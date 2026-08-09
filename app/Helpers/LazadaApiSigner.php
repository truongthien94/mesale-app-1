<?php

namespace App\Helpers;

/**
 * Helper ký chữ (signature) cho Lazada Open Platform API.
 *
 * Lazada (giống chuẩn TOP của Alibaba) yêu cầu mọi request tới api.lazada.* phải kèm:
 *  - app_key: khóa ứng dụng cấp trên Lazada Open Platform
 *  - timestamp: mốc thời gian mili-giây (13 chữ số)
 *  - sign_method: sha256
 *  - access_token: token truy cập của tài khoản affiliate (chính là "userToken" trong tài liệu rút gọn)
 *  - sign: chữ ký HMAC-SHA256 của (đường_dẫn_API + các_tham_số_sắp_xếp) với khóa app_secret
 *
 * Thuật toán ký:
 *  1. Gom tất cả tham số (hệ thống + nghiệp vụ), BỎ tham số 'sign'.
 *  2. Sắp xếp tham số theo tên khóa tăng dần (ASCII).
 *  3. Ghép chuỗi: apiPath + key1 + value1 + key2 + value2 + ... (không dấu phân cách).
 *  4. sign = HMAC-SHA256(chuỗi, app_secret) rồi viết HOA toàn bộ (hex).
 */
class LazadaApiSigner
{
    /**
     * Dựng mảng tham số đã ký đầy đủ để gửi kèm request tới Lazada Open Platform.
     *
     * @param string $apiPath Đường dẫn API tương đối sau /rest, ví dụ: /marketing/product/link
     * @param array $businessParams Các tham số nghiệp vụ (ví dụ: productId, dateStart...)
     * @param string $appKey App Key của ứng dụng Lazada
     * @param string $appSecret App Secret dùng để ký
     * @param string|null $accessToken Access Token của affiliate (userToken); có thể null với API không cần token
     * @return array Mảng tham số đầy đủ gồm cả 'sign' để đính vào query string
     */
    public static function signedParams(
        string $apiPath,
        array $businessParams,
        string $appKey,
        string $appSecret,
        ?string $accessToken = null
    ): array {
        // 1. Chuẩn bị tham số hệ thống bắt buộc
        $params = $businessParams;
        $params['app_key'] = $appKey;
        $params['timestamp'] = (string) round(microtime(true) * 1000);
        $params['sign_method'] = 'sha256';
        if (!empty($accessToken)) {
            $params['access_token'] = $accessToken;
        }

        // Loại bỏ mọi giá trị null để không đưa vào chuỗi ký
        $params = array_filter($params, static fn ($v) => $v !== null && $v !== '');

        // 2. Sắp xếp theo tên khóa tăng dần
        ksort($params);

        // 3. Ghép chuỗi ký: apiPath + key + value ... (không phân cách)
        $baseString = $apiPath;
        foreach ($params as $key => $value) {
            $baseString .= $key . $value;
        }

        // 4. Ký HMAC-SHA256 với app_secret rồi viết hoa
        $params['sign'] = strtoupper(hash_hmac('sha256', $baseString, $appSecret));

        return $params;
    }

    /**
     * Kiểm tra xem đã đủ cấu hình để ký request trực tiếp tới Lazada Open Platform hay chưa.
     *
     * @param string|null $appKey
     * @param string|null $appSecret
     * @return bool
     */
    public static function canSign(?string $appKey, ?string $appSecret): bool
    {
        return !empty($appKey) && !empty($appSecret);
    }
}
