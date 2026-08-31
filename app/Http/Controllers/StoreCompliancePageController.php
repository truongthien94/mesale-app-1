<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Contracts\View\View;

class StoreCompliancePageController extends Controller
{
    public function privacy(): View
    {
        return $this->render('privacy', [
            'title' => 'Chính sách bảo mật',
            'description' => 'Cách Mesale thu thập, sử dụng, bảo vệ và xóa dữ liệu thành viên.',
            'sections' => [
                [
                    'heading' => 'Dữ liệu được thu thập',
                    'paragraphs' => [
                        'Mesale có thể lưu thông tin tài khoản như họ tên, email, số điện thoại, trạng thái xác minh và tùy chọn ngôn ngữ hoặc tiền tệ.',
                        'Khi thành viên sử dụng chức năng hoàn tiền, hệ thống xử lý liên kết sản phẩm, đơn hàng, tiền hoàn, số dư, lịch sử ví, yêu cầu rút tiền, tài khoản nhận tiền, giới thiệu F1/F2, nhiệm vụ, quà tặng, mã quà, coupon và thông báo.',
                        'Nếu thành viên chọn đăng nhập Google hoặc Sign in with Apple, Mesale lưu định danh nhà cung cấp đã được backend xác minh. Mật khóa Apple, Google client secret và khóa dịch vụ không được lưu trong ứng dụng.',
                        'Hệ thống cũng có thể xử lý Bearer access token theo phiên, thông tin thiết bị/phiên, push token khi thông báo đẩy được bật, địa chỉ IP, user agent và nhật ký bảo mật cần thiết để chống gian lận và hỗ trợ vận hành.',
                    ],
                ],
                [
                    'heading' => 'Mục đích sử dụng',
                    'items' => [
                        'Xác thực tài khoản, khôi phục phiên và bảo vệ đăng nhập.',
                        'Đối soát đơn hàng, tính cashback, quản lý ví, rút tiền và quyền lợi giới thiệu.',
                        'Cung cấp hỗ trợ, thông báo trạng thái và xử lý khiếu nại.',
                        'Ngăn chặn lạm dụng, phát hiện gian lận, ghi nhận lỗi và tuân thủ nghĩa vụ pháp lý.',
                    ],
                ],
                [
                    'heading' => 'Lưu trữ và chia sẻ',
                    'paragraphs' => [
                        'Laravel tại mesale.vn là nguồn dữ liệu nghiệp vụ chính. Ứng dụng mobile không có cơ sở dữ liệu business riêng và không truy cập trực tiếp MariaDB.',
                        'Dữ liệu chỉ được chia sẻ với nhà cung cấp đăng nhập, hạ tầng vận hành hoặc nền tảng thương mại điện tử trong phạm vi cần thiết để cung cấp chức năng. Mesale không tuyên bố bán dữ liệu cá nhân hoặc theo dõi người dùng giữa các ứng dụng cho mục đích quảng cáo.',
                        'Access token mobile được lưu trong vùng bảo mật của thiết bị. Mọi thay đổi tài chính đều do máy chủ xác nhận.',
                    ],
                ],
                [
                    'heading' => 'Xóa tài khoản và lưu giữ hồ sơ',
                    'paragraphs' => [
                        'Trong ứng dụng, mở Tài khoản, chọn Xóa tài khoản và hoàn tất bước xác nhận danh tính. Tài khoản liên kết Apple phải xác thực lại bằng Sign in with Apple để ngắt quyền cấp trước khi xóa.',
                        'Trên website, thành viên đã đăng nhập có thể mở Hồ sơ và dùng chức năng xóa tài khoản khi tính năng này được quản trị viên bật. Người không còn truy cập ứng dụng có thể làm theo hướng dẫn tại trang Xóa tài khoản.',
                        'Khi xóa, token và phiên đăng nhập bị thu hồi. Một số hồ sơ đối soát tài chính hoặc hoa hồng có thể được giữ lại ở dạng đã loại bỏ liên kết tới thông tin định danh khi pháp luật hoặc yêu cầu kế toán bắt buộc.',
                    ],
                ],
                [
                    'heading' => 'Quyền và liên hệ',
                    'paragraphs' => [
                        'Thành viên có thể yêu cầu xem, sửa hoặc xóa dữ liệu bằng các công cụ trong tài khoản hoặc qua trang Hỗ trợ.',
                    ],
                ],
            ],
        ]);
    }

