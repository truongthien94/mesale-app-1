import { useEffect, useRef, useState } from "react";
import { useInfiniteQuery } from "@tanstack/react-query";
import * as Clipboard from "expo-clipboard";
import { LinearGradient } from "expo-linear-gradient";
import {
  Check,
  ChevronRight,
  Copy,
  Gift,
  Infinity as InfinityIcon,
  Share2,
  UsersRound
} from "lucide-react-native";
import {
  Alert,
  FlatList,
  Pressable,
  RefreshControl,
  Share,
  StyleSheet,
  Text,
  View
} from "react-native";
import { useSafeAreaInsets } from "react-native-safe-area-context";
import { ApiError } from "@/api/client";
import { EmptyState, ErrorState, LoadingState, OfflineState } from "@/components/AsyncState";
import { referralsQueryOptions, type ReferralCommission, type ReferralMember } from "@/features/earn/api";
import { ListFooterLoading, StatusBadge, formatDate, formatMoney } from "@/features/earn/ui";
import { getDeviceLocale } from "@/i18n";
import { useTheme } from "@/theme/ThemeProvider";

type ReferralView = "overview" | "network" | "history";

type ReferralListItem =
  | { key: string; kind: "commission"; value: ReferralCommission }
  | { key: string; kind: "member"; level: 1 | 2; value: ReferralMember };

function formatRate(value: number): string {
  return new Intl.NumberFormat("vi-VN", { maximumFractionDigits: 1 }).format(value);
}

function ReferralChip({ label, selected, onPress }: { label: string; selected: boolean; onPress: () => void }) {
  const { colors, scheme } = useTheme();
  const selectedBackground = scheme === "dark" ? "#12365b" : "#eaf5ff";

  return (
    <Pressable
      accessibilityRole="button"
      accessibilityState={{ selected }}
      onPress={onPress}
      style={({ pressed }) => [
        styles.chip,
        { backgroundColor: selected ? selectedBackground : colors.surface, borderColor: selected ? "#2f9af5" : colors.border },
        pressed && styles.pressed
      ]}
    >
      <Text style={[styles.chipText, { color: selected ? "#2f9af5" : colors.mutedText }]}>{label}</Text>
    </Pressable>
  );
}

