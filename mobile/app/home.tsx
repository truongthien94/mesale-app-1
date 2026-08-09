import { ActivityIndicator, Pressable, StyleSheet, Text, View } from "react-native";
import { Redirect } from "expo-router";
import { useAuth } from "@/auth/AuthProvider";
import { getDeviceLocale, t } from "@/i18n";
import { colors, spacing } from "@/theme/tokens";

export default function HomeScreen() {
  const locale = getDeviceLocale();
  const { isLoading, session, logout } = useAuth();
  if (isLoading) return <View style={styles.loading}><ActivityIndicator color={colors.primary} /></View>;
  if (!session) return <Redirect href="/login" />;
  return (
    <View style={styles.container}>
      <Text style={styles.title}>{t(locale, "appName")}</Text>
      <Text style={styles.text}>
        {locale === "vi" ? "Phiên đăng nhập đang hoạt động." : "Your session is active."}
      </Text>
      <Pressable onPress={() => void logout().catch(() => undefined)} style={styles.button}>
        <Text style={styles.buttonText}>{t(locale, "logout")}</Text>
      </Pressable>
    </View>
  );
}

const styles = StyleSheet.create({
  loading: { flex: 1, alignItems: "center", justifyContent: "center", backgroundColor: colors.background },
  container: {
    flex: 1,
    alignItems: "center",
    justifyContent: "center",
    gap: spacing.md,
    padding: spacing.lg,
    backgroundColor: colors.background
  },
  title: { color: colors.primary, fontSize: 34, fontWeight: "800" },
  text: { color: colors.mutedText, fontSize: 16 },
  button: { backgroundColor: colors.primary, borderRadius: 12, paddingHorizontal: spacing.lg, paddingVertical: 14 },
  buttonText: { color: colors.surface, fontWeight: "700" }
});
