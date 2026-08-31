import { useLocalSearchParams } from "expo-router";
import { Image, ScrollView, StyleSheet, Text, View } from "react-native";
import { useSafeAreaInsets } from "react-native-safe-area-context";
import { IosPayoutRouteGuard } from "@/components/IosPayoutRouteGuard";
import { ErrorState, LoadingState } from "@/components/AsyncState";
import { Card, PageFrame, QueryFailure, SectionTitle, StatusBadge } from "@/features/wallet/components";
import { formatDate, formatVnd } from "@/features/wallet/format";
import { useOrder } from "@/features/wallet/api";
import { colors, spacing, theme } from "@/theme/tokens";

export default function OrderDetailScreen() {
  return <IosPayoutRouteGuard><OrderDetailContent /></IosPayoutRouteGuard>;
}

function OrderDetailContent() {
  const params = useLocalSearchParams<{ id?: string }>();
  const id = Number(params.id);
  const insets = useSafeAreaInsets();
  const query = useOrder(id);

  if (!Number.isInteger(id) || id <= 0) return <ErrorState title="Đơn hàng không hợp lệ" message="Không thể xác định mã đơn hàng cần mở." />;
  if (query.isPending) return <LoadingState label="Đang tải chi tiết đơn..." />;
  if (query.isError) return <QueryFailure error={query.error} onRetry={() => void query.refetch()} />;

  const order = query.data;
  return (
    <PageFrame>
      <ScrollView
        contentContainerStyle={[styles.content, { paddingBottom: spacing.xl + insets.bottom }]}
        contentInsetAdjustmentBehavior="automatic"
      >
        <Card style={styles.hero}>
          {order.product_image?.startsWith("http") ? (
            <Image accessibilityIgnoresInvertColors source={{ uri: order.product_image }} style={styles.image} />
          ) : null}
          <View style={styles.heroCopy}>
            <Text style={styles.platform}>{order.platform?.toUpperCase() || "MARKETPLACE"}</Text>
            <Text style={styles.title}>{order.product_name || "Đơn hàng hoàn tiền"}</Text>
            {order.shop_name ? <Text style={styles.shop}>{order.shop_name}</Text> : null}
            <StatusBadge status={order.status} />
          </View>
        </Card>

        <Card>
          <SectionTitle title="Giá trị đơn hàng" />
          <View style={styles.rows}>
            <DetailRow label="Giá sản phẩm" value={formatVnd(order.original_price)} />
            <DetailRow label="Giá trị đối soát từ sàn" value={formatVnd(order.commission_amount)} />
            <DetailRow label="Tỷ lệ hoàn tiền" value={`${order.cashback_rate}%`} />
            <DetailRow emphasize label="Quyền lợi hoàn tiền" value={formatVnd(order.cashback_amount)} />
          </View>
        </Card>

        <Card>
          <SectionTitle title="Đối soát" />
          <View style={styles.rows}>
            <DetailRow label="Mã đơn hàng" value={order.order_id || "--"} />
            <DetailRow label="Mã giao dịch" value={order.trans_id || "--"} />
            <DetailRow label="Ngày ghi nhận" value={formatDate(order.created_at)} />
            <DetailRow label="Ngày duyệt" value={formatDate(order.approved_at)} />
          </View>
          {order.rejected_reason ? (
            <View accessibilityRole="alert" style={styles.rejectedReason}>
              <Text style={styles.rejectedTitle}>Lý do từ chối</Text>
              <Text style={styles.rejectedText}>{order.rejected_reason}</Text>
            </View>
          ) : null}
        </Card>
      </ScrollView>
    </PageFrame>
  );
}

function DetailRow({ label, value, emphasize = false }: { label: string; value: string; emphasize?: boolean }) {
  return (
    <View style={styles.row}>
      <Text style={styles.rowLabel}>{label}</Text>
      <Text selectable style={[styles.rowValue, emphasize && styles.rowValueEmphasized]}>{value}</Text>
    </View>
  );
}

const styles = StyleSheet.create({
  content: {
    gap: spacing.md,
    padding: spacing.md
  },
  hero: {
    flexDirection: "row",
    gap: spacing.md
  },
  image: {
    backgroundColor: "#fff7ed",
    borderRadius: theme.radius.md,
    height: 88,
    width: 88
  },
  heroCopy: {
    flex: 1,
    gap: spacing.sm
  },
  platform: {
    color: colors.primary,
    fontSize: 10,
    fontWeight: "900",
    letterSpacing: 0.8
  },
  title: {
    color: colors.text,
    fontSize: 16,
    fontWeight: "800",
    lineHeight: 22
  },
  shop: {
    color: colors.mutedText,
    fontSize: 12
  },
  rows: {
    gap: spacing.md,
    marginTop: spacing.lg
  },
  row: {
    alignItems: "flex-start",
    borderBottomColor: colors.border,
    borderBottomWidth: StyleSheet.hairlineWidth,
    flexDirection: "row",
    gap: spacing.md,
    justifyContent: "space-between",
    paddingBottom: spacing.md
  },
  rowLabel: {
    color: colors.mutedText,
    flex: 1,
    fontSize: 12
  },
  rowValue: {
    color: colors.text,
    flex: 1,
    fontSize: 12,
    fontWeight: "700",
    textAlign: "right"
  },
  rowValueEmphasized: {
    color: colors.primary,
    fontSize: 15,
    fontWeight: "900"
  },
  rejectedReason: {
    backgroundColor: "#fff1f2",
    borderColor: "#fecdd3",
    borderRadius: theme.radius.md,
    borderWidth: 1,
    gap: spacing.xs,
    marginTop: spacing.md,
    padding: spacing.md
  },
  rejectedTitle: {
    color: "#be123c",
    fontSize: 12,
    fontWeight: "800"
  },
  rejectedText: {
    color: "#9f1239",
    fontSize: 12,
    lineHeight: 18
  }
});
