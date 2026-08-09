import { useMutation, useQuery } from "@tanstack/react-query";
import {
  createCashbackLink,
  fetchAccountSummary,
  fetchHomeConfig
} from "@/features/home/api";

export const homeQueryKeys = {
  account: ["account"] as const,
  config: ["config"] as const
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
