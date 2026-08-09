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
        Schema::table('users', function (Blueprint $table) {
            $table->string('bot_zalo_chat_id', 64)->nullable()->after('last_seen_at');
            $table->string('bot_telegram_chat_id', 64)->nullable()->after('bot_zalo_chat_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['bot_zalo_chat_id', 'bot_telegram_chat_id']);
        });
    }
};
