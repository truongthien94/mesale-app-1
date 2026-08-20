<?php

use App\Http\Controllers\Api\V1\AccountController;
use App\Http\Controllers\Api\V1\ActivityLogController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BalanceLogController;
use App\Http\Controllers\Api\V1\Bot\OrderController as BotOrderController;
use App\Http\Controllers\Api\V1\CashbackController;
use App\Http\Controllers\Api\V1\CheckinController;
use App\Http\Controllers\Api\V1\ConfigController;
use App\Http\Controllers\Api\V1\CouponController;
use App\Http\Controllers\Api\V1\DeviceController;
use App\Http\Controllers\Api\V1\GiftCodeController;
use App\Http\Controllers\Api\V1\GiftController;
use App\Http\Controllers\Api\V1\GoogleNativeOAuthController;
use App\Http\Controllers\Api\V1\NativeOAuthController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\OrderController;
use App\Http\Controllers\Api\V1\PageController;
use App\Http\Controllers\Api\V1\PaymentAccountController;
use App\Http\Controllers\Api\V1\RankingController;
use App\Http\Controllers\Api\V1\ReferralController;
use App\Http\Controllers\Api\V1\SavedProductController;
use App\Http\Controllers\Api\V1\SecurityController;
use App\Http\Controllers\Api\V1\SessionController;
use App\Http\Controllers\Api\V1\TaskController;
use App\Http\Controllers\Api\V1\WithdrawalController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Open API v1 (dùng cho App Mobile / Frontend)
|--------------------------------------------------------------------------
| Toàn bộ endpoint nằm dưới tiền tố /api/v1/openapi.
|
| Bảo mật nhiều lớp:
|   - api.enabled            : công tắc tổng bật/tắt toàn hệ thống Open API.
|   - api.enabled:{group}    : công tắc riêng từng nhóm chức năng (Admin tự bật/tắt).
|   - api.auth               : xác thực token Bearer của thành viên.
|   - throttle               : giới hạn tần suất chống lạm dụng / DDoS.
*/

