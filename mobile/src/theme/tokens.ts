export const lightColors = {
  background: "#f8fafc",
  surface: "#ffffff",
  text: "#0f172a",
  mutedText: "#64748b",
  primary: "#f97316",
  border: "#e2e8f0",
  danger: "#dc2626"
};

export const darkColors = {
  background: "#0f172a",
  surface: "#1e293b",
  text: "#f8fafc",
  mutedText: "#94a3b8",
  primary: "#f97316",
  border: "#334155",
  danger: "#f87171"
};

// Keep the legacy light-mode export stable while new screens opt into ThemeProvider.
export const colors = lightColors;
export const spacing = { xs: 4, sm: 8, md: 16, lg: 24, xl: 32 } as const;

export const radius = { sm: 8, md: 12, lg: 20 } as const;

export const theme = { colors, spacing, radius } as const;

export type Theme = {
  colors: typeof lightColors;
  spacing: typeof spacing;
  radius: typeof radius;
  scheme: "light" | "dark";
};
