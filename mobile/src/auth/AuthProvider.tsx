import { createContext, useCallback, useContext, useEffect, useMemo, useRef, useState, type PropsWithChildren } from "react";
import {
  getCurrentUser,
  login as loginRequest,
  logout as logoutRequest,
  resendEmailVerification as resendEmailVerificationRequest,
  resendTwoFactorOtp as resendTwoFactorOtpRequest,
  verifyEmail as verifyEmailRequest,
  verifyTwoFactor as verifyTwoFactorRequest,
  type AuthContinuation,
  type TwoFactorCodes,
  type User
} from "@/api/auth";
import type { AuthenticatedAuthResult } from "@/api/authContract";
import { clearSession, clearSessionIfTokenMatches, loadSession, onSessionInvalidated, saveSession, type Session } from "@/auth/session";

type AuthContextValue = {
  isLoading: boolean;
  pendingAuth: AuthContinuation | null;
  session: Session | null;
  user: User | null;
  login(email: string, password: string): Promise<void>;
  verifyEmail(otpCode: string): Promise<void>;
  resendEmailVerification(): Promise<void>;
  verifyTwoFactor(codes: TwoFactorCodes): Promise<void>;
  resendTwoFactorOtp(): Promise<void>;
  cancelAuthContinuation(): void;
  logout(): Promise<void>;
};

const AuthContext = createContext<AuthContextValue | null>(null);

export function AuthProvider({ children }: PropsWithChildren) {
  const [isLoading, setLoading] = useState(true);
  const [pendingAuth, setPendingAuth] = useState<AuthContinuation | null>(null);
  const [session, setSession] = useState<Session | null>(null);
  const [user, setUser] = useState<User | null>(null);
  const authRevision = useRef(0);

  useEffect(() => {
    let isActive = true;
    const restoreRevision = authRevision.current;

    async function restoreSession() {
      try {
        const saved = await loadSession();
        if (!saved) return;

        if (!isActive || authRevision.current !== restoreRevision) return;
        setSession(saved);

        const restoredUser = await getCurrentUser();
        if (!isActive || authRevision.current !== restoreRevision) return;
        setUser(restoredUser);
      } catch {
        // A 401 is cleared and broadcast by the API client. Other failures keep
        // the valid session available while account data is temporarily unavailable.
        if (!isActive || authRevision.current !== restoreRevision) return;
        setUser(null);
      } finally {
        if (isActive) setLoading(false);
      }
    }

    void restoreSession();
    return () => {
      isActive = false;
    };
  }, []);

  useEffect(() => onSessionInvalidated(() => {
    authRevision.current += 1;
    setPendingAuth(null);
    setSession(null);
    setUser(null);
  }), []);

  const acceptAuthenticated = useCallback(async (result: AuthenticatedAuthResult, revision: number) => {
    if (authRevision.current !== revision) return;
    await saveSession(result.session);
    if (authRevision.current !== revision) {
      await clearSessionIfTokenMatches(result.session.accessToken);
      return;
    }
    setPendingAuth(null);
    setSession(result.session);
    setUser(result.user);
    setLoading(false);
  }, []);

  const login = useCallback(async (email: string, password: string) => {
    const revision = ++authRevision.current;
    setPendingAuth(null);
    const result = await loginRequest(email, password);
    if (authRevision.current !== revision) return;
    if (result.kind === "authenticated") {
      await acceptAuthenticated(result, revision);
      return;
    }
    setPendingAuth(result);
    setLoading(false);
  }, [acceptAuthenticated]);

  const verifyEmail = useCallback(async (otpCode: string) => {
    if (pendingAuth?.kind !== "email-verification") throw new Error("Email verification is not pending.");
    const revision = ++authRevision.current;
    const result = await verifyEmailRequest(pendingAuth.email, otpCode);
    await acceptAuthenticated(result, revision);
  }, [acceptAuthenticated, pendingAuth]);

  const resendEmailVerification = useCallback(async () => {
    if (pendingAuth?.kind !== "email-verification") throw new Error("Email verification is not pending.");
    await resendEmailVerificationRequest(pendingAuth.email);
  }, [pendingAuth]);

  const verifyTwoFactor = useCallback(async (codes: TwoFactorCodes) => {
    if (pendingAuth?.kind !== "two-factor") throw new Error("Two-factor authentication is not pending.");
    const revision = ++authRevision.current;
    const result = await verifyTwoFactorRequest(pendingAuth.challengeToken, codes);
    await acceptAuthenticated(result, revision);
  }, [acceptAuthenticated, pendingAuth]);

  const resendTwoFactorOtp = useCallback(async () => {
    if (pendingAuth?.kind !== "two-factor" || !pendingAuth.methods.includes("email_otp")) {
      throw new Error("Email OTP is not enabled for this challenge.");
    }
    await resendTwoFactorOtpRequest(pendingAuth.challengeToken);
  }, [pendingAuth]);

  const cancelAuthContinuation = useCallback(() => {
    authRevision.current += 1;
    setPendingAuth(null);
  }, []);

  const logout = useCallback(async () => {
    const logoutRevision = ++authRevision.current;
    try {
      if (session) await logoutRequest();
    } finally {
      if (authRevision.current === logoutRevision) {
        try {
          await clearSession();
        } finally {
          setPendingAuth(null);
          setSession(null);
          setUser(null);
          setLoading(false);
        }
      }
    }
  }, [session]);

  const value = useMemo(() => ({
    isLoading,
    pendingAuth,
    session,
    user,
    login,
    verifyEmail,
    resendEmailVerification,
    verifyTwoFactor,
    resendTwoFactorOtp,
    cancelAuthContinuation,
    logout
  }), [
    isLoading,
    pendingAuth,
    session,
    user,
    login,
    verifyEmail,
    resendEmailVerification,
    verifyTwoFactor,
    resendTwoFactorOtp,
    cancelAuthContinuation,
    logout
  ]);
  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}

export function useAuth(): AuthContextValue {
  const context = useContext(AuthContext);
  if (!context) throw new Error("useAuth must be used inside AuthProvider");
  return context;
}
