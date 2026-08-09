<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Bổ sung trường is_noindex cho bảng pages
 * Dùng để hỗ trợ chặn công cụ tìm kiếm index và loại bỏ khỏi sitemap.xml.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            // is_noindex = 1: Không cho Google index trang này và không đưa vào sitemap.xml
            $table->boolean('is_noindex')->default(false)->after('meta_description');
        });
    }

    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->dropColumn('is_noindex');
        });
    }
};
