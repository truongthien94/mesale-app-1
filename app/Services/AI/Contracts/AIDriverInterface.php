<?php

namespace App\Services\AI\Contracts;

/**
 * Giao diện hợp đồng (Interface Contract) dành cho các trình điều khiển AI.
 * Đảm bảo mọi AI Driver (OpenAI, DeepSeek, Claude, Gemini, ...) đều triển khai cấu trúc thống nhất.
 */
interface AIDriverInterface
{
    /**
     * Thực hiện gửi luồng hội thoại đến nhà cung cấp AI và lấy phản hồi văn bản.
     * 
     * @param array $messages Luồng tin nhắn định dạng [['role' => '...', 'content' => '...'], ...]
     * @param array $options Tham số tuỳ biến thêm (model, temperature, max_tokens, ...).
     * @return string Văn bản phản hồi từ AI.
     */
    public function chat(array $messages, array $options = []): string;
}
