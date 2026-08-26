import { Redirect } from "expo-router";
import type { PropsWithChildren } from "react";
import { useIosPayoutFeaturesEnabled } from "@/config/features";

export function IosPayoutRouteGuard({ children }: PropsWithChildren) {
  const payoutFeaturesEnabled = useIosPayoutFeaturesEnabled();
  if (!payoutFeaturesEnabled) return <Redirect href="/(tabs)/home" />;
  return children;
}
