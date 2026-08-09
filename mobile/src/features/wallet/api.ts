import { useInfiniteQuery, useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { idempotencyHeaders, type IdempotentVariables } from "@/api/idempotency";
import { getNextPageParam } from "@/api/pagination";
import { request } from "@/api/client";
import type {
  AccountSummary,
  AppConfig,
  BalanceLogsPage,
  OrderDetail,
  OrdersPage,
  PaymentAccount,
  PaymentAccountCollection,
  PaymentAccountPayload,
  WithdrawalCreated,
  WithdrawalPayload,
  WithdrawalsPage
} from "@/features/wallet/types";

const PAGE_SIZE = 20;

export const walletKeys = {
  account: ["wallet", "account"] as const,
  config: ["wallet", "config"] as const,
  orders: ["wallet", "orders"] as const,
  order: (id: number) => ["wallet", "orders", id] as const,
  balanceLogs: ["wallet", "balance-logs"] as const,
  withdrawals: ["wallet", "withdrawals"] as const,
  paymentAccounts: ["wallet", "payment-accounts"] as const
};

function pagePath(path: string, page: number): string {
  return `${path}?page=${page}&per_page=${PAGE_SIZE}`;
}

export function useAccountSummary() {
  return useQuery({
    queryKey: walletKeys.account,
    queryFn: ({ signal }) => request<AccountSummary>("account", { signal })
  });
}

export function useAppConfig() {
  return useQuery({
    queryKey: walletKeys.config,
    queryFn: ({ signal }) => request<AppConfig>("config", { signal })
  });
}

export function useOrders() {
  return useInfiniteQuery({
    queryKey: walletKeys.orders,
    initialPageParam: 1,
    queryFn: ({ pageParam, signal }) => request<OrdersPage>(pagePath("orders", pageParam), { signal }),
    getNextPageParam
  });
}

export function useOrder(id: number) {
  return useQuery({
    queryKey: walletKeys.order(id),
    enabled: Number.isInteger(id) && id > 0,
    queryFn: ({ signal }) => request<OrderDetail>(`orders/${id}`, { signal })
  });
}

export function useBalanceLogs() {
  return useInfiniteQuery({
    queryKey: walletKeys.balanceLogs,
    initialPageParam: 1,
    queryFn: ({ pageParam, signal }) => request<BalanceLogsPage>(pagePath("balance-logs", pageParam), { signal }),
    getNextPageParam
  });
}

export function useWithdrawals() {
  return useInfiniteQuery({
    queryKey: walletKeys.withdrawals,
    initialPageParam: 1,
    queryFn: ({ pageParam, signal }) => request<WithdrawalsPage>(pagePath("withdrawals", pageParam), { signal }),
    getNextPageParam
  });
}

export function usePaymentAccounts() {
  return useQuery({
    queryKey: walletKeys.paymentAccounts,
    queryFn: ({ signal }) => request<PaymentAccountCollection>("payment-accounts", { signal })
  });
}

export function useSendWithdrawalOtp() {
  return useMutation({
    mutationFn: () => request<null>("withdrawals/otp", { method: "POST" })
  });
}

export function useCreateWithdrawal() {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: ({ idempotencyKey, payload }: IdempotentVariables<WithdrawalPayload>) =>
      request<WithdrawalCreated>("withdrawals", {
        method: "POST",
        headers: idempotencyHeaders(idempotencyKey),
        body: payload
      }),
    onSuccess: async () => {
      await Promise.all([
        queryClient.invalidateQueries({ queryKey: walletKeys.account }),
        queryClient.invalidateQueries({ queryKey: walletKeys.withdrawals }),
        queryClient.invalidateQueries({ queryKey: walletKeys.balanceLogs })
      ]);
    }
  });
}

export function useCreatePaymentAccount() {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: ({ idempotencyKey, payload }: IdempotentVariables<PaymentAccountPayload>) =>
      request<PaymentAccount>("payment-accounts", {
        method: "POST",
        headers: idempotencyHeaders(idempotencyKey),
        body: payload
      }),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: walletKeys.paymentAccounts })
  });
}

export function useSetDefaultPaymentAccount() {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: (id: number) => request<null>(`payment-accounts/${id}/default`, { method: "POST" }),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: walletKeys.paymentAccounts })
  });
}

export function useDeletePaymentAccount() {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: (id: number) => request<null>(`payment-accounts/${id}`, { method: "DELETE" }),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: walletKeys.paymentAccounts })
  });
}
