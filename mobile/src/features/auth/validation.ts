export type AuthFormErrors = Record<string, string>;

export function validateRegistration(input: {
  email: string;
  password: string;
  passwordConfirmation: string;
  acceptedTerms: boolean;
}): AuthFormErrors {
  const errors: AuthFormErrors = {};
  if (!input.email.trim() || !input.email.includes("@")) errors.email = "Vui lòng nhập email hợp lệ.";
  if (input.password.length < 8) errors.password = "Mật khẩu phải có ít nhất 8 ký tự.";
  if (input.password !== input.passwordConfirmation) errors.password_confirmation = "Mật khẩu xác nhận không khớp.";
  if (!input.acceptedTerms) errors.terms = "Bạn cần đồng ý với điều khoản và chính sách bảo mật.";
  return errors;
}

export function validatePasswordReset(input: {
  token: string;
  email: string;
  password: string;
  passwordConfirmation: string;
}): AuthFormErrors {
  const errors: AuthFormErrors = {};
  if (!input.token.trim()) errors.token = "Vui lòng nhập mã đặt lại mật khẩu.";
  if (!input.email.trim() || !input.email.includes("@")) errors.email = "Vui lòng nhập email hợp lệ.";
  if (input.password.length < 8) errors.password = "Mật khẩu phải có ít nhất 8 ký tự.";
  if (input.password !== input.passwordConfirmation) errors.password_confirmation = "Mật khẩu xác nhận không khớp.";
  return errors;
}
