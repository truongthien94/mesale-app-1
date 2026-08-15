const assert = require("node:assert/strict");
const fs = require("node:fs");
const path = require("node:path");
const test = require("node:test");
const ts = require("typescript");

function loadTypeScriptModule(filePath) {
  return loadTypeScriptModuleWithMocks(filePath, {});
}

function loadTypeScriptModuleWithMocks(filePath, mocks) {
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
  const mockedRequire = (specifier) => Object.hasOwn(mocks, specifier) ? mocks[specifier] : require(specifier);
  execute(loadedModule.exports, mockedRequire, loadedModule, filePath, path.dirname(filePath));
  return loadedModule.exports;
}

const accountContractsPath = path.resolve(__dirname, "../src/features/account/contracts.ts");
const authValidationPath = path.resolve(__dirname, "../src/features/auth/validation.ts");
const authApiPath = path.resolve(__dirname, "../src/api/auth.ts");
const accountApiPath = path.resolve(__dirname, "../src/features/account/api.ts");
const accountHubPath = path.resolve(__dirname, "../app/(tabs)/account/index.tsx");
const accountInformationPath = path.resolve(__dirname, "../app/(tabs)/account/information.tsx");
const accountFinancePath = path.resolve(__dirname, "../app/(tabs)/account/finance.tsx");
const accountSettingsPath = path.resolve(__dirname, "../app/(tabs)/account/settings.tsx");
const sessionsScreenPath = path.resolve(__dirname, "../app/(tabs)/account/sessions.tsx");
const deletionScreenPath = path.resolve(__dirname, "../app/(tabs)/account/delete.tsx");
const securityScreenPath = path.resolve(__dirname, "../app/(tabs)/account/security.tsx");
const passwordScreenPath = path.resolve(__dirname, "../app/(tabs)/account/password.tsx");
const authProviderPath = path.resolve(__dirname, "../src/auth/AuthProvider.tsx");
const authRoutingPath = path.resolve(__dirname, "../src/auth/routing.ts");
const indexScreenPath = path.resolve(__dirname, "../app/index.tsx");
const tabsLayoutPath = path.resolve(__dirname, "../app/(tabs)/_layout.tsx");
const registerScreenPath = path.resolve(__dirname, "../app/(auth)/register.tsx");
const referralScreenPath = path.resolve(__dirname, "../app/(auth)/referral-code.tsx");
const queryClientPath = path.resolve(__dirname, "../src/api/queryClient.ts");
const sessionPath = path.resolve(__dirname, "../src/auth/session.ts");
const clientPath = path.resolve(__dirname, "../src/api/client.ts");

const { accountPaths, normalizePreferences, sessionRevokePath } = loadTypeScriptModule(accountContractsPath);
const { validatePasswordReset, validateRegistration } = loadTypeScriptModule(authValidationPath);
const { resolveAuthGate } = loadTypeScriptModule(authRoutingPath);

test("uses only the existing auth and account endpoints", () => {
  const authSource = fs.readFileSync(authApiPath, "utf8");
  const accountSource = fs.readFileSync(accountApiPath, "utf8");
  assert.match(authSource, /"auth\/register"/);
  assert.match(authSource, /"auth\/forgot-password"/);
  assert.match(authSource, /"auth\/reset-password"/);
  assert.match(authSource, /"account\/referral-code"/);
  assert.match(accountSource, /accountPaths\.profile/);
  assert.match(accountSource, /accountPaths\.password/);
  assert.match(accountSource, /accountPaths\.preferences/);
  assert.match(accountSource, /accountPaths\.delete/);
  assert.equal(accountPaths.security, "security");
  assert.equal(accountPaths.sessions, "sessions");
  assert.equal(sessionRevokePath(42), "sessions/42/revoke");
  assert.throws(() => sessionRevokePath(0));
});

