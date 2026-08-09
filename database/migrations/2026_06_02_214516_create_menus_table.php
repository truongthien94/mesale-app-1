<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Chạy migration để tạo bảng menus lưu trữ danh sách menu tùy biến.
     */
    public function up(): void
    {
        Schema::create('menus', function (Blueprint $table) {
            $table->id(); // Khóa chính tự tăng
            $table->string('title'); // Tên hiển thị của menu
            $table->string('url')->nullable(); // Đường dẫn liên kết (URL) của menu, nullable nếu là menu cha rỗng
            $table->string('position')->default('header'); // Vị trí hiển thị (ví dụ: header, footer)
            $table->string('target')->default('_self'); // Cách mở liên kết (_self, _blank)
            $table->integer('order')->default(0); // Thứ tự sắp xếp hiển thị
            $table->boolean('status')->default(true); // Trạng thái hiển thị (1: hiển thị, 0: ẩn)
            $table->unsignedBigInteger('parent_id')->nullable(); // ID của menu cha để hỗ trợ cấu trúc phân cấp (nếu cần)
            $table->timestamps(); // Cột created_at và updated_at

            // Thiết lập khóa ngoại liên kết cha con của menu
            $table->foreign('parent_id')
                  ->references('id')
                  ->on('menus')
                  ->onDelete('cascade');
        });
    }

    /**
     * Hủy bỏ migration và xóa bảng menus.
     */
    public function down(): void
    {
        Schema::dropIfExists('menus');
    }
};
