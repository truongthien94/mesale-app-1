import { useState } from "react";
import { useLocalSearchParams } from "expo-router";
import { ApiError } from "@/api/client";
import { resetPassword } from "@/api/auth";
import { FormErrorSummary } from "@/components/FormErrorSummary";
import { AuthButton, AuthField, AuthForm, AuthLink, AuthNotice } from "@/features/auth/components";
import { validatePasswordReset } from "@/features/auth/validation";

function param(value: string | string[] | undefined): string {
  return Array.isArray(value) ? value[0] ?? "" : value ?? "";
}

export default function ResetPasswordScreen() {
  const params = useLocalSearchParams<{ token?: string | string[]; email?: string | string[] }>();
  const [token, setToken] = useState(() => param(params.token));
  const [email, setEmail] = useState(() => param(params.email));
  const [password, setPassword] = useState("");
  const [passwordConfirmation, setPasswordConfirmation] = useState("");
  const [fieldErrors, setFieldErrors] = useState<Record<string, string>>({});
  const [error, setError] = useState<ApiError | null>(null);
  const [message, setMessage] = useState<string | null>(null);
  const [submitting, setSubmitting] = useState(false);

  async function submit() {
    const nextErrors = validatePasswordReset({ token, email, password, passwordConfirmation });
    setFieldErrors(nextErrors);
    setError(null);
    setMessage(null);
    if (Object.keys(nextErrors).length > 0) return;
    setSubmitting(true);
    try {
      const response = await resetPassword({ token, email, password, passwordConfirmation });
      setMessage(response.message ?? "Mật khẩu đã được cập nhật. Vui lòng đăng nhập lại.");
      setPassword("");
      setPasswordConfirmation("");
    } catch (reason) {
      setError(reason instanceof ApiError ? reason : new ApiError("Không thể đặt lại mật khẩu.", 0));
    } finally {
      setSubmitting(false);
    }
  }

  return (
    <AuthForm title="Đặt lại mật khẩu" subtitle="Mã đặt lại và email nằm trong liên kết hệ thống đã gửi cho bạn.">
      <AuthField autoCapitalize="none" error={fieldErrors.token ?? error?.errors?.token?.[0]} label="Mã đặt lại mật khẩu" onChangeText={setToken} placeholder="Nhập mã trong liên kết email" value={token} />
      <AuthField autoCapitalize="none" autoComplete="email" error={fieldErrors.email ?? error?.errors?.email?.[0]} keyboardType="email-address" label="Địa chỉ email" onChangeText={setEmail} value={email} />
      <AuthField autoCapitalize="none" autoComplete="new-password" error={fieldErrors.password ?? error?.errors?.password?.[0]} label="Mật khẩu mới" onChangeText={setPassword} placeholder="Tối thiểu 8 ký tự" secureTextEntry value={password} />
      <AuthField autoCapitalize="none" autoComplete="new-password" error={fieldErrors.password_confirmation} label="Xác nhận mật khẩu mới" onChangeText={setPasswordConfirmation} secureTextEntry value={passwordConfirmation} />
      <FormErrorSummary errors={error?.errors} message={error?.message} />
      <AuthNotice message={message} tone="success" />
      <AuthButton disabled={Boolean(message)} label="Cập nhật mật khẩu" loading={submitting} onPress={() => void submit()} />
      <AuthLink href="/login">Quay lại đăng nhập</AuthLink>
    </AuthForm>
  );
}
