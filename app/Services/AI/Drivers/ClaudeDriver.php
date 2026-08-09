<?php

namespace App\Services\AI\Drivers;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Exception;

/**
 * Trình điều khiển kết nối Anthropic Claude API (Messages API).
 */
class ClaudeDriver extends AbstractDriver
{
    /**
     * Gửi hội thoại đến Claude.
     */
    public function chat(array $messages, array $options = []): string
    {
        $apiKey = $options['api_key'] ?? Setting::getVal('ai_claude_key');
        if (empty($apiKey)) {
            throw new Exception(__('API Key của Anthropic Claude chưa được cấu hình.'));
        }

        $model = $options['model'] ?? Setting::getVal('ai_claude_model', 'claude-3-5-sonnet-latest');
        if (empty($model)) {
            $model = 'claude-3-5-sonnet-latest';
        }

        $maxTokens = $options['max_tokens'] ?? 4000;
        $temperature = $options['temperature'] ?? 0.7;

        $payload = [
            'model' => $model,
            'messages' => $messages,
            'max_tokens' => (int)$maxTokens,
            'temperature' => (float)$temperature,
        ];

        // Tách system prompt ra khỏi messages để tối ưu cấu trúc của Claude
        $systemPrompt = '';
        $filteredMessages = [];
        foreach ($messages as $msg) {
            if ($msg['role'] === 'system') {
                $systemPrompt = $msg['content'];
            } else {
                $filteredMessages[] = $msg;
            }
        }

        if (!empty($systemPrompt)) {
            $payload['system'] = $systemPrompt;
            $payload['messages'] = $filteredMessages;
        }

        // Thực hiện cuộc gọi API Claude
        $response = Http::withHeaders([
            'x-api-key' => $apiKey,
            'anthropic-version' => '2023-06-01',
            'Content-Type' => 'application/json',
        ])->timeout(45)->post('https://api.anthropic.com/v1/messages', $payload);

        if ($response->failed()) {
            $errorMsg = $response->json('error.message') ?? $response->body() ?? 'Unknown Error';
            throw new Exception("Claude API failed: " . $errorMsg);
        }

        $result = $response->json('content.0.text');
        if (is_null($result)) {
            throw new Exception("Định dạng dữ liệu trả về từ Claude không hợp lệ.");
        }

        return trim($result);
    }
}
