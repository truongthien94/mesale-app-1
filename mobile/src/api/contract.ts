export type ApiFieldErrors = Record<string, string[]>;

export type ApiResponse<T> = {
  success: true;
  data: T;
  message?: string;
  code?: string;
  requestId?: string;
};

export type ApiFailure = {
  message: string;
  code?: string;
  requestId?: string;
  errors?: ApiFieldErrors;
};

function isRecord(value: unknown): value is Record<string, unknown> {
  return value !== null && typeof value === "object" && !Array.isArray(value);
}

function optionalString(value: unknown): string | undefined {
  return typeof value === "string" && value.trim().length > 0 ? value : undefined;
}

export function normalizeFieldErrors(value: unknown): ApiFieldErrors | undefined {
  if (!isRecord(value)) return undefined;

  const errors: ApiFieldErrors = {};
  for (const [field, messages] of Object.entries(value)) {
    if (typeof messages === "string" && messages.trim().length > 0) {
      errors[field] = [messages];
      continue;
    }
    if (!Array.isArray(messages)) continue;

    const normalized = messages.filter((message): message is string => typeof message === "string" && message.trim().length > 0);
    if (normalized.length > 0) errors[field] = normalized;
  }

  return Object.keys(errors).length > 0 ? errors : undefined;
}

export function normalizeApiSuccess<T>(payload: unknown): ApiResponse<T> {
  if (!isRecord(payload)) {
    return { success: true, data: payload as T };
  }

  const hasEnvelope = "success" in payload || "data" in payload || "message" in payload || "request_id" in payload;
  return {
    success: true,
    data: (hasEnvelope ? ("data" in payload ? payload.data : undefined) : payload) as T,
    message: optionalString(payload.message),
    code: optionalString(payload.code),
    requestId: optionalString(payload.request_id)
  };
}

export function normalizeApiFailure(payload: unknown, fallbackMessage: string): ApiFailure {
  if (!isRecord(payload)) return { message: fallbackMessage };

  return {
    message: optionalString(payload.message) ?? fallbackMessage,
    code: optionalString(payload.code),
    requestId: optionalString(payload.request_id),
    errors: normalizeFieldErrors(payload.errors)
  };
}

export function isFailureEnvelope(payload: unknown): boolean {
  return isRecord(payload) && payload.success === false;
}
