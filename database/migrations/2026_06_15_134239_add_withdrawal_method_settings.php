<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Thêm cấu hình mặc định cho rút tiền qua ngân hàng và ví điện tử, mặc định luôn bật ('1')
        DB::table('settings')->updateOrInsert(
            ['key' => 'withdraw_bank_enabled'],
            [
                'value' => '1',
                'description' => 'Cho phép rút tiền qua tài khoản ngân hàng (1: Bật, 0: Tắt)',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        DB::table('settings')->updateOrInsert(
            ['key' => 'withdraw_wallet_enabled'],
            [
                'value' => '1',
                'description' => 'Cho phép rút tiền qua ví điện tử (1: Bật, 0: Tắt)',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('settings')
            ->whereIn('key', ['withdraw_bank_enabled', 'withdraw_wallet_enabled'])
            ->delete();
    }
};
