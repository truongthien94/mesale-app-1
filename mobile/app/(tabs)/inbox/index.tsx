import { useState } from "react";
import { useInfiniteQuery, useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { ActivityIndicator, FlatList, Modal, Pressable, StyleSheet, Text, View } from "react-native";
import { useSafeAreaInsets } from "react-native-safe-area-context";
import { ApiError } from "@/api/client";
import { EmptyState, ErrorState, LoadingState, OfflineState } from "@/components/AsyncState";
import { FormErrorSummary } from "@/components/FormErrorSummary";
import {
  fetchNotifications,
  fetchUnreadCount,
  markAllNotificationsRead,
  markNotificationRead,
  type NotificationItem
} from "@/features/notifications/api";
import { getDeviceLocale } from "@/i18n";
import { colors, spacing } from "@/theme/tokens";

function formatNotificationDate(value: string | null): string {
  if (!value) return "—";
  const timestamp = Date.parse(value);
  if (Number.isNaN(timestamp)) return value;
  return new Intl.DateTimeFormat("vi-VN", { dateStyle: "medium", timeStyle: "short" }).format(timestamp);
}

export default function InboxRoute() {
  const insets = useSafeAreaInsets();
  const queryClient = useQueryClient();
  const vi = getDeviceLocale() === "vi";
  const [type, setType] = useState<"general" | "personal" | undefined>();
  const [filter, setFilter] = useState<"all" | "unread">("all");
  const [selected, setSelected] = useState<NotificationItem | null>(null);
  const [feedback, setFeedback] = useState<string | null>(null);
  const listQuery = useInfiniteQuery({
    queryKey: ["notifications", "list", type ?? "all", filter],
    initialPageParam: 1,
    queryFn: ({ pageParam, signal }) => fetchNotifications(pageParam, { type, filter }, signal),
    getNextPageParam: (page) => page.pagination.current_page < page.pagination.last_page
      ? page.pagination.current_page + 1
      : undefined
  });
  const countQuery = useQuery({
    queryKey: ["notifications", "unread-count"],
    queryFn: ({ signal }) => fetchUnreadCount(signal)
  });
  const refreshNotifications = () => queryClient.invalidateQueries({ queryKey: ["notifications"] });
  const readMutation = useMutation({
    mutationFn: markNotificationRead,
    onSuccess: async (response, id) => {
      setSelected((item) => item?.id === id ? { ...item, is_read: true } : item);
      setFeedback(response.message ?? null);
      await refreshNotifications();
    }
  });
  const readAllMutation = useMutation({
    mutationFn: markAllNotificationsRead,
    onSuccess: async (response) => {
      setFeedback(response.message ?? (vi ? "Đã đánh dấu đọc tất cả." : "All notifications marked as read."));
      await refreshNotifications();
    }
  });

  if (listQuery.isPending) return <LoadingState label={vi ? "Đang tải thông báo..." : "Loading notifications..."} />;
  if (listQuery.isError) {
    const props = {
      actionLabel: vi ? "Thử lại" : "Retry",
      message: listQuery.error instanceof Error ? listQuery.error.message : undefined,
      onAction: () => void listQuery.refetch(),
      title: vi ? "Không thể tải thông báo" : "Unable to load notifications"
    };
    return listQuery.error instanceof ApiError && listQuery.error.isNetworkError ? <OfflineState {...props} /> : <ErrorState {...props} />;
  }

  const notifications = listQuery.data.pages.flatMap((page) => page.items);
  const firstPage = listQuery.data.pages[0];
  const unreadTotal = countQuery.data?.unread_total ?? firstPage?.unread_total ?? 0;
  const mutationError = [readMutation.error, readAllMutation.error].find((error) => error instanceof ApiError) as ApiError | undefined;

  return (
    <View style={styles.screen}>
      <FlatList
        contentContainerStyle={[styles.content, { paddingBottom: insets.bottom + spacing.lg }]}
        contentInsetAdjustmentBehavior="automatic"
        data={notifications}
        keyExtractor={(item) => String(item.id)}
        ListEmptyComponent={
          <EmptyState
            message={filter === "unread"
              ? (vi ? "Bạn đã đọc hết thông báo trong nhóm này." : "You have read every notification in this group.")
              : (vi ? "Thông báo mới sẽ xuất hiện tại đây." : "New notifications will appear here.")}
            style={styles.empty}
            title={filter === "unread" ? (vi ? "Đã đọc hết" : "All caught up") : (vi ? "Chưa có thông báo" : "No notifications")}
          />
        }
        ListFooterComponent={listQuery.isFetchingNextPage ? <ActivityIndicator color={colors.primary} style={styles.footer} /> : null}
        ListHeaderComponent={
          <View style={styles.headerContent}>
            <View style={[styles.hero, { paddingTop: insets.top + spacing.lg }]}>
              <View style={styles.heroTop}>
                <View style={styles.heroText}>
                  <Text style={styles.eyebrow}>MESALE INBOX</Text>
                  <Text accessibilityRole="header" style={styles.heroTitle}>{vi ? "Thông báo" : "Notifications"}</Text>
                  <Text style={styles.heroSubtitle}>{vi ? "Cập nhật tài khoản, đơn hàng và quyền lợi của bạn." : "Updates about your account, orders, and benefits."}</Text>
                </View>
                <View accessibilityLabel={vi ? `${unreadTotal} thông báo chưa đọc` : `${unreadTotal} unread notifications`} style={styles.unreadBadge}>
                  <Text style={styles.unreadValue}>{unreadTotal}</Text>
                  <Text style={styles.unreadLabel}>{vi ? "CHƯA ĐỌC" : "UNREAD"}</Text>
                </View>
              </View>
              <Pressable
                accessibilityRole="button"
                accessibilityState={{ disabled: unreadTotal === 0 || readAllMutation.isPending, busy: readAllMutation.isPending }}
                disabled={unreadTotal === 0 || readAllMutation.isPending}
                onPress={() => {
                  setFeedback(null);
                  readAllMutation.mutate();
                }}
                style={({ pressed }) => [styles.readAll, (unreadTotal === 0 || readAllMutation.isPending) && styles.disabled, pressed && styles.pressed]}
              >
                {readAllMutation.isPending ? <ActivityIndicator color={colors.primary} /> : null}
                <Text style={styles.readAllText}>{vi ? "Đánh dấu đọc tất cả" : "Mark all as read"}</Text>
              </Pressable>
            </View>

            <View style={styles.chips}>
              {([
                [undefined, vi ? "Tất cả" : "All"],
                ["general", vi ? "Thông báo chung" : "General"],
                ["personal", vi ? "Của bạn" : "Personal"]
              ] as Array<["general" | "personal" | undefined, string]>).map(([value, label]) => (
                <FilterButton
                  key={value ?? "all"}
                  label={label}
                  onPress={() => setType(value)}
                  selected={type === value}
                />
              ))}
            </View>
            <View style={styles.chips}>
              <FilterButton label={vi ? "Tất cả trạng thái" : "All statuses"} onPress={() => setFilter("all")} selected={filter === "all"} />
              <FilterButton label={vi ? "Chưa đọc" : "Unread"} onPress={() => setFilter("unread")} selected={filter === "unread"} />
            </View>
            {mutationError ? <FormErrorSummary errors={mutationError.errors} message={mutationError.message} /> : null}
            {feedback ? <View accessibilityLiveRegion="polite" style={styles.feedback}><Text style={styles.feedbackText}>{feedback}</Text></View> : null}
          </View>
        }
        onEndReached={() => {
          if (listQuery.hasNextPage && !listQuery.isFetchingNextPage) void listQuery.fetchNextPage();
        }}
        onEndReachedThreshold={0.4}
        onRefresh={() => void Promise.all([listQuery.refetch(), countQuery.refetch()])}
        refreshing={listQuery.isRefetching || countQuery.isRefetching}
        renderItem={({ item }) => (
          <Pressable
            accessibilityRole="button"
            accessibilityState={{ selected: selected?.id === item.id }}
            onPress={() => {
              setSelected(item);
              setFeedback(null);
              if (!item.is_read) readMutation.mutate(item.id);
            }}
            style={({ pressed }) => [styles.notification, item.is_read ? styles.notificationRead : styles.notificationUnread, pressed && styles.pressed]}
          >
            <View style={styles.notificationTop}>
              <View style={styles.notificationTitleRow}>
                {!item.is_read ? <View style={styles.dot} /> : null}
                <Text numberOfLines={1} style={[styles.notificationTitle, item.is_read && styles.notificationTitleRead]}>{item.title}</Text>
              </View>
              <Text style={styles.date}>{formatNotificationDate(item.created_at)}</Text>
            </View>
            <Text numberOfLines={3} style={styles.notificationContent}>{item.content}</Text>
            <Text style={styles.type}>{item.type === "personal" ? (vi ? "Cá nhân" : "Personal") : (vi ? "Chung" : "General")}</Text>
          </Pressable>
        )}
      />

      <Modal animationType="slide" onRequestClose={() => setSelected(null)} transparent visible={selected !== null}>
        <Pressable onPress={() => setSelected(null)} style={styles.modalBackdrop}>
          <Pressable accessibilityViewIsModal onPress={() => undefined} style={[styles.modalCard, { paddingBottom: insets.bottom + spacing.lg }]}>
            <View style={styles.modalHandle} />
            <Text style={styles.type}>{selected?.type === "personal" ? (vi ? "THÔNG BÁO CỦA BẠN" : "PERSONAL NOTIFICATION") : (vi ? "THÔNG BÁO CHUNG" : "GENERAL NOTIFICATION")}</Text>
            <Text accessibilityRole="header" style={styles.modalTitle}>{selected?.title}</Text>
            <Text style={styles.date}>{formatNotificationDate(selected?.created_at ?? null)}</Text>
            <View style={styles.divider} />
            <Text style={styles.modalContent}>{selected?.content}</Text>
            <Pressable accessibilityRole="button" onPress={() => setSelected(null)} style={({ pressed }) => [styles.closeButton, pressed && styles.pressed]}>
              <Text style={styles.closeText}>{vi ? "Đóng" : "Close"}</Text>
            </Pressable>
          </Pressable>
        </Pressable>
      </Modal>
    </View>
  );
}

function FilterButton({ label, selected, onPress }: { label: string; selected: boolean; onPress: () => void }) {
  return (
    <Pressable accessibilityRole="button" accessibilityState={{ selected }} onPress={onPress} style={({ pressed }) => [styles.chip, selected && styles.chipSelected, pressed && styles.pressed]}>
      <Text style={[styles.chipText, selected && styles.chipTextSelected]}>{label}</Text>
    </Pressable>
  );
}

const styles = StyleSheet.create({
  screen: { backgroundColor: colors.background, flex: 1 },
  content: { gap: spacing.md, paddingHorizontal: spacing.md },
  headerContent: { gap: spacing.md, marginHorizontal: -spacing.md },
  hero: { backgroundColor: "#f97316", borderBottomLeftRadius: 28, borderBottomRightRadius: 28, gap: spacing.md, padding: spacing.lg },
  heroTop: { alignItems: "flex-start", flexDirection: "row", gap: spacing.md, justifyContent: "space-between" },
  heroText: { flex: 1, gap: spacing.xs },
  eyebrow: { color: "#ffedd5", fontSize: 11, fontWeight: "900", letterSpacing: 1.2 },
  heroTitle: { color: colors.surface, fontSize: 28, fontWeight: "900" },
  heroSubtitle: { color: "#fff7ed", fontSize: 14, lineHeight: 20 },
  unreadBadge: { alignItems: "center", backgroundColor: colors.surface, borderRadius: 16, minWidth: 72, padding: spacing.sm },
  unreadValue: { color: colors.primary, fontSize: 22, fontWeight: "900" },
  unreadLabel: { color: colors.mutedText, fontSize: 9, fontWeight: "900", letterSpacing: 0.7 },
  readAll: { alignItems: "center", alignSelf: "flex-start", backgroundColor: colors.surface, borderRadius: 12, flexDirection: "row", gap: spacing.sm, minHeight: 44, paddingHorizontal: spacing.md },
  readAllText: { color: colors.primary, fontSize: 13, fontWeight: "900" },
  disabled: { opacity: 0.55 },
  pressed: { opacity: 0.78 },
  chips: { flexDirection: "row", flexWrap: "wrap", gap: spacing.sm, paddingHorizontal: spacing.md },
  chip: { backgroundColor: colors.surface, borderColor: colors.border, borderRadius: 999, borderWidth: 1, justifyContent: "center", minHeight: 40, paddingHorizontal: spacing.md },
  chipSelected: { backgroundColor: "#fff7ed", borderColor: colors.primary },
  chipText: { color: colors.mutedText, fontSize: 13, fontWeight: "700" },
  chipTextSelected: { color: colors.primary },
  feedback: { backgroundColor: "#fff7ed", borderColor: "#fed7aa", borderRadius: 12, borderWidth: 1, marginHorizontal: spacing.md, padding: spacing.md },
  feedbackText: { color: "#9a3412", fontSize: 13, lineHeight: 19 },
  notification: { borderRadius: 18, borderWidth: 1, gap: spacing.sm, padding: spacing.md, shadowColor: "#0f172a", shadowOffset: { width: 0, height: 4 }, shadowOpacity: 0.04, shadowRadius: 10, elevation: 1 },
  notificationUnread: { backgroundColor: colors.surface, borderColor: "#fdba74", borderLeftColor: colors.primary, borderLeftWidth: 4 },
  notificationRead: { backgroundColor: "#f1f5f9", borderColor: colors.border },
  notificationTop: { gap: spacing.xs },
  notificationTitleRow: { alignItems: "center", flexDirection: "row", gap: spacing.sm },
  dot: { backgroundColor: colors.primary, borderRadius: 999, height: 8, width: 8 },
  notificationTitle: { color: colors.text, flex: 1, fontSize: 15, fontWeight: "900" },
  notificationTitleRead: { color: colors.mutedText },
  notificationContent: { color: colors.mutedText, fontSize: 14, lineHeight: 21 },
  date: { color: colors.mutedText, fontSize: 12 },
  type: { color: colors.primary, fontSize: 10, fontWeight: "900", letterSpacing: 0.8, textTransform: "uppercase" },
  footer: { padding: spacing.lg },
  empty: { minHeight: 300 },
  modalBackdrop: { backgroundColor: "rgba(15, 23, 42, 0.45)", flex: 1, justifyContent: "flex-end" },
  modalCard: { backgroundColor: colors.surface, borderTopLeftRadius: 24, borderTopRightRadius: 24, gap: spacing.md, maxHeight: "85%", padding: spacing.lg },
  modalHandle: { alignSelf: "center", backgroundColor: colors.border, borderRadius: 999, height: 5, width: 48 },
  modalTitle: { color: colors.text, fontSize: 21, fontWeight: "900", lineHeight: 27 },
  divider: { backgroundColor: colors.border, height: 1 },
  modalContent: { color: colors.text, fontSize: 15, lineHeight: 24 },
  closeButton: { alignItems: "center", backgroundColor: colors.primary, borderRadius: 12, justifyContent: "center", minHeight: 48 },
  closeText: { color: colors.surface, fontSize: 14, fontWeight: "900" }
});
