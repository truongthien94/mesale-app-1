<?php

namespace App\Services;

use App\Models\Setting;
use App\Services\AI\Contracts\AIDriverInterface;
use App\Services\AI\Drivers\OpenAIDriver;
use App\Services\AI\Drivers\DeepSeekDriver;
use App\Services\AI\Drivers\ClaudeDriver;
use App\Services\AI\Drivers\GeminiDriver;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Service quản lý và điều phối các cuộc gọi AI (AI Manager / Factory).
 * Áp dụng Design Pattern: Strategy Pattern kết hợp Factory và cơ chế Fallback (Tự động chuyển đổi nhà cung cấp dự phòng khi lỗi) dành cho hệ thống lớn.
 */
class AIService
{
    /**
     * Trạng thái hoạt động toàn cục của dịch vụ AI.
     */
    protected bool $isActive;

    /**
     * Nhà cung cấp AI mặc định được cấu hình trong hệ thống.
     */
    protected string $defaultProvider;

    /**
     * Danh sách các instance Driver đã được khởi tạo để tái sử dụng.
     */
    protected array $drivers = [];

    /**
     * Khởi tạo AIService và lấy cấu hình cơ bản.
     */
    public function __construct()
    {
        $this->isActive = Setting::getVal('ai_status', '0') === '1';
        $this->defaultProvider = Setting::getVal('ai_default_provider', 'openai');
    }

    /**
     * Khởi tạo và trả về Driver tương ứng theo tên nhà cung cấp.
     * 
     * @param string|null $name Tên nhà cung cấp AI (openai, deepseek, claude, gemini).
     * @return AIDriverInterface
     * @throws Exception
     */
    public function driver(?string $name = null): AIDriverInterface
    {
        $name = $name ?: $this->defaultProvider;
        $name = strtolower($name);

        // Trả về driver cũ nếu đã được khởi tạo trong request lifecycle (Multiton / Flyweight Pattern)
        if (isset($this->drivers[$name])) {
            return $this->drivers[$name];
        }

        switch ($name) {
            case 'openai':
                return $this->drivers[$name] = new OpenAIDriver();
            case 'deepseek':
                return $this->drivers[$name] = new DeepSeekDriver();
            case 'claude':
                return $this->drivers[$name] = new ClaudeDriver();
            case 'gemini':
                return $this->drivers[$name] = new GeminiDriver();
            default:
                throw new Exception("Nhà cung cấp AI '{$name}' không được hỗ trợ.");
        }
    }

    /**
     * Tạo nội dung văn bản dựa trên một Prompt đơn lẻ.
     * 
     * @param string $prompt
     * @param array $options
     * @return string
     */
    public function generateText(string $prompt, array $options = []): string
    {
        if (!$this->isActive) {
            Log::warning('AIService: Cuộc gọi bị từ chối do dịch vụ AI đang TẮT toàn cục.');
            return __('Dịch vụ AI hiện đang tạm tắt. Vui lòng kích hoạt trong cài đặt hệ thống.');
        }

        $messages = [
            [
                'role' => 'user',
                'content' => $prompt
            ]
        ];

        return $this->chat($messages, $options);
    }

    /**
     * Gửi luồng hội thoại đến AI và tự động xử lý Fallback nếu xảy ra lỗi.
     * 
     * @param array $messages
     * @param array $options
     * @return string
     * @throws Exception
     */
    public function chat(array $messages, array $options = []): string
    {
        if (!$this->isActive) {
            return __('Dịch vụ AI hiện đang tạm tắt.');
        }

        $provider = $options['provider'] ?? $this->defaultProvider;
        $provider = strtolower($provider);

        // Ghi nhận thời gian bắt đầu gọi API để đo lường hiệu năng của nhà cung cấp
        $startTime = microtime(true);

        try {
            // Lấy driver tương ứng và gọi API
            $driver = $this->driver($provider);
            $response = $driver->chat($messages, $options);

            // Tính toán thời gian phản hồi (mili giây)
            $duration = round((microtime(true) - $startTime) * 1000);
            Log::info("AIService: Gọi thành công [Provider: {$provider}] - Thời gian phản hồi: {$duration}ms");

            return $response;

        } catch (Exception $e) {
            // Tính toán thời gian phản hồi khi lỗi
            $duration = round((microtime(true) - $startTime) * 1000);
            Log::error("AIService Error [Provider: {$provider}] sau {$duration}ms: " . $e->getMessage(), [
                'exception' => $e
            ]);

            // Trả về lỗi chuẩn hóa cho người dùng nếu API lỗi
            return __('Lỗi khi kết nối với AI API (:error). Vui lòng kiểm tra lại cấu hình key/model.', ['error' => $e->getMessage()]);
        }
    }
}
