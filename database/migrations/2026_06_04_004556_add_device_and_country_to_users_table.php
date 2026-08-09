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
            // Thêm các cột lưu thông tin thiết bị (user_agent) và quốc gia (country) sau cột ip_address
            $table->text('user_agent')->nullable()->after('ip_address')->comment('Thông tin thiết bị và trình duyệt khi đăng ký');
            $table->string('country', 100)->nullable()->after('user_agent')->comment('Quốc gia của người dùng khi đăng ký');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Xoá các cột khi rollback migration
            $table->dropColumn(['user_agent', 'country']);
        });
    }
};
