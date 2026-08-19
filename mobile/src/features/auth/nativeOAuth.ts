import * as AppleAuthentication from "expo-apple-authentication";
import * as Crypto from "expo-crypto";
import { Platform } from "react-native";
import { ApiError } from "@/api/client";
import {
  loginWithAppleCredential,
  loginWithGoogleIdToken,
  type LoginResult
} from "@/api/auth";
import { env } from "@/config/env";
import {
  mapOAuthError,
  type AppLocale,
  type AppleNativeCredential,
  type NativeOAuthProvider,
  type OAuthUiState
} from "@/features/auth/nativeOAuthContract";

type NativeOAuthFlowErrorCode =
  | "NATIVE_AUTH_CANCELLED"
  | "GOOGLE_CLIENT_NOT_CONFIGURED"
  | "GOOGLE_DEVELOPER_ERROR"
  | "APPLE_NOT_AVAILABLE"
  | "APPLE_AUTHORIZATION_FAILED"
  | "NATIVE_CREDENTIAL_MISSING"
  | "GOOGLE_PLAY_SERVICES_UNAVAILABLE"
  | "OAUTH_REQUEST_IN_PROGRESS";

class NativeOAuthFlowError extends Error {
  readonly code: NativeOAuthFlowErrorCode;

  constructor(code: NativeOAuthFlowErrorCode, cause?: unknown) {
    super(code, { cause });
    this.name = "NativeOAuthFlowError";
    this.code = code;
  }
}

function errorCode(reason: unknown): string | undefined {
  if (!reason || typeof reason !== "object" || !("code" in reason)) return undefined;
  const code = (reason as { code?: unknown }).code;
  return typeof code === "string" || typeof code === "number" ? String(code) : undefined;
}

export function isGoogleNativeSignInConfigured(): boolean {
  if (Platform.OS !== "android" && Platform.OS !== "ios") return false;
  if (!env.googleWebClientId) return false;
  if (Platform.OS === "ios") {
    return Boolean(env.googleIosClientId && env.googleIosUrlScheme);
  }
  return true;
}

export async function isAppleNativeSignInAvailable(): Promise<boolean> {
  if (Platform.OS !== "ios") return false;
  try {
    return await AppleAuthentication.isAvailableAsync();
  } catch {
    return false;
  }
}

async function randomNonce(): Promise<string> {
  const bytes = await Crypto.getRandomBytesAsync(32);
  return Array.from(bytes, (byte) => byte.toString(16).padStart(2, "0")).join("");
}

export async function requestAppleNativeCredential(): Promise<AppleNativeCredential> {
  if (!await isAppleNativeSignInAvailable()) {
    throw new NativeOAuthFlowError("APPLE_NOT_AVAILABLE");
  }

  const nonce = await randomNonce();
  const hashedNonce = await Crypto.digestStringAsync(
    Crypto.CryptoDigestAlgorithm.SHA256,
    nonce,
    { encoding: Crypto.CryptoEncoding.HEX }
  );

  try {
    const credential = await AppleAuthentication.signInAsync({
      requestedScopes: [
        AppleAuthentication.AppleAuthenticationScope.FULL_NAME,
        AppleAuthentication.AppleAuthenticationScope.EMAIL
      ],
      nonce: hashedNonce
    });

    if (!credential.identityToken || !credential.authorizationCode) {
      throw new NativeOAuthFlowError("NATIVE_CREDENTIAL_MISSING");
    }

    return {
      identityToken: credential.identityToken,
      authorizationCode: credential.authorizationCode,
      nonce
    };
  } catch (reason) {
    if (reason instanceof NativeOAuthFlowError) throw reason;
    const code = errorCode(reason);
    if (code === "ERR_REQUEST_CANCELED") {
      throw new NativeOAuthFlowError("NATIVE_AUTH_CANCELLED", reason);
    }
    if (__DEV__) {
      console.warn("[Apple OAuth] Native authorization failed", { code: code ?? "UNKNOWN" });
    }
    throw new NativeOAuthFlowError("APPLE_AUTHORIZATION_FAILED", reason);
  }
}

export async function signInWithAppleNative(): Promise<LoginResult> {
  return loginWithAppleCredential(await requestAppleNativeCredential());
}

export async function signInWithGoogleNative(): Promise<LoginResult> {
  if (!isGoogleNativeSignInConfigured()) {
    throw new NativeOAuthFlowError("GOOGLE_CLIENT_NOT_CONFIGURED");
  }

  const google = await import("@react-native-google-signin/google-signin");
  google.GoogleSignin.configure(Platform.OS === "ios" ? {
    webClientId: env.googleWebClientId,
    iosClientId: env.googleIosClientId,
    offlineAccess: false
  } : {
    webClientId: env.googleWebClientId,
    offlineAccess: false
  });

  try {
    if (Platform.OS === "android") {
      await google.GoogleSignin.hasPlayServices({ showPlayServicesUpdateDialog: true });
    }
    const response = await google.GoogleSignin.signIn();
    if (response.type === "cancelled") {
      throw new NativeOAuthFlowError("NATIVE_AUTH_CANCELLED");
    }
    if (!response.data.idToken) {
      throw new NativeOAuthFlowError("NATIVE_CREDENTIAL_MISSING");
    }
    return loginWithGoogleIdToken(response.data.idToken);
  } catch (reason) {
    if (reason instanceof NativeOAuthFlowError) throw reason;
    const code = errorCode(reason);
    // Google status 10 means the Android package/SHA-1 does not match OAuth.
    if (code === "10" || code === "DEVELOPER_ERROR") {
      throw new NativeOAuthFlowError("GOOGLE_DEVELOPER_ERROR", reason);
    }
    if (code === google.statusCodes.SIGN_IN_CANCELLED) {
      throw new NativeOAuthFlowError("NATIVE_AUTH_CANCELLED", reason);
    }
    if (code === google.statusCodes.IN_PROGRESS) {
      throw new NativeOAuthFlowError("OAUTH_REQUEST_IN_PROGRESS", reason);
    }
    if (code === google.statusCodes.PLAY_SERVICES_NOT_AVAILABLE) {
      throw new NativeOAuthFlowError("GOOGLE_PLAY_SERVICES_UNAVAILABLE", reason);
    }
    throw reason;
  }
}

export function oauthUiStateFromReason(
  reason: unknown,
  provider: NativeOAuthProvider,
  locale: AppLocale
): OAuthUiState {
  if (reason instanceof ApiError) {
    return mapOAuthError(reason.code, reason.status, provider, locale);
  }
  if (reason instanceof NativeOAuthFlowError) {
    return mapOAuthError(reason.code, 0, provider, locale);
  }
  return mapOAuthError(errorCode(reason), 0, provider, locale);
}