test("links security to the single password-change flow without exposing technical copy", () => {
  const securitySource = fs.readFileSync(securityScreenPath, "utf8");
  const passwordSource = fs.readFileSync(passwordScreenPath, "utf8");

  assert.match(securitySource, /<AccountHeader title="Bảo mật tài khoản" \/>/);
  assert.match(securitySource, /router\.push\("\/\(tabs\)\/account\/password"\)/);
  assert.match(securitySource, /title="Đổi mật khẩu"/);
  assert.doesNotMatch(securitySource, /Thiết lập xác thực hai lớp trực tiếp với Laravel/);
  assert.match(passwordSource, /useChangePassword\(\)/);
  assert.match(passwordSource, /currentPassword, password, passwordConfirmation: confirmation/);
  assert.match(passwordSource, /secureTextEntry=\{!visible\}/);
  assert.match(passwordSource, /Mật khẩu mới phải có ít nhất 8 ký tự/);
  assert.match(passwordSource, /Mật khẩu xác nhận không khớp/);
  assert.match(passwordSource, /mutation\.isPending/);
  assert.match(passwordSource, /mutation\.isSuccess/);
  assert.doesNotMatch(passwordSource, /AsyncStorage|SecureStore|setItem/);
});

test("keeps account categories collapsed on the hub and preserves the existing child contracts", () => {
  const hubSource = fs.readFileSync(accountHubPath, "utf8");
  const informationSource = fs.readFileSync(accountInformationPath, "utf8");
  const financeSource = fs.readFileSync(accountFinancePath, "utf8");
  const settingsSource = fs.readFileSync(accountSettingsPath, "utf8");

  for (const route of ["information", "finance", "settings"]) {
    assert.match(hubSource, new RegExp(`account/${route}`));
  }
  for (const removedLabel of ["TÀI KHOẢN", "TÀI CHÍNH", "THÔNG BÁO", "CÀI ĐẶT"]) {
    assert.doesNotMatch(hubSource, new RegExp(`>${removedLabel}<`));
  }
  assert.match(hubSource, /primaryMenuStack: \{ gap: 10 \}/);
  for (const title of ["Thông tin cá nhân", "Bảo mật tài khoản", "Phiên đăng nhập"]) {
    assert.match(informationSource, new RegExp(title));
    assert.doesNotMatch(hubSource, new RegExp(`title="${title}"`));
  }
  assert.match(financeSource, /usePaymentAccounts\(\)/);
  assert.match(financeSource, /useWithdrawals\(\)/);
  assert.match(settingsSource, /useAccount\(\)/);
  assert.match(settingsSource, /accountQuery\.data\?\.wallet\?\.currency/);
});

