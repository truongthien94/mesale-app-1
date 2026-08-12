import { createContext, useCallback, useContext, useEffect, useMemo, useRef, useState, type PropsWithChildren } from "react";
import { useQueryClient } from "@tanstack/react-query";
import {
  login as loginRequest,
  logout as logoutRequest,
  register as registerRequest,
  resendEmailVerification as resendEmailVerificationRequest,
  resendTwoFactorOtp as resendTwoFactorOtpRequest,
  verifyEmail as verifyEmailRequest,
  verifyTwoFactor as verifyTwoFactorRequest,
  type AuthContinuation,
  type RegisterPayload,
  type TwoFactorCodes,
  type User
} from "@/api/auth";
import { parseUser, type AuthenticatedAuthResult, type LoginResult } from "@/api/authContract";
import { clearAppQueryCache } from "@/api/queryClient";
import { clearSession, clearSessionIfTokenMatches, loadAuthState, onSessionInvalidated, saveAuthState, type Session } from "@/auth/session";
import { signInWithAppleNative, signInWithGoogleNative } from "@/features/auth/nativeOAuth";
import { accountDetailQueryOptions } from "@/features/account/query";

type AuthContextValue = {
  isLoading: boolean;
  pendingAuth: AuthContinuation | null;
  session: Session | null;
  user: User | null;
  login(email: string, password: string): Promise<void>;
  loginWithGoogle(): Promise<void>;
  loginWithApple(): Promise<void>;
  register(payload: RegisterPayload): Promise<void>;
  verifyEmail(otpCode: string): Promise<void>;
  resendEmailVerification(): Promise<void>;
  verifyTwoFactor(codes: TwoFactorCodes): Promise<void>;
  resendTwoFactorOtp(): Promise<void>;
  cancelAuthContinuation(): void;
  refreshUser(): Promise<void>;
  settleReferralPrompt(): Promise<void>;
  completeAccountDeletion(): Promise<void>;
  logout(): Promise<void>;
};

const AuthContext = createContext<AuthContextValue | null>(null);

