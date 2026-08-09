<?php

namespace App\Http\Controllers;

use App\Models\EmailQueue;
use App\Models\TelegramQueue;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * CronController - Xử lý các tác vụ nền chạy qua URL cron job.
 * 
 * Các endpoint trong controller này được bảo vệ bằng cron_secret_key
 * (cấu hình trong bảng settings) để ngăn chặn truy cập trái phép.
 */
class CronController extends Controller
{
    /**
     * Xử lý hàng đợi gửi email hàng loạt.
     * Mỗi lần gọi sẽ lấy 1 batch (mặc định 10 email) từ hàng đợi và gửi tuần tự.
     * Nếu gửi thất bại sẽ retry tối đa 3 lần trước khi đánh dấu 'failed'.
     *
     * URL: /cron/process-email-queue?key={cron_secret_key}
     */
    public function processEmailQueue(Request $request)
    {
        // Xác thực khóa bảo mật cron job (Hỗ trợ lấy key từ cả query string và input parameters để tương thích khi chạy thủ công)
        if (!app()->runningInConsole()) {
            $secretKey = Setting::getVal('cron_secret_key');
            $requestKey = $request->query('key') ?? $request->input('key');
            if (empty($secretKey) || !hash_equals($secretKey, (string)$requestKey)) {
                abort(404);
            }
        }

        // Kiểm tra xem trạng thái Gửi Thư (SMTP) có đang được Bật hay không
        // Nếu bị Tắt, cron job sẽ dừng lại và báo trạng thái tắt để không cố gắng kết nối server SMTP vô ích
        if (Setting::getVal('smtp_status', '1') !== '1') {
            return response()->json([
                'success' => false,
                'message' => 'Hệ thống Gửi Thư (SMTP) đang bị TẮT.',
                'processed' => 0,
            ]);
        }

        // Số lượng email gửi mỗi batch (có thể cấu hình trong settings)
        $batchSize = (int) Setting::getVal('email_queue_batch_size', 10);
        $pendingEmails = EmailQueue::getPendingBatch($batchSize);

        if ($pendingEmails->isEmpty()) {
            // Ghi nhận thời gian chạy thành công gần nhất
            Setting::setVal('cron_last_run_email_queue', now()->toDateTimeString());
            return response()->json([
                'success' => true,
                'message' => 'Không có email nào trong hàng đợi.',
                'processed' => 0,
            ]);
        }

        $sent = 0;
        $failed = 0;

        foreach ($pendingEmails as $emailJob) {
            try {
                // Gửi email qua view 'emails.dynamic' (sử dụng cấu hình SMTP đã thiết lập trong AppServiceProvider)
                Mail::send('emails.dynamic', ['content' => $emailJob->body], function ($message) use ($emailJob) {
                    $message->to($emailJob->to_email, $emailJob->to_name);
                    $message->subject($emailJob->subject);
                });

                // Đánh dấu đã gửi thành công
                $emailJob->markAsSent();
                $sent++;
            } catch (\Exception $e) {
                // Ghi log lỗi và đánh dấu thất bại (tự động retry nếu chưa đủ 3 lần)
                Log::error("Cron Email Queue - Gửi thất bại tới [{$emailJob->to_email}]: " . $e->getMessage());
                $emailJob->markAsFailed($e->getMessage());
                $failed++;
            }

            // Nghỉ 1 giây giữa mỗi email để không bị rate limit từ SMTP server
            sleep(1);
        }

        // Ghi nhận thời gian chạy thành công gần nhất
        Setting::setVal('cron_last_run_email_queue', now()->toDateTimeString());

        return response()->json([
            'success' => true,
            'message' => "Đã xử lý {$pendingEmails->count()} email: {$sent} thành công, {$failed} thất bại.",
            'processed' => $pendingEmails->count(),
            'sent' => $sent,
            'failed' => $failed,
        ]);
    }

