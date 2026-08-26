import { type ReactNode, useState } from "react";
import {
  Alert,
  Image,
  Keyboard,
  KeyboardAvoidingView,
  Linking,
  Platform,
  Pressable,
  RefreshControl,
  ScrollView,
  Share,
  StyleSheet,
  Text,
  TextInput,
  View
} from "react-native";
import { useSafeAreaInsets } from "react-native-safe-area-context";
import { router } from "expo-router";
import * as Clipboard from "expo-clipboard";
import { LinearGradient } from "expo-linear-gradient";
import {
  Banknote,
  Bell,
  CalendarDays,
  ChevronDown,
  ChevronRight,
  Copy,
  Gift,
  Hourglass,
  Lightbulb,
  Link2,
  MessageCircle,
  Share2,
  ShoppingBag,
  Ticket,
  TrendingUp,
  WalletCards
} from "lucide-react-native";
import { ApiError } from "@/api/client";
import { useAuth } from "@/auth/AuthProvider";
import { useIosPayoutFeaturesEnabled } from "@/config/features";
import { EmptyState, ErrorState, LoadingState, OfflineState } from "@/components/AsyncState";
import { getDeviceLocale, resolveLocale } from "@/i18n";
import { useTheme } from "@/theme/ThemeProvider";
import { colors, spacing } from "@/theme/tokens";
import { normalizeAffiliateUrl, normalizeProductUrl } from "@/features/home/api";
import { createHomeAuthPreview } from "@/features/home/bootstrap";
import { formatAccountMoney } from "@/features/home/format";
import {
  useAccountSummary,
  useCreateCashbackLink,
  useHomeConfig
} from "@/features/home/hooks";
import { PhoneFlowDemo } from "@/features/home/PhoneFlowDemo";
import { QuickAccessPrefetch } from "@/features/home/QuickAccessPrefetch";
import type { CashbackProduct, HomeConfig, Marketplace } from "@/features/home/types";

const copy = {
  vi: {
    loading: "Đang tải trang chủ...",
    offlineTitle: "Không có kết nối",
    offlineMessage: "Không thể tải dữ liệu tài khoản. Hãy kiểm tra mạng rồi thử lại.",
    errorTitle: "Chưa thể tải trang chủ",
    refreshError: "Dữ liệu đang hiển thị có thể đã cũ vì lần làm mới gần nhất thất bại.",
    emptyTitle: "Không có dữ liệu tài khoản",
    emptyMessage: "Máy chủ chưa trả về thông tin tài khoản này.",
    retry: "Thử lại",
    greeting: "Chào",
    availableBalance: "Số dư ví",
    withdraw: "Rút tiền",
    totalCashback: "Tổng đã nhận",
    pending: "Chờ duyệt",
    pendingLoading: "Đang tải số tiền chờ duyệt",
    totalWithdrawn: "Tổng đã rút",
    creatorPromo: "Hoàn tiền mua sắm Shopee - Tiktok Shop lên đến 15% giá trị đơn hàng",
    supported: "Nền tảng hỗ trợ:",
    quickAccess: "Truy cập nhanh",
    huntCoupons: "Săn mã",
    dailyCheckin: "Điểm danh",
    tips: "Tips & Trick",
    support: "Hỗ trợ",
    supportError: "Không thể mở trang hỗ trợ lúc này.",
    configLoading: "Đang kiểm tra trạng thái các sàn...",
    configOffline: "Chưa thể kiểm tra trạng thái sàn vì thiết bị đang offline.",
    configError: "Chưa thể tải cấu hình sàn. Tính năng tạo link tạm khóa để bảo đảm an toàn.",
    featureDisabled: "Tính năng tạo link hoàn tiền đang tạm bảo trì.",
    noMarketplace: "Hiện chưa có sàn nào được bật trên máy chủ.",
    urlPlaceholder: "Dán link sản phẩm Shopee - TikTok tại đây...",
    urlRequired: "Vui lòng nhập link sản phẩm.",
    urlInvalid: "Link sản phẩm không đúng định dạng.",
    referralCtaTitle: "Rủ bạn dùng Mê Sale",
    referralCtaSubtitle: "Chia sẻ mã giới thiệu, nhận hoa hồng!",
    referralCtaAction: "Mời bạn",
    productReady: "Sản phẩm có thể nhận hoàn tiền",
    currentPrice: "Giá sản phẩm",
    estimatedCashback: "Ước tính hoàn tiền đơn này",
    estimatedRate: "Tỷ lệ hoàn ước tính",
    estimatedNote: "Mức hoàn thực tế được xác nhận sau khi sàn ghi nhận đơn hợp lệ.",
    affiliateLink: "Link mua hàng đã tạo",
    notice: "Chi tiết tính toán",
    openMarketplace: "Mua ngay",
    shareLink: "Chia sẻ",
    handoffTitle: "Rời Mê Sale?",
    handoffMessage: "Bạn sẽ mở ứng dụng hoặc website của sàn. Hãy mua hàng qua đúng liên kết này để hệ thống ghi nhận cashback.",
    cancel: "Ở lại",
    continue: "Mở sàn",
    handoffError: "Không thể mở liên kết mua hàng an toàn trên thiết bị này.",
    bannerLinkError: "Không thể mở liên kết của banner này trên thiết bị.",
    mutationOffline: "Không thể phân tích link khi thiết bị mất kết nối.",
    mutationError: "Không thể tạo link hoàn tiền lúc này.",
    resultEmpty: "Kết quả sản phẩm sẽ hiển thị tại đây sau khi bạn phân tích link.",
    reference: "Mã đối soát",
    rateSuffix: "% hoàn tiền"
  },
  en: {
    loading: "Loading home...",
    offlineTitle: "You're offline",
    offlineMessage: "We could not load your account. Check your connection and retry.",
    errorTitle: "Home is unavailable",
    refreshError: "The visible data may be stale because the latest refresh failed.",
    emptyTitle: "Account data is empty",
    emptyMessage: "The server did not return wallet data for this account.",
    retry: "Retry",
    greeting: "Hello",
    availableBalance: "Wallet balance",
    withdraw: "Withdraw",
    totalCashback: "Total received",
    pending: "Pending",
    pendingLoading: "Loading pending cashback",
    totalWithdrawn: "Total withdrawn",
    creatorPromo: "Get up to 15% cashback on the value of Shopee - TikTok Shop orders",
    supported: "Supported platforms:",
    quickAccess: "Quick access",
    huntCoupons: "Find coupons",
    dailyCheckin: "Daily check-in",
    tips: "Tips & Trick",
    support: "Support",
    supportError: "The support page cannot be opened right now.",
    configLoading: "Checking marketplace availability...",
    configOffline: "Marketplace availability cannot be checked while offline.",
    configError: "Marketplace configuration is unavailable. Link creation is locked for safety.",
    featureDisabled: "Cashback-link creation is under maintenance.",
    noMarketplace: "No marketplace is currently enabled by the server.",
    urlPlaceholder: "Paste a Shopee or TikTok product link here...",
    urlRequired: "Enter a product URL.",
    urlInvalid: "Enter a valid product URL.",
    referralCtaTitle: "Invite friends to Mê Sale",
    referralCtaSubtitle: "Share your referral code and earn commission!",
    referralCtaAction: "Invite",
    productReady: "Product may be eligible for cashback",
    currentPrice: "Current price",
    estimatedCashback: "Estimated cashback for this order",
    estimatedRate: "Estimated cashback rate",
    estimatedNote: "The final cashback is confirmed after the marketplace records an eligible order.",
    affiliateLink: "Generated purchase link",
    notice: "Calculation details",
    openMarketplace: "Buy now",
    shareLink: "Share",
    handoffTitle: "Leave Mesale?",
    handoffMessage: "This opens the marketplace app or website. Complete the purchase through this exact link so cashback can be tracked.",
    cancel: "Stay",
    continue: "Open",
    handoffError: "This purchase link cannot be opened safely on this device.",
    bannerLinkError: "This banner link cannot be opened on this device.",
    mutationOffline: "A product link cannot be analyzed while offline.",
    mutationError: "The cashback link could not be created.",
    resultEmpty: "Your product result will appear here after the link is analyzed.",
    reference: "Tracking reference",
    rateSuffix: "% cashback"
  }
} as const;

