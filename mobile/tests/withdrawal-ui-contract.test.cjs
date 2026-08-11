const assert = require("node:assert/strict");
const fs = require("node:fs");
const path = require("node:path");
const test = require("node:test");

function read(relativePath) {
  return fs.readFileSync(path.resolve(__dirname, relativePath), "utf8");
}

test("withdrawal form uses server financial terms and saved payment accounts in the native layout", () => {
  const source = read("../app/(tabs)/wallet/withdrawals/create.tsx");
  const layout = read("../app/(tabs)/wallet/_layout.tsx");

  assert.match(layout, /name="withdrawals\/create"/);
  assert.match(layout, /title: "Rút tiền"/);
  assert.match(layout, /accessibilityLabel="Quay lại"/);
  assert.match(layout, /navigation\.canGoBack\(\)/);
  assert.match(layout, /navigation\.goBack\(\)/);
  assert.match(layout, /route\.name === "withdrawals\/create" \? "\/\(tabs\)\/wallet\/withdrawals" : "\/\(tabs\)\/wallet"/);
  assert.match(layout, /useTheme\(\)/);
  assert.match(layout, /const \{ colors, scheme \} = useTheme\(\)/);
  assert.match(layout, /header: \(props\) => <WalletStackHeader \{\.\.\.props\} \/>/);
  assert.match(layout, /useSafeAreaInsets\(\)/);
  assert.match(layout, /paddingTop: insets\.top/);
  assert.match(layout, /allowFontScaling=\{false\}/);
  assert.match(layout, /numberOfLines=\{1\}/);
  assert.match(layout, /headerTitle: \{[^}]*fontSize: 17/);
  assert.match(layout, /statusBarStyle: scheme === "dark" \? "light" : "dark"/);
  assert.match(layout, /<ChevronLeft color=\{colors\.text\}/);
  assert.match(layout, /showBack = navigation\.canGoBack\(\) \|\| route\.name !== "index"/);
  assert.doesNotMatch(layout, /import \{ colors \} from "@\/theme\/tokens"/);
  assert.match(source, /accountQuery\.data\?\.wallet\.balance/);
  assert.match(source, /withdrawConfig\.min_amount/);
  assert.match(source, /config\.fee_type === "percentage"/);
  assert.match(source, /withdrawConfig\.otp_required/);
  assert.match(source, /accountsQuery\.data\.items\.filter/);
  assert.match(source, /router\.push\("\/\(tabs\)\/wallet\/payment-accounts\/create"\)/);
  assert.match(source, /Cần liên kết ngân hàng trước/);
  assert.match(source, /Tiền chỉ về đúng tài khoản đã lưu trong hồ sơ của bạn/);
  assert.match(source, /Lịch sử rút tiền/);
  assert.match(source, /styles\.footer/);
  assert.match(source, /stableSubmission\.getVariables\(payload\)/);
  assert.doesNotMatch(source, /Matumi|30[.]000|24h/);
});

test("withdrawal creation keeps validation, OTP, retry, success and keyboard states", () => {
  const source = read("../app/(tabs)/wallet/withdrawals/create.tsx");

  assert.match(source, /KeyboardAvoidingView/);
  assert.match(source, /validateAmount\(amount\)/);
  assert.match(source, /\^\\d\{6\}\$/);
  assert.match(source, /useSendWithdrawalOtp\(\)/);
  assert.match(source, /InlineError error=\{mutation\.error\} onRetry=\{\(\) => void submit\(\)\}/);
  assert.match(source, /if \(created\)/);
  assert.match(source, /router\.replace\("\/\(tabs\)\/wallet\/withdrawals"\)/);
  assert.match(source, /accessibilityRole="radio"/);
});
