export type SupportedLocale = "vi" | "en";

/**
 * Vietnamese is the product default. Device locale must not silently switch
 * the member experience to English; callers may pass the explicit account
 * preference returned by Laravel when a member has selected another locale.
 */
export function getDeviceLocale(): SupportedLocale {
  return "vi";
}

export function resolveLocale(preference?: string | null): SupportedLocale {
  return preference?.trim().toLowerCase() === "en" ? "en" : "vi";
}

const messages = {
  vi: {
    appName: "Mesale",
    signIn: "Đăng nhập",
    email: "Email",
    password: "Mật khẩu",
    logout: "Đăng xuất",
    loading: "Đang tải..."
  },
  en: {
    appName: "Mesale",
    signIn: "Sign in",
    email: "Email",
    password: "Password",
    logout: "Log out",
    loading: "Loading..."
  }
} as const;

export function t(locale: SupportedLocale, key: keyof typeof messages.vi): string {
  return messages[locale][key];
}
