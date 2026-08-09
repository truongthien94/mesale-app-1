<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Tạo bảng pages lưu trữ các trang nội dung tĩnh (Điều khoản dịch vụ, Chính sách bảo mật, v.v.)
 * Quản trị viên có thể tự do tạo, sửa, xóa các trang tùy ý từ Admin Panel.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            // Tiêu đề trang (ví dụ: Điều khoản dịch vụ)
            $table->string('title');
            // Slug dùng cho URL thân thiện SEO (ví dụ: dieu-khoan-dich-vu)
            $table->string('slug')->unique();
            // Nội dung HTML đầy đủ của trang
            $table->longText('content')->nullable();
            // Trạng thái xuất bản: published (hiển thị) / draft (nháp, ẩn)
            $table->enum('status', ['published', 'draft'])->default('published');
            // Thứ tự hiển thị (dùng để sắp xếp nếu cần)
            $table->integer('sort_order')->default(0);
            // SEO Meta Description tùy chỉnh cho trang
            $table->string('meta_description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pages');
    }
};
