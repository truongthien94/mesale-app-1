import { useState } from "react";
import { ActivityIndicator, Pressable, StyleSheet, Text } from "react-native";
import { Redirect } from "expo-router";
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
  const locale = getDeviceLocale();
  const { isLoading, pendingAuth, session, settleReferralPrompt, user } = useAuth();
  const [referralCode, setReferralCode] = useState("");
  const [requestError, setRequestError] = useState<ApiError | null>(null);
  const [action, setAction] = useState<"apply" | "skip" | null>(null);
  const normalizedCode = referralCode.trim();

  if (isLoading) return <LoadingState />;
  const authGate = resolveAuthGate(pendingAuth, Boolean(session), user?.referralPromptPending ?? false);
  if (authGate !== "/referral-code") return <Redirect href={authGate ?? "/login"} />;

  async function applyCode() {
    setRequestError(null);
    setAction("apply");
    try {
      await applyReferralCode(normalizedCode);
      await settleReferralPrompt();
    } catch (reason) {
      if (isAlreadyDecided(reason)) {
        await settleReferralPrompt();
        return;
      }
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
    } catch (reason) {
      if (isAlreadyDecided(reason)) {
        await settleReferralPrompt();
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
      title={locale === "vi" ? "Nhập mã giới thiệu" : "Enter your referral code"}
      subtitle={locale === "vi"
        ? "Nếu bạn được một thành viên Mesale giới thiệu, hãy nhập mã của họ tại đây."
        : "If a Mesale member invited you, enter their code here."}
    >
      <AuthField
        autoCapitalize="characters"
        autoComplete="off"
        editable={!busy}
        error={requestError?.errors?.referral_code?.[0]}
        label={locale === "vi" ? "Mã giới thiệu" : "Referral code"}
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
        label={locale === "vi" ? "Áp dụng mã" : "Apply code"}
        loading={action === "apply"}
        onPress={() => void applyCode()}
      />
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
    </AuthForm>
  );
}

function toApiError(reason: unknown, fallback: string): ApiError {
  if (reason instanceof ApiError) return reason;
  return new ApiError(reason instanceof Error ? reason.message : fallback, 0);
}

function isAlreadyDecided(reason: unknown): boolean {
  return reason instanceof ApiError && reason.code === "REFERRAL_PROMPT_ALREADY_DECIDED";
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
