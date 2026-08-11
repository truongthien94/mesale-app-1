import { useEffect, useMemo, useRef, useState } from "react";
import { useRouter } from "expo-router";
import {
  ActivityIndicator,
  KeyboardAvoidingView,
  Platform,
  Pressable,
  ScrollView,
  StyleSheet,
  Text,
  TextInput,
  View
} from "react-native";
import { ChevronRight, CirclePlus, Clock3, CreditCard, LockKeyhole, ReceiptText } from "lucide-react-native";
import { useSafeAreaInsets } from "react-native-safe-area-context";
import { EmptyState, LoadingState } from "@/components/AsyncState";
import { InlineError, PrimaryButton, QueryFailure } from "@/features/wallet/components";
import { formatVnd } from "@/features/wallet/format";
import {
  useAccountSummary,
  useAppConfig,
  useCreateWithdrawal,
  usePaymentAccounts,
  useSendWithdrawalOtp
} from "@/features/wallet/api";
import { useStableSubmission } from "@/features/wallet/submission";
import type { PaymentAccount, WithdrawalCreated, WithdrawalPayload } from "@/features/wallet/types";
import { useTheme } from "@/theme/ThemeProvider";
import type { Theme } from "@/theme/tokens";

type FormErrors = Partial<Record<"amount" | "account" | "otp", string>>;

function withdrawalFingerprint(payload: WithdrawalPayload) {
  const { otp_code: _otpCode, ...businessPayload } = payload;
  return businessPayload;
}

function amountInput(value: number): string {
  if (!value) return "";
  return new Intl.NumberFormat("vi-VN", { maximumFractionDigits: 0 }).format(value);
}

function accountSuffix(value: string): string {
  const normalized = value.replace(/\s/g, "");
  if (normalized.length <= 4) return normalized;
  return `•••• ${normalized.slice(-4)}`;
}

function accountMethodLabel(account: PaymentAccount): string {
  return account.payment_method === "bank" ? account.bank_name : `Ví ${account.bank_name}`;
}

