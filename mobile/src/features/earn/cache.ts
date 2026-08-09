import type { QueryClient } from "@tanstack/react-query";

export async function invalidateRewardCaches(queryClient: QueryClient): Promise<void> {
  await Promise.all([
    queryClient.invalidateQueries({ queryKey: ["account"] }),
    queryClient.invalidateQueries({ queryKey: ["wallet", "account"] }),
    queryClient.invalidateQueries({ queryKey: ["wallet", "balance-logs"] })
  ]);
}
