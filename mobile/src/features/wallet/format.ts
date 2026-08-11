import type { OrderStatus } from "@/features/wallet/types";

export function formatVnd(value: number, signed = false): string {
  const rounded = Math.round(value);
  const prefix = signed && rounded > 0 ? "+" : "";
  return `${prefix}${new Intl.NumberFormat("vi-VN", { maximumFractionDigits: 0 }).format(rounded)} đ`;
}

export function formatDate(value: string | null, includeTime = true): string {
  if (!value) return "--";
  const date = new Date(value);
  if (Number.isNaN(date.getTime())) return "--";
  return new Intl.DateTimeFormat("vi-VN", {
    day: "2-digit",
    month: "2-digit",
    year: "numeric",
    ...(includeTime ? { hour: "2-digit", minute: "2-digit" } : {})
  }).format(date);
}

export function statusLabel(status: OrderStatus): string {
  if (status === "unrecorded") return "Chờ sàn ghi nhận";
  if (status === "approved") return "Đã xác nhận";
  if (status === "rejected") return "Bị từ chối";
  return "Chờ xác nhận";
}

export function balanceTypeLabel(type: string): string {
  const labels: Record<string, string> = {
    cashback: "Hoàn tiền",
    referral: "Hoa hồng MLM",
    checkin: "Điểm danh",
    withdraw_request: "Rút tiền",
    withdraw_refund: "Hoàn tiền rút",
    admin_adjust: "Admin sửa",
    task_reward: "Nhiệm vụ"
  };
  return labels[type] ?? type.replaceAll("_", " ");
}
