import { createContext, useCallback, useContext, useEffect, useMemo, useState, type PropsWithChildren } from "react";
import { login as loginRequest, logout as logoutRequest, type User } from "@/api/auth";
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

  useEffect(() => {
    void loadSession().then((saved) => {
      setSession(saved);
      setLoading(false);
    });
  }, []);

  useEffect(() => onSessionInvalidated(() => {
    setSession(null);
    setUser(null);
  }), []);

  const login = useCallback(async (email: string, password: string) => {
    const result = await loginRequest(email, password);
    await saveSession(result.session);
    setSession(result.session);
    setUser(result.user);
  }, []);

  const logout = useCallback(async () => {
    try {
      if (session) await logoutRequest();
    } finally {
      await clearSession();
      setSession(null);
      setUser(null);
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
