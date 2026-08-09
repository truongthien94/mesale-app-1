import { useState } from "react";
import { ApiError } from "@/api/client";
import { forgotPassword } from "@/api/auth";
import { FormErrorSummary } from "@/components/FormErrorSummary";
import { AuthButton, AuthField, AuthForm, AuthLink, AuthNotice } from "@/features/auth/components";

export default function ForgotPasswordScreen() {
  const [email, setEmail] = useState("");
  const [error, setError] = useState<ApiError | null>(null);
  const [message, setMessage] = useState<string | null>(null);
  const [submitting, setSubmitting] = useState(false);

  async function submit() {
    setError(null);
    setMessage(null);
    if (!email.trim() || !email.includes("@")) {
      setError(new ApiError("Vui lòng nhập email hợp lệ.", 422, { errors: { email: ["Vui lòng nhập email hợp lệ."] } }));
      return;
    }
    setSubmitting(true);
    try {
      const response = await forgotPassword(email);
      setMessage(response.message ?? "Nếu email tồn tại, liên kết đặt lại mật khẩu đã được gửi.");
    } catch (reason) {
      setError(reason instanceof ApiError ? reason : new ApiError("Không thể gửi yêu cầu đặt lại mật khẩu.", 0));
    } finally {
      setSubmitting(false);
    }
  }

  return (
    <AuthForm title="Quên mật khẩu" subtitle="Nhập email tài khoản. Hệ thống sẽ gửi liên kết đặt lại nếu email tồn tại.">
      <AuthField autoCapitalize="none" autoComplete="email" error={error?.errors?.email?.[0]} keyboardType="email-address" label="Địa chỉ email" onChangeText={setEmail} placeholder="email-cua-ban@gmail.com" value={email} />
      <FormErrorSummary errors={error?.errors} message={error?.message} />
      <AuthNotice message={message} tone="success" />
      <AuthButton label="Gửi liên kết đặt lại" loading={submitting} onPress={() => void submit()} />
      {message ? <AuthLink href="/reset-password">Tôi đã có mã đặt lại</AuthLink> : null}
      <AuthLink href="/login">Quay lại đăng nhập</AuthLink>
    </AuthForm>
  );
}
