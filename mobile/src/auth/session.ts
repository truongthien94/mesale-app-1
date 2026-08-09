import * as SecureStore from "expo-secure-store";
import { Platform } from "react-native";

const sessionStorageKey = "mesale.session.v1";
const invalidationListeners = new Set<() => void>();
let sessionStorageRevision = 0;

export type Session = {
  accessToken: string;
  tokenType: "Bearer";
  expiresAt?: string;
};

function isSession(value: unknown): value is Session {
  if (!value || typeof value !== "object") return false;
  const candidate = value as Record<string, unknown>;
  if (typeof candidate.accessToken !== "string" || candidate.accessToken.trim().length === 0 || candidate.tokenType !== "Bearer") {
    return false;
  }

  if (candidate.expiresAt === undefined) return true;
  if (typeof candidate.expiresAt !== "string" || candidate.expiresAt.trim().length === 0) return false;

  const expiresAt = Date.parse(candidate.expiresAt);
  return Number.isFinite(expiresAt) && expiresAt > Date.now();
}

export async function loadSession(): Promise<Session | null> {
  if (Platform.OS === "web") return null;
  const raw = await SecureStore.getItemAsync(sessionStorageKey);
  if (!raw) return null;
  try {
    const value: unknown = JSON.parse(raw);
    if (isSession(value)) return value;
  } catch {
    // The invalid value is removed below so it cannot break every app launch.
  }

  await clearSession();
  return null;
}

export async function saveSession(session: Session): Promise<void> {
  if (Platform.OS === "web") return;
  sessionStorageRevision += 1;
  await SecureStore.setItemAsync(sessionStorageKey, JSON.stringify(session), {
    keychainAccessible: SecureStore.WHEN_UNLOCKED_THIS_DEVICE_ONLY
  });
}

export async function clearSession(): Promise<void> {
  if (Platform.OS === "web") return;
  sessionStorageRevision += 1;
  await SecureStore.deleteItemAsync(sessionStorageKey);
}

export async function clearSessionIfTokenMatches(accessToken: string): Promise<boolean> {
  if (Platform.OS === "web") return false;

  const observedRevision = sessionStorageRevision;
  const raw = await SecureStore.getItemAsync(sessionStorageKey);
  if (sessionStorageRevision !== observedRevision) return false;
  if (!raw) return false;

  try {
    const value = JSON.parse(raw) as Record<string, unknown>;
    if (value.accessToken !== accessToken) return false;
  } catch {
    return false;
  }

  sessionStorageRevision += 1;
  await SecureStore.deleteItemAsync(sessionStorageKey);
  return true;
}

export function onSessionInvalidated(listener: () => void): () => void {
  invalidationListeners.add(listener);
  return () => invalidationListeners.delete(listener);
}

export function notifySessionInvalidated(): void {
  for (const listener of invalidationListeners) listener();
}
