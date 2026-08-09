export type IdempotentVariables<T> = Readonly<{
  idempotencyKey: string;
  payload: T;
}>;

function randomHex(bytes: number): string {
  const values = new Uint8Array(bytes);
  if (globalThis.crypto?.getRandomValues) {
    globalThis.crypto.getRandomValues(values);
  } else {
    for (let index = 0; index < values.length; index += 1) {
      values[index] = Math.floor(Math.random() * 256);
    }
  }

  return Array.from(values, (value) => value.toString(16).padStart(2, "0")).join("");
}

export function createIdempotencyKey(operation: string): string {
  const normalizedOperation = operation
    .trim()
    .replace(/[^A-Za-z0-9._:-]+/g, "-")
    .replace(/^[^A-Za-z0-9]+/, "")
    .slice(0, 48) || "request";

  return `mob:${normalizedOperation}:${Date.now().toString(36)}:${randomHex(16)}`;
}

export function createIdempotentVariables<T>(operation: string, payload: T): IdempotentVariables<T> {
  return Object.freeze({
    idempotencyKey: createIdempotencyKey(operation),
    payload
  });
}

export function idempotencyHeaders(idempotencyKey: string): Record<"Idempotency-Key", string> {
  return { "Idempotency-Key": idempotencyKey };
}
