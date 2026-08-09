import { clearSessionIfTokenMatches, loadSession, notifySessionInvalidated } from "@/auth/session";
import { env } from "@/config/env";

export type ApiEnvelope<T> = {
  success?: boolean;
  data: T;
  message?: string;
  code?: string;
  request_id?: string;
};

export class ApiError extends Error {
  readonly status: number;
  readonly code?: string;
  readonly requestId?: string;

  constructor(message: string, status: number, code?: string, requestId?: string) {
    super(message);
    this.name = "ApiError";
    this.status = status;
    this.code = code;
    this.requestId = requestId;
  }
}

type RequestOptions = Omit<RequestInit, "body"> & {
  body?: unknown;
  authenticated?: boolean;
};

async function readJson(response: Response): Promise<unknown> {
  const text = await response.text();
  if (!text) return null;
  try {
    return JSON.parse(text) as unknown;
  } catch {
    return { message: text };
  }
}

export async function request<T>(path: string, options: RequestOptions = {}): Promise<T> {
  const controller = new AbortController();
  const timeout = setTimeout(() => controller.abort(), env.apiTimeoutMs);
  try {
    const session = options.authenticated === false ? null : await loadSession();
    const headers = new Headers(options.headers);
    headers.set("Accept", "application/json");
    if (options.body !== undefined) headers.set("Content-Type", "application/json");
    if (session) headers.set("Authorization", `${session.tokenType} ${session.accessToken}`);
    const response = await fetch(`${env.apiBaseUrl}/${path.replace(/^\//, "")}`, {
      ...options,
      body: options.body === undefined ? undefined : JSON.stringify(options.body),
      headers,
      signal: controller.signal
    });
    const payload = await readJson(response);
    if (response.status === 401 && session && await clearSessionIfTokenMatches(session.accessToken)) {
      notifySessionInvalidated();
    }
    if (!response.ok) {
      const errorPayload = payload && typeof payload === "object" ? payload as Record<string, unknown> : {};
      throw new ApiError(
        typeof errorPayload.message === "string" ? errorPayload.message : `Request failed (${response.status})`,
        response.status,
        typeof errorPayload.code === "string" ? errorPayload.code : undefined,
        typeof errorPayload.request_id === "string" ? errorPayload.request_id : undefined
      );
    }
    if (payload && typeof payload === "object" && "data" in payload) return (payload as ApiEnvelope<T>).data;
    return payload as T;
  } finally {
    clearTimeout(timeout);
  }
}
