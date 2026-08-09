<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\LinkHelper;
use App\Http\Controllers\Controller;
use App\Http\Controllers\HomeController;
use App\Models\ActivityLog;
use App\Models\PageBlock;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Controller cho trình dựng trang (Page Builder) kiểu WordPress của homepage.
 * Cho phép Admin kéo-thả sắp xếp, bật/tắt, thêm/xoá và chỉnh sửa nội dung từng block
 * kèm khung xem trước trực tiếp (live preview), thay cho các form rời rạc trước đây.
 */
class AppearanceController extends Controller
{
    /** Số block tối đa cho phép trên một trang, chặn nhồi dữ liệu làm quá tải trang chủ. */
    protected const MAX_BLOCKS = 40;

    /** Số mục tối đa trong một trường lặp lại (gallery, FAQ, đánh giá thành viên...). */
    protected const MAX_REPEATER_ITEMS = 30;

    /**
     * Định nghĩa toàn bộ loại block khả dụng: nhãn, icon, có phải block đơn (singleton),
     * có thể thêm/xoá (repeatable), giá trị mặc định và danh sách field để render panel chỉnh sửa.
     * Đây là "nguồn chân lý duy nhất" dùng chung cho cả admin builder lẫn giá trị mặc định khi thêm block.
     */
    public static function blockDefinitions(): array
    {
        $colorOptions = [
            'orange' => 'Cam', 'green' => 'Xanh lá', 'blue' => 'Xanh dương',
            'purple' => 'Tím', 'red' => 'Đỏ', 'yellow' => 'Vàng', 'pink' => 'Hồng',
        ];

        return [
            // ---------------- HERO ----------------
            'hero' => [
                'label' => __('Hero (Đầu trang)'),
                'icon' => 'layout-template',
                'singleton' => true,
                'addable' => false,
                'defaults' => [
                    'hp_hero_layout' => 'classic',
                    'hp_hero_badge' => 'Hoàn tiền mua sắm Shopee lên đến {cashback_rate}% hoa hồng',
                    'hp_hero_title_1' => 'Mua Sắm Shopee',
                    'hp_hero_title_2' => 'Nhận Lại Tiền Hoàn',
                    'hp_hero_title_3' => 'Dễ Dàng',
                    'hp_hero_description' => '',
                    'hp_hero_placeholder' => 'Dán link sản phẩm Shopee tại đây...',
                    'hp_hero_btn_text' => 'Lấy Link Hoàn Tiền',
                    'hp_hero_right_type' => 'slider',
                    'hp_hero_right_video_url' => '',
                    'hp_hero_right_video_poster' => '',
                    'hp_show_demo_modal' => '1',
                    'hp_demo_modal_type' => 'interactive',
                    'hp_demo_modal_custom_content' => '',
                    'hp_show_notice_modal' => '1',
                    'hp_notice_modal_content' => '',
                    'hp_stats_title' => 'Thống kê hệ thống thực tế',
                    'hp_stats_clicks_label' => 'Lượt nhấp hoàn tiền',
                    'hp_stats_users_label' => 'Thành viên hoạt động',
                    'hp_stats_paid_label' => 'Tổng hoa hồng đã chi trả',
                    'hp_stats_clicks_value' => '48200',
                    'hp_stats_users_value' => '1450',
                    'hp_stats_paid_value' => '125000000',
                ],
                'fields' => [
                    ['key' => 'hp_hero_layout', 'label' => __('Bố cục Hero'), 'type' => 'select', 'options' => [
                        'classic' => __('Cổ điển (2 cột + slider)'),
                        'centered' => __('Căn giữa (tập trung CTA)'),
                        'split' => __('Chia đôi (kèm thống kê)'),
                    ]],
                    ['key' => 'hp_hero_badge', 'label' => __('Nhãn badge'), 'type' => 'text', 'help' => __('Dùng {cashback_rate} để chèn tỷ lệ hoàn tiền.')],
                    ['key' => 'hp_hero_title_1', 'label' => __('Tiêu đề dòng 1'), 'type' => 'text'],
                    ['key' => 'hp_hero_title_2', 'label' => __('Tiêu đề nhấn mạnh (màu cam)'), 'type' => 'text'],
                    ['key' => 'hp_hero_title_3', 'label' => __('Tiêu đề dòng cuối'), 'type' => 'text'],
                    ['key' => 'hp_hero_description', 'label' => __('Mô tả'), 'type' => 'textarea'],
                    ['key' => 'hp_hero_placeholder', 'label' => __('Placeholder ô nhập link'), 'type' => 'text'],
                    ['key' => 'hp_hero_btn_text', 'label' => __('Chữ trên nút'), 'type' => 'text'],
                    ['key' => 'hp_hero_right_type', 'label' => __('Nội dung cột phải (bố cục cổ điển)'), 'type' => 'select', 'options' => [
                        'slider' => __('Slider Banner'),
                        'video' => __('Video giới thiệu'),
                    ]],
                    ['key' => 'hp_hero_right_video_url', 'label' => __('Link video (YouTube hoặc .mp4)'), 'type' => 'media', 'condition' => ['hp_hero_right_type' => 'video']],
                    ['key' => 'hp_hero_right_video_poster', 'label' => __('Ảnh poster video'), 'type' => 'image', 'condition' => ['hp_hero_right_type' => 'video']],
                    ['key' => 'hp_show_demo_modal', 'label' => __('Hiện nút "Hướng dẫn lấy link"'), 'type' => 'toggle'],
                    ['key' => 'hp_demo_modal_type', 'label' => __('Kiểu popup hướng dẫn'), 'type' => 'select', 'options' => [
                        'interactive' => __('Mô phỏng tương tác'),
                        'custom' => __('Nội dung / video tùy chỉnh'),
                    ], 'condition' => ['hp_show_demo_modal' => '1']],
                    ['key' => 'hp_demo_modal_custom_content', 'label' => __('Nội dung hướng dẫn tùy chỉnh'), 'type' => 'textarea', 'help' => __('Dán link YouTube/video hoặc mã HTML.'), 'condition' => ['hp_demo_modal_type' => 'custom']],
                    ['key' => 'hp_show_notice_modal', 'label' => __('Hiện nút "Lưu ý khi sử dụng"'), 'type' => 'toggle'],
                    ['key' => 'hp_notice_modal_content', 'label' => __('Nội dung lưu ý sử dụng'), 'type' => 'richtext', 'condition' => ['hp_show_notice_modal' => '1']],
                    ['key' => 'hp_stats_title', 'label' => __('Tiêu đề thống kê (bố cục chia đôi)'), 'type' => 'text', 'condition' => ['hp_hero_layout' => 'split']],
                    ['key' => 'hp_stats_clicks_label', 'label' => __('Nhãn lượt nhấp'), 'type' => 'text', 'condition' => ['hp_hero_layout' => 'split']],
                    ['key' => 'hp_stats_clicks_value', 'label' => __('Số ảo cộng thêm (lượt nhấp)'), 'type' => 'text', 'sanitize' => 'int', 'min' => 0, 'help' => __('Cộng thêm vào số lượt nhấp thực tế để tăng độ tin cậy.'), 'condition' => ['hp_hero_layout' => 'split']],
                    ['key' => 'hp_stats_users_label', 'label' => __('Nhãn thành viên'), 'type' => 'text', 'condition' => ['hp_hero_layout' => 'split']],
                    ['key' => 'hp_stats_users_value', 'label' => __('Số ảo cộng thêm (thành viên)'), 'type' => 'text', 'sanitize' => 'int', 'min' => 0, 'help' => __('Cộng thêm vào số thành viên thực tế.'), 'condition' => ['hp_hero_layout' => 'split']],
                    ['key' => 'hp_stats_paid_label', 'label' => __('Nhãn tiền đã chi'), 'type' => 'text', 'condition' => ['hp_hero_layout' => 'split']],
                    ['key' => 'hp_stats_paid_value', 'label' => __('Số ảo cộng thêm (tiền đã chi)'), 'type' => 'text', 'sanitize' => 'int', 'min' => 0, 'help' => __('Cộng thêm vào tổng hoa hồng đã chi trả thực tế (đơn vị VND).'), 'condition' => ['hp_hero_layout' => 'split']],
                ],
            ],

            // ---------------- TIMELINE ----------------
            'timeline' => [
                'label' => __('Quy trình hoàn tiền'),
                'icon' => 'activity',
                'singleton' => true,
                'addable' => false,
                'defaults' => [
                    'hp_timeline_badge' => 'Lộ trình hoàn tiền Shopee',
                    'hp_timeline_title' => 'Quy Trình Nhận Hoàn Tiền Siêu Tốc',
                    'hp_timeline_subtitle' => 'Hiểu rõ quy trình ghi nhận đơn hàng và thời gian tiền hoàn về tài khoản của bạn.',
                    'hp_timeline_step_1_badge' => 'Bước 1: Mua hàng', 'hp_timeline_step_1_title' => 'Ngày mua', 'hp_timeline_step_1_tag' => 'Hôm nay',
                    'hp_timeline_step_1_desc' => 'Bạn copy link Shopee dán vào hệ thống, nhận link rút gọn và tiến hành đặt mua hàng.',
                    'hp_timeline_step_2_badge' => 'Bước 2: Đối soát', 'hp_timeline_step_2_title' => 'Ghi nhận', 'hp_timeline_step_2_tag' => 'Ngày mai',
                    'hp_timeline_step_2_desc' => 'Shopee ghi nhận đơn hàng tạm tính và tự động đồng bộ hiển thị trong lịch sử ví của bạn.',
                    'hp_timeline_step_3_badge' => 'Bước 3: Thực nhận', 'hp_timeline_step_3_title' => 'Có thể rút', 'hp_timeline_step_3_tag' => '7 ngày',
                    'hp_timeline_step_3_desc' => 'Sau khi Shopee đối soát kỳ hoàn thành (khoảng 7 ngày khi nhận hàng), tiền khả dụng sẽ được cộng vào ví và có thể rút ngay.',
                ],
                'fields' => array_merge(
                    [
                        ['key' => 'hp_timeline_badge', 'label' => __('Nhãn badge'), 'type' => 'text'],
                        ['key' => 'hp_timeline_title', 'label' => __('Tiêu đề'), 'type' => 'text'],
                        ['key' => 'hp_timeline_subtitle', 'label' => __('Mô tả'), 'type' => 'textarea'],
                    ],
                    self::stepFields('hp_timeline_step_', 3, ['badge' => 'Nhãn bước', 'title' => 'Tiêu đề', 'tag' => 'Thẻ thời gian', 'desc' => 'Mô tả'])
                ),
            ],

            // ---------------- STEPS ----------------
            'steps' => [
                'label' => __('Hướng dẫn 3 bước'),
                'icon' => 'list-ordered',
                'singleton' => true,
                'addable' => false,
                'defaults' => [
                    'hp_steps_badge' => 'Hướng dẫn nhanh',
                    'hp_steps_title' => 'Cách Nhận Hoàn Tiền Shopee Trong 3 Bước',
                    'hp_steps_subtitle' => 'Chỉ mất chưa tới một phút để biến mỗi đơn hàng Shopee thành tiền hoàn về ví của bạn.',
                    'hp_step_1_title' => 'Sao chép link sản phẩm', 'hp_step_1_icon' => 'copy',
                    'hp_step_1_desc' => 'Mở ứng dụng hoặc trang web Shopee, tìm sản phẩm bạn yêu thích rồi sao chép đường dẫn (link) sản phẩm đó.',
                    'hp_step_2_title' => 'Dán link & Lấy link hoàn tiền', 'hp_step_2_icon' => 'search',
                    'hp_step_2_desc' => 'Dán link vừa sao chép vào ô tìm kiếm tại trang chủ, hệ thống sẽ phân tích và tạo ngay link hoàn tiền cho bạn.',
                    'hp_step_3_title' => 'Mua hàng và nhận tiền hoàn', 'hp_step_3_icon' => 'check-square',
                    'hp_step_3_desc' => 'Hoàn tất đặt hàng qua link hoàn tiền, đơn hàng được ghi nhận tự động và tiền hoàn sẽ cộng vào ví của bạn.',
                ],
                'fields' => array_merge(
                    [
                        ['key' => 'hp_steps_badge', 'label' => __('Nhãn badge'), 'type' => 'text'],
                        ['key' => 'hp_steps_title', 'label' => __('Tiêu đề'), 'type' => 'text'],
                        ['key' => 'hp_steps_subtitle', 'label' => __('Mô tả'), 'type' => 'textarea'],
                    ],
                    self::stepFields('hp_step_', 3, ['title' => 'Tiêu đề', 'icon' => 'Icon', 'desc' => 'Mô tả'])
                ),
            ],

            // ---------------- FEATURES ----------------
            'features' => [
                'label' => __('Điểm nổi bật'),
                'icon' => 'star',
                'singleton' => true,
                'addable' => false,
                'defaults' => [
                    'hp_features_badge' => 'Ưu điểm vượt trội',
                    'hp_features_title' => 'Tại Sao Nên Chọn Nền Tảng Của Chúng Tôi?',
                    'hp_features_subtitle' => 'Những giá trị thiết thực giúp bạn tiết kiệm nhiều hơn trên mỗi đơn hàng mua sắm.',
                    'hp_feature_1_title' => 'Tỷ lệ hoàn tiền cao', 'hp_feature_1_icon' => 'percent', 'hp_feature_1_color' => 'orange',
                    'hp_feature_1_desc' => 'Nhận lại phần lớn số tiền hoa hồng mà sàn thương mại điện tử chi trả cho mỗi đơn hàng tiếp thị liên kết thành công.',
                    'hp_feature_2_title' => 'Hệ thống 2 tầng MLM', 'hp_feature_2_icon' => 'git-branch', 'hp_feature_2_color' => 'green',
                    'hp_feature_2_desc' => 'Giới thiệu bạn bè đăng ký để nhận thêm hoa hồng từ F1 và F2 trên mỗi đơn hoàn tiền của họ, tạo nguồn thu nhập thụ động.',
                    'hp_feature_3_title' => 'Thanh toán linh hoạt', 'hp_feature_3_icon' => 'banknote', 'hp_feature_3_color' => 'blue',
                    'hp_feature_3_desc' => 'Hỗ trợ rút tiền qua mã QR ngân hàng (VietQR) hoặc ví điện tử với hạn mức tối thiểu thấp, xử lý nhanh gọn.',
                    'hp_feature_4_title' => 'Ghi nhận đơn tự động', 'hp_feature_4_icon' => 'history', 'hp_feature_4_color' => 'purple',
                    'hp_feature_4_desc' => 'Đơn hàng được đồng bộ tự động qua API đối soát và hiển thị ngay trong bảng lịch sử ví của bạn.',
                ],
                'fields' => array_merge(
                    [
                        ['key' => 'hp_features_badge', 'label' => __('Nhãn badge'), 'type' => 'text'],
                        ['key' => 'hp_features_title', 'label' => __('Tiêu đề'), 'type' => 'text'],
                        ['key' => 'hp_features_subtitle', 'label' => __('Mô tả'), 'type' => 'textarea'],
                    ],
                    self::featureFields($colorOptions)
                ),
            ],

            // ---------------- STATS ----------------
            'stats' => [
                'label' => __('Thống kê hệ thống'),
                'icon' => 'bar-chart-3',
                'singleton' => true,
                'addable' => true,
                'defaults' => [
                    'hp_stats_title' => 'Thống kê hệ thống thực tế',
                    'hp_stats_clicks_label' => 'Lượt nhấp hoàn tiền', 'hp_stats_clicks_value' => '48200',
                    'hp_stats_users_label' => 'Thành viên hoạt động', 'hp_stats_users_value' => '1450',
                    'hp_stats_paid_label' => 'Tổng hoa hồng đã chi trả', 'hp_stats_paid_value' => '125000000',
                ],
                'fields' => [
                    ['key' => 'hp_stats_title', 'label' => __('Tiêu đề khu vực'), 'type' => 'text'],
                    ['key' => 'hp_stats_clicks_label', 'label' => __('Nhãn lượt nhấp'), 'type' => 'text'],
                    ['key' => 'hp_stats_clicks_value', 'label' => __('Số ảo cộng thêm (lượt nhấp)'), 'type' => 'text', 'sanitize' => 'int', 'min' => 0],
                    ['key' => 'hp_stats_users_label', 'label' => __('Nhãn thành viên'), 'type' => 'text'],
                    ['key' => 'hp_stats_users_value', 'label' => __('Số ảo cộng thêm (thành viên)'), 'type' => 'text', 'sanitize' => 'int', 'min' => 0],
                    ['key' => 'hp_stats_paid_label', 'label' => __('Nhãn tiền đã chi'), 'type' => 'text'],
                    ['key' => 'hp_stats_paid_value', 'label' => __('Số ảo cộng thêm (tiền đã chi)'), 'type' => 'text', 'sanitize' => 'int', 'min' => 0],
                ],
            ],

            // ---------------- COUPONS ----------------
            'coupons' => [
                'label' => __('Mã giảm giá Shopee'),
                'icon' => 'ticket',
                'singleton' => true,
                'addable' => false,
                'defaults' => [],
                'fields' => [],
                'note' => __('Khu vực này hiển thị tự động các mã giảm giá Shopee còn hạn từ hệ thống. Không có nội dung cần chỉnh.'),
            ],

            // ---------------- BLOG ----------------
            'blog' => [
                'label' => __('Tin tức & Blog'),
                'icon' => 'book-open',
                'singleton' => true,
                'addable' => false,
                'defaults' => [],
                'fields' => [],
                'note' => __('Khu vực này hiển thị tự động 3 bài viết blog mới nhất. Không có nội dung cần chỉnh.'),
            ],

            // ---------------- DANH SÁCH LINK ĐÃ TẠO ----------------
            'created_links' => [
                'label' => __('Danh sách link đã tạo'),
                'icon' => 'link-2',
                'singleton' => true,
                'addable' => true,
                'defaults' => [
                    'title' => 'Link Hoàn Tiền Của Bạn',
                    'subtitle' => 'Danh sách các sản phẩm bạn vừa chuyển đổi link và trạng thái đối soát ghi nhận đơn.',
                    'limit' => 6,
                ],
                'fields' => [
                    ['key' => 'title', 'label' => __('Tiêu đề khu vực'), 'type' => 'text'],
                    ['key' => 'subtitle', 'label' => __('Mô tả ngắn'), 'type' => 'textarea'],
                    ['key' => 'limit', 'label' => __('Số lượng link hiển thị'), 'type' => 'number', 'sanitize' => 'int', 'min' => 1, 'max' => 24, 'help' => __('Số lượng link mới nhất được hiển thị trên trang chủ (từ 1 đến 24).')],
                ],
            ],

            // ---------------- HTML tùy chỉnh ----------------
            'html' => [
                'label' => __('HTML / Văn bản tùy chỉnh'),
                'icon' => 'code',
                'singleton' => false,
                'addable' => true,
                'defaults' => [
                    'content' => '',
                ],
                'fields' => [
                    ['key' => 'content', 'label' => __('Nội dung HTML'), 'type' => 'richtext', 'ai' => true],
                ],
            ],

            // ---------------- GALLERY / Ảnh + Văn bản ----------------
            'gallery' => [
                'label' => __('Ảnh + Văn bản / Gallery'),
                'icon' => 'images',
                'singleton' => false,
                'addable' => true,
                'defaults' => [
                    'title' => 'Bộ sưu tập hình ảnh',
                    'subtitle' => '',
                    'layout' => 'grid',
                    'items' => [
                        ['image' => '', 'title' => '', 'text' => '', 'link' => ''],
                    ],
                ],
                'fields' => [
                    ['key' => 'title', 'label' => __('Tiêu đề khu vực'), 'type' => 'text'],
                    ['key' => 'subtitle', 'label' => __('Mô tả'), 'type' => 'textarea'],
                    ['key' => 'layout', 'label' => __('Kiểu hiển thị'), 'type' => 'select', 'options' => [
                        'grid' => __('Lưới thẻ (Grid)'),
                        'split' => __('Ảnh + Văn bản 2 cột'),
                        'logos' => __('Dải logo đối tác'),
                    ]],
                    ['key' => 'items', 'label' => __('Danh sách mục'), 'type' => 'repeater', 'subfields' => [
                        ['key' => 'image', 'label' => __('Hình ảnh'), 'type' => 'image'],
                        ['key' => 'title', 'label' => __('Tiêu đề'), 'type' => 'text'],
                        ['key' => 'text', 'label' => __('Mô tả'), 'type' => 'textarea'],
                        ['key' => 'link', 'label' => __('Liên kết (tùy chọn)'), 'type' => 'text', 'sanitize' => 'url'],
                    ]],
                ],
            ],

            // ---------------- FAQ (Câu hỏi thường gặp) ----------------
            'faq' => [
                'label' => __('Câu hỏi thường gặp (FAQ)'),
                'icon' => 'help-circle',
                'singleton' => false,
                'addable' => true,
                'defaults' => [
                    'badge' => 'Giải đáp thắc mắc',
                    'title' => 'Câu Hỏi Thường Gặp',
                    'subtitle' => 'Những thắc mắc phổ biến nhất về cách nhận và rút tiền hoàn.',
                    'items' => [
                        ['question' => 'Tôi nhận lại được bao nhiêu tiền hoàn cho mỗi đơn?', 'answer' => ''],
                        ['question' => 'Bao lâu thì tiền hoàn về tài khoản của tôi?', 'answer' => ''],
                        ['question' => 'Số tiền tối thiểu để rút là bao nhiêu?', 'answer' => ''],
                    ],
                ],
                'fields' => [
                    ['key' => 'badge', 'label' => __('Nhãn badge'), 'type' => 'text'],
                    ['key' => 'title', 'label' => __('Tiêu đề'), 'type' => 'text'],
                    ['key' => 'subtitle', 'label' => __('Mô tả'), 'type' => 'textarea'],
                    ['key' => 'items', 'label' => __('Danh sách câu hỏi'), 'type' => 'repeater', 'subfields' => [
                        ['key' => 'question', 'label' => __('Câu hỏi'), 'type' => 'text'],
                        ['key' => 'answer', 'label' => __('Câu trả lời'), 'type' => 'textarea'],
                    ]],
                ],
            ],

            // ---------------- ĐÁNH GIÁ THÀNH VIÊN (Testimonials) ----------------
            'testimonials' => [
                'label' => __('Đánh giá thành viên'),
                'icon' => 'message-square-quote',
                'singleton' => false,
                'addable' => true,
                'defaults' => [
                    'badge' => 'Cảm nhận thành viên',
                    'title' => 'Hàng Nghìn Thành Viên Đã Tin Dùng',
                    'subtitle' => 'Những chia sẻ thực tế từ cộng đồng nhận hoàn tiền mỗi ngày.',
                    'layout' => 'grid',
                    'items' => [
                        ['avatar' => '', 'name' => 'Nguyễn Thị Mai', 'role' => 'Thành viên VIP', 'rating' => '5', 'content' => 'Mình đã nhận lại được kha khá tiền chỉ nhờ dán link trước khi mua. Rất đáng để dùng!'],
                        ['avatar' => '', 'name' => 'Trần Quốc Huy', 'role' => 'Thành viên', 'rating' => '5', 'content' => 'Tiền hoàn về đều đặn, rút tiền nhanh gọn qua ngân hàng. Hệ thống rất minh bạch.'],
                        ['avatar' => '', 'name' => 'Lê Thanh Hà', 'role' => 'Cộng tác viên', 'rating' => '5', 'content' => 'Nhờ giới thiệu bạn bè mà mình có thêm nguồn hoa hồng thụ động mỗi tháng.'],
                    ],
                ],
                'fields' => [
                    ['key' => 'badge', 'label' => __('Nhãn badge'), 'type' => 'text'],
                    ['key' => 'title', 'label' => __('Tiêu đề'), 'type' => 'text'],
                    ['key' => 'subtitle', 'label' => __('Mô tả'), 'type' => 'textarea'],
                    ['key' => 'layout', 'label' => __('Kiểu hiển thị'), 'type' => 'select', 'options' => [
                        'grid' => __('Lưới thẻ (Grid)'),
                        'slider' => __('Trượt ngang (Slider)'),
                    ]],
                    ['key' => 'items', 'label' => __('Danh sách đánh giá'), 'type' => 'repeater', 'subfields' => [
                        ['key' => 'avatar', 'label' => __('Ảnh đại diện (tùy chọn)'), 'type' => 'image'],
                        ['key' => 'name', 'label' => __('Tên thành viên'), 'type' => 'text'],
                        ['key' => 'role', 'label' => __('Vai trò / mô tả ngắn'), 'type' => 'text'],
                        ['key' => 'rating', 'label' => __('Số sao (1-5)'), 'type' => 'text'],
                        ['key' => 'content', 'label' => __('Nội dung đánh giá'), 'type' => 'textarea'],
                    ]],
                ],
            ],

            // ---------------- CTA (Dải kêu gọi hành động) ----------------
            'cta' => [
                'label' => __('Dải kêu gọi hành động (CTA)'),
                'icon' => 'megaphone',
                'singleton' => false,
                'addable' => true,
                'defaults' => [
                    'style' => 'gradient',
                    'icon' => 'rocket',
                    'title' => 'Bắt Đầu Nhận Hoàn Tiền Ngay Hôm Nay',
                    'subtitle' => 'Đăng ký miễn phí, dán link Shopee và nhận lại tiền cho mỗi đơn hàng.',
                    'btn_text' => 'Đăng ký miễn phí',
                    'btn_link' => '',
                    'btn2_text' => '',
                    'btn2_link' => '',
                ],
                'fields' => [
                    ['key' => 'style', 'label' => __('Kiểu nền'), 'type' => 'select', 'options' => [
                        'gradient' => __('Gradient cam nổi bật'),
                        'solid' => __('Màu thương hiệu đậm'),
                        'soft' => __('Nền nhạt tinh tế'),
                    ]],
                    ['key' => 'icon', 'label' => __('Icon'), 'type' => 'icon'],
                    ['key' => 'title', 'label' => __('Tiêu đề'), 'type' => 'text'],
                    ['key' => 'subtitle', 'label' => __('Mô tả'), 'type' => 'textarea'],
                    ['key' => 'btn_text', 'label' => __('Chữ nút chính'), 'type' => 'text'],
                    ['key' => 'btn_link', 'label' => __('Liên kết nút chính'), 'type' => 'text', 'sanitize' => 'url', 'help' => __('Để trống sẽ tự trỏ tới trang đăng ký.')],
                    ['key' => 'btn2_text', 'label' => __('Chữ nút phụ (tùy chọn)'), 'type' => 'text'],
                    ['key' => 'btn2_link', 'label' => __('Liên kết nút phụ'), 'type' => 'text', 'sanitize' => 'url'],
                ],
            ],

            // ---------------- BẢNG XẾP HẠNG HOÀN TIỀN (Leaderboard) ----------------
            'leaderboard' => [
                'label' => __('Bảng xếp hạng hoàn tiền'),
                'icon' => 'trophy',
                'singleton' => true,
                'addable' => true,
                'defaults' => [
                    'badge' => 'Bảng vàng vinh danh',
                    'title' => 'Bảng Xếp Hạng Cao Thủ Hoàn Tiền',
                    'subtitle' => 'Nơi tôn vinh những thành viên tích cực nhất hệ thống hoàn tiền Shopee.',
                    'show_orders' => '1',
                    'show_cashback' => '1',
                    'show_checkin' => '1',
                    'show_referral' => '0',
                    'show_balance' => '0',
                    'limit' => '5',
                    'link_text' => 'Xem bảng xếp hạng đầy đủ',
                ],
                'fields' => [
                    ['key' => 'badge', 'label' => __('Nhãn badge'), 'type' => 'text'],
                    ['key' => 'title', 'label' => __('Tiêu đề'), 'type' => 'text'],
                    ['key' => 'subtitle', 'label' => __('Mô tả'), 'type' => 'textarea'],
                    ['key' => 'show_orders', 'label' => __('Hiện tab Top Đơn Hàng'), 'type' => 'toggle'],
                    ['key' => 'show_cashback', 'label' => __('Hiện tab Top Tiền Hoàn'), 'type' => 'toggle'],
                    ['key' => 'show_checkin', 'label' => __('Hiện tab Top Điểm Danh'), 'type' => 'toggle'],
                    ['key' => 'show_referral', 'label' => __('Hiện tab Top Giới Thiệu'), 'type' => 'toggle'],
                    ['key' => 'show_balance', 'label' => __('Hiện tab Top Số Dư'), 'type' => 'toggle'],
                    ['key' => 'limit', 'label' => __('Số thành viên mỗi bảng'), 'type' => 'text', 'sanitize' => 'int', 'min' => 1, 'max' => 10, 'help' => __('Nhập từ 1 đến 10.')],
                    ['key' => 'link_text', 'label' => __('Chữ nút "Xem đầy đủ"'), 'type' => 'text', 'help' => __('Để trống để ẩn nút liên kết sang trang bảng xếp hạng.')],
                ],
                'note' => __('Mỗi bảng lấy dữ liệu thật từ hệ thống (giống trang /ranking). Tab chỉ hiển thị khi đồng thời được bật ở đây và ở Cài đặt > Bảng xếp hạng; tên hiển thị cũng tuân theo cấu hình bảo mật tại đó.'),
            ],
        ];
    }