    /**
     * Xử lý hàng đợi gửi tin nhắn Telegram hàng loạt.
     * Mỗi lần gọi sẽ lấy 1 batch (mặc định 10 tin nhắn) từ hàng đợi và gửi tuần tự.
     * Nếu gửi thất bại sẽ retry tối đa 3 lần trước khi đánh dấu 'failed'.
     *
     * URL: /cron/process-telegram-queue?key={cron_secret_key}
     */
    public function processTelegramQueue(Request $request)
    {
        // Xác thực khóa bảo mật cron job (Hỗ trợ lấy key từ cả query string và input parameters để tương thích khi chạy thủ công)
        if (!app()->runningInConsole()) {
            $secretKey = Setting::getVal('cron_secret_key');
            $requestKey = $request->query('key') ?? $request->input('key');
            if (empty($secretKey) || !hash_equals($secretKey, (string)$requestKey)) {
                abort(404);
            }
        }

        // Kiểm tra xem trạng thái gửi Telegram Bot có đang được Bật hay không
        // Nếu bị Tắt, cron job sẽ dừng lại và báo trạng thái tắt để không gọi API Telegram vô ích
        if (Setting::getVal('telegram_status', '1') !== '1') {
            return response()->json([
                'success' => false,
                'message' => 'Hệ thống thông báo Telegram Bot đang bị TẮT.',
                'processed' => 0,
            ]);
        }

        // Số lượng tin nhắn gửi mỗi batch (có thể cấu hình trong settings)
        $batchSize = (int) Setting::getVal('telegram_queue_batch_size', 10);
        $pendingTelegrams = TelegramQueue::getPendingBatch($batchSize);

        if ($pendingTelegrams->isEmpty()) {
            // Ghi nhận thời gian chạy thành công gần nhất
            Setting::setVal('cron_last_run_telegram_queue', now()->toDateTimeString());
            return response()->json([
                'success' => true,
                'message' => 'Không có tin nhắn Telegram nào trong hàng đợi.',
                'processed' => 0,
            ]);
        }

        $sent = 0;
        $failed = 0;

        foreach ($pendingTelegrams as $telegramJob) {
            try {
                // Cập nhật trạng thái đang gửi
                $telegramJob->update(['status' => 'sending']);

                // Thực hiện gửi tin nhắn qua API Telegram thực tế
                $result = Setting::sendTelegram($telegramJob->message, $telegramJob->chat_id);

                if ($result) {
                    $telegramJob->markAsSent();
                    $sent++;
                } else {
                    $telegramJob->markAsFailed("Lỗi phản hồi từ Telegram API (kiểm tra Token/Chat ID).");
                    $failed++;
                }
            } catch (\Exception $e) {
                // Ghi log lỗi và đánh dấu thất bại (tự động retry nếu chưa đủ 3 lần)
                Log::error("Cron Telegram Queue - Gửi thất bại tới [{$telegramJob->chat_id}]: " . $e->getMessage());
                $telegramJob->markAsFailed($e->getMessage());
                $failed++;
            }

            // Nghỉ 1 giây giữa mỗi tin nhắn để không bị rate limit từ Telegram API
            sleep(1);
        }

        // Ghi nhận thời gian chạy thành công gần nhất
        Setting::setVal('cron_last_run_telegram_queue', now()->toDateTimeString());

        return response()->json([
            'success' => true,
            'message' => "Đã xử lý {$pendingTelegrams->count()} tin nhắn Telegram: {$sent} thành công, {$failed} thất bại.",
            'processed' => $pendingTelegrams->count(),
            'sent' => $sent,
            'failed' => $failed,
        ]);
    }

