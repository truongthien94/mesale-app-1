const assert = require("node:assert/strict");
const fs = require("node:fs");
const path = require("node:path");
const test = require("node:test");

function read(relativePath) {
  return fs.readFileSync(path.resolve(__dirname, relativePath), "utf8");
}

test("Round B exposes the website's four tabs and hides legacy tabs", () => {
  const layout = read("../app/(tabs)/_layout.tsx");
  for (const route of ["home", "wallet", "orders", "withdraw", "more"]) {
    assert.match(layout, new RegExp(`name=\"${route}\"`));
  }
  for (const route of ["earn", "inbox", "account"]) {
    assert.match(layout, new RegExp(`name=\"${route}\" options=\\{\\{ href: null \\}\\}`));
  }
  assert.match(layout, /useTheme\(\)/);
  assert.match(layout, /tabBarButton:/);
});

test("Round B keeps canonical wallet screens behind direct tab aliases", () => {
  assert.match(read("../app/(tabs)/orders.tsx"), /export \{ default \} from \"\.\/wallet\/orders\"/);
  assert.match(read("../app/(tabs)/withdraw.tsx"), /export \{ default \} from \"\.\/wallet\/withdrawals\"/);
});

test("More sheet uses the shared unread/tasks query keys and approved routes", () => {
  const sheet = read("../src/features/navigation/MoreSheet.tsx");
  assert.match(sheet, /queryKey: \[\"notifications\", \"unread-count\"\]/);
  assert.match(sheet, /queryKey: \[\"earn\", \"tasks\"\]/);
  for (const route of ["wallet/orders", "earn/checkin", "earn/referrals", "wallet/withdrawals", "earn/gifts", "earn/tasks", "account/profile", "inbox", "wallet/balance-logs"]) {
    assert.match(sheet, new RegExp(`\\/\\(tabs\\)\\/${route.replaceAll("/", "\\/")}`));
  }
  assert.match(sheet, /setPreference\(option\.value\)/);
  assert.match(sheet, /await logout\(\)/);
});
