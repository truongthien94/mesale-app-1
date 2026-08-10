import { useQuery } from "@tanstack/react-query";
import { router, type Href } from "expo-router";
import {
  ArrowLeftRight,
  Banknote,
  Bell,
  CalendarCheck,
  Gift,
  ListChecks,
  LogOut,
  Menu,
  Monitor,
  Moon,
  Receipt,
  Share2,
  Sun,
  UserCog,
  Wallet,
  type LucideIcon
} from "lucide-react-native";
import { useState } from "react";
import { ActivityIndicator, Modal, Pressable, ScrollView, StyleSheet, Switch, Text, View } from "react-native";
import { useSafeAreaInsets } from "react-native-safe-area-context";
import { useAuth } from "@/auth/AuthProvider";
import { useAccount } from "@/features/account/api";
import { fetchTasks } from "@/features/earn/api";
import { fetchUnreadCount } from "@/features/notifications/api";
import { useMoreSheetStore } from "@/features/navigation/moreStore";
import { getDeviceLocale } from "@/i18n";
import { useTheme } from "@/theme/ThemeProvider";
import type { ThemePreference } from "@/theme/themePreference";

type MoreItem = {
  label: string;
  href: Href;
  icon: LucideIcon;
  badge?: number;
};

const MORE_ROUTES: Array<{ labelVi: string; labelEn: string; href: Href; icon: LucideIcon; badge?: "notifications" | "tasks" }> = [
  { labelVi: "Ví của tôi", labelEn: "My wallet", href: "/(tabs)/wallet", icon: Wallet },
  { labelVi: "Lịch sử hoàn tiền", labelEn: "Cashback history", href: "/(tabs)/wallet/orders", icon: Receipt },
  { labelVi: "Điểm danh nhận xu", labelEn: "Daily check-in", href: "/(tabs)/earn/checkin", icon: CalendarCheck },
  { labelVi: "Tiếp thị liên kết", labelEn: "Affiliate referrals", href: "/(tabs)/earn/referrals", icon: Share2 },
  { labelVi: "Yêu cầu rút tiền", labelEn: "Withdrawal requests", href: "/(tabs)/wallet/withdrawals", icon: Banknote },
  { labelVi: "Đổi quà tặng", labelEn: "Redeem gifts", href: "/(tabs)/earn/gifts", icon: Gift },
  { labelVi: "Nhiệm vụ nhận thưởng", labelEn: "Reward tasks", href: "/(tabs)/earn/tasks", icon: ListChecks, badge: "tasks" },
  { labelVi: "Thiết lập tài khoản", labelEn: "Account settings", href: "/(tabs)/account/profile", icon: UserCog },
  { labelVi: "Thông báo", labelEn: "Notifications", href: "/(tabs)/inbox", icon: Bell, badge: "notifications" },
  { labelVi: "Biến động số dư", labelEn: "Balance activity", href: "/(tabs)/wallet/balance-logs", icon: ArrowLeftRight }
];

