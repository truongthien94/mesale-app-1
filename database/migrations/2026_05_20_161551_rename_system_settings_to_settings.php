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
        Schema::rename('system_settings', 'settings');

        // Bổ sung khoá cấu hình logo website mặc định nếu chưa tồn tại
        if (Schema::hasTable('settings')) {
            $exists = \Illuminate\Support\Facades\DB::table('settings')->where('key', 'site_logo')->exists();
            if (!$exists) {
                \Illuminate\Support\Facades\DB::table('settings')->insert([
                    'key' => 'site_logo',
                    'value' => '', // Mặc định để trống để sử dụng logo text/icon hoặc điền link ảnh sau
                    'description' => 'Đường dẫn ảnh logo của website',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::rename('settings', 'system_settings');
    }
};