type Strings = { [Key in keyof typeof copy.vi]: string };

const platformPresentation: Record<Marketplace, { label: string; color: string }> = {
  shopee: { label: "Shopee", color: "#ee4d2d" },
  tiktok: { label: "TikTok Shop", color: "#111827" },
  lazada: { label: "Lazada", color: "#0f146d" }
};

const SUPPORT_URL = "https://zalo.me/g/rb0b31ft7erer5slrcqb";
const RESULT_BLUE = "#2f93f5";

function isOfflineError(error: unknown): boolean {
  return error instanceof ApiError && (error.isNetworkError || error.isTimeout || error.status === 0);
}

function errorMessage(error: unknown, fallback: string): string {
  return error instanceof Error && error.message.trim().length > 0 ? error.message : fallback;
}

function formatVnd(value: number, language: "vi" | "en"): string {
  return new Intl.NumberFormat(language === "vi" ? "vi-VN" : "en-US", {
    style: "currency",
    currency: "VND",
    maximumFractionDigits: 0
  }).format(value);
}

function secureRemoteUri(value: string | null): string | null {
  if (!value) return null;
  try {
    const url = new URL(value);
    if (url.protocol === "http:") url.protocol = "https:";
    return url.protocol === "https:" ? url.toString() : null;
  } catch {
    return null;
  }
}

function stripMarkup(value: string | null): string | null {
  if (!value) return null;
  const text = value.replace(/<[^>]*>/g, " ").replace(/&nbsp;/gi, " ").replace(/\s+/g, " ").trim();
  return text.length > 0 ? text : null;
}

function InlineNotice({ message, tone = "neutral", onRetry, strings }: {
  message: string;
  tone?: "neutral" | "danger" | "warning";
  onRetry?: () => void;
  strings: Strings;
}) {
  const { colors } = useTheme();
  const toneColors = tone === "danger"
    ? { backgroundColor: `${colors.danger}14`, borderColor: `${colors.danger}55` }
    : tone === "warning"
      ? { backgroundColor: `${colors.primary}12`, borderColor: `${colors.primary}45` }
      : { backgroundColor: colors.background, borderColor: colors.border };

  return (
    <View accessibilityRole={tone === "danger" ? "alert" : undefined} style={[
      styles.notice,
      toneColors
    ]}>
      <Text style={[styles.noticeText, { color: colors.mutedText }]}>{message}</Text>
      {onRetry ? (
        <Pressable accessibilityRole="button" onPress={onRetry} style={styles.noticeAction}>
          <Text style={styles.noticeActionText}>{strings.retry}</Text>
        </Pressable>
      ) : null}
    </View>
  );
}

function AccountStat({ icon, label, loadingLabel, value }: {
  icon: ReactNode;
  label: string;
  loadingLabel?: string;
  value: string | null;
}) {
  const { colors } = useTheme();

  return (
    <View style={[styles.accountStatCard, { backgroundColor: colors.surface, borderColor: colors.border }]}>
      {icon}
      {value === null ? (
        <View
          accessibilityLabel={loadingLabel}
          accessibilityRole="progressbar"
          style={[styles.accountStatValueSkeleton, { backgroundColor: colors.border }]}
        />
      ) : (
        <Text adjustsFontSizeToFit numberOfLines={1} style={[styles.accountStatValue, { color: colors.text }]}>{value}</Text>
      )}
      <Text numberOfLines={2} style={[styles.accountStatLabel, { color: colors.mutedText }]}>{label}</Text>
    </View>
  );
}

