import { useRouter } from "expo-router";
import { Pressable, ScrollView, StyleSheet, Text, View } from "react-native";
import { useSafeAreaInsets } from "react-native-safe-area-context";
import { LoadingState } from "@/components/AsyncState";
import { Card, PageFrame, QueryFailure, SectionTitle } from "@/features/wallet/components";
import { formatVnd } from "@/features/wallet/format";
import { useAccountSummary, useAppConfig } from "@/features/wallet/api";
import { colors, spacing, theme } from "@/theme/tokens";

export default function WalletRoute() {
  const router = useRouter();
  const insets = useSafeAreaInsets();
  const account = useAccountSummary();
  const config = useAppConfig();

  if (account.isPending || config.isPending) return <LoadingState label="Đang tải ví..." />;
  if (account.isError) return <QueryFailure error={account.error} onRetry={() => void account.refetch()} />;
  if (config.isError) return <QueryFailure error={config.error} onRetry={() => void config.refetch()} />;

  const wallet = account.data.wallet;
  const stats = account.data.stats;
  const features = config.data.features;

  return (
    <PageFrame>
      <ScrollView
        contentContainerStyle={[styles.content, { paddingBottom: spacing.xl + insets.bottom }]}
        contentInsetAdjustmentBehavior="automatic"
      >
        <View style={styles.balanceCard}>
          <Text style={styles.balanceEyebrow}>SỐ DƯ KHẢ DỤNG</Text>
          <Text accessibilityLabel={`Số dư ${formatVnd(wallet.balance)}`} style={styles.balanceValue}>
            {formatVnd(wallet.balance)}
          </Text>
          <View style={styles.balanceDivider} />
          <View style={styles.balanceStats}>
            <BalanceStat label="Đã tích lũy" value={formatVnd(wallet.total_cashback)} tone="#4ade80" />
            <BalanceStat label="Đã giải ngân" value={formatVnd(wallet.total_withdrawn)} tone="#60a5fa" />
          </View>
        </View>

        <SectionTitle title="Quản lý ví" caption="Mọi số dư và giao dịch được xác nhận trực tiếp bởi mesale.vn." />
        <View style={styles.grid}>
          <WalletAction
            label="Đơn hoàn tiền"
            meta={`${stats.orders_total} đơn`}
            onPress={() => router.push("/(tabs)/wallet/orders")}
            disabled={!features.api_orders}
          />
          <WalletAction
            label="Biến động số dư"
            meta="Lịch sử vào / ra"
            onPress={() => router.push("/(tabs)/wallet/balance-logs")}
            disabled={!features.api_balance_logs}
          />
          <WalletAction
            label="Rút tiền"
            meta={`${stats.withdrawals_pending} đang chờ`}
            onPress={() => router.push("/(tabs)/wallet/withdrawals")}
            disabled={!features.api_withdraw}
          />
          <WalletAction
            label="Tài khoản nhận"
            meta="Ngân hàng và ví"
            onPress={() => router.push("/(tabs)/wallet/payment-accounts")}
            disabled={!features.api_withdraw}
          />
        </View>

        <Card>
          <SectionTitle title="Tình trạng đơn" />
          <View style={styles.orderStats}>
            <OrderStat label="Chờ duyệt" value={stats.orders_pending} color="#d97706" />
            <OrderStat label="Thành công" value={stats.orders_approved} color="#059669" />
            <OrderStat label="Từ chối" value={stats.orders_rejected} color="#e11d48" />
          </View>
        </Card>
      </ScrollView>
    </PageFrame>
  );
}

function BalanceStat({ label, value, tone }: { label: string; value: string; tone: string }) {
  return (
    <View style={styles.balanceStat}>
      <Text style={styles.balanceStatLabel}>{label}</Text>
      <Text style={[styles.balanceStatValue, { color: tone }]}>{value}</Text>
    </View>
  );
}

function WalletAction({ label, meta, onPress, disabled }: { label: string; meta: string; onPress: () => void; disabled: boolean }) {
  return (
    <Pressable
      accessibilityRole="button"
      accessibilityState={{ disabled }}
      disabled={disabled}
      onPress={onPress}
      style={({ pressed }) => [styles.action, disabled && styles.actionDisabled, pressed && styles.actionPressed]}
    >
      <View style={styles.actionMark} />
      <Text style={styles.actionLabel}>{label}</Text>
      <Text style={styles.actionMeta}>{disabled ? "Đang tạm tắt" : meta}</Text>
    </Pressable>
  );
}

function OrderStat({ label, value, color }: { label: string; value: number; color: string }) {
  return (
    <View style={styles.orderStat}>
      <Text style={[styles.orderStatValue, { color }]}>{value}</Text>
      <Text style={styles.orderStatLabel}>{label}</Text>
    </View>
  );
}

const styles = StyleSheet.create({
  content: {
    gap: spacing.lg,
    padding: spacing.md
  },
  balanceCard: {
    backgroundColor: "#0f172a",
    borderRadius: 24,
    overflow: "hidden",
    padding: spacing.lg,
    shadowColor: "#0f172a",
    shadowOffset: { width: 0, height: 10 },
    shadowOpacity: 0.2,
    shadowRadius: 16,
    elevation: 6
  },
  balanceEyebrow: {
    color: "#94a3b8",
    fontSize: 10,
    fontWeight: "800",
    letterSpacing: 1.2
  },
  balanceValue: {
    color: colors.surface,
    fontSize: 32,
    fontWeight: "900",
    marginTop: spacing.sm
  },
  balanceDivider: {
    backgroundColor: "#334155",
    height: 1,
    marginVertical: spacing.lg
  },
  balanceStats: {
    flexDirection: "row",
    gap: spacing.md
  },
  balanceStat: {
    flex: 1,
    gap: spacing.xs
  },
  balanceStatLabel: {
    color: "#94a3b8",
    fontSize: 11
  },
  balanceStatValue: {
    fontSize: 14,
    fontWeight: "800"
  },
  grid: {
    flexDirection: "row",
    flexWrap: "wrap",
    gap: spacing.md
  },
  action: {
    backgroundColor: colors.surface,
    borderColor: colors.border,
    borderRadius: theme.radius.lg,
    borderWidth: 1,
    gap: spacing.sm,
    minHeight: 126,
    padding: spacing.md,
    width: "47.5%"
  },
  actionDisabled: {
    opacity: 0.5
  },
  actionPressed: {
    opacity: 0.75
  },
  actionMark: {
    backgroundColor: colors.primary,
    borderRadius: 999,
    height: 10,
    width: 36
  },
  actionLabel: {
    color: colors.text,
    fontSize: 14,
    fontWeight: "800",
    marginTop: spacing.sm
  },
  actionMeta: {
    color: colors.mutedText,
    fontSize: 11,
    lineHeight: 16
  },
  orderStats: {
    flexDirection: "row",
    marginTop: spacing.lg
  },
  orderStat: {
    alignItems: "center",
    borderRightColor: colors.border,
    borderRightWidth: 1,
    flex: 1,
    gap: spacing.xs
  },
  orderStatValue: {
    fontSize: 22,
    fontWeight: "900"
  },
  orderStatLabel: {
    color: colors.mutedText,
    fontSize: 10
  }
});
