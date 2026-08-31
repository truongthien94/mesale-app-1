import * as SecureStore from "expo-secure-store";
import { Platform } from "react-native";
import {
  createStoredUserPreview,
  parseStoredUserPreview,
  type User
} from "@/api/authContract";

const legacySessionStorageKey = "mesale.session.v1";
const authStorageKey = "mesale.auth.v2";
const invalidationListeners = new Set<() => void>();
let storageQueue: Promise<void> = Promise.resolve();

export type Session = {
  accessToken: string;
  tokenType: "Bearer";
  expiresAt?: string;
};

export type StoredAuthState = {
  session: Session;
  userPreview: User | null;
  requiresBootstrap: boolean;
};

type AuthStorageRecord = {
  version: 2;
  auth: null | {
    session: Session;
    previewForAccessToken: string;
    userPreview: unknown;
  };
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

function enqueueStorageOperation<T>(operation: () => Promise<T>): Promise<T> {
  const result = storageQueue.then(operation, operation);
  storageQueue = result.then(() => undefined, () => undefined);
  return result;
}

function secureStoreOptions() {
  return { keychainAccessible: SecureStore.WHEN_UNLOCKED_THIS_DEVICE_ONLY };
}

export function applyPayoutFeaturePolicy(user: User | null, payoutFeaturesEnabled: boolean): User | null {
  if (!user || payoutFeaturesEnabled) return user;
  const {
    financialSnapshot: _financialSnapshot,
    referral_code: _referralCode,
    referralCodeEligible: _referralCodeEligible,
    referralCodeExpiresAt: _referralCodeExpiresAt,
    wallet: _wallet,
    ...safeUser
  } = user;
  return { ...safeUser, referralPromptPending: false };
}

async function writeTombstone(): Promise<void> {
  const tombstone: AuthStorageRecord = { version: 2, auth: null };
  await SecureStore.setItemAsync(authStorageKey, JSON.stringify(tombstone), secureStoreOptions());
  await SecureStore.deleteItemAsync(legacySessionStorageKey).catch(() => undefined);
}

function parseAuthStorageRecord(raw: string, payoutFeaturesEnabled: boolean): StoredAuthState | null {
  const value: unknown = JSON.parse(raw);
  if (!value || typeof value !== "object") throw new Error("Invalid auth storage record.");
  const record = value as Record<string, unknown>;
  if (record.version !== 2 || !("auth" in record)) throw new Error("Invalid auth storage version.");
  if (record.auth === null) return null;
  if (!record.auth || typeof record.auth !== "object") throw new Error("Invalid auth storage payload.");
  const auth = record.auth as Record<string, unknown>;
  if (!isSession(auth.session) || auth.previewForAccessToken !== auth.session.accessToken) {
    throw new Error("Invalid auth storage binding.");
  }

  return {
    session: auth.session,
    userPreview: applyPayoutFeaturePolicy(parseStoredUserPreview(auth.userPreview), payoutFeaturesEnabled),
    requiresBootstrap: false
  };
}

async function loadStoredAuthState(payoutFeaturesEnabled: boolean): Promise<StoredAuthState | null> {
  const currentRaw = await SecureStore.getItemAsync(authStorageKey);
  if (currentRaw) {
    try {
      const state = parseAuthStorageRecord(currentRaw, payoutFeaturesEnabled);
      if (state && !payoutFeaturesEnabled && state.userPreview) {
        const migratedRecord: AuthStorageRecord = {
          version: 2,
          auth: {
            session: state.session,
            previewForAccessToken: state.session.accessToken,
            userPreview: createStoredUserPreview(state.userPreview)
          }
        };
        await SecureStore.setItemAsync(authStorageKey, JSON.stringify(migratedRecord), secureStoreOptions());
      }
      return state;
    } catch {
      await writeTombstone();
      notifySessionInvalidated();
      return null;
    }
  }

  const legacyRaw = await SecureStore.getItemAsync(legacySessionStorageKey);
  if (!legacyRaw) return null;
  try {
    const value: unknown = JSON.parse(legacyRaw);
    if (isSession(value)) {
      return { session: value, userPreview: null, requiresBootstrap: true };
    }
  } catch {
    // The invalid legacy value is replaced with a v2 tombstone below.
  }

  await writeTombstone();
  notifySessionInvalidated();
  return null;
}

export async function loadAuthState(payoutFeaturesEnabled: boolean): Promise<StoredAuthState | null> {
  if (Platform.OS === "web") return null;
  return enqueueStorageOperation(() => loadStoredAuthState(payoutFeaturesEnabled));
}

export async function loadSession(): Promise<Session | null> {
  return (await loadAuthState(true))?.session ?? null;
}

export async function saveAuthState(session: Session, user: User, payoutFeaturesEnabled: boolean): Promise<void> {
  if (Platform.OS === "web") return;
  if (!isSession(session)) throw new Error("Cannot store an invalid or expired session.");
  const userPreview = createStoredUserPreview(applyPayoutFeaturePolicy(user, payoutFeaturesEnabled) ?? user);
  parseStoredUserPreview(userPreview);
  const record: AuthStorageRecord = {
    version: 2,
    auth: {
      session,
      previewForAccessToken: session.accessToken,
      userPreview
    }
  };

  await enqueueStorageOperation(async () => {
    await SecureStore.setItemAsync(authStorageKey, JSON.stringify(record), secureStoreOptions());
    await SecureStore.deleteItemAsync(legacySessionStorageKey).catch(() => undefined);
  });
}

export async function clearSession(): Promise<void> {
  if (Platform.OS === "web") return;
  await enqueueStorageOperation(writeTombstone);
}

export async function clearSessionIfTokenMatches(accessToken: string): Promise<boolean> {
  if (Platform.OS === "web") return false;
  return enqueueStorageOperation(async () => {
    const state = await loadStoredAuthState(true);
    if (!state || state.session.accessToken !== accessToken) return false;
    await writeTombstone();
    return true;
  });
}

export function onSessionInvalidated(listener: () => void): () => void {
  invalidationListeners.add(listener);
  return () => invalidationListeners.delete(listener);
}

export function notifySessionInvalidated(): void {
  for (const listener of invalidationListeners) listener();
}