    /** Sinh nhanh danh sách field cho các "bước" lặp lại (timeline/steps). */
    protected static function stepFields(string $prefix, int $count, array $subs): array
    {
        $fields = [];
        for ($i = 1; $i <= $count; $i++) {
            foreach ($subs as $sub => $label) {
                $type = $sub === 'desc' ? 'textarea' : ($sub === 'icon' ? 'icon' : 'text');
                $fields[] = [
                    'key' => "{$prefix}{$i}_{$sub}",
                    'label' => __(':label (Mục :n)', ['label' => __($label), 'n' => $i]),
                    'type' => $type,
                    'group' => __('Mục :n', ['n' => $i]),
                ];
            }
        }
        return $fields;
    }

    /** Sinh nhanh danh sách field cho 4 thẻ "Điểm nổi bật". */
    protected static function featureFields(array $colorOptions): array
    {
        $fields = [];
        for ($i = 1; $i <= 4; $i++) {
            $g = __('Thẻ :n', ['n' => $i]);
            $fields[] = ['key' => "hp_feature_{$i}_title", 'label' => __('Tiêu đề (Thẻ :n)', ['n' => $i]), 'type' => 'text', 'group' => $g];
            $fields[] = ['key' => "hp_feature_{$i}_icon", 'label' => __('Icon (Thẻ :n)', ['n' => $i]), 'type' => 'icon', 'group' => $g];
            $fields[] = ['key' => "hp_feature_{$i}_color", 'label' => __('Màu (Thẻ :n)', ['n' => $i]), 'type' => 'select', 'options' => $colorOptions, 'group' => $g];
            $fields[] = ['key' => "hp_feature_{$i}_desc", 'label' => __('Mô tả (Thẻ :n)', ['n' => $i]), 'type' => 'textarea', 'group' => $g];
        }
        return $fields;
    }