function QuickAccessItem({ icon, label, onPress, role = "button" }: {
  icon: ReactNode;
  label: string;
  onPress: () => void;
  role?: "button" | "link";
}) {
  const { colors } = useTheme();

  return (
    <Pressable
      accessibilityLabel={label}
      accessibilityRole={role}
      onPress={onPress}
      style={({ pressed }) => [styles.quickAccessItem, pressed && styles.pressed]}
    >
      {icon}
      <Text numberOfLines={2} style={[styles.quickAccessLabel, { color: colors.text }]}>{label}</Text>
    </Pressable>
  );
}

function QuickAccessSection({ language, onTips, onSupport }: {
  language: "vi" | "en";
  onTips: () => void;
  onSupport: () => void;
}) {
  const { colors } = useTheme();
  const strings = copy[language];
  const payoutFeaturesEnabled = useIosPayoutFeaturesEnabled();

  return (
    <View style={styles.quickAccessSection}>
      <Text accessibilityRole="header" style={[styles.quickAccessTitle, { color: colors.text }]}>{strings.quickAccess}</Text>
      <View style={styles.quickAccessRow}>
        <QuickAccessItem
          icon={<View style={[styles.quickAccessIcon, { backgroundColor: "#fef2f2" }]}><Ticket color="#ef4444" size={23} strokeWidth={2.1} /></View>}
          label={strings.huntCoupons}
          onPress={() => router.push("/(tabs)/home/coupons")}
        />
        {payoutFeaturesEnabled ? <QuickAccessItem
          icon={<View style={[styles.quickAccessIcon, { backgroundColor: "#eff6ff" }]}><CalendarDays color="#3b82f6" size={23} strokeWidth={2.1} /></View>}
          label={strings.dailyCheckin}
          onPress={() => router.push("/(tabs)/earn/checkin")}
        /> : null}
        <QuickAccessItem
          icon={<View style={[styles.quickAccessIcon, { backgroundColor: "#fff7ed" }]}><Lightbulb color="#f97316" size={23} strokeWidth={2.1} /></View>}
          label={strings.tips}
          onPress={onTips}
        />
        <QuickAccessItem
          icon={<View style={[styles.quickAccessIcon, { backgroundColor: "#ecfdf5" }]}><MessageCircle color="#16a34a" size={23} strokeWidth={2.1} /></View>}
          label={strings.support}
          onPress={onSupport}
          role="link"
        />
      </View>
    </View>
  );
}

function PlatformBadges({ config }: { config: HomeConfig }) {
  const enabled = (Object.keys(platformPresentation) as Marketplace[])
    .filter((platform) => config.marketplaces[platform].enabled);

  return (
    <View style={styles.badgeRow}>
      {enabled.map((platform) => {
        const presentation = platformPresentation[platform];
        return (
          <View key={platform} style={[styles.platformBadge, { backgroundColor: presentation.color }]}>
            <ShoppingBag color="#ffffff" size={12} strokeWidth={2.4} />
            <Text style={styles.platformBadgeText}>{presentation.label}</Text>
          </View>
        );
      })}
    </View>
  );
}

function ProductImage({ uri, name }: { uri: string | null; name: string }) {
  const [failed, setFailed] = useState(false);
  const { colors } = useTheme();
  const secureUri = secureRemoteUri(uri);

  return (
    <View style={[styles.productImageFrame, { backgroundColor: colors.background, borderColor: colors.border }]}>
      {secureUri && !failed ? (
        <Image
          accessibilityLabel={name}
          onError={() => setFailed(true)}
          resizeMode="cover"
          source={{ uri: secureUri }}
          style={styles.productImage}
        />
      ) : (
        <Text accessibilityLabel="Product image unavailable" style={[styles.productImageFallback, { color: colors.mutedText }]}>IMG</Text>
      )}
    </View>
  );
}

