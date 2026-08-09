<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ZaloBotService
{
    protected string $baseUrl = 'https://bot-api.zaloplatforms.com/bot';
    protected ?string $token;

    public function __construct(?string $token = null)
    {
        $this->token = $token;
    }

    protected function apiUrl(string $method): string
    {
        return $this->baseUrl . $this->token . '/' . $method;
    }

    /**
     * Gửi tin nhắn văn bản đến chat_id.
     */
    public function sendMessage(string $chatId, string $text, array $extra = []): array
    {
        try {
            $payload = array_merge(['chat_id' => $chatId, 'text' => $text], $extra);
            $response = Http::timeout(10)->post($this->apiUrl('sendMessage'), $payload);
            $resData = $response->json() ?? [];
            Log::info('ZaloBotService sendMessage response', [
                'payload' => $payload,
                'response' => $resData
            ]);
            return $resData;
        } catch (\Throwable $e) {
            Log::error('ZaloBotService sendMessage error: ' . $e->getMessage());
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Đặt URL webhook để nhận tin nhắn.
     * Cung cấp thêm tham số secretToken để Zalo Bot Platform gửi lại qua header X-Bot-Api-Secret-Token.
     */
    public function setWebhook(string $url, ?string $secretToken = null): array
    {
        try {
            $payload = ['url' => $url];
            if ($secretToken) {
                $payload['secret_token'] = $secretToken;
            }
            $response = Http::timeout(15)->post($this->apiUrl('setWebhook'), $payload);
            return $response->json() ?? [];
        } catch (\Throwable $e) {
            Log::error('ZaloBotService setWebhook error: ' . $e->getMessage());
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Xoá webhook (chuyển sang chế độ polling).
     */
    public function deleteWebhook(): array
    {
        try {
            $response = Http::timeout(10)->post($this->apiUrl('deleteWebhook'));
            return $response->json() ?? [];
        } catch (\Throwable $e) {
            Log::error('ZaloBotService deleteWebhook error: ' . $e->getMessage());
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Lấy thông tin Bot hiện tại.
     */
    public function getMe(): array
    {
        try {
            $response = Http::timeout(10)->get($this->apiUrl('getMe'));
            return $response->json() ?? [];
        } catch (\Throwable $e) {
            Log::error('ZaloBotService getMe error: ' . $e->getMessage());
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Lấy thông tin webhook hiện tại.
     */
    public function getWebhookInfo(): array
    {
        try {
            $response = Http::timeout(10)->get($this->apiUrl('getWebhookInfo'));
            return $response->json() ?? [];
        } catch (\Throwable $e) {
            Log::error('ZaloBotService getWebhookInfo error: ' . $e->getMessage());
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }
}
