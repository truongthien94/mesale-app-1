import { useQuery } from "@tanstack/react-query";
import { createContext, createElement, useContext, useEffect, type PropsWithChildren } from "react";
import { AppState, Platform } from "react-native";
import { appConfigRawQueryOptions } from "@/features/config/query";

const IosPayoutFeaturesContext = createContext<boolean | null>(null);

function isRecord(value: unknown): value is Record<string, unknown> {
  return value !== null && typeof value === "object" && !Array.isArray(value);
}

export function readIosPayoutRemoteFlag(source: unknown): boolean {
  if (!isRecord(source) || !isRecord(source.features)) return false;
  return source.features.ios_payout_features_enabled === true;
}

export function resolveIosPayoutFeaturesEnabled(platform: string, source: unknown): boolean {
  return platform !== "ios" || readIosPayoutRemoteFlag(source);
}

export function IosPayoutFeaturesProvider({ children }: PropsWithChildren) {
  const configQuery = useQuery({
    ...appConfigRawQueryOptions(),
    enabled: Platform.OS === "ios",
    retry: false,
    select: readIosPayoutRemoteFlag,
    staleTime: 30_000
  });

  useEffect(() => {
    if (Platform.OS !== "ios") return;
    const subscription = AppState.addEventListener("change", (state) => {
      if (state === "active") void configQuery.refetch();
    });
    return () => subscription.remove();
  }, [configQuery.refetch]);

  const payoutFeaturesEnabled = Platform.OS !== "ios" || configQuery.data === true;
  return createElement(IosPayoutFeaturesContext.Provider, { value: payoutFeaturesEnabled }, children);
}

export function useIosPayoutFeaturesEnabled(): boolean {
  const value = useContext(IosPayoutFeaturesContext);
  if (value === null) {
    throw new Error("useIosPayoutFeaturesEnabled must be used inside IosPayoutFeaturesProvider");
  }
  return value;
}
