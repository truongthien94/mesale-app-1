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
        // Bảng quản lý quà tặng
        Schema::create('gifts', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('image')->nullable();
            $table->text('description')->nullable();
            $table->decimal('price', 15, 2);
            $table->integer('stock')->default(0);
            $table->string('type')->default('voucher'); // voucher, phone_card, giftcode, physical
            $table->boolean('status')->default(true);
            $table->timestamps();
        });

        // Bảng quản lý lượt đổi quà
        Schema::create('gift_redemptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('gift_id')->constrained()->onDelete('cascade');
            $table->decimal('amount', 15, 2);
            $table->text('shipping_info')->nullable(); // JSON lưu thông tin nhận hàng (họ tên, sđt, địa chỉ,...)
            $table->string('status')->default('pending'); // pending, approved, rejected
            $table->text('gift_data')->nullable(); // lưu mã thẻ, code voucher,...
            $table->text('notes')->nullable(); // ghi chú của admin (mã vận đơn, lý do từ chối,...)
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gift_redemptions');
        Schema::dropIfExists('gifts');
    }
};
