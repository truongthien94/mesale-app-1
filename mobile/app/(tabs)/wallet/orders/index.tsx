import { useCallback, useMemo, useState } from "react";
import { useRouter } from "expo-router";
import {
  ActivityIndicator,
  Alert,
  FlatList,
  Image,
  Pressable,
  ScrollView,
  StyleSheet,
  Text,
  View,
  type ListRenderItemInfo
} from "react-native";
import {
  ChevronDown,
  ChevronRight,
  CircleCheck,
  Clock3,
  ImageIcon,
  Info,
  Receipt,
  ShoppingBag,
  Smartphone,
  WalletCards
} from "lucide-react-native";
import { useSafeAreaInsets } from "react-native-safe-area-context";
import { formatAccountMoney } from "@/features/home/format";
import { useAccountSummary } from "@/features/home/hooks";
import { useOrders } from "@/features/wallet/api";
import { formatDate, formatVnd, statusLabel } from "@/features/wallet/format";
import { orderListKey, orderRecordType, secureOrderImageUrl } from "@/features/wallet/orders";
import type { Order, OrderQueryFilters, OrderStatus } from "@/features/wallet/types";
import { useTheme } from "@/theme/ThemeProvider";

type StatusFilter = "all" | Exclude<OrderStatus, "unrecorded">;

const statusFilters: { label: string; value: StatusFilter }[] = [
  { label: "Tất cả", value: "all" },
  { label: "Chờ xác nhận", value: "pending" },
  { label: "Đã xác nhận", value: "approved" },
  { label: "Bị từ chối", value: "rejected" }
];