export default function ReferralsScreen() {
  const insets = useSafeAreaInsets();
  const { colors, scheme } = useTheme();
  const vi = getDeviceLocale() === "vi";
  const listRef = useRef<FlatList<ReferralListItem>>(null);
  const activityOffset = useRef(0);
  const [view, setView] = useState<ReferralView>("overview");
  const [level, setLevel] = useState<"1" | "2" | undefined>();
  const [status, setStatus] = useState<"pending" | "approved" | undefined>();
  const [copied, setCopied] = useState(false);
  const query = useInfiniteQuery(referralsQueryOptions({ level, status }));

  useEffect(() => {
    if (!copied) return;
    const timer = setTimeout(() => setCopied(false), 1800);
    return () => clearTimeout(timer);
  }, [copied]);

  if (query.isPending) return <LoadingState label={vi ? "Đang tải dữ liệu giới thiệu..." : "Loading referrals..."} />;
  if (query.isError) {
    const props = {
      actionLabel: vi ? "Thử lại" : "Retry",
      message: query.error instanceof Error ? query.error.message : undefined,
      onAction: () => void query.refetch(),
      title: vi ? "Không thể tải giới thiệu" : "Unable to load referrals"
    };
    return query.error instanceof ApiError && query.error.isNetworkError ? <OfflineState {...props} /> : <ErrorState {...props} />;
  }

  const firstPage = query.data.pages[0];
  if (!firstPage) return <EmptyState title={vi ? "Không có dữ liệu giới thiệu" : "No referral data"} />;

  const commissions = query.data.pages.flatMap((page) => page.commissions.items);
  const networkMembers = [
    ...(level !== "2" ? firstPage.f1_members.map((value) => ({ key: `f1-${value.id}`, kind: "member" as const, level: 1 as const, value })) : []),
    ...(level !== "1" ? firstPage.f2_members.map((value) => ({ key: `f2-${value.id}`, kind: "member" as const, level: 2 as const, value })) : [])
  ];
  const listItems: ReferralListItem[] = view === "history"
    ? commissions.map((value) => ({ key: `commission-${value.id}`, kind: "commission", value }))
    : view === "network" ? networkMembers : [];
  const referralCode = firstPage.referral_code?.trim() ?? "";
  const referralLink = firstPage.referral_link;
  const f1Rate = formatRate(firstPage.rates.f1_rate);
  const screenBackground = scheme === "dark" ? "#08111f" : "#f2f7fc";
  const blueSoft = scheme === "dark" ? "#102a44" : "#eaf5ff";
  const sectionCard = colors.surface;

  async function copyReferralCode() {
    if (!referralCode) return;
    try {
      await Clipboard.setStringAsync(referralCode);
      setCopied(true);
    } catch {
      Alert.alert(vi ? "Không thể sao chép" : "Unable to copy", vi ? "Vui lòng thử lại sau." : "Please try again.");
    }
  }

  function shareReferral() {
    const message = vi
      ? `Tham gia Mê Sale cùng tôi với mã ${referralCode || "giới thiệu của tôi"}: ${referralLink}`
      : `Join Mê Sale with my referral code ${referralCode || ""}: ${referralLink}`;
    void Share.share({ message });
  }

  function showNetwork() {
    setLevel(undefined);
    setView("network");
    requestAnimationFrame(() => listRef.current?.scrollToOffset({ animated: true, offset: Math.max(0, activityOffset.current - 12) }));
  }

  return (
    <FlatList
      ref={listRef}
      contentContainerStyle={[styles.listContent, { paddingBottom: insets.bottom + 28, paddingTop: insets.top + 16 }]}
      data={listItems}
      keyExtractor={(item) => item.key}
      ListEmptyComponent={view === "overview" ? null : (
        <EmptyState
          message={view === "history"
            ? (vi ? "Chưa có hoa hồng phù hợp bộ lọc." : "No commissions match these filters.")
            : (vi ? "Chưa có thành viên trong mạng lưới này." : "This referral network is empty.")}
          style={styles.empty}
          title={vi ? "Chưa có dữ liệu" : "Nothing here yet"}
        />
      )}
      ListFooterComponent={<ListFooterLoading visible={view === "history" && query.isFetchingNextPage} />}
      ListHeaderComponent={(
        <View style={styles.headerContent}>
          <Text accessibilityRole="header" style={[styles.screenTitle, { color: colors.text }]}>
            {vi ? "Giới thiệu bạn bè" : "Refer friends"}
          </Text>

          <View style={[styles.policyCard, { backgroundColor: sectionCard, borderColor: "#7bbcff" }]}>
            <View style={[styles.policyIcon, { backgroundColor: blueSoft }]}>
              <InfinityIcon color="#2f9af5" size={24} strokeWidth={2.4} />
            </View>
            <Text style={[styles.policyText, { color: colors.text }]}>
              {vi ? "Khi bạn bè mua sắm qua Mê Sale, bạn nhận " : "When friends shop with Mê Sale, you receive "}
              <Text style={styles.policyEmphasis}>
                {vi ? `${f1Rate}% hoa hồng giới thiệu theo chính sách hiện hành.` : `${f1Rate}% referral commission under the current policy.`}
              </Text>
            </Text>
          </View>

          <LinearGradient
            colors={scheme === "dark" ? ["#1976d2", "#0752a6"] : ["#3ba8ff", "#0872df"]}
            end={{ x: 1, y: 1 }}
            start={{ x: 0, y: 0 }}
            style={styles.codeCard}
          >
            <View pointerEvents="none" style={styles.codeDecoration} />
            <Text style={styles.codeLabel}>{vi ? "Mã giới thiệu của bạn" : "Your referral code"}</Text>
            <View style={styles.codeRow}>
              <Text adjustsFontSizeToFit numberOfLines={1} selectable style={styles.codeText}>
                {referralCode || (vi ? "CHƯA CÓ MÃ" : "NO CODE")}
              </Text>
              {referralCode ? <Copy color="rgba(255,255,255,0.78)" size={20} /> : null}
            </View>
            <View style={styles.codeActions}>
              <Pressable
                accessibilityLabel={vi ? "Sao chép mã giới thiệu" : "Copy referral code"}
                accessibilityRole="button"
                disabled={!referralCode}
                onPress={() => void copyReferralCode()}
                style={({ pressed }) => [styles.codeActionPrimary, !referralCode && styles.disabled, pressed && styles.pressed]}
              >
                {copied ? <Check color="#ffffff" size={17} /> : <Copy color="#ffffff" size={17} />}
                <Text style={styles.codeActionPrimaryText}>
                  {copied ? (vi ? "Đã sao chép" : "Copied") : (vi ? "Sao chép mã" : "Copy code")}
                </Text>
              </Pressable>
              <Pressable
                accessibilityLabel={vi ? "Chia sẻ giới thiệu" : "Share referral"}
                accessibilityRole="button"
                onPress={shareReferral}
                style={({ pressed }) => [styles.codeActionSecondary, pressed && styles.pressed]}
              >
                <Share2 color="#147ee5" size={18} />
                <Text style={styles.codeActionSecondaryText}>{vi ? "Chia sẻ ngay" : "Share now"}</Text>
              </Pressable>
            </View>
          </LinearGradient>

          <View style={styles.statsRow}>
            <View style={[styles.statCard, { backgroundColor: sectionCard }]}>
              <Text style={styles.statValue}>{firstPage.stats.f1_count}</Text>
              <Text style={[styles.statLabel, { color: colors.mutedText }]}>{vi ? "Người đã mời" : "People invited"}</Text>
            </View>
            <View style={[styles.statCard, { backgroundColor: sectionCard }]}>
              <Text adjustsFontSizeToFit numberOfLines={1} style={styles.statValue}>{formatMoney(firstPage.stats.total_referral_earned)}</Text>
              <Text style={[styles.statLabel, { color: colors.mutedText }]}>{vi ? "Đã nhận từ giới thiệu" : "Earned from referrals"}</Text>
            </View>
          </View>

          <Pressable
            accessibilityRole="button"
            onPress={showNetwork}
            style={({ pressed }) => [styles.networkCta, pressed && styles.pressed]}
          >
            <Text style={styles.networkCtaText}>{vi ? "Xem danh sách người đã mời" : "View invited people"}</Text>
            <ChevronRight color="#2f9af5" size={19} />
          </Pressable>

          <View style={[styles.howCard, { backgroundColor: sectionCard }]}>
            <Text style={[styles.howTitle, { color: colors.text }]}>{vi ? "Cách hoạt động" : "How it works"}</Text>
            {[
              vi ? "Chia sẻ mã hoặc đường dẫn giới thiệu của bạn qua Zalo, Facebook, Messenger..." : "Share your referral code or link with friends.",
              vi ? "Bạn bè đăng ký Mê Sale và nhập mã của bạn trong thời hạn 3 ngày." : "Friends register for Mê Sale and enter your code within 3 days.",
              vi ? `Đơn đủ điều kiện giúp bạn nhận ${f1Rate}% theo chính sách hiện hành.` : `Eligible orders earn ${f1Rate}% under the current policy.`
            ].map((copy, index) => (
              <View key={copy} style={styles.howStep}>
                <View style={styles.stepNumber}><Text style={styles.stepNumberText}>{index + 1}</Text></View>
                <Text style={[styles.stepText, { color: colors.text }]}>{copy}</Text>
              </View>
            ))}
          </View>

          <View onLayout={(event) => { activityOffset.current = event.nativeEvent.layout.y; }} style={styles.activityHeader}>
            <View style={[styles.activityIcon, { backgroundColor: blueSoft }]}>
              {view === "history" ? <Gift color="#2f9af5" size={20} /> : <UsersRound color="#2f9af5" size={20} />}
            </View>
            <View style={styles.activityCopy}>
              <Text style={[styles.activityTitle, { color: colors.text }]}>{vi ? "Theo dõi giới thiệu" : "Referral activity"}</Text>
              <Text style={[styles.activitySubtitle, { color: colors.mutedText }]}>
                {vi ? "Xem mạng lưới và hoa hồng phát sinh thực tế." : "View your network and actual commission history."}
              </Text>
            </View>
          </View>
          <View style={styles.segmentRow}>
            <ReferralChip label={vi ? "Người đã mời" : "Network"} onPress={() => setView("network")} selected={view === "network"} />
            <ReferralChip label={vi ? "Lịch sử hoa hồng" : "Commission history"} onPress={() => setView("history")} selected={view === "history"} />
          </View>

          {view === "network" ? (
            <View style={styles.filterRow}>
              <ReferralChip label={vi ? "Tất cả" : "All"} onPress={() => setLevel(undefined)} selected={!level} />
              <ReferralChip label="F1" onPress={() => setLevel("1")} selected={level === "1"} />
              {firstPage.rates.f2_enabled ? <ReferralChip label="F2" onPress={() => setLevel("2")} selected={level === "2"} /> : null}
            </View>
          ) : null}

          {view === "history" ? (
            <>
              <View style={styles.filterRow}>
                <ReferralChip label={vi ? "Tất cả cấp" : "All levels"} onPress={() => setLevel(undefined)} selected={!level} />
                <ReferralChip label="F1" onPress={() => setLevel("1")} selected={level === "1"} />
                {firstPage.rates.f2_enabled ? <ReferralChip label="F2" onPress={() => setLevel("2")} selected={level === "2"} /> : null}
              </View>
              <View style={styles.filterRow}>
                <ReferralChip label={vi ? "Mọi trạng thái" : "All statuses"} onPress={() => setStatus(undefined)} selected={!status} />
                <ReferralChip label={vi ? "Đã duyệt" : "Approved"} onPress={() => setStatus("approved")} selected={status === "approved"} />
                <ReferralChip label={vi ? "Đang chờ" : "Pending"} onPress={() => setStatus("pending")} selected={status === "pending"} />
              </View>
            </>
          ) : null}
        </View>
      )}
      onEndReached={() => {
        if (view === "history" && query.hasNextPage && !query.isFetchingNextPage) void query.fetchNextPage();
      }}
      onEndReachedThreshold={0.4}
      refreshControl={<RefreshControl colors={["#2f9af5"]} onRefresh={() => void query.refetch()} refreshing={query.isRefetching} tintColor="#2f9af5" />}
      renderItem={({ item }) => item.kind === "commission" ? (
        <View style={[styles.listCard, { backgroundColor: sectionCard, borderColor: colors.border }]}>
          <View style={styles.rowBetween}>
            <Text style={[styles.memberName, { color: colors.text }]}>{item.value.from_member?.name ?? (vi ? "Thành viên" : "Member")}</Text>
            <StatusBadge status={item.value.status} />
          </View>
          <Text style={styles.commissionAmount}>+{formatMoney(item.value.amount)}</Text>
          <Text style={[styles.listBody, { color: colors.mutedText }]}>F{item.value.level} · {item.value.product_name ?? item.value.order_id ?? (vi ? "Hoa hồng giới thiệu" : "Referral commission")}</Text>
          <Text style={[styles.listDate, { color: colors.mutedText }]}>{formatDate(item.value.created_at)}</Text>
        </View>
      ) : (
        <View style={[styles.listCard, { backgroundColor: sectionCard, borderColor: colors.border }]}>
          <View style={styles.rowBetween}>
            <View style={styles.memberCopy}>
              <Text style={[styles.memberName, { color: colors.text }]}>{item.value.name}</Text>
              <Text style={[styles.listBody, { color: colors.mutedText }]}>{item.value.email ?? "—"}</Text>
            </View>
            <StatusBadge status={`F${item.level}`} />
          </View>
          <View style={styles.rowBetween}>
            <Text style={[styles.listDate, { color: colors.mutedText }]}>{vi ? "Tham gia" : "Joined"}: {formatDate(item.value.joined_at)}</Text>
            <Text style={styles.memberCommission}>{formatMoney(item.value.total_commission)}</Text>
          </View>
        </View>
      )}
      showsVerticalScrollIndicator={false}
      style={{ backgroundColor: screenBackground }}
    />
  );
}

