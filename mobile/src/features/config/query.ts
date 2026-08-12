import { request } from "@/api/client";

export const appConfigKey = ["config"] as const;

export function appConfigRawQueryOptions() {
  return {
    queryKey: appConfigKey,
    queryFn: ({ signal }: { signal: AbortSignal }) => request<unknown>("config", { authenticated: false, signal })
  };
}
