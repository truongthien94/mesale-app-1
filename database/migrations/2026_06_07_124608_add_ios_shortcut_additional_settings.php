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
        $settings = [
            [
                'key' => 'ios_shortcut_status',
                'value' => '0',
                'description' => 'Trạng thái hoạt động của phím tắt iPhone (1: Bật, 0: Tắt)',
            ],
            [
                'key' => 'ios_shortcut_desc',
                'value' => 'Hướng dẫn cài đặt phím tắt trên điện thoại iPhone giúp chuyển đổi link hoàn tiền Shopee siêu nhanh trong 1 giây',
                'description' => 'Mô tả ngắn trang hướng dẫn phím tắt',
            ],
            [
                'key' => 'ios_shortcut_guide',
                'value' => 'Không cần truy cập website! Chỉ cần sao chép link Shopee trên điện thoại và chạy Phím tắt (Shortcuts). Hệ thống sẽ tự động nhận diện tài khoản, phân tích sản phẩm và copy lại link hoàn tiền mới vào bộ nhớ tạm.',
                'description' => 'Mô tả cách hoạt động của phím tắt',
            ]
        ];

        foreach ($settings as $setting) {
            $exists = DB::table('settings')->where('key', $setting['key'])->exists();
            if (!$exists) {
                DB::table('settings')->insert(array_merge($setting, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ]));
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('settings')->whereIn('key', ['ios_shortcut_status', 'ios_shortcut_desc', 'ios_shortcut_guide'])->delete();
    }
};
