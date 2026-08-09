<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Chạy migration để thêm thông tin sản phẩm vào bảng short_links.
     * Các thông tin này sẽ được dùng để tạo thẻ Open Graph hiển thị ảnh và tiêu đề khi chia sẻ lên MXH.
     */
    public function up(): void
    {
        Schema::table('short_links', function (Blueprint $table) {
            // Thêm cột lưu tiêu đề sản phẩm phục vụ hiển thị Open Graph khi share mạng xã hội
            $table->text('product_name')->nullable()->after('destination_url');
            
            // Thêm cột lưu ảnh sản phẩm (dùng kiểu text vì URL CDN của Shopee và TikTok có thể rất dài)
            $table->text('product_image')->nullable()->after('product_name');
        });
    }

    /**
     * Hoàn tác migration, xóa các cột thông tin sản phẩm.
     */
    public function down(): void
    {
        Schema::table('short_links', function (Blueprint $table) {
            $table->dropColumn(['product_name', 'product_image']);
        });
    }
};
