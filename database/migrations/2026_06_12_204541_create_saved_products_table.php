<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('saved_products', function (Blueprint $table) {
            $table->id();
            // ID người dùng liên kết
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            // Tên sản phẩm
            $table->string('name', 500);
            // Ảnh sản phẩm
            $table->text('image')->nullable();
            // Giá bán sản phẩm
            $table->decimal('price', 15, 2)->default(0);
            // Số tiền được hoàn dự kiến
            $table->decimal('cashback_amount', 15, 2)->default(0);
            // Link rút gọn affiliate mua hàng
            $table->text('affiliate_url');
            // Link gốc Shopee
            $table->text('product_url')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('saved_products');
    }
};
