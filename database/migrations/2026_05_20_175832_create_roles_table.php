<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Tạo bảng roles lưu trữ vai trò và thêm cột liên kết vào bảng users.
     */
    public function up(): void
    {
        // 1. Tạo bảng roles chứa thông tin vai trò và danh sách các quyền dạng JSON
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->json('permissions')->nullable()->comment('Danh sách các quyền của vai trò này (dạng mảng JSON)');
            $table->timestamps();
        });

        // 2. Thêm cột khóa ngoại role_id vào bảng users để gán vai trò cụ thể
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('role_id')->nullable()->after('role')->comment('Liên kết với bảng roles nếu là admin được phân quyền');
            $table->foreign('role_id')->references('id')->on('roles')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Gỡ bỏ khóa ngoại và cột liên kết ở bảng users trước khi xóa bảng roles
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['role_id']);
            $table->dropColumn('role_id');
        });
        
        Schema::dropIfExists('roles');
    }
};
