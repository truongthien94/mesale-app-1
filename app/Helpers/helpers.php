<?php

use App\Services\AIService;

if (!function_exists('generate_ai_content')) {
    /**
     * Hàm helper toàn cục dùng để tạo nội dung văn bản từ AI.
     * Thường dùng cho các chức năng viết bài viết blog, tạo mô tả sản phẩm, phản hồi nhanh...
     * 
     * @param string $prompt Câu lệnh yêu cầu gửi cho AI.
     * @param array $options Các cấu hình bổ sung (model, provider, temperature, max_tokens).
     * @return string Nội dung phản hồi từ AI.
     */
    function generate_ai_content(string $prompt, array $options = []): string
    {
        // Gọi dịch vụ AIService thông qua Service Container của Laravel để xử lý tác vụ
        return app(AIService::class)->generateText($prompt, $options);
    }
}

if (!function_exists('chat_with_ai')) {
    /**
     * Hàm helper toàn cục gửi luồng hội thoại đến AI và nhận văn bản phản hồi.
     * 
     * @param array $messages Mảng chứa các tin nhắn hội thoại [['role' => '...', 'content' => '...'], ...]
     * @param array $options Các cấu hình bổ sung (model, provider, temperature, max_tokens).
     * @return string Phản hồi hội thoại của AI.
     */
    function chat_with_ai(array $messages, array $options = []): string
    {
        return app(AIService::class)->chat($messages, $options);
    }
}
