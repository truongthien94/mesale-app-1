<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramBotService
{
    protected string $baseUrl = 'https://api.telegram.org/bot';
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
    public function sendMessage(string $chatId, string $text, string $parseMode = 'Markdown'): array
    {
        try {
            $response = Http::timeout(10)->post($this->apiUrl('sendMessage'), [
                'chat_id' => $chatId,
                'text' => $text,
                'parse_mode' => $parseMode,
            ]);
            return $response->json() ?? [];
        } catch (\Throwable $e) {
            Log::error('TelegramBotService sendMessage error: ' . $e->getMessage());
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Đặt URL webhook để nhận tin nhắn.
     * Tham số $secretToken sẽ được Telegram gửi lại qua header X-Telegram-Bot-Api-Secret-Token
     * trên mọi webhook request, dùng để xác thực nguồn gốc.
     */
    public function setWebhook(string $url, ?string $secretToken = null, bool $dropPendingUpdates = false): array
    {
        try {
            $payload = [
                'url'                  => $url,
                'drop_pending_updates' => $dropPendingUpdates,
            ];
            if ($secretToken) {
                $payload['secret_token'] = $secretToken;
            }
            $response = Http::timeout(15)->post($this->apiUrl('setWebhook'), $payload);
            return $response->json() ?? [];
        } catch (\Throwable $e) {
            Log::error('TelegramBotService setWebhook error: ' . $e->getMessage());
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Xoá webhook.
     */
    public function deleteWebhook(): array
    {
        try {
            $response = Http::timeout(10)->post($this->apiUrl('deleteWebhook'));
            return $response->json() ?? [];
        } catch (\Throwable $e) {
            Log::error('TelegramBotService deleteWebhook error: ' . $e->getMessage());
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
            Log::error('TelegramBotService getMe error: ' . $e->getMessage());
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
            Log::error('TelegramBotService getWebhookInfo error: ' . $e->getMessage());
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }
}
