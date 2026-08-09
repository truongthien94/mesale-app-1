<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Model TelegramQueue - Quản lý hàng đợi gửi tin nhắn Telegram.
 * 
 * Lưu trữ nhật ký và trạng thái gửi của từng tin nhắn Telegram bất đồng bộ,
 * cho phép quản trị viên theo dõi trạng thái, thử lại hoặc xóa bản ghi từ Admin Panel.
 */
class TelegramQueue extends Model
{
    use HasFactory;

    protected $table = 'telegram_queues';

    protected $fillable = [
        'chat_id',
        'message',
        'status',
        'attempts',
        'error_message',
        'sent_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    /**
     * Tạo tin nhắn mới đưa vào hàng đợi.
     *
     * @param string $chatId ID chat nhận
     * @param string $message Nội dung tin nhắn
     * @return self
     */
    public static function enqueue(string $chatId, string $message): self
    {
        return self::create([
            'chat_id' => $chatId,
            'message' => $message,
            'status' => 'pending',
            'attempts' => 0,
        ]);
    }

    /**
     * Lấy danh sách tin nhắn đang chờ gửi (tối đa $limit bản ghi mỗi lần).
     * Chỉ lấy những tin nhắn có trạng thái 'pending' và số lần thử < 3.
     *
     * @param int $limit Số lượng tin nhắn tối đa lấy ra mỗi lần (batch size)
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getPendingBatch(int $limit = 10)
    {
        return self::where('status', 'pending')
            ->where('attempts', '<', 3)
            ->orderBy('id', 'asc')
            ->limit($limit)
            ->get();
    }

    /**
     * Đánh dấu tin nhắn đã gửi thành công.
     */
    public function markAsSent(): void
    {
        $this->update([
            'status' => 'sent',
            'sent_at' => now(),
            'error_message' => null,
        ]);
    }

    /**
     * Đánh dấu gửi thất bại kèm thông báo lỗi.
     * Nếu số lần thử vượt quá 3, chuyển trạng thái sang 'failed'.
     *
     * @param string $errorMessage Thông báo lỗi chi tiết
     */
    public function markAsFailed(string $errorMessage): void
    {
        $newAttempts = $this->attempts + 1;
        $this->update([
            'status' => $newAttempts >= 3 ? 'failed' : 'pending',
            'attempts' => $newAttempts,
            'error_message' => $errorMessage,
        ]);
    }
}
