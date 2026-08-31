<?php

namespace App\Services;

use App\Models\DeviceToken;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Dịch vụ gửi thông báo đẩy (Push Notification) tới thiết bị qua Firebase Cloud Messaging (FCM HTTP v1).
 *
 * Cơ chế:
 *  - Admin dán JSON Service Account của Firebase vào cài đặt `fcm_service_account`.
 *  - Service tự ký JWT (RS256) bằng private key để lấy OAuth2 access token (cache ~55 phút).
 *  - Gửi message tới từng device token; token chết (UNREGISTERED/INVALID) sẽ bị xóa tự động.
 *
 * Toàn bộ được bọc try/catch và fail-safe: nếu chưa cấu hình hoặc lỗi mạng thì bỏ qua âm thầm,
 * không bao giờ làm gián đoạn luồng nghiệp vụ đang gọi.
 */
class PushNotificationService
{
    /**
     * Hệ thống push đã được cấu hình (có Service Account) hay chưa.
     */
    public function isConfigured(): bool
    {
        return trim((string) Setting::getVal('fcm_service_account', '')) !== '';
    }

    /**
     * Gửi push tới toàn bộ thiết bị của một thành viên.
     *
     * @param  array<string,mixed>  $data  Payload dữ liệu kèm theo (dùng để điều hướng trong app)
     */
    public function sendToUser(User|int $user, string $title, string $body, array $data = []): void
    {
        if (!$this->isConfigured()) {
            return;
        }

        $userId = $user instanceof User ? $user->id : (int) $user;
        $tokens = DeviceToken::where('user_id', $userId)
            ->when(
                Setting::getVal('ios_payout_features_enabled', '0') !== '1',
                static fn ($query) => $query->where('platform', '!=', 'ios')
            )
            ->pluck('token', 'id');
        if ($tokens->isEmpty()) {
            return;
        }

        try {
            $accessToken = $this->accessToken();
            $projectId = $this->projectId();
            if (!$accessToken || !$projectId) {
                return;
            }

            $endpoint = "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send";

            foreach ($tokens as $id => $token) {
                $this->sendSingle($endpoint, $accessToken, (string) $token, $title, $body, $data, (int) $id);
            }
        } catch (\Throwable $e) {
            Log::warning('PushNotificationService lỗi gửi push: ' . $e->getMessage());
        }
    }

    /**
     * Gửi một message tới một token; xử lý dọn token chết.
     *
     * @param  array<string,mixed>  $data
     */
    private function sendSingle(string $endpoint, string $accessToken, string $token, string $title, string $body, array $data, int $deviceId): void
    {
        // FCM yêu cầu mọi giá trị trong "data" phải là chuỗi
        $stringData = [];
        foreach ($data as $k => $v) {
            $stringData[$k] = is_scalar($v) ? (string) $v : json_encode($v);
        }

        try {
            $response = Http::withToken($accessToken)
                ->timeout(10)
                ->post($endpoint, [
                    'message' => [
                        'token' => $token,
                        'notification' => [
                            'title' => $title,
                            'body' => $body,
                        ],
                        'data' => (object) $stringData,
                        'android' => ['priority' => 'high'],
                    ],
                ]);

            // Token không còn hợp lệ → xóa để không gửi lại lần sau
            if ($response->status() === 404 || $response->status() === 400) {
                $err = (string) ($response->json('error.status') ?? '');
                if (in_array($err, ['NOT_FOUND', 'UNREGISTERED', 'INVALID_ARGUMENT'], true)) {
                    DeviceToken::where('id', $deviceId)->delete();
                }
            }
        } catch (\Throwable $e) {
            Log::warning('PushNotificationService lỗi gửi tới token thiết bị: ' . $e->getMessage());
        }
    }

    /**
     * Lấy OAuth2 access token từ Service Account (cache lại để tái sử dụng).
     */
    private function accessToken(): ?string
    {
        $sa = $this->serviceAccount();
        if (!$sa) {
            return null;
        }

        return Cache::remember('fcm_access_token:' . md5($sa['client_email'] ?? ''), 3000, function () use ($sa) {
            $now = time();
            $tokenUri = $sa['token_uri'] ?? 'https://oauth2.googleapis.com/token';

            $jwt = $this->signJwt([
                'iss' => $sa['client_email'],
                'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
                'aud' => $tokenUri,
                'iat' => $now,
                'exp' => $now + 3600,
            ], $sa['private_key']);

            if (!$jwt) {
                return null;
            }

            $resp = Http::asForm()->timeout(10)->post($tokenUri, [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt,
            ]);

            return $resp->successful() ? $resp->json('access_token') : null;
        });
    }

    /**
     * Ký JWT RS256 bằng private key của Service Account (dùng openssl, không cần thư viện ngoài).
     *
     * @param  array<string,mixed>  $claims
     */
    private function signJwt(array $claims, string $privateKey): ?string
    {
        $header = ['alg' => 'RS256', 'typ' => 'JWT'];
        $segments = [
            $this->base64UrlEncode(json_encode($header)),
            $this->base64UrlEncode(json_encode($claims)),
        ];
        $signingInput = implode('.', $segments);

        $signature = '';
        if (!openssl_sign($signingInput, $signature, $privateKey, 'SHA256')) {
            return null;
        }
        $segments[] = $this->base64UrlEncode($signature);

        return implode('.', $segments);
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * Giải mã JSON Service Account từ cài đặt.
     *
     * @return array<string,mixed>|null
     */
    private function serviceAccount(): ?array
    {
        $raw = trim((string) Setting::getVal('fcm_service_account', ''));
        if ($raw === '') {
            return null;
        }
        $decoded = json_decode($raw, true);
        if (!is_array($decoded) || empty($decoded['client_email']) || empty($decoded['private_key'])) {
            return null;
        }
        return $decoded;
    }

    private function projectId(): ?string
    {
        $sa = $this->serviceAccount();
        return $sa['project_id'] ?? null;
    }
}
