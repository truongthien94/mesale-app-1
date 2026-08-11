<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * BLK-AUTH-002: thêm ràng buộc UNIQUE cho users.google_id.
 *
 * Trước migration này, `apple_id` đã có UNIQUE nhưng `google_id` chỉ có INDEX,
 * nên cùng một định danh Google có thể nằm trên nhiều tài khoản.
 *
 * Audit production ngày 2026-08-11 xác nhận không có định danh Google trùng lặp,
 * nên việc thêm ràng buộc là an toàn. Xem docs/release/OAUTH-IDENTITY-AUDIT.md.
 */
return new class extends Migration
{
    public function up(): void
    {
        // MySQL/MariaDB cho phép nhiều NULL trong cột UNIQUE, nhưng KHÔNG cho phép
        // nhiều chuỗi rỗng. Chuẩn hóa '' thành NULL trước để tránh migration thất bại,
        // đồng thời làm dữ liệu đúng nghĩa "chưa liên kết Google".
        DB::table('users')->where('google_id', '')->update(['google_id' => null]);

        Schema::table('users', function (Blueprint $table): void {
            // Giữ nguyên index cũ; UNIQUE tạo index riêng nên không cần drop index trước.
            $table->unique('google_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropUnique(['google_id']);
        });
    }
};
