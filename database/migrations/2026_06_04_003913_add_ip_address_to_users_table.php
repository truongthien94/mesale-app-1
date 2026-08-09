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
            // Thêm cột ip_address để lưu IP lúc đăng ký tài khoản (cho phép null và đặt sau cột utm_source)
            $table->string('ip_address', 45)->nullable()->after('utm_source')->comment('Địa chỉ IP khi đăng ký tài khoản');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Xoá cột ip_address khi rollback migration
            $table->dropColumn('ip_address');
        });
    }
};
