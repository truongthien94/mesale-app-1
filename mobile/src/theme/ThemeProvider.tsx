import { createContext, useCallback, useContext, useEffect, useMemo, useState } from "react";
import { useColorScheme } from "react-native";
import { darkColors, lightColors, radius, spacing } from "@/theme/tokens";
import { loadThemePreference, saveThemePreference, type ThemePreference } from "@/theme/themePreference";

type ColorScheme = "light" | "dark";

type ThemeContextValue = {
  colors: typeof lightColors;
  spacing: typeof spacing;
  radius: typeof radius;
  scheme: ColorScheme;
  preference: ThemePreference;
  setPreference: (value: ThemePreference) => void;
};

const ThemeContext = createContext<ThemeContextValue | null>(null);

function resolveScheme(preference: ThemePreference, systemScheme: ColorScheme | null | undefined): ColorScheme {
  if (preference === "light" || preference === "dark") return preference;
  return systemScheme === "dark" ? "dark" : "light";
}

export function ThemeProvider({ children }: { children: React.ReactNode }) {
  const systemScheme = useColorScheme();
  const [preference, setPreferenceState] = useState<ThemePreference>("system");

  useEffect(() => {
    let mounted = true;
    void loadThemePreference().then((storedPreference) => {
      if (mounted && storedPreference) setPreferenceState(storedPreference);
    });
    return () => {
      mounted = false;
    };
  }, []);

  const setPreference = useCallback((value: ThemePreference) => {
    setPreferenceState(value);
    void saveThemePreference(value).catch(() => {
      // Preference persistence should never block or crash the UI.
    });
  }, []);

  const scheme = resolveScheme(preference, systemScheme);
  const value = useMemo<ThemeContextValue>(() => ({
    colors: scheme === "dark" ? darkColors : lightColors,
    spacing,
    radius,
    scheme,
    preference,
    setPreference
  }), [preference, scheme, setPreference]);

  return <ThemeContext.Provider value={value}>{children}</ThemeContext.Provider>;
}

export function useTheme(): ThemeContextValue {
  const value = useContext(ThemeContext);
  if (!value) throw new Error("useTheme must be used inside ThemeProvider.");
  return value;
}
