import { request, requestEnvelope, type ApiResponse } from "@/api/client";
import { idempotencyHeaders, type IdempotentVariables } from "@/api/idempotency";
import type { PaginatedData } from "@/api/pagination";
import { checkinPath, giftRedemptionsPath, giftsPath, referralsPath } from "@/features/earn/contracts";

export type ReferralMember = {
  id: number;
  name: string;
  email: string | null;
  avatar: string | null;
  joined_at: string | null;
  total_commission: number;
};

export type ReferralCommission = {
  id: number;
  amount: number;
  level: number;
  status: string;
  from_member: Pick<ReferralMember, "id" | "name" | "email"> | null;
  order_id: string | null;
  product_name: string | null;
  created_at: string | null;
};

export type ReferralData = {
  referral_code: string | null;
  referral_link: string;
  rates: { f1_rate: number; f2_rate: number; f2_enabled: boolean };
  stats: {
    f1_count: number;
    f2_count: number;
    total_commission: number;
    pending_commission: number;
    total_referral_earned: number;
  };
  f1_members: ReferralMember[];
  f2_members: ReferralMember[];
  commissions: PaginatedData<ReferralCommission>;
};

export type CheckinHistoryItem = {
  coins_earned: number;
  streak_days: number;
  checked_in_date: string | null;
};

export type CheckinData = {
  has_checked_in_today: boolean;
  can_checkin: boolean;
  ineligible_reason: string | null;
  current_streak: number;
  reward_coins: number;
  milestones: Record<string, number>;
  history: PaginatedData<CheckinHistoryItem>;
};

export type CheckinResult = {
  coins_earned: number;
  streak_days: number;
  is_bonus: boolean;
  bonus_amount: number;
  new_balance: number;
};

export const checkinQueryKey = ["earn", "checkin"] as const;

export type EarnTask = {
  id: number;
  title: string;
  description: string | null;
  guide: string | null;
  type: string;
  type_label: string;
  action: string;
  action_label: string;
  icon: string | null;
  badge_color: string | null;
  target_count: number;
  reward_amount: number;
  reward_type: string;
  start_at: string | null;
  end_at: string | null;
  progress: number;
  percent: number;
  status: string;
  submitted_at: string | null;
  submit_note: string | null;
  reject_reason: string | null;
};

export type TaskData = {
  items: EarnTask[];
  stats: {
    claimed: number;
    completed: number;
    in_progress: number;
    pending: number;
    total_earned: number;
  };
};

export type Gift = {
  id: number;
  title: string;
  image: string | null;
  description: string | null;
  price: number;
  stock: number;
  type: string;
  tag: string | null;
};

export type GiftListData = PaginatedData<Gift> & { available_tags: string[] };

export type GiftRedemption = {
  id: number;
  code: string;
  gift_title: string | null;
  gift_image: string | null;
  amount: number;
  status: string;
  created_at: string | null;
  processed_at: string | null;
};

export type GiftRedemptionInput = {
  gift_id: number;
  fullname: string;
  phone: string;
  email: string;
  address?: string;
  notes?: string;
};

export function fetchReferrals(
  page: number,
  filters: { level?: "1" | "2"; status?: "pending" | "approved" },
  signal?: AbortSignal
): Promise<ReferralData> {
  return request(referralsPath(page, filters.level, filters.status), { signal });
}

export function fetchCheckin(page: number, signal?: AbortSignal): Promise<CheckinData> {
  return request(checkinPath(page), { signal });
}

export function checkinQueryOptions() {
  return {
    queryKey: checkinQueryKey,
    initialPageParam: 1,
    queryFn: ({ pageParam, signal }: { pageParam: number; signal: AbortSignal }) => fetchCheckin(pageParam, signal),
    getNextPageParam: (page: CheckinData) => page.history.pagination.current_page < page.history.pagination.last_page
      ? page.history.pagination.current_page + 1
      : undefined
  };
}

export function performCheckin(): Promise<ApiResponse<CheckinResult>> {
  return requestEnvelope("checkin", { method: "POST" });
}

export function fetchTasks(signal?: AbortSignal): Promise<TaskData> {
  return request("tasks", { signal });
}

export function syncTask(taskId: number): Promise<ApiResponse<{ task_id: number; progress: number; target: number; percent: number; status: string }>> {
  return requestEnvelope(`tasks/${taskId}/sync`, { method: "GET" });
}

export function claimTask(variables: IdempotentVariables<{ taskId: number }>): Promise<ApiResponse<{ amount: number }>> {
  return requestEnvelope(`tasks/${variables.payload.taskId}/claim`, {
    method: "POST",
    headers: idempotencyHeaders(variables.idempotencyKey)
  });
}

export function submitTask(input: { taskId: number; note?: string }): Promise<ApiResponse<{ task_id: number; status: string }>> {
  return requestEnvelope(`tasks/${input.taskId}/submit`, {
    method: "POST",
    body: input.note ? { note: input.note } : {}
  });
}

export function fetchGifts(
  page: number,
  filters: { search?: string; tag?: string; type?: string; sort?: string },
  signal?: AbortSignal
): Promise<GiftListData> {
  return request(giftsPath(page, filters), { signal });
}

export function fetchGiftRedemptions(page: number, search?: string, status?: string, signal?: AbortSignal): Promise<PaginatedData<GiftRedemption>> {
  return request(giftRedemptionsPath(page, search, status), { signal });
}

export function redeemGift(variables: IdempotentVariables<GiftRedemptionInput>): Promise<ApiResponse<{ code: string; amount: number; status: string }>> {
  return requestEnvelope("gifts/redeem", {
    method: "POST",
    body: variables.payload,
    headers: idempotencyHeaders(variables.idempotencyKey)
  });
}

export function redeemGiftCode(variables: IdempotentVariables<{ code: string }>): Promise<ApiResponse<{ amount: number }>> {
  return requestEnvelope("giftcode/redeem", {
    method: "POST",
    body: variables.payload,
    headers: idempotencyHeaders(variables.idempotencyKey)
  });
}
