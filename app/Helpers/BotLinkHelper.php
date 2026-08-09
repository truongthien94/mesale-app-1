<?php

namespace App\Helpers;

/**
 * Lớp tiện ích nhận diện link sản phẩm trong tin nhắn của các Bot chat (Zalo, Telegram).
 *
 * Bối cảnh nghiệp vụ: danh sách sàn được hỗ trợ trước đây bị chép tay ở nhiều nơi (trang chủ web,
 * controller API, hai controller webhook bot). Hệ quả là khi mở thêm sàn Lazada cho trang chủ,
 * các nơi còn lại vẫn từ chối link Lazada và trả về cho khách thông báo sai là hệ thống không hỗ trợ.
 * Vì vậy toàn bộ phần nhận diện link của Bot phải lấy từ đúng một nguồn duy nhất là lớp này —
 * mở thêm sàn mới chỉ cần sửa ở đây.
 *
 * Lưu ý phạm vi: lớp này dành riêng cho Bot, nơi tin nhắn là văn bản lộn xộn (kèm chữ, xuống dòng,
 * hoặc bị bẻ dấu chấm để lách bộ lọc xem trước link). Luồng web và API dùng cách chặt hơn là
 * `parse_url` + regex khớp trọn tên miền, nên KHÔNG dùng chung hàm với lớp này.
 */
class BotLinkHelper
{
    /**
     * Tên miền chính thức của các sàn liên kết mà Bot chấp nhận.
     * Giữ đồng bộ với danh sách regex ở HomeController@getProductInfo và Api\V1\CashbackController@create.
     */
    public const PRODUCT_DOMAINS = [
        // Shopee
        'shopee.vn', 'shopee.sg', 'shope.ee', 'shp.ee', 'shopee.co', 'shopee.com',
        // TikTok Shop
        'tiktok.com', 'tiktok.shop',
        // Lazada (kể cả tên miền chia sẻ rút gọn lzd.co)
        'lazada.vn', 'lazada.sg', 'lazada.co.id', 'lazada.com.my', 'lazada.co.th', 'lazada.com.ph', 'lzd.co',
    ];

    /**
     * Biến thể tên miền bị bẻ dấu chấm thành khoảng trắng/gạch ngang/gạch dưới.
     * Người dùng Zalo hay gõ kiểu này để tin nhắn không bị ứng dụng tự dựng thẻ xem trước link.
     */
    public const BROKEN_DOMAINS = [
        's shopee vn', 's-shopee-vn', 's_shopee_vn',
        'shopee vn', 'shopee-vn', 'shopee_vn',
        'shope ee', 'shope-ee', 'shope_ee',
        'shp ee', 'shp-ee', 'shp_ee',
        'tiktok com', 'tiktok-com', 'tiktok_com',
        'tiktok shop', 'tiktok-shop', 'tiktok_shop',
        'lazada vn', 'lazada-vn', 'lazada_vn',
        's lazada vn', 's-lazada-vn', 's_lazada_vn',
        'lzd co', 'lzd-co', 'lzd_co',
    ];

    /**
     * Nhận diện sàn thương mại điện tử từ nội dung tin nhắn.
     *
     * Thứ tự kiểm tra Shopee trước rồi mới tới TikTok Shop và Lazada là có chủ đích: link Shopee
     * hay kèm tham số tiếp thị chứa tên sàn khác, kiểm tra Shopee trước giúp không phân loại nhầm.
     *
     * @return string|null 'shopee' | 'tiktok' | 'lazada' | null nếu không thuộc sàn nào
     */
    public static function detectPlatform(string $text): ?string
    {
        // Dùng stripos (không phân biệt hoa thường) cho khớp với isProductLink: hai hàm này là hai
        // cửa liên tiếp của cùng một luồng, nếu lệch nhau về cách so khớp thì sẽ có tin nhắn qua
        // được cửa trước rồi chết ở cửa sau và khách nhận thông báo lỗi sai bản chất.
        if (stripos($text, 'shopee.') !== false || stripos($text, 'shope.ee') !== false || stripos($text, 'shp.ee') !== false) {
            return 'shopee';
        }

        if (stripos($text, 'tiktok.com') !== false || stripos($text, 'tiktok.shop') !== false) {
            return 'tiktok';
        }

        if (stripos($text, 'lazada.') !== false || stripos($text, 'lzd.co') !== false) {
            return 'lazada';
        }

        return null;
    }

    /**
     * Kiểm tra tin nhắn có chứa link sản phẩm của sàn liên kết hay không.
     *
     * @param bool $includeBrokenVariants Có chấp nhận cả tên miền bị bẻ dấu chấm hay không.
     *        CHỈ bật cho Bot nào có khả năng dựng lại link từ dạng bẻ đó (hiện chỉ Bot Zalo có
     *        extractUrlFromText). Bật cho Bot không dựng lại được thì tin nhắn lọt qua cửa này
     *        rồi chắc chắn chết ở bước nhận diện sàn, khách nhận thông báo lỗi sai bản chất.
     */
    public static function isProductLink(string $text, bool $includeBrokenVariants = true): bool
    {
        foreach (self::PRODUCT_DOMAINS as $domain) {
            if (stripos($text, $domain) !== false) {
                return true;
            }
        }

        if (!$includeBrokenVariants) {
            return false;
        }

        foreach (self::BROKEN_DOMAINS as $broken) {
            if (stripos($text, $broken) !== false) {
                return true;
            }
        }

        return false;
    }
}