export default function CreateWithdrawalScreen() {
  const router = useRouter();
  const insets = useSafeAreaInsets();
  const { colors, scheme } = useTheme();
  const styles = useMemo(() => createStyles(colors, scheme), [colors, scheme]);
  const accountQuery = useAccountSummary();
  const configQuery = useAppConfig();
  const accountsQuery = usePaymentAccounts();
  const mutation = useCreateWithdrawal();
  const otpMutation = useSendWithdrawalOtp();
  const stableSubmission = useStableSubmission("withdrawal.create", withdrawalFingerprint);
  const didPrefill = useRef(false);

  const [amountText, setAmountText] = useState("");
  const [selectedAccountId, setSelectedAccountId] = useState<number | null>(null);
  const [showAccountPicker, setShowAccountPicker] = useState(false);
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
  const accounts = useMemo(() => {
    if (!config || !accountsQuery.data) return [];
    return accountsQuery.data.items.filter((account) => account.payment_method === "bank"
      ? config.bank_enabled
      : config.wallet_enabled);
  }, [accountsQuery.data, config]);
  const selectedAccount = accounts.find((account) => account.id === selectedAccountId) ?? null;

  useEffect(() => {
    if (!config || accountsQuery.isPending || didPrefill.current) return;
    didPrefill.current = true;
    const defaultAccount = accounts.find((item) => item.is_default) ?? accounts[0];
    if (defaultAccount) setSelectedAccountId(defaultAccount.id);
  }, [accounts, accountsQuery.isPending, config]);

  if (accountQuery.isPending || configQuery.isPending || accountsQuery.isPending) return <LoadingState label="Đang chuẩn bị biểu mẫu..." />;
  if (accountQuery.isError) return <QueryFailure error={accountQuery.error} onRetry={() => void accountQuery.refetch()} />;
  if (configQuery.isError) return <QueryFailure error={configQuery.error} onRetry={() => void configQuery.refetch()} />;
  if (accountsQuery.isError) return <QueryFailure error={accountsQuery.error} onRetry={() => void accountsQuery.refetch()} />;

  if (created) {
    return (
      <View style={styles.successScreen}>
        <View style={styles.successCard}>
          <View style={styles.successMark}><Text style={styles.successMarkText}>✓</Text></View>
          <Text accessibilityRole="header" style={styles.successTitle}>Đã gửi yêu cầu rút tiền</Text>
          <Text style={styles.successCaption}>Mã yêu cầu #{created.code} đang chờ Mê Sale xét duyệt.</Text>
          <Text style={styles.successAmount}>{formatVnd(created.real_amount)}</Text>
          <PrimaryButton label="Xem lịch sử rút tiền" onPress={() => router.replace("/(tabs)/wallet/withdrawals")} />
        </View>
      </View>
    );
  }

  if (!configQuery.data.withdraw.enabled) {
    return <EmptyState title="Rút tiền đang tạm tắt" message="Vui lòng quay lại sau khi hệ thống mở lại tính năng." actionLabel="Quay lại" onAction={() => router.back()} />;
  }

  const withdrawConfig = configQuery.data.withdraw;
  const amountIsValid = amount >= withdrawConfig.min_amount && amount <= balance;
  const otpIsValid = !withdrawConfig.otp_required || /^\d{6}$/.test(otpCode);
  const canSubmit = Boolean(selectedAccount) && amountIsValid && otpIsValid && !mutation.isPending;
  const footerLabel = accounts.length === 0
    ? "Cần liên kết ngân hàng trước"
    : !amountIsValid
      ? "Nhập số tiền hợp lệ"
      : withdrawConfig.otp_required && !otpIsValid
        ? "Nhập mã OTP để tiếp tục"
        : "Gửi yêu cầu rút tiền";
  const feeCopy = fee > 0 ? `Phí dự kiến ${formatVnd(fee)}` : "Mê Sale không thu phí xử lý";

  function selectSavedAccount(account: PaymentAccount) {
    setSelectedAccountId(account.id);
    setShowAccountPicker(false);
    setErrors((current) => ({ ...current, account: undefined }));
  }

  function validateAmount(nextAmount: number): string | undefined {
    if (nextAmount < withdrawConfig.min_amount) return `Số tiền tối thiểu là ${formatVnd(withdrawConfig.min_amount)}.`;
    if (nextAmount > balance) return "Số dư khả dụng không đủ.";
    return undefined;
  }

  function buildPayload(): WithdrawalPayload | null {
    const nextErrors: FormErrors = {};
    nextErrors.amount = validateAmount(amount);
    if (!selectedAccount) nextErrors.account = "Vui lòng chọn tài khoản nhận tiền đã lưu.";
    if (withdrawConfig.otp_required && !otpIsValid) nextErrors.otp = "Mã OTP phải gồm 6 số.";
    setErrors(nextErrors);
    if (Object.values(nextErrors).some(Boolean) || !selectedAccount) return null;

    return {
      amount,
      payment_method: selectedAccount.payment_method,
      account_number: selectedAccount.account_number,
      account_name: selectedAccount.account_name.trim().toUpperCase(),
      ...(selectedAccount.payment_method === "bank"
        ? { bank_name: selectedAccount.bank_name }
        : { wallet_name: selectedAccount.bank_name }),
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
      // The normalized API error remains visible and retry retains the same idempotency key.
    }
  }

  return (
    <KeyboardAvoidingView behavior={Platform.OS === "ios" ? "padding" : undefined} style={styles.screen}>
      <ScrollView
        contentContainerStyle={[styles.content, { paddingBottom: 28 }]}
        contentInsetAdjustmentBehavior="automatic"
        keyboardShouldPersistTaps="handled"
        showsVerticalScrollIndicator={false}
      >
        <View style={styles.balanceRow}>
          <Text style={styles.balanceLabel}>Số dư khả dụng</Text>
          <Text adjustsFontSizeToFit numberOfLines={1} style={styles.balanceValue}>{formatVnd(balance)}</Text>
        </View>

        <View style={styles.amountCard}>
          <Text style={styles.amountLabel}>Số tiền muốn rút</Text>
          <View style={styles.amountInputRow}>
            <TextInput
              accessibilityLabel="Số tiền muốn rút"
              keyboardType="number-pad"
              onBlur={() => setErrors((current) => ({ ...current, amount: amountText ? validateAmount(amount) : undefined }))}
              onChangeText={(value) => {
                const normalized = value.replace(/\D/g, "");
                const nextAmount = Number(normalized) || 0;
                setAmountText(normalized);
                setErrors((current) => ({ ...current, amount: normalized ? validateAmount(nextAmount) : undefined }));
              }}
              placeholder="0"
              placeholderTextColor={styles.amountPlaceholder.color}
              selectionColor="#2f9af5"
              style={styles.amountInput}
              value={amountInput(amount)}
            />
            <Text style={styles.amountUnit}>đ</Text>
          </View>
          <Text style={styles.amountCaption}>Tối thiểu {formatVnd(withdrawConfig.min_amount)} · {feeCopy}</Text>
          {amount > 0 ? <Text style={styles.receiveCaption}>Dự kiến thực nhận: {formatVnd(Math.max(0, amount - fee))}</Text> : null}
          {errors.amount ? <Text accessibilityRole="alert" style={styles.errorText}>{errors.amount}</Text> : null}
        </View>

        <Text style={styles.sectionLabel}>Tiền về tài khoản</Text>
        {selectedAccount ? (
          <View style={styles.accountCard}>
            <View style={styles.accountIcon}><CreditCard color="#2f9af5" size={24} strokeWidth={2.2} /></View>
            <View style={styles.accountCopy}>
              <View style={styles.accountTitleRow}>
                <Text numberOfLines={1} style={styles.accountTitle}>{accountMethodLabel(selectedAccount)}</Text>
                {selectedAccount.is_default ? <Text style={styles.defaultBadge}>Mặc định</Text> : null}
              </View>
              <Text numberOfLines={1} style={styles.accountNumber}>{accountSuffix(selectedAccount.account_number)} · {selectedAccount.account_name}</Text>
            </View>
            <Pressable
              accessibilityLabel={accounts.length > 1 ? "Đổi tài khoản nhận tiền" : "Quản lý tài khoản nhận tiền"}
              accessibilityRole="button"
              onPress={() => accounts.length > 1
                ? setShowAccountPicker((value) => !value)
                : router.push("/(tabs)/wallet/payment-accounts")}
              style={({ pressed }) => [styles.accountAction, pressed && styles.pressed]}
            >
              <Text style={styles.accountActionText}>{accounts.length > 1 ? "Đổi" : "Quản lý"}</Text>
              <ChevronRight color="#64748b" size={18} />
            </Pressable>
          </View>
        ) : (
          <Pressable
            accessibilityLabel="Thêm tài khoản nhận tiền"
            accessibilityRole="button"
            onPress={() => router.push("/(tabs)/wallet/payment-accounts/create")}
            style={({ pressed }) => [styles.missingAccountCard, pressed && styles.pressed]}
          >
            <View style={styles.missingAccountIcon}><CirclePlus color="#f59e0b" size={25} strokeWidth={2.1} /></View>
            <View style={styles.accountCopy}>
              <Text style={styles.missingAccountTitle}>Chưa liên kết ngân hàng</Text>
              <Text style={styles.missingAccountCaption}>Bấm để thêm tài khoản nhận tiền</Text>
            </View>
            <ChevronRight color="#64748b" size={24} />
          </Pressable>
        )}
        {errors.account ? <Text accessibilityRole="alert" style={styles.errorText}>{errors.account}</Text> : null}

        {showAccountPicker ? (
          <View accessibilityRole="radiogroup" style={styles.accountPicker}>
            {accounts.map((account) => {
              const selected = account.id === selectedAccountId;
              return (
                <Pressable
                  accessibilityLabel={`${accountMethodLabel(account)}, ${accountSuffix(account.account_number)}, ${account.account_name}`}
                  accessibilityRole="radio"
                  accessibilityState={{ checked: selected }}
                  key={account.id}
                  onPress={() => selectSavedAccount(account)}
                  style={({ pressed }) => [styles.accountOption, selected && styles.accountOptionSelected, pressed && styles.pressed]}
                >
                  <View style={styles.optionRadio}>{selected ? <View style={styles.optionRadioDot} /> : null}</View>
                  <View style={styles.accountCopy}>
                    <Text style={styles.optionTitle}>{accountMethodLabel(account)}</Text>
                    <Text style={styles.optionCaption}>{accountSuffix(account.account_number)} · {account.account_name}</Text>
                  </View>
                </Pressable>
              );
            })}
            <Pressable
              accessibilityRole="button"
              onPress={() => router.push("/(tabs)/wallet/payment-accounts")}
              style={({ pressed }) => [styles.manageAccounts, pressed && styles.pressed]}
            >
              <Text style={styles.manageAccountsText}>Quản lý tài khoản đã lưu</Text>
              <ChevronRight color="#2f9af5" size={18} />
            </Pressable>
          </View>
        ) : null}

        {withdrawConfig.otp_required ? (
          <View style={styles.otpCard}>
            <Text style={styles.otpTitle}>Xác minh yêu cầu</Text>
            <Text style={styles.otpCaption}>Nhập mã OTP 6 số do Mê Sale gửi để bảo vệ giao dịch.</Text>
            <View style={styles.otpRow}>
              <TextInput
                accessibilityLabel="Mã xác minh OTP"
                keyboardType="number-pad"
                maxLength={6}
                onChangeText={(value) => {
                  setOtpCode(value.replace(/\D/g, "").slice(0, 6));
                  setErrors((current) => ({ ...current, otp: undefined }));
                }}
                placeholder="Mã OTP 6 số"
                placeholderTextColor={colors.mutedText}
                style={[styles.otpInput, errors.otp && styles.inputError]}
                value={otpCode}
              />
              <Pressable
                accessibilityRole="button"
                accessibilityState={{ busy: otpMutation.isPending }}
                disabled={otpMutation.isPending}
                onPress={() => otpMutation.mutate()}
                style={({ pressed }) => [styles.otpButton, otpMutation.isPending && styles.disabled, pressed && styles.pressed]}
              >
                {otpMutation.isPending
                  ? <ActivityIndicator color="#2f9af5" />
                  : <Text style={styles.otpButtonText}>{otpMutation.isSuccess ? "Gửi lại OTP" : "Gửi mã OTP"}</Text>}
              </Pressable>
            </View>
            {errors.otp ? <Text accessibilityRole="alert" style={styles.errorText}>{errors.otp}</Text> : null}
            <InlineError error={otpMutation.error} onRetry={() => otpMutation.mutate()} />
          </View>
        ) : null}

        <View style={styles.infoCard}>
          <View style={styles.infoRow}>
            <Clock3 color="#16a34a" size={20} />
            <Text style={styles.infoText}>Mê Sale xử lý yêu cầu sau khi xác minh số dư và điều kiện rút tiền.</Text>
          </View>
          <View style={styles.infoRow}>
            <LockKeyhole color="#16a34a" size={20} />
            <Text style={styles.infoText}>Tiền chỉ về đúng tài khoản đã lưu trong hồ sơ của bạn.</Text>
          </View>
          <Pressable
            accessibilityRole="link"
            onPress={() => router.push("/(tabs)/wallet/withdrawals")}
            style={({ pressed }) => [styles.infoRow, pressed && styles.pressed]}
          >
            <ReceiptText color="#16a34a" size={20} />
            <Text style={styles.infoText}>Theo dõi từng lệnh ở mục <Text style={styles.infoStrong}>Lịch sử rút tiền.</Text></Text>
          </Pressable>
        </View>

        <InlineError error={mutation.error} onRetry={() => void submit()} />
      </ScrollView>

      <View style={[styles.footer, { paddingBottom: Math.max(insets.bottom, 12) }]}>
        <Pressable
          accessibilityRole="button"
          accessibilityState={{ busy: mutation.isPending, disabled: !canSubmit }}
          disabled={!canSubmit}
          onPress={() => void submit()}
          style={({ pressed }) => [styles.submitButton, !canSubmit && styles.submitButtonDisabled, pressed && styles.pressed]}
        >
          {mutation.isPending ? <ActivityIndicator color="#ffffff" /> : <Text style={styles.submitButtonText}>{footerLabel}</Text>}
        </Pressable>
      </View>
    </KeyboardAvoidingView>
  );
}

