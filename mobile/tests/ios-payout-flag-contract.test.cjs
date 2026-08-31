const assert = require("node:assert/strict");
const fs = require("node:fs");
const Module = require("node:module");
const path = require("node:path");
const test = require("node:test");
const ts = require("typescript");

function read(relativePath) {
  return fs.readFileSync(path.resolve(__dirname, relativePath), "utf8");
}

function loadFeatureModule(platform) {
  const filePath = path.resolve(__dirname, "../src/config/features.ts");
  const output = ts.transpileModule(fs.readFileSync(filePath, "utf8"), {
    compilerOptions: { module: ts.ModuleKind.CommonJS, target: ts.ScriptTarget.ES2022 },
    fileName: filePath,
    reportDiagnostics: true
  });
  const loadedModule = { exports: {} };
  const originalLoad = Module._load;
  Module._load = function patchedLoad(request, parent, isMain) {
    if (request === "@tanstack/react-query") return { useQuery: () => ({ data: false, isPending: false, refetch: async () => undefined }) };
    if (request === "react") {
      return {
        createContext: () => ({ Provider: "Provider" }),
        createElement: () => null,
        useContext: () => false,
        useEffect: () => undefined
      };
    }
    if (request === "react-native") {
      return {
        AppState: { addEventListener: () => ({ remove: () => undefined }) },
        Platform: { OS: platform }
      };
    }
    if (request === "@/features/config/query") return { appConfigRawQueryOptions: () => ({}) };
    return originalLoad.call(this, request, parent, isMain);
  };
  try {
    const execute = new Function("exports", "require", "module", "__filename", "__dirname", output.outputText);
    execute(loadedModule.exports, require, loadedModule, filePath, path.dirname(filePath));
    return loadedModule.exports;
  } finally {
    Module._load = originalLoad;
  }
}

function loadConfigQueryModule(platform, version) {
  const filePath = path.resolve(__dirname, "../src/features/config/query.ts");
  const output = ts.transpileModule(fs.readFileSync(filePath, "utf8"), {
    compilerOptions: { module: ts.ModuleKind.CommonJS, target: ts.ScriptTarget.ES2022 },
    fileName: filePath,
    reportDiagnostics: true
  });
  const loadedModule = { exports: {} };
  const originalLoad = Module._load;
  Module._load = function patchedLoad(request, parent, isMain) {
    if (request === "expo-constants") return { __esModule: true, default: { expoConfig: { version } } };
    if (request === "react-native") return { Platform: { OS: platform } };
    if (request === "@/api/client") return { request: async () => ({}) };
    return originalLoad.call(this, request, parent, isMain);
  };
  try {
    const execute = new Function("exports", "require", "module", "__filename", "__dirname", output.outputText);
    execute(loadedModule.exports, require, loadedModule, filePath, path.dirname(filePath));
    return loadedModule.exports;
  } finally {
    Module._load = originalLoad;
  }
}

test("iOS missing or malformed remote config fails safe to disabled", () => {
  const features = loadFeatureModule("ios");
  assert.equal(features.resolveIosPayoutFeaturesEnabled("ios", {}), false);
  assert.equal(features.resolveIosPayoutFeaturesEnabled("ios", { features: {} }), false);
  assert.equal(features.resolveIosPayoutFeaturesEnabled("ios", { features: { ios_payout_features_enabled: "true" } }), false);
  assert.equal(features.resolveIosPayoutFeaturesEnabled("ios", { features: { ios_payout_features_enabled: false } }), false);
});

test("iOS enables payout only when remote config contains boolean true", () => {
  const features = loadFeatureModule("ios");
  const config = { features: { ios_payout_features_enabled: true } };
  assert.equal(features.readIosPayoutRemoteFlag(config), true);
  assert.equal(features.resolveIosPayoutFeaturesEnabled("ios", config), true);
});

test("Android and Web bypass the iOS-only flag", () => {
  const features = loadFeatureModule("android");
  assert.equal(features.resolveIosPayoutFeaturesEnabled("android", {}), true);
  assert.equal(features.resolveIosPayoutFeaturesEnabled("web", {}), true);
});

test("config requests send the current platform and app version", () => {
  const configQuery = loadConfigQueryModule("ios", "1.0.1");
  assert.deepEqual(configQuery.createAppConfigHeaders("ios", " 1.0.1 "), {
    "X-Mesale-App-Platform": "ios",
    "X-Mesale-App-Version": "1.0.1"
  });
  assert.deepEqual(configQuery.createAppConfigHeaders("ios", undefined), {
    "X-Mesale-App-Platform": "ios"
  });
  assert.deepEqual(configQuery.appConfigRequestOptions().headers, {
    "X-Mesale-App-Platform": "ios",
    "X-Mesale-App-Version": "1.0.1"
  });
});

