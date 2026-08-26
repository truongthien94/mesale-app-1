import { useCallback } from "react";
import { FlatList, StyleSheet, Text, View, type ListRenderItemInfo } from "react-native";
import { useSafeAreaInsets } from "react-native-safe-area-context";
import { EmptyState, LoadingState } from "@/components/AsyncState";
import { IosPayoutRouteGuard } from "@/components/IosPayoutRouteGuard";
import { ListFooter, PageFrame, QueryFailure } from "@/features/wallet/components";
import { balanceTypeLabel, formatDate, formatVnd } from "@/features/wallet/format";
import { useBalanceLogs } from "@/features/wallet/api";
import type { BalanceLog } from "@/features/wallet/types";
import { colors, spacing, theme } from "@/theme/tokens";

export default function BalanceLogsScreen() {
  return <IosPayoutRouteGuard><BalanceLogsContent /></IosPayoutRouteGuard>;
}

function BalanceLogsContent() {
  const insets = useSafeAreaInsets();
  const query = useBalanceLogs();
  const logs = query.data?.pages.flatMap((page) => page.items) ?? [];

  const renderItem = useCallback(({ item }: ListRenderItemInfo<BalanceLog>) => (
    <View style={styles.card}>
      <View style={styles.header}>
        <View style={styles.typeBadge}><Text style={styles.typeText}>{balanceTypeLabel(item.type)}</Text></View>
        <Text style={styles.date}>{formatDate(item.created_at)}</Text>
      </View>
      <Text style={styles.description}>{item.description}</Text>
      <View style={styles.footer}>
        <View style={styles.amounts}>
          <Text style={styles.balanceLabel}>Trước: <Text style={styles.balanceValue}>{formatVnd(item.amount_before)}</Text></Text>
          <Text style={styles.balanceLabel}>Sau: <Text style={styles.balanceValueStrong}>{formatVnd(item.amount_after)}</Text></Text>
        </View>
        <Text style={[styles.change, item.is_credit ? styles.credit : styles.debit]}>
          {formatVnd(item.amount_change, true)}
        </Text>
      </View>
    </View>
  ), []);

  if (query.isPending) return <LoadingState label="Đang tải biến động số dư..." />;
  if (query.isError && logs.length === 0) return <QueryFailure error={query.error} onRetry={() => void query.refetch()} />;

  return (
    <PageFrame>
      <FlatList
        contentContainerStyle={[styles.content, { paddingBottom: spacing.xl + insets.bottom }, logs.length === 0 && styles.emptyContent]}
        contentInsetAdjustmentBehavior="automatic"
        data={logs}
        keyExtractor={(item) => String(item.id)}
        ListEmptyComponent={<EmptyState title="Chưa có biến động số dư" message="Các khoản hoàn tiền, thưởng và rút tiền sẽ được ghi nhận tại đây." />}
        ListFooterComponent={
          <ListFooter
            error={query.isFetchNextPageError ? query.error : null}
            loading={query.isFetchingNextPage}
            onRetry={() => void query.fetchNextPage()}
          />
        }
        onEndReached={() => {
          if (query.hasNextPage && !query.isFetchingNextPage) void query.fetchNextPage();
        }}
        onEndReachedThreshold={0.4}
        onRefresh={() => void query.refetch()}
        refreshing={query.isRefetching && !query.isFetchingNextPage}
        renderItem={renderItem}
      />
    </PageFrame>
  );
}

const styles = StyleSheet.create({
  content: {
    gap: spacing.md,
    padding: spacing.md
  },
  emptyContent: {
    flexGrow: 1
  },
  card: {
    backgroundColor: colors.surface,
    borderColor: colors.border,
    borderRadius: theme.radius.lg,
    borderWidth: 1,
    gap: spacing.md,
    padding: spacing.md
  },
  header: {
    alignItems: "center",
    borderBottomColor: colors.border,
    borderBottomWidth: StyleSheet.hairlineWidth,
    flexDirection: "row",
    justifyContent: "space-between",
    paddingBottom: spacing.sm
  },
  typeBadge: {
    backgroundColor: "#fff7ed",
    borderColor: "#fed7aa",
    borderRadius: 999,
    borderWidth: 1,
    paddingHorizontal: 9,
    paddingVertical: 4
  },
  typeText: {
    color: colors.primary,
    fontSize: 10,
    fontWeight: "800"
  },
  date: {
    color: colors.mutedText,
    fontSize: 10
  },
  description: {
    color: colors.text,
    fontSize: 13,
    fontWeight: "800",
    lineHeight: 19
  },
  footer: {
    alignItems: "flex-end",
    borderTopColor: colors.border,
    borderTopWidth: StyleSheet.hairlineWidth,
    flexDirection: "row",
    justifyContent: "space-between",
    paddingTop: spacing.sm
  },
  amounts: {
    gap: spacing.xs
  },
  balanceLabel: {
    color: colors.mutedText,
    fontSize: 10
  },
  balanceValue: {
    color: "#475569",
    fontWeight: "600"
  },
  balanceValueStrong: {
    color: colors.text,
    fontWeight: "800"
  },
  change: {
    fontSize: 15,
    fontWeight: "900"
  },
  credit: {
    color: "#059669"
  },
  debit: {
    color: colors.danger
  }
});
