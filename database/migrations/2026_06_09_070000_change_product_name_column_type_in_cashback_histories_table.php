<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Thay đổi kiểu dữ liệu cột product_name trong bảng cashback_histories từ VARCHAR(255) thành TEXT
     * để hỗ trợ lưu trữ tên các sản phẩm Shopee có độ dài lớn mà không bị lỗi tràn dữ liệu.
     */
    public function up(): void
    {
        Schema::table('cashback_histories', function (Blueprint $table) {
            // Thay đổi cột product_name thành kiểu TEXT
            $table->text('product_name')->comment('Tên sản phẩm Shopee (hỗ trợ độ dài lớn)')->change();
        });
    }

    /**
     * Reverse the migrations.
     * Khôi phục kiểu dữ liệu cột product_name về VARCHAR(255).
     */
    public function down(): void
    {
        Schema::table('cashback_histories', function (Blueprint $table) {
            // Khôi phục lại cột product_name về kiểu string (VARCHAR 255)
            $table->string('product_name')->comment('Tên sản phẩm Shopee')->change();
        });
    }
};