export default function OrdersScreen() {
  const router = useRouter();
  const insets = useSafeAreaInsets();
  const { colors, radius, scheme, spacing } = useTheme();
  const [guideExpanded, setGuideExpanded] = useState(true);
  const [statusFilter, setStatusFilter] = useState<StatusFilter>("all");
  const queryFilters = useMemo<OrderQueryFilters>(
    () => statusFilter === "all" ? {} : { status: statusFilter },
    [statusFilter]
  );
  const accountQuery = useAccountSummary();
  const ordersQuery = useOrders(queryFilters);
  const orders = useMemo(
    () => ordersQuery.data?.pages.flatMap((page) => page.items) ?? [],
    [ordersQuery.data?.pages]
  );

  const openOrder = useCallback((order: Order) => {
    if (orderRecordType(order) === "unrecorded") {
      Alert.alert(
        "Chờ sàn ghi nhận",
        "Sàn đang đối soát giao dịch này. Chi tiết đơn hàng sẽ khả dụng sau khi sàn trả dữ liệu về hệ thống."
      );
      return;
    }
    router.push(`/(tabs)/wallet/orders/${order.id}`);
  }, [router]);

  const renderItem = useCallback(({ item }: ListRenderItemInfo<Order>) => (
    <OrderCard
      colors={colors}
      onPress={() => openOrder(item)}
      order={item}
      radius={radius}
      scheme={scheme}
    />
  ), [colors, openOrder, radius, scheme]);

  const pendingAmount = accountQuery.data?.wallet.pendingCashback ?? null;
  const pendingCount = accountQuery.data?.stats.ordersPending;
  const approvedAmount = accountQuery.data?.wallet.approvedCashback ?? null;
  const approvedCount = accountQuery.data?.stats.ordersApproved;

  const listHeader = (
    <View style={styles.headerStack}>
      <Text accessibilityRole="header" style={[styles.screenTitle, { color: colors.text }]}>Đơn hàng</Text>

      <View style={[styles.guideCard, { backgroundColor: scheme === "dark" ? "#172554" : "#eff6ff", borderColor: scheme === "dark" ? "#1e3a8a" : "#bfdbfe", borderRadius: radius.lg }]}>
        <Pressable
          accessibilityRole="button"
          accessibilityState={{ expanded: guideExpanded }}
          onPress={() => setGuideExpanded((value) => !value)}
          style={styles.guideHeader}
        >
          <View style={[styles.guideBrand, { backgroundColor: colors.surface }]}>
            <Image accessibilityIgnoresInvertColors resizeMode="contain" source={require("../../../../assets/mesale-logo.png")} style={styles.guideLogo} />
          </View>
          <View style={styles.guideTitleWrap}>
            <Text style={[styles.guideTitle, { color: scheme === "dark" ? "#dbeafe" : "#1e3a8a" }]}>Quy trình ghi nhận đơn hàng</Text>
            <Text style={[styles.guideTiming, { color: scheme === "dark" ? "#93c5fd" : "#2563eb" }]}>Đơn lên app: TikTok ~1 giờ · Shopee ~1 ngày</Text>
          </View>
          <ChevronDown color={scheme === "dark" ? "#93c5fd" : "#2563eb"} size={18} style={guideExpanded ? styles.chevronUp : undefined} />
        </Pressable>

        {guideExpanded ? (
          <View style={[styles.guideSteps, { borderTopColor: scheme === "dark" ? "#1e3a8a" : "#bfdbfe" }]}>
            <GuideStep
              caption={'Bấm "Mua ngay" trong app rồi thanh toán như bình thường — sàn sẽ ghi nhận đơn cho bạn.'}
              colors={colors}
              icon={<ShoppingBag color="#2563eb" size={17} />}
              title="Đặt đơn qua Mê Sale"
            />
            <GuideStep
              caption="TikTok ~1 giờ · Shopee ~1 ngày"
              colors={colors}
              icon={<Smartphone color="#7c3aed" size={17} />}
              title="Đơn hiện ở màn này"
            />
            <GuideStep
              caption="Sau khi giao thành công, sàn duyệt đơn trong 7–14 ngày. Duyệt xong, tiền tự cộng vào ví Mê Sale."
              colors={colors}
              icon={<WalletCards color="#16a34a" size={17} />}
              title="Nhận hàng → tiền về ví"
            />
            <Text style={[styles.guideReassurance, { color: scheme === "dark" ? "#bfdbfe" : "#1d4ed8" }]}>Đôi khi sàn gửi dữ liệu chậm hơn một chút — đơn không mất đâu.</Text>
          </View>
        ) : null}
      </View>

      <View style={styles.summaryRow}>
        <SummaryCard
          amount={accountQuery.data ? formatAccountMoney(pendingAmount, "vi") : null}
          colors={colors}
          count={pendingCount}
          description="Về ví 7–14 ngày sau khi giao"
          icon={<Clock3 color="#d97706" size={19} />}
          label="Chờ xác nhận"
          loading={accountQuery.isPending}
          radius={radius}
          tone="pending"
        />
        <SummaryCard
          amount={accountQuery.data ? formatAccountMoney(approvedAmount, "vi") : null}
          colors={colors}
          count={approvedCount}
          description="Đã cộng vào ví của bạn"
          icon={<CircleCheck color="#16a34a" size={19} />}
          label="Đã xác nhận"
          loading={accountQuery.isPending}
          radius={radius}
          tone="approved"
        />
      </View>

      {accountQuery.isError && !accountQuery.data ? (
        <InlineQueryNotice
          colors={colors}
          message="Chưa thể tải số liệu tổng hợp đơn hàng."
          onRetry={() => void accountQuery.refetch()}
        />
      ) : null}

      <ScrollView
        contentContainerStyle={styles.chipsContent}
        horizontal
        showsHorizontalScrollIndicator={false}
        style={styles.chipsScroll}
      >
        {statusFilters.map((filter) => {
          const selected = filter.value === statusFilter;
          const appearance = filterAppearance(filter.value, selected, scheme);
          return (
            <Pressable
              accessibilityRole="tab"
              accessibilityState={{ selected }}
              key={filter.value}
              onPress={() => setStatusFilter(filter.value)}
              style={[styles.filterChip, { backgroundColor: appearance.background, borderColor: appearance.border }]}
            >
              <Text style={[styles.filterChipText, { color: appearance.text }]}>{filter.label}</Text>
            </Pressable>
          );
        })}
      </ScrollView>

      <View style={styles.listHeadingRow}>
        <Text style={[styles.listHeading, { color: colors.text }]}>Danh sách đơn</Text>
        <Text style={[styles.listCaption, { color: colors.mutedText }]}>Dữ liệu từ Mê Sale và sàn mua sắm</Text>
      </View>

      {ordersQuery.isError && orders.length > 0 ? (
        <InlineQueryNotice
          colors={colors}
          message="Lần làm mới gần nhất thất bại. Danh sách đang hiển thị có thể đã cũ."
          onRetry={() => void ordersQuery.refetch()}
        />
      ) : null}
    </View>
  );

  return (
    <View style={[styles.screen, { backgroundColor: colors.background }]}>
      <FlatList
        contentContainerStyle={[
          styles.content,
          { paddingBottom: spacing.xl + insets.bottom, paddingTop: insets.top + spacing.sm },
          orders.length === 0 && styles.emptyContent
        ]}
        data={orders}
        initialNumToRender={8}
        keyExtractor={orderListKey}
        ListEmptyComponent={(
          <OrdersEmptyState
            colors={colors}
            filter={statusFilter}
            loading={ordersQuery.isPending}
            onRetry={() => void ordersQuery.refetch()}
            onShowAll={() => setStatusFilter("all")}
            onShop={() => router.push("/(tabs)/home")}
            queryError={ordersQuery.isError ? ordersQuery.error : null}
            scheme={scheme}
          />
        )}
        ListFooterComponent={(
          <OrdersFooter
            colors={colors}
            error={ordersQuery.isFetchNextPageError ? ordersQuery.error : null}
            loading={ordersQuery.isFetchingNextPage}
            onRetry={() => void ordersQuery.fetchNextPage()}
          />
        )}
        ListHeaderComponent={listHeader}
        maxToRenderPerBatch={8}
        onEndReached={() => {
          if (ordersQuery.hasNextPage && !ordersQuery.isFetchingNextPage) void ordersQuery.fetchNextPage();
        }}
        onEndReachedThreshold={0.35}
        onRefresh={() => {
          void accountQuery.refetch();
          void ordersQuery.refetch();
        }}
        refreshing={(accountQuery.isRefetching || ordersQuery.isRefetching) && !ordersQuery.isFetchingNextPage}
        renderItem={renderItem}
        showsVerticalScrollIndicator={false}
        updateCellsBatchingPeriod={50}
        windowSize={7}
      />
    </View>
  );
}

