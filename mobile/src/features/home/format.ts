export function formatAccountMoney(value: number | null, language: "vi" | "en"): string {
  const amount = value ?? 0;

  if (language === "vi") {
    return `${new Intl.NumberFormat("vi-VN", { maximumFractionDigits: 0 }).format(amount)}đ`;
  }

  return new Intl.NumberFormat("en-US", {
    style: "currency",
    currency: "VND",
    maximumFractionDigits: 0
  }).format(amount);
}
