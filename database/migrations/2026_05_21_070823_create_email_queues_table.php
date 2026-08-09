<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration tạo bảng email_queues để lưu hàng đợi gửi email hàng loạt.
 * Hệ thống sử dụng cron job URL để xử lý từng batch email thay vì gửi đồng thời.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_queues', function (Blueprint $table) {
            $table->id();
            // Địa chỉ email người nhận
            $table->string('to_email');
            // Tên người nhận (để hiển thị trong email)
            $table->string('to_name')->nullable();
            // Tiêu đề email
            $table->string('subject');
            // Nội dung HTML email
            $table->longText('body');
            // Trạng thái: pending (chờ gửi), sending (đang gửi), sent (đã gửi), failed (thất bại)
            $table->enum('status', ['pending', 'sending', 'sent', 'failed'])->default('pending');
            // Số lần thử gửi lại nếu thất bại
            $table->unsignedTinyInteger('attempts')->default(0);
            // Thông báo lỗi nếu gửi thất bại
            $table->text('error_message')->nullable();
            // Thời điểm gửi thành công
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            // Index để cron job lấy nhanh các email chờ gửi
            $table->index(['status', 'attempts']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_queues');
    }
};