export function MoreSheet() {
  const insets = useSafeAreaInsets();
  const isOpen = useMoreSheetStore((state) => state.isOpen);
  const close = useMoreSheetStore((state) => state.close);
  const { logout, user } = useAuth();
  const { colors, preference, setPreference } = useTheme();
  const accountQuery = useAccount();
  const unreadQuery = useQuery({
    queryKey: ["notifications", "unread-count"],
    queryFn: ({ signal }) => fetchUnreadCount(signal),
    enabled: isOpen
  });
  const tasksQuery = useQuery({
    queryKey: ["earn", "tasks"],
    queryFn: ({ signal }) => fetchTasks(signal),
    enabled: isOpen
  });
  const [loggingOut, setLoggingOut] = useState(false);
  const vi = getDeviceLocale() === "vi";
  const account = accountQuery.data;
  const displayName = account?.name || user?.name || "Mesale";
  const displayEmail = account?.email || user?.email || (vi ? "Thành viên Mesale" : "Mesale member");
  const referralCode = account?.referral_code ?? user?.referral_code ?? null;
  const notificationCount = unreadQuery.data?.unread_total ?? 0;
  const taskCount = tasksQuery.data?.stats.completed ?? 0;
  const items: MoreItem[] = MORE_ROUTES.map((item) => ({
    ...item,
    label: vi ? item.labelVi : item.labelEn,
    badge: item.badge === "notifications" ? notificationCount : item.badge === "tasks" ? taskCount : undefined
  }));
  const preferenceOptions: Array<{ value: ThemePreference; label: string; icon: LucideIcon }> = [
    { value: "system", label: vi ? "Hệ thống" : "System", icon: Monitor },
    { value: "light", label: vi ? "Sáng" : "Light", icon: Sun },
    { value: "dark", label: vi ? "Tối" : "Dark", icon: Moon }
  ];

  function navigateTo(href: Href) {
    close();
    router.push(href);
  }

  async function signOut() {
    setLoggingOut(true);
    close();
    try {
      await logout();
    } finally {
      setLoggingOut(false);
    }
  }

  return (
    <Modal animationType="slide" onRequestClose={close} transparent visible={isOpen}>
      <Pressable onPress={close} style={styles.modalBackdrop}>
        <Pressable
          accessibilityViewIsModal
          onPress={() => undefined}
          style={[styles.modalCard, { backgroundColor: colors.surface, paddingBottom: insets.bottom + 24 }]}
        >
          <View style={[styles.modalHandle, { backgroundColor: colors.border }]} />
          <ScrollView contentContainerStyle={styles.content} showsVerticalScrollIndicator={false}>
            <View style={styles.identityRow}>
              <View style={[styles.avatar, { backgroundColor: colors.primary }]}>
                <Text style={[styles.avatarText, { color: colors.surface }]}>{displayName.slice(0, 1).toUpperCase()}</Text>
              </View>
              <View style={styles.identityCopy}>
                <Text numberOfLines={1} style={[styles.name, { color: colors.text }]}>{displayName}</Text>
                <Text numberOfLines={1} style={[styles.secondary, { color: colors.mutedText }]}>{displayEmail}</Text>
                {referralCode ? <Text style={[styles.referral, { color: colors.primary }]}>Mã giới thiệu: {referralCode}</Text> : null}
              </View>
            </View>

            <View style={[styles.divider, { backgroundColor: colors.border }]} />
            <View style={styles.navigationList}>
              {items.map((item) => {
                const Icon = item.icon;
                return (
                  <Pressable
                    accessibilityRole="button"
                    accessibilityLabel={item.label}
                    key={String(item.href)}
                    onPress={() => navigateTo(item.href)}
                    style={({ pressed }) => [styles.navigationRow, pressed && styles.pressed]}
                  >
                    <Icon color={colors.mutedText} size={20} />
                    <Text style={[styles.navigationLabel, { color: colors.text }]}>{item.label}</Text>
                    {item.badge ? <View style={[styles.badge, { backgroundColor: colors.primary }]}><Text style={[styles.badgeText, { color: colors.surface }]}>{item.badge > 99 ? "99+" : item.badge}</Text></View> : null}
                  </Pressable>
                );
              })}
            </View>

            <View style={[styles.divider, { backgroundColor: colors.border }]} />
            <View style={styles.themeSection}>
              <View style={styles.themeHeader}>
                <View style={styles.themeTitleRow}>
                  <Menu color={colors.mutedText} size={20} />
                  <Text style={[styles.navigationLabel, { color: colors.text }]}>{vi ? "Chế độ hiển thị" : "Appearance"}</Text>
                </View>
                <Switch
                  accessibilityLabel={vi ? "Chế độ tối" : "Dark mode"}
                  onValueChange={(value) => setPreference(value ? "dark" : "light")}
                  value={preference === "dark"}
                  trackColor={{ false: colors.border, true: colors.primary }}
                  thumbColor={colors.surface}
                />
              </View>
              <View style={styles.preferenceRow}>
                {preferenceOptions.map((option) => {
                  const Icon = option.icon;
                  const selected = preference === option.value;
                  return (
                    <Pressable
                      accessibilityRole="button"
                      accessibilityState={{ selected }}
                      key={option.value}
                      onPress={() => setPreference(option.value)}
                      style={[styles.preferenceOption, { borderColor: selected ? colors.primary : colors.border, backgroundColor: selected ? `${colors.primary}18` : colors.surface }]}
                    >
                      <Icon color={selected ? colors.primary : colors.mutedText} size={16} />
                      <Text style={[styles.preferenceLabel, { color: selected ? colors.primary : colors.mutedText }]}>{option.label}</Text>
                    </Pressable>
                  );
                })}
              </View>
            </View>

            <View style={[styles.divider, { backgroundColor: colors.border }]} />
            <Pressable
              accessibilityRole="button"
              accessibilityLabel={vi ? "Đăng xuất" : "Log out"}
              disabled={loggingOut}
              onPress={() => void signOut()}
              style={({ pressed }) => [styles.logoutButton, { borderColor: colors.border }, pressed && styles.pressed]}
            >
              {loggingOut ? <ActivityIndicator color={colors.danger} /> : <LogOut color={colors.danger} size={20} />}
              <Text style={[styles.logoutText, { color: colors.danger }]}>{vi ? "Đăng xuất" : "Log out"}</Text>
            </Pressable>
          </ScrollView>
        </Pressable>
      </Pressable>
    </Modal>
  );
}

