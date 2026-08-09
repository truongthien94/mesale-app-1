import { QueryClient } from "@tanstack/react-query";
import { ApiError } from "@/api/client";

export function createAppQueryClient(): QueryClient {
  return new QueryClient({
    defaultOptions: {
      queries: {
        staleTime: 30_000,
        retry(failureCount, error) {
          if (failureCount >= 1) return false;
          if (error instanceof ApiError) {
            return error.isNetworkError || error.status === 429 || error.status >= 500;
          }
          return false;
        }
      },
      mutations: {
        retry: false
      }
    }
  });
}

export function clearAppQueryCache(queryClient: QueryClient): void {
  queryClient.clear();
}
