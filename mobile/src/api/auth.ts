import { request } from "@/api/client";
import {
  parseLoginResult,
  requireAuthenticated,
  type AuthenticatedAuthResult,
  type LoginResult,
  type User
} from "@/api/authContract";

export type { AuthContinuation, LoginResult, TwoFactorMethod, User } from "@/api/authContract";

const mobileDeviceName = "Mesale Mobile";

export async function login(email: string, password: string): Promise<LoginResult> {
  const response = await request<unknown>("auth/login", {
    method: "POST",
    authenticated: false,
    body: { email, password, device_name: mobileDeviceName }
  });
  return parseLoginResult(response);
}

export async function verifyEmail(email: string, otpCode: string): Promise<AuthenticatedAuthResult> {
  const response = await request<unknown>("auth/verify-email", {
    method: "POST",
    authenticated: false,
    body: { email, otp_code: otpCode, device_name: mobileDeviceName }
  });
  return requireAuthenticated(parseLoginResult(response));
}

export async function resendEmailVerification(email: string): Promise<void> {
  await request<unknown>("auth/verify-email/resend", {
    method: "POST",
    authenticated: false,
    body: { email }
  });
}

export type TwoFactorCodes = {
  google2faCode?: string;
  emailOtpCode?: string;
};

export async function verifyTwoFactor(challengeToken: string, codes: TwoFactorCodes): Promise<AuthenticatedAuthResult> {
  const response = await request<unknown>("auth/login/2fa", {
    method: "POST",
    authenticated: false,
    body: {
      challenge_token: challengeToken,
      google2fa_code: codes.google2faCode,
      email_otp_code: codes.emailOtpCode,
      device_name: mobileDeviceName
    }
  });
  return requireAuthenticated(parseLoginResult(response));
}

export async function resendTwoFactorOtp(challengeToken: string): Promise<void> {
  await request<unknown>("auth/login/2fa/resend", {
    method: "POST",
    authenticated: false,
    body: { challenge_token: challengeToken }
  });
}

export async function getCurrentUser(): Promise<User> {
  return request<User>("account");
}

export async function logout(): Promise<void> {
  await request<void>("auth/logout", { method: "POST" });
}
