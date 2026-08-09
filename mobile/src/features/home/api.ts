import { request } from "@/api/client";
import type {
  AccountSummary,
  CashbackProduct,
  HomeBanner,
  HomeConfig,
  Marketplace,
  MarketplaceConfig,
  ProductUrlValidation
} from "@/features/home/types";

class HomeContractError extends Error {
  constructor(message: string) {
    super(message);
    this.name = "HomeContractError";
  }
}

function isRecord(value: unknown): value is Record<string, unknown> {
  return value !== null && typeof value === "object" && !Array.isArray(value);
}

function requireRecord(value: unknown, field: string): Record<string, unknown> {
  if (!isRecord(value)) throw new HomeContractError(`Invalid ${field} response.`);
  return value;
}

function requireString(value: unknown, field: string): string {
  if (typeof value !== "string" || value.trim().length === 0) {
    throw new HomeContractError(`Invalid ${field} response.`);
  }
  return value;
}

function optionalString(value: unknown): string | null {
  return typeof value === "string" && value.trim().length > 0 ? value : null;
}

function requireInteger(value: unknown, field: string): number {
  if (typeof value !== "number" || !Number.isInteger(value)) {
    throw new HomeContractError(`Invalid ${field} response.`);
  }
  return value;
}

function optionalNumber(value: unknown, fallback = 0): number {
  return typeof value === "number" && Number.isFinite(value) ? value : fallback;
}

function optionalBoolean(value: unknown, fallback = false): boolean {
  return typeof value === "boolean" ? value : fallback;
}

function parseMarketplace(value: unknown): Marketplace {
  if (value === "shopee" || value === "tiktok" || value === "lazada") return value;
  throw new HomeContractError("Invalid cashback platform response.");
}

function parseMarketplaceConfig(
  source: Record<string, unknown>,
  key: Marketplace
): MarketplaceConfig {
  return {
    enabled: optionalBoolean(source[`${key}_enabled`]),
    rate: optionalNumber(source[`${key}_rate`]),
    notice: optionalString(source[`${key}_notice`])
  };
}

function parseBanner(value: unknown): HomeBanner | null {
  if (!isRecord(value)) return null;
  const imageUrl = optionalString(value.image_url);
  if (!imageUrl) return null;
  return {
    imageUrl,
    link: optionalString(value.link),
    title: optionalString(value.title)
  };
}

export function normalizeProductUrl(value: string): ProductUrlValidation {
  const trimmed = value.trim();
  if (!trimmed) return { valid: false, reason: "required" };

  const candidate = /^https?:\/\//i.test(trimmed) ? trimmed : `https://${trimmed}`;
  try {
    const url = new URL(candidate);
    if (!url.hostname || (url.protocol !== "https:" && url.protocol !== "http:")) {
      return { valid: false, reason: "invalid" };
    }
    // Marketplace links should leave the app over TLS. Laravel remains the
    // authority for supported domains and all SSRF/business validation.
    if (url.protocol === "http:") url.protocol = "https:";
    return { valid: true, url: url.toString() };
  } catch {
    return { valid: false, reason: "invalid" };
  }
}

export function isSafeAffiliateUrl(value: string): boolean {
  try {
    const url = new URL(value);
    return url.protocol === "https:" && url.hostname.length > 0;
  } catch {
    return false;
  }
}

export function normalizeBannerLink(value: string | null): string | null {
  if (!value?.trim()) return null;

  try {
    const url = new URL(value.trim());
    if (url.username || url.password) return null;
    if (url.protocol === "http:") url.protocol = "https:";
    return ["https:", "mailto:", "tel:"].includes(url.protocol) ? url.toString() : null;
  } catch {
    return null;
  }
}

export async function fetchAccountSummary(signal?: AbortSignal): Promise<AccountSummary> {
  const value = requireRecord(await request<unknown>("account", { signal }), "account");
  const wallet = requireRecord(value.wallet, "account wallet");
  const stats = requireRecord(value.stats, "account stats");

  return {
    id: requireInteger(value.id, "account id"),
    name: optionalString(value.name),
    email: optionalString(value.email),
    avatar: optionalString(value.avatar),
    referralCode: optionalString(value.referral_code),
    wallet: {
      balance: requireInteger(wallet.balance, "wallet balance"),
      totalCashback: requireInteger(wallet.total_cashback, "total cashback"),
      totalReferralEarned: requireInteger(wallet.total_referral_earned, "referral earnings"),
      totalWithdrawn: requireInteger(wallet.total_withdrawn, "total withdrawn"),
      currency: requireString(wallet.currency, "wallet currency")
    },
    stats: {
      ordersTotal: requireInteger(stats.orders_total, "orders total"),
      ordersPending: requireInteger(stats.orders_pending, "orders pending"),
      ordersApproved: requireInteger(stats.orders_approved, "orders approved"),
      ordersRejected: requireInteger(stats.orders_rejected, "orders rejected"),
      referralsCount: requireInteger(stats.referrals_count, "referrals count"),
      withdrawalsPending: requireInteger(stats.withdrawals_pending, "pending withdrawals")
    }
  };
}

export async function fetchHomeConfig(signal?: AbortSignal): Promise<HomeConfig> {
  const value = requireRecord(await request<unknown>("config", { authenticated: false, signal }), "config");
  const site = requireRecord(value.site, "site config");
  const theme = requireRecord(value.theme, "theme config");
  const cashback = requireRecord(value.cashback, "cashback config");
  const features = requireRecord(value.features, "feature config");
  const banners = Array.isArray(value.banners)
    ? value.banners.map(parseBanner).filter((banner): banner is HomeBanner => banner !== null)
    : [];

  return {
    siteName: optionalString(site.name) ?? "Mesale",
    themeColor: optionalString(theme.color) ?? "#ee4d2d",
    cashbackLinkEnabled: optionalBoolean(features.api_cashback_link),
    marketplaces: {
      shopee: parseMarketplaceConfig(cashback, "shopee"),
      tiktok: parseMarketplaceConfig(cashback, "tiktok"),
      lazada: parseMarketplaceConfig(cashback, "lazada")
    },
    banners
  };
}

export async function createCashbackLink(productUrl: string): Promise<CashbackProduct> {
  const value = requireRecord(await request<unknown>("cashback/link", {
    method: "POST",
    body: { url: productUrl }
  }), "cashback product");

  return {
    transId: requireString(value.trans_id, "transaction id"),
    platform: parseMarketplace(value.platform),
    name: requireString(value.name, "product name"),
    image: optionalString(value.image),
    price: requireInteger(value.price, "product price"),
    commissionAmount: requireInteger(value.commission_amount, "commission amount"),
    cashbackAmount: requireInteger(value.cashback_amount, "cashback amount"),
    cashbackRate: optionalNumber(value.cashback_rate),
    isEstimated: optionalBoolean(value.is_estimated),
    affiliateUrl: requireString(value.affiliate_url, "affiliate URL")
  };
}
