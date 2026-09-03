<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\UserDashboardController;
use App\Http\Controllers\DailyCheckinController;
use App\Http\Controllers\ReferralController;
use App\Http\Controllers\WithdrawalController;
use App\Http\Controllers\PaymentAccountController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RankingController;
use App\Http\Controllers\StoreCompliancePageController;
use App\Http\Controllers\WellKnownController;

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ForgotPasswordController;

/*
|--------------------------------------------------------------------------
| Web Routes - Các tuyến đường giao diện của hệ thống
|--------------------------------------------------------------------------
*/

// ==========================================
// 1. PUBLIC ROUTES (Giao diện công khai)
// ==========================================
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/change-language/{locale}', [\App\Http\Controllers\LanguageController::class, 'changeLanguage'])->name('change-language');
Route::get('/change-currency/{code}', [\App\Http\Controllers\CurrencyController::class, 'changeCurrency'])->name('change-currency');
Route::match(['get', 'post'], '/api/shopee/product', [HomeController::class, 'getProductInfo'])->name('shopee.product')->middleware('throttle:10,1');
Route::get('/coupons', [\App\Http\Controllers\CouponController::class, 'index'])->name('coupons.index');
Route::get('/ranking', [RankingController::class, 'index'])->name('ranking.index');
Route::get('/privacy', [StoreCompliancePageController::class, 'privacy'])->name('legal.privacy');
Route::get('/terms', [StoreCompliancePageController::class, 'terms'])->name('legal.terms');
Route::get('/support', [StoreCompliancePageController::class, 'support'])->name('support');
Route::get('/account-deletion', [StoreCompliancePageController::class, 'accountDeletion'])->name('account-deletion');
Route::get('/account/delete', [StoreCompliancePageController::class, 'accountDeletion'])->name('account-deletion.legacy');
Route::get('/.well-known/apple-app-site-association', [WellKnownController::class, 'appleAppSiteAssociation'])
    ->name('well-known.apple');
Route::get('/.well-known/assetlinks.json', [WellKnownController::class, 'assetLinks'])
    ->name('well-known.android');

