<?php

namespace App\Console\Commands;

use App\Models\Setting;
use App\Services\SystemUpdateService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * SystemAutoUpdate - Artisan Command thực hiện tự động cập nhật hệ thống.
 * 
 * Command này được thiết lập để chạy định kỳ qua Laravel Task Scheduler,
 * tự động kiểm tra phiên bản mới từ máy chủ cập nhật và tải về, cài đặt nếu cấu hình được kích hoạt.
 */
class SystemAutoUpdate extends Command
{
    /**
     * Tên và cú pháp của lệnh trên terminal.
     *
     * @var string
     */
    protected $signature = 'system:auto-update {--force : Ép buộc mở khoá tiến trình cập nhật đang bị kẹt và chạy lại}';

    /**
     * Mô tả nhiệm vụ của lệnh.
     *
     * @var string
     */
    protected $description = 'Tự động kiểm tra phiên bản mới và thực hiện cập nhật nếu có bản cập nhật mới';

    /**
     * Thực thi lệnh Artisan.
     * 
     * @param SystemUpdateService $updateService
     * @return int
     */
    public function handle(SystemUpdateService $updateService)
    {
        // Ép buộc mở khoá tiến trình cập nhật đang bị kẹt nếu người dùng truyền tham số --force từ CLI
        if ($this->option('force')) {
            $this->info('Đang thực hiện ép buộc mở khoá tiến trình cập nhật cũ...');
            $updateService->forceUnlock();
        }

        $this->info('Bắt đầu kiểm tra tự động cập nhật phiên bản...');
        Log::info('Artisan Command: Bắt đầu kiểm tra tự động cập nhật phiên bản...');

        // 1. Kiểm tra xem quản trị viên có bật tùy chọn tự động cập nhật hay không
        $autoUpdate = Setting::getVal('auto_update', '1');
        if ($autoUpdate !== '1') {
            $this->warn('Tính năng tự động cập nhật đang bị TẮT.');
            Log::info('Artisan Command: Tự động cập nhật bị bỏ qua do tính năng đang bị TẮT.');
            
            // Vẫn lưu thời điểm kiểm tra gần nhất để hiển thị trạng thái
            Setting::setVal('cron_last_run_system_auto_update', now()->toDateTimeString());
            return Command::SUCCESS;
        }

        // 2. Lấy tên miền cấu hình để làm căn cứ xác thực bản quyền
        $domain = parse_url(config('app.url'), PHP_URL_HOST) ?: 'hoantienshopee.ddev.site';

        // 3. Gọi dịch vụ kiểm tra phiên bản mới
        $checkResult = $updateService->checkUpdate($domain);

        if (!$checkResult['success']) {
            $this->error('Kiểm tra phiên bản thất bại: ' . $checkResult['message']);
            Log::error('Artisan Command: Tự động cập nhật thất bại ở bước kiểm tra phiên bản. Chi tiết: ' . $checkResult['message']);
            
            Setting::setVal('cron_last_run_system_auto_update', now()->toDateTimeString());
            return Command::FAILURE;
        }

        // 4. Nếu hệ thống đã là phiên bản mới nhất, không cần làm gì thêm
        if ($checkResult['up_to_date']) {
            $this->info("Hệ thống đang chạy phiên bản mới nhất ({$checkResult['current_version']}). Không cần cập nhật.");
            Log::info("Artisan Command: Hệ thống đã chạy bản mới nhất ({$checkResult['current_version']}).");
            
            Setting::setVal('cron_last_run_system_auto_update', now()->toDateTimeString());
            return Command::SUCCESS;
        }

        // 5. Nếu phát hiện phiên bản mới, tiến hành cập nhật tự động
        $targetVersion = $checkResult['latest_version'];
        $downloadUrl   = $checkResult['download_url'];

        $this->info("Phát hiện phiên bản mới: {$targetVersion}. Tiến hành tải xuống và cập nhật...");
        Log::info("Artisan Command: Bắt đầu tự động cập nhật từ phiên bản {$checkResult['current_version']} lên {$targetVersion}...");

        $applyResult = $updateService->applyUpdate($downloadUrl, $targetVersion);

        if (!$applyResult['success']) {
            $this->error('Cập nhật phiên bản mới thất bại: ' . $applyResult['message']);
            Log::error('Artisan Command: Áp dụng bản cập nhật mới thất bại. Chi tiết: ' . $applyResult['message']);
            
            Setting::setVal('cron_last_run_system_auto_update', now()->toDateTimeString());
            return Command::FAILURE;
        }

        $this->info("Hệ thống đã tự động cập nhật thành công lên phiên bản: {$targetVersion}!");
        Log::info("Artisan Command: Hệ thống đã tự động cập nhật thành công lên phiên bản: {$targetVersion}.");

        // Ghi nhận thời gian chạy tự động cập nhật thành công gần nhất
        Setting::setVal('cron_last_run_system_auto_update', now()->toDateTimeString());

        return Command::SUCCESS;
    }
}
