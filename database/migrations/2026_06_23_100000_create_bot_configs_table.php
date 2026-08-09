<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bot_configs', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['zalo', 'telegram'])->unique();
            $table->boolean('is_enabled')->default(false);
            $table->string('bot_token', 500)->nullable();
            $table->string('bot_username', 100)->nullable();
            $table->text('welcome_message')->nullable();
            $table->text('cashback_template')->nullable();
            $table->text('not_found_message')->nullable();
            $table->text('login_required_message')->nullable();
            $table->json('extra')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bot_configs');
    }
};
