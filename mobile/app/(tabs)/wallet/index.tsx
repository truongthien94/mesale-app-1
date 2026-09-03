import { LinearGradient } from "expo-linear-gradient";
import { useRouter } from "expo-router";
import { Eye, EyeOff, History, Receipt, Sparkles, WalletCards } from "lucide-react-native";
import { useState } from "react";
import {
  Image,
  Pressable,
  RefreshControl,
  ScrollView,
  StyleSheet,
  Text,
  View
} from "react-native";
import { useSafeAreaInsets } from "react-native-safe-area-context";
import { useAuth } from "@/auth/AuthProvider";
import { useAccountSummary, useOrders } from "@/features/wallet/api";
import { formatDate, formatVnd } from "@/features/wallet/format";
import { isRecordedOrder, orderListKey } from "@/features/wallet/orders";
import type { Order, OrderStatus } from "@/features/wallet/types";
import { useTheme } from "@/theme/ThemeProvider";

const statusBorder: Record<OrderStatus, string> = {
  unrecorded: "#60a5fa",
  pending: "#facc15",
  approved: "#10b981",
  rejected: "#f43f5e"
};

const statusCopy: Record<OrderStatus, string> = {
  unrecorded: "Chờ sàn ghi nhận",
  pending: "Chờ duyệt",
  approved: "Đã cộng tiền",
  rejected: "Từ chối"
};

