import type { AuthContinuation } from "@/api/authContract";

export type AuthGateRoute = "/verify-email" | "/two-factor" | "/referral-code" | "/home";

export function resolveAuthGate(
  pendingAuth: AuthContinuation | null,
  hasSession: boolean,
  referralPromptPending: boolean
): AuthGateRoute | null {
  if (pendingAuth?.kind === "email-verification") return "/verify-email";
  if (pendingAuth?.kind === "two-factor") return "/two-factor";
  if (!hasSession) return null;
  if (referralPromptPending) return "/referral-code";
  return "/home";
}
