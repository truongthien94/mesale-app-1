import { useState } from "react";
import {
  ActivityIndicator,
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
import { ApiError } from "@/api/client";
import { EmptyState, ErrorState, LoadingState, OfflineState } from "@/components/AsyncState";
import { getDeviceLocale } from "@/i18n";
import { colors, spacing } from "@/theme/tokens";
import { isSafeAffiliateUrl, normalizeBannerLink, normalizeProductUrl } from "@/features/home/api";
import { useAccountSummary, useCreateCashbackLink, useHomeConfig } from "@/features/home/hooks";
import type { CashbackProduct, HomeConfig, Marketplace } from "@/features/home/types";

const copy = {
  vi: {
    loading: "Đang tải trang chủ...",
    offlineTitle: "Không có kết nối",
    offlineMessage: "Không thể tải dữ liệu tài khoản. Hãy kiểm tra mạng rồi thử lại.",
    errorTitle: "Chưa thể tải trang chủ",
    refreshError: "Dữ liệu đang hiển thị có thể đã cũ vì lần làm mới gần nhất thất bại.",
    emptyTitle: "Không có dữ liệu tài khoản",
    emptyMessage: "Máy chủ chưa trả về thông tin ví cho tài khoản này.",
    retry: "Thử lại",
    greeting: "Xin chào",
    dashboardCaption: "Tổng quan tài khoản và công cụ hoàn tiền của bạn",
    availableBalance: "Số dư khả dụng",
    totalCashback: "Tổng cashback",
    totalWithdrawn: "Tổng đã rút",
    referralEarned: "Hoa hồng giới thiệu",
    orders: "Đơn hàng",
    pending: "Chờ duyệt",
    approved: "Đã duyệt",
    rejected: "Từ chối",
    referrals: "Giới thiệu",
    cashbackTitle: "Tạo link hoàn tiền",
    cashbackCaption: "Dán link sản phẩm, Mê Sale sẽ tạo liên kết mua hàng có ghi nhận cashback.",
    supported: "Nền tảng hỗ trợ",
    configLoading: "Đang kiểm tra trạng thái các sàn...",
    configOffline: "Chưa thể kiểm tra trạng thái sàn vì thiết bị đang offline.",
    configError: "Chưa thể tải cấu hình sàn. Tính năng tạo link tạm khóa để bảo đảm an toàn.",
    featureDisabled: "Tính năng tạo link hoàn tiền đang tạm bảo trì.",
    noMarketplace: "Hiện chưa có sàn nào được bật trên máy chủ.",
    urlPlaceholder: "Dán link Shopee, TikTok Shop hoặc Lazada",
    urlRequired: "Vui lòng nhập link sản phẩm.",
    urlInvalid: "Link sản phẩm không đúng định dạng.",
    analyze: "Phân tích link",
    analyzing: "Đang phân tích...",
    productReady: "Sản phẩm hợp lệ nhận hoàn tiền",
    currentPrice: "Giá hiện tại",
    estimatedCashback: "Tiền hoàn dự kiến",
    estimatedRate: "Tỷ lệ hoàn ước tính",
    estimatedNote: "Số tiền thực tế được máy chủ cập nhật sau khi sàn ghi nhận đơn hàng.",
    affiliateLink: "Liên kết hoàn tiền của bạn",
    notice: "Lưu ý",
    openMarketplace: "Mở sàn mua hàng",
    shareLink: "Chia sẻ liên kết",
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
    dashboardCaption: "Your account overview and cashback tool",
    availableBalance: "Available balance",
    totalCashback: "Total cashback",
    totalWithdrawn: "Total withdrawn",
    referralEarned: "Referral earnings",
    orders: "Orders",
    pending: "Pending",
    approved: "Approved",
    rejected: "Rejected",
    referrals: "Referrals",
    cashbackTitle: "Create cashback link",
    cashbackCaption: "Paste a product URL and Mesale will create a tracked cashback link.",
    supported: "Supported marketplaces",
    configLoading: "Checking marketplace availability...",
    configOffline: "Marketplace availability cannot be checked while offline.",
    configError: "Marketplace configuration is unavailable. Link creation is locked for safety.",
    featureDisabled: "Cashback-link creation is under maintenance.",
    noMarketplace: "No marketplace is currently enabled by the server.",
    urlPlaceholder: "Paste a Shopee, TikTok Shop or Lazada URL",
    urlRequired: "Enter a product URL.",
    urlInvalid: "Enter a valid product URL.",
    analyze: "Analyze link",
    analyzing: "Analyzing...",
    productReady: "Product eligible for cashback",
    currentPrice: "Current price",
    estimatedCashback: "Estimated cashback",
    estimatedRate: "Estimated cashback rate",
    estimatedNote: "The server updates the final amount after the marketplace records your order.",
    affiliateLink: "Your cashback link",
    notice: "Notice",
    openMarketplace: "Open marketplace",
    shareLink: "Share link",
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
  return (
    <View accessibilityRole={tone === "danger" ? "alert" : undefined} style={[
      styles.notice,
      tone === "danger" && styles.noticeDanger,
      tone === "warning" && styles.noticeWarning
    ]}>
      <Text style={styles.noticeText}>{message}</Text>
      {onRetry ? (
        <Pressable accessibilityRole="button" onPress={onRetry} style={styles.noticeAction}>
          <Text style={styles.noticeActionText}>{strings.retry}</Text>
        </Pressable>
      ) : null}
    </View>
  );
}

function MetricCard({ label, value, accent }: { label: string; value: string; accent: string }) {
  return (
    <View style={styles.metricCard}>
      <View style={[styles.metricMark, { backgroundColor: accent }]} />
      <Text numberOfLines={2} style={styles.metricLabel}>{label}</Text>
      <Text adjustsFontSizeToFit numberOfLines={1} style={styles.metricValue}>{value}</Text>
    </View>
  );
}

function StatPill({ label, value, color }: { label: string; value: number; color: string }) {
  return (
    <View style={styles.statPill}>
      <Text style={[styles.statValue, { color }]}>{value}</Text>
      <Text numberOfLines={1} style={styles.statLabel}>{label}</Text>
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
        const rate = config.marketplaces[platform].rate;
        return (
          <View key={platform} style={[styles.platformBadge, { backgroundColor: presentation.color }]}>
            <Text style={styles.platformBadgeText}>
              {presentation.label}{rate > 0 ? ` · ${rate}%` : ""}
            </Text>
          </View>
        );
      })}
    </View>
  );
}

