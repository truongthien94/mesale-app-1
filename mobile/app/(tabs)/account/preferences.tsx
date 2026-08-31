import { useEffect, useState } from "react";
import { Pressable, StyleSheet, Text, View } from "react-native";
import { ApiError } from "@/api/client";
import { useAuth } from "@/auth/AuthProvider";
import { ErrorState, LoadingState, OfflineState } from "@/components/AsyncState";
import { useIosPayoutFeaturesEnabled } from "@/config/features";
import { useAccount, useUpdatePreferences } from "@/features/account/api";
import { AccountButton, AccountCard, AccountFormScreen, AccountHeader, AccountMutationError, AccountNotice, accountStyles } from "@/features/account/components";
import { colors, spacing, theme } from "@/theme/tokens";

const locales = [{ value: "vi", label: "Tiếng Việt" }, { value: "en", label: "English" }];
const currencies = [{ value: "VND", label: "VND · Việt Nam Đồng" }, { value: "USD", label: "USD · Đô la Mỹ" }];

export default function PreferencesScreen() {
  const payoutFeaturesEnabled = useIosPayoutFeaturesEnabled();
  const query = useAccount();
  const mutation = useUpdatePreferences();
  const { refreshUser } = useAuth();
  const [locale, setLocale] = useState("vi");
  const [currency, setCurrency] = useState("VND");

  useEffect(() => {
    if (query.data) {
      setLocale(query.data.preferences.locale);
      setCurrency(query.data.preferences.currency);
    }
  }, [query.data]);

  if (query.isPending) return <LoadingState label="Đang tải tùy chọn..." />;
  if (query.isError) {
    const props = { title: "Không thể tải tùy chọn", message: query.error instanceof Error ? query.error.message : undefined, actionLabel: "Thử lại", onAction: () => void query.refetch() };
    return query.error instanceof ApiError && query.error.isNetworkError ? <OfflineState {...props} /> : <ErrorState {...props} />;
  }

  async function submit() {
    try {
      await mutation.mutateAsync({ locale, currency });
      await refreshUser();
    } catch {
      // The mutation exposes normalized server and field errors below.
    }
  }

  return (
    <AccountFormScreen>
      <AccountHeader
        title={payoutFeaturesEnabled ? "Ngôn ngữ & tiền tệ" : "Ngôn ngữ"}
        subtitle={payoutFeaturesEnabled
          ? "Đây là tùy chọn hiển thị. Số dư và mọi giao dịch tài chính trên máy chủ vẫn được hạch toán bằng VND nguyên."
          : "Chọn ngôn ngữ dùng để hiển thị nội dung trong ứng dụng."}
      />
      <AccountCard>
        <View style={accountStyles.section}>
          <Text style={accountStyles.strong}>Ngôn ngữ</Text>
          <View style={accountStyles.options}>{locales.map((item) => <Choice key={item.value} label={item.label} selected={locale === item.value} onPress={() => setLocale(item.value)} />)}</View>
        </View>
        {payoutFeaturesEnabled ? (
          <View style={accountStyles.section}>
            <Text style={accountStyles.strong}>Tiền tệ hiển thị</Text>
            <View style={accountStyles.options}>{currencies.map((item) => <Choice key={item.value} label={item.label} selected={currency === item.value} onPress={() => setCurrency(item.value)} />)}</View>
          </View>
        ) : null}
        <AccountMutationError error={mutation.error} />
        {mutation.isSuccess ? <AccountNotice tone="success">{mutation.data.message ?? "Đã cập nhật tùy chọn hiển thị."}</AccountNotice> : null}
        <AccountButton label="Lưu tùy chọn" loading={mutation.isPending} onPress={() => void submit()} />
      </AccountCard>
    </AccountFormScreen>
  );
}

function Choice({ label, selected, onPress }: { label: string; selected: boolean; onPress(): void }) {
  return (
    <Pressable accessibilityRole="radio" accessibilityState={{ checked: selected }} onPress={onPress} style={({ pressed }) => [styles.choice, selected && styles.selected, pressed && styles.pressed]}>
      <Text style={[styles.choiceText, selected && styles.selectedText]}>{label}</Text>
    </Pressable>
  );
}

const styles = StyleSheet.create({
  choice: { borderColor: colors.border, borderRadius: theme.radius.md, borderWidth: 1, paddingHorizontal: spacing.md, paddingVertical: 12 },
  selected: { backgroundColor: "#fff7ed", borderColor: colors.primary },
  choiceText: { color: colors.mutedText, fontSize: 13, fontWeight: "700" },
  selectedText: { color: colors.primary },
  pressed: { opacity: 0.78 }
});
