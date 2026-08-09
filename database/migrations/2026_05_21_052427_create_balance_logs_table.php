<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Chạy migration tạo bảng balance_logs (Lịch sử biến động số dư).
     */
    public function up(): void
    {
        Schema::create('balance_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->comment('Liên kết người dùng');
            $table->decimal('amount_before', 15, 2)->comment('Số dư trước khi thay đổi');
            $table->decimal('amount_change', 15, 2)->comment('Số tiền biến động (âm hoặc dương)');
            $table->decimal('amount_after', 15, 2)->comment('Số dư sau khi thay đổi');
            $table->string('type')->comment('Loại biến động: cashback, referral, checkin, withdraw_request, withdraw_refund, admin_adjust');
            $table->string('description')->comment('Mô tả chi tiết nội dung biến động');
            $table->timestamps();

            // Khai báo khóa ngoại và các index để tối ưu hóa truy vấn dòng tiền
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index('user_id');
            $table->index('type');
            $table->index('created_at');
        });
    }

    /**
     * Thu hồi migration.
     */
    public function down(): void
    {
        Schema::dropIfExists('balance_logs');
    }
};