    /**
     * Hiển thị trình dựng trang (Page Builder) với danh sách block hiện tại và định nghĩa block.
     */
    public function index()
    {
        // Tự khởi tạo Hero nếu bảng rỗng (trường hợp cài mới chưa qua migration seed)
        if (!PageBlock::where('page', 'home')->exists()) {
            $defs = self::blockDefinitions();
            PageBlock::create([
                'page' => 'home', 'type' => 'hero', 'name' => $defs['hero']['label'],
                'settings' => $defs['hero']['defaults'], 'sort_order' => 0, 'enabled' => true,
            ]);
        }

        $blocks = PageBlock::forPage('home');
        $definitions = self::blockDefinitions();
        $aiEnabled = Setting::getVal('ai_status', '0') === '1';

        return view('admin.appearance.index', compact('blocks', 'definitions', 'aiEnabled'));
    }

    /**
     * Lưu (xuất bản) toàn bộ cấu trúc block vào database.
     * Nhận mảng block JSON từ trình builder, đồng bộ hoá: cập nhật/tạo mới/xoá.
     */
    public function publish(Request $request)
    {
        if (config('app.demo')) {
            return response()->json([
                'success' => false,
                'message' => __('Không thể lưu thay đổi trong phiên bản thử nghiệm (Demo).'),
            ], 422);
        }

        $blocks = $this->normalizeBlocks($request->input('blocks', '[]'));

        // CHỐNG MẤT DỮ LIỆU: từ chối lưu khi danh sách block rỗng.
        // Thao tác lưu luôn xoá những block không còn trong danh sách gửi lên, nên nếu một request
        // hỏng (mất tham số, JSON sai định dạng, mất kết nối giữa chừng) gửi mảng rỗng thì toàn bộ
        // giao diện trang chủ sẽ bị xoá sạch. Trình dựng trang không bao giờ cho phép xoá hết block
        // nên mảng rỗng chắc chắn là dữ liệu lỗi.
        if (empty($blocks)) {
            return response()->json([
                'success' => false,
                'message' => __('Dữ liệu giao diện gửi lên không hợp lệ hoặc rỗng. Vui lòng tải lại trang và thử lại.'),
            ], 422);
        }

        $keptIds = [];

        DB::transaction(function () use ($blocks, &$keptIds) {
            foreach ($blocks as $index => $b) {
                $data = [
                    'page' => 'home',
                    'type' => $b['type'],
                    'name' => $b['name'],
                    'settings' => $b['settings'],
                    'sort_order' => $index,
                    'enabled' => $b['enabled'],
                ];

                // BẢO MẬT: chỉ cập nhật block thuộc đúng trang 'home' và mỗi bản ghi chỉ được ghi
                // đè một lần. Nếu không ràng buộc cột page, request giả mạo có thể gửi id của block
                // thuộc trang khác để "cướp" bản ghi đó; còn nếu không chặn id lặp, hai block cùng
                // id sẽ ghi đè lên nhau và làm mất một block.
                $id = $b['id'];
                if ($id && !in_array($id, $keptIds, true) && ($model = PageBlock::where('page', 'home')->find($id))) {
                    $model->update($data);
                    $keptIds[] = $model->id;
                } else {
                    $model = PageBlock::create($data);
                    $keptIds[] = $model->id;
                }
            }

            // Xoá các block đã bị người dùng gỡ khỏi builder
            PageBlock::where('page', 'home')->whereNotIn('id', $keptIds)->delete();
        });

        // Xoá bản nháp preview & log hành động
        session()->forget('hp_builder_draft');
        Cache::forget('page_blocks.home');
        ActivityLog::log(__('Cập nhật giao diện trang chủ (Page Builder)'), auth()->id());

        return response()->json([
            'success' => true,
            'message' => __('Đã xuất bản giao diện trang chủ thành công!'),
        ]);
    }