function GuideStep({ caption, colors, icon, title }: {
  caption: string;
  colors: ReturnType<typeof useTheme>["colors"];
  icon: React.ReactNode;
  title: string;
}) {
  return (
    <View style={styles.guideStep}>
      <View style={[styles.stepIcon, { backgroundColor: colors.surface, borderColor: colors.border }]}>{icon}</View>
      <View style={styles.stepCopy}>
        <Text style={[styles.stepTitle, { color: colors.text }]}>{title}</Text>
        <Text style={[styles.stepCaption, { color: colors.mutedText }]}>{caption}</Text>
      </View>
    </View>
  );
}

function SummaryCard({ amount, colors, count, description, icon, label, loading, radius, tone }: {
  amount: string | null;
  colors: ReturnType<typeof useTheme>["colors"];
  count: number | undefined;
  description: string;
  icon: React.ReactNode;
  label: string;
  loading: boolean;
  radius: ReturnType<typeof useTheme>["radius"];
  tone: "pending" | "approved";
}) {
  const accent = tone === "pending" ? "#f59e0b" : "#22c55e";
  return (
    <View style={[styles.summaryCard, { backgroundColor: colors.surface, borderColor: colors.border, borderRadius: radius.lg }]}>
      <View style={styles.summaryHeader}>
        <View style={[styles.summaryIcon, { backgroundColor: `${accent}18` }]}>{icon}</View>
        <Text style={[styles.summaryLabel, { color: colors.mutedText }]}>{label}</Text>
      </View>
      {loading ? (
        <ActivityIndicator color={accent} style={styles.summaryLoader} />
      ) : (
        <>
          <Text adjustsFontSizeToFit numberOfLines={1} style={[styles.summaryAmount, { color: accent }]}>{amount ?? "--"}</Text>
          <Text style={[styles.summaryCount, { color: colors.mutedText }]}>{typeof count === "number" ? `${count} đơn` : "Chưa có dữ liệu"}</Text>
          <Text numberOfLines={2} style={[styles.summaryDescription, { color: colors.mutedText }]}>{description}</Text>
        </>
      )}
    </View>
  );
}