const styles = StyleSheet.create({
  modalBackdrop: { backgroundColor: "rgba(15, 23, 42, 0.45)", flex: 1, justifyContent: "flex-end" },
  modalCard: { borderTopLeftRadius: 24, borderTopRightRadius: 24, gap: 16, maxHeight: "85%", padding: 24 },
  modalHandle: { alignSelf: "center", borderRadius: 999, height: 5, width: 48 },
  content: { gap: 16 },
  identityRow: { alignItems: "center", flexDirection: "row", gap: 16 },
  avatar: { alignItems: "center", borderRadius: 28, height: 56, justifyContent: "center", width: 56 },
  avatarText: { fontSize: 22, fontWeight: "900" },
  identityCopy: { flex: 1, gap: 4 },
  name: { fontSize: 18, fontWeight: "900" },
  secondary: { fontSize: 13 },
  referral: { fontSize: 11, fontWeight: "800" },
  divider: { height: StyleSheet.hairlineWidth },
  navigationList: { gap: 4 },
  navigationRow: { alignItems: "center", borderRadius: 14, flexDirection: "row", gap: 14, minHeight: 48, paddingHorizontal: 8 },
  navigationLabel: { flex: 1, fontSize: 14, fontWeight: "700" },
  badge: { alignItems: "center", borderRadius: 999, minWidth: 22, paddingHorizontal: 6, paddingVertical: 2 },
  badgeText: { fontSize: 11, fontWeight: "900", textAlign: "center" },
  pressed: { opacity: 0.7 },
  themeSection: { gap: 12 },
  themeHeader: { alignItems: "center", flexDirection: "row", justifyContent: "space-between" },
  themeTitleRow: { alignItems: "center", flexDirection: "row", gap: 14 },
  preferenceRow: { flexDirection: "row", gap: 8 },
  preferenceOption: { alignItems: "center", borderRadius: 12, borderWidth: 1, flex: 1, flexDirection: "row", gap: 6, justifyContent: "center", minHeight: 40, paddingHorizontal: 6 },
  preferenceLabel: { fontSize: 11, fontWeight: "800" },
  logoutButton: { alignItems: "center", borderRadius: 14, borderWidth: 1, flexDirection: "row", gap: 12, justifyContent: "center", minHeight: 48 },
  logoutText: { fontSize: 14, fontWeight: "900" }
});
