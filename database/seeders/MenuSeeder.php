<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class MenuSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Menu Header
        $headerMenus = [
            [
                'title' => 'Trang chủ',
                'url' => '/',
                'icon' => 'home',
                'position' => 'header',
                'target' => '_self',
                'order' => 1,
                'status' => true,
                'auth_rule' => 'all'
            ],
            [
                'title' => 'Đơn hàng',
                'url' => '/dashboard/cashback',
                'icon' => 'shopping-bag',
                'position' => 'header',
                'target' => '_self',
                'order' => 2,
                'status' => true,
                'auth_rule' => 'auth'
            ],
            [
                'title' => 'Điểm danh',
                'url' => '/dashboard/checkin',
                'icon' => 'calendar-check',
                'position' => 'header',
                'target' => '_self',
                'order' => 3,
                'status' => true,
                'auth_rule' => 'auth'
            ],
            [
                'title' => 'Giới thiệu',
                'url' => '/dashboard/referrals',
                'icon' => 'users',
                'position' => 'header',
                'target' => '_self',
                'order' => 4,
                'status' => true,
                'auth_rule' => 'auth'
            ],
            [
                'title' => 'Rút tiền',
                'url' => '/dashboard/withdraw',
                'icon' => 'wallet',
                'position' => 'header',
                'target' => '_self',
                'order' => 5,
                'status' => true,
                'auth_rule' => 'auth'
            ],
            [
                'title' => 'Blog',
                'url' => '/blog',
                'icon' => 'book-open',
                'position' => 'header',
                'target' => '_self',
                'order' => 6,
                'status' => true,
                'auth_rule' => 'all'
            ]
        ];

        foreach ($headerMenus as $menu) {
            \App\Models\Menu::updateOrCreate(
                ['title' => $menu['title'], 'position' => $menu['position']],
                $menu
            );
        }

        // Menu Footer
        $footerMenus = [
            [
                'title' => 'Trang chủ',
                'url' => '/',
                'icon' => null,
                'position' => 'footer',
                'target' => '_self',
                'order' => 1,
                'status' => true,
                'auth_rule' => 'all'
            ],
            [
                'title' => 'Bảng xếp hạng',
                'url' => '/ranking',
                'icon' => null,
                'position' => 'footer',
                'target' => '_self',
                'order' => 2,
                'status' => true,
                'auth_rule' => 'all'
            ],
            [
                'title' => 'Điểm danh hàng ngày',
                'url' => '/dashboard/checkin',
                'icon' => null,
                'position' => 'footer',
                'target' => '_self',
                'order' => 3,
                'status' => true,
                'auth_rule' => 'auth'
            ],
            [
                'title' => 'Tiếp thị liên kết',
                'url' => '/dashboard/referrals',
                'icon' => null,
                'position' => 'footer',
                'target' => '_self',
                'order' => 4,
                'status' => true,
                'auth_rule' => 'auth'
            ],
            [
                'title' => 'Yêu cầu rút tiền',
                'url' => '/dashboard/withdraw',
                'icon' => null,
                'position' => 'footer',
                'target' => '_self',
                'order' => 5,
                'status' => true,
                'auth_rule' => 'auth'
            ]
        ];

        foreach ($footerMenus as $menu) {
            \App\Models\Menu::updateOrCreate(
                ['title' => $menu['title'], 'position' => $menu['position']],
                $menu
            );
        }

        // Menu User Dropdown (Menu cá nhân)
        $userDropdownMenus = [
            [
                'title' => 'Ví của tôi',
                'url' => '/dashboard',
                'icon' => 'layout-dashboard',
                'position' => 'user_dropdown',
                'target' => '_self',
                'order' => 2,
                'status' => true,
                'auth_rule' => 'auth'
            ],
            [
                'title' => 'Cấu hình hồ sơ',
                'url' => '/dashboard/profile',
                'icon' => 'user',
                'position' => 'user_dropdown',
                'target' => '_self',
                'order' => 3,
                'status' => true,
                'auth_rule' => 'auth'
            ],
            [
                'title' => 'Sản Phẩm Đã Lưu',
                'url' => '/dashboard/saved-products',
                'icon' => 'bookmark',
                'position' => 'user_dropdown',
                'target' => '_self',
                'order' => 4,
                'status' => true,
                'auth_rule' => 'auth'
            ],
            [
                'title' => 'Yêu cầu rút tiền',
                'url' => '/dashboard/withdraw',
                'icon' => 'banknote',
                'position' => 'user_dropdown',
                'target' => '_self',
                'order' => 5,
                'status' => true,
                'auth_rule' => 'auth'
            ],
            [
                'title' => 'Biến động số dư',
                'url' => '/dashboard/balance-logs',
                'icon' => 'arrow-left-right',
                'position' => 'user_dropdown',
                'target' => '_self',
                'order' => 6,
                'status' => true,
                'auth_rule' => 'auth'
            ],
            [
                'title' => 'Nhật ký hoạt động',
                'url' => '/dashboard/logs',
                'icon' => 'history',
                'position' => 'user_dropdown',
                'target' => '_self',
                'order' => 7,
                'status' => true,
                'auth_rule' => 'auth'
            ]
        ];

        // Xóa bản ghi Trang quản trị cũ ở vị trí user_dropdown để tránh trùng lặp hoặc hiển thị thừa
        \App\Models\Menu::where('url', '/admin')->where('position', 'user_dropdown')->delete();

        foreach ($userDropdownMenus as $menu) {
            \App\Models\Menu::updateOrCreate(
                ['title' => $menu['title'], 'position' => $menu['position']],
                $menu
            );
        }

        // Menu Bottom (Menu di động đáy)
        $bottomMenus = [
            [
                'title' => 'Trang chủ',
                'url' => '/',
                'icon' => 'home',
                'position' => 'bottom',
                'target' => '_self',
                'order' => 1,
                'status' => true,
                'auth_rule' => 'all'
            ],
            [
                'title' => 'Ví',
                'url' => '/dashboard',
                'icon' => 'wallet',
                'position' => 'bottom',
                'target' => '_self',
                'order' => 2,
                'status' => true,
                'auth_rule' => 'auth'
            ],
            [
                'title' => 'Đơn hàng',
                'url' => '/dashboard/cashback',
                'icon' => 'shopping-bag',
                'position' => 'bottom',
                'target' => '_self',
                'order' => 3,
                'status' => true,
                'auth_rule' => 'auth'
            ],
            [
                'title' => 'Rút tiền',
                'url' => '/dashboard/withdraw',
                'icon' => 'banknote',
                'position' => 'bottom',
                'target' => '_self',
                'order' => 4,
                'status' => true,
                'auth_rule' => 'auth'
            ],
            [
                'title' => 'Ví',
                'url' => '/dashboard',
                'icon' => 'wallet',
                'position' => 'bottom',
                'target' => '_self',
                'order' => 5,
                'status' => true,
                'auth_rule' => 'guest'
            ],
            [
                'title' => 'Đăng nhập',
                'url' => '/login',
                'icon' => 'log-in',
                'position' => 'bottom',
                'target' => '_self',
                'order' => 6,
                'status' => true,
                'auth_rule' => 'guest'
            ],
            [
                'title' => 'Đăng ký',
                'url' => '/register',
                'icon' => 'user-plus',
                'position' => 'bottom',
                'target' => '_self',
                'order' => 7,
                'status' => true,
                'auth_rule' => 'guest'
            ]
        ];

        foreach ($bottomMenus as $menu) {
            \App\Models\Menu::updateOrCreate(
                ['title' => $menu['title'], 'position' => $menu['position'], 'auth_rule' => $menu['auth_rule']],
                $menu
            );
        }
    }
}