    /**
     * Tác vụ cron định kỳ bảo trì hệ thống (Common System Maintenance).
     * Dùng để dọn dẹp log lỗi, dọn dẹp cache sản phẩm Shopee hết hạn, xóa lịch sử hoạt động quá cũ, và thực hiện các công việc định kỳ khác trong tương lai.
     *
     * URL: /cron/system-maintenance?key={cron_secret_key}
     */
    public function systemMaintenance(Request $request)
    {
        // 1. Xác thực khóa bảo mật cron job (Hỗ trợ lấy key từ cả query string và input parameters để tương thích khi chạy thủ công)
        if (!app()->runningInConsole()) {
            $secretKey = Setting::getVal('cron_secret_key');
            $requestKey = $request->query('key') ?? $request->input('key');
            if (empty($secretKey) || !hash_equals($secretKey, (string)$requestKey)) {
                abort(404);
            }
        }

        $actions = [];

        try {
            // 2. Tác vụ chạy mỗi lần gọi cron (chu kỳ 1 - 5 phút): Kiểm tra và giới hạn kích thước tệp laravel.log (không quá 10MB)
            \App\Http\Controllers\Admin\LogController::limitLogFileSize();
            $actions[] = 'Đã kiểm tra và tự động giới hạn kích thước tệp laravel.log (Chạy mỗi 1-5 phút)';

            // 3. Kiểm tra xem đã đến thời điểm chạy các tác vụ dọn dẹp DB định kỳ (24 giờ một lần) hay chưa
            $lastDailyRun = Setting::getVal('cron_last_run_maintenance_daily_tasks');
            $shouldRunDaily = true;
            
            if (!empty($lastDailyRun)) {
                // Kiểm tra xem đã qua 24 giờ kể từ lần chạy trước chưa
                $lastRunTime = \Carbon\Carbon::parse($lastDailyRun);
                if (now()->diffInHours($lastRunTime) < 24) {
                    $shouldRunDaily = false;
                }
            }

            if ($shouldRunDaily) {
                // 3.1. Tự động dọn dẹp lịch sử hoạt động cũ để giải phóng dung lượng DB (mặc định giữ lại 30 ngày)
                $keepDays = (int) Setting::getVal('keep_activity_logs_days', 30);
                $keepDays = $keepDays > 0 ? $keepDays : 30;
                $cutoffDate = now()->subDays($keepDays);
                
                $deletedLogs = \App\Models\ActivityLog::where('created_at', '<', $cutoffDate)->delete();
                $actions[] = "Đã dọn dẹp {$deletedLogs} bản ghi nhật ký hoạt động cũ hơn {$keepDays} ngày (Chạy định kỳ 24 giờ)";

                // 3.2. Tự động dọn dẹp cache sản phẩm cũ và sản phẩm ước tính trong bảng products
                $estimatedHours = (int) Setting::getVal('cache_clean_estimated_hours', 24);
                $normalDays = (int) Setting::getVal('cache_clean_normal_days', 30);
                $estimatedHours = $estimatedHours > 0 ? $estimatedHours : 24;
                $normalDays = $normalDays > 0 ? $normalDays : 30;

                // Xóa các sản phẩm cào ước tính (giá bán bằng 0) đã hết hạn
                $estimatedCutoff = now()->subHours($estimatedHours);
                $deletedEstimated = \App\Models\Product::where('price', '<=', 0)
                    ->where('updated_at', '<', $estimatedCutoff)
                    ->delete();

                // Xóa các sản phẩm cache thông thường quá hạn cập nhật
                $normalCutoff = now()->subDays($normalDays);
                $deletedNormal = \App\Models\Product::where('price', '>', 0)
                    ->where('updated_at', '<', $normalCutoff)
                    ->delete();
                    
                $actions[] = "Đã dọn dẹp cache bảng products: xóa {$deletedEstimated} sản phẩm cào ước tính (> {$estimatedHours} giờ) và {$deletedNormal} sản phẩm cache thông thường (> {$normalDays} ngày) (Chạy định kỳ 24 giờ)";

                // Ghi nhận thời điểm chạy thành công các tác vụ 24h
                Setting::setVal('cron_last_run_maintenance_daily_tasks', now()->toDateTimeString());
            } else {
                $actions[] = 'Bỏ qua các tác vụ dọn dẹp DB định kỳ 24 giờ (Lần chạy cuối: ' . date('H:i:s d/m/Y', strtotime($lastDailyRun)) . ')';
            }

            // Ghi nhận thời gian chạy của endpoint cron
            Setting::setVal('cron_last_run_system_maintenance', now()->toDateTimeString());

            return response()->json([
                'success' => true,
                'message' => 'Hệ thống đã thực hiện các tác vụ bảo trì định kỳ thành công.',
                'actions' => $actions,
            ]);

        } catch (\Exception $e) {
            Log::error("Cron System Maintenance - Lỗi khi bảo trì hệ thống: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi bảo trì hệ thống: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Xác thực khóa bảo mật cron.
     */
    protected function validateCronKey(Request $request): void
    {
        if (!app()->runningInConsole()) {
            $secretKey = Setting::getVal('cron_secret_key');
            $requestKey = $request->query('key') ?? $request->input('key');
            if (empty($secretKey) || !hash_equals($secretKey, (string)$requestKey)) {
                abort(404);
            }
        }
    }

    /**
     * Đồng bộ báo cáo hoa hồng Shopee Affiliate.
     * URL: /cron/sync-shopee-commissions?key={cron_secret_key}
     */
    public function syncShopeeCommissions(Request $request)
    {
        $this->validateCronKey($request);

        try {
            \Illuminate\Support\Facades\Artisan::call('shopee:sync-commissions');
            Setting::setVal('cron_last_run_shopee_sync', now()->toDateTimeString());

            return response()->json([
                'success' => true,
                'message' => 'Đồng bộ hoa hồng Shopee Affiliate thành công.'
            ]);
        } catch (\Exception $e) {
            Log::error("Cron Sync Shopee - Lỗi: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi đồng bộ hoa hồng Shopee: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Đồng bộ báo cáo hoa hồng TikTok Shop Affiliate.
     * URL: /cron/sync-tiktok-commissions?key={cron_secret_key}
     */
    public function syncTiktokCommissions(Request $request)
    {
        $this->validateCronKey($request);

        try {
            \Illuminate\Support\Facades\Artisan::call('tiktok:sync-commissions');
            Setting::setVal('cron_last_run_tiktok_sync', now()->toDateTimeString());

            return response()->json([
                'success' => true,
                'message' => 'Đồng bộ hoa hồng TikTok Shop Affiliate thành công.'
            ]);
        } catch (\Exception $e) {
            Log::error("Cron Sync TikTok - Lỗi: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi đồng bộ hoa hồng TikTok: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Đồng bộ báo cáo hoa hồng Lazada Affiliate.
     * URL: /cron/sync-lazada-commissions?key={cron_secret_key}
     */
    public function syncLazadaCommissions(Request $request)
    {
        $this->validateCronKey($request);

        try {
            \Illuminate\Support\Facades\Artisan::call('lazada:sync-commissions');
            Setting::setVal('cron_last_run_lazada_sync', now()->toDateTimeString());

            return response()->json([
                'success' => true,
                'message' => 'Đồng bộ hoa hồng Lazada Affiliate thành công.'
            ]);
        } catch (\Exception $e) {
            Log::error("Cron Sync Lazada - Lỗi: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi đồng bộ hoa hồng Lazada: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Đồng bộ danh sách mã giảm giá đa sàn.
     * URL: /cron/sync-coupons?key={cron_secret_key}
     */
    public function syncCoupons(Request $request)
    {
        $this->validateCronKey($request);

        try {
            \Illuminate\Support\Facades\Artisan::call('coupons:sync', ['--force' => true]);
            Setting::setVal('cron_last_run_coupons_sync', now()->toDateTimeString());

            return response()->json([
                'success' => true,
                'message' => 'Đồng bộ danh sách mã giảm giá đa sàn thành công.'
            ]);
        } catch (\Exception $e) {
            Log::error("Cron Sync Coupons - Lỗi: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi đồng bộ mã giảm giá: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Tự động kiểm tra và cập nhật phiên bản mới hệ thống.
     * URL: /cron/system-auto-update?key={cron_secret_key}
     */
    public function systemAutoUpdate(Request $request)
    {
        $this->validateCronKey($request);

        try {
            \Illuminate\Support\Facades\Artisan::call('system:auto-update');
            Setting::setVal('cron_last_run_system_auto_update', now()->toDateTimeString());

            return response()->json([
                'success' => true,
                'message' => 'Tự động kiểm tra và cập nhật hệ thống thành công.'
            ]);
        } catch (\Exception $e) {
            Log::error("Cron Auto Update - Lỗi: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi cập nhật hệ thống: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Tạo sitemap SEO cho website.
     * URL: /cron/generate-sitemap?key={cron_secret_key}
     */
    public function generateSitemap(Request $request)
    {
        $this->validateCronKey($request);

        try {
            \Illuminate\Support\Facades\Artisan::call('sitemap:generate');

            return response()->json([
                'success' => true,
                'message' => 'Tạo sitemap XML thành công.'
            ]);
        } catch (\Exception $e) {
            Log::error("Cron Sitemap Generate - Lỗi: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi tạo sitemap: ' . $e->getMessage()
            ], 500);
        }
    }
}
