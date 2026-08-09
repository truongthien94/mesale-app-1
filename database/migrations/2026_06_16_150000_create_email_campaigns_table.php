<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tạo bảng email_campaigns để lưu trữ các chiến dịch email marketing.
     * Mỗi campaign có thể nhắm đến toàn bộ hoặc một nhóm người dùng cụ thể.
     * Trạng thái campaign: draft → scheduled/sending → sent | cancelled
     */
    public function up(): void
    {
        Schema::create('email_campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('subject');
            $table->longText('body');
            $table->string('from_name')->nullable();
            $table->string('from_email')->nullable();
            // all, active, inactive, has_balance, no_orders, referrers, top_referrers
            $table->string('target_audience')->default('all');
            // JSON filter bổ sung, ví dụ: {"min_balance": 50000, "registered_after": "2024-01-01"}
            $table->json('target_filter')->nullable();
            // draft, scheduled, sending, sent, cancelled
            $table->string('status')->default('draft');
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->unsignedInteger('total_recipients')->default(0);
            $table->unsignedInteger('total_sent')->default(0);
            $table->unsignedInteger('total_failed')->default(0);
            // Admin tạo campaign
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // Thêm cột campaign_id vào email_queues để liên kết email với campaign
        Schema::table('email_queues', function (Blueprint $table) {
            $table->foreignId('campaign_id')->nullable()->after('id')->constrained('email_campaigns')->nullOnDelete();
        });
    }

    /**
     * Rollback migration.
     */
    public function down(): void
    {
        Schema::table('email_queues', function (Blueprint $table) {
            $table->dropForeign(['campaign_id']);
            $table->dropColumn('campaign_id');
        });
        Schema::dropIfExists('email_campaigns');
    }
};
