<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Thực thi thêm cột last_seen_at để theo dõi thời gian truy cập (online) gần nhất của người dùng.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Thêm cột last_seen_at kiểu timestamp để lưu vết thời gian hoạt động cuối cùng của tài khoản
            $table->timestamp('last_seen_at')->nullable()->after('country')->comment('Thời gian hoạt động (online) gần nhất');
        });
    }

    /**
     * Reverse the migrations.
     * Hoàn tác cột last_seen_at khi thực hiện rollback.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Xóa cột last_seen_at khi hoàn tác migration
            $table->dropColumn('last_seen_at');
        });
    }
};
