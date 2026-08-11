import { request } from "@/api/client";
import type { Coupon, Marketplace } from "@/features/home/types";

export type CouponFilters = {
  category?: string;
  page?: number;
  perPage?: number;
  platform?: "all" | Marketplace;
};

export type CouponPage = {
  categories: string[];
  items: Coupon[];
  pagination: {
    currentPage: number;
    lastPage: number;
    perPage: number;
    total: number;
  };
};

class CouponContractError extends Error {
  constructor(message: string) {
    super(message);
    this.name = "CouponContractError";
  }
}
function isRecord(value: unknown): value is Record<string, unknown> {
  return value !== null && typeof value === "object" && !Array.isArray(value);
}

function requireRecord(value: unknown, field: string): Record<string, unknown> {
  if (!isRecord(value)) throw new CouponContractError(`Invalid ${field} response.`);
  return value;
}

function optionalString(value: unknown): string | null {
  return typeof value === "string" && value.trim().length > 0 ? value : null;
}

function requireInteger(value: unknown, field: string): number {
  if (typeof value !== "number" || !Number.isInteger(value)) {
    throw new CouponContractError(`Invalid ${field} response.`);
  }
  return value;
}

function optionalNumber(value: unknown): number {
  return typeof value === "number" && Number.isFinite(value) ? value : 0;
}

function parseCoupon(value: unknown): Coupon | null {
  if (!isRecord(value)) return null;
  const platform = value.platform;
  if (platform !== "shopee" && platform !== "tiktok" && platform !== "lazada") return null;
  const code = optionalString(value.code);
  const title = optionalString(value.title);
  if (!code || !title) return null;

  return {
    id: requireInteger(value.id, "coupon id"),
    platform,
    code,
    title,
    description: optionalString(value.description),
    category: optionalString(value.category),
    minSpend: optionalNumber(value.min_spend),
    discountAmount: optionalNumber(value.discount_amount),
    discountPercentage: optionalNumber(value.discount_percentage),
    imageUrl: optionalString(value.image_url),
    redirectLink: optionalString(value.redirect_link),
    expiredAt: optionalString(value.expired_at)
  };
}

export async function fetchCouponPage(filters: CouponFilters = {}, signal?: AbortSignal): Promise<CouponPage> {
  const params = new URLSearchParams();
  params.set("platform", filters.platform ?? "all");
  params.set("page", String(Math.max(1, filters.page ?? 1)));
  params.set("per_page", String(Math.min(50, Math.max(1, filters.perPage ?? 12))));
  if (filters.category && filters.category !== "all") params.set("category", filters.category);

  const value = requireRecord(await request<unknown>(`coupons?${params.toString()}`, { signal }), "coupons");
  const rawPagination = requireRecord(value.pagination, "coupon pagination");
  const rawCategories = Array.isArray(value.categories) ? value.categories : [];

  return {
    items: (Array.isArray(value.items) ? value.items : [])
      .map(parseCoupon)
      .filter((coupon): coupon is Coupon => coupon !== null),
    categories: rawCategories.filter((category): category is string => typeof category === "string" && category.trim().length > 0),
    pagination: {
      currentPage: requireInteger(rawPagination.current_page, "coupon current page"),
      lastPage: requireInteger(rawPagination.last_page, "coupon last page"),
      perPage: requireInteger(rawPagination.per_page, "coupon per page"),
      total: requireInteger(rawPagination.total, "coupon total")
    }
  };
}