function ProductResult({ product, language, notice, strings }: {
  product: CashbackProduct;
  language: "vi" | "en";
  notice?: string | null;
  strings: Strings;
}) {
  const [handoffError, setHandoffError] = useState<string | null>(null);
  const [detailsOpen, setDetailsOpen] = useState(false);
  const { colors } = useTheme();
  const marketplaceNotice = stripMarkup(notice ?? null);
  const commissionRate = product.price > 0 ? (product.commissionAmount / product.price) * 100 : 0;
  const commissionSummary = product.isEstimated
    ? `${product.cashbackRate}${strings.rateSuffix}`
    : `${language === "vi" ? "Tỷ lệ hoàn" : "Cashback rate"} ${commissionRate.toLocaleString(language === "vi" ? "vi-VN" : "en-US", { maximumFractionDigits: 1 })}%`;
  const totalLabel = product.isEstimated ? strings.estimatedRate : strings.estimatedCashback;

  async function openMarketplace() {
    setHandoffError(null);
    const affiliateUrl = normalizeAffiliateUrl(product.affiliateUrl);
    if (!affiliateUrl) {
      setHandoffError(strings.handoffError);
      return;
    }

    try {
      // HTTPS preserves affiliate attribution and lets a verified marketplace
      // app-link open the native app, with the system browser as fallback.
      await Linking.openURL(affiliateUrl);
    } catch {
      if (Platform.OS === "android") {
        try {
          // Some Android builds reject nested redirect URLs through openURL.
          // A VIEW intent retries the same HTTPS URL without weakening validation.
          const IntentLauncher = await import("expo-intent-launcher");
          await IntentLauncher.startActivityAsync("android.intent.action.VIEW", { data: affiliateUrl });
          return;
        } catch {
          // Show the existing inline error after both safe external routes fail.
        }
      }
      setHandoffError(strings.handoffError);
    }
  }

  async function shareAffiliateLink() {
    try {
      await Share.share({ message: product.affiliateUrl });
    } catch {
      setHandoffError(strings.handoffError);
    }
  }

  return (
    <View style={[styles.resultCard, { backgroundColor: colors.surface, borderColor: colors.border }]}>
      <View style={styles.productRow}>
        <ProductImage key={product.image ?? product.transId} name={product.name} uri={product.image} />
        <View style={styles.productBody}>
          <Text numberOfLines={3} style={[styles.productName, { color: colors.text }]}>{product.name}</Text>
          {product.price > 0 ? <Text style={[styles.productPrice, { color: RESULT_BLUE }]}>{formatVnd(product.price, language)}</Text> : null}
          <Text numberOfLines={1} style={[styles.productRate, { color: colors.mutedText }]}>{commissionSummary}</Text>
        </View>
      </View>

      <LinearGradient colors={["#36a4ff", "#176fe5"]} end={{ x: 1, y: 0 }} start={{ x: 0, y: 0 }} style={styles.cashbackTotal}>
        <View style={styles.cashbackTotalCopy}>
          <Text style={styles.cashbackTotalLabel}>{totalLabel}</Text>
          <Text adjustsFontSizeToFit numberOfLines={1} style={styles.cashbackTotalValue}>
            {product.isEstimated ? `~${product.cashbackRate}%` : formatVnd(product.commissionAmount, language)}
          </Text>
        </View>
      </LinearGradient>

      <Pressable
        accessibilityRole="button"
        accessibilityState={{ expanded: detailsOpen }}
        onPress={() => setDetailsOpen((open) => !open)}
        style={({ pressed }) => [styles.detailsToggle, pressed && styles.pressed]}
      >
        <ChevronDown color={RESULT_BLUE} size={17} strokeWidth={2.4} style={detailsOpen ? styles.detailsChevronOpen : undefined} />
        <Text style={[styles.detailsToggleText, { color: RESULT_BLUE }]}>{strings.notice}</Text>
      </Pressable>

      {detailsOpen ? (
        <View style={[styles.detailsPanel, { backgroundColor: colors.background, borderColor: colors.border }]}>
          <View style={styles.detailRow}><Text style={[styles.detailLabel, { color: colors.mutedText }]}>{strings.reference}</Text><Text selectable style={[styles.detailValue, { color: colors.text }]}>{product.transId}</Text></View>
          {product.price > 0 ? <View style={styles.detailRow}><Text style={[styles.detailLabel, { color: colors.mutedText }]}>{strings.currentPrice}</Text><Text style={[styles.detailValue, { color: colors.text }]}>{formatVnd(product.price, language)}</Text></View> : null}
          {!product.isEstimated ? <View style={styles.detailRow}><Text style={[styles.detailLabel, { color: colors.mutedText }]}>{strings.estimatedCashback}</Text><Text style={[styles.detailValue, { color: colors.text }]}>{formatVnd(product.commissionAmount, language)}</Text></View> : null}
          {!product.isEstimated ? <View style={styles.detailRow}><Text style={[styles.detailLabel, { color: colors.mutedText }]}>{language === "vi" ? "Ước tính hoàn tiền của bạn" : "Your estimated cashback"}</Text><Text style={[styles.detailValue, { color: colors.text }]}>{formatVnd(product.cashbackAmount, language)}</Text></View> : null}
          <View style={styles.detailRow}><Text style={[styles.detailLabel, { color: colors.mutedText }]}>{strings.estimatedRate}</Text><Text style={[styles.detailValue, { color: colors.text }]}>{product.cashbackRate}{strings.rateSuffix}</Text></View>
          {marketplaceNotice ? <Text style={[styles.detailNotice, { color: colors.mutedText }]}>{marketplaceNotice}</Text> : null}
          <Text style={[styles.detailNotice, { color: colors.mutedText }]}>{strings.estimatedNote}</Text>
        </View>
      ) : null}

      {handoffError ? <InlineNotice message={handoffError} tone="danger" strings={strings} /> : null}

      <View style={styles.resultActions}>
        <Pressable accessibilityRole="button" onPress={() => void openMarketplace()} style={({ pressed }) => [styles.primaryButton, styles.resultAction, pressed && styles.pressed]}>
          <ShoppingBag color="#ffffff" size={18} strokeWidth={2.2} />
          <Text adjustsFontSizeToFit minimumFontScale={0.72} numberOfLines={1} style={styles.primaryButtonText}>{strings.openMarketplace}</Text>
        </Pressable>
        <Pressable
          accessibilityRole="button"
          onPress={() => void shareAffiliateLink()}
          style={({ pressed }) => [styles.secondaryButton, styles.resultAction, { backgroundColor: colors.surface, borderColor: RESULT_BLUE }, pressed && styles.pressed]}
        >
          <Share2 color={RESULT_BLUE} size={18} strokeWidth={2.2} />
          <Text adjustsFontSizeToFit minimumFontScale={0.72} numberOfLines={1} style={[styles.secondaryButtonText, { color: RESULT_BLUE }]}>{strings.shareLink}</Text>
        </Pressable>
      </View>
    </View>
  );
}

