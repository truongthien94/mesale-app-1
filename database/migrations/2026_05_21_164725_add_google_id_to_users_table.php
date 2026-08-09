<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Thực hiện thay đổi cấu trúc bảng: thêm cột google_id.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Thêm cột google_id để liên kết với tài khoản Google, cho phép null và đánh index để tìm kiếm nhanh
            $table->string('google_id')->nullable()->after('password')->index();
        });
    }

    /**
     * Reverse the migrations.
     * Hoàn tác thay đổi cấu trúc bảng: xóa cột google_id.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Xóa cột google_id khỏi bảng users
            $table->dropColumn('google_id');
        });
    }
};
