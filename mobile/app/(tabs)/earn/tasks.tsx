import { useState } from "react";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { FlatList, KeyboardAvoidingView, Platform, StyleSheet, Text, View } from "react-native";
import { useSafeAreaInsets } from "react-native-safe-area-context";
import { ApiError } from "@/api/client";
import { EmptyState, ErrorState, LoadingState, OfflineState } from "@/components/AsyncState";
import { FormErrorSummary } from "@/components/FormErrorSummary";
import { claimTask, fetchTasks, submitTask, syncTask } from "@/features/earn/api";
import { invalidateRewardCaches } from "@/features/earn/cache";
import { canClaimTask, canSubmitCustomTask } from "@/features/earn/contracts";
import { useStableEarnSubmission } from "@/features/earn/submission";
import { ActionButton, Card, Field, FilterChip, ScreenHeader, StatusBadge, earnStyles, formatMoney } from "@/features/earn/ui";
import { getDeviceLocale } from "@/i18n";
import { colors, spacing } from "@/theme/tokens";

type TaskFilter = "all" | "in_progress" | "completed" | "pending" | "claimed";

function taskClaimFingerprint(payload: { taskId: number }) {
  return payload;
}

export default function TasksScreen() {
  const insets = useSafeAreaInsets();
  const queryClient = useQueryClient();
  const vi = getDeviceLocale() === "vi";
  const [filter, setFilter] = useState<TaskFilter>("all");
  const [submitTaskId, setSubmitTaskId] = useState<number | null>(null);
  const [note, setNote] = useState("");
  const [feedback, setFeedback] = useState<string | null>(null);
  const stableClaim = useStableEarnSubmission("task.claim", taskClaimFingerprint);
  const query = useQuery({
    queryKey: ["earn", "tasks"],
    queryFn: ({ signal }) => fetchTasks(signal)
  });
  const refreshTasks = () => queryClient.invalidateQueries({ queryKey: ["earn", "tasks"] });
  const syncMutation = useMutation({
    mutationFn: syncTask,
    onSuccess: async (response) => {
      setFeedback(response.message ?? (vi ? "Đã đồng bộ tiến độ." : "Progress synced."));
      await refreshTasks();
    }
  });
  const claimMutation = useMutation({
    mutationFn: claimTask,
    onSuccess: async (response, variables) => {
      setFeedback(response.message ?? (vi ? "Đã nhận thưởng nhiệm vụ." : "Task reward claimed."));
      stableClaim.reset(variables.payload);
      await Promise.all([refreshTasks(), invalidateRewardCaches(queryClient)]);
    }
  });
  const submitMutation = useMutation({
    mutationFn: submitTask,
    onSuccess: async (response) => {
      setFeedback(response.message ?? (vi ? "Đã gửi yêu cầu xác nhận." : "Verification request submitted."));
      setSubmitTaskId(null);
      setNote("");
      await refreshTasks();
    }
  });

  if (query.isPending) return <LoadingState label={vi ? "Đang tải nhiệm vụ..." : "Loading tasks..."} />;
  if (query.isError) {
    const props = {
      actionLabel: vi ? "Thử lại" : "Retry",
      message: query.error instanceof Error ? query.error.message : undefined,
      onAction: () => void query.refetch(),
      title: vi ? "Không thể tải nhiệm vụ" : "Unable to load tasks"
    };
    return query.error instanceof ApiError && query.error.isNetworkError ? <OfflineState {...props} /> : <ErrorState {...props} />;
  }

  const tasks = filter === "all" ? query.data.items : query.data.items.filter((task) => task.status === filter);
  const activeError = [syncMutation.error, claimMutation.error, submitMutation.error].find((error) => error instanceof ApiError) as ApiError | undefined;

  return (
    <KeyboardAvoidingView behavior={Platform.OS === "ios" ? "padding" : undefined} style={earnStyles.screen}>
      <FlatList
        contentContainerStyle={[earnStyles.listContent, { paddingBottom: insets.bottom + spacing.lg }]}
        contentInsetAdjustmentBehavior="automatic"
        data={tasks}
        keyExtractor={(task) => String(task.id)}
        keyboardShouldPersistTaps="handled"
        ListEmptyComponent={<EmptyState message={vi ? "Hãy thử trạng thái khác hoặc tải lại." : "Try another status or reload."} style={styles.empty} title={vi ? "Không có nhiệm vụ" : "No tasks found"} />}
        ListHeaderComponent={
          <View style={styles.headerContent}>
            <ScreenHeader
              eyebrow={vi ? "Tiến độ thành viên" : "Member progress"}
              subtitle={vi ? "Hoàn thành điều kiện, đồng bộ tiến độ và chỉ nhận thưởng khi hệ thống xác nhận." : "Complete requirements, sync progress, and claim only after server confirmation."}
              title={vi ? "Nhiệm vụ" : "Tasks"}
            />

            <View style={styles.statsGrid}>
              {[
                [vi ? "Đang làm" : "In progress", query.data.stats.in_progress],
                [vi ? "Chờ nhận" : "Ready", query.data.stats.completed],
                [vi ? "Chờ duyệt" : "Pending", query.data.stats.pending],
                [vi ? "Đã nhận" : "Claimed", query.data.stats.claimed]
              ].map(([label, value]) => (
                <View key={String(label)} style={styles.statCard}>
                  <Text style={styles.statValue}>{value}</Text>
                  <Text style={styles.statLabel}>{label}</Text>
                </View>
              ))}
            </View>
            <Card><View style={earnStyles.rowBetween}><Text style={earnStyles.strong}>{vi ? "Tổng thưởng nhiệm vụ" : "Total task rewards"}</Text><Text style={earnStyles.amount}>{formatMoney(query.data.stats.total_earned)}</Text></View></Card>

            <View style={earnStyles.chips}>
              {([
                ["all", vi ? "Tất cả" : "All"],
                ["in_progress", vi ? "Đang làm" : "In progress"],
                ["completed", vi ? "Chờ nhận" : "Ready"],
                ["pending", vi ? "Chờ duyệt" : "Pending"],
                ["claimed", vi ? "Đã nhận" : "Claimed"]
              ] as Array<[TaskFilter, string]>).map(([value, label]) => (
                <FilterChip key={value} label={label} onPress={() => setFilter(value)} selected={filter === value} />
              ))}
            </View>

            {activeError ? <FormErrorSummary errors={activeError.errors} message={activeError.message} /> : null}
            {feedback ? <View accessibilityLiveRegion="polite" style={earnStyles.feedback}><Text style={earnStyles.feedbackText}>{feedback}</Text></View> : null}
          </View>
        }
        onRefresh={() => void query.refetch()}
        refreshing={query.isRefetching}
        renderItem={({ item }) => {
          const percent = Math.max(0, Math.min(100, item.percent));
          const isSubmittingThis = submitMutation.isPending && submitTaskId === item.id;
          const canSubmitCompletion = canSubmitCustomTask(item.action, item.status);
          return (
            <Card>
              <View style={earnStyles.rowBetween}>
                <View style={styles.flex}>
                  <Text style={styles.type}>{item.type_label}</Text>
                  <Text style={styles.taskTitle}>{item.title}</Text>
                </View>
                <StatusBadge status={item.status} />
              </View>
              {item.description ? <Text style={earnStyles.body}>{item.description}</Text> : null}
              <View style={earnStyles.progressTrack}><View style={[earnStyles.progressFill, { width: `${percent}%` }]} /></View>
              <View style={earnStyles.rowBetween}>
                <Text style={earnStyles.body}>{item.progress}/{item.target_count} · {Math.round(percent)}%</Text>
                <Text style={styles.reward}>+{formatMoney(item.reward_amount)}</Text>
              </View>
              {item.guide ? <View style={styles.guide}><Text style={styles.guideTitle}>{vi ? "Hướng dẫn" : "Guide"}</Text><Text style={earnStyles.body}>{item.guide}</Text></View> : null}
              {item.reject_reason ? <View style={styles.reject}><Text style={styles.rejectText}>{item.reject_reason}</Text></View> : null}
              {item.status !== "claimed" ? (
                <View style={styles.actions}>
                  <ActionButton
                    label={vi ? "Đồng bộ tiến độ" : "Sync progress"}
                    loading={syncMutation.isPending && syncMutation.variables === item.id}
                    onPress={() => {
                      setFeedback(null);
                      syncMutation.mutate(item.id);
                    }}
                    tone="secondary"
                  />
                  {canClaimTask(item.status) ? (
                    <ActionButton
                      label={vi ? "Nhận thưởng" : "Claim reward"}
                      loading={claimMutation.isPending && claimMutation.variables?.payload.taskId === item.id}
                      onPress={() => {
                        setFeedback(null);
                        claimMutation.mutate(stableClaim.getVariables({ taskId: item.id }));
                      }}
                    />
                  ) : null}
                  {canSubmitCompletion ? (
                    <ActionButton
                      label={submitTaskId === item.id ? (vi ? "Đóng biểu mẫu" : "Close form") : (vi ? "Gửi xác nhận" : "Submit completion")}
                      onPress={() => {
                        setSubmitTaskId((current) => current === item.id ? null : item.id);
                        setNote("");
                      }}
                      tone="secondary"
                    />
                  ) : null}
                </View>
              ) : null}
              {submitTaskId === item.id && canSubmitCompletion ? (
                <View style={earnStyles.form}>
                  <Field
                    label={vi ? "Ghi chú / bằng chứng" : "Note / evidence"}
                    maxLength={500}
                    multiline
                    onChangeText={setNote}
                    placeholder={vi ? "Mô tả ngắn việc bạn đã hoàn thành..." : "Briefly describe what you completed..."}
                    value={note}
                  />
                  <ActionButton
                    label={vi ? "Gửi quản trị viên duyệt" : "Send for review"}
                    loading={isSubmittingThis}
                    onPress={() => {
                      setFeedback(null);
                      submitMutation.mutate({ taskId: item.id, note: note.trim() || undefined });
                    }}
                  />
                </View>
              ) : null}
            </Card>
          );
        }}
      />
    </KeyboardAvoidingView>
  );
}

