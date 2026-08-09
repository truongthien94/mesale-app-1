<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Model EmailQueue - Quản lý hàng đợi gửi email hàng loạt.
 * 
 * Mỗi bản ghi đại diện cho 1 email cần gửi. Cron job sẽ quét và gửi từng batch
 * để tránh quá tải SMTP server khi gửi thông báo cho hàng trăm/nghìn thành viên.
 */
class EmailQueue extends Model
{
    use HasFactory;

    protected $table = 'email_queues';

    protected $fillable = [
        'to_email',
        'to_name',
        'subject',
        'body',
        'status',
        'attempts',
        'error_message',
        'sent_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    /**
     * Lấy danh sách email đang chờ gửi (tối đa $limit bản ghi mỗi lần).
     * Chỉ lấy những email có trạng thái 'pending' và số lần thử < 3.
     *
     * @param int $limit Số lượng email tối đa lấy ra mỗi lần (batch size)
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
     * Đánh dấu email đã gửi thành công.
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
     * Đánh dấu email gửi thất bại kèm thông báo lỗi.
     * Nếu đã vượt quá số lần thử cho phép (3 lần), chuyển trạng thái sang 'failed'.
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

    /**
     * Mối quan hệ với thành viên nhận email thông qua to_email và email của bảng users.
     * Giúp hệ thống dễ dàng liên kết từ hàng đợi email tới hồ sơ thành viên để chỉnh sửa hoặc tra cứu nhanh.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'to_email', 'email');
    }
}

