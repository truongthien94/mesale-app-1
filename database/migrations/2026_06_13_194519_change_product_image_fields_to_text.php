<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Chạy migration để thay đổi kiểu dữ liệu các cột hình ảnh.
     * Giải thích: Chuyển đổi các cột lưu trữ URL hình ảnh sản phẩm từ VARCHAR(255) sang TEXT
     * nhằm hỗ trợ lưu trữ các đường dẫn hình ảnh có độ dài lớn (ví dụ link CDN của TikTok Shop chứa mã token bảo mật dài),
     * tránh hiện tượng đường dẫn bị cắt cụt dẫn đến lỗi hiển thị hình ảnh trên giao diện.
     */
    public function up(): void
    {
        // Thay đổi cột product_image trong bảng click lịch sử hoàn tiền sang kiểu TEXT
        Schema::table('cashback_clicks', function (Blueprint $table) {
            $table->text('product_image')->nullable()->change();
        });

        // Thay đổi cột product_image trong bảng lịch sử nhận hoàn tiền sang kiểu TEXT
        Schema::table('cashback_histories', function (Blueprint $table) {
            $table->text('product_image')->nullable()->change();
        });

        // Thay đổi cột image trong bảng cache sản phẩm sang kiểu TEXT
        Schema::table('products', function (Blueprint $table) {
            $table->text('image')->nullable()->change();
        });
    }

    /**
     * Hoàn tác migration, khôi phục về VARCHAR(255).
     */
    public function down(): void
    {
        Schema::table('cashback_clicks', function (Blueprint $table) {
            $table->string('product_image', 255)->nullable()->change();
        });

        Schema::table('cashback_histories', function (Blueprint $table) {
            $table->string('product_image', 255)->nullable()->change();
        });

        Schema::table('products', function (Blueprint $table) {
            $table->string('image', 255)->nullable()->change();
        });
    }
};
