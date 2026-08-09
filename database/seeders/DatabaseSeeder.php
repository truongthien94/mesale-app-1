<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Khởi tạo dữ liệu mẫu cho hệ thống platform Hoàn Tiền Shopee.
     */
    public function run(): void
    {
        // 1. Tạo tài khoản Admin mặc định
        $admin = User::forceCreate([
            'name' => 'Administrator',
            'email' => 'admin@cmsnt.co',
            'phone' => '0987654321',
            'password' => bcrypt('123456'),
            'role' => 'admin',
            'status' => 'active',
            'referral_code' => 'ADMIN',
            'email_verified_at' => now(),
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'country' => 'Localhost',
        ]);

        // 2. Tạo tài khoản người dùng mặc định (User)
        $user1 = User::forceCreate([
            'name' => 'Nguyễn Văn A',
            'email' => 'user@gmail.com',
            'phone' => '0123456789',
            'password' => bcrypt('user123'),
            'role' => 'user',
            'status' => 'active',
            'referral_code' => 'USER123',
            'referred_by' => $admin->id,
            'balance' => 150000.00,
            'total_cashback' => 200000.00,
            'total_withdrawn' => 50000.00,
            'email_verified_at' => now(),
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'country' => 'Localhost',
        ]);

        // Tạo biến động số dư mẫu cho user1
        \App\Models\BalanceLog::create([
            'user_id' => $user1->id,
            'amount_before' => 0.00,
            'amount_change' => 200000.00,
            'amount_after' => 200000.00,
            'type' => 'cashback',
            'description' => 'Nhận hoàn tiền Shopee đơn hàng SHP123456789'
        ]);

        \App\Models\BalanceLog::create([
            'user_id' => $user1->id,
            'amount_before' => 200000.00,
            'amount_change' => -50000.00,
            'amount_after' => 150000.00,
            'type' => 'withdraw_request',
            'description' => 'Yêu cầu rút tiền về Ví MoMo'
        ]);

        // Tạo thêm một user phụ được giới thiệu bởi Nguyễn Văn A để test F1
        $user2 = User::forceCreate([
            'name' => 'Trần Thị B',
            'email' => 'user2@gmail.com',
            'phone' => '0112233445',
            'password' => bcrypt('user123'),
            'role' => 'user',
            'status' => 'active',
            'referral_code' => 'USER456',
            'referred_by' => $user1->id,
            'balance' => 0.00,
            'total_cashback' => 0.00,
            'total_withdrawn' => 0.00,
            'email_verified_at' => now(),
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_1_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.1 Mobile/15E148 Safari/604.1',
            'country' => 'Localhost',
        ]);

        // 3. Tạo cấu hình hệ thống mặc định trong settings khớp với code controller
        $settings = [
            ['key' => 'site_name', 'value' => request()->getHost() && request()->getHost() !== 'localhost' ? request()->getHost() : (parse_url(config('app.url'), PHP_URL_HOST) ?: 'Hoantienshopee.vn'), 'description' => 'Tên website chính thức'],
            ['key' => 'site_description', 'value' => 'Website hoàn tiền mua sắm Shopee tự động, nhận hoa hồng MLM 2 tầng', 'description' => 'Mô tả website chính thức'],
            ['key' => 'shopee_cashback_rate', 'value' => '50', 'description' => 'Tỷ lệ hoàn tiền cho người dùng (% trên tổng hoa hồng nhận được)'],
            ['key' => 'shopee_fake_cashback_rate', 'value' => '50', 'description' => 'Tỷ lệ hoàn tiền ảo hiển thị cho người dùng khi lấy link Shopee (%)'],
            ['key' => 'tiktok_fake_cashback_rate', 'value' => '50', 'description' => 'Tỷ lệ hoàn tiền ảo hiển thị cho người dùng khi lấy link TikTok Shop (%)'],
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
            ['key' => 'coupon_redirect_on_copy', 'value' => '0', 'description' => 'Tự động mở link chuyển hướng Shopee khi copy mã giảm giá (1: Bật, 0: Tắt)'],
            ['key' => 'coupon_redirect_url', 'value' => '', 'description' => 'Đường dẫn chuyển hướng Shopee tùy chỉnh khi copy mã giảm giá'],

            // Cấu hình Giao diện trang chủ (Homepage Appearance Settings)
            ['key' => 'hp_hero_layout', 'value' => 'classic', 'description' => 'Bố cục Hero Section trang chủ (classic: Cổ điển, centered: Tập trung, split: Chia đôi với Thống kê)'],
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
            ['key' => 'hp_stats_clicks_value', 'value' => '48200', 'description' => 'Số ảo lượt nhấp chuột cộng thêm'],
            ['key' => 'hp_stats_users_label', 'value' => 'Thành viên hoạt động', 'description' => 'Nhãn hiển thị thành viên hoạt động'],
            ['key' => 'hp_stats_users_value', 'value' => '1450', 'description' => 'Số ảo thành viên hoạt động cộng thêm'],
            ['key' => 'hp_stats_paid_label', 'value' => 'Tổng hoa hồng đã chi trả', 'description' => 'Nhãn hiển thị tổng hoa hồng đã chi trả'],
            ['key' => 'hp_stats_paid_value', 'value' => '125000000', 'description' => 'Số ảo tiền hoa hồng đã chi trả cộng thêm'],
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
            ['key' => 'gift_redemption_enabled', 'value' => '0', 'description' => 'Trạng thái hoạt động của chức năng quy đổi quà tặng (1: Bật, 0: Tắt)'],
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
        ];

        foreach ($settings as $setting) {
            \App\Models\Setting::updateOrCreate(['key' => $setting['key']], $setting);
        }

        // 4. Tạo Banners mẫu
        \App\Models\Banner::create([
            'image_url' => 'https://picsum.photos/1200/400?random=1',
            'link' => '#',
            'title' => 'Hoàn tiền mua sắm Shopee lên đến 15%',
            'order' => 1,
            'is_active' => true,
        ]);
        \App\Models\Banner::create([
            'image_url' => 'https://picsum.photos/1200/400?random=2',
            'link' => '#',
            'title' => 'Mời bạn bè nhận ngay hoa hồng trọn đời',
            'order' => 2,
            'is_active' => true,
        ]);

        // 5. Tạo lịch sử hoàn tiền mẫu cho Nguyễn Văn A (user1)
        \App\Models\CashbackHistory::create([
            'user_id' => $user1->id,
            'order_id' => 'SHP123456789',
            'product_name' => 'Điện thoại Apple iPhone 15 Pro Max 256GB - Chính hãng VNA',
            'product_image' => 'https://picsum.photos/200/200?random=10',
            'original_price' => 30000000.00,
            'cashback_amount' => 150000.00,
            'cashback_rate' => 0.50,
            'commission_amount' => 187500.00,
            'affiliate_url' => 'https://shope.ee/example1',
            'status' => 'approved',
            'approved_at' => now()->subDays(2),
        ]);

        \App\Models\CashbackHistory::create([
            'user_id' => $user1->id,
            'order_id' => 'SHP987654321',
            'product_name' => 'Giày Thể Thao Nam Nike Air Force 1 07 White',
            'product_image' => 'https://picsum.photos/200/200?random=11',
            'original_price' => 2500000.00,
            'cashback_amount' => 50000.00,
            'cashback_rate' => 2.00,
            'commission_amount' => 62500.00,
            'affiliate_url' => 'https://shope.ee/example2',
            'status' => 'pending',
        ]);

        // 6. Tạo yêu cầu rút tiền mẫu
        \App\Models\Withdrawal::create([
            'user_id' => $user1->id,
            'amount' => 50000.00,
            'payment_method' => 'momo',
            'account_number' => '0123456789',
            'account_name' => 'NGUYEN VAN A',
            'status' => 'approved',
            'notes' => 'Đã chuyển khoản thành công qua ví MoMo',
            'processed_at' => now()->subDays(5),
        ]);

        \App\Models\Withdrawal::create([
            'user_id' => $user1->id,
            'amount' => 100000.00,
            'payment_method' => 'bank',
            'account_number' => '1903456789012',
            'account_name' => 'NGUYEN VAN A',
            'bank_name' => 'Techcombank',
            'status' => 'pending',
        ]);

        // 7. Tạo lịch sử giới thiệu trong bảng referrals
        \App\Models\Referral::create([
            'referrer_id' => $user1->id,
            'referred_id' => $user2->id,
        ]);

        // 8. Ghi log hoạt động mẫu (dùng cột 'activity' thay vì 'action')
        \App\Models\ActivityLog::create([
            'user_id' => $user1->id,
            'activity' => 'Đăng nhập vào hệ thống',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Mozilla/5.0',
            'created_at' => now()->subMinutes(10),
        ]);
        \App\Models\ActivityLog::create([
            'user_id' => $user1->id,
            'activity' => 'Điểm danh hàng ngày nhận xu',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Mozilla/5.0',
            'created_at' => now()->subMinutes(5),
        ]);

        // 9. Tạo thông báo mẫu
        \App\Models\Notification::create([
            'user_id' => $user1->id,
            'title' => 'Chào mừng bạn tham gia',
            'content' => 'Chào mừng bạn đã đăng ký tài khoản tại Cashback Shopee. Dán link sản phẩm và nhận hoàn tiền ngay nhé!',
            'type' => 'general',
            'is_read' => false,
        ]);
        \App\Models\Notification::create([
            'user_id' => $user1->id,
            'title' => 'Thông tin chương trình khuyến mãi tháng 5',
            'content' => 'Nhận ngay thêm 10% cashback cho tất cả các đơn hàng hoàn tiền từ ngày 20/05 đến ngày 30/05/2026.',
            'type' => 'general',
            'is_read' => false,
        ]);
        \App\Models\Notification::create([
            'user_id' => $user1->id,
            'title' => 'Yêu cầu rút tiền thành công',
            'content' => 'Yêu cầu rút 50.000đ của bạn đã được duyệt và chuyển khoản qua ví MoMo.',
            'type' => 'personal',
            'is_read' => true,
        ]);

        // Tạo thông báo mẫu cho tài khoản admin (id = 1)
        \App\Models\Notification::create([
            'user_id' => $admin->id,
            'title' => 'Chào mừng Admin quản trị hệ thống',
            'content' => 'Chào mừng bạn đến với bảng điều khiển Admin Cashback Shopee. Kiểm tra và duyệt các yêu cầu hoàn tiền mới nhé!',
            'type' => 'general',
            'is_read' => false,
        ]);
        \App\Models\Notification::create([
            'user_id' => $admin->id,
            'title' => 'Thông tin cập nhật hệ thống định kỳ',
            'content' => 'Hệ thống đã nâng cấp cơ chế tự động render icon chống chớp nháy và nạp tab thông báo bằng công nghệ Ajax mượt mà.',
            'type' => 'general',
            'is_read' => false,
        ]);
        \App\Models\Notification::create([
            'user_id' => $admin->id,
            'title' => 'Yêu cầu rút tiền cần xử lý',
            'content' => 'Bạn có 1 yêu cầu rút tiền mới đang chờ xử lý trong bảng quản trị.',
            'type' => 'personal',
            'is_read' => false,
        ]);

        // Gọi Seeder khởi tạo dữ liệu cho module Blog CMS chuyên nghiệp
        $this->call(BlogCmsSeeder::class);

        // Gọi Seeder khởi tạo các ngôn ngữ mặc định hệ thống
        $this->call(LanguageSeeder::class);

        // Gọi Seeder khởi tạo các tiền tệ mặc định hệ thống
        $this->call(CurrencySeeder::class);

        // Gọi Seeder khởi tạo trang nội dung tĩnh và cấu hình chân trang
        $this->call(PageSeeder::class);

        // Gọi Seeder khởi tạo các menu mặc định
        $this->call(MenuSeeder::class);

        // Gọi Seeder khởi tạo các quà tặng mặc định
        $this->call(GiftSeeder::class);
    }
}
