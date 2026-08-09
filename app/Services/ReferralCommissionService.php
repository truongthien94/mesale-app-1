<?php

namespace App\Services;

use App\Models\CashbackHistory;
use App\Models\User;
use App\Models\ReferralCommission;
use App\Models\Setting;
use App\Models\BalanceLog;
use App\Models\Notification;
use App\Models\ActivityLog;
use App\Helpers\MoneyHelper;

/**
 * Service tập trung xử lý phân chia hoa hồng tiếp thị liên kết MLM 2 tầng (F1 & F2).
 * Được gọi từ CashbackController (duyệt tay) và ToolController (import CSV đối soát).
 * Tập trung logic vào 1 nơi duy nhất để tránh duplicate code và đảm bảo tính nhất quán.
 */
class ReferralCommissionService
{
    /**
     * Phân chia hoa hồng tiếp thị liên kết MLM 2 tầng khi đơn hoàn tiền được duyệt.
     * Tính toán dựa trên cashback_amount thực tế hoàn lại cho F0 (người mua).
     * Sử dụng lockForUpdate và kiểm tra trùng để chống cộng tiền đôi.
     *
     * @param CashbackHistory $cashback Đơn hoàn tiền đã được duyệt
     * @param User $buyer Người mua hàng (F0) — đã được lockForUpdate từ bên ngoài
     * @param string $source Nguồn phát sinh ('manual' hoặc 'csv') để ghi log phân biệt
     */
    public static function distribute(CashbackHistory $cashback, User $buyer, string $source = 'manual'): void
    {
        // Kiểm tra tính năng tiếp thị liên kết có được bật không
        $referralEnabled = Setting::getVal('referral_enabled', '1') === '1';
        if (!$referralEnabled || !$buyer->referred_by) {
            return;
        }

        $referralF2Enabled = Setting::getVal('referral_f2_enabled', '1') === '1';
        $sourceLabel = $source === 'csv' ? ' (Đối soát tự động qua CSV)' : '';

        // ========== TẦNG 1: Người giới thiệu trực tiếp của F0 (F1 Referrer) ==========
        $f1Referrer = User::where('id', $buyer->referred_by)->lockForUpdate()->first();
        if (!$f1Referrer) {
            return;
        }

        $f1Rate = (float)Setting::getVal('referral_f1_rate', 5);
        // Chuẩn hoá về số nguyên đồng vì đồng Việt Nam không có đơn vị nhỏ hơn 1 đồng
        $f1Amount = MoneyHelper::round($cashback->cashback_amount * ($f1Rate / 100));

        if ($f1Amount > 0) {
            // Kiểm tra trùng hoa hồng F1 (bảo vệ kép cùng UNIQUE constraint trên DB)
            $existingF1 = ReferralCommission::where('cashback_history_id', $cashback->id)
                ->where('referrer_id', $f1Referrer->id)
                ->where('level', 1)
                ->exists();

            if (!$existingF1) {
                self::creditCommission($f1Referrer, $buyer, $cashback, $f1Amount, 1, $sourceLabel);
            }

            // ========== TẦNG 2: Người giới thiệu của F1 (F2 Referrer) ==========
            if ($referralF2Enabled && $f1Referrer->referred_by) {
                $f2Referrer = User::where('id', $f1Referrer->referred_by)->lockForUpdate()->first();

                if ($f2Referrer) {
                    $f2Rate = (float)Setting::getVal('referral_f2_rate', 2);
                    // Chuẩn hoá về số nguyên đồng vì đồng Việt Nam không có đơn vị nhỏ hơn 1 đồng
                    $f2Amount = MoneyHelper::round($cashback->cashback_amount * ($f2Rate / 100));

                    if ($f2Amount > 0) {
                        // Kiểm tra trùng hoa hồng F2
                        $existingF2 = ReferralCommission::where('cashback_history_id', $cashback->id)
                            ->where('referrer_id', $f2Referrer->id)
                            ->where('level', 2)
                            ->exists();

                        if (!$existingF2) {
                            self::creditCommission($f2Referrer, $buyer, $cashback, $f2Amount, 2, $sourceLabel);
                        }
                    }
                }
            }
        }
    }

