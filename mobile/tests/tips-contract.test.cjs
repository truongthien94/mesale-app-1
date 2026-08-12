const assert = require("node:assert/strict");
const fs = require("node:fs");
const path = require("node:path");
const test = require("node:test");

function read(relativePath) {
  return fs.readFileSync(path.resolve(__dirname, relativePath), "utf8");
}

test("Home opens the native Tips route and the Home stack registers it", () => {
  const home = read("../src/features/home/HomeScreen.tsx");
  const layout = read("../app/(tabs)/home/_layout.tsx");
  const tabs = read("../app/(tabs)/_layout.tsx");

  assert.match(home, /router\.push\("\/\(tabs\)\/home\/tips"\)/);
  assert.match(layout, /<Stack\.Screen name="tips" \/>/);
  assert.match(tabs, /usePathname\(\)/);
  assert.match(tabs, /pathname === "\/home\/tips"/);
  assert.match(tabs, /display: hideTabBar \? "none" : "flex"/);
  assert.doesNotMatch(home, /onTips=\{\(\) => Alert\.alert/);
});

test("Tips renders the nine approved cashback cases as a multi-expand native accordion", () => {
  const source = read("../app/(tabs)/home/tips.tsx");
  const titles = [
    'Dính "dấu vết" từ Shopee Video/Live trong 7 ngày',
    "Thanh toán trước bằng thẻ tín dụng / thẻ ngân hàng",
    "Bấm link nơi khác SAU khi bấm link Mê Sale",
    "Đặt nhiều đơn nhưng chỉ bấm link 1 lần",
    "Bấm link dồn dập rồi mới đặt hàng một loạt",
    "Bấm link sàn này nhưng mua hàng sàn khác",
    "Bấm link trên máy này, đặt hàng trên máy khác",
    "Sản phẩm không được sàn tài trợ hoa hồng",
    "Lỗi ghi nhận khách quan từ phía sàn"
  ];

  for (const title of titles) assert.ok(source.includes(title), `${title} must be rendered`);
  assert.match(source, /const \[expandedIds, setExpandedIds\] = useState<Set<number>>\(\(\) => new Set\(\[1\]\)\)/);
  assert.match(source, /const next = new Set\(current\)/);
  assert.match(source, /accessibilityState=\{\{ expanded \}\}/);
  assert.match(source, /<FlatList/);
  assert.match(source, /extraData=\{expandedIds\}/);
  assert.match(source, /useTheme\(\)/);
  assert.match(source, /useSafeAreaInsets\(\)/);
  assert.match(source, /router\.canGoBack\(\)/);
  assert.match(source, /router\.replace\("\/\(tabs\)\/home"\)/);
});

test("Tips keeps Me Sale branding and contains no remote or WebView dependency", () => {
  const source = read("../app/(tabs)/home/tips.tsx");

  assert.match(source, /9 trường hợp đơn KHÔNG được ghi nhận hoàn tiền/);
  assert.match(source, /Áp dụng cho tất cả các sàn: Shopee · TikTok Shop/);
  assert.match(source, /Lưu ý từ Mê Sale/);
  assert.match(source, /Cách khắc phục:/);
  assert.doesNotMatch(source, /Matumi|WebView|fetch\(|request\(|useQuery|Linking\.openURL/);
});

test("Support uses the requested fixed HTTPS Zalo group without a blocking capability probe", () => {
  const source = read("../src/features/home/HomeScreen.tsx");

  assert.match(source, /const SUPPORT_URL = "https:\/\/zalo\.me\/g\/rb0b31ft7erer5slrcqb"/);
  assert.match(source, /Linking\.openURL\(SUPPORT_URL\)\.catch/);
  assert.doesNotMatch(source, /Linking\.canOpenURL\(SUPPORT_URL\)/);
});