// ==========================================
// Tuyến đường sinh file manifest.json động để hỗ trợ PWA (Thêm vào màn hình chính)
// ==========================================
Route::get('/manifest.json', function () {
    $siteName = \App\Models\Setting::getVal('site_name', 'Hoàn Tiền Shopee');
    $favicon = \App\Models\Setting::getVal('site_favicon', '/favicon.ico');
    $themeColor = \App\Models\Setting::getVal('theme_color', '#ee4d2d');

    // Chuyển đường dẫn favicon thành URL tuyệt đối khớp với tên miền và giao thức (http/https) hiện tại
    if (filter_var($favicon, FILTER_VALIDATE_URL)) {
        $faviconUrl = asset(parse_url($favicon, PHP_URL_PATH));
    } else {
        $faviconUrl = asset($favicon);
    }

    $faviconPath = parse_url($faviconUrl, PHP_URL_PATH);
    $faviconLocalPath = $faviconPath ? public_path(ltrim($faviconPath, '/')) : null;
    if ($faviconLocalPath && is_file($faviconLocalPath)) {
        $faviconUrl .= '?v=' . filemtime($faviconLocalPath);
    }

    return response()->json([
        'name' => $siteName,
        'short_name' => $siteName,
        // Mô tả chi tiết ứng dụng nhằm gia tăng độ nhận diện và tối ưu hóa điểm SEO Lighthouse
        'description' => 'Hệ thống hoàn tiền mua sắm tự động Shopee, TikTok Shop. Nhận hoa hồng affiliate và trích thưởng giới thiệu 2 tầng tiện lợi.',
        // Khai báo danh mục ứng dụng để Google Play / App Store / Trình duyệt phân loại chính xác
        'categories' => ['shopping', 'finance'],
        'start_url' => '/',
        // Giới hạn phạm vi hoạt động của PWA trong toàn bộ thư mục gốc
        'scope' => '/',
        'display' => 'standalone',
        // Khóa định hướng màn hình dọc (portrait) để giao diện PWA chạy mượt mà và trực quan nhất trên thiết bị di động
        'orientation' => 'portrait',
        'background_color' => '#ffffff',
        'theme_color' => $themeColor,
        'icons' => [
            [
                'src' => $faviconUrl,
                'sizes' => '512x512',
                'type' => 'image/png',
                'purpose' => 'any maskable'
            ],
        ],
        // Cấu hình Share Target để thiết bị di động nhận diện ứng dụng có thể nhận dữ liệu chia sẻ (dán link tự động)
        'share_target' => [
            'action' => '/',
            'method' => 'GET',
            'enctype' => 'application/x-www-form-urlencoded',
            'params' => [
                'title' => 'title',
                'text' => 'text',
                'url' => 'url'
            ]
        ],
        // Phím tắt nhanh (Shortcuts) hiển thị khi người dùng nhấn giữ biểu tượng ứng dụng ở màn hình điện thoại
        'shortcuts' => [
            [
                'name' => 'Lấy Link Hoàn Tiền',
                'short_name' => 'Lấy Link',
                'description' => 'Dán link Shopee/TikTok để nhận hoa hồng hoàn tiền',
                'url' => '/',
                'icons' => [
                    [
                        'src' => $faviconUrl,
                        'sizes' => '512x512',
                        'type' => 'image/png'
                    ]
                ]
            ],
            [
                'name' => 'Rút Tiền Số Dư',
                'short_name' => 'Rút Tiền',
                'description' => 'Yêu cầu rút tiền tích lũy về tài khoản ngân hàng',
                'url' => '/dashboard/withdraw',
                'icons' => [
                    [
                        'src' => $faviconUrl,
                        'sizes' => '512x512',
                        'type' => 'image/png'
                    ]
                ]
            ],
            [
                'name' => 'Lịch Sử Click',
                'short_name' => 'Lịch Sử',
                'description' => 'Xem danh sách link đã chuyển đổi và click của bạn',
                'url' => '/dashboard/clicks',
                'icons' => [
                    [
                        'src' => $faviconUrl,
                        'sizes' => '512x512',
                        'type' => 'image/png'
                    ]
                ]
            ]
        ]
    ], 200, [
        'Content-Type' => 'application/json',
        'Cache-Control' => 'no-cache, no-store, must-revalidate'
    ]);
})->name('manifest.json');

// ==========================================
// BLOG TIN TỨC ROUTES (Frontend)
// ==========================================
Route::prefix('blog')->name('blog.')->group(function () {
    Route::get('/', [\App\Http\Controllers\BlogController::class, 'index'])->name('index');
    Route::get('/search', [\App\Http\Controllers\BlogController::class, 'search'])->name('search');
    Route::get('/category/{slug}', [\App\Http\Controllers\BlogController::class, 'category'])->name('category');
    Route::get('/tag/{slug}', [\App\Http\Controllers\BlogController::class, 'tag'])->name('tag');
    Route::get('/feed', [\App\Http\Controllers\BlogController::class, 'feed'])->name('feed');
    Route::get('/{slug}', [\App\Http\Controllers\BlogController::class, 'show'])->name('show');
    Route::post('/{post}/like', [\App\Http\Controllers\BlogController::class, 'like'])->name('like');
    Route::post('/{post}/share', [\App\Http\Controllers\BlogController::class, 'share'])
        ->middleware('throttle:30,1')
        ->name('share');
    Route::post('/{post}/comment', [\App\Http\Controllers\BlogController::class, 'comment'])->name('comment');
});
// Xác thực tài khoản (Authentication)
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::get('/login/verification', [LoginController::class, 'showVerificationForm'])->name('login.verification');
Route::post('/login/verification', [LoginController::class, 'verify']);
Route::post('/login/verification/resend', [LoginController::class, 'resendOTP'])->name('login.verification.resend');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// Đăng nhập bằng Google
Route::get('/auth/google', [LoginController::class, 'redirectToGoogle'])->name('auth.google');
Route::get('/auth/google/callback', [LoginController::class, 'handleGoogleCallback'])->name('auth.google.callback');