function OrderCard({ colors, onPress, order, radius, scheme }: {
  colors: ReturnType<typeof useTheme>["colors"];
  onPress: () => void;
  order: Order;
  radius: ReturnType<typeof useTheme>["radius"];
  scheme: ReturnType<typeof useTheme>["scheme"];
}) {
  const type = orderRecordType(order);
  const imageUrl = secureOrderImageUrl(order.product_image);
  const marketplace = marketplaceAppearance(order.platform);
  const status = statusAppearance(order.status, scheme);
  const reference = type === "unrecorded" ? order.trans_id : order.order_id;
  const productName = order.product_name || "Đơn hàng hoàn tiền";
  const cashbackLabel = `${type === "unrecorded" ? "Ước tính " : ""}${formatVnd(order.cashback_amount)}`;

  return (
    <Pressable
      accessibilityLabel={`${productName}. Mã ${reference ?? order.id}. Hoàn tiền ${cashbackLabel}. Trạng thái ${statusLabel(order.status)}.`}
      accessibilityRole="button"
      onPress={onPress}
      style={({ pressed }) => [
        styles.card,
        { backgroundColor: colors.surface, borderColor: colors.border, borderLeftColor: status.dot, borderRadius: radius.lg },
        pressed && styles.pressed
      ]}
    >
      <View style={styles.cardTopRow}>
        <View style={styles.referenceRow}>
          <View style={[styles.marketplaceBadge, { backgroundColor: marketplace.background }]}>
            <Text style={styles.marketplaceText}>{marketplace.label}</Text>
          </View>
          <Text numberOfLines={1} style={[styles.reference, { color: colors.mutedText }]}>#{reference || order.id}</Text>
        </View>
        <Text style={[styles.cardDate, { color: colors.mutedText }]}>{formatDate(order.created_at)}</Text>
      </View>

      <View style={styles.cardMainRow}>
        {imageUrl ? (
          <Image accessibilityIgnoresInvertColors source={{ uri: imageUrl }} style={[styles.productImage, { backgroundColor: colors.background, borderColor: colors.border, borderRadius: radius.md }]} />
        ) : (
          <View style={[styles.productImage, styles.productImageFallback, { backgroundColor: colors.background, borderColor: colors.border, borderRadius: radius.md }]}>
            <ImageIcon color={colors.mutedText} size={22} />
          </View>
        )}
        <Text numberOfLines={2} style={[styles.productTitle, { color: colors.text }]}>{productName}</Text>
        <View style={styles.amountColumn}>
          <Text adjustsFontSizeToFit numberOfLines={1} style={[styles.cashbackAmount, { color: type === "unrecorded" ? "#059669" : "#16a34a" }]}>{type === "unrecorded" ? "~" : "+"}{formatVnd(order.cashback_amount)}</Text>
          <Text style={[styles.amountCaption, { color: colors.mutedText }]}>{type === "unrecorded" ? "Ước tính" : formatVnd(order.original_price)}</Text>
        </View>
      </View>

      <View style={styles.cardBottomRow}>
        <View style={styles.statusRow}>
          <View style={[styles.statusDot, { backgroundColor: status.dot }]} />
          <Text style={[styles.statusText, { color: status.text }]}>{statusLabel(order.status)}</Text>
        </View>
        <ChevronRight color={colors.mutedText} size={18} />
      </View>
    </Pressable>
  );
}

