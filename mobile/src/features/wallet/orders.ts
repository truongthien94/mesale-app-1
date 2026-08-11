import type { Order, OrderRecordType } from "@/features/wallet/types";

export function orderRecordType(order: Pick<Order, "record_type">): OrderRecordType {
  return order.record_type === "unrecorded" ? "unrecorded" : "order";
}

export function isRecordedOrder(order: Pick<Order, "record_type">): boolean {
  return orderRecordType(order) === "order";
}

export function orderListKey(order: Pick<Order, "id" | "record_type">): string {
  return `${orderRecordType(order)}:${order.id}`;
}

export function visibleOrdersForTab(
  orders: Order[],
  tab: "all" | "unrecorded",
  showUnrecorded: boolean | undefined
): Order[] {
  if (tab === "all") return orders;
  if (showUnrecorded !== true) return [];
  return orders.filter((order) => orderRecordType(order) === "unrecorded");
}

export function secureOrderImageUrl(value: string | null): string | null {
  if (!value) return null;

  try {
    const url = new URL(value);
    if (url.username || url.password) return null;
    if (url.protocol === "http:") url.protocol = "https:";
    return url.protocol === "https:" ? url.toString() : null;
  } catch {
    return null;
  }
}
