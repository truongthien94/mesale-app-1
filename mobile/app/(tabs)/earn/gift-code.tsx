import { useState } from "react";
import { useMutation, useQueryClient } from "@tanstack/react-query";
import { KeyboardAvoidingView, Platform, ScrollView, StyleSheet, Text, View } from "react-native";
import { useSafeAreaInsets } from "react-native-safe-area-context";
import { ApiError } from "@/api/client";
import { FormErrorSummary } from "@/components/FormErrorSummary";
import { IosPayoutRouteGuard } from "@/components/IosPayoutRouteGuard";
import { redeemGiftCode } from "@/features/earn/api";
import { invalidateRewardCaches } from "@/features/earn/cache";
import { useStableEarnSubmission } from "@/features/earn/submission";
import { ActionButton, Card, Field, ScreenHeader, earnStyles, formatMoney } from "@/features/earn/ui";
import { getDeviceLocale } from "@/i18n";
import { colors, spacing } from "@/theme/tokens";

function giftCodeFingerprint(payload: { code: string }) {
  return payload;
}

export default function GiftCodeScreen() {
  return <IosPayoutRouteGuard><GiftCodeContent /></IosPayoutRouteGuard>;
}

function GiftCodeContent() {
  const insets = useSafeAreaInsets();
  const queryClient = useQueryClient();
  const vi = getDeviceLocale() === "vi";
  const [code, setCode] = useState("");
  const stableSubmission = useStableEarnSubmission("giftcode.redeem", giftCodeFingerprint);
  const mutation = useMutation({
    mutationFn: redeemGiftCode,
    onSuccess: async (_response, variables) => {
      stableSubmission.reset(variables.payload);
      setCode("");
      await invalidateRewardCaches(queryClient);
    }
  });
  const error = mutation.error instanceof ApiError ? mutation.error : null;

  const submit = () => {
    const normalizedCode = code.replaceAll(" ", "").trim().toUpperCase();
    if (!normalizedCode) return;
    mutation.mutate(stableSubmission.getVariables({ code: normalizedCode }));
  };

  return (
    <KeyboardAvoidingView behavior={Platform.OS === "ios" ? "padding" : undefined} style={earnStyles.screen}>
      <ScrollView
        contentContainerStyle={[styles.content, { paddingBottom: insets.bottom + spacing.lg }]}
        contentInsetAdjustmentBehavior="automatic"
        keyboardShouldPersistTaps="handled"
      >
        <ScreenHeader
          eyebrow="Giftcode"
          subtitle={vi ? "Nhập mã từ sự kiện, minigame hoặc quản trị viên. Phần thưởng do hệ thống xác nhận." : "Enter a code from an event, campaign, or administrator. Rewards are server-confirmed."}
          title={vi ? "Nhập Giftcode" : "Redeem gift code"}
        />
        <View style={styles.hero}>
          <Text style={styles.heroMarker}>GC</Text>
          <Text style={styles.heroTitle}>{vi ? "Đổi mã quà tặng" : "Redeem your code"}</Text>
          <Text style={styles.heroText}>{vi ? "Mỗi mã có điều kiện, thời hạn và số lượt sử dụng riêng." : "Each code has its own eligibility, expiry, and usage limits."}</Text>
        </View>
        {error ? <FormErrorSummary errors={error.errors} message={error.message} /> : null}
        {mutation.isSuccess ? (
          <View accessibilityLiveRegion="polite" style={styles.success}>
            <Text style={styles.successTitle}>{vi ? "Đổi mã thành công" : "Code redeemed"}</Text>
            <Text style={styles.successAmount}>+{formatMoney(mutation.data.data.amount)}</Text>
            {mutation.data.message ? <Text style={styles.successText}>{mutation.data.message}</Text> : null}
          </View>
        ) : null}
        <Card>
          <Field
            autoCapitalize="characters"
            autoCorrect={false}
            label={vi ? "Mã Giftcode" : "Gift code"}
            maxLength={50}
            onChangeText={(value) => {
              mutation.reset();
              setCode(value);
            }}
            onSubmitEditing={submit}
            placeholder="MESALE2026"
            returnKeyType="done"
            value={code}
          />
          <ActionButton
            disabled={!code.replaceAll(" ", "").trim()}
            label={vi ? "Nhận thưởng" : "Redeem reward"}
            loading={mutation.isPending}
            onPress={submit}
          />
        </Card>
        <Card>
          <Text style={earnStyles.sectionTitle}>{vi ? "Lưu ý an toàn" : "Safety notes"}</Text>
          <Text style={earnStyles.body}>{vi ? "Không chia sẻ mật khẩu, Bearer token hoặc API key cá nhân để nhận Giftcode. Mesale không yêu cầu những thông tin đó trong màn hình này." : "Never share your password, Bearer token, or personal API key to receive a gift code. Mesale does not request them here."}</Text>
        </Card>
      </ScrollView>
    </KeyboardAvoidingView>
  );
}

const styles = StyleSheet.create({
  content: { gap: spacing.md, padding: spacing.md },
  hero: { alignItems: "center", backgroundColor: "#ff5b4d", borderRadius: 24, gap: spacing.sm, padding: spacing.lg },
  heroMarker: { color: "#ffedd5", fontSize: 36, fontWeight: "900", letterSpacing: 2 },
  heroTitle: { color: colors.surface, fontSize: 22, fontWeight: "900", textAlign: "center" },
  heroText: { color: "#fff7ed", fontSize: 14, lineHeight: 21, textAlign: "center" },
  success: { alignItems: "center", backgroundColor: "#ecfdf5", borderColor: "#a7f3d0", borderRadius: 18, borderWidth: 1, gap: spacing.xs, padding: spacing.md },
  successTitle: { color: "#047857", fontSize: 15, fontWeight: "900" },
  successAmount: { color: "#047857", fontSize: 26, fontWeight: "900" },
  successText: { color: "#065f46", fontSize: 13, lineHeight: 19, textAlign: "center" }
});
