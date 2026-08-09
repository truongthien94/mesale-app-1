<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Thêm cột status_timeline lưu nhật ký dòng thời gian thay đổi trạng thái của đơn hoàn tiền.
     * Mỗi phần tử lưu: event (loại sự kiện), at (thời gian), source (nguồn xử lý), reason (lý do nếu có).
     */
    public function up(): void
    {
        Schema::table('cashback_histories', function (Blueprint $table) {
            $table->json('status_timeline')->nullable()->after('click_metadata');
        });
    }

    public function down(): void
    {
        Schema::table('cashback_histories', function (Blueprint $table) {
            $table->dropColumn('status_timeline');
        });
    }
};
