<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Language;

class LanguageSeeder extends Seeder
{
    /**
     * Chạy dữ liệu hạt giống (Seeder) khởi tạo các ngôn ngữ hệ thống.
     */
    public function run(): void
    {
        // 1. Khởi tạo ngôn ngữ Tiếng Việt làm mặc định
        Language::updateOrCreate(
            ['code' => 'vi'],
            [
                'name' => 'Tiếng Việt',
                'flag' => '/uploads/flags/vi.png',
                'is_active' => true,
                'is_default' => true,
                'order' => 1
            ]
        );

        // 2. Khởi tạo ngôn ngữ Tiếng Anh
        Language::updateOrCreate(
            ['code' => 'en'],
            [
                'name' => 'English',
                'flag' => '/uploads/flags/en.png',
                'is_active' => true,
                'is_default' => false,
                'order' => 2
            ]
        );
    }
}
