import { useState } from "react";
import { useInfiniteQuery } from "@tanstack/react-query";
import { router } from "expo-router";
import * as Clipboard from "expo-clipboard";
import { StatusBar } from "expo-status-bar";
import {
  CheckCircle2,
  ChevronLeft,
  Copy,
  ExternalLink,
  ShoppingBag
} from "lucide-react-native";
import {
  Alert,
  FlatList,
  Image,
  Linking,
  Pressable,
  RefreshControl,
  StyleSheet,
  Text,
  View
} from "react-native";
import { ApiError } from "@/api/client";
import { EmptyState, ErrorState, LoadingState, OfflineState } from "@/components/AsyncState";
import { fetchCouponPage } from "@/features/coupons/api";
import { formatAccountMoney } from "@/features/home/format";
import type { Coupon } from "@/features/home/types";
import { useTheme } from "@/theme/ThemeProvider";
import type { Theme } from "@/theme/tokens";
import { useSafeAreaInsets } from "react-native-safe-area-context";

function platformLabel(platform: Coupon["platform"]): string {
  if (platform === "shopee") return "Shopee";
  if (platform === "tiktok") return "TikTok Shop";
  return "Lazada";
}

function safeRemoteUri(value: string | null): string | null {
  if (!value) return null;
  try {
    const url = new URL(value);
    return url.protocol === "https:" ? url.toString() : null;
  } catch {
    return null;
  }
}

function formatExpiry(value: string | null): string {
  if (!value) return "HSD chưa xác định";
  const timestamp = Date.parse(value);
  if (!Number.isFinite(timestamp)) return "HSD chưa xác định";
  return `HSD ${new Intl.DateTimeFormat("vi-VN", { day: "2-digit", month: "2-digit" }).format(timestamp)}`;
}

function formatCategory(value: string | null): string {
  if (!value) return "Mã giảm giá";
  return value.toLowerCase().includes("toàn") ? "Áp dụng nhiều shop" : `Áp dụng ${value}`;
}

function fullScreenBack() {
  if (router.canGoBack()) router.back();
  else router.replace("/(tabs)/home");
}

function CouponCard({ coupon, colors, radius, scheme }: { coupon: Coupon; colors: Theme["colors"]; radius: Theme["radius"]; scheme: Theme["scheme"] }) {
  const imageUri = safeRemoteUri(coupon.imageUrl);
  const redirectUri = safeRemoteUri(coupon.redirectLink);
  const platform = platformLabel(coupon.platform);

  async function copyCode() {
    await Clipboard.setStringAsync(coupon.code);
    Alert.alert("Đã sao chép mã", `${coupon.code} đã được lưu vào bộ nhớ tạm.`);
  }

  async function openCoupon() {
    await Clipboard.setStringAsync(coupon.code);
    if (!redirectUri) {
      Alert.alert("Đã sao chép mã", "Liên kết mua sắm của mã này chưa sẵn sàng.");
      return;
    }
    try {
      await Linking.openURL(redirectUri);
    } catch {
      Alert.alert("Không thể mở liên kết", "Mã đã được sao chép. Bạn có thể mở sàn mua sắm và dán mã.");
    }
  }

  return (
    <View style={[styles.couponCard, { backgroundColor: colors.surface, borderColor: colors.border, borderRadius: radius.lg }]}>
      <View style={styles.couponTopRow}>
        <View style={[styles.platformIcon, { backgroundColor: coupon.platform === "shopee" ? "#ee5b3d" : "#3b82f6" }]}>
          {imageUri ? <Image accessibilityIgnoresInvertColors source={{ uri: imageUri }} style={styles.platformImage} /> : <ShoppingBag color="#ffffff" size={22} />}
        </View>
        <View style={styles.couponCopy}>
          <View style={styles.categoryRow}>
            <CheckCircle2 color="#16a34a" fill="#dcfce7" size={16} />
            <Text numberOfLines={1} style={[styles.categoryText, { color: scheme === "dark" ? "#86efac" : "#15803d" }]}>{formatCategory(coupon.category)}</Text>
          </View>
          <Text numberOfLines={2} style={[styles.couponTitle, { color: colors.text }]}>{coupon.title}</Text>
          <Text numberOfLines={1} style={[styles.couponMeta, { color: colors.mutedText }]}>
            <Text style={styles.platformText}>{platform}</Text>{coupon.minSpend > 0 ? ` · Đơn từ ${formatAccountMoney(coupon.minSpend, "vi")}` : ""} · {formatExpiry(coupon.expiredAt)}
          </Text>
        </View>
      </View>

      {coupon.description ? <Text numberOfLines={2} style={[styles.description, { color: colors.mutedText }]}>{coupon.description}</Text> : null}

      <View style={styles.couponActions}>
        <Pressable accessibilityLabel={`Sao chép mã ${coupon.code}`} accessibilityRole="button" onPress={() => void copyCode()} style={({ pressed }) => [styles.codeButton, { backgroundColor: scheme === "dark" ? "#111c2c" : "#f3f8ff", borderColor: scheme === "dark" ? "#334155" : "#bfdbfe" }, pressed && styles.pressed]}>
          <Copy color="#3b82f6" size={18} />
          <Text numberOfLines={1} style={[styles.codeText, { color: colors.text }]}>{coupon.code}</Text>
        </Pressable>
        <Pressable accessibilityLabel={`Mở liên kết cho mã ${coupon.code}`} accessibilityRole="button" onPress={() => void openCoupon()} style={({ pressed }) => [styles.buyButton, pressed && styles.pressed]}>
          <Text style={styles.buyText}>Mua</Text>
          <ExternalLink color="#ffffff" size={16} />
        </Pressable>
      </View>
    </View>
  );
}

