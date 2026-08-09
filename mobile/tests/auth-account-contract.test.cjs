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
const sessionsScreenPath = path.resolve(__dirname, "../app/(tabs)/account/sessions.tsx");
const deletionScreenPath = path.resolve(__dirname, "../app/(tabs)/account/delete.tsx");
const authProviderPath = path.resolve(__dirname, "../src/auth/AuthProvider.tsx");
const queryClientPath = path.resolve(__dirname, "../src/api/queryClient.ts");
const sessionPath = path.resolve(__dirname, "../src/auth/session.ts");
const clientPath = path.resolve(__dirname, "../src/api/client.ts");

const { accountPaths, normalizePreferences, sessionRevokePath } = loadTypeScriptModule(accountContractsPath);
const { validatePasswordReset, validateRegistration } = loadTypeScriptModule(authValidationPath);

test("uses only the existing auth and account endpoints", () => {
  const authSource = fs.readFileSync(authApiPath, "utf8");
  const accountSource = fs.readFileSync(accountApiPath, "utf8");
  assert.match(authSource, /"auth\/register"/);
  assert.match(authSource, /"auth\/forgot-password"/);
  assert.match(authSource, /"auth\/reset-password"/);
  assert.match(accountSource, /accountPaths\.profile/);
  assert.match(accountSource, /accountPaths\.password/);
  assert.match(accountSource, /accountPaths\.preferences/);
  assert.match(accountSource, /accountPaths\.delete/);
  assert.equal(accountPaths.security, "security");
  assert.equal(accountPaths.sessions, "sessions");
  assert.equal(sessionRevokePath(42), "sessions/42/revoke");
  assert.throws(() => sessionRevokePath(0));
});

test("maps Laravel confirmation fields and the mobile device name", () => {
  const source = fs.readFileSync(authApiPath, "utf8");
  assert.match(source, /password_confirmation: payload\.passwordConfirmation/);
  assert.match(source, /referral_code: payload\.referralCode/);
  assert.match(source, /device_name: mobileDeviceName/);
  assert.match(source, /authenticated: false/);
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
  assert.match(providerSource, /const logout = useCallback/);
});

test("broadcasts expired and malformed stored sessions once and clears authenticated state", async () => {
  let storedSession = JSON.stringify({
    accessToken: "expired-token",
    tokenType: "Bearer",
    expiresAt: "2000-01-01T00:00:00.000Z"
  });
  const secureStore = {
    WHEN_UNLOCKED_THIS_DEVICE_ONLY: "device-only",
    getItemAsync: async () => storedSession,
    setItemAsync: async (_key, value) => { storedSession = value; },
    deleteItemAsync: async () => { storedSession = null; }
  };
  const sessionModule = loadTypeScriptModuleWithMocks(sessionPath, {
    "expo-secure-store": secureStore,
    "react-native": { Platform: { OS: "ios" } }
  });
  let invalidations = 0;
  sessionModule.onSessionInvalidated(() => { invalidations += 1; });

  assert.equal(await sessionModule.loadSession(), null);
  assert.equal(storedSession, null);
  assert.equal(invalidations, 1);
  assert.equal(await sessionModule.loadSession(), null);
  assert.equal(invalidations, 1);

  storedSession = "{malformed-json";
  assert.equal(await sessionModule.loadSession(), null);
  assert.equal(storedSession, null);
  assert.equal(invalidations, 2);
  assert.equal(await sessionModule.loadSession(), null);
  assert.equal(invalidations, 2);

  const providerSource = fs.readFileSync(authProviderPath, "utf8");
  assert.match(providerSource, /onSessionInvalidated\(\(\) => \{[\s\S]*clearAppQueryCache\(queryClient\)[\s\S]*setSession\(null\)[\s\S]*setUser\(null\)/);
});

test("keeps 401 and explicit logout invalidation single-owner", async () => {
  let storedSession = null;
  const secureStore = {
    WHEN_UNLOCKED_THIS_DEVICE_ONLY: "device-only",
    getItemAsync: async () => storedSession,
    setItemAsync: async (_key, value) => { storedSession = value; },
    deleteItemAsync: async () => { storedSession = null; }
  };
  const sessionModule = loadTypeScriptModuleWithMocks(sessionPath, {
    "expo-secure-store": secureStore,
    "react-native": { Platform: { OS: "android" } }
  });
  let invalidations = 0;
  sessionModule.onSessionInvalidated(() => { invalidations += 1; });

  await sessionModule.saveSession({ accessToken: "active-token", tokenType: "Bearer" });
  assert.equal(await sessionModule.clearSessionIfTokenMatches("active-token"), true);
  assert.equal(invalidations, 0);
  await sessionModule.clearSession();
  assert.equal(invalidations, 0);

  const clientSource = fs.readFileSync(clientPath, "utf8");
  assert.equal((clientSource.match(/notifySessionInvalidated\(\)/g) ?? []).length, 1);
});
