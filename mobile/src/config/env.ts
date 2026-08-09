function readApiBaseUrl(): string {
  const value = process.env.EXPO_PUBLIC_API_BASE_URL?.trim();
  if (!value) {
    throw new Error("EXPO_PUBLIC_API_BASE_URL must be configured for this app environment.");
  }

  let url: URL;
  try {
    url = new URL(value);
  } catch {
    throw new Error("EXPO_PUBLIC_API_BASE_URL must be a valid absolute HTTP(S) URL.");
  }

  if (url.protocol !== "https:" && (!__DEV__ || url.protocol !== "http:")) {
    throw new Error("EXPO_PUBLIC_API_BASE_URL must use HTTPS outside local development.");
  }

  return value.replace(/\/$/, "");
}

function readTimeout(): number {
  const value = Number(process.env.EXPO_PUBLIC_API_TIMEOUT_MS ?? "15000");
  return Number.isFinite(value) && value > 0 ? value : 15000;
}

export const env = {
  apiBaseUrl: readApiBaseUrl(),
  apiTimeoutMs: readTimeout()
} as const;