    /**
     * Lưu bản nháp tạm vào session để khung xem trước (iframe) hiển thị nội dung chưa lưu.
     */
    public function previewDraft(Request $request)
    {
        if (config('app.demo')) {
            return response()->json(['success' => false], 422);
        }

        $clean = array_map(fn($b) => [
            'type' => $b['type'],
            'name' => $b['name'],
            'settings' => $b['settings'],
            'enabled' => $b['enabled'],
        ], $this->normalizeBlocks($request->input('blocks', '[]')));

        session(['hp_builder_draft' => $clean]);

        return response()->json(['success' => true]);
    }

    /**
     * Chuẩn hoá & kiểm duyệt danh sách block nhận từ trình dựng trang phía trình duyệt.
     *
     * Vì sao bắt buộc phải kiểm tra lại ở máy chủ: toàn bộ ràng buộc trong giao diện builder
     * (chỉ được thêm 1 block singleton, không được xoá block bắt buộc...) đều nằm ở JavaScript
     * nên có thể bị bỏ qua bằng cách gửi thẳng request. Nếu không chặn tại đây, một request giả
     * mạo có thể tạo ra nhiều block Hero trên trang chủ, dẫn tới trang có nhiều thẻ <h1> trùng
     * lặp (lỗi SEO nghiêm trọng) hoặc nhồi hàng nghìn block gây quá tải trang.
     *
     * @param mixed $raw Chuỗi JSON danh sách block gửi lên
     * @return array Danh sách block đã hợp lệ hoá
     */
    protected function normalizeBlocks($raw): array
    {
        $blocks = json_decode(is_string($raw) ? $raw : '[]', true);
        if (!is_array($blocks)) {
            $blocks = [];
        }

        $defs = self::blockDefinitions();
        $clean = [];
        $singletonSeen = [];

        foreach ($blocks as $b) {
            if (!is_array($b)) {
                continue;
            }

            $type = is_string($b['type'] ?? null) ? $b['type'] : '';
            if (!isset($defs[$type])) {
                continue;
            }

            // Chặn lặp block dạng singleton (Hero, Quy trình, Điểm nổi bật...) — chỉ giữ bản đầu tiên
            if (!empty($defs[$type]['singleton'])) {
                if (isset($singletonSeen[$type])) {
                    continue;
                }
                $singletonSeen[$type] = true;
            }

            $id = $b['id'] ?? null;
            $id = (is_numeric($id) && (int) $id > 0) ? (int) $id : null;

            $clean[] = [
                'id' => $id,
                'type' => $type,
                // Giới hạn độ dài tên nội bộ để tránh tràn cột và loại bỏ thẻ HTML
                'name' => mb_substr(trim(strip_tags((string) ($b['name'] ?? ''))), 0, 120) ?: $defs[$type]['label'],
                'settings' => $this->sanitizeSettings($type, $b['settings'] ?? [], $defs),
                'enabled' => !empty($b['enabled']),
            ];

            // Chặn nhồi block gây quá tải trang chủ
            if (count($clean) >= self::MAX_BLOCKS) {
                break;
            }
        }

        return $clean;
    }

