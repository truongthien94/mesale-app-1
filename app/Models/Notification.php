<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Notification extends Model
{
    use HasFactory;

    // Danh sách cột điền thông tin
    protected $fillable = [
        'user_id',
        'title',
        'content',
        'type',
        'is_read'
    ];

    // Ép kiểu
    protected $casts = [
        'is_read' => 'boolean',
    ];

    /**
     * Đăng ký sự kiện model: mỗi khi tạo thông báo cho một thành viên, tự động đẩy
     * thông báo (Push) tới các thiết bị đã đăng ký của họ thông qua hàng đợi (queue).
     *
     * Dùng queued job (chạy nền) thay vì gửi đồng bộ nên:
     *  - Không làm tăng độ trễ request đang xử lý.
     *  - Chạy ngoài giao dịch DB (không giữ khóa dòng khi gọi HTTP tới FCM).
     *  - An toàn khi gửi thông báo hàng loạt (broadcast) cho nhiều nghìn user.
     *
     * Chỉ đẩy job khi hệ thống push đã được cấu hình (có Firebase Service Account)
     * để tránh phát sinh job rỗng không cần thiết.
     */
    protected static function booted(): void
    {
        static::created(function (Notification $notification) {
            if (empty($notification->user_id)) {
                return;
            }

            // Bỏ qua nếu chưa cấu hình push (đọc từ cache nên rất nhẹ, kể cả trong vòng lặp broadcast)
            if (!app(\App\Services\PushNotificationService::class)->isConfigured()) {
                return;
            }

            \App\Jobs\SendPushNotification::dispatch(
                (int) $notification->user_id,
                (string) $notification->title,
                (string) $notification->content,
                ['notification_id' => $notification->id],
            );
        });
    }

    /**
     * Mối quan hệ: Thông báo thuộc về một Người dùng (User).
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
