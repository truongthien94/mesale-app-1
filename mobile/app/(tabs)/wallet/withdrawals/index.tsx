import { useCallback } from "react";
import { useRouter } from "expo-router";
import { FlatList, StyleSheet, Text, View, type ListRenderItemInfo } from "react-native";
import { useSafeAreaInsets } from "react-native-safe-area-context";
import { LoadingState } from "@/components/AsyncState";
import { IosPayoutRouteGuard } from "@/components/IosPayoutRouteGuard";
import { ListFooter, PageFrame, PrimaryButton, QueryFailure, StatusBadge } from "@/features/wallet/components";
import { formatDate, formatVnd } from "@/features/wallet/format";
import { useAppConfig, useWithdrawals } from "@/features/wallet/api";
import type { Withdrawal } from "@/features/wallet/types";
import { useTheme } from "@/theme/ThemeProvider";
import { spacing as spacingTokens } from "@/theme/tokens";

export default function WithdrawalsScreen() {
  return <IosPayoutRouteGuard><WithdrawalsContent /></IosPayoutRouteGuard>;
}

function WithdrawalsContent() {
  const router = useRouter();
  const insets = useSafeAreaInsets();
  const { colors, spacing, radius } = useTheme();
  const query = useWithdrawals();
  const config = useAppConfig();
  const withdrawals = query.data?.pages.flatMap((page) => page.items) ?? [];

  const renderItem = useCallback(({ item }: ListRenderItemInfo<Withdrawal>) => (
    <View style={[styles.card, { backgroundColor: colors.surface, borderColor: colors.border, borderRadius: radius.lg }]}>
      <View style={styles.topRow}>
        <View style={styles.accountCopy}>
          <Text numberOfLines={1} style={[styles.bank, { color: colors.text }]}>{item.bank_name || "Ví điện tử"}</Text>
          <Text style={[styles.accountNumber, { color: colors.mutedText }]}>STK: {item.account_number}</Text>
          <Text numberOfLines={1} style={[styles.accountName, { color: colors.mutedText }]}>{item.account_name}</Text>
        </View>
        <View style={styles.amountCopy}>
          <Text style={[styles.amount, { color: colors.text }]}>-{formatVnd(item.amount)}</Text>
          <Text style={[styles.received, { color: colors.mutedText }]}>Thực nhận: <Text style={[styles.receivedValue, { color: colors.primary }]}>{formatVnd(item.real_amount)}</Text></Text>
        </View>
      </View>
      <View style={[styles.bottomRow, { borderTopColor: colors.border }]}>
        <View style={styles.dateCopy}>
          <Text style={[styles.code, { color: colors.mutedText }]}>#{item.code}</Text>
          <Text style={[styles.date, { color: colors.mutedText }]}>{formatDate(item.created_at)}</Text>
        </View>
        <View style={styles.statusCopy}>
          <StatusBadge status={item.status} />
          {item.notes ? <Text numberOfLines={2} style={[styles.notes, { color: colors.danger }]}>Lý do: {item.notes}</Text> : null}
        </View>
      </View>
    </View>
  ), [colors, radius.lg]);

  if (query.isPending || config.isPending) return <LoadingState label="Đang tải lịch sử rút tiền..." />;
  if (query.isError && withdrawals.length === 0) return <QueryFailure error={query.error} onRetry={() => void query.refetch()} />;
  if (config.isError) return <QueryFailure error={config.error} onRetry={() => void config.refetch()} />;

  return (
    <PageFrame style={{ backgroundColor: colors.background }}>
      <FlatList
        contentContainerStyle={[
          styles.content,
          { paddingBottom: spacing.xl + insets.bottom },
          withdrawals.length === 0 && styles.emptyContent
        ]}
        contentInsetAdjustmentBehavior="automatic"
        data={withdrawals}
        keyExtractor={(item) => String(item.id)}
        style={{ backgroundColor: colors.background }}
        ListHeaderComponent={
          <View style={styles.headerAction}>
            <PrimaryButton
              disabled={!config.data.withdraw.enabled}
              label={config.data.withdraw.enabled ? "Tạo yêu cầu rút tiền" : "Rút tiền đang tạm tắt"}
              onPress={() => router.push("/(tabs)/withdraw")}
            />
          </View>
        }
        ListEmptyComponent={(
          <View style={styles.emptyState}>
            <Text style={[styles.emptyTitle, { color: colors.text }]}>Chưa có lệnh rút tiền</Text>
            <Text style={[styles.emptyMessage, { color: colors.mutedText }]}>Yêu cầu mới và trạng thái xử lý sẽ xuất hiện tại đây.</Text>
          </View>
        )}
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
    gap: spacingTokens.md,
    padding: spacingTokens.md
  },
  emptyContent: {
    flexGrow: 1
  },
  headerAction: {
    marginBottom: spacingTokens.sm
  },
  emptyState: {
    alignItems: "center",
    flex: 1,
    justifyContent: "center",
    minHeight: 280,
    paddingBottom: spacingTokens.xl,
    paddingHorizontal: spacingTokens.lg
  },
  emptyTitle: {
    fontSize: 18,
    fontWeight: "900",
    textAlign: "center"
  },
  emptyMessage: {
    fontSize: 13,
    lineHeight: 20,
    marginTop: spacingTokens.sm,
    maxWidth: 285,
    textAlign: "center"
  },
  card: {
    borderWidth: 1,
    gap: spacingTokens.md,
    padding: spacingTokens.md
  },
  topRow: {
    alignItems: "flex-start",
    flexDirection: "row",
    gap: spacingTokens.md,
    justifyContent: "space-between"
  },
  accountCopy: {
    flex: 1,
    gap: spacingTokens.xs,
    minWidth: 0
  },
  bank: {
    fontSize: 13,
    fontWeight: "800"
  },
  accountNumber: {
    fontSize: 11,
    fontWeight: "700"
  },
  accountName: {
    fontSize: 10,
    textTransform: "uppercase"
  },
  amountCopy: {
    alignItems: "flex-end",
    gap: spacingTokens.xs
  },
  amount: {
    fontSize: 14,
    fontWeight: "900"
  },
  received: {
    fontSize: 9
  },
  receivedValue: {
    fontWeight: "800"
  },
  bottomRow: {
    alignItems: "flex-start",
    borderTopWidth: StyleSheet.hairlineWidth,
    flexDirection: "row",
    justifyContent: "space-between",
    paddingTop: spacingTokens.sm
  },
  dateCopy: {
    gap: spacingTokens.xs
  },
  code: {
    fontSize: 10,
    fontWeight: "800"
  },
  date: {
    fontSize: 9
  },
  statusCopy: {
    alignItems: "flex-end",
    gap: spacingTokens.xs,
    maxWidth: "52%"
  },
  notes: {
    fontSize: 9,
    textAlign: "right"
  }
});
