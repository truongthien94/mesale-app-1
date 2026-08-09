<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->text('guide')->nullable(); // Hướng dẫn chi tiết cách thực hiện
            $table->enum('type', ['daily', 'weekly', 'one_time'])->default('one_time');
            $table->enum('action', ['profile', 'referral', 'cashback', 'checkin', 'withdraw', 'save_product', 'custom'])->default('custom');
            $table->unsignedInteger('target_count')->default(1); // Số lần cần hoàn thành
            $table->decimal('reward_amount', 15, 2)->default(0);
            $table->enum('reward_type', ['balance'])->default('balance');
            $table->boolean('is_active')->default(true);
            $table->timestamp('start_at')->nullable();
            $table->timestamp('end_at')->nullable();
            $table->string('icon')->nullable()->default('star'); // Lucide icon name
            $table->string('badge_color')->nullable()->default('orange'); // orange, blue, green, purple, red, yellow
            $table->unsignedInteger('sort_order')->default(0);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index('type');
            $table->index('action');
            $table->index('is_active');
            $table->index('sort_order');
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