Route::get('/register', [RegisterController::class, 'showRegisterForm'])->name('register');
Route::post('/register', [RegisterController::class, 'register']);
Route::get('/register/verify', [RegisterController::class, 'showVerificationForm'])->name('register.verification');
Route::post('/register/verify', [RegisterController::class, 'verify'])->name('register.verify');
Route::post('/register/verify/resend', [RegisterController::class, 'resendVerificationOTP'])->name('register.verification.resend');

// Quên mật khẩu & Đặt lại mật khẩu
Route::get('/forgot-password', [ForgotPasswordController::class, 'showLinkRequestForm'])->name('password.request');
Route::post('/forgot-password', [ForgotPasswordController::class, 'sendResetLinkEmail'])->name('password.email');
Route::get('/reset-password/{token}', [ForgotPasswordController::class, 'showResetForm'])->name('password.reset');
Route::post('/reset-password', [ForgotPasswordController::class, 'reset'])->name('password.update')->middleware('throttle:5,1');


// ==========================================
// 2. MEMBER DASHBOARD ROUTES (Giao diện thành viên)
// ==========================================
Route::middleware(['auth', 'active'])->prefix('dashboard')->group(function () {
    
    // Quay lại tài khoản quản trị Admin gốc
    Route::post('/return-to-admin', [\App\Http\Controllers\Admin\UserController::class, 'returnToAdmin'])->name('admin.users.return_to_admin');

    // Trang tổng quan Dashboard
    Route::get('/', [UserDashboardController::class, 'index'])->name('dashboard');
    
    // API Lấy dữ liệu biểu đồ tiết kiệm theo ngày
    Route::get('/savings-chart', [UserDashboardController::class, 'getSavingsChartData'])->name('dashboard.savings_chart');
    
    // API Lấy danh sách link hoàn tiền đã tạo với phân trang Ajax (Tải thêm link)
    Route::get('/created-links', [UserDashboardController::class, 'getUserCreatedLinks'])->name('dashboard.created_links');
    
    // Lịch sử hoàn tiền
    Route::get('/cashback', [UserDashboardController::class, 'cashbackHistory'])->name('cashback.history');
    
    // Nhật ký hoạt động
    Route::get('/logs', [UserDashboardController::class, 'activityLogs'])->name('activity.logs');
    
    // Biến động số dư tài khoản
    Route::get('/balance-logs', [UserDashboardController::class, 'balanceLogs'])->name('balance.logs');
    
    // Quản lý thông báo
    Route::get('/notifications', [UserDashboardController::class, 'notifications'])->name('notifications');
    
    // Hướng dẫn tích hợp Phím tắt iPhone (iOS Shortcuts)
    Route::get('/shortcuts', [UserDashboardController::class, 'shortcuts'])->name('shortcuts');
    Route::post('/notifications/read-all', [UserDashboardController::class, 'markAllAsRead'])->name('notifications.read_all');
    Route::post('/notifications/{id}/read', [UserDashboardController::class, 'markAsRead'])->name('notifications.read');
    
    // Điểm danh hằng ngày (Daily Check-in)
    Route::get('/checkin', [DailyCheckinController::class, 'index'])->name('checkin');
    // Giới hạn 5 request/phút để chống spam DoS vào endpoint điểm danh (mỗi request tạo transaction + lock)
    Route::post('/checkin', [DailyCheckinController::class, 'checkin'])->name('checkin.post')->middleware('throttle:5,1');
    
    // Tiếp thị liên kết (Affiliate Referral)
    Route::get('/referrals', [ReferralController::class, 'index'])->name('referrals');
    
    // Rút tiền (Withdrawal)
    Route::get('/withdraw', [WithdrawalController::class, 'index'])->name('withdraw');
    Route::post('/withdraw', [WithdrawalController::class, 'store'])->name('withdraw.post');
    Route::post('/withdraw/send-otp', [WithdrawalController::class, 'sendOtp'])->name('withdraw.send_otp');

    // Sổ tài khoản nhận tiền đã lưu (chọn nhanh khi rút tiền)
    Route::post('/payment-accounts', [PaymentAccountController::class, 'store'])->name('payment_accounts.store')->middleware('throttle:20,1');
    Route::post('/payment-accounts/{id}/default', [PaymentAccountController::class, 'setDefault'])->name('payment_accounts.default')->middleware('throttle:30,1');
    Route::delete('/payment-accounts/{id}', [PaymentAccountController::class, 'destroy'])->name('payment_accounts.destroy')->middleware('throttle:30,1');

    // Đổi quà tặng (Gifts Exchange)
    Route::get('/gifts', [\App\Http\Controllers\GiftController::class, 'index'])->name('gifts.index');
    Route::post('/gifts', [\App\Http\Controllers\GiftController::class, 'store'])->name('gifts.store');
    // AJAX endpoint riêng để tìm kiếm / phân trang lịch sử đổi quà mà không reload trang
    Route::get('/gifts/redemptions', [\App\Http\Controllers\GiftController::class, 'redemptions'])->name('gifts.redemptions');

    // Nhiệm vụ nhận thưởng (Tasks)
    Route::get('/tasks', [\App\Http\Controllers\TaskController::class, 'index'])->name('tasks.index');
    Route::post('/tasks/{task}/claim', [\App\Http\Controllers\TaskController::class, 'claim'])->name('tasks.claim')->middleware('throttle:20,1');
    // Thành viên gửi yêu cầu xác nhận đã hoàn thành nhiệm vụ thủ công (chờ Admin duyệt)
    Route::post('/tasks/{task}/submit', [\App\Http\Controllers\TaskController::class, 'submit'])->name('tasks.submit')->middleware('throttle:10,1');
    Route::get('/tasks/{task}/sync', [\App\Http\Controllers\TaskController::class, 'syncProgress'])->name('tasks.sync')->middleware('throttle:30,1');

    // Nhập Giftcode nhận thưởng (Gift Code Redeem)
    Route::get('/giftcode', [\App\Http\Controllers\GiftCodeController::class, 'index'])->name('giftcode.index');
    // Giới hạn 10 request/phút để chống brute-force dò mã Giftcode
    Route::post('/giftcode/redeem', [\App\Http\Controllers\GiftCodeController::class, 'redeem'])->name('giftcode.redeem')->middleware('throttle:10,1');
    
    // Thông tin tài khoản & Đổi mật khẩu
    Route::get('/profile', [ProfileController::class, 'index'])->name('profile');
    Route::post('/profile/update', [ProfileController::class, 'update'])->name('profile.update')->middleware('throttle:5,1');
    Route::post('/profile/password', [ProfileController::class, 'changePassword'])->name('profile.password')->middleware('throttle:5,1');
    Route::post('/profile/regenerate-api-key', [ProfileController::class, 'regenerateApiKey'])->name('profile.regenerate_api_key')->middleware('throttle:5,1');
    
    // Bảo mật 2FA (Google Authenticator)
    Route::get('/profile/2fa/setup', [ProfileController::class, 'setup2FA'])->name('profile.2fa.setup');
    Route::post('/profile/2fa/enable', [ProfileController::class, 'enable2FA'])->name('profile.2fa.enable')->middleware('throttle:5,1');
    Route::post('/profile/2fa/disable', [ProfileController::class, 'disable2FA'])->name('profile.2fa.disable')->middleware('throttle:5,1');
    
    // Bảo mật OTP qua Email
    Route::post('/profile/email-otp/send', [ProfileController::class, 'sendEmailOTP'])->name('profile.email_otp.send')->middleware('throttle:5,1');
    Route::post('/profile/email-otp/enable', [ProfileController::class, 'enableEmailOTP'])->name('profile.email_otp.enable')->middleware('throttle:5,1');
    Route::post('/profile/email-otp/disable', [ProfileController::class, 'disableEmailOTP'])->name('profile.email_otp.disable')->middleware('throttle:5,1');
    
    // Đăng xuất khỏi thiết bị khác
    Route::post('/profile/logout-devices', [LoginController::class, 'logoutAllDevices'])->name('profile.logout_devices');
    Route::post('/profile/logout-session/{id}', [ProfileController::class, 'logoutSession'])->name('profile.logout_session');

    // Hủy liên kết Bot
    Route::post('/profile/unlink-bot', [ProfileController::class, 'unlinkBot'])->name('profile.unlink.bot');

    // Tự xóa tài khoản (khi quản trị viên bật tùy chọn cho phép)
    Route::post('/profile/delete-account', [ProfileController::class, 'deleteAccount'])->name('profile.delete')->middleware('throttle:5,1');

    // Sản phẩm đã lưu (Saved Products / Wishlist)
    Route::get('/saved-products', [\App\Http\Controllers\SavedProductController::class, 'index'])->name('saved-products');
    Route::post('/saved-products', [\App\Http\Controllers\SavedProductController::class, 'store'])->name('saved-products.store');
    Route::delete('/saved-products/{id}', [\App\Http\Controllers\SavedProductController::class, 'destroy'])->name('saved-products.destroy');
});