function OrdersEmptyState({ colors, filter, loading, onRetry, onShop, onShowAll, queryError, scheme }: {
  colors: ReturnType<typeof useTheme>["colors"];
  filter: StatusFilter;
  loading: boolean;
  onRetry: () => void;
  onShop: () => void;
  onShowAll: () => void;
  queryError: unknown;
  scheme: ReturnType<typeof useTheme>["scheme"];
}) {
  if (loading) {
    return (
      <View accessibilityRole="progressbar" style={styles.stateCard}>
        <ActivityIndicator color={colors.primary} size="large" />
        <Text style={[styles.stateMessage, { color: colors.mutedText }]}>Đang tải danh sách đơn hàng...</Text>
      </View>
    );
  }

  if (queryError) {
    return (
      <View accessibilityRole="alert" style={styles.stateCard}>
        <Text style={[styles.stateTitle, { color: colors.text }]}>Chưa thể tải đơn hàng</Text>
        <Text style={[styles.stateMessage, { color: colors.mutedText }]}>{queryError instanceof Error ? queryError.message : "Vui lòng kiểm tra kết nối và thử lại."}</Text>
        <Pressable accessibilityRole="button" onPress={onRetry} style={[styles.primaryButton, { backgroundColor: colors.primary }]}>
          <Text style={styles.primaryButtonText}>Thử lại</Text>
        </Pressable>
      </View>
    );
  }

  const filtered = filter !== "all";
  return (
    <View style={styles.stateCard}>
      <View style={[styles.emptyIcon, { backgroundColor: scheme === "dark" ? "#172554" : "#eff6ff", borderColor: scheme === "dark" ? "#1e3a8a" : "#bfdbfe" }]}>
        <Receipt color={scheme === "dark" ? "#60a5fa" : "#2563eb"} size={30} />
      </View>
      <Text style={[styles.stateTitle, { color: colors.text }]}>{filtered ? "Chưa có đơn ở trạng thái này" : "Chưa có đơn hàng nào"}</Text>
      <Text style={[styles.stateMessage, { color: colors.mutedText }]}>{filtered ? "Hãy chọn Tất cả để xem những đơn ở trạng thái khác." : "Sau khi đặt qua Mê Sale, đơn TikTok thường lên app sau ~1 giờ và Shopee sau ~1 ngày."}</Text>
      <Pressable accessibilityRole="button" onPress={filtered ? onShowAll : onShop} style={[styles.primaryButton, { backgroundColor: "#2563eb" }]}>
        {filtered ? null : <ShoppingBag color="#ffffff" size={16} />}
        <Text style={styles.primaryButtonText}>{filtered ? "Xem tất cả" : "Mua sắm ngay"}</Text>
      </Pressable>
    </View>
  );
}

function OrdersFooter({ colors, error, loading, onRetry }: {
  colors: ReturnType<typeof useTheme>["colors"];
  error: unknown;
  loading: boolean;
  onRetry: () => void;
}) {
  if (loading) return <ActivityIndicator color={colors.primary} style={styles.footer} />;
  if (!error) return <View style={styles.footerSpacer} />;
  return <InlineQueryNotice colors={colors} message={error instanceof Error ? error.message : "Chưa thể tải thêm đơn hàng."} onRetry={onRetry} />;
}

