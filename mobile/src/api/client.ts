import { clearSessionIfTokenMatches, loadSession, notifySessionInvalidated } from "@/auth/session";
import {
  isFailureEnvelope,
  normalizeApiFailure,
  normalizeApiSuccess,
  type ApiFieldErrors,
  type ApiResponse
} from "@/api/contract";
import { env } from "@/config/env";

export type { ApiFieldErrors, ApiResponse } from "@/api/contract";

export class ApiError extends Error {
  readonly status: number;
  readonly code?: string;
  readonly requestId?: string;
  readonly errors?: ApiFieldErrors;
  readonly isNetworkError: boolean;
  readonly isTimeout: boolean;

  constructor(
    message: string,
    status: number,
    options: {
      code?: string;
      requestId?: string;
      errors?: ApiFieldErrors;
      isNetworkError?: boolean;
      isTimeout?: boolean;
      cause?: unknown;
    } = {}
  ) {
    super(message, { cause: options.cause });
    this.name = "ApiError";
    this.status = status;
    this.code = options.code;
    this.requestId = options.requestId;
    this.errors = options.errors;
    this.isNetworkError = options.isNetworkError ?? false;
    this.isTimeout = options.isTimeout ?? false;
  }
}

export type RequestOptions = Omit<RequestInit, "body"> & {
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

function isAbortError(reason: unknown): boolean {
  return reason instanceof Error && reason.name === "AbortError";
}

export async function requestEnvelope<T>(path: string, options: RequestOptions = {}): Promise<ApiResponse<T>> {
  const {
    authenticated = true,
    body,
    signal: callerSignal,
    ...fetchOptions
  } = options;
  const controller = new AbortController();
  let timedOut = false;
  const timeout = setTimeout(() => {
    timedOut = true;
    controller.abort();
  }, env.apiTimeoutMs);
  const abortFromCaller = () => controller.abort(callerSignal?.reason);

  if (callerSignal?.aborted) abortFromCaller();
  else callerSignal?.addEventListener("abort", abortFromCaller, { once: true });

  try {
    const session = authenticated ? await loadSession() : null;
    const headers = new Headers(fetchOptions.headers);
    headers.set("Accept", "application/json");
    if (body !== undefined) headers.set("Content-Type", "application/json");
    if (session) headers.set("Authorization", `${session.tokenType} ${session.accessToken}`);

    let response: Response;
    let payload: unknown;
    try {
      response = await fetch(`${env.apiBaseUrl}/${path.replace(/^\//, "")}`, {
        ...fetchOptions,
        body: body === undefined ? undefined : JSON.stringify(body),
        headers,
        signal: controller.signal
      });
      payload = await readJson(response);
    } catch (reason) {
      if (callerSignal?.aborted) throw reason;
      if (timedOut || isAbortError(reason)) {
        throw new ApiError("The request timed out.", 0, {
          code: "REQUEST_TIMEOUT",
          isTimeout: true,
          cause: reason
        });
      }
      throw new ApiError("Unable to connect to the server.", 0, {
        code: "NETWORK_ERROR",
        isNetworkError: true,
        cause: reason
      });
    }

    if (response.status === 401 && session && await clearSessionIfTokenMatches(session.accessToken)) {
      notifySessionInvalidated();
    }

    if (!response.ok || isFailureEnvelope(payload)) {
      const failureStatus = response.ok ? 400 : response.status;
      const failure = normalizeApiFailure(payload, `Request failed (${failureStatus})`);
      throw new ApiError(failure.message, failureStatus, {
        code: failure.code,
        requestId: failure.requestId,
        errors: failure.errors
      });
    }

    return normalizeApiSuccess<T>(payload);
  } finally {
    clearTimeout(timeout);
    callerSignal?.removeEventListener("abort", abortFromCaller);
  }
}

export async function request<T>(path: string, options: RequestOptions = {}): Promise<T> {
  return (await requestEnvelope<T>(path, options)).data;
}
