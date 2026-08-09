<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Currency;

class CurrencySeeder extends Seeder
{
    /**
     * Chạy dữ liệu hạt giống (Seeder) khởi tạo các tiền tệ mặc định trong hệ thống.
     */
    public function run(): void
    {
        // 1. Khởi tạo tiền tệ Việt Nam Đồng (VND) làm mặc định
        Currency::updateOrCreate(
            ['code' => 'VND'],
            [
                'name' => 'Việt Nam Đồng',
                'symbol' => '₫',
                'exchange_rate' => 1.0000,
                'symbol_position' => 'after',
                'is_active' => true,
                'is_default' => true
            ]
        );

        // 2. Khởi tạo tiền tệ Đô la Mỹ (USD)
        Currency::updateOrCreate(
            ['code' => 'USD'],
            [
                'name' => 'Đô la Mỹ',
                'symbol' => '$',
                'exchange_rate' => 25000.0000, // 1 USD = 25000 VND
                'symbol_position' => 'before',
                'is_active' => true,
                'is_default' => false
            ]
        );
    }
}
