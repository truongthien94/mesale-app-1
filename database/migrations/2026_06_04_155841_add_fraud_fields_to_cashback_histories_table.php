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
            $table->string('shop_name')->nullable()->after('product_name');
            $table->string('fraud_reason')->nullable()->after('rejected_reason');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cashback_histories', function (Blueprint $table) {
            $table->dropColumn(['shop_name', 'fraud_reason']);
        });
    }
};
