const assert = require("node:assert/strict");
const fs = require("node:fs");
const path = require("node:path");
const test = require("node:test");

function read(relativePath) {
  return fs.readFileSync(path.resolve(__dirname, relativePath), "utf8");
}

test("authenticated tabs prefetch their server data without delaying navigation", () => {
  const layout = read("../app/(tabs)/_layout.tsx");
  const prefetch = read("../src/api/AuthenticatedPrefetch.tsx");

  assert.match(layout, /<AuthenticatedPrefetch \/>[\s\S]*<Tabs/);
  assert.match(prefetch, /if \(!session \|\| !user\) return/);
  assert.match(prefetch, /session\?\.accessToken, user\?\.id/);
  assert.match(prefetch, /prefetchQuery\(accountDetailQueryOptions\(\)\)/);
  assert.match(prefetch, /prefetchInfiniteQuery\(ordersQueryOptions\(\)\)/);
  assert.match(prefetch, /prefetchQuery\(appConfigRawQueryOptions\(\)\)/);
  assert.match(prefetch, /prefetchQuery\(paymentAccountsQueryOptions\(\)\)/);
  assert.match(prefetch, /InteractionManager\.runAfterInteractions\(\(\) => \{/);
  assert.match(prefetch, /router\.prefetch\("\/\(tabs\)\/referrals"\)/);
  assert.match(prefetch, /router\.prefetch\("\/\(tabs\)\/withdraw"\)/);
  assert.match(prefetch, /prefetchInfiniteQuery\(referralsQueryOptions\(\)\)/);
  assert.doesNotMatch(prefetch, /prefetchInfiniteQuery\(withdrawalsQueryOptions/);
  assert.doesNotMatch(prefetch, /await Promise|setQueryData|initialData|placeholderData/);
});

test("Home warms Quick Access routes and data after initial interactions", () => {
  const home = read("../src/features/home/HomeScreen.tsx");
  const prefetch = read("../src/features/home/QuickAccessPrefetch.tsx");

  assert.match(home, /<QuickAccessPrefetch \/>/);
  assert.match(prefetch, /router\.prefetch\("\/\(tabs\)\/home\/coupons"\)/);
  assert.match(prefetch, /router\.prefetch\("\/\(tabs\)\/earn\/checkin"\)/);
  assert.match(prefetch, /router\.prefetch\("\/\(tabs\)\/home\/tips"\)/);
  assert.match(prefetch, /InteractionManager\.runAfterInteractions\(\(\) => \{[\s\S]*router\.prefetch[\s\S]*prefetchInfiniteQuery/);
  assert.match(prefetch, /prefetchInfiniteQuery\(couponQueryOptions\(\)\)/);
  assert.match(prefetch, /prefetchInfiniteQuery\(checkinQueryOptions\(\)\)/);
  assert.match(prefetch, /Promise\.allSettled/);
  assert.doesNotMatch(prefetch, /await Promise|setQueryData|initialData|placeholderData/);
});

test("session restore refreshes account immediately in the background and Home/Withdraw reuse server responses", () => {
  const provider = read("../src/auth/AuthProvider.tsx");
  const homeHooks = read("../src/features/home/hooks.ts");
  const walletApi = read("../src/features/wallet/api.ts");
  const configQuery = read("../src/features/config/query.ts");

  assert.match(provider, /fetchQuery\(\{ \.\.\.accountDetailQueryOptions\(\), staleTime: 0 \}\)/);
  assert.match(provider, /setUser\(saved\.userPreview\);[\s\S]*setLoading\(false\);[\s\S]*fetchQuery/);
  assert.match(provider, /saveAuthState\(saved\.session, restoredUser\)/);
  assert.match(homeHooks, /appConfigRawQueryOptions\(\)[\s\S]*select: normalizeHomeConfig/);
  assert.match(walletApi, /\.\.\.appConfigRawQueryOptions\(\)[\s\S]*select:/);
  assert.match(configQuery, /appConfigKey = \["config"\]/);
  assert.doesNotMatch(provider, /getCurrentUser\(\)/);
});

test("Home, Wallet, Withdraw and Account share one canonical account query", () => {
  const homeHooks = read("../src/features/home/hooks.ts");
  const walletApi = read("../src/features/wallet/api.ts");
  const accountApi = read("../src/features/account/api.ts");
  const accountQuery = read("../src/features/account/query.ts");

  assert.match(homeHooks, /accountSummaryQueryOptions/);
  assert.match(homeHooks, /accountDetailQueryOptions/);
  assert.match(homeHooks, /select: normalizeAccountSummary/);
  assert.match(walletApi, /return useQuery\(accountSummaryQueryOptions\(\)\)/);
  assert.match(accountApi, /\.\.\.accountDetailQueryOptions\(\)/);
  assert.match(accountQuery, /accountDetailKey = \["account"\]/);
  assert.doesNotMatch(walletApi, /request<AccountSummary>\("account"/);
  assert.doesNotMatch(walletApi, /invalidateQueries\(\{ queryKey: walletKeys\.account \}\)/);
});
