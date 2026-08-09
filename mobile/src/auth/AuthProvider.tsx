import { createContext, useCallback, useContext, useEffect, useMemo, useRef, useState, type PropsWithChildren } from "react";
import { getCurrentUser, login as loginRequest, logout as logoutRequest, type User } from "@/api/auth";
import { clearSession, loadSession, onSessionInvalidated, saveSession, type Session } from "@/auth/session";

type AuthContextValue = {
  isLoading: boolean;
  session: Session | null;
  user: User | null;
  login(email: string, password: string): Promise<void>;
  logout(): Promise<void>;
};

const AuthContext = createContext<AuthContextValue | null>(null);

export function AuthProvider({ children }: PropsWithChildren) {
  const [isLoading, setLoading] = useState(true);
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
    setSession(null);
    setUser(null);
  }), []);

  const login = useCallback(async (email: string, password: string) => {
    authRevision.current += 1;
    const result = await loginRequest(email, password);
    await saveSession(result.session);
    setSession(result.session);
    setUser(result.user);
    setLoading(false);
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
          setSession(null);
          setUser(null);
          setLoading(false);
        }
      }
    }
  }, [session]);

  const value = useMemo(() => ({ isLoading, session, user, login, logout }), [isLoading, session, user, login, logout]);
  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}

export function useAuth(): AuthContextValue {
  const context = useContext(AuthContext);
  if (!context) throw new Error("useAuth must be used inside AuthProvider");
  return context;
}
