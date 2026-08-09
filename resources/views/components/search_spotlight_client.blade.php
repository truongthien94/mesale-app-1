{{-- 
    Component: Spotlight Search Client (Tìm kiếm nhanh cho giao diện thành viên & trang khách)
    Vai trò: Cho phép người dùng tìm kiếm và truy cập nhanh các tính năng, ví tiền, điểm danh, rút tiền, hoặc tin tức từ trang chủ.
    Kiến trúc:
        - Giao diện được dựng bằng Tailwind CSS đồng bộ với thiết kế trang chủ, hỗ trợ Dark Mode.
        - Logic được quản lý qua AlpineJS hoàn toàn ở phía client đem lại phản hồi tức thì.
        - Dữ liệu các trang chức năng được xây dựng động bằng PHP dựa trên trạng thái đăng nhập và các menu động cấu hình trong DB để đảm bảo bảo mật và đồng bộ.
--}}
@php
    $clientItems = [];
    
    // 1. Thêm các trang cơ bản cố định
    $clientItems[] = [
        'name' => __('Trang chủ'),
        'category' => __('Trang chính'),
        'url' => route('home'),
        'icon' => 'home',
        'keywords' => 'trang chu homepage dan link shopee hoan tien cashback mua sam'
    ];
    
    if (isset($blogEnabled) && $blogEnabled == '1') {
        $clientItems[] = [
            'name' => __('Tin tức & Cẩm nang'),
            'category' => __('Trang chính'),
            'url' => route('blog.index'),
            'icon' => 'book-open',
            'keywords' => 'tin tuc blog cam nang huong dan tin moi chia se kinh nghiem'
        ];
    }
    
    if (auth()->guest()) {
        $clientItems[] = [
            'name' => __('Đăng nhập tài khoản'),
            'category' => __('Tài khoản'),
            'url' => route('login'),
            'icon' => 'log-in',
            'keywords' => 'dang nhap login sign in'
        ];
        if (\App\Models\Setting::getVal('registration_enabled', '1') === '1') {
            $clientItems[] = [
                'name' => __('Đăng ký tài khoản'),
                'category' => __('Tài khoản'),
                'url' => route('register'),
                'icon' => 'user-plus',
                'keywords' => 'dang ky register sign up tao tai khoan'
            ];
        }
        $clientItems[] = [
            'name' => __('Quên mật khẩu'),
            'category' => __('Tài khoản'),
            'url' => route('password.request'),
            'icon' => 'key',
            'keywords' => 'quen mat khau forgot password reset mat khau lay lai mat khau'
        ];
    } else {
        $clientItems[] = [
            'name' => __('Ví của tôi (Bảng điều khiển)'),
            'category' => __('Thành viên'),
            'url' => route('dashboard'),
            'icon' => 'layout-dashboard',
            'keywords' => 'vi cua toi bang dieu khien dashboard so du balance vi tien'
        ];
        $clientItems[] = [
            'name' => __('Lịch sử đơn hàng hoàn tiền'),
            'category' => __('Thành viên'),
            'url' => route('cashback.history'),
            'icon' => 'shopping-bag',
            'keywords' => 'don hang hoan tien lich su mua sam shopee cashback history'
        ];
        if (\App\Models\Setting::getVal('daily_checkin_enabled', '1') === '1') {
            $clientItems[] = [
                'name' => __('Điểm danh hàng ngày nhận thưởng'),
                'category' => __('Thành viên'),
                'url' => route('checkin'),
                'icon' => 'calendar',
                'keywords' => 'diem danh checkin nhan coin free thach thuc hang ngay streak chuoi'
            ];
        }
        if (\App\Models\Setting::getVal('referral_enabled', '1') === '1') {
            $clientItems[] = [
                'name' => __('Tiếp thị liên kết (Referral)'),
                'category' => __('Thành viên'),
                'url' => route('referrals'),
                'icon' => 'users',
                'keywords' => 'tiep thi lien ket referrals gioi thieu ban be f1 f2 hoa hong mlm ma gioi thieu'
            ];
        }
        if (\App\Models\Setting::getVal('withdrawal_enabled', '1') === '1') {
            $clientItems[] = [
                'name' => __('Yêu cầu rút tiền về ngân hàng'),
                'category' => __('Thành viên'),
                'url' => route('withdraw'),
                'icon' => 'wallet',
                'keywords' => 'rut tien withdraw rut so du ngan hang bank lien ket ngan hang'
            ];
        }
        if (\App\Models\Setting::getVal('gift_redemption_enabled', '0') === '1') {
            $clientItems[] = [
                'name' => __('Đổi quà tặng hấp dẫn'),
                'category' => __('Thành viên'),
                'url' => route('gifts.index'),
                'icon' => 'gift',
                'keywords' => 'doi qua tang doi the cao gifts exchange voucher code qua tang khuyen mai'
            ];
        }
        
        // Cho phép người dùng tìm nhanh trang nhập Giftcode nếu tính năng này được Admin kích hoạt trong hệ thống
        if (\App\Models\Setting::getVal('gift_code_enabled', '0') === '1') {
            $clientItems[] = [
                'name' => __('Nhập mã Giftcode'),
                'category' => __('Thành viên'),
                'url' => route('giftcode.index'),
                'icon' => 'ticket',
                'keywords' => 'giftcode nhap ma nhan thuong ma khuyen mai code coupon voucher free coin'
            ];
        }
        $clientItems[] = [
            'name' => __('Thông tin hồ sơ & Bảo mật'),
            'category' => __('Thành viên'),
            'url' => route('profile'),
            'icon' => 'user',
            'keywords' => 'thong tin tai khoan ho so profile doi mat khau 2fa bao mat otp email phone'
        ];
        $clientItems[] = [
            'name' => __('Sản phẩm đã lưu'),
            'category' => __('Thành viên'),
            'url' => route('saved-products'),
            'icon' => 'bookmark',
            'keywords' => 'san pham da luu mua sau bookmark saved products'
        ];
        if (\App\Models\Setting::getVal('ios_shortcut_status', '0') === '1') {
            $clientItems[] = [
                'name' => __('Phím tắt iPhone (iOS Shortcuts)'),
                'category' => __('Thành viên'),
                'url' => route('shortcuts'),
                'icon' => 'smartphone',
                'keywords' => 'phim tat iphone ios shortcuts tu dong hoa lay link shopee cashback'
            ];
        }
        
        if (auth()->user()->isAdmin()) {
            $clientItems[] = [
                'name' => __('Trang quản trị (Admin Panel)'),
                'category' => __('Quản trị'),
                'url' => route('admin.dashboard'),
                'icon' => 'shield-check',
                'keywords' => 'trang quan tri admin panel dashboard he thong quan ly'
            ];
        }
    }
    
    // 2. Thêm các link từ menu động cấu hình trong DB (Tránh trùng lặp URL)
    if (isset($groupedMenus)) {
        foreach (['header', 'footer', 'user_dropdown'] as $groupKey) {
            if ($groupedMenus->has($groupKey)) {
                foreach ($groupedMenus[$groupKey] as $menuItem) {
                    $showMenu = false;
                    if ($menuItem->auth_rule === 'all') {
                        $showMenu = true;
                    } elseif ($menuItem->auth_rule === 'auth' && auth()->check()) {
                        $showMenu = true;
                    } elseif ($menuItem->auth_rule === 'guest' && auth()->guest()) {
                        $showMenu = true;
                    }
                    
                    if ($showMenu) {
                        $url = str_starts_with($menuItem->url, 'http') ? $menuItem->url : url($menuItem->url);
                        // Tránh trùng URL với các chức năng cơ bản ở trên
                        $exists = false;
                        foreach ($clientItems as $item) {
                            if ($item['url'] === $url) {
                                $exists = true;
                                break;
                            }
                        }
                        if (!$exists) {
                            $clientItems[] = [
                                'name' => __($menuItem->title),
                                'category' => __('Menu liên kết'),
                                'url' => $url,
                                'icon' => $menuItem->icon ?: 'link',
                                'keywords' => strtolower($menuItem->title) . ' menu lien ket dieu huong'
                            ];
                        }
                    }
                }
            }
        }
    }
