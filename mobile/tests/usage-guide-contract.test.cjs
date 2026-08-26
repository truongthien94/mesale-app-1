const assert = require("node:assert/strict");
const fs = require("node:fs");
const path = require("node:path");
const test = require("node:test");

function read(relativePath) {
  return fs.readFileSync(path.resolve(__dirname, relativePath), "utf8");
}

test("Account opens the full native usage guide and hides the primary tab bar", () => {
  const account = read("../app/(tabs)/account/index.tsx");
  const layout = read("../app/(tabs)/account/_layout.tsx");
  const tabs = read("../app/(tabs)/_layout.tsx");

  assert.match(account, /navigateTo\("\/\(tabs\)\/account\/guide"\)/);
  assert.match(
    layout,
    /<Stack\.Screen\s+name="guide"\s+options=\{\{ headerShown: false, title: "Hướng dẫn sử dụng" \}\}\s+\/>/,
  );
  assert.match(tabs, /pathname === "\/account\/guide"/);
  assert.match(tabs, /display: hideTabBar \? "none" : "flex"/);
  assert.doesNotMatch(account, /showUnavailable\("Hướng dẫn sử dụng"\)/);
});

test("Usage guide mirrors the approved native card flow without stale product claims", () => {
  const source = read("../app/(tabs)/account/guide.tsx");
  const requiredCopy = [
    "Dán link → Mua ngay → nhận hàng → chờ xác nhận",
    "Bắt đầu",
    "Để đơn được ghi nhận hoàn tiền",
    "Quyền lợi hoàn tiền được tính thế nào?",
    "Theo dõi đơn hàng",
    "Săn mã giảm giá",
    "Điểm danh mỗi ngày",
    "Giới thiệu bạn bè",
    "Rút tiền",
    "Mẹo tối đa hoàn tiền",
    "Câu hỏi thường gặp",
    "Tài khoản trong 3 ngày đầu"
  ];

  for (const copy of requiredCopy) assert.ok(source.includes(copy), `${copy} must be rendered`);
  assert.match(source, /useAppConfig\(\)/);
  assert.match(source, /config\.min_amount/);
  assert.match(source, /config\.fee_value/);
  assert.match(source, /useIosPayoutFeaturesEnabled\(\)/);
  assert.match(source, /guideChapters = payoutFeaturesEnabled/);
  assert.match(source, /payoutOnly/);
  assert.match(source, /router\.push\("\/\(tabs\)\/home\/tips"\)/);
  assert.doesNotMatch(source, /Matumi|Bạn nhận 80%|Bạn nhận 85%|30\.000đ|7 ngày đầu|App Store|Google Play/);
});

test("Usage guide is theme-safe, safe-area aware, accessible, and fully native", () => {
  const source = read("../app/(tabs)/account/guide.tsx");

  assert.match(source, /useTheme\(\)/);
  assert.match(source, /useSafeAreaInsets\(\)/);
  assert.match(source, /<FlatList/);
  assert.match(source, /paddingBottom: insets\.bottom \+ 28/);
  assert.match(source, /router\.canGoBack\(\)/);
  assert.match(source, /router\.replace\("\/\(tabs\)\/account"\)/);
  assert.match(source, /accessibilityState=\{\{ expanded: open \}\}/);
  assert.match(source, /accessibilityRole="header"/);
  assert.doesNotMatch(source, /WebView|fetch\(|request\(|Linking\.openURL|sk_live_/);
});
