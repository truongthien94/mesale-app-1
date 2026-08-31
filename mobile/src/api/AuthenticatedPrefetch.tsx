import { useEffect } from "react";
import { router } from "expo-router";
import { useQueryClient } from "@tanstack/react-query";
import { InteractionManager } from "react-native";
import { useAuth } from "@/auth/AuthProvider";
import { useIosPayoutFeaturesEnabled } from "@/config/features";
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
  const payoutFeaturesEnabled = useIosPayoutFeaturesEnabled();

  useEffect(() => {
    if (!session || !user) return;

    const prefetches = [
      queryClient.prefetchQuery(accountDetailQueryOptions()),
      queryClient.prefetchQuery(appConfigRawQueryOptions())
    ];
    if (payoutFeaturesEnabled) {
      prefetches.push(queryClient.prefetchInfiniteQuery(ordersQueryOptions()));
      prefetches.push(queryClient.prefetchQuery(paymentAccountsQueryOptions()));
    }
    void Promise.allSettled(prefetches);

    const task = InteractionManager.runAfterInteractions(() => {
      if (payoutFeaturesEnabled) {
        router.prefetch("/(tabs)/referrals");
        router.prefetch("/(tabs)/tasks");
        void queryClient.prefetchInfiniteQuery(referralsQueryOptions());
        void queryClient.prefetchQuery({
          queryKey: ["earn", "tasks"],
          queryFn: ({ signal }) => fetchTasks(signal)
        });
      }
    });

    return () => task.cancel();
  }, [payoutFeaturesEnabled, queryClient, session?.accessToken, user?.id]);

  return null;
}
