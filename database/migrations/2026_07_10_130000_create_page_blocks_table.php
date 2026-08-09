<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bảng lưu trữ các block của trình dựng trang (Page Builder).
 * Thay thế cách quản lý giao diện homepage rời rạc bằng các key hp_* trong bảng settings,
 * cho phép kéo-thả sắp xếp, bật/tắt và chỉnh sửa nội dung từng block như WordPress.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('page_blocks', function (Blueprint $table) {
            $table->id();
            $table->string('page')->default('home')->index();   // Trang chứa block (mặc định homepage)
            $table->string('type');                              // Loại block: hero, timeline, steps, features, stats, coupons, blog, html, gallery
            $table->string('name')->nullable();                  // Nhãn hiển thị trong trang quản trị
            $table->json('settings')->nullable();                // Toàn bộ nội dung/cấu hình của block
            $table->integer('sort_order')->default(0)->index();  // Thứ tự hiển thị (kéo-thả)
            $table->boolean('enabled')->default(true);           // Bật/tắt hiển thị
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('page_blocks');
    }
};
