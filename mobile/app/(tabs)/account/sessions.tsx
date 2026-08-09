import { useState } from "react";
import { FlatList, StyleSheet, Text, View } from "react-native";
import { useSafeAreaInsets } from "react-native-safe-area-context";
import { ApiError } from "@/api/client";
import { EmptyState, ErrorState, LoadingState, OfflineState } from "@/components/AsyncState";
import { useRevokeOtherSessions, useRevokeSession, useSessions } from "@/features/account/api";
import { AccountButton, AccountCard, AccountHeader, AccountMutationError, AccountNotice, accountStyles } from "@/features/account/components";
import type { AccountSession } from "@/features/account/types";
import { colors, spacing } from "@/theme/tokens";

function formatDate(value: string | null): string {
  if (!value) return "Chưa ghi nhận";
  const date = new Date(value);
  return Number.isNaN(date.getTime()) ? value : new Intl.DateTimeFormat("vi-VN", { dateStyle: "medium", timeStyle: "short" }).format(date);
}

export default function SessionsScreen() {
  const insets = useSafeAreaInsets();
  const query = useSessions();
  const revoke = useRevokeSession();
  const revokeOthers = useRevokeOtherSessions();
  const [notice, setNotice] = useState<string>();

  if (query.isPending) return <LoadingState label="Đang tải phiên đăng nhập..." />;
  if (query.isError) {
    const props = { title: "Không thể tải phiên đăng nhập", message: query.error instanceof Error ? query.error.message : undefined, actionLabel: "Thử lại", onAction: () => void query.refetch() };
    return query.error instanceof ApiError && query.error.isNetworkError ? <OfflineState {...props} /> : <ErrorState {...props} />;
  }

  async function revokeOne(id: number) {
    setNotice(undefined);
    try {
      const response = await revoke.mutateAsync(id);
      setNotice(response.message ?? "Đã thu hồi phiên đăng nhập.");
    } catch {
      // Normalized error is rendered in the list header.
    }
  }

  async function revokeAllOthers() {
    setNotice(undefined);
    try {
      const response = await revokeOthers.mutateAsync();
      setNotice(response.message ?? `Đã thu hồi ${response.data.revoked} phiên khác.`);
    } catch {
      // Normalized error is rendered in the list header.
    }
  }

  const activeError = revoke.error ?? revokeOthers.error;
  return (
    <FlatList
      contentContainerStyle={[styles.content, { paddingBottom: spacing.xl + insets.bottom }]}
      contentInsetAdjustmentBehavior="automatic"
      data={query.data.items}
      keyExtractor={(item) => String(item.id)}
      ListEmptyComponent={<EmptyState message="Không có access token thiết bị nào khác được ghi nhận." style={styles.empty} title="Chưa có phiên đăng nhập" />}
      ListHeaderComponent={
        <View style={styles.headerContent}>
          <AccountHeader title="Phiên đăng nhập" subtitle="Mỗi thiết bị dùng một Bearer access token riêng. Bạn có thể thu hồi phiên khác ngay lập tức." />
          <AccountButton label="Đăng xuất tất cả thiết bị khác" loading={revokeOthers.isPending} onPress={() => void revokeAllOthers()} tone="danger" />
          <AccountMutationError error={activeError} />
          {notice ? <AccountNotice tone="success">{notice}</AccountNotice> : null}
        </View>
      }
      onRefresh={() => void query.refetch()}
      refreshing={query.isRefetching}
      renderItem={({ item }) => <SessionCard item={item} loading={revoke.isPending && revoke.variables === item.id} onRevoke={() => void revokeOne(item.id)} />}
    />
  );
}

function SessionCard({ item, loading, onRevoke }: { item: AccountSession; loading: boolean; onRevoke(): void }) {
  return (
    <AccountCard>
      <View style={accountStyles.row}>
        <View style={styles.copy}>
          <Text style={accountStyles.strong}>{item.device_name || "API Client"}</Text>
          <Text style={accountStyles.body}>{item.last_ip ?? "Chưa có IP"}</Text>
        </View>
        {item.is_current ? <View style={styles.current}><Text style={styles.currentText}>Hiện tại</Text></View> : null}
      </View>
      <Text style={accountStyles.body}>Dùng gần nhất: {formatDate(item.last_used_at)}</Text>
      <Text style={accountStyles.body}>Tạo lúc: {formatDate(item.created_at)}</Text>
      <Text style={accountStyles.body}>Hết hạn: {formatDate(item.expires_at)}</Text>
      {!item.is_current ? <AccountButton compact label="Thu hồi phiên" loading={loading} onPress={onRevoke} tone="secondary" /> : null}
    </AccountCard>
  );
}

const styles = StyleSheet.create({
  content: { backgroundColor: colors.background, gap: spacing.md, padding: spacing.md },
  headerContent: { gap: spacing.md },
  copy: { flex: 1, gap: spacing.xs },
  current: { backgroundColor: "#dcfce7", borderRadius: 999, paddingHorizontal: spacing.sm, paddingVertical: spacing.xs },
  currentText: { color: "#15803d", fontSize: 11, fontWeight: "800" },
  empty: { minHeight: 260 }
});
