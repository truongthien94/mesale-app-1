import { useEffect } from "react";
import { InteractionManager } from "react-native";
import { router } from "expo-router";
import { useQueryClient } from "@tanstack/react-query";
import { couponQueryOptions } from "@/features/coupons/api";
import { checkinQueryOptions } from "@/features/earn/api";

export function QuickAccessPrefetch() {
  const queryClient = useQueryClient();

  useEffect(() => {
    const task = InteractionManager.runAfterInteractions(() => {
      router.prefetch("/(tabs)/home/coupons");
      router.prefetch("/(tabs)/earn/checkin");
      router.prefetch("/(tabs)/home/tips");

      void Promise.allSettled([
        queryClient.prefetchInfiniteQuery(couponQueryOptions()),
        queryClient.prefetchInfiniteQuery(checkinQueryOptions())
      ]);
    });

    return () => task.cancel();
  }, [queryClient]);

  return null;
}