Route::prefix('v1/openapi')
    ->middleware(['api.enabled', 'throttle:120,1', 'api.log:openapi'])
    ->group(function () {

        // === Cấu hình khởi động ứng dụng (public, không cần token) ===
        Route::middleware('api.enabled:config')->group(function () {
            Route::get('config', [ConfigController::class, 'show']);
        });

        // === Trang tĩnh: Điều khoản, Chính sách bảo mật... (public — cần cho App Store/Play) ===
        Route::middleware('api.enabled:pages')->group(function () {
            Route::get('pages', [PageController::class, 'index']);
            Route::get('pages/{slug}', [PageController::class, 'show']);
        });

        // === Nhóm Xác thực: Đăng ký & Đăng nhập (không cần token) ===
        Route::prefix('auth')
            ->middleware('api.enabled:auth')
            ->group(function () {
                // Siết chặt tần suất cho các endpoint nhạy cảm về bảo mật
                Route::post('register', [AuthController::class, 'register'])->middleware('throttle:10,1');
                Route::post('login', [AuthController::class, 'login'])->middleware('throttle:10,1');
                Route::post('oauth/google', GoogleNativeOAuthController::class)
                    ->middleware(['api.enabled:auth_oauth_google', 'throttle:10,1']);
                Route::post('oauth/apple', [NativeOAuthController::class, 'apple'])
                    ->middleware(['api.enabled:auth_oauth_apple', 'throttle:30,1']);
                // Bước 2 cho tài khoản bật bảo mật 2 lớp (2FA)
                Route::post('login/2fa', [AuthController::class, 'loginTwoFactor'])->middleware('throttle:10,1');
                Route::post('login/2fa/resend', [AuthController::class, 'resendTwoFactorOtp'])->middleware('throttle:5,1');
                // Xác minh email khi hệ thống bắt buộc kích hoạt tài khoản
                Route::post('verify-email', [AuthController::class, 'verifyEmail'])->middleware('throttle:10,1');
                Route::post('verify-email/resend', [AuthController::class, 'resendEmailVerification'])->middleware('throttle:5,1');
                // Quên & đặt lại mật khẩu
                Route::post('forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:5,1');
                Route::post('reset-password', [AuthController::class, 'resetPassword'])->middleware('throttle:5,1');
                Route::post('logout', [AuthController::class, 'logout'])->middleware('api.auth');
            });

        // === Các endpoint yêu cầu xác thực token Bearer của phiên đăng nhập ===
        Route::middleware('api.auth')->group(function () {

            // Thông tin tài khoản, số dư & quản lý hồ sơ
            Route::middleware('api.enabled:profile')->group(function () {
                Route::get('account', [AccountController::class, 'show']);
                Route::post('account/referral-code', [AccountController::class, 'decideReferralPrompt'])->middleware('throttle:10,1');
                Route::post('account/profile', [AccountController::class, 'updateProfile']);
                Route::post('account/avatar', [AccountController::class, 'uploadAvatar'])->middleware('throttle:10,1');
                Route::delete('account/avatar', [AccountController::class, 'deleteAvatar'])->middleware('throttle:10,1');
                Route::post('account/preferences', [AccountController::class, 'updatePreferences']);
                Route::post('account/password', [AccountController::class, 'changePassword'])->middleware('throttle:10,1');
                Route::post('account/delete', [AccountController::class, 'deleteAccount'])->middleware('throttle:5,1');
            });

            // Thông báo
            Route::middleware('api.enabled:notifications')->group(function () {
                Route::get('notifications', [NotificationController::class, 'index']);
                Route::get('notifications/unread-count', [NotificationController::class, 'unreadCount']);
                Route::post('notifications/read-all', [NotificationController::class, 'markAllRead']);
                Route::post('notifications/{id}/read', [NotificationController::class, 'markRead'])->whereNumber('id');
            });

            // Lấy link hoàn tiền
            Route::middleware('api.enabled:cashback_link')->group(function () {
                Route::post('cashback/link', [CashbackController::class, 'create']);
            });

            // Danh sách & chi tiết đơn hàng hoàn tiền
            Route::middleware('api.enabled:orders')->group(function () {
                Route::get('orders', [OrderController::class, 'index']);
                Route::get('orders/{id}', [OrderController::class, 'show'])->whereNumber('id');
            });

            // Điểm danh hàng ngày
            Route::middleware('api.enabled:checkin')->group(function () {
                Route::get('checkin', [CheckinController::class, 'index']);
                Route::post('checkin', [CheckinController::class, 'store'])->middleware('throttle:10,1');
            });

            // Tiếp thị liên kết (giới thiệu F1/F2)
            Route::middleware('api.enabled:referrals')->group(function () {
                Route::get('referrals', [ReferralController::class, 'index']);
            });

            // Biến động số dư ví
            Route::middleware('api.enabled:balance_logs')->group(function () {
                Route::get('balance-logs', [BalanceLogController::class, 'index']);
            });

            // Nhật ký hoạt động tài khoản
            Route::middleware('api.enabled:activity_logs')->group(function () {
                Route::get('logs', [ActivityLogController::class, 'index']);
            });

            // Đổi quà tặng
            Route::middleware('api.enabled:gifts')->group(function () {
                Route::get('gifts', [GiftController::class, 'index']);
                Route::get('gifts/redemptions', [GiftController::class, 'redemptions']);
                Route::post('gifts/redeem', [GiftController::class, 'redeem'])->middleware(['idempotency.key', 'throttle:20,1']);
            });

            // Nhập Giftcode nhận thưởng
            Route::middleware('api.enabled:giftcode')->group(function () {
                Route::post('giftcode/redeem', [GiftCodeController::class, 'redeem'])->middleware(['idempotency.key', 'throttle:10,1']);
            });

            // Sản phẩm đã lưu (mua sau)
            Route::middleware('api.enabled:saved_products')->group(function () {
                Route::get('saved-products', [SavedProductController::class, 'index']);
                Route::post('saved-products', [SavedProductController::class, 'store'])->middleware('throttle:30,1');
                Route::delete('saved-products/{id}', [SavedProductController::class, 'destroy'])->whereNumber('id');
            });

            // Mã giảm giá
            Route::middleware('api.enabled:coupons')->group(function () {
                Route::get('coupons', [CouponController::class, 'index']);
            });

            // Bảng xếp hạng
            Route::middleware('api.enabled:ranking')->group(function () {
                Route::get('ranking', [RankingController::class, 'index']);
            });

            // Thiết bị nhận thông báo đẩy (Push Notification)
            Route::middleware('api.enabled:push')->group(function () {
                Route::get('devices', [DeviceController::class, 'index']);
                Route::post('devices/register', [DeviceController::class, 'register'])->middleware('throttle:30,1');
                Route::post('devices/unregister', [DeviceController::class, 'unregister'])->middleware('throttle:30,1');
            });

            // Quản lý bảo mật: 2FA + Email OTP
            Route::middleware('api.enabled:security')->group(function () {
                Route::get('security', [SecurityController::class, 'show']);
                Route::post('security/2fa/setup', [SecurityController::class, 'setup2FA'])->middleware('throttle:10,1');
                Route::post('security/2fa/enable', [SecurityController::class, 'enable2FA'])->middleware('throttle:10,1');
                Route::post('security/2fa/disable', [SecurityController::class, 'disable2FA'])->middleware('throttle:10,1');
                Route::post('security/email-otp/send', [SecurityController::class, 'sendEmailOTP'])->middleware('throttle:5,1');
                Route::post('security/email-otp/enable', [SecurityController::class, 'enableEmailOTP'])->middleware('throttle:10,1');
                Route::post('security/email-otp/disable', [SecurityController::class, 'disableEmailOTP'])->middleware('throttle:10,1');
            });

            // Quản lý phiên đăng nhập (thiết bị đang đăng nhập)
            Route::middleware('api.enabled:sessions')->group(function () {
                Route::get('sessions', [SessionController::class, 'index']);
                Route::post('sessions/revoke-others', [SessionController::class, 'revokeOthers']);
                Route::post('sessions/{id}/revoke', [SessionController::class, 'revoke'])->whereNumber('id');
            });

            // Rút tiền
            Route::middleware('api.enabled:withdraw')->group(function () {
                Route::get('withdrawals', [WithdrawalController::class, 'index']);
                Route::post('withdrawals', [WithdrawalController::class, 'store'])->middleware('idempotency.key');
                Route::post('withdrawals/otp', [WithdrawalController::class, 'sendOtp']);
            });

            // Sổ tài khoản nhận tiền (lưu, liệt kê, đặt mặc định, xóa)
            Route::middleware('api.enabled:payment_accounts')->group(function () {
                Route::get('payment-accounts', [PaymentAccountController::class, 'index']);
                Route::post('payment-accounts', [PaymentAccountController::class, 'store'])->middleware(['idempotency.key', 'throttle:20,1']);
                Route::post('payment-accounts/{id}/default', [PaymentAccountController::class, 'setDefault'])->whereNumber('id');
                Route::delete('payment-accounts/{id}', [PaymentAccountController::class, 'destroy'])->whereNumber('id');
            });

            // Nhiệm vụ nhận thưởng
            Route::middleware('api.enabled:tasks')->group(function () {
                Route::get('tasks', [TaskController::class, 'index']);
                Route::get('tasks/{task}/sync', [TaskController::class, 'sync'])->whereNumber('task')->middleware('throttle:30,1');
                Route::post('tasks/{task}/claim', [TaskController::class, 'claim'])->whereNumber('task')->middleware(['idempotency.key', 'throttle:20,1']);
                // Gửi yêu cầu xác nhận đã hoàn thành nhiệm vụ thủ công để chờ Admin duyệt
                Route::post('tasks/{task}/submit', [TaskController::class, 'submit'])->whereNumber('task')->middleware('throttle:10,1');
            });
        });
    });