    /**
     * Render homepage từ bản nháp trong session để hiển thị trong iframe xem trước.
     */
    public function preview(Request $request)
    {
        $request->merge(['__builder_preview' => 1]);
        return app(HomeController::class)->index($request);
    }

    /**
     * Chuẩn hoá mảng settings của một block: chỉ giữ các key được định nghĩa theo type,
     * xử lý repeater (gallery/FAQ/đánh giá), lọc URL nguy hiểm và ép kiểu an toàn.
     */
    protected function sanitizeSettings(string $type, $settings, array $defs): array
    {
        if (!is_array($settings)) {
            $settings = [];
        }

        $out = [];
        foreach ($defs[$type]['fields'] as $field) {
            $key = $field['key'];

            if (($field['type'] ?? '') === 'repeater') {
                $items = $settings[$key] ?? [];
                $items = is_array($items) ? $items : [];
                $cleanItems = [];
                foreach ($items as $item) {
                    if (!is_array($item)) {
                        continue;
                    }
                    $row = [];
                    foreach ($field['subfields'] as $sf) {
                        $row[$sf['key']] = $this->sanitizeValue($sf, $item[$sf['key']] ?? '');
                    }
                    $cleanItems[] = $row;

                    // Giới hạn số mục trong một repeater để tránh phình dữ liệu và làm nặng trang chủ
                    if (count($cleanItems) >= self::MAX_REPEATER_ITEMS) {
                        break;
                    }
                }
                $out[$key] = $cleanItems;
                continue;
            }

            if (array_key_exists($key, $settings) && is_scalar($settings[$key])) {
                $out[$key] = $this->sanitizeValue($field, $settings[$key]);
            } elseif (isset($defs[$type]['defaults'][$key])) {
                $out[$key] = $defs[$type]['defaults'][$key];
            }
        }

        return $out;
    }