    /**
     * Thực hiện cộng hoa hồng cho một referrer cụ thể (F1 hoặc F2).
     * Bao gồm: tạo record commission, cộng ví, ghi log, gửi thông báo.
     *
     * @param User $referrer Người nhận hoa hồng (đã được lockForUpdate)
     * @param User $buyer Người mua hàng phát sinh hoa hồng (F0)
     * @param CashbackHistory $cashback Đơn hoàn tiền gốc
     * @param float $amount Số tiền hoa hồng
     * @param int $level Tầng MLM (1 = F1, 2 = F2)
     * @param string $sourceLabel Nhãn nguồn phát sinh để ghi vào log
     */
    private static function creditCommission(
        User $referrer,
        User $buyer,
        CashbackHistory $cashback,
        float $amount,
        int $level,
        string $sourceLabel
    ): void {
        // Lưu vết hoa hồng vào bảng referral_commissions
        ReferralCommission::create([
            'referrer_id' => $referrer->id,
            'referred_id' => $buyer->id,
            'cashback_history_id' => $cashback->id,
            'amount' => $amount,
            'level' => $level,
            'status' => 'approved'
        ]);

        // Cộng tiền thưởng vào ví referrer
        // Dùng total_referral_earned thay vì total_cashback để tách biệt nguồn thu nhập
        $oldBalance = $referrer->balance;
        $referrer->balance += $amount;
        $referrer->total_referral_earned += $amount;
        $referrer->save();

        // Ghi nhận biến động số dư
        BalanceLog::write(
            $referrer,
            $oldBalance,
            $amount,
            $referrer->balance,
            'referral',
            "Nhận hoa hồng MLM F{$level} từ đơn hàng {$cashback->order_id} của thành viên {$buyer->name}"
        );

        // Gửi thông báo cho referrer
        Notification::create([
            'user_id' => $referrer->id,
            'title' => "Bạn nhận được hoa hồng F{$level}",
            'content' => "Nhận +" . number_format($amount) . "đ hoa hồng F{$level} từ đơn hàng hoàn tiền của thành viên {$buyer->name}."
        ]);

        // Ghi nhật ký hoạt động
        ActivityLog::log(
            "Cộng +" . number_format($amount) . "đ hoa hồng F{$level} từ đơn hàng của {$buyer->email}{$sourceLabel}",
            $referrer->id
        );
    }

    /**
     * Thu hồi hoa hồng tiếp thị liên kết MLM 2 tầng khi đơn hoàn tiền bị thu hồi (Clawback).
     * Trừ tiền ví của referrer (F1/F2), giảm total_referral_earned, cập nhật status commission, ghi logs.
     * Áp dụng Transaction và lockForUpdate để chống Race Condition.
     *
     * @param CashbackHistory $cashback Đơn hoàn tiền bị thu hồi
     * @param string $source Nguồn phát sinh ('sync' hoặc 'manual')
     */
    public static function clawback(CashbackHistory $cashback, string $source = 'manual'): void
    {
        // Truy vấn các bản ghi hoa hồng đã được duyệt của đơn hàng này
        $commissions = ReferralCommission::where('cashback_history_id', $cashback->id)
            ->where('status', 'approved')
            ->get();

        if ($commissions->isEmpty()) {
            return;
        }

        $sourceLabel = $source === 'sync' ? ' (Tự động đối soát qua API)' : '';

        foreach ($commissions as $comm) {
            // Lock dòng User để đảm bảo đồng bộ số dư chuẩn xác
            $referrer = User::where('id', $comm->referrer_id)->lockForUpdate()->first();
            if (!$referrer) {
                continue;
            }

            // Đổi trạng thái hoa hồng giới thiệu sang rejected
            $comm->status = 'rejected';
            $comm->save();

            // Trừ tiền trong ví của người giới thiệu (referrer)
            $oldBalance = $referrer->balance;
            $referrer->balance -= $comm->amount;
            $referrer->total_referral_earned -= $comm->amount;
            $referrer->save();

            $buyerName = $comm->referred ? $comm->referred->name : 'Thành viên';

            // Ghi log biến động số dư ví
            BalanceLog::write(
                $referrer,
                $oldBalance,
                -$comm->amount,
                $referrer->balance,
                'referral',
                "Thu hồi hoa hồng MLM F{$comm->level} từ đơn hàng {$cashback->order_id} của thành viên {$buyerName} (Đơn hàng bị hủy)"
            );

            // Gửi thông báo cho người giới thiệu
            Notification::create([
                'user_id' => $referrer->id,
                'title' => __("Thu hồi hoa hồng F:level", ['level' => $comm->level]),
                'content' => __("Thu hồi -:amountđ hoa hồng F:level từ đơn hàng hoàn tiền của thành viên :buyer do đơn hàng đã bị hủy/hoàn tiền trên sàn.", [
                    'amount' => number_format($comm->amount),
                    'level' => $comm->level,
                    'buyer' => $buyerName
                ])
            ]);

            // Ghi nhật ký hoạt động hệ thống
            $activityMsg = __("Thu hồi -:amountđ hoa hồng F:level từ đơn hàng của referrer ID :referrer_id:label", [
                'amount' => number_format($comm->amount),
                'level' => $comm->level,
                'referrer_id' => $referrer->id,
                'label' => $sourceLabel
            ]);
            ActivityLog::log($activityMsg, $referrer->id);
        }
    }
}
