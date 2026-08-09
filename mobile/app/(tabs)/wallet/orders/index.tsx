import { useCallback } from "react";
import { useRouter } from "expo-router";
import { FlatList, Image, Pressable, StyleSheet, Text, View, type ListRenderItemInfo } from "react-native";
import { useSafeAreaInsets } from "react-native-safe-area-context";
import { EmptyState, LoadingState } from "@/components/AsyncState";
import { ListFooter, PageFrame, QueryFailure, StatusBadge } from "@/features/wallet/components";
import { formatDate, formatVnd } from "@/features/wallet/format";
import { useOrders } from "@/features/wallet/api";
import type { Order } from "@/features/wallet/types";
import { colors, spacing, theme } from "@/theme/tokens";

export default function OrdersScreen() {
  const router = useRouter();
  const insets = useSafeAreaInsets();
  const query = useOrders();
  const orders = query.data?.pages.flatMap((page) => page.items) ?? [];

  const renderItem = useCallback(({ item }: ListRenderItemInfo<Order>) => (
    <Pressable
      accessibilityRole="button"
      accessibilityLabel={`Xem đơn ${item.order_id ?? item.id}`}
      onPress={() => router.push(`/(tabs)/wallet/orders/${item.id}`)}
      style={({ pressed }) => [styles.card, pressed && styles.pressed]}
    >
      {item.product_image?.startsWith("http") ? (
        <Image accessibilityIgnoresInvertColors source={{ uri: item.product_image }} style={styles.image} />
      ) : (
        <View style={[styles.image, styles.imageFallback]}><Text style={styles.imageFallbackText}>M</Text></View>
      )}
      <View style={styles.copy}>
        <View style={styles.titleRow}>
          <Text numberOfLines={2} style={styles.title}>{item.product_name || "Đơn hàng hoàn tiền"}</Text>
          <Text style={styles.cashback}>+{formatVnd(item.cashback_amount)}</Text>
        </View>
        <Text numberOfLines={1} style={styles.meta}>
          {item.platform?.toUpperCase() || "MARKETPLACE"} · #{item.order_id || item.id}
        </Text>
        <View style={styles.footer}>
          <StatusBadge status={item.status} />
          <Text style={styles.date}>{formatDate(item.created_at, false)}</Text>
        </View>
      </View>
    </Pressable>
  ), [router]);

  if (query.isPending) return <LoadingState label="Đang tải đơn hàng..." />;
  if (query.isError && orders.length === 0) return <QueryFailure error={query.error} onRetry={() => void query.refetch()} />;

  return (
    <PageFrame>
      <FlatList
        contentContainerStyle={[styles.content, { paddingBottom: spacing.xl + insets.bottom }, orders.length === 0 && styles.emptyContent]}
        contentInsetAdjustmentBehavior="automatic"
        data={orders}
        keyExtractor={(item) => String(item.id)}
        ListEmptyComponent={<EmptyState title="Chưa có đơn hoàn tiền" message="Đơn phát sinh từ liên kết cashback sẽ xuất hiện tại đây." />}
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
    gap: spacing.sm,
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
    flexDirection: "row",
    gap: spacing.md,
    padding: spacing.md
  },
  pressed: {
    opacity: 0.75
  },
  image: {
    backgroundColor: "#fff7ed",
    borderRadius: theme.radius.md,
    height: 64,
    width: 64
  },
  imageFallback: {
    alignItems: "center",
    justifyContent: "center"
  },
  imageFallbackText: {
    color: colors.primary,
    fontSize: 22,
    fontWeight: "900"
  },
  copy: {
    flex: 1,
    gap: spacing.sm,
    minWidth: 0
  },
  titleRow: {
    alignItems: "flex-start",
    flexDirection: "row",
    gap: spacing.sm,
    justifyContent: "space-between"
  },
  title: {
    color: colors.text,
    flex: 1,
    fontSize: 13,
    fontWeight: "800",
    lineHeight: 18
  },
  cashback: {
    color: colors.primary,
    fontSize: 13,
    fontWeight: "900"
  },
  meta: {
    color: colors.mutedText,
    fontSize: 10,
    fontWeight: "700"
  },
  footer: {
    alignItems: "center",
    flexDirection: "row",
    justifyContent: "space-between"
  },
  date: {
    color: colors.mutedText,
    fontSize: 10
  }
});
