import { useEffect, useMemo, useState } from "react";
import { useRouter } from "expo-router";
import { KeyboardAvoidingView, Platform, ScrollView, StyleSheet, Switch, Text, View } from "react-native";
import { useSafeAreaInsets } from "react-native-safe-area-context";
import { EmptyState, LoadingState } from "@/components/AsyncState";
import {
  Card,
  ChoiceRow,
  InlineError,
  PageFrame,
  PrimaryButton,
  QueryFailure,
  SectionTitle,
  TextField
} from "@/features/wallet/components";
import { useAppConfig, useCreatePaymentAccount } from "@/features/wallet/api";
import { useStableSubmission } from "@/features/wallet/submission";
import type { PaymentAccountPayload } from "@/features/wallet/types";
import { colors, spacing, theme } from "@/theme/tokens";

type FormErrors = Partial<Record<"destination" | "accountNumber" | "accountName", string>>;

function paymentAccountFingerprint(payload: PaymentAccountPayload) {
  return payload;
}

export default function CreatePaymentAccountScreen() {
  const router = useRouter();
  const insets = useSafeAreaInsets();
  const configQuery = useAppConfig();
  const mutation = useCreatePaymentAccount();
  const stableSubmission = useStableSubmission("payment-account.create", paymentAccountFingerprint);
  const [method, setMethod] = useState<"bank" | "wallet">("bank");
  const [destination, setDestination] = useState("");
  const [accountNumber, setAccountNumber] = useState("");
  const [accountName, setAccountName] = useState("");
  const [isDefault, setIsDefault] = useState(false);
  const [errors, setErrors] = useState<FormErrors>({});

  const destinations = useMemo(() => {
    if (!configQuery.data) return [];
    return method === "bank" ? configQuery.data.withdraw.allowed_banks : configQuery.data.withdraw.allowed_wallets;
  }, [configQuery.data, method]);

  useEffect(() => {
    if (configQuery.data && !configQuery.data.withdraw.bank_enabled && configQuery.data.withdraw.wallet_enabled) {
      setMethod("wallet");
    }
  }, [configQuery.data]);

  if (configQuery.isPending) return <LoadingState label="Đang chuẩn bị biểu mẫu..." />;
  if (configQuery.isError) return <QueryFailure error={configQuery.error} onRetry={() => void configQuery.refetch()} />;
  if (!configQuery.data.withdraw.bank_enabled && !configQuery.data.withdraw.wallet_enabled) {
    return <EmptyState title="Không có phương thức nhận tiền" message="Ngân hàng và ví điện tử đang tạm tắt." actionLabel="Quay lại" onAction={() => router.back()} />;
  }

  function buildPayload(): PaymentAccountPayload | null {
    const nextErrors: FormErrors = {};
    if (!destination) nextErrors.destination = "Vui lòng chọn ngân hàng hoặc ví.";
    if (!accountNumber.trim()) nextErrors.accountNumber = "Vui lòng nhập số tài khoản hoặc số điện thoại ví.";
    if (!accountName.trim()) nextErrors.accountName = "Vui lòng nhập tên chủ tài khoản.";
    setErrors(nextErrors);
    if (Object.keys(nextErrors).length > 0) return null;
    return {
      payment_method: method,
      bank_name: destination,
      account_number: accountNumber.trim(),
      account_name: accountName.trim().toUpperCase(),
      is_default: isDefault
    };
  }

  async function submit() {
    const payload = buildPayload();
    if (!payload) return;
    try {
      await mutation.mutateAsync(stableSubmission.getVariables(payload));
      stableSubmission.reset();
      router.replace("/(tabs)/wallet/payment-accounts");
    } catch {
      // Keep the same idempotency key so an explicit retry is safe.
    }
  }

  return (
    <PageFrame>
      <KeyboardAvoidingView behavior={Platform.OS === "ios" ? "padding" : undefined} style={styles.flex}>
        <ScrollView
          contentContainerStyle={[styles.content, { paddingBottom: spacing.xl + insets.bottom }]}
          contentInsetAdjustmentBehavior="automatic"
          keyboardShouldPersistTaps="handled"
        >
          <Card style={styles.formCard}>
            <SectionTitle title="Tài khoản nhận tiền" caption="Tài khoản đầu tiên sẽ tự động trở thành mặc định." />
            <ChoiceRow
              label="Loại tài khoản"
              onChange={(value) => { setMethod(value as "bank" | "wallet"); setDestination(""); setErrors({}); }}
              options={[
                { label: "Ngân hàng", value: "bank", disabled: !configQuery.data.withdraw.bank_enabled },
                { label: "Ví điện tử", value: "wallet", disabled: !configQuery.data.withdraw.wallet_enabled }
              ]}
              value={method}
            />
            <View style={styles.destinationGroup}>
              <ChoiceRow
                label={method === "bank" ? "Ngân hàng" : "Ví điện tử"}
                onChange={(value) => { setDestination(value); setErrors((current) => ({ ...current, destination: undefined })); }}
                options={destinations.map((item) => ({ label: item, value: item }))}
                value={destination}
              />
              {errors.destination ? <Text accessibilityRole="alert" style={styles.errorText}>{errors.destination}</Text> : null}
            </View>
            <TextField
              autoCapitalize="none"
              error={errors.accountNumber}
              keyboardType={method === "bank" ? "number-pad" : "phone-pad"}
              label={method === "bank" ? "Số tài khoản" : "Số điện thoại ví"}
              onChangeText={(value) => { setAccountNumber(value); setErrors((current) => ({ ...current, accountNumber: undefined })); }}
              placeholder="Nhập thông tin tài khoản"
              value={accountNumber}
            />
            <TextField
              autoCapitalize="characters"
              error={errors.accountName}
              label="Tên chủ tài khoản"
              onChangeText={(value) => { setAccountName(value.toUpperCase()); setErrors((current) => ({ ...current, accountName: undefined })); }}
              placeholder="NGUYEN VAN A"
              value={accountName}
            />
            <View style={styles.switchRow}>
              <View style={styles.switchCopy}>
                <Text style={styles.switchTitle}>Đặt làm mặc định</Text>
                <Text style={styles.switchCaption}>Tự điền tài khoản này khi tạo lệnh rút.</Text>
              </View>
              <Switch
                accessibilityLabel="Đặt làm tài khoản mặc định"
                onValueChange={setIsDefault}
                thumbColor={colors.surface}
                trackColor={{ false: colors.border, true: colors.primary }}
                value={isDefault}
              />
            </View>
            <InlineError error={mutation.error} onRetry={() => void submit()} />
            <PrimaryButton label="Lưu tài khoản nhận tiền" loading={mutation.isPending} onPress={() => void submit()} />
          </Card>
        </ScrollView>
      </KeyboardAvoidingView>
    </PageFrame>
  );
}

const styles = StyleSheet.create({
  flex: { flex: 1 },
  content: {
    padding: spacing.md
  },
  formCard: {
    gap: spacing.lg
  },
  destinationGroup: {
    gap: spacing.xs
  },
  errorText: {
    color: colors.danger,
    fontSize: 12
  },
  switchRow: {
    alignItems: "center",
    backgroundColor: "#f8fafc",
    borderColor: colors.border,
    borderRadius: theme.radius.md,
    borderWidth: 1,
    flexDirection: "row",
    gap: spacing.md,
    justifyContent: "space-between",
    padding: spacing.md
  },
  switchCopy: {
    flex: 1,
    gap: spacing.xs
  },
  switchTitle: {
    color: colors.text,
    fontSize: 13,
    fontWeight: "800"
  },
  switchCaption: {
    color: colors.mutedText,
    fontSize: 11,
    lineHeight: 16
  }
});
