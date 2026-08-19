import {
  createContext,
  useCallback,
  useContext,
  useEffect,
  useMemo,
  useRef,
  useState,
} from "react";
import { useColorScheme } from "react-native";
import { darkColors, lightColors, radius, spacing } from "@/theme/tokens";
import {
  createThemePreferenceCoordinator,
  loadThemePreference,
  type ThemePreference,
} from "@/theme/themePreference";

type ColorScheme = "light" | "dark";
type SystemColorScheme = ReturnType<typeof useColorScheme>;

type ThemeContextValue = {
  colors: typeof lightColors;
  spacing: typeof spacing;
  radius: typeof radius;
  scheme: ColorScheme;
  preference: ThemePreference;
  setPreference: (value: ThemePreference) => void;
};

const ThemeContext = createContext<ThemeContextValue | null>(null);

function resolveScheme(
  preference: ThemePreference,
  systemScheme: SystemColorScheme,
): ColorScheme {
  if (preference === "light" || preference === "dark") return preference;
  return systemScheme === "dark" ? "dark" : "light";
}

export function ThemeProvider({ children }: { children: React.ReactNode }) {
  const systemScheme = useColorScheme();
  const [preference, setPreferenceState] = useState<ThemePreference>("system");
  const coordinatorRef = useRef<ReturnType<
    typeof createThemePreferenceCoordinator
  > | null>(null);
  coordinatorRef.current ??= createThemePreferenceCoordinator();

  useEffect(() => {
    let mounted = true;
    const coordinator = coordinatorRef.current!;
    const observedRevision = coordinator.currentRevision();
    void loadThemePreference()
      .then((storedPreference) => {
        if (
          mounted &&
          storedPreference &&
          coordinator.isCurrentRevision(observedRevision)
        ) {
          setPreferenceState(storedPreference);
        }
      })
      .catch(() => {
        // Storage failures fall back to the system preference without breaking launch.
      });
    return () => {
      mounted = false;
    };
  }, []);

  const setPreference = useCallback((value: ThemePreference) => {
    setPreferenceState(value);
    coordinatorRef.current!.persist(value);
  }, []);

  const scheme = resolveScheme(preference, systemScheme);
  const value = useMemo<ThemeContextValue>(
    () => ({
      colors: scheme === "dark" ? darkColors : lightColors,
      spacing,
      radius,
      scheme,
      preference,
      setPreference,
    }),
    [preference, scheme, setPreference],
  );

  return (
    <ThemeContext.Provider value={value}>{children}</ThemeContext.Provider>
  );
}

export function useTheme(): ThemeContextValue {
  const value = useContext(ThemeContext);
  if (!value) throw new Error("useTheme must be used inside ThemeProvider.");
  return value;
}
