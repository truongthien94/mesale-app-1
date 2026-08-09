import { request, requestEnvelope, type ApiResponse } from "@/api/client";
import type { PaginatedData } from "@/api/pagination";
import { notificationsPath } from "@/features/notifications/contracts";

export type NotificationItem = {
  id: number;
  title: string;
  content: string;
  type: "general" | "personal";
  is_read: boolean;
  created_at: string | null;
};

export type NotificationListData = PaginatedData<NotificationItem> & { unread_total: number };

export type NotificationUnreadCount = {
  unread_total: number;
  general: number;
  personal: number;
};

export function fetchNotifications(
  page: number,
  filters: { type?: "general" | "personal"; filter?: "all" | "unread" },
  signal?: AbortSignal
): Promise<NotificationListData> {
  return request(notificationsPath(page, filters), { signal });
}

export function fetchUnreadCount(signal?: AbortSignal): Promise<NotificationUnreadCount> {
  return request("notifications/unread-count", { signal });
}

export function markNotificationRead(id: number): Promise<ApiResponse<null>> {
  return requestEnvelope(`notifications/${id}/read`, { method: "POST" });
}

export function markAllNotificationsRead(): Promise<ApiResponse<{ updated: number }>> {
  return requestEnvelope("notifications/read-all", { method: "POST" });
}
