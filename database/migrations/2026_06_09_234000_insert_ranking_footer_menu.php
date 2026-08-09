<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Chèn menu Bảng xếp hạng vào Footer Menu nếu chưa có và cập nhật order.
     */
    public function up(): void
    {
        $exists = DB::table('menus')
            ->where('url', '/ranking')
            ->where('position', 'footer')
            ->exists();

        if (!$exists) {
            $now = now();
            DB::table('menus')->insert([
                'title' => 'Bảng xếp hạng',
                'url' => '/ranking',
                'icon' => null,
                'position' => 'footer',
                'auth_rule' => 'all',
                'target' => '_self',
                'order' => 2,
                'status' => true,
                'parent_id' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            // Cập nhật tăng order của các menu footer cũ xếp sau nó
            DB::table('menus')
                ->where('position', 'footer')
                ->where('url', '!=', '/ranking')
                ->where('order', '>=', 2)
                ->increment('order');
        }
    }

    /**
     * Reverse the migrations.
     * Xóa menu Bảng xếp hạng của Footer Menu đã thêm.
     */
    public function down(): void
    {
        DB::table('menus')
            ->where('url', '/ranking')
            ->where('position', 'footer')
            ->delete();
    }
};
