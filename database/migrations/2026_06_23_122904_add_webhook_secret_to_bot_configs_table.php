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
        Schema::table('bot_configs', function (Blueprint $table) {
            $table->string('webhook_secret', 64)->nullable()->after('bot_token');
        });
    }

    public function down(): void
    {
        Schema::table('bot_configs', function (Blueprint $table) {
            $table->dropColumn('webhook_secret');
        });
    }
};
