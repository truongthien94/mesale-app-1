const assert = require("node:assert/strict");
const fs = require("node:fs");
const path = require("node:path");
const test = require("node:test");

function read(relativePath) {
  return fs.readFileSync(path.resolve(__dirname, relativePath), "utf8");
}

test("Wallet renders from the authenticated financial snapshot without seeding query cache", () => {
  const source = read("../app/(tabs)/wallet/index.tsx");

  assert.match(source, /const \{ user \} = useAuth\(\)/);
  assert.match(source, /const authSnapshot = user\?\.financialSnapshot/);
  assert.match(source, /liveAccount\?\.wallet\.balance \?\? authSnapshot\?\.balance/);
  assert.match(source, /liveAccount\?\.wallet\.totalCashback \?\? authSnapshot\?\.totalCashback/);
  assert.match(source, /liveAccount\?\.wallet\.totalWithdrawn \?\? authSnapshot\?\.totalWithdrawn/);
  assert.match(source, /pendingOrders = liveAccount\?\.stats\.ordersPending/);
  assert.match(source, /accountQuery\.isError[\s\S]*accountQuery\.refetch\(\)/);
  assert.doesNotMatch(source, /if \(accountQuery\.isPending\) return <LoadingState/);
  assert.doesNotMatch(source, /initialData|placeholderData|setQueryData/);
  assert.doesNotMatch(source, /authSnapshot.*orders_pending|pendingOrders.*\?\?\s*0/);
});

test("Withdraw shell previews only balance and keeps authoritative dependencies submission-gated", () => {
  const source = read("../app/(tabs)/wallet/withdrawals/create.tsx");

  assert.match(source, /const previewBalance = user\?\.financialSnapshot\?\.balance/);
  assert.match(source, /const displayBalance = authoritativeBalance \?\? previewBalance/);
  assert.match(
    source,
    /const dependenciesReady\s*=\s*accountQuery\.isSuccess\s*&&\s*configQuery\.isSuccess\s*&&\s*accountsQuery\.isSuccess/,
  );
  assert.match(
    source,
    /const canSubmit\s*=\s*dependenciesReady\s*&&\s*Boolean\(selectedAccount\)/,
  );
  assert.match(source, /if \(!dependenciesReady \|\| !withdrawConfig\) return null/);
  assert.match(source, /editable=\{dependenciesReady\}/);
  assert.match(source, /disabled=\{!canSubmit\}/);
  assert.match(source, /accountQuery\.isError\s*\?\s*\(\s*<InlineError/);
  assert.match(source, /configQuery\.isError\s*\?\s*\(\s*<InlineError/);
  assert.match(source, /accountsQuery\.isError\s*\?\s*\(\s*<InlineError/);
  assert.doesNotMatch(source, /if \(accountQuery\.isPending \|\| configQuery\.isPending \|\| accountsQuery\.isPending\) return <LoadingState/);
  assert.doesNotMatch(source, /previewBalance.*min_amount|previewBalance.*fee_|previewBalance.*otp|required.*previewBalance/);
});
