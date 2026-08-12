import { useEffect, useMemo, useRef, useState } from "react";
import { useBottomTabBarHeight } from "@react-navigation/bottom-tabs";
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
import { Building2, CheckCircle2, ChevronDown, ChevronRight, CirclePlus, WalletCards } from "lucide-react-native";
import { useSafeAreaInsets } from "react-native-safe-area-context";
import { useAuth } from "@/auth/AuthProvider";
import { EmptyState } from "@/components/AsyncState";
import { InlineError, PrimaryButton } from "@/features/wallet/components";
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
  const tabBarHeight = useBottomTabBarHeight();
  const { user } = useAuth();
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
  const [selectedMethod, setSelectedMethod] = useState<PaymentAccount["payment_method"]>("bank");
  const [selectedAccountId, setSelectedAccountId] = useState<number | null>(null);
  const [showAccountPicker, setShowAccountPicker] = useState(false);
  const [otpCode, setOtpCode] = useState("");
  const [errors, setErrors] = useState<FormErrors>({});
  const [created, setCreated] = useState<WithdrawalCreated | null>(null);

  const config = configQuery.data?.withdraw;
  const authoritativeBalance = accountQuery.data?.wallet.balance;
  const previewBalance = user?.financialSnapshot?.balance;
  const displayBalance = authoritativeBalance ?? previewBalance;
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
  const methodAccounts = useMemo(
    () => accounts.filter((account) => account.payment_method === selectedMethod),
    [accounts, selectedMethod]
  );
  const selectedAccount = methodAccounts.find((account) => account.id === selectedAccountId) ?? null;

  useEffect(() => {
    if (!config || accountsQuery.isPending || didPrefill.current) return;
    didPrefill.current = true;
    const defaultAccount = accounts.find((item) => item.is_default) ?? accounts[0];
    if (defaultAccount) {
      setSelectedMethod(defaultAccount.payment_method);
      setSelectedAccountId(defaultAccount.id);
    } else if (!config.bank_enabled && config.wallet_enabled) {
      setSelectedMethod("wallet");
    }
  }, [accounts, accountsQuery.isPending, config]);

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

  if (configQuery.isSuccess && !configQuery.data.withdraw.enabled) {
    return <EmptyState title="Rút tiền đang tạm tắt" message="Vui lòng quay lại sau khi hệ thống mở lại tính năng." actionLabel="Quay lại" onAction={() => router.back()} />;
  }

  const dependenciesReady = accountQuery.isSuccess && configQuery.isSuccess && accountsQuery.isSuccess;
  const withdrawConfig = configQuery.data?.withdraw;
  const amountIsValid = dependenciesReady && withdrawConfig !== undefined && typeof authoritativeBalance === "number"
    && amount >= withdrawConfig.min_amount && amount <= authoritativeBalance;
  const otpIsValid = dependenciesReady && withdrawConfig !== undefined
    && (!withdrawConfig.otp_required || /^\d{6}$/.test(otpCode));
  const canSubmit = dependenciesReady && Boolean(selectedAccount) && amountIsValid && otpIsValid && !mutation.isPending;
  const footerLabel = !dependenciesReady
    ? "Đang xác nhận dữ liệu rút tiền"
    : !selectedAccount
      ? selectedMethod === "bank" ? "Cần liên kết ngân hàng trước" : "Cần liên kết ví điện tử trước"
      : !amountIsValid
        ? "Nhập số tiền hợp lệ"
        : withdrawConfig?.otp_required && !otpIsValid
          ? "Nhập mã OTP để tiếp tục"
          : "Gửi yêu cầu rút tiền";
  const feeCopy = fee > 0 ? `Phí dự kiến ${formatVnd(fee)}` : "Mê Sale không thu phí xử lý";

  function selectSavedAccount(account: PaymentAccount) {
    setSelectedMethod(account.payment_method);
    setSelectedAccountId(account.id);
    setShowAccountPicker(false);
    setErrors((current) => ({ ...current, account: undefined }));
  }

  function selectMethod(method: PaymentAccount["payment_method"]) {
    if (!withdrawConfig) return;
    const enabled = method === "bank" ? withdrawConfig.bank_enabled : withdrawConfig.wallet_enabled;
    if (!enabled) return;
    const nextAccount = accounts.find((account) => account.payment_method === method && account.is_default)
      ?? accounts.find((account) => account.payment_method === method)
      ?? null;
    setSelectedMethod(method);
    setSelectedAccountId(nextAccount?.id ?? null);
    setShowAccountPicker(false);
    setErrors((current) => ({ ...current, account: undefined }));
  }

  function validateAmount(nextAmount: number): string | undefined {
    if (!withdrawConfig || typeof authoritativeBalance !== "number") return undefined;
    if (nextAmount < withdrawConfig.min_amount) return `Số tiền tối thiểu là ${formatVnd(withdrawConfig.min_amount)}.`;
    if (nextAmount > authoritativeBalance) return "Số dư khả dụng không đủ.";
    return undefined;
  }

  function buildPayload(): WithdrawalPayload | null {
    if (!dependenciesReady || !withdrawConfig) return null;
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
        contentContainerStyle={[styles.content, { paddingBottom: Math.max(tabBarHeight, insets.bottom + 16) + 24 }]}
        contentInsetAdjustmentBehavior="automatic"
        keyboardDismissMode={Platform.OS === "ios" ? "interactive" : "on-drag"}
        keyboardShouldPersistTaps="handled"
        showsVerticalScrollIndicator={false}
      >
        <View style={styles.balanceStrip}>
          <Text style={styles.balanceLabel}>Số dư khả dụng</Text>
          {typeof displayBalance === "number"
            ? <Text adjustsFontSizeToFit numberOfLines={1} style={styles.balanceValue}>{formatVnd(displayBalance)}</Text>
            : <View accessibilityLabel="Đang tải số dư" accessibilityRole="progressbar" style={styles.balanceSkeleton} />}
        </View>

        {!dependenciesReady ? (
          <View style={styles.dependencyNotice}>
            <ActivityIndicator color="#2f9af5" size="small" />
            <Text style={styles.dependencyText}>Đang xác nhận số dư, chính sách rút và tài khoản nhận tiền từ Mê Sale.</Text>
          </View>
        ) : null}
        {accountQuery.isError ? <InlineError error={accountQuery.error} onRetry={() => void accountQuery.refetch()} /> : null}
        {configQuery.isError ? <InlineError error={configQuery.error} onRetry={() => void configQuery.refetch()} /> : null}
        {accountsQuery.isError ? <InlineError error={accountsQuery.error} onRetry={() => void accountsQuery.refetch()} /> : null}

        <View style={styles.formCard}>
          <View style={styles.fieldGroup}>
            <Text style={styles.fieldLabel}>SỐ TIỀN CẦN RÚT (VND)</Text>
            <View style={[styles.inputShell, errors.amount && styles.inputError]}>
              <WalletCards color={colors.mutedText} size={19} />
              <TextInput
                accessibilityLabel="Số tiền cần rút"
                editable={dependenciesReady}
                keyboardType="number-pad"
                onBlur={() => setErrors((current) => ({ ...current, amount: amountText ? validateAmount(amount) : undefined }))}
                onChangeText={(value) => {
                  const normalized = value.replace(/\D/g, "");
                  const nextAmount = Number(normalized) || 0;
                  setAmountText(normalized);
                  setErrors((current) => ({ ...current, amount: normalized ? validateAmount(nextAmount) : undefined }));
                }}
                placeholder="Ví dụ: 50000"
                placeholderTextColor={colors.mutedText}
                selectionColor="#2f9af5"
                style={styles.textInput}
                value={amountInput(amount)}
              />
            </View>
            <View style={styles.helperRow}>
              {withdrawConfig ? (
                <>
                  <Text style={styles.helperText}>Số tiền tối thiểu: <Text style={styles.helperStrong}>{formatVnd(withdrawConfig.min_amount)}</Text></Text>
                  <Text style={styles.helperDot}>·</Text>
                  <Text style={styles.helperText}>{feeCopy}</Text>
                </>
              ) : <View accessibilityLabel="Đang tải chính sách rút tiền" accessibilityRole="progressbar" style={styles.helperSkeleton} />}
            </View>
            {amount > 0 ? <Text style={styles.receiveCaption}>Dự kiến thực nhận: {formatVnd(Math.max(0, amount - fee))}</Text> : null}
            {errors.amount ? <Text accessibilityRole="alert" style={styles.errorText}>{errors.amount}</Text> : null}
          </View>

          <View style={styles.fieldGroup}>
            <Text style={styles.fieldLabel}>HÌNH THỨC NHẬN TIỀN</Text>
            <View accessibilityRole="radiogroup" style={styles.methodRow}>
              {(["bank", "wallet"] as const).map((method) => {
                const enabled = dependenciesReady && withdrawConfig !== undefined
                  ? (method === "bank" ? withdrawConfig.bank_enabled : withdrawConfig.wallet_enabled)
                  : false;
                const selected = selectedMethod === method;
                const MethodIcon = method === "bank" ? Building2 : WalletCards;
                return (
                  <Pressable
                    accessibilityRole="radio"
                    accessibilityState={{ checked: selected, disabled: !enabled }}
                    disabled={!enabled}
                    key={method}
                    onPress={() => selectMethod(method)}
                    style={({ pressed }) => [styles.methodOption, selected && styles.methodOptionSelected, !enabled && styles.disabled, pressed && styles.pressed]}
                  >
                    <MethodIcon color={selected ? "#2f9af5" : colors.mutedText} size={19} />
                    <View style={styles.methodCopy}>
                      <Text style={[styles.methodTitle, selected && styles.methodTitleSelected]}>{method === "bank" ? "Ngân hàng" : "Ví điện tử"}</Text>
                      {!enabled ? <Text style={styles.unavailableText}>Chưa hỗ trợ</Text> : null}
                    </View>
                    {selected ? <CheckCircle2 color="#2f9af5" size={19} /> : <View style={styles.radioRing} />}
                  </Pressable>
                );
              })}
            </View>
          </View>

          <View style={styles.fieldGroup}>
            <Text style={styles.fieldLabel}>{selectedMethod === "bank" ? "TÊN NGÂN HÀNG NHẬN" : "TÊN VÍ ĐIỆN TỬ"}</Text>
            {methodAccounts.length > 0 ? (
              <Pressable
                accessibilityLabel="Chọn tài khoản nhận tiền đã lưu"
                accessibilityRole="button"
                onPress={() => setShowAccountPicker((value) => !value)}
                style={({ pressed }) => [styles.inputShell, errors.account && styles.inputError, pressed && styles.pressed]}
              >
                {selectedMethod === "bank" ? <Building2 color={colors.mutedText} size={19} /> : <WalletCards color={colors.mutedText} size={19} />}
                <Text numberOfLines={1} style={[styles.selectText, !selectedAccount && styles.placeholderText]}>
                  {selectedAccount ? accountMethodLabel(selectedAccount) : `-- Chọn ${selectedMethod === "bank" ? "ngân hàng" : "ví điện tử"} --`}
                </Text>
                <ChevronDown color={colors.mutedText} size={19} />
              </Pressable>
            ) : (
              <Pressable
                accessibilityLabel="Thêm tài khoản nhận tiền"
                accessibilityRole="button"
                onPress={() => router.push("/(tabs)/wallet/payment-accounts/create")}
                style={({ pressed }) => [styles.addAccountButton, pressed && styles.pressed]}
              >
                <CirclePlus color="#f59e0b" size={21} />
                <Text style={styles.addAccountText}>Thêm {selectedMethod === "bank" ? "tài khoản ngân hàng" : "ví điện tử"}</Text>
                <ChevronRight color={colors.mutedText} size={19} />
              </Pressable>
            )}
            {errors.account ? <Text accessibilityRole="alert" style={styles.errorText}>{errors.account}</Text> : null}
          </View>

          {showAccountPicker && methodAccounts.length > 0 ? (
            <View accessibilityRole="radiogroup" style={styles.accountPicker}>
              {methodAccounts.map((account) => {
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
                      <Text numberOfLines={1} style={styles.optionCaption}>{accountSuffix(account.account_number)} · {account.account_name}</Text>
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

          <View style={styles.fieldGroup}>
            <Text style={styles.fieldLabel}>{selectedMethod === "bank" ? "SỐ TÀI KHOẢN NGÂN HÀNG" : "SỐ ĐIỆN THOẠI NHẬN TIỀN"}</Text>
            <View style={styles.readOnlyInput}>
              <Text numberOfLines={1} style={[styles.readOnlyText, !selectedAccount && styles.placeholderText]}>{selectedAccount?.account_number || "Chọn tài khoản nhận tiền"}</Text>
            </View>
          </View>

          <View style={styles.fieldGroup}>
            <Text style={styles.fieldLabel}>HỌ TÊN CHỦ TÀI KHOẢN</Text>
            <View style={styles.readOnlyInput}>
              <Text numberOfLines={1} style={[styles.readOnlyText, !selectedAccount && styles.placeholderText]}>{selectedAccount?.account_name.trim().toUpperCase() || "Tên chủ tài khoản đã xác minh"}</Text>
            </View>
          </View>

          {withdrawConfig?.otp_required ? (
            <View style={styles.fieldGroup}>
              <Text style={styles.fieldLabel}>MÃ XÁC MINH OTP <Text style={styles.requiredMark}>*</Text></Text>
              <View style={styles.otpRow}>
                <TextInput
                  accessibilityLabel="Mã xác minh OTP"
                  keyboardType="number-pad"
                  maxLength={6}
                  onChangeText={(value) => {
                    setOtpCode(value.replace(/\D/g, "").slice(0, 6));
                    setErrors((current) => ({ ...current, otp: undefined }));
                  }}
                  placeholder="Nhập mã OTP 6 số..."
                  placeholderTextColor={colors.mutedText}
                  style={[styles.otpInput, errors.otp && styles.inputError]}
                  value={otpCode}
                />
                <Pressable
                  accessibilityRole="button"
                  accessibilityState={{ busy: otpMutation.isPending }}
                  disabled={!dependenciesReady || otpMutation.isPending}
                  onPress={() => otpMutation.mutate()}
                  style={({ pressed }) => [styles.otpButton, (!dependenciesReady || otpMutation.isPending) && styles.disabled, pressed && styles.pressed]}
                >
                  {otpMutation.isPending
                    ? <ActivityIndicator color="#2f9af5" />
                    : <Text style={styles.otpButtonText}>{otpMutation.isSuccess ? "Gửi lại OTP" : "Gửi mã OTP"}</Text>}
                </Pressable>
              </View>
              <Text style={styles.helperText}>Mã OTP được gửi về email đăng ký tài khoản của bạn.</Text>
              {errors.otp ? <Text accessibilityRole="alert" style={styles.errorText}>{errors.otp}</Text> : null}
              <InlineError error={otpMutation.error} onRetry={() => otpMutation.mutate()} />
            </View>
          ) : null}

          <InlineError error={mutation.error} onRetry={() => void submit()} />

          <Pressable
            accessibilityRole="button"
            accessibilityState={{ busy: mutation.isPending, disabled: !canSubmit }}
            disabled={!canSubmit}
            onPress={() => void submit()}
            style={({ pressed }) => [styles.submitButton, !canSubmit && styles.submitButtonDisabled, pressed && styles.pressed]}
          >
            {mutation.isPending ? <ActivityIndicator color="#ffffff" /> : <Text style={styles.submitButtonText}>{footerLabel}</Text>}
          </Pressable>

          <Text style={styles.securityNote}>Tiền chỉ chuyển về tài khoản đã lưu trong hồ sơ. Số dư, mức tối thiểu và phí được Mê Sale xác nhận khi gửi yêu cầu.</Text>
        </View>
      </ScrollView>
    </KeyboardAvoidingView>
  );
}

function createStyles(colors: Theme["colors"], scheme: Theme["scheme"]) {
  const dark = scheme === "dark";
  return StyleSheet.create({
    screen: { backgroundColor: dark ? "#08111f" : "#f2f7fc", flex: 1 },
    content: { gap: 12, paddingHorizontal: 16, paddingTop: 10 },
    balanceStrip: { alignItems: "center", backgroundColor: colors.surface, borderColor: colors.border, borderRadius: 14, borderWidth: 1, flexDirection: "row", gap: 14, justifyContent: "space-between", minHeight: 48, paddingHorizontal: 14, paddingVertical: 10 },
    balanceLabel: { color: colors.mutedText, fontSize: 13, fontWeight: "700" },
    balanceValue: { color: colors.text, flexShrink: 1, fontSize: 17, fontWeight: "900" },
    balanceSkeleton: { backgroundColor: colors.border, borderRadius: 6, height: 20, width: 118 },
    dependencyNotice: { alignItems: "center", backgroundColor: dark ? "#102a44" : "#eaf5ff", borderColor: dark ? "#1e4a70" : "#bfdbfe", borderRadius: 12, borderWidth: 1, flexDirection: "row", gap: 10, minHeight: 48, paddingHorizontal: 13, paddingVertical: 10 },
    dependencyText: { color: colors.mutedText, flex: 1, fontSize: 11.5, lineHeight: 17 },
    formCard: { backgroundColor: colors.surface, borderColor: colors.border, borderRadius: 18, borderWidth: 1, gap: 18, padding: 16, shadowColor: "#2563eb", shadowOffset: { width: 0, height: 8 }, shadowOpacity: dark ? 0.12 : 0.035, shadowRadius: 16, elevation: 2 },
    fieldGroup: { gap: 8 },
    fieldLabel: { color: colors.text, fontSize: 12, fontWeight: "900", letterSpacing: 0.35 },
    inputShell: { alignItems: "center", backgroundColor: dark ? "#111c2c" : "#fbfcfe", borderColor: colors.border, borderRadius: 12, borderWidth: 1, flexDirection: "row", gap: 10, minHeight: 48, paddingHorizontal: 13 },
    textInput: { color: colors.text, flex: 1, fontSize: 14.5, fontWeight: "700", minHeight: 46, padding: 0 },
    helperRow: { alignItems: "center", flexDirection: "row", flexWrap: "wrap", gap: 5 },
    helperText: { color: colors.mutedText, fontSize: 11.5, lineHeight: 17 },
    helperStrong: { color: colors.text, fontWeight: "900" },
    helperSkeleton: { backgroundColor: colors.border, borderRadius: 5, height: 14, width: 210 },
    helperDot: { color: colors.mutedText, fontSize: 11.5 },
    receiveCaption: { color: "#16a34a", fontSize: 11.5, fontWeight: "800", lineHeight: 17 },
    methodRow: { flexDirection: "row", gap: 10 },
    methodOption: { alignItems: "center", backgroundColor: dark ? "#111c2c" : "#fbfcfe", borderColor: colors.border, borderRadius: 12, borderWidth: 1, flex: 1, flexDirection: "row", gap: 8, minHeight: 50, minWidth: 0, paddingHorizontal: 11, paddingVertical: 8 },
    methodOptionSelected: { backgroundColor: dark ? "#102a44" : "#f0f8ff", borderColor: "#2f9af5" },
    methodCopy: { flex: 1, gap: 1, minWidth: 0 },
    methodTitle: { color: colors.text, fontSize: 12.5, fontWeight: "800" },
    methodTitleSelected: { color: "#2f9af5" },
    unavailableText: { color: colors.mutedText, fontSize: 9.5, lineHeight: 13 },
    radioRing: { borderColor: colors.border, borderRadius: 999, borderWidth: 1.5, height: 18, width: 18 },
    selectText: { color: colors.text, flex: 1, fontSize: 13.5, fontWeight: "700" },
    placeholderText: { color: colors.mutedText, fontWeight: "600" },
    addAccountButton: { alignItems: "center", backgroundColor: dark ? "#302512" : "#fffaf0", borderColor: dark ? "#854d0e" : "#fed7aa", borderRadius: 12, borderWidth: 1, flexDirection: "row", gap: 10, minHeight: 50, paddingHorizontal: 13 },
    addAccountText: { color: dark ? "#fbbf24" : "#d97706", flex: 1, fontSize: 13, fontWeight: "900" },
    accountCopy: { flex: 1, gap: 4, minWidth: 0 },
    accountPicker: { backgroundColor: dark ? "#111c2c" : "#fbfcfe", borderColor: colors.border, borderRadius: 12, borderWidth: 1, overflow: "hidden" },
    accountOption: { alignItems: "center", borderBottomColor: colors.border, borderBottomWidth: StyleSheet.hairlineWidth, flexDirection: "row", gap: 10, minHeight: 60, paddingHorizontal: 13 },
    accountOptionSelected: { backgroundColor: dark ? "#102a44" : "#f0f8ff" },
    optionRadio: { alignItems: "center", borderColor: "#2f9af5", borderRadius: 999, borderWidth: 1.5, height: 18, justifyContent: "center", width: 18 },
    optionRadioDot: { backgroundColor: "#2f9af5", borderRadius: 999, height: 9, width: 9 },
    optionTitle: { color: colors.text, fontSize: 13, fontWeight: "800" },
    optionCaption: { color: colors.mutedText, fontSize: 11.5 },
    manageAccounts: { alignItems: "center", flexDirection: "row", justifyContent: "space-between", minHeight: 48, paddingHorizontal: 13 },
    manageAccountsText: { color: "#2f9af5", fontSize: 12.5, fontWeight: "900" },
    readOnlyInput: { backgroundColor: dark ? "#0d1725" : "#f6f8fb", borderColor: colors.border, borderRadius: 12, borderWidth: 1, justifyContent: "center", minHeight: 48, paddingHorizontal: 13 },
    readOnlyText: { color: colors.text, fontSize: 13.5, fontWeight: "700" },
    requiredMark: { color: colors.danger },
    otpRow: { alignItems: "center", flexDirection: "row", gap: 10 },
    otpInput: { backgroundColor: dark ? "#111c2c" : "#fbfcfe", borderColor: colors.border, borderRadius: 12, borderWidth: 1, color: colors.text, flex: 1, fontSize: 13.5, minHeight: 48, minWidth: 0, paddingHorizontal: 13 },
    otpButton: { alignItems: "center", backgroundColor: dark ? "#102a44" : "#eaf5ff", borderRadius: 12, justifyContent: "center", minHeight: 48, minWidth: 106, paddingHorizontal: 11 },
    otpButtonText: { color: "#2f9af5", fontSize: 11.5, fontWeight: "900" },
    inputError: { borderColor: colors.danger },
    errorText: { color: colors.danger, fontSize: 11.5, lineHeight: 17, marginHorizontal: 2 },
    submitButton: { alignItems: "center", backgroundColor: "#2f9af5", borderRadius: 12, justifyContent: "center", minHeight: 52, paddingHorizontal: 16 },
    submitButtonDisabled: { backgroundColor: dark ? "#233348" : "#c7dcef" },
    submitButtonText: { color: "#ffffff", fontSize: 14, fontWeight: "900", textAlign: "center" },
    securityNote: { color: colors.mutedText, fontSize: 11, lineHeight: 17, textAlign: "center" },
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