    /**
     * Làm sạch giá trị của một field đơn lẻ theo kiểu dữ liệu đã khai báo.
     *
     * @param array $field Định nghĩa field (type, sanitize, min, max)
     * @param mixed $value Giá trị thô gửi lên
     * @return string Giá trị đã an toàn để lưu và in ra Storefront
     */
    protected function sanitizeValue(array $field, $value): string
    {
        $value = is_scalar($value) ? (string) $value : '';
        $type = $field['type'] ?? 'text';
        $rule = $field['sanitize'] ?? null;

        // Danh sách lựa chọn: giá trị không nằm trong options sẽ bị trả về lựa chọn đầu tiên.
        // Nếu bỏ qua bước này, một giá trị lạ (vd: hp_hero_layout = 'abc') sẽ không khớp nhánh
        // hiển thị nào trong view và làm cả khu vực Hero biến mất khỏi trang chủ.
        if ($type === 'select') {
            $options = array_keys((array) ($field['options'] ?? []));
            if (empty($options)) {
                return strip_tags($value);
            }
            return in_array($value, $options, true) ? $value : (string) $options[0];
        }

        // Công tắc bật/tắt chỉ nhận đúng hai giá trị '1' hoặc '0'
        if ($type === 'toggle') {
            return $value === '1' ? '1' : '0';
        }

        // Tên icon Lucide chỉ gồm chữ thường, số và dấu gạch nối
        if ($type === 'icon') {
            return (string) preg_replace('/[^a-z0-9\-]/', '', strtolower($value));
        }

        // Ép kiểu số nguyên và kẹp trong khoảng cho phép (số lượng hiển thị, số liệu ảo...)
        if ($rule === 'int' || $type === 'number') {
            $number = (int) preg_replace('/[^\-0-9]/', '', $value);
            if (isset($field['min'])) {
                $number = max((int) $field['min'], $number);
            }
            if (isset($field['max'])) {
                $number = min((int) $field['max'], $number);
            }
            return (string) $number;
        }

        // Lọc URL cho các field ảnh, media và liên kết: loại bỏ javascript:, data: không phải ảnh...
        if ($rule === 'url' || in_array($type, ['image', 'media'], true)) {
            return LinkHelper::safe($value);
        }

        // Nội dung HTML tự do: chặn mã JavaScript nếu người thao tác không phải Super Admin
        if (in_array($type, ['richtext', 'textarea'], true)) {
            return $this->sanitizeHtml($value);
        }

        // Các field văn bản thuần: loại bỏ thẻ HTML để không phá vỡ bố cục trang chủ
        return strip_tags($value);
    }

