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

function readOptionalPublicValue(name: string, value: string | undefined): string | undefined {
  const normalized = value?.trim();
  if (normalized?.toLowerCase().includes("placeholder")) {
    throw new Error(`${name} must not contain a placeholder credential.`);
  }
  return normalized || undefined;
}

export const env = {
  apiBaseUrl: readApiBaseUrl(),
  apiTimeoutMs: readTimeout(),
  googleWebClientId: readOptionalPublicValue("EXPO_PUBLIC_GOOGLE_WEB_CLIENT_ID", process.env.EXPO_PUBLIC_GOOGLE_WEB_CLIENT_ID),
  googleIosClientId: readOptionalPublicValue("EXPO_PUBLIC_GOOGLE_IOS_CLIENT_ID", process.env.EXPO_PUBLIC_GOOGLE_IOS_CLIENT_ID),
  googleIosUrlScheme: readOptionalPublicValue("EXPO_PUBLIC_GOOGLE_IOS_URL_SCHEME", process.env.EXPO_PUBLIC_GOOGLE_IOS_URL_SCHEME)
} as const;
