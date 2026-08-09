<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Thêm các trường phí và thực nhận vào bảng withdrawals để phục vụ ghi nhận lịch sử rút tiền động.
     */
    public function up(): void
    {
        Schema::table('withdrawals', function (Blueprint $table) {
            $table->decimal('fee', 15, 2)->default(0.00)->after('amount')->comment('Phí rút tiền của giao dịch');
            $table->decimal('real_amount', 15, 2)->default(0.00)->after('fee')->comment('Số tiền thực nhận sau khi trừ phí');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('withdrawals', function (Blueprint $table) {
            $table->dropColumn(['fee', 'real_amount']);
        });
    }
};