const styles = StyleSheet.create({
  headerContent: { gap: spacing.md },
  statsGrid: { flexDirection: "row", flexWrap: "wrap", gap: spacing.sm },
  statCard: { backgroundColor: colors.surface, borderColor: colors.border, borderRadius: 14, borderWidth: 1, minWidth: "47%", padding: spacing.md },
  statValue: { color: colors.text, fontSize: 22, fontWeight: "900" },
  statLabel: { color: colors.mutedText, fontSize: 12, fontWeight: "700", marginTop: spacing.xs },
  flex: { flex: 1 },
  type: { color: colors.primary, fontSize: 11, fontWeight: "900", letterSpacing: 0.7, textTransform: "uppercase" },
  taskTitle: { color: colors.text, fontSize: 17, fontWeight: "900", lineHeight: 22, marginTop: spacing.xs },
  reward: { color: colors.primary, fontSize: 15, fontWeight: "900" },
  guide: { backgroundColor: "#f8fafc", borderRadius: 12, gap: spacing.xs, padding: spacing.md },
  guideTitle: { color: colors.text, fontSize: 13, fontWeight: "900" },
  reject: { backgroundColor: "#fef2f2", borderRadius: 12, padding: spacing.md },
  rejectText: { color: colors.danger, fontSize: 13, lineHeight: 19 },
  actions: { gap: spacing.sm },
  empty: { minHeight: 240 }
});
