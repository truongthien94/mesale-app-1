import type { AuthContinuation } from "@/api/authContract";

export type AuthGateRoute = "/verify-email" | "/two-factor" | "/referral-code" | "/home";

export function resolveAuthGate(
  pendingAuth: AuthContinuation | null,
  hasSession: boolean,
  _referralPromptPending: boolean
): AuthGateRoute | null {
  if (pendingAuth?.kind === "email-verification") return "/verify-email";
  if (pendingAuth?.kind === "two-factor") return "/two-factor";
  if (!hasSession) return null;
  return "/home";
}
