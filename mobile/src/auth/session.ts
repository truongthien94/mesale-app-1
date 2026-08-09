import * as SecureStore from "expo-secure-store";
import { Platform } from "react-native";

const sessionStorageKey = "mesale.session.v1";
const invalidationListeners = new Set<() => void>();

export type Session = {
  accessToken: string;
  tokenType: "Bearer";
  expiresAt?: string;
};

function isSession(value: unknown): value is Session {
  if (!value || typeof value !== "object") return false;
  const candidate = value as Record<string, unknown>;
  return typeof candidate.accessToken === "string" && candidate.accessToken.length > 0 && candidate.tokenType === "Bearer";
}

export async function loadSession(): Promise<Session | null> {
  if (Platform.OS === "web") return null;
  const raw = await SecureStore.getItemAsync(sessionStorageKey);
  if (!raw) return null;
  try {
    const value: unknown = JSON.parse(raw);
    return isSession(value) ? value : null;
  } catch {
    return null;
  }
}

export async function saveSession(session: Session): Promise<void> {
  if (Platform.OS === "web") return;
  await SecureStore.setItemAsync(sessionStorageKey, JSON.stringify(session), {
    keychainAccessible: SecureStore.WHEN_UNLOCKED_THIS_DEVICE_ONLY
  });
}

export async function clearSession(): Promise<void> {
  if (Platform.OS === "web") return;
  await SecureStore.deleteItemAsync(sessionStorageKey);
}

export function onSessionInvalidated(listener: () => void): () => void {
  invalidationListeners.add(listener);
  return () => invalidationListeners.delete(listener);
}

export function notifySessionInvalidated(): void {
  for (const listener of invalidationListeners) listener();
}
