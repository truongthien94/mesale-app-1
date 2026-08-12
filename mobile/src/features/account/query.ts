import { request } from "@/api/client";
import { accountPaths } from "@/features/account/contracts";
import type { AccountData } from "@/features/account/types";

export const accountDetailKey = ["account"] as const;

export function accountDetailQueryOptions() {
  return {
    queryKey: accountDetailKey,
    queryFn: ({ signal }: { signal: AbortSignal }) => request<AccountData>(accountPaths.account, { signal })
  };
}
