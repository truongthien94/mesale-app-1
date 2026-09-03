import { useEffect } from "react";
import { router } from "expo-router";
import { useQueryClient } from "@tanstack/react-query";
import { InteractionManager } from "react-native";
import { useAuth } from "@/auth/AuthProvider";
import { accountDetailQueryOptions } from "@/features/account/query";
import { appConfigRawQueryOptions } from "@/features/config/query";
import { fetchTasks, referralsQueryOptions } from "@/features/earn/api";
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

    const task = InteractionManager.runAfterInteractions(() => {
      router.prefetch("/(tabs)/referrals");
      router.prefetch("/(tabs)/tasks");
      void queryClient.prefetchInfiniteQuery(referralsQueryOptions());
      void queryClient.prefetchQuery({
        queryKey: ["earn", "tasks"],
        queryFn: ({ signal }) => fetchTasks(signal)
      });
    });

    return () => task.cancel();
  }, [queryClient, session?.accessToken, user?.id]);

  return null;
}