test("shows referral entry from Laravel account or auth eligibility without trusting device time", () => {
  const hubSource = fs.readFileSync(accountHubPath, "utf8");
  assert.match(hubSource, /typeof account\.referral_code_eligible === "boolean"/);
  assert.match(hubSource, /\? account\.referral_code_eligible[\s\S]*: user\?\.referralCodeEligible === true/);
  assert.match(hubSource, /account\.referral_code_expires_at !== undefined/);
  assert.match(hubSource, /: user\?\.referralCodeExpiresAt/);
  assert.match(hubSource, /\{referralCodeEligible \? \(/);
  assert.match(hubSource, /referralEntryExpanded \? "Thu gọn" : "Nhập ngay"/);
  assert.match(hubSource, /setReferralEntryExpanded\(\(value\) => !value\)/);
  assert.match(hubSource, /referralEntryPanel/);
  assert.match(hubSource, /Hạn nhập mã do máy chủ Mê Sale xác nhận/);
  assert.match(hubSource, /Chỉ áp dụng trong 3 ngày đầu sau khi đăng ký/);
  assert.doesNotMatch(hubSource, /Date\.now\(\)|85%|24h/i);
});

test("maps Laravel confirmation fields and the mobile device name", () => {
  const source = fs.readFileSync(authApiPath, "utf8");
  const registerSource = fs.readFileSync(registerScreenPath, "utf8");
  assert.match(source, /password_confirmation: payload\.passwordConfirmation/);
  assert.doesNotMatch(source, /referral_code: payload\.referralCode/);
  assert.doesNotMatch(registerSource, /referralCode|Mã giới thiệu \(tùy chọn\)/);
  assert.match(source, /device_name: mobileDeviceName/);
  assert.match(source, /authenticated: false/);
});

test("registration matches the branded native layout without an inline referral field", () => {
  const registerSource = fs.readFileSync(registerScreenPath, "utf8");

  assert.match(registerSource, /source=\{require\("\.\.\/\.\.\/assets\/mesale-logo\.png"\)\}/);
  assert.match(registerSource, /<LinearGradient/);
  assert.match(registerSource, />Tạo tài khoản<\/Text>/);
  assert.match(registerSource, /Tiếp tục với Google/);
  assert.match(registerSource, /AppleAuthentication\.AppleAuthenticationButton/);
  assert.match(registerSource, /Bằng việc tạo tài khoản, bạn đồng ý/);
  assert.doesNotMatch(registerSource, /referralCode|Mã giới thiệu/);
  assert.match(registerSource, /const \[phone, setPhone\] = useState\(""\)/);
  assert.match(registerSource, /placeholder="Số điện thoại"/);
  assert.match(registerSource, /await register\(\{ name, email, phone, password, passwordConfirmation \}\)/);
});

test("gates auth continuations and routes eligible sessions to referral onboarding", () => {
  assert.equal(resolveAuthGate({ kind: "email-verification", email: "member@example.test" }, true, true), "/verify-email");
  assert.equal(resolveAuthGate({ kind: "two-factor", challengeToken: "challenge", methods: ["email_otp"] }, true, true), "/two-factor");
  assert.equal(resolveAuthGate(null, true, true), "/referral-code");
  assert.equal(resolveAuthGate(null, true, false), "/home");
  assert.equal(resolveAuthGate(null, false, true), null);

  const indexSource = fs.readFileSync(indexScreenPath, "utf8");
  const tabsSource = fs.readFileSync(tabsLayoutPath, "utf8");
  assert.match(indexSource, /resolveAuthGate/);
  assert.match(tabsSource, /authGate !== "\/home"/);
  assert.match(tabsSource, /user\?\.referralPromptPending \?\? false/);

  for (const screen of ["login.tsx", "register.tsx", "verify-email.tsx", "two-factor.tsx"]) {
    const source = fs.readFileSync(path.resolve(__dirname, `../app/(auth)/${screen}`), "utf8");
    assert.match(source, /resolveAuthGate/, `${screen} must use the shared continuation-first auth gate`);
    assert.doesNotMatch(source, /Redirect href="\/home"/, `${screen} must not bypass the referral gate`);
  }
  const referralSource = fs.readFileSync(referralScreenPath, "utf8");
  assert.match(referralSource, /resolveAuthGate/);
  assert.match(referralSource, /user\?\.referralCodeEligible === true/);
});

test("keeps the three-day referral route available to eligible sessions and refreshes terminal states", () => {
  const authSource = fs.readFileSync(authApiPath, "utf8");
  const screenSource = fs.readFileSync(referralScreenPath, "utf8");
  assert.match(authSource, /body: \{ referral_code: referralCode\.trim\(\) \}/);
  assert.match(authSource, /body: \{ skip: true \}/);
  assert.match(authSource, /return parseUser\(await request<unknown>\("account"\)\)/);
  assert.match(screenSource, /<FormErrorSummary/);
  assert.equal((screenSource.match(/await settleReferralPrompt\(\)/g) ?? []).length, 4);
  assert.match(screenSource, /REFERRAL_PROMPT_ALREADY_DECIDED/);
  assert.match(screenSource, /user\?\.referralCodeEligible === true/);
  assert.match(screenSource, /if \(!session \|\| pendingAuth\)/);
  for (const code of ["REFERRAL_WINDOW_EXPIRED", "REFERRAL_NOT_ELIGIBLE", "REFERRAL_ALREADY_LINKED", "REFERRAL_DISABLED"]) {
    assert.match(screenSource, new RegExp(code));
  }
  assert.match(screenSource, /3 ngày đầu sau khi đăng ký/);
  assert.doesNotMatch(screenSource, /85%|24h/i);
  assert.doesNotMatch(screenSource, /already decided|already skipped|đã quyết định|đã bỏ qua/i);
  const applyFlow = screenSource.slice(screenSource.indexOf("async function applyCode"), screenSource.indexOf("async function skip"));
  assert.match(applyFlow, /catch \(reason\)[\s\S]*setRequestError/);
  assert.doesNotMatch(applyFlow, /skipReferralPrompt/);
});

test("normalizes partial locale and currency preferences without inventing wallet conversion", () => {
  assert.deepEqual(normalizePreferences({ locale: " EN ", currency: " usd " }), { locale: "en", currency: "USD" });
  assert.deepEqual(normalizePreferences({ locale: " vi " }), { locale: "vi" });
  const source = fs.readFileSync(accountApiPath, "utf8");
  assert.doesNotMatch(source, /wallet\.currency|exchange_rate|balance\s*[*/]/);
});

test("validates registration terms and reset-password confirmation locally", () => {
  assert.deepEqual(validateRegistration({ email: "member@example.test", password: "password123", passwordConfirmation: "password123", acceptedTerms: true }), {});
  assert.equal(validateRegistration({ email: "bad", password: "short", passwordConfirmation: "other", acceptedTerms: false }).terms.length > 0, true);
  assert.deepEqual(validatePasswordReset({ token: "reset-token", email: "member@example.test", password: "password123", passwordConfirmation: "password123" }), {});
  assert.equal(validatePasswordReset({ token: "", email: "bad", password: "short", passwordConfirmation: "other" }).token.length > 0, true);
});

test("virtualizes sessions and prevents revoking the current token in the UI", () => {
  const source = fs.readFileSync(sessionsScreenPath, "utf8");
  assert.match(source, /<FlatList/);
  assert.match(source, /!item\.is_current/);
  assert.match(source, /useRevokeSession/);
});

test("does not ask users to paste raw provider credentials for account deletion", () => {
  const source = fs.readFileSync(deletionScreenPath, "utf8");
  assert.match(source, /PROVIDER_REAUTHENTICATION_REQUIRED/);
  assert.match(source, /Sign in with Apple/);
  assert.doesNotMatch(source, /google_id_token[^\n]*AccountField/);
  assert.doesNotMatch(source, /apple_identity_token[^\n]*AccountField/);
  assert.doesNotMatch(source, /authorization_code[^\n]*AccountField/);
});

test("clears TanStack Query data at every authenticated-session boundary", () => {
  const providerSource = fs.readFileSync(authProviderPath, "utf8");
  const queryClientSource = fs.readFileSync(queryClientPath, "utf8");
  assert.match(queryClientSource, /queryClient\.clear\(\)/);
  assert.ok((providerSource.match(/clearAppQueryCache\(queryClient\)/g) ?? []).length >= 4);
  assert.match(providerSource, /onSessionInvalidated/);
  assert.match(providerSource, /completeAccountDeletion/);
  assert.match(providerSource, /const settleReferralPrompt = useCallback\([\s\S]*referralPromptPending: false[\s\S]*await refreshUser\(\)/);
  assert.match(providerSource, /const logout = useCallback/);
});

test("broadcasts expired and malformed stored sessions once and clears authenticated state", async () => {
  const storedItems = new Map([["mesale.session.v1", JSON.stringify({
    accessToken: "expired-token",
    tokenType: "Bearer",
    expiresAt: "2000-01-01T00:00:00.000Z"
  })]]);
  const secureStore = {
    WHEN_UNLOCKED_THIS_DEVICE_ONLY: "device-only",
    getItemAsync: async (key) => storedItems.get(key) ?? null,
    setItemAsync: async (key, value) => { storedItems.set(key, value); },
    deleteItemAsync: async (key) => { storedItems.delete(key); }
  };
  const authContract = loadTypeScriptModule(path.resolve(__dirname, "../src/api/authContract.ts"));
  const sessionModule = loadTypeScriptModuleWithMocks(sessionPath, {
    "expo-secure-store": secureStore,
    "react-native": { Platform: { OS: "ios" } },
    "@/api/authContract": authContract
  });
  let invalidations = 0;
  sessionModule.onSessionInvalidated(() => { invalidations += 1; });

  assert.equal(await sessionModule.loadAuthState(), null);
  assert.equal(storedItems.has("mesale.session.v1"), false);
  assert.deepEqual(JSON.parse(storedItems.get("mesale.auth.v2")), { version: 2, auth: null });
  assert.equal(invalidations, 1);
  assert.equal(await sessionModule.loadAuthState(), null);
  assert.equal(invalidations, 1);

  storedItems.set("mesale.auth.v2", "{malformed-json");
  assert.equal(await sessionModule.loadAuthState(), null);
  assert.deepEqual(JSON.parse(storedItems.get("mesale.auth.v2")), { version: 2, auth: null });
  assert.equal(invalidations, 2);
  assert.equal(await sessionModule.loadAuthState(), null);
  assert.equal(invalidations, 2);

  const providerSource = fs.readFileSync(authProviderPath, "utf8");
  assert.match(providerSource, /onSessionInvalidated\(\(\) => \{[\s\S]*clearAppQueryCache\(queryClient\)[\s\S]*setSession\(null\)[\s\S]*setUser\(null\)/);
});

test("keeps 401 and explicit logout invalidation single-owner", async () => {
  const storedItems = new Map();
  const secureStore = {
    WHEN_UNLOCKED_THIS_DEVICE_ONLY: "device-only",
    getItemAsync: async (key) => storedItems.get(key) ?? null,
    setItemAsync: async (key, value) => { storedItems.set(key, value); },
    deleteItemAsync: async (key) => { storedItems.delete(key); }
  };
  const authContract = loadTypeScriptModule(path.resolve(__dirname, "../src/api/authContract.ts"));
  const sessionModule = loadTypeScriptModuleWithMocks(sessionPath, {
    "expo-secure-store": secureStore,
    "react-native": { Platform: { OS: "android" } },
    "@/api/authContract": authContract
  });
  let invalidations = 0;
  sessionModule.onSessionInvalidated(() => { invalidations += 1; });

  await sessionModule.saveAuthState(
    { accessToken: "active-token", tokenType: "Bearer" },
    { id: 42, name: "Mobile User", referralPromptPending: true }
  );
  const storedAuth = await sessionModule.loadAuthState();
  assert.equal(storedAuth.session.accessToken, "active-token");
  assert.equal(storedAuth.userPreview.id, 42);
  assert.equal(storedAuth.userPreview.referralPromptPending, true);
  assert.equal(storedAuth.requiresBootstrap, false);
  assert.equal(await sessionModule.clearSessionIfTokenMatches("another-token"), false);
  assert.equal(await sessionModule.clearSessionIfTokenMatches("active-token"), true);
  assert.equal(invalidations, 0);
  assert.deepEqual(JSON.parse(storedItems.get("mesale.auth.v2")), { version: 2, auth: null });
  await sessionModule.clearSession();
  assert.equal(invalidations, 0);

  const clientSource = fs.readFileSync(clientPath, "utf8");
  assert.equal((clientSource.match(/notifySessionInvalidated\(\)/g) ?? []).length, 1);
});

test("legacy sessions bootstrap once while v2 previews unblock restore without seeding query data", () => {
  const providerSource = fs.readFileSync(authProviderPath, "utf8");
  const sessionSource = fs.readFileSync(sessionPath, "utf8");

  assert.match(sessionSource, /legacySessionStorageKey = "mesale\.session\.v1"/);
  assert.match(sessionSource, /authStorageKey = "mesale\.auth\.v2"/);
  assert.match(sessionSource, /previewForAccessToken !== auth\.session\.accessToken/);
  assert.match(sessionSource, /requiresBootstrap: true/);
  assert.match(providerSource, /if \(saved\.userPreview\) \{[\s\S]*setUser\(saved\.userPreview\)[\s\S]*setLoading\(false\)/);
  assert.match(providerSource, /fetchQuery\(\{ \.\.\.accountDetailQueryOptions\(\), staleTime: 0 \}\)/);
  assert.match(providerSource, /restoredUser\.id !== saved\.userPreview\.id/);
  assert.match(providerSource, /saveAuthState\(saved\.session, restoredUser\)/);
  assert.doesNotMatch(providerSource, /setQueryData|initialData|placeholderData/);
});
