import * as SecureStore from "expo-secure-store";
import { Platform } from "react-native";

const themePreferenceStorageKey = "mesale.theme.v1";

export type ThemePreference = "light" | "dark" | "system";

function isThemePreference(value: unknown): value is ThemePreference {
  return value === "light" || value === "dark" || value === "system";
}

export async function loadThemePreference(): Promise<ThemePreference | null> {
  if (Platform.OS === "web") return null;

  const raw = await SecureStore.getItemAsync(themePreferenceStorageKey);
  if (!raw) return null;

  try {
    const value: unknown = JSON.parse(raw);
    return isThemePreference(value) ? value : null;
  } catch {
    return null;
  }
}

export async function saveThemePreference(value: ThemePreference): Promise<void> {
  if (Platform.OS === "web") return;

  await SecureStore.setItemAsync(themePreferenceStorageKey, JSON.stringify(value), {
    keychainAccessible: SecureStore.WHEN_UNLOCKED_THIS_DEVICE_ONLY
  });
}
