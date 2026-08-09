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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->string('avatar')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            
            // Thông tin tài chính và hoàn tiền
            $table->decimal('balance', 15, 2)->default(0.00)->comment('Số dư tài khoản khả dụng để rút');
            $table->decimal('total_cashback', 15, 2)->default(0.00)->comment('Tổng số tiền đã được hoàn');
            $table->decimal('total_withdrawn', 15, 2)->default(0.00)->comment('Tổng số tiền đã rút thành công');
            
            // Tiếp thị liên kết (Affiliate Referral)
            $table->string('referral_code')->nullable()->unique()->comment('Mã giới thiệu của người dùng');
            $table->unsignedBigInteger('referred_by')->nullable()->comment('ID của người giới thiệu');
            
            // Phân quyền và trạng thái
            $table->string('role')->default('user')->comment('Vai trò: user, admin');
            $table->string('status')->default('active')->comment('Trạng thái: active, suspended');
            
            $table->rememberToken();
            $table->timestamps();

            // Khai báo khoá ngoại cho người giới thiệu liên kết đến bảng users
            $table->foreign('referred_by')->references('id')->on('users')->onDelete('set null');
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
