import { useEffect, useMemo, useState } from "react";
import { useRouter } from "expo-router";
import { ChevronDown, ChevronUp, Check, Search } from "lucide-react-native";
import { KeyboardAvoidingView, Platform, Pressable, ScrollView, StyleSheet, Switch, Text, TextInput, View } from "react-native";
import { useSafeAreaInsets } from "react-native-safe-area-context";
import { EmptyState, LoadingState } from "@/components/AsyncState";
import { IosPayoutRouteGuard } from "@/components/IosPayoutRouteGuard";
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
  return <IosPayoutRouteGuard><CreatePaymentAccountContent /></IosPayoutRouteGuard>;
}

function CreatePaymentAccountContent() {
  const router = useRouter();
  const insets = useSafeAreaInsets();
  const configQuery = useAppConfig();
  const mutation = useCreatePaymentAccount();
  const stableSubmission = useStableSubmission("payment-account.create", paymentAccountFingerprint);
  const [method, setMethod] = useState<"bank" | "wallet">("bank");
  const [destination, setDestination] = useState("");
  const [destinationSearch, setDestinationSearch] = useState("");
  const [showDestinationPicker, setShowDestinationPicker] = useState(false);
  const [accountNumber, setAccountNumber] = useState("");
  const [accountName, setAccountName] = useState("");
  const [isDefault, setIsDefault] = useState(false);
  const [errors, setErrors] = useState<FormErrors>({});

  const destinations = useMemo(() => {
    if (!configQuery.data) return [];
    return method === "bank" ? configQuery.data.withdraw.allowed_banks : configQuery.data.withdraw.allowed_wallets;
  }, [configQuery.data, method]);
  const filteredDestinations = useMemo(() => {
    const query = destinationSearch.trim().toLocaleLowerCase("vi-VN");
    if (!query) return destinations;
    return destinations.filter((item) => item.toLocaleLowerCase("vi-VN").includes(query));
  }, [destinationSearch, destinations]);

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
              onChange={(value) => {
                setMethod(value as "bank" | "wallet");
                setDestination("");
                setDestinationSearch("");
                setShowDestinationPicker(false);
                setErrors({});
              }}
              options={[
                { label: "Ngân hàng", value: "bank", disabled: !configQuery.data.withdraw.bank_enabled },
                { label: "Ví điện tử", value: "wallet", disabled: !configQuery.data.withdraw.wallet_enabled }
              ]}
              value={method}
            />
            <View style={styles.destinationGroup}>
              <Text style={styles.fieldLabel}>{method === "bank" ? "Ngân hàng" : "Ví điện tử"}</Text>
              <Pressable
                accessibilityLabel={method === "bank" ? "Chọn ngân hàng nhận tiền" : "Chọn ví điện tử nhận tiền"}
                accessibilityRole="button"
                accessibilityState={{ expanded: showDestinationPicker }}
                onPress={() => setShowDestinationPicker((current) => !current)}
                style={({ pressed }) => [styles.destinationTrigger, pressed && styles.buttonPressed]}
              >
                <Text numberOfLines={1} style={[styles.destinationValue, !destination && styles.destinationPlaceholder]}>
                  {destination || `-- Chọn ${method === "bank" ? "ngân hàng nhận" : "ví điện tử nhận"} --`}
                </Text>
                {showDestinationPicker ? <ChevronUp color={colors.mutedText} size={18} /> : <ChevronDown color={colors.mutedText} size={18} />}
              </Pressable>
              {showDestinationPicker ? (
                <View accessibilityRole="menu" style={styles.destinationPanel}>
                  <View style={styles.destinationSearchShell}>
                    <Search color={colors.mutedText} size={17} />
                    <TextInput
                      accessibilityLabel="Tìm ngân hàng hoặc ví điện tử"
                      autoCapitalize="none"
                      autoCorrect={false}
                      onChangeText={setDestinationSearch}
                      placeholder={method === "bank" ? "Tìm ngân hàng..." : "Tìm ví điện tử..."}
                      placeholderTextColor={colors.mutedText}
                      style={styles.destinationSearchInput}
                      value={destinationSearch}
                    />
                  </View>
                  <ScrollView
                    accessibilityLabel="Danh sách ngân hàng hoặc ví điện tử"
                    keyboardShouldPersistTaps="handled"
                    nestedScrollEnabled
                    showsVerticalScrollIndicator
                    style={styles.destinationOptions}
                  >
                    {filteredDestinations.length > 0 ? filteredDestinations.map((item) => {
                      const selected = item === destination;
                      return (
                        <Pressable
                          accessibilityRole="radio"
                          accessibilityState={{ checked: selected }}
                          key={item}
                          onPress={() => {
                            setDestination(item);
                            setDestinationSearch("");
                            setShowDestinationPicker(false);
                            setErrors((current) => ({ ...current, destination: undefined }));
                          }}
                          style={({ pressed }) => [styles.destinationOption, selected && styles.destinationOptionSelected, pressed && styles.buttonPressed]}
                        >
                          <Text style={styles.destinationOptionText}>{item}</Text>
                          {selected ? <Check color={colors.primary} size={17} /> : null}
                        </Pressable>
                      );
                    }) : <Text style={styles.destinationEmpty}>Không tìm thấy lựa chọn phù hợp.</Text>}
                  </ScrollView>
                </View>
              ) : null}
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
  fieldLabel: {
    color: "#334155",
    fontSize: 12,
    fontWeight: "800",
    letterSpacing: 0.35,
    textTransform: "uppercase"
  },
  destinationTrigger: {
    alignItems: "center",
    backgroundColor: "#f8fafc",
    borderColor: colors.border,
    borderRadius: theme.radius.md,
    borderWidth: 1,
    flexDirection: "row",
    gap: spacing.sm,
    justifyContent: "space-between",
    minHeight: 50,
    paddingHorizontal: spacing.md
  },
  destinationValue: {
    color: colors.text,
    flex: 1,
    fontSize: 14,
    fontWeight: "700"
  },
  destinationPlaceholder: {
    color: colors.mutedText,
    fontWeight: "600"
  },
  destinationPanel: {
    backgroundColor: colors.surface,
    borderColor: colors.primary,
    borderRadius: theme.radius.md,
    borderWidth: 1,
    gap: spacing.sm,
    padding: spacing.sm
  },
  destinationSearchShell: {
    alignItems: "center",
    backgroundColor: "#f8fafc",
    borderColor: colors.primary,
    borderRadius: theme.radius.md,
    borderWidth: 1,
    flexDirection: "row",
    gap: spacing.sm,
    minHeight: 44,
    paddingHorizontal: spacing.sm
  },
  destinationSearchInput: {
    color: colors.text,
    flex: 1,
    fontSize: 13,
    minHeight: 42,
    padding: 0
  },
  destinationOptions: {
    maxHeight: 224
  },
  destinationOption: {
    alignItems: "center",
    borderBottomColor: colors.border,
    borderBottomWidth: StyleSheet.hairlineWidth,
    flexDirection: "row",
    gap: spacing.sm,
    justifyContent: "space-between",
    minHeight: 48,
    paddingHorizontal: spacing.sm
  },
  destinationOptionSelected: {
    backgroundColor: "#fff7ed"
  },
  destinationOptionText: {
    color: colors.text,
    flex: 1,
    fontSize: 13,
    fontWeight: "700"
  },
  destinationEmpty: {
    color: colors.mutedText,
    fontSize: 12,
    paddingHorizontal: spacing.sm,
    paddingVertical: spacing.md,
    textAlign: "center"
  },
  buttonPressed: {
    opacity: 0.78
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
