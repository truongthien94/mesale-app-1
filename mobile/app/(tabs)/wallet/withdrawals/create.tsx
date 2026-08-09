import { useEffect, useMemo, useRef, useState } from "react";
import { useRouter } from "expo-router";
import { KeyboardAvoidingView, Platform, Pressable, ScrollView, StyleSheet, Text, View } from "react-native";
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
import { formatVnd } from "@/features/wallet/format";
import {
  useAccountSummary,
  useAppConfig,
  useCreateWithdrawal,
  usePaymentAccounts,
  useSendWithdrawalOtp
} from "@/features/wallet/api";
import { useStableSubmission } from "@/features/wallet/submission";
import type { PaymentMethod, WithdrawalCreated, WithdrawalPayload } from "@/features/wallet/types";
import { colors, spacing, theme } from "@/theme/tokens";

type FormErrors = Partial<Record<"amount" | "destination" | "accountNumber" | "accountName" | "otp", string>>;

function withdrawalFingerprint(payload: WithdrawalPayload) {
  const { otp_code: _otpCode, ...businessPayload } = payload;
  return businessPayload;
}

export default function CreateWithdrawalScreen() {
  const router = useRouter();
  const insets = useSafeAreaInsets();
  const accountQuery = useAccountSummary();
  const configQuery = useAppConfig();
  const accountsQuery = usePaymentAccounts();
  const mutation = useCreateWithdrawal();
  const otpMutation = useSendWithdrawalOtp();
  const stableSubmission = useStableSubmission("withdrawal.create", withdrawalFingerprint);
  const didPrefill = useRef(false);

  const [amountText, setAmountText] = useState("");
  const [method, setMethod] = useState<PaymentMethod>("bank");
  const [destination, setDestination] = useState("");
  const [accountNumber, setAccountNumber] = useState("");
  const [accountName, setAccountName] = useState("");
  const [otpCode, setOtpCode] = useState("");
  const [errors, setErrors] = useState<FormErrors>({});
  const [created, setCreated] = useState<WithdrawalCreated | null>(null);

  const config = configQuery.data?.withdraw;
  const balance = accountQuery.data?.wallet.balance ?? 0;
  const amount = Number(amountText.replace(/\D/g, "")) || 0;
  const fee = useMemo(() => {
    if (!config) return 0;
    return config.fee_type === "percentage"
      ? Math.round((amount * config.fee_value) / 100)
      : Math.round(config.fee_value);
  }, [amount, config]);

  useEffect(() => {
    if (!config || accountsQuery.isPending || didPrefill.current) return;
    didPrefill.current = true;
    const defaultAccount = accountsQuery.data?.items.find((item) => item.is_default) ?? accountsQuery.data?.items[0];
    if (defaultAccount) {
      setMethod(defaultAccount.payment_method);
      setDestination(defaultAccount.bank_name);
      setAccountNumber(defaultAccount.account_number);
      setAccountName(defaultAccount.account_name);
      return;
    }
    if (!config.bank_enabled && config.wallet_enabled) setMethod("wallet");
  }, [accountsQuery.data, accountsQuery.isPending, config]);

  if (accountQuery.isPending || configQuery.isPending || accountsQuery.isPending) return <LoadingState label="Đang chuẩn bị biểu mẫu..." />;
  if (accountQuery.isError) return <QueryFailure error={accountQuery.error} onRetry={() => void accountQuery.refetch()} />;
  if (configQuery.isError) return <QueryFailure error={configQuery.error} onRetry={() => void configQuery.refetch()} />;

  if (created) {
    return (
      <PageFrame style={styles.centered}>
        <Card style={styles.successCard}>
          <View style={styles.successMark}><Text style={styles.successMarkText}>✓</Text></View>
          <Text accessibilityRole="header" style={styles.successTitle}>Đã gửi yêu cầu rút tiền</Text>
          <Text style={styles.successCaption}>Mã yêu cầu #{created.code} đang chờ hệ thống xét duyệt.</Text>
          <Text style={styles.successAmount}>{formatVnd(created.real_amount)}</Text>
          <PrimaryButton label="Xem lịch sử rút tiền" onPress={() => router.replace("/(tabs)/wallet/withdrawals")} />
        </Card>
      </PageFrame>
    );
  }

  if (!configQuery.data.withdraw.enabled) {
    return <EmptyState title="Rút tiền đang tạm tắt" message="Vui lòng quay lại sau khi hệ thống mở lại tính năng." actionLabel="Quay lại" onAction={() => router.back()} />;
  }

  const withdrawConfig = configQuery.data.withdraw;
  const destinations = method === "bank"
    ? withdrawConfig.allowed_banks
    : method === "wallet"
      ? withdrawConfig.allowed_wallets
      : ["MoMo"];

  function selectSavedAccount(idText: string) {
    const saved = accountsQuery.data?.items.find((item) => String(item.id) === idText);
    if (!saved) return;
    setMethod(saved.payment_method);
    setDestination(saved.bank_name);
    setAccountNumber(saved.account_number);
    setAccountName(saved.account_name);
    setErrors({});
  }

  function buildPayload(): WithdrawalPayload | null {
    const nextErrors: FormErrors = {};
    if (amount < withdrawConfig.min_amount) nextErrors.amount = `Tối thiểu ${formatVnd(withdrawConfig.min_amount)}.`;
    else if (amount > balance) nextErrors.amount = "Số dư khả dụng không đủ.";
    if (method !== "momo" && !destination) nextErrors.destination = "Vui lòng chọn ngân hàng hoặc ví nhận tiền.";
    if (!accountNumber.trim()) nextErrors.accountNumber = "Vui lòng nhập số tài khoản hoặc số điện thoại ví.";
    if (!accountName.trim()) nextErrors.accountName = "Vui lòng nhập tên chủ tài khoản.";
    if (withdrawConfig.otp_required && !/^\d{6}$/.test(otpCode)) nextErrors.otp = "Mã OTP phải gồm 6 số.";
    setErrors(nextErrors);
    if (Object.keys(nextErrors).length > 0) return null;

    return {
      amount,
      payment_method: method,
      account_number: accountNumber.trim(),
      account_name: accountName.trim().toUpperCase(),
      ...(method === "bank" ? { bank_name: destination } : {}),
      ...(method === "wallet" ? { wallet_name: destination } : {}),
      ...(withdrawConfig.otp_required ? { otp_code: otpCode } : {})
    };
  }

  async function submit() {
    const payload = buildPayload();
    if (!payload) return;
    try {
      const result = await mutation.mutateAsync(stableSubmission.getVariables(payload));
      stableSubmission.reset();
      setCreated(result);
    } catch {
      // The mutation exposes the normalized API error inline and keeps the same key for retry.
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
          <View style={styles.balanceCard}>
            <Text style={styles.balanceLabel}>SỐ DƯ VÍ HIỆN TẠI</Text>
            <Text style={styles.balanceValue}>{formatVnd(balance)}</Text>
            <View style={styles.balanceRow}>
              <Text style={styles.balanceHint}>Rút tối thiểu {formatVnd(withdrawConfig.min_amount)}</Text>
              <Pressable accessibilityRole="button" onPress={() => setAmountText(String(balance))}>
                <Text style={styles.withdrawAll}>RÚT TẤT CẢ</Text>
              </Pressable>
            </View>
          </View>

          {accountsQuery.data && accountsQuery.data.items.length > 0 ? (
            <Card>
              <SectionTitle title="Tài khoản đã lưu" caption="Chọn để điền nhanh thông tin nhận tiền." />
              <View style={styles.savedAccounts}>
                {accountsQuery.data.items.map((saved) => (
                  <Pressable
                    accessibilityRole="button"
                    key={saved.id}
                    onPress={() => selectSavedAccount(String(saved.id))}
                    style={({ pressed }) => [styles.savedAccount, pressed && styles.pressed]}
                  >
                    <Text style={styles.savedBank}>{saved.bank_name}{saved.is_default ? " · Mặc định" : ""}</Text>
                    <Text style={styles.savedNumber}>{saved.account_number}</Text>
                  </Pressable>
                ))}
              </View>
            </Card>
          ) : null}

          <Card style={styles.formCard}>
            <SectionTitle title="Thông tin rút tiền" caption="Giao dịch chỉ được tạo sau khi Laravel xác thực số dư và điều kiện rút." />
            <TextField
              error={errors.amount}
              keyboardType="number-pad"
              label="Số tiền muốn rút"
              onChangeText={(value) => { setAmountText(value.replace(/\D/g, "")); setErrors((current) => ({ ...current, amount: undefined })); }}
              placeholder={String(withdrawConfig.min_amount)}
              value={amountText}
            />
            <View style={styles.feeBox}>
              <Text style={styles.feeText}>Phí dự kiến: {formatVnd(fee)}</Text>
              <Text style={styles.receivedText}>Thực nhận: {formatVnd(Math.max(0, amount - fee))}</Text>
            </View>
            <ChoiceRow
              label="Hình thức nhận"
              onChange={(value) => { setMethod(value as PaymentMethod); setDestination(value === "momo" ? "MoMo" : ""); setErrors({}); }}
              options={[
                { label: "Ngân hàng", value: "bank", disabled: !withdrawConfig.bank_enabled },
                { label: "Ví điện tử", value: "wallet", disabled: !withdrawConfig.wallet_enabled },
                { label: "MoMo", value: "momo", disabled: !withdrawConfig.wallet_enabled }
              ]}
              value={method}
            />
            {method !== "momo" ? (
              <View style={styles.destinationGroup}>
                <ChoiceRow
                  label={method === "bank" ? "Ngân hàng nhận" : "Ví điện tử nhận"}
                  onChange={(value) => { setDestination(value); setErrors((current) => ({ ...current, destination: undefined })); }}
                  options={destinations.map((item) => ({ label: item, value: item }))}
                  value={destination}
                />
                {errors.destination ? <Text accessibilityRole="alert" style={styles.errorText}>{errors.destination}</Text> : null}
              </View>
            ) : null}
            <TextField
              autoCapitalize="none"
              error={errors.accountNumber}
              keyboardType={method === "bank" ? "number-pad" : "phone-pad"}
              label={method === "bank" ? "Số tài khoản ngân hàng" : "Số điện thoại ví"}
              onChangeText={(value) => { setAccountNumber(value); setErrors((current) => ({ ...current, accountNumber: undefined })); }}
              placeholder="Nhập thông tin nhận tiền"
              value={accountNumber}
            />
            <TextField
              autoCapitalize="characters"
              error={errors.accountName}
              label="Họ tên chủ tài khoản"
              onChangeText={(value) => { setAccountName(value.toUpperCase()); setErrors((current) => ({ ...current, accountName: undefined })); }}
              placeholder="NGUYEN VAN A"
              value={accountName}
            />
            {withdrawConfig.otp_required ? (
              <View style={styles.otpGroup}>
                <TextField
                  autoCapitalize="none"
                  error={errors.otp}
                  keyboardType="number-pad"
                  label="Mã xác minh OTP"
                  onChangeText={(value) => { setOtpCode(value.replace(/\D/g, "").slice(0, 6)); setErrors((current) => ({ ...current, otp: undefined })); }}
                  placeholder="Nhập mã OTP 6 số"
                  value={otpCode}
                />
                <PrimaryButton
                  compact
                  label={otpMutation.isSuccess ? "Đã gửi OTP" : "Gửi mã OTP"}
                  loading={otpMutation.isPending}
                  onPress={() => otpMutation.mutate()}
                  tone="secondary"
                />
                <InlineError error={otpMutation.error} onRetry={() => otpMutation.mutate()} />
              </View>
            ) : null}
            <InlineError error={mutation.error} onRetry={() => void submit()} />
            <PrimaryButton label="Gửi yêu cầu rút tiền" loading={mutation.isPending} onPress={() => void submit()} />
          </Card>
        </ScrollView>
      </KeyboardAvoidingView>
    </PageFrame>
  );
}

const styles = StyleSheet.create({
  flex: { flex: 1 },
  centered: {
    justifyContent: "center",
    padding: spacing.lg
  },
  content: {
    gap: spacing.md,
    padding: spacing.md
  },
  balanceCard: {
    backgroundColor: "#0f172a",
    borderRadius: 24,
    gap: spacing.sm,
    padding: spacing.lg
  },
  balanceLabel: {
    color: "#94a3b8",
    fontSize: 10,
    fontWeight: "800",
    letterSpacing: 1
  },
  balanceValue: {
    color: colors.surface,
    fontSize: 30,
    fontWeight: "900"
  },
  balanceRow: {
    alignItems: "center",
    flexDirection: "row",
    justifyContent: "space-between"
  },
  balanceHint: {
    color: "#94a3b8",
    fontSize: 10
  },
  withdrawAll: {
    color: "#fb923c",
    fontSize: 10,
    fontWeight: "900"
  },
  savedAccounts: {
    gap: spacing.sm,
    marginTop: spacing.md
  },
  savedAccount: {
    backgroundColor: "#f8fafc",
    borderColor: colors.border,
    borderRadius: theme.radius.md,
    borderWidth: 1,
    gap: spacing.xs,
    padding: spacing.md
  },
  savedBank: {
    color: colors.text,
    fontSize: 12,
    fontWeight: "800"
  },
  savedNumber: {
    color: colors.mutedText,
    fontSize: 11
  },
  pressed: { opacity: 0.72 },
  formCard: {
    gap: spacing.lg
  },
  feeBox: {
    backgroundColor: "#fff7ed",
    borderRadius: theme.radius.md,
    flexDirection: "row",
    justifyContent: "space-between",
    padding: spacing.md
  },
  feeText: {
    color: "#9a3412",
    fontSize: 11
  },
  receivedText: {
    color: colors.primary,
    fontSize: 11,
    fontWeight: "800"
  },
  destinationGroup: {
    gap: spacing.xs
  },
  errorText: {
    color: colors.danger,
    fontSize: 12
  },
  otpGroup: {
    gap: spacing.sm
  },
  successCard: {
    alignItems: "center",
    gap: spacing.md
  },
  successMark: {
    alignItems: "center",
    backgroundColor: "#ecfdf5",
    borderRadius: 999,
    height: 64,
    justifyContent: "center",
    width: 64
  },
  successMarkText: {
    color: "#059669",
    fontSize: 30,
    fontWeight: "900"
  },
  successTitle: {
    color: colors.text,
    fontSize: 20,
    fontWeight: "900",
    textAlign: "center"
  },
  successCaption: {
    color: colors.mutedText,
    fontSize: 13,
    lineHeight: 20,
    textAlign: "center"
  },
  successAmount: {
    color: colors.primary,
    fontSize: 26,
    fontWeight: "900"
  }
});
