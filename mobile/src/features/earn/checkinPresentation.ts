export type StreakDayState = "claimed" | "current" | "locked";

export type StreakDay = {
  day: number;
  reward: number;
  state: StreakDayState;
};

export type RewardMilestone = {
  days: number;
  amount: number;
};

function nonNegativeInteger(value: number): number {
  return Number.isFinite(value) ? Math.max(0, Math.floor(value)) : 0;
}

export function buildStreakDays(
  currentStreak: number,
  hasCheckedInToday: boolean,
  canCheckin: boolean,
  rewardCoins: number
): StreakDay[] {
  const streak = nonNegativeInteger(currentStreak);
  const focusDay = Math.max(1, hasCheckedInToday ? streak : streak + 1);
  const cycleStart = Math.floor((focusDay - 1) / 7) * 7;
  const reward = nonNegativeInteger(rewardCoins);

  return Array.from({ length: 7 }, (_, index) => {
    const day = cycleStart + index + 1;
    const isCurrent = hasCheckedInToday
      ? day === streak
      : canCheckin && day === streak + 1;

    return {
      day,
      reward,
      state: isCurrent ? "current" : day <= streak ? "claimed" : "locked"
    };
  });
}

export function normalizeMilestones(milestones: Record<string, number>): RewardMilestone[] {
  return Object.entries(milestones)
    .map(([days, amount]) => ({ days: Number(days), amount: Number(amount) }))
    .filter((item) => Number.isFinite(item.days) && item.days > 0 && Number.isFinite(item.amount) && item.amount >= 0)
    .map((item) => ({ days: Math.floor(item.days), amount: Math.floor(item.amount) }))
    .sort((left, right) => left.days - right.days);
}

export function findNextMilestone(milestones: RewardMilestone[], currentStreak: number): RewardMilestone | null {
  const streak = nonNegativeInteger(currentStreak);
  return milestones.find((milestone) => milestone.days > streak) ?? null;
}

export function milestoneProgress(currentStreak: number, milestoneDays: number): number {
  const streak = nonNegativeInteger(currentStreak);
  const target = nonNegativeInteger(milestoneDays);
  if (target === 0) return 0;
  return Math.min(100, (streak / target) * 100);
}
