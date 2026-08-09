import { ActivityIndicator, Pressable, StyleSheet, Text, View } from "react-native";
import { router } from "expo-router";
import { useAuth } from "@/auth/AuthProvider";
import { getDeviceLocale, t } from "@/i18n";
import { colors, spacing } from "@/theme/tokens";

export default function HomeScreen() {
  const locale = getDeviceLocale();
  const { session, logout } = useAuth();
  if (!session) {
    router.replace("/login");
    return <ActivityIndicator />;
  }
  return (
    <View style={styles.container}>
      <Text style={styles.title}>{t(locale, "appName")}</Text>
      <Text style={styles.text}>
        {locale === "vi" ? "Phiên đăng nhập đang hoạt động." : "Your session is active."}
      </Text>
      <Pressable onPress={() => void logout().then(() => router.replace("/login"))} style={styles.button}>
        <Text style={styles.buttonText}>{t(locale, "logout")}</Text>
      </Pressable>
    </View>
  );
}

const styles = StyleSheet.create({
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
