import { useState } from "react";
import { useInfiniteQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { FlatList, StyleSheet, Text, View } from "react-native";
import { useSafeAreaInsets } from "react-native-safe-area-context";
import { ApiError } from "@/api/client";
import { EmptyState, ErrorState, LoadingState, OfflineState } from "@/components/AsyncState";
import { FormErrorSummary } from "@/components/FormErrorSummary";
import { fetchCheckin, performCheckin, type CheckinResult } from "@/features/earn/api";
import { invalidateRewardCaches } from "@/features/earn/cache";
import { ActionButton, Card, ListFooterLoading, ScreenHeader, earnStyles, formatDate, formatMoney } from "@/features/earn/ui";
import { getDeviceLocale } from "@/i18n";
import { colors, spacing } from "@/theme/tokens";

export default function CheckinScreen() {
  const insets = useSafeAreaInsets();
  const queryClient = useQueryClient();
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

  if (query.isPending) return <LoadingState label={vi ? "Đang tải điểm danh..." : "Loading check-in..."} />;
  if (query.isError) {
    const props = {
      actionLabel: vi ? "Thử lại" : "Retry",
      message: query.error instanceof Error ? query.error.message : undefined,
      onAction: () => void query.refetch(),
      title: vi ? "Không thể tải điểm danh" : "Unable to load check-in"
    };
    return query.error instanceof ApiError && query.error.isNetworkError ? <OfflineState {...props} /> : <ErrorState {...props} />;
  }

  const firstPage = query.data.pages[0];
  if (!firstPage) return <EmptyState title={vi ? "Không có dữ liệu điểm danh" : "No check-in data"} />;
  const history = query.data.pages.flatMap((page) => page.history.items);
  const milestones = Object.entries(firstPage.milestones)
    .map(([days, amount]) => ({ days: Number(days), amount }))
    .filter((item) => Number.isFinite(item.days))
    .sort((left, right) => left.days - right.days);
  const nextMilestone = milestones.find((item) => item.days > firstPage.current_streak) ?? milestones.at(-1);
  const progress = nextMilestone ? Math.min(100, (firstPage.current_streak / nextMilestone.days) * 100) : 0;
  const mutationError = mutation.error instanceof ApiError ? mutation.error : null;

  return (
    <FlatList
      contentContainerStyle={[earnStyles.listContent, { paddingBottom: insets.bottom + spacing.lg }]}
      contentInsetAdjustmentBehavior="automatic"
      data={history}
      keyExtractor={(item, index) => `${item.checked_in_date ?? "unknown"}-${index}`}
      ListEmptyComponent={<EmptyState message={vi ? "Điểm danh hôm nay để bắt đầu chuỗi." : "Check in today to start your streak."} style={styles.empty} title={vi ? "Chưa có lịch sử" : "No history yet"} />}
      ListFooterComponent={<ListFooterLoading visible={query.isFetchingNextPage} />}
      ListHeaderComponent={
        <View style={styles.headerContent}>
          <ScreenHeader
            eyebrow={vi ? "Thưởng mỗi ngày" : "Daily reward"}
            subtitle={vi ? "Điểm danh mỗi ngày để nhận thưởng, không cam kết thu nhập." : "Check in daily for configured rewards; earnings are not guaranteed."}
            title={vi ? "Điểm danh" : "Daily check-in"}
          />

          <View style={styles.hero}>
            <Text style={styles.heroEyebrow}>{vi ? "CHUỖI HIỆN TẠI" : "CURRENT STREAK"}</Text>
            <Text style={styles.heroValue}>{firstPage.current_streak} {vi ? "ngày" : "days"}</Text>
            <Text style={styles.heroReward}>+{formatMoney(firstPage.reward_coins)} / {vi ? "ngày" : "day"}</Text>
            <ActionButton
              disabled={!firstPage.can_checkin}
              label={firstPage.has_checked_in_today ? (vi ? "Đã điểm danh hôm nay" : "Checked in today") : (vi ? "Điểm danh ngay" : "Check in now")}
              loading={mutation.isPending}
              onPress={() => {
                setResult(null);
                mutation.mutate();
              }}
            />
            {!firstPage.can_checkin && firstPage.ineligible_reason ? <Text style={styles.heroReason}>{firstPage.ineligible_reason}</Text> : null}
          </View>

          {mutationError ? <FormErrorSummary errors={mutationError.errors} message={mutationError.message} /> : null}
          {result ? (
            <View accessibilityLiveRegion="polite" style={earnStyles.feedback}>
              <Text style={styles.successTitle}>{vi ? "Điểm danh thành công" : "Check-in complete"}</Text>
              <Text style={earnStyles.feedbackText}>
                +{formatMoney(result.coins_earned)} · {vi ? "Chuỗi" : "Streak"} {result.streak_days} {vi ? "ngày" : "days"}
                {result.is_bonus ? ` · ${vi ? "Thưởng mốc" : "Milestone bonus"} +${formatMoney(result.bonus_amount)}` : ""}
              </Text>
            </View>
          ) : null}

          {nextMilestone ? (
            <Card>
              <View style={earnStyles.rowBetween}>
                <Text style={earnStyles.sectionTitle}>{vi ? "Mốc tiếp theo" : "Next milestone"}</Text>
                <Text style={styles.milestone}>{nextMilestone.days}D · +{formatMoney(nextMilestone.amount)}</Text>
              </View>
              <View style={earnStyles.progressTrack}><View style={[earnStyles.progressFill, { width: `${progress}%` }]} /></View>
              <Text style={earnStyles.body}>{firstPage.current_streak}/{nextMilestone.days} {vi ? "ngày liên tục" : "consecutive days"}</Text>
            </Card>
          ) : null}

          {milestones.length > 0 ? (
            <Card>
              <Text style={earnStyles.sectionTitle}>{vi ? "Các mốc thưởng" : "Reward milestones"}</Text>
              <View style={styles.milestoneRows}>
                {milestones.map((milestone) => (
                  <View key={milestone.days} style={earnStyles.rowBetween}>
                    <Text style={earnStyles.strong}>{milestone.days} {vi ? "ngày" : "days"}</Text>
                    <Text style={styles.milestone}>+{formatMoney(milestone.amount)}</Text>
                  </View>
                ))}
              </View>
            </Card>
          ) : null}

          <Text style={earnStyles.sectionTitle}>{vi ? "Lịch sử điểm danh" : "Check-in history"}</Text>
        </View>
      }
      onEndReached={() => {
        if (query.hasNextPage && !query.isFetchingNextPage) void query.fetchNextPage();
      }}
      onEndReachedThreshold={0.4}
      renderItem={({ item }) => (
        <Card>
          <View style={earnStyles.rowBetween}>
            <View>
              <Text style={earnStyles.strong}>{formatDate(item.checked_in_date)}</Text>
              <Text style={earnStyles.body}>{vi ? "Chuỗi" : "Streak"}: {item.streak_days} {vi ? "ngày" : "days"}</Text>
            </View>
            <Text style={earnStyles.amount}>+{formatMoney(item.coins_earned)}</Text>
          </View>
        </Card>
      )}
      style={earnStyles.screen}
    />
  );
}

const styles = StyleSheet.create({
  headerContent: { gap: spacing.md },
  hero: { backgroundColor: "#ff5b4d", borderRadius: 24, gap: spacing.md, padding: spacing.lg },
  heroEyebrow: { color: "#ffedd5", fontSize: 12, fontWeight: "900", letterSpacing: 1.2 },
  heroValue: { color: colors.surface, fontSize: 34, fontWeight: "900" },
  heroReward: { color: colors.surface, fontSize: 16, fontWeight: "800" },
  heroReason: { color: "#fff7ed", fontSize: 13, lineHeight: 19 },
  successTitle: { color: "#9a3412", fontSize: 15, fontWeight: "900" },
  milestone: { color: colors.primary, fontSize: 14, fontWeight: "900" },
  milestoneRows: { gap: spacing.md },
  empty: { minHeight: 220 }
});
