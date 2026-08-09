import { request } from "@/api/client";
import type { Session } from "@/auth/session";

export type User = {
  id: number;
  name?: string;
  email?: string;
};

type LoginResponse = {
  access_token?: string;
  token?: string;
  token_type?: string;
  expires_at?: string;
  user: User;
};

export type AuthResult = { session: Session; user: User };

export async function login(email: string, password: string): Promise<AuthResult> {
  const response = await request<LoginResponse>("auth/login", {
    method: "POST",
    authenticated: false,
    body: { email, password }
  });
  const accessToken = response.access_token ?? response.token;
  if (!accessToken || response.token_type?.toLowerCase() !== "bearer") {
    throw new Error("The API did not return a session Bearer token.");
  }
  return {
    session: { accessToken, tokenType: "Bearer", expiresAt: response.expires_at },
    user: response.user
  };
}

export async function logout(): Promise<void> {
  await request<void>("auth/logout", { method: "POST" });
}
