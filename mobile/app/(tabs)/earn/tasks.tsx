import { useMemo, useState } from "react";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import {
  ActivityIndicator,
  FlatList,
  KeyboardAvoidingView,
  Platform,
  Pressable,
  ScrollView,
  StyleSheet,
  Text,
  TextInput,
  View
} from "react-native";
import {
  Check,
  CircleDollarSign,
  Clock3,
  Gift,
  LockKeyhole,
  ListChecks,
  ShoppingCart,
  UsersRound
} from "lucide-react-native";
import { useSafeAreaInsets } from "react-native-safe-area-context";
import { ApiError } from "@/api/client";
import { ErrorState, LoadingState, OfflineState } from "@/components/AsyncState";
import { fetchTasks, claimTask, submitTask, syncTask, type EarnTask } from "@/features/earn/api";
import { invalidateRewardCaches } from "@/features/earn/cache";
import { canClaimTask, canSubmitCustomTask } from "@/features/earn/contracts";
import { useStableEarnSubmission } from "@/features/earn/submission";
import { groupTaskMilestones, isTaskMilestoneReached, taskProgressPercent } from "@/features/earn/taskPresentation";
import { formatMoney } from "@/features/earn/ui";
import { getDeviceLocale } from "@/i18n";
import { useTheme } from "@/theme/ThemeProvider";
import type { Theme } from "@/theme/tokens";

type SectionKey = "referral" | "cashback" | "other";

type TaskSection = {
  key: SectionKey;
  title: string;
  subtitle: string;
  tasks: EarnTask[];
};

function taskClaimFingerprint(payload: { taskId: number }) {
  return payload;
}

function statusLabel(status: string, vi: boolean): string {
  if (!vi) {
    return status.replaceAll("_", " ");
  }
  return {
    in_progress: "Đang thực hiện",
    pending: "Chờ duyệt",
    completed: "Chờ nhận thưởng",
    claimed: "Đã nhận thưởng"
  }[status] ?? status;
}

function compactStatusLabel(status: string, vi: boolean): string {
  if (!vi) {
    return {
      in_progress: "Active",
      pending: "Review",
      completed: "Ready",
      claimed: "Claimed"
    }[status] ?? status.replaceAll("_", " ");
  }
  return {
    in_progress: "Đang làm",
    pending: "Chờ",
    completed: "Sẵn sàng",
    claimed: "Đã nhận"
  }[status] ?? status;
}

function StatusPill({ compact = false, status, vi, styles }: { compact?: boolean; status: string; vi: boolean; styles: ReturnType<typeof createStyles> }) {
  const positive = status === "claimed";
  const ready = status === "completed";
  const pending = status === "pending";
  return (
    <View style={[styles.statusPill, compact && styles.compactStatusPill, positive && styles.statusPositive, ready && styles.statusReady, pending && styles.statusPending]}>
      <Text numberOfLines={1} style={[styles.statusText, compact && styles.compactStatusText, (positive || ready) && styles.statusPositiveText]}>{compact ? compactStatusLabel(status, vi) : statusLabel(status, vi)}</Text>
    </View>
  );
}

function TaskAction({
  compact = false,
  disabled = false,
  label,
  loading = false,
  onPress,
  styles
}: {
  compact?: boolean;
  disabled?: boolean;
  label: string;
  loading?: boolean;
  onPress?: () => void;
  styles: ReturnType<typeof createStyles>;
}) {
  return (
    <Pressable
      accessibilityLabel={label}
      accessibilityRole="button"
      accessibilityState={{ busy: loading, disabled: disabled || loading }}
      disabled={disabled || loading || !onPress}
      hitSlop={compact ? { top: 9, right: 1, bottom: 9, left: 1 } : { top: 7, right: 2, bottom: 7, left: 2 }}
      onPress={onPress}
      style={({ pressed }) => [styles.taskAction, compact && styles.compactTaskAction, (disabled || loading) && styles.taskActionDisabled, pressed && styles.pressed]}
    >
      {loading ? <ActivityIndicator color="#ffffff" size="small" /> : null}
      {!loading || !compact ? <Text numberOfLines={1} style={[styles.taskActionText, compact && styles.compactTaskActionText]}>{label}</Text> : null}
    </Pressable>
  );
}

