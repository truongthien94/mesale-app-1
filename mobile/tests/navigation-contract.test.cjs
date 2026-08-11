const assert = require("node:assert/strict");
const fs = require("node:fs");
const path = require("node:path");
const test = require("node:test");

function read(relativePath) {
  return fs.readFileSync(path.resolve(__dirname, relativePath), "utf8");
}

test("the primary tab bar exposes Account as a real screen and retires the More sheet", () => {
  const layout = read("../app/(tabs)/_layout.tsx");
  for (const route of ["home", "wallet", "orders", "withdraw", "account"]) {
    assert.match(layout, new RegExp(`name=\"${route}\"`));
  }
  for (const route of ["earn", "inbox", "more"]) {
    assert.match(layout, new RegExp(`name=\"${route}\" options=\\{\\{ href: null \\}\\}`));
  }
  assert.match(layout, /useTheme\(\)/);
  assert.match(layout, /title: isVietnamese \? "Tài khoản" : "Account"/);
  assert.match(layout, /<UserRound color=\{color\} size=\{size\}/);
  assert.match(layout, /tabBarActiveTintColor: "#2f9af5"/);
  assert.doesNotMatch(layout, /MoreSheet|useMoreSheetStore|tabBarButton:/);
});

test("Round B keeps canonical wallet screens behind direct tab aliases", () => {
  assert.match(read("../app/(tabs)/orders.tsx"), /export \{ default \} from "\.\/wallet\/orders"/);
  assert.match(read("../app/(tabs)/withdraw.tsx"), /export \{ default \} from "\.\/wallet\/withdrawals"/);
});

test("Account is a server-authoritative native hub with approved destinations", () => {
  const account = read("../app/(tabs)/account/index.tsx");
  for (const hook of ["useAccount", "usePaymentAccounts", "useWithdrawals", "fetchReferrals"]) {
    assert.match(account, new RegExp(`${hook}\\(`));
  }
  for (const route of ["account/profile", "account/security", "account/sessions", "account/preferences", "account/delete", "wallet/payment-accounts", "wallet/withdrawals", "earn/referrals", "earn/checkin", "earn/tasks", "earn/gifts", "inbox"]) {
    assert.match(account, new RegExp(`\\/\\(tabs\\)\\/${route.replaceAll("/", "\\/")}`));
  }
  for (const label of ["Số dư khả dụng", "Tổng đã nhận", "Từ giới thiệu", "Chưa liên kết ngân hàng", "Giới thiệu bạn bè", "TÀI KHOẢN", "KHÁM PHÁ", "HỖ TRỢ & PHÁP LÝ", "Đăng xuất", "Xóa tài khoản"]) {
    assert.match(account, new RegExp(label));
  }
  assert.match(account, /paymentAccountsQuery\.isSuccess && paymentAccountCount === 0/);
  assert.match(account, /actionLabel=\{paymentAccountsQuery\.isSuccess && paymentAccountCount === 0 \? "Thêm ngay" : undefined\}/);
  assert.match(account, /paymentAccountsQuery\.data\?\.total/);
  assert.match(account, /withdrawalsQuery\.data\?\.pages\[0\]\?\.pagination\.total/);
  assert.match(account, /withdrawalsQuery\.isRefetching/);
  assert.match(account, /referralsQuery\.data\?\.rates\.f1_rate/);
  assert.match(account, /queryKey: \["account", "referral-preview"\]/);
  assert.doesNotMatch(account, /queryKey: \["earn", "referrals"/);
  assert.match(account, /account\.preferences\?\.locale\?\.trim\(\) \|\| "vi"/);
  assert.match(account, /account\.preferences\?\.currency\?\.trim\(\) \|\| account\.wallet\?\.currency\?\.trim\(\) \|\| "VND"/);
  assert.doesNotMatch(account, /account\.preferences\.locale|account\.preferences\.currency/);
  assert.match(account, /Hoàn tiền Mê Sale/);
  assert.match(account, /logoutInFlight\.current/);
  assert.match(account, /Đã đăng xuất trên thiết bị/);
  assert.match(account, /showUnavailable\("Hướng dẫn sử dụng"\)/);
  assert.match(account, /menuRow: \{[^}]*minHeight: 74/);
  assert.match(account, /warningAction: \{[^}]*minHeight: 44/);
  assert.match(account, /https:\/\/mesale\.vn\/privacy/);
  assert.match(account, /https:\/\/mesale\.vn\/terms/);
  assert.match(account, /https:\/\/mesale\.vn\/support/);
  assert.doesNotMatch(account, /80%|WebView|sk_live_/);
});
