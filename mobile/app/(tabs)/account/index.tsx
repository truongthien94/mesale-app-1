import { useRef, useState, type ComponentType } from "react";
import { useInfiniteQuery } from "@tanstack/react-query";
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
  Languages,
  Lightbulb,
  LogOut,
  ShieldCheck,
  Tag,
  Trash2,
  UserRound,
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
  TextInput,
  View
} from "react-native";
import { useSafeAreaInsets } from "react-native-safe-area-context";
import { applyReferralCode } from "@/api/auth";
import { ApiError } from "@/api/client";
import { useAuth } from "@/auth/AuthProvider";
import { ErrorState, LoadingState, OfflineState } from "@/components/AsyncState";
import { CompactBlueHero } from "@/components/CompactBlueHero";
import { FormErrorSummary } from "@/components/FormErrorSummary";
import { useIosPayoutFeaturesEnabled } from "@/config/features";
import { useAccount } from "@/features/account/api";
import { referralsQueryOptions } from "@/features/earn/api";
import { formatAccountMoney } from "@/features/home/format";
import { legalUrlsForPayoutFeatures } from "@/features/legal/urls";
import { usePaymentAccounts } from "@/features/wallet/api";
import { useTheme } from "@/theme/ThemeProvider";

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

function referralDeadlineCopy(expiresAt: string | null | undefined): string {
  if (!expiresAt) return "Mê Sale sẽ kiểm tra thời hạn 72 giờ trên máy chủ khi bạn gửi mã.";
  const expiryTimestamp = Date.parse(expiresAt);
  if (!Number.isFinite(expiryTimestamp)) return "Mê Sale sẽ kiểm tra thời hạn 72 giờ trên máy chủ khi bạn gửi mã.";

  const deadline = new Intl.DateTimeFormat("vi-VN", {
    day: "2-digit",
    hour: "2-digit",
    minute: "2-digit",
    month: "2-digit",
    year: "numeric"
  }).format(expiryTimestamp);
  return `Hạn nhập mã do máy chủ Mê Sale xác nhận: ${deadline}.`;
}

const REFERRAL_STATE_MESSAGES: Record<string, string> = {
  REFERRAL_WINDOW_EXPIRED: "Thời hạn nhập mã giới thiệu đã kết thúc.",
  REFERRAL_NOT_ELIGIBLE: "Tài khoản hiện không đủ điều kiện nhập mã giới thiệu.",
  REFERRAL_ALREADY_LINKED: "Tài khoản đã liên kết với người giới thiệu.",
  REFERRAL_DISABLED: "Chương trình giới thiệu hiện đang tạm dừng."
};

function secureAvatarUri(value: string | null): string | null {
  if (!value?.trim()) return null;
  try {
    const url = new URL(value.trim());
    return url.protocol === "https:" && !url.username && !url.password ? url.toString() : null;
  } catch {
    return null;
  }
}

type AccountPreview = {
  id: number;
  name: string;
  email: string | null;
  avatar: string | null;
  referral_code: string | null;
  referral_code_eligible?: boolean;
  referral_code_expires_at?: string | null;
  wallet: {
    balance: number;
    total_cashback: number;
    total_referral_earned: number;
    total_withdrawn: number;
  } | null;
};

function formatKnownMoney(value: number | null | undefined): string {
  return typeof value === "number" ? formatAccountMoney(value, "vi") : "—";
}

