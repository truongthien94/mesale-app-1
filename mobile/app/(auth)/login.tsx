import { useRef, useState } from "react";
import { ActivityIndicator, Pressable, StyleSheet, Text, TextInput, View } from "react-native";
import { Redirect } from "expo-router";
import { useAuth } from "@/auth/AuthProvider";
import { AuthKeyboardScreen } from "@/auth/AuthKeyboardScreen";
import { getDeviceLocale, t } from "@/i18n";
import { colors, spacing } from "@/theme/tokens";
import { AuthLink } from "@/features/auth/components";

export default function LoginScreen() {
  const locale = getDeviceLocale();
  const { isLoading: isAuthLoading, pendingAuth, session, login } = useAuth();
  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [error, setError] = useState<string | null>(null);
  const [isSubmitting, setSubmitting] = useState(false);
  const passwordInputRef = useRef<TextInput>(null);
  const normalizedLogin = email.trim();
  const cannotSubmit = isSubmitting || !normalizedLogin || !password;

  async function submit() {
    setError(null);
    setSubmitting(true);
    try {
      await login(normalizedLogin, password);
    } catch (reason) {
      setError(reason instanceof Error ? reason.message : "Unable to sign in");
    } finally {
      setSubmitting(false);
    }
  }

  if (isAuthLoading) return <View style={styles.loading}><ActivityIndicator color={colors.primary} /></View>;
  if (session) return <Redirect href="/home" />;
  if (pendingAuth?.kind === "email-verification") return <Redirect href="/verify-email" />;
  if (pendingAuth?.kind === "two-factor") return <Redirect href="/two-factor" />;

  return <AuthKeyboardScreen>
    <Text accessibilityRole="header" style={styles.title}>{t(locale, "appName")}</Text>
    <Text style={styles.subtitle}>{t(locale, "signIn")}</Text>
    <View style={styles.field}>
      <Text style={styles.label}>{t(locale, "email")}</Text>
      <TextInput
        accessibilityHint={locale === "vi" ? "Nhập email hoặc số điện thoại dùng để đăng nhập." : "Enter the email address or phone number used to sign in."}
        accessibilityLabel={t(locale, "email")}
        autoCapitalize="none"
        autoComplete="email"
        editable={!isSubmitting}
        enablesReturnKeyAutomatically
        keyboardType="email-address"
        onChangeText={setEmail}
        onSubmitEditing={() => passwordInputRef.current?.focus()}
        placeholder={t(locale, "email")}
        returnKeyType="next"
        style={styles.input}
        value={email}
      />
    </View>
    <View style={styles.field}>
      <Text style={styles.label}>{t(locale, "password")}</Text>
      <TextInput
        accessibilityHint={locale === "vi" ? "Nhập mật khẩu rồi nhấn đăng nhập trên bàn phím." : "Enter your password, then use the keyboard sign-in action."}
        accessibilityLabel={t(locale, "password")}
        autoCapitalize="none"
        autoComplete="password"
        editable={!isSubmitting}
        enablesReturnKeyAutomatically
        onChangeText={setPassword}
        onSubmitEditing={() => { if (!cannotSubmit) void submit(); }}
        placeholder={t(locale, "password")}
        ref={passwordInputRef}
        returnKeyType="go"
        secureTextEntry
        style={styles.input}
        value={password}
      />
    </View>
    {error ? <Text accessibilityRole="alert" style={styles.error}>{error}</Text> : null}
    <Pressable
      accessibilityHint={locale === "vi" ? "Xác thực thông tin và tiếp tục đăng nhập." : "Authenticate the credentials and continue signing in."}
      accessibilityLabel={t(locale, "signIn")}
      accessibilityRole="button"
      accessibilityState={{ disabled: cannotSubmit, busy: isSubmitting }}
      disabled={cannotSubmit}
      onPress={() => void submit()}
      style={({ pressed }) => [styles.button, cannotSubmit && styles.disabled, pressed && styles.pressed]}
    >
      {isSubmitting ? <ActivityIndicator color={colors.surface} /> : <Text style={styles.buttonText}>{t(locale, "signIn")}</Text>}
    </Pressable>
    <View style={styles.links}>
      <AuthLink href="/forgot-password">{locale === "vi" ? "Quên mật khẩu?" : "Forgot password?"}</AuthLink>
      <AuthLink href="/register">{locale === "vi" ? "Chưa có tài khoản? Đăng ký" : "New here? Create an account"}</AuthLink>
    </View>
  </AuthKeyboardScreen>;
}

const styles = StyleSheet.create({
  loading: { flex: 1, alignItems: "center", justifyContent: "center", backgroundColor: colors.background },
  title: { color: colors.primary, fontSize: 34, fontWeight: "800" },
  subtitle: { color: colors.text, fontSize: 20, fontWeight: "700", marginBottom: spacing.sm },
  field: { gap: spacing.sm },
  label: { color: colors.text, fontSize: 14, fontWeight: "700" },
  input: { backgroundColor: colors.surface, borderColor: colors.border, borderRadius: 12, borderWidth: 1, color: colors.text, minHeight: 50, paddingHorizontal: spacing.md, paddingVertical: 14 },
  button: { alignItems: "center", backgroundColor: colors.primary, borderRadius: 12, justifyContent: "center", minHeight: 50 },
  buttonText: { color: colors.surface, fontSize: 16, fontWeight: "700" },
  disabled: { opacity: 0.45 },
  pressed: { opacity: 0.8 },
  error: { color: colors.danger },
  links: { gap: spacing.md, paddingTop: spacing.xs }
});
