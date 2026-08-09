<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Setting;

/**
 * Migration bổ sung: Thêm cấu hình Feature Gate (mã bí mật) cho tính năng Lazada.
 * Dùng updateOrCreate để không ghi đè nếu web đã có cấu hình sẵn (an toàn cho cả cài mới lẫn cập nhật).
 */
return new class extends Migration
{
    public function up(): void
    {
        // Bcrypt hash của mã bí mật (mã gốc KHÔNG lưu trong code để chống đọc ngược)
        Setting::updateOrCreate(
            ['key' => 'lazada_feature_secret_hash'],
            ['value' => '$2y$12$nzu1qc0NhAUDsuaYTDmknecoHRp8jGy3Nkz4ZvxrGDIc1UvZkB5nq', 'description' => 'Bcrypt hash của mã bí mật mở khoá tính năng Lazada']
        );

        // Flag trạng thái mở khoá, mặc định khoá (0)
        Setting::updateOrCreate(
            ['key' => 'lazada_feature_unlocked'],
            ['value' => '0', 'description' => 'Trạng thái mở khoá tính năng Lazada (0: Khoá, 1: Đã mở)']
        );
    }

    public function down(): void
    {
        Setting::whereIn('key', [
            'lazada_feature_secret_hash',
            'lazada_feature_unlocked',
        ])->delete();
    }
};
