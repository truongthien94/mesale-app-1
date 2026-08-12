import type { Session } from "@/auth/session";

export type User = {
  id: number;
  name?: string | null;
  email?: string | null;
  financialSnapshot?: {
    balance: number;
    totalCashback: number;
    totalReferralEarned: number;
    totalWithdrawn: number;
  };
  referralPromptPending: boolean;
  referralCodeEligible?: boolean;
  referralCodeExpiresAt?: string | null;
  phone?: string | null;
  avatar?: string | null;
  referral_code?: string | null;
  status?: string | null;
  email_verified?: boolean;
  preferences?: {
    locale: string;
    currency: string;
  };
  wallet?: {
    balance: number;
    total_cashback: number;
    total_referral_earned: number;
    total_withdrawn: number;
    currency: string;
  };
  stats?: {
    orders_total: number;
    orders_pending: number;
    orders_approved: number;
    orders_rejected: number;
    referrals_count: number;
    withdrawals_pending: number;
  };
  created_at?: string | null;
};

export type AuthenticatedAuthResult = {
  kind: "authenticated";
  session: Session;
  user: User;
};

export type EmailVerificationRequired = {
  kind: "email-verification";
  email: string;
};

export type TwoFactorMethod = "google2fa" | "email_otp";

export type TwoFactorRequired = {
  kind: "two-factor";
  challengeToken: string;
  methods: TwoFactorMethod[];
};

export type LoginResult = AuthenticatedAuthResult | EmailVerificationRequired | TwoFactorRequired;
export type AuthContinuation = EmailVerificationRequired | TwoFactorRequired;

export type StoredUserPreview = {
  id: number;
  name?: string | null;
  email?: string | null;
  avatar?: string | null;
  referral_code?: string | null;
  referral_prompt_pending: boolean;
  referral_code_eligible?: boolean;
  referral_code_expires_at?: string | null;
  preferences?: {
    locale: string;
    currency: string;
  };
  balance?: number;
  total_cashback?: number;
  total_referral_earned?: number;
  total_withdrawn?: number;
};

export class AuthContractError extends Error {
  constructor(message: string) {
    super(message);
    this.name = "AuthContractError";
  }
}

function isRecord(value: unknown): value is Record<string, unknown> {
  return value !== null && typeof value === "object" && !Array.isArray(value);
}

function nonEmptyString(value: unknown): string | null {
  return typeof value === "string" && value.trim().length > 0 ? value : null;
}

function parseFinancialFields(value: Record<string, unknown>): User["financialSnapshot"] {
  const fields = ["balance", "total_cashback", "total_referral_earned", "total_withdrawn"] as const;
  const presentFields = fields.filter((field) => value[field] !== undefined);
  if (presentFields.length === 0) return undefined;
  if (presentFields.length !== fields.length) {
    throw new AuthContractError("The authentication response included an incomplete financial snapshot.");
  }

  for (const field of fields) {
    if (typeof value[field] !== "number" || !Number.isSafeInteger(value[field])) {
      throw new AuthContractError("The authentication response included an invalid financial snapshot.");
    }
  }

  return {
    balance: value.balance as number,
    totalCashback: value.total_cashback as number,
    totalReferralEarned: value.total_referral_earned as number,
    totalWithdrawn: value.total_withdrawn as number
  };
}

function parseFinancialSnapshot(value: Record<string, unknown>): User["financialSnapshot"] {
  const flatSnapshot = parseFinancialFields(value);
  if (flatSnapshot) return flatSnapshot;
  if (value.wallet === undefined) return undefined;
  if (!isRecord(value.wallet)) {
    throw new AuthContractError("The account response included an invalid wallet snapshot.");
  }
  return parseFinancialFields(value.wallet);
}

export function parseUser(value: unknown): User {
  if (!isRecord(value) || typeof value.id !== "number" || !Number.isSafeInteger(value.id) || value.id <= 0) {
    throw new AuthContractError("The authentication response did not include a valid user.");
  }
  if (value.name !== undefined && value.name !== null && typeof value.name !== "string") {
    throw new AuthContractError("The authentication response included an invalid user name.");
  }
  if (value.email !== undefined && value.email !== null && typeof value.email !== "string") {
    throw new AuthContractError("The authentication response included an invalid user email.");
  }
  const user: User = {
    id: value.id,
    name: value.name as string | null | undefined,
    email: value.email as string | null | undefined,
    referralPromptPending: value.referral_prompt_pending === true
  };
  const financialSnapshot = parseFinancialSnapshot(value);
  if (financialSnapshot) user.financialSnapshot = financialSnapshot;
  if (typeof value.phone === "string" || value.phone === null) user.phone = value.phone;
  if (typeof value.avatar === "string" || value.avatar === null) user.avatar = value.avatar;
  if (typeof value.referral_code === "string" || value.referral_code === null) user.referral_code = value.referral_code;
  if (value.referral_code_eligible !== undefined) {
    if (typeof value.referral_code_eligible !== "boolean") {
      throw new AuthContractError("The authentication response included an invalid referral eligibility flag.");
    }
    user.referralCodeEligible = value.referral_code_eligible;
  }
  if (value.referral_code_expires_at !== undefined) {
    if (typeof value.referral_code_expires_at !== "string" && value.referral_code_expires_at !== null) {
      throw new AuthContractError("The authentication response included an invalid referral eligibility expiry.");
    }
    user.referralCodeExpiresAt = value.referral_code_expires_at;
  }
  if (typeof value.status === "string" || value.status === null) user.status = value.status;
  if (typeof value.email_verified === "boolean") user.email_verified = value.email_verified;
  if (value.preferences !== undefined) {
    if (!isRecord(value.preferences)) throw new AuthContractError("The authentication response included invalid account preferences.");
    const locale = value.preferences.locale;
    const currency = value.preferences.currency;
    if (typeof locale !== "string" || typeof currency !== "string") {
      throw new AuthContractError("The authentication response included invalid account preferences.");
    }
    user.preferences = { locale, currency };
  }
  if (typeof value.created_at === "string" || value.created_at === null) user.created_at = value.created_at;
  return user;
}

