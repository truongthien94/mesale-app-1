const developmentApiUrl = "http://localhost:8000/api/v1/openapi";

function readTimeout(): number {
  const value = Number(process.env.EXPO_PUBLIC_API_TIMEOUT_MS ?? "15000");
  return Number.isFinite(value) && value > 0 ? value : 15000;
}

/**
 * Runtime configuration is supplied by the EAS profile/environment. The
 * localhost fallback is development-only and keeps production out of code.
 */
export const env = {
  apiBaseUrl: (process.env.EXPO_PUBLIC_API_BASE_URL?.trim() || developmentApiUrl).replace(/\/$/, ""),
  apiTimeoutMs: readTimeout()
} as const;
