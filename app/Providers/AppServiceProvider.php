<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     * Đăng ký các dịch vụ và repositories vào Service Container của Laravel.
     */
    public function register(): void
    {
        // Nạp file helpers định nghĩa các hàm tiện ích toàn cục (bao gồm helper AI)
        require_once app_path('Helpers/helpers.php');

        $this->app->bind(
            \App\Repositories\Contracts\ProductRepositoryInterface::class,
            \App\Repositories\Eloquent\ProductRepository::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Cấu hình độ dài chuỗi mặc định của các cột Schema (Khắc phục lỗi index unique dài quá 1000 bytes trên một số hosting cPanel dùng MySQL/MariaDB cũ)
        \Illuminate\Support\Facades\Schema::defaultStringLength(191);

        // Nếu chưa cài đặt hệ thống (chưa có installed.lock), bỏ qua việc đọc cấu hình từ Database để tránh lỗi 500 khi boot
        if (!file_exists(storage_path('installed.lock'))) {
            view()->composer('*', function ($view) {
                $view->with('siteName', request()->getHost() ?: 'Hoantienshopee.vn');
                $view->with('siteLogo', '');
                $view->with('siteLogoDark', '');
                $view->with('siteFavicon', '');
                $view->with('siteOgImage', '');
                $view->with('siteDescription', 'Website hoàn tiền mua sắm shopee tự động');
                $view->with('blogEnabled', '1');
                $view->with('themeColor', '#ee4d2d');
                $view->with('themeColorLight', '#ff7337');
                $view->with('themeColorDark', '#d63017');
                $view->with('themeColorBg', '#fff5f1');
                $view->with('googleAnalyticsId', '');
            });
            return;
        }

        try {
            // 1. Cấu hình động SMTP gửi email từ Database nếu bảng settings tồn tại
            if (\Illuminate\Support\Facades\Schema::hasTable('settings')) {
                // Cấu hình động Chế độ Debug hệ thống (APP_DEBUG) từ Database
                $appDebug = \App\Models\Setting::getVal('app_debug');
                if ($appDebug !== null && $appDebug !== '') {
                    config(['app.debug' => $appDebug === '1' || $appDebug === true || $appDebug === 'true']);
                }

                // Cấu hình động Timezone từ Database
                $appTimezone = \App\Models\Setting::getVal('app_timezone');
                if (!empty($appTimezone)) {
                    config(['app.timezone' => $appTimezone]);
                    date_default_timezone_set($appTimezone);
                }

                $smtpHost = \App\Models\Setting::getVal('smtp_host');
                if (!empty($smtpHost)) {
                    $smtpPort = \App\Models\Setting::getVal('smtp_port', '587');
                    $smtpUser = \App\Models\Setting::getVal('smtp_username');
                    $smtpPass = \App\Models\Setting::getVal('smtp_password');
                    $smtpEnc = \App\Models\Setting::getVal('smtp_encryption', 'tls');
                    $smtpFromName = \App\Models\Setting::getVal('smtp_from_name', 'Hoàn Tiền Shopee');
                    $smtpFromAddress = \App\Models\Setting::getVal('smtp_from_address');

                    // Nếu không cấu hình địa chỉ email gửi, thử dùng tên đăng nhập (nếu là email) hoặc dùng mặc định hệ thống
                    if (empty($smtpFromAddress)) {
                        $smtpFromAddress = filter_var($smtpUser, FILTER_VALIDATE_EMAIL) ? $smtpUser : config('mail.from.address');
                    }

                    config([
                        'mail.default' => 'smtp',
                        'mail.mailers.smtp.host' => $smtpHost,
                        'mail.mailers.smtp.port' => (int) $smtpPort,
                        'mail.mailers.smtp.encryption' => $smtpEnc === 'none' ? null : $smtpEnc,
                        'mail.mailers.smtp.username' => $smtpUser,
                        'mail.mailers.smtp.password' => $smtpPass,
                        'mail.from.address' => $smtpFromAddress,
                        'mail.from.name' => $smtpFromName,
                    ]);
                }

                // 1b. Cấu hình động Google OAuth Socialite từ Database nếu bảng settings tồn tại
                $googleLoginEnabled = \App\Models\Setting::getVal('google_login_enabled', '0');
                if ($googleLoginEnabled === '1') {
                    $googleClientId = \App\Models\Setting::getVal('google_client_id');
                    $googleClientSecret = \App\Models\Setting::getVal('google_client_secret');
                    // Tự động sinh URL callback Google
                    $googleRedirect = url('auth/google/callback');

                    config([
                        'services.google' => [
                            'client_id' => $googleClientId,
                            'client_secret' => $googleClientSecret,
                            'redirect' => $googleRedirect,
                        ]
                    ]);
                }
            }

            // Tự động chia sẻ siteName, siteLogo, siteFavicon, siteOgImage và siteDescription cho tất cả các view để đồng bộ thương hiệu
            view()->composer('*', function ($view) {
                if (\Illuminate\Support\Facades\Schema::hasTable('settings')) {
                    $siteName = \App\Models\Setting::getVal('site_name', request()->getHost() ?: 'Hoantienshopee.vn');
                    $siteLogo = \App\Models\Setting::getVal('site_logo', '');
                    $siteLogoDark = \App\Models\Setting::getVal('site_logo_dark', '');
                    $siteFavicon = \App\Models\Setting::getVal('site_favicon', '');
                    $siteOgImage = \App\Models\Setting::getVal('site_og_image', '');
                    $siteDescription = \App\Models\Setting::getVal('site_description', 'Website hoàn tiền mua sắm shopee tự động');
                    $blogEnabled = \App\Models\Setting::getVal('blog_enabled', '1');
                    $themeColor = \App\Models\Setting::getVal('theme_color', '#ee4d2d');
                    $themeColorLight = \App\Models\Setting::adjustBrightness($themeColor, 0.15);
                    $themeColorDark = \App\Models\Setting::adjustBrightness($themeColor, -0.15);
                    $themeColorBg = \App\Models\Setting::adjustBrightness($themeColor, 0.95);
                    $googleAnalyticsId = \App\Models\Setting::getVal('google_analytics_id', '');
                    
                    $view->with('siteName', $siteName);
                    $view->with('siteLogo', $siteLogo);
                    $view->with('siteLogoDark', $siteLogoDark);
                    $view->with('siteFavicon', $siteFavicon);
                    $view->with('siteOgImage', $siteOgImage);
                    $view->with('siteDescription', $siteDescription);
                    $view->with('blogEnabled', $blogEnabled);
                    $view->with('themeColor', $themeColor);
                    $view->with('themeColorLight', $themeColorLight);
                    $view->with('themeColorDark', $themeColorDark);
                    $view->with('themeColorBg', $themeColorBg);
                    $view->with('googleAnalyticsId', $googleAnalyticsId);
                } else {
                    $view->with('siteName', request()->getHost() ?: 'Hoantienshopee.vn');
                    $view->with('siteLogo', '');
                    $view->with('siteLogoDark', '');
                    $view->with('siteFavicon', '');
                    $view->with('siteOgImage', '');
                    $view->with('siteDescription', 'Website hoàn tiền mua sắm shopee tự động');
                    $view->with('blogEnabled', '1');
                    $view->with('themeColor', '#ee4d2d');
                    $view->with('themeColorLight', '#ff7337');
                    $view->with('themeColorDark', '#d63017');
                    $view->with('themeColorBg', '#fff5f1');
                    $view->with('googleAnalyticsId', '');
                }

                // Chia sẻ danh sách ngôn ngữ hoạt động và ngôn ngữ hiện tại cho toàn bộ giao diện
                if (\Illuminate\Support\Facades\Schema::hasTable('languages')) {
                    // Cache danh sách ngôn ngữ hoạt động trong 60 phút để tối ưu hóa hiệu suất
                    $activeLanguages = \Illuminate\Support\Facades\Cache::remember('active_languages_list', 3600, function () {
                        return \App\Models\Language::where('is_active', true)->orderBy('order', 'asc')->get();
                    });
                    
                    $currentLocale = \Illuminate\Support\Facades\App::getLocale();
                    $currentLanguage = $activeLanguages->firstWhere('code', $currentLocale) 
                         ?? $activeLanguages->firstWhere('is_default', true) 
                         ?? $activeLanguages->first();

                    $view->with('activeLanguages', $activeLanguages);
                    $view->with('currentLocale', $currentLocale);
                    $view->with('currentLanguage', $currentLanguage);
                }

                // Chia sẻ danh sách tiền tệ hoạt động và tiền tệ hiện tại cho toàn bộ giao diện
                if (\Illuminate\Support\Facades\Schema::hasTable('currencies')) {
                    // Cache danh sách tiền tệ hoạt động trong 60 phút để tối ưu hóa hiệu suất
                    $activeCurrencies = \Illuminate\Support\Facades\Cache::remember('active_currencies_list', 3600, function () {
                        return \App\Models\Currency::where('is_active', true)->get();
                    });
                    
                    $currentCurrency = \App\Helpers\CurrencyHelper::getCurrentCurrency();

                    $view->with('activeCurrencies', $activeCurrencies);
                    $view->with('currentCurrency', $currentCurrency);
                }

                // Chia sẻ danh sách Menu tùy biến động từ database cho toàn bộ giao diện người dùng
                if (\Illuminate\Support\Facades\Schema::hasTable('menus')) {
                    // Lấy tất cả menu được bật (status = true), sắp xếp theo thứ tự hiển thị
                    // Chỉ lấy menu cấp cha cao nhất (parent_id = null) ở ngoài cùng, menu con sẽ duyệt qua quan hệ children
                    $globalMenus = \App\Models\Menu::with('children')
                        ->where('status', true)
                        ->whereNull('parent_id')
                        ->orderBy('order', 'asc')
                        ->get();
                    
                    // Nhóm các menu cha theo vị trí hiển thị (ví dụ: header, footer, ...) để view lấy ra dùng theo vị trí mong muốn
                    $groupedMenus = $globalMenus->groupBy('position');
                    
                    $view->with('groupedMenus', $groupedMenus);
                }
            });
        } catch (\Throwable $e) {
            // Nếu có lỗi kết nối DB hoặc bất kỳ lỗi gì khi boot, fallback chia sẻ các biến view mặc định để tránh crash lỗi 500
            view()->composer('*', function ($view) {
                $view->with('siteName', request()->getHost() ?: 'Hoantienshopee.vn');
                $view->with('siteLogo', '');
                $view->with('siteLogoDark', '');
                $view->with('siteFavicon', '');
                $view->with('siteOgImage', '');
                $view->with('siteDescription', 'Website hoàn tiền mua sắm shopee tự động');
                $view->with('blogEnabled', '1');
                $view->with('themeColor', '#ee4d2d');
                $view->with('themeColorLight', '#ff7337');
                $view->with('themeColorDark', '#d63017');
                $view->with('themeColorBg', '#fff5f1');
                $view->with('googleAnalyticsId', '');
            });
        }
    }
}
