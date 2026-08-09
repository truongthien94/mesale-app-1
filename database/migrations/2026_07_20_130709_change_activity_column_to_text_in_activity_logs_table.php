<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Lý do: Chuyển cột activity từ VARCHAR sang TEXT để tránh lỗi tràn dữ liệu (Data too long) khi ghi các log hoạt động chi tiết
     * ví dụ như log tự động thu hồi đơn hàng hoàn tiền có lý do dài từ TikTok/Shopee API.
     */
    public function up(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->text('activity')->change();
        });
    }

    /**
     * Reverse the migrations.
     * Khôi phục cột activity về VARCHAR(255)
     */
    public function down(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->string('activity', 255)->change();
        });
    }
};
