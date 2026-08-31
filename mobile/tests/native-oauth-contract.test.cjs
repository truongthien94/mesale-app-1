const assert = require("node:assert/strict");
const fs = require("node:fs");
const path = require("node:path");
const test = require("node:test");
const ts = require("typescript");

function loadTypeScriptModule(filePath) {
  const source = fs.readFileSync(filePath, "utf8");
  const output = ts.transpileModule(source, {
    compilerOptions: { module: ts.ModuleKind.CommonJS, target: ts.ScriptTarget.ES2022 },
    fileName: filePath,
    reportDiagnostics: true
  });
  const errors = output.diagnostics?.filter((diagnostic) => diagnostic.category === ts.DiagnosticCategory.Error) ?? [];
  assert.equal(errors.length, 0, `${path.basename(filePath)} must transpile without diagnostics`);
  const loadedModule = { exports: {} };
  const execute = new Function("exports", "require", "module", "__filename", "__dirname", output.outputText);
  execute(loadedModule.exports, require, loadedModule, filePath, path.dirname(filePath));
  return loadedModule.exports;
}

const contractPath = path.resolve(__dirname, "../src/features/auth/nativeOAuthContract.ts");
const loginScreenPath = path.resolve(__dirname, "../app/(auth)/login.tsx");
const deletionScreenPath = path.resolve(__dirname, "../app/(tabs)/account/delete.tsx");
const {
  buildAppleDeletionRequest,
  buildAppleOAuthRequest,
  buildGoogleOAuthRequest,
  mapOAuthError
} = loadTypeScriptModule(contractPath);

const appleCredential = {
  identityToken: "apple-identity-token",
  authorizationCode: "apple-authorization-code",
  nonce: "0123456789abcdef0123456789abcdef"
};

test("builds the exact Laravel native OAuth request payloads", () => {
  assert.deepEqual(buildGoogleOAuthRequest("google-id-token"), {
    id_token: "google-id-token",
    device_name: "Mesale Mobile"
  });
  assert.deepEqual(buildAppleOAuthRequest(appleCredential), {
    identity_token: "apple-identity-token",
    authorization_code: "apple-authorization-code",
    nonce: "0123456789abcdef0123456789abcdef",
    device_name: "Mesale Mobile"
  });
  assert.deepEqual(buildAppleDeletionRequest(appleCredential), {
    apple_identity_token: "apple-identity-token",
    apple_authorization_code: "apple-authorization-code",
    apple_nonce: "0123456789abcdef0123456789abcdef"
  });
  assert.throws(() => buildGoogleOAuthRequest("  "));
});

test("maps every required Laravel OAuth error to a distinct UI state", () => {
  const codes = [
    "ACCOUNT_LINK_REQUIRED",
    "OAUTH_EMAIL_UNVERIFIED",
    "OAUTH_EMAIL_REQUIRED",
    "OAUTH_IDENTITY_AMBIGUOUS",
    "OAUTH_CREDENTIAL_INVALID",
    "OAUTH_PROVIDER_UNAVAILABLE",
    "OAUTH_REQUEST_IN_PROGRESS",
    "NETWORK_ERROR",
    "REQUEST_TIMEOUT",
    "ENDPOINT_DISABLED"
  ];
  const states = codes.map((code) => mapOAuthError(code, code === "OAUTH_PROVIDER_UNAVAILABLE" ? 503 : 409, "google", "en"));

  assert.deepEqual(states.map((state) => state.code), codes);
  assert.equal(new Set(states.map((state) => state.kind)).size, codes.length);
  assert.equal(new Set(states.map((state) => state.message)).size, codes.length);
  assert.equal(states.at(-1).disablesProvider, true);
});

