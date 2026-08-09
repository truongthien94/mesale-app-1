import type { ConfigContext, ExpoConfig } from "expo/config";

export default ({ config }: ConfigContext): ExpoConfig => {
  const baseConfig = config as ExpoConfig;
  const iosUrlScheme = process.env.EXPO_PUBLIC_GOOGLE_IOS_URL_SCHEME?.trim();
  if (!iosUrlScheme) return baseConfig;
  if (!iosUrlScheme.startsWith("com.googleusercontent.apps.")) {
    throw new Error("EXPO_PUBLIC_GOOGLE_IOS_URL_SCHEME must be the reversed Google iOS client ID.");
  }

  return {
    ...baseConfig,
    plugins: [
      ...(baseConfig.plugins ?? []),
      ["@react-native-google-signin/google-signin", { iosUrlScheme }]
    ]
  };
};
