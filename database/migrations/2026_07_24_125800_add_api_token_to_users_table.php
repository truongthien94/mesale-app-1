<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration bổ sung cột api_token vào bảng users để quản lý API Key cá nhân xác thực an toàn cho từng người dùng.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('users', 'api_token')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('api_token', 64)->nullable()->unique()->after('referral_code')->comment('Mã API Token cá nhân xác thực ứng dụng/phím tắt');
            });
        }

        // Tự động sinh api_token cho tất cả thành viên hiện có trong CSDL nếu chưa có
        \App\Models\User::whereNull('api_token')->orWhere('api_token', '')->get()->each(function ($user) {
            if (empty($user->attributes['api_token'])) {
                $user->forceFill(['api_token' => 'sk_live_' . \Illuminate\Support\Str::random(32)])->save();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('api_token');
        });
    }
};
