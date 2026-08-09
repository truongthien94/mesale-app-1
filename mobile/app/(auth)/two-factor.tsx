import { useRef, useState, type RefObject } from "react";
import { AccessibilityInfo, ActivityIndicator, Platform, Pressable, StyleSheet, Text, TextInput, View } from "react-native";
import { Redirect } from "expo-router";
import { useAuth } from "@/auth/AuthProvider";
import { resolveAuthGate } from "@/auth/routing";
import { AuthKeyboardScreen } from "@/auth/AuthKeyboardScreen";
import { getDeviceLocale } from "@/i18n";
import { colors, spacing } from "@/theme/tokens";

export default function TwoFactorScreen() {
  const locale = getDeviceLocale();
  const {
    isLoading: isAuthLoading,
    pendingAuth,
    session,
    user,
    verifyTwoFactor,
    resendTwoFactorOtp,
    cancelAuthContinuation
  } = useAuth();
  const [googleCode, setGoogleCode] = useState("");
  const [emailCode, setEmailCode] = useState("");
  const [error, setError] = useState<string | null>(null);
  const [notice, setNotice] = useState<string | null>(null);
  const [action, setAction] = useState<"verify" | "resend" | null>(null);
  const emailInputRef = useRef<TextInput>(null);

  const needsGoogle = pendingAuth?.kind === "two-factor" && pendingAuth.methods.includes("google2fa");
  const needsEmail = pendingAuth?.kind === "two-factor" && pendingAuth.methods.includes("email_otp");

  async function submit() {
    setError(null);
    setNotice(null);
    setAction("verify");
    try {
      await verifyTwoFactor({
        google2faCode: needsGoogle ? googleCode : undefined,
        emailOtpCode: needsEmail ? emailCode : undefined
      });
    } catch (reason) {
      setError(reason instanceof Error ? reason.message : "Unable to verify two-factor authentication");
    } finally {
      setAction(null);
    }
  }

  async function resend() {
    setError(null);
    setNotice(null);
    setAction("resend");
    try {
      await resendTwoFactorOtp();
      const message = locale === "vi" ? "Mã OTP email mới đã được gửi." : "A new email OTP was sent.";
      setNotice(message);
      if (Platform.OS === "ios") {
        AccessibilityInfo.announceForAccessibilityWithOptions(message, { queue: true });
      }
    } catch (reason) {
      setError(reason instanceof Error ? reason.message : "Unable to resend email OTP");
    } finally {
      setAction(null);
    }
  }

  if (isAuthLoading) return <Loading />;
  const authGate = resolveAuthGate(pendingAuth, Boolean(session), user?.referralPromptPending ?? false);
  if (authGate !== "/two-factor") return <Redirect href={authGate ?? "/login"} />;

  const validGoogle = !needsGoogle || googleCode.length === 6;
  const validEmail = !needsEmail || emailCode.length === 6;
  const disabled = action !== null;
  const cannotVerify = disabled || !validGoogle || !validEmail;
  const verifyLabel = error ? (locale === "vi" ? "Thử lại" : "Retry") : (locale === "vi" ? "Xác nhận" : "Verify");

  return (
    <AuthKeyboardScreen>
      <Text accessibilityRole="header" style={styles.title}>{locale === "vi" ? "Xác thực hai lớp" : "Two-factor authentication"}</Text>
      <Text style={styles.description}>
        {locale === "vi" ? "Nhập đầy đủ các mã bảo mật được yêu cầu." : "Enter every required security code."}
      </Text>
      {needsGoogle ? (
        <CodeField
          accessibilityHint={locale === "vi" ? "Nhập mã 6 chữ số từ ứng dụng Google Authenticator." : "Enter the 6-digit code from Google Authenticator."}
          editable={!disabled}
          label={locale === "vi" ? "Google Authenticator" : "Google Authenticator"}
          onChange={(value) => {
            setGoogleCode(value);
            if (needsEmail && value.length === 6 && !disabled) {
              emailInputRef.current?.focus();
            }
          }}
          onSubmit={() => {
            if (needsEmail) emailInputRef.current?.focus();
            else if (!cannotVerify) void submit();
          }}
          oneTimeCodeAutofill={false}
          returnKeyType={needsEmail ? "next" : "done"}
          value={googleCode}
        />
      ) : null}
      {needsEmail ? (
        <CodeField
          accessibilityHint={locale === "vi" ? "Nhập mã OTP 6 chữ số đã gửi qua email." : "Enter the 6-digit OTP sent by email."}
          editable={!disabled}
          inputRef={emailInputRef}
          label={locale === "vi" ? "Mã OTP email" : "Email OTP"}
          onChange={setEmailCode}
          onSubmit={() => { if (!cannotVerify) void submit(); }}
          oneTimeCodeAutofill
          returnKeyType="done"
          value={emailCode}
        />
      ) : null}
      {error ? <Text accessibilityRole="alert" style={styles.error}>{error}</Text> : null}
      {notice ? <Text accessibilityLiveRegion="polite" style={styles.notice}>{notice}</Text> : null}
      <Pressable
        accessibilityHint={locale === "vi" ? "Xác thực tất cả mã được yêu cầu và hoàn tất đăng nhập." : "Verify every required code and finish signing in."}
        accessibilityLabel={verifyLabel}
        accessibilityRole="button"
        accessibilityState={{ disabled: cannotVerify, busy: action === "verify" }}
        disabled={cannotVerify}
        onPress={() => void submit()}
        style={({ pressed }) => [styles.primaryButton, cannotVerify && styles.disabled, pressed && styles.pressed]}
      >
        {action === "verify" ? <ActivityIndicator color={colors.surface} /> : <Text style={styles.primaryButtonText}>{verifyLabel}</Text>}
      </Pressable>
      {needsEmail ? (
        <Pressable
          accessibilityHint={locale === "vi" ? "Yêu cầu gửi mã OTP email mới." : "Request a new email OTP."}
          accessibilityLabel={locale === "vi" ? "Gửi lại OTP email" : "Resend email OTP"}
          accessibilityRole="button"
          accessibilityState={{ disabled, busy: action === "resend" }}
          disabled={disabled}
          onPress={() => void resend()}
          style={({ pressed }) => [styles.secondaryButton, disabled && styles.disabled, pressed && styles.pressed]}
        >
          {action === "resend" ? <ActivityIndicator color={colors.primary} /> : <Text style={styles.secondaryButtonText}>{locale === "vi" ? "Gửi lại OTP email" : "Resend email OTP"}</Text>}
        </Pressable>
      ) : null}
      <Pressable
        accessibilityHint={locale === "vi" ? "Hủy xác thực hai lớp và quay lại màn hình đăng nhập." : "Cancel two-factor authentication and return to the sign-in screen."}
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

function CodeField({
  accessibilityHint,
  editable,
  inputRef,
  label,
  onChange,
  onSubmit,
  oneTimeCodeAutofill,
  returnKeyType,
  value
}: {
  accessibilityHint: string;
  editable: boolean;
  inputRef?: RefObject<TextInput | null>;
  label: string;
  onChange(value: string): void;
  onSubmit(): void;
  oneTimeCodeAutofill: boolean;
  returnKeyType: "done" | "next";
  value: string;
}) {
  return (
    <View style={styles.field}>
      <Text style={styles.label}>{label}</Text>
      <TextInput
        accessibilityHint={accessibilityHint}
        accessibilityLabel={label}
        autoComplete={oneTimeCodeAutofill ? "one-time-code" : "off"}
        editable={editable}
        enablesReturnKeyAutomatically
        keyboardType="number-pad"
        maxLength={6}
        onChangeText={(nextValue) => onChange(nextValue.replace(/\D/g, ""))}
        onSubmitEditing={onSubmit}
        placeholder="000000"
        ref={inputRef}
        returnKeyType={returnKeyType}
        style={styles.input}
        textContentType={oneTimeCodeAutofill ? "oneTimeCode" : "none"}
        value={value}
      />
    </View>
  );
}

function Loading() {
  return <View style={styles.loading}><ActivityIndicator color={colors.primary} /></View>;
}

const styles = StyleSheet.create({
  loading: { flex: 1, alignItems: "center", justifyContent: "center", backgroundColor: colors.background },
  title: { color: colors.text, fontSize: 28, fontWeight: "800" },
  description: { color: colors.mutedText, fontSize: 16, lineHeight: 24 },
  field: { gap: spacing.sm },
  label: { color: colors.text, fontSize: 14, fontWeight: "700" },
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
