import { useState } from "react";
import { useInfiniteQuery } from "@tanstack/react-query";
import { FlatList, StyleSheet, Text, View } from "react-native";
import { useSafeAreaInsets } from "react-native-safe-area-context";
import { ApiError } from "@/api/client";
import { EmptyState, ErrorState, LoadingState, OfflineState } from "@/components/AsyncState";
import { IosPayoutRouteGuard } from "@/components/IosPayoutRouteGuard";
import { fetchGiftRedemptions } from "@/features/earn/api";
import { ActionButton, Card, Field, FilterChip, ListFooterLoading, ScreenHeader, StatusBadge, earnStyles, formatDate, formatMoney } from "@/features/earn/ui";
import { getDeviceLocale } from "@/i18n";
import { colors, spacing } from "@/theme/tokens";

export default function GiftHistoryScreen() {
  return <IosPayoutRouteGuard><GiftHistoryContent /></IosPayoutRouteGuard>;
}

function GiftHistoryContent() {
  const insets = useSafeAreaInsets();
  const vi = getDeviceLocale() === "vi";
  const [searchDraft, setSearchDraft] = useState("");
  const [search, setSearch] = useState("");
  const [status, setStatus] = useState("all");
  const query = useInfiniteQuery({
    queryKey: ["earn", "gift-redemptions", search, status],
    initialPageParam: 1,
    queryFn: ({ pageParam, signal }) => fetchGiftRedemptions(
      pageParam,
      search || undefined,
      status === "all" ? undefined : status,
      signal
    ),
    getNextPageParam: (page) => page.pagination.current_page < page.pagination.last_page
      ? page.pagination.current_page + 1
      : undefined
  });

  if (query.isPending) return <LoadingState label={vi ? "Đang tải lịch sử đổi quà..." : "Loading gift history..."} />;
  if (query.isError) {
    const props = {
      actionLabel: vi ? "Thử lại" : "Retry",
      message: query.error instanceof Error ? query.error.message : undefined,
      onAction: () => void query.refetch(),
      title: vi ? "Không thể tải lịch sử quà" : "Unable to load gift history"
    };
    return query.error instanceof ApiError && query.error.isNetworkError ? <OfflineState {...props} /> : <ErrorState {...props} />;
  }

  const redemptions = query.data.pages.flatMap((page) => page.items);

  return (
    <FlatList
      contentContainerStyle={[earnStyles.listContent, { paddingBottom: insets.bottom + spacing.lg }]}
      contentInsetAdjustmentBehavior="automatic"
      data={redemptions}
      keyExtractor={(item) => String(item.id)}
      keyboardShouldPersistTaps="handled"
      ListEmptyComponent={<EmptyState message={vi ? "Các lần đổi quà sẽ xuất hiện tại đây." : "Your gift redemptions will appear here."} style={styles.empty} title={vi ? "Chưa có lịch sử" : "No redemption history"} />}
      ListFooterComponent={<ListFooterLoading visible={query.isFetchingNextPage} />}
      ListHeaderComponent={
        <View style={styles.headerContent}>
          <ScreenHeader
            eyebrow={vi ? "Theo dõi xử lý" : "Track fulfillment"}
            subtitle={vi ? "Trạng thái được cập nhật trực tiếp từ hệ thống Mesale." : "Statuses are loaded directly from Mesale."}
            title={vi ? "Lịch sử đổi quà" : "Gift history"}
          />
          <Card>
            <Field
              autoCapitalize="characters"
              label={vi ? "Tìm theo mã hoặc tên quà" : "Search by code or gift name"}
              onChangeText={setSearchDraft}
              onSubmitEditing={() => setSearch(searchDraft.trim())}
              returnKeyType="search"
              value={searchDraft}
            />
            <ActionButton label={vi ? "Tìm kiếm" : "Search"} onPress={() => setSearch(searchDraft.trim())} />
          </Card>
          <View style={earnStyles.chips}>
            {([
              ["all", vi ? "Tất cả" : "All"],
              ["pending", vi ? "Đang chờ" : "Pending"],
              ["approved", vi ? "Đã duyệt" : "Approved"],
              ["rejected", vi ? "Từ chối" : "Rejected"]
            ] as Array<[string, string]>).map(([value, label]) => (
              <FilterChip key={value} label={label} onPress={() => setStatus(value)} selected={status === value} />
            ))}
          </View>
          <Text style={earnStyles.sectionTitle}>{vi ? "Yêu cầu đã gửi" : "Submitted requests"}</Text>
        </View>
      }
      onEndReached={() => {
        if (query.hasNextPage && !query.isFetchingNextPage) void query.fetchNextPage();
      }}
      onEndReachedThreshold={0.4}
      onRefresh={() => void query.refetch()}
      refreshing={query.isRefetching}
      renderItem={({ item }) => (
        <Card>
          <View style={earnStyles.rowBetween}>
            <View style={styles.flex}>
              <Text style={styles.code}>#{item.code}</Text>
              <Text style={styles.title}>{item.gift_title ?? (vi ? "Phần quà" : "Gift")}</Text>
            </View>
            <StatusBadge status={item.status} />
          </View>
          <View style={earnStyles.rowBetween}>
            <Text style={earnStyles.amount}>{formatMoney(item.amount)}</Text>
            <Text style={styles.date}>{formatDate(item.created_at)}</Text>
          </View>
          {item.processed_at ? <Text style={earnStyles.body}>{vi ? "Xử lý" : "Processed"}: {formatDate(item.processed_at)}</Text> : null}
        </Card>
      )}
      style={earnStyles.screen}
    />
  );
}

const styles = StyleSheet.create({
  headerContent: { gap: spacing.md },
  flex: { flex: 1 },
  code: { color: colors.primary, fontSize: 12, fontWeight: "900", letterSpacing: 0.7 },
  title: { color: colors.text, fontSize: 16, fontWeight: "900", marginTop: spacing.xs },
  date: { color: colors.mutedText, fontSize: 12 },
  empty: { minHeight: 240 }
});
