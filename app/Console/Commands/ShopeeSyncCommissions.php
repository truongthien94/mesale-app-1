<?php

namespace App\Console\Commands;

use App\Models\ShopeeAccount;
use App\Services\ShopeeSyncService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ShopeeSyncCommissions extends Command
{
    /**
     * Tên và cú pháp của lệnh trên terminal.
     * Giải thích: Admin hoặc hệ thống Cron Job sẽ gọi lệnh qua "php artisan shopee:sync-commissions".
     *
     * @var string
     */
    protected $signature = 'shopee:sync-commissions';

    /**
     * Mô tả nhiệm vụ của lệnh.
     *
     * @var string
     */
    protected $description = 'Tự động đồng bộ báo cáo hoa hồng đơn hàng từ Shopee Affiliate qua API cho các tài khoản active';

    /**
     * Thực thi lệnh Artisan.
     * Giải thích: Lặp qua từng tài khoản Shopee Affiliate đang hoạt động để lấy dữ liệu báo cáo 30 ngày qua và đối soát đơn hàng.
     */
    public function handle()
    {
        $this->info('Bắt đầu đồng bộ hoa hồng Shopee Affiliate...');
        Log::info('Artisan Command: Bắt đầu đồng bộ hoa hồng Shopee Affiliate...');

        $accounts = ShopeeAccount::where('status', 'active')->get();

        if ($accounts->isEmpty()) {
            $this->warn('Không có tài khoản Shopee Affiliate nào đang ở trạng thái Hoạt động.');
            Log::info('Artisan Command: Không tìm thấy tài khoản Shopee Affiliate nào active để đồng bộ.');
            return Command::SUCCESS;
        }

        $syncService = new ShopeeSyncService();

        foreach ($accounts as $account) {
            $this->info("Đang đồng bộ tài khoản: {$account->name} (Username: {$account->username})");
            
            try {
                $result = $syncService->syncAccount($account, 30);
                
                if ($result['success']) {
                    $msg = "Đồng bộ thành công tài khoản '{$account->name}': Duyệt {$result['approved']} đơn, từ chối {$result['rejected']} đơn.";
                    $this->info($msg);
                    Log::info("Artisan Command: {$msg}");
                } else {
                    $msg = "Đồng bộ thất bại tài khoản '{$account->name}': " . ($account->error_message ?? 'Lỗi không xác định.');
                    $this->error($msg);
                    Log::error("Artisan Command: {$msg}");
                }
            } catch (\Exception $e) {
                $msg = "Đồng bộ tài khoản '{$account->name}' gặp lỗi hệ thống: " . $e->getMessage();
                $this->error($msg);
                Log::error("Artisan Command: {$msg}");
            }
        }

        $this->info('Đã hoàn tất đồng bộ hoa hồng Shopee.');
        Log::info('Artisan Command: Hoàn tất đồng bộ hoa hồng Shopee.');

        // Ghi nhận thời gian chạy thành công gần nhất để hiển thị ở cấu hình Admin Panel
        \App\Models\Setting::setVal('cron_last_run_shopee_sync', now()->toDateTimeString());

        return Command::SUCCESS;
    }
}
