<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Bổ sung khả năng đăng ký tài khoản bằng Số điện thoại thay cho Email:
     *  - Cột email cho phép để trống (NULL) vì thành viên đăng ký bằng SĐT chưa có email.
     *  - Cột phone được đánh chỉ mục để tra cứu nhanh khi đăng nhập bằng số điện thoại,
     *    ưu tiên chỉ mục UNIQUE nếu dữ liệu hiện tại không có số điện thoại trùng lặp.
     *  - Thêm 2 cấu hình mới cho phép admin bật/tắt từng phương thức định danh khi đăng ký.
     */
    public function up(): void
    {
        // 1. Cho phép cột email để trống (thành viên đăng ký bằng số điện thoại)
        Schema::table('users', function (Blueprint $table) {
            $table->string('email')->nullable()->change();
        });

        // 2. Đánh chỉ mục cho cột phone để phục vụ đăng nhập bằng số điện thoại
        // Dùng Schema::getIndexes để tương thích với mọi hệ quản trị CSDL (MariaDB/MySQL/SQLite)
        $indexes = collect(Schema::getIndexes('users'))->pluck('name')->all();

        if (!in_array('users_phone_unique', $indexes, true) && !in_array('users_phone_index', $indexes, true)) {
            // Kiểm tra xem dữ liệu hiện tại có số điện thoại bị trùng lặp hay không
            $hasDuplicate = DB::table('users')
                ->select('phone')
                ->whereNotNull('phone')
                ->where('phone', '!=', '')
                ->groupBy('phone')
                ->havingRaw('COUNT(*) > 1')
                ->exists();

            Schema::table('users', function (Blueprint $table) use ($hasDuplicate) {
                if ($hasDuplicate) {
                    // Dữ liệu cũ đang có số điện thoại trùng nhau: chỉ tạo chỉ mục thường để không làm hỏng dữ liệu khách hàng.
                    // Tính duy nhất của số điện thoại vẫn được kiểm soát ở tầng ứng dụng khi đăng ký / cập nhật hồ sơ.
                    $table->index('phone');
                } else {
                    $table->unique('phone');
                }
            });

            if ($hasDuplicate) {
                \Log::warning('Migration đăng ký bằng SĐT: phát hiện số điện thoại trùng lặp trong bảng users nên chỉ tạo chỉ mục thường thay vì UNIQUE.');
            }
        }

        // 3. Thêm cấu hình phương thức định danh khi đăng ký tài khoản
        $newSettings = [
            [
                'key' => 'register_identifier_email',
                'value' => '1',
                'description' => 'Cho phép đăng ký tài khoản bằng địa chỉ Email (1: Bật, 0: Tắt)',
            ],
            [
                'key' => 'register_identifier_phone',
                'value' => '0',
                'description' => 'Cho phép đăng ký tài khoản bằng Số điện thoại (1: Bật, 0: Tắt)',
            ],
        ];

        foreach ($newSettings as $setting) {
            if (!DB::table('settings')->where('key', $setting['key'])->exists()) {
                DB::table('settings')->insert(array_merge($setting, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ]));
            }
        }
    }

    /**
     * Reverse the migrations.
     * Lưu ý: KHÔNG khôi phục ràng buộc NOT NULL của cột email để tránh làm hỏng
     * các tài khoản đã đăng ký bằng số điện thoại (email đang để trống).
     */
    public function down(): void
    {
        DB::table('settings')->whereIn('key', ['register_identifier_email', 'register_identifier_phone'])->delete();
    }
};
