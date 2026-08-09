<?php

namespace App\Services\AI\Drivers;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Exception;

/**
 * Trình điều khiển kết nối OpenAI API.
 */
class OpenAIDriver extends AbstractDriver
{
    /**
     * Gửi hội thoại đến OpenAI.
     */
    public function chat(array $messages, array $options = []): string
    {
        $apiKey = $options['api_key'] ?? Setting::getVal('ai_openai_key');
        if (empty($apiKey)) {
            throw new Exception(__('API Key của OpenAI chưa được cấu hình.'));
        }

        // Lấy model cấu hình hoặc lấy mặc định nếu chưa chọn
        $model = $options['model'] ?? Setting::getVal('ai_openai_model', 'gpt-4o-mini');
        if (empty($model)) {
            $model = 'gpt-4o-mini';
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

        // Thực hiện cuộc gọi API OpenAI Chat Completions
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type' => 'application/json',
        ])->timeout(45)->post('https://api.openai.com/v1/chat/completions', $payload);

        if ($response->failed()) {
            $errorMsg = $response->json('error.message') ?? $response->body() ?? 'Unknown Error';
            throw new Exception("OpenAI API failed: " . $errorMsg);
        }

        $result = $response->json('choices.0.message.content');
        if (is_null($result)) {
            throw new Exception("Định dạng dữ liệu trả về từ OpenAI không hợp lệ.");
        }

        return trim($result);
    }
}
