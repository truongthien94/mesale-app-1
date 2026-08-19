const assert = require("node:assert/strict");
const fs = require("node:fs");
const path = require("node:path");
const test = require("node:test");

function read(relativePath) {
  return fs.readFileSync(path.resolve(__dirname, relativePath), "utf8");
}

function assertInOrder(source, markers) {
  let cursor = -1;
  for (const marker of markers) {
    const next = source.indexOf(marker, cursor + 1);
    assert.ok(next > cursor, `Expected ${JSON.stringify(marker)} after the previous withdrawal field`);
    cursor = next;
  }
}

test("withdrawal form uses server financial terms and saved payment accounts in the native layout", () => {
  const source = read("../app/(tabs)/wallet/withdrawals/create.tsx");
  const layout = read("../app/(tabs)/wallet/_layout.tsx");

  assert.match(layout, /name="withdrawals\/create"/);
  assert.doesNotMatch(layout, /@react-navigation\//);
  assert.match(layout, /title: "Rút tiền"/);
  assert.match(layout, /accessibilityLabel="Quay lại"/);
  assert.match(layout, /navigation\.canGoBack\(\)/);
  assert.match(layout, /navigation\.goBack\(\)/);
  assert.match(
    layout,
    /route\.name === "withdrawals\/create"\s*\? "\/\(tabs\)\/wallet\/withdrawals"\s*: "\/\(tabs\)\/wallet"/,
  );
  assert.match(layout, /useTheme\(\)/);
  assert.match(layout, /const \{ colors \} = useTheme\(\)/);
  assert.match(layout, /header: \(props\) => <WalletStackHeader \{\.\.\.props\} \/>/);
  assert.match(layout, /useSafeAreaInsets\(\)/);
  assert.match(layout, /paddingTop: insets\.top/);
  assert.match(layout, /allowFontScaling=\{false\}/);
  assert.match(layout, /numberOfLines=\{1\}/);
  assert.match(layout, /headerTitle: \{[^}]*fontSize: 17/);
  assert.doesNotMatch(layout, /statusBarStyle/);
  assert.match(layout, /<ChevronLeft color=\{colors\.text\}/);
  assert.match(layout, /showBack = navigation\.canGoBack\(\) \|\| route\.name !== "index"/);
  assert.doesNotMatch(layout, /import \{ colors \} from "@\/theme\/tokens"/);
  assert.match(source, /accountQuery\.data\?\.wallet\.balance/);
  assert.match(source, /from "expo-router\/tabs"/);
  assert.doesNotMatch(source, /@react-navigation\//);
  assert.match(source, /withdrawConfig\.min_amount/);
  assert.match(source, /config\.fee_type === "percentage"/);
  assert.match(source, /withdrawConfig\.otp_required/);
  assert.match(source, /accountsQuery\.data\.items\.filter/);
  assert.match(source, /router\.push\("\/\(tabs\)\/wallet\/payment-accounts\/create"\)/);
  assert.match(source, /Cần liên kết ngân hàng trước/);
  assert.match(source, /Tiền chỉ chuyển về tài khoản đã lưu trong hồ sơ/);
  assert.match(source, /stableSubmission\.getVariables\(payload\)/);
  assert.doesNotMatch(source, /Matumi|30[.]000|24h|sk_live_/);
});

test("withdrawal form follows the compact native field order and server-enabled receiving methods", () => {
  const source = read("../app/(tabs)/wallet/withdrawals/create.tsx");

  assertInOrder(source, [
    "SỐ TIỀN CẦN RÚT (VND)",
    "HÌNH THỨC NHẬN TIỀN",
    "TÊN NGÂN HÀNG NHẬN",
    "SỐ TÀI KHOẢN NGÂN HÀNG",
    "HỌ TÊN CHỦ TÀI KHOẢN",
    "MÃ XÁC MINH OTP",
    "styles.submitButton"
  ]);
  assert.match(source, /styles\.balanceStrip/);
  assert.match(source, /styles\.formCard/);
  assert.match(source, /balanceStrip:\s*\{[\s\S]*?minHeight:\s*48/);
  assert.doesNotMatch(source, /styles\.amountCard|styles\.footer|fontSize: 47/);
  assert.match(source, /const \[selectedMethod, setSelectedMethod\]/);
  assert.match(source, /account\.payment_method === selectedMethod/);
  assert.match(
    source,
    /method === "bank"\s*\?\s*withdrawConfig\.bank_enabled\s*:\s*withdrawConfig\.wallet_enabled/,
  );
  assert.match(source, /if \(!enabled\) return/);
  assert.match(
    source,
    /accessibilityState=\{\{[\s\S]*?checked:\s*selected,[\s\S]*?disabled:\s*!enabled,[\s\S]*?\}\}/,
  );
  assert.match(source, /disabled=\{!enabled\}/);
  assert.match(source, /Chưa hỗ trợ/);
  assert.match(source, /styles\.addAccountButton/);
  assert.match(
    source,
    /Thêm\s*\{" "\}\s*\{selectedMethod === "bank"\s*\?\s*"tài khoản ngân hàng"\s*:\s*"ví điện tử"\}/,
  );
  assert.match(
    source,
    /Boolean\(selectedAccount\)\s*&&\s*amountIsValid\s*&&\s*otpIsValid/,
  );
  assert.match(source, /disabled=\{!canSubmit\}/);
});

test("withdrawal destination stays saved-account-derived and server authoritative", () => {
  const source = read("../app/(tabs)/wallet/withdrawals/create.tsx");

  assert.match(source, /selectedAccount\?\.account_number/);
  assert.match(source, /selectedAccount\?\.account_name\.trim\(\)\.toUpperCase\(\)/);
  assert.match(source, /payment_method: selectedAccount\.payment_method/);
  assert.match(source, /account_number: selectedAccount\.account_number/);
  assert.match(source, /account_name: selectedAccount\.account_name\.trim\(\)\.toUpperCase\(\)/);
  assert.match(source, /bank_name: selectedAccount\.bank_name/);
  assert.match(source, /wallet_name: selectedAccount\.bank_name/);
  assert.match(source, /styles\.readOnlyInput/);
  assert.doesNotMatch(source, /placeholder="Nhập số tài khoản|placeholder="Nhập họ tên chủ tài khoản/);
});

test("payment accounts keeps a compact blue hero above the white account cards", () => {
  const source = read("../app/(tabs)/wallet/payment-accounts/index.tsx");

  assert.match(source, /<CompactBlueHero[^>]*title="Tài khoản nhận tiền"/);
  assert.match(source, /backgroundColor: colors\.surface/);
  assert.match(source, /usePaymentAccounts\(\)/);
});

test("withdrawal creation keeps validation, OTP, retry, success and keyboard states", () => {
  const source = read("../app/(tabs)/wallet/withdrawals/create.tsx");

  assert.match(source, /KeyboardAvoidingView/);
  assert.match(source, /keyboardShouldPersistTaps="handled"/);
  assert.match(source, /useBottomTabBarHeight\(\)/);
  assert.match(source, /Math\.max\(tabBarHeight, insets\.bottom \+ 16\) \+ 24/);
  assert.match(source, /validateAmount\(amount\)/);
  assert.match(source, /\^\\d\{6\}\$/);
  assert.match(source, /useSendWithdrawalOtp\(\)/);
  assert.match(source, /styles\.otpRow/);
  assert.match(source, /styles\.submitButton/);
  assert.match(source, /InlineError error=\{mutation\.error\} onRetry=\{\(\) => void submit\(\)\}/);
  assert.match(source, /if \(created\)/);
  assert.match(source, /router\.replace\("\/\(tabs\)\/wallet\/withdrawals"\)/);
  assert.match(source, /accessibilityRole="radio"/);
});

test("keeps withdrawal history and create-request routes distinct", () => {
  const layout = read("../app/(tabs)/wallet/_layout.tsx");
  const tabAlias = read("../app/(tabs)/withdraw.tsx");
  const history = read("../app/(tabs)/wallet/withdrawals/index.tsx");
  const create = read("../app/(tabs)/wallet/withdrawals/create.tsx");

  assert.match(layout, /name="withdrawals\/index"/);
  assert.match(layout, /name="withdrawals\/create"/);
  assert.match(tabAlias, /import CreateWithdrawalScreen from "\.\/wallet\/withdrawals\/create"/);
  assert.match(tabAlias, /<CreateWithdrawalScreen \/>/);
  assert.match(tabAlias, /useSafeAreaInsets\(\)/);
  assert.match(tabAlias, /useTheme\(\)/);
  assert.match(tabAlias, /paddingTop: insets\.top/);
  assert.match(tabAlias, /accessibilityLabel="Mở lịch sử rút tiền"/);
  assert.match(tabAlias, /router\.replace\("\/\(tabs\)\/wallet\/withdrawals"\)/);
  assert.match(tabAlias, /allowFontScaling=\{false\}/);
  assert.doesNotMatch(tabAlias, /<Redirect/);
  assert.match(history, /router\.push\("\/\(tabs\)\/withdraw"\)/);
  assert.match(create, /router\.replace\("\/\(tabs\)\/wallet\/withdrawals"\)/);
});

test("withdrawal history keeps the native empty state and server-backed list behavior", () => {
  const history = read("../app/(tabs)/wallet/withdrawals/index.tsx");

  assert.match(history, /useWithdrawals\(\)/);
  assert.match(history, /useAppConfig\(\)/);
  assert.match(history, /useTheme\(\)/);
  assert.match(history, /Tạo yêu cầu rút tiền/);
  assert.match(history, /disabled=\{!config\.data\.withdraw\.enabled\}/);
  assert.match(history, /Chưa có lệnh rút tiền/);
  assert.match(history, /Yêu cầu mới và trạng thái xử lý sẽ xuất hiện tại đây\./);
  assert.match(history, /emptyState: \{[\s\S]*flex: 1[\s\S]*justifyContent: "center"/);
  assert.doesNotMatch(history, /<History\b|emptyIcon/);
  assert.match(history, /query\.hasNextPage && !query\.isFetchingNextPage/);
  assert.match(history, /query\.isFetchNextPageError/);
  assert.match(history, /onRefresh=\{\(\) => void query\.refetch\(\)\}/);
  assert.doesNotMatch(history, /sk_live_|fake withdrawal|mock withdrawal/i);
});
