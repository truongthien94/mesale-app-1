export const MOBILE_DEVICE_NAME = "Mesale Mobile";

export type NativeOAuthProvider = "google" | "apple";
export type AppLocale = "vi" | "en";

export type AppleNativeCredential = {
  identityToken: string;
  authorizationCode: string;
  nonce: string;
};

export type GoogleOAuthRequest = {
  id_token: string;
  device_name: string;
};

export type AppleOAuthRequest = {
  identity_token: string;
  authorization_code: string;
  nonce: string;
  device_name: string;
};

export type AppleDeletionRequest = {
  apple_identity_token: string;
  apple_authorization_code: string;
  apple_nonce: string;
};

export type OAuthUiStateKind =
  | "account-link-required"
  | "email-unverified"
  | "email-required"
  | "identity-ambiguous"
  | "credential-invalid"
  | "provider-unavailable"
  | "request-in-progress"
  | "endpoint-disabled"
  | "api-disabled"
  | "service-unavailable"
  | "cancelled"
  | "client-not-configured"
  | "apple-not-available"
  | "credential-missing"
  | "play-services-unavailable"
  | "unknown";

export type OAuthUiState = {
  kind: OAuthUiStateKind;
  code: string;
  message: string;
  disablesProvider: boolean;
  isCancellation: boolean;
};

function requireCredential(value: string, field: string): string {
  const normalized = value.trim();
  if (!normalized) throw new Error(`${field} is required.`);
  return normalized;
}

export function buildGoogleOAuthRequest(idToken: string): GoogleOAuthRequest {
  return {
    id_token: requireCredential(idToken, "Google ID token"),
    device_name: MOBILE_DEVICE_NAME
  };
}

export function buildAppleOAuthRequest(credential: AppleNativeCredential): AppleOAuthRequest {
  return {
    identity_token: requireCredential(credential.identityToken, "Apple identity token"),
    authorization_code: requireCredential(credential.authorizationCode, "Apple authorization code"),
    nonce: requireCredential(credential.nonce, "Apple nonce"),
    device_name: MOBILE_DEVICE_NAME
  };
}

export function buildAppleDeletionRequest(credential: AppleNativeCredential): AppleDeletionRequest {
  return {
    apple_identity_token: requireCredential(credential.identityToken, "Apple identity token"),
    apple_authorization_code: requireCredential(credential.authorizationCode, "Apple authorization code"),
    apple_nonce: requireCredential(credential.nonce, "Apple nonce")
  };
}

function translated(locale: AppLocale, vi: string, en: string): string {
  return locale === "vi" ? vi : en;
}

