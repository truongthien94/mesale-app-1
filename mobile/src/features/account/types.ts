export type AccountPreferences = {
  locale: string;
  currency: string;
};

export type AccountData = {
  id: number;
  name: string;
  email: string;
  phone: string | null;
  avatar: string | null;
  referral_code: string | null;
  referral_code_eligible?: boolean;
  referral_code_expires_at?: string | null;
  status: string;
  email_verified: boolean;
  preferences: AccountPreferences;
  wallet: {
    balance: number;
    total_cashback: number;
    total_referral_earned: number;
    total_withdrawn: number;
    currency: string;
  };
  stats: {
    orders_total: number;
    orders_pending: number;
    orders_approved: number;
    orders_rejected: number;
    referrals_count: number;
    withdrawals_pending: number;
  };
  created_at: string | null;
};

export type SecurityStatus = {
  google2fa_enabled: boolean;
  email_otp_enabled: boolean;
  email_verified: boolean;
};

export type TwoFactorSetup = {
  secret_key: string;
  otpauth_url: string;
  qr_code_url: string;
};

export type AccountSession = {
  id: number;
  device_name: string;
  last_ip: string | null;
  last_used_at: string | null;
  created_at: string | null;
  expires_at: string | null;
  is_current: boolean;
};

export type SessionCollection = { items: AccountSession[] };