const styles = StyleSheet.create({
  listContent: { gap: 12, paddingHorizontal: 16 },
  headerContent: { gap: 14 },
  screenTitle: { fontSize: 25, fontWeight: "900", letterSpacing: -0.6 },
  policyCard: { alignItems: "center", borderRadius: 17, borderWidth: 1.5, flexDirection: "row", gap: 12, padding: 14 },
  policyIcon: { alignItems: "center", borderRadius: 12, height: 42, justifyContent: "center", width: 42 },
  policyText: { flex: 1, fontSize: 13, lineHeight: 20 },
  policyEmphasis: { color: "#188af3", fontWeight: "900" },
  codeCard: { borderRadius: 22, minHeight: 188, overflow: "hidden", padding: 20 },
  codeDecoration: { backgroundColor: "rgba(255,255,255,0.09)", borderRadius: 130, height: 210, position: "absolute", right: -68, top: -105, width: 210 },
  codeLabel: { color: "rgba(255,255,255,0.82)", fontSize: 13, textAlign: "center" },
  codeRow: { alignItems: "center", flexDirection: "row", gap: 8, justifyContent: "center", marginBottom: 16, marginTop: 8 },
  codeText: { color: "#ffffff", fontSize: 30, fontWeight: "900", letterSpacing: 1.2, maxWidth: "88%" },
  codeActions: { flexDirection: "row", gap: 9 },
  codeActionPrimary: { alignItems: "center", backgroundColor: "rgba(0,83,190,0.55)", borderRadius: 13, flex: 1, flexDirection: "row", gap: 8, justifyContent: "center", minHeight: 48, paddingHorizontal: 10 },
  codeActionPrimaryText: { color: "#ffffff", fontSize: 14, fontWeight: "900" },
  codeActionSecondary: { alignItems: "center", backgroundColor: "#ffffff", borderRadius: 13, flex: 1, flexDirection: "row", gap: 8, justifyContent: "center", minHeight: 48, paddingHorizontal: 10 },
  codeActionSecondaryText: { color: "#147ee5", fontSize: 14, fontWeight: "900" },
  statsRow: { flexDirection: "row", gap: 10 },
  statCard: { alignItems: "center", borderRadius: 17, flex: 1, gap: 6, justifyContent: "center", minHeight: 86, padding: 12 },
  statValue: { color: "#2f9af5", fontSize: 23, fontWeight: "900" },
  statLabel: { fontSize: 12, textAlign: "center" },
  networkCta: { alignItems: "center", flexDirection: "row", gap: 3, justifyContent: "center", minHeight: 32 },
  networkCtaText: { color: "#2f9af5", fontSize: 14, fontWeight: "900" },
  howCard: { borderRadius: 18, gap: 14, padding: 16 },
  howTitle: { fontSize: 16, fontWeight: "900" },
  howStep: { alignItems: "flex-start", flexDirection: "row", gap: 12 },
  stepNumber: { alignItems: "center", backgroundColor: "#2f9af5", borderRadius: 999, height: 25, justifyContent: "center", marginTop: 1, width: 25 },
  stepNumberText: { color: "#ffffff", fontSize: 12, fontWeight: "900" },
  stepText: { flex: 1, fontSize: 13, lineHeight: 20 },
  activityHeader: { alignItems: "center", flexDirection: "row", gap: 11, marginTop: 8 },
  activityIcon: { alignItems: "center", borderRadius: 12, height: 42, justifyContent: "center", width: 42 },
  activityCopy: { flex: 1, gap: 2 },
  activityTitle: { fontSize: 16, fontWeight: "900" },
  activitySubtitle: { fontSize: 12, lineHeight: 17 },
  segmentRow: { flexDirection: "row", flexWrap: "wrap", gap: 8 },
  filterRow: { flexDirection: "row", flexWrap: "wrap", gap: 8 },
  chip: { borderRadius: 999, borderWidth: 1, justifyContent: "center", minHeight: 40, paddingHorizontal: 14 },
  chipText: { fontSize: 13, fontWeight: "800" },
  listCard: { borderRadius: 17, borderWidth: 1, gap: 9, marginTop: 2, padding: 15 },
  rowBetween: { alignItems: "center", flexDirection: "row", gap: 10, justifyContent: "space-between" },
  memberCopy: { flex: 1, gap: 3 },
  memberName: { flex: 1, fontSize: 15, fontWeight: "900" },
  listBody: { fontSize: 13, lineHeight: 19 },
  listDate: { fontSize: 12 },
  commissionAmount: { color: "#2f9af5", fontSize: 19, fontWeight: "900" },
  memberCommission: { color: "#2f9af5", fontSize: 14, fontWeight: "900" },
  empty: { minHeight: 220 },
  disabled: { opacity: 0.45 },
  pressed: { opacity: 0.72 }
});
