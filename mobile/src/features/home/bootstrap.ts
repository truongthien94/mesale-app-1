import type { User } from "@/api/authContract";

export type HomeAuthPreview = {
  id: number;
  name: string | null;
  email: string | null;
  avatar: string | null;
  referralCode: string | null;
  wallet: {
    balance: number;
    totalCashback: number;
    totalReferralEarned: number;
    totalWithdrawn: number;
  };
};

export function createHomeAuthPreview(user: User | null): HomeAuthPreview | null {
  if (!user?.financialSnapshot) return null;

  return {
    id: user.id,
    name: user.name ?? null,
    email: user.email ?? null,
    avatar: user.avatar ?? null,
    referralCode: user.referral_code ?? null,
    wallet: user.financialSnapshot
  };
}
