<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Setting;

/**
 * Migration tự động khởi tạo các cấu hình giao diện trang chủ (Homepage Settings) vào database.
 * 
 * Tại sao cần migration này:
 * Trước đây, các cấu hình giao diện trang chủ (như hp_notice_modal_content - nội dung lưu ý khi sử dụng) 
 * chỉ được tự động khởi tạo khi admin truy cập trực tiếp vào trang quản trị giao diện (AppearanceController@index). 
 * Nếu hệ thống được cập nhật (update) nhưng admin chưa từng vào trang quản trị đó, các cấu hình này sẽ bị trống 
 * ngoài trang chủ hoặc gây lỗi hiển thị. Migration này giúp tự động ghi sẵn các giá trị cấu hình mặc định vào database 
 * ngay khi chạy lệnh cập nhật database (php artisan migrate) mà không cần đợi admin nhấn lưu.
 */
return new class extends Migration
{
    /**
     * Thực hiện cập nhật dữ liệu cấu hình vào database.
     */
    public function up(): void
    {
        // Định nghĩa mảng dữ liệu cấu hình giao diện mặc định
        $defaultSettings = [
            'hp_hero_layout' => 'classic',
            'hp_hero_badge' => 'Hoàn tiền mua sắm Shopee lên đến {cashback_rate}% hoa hồng',
            'hp_hero_title_1' => 'Mua Sắm Shopee',
            'hp_hero_title_2' => 'Nhận Lại Tiền Hoàn',
            'hp_hero_title_3' => 'Dễ Dàng',
            'hp_hero_description' => 'Dán link sản phẩm bất kỳ từ Shopee để lấy mã giảm giá, kiểm tra số tiền hoàn lại dự kiến và nhận tiền hoàn trực tiếp vào ví sau khi mua hàng thành công.',
            'hp_hero_placeholder' => 'Dán link sản phẩm Shopee tại đây...',
            'hp_hero_btn_text' => 'Lấy Link Hoàn Tiền',
            
            'hp_steps_title' => 'Cách Nhận Hoàn Tiền Shopee Trong 3 Bước',
            'hp_steps_subtitle' => 'Chỉ mất chưa đầy 1 phút để tối ưu hóa chi phí mua sắm của bạn trên sàn Shopee và bắt đầu tích lũy hoa hồng thụ động.',
            
            'hp_step_1_title' => 'Sao chép link sản phẩm',
            'hp_step_1_icon' => 'copy',
            'hp_step_1_desc' => 'Mở ứng dụng hoặc trang web Shopee, tìm sản phẩm bạn yêu thích rồi sao chép đường dẫn (link) sản phẩm đó.',
            
            'hp_step_2_title' => 'Dán link & Lấy link hoàn tiền',
            'hp_step_2_icon' => 'search',
            'hp_step_2_desc' => 'Dán link vừa copy vào ô tìm kiếm ở trên đầu trang này để hệ thống phân tích và tạo liên kết rút gọn hoàn tiền của riêng bạn.',
            
            'hp_step_3_title' => 'Mua hàng và nhận tiền hoàn',
            'hp_step_3_icon' => 'check-square',
            'hp_step_3_desc' => 'Nhấn vào liên kết rút gọn để đi tới Shopee mua hàng như bình thường. Tiền hoàn sẽ tự động cộng vào ví sau khi giao dịch thành công.',
            
            'hp_features_title' => 'Tại Sao Nên Chọn Nền Tảng Của Chúng Tôi?',
            'hp_features_subtitle' => 'Mang lại trải nghiệm mua sắm tiết kiệm thông minh nhất cùng nhiều chính sách hấp dẫn bậc nhất thị trường.',
            
            'hp_feature_1_title' => 'Tỷ lệ hoàn tiền cao',
            'hp_feature_1_icon' => 'percent',
            'hp_feature_1_color' => 'orange',
            'hp_feature_1_desc' => 'Nhận lại lên đến 70% tổng số tiền hoa hồng mà Shopee chi trả cho mỗi đơn hàng tiếp thị liên kết thành công.',
            
            'hp_feature_2_title' => 'Hệ thống 2 tầng MLM',
            'hp_feature_2_icon' => 'git-branch',
            'hp_feature_2_color' => 'green',
            'hp_feature_2_desc' => 'Giới thiệu bạn bè đăng ký và nhận thêm 5% từ F1 cùng 2% từ F2 trên mỗi đơn hoàn tiền của họ, tạo nguồn thu nhập trọn đời.',
            
            'hp_feature_3_title' => 'Thanh toán linh hoạt',
            'hp_feature_3_icon' => 'banknote',
            'hp_feature_3_color' => 'blue',
            'hp_feature_3_desc' => 'Hỗ trợ rút tiền tự động qua mã QR Ngân hàng (VietQR) hoặc ví điện tử MoMo với hạn mức tối thiểu cực thấp chỉ từ 50,000đ.',
            
            'hp_feature_4_title' => 'Ghi nhận đơn tự động',
            'hp_feature_4_icon' => 'history',
            'hp_feature_4_color' => 'purple',
            'hp_feature_4_desc' => 'Các đơn hàng Shopee sẽ tự động đồng bộ thời gian thực thông qua API và được hiển thị ngay lập tức trong bảng lịch sử ví.',
            
            'hp_stats_title' => 'Thống kê hệ thống thực tế',
            'hp_stats_clicks_label' => 'Lượt nhấp hoàn tiền',
            'hp_stats_clicks_value' => '48200',
            'hp_stats_users_label' => 'Thành viên hoạt động',
            'hp_stats_users_value' => '1450',
            'hp_stats_paid_label' => 'Tổng hoa hồng đã chi trả',
            'hp_stats_paid_value' => '125000000',

            'hp_show_demo_modal' => '1',
            'hp_show_steps' => '1',
            'hp_show_features' => '1',
            'hp_show_blog' => '1',
            'hp_show_timeline' => '1',
            'hp_hero_right_type' => 'slider',
            'hp_hero_right_video_url' => '',
            'hp_hero_right_video_poster' => '',
            'hp_demo_modal_type' => 'interactive',
            'hp_demo_modal_custom_content' => '',
            'hp_show_notice_modal' => '1',
            
            // Nội dung HTML lưu ý sử dụng hiển thị trong Popup hướng dẫn/lưu ý mua hàng
            'hp_notice_modal_content' => '<div class="space-y-4">
    <div class="flex gap-3">
        <div class="w-6 h-6 rounded-full bg-orange-100 text-shopee font-bold text-xs flex items-center justify-center shrink-0">1</div>
        <div>
            <h4 class="font-bold text-gray-800 text-xs md:text-sm">Tạo giỏ hàng trống trước khi mua</h4>
            <p class="text-xs text-gray-400 mt-0.5">Hãy đảm bảo giỏ hàng trên App Shopee/TikTok của bạn chưa có sản phẩm đó. Sau khi click qua link hoàn tiền, bạn mới thêm sản phẩm vào giỏ và thanh toán.</p>
        </div>
    </div>

    <div class="flex gap-3">
        <div class="w-6 h-6 rounded-full bg-orange-100 text-shopee font-bold text-xs flex items-center justify-center shrink-0">2</div>
        <div>
            <h4 class="font-bold text-gray-800 text-xs md:text-sm">Không bấm vào link chia sẻ khác</h4>
            <p class="text-xs text-gray-400 mt-0.5">Từ lúc click link hoàn tiền đến khi thanh toán xong, tuyệt đối không click vào bất kỳ link chia sẻ nào khác (Facebook, Telegram, Youtube của các KOLs hoặc trang web khác) để tránh bị ghi đè mã giới thiệu.</p>
        </div>
    </div>

    <div class="flex gap-3">
        <div class="w-6 h-6 rounded-full bg-orange-100 text-shopee font-bold text-xs flex items-center justify-center shrink-0">3</div>
        <div>
            <h4 class="font-bold text-gray-800 text-xs md:text-sm">Hoàn thành mua hàng nhanh chóng</h4>
            <p class="text-xs text-gray-400 mt-0.5">Nên thanh toán đơn hàng trong vòng 20 - 30 phút sau khi chuyển hướng sang Shopee/TikTok Shop để đảm bảo cookies theo dõi còn hiệu lực.</p>
        </div>
    </div>

    <div class="flex gap-3">
        <div class="w-6 h-6 rounded-full bg-orange-100 text-shopee font-bold text-xs flex items-center justify-center shrink-0">4</div>
        <div>
            <h4 class="font-bold text-gray-800 text-xs md:text-sm">Tắt trình chặn quảng cáo (Adblock)</h4>
            <p class="text-xs text-gray-400 mt-0.5">Các ứng dụng hoặc extension chặn quảng cáo có thể chặn mã theo dõi đơn hàng của hệ thống liên kết, khiến đơn hàng không được ghi nhận hoàn tiền.</p>
        </div>
    </div>

    <div class="flex gap-3">
        <div class="w-6 h-6 rounded-full bg-orange-100 text-shopee font-bold text-xs flex items-center justify-center shrink-0">5</div>
        <div>
            <h4 class="font-bold text-gray-800 text-xs md:text-sm">Không hủy đơn rồi đặt lại trực tiếp</h4>
            <p class="text-xs text-gray-400 mt-0.5">Nếu bạn hủy đơn hàng và muốn đặt lại, bạn phải quay lại trang web của chúng tôi để lấy link hoàn tiền mới và tiến hành click lại từ đầu.</p>
        </div>
    </div>
</div>',

            'hp_timeline_badge' => 'Lộ trình hoàn tiền Shopee',
            'hp_timeline_title' => 'Quy Trình Nhận Hoàn Tiền Siêu Tốc',
            'hp_timeline_subtitle' => 'Hiểu rõ quy trình ghi nhận đơn hàng và thời gian tiền hoàn về tài khoản của bạn.',
            
            'hp_timeline_step_1_badge' => 'Bước 1: Mua hàng',
            'hp_timeline_step_1_title' => 'Ngày mua',
            'hp_timeline_step_1_tag' => 'Hôm nay',
            'hp_timeline_step_1_desc' => 'Bạn copy link Shopee dán vào hệ thống, nhận link rút gọn và tiến hành đặt mua hàng.',
            
            'hp_timeline_step_2_badge' => 'Bước 2: Đối soát',
            'hp_timeline_step_2_title' => 'Ghi nhận',
            'hp_timeline_step_2_tag' => 'Ngày mai',
            'hp_timeline_step_2_desc' => 'Shopee ghi nhận đơn hàng tạm tính và tự động đồng bộ hiển thị trong lịch sử ví của bạn.',
            
            'hp_timeline_step_3_badge' => 'Bước 3: Thực nhận',
            'hp_timeline_step_3_title' => 'Có thể rút',
            'hp_timeline_step_3_tag' => '7 ngày',
            'hp_timeline_step_3_desc' => 'Sau khi Shopee đối soát kỳ hoàn thành (khoảng 7 ngày khi nhận hàng), tiền khả dụng sẽ được cộng vào ví và có thể rút ngay.',
            'hp_sections_order' => '["hp_show_timeline","hp_show_steps","hp_show_features","hp_show_coupons","hp_show_blog"]',
            'hp_enable_bubble_effect' => '1',
        ];

        // Lặp qua mảng cấu hình mặc định để ghi nhận vào database
        foreach ($defaultSettings as $key => $value) {
            $setting = Setting::where('key', $key)->first();
            // Nếu chưa tồn tại cấu hình, hoặc cấu hình đang bị rỗng (ví dụ do lỗi khởi tạo)
            if (!$setting || empty(trim($setting->value))) {
                Setting::setVal($key, $value, 'Cấu hình giao diện trang chủ: ' . $key);
            }
        }
    }

    /**
     * Hủy bỏ thay đổi khi rollback migration.
     */
    public function down(): void
    {
        // Không xóa dữ liệu trong down() để tránh mất cấu hình hiện tại của hệ thống.
    }
};
