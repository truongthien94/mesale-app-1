const STANDARD_LEGAL_URLS = {
  privacy: "https://mesale.vn/privacy",
  terms: "https://mesale.vn/terms",
  support: "https://mesale.vn/support",
  deletion: "https://mesale.vn/account-deletion"
} as const;

const SHOPPING_LEGAL_URLS = {
  privacy: "https://mesale.vn/ios/privacy",
  terms: "https://mesale.vn/ios/terms",
  support: "https://mesale.vn/ios/support",
  deletion: "https://mesale.vn/ios/account-deletion"
} as const;

export function legalUrlsForPayoutFeatures(payoutFeaturesEnabled: boolean) {
  return payoutFeaturesEnabled ? STANDARD_LEGAL_URLS : SHOPPING_LEGAL_URLS;
}
