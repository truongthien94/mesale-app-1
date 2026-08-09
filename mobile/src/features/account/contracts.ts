export const accountPaths = {
  account: "account",
  profile: "account/profile",
  password: "account/password",
  preferences: "account/preferences",
  delete: "account/delete",
  security: "security",
  twoFactorSetup: "security/2fa/setup",
  twoFactorEnable: "security/2fa/enable",
  twoFactorDisable: "security/2fa/disable",
  emailOtpSend: "security/email-otp/send",
  emailOtpEnable: "security/email-otp/enable",
  emailOtpDisable: "security/email-otp/disable",
  sessions: "sessions",
  revokeOthers: "sessions/revoke-others"
} as const;

export function sessionRevokePath(id: number): string {
  if (!Number.isInteger(id) || id <= 0) throw new Error("A positive session ID is required.");
  return `sessions/${id}/revoke`;
}

export function normalizePreferences(input: { locale?: string; currency?: string }) {
  return {
    ...(input.locale === undefined ? {} : { locale: input.locale.trim().toLowerCase() }),
    ...(input.currency === undefined ? {} : { currency: input.currency.trim().toUpperCase() })
  };
}
