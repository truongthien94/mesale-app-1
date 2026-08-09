<?php

namespace App\Helpers;

/**
 * Helper xử lý liên kết (URL) do Admin tự nhập trong trình dựng trang (Page Builder).
 *
 * Vì nội dung các block (gallery, CTA, logo đối tác...) được nhập tay ở trang
 * /admin/appearance rồi in thẳng ra thuộc tính href/src ngoài Storefront nên bắt buộc
 * phải lọc giao thức nguy hiểm (javascript:, vbscript:, data:...) để chặn XSS, đồng thời
 * tự gắn các thuộc tính chuẩn SEO/bảo mật cho liên kết trỏ ra tên miền bên ngoài.
 */
class LinkHelper
{
    /** Các giao thức được phép xuất hiện trong href/src. */
    protected const ALLOWED_SCHEMES = ['http', 'https', 'mailto', 'tel'];

    /**
     * Làm sạch URL: trả về chuỗi rỗng nếu phát hiện giao thức không nằm trong danh sách cho phép.
     * Chuỗi rỗng sẽ khiến view bỏ qua không render liên kết đó.
     *
     * @param string|null $url URL thô do Admin nhập
     * @return string URL an toàn hoặc chuỗi rỗng
     */
    public static function safe(?string $url): string
    {
        $url = trim((string) $url);
        if ($url === '') {
            return '';
        }

        // Đường dẫn tương đối, neo trong trang hoặc query string luôn an toàn
        if (str_starts_with($url, '/') || str_starts_with($url, '#') || str_starts_with($url, '?')) {
            return $url;
        }

        // Cho phép ảnh nhúng base64 (dùng cho thẻ <img> trong block gallery)
        if (preg_match('#^data:image/(png|jpe?g|gif|webp|avif|svg\+xml);base64,#i', $url)) {
            return $url;
        }

        // Chuẩn hoá chuỗi trước khi dò giao thức: loại bỏ khoảng trắng và ký tự điều khiển ẩn
        // (thủ thuật né bộ lọc thường thấy như "java\tscript:alert(1)" hoặc "jav\0ascript:")
        $probe = strtolower(preg_replace('/[\s\x00-\x1F\x7F]+/', '', $url));

        // Không có dấu ":" ở phần đầu nghĩa là đường dẫn tương đối (vd: "trang-gioi-thieu")
        if (!preg_match('#^([a-z][a-z0-9+.\-]*):#', $probe, $m)) {
            return $url;
        }

        return in_array($m[1], self::ALLOWED_SCHEMES, true) ? $url : '';
    }

    /**
     * Kiểm tra một URL có trỏ ra tên miền bên ngoài website hay không.
     *
     * @param string|null $url URL cần kiểm tra
     * @return bool true nếu là liên kết ngoài
     */
    public static function isExternal(?string $url): bool
    {
        $url = trim((string) $url);
        if ($url === '' || !preg_match('#^https?://#i', $url)) {
            return false;
        }

        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        $selfHost = strtolower((string) parse_url(config('app.url', url('/')), PHP_URL_HOST));

        return $host !== '' && $host !== $selfHost;
    }

    /**
     * Sinh sẵn chuỗi thuộc tính cho thẻ <a> tuỳ theo liên kết nội bộ hay liên kết ngoài.
     *
     * Quy tắc SEO: liên kết ngoài do Admin cấu hình (đối tác, sàn thương mại điện tử...) luôn
     * mở tab mới và gắn rel="nofollow noopener" — vừa chống rò rỉ PageRank sang trang ngoài,
     * vừa tuân thủ yêu cầu của Google về liên kết tiếp thị liên kết (affiliate), đồng thời
     * "noopener" ngăn trang đích chiếm quyền điều khiển tab gốc qua window.opener.
     *
     * @param string|null $url URL đã được làm sạch
     * @return string Chuỗi thuộc tính HTML để chèn vào thẻ <a>
     */
    public static function anchorAttrs(?string $url): string
    {
        return self::isExternal($url) ? 'target="_blank" rel="nofollow noopener"' : '';
    }
}
