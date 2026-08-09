<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Bổ sung luồng "Thành viên tự xác nhận đã hoàn thành" cho nhiệm vụ loại Tùy chỉnh (thủ công).
 *
 * Trước đây nhiệm vụ thủ công không có cách nào để thành viên báo đã làm xong,
 * Admin phải tự dò trong danh sách nên không biết ai thực sự cần duyệt.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Bổ sung trạng thái 'pending' (Chờ Admin duyệt) vào cột status của bảng user_tasks
        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'])) {
            DB::statement("ALTER TABLE `user_tasks` MODIFY COLUMN `status` ENUM('in_progress','pending','completed','claimed') NOT NULL DEFAULT 'in_progress'");
        }

        Schema::table('user_tasks', function (Blueprint $table) {
            // Thời điểm thành viên bấm nút xác nhận đã hoàn thành nhiệm vụ
            $table->timestamp('submitted_at')->nullable()->after('claimed_at');
            // Ghi chú/bằng chứng thành viên gửi kèm khi xác nhận (link bài đăng, mã đơn hàng...)
            $table->string('submit_note', 500)->nullable()->after('submitted_at');
            // Lý do Admin từ chối, hiển thị lại cho thành viên để sửa và gửi lại
            $table->string('reject_reason', 500)->nullable()->after('submit_note');
            // Thời điểm và tài khoản Admin đã kiểm duyệt yêu cầu
            $table->timestamp('reviewed_at')->nullable()->after('reject_reason');
            $table->unsignedBigInteger('reviewed_by')->nullable()->after('reviewed_at');

            // Đánh chỉ mục để lọc nhanh các yêu cầu đang chờ duyệt trong trang quản trị
            $table->index(['status', 'submitted_at'], 'user_tasks_status_submitted_index');
        });
    }

    public function down(): void
    {
        Schema::table('user_tasks', function (Blueprint $table) {
            $table->dropIndex('user_tasks_status_submitted_index');
            $table->dropColumn(['submitted_at', 'submit_note', 'reject_reason', 'reviewed_at', 'reviewed_by']);
        });

        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'])) {
            // Đưa các bản ghi đang chờ duyệt về lại trạng thái đang thực hiện trước khi thu gọn danh sách trạng thái
            DB::table('user_tasks')->where('status', 'pending')->update(['status' => 'in_progress']);
            DB::statement("ALTER TABLE `user_tasks` MODIFY COLUMN `status` ENUM('in_progress','completed','claimed') NOT NULL DEFAULT 'in_progress'");
        }
    }
};
