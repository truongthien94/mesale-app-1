<?php

namespace App\Services;

use App\Models\CashbackHistory;
use App\Models\User;
use App\Models\Setting;
use App\Models\BalanceLog;
use App\Models\Notification;
use App\Models\ActivityLog;
use App\Models\ReferralCommission;
use App\Helpers\MoneyHelper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CashbackApprovalService
{
    /**
     * Phê duyệt đơn hàng hoàn tiền Shopee.
     * Giải thích: Thực hiện cộng số dư cho người mua F0, ghi log biến động số dư, gửi thông báo, 
     * phân chia hoa hồng MLM 2 tầng (F1/F2), gửi thông báo Telegram khi hoàn thành.
     * Áp dụng Transaction và lockForUpdate để chống Race Condition.
     *
     * @param CashbackHistory $cashback Đối tượng bản ghi cashback cần duyệt
     * @param float $realPrice Giá bán gốc của sản phẩm (nhận từ Shopee)
     * @param float $realCommission Tổng hoa hồng Shopee chi trả (nhận từ Shopee)
     * @param array $options Cấu hình tuỳ chọn như 'source' (manual/csv/sync), 'cashback_amount' (số tiền duyệt tay tuỳ chỉnh)
     * @return bool Trả về true nếu duyệt thành công
     */
    public static function approve(CashbackHistory $cashback, float $realPrice = 0, float $realCommission = 0, array $options = []): bool
    {
        return DB::transaction(function () use ($cashback, $realPrice, $realCommission, $options) {
            // Khóa bản ghi cashback_history để đảm bảo không bị xử lý trùng lặp bởi tiến trình song song
            $cashback = CashbackHistory::where('id', $cashback->id)->lockForUpdate()->firstOrFail();

            if ($cashback->status !== 'pending') {
                return false;
            }

            $source = $options['source'] ?? 'manual';
            $platformSuffix = self::platformSuffix($cashback->platform);
            $platformName = self::platformName($cashback->platform);

            $cashbackSystemRate = (float)Setting::getVal("{$platformSuffix}_cashback_rate", 70);

            // Cập nhật giá trị gốc của đơn hàng nếu nhận được thông tin từ đối soát
            if ($realPrice > 0) {
                $cashback->original_price = $realPrice;
            }

            // Tính toán số tiền hoàn dựa trên hoa hồng thực tế nhận từ sàn thương mại
            if ($realCommission > 0) {
                $cashback->commission_amount = $realCommission;
                
                // Áp dụng công thức tính tiền hoàn dựa trên cấu hình hệ thống: lấy hoa hồng thực tế nhân tỷ lệ hoàn tiền
                // Chuẩn hoá về số nguyên đồng vì đồng Việt Nam không có đơn vị nhỏ hơn 1 đồng
                $cashback->cashback_amount = MoneyHelper::round($realCommission * ($cashbackSystemRate / 100));
            }

            // Cho phép ghi đè số tiền hoàn trả cụ thể nếu admin nhập thủ công lúc duyệt tay
            if (isset($options['cashback_amount'])) {
                $customAmount = MoneyHelper::round($options['cashback_amount']);
                if ($customAmount >= 0) {
                    $cashback->cashback_amount = $customAmount;
                }
            }

            // Cập nhật tỷ lệ hoàn tiền thực tế cho đơn hàng
            // Chặn trên giá trị tỷ lệ để không vượt ngưỡng lưu trữ của cột decimal(5,2)
            if ($cashback->original_price > 0) {
                $cashback->cashback_rate = \App\Helpers\CashbackHelper::clampRate(
                    ($cashback->cashback_amount / $cashback->original_price) * 100
                );
            }

            // Cập nhật nguồn phê duyệt vào click_metadata
            $meta = $cashback->click_metadata ?? [];
            $meta['approve_source'] = $source;
            $cashback->click_metadata = $meta;

            // Đổi trạng thái đơn hàng thành hoàn thành
            $cashback->status = 'approved';
            $cashback->approved_at = now();

            // Ghi mốc thời gian duyệt đơn vào dòng thời gian trạng thái
            $cashback->pushTimeline('approved', [
                'source' => $source,
                'amount' => (float)$cashback->cashback_amount,
            ]);

            $cashback->save();

            // Cộng tiền vào số dư ví của khách hàng (F0) và khóa dòng bản ghi User
            $buyer = User::where('id', $cashback->user_id)->lockForUpdate()->firstOrFail();
            $oldBalance = $buyer->balance;
            $buyer->balance += $cashback->cashback_amount;
            $buyer->total_cashback += $cashback->cashback_amount;
            $buyer->save();

            // Định nghĩa ghi chú biến động số dư ví cho người dùng
            $logMessage = "Nhận hoàn tiền {$platformName} đơn hàng {$cashback->order_id}";

            // Ghi nhận biến động số dư ví
            BalanceLog::write(
                $buyer,
                $oldBalance,
                $cashback->cashback_amount,
                $buyer->balance,
                'cashback',
                $logMessage
            );

            // Gửi thông báo đến tài khoản thành viên trên web
            Notification::create([
                'user_id' => $buyer->id,
                'title' => __('Đơn hoàn tiền đã được duyệt!'),
                'content' => __("Đơn hàng :platform :order_id của bạn đã được phê duyệt. Số tiền +:amountđ đã được cộng vào số dư ví.", [
                    'platform' => $platformName,
                    'order_id' => $cashback->order_id,
                    'amount' => number_format($cashback->cashback_amount)
                ])
            ]);

            // Ghi log hoạt động của admin hoặc hệ thống
            $adminId = auth()->id();
            $activityMsg = "";
            if ($source === 'manual') {
                $activityMsg = "Duyệt đơn hoàn tiền ID {$cashback->id} cho user {$buyer->email}";
            } elseif ($source === 'sync') {
                $activityMsg = "Tự động duyệt đơn hoàn tiền ID {$cashback->id} (Mã đơn: {$cashback->order_id}) qua đồng bộ {$platformName} API. Số tiền hoàn: " . number_format($cashback->cashback_amount) . "đ";
                $adminId = null; // Chạy cron không có phân đăng nhập của admin
            } elseif ($source === 'csv') {
                $activityMsg = "Tự động duyệt đơn hoàn tiền ID {$cashback->id} (Mã đơn: {$cashback->order_id}) qua import đối soát CSV. Số tiền hoàn: " . number_format($cashback->cashback_amount) . "đ";
            }
            ActivityLog::log($activityMsg, $adminId);

            // Phân chia hoa hồng MLM 2 tầng F1/F2 cho tuyến trên
            ReferralCommissionService::distribute($cashback, $buyer, $source);

            // GỬI THÔNG BÁO TELEGRAM KHI DUYỆT ĐƠN CASHBACK THÀNH CÔNG
            $f1Comm = ReferralCommission::where('cashback_history_id', $cashback->id)->where('level', 1)->first();
            $f2Comm = ReferralCommission::where('cashback_history_id', $cashback->id)->where('level', 2)->first();

            $f1User = $f1Comm ? User::find($f1Comm->referrer_id) : null;
            $f2User = $f2Comm ? User::find($f2Comm->referrer_id) : null;

            try {
                Setting::sendTelegramTemplate('telegram_template_cashback_approved', [
                    'name' => $buyer->name,
                    'email' => $buyer->email,
                    'product_name' => $cashback->product_name,
                    'price' => number_format($cashback->original_price),
                    'cashback_amount' => number_format($cashback->cashback_amount),
                    'commission' => number_format($cashback->commission_amount),
                    'profit' => number_format($cashback->commission_amount - $cashback->cashback_amount),
                    'f1_name' => $f1User ? $f1User->name : 'N/A',
                    'f1_commission' => $f1Comm ? number_format($f1Comm->amount) : '0',
                    'f2_name' => $f2User ? $f2User->name : 'N/A',
                    'f2_commission' => $f2Comm ? number_format($f2Comm->amount) : '0',
                    'platform' => $platformName,
                ]);
            } catch (\Exception $e) {
                Log::error('Lỗi gửi Telegram khi duyệt đơn cashback qua CashbackApprovalService: ' . $e->getMessage());
            }

            // GỬI EMAIL THÔNG BÁO KHI DUYỆT ĐƠN CASHBACK THÀNH CÔNG (SỬ DỤNG HÀNG ĐỢI)
            try {
                if ($buyer && !empty($buyer->email)) {
                    Setting::sendEmailQueue($buyer->email, 'cashback_approved', [
                        'name' => $buyer->name,
                        'email' => $buyer->email,
                        'order_id' => $cashback->order_id,
                        'product_name' => $cashback->product_name,
                        'price' => number_format($cashback->original_price),
                        'cashback_amount' => number_format($cashback->cashback_amount),
                        'dashboard_url' => route('dashboard'), // Liên kết kiểm tra ví thành viên
                        'platform' => $platformName,
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('Lỗi gửi Email thông báo duyệt đơn hoàn tiền thành công: ' . $e->getMessage());
            }

            return true;
        });
    }

    /**
     * Từ chối đơn hàng hoàn tiền Shopee/TikTok Shop.
     * Giải thích: Chuyển trạng thái đơn sang rejected, ghi lý do từ chối, gửi thông báo hệ thống và ghi log hoạt động.
     * Áp dụng Transaction và lockForUpdate để tránh Race Condition.
     *
     * @param CashbackHistory $cashback Đối tượng bản ghi cashback cần từ chối
     * @param string $reason Lý do từ chối đơn hàng
     * @param array $options Cấu hình tuỳ chọn như 'source' (manual/csv/sync)
     * @return bool Trả về true nếu từ chối thành công
     */
    public static function reject(CashbackHistory $cashback, string $reason, array $options = []): bool
    {
        return DB::transaction(function () use ($cashback, $reason, $options) {
            // Khóa bản ghi cashback_history để đảm bảo không bị xử lý trùng lặp bởi tiến trình song song
            $cashback = CashbackHistory::where('id', $cashback->id)->lockForUpdate()->firstOrFail();

            if ($cashback->status !== 'pending') {
                return false;
            }

            $source = $options['source'] ?? 'manual';
            $platformName = self::platformName($cashback->platform);

            // Cập nhật trạng thái từ chối
            $cashback->status = 'rejected';
            $cashback->rejected_reason = $reason;

            // Cập nhật nguồn xử lý vào click_metadata
            $meta = $cashback->click_metadata ?? [];
            $meta['approve_source'] = $source;
            $cashback->click_metadata = $meta;

            // Ghi mốc thời gian từ chối đơn vào dòng thời gian trạng thái
            $cashback->pushTimeline('rejected', [
                'source' => $source,
                'reason' => $reason,
            ]);

            $cashback->save();

            // Gửi thông báo đến tài khoản khách hàng trên web
            Notification::create([
                'user_id' => $cashback->user_id,
                'title' => __('Đơn hoàn tiền bị từ chối'),
                'content' => __("Đơn hàng :platform :order_id của bạn bị từ chối duyệt. Lý do: :reason", [
                    'platform' => $platformName,
                    'order_id' => $cashback->order_id,
                    'reason' => $reason
                ])
            ]);

            // Ghi log hoạt động của admin hoặc hệ thống
            $adminId = auth()->id();
            $activityMsg = "";
            if ($source === 'manual') {
                $activityMsg = "Từ chối đơn hoàn tiền ID {$cashback->id}. Lý do: {$reason}";
            } elseif ($source === 'sync') {
                $activityMsg = "Tự động từ chối đơn hoàn tiền ID {$cashback->id} (Mã đơn: {$cashback->order_id}) qua đồng bộ {$platformName} API. Lý do: {$reason}";
                $adminId = null; // Chạy cron không có phiên đăng nhập của admin
            } elseif ($source === 'csv') {
                $activityMsg = "Tự động từ chối đơn hoàn tiền ID {$cashback->id} (Mã đơn: {$cashback->order_id}) qua import đối soát CSV. Lý do: {$reason}";
            }
            ActivityLog::log($activityMsg, $adminId);

            return true;
        });
    }

    /**
     * Thu hồi đơn hàng hoàn tiền đã phê duyệt trước đó (Clawback).
     * Giải thích: Trừ tiền hoàn của F0, giảm total_cashback, chuyển trạng thái đơn sang rejected,
     * đồng thời tự động thu hồi tiền hoa hồng MLM F1/F2 (nếu có) thông qua ReferralCommissionService.
     * Áp dụng Transaction và lockForUpdate để chống Race Condition.
     *
     * @param CashbackHistory $cashback Đối tượng bản ghi cashback cần thu hồi
     * @param string $reason Lý do thu hồi
     * @param array $options Cấu hình tuỳ chọn như 'source' (manual/csv/sync)
     * @return bool Trả về true nếu thu hồi thành công
     */
    public static function clawback(CashbackHistory $cashback, string $reason, array $options = []): bool
    {
        return DB::transaction(function () use ($cashback, $reason, $options) {
            // Khóa dòng bản ghi cashback_history để đảm bảo không bị xử lý trùng lặp bởi tiến trình song song
            $cashback = CashbackHistory::where('id', $cashback->id)->lockForUpdate()->firstOrFail();

            // Chỉ thu hồi những đơn hàng đã duyệt trước đó
            if ($cashback->status !== 'approved') {
                return false;
            }

            $source = $options['source'] ?? 'manual';
            $platformName = self::platformName($cashback->platform);

            // Cập nhật trạng thái sang bị từ chối/hủy và lưu lý do thu hồi
            $cashback->status = 'rejected';
            $cashback->rejected_reason = $reason;

            // Cập nhật nguồn xử lý vào click_metadata
            $meta = $cashback->click_metadata ?? [];
            $meta['approve_source'] = $source;
            $cashback->click_metadata = $meta;

            // Ghi mốc thời gian thu hồi đơn vào dòng thời gian trạng thái
            $cashback->pushTimeline('clawback', [
                'source' => $source,
                'reason' => $reason,
                'amount' => (float)$cashback->cashback_amount,
            ]);

            $cashback->save();

            // Khóa dòng bản ghi User F0 để trừ tiền ví
            $buyer = User::where('id', $cashback->user_id)->lockForUpdate()->firstOrFail();
            $oldBalance = $buyer->balance;
            $buyer->balance -= $cashback->cashback_amount;
            $buyer->total_cashback -= $cashback->cashback_amount;
            $buyer->save();

            $logMessage = "Thu hồi hoàn tiền {$platformName} đơn hàng {$cashback->order_id} (Đơn hàng bị hủy/hoàn trả trên sàn)";

            // Ghi nhận biến động số dư ví của F0
            BalanceLog::write(
                $buyer,
                $oldBalance,
                -$cashback->cashback_amount,
                $buyer->balance,
                'cashback',
                $logMessage
            );

            // Gửi thông báo đến tài khoản khách hàng F0 trên web
            Notification::create([
                'user_id' => $buyer->id,
                'title' => __('Thu hồi tiền hoàn!'),
                'content' => __("Tiền hoàn đơn hàng :platform :order_id của bạn bị thu hồi do đơn hàng đã bị hủy hoặc hoàn trả trên sàn.", [
                    'platform' => $platformName,
                    'order_id' => $cashback->order_id
                ])
            ]);

            // Ghi log hoạt động của admin hoặc hệ thống
            $adminId = auth()->id();
            $activityMsg = "";
            if ($source === 'manual') {
                $activityMsg = "Thu hồi đơn hoàn tiền ID {$cashback->id} cho user {$buyer->email}. Lý do: {$reason}";
            } elseif ($source === 'sync') {
                $activityMsg = "Tự động thu hồi đơn hoàn tiền ID {$cashback->id} (Mã đơn: {$cashback->order_id}) qua đồng bộ {$platformName} API. Lý do: {$reason}";
                $adminId = null;
            } elseif ($source === 'csv') {
                $activityMsg = "Tự động thu hồi đơn hoàn tiền ID {$cashback->id} (Mã đơn: {$cashback->order_id}) qua import đối soát CSV. Lý do: {$reason}";
            }
            ActivityLog::log($activityMsg, $adminId);

            // Tự động thu hồi hoa hồng MLM của tuyến trên (F1/F2) liên quan đến đơn này
            ReferralCommissionService::clawback($cashback, $source);

            return true;
        });
    }

    /**
     * Ánh xạ platform sang tiền tố khóa Setting (dùng cho *_cashback_rate, *_platform_name...).
     * Trả về đúng tên sàn cho các sàn đã hỗ trợ, mặc định 'shopee' cho giá trị không xác định.
     *
     * @param string|null $platform
     * @return string
     */
    public static function platformSuffix(?string $platform): string
    {
        return in_array($platform, ['shopee', 'tiktok', 'lazada'], true) ? $platform : 'shopee';
    }

    /**
     * Ánh xạ platform sang tên hiển thị nền tảng (đọc từ Setting, có mặc định thân thiện).
     *
     * @param string|null $platform
     * @return string
     */
    public static function platformName(?string $platform): string
    {
        return match ($platform) {
            'tiktok' => Setting::getVal('tiktok_platform_name', 'TikTok Shop'),
            'lazada' => Setting::getVal('lazada_platform_name', 'Lazada'),
            'shopee' => Setting::getVal('shopee_platform_name', 'Shopee'),
            default => ucfirst((string)$platform),
        };
    }
}