function MilestoneSection({
  icon,
  onClaim,
  onSync,
  section,
  styles,
  vi,
  busyClaimId,
  busySyncId
}: {
  icon: "referral" | "cashback";
  onClaim: (task: EarnTask) => void;
  onSync: (task: EarnTask) => void;
  section: TaskSection;
  styles: ReturnType<typeof createStyles>;
  vi: boolean;
  busyClaimId: number | null;
  busySyncId: number | null;
}) {
  const Icon = icon === "referral" ? UsersRound : ShoppingCart;
  const IconColor = icon === "referral" ? "#16a34a" : "#f97316";

  return (
    <View style={styles.sectionCard}>
      <View style={styles.sectionHeading}>
        <View style={[styles.sectionIcon, icon === "referral" ? styles.referralIcon : styles.cashbackIcon]}>
          <Icon color={IconColor} size={23} strokeWidth={2.4} />
        </View>
        <View style={styles.sectionHeadingCopy}>
          <Text accessibilityRole="header" style={styles.sectionTitle}>{section.title}</Text>
          {vi ? (
            icon === "referral" ? (
              <Text style={styles.sectionSubtitle}>
                Mời bạn qua mã giới thiệu, có ít nhất <Text style={styles.subtitleStrong}>1 đơn hoàn tiền từ 1.000đ</Text> để nhận thêm thưởng theo từng mốc.
              </Text>
            ) : (
              <Text style={styles.sectionSubtitle}>
                Hoàn thành đơn có tiền hoàn từ <Text style={styles.subtitleStrong}>1.000đ</Text> để nhận thưởng theo từng mốc.
              </Text>
            )
          ) : <Text style={styles.sectionSubtitle}>{section.subtitle}</Text>}
        </View>
      </View>

      {section.tasks.length === 0 ? (
        <View accessibilityLiveRegion="polite" style={styles.unconfigured}>
          <LockKeyhole color="#94a3b8" size={19} />
          <Text style={styles.unconfiguredTitle}>{vi ? "Chưa có mốc được cấu hình" : "No milestones configured"}</Text>
          <Text style={styles.unconfiguredText}>
            {vi ? "Các mốc thưởng sẽ xuất hiện khi hệ thống kích hoạt nhiệm vụ tương ứng." : "Milestones will appear when the server enables the corresponding tasks."}
          </Text>
        </View>
      ) : (
        <ScrollView
          contentContainerStyle={styles.timelineContent}
          horizontal
          showsHorizontalScrollIndicator={false}
        >
          {section.tasks.map((task, index) => {
            const reached = isTaskMilestoneReached(task);
            const percent = taskProgressPercent(task);
            const isClaiming = busyClaimId === task.id;
            const isSyncing = busySyncId === task.id;
            const actionLabel = task.status === "completed"
              ? (vi ? "Nhận" : "Claim")
              : task.status === "claimed"
                ? (vi ? "Đã nhận" : "Claimed")
                : task.status === "pending"
                  ? (vi ? "Chờ" : "Pending")
                  : (vi ? "Cập nhật" : "Sync");

            return (
              <View accessibilityLabel={vi ? `${section.title}, mốc ${task.target_count}, thưởng ${formatMoney(task.reward_amount)}` : `${section.title}, target ${task.target_count}, reward ${formatMoney(task.reward_amount)}`} key={task.id} style={styles.timelineSlot}>
                {index > 0 ? <View style={[styles.timelineConnector, reached && styles.timelineConnectorReached]} /> : null}
                <View style={[styles.milestoneCircle, reached && styles.milestoneCircleReached, task.status === "completed" && styles.milestoneCircleReady]}>
                  {task.status === "claimed" ? <Check color="#ffffff" size={17} strokeWidth={3} /> : task.status === "pending" ? <Clock3 color="#ffffff" size={17} strokeWidth={2.6} /> : task.status === "completed" ? <Gift color="#ffffff" size={17} strokeWidth={2.4} /> : <LockKeyhole color={reached ? "#ffffff" : "#94a3b8"} size={16} strokeWidth={2.2} />}
                </View>
                <View style={[styles.milestoneCard, task.status === "completed" && styles.milestoneCardReady, task.status === "claimed" && styles.milestoneCardClaimed]}>
                  <Text style={styles.milestoneTarget}>{task.target_count}</Text>
                  <Text style={styles.milestoneUnit}>{icon === "referral" ? (vi ? "NGƯỜI" : "PEOPLE") : (vi ? "ĐƠN" : "ORDERS")}</Text>
                  <Text adjustsFontSizeToFit minimumFontScale={0.75} numberOfLines={1} style={styles.milestoneReward}>+{formatMoney(task.reward_amount)}</Text>
                  <Text style={styles.milestoneProgress}>{task.progress}/{task.target_count} · {Math.round(percent)}%</Text>
                  <View style={styles.miniProgressTrack}><View style={[styles.miniProgressFill, { width: `${percent}%` }]} /></View>
                  <StatusPill compact status={task.status} styles={styles} vi={vi} />
                  <TaskAction
                    compact
                    disabled={task.status === "claimed" || task.status === "pending"}
                    label={actionLabel}
                    loading={isClaiming || isSyncing}
                    onPress={task.status === "completed" ? () => onClaim(task) : task.status === "in_progress" ? () => onSync(task) : undefined}
                    styles={styles}
                  />
                </View>
              </View>
            );
          })}
        </ScrollView>
      )}
    </View>
  );
}