test("root provider wraps authentication and defaults safely while config loads", () => {
  const features = read("../src/config/features.ts");
  const rootLayout = read("../app/_layout.tsx");
  assert.match(features, /appConfigRawQueryOptions\(\)/);
  assert.match(features, /select: readIosPayoutRemoteFlag/);
  assert.match(features, /retry: false/);
  assert.doesNotMatch(features, /refetchInterval/);
  assert.match(features, /AppState\.addEventListener\("change"/);
  assert.match(features, /state === "active"/);
  assert.match(features, /Platform\.OS !== "ios" \|\| configQuery\.data === true/);
  assert.match(rootLayout, /<IosPayoutFeaturesProvider>[\s\S]*<AuthProvider>/);
});

test("route guard redirects to Home and Orders is protected", () => {
  const guard = read("../src/components/IosPayoutRouteGuard.tsx");
  const orders = read("../app/(tabs)/orders.tsx");
  const walletLayout = read("../app/(tabs)/wallet/_layout.tsx");
  assert.match(guard, /Redirect href="\/\(tabs\)\/home"/);
  assert.match(guard, /useIosPayoutFeaturesEnabled\(\)/);
  assert.match(orders, /IosPayoutRouteGuard/);
  assert.match(orders, /<OrdersRoute \/>/);
  assert.match(walletLayout, /if \(!payoutFeaturesEnabled\) return <Redirect href="\/\(tabs\)\/home" \/>/);
});

test("all approved direct payout routes guard before their content hooks", () => {
  const routes = [
    "../app/(tabs)/wallet/index.tsx",
    "../app/(tabs)/wallet/balance-logs.tsx",
    "../app/(tabs)/wallet/withdrawals/index.tsx",
    "../app/(tabs)/wallet/withdrawals/create.tsx",
    "../app/(tabs)/wallet/payment-accounts/index.tsx",
    "../app/(tabs)/wallet/payment-accounts/create.tsx",
    "../app/(tabs)/account/finance.tsx",
    "../app/(tabs)/withdraw.tsx",
    "../app/(tabs)/earn/referrals.tsx",
    "../app/(tabs)/earn/tasks.tsx",
    "../app/(tabs)/earn/checkin.tsx",
    "../app/(tabs)/earn/gifts.tsx",
    "../app/(tabs)/earn/gift-code.tsx",
    "../app/(tabs)/earn/gift-history.tsx"
  ];
  for (const route of routes) {
    const source = read(route);
    assert.match(source, /IosPayoutRouteGuard/);
    assert.match(source, /return <IosPayoutRouteGuard><\w+Content \/><\/IosPayoutRouteGuard>/);
  }
});

test("disabled iOS suppresses payout prefetch and Account payment-account mount", () => {
  const prefetch = read("../src/api/AuthenticatedPrefetch.tsx");
  const quickAccess = read("../src/features/home/QuickAccessPrefetch.tsx");
  const account = read("../app/(tabs)/account/index.tsx");
  assert.match(prefetch, /if \(payoutFeaturesEnabled\) \{[\s\S]*prefetchInfiniteQuery\(ordersQueryOptions\(\)\)[\s\S]*prefetchQuery\(paymentAccountsQueryOptions\(\)\)/);
  assert.match(quickAccess, /if \(payoutFeaturesEnabled\) \{/);
  assert.match(account, /payoutFeaturesEnabled \? <AccountPayoutSection/);
  assert.match(account, /function AccountPayoutSection[\s\S]*usePaymentAccounts\(\)/);
});

test("disabled iOS does not render stored financial preview fields", () => {
  const session = read("../src/auth/session.ts");
  assert.match(session, /applyPayoutFeaturePolicy/);
  assert.match(session, /financialSnapshot: _financialSnapshot/);
  assert.match(session, /referral_code: _referralCode/);
  assert.match(session, /referralPromptPending: false/);
  assert.match(session, /wallet: _wallet/);
  assert.match(session, /migratedRecord/);
});

test("the build-time payout flag is removed from Expo and EAS configuration", () => {
  const featureSource = read("../src/config/features.ts");
  const appConfig = read("../app.config.ts");
  const envExample = read("../.env.example");
  const eas = JSON.parse(read("../eas.json"));
  assert.match(featureSource, /Platform\.OS !== "ios" \|\| configQuery\.data === true/);
  assert.doesNotMatch(appConfig, /IOS_PAYOUT_FEATURES_ENABLED|iosPayoutFeaturesEnabled/);
  assert.doesNotMatch(envExample, /IOS_PAYOUT_FEATURES_ENABLED/);
  for (const profile of Object.values(eas.build)) {
    assert.equal(profile.env?.IOS_PAYOUT_FEATURES_ENABLED, undefined);
  }
});

test("restricted iOS account screens use shopping-safe copy and hide currency controls", () => {
  const settings = read("../app/(tabs)/account/settings.tsx");
  const preferences = read("../app/(tabs)/account/preferences.tsx");
  const deletion = read("../app/(tabs)/account/delete.tsx");
  const accountLayout = read("../app/(tabs)/account/_layout.tsx");

  for (const source of [settings, preferences, deletion]) {
    assert.match(source, /useIosPayoutFeaturesEnabled\(\)/);
  }
  assert.match(settings, /payoutFeaturesEnabled \? "Ngôn ngữ & tiền tệ" : "Ngôn ngữ"/);
  assert.match(preferences, /payoutFeaturesEnabled \? \(/);
  assert.match(preferences, /Chọn ngôn ngữ dùng để hiển thị nội dung trong ứng dụng/);
  assert.match(deletion, /Hồ sơ, tùy chọn, sản phẩm đã lưu, thông báo và các phiên đăng nhập/);
  assert.match(accountLayout, /payoutFeaturesEnabled \? "Ngôn ngữ & tiền tệ" : "Ngôn ngữ"/);
});

test("restricted iOS legal links stay inside the dedicated policy set", () => {
  const urls = read("../src/features/legal/urls.ts");
  assert.match(urls, /https:\/\/mesale\.vn\/ios\/privacy/);
  assert.match(urls, /https:\/\/mesale\.vn\/ios\/terms/);
  assert.match(urls, /https:\/\/mesale\.vn\/ios\/support/);
  assert.match(urls, /https:\/\/mesale\.vn\/ios\/account-deletion/);
});
