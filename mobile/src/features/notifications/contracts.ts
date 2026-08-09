type QueryValue = string | number | undefined;

export function notificationsPath(
  page: number,
  filters: { type?: "general" | "personal"; filter?: "all" | "unread" }
): string {
  const values: Record<string, QueryValue> = {
    type: filters.type,
    filter: filters.filter,
    page,
    per_page: 15
  };
  const query = Object.entries(values)
    .filter((entry): entry is [string, string | number] => entry[1] !== undefined && entry[1] !== "")
    .map(([key, value]) => `${encodeURIComponent(key)}=${encodeURIComponent(String(value))}`)
    .join("&");

  return `notifications?${query}`;
}