    public function terms(): View
    {
        return $this->render('terms', [
            'title' => 'Điều khoản dịch vụ',
            'description' => 'Điều kiện sử dụng dịch vụ cashback Mesale trên website và ứng dụng.',
            'sections' => [
                [
                    'heading' => 'Phạm vi dịch vụ',
                    'paragraphs' => [
                        'Mesale cung cấp công cụ tạo liên kết mua sắm, theo dõi đơn đủ điều kiện, cashback, giới thiệu, nhiệm vụ, quà tặng và các tiện ích thành viên. Giao dịch mua hàng hóa vật lý được hoàn tất tại Shopee, TikTok Shop, Lazada hoặc nền tảng liên quan; Mesale không phải bên bán hàng hóa đó.',
                    ],
                ],
                [
                    'heading' => 'Tài khoản thành viên',
                    'items' => [
                        'Cung cấp thông tin chính xác và bảo vệ mật khẩu, OTP, 2FA và thiết bị đăng nhập.',
                        'Không chia sẻ Bearer token, API key cá nhân hoặc tài khoản cho bên không được phép.',
                        'Không tạo tài khoản trùng lặp, giả mạo danh tính, tự giới thiệu hoặc thao túng đơn hàng để nhận quyền lợi.',
                        'Thông báo ngay cho Mesale khi phát hiện truy cập trái phép.',
                    ],
                ],
                [
                    'heading' => 'Cashback, số dư và rút tiền',
                    'paragraphs' => [
                        'Cashback chỉ được ghi nhận khi nền tảng đối tác xác nhận đơn hàng hợp lệ. Trạng thái, thời gian đối soát, tỷ lệ và số tiền có thể thay đổi theo dữ liệu đối tác, hoàn/hủy đơn, chống gian lận và chính sách được công bố tại thời điểm giao dịch.',
                        'Mesale không bảo đảm thu nhập, lợi nhuận hoặc mức hoàn tiền cố định. Số dư hiển thị không phải tài khoản ngân hàng và chỉ được rút theo điều kiện, hạn mức, xác minh và phương thức nhận tiền đang áp dụng.',
                    ],
                ],
                [
                    'heading' => 'Hành vi bị cấm',
                    'items' => [
                        'Can thiệp kỹ thuật, tự động hóa trái phép, khai thác lỗ hổng hoặc vượt rate limit.',
                        'Cung cấp tài khoản nhận tiền không thuộc quyền sử dụng hợp pháp hoặc đã được tài khoản khác khai báo.',
                        'Đăng nội dung vi phạm pháp luật, xâm phạm quyền người khác hoặc gây nhầm lẫn về thu nhập/cashback.',
                        'Lạm dụng referral, nhiệm vụ, quà tặng, coupon hoặc quy trình rút tiền.',
                    ],
                ],
                [
                    'heading' => 'Tạm ngừng, thay đổi và chấm dứt',
                    'paragraphs' => [
                        'Mesale có thể tạm ngừng tính năng để bảo trì, bảo mật, đối soát hoặc tuân thủ yêu cầu của đối tác và cơ quan có thẩm quyền. Tài khoản vi phạm có thể bị giới hạn hoặc đình chỉ sau khi xem xét bằng chứng.',
                        'Các thay đổi quan trọng của điều khoản sẽ được công bố trên URL này. Việc tiếp tục sử dụng sau ngày hiệu lực thể hiện sự chấp nhận phiên bản cập nhật, trong giới hạn pháp luật cho phép.',
                    ],
                ],
                [
                    'heading' => 'Hỗ trợ',
                    'paragraphs' => [
                        'Khi cần hỗ trợ hoặc khiếu nại, sử dụng kênh được công bố tại trang Hỗ trợ.',
                    ],
                ],
            ],
        ]);
    }

