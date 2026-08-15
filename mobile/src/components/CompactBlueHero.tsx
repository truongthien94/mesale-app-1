import type { PropsWithChildren, ReactNode } from "react";
import { LinearGradient } from "expo-linear-gradient";
import type { LucideIcon } from "lucide-react-native";
import { StyleSheet, Text, View, type StyleProp, type ViewStyle } from "react-native";
import { useTheme } from "@/theme/ThemeProvider";

export function CompactBlueHero({
  aside,
  children,
  eyebrow,
  icon: Icon,
  style,
  subtitle,
  title
}: PropsWithChildren<{
  aside?: ReactNode;
  eyebrow?: string;
  icon: LucideIcon;
  style?: StyleProp<ViewStyle>;
  subtitle?: string;
  title: string;
}>) {
  const { scheme } = useTheme();

  return (
    <LinearGradient
      colors={scheme === "dark" ? ["#1976d2", "#0752a6"] : ["#3ba8ff", "#0872df"]}
      end={{ x: 1, y: 1 }}
      start={{ x: 0, y: 0 }}
      style={[styles.hero, style]}
    >
      <View pointerEvents="none" style={styles.decorationLarge} />
      <View pointerEvents="none" style={styles.decorationSmall} />
      <View style={styles.heading}>
        <View style={styles.iconBadge}>
          <Icon color="#ffffff" size={22} strokeWidth={2.35} />
        </View>
        <View style={styles.copy}>
          {eyebrow ? <Text style={styles.eyebrow}>{eyebrow}</Text> : null}
          <Text accessibilityRole="header" style={styles.title}>{title}</Text>
          {subtitle ? <Text style={styles.subtitle}>{subtitle}</Text> : null}
        </View>
        {aside}
      </View>
      {children ? <View style={styles.footer}>{children}</View> : null}
    </LinearGradient>
  );
}

const styles = StyleSheet.create({
  hero: { borderRadius: 18, gap: 11, overflow: "hidden", padding: 14 },
  decorationLarge: { backgroundColor: "rgba(255,255,255,0.09)", borderRadius: 100, height: 170, position: "absolute", right: -54, top: -88, width: 170 },
  decorationSmall: { backgroundColor: "rgba(255,255,255,0.08)", borderRadius: 60, bottom: -48, height: 100, left: -34, position: "absolute", width: 100 },
  heading: { alignItems: "center", flexDirection: "row", gap: 11 },
  iconBadge: { alignItems: "center", backgroundColor: "rgba(255,255,255,0.18)", borderColor: "rgba(255,255,255,0.25)", borderRadius: 12, borderWidth: 1, height: 40, justifyContent: "center", width: 40 },
  copy: { flex: 1, gap: 2, minWidth: 0 },
  eyebrow: { color: "rgba(255,255,255,0.76)", fontSize: 9.5, fontWeight: "900", letterSpacing: 0.9, textTransform: "uppercase" },
  title: { color: "#ffffff", fontSize: 18, fontWeight: "900", letterSpacing: -0.35 },
  subtitle: { color: "rgba(255,255,255,0.86)", fontSize: 11.5, lineHeight: 16 },
  footer: { zIndex: 1 }
});
