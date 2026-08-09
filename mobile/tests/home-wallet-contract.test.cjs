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

test("home banner links use safe native Linking handoff", () => {
  const source = read("../src/features/home/HomeScreen.tsx");
  assert.match(source, /normalizeBannerLink\(banner\?\.link/);
  assert.match(source, /Linking\.canOpenURL\(bannerLink\)/);
  assert.match(source, /Linking\.openURL\(bannerLink\)/);
  assert.match(source, /accessibilityRole=\{bannerLink \? "link"/);
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
