const assert = require("node:assert/strict");
const fs = require("node:fs");
const path = require("node:path");
const test = require("node:test");

function read(relativePath) {
  return fs.readFileSync(path.resolve(__dirname, relativePath), "utf8");
}

test("coupon screen uses the existing Laravel contract and native route", () => {
  const source = read("../app/(tabs)/home/coupons.tsx");
  const api = read("../src/features/coupons/api.ts");
  const home = read("../src/features/home/HomeScreen.tsx");

  assert.match(api, /request<unknown>\(`coupons\?\$\{params\.toString\(\)\}`/);
  assert.match(api, /current_page/);
  assert.match(api, /categories/);
  assert.match(source, /useInfiniteQuery/);
  assert.match(source, /fetchCouponPage/);
  assert.match(source, /LoadingState/);
  assert.match(source, /OfflineState/);
  assert.match(source, /ErrorState/);
  assert.match(source, /EmptyState/);
  assert.match(source, /Clipboard\.setStringAsync/);
  assert.match(source, /Linking\.openURL/);
  assert.match(source, /<StatusBar style=\{scheme === "dark" \? "light" : "dark"\}/);
  assert.match(source, /https:/);
  assert.match(home, /router\.push\("\/\(tabs\)\/home\/coupons"\)/);
  assert.doesNotMatch(source, /\b200\b|\b194\b|62%|Sắp hết/);
  assert.doesNotMatch(source, /Matumi|sk_live_|apps\.apple\.com|play\.google\.com/);
});

test("coupon screen keeps pagination and category selection server-backed", () => {
  const source = read("../app/(tabs)/home/coupons.tsx");

  assert.match(source, /queryKey: \["coupons", category \?\? "all"\]/);
  assert.match(source, /getNextPageParam/);
  assert.match(source, /query\.fetchNextPage\(\)/);
  assert.match(source, /data=\{\[null, \.\.\.categories\]\}/);
  assert.match(source, /setCategory\(item\)/);
  assert.match(source, /firstPage\?\.pagination\.total/);
});
