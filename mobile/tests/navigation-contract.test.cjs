const assert = require("node:assert/strict");
const fs = require("node:fs");
const path = require("node:path");
const test = require("node:test");

function read(relativePath) {
  return fs.readFileSync(path.resolve(__dirname, relativePath), "utf8");
}

test("the primary tab bar exposes Account as a real screen and retires the More sheet", () => {
  const layout = read("../app/(tabs)/_layout.tsx");
  const primaryRoutes = ["home", "referrals", "orders", "withdraw", "account"];
  for (const route of primaryRoutes) {
    assert.match(layout, new RegExp(`name=\"${route}\"`));
  }
  const primaryPositions = primaryRoutes.map((route) => layout.indexOf(`name="${route}"`));
  assert.deepEqual(primaryPositions, [...primaryPositions].sort((left, right) => left - right));
  for (const route of ["wallet", "earn", "inbox", "more"]) {
    assert.match(layout, new RegExp(`name=\"${route}\" options=\\{\\{ href: null \\}\\}`));
  }
  assert.match(layout, /useTheme\(\)/);
  assert.match(layout, /title: isVietnamese \? "Tài khoản" : "Account"/);
  assert.match(layout, /<UserRound color=\{color\} size=\{size\}/);
  assert.match(layout, /title: isVietnamese \? "Giới thiệu" : "Referral"/);
  assert.match(layout, /<UsersRound color=\{color\} size=\{size\}/);
  assert.match(layout, /tabBarActiveTintColor: "#2f9af5"/);
  assert.match(layout, /useRouter\(\)/);
  assert.match(layout, /tabPress: \(event\) => \{[\s\S]*event\.preventDefault\(\);[\s\S]*router\.replace\("\/\(tabs\)\/account"\);/);
  assert.match(layout, /popToTopOnBlur: true/);
  assert.doesNotMatch(layout, /MoreSheet|useMoreSheetStore|tabBarButton:/);
});

test("the Referral tab reuses the canonical referral screen", () => {
  assert.match(read("../app/(tabs)/referrals.tsx"), /export \{ default \} from "\.\/earn\/referrals"/);
});

test("Round B keeps canonical wallet screens behind direct tab aliases", () => {
  assert.match(read("../app/(tabs)/orders.tsx"), /export \{ default \} from "\.\/wallet\/orders"/);
  const withdrawTab = read("../app/(tabs)/withdraw.tsx");
  assert.match(withdrawTab, /import CreateWithdrawalScreen from "\.\/wallet\/withdrawals\/create"/);
  assert.match(withdrawTab, /<CreateWithdrawalScreen \/>/);
  assert.match(withdrawTab, /router\.replace\("\/\(tabs\)\/wallet\/withdrawals"\)/);
  assert.doesNotMatch(withdrawTab, /<Redirect/);
});

test("Account is a server-authoritative native hub with approved destinations", () => {
  const account = read("../app/(tabs)/account/index.tsx");
  for (const hook of ["useAccount", "usePaymentAccounts", "useInfiniteQuery", "referralsQueryOptions"]) {
    assert.match(account, new RegExp(`${hook}\\(`));
  }
  for (const route of ["account/information", "account/finance", "account/settings", "account/delete", "wallet/payment-accounts/create", "withdraw", "earn/referrals", "earn/checkin", "earn/tasks", "inbox"]) {
    assert.match(account, new RegExp(`\\/\\(tabs\\)\\/${route.replaceAll("/", "\\/")}`));
  }
  for (const label of ["Số dư khả dụng", "Tổng đã nhận", "Từ giới thiệu", "Chưa liên kết ngân hàng", "Giới thiệu bạn bè", "Thông tin tài khoản", "Tài chính", "Thông báo", "Cài đặt", "KHÁM PHÁ", "HỖ TRỢ & PHÁP LÝ", "Đăng xuất", "Xóa tài khoản"]) {
    assert.match(account, new RegExp(label));
  }
  assert.match(account, /paymentAccountsQuery\.isSuccess && paymentAccountCount === 0/);
  assert.match(account, /paymentAccountsQuery\.data\?\.total/);
  assert.doesNotMatch(account, /useWithdrawals\(|withdrawalsQuery/);
  assert.match(account, /referralsQuery\.data\?\.pages\[0\]\?\.rates\.f1_rate/);
  assert.match(account, /useInfiniteQuery\(referralsQueryOptions\(\)\)/);
  assert.doesNotMatch(account, /\["account", "referral-preview"\]/);
  assert.doesNotMatch(account, /Hoàn tiền Mê Sale|cashbackBadge|<Sparkles/);
  assert.match(account, /primaryMenuStack[\s\S]*title="Thông tin tài khoản"[\s\S]*title="Tài chính"[\s\S]*title="Thông báo"[\s\S]*title="Cài đặt"[\s\S]*KHÁM PHÁ/);
  for (const removedLabel of ["TÀI KHOẢN", "TÀI CHÍNH", "THÔNG BÁO", "CÀI ĐẶT"]) {
    assert.doesNotMatch(account, new RegExp(`>${removedLabel}<`));
  }
  for (const childLabel of ["Thông tin cá nhân", "Bảo mật tài khoản", "Phiên đăng nhập", "Tài khoản ngân hàng", "Lịch sử rút tiền", "Ngôn ngữ & tiền tệ", "Giao diện"]) {
    assert.doesNotMatch(account, new RegExp(`title="${childLabel}"`), `${childLabel} must live on a nested screen, not the hub`);
  }
  assert.match(account, /logoutInFlight\.current/);
  assert.match(account, /Đã đăng xuất trên thiết bị/);
  assert.match(account, /navigateTo\("\/\(tabs\)\/account\/guide"\)/);
  assert.match(account, /navigateTo\("\/\(tabs\)\/home\/tips"\)/);
  assert.doesNotMatch(account, /showUnavailable\("Hướng dẫn sử dụng"\)/);
  assert.match(account, /typeof account\.referral_code_eligible === "boolean"/);
  assert.match(account, /user\?\.referralCodeEligible === true/);
  assert.match(account, /account\.referral_code_expires_at !== undefined/);
  assert.match(account, /user\?\.referralCodeExpiresAt/);
  assert.match(account, /Hạn nhập mã do máy chủ Mê Sale xác nhận/);
  assert.match(account, /Chỉ áp dụng trong 3 ngày đầu sau khi đăng ký/);
  assert.match(account, /referralEntryExpanded \? "Thu gọn" : "Nhập ngay"/);
  assert.doesNotMatch(account, /Date\.now\(\)/);
  assert.match(account, /applyReferralCode\(normalizedReferralEntryCode\)/);
  assert.match(account, /Promise\.allSettled\(\[accountQuery\.refetch\(\), refreshUser\(\)\]\)/);
  for (const code of ["REFERRAL_WINDOW_EXPIRED", "REFERRAL_NOT_ELIGIBLE", "REFERRAL_ALREADY_LINKED", "REFERRAL_DISABLED"]) {
    assert.match(account, new RegExp(code));
  }
  assert.doesNotMatch(account, /85%|24h/i);
  assert.match(account, /menuRow: \{[^}]*minHeight: 74/);
  assert.match(account, /primaryMenuStack: \{ gap: 10 \}/);
  assert.match(account, /referralEntryPanel: \{[^}]*borderBottomWidth: StyleSheet\.hairlineWidth/);
  assert.match(account, /warningAction: \{[^}]*minHeight: 44/);
  assert.match(account, /https:\/\/mesale\.vn\/privacy/);
  assert.match(account, /https:\/\/mesale\.vn\/terms/);
  assert.match(account, /https:\/\/mesale\.vn\/support/);
  assert.doesNotMatch(account, /title="Đổi quà tặng"|\/\(tabs\)\/earn\/gifts/);
  assert.doesNotMatch(account, /80%|WebView|sk_live_/);
});

test("the retired rewards hub redirects back to Account without duplicate navigation", () => {
  const earnIndex = read("../app/(tabs)/earn/index.tsx");
  const moreSheet = read("../src/features/navigation/MoreSheet.tsx");

  assert.match(earnIndex, /import \{ Redirect \} from "expo-router"/);
  assert.match(earnIndex, /<Redirect href="\/\(tabs\)\/account" \/>/);
  assert.doesNotMatch(earnIndex, /Nhận thưởng|Mesale Rewards|FlatList|router\.push|\/earn\/gifts/);
  assert.doesNotMatch(moreSheet, /Đổi quà tặng|Redeem gifts|\/\(tabs\)\/earn\/gifts/);
});

test("Account summary rows open focused nested menus", () => {
  const layout = read("../app/(tabs)/account/_layout.tsx");
  const information = read("../app/(tabs)/account/information.tsx");
  const finance = read("../app/(tabs)/account/finance.tsx");
  const settings = read("../app/(tabs)/account/settings.tsx");
  const stackHeader = read("../src/features/account/AccountStackHeader.tsx");
  const sharedMenu = read("../src/features/account/AccountSectionMenu.tsx");

  for (const route of ["information", "finance", "settings", "guide"]) {
    assert.match(layout, new RegExp(`name="${route}"`));
  }
  assert.match(layout, /useTheme\(\)/);
  assert.match(layout, /header: \(props\) => <AccountStackHeader \{\.\.\.props\} \/>/);
  assert.match(layout, /statusBarStyle: scheme === "dark" \? "light" : "dark"/);
  assert.doesNotMatch(layout, /statusBarTranslucent|unstable_headerInsets/);
  assert.match(stackHeader, /useSafeAreaInsets\(\)/);
  assert.match(stackHeader, /paddingTop: insets\.top/);
  assert.match(stackHeader, /navigation\.canGoBack\(\)/);
  assert.match(stackHeader, /router\.replace\("\/\(tabs\)\/account"\)/);
  assert.match(stackHeader, /accessibilityLabel="Quay lại Tài khoản"/);
  assert.match(stackHeader, /minHeight: 56/);
  for (const route of ["account/profile", "account/security", "account/sessions"]) {
    assert.match(information, new RegExp(`\\/\\(tabs\\)\\/${route.replaceAll("/", "\\/")}`));
  }
  for (const route of ["wallet/payment-accounts", "wallet/withdrawals"]) {
    assert.match(finance, new RegExp(`\\/\\(tabs\\)\\/${route.replaceAll("/", "\\/")}`));
  }
  assert.match(settings, /account\/preferences/);
  assert.match(settings, /setPreference\(option\.value\)/);
  assert.match(settings, /accountQuery\.data\?\.preferences\?\.locale/);
  assert.match(sharedMenu, /minHeight: 74/);
  assert.match(sharedMenu, /useTheme\(\)/);
});