export function HomeScreen() {
  const insets = useSafeAreaInsets();
  const { user } = useAuth();
  const { colors: themeColors, scheme } = useTheme();
  // Keep Vietnamese as the default while honoring an explicit account choice.
  const language = resolveLocale(user?.preferences?.locale ?? getDeviceLocale());
  const strings = copy[language];
  const payoutFeaturesEnabled = useIosPayoutFeaturesEnabled();
  const accountQuery = useAccountSummary();
  const configQuery = useHomeConfig();
  const cashbackMutation = useCreateCashbackLink();
  const [productUrl, setProductUrl] = useState("");
  const [inputError, setInputError] = useState<string | null>(null);
  const [lastSubmittedUrl, setLastSubmittedUrl] = useState<string | null>(null);
  const authPreview = createHomeAuthPreview(user);
  const account = accountQuery.data ?? authPreview;

  if (accountQuery.isPending && !account) return <LoadingState label={strings.loading} />;
  if (accountQuery.isError && !account) {
    const State = isOfflineError(accountQuery.error) ? OfflineState : ErrorState;
    return (
      <State
        actionLabel={strings.retry}
        message={isOfflineError(accountQuery.error)
          ? strings.offlineMessage
          : errorMessage(accountQuery.error, strings.errorTitle)}
        onAction={() => void accountQuery.refetch()}
        title={isOfflineError(accountQuery.error) ? strings.offlineTitle : strings.errorTitle}
      />
    );
  }
  if (!account) {
    return <EmptyState message={strings.emptyMessage} title={strings.emptyTitle} />;
  }

  const pendingCashbackValue = accountQuery.data
    ? formatAccountMoney(accountQuery.data.wallet.pendingCashback, language)
    : null;
  const config = configQuery.data;
  const enabledMarketplaces = config
    ? (Object.keys(config.marketplaces) as Marketplace[]).filter((key) => config.marketplaces[key].enabled)
    : [];
  const creationDisabled = configQuery.isPending
    || configQuery.isError
    || !config?.cashbackLinkEnabled
    || enabledMarketplaces.length === 0;
  const refreshing = accountQuery.isRefetching || configQuery.isRefetching;

  function submitProductUrl(value: string = productUrl) {
    const validation = normalizeProductUrl(value);
    if (!validation.valid) {
      setInputError(validation.reason === "required" ? strings.urlRequired : strings.urlInvalid);
      return;
    }
    setInputError(null);
    setLastSubmittedUrl(validation.url);
    setProductUrl(validation.url);
    Keyboard.dismiss();
    cashbackMutation.mutate(validation.url);
  }

  async function pasteProductUrl() {
    const value = (await Clipboard.getStringAsync()).trim();
    if (!value) return;
    setProductUrl(value);
    setInputError(null);
    // A paste is the primary mobile action: validate and analyze immediately.
    submitProductUrl(value);
  }

  const mutationMessage = cashbackMutation.isError
    ? (isOfflineError(cashbackMutation.error)
      ? strings.mutationOffline
      : errorMessage(cashbackMutation.error, strings.mutationError))
    : null;
  const displayName = account.name?.trim() || user?.name?.trim() || (language === "vi" ? "bạn" : "there");

  async function openSupport() {
    void Linking.openURL(SUPPORT_URL).catch(() => Alert.alert(strings.support, strings.supportError));
  }

  return (
    <KeyboardAvoidingView
      behavior={Platform.OS === "ios" ? "padding" : undefined}
      style={[styles.screen, { backgroundColor: themeColors.background }]}
    >
      <QuickAccessPrefetch />
      <ScrollView
        contentContainerStyle={[
          styles.content,
          {
            paddingTop: insets.top + spacing.sm,
            paddingBottom: insets.bottom + spacing.xl,
            paddingLeft: insets.left + spacing.md,
            paddingRight: insets.right + spacing.md
          }
        ]}
        contentInsetAdjustmentBehavior="automatic"
        keyboardShouldPersistTaps="handled"
        refreshControl={(
          <RefreshControl
            onRefresh={() => {
              void accountQuery.refetch();
              void configQuery.refetch();
            }}
            refreshing={refreshing}
            tintColor={themeColors.primary}
          />
        )}
      >
        {accountQuery.isError ? (
          <InlineNotice
            message={strings.refreshError}
            onRetry={() => void accountQuery.refetch()}
            strings={strings}
            tone="warning"
          />
        ) : null}

        <View style={styles.accountSummary}>
          <View style={styles.accountGreetingRow}>
            <View style={styles.accountGreetingIdentity}>
              <Image
                accessibilityLabel="Mê Sale"
                resizeMode="contain"
                source={require("../../../assets/mesale-logo.png")}
                style={styles.accountGreetingLogo}
              />
              <Text accessibilityRole="header" style={[styles.accountGreeting, { color: themeColors.text }]}>
                {strings.greeting} {displayName} 👋
              </Text>
            </View>
            <Pressable
              accessibilityLabel={language === "vi" ? "Thông báo" : "Notifications"}
              accessibilityRole="button"
              hitSlop={6}
              onPress={() => router.push("/(tabs)/inbox")}
              style={({ pressed }) => [
                styles.accountNotificationButton,
                { backgroundColor: themeColors.surface, borderColor: themeColors.border },
                pressed && styles.pressed
              ]}
            >
              <Bell color={themeColors.text} size={19} strokeWidth={2} />
            </Pressable>
          </View>

          {payoutFeaturesEnabled ? <>
          <LinearGradient
            colors={["#59a5fa", "#356dd3"]}
            end={{ x: 1, y: 1 }}
            start={{ x: 0, y: 0 }}
            style={styles.accountBalanceCard}
          >
            <View style={styles.accountBalanceCopy}>
              <Text style={styles.accountBalanceLabel}>{strings.availableBalance}</Text>
              <Text
                accessibilityLabel={`${strings.availableBalance}: ${formatAccountMoney(account.wallet.balance, language)}`}
                adjustsFontSizeToFit
                numberOfLines={1}
                style={styles.accountBalanceValue}
              >
                {formatAccountMoney(account.wallet.balance, language)}
              </Text>
            </View>
            <Pressable
              accessibilityLabel={strings.withdraw}
              accessibilityRole="button"
              onPress={() => router.push("/(tabs)/withdraw")}
              style={({ pressed }) => [styles.accountWithdrawButton, pressed && styles.pressed]}
            >
              <Banknote color="#3b82f6" size={17} strokeWidth={2.2} />
              <Text style={styles.accountWithdrawText}>{strings.withdraw}</Text>
            </Pressable>
          </LinearGradient>

          <View style={styles.accountStatsRow}>
            <AccountStat
              icon={<TrendingUp color="#16a34a" size={20} strokeWidth={2.2} />}
              label={strings.totalCashback}
              value={formatAccountMoney(account.wallet.totalCashback, language)}
            />
            <AccountStat
              icon={<Hourglass color="#f59e0b" size={20} strokeWidth={2.2} />}
              label={strings.pending}
              loadingLabel={strings.pendingLoading}
              value={pendingCashbackValue}
            />
            <AccountStat
              icon={<WalletCards color="#8b5cf6" size={20} strokeWidth={2.2} />}
              label={strings.totalWithdrawn}
              value={formatAccountMoney(account.wallet.totalWithdrawn, language)}
            />
          </View>
          </> : null}
        </View>

        <View style={[styles.creatorCard, { backgroundColor: themeColors.surface, borderColor: scheme === "dark" ? themeColors.border : "#fed7c7" }]}>
          <Text style={[styles.creatorPromo, { color: themeColors.text }]}>{strings.creatorPromo}</Text>
          <View style={styles.supportBlock}>
            <Text style={[styles.supportLabel, { color: themeColors.mutedText }]}>{strings.supported}</Text>
            {config ? <PlatformBadges config={config} /> : null}
          </View>

          {configQuery.isPending ? <InlineNotice message={strings.configLoading} strings={strings} /> : null}
          {configQuery.isError ? (
            <InlineNotice
              message={isOfflineError(configQuery.error) ? strings.configOffline : strings.configError}
              onRetry={() => void configQuery.refetch()}
              strings={strings}
              tone="warning"
            />
          ) : null}
          {config && !config.cashbackLinkEnabled ? (
            <InlineNotice message={strings.featureDisabled} strings={strings} tone="warning" />
          ) : null}
          {config?.cashbackLinkEnabled && enabledMarketplaces.length === 0 ? (
            <InlineNotice message={strings.noMarketplace} strings={strings} tone="warning" />
          ) : null}

          <View style={[styles.inputFrame, { backgroundColor: themeColors.background, borderColor: inputError ? themeColors.danger : themeColors.border }]}>
            <Link2 color={themeColors.mutedText} size={19} strokeWidth={2.1} />
            <TextInput
              accessibilityLabel={strings.urlPlaceholder}
              autoCapitalize="none"
              autoCorrect={false}
              editable={!creationDisabled && !cashbackMutation.isPending}
              keyboardType="url"
              onChangeText={(value) => {
                setProductUrl(value);
                if (inputError) setInputError(null);
              }}
              onSubmitEditing={() => submitProductUrl()}
              placeholder={strings.urlPlaceholder}
              placeholderTextColor={themeColors.mutedText}
              returnKeyType="search"
              style={[styles.input, { color: themeColors.text }]}
              value={productUrl}
            />
            <Pressable
              accessibilityHint={language === "vi" ? "Dán nội dung đang có trong bộ nhớ tạm" : "Paste the current clipboard contents"}
              accessibilityLabel={language === "vi" ? "Dán link" : "Paste link"}
              accessibilityRole="button"
              disabled={creationDisabled || cashbackMutation.isPending}
              hitSlop={8}
              onPress={() => void pasteProductUrl()}
              style={({ pressed }) => [styles.pasteButton, pressed && styles.pressed]}
            >
              <Copy color={themeColors.mutedText} size={18} strokeWidth={2.1} />
            </Pressable>
          </View>
          {inputError ? <Text accessibilityRole="alert" style={[styles.fieldError, { color: themeColors.danger }]}>{inputError}</Text> : null}

          {cashbackMutation.data ? (
            <ProductResult
              key={cashbackMutation.data.transId}
              language={language}
              notice={config?.marketplaces[cashbackMutation.data.platform].notice}
              product={cashbackMutation.data}
              strings={strings}
            />
          ) : cashbackMutation.isPending ? (
            <View accessibilityRole="progressbar" style={[styles.resultSkeleton, { backgroundColor: themeColors.surface, borderColor: themeColors.border }]}>
              <View style={[styles.skeletonImage, { backgroundColor: themeColors.border }]} />
              <View style={styles.skeletonBody}>
                <View style={[styles.skeletonLine, styles.skeletonLineLong, { backgroundColor: themeColors.border }]} />
                <View style={[styles.skeletonLine, { backgroundColor: themeColors.border }]} />
                <View style={[styles.skeletonLine, styles.skeletonLineShort, { backgroundColor: themeColors.border }]} />
              </View>
            </View>
          ) : null}

          {payoutFeaturesEnabled ? <Pressable
            accessibilityHint={language === "vi" ? "Mở trang giới thiệu bạn bè" : "Open the referral page"}
            accessibilityLabel={strings.referralCtaTitle}
            accessibilityRole="button"
            onPress={() => router.push("/(tabs)/referrals")}
            style={({ pressed }) => [styles.referralCtaPressable, pressed && styles.pressed]}
          >
            <LinearGradient
              colors={["#ff8a1f", "#ff4d18"]}
              end={{ x: 1, y: 0 }}
              start={{ x: 0, y: 0 }}
              style={styles.referralCta}
            >
              <View style={styles.referralCtaIcon}>
                <Gift color="#ffffff" size={19} strokeWidth={2.3} />
              </View>
              <View style={styles.referralCtaCopy}>
                <Text numberOfLines={1} style={styles.referralCtaTitle}>{strings.referralCtaTitle}</Text>
                <Text numberOfLines={2} style={styles.referralCtaSubtitle}>{strings.referralCtaSubtitle}</Text>
              </View>
              <View style={styles.referralCtaAction}>
                <Text style={styles.referralCtaActionText}>{strings.referralCtaAction}</Text>
                <ChevronRight color="#c2410c" size={14} strokeWidth={2.5} />
              </View>
            </LinearGradient>
          </Pressable> : null}

          {mutationMessage ? (
            <InlineNotice
              message={mutationMessage}
              onRetry={lastSubmittedUrl ? () => submitProductUrl(lastSubmittedUrl) : undefined}
              strings={strings}
              tone="danger"
            />
          ) : null}
        </View>

        <QuickAccessSection
          language={language}
          onSupport={() => void openSupport()}
          onTips={() => router.push("/(tabs)/home/tips")}
        />

        <View style={styles.demoSection}>
          <PhoneFlowDemo />
        </View>
      </ScrollView>
    </KeyboardAvoidingView>
  );
}

