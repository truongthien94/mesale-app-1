<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bảng lưu token thiết bị (FCM/APNs) để gửi thông báo đẩy (Push Notification) cho App Mobile.
 * Mỗi thiết bị đăng nhập sẽ đăng ký một token; token trùng sẽ được cập nhật gắn về đúng user mới nhất.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Kiểm tra xem bảng device_tokens đã tồn tại trong cơ sở dữ liệu chưa trước khi tạo mới
        if (!Schema::hasTable('device_tokens')) {
            Schema::create('device_tokens', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                // Token đăng ký nhận push do Firebase/APNs cấp cho thiết bị
                $table->string('token', 512)->unique();
                // Nền tảng thiết bị: android / ios / web
                $table->string('platform', 20)->default('android');
                // Tên thiết bị để người dùng nhận biết khi quản lý
                $table->string('device_name')->nullable();
                $table->timestamp('last_used_at')->nullable();
                $table->timestamps();

                $table->index('user_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('device_tokens');
    }
};