export default function WalletRoute() {
  const router = useRouter();
  const insets = useSafeAreaInsets();
  const { colors, spacing, radius } = useTheme();
  const { user } = useAuth();
  const accountQuery = useAccountSummary();
  const ordersQuery = useOrders();
  const [showBalance, setShowBalance] = useState(true);

  const liveAccount = accountQuery.data;
  const authSnapshot = user?.financialSnapshot;
  const balance = liveAccount?.wallet.balance ?? authSnapshot?.balance;
  const totalCashback = liveAccount?.wallet.totalCashback ?? authSnapshot?.totalCashback;
  const totalWithdrawn = liveAccount?.wallet.totalWithdrawn ?? authSnapshot?.totalWithdrawn;
  const pendingOrders = liveAccount?.stats.ordersPending;
  const recentOrders = (ordersQuery.data?.pages.flatMap((page) => page.items) ?? [])
    .filter(isRecordedOrder)
    .slice(0, 5);
  const balanceText = showBalance
    ? typeof balance === "number" ? formatVnd(balance) : null
    : "••••••";

  return (
    <View style={[styles.screen, { backgroundColor: colors.background }]}>
      <ScrollView
        contentContainerStyle={{ gap: spacing.lg, padding: spacing.md, paddingBottom: spacing.xl + insets.bottom }}
        contentInsetAdjustmentBehavior="automatic"
        refreshControl={(
          <RefreshControl
            onRefresh={() => {
              void accountQuery.refetch();
              void ordersQuery.refetch();
            }}
            refreshing={accountQuery.isRefetching || ordersQuery.isRefetching}
            tintColor={colors.primary}
          />
        )}
      >
        {accountQuery.isError ? (
          <View style={[styles.inlineError, { backgroundColor: colors.surface, borderColor: colors.border }]}>
            <Text style={[styles.stateText, { color: colors.mutedText }]}>Chưa thể làm mới số dư ví. Dữ liệu hiện tại có thể đã cũ.</Text>
            <Pressable accessibilityRole="button" onPress={() => void accountQuery.refetch()}>
              <Text style={[styles.retryText, { color: colors.primary }]}>Thử lại</Text>
            </Pressable>
          </View>
        ) : null}

        <LinearGradient
          colors={["#FF5733", "#FF451A", "#E02F05"]}
          end={{ x: 1, y: 1 }}
          start={{ x: 0, y: 0 }}
          style={[styles.balanceBanner, { borderRadius: radius.lg }]}
        >
          <View style={styles.bannerOrbLarge} />
          <View style={styles.bannerOrbSmall} />
          <View style={styles.bannerHeader}>
            <View style={styles.bannerTitleWrap}>
              <Text style={styles.bannerTitle}>Ví tiền của tôi</Text>
              <Text style={styles.bannerEyebrow}>Số dư khả dụng</Text>
            </View>
            <WalletCards color="#ffffff" size={28} />
          </View>
          <View style={styles.balanceRow}>
            {balanceText === null ? (
              <View accessibilityLabel="Đang tải số dư" accessibilityRole="progressbar" style={styles.balanceSkeleton} />
            ) : (
              <Text accessibilityLabel={showBalance && typeof balance === "number" ? `Số dư ${formatVnd(balance)}` : "Số dư đang ẩn"} adjustsFontSizeToFit numberOfLines={1} style={styles.balanceValue}>
                {balanceText}
              </Text>
            )}
            <Pressable
              accessibilityLabel={showBalance ? "Ẩn số dư" : "Hiện số dư"}
              accessibilityRole="button"
              onPress={() => setShowBalance((value) => !value)}
              style={styles.eyeButton}
            >
              {showBalance ? <Eye color="#ffffff" size={19} /> : <EyeOff color="#ffffff" size={19} />}
            </Pressable>
          </View>
          <View style={styles.bannerActions}>
            <Pressable
              accessibilityRole="button"
              onPress={() => router.push("/(tabs)/withdraw")}
              style={({ pressed }) => [styles.bannerAction, pressed && styles.pressed]}
            >
              <Text style={styles.bannerActionText}>Rút tiền</Text>
            </Pressable>
            <Pressable
              accessibilityRole="button"
              onPress={() => router.push("/(tabs)/wallet/orders")}
              style={({ pressed }) => [styles.bannerActionSecondary, pressed && styles.pressed]}
            >
              <History color="#ffffff" size={15} />
              <Text style={styles.bannerActionSecondaryText}>Lịch sử</Text>
            </Pressable>
          </View>
        </LinearGradient>

        <View style={styles.statsRow}>
          <WalletStat colors={colors} iconColor="#10b981" label="Tổng Cashback" value={showBalance && typeof totalCashback === "number" ? formatVnd(totalCashback) : showBalance ? null : "••••"} />
          <WalletStat colors={colors} iconColor="#3b82f6" label="Đã rút" value={showBalance && typeof totalWithdrawn === "number" ? formatVnd(totalWithdrawn) : showBalance ? null : "••••"} />
          <WalletStat colors={colors} iconColor="#facc15" label="Chờ duyệt" value={typeof pendingOrders === "number" ? String(pendingOrders) : null} />
        </View>

        <View style={[styles.recentCard, { backgroundColor: colors.surface, borderColor: colors.border, borderRadius: radius.lg }]}>
          <View style={styles.sectionHeader}>
            <View style={styles.sectionHeadingWrap}>
              <View style={[styles.sectionIcon, { backgroundColor: `${colors.primary}16` }]}>
                <Receipt color={colors.primary} size={19} />
                <Sparkles color={colors.primary} fill={colors.primary} size={9} style={styles.sparkle} />
              </View>
              <View>
                <Text style={[styles.sectionTitle, { color: colors.text }]}>Đơn hoàn tiền gần đây</Text>
                <Text style={[styles.sectionCaption, { color: colors.mutedText }]}>Giao dịch phát sinh gần đây</Text>
              </View>
            </View>
            <Pressable accessibilityRole="button" onPress={() => router.push("/(tabs)/wallet/orders")}>
              <Text style={[styles.seeAll, { color: colors.primary }]}>Xem tất cả</Text>
            </Pressable>
          </View>

          {ordersQuery.isPending ? <Text style={[styles.stateText, { color: colors.mutedText }]}>Đang tải giao dịch...</Text> : null}
          {ordersQuery.isError ? (
            <View style={[styles.inlineError, { backgroundColor: colors.background, borderColor: colors.border }]}>
              <Text style={[styles.stateText, { color: colors.mutedText }]}>Chưa thể tải giao dịch gần đây.</Text>
              <Pressable accessibilityRole="button" onPress={() => void ordersQuery.refetch()}>
                <Text style={[styles.retryText, { color: colors.primary }]}>Thử lại</Text>
              </Pressable>
            </View>
          ) : null}
          {!ordersQuery.isPending && !ordersQuery.isError && recentOrders.length === 0 ? (
            <View style={styles.emptyState}>
              <Receipt color={colors.mutedText} size={26} />
              <Text style={[styles.stateText, { color: colors.mutedText }]}>Bạn chưa có giao dịch hoàn tiền nào.</Text>
            </View>
          ) : null}
          <View style={{ gap: spacing.sm }}>
            {recentOrders.map((order) => (
              <RecentOrder
                colors={colors}
                key={orderListKey(order)}
                onPress={() => router.push(`/(tabs)/wallet/orders/${order.id}`)}
                order={order}
                radius={radius}
                showBalance={showBalance}
              />
            ))}
          </View>
        </View>
      </ScrollView>
    </View>
  );
}