test("keeps API-disabled, bare 503, cancellation, and client configuration states separate", () => {
  const states = [
    mapOAuthError("API_DISABLED", 503, "apple", "vi"),
    mapOAuthError(undefined, 503, "apple", "vi"),
    mapOAuthError("NATIVE_AUTH_CANCELLED", 0, "apple", "vi"),
    mapOAuthError("GOOGLE_CLIENT_NOT_CONFIGURED", 0, "google", "vi")
  ];

  assert.deepEqual(states.map((state) => state.kind), [
    "api-disabled",
    "service-unavailable",
    "cancelled",
    "client-not-configured"
  ]);
  assert.equal(states[2].isCancellation, true);
  assert.equal(states[0].disablesProvider, true);
  assert.equal(states[3].disablesProvider, true);
  const developerError = mapOAuthError("GOOGLE_DEVELOPER_ERROR", 0, "google", "vi");
  assert.equal(developerError.kind, "client-not-configured");
  assert.equal(developerError.disablesProvider, true);
  assert.match(developerError.message, /vn\.mesale\.app/);
  assert.match(developerError.message, /Cáº¥u hÃ¬nh|Cấu hình/);

  const appleAuthorizationError = mapOAuthError("APPLE_AUTHORIZATION_FAILED", 0, "apple", "vi");
  assert.equal(appleAuthorizationError.kind, "apple-authorization-failed");
  assert.match(appleAuthorizationError.message, /Apple/);
});

test("maps a 429 Apple response to a retryable rate-limit state", () => {
  const state = mapOAuthError(undefined, 429, "apple", "vi", 17);

  assert.equal(state.kind, "rate-limited");
  assert.equal(state.code, "RATE_LIMITED");
  assert.equal(state.retryAfterSeconds, 17);
  assert.match(state.message, /17 giây/);
});

test("wires native provider actions into login and Apple reauthentication into deletion", () => {
  const loginSource = fs.readFileSync(loginScreenPath, "utf8");
  const deletionSource = fs.readFileSync(deletionScreenPath, "utf8");

  assert.match(loginSource, /loginWithGoogle/);
  assert.match(loginSource, /AppleAuthenticationButton/);
  assert.match(loginSource, /oauthUiStateFromReason/);
  assert.match(deletionSource, /APPLE_REAUTH_REQUIRED_FOR_DELETION/);
  assert.match(deletionSource, /requestAppleNativeCredential/);
  assert.doesNotMatch(deletionSource, /apple_identity_token[^\n]*AccountField/);
});

test("keeps Google visible on every native login build and orders Apple first on iOS", () => {
  const loginSource = fs.readFileSync(loginScreenPath, "utf8");
  const appleButton = loginSource.indexOf("AppleAuthentication.AppleAuthenticationButton");
  const googleButton = loginSource.indexOf('accessibilityLabel="Tiếp tục với Google"');

  assert.equal(googleButton > -1, true);
  assert.equal(appleButton > -1, true);
  assert.equal(appleButton < googleButton, true);
  assert.doesNotMatch(loginSource, /isGoogleNativeSignInConfigured/);
  assert.doesNotMatch(loginSource, /Google chưa được cấu hình cho bản build này/);
  assert.match(loginSource, /onPress=\{\(\) => void submitNative\("google"\)\}/);
  assert.match(loginSource, /oauthUiStateFromReason/);
});

test("login redesign follows the active theme and exposes password visibility", () => {
  const loginSource = fs.readFileSync(loginScreenPath, "utf8");

  assert.match(loginSource, /useTheme\(\)/);
  assert.match(loginSource, /LinearGradient/);
  assert.match(loginSource, /mesale-logo\.png/);
  assert.match(loginSource, /function GoogleLogo\(\)/);
  assert.match(loginSource, /fill="#4285f4"/);
  assert.match(loginSource, /secureTextEntry=\{!passwordVisible\}/);
  assert.match(loginSource, /passwordVisible \? "Ẩn mật khẩu" : "Hiện mật khẩu"/);
  assert.match(loginSource, /Chào mừng trở lại/);
  assert.match(loginSource, /useIosPayoutFeaturesEnabled\(\)/);
  assert.match(loginSource, /Hệ thống mua sắm hoàn tiền Shopee - TikTok/);
  assert.match(loginSource, /Khám phá sản phẩm và ưu đãi Shopee - TikTok Shop/);
  assert.doesNotMatch(loginSource, /Đăng nhập để quản lý cashback và rút tiền/);
  assert.doesNotMatch(loginSource, /Đăng nhập an toàn qua máy chủ Mê Sale/);
  assert.doesNotMatch(loginSource, /<LegalLink label="Hỗ trợ"/);
  assert.doesNotMatch(loginSource, /<LegalLink label="Xóa tài khoản"/);
  assert.match(loginSource, /footer: \{[\s\S]*?marginTop: "auto"/);
  assert.doesNotMatch(loginSource, /import \{ colors, spacing \} from "@\/theme\/tokens"/);
});
