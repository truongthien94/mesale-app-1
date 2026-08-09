import { getLocales } from "expo-localization";

export type SupportedLocale = "vi" | "en";

export function getDeviceLocale(): SupportedLocale {
  return getLocales()[0]?.languageCode?.toLowerCase() === "en" ? "en" : "vi";
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