    public function iosPrivacy(): View
    {
        return $this->render('ios-privacy', [
            'title' => 'Chính sách bảo mật ứng dụng iOS',
            'description' => 'Cách Mê Sale xử lý dữ liệu trong chế độ khám phá sản phẩm và ưu đãi trên iOS.',
            'sections' => [
                [
                    'heading' => 'Dữ liệu được thu thập',
                    'paragraphs' => [
                        'Ứng dụng có thể lưu thông tin tài khoản như họ tên, email, số điện thoại, ảnh đại diện, trạng thái xác minh và tùy chọn hiển thị.',
                        'Khi người dùng dán liên kết sản phẩm, ứng dụng gửi liên kết đó đến máy chủ để nhận diện sản phẩm và tạo liên kết mở sàn phù hợp.',
                        'Nếu người dùng đăng nhập bằng Google hoặc Sign in with Apple, máy chủ lưu định danh nhà cung cấp đã được xác minh để bảo vệ tài khoản.',
                        'Ứng dụng có thể xử lý access token theo phiên, thông tin thiết bị, địa chỉ IP, user agent và nhật ký bảo mật cần thiết để vận hành và chống lạm dụng.',
                    ],
                ],
                [
                    'heading' => 'Mục đích sử dụng',
                    'items' => [
                        'Xác thực tài khoản, khôi phục phiên và bảo vệ đăng nhập.',
                        'Nhận diện liên kết sản phẩm, hiển thị thông tin mua sắm và mở sản phẩm trên sàn.',
                        'Cung cấp mã giảm giá, hướng dẫn sử dụng và hỗ trợ người dùng.',
                        'Phát hiện lỗi, ngăn chặn lạm dụng và duy trì an toàn hệ thống.',
                    ],
                ],
                [
                    'heading' => 'Xóa tài khoản và liên hệ',
                    'paragraphs' => [
                        'Người dùng có thể xóa tài khoản trong ứng dụng tại Tài khoản → Xóa tài khoản hoặc làm theo hướng dẫn tại trang Xóa tài khoản.',
                        'Có thể yêu cầu xem, sửa hoặc xóa dữ liệu qua các kênh tại trang Hỗ trợ. Không gửi mật khẩu hoặc mã OTP cho bộ phận hỗ trợ.',
                    ],
                ],
            ],
        ]);
    }

    public function iosTerms(): View
    {
        return $this->render('ios-terms', [
            'title' => 'Điều khoản ứng dụng iOS',
            'description' => 'Điều kiện sử dụng chế độ khám phá sản phẩm và ưu đãi của Mê Sale trên iOS.',
            'sections' => [
                [
                    'heading' => 'Phạm vi dịch vụ',
                    'paragraphs' => [
                        'Ứng dụng hỗ trợ nhận diện liên kết sản phẩm, khám phá mã giảm giá, xem hướng dẫn mua sắm và mở sản phẩm trên các nền tảng thương mại điện tử được hỗ trợ.',
                        'Mê Sale hoạt động độc lập và không phải ứng dụng chính thức của Shopee, TikTok Shop hoặc Lazada.',
                    ],
                ],
                [
                    'heading' => 'Thông tin sản phẩm và ưu đãi',
                    'paragraphs' => [
                        'Giá, tồn kho, mã giảm giá, phí vận chuyển và điều kiện mua hàng do từng sàn hoặc gian hàng cập nhật và có thể thay đổi.',
                        'Người dùng cần kiểm tra thông tin cuối cùng trên sàn trước khi đặt hàng.',
                    ],
                ],
                [
                    'heading' => 'Trách nhiệm tài khoản',
                    'items' => [
                        'Bảo vệ mật khẩu, mã OTP và thiết bị đăng nhập.',
                        'Không sử dụng ứng dụng để gửi nội dung trái pháp luật, gây hại hoặc lạm dụng hệ thống.',
                        'Thông báo cho bộ phận hỗ trợ khi phát hiện truy cập bất thường.',
                    ],
                ],
                [
                    'heading' => 'Tạm ngừng và thay đổi',
                    'paragraphs' => [
                        'Một số nền tảng, liên kết hoặc mã giảm giá có thể tạm ngừng khi dữ liệu sàn không khả dụng hoặc cần bảo trì.',
                        'Các thay đổi quan trọng đối với phạm vi ứng dụng iOS sẽ được công bố và gửi lại App Review khi cần thiết.',
                    ],
                ],
            ],
        ]);
    }

