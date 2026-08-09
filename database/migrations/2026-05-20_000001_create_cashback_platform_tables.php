<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Chạy migration tạo các bảng nghiệp vụ cho Cashback Platform.
     */
    public function up(): void
    {
        // 1. Bảng lịch sử hoàn tiền (Cashback Histories)
        Schema::create('cashback_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->comment('Liên kết tới người mua hàng');
            $table->string('order_id')->nullable()->unique()->comment('Mã đơn hàng Shopee');
            $table->string('product_name')->comment('Tên sản phẩm Shopee');
            $table->string('product_image')->nullable()->comment('Ảnh sản phẩm');
            $table->decimal('original_price', 15, 2)->comment('Giá gốc của sản phẩm');
            $table->decimal('cashback_amount', 15, 2)->comment('Số tiền hoàn cho user');
            $table->decimal('cashback_rate', 5, 2)->comment('% hoàn tiền thực tế cho user');
            $table->decimal('commission_amount', 15, 2)->comment('Tổng hoa hồng nhận từ hệ thống Shopee');
            $table->text('affiliate_url')->nullable()->comment('Đường dẫn mua hàng affiliate');
            $table->string('status')->default('pending')->comment('Trạng thái: pending (chờ duyệt), approved (đã duyệt), rejected (từ chối)');
            $table->timestamp('approved_at')->nullable()->comment('Thời gian duyệt đơn hàng');
            $table->string('rejected_reason')->nullable()->comment('Lý do từ chối nếu có');
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index('status');
        });

        // 2. Bảng yêu cầu rút tiền (Withdrawals)
        Schema::create('withdrawals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->comment('Liên kết tới user yêu cầu rút');
            $table->decimal('amount', 15, 2)->comment('Số tiền yêu cầu rút');
            $table->string('payment_method')->comment('Hình thức rút: bank (ngân hàng), momo (ví momo)');
            $table->string('account_number')->comment('Số tài khoản nhận tiền');
            $table->string('account_name')->comment('Tên chủ tài khoản nhận tiền');
            $table->string('bank_name')->nullable()->comment('Tên ngân hàng (đối với bank)');
            $table->string('status')->default('pending')->comment('Trạng thái: pending, approved, rejected');
            $table->text('notes')->nullable()->comment('Phản hồi hoặc lý do từ admin');
            $table->timestamp('processed_at')->nullable()->comment('Thời gian xử lý');
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index('status');
        });

        // 3. Bảng quan hệ giới thiệu (Referrals)
        Schema::create('referrals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('referrer_id')->comment('Người giới thiệu (F0)');
            $table->unsignedBigInteger('referred_id')->unique()->comment('Người được giới thiệu (F1)');
            $table->timestamps();

            $table->foreign('referrer_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('referred_id')->references('id')->on('users')->onDelete('cascade');
        });

        // 4. Bảng hoa hồng giới thiệu (Referral Commissions)
        Schema::create('referral_commissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('referrer_id')->comment('Người được nhận hoa hồng F1/F2');
            $table->unsignedBigInteger('referred_id')->comment('Người phát sinh đơn hàng (F1/F2)');
            $table->unsignedBigInteger('cashback_history_id')->comment('Đơn hàng hoàn tiền gốc phát sinh hoa hồng');
            $table->decimal('amount', 15, 2)->comment('Số tiền hoa hồng nhận được');
            $table->integer('level')->comment('Cấp giới thiệu: 1 (F1), 2 (F2)');
            $table->string('status')->default('pending')->comment('Trạng thái: pending, approved, rejected');
            $table->timestamps();

            $table->foreign('referrer_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('referred_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('cashback_history_id')->references('id')->on('cashback_histories')->onDelete('cascade');
            $table->index('status');
        });

        // 5. Bảng điểm danh hằng ngày (Daily Check-ins)
        Schema::create('daily_checkins', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->comment('Người điểm danh');
            $table->decimal('coins_earned', 15, 2)->comment('Số xu/tiền thưởng nhận được');
            $table->integer('streak_days')->comment('Số ngày điểm danh liên tiếp');
            $table->date('checked_in_date')->comment('Ngày điểm danh');
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->unique(['user_id', 'checked_in_date']);
        });

        // 6. Bảng sản phẩm cache/tìm kiếm (Products)
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('shopee_id')->nullable()->unique()->comment('Mã sản phẩm Shopee');
            $table->string('name')->comment('Tên sản phẩm');
            $table->string('image')->nullable()->comment('Ảnh sản phẩm');
            $table->decimal('price', 15, 2)->comment('Giá bán hiện tại');
            $table->decimal('commission_amount', 15, 2)->comment('Hoa hồng gốc của hệ thống');
            $table->decimal('cashback_amount', 15, 2)->comment('Tiền hoàn dự kiến cho khách');
            $table->decimal('cashback_rate', 5, 2)->comment('% hoàn tiền dự kiến');
            $table->text('affiliate_url')->comment('Đường dẫn mua hàng Shopee Affiliate');
            $table->integer('search_count')->default(1)->comment('Số lần người dùng tra cứu sản phẩm này');
            $table->timestamps();
        });

        // 7. Bảng cấu hình hệ thống (System Settings)
        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique()->comment('Khoá cấu hình');
            $table->text('value')->nullable()->comment('Giá trị cấu hình');
            $table->string('description')->nullable()->comment('Mô tả cấu hình');
            $table->timestamps();
        });

        // 8. Bảng nhật ký hoạt động tài khoản (Activity Logs)
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->comment('Người thực hiện hành động');
            $table->string('activity')->comment('Hành động (ví dụ: login, check-in, withdraw_request...)');
            $table->string('ip_address', 45)->nullable()->comment('Địa chỉ IP khách');
            $table->text('user_agent')->nullable()->comment('Trình duyệt / OS của khách');
            $table->timestamp('created_at')->nullable();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });

        // 9. Bảng thông báo (Notifications)
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->comment('Người nhận thông báo');
            $table->string('title')->comment('Tiêu đề thông báo');
            $table->text('content')->comment('Nội dung chi tiết thông báo');
            $table->string('type')->default('personal')->comment('Phân loại: personal (cá nhân), general (chung)');
            $table->boolean('is_read')->default(false)->comment('Trạng thái đã đọc');
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });

        // 10. Bảng Banners quảng cáo
        Schema::create('banners', function (Blueprint $table) {
            $table->id();
            $table->string('image_url')->comment('Đường dẫn ảnh banner');
            $table->string('link')->nullable()->comment('Đường dẫn liên kết khi nhấn banner');
            $table->string('title')->nullable()->comment('Tiêu đề phụ');
            $table->integer('order')->default(0)->comment('Thứ tự hiển thị');
            $table->boolean('is_active')->default(true)->comment('Trạng thái hoạt động: true/false');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('banners');
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('activity_logs');
        Schema::dropIfExists('system_settings');
        Schema::dropIfExists('products');
        Schema::dropIfExists('daily_checkins');
        Schema::dropIfExists('referral_commissions');
        Schema::dropIfExists('referrals');
        Schema::dropIfExists('withdrawals');
        Schema::dropIfExists('cashback_histories');
    }
};
