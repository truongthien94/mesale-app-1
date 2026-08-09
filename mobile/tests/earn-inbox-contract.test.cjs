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
  assert.equal(errors.length, 0, `${path.basename(filePath)} must transpile without diagnostics`);

  const loadedModule = { exports: {} };
  const execute = new Function("exports", "require", "module", "__filename", "__dirname", output.outputText);
  execute(loadedModule.exports, require, loadedModule, filePath, path.dirname(filePath));
  return loadedModule.exports;
}

const earnContractsPath = path.resolve(__dirname, "../src/features/earn/contracts.ts");
const notificationContractsPath = path.resolve(__dirname, "../src/features/notifications/contracts.ts");
const earnApiPath = path.resolve(__dirname, "../src/features/earn/api.ts");
const notificationApiPath = path.resolve(__dirname, "../src/features/notifications/api.ts");
const taskScreenPath = path.resolve(__dirname, "../app/(tabs)/earn/tasks.tsx");
const giftScreenPath = path.resolve(__dirname, "../app/(tabs)/earn/gifts.tsx");
const giftCodeScreenPath = path.resolve(__dirname, "../app/(tabs)/earn/gift-code.tsx");

const {
  referralsPath,
  checkinPath,
  giftsPath,
  giftRedemptionsPath,
  canClaimTask,
  canSubmitCustomTask,
  requiresPhysicalAddress
} = loadTypeScriptModule(earnContractsPath);
const { notificationsPath } = loadTypeScriptModule(notificationContractsPath);

test("builds the existing referral and nested check-in pagination contracts", () => {
  assert.equal(referralsPath(3, "2", "approved"), "referrals?level=2&status=approved&page=3&per_page=15");
  assert.equal(referralsPath(1), "referrals?page=1&per_page=15");
  assert.equal(checkinPath(4), "checkin?page=4");
});

test("builds gift catalog and redemption history filters without inventing endpoints", () => {
  assert.equal(
    giftsPath(2, { search: "Thẻ quà", tag: "hot", type: "giftcode", sort: "price_asc" }),
    "gifts?search=Th%E1%BA%BB%20qu%C3%A0&tag=hot&type=giftcode&sort=price_asc&page=2&per_page=12"
  );
  assert.equal(giftRedemptionsPath(5, "GFT 10", "pending"), "gifts/redemptions?search=GFT%2010&status=pending&page=5&per_page=10");
});

test("keeps task claim and physical gift validation rules explicit", () => {
  assert.equal(canClaimTask("completed", 25), true);
  assert.equal(canClaimTask("in_progress"), false);
  assert.equal(canClaimTask("pending"), false);
  assert.equal(canClaimTask("claimed"), false);
  assert.equal(canSubmitCustomTask("custom", "in_progress"), true);
  assert.equal(canSubmitCustomTask("custom", "pending"), false);
  assert.equal(canSubmitCustomTask("custom", "completed"), false);
  assert.equal(canSubmitCustomTask("profile", "in_progress"), false);
  assert.equal(requiresPhysicalAddress("physical"), true);
  assert.equal(requiresPhysicalAddress("giftcode"), false);
});

test("builds the notification list contract with type, unread filter, and pagination", () => {
  assert.equal(
    notificationsPath(2, { type: "personal", filter: "unread" }),
    "notifications?type=personal&filter=unread&page=2&per_page=15"
  );
  assert.equal(notificationsPath(1, { filter: "all" }), "notifications?filter=all&page=1&per_page=15");
});

test("uses GET for task sync and Idempotency-Key for reward mutations", () => {
  const apiSource = fs.readFileSync(earnApiPath, "utf8");
  assert.match(apiSource, /tasks\/\$\{taskId\}\/sync`, \{ method: "GET" \}/);
  assert.match(apiSource, /tasks\/\$\{variables\.payload\.taskId\}\/claim[\s\S]*idempotencyHeaders\(variables\.idempotencyKey\)/);
  assert.match(apiSource, /"gifts\/redeem"[\s\S]*idempotencyHeaders\(variables\.idempotencyKey\)/);
  assert.match(apiSource, /"giftcode\/redeem"[\s\S]*idempotencyHeaders\(variables\.idempotencyKey\)/);
});

test("keeps reward idempotency keys stable across explicit retries", () => {
  const taskSource = fs.readFileSync(taskScreenPath, "utf8");
  const giftSource = fs.readFileSync(giftScreenPath, "utf8");
  const giftCodeSource = fs.readFileSync(giftCodeScreenPath, "utf8");
  assert.match(taskSource, /useStableEarnSubmission\("task\.claim"/);
  assert.match(taskSource, /claimMutation\.mutate\(stableClaim\.getVariables\(/);
  assert.match(taskSource, /stableClaim\.reset\(/);
  assert.match(giftSource, /useStableEarnSubmission\("gift\.redeem"/);
  assert.match(giftSource, /mutation\.mutate\(stableSubmission\.getVariables\(/);
  assert.match(giftSource, /stableSubmission\.reset\(/);
  assert.match(giftCodeSource, /useStableEarnSubmission\("giftcode\.redeem"/);
  assert.match(giftCodeSource, /mutation\.mutate\(stableSubmission\.getVariables\(/);
  assert.match(giftCodeSource, /stableSubmission\.reset\(/);
  assert.doesNotMatch(giftCodeSource, /createIdempotentVariables/);
});

test("refreshes account and wallet caches after reward mutations", () => {
  const cacheSource = fs.readFileSync(path.resolve(__dirname, "../src/features/earn/cache.ts"), "utf8");
  const taskSource = fs.readFileSync(taskScreenPath, "utf8");
  const giftSource = fs.readFileSync(giftScreenPath, "utf8");
  const giftCodeSource = fs.readFileSync(giftCodeScreenPath, "utf8");
  assert.match(cacheSource, /queryKey: \["account"\]/);
  assert.match(cacheSource, /queryKey: \["wallet", "account"\]/);
  assert.match(cacheSource, /queryKey: \["wallet", "balance-logs"\]/);
  for (const source of [
    taskSource,
    giftSource,
    giftCodeSource,
    fs.readFileSync(path.resolve(__dirname, "../app/(tabs)/earn/checkin.tsx"), "utf8")
  ]) {
    assert.match(source, /invalidateRewardCaches\(queryClient\)/);
  }
});

test("uses only the existing notification unread and read mutation routes", () => {
  const source = fs.readFileSync(notificationApiPath, "utf8");
  assert.match(source, /"notifications\/unread-count"/);
  assert.match(source, /`notifications\/\$\{id\}\/read`/);
  assert.match(source, /"notifications\/read-all"/);
});
