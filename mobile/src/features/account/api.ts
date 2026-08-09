import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { request, requestEnvelope } from "@/api/client";
import { accountPaths, normalizePreferences, sessionRevokePath } from "@/features/account/contracts";
import type { AccountData, AccountPreferences, SecurityStatus, SessionCollection, TwoFactorSetup } from "@/features/account/types";

export const accountKeys = {
  detail: ["account", "detail"] as const,
  security: ["account", "security"] as const,
  sessions: ["account", "sessions"] as const
};

export function useAccount() {
  return useQuery({ queryKey: accountKeys.detail, queryFn: ({ signal }) => request<AccountData>(accountPaths.account, { signal }) });
}

export function useUpdateProfile() {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: (payload: { name: string; phone?: string }) => requestEnvelope<{ id: number; name: string; phone: string | null }>(accountPaths.profile, {
      method: "POST",
      body: { name: payload.name.trim(), phone: payload.phone?.trim() || null }
    }),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: accountKeys.detail })
  });
}

export function useChangePassword() {
  return useMutation({
    mutationFn: (payload: { currentPassword: string; password: string; passwordConfirmation: string }) => requestEnvelope<null>(accountPaths.password, {
      method: "POST",
      body: {
        current_password: payload.currentPassword,
        password: payload.password,
        password_confirmation: payload.passwordConfirmation
      }
    })
  });
}

export function useUpdatePreferences() {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: (payload: { locale?: string; currency?: string }) => requestEnvelope<AccountPreferences>(accountPaths.preferences, {
      method: "POST",
      body: normalizePreferences(payload)
    }),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: accountKeys.detail })
  });
}

export function useSecurity() {
  return useQuery({ queryKey: accountKeys.security, queryFn: ({ signal }) => request<SecurityStatus>(accountPaths.security, { signal }) });
}

export function useSetupTwoFactor() {
  return useMutation({ mutationFn: () => request<TwoFactorSetup>(accountPaths.twoFactorSetup, { method: "POST" }) });
}

function securityMutation(path: string, body?: unknown) {
  return requestEnvelope<null>(path, { method: "POST", body });
}

export function useEnableTwoFactor() {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: (payload: { secret: string; otpCode: string }) => securityMutation(accountPaths.twoFactorEnable, { secret: payload.secret, otp_code: payload.otpCode }),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: accountKeys.security })
  });
}

export function useDisableTwoFactor() {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: (payload: { password: string; otpCode: string }) => securityMutation(accountPaths.twoFactorDisable, { password: payload.password, otp_code: payload.otpCode }),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: accountKeys.security })
  });
}

export function useSendEmailOtp() {
  return useMutation({ mutationFn: () => securityMutation(accountPaths.emailOtpSend) });
}

export function useEnableEmailOtp() {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: (otpCode: string) => securityMutation(accountPaths.emailOtpEnable, { otp_code: otpCode }),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: accountKeys.security })
  });
}

export function useDisableEmailOtp() {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: (payload: { password: string; otpCode: string }) => securityMutation(accountPaths.emailOtpDisable, { password: payload.password, otp_code: payload.otpCode }),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: accountKeys.security })
  });
}

export function useSessions() {
  return useQuery({ queryKey: accountKeys.sessions, queryFn: ({ signal }) => request<SessionCollection>(accountPaths.sessions, { signal }) });
}

export function useRevokeSession() {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: (id: number) => requestEnvelope<null>(sessionRevokePath(id), { method: "POST" }),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: accountKeys.sessions })
  });
}

export function useRevokeOtherSessions() {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: () => requestEnvelope<{ revoked: number }>(accountPaths.revokeOthers, { method: "POST" }),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: accountKeys.sessions })
  });
}

export function useDeleteAccount() {
  return useMutation({
    mutationFn: (password?: string) => requestEnvelope<null>(accountPaths.delete, {
      method: "POST",
      body: password?.trim() ? { password } : {}
    })
  });
}