    /**
     * Lọc mã JavaScript trong nội dung HTML tự do của block.
     *
     * Quy tắc phân quyền: chỉ Super Admin (tài khoản admin có role_id = null) mới được phép nhúng
     * <script>, sự kiện on...="" hay javascript: — vì đây là các mã nhúng hợp lệ như pixel quảng cáo,
     * mã theo dõi chuyển đổi. Với quản trị viên phụ chỉ được cấp quyền manage_appearance, việc cho
     * phép nhúng JavaScript đồng nghĩa họ có thể đánh cắp phiên đăng nhập của Super Admin (Stored XSS
     * dẫn tới leo thang đặc quyền), nên hệ thống tự động loại bỏ các đoạn mã này.
     *
     * @param string $html Nội dung HTML thô
     * @return string Nội dung đã được lọc theo quyền hạn
     */
    protected function sanitizeHtml(string $html): string
    {
        $user = auth()->user();
        if ($user && $user->role_id === null) {
            return $html;
        }

        // Gỡ toàn bộ khối <script>...</script> kể cả khi không đóng thẻ
        $html = preg_replace('#<script\b[^>]*>.*?</script>#is', '', $html);
        $html = preg_replace('#<script\b[^>]*>#i', '', $html);

        // Gỡ các thuộc tính sự kiện onclick=, onerror=, onload=... (cả dạng nháy đơn, nháy kép và không nháy)
        $html = preg_replace('#\son[a-z]+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)#i', '', $html);

        // Vô hiệu hoá giao thức javascript:/vbscript: trong href/src
        $html = preg_replace('#(href|src|xlink:href)\s*=\s*(["\']?)\s*(javascript|vbscript)\s*:#i', '$1=$2#', $html);

        return (string) $html;
    }