export function mapOAuthError(
  code: string | undefined,
  status: number,
  provider: NativeOAuthProvider,
  locale: AppLocale
): OAuthUiState {
  const normalizedCode = code?.trim().toUpperCase() || (status === 503 ? "SERVICE_UNAVAILABLE" : "UNKNOWN");
  const providerName = provider === "apple" ? "Apple" : "Google";

  const states: Record<string, Omit<OAuthUiState, "code">> = {
    ACCOUNT_LINK_REQUIRED: {
      kind: "account-link-required",
      message: translated(locale, "Email này đã có tài khoản Mesale. Hãy đăng nhập bằng phương thức hiện tại để liên kết an toàn.", "This email already has a Mesale account. Sign in with the existing method before linking it."),
      disablesProvider: false,
      isCancellation: false
    },
    OAUTH_EMAIL_UNVERIFIED: {
      kind: "email-unverified",
      message: translated(locale, `${providerName} chưa xác minh địa chỉ email này.`, `${providerName} has not verified this email address.`),
      disablesProvider: false,
      isCancellation: false
    },
    OAUTH_EMAIL_REQUIRED: {
      kind: "email-required",
      message: translated(locale, `${providerName} không cung cấp email đã xác minh.`, `${providerName} did not provide a verified email address.`),
      disablesProvider: false,
      isCancellation: false
    },
    OAUTH_IDENTITY_AMBIGUOUS: {
      kind: "identity-ambiguous",
      message: translated(locale, `Danh tính ${providerName} đang liên kết không nhất quán. Vui lòng liên hệ hỗ trợ.`, `This ${providerName} identity has conflicting account links. Contact support.`),
      disablesProvider: false,
      isCancellation: false
    },
    OAUTH_CREDENTIAL_INVALID: {
      kind: "credential-invalid",
      message: translated(locale, `Thông tin đăng nhập ${providerName} không hợp lệ hoặc đã hết hạn.`, `The ${providerName} credential is invalid or expired.`),
      disablesProvider: false,
      isCancellation: false
    },
    OAUTH_PROVIDER_UNAVAILABLE: {
      kind: "provider-unavailable",
      message: translated(locale, `Không thể xác minh ${providerName} lúc này. Vui lòng thử lại sau.`, `${providerName} verification is temporarily unavailable. Try again later.`),
      disablesProvider: false,
      isCancellation: false
    },
    OAUTH_REQUEST_IN_PROGRESS: {
      kind: "request-in-progress",
      message: translated(locale, `Một yêu cầu đăng nhập ${providerName} đang được xử lý.`, `A ${providerName} sign-in request is already in progress.`),
      disablesProvider: false,
      isCancellation: false
    },
    ENDPOINT_DISABLED: {
      kind: "endpoint-disabled",
      message: translated(locale, `Đăng nhập bằng ${providerName} đang tạm tắt trên máy chủ.`, `${providerName} sign-in is currently disabled by the server.`),
      disablesProvider: true,
      isCancellation: false
    },
    API_DISABLED: {
      kind: "api-disabled",
      message: translated(locale, "Dịch vụ ứng dụng Mesale hiện chưa sẵn sàng.", "The Mesale app service is not available yet."),
      disablesProvider: true,
      isCancellation: false
    },
    SERVICE_UNAVAILABLE: {
      kind: "service-unavailable",
      message: translated(locale, `Dịch vụ đăng nhập ${providerName} đang tạm gián đoạn.`, `${providerName} sign-in is temporarily unavailable.`),
      disablesProvider: false,
      isCancellation: false
    },
    NATIVE_AUTH_CANCELLED: {
      kind: "cancelled",
      message: translated(locale, `Đã hủy đăng nhập bằng ${providerName}.`, `${providerName} sign-in was cancelled.`),
      disablesProvider: false,
      isCancellation: true
    },
    GOOGLE_CLIENT_NOT_CONFIGURED: {
      kind: "client-not-configured",
      message: translated(locale, "Đăng nhập Google chưa được cấu hình cho bản build này.", "Google sign-in is not configured for this build."),
      disablesProvider: true,
      isCancellation: false
    },
    APPLE_NOT_AVAILABLE: {
      kind: "apple-not-available",
      message: translated(locale, "Sign in with Apple không khả dụng trên thiết bị này.", "Sign in with Apple is not available on this device."),
      disablesProvider: true,
      isCancellation: false
    },
    NATIVE_CREDENTIAL_MISSING: {
      kind: "credential-missing",
      message: translated(locale, `${providerName} không trả về đủ thông tin xác thực. Vui lòng thử lại.`, `${providerName} did not return a complete credential. Try again.`),
      disablesProvider: false,
      isCancellation: false
    },
    GOOGLE_PLAY_SERVICES_UNAVAILABLE: {
      kind: "play-services-unavailable",
      message: translated(locale, "Google Play Services chưa sẵn sàng trên thiết bị này.", "Google Play Services is not available on this device."),
      disablesProvider: false,
      isCancellation: false
    }
  };

  states.GOOGLE_DEVELOPER_ERROR = {
    kind: "client-not-configured",
    message: translated(
      locale,
      "Cấu hình Google Android chưa khớp với bản app này. Kiểm tra package vn.mesale.app và SHA-1 của APK.",
      "Google Android OAuth is not configured for this build. Check package vn.mesale.app and the APK SHA-1 certificate."
    ),
    disablesProvider: true,
    isCancellation: false
  };

  return {
    ...(states[normalizedCode] ?? {
      kind: "unknown" as const,
      message: translated(locale, `Không thể đăng nhập bằng ${providerName}. Vui lòng thử lại.`, `Unable to sign in with ${providerName}. Try again.`),
      disablesProvider: false,
      isCancellation: false
    }),
    code: normalizedCode
  };
}
