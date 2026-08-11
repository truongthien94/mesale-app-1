import { useMemo, useState } from "react";
import { useInfiniteQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import {
  ActivityIndicator,
  FlatList,
  Pressable,
  StyleSheet,
  Text,
  View
} from "react-native";
import {
  CalendarCheck,
  Check,
  Flame,
  Gift,
  LockKeyhole
} from "lucide-react-native";
import { useSafeAreaInsets } from "react-native-safe-area-context";
import { ApiError } from "@/api/client";
import { FormErrorSummary } from "@/components/FormErrorSummary";
import { fetchCheckin, performCheckin, type CheckinResult } from "@/features/earn/api";
import { invalidateRewardCaches } from "@/features/earn/cache";
import {
  buildStreakDays,
  findNextMilestone,
  milestoneProgress,
  normalizeMilestones,
  type StreakDayState
} from "@/features/earn/checkinPresentation";
import { ListFooterLoading, formatDate, formatMoney } from "@/features/earn/ui";
import { getDeviceLocale } from "@/i18n";
import { useTheme } from "@/theme/ThemeProvider";
import type { Theme } from "@/theme/tokens";

function compactMoney(amount: number): string {
  return `${new Intl.NumberFormat("vi-VN", { maximumFractionDigits: 0 }).format(amount)}đ`;
}

function StreakIcon({ state, checkedToday }: { state: StreakDayState; checkedToday: boolean }) {
  if (state === "claimed" || (state === "current" && checkedToday)) {
    return <Check color={state === "current" ? "#ea580c" : "#16a34a"} size={18} strokeWidth={3} />;
  }
  if (state === "current") return <CalendarCheck color="#ea580c" size={18} strokeWidth={2.5} />;
  return <LockKeyhole color="#94a3b8" size={16} strokeWidth={2.2} />;
}

function FullScreenState({
  actionLabel,
  alert = false,
  insets,
  loading = false,
  message,
  onAction,
  styles,
  title
}: {
  actionLabel?: string;
  alert?: boolean;
  insets: { bottom: number; left: number; right: number; top: number };
  loading?: boolean;
  message?: string;
  onAction?: () => void;
  styles: ReturnType<typeof createStyles>;
  title?: string;
}) {
  return (
    <View
      accessibilityLabel={loading ? message ?? "Loading" : undefined}
      accessibilityLiveRegion={alert ? "polite" : undefined}
      accessibilityRole={loading ? "progressbar" : alert ? "alert" : undefined}
      style={[
        styles.stateScreen,
        {
          paddingBottom: insets.bottom + 24,
          paddingLeft: insets.left + 24,
          paddingRight: insets.right + 24,
          paddingTop: insets.top + 24
        }
      ]}
    >
      {loading ? <ActivityIndicator color="#f97316" size="large" /> : null}
      {title ? <Text accessibilityRole="header" style={styles.stateTitle}>{title}</Text> : null}
      {message ? <Text style={styles.stateMessage}>{message}</Text> : null}
      {actionLabel && onAction ? (
        <Pressable
          accessibilityLabel={actionLabel}
          accessibilityRole="button"
          onPress={onAction}
          style={({ pressed }) => [styles.stateAction, pressed && styles.pressed]}
        >
          <Text style={styles.stateActionText}>{actionLabel}</Text>
        </Pressable>
      ) : null}
    </View>
  );
}

export default function CheckinScreen() {
  const insets = useSafeAreaInsets();
  const queryClient = useQueryClient();
  const { colors, scheme } = useTheme();
  const styles = useMemo(() => createStyles(colors, scheme), [colors, scheme]);
  const vi = getDeviceLocale() === "vi";
  const [result, setResult] = useState<CheckinResult | null>(null);
  const query = useInfiniteQuery({
    queryKey: ["earn", "checkin"],
    initialPageParam: 1,
    queryFn: ({ pageParam, signal }) => fetchCheckin(pageParam, signal),
    getNextPageParam: (page) => page.history.pagination.current_page < page.history.pagination.last_page
      ? page.history.pagination.current_page + 1
      : undefined
  });
  const mutation = useMutation({
    mutationFn: performCheckin,
    onSuccess: async (response) => {
      setResult(response.data);
      await Promise.all([
        queryClient.invalidateQueries({ queryKey: ["earn", "checkin"] }),
        invalidateRewardCaches(queryClient)
      ]);
    }
  });

  if (query.isPending) {
    return <FullScreenState insets={insets} loading message={vi ? "Đang tải điểm danh..." : "Loading check-in..."} styles={styles} />;
  }
  if (query.isError) {
    const offline = query.error instanceof ApiError && query.error.isNetworkError;
    return (
      <FullScreenState
        actionLabel={vi ? "Thử lại" : "Retry"}
        alert
        insets={insets}
        message={query.error instanceof Error ? query.error.message : undefined}
        onAction={() => void query.refetch()}
        styles={styles}
        title={offline
          ? (vi ? "Bạn đang ngoại tuyến" : "You are offline")
          : (vi ? "Không thể tải điểm danh" : "Unable to load check-in")}
      />
    );
  }

  const firstPage = query.data.pages[0];
  if (!firstPage) return <FullScreenState insets={insets} styles={styles} title={vi ? "Không có dữ liệu điểm danh" : "No check-in data"} />;
  const history = query.data.pages.flatMap((page) => page.history.items);
  const milestones = normalizeMilestones(firstPage.milestones);
  const nextMilestone = findNextMilestone(milestones, firstPage.current_streak);
  const progress = nextMilestone ? milestoneProgress(firstPage.current_streak, nextMilestone.days) : 0;
  const streakDays = buildStreakDays(
    firstPage.current_streak,
    firstPage.has_checked_in_today,
    firstPage.can_checkin,
    firstPage.reward_coins
  );
  const mutationError = mutation.error instanceof ApiError ? mutation.error : null;

  return (
    <FlatList
      contentContainerStyle={[styles.listContent, { paddingBottom: insets.bottom + 24 }]}
      data={history}
      initialNumToRender={8}
      keyExtractor={(item, index) => `${item.checked_in_date ?? "unknown"}-${index}`}
      ListEmptyComponent={(
        <View style={styles.empty}>
          <Text style={styles.emptyTitle}>{vi ? "Chưa có lịch sử" : "No history yet"}</Text>
          <Text style={styles.emptyMessage}>{vi ? "Điểm danh hôm nay để bắt đầu chuỗi của bạn." : "Check in today to start your streak."}</Text>
        </View>
      )}
      ListFooterComponent={<ListFooterLoading visible={query.isFetchingNextPage} />}
      ListHeaderComponent={(
        <View style={[styles.headerContent, { paddingTop: insets.top + 12 }]}>
          <View style={styles.streakCard}>
            <View style={styles.heroHeading}>
              <View style={styles.heroIcon}>
                <CalendarCheck color="#f97316" size={25} strokeWidth={2.4} />
              </View>
              <View style={styles.heroCopy}>
                <Text accessibilityRole="header" style={styles.heroTitle}>
                  {vi ? "CHUỖI ĐIỂM DANH ✨" : "CHECK-IN STREAK ✨"}
                </Text>
                <Text style={styles.heroSubtitle}>
                  {vi ? "Điểm danh mỗi ngày để nhận thưởng hấp dẫn" : "Check in daily to receive configured rewards"}
                </Text>
              </View>
            </View>

            <View accessibilityLabel={vi ? `Chuỗi hiện tại ${firstPage.current_streak} ngày` : `Current streak ${firstPage.current_streak} days`} style={styles.streakRow}>
              {streakDays.map((item, index) => (
                <View
                  accessibilityLabel={vi
                    ? `Ngày ${item.day}, thưởng ${compactMoney(item.reward)}, trạng thái ${item.state === "claimed" ? "đã nhận" : item.state === "current" ? "hiện tại" : "đang khóa"}`
                    : `Day ${item.day}, reward ${compactMoney(item.reward)}, ${item.state}`}
                  accessible
                  key={item.day}
                  style={styles.daySlot}
                >
                  {index < streakDays.length - 1 ? <View style={[styles.dayConnector, item.state !== "locked" && styles.dayConnectorActive]} /> : null}
                  <View style={[
                    styles.dayCircle,
                    item.state === "claimed" && styles.dayCircleClaimed,
                    item.state === "current" && styles.dayCircleCurrent
                  ]}>
                    <StreakIcon checkedToday={firstPage.has_checked_in_today} state={item.state} />
                  </View>
                  <Text adjustsFontSizeToFit minimumFontScale={0.7} numberOfLines={1} style={styles.dayLabel}>{vi ? `Ngày ${item.day}` : `Day ${item.day}`}</Text>
                  <Text adjustsFontSizeToFit minimumFontScale={0.65} numberOfLines={1} style={[styles.dayReward, item.state === "current" && styles.dayRewardCurrent]}>
                    +{compactMoney(item.reward)}
                  </Text>
                </View>
              ))}
            </View>

            <Pressable
              accessibilityRole="button"
              accessibilityState={{ busy: mutation.isPending, disabled: !firstPage.can_checkin || mutation.isPending }}
              disabled={!firstPage.can_checkin || mutation.isPending}
              onPress={() => {
                setResult(null);
                mutation.mutate();
              }}
              style={({ pressed }) => [
                styles.checkinButton,
                (!firstPage.can_checkin || mutation.isPending) && styles.checkinButtonDisabled,
                pressed && styles.pressed
              ]}
            >
              {mutation.isPending ? <ActivityIndicator color="#ffffff" /> : <CalendarCheck color="#ffffff" size={19} strokeWidth={2.5} />}
              <Text style={styles.checkinButtonText}>
                {firstPage.has_checked_in_today
                  ? (vi ? "Đã điểm danh hôm nay" : "Checked in today")
                  : (vi ? `Điểm danh nhận +${compactMoney(firstPage.reward_coins)}` : `Check in for +${compactMoney(firstPage.reward_coins)}`)}
              </Text>
            </Pressable>
            {!firstPage.can_checkin && firstPage.ineligible_reason ? <Text style={styles.heroReason}>{firstPage.ineligible_reason}</Text> : null}
          </View>

          {mutationError ? <FormErrorSummary errors={mutationError.errors} message={mutationError.message} /> : null}
          {result ? (
            <View accessibilityLiveRegion="polite" style={styles.feedback}>
              <View style={styles.feedbackIcon}><Check color="#16a34a" size={18} strokeWidth={3} /></View>
              <View style={styles.feedbackCopy}>
                <Text style={styles.successTitle}>{vi ? "Điểm danh thành công" : "Check-in complete"}</Text>
                <Text style={styles.feedbackText}>
                  +{formatMoney(result.coins_earned)} · {vi ? "Chuỗi" : "Streak"} {result.streak_days} {vi ? "ngày" : "days"}
                  {result.is_bonus ? ` · ${vi ? "Thưởng mốc" : "Milestone bonus"} +${formatMoney(result.bonus_amount)}` : ""}
                </Text>
              </View>
            </View>
          ) : null}

          {nextMilestone ? (
            <View style={styles.nextMilestoneCard}>
              <View style={styles.milestoneHeader}>
                <View style={styles.flameIcon}><Flame color="#f97316" fill="#f97316" size={22} /></View>
                <View style={styles.milestoneCopy}>
                  <Text style={styles.nextMilestoneTitle}>
                    {vi ? "Mốc thưởng chuỗi tiếp theo:" : "Next streak milestone:"} <Text style={styles.accentText}>{nextMilestone.days} {vi ? "ngày" : "days"}</Text>
                  </Text>
                  <Text style={styles.nextMilestoneSubtitle}>
                    {vi ? "Nhận ngay thêm" : "Receive an extra"} <Text style={styles.positiveText}>+{compactMoney(nextMilestone.amount)}</Text> {vi ? "khi hoàn thành chuỗi" : "when the streak is completed"}
                  </Text>
                </View>
                <Text style={styles.progressLabel}>{firstPage.current_streak} / {nextMilestone.days} {vi ? "ngày" : "days"}</Text>
              </View>
              <View style={styles.progressTrack}><View style={[styles.progressFill, { width: `${progress}%` }]} /></View>
            </View>
          ) : null}

          {milestones.length > 0 ? (
            <View style={styles.allMilestonesCard}>
              <View style={styles.sectionHeading}>
                <View style={styles.giftIcon}><Gift color="#f97316" size={23} strokeWidth={2.2} /></View>
                <View style={styles.sectionHeadingCopy}>
                  <Text style={styles.sectionTitle}>{vi ? "TẤT CẢ MỐC THƯỞNG TÍCH LŨY" : "ALL CUMULATIVE REWARDS"}</Text>
                  <Text style={styles.sectionSubtitle}>{vi ? "Điểm danh liên tục để nhận thưởng lớn hơn" : "Keep your streak to unlock larger rewards"}</Text>
                </View>
              </View>
              <View style={styles.milestoneGrid}>
                {milestones.map((milestone) => {
                  const completed = firstPage.current_streak >= milestone.days;
                  return (
                    <View key={milestone.days} style={[styles.rewardCard, completed && styles.rewardCardCompleted]}>
                      <View style={[styles.rewardFlag, completed && styles.rewardFlagCompleted]}>
                        <Text style={styles.rewardFlagValue}>{milestone.days}</Text>
                        <Text style={styles.rewardFlagUnit}>{vi ? "NGÀY" : "DAYS"}</Text>
                      </View>
                      <Gift color={completed ? "#16a34a" : "#f97316"} size={29} strokeWidth={2} />
                      <Text style={styles.rewardCardTitle}>{vi ? `Chuỗi ${milestone.days} ngày` : `${milestone.days}-day streak`}</Text>
                      <Text style={[styles.rewardCardAmount, completed && styles.positiveText]}>+{compactMoney(milestone.amount)}</Text>
                      {completed ? <Text style={styles.completedLabel}>{vi ? "Đã đạt" : "Completed"}</Text> : null}
                    </View>
                  );
                })}
              </View>
            </View>
          ) : null}

          <Text style={styles.historyTitle}>{vi ? "Lịch sử điểm danh" : "Check-in history"}</Text>
        </View>
      )}
      onEndReached={() => {
        if (query.hasNextPage && !query.isFetchingNextPage) void query.fetchNextPage();
      }}
      onEndReachedThreshold={0.4}
      renderItem={({ item }) => (
        <View style={styles.historyCard}>
          <View style={styles.historyRow}>
            <View>
              <Text style={styles.historyDate}>{formatDate(item.checked_in_date)}</Text>
              <Text style={styles.historyStreak}>{vi ? "Chuỗi" : "Streak"}: {item.streak_days} {vi ? "ngày" : "days"}</Text>
            </View>
            <Text style={styles.historyAmount}>+{formatMoney(item.coins_earned)}</Text>
          </View>
        </View>
      )}
      showsVerticalScrollIndicator={false}
      style={styles.screen}
    />
  );
}

function createStyles(colors: Theme["colors"], scheme: Theme["scheme"]) {
  const dark = scheme === "dark";
  return StyleSheet.create({
    screen: { backgroundColor: dark ? "#08111f" : "#f4f1ed", flex: 1 },
    listContent: { gap: 14, paddingHorizontal: 16 },
    headerContent: { gap: 16 },
    streakCard: { backgroundColor: colors.surface, borderColor: colors.border, borderRadius: 18, borderWidth: 1, gap: 18, padding: 16, shadowColor: "#0f172a", shadowOffset: { width: 0, height: 5 }, shadowOpacity: dark ? 0.18 : 0.06, shadowRadius: 12, elevation: 2 },
    heroHeading: { alignItems: "center", flexDirection: "row", gap: 12 },
    heroIcon: { alignItems: "center", backgroundColor: dark ? "#3b2910" : "#fff7ed", borderColor: dark ? "#854d0e" : "#fed7aa", borderRadius: 10, borderWidth: 1, height: 42, justifyContent: "center", width: 42 },
    heroCopy: { flex: 1, gap: 3 },
    heroTitle: { color: colors.text, fontSize: 17, fontWeight: "900", letterSpacing: -0.2 },
    heroSubtitle: { color: colors.mutedText, fontSize: 12.5, lineHeight: 18 },
    streakRow: { flexDirection: "row", justifyContent: "space-between", paddingTop: 2 },
    daySlot: { alignItems: "center", flex: 1, gap: 4, minWidth: 0, position: "relative" },
    dayConnector: { backgroundColor: dark ? "#334155" : "#e2e8f0", height: 1, position: "absolute", right: -4, top: 18, width: 8 },
    dayConnectorActive: { backgroundColor: "#fdba74" },
    dayCircle: { alignItems: "center", backgroundColor: dark ? "#182334" : "#f1f5f9", borderColor: dark ? "#334155" : "#e2e8f0", borderRadius: 999, borderWidth: 1, height: 38, justifyContent: "center", width: 38 },
    dayCircleClaimed: { backgroundColor: dark ? "#0d3327" : "#ecfdf5", borderColor: dark ? "#166534" : "#86efac" },
    dayCircleCurrent: { backgroundColor: dark ? "#3b2910" : "#fff7ed", borderColor: "#fb923c", borderWidth: 2 },
    dayLabel: { color: colors.mutedText, fontSize: 9.5, fontWeight: "700" },
    dayReward: { color: colors.text, fontSize: 10.5, fontWeight: "900" },
    dayRewardCurrent: { color: "#ea580c" },
    checkinButton: { alignItems: "center", alignSelf: "center", backgroundColor: "#f97316", borderRadius: 12, flexDirection: "row", gap: 8, justifyContent: "center", minHeight: 46, paddingHorizontal: 22 },
    checkinButtonDisabled: { backgroundColor: dark ? "#475569" : "#cbd5e1" },
    checkinButtonText: { color: "#ffffff", fontSize: 14, fontWeight: "900" },
    pressed: { opacity: 0.78 },
    heroReason: { color: colors.mutedText, fontSize: 12, lineHeight: 18, textAlign: "center" },
    feedback: { alignItems: "center", backgroundColor: dark ? "#0d3327" : "#ecfdf5", borderColor: dark ? "#166534" : "#86efac", borderRadius: 14, borderWidth: 1, flexDirection: "row", gap: 10, padding: 13 },
    feedbackIcon: { alignItems: "center", backgroundColor: dark ? "#14532d" : "#dcfce7", borderRadius: 999, height: 34, justifyContent: "center", width: 34 },
    feedbackCopy: { flex: 1, gap: 2 },
    successTitle: { color: dark ? "#86efac" : "#166534", fontSize: 14, fontWeight: "900" },
    feedbackText: { color: dark ? "#bbf7d0" : "#15803d", fontSize: 12.5, lineHeight: 18 },
    nextMilestoneCard: { backgroundColor: dark ? "#2f2017" : "#fffaf6", borderColor: dark ? "#7c2d12" : "#fed7aa", borderRadius: 16, borderWidth: 1, gap: 14, padding: 15 },
    milestoneHeader: { alignItems: "center", flexDirection: "row", gap: 10 },
    flameIcon: { alignItems: "center", backgroundColor: dark ? "#431d12" : "#ffedd5", borderRadius: 999, height: 42, justifyContent: "center", width: 42 },
    milestoneCopy: { flex: 1, gap: 4 },
    nextMilestoneTitle: { color: colors.text, fontSize: 13.5, fontWeight: "800", lineHeight: 19 },
    nextMilestoneSubtitle: { color: colors.mutedText, fontSize: 11.5, lineHeight: 17 },
    accentText: { color: "#ea580c", fontWeight: "900" },
    positiveText: { color: "#16a34a", fontWeight: "900" },
    progressLabel: { color: "#ea580c", fontSize: 12, fontWeight: "900" },
    progressTrack: { backgroundColor: dark ? "#4c2b1e" : "#ffeadb", borderRadius: 999, height: 7, overflow: "hidden" },
    progressFill: { backgroundColor: "#f97316", borderRadius: 999, height: "100%" },
    allMilestonesCard: { backgroundColor: colors.surface, borderColor: colors.border, borderRadius: 18, borderWidth: 1, gap: 16, padding: 16, shadowColor: "#0f172a", shadowOffset: { width: 0, height: 5 }, shadowOpacity: dark ? 0.18 : 0.05, shadowRadius: 12, elevation: 2 },
    sectionHeading: { alignItems: "center", flexDirection: "row", gap: 12 },
    giftIcon: { alignItems: "center", backgroundColor: dark ? "#3b2910" : "#fff3df", borderRadius: 999, height: 44, justifyContent: "center", width: 44 },
    sectionHeadingCopy: { flex: 1, gap: 3 },
    sectionTitle: { color: colors.text, fontSize: 15.5, fontWeight: "900", letterSpacing: -0.2 },
    sectionSubtitle: { color: colors.mutedText, fontSize: 12, lineHeight: 17 },
    milestoneGrid: { flexDirection: "row", flexWrap: "wrap", gap: 10 },
    rewardCard: { alignItems: "center", backgroundColor: dark ? "#162132" : "#fffcf8", borderColor: dark ? "#475569" : "#fed7aa", borderRadius: 12, borderWidth: 1, flexBasis: "47%", gap: 5, minWidth: 126, overflow: "hidden", paddingBottom: 12, paddingHorizontal: 10 },
    rewardCardCompleted: { backgroundColor: dark ? "#0d3327" : "#f0fdf4", borderColor: dark ? "#166534" : "#86efac" },
    rewardFlag: { alignSelf: "flex-start", backgroundColor: "#f97316", borderBottomLeftRadius: 4, borderBottomRightRadius: 4, minWidth: 38, paddingHorizontal: 6, paddingVertical: 5 },
    rewardFlagCompleted: { backgroundColor: "#16a34a" },
    rewardFlagValue: { color: "#ffffff", fontSize: 14, fontWeight: "900", textAlign: "center" },
    rewardFlagUnit: { color: "#ffffff", fontSize: 7, fontWeight: "900", textAlign: "center" },
    rewardCardTitle: { color: colors.text, fontSize: 11.5, fontWeight: "800", textAlign: "center" },
    rewardCardAmount: { color: "#ea580c", fontSize: 17, fontWeight: "900" },
    completedLabel: { color: "#16a34a", fontSize: 10, fontWeight: "800" },
    historyTitle: { color: colors.text, fontSize: 17, fontWeight: "900", marginTop: 2 },
    historyCard: { backgroundColor: colors.surface, borderColor: colors.border, borderRadius: 18, borderWidth: 1, padding: 16, shadowColor: "#0f172a", shadowOffset: { width: 0, height: 4 }, shadowOpacity: dark ? 0.18 : 0.05, shadowRadius: 10, elevation: 2 },
    historyRow: { alignItems: "center", flexDirection: "row", gap: 12, justifyContent: "space-between" },
    historyDate: { color: colors.text, fontSize: 14.5, fontWeight: "800" },
    historyStreak: { color: colors.mutedText, fontSize: 12.5, marginTop: 3 },
    historyAmount: { color: colors.primary, fontSize: 17, fontWeight: "900" },
    stateScreen: { alignItems: "center", backgroundColor: dark ? "#08111f" : "#f4f1ed", flex: 1, gap: 10, justifyContent: "center" },
    stateTitle: { color: colors.text, fontSize: 20, fontWeight: "900", textAlign: "center" },
    stateMessage: { color: colors.mutedText, fontSize: 15, lineHeight: 22, textAlign: "center" },
    stateAction: { alignItems: "center", backgroundColor: "#f97316", borderRadius: 12, justifyContent: "center", marginTop: 4, minHeight: 48, minWidth: 120, paddingHorizontal: 22 },
    stateActionText: { color: "#ffffff", fontSize: 15, fontWeight: "800" },
    empty: { alignItems: "center", backgroundColor: colors.surface, borderColor: colors.border, borderRadius: 18, borderWidth: 1, gap: 6, justifyContent: "center", minHeight: 180, padding: 18 },
    emptyTitle: { color: colors.text, fontSize: 18, fontWeight: "900", textAlign: "center" },
    emptyMessage: { color: colors.mutedText, fontSize: 14, lineHeight: 21, textAlign: "center" }
  });
}
