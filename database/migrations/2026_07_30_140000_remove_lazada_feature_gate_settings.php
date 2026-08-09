<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use App\Models\Setting;

/**
 * Migration bổ sung: Xoá bỏ cơ chế Feature Gate (khoá bằng mã bí mật) của tính năng hoàn tiền Lazada.
 * Kể từ nay tính năng Lazada được phát hành chính thức, mọi website đều dùng được ngay
 * mà không cần nhập mã bí mật để mở khoá.
 */
return new class extends Migration
{
    public function up(): void
    {
        $keys = [
            'lazada_feature_secret_hash',
            'lazada_feature_unlocked',
        ];

        Setting::whereIn('key', $keys)->delete();

        // Xoá luôn bộ nhớ đệm của các khoá cấu hình này để hệ thống không đọc lại giá trị cũ
        foreach ($keys as $key) {
            Cache::forget("setting.{$key}");
        }
    }

    public function down(): void
    {
        // Khi rollback, phục hồi lại cấu hình Feature Gate nhưng đặt ở trạng thái ĐÃ MỞ KHOÁ (1)
        // để tránh trường hợp website đang vận hành hoàn tiền Lazada bị khoá mất giao diện cấu hình.
        Setting::updateOrCreate(
            ['key' => 'lazada_feature_secret_hash'],
            ['value' => '$2y$12$nzu1qc0NhAUDsuaYTDmknecoHRp8jGy3Nkz4ZvxrGDIc1UvZkB5nq', 'description' => 'Bcrypt hash của mã bí mật mở khoá tính năng Lazada']
        );

        Setting::updateOrCreate(
            ['key' => 'lazada_feature_unlocked'],
            ['value' => '1', 'description' => 'Trạng thái mở khoá tính năng Lazada (0: Khoá, 1: Đã mở)']
        );

        Cache::forget('setting.lazada_feature_secret_hash');
        Cache::forget('setting.lazada_feature_unlocked');
    }
};
