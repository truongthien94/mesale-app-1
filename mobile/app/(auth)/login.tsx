import { useState } from "react";
import { ActivityIndicator, Pressable, StyleSheet, Text, TextInput, View } from "react-native";
import { router } from "expo-router";
import { useAuth } from "@/auth/AuthProvider";
import { getDeviceLocale, t } from "@/i18n";
import { colors, spacing } from "@/theme/tokens";

export default function LoginScreen() {
  const locale = getDeviceLocale();
  const { login } = useAuth();
  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [error, setError] = useState<string | null>(null);
  const [isSubmitting, setSubmitting] = useState(false);

  async function submit() {
    setError(null);
    setSubmitting(true);
    try {
      await login(email.trim(), password);
      router.replace("/home");
    } catch (reason) {
      setError(reason instanceof Error ? reason.message : "Unable to sign in");
    } finally {
      setSubmitting(false);
    }
  }

  return <View style={styles.container}>
    <Text style={styles.title}>{t(locale, "appName")}</Text>
    <Text style={styles.subtitle}>{t(locale, "signIn")}</Text>
    <TextInput autoCapitalize="none" autoComplete="email" keyboardType="email-address" placeholder={t(locale, "email")} value={email} onChangeText={setEmail} style={styles.input} />
    <TextInput autoCapitalize="none" autoComplete="password" placeholder={t(locale, "password")} secureTextEntry value={password} onChangeText={setPassword} style={styles.input} />
    {error ? <Text accessibilityRole="alert" style={styles.error}>{error}</Text> : null}
    <Pressable disabled={isSubmitting || !email || !password} onPress={() => void submit()} style={({ pressed }) => [styles.button, (isSubmitting || !email || !password) && styles.disabled, pressed && styles.pressed]}>
      {isSubmitting ? <ActivityIndicator color={colors.surface} /> : <Text style={styles.buttonText}>{t(locale, "signIn")}</Text>}
    </Pressable>
  </View>;
}

const styles = StyleSheet.create({
  container: { flex: 1, justifyContent: "center", padding: spacing.lg, gap: spacing.md, backgroundColor: colors.background },
  title: { color: colors.primary, fontSize: 34, fontWeight: "800" },
  subtitle: { color: colors.text, fontSize: 20, fontWeight: "700", marginBottom: spacing.sm },
  input: { backgroundColor: colors.surface, borderColor: colors.border, borderRadius: 12, borderWidth: 1, color: colors.text, paddingHorizontal: spacing.md, paddingVertical: 14 },
  button: { alignItems: "center", backgroundColor: colors.primary, borderRadius: 12, justifyContent: "center", minHeight: 50 },
  buttonText: { color: colors.surface, fontSize: 16, fontWeight: "700" },
  disabled: { opacity: 0.45 },
  pressed: { opacity: 0.8 },
  error: { color: colors.danger }
});
