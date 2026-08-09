<?php

namespace App\Services\AI\Drivers;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Exception;

/**
 * Trình điều khiển kết nối Google Gemini API.
 */
class GeminiDriver extends AbstractDriver
{
    /**
     * Gửi hội thoại đến Google Gemini.
     */
    public function chat(array $messages, array $options = []): string
    {
        $apiKey = $options['api_key'] ?? Setting::getVal('ai_gemini_key');
        if (empty($apiKey)) {
            throw new Exception(__('API Key của Google Gemini chưa được cấu hình.'));
        }

        $model = $options['model'] ?? Setting::getVal('ai_gemini_model', 'gemini-1.5-flash');
        if (empty($model)) {
            $model = 'gemini-1.5-flash';
        }

        $contents = [];
        $systemInstruction = null;

        foreach ($messages as $msg) {
            $role = $msg['role'];
            if ($role === 'assistant') {
                $role = 'model';
            }

            if ($role === 'system') {
                $systemInstruction = [
                    'parts' => [
                        ['text' => $msg['content']]
                    ]
                ];
            } else {
                $contents[] = [
                    'role' => $role,
                    'parts' => [
                        ['text' => $msg['content']]
                    ]
                ];
            }
        }

        $payload = [
            'contents' => $contents,
        ];

        if ($systemInstruction) {
            $payload['systemInstruction'] = $systemInstruction;
        }

        $generationConfig = [];
        if (isset($options['temperature'])) {
            $generationConfig['temperature'] = (float)$options['temperature'];
        }
        if (isset($options['max_tokens'])) {
            $generationConfig['maxOutputTokens'] = (int)$options['max_tokens'];
        }

        if (!empty($generationConfig)) {
            $payload['generationConfig'] = $generationConfig;
        }

        // Endpoint Google Gemini API
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->timeout(45)->post($url, $payload);

        if ($response->failed()) {
            $errorMsg = $response->json('error.message') ?? $response->body() ?? 'Unknown Error';
            throw new Exception("Gemini API failed: " . $errorMsg);
        }

        $result = $response->json('candidates.0.content.parts.0.text');
        if (is_null($result)) {
            throw new Exception("Định dạng dữ liệu trả về từ Gemini không hợp lệ.");
        }

        return trim($result);
    }
}
