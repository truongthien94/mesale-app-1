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
        Schema::table('menus', function (Blueprint $table) {
            // auth_rule: all (hiển thị tất cả), auth (chỉ thành viên đã đăng nhập), guest (chỉ khách chưa đăng nhập)
            $table->string('auth_rule')->default('all')->after('target');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('menus', function (Blueprint $table) {
            $table->dropColumn('auth_rule');
        });
    }
};
