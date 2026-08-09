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
        Schema::table('cashback_histories', function (Blueprint $table) {
            $table->text('click_metadata')->nullable()->comment('Thông tin click: ip, thiết bị, vị trí, user agent, thời gian')->after('affiliate_url');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cashback_histories', function (Blueprint $table) {
            $table->dropColumn('click_metadata');
        });
    }
};
