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
        Schema::table('users', function (Blueprint $table) {
            // Thêm cột lưu trữ số lượt click vào link giới thiệu
            $table->unsignedInteger('referral_clicks')->default(0)->after('referred_by')->comment('Số lượt nhấp vào liên kết giới thiệu');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Xóa cột khi rollback migration
            $table->dropColumn('referral_clicks');
        });
    }
};
