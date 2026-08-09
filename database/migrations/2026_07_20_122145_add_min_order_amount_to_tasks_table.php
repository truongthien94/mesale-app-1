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
        Schema::table('tasks', function (Blueprint $table) {
            // Thêm trường min_order_amount để lưu giá trị đơn hàng tối thiểu khi làm nhiệm vụ
            $table->decimal('min_order_amount', 15, 2)->default(0)->after('target_count')->comment('Giá trị đơn hàng tối thiểu để được tính nhiệm vụ');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            // Xóa trường min_order_amount khi rollback migration
            $table->dropColumn('min_order_amount');
        });
    }
};
