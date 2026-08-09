<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Nâng kiểu cột `value` của bảng `settings` từ TEXT (tối đa 64KB) lên LONGTEXT
 * để chứa được nội dung lớn như nhiều Section HTML/TEXT tùy chỉnh của trang chủ.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('settings') && Schema::hasColumn('settings', 'value')) {
            Schema::table('settings', function (Blueprint $table) {
                $table->longText('value')->nullable()->comment('Giá trị cấu hình')->change();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('settings') && Schema::hasColumn('settings', 'value')) {
            Schema::table('settings', function (Blueprint $table) {
                $table->text('value')->nullable()->comment('Giá trị cấu hình')->change();
            });
        }
    }
};
