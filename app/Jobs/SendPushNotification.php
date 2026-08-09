<?php

namespace App\Jobs;

use App\Services\PushNotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

/**
 * Job gửi thông báo đẩy (Push) tới thiết bị của một thành viên qua hàng đợi (queue).
 *
 * Dùng hàng đợi thay vì gửi đồng bộ để:
 *  - Không làm chậm request đang xử lý.
 *  - Không gây treo khi gửi thông báo hàng loạt (broadcast) cho nhiều nghìn user
 *    (mỗi user một job nhỏ, worker xử lý nền tuần tự).
 */
class SendPushNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    /** Số lần thử lại nếu gặp lỗi tạm thời (mạng/FCM). */
    public int $tries = 2;

    /** Thời gian tối đa cho phép job chạy (giây). */
    public int $timeout = 30;

    /**
     * @param  array<string,mixed>  $data  Payload dữ liệu kèm theo push
     */
    public function __construct(
        public int $userId,
        public string $title,
        public string $body,
        public array $data = [],
    ) {}

    public function handle(PushNotificationService $push): void
    {
        $push->sendToUser($this->userId, $this->title, $this->body, $this->data);
    }
}
