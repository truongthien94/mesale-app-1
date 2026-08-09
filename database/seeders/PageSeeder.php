<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Page;
use App\Models\Setting;

/**
 * Seeder khởi tạo dữ liệu mẫu cho trang Điều khoản dịch vụ và Chính sách bảo mật.
 * Đồng thời tự động thiết lập liên kết ở footer trong bảng settings.
 */
class PageSeeder extends Seeder
{
    /**
     * Chạy seeder để thêm dữ liệu mẫu.
     */
    public function run(): void
    {
        $siteDomain = request()->getHost() && request()->getHost() !== 'localhost' ? request()->getHost() : (parse_url(config('app.url'), PHP_URL_HOST) ?: 'Hoantienshopee.vn');

        // 1. Tạo hoặc cập nhật trang Điều khoản dịch vụ mẫu
        Page::updateOrCreate(
            ['slug' => 'dieu-khoan-dich-vu'],
            [
                'title' => 'Điều khoản dịch vụ',
                'content' => '
                    <h2>1. Giới thiệu dịch vụ</h2>
                    <p>Chào mừng bạn đến với <strong>' . $siteDomain . '</strong>. Khi sử dụng dịch vụ của chúng tôi, bạn đồng ý với các điều khoản dưới đây. Hệ thống của chúng tôi cung cấp dịch vụ liên kết mua sắm và nhận hoàn tiền (cashback) từ các giao dịch mua hàng thành công trên sàn thương mại điện tử Shopee thông qua chương trình Tiếp thị liên kết (Affiliate).</p>
                    
                    <h2>2. Điều kiện tham gia và tài khoản</h2>
                    <ul>
                        <li>Người dùng phải đủ 18 tuổi hoặc có sự giám sát của người giám hộ hợp pháp khi thực hiện giao dịch.</li>
                        <li>Bạn có trách nhiệm tự bảo mật thông tin tài khoản, mật khẩu cá nhân và mã PIN rút tiền.</li>
                        <li>Mỗi người dùng chỉ được đăng ký duy nhất 1 tài khoản trên hệ thống. Chúng tôi có quyền khóa vĩnh viễn các tài khoản được phát hiện trùng IP, trùng thông tin thanh toán hoặc có dấu hiệu tạo tài khoản ảo nhằm trục lợi chính sách giới thiệu.</li>
                    </ul>

                    <h2>3. Cơ chế ghi nhận hoàn tiền (Cashback)</h2>
                    <ul>
                        <li>Giao dịch hoàn tiền chỉ được ghi nhận khi người dùng click vào link chuyển đổi trên hệ thống của chúng tôi và hoàn tất mua hàng trên ứng dụng/website Shopee.</li>
                        <li>Số tiền hoàn lại dựa trên tỷ lệ % hoa hồng thực tế mà Shopee đối soát và chi trả cho hệ thống.</li>
                        <li>Đơn hàng bị hủy, trả hàng, hoàn tiền hoặc vi phạm chính sách của Shopee sẽ không được ghi nhận cashback.</li>
                        <li>Thời gian đối soát và duyệt đơn hàng hoàn tiền có thể kéo dài từ 30 đến 75 ngày tùy thuộc vào chu kỳ thanh toán của Shopee.</li>
                    </ul>

                    <h2>4. Chương trình giới thiệu thành viên (MLM 2 Tầng)</h2>
                    <p>Hệ thống hỗ trợ cơ chế hoa hồng giới thiệu 2 tầng gồm F1 (trực tiếp) và F2 (gián tiếp):</p>
                    <ul>
                        <li>Bạn sẽ nhận được hoa hồng giới thiệu tính theo tỷ lệ phần trăm trên số tiền hoàn (cashback) thực nhận của tuyến dưới khi đơn hàng của họ được phê duyệt thành công.</li>
                        <li>Hành vi cố ý tự giới thiệu chéo (tự tạo nhiều tài khoản dưới link của mình) để nhận hoa hồng giới thiệu là nghiêm cấm. Nếu bị phát hiện, hệ thống sẽ đóng băng tài khoản và tịch thu toàn bộ số dư khả dụng.</li>
                    </ul>

                    <h2>5. Quy định về Rút tiền</h2>
                    <ul>
                        <li>Hạn mức rút tiền tối thiểu được cấu hình công khai trên hệ thống (mặc định từ 50,000đ).</li>
                        <li>Các yêu cầu rút tiền sẽ được bộ phận kiểm duyệt xử lý thủ công trong vòng từ 1 đến 24 giờ làm việc.</li>
                        <li>Người dùng cần cung cấp chính xác số tài khoản ngân hàng hoặc số điện thoại ví MoMo. Mọi trường hợp sai lệch thông tin dẫn tới thất thoát tài sản, hệ thống sẽ không chịu trách nhiệm.</li>
                    </ul>

                    <h2>6. Thay đổi điều khoản</h2>
                    <p>Chúng tôi giữ quyền thay đổi, chỉnh sửa các điều khoản dịch vụ này bất cứ lúc nào mà không cần báo trước. Các thay đổi sẽ có hiệu lực ngay khi được đăng tải lên hệ thống.</p>
                ',
                'status' => 'published',
                'sort_order' => 1,
                'meta_description' => 'Điều khoản sử dụng và quy định tham gia hệ thống hoàn tiền mua sắm Shopee.',
            ]
        );

        // 2. Tạo hoặc cập nhật trang Chính sách bảo mật mẫu
        Page::updateOrCreate(
            ['slug' => 'chinh-sach-bao-mat'],
            [
                'title' => 'Chính sách bảo mật',
                'content' => '
                    <h2>1. Thu thập thông tin cá nhân</h2>
                    <p>Chúng tôi chỉ thu thập các thông tin cần thiết phục vụ cho việc vận hành tài khoản và xử lý yêu cầu rút tiền của bạn, bao gồm:</p>
                    <ul>
                        <li>Họ và tên người dùng.</li>
                        <li>Địa chỉ Email (sử dụng để xác thực tài khoản và gửi thông báo).</li>
                        <li>Số điện thoại (dùng để đăng ký, xác thực OTP).</li>
                        <li>Thông tin tài khoản ngân hàng hoặc số ví điện tử (chỉ dùng cho mục đích thanh toán rút tiền).</li>
                    </ul>

                    <h2>2. Mục đích sử dụng thông tin</h2>
                    <p>Thông tin thu thập từ người dùng được sử dụng vào các mục đích sau:</p>
                    <ul>
                        <li>Quản lý tài khoản thành viên và ghi nhận doanh số hoàn tiền Shopee.</li>
                        <li>Xử lý và chuyển khoản các yêu cầu rút tiền từ ví của bạn.</li>
                        <li>Gửi các email thông báo giao dịch, cập nhật số dư, thông báo hệ thống và các chương trình khuyến mãi.</li>
                        <li>Ngăn ngừa các hoạt động giả mạo, gian lận hoặc tấn công hệ thống bảo mật.</li>
                    </ul>

                    <h2>3. Bảo mật thông tin dữ liệu</h2>
                    <ul>
                        <li>Chúng tôi áp dụng các tiêu chuẩn mã hóa dữ liệu hiện đại (SSL/TLS) để đảm bảo thông tin cá nhân và thông tin tài khoản ngân hàng của bạn được bảo mật tuyệt đối khi truyền tải qua Internet.</li>
                        <li>Thông tin mật khẩu của bạn được mã hóa một chiều bằng thuật toán an toàn (Bcrypt) trước khi lưu trữ trong cơ sở dữ liệu.</li>
                    </ul>

                    <h2>4. Chia sẻ thông tin với bên thứ ba</h2>
                    <p>Chúng tôi cam kết không bán, trao đổi hoặc cho thuê thông tin cá nhân của người dùng cho bất kỳ bên thứ ba nào. Thông tin chỉ được chia sẻ trong các trường hợp:</p>
                    <ul>
                        <li>Được sự đồng ý bằng văn bản của chính người dùng.</li>
                        <li>Yêu cầu từ cơ quan pháp luật có thẩm quyền phục vụ điều tra theo đúng quy định pháp luật hiện hành.</li>
                    </ul>

                    <h2>5. Công nghệ Cookie</h2>
                    <p>Website sử dụng Cookie để lưu trữ thông tin phiên đăng nhập của người dùng, giúp bạn không cần phải đăng nhập lại nhiều lần và tăng tốc độ tải trang. Bạn hoàn toàn có thể chủ động tắt Cookie trong phần cài đặt trình duyệt của mình.</p>
                ',
                'status' => 'published',
                'sort_order' => 2,
                'meta_description' => 'Chính sách bảo mật thông tin cá nhân và dữ liệu thanh toán tại hệ thống hoàn tiền Shopee.',
            ]
        );

        // 3. Tự động thiết lập đường dẫn footer trong cài đặt chung
        Setting::updateOrCreate(
            ['key' => 'footer_terms_url'],
            [
                'value' => '/page/dieu-khoan-dich-vu',
                'description' => 'Đường dẫn trang Điều khoản dịch vụ chân trang'
            ]
        );

        Setting::updateOrCreate(
            ['key' => 'footer_privacy_url'],
            [
                'value' => '/page/chinh-sach-bao-mat',
                'description' => 'Đường dẫn trang Chính sách bảo mật chân trang'
            ]
        );
    }
}