    /**
     * API AJAX: Tạo nhanh nội dung HTML cho một block bằng AI.
     */
    public function generateAi(Request $request)
    {
        if (config('app.demo', false)) {
            return response()->json([
                'success' => false,
                'message' => __('Tính năng tạo nội dung bằng AI bị vô hiệu hóa trong phiên bản thử nghiệm (Demo).'),
            ], 422);
        }

        if (Setting::getVal('ai_status', '0') !== '1') {
            return response()->json([
                'success' => false,
                'message' => __('Dịch vụ AI hiện đang tắt. Vui lòng kích hoạt trong tab Kết nối > AI.'),
            ], 422);
        }

        $validated = $request->validate([
            'prompt' => 'required|string|max:2000',
        ], [
            'prompt.required' => __('Vui lòng mô tả nội dung section bạn muốn tạo.'),
        ]);

        $siteName   = Setting::getVal('site_name', 'Hoàn Tiền Shopee');
        $themeColor = Setting::getVal('theme_color', '#ee4d2d');

        $systemPrompt = <<<SYS
Bạn là chuyên gia thiết kế giao diện web cho nền tảng hoàn tiền mua sắm Shopee tên là "{$siteName}".
Nhiệm vụ: viết mã HTML cho một SECTION hiển thị trên TRANG CHỦ bằng TIẾNG VIỆT theo yêu cầu của người dùng (banner khuyến mãi, giới thiệu dịch vụ, câu hỏi thường gặp, lời kêu gọi hành động...).

QUY TẮC BẮT BUỘC:
- Chỉ trả về DUY NHẤT một object JSON hợp lệ, KHÔNG kèm giải thích, KHÔNG bọc trong dấu ```.
- Cấu trúc JSON: {"content": "mã HTML của section"}.
- Trường "content" là HTML dùng inline CSS (style="..."), responsive, đẹp mắt, hiển thị tốt trên cả máy tính và điện thoại, KHÔNG kèm thẻ <html>, <head>, <body>.
- Section sẽ nằm trong khung rộng tối đa khoảng 1280px (max-w-7xl) đã có padding hai bên, hãy thiết kế phần nền/bo góc bên trong cho phù hợp.
- Dùng màu thương hiệu chủ đạo là {$themeColor} cho tiêu đề, nút bấm và điểm nhấn.
- KHÔNG dùng <script>, KHÔNG dùng JavaScript.
SYS;

        try {
            $aiService = app(\App\Services\AIService::class);
            $response = $aiService->chat([
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user',   'content' => 'Yêu cầu nội dung section: ' . $validated['prompt']],
            ], [
                'max_tokens'  => 4000,
                'temperature' => 0.8,
            ]);

            if (str_contains($response, 'Lỗi khi kết nối với AI API') || str_contains($response, 'Dịch vụ AI hiện đang')) {
                return response()->json(['success' => false, 'message' => $response], 422);
            }

            $clean = trim($response);
            $clean = preg_replace('/^```(?:json)?\s*/i', '', $clean);
            $clean = preg_replace('/\s*```$/', '', $clean);

            $start = strpos($clean, '{');
            $end   = strrpos($clean, '}');
            $content = null;

            if ($start !== false && $end !== false && $end > $start) {
                $json = substr($clean, $start, $end - $start + 1);
                $parsed = json_decode($json, true);
                if (is_array($parsed)) {
                    $content = $parsed['content'] ?? null;
                }
            }

            if (empty($content)) {
                $content = $clean;
            }

            ActivityLog::log(__('Tạo nội dung Section tùy chỉnh trang chủ bằng AI'), auth()->id());

            return response()->json([
                'success' => true,
                'content' => $content,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('Lỗi khi tạo nội dung AI: :error', ['error' => $e->getMessage()]),
            ], 422);
        }
    }
}
