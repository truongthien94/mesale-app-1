import { useState } from "react";
import { Pressable, StyleSheet, Text, View } from "react-native";
import { Redirect } from "expo-router";
import { ApiError } from "@/api/client";
import { useAuth } from "@/auth/AuthProvider";
import { FormErrorSummary } from "@/components/FormErrorSummary";
import { AuthButton, AuthField, AuthForm, AuthLink } from "@/features/auth/components";
import { LegalLinks } from "@/features/legal/LegalLinks";
import { validateRegistration } from "@/features/auth/validation";
import { colors, spacing } from "@/theme/tokens";

export default function RegisterScreen() {
  const { pendingAuth, register, session } = useAuth();
  const [name, setName] = useState("");
  const [email, setEmail] = useState("");
  const [phone, setPhone] = useState("");
  const [referralCode, setReferralCode] = useState("");
  const [password, setPassword] = useState("");
  const [passwordConfirmation, setPasswordConfirmation] = useState("");
  const [acceptedTerms, setAcceptedTerms] = useState(false);
  const [errors, setErrors] = useState<Record<string, string>>({});
  const [requestError, setRequestError] = useState<ApiError | null>(null);
  const [submitting, setSubmitting] = useState(false);

  if (session) return <Redirect href="/home" />;
  if (pendingAuth?.kind === "email-verification") return <Redirect href="/verify-email" />;
  if (pendingAuth?.kind === "two-factor") return <Redirect href="/two-factor" />;

  async function submit() {
    const nextErrors = validateRegistration({ email, password, passwordConfirmation, acceptedTerms });
    setErrors(nextErrors);
    setRequestError(null);
    if (Object.keys(nextErrors).length > 0) return;

    setSubmitting(true);
    try {
      await register({ name, email, phone, password, passwordConfirmation, referralCode });
    } catch (reason) {
      setRequestError(reason instanceof ApiError ? reason : new ApiError(reason instanceof Error ? reason.message : "Không thể đăng ký.", 0));
    } finally {
      setSubmitting(false);
    }
  }

  return (
    <AuthForm title="Đăng ký tài khoản" subtitle="Gia nhập Mesale để nhận hoàn tiền và quản lý phần thưởng trên cùng tài khoản website.">
      <AuthField label="Họ và tên (tùy chọn)" onChangeText={setName} placeholder="Nguyễn Văn A" value={name} />
      <AuthField autoCapitalize="none" autoComplete="email" error={errors.email} keyboardType="email-address" label="Địa chỉ email" onChangeText={setEmail} placeholder="email-cua-ban@gmail.com" value={email} />
      <AuthField autoCapitalize="none" autoComplete="tel" keyboardType="phone-pad" label="Số điện thoại (tùy chọn)" onChangeText={setPhone} placeholder="0987654321" value={phone} />
      <AuthField autoCapitalize="none" label="Mã giới thiệu (tùy chọn)" onChangeText={setReferralCode} placeholder="REFXXXXXX" value={referralCode} />
      <AuthField autoCapitalize="none" autoComplete="new-password" error={errors.password} label="Mật khẩu" onChangeText={setPassword} placeholder="Tối thiểu 8 ký tự" secureTextEntry value={password} />
      <AuthField autoCapitalize="none" autoComplete="new-password" error={errors.password_confirmation} label="Xác nhận mật khẩu" onChangeText={setPasswordConfirmation} placeholder="Nhập lại mật khẩu" secureTextEntry value={passwordConfirmation} />
      <Pressable accessibilityRole="checkbox" accessibilityState={{ checked: acceptedTerms }} onPress={() => setAcceptedTerms((value) => !value)} style={styles.termsRow}>
        <View style={[styles.checkbox, acceptedTerms && styles.checkboxChecked]}><Text style={styles.checkmark}>{acceptedTerms ? "✓" : ""}</Text></View>
        <Text style={styles.termsText}>Tôi đồng ý với Điều khoản dịch vụ và Chính sách bảo mật của Mesale.</Text>
      </Pressable>
      {errors.terms ? <Text accessibilityRole="alert" style={styles.error}>{errors.terms}</Text> : null}
      <FormErrorSummary errors={requestError?.errors} message={requestError?.message} />
      <AuthButton label="Đăng ký tài khoản" loading={submitting} onPress={() => void submit()} />
      <AuthLink href="/login">Đã có tài khoản? Đăng nhập</AuthLink>
      <LegalLinks />
    </AuthForm>
  );
}

const styles = StyleSheet.create({
  termsRow: { alignItems: "flex-start", flexDirection: "row", gap: spacing.sm },
  checkbox: { alignItems: "center", borderColor: colors.border, borderRadius: 5, borderWidth: 1, height: 22, justifyContent: "center", width: 22 },
  checkboxChecked: { backgroundColor: colors.primary, borderColor: colors.primary },
  checkmark: { color: colors.surface, fontSize: 14, fontWeight: "900" },
  termsText: { color: colors.mutedText, flex: 1, fontSize: 12, lineHeight: 18 },
  error: { color: colors.danger, fontSize: 12 }
});
