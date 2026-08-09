<?php

// Định nghĩa các hằng số cURL SSL/TLS phiên bản cũ/mới nếu máy chủ chạy phiên bản libcurl không hỗ trợ.
// Điều này giúp ngăn chặn lỗi Fatal Error "Undefined constant" khi GuzzleHttp thực hiện cuộc gọi API bản quyền và đồng bộ hóa dữ liệu.
if (!defined('CURL_SSLVERSION_TLSv1_0')) {
    define('CURL_SSLVERSION_TLSv1_0', 4);
}
if (!defined('CURL_SSLVERSION_TLSv1_1')) {
    define('CURL_SSLVERSION_TLSv1_1', 5);
}
if (!defined('CURL_SSLVERSION_TLSv1_2')) {
    define('CURL_SSLVERSION_TLSv1_2', 6);
}
if (!defined('CURL_SSLVERSION_TLSv1_3')) {
    define('CURL_SSLVERSION_TLSv1_3', 7);
}

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        apiPrefix: 'api',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            // Nạp file route dành riêng cho Trình cài đặt (Installer)
            // Không áp dụng InstallerCheck middleware ở đây vì route installer cần truy cập tự do
            \Illuminate\Support\Facades\Route::middleware('web')
                ->group(base_path('routes/installer.php'));

            // Nạp file route dành riêng cho Admin Panel qua middleware web
            \Illuminate\Support\Facades\Route::middleware('web')
                ->group(base_path('routes/admin.php'));

            // Tuyến đường chuyển hướng link rút gọn được đăng ký ở cuối cùng của toàn bộ hệ thống.
            // Điều này giúp tránh tranh chấp quyền ưu tiên với route Admin, Installer hay các route tĩnh khác trong web.php.
            // Giới hạn mã rút gọn từ 3 đến 32 ký tự alphanumeric.
            \Illuminate\Support\Facades\Route::middleware('web')
                ->get('/{code}', [\App\Http\Controllers\HomeController::class, 'redirectShortLink'])
                ->name('shortlink.redirect')
                ->where('code', '[a-zA-Z0-9]{3,32}');
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->validateCsrfTokens(except: [
            'api/shopee/product',
            'install/*',
            'webhook/zalo-bot*',
            'webhook/telegram-bot*',
        ]);

        $middleware->web(append: [
            \App\Http\Middleware\InstallerCheck::class,
            \App\Http\Middleware\DetectAppSession::class,
            \App\Http\Middleware\SetLocale::class,
            \App\Http\Middleware\SetCurrency::class,
            \App\Http\Middleware\BlockDemoActions::class,
            \App\Http\Middleware\CheckMaintenanceMode::class,
            \App\Http\Middleware\CaptureUtmSource::class,
            \App\Http\Middleware\UpdateLastSeen::class,
            \App\Http\Middleware\ContentSecurityPolicy::class,
        ]);

        // Chặn toàn bộ API khi chạy chế độ Demo. Gắn ở đầu nhóm `api` để mọi endpoint
        // (Open API, API cho Bot và các endpoint bổ sung sau này) đều được bảo vệ tự động.
        $middleware->api(prepend: [
            \App\Http\Middleware\BlockDemoApi::class,
        ]);

        $middleware->alias([
            'admin' => \App\Http\Middleware\IsAdmin::class,
            'active' => \App\Http\Middleware\IsActive::class,
            'permission' => \App\Http\Middleware\CheckPermission::class,
            // Middleware dành riêng cho hệ thống Open API (App Mobile / Frontend)
            'api.enabled' => \App\Http\Middleware\ApiEndpointEnabled::class,
            'api.auth' => \App\Http\Middleware\ApiAuthenticate::class,
            // Công tắc riêng của nhóm API cho hệ thống ngoài (Bot), độc lập với Open API
            'bot.enabled' => \App\Http\Middleware\BotApiEnabled::class,
            // Ghi nhật ký mọi lần gọi API vào bảng api_logs để Admin giám sát
            'api.log' => \App\Http\Middleware\LogApiRequest::class,
        ]);

        // Cấu hình chuyển hướng mặc định: 
        // guests: Khi chưa đăng nhập truy cập trang yêu cầu đăng nhập -> chuyển hướng về /login
        // users: Khi đã đăng nhập truy cập trang chỉ dành cho khách (guest) -> chuyển hướng về trang chủ /
        $middleware->redirectTo(
            guests: '/login',
            users: '/'
        );
    })
    ->withSchedule(function (\Illuminate\Console\Scheduling\Schedule $schedule): void {
        // Hourly fallback; content changes may trigger an immediate sitemap refresh.
        $schedule->call(function (): void {
            $lock = \Illuminate\Support\Facades\Cache::lock('sitemap-generate-shared', 300);
            $acquired = false;

            try {
                $acquired = $lock->get();

                if ($acquired) {
                    \Illuminate\Support\Facades\Artisan::call('sitemap:generate');
                }
            } finally {
                if ($acquired) {
                    $lock->release();
                }
            }
        })
            ->hourly()
            ->name('sitemap-generate')
            ->withoutOverlapping();

        // Tự động đồng bộ báo cáo hoa hồng Shopee Affiliate mỗi 6 tiếng
        $schedule->command('shopee:sync-commissions')->everySixHours()->name('shopee-sync-commissions');

        // Tự động đồng bộ báo cáo hoa hồng TikTok Shop Affiliate mỗi 30 phút
        $schedule->command('tiktok:sync-commissions')->everyThirtyMinutes()->name('tiktok-sync-commissions');

        // Tự động đồng bộ báo cáo hoa hồng Lazada Affiliate mỗi 30 phút
        $schedule->command('lazada:sync-commissions')->everyThirtyMinutes()->name('lazada-sync-commissions');

        // Tự động đồng bộ danh sách mã giảm giá đa sàn mỗi 1 tiếng (hằng giờ)
        $schedule->command('coupons:sync')->hourly()->name('coupons-sync');

        // Tự động kiểm tra và cập nhật phiên bản mới hệ thống mỗi 30 phút
        $schedule->command('system:auto-update')->everyThirtyMinutes()->name('system-auto-update');

        // Xử lý hàng đợi gửi tin nhắn Telegram hàng loạt (Chạy mỗi 1 phút)
        $schedule->call(function () {
            app(\App\Http\Controllers\CronController::class)->processTelegramQueue(request());
        })->everyMinute()->name('process-telegram-queue');

        // Ghi lại thời điểm chạy gần nhất của Laravel Task Scheduler
        $schedule->call(function () {
            \App\Models\Setting::setVal('schedule_last_run', now()->toDateTimeString());
        })->everyMinute();

        // Kích hoạt chiến dịch Email Marketing đã đến giờ lên lịch & hoàn tất chiến dịch đã gửi xong (Chạy mỗi 1 phút)
        $schedule->call(function () {
            \App\Models\EmailCampaign::dispatchDueScheduled();
            \App\Models\EmailCampaign::finalizeCompletedSending();
        })->everyMinute()->name('dispatch-scheduled-campaigns');

        // Xử lý hàng đợi gửi email hàng loạt (Chạy mỗi 1 phút)
        $schedule->call(function () {
            app(\App\Http\Controllers\CronController::class)->processEmailQueue(request());
        })->everyMinute()->name('process-email-queue');

        // Tác vụ bảo trì hệ thống, dọn dẹp log và cache sản phẩm định kỳ (Chạy mỗi 12 tiếng vào lúc 0:00 và 12:00)
        $schedule->call(function () {
            app(\App\Http\Controllers\CronController::class)->systemMaintenance(request());
        })->twiceDaily(0, 12)->name('system-maintenance');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();

