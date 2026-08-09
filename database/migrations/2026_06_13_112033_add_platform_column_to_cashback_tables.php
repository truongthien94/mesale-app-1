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
        // Thêm cột platform vào bảng cashback_clicks
        Schema::table('cashback_clicks', function (Blueprint $table) {
            $table->string('platform')->default('shopee')->after('user_id')->comment('Nền tảng thương mại điện tử: shopee, tiktok, lazada...');
            $table->index('platform');
        });

        // Thêm cột platform vào bảng cashback_histories
        Schema::table('cashback_histories', function (Blueprint $table) {
            $table->string('platform')->default('shopee')->after('user_id')->comment('Nền tảng thương mại điện tử: shopee, tiktok, lazada...');
            $table->index('platform');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cashback_clicks', function (Blueprint $table) {
            $table->dropIndex(['platform']);
            $table->dropColumn('platform');
        });

        Schema::table('cashback_histories', function (Blueprint $table) {
            $table->dropIndex(['platform']);
            $table->dropColumn('platform');
        });
    }
};
