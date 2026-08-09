<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class Task extends Model
{
    protected $fillable = [
        'title', 'description', 'guide', 'type', 'action',
        'referral_require_order',
        'target_count', 'min_order_amount', 'reward_amount', 'reward_type',
        'is_active', 'start_at', 'end_at',
        'icon', 'badge_color', 'sort_order', 'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'referral_require_order' => 'boolean',
        'start_at' => 'datetime',
        'end_at' => 'datetime',
        'reward_amount' => 'decimal:2',
        'min_order_amount' => 'decimal:2',
        'target_count' => 'integer',
        'sort_order' => 'integer',
    ];

    public static array $typeLabels = [
        'daily'    => 'Hằng ngày',
        'weekly'   => 'Hằng tuần',
        'one_time' => 'Một lần',
    ];

    public static array $actionLabels = [
        'profile'      => 'Hoàn thiện hồ sơ',
        'referral'     => 'Mời bạn bè',
        'cashback'     => 'Đơn hoàn tiền',
        'checkin'      => 'Điểm danh',
        'withdraw'     => 'Rút tiền',
        'save_product' => 'Lưu sản phẩm',
        'custom'       => 'Tùy chỉnh (thủ công)',
    ];

    public static array $colorClasses = [
        'orange' => ['bg' => 'bg-orange-100 dark:bg-orange-900/30', 'text' => 'text-orange-600 dark:text-orange-400', 'badge' => 'bg-orange-500'],
        'blue'   => ['bg' => 'bg-blue-100 dark:bg-blue-900/30', 'text' => 'text-blue-600 dark:text-blue-400', 'badge' => 'bg-blue-500'],
        'green'  => ['bg' => 'bg-green-100 dark:bg-green-900/30', 'text' => 'text-green-600 dark:text-green-400', 'badge' => 'bg-green-500'],
        'purple' => ['bg' => 'bg-purple-100 dark:bg-purple-900/30', 'text' => 'text-purple-600 dark:text-purple-400', 'badge' => 'bg-purple-500'],
        'red'    => ['bg' => 'bg-red-100 dark:bg-red-900/30', 'text' => 'text-red-600 dark:text-red-400', 'badge' => 'bg-red-500'],
        'yellow' => ['bg' => 'bg-yellow-100 dark:bg-yellow-900/30', 'text' => 'text-yellow-600 dark:text-yellow-400', 'badge' => 'bg-yellow-500'],
        'pink'   => ['bg' => 'bg-pink-100 dark:bg-pink-900/30', 'text' => 'text-pink-600 dark:text-pink-400', 'badge' => 'bg-pink-500'],
        'cyan'   => ['bg' => 'bg-cyan-100 dark:bg-cyan-900/30', 'text' => 'text-cyan-600 dark:text-cyan-400', 'badge' => 'bg-cyan-500'],
    ];

    public function userTasks(): HasMany
    {
        return $this->hasMany(UserTask::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getTypeLabel(): string
    {
        return self::$typeLabels[$this->type] ?? $this->type;
    }

    public function getActionLabel(): string
    {
        return self::$actionLabels[$this->action] ?? $this->action;
    }

    public function getColorClasses(): array
    {
        return self::$colorClasses[$this->badge_color ?? 'orange'] ?? self::$colorClasses['orange'];
    }

    public function isAvailable(): bool
    {
        if (!$this->is_active) return false;
        $now = now();
        if ($this->start_at && $now->lt($this->start_at)) return false;
        if ($this->end_at && $now->gt($this->end_at)) return false;
        return true;
    }

    public function getPeriodKey(): ?string
    {
        return match ($this->type) {
            'daily'  => now()->format('Y-m-d'),
            'weekly' => now()->format('Y-\WW'),
            default  => null,
        };
    }

    public function getTypeBadgeClass(): string
    {
        return match ($this->type) {
            'daily'    => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
            'weekly'   => 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400',
            'one_time' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
            default    => 'bg-gray-100 text-gray-700 dark:bg-slate-800 dark:text-gray-400',
        };
    }

    public function completedCountForUser(int $userId): int
    {
        return $this->userTasks()
            ->where('user_id', $userId)
            ->where('status', 'claimed')
            ->count();
    }

    /**
     * Danh sách các thẻ HTML hợp lệ được trình soạn thảo trực quan (TinyMCE) sinh ra.
     * Dùng để phân biệt nội dung HTML với nội dung văn bản thuần nhập từ trước đây.
     */
    private const RICH_TEXT_TAGS = 'p|br|ul|ol|li|strong|b|em|i|u|s|a|h[1-6]|span|div|img|table|thead|tbody|tr|th|td|blockquote|hr|figure|code|pre';

    /**
     * Lọc sạch mã HTML soạn từ trình soạn thảo trực quan trước khi lưu vào CSDL hoặc hiển thị ra giao diện.
     *
     * Mục đích: chặn hoàn toàn nguy cơ chèn mã độc (Stored XSS) từ tài khoản Admin phụ
     * có quyền quản lý nhiệm vụ, vì nội dung này được hiển thị trực tiếp cho thành viên.
     *
     * @param  string|null  $html  Mã HTML thô do trình soạn thảo gửi lên
     * @return string|null         Mã HTML đã lọc sạch, hoặc null nếu nội dung rỗng
     */
    public static function sanitizeHtmlContent(?string $html): ?string
    {
        $html = trim((string) $html);
        if ($html === '') {
            return null;
        }

        // Xóa toàn bộ khối <script>, <style> kèm nội dung bên trong
        $html = preg_replace('#<\s*(script|style)\b[^>]*>.*?<\s*/\s*\1\s*>#is', '', $html);
        // Xóa các thẻ <script>/<style> còn sót lại do người dùng cố tình viết thiếu thẻ đóng
        $html = preg_replace('#<\s*/?\s*(script|style)\b[^>]*>#i', '', $html);
        // Xóa các thuộc tính sự kiện JavaScript kiểu onclick="...", onerror='...', onload=...
        $html = preg_replace('/\son[a-z]+\s*=\s*(?:"[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $html);
        // Vô hiệu hóa liên kết/nguồn tài nguyên dùng giao thức javascript:, vbscript: hoặc data:
        // Xử lý cả hai dạng: có dấu nháy (href="javascript:...") và không dấu nháy (href=javascript:...)
        $html = preg_replace('/\b(href|src)\s*=\s*(["\'])\s*(?:javascript|vbscript|data)\s*:[^"\']*\2/i', '$1="#"', $html);
        $html = preg_replace('/\b(href|src)\s*=\s*(?:javascript|vbscript|data)\s*:[^\s>]*/i', '$1="#"', $html);

        // Nếu sau khi lọc chỉ còn thẻ rỗng (ví dụ "<p>&nbsp;</p>") thì coi như không có nội dung
        $plainText = trim(preg_replace('/[\s\x{00A0}]+/u', '', html_entity_decode(strip_tags($html), ENT_QUOTES, 'UTF-8')));
        $hasMedia  = preg_match('/<(img|iframe|video|table|hr)\b/i', $html) === 1;

        return ($plainText === '' && !$hasMedia) ? null : trim($html);
    }

    /**
     * Chuyển nội dung lưu trong CSDL thành mã HTML an toàn để hiển thị ra giao diện.
     *
     * - Nội dung mới (soạn bằng trình soạn thảo trực quan TinyMCE) giữ nguyên định dạng HTML,
     *   nhưng vẫn được lọc lại một lần nữa để đảm bảo an toàn cho cả các bản ghi cũ.
     * - Nội dung cũ (văn bản thuần nhập trước đây) sẽ được escape để chống XSS
     *   và tự động chuyển ký tự xuống dòng thành thẻ <br> giúp hiển thị đúng như lúc nhập.
     */
    protected function toDisplayHtml(?string $value): string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return '';
        }

        // Chỉ coi là nội dung HTML khi thực sự chứa các thẻ định dạng hợp lệ của trình soạn thảo
        if (preg_match('#<\s*/?\s*(' . self::RICH_TEXT_TAGS . ')\b[^>]*>#i', $value) === 1) {
            return (string) self::sanitizeHtmlContent($value);
        }

        return nl2br(e($value));
    }

    /**
     * Mô tả ngắn dạng HTML dùng để hiển thị cho thành viên ngoài Storefront.
     */
    public function getDescriptionHtmlAttribute(): string
    {
        return $this->toDisplayHtml($this->description);
    }

    /**
     * Hướng dẫn thực hiện dạng HTML dùng để hiển thị cho thành viên ngoài Storefront.
     */
    public function getGuideHtmlAttribute(): string
    {
        return $this->toDisplayHtml($this->guide);
    }

    /**
     * Mô tả ngắn đã lược bỏ toàn bộ thẻ HTML, dùng cho các vị trí chỉ hiển thị 1 dòng
     * (danh sách nhiệm vụ trong Admin, tooltip, kết quả tìm kiếm...).
     */
    public function getDescriptionPlainAttribute(): string
    {
        $plain = html_entity_decode(strip_tags((string) $this->description), ENT_QUOTES, 'UTF-8');

        // Gom các khoảng trắng, ký tự xuống dòng và khoảng trắng đặc biệt (&nbsp;) về 1 dấu cách
        return trim(preg_replace('/[\s\x{00A0}]+/u', ' ', $plain));
    }
}