export default function AccountRoute() {
  const router = useRouter();
  const insets = useSafeAreaInsets();
  const { logout, refreshUser, user } = useAuth();
  const { colors, scheme } = useTheme();
  const payoutFeaturesEnabled = useIosPayoutFeaturesEnabled();
  const legalUrls = legalUrlsForPayoutFeatures(payoutFeaturesEnabled);
  const accountQuery = useAccount({ enabled: payoutFeaturesEnabled });
  const referralsQuery = useInfiniteQuery({ ...referralsQueryOptions(), enabled: payoutFeaturesEnabled });
  const [loggingOut, setLoggingOut] = useState(false);
  const logoutInFlight = useRef(false);
  const [referralEntryCode, setReferralEntryCode] = useState("");
  const [referralEntryError, setReferralEntryError] = useState<ApiError | null>(null);
  const [referralEntryExpanded, setReferralEntryExpanded] = useState(false);
  const [submittingReferral, setSubmittingReferral] = useState(false);
  const referralEntryInFlight = useRef(false);

  const accountPreview: AccountPreview | null = user ? {
    id: user.id,
    name: user.name?.trim() || "Thành viên Mê Sale",
    email: user.email ?? null,
    avatar: user.avatar ?? null,
    referral_code: user.referral_code ?? null,
    referral_code_eligible: user.referralCodeEligible,
    referral_code_expires_at: user.referralCodeExpiresAt,
    wallet: user.financialSnapshot ? {
      balance: user.financialSnapshot.balance,
      total_cashback: user.financialSnapshot.totalCashback,
      total_referral_earned: user.financialSnapshot.totalReferralEarned,
      total_withdrawn: user.financialSnapshot.totalWithdrawn
    } : null
  } : null;
  const serverAccount = payoutFeaturesEnabled && user && accountQuery.data?.id === user.id ? accountQuery.data : null;
  const account = serverAccount ?? accountPreview;

  if (accountQuery.isPending && !account) return <LoadingState label="Đang tải tài khoản..." />;
  if (accountQuery.isError && !account) {
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
  if (!account) return <LoadingState label="Đang đồng bộ tài khoản..." />;

  const displayName = account.name.trim() || "Thành viên Mê Sale";
  const initial = displayName.slice(0, 1).toUpperCase();
  const avatarUri = secureAvatarUri(account.avatar);
  const referralRate = referralsQuery.data?.pages[0]?.rates.f1_rate;
  const normalizedReferralEntryCode = referralEntryCode.trim();
  const referralCodeEligible = typeof account.referral_code_eligible === "boolean"
    ? account.referral_code_eligible
    : user?.referralCodeEligible === true;
  const referralCodeExpiresAt = account.referral_code_expires_at !== undefined
    ? account.referral_code_expires_at
    : user?.referralCodeExpiresAt;
  const referralWindowCopy = referralDeadlineCopy(referralCodeExpiresAt);
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

  async function refreshReferralState() {
    await Promise.allSettled([accountQuery.refetch(), refreshUser()]);
  }

  async function submitReferralCode() {
    if (referralEntryInFlight.current || !normalizedReferralEntryCode) return;
    referralEntryInFlight.current = true;
    setSubmittingReferral(true);
    setReferralEntryError(null);
    try {
      await applyReferralCode(normalizedReferralEntryCode);
      setReferralEntryCode("");
      setReferralEntryExpanded(false);
      await refreshReferralState();
      Alert.alert("Đã liên kết", "Mã giới thiệu đã được áp dụng thành công.");
    } catch (reason) {
      const error = reason instanceof ApiError
        ? reason
        : new ApiError(reason instanceof Error ? reason.message : "Không thể áp dụng mã giới thiệu.", 0);
      if (error.code && REFERRAL_STATE_MESSAGES[error.code]) {
        await refreshReferralState();
        Alert.alert("Mã giới thiệu", REFERRAL_STATE_MESSAGES[error.code]);
      } else {
        setReferralEntryError(error);
      }
    } finally {
      referralEntryInFlight.current = false;
      setSubmittingReferral(false);
    }
  }

  return (
    <ScrollView
      contentContainerStyle={[styles.content, { backgroundColor: screenBackground, paddingBottom: insets.bottom + 32, paddingTop: insets.top + 18 }]}
      contentInsetAdjustmentBehavior="automatic"
      keyboardShouldPersistTaps="handled"
      refreshControl={(
        <RefreshControl
          colors={[colors.primary]}
          onRefresh={() => void (payoutFeaturesEnabled
            ? Promise.all([accountQuery.refetch(), referralsQuery.refetch()])
            : refreshUser())}
          refreshing={payoutFeaturesEnabled && (accountQuery.isRefetching || referralsQuery.isRefetching)}
          tintColor={colors.primary}
        />
      )}
      showsVerticalScrollIndicator={false}
      style={{ backgroundColor: screenBackground }}
    >
      {payoutFeaturesEnabled && accountQuery.isError ? (
        <View style={[styles.accountSyncWarning, { backgroundColor: softOrange, borderColor: scheme === "dark" ? "#8a6c10" : "#f5d666" }]}>
          <View style={styles.accountSyncCopy}>
            <Text style={[styles.accountSyncTitle, { color: scheme === "dark" ? "#fde68a" : "#92400e" }]}>Dữ liệu tài khoản có thể chưa mới nhất</Text>
            <Text style={[styles.accountSyncMessage, { color: scheme === "dark" ? "#fcd34d" : "#b45309" }]}>Mê Sale đang hiển thị thông tin từ phiên đăng nhập gần nhất.</Text>
          </View>
          <Pressable
            accessibilityLabel="Thử tải lại dữ liệu tài khoản"
            accessibilityRole="button"
            onPress={() => void accountQuery.refetch()}
            style={({ pressed }) => [styles.accountSyncRetry, pressed && styles.pressed]}
          >
            <Text style={styles.accountSyncRetryText}>Thử lại</Text>
          </Pressable>
        </View>
      ) : null}

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
            <Text numberOfLines={1} style={[styles.profileEmail, { color: colors.mutedText }]}>{account.email?.trim() || "—"}</Text>
          </View>
        </Pressable>
      </View>

      {payoutFeaturesEnabled ? <AccountPayoutSection accountWallet={account.wallet ?? null} navigateTo={navigateTo} /> : null}

      {payoutFeaturesEnabled ? <>
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

      {referralCodeEligible ? (
        <View style={[styles.menuCard, { backgroundColor: colors.surface }]}>
          <AccountMenuRow
              actionLabel={referralEntryExpanded ? "Thu gọn" : "Nhập ngay"}
              icon={Gift}
              iconBackground={softBlue}
              iconColor="#2f9af5"
              onPress={() => {
                setReferralEntryExpanded((value) => !value);
                setReferralEntryError(null);
              }}
              showDivider={referralEntryExpanded}
              subtitle="Chỉ áp dụng trong 3 ngày đầu sau khi đăng ký"
              title="Nhập mã giới thiệu"
            />
          {referralEntryExpanded ? (
            <View style={[styles.referralEntryPanel, { borderBottomColor: colors.border }]}>
                <Text style={[styles.referralEntryDeadline, { color: colors.mutedText }]}>{referralWindowCopy}</Text>
                <View style={styles.referralEntryControls}>
                  <TextInput
                    accessibilityLabel="Mã giới thiệu"
                    autoCapitalize="characters"
                    autoComplete="off"
                    editable={!submittingReferral}
                    maxLength={50}
                    onChangeText={(value) => {
                      setReferralEntryCode(value);
                      setReferralEntryError(null);
                    }}
                    onSubmitEditing={() => void submitReferralCode()}
                    placeholder="Nhập mã giới thiệu"
                    placeholderTextColor={colors.mutedText}
                    returnKeyType="done"
                    style={[styles.referralEntryInput, { backgroundColor: screenBackground, borderColor: referralEntryError ? colors.danger : colors.border, color: colors.text }]}
                    value={referralEntryCode}
                  />
                  <Pressable
                    accessibilityLabel="Xác nhận mã giới thiệu"
                    accessibilityRole="button"
                    accessibilityState={{ busy: submittingReferral, disabled: submittingReferral || !normalizedReferralEntryCode }}
                    disabled={submittingReferral || !normalizedReferralEntryCode}
                    onPress={() => void submitReferralCode()}
                    style={({ pressed }) => [styles.referralEntryButton, (submittingReferral || !normalizedReferralEntryCode) && styles.disabledAction, pressed && styles.pressed]}
                  >
                    {submittingReferral
                      ? <ActivityIndicator color="#ffffff" />
                      : <Text style={styles.referralEntryButtonText}>Áp dụng</Text>}
                  </Pressable>
                </View>
                <FormErrorSummary errors={referralEntryError?.errors} message={referralEntryError?.message} />
            </View>
          ) : null}
        </View>
      ) : null}
      </> : null}

      <View style={styles.primaryMenuStack}>
        <View
          style={[styles.menuCard, { backgroundColor: colors.surface }]}
        >
          <AccountMenuRow
            icon={UserRound}
            iconBackground={softBlue}
            iconColor="#2f9af5"
            onPress={() => navigateTo("/(tabs)/account/information")}
            showDivider={false}
            subtitle="Hồ sơ, bảo mật và phiên đăng nhập"
            title="Thông tin tài khoản"
          />
        </View>

        {payoutFeaturesEnabled ? (
          <View
            style={[styles.menuCard, { backgroundColor: colors.surface }]}
          >
          <AccountMenuRow
            icon={CreditCard}
            iconBackground={softOrange}
            iconColor="#f59e0b"
            onPress={() => navigateTo("/(tabs)/account/finance")}
            showDivider={false}
            subtitle="Tài khoản ngân hàng và lịch sử rút tiền"
            title="Tài chính"
          />
          </View>
        ) : null}

        {payoutFeaturesEnabled ? <View style={[styles.menuCard, { backgroundColor: colors.surface }]}>
          <AccountMenuRow
            icon={Bell}
            iconBackground={softBlue}
            iconColor="#2f9af5"
            onPress={() => navigateTo("/(tabs)/inbox")}
            showDivider={false}
            subtitle="Thông báo và biến động tài khoản"
            title="Thông báo"
          />
        </View> : null}

        <View style={[styles.menuCard, { backgroundColor: colors.surface }]}>
          <AccountMenuRow
            icon={Languages}
            iconBackground={softBlue}
            iconColor="#2f9af5"
            onPress={() => navigateTo("/(tabs)/account/settings")}
            showDivider={false}
            subtitle="Ngôn ngữ, tiền tệ và giao diện"
            title="Cài đặt"
          />
        </View>
      </View>

      <Text style={[styles.sectionLabel, { color: colors.mutedText }]}>KHÁM PHÁ</Text>
      <View style={[styles.menuCard, { backgroundColor: colors.surface }]}>
        <AccountMenuRow
          icon={Tag}
          iconBackground={softRed}
          iconColor="#f05a3c"
          onPress={() => navigateTo("/(tabs)/home/coupons")}
          subtitle="Mã hot cập nhật mỗi ngày"
          title="Săn mã giảm giá"
        />
        {payoutFeaturesEnabled ? <AccountMenuRow
          icon={CalendarCheck}
          iconBackground={softOrange}
          iconColor="#f59e0b"
          onPress={() => navigateTo("/(tabs)/earn/checkin")}
          subtitle="Điểm danh và nhận thưởng mỗi ngày"
          title="Điểm danh nhận xu"
        /> : null}
        <AccountMenuRow
          icon={Lightbulb}
          iconBackground={softOrange}
          iconColor="#f59e0b"
          onPress={() => navigateTo("/(tabs)/home/tips")}
          subtitle={payoutFeaturesEnabled ? "Mẹo tăng khả năng đơn được ghi nhận" : "Mẹo mua sắm an toàn và sử dụng mã giảm giá"}
          title="Tips & Trick"
        />
        <AccountMenuRow
          icon={CircleHelp}
          iconBackground={softBlue}
          iconColor="#2f9af5"
          onPress={() => navigateTo("/(tabs)/account/guide")}
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
          onPress={() => void openExternal(legalUrls.support, "Hỗ trợ Mê Sale")}
          subtitle="Liên hệ bộ phận hỗ trợ Mê Sale"
          title="Hỗ trợ Mê Sale"
        />
        <AccountMenuRow
          icon={ShieldCheck}
          iconBackground={softBlue}
          iconColor="#2f9af5"
          onPress={() => void openExternal(legalUrls.privacy, "Chính sách bảo mật")}
          title="Chính sách bảo mật"
        />
        <AccountMenuRow
          icon={FileText}
          iconBackground={softBlue}
          iconColor="#2f9af5"
          onPress={() => void openExternal(legalUrls.terms, "Điều khoản sử dụng")}
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

      <Text style={[styles.disclosure, { color: colors.mutedText }]}>{payoutFeaturesEnabled
        ? "Mê Sale là ứng dụng hoàn tiền độc lập, không phải sản phẩm chính thức của Shopee, TikTok Shop hoặc Lazada."
        : "Mê Sale hỗ trợ khám phá sản phẩm và ưu đãi, không phải ứng dụng chính thức của Shopee, TikTok Shop hoặc Lazada."}</Text>
    </ScrollView>
  );
}

function AccountPayoutSection({ accountWallet, navigateTo }: { accountWallet: AccountPreview["wallet"]; navigateTo: (href: Href) => void }) {
  const { colors, scheme } = useTheme();
  const paymentAccountsQuery = usePaymentAccounts();
  const hasConfirmedNoPaymentAccount = paymentAccountsQuery.isSuccess && paymentAccountsQuery.data?.total === 0;

  return (
    <>
      <CompactBlueHero
        icon={CreditCard}
        subtitle="Số dư khả dụng"
        title={formatKnownMoney(accountWallet?.balance)}
      >
        <View style={styles.walletStats}>
          <View style={styles.walletStat}>
            <Text style={styles.walletStatLabel}>Tổng đã nhận</Text>
            <Text numberOfLines={1} style={styles.walletStatValue}>{formatKnownMoney(accountWallet?.total_cashback)}</Text>
          </View>
          <View style={styles.walletStatDivider} />
          <View style={styles.walletStat}>
            <Text style={styles.walletStatLabel}>Từ giới thiệu</Text>
            <Text numberOfLines={1} style={styles.walletStatValue}>{formatKnownMoney(accountWallet?.total_referral_earned)}</Text>
          </View>
        </View>
        <Pressable
          accessibilityLabel="Rút tiền về ngân hàng"
          accessibilityRole="button"
          onPress={() => navigateTo("/(tabs)/withdraw")}
          style={({ pressed }) => [styles.withdrawButton, pressed && styles.pressed]}
        >
          <ArrowDownCircle color="#197ddd" size={22} />
          <Text style={styles.withdrawButtonText}>Rút tiền về ngân hàng</Text>
        </Pressable>
      </CompactBlueHero>

      {hasConfirmedNoPaymentAccount ? (
        <View
          style={[styles.bankWarning, { backgroundColor: scheme === "dark" ? "#33290f" : "#fffbea", borderColor: scheme === "dark" ? "#8a6c10" : "#f5d666" }]}
        >
          <View
            style={[styles.warningIcon, { backgroundColor: scheme === "dark" ? "#5b4510" : "#fff2bd" }]}
          >
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
    </>
  );
}

const styles = StyleSheet.create({
  content: { gap: 18, paddingHorizontal: 18 },
  accountSyncWarning: { alignItems: "center", borderRadius: 18, borderWidth: 1, flexDirection: "row", gap: 12, padding: 13 },
  accountSyncCopy: { flex: 1, gap: 3, minWidth: 0 },
  accountSyncTitle: { fontSize: 13, fontWeight: "900" },
  accountSyncMessage: { fontSize: 11, lineHeight: 16 },
  accountSyncRetry: { alignItems: "center", backgroundColor: "#f59e0b", borderRadius: 999, justifyContent: "center", minHeight: 44, paddingHorizontal: 14 },
  accountSyncRetryText: { color: "#ffffff", fontSize: 12, fontWeight: "900" },
  profileHeader: { alignItems: "center", flexDirection: "row" },
  profileIdentity: { alignItems: "center", flex: 1, flexDirection: "row", gap: 12, minHeight: 58, minWidth: 0 },
  avatarRing: { alignItems: "center", borderColor: "#75baff", borderRadius: 34, borderWidth: 3, height: 68, justifyContent: "center", width: 68 },
  avatar: { alignItems: "center", borderRadius: 28, height: 56, justifyContent: "center", overflow: "hidden", width: 56 },
  avatarImage: { height: 56, width: 56 },
  avatarText: { color: "#2f9af5", fontSize: 23, fontWeight: "900" },
  identityCopy: { flex: 1, gap: 3, minWidth: 0 },
  profileName: { fontSize: 19, fontWeight: "900" },
  profileEmail: { fontSize: 13 },
  walletStats: { flexDirection: "row", marginBottom: 12 },
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
  referralEntryPanel: { borderBottomWidth: StyleSheet.hairlineWidth, gap: 10, paddingBottom: 14, paddingHorizontal: 14 },
  referralEntryDeadline: { fontSize: 11, lineHeight: 17 },
  referralEntryControls: { alignItems: "center", flexDirection: "row", gap: 9 },
  referralEntryInput: { borderRadius: 13, borderWidth: 1, flex: 1, fontSize: 14, minHeight: 48, paddingHorizontal: 13 },
  referralEntryButton: { alignItems: "center", backgroundColor: "#2f9af5", borderRadius: 13, justifyContent: "center", minHeight: 48, minWidth: 104, paddingHorizontal: 14 },
  referralEntryButtonText: { color: "#ffffff", fontSize: 13, fontWeight: "900" },
  disabledAction: { opacity: 0.45 },
  primaryMenuStack: { gap: 10 },
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
