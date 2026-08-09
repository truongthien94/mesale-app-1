export type QueryValue = string | number | undefined;

export function appendQuery(path: string, values: Record<string, QueryValue>): string {
  const query = Object.entries(values)
    .filter((entry): entry is [string, string | number] => entry[1] !== undefined && entry[1] !== "")
    .map(([key, value]) => `${encodeURIComponent(key)}=${encodeURIComponent(String(value))}`)
    .join("&");

  return query ? `${path}?${query}` : path;
}

export function referralsPath(page: number, level?: "1" | "2", status?: "pending" | "approved"): string {
  return appendQuery("referrals", { level, status, page, per_page: 15 });
}

export function checkinPath(page: number): string {
  return appendQuery("checkin", { page });
}

export function giftsPath(
  page: number,
  filters: { search?: string; tag?: string; type?: string; sort?: string }
): string {
  return appendQuery("gifts", { ...filters, page, per_page: 12 });
}

export function giftRedemptionsPath(page: number, search?: string, status?: string): string {
  return appendQuery("gifts/redemptions", { search, status, page, per_page: 10 });
}

export function canClaimTask(status: string): boolean {
  return status === "completed";
}

export function canSubmitCustomTask(action: string, status: string): boolean {
  return action === "custom" && status === "in_progress";
}

export function requiresPhysicalAddress(type: string): boolean {
  return type === "physical";
}
