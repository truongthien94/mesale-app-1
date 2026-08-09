import { useCallback } from "react";
import { useRouter } from "expo-router";
import { FlatList, StyleSheet, Text, View, type ListRenderItemInfo } from "react-native";
import { useSafeAreaInsets } from "react-native-safe-area-context";
import { EmptyState, LoadingState } from "@/components/AsyncState";
import { ListFooter, PageFrame, PrimaryButton, QueryFailure, StatusBadge } from "@/features/wallet/components";
import { formatDate, formatVnd } from "@/features/wallet/format";
import { useAppConfig, useWithdrawals } from "@/features/wallet/api";
import type { Withdrawal } from "@/features/wallet/types";
import { colors, spacing, theme } from "@/theme/tokens";

export default function WithdrawalsScreen() {
  const router = useRouter();
  const insets = useSafeAreaInsets();
  const query = useWithdrawals();
  const config = useAppConfig();
  const withdrawals = query.data?.pages.flatMap((page) => page.items) ?? [];

  const renderItem = useCallback(({ item }: ListRenderItemInfo<Withdrawal>) => (
    <View style={styles.card}>
      <View style={styles.topRow}>
        <View style={styles.accountCopy}>
          <Text numberOfLines={1} style={styles.bank}>{item.bank_name || "Ví điện tử"}</Text>
          <Text style={styles.accountNumber}>STK: {item.account_number}</Text>
          <Text numberOfLines={1} style={styles.accountName}>{item.account_name}</Text>
        </View>
        <View style={styles.amountCopy}>
          <Text style={styles.amount}>-{formatVnd(item.amount)}</Text>
          <Text style={styles.received}>Thực nhận: <Text style={styles.receivedValue}>{formatVnd(item.real_amount)}</Text></Text>
        </View>
      </View>
      <View style={styles.bottomRow}>
        <View style={styles.dateCopy}>
          <Text style={styles.code}>#{item.code}</Text>
          <Text style={styles.date}>{formatDate(item.created_at)}</Text>
        </View>
        <View style={styles.statusCopy}>
          <StatusBadge status={item.status} />
          {item.notes ? <Text numberOfLines={2} style={styles.notes}>Lý do: {item.notes}</Text> : null}
        </View>
      </View>
    </View>
  ), []);

  if (query.isPending || config.isPending) return <LoadingState label="Đang tải lịch sử rút tiền..." />;
  if (query.isError && withdrawals.length === 0) return <QueryFailure error={query.error} onRetry={() => void query.refetch()} />;
  if (config.isError) return <QueryFailure error={config.error} onRetry={() => void config.refetch()} />;

  return (
    <PageFrame>
      <FlatList
        contentContainerStyle={[styles.content, { paddingBottom: spacing.xl + insets.bottom }, withdrawals.length === 0 && styles.emptyContent]}
        contentInsetAdjustmentBehavior="automatic"
        data={withdrawals}
        keyExtractor={(item) => String(item.id)}
        ListHeaderComponent={
          <View style={styles.headerAction}>
            <PrimaryButton
              disabled={!config.data.withdraw.enabled}
              label={config.data.withdraw.enabled ? "Tạo yêu cầu rút tiền" : "Rút tiền đang tạm tắt"}
              onPress={() => router.push("/(tabs)/wallet/withdrawals/create")}
            />
          </View>
        }
        ListEmptyComponent={<EmptyState title="Chưa có lệnh rút tiền" message="Yêu cầu mới và trạng thái xử lý sẽ xuất hiện tại đây." />}
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
  headerAction: {
    marginBottom: spacing.sm
  },
  card: {
    backgroundColor: colors.surface,
    borderColor: colors.border,
    borderRadius: theme.radius.lg,
    borderWidth: 1,
    gap: spacing.md,
    padding: spacing.md
  },
  topRow: {
    alignItems: "flex-start",
    flexDirection: "row",
    gap: spacing.md,
    justifyContent: "space-between"
  },
  accountCopy: {
    flex: 1,
    gap: spacing.xs,
    minWidth: 0
  },
  bank: {
    color: colors.text,
    fontSize: 13,
    fontWeight: "800"
  },
  accountNumber: {
    color: "#475569",
    fontSize: 11,
    fontWeight: "700"
  },
  accountName: {
    color: colors.mutedText,
    fontSize: 10,
    textTransform: "uppercase"
  },
  amountCopy: {
    alignItems: "flex-end",
    gap: spacing.xs
  },
  amount: {
    color: colors.text,
    fontSize: 14,
    fontWeight: "900"
  },
  received: {
    color: colors.mutedText,
    fontSize: 9
  },
  receivedValue: {
    color: colors.primary,
    fontWeight: "800"
  },
  bottomRow: {
    alignItems: "flex-start",
    borderTopColor: colors.border,
    borderTopWidth: StyleSheet.hairlineWidth,
    flexDirection: "row",
    justifyContent: "space-between",
    paddingTop: spacing.sm
  },
  dateCopy: {
    gap: spacing.xs
  },
  code: {
    color: "#475569",
    fontSize: 10,
    fontWeight: "800"
  },
  date: {
    color: colors.mutedText,
    fontSize: 9
  },
  statusCopy: {
    alignItems: "flex-end",
    gap: spacing.xs,
    maxWidth: "52%"
  },
  notes: {
    color: colors.danger,
    fontSize: 9,
    textAlign: "right"
  }
});
