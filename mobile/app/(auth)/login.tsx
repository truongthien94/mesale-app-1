import { useEffect, useRef, useState } from "react";
import * as AppleAuthentication from "expo-apple-authentication";
import { ActivityIndicator, Pressable, StyleSheet, Text, TextInput, View } from "react-native";
import { Redirect } from "expo-router";
import { useAuth } from "@/auth/AuthProvider";
import { resolveAuthGate } from "@/auth/routing";
import { AuthKeyboardScreen } from "@/auth/AuthKeyboardScreen";
import { getDeviceLocale, t } from "@/i18n";
import { colors, spacing } from "@/theme/tokens";
import { AuthLink } from "@/features/auth/components";
import { LegalLinks } from "@/features/legal/LegalLinks";
import {
  isAppleNativeSignInAvailable,
  isGoogleNativeSignInConfigured,
  oauthUiStateFromReason
} from "@/features/auth/nativeOAuth";
import type { NativeOAuthProvider } from "@/features/auth/nativeOAuthContract";

export default function LoginScreen() {
  const locale = getDeviceLocale();
  const { isLoading: isAuthLoading, pendingAuth, session, user, login, loginWithApple, loginWithGoogle } = useAuth();
  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [error, setError] = useState<string | null>(null);
  const [notice, setNotice] = useState<string | null>(null);
  const [isSubmitting, setSubmitting] = useState(false);
  const [nativeProvider, setNativeProvider] = useState<NativeOAuthProvider | null>(null);
  const [appleAvailable, setAppleAvailable] = useState(false);
  const [disabledProviders, setDisabledProviders] = useState<Set<NativeOAuthProvider>>(() => new Set());
  const passwordInputRef = useRef<TextInput>(null);
  const normalizedLogin = email.trim();
  const isBusy = isSubmitting || nativeProvider !== null;
  const cannotSubmit = isBusy || !normalizedLogin || !password;
  const googleConfigured = isGoogleNativeSignInConfigured();

  useEffect(() => {
    let active = true;
    void isAppleNativeSignInAvailable().then((available) => {
      if (active) setAppleAvailable(available);
    });
    return () => { active = false; };
  }, []);

  async function submit() {
    setError(null);
    setNotice(null);
    setSubmitting(true);
    try {
      await login(normalizedLogin, password);
    } catch (reason) {
      setError(reason instanceof Error ? reason.message : "Unable to sign in");
    } finally {
      setSubmitting(false);
    }
  }

  async function submitNative(provider: NativeOAuthProvider) {
    setError(null);
    setNotice(null);
    setNativeProvider(provider);
    try {
      if (provider === "google") await loginWithGoogle();
      else await loginWithApple();
    } catch (reason) {
      const state = oauthUiStateFromReason(reason, provider, locale);
      if (state.disablesProvider) {
        setDisabledProviders((current) => new Set(current).add(provider));
      }
      if (state.isCancellation) setNotice(state.message);
      else setError(state.message);
    } finally {
      setNativeProvider(null);
    }
  }

  if (isAuthLoading) return <View style={styles.loading}><ActivityIndicator color={colors.primary} /></View>;
  const authGate = resolveAuthGate(pendingAuth, Boolean(session), user?.referralPromptPending ?? false);
  if (authGate) return <Redirect href={authGate} />;

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
        editable={!isBusy}
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
        editable={!isBusy}
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
    {notice ? <Text accessibilityLiveRegion="polite" style={styles.notice}>{notice}</Text> : null}
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
    {googleConfigured || appleAvailable ? <View style={styles.dividerRow}><View style={styles.divider} /><Text style={styles.dividerText}>{locale === "vi" ? "hoặc" : "or"}</Text><View style={styles.divider} /></View> : null}
    {googleConfigured ? (
      <Pressable
        accessibilityLabel={locale === "vi" ? "Đăng nhập bằng Google" : "Sign in with Google"}
        accessibilityRole="button"
        accessibilityState={{ disabled: isBusy || disabledProviders.has("google"), busy: nativeProvider === "google" }}
        disabled={isBusy || disabledProviders.has("google")}
        onPress={() => void submitNative("google")}
        style={({ pressed }) => [styles.oauthButton, (isBusy || disabledProviders.has("google")) && styles.disabled, pressed && styles.pressed]}
      >
        {nativeProvider === "google" ? <ActivityIndicator color={colors.text} /> : <Text style={styles.oauthButtonText}>{locale === "vi" ? "Đăng nhập bằng Google" : "Sign in with Google"}</Text>}
      </Pressable>
    ) : null}
    {appleAvailable ? (
      <View
        accessibilityElementsHidden={disabledProviders.has("apple")}
        importantForAccessibility={disabledProviders.has("apple") ? "no-hide-descendants" : "auto"}
        pointerEvents={isBusy || disabledProviders.has("apple") ? "none" : "auto"}
        style={[styles.appleButtonContainer, (isBusy || disabledProviders.has("apple")) && styles.disabled]}
      >
        {nativeProvider === "apple" ? (
          <View style={styles.appleLoading}><ActivityIndicator color="#ffffff" /></View>
        ) : (
          <AppleAuthentication.AppleAuthenticationButton
            buttonStyle={AppleAuthentication.AppleAuthenticationButtonStyle.BLACK}
            buttonType={AppleAuthentication.AppleAuthenticationButtonType.SIGN_IN}
            cornerRadius={12}
            onPress={() => void submitNative("apple")}
            style={styles.appleButton}
          />
        )}
      </View>
    ) : null}
    <View style={styles.links}>
      <AuthLink href="/forgot-password">{locale === "vi" ? "Quên mật khẩu?" : "Forgot password?"}</AuthLink>
      <AuthLink href="/register">{locale === "vi" ? "Chưa có tài khoản? Đăng ký" : "New here? Create an account"}</AuthLink>
    </View>
    <LegalLinks />
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
  notice: { color: colors.primary },
  dividerRow: { alignItems: "center", flexDirection: "row", gap: spacing.sm },
  divider: { backgroundColor: colors.border, flex: 1, height: 1 },
  dividerText: { color: colors.mutedText, fontSize: 13 },
  oauthButton: { alignItems: "center", backgroundColor: colors.surface, borderColor: colors.border, borderRadius: 12, borderWidth: 1, justifyContent: "center", minHeight: 50 },
  oauthButtonText: { color: colors.text, fontSize: 16, fontWeight: "700" },
  appleButtonContainer: { minHeight: 50 },
  appleButton: { height: 50, width: "100%" },
  appleLoading: { alignItems: "center", backgroundColor: "#000000", borderRadius: 12, height: 50, justifyContent: "center" },
  links: { gap: spacing.md, paddingTop: spacing.xs }
});
