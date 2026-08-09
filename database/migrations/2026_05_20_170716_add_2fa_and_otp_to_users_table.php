<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Chạy migration để bổ sung các trường phục vụ xác thực bảo mật 2 lớp (2FA và Email OTP).
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // google2fa_secret lưu mã khoá bảo mật của Google Authenticator dạng string
            $table->string('google2fa_secret')->nullable()->after('status');
            // google2fa_enabled xác định trạng thái đã kích hoạt 2FA Google Authenticator hay chưa
            $table->boolean('google2fa_enabled')->default(false)->after('google2fa_secret');
            // email_otp_enabled xác định trạng thái đã kích hoạt bảo mật OTP qua email hay chưa
            $table->boolean('email_otp_enabled')->default(false)->after('google2fa_enabled');
            // otp_code lưu mã OTP 6 số ngẫu nhiên được gửi về email của người dùng
            $table->string('otp_code')->nullable()->after('email_otp_enabled');
            // otp_expires_at lưu thời hạn của mã OTP email (thông thường hết hạn sau 5-10 phút)
            $table->timestamp('otp_expires_at')->nullable()->after('otp_code');
        });
    }

    /**
     * Reverse the migrations.
     * Hoàn tác migration, xoá bỏ các trường bảo mật đã thêm vào bảng users.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'google2fa_secret',
                'google2fa_enabled',
                'email_otp_enabled',
                'otp_code',
                'otp_expires_at'
            ]);
        });
    }
};
