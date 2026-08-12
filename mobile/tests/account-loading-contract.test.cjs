const assert = require("node:assert/strict");
const fs = require("node:fs");
const path = require("node:path");
const test = require("node:test");

function read(relativePath) {
  return fs.readFileSync(path.resolve(__dirname, relativePath), "utf8");
}

test("account main tab renders the current auth user while account detail refreshes", () => {
  const source = read("../app/(tabs)/account/index.tsx");

  assert.match(source, /const accountQuery = useAccount\(\)/);
  assert.match(source, /const accountPreview: AccountPreview \| null = user \? \{/);
  assert.match(source, /wallet: user\.financialSnapshot \? \{/);
  assert.match(source, /const serverAccount = user && accountQuery\.data\?\.id === user\.id \? accountQuery\.data : null/);
  assert.match(source, /const account = serverAccount \?\? accountPreview/);
  assert.match(source, /if \(accountQuery\.isPending && !account\)/);
  assert.match(source, /if \(accountQuery\.isError && !account\)/);
  assert.match(source, /accountQuery\.isError \? \([\s\S]*Dữ liệu tài khoản có thể chưa mới nhất[\s\S]*accountQuery\.refetch\(\)/);
  assert.doesNotMatch(source, /queryClient\.(?:setQueryData|setQueriesData)/);
});

test("account preview never invents unknown money or referral values", () => {
  const source = read("../app/(tabs)/account/index.tsx");

  assert.match(source, /typeof value === "number" \? formatAccountMoney\(value, "vi"\) : "—"/);
  assert.match(source, /referral_code_eligible: user\.referralCodeEligible/);
  assert.match(source, /referral_code_expires_at: user\.referralCodeExpiresAt/);
  assert.match(source, /account\.email\?\.trim\(\) \|\| "—"/);
  assert.doesNotMatch(source, /account\.wallet\?\.[a-z_]+ \?\? 0/);
  assert.doesNotMatch(source, /referralRate \?\? 0/);
});

test("More sheet fetches account detail only while open and rejects another user's cache", () => {
  const source = read("../src/features/navigation/MoreSheet.tsx");
  const api = read("../src/features/account/api.ts");

  assert.match(source, /useAccount\(\{ enabled: isOpen \}\)/);
  assert.match(source, /const account = user && accountQuery\.data\?\.id === user\.id \? accountQuery\.data : null/);
  assert.match(source, /account\?\.name \|\| user\?\.name/);
  assert.match(api, /export function useAccount\(options: \{ enabled\?: boolean \} = \{\}\)/);
  assert.match(api, /enabled: options\.enabled/);
});
