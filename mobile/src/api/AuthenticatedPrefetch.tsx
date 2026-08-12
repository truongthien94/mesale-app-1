import { useEffect } from "react";
import { useQueryClient } from "@tanstack/react-query";
import { useAuth } from "@/auth/AuthProvider";
import { accountDetailQueryOptions } from "@/features/account/query";
import { appConfigRawQueryOptions } from "@/features/config/query";
import {
  ordersQueryOptions,
  paymentAccountsQueryOptions
} from "@/features/wallet/api";

export function AuthenticatedPrefetch() {
  const queryClient = useQueryClient();
  const { session, user } = useAuth();

  useEffect(() => {
    if (!session || !user) return;

    void Promise.allSettled([
      queryClient.prefetchQuery(accountDetailQueryOptions()),
      queryClient.prefetchInfiniteQuery(ordersQueryOptions()),
      queryClient.prefetchQuery(appConfigRawQueryOptions()),
      queryClient.prefetchQuery(paymentAccountsQueryOptions())
    ]);
  }, [queryClient, session?.accessToken, user?.id]);

  return null;
}
