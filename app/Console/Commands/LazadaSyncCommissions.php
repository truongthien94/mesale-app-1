<?php

namespace App\Console\Commands;

use App\Services\LazadaSyncService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Command Artisan tự động đồng bộ báo cáo hoa hồng đơn hàng từ Lazada qua Lazada Affiliate API.
 * Mọi function, logic block đều có comment tiếng Việt giải thích rõ ràng.
 */
class LazadaSyncCommissions extends Command
{
    /**
     * Tên và cú pháp của lệnh trên terminal.
     * Admin hoặc hệ thống Cron Job sẽ gọi lệnh qua "php artisan lazada:sync-commissions".
     *
     * @var string
     */
    protected $signature = 'lazada:sync-commissions';

    /**
     * Mô tả nhiệm vụ của lệnh.
     *
     * @var string
     */
    protected $description = 'Tự động đồng bộ báo cáo hoa hồng đơn hàng từ Lazada qua Lazada Affiliate API';

    /**
     * Thực thi lệnh Artisan.
     * Gọi LazadaSyncService để tiến hành đồng bộ các đơn hàng hoàn tiền từ Lazada.
     */
    public function handle()
    {
        $this->info('Bắt đầu đồng bộ hoa hồng Lazada Affiliate...');
        Log::info('Artisan Command: Bắt đầu đồng bộ hoa hồng Lazada Affiliate...');

        $syncService = new LazadaSyncService();

        try {
            $result = $syncService->syncOrders(30);

            if ($result['success']) {
                $msg = "Đồng bộ thành công Lazada: Duyệt {$result['approved']} đơn, từ chối {$result['rejected']} đơn.";
                $this->info($msg);
                Log::info("Artisan Command: {$msg}");
            } else {
                $msg = "Đồng bộ thất bại Lazada: " . ($result['error'] ?? 'Lỗi không xác định.');
                $this->error($msg);
                Log::error("Artisan Command: {$msg}");
            }
        } catch (\Exception $e) {
            $msg = "Đồng bộ Lazada gặp lỗi hệ thống: " . $e->getMessage();
            $this->error($msg);
            Log::error("Artisan Command: {$msg}");
        }

        $this->info('Đã hoàn tất đồng bộ hoa hồng Lazada.');
        Log::info('Artisan Command: Hoàn tất đồng bộ hoa hồng Lazada.');

        // Ghi nhận thời gian chạy thành công gần nhất để hiển thị ở cấu hình Admin Panel
        \App\Models\Setting::setVal('cron_last_run_lazada_sync', now()->toDateTimeString());

        return Command::SUCCESS;
    }
}