function CategoryChip({ count, label, onPress, selected, colors, scheme }: { count?: number; label: string; onPress: () => void; selected: boolean; colors: Theme["colors"]; scheme: Theme["scheme"] }) {
  return (
    <Pressable accessibilityRole="tab" accessibilityState={{ selected }} onPress={onPress} style={({ pressed }) => [styles.categoryChip, { backgroundColor: selected ? "#3b82f6" : colors.surface, borderColor: selected ? "#3b82f6" : colors.border }, pressed && styles.pressed]}>
      <Text style={[styles.categoryChipText, { color: selected ? "#ffffff" : colors.text }]}>{label}</Text>
      {typeof count === "number" ? <Text style={[styles.categoryCount, { backgroundColor: selected ? "rgba(255,255,255,0.22)" : scheme === "dark" ? "#334155" : "#eef2f7", color: selected ? "#ffffff" : colors.mutedText }]}>{count}</Text> : null}
    </Pressable>
  );
}

export default function CouponsScreen() {
  const insets = useSafeAreaInsets();
  const { colors, radius, scheme } = useTheme();
  const [category, setCategory] = useState<string | null>(null);
  const query = useInfiniteQuery({
    queryKey: ["coupons", category ?? "all"],
    initialPageParam: 1,
    queryFn: ({ pageParam, signal }) => fetchCouponPage({ category: category ?? undefined, page: pageParam }, signal),
    getNextPageParam: (page) => page.pagination.currentPage < page.pagination.lastPage ? page.pagination.currentPage + 1 : undefined
  });

  const firstPage = query.data?.pages[0];
  const coupons = query.data?.pages.flatMap((page) => page.items) ?? [];
  const categories = Array.from(new Set(firstPage?.categories ?? []));
  const retry = () => void query.refetch();

  if (query.isPending) return <LoadingState label="Đang tải mã giảm giá..." />;
  if (query.isError) {
    const props = { actionLabel: "Thử lại", message: query.error instanceof Error ? query.error.message : "Vui lòng thử lại sau.", onAction: retry, title: "Không thể tải mã giảm giá" };
    return query.error instanceof ApiError && query.error.isNetworkError ? <OfflineState {...props} title="Bạn đang ngoại tuyến" /> : <ErrorState {...props} />;
  }

  return (
    <View
      style={[styles.screen, { backgroundColor: colors.background }]}
    >
      <StatusBar style={scheme === "dark" ? "light" : "dark"} />
      <FlatList
        contentContainerStyle={{ paddingBottom: insets.bottom + 24, paddingHorizontal: 16, paddingTop: insets.top + 8 }}
        data={coupons}
        keyExtractor={(item) => String(item.id)}
        ListEmptyComponent={<EmptyState actionLabel={category ? "Xóa bộ lọc" : undefined} message={category ? "Không có mã phù hợp với bộ lọc này." : "Máy chủ chưa có mã giảm giá đang hiệu lực."} onAction={category ? () => setCategory(null) : undefined} title="Chưa có mã giảm giá" />}
        ListFooterComponent={query.isFetchingNextPage ? <LoadingState label="Đang tải thêm..." style={styles.footerLoading} /> : <View style={{ height: 16 }} />}
        ListHeaderComponent={<View style={styles.headerStack}>
          <View style={styles.headerRow}>
            <Pressable accessibilityLabel="Quay lại" accessibilityRole="button" hitSlop={8} onPress={fullScreenBack} style={styles.backButton}><ChevronLeft color={colors.text} size={27} /></Pressable>
            <View style={styles.headerCopy}><Text accessibilityRole="header" style={[styles.title, { color: colors.text }]}>Săn mã giảm giá</Text><Text style={[styles.subtitle, { color: colors.mutedText }]}>Mã toàn sàn áp dụng nhiều shop theo điều kiện · mã có tên shop chỉ dùng ở shop đó</Text></View>
          </View>
          <FlatList horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.chipsContent} data={[null, ...categories]} keyExtractor={(item) => item ?? "all"} renderItem={({ item }) => <CategoryChip colors={colors} count={item === category ? firstPage?.pagination.total : item === null && category === null ? firstPage?.pagination.total : undefined} label={item ?? "Tất cả"} onPress={() => setCategory(item)} scheme={scheme} selected={item === category} />} />
        </View>}
        onEndReached={() => { if (query.hasNextPage && !query.isFetchingNextPage) void query.fetchNextPage(); }}
        onEndReachedThreshold={0.4}
        onRefresh={retry}
        refreshControl={<RefreshControl onRefresh={retry} refreshing={query.isRefetching && !query.isFetchingNextPage} tintColor={colors.primary} />}
        renderItem={({ item }) => <CouponCard colors={colors} coupon={item} radius={radius} scheme={scheme} />}
        refreshing={query.isRefetching && !query.isFetchingNextPage}
        showsVerticalScrollIndicator={false}
        style={styles.list}
      />
    </View>
  );
}