function ProductImage({ uri, name }: { uri: string | null; name: string }) {
  const [failed, setFailed] = useState(false);
  const secureUri = secureRemoteUri(uri);

  return (
    <View style={styles.productImageFrame}>
      {secureUri && !failed ? (
        <Image
          accessibilityLabel={name}
          onError={() => setFailed(true)}
          resizeMode="cover"
          source={{ uri: secureUri }}
          style={styles.productImage}
        />
      ) : (
        <Text accessibilityLabel="Product image unavailable" style={styles.productImageFallback}>IMG</Text>
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
  const presentation = platformPresentation[product.platform];
  const marketplaceNotice = stripMarkup(notice ?? null);

  async function confirmMarketplaceHandoff() {
    setHandoffError(null);
    if (!isSafeAffiliateUrl(product.affiliateUrl)) {
      setHandoffError(strings.handoffError);
      return;
    }

    try {
      if (!await Linking.canOpenURL(product.affiliateUrl)) {
        setHandoffError(strings.handoffError);
        return;
      }
    } catch {
      setHandoffError(strings.handoffError);
      return;
    }

    Alert.alert(strings.handoffTitle, strings.handoffMessage, [
      { text: strings.cancel, style: "cancel" },
      {
        text: strings.continue,
        onPress: () => {
          void Linking.openURL(product.affiliateUrl).catch(() => setHandoffError(strings.handoffError));
        }
      }
    ]);
  }

  async function shareAffiliateLink() {
    try {
      await Share.share({ message: product.affiliateUrl });
    } catch {
      setHandoffError(strings.handoffError);
    }
  }

  return (
    <View style={styles.resultCard}>
      <View style={styles.resultTopRow}>
        <View style={[styles.platformBadge, { backgroundColor: presentation.color }]}>
          <Text style={styles.platformBadgeText}>{presentation.label}</Text>
        </View>
        <Text style={styles.referenceText}>{strings.reference}: {product.transId}</Text>
      </View>

      <View style={styles.productRow}>
        <ProductImage key={product.image ?? product.transId} name={product.name} uri={product.image} />
        <View style={styles.productBody}>
          <Text style={styles.eligibleText}>{strings.productReady}</Text>
          <Text numberOfLines={4} style={styles.productName}>{product.name}</Text>
        </View>
      </View>

      {product.isEstimated ? (
        <View style={styles.estimatedBox}>
          <Text style={styles.estimatedLabel}>{strings.estimatedRate}</Text>
          <Text style={styles.estimatedValue}>{product.cashbackRate}{strings.rateSuffix}</Text>
          <Text style={styles.estimatedNote}>{strings.estimatedNote}</Text>
        </View>
      ) : (
        <View style={styles.productMetrics}>
          <MetricCard label={strings.currentPrice} value={formatVnd(product.price, language)} accent="#94a3b8" />
          <MetricCard label={strings.estimatedCashback} value={formatVnd(product.cashbackAmount, language)} accent="#f97316" />
        </View>
      )}

      <View style={styles.affiliateBox}>
        <Text style={styles.affiliateLabel}>{strings.affiliateLink}</Text>
        <Text numberOfLines={2} selectable style={styles.affiliateUrl}>{product.affiliateUrl}</Text>
      </View>

      {marketplaceNotice ? (
        <View style={styles.marketplaceNotice}>
          <Text style={styles.marketplaceNoticeTitle}>{strings.notice}</Text>
          <Text style={styles.marketplaceNoticeText}>{marketplaceNotice}</Text>
        </View>
      ) : null}

      {handoffError ? <InlineNotice message={handoffError} tone="danger" strings={strings} /> : null}

      <Pressable
        accessibilityRole="button"
        onPress={() => void confirmMarketplaceHandoff()}
        style={({ pressed }) => [styles.primaryButton, pressed && styles.pressed]}
      >
        <Text style={styles.primaryButtonText}>{strings.openMarketplace}</Text>
      </Pressable>
      <Pressable
        accessibilityRole="button"
        onPress={() => void shareAffiliateLink()}
        style={({ pressed }) => [styles.secondaryButton, pressed && styles.pressed]}
      >
        <Text style={styles.secondaryButtonText}>{strings.shareLink}</Text>
      </Pressable>
    </View>
  );
}

export function HomeScreen() {
  const insets = useSafeAreaInsets();
  const language = getDeviceLocale();
  const strings = copy[language];
  const accountQuery = useAccountSummary();
  const configQuery = useHomeConfig();
  const cashbackMutation = useCreateCashbackLink();
  const [productUrl, setProductUrl] = useState("");
  const [inputError, setInputError] = useState<string | null>(null);
  const [lastSubmittedUrl, setLastSubmittedUrl] = useState<string | null>(null);

  if (accountQuery.isPending) return <LoadingState label={strings.loading} />;
  if (accountQuery.isError && !accountQuery.data) {
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
  if (!accountQuery.data) {
    return <EmptyState message={strings.emptyMessage} title={strings.emptyTitle} />;
  }

  const account = accountQuery.data;
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

  const mutationMessage = cashbackMutation.isError
    ? (isOfflineError(cashbackMutation.error)
      ? strings.mutationOffline
      : errorMessage(cashbackMutation.error, strings.mutationError))
    : null;
  const banner = config?.banners[0];
  const bannerUri = secureRemoteUri(banner?.imageUrl ?? null);
  const bannerLink = normalizeBannerLink(banner?.link ?? null);

  async function openBannerLink() {
    if (!bannerLink) return;
    try {
      if (!await Linking.canOpenURL(bannerLink)) throw new Error("Unsupported banner link");
      await Linking.openURL(bannerLink);
    } catch {
      Alert.alert(strings.bannerLinkError);
    }
  }

  return (
    <KeyboardAvoidingView
      behavior={Platform.OS === "ios" ? "padding" : undefined}
      style={styles.screen}
    >
      <ScrollView
        contentContainerStyle={[
          styles.content,
          {
            paddingTop: insets.top + spacing.md,
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
            tintColor={colors.primary}
          />
        )}
      >
        <View style={styles.headingRow}>
          <View style={styles.avatar}>
            <Text style={styles.avatarText}>{(account.name ?? account.email ?? "M").charAt(0).toUpperCase()}</Text>
          </View>
          <View style={styles.headingBody}>
            <Text style={styles.eyebrow}>{strings.greeting}</Text>
            <Text numberOfLines={1} style={styles.heading}>{account.name ?? account.email ?? `#${account.id}`}</Text>
            <Text style={styles.caption}>{strings.dashboardCaption}</Text>
          </View>
        </View>

        {accountQuery.isError ? (
          <InlineNotice
            message={strings.refreshError}
            onRetry={() => void accountQuery.refetch()}
            strings={strings}
            tone="warning"
          />
        ) : null}

        {banner && bannerUri ? (
          <Pressable
            accessibilityRole={bannerLink ? "link" : "image"}
            disabled={!bannerLink}
            onPress={() => void openBannerLink()}
            style={({ pressed }) => [styles.bannerFrame, pressed && styles.pressed]}
          >
            <Image
              accessibilityLabel={banner.title ?? config?.siteName ?? "Mesale"}
              resizeMode="cover"
              source={{ uri: bannerUri }}
              style={styles.bannerImage}
            />
          </Pressable>
        ) : null}

        <View style={styles.balanceCard}>
          <View style={styles.balanceOrbLarge} />
          <View style={styles.balanceOrbSmall} />
          <Text style={styles.balanceLabel}>{strings.availableBalance}</Text>
          <Text adjustsFontSizeToFit numberOfLines={1} style={styles.balanceValue}>
            {formatVnd(account.wallet.balance, language)}
          </Text>
        </View>

        <View style={styles.metricGrid}>
          <MetricCard label={strings.totalCashback} value={formatVnd(account.wallet.totalCashback, language)} accent="#10b981" />
          <MetricCard label={strings.totalWithdrawn} value={formatVnd(account.wallet.totalWithdrawn, language)} accent="#3b82f6" />
          <MetricCard label={strings.referralEarned} value={formatVnd(account.wallet.totalReferralEarned, language)} accent="#f59e0b" />
          <MetricCard label={strings.referrals} value={String(account.stats.referralsCount)} accent="#8b5cf6" />
        </View>

        <View style={styles.statsCard}>
          <Text style={styles.sectionLabel}>{strings.orders} · {account.stats.ordersTotal}</Text>
          <View style={styles.statsRow}>
            <StatPill color="#d97706" label={strings.pending} value={account.stats.ordersPending} />
            <StatPill color="#059669" label={strings.approved} value={account.stats.ordersApproved} />
            <StatPill color="#dc2626" label={strings.rejected} value={account.stats.ordersRejected} />
          </View>
        </View>

        <View style={styles.creatorCard}>
          <View>
            <Text accessibilityRole="header" style={styles.creatorTitle}>{strings.cashbackTitle}</Text>
            <Text style={styles.creatorCaption}>{strings.cashbackCaption}</Text>
          </View>

          <View style={styles.supportBlock}>
            <Text style={styles.supportLabel}>{strings.supported}</Text>
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

          <View style={[styles.inputFrame, inputError && styles.inputFrameError]}>
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
              placeholderTextColor="#94a3b8"
              returnKeyType="search"
              style={styles.input}
              value={productUrl}
            />
          </View>
          {inputError ? <Text accessibilityRole="alert" style={styles.fieldError}>{inputError}</Text> : null}

          <Pressable
            accessibilityRole="button"
            disabled={creationDisabled || cashbackMutation.isPending}
            onPress={() => submitProductUrl()}
            style={({ pressed }) => [
              styles.primaryButton,
              (creationDisabled || cashbackMutation.isPending) && styles.disabled,
              pressed && styles.pressed
            ]}
          >
            {cashbackMutation.isPending ? <ActivityIndicator color="#ffffff" size="small" /> : null}
            <Text style={styles.primaryButtonText}>
              {cashbackMutation.isPending ? strings.analyzing : strings.analyze}
            </Text>
          </Pressable>

          {mutationMessage ? (
            <InlineNotice
              message={mutationMessage}
              onRetry={lastSubmittedUrl ? () => submitProductUrl(lastSubmittedUrl) : undefined}
              strings={strings}
              tone="danger"
            />
          ) : null}
        </View>

        {cashbackMutation.data ? (
          <ProductResult
            language={language}
            notice={config?.marketplaces[cashbackMutation.data.platform].notice}
            product={cashbackMutation.data}
            strings={strings}
          />
        ) : cashbackMutation.isPending ? (
          <View accessibilityRole="progressbar" style={styles.resultSkeleton}>
            <View style={styles.skeletonImage} />
            <View style={styles.skeletonBody}>
              <View style={[styles.skeletonLine, styles.skeletonLineLong]} />
              <View style={styles.skeletonLine} />
              <View style={[styles.skeletonLine, styles.skeletonLineShort]} />
            </View>
          </View>
        ) : (
          <View style={styles.resultEmpty}>
            <Text style={styles.resultEmptyMark}>LINK</Text>
            <Text style={styles.resultEmptyText}>{strings.resultEmpty}</Text>
          </View>
        )}
      </ScrollView>
    </KeyboardAvoidingView>
  );
}

const styles = StyleSheet.create({
  screen: { backgroundColor: colors.background, flex: 1 },
  content: { gap: spacing.md },
  headingRow: { alignItems: "center", flexDirection: "row", gap: 12 },
  avatar: {
    alignItems: "center", backgroundColor: "#fff1eb", borderColor: "#fed7c7", borderRadius: 18,
    borderWidth: 1, height: 52, justifyContent: "center", width: 52
  },
  avatarText: { color: "#ee4d2d", fontSize: 22, fontWeight: "900" },
  headingBody: { flex: 1 },
  eyebrow: { color: colors.mutedText, fontSize: 12, fontWeight: "700", textTransform: "uppercase" },
  heading: { color: colors.text, fontSize: 22, fontWeight: "900", letterSpacing: -0.4 },
  caption: { color: colors.mutedText, fontSize: 12, lineHeight: 18, marginTop: 2 },
  bannerFrame: { borderRadius: 20, height: 144, overflow: "hidden" },
  bannerImage: { height: "100%", width: "100%" },
  balanceCard: {
    backgroundColor: "#ff451a", borderRadius: 24, minHeight: 154, overflow: "hidden", padding: spacing.lg,
    shadowColor: "#f97316", shadowOffset: { height: 8, width: 0 }, shadowOpacity: 0.22, shadowRadius: 18, elevation: 7
  },
  balanceOrbLarge: { backgroundColor: "rgba(255,255,255,0.17)", borderRadius: 80, height: 150, position: "absolute", right: -45, top: -55, width: 150 },
  balanceOrbSmall: { backgroundColor: "rgba(98,25,0,0.12)", borderRadius: 60, bottom: -55, height: 115, left: -25, position: "absolute", width: 115 },
  balanceLabel: { color: "rgba(255,255,255,0.88)", fontSize: 12, fontWeight: "800", letterSpacing: 1, textTransform: "uppercase" },
  balanceValue: { color: "#ffffff", fontSize: 34, fontWeight: "900", letterSpacing: -1, marginTop: spacing.lg },
  metricGrid: { flexDirection: "row", flexWrap: "wrap", gap: 12 },
  metricCard: {
    backgroundColor: colors.surface, borderColor: "#f1f5f9", borderRadius: 18, borderWidth: 1,
    flexBasis: "47%", flexGrow: 1, minHeight: 112, padding: spacing.md,
    shadowColor: "#0f172a", shadowOffset: { height: 3, width: 0 }, shadowOpacity: 0.04, shadowRadius: 9, elevation: 2
  },
  metricMark: { borderRadius: 3, height: 6, marginBottom: 12, width: 28 },
  metricLabel: { color: colors.mutedText, fontSize: 11, fontWeight: "800", minHeight: 28, textTransform: "uppercase" },
  metricValue: { color: colors.text, fontSize: 18, fontWeight: "900", marginTop: 6 },
  statsCard: { backgroundColor: colors.surface, borderColor: colors.border, borderRadius: 20, borderWidth: 1, padding: spacing.md },
  sectionLabel: { color: colors.text, fontSize: 13, fontWeight: "900", marginBottom: spacing.md, textTransform: "uppercase" },
  statsRow: { flexDirection: "row", gap: spacing.sm },
  statPill: { alignItems: "center", backgroundColor: "#f8fafc", borderRadius: 14, flex: 1, paddingHorizontal: 6, paddingVertical: 12 },
  statValue: { fontSize: 19, fontWeight: "900" },
  statLabel: { color: colors.mutedText, fontSize: 10, fontWeight: "700", marginTop: 2 },
  creatorCard: {
    backgroundColor: colors.surface, borderColor: "#fed7c7", borderRadius: 24, borderWidth: 1, gap: spacing.md,
    padding: spacing.lg, shadowColor: "#f97316", shadowOffset: { height: 5, width: 0 }, shadowOpacity: 0.08, shadowRadius: 16, elevation: 3
  },
  creatorTitle: { color: colors.text, fontSize: 21, fontWeight: "900" },
  creatorCaption: { color: colors.mutedText, fontSize: 13, lineHeight: 20, marginTop: 5 },
  supportBlock: { borderBottomColor: "#f1f5f9", borderBottomWidth: 1, gap: 9, paddingBottom: spacing.md },
  supportLabel: { color: colors.mutedText, fontSize: 10, fontWeight: "900", letterSpacing: 0.8, textTransform: "uppercase" },
  badgeRow: { flexDirection: "row", flexWrap: "wrap", gap: 7 },
  platformBadge: { borderRadius: 999, paddingHorizontal: 10, paddingVertical: 6 },
  platformBadgeText: { color: "#ffffff", fontSize: 11, fontWeight: "900" },
  notice: { alignItems: "center", backgroundColor: "#f1f5f9", borderColor: colors.border, borderRadius: 14, borderWidth: 1, flexDirection: "row", gap: 10, padding: 12 },
  noticeDanger: { backgroundColor: "#fff1f2", borderColor: "#fecdd3" },
  noticeWarning: { backgroundColor: "#fffbeb", borderColor: "#fde68a" },
  noticeText: { color: "#475569", flex: 1, fontSize: 12, lineHeight: 18 },
  noticeAction: { minHeight: 36, justifyContent: "center", paddingHorizontal: 5 },
  noticeActionText: { color: "#ea580c", fontSize: 12, fontWeight: "900" },
  inputFrame: { backgroundColor: "#f8fafc", borderColor: colors.border, borderRadius: 16, borderWidth: 1, minHeight: 54 },
  inputFrameError: { borderColor: colors.danger },
  input: { color: colors.text, flex: 1, fontSize: 14, minHeight: 54, paddingHorizontal: 15, paddingVertical: 12 },
  fieldError: { color: colors.danger, fontSize: 12, fontWeight: "600", marginTop: -8 },
  primaryButton: { alignItems: "center", backgroundColor: "#ee4d2d", borderRadius: 16, flexDirection: "row", gap: 9, justifyContent: "center", minHeight: 52, paddingHorizontal: spacing.lg },
  primaryButtonText: { color: "#ffffff", fontSize: 14, fontWeight: "900" },
  secondaryButton: { alignItems: "center", backgroundColor: "#fff7ed", borderColor: "#fed7aa", borderRadius: 16, borderWidth: 1, justifyContent: "center", minHeight: 48, paddingHorizontal: spacing.lg },
  secondaryButtonText: { color: "#c2410c", fontSize: 14, fontWeight: "800" },
  disabled: { opacity: 0.5 },
  pressed: { opacity: 0.78, transform: [{ scale: 0.99 }] },
  resultCard: { backgroundColor: colors.surface, borderColor: "#fed7aa", borderRadius: 24, borderWidth: 1, gap: spacing.md, padding: spacing.lg },
  resultTopRow: { alignItems: "center", flexDirection: "row", justifyContent: "space-between", gap: 10 },
  referenceText: { color: colors.mutedText, flex: 1, fontSize: 10, fontWeight: "700", textAlign: "right" },
  productRow: { flexDirection: "row", gap: spacing.md },
  productImageFrame: { alignItems: "center", backgroundColor: "#f1f5f9", borderColor: colors.border, borderRadius: 16, borderWidth: 1, height: 108, justifyContent: "center", overflow: "hidden", width: 108 },
  productImage: { height: "100%", width: "100%" },
  productImageFallback: { color: "#94a3b8", fontSize: 12, fontWeight: "900" },
  productBody: { flex: 1, gap: 7 },
  eligibleText: { alignSelf: "flex-start", backgroundColor: "#fff7ed", borderRadius: 8, color: "#c2410c", fontSize: 10, fontWeight: "900", overflow: "hidden", paddingHorizontal: 8, paddingVertical: 5 },
  productName: { color: colors.text, fontSize: 16, fontWeight: "900", lineHeight: 22 },
  productMetrics: { flexDirection: "row", gap: 12 },
  estimatedBox: { backgroundColor: "#fffbeb", borderColor: "#fde68a", borderRadius: 16, borderWidth: 1, padding: spacing.md },
  estimatedLabel: { color: "#92400e", fontSize: 10, fontWeight: "900", textTransform: "uppercase" },
  estimatedValue: { color: "#c2410c", fontSize: 22, fontWeight: "900", marginTop: 4 },
  estimatedNote: { color: "#92400e", fontSize: 12, lineHeight: 18, marginTop: spacing.sm },
  affiliateBox: { backgroundColor: "#f8fafc", borderColor: colors.border, borderRadius: 16, borderWidth: 1, gap: 5, padding: 13 },
  affiliateLabel: { color: colors.mutedText, fontSize: 10, fontWeight: "900", textTransform: "uppercase" },
  affiliateUrl: { color: "#334155", fontSize: 12, fontWeight: "600", lineHeight: 18 },
  marketplaceNotice: { backgroundColor: "#fff7ed", borderColor: "#fed7aa", borderRadius: 16, borderWidth: 1, gap: 5, padding: 13 },
  marketplaceNoticeTitle: { color: "#9a3412", fontSize: 11, fontWeight: "900", textTransform: "uppercase" },
  marketplaceNoticeText: { color: "#7c2d12", fontSize: 12, lineHeight: 18 },
  resultSkeleton: { backgroundColor: colors.surface, borderColor: colors.border, borderRadius: 24, borderWidth: 1, flexDirection: "row", gap: spacing.md, padding: spacing.lg },
  skeletonImage: { backgroundColor: "#e2e8f0", borderRadius: 16, height: 108, width: 108 },
  skeletonBody: { flex: 1, gap: 12, paddingTop: 7 },
  skeletonLine: { backgroundColor: "#e2e8f0", borderRadius: 6, height: 13, width: "65%" },
  skeletonLineLong: { width: "100%" },
  skeletonLineShort: { width: "42%" },
  resultEmpty: { alignItems: "center", backgroundColor: "#ffffff", borderColor: colors.border, borderRadius: 22, borderStyle: "dashed", borderWidth: 1, gap: 10, padding: spacing.lg },
  resultEmptyMark: { color: "#cbd5e1", fontSize: 13, fontWeight: "900", letterSpacing: 2 },
  resultEmptyText: { color: colors.mutedText, fontSize: 13, lineHeight: 20, textAlign: "center" }
});