    public function iosSupport(): View
    {
        return $this->render('ios-support', [
            'title' => 'Hỗ trợ ứng dụng iOS',
            'description' => 'Các kênh hỗ trợ chính thức cho tài khoản và chế độ khám phá sản phẩm của Mê Sale trên iOS.',
            'sections' => [
                [
                    'heading' => 'Nội dung hỗ trợ',
                    'items' => [
                        'Đăng nhập, xác minh email hoặc số điện thoại, OTP, 2FA, Google và Sign in with Apple.',
                        'Nhận diện liên kết sản phẩm, mã giảm giá, sản phẩm đã lưu và mở liên kết trên sàn.',
                        'Tùy chọn hiển thị, bảo mật tài khoản và thông báo lỗi kỹ thuật.',
                        'Yêu cầu truy cập, chỉnh sửa hoặc xóa tài khoản và dữ liệu cá nhân.',
                    ],
                ],
                [
                    'heading' => 'Thông tin cần cung cấp',
                    'paragraphs' => [
                        'Để được xử lý nhanh, hãy cung cấp email hoặc số điện thoại đã đăng ký, mô tả vấn đề và thời điểm xảy ra. Không gửi mật khẩu, OTP, Bearer token, API key hoặc private key.',
                    ],
                ],
            ],
            'show_contacts' => true,
        ]);
    }

    public function iosAccountDeletion(): View
    {
        return $this->render('ios-account-deletion', [
            'title' => 'Xóa tài khoản ứng dụng iOS',
            'description' => 'Cách xóa tài khoản Mê Sale và dữ liệu liên kết từ ứng dụng iOS.',
            'sections' => [
                [
                    'heading' => 'Xóa trong ứng dụng',
                    'items' => [
                        'Đăng nhập ứng dụng Mê Sale.',
                        'Mở tab Tài khoản, chọn Xóa tài khoản.',
                        'Đọc cảnh báo, nhập xác nhận được yêu cầu và hoàn tất xác thực lại.',
                        'Nếu tài khoản có liên kết Apple ID, dùng Sign in with Apple khi được yêu cầu để ngắt quyền cấp trước khi xóa.',
                    ],
                ],
                [
                    'heading' => 'Khi không còn truy cập ứng dụng',
                    'paragraphs' => [
                        'Gửi yêu cầu qua một kênh chính thức tại trang Hỗ trợ iOS. Nêu rõ yêu cầu xóa tài khoản và thông tin đủ để Mê Sale xác minh quyền sở hữu. Không gửi mật khẩu hoặc OTP.',
                    ],
                ],
                [
                    'heading' => 'Dữ liệu sau khi xóa',
                    'paragraphs' => [
                        'Token và phiên đăng nhập bị thu hồi. Dữ liệu tài khoản được xóa hoặc loại bỏ liên kết nhận dạng theo quy trình lưu giữ và nghĩa vụ pháp lý áp dụng.',
                    ],
                ],
            ],
            'show_contacts' => true,
        ]);
    }

    public function support(): View
    {
        return $this->render('support', [
            'title' => 'Hỗ trợ và liên hệ',
            'description' => 'Các kênh hỗ trợ chính thức cho tài khoản và ứng dụng Mesale.',
            'sections' => [
                [
                    'heading' => 'Nội dung hỗ trợ',
                    'items' => [
                        'Đăng nhập, xác minh email/số điện thoại, OTP, 2FA, Google và Sign in with Apple.',
                        'Đơn hàng, cashback, số dư, rút tiền và tài khoản nhận tiền.',
                        'Referral, nhiệm vụ, quà tặng, mã quà, coupon và thông báo.',
                        'Yêu cầu truy cập, chỉnh sửa hoặc xóa tài khoản và dữ liệu cá nhân.',
                    ],
                ],
                [
                    'heading' => 'Thông tin cần cung cấp',
                    'paragraphs' => [
                        'Để được xử lý nhanh, hãy cung cấp mã thành viên hoặc email/số điện thoại đã đăng ký, mô tả vấn đề, thời điểm xảy ra và mã đơn hoặc mã đối soát nếu có. Không gửi mật khẩu, OTP, Bearer token, API key, private key hoặc ảnh chứa đầy đủ thông tin tài khoản nhận tiền.',
                    ],
                ],
            ],
            'show_contacts' => true,
        ]);
    }