const styles = StyleSheet.create({
  demoSection: { marginBottom: spacing.sm },
  screen: { backgroundColor: colors.background, flex: 1 },
  content: { gap: spacing.md },
  accountSummary: { gap: spacing.md },
  accountGreetingRow: { alignItems: "center", flexDirection: "row", gap: spacing.sm, justifyContent: "space-between" },
  accountGreetingIdentity: { alignItems: "center", flex: 1, flexDirection: "row", gap: spacing.sm, minWidth: 0 },
  accountGreetingLogo: { height: 38, width: 38 },
  accountGreeting: { flex: 1, fontSize: 20, fontWeight: "900", lineHeight: 27 },
  accountNotificationButton: {
    alignItems: "center",
    borderRadius: 999,
    borderWidth: 1,
    height: 44,
    justifyContent: "center",
    width: 44
  },
  accountBalanceCard: {
    alignItems: "center",
    borderRadius: 20,
    flexDirection: "row",
    gap: spacing.md,
    justifyContent: "space-between",
    minHeight: 104,
    overflow: "hidden",
    paddingHorizontal: 20,
    paddingVertical: spacing.md
  },
  accountBalanceCopy: { flex: 1, minWidth: 0 },
  accountBalanceLabel: { color: "#dbeafe", fontSize: 12, fontWeight: "600" },
  accountBalanceValue: { color: "#ffffff", fontSize: 31, fontWeight: "900", letterSpacing: -0.7, marginTop: 4 },
  accountWithdrawButton: {
    alignItems: "center",
    backgroundColor: "#ffffff",
    borderRadius: 999,
    flexDirection: "row",
    gap: 6,
    justifyContent: "center",
    minHeight: 44,
    paddingHorizontal: 14
  },
  accountWithdrawText: { color: "#3b82f6", fontSize: 12, fontWeight: "900" },
  accountStatsRow: { flexDirection: "row", gap: 8 },
  accountStatCard: {
    borderRadius: 16,
    borderWidth: 1,
    flex: 1,
    minHeight: 96,
    paddingHorizontal: 9,
    paddingVertical: 11,
    shadowColor: "#0f172a",
    shadowOffset: { height: 3, width: 0 },
    shadowOpacity: 0.04,
    shadowRadius: 8,
    elevation: 1
  },
  accountStatValue: { fontSize: 15, fontWeight: "900", marginTop: 7 },
  accountStatValueSkeleton: { borderRadius: 6, height: 17, marginTop: 8, width: "72%" },
  accountStatLabel: { fontSize: 9.5, fontWeight: "600", lineHeight: 13, marginTop: 2, minHeight: 26 },
  creatorCard: {
    backgroundColor: colors.surface, borderColor: "#fed7c7", borderRadius: 24, borderWidth: 1, gap: spacing.md,
    padding: spacing.lg, shadowColor: "#f97316", shadowOffset: { height: 5, width: 0 }, shadowOpacity: 0.08, shadowRadius: 16, elevation: 3
  },
  creatorPromo: { fontSize: 13, fontWeight: "900", lineHeight: 19 },
  supportBlock: { alignItems: "center", flexDirection: "row", flexWrap: "wrap", gap: 9, justifyContent: "space-between" },
  supportLabel: { color: colors.mutedText, fontSize: 10, fontWeight: "900", letterSpacing: 0.8, textTransform: "uppercase" },
  badgeRow: { flexDirection: "row", flexWrap: "wrap", gap: 7 },
  platformBadge: { alignItems: "center", borderRadius: 999, flexDirection: "row", gap: 5, paddingHorizontal: 11, paddingVertical: 7 },
  platformBadgeText: { color: "#ffffff", fontSize: 11, fontWeight: "900" },
  quickAccessSection: { gap: spacing.sm, paddingVertical: spacing.xs },
  quickAccessTitle: { fontSize: 16, fontWeight: "900" },
  quickAccessRow: { alignItems: "flex-start", flexDirection: "row", gap: 6, justifyContent: "space-between" },
  quickAccessItem: { alignItems: "center", flex: 1, gap: 7, justifyContent: "flex-start", minHeight: 82, minWidth: 0, paddingVertical: 4 },
  quickAccessIcon: { alignItems: "center", borderRadius: 14, height: 50, justifyContent: "center", width: 50 },
  quickAccessLabel: { fontSize: 10.5, fontWeight: "600", lineHeight: 14, textAlign: "center" },
  notice: { alignItems: "center", backgroundColor: "#f1f5f9", borderColor: colors.border, borderRadius: 14, borderWidth: 1, flexDirection: "row", gap: 10, padding: 12 },
  noticeDanger: { backgroundColor: "#fff1f2", borderColor: "#fecdd3" },
  noticeWarning: { backgroundColor: "#fffbeb", borderColor: "#fde68a" },
  noticeText: { color: "#475569", flex: 1, fontSize: 12, lineHeight: 18 },
  noticeAction: { minHeight: 36, justifyContent: "center", paddingHorizontal: 5 },
  noticeActionText: { color: "#ea580c", fontSize: 12, fontWeight: "900" },
  inputFrame: { alignItems: "center", backgroundColor: "#f8fafc", borderColor: colors.border, borderRadius: 16, borderWidth: 1, flexDirection: "row", minHeight: 54, paddingLeft: 13 },
  inputFrameError: { borderColor: colors.danger },
  input: { color: colors.text, flex: 1, fontSize: 13, minHeight: 54, paddingHorizontal: 10, paddingVertical: 12 },
  pasteButton: { alignItems: "center", justifyContent: "center", minHeight: 48, minWidth: 44 },
  fieldError: { color: colors.danger, fontSize: 12, fontWeight: "600", marginTop: -8 },
  primaryButton: { alignItems: "center", backgroundColor: "#2f93f5", borderRadius: 14, flexDirection: "row", gap: 9, justifyContent: "center", minHeight: 50, paddingHorizontal: spacing.md },
  primaryButtonText: { color: "#ffffff", flexShrink: 1, fontSize: 14, fontWeight: "900" },
  referralCtaPressable: { borderRadius: 14, overflow: "hidden" },
  referralCta: { alignItems: "center", flexDirection: "row", gap: 9, minHeight: 66, paddingHorizontal: 11, paddingVertical: 9 },
  referralCtaIcon: { alignItems: "center", backgroundColor: "rgba(255,255,255,0.2)", borderRadius: 11, height: 38, justifyContent: "center", width: 38 },
  referralCtaCopy: { flex: 1, minWidth: 0 },
  referralCtaTitle: { color: "#ffffff", fontSize: 12.5, fontWeight: "900", lineHeight: 17 },
  referralCtaSubtitle: { color: "rgba(255,255,255,0.9)", fontSize: 9.5, fontWeight: "600", lineHeight: 13, marginTop: 1 },
  referralCtaAction: { alignItems: "center", backgroundColor: "#fff7ed", borderRadius: 999, flexDirection: "row", justifyContent: "center", minHeight: 34, paddingLeft: 10, paddingRight: 6 },
  referralCtaActionText: { color: "#c2410c", fontSize: 10.5, fontWeight: "900" },
  secondaryButton: { alignItems: "center", borderRadius: 14, borderWidth: 1, flexDirection: "row", gap: 9, justifyContent: "center", minHeight: 50, paddingHorizontal: spacing.md },
  secondaryButtonText: { color: "#c2410c", flexShrink: 1, fontSize: 14, fontWeight: "800" },
  disabled: { opacity: 0.5 },
  pressed: { opacity: 0.78, transform: [{ scale: 0.99 }] },
  resultCard: { backgroundColor: colors.surface, borderColor: colors.border, borderRadius: 18, borderWidth: 1, gap: 12, padding: 14 },
  productRow: { flexDirection: "row", gap: 11 },
  productImageFrame: { alignItems: "center", backgroundColor: "#f1f5f9", borderColor: colors.border, borderRadius: 12, borderWidth: 1, height: 78, justifyContent: "center", overflow: "hidden", width: 78 },
  productImage: { height: "100%", width: "100%" },
  productImageFallback: { color: "#94a3b8", fontSize: 12, fontWeight: "900" },
  productBody: { flex: 1, gap: 3, minWidth: 0 },
  productName: { color: colors.text, fontSize: 13, fontWeight: "800", lineHeight: 18 },
  productPrice: { fontSize: 15, fontWeight: "900" },
  productRate: { fontSize: 10.5, fontWeight: "600", lineHeight: 15 },
  cashbackTotal: { alignItems: "center", borderRadius: 15, flexDirection: "row", justifyContent: "space-between", minHeight: 84, paddingHorizontal: 14, paddingVertical: 12 },
  cashbackTotalCopy: { flex: 1, minWidth: 0 },
  cashbackTotalLabel: { color: "rgba(255,255,255,0.78)", fontSize: 10.5, fontWeight: "600" },
  cashbackTotalValue: { color: "#ffffff", fontSize: 28, fontWeight: "900", lineHeight: 34, marginTop: 2 },
  detailsToggle: { alignItems: "center", alignSelf: "flex-start", flexDirection: "row", gap: 4, minHeight: 38, paddingRight: 10 },
  detailsToggleText: { fontSize: 11, fontWeight: "800" },
  detailsChevronOpen: { transform: [{ rotate: "180deg" }] },
  detailsPanel: { borderRadius: 12, borderWidth: 1, gap: 8, padding: 11 },
  detailRow: { alignItems: "center", flexDirection: "row", gap: 10, justifyContent: "space-between" },
  detailLabel: { flex: 1, fontSize: 10.5, fontWeight: "600" },
  detailValue: { flexShrink: 1, fontSize: 10.5, fontWeight: "800", textAlign: "right" },
  detailNotice: { fontSize: 10.5, lineHeight: 16 },
  resultActions: { flexDirection: "row", gap: 9 },
  resultAction: { flex: 1, minWidth: 0 },
  resultSkeleton: { backgroundColor: colors.surface, borderColor: colors.border, borderRadius: 24, borderWidth: 1, flexDirection: "row", gap: spacing.md, padding: spacing.lg },
  skeletonImage: { backgroundColor: "#e2e8f0", borderRadius: 16, height: 108, width: 108 },
  skeletonBody: { flex: 1, gap: 12, paddingTop: 7 },
  skeletonLine: { backgroundColor: "#e2e8f0", borderRadius: 6, height: 13, width: "65%" },
  skeletonLineLong: { width: "100%" },
  skeletonLineShort: { width: "42%" },
});
