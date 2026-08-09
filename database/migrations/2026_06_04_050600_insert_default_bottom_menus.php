<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Chèn danh sách menu bottom mặc định vào database nếu chưa có.
     */
    public function up(): void
    {
        // Kiểm tra xem đã có menu nào ở vị trí bottom hay chưa để tránh chèn trùng lặp dữ liệu
        $exists = DB::table('menus')->where('position', 'bottom')->exists();
        
        if (!$exists) {
            $now = now();
            DB::table('menus')->insert([
                // 1. Trang chủ (Hiển thị cho tất cả)
                [
                    'title' => 'Trang chủ',
                    'url' => '/',
                    'icon' => 'home',
                    'position' => 'bottom',
                    'auth_rule' => 'all',
                    'target' => '_self',
                    'order' => 1,
                    'status' => true,
                    'parent_id' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                // 2. Ví (Chỉ hiển thị cho thành viên đã đăng nhập)
                [
                    'title' => 'Ví',
                    'url' => '/dashboard',
                    'icon' => 'wallet',
                    'position' => 'bottom',
                    'auth_rule' => 'auth',
                    'target' => '_self',
                    'order' => 2,
                    'status' => true,
                    'parent_id' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                // 3. Đơn hàng (Chỉ hiển thị cho thành viên đã đăng nhập)
                [
                    'title' => 'Đơn hàng',
                    'url' => '/dashboard/cashback',
                    'icon' => 'shopping-bag',
                    'position' => 'bottom',
                    'auth_rule' => 'auth',
                    'target' => '_self',
                    'order' => 3,
                    'status' => true,
                    'parent_id' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                // 4. Rút tiền (Chỉ hiển thị cho thành viên đã đăng nhập)
                [
                    'title' => 'Rút tiền',
                    'url' => '/dashboard/withdraw',
                    'icon' => 'banknote',
                    'position' => 'bottom',
                    'auth_rule' => 'auth',
                    'target' => '_self',
                    'order' => 4,
                    'status' => true,
                    'parent_id' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                // 5. Ví (Chỉ hiển thị cho khách chưa đăng nhập)
                [
                    'title' => 'Ví',
                    'url' => '/dashboard',
                    'icon' => 'wallet',
                    'position' => 'bottom',
                    'auth_rule' => 'guest',
                    'target' => '_self',
                    'order' => 5,
                    'status' => true,
                    'parent_id' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                // 6. Đăng nhập (Chỉ hiển thị cho khách chưa đăng nhập)
                [
                    'title' => 'Đăng nhập',
                    'url' => '/login',
                    'icon' => 'log-in',
                    'position' => 'bottom',
                    'auth_rule' => 'guest',
                    'target' => '_self',
                    'order' => 6,
                    'status' => true,
                    'parent_id' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                // 7. Đăng ký (Chỉ hiển thị cho khách chưa đăng nhập)
                [
                    'title' => 'Đăng ký',
                    'url' => '/register',
                    'icon' => 'user-plus',
                    'position' => 'bottom',
                    'auth_rule' => 'guest',
                    'target' => '_self',
                    'order' => 7,
                    'status' => true,
                    'parent_id' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            ]);
        }
    }

    /**
     * Reverse the migrations.
     * Xóa các menu bottom mặc định đã chèn.
     */
    public function down(): void
    {
        DB::table('menus')->where('position', 'bottom')->delete();
    }
};