function OtherTaskCard({
  onClaim,
  onSubmit,
  onSync,
  setSubmitTaskId,
  note,
  setNote,
  submitTaskId,
  task,
  styles,
  vi,
  busyClaimId,
  busySubmitId,
  busySyncId
}: {
  onClaim: (task: EarnTask) => void;
  onSubmit: (task: EarnTask) => void;
  onSync: (task: EarnTask) => void;
  setSubmitTaskId: (id: number | null) => void;
  note: string;
  setNote: (value: string) => void;
  submitTaskId: number | null;
  task: EarnTask;
  styles: ReturnType<typeof createStyles>;
  vi: boolean;
  busyClaimId: number | null;
  busySubmitId: number | null;
  busySyncId: number | null;
}) {
  const percent = taskProgressPercent(task);
  const canSubmit = canSubmitCustomTask(task.action, task.status);
  return (
    <View style={styles.otherTaskCard}>
      <View style={styles.otherTaskTop}>
        <View style={styles.otherTaskCopy}>
          <Text style={styles.otherTaskType}>{task.type_label}</Text>
          <Text style={styles.otherTaskTitle}>{task.title}</Text>
        </View>
        <StatusPill status={task.status} styles={styles} vi={vi} />
      </View>
      {task.description ? <Text style={styles.otherTaskDescription}>{task.description}</Text> : null}
      <View style={styles.progressTrack}><View style={[styles.progressFill, { width: `${percent}%` }]} /></View>
      <View style={styles.otherTaskMeta}>
        <Text style={styles.otherTaskProgress}>{task.progress}/{task.target_count} · {Math.round(percent)}%</Text>
        <Text style={styles.otherTaskReward}>+{formatMoney(task.reward_amount)}</Text>
      </View>
      {task.guide ? <Text style={styles.otherTaskGuide}>{task.guide}</Text> : null}
      {task.reject_reason ? <Text style={styles.rejectReason}>{task.reject_reason}</Text> : null}
      {task.status !== "claimed" ? (
        <View style={styles.otherActions}>
          <TaskAction disabled={task.status === "pending"} label={task.status === "pending" ? (vi ? "Chờ duyệt" : "Pending") : vi ? "Cập nhật" : "Sync"} loading={busySyncId === task.id} onPress={task.status !== "pending" ? () => onSync(task) : undefined} styles={styles} />
          {canClaimTask(task.status) ? <TaskAction label={vi ? "Nhận thưởng" : "Claim reward"} loading={busyClaimId === task.id} onPress={() => onClaim(task)} styles={styles} /> : null}
          {canSubmit ? <TaskAction label={submitTaskId === task.id ? (vi ? "Đóng biểu mẫu" : "Close form") : (vi ? "Gửi xác nhận" : "Submit completion")} onPress={() => { setSubmitTaskId(submitTaskId === task.id ? null : task.id); setNote(""); }} styles={styles} /> : null}
        </View>
      ) : null}
      {submitTaskId === task.id && canSubmit ? (
        <View style={styles.submitForm}>
          <Text style={styles.submitLabel}>{vi ? "Ghi chú / bằng chứng" : "Note / evidence"}</Text>
          <TextInput maxLength={500} multiline onChangeText={setNote} placeholder={vi ? "Mô tả ngắn việc bạn đã hoàn thành..." : "Briefly describe what you completed..."} placeholderTextColor="#94a3b8" style={styles.submitInput} value={note} />
          <TaskAction label={vi ? "Gửi quản trị viên duyệt" : "Send for review"} loading={busySubmitId === task.id} onPress={() => onSubmit(task)} styles={styles} />
        </View>
      ) : null}
    </View>
  );
}

