<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Chạy migration để tạo bảng lưu trữ tài khoản Shopee Affiliate.
     */
    public function up(): void
    {
        Schema::create('shopee_accounts', function (Blueprint $table) {
            $table->id();
            // Tên gợi nhớ của tài khoản Shopee Affiliate
            $table->string('name')->nullable();
            // Cookie tài khoản Shopee Affiliate (lưu dưới dạng JSON text)
            $table->text('cookie');
            // Username hoặc thông tin định danh tài khoản từ Shopee (nếu có)
            $table->string('username')->nullable();
            // Trạng thái tài khoản: active (hoạt động tốt), expired (cookie hết hạn/lỗi)
            $table->string('status')->default('active');
            // Ghi nhận lỗi chi tiết khi thực hiện đồng bộ tự động thất bại
            $table->text('error_message')->nullable();
            // Thời điểm cuối cùng đồng bộ báo cáo thành công
            $table->timestamp('last_sync_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Hoàn tác migration, xóa bảng shopee_accounts.
     */
    public function down(): void
    {
        Schema::dropIfExists('shopee_accounts');
    }
};
