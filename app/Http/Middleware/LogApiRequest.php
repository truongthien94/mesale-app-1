<?php

namespace App\Http\Middleware;

use App\Models\ApiLog;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use JsonSerializable;
use Symfony\Component\HttpFoundation\Response;
use Traversable;

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
    /** Marker retained in structured logs instead of the original sensitive value. */
    private const REDACTED_VALUE = '[REDACTED]';

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
                'user_id' => $user?->id,
                'method' => strtoupper($request->method()),
                'endpoint' => $this->truncateEndpoint($request->path()),
                'api_group' => $apiGroup,
                'status_code' => $response->getStatusCode(),
                'request_data' => $this->sanitizeRequestData($request),
                'response_time_ms' => $responseTimeMs,
                'ip_address' => $request->ip(),
                'user_agent' => $this->truncateUserAgent($request->userAgent()),
            ]);
        } catch (\Throwable $e) {
            // Bỏ qua lỗi ghi log — không bao giờ để việc ghi nhật ký làm hỏng response API
            // Ghi vào Laravel log để debug khi cần
            Log::warning('LogApiRequest: Không thể ghi nhật ký API', [
                'exception' => $e::class,
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
        $data = $this->redactSensitiveData($request->all());

        // Bỏ qua nếu không có dữ liệu
        if (empty($data)) {
            return null;
        }

        $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            return null;
        }

        // Cắt ngắn nếu quá dài để bảo vệ dung lượng database
        if (strlen($json) > 5000) {
            $json = mb_substr($json, 0, 5000).'... [đã cắt ngắn]';
        }

        return $json;
    }

    /**
     * Redact sensitive values at every nesting level while retaining safe request context.
     */
    private function redactSensitiveData(mixed $value): mixed
    {
        if ($value instanceof UploadedFile) {
            return self::REDACTED_VALUE;
        }

        if (is_array($value)) {
            $redacted = [];

            foreach ($value as $key => $item) {
                $redacted[$key] = is_string($key) && $this->isSensitiveField($key)
                    ? self::REDACTED_VALUE
                    : $this->redactSensitiveData($item);
            }

            return $redacted;
        }

        if ($value instanceof JsonSerializable) {
            return $this->redactSensitiveData($value->jsonSerialize());
        }

        if ($value instanceof Traversable) {
            return $this->redactSensitiveData(iterator_to_array($value));
        }

        if (is_object($value)) {
            return $this->redactSensitiveData(get_object_vars($value));
        }

        return $value;
    }

    /**
     * Normalize snake_case, kebab-case and camelCase variants before classification.
     */
    private function isSensitiveField(string $field): bool
    {
        $normalized = preg_replace('/([a-z0-9])([A-Z])/', '$1_$2', $field) ?? $field;
        $normalized = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '_', $normalized) ?? $normalized);
        $normalized = trim($normalized, '_');

        if (in_array($normalized, [
            'authorization',
            'cookie',
            'set_cookie',
            'session_id',
            'api_key',
            'private_key',
            'authorization_code',
            'nonce',
            'google2fa_code',
            'two_factor_code',
            '2fa_code',
            'account_number',
            'account_no',
            'card_number',
            'routing_number',
            'iban',
            'avatar',
        ], true)) {
            return true;
        }

        if (str_contains($normalized, 'password')
            || str_contains($normalized, 'token')
            || str_contains($normalized, 'secret')
            || str_contains($normalized, 'otp')
            || str_contains($normalized, 'authorization_code')
            || str_ends_with($normalized, '_nonce')) {
            return true;
        }

        if (str_contains($normalized, 'account_number') || str_contains($normalized, 'account_no')) {
            return true;
        }

        return (str_contains($normalized, 'bank')
                || str_contains($normalized, 'wallet')
                || str_contains($normalized, 'payout'))
            && str_contains($normalized, 'account');
    }

    /**
     * Cắt ngắn đường dẫn endpoint nếu quá dài (tối đa 500 ký tự theo schema).
     */
    private function truncateEndpoint(string $path): string
    {
        // Thêm dấu / phía trước cho nhất quán
        $endpoint = '/'.ltrim($path, '/');

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
