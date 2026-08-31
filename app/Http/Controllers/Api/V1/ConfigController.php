<?php

namespace App\Http\Controllers\Api\V1;

use App\Helpers\MoneyHelper;
use App\Models\Banner;
use App\Models\Setting;
use App\Services\AppleOAuthConfiguration;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * API Cấu hình khởi động ứng dụng (public, không cần token).
 * App gọi một lần khi mở để lấy nhận diện thương hiệu, tỉ lệ hoàn tiền, trạng thái các sàn,
 * cấu hình rút tiền và cờ bật/tắt tính năng nhằm dựng giao diện chính xác.
 */
class ConfigController extends ApiController
{
    /**
     * GET /api/v1/openapi/config
     */
    public function show(Request $request): JsonResponse
    {
        $withdrawalFeeType = Setting::getVal('withdrawal_fee_type', 'percentage');
        $withdrawalFeeValue = Setting::getVal('withdrawal_fee_value', 0);
        $appleOAuthReady = app(AppleOAuthConfiguration::class)->isReady();
        $payoutFeaturesEnabled = $this->iosPayoutFeaturesEnabled($request);

        // Danh sách banner đang hiển thị ở trang chủ (giống logic web)
        $banners = Banner::where('is_active', true)
            ->orderBy('order')
            ->get()
            ->map(fn ($b) => [
                'image_url' => $b->image_url,
                'link' => $b->link,
                'title' => $b->title,
            ]);

        return $this->ok([
            'site' => [
                'name' => $payoutFeaturesEnabled ? Setting::getVal('site_name', 'Hoàn Tiền Shopee') : 'Mê Sale',
                'description' => $payoutFeaturesEnabled ? Setting::getVal('site_description', '') : 'Khám phá sản phẩm và ưu đãi mua sắm',
                'logo' => Setting::getVal('site_logo', ''),
                'logo_dark' => Setting::getVal('site_logo_dark', ''),
                'favicon' => Setting::getVal('site_favicon', ''),
                'og_image' => Setting::getVal('site_og_image', ''),
                'version' => Setting::getVal('current_version', 'v1.0.0'),
            ],
            'theme' => [
                'color' => Setting::getVal('theme_color', '#ee4d2d'),
                'color_light' => Setting::getVal('theme_color_light', '#ff7337'),
                'color_dark' => Setting::getVal('theme_color_dark', '#d73211'),
                'color_bg' => Setting::getVal('theme_color_bg', '#fff5f2'),
            ],
            ...($payoutFeaturesEnabled ? [
                'cashback' => [
                    'shopee_enabled' => Setting::getVal('shopee_status', '1') === '1',
                    'shopee_rate' => (float) Setting::getVal('shopee_cashback_rate', 0),
                    'shopee_notice' => Setting::getVal('hp_cashback_notice', ''),
                    'tiktok_enabled' => Setting::getVal('tiktok_status', '1') === '1',
                    'tiktok_rate' => (float) Setting::getVal('tiktok_cashback_rate', 0),
                    'tiktok_notice' => Setting::getVal('hp_cashback_notice_tiktok', ''),
                    'lazada_enabled' => Setting::getVal('lazada_status', '0') === '1',
                    'lazada_rate' => (float) Setting::getVal('lazada_cashback_rate', 0),
                    'lazada_notice' => Setting::getVal('hp_cashback_notice_lazada', ''),
                ],
                'withdraw' => [
                    'enabled' => Setting::getVal('withdrawal_enabled', '1') === '1',
                    'min_amount' => (int) MoneyHelper::round(Setting::getVal('min_withdraw', 50000)),
                    'fee_type' => $withdrawalFeeType,
                    'fee_value' => $withdrawalFeeType === 'percentage'
                        ? (float) $withdrawalFeeValue
                        : (int) MoneyHelper::round($withdrawalFeeValue),
                    'fee_value_unit' => $withdrawalFeeType === 'percentage' ? 'percent' : 'vnd',
                    'otp_required' => Setting::getVal('withdraw_otp_required', '0') === '1',
                    'bank_enabled' => Setting::getVal('withdraw_bank_enabled', '1') === '1',
                    'wallet_enabled' => Setting::getVal('withdraw_wallet_enabled', '1') === '1',
                    'allowed_banks' => array_values(array_filter(array_map('trim', explode(',', Setting::getVal('allowed_banks', 'Vietcombank,Techcombank,MB Bank,ACB,BIDV,Vietinbank,Agribank,TPBank,VPBank'))))),
                    'allowed_wallets' => array_values(array_filter(array_map('trim', explode(',', Setting::getVal('allowed_wallets', 'Momo,ZaloPay,ShopeePay'))))),
                ],
                'referral' => [
                    'enabled' => Setting::getVal('referral_enabled', '1') === '1',
                    'f1_rate' => (float) Setting::getVal('referral_f1_rate', 5),
                    'f2_enabled' => Setting::getVal('referral_f2_enabled', '1') === '1',
                    'f2_rate' => (float) Setting::getVal('referral_f2_rate', 2),
                ],
            ] : [
                'marketplaces' => [
                    'shopee_enabled' => Setting::getVal('shopee_status', '1') === '1',
                    'tiktok_enabled' => Setting::getVal('tiktok_status', '1') === '1',
                    'lazada_enabled' => Setting::getVal('lazada_status', '0') === '1',
                ],
            ]),
            'features' => [
                // Cờ đăng ký / xác minh để App biết trước luồng cần xử lý
                'registration_enabled' => Setting::getVal('registration_enabled', '1') === '1',
                'email_verification_required' => Setting::getVal('email_verification_enabled', '0') === '1',
                'self_delete_enabled' => Setting::getVal('allow_self_delete_account', '0') === '1',
                'ios_payout_features_enabled' => $payoutFeaturesEnabled,
                'checkin_enabled' => $payoutFeaturesEnabled && Setting::getVal('daily_checkin_enabled', '1') === '1',
                'gift_code_enabled' => $payoutFeaturesEnabled && Setting::getVal('gift_code_enabled', '1') === '1',
                'gift_redemption_enabled' => $payoutFeaturesEnabled && Setting::getVal('gift_redemption_enabled', '0') === '1',
                'coupon_enabled' => Setting::getVal('coupon_status', '1') === '1',
                'ranking_enabled' => $payoutFeaturesEnabled && Setting::getVal('ranking_status', '1') === '1',
                'tasks_enabled' => $payoutFeaturesEnabled && Setting::getVal('tasks_enabled', '0') === '1',
                // Cờ bật/tắt từng nhóm Open API để App ẩn tính năng chưa mở
                'api_auth' => Setting::getVal('openapi_auth_status', '1') === '1',
                'api_auth_oauth_google' => Setting::getVal('openapi_auth_oauth_google_status', '0') === '1',
                'api_auth_oauth_apple' => Setting::getVal('openapi_auth_oauth_apple_status', '0') === '1'
                    && $appleOAuthReady,
                ...($payoutFeaturesEnabled
                    ? ['api_cashback_link' => Setting::getVal('openapi_cashback_link_status', '1') === '1']
                    : ['api_product_link' => Setting::getVal('openapi_cashback_link_status', '1') === '1']),
                'api_orders' => $payoutFeaturesEnabled && Setting::getVal('openapi_orders_status', '1') === '1',
                'api_withdraw' => $payoutFeaturesEnabled && Setting::getVal('openapi_withdraw_status', '1') === '1',
                'api_notifications' => $payoutFeaturesEnabled && Setting::getVal('openapi_notifications_status', '1') === '1',
                'api_tasks' => $payoutFeaturesEnabled && Setting::getVal('openapi_tasks_status', '1') === '1',
                'api_checkin' => $payoutFeaturesEnabled && Setting::getVal('openapi_checkin_status', '1') === '1',
                'api_referrals' => $payoutFeaturesEnabled && Setting::getVal('openapi_referrals_status', '1') === '1',
                'api_balance_logs' => $payoutFeaturesEnabled && Setting::getVal('openapi_balance_logs_status', '1') === '1',
                'api_activity_logs' => Setting::getVal('openapi_activity_logs_status', '1') === '1',
                'api_gifts' => $payoutFeaturesEnabled && Setting::getVal('openapi_gifts_status', '1') === '1',
                'api_giftcode' => $payoutFeaturesEnabled && Setting::getVal('openapi_giftcode_status', '1') === '1',
                'api_saved_products' => Setting::getVal('openapi_saved_products_status', '1') === '1',
                'api_coupons' => Setting::getVal('openapi_coupons_status', '1') === '1',
                'api_ranking' => $payoutFeaturesEnabled && Setting::getVal('openapi_ranking_status', '1') === '1',
                'api_pages' => Setting::getVal('openapi_pages_status', '1') === '1',
                'api_push' => $payoutFeaturesEnabled && Setting::getVal('openapi_push_status', '1') === '1',
                'api_security' => Setting::getVal('openapi_security_status', '1') === '1',
                'api_sessions' => Setting::getVal('openapi_sessions_status', '1') === '1',
                // Push đã sẵn sàng gửi (admin đã dán Service Account FCM) hay chưa
                'push_configured' => trim((string) Setting::getVal('fcm_service_account', '')) !== '',
            ],
            // Thông tin kiểm tra & ép cập nhật phiên bản App Mobile
            'app_update' => [
                // Phiên bản App mới nhất hiện có trên store
                'latest_version' => Setting::getVal('mobile_latest_version', ''),
                // Phiên bản tối thiểu còn được hỗ trợ; App thấp hơn mức này phải cập nhật mới dùng được
                'min_supported_version' => Setting::getVal('mobile_min_version', ''),
                // Bắt buộc cập nhật (chặn sử dụng cho tới khi update)
                'force_update' => Setting::getVal('mobile_force_update', '0') === '1',
                'android_store_url' => Setting::getVal('mobile_android_store_url', ''),
                'ios_store_url' => Setting::getVal('mobile_ios_store_url', ''),
                'update_message' => Setting::getVal('mobile_update_message', ''),
            ],
            'banners' => $banners,
        ]);
    }

}