// ==========================================
// 3. STATIC PAGE ROUTES (Trang nội dung tĩnh: Điều khoản, Chính sách,...)
// ==========================================
Route::get('/page/{slug}', [\App\Http\Controllers\PageController::class, 'show'])->name('page.show');

// ==========================================
// 4. BOT ROUTES (Hướng dẫn sử dụng + Webhook)
// ==========================================
Route::get('/bot-guide', [HomeController::class, 'botGuide'])->name('bot.guide');
// {secret?} optional: khi chưa cấu hình webhook_secret thì bot vẫn hoạt động,
// sau khi admin nhấn "Đặt Webhook" sẽ dùng URL /webhook/zalo-bot/{32-char-secret}
Route::post('/webhook/zalo-bot/{secret?}', [\App\Http\Controllers\ZaloBotWebhookController::class, 'handle'])->name('webhook.zalo_bot')->middleware('throttle:60,1');
Route::post('/webhook/telegram-bot/{secret?}', [\App\Http\Controllers\TelegramBotWebhookController::class, 'handle'])->name('webhook.telegram_bot')->middleware('throttle:60,1');

// ==========================================
// 5. CRON WEBHOOK ROUTES (Tác vụ nền chạy qua URL cho Cron bên ngoài)
// ==========================================
Route::prefix('cron')->group(function () {
    // Gọi tác vụ gửi email hàng đợi tự động
    Route::get('/process-email-queue', [\App\Http\Controllers\CronController::class, 'processEmailQueue'])->name('cron.process_email_queue');
    // Gọi tác vụ gửi tin nhắn Telegram hàng đợi tự động
    Route::get('/process-telegram-queue', [\App\Http\Controllers\CronController::class, 'processTelegramQueue'])->name('cron.process_telegram_queue');
    // Gọi tác vụ bảo trì và dọn dẹp hệ thống định kỳ
    Route::get('/system-maintenance', [\App\Http\Controllers\CronController::class, 'systemMaintenance'])->name('cron.system_maintenance');
    // Đồng bộ báo cáo hoa hồng Shopee Affiliate
    Route::get('/sync-shopee-commissions', [\App\Http\Controllers\CronController::class, 'syncShopeeCommissions'])->name('cron.sync_shopee_commissions');
    // Đồng bộ báo cáo hoa hồng TikTok Shop Affiliate
    Route::get('/sync-tiktok-commissions', [\App\Http\Controllers\CronController::class, 'syncTiktokCommissions'])->name('cron.sync_tiktok_commissions');
    // Đồng bộ báo cáo hoa hồng Lazada Affiliate
    Route::get('/sync-lazada-commissions', [\App\Http\Controllers\CronController::class, 'syncLazadaCommissions'])->name('cron.sync_lazada_commissions');
    // Đồng bộ danh sách mã giảm giá đa sàn
    Route::get('/sync-coupons', [\App\Http\Controllers\CronController::class, 'syncCoupons'])->name('cron.sync_coupons');
    // Tự động kiểm tra và cập nhật hệ thống
    Route::get('/system-auto-update', [\App\Http\Controllers\CronController::class, 'systemAutoUpdate'])->name('cron.system_auto_update');
    // Sinh sitemap.xml phục vụ tối ưu tìm kiếm SEO
    Route::get('/generate-sitemap', [\App\Http\Controllers\CronController::class, 'generateSitemap'])->name('cron.generate_sitemap');
});
