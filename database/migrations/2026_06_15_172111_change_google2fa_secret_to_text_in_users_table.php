<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Thay đổi kiểu dữ liệu cột google2fa_secret của bảng users sang kiểu text để đủ chỗ lưu trữ chuỗi mã hóa dài từ Crypt.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Thay đổi kiểu cột google2fa_secret từ string sang text để chứa được chuỗi mã hóa dài của Crypt::encryptString
            $table->text('google2fa_secret')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     * Hoàn tác thay đổi, đưa cột google2fa_secret về kiểu string (varchar 255) ban đầu.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Hoàn tác về kiểu string ban đầu
            $table->string('google2fa_secret')->nullable()->change();
        });
    }
};
