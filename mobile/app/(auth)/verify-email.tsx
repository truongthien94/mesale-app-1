import { useState } from "react";
import { AccessibilityInfo, ActivityIndicator, Platform, Pressable, StyleSheet, Text, TextInput, View } from "react-native";
import { Redirect } from "expo-router";
import { useAuth } from "@/auth/AuthProvider";
import { resolveAuthGate } from "@/auth/routing";
import { AuthKeyboardScreen } from "@/auth/AuthKeyboardScreen";
import { getDeviceLocale } from "@/i18n";
import { colors, spacing } from "@/theme/tokens";

export default function VerifyEmailScreen() {
  const locale = getDeviceLocale();
  const {
    isLoading: isAuthLoading,
    pendingAuth,
    session,
    user,
    verifyEmail,
    resendEmailVerification,
    cancelAuthContinuation
  } = useAuth();
  const [otpCode, setOtpCode] = useState("");
  const [error, setError] = useState<string | null>(null);
  const [notice, setNotice] = useState<string | null>(null);
  const [action, setAction] = useState<"verify" | "resend" | null>(null);

  async function submit() {
    setError(null);
    setNotice(null);
    setAction("verify");
    try {
      await verifyEmail(otpCode);
    } catch (reason) {
      setError(reason instanceof Error ? reason.message : "Unable to verify email");
    } finally {
      setAction(null);
    }
  }

  async function resend() {
    setError(null);
    setNotice(null);
    setAction("resend");
    try {
      await resendEmailVerification();
      const message = locale === "vi" ? "Mã xác minh mới đã được gửi." : "A new verification code was sent.";
      setNotice(message);
      if (Platform.OS === "ios") {
        AccessibilityInfo.announceForAccessibilityWithOptions(message, { queue: true });
      }
    } catch (reason) {
      setError(reason instanceof Error ? reason.message : "Unable to resend code");
    } finally {
      setAction(null);
    }
  }

  if (isAuthLoading) return <Loading />;
  const authGate = resolveAuthGate(pendingAuth, Boolean(session), user?.referralPromptPending ?? false);
  if (authGate !== "/verify-email" || pendingAuth?.kind !== "email-verification") {
    return <Redirect href={authGate ?? "/login"} />;
  }

  const disabled = action !== null;
  const cannotVerify = disabled || otpCode.length !== 6;
  const verifyLabel = error ? (locale === "vi" ? "Thử lại" : "Retry") : (locale === "vi" ? "Xác minh" : "Verify");
  return (
    <AuthKeyboardScreen>
      <Text accessibilityRole="header" style={styles.title}>{locale === "vi" ? "Xác minh email" : "Verify email"}</Text>
      <Text style={styles.description}>
        {locale === "vi"
          ? `Nhập mã 6 chữ số đã gửi đến ${pendingAuth.email}.`
          : `Enter the 6-digit code sent to ${pendingAuth.email}.`}
      </Text>
      <TextInput
        accessibilityHint={locale === "vi" ? "Nhập mã xác minh gồm 6 chữ số đã gửi qua email." : "Enter the 6-digit verification code sent by email."}
        accessibilityLabel={locale === "vi" ? "Mã xác minh email" : "Email verification code"}
        autoComplete="one-time-code"
        editable={!disabled}
        enablesReturnKeyAutomatically
        keyboardType="number-pad"
        maxLength={6}
        onChangeText={(value) => setOtpCode(value.replace(/\D/g, ""))}
        onSubmitEditing={() => { if (!cannotVerify) void submit(); }}
        placeholder="000000"
        returnKeyType="done"
        style={styles.input}
        textContentType="oneTimeCode"
        value={otpCode}
      />
      {error ? <Text accessibilityRole="alert" style={styles.error}>{error}</Text> : null}
      {notice ? <Text accessibilityLiveRegion="polite" style={styles.notice}>{notice}</Text> : null}
      <Pressable
        accessibilityHint={locale === "vi" ? "Xác minh email và hoàn tất đăng nhập." : "Verify the email address and finish signing in."}
        accessibilityLabel={verifyLabel}
        accessibilityRole="button"
        accessibilityState={{ disabled: cannotVerify, busy: action === "verify" }}
        disabled={cannotVerify}
        onPress={() => void submit()}
        style={({ pressed }) => [styles.primaryButton, cannotVerify && styles.disabled, pressed && styles.pressed]}
      >
        {action === "verify" ? <ActivityIndicator color={colors.surface} /> : <Text style={styles.primaryButtonText}>{verifyLabel}</Text>}
      </Pressable>
      <Pressable
        accessibilityHint={locale === "vi" ? "Yêu cầu gửi mã xác minh email mới." : "Request a new email verification code."}
        accessibilityLabel={locale === "vi" ? "Gửi lại mã" : "Resend code"}
        accessibilityRole="button"
        accessibilityState={{ disabled, busy: action === "resend" }}
        disabled={disabled}
        onPress={() => void resend()}
        style={({ pressed }) => [styles.secondaryButton, disabled && styles.disabled, pressed && styles.pressed]}
      >
        {action === "resend" ? <ActivityIndicator color={colors.primary} /> : <Text style={styles.secondaryButtonText}>{locale === "vi" ? "Gửi lại mã" : "Resend code"}</Text>}
      </Pressable>
      <Pressable
        accessibilityHint={locale === "vi" ? "Hủy bước xác minh và quay lại màn hình đăng nhập." : "Cancel verification and return to the sign-in screen."}
        accessibilityLabel={locale === "vi" ? "Hủy và đăng nhập lại" : "Cancel and sign in again"}
        accessibilityRole="button"
        accessibilityState={{ disabled }}
        disabled={disabled}
        onPress={cancelAuthContinuation}
        style={({ pressed }) => [styles.cancelButton, disabled && styles.disabled, pressed && styles.pressed]}
      >
        <Text style={styles.cancelText}>{locale === "vi" ? "Hủy và đăng nhập lại" : "Cancel and sign in again"}</Text>
      </Pressable>
    </AuthKeyboardScreen>
  );
}

function Loading() {
  return <View style={styles.loading}><ActivityIndicator color={colors.primary} /></View>;
}

const styles = StyleSheet.create({
  loading: { flex: 1, alignItems: "center", justifyContent: "center", backgroundColor: colors.background },
  title: { color: colors.text, fontSize: 28, fontWeight: "800" },
  description: { color: colors.mutedText, fontSize: 16, lineHeight: 24 },
  input: { backgroundColor: colors.surface, borderColor: colors.border, borderRadius: 12, borderWidth: 1, color: colors.text, fontSize: 24, letterSpacing: 8, minHeight: 50, paddingHorizontal: spacing.md, paddingVertical: 14, textAlign: "center" },
  primaryButton: { alignItems: "center", backgroundColor: colors.primary, borderRadius: 12, justifyContent: "center", minHeight: 50 },
  primaryButtonText: { color: colors.surface, fontSize: 16, fontWeight: "700" },
  secondaryButton: { alignItems: "center", borderColor: colors.primary, borderRadius: 12, borderWidth: 1, justifyContent: "center", minHeight: 50 },
  secondaryButtonText: { color: colors.primary, fontSize: 16, fontWeight: "700" },
  cancelButton: { alignItems: "center", minHeight: 44, justifyContent: "center" },
  cancelText: { color: colors.mutedText, fontWeight: "600" },
  disabled: { opacity: 0.45 },
  pressed: { opacity: 0.8 },
  error: { color: colors.danger },
  notice: { color: colors.primary }
});
