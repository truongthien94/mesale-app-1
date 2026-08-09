<?php

namespace App\Services\AI\Drivers;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Exception;

/**
 * Trình điều khiển kết nối DeepSeek API (Tương thích chuẩn OpenAI).
 */
class DeepSeekDriver extends AbstractDriver
{
    /**
     * Gửi hội thoại đến DeepSeek.
     */
    public function chat(array $messages, array $options = []): string
    {
        $apiKey = $options['api_key'] ?? Setting::getVal('ai_deepseek_key');
        if (empty($apiKey)) {
            throw new Exception(__('API Key của DeepSeek chưa được cấu hình.'));
        }

        $model = $options['model'] ?? Setting::getVal('ai_deepseek_model', 'deepseek-chat');
        if (empty($model)) {
            $model = 'deepseek-chat';
        }

        $temperature = $options['temperature'] ?? 0.7;
        $maxTokens = $options['max_tokens'] ?? null;

        $payload = [
            'model' => $model,
            'messages' => $messages,
            'temperature' => (float)$temperature,
        ];

        if ($maxTokens) {
            $payload['max_tokens'] = (int)$maxTokens;
        }

        // Thực hiện cuộc gọi API DeepSeek Chat Completions
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type' => 'application/json',
        ])->timeout(45)->post('https://api.deepseek.com/v1/chat/completions', $payload);

        if ($response->failed()) {
            $errorMsg = $response->json('error.message') ?? $response->body() ?? 'Unknown Error';
            throw new Exception("DeepSeek API failed: " . $errorMsg);
        }

        $result = $response->json('choices.0.message.content');
        if (is_null($result)) {
            throw new Exception("Định dạng dữ liệu trả về từ DeepSeek không hợp lệ.");
        }

        return trim($result);
    }
}
