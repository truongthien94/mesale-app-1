<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Thực hiện thêm cột utm_source vào bảng users.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Thêm cột utm_source, cho phép rỗng và đặt sau cột otp_expires_at
            $table->string('utm_source')->nullable()->after('otp_expires_at');
        });
    }

    /**
     * Reverse the migrations.
     * Hoàn tác việc thêm cột utm_source khỏi bảng users.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Xóa cột utm_source khi rollback migration
            $table->dropColumn('utm_source');
        });
    }
};
