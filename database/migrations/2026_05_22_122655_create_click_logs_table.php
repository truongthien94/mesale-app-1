<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tạo bảng click_logs để lưu toàn bộ lịch sử lượt click link rút gọn.
     * Mỗi lần có người click vào link rút gọn sẽ ghi thêm 1 dòng log mới thay vì ghi đè.
     */
    public function up(): void
    {
        Schema::create('click_logs', function (Blueprint $table) {
            $table->id();
            // Liên kết với bảng short_links, xóa link rút gọn thì xóa luôn log click
            $table->foreignId('short_link_id')->constrained('short_links')->onDelete('cascade');
            // Địa chỉ IP đã validate (IPv4/IPv6)
            $table->string('ip_address', 45)->nullable()->comment('Địa chỉ IP người click');
            // Loại thiết bị: Mobile, Tablet, Desktop
            $table->string('device', 20)->default('Desktop')->comment('Loại thiết bị');
            // Vị trí địa lý dựa vào IP (city, country)
            $table->string('location', 200)->nullable()->comment('Vị trí địa lý');
            // User Agent đã sanitize (tối đa 500 ký tự)
            $table->string('user_agent', 500)->nullable()->comment('User Agent đã sanitize');
            // Chỉ dùng created_at, không cần updated_at vì log chỉ ghi 1 lần
            $table->timestamp('created_at')->nullable();

            // Index theo short_link_id để truy vấn nhanh khi xem chi tiết
            $table->index('short_link_id');
        });
    }

    /**
     * Hoàn tác migration, xoá bảng click_logs.
     */
    public function down(): void
    {
        Schema::dropIfExists('click_logs');
    }
};