const styles = StyleSheet.create({
  screen: { flex: 1 },
  list: { flex: 1 },
  headerStack: { gap: 14, marginBottom: 6 },
  headerRow: { alignItems: "flex-start", flexDirection: "row", gap: 7 },
  backButton: { alignItems: "center", height: 42, justifyContent: "center", width: 34 },
  headerCopy: { flex: 1, gap: 4, minWidth: 0 },
  title: { fontSize: 24, fontWeight: "900", letterSpacing: -0.7 },
  subtitle: { fontSize: 13, lineHeight: 19 },
  chipsContent: { gap: 8, paddingRight: 16 },
  categoryChip: { alignItems: "center", borderRadius: 999, borderWidth: 1, flexDirection: "row", gap: 7, minHeight: 44, paddingHorizontal: 16 },
  categoryChipText: { fontSize: 14, fontWeight: "900" },
  categoryCount: { borderRadius: 999, fontSize: 12, fontWeight: "900", overflow: "hidden", paddingHorizontal: 8, paddingVertical: 3 },
  couponCard: { borderWidth: 1, gap: 12, marginBottom: 12, padding: 14 },
  couponTopRow: { flexDirection: "row", gap: 12 },
  platformIcon: { alignItems: "center", borderRadius: 14, height: 54, justifyContent: "center", overflow: "hidden", width: 54 },
  platformImage: { height: "100%", width: "100%" },
  couponCopy: { flex: 1, gap: 4, minWidth: 0 },
  categoryRow: { alignItems: "center", flexDirection: "row", gap: 5 },
  categoryText: { flex: 1, fontSize: 13, fontWeight: "900" },
  couponTitle: { fontSize: 17, fontWeight: "900", lineHeight: 22 },
  couponMeta: { fontSize: 12, lineHeight: 18 },
  platformText: { color: "#ee4d2d", fontWeight: "900" },
  description: { fontSize: 12, lineHeight: 18 },
  couponActions: { alignItems: "center", flexDirection: "row", gap: 10 },
  codeButton: { alignItems: "center", borderRadius: 12, borderStyle: "dashed", borderWidth: 1.5, flex: 1, flexDirection: "row", gap: 8, minHeight: 50, minWidth: 0, paddingHorizontal: 12 },
  codeText: { flex: 1, fontFamily: "monospace", fontSize: 13, fontWeight: "900" },
  buyButton: { alignItems: "center", backgroundColor: "#3b82f6", borderRadius: 12, flexDirection: "row", gap: 6, justifyContent: "center", minHeight: 50, paddingHorizontal: 17 },
  buyText: { color: "#ffffff", fontSize: 15, fontWeight: "900" },
  footerLoading: { minHeight: 72 },
  pressed: { opacity: 0.76 }
});
