<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Tạo cấu trúc bảng cho hệ thống Giftcode (mã quà tặng nhập tay nhận thưởng vào ví).
     */
    public function up(): void
    {
        // Bảng quản lý mã Giftcode cùng các điều kiện sử dụng linh hoạt cho từng mã
        Schema::create('gift_codes', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();                 // Mã code người dùng nhập (luôn lưu dạng IN HOA)
            $table->string('title')->nullable();              // Tên gợi nhớ chiến dịch (hiển thị nội bộ + cho user)
            $table->text('description')->nullable();           // Mô tả/điều kiện hiển thị cho người dùng

            // Cấu hình phần thưởng: cố định hoặc ngẫu nhiên trong khoảng
            $table->string('reward_type')->default('fixed');   // fixed | random
            $table->decimal('reward_amount', 15, 2)->default(0); // Số tiền cố định (reward_type = fixed)
            $table->decimal('reward_min', 15, 2)->default(0);    // Cận dưới khi random
            $table->decimal('reward_max', 15, 2)->default(0);    // Cận trên khi random

            // Giới hạn lượt sử dụng
            $table->unsignedInteger('max_uses')->nullable();   // Tổng lượt tối đa toàn hệ thống (null = không giới hạn)
            $table->unsignedInteger('used_count')->default(0); // Số lượt đã sử dụng (đếm tăng khi đổi thành công)
            $table->unsignedInteger('per_user_limit')->default(1); // Số lượt tối đa mỗi tài khoản

            // Khung thời gian hiệu lực
            $table->timestamp('starts_at')->nullable();        // Bắt đầu hiệu lực (null = hiệu lực ngay)
            $table->timestamp('expires_at')->nullable();       // Hết hạn (null = không hết hạn)

            // Điều kiện đủ tư cách của tài khoản (eligibility)
            $table->boolean('require_verified_email')->default(false); // Bắt buộc đã xác minh email
            $table->decimal('min_total_cashback', 15, 2)->default(0);  // Yêu cầu tổng hoàn tiền tích lũy tối thiểu
            $table->unsignedInteger('min_account_age_days')->default(0); // Tài khoản phải đăng ký tối thiểu X ngày
            $table->unsignedInteger('new_user_within_days')->nullable(); // Chỉ tài khoản đăng ký trong vòng X ngày (mã chào mừng)

            $table->boolean('status')->default(true);          // Bật/Tắt mã
            $table->timestamps();

            // Index hỗ trợ lọc nhanh ở admin và khi đổi mã
            $table->index('status');
            $table->index('expires_at');
        });

        // Bảng nhật ký các lượt đổi mã thành công (phục vụ giới hạn theo user và đối soát)
        Schema::create('gift_code_redemptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gift_code_id')->constrained('gift_codes')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('code');                            // Ảnh chụp mã tại thời điểm đổi (lưu vết)
            $table->decimal('amount', 15, 2);                  // Số tiền thực tế đã cộng vào ví
            $table->string('ip_address', 64)->nullable();      // IP tại thời điểm đổi để chống gian lận
            $table->timestamps();

            $table->index(['gift_code_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gift_code_redemptions');
        Schema::dropIfExists('gift_codes');
    }
};
