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
        // Tạo bảng lưu vết click lấy link hoàn tiền của người dùng
        Schema::create('cashback_clicks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('trans_id')->unique();
            $table->string('product_name');
            $table->string('product_image')->nullable();
            $table->decimal('original_price', 15, 2);
            $table->decimal('cashback_amount', 15, 2);
            $table->decimal('cashback_rate', 5, 2);
            $table->decimal('commission_amount', 15, 2);
            $table->text('affiliate_url')->nullable();
            $table->timestamps();

            // Khai báo foreign key liên kết với bảng users
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index('trans_id');
        });

        // Bổ sung cột trans_id vào bảng cashback_histories để đối chiếu lượt click gốc khi đồng bộ đơn hàng
        Schema::table('cashback_histories', function (Blueprint $table) {
            $table->string('trans_id')->nullable()->after('order_id');
            $table->index('trans_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cashback_histories', function (Blueprint $table) {
            $table->dropIndex(['trans_id']);
            $table->dropColumn('trans_id');
        });

        Schema::dropIfExists('cashback_clicks');
    }
};
