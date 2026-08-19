import type { ConfigContext, ExpoConfig } from "expo/config";

// Injects the Android release signingConfig during prebuild. It only reads
// credentials from Gradle properties/env at build time, so it is safe to apply
// on every platform (it no-ops on non-Android native projects).
const ANDROID_RELEASE_SIGNING_PLUGIN = "./plugins/withAndroidReleaseSigning";

export default ({ config }: ConfigContext): ExpoConfig => {
  const baseConfig = config as ExpoConfig;
  const iosUrlScheme = process.env.EXPO_PUBLIC_GOOGLE_IOS_URL_SCHEME?.trim();
  const isProductionBuild = process.env.EAS_BUILD_PROFILE === "production";
  // EAS_BUILD_PLATFORM is "ios" | "android" during an EAS Build; it is absent
  // when Expo evaluates the config locally (e.g. prebuild, expo start, tests).
  const buildPlatform = process.env.EAS_BUILD_PLATFORM;
  // The Google config plugin only injects an iOS URL scheme; Android Google
  // Sign-In relies on the Web client ID and does not need this value. Only
  // block a production build when we are actually building iOS.
  const requiresIosUrlScheme = isProductionBuild && buildPlatform !== "android";
  if (!iosUrlScheme) {
    if (requiresIosUrlScheme) {
      throw new Error("EXPO_PUBLIC_GOOGLE_IOS_URL_SCHEME is required for iOS production builds.");
    }
    return {
      ...baseConfig,
      plugins: [
        ...(baseConfig.plugins ?? []),
        ANDROID_RELEASE_SIGNING_PLUGIN
      ]
    };
  }
  if (!iosUrlScheme.startsWith("com.googleusercontent.apps.")) {
    throw new Error("EXPO_PUBLIC_GOOGLE_IOS_URL_SCHEME must be the reversed Google iOS client ID.");
  }

  return {
    ...baseConfig,
    plugins: [
      ...(baseConfig.plugins ?? []),
      ["@react-native-google-signin/google-signin", { iosUrlScheme }],
      ANDROID_RELEASE_SIGNING_PLUGIN
    ]
  };
};