function InlineQueryNotice({ colors, message, onRetry }: {
  colors: ReturnType<typeof useTheme>["colors"];
  message: string;
  onRetry: () => void;
}) {
  return (
    <View accessibilityRole="alert" style={[styles.inlineNotice, { backgroundColor: colors.surface, borderColor: colors.border }]}>
      <Info color={colors.primary} size={16} />
      <Text style={[styles.inlineNoticeText, { color: colors.mutedText }]}>{message}</Text>
      <Pressable accessibilityRole="button" onPress={onRetry}>
        <Text style={[styles.inlineRetry, { color: colors.primary }]}>Thử lại</Text>
      </Pressable>
    </View>
  );
}

function filterAppearance(filter: StatusFilter, selected: boolean, scheme: "light" | "dark") {
  if (selected) {
    if (filter === "pending") return { background: "#f59e0b", border: "#f59e0b", text: "#ffffff" };
    if (filter === "approved") return { background: "#22c55e", border: "#22c55e", text: "#ffffff" };
    if (filter === "rejected") return { background: "#ef4444", border: "#ef4444", text: "#ffffff" };
    return { background: "#2563eb", border: "#2563eb", text: "#ffffff" };
  }
  return scheme === "dark"
    ? { background: "#1e293b", border: "#334155", text: "#cbd5e1" }
    : { background: "#ffffff", border: "#e2e8f0", text: "#64748b" };
}

function marketplaceAppearance(platform: string | null): { background: string; label: string } {
  if (platform === "shopee") return { background: "#ee4d2d", label: "Shopee" };
  if (platform === "tiktok") return { background: "#111827", label: "TikTok Shop" };
  if (platform === "lazada") return { background: "#1d4ed8", label: "Lazada" };
  return { background: "#475569", label: platform?.toUpperCase() || "SÀN" };
}

function statusAppearance(status: OrderStatus, scheme: "light" | "dark"): { dot: string; text: string } {
  if (status === "unrecorded") return { dot: "#60a5fa", text: scheme === "dark" ? "#93c5fd" : "#2563eb" };
  if (status === "approved") return { dot: "#22c55e", text: scheme === "dark" ? "#86efac" : "#15803d" };
  if (status === "rejected") return { dot: "#f87171", text: scheme === "dark" ? "#fca5a5" : "#dc2626" };
  return { dot: "#facc15", text: scheme === "dark" ? "#fde047" : "#a16207" };
}