export default function TasksScreen() {
  const insets = useSafeAreaInsets();
  const queryClient = useQueryClient();
  const { colors, scheme } = useTheme();
  const styles = useMemo(() => createStyles(colors, scheme), [colors, scheme]);
  const vi = getDeviceLocale() === "vi";
  const [submitTaskId, setSubmitTaskId] = useState<number | null>(null);
  const [note, setNote] = useState("");
  const [feedback, setFeedback] = useState<string | null>(null);
  const stableClaim = useStableEarnSubmission("task.claim", taskClaimFingerprint);
  const query = useQuery({ queryKey: ["earn", "tasks"], queryFn: ({ signal }) => fetchTasks(signal) });
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

  const groups = groupTaskMilestones(query.data.items);
  const sections: TaskSection[] = [
    { key: "referral", title: vi ? "Mời bạn nhận thưởng" : "Invite participants", subtitle: vi ? "Mời thành viên mới theo từng mốc để nhận thêm tiền thưởng." : "Invite new members and unlock each configured reward milestone.", tasks: groups.referral },
    { key: "cashback", title: vi ? "Hoàn thành đơn hàng" : "Complete cashback orders", subtitle: vi ? "Hoàn thành đơn được duyệt theo các mốc thưởng từ hệ thống." : "Complete approved cashback orders to unlock server-configured rewards.", tasks: groups.cashback }
  ];
  if (groups.other.length > 0) sections.push({ key: "other", title: vi ? "Nhiệm vụ khác" : "Other tasks", subtitle: vi ? "Các nhiệm vụ bổ sung do hệ thống cấu hình." : "Additional tasks configured by the server.", tasks: groups.other });
  const activeError = [syncMutation.error, claimMutation.error, submitMutation.error].find((error) => error instanceof ApiError) as ApiError | undefined;
  const activeErrorMessages = activeError
    ? [...new Set([activeError.message, ...Object.values(activeError.errors ?? {}).flat()].filter(Boolean))]
    : [];

  return (
    <KeyboardAvoidingView behavior={Platform.OS === "ios" ? "padding" : undefined} style={styles.screen}>
      <FlatList
        contentContainerStyle={[styles.listContent, { paddingBottom: insets.bottom + 28 }]}
        data={sections}
        keyExtractor={(item) => item.key}
        keyboardShouldPersistTaps="handled"
        ListHeaderComponent={(
          <View style={[styles.headerContent, { paddingTop: insets.top + 12 }]}>
            <View style={styles.heroCard}>
              <View style={styles.heroHeading}>
                <View style={styles.heroIcon}><ListChecks color={colors.primary} size={25} strokeWidth={2.4} /></View>
                <View style={styles.heroCopy}>
                  <Text accessibilityRole="header" style={styles.heroTitle}>{vi ? "NHIỆM VỤ NHẬN THƯỞNG ✨" : "REWARD TASKS ✨"}</Text>
                  <Text style={styles.heroSubtitle}>{vi ? "Hoàn thành từng mốc, đồng bộ và nhận thưởng do máy chủ xác nhận." : "Complete milestones, sync progress, and claim server-confirmed rewards."}</Text>
                </View>
              </View>
              <View style={styles.totalReward}><CircleDollarSign color={colors.primary} size={18} /><Text style={styles.totalRewardLabel}>{vi ? "Tổng thưởng nhiệm vụ" : "Total task rewards"}</Text><Text style={styles.totalRewardAmount}>{formatMoney(query.data.stats.total_earned)}</Text></View>
            </View>
            {activeErrorMessages.length > 0 ? <View accessibilityLiveRegion="polite" accessibilityRole="alert" style={styles.errorBanner}>{activeErrorMessages.map((message) => <Text key={message} style={styles.errorText}>{message}</Text>)}</View> : null}
            {feedback ? <View accessibilityLiveRegion="polite" style={styles.feedback}><Check color="#16a34a" size={18} strokeWidth={3} /><Text style={styles.feedbackText}>{feedback}</Text></View> : null}
          </View>
        )}
        onRefresh={() => void query.refetch()}
        refreshing={query.isRefetching}
        renderItem={({ item }) => item.key === "other" ? (
          <View style={styles.otherSection}>
            <SectionHeading icon="other" section={item} styles={styles} />
            {item.tasks.map((task) => <OtherTaskCard busyClaimId={claimMutation.isPending ? claimMutation.variables?.payload.taskId ?? null : null} busySubmitId={submitMutation.isPending ? submitTaskId : null} busySyncId={syncMutation.isPending ? syncMutation.variables ?? null : null} key={task.id} note={note} onClaim={(selected) => { setFeedback(null); claimMutation.mutate(stableClaim.getVariables({ taskId: selected.id })); }} onSubmit={(selected) => { setFeedback(null); submitMutation.mutate({ taskId: selected.id, note: note.trim() || undefined }); }} onSync={(selected) => { setFeedback(null); syncMutation.mutate(selected.id); }} setNote={setNote} setSubmitTaskId={setSubmitTaskId} styles={styles} submitTaskId={submitTaskId} task={task} vi={vi} />)}
          </View>
        ) : (
          <MilestoneSection busyClaimId={claimMutation.isPending ? claimMutation.variables?.payload.taskId ?? null : null} busySyncId={syncMutation.isPending ? syncMutation.variables ?? null : null} icon={item.key} onClaim={(selected) => { setFeedback(null); claimMutation.mutate(stableClaim.getVariables({ taskId: selected.id })); }} onSync={(selected) => { setFeedback(null); syncMutation.mutate(selected.id); }} section={item} styles={styles} vi={vi} />
        )}
        showsVerticalScrollIndicator={false}
      />
    </KeyboardAvoidingView>
  );
}

