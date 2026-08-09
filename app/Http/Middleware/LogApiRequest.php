<?php

namespace App\Http\Middleware;

use App\Models\ApiLog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware tự động ghi nhật ký mọi lần gọi API vào bảng api_logs.
 *
 * Luồng xử lý:
 *   1. Ghi nhận thời điểm bắt đầu request (microtime).
 *   2. Cho request đi tiếp qua pipeline bình thường.
 *   3. Sau khi có response, tính toán thời gian xử lý và ghi log.
 *
 * Bảo mật: Tự động lọc bỏ các trường nhạy cảm (password, token, secret)
 * khỏi dữ liệu request trước khi lưu vào database.
 *
 * Hiệu năng: Sử dụng try-catch và ghi không đồng bộ (saveQuietly)
 * để không ảnh hưởng đến tốc độ phản hồi của API nếu ghi log lỗi.
 */
class LogApiRequest
{
    /**
     * Danh sách trường nhạy cảm cần lọc bỏ khỏi request data trước khi lưu log.
     * Ngăn chặn việc vô tình lưu mật khẩu hay token vào database.
     */
    private const SENSITIVE_FIELDS = [
        'password',
        'password_confirmation',
        'api_token',
        'api_key',
        'token',
        'secret',
        'current_password',
        'new_password',
        'otp',
        'otp_code',
    ];

    public function handle(Request $request, Closure $next, ?string $group = null): Response
    {
        // Ghi nhận thời điểm bắt đầu xử lý request
        $startTime = microtime(true);

        // Cho request đi tiếp qua các middleware và controller phía sau
        $response = $next($request);

        // Tính thời gian xử lý (đơn vị millisecond)
        $responseTimeMs = (int) round((microtime(true) - $startTime) * 1000);

        // Xác định nhóm API dựa vào tham số middleware hoặc phân tích URL
        $apiGroup = $group ?? $this->detectApiGroup($request);

        // Ghi log vào database, bọc try-catch để không ảnh hưởng response
        try {
            // Lấy user đã xác thực (nếu có) — hỗ trợ cả token phiên và API Key
            $user = $request->user() ?? null;

            ApiLog::create([
                'user_id'          => $user?->id,
                'method'           => strtoupper($request->method()),
                'endpoint'         => $this->truncateEndpoint($request->path()),
                'api_group'        => $apiGroup,
                'status_code'      => $response->getStatusCode(),
                'request_data'     => $this->sanitizeRequestData($request),
                'response_time_ms' => $responseTimeMs,
                'ip_address'       => $request->ip(),
                'user_agent'       => $this->truncateUserAgent($request->userAgent()),
            ]);
        } catch (\Throwable $e) {
            // Bỏ qua lỗi ghi log — không bao giờ để việc ghi nhật ký làm hỏng response API
            // Ghi vào Laravel log để debug khi cần
            \Illuminate\Support\Facades\Log::warning('LogApiRequest: Không thể ghi nhật ký API', [
                'error' => $e->getMessage(),
            ]);
        }

        return $response;
    }

    /**
     * Tự động nhận diện nhóm API dựa vào đường dẫn URL.
     * Fallback khi không truyền tham số group qua middleware.
     */
    private function detectApiGroup(Request $request): string
    {
        $path = $request->path();

        if (str_contains($path, 'v1/bot')) {
            return 'bot';
        }

        if (str_contains($path, 'v1/openapi')) {
            return 'openapi';
        }

        return 'other';
    }

    /**
     * Lọc bỏ trường nhạy cảm và JSON encode dữ liệu request để lưu log.
     * Giới hạn kích thước tối đa 5000 ký tự để tránh phình database.
     */
    private function sanitizeRequestData(Request $request): ?string
    {
        $data = $request->except(self::SENSITIVE_FIELDS);

        // Bỏ qua nếu không có dữ liệu
        if (empty($data)) {
            return null;
        }

        $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        // Cắt ngắn nếu quá dài để bảo vệ dung lượng database
        if (strlen($json) > 5000) {
            $json = mb_substr($json, 0, 5000) . '... [đã cắt ngắn]';
        }

        return $json;
    }

    /**
     * Cắt ngắn đường dẫn endpoint nếu quá dài (tối đa 500 ký tự theo schema).
     */
    private function truncateEndpoint(string $path): string
    {
        // Thêm dấu / phía trước cho nhất quán
        $endpoint = '/' . ltrim($path, '/');

        return mb_substr($endpoint, 0, 500);
    }

    /**
     * Cắt ngắn User-Agent nếu quá dài (tối đa 500 ký tự theo schema).
     */
    private function truncateUserAgent(?string $userAgent): ?string
    {
        if ($userAgent === null) {
            return null;
        }

        return mb_substr($userAgent, 0, 500);
    }
}
