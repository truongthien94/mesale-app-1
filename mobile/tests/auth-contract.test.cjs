const assert = require("node:assert/strict");
const fs = require("node:fs");
const path = require("node:path");
const test = require("node:test");
const ts = require("typescript");

function loadTypeScriptModule(filePath) {
  const source = fs.readFileSync(filePath, "utf8");
  const output = ts.transpileModule(source, {
    compilerOptions: {
      module: ts.ModuleKind.CommonJS,
      target: ts.ScriptTarget.ES2022
    },
    fileName: filePath,
    reportDiagnostics: true
  });
  const errors = output.diagnostics?.filter((diagnostic) => diagnostic.category === ts.DiagnosticCategory.Error) ?? [];
  assert.equal(errors.length, 0, "auth contract parser must transpile without diagnostics");

  const loadedModule = { exports: {} };
  const execute = new Function("exports", "require", "module", "__filename", "__dirname", output.outputText);
  execute(loadedModule.exports, require, loadedModule, filePath, path.dirname(filePath));
  return loadedModule.exports;
}

const parserPath = path.resolve(__dirname, "../src/api/authContract.ts");
const { AuthContractError, parseLoginResult, requireAuthenticated } = loadTypeScriptModule(parserPath);
const now = Date.parse("2026-08-09T00:00:00.000Z");
const futureExpiry = "2026-08-10T00:00:00.000Z";
const user = { id: 42, name: "Member", email: "member@example.test" };

test("parses the canonical authenticated Bearer response", () => {
  const result = parseLoginResult({
    access_token: "session-token",
    token_type: "Bearer",
    expires_at: futureExpiry,
    user
  }, now);

  assert.equal(result.kind, "authenticated");
  assert.equal(result.session.accessToken, "session-token");
  assert.equal(result.session.expiresAt, futureExpiry);
  assert.deepEqual(result.user, user);
});

test("accepts the temporary token alias only when it is a Bearer session", () => {
  const result = parseLoginResult({ token: "legacy-alias", token_type: "bearer", expires_at: null, user }, now);
  assert.equal(result.kind, "authenticated");
  assert.equal(result.session.accessToken, "legacy-alias");
});

test("parses email verification without constructing a session", () => {
  const result = parseLoginResult({ email_verification_required: true, email: "member@example.test" }, now);
  assert.deepEqual(result, { kind: "email-verification", email: "member@example.test" });
  assert.equal("session" in result, false);
  assert.throws(() => requireAuthenticated(result), AuthContractError);
});

test("parses and deduplicates the Laravel 2FA methods", () => {
  const result = parseLoginResult({
    two_factor_required: true,
    challenge_token: "challenge-token",
    methods: ["google2fa", "email_otp", "email_otp"]
  }, now);

  assert.deepEqual(result, {
    kind: "two-factor",
    challengeToken: "challenge-token",
    methods: ["google2fa", "email_otp"]
  });
  assert.equal("session" in result, false);
});

test("rejects missing tokens, expired tokens, and malformed continuations", () => {
  assert.throws(() => parseLoginResult({ token_type: "Bearer", user }, now), AuthContractError);
  assert.throws(() => parseLoginResult({
    access_token: "canonical",
    token: "different-alias",
    token_type: "Bearer",
    expires_at: futureExpiry,
    user
  }, now), AuthContractError);
  assert.throws(() => parseLoginResult({
    access_token: "expired",
    token_type: "Bearer",
    expires_at: "2026-08-08T00:00:00.000Z",
    user
  }, now), AuthContractError);
  assert.throws(() => parseLoginResult({ two_factor_required: true, challenge_token: "challenge", methods: [] }, now), AuthContractError);
  assert.throws(() => parseLoginResult({ two_factor_required: true, challenge_token: "challenge", methods: ["sms"] }, now), AuthContractError);
  assert.throws(() => parseLoginResult({
    email_verification_required: true,
    two_factor_required: true,
    email: "member@example.test",
    challenge_token: "challenge",
    methods: ["email_otp"]
  }, now), AuthContractError);
});
