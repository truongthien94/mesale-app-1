<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_tasks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('task_id');
            $table->unsignedInteger('progress')->default(0);
            $table->enum('status', ['in_progress', 'completed', 'claimed'])->default('in_progress');
            // Giới hạn độ dài period_key xuống 50 ký tự để tránh lỗi key quá dài trong Unique Index (unique_user_task_period) trên MySQL/MariaDB cũ
            $table->string('period_key', 50)->nullable(); // daily: '2026-06-19', weekly: '2026-W25', one_time: null
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('claimed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'task_id']);
            $table->index(['task_id', 'status']);
            $table->index('period_key');
            $table->unique(['user_id', 'task_id', 'period_key'], 'unique_user_task_period');

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('task_id')->references('id')->on('tasks')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_tasks');
    }
};
