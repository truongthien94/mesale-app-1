<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bảng lưu trữ Personal Access Token dùng cho Open API (App Mobile / Frontend).
 * Token gốc (plaintext) chỉ hiển thị một lần duy nhất cho client khi đăng nhập/đăng ký,
 * trong database chỉ lưu bản băm SHA-256 để đảm bảo an toàn khi cơ sở dữ liệu bị rò rỉ.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_tokens', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->comment('Thành viên sở hữu token');
            $table->string('name')->nullable()->comment('Tên thiết bị/ứng dụng đăng nhập');
            $table->string('token', 64)->unique()->comment('Bản băm SHA-256 của token gốc');
            $table->string('last_ip', 45)->nullable()->comment('Địa chỉ IP sử dụng gần nhất');
            $table->timestamp('last_used_at')->nullable()->comment('Thời điểm sử dụng token gần nhất');
            $table->timestamp('expires_at')->nullable()->comment('Thời điểm token hết hạn (null = vĩnh viễn)');
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_tokens');
    }
};
