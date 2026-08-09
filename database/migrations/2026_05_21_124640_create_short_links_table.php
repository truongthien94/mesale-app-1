<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Chạy migration tạo bảng liên kết rút gọn.
     * Bảng này dùng để lưu trữ liên kết gốc và mã hash tương ứng của nó nhằm hỗ trợ rút gọn link và theo dõi click.
     */
    public function up(): void
    {
        Schema::create('short_links', function (Blueprint $table) {
            $table->id();
            // Mã rút gọn duy nhất để nhận diện trên URL, vd: pzo928gj
            $table->string('code', 10)->unique()->index();
            // Đường dẫn đích nguyên bản (link affiliate của Shopee với các sub_id)
            $table->text('destination_url');
            // Liên kết với tài khoản người dùng, nullable nếu là khách vãng lai tự tạo link
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade');
            // Số lần liên kết này được click để phục vụ phân tích/thống kê trong tương lai
            $table->unsignedInteger('clicks')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Hoàn tác migration, xoá bảng liên kết rút gọn.
     */
    public function down(): void
    {
        Schema::dropIfExists('short_links');
    }
};
