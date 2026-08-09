import { useCallback, useRef } from "react";
import { createIdempotentVariables, type IdempotentVariables } from "@/api/idempotency";

type ActiveSubmission<T> = {
  signature: string;
  variables: IdempotentVariables<T>;
};

/** Keep a financial mutation's key stable while the caller retries it. */
export function useStableEarnSubmission<T>(operation: string, fingerprint: (payload: T) => unknown) {
  const active = useRef(new Map<string, ActiveSubmission<T>>());

  const getVariables = useCallback((payload: T): IdempotentVariables<T> => {
    const signature = JSON.stringify(fingerprint(payload));
    const existing = active.current.get(signature);
    if (existing) {
      return Object.freeze({
        idempotencyKey: existing.variables.idempotencyKey,
        payload
      });
    }

    const variables = createIdempotentVariables(operation, payload);
    active.current.set(signature, { signature, variables });
    return variables;
  }, [fingerprint, operation]);

  const reset = useCallback((payload?: T) => {
    if (payload === undefined) {
      active.current.clear();
      return;
    }
    active.current.delete(JSON.stringify(fingerprint(payload)));
  }, [fingerprint]);

  return { getVariables, reset };
}
