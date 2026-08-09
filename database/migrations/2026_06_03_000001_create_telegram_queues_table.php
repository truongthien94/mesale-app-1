<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration tạo bảng telegram_queues để lưu hàng đợi gửi tin nhắn Telegram bất đồng bộ.
 * Hỗ trợ admin theo dõi trạng thái gửi, thử lại hoặc xóa log tương tự email queue.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('telegram_queues', function (Blueprint $table) {
            $table->id();
            // ID chat nhận tin nhắn (có thể là ID cá nhân hoặc ID group)
            $table->string('chat_id');
            // Nội dung tin nhắn cần gửi (hỗ trợ HTML format của Telegram)
            $table->text('message');
            // Trạng thái: pending (chờ gửi), sending (đang gửi), sent (đã gửi), failed (thất bại)
            $table->enum('status', ['pending', 'sending', 'sent', 'failed'])->default('pending');
            // Số lần thử gửi lại nếu thất bại (tối đa 3 lần)
            $table->unsignedTinyInteger('attempts')->default(0);
            // Thông báo lỗi nếu gửi thất bại
            $table->text('error_message')->nullable();
            // Thời điểm gửi thành công
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            // Index để cron job tối ưu hoá tốc độ truy vấn
            $table->index(['status', 'attempts']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('telegram_queues');
    }
};
