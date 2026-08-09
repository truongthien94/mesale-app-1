<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Migration bổ sung: Thêm UNIQUE constraint chống trùng hoa hồng MLM
     * và tách riêng cột total_referral_earned cho hoa hồng tiếp thị liên kết.
     */
    public function up(): void
    {
        // 1. Thêm cột total_referral_earned vào bảng users
        // Tách riêng hoa hồng MLM khỏi total_cashback để thống kê chính xác
        Schema::table('users', function (Blueprint $table) {
            $table->decimal('total_referral_earned', 15, 2)->default(0.00)
                  ->after('total_cashback')
                  ->comment('Tổng hoa hồng tiếp thị liên kết MLM đã nhận (tách riêng khỏi total_cashback)');
        });

        // 2. Thêm UNIQUE constraint chống trùng hoa hồng trên referral_commissions
        // Đảm bảo mỗi đơn hàng chỉ phát sinh tối đa 1 hoa hồng cho mỗi referrer ở mỗi tầng
        Schema::table('referral_commissions', function (Blueprint $table) {
            $table->unique(
                ['cashback_history_id', 'referrer_id', 'level'],
                'unique_commission_per_order'
            );
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('total_referral_earned');
        });

        Schema::table('referral_commissions', function (Blueprint $table) {
            $table->dropUnique('unique_commission_per_order');
        });
    }
};
