<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Tạo bảng lưu trữ thông tin các ngôn ngữ trong hệ thống.
     */
    public function up(): void
    {
        Schema::create('languages', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('Tên hiển thị của ngôn ngữ (ví dụ: Tiếng Việt, English)');
            $table->string('code')->unique()->comment('Mã ngôn ngữ chuẩn ISO (ví dụ: vi, en, zh)');
            $table->string('flag')->nullable()->comment('Đường dẫn ảnh lá cờ quốc gia đại diện');
            $table->boolean('is_active')->default(true)->comment('Trạng thái kích hoạt ngôn ngữ');
            $table->boolean('is_default')->default(false)->comment('Đánh dấu đây là ngôn ngữ mặc định của hệ thống');
            $table->integer('order')->default(0)->comment('Thứ tự sắp xếp hiển thị');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     * Xóa bảng languages khi rollback migration.
     */
    public function down(): void
    {
        Schema::dropIfExists('languages');
    }
};
