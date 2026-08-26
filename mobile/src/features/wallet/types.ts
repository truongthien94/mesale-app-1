import type { PaginatedData } from "@/api/pagination";

export type OrderStatus = "pending" | "approved" | "rejected" | "unrecorded";
export type OrderRecordType = "order" | "unrecorded";
export type OrderPlatform = "shopee" | "tiktok" | "lazada";
export type PaymentMethod = "bank" | "wallet" | "momo";

export type Order = {
  id: number;
  record_type?: OrderRecordType;
  order_id: string | null;
  trans_id: string | null;
  platform: string | null;
  product_name: string | null;
  product_image: string | null;
  affiliate_url?: string | null;
  original_price: number;
  commission_amount: number;
  cashback_amount: number;
  cashback_rate: number;
  status: OrderStatus;
  rejected_reason: string | null;
  approved_at: string | null;
  created_at: string | null;
};

export type OrderQueryFilters = {
  status?: OrderStatus;
  platform?: OrderPlatform;
  search?: string;
  startDate?: string;
  endDate?: string;
};

export type OrderDetail = Order & {
  shop_name: string | null;
};

export type BalanceLog = {
  id: number;
  type: string;
  description: string;
  amount_before: number;
  amount_change: number;
  amount_after: number;
  is_credit: boolean;
  created_at: string | null;
};

export type Withdrawal = {
  id: number;
  code: string;
  amount: number;
  fee: number;
  real_amount: number;
  payment_method: PaymentMethod;
  bank_name: string | null;
  account_number: string;
  account_name: string;
  status: OrderStatus;
  notes: string | null;
  processed_at: string | null;
  created_at: string | null;
};

export type PaymentAccount = {
  id: number;
  payment_method: Exclude<PaymentMethod, "momo">;
  bank_name: string;
  account_number: string;
  account_name: string;
  is_default: boolean;
  created_at: string | null;
};

export type AccountSummary = {
  id: number;
  name: string;
  wallet: {
    balance: number;
    total_cashback: number;
    total_referral_earned: number;
    total_withdrawn: number;
    currency: "VND" | string;
  };
  stats: {
    orders_total: number;
    orders_pending: number;
    orders_approved: number;
    orders_rejected: number;
    withdrawals_pending: number;
  };
};

export type WithdrawConfig = {
  enabled: boolean;
  min_amount: number;
  fee_type: "percentage" | "fixed" | string;
  fee_value: number;
  fee_value_unit: "percent" | "vnd" | string;
  otp_required: boolean;
  bank_enabled: boolean;
  wallet_enabled: boolean;
  allowed_banks: string[];
  allowed_wallets: string[];
};

export type AppConfig = {
  withdraw: WithdrawConfig;
  features: {
    ios_payout_features_enabled: boolean;
    api_orders: boolean;
    api_withdraw: boolean;
    api_balance_logs: boolean;
    [key: string]: boolean;
  };
};

export type WithdrawalPayload = {
  amount: number;
  payment_method: PaymentMethod;
  account_number: string;
  account_name: string;
  bank_name?: string;
  wallet_name?: string;
  otp_code?: string;
};

export type WithdrawalCreated = Pick<Withdrawal, "id" | "code" | "amount" | "fee" | "real_amount" | "status">;

export type PaymentAccountPayload = {
  payment_method: Exclude<PaymentMethod, "momo">;
  bank_name: string;
  account_number: string;
  account_name: string;
  is_default: boolean;
};

export type PaymentAccountCollection = { items: PaymentAccount[]; total: number };
export type OrdersPage = PaginatedData<Order> & {
  meta?: {
    show_unrecorded: boolean;
  };
};
export type BalanceLogsPage = PaginatedData<BalanceLog>;
export type WithdrawalsPage = PaginatedData<Withdrawal>;
