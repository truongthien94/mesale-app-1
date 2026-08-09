import { useCallback, useRef } from "react";
import { createIdempotentVariables, type IdempotentVariables } from "@/api/idempotency";

type StableSubmission<T> = {
  signature: string;
  variables: IdempotentVariables<T>;
};

export function useStableSubmission<T>(operation: string, fingerprint: (payload: T) => unknown) {
  const active = useRef<StableSubmission<T> | null>(null);

  const getVariables = useCallback((payload: T): IdempotentVariables<T> => {
    const signature = JSON.stringify(fingerprint(payload));
    if (active.current?.signature === signature) {
      return Object.freeze({
        idempotencyKey: active.current.variables.idempotencyKey,
        payload
      });
    }

    const variables = createIdempotentVariables(operation, payload);
    active.current = { signature, variables };
    return variables;
  }, [fingerprint, operation]);

  const reset = useCallback(() => {
    active.current = null;
  }, []);

  return { getVariables, reset };
}
