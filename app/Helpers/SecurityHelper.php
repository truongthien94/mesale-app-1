<?php

namespace App\Helpers;

/**
 * Helper hỗ trợ bảo mật hệ thống.
 * Mọi function, logic block đều có comment tiếng Việt giải thích rõ ràng.
 */
class SecurityHelper
{
    /**
     * Xác thực URL nhằm chống tấn công Server-Side Request Forgery (SSRF).
     * Yêu cầu:
     * 1. URL phải đúng định dạng hợp lệ.
     * 2. Giao thức (scheme) bắt buộc phải là HTTPS (trừ trường hợp chạy ở local có thể cho phép HTTP).
     * 3. Hostname của URL không được là 'localhost', 'loopback', các IP private (10.x, 172.16-31.x, 192.168.x, 169.254.x, 0.0.0.0, 127.0.0.1)
     *    hoặc phân giải ra các IP thuộc các dải này.
     *
     * @param string|null $url URL cần kiểm tra
     * @return bool Trả về true nếu URL an toàn, ngược lại trả về false.
     */
    public static function validateSsfUrl(?string $url): bool
    {
        if (empty($url)) {
            return false;
        }

        // 1. Kiểm tra định dạng URL cơ bản
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }

        $parts = parse_url($url);
        $scheme = isset($parts['scheme']) ? strtolower($parts['scheme']) : '';
        $host = isset($parts['host']) ? strtolower($parts['host']) : '';

        if (empty($host)) {
            return false;
        }

        // 2. Chỉ chấp nhận HTTPS trên môi trường production, còn local cho phép http để test/phát triển
        $isLocal = config('app.env') === 'local';
        if ($scheme !== 'https') {
            if (!$isLocal || $scheme !== 'http') {
                return false;
            }
        }

        // 3. Danh sách kiểm tra chuỗi hostname nhạy cảm (Chỉ áp dụng trên môi trường production)
        $blacklistHosts = ['localhost', 'loopback', '127.0.0.1', '0.0.0.0', '169.254.169.254'];
        if (!$isLocal && in_array($host, $blacklistHosts)) {
            return false;
        }

        // 4. Phân giải IP của hostname để chặn các IP private được trỏ bởi tên miền (DNS Rebinding / SSRF bypass)
        $ip = gethostbyname($host);
        if (!$isLocal) {
            if (!$ip || $ip === $host) {
                // Trường hợp không phân giải được IP, hoặc gethostbyname trả về chính host (phân giải thất bại)
                return false;
            }

            // 5. Kiểm tra xem IP có thuộc dải Private hoặc Loopback không
            if (self::isPrivateIp($ip)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Kiểm tra xem một địa chỉ IP có thuộc dải Private hoặc Loopback không.
     *
     * @param string $ip Địa chỉ IP cần kiểm tra
     * @return bool
     */
    public static function isPrivateIp(string $ip): bool
    {
        // Lọc IPv4 hợp lệ
        if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            // Nếu là IPv6, kiểm tra loopback (::1) hoặc các dải private IPv6 (fe80::, fc00::, fd00::)
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
                if ($ip === '::1' || str_starts_with(strtolower($ip), 'fe80:') || str_starts_with(strtolower($ip), 'fc00:') || str_starts_with(strtolower($ip), 'fd00:')) {
                    return true;
                }
                return false;
            }
            return true; // Chặn nếu định dạng IP không xác định để đảm bảo an toàn tối đa
        }

        $ipLong = ip2long($ip);
        if ($ipLong === false) {
            return true;
        }

        // Các dải IPv4 Private/Loopback/Local phổ biến:
        // 127.0.0.0/8 (127.0.0.0 - 127.255.255.255)
        // 10.0.0.0/8 (10.0.0.0 - 10.255.255.255)
        // 172.16.0.0/12 (172.16.0.0 - 172.31.255.255)
        // 192.168.0.0/16 (192.168.0.0 - 192.255.255.255)
        // 169.254.0.0/16 (169.254.0.0 - 169.254.255.255)
        // 0.0.0.0/8 (0.0.0.0 - 0.255.255.255)
        $privateRanges = [
            ['start' => '127.0.0.0', 'end' => '127.255.255.255'],
            ['start' => '10.0.0.0', 'end' => '10.255.255.255'],
            ['start' => '172.16.0.0', 'end' => '172.31.255.255'],
            ['start' => '192.168.0.0', 'end' => '192.255.255.255'],
            ['start' => '169.254.0.0', 'end' => '169.254.255.255'],
            ['start' => '0.0.0.0', 'end' => '0.255.255.255'],
        ];

        foreach ($privateRanges as $range) {
            $startLong = ip2long($range['start']);
            $endLong = ip2long($range['end']);
            if ($ipLong >= $startLong && $ipLong <= $endLong) {
                return true;
            }
        }

        return false;
    }
}