/*
|--------------------------------------------------------------------------
| API cho hệ thống ngoài / Bot (tiền tố /api/v1/bot)
|--------------------------------------------------------------------------
| ĐỘC LẬP HOÀN TOÀN với Open API ở trên — đây là hai hệ thống tách rời:
|
|   - Open API (/api/v1/openapi) : dành cho chủ website tự build App Mobile.
|                                  Xác thực bằng token phiên đăng nhập, bật/tắt bởi `openapi_status`.
|   - Bot API  (/api/v1/bot)     : dành cho Bot / hệ thống bên thứ ba của thành viên.
|                                  Xác thực bằng Khóa API Token cá nhân lấy ở trang Hồ sơ,
|                                  bật/tắt bởi `api_docs_enabled` (mặc định BẬT).
|
| Nghĩa là: tắt Open API KHÔNG làm chết Bot của thành viên, và ngược lại.
|
| Dùng lại nguyên vẹn controller của Open API để tránh trùng lặp logic nghiệp vụ
| (chống SSRF, ràng buộc tên miền, giới hạn tần suất tạo link đều được giữ nguyên).
|
| Bảo mật: chỉ mở các endpoint đọc dữ liệu và tạo link. Tuyệt đối không mở endpoint
| đụng tới ví tiền (rút tiền) hay thiết lập bảo mật, vì Khóa API Token là khóa tĩnh vĩnh viễn.
*/
Route::prefix('v1/bot')
    ->middleware(['bot.enabled', 'throttle:120,1', 'api.auth:allow_key', 'api.log:bot'])
    ->group(function () {

        // Tạo link hoàn tiền từ link sản phẩm Shopee / TikTok Shop
        Route::post('cashback/link', [CashbackController::class, 'create']);

        // Danh sách & chi tiết đơn hàng đã ghi nhận kèm trạng thái.
        // Dùng controller riêng: ẩn khóa chính nội bộ (id) và tra cứu chi tiết theo mã đơn hàng
        // của sàn (order_id) — thứ duy nhất hệ thống ngoài cần để đối chiếu đơn.
        Route::get('orders', [BotOrderController::class, 'index']);
        Route::get('orders/{order_id}', [BotOrderController::class, 'show'])
            ->where('order_id', '[A-Za-z0-9_-]{1,64}');
    });
