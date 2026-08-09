import { useState } from "react";
import { useInfiniteQuery } from "@tanstack/react-query";
import { FlatList, Share, StyleSheet, Text, View } from "react-native";
import { useSafeAreaInsets } from "react-native-safe-area-context";
import { ApiError } from "@/api/client";
import { EmptyState, ErrorState, LoadingState, OfflineState } from "@/components/AsyncState";
import { fetchReferrals, type ReferralCommission, type ReferralMember } from "@/features/earn/api";
import { ActionButton, Card, FilterChip, ListFooterLoading, ScreenHeader, StatusBadge, earnStyles, formatDate, formatMoney } from "@/features/earn/ui";
import { getDeviceLocale } from "@/i18n";
import { colors, spacing } from "@/theme/tokens";

type ReferralListItem =
  | { key: string; kind: "commission"; value: ReferralCommission }
  | { key: string; kind: "member"; level: 1 | 2; value: ReferralMember };

export default function ReferralsScreen() {
  const insets = useSafeAreaInsets();
  const vi = getDeviceLocale() === "vi";
  const [view, setView] = useState<"history" | "network">("history");
  const [level, setLevel] = useState<"1" | "2" | undefined>();
  const [status, setStatus] = useState<"pending" | "approved" | undefined>();
  const query = useInfiniteQuery({
    queryKey: ["earn", "referrals", level ?? "all", status ?? "all"],
    initialPageParam: 1,
    queryFn: ({ pageParam, signal }) => fetchReferrals(pageParam, { level, status }, signal),
    getNextPageParam: (page) => page.commissions.pagination.current_page < page.commissions.pagination.last_page
      ? page.commissions.pagination.current_page + 1
      : undefined
  });

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
  const listItems: ReferralListItem[] = view === "history"
    ? commissions.map((value) => ({ key: `commission-${value.id}`, kind: "commission", value }))
    : [
        ...firstPage.f1_members.map((value) => ({ key: `f1-${value.id}`, kind: "member" as const, level: 1 as const, value })),
        ...firstPage.f2_members.map((value) => ({ key: `f2-${value.id}`, kind: "member" as const, level: 2 as const, value }))
      ];

  const shareReferral = () => {
    void Share.share({ message: vi ? `Tham gia Mesale cùng tôi: ${firstPage.referral_link}` : `Join me on Mesale: ${firstPage.referral_link}` });
  };

  return (
    <FlatList
      contentContainerStyle={[earnStyles.listContent, { paddingBottom: insets.bottom + spacing.lg }]}
      contentInsetAdjustmentBehavior="automatic"
      data={listItems}
      keyExtractor={(item) => item.key}
      ListEmptyComponent={
        <EmptyState
          message={view === "history"
            ? (vi ? "Chưa có hoa hồng phù hợp bộ lọc." : "No commissions match these filters.")
            : (vi ? "Chưa có thành viên trong mạng lưới." : "Your referral network is empty.")}
          style={styles.empty}
          title={vi ? "Chưa có dữ liệu" : "Nothing here yet"}
        />
      }
      ListFooterComponent={<ListFooterLoading visible={query.isFetchingNextPage} />}
      ListHeaderComponent={
        <View style={styles.headerContent}>
          <ScreenHeader
            eyebrow="F1 / F2"
            subtitle={vi ? "Chia sẻ đúng liên kết của bạn và theo dõi hoa hồng thực tế." : "Share your unique link and track real commission activity."}
            title={vi ? "Giới thiệu bạn bè" : "Referrals"}
          />

          <Card accent>
            <View style={earnStyles.rowBetween}>
              <View style={styles.flex}>
                <Text style={styles.label}>{vi ? "MÃ GIỚI THIỆU" : "REFERRAL CODE"}</Text>
                <Text selectable style={styles.code}>{firstPage.referral_code ?? "—"}</Text>
              </View>
              <ActionButton label={vi ? "Chia sẻ" : "Share"} onPress={shareReferral} />
            </View>
            <Text selectable style={styles.link}>{firstPage.referral_link}</Text>
            <Text style={earnStyles.body}>
              F1 {firstPage.rates.f1_rate}%{firstPage.rates.f2_enabled ? ` · F2 ${firstPage.rates.f2_rate}%` : ""}
            </Text>
          </Card>

          <View style={styles.statsGrid}>
            {[
              [vi ? "Thành viên F1" : "F1 members", String(firstPage.stats.f1_count)],
              [vi ? "Thành viên F2" : "F2 members", String(firstPage.stats.f2_count)],
              [vi ? "Đã duyệt" : "Approved", formatMoney(firstPage.stats.total_commission)],
              [vi ? "Đang chờ" : "Pending", formatMoney(firstPage.stats.pending_commission)]
            ].map(([labelText, value]) => (
              <View key={labelText} style={styles.statCard}>
                <Text style={styles.statValue}>{value}</Text>
                <Text style={styles.statLabel}>{labelText}</Text>
              </View>
            ))}
          </View>

          <View style={earnStyles.chips}>
            <FilterChip label={vi ? "Hoa hồng" : "Commissions"} onPress={() => setView("history")} selected={view === "history"} />
            <FilterChip label={vi ? "Mạng lưới" : "Network"} onPress={() => setView("network")} selected={view === "network"} />
          </View>

          {view === "history" ? (
            <>
              <View style={earnStyles.chips}>
                <FilterChip label={vi ? "Tất cả cấp" : "All levels"} onPress={() => setLevel(undefined)} selected={!level} />
                <FilterChip label="F1" onPress={() => setLevel("1")} selected={level === "1"} />
                <FilterChip label="F2" onPress={() => setLevel("2")} selected={level === "2"} />
              </View>
              <View style={earnStyles.chips}>
                <FilterChip label={vi ? "Mọi trạng thái" : "All statuses"} onPress={() => setStatus(undefined)} selected={!status} />
                <FilterChip label={vi ? "Đã duyệt" : "Approved"} onPress={() => setStatus("approved")} selected={status === "approved"} />
                <FilterChip label={vi ? "Đang chờ" : "Pending"} onPress={() => setStatus("pending")} selected={status === "pending"} />
              </View>
            </>
          ) : null}

          <Text style={earnStyles.sectionTitle}>{view === "history" ? (vi ? "Lịch sử hoa hồng" : "Commission history") : (vi ? "Thành viên F1/F2" : "F1/F2 members")}</Text>
        </View>
      }
      onEndReached={() => {
        if (view === "history" && query.hasNextPage && !query.isFetchingNextPage) void query.fetchNextPage();
      }}
      onEndReachedThreshold={0.4}
      renderItem={({ item }) => item.kind === "commission" ? (
        <Card>
          <View style={earnStyles.rowBetween}>
            <Text style={earnStyles.strong}>{item.value.from_member?.name ?? (vi ? "Thành viên" : "Member")}</Text>
            <StatusBadge status={item.value.status} />
          </View>
          <Text style={earnStyles.amount}>+{formatMoney(item.value.amount)}</Text>
          <Text style={earnStyles.body}>F{item.value.level} · {item.value.product_name ?? item.value.order_id ?? (vi ? "Hoa hồng giới thiệu" : "Referral commission")}</Text>
          <Text style={styles.date}>{formatDate(item.value.created_at)}</Text>
        </Card>
      ) : (
        <Card>
          <View style={earnStyles.rowBetween}>
            <View style={styles.flex}>
              <Text style={earnStyles.strong}>{item.value.name}</Text>
              <Text style={earnStyles.body}>{item.value.email ?? "—"}</Text>
            </View>
            <StatusBadge status={`F${item.level}`} />
          </View>
          <View style={earnStyles.rowBetween}>
            <Text style={styles.date}>{vi ? "Tham gia" : "Joined"}: {formatDate(item.value.joined_at)}</Text>
            <Text style={styles.commission}>{formatMoney(item.value.total_commission)}</Text>
          </View>
        </Card>
      )}
      style={earnStyles.screen}
    />
  );
}

const styles = StyleSheet.create({
  headerContent: { gap: spacing.md },
  flex: { flex: 1 },
  label: { color: colors.mutedText, fontSize: 11, fontWeight: "800", letterSpacing: 1 },
  code: { color: colors.text, fontSize: 24, fontWeight: "900", marginTop: spacing.xs },
  link: { backgroundColor: "#f8fafc", borderRadius: 10, color: colors.mutedText, fontSize: 13, lineHeight: 19, padding: spacing.sm },
  statsGrid: { flexDirection: "row", flexWrap: "wrap", gap: spacing.sm },
  statCard: { backgroundColor: colors.surface, borderColor: colors.border, borderRadius: 16, borderWidth: 1, gap: spacing.xs, minWidth: "47%", padding: spacing.md },
  statValue: { color: colors.text, fontSize: 17, fontWeight: "900" },
  statLabel: { color: colors.mutedText, fontSize: 12, fontWeight: "700" },
  date: { color: colors.mutedText, fontSize: 12 },
  commission: { color: colors.primary, fontSize: 14, fontWeight: "900" },
  empty: { minHeight: 240 }
});