function SectionHeading({ icon, section, styles }: { icon: SectionKey; section: TaskSection; styles: ReturnType<typeof createStyles> }) {
  const Icon = icon === "referral" ? UsersRound : icon === "cashback" ? ShoppingCart : ListChecks;
  return <View style={styles.sectionHeading}><View style={[styles.sectionIcon, icon === "referral" ? styles.referralIcon : icon === "cashback" ? styles.cashbackIcon : styles.otherIcon]}><Icon color={icon === "referral" ? "#16a34a" : icon === "cashback" ? "#f97316" : "#2563eb"} size={23} strokeWidth={2.4} /></View><View style={styles.sectionHeadingCopy}><Text accessibilityRole="header" style={styles.sectionTitle}>{section.title}</Text><Text style={styles.sectionSubtitle}>{section.subtitle}</Text></View></View>;
}

function createStyles(colors: Theme["colors"], scheme: Theme["scheme"]) {
  const dark = scheme === "dark";
  return StyleSheet.create({
    screen: { backgroundColor: dark ? "#08111f" : "#f4f1ed", flex: 1 },
    listContent: { gap: 14, paddingHorizontal: 16 },
    headerContent: { gap: 12 },
    heroCard: { backgroundColor: colors.surface, borderColor: colors.border, borderRadius: 16, borderWidth: 1, gap: 10, padding: 13, shadowColor: "#0f172a", shadowOffset: { width: 0, height: 4 }, shadowOpacity: dark ? 0.16 : 0.05, shadowRadius: 10, elevation: 2 },
    heroHeading: { alignItems: "center", flexDirection: "row", gap: 12 },
    heroIcon: { alignItems: "center", backgroundColor: dark ? "#3b2910" : "#fff7ed", borderColor: dark ? "#854d0e" : "#fed7aa", borderRadius: 10, borderWidth: 1, height: 38, justifyContent: "center", width: 38 },
    heroCopy: { flex: 1, gap: 3 },
    heroTitle: { color: colors.text, fontSize: 15, fontWeight: "900", letterSpacing: -0.2 },
    heroSubtitle: { color: colors.mutedText, fontSize: 11.5, lineHeight: 16 },
    totalReward: { alignItems: "center", backgroundColor: dark ? "#2f2017" : "#fff7ed", borderRadius: 10, flexDirection: "row", gap: 6, paddingHorizontal: 10, paddingVertical: 8 },
    totalRewardLabel: { color: colors.mutedText, flex: 1, fontSize: 11, fontWeight: "700" },
    totalRewardAmount: { color: colors.primary, fontSize: 15, fontWeight: "900" },
    errorBanner: { backgroundColor: dark ? "#431b22" : "#fef2f2", borderColor: dark ? "#7f1d1d" : "#fecaca", borderRadius: 12, borderWidth: 1, padding: 13 },
    errorText: { color: dark ? "#fecaca" : "#b91c1c", fontSize: 13, lineHeight: 19 },
    feedback: { alignItems: "center", backgroundColor: dark ? "#0d3327" : "#ecfdf5", borderColor: dark ? "#166534" : "#86efac", borderRadius: 12, borderWidth: 1, flexDirection: "row", gap: 8, padding: 12 },
    feedbackText: { color: dark ? "#bbf7d0" : "#15803d", flex: 1, fontSize: 13, lineHeight: 19 },
    sectionCard: { backgroundColor: colors.surface, borderColor: colors.border, borderRadius: 16, borderWidth: 1, gap: 10, paddingHorizontal: 10, paddingVertical: 12, shadowColor: "#0f172a", shadowOffset: { width: 0, height: 4 }, shadowOpacity: dark ? 0.16 : 0.04, shadowRadius: 10, elevation: 2 },
    sectionHeading: { alignItems: "center", flexDirection: "row", gap: 12 },
    sectionIcon: { alignItems: "center", borderRadius: 999, height: 38, justifyContent: "center", width: 38 },
    referralIcon: { backgroundColor: dark ? "#0d3327" : "#ecfdf5" },
    cashbackIcon: { backgroundColor: dark ? "#3b2910" : "#fff3df" },
    otherIcon: { backgroundColor: dark ? "#172554" : "#eff6ff" },
    sectionHeadingCopy: { flex: 1, gap: 3 },
    sectionTitle: { color: colors.text, fontSize: 14.5, fontWeight: "900", letterSpacing: -0.2 },
    sectionSubtitle: { color: colors.mutedText, fontSize: 11, lineHeight: 16 },
    subtitleStrong: { color: colors.text, fontWeight: "900" },
    timelineContent: { gap: 3, paddingHorizontal: 1, paddingVertical: 3 },
    timelineSlot: { alignItems: "center", minWidth: 68, position: "relative", width: 68 },
    timelineConnector: { backgroundColor: dark ? "#475569" : "#fed7aa", height: 2, left: -23, position: "absolute", top: 13, width: 43 },
    timelineConnectorReached: { backgroundColor: "#22c55e" },
    milestoneCircle: { alignItems: "center", backgroundColor: dark ? "#1e293b" : "#e2e8f0", borderColor: dark ? "#475569" : "#cbd5e1", borderRadius: 999, borderWidth: 1, height: 28, justifyContent: "center", width: 28, zIndex: 1 },
    milestoneCircleReached: { backgroundColor: "#16a34a", borderColor: "#16a34a" },
    milestoneCircleReady: { backgroundColor: "#f97316", borderColor: "#f97316" },
    milestoneCard: { alignItems: "center", backgroundColor: dark ? "#162132" : "#fffcf8", borderColor: dark ? "#475569" : "#fed7aa", borderRadius: 9, borderWidth: 1, gap: 2, marginTop: 4, minHeight: 112, paddingHorizontal: 3, paddingVertical: 5, width: 64 },
    milestoneCardReady: { backgroundColor: dark ? "#3b2910" : "#fff7ed", borderColor: dark ? "#c2410c" : "#fb923c" },
    milestoneCardClaimed: { backgroundColor: dark ? "#0d3327" : "#f0fdf4", borderColor: dark ? "#166534" : "#86efac" },
    milestoneTarget: { color: colors.text, fontSize: 16, fontWeight: "900", lineHeight: 18 },
    milestoneUnit: { color: colors.mutedText, fontSize: 7, fontWeight: "900", letterSpacing: 0.45 },
    milestoneReward: { color: colors.primary, fontSize: 9, fontWeight: "900", maxWidth: 58 },
    milestoneProgress: { color: colors.mutedText, fontSize: 7.5, fontWeight: "700" },
    miniProgressTrack: { backgroundColor: dark ? "#334155" : "#ffeadb", borderRadius: 999, height: 3, overflow: "hidden", width: "100%" },
    miniProgressFill: { backgroundColor: colors.primary, borderRadius: 999, height: "100%" },
    statusPill: { alignSelf: "center", backgroundColor: dark ? "#334155" : "#f1f5f9", borderRadius: 999, paddingHorizontal: 7, paddingVertical: 3 },
    compactStatusPill: { maxWidth: "100%", paddingHorizontal: 4, paddingVertical: 2 },
    statusReady: { backgroundColor: dark ? "#431d12" : "#ffedd5" },
    statusPositive: { backgroundColor: dark ? "#14532d" : "#dcfce7" },
    statusPending: { backgroundColor: dark ? "#422006" : "#fef3c7" },
    statusText: { color: colors.mutedText, fontSize: 8.5, fontWeight: "800", textAlign: "center" },
    compactStatusText: { fontSize: 7.2 },
    statusPositiveText: { color: dark ? "#bbf7d0" : "#15803d" },
    taskAction: { alignItems: "center", alignSelf: "stretch", backgroundColor: colors.primary, borderRadius: 8, flexDirection: "row", gap: 4, justifyContent: "center", minHeight: 31, paddingHorizontal: 5 },
    compactTaskAction: { borderRadius: 6, minHeight: 26, paddingHorizontal: 2 },
    taskActionDisabled: { backgroundColor: dark ? "#475569" : "#cbd5e1" },
    taskActionText: { color: "#ffffff", fontSize: 10, fontWeight: "900", textAlign: "center" },
    compactTaskActionText: { fontSize: 7.5 },
    pressed: { opacity: 0.75 },
    unconfigured: { alignItems: "center", backgroundColor: dark ? "#162132" : "#f8fafc", borderRadius: 13, gap: 7, paddingHorizontal: 20, paddingVertical: 20 },
    unconfiguredTitle: { color: colors.text, fontSize: 14, fontWeight: "900", textAlign: "center" },
    unconfiguredText: { color: colors.mutedText, fontSize: 12, lineHeight: 18, textAlign: "center" },
    otherSection: { gap: 13 },
    otherTaskCard: { backgroundColor: colors.surface, borderColor: colors.border, borderRadius: 16, borderWidth: 1, gap: 11, padding: 15 },
    otherTaskTop: { alignItems: "flex-start", flexDirection: "row", gap: 8, justifyContent: "space-between" },
    otherTaskCopy: { flex: 1, gap: 3 },
    otherTaskType: { color: colors.primary, fontSize: 10, fontWeight: "900", letterSpacing: 0.7, textTransform: "uppercase" },
    otherTaskTitle: { color: colors.text, fontSize: 16, fontWeight: "900", lineHeight: 21 },
    otherTaskDescription: { color: colors.mutedText, fontSize: 13, lineHeight: 19 },
    progressTrack: { backgroundColor: dark ? "#334155" : "#e2e8f0", borderRadius: 999, height: 7, overflow: "hidden" },
    progressFill: { backgroundColor: colors.primary, borderRadius: 999, height: "100%" },
    otherTaskMeta: { alignItems: "center", flexDirection: "row", justifyContent: "space-between" },
    otherTaskProgress: { color: colors.mutedText, fontSize: 12, fontWeight: "700" },
    otherTaskReward: { color: colors.primary, fontSize: 15, fontWeight: "900" },
    otherTaskGuide: { backgroundColor: dark ? "#162132" : "#f8fafc", borderRadius: 10, color: colors.mutedText, fontSize: 12, lineHeight: 18, padding: 10 },
    rejectReason: { backgroundColor: dark ? "#431b22" : "#fef2f2", borderRadius: 10, color: dark ? "#fecaca" : "#b91c1c", fontSize: 12, lineHeight: 18, padding: 10 },
    otherActions: { gap: 8 },
    submitForm: { backgroundColor: dark ? "#162132" : "#f8fafc", borderRadius: 12, gap: 8, padding: 11 },
    submitLabel: { color: colors.text, fontSize: 12, fontWeight: "800" },
    submitInput: { backgroundColor: colors.surface, borderColor: colors.border, borderRadius: 10, borderWidth: 1, color: colors.text, minHeight: 82, padding: 10, textAlignVertical: "top" },
  });
}
