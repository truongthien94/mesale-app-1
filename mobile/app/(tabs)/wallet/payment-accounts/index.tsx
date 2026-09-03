import { useCallback, useState } from "react";
import { useRouter } from "expo-router";
import { Landmark } from "lucide-react-native";
import { Alert, FlatList, Pressable, StyleSheet, Text, View, type ListRenderItemInfo } from "react-native";
import { useSafeAreaInsets } from "react-native-safe-area-context";
import { EmptyState, LoadingState } from "@/components/AsyncState";
import { CompactBlueHero } from "@/components/CompactBlueHero";
import { InlineError, PageFrame, PrimaryButton, QueryFailure } from "@/features/wallet/components";
import { useDeletePaymentAccount, usePaymentAccounts, useSetDefaultPaymentAccount } from "@/features/wallet/api";
import type { PaymentAccount } from "@/features/wallet/types";
import { colors, spacing, theme } from "@/theme/tokens";

export default function PaymentAccountsScreen() {
  const router = useRouter();
  const insets = useSafeAreaInsets();
  const query = usePaymentAccounts();
  const setDefault = useSetDefaultPaymentAccount();
  const remove = useDeletePaymentAccount();
  const [activeId, setActiveId] = useState<number | null>(null);
  const accounts = query.data?.items ?? [];

  const confirmDelete = useCallback((item: PaymentAccount) => {
    Alert.alert(
      "Xóa tài khoản đã lưu?",
      `${item.bank_name} · ${item.account_number}\nThao tác này không thể hoàn tác.`,
      [
        { text: "Hủy", style: "cancel" },
        {
          text: "Xóa",
          style: "destructive",
          onPress: () => {
            setActiveId(item.id);
            remove.mutate(item.id, { onSettled: () => setActiveId(null) });
          }
        }
      ]
    );
  }, [remove]);

  const renderItem = useCallback(({ item }: ListRenderItemInfo<PaymentAccount>) => (
    <View style={styles.card}>
      <View style={styles.topRow}>
        <View style={styles.copy}>
          <View style={styles.bankRow}>
            <Text style={styles.bank}>{item.bank_name}</Text>
            {item.is_default ? <View style={styles.defaultBadge}><Text style={styles.defaultText}>Mặc định</Text></View> : null}
          </View>
          <Text selectable style={styles.number}>{item.account_number}</Text>
          <Text style={styles.name}>{item.account_name}</Text>
          <Text style={styles.method}>{item.payment_method === "bank" ? "Tài khoản ngân hàng" : "Ví điện tử"}</Text>
        </View>
      </View>
      <View style={styles.actions}>
        {!item.is_default ? (
          <Pressable
            accessibilityRole="button"
            disabled={setDefault.isPending || remove.isPending}
            onPress={() => {
              setActiveId(item.id);
              setDefault.mutate(item.id, { onSettled: () => setActiveId(null) });
            }}
            style={({ pressed }) => [styles.actionButton, pressed && styles.pressed]}
          >
            <Text style={styles.actionText}>{setDefault.isPending && activeId === item.id ? "Đang lưu..." : "Đặt mặc định"}</Text>
          </Pressable>
        ) : <View />}
        <Pressable
          accessibilityRole="button"
          disabled={setDefault.isPending || remove.isPending}
          onPress={() => confirmDelete(item)}
          style={({ pressed }) => [styles.deleteButton, pressed && styles.pressed]}
        >
          <Text style={styles.deleteText}>{remove.isPending && activeId === item.id ? "Đang xóa..." : "Xóa"}</Text>
        </Pressable>
      </View>
    </View>
  ), [activeId, confirmDelete, remove.isPending, setDefault]);

  if (query.isPending) return <LoadingState label="Đang tải tài khoản nhận tiền..." />;
  if (query.isError) return <QueryFailure error={query.error} onRetry={() => void query.refetch()} />;

  return (
    <PageFrame>
      <FlatList
        contentContainerStyle={[styles.content, { paddingBottom: spacing.xl + insets.bottom }, accounts.length === 0 && styles.emptyContent]}
        contentInsetAdjustmentBehavior="automatic"
        data={accounts}
        keyExtractor={(item) => String(item.id)}
        ListHeaderComponent={
          <View style={styles.header}>
            <CompactBlueHero icon={Landmark} subtitle="Quản lý ngân hàng và ví điện tử dùng khi rút tiền." title="Tài khoản nhận tiền" />
            <PrimaryButton disabled={accounts.length >= 10} label={accounts.length >= 10 ? "Đã đạt giới hạn 10 tài khoản" : "Thêm tài khoản nhận tiền"} onPress={() => router.push("/(tabs)/wallet/payment-accounts/create")} />
            <InlineError error={setDefault.error ?? remove.error} />
          </View>
        }
        ListEmptyComponent={<EmptyState title="Chưa lưu tài khoản nhận tiền" message="Thêm ngân hàng hoặc ví để điền nhanh khi rút tiền." />}
        onRefresh={() => void query.refetch()}
        refreshing={query.isRefetching}
        renderItem={renderItem}
      />
    </PageFrame>
  );
}

const styles = StyleSheet.create({
  content: {
    gap: spacing.md,
    padding: spacing.md
  },
  emptyContent: {
    flexGrow: 1
  },
  header: {
    gap: spacing.sm,
    marginBottom: spacing.sm
  },
  card: {
    backgroundColor: colors.surface,
    borderColor: colors.border,
    borderRadius: theme.radius.lg,
    borderWidth: 1,
    gap: spacing.md,
    padding: spacing.md
  },
  topRow: {
    flexDirection: "row",
    justifyContent: "space-between"
  },
  copy: {
    flex: 1,
    gap: spacing.xs
  },
  bankRow: {
    alignItems: "center",
    flexDirection: "row",
    flexWrap: "wrap",
    gap: spacing.sm
  },
  bank: {
    color: colors.text,
    fontSize: 15,
    fontWeight: "900"
  },
  defaultBadge: {
    backgroundColor: "#fff7ed",
    borderColor: "#fed7aa",
    borderRadius: 999,
    borderWidth: 1,
    paddingHorizontal: 8,
    paddingVertical: 3
  },
  defaultText: {
    color: colors.primary,
    fontSize: 9,
    fontWeight: "800"
  },
  number: {
    color: "#334155",
    fontSize: 14,
    fontWeight: "800",
    letterSpacing: 0.5
  },
  name: {
    color: colors.mutedText,
    fontSize: 11,
    textTransform: "uppercase"
  },
  method: {
    color: colors.mutedText,
    fontSize: 10
  },
  actions: {
    alignItems: "center",
    borderTopColor: colors.border,
    borderTopWidth: StyleSheet.hairlineWidth,
    flexDirection: "row",
    justifyContent: "space-between",
    paddingTop: spacing.sm
  },
  actionButton: {
    minHeight: 40,
    justifyContent: "center",
    paddingHorizontal: spacing.sm
  },
  actionText: {
    color: colors.primary,
    fontSize: 12,
    fontWeight: "800"
  },
  deleteButton: {
    minHeight: 40,
    justifyContent: "center",
    paddingHorizontal: spacing.sm
  },
  deleteText: {
    color: colors.danger,
    fontSize: 12,
    fontWeight: "800"
  },
  pressed: {
    opacity: 0.65
  }
});