    public function accountDeletion(): View
    {
        return $this->render('account-deletion', [
            'title' => 'Hướng dẫn xóa tài khoản',
            'description' => 'Cách yêu cầu xóa cùng một tài khoản Mesale trên ứng dụng và website.',
            'sections' => [
                [
                    'heading' => 'Xóa trong ứng dụng',
                    'items' => [
                        'Đăng nhập ứng dụng Mesale.',
                        'Mở tab Tài khoản, chọn Xóa tài khoản.',
                        'Đọc cảnh báo, nhập xác nhận được yêu cầu và hoàn tất xác thực lại.',
                        'Nếu tài khoản có liên kết Apple ID, dùng Sign in with Apple khi được yêu cầu để ngắt quyền cấp trước khi xóa.',
                    ],
                ],
                [
                    'heading' => 'Xóa trên website',
                    'paragraphs' => [
                        'Đăng nhập mesale.vn, mở trang Hồ sơ và sử dụng mục xóa tài khoản nếu quản trị viên đang bật chức năng tự xóa. Việc xóa áp dụng cho cùng user ID, dữ liệu tài khoản website và ứng dụng.',
                    ],
                ],
                [
                    'heading' => 'Khi không còn truy cập ứng dụng hoặc tài khoản',
                    'paragraphs' => [
                        'Gửi yêu cầu qua một kênh chính thức tại trang Hỗ trợ. Nêu rõ yêu cầu xóa tài khoản và thông tin đủ để Mesale xác minh quyền sở hữu. Không gửi mật khẩu hoặc OTP.',
                    ],
                ],
                [
                    'heading' => 'Dữ liệu sau khi xóa',
                    'paragraphs' => [
                        'Token và phiên đăng nhập bị thu hồi. Một số hồ sơ đối soát tài chính hoặc hoa hồng có thể được lưu ở dạng đã loại bỏ liên kết tới thông tin định danh khi nghĩa vụ pháp lý hoặc kế toán yêu cầu.',
                    ],
                ],
            ],
            'show_contacts' => true,
        ]);
    }

    private function render(string $slug, array $page): View
    {
        $contacts = $this->supportContacts();

        return view('pages.store-compliance', array_merge($page, [
            'slug' => $slug,
            'contacts' => $contacts,
            'supportConfigured' => collect($contacts)->contains(
                static fn (array $contact): bool => $contact['value'] !== null
            ),
        ]));
    }

    /** @return array<int, array{label: string, value: ?string, href: ?string}> */
    private function supportContacts(): array
    {
        $email = trim((string) Setting::getVal('support_email', ''));
        $email = filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null;

        $hotline = trim((string) Setting::getVal('support_hotline', '')) ?: null;

        return [
            ['label' => 'Email', 'value' => $email, 'href' => $email ? 'mailto:'.$email : null],
            ['label' => 'Hotline', 'value' => $hotline, 'href' => $hotline ? 'tel:'.preg_replace('/[^0-9+]/', '', $hotline) : null],
            $this->externalContact('Zalo', 'zalo_link'),
            $this->externalContact('Facebook', 'facebook_link'),
            $this->externalContact('Telegram', 'telegram_link'),
        ];
    }

    /** @return array{label: string, value: ?string, href: ?string} */
    private function externalContact(string $label, string $settingKey): array
    {
        $url = trim((string) Setting::getVal($settingKey, ''));
        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
        $url = filter_var($url, FILTER_VALIDATE_URL) && in_array($scheme, ['http', 'https'], true)
            ? $url
            : null;

        return ['label' => $label, 'value' => $url, 'href' => $url];
    }
}
