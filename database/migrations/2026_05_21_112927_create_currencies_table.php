<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Tạo bảng currencies lưu trữ cấu hình đa tiền tệ trong hệ thống.
     */
    public function up(): void
    {
        Schema::create('currencies', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('Tên tiền tệ (ví dụ: Việt Nam Đồng, Đô la Mỹ)');
            $table->string('code', 10)->unique()->comment('Mã tiền tệ (ví dụ: VND, USD, EUR)');
            $table->string('symbol', 10)->comment('Ký hiệu tiền tệ (ví dụ: ₫, $, €)');
            $table->decimal('exchange_rate', 15, 4)->default(1.0000)->comment('Tỷ giá quy đổi so với đồng tiền gốc VND (1 ngoại tệ = X VND)');
            $table->enum('symbol_position', ['before', 'after'])->default('after')->comment('Vị trí hiển thị ký hiệu tiền tệ (trước hoặc sau số tiền)');
            $table->boolean('is_active')->default(true)->comment('Trạng thái hoạt động của tiền tệ');
            $table->boolean('is_default')->default(false)->comment('Thiết lập tiền tệ mặc định');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     * Xóa bảng currencies khi rollback migration.
     */
    public function down(): void
    {
        Schema::dropIfExists('currencies');
    }
};
