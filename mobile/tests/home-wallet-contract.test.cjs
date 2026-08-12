const assert = require("node:assert/strict");
const fs = require("node:fs");
const path = require("node:path");
const test = require("node:test");
const ts = require("typescript");

function read(relativePath) {
  return fs.readFileSync(path.resolve(__dirname, relativePath), "utf8");
}

function loadTypeScriptModule(relativePath, stubs = {}) {
  const filePath = path.resolve(__dirname, relativePath);
  const output = ts.transpileModule(fs.readFileSync(filePath, "utf8"), {
    compilerOptions: {
      module: ts.ModuleKind.CommonJS,
      target: ts.ScriptTarget.ES2022
    },
    fileName: filePath,
    reportDiagnostics: true
  });
  const errors = output.diagnostics?.filter((diagnostic) => diagnostic.category === ts.DiagnosticCategory.Error) ?? [];
  assert.equal(errors.length, 0, `${relativePath} must transpile without diagnostics`);

  const loadedModule = { exports: {} };
  const localRequire = (specifier) => {
    if (Object.hasOwn(stubs, specifier)) return stubs[specifier];
    return require(specifier);
  };
  const execute = new Function("exports", "require", "module", "__filename", "__dirname", output.outputText);
  execute(loadedModule.exports, localRequire, loadedModule, filePath, path.dirname(filePath));
  return loadedModule.exports;
}

test("Vietnamese remains the default locale unless English is explicitly preferred", () => {
  const { getDeviceLocale, resolveLocale } = loadTypeScriptModule("../src/i18n/index.ts");

  assert.equal(getDeviceLocale(), "vi");
  assert.equal(resolveLocale(), "vi");
  assert.equal(resolveLocale("th"), "vi");
  assert.equal(resolveLocale(" EN "), "en");
});

test("account money formatting renders rolling-deploy nulls as zero", () => {
  const { formatAccountMoney } = loadTypeScriptModule("../src/features/home/format.ts");

  assert.equal(formatAccountMoney(null, "vi"), "0đ");
  assert.equal(formatAccountMoney(1250, "vi"), "1.250đ");
  assert.equal(formatAccountMoney(null, "en"), "₫0");
});

test("home normalizes product URLs and only accepts HTTPS affiliate handoff", () => {
  const { isSafeAffiliateUrl, normalizeProductUrl, normalizeBannerLink } = loadTypeScriptModule("../src/features/home/api.ts", {
    "@/api/client": { request: async () => { throw new Error("not called"); } }
  });

  assert.deepEqual(normalizeProductUrl("shopee.vn/product/42"), {
    valid: true,
    url: "https://shopee.vn/product/42"
  });
  assert.deepEqual(normalizeProductUrl("http://tiktok.shop/item/42"), {
    valid: true,
    url: "https://tiktok.shop/item/42"
  });
  assert.deepEqual(normalizeProductUrl("javascript:alert(1)"), { valid: false, reason: "invalid" });
  assert.equal(isSafeAffiliateUrl("https://s.shopee.vn/track"), true);
  assert.equal(isSafeAffiliateUrl("http://s.shopee.vn/track"), false);
  assert.equal(normalizeBannerLink("http://mesale.vn/promo"), "https://mesale.vn/promo");
  assert.equal(normalizeBannerLink("mailto:support@mesale.vn"), "mailto:support@mesale.vn");
  assert.equal(normalizeBannerLink("https://user:pass@mesale.vn/promo"), null);
  assert.equal(normalizeBannerLink("javascript:alert(1)"), null);
});

test("account summary preserves unknown aggregate cashback fields during rolling deploy", async () => {
  let accountPayload = {
    id: 42,
    name: "Mobile User",
    email: "mobile@example.test",
    avatar: null,
    referral_code: "MESALE42",
    wallet: {
      balance: 200,
      total_cashback: 500,
      total_referral_earned: 50,
      total_withdrawn: 100,
      currency: "VND"
    },
    stats: {
      orders_total: 3,
      orders_pending: 1,
      orders_approved: 2,
      orders_rejected: 0,
      referrals_count: 0,
      withdrawals_pending: 0
    }
  };
  const { fetchAccountSummary } = loadTypeScriptModule("../src/features/home/api.ts", {
    "@/api/client": { request: async () => accountPayload }
  });

  const legacySummary = await fetchAccountSummary();
  assert.equal(legacySummary.wallet.pendingCashback, null);
  assert.equal(legacySummary.wallet.approvedCashback, null);

  accountPayload = {
    ...accountPayload,
    wallet: { ...accountPayload.wallet, pending_cashback: 1250, approved_cashback: 3400 }
  };
  const aggregateSummary = await fetchAccountSummary();
  assert.equal(aggregateSummary.wallet.pendingCashback, 1250);
  assert.equal(aggregateSummary.wallet.approvedCashback, 3400);

  accountPayload = {
    ...accountPayload,
    wallet: { ...accountPayload.wallet, pending_cashback: 12.5 }
  };
  await assert.rejects(fetchAccountSummary(), /Invalid pending cashback response/);

  accountPayload = {
    ...accountPayload,
    wallet: { ...accountPayload.wallet, pending_cashback: 1250, approved_cashback: 12.5 }
  };
  await assert.rejects(fetchAccountSummary(), /Invalid approved cashback response/);
});

