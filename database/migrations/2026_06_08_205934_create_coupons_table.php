<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Tạo bảng coupons hỗ trợ đa sàn TMĐT và cấu hình settings tương ứng.
     */
    public function up(): void
    {
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            // Giới hạn độ dài platform (50) và code (100) để Composite Unique Index (platform, code) không bị quá giới hạn bytes trên MySQL/MariaDB cũ
            $table->string('platform', 50)->default('shopee'); // Tên sàn: shopee, lazada, tiktok...
            $table->string('code', 100); // Mã giảm giá
            $table->string('title'); // Tiêu đề voucher
            $table->text('description')->nullable(); // Mô tả điều kiện voucher
            $table->string('category')->nullable(); // Danh mục áp dụng
            $table->integer('min_spend')->default(0); // Đơn tối thiểu
            $table->integer('discount_amount')->default(0); // Số tiền giảm giá
            $table->integer('discount_percentage')->default(0); // % giảm giá
            $table->integer('clicks')->default(0); // Lượt click
            $table->dateTime('expired_at')->nullable(); // Hạn sử dụng
            $table->text('redirect_link')->nullable(); // Đường dẫn chuyển hướng affiliate
            $table->string('source')->nullable(); // Nguồn voucher (ví dụ: KOL, Shop, Sàn)
            $table->timestamps();

            // Đảm bảo không trùng lặp mã giảm giá trên cùng một sàn
            $table->unique(['platform', 'code']);
            $table->index('platform');
        });

        // Thiết lập cấu hình mặc định cho chức năng mã giảm giá
        $settings = [
            [
                'key' => 'coupon_status',
                'value' => '1',
                'description' => 'Trạng thái hoạt động của chức năng mã giảm giá (1: Bật, 0: Tắt)',
            ],
            [
                'key' => 'coupon_api_url',
                'value' => 'https://apishopee.cmsnt.co/api/v1/shopee/vouchers',
                'description' => 'Đường dẫn API đồng bộ mã giảm giá',
            ],
            [
                'key' => 'coupon_api_key',
                'value' => '',
                'description' => 'X-API-KEY kết nối API mã giảm giá',
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
        Schema::dropIfExists('coupons');
        DB::table('settings')->whereIn('key', ['coupon_status', 'coupon_api_url', 'coupon_api_key'])->delete();
    }
};
