<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * SystemInitSeeder - Khởi tạo dữ liệu hệ thống sạch khi cài đặt website (không chứa dữ liệu test rác).
 */
class SystemInitSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Tạo tài khoản Admin tạm thời (Sẽ được Installer cập nhật lại thông tin thật ở bước sau)
        User::forceCreate([
            'name' => 'Administrator',
            'email' => 'admin@temporary.com',
            'phone' => '0000000000',
            'password' => bcrypt(Str::random(16)),
            'role' => 'admin',
            'status' => 'active',
            'referral_code' => 'ADMIN',
            'email_verified_at' => now(),
        ]);

        // 2. Tạo cấu hình hệ thống mặc định (Settings)
        $settings = [
            ['key' => 'site_name', 'value' => 'Hoantienshopee', 'description' => 'Tên website chính thức'],
            ['key' => 'site_description', 'value' => 'Website hoàn tiền mua sắm Shopee tự động, nhận hoa hồng MLM 2 tầng', 'description' => 'Mô tả website chính thức'],
            ['key' => 'shopee_cashback_rate', 'value' => '50', 'description' => 'Tỷ lệ hoàn tiền cho người dùng (% trên tổng hoa hồng nhận được)'],
            ['key' => 'shopee_fake_cashback_rate', 'value' => '50', 'description' => 'Tỷ lệ hoàn tiền ảo hiển thị cho người dùng khi lấy link Shopee (%)'],
            ['key' => 'referral_f1_rate', 'value' => '5', 'description' => '% hoa hồng nhận từ người giới thiệu F1 (% trên tiền hoàn của F1)'],
            ['key' => 'referral_f2_rate', 'value' => '2', 'description' => '% hoa hồng nhận từ người giới thiệu F2 (% trên tiền hoàn của F2)'],
            ['key' => 'checkin_reward_coins', 'value' => '500', 'description' => 'Số tiền nhận được khi điểm danh hàng ngày (đơn vị: VNĐ)'],
            ['key' => 'checkin_bonus_coins', 'value' => '2000', 'description' => 'Thưởng thêm khi điểm danh liên tục 7 ngày (đơn vị: VNĐ)'],
            ['key' => 'min_withdraw', 'value' => '50000', 'description' => 'Số tiền rút tối thiểu (đơn vị: VNĐ)'],
            ['key' => 'withdraw_bank_enabled', 'value' => '1', 'description' => 'Cho phép rút tiền qua tài khoản ngân hàng (1: Bật, 0: Tắt)'],
            ['key' => 'withdraw_wallet_enabled', 'value' => '1', 'description' => 'Cho phép rút tiền qua ví điện tử (1: Bật, 0: Tắt)'],
            ['key' => 'allowed_banks', 'value' => 'Vietcombank,Techcombank,MB Bank,ACB,BIDV,Vietinbank,Agribank,TPBank,VPBank', 'description' => 'Danh sách ngân hàng được phép rút tiền (cách nhau bởi dấu phẩy)'],
            ['key' => 'withdrawal_rules', 'value' => "Vui lòng điền đúng thông tin số tài khoản và viết hoa tên chủ tài khoản không dấu. Hệ thống không chịu trách nhiệm nếu chuyển khoản sai thông tin do người dùng cung cấp.\nCác yêu cầu rút tiền được duyệt thủ công bởi Admin trong vòng 1-24h làm việc.\nTài khoản vi phạm, cố tình gian lận điểm danh hoặc tạo đơn Shopee ảo sẽ bị khoá vĩnh viễn và huỷ số dư ví.", 'description' => 'Nội dung quy định và hướng dẫn rút tiền'],
            ['key' => 'withdrawal_fee_type', 'value' => 'percentage', 'description' => 'Loại phí rút tiền (fixed: phí cố định, percentage: phí theo %)'],
            ['key' => 'withdrawal_fee_value', 'value' => '0', 'description' => 'Giá trị phí rút tiền (số tiền cố định hoặc tỷ lệ %)'],
            ['key' => 'shopee_app_id', 'value' => 'shopee_demo_id_123', 'description' => 'Shopee Affiliate App ID'],
            ['key' => 'shopee_status', 'value' => '1', 'description' => 'Trạng thái hoạt động của hoàn tiền Shopee (1: Bật, 0: Tắt)'],
            ['key' => 'tiktok_status', 'value' => '1', 'description' => 'Trạng thái hoạt động của hoàn tiền TikTok Shop (1: Bật, 0: Tắt)'],
            ['key' => 'tiktok_cashback_rate', 'value' => '50', 'description' => 'Tỷ lệ hoàn tiền cho người dùng (% trên tổng hoa hồng nhận được từ TikTok Shop)'],
            ['key' => 'tiktok_fake_cashback_rate', 'value' => '50', 'description' => 'Tỷ lệ hoàn tiền ảo hiển thị cho người dùng khi lấy link TikTok Shop (%)'],
            ['key' => 'tiktok_tax_deduct_rate', 'value' => '10', 'description' => 'Tỷ lệ trừ thuế/phí hoa hồng TikTok Shop khi hoàn tiền bằng 0'],
            ['key' => 'apitiktok_status', 'value' => '0', 'description' => 'Trạng thái hoạt động của API TikTok Shop (1: Bật, 0: Tắt)'],
            ['key' => 'apitiktok_url', 'value' => 'https://riohub.vn/api/v1', 'description' => 'Đường dẫn API TikTok Shop lấy thông tin sản phẩm'],
            ['key' => 'apitiktok_key', 'value' => '', 'description' => 'Mã API Key kết nối TikTok Shop API'],
            ['key' => 'cache_clean_estimated_hours_tiktok', 'value' => '24', 'description' => 'Thời gian xóa sản phẩm cào ước tính TikTok Shop (Giờ)'],
            ['key' => 'cache_clean_normal_days_tiktok', 'value' => '30', 'description' => 'Thời gian xóa cache thông thường TikTok Shop (Ngày)'],
            ['key' => 'tiktok_check_product_match', 'value' => '1', 'description' => 'Bắt buộc khớp tên sản phẩm TikTok Shop (1: Bật, 0: Tắt)'],
            ['key' => 'hp_cashback_notice_tiktok', 'value' => 'Tiền hoàn hiển thị là tạm tính. Số tiền thực nhận sẽ được ghi nhận theo giá trị đơn hàng sau khi trừ voucher và mã giảm giá TikTok Shop.', 'description' => 'Nội dung lưu ý hiển thị ở kết quả tìm kiếm sản phẩm TikTok Shop'],
            ['key' => 'order_code_prefix_tiktok', 'value' => 'TTS', 'description' => 'Tiền tố mã đơn hàng TikTok Shop'],
            ['key' => 'order_code_prefix_position_tiktok', 'value' => 'left', 'description' => 'Vị trí hiển thị tiền tố mã đơn TikTok Shop (left: trái, right: phải)'],
            ['key' => 'order_code_random_length_tiktok', 'value' => '10', 'description' => 'Số ký tự ngẫu nhiên trong mã đơn TikTok Shop'],
            ['key' => 'order_code_random_type_tiktok', 'value' => 'alphanumeric_upper', 'description' => 'Kiểu ký tự ngẫu nhiên trong mã đơn TikTok Shop'],
            ['key' => 'site_logo', 'value' => '', 'description' => 'Đường dẫn ảnh logo của website'],
            ['key' => 'shopee_tax_deduct_rate', 'value' => '10', 'description' => 'Tỷ lệ trừ thuế TNCN/phí hoa hồng khi hoàn tiền bằng 0'],
            ['key' => 'apishopee_url', 'value' => 'https://apishopee.cmsnt.co/api/v1/shopee/product', 'description' => 'Đường dẫn API Shopee lấy thông tin sản phẩm'],
            ['key' => 'apishopee_status', 'value' => '0', 'description' => 'Trạng thái hoạt động của API Shopee (1: Bật, 0: Tắt)'],
            ['key' => 'apilazada_product_url', 'value' => 'https://apishopee.cmsnt.co/api/v1/lazada/product', 'description' => 'Đường dẫn API Lazada lấy thông tin sản phẩm'],
            ['key' => 'apilazada_product_status', 'value' => '0', 'description' => 'Trạng thái hoạt động của API lấy thông tin sản phẩm Lazada (1: Bật, 0: Tắt)'],
            ['key' => 'apilazada_product_key', 'value' => '', 'description' => 'Mã API Key kết nối API lấy thông tin sản phẩm Lazada'],
            ['key' => 'shortlink_status', 'value' => '0', 'description' => 'Trạng thái tự động rút gọn link (1: Bật, 0: Tắt)'],
            ['key' => 'shortlink_length', 'value' => '8', 'description' => 'Độ dài của mã rút gọn (Số ký tự)'],
            ['key' => 'shortlink_domain', 'value' => '', 'description' => 'Tên miền rút gọn riêng (Custom Domain)'],
            ['key' => 'ios_shortcut_url', 'value' => '', 'description' => 'Đường dẫn chia sẻ phím tắt iCloud cho iPhone'],
            ['key' => 'ios_shortcut_status', 'value' => '0', 'description' => 'Trạng thái hoạt động của phím tắt iPhone (1: Bật, 0: Tắt)'],
            ['key' => 'ios_shortcut_desc', 'value' => 'Hướng dẫn cài đặt phím tắt trên điện thoại iPhone giúp chuyển đổi link hoàn tiền Shopee siêu nhanh trong 1 giây', 'description' => 'Mô tả ngắn trang hướng dẫn phím tắt'],
            ['key' => 'ios_shortcut_guide', 'value' => 'Không cần truy cập website! Chỉ cần sao chép link Shopee trên điện thoại và chạy Phím tắt (Shortcuts). Hệ thống sẽ tự động nhận diện tài khoản, phân tích sản phẩm và copy lại link hoàn tiền mới vào bộ nhớ tạm.', 'description' => 'Mô tả cách hoạt động của phím tắt'],
            ['key' => 'current_version', 'value' => 'v1.0.0', 'description' => 'Phiên bản hiện tại của hệ thống'],
            ['key' => 'auto_update', 'value' => '1', 'description' => 'Tự động cập nhật hệ thống khi có phiên bản mới (1: Bật, 0: Tắt)'],
            ['key' => 'coupon_redirect_on_copy', 'value' => '0', 'description' => 'Tự động mở link chuyển hướng Shopee khi copy mã giảm giá (1: Bật, 0: Tắt)'],
            ['key' => 'coupon_redirect_url', 'value' => '', 'description' => 'Đường dẫn chuyển hướng Shopee tùy chỉnh khi copy mã giảm giá'],
            ['key' => 'telegram_status_shopee_cookie_expired', 'value' => '1', 'description' => 'Bật gửi thông báo Telegram khi lỗi Cookie Shopee (1: Bật, 0: Tắt)'],
            ['key' => 'telegram_chat_id_shopee_cookie_expired', 'value' => '', 'description' => 'Telegram Chat ID nhận thông báo lỗi Cookie Shopee'],
            ['key' => 'telegram_template_shopee_cookie_expired', 'value' => "⚠️ <b>[CẢNH BÁO COOKIE SHOPEE LỖI]</b>
🌐 Hệ thống: {site_name}
👤 Tài khoản: <b>{account_name}</b> ({username})
🔴 Trạng thái: Hết hạn / Lỗi Cookie
💬 Chi tiết: {error_message}
📅 Thời gian: {time}
👉 Vui lòng đăng nhập trang quản trị để cập nhật lại Cookie Shopee.", 'description' => 'Mẫu thông báo Telegram lỗi Cookie Shopee'],

            // Cấu hình Giao diện trang chủ
            ['key' => 'hp_hero_layout', 'value' => 'classic', 'description' => 'Bố cục Hero Section trang chủ'],
            ['key' => 'hp_hero_badge', 'value' => 'Hoàn tiền mua sắm Shopee lên đến {cashback_rate}% hoa hồng', 'description' => 'Badge text trên Hero section trang chủ'],
            ['key' => 'hp_hero_title_1', 'value' => 'Mua Sắm Shopee', 'description' => 'Tiêu đề trang chủ dòng 1'],
            ['key' => 'hp_hero_title_2', 'value' => 'Nhận Lại Tiền Hoàn', 'description' => 'Tiêu đề trang chủ dòng 2'],
            ['key' => 'hp_hero_title_3', 'value' => 'Dễ Dàng', 'description' => 'Tiêu đề trang chủ dòng 3'],
            ['key' => 'hp_hero_description', 'value' => 'Dán link sản phẩm bất kỳ từ Shopee để lấy mã giảm giá, kiểm tra số tiền hoàn lại dự kiến và nhận tiền hoàn trực tiếp vào ví sau khi mua hàng thành công.', 'description' => 'Mô tả phụ Hero section trang chủ'],
            ['key' => 'hp_hero_placeholder', 'value' => 'Dán link sản phẩm Shopee tại đây...', 'description' => 'Placeholder ô tìm kiếm link Shopee'],
            ['key' => 'hp_hero_btn_text', 'value' => 'Lấy Link Hoàn Tiền', 'description' => 'Text nút tìm kiếm link Shopee'],
            ['key' => 'hp_steps_title', 'value' => 'Cách Nhận Hoàn Tiền Shopee Trong 3 Bước', 'description' => 'Tiêu đề Section 3 bước'],
            ['key' => 'hp_steps_subtitle', 'value' => 'Chỉ mất chưa đầy 1 phút để tối ưu hóa chi phí mua sắm của bạn trên sàn Shopee và bắt đầu tích lũy hoa hồng thụ động.', 'description' => 'Phụ đề Section 3 bước'],
            ['key' => 'hp_step_1_title', 'value' => 'Sao chép link sản phẩm', 'description' => 'Tiêu đề Bước 1'],
            ['key' => 'hp_step_1_icon', 'value' => 'copy', 'description' => 'Icon Bước 1'],
            ['key' => 'hp_step_1_desc', 'value' => 'Mở ứng dụng hoặc trang web Shopee, tìm sản phẩm bạn yêu thích rồi sao chép đường dẫn (link) sản phẩm đó.', 'description' => 'Mô tả Bước 1'],
            ['key' => 'hp_step_2_title', 'value' => 'Dán link & Lấy link hoàn tiền', 'description' => 'Tiêu đề Bước 2'],
            ['key' => 'hp_step_2_icon', 'value' => 'search', 'description' => 'Icon Bước 2'],
            ['key' => 'hp_step_2_desc', 'value' => 'Dán link vừa copy vào ô tìm kiếm ở trên đầu trang này để hệ thống phân tích và tạo liên kết rút gọn hoàn tiền của riêng bạn.', 'description' => 'Mô tả Bước 2'],
            ['key' => 'hp_step_3_title', 'value' => 'Mua hàng và nhận tiền hoàn', 'description' => 'Tiêu đề Bước 3'],
            ['key' => 'hp_step_3_icon', 'value' => 'check-square', 'description' => 'Icon Bước 3'],
            ['key' => 'hp_step_3_desc', 'value' => 'Nhấn vào liên kết rút gọn để đi tới Shopee mua hàng như bình thường. Tiền hoàn sẽ tự động cộng vào ví sau khi giao dịch thành công.', 'description' => 'Mô tả Bước 3'],
            ['key' => 'hp_features_title', 'value' => 'Tại Sao Nên Chọn Nền Tảng Của Chúng Tôi?', 'description' => 'Tiêu đề Section Ưu điểm'],
            ['key' => 'hp_features_subtitle', 'value' => 'Mang lại trải nghiệm mua sắm tiết kiệm thông minh nhất cùng nhiều chính sách hấp dẫn bậc nhất thị trường.', 'description' => 'Phụ đề Section Ưu điểm'],
            ['key' => 'hp_feature_1_title', 'value' => 'Tỷ lệ hoàn tiền cao', 'description' => 'Tiêu đề Ưu điểm 1'],
            ['key' => 'hp_feature_1_icon', 'value' => 'percent', 'description' => 'Icon Ưu điểm 1'],
            ['key' => 'hp_feature_1_color', 'value' => 'orange', 'description' => 'Màu sắc Ưu điểm 1'],
            ['key' => 'hp_feature_1_desc', 'value' => 'Nhận lại lên đến 70% tổng số tiền hoa hồng mà Shopee chi trả cho mỗi đơn hàng tiếp thị liên kết thành công.', 'description' => 'Mô tả Ưu điểm 1'],
            ['key' => 'hp_feature_2_title', 'value' => 'Hệ thống 2 tầng MLM', 'description' => 'Tiêu đề Ưu điểm 2'],
            ['key' => 'hp_feature_2_icon', 'value' => 'git-branch', 'description' => 'Icon Ưu điểm 2'],
            ['key' => 'hp_feature_2_color', 'value' => 'green', 'description' => 'Màu sắc Ưu điểm 2'],
            ['key' => 'hp_feature_2_desc', 'value' => 'Giới thiệu bạn bè đăng ký và nhận thêm 5% từ F1 cùng 2% từ F2 trên mỗi đơn hoàn tiền của họ, tạo nguồn thu nhập trọn đời.', 'description' => 'Mô tả Ưu điểm 2'],
            ['key' => 'hp_feature_3_title', 'value' => 'Thanh toán linh hoạt', 'description' => 'Tiêu đề Ưu điểm 3'],
            ['key' => 'hp_feature_3_icon', 'value' => 'banknote', 'description' => 'Icon Ưu điểm 3'],
            ['key' => 'hp_feature_3_color', 'value' => 'blue', 'description' => 'Màu sắc Ưu điểm 3'],
            ['key' => 'hp_feature_3_desc', 'value' => 'Hỗ trợ rút tiền tự động qua mã QR Ngân hàng (VietQR) hoặc ví điện tử MoMo với hạn mức tối thiểu cực thấp chỉ từ 50,000đ.', 'description' => 'Mô tả Ưu điểm 3'],
            ['key' => 'hp_feature_4_title', 'value' => 'Ghi nhận đơn tự động', 'description' => 'Tiêu đề Ưu điểm 4'],
            ['key' => 'hp_feature_4_icon', 'value' => 'history', 'description' => 'Icon Ưu điểm 4'],
            ['key' => 'hp_feature_4_color', 'value' => 'purple', 'description' => 'Màu sắc Ưu điểm 4'],
            ['key' => 'hp_feature_4_desc', 'value' => 'Các đơn hàng Shopee sẽ tự động đồng bộ thời gian thực thông qua API và được hiển thị ngay lập tức trong bảng lịch sử ví.', 'description' => 'Mô tả Ưu điểm 4'],
            ['key' => 'hp_stats_title', 'value' => 'Thống kê hệ thống thực tế', 'description' => 'Tiêu đề widget thống kê trên Hero'],
            ['key' => 'hp_stats_clicks_label', 'value' => 'Lượt nhấp hoàn tiền', 'description' => 'Nhãn hiển thị lượt nhấp chuột'],
            ['key' => 'hp_stats_clicks_value', 'value' => '0', 'description' => 'Số lượt nhấp chuột khởi tạo'],
            ['key' => 'hp_stats_users_label', 'value' => 'Thành viên hoạt động', 'description' => 'Nhãn hiển thị thành viên hoạt động'],
            ['key' => 'hp_stats_users_value', 'value' => '0', 'description' => 'Số thành viên hoạt động khởi tạo'],
            ['key' => 'hp_stats_paid_label', 'value' => 'Tổng hoa hồng đã chi trả', 'description' => 'Nhãn hiển thị tổng hoa hồng đã chi trả'],
            ['key' => 'hp_stats_paid_value', 'value' => '0', 'description' => 'Số tiền hoa hồng đã chi trả khởi tạo'],
            // Mặc định bật popup hướng dẫn tương tác khi cài đặt hệ thống sạch theo yêu cầu của sếp Thành
            ['key' => 'hp_show_demo_modal', 'value' => '1', 'description' => 'Bật/Tắt hướng dẫn demo lấy link hoàn tiền'],
            ['key' => 'hp_show_timeline', 'value' => '1', 'description' => 'Bật/Tắt hiển thị section timeline quy trình hoàn tiền'],
            ['key' => 'hp_show_steps', 'value' => '1', 'description' => 'Bật/Tắt hiển thị section 3 bước nhận hoàn tiền'],
            ['key' => 'hp_show_features', 'value' => '1', 'description' => 'Bật/Tắt hiển thị section ưu điểm vượt trội'],
            ['key' => 'hp_show_blog', 'value' => '1', 'description' => 'Bật/Tắt hiển thị section bài viết blog mới nhất'],
            ['key' => 'hp_hero_right_type', 'value' => 'slider', 'description' => 'Kiểu hiển thị media bên phải Hero (slider: Slider, video: Video)'],
            ['key' => 'hp_hero_right_video_url', 'value' => '', 'description' => 'Link video YouTube hoặc video trực tiếp (.mp4) hiển thị bên phải Hero'],
            ['key' => 'hp_hero_right_video_poster', 'value' => '', 'description' => 'Link ảnh poster đại diện của video bên phải Hero'],
            ['key' => 'hp_demo_modal_type', 'value' => 'interactive', 'description' => 'Kiểu hiển thị của modal hướng dẫn lấy link (interactive: Tương tác mockup, custom: Tự soạn)'],
            ['key' => 'hp_demo_modal_custom_content', 'value' => '', 'description' => 'Nội dung hướng dẫn lấy link tự soạn (hỗ trợ HTML)'],
            ['key' => 'show_admin_menu_on_frontend', 'value' => '1', 'description' => 'Hiển thị nút Trang quản trị ở giao diện thành viên cho Admin (1: Bật, 0: Tắt)'],
            ['key' => 'ip_register_limit', 'value' => '5', 'description' => 'Số lượng tài khoản tối đa được đăng ký trên cùng một địa chỉ IP'],
            ['key' => 'hp_cashback_notice', 'value' => "Tiền hoàn hiển thị là tạm tính. Số tiền thực nhận sẽ được ghi nhận theo giá trị đơn hàng sau khi trừ voucher và mã giảm giá.\n\nNếu mua nhiều sản phẩm trong cùng một đơn, tiền hoàn sẽ được nhân lên theo số lượng sản phẩm đủ điều kiện. Một số ngành hàng Shopee có thể giới hạn tối đa 50K/đơn, nên với đơn lớn bạn có thể tách đơn hoặc nhắn hỗ trợ để được tư vấn.", 'description' => 'Nội dung lưu ý hiển thị ở phần kết quả tìm kiếm sản phẩm trang chủ'],
            ['key' => 'hp_show_notice_modal', 'value' => '1', 'description' => 'Bật/Tắt hiển thị popup lưu ý khi mua hàng'],
            ['key' => 'hp_notice_modal_content', 'value' => '<div class="space-y-4">
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
</div>', 'description' => 'Nội dung popup lưu ý khi mua hàng'],
            ['key' => 'hp_timeline_badge', 'value' => 'Lộ trình hoàn tiền Shopee', 'description' => 'Badge của section timeline trang chủ'],
            ['key' => 'hp_timeline_title', 'value' => 'Quy Trình Nhận Hoàn Tiền Siêu Tốc', 'description' => 'Tiêu đề section timeline trang chủ'],
            ['key' => 'hp_timeline_subtitle', 'value' => 'Hiểu rõ quy trình ghi nhận đơn hàng và thời gian tiền hoàn về tài khoản của bạn.', 'description' => 'Phụ đề section timeline trang chủ'],
            ['key' => 'hp_timeline_step_1_badge', 'value' => 'Bước 1: Mua hàng', 'description' => 'Badge timeline Bước 1'],
            ['key' => 'hp_timeline_step_1_title', 'value' => 'Ngày mua', 'description' => 'Tiêu đề timeline Bước 1'],
            ['key' => 'hp_timeline_step_1_tag', 'value' => 'Hôm nay', 'description' => 'Tag timeline Bước 1'],
            ['key' => 'hp_timeline_step_1_desc', 'value' => 'Bạn copy link Shopee dán vào hệ thống, nhận link rút gọn và tiến hành đặt mua hàng.', 'description' => 'Mô tả timeline Bước 1'],
            ['key' => 'hp_timeline_step_2_badge', 'value' => 'Bước 2: Đối soát', 'description' => 'Badge timeline Bước 2'],
            ['key' => 'hp_timeline_step_2_title', 'value' => 'Ghi nhận', 'description' => 'Tiêu đề timeline Bước 2'],
            ['key' => 'hp_timeline_step_2_tag', 'value' => 'Ngày mai', 'description' => 'Tag timeline Bước 2'],
            ['key' => 'hp_timeline_step_2_desc', 'value' => 'Shopee ghi nhận đơn hàng tạm tính và tự động đồng bộ hiển thị trong lịch sử ví của bạn.', 'description' => 'Mô tả timeline Bước 2'],
            ['key' => 'hp_timeline_step_3_badge', 'value' => 'Bước 3: Thực nhận', 'description' => 'Badge timeline Bước 3'],
            ['key' => 'hp_timeline_step_3_title', 'value' => 'Có thể rút', 'description' => 'Tiêu đề timeline Bước 3'],
            ['key' => 'hp_timeline_step_3_tag', 'value' => '7 ngày', 'description' => 'Tag timeline Bước 3'],
            ['key' => 'hp_timeline_step_3_desc', 'value' => 'Sau khi Shopee đối soát kỳ hoàn thành (khoảng 7 ngày khi nhận hàng), tiền khả dụng sẽ được cộng vào ví và có thể rút ngay.', 'description' => 'Mô tả timeline Bước 3'],
            ['key' => 'hp_sections_order', 'value' => '["hp_show_timeline","hp_show_steps","hp_show_features","hp_show_coupons","hp_show_blog"]', 'description' => 'Thứ tự sắp xếp các section trên trang chủ'],
            ['key' => 'hp_enable_bubble_effect', 'value' => '1', 'description' => 'Bật/Tắt hiệu ứng bong bóng bay trang chủ'],

            // Cấu hình chức năng Giftcode (mã nhập nhận thưởng)
            ['key' => 'gift_code_enabled', 'value' => '1', 'description' => 'Bật/Tắt chức năng nhập Giftcode nhận thưởng (1: Bật, 0: Tắt)'],
            ['key' => 'gift_code_intro', 'value' => 'Nhập mã quà tặng (Giftcode) được phát từ các sự kiện, minigame hoặc admin để nhận thưởng cộng thẳng vào số dư ví khả dụng của bạn.', 'description' => 'Nội dung mô tả hiển thị ở trang nhập Giftcode của thành viên'],

            // Cấu hình hệ thống Open API (dùng cho App Mobile / Frontend)
            // BẮT BUỘC mặc định TẮT khi cài đặt mới, không bao giờ tự động bật để đảm bảo an toàn
            ['key' => 'openapi_status', 'value' => '0', 'description' => 'Công tắc tổng hệ thống Open API (0: Tắt - mặc định an toàn, 1: Bật)'],
            ['key' => 'openapi_token_ttl_days', 'value' => '0', 'description' => 'Số ngày hiệu lực của token đăng nhập Open API (0: Vĩnh viễn)'],

            // Tài liệu API hiển thị cho thành viên trong trang Hồ sơ (chỉ là tài liệu hướng dẫn,
            // không tự mở endpoint nào nên mặc định BẬT là an toàn — endpoint vẫn phụ thuộc openapi_status)
            ['key' => 'api_docs_enabled', 'value' => '1', 'description' => 'Bật/Tắt hiển thị Tài liệu API cho thành viên (1: Bật - mặc định, 0: Tắt)'],
        ];

        foreach ($settings as $setting) {
            \App\Models\Setting::updateOrCreate(['key' => $setting['key']], $setting);
        }

        // 3. Tạo Banners trống mặc định
        \App\Models\Banner::create([
            'image_url' => 'https://picsum.photos/1200/400?random=1',
            'link' => '#',
            'title' => 'Chào mừng bạn tham gia',
            'order' => 1,
            'is_active' => true,
        ]);

        // 4. Chạy các seeder danh mục cấu hình hệ thống
        $this->call(BlogCmsSeeder::class);
        $this->call(LanguageSeeder::class);
        $this->call(CurrencySeeder::class);
        $this->call(PageSeeder::class);
        $this->call(MenuSeeder::class);

        // 5. Đảm bảo các bảng giao dịch, hoạt động và cache hoàn toàn trống rỗng khi cài đặt mới (Reset AUTO_INCREMENT về 1)
        \Illuminate\Support\Facades\Schema::disableForeignKeyConstraints();
        \Illuminate\Support\Facades\DB::table('balance_logs')->truncate();
        \Illuminate\Support\Facades\DB::table('activity_logs')->truncate();
        \Illuminate\Support\Facades\DB::table('referrals')->truncate();
        \Illuminate\Support\Facades\DB::table('withdrawals')->truncate();
        \Illuminate\Support\Facades\DB::table('cashback_histories')->truncate();
        \Illuminate\Support\Facades\DB::table('notifications')->truncate();
        \Illuminate\Support\Facades\DB::table('gift_redemptions')->truncate();
        \Illuminate\Support\Facades\DB::table('gifts')->truncate();
        \Illuminate\Support\Facades\DB::table('gift_code_redemptions')->truncate();
        \Illuminate\Support\Facades\DB::table('gift_codes')->truncate();
        
        try {
            \Illuminate\Support\Facades\DB::table('cache')->truncate();
        } catch (\Exception $e) {
            // Bỏ qua nếu bảng cache không tồn tại hoặc sử dụng driver khác
        }
        \Illuminate\Support\Facades\Schema::enableForeignKeyConstraints();
    }
}
