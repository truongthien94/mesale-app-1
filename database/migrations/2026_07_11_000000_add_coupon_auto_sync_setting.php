<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Thêm cấu hình coupon_auto_sync để tách biệt việc tự động đồng bộ mã giảm giá từ API
     * ra khỏi công tắc hiển thị (coupon_status). Nhờ đó admin có thể TẮT đồng bộ tự động
     * và chỉ dùng các mã giảm giá tự nhập tay, nhưng vẫn hiển thị trang mã giảm giá.
     */
    public function up(): void
    {
        $exists = DB::table('settings')->where('key', 'coupon_auto_sync')->exists();
        if (!$exists) {
            DB::table('settings')->insert([
                'key' => 'coupon_auto_sync',
                'value' => '1',
                'description' => 'Tự động đồng bộ mã giảm giá từ API theo lịch (1: Bật, 0: Tắt)',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('settings')->where('key', 'coupon_auto_sync')->delete();
    }
};
