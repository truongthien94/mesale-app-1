import { router, type Href } from "expo-router";
import { FlatList, Pressable, StyleSheet, Text, View } from "react-native";
import { useSafeAreaInsets } from "react-native-safe-area-context";
import { ScreenHeader } from "@/features/earn/ui";
import { getDeviceLocale } from "@/i18n";
import { colors, spacing } from "@/theme/tokens";

export default function EarnRoute() {
  const insets = useSafeAreaInsets();
  const isVietnamese = getDeviceLocale() === "vi";
  const items: Array<{ title: string; subtitle: string; marker: string; href: Href }> = isVietnamese ? [
    { title: "Giới thiệu", subtitle: "F1/F2 và hoa hồng", marker: "F1", href: "/earn/referrals" },
    { title: "Điểm danh", subtitle: "Duy trì chuỗi mỗi ngày", marker: "7D", href: "/earn/checkin" },
    { title: "Nhiệm vụ", subtitle: "Theo dõi và nhận thưởng", marker: "TS", href: "/earn/tasks" },
    { title: "Đổi quà", subtitle: "Quà số và quà vật lý", marker: "GF", href: "/earn/gifts" },
    { title: "Lịch sử quà", subtitle: "Theo dõi yêu cầu đổi quà", marker: "HS", href: "/earn/gift-history" },
    { title: "Giftcode", subtitle: "Nhập mã nhận thưởng", marker: "GC", href: "/earn/gift-code" }
  ] : [
    { title: "Referrals", subtitle: "F1/F2 network and commission", marker: "F1", href: "/earn/referrals" },
    { title: "Daily check-in", subtitle: "Keep your daily streak", marker: "7D", href: "/earn/checkin" },
    { title: "Tasks", subtitle: "Track and claim rewards", marker: "TS", href: "/earn/tasks" },
    { title: "Gifts", subtitle: "Digital and physical gifts", marker: "GF", href: "/earn/gifts" },
    { title: "Gift history", subtitle: "Track your redemptions", marker: "HS", href: "/earn/gift-history" },
    { title: "Gift code", subtitle: "Redeem a reward code", marker: "GC", href: "/earn/gift-code" }
  ];

  return (
    <FlatList
      columnWrapperStyle={styles.columns}
      contentContainerStyle={[styles.content, { paddingBottom: insets.bottom + spacing.lg }]}
      contentInsetAdjustmentBehavior="automatic"
      data={items}
      keyExtractor={(item) => item.title}
      ListHeaderComponent={
        <ScreenHeader
          eyebrow="Mesale Rewards"
          subtitle={isVietnamese ? "Tất cả quyền lợi thành viên trong một nơi." : "All member benefits in one place."}
          title={isVietnamese ? "Nhận thưởng" : "Earn"}
        />
      }
      numColumns={2}
      renderItem={({ item }) => (
        <Pressable
          accessibilityRole="button"
          onPress={() => router.push(item.href)}
          style={({ pressed }) => [styles.card, pressed && styles.pressed]}
        >
          <View style={styles.marker}><Text style={styles.markerText}>{item.marker}</Text></View>
          <Text style={styles.title}>{item.title}</Text>
          <Text style={styles.subtitle}>{item.subtitle}</Text>
        </Pressable>
      )}
      style={styles.screen}
    />
  );
}

const styles = StyleSheet.create({
  screen: { backgroundColor: colors.background, flex: 1 },
  content: { gap: spacing.md, padding: spacing.md },
  columns: { gap: spacing.md },
  card: { backgroundColor: colors.surface, borderColor: colors.border, borderRadius: 20, borderWidth: 1, flex: 1, gap: spacing.sm, minHeight: 164, padding: spacing.md, shadowColor: "#0f172a", shadowOffset: { width: 0, height: 5 }, shadowOpacity: 0.05, shadowRadius: 12, elevation: 2 },
  pressed: { opacity: 0.78 },
  marker: { alignItems: "center", backgroundColor: "#fff7ed", borderRadius: 14, height: 44, justifyContent: "center", width: 44 },
  markerText: { color: colors.primary, fontSize: 14, fontWeight: "900" },
  title: { color: colors.text, fontSize: 16, fontWeight: "900" },
  subtitle: { color: colors.mutedText, fontSize: 13, lineHeight: 18 }
});
