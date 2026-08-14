import type { EarnTask } from "@/features/earn/api";

export type TaskMilestoneGroups = {
  referral: EarnTask[];
  cashback: EarnTask[];
  other: EarnTask[];
};

function byTargetThenId(left: EarnTask, right: EarnTask): number {
  return left.target_count - right.target_count || left.id - right.id;
}

/** Keep milestone rewards server-authoritative by grouping the API tasks without synthesizing values. */
export function groupTaskMilestones(tasks: EarnTask[]): TaskMilestoneGroups {
  const referral: EarnTask[] = [];
  const cashback: EarnTask[] = [];
  const other: EarnTask[] = [];

  for (const task of tasks) {
    if (task.action === "referral") {
      referral.push(task);
    } else if (task.action === "cashback") {
      cashback.push(task);
    } else {
      other.push(task);
    }
  }

  return {
    referral: referral.sort(byTargetThenId),
    cashback: cashback.sort(byTargetThenId),
    other: other.sort(byTargetThenId)
  };
}

export function taskProgressPercent(task: EarnTask): number {
  const serverPercent = Number(task.percent);
  if (Number.isFinite(serverPercent)) return Math.max(0, Math.min(100, serverPercent));
  if (task.target_count <= 0) return 0;
  return Math.max(0, Math.min(100, (task.progress / task.target_count) * 100));
}

export function isTaskMilestoneReached(task: EarnTask): boolean {
  return task.status === "completed" || task.status === "claimed" || task.progress >= task.target_count;
}
