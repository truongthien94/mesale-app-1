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

test("account summary preserves an unknown pending cashback field during rolling deploy", async () => {
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

  assert.equal((await fetchAccountSummary()).wallet.pendingCashback, null);

  accountPayload = {
    ...accountPayload,
    wallet: { ...accountPayload.wallet, pending_cashback: 1250 }
  };
  assert.equal((await fetchAccountSummary()).wallet.pendingCashback, 1250);

  accountPayload = {
    ...accountPayload,
    wallet: { ...accountPayload.wallet, pending_cashback: 12.5 }
  };
  await assert.rejects(fetchAccountSummary(), /Invalid pending cashback response/);
});

test("successful withdrawal invalidates both Wallet and Home account summaries", async () => {
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
    "@/features/home/hooks": { homeQueryKeys: { account: ["account"] } }
  });

  await useCreateWithdrawal().onSuccess();

  assert.deepEqual(invalidated, [
    ["account"],
    ["wallet", "account"],
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
  const compactAccountValues = source.match(/formatAccountMoney\(account\.wallet\./g) ?? [];

  assert.match(source, /account\.wallet\.balance/);
  assert.match(source, /account\.wallet\.totalCashback/);
  assert.match(source, /account\.wallet\.pendingCashback/);
  assert.match(source, /account\.wallet\.totalWithdrawn/);
  assert.match(source, /router\.push\("\/\(tabs\)\/wallet\/withdrawals"\)/);
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
  assert.match(source, /onCoupons=\{openCouponsQuickAccess\}/);
  assert.match(source, /onTips=\{\(\) => Alert\.alert\(strings\.usageCautionTitle, strings\.usageCautionMessage\)\}/);
  assert.match(source, /if \(\(couponsQuery\.data\?\.length \?\? 0\) === 0\)/);
  assert.match(source, /Alert\.alert\(strings\.huntCoupons, strings\.couponsEmpty\)/);
  assert.match(source, /Alert\.alert\(strings\.quickAccess, strings\.quickAccessUnavailable\)/);
  assert.doesNotMatch(source, /scrollToEnd|tipsSectionY|onTipsLayout/);
  assert.ok(quickAccessIndex > source.indexOf("styles.creatorCard"));
  assert.ok(phoneDemoIndex > quickAccessIndex);
});

test("Round C renders the enabled homepage blocks with live coupon and ranking data, no disabled blog block", () => {
  const source = read("../src/features/home/HomeScreen.tsx");
  assert.match(source, /RoundCHomeBlocks/);
  assert.match(source, /RoundCCouponSection/);
  assert.match(source, /useCoupons\(\)/);
  assert.match(source, /RoundCTimelineSection/);
  assert.match(source, /RoundCLeaderboardSection/);
  assert.match(source, /useRanking\(\)/);
  assert.match(source, /useTheme/);
  assert.doesNotMatch(source, /RoundCBlogSection/);
  assert.doesNotMatch(source, /route\(['"]blog\./);
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
  assert.doesNotMatch(source, /savings|chart|referral_count/i);
});

test("wallet histories use infinite queries and virtualized native lists", () => {
  const api = read("../src/features/wallet/api.ts");
  assert.match(api, /useInfiniteQuery/);
  assert.match(api, /pagePath\("orders"/);
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

test("wallet VND formatting emits rounded integer amounts", () => {
  const { formatVnd } = loadTypeScriptModule("../src/features/wallet/format.ts");
  const formatted = formatVnd(50000.49);
  const signed = formatVnd(1250, true);

  assert.match(formatted, /50[.\s]?000/);
  assert.doesNotMatch(formatted, /[,.]49/);
  assert.match(signed, /^\+1[.\s]?250/);
});
