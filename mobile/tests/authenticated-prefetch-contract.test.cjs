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
  assert.doesNotMatch(prefetch, /await Promise|setQueryData|initialData|placeholderData/);
});

test("session restore and Home/Withdraw reuse complete cached server responses", () => {
  const provider = read("../src/auth/AuthProvider.tsx");
  const homeHooks = read("../src/features/home/hooks.ts");
  const walletApi = read("../src/features/wallet/api.ts");
  const configQuery = read("../src/features/config/query.ts");

  assert.match(provider, /queryClient\.fetchQuery\(accountDetailQueryOptions\(\)\)/);
  assert.match(provider, /fetchQuery\(\{ \.\.\.accountDetailQueryOptions\(\), staleTime: 0 \}\)/);
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
