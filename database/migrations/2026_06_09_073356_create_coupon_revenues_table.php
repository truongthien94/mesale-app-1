<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Tạo bảng coupon_revenues để lưu trữ doanh thu và lợi nhuận từ các đơn hàng mã giảm giá (utm_content hoặc utm_source = coupon_utm_source).
     */
    public function up(): void
    {
        Schema::create('coupon_revenues', function (Blueprint $table) {
            $table->id();
            $table->string('order_id')->unique()->comment('Mã đơn hàng Shopee');
            $table->text('product_name')->nullable()->comment('Tên sản phẩm');
            $table->decimal('original_price', 15, 2)->default(0)->comment('Giá gốc của đơn hàng');
            $table->decimal('commission_amount', 15, 2)->default(0)->comment('Doanh thu hoa hồng nhận từ Shopee');
            $table->string('status')->default('pending')->comment('Trạng thái đơn: pending (chờ đối soát), approved (đã thanh toán), rejected (đã hủy)');
            $table->string('shop_name')->nullable()->comment('Tên shop bán hàng');
            $table->string('fraud_reason')->nullable()->comment('Lý do gian lận nếu có');
            $table->timestamps();

            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coupon_revenues');
    }
};