function WalletStat({ colors, iconColor, label, value }: { colors: ReturnType<typeof useTheme>["colors"]; iconColor: string; label: string; value: string | null }) {
  return (
    <View style={[styles.statCard, { backgroundColor: colors.surface, borderColor: colors.border }]}>
      <View style={[styles.statDot, { backgroundColor: iconColor }]} />
      <Text numberOfLines={2} style={[styles.statLabel, { color: colors.mutedText }]}>{label}</Text>
      {value === null
        ? <View accessibilityLabel={`Đang tải ${label}`} accessibilityRole="progressbar" style={[styles.statSkeleton, { backgroundColor: colors.border }]} />
        : <Text adjustsFontSizeToFit numberOfLines={1} style={[styles.statValue, { color: colors.text }]}>{value}</Text>}
    </View>
  );
}

function RecentOrder({ colors, onPress, order, radius, showBalance }: {
  colors: ReturnType<typeof useTheme>["colors"];
  onPress: () => void;
  order: Order;
  radius: ReturnType<typeof useTheme>["radius"];
  showBalance: boolean;
}) {
  const borderColor = statusBorder[order.status];
  const imageUri = order.product_image?.startsWith("http://")
    ? order.product_image.replace(/^http:/, "https:")
    : order.product_image;
  return (
    <Pressable
      accessibilityRole="button"
      accessibilityLabel={`Xem đơn ${order.order_id ?? order.id}`}
      onPress={onPress}
      style={({ pressed }) => [styles.orderRow, { backgroundColor: colors.background, borderColor: colors.border, borderRadius: radius.md, borderLeftColor: borderColor }, pressed && styles.pressed]}
    >
      {imageUri ? <Image accessibilityIgnoresInvertColors source={{ uri: imageUri }} style={[styles.orderImage, { borderRadius: radius.sm }]} /> : <View style={[styles.orderImage, styles.imageFallback, { backgroundColor: colors.border, borderRadius: radius.sm }]}><Receipt color={colors.mutedText} size={17} /></View>}
      <View style={styles.orderCopy}>
        <Text numberOfLines={1} style={[styles.orderTitle, { color: colors.text }]}>{order.product_name || "Đơn hàng hoàn tiền"}</Text>
        <Text style={[styles.orderMeta, { color: colors.mutedText }]}>{order.platform?.toUpperCase() || "MARKETPLACE"} · #{order.order_id || order.id}</Text>
        <View style={styles.orderFooter}>
          <Text style={[styles.orderStatus, { backgroundColor: `${borderColor}1c`, color: borderColor }]}>{statusCopy[order.status]}</Text>
          <Text style={[styles.orderDate, { color: colors.mutedText }]}>{formatDate(order.created_at, false)}</Text>
        </View>
      </View>
      <Text style={[styles.orderCashback, { color: showBalance ? "#e11d48" : colors.mutedText }]}>{showBalance ? `+${formatVnd(order.cashback_amount)}` : "••••"}</Text>
    </Pressable>
  );
}