const styles = StyleSheet.create({
  screen: { flex: 1 },
  content: { gap: 10, paddingHorizontal: 14 },
  emptyContent: { flexGrow: 1 },
  headerStack: { gap: 14, marginBottom: 4 },
  screenTitle: { fontSize: 22, fontWeight: "900", letterSpacing: -0.4, marginTop: 2 },
  guideCard: { borderWidth: 1, overflow: "hidden", padding: 13 },
  guideHeader: { alignItems: "center", flexDirection: "row", gap: 10 },
  guideBrand: { alignItems: "center", borderRadius: 12, height: 42, justifyContent: "center", overflow: "hidden", width: 42 },
  guideLogo: { height: 32, width: 32 },
  guideTitleWrap: { flex: 1, gap: 4, minWidth: 0 },
  guideTitle: { fontSize: 13, fontWeight: "900" },
  guideTiming: { fontSize: 11, fontWeight: "700", lineHeight: 15 },
  chevronUp: { transform: [{ rotate: "180deg" }] },
  guideSteps: { borderTopWidth: StyleSheet.hairlineWidth, gap: 12, marginTop: 12, paddingTop: 12 },
  guideStep: { alignItems: "center", flexDirection: "row", gap: 10 },
  stepIcon: { alignItems: "center", borderRadius: 11, borderWidth: 1, height: 32, justifyContent: "center", width: 32 },
  stepCopy: { flex: 1, gap: 2 },
  stepTitle: { fontSize: 11, fontWeight: "900" },
  stepCaption: { fontSize: 11, lineHeight: 16 },
  guideReassurance: { fontSize: 11, fontWeight: "700", lineHeight: 16, paddingLeft: 42 },
  summaryRow: { flexDirection: "row", gap: 10 },
  summaryCard: { borderWidth: 1, flex: 1, minHeight: 154, padding: 13 },
  summaryHeader: { alignItems: "center", flexDirection: "row", gap: 7 },
  summaryIcon: { alignItems: "center", borderRadius: 10, height: 34, justifyContent: "center", width: 34 },
  summaryLabel: { flex: 1, fontSize: 10, fontWeight: "800", lineHeight: 14 },
  summaryAmount: { fontSize: 18, fontWeight: "900", marginTop: 13 },
  summaryCount: { fontSize: 10, marginTop: 5 },
  summaryDescription: { fontSize: 9, lineHeight: 13, marginTop: 5 },
  summaryLoader: { alignSelf: "flex-start", marginTop: 18 },
  chipsScroll: { marginHorizontal: -14 },
  chipsContent: { gap: 8, paddingHorizontal: 14 },
  filterChip: { borderRadius: 999, borderWidth: 1, justifyContent: "center", minHeight: 44, paddingHorizontal: 14 },
  filterChipText: { fontSize: 11, fontWeight: "800" },
  listHeadingRow: { gap: 3, marginTop: 2 },
  listHeading: { fontSize: 15, fontWeight: "900" },
  listCaption: { fontSize: 10 },
  card: { borderLeftWidth: 4, borderWidth: 1, overflow: "hidden", paddingHorizontal: 12, paddingVertical: 10 },
  pressed: { opacity: 0.76 },
  cardTopRow: { alignItems: "center", flexDirection: "row", gap: 8, justifyContent: "space-between" },
  referenceRow: { alignItems: "center", flex: 1, flexDirection: "row", gap: 7, minWidth: 0 },
  marketplaceBadge: { borderRadius: 4, paddingHorizontal: 6, paddingVertical: 3 },
  marketplaceText: { color: "#ffffff", fontSize: 8, fontWeight: "900" },
  reference: { flex: 1, fontFamily: "monospace", fontSize: 9 },
  cardDate: { fontSize: 9 },
  cardMainRow: { alignItems: "center", flexDirection: "row", gap: 10, paddingVertical: 10 },
  productImage: { borderWidth: 1, height: 54, width: 54 },
  productImageFallback: { alignItems: "center", justifyContent: "center" },
  productTitle: { flex: 1, fontSize: 12, fontWeight: "700", lineHeight: 17 },
  amountColumn: { alignItems: "flex-end", maxWidth: 96, minWidth: 76 },
  cashbackAmount: { fontSize: 13, fontWeight: "900" },
  amountCaption: { fontSize: 9, marginTop: 3 },
  cardBottomRow: { alignItems: "center", flexDirection: "row", justifyContent: "space-between" },
  statusRow: { alignItems: "center", flexDirection: "row", gap: 6 },
  statusDot: { borderRadius: 4, height: 7, width: 7 },
  statusText: { fontSize: 10, fontWeight: "700" },
  stateCard: { alignItems: "center", flex: 1, gap: 10, justifyContent: "center", minHeight: 230, padding: 24 },
  stateTitle: { fontSize: 16, fontWeight: "900", textAlign: "center" },
  stateMessage: { fontSize: 12, lineHeight: 18, textAlign: "center" },
  emptyIcon: { alignItems: "center", borderRadius: 18, borderWidth: 1, height: 62, justifyContent: "center", width: 62 },
  primaryButton: { alignItems: "center", borderRadius: 12, flexDirection: "row", gap: 7, justifyContent: "center", minHeight: 44, paddingHorizontal: 20 },
  primaryButtonText: { color: "#ffffff", fontSize: 12, fontWeight: "900" },
  footer: { marginVertical: 18 },
  footerSpacer: { height: 18 },
  inlineNotice: { alignItems: "center", borderRadius: 12, borderWidth: 1, flexDirection: "row", gap: 9, padding: 11 },
  inlineNoticeText: { flex: 1, fontSize: 10, lineHeight: 15 },
  inlineRetry: { fontSize: 10, fontWeight: "900" }
});
