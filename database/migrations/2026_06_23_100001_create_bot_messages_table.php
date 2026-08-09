<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bot_messages', function (Blueprint $table) {
            $table->id();
            $table->enum('bot_type', ['zalo', 'telegram']);
            $table->bigInteger('user_id')->unsigned()->nullable();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            $table->string('chat_id', 100);
            $table->string('user_name', 200)->nullable();
            $table->enum('direction', ['inbound', 'outbound']);
            $table->text('content');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['bot_type', 'chat_id']);
            $table->index(['bot_type', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bot_messages');
    }
};
