<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Bổ sung các cấu hình mặc định cho chức năng Giftcode mà không làm mất dữ liệu hiện có.
     */
    public function up(): void
    {
        $settings = [
            ['key' => 'gift_code_enabled', 'value' => '1', 'description' => 'Bật/Tắt chức năng nhập Giftcode nhận thưởng (1: Bật, 0: Tắt)'],
            ['key' => 'gift_code_intro', 'value' => 'Nhập mã quà tặng (Giftcode) được phát từ các sự kiện, minigame hoặc admin để nhận thưởng cộng thẳng vào số dư ví khả dụng của bạn.', 'description' => 'Nội dung mô tả hiển thị ở trang nhập Giftcode của thành viên'],
        ];

        foreach ($settings as $setting) {
            // Dùng updateOrInsert để không ghi đè nếu admin đã tùy chỉnh trước đó
            DB::table('settings')->updateOrInsert(
                ['key' => $setting['key']],
                ['value' => $setting['value'], 'description' => $setting['description']]
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('settings')->whereIn('key', ['gift_code_enabled', 'gift_code_intro'])->delete();
    }
};