export function createStoredUserPreview(user: User): StoredUserPreview {
  const preview: StoredUserPreview = {
    id: user.id,
    name: user.name,
    email: user.email,
    avatar: user.avatar,
    referral_code: user.referral_code,
    referral_prompt_pending: user.referralPromptPending
  };

  if (user.referralCodeEligible !== undefined) preview.referral_code_eligible = user.referralCodeEligible;
  if (user.referralCodeExpiresAt !== undefined) preview.referral_code_expires_at = user.referralCodeExpiresAt;
  if (user.preferences) preview.preferences = user.preferences;
  if (user.financialSnapshot) {
    preview.balance = user.financialSnapshot.balance;
    preview.total_cashback = user.financialSnapshot.totalCashback;
    preview.total_referral_earned = user.financialSnapshot.totalReferralEarned;
    preview.total_withdrawn = user.financialSnapshot.totalWithdrawn;
  }

  return preview;
}

export function parseStoredUserPreview(value: unknown): User {
  if (!isRecord(value) || typeof value.referral_prompt_pending !== "boolean") {
    throw new AuthContractError("The stored authentication preview was invalid.");
  }
  return parseUser(value);
}

function parseExpiry(value: unknown, now: number): string | undefined {
  if (value === undefined || value === null) return undefined;
  const expiresAt = nonEmptyString(value);
  if (!expiresAt) throw new AuthContractError("The authentication response included an invalid token expiry.");
  const timestamp = Date.parse(expiresAt);
  if (!Number.isFinite(timestamp) || timestamp <= now) {
    throw new AuthContractError("The authentication response included an expired token.");
  }
  return expiresAt;
}

function parseAuthenticated(value: Record<string, unknown>, now: number): AuthenticatedAuthResult {
  const canonicalToken = nonEmptyString(value.access_token);
  const compatibilityToken = nonEmptyString(value.token);
  if (canonicalToken && compatibilityToken && canonicalToken !== compatibilityToken) {
    throw new AuthContractError("The authentication response included conflicting session tokens.");
  }
  const accessToken = canonicalToken ?? compatibilityToken;
  if (!accessToken || nonEmptyString(value.token_type)?.toLowerCase() !== "bearer") {
    throw new AuthContractError("The API did not return a session Bearer token.");
  }
  return {
    kind: "authenticated",
    session: {
      accessToken,
      tokenType: "Bearer",
      expiresAt: parseExpiry(value.expires_at, now)
    },
    user: parseUser(value.user)
  };
}

export function parseLoginResult(value: unknown, now = Date.now()): LoginResult {
  if (!isRecord(value)) throw new AuthContractError("The authentication response was not an object.");

  const emailVerificationRequired = value.email_verification_required === true;
  const twoFactorRequired = value.two_factor_required === true;
  if (emailVerificationRequired && twoFactorRequired) {
    throw new AuthContractError("The authentication response contained conflicting continuation states.");
  }

  if (emailVerificationRequired) {
    const email = nonEmptyString(value.email);
    if (!email) throw new AuthContractError("Email verification was requested without an email address.");
    return { kind: "email-verification", email };
  }

  if (twoFactorRequired) {
    const challengeToken = nonEmptyString(value.challenge_token);
    if (!challengeToken || !Array.isArray(value.methods) || value.methods.length === 0) {
      throw new AuthContractError("Two-factor authentication was requested without a valid challenge.");
    }
    const methods = [...new Set(value.methods)];
    if (methods.some((method) => method !== "google2fa" && method !== "email_otp")) {
      throw new AuthContractError("The API requested an unsupported two-factor method.");
    }
    return { kind: "two-factor", challengeToken, methods: methods as TwoFactorMethod[] };
  }

  return parseAuthenticated(value, now);
}

export function requireAuthenticated(result: LoginResult): AuthenticatedAuthResult {
  if (result.kind !== "authenticated") {
    throw new AuthContractError("The authentication continuation did not return a Bearer token.");
  }
  return result;
}