@endphp

<div x-data="searchSpotlightClient(@js($clientItems))"
     @open-search.window="openModal()"
     @keydown.escape.window="closeModal()"
     x-show="isOpen"
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     class="fixed inset-0 z-50 overflow-y-auto p-4 sm:p-6 md:p-20"
     role="dialog"
     aria-modal="true"
     x-cloak>
    
    <!-- Lớp phủ nền mờ phía sau (Backdrop Overlay) -->
    <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" @click="closeModal()"></div>

    <!-- Khung tìm kiếm chính (Modal Card) -->
    <div x-show="isOpen"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         class="mx-auto max-w-xl transform divide-y divide-gray-100 dark:divide-slate-800 overflow-hidden rounded-2xl bg-white dark:bg-slate-900 shadow-2xl ring-1 ring-black ring-opacity-5 transition-all">
        
        <!-- Hộp nhập từ khóa tìm kiếm -->
        <div class="relative flex items-center px-4 py-3.5">
            <i data-lucide="search" class="h-5 w-5 text-gray-400 dark:text-slate-500 shrink-0"></i>
            <input type="text"
                   x-model="searchQuery"
                   x-ref="searchInput"
                   @keydown.arrow-down.prevent="navigateDown()"
                   @keydown.arrow-up.prevent="navigateUp()"
                   @keydown.enter.prevent="selectCurrent()"
                   @keydown.escape.prevent="closeModal()"
                   class="ml-3 h-8 w-full border-0 bg-transparent text-sm text-gray-800 dark:text-slate-100 placeholder-gray-400 focus:ring-0 focus:outline-none dark:placeholder-slate-500"
                   placeholder="{{ __('Tìm nhanh chức năng (ví dụ: điểm danh, ví, rút tiền...)') }}"
                   role="combobox"
                   aria-expanded="false">
            <button @click="closeModal()" class="text-xs text-gray-400 hover:text-gray-600 dark:hover:text-slate-200 border border-gray-200 dark:border-slate-800 rounded px-1.5 py-0.5 bg-gray-50 dark:bg-slate-950/40">
                ESC
            </button>
        </div>

        <!-- Vùng hiển thị kết quả tìm kiếm -->
        <div class="max-h-96 overflow-y-auto py-2 scrollbar-none" x-ref="resultsContainer">
            <!-- Trạng thái chưa nhập từ khóa: Hiển thị các gợi ý truy cập nhanh -->
            <template x-if="searchQuery === ''">
                <div>
                    <div class="px-4 py-2 text-[10px] font-bold text-gray-400 dark:text-slate-500 uppercase tracking-widest">
                        {{ __('Gợi ý truy cập nhanh') }}
                    </div>
                    <ul class="text-sm text-gray-700 dark:text-slate-350">
                        <template x-for="(item, index) in popularItems" :key="'popular-' + index">
                            <li>
                                <a :href="item.url"
                                   @mouseenter="selectedIndex = index"
                                   @click="closeModal()"
                                   class="flex items-center gap-3 px-4 py-3 transition-colors text-xs font-semibold"
                                   :class="selectedIndex === index ? 'bg-shopee/5 text-shopee dark:bg-shopee/10' : 'hover:bg-gray-50 dark:hover:bg-slate-850/40'">
                                    <div class="flex items-center justify-center w-7 h-7 rounded-lg bg-gray-100 dark:bg-slate-800 text-gray-500 dark:text-slate-400 shrink-0"
                                         :class="selectedIndex === index ? 'text-shopee bg-shopee/10 dark:text-shopee dark:bg-shopee/20' : ''">
                                        <i :data-lucide="item.icon || 'link'" class="w-4 h-4"></i>
                                    </div>
                                    <div class="flex-grow min-w-0">
                                        <span x-text="item.name" class="block truncate"></span>
                                        <span x-text="item.category" class="block text-[10px] text-gray-400 dark:text-slate-500 font-medium"></span>
                                    </div>
                                    <i data-lucide="chevron-right" class="w-3.5 h-3.5 text-gray-300 dark:text-slate-700"></i>
                                </a>
                            </li>
                        </template>
                    </ul>
                </div>
            </template>

            <!-- Trạng thái có kết quả tìm kiếm -->
            <template x-if="searchQuery !== '' && filteredItems.length > 0">
                <ul class="text-sm text-gray-700 dark:text-slate-350">
                    <template x-for="(item, index) in filteredItems" :key="'result-' + index">
                        <li>
                            <a :href="item.url"
                               :id="'search-item-' + index"
                               @mouseenter="selectedIndex = index"
                               @click="closeModal()"
                               class="flex items-center gap-3 px-4 py-3 transition-colors text-xs font-semibold"
                               :class="selectedIndex === index ? 'bg-shopee/5 text-shopee dark:bg-shopee/10 border-l-4 border-shopee' : 'hover:bg-gray-50 dark:hover:bg-slate-850/40 pl-5'">
                                <div class="flex items-center justify-center w-7 h-7 rounded-lg bg-gray-100 dark:bg-slate-800 text-gray-500 dark:text-slate-400 shrink-0"
                                     :class="selectedIndex === index ? 'text-shopee bg-shopee/10 dark:text-shopee dark:bg-shopee/20' : ''">
                                    <i :data-lucide="item.icon || 'link'" class="w-4 h-4"></i>
                                </div>
                                <div class="flex-grow min-w-0">
                                    <span x-text="item.name" class="block truncate"></span>
                                    <span x-text="item.category" class="block text-[10px] text-gray-400 dark:text-slate-500 font-medium"></span>
                                </div>
                                <div class="flex items-center gap-1 shrink-0">
                                    <span class="text-[10px] text-gray-400 dark:text-slate-500 bg-gray-100 dark:bg-slate-800 px-1.5 py-0.5 rounded font-normal" x-text="item.category"></span>
                                    <i data-lucide="chevron-right" class="w-3.5 h-3.5 text-gray-300 dark:text-slate-700"></i>
                                </div>
                            </a>
                        </li>
                    </template>
                </ul>
            </template>

            <!-- Trạng thái không có kết quả tìm kiếm (Empty State) -->
            <template x-if="searchQuery !== '' && filteredItems.length === 0">
                <div class="px-6 py-14 text-center sm:px-14">
                    <i data-lucide="search-code" class="mx-auto h-8 w-8 text-gray-400 dark:text-slate-500"></i>
                    <p class="mt-4 text-xs font-bold text-gray-900 dark:text-slate-200">{{ __('Không tìm thấy kết quả nào') }}</p>
                    <p class="mt-2 text-xs text-gray-500">{{ __('Không tìm thấy chức năng hoặc liên kết nào khớp với từ khóa ":query"', ['query' => '']) }}<span class="font-semibold" x-text="searchQuery"></span>".</p>
                </div>
            </template>
        </div>

        <!-- Hướng dẫn sử dụng phím tắt dưới chân thanh tìm kiếm (Footer) -->
        <div class="flex items-center justify-between bg-gray-50 dark:bg-slate-950/40 px-4 py-2.5 text-[10px] text-gray-400 dark:text-slate-500 font-medium">
            <div class="flex items-center gap-3">
                <span class="flex items-center gap-1">
                    <kbd class="bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 px-1 rounded shadow-sm">↑↓</kbd> {{ __('Di chuyển') }}
                </span>
                <span class="flex items-center gap-1">
                    <kbd class="bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 px-1 rounded shadow-sm">Enter</kbd> {{ __('Chọn') }}
                </span>
                <span class="flex items-center gap-1">
                    <kbd class="bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 px-1 rounded shadow-sm">ESC</kbd> {{ __('Đóng') }}
                </span>
            </div>
            <div class="hidden sm:block">
                <span>{{ $siteName }}</span>
            </div>
        </div>
    </div>
</div>

<script src="{{ asset('js/search-spotlight-client.js') }}?v=1.0.1"></script>
