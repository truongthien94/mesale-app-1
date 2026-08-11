import { useMutation, useQuery } from "@tanstack/react-query";
import {
  createCashbackLink,
  fetchAccountSummary,
  fetchCoupons,
  fetchHomeConfig,
  fetchRanking
} from "@/features/home/api";

export const homeQueryKeys = {
  account: ["account"] as const,
  config: ["config"] as const,
  coupons: ["home", "coupons"] as const,
  ranking: ["home", "ranking"] as const
};

export function useAccountSummary() {
  return useQuery({
    queryKey: homeQueryKeys.account,
    queryFn: ({ signal }) => fetchAccountSummary(signal)
  });
}

export function useHomeConfig() {
  return useQuery({
    queryKey: homeQueryKeys.config,
    queryFn: ({ signal }) => fetchHomeConfig(signal)
  });
}

export function useCreateCashbackLink() {
  return useMutation({
    mutationFn: createCashbackLink
  });
}

export function useCoupons() {
  return useQuery({
    queryKey: homeQueryKeys.coupons,
    queryFn: ({ signal }) => fetchCoupons(signal)
  });
}

export function useRanking() {
  return useQuery({
    queryKey: homeQueryKeys.ranking,
    queryFn: ({ signal }) => fetchRanking(signal)
  });
}
