import { useMutation, useQuery } from "@tanstack/react-query";
import {
  createCashbackLink,
  fetchCoupons,
  fetchRanking,
  normalizeAccountSummary,
  normalizeHomeConfig
} from "@/features/home/api";
import { accountDetailQueryOptions } from "@/features/account/query";
import { appConfigKey, appConfigRawQueryOptions } from "@/features/config/query";

export const homeQueryKeys = {
  account: ["account"] as const,
  config: appConfigKey,
  coupons: ["home", "coupons"] as const,
  ranking: ["home", "ranking"] as const
};

export function accountSummaryQueryOptions() {
  return {
    ...accountDetailQueryOptions(),
    select: normalizeAccountSummary
  };
}

export function useAccountSummary(enabled = true) {
  return useQuery({ ...accountSummaryQueryOptions(), enabled });
}

export function useHomeConfig() {
  return useQuery({ ...appConfigRawQueryOptions(), select: normalizeHomeConfig });
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
