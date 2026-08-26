import { useEffect } from "react";
import { InteractionManager } from "react-native";
import { router } from "expo-router";
import { useQueryClient } from "@tanstack/react-query";
import { couponQueryOptions } from "@/features/coupons/api";
import { useIosPayoutFeaturesEnabled } from "@/config/features";
import { checkinQueryOptions } from "@/features/earn/api";

export function QuickAccessPrefetch() {
  const queryClient = useQueryClient();
  const payoutFeaturesEnabled = useIosPayoutFeaturesEnabled();

  useEffect(() => {
    const task = InteractionManager.runAfterInteractions(() => {
      router.prefetch("/(tabs)/home/coupons");
      router.prefetch("/(tabs)/home/tips");

      const prefetches = [queryClient.prefetchInfiniteQuery(couponQueryOptions())];
      if (payoutFeaturesEnabled) {
        router.prefetch("/(tabs)/earn/checkin");
        prefetches.push(queryClient.prefetchInfiniteQuery(checkinQueryOptions()));
      }
      void Promise.allSettled(prefetches);
    });

    return () => task.cancel();
  }, [payoutFeaturesEnabled, queryClient]);

  return null;
}