const styles = StyleSheet.create({
  screen: { flex: 1 },
  balanceBanner: { minHeight: 205, overflow: "hidden", padding: 22 },
  bannerOrbLarge: { backgroundColor: "rgba(255,255,255,0.17)", borderRadius: 90, height: 170, position: "absolute", right: -55, top: -60, width: 170 },
  bannerOrbSmall: { backgroundColor: "rgba(80,16,0,0.14)", borderRadius: 70, bottom: -55, height: 130, left: -30, position: "absolute", width: 130 },
  bannerHeader: { alignItems: "flex-start", flexDirection: "row", justifyContent: "space-between" },
  bannerTitleWrap: { gap: 7 },
  bannerTitle: { color: "#ffffff", fontSize: 19, fontWeight: "900" },
  bannerEyebrow: { color: "rgba(255,255,255,0.86)", fontSize: 11, fontWeight: "800", letterSpacing: 1, textTransform: "uppercase" },
  balanceRow: { alignItems: "center", flexDirection: "row", gap: 9, marginTop: 16 },
  balanceValue: { color: "#ffffff", flex: 1, fontSize: 32, fontWeight: "900", letterSpacing: -1 },
  balanceSkeleton: { backgroundColor: "rgba(255,255,255,0.34)", borderRadius: 8, height: 38, width: 172 },
  eyeButton: { alignItems: "center", backgroundColor: "rgba(255,255,255,0.18)", borderRadius: 9, height: 34, justifyContent: "center", width: 34 },
  bannerActions: { flexDirection: "row", gap: 9, marginTop: 20 },
  bannerAction: { alignItems: "center", backgroundColor: "#ffffff", borderRadius: 11, justifyContent: "center", minHeight: 40, paddingHorizontal: 17 },
  bannerActionText: { color: "#e02f05", fontSize: 12, fontWeight: "900" },
  bannerActionSecondary: { alignItems: "center", borderColor: "rgba(255,255,255,0.32)", borderRadius: 11, borderWidth: 1, flexDirection: "row", gap: 6, justifyContent: "center", minHeight: 40, paddingHorizontal: 15 },
  bannerActionSecondaryText: { color: "#ffffff", fontSize: 12, fontWeight: "900" },
  statsRow: { flexDirection: "row", gap: 10 },
  statCard: { borderRadius: 16, borderWidth: 1, flex: 1, minHeight: 118, padding: 13 },
  statDot: { borderRadius: 5, height: 8, marginBottom: 13, width: 28 },
  statLabel: { fontSize: 10, fontWeight: "800", lineHeight: 14, textTransform: "uppercase" },
  statValue: { fontSize: 15, fontWeight: "900", marginTop: 8 },
  statSkeleton: { borderRadius: 6, height: 17, marginTop: 8, width: "76%" },
  recentCard: { borderWidth: 1, gap: 18, padding: 18 },
  sectionHeader: { alignItems: "center", flexDirection: "row", gap: 10, justifyContent: "space-between" },
  sectionHeadingWrap: { alignItems: "center", flex: 1, flexDirection: "row", gap: 10 },
  sectionIcon: { alignItems: "center", borderRadius: 12, height: 40, justifyContent: "center", position: "relative", width: 40 },
  sparkle: { bottom: 10, left: 21, position: "absolute" },
  sectionTitle: { fontSize: 14, fontWeight: "900", textTransform: "uppercase" },
  sectionCaption: { fontSize: 11, marginTop: 3 },
  seeAll: { fontSize: 11, fontWeight: "900" },
  orderRow: { alignItems: "center", borderLeftWidth: 4, borderWidth: 1, flexDirection: "row", gap: 10, minHeight: 76, padding: 10 },
  orderImage: { backgroundColor: "#f1f5f9", height: 45, width: 45 },
  imageFallback: { alignItems: "center", justifyContent: "center" },
  orderCopy: { flex: 1, gap: 4, minWidth: 0 },
  orderTitle: { fontSize: 12, fontWeight: "800" },
  orderMeta: { fontSize: 9, fontWeight: "700" },
  orderFooter: { alignItems: "center", flexDirection: "row", justifyContent: "space-between" },
  orderStatus: { borderRadius: 5, fontSize: 9, fontWeight: "800", overflow: "hidden", paddingHorizontal: 5, paddingVertical: 3 },
  orderDate: { fontSize: 9 },
  orderCashback: { fontSize: 12, fontWeight: "900" },
  inlineError: { alignItems: "center", borderRadius: 12, borderWidth: 1, flexDirection: "row", gap: 10, padding: 12 },
  stateText: { flex: 1, fontSize: 12, lineHeight: 18 },
  retryText: { fontSize: 12, fontWeight: "900" },
  emptyState: { alignItems: "center", gap: 9, paddingVertical: 22 },
  pressed: { opacity: 0.76 }
});
