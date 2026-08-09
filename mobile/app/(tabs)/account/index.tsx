import { useState } from "react";
import { ScrollView, StyleSheet, Text, View } from "react-native";
import { useRouter } from "expo-router";
import { useSafeAreaInsets } from "react-native-safe-area-context";
import { ApiError } from "@/api/client";
import { useAuth } from "@/auth/AuthProvider";
import { ErrorState, LoadingState, OfflineState } from "@/components/AsyncState";
import { useAccount } from "@/features/account/api";
import { AccountButton, AccountCard, AccountHeader, AccountMenuRow, accountStyles } from "@/features/account/components";
import { colors, spacing } from "@/theme/tokens";

export default function AccountRoute() {
  const router = useRouter();
  const insets = useSafeAreaInsets();
  const { logout } = useAuth();
  const query = useAccount();
  const [loggingOut, setLoggingOut] = useState(false);

  if (query.isPending) return <LoadingState label="Đang tải tài khoản..." />;
  if (query.isError) {
    const props = { title: "Không thể tải tài khoản", message: query.error instanceof Error ? query.error.message : undefined, actionLabel: "Thử lại", onAction: () => void query.refetch() };
    return query.error instanceof ApiError && query.error.isNetworkError ? <OfflineState {...props} /> : <ErrorState {...props} />;
  }

  const account = query.data;
  async function signOut() {
    setLoggingOut(true);
    try {
      await logout();
    } finally {
      setLoggingOut(false);
    }
  }

  return (
    <ScrollView
      contentContainerStyle={[styles.content, { paddingTop: spacing.md + insets.top, paddingBottom: spacing.xl + insets.bottom }]}
      contentInsetAdjustmentBehavior="automatic"
    >
      <AccountHeader eyebrow="Thiết lập thành viên" title="Tài khoản" subtitle="Quản lý hồ sơ, bảo mật và các phiên đăng nhập dùng chung với mesale.vn." />
      <AccountCard>
        <View style={styles.identityRow}>
          <View style={styles.avatar}><Text style={styles.avatarText}>{account.name.slice(0, 1).toUpperCase()}</Text></View>
          <View style={styles.identityCopy}>
            <Text style={styles.name}>{account.name}</Text>
            <Text style={styles.email}>{account.email}</Text>
            <Text style={styles.memberCode}>ID #{account.id} · {account.email_verified ? "Email đã xác minh" : "Chưa xác minh email"}</Text>
          </View>
        </View>
        <View style={styles.statsRow}>
          <View style={styles.stat}><Text style={styles.statValue}>{account.stats.orders_total}</Text><Text style={styles.statLabel}>Đơn hàng</Text></View>
          <View style={styles.stat}><Text style={styles.statValue}>{account.stats.referrals_count}</Text><Text style={styles.statLabel}>Giới thiệu</Text></View>
          <View style={styles.stat}><Text style={styles.statValue}>{account.stats.withdrawals_pending}</Text><Text style={styles.statLabel}>Chờ rút</Text></View>
        </View>
      </AccountCard>
      <AccountCard>
        <AccountMenuRow title="Thông tin cá nhân" subtitle="Họ tên và số điện thoại" onPress={() => router.push("/(tabs)/account/profile")} />
        <View style={styles.divider} />
        <AccountMenuRow title="Đổi mật khẩu" subtitle="Thu hồi các phiên khác sau khi đổi" onPress={() => router.push("/(tabs)/account/password")} />
        <View style={styles.divider} />
        <AccountMenuRow title="Ngôn ngữ & tiền tệ" subtitle={`${account.preferences.locale.toUpperCase()} · ${account.preferences.currency}`} onPress={() => router.push("/(tabs)/account/preferences")} />
        <View style={styles.divider} />
        <AccountMenuRow title="Bảo mật 2 lớp" subtitle="Google Authenticator và OTP email" onPress={() => router.push("/(tabs)/account/security")} />
        <View style={styles.divider} />
        <AccountMenuRow title="Phiên đăng nhập" subtitle="Kiểm tra và thu hồi thiết bị" onPress={() => router.push("/(tabs)/account/sessions")} />
      </AccountCard>
      <AccountCard tone="danger">
        <AccountMenuRow title="Xóa tài khoản" subtitle="Xóa vĩnh viễn cùng tài khoản website" onPress={() => router.push("/(tabs)/account/delete")} />
      </AccountCard>
      <AccountButton label="Đăng xuất" loading={loggingOut} onPress={() => void signOut()} tone="secondary" />
      <Text style={accountStyles.body}>Dữ liệu ví, đơn hàng và tài khoản luôn do máy chủ Mesale quản lý. Ứng dụng không lưu database business riêng.</Text>
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  content: { backgroundColor: colors.background, gap: spacing.md, paddingHorizontal: spacing.md },
  identityRow: { alignItems: "center", flexDirection: "row", gap: spacing.md },
  avatar: { alignItems: "center", backgroundColor: colors.primary, borderRadius: 28, height: 56, justifyContent: "center", width: 56 },
  avatarText: { color: colors.surface, fontSize: 22, fontWeight: "900" },
  identityCopy: { flex: 1, gap: spacing.xs },
  name: { color: colors.text, fontSize: 18, fontWeight: "900" },
  email: { color: colors.mutedText, fontSize: 13 },
  memberCode: { color: colors.primary, fontSize: 11, fontWeight: "700" },
  statsRow: { flexDirection: "row", gap: spacing.sm },
  stat: { alignItems: "center", backgroundColor: "#f8fafc", borderRadius: 12, flex: 1, gap: spacing.xs, padding: spacing.sm },
  statValue: { color: colors.text, fontSize: 17, fontWeight: "900" },
  statLabel: { color: colors.mutedText, fontSize: 10, fontWeight: "700" },
  divider: { backgroundColor: colors.border, height: StyleSheet.hairlineWidth }
});
