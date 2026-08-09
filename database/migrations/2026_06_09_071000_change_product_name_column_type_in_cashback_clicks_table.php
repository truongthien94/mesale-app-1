<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Thay đổi kiểu dữ liệu cột product_name trong bảng cashback_clicks từ VARCHAR(255) thành TEXT
     * để đồng bộ với bảng cashback_histories, hỗ trợ lưu trữ tên sản phẩm Shopee siêu dài lúc người dùng click.
     */
    public function up(): void
    {
        Schema::table('cashback_clicks', function (Blueprint $table) {
            // Thay đổi cột product_name thành kiểu TEXT
            $table->text('product_name')->comment('Tên sản phẩm Shopee lúc click (hỗ trợ độ dài lớn)')->change();
        });
    }

    /**
     * Reverse the migrations.
     * Khôi phục kiểu dữ liệu cột product_name về VARCHAR(255).
     */
    public function down(): void
    {
        Schema::table('cashback_clicks', function (Blueprint $table) {
            // Khôi phục cột product_name về kiểu string (VARCHAR 255)
            $table->string('product_name')->comment('Tên sản phẩm')->change();
        });
    }
};
