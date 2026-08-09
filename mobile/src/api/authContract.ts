import type { Session } from "@/auth/session";

export type User = {
  id: number;
  name?: string | null;
  email?: string | null;
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

function parseUser(value: unknown): User {
  if (!isRecord(value) || typeof value.id !== "number" || !Number.isSafeInteger(value.id) || value.id <= 0) {
    throw new AuthContractError("The authentication response did not include a valid user.");
  }
  if (value.name !== undefined && value.name !== null && typeof value.name !== "string") {
    throw new AuthContractError("The authentication response included an invalid user name.");
  }
  if (value.email !== undefined && value.email !== null && typeof value.email !== "string") {
    throw new AuthContractError("The authentication response included an invalid user email.");
  }
  return {
    id: value.id,
    name: value.name as string | null | undefined,
    email: value.email as string | null | undefined
  };
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
