export const colors = {
  background: "#f8fafc",
  surface: "#ffffff",
  text: "#0f172a",
  mutedText: "#64748b",
  primary: "#f97316",
  border: "#e2e8f0",
  danger: "#dc2626"
} as const;

export const spacing = { xs: 4, sm: 8, md: 16, lg: 24, xl: 32 } as const;

export const theme = { colors, spacing, radius: { sm: 8, md: 12, lg: 20 } } as const;

export type Theme = typeof theme;
