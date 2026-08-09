<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin Routes - Các tuyến đường dành riêng cho Giao diện quản trị (Admin Panel)
|--------------------------------------------------------------------------
| File route này tự động được nạp và áp dụng middleware 'web' thông qua bootstrap/app.php.
| Mọi thay đổi về phân quyền và các module quản trị đều được thực hiện ở đây.
*/

Route::middleware(['auth', 'active', 'admin'])->prefix(\App\Models\Setting::getVal('admin_prefix', 'admin'))->group(function () {
    
    // Trang chủ thống kê Admin Dashboard
    Route::get('/', [\App\Http\Controllers\Admin\AdminDashboardController::class, 'index'])->name('admin.dashboard');
    Route::post('/dashboard/save-widgets-config', [\App\Http\Controllers\Admin\AdminDashboardController::class, 'saveWidgetsConfig'])->name('admin.dashboard.save_widgets_config');
    Route::get('/dashboard/chart-data', [\App\Http\Controllers\Admin\AdminDashboardController::class, 'getGrowthChartDataAjax'])->name('admin.dashboard.chart_data');
    
    // Nhóm 1: Quản lý thành viên (Users) & Thông báo hệ thống
    Route::middleware(['permission:manage_users'])->group(function () {
        Route::get('/users', [\App\Http\Controllers\Admin\UserController::class, 'index'])->name('admin.users.index');
        Route::post('/users/bulk-adjust-balance', [\App\Http\Controllers\Admin\UserController::class, 'bulkAdjustBalance'])->name('admin.users.bulk_adjust_balance');
        Route::get('/users/{user}/edit', [\App\Http\Controllers\Admin\UserController::class, 'edit'])->name('admin.users.edit');
        Route::get('/users/{user}/login-as', [\App\Http\Controllers\Admin\UserController::class, 'loginAs'])->name('admin.users.login_as');
        Route::get('/users/{user}/mlm-ajax', [\App\Http\Controllers\Admin\UserController::class, 'getMlmAjax'])->name('admin.users.mlm_ajax');
        Route::get('/users/{user}/balance-logs-ajax', [\App\Http\Controllers\Admin\UserController::class, 'getBalanceLogsAjax'])->name('admin.users.balance_logs_ajax');
        Route::get('/users/{user}/cashbacks-ajax', [\App\Http\Controllers\Admin\UserController::class, 'getCashbacksAjax'])->name('admin.users.cashbacks_ajax');
        Route::get('/users/{user}/activity-logs-ajax', [\App\Http\Controllers\Admin\UserController::class, 'getActivityLogsAjax'])->name('admin.users.activity_logs_ajax');
        Route::get('/users/{user}/sessions-ajax', [\App\Http\Controllers\Admin\UserController::class, 'getSessionsAjax'])->name('admin.users.sessions_ajax');
        Route::post('/users/{user}/logout-session/{id}', [\App\Http\Controllers\Admin\UserController::class, 'logoutSession'])->name('admin.users.logout_session');
        Route::post('/users/{user}', [\App\Http\Controllers\Admin\UserController::class, 'update'])->name('admin.users.update');
        Route::post('/users/{user}/adjust-balance', [\App\Http\Controllers\Admin\UserController::class, 'adjustBalance'])->name('admin.users.adjust_balance');
        Route::get('/users-export', [\App\Http\Controllers\Admin\UserController::class, 'exportCsv'])->name('admin.users.export');
        // API thống kê số lượng đăng ký thành viên theo tuần/tháng/năm (AJAX Chart.js)
        Route::get('/users/registration-stats', [\App\Http\Controllers\Admin\UserController::class, 'registrationStats'])->name('admin.users.registration_stats');
        
        // Gửi thông báo hệ thống
        Route::get('/notifications', [\App\Http\Controllers\Admin\NotificationController::class, 'index'])->name('admin.notifications.index');
        Route::post('/notifications', [\App\Http\Controllers\Admin\NotificationController::class, 'store'])->name('admin.notifications.store');
        Route::post('/notifications/generate-ai', [\App\Http\Controllers\Admin\NotificationController::class, 'generateAi'])->name('admin.notifications.generate_ai');
    });

    // Nhóm 1.5: Xoá thành viên (Tách biệt quyền hạn bảo mật)
    Route::middleware(['permission:delete_users'])->group(function () {
        Route::delete('/users/bulk-destroy', [\App\Http\Controllers\Admin\UserController::class, 'bulkDestroy'])->name('admin.users.bulk_destroy');
        Route::delete('/users/{user}', [\App\Http\Controllers\Admin\UserController::class, 'destroy'])->name('admin.users.destroy');
    });

    // Nhóm 1.7: Quản lý sản phẩm (Products)
    Route::middleware(['permission:manage_products'])->group(function () {
        Route::get('/products', [\App\Http\Controllers\Admin\ProductController::class, 'index'])->name('admin.products.index');
        Route::get('/products/stats', [\App\Http\Controllers\Admin\ProductController::class, 'productStats'])->name('admin.products.stats');
        // Dọn dẹp sản phẩm cache theo mốc thời gian lưu trữ (modal "Dọn dẹp")
        Route::delete('/products/cleanup', [\App\Http\Controllers\Admin\ProductController::class, 'cleanup'])->name('admin.products.cleanup');
        // Xóa nhanh hàng loạt các sản phẩm được tích chọn trên bảng danh sách
        Route::delete('/products/bulk-destroy', [\App\Http\Controllers\Admin\ProductController::class, 'bulkDestroy'])->name('admin.products.bulk_destroy');
        // Ràng buộc {id} chỉ nhận giá trị số để không bắt nhầm các route hành động phía trên
        Route::delete('/products/{id}', [\App\Http\Controllers\Admin\ProductController::class, 'destroy'])->name('admin.products.destroy')->whereNumber('id');
    });

    // Nhóm 2: Quản lý lịch sử hoàn tiền (Cashback Histories)
    Route::middleware(['permission:manage_cashbacks'])->group(function () {
        Route::get('/cashback', [\App\Http\Controllers\Admin\CashbackController::class, 'index'])->name('admin.cashback.index');
        // API thống kê đơn hoàn tiền theo tuần/tháng/năm (AJAX Chart.js)
        Route::get('/cashback/stats', [\App\Http\Controllers\Admin\CashbackController::class, 'cashbackStats'])->name('admin.cashback.stats');
        Route::post('/cashback/bulk-approve', [\App\Http\Controllers\Admin\CashbackController::class, 'bulkApprove'])->name('admin.cashback.bulk_approve');
        Route::post('/cashback/bulk-reject', [\App\Http\Controllers\Admin\CashbackController::class, 'bulkReject'])->name('admin.cashback.bulk_reject');
        Route::delete('/cashback/bulk-destroy', [\App\Http\Controllers\Admin\CashbackController::class, 'bulkDestroy'])->name('admin.cashback.bulk_destroy');
        Route::delete('/cashback/{id}', [\App\Http\Controllers\Admin\CashbackController::class, 'destroy'])->name('admin.cashback.destroy');
        Route::post('/cashback/{id}/approve', [\App\Http\Controllers\Admin\CashbackController::class, 'approve'])->name('admin.cashback.approve');
        Route::post('/cashback/{id}/reject', [\App\Http\Controllers\Admin\CashbackController::class, 'reject'])->name('admin.cashback.reject');
        // API lấy danh sách lịch sử click của đơn hàng hoàn tiền (dùng AJAX trong modal chi tiết)
        Route::get('/cashback/{id}/click-logs', [\App\Http\Controllers\Admin\CashbackController::class, 'getClickLogs'])->name('admin.cashback.click_logs');
        // Đồng bộ tất cả Shopee Accounts + TikTok Shop và trả kết quả JSON cho modal
        Route::post('/cashback/sync-api', [\App\Http\Controllers\Admin\CashbackController::class, 'syncApi'])->name('admin.cashback.sync_api');
    });

    // Nhóm 3: Quản lý yêu cầu rút tiền (Withdrawals)
    Route::middleware(['permission:manage_withdrawals'])->group(function () {
        Route::get('/withdrawals', [\App\Http\Controllers\Admin\WithdrawalController::class, 'index'])->name('admin.withdrawals.index');
        Route::get('/withdrawals/stats', [\App\Http\Controllers\Admin\WithdrawalController::class, 'withdrawalStats'])->name('admin.withdrawals.stats');
        Route::get('/withdrawals/{id}/details', [\App\Http\Controllers\Admin\WithdrawalController::class, 'show'])->name('admin.withdrawals.show');
        Route::post('/withdrawals/bulk-approve', [\App\Http\Controllers\Admin\WithdrawalController::class, 'bulkApprove'])->name('admin.withdrawals.bulk_approve');
        Route::post('/withdrawals/{id}/approve', [\App\Http\Controllers\Admin\WithdrawalController::class, 'approve'])->name('admin.withdrawals.approve');
        Route::post('/withdrawals/{id}/reject', [\App\Http\Controllers\Admin\WithdrawalController::class, 'reject'])->name('admin.withdrawals.reject');
        Route::delete('/withdrawals/bulk-destroy', [\App\Http\Controllers\Admin\WithdrawalController::class, 'bulkDestroy'])->name('admin.withdrawals.bulk_destroy');
        Route::delete('/withdrawals/{id}', [\App\Http\Controllers\Admin\WithdrawalController::class, 'destroy'])->name('admin.withdrawals.destroy');
    });

    // Nhóm 3.5: Xem nhật ký biến động số dư thành viên (Balance Logs)
    Route::middleware(['permission:view_balance_logs'])->group(function () {
        Route::get('/balance-logs', [\App\Http\Controllers\Admin\UserController::class, 'balanceLogs'])->name('admin.balance_logs.index');
        Route::post('/balance-logs/clear', [\App\Http\Controllers\Admin\UserController::class, 'clearBalanceLogs'])->name('admin.balance_logs.clear');
    });

    // Nhóm 3.6: Xem nhật ký hoạt động (Logs Activity)
    Route::middleware(['permission:view_activity_logs'])->group(function () {
        Route::get('/logs/activity', [\App\Http\Controllers\Admin\LogController::class, 'activityLogs'])->name('admin.logs.activity');
        Route::post('/logs/activity/clear', [\App\Http\Controllers\Admin\LogController::class, 'clearActivityLogs'])->name('admin.logs.activity.clear');
    });

    // Nhóm 3.7: Xem nhật ký lỗi hệ thống (System Logs)
    Route::middleware(['permission:view_system_logs'])->group(function () {
        Route::get('/logs/system', [\App\Http\Controllers\Admin\LogController::class, 'systemLogs'])->name('admin.logs.system');
        Route::get('/logs/system/download', [\App\Http\Controllers\Admin\LogController::class, 'downloadSystemLogs'])->name('admin.logs.system.download');
        Route::post('/logs/system/clear', [\App\Http\Controllers\Admin\LogController::class, 'clearSystemLogs'])->name('admin.logs.system.clear');
    });

    // Nhóm 3.8: Xem hàng đợi Email hệ thống (Email Queue Logs)
    Route::middleware(['permission:view_email_queue_logs'])->group(function () {
        Route::get('/logs/email-queue', [\App\Http\Controllers\Admin\LogController::class, 'emailQueueLogs'])->name('admin.logs.email_queue');
        Route::post('/logs/email-queue/{id}/retry', [\App\Http\Controllers\Admin\LogController::class, 'retryEmail'])->name('admin.logs.email_queue.retry');
        Route::post('/logs/email-queue/{id}/delete', [\App\Http\Controllers\Admin\LogController::class, 'deleteEmail'])->name('admin.logs.email_queue.delete');
        Route::post('/logs/email-queue/bulk-retry', [\App\Http\Controllers\Admin\LogController::class, 'bulkRetryEmail'])->name('admin.logs.email_queue.bulk_retry');
        Route::post('/logs/email-queue/bulk-delete', [\App\Http\Controllers\Admin\LogController::class, 'bulkDeleteEmail'])->name('admin.logs.email_queue.bulk_delete');
        Route::post('/logs/email-queue/clear', [\App\Http\Controllers\Admin\LogController::class, 'clearEmailQueue'])->name('admin.logs.email_queue.clear');
    });

    // Nhóm 3.8.5: Xem hàng đợi Telegram hệ thống (Telegram Queue Logs)
    Route::middleware(['permission:view_telegram_queue_logs'])->group(function () {
        Route::get('/logs/telegram-queue', [\App\Http\Controllers\Admin\LogController::class, 'telegramQueueLogs'])->name('admin.logs.telegram_queue');
        Route::post('/logs/telegram-queue/{id}/retry', [\App\Http\Controllers\Admin\LogController::class, 'retryTelegram'])->name('admin.logs.telegram_queue.retry');
        Route::post('/logs/telegram-queue/{id}/delete', [\App\Http\Controllers\Admin\LogController::class, 'deleteTelegram'])->name('admin.logs.telegram_queue.delete');
        Route::post('/logs/telegram-queue/clear', [\App\Http\Controllers\Admin\LogController::class, 'clearTelegramQueue'])->name('admin.logs.telegram_queue.clear');
    });

    // Nhóm 3.9: Xem nhật ký điểm danh hệ thống (Daily Checkin Logs)
    Route::middleware(['permission:view_checkin_logs'])->group(function () {
        Route::get('/logs/checkin', [\App\Http\Controllers\Admin\LogController::class, 'checkinLogs'])->name('admin.logs.checkin');
        Route::post('/logs/checkin/clear', [\App\Http\Controllers\Admin\LogController::class, 'clearCheckinLogs'])->name('admin.logs.checkin.clear');
    });

    // Nhóm 3.10: Xem nhật ký hoa hồng Affiliate (Referral Commissions Logs)
    Route::middleware(['permission:view_referral_commissions'])->group(function () {
        Route::get('/logs/referrals', [\App\Http\Controllers\Admin\LogController::class, 'referralCommissions'])->name('admin.logs.referrals');
        Route::post('/logs/referrals/clear', [\App\Http\Controllers\Admin\LogController::class, 'clearReferralCommissions'])->name('admin.logs.referrals.clear');
    });

    // Nhóm 3.11: Xem nhật ký lượt click hoàn tiền (Cashback Click Logs)
    Route::middleware(['permission:view_cashback_click_logs'])->group(function () {
        Route::get('/logs/cashback-clicks', [\App\Http\Controllers\Admin\LogController::class, 'cashbackClicks'])->name('admin.logs.cashback_clicks');
        Route::get('/logs/cashback-clicks/stats', [\App\Http\Controllers\Admin\LogController::class, 'cashbackClicksStats'])->name('admin.logs.cashback_clicks.stats');
        Route::post('/logs/cashback-clicks/clear', [\App\Http\Controllers\Admin\LogController::class, 'clearCashbackClicks'])->name('admin.logs.cashback_clicks.clear');
    });

    // Nhóm 3.12: Xem nhật ký liên kết rút gọn (Short Link Logs)
    Route::middleware(['permission:view_short_links'])->group(function () {
        Route::get('/logs/short-links', [\App\Http\Controllers\Admin\LogController::class, 'shortLinks'])->name('admin.logs.short_links');
        Route::post('/logs/short-links/clear', [\App\Http\Controllers\Admin\LogController::class, 'clearShortLinks'])->name('admin.logs.short_links.clear');
    });

    // Nhóm 3.13: Xem nhật ký thông báo đã gửi cho user (Notification Logs)
    // Business rule: Giúp admin kiểm tra lại lịch sử các thông báo hệ thống hoặc thông báo cá nhân đã gửi cho user, kèm trạng thái đã đọc/chưa đọc.
    Route::middleware(['permission:view_notification_logs'])->group(function () {
        Route::get('/logs/notifications', [\App\Http\Controllers\Admin\LogController::class, 'notificationLogs'])->name('admin.logs.notifications');
        Route::delete('/logs/notifications/{id}', [\App\Http\Controllers\Admin\LogController::class, 'deleteNotification'])->name('admin.logs.notifications.delete');
        Route::post('/logs/notifications/clear', [\App\Http\Controllers\Admin\LogController::class, 'clearNotificationLogs'])->name('admin.logs.notifications.clear');
    });

    // Nhóm 3.13.5: Xem nhật ký sản phẩm đã lưu của User (Saved Products Logs)
    Route::middleware(['permission:view_saved_products'])->group(function () {
        Route::get('/logs/saved-products', [\App\Http\Controllers\Admin\LogController::class, 'savedProducts'])->name('admin.logs.saved_products');
        Route::delete('/logs/saved-products/{id}', [\App\Http\Controllers\Admin\LogController::class, 'deleteSavedProduct'])->name('admin.logs.saved_products.delete');
        Route::post('/logs/saved-products/clear', [\App\Http\Controllers\Admin\LogController::class, 'clearSavedProducts'])->name('admin.logs.saved_products.clear');
    });

    // Nhóm 3.14: Xem nhật ký gọi API (API Request Logs)
    // Business rule: Giúp Admin giám sát hoạt động sử dụng API của thành viên — ai gọi, lúc nào, endpoint nào, dữ liệu gì.
    Route::middleware(['permission:view_api_logs'])->group(function () {
        Route::get('/logs/api', [\App\Http\Controllers\Admin\LogController::class, 'apiLogs'])->name('admin.logs.api');
        Route::post('/logs/api/clear', [\App\Http\Controllers\Admin\LogController::class, 'clearApiLogs'])->name('admin.logs.api.clear');
    });

    // Nhóm 4: Quản lý cấu hình hệ thống & Banner quảng cáo
    Route::middleware(['permission:manage_settings'])->group(function () {
        Route::get('/settings', [\App\Http\Controllers\Admin\SettingController::class, 'index'])->name('admin.settings.index');
        Route::post('/settings', [\App\Http\Controllers\Admin\SettingController::class, 'update'])->name('admin.settings.update');
        Route::post('/settings/test-smtp', [\App\Http\Controllers\Admin\SettingController::class, 'testSmtp'])->name('admin.settings.test_smtp');
        Route::post('/settings/test-email', [\App\Http\Controllers\Admin\SettingController::class, 'testEmail'])->name('admin.settings.test_email');
        Route::post('/settings/test-telegram', [\App\Http\Controllers\Admin\SettingController::class, 'testTelegram'])->name('admin.settings.test_telegram');
        Route::post('/settings/test-ai', [\App\Http\Controllers\Admin\SettingController::class, 'testAI'])->name('admin.settings.test_ai');
        // API AJAX: tạo nhanh nội dung popup thông báo trang chủ bằng AI
        Route::post('/settings/generate-popup-ai', [\App\Http\Controllers\Admin\SettingController::class, 'generatePopupAi'])->name('admin.settings.generate_popup_ai');
        // API AJAX: tạo nhanh nội dung mẫu email giao dịch bằng AI
        Route::post('/settings/generate-email-ai', [\App\Http\Controllers\Admin\SettingController::class, 'generateEmailTemplateAi'])->name('admin.settings.generate_email_ai');
        Route::delete('/settings/email-defaults/{key}', [\App\Http\Controllers\Admin\SettingController::class, 'resetEmailDefault'])->name('admin.settings.reset_email_default');
        Route::post('/settings/run-cron', [\App\Http\Controllers\Admin\SettingController::class, 'runCron'])->name('admin.settings.run_cron');

        // Quản lý Banner quảng cáo
        Route::get('/banners', [\App\Http\Controllers\Admin\BannerController::class, 'index'])->name('admin.banners.index');
        Route::post('/banners', [\App\Http\Controllers\Admin\BannerController::class, 'store'])->name('admin.banners.store');
        Route::put('/banners/{banner}', [\App\Http\Controllers\Admin\BannerController::class, 'update'])->name('admin.banners.update');
        Route::delete('/banners/{banner}', [\App\Http\Controllers\Admin\BannerController::class, 'destroy'])->name('admin.banners.destroy');
    });

    // Nhóm 4.5: Quản lý đa ngôn ngữ (Languages)
    Route::middleware(['permission:manage_languages'])->group(function () {
        Route::get('/languages', [\App\Http\Controllers\Admin\LanguageController::class, 'index'])->name('admin.languages.index');
        Route::post('/languages', [\App\Http\Controllers\Admin\LanguageController::class, 'store'])->name('admin.languages.store');
        Route::put('/languages/{language}', [\App\Http\Controllers\Admin\LanguageController::class, 'update'])->name('admin.languages.update');
        Route::delete('/languages/{language}', [\App\Http\Controllers\Admin\LanguageController::class, 'destroy'])->name('admin.languages.destroy');

        // Quản lý chi tiết bản dịch của từng ngôn ngữ
        Route::get('/languages/{language}/translations', [\App\Http\Controllers\Admin\LanguageController::class, 'translations'])->name('admin.languages.translations');
        Route::post('/languages/{language}/translations/update', [\App\Http\Controllers\Admin\LanguageController::class, 'updateTranslation'])->name('admin.languages.translations.update');
        Route::post('/languages/{language}/translations/delete', [\App\Http\Controllers\Admin\LanguageController::class, 'deleteTranslation'])->name('admin.languages.translations.delete');
        Route::post('/languages/{language}/translations/auto-translate', [\App\Http\Controllers\Admin\LanguageController::class, 'autoTranslate'])->name('admin.languages.translations.auto_translate');
        Route::post('/languages/{language}/translations/sync-file', [\App\Http\Controllers\Admin\LanguageController::class, 'syncFileTranslations'])->name('admin.languages.translations.sync_file');
        Route::post('/languages/{language}/translations/rebuild', [\App\Http\Controllers\Admin\LanguageController::class, 'rebuildTranslations'])->name('admin.languages.translations.rebuild');
    });

    // Nhóm 4.6: Quản lý đa tiền tệ (Currencies)
    Route::middleware(['permission:manage_currencies'])->group(function () {
        Route::get('/currencies', [\App\Http\Controllers\Admin\CurrencyController::class, 'index'])->name('admin.currencies.index');
        Route::post('/currencies', [\App\Http\Controllers\Admin\CurrencyController::class, 'store'])->name('admin.currencies.store');
        Route::put('/currencies/{currency}', [\App\Http\Controllers\Admin\CurrencyController::class, 'update'])->name('admin.currencies.update');
        Route::delete('/currencies/{currency}', [\App\Http\Controllers\Admin\CurrencyController::class, 'destroy'])->name('admin.currencies.destroy');
    });

    // Nhóm 5: Quản lý vai trò phân quyền (Roles)
    Route::middleware(['permission:manage_roles'])->group(function () {
        Route::get('/roles', [\App\Http\Controllers\Admin\RoleController::class, 'index'])->name('admin.roles.index');
        Route::get('/roles/create', [\App\Http\Controllers\Admin\RoleController::class, 'create'])->name('admin.roles.create');
        Route::post('/roles', [\App\Http\Controllers\Admin\RoleController::class, 'store'])->name('admin.roles.store');
        Route::get('/roles/{role}/edit', [\App\Http\Controllers\Admin\RoleController::class, 'edit'])->name('admin.roles.edit');
        Route::put('/roles/{role}', [\App\Http\Controllers\Admin\RoleController::class, 'update'])->name('admin.roles.update');
        Route::delete('/roles/{role}', [\App\Http\Controllers\Admin\RoleController::class, 'destroy'])->name('admin.roles.destroy');
    });

    // Nhóm 6: Quản lý Blog CMS
    Route::middleware(['permission:manage_blog'])->prefix('blog')->group(function () {
        // Bài viết
        Route::get('/posts', [\App\Http\Controllers\Admin\BlogController::class, 'postsIndex'])->name('admin.blog.posts.index');
        Route::get('/posts/create', [\App\Http\Controllers\Admin\BlogController::class, 'postCreate'])->name('admin.blog.posts.create');
        Route::post('/posts', [\App\Http\Controllers\Admin\BlogController::class, 'postStore'])->name('admin.blog.posts.store');
        // API AJAX: tạo nhanh nội dung bài viết bằng AI
        Route::post('/posts/generate-ai', [\App\Http\Controllers\Admin\BlogController::class, 'postGenerateAi'])->name('admin.blog.posts.generate_ai');
        Route::get('/posts/{post}/edit', [\App\Http\Controllers\Admin\BlogController::class, 'postEdit'])->name('admin.blog.posts.edit');
        Route::put('/posts/{post}', [\App\Http\Controllers\Admin\BlogController::class, 'postUpdate'])->name('admin.blog.posts.update');
        Route::delete('/posts/{post}', [\App\Http\Controllers\Admin\BlogController::class, 'postDestroy'])->name('admin.blog.posts.destroy');
        Route::post('/posts/{post}/toggle-sticky', [\App\Http\Controllers\Admin\BlogController::class, 'postToggleSticky'])->name('admin.blog.posts.toggle_sticky');

        // Danh mục (Category)
        Route::get('/categories', [\App\Http\Controllers\Admin\BlogController::class, 'categoriesIndex'])->name('admin.blog.categories.index');
        Route::post('/categories', [\App\Http\Controllers\Admin\BlogController::class, 'categoryStore'])->name('admin.blog.categories.store');
        Route::put('/categories/{category}', [\App\Http\Controllers\Admin\BlogController::class, 'categoryUpdate'])->name('admin.blog.categories.update');
        Route::delete('/categories/{category}', [\App\Http\Controllers\Admin\BlogController::class, 'categoryDestroy'])->name('admin.blog.categories.destroy');

        // Tag
        Route::get('/tags', [\App\Http\Controllers\Admin\BlogController::class, 'tagsIndex'])->name('admin.blog.tags.index');
        Route::post('/tags', [\App\Http\Controllers\Admin\BlogController::class, 'tagStore'])->name('admin.blog.tags.store');
        Route::put('/tags/{tag}', [\App\Http\Controllers\Admin\BlogController::class, 'tagUpdate'])->name('admin.blog.tags.update');
        Route::delete('/tags/{tag}', [\App\Http\Controllers\Admin\BlogController::class, 'tagDestroy'])->name('admin.blog.tags.destroy');

        // Bình luận (Comment)
        Route::get('/comments', [\App\Http\Controllers\Admin\BlogController::class, 'commentsIndex'])->name('admin.blog.comments.index');
        Route::post('/comments/{comment}/toggle-status', [\App\Http\Controllers\Admin\BlogController::class, 'commentToggleStatus'])->name('admin.blog.comments.toggle_status');
        Route::post('/comments/{comment}/reply', [\App\Http\Controllers\Admin\BlogController::class, 'commentReply'])->name('admin.blog.comments.reply');
        Route::delete('/comments/{comment}', [\App\Http\Controllers\Admin\BlogController::class, 'commentDestroy'])->name('admin.blog.comments.destroy');

        // Cấu hình (Settings)
        Route::get('/settings', [\App\Http\Controllers\Admin\BlogController::class, 'settingsIndex'])->name('admin.blog.settings.index');
        Route::post('/settings', [\App\Http\Controllers\Admin\BlogController::class, 'settingsStore'])->name('admin.blog.settings.store');
    });

    // Nhóm 6.5: Quản lý trang nội dung tĩnh (Pages)
    Route::middleware(['permission:manage_pages'])->group(function () {
        Route::get('/pages', [\App\Http\Controllers\Admin\PageController::class, 'index'])->name('admin.pages.index');
        Route::get('/pages/create', [\App\Http\Controllers\Admin\PageController::class, 'create'])->name('admin.pages.create');
        Route::post('/pages', [\App\Http\Controllers\Admin\PageController::class, 'store'])->name('admin.pages.store');
        // API AJAX: tạo nhanh nội dung trang bằng AI
        Route::post('/pages/generate-ai', [\App\Http\Controllers\Admin\PageController::class, 'generateAi'])->name('admin.pages.generate_ai');
        Route::get('/pages/{page}/edit', [\App\Http\Controllers\Admin\PageController::class, 'edit'])->name('admin.pages.edit');
        Route::put('/pages/{page}', [\App\Http\Controllers\Admin\PageController::class, 'update'])->name('admin.pages.update');
        Route::delete('/pages/{page}', [\App\Http\Controllers\Admin\PageController::class, 'destroy'])->name('admin.pages.destroy');
    });

    // Nhóm 7: Quản lý Media (CKFinder)
    Route::middleware(['permission:manage_media'])->group(function () {
        Route::get('/media', [\App\Http\Controllers\Admin\MediaController::class, 'index'])->name('admin.media.index');
    });

    // Nhóm 8: Quản lý Công cụ (Tools)
    Route::middleware(['permission:manage_tools'])->group(function () {
        Route::get('/tools', [\App\Http\Controllers\Admin\ToolController::class, 'index'])->name('admin.tools.index');
        Route::post('/tools/import-commission', [\App\Http\Controllers\Admin\ToolController::class, 'importCommission'])->name('admin.tools.import_commission');
        
        // Quản lý đồng bộ Shopee Affiliate tự động qua Cookie
        Route::post('/shopee-accounts', [\App\Http\Controllers\Admin\ToolController::class, 'storeShopeeAccount'])->name('admin.tools.shopee_accounts.store');
        Route::delete('/shopee-accounts/{id}', [\App\Http\Controllers\Admin\ToolController::class, 'destroyShopeeAccount'])->name('admin.tools.shopee_accounts.destroy');
        Route::post('/shopee-accounts/{id}/sync', [\App\Http\Controllers\Admin\ToolController::class, 'syncShopeeAccount'])->name('admin.tools.shopee_accounts.sync');
    });

    // Nhóm 8.5: Tùy chỉnh giao diện trang chủ (Appearance)
    Route::middleware(['permission:manage_appearance'])->group(function () {
        Route::get('/appearance', [\App\Http\Controllers\Admin\AppearanceController::class, 'index'])->name('admin.appearance.index');
        // Xuất bản toàn bộ cấu trúc block của trình dựng trang
        Route::post('/appearance/publish', [\App\Http\Controllers\Admin\AppearanceController::class, 'publish'])->name('admin.appearance.publish');
        // Lưu bản nháp tạm để hiển thị trong khung xem trước (live preview)
        Route::post('/appearance/preview-draft', [\App\Http\Controllers\Admin\AppearanceController::class, 'previewDraft'])->name('admin.appearance.preview_draft');
        // Trang xem trước homepage từ bản nháp (hiển thị trong iframe)
        Route::get('/appearance/preview', [\App\Http\Controllers\Admin\AppearanceController::class, 'preview'])->name('admin.appearance.preview');
        // API AJAX: tạo nội dung HTML Section tùy chỉnh bằng AI
        Route::post('/appearance/generate-ai', [\App\Http\Controllers\Admin\AppearanceController::class, 'generateAi'])->name('admin.appearance.generate_ai');
    });

    // Nhóm 9: Quản lý Cập nhật hệ thống (Updates)
    Route::middleware(['permission:manage_updates'])->group(function () {
        Route::get('/update', [\App\Http\Controllers\Admin\UpdateController::class, 'index'])->name('admin.update.index');
        Route::post('/update/toggle-auto', [\App\Http\Controllers\Admin\UpdateController::class, 'toggleAutoUpdate'])->name('admin.update.toggle-auto');
        Route::post('/update/check', [\App\Http\Controllers\Admin\UpdateController::class, 'check'])->name('admin.update.check');
        Route::post('/update/commit-detail', [\App\Http\Controllers\Admin\UpdateController::class, 'commitDetail'])->name('admin.update.commit-detail');
        Route::post('/update/apply', [\App\Http\Controllers\Admin\UpdateController::class, 'apply'])->name('admin.update.apply');
        Route::post('/update/unlock', [\App\Http\Controllers\Admin\UpdateController::class, 'forceUnlock'])->name('admin.update.unlock');
        Route::post('/update/save-license', [\App\Http\Controllers\Admin\UpdateController::class, 'saveLicense'])->name('admin.update.save-license');
    });

    // Nhóm 9.5: Quản lý Quà tặng & Đổi thưởng (Gifts)
    Route::middleware(['permission:manage_gifts'])->group(function () {
        Route::get('/gifts', [\App\Http\Controllers\Admin\GiftController::class, 'index'])->name('admin.gifts.index');
        Route::post('/gifts', [\App\Http\Controllers\Admin\GiftController::class, 'store'])->name('admin.gifts.store');
        Route::post('/gifts/config', [\App\Http\Controllers\Admin\GiftController::class, 'updateConfig'])->name('admin.gifts.config.update');
        Route::get('/gifts/export', [\App\Http\Controllers\Admin\GiftController::class, 'export'])->name('admin.gifts.export');
        Route::post('/gifts/import', [\App\Http\Controllers\Admin\GiftController::class, 'import'])->name('admin.gifts.import');
        Route::post('/gifts/tags', [\App\Http\Controllers\Admin\GiftController::class, 'updateTags'])->name('admin.gifts.tags.update');
        Route::delete('/gifts/bulk-destroy', [\App\Http\Controllers\Admin\GiftController::class, 'bulkDestroy'])->name('admin.gifts.bulk_destroy');
        Route::post('/gifts/bulk-update', [\App\Http\Controllers\Admin\GiftController::class, 'bulkUpdate'])->name('admin.gifts.bulk_update');
        Route::put('/gifts/{gift}', [\App\Http\Controllers\Admin\GiftController::class, 'update'])->name('admin.gifts.update');
        Route::delete('/gifts/{gift}', [\App\Http\Controllers\Admin\GiftController::class, 'destroy'])->name('admin.gifts.destroy');
        Route::post('/gifts/redemptions/{id}/approve', [\App\Http\Controllers\Admin\GiftController::class, 'approve'])->name('admin.gifts.redemptions.approve');
        Route::post('/gifts/redemptions/{id}/reject', [\App\Http\Controllers\Admin\GiftController::class, 'reject'])->name('admin.gifts.redemptions.reject');
    });

    // Nhóm 9.7: Quản lý Nhiệm vụ (Tasks)
    Route::middleware(['permission:manage_tasks'])->prefix('tasks')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\TaskController::class, 'index'])->name('admin.tasks.index');
        // API thống kê nhiệm vụ của thành viên theo tuần/tháng/năm (AJAX Chart.js)
        Route::get('/stats', [\App\Http\Controllers\Admin\TaskController::class, 'taskStats'])->name('admin.tasks.stats');
        Route::post('/', [\App\Http\Controllers\Admin\TaskController::class, 'store'])->name('admin.tasks.store');
        Route::post('/toggle-feature', [\App\Http\Controllers\Admin\TaskController::class, 'toggleFeature'])->name('admin.tasks.toggle_feature');
        Route::post('/config', [\App\Http\Controllers\Admin\TaskController::class, 'updateConfig'])->name('admin.tasks.config.update');
        Route::post('/update-order', [\App\Http\Controllers\Admin\TaskController::class, 'updateOrder'])->name('admin.tasks.update_order');
        Route::delete('/bulk-destroy', [\App\Http\Controllers\Admin\TaskController::class, 'bulkDestroy'])->name('admin.tasks.bulk_destroy');
        Route::put('/{task}', [\App\Http\Controllers\Admin\TaskController::class, 'update'])->name('admin.tasks.update');
        Route::delete('/{task}', [\App\Http\Controllers\Admin\TaskController::class, 'destroy'])->name('admin.tasks.destroy');
        Route::post('/{task}/toggle-status', [\App\Http\Controllers\Admin\TaskController::class, 'toggleStatus'])->name('admin.tasks.toggle_status');
        Route::post('/submissions/{submissionId}/approve', [\App\Http\Controllers\Admin\TaskController::class, 'approveSubmission'])->name('admin.tasks.submissions.approve');
        Route::post('/submissions/{submissionId}/reject', [\App\Http\Controllers\Admin\TaskController::class, 'rejectSubmission'])->name('admin.tasks.submissions.reject');
    });

    // Nhóm 9.6: Quản lý Giftcode (Mã quà tặng nhập tay nhận thưởng)
    Route::middleware(['permission:manage_gift_codes'])->group(function () {
        Route::get('/giftcodes', [\App\Http\Controllers\Admin\GiftCodeController::class, 'index'])->name('admin.giftcodes.index');
        Route::post('/giftcodes', [\App\Http\Controllers\Admin\GiftCodeController::class, 'store'])->name('admin.giftcodes.store');
        Route::post('/giftcodes/config', [\App\Http\Controllers\Admin\GiftCodeController::class, 'updateConfig'])->name('admin.giftcodes.config.update');
        Route::get('/giftcodes/generate', [\App\Http\Controllers\Admin\GiftCodeController::class, 'generate'])->name('admin.giftcodes.generate');
        Route::delete('/giftcodes/bulk-destroy', [\App\Http\Controllers\Admin\GiftCodeController::class, 'bulkDestroy'])->name('admin.giftcodes.bulk_destroy');
        Route::post('/giftcodes/{giftCode}/toggle-status', [\App\Http\Controllers\Admin\GiftCodeController::class, 'toggleStatus'])->name('admin.giftcodes.toggle_status');
        Route::put('/giftcodes/{giftCode}', [\App\Http\Controllers\Admin\GiftCodeController::class, 'update'])->name('admin.giftcodes.update');
        Route::delete('/giftcodes/{giftCode}', [\App\Http\Controllers\Admin\GiftCodeController::class, 'destroy'])->name('admin.giftcodes.destroy');
    });

    // Nhóm 10: Quản lý Menu tùy biến (Menus)
    Route::middleware(['permission:manage_menus'])->group(function () {
        Route::get('/menus', [\App\Http\Controllers\Admin\MenuController::class, 'index'])->name('admin.menus.index');
        Route::post('/menus', [\App\Http\Controllers\Admin\MenuController::class, 'store'])->name('admin.menus.store');
        Route::put('/menus/{menu}', [\App\Http\Controllers\Admin\MenuController::class, 'update'])->name('admin.menus.update');
        Route::delete('/menus/{menu}', [\App\Http\Controllers\Admin\MenuController::class, 'destroy'])->name('admin.menus.destroy');
        Route::post('/menus/update-order', [\App\Http\Controllers\Admin\MenuController::class, 'updateOrder'])->name('admin.menus.update_order');
        Route::post('/menus/{menu}/toggle-status', [\App\Http\Controllers\Admin\MenuController::class, 'toggleStatus'])->name('admin.menus.toggle_status');
    });

    // Nhóm 10.5: Quản lý Mã giảm giá thủ công (Coupons)
    Route::middleware(['permission:manage_coupons'])->group(function () {
        Route::get('/coupons', [\App\Http\Controllers\Admin\CouponController::class, 'index'])->name('admin.coupons.index');
        Route::post('/coupons', [\App\Http\Controllers\Admin\CouponController::class, 'store'])->name('admin.coupons.store');
        Route::delete('/coupons/bulk-destroy', [\App\Http\Controllers\Admin\CouponController::class, 'bulkDestroy'])->name('admin.coupons.bulk_destroy');
        Route::put('/coupons/{coupon}', [\App\Http\Controllers\Admin\CouponController::class, 'update'])->name('admin.coupons.update');
        Route::delete('/coupons/{coupon}', [\App\Http\Controllers\Admin\CouponController::class, 'destroy'])->name('admin.coupons.destroy');
    });

    // Nhóm 11: Trạng thái sức khỏe hệ thống (System Status)
    Route::middleware(['permission:view_system_status'])->group(function () {
        Route::get('/system-status', [\App\Http\Controllers\Admin\SystemStatusController::class, 'index'])->name('admin.system_status');
        Route::post('/system-status/clear-cache', [\App\Http\Controllers\Admin\SystemStatusController::class, 'clearCache'])->name('admin.system_status.clear_cache');
        Route::post('/system-status/optimize', [\App\Http\Controllers\Admin\SystemStatusController::class, 'optimize'])->name('admin.system_status.optimize');
    });

    // Nhóm 11.5: Quản lý Email Campaign (Email Marketing)
    Route::middleware(['permission:manage_email_campaigns'])->prefix('email-campaigns')->group(function () {
        // Routes tĩnh phải khai báo TRƯỚC route wildcard /{emailCampaign} để tránh conflict
        Route::get('/', [\App\Http\Controllers\Admin\EmailCampaignController::class, 'index'])->name('admin.email_campaigns.index');
        Route::get('/create', [\App\Http\Controllers\Admin\EmailCampaignController::class, 'create'])->name('admin.email_campaigns.create');
        Route::post('/', [\App\Http\Controllers\Admin\EmailCampaignController::class, 'store'])->name('admin.email_campaigns.store');
        // API AJAX: khai báo TRƯỚC wildcard để /api/* không bị hiểu là campaign ID
        Route::get('/api/count-recipients', [\App\Http\Controllers\Admin\EmailCampaignController::class, 'countRecipients'])->name('admin.email_campaigns.count_recipients');
        Route::post('/api/preview', [\App\Http\Controllers\Admin\EmailCampaignController::class, 'preview'])->name('admin.email_campaigns.preview');
        // API AJAX: tạo nhanh nội dung email bằng AI
        Route::post('/api/generate-ai', [\App\Http\Controllers\Admin\EmailCampaignController::class, 'generateAi'])->name('admin.email_campaigns.generate_ai');
        // Routes có tham số campaign (wildcard)
        Route::get('/{emailCampaign}', [\App\Http\Controllers\Admin\EmailCampaignController::class, 'show'])->name('admin.email_campaigns.show');
        Route::get('/{emailCampaign}/queue-logs', [\App\Http\Controllers\Admin\EmailCampaignController::class, 'queueLogs'])->name('admin.email_campaigns.queue_logs');
        Route::get('/{emailCampaign}/edit', [\App\Http\Controllers\Admin\EmailCampaignController::class, 'edit'])->name('admin.email_campaigns.edit');
        Route::put('/{emailCampaign}', [\App\Http\Controllers\Admin\EmailCampaignController::class, 'update'])->name('admin.email_campaigns.update');
        Route::delete('/{emailCampaign}', [\App\Http\Controllers\Admin\EmailCampaignController::class, 'destroy'])->name('admin.email_campaigns.destroy');
        Route::post('/{emailCampaign}/send', [\App\Http\Controllers\Admin\EmailCampaignController::class, 'send'])->name('admin.email_campaigns.send');
        Route::post('/{emailCampaign}/cancel', [\App\Http\Controllers\Admin\EmailCampaignController::class, 'cancel'])->name('admin.email_campaigns.cancel');
        Route::post('/{emailCampaign}/duplicate', [\App\Http\Controllers\Admin\EmailCampaignController::class, 'duplicate'])->name('admin.email_campaigns.duplicate');
        Route::post('/{emailCampaign}/refresh-stats', [\App\Http\Controllers\Admin\EmailCampaignController::class, 'refreshStats'])->name('admin.email_campaigns.refresh_stats');
    });

    // Nhóm 12: Dọn dẹp & Sao lưu hệ thống (System Cleanup & Database Backup)
    Route::middleware(['permission:manage_system_cleanup'])->group(function () {
        Route::get('/system-cleanup', [\App\Http\Controllers\Admin\SystemCleanupController::class, 'index'])->name('admin.system_cleanup.index');
        Route::post('/system-cleanup/cleanup', [\App\Http\Controllers\Admin\SystemCleanupController::class, 'cleanup'])->name('admin.system_cleanup.cleanup');
        Route::post('/system-cleanup/backup', [\App\Http\Controllers\Admin\SystemCleanupController::class, 'backupStore'])->name('admin.system_cleanup.backup.store');
        Route::get('/system-cleanup/backup/{filename}/download', [\App\Http\Controllers\Admin\SystemCleanupController::class, 'backupDownload'])->name('admin.system_cleanup.backup.download');
        Route::delete('/system-cleanup/backup/{filename}', [\App\Http\Controllers\Admin\SystemCleanupController::class, 'backupDestroy'])->name('admin.system_cleanup.backup.destroy');
    });

    // Nhóm 13: Quản lý Bot (Zalo Bot & Telegram Bot)
    Route::middleware(['permission:manage_bots'])->prefix('bots')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\BotController::class, 'index'])->name('admin.bots.index');
        Route::post('/zalo/save', [\App\Http\Controllers\Admin\BotController::class, 'saveZaloConfig'])->name('admin.bots.zalo.save');
        Route::post('/telegram/save', [\App\Http\Controllers\Admin\BotController::class, 'saveTelegramConfig'])->name('admin.bots.telegram.save');
        Route::post('/zalo/set-webhook', [\App\Http\Controllers\Admin\BotController::class, 'setZaloWebhook'])->name('admin.bots.zalo.set_webhook');
        Route::post('/telegram/set-webhook', [\App\Http\Controllers\Admin\BotController::class, 'setTelegramWebhook'])->name('admin.bots.telegram.set_webhook');
        Route::post('/zalo/test', [\App\Http\Controllers\Admin\BotController::class, 'testZaloBot'])->name('admin.bots.zalo.test');
        Route::post('/telegram/test', [\App\Http\Controllers\Admin\BotController::class, 'testTelegramBot'])->name('admin.bots.telegram.test');
        Route::post('/clear-messages', [\App\Http\Controllers\Admin\BotController::class, 'clearMessages'])->name('admin.bots.clear_messages');
    });
});
