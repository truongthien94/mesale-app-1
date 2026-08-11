export type Marketplace = "shopee" | "tiktok" | "lazada";

export type AccountWallet = {
  balance: number;
  pendingCashback: number | null;
  totalCashback: number;
  totalReferralEarned: number;
  totalWithdrawn: number;
  currency: "VND" | string;
};

export type AccountStats = {
  ordersTotal: number;
  ordersPending: number;
  ordersApproved: number;
  ordersRejected: number;
  referralsCount: number;
  withdrawalsPending: number;
};

export type AccountSummary = {
  id: number;
  name: string | null;
  email: string | null;
  avatar: string | null;
  referralCode: string | null;
  wallet: AccountWallet;
  stats: AccountStats;
};

export type MarketplaceConfig = {
  enabled: boolean;
  rate: number;
  notice: string | null;
};

export type HomeBanner = {
  imageUrl: string;
  link: string | null;
  title: string | null;
};

export type HomeConfig = {
  siteName: string;
  themeColor: string;
  cashbackLinkEnabled: boolean;
  marketplaces: Record<Marketplace, MarketplaceConfig>;
  banners: HomeBanner[];
};

export type CashbackProduct = {
  transId: string;
  platform: Marketplace;
  name: string;
  image: string | null;
  price: number;
  commissionAmount: number;
  cashbackAmount: number;
  cashbackRate: number;
  isEstimated: boolean;
  affiliateUrl: string;
};

export type ProductUrlValidation =
  | { valid: true; url: string }
  | { valid: false; reason: "required" | "invalid" };

export type Coupon = {
  id: number;
  platform: Marketplace;
  code: string;
  title: string;
  description: string | null;
  category: string | null;
  minSpend: number;
  discountAmount: number;
  discountPercentage: number;
  imageUrl: string | null;
  redirectLink: string | null;
  expiredAt: string | null;
};

export type RankingEntry = {
  name: string;
  avatar: string | null;
  value: number;
};

export type RankingBoard = {
  topOrders: RankingEntry[];
  topCashback: RankingEntry[];
  topCheckin: RankingEntry[];
  topReferral: RankingEntry[];
  topBalance: RankingEntry[];
};