function createStyles(colors: Theme["colors"], scheme: Theme["scheme"]) {
  const dark = scheme === "dark";
  return StyleSheet.create({
    screen: { backgroundColor: dark ? "#08111f" : "#f2f7fc", flex: 1 },
    content: { gap: 18, paddingHorizontal: 18, paddingTop: 14 },
    balanceRow: { alignItems: "center", flexDirection: "row", gap: 16, justifyContent: "space-between", paddingHorizontal: 4 },
    balanceLabel: { color: colors.mutedText, fontSize: 16, fontWeight: "700" },
    balanceValue: { color: colors.text, flexShrink: 1, fontSize: 21, fontWeight: "900" },
    amountCard: { backgroundColor: colors.surface, borderColor: colors.border, borderRadius: 24, borderWidth: 1, gap: 12, padding: 20, shadowColor: "#2563eb", shadowOffset: { width: 0, height: 8 }, shadowOpacity: dark ? 0.14 : 0.04, shadowRadius: 18, elevation: 2 },
    amountLabel: { color: colors.mutedText, fontSize: 16, fontWeight: "700" },
    amountInputRow: { alignItems: "center", flexDirection: "row", gap: 10 },
    amountInput: { color: colors.text, flex: 1, fontSize: 47, fontWeight: "900", letterSpacing: -1.6, minHeight: 74, padding: 0 },
    amountPlaceholder: { color: dark ? "#475569" : "#e2e8f0" },
    amountUnit: { color: colors.mutedText, fontSize: 36, fontWeight: "900" },
    amountCaption: { color: colors.mutedText, fontSize: 13, lineHeight: 19 },
    receiveCaption: { color: "#16a34a", fontSize: 13, fontWeight: "800" },
    sectionLabel: { color: colors.mutedText, fontSize: 16, fontWeight: "900", letterSpacing: 0.6, marginLeft: 4, marginTop: 8 },
    accountCard: { alignItems: "center", backgroundColor: colors.surface, borderColor: colors.border, borderRadius: 20, borderWidth: 1, flexDirection: "row", gap: 12, minHeight: 92, padding: 14 },
    accountIcon: { alignItems: "center", backgroundColor: dark ? "#102a44" : "#eaf5ff", borderRadius: 15, height: 52, justifyContent: "center", width: 52 },
    accountCopy: { flex: 1, gap: 4, minWidth: 0 },
    accountTitleRow: { alignItems: "center", flexDirection: "row", gap: 7 },
    accountTitle: { color: colors.text, flexShrink: 1, fontSize: 16, fontWeight: "900" },
    accountNumber: { color: colors.mutedText, fontSize: 12.5 },
    defaultBadge: { backgroundColor: dark ? "#0d3327" : "#ecfdf5", borderRadius: 999, color: "#16a34a", fontSize: 9, fontWeight: "900", overflow: "hidden", paddingHorizontal: 7, paddingVertical: 3 },
    accountAction: { alignItems: "center", flexDirection: "row", gap: 2, minHeight: 44, paddingLeft: 8 },
    accountActionText: { color: "#2f9af5", fontSize: 12, fontWeight: "900" },
    missingAccountCard: { alignItems: "center", backgroundColor: colors.surface, borderColor: dark ? "#854d0e" : "#fed7aa", borderRadius: 20, borderWidth: 1, flexDirection: "row", gap: 12, minHeight: 92, padding: 14 },
    missingAccountIcon: { alignItems: "center", backgroundColor: dark ? "#3b2910" : "#fff7e6", borderRadius: 15, height: 52, justifyContent: "center", width: 52 },
    missingAccountTitle: { color: dark ? "#fbbf24" : "#f59e0b", fontSize: 16, fontWeight: "900" },
    missingAccountCaption: { color: colors.mutedText, fontSize: 12.5 },
    accountPicker: { backgroundColor: colors.surface, borderColor: colors.border, borderRadius: 18, borderWidth: 1, overflow: "hidden" },
    accountOption: { alignItems: "center", borderBottomColor: colors.border, borderBottomWidth: StyleSheet.hairlineWidth, flexDirection: "row", gap: 12, minHeight: 72, paddingHorizontal: 15 },
    accountOptionSelected: { backgroundColor: dark ? "#102a44" : "#f0f8ff" },
    optionRadio: { alignItems: "center", borderColor: "#2f9af5", borderRadius: 999, borderWidth: 2, height: 20, justifyContent: "center", width: 20 },
    optionRadioDot: { backgroundColor: "#2f9af5", borderRadius: 999, height: 10, width: 10 },
    optionTitle: { color: colors.text, fontSize: 14, fontWeight: "800" },
    optionCaption: { color: colors.mutedText, fontSize: 12 },
    manageAccounts: { alignItems: "center", flexDirection: "row", justifyContent: "space-between", minHeight: 52, paddingHorizontal: 16 },
    manageAccountsText: { color: "#2f9af5", fontSize: 13, fontWeight: "900" },
    otpCard: { backgroundColor: colors.surface, borderColor: colors.border, borderRadius: 20, borderWidth: 1, gap: 10, padding: 16 },
    otpTitle: { color: colors.text, fontSize: 16, fontWeight: "900" },
    otpCaption: { color: colors.mutedText, fontSize: 12.5, lineHeight: 18 },
    otpRow: { alignItems: "center", flexDirection: "row", gap: 10 },
    otpInput: { backgroundColor: dark ? "#111c2c" : "#f8fafc", borderColor: colors.border, borderRadius: 13, borderWidth: 1, color: colors.text, flex: 1, fontSize: 15, minHeight: 48, paddingHorizontal: 13 },
    otpButton: { alignItems: "center", backgroundColor: dark ? "#102a44" : "#eaf5ff", borderRadius: 13, justifyContent: "center", minHeight: 48, minWidth: 112, paddingHorizontal: 12 },
    otpButtonText: { color: "#2f9af5", fontSize: 12, fontWeight: "900" },
    inputError: { borderColor: colors.danger },
    errorText: { color: colors.danger, fontSize: 12.5, lineHeight: 18, marginHorizontal: 4 },
    infoCard: { backgroundColor: colors.surface, borderColor: colors.border, borderRadius: 20, borderWidth: 1, gap: 17, padding: 18 },
    infoRow: { alignItems: "flex-start", flexDirection: "row", gap: 12, minHeight: 24 },
    infoText: { color: colors.mutedText, flex: 1, fontSize: 13, lineHeight: 20 },
    infoStrong: { color: colors.text, fontWeight: "900" },
    footer: { backgroundColor: colors.surface, borderTopColor: colors.border, borderTopWidth: StyleSheet.hairlineWidth, paddingHorizontal: 18, paddingTop: 12 },
    submitButton: { alignItems: "center", backgroundColor: "#2f9af5", borderRadius: 17, justifyContent: "center", minHeight: 58, paddingHorizontal: 18 },
    submitButtonDisabled: { backgroundColor: dark ? "#233348" : "#c7dcef" },
    submitButtonText: { color: "#ffffff", fontSize: 16, fontWeight: "900", textAlign: "center" },
    disabled: { opacity: 0.5 },
    pressed: { opacity: 0.75 },
    successScreen: { alignItems: "center", backgroundColor: dark ? "#08111f" : "#f2f7fc", flex: 1, justifyContent: "center", padding: 20 },
    successCard: { alignItems: "center", backgroundColor: colors.surface, borderColor: colors.border, borderRadius: 24, borderWidth: 1, gap: 16, maxWidth: 420, padding: 24, width: "100%" },
    successMark: { alignItems: "center", backgroundColor: dark ? "#0d3327" : "#ecfdf5", borderRadius: 999, height: 64, justifyContent: "center", width: 64 },
    successMarkText: { color: "#16a34a", fontSize: 30, fontWeight: "900" },
    successTitle: { color: colors.text, fontSize: 21, fontWeight: "900", textAlign: "center" },
    successCaption: { color: colors.mutedText, fontSize: 13, lineHeight: 20, textAlign: "center" },
    successAmount: { color: colors.primary, fontSize: 27, fontWeight: "900" }
  });
}