test("home auth preview exposes only login-authoritative financial fields", () => {
  const { createHomeAuthPreview } = loadTypeScriptModule("../src/features/home/bootstrap.ts");
  const financialSnapshot = {
    balance: 200,
    totalCashback: 500,
    totalReferralEarned: 50,
    totalWithdrawn: 100
  };

  assert.equal(createHomeAuthPreview(null), null);
  assert.equal(createHomeAuthPreview({ id: 42, referralPromptPending: false }), null);
  assert.deepEqual(createHomeAuthPreview({
    id: 42,
    name: "Mobile User",
    email: "mobile@example.test",
    avatar: null,
    referral_code: "MESALE42",
    referralPromptPending: false,
    financialSnapshot
  }), {
    id: 42,
    name: "Mobile User",
    email: "mobile@example.test",
    avatar: null,
    referralCode: "MESALE42",
    wallet: financialSnapshot
  });
});

test("home renders the auth preview while account remains authoritative in the background", () => {
  const source = read("../src/features/home/HomeScreen.tsx");
  const bootstrap = read("../src/features/home/bootstrap.ts");

  assert.match(source, /const accountQuery = useAccountSummary\(\)/);
  assert.match(source, /const authPreview = createHomeAuthPreview\(user\)/);
  assert.match(source, /const account = accountQuery\.data \?\? authPreview/);
  assert.match(source, /if \(accountQuery\.isPending && !account\)/);
  assert.match(source, /accountQuery\.data[\s\S]*formatAccountMoney\(accountQuery\.data\.wallet\.pendingCashback/);
  assert.match(source, /accessibilityRole="progressbar"/);
  assert.doesNotMatch(source, /initialData|placeholderData|setQueryData/);
  assert.doesNotMatch(bootstrap, /pendingCashback|ordersPending|stats:/);
});

test("successful withdrawal invalidates the canonical account summary", async () => {
  const invalidated = [];
  const { useCreateWithdrawal } = loadTypeScriptModule("../src/features/wallet/api.ts", {
    "@tanstack/react-query": {
      useInfiniteQuery: () => undefined,
      useMutation: (options) => options,
      useQuery: () => undefined,
      useQueryClient: () => ({
        invalidateQueries: async ({ queryKey }) => { invalidated.push(queryKey); }
      })
    },
    "@/api/idempotency": { idempotencyHeaders: () => ({}) },
    "@/api/pagination": { getNextPageParam: () => undefined },
    "@/api/client": { request: async () => ({}) },
    "@/features/config/query": { appConfigRawQueryOptions: () => ({}) },
    "@/features/home/hooks": { homeQueryKeys: { account: ["account"] } }
  });

  await useCreateWithdrawal().onSuccess();

  assert.deepEqual(invalidated, [
    ["account"],
    ["wallet", "withdrawals"],
    ["wallet", "balance-logs"]
  ]);
});

test("home has no banner block, matching the live classic hero layout which renders no banner", () => {
  const source = read("../src/features/home/HomeScreen.tsx");
  assert.doesNotMatch(source, /normalizeBannerLink/);
  assert.doesNotMatch(source, /openBannerLink|bannerFrame|bannerImage|const banner =/);
  assert.doesNotMatch(source, /config\?\.banners/);
});

test("home replaces the promotional hero with a live three-card account summary", () => {
  const source = read("../src/features/home/HomeScreen.tsx");
  const accountStats = source.match(/<AccountStat\b/g) ?? [];
  const bellIcons = source.match(/<Bell\b/g) ?? [];
  const inboxActions = source.match(/router\.push\("\/\(tabs\)\/inbox"\)/g) ?? [];
  const compactAccountValues = source.match(/formatAccountMoney\((?:account|accountQuery\.data)\.wallet\./g) ?? [];

  assert.match(source, /account\.wallet\.balance/);
  assert.match(source, /account\.wallet\.totalCashback/);
  assert.match(source, /accountQuery\.data\.wallet\.pendingCashback/);
  assert.match(source, /account\.wallet\.totalWithdrawn/);
  assert.match(source, /router\.push\("\/\(tabs\)\/withdraw"\)/);
  assert.match(source, /import \{ formatAccountMoney \} from "@\/features\/home\/format"/);
  assert.match(source, /accountNotificationButton:\s*\{[^}]*height: 44,[^}]*width: 44/);
  assert.match(source, /accountWithdrawButton:\s*\{[^}]*minHeight: 44/);
  assert.match(source, /paddingTop: insets\.top \+ spacing\.sm/);
  assert.doesNotMatch(source, /accountSummary:\s*\{[^}]*paddingTop/);
  assert.equal(accountStats.length, 3);
  assert.equal(bellIcons.length, 1);
  assert.equal(inboxActions.length, 1);
  assert.equal(compactAccountValues.length, 5);
  assert.match(source, /styles\.accountGreetingIdentity[\s\S]*mesale-logo\.png[\s\S]*strings\.greeting/);
  assert.doesNotMatch(source, /styles\.brandBar|useMoreSheetStore|productInputRef|\.current\?\.focus|<Menu\b|<Moon\b|<Sun\b/);
  assert.doesNotMatch(source, /strings\.(heroBadge|cashbackTitle|cashbackCaption)/);
  assert.doesNotMatch(source, /Hệ Thống Mua Sắm|Cashback Shopping for/);
  assert.doesNotMatch(source, /% HH ròng|% net commission/i);
});

test("home adds the approved cashback claim and live Quick Access actions before the phone demo", () => {
  const source = read("../src/features/home/HomeScreen.tsx");
  const quickAccessIndex = source.indexOf("<QuickAccessSection");
  const phoneDemoIndex = source.indexOf("<PhoneFlowDemo");

  assert.match(source, /Hoàn tiền mua sắm Shopee - Tiktok Shop lên đến 15% giá trị đơn hàng/);
  for (const label of ["Truy cập nhanh", "Săn mã", "Điểm danh", "Tips & Trick", "Hỗ trợ"]) {
    assert.match(source, new RegExp(label));
  }
  assert.match(source, /router\.push\("\/\(tabs\)\/earn\/checkin"\)/);
  assert.match(source, /const SUPPORT_URL = "https:\/\/mesale\.vn\/support"/);
  assert.match(source, /onPress=\{\(\) => router\.push\("\/\(tabs\)\/home\/coupons"\)\}/);
  assert.match(source, /onTips=\{\(\) => Alert\.alert\(strings\.usageCautionTitle, strings\.usageCautionMessage\)\}/);
  assert.doesNotMatch(source, /openCouponsQuickAccess|couponsSectionY|homeScrollRef|scrollToQuickContent|scrollToEnd/);
  assert.ok(quickAccessIndex > source.indexOf("styles.creatorCard"));
  assert.ok(phoneDemoIndex > quickAccessIndex);
});

test("home ends at PhoneFlowDemo and does not mount the removed Round C blocks", () => {
  const source = read("../src/features/home/HomeScreen.tsx");
  assert.match(source, /<View style=\{styles\.demoSection\}>\s*<PhoneFlowDemo \/>\s*<\/View>\s*<\/ScrollView>/);
  assert.doesNotMatch(source, /RoundCHomeBlocks|RoundCCouponSection|RoundCTimelineSection|RoundCLeaderboardSection/);
  assert.doesNotMatch(source, /useCoupons|useRanking|RankingBoard|RankingEntry|couponDaysLeft|roundCStaticTimeline/);
});

test("Round C wallet uses the orange dashboard, shared orders cache, and status borders", () => {
  const source = read("../app/(tabs)/wallet/index.tsx");
  assert.match(source, /LinearGradient/);
  assert.match(source, /useOrders\(\)/);
  assert.match(source, /\.slice\(0, 5\)/);
  assert.match(source, /pending: "#facc15"/);
  assert.match(source, /approved: "#10b981"/);
  assert.match(source, /rejected: "#f43f5e"/);
  assert.match(source, /useTheme/);
  assert.match(source, /router\.push\("\/\(tabs\)\/withdraw"\)/);
  assert.doesNotMatch(source, /savings|chart|referral_count/i);
});

test("orders API builds the additive server-side filter and pagination contract", () => {
  const { buildOrdersPath } = loadTypeScriptModule("../src/features/wallet/api.ts", {
    "@tanstack/react-query": {
      useInfiniteQuery: () => undefined,
      useMutation: () => undefined,
      useQuery: () => undefined,
      useQueryClient: () => undefined
    },
    "@/api/idempotency": { idempotencyHeaders: () => ({}) },
    "@/api/pagination": { getNextPageParam: () => undefined },
    "@/api/client": { request: async () => ({}) },
    "@/features/config/query": { appConfigRawQueryOptions: () => ({}) },
    "@/features/home/hooks": { homeQueryKeys: { account: ["account"] } }
  });

  assert.equal(
    buildOrdersPath(2, {
      status: "unrecorded",
      platform: "lazada",
      search: "  ma don 42  ",
      startDate: "2026-08-01",
      endDate: "2026-08-11"
    }),
    "orders?page=2&per_page=20&status=unrecorded&platform=lazada&search=ma%20don%2042&start_date=2026-08-01&end_date=2026-08-11"
  );
  assert.equal(buildOrdersPath(1), "orders?page=1&per_page=20");
});

test("orders preserve stable record keys and suppress unsafe unrecorded fallbacks", () => {
  const {
    isRecordedOrder,
    orderListKey,
    secureOrderImageUrl,
    visibleOrdersForTab
  } = loadTypeScriptModule("../src/features/wallet/orders.ts");
  const recorded = { id: 7, record_type: "order" };
  const legacyRecorded = { id: 8 };
  const unrecorded = { id: 7, record_type: "unrecorded" };

  assert.equal(orderListKey(recorded), "order:7");
  assert.equal(orderListKey(unrecorded), "unrecorded:7");
  assert.equal(orderListKey(legacyRecorded), "order:8");
  assert.equal(isRecordedOrder(recorded), true);
  assert.equal(isRecordedOrder(unrecorded), false);
  assert.deepEqual(visibleOrdersForTab([recorded, unrecorded], "unrecorded", false), []);
  assert.deepEqual(visibleOrdersForTab([recorded, unrecorded], "unrecorded", true), [unrecorded]);
  assert.equal(secureOrderImageUrl("http://cdn.example.test/item.png"), "https://cdn.example.test/item.png");
  assert.equal(secureOrderImageUrl("https://user:pass@cdn.example.test/item.png"), null);
  assert.equal(secureOrderImageUrl("javascript:alert(1)"), null);
});

test("orders tab follows the compact MeSale process and server-authoritative summary flow", () => {
  const source = read("../app/(tabs)/wallet/orders/index.tsx");

  for (const label of [
    "Đơn hàng",
    "Đơn lên app: TikTok ~1 giờ · Shopee ~1 ngày",
    "Đặt đơn qua Mê Sale",
    "Đơn hiện ở màn này",
    "Nhận hàng → tiền về ví",
    "Đôi khi sàn gửi dữ liệu chậm hơn một chút — đơn không mất đâu.",
    "Về ví 7–14 ngày sau khi giao",
    "Đã cộng vào ví của bạn",
    "Chờ xác nhận",
    "Đã xác nhận",
    "Tất cả",
    "Bị từ chối",
    "Chưa có đơn hàng nào",
    "Chưa có đơn ở trạng thái này",
    "Xem tất cả",
    "Mua sắm ngay"
  ]) {
    assert.match(source, new RegExp(label));
  }
  assert.match(source, /useAccountSummary\(\)/);
  assert.match(source, /accountQuery\.data\?\.wallet\.pendingCashback/);
  assert.match(source, /accountQuery\.data\?\.stats\.ordersPending/);
  assert.match(source, /accountQuery\.data\?\.wallet\.approvedCashback/);
  assert.match(source, /accountQuery\.data\?\.stats\.ordersApproved/);
  assert.doesNotMatch(source, /accountQuery\.data\?\.wallet\.totalCashback/);
  assert.match(source, /useMemo\([\s\S]*pages\.flatMap/);
  assert.match(source, /background: "#2563eb", border: "#2563eb"/);
  assert.match(source, /<Receipt color=\{scheme === "dark" \? "#60a5fa" : "#2563eb"\}/);
  assert.match(source, /<ShoppingBag color="#2563eb"/);
  assert.match(source, /<Smartphone color="#7c3aed"/);
  assert.match(source, /<WalletCards color="#16a34a"/);
  assert.match(source, /filterChip:[^\n]*minHeight: 44/);
  assert.match(source, /<FlatList/);
  assert.match(source, /ListHeaderComponent=\{listHeader\}/);
  assert.match(source, /ListEmptyComponent=/);
  assert.match(source, /ordersQuery\.fetchNextPage\(\)/);
  assert.match(source, /keyExtractor=\{orderListKey\}/);
  assert.match(source, /if \(orderRecordType\(order\) === "unrecorded"\)[\s\S]*Alert\.alert[\s\S]*return;[\s\S]*router\.push/);
  assert.match(source, /accessibilityLabel=\{`\$\{productName\}\. Mã \$\{reference \?\? order\.id\}\. Hoàn tiền \$\{cashbackLabel\}\. Trạng thái \$\{statusLabel\(order\.status\)\}\.`\}/);
  assert.match(source, /router\.push\("\/\(tabs\)\/home"\)/);
  assert.match(source, /onShowAll=\{\(\) => setStatusFilter\("all"\)\}/);
  assert.match(source, /onPress=\{filtered \? onShowAll : onShop\}/);
  assert.match(source, /useTheme\(\)/);
  assert.doesNotMatch(source, /WebView|orders\.reduce|cashback_amount[\s\S]{0,80}reduce/);

  const filterBlock = source.match(/const statusFilters:[\s\S]*?\];/)?.[0] ?? "";
  assert.doesNotMatch(filterBlock, /unrecorded|Chờ sàn ghi nhận/);
});

test("wallet histories use infinite queries and virtualized native lists", () => {
  const api = read("../src/features/wallet/api.ts");
  assert.match(api, /useInfiniteQuery/);
  assert.match(api, /buildOrdersPath\(pageParam, filters\)/);
  assert.match(api, /pagePath\("balance-logs"/);
  assert.match(api, /pagePath\("withdrawals"/);

  for (const route of [
    "../app/(tabs)/wallet/orders/index.tsx",
    "../app/(tabs)/wallet/balance-logs.tsx",
    "../app/(tabs)/wallet/withdrawals/index.tsx"
  ]) {
    const source = read(route);
    assert.match(source, /<FlatList/);
    assert.doesNotMatch(source, /WebView/);
  }
});

test("financial submissions retain one idempotency key until success", () => {
  const stableSubmission = read("../src/features/wallet/submission.ts");
  assert.match(stableSubmission, /active\.current\?\.signature === signature/);
  assert.match(stableSubmission, /idempotencyKey: active\.current\.variables\.idempotencyKey/);
  assert.match(stableSubmission, /active\.current = null/);

  const withdrawal = read("../app/(tabs)/wallet/withdrawals/create.tsx");
  assert.match(withdrawal, /otp_code: _otpCode/);
  assert.match(withdrawal, /stableSubmission\.getVariables\(payload\)/);
  assert.match(withdrawal, /stableSubmission\.reset\(\)/);

  const paymentAccount = read("../app/(tabs)/wallet/payment-accounts/create.tsx");
  assert.match(paymentAccount, /stableSubmission\.getVariables\(payload\)/);
  assert.match(paymentAccount, /stableSubmission\.reset\(\)/);
});

test("payment account destination uses a searchable native dropdown", () => {
  const source = read("../app/(tabs)/wallet/payment-accounts/create.tsx");

  assert.match(source, /const \[showDestinationPicker, setShowDestinationPicker\]/);
  assert.match(source, /const \[destinationSearch, setDestinationSearch\]/);
  assert.match(source, /filteredDestinations/);
  assert.match(source, /toLocaleLowerCase\("vi-VN"\)/);
  assert.match(source, /accessibilityState=\{\{ expanded: showDestinationPicker \}\}/);
  assert.match(source, /Tìm ngân hàng hoặc ví điện tử/);
  assert.match(source, /nestedScrollEnabled/);
  assert.match(source, /setShowDestinationPicker\(false\)/);
  assert.match(source, /accessibilityRole="radio"/);
  assert.doesNotMatch(source, /options=\{destinations\.map/);
  assert.match(source, /withdraw\.bank_enabled/);
  assert.match(source, /withdraw\.wallet_enabled/);
});

test("wallet VND formatting emits rounded integer amounts and current order labels", () => {
  const { formatVnd, statusLabel } = loadTypeScriptModule("../src/features/wallet/format.ts");
  const formatted = formatVnd(50000.49);
  const signed = formatVnd(1250, true);

  assert.match(formatted, /50[.\s]?000/);
  assert.doesNotMatch(formatted, /[,.]49/);
  assert.match(signed, /^\+1[.\s]?250/);
  assert.equal(statusLabel("pending"), "Chờ xác nhận");
  assert.equal(statusLabel("approved"), "Đã xác nhận");
  assert.equal(statusLabel("rejected"), "Bị từ chối");
  assert.equal(statusLabel("unrecorded"), "Chờ sàn ghi nhận");
});
