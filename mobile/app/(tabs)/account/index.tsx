import { useRef, useState, type ComponentType } from "react";
import { useQuery } from "@tanstack/react-query";
import { LinearGradient } from "expo-linear-gradient";
import { useRouter, type Href } from "expo-router";
import {
  ArrowDownCircle,
  Bell,
  CalendarCheck,
  ChevronRight,
  CircleHelp,
  CreditCard,
  FileText,
  Gift,
  History,
  Languages,
  Lightbulb,
  ListChecks,
  LogOut,
  ShieldCheck,
  Sparkles,
  SunMoon,
  Tag,
  Trash2,
  UserRound,
  UsersRound
} from "lucide-react-native";
import {
  ActivityIndicator,
  Alert,
  Image,
  Linking,
  Pressable,
  RefreshControl,
  ScrollView,
  StyleSheet,
  Text,
  View
} from "react-native";
import { useSafeAreaInsets } from "react-native-safe-area-context";
import { ApiError } from "@/api/client";
import { useAuth } from "@/auth/AuthProvider";
import { ErrorState, LoadingState, OfflineState } from "@/components/AsyncState";
import { useAccount } from "@/features/account/api";
import { fetchReferrals } from "@/features/earn/api";
import { formatAccountMoney } from "@/features/home/format";
import { usePaymentAccounts, useWithdrawals } from "@/features/wallet/api";
import { useTheme } from "@/theme/ThemeProvider";
import type { ThemePreference } from "@/theme/themePreference";

const EXTERNAL_LINKS = {
  privacy: "https://mesale.vn/privacy",
  support: "https://mesale.vn/support",
  terms: "https://mesale.vn/terms"
} as const;

type MenuIcon = ComponentType<{ color?: string; size?: number }>;

type AccountMenuRowProps = {
  actionLabel?: string;
  danger?: boolean;
  icon: MenuIcon;
  iconBackground: string;
  iconColor: string;
  onPress: () => void;
  showDivider?: boolean;
  subtitle?: string;
  title: string;
};

function AccountMenuRow({
  actionLabel,
  danger = false,
  icon: Icon,
  iconBackground,
  iconColor,
  onPress,
  showDivider = true,
  subtitle,
  title
}: AccountMenuRowProps) {
  const { colors } = useTheme();

  return (
    <Pressable
      accessibilityHint={subtitle}
      accessibilityLabel={title}
      accessibilityRole="button"
      onPress={onPress}
      style={({ pressed }) => [styles.menuRow, pressed && styles.pressed]}
    >
      <View style={[styles.menuIcon, { backgroundColor: iconBackground }]}>
        <Icon color={iconColor} size={21} />
      </View>
      <View style={[styles.menuCopy, showDivider && { borderBottomColor: colors.border, borderBottomWidth: StyleSheet.hairlineWidth }]}>
        <View style={styles.menuTextWrap}>
          <Text numberOfLines={1} style={[styles.menuTitle, { color: danger ? colors.danger : colors.text }]}>{title}</Text>
          {subtitle ? <Text numberOfLines={1} style={[styles.menuSubtitle, { color: colors.mutedText }]}>{subtitle}</Text> : null}
        </View>
        {actionLabel ? (
          <View style={[styles.actionPill, { backgroundColor: iconBackground }]}>
            <Text style={[styles.actionPillText, { color: iconColor }]}>{actionLabel}</Text>
          </View>
        ) : null}
        <ChevronRight color={colors.mutedText} size={21} />
      </View>
    </Pressable>
  );
}

function formatRate(value: number): string {
  return new Intl.NumberFormat("vi-VN", { maximumFractionDigits: 1 }).format(value);
}

function secureAvatarUri(value: string | null): string | null {
  if (!value?.trim()) return null;
  try {
    const url = new URL(value.trim());
    return url.protocol === "https:" && !url.username && !url.password ? url.toString() : null;
  } catch {
    return null;
  }
}

