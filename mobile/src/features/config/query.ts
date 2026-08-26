import Constants from "expo-constants";
import { Platform } from "react-native";
import { request } from "@/api/client";

export const appConfigKey = ["config"] as const;

export function createAppConfigHeaders(platform: string, version: unknown): Record<string, string> {
  const headers: Record<string, string> = {
    "X-Mesale-App-Platform": platform
  };
  if (typeof version === "string" && version.trim().length > 0) {
    headers["X-Mesale-App-Version"] = version.trim();
  }
  return headers;
}

export function appConfigRequestOptions(signal?: AbortSignal) {
  return {
    authenticated: false,
    headers: createAppConfigHeaders(Platform.OS, Constants.expoConfig?.version),
    signal
  };
}

export function appConfigRawQueryOptions() {
  return {
    queryKey: appConfigKey,
    queryFn: ({ signal }: { signal: AbortSignal }) => request<unknown>("config", appConfigRequestOptions(signal))
  };
}
