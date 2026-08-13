import { useState } from "react";
import { ActivityIndicator, Pressable, StyleSheet, Text } from "react-native";
import { Redirect, useRouter } from "expo-router";
import { applyReferralCode, skipReferralPrompt } from "@/api/auth";
import { ApiError } from "@/api/client";
import { useAuth } from "@/auth/AuthProvider";
import { resolveAuthGate } from "@/auth/routing";
import { LoadingState } from "@/components/AsyncState";
import { FormErrorSummary } from "@/components/FormErrorSummary";
import { AuthButton, AuthField, AuthForm } from "@/features/auth/components";
import { getDeviceLocale } from "@/i18n";
import { colors } from "@/theme/tokens";

export default function ReferralCodeScreen() {
  const router = useRouter();
  const locale = getDeviceLocale();
  const { isLoading, pendingAuth, refreshUser, session, settleReferralPrompt, user } = useAuth();
  const [referralCode, setReferralCode] = useState("");
  const [requestError, setRequestError] = useState<ApiError | null>(null);
  const [action, setAction] = useState<"apply" | "skip" | null>(null);
  const normalizedCode = referralCode.trim();

  if (isLoading) return <LoadingState />;
  const authGate = resolveAuthGate(pendingAuth, Boolean(session), user?.referralPromptPending ?? false);
  if (!session || pendingAuth) return <Redirect href={authGate ?? "/login"} />;
  const canEnterReferralCode = user?.referralPromptPending === true || user?.referralCodeEligible === true;
  if (!canEnterReferralCode) return <Redirect href="/home" />;

  async function applyCode() {
    setRequestError(null);
    setAction("apply");
    try {
      await applyReferralCode(normalizedCode);
      await settleReferralPrompt();
      router.replace("/home");
    } catch (reason) {
      if (isCompatibilityAlreadyDecided(reason)) {
        await settleReferralPrompt();
        router.replace("/home");
        return;
      }
      if (isTerminalReferralState(reason)) await refreshUser().catch(() => undefined);
      setRequestError(toApiError(reason, locale === "vi" ? "Không thể áp dụng mã giới thiệu." : "Unable to apply the referral code."));
    } finally {
      setAction(null);
    }
  }

  async function skip() {
    setRequestError(null);
    setAction("skip");
    try {
      await skipReferralPrompt();
      await settleReferralPrompt();
      router.replace("/home");
    } catch (reason) {
      if (isCompatibilityAlreadyDecided(reason)) {
        await settleReferralPrompt();
        router.replace("/home");
        return;
      }
      setRequestError(toApiError(reason, locale === "vi" ? "Không thể bỏ qua lúc này." : "Unable to skip right now."));
    } finally {
      setAction(null);
    }
  }

  const busy = action !== null;
  return (
    <AuthForm
      logo={require("../../assets/mesale-logo.png")}
      title={locale === "vi" ? "Liên kết người giới thiệu" : "Link your referrer"}
      subtitle={locale === "vi"
        ? "Bạn có 3 ngày đầu sau khi đăng ký tài khoản Mê Sale để nhập mã của người đã giới thiệu bạn. Sau thời hạn này, tài khoản sẽ không thể liên kết mã giới thiệu."
        : "Enter the code from the person who referred you within 3 days of creating your Mê Sale account. Referral linking closes after this period."}
    >
      <AuthField
        autoCapitalize="characters"
        autoComplete="off"
        editable={!busy}
        error={requestError?.errors?.referral_code?.[0]}
        label={locale === "vi" ? "Mã của người giới thiệu" : "Referrer's code"}
        maxLength={50}
        onChangeText={(value) => {
          setReferralCode(value);
          setRequestError(null);
        }}
        onSubmitEditing={() => { if (!busy && normalizedCode) void applyCode(); }}
        placeholder="REFXXXXXX"
        returnKeyType="done"
        value={referralCode}
      />
      <FormErrorSummary errors={requestError?.errors} message={requestError?.message} />
      <AuthButton
        disabled={busy || !normalizedCode}
        label={locale === "vi" ? "Xác nhận mã giới thiệu" : "Confirm referral code"}
        loading={action === "apply"}
        onPress={() => void applyCode()}
      />
      {user?.referralPromptPending === true ? (
        <Pressable
          accessibilityRole="button"
          accessibilityState={{ busy: action === "skip", disabled: busy }}
          disabled={busy}
          onPress={() => void skip()}
          style={({ pressed }) => [styles.skipButton, busy && styles.disabled, pressed && styles.pressed]}
        >
          {action === "skip"
            ? <ActivityIndicator color={colors.primary} />
            : <Text style={styles.skipText}>{locale === "vi" ? "Bỏ qua" : "Skip"}</Text>}
        </Pressable>
      ) : (
        <Pressable
          accessibilityRole="button"
          disabled={busy}
          onPress={() => router.replace("/(tabs)/account")}
          style={({ pressed }) => [styles.skipButton, busy && styles.disabled, pressed && styles.pressed]}
        >
          <Text style={styles.skipText}>{locale === "vi" ? "Quay lại tài khoản" : "Back to account"}</Text>
        </Pressable>
      )}
    </AuthForm>
  );
}

function toApiError(reason: unknown, fallback: string): ApiError {
  if (reason instanceof ApiError) {
    const stateMessage = referralStateMessage(reason.code);
    if (!stateMessage) return reason;
    return new ApiError(stateMessage, reason.status, {
      code: reason.code,
      errors: reason.errors,
      requestId: reason.requestId
    });
  }
  return new ApiError(reason instanceof Error ? reason.message : fallback, 0);
}

function referralStateMessage(code?: string): string | null {
  if (code === "REFERRAL_WINDOW_EXPIRED") return "Thời hạn nhập mã giới thiệu đã kết thúc.";
  if (code === "REFERRAL_NOT_ELIGIBLE") return "Tài khoản hiện không đủ điều kiện nhập mã giới thiệu.";
  if (code === "REFERRAL_ALREADY_LINKED") return "Tài khoản đã liên kết với người giới thiệu.";
  if (code === "REFERRAL_DISABLED") return "Chương trình giới thiệu hiện đang tạm dừng.";
  return null;
}

function isCompatibilityAlreadyDecided(reason: unknown): boolean {
  return reason instanceof ApiError && reason.code === "REFERRAL_PROMPT_ALREADY_DECIDED";
}

function isTerminalReferralState(reason: unknown): boolean {
  return reason instanceof ApiError && [
    "REFERRAL_WINDOW_EXPIRED",
    "REFERRAL_NOT_ELIGIBLE",
    "REFERRAL_ALREADY_LINKED",
    "REFERRAL_DISABLED"
  ].includes(reason.code ?? "");
}

const styles = StyleSheet.create({
  skipButton: {
    alignItems: "center",
    borderColor: colors.primary,
    borderRadius: 12,
    borderWidth: 1,
    justifyContent: "center",
    minHeight: 50
  },
  skipText: { color: colors.primary, fontSize: 15, fontWeight: "800" },
  disabled: { opacity: 0.45 },
  pressed: { opacity: 0.78 }
});