export default function AccountRoute() {
  const router = useRouter();
  const insets = useSafeAreaInsets();
  const { logout } = useAuth();
  const { colors, preference, scheme, setPreference } = useTheme();
  const accountQuery = useAccount();
  const paymentAccountsQuery = usePaymentAccounts();
  const withdrawalsQuery = useWithdrawals();
  const referralsQuery = useQuery({
    queryKey: ["account", "referral-preview"],
    queryFn: ({ signal }) => fetchReferrals(1, {}, signal)
  });
  const [loggingOut, setLoggingOut] = useState(false);
  const logoutInFlight = useRef(false);

  if (accountQuery.isPending) return <LoadingState label="Đang tải tài khoản..." />;
  if (accountQuery.isError) {
    const props = {
      actionLabel: "Thử lại",
      message: accountQuery.error instanceof Error ? accountQuery.error.message : undefined,
      onAction: () => void accountQuery.refetch(),
      title: "Không thể tải tài khoản"
    };
    return accountQuery.error instanceof ApiError && accountQuery.error.isNetworkError
      ? <OfflineState {...props} />
      : <ErrorState {...props} />;
  }

  const account = accountQuery.data;
  const displayName = account.name.trim() || "Thành viên Mê Sale";
  const initial = displayName.slice(0, 1).toUpperCase();
  const avatarUri = secureAvatarUri(account.avatar);
  const accountLocale = account.preferences?.locale?.trim() || "vi";
  const accountCurrency = account.preferences?.currency?.trim() || account.wallet?.currency?.trim() || "VND";
  const paymentAccountCount = paymentAccountsQuery.data?.total ?? 0;
  const withdrawalCount = withdrawalsQuery.data?.pages[0]?.pagination.total;
  const hasConfirmedNoPaymentAccount = paymentAccountsQuery.isSuccess && paymentAccountCount === 0;
  const paymentSubtitle = paymentAccountsQuery.isPending
    ? "Đang kiểm tra liên kết..."
    : paymentAccountsQuery.isError
      ? "Chưa thể kiểm tra lúc này"
      : paymentAccountCount > 0
        ? `${paymentAccountCount} tài khoản đã liên kết`
        : "Chưa liên kết";
  const referralRate = referralsQuery.data?.rates.f1_rate;
  const currentThemeLabel = preference === "system" ? "Theo hệ thống" : preference === "dark" ? "Tối" : "Sáng";
  const screenBackground = scheme === "dark" ? "#08111f" : "#f2f7fc";
  const softBlue = scheme === "dark" ? "#102a44" : "#eaf5ff";
  const softGreen = scheme === "dark" ? "#0d3327" : "#eaf9f1";
  const softOrange = scheme === "dark" ? "#3b2910" : "#fff6df";
  const softRed = scheme === "dark" ? "#3a171c" : "#fff0f1";
  const softGray = scheme === "dark" ? "#243143" : "#f1f5f9";

  function navigateTo(href: Href) {
    router.push(href);
  }

  async function openExternal(url: string, label: string) {
    try {
      if (!await Linking.canOpenURL(url)) throw new Error("Unsupported URL");
      await Linking.openURL(url);
    } catch {
      Alert.alert(label, "Không thể mở trang này lúc này. Vui lòng thử lại sau.");
    }
  }

  function showUnavailable(title: string) {
    Alert.alert(title, "Tính năng này đang được hoàn thiện trên ứng dụng Mê Sale.");
  }

  function chooseTheme() {
    const options: Array<{ label: string; value: ThemePreference }> = [
      { label: "Theo hệ thống", value: "system" },
      { label: "Chế độ sáng", value: "light" },
      { label: "Chế độ tối", value: "dark" }
    ];
    Alert.alert(
      "Giao diện",
      "Chọn chế độ hiển thị cho ứng dụng.",
      [
        ...options.map((option) => ({ text: option.label, onPress: () => setPreference(option.value) })),
        { text: "Hủy", style: "cancel" as const }
      ]
    );
  }

  async function signOut() {
    if (logoutInFlight.current) return;
    logoutInFlight.current = true;
    setLoggingOut(true);
    try {
      await logout();
    } catch {
      Alert.alert("Đã đăng xuất trên thiết bị", "Máy chủ có thể chưa thu hồi phiên này. Vui lòng kiểm tra lại trong mục Phiên đăng nhập khi đăng nhập lần sau.");
    } finally {
      logoutInFlight.current = false;
      setLoggingOut(false);
    }
  }

  return (
    <ScrollView
      contentContainerStyle={[styles.content, { backgroundColor: screenBackground, paddingBottom: insets.bottom + 32, paddingTop: insets.top + 18 }]}
      contentInsetAdjustmentBehavior="automatic"
      refreshControl={(
        <RefreshControl
          colors={[colors.primary]}
          onRefresh={() => void Promise.all([
            accountQuery.refetch(),
            paymentAccountsQuery.refetch(),
            referralsQuery.refetch(),
            withdrawalsQuery.refetch()
          ])}
          refreshing={accountQuery.isRefetching || paymentAccountsQuery.isRefetching || referralsQuery.isRefetching || withdrawalsQuery.isRefetching}
          tintColor={colors.primary}
        />
      )}
      showsVerticalScrollIndicator={false}
      style={{ backgroundColor: screenBackground }}
    >
      <View style={styles.profileHeader}>
        <Pressable
          accessibilityLabel="Mở thông tin cá nhân"
          accessibilityRole="button"
          onPress={() => navigateTo("/(tabs)/account/profile")}
          style={({ pressed }) => [styles.profileIdentity, pressed && styles.pressed]}
        >
          <View style={styles.avatarRing}>
            <View style={[styles.avatar, { backgroundColor: colors.surface }]}>
              {avatarUri ? (
                <Image accessibilityIgnoresInvertColors source={{ uri: avatarUri }} style={styles.avatarImage} />
              ) : <Text style={styles.avatarText}>{initial}</Text>}
            </View>
          </View>
          <View style={styles.identityCopy}>
            <Text numberOfLines={1} style={[styles.profileName, { color: colors.text }]}>{displayName}</Text>
            <Text numberOfLines={1} style={[styles.profileEmail, { color: colors.mutedText }]}>{account.email}</Text>
          </View>
        </Pressable>
        <View accessibilityLabel="Hoàn tiền Mê Sale" style={[styles.cashbackBadge, { backgroundColor: softBlue }]}>
          <Sparkles color="#2f9af5" size={16} />
          <Text style={styles.cashbackBadgeText}>Hoàn tiền Mê Sale</Text>
        </View>
      </View>

      <LinearGradient
        colors={scheme === "dark" ? ["#1269c7", "#0b4d9f"] : ["#39a8ff", "#1473df"]}
        end={{ x: 1, y: 1 }}
        start={{ x: 0, y: 0 }}
        style={styles.walletCard}
      >
        <View pointerEvents="none" style={styles.walletBubble} />
        <Text style={styles.balanceLabel}>Số dư khả dụng</Text>
        <Text adjustsFontSizeToFit numberOfLines={1} style={styles.balanceValue}>{formatAccountMoney(account.wallet?.balance ?? null, "vi")}</Text>
        <View style={styles.walletStats}>
          <View style={styles.walletStat}>
            <Text style={styles.walletStatLabel}>Tổng đã nhận</Text>
            <Text numberOfLines={1} style={styles.walletStatValue}>{formatAccountMoney(account.wallet?.total_cashback ?? null, "vi")}</Text>
          </View>
          <View style={styles.walletStatDivider} />
          <View style={styles.walletStat}>
            <Text style={styles.walletStatLabel}>Từ giới thiệu</Text>
            <Text numberOfLines={1} style={styles.walletStatValue}>{formatAccountMoney(account.wallet?.total_referral_earned ?? null, "vi")}</Text>
          </View>
        </View>
        <Pressable
          accessibilityLabel="Rút tiền về ngân hàng"
          accessibilityRole="button"
          onPress={() => navigateTo("/(tabs)/wallet/withdrawals/create")}
          style={({ pressed }) => [styles.withdrawButton, pressed && styles.pressed]}
        >
          <ArrowDownCircle color="#197ddd" size={22} />
          <Text style={styles.withdrawButtonText}>Rút tiền về ngân hàng</Text>
        </Pressable>
      </LinearGradient>

      {hasConfirmedNoPaymentAccount ? (
        <View style={[styles.bankWarning, { backgroundColor: scheme === "dark" ? "#33290f" : "#fffbea", borderColor: scheme === "dark" ? "#8a6c10" : "#f5d666" }]}>
          <View style={[styles.warningIcon, { backgroundColor: scheme === "dark" ? "#5b4510" : "#fff2bd" }]}>
            <Text style={styles.warningMark}>!</Text>
          </View>
          <View style={styles.warningCopy}>
            <Text style={[styles.warningTitle, { color: scheme === "dark" ? "#fde68a" : "#92400e" }]}>Chưa liên kết ngân hàng</Text>
            <Text style={[styles.warningSubtitle, { color: scheme === "dark" ? "#fcd34d" : "#b45309" }]}>Thêm tài khoản nhận tiền để gửi yêu cầu rút.</Text>
          </View>
          <Pressable
            accessibilityLabel="Thêm tài khoản ngân hàng ngay"
            accessibilityRole="button"
            onPress={() => navigateTo("/(tabs)/wallet/payment-accounts/create")}
            style={({ pressed }) => [styles.warningAction, pressed && styles.pressed]}
          >
            <Text style={styles.warningActionText}>Thêm ngay</Text>
          </Pressable>
        </View>
      ) : null}

      <Pressable
        accessibilityLabel="Mở chương trình giới thiệu bạn bè"
        accessibilityRole="button"
        onPress={() => navigateTo("/(tabs)/earn/referrals")}
        style={({ pressed }) => [styles.referralCard, { backgroundColor: colors.surface }, pressed && styles.pressed]}
      >
        <View pointerEvents="none" style={[styles.referralDecoration, { backgroundColor: softBlue }]} />
        <View style={styles.referralCopy}>
          <View style={styles.referralTitleRow}>
            <Text style={[styles.referralTitle, { color: colors.text }]}>Giới thiệu bạn bè</Text>
            {typeof referralRate === "number" && Number.isFinite(referralRate) && referralRate > 0 ? (
              <View style={[styles.referralRate, { backgroundColor: softGreen }]}>
                <Text style={styles.referralRateText}>+{formatRate(referralRate)}% hiện hành</Text>
              </View>
            ) : null}
          </View>
          <Text style={[styles.referralDescription, { color: colors.mutedText }]}>Nhận hoa hồng giới thiệu từ đơn đủ điều kiện của bạn bè.</Text>
          <Text style={styles.referralLink}>{account.referral_code ? `Lấy mã ${account.referral_code}  →` : "Xem chương trình giới thiệu  →"}</Text>
        </View>
        <Gift color="#93c5fd" size={38} />
      </Pressable>

      <Text style={[styles.sectionLabel, { color: colors.mutedText }]}>TÀI KHOẢN</Text>
      <View style={[styles.menuCard, { backgroundColor: colors.surface }]}>
        <AccountMenuRow
          icon={UserRound}
          iconBackground={softBlue}
          iconColor="#2f9af5"
          onPress={() => navigateTo("/(tabs)/account/profile")}
          subtitle="Họ tên và số điện thoại"
          title="Thông tin cá nhân"
        />
        <AccountMenuRow
          actionLabel={paymentAccountsQuery.isSuccess && paymentAccountCount === 0 ? "Thêm ngay" : undefined}
          icon={CreditCard}
          iconBackground={softOrange}
          iconColor="#f59e0b"
          onPress={() => navigateTo("/(tabs)/wallet/payment-accounts")}
          subtitle={paymentSubtitle}
          title="Tài khoản ngân hàng"
        />
        <AccountMenuRow
          actionLabel={typeof withdrawalCount === "number" ? `${withdrawalCount} lệnh` : undefined}
          icon={History}
          iconBackground={softBlue}
          iconColor="#2f9af5"
          onPress={() => navigateTo("/(tabs)/wallet/withdrawals")}
          subtitle="Theo dõi trạng thái rút tiền"
          title="Lịch sử rút tiền"
        />
        <AccountMenuRow
          icon={Bell}
          iconBackground={softBlue}
          iconColor="#2f9af5"
          onPress={() => navigateTo("/(tabs)/inbox")}
          subtitle="Thông báo và biến động tài khoản"
          title="Thông báo"
        />
        <AccountMenuRow
          icon={ShieldCheck}
          iconBackground={softGreen}
          iconColor="#16a34a"
          onPress={() => navigateTo("/(tabs)/account/security")}
          subtitle="OTP email và xác thực hai lớp"
          title="Bảo mật tài khoản"
        />
        <AccountMenuRow
          icon={UsersRound}
          iconBackground={softBlue}
          iconColor="#2f9af5"
          onPress={() => navigateTo("/(tabs)/account/sessions")}
          subtitle="Kiểm tra và thu hồi thiết bị"
          title="Phiên đăng nhập"
        />
        <AccountMenuRow
          icon={Languages}
          iconBackground={softBlue}
          iconColor="#2f9af5"
          onPress={() => navigateTo("/(tabs)/account/preferences")}
          subtitle={`${accountLocale.toUpperCase()} · ${accountCurrency}`}
          title="Ngôn ngữ & tiền tệ"
        />
        <AccountMenuRow
          icon={SunMoon}
          iconBackground={softOrange}
          iconColor="#f59e0b"
          onPress={chooseTheme}
          showDivider={false}
          subtitle={currentThemeLabel}
          title="Giao diện"
        />
      </View>

      <Text style={[styles.sectionLabel, { color: colors.mutedText }]}>KHÁM PHÁ</Text>
      <View style={[styles.menuCard, { backgroundColor: colors.surface }]}>
        <AccountMenuRow
          icon={Tag}
          iconBackground={softRed}
          iconColor="#f05a3c"
          onPress={() => showUnavailable("Săn mã giảm giá")}
          subtitle="Tính năng sẽ mở khi nội dung sẵn sàng"
          title="Săn mã giảm giá"
        />
        <AccountMenuRow
          icon={CalendarCheck}
          iconBackground={softOrange}
          iconColor="#f59e0b"
          onPress={() => navigateTo("/(tabs)/earn/checkin")}
          subtitle="Điểm danh và nhận thưởng mỗi ngày"
          title="Điểm danh nhận xu"
        />
        <AccountMenuRow
          icon={ListChecks}
          iconBackground={softGreen}
          iconColor="#16a34a"
          onPress={() => navigateTo("/(tabs)/earn/tasks")}
          subtitle="Hoàn thành nhiệm vụ đang mở"
          title="Nhiệm vụ nhận thưởng"
        />
        <AccountMenuRow
          icon={Gift}
          iconBackground={softBlue}
          iconColor="#2f9af5"
          onPress={() => navigateTo("/(tabs)/earn/gifts")}
          subtitle="Khám phá quà tặng hiện có"
          title="Đổi quà tặng"
        />
        <AccountMenuRow
          icon={Lightbulb}
          iconBackground={softOrange}
          iconColor="#f59e0b"
          onPress={() => showUnavailable("Tips & Trick")}
          subtitle="Mẹo tăng khả năng đơn được ghi nhận"
          title="Tips & Trick"
        />
        <AccountMenuRow
          icon={CircleHelp}
          iconBackground={softBlue}
          iconColor="#2f9af5"
          onPress={() => showUnavailable("Hướng dẫn sử dụng")}
          showDivider={false}
          title="Hướng dẫn sử dụng"
        />
      </View>

      <Text style={[styles.sectionLabel, { color: colors.mutedText }]}>HỖ TRỢ & PHÁP LÝ</Text>
      <View style={[styles.menuCard, { backgroundColor: colors.surface }]}>
        <AccountMenuRow
          icon={CircleHelp}
          iconBackground={softGreen}
          iconColor="#16a34a"
          onPress={() => void openExternal(EXTERNAL_LINKS.support, "Hỗ trợ Mê Sale")}
          subtitle="Liên hệ bộ phận hỗ trợ Mê Sale"
          title="Hỗ trợ Mê Sale"
        />
        <AccountMenuRow
          icon={ShieldCheck}
          iconBackground={softBlue}
          iconColor="#2f9af5"
          onPress={() => void openExternal(EXTERNAL_LINKS.privacy, "Chính sách bảo mật")}
          title="Chính sách bảo mật"
        />
        <AccountMenuRow
          icon={FileText}
          iconBackground={softBlue}
          iconColor="#2f9af5"
          onPress={() => void openExternal(EXTERNAL_LINKS.terms, "Điều khoản sử dụng")}
          showDivider={false}
          title="Điều khoản sử dụng"
        />
      </View>

      <View style={[styles.menuCard, { backgroundColor: colors.surface }]}>
        <AccountMenuRow
          icon={LogOut}
          iconBackground={softGray}
          iconColor={colors.mutedText}
          onPress={() => void signOut()}
          title={loggingOut ? "Đang đăng xuất..." : "Đăng xuất"}
        />
        {loggingOut ? <ActivityIndicator color={colors.primary} style={styles.logoutSpinner} /> : null}
        <AccountMenuRow
          danger
          icon={Trash2}
          iconBackground={softRed}
          iconColor={colors.danger}
          onPress={() => navigateTo("/(tabs)/account/delete")}
          showDivider={false}
          title="Xóa tài khoản"
        />
      </View>

      <Text style={[styles.disclosure, { color: colors.mutedText }]}>Mê Sale là ứng dụng hoàn tiền độc lập, không phải sản phẩm chính thức của Shopee, TikTok Shop hoặc Lazada.</Text>
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  content: { gap: 18, paddingHorizontal: 18 },
  profileHeader: { alignItems: "center", flexDirection: "row", gap: 12, justifyContent: "space-between" },
  profileIdentity: { alignItems: "center", flex: 1, flexDirection: "row", gap: 12, minHeight: 58, minWidth: 0 },
  avatarRing: { alignItems: "center", borderColor: "#75baff", borderRadius: 34, borderWidth: 3, height: 68, justifyContent: "center", width: 68 },
  avatar: { alignItems: "center", borderRadius: 28, height: 56, justifyContent: "center", overflow: "hidden", width: 56 },
  avatarImage: { height: 56, width: 56 },
  avatarText: { color: "#2f9af5", fontSize: 23, fontWeight: "900" },
  identityCopy: { flex: 1, gap: 3, minWidth: 0 },
  profileName: { fontSize: 19, fontWeight: "900" },
  profileEmail: { fontSize: 13 },
  cashbackBadge: { alignItems: "center", borderColor: "#9ed2ff", borderRadius: 999, borderWidth: 1, flexDirection: "row", gap: 5, minHeight: 44, paddingHorizontal: 8 },
  cashbackBadgeText: { color: "#258ce9", fontSize: 11, fontWeight: "900" },
  walletCard: { borderRadius: 24, gap: 5, overflow: "hidden", padding: 22 },
  walletBubble: { backgroundColor: "rgba(255,255,255,0.10)", borderRadius: 130, height: 220, position: "absolute", right: -64, top: -96, width: 220 },
  balanceLabel: { color: "rgba(255,255,255,0.86)", fontSize: 15 },
  balanceValue: { color: "#ffffff", fontSize: 34, fontWeight: "900", letterSpacing: -1, marginBottom: 18 },
  walletStats: { flexDirection: "row", marginBottom: 18 },
  walletStat: { flex: 1, gap: 4 },
  walletStatDivider: { backgroundColor: "rgba(255,255,255,0.28)", marginHorizontal: 16, width: StyleSheet.hairlineWidth },
  walletStatLabel: { color: "rgba(255,255,255,0.76)", fontSize: 12 },
  walletStatValue: { color: "#ffffff", fontSize: 17, fontWeight: "900" },
  withdrawButton: { alignItems: "center", backgroundColor: "#ffffff", borderRadius: 16, flexDirection: "row", gap: 9, justifyContent: "center", minHeight: 52, paddingHorizontal: 16 },
  withdrawButtonText: { color: "#197ddd", fontSize: 16, fontWeight: "900" },
  bankWarning: { alignItems: "center", borderRadius: 18, borderWidth: 1, flexDirection: "row", gap: 11, padding: 13 },
  warningIcon: { alignItems: "center", borderRadius: 12, height: 42, justifyContent: "center", width: 42 },
  warningMark: { color: "#f59e0b", fontSize: 19, fontWeight: "900" },
  warningCopy: { flex: 1, gap: 2, minWidth: 0 },
  warningTitle: { fontSize: 14, fontWeight: "900" },
  warningSubtitle: { fontSize: 11, lineHeight: 15 },
  warningAction: { alignItems: "center", backgroundColor: "#f59e0b", borderRadius: 999, justifyContent: "center", minHeight: 44, paddingHorizontal: 14 },
  warningActionText: { color: "#ffffff", fontSize: 12, fontWeight: "900" },
  referralCard: { alignItems: "center", borderRadius: 22, flexDirection: "row", gap: 12, minHeight: 132, overflow: "hidden", padding: 18 },
  referralDecoration: { borderRadius: 100, height: 150, position: "absolute", right: -50, top: -55, width: 150 },
  referralCopy: { flex: 1, gap: 8, minWidth: 0, zIndex: 1 },
  referralTitleRow: { alignItems: "center", flexDirection: "row", flexWrap: "wrap", gap: 8 },
  referralTitle: { fontSize: 18, fontWeight: "900" },
  referralRate: { borderRadius: 999, paddingHorizontal: 9, paddingVertical: 4 },
  referralRateText: { color: "#16a34a", fontSize: 11, fontWeight: "900" },
  referralDescription: { fontSize: 13, lineHeight: 19 },
  referralLink: { color: "#2f9af5", fontSize: 14, fontWeight: "900" },
  sectionLabel: { fontSize: 13, fontWeight: "900", letterSpacing: 1.4, marginLeft: 4, marginTop: 4 },
  menuCard: { borderRadius: 22, overflow: "hidden" },
  menuRow: { alignItems: "center", flexDirection: "row", minHeight: 74, paddingLeft: 14 },
  menuIcon: { alignItems: "center", borderRadius: 13, height: 44, justifyContent: "center", width: 44 },
  menuCopy: { alignItems: "center", flex: 1, flexDirection: "row", gap: 8, marginLeft: 12, minHeight: 74, paddingRight: 13 },
  menuTextWrap: { flex: 1, gap: 3, minWidth: 0 },
  menuTitle: { fontSize: 15, fontWeight: "800" },
  menuSubtitle: { fontSize: 12 },
  actionPill: { borderRadius: 999, paddingHorizontal: 10, paddingVertical: 6 },
  actionPillText: { fontSize: 11, fontWeight: "900" },
  logoutSpinner: { position: "absolute", right: 48, top: 27 },
  disclosure: { fontSize: 12, lineHeight: 18, paddingHorizontal: 24, textAlign: "center" },
  pressed: { opacity: 0.72 }
});