export function AuthProvider({ children }: PropsWithChildren) {
  const queryClient = useQueryClient();
  const [isLoading, setLoading] = useState(true);
  const [pendingAuth, setPendingAuth] = useState<AuthContinuation | null>(null);
  const [session, setSession] = useState<Session | null>(null);
  const [user, setUser] = useState<User | null>(null);
  const authRevision = useRef(0);

  useEffect(() => {
    let isActive = true;
    const restoreRevision = authRevision.current;

    async function restoreSession() {
      let hasStoredPreview = false;
      try {
        const saved = await loadAuthState();
        if (!saved) return;

        if (!isActive || authRevision.current !== restoreRevision) return;
        setSession(saved.session);
        if (saved.userPreview) {
          hasStoredPreview = true;
          setUser(saved.userPreview);
          setLoading(false);
        }

        const restoredUser = parseUser(await queryClient.fetchQuery({ ...accountDetailQueryOptions(), staleTime: 0 }));
        if (!isActive || authRevision.current !== restoreRevision) return;
        if (saved.userPreview && restoredUser.id !== saved.userPreview.id) {
          const cleared = await clearSessionIfTokenMatches(saved.session.accessToken);
          if (cleared && isActive && authRevision.current === restoreRevision) {
            clearAppQueryCache(queryClient);
            setSession(null);
            setUser(null);
          }
          return;
        }
        await saveAuthState(saved.session, restoredUser);
        if (!isActive || authRevision.current !== restoreRevision) {
          await clearSessionIfTokenMatches(saved.session.accessToken);
          return;
        }
        setUser(restoredUser);
      } catch {
        // A 401 is cleared and broadcast by the API client. Other failures keep
        // the valid session available while account data is temporarily unavailable.
        if (!isActive || authRevision.current !== restoreRevision) return;
        if (!hasStoredPreview) setUser(null);
      } finally {
        if (isActive && authRevision.current === restoreRevision) setLoading(false);
      }
    }

    void restoreSession();
    return () => {
      isActive = false;
    };
  }, [queryClient]);

  useEffect(() => onSessionInvalidated(() => {
    clearAppQueryCache(queryClient);
    authRevision.current += 1;
    setPendingAuth(null);
    setSession(null);
    setUser(null);
    setLoading(false);
  }), [queryClient]);

  const acceptAuthenticated = useCallback(async (result: AuthenticatedAuthResult, revision: number) => {
    if (authRevision.current !== revision) return;
    clearAppQueryCache(queryClient);
    await saveAuthState(result.session, result.user);
    if (authRevision.current !== revision) {
      await clearSessionIfTokenMatches(result.session.accessToken);
      return;
    }
    setPendingAuth(null);
    setSession(result.session);
    setUser(result.user);
    setLoading(false);
  }, [queryClient]);

  const acceptLoginResult = useCallback(async (result: LoginResult, revision: number) => {
    if (authRevision.current !== revision) return;
    if (result.kind === "authenticated") {
      await acceptAuthenticated(result, revision);
      return;
    }
    setPendingAuth(result);
    setLoading(false);
  }, [acceptAuthenticated]);

  const login = useCallback(async (email: string, password: string) => {
    const revision = ++authRevision.current;
    setPendingAuth(null);
    const result = await loginRequest(email, password);
    await acceptLoginResult(result, revision);
  }, [acceptLoginResult]);

  const loginWithGoogle = useCallback(async () => {
    const revision = ++authRevision.current;
    setPendingAuth(null);
    await acceptLoginResult(await signInWithGoogleNative(), revision);
  }, [acceptLoginResult]);

  const loginWithApple = useCallback(async () => {
    const revision = ++authRevision.current;
    setPendingAuth(null);
    await acceptLoginResult(await signInWithAppleNative(), revision);
  }, [acceptLoginResult]);

  const register = useCallback(async (payload: RegisterPayload) => {
    const revision = ++authRevision.current;
    setPendingAuth(null);
    const result = await registerRequest(payload);
    await acceptLoginResult(result, revision);
  }, [acceptLoginResult]);

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

  const refreshUser = useCallback(async () => {
    const revision = authRevision.current;
    const activeSession = session;
    const activeUserId = user?.id;
    if (!activeSession) return;
    const refreshedUser = parseUser(await queryClient.fetchQuery({ ...accountDetailQueryOptions(), staleTime: 0 }));
    if (authRevision.current !== revision) return;
    if (activeUserId !== undefined && refreshedUser.id !== activeUserId) {
      if (await clearSessionIfTokenMatches(activeSession.accessToken)) {
        clearAppQueryCache(queryClient);
        authRevision.current += 1;
        setPendingAuth(null);
        setSession(null);
        setUser(null);
      }
      return;
    }
    await saveAuthState(activeSession, refreshedUser);
    if (authRevision.current !== revision) {
      await clearSessionIfTokenMatches(activeSession.accessToken);
      return;
    }
    setUser(refreshedUser);
  }, [queryClient, session, user?.id]);

  const settleReferralPrompt = useCallback(async () => {
    const revision = authRevision.current;
    const settledUser = user ? { ...user, referralPromptPending: false } : null;
    if (settledUser && session) {
      setUser(settledUser);
    }
    try {
      if (settledUser && session) {
        await saveAuthState(session, settledUser);
        if (authRevision.current !== revision) return;
      }
      await refreshUser();
    } catch {
      // The successful decision response is authoritative; a later session
      // restore will retry the account refresh if this request is offline.
    }
  }, [refreshUser, session, user]);

  const completeAccountDeletion = useCallback(async () => {
    authRevision.current += 1;
    clearAppQueryCache(queryClient);
    await clearSession();
    setPendingAuth(null);
    setSession(null);
    setUser(null);
    setLoading(false);
  }, [queryClient]);

  const logout = useCallback(async () => {
    const logoutRevision = ++authRevision.current;
    clearAppQueryCache(queryClient);
    try {
      if (session) await logoutRequest();
    } finally {
      if (authRevision.current === logoutRevision) {
        try {
          clearAppQueryCache(queryClient);
          await clearSession();
        } finally {
          setPendingAuth(null);
          setSession(null);
          setUser(null);
          setLoading(false);
        }
      }
    }
  }, [queryClient, session]);

  const value = useMemo(() => ({
    isLoading,
    pendingAuth,
    session,
    user,
    login,
    loginWithGoogle,
    loginWithApple,
    register,
    verifyEmail,
    resendEmailVerification,
    verifyTwoFactor,
    resendTwoFactorOtp,
    cancelAuthContinuation,
    refreshUser,
    settleReferralPrompt,
    completeAccountDeletion,
    logout
  }), [
    isLoading,
    pendingAuth,
    session,
    user,
    login,
    loginWithGoogle,
    loginWithApple,
    register,
    verifyEmail,
    resendEmailVerification,
    verifyTwoFactor,
    resendTwoFactorOtp,
    cancelAuthContinuation,
    refreshUser,
    settleReferralPrompt,
    completeAccountDeletion,
    logout
  ]);
  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}

export function useAuth(): AuthContextValue {
  const context = useContext(AuthContext);
  if (!context) throw new Error("useAuth must be used inside AuthProvider");
  return context;
}
