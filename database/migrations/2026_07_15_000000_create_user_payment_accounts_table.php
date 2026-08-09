<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tạo bảng lưu sổ tài khoản nhận tiền của thành viên.
     * Cho phép người dùng lưu sẵn nhiều tài khoản ngân hàng / ví điện tử để chọn nhanh khi rút tiền,
     * thay vì phải nhập lại toàn bộ thông tin từ đầu mỗi lần tạo lệnh rút.
     */
    public function up(): void
    {
        Schema::create('user_payment_accounts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            // Hình thức nhận tiền: bank (ngân hàng) hoặc wallet (ví điện tử)
            $table->string('payment_method', 20)->default('bank');
            // Tên ngân hàng hoặc tên ví điện tử (trùng quy ước cột bank_name của bảng withdrawals)
            $table->string('bank_name', 100);
            $table->string('account_number', 50);
            $table->string('account_name', 100);
            // Đánh dấu tài khoản mặc định sẽ tự điền sẵn khi mở trang rút tiền
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->index('user_id');
            $table->index(['user_id', 'is_default']);

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_payment_accounts');
    }
};
