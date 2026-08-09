<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration tạo bảng api_logs — lưu trữ nhật ký mọi lần gọi API.
 *
 * Mục đích: Giúp Admin giám sát hoạt động sử dụng API của thành viên,
 * phát hiện bất thường, kiểm soát tần suất gọi và hỗ trợ điều tra sự cố.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_logs', function (Blueprint $table) {
            $table->id();

            // Thành viên gọi API (null nếu request chưa xác thực được — ví dụ token sai)
            $table->unsignedBigInteger('user_id')->nullable()->index();

            // HTTP method: GET, POST, PUT, DELETE
            $table->string('method', 10);

            // Đường dẫn API đầy đủ (ví dụ: /api/v1/bot/orders)
            $table->string('endpoint', 500);

            // Phân loại nhóm API để lọc nhanh: openapi hoặc bot
            $table->string('api_group', 20)->index();

            // HTTP status code trả về (200, 401, 403, 404, 500...)
            $table->smallInteger('status_code')->nullable();

            // JSON encode dữ liệu request gửi lên (đã lọc trường nhạy cảm)
            $table->text('request_data')->nullable();

            // Thời gian xử lý request (đơn vị millisecond)
            $table->unsignedInteger('response_time_ms')->nullable();

            // Địa chỉ IP nguồn gọi API
            $table->string('ip_address', 45)->nullable();

            // User-Agent header giúp nhận diện client/bot
            $table->string('user_agent', 500)->nullable();

            // Chỉ cần created_at, không cần updated_at cho bảng log chỉ ghi 1 lần
            $table->timestamp('created_at')->useCurrent();

            // Khóa ngoại tham chiếu đến bảng users
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');

            // Index tổ hợp hỗ trợ lọc theo thời gian (mặc định sắp xếp mới nhất trước)
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_logs');
    }
};
