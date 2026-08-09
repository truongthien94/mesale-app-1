<!DOCTYPE html>
<html lang="vi" class="h-full bg-gray-100">

<head>
    <script>
        // Thiết lập giao diện tối ngay lập tức để tránh hiện tượng nhấp nháy màn hình (flash)
        // Áp dụng cấu hình giao diện mặc định được thiết lập từ trang quản trị (Default Theme)
        const defaultTheme = '{{ \App\Models\Setting::getVal('default_theme', 'light') }}';
        const savedTheme = localStorage.getItem('theme');
        if (savedTheme === 'dark' || 
            (savedTheme === null && defaultTheme === 'dark') || 
            (savedTheme === null && defaultTheme === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    </script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script>
        window.adminPrefix = "{{ \App\Models\Setting::getVal('admin_prefix', 'admin') }}";
        window.adminUrl = "{{ url(\App\Models\Setting::getVal('admin_prefix', 'admin')) }}";
    </script>

    <title>@yield('title', 'Quản Trị Hệ Thống - ' . $siteName)</title>

    <!-- Favicon -->
    @if(!empty($siteFavicon))
    <link rel="icon" type="image/x-icon" href="{{ $siteFavicon }}">
    <link rel="shortcut icon" type="image/x-icon" href="{{ $siteFavicon }}">
    <link rel="apple-touch-icon" href="{{ $siteFavicon }}">
    @endif

    <!-- Google Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Tailwind CSS (Local Asset) -->
    <script src="{{ asset('vendor/tailwindcss/tailwind.min.js') }}"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    },
                    colors: {
                        shopee: {
                            DEFAULT: '{{ $themeColor }}',
                            light: '{{ $themeColorLight }}',
                            dark: '{{ $themeColorDark }}',
                            bg: '{{ $themeColorBg }}'
                        }
                    },
                    // Giảm độ bo góc của các card/block trên toàn hệ thống theo yêu cầu của user
                    borderRadius: {
                        'lg': '0.375rem', // Giảm từ 8px xuống 6px
                        'xl': '0.5rem', // Giảm từ 12px xuống 8px
                        '2xl': '0.75rem', // Giảm từ 16px xuống 12px
                        '3xl': '1rem', // Giảm từ 24px xuống 16px
                    }
                }
            }
        }
    </script>

    <!-- AlpineJS Collapse Plugin (Local Asset) -->
    <script defer src="{{ asset('vendor/alpinejs/collapse.min.js') }}"></script>

    <!-- AlpineJS (Local Asset) -->
    <script defer src="{{ asset('vendor/alpinejs/alpine.min.js') }}"></script>

    <!-- Chart.js (Local Asset) -->
    <script src="{{ asset('vendor/chartjs/chart.umd.js') }}"></script>

    <!-- Lucide Icons (Local Asset) -->
    <script src="{{ asset('vendor/lucide/lucide.min.js') }}"></script>

    <!-- NProgress Loading Bar (Local Asset) -->
    <link rel="stylesheet" href="{{ asset('vendor/nprogress/nprogress.min.css') }}">
    <script src="{{ asset('vendor/nprogress/nprogress.min.js') }}"></script>

    <!-- Axios HTTP Client (Local Asset) -->
    <script src="{{ asset('vendor/axios/axios.min.js') }}"></script>

    <!-- SweetAlert2 (Local Asset) -->
    <script src="{{ asset('vendor/sweetalert2/sweetalert2.all.min.js') }}"></script>

    <!-- Thiết lập CSS custom property cho màu chủ đạo (dùng biến CSS thay vì Blade curly braces trong style để IDE formatter không phá) -->
    <style>
        :root { --shopee-color: {{ $themeColor }}; }

        [x-cloak] {
            display: none !important;
        }

        i[data-lucide] {
            opacity: 0;
            display: inline-block;
            vertical-align: middle;
        }

        i[data-lucide],
        i[data-lucide] *,
        svg.lucide,
        svg.lucide *,
        .lucide,
        .lucide * {
            pointer-events: none !important;
        }

        /* Chuẩn hóa ô nhập ngày trên iOS Safari: mặc định control native cao hơn chiều cao CSS (h-9),
           khiến ô lọc ngày to hơn hẳn các input khác. Tắt appearance native để tôn trọng height và căn trái giá trị. */
        input[type="date"] {
            -webkit-appearance: none;
            appearance: none;
        }

        input[type="date"]::-webkit-date-and-time-value {
            text-align: left;
            margin: 0;
        }

        input[type="date"]::-webkit-datetime-edit {
            padding: 0;
            line-height: 1;
        }

        #nprogress .bar {
            background: var(--shopee-color) !important;
            height: 3px !important;
            z-index: 99999 !important;
        }

        #nprogress .peg {
            box-shadow: 0 0 10px var(--shopee-color), 0 0 5px var(--shopee-color) !important;
        }

        #nprogress .spinner {
            display: none !important;
        }

        body {
            transition: opacity 0.15s ease-in-out;
        }

        body.page-leaving {
            opacity: 0.7;
            pointer-events: none;
        }

        /* Hiệu ứng co giãn mượt mà cho sidebar khi chuyển giữa chế độ đầy đủ và thu gọn (chỉ hiển thị icon) */
        #admin-sidebar {
            transition: transform .3s ease-in-out, width .3s ease-in-out;
        }

        /*
         * MOBILE (<768px): Sidebar đóng vai trò lớp phủ.
         * Khi thu gọn (đóng) thì trượt hẳn ra khỏi màn hình. Class sidebar-collapsed được
         * thêm sớm bằng inline script ở đầu <body> để tránh nháy giao diện (layout shift)
         * trước khi Alpine.js khởi tạo.
         */
        @media (max-width: 767px) {
            body.sidebar-collapsed #admin-sidebar {
                transform: translateX(-100%);
            }
        }

        /*
         * DESKTOP (>=768px): Mặc định sidebar rộng 16rem, nội dung chính chừa lề trái 16rem.
         * Khi thu gọn: sidebar co lại còn 5rem, chỉ hiển thị icon và ẩn toàn bộ nhãn chữ
         * (thay vì ẩn hoàn toàn sidebar như trước đây).
         */
        @media (min-width: 768px) {
            #admin-main-container {
                padding-left: 16rem;
            }

            body.sidebar-collapsed #admin-main-container {
                padding-left: 5rem;
            }

            body.sidebar-collapsed #admin-sidebar {
                width: 5rem;
            }

            /*
             * Căn giữa icon & ẩn nhãn chữ. Nhãn là text node trần (không bọc thẻ) nên dùng
             * font-size: 0 để ẩn; icon Lucide dùng class kích thước cố định (w-4 h-4) nên không bị ảnh hưởng.
             */
            body.sidebar-collapsed #admin-sidebar nav a,
            body.sidebar-collapsed #admin-sidebar nav > div > button {
                justify-content: center;
                gap: 0;
                font-size: 0;
            }

            body.sidebar-collapsed #admin-sidebar nav a > span,
            body.sidebar-collapsed #admin-sidebar nav > div > button > span {
                gap: 0;
            }

            /* Ẩn mũi tên xổ xuống của menu cha (svg/i là con trực tiếp của button) */
            body.sidebar-collapsed #admin-sidebar nav > div > button > svg,
            body.sidebar-collapsed #admin-sidebar nav > div > button > i {
                display: none;
            }

            /* Ẩn toàn bộ menu con khi thu gọn (ghi đè cả inline style do x-show sinh ra) */
            body.sidebar-collapsed #admin-sidebar nav > div > div {
                display: none !important;
            }

            /* Ẩn badge số lượng (span thứ 2) trong các link có badge thông báo */
            body.sidebar-collapsed #admin-sidebar nav a > span + span {
                display: none;
            }

            /*
             * Ép tất cả icon top-level về cùng một kích thước khi thu gọn.
             * Cần thiết vì các icon bọc trong <span> (menu có badge / menu cha) và icon là con
             * trực tiếp của <a> được flexbox xử lý khác nhau → hiển thị to nhỏ không đồng đều.
             * flex-shrink: 0 ngăn icon bị co lại khi trở thành flex item.
             */
            body.sidebar-collapsed #admin-sidebar nav a svg,
            body.sidebar-collapsed #admin-sidebar nav a i,
            body.sidebar-collapsed #admin-sidebar nav > div > button svg,
            body.sidebar-collapsed #admin-sidebar nav > div > button i {
                width: 1.25rem !important;
                height: 1.25rem !important;
                flex-shrink: 0;
            }

            /* Thu gọn logo thương hiệu: chỉ giữ lại icon, ẩn phần chữ */
            body.sidebar-collapsed #admin-sidebar .sidebar-brand {
                justify-content: center;
                padding-left: 0;
                padding-right: 0;
            }

            body.sidebar-collapsed #admin-sidebar .sidebar-brand-text {
                display: none;
            }
        }

        /*
         * Flyout hiển thị khi rê chuột vào một mục menu lúc sidebar đang thu gọn:
         * - Link thường: chỉ hiển thị tên menu (giống tooltip).
         * - Menu cha có menu con: hiển thị tên cha + danh sách menu con bấm được.
         * Được gắn vào <body> (ngoài sidebar) để không bị overflow của thanh nav cắt mất.
         */
        .admin-sidebar-flyout {
            position: fixed;
            z-index: 9999;
            min-width: 12rem;
            padding: 0.375rem;
            background-color: #0f172a;
            color: #e2e8f0;
            border-radius: 0.625rem;
            box-shadow: 0 12px 30px -8px rgba(0, 0, 0, 0.55);
            pointer-events: none;
            opacity: 0;
            transform: translateX(-4px);
            transition: opacity .15s ease, transform .15s ease;
        }

        .admin-sidebar-flyout.is-visible {
            opacity: 1;
            transform: translateX(0);
            pointer-events: auto;
        }

        /* Khi chỉ có tiêu đề (link thường) thì thu nhỏ như tooltip, không cần tương tác */
        .admin-sidebar-flyout:not(.has-list) {
            min-width: 0;
            padding: 0.375rem 0.75rem;
            pointer-events: none;
        }

        .admin-sidebar-flyout__title {
            padding: 0.375rem 0.625rem;
            font-size: 0.75rem;
            font-weight: 700;
            white-space: nowrap;
            color: #fff;
        }

        .admin-sidebar-flyout.has-list .admin-sidebar-flyout__title {
            border-bottom: 1px solid rgba(148, 163, 184, 0.15);
            margin-bottom: 0.25rem;
            padding-bottom: 0.5rem;
        }

        .admin-sidebar-flyout__list {
            display: flex;
            flex-direction: column;
            gap: 0.125rem;
            max-height: calc(100vh - 4rem);
            overflow-y: auto;
        }

        .admin-sidebar-flyout__item {
            padding: 0.4rem 0.625rem;
            font-size: 0.75rem;
            font-weight: 600;
            white-space: nowrap;
            border-radius: 0.375rem;
            color: #cbd5e1;
            transition: background-color .15s ease, color .15s ease;
        }

        .admin-sidebar-flyout__item:hover {
            background-color: #1e293b;
            color: #fff;
        }

        .admin-sidebar-flyout__item.is-active {
            background-color: var(--shopee-color, #ee4d2d);
            color: #fff;
        }

        .dark .admin-sidebar-flyout {
            background-color: #020617;
            border: 1px solid rgba(148, 163, 184, 0.15);
        }
    </style>

    <!-- Custom Dark Mode CSS -->
    <link href="{{ asset('css/darkmode.css') }}" rel="stylesheet">
    @yield('styles')
</head>

<body class="flex h-full text-gray-800 admin-page"
    x-data="{ sidebarOpen: window.innerWidth >= 768 ? (localStorage.getItem('admin_sidebar_collapsed') !== '1') : false }"
    x-init="$watch('sidebarOpen', value => { if (window.innerWidth >= 768) localStorage.setItem('admin_sidebar_collapsed', value ? '0' : '1'); })"
    :class="{ 'sidebar-collapsed': !sidebarOpen }">

    <script>
        // Khôi phục trạng thái thu gọn sidebar ngay khi <body> được phân tích, trước khi trình duyệt vẽ,
        // để tránh hiện tượng nháy (sidebar hiển thị đầy đủ rồi mới co lại) trên màn hình desktop.
        if (window.innerWidth >= 768 && localStorage.getItem('admin_sidebar_collapsed') === '1') {
            document.body.classList.add('sidebar-collapsed');
        }
    </script>

    <!-- Thanh Sidebar quản trị sử dụng hiệu ứng chuyển động mượt mà khi đóng/mở (tăng z-index lên z-[60] trên mobile để đè menu di động, hạ xuống md:z-[40] trên desktop để nằm dưới modal z-50) -->
    <aside id="admin-sidebar" class="fixed inset-y-0 left-0 z-[60] md:z-[40] flex flex-col w-64 bg-slate-900 dark:bg-slate-950 text-slate-300 border-r border-transparent dark:border-slate-900/40 transition-transform duration-300 ease-in-out">

        <!-- Sidebar Brand logo -->
        <div class="sidebar-brand flex items-center gap-3.5 px-6 h-16 bg-slate-950 dark:bg-black text-white border-b border-slate-800/80 dark:border-slate-900/60">
            <div class="flex items-center justify-center w-9 h-9 rounded-xl bg-gradient-to-tr from-shopee to-shopee-light shadow-md shadow-shopee/20 text-white shrink-0">
                <i data-lucide="shield" class="w-4.5 h-4.5"></i>
            </div>
            <div class="sidebar-brand-text flex flex-col truncate">
                <span class="text-sm font-bold tracking-wider text-slate-100 uppercase leading-none">CMSNT</span>
                <span class="text-[9px] font-semibold text-slate-400 uppercase tracking-widest mt-1">Panel Quản Trị</span>
            </div>
        </div>

        <!-- Sidebar Navigation Menu -->
        <nav class="flex-1 px-4 py-4 space-y-1 overflow-y-auto">
            <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3 px-3 py-2.5 text-sm font-medium rounded-lg transition-all {{ request()->routeIs('admin.dashboard') ? 'text-white bg-shopee' : 'hover:bg-slate-800 hover:text-white' }}">
                <i data-lucide="layout-dashboard" class="w-4 h-4"></i>
                {{ __('Tổng quan') }}
            </a>

            @if(auth()->user()->hasPermission('manage_updates'))
            <a href="{{ route('admin.update.index') }}" class="flex items-center gap-3 px-3 py-2.5 text-sm font-medium rounded-lg transition-all {{ request()->routeIs('admin.update.*') ? 'text-white bg-shopee' : 'hover:bg-slate-800 hover:text-white' }}">
                <i data-lucide="git-pull-request" class="w-4 h-4"></i>
                {{ __('Cập nhật phiên bản') }}
            </a>
            @endif

            @if(auth()->user()->hasPermission('manage_users'))
            <a href="{{ route('admin.users.index') }}" class="flex items-center gap-3 px-3 py-2.5 text-sm font-medium rounded-lg transition-all {{ request()->routeIs('admin.users.*') ? 'text-white bg-shopee' : 'hover:bg-slate-800 hover:text-white' }}">
                <i data-lucide="users" class="w-4 h-4"></i>
                {{ __('Quản lý thành viên') }}
            </a>
            @endif

            @if(auth()->user()->hasPermission('view_balance_logs') || auth()->user()->hasPermission('view_activity_logs') || auth()->user()->hasPermission('view_checkin_logs') || auth()->user()->hasPermission('view_referral_commissions') || auth()->user()->hasPermission('view_cashback_click_logs') || auth()->user()->hasPermission('view_short_links') || auth()->user()->hasPermission('view_email_queue_logs') || auth()->user()->hasPermission('view_telegram_queue_logs') || auth()->user()->hasPermission('view_notification_logs') || auth()->user()->hasPermission('view_saved_products') || auth()->user()->hasPermission('view_system_logs') || auth()->user()->hasPermission('manage_products'))
            <div x-data="{ open: {{ request()->is(\App\Models\Setting::getVal('admin_prefix', 'admin') . '/balance-logs*') || request()->is(\App\Models\Setting::getVal('admin_prefix', 'admin') . '/logs*') || request()->is(\App\Models\Setting::getVal('admin_prefix', 'admin') . '/products*') ? 'true' : 'false' }} }" class="space-y-1">
                <button @click="open = !open" class="w-full flex items-center justify-between px-3 py-2.5 text-sm font-medium rounded-lg transition-all hover:bg-slate-800 hover:text-white">
                    <span class="flex items-center gap-3">
                        <i data-lucide="scroll-text" class="w-4 h-4"></i>
                        {{ __('Nhật ký & Logs') }}
                    </span>
                    <i data-lucide="chevron-down" class="w-3.5 h-3.5 transition-transform duration-200" :class="{ 'rotate-180': open }"></i>
                </button>
                <div x-show="open" x-cloak class="pl-6 space-y-1.5 transition-all">
                    {{-- Di chuyển Quản lý sản phẩm vào menu con Nhật ký & Logs --}}
                    @if(auth()->user()->hasPermission('manage_products'))
                    <a href="{{ route('admin.products.index') }}" class="flex items-center gap-2 px-3 py-1.5 text-xs font-semibold rounded-lg transition-all {{ request()->routeIs('admin.products.*') ? 'text-white bg-shopee' : 'hover:bg-slate-800 hover:text-white' }}">
                        <i data-lucide="shopping-bag" class="w-3.5 h-3.5"></i>
                        {{ __('Quản lý sản phẩm') }}
                    </a>
                    @endif

                    @if(auth()->user()->hasPermission('view_balance_logs'))
                    <a href="{{ route('admin.balance_logs.index') }}" class="flex items-center gap-2 px-3 py-1.5 text-xs font-semibold rounded-lg transition-all {{ request()->routeIs('admin.balance_logs.*') ? 'text-white bg-shopee' : 'hover:bg-slate-800 hover:text-white' }}">
                        <i data-lucide="arrow-left-right" class="w-3.5 h-3.5"></i>
                        {{ __('Biến động số dư') }}
                    </a>
                    @endif

                    @if(auth()->user()->hasPermission('view_activity_logs'))
                    <a href="{{ route('admin.logs.activity') }}" class="flex items-center gap-2 px-3 py-1.5 text-xs font-semibold rounded-lg transition-all {{ request()->routeIs('admin.logs.activity') ? 'text-white bg-shopee' : 'hover:bg-slate-800 hover:text-white' }}">
                        <i data-lucide="history" class="w-3.5 h-3.5"></i>
                        {{ __('Nhật ký hoạt động') }}
                    </a>
                    @endif

                    @if(auth()->user()->hasPermission('view_checkin_logs'))
                    <a href="{{ route('admin.logs.checkin') }}" class="flex items-center gap-2 px-3 py-1.5 text-xs font-semibold rounded-lg transition-all {{ request()->routeIs('admin.logs.checkin') ? 'text-white bg-shopee' : 'hover:bg-slate-800 hover:text-white' }}">
                        <i data-lucide="calendar-check" class="w-3.5 h-3.5"></i>
                        {{ __('Nhật ký điểm danh') }}
                    </a>
                    @endif

                    @if(auth()->user()->hasPermission('view_referral_commissions'))
                    <a href="{{ route('admin.logs.referrals') }}" class="flex items-center gap-2 px-3 py-1.5 text-xs font-semibold rounded-lg transition-all {{ request()->routeIs('admin.logs.referrals') ? 'text-white bg-shopee' : 'hover:bg-slate-800 hover:text-white' }}">
                        <i data-lucide="users-2" class="w-3.5 h-3.5"></i>
                        {{ __('Nhật ký Affiliate') }}
                    </a>
                    @endif

                    @if(auth()->user()->hasPermission('view_cashback_click_logs'))
                    <a href="{{ route('admin.logs.cashback_clicks') }}" class="flex items-center gap-2 px-3 py-1.5 text-xs font-semibold rounded-lg transition-all {{ request()->routeIs('admin.logs.cashback_clicks') ? 'text-white bg-shopee' : 'hover:bg-slate-800 hover:text-white' }}">
                        <i data-lucide="mouse-pointer-click" class="w-3.5 h-3.5"></i>
                        {{ __('Lượt click hoàn tiền') }}
                    </a>
                    @endif

                    @if(auth()->user()->hasPermission('view_short_links'))
                    <a href="{{ route('admin.logs.short_links') }}" class="flex items-center gap-2 px-3 py-1.5 text-xs font-semibold rounded-lg transition-all {{ request()->routeIs('admin.logs.short_links') ? 'text-white bg-shopee' : 'hover:bg-slate-800 hover:text-white' }}">
                        <i data-lucide="link" class="w-3.5 h-3.5"></i>
                        {{ __('Liên kết rút gọn') }}
                    </a>
                    @endif

                    @if(auth()->user()->hasPermission('view_email_queue_logs'))
                    <a href="{{ route('admin.logs.email_queue') }}" class="flex items-center gap-2 px-3 py-1.5 text-xs font-semibold rounded-lg transition-all {{ request()->routeIs('admin.logs.email_queue') ? 'text-white bg-shopee' : 'hover:bg-slate-800 hover:text-white' }}">
                        <i data-lucide="mail" class="w-3.5 h-3.5"></i>
                        {{ __('Hàng đợi Email') }}
                    </a>
                    @endif

                    @if(auth()->user()->hasPermission('view_telegram_queue_logs'))
                    <a href="{{ route('admin.logs.telegram_queue') }}" class="flex items-center gap-2 px-3 py-1.5 text-xs font-semibold rounded-lg transition-all {{ request()->routeIs('admin.logs.telegram_queue') ? 'text-white bg-shopee' : 'hover:bg-slate-800 hover:text-white' }}">
                        <i data-lucide="send" class="w-3.5 h-3.5"></i>
                        {{ __('Hàng đợi Telegram') }}
                    </a>
                    @endif

                    @if(auth()->user()->hasPermission('view_notification_logs'))
                    <a href="{{ route('admin.logs.notifications') }}" class="flex items-center gap-2 px-3 py-1.5 text-xs font-semibold rounded-lg transition-all {{ request()->routeIs('admin.logs.notifications') ? 'text-white bg-shopee' : 'hover:bg-slate-800 hover:text-white' }}">
                        <i data-lucide="bell" class="w-3.5 h-3.5"></i>
                        {{ __('Nhật ký thông báo') }}
                    </a>
                    @endif

                    @if(auth()->user()->hasPermission('view_saved_products'))
                    <a href="{{ route('admin.logs.saved_products') }}" class="flex items-center gap-2 px-3 py-1.5 text-xs font-semibold rounded-lg transition-all {{ request()->routeIs('admin.logs.saved_products') ? 'text-white bg-shopee' : 'hover:bg-slate-800 hover:text-white' }}">
                        <i data-lucide="heart" class="w-3.5 h-3.5"></i>
                        {{ __('Sản phẩm đã lưu') }}
                    </a>
                    @endif

                    @if(auth()->user()->hasPermission('view_api_logs'))
                    <a href="{{ route('admin.logs.api') }}" class="flex items-center gap-2 px-3 py-1.5 text-xs font-semibold rounded-lg transition-all {{ request()->routeIs('admin.logs.api') ? 'text-white bg-shopee' : 'hover:bg-slate-800 hover:text-white' }}">
                        <i data-lucide="webhook" class="w-3.5 h-3.5"></i>
                        {{ __('Nhật ký gọi API') }}
                    </a>
                    @endif

                    @if(auth()->user()->hasPermission('view_system_logs'))
                    <a href="{{ route('admin.logs.system') }}" class="flex items-center gap-2 px-3 py-1.5 text-xs font-semibold rounded-lg transition-all {{ request()->routeIs('admin.logs.system') ? 'text-white bg-shopee' : 'hover:bg-slate-800 hover:text-white' }}">
                        <i data-lucide="terminal" class="w-3.5 h-3.5"></i>
                        {{ __('Nhật ký hệ thống') }}
                    </a>
                    @endif
                </div>
            </div>
            @endif



            @if(auth()->user()->hasPermission('manage_cashbacks'))
            <a href="{{ route('admin.cashback.index') }}" class="flex items-center gap-3 px-3 py-2.5 text-sm font-medium rounded-lg transition-all {{ request()->routeIs('admin.cashback.*') ? 'text-white bg-shopee' : 'hover:bg-slate-800 hover:text-white' }}">
                <i data-lucide="receipt" class="w-4 h-4"></i>
                {{ __('Đơn hàng hoàn tiền') }}
            </a>
            @endif

            @if(auth()->user()->hasPermission('manage_withdrawals'))
            @php
                // Lấy số lượng yêu cầu rút tiền đang chờ duyệt để hiển thị badge trên sidebar admin panel
                // Việc đếm số lượng đơn này giúp Quản trị viên nhận biết nhanh chóng có bao nhiêu yêu cầu rút tiền cần xử lý
                $pendingWithdrawalsCount = \App\Models\Withdrawal::where('status', 'pending')->count();
            @endphp
            <a href="{{ route('admin.withdrawals.index') }}" class="flex items-center justify-between px-3 py-2.5 text-sm font-medium rounded-lg transition-all {{ request()->routeIs('admin.withdrawals.*') ? 'text-white bg-shopee' : 'hover:bg-slate-800 hover:text-white' }}">
                <span class="flex items-center gap-3">
                    <i data-lucide="wallet" class="w-4 h-4"></i>
                    {{ __('Quản lý rút tiền') }}
                </span>
                @if($pendingWithdrawalsCount > 0)
                <span class="inline-flex items-center justify-center px-1.5 py-0.5 text-[9px] font-black leading-none text-white bg-red-600 rounded-full">
                    {{ $pendingWithdrawalsCount }}
                </span>
                @endif
            </a>
            @endif

            @if(auth()->user()->hasPermission('manage_gifts'))
            @php
                $pendingRedemptionsCount = \App\Models\GiftRedemption::where('status', 'pending')->count();
            @endphp
            <a href="{{ route('admin.gifts.index') }}" class="flex items-center justify-between px-3 py-2.5 text-sm font-medium rounded-lg transition-all {{ request()->routeIs('admin.gifts.*') ? 'text-white bg-shopee' : 'hover:bg-slate-800 hover:text-white' }}">
                <span class="flex items-center gap-3">
                    <i data-lucide="gift" class="w-4 h-4"></i>
                    {{ __('Quản lý quà tặng') }}
                </span>
                @if($pendingRedemptionsCount > 0)
                <span class="inline-flex items-center justify-center px-1.5 py-0.5 text-[9px] font-black leading-none text-white bg-red-600 rounded-full">
                    {{ $pendingRedemptionsCount }}
                </span>
                @endif
            </a>
            @endif

            @if(auth()->user()->hasPermission('manage_gift_codes'))
            <a href="{{ route('admin.giftcodes.index') }}" class="flex items-center justify-between px-3 py-2.5 text-sm font-medium rounded-lg transition-all {{ request()->routeIs('admin.giftcodes.*') ? 'text-white bg-shopee' : 'hover:bg-slate-800 hover:text-white' }}">
                <span class="flex items-center gap-3">
                    <i data-lucide="ticket" class="w-4 h-4"></i>
                    {{ __('Quản lý Giftcode') }}
                </span>
            </a>
            @endif

            @if(auth()->user()->hasPermission('manage_coupons'))
            <a href="{{ route('admin.coupons.index') }}" class="flex items-center justify-between px-3 py-2.5 text-sm font-medium rounded-lg transition-all {{ request()->routeIs('admin.coupons.*') ? 'text-white bg-shopee' : 'hover:bg-slate-800 hover:text-white' }}">
                <span class="flex items-center gap-3">
                    <i data-lucide="ticket-percent" class="w-4 h-4"></i>
                    {{ __('Quản lý Mã giảm giá') }}
                </span>
            </a>
            @endif

            @if(auth()->user()->hasPermission('manage_tasks'))
            @php
                $pendingTasksCount = \App\Models\UserTask::where('status', 'completed')
                    ->whereHas('task', fn($q) => $q->where('action', 'custom'))->count();
            @endphp
            <a href="{{ route('admin.tasks.index') }}" class="flex items-center justify-between px-3 py-2.5 text-sm font-medium rounded-lg transition-all {{ request()->routeIs('admin.tasks.*') ? 'text-white bg-shopee' : 'hover:bg-slate-800 hover:text-white' }}">
                <span class="flex items-center gap-3">
                    <i data-lucide="list-checks" class="w-4 h-4"></i>
                    {{ __('Quản lý Nhiệm vụ') }}
                </span>
                @if($pendingTasksCount > 0)
                <span class="inline-flex items-center justify-center px-1.5 py-0.5 text-[9px] font-black leading-none text-white bg-red-600 rounded-full">
                    {{ $pendingTasksCount }}
                </span>
                @endif
            </a>
            @endif

            @if(auth()->user()->hasPermission('manage_blog'))
            <div x-data="{ open: {{ request()->is(\App\Models\Setting::getVal('admin_prefix', 'admin') . '/blog*') ? 'true' : 'false' }} }" class="space-y-1">
                <button @click="open = !open" class="w-full flex items-center justify-between px-3 py-2.5 text-sm font-medium rounded-lg transition-all hover:bg-slate-800 hover:text-white">
                    <span class="flex items-center gap-3">
                        <i data-lucide="book-open" class="w-4 h-4"></i>
                        {{ __('Quản lý Blog') }}
                    </span>
                    <i data-lucide="chevron-down" class="w-3.5 h-3.5 transition-transform duration-200" :class="{ 'rotate-180': open }"></i>
                </button>
                <div x-show="open" x-cloak class="pl-6 space-y-1.5 transition-all">
                    <a href="{{ route('admin.blog.posts.index') }}" class="flex items-center gap-2 px-3 py-1.5 text-xs font-semibold rounded-lg transition-all {{ request()->routeIs('admin.blog.posts.*') ? 'text-white bg-shopee' : 'hover:bg-slate-800 hover:text-white' }}">
                        <i data-lucide="file-text" class="w-3.5 h-3.5"></i>
                        {{ __('Bài viết') }}
                    </a>
                    <a href="{{ route('admin.blog.categories.index') }}" class="flex items-center gap-2 px-3 py-1.5 text-xs font-semibold rounded-lg transition-all {{ request()->routeIs('admin.blog.categories.*') ? 'text-white bg-shopee' : 'hover:bg-slate-800 hover:text-white' }}">
                        <i data-lucide="folder" class="w-3.5 h-3.5"></i>
                        {{ __('Chuyên mục') }}
                    </a>
                    <a href="{{ route('admin.blog.tags.index') }}" class="flex items-center gap-2 px-3 py-1.5 text-xs font-semibold rounded-lg transition-all {{ request()->routeIs('admin.blog.tags.*') ? 'text-white bg-shopee' : 'hover:bg-slate-800 hover:text-white' }}">
                        <i data-lucide="tag" class="w-3.5 h-3.5"></i>
                        {{ __('Thẻ tag') }}
                    </a>
                    <a href="{{ route('admin.blog.comments.index') }}" class="flex items-center gap-2 px-3 py-1.5 text-xs font-semibold rounded-lg transition-all {{ request()->routeIs('admin.blog.comments.*') ? 'text-white bg-shopee' : 'hover:bg-slate-800 hover:text-white' }}">
                        <i data-lucide="message-square" class="w-3.5 h-3.5"></i>
                        {{ __('Bình luận') }}
                    </a>
                    <a href="{{ route('admin.blog.settings.index') }}" class="flex items-center gap-2 px-3 py-1.5 text-xs font-semibold rounded-lg transition-all {{ request()->routeIs('admin.blog.settings.*') ? 'text-white bg-shopee' : 'hover:bg-slate-800 hover:text-white' }}">
                        <i data-lucide="settings-2" class="w-3.5 h-3.5"></i>
                        {{ __('Cấu hình Blog') }}
                    </a>
                </div>
            </div>
            @endif

            @if(auth()->user()->hasPermission('manage_pages'))
            <a href="{{ route('admin.pages.index') }}" class="flex items-center gap-3 px-3 py-2.5 text-sm font-medium rounded-lg transition-all {{ request()->routeIs('admin.pages.*') ? 'text-white bg-shopee' : 'hover:bg-slate-800 hover:text-white' }}">
                <i data-lucide="file-text" class="w-4 h-4"></i>
                {{ __('Quản lý Page') }}
            </a>
            @endif

            @if(auth()->user()->hasPermission('manage_menus'))
            <a href="{{ route('admin.menus.index') }}" class="flex items-center gap-3 px-3 py-2.5 text-sm font-medium rounded-lg transition-all {{ request()->routeIs('admin.menus.*') ? 'text-white bg-shopee' : 'hover:bg-slate-800 hover:text-white' }}">
                <i data-lucide="menu" class="w-4 h-4"></i>
                {{ __('Quản lý Menu') }}
            </a>
            @endif

            @if(auth()->user()->hasPermission('manage_settings'))
            <a href="{{ route('admin.banners.index') }}" class="flex items-center gap-3 px-3 py-2.5 text-sm font-medium rounded-lg transition-all {{ request()->routeIs('admin.banners.*') ? 'text-white bg-shopee' : 'hover:bg-slate-800 hover:text-white' }}">
                <i data-lucide="image" class="w-4 h-4"></i>
                {{ __('Banner quảng cáo') }}
            </a>
            @endif

            @if(auth()->user()->hasPermission('manage_media'))
            <a href="{{ route('admin.media.index') }}" class="flex items-center gap-3 px-3 py-2.5 text-sm font-medium rounded-lg transition-all {{ request()->routeIs('admin.media.index') ? 'text-white bg-shopee' : 'hover:bg-slate-800 hover:text-white' }}">
                <i data-lucide="folder-open" class="w-4 h-4"></i>
                {{ __('Quản lý Media') }}
            </a>
            @endif

            @if(auth()->user()->hasPermission('manage_users'))
            <a href="{{ route('admin.notifications.index') }}" class="flex items-center gap-3 px-3 py-2.5 text-sm font-medium rounded-lg transition-all {{ request()->routeIs('admin.notifications.*') ? 'text-white bg-shopee' : 'hover:bg-slate-800 hover:text-white' }}">
                <i data-lucide="bell" class="w-4 h-4"></i>
                {{ __('Gửi thông báo') }}
            </a>
            @endif

            @if(auth()->user()->hasPermission('manage_email_campaigns'))
            <a href="{{ route('admin.email_campaigns.index') }}" class="flex items-center gap-3 px-3 py-2.5 text-sm font-medium rounded-lg transition-all {{ request()->routeIs('admin.email_campaigns.*') ? 'text-white bg-shopee' : 'hover:bg-slate-800 hover:text-white' }}">
                <i data-lucide="mail-check" class="w-4 h-4"></i>
                {{ __('Email Campaign') }}
            </a>
            @endif

            @if(auth()->user()->hasPermission('manage_roles'))
            <a href="{{ route('admin.roles.index') }}" class="flex items-center gap-3 px-3 py-2.5 text-sm font-medium rounded-lg transition-all {{ request()->routeIs('admin.roles.*') ? 'text-white bg-shopee' : 'hover:bg-slate-800 hover:text-white' }}">
                <i data-lucide="shield-check" class="w-4 h-4"></i>
                {{ __('Vai trò & Quyền hạn') }}
            </a>
            @endif

            @if(auth()->user()->hasPermission('manage_languages'))
            <a href="{{ route('admin.languages.index') }}" class="flex items-center gap-3 px-3 py-2.5 text-sm font-medium rounded-lg transition-all {{ request()->routeIs('admin.languages.*') ? 'text-white bg-shopee' : 'hover:bg-slate-800 hover:text-white' }}">
                <i data-lucide="languages" class="w-4 h-4"></i>
                {{ __('Quản lý ngôn ngữ') }}
            </a>
            @endif

            @if(auth()->user()->hasPermission('manage_currencies'))
            <a href="{{ route('admin.currencies.index') }}" class="flex items-center gap-3 px-3 py-2.5 text-sm font-medium rounded-lg transition-all {{ request()->routeIs('admin.currencies.*') ? 'text-white bg-shopee' : 'hover:bg-slate-800 hover:text-white' }}">
                <i data-lucide="coins" class="w-4 h-4"></i>
                {{ __('Quản lý tiền tệ') }}
            </a>
            @endif

            @if(auth()->user()->hasPermission('manage_tools'))
            <a href="{{ route('admin.tools.index') }}" class="flex items-center gap-3 px-3 py-2.5 text-sm font-medium rounded-lg transition-all {{ request()->routeIs('admin.tools.*') ? 'text-white bg-shopee' : 'hover:bg-slate-800 hover:text-white' }}">
                <i data-lucide="wrench" class="w-4 h-4"></i>
                {{ __('Công cụ') }}
            </a>
            @endif

            @if(auth()->user()->hasPermission('manage_bots'))
            <div x-data="{ open: {{ request()->is(\App\Models\Setting::getVal('admin_prefix', 'admin') . '/bots*') ? 'true' : 'false' }} }" class="space-y-1">
                <button @click="open = !open" class="w-full flex items-center justify-between px-3 py-2.5 text-sm font-medium rounded-lg transition-all hover:bg-slate-800 hover:text-white">
                    <span class="flex items-center gap-3">
                        <i data-lucide="bot" class="w-4 h-4"></i>
                        {{ __('Quản lý Bot') }}
                    </span>
                    <i data-lucide="chevron-down" class="w-3.5 h-3.5 transition-transform duration-200" :class="{ 'rotate-180': open }"></i>
                </button>
                <div x-show="open" x-cloak class="pl-6 space-y-1.5 transition-all">
                    <a href="{{ route('admin.bots.index', ['tab' => 'zalo']) }}" class="flex items-center gap-2 px-3 py-1.5 text-xs font-semibold rounded-lg transition-all {{ request()->routeIs('admin.bots.*') && request()->input('tab', 'zalo') === 'zalo' ? 'text-white bg-shopee' : 'hover:bg-slate-800 hover:text-white' }}">
                        <i data-lucide="message-circle" class="w-3.5 h-3.5"></i>
                        {{ __('Zalo Bot') }}
                    </a>
                    <a href="{{ route('admin.bots.index', ['tab' => 'telegram']) }}" class="flex items-center gap-2 px-3 py-1.5 text-xs font-semibold rounded-lg transition-all {{ request()->routeIs('admin.bots.*') && request()->input('tab') === 'telegram' ? 'text-white bg-shopee' : 'hover:bg-slate-800 hover:text-white' }}">
                        <i data-lucide="send" class="w-3.5 h-3.5"></i>
                        {{ __('Telegram Bot') }}
                    </a>
                    <a href="{{ route('admin.bots.index', ['tab' => 'messages']) }}" class="flex items-center gap-2 px-3 py-1.5 text-xs font-semibold rounded-lg transition-all {{ request()->routeIs('admin.bots.*') && request()->input('tab') === 'messages' ? 'text-white bg-shopee' : 'hover:bg-slate-800 hover:text-white' }}">
                        <i data-lucide="message-square" class="w-3.5 h-3.5"></i>
                        {{ __('Lịch sử tin nhắn') }}
                    </a>
                </div>
            </div>
            @endif

            @if(auth()->user()->hasPermission('manage_appearance'))
            <a href="{{ route('admin.appearance.index') }}" class="flex items-center gap-3 px-3 py-2.5 text-sm font-medium rounded-lg transition-all {{ request()->routeIs('admin.appearance.*') ? 'text-white bg-shopee' : 'hover:bg-slate-800 hover:text-white' }}">
                <i data-lucide="palette" class="w-4 h-4"></i>
                {{ __('Giao diện') }}
            </a>
            @endif

            @if(auth()->user()->hasPermission('view_system_status'))
            <a href="{{ route('admin.system_status') }}" class="flex items-center gap-3 px-3 py-2.5 text-sm font-medium rounded-lg transition-all {{ request()->routeIs('admin.system_status') ? 'text-white bg-shopee' : 'hover:bg-slate-800 hover:text-white' }}">
                <i data-lucide="activity" class="w-4 h-4"></i>
                {{ __('Trạng thái hệ thống') }}
            </a>
            @endif

            @if(auth()->user()->hasPermission('manage_system_cleanup'))
            <a href="{{ route('admin.system_cleanup.index') }}" class="flex items-center gap-3 px-3 py-2.5 text-sm font-medium rounded-lg transition-all {{ request()->routeIs('admin.system_cleanup.*') ? 'text-white bg-shopee' : 'hover:bg-slate-800 hover:text-white' }}">
                <i data-lucide="shield-alert" class="w-4 h-4"></i>
                {{ __('Dọn dẹp hệ thống') }}
            </a>
            @endif

            @if(auth()->user()->hasPermission('manage_settings'))
            <a href="{{ route('admin.settings.index') }}" class="flex items-center gap-3 px-3 py-2.5 text-sm font-medium rounded-lg transition-all {{ request()->routeIs('admin.settings.*') ? 'text-white bg-shopee' : 'hover:bg-slate-800 hover:text-white' }}">
                <i data-lucide="settings" class="w-4 h-4"></i>
                {{ __('Cài đặt') }}
            </a>
            @endif

            <div class="border-t border-slate-800 my-4"></div>

            <a href="{{ route('home') }}" class="flex items-center gap-3 px-3 py-2.5 text-sm font-medium rounded-lg hover:bg-slate-800 hover:text-white">
                <i data-lucide="arrow-left-right" class="w-4 h-4"></i>
                Xem Website
            </a>
        </nav>


    </aside>

    <!-- Backdrop Overlay on Mobile when Sidebar is open (tăng z-index lên z-[55] để nằm dưới sidebar z-[60] nhưng đè lên menu bottom z-50 và topbar z-30) -->
    <div x-show="sidebarOpen" @click="sidebarOpen = false" x-transition x-cloak class="fixed inset-0 z-[55] bg-slate-900/40 md:hidden"></div>

    <!-- 2. MAIN WORKSPACE CONTAINER -->
    <!-- Thêm id để JS nút Back-to-Top có thể tìm đúng phần tử đang scroll thực tế (do body dùng flex h-full, window không scroll) -->
    {{-- Lề trái (padding-left) được điều khiển hoàn toàn bằng CSS dựa trên class sidebar-collapsed ở thẻ <body>:
         16rem khi sidebar đầy đủ và 5rem khi sidebar thu gọn (chỉ hiển thị icon).
         Thêm hiệu ứng transition-all giúp co giãn phần nội dung mượt mà khi đóng/mở sidebar. --}}
    <!-- Thêm khoảng đệm pb-20 ở dưới cùng trên mobile để tránh bị menu bottom che khuất nội dung khi cuộn hết trang -->
    <div id="admin-main-container" class="flex flex-col flex-grow min-w-0 overflow-x-hidden overflow-y-auto transition-all duration-300 ease-in-out pb-20 md:pb-0">
        
        {{-- Thanh cảnh báo chạy ngang phía trên cùng của Admin Panel khi bật chế độ Demo --}}
        @if(config('app.demo'))
        <div class="bg-gradient-to-r from-red-600 to-orange-600 text-white px-4 py-2.5 text-center text-xs font-bold flex items-center justify-center gap-2 shadow-inner">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 animate-bounce shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
            <span>{{ __('Hệ thống đang chạy ở chế độ Demo thử nghiệm. Mọi thao tác submit form/thay đổi dữ liệu trong Admin Panel đều bị khoá!') }}</span>
        </div>
        @endif

        <!-- Thanh Header (Topbar) hỗ trợ sáng/tối tự động - Cố định trên cùng màn hình khi cuộn (sticky top-0 z-30) kèm hiệu ứng glassmorphism premium -->
        <header class="sticky top-0 z-30 flex items-center justify-between h-16 w-full shrink-0 px-6 bg-white/95 dark:bg-slate-900/95 backdrop-blur border-b border-gray-200 dark:border-slate-800 transition-colors duration-300">
            <!-- Nút bật/tắt hiển thị Sidebar -->
            <button @click="sidebarOpen = !sidebarOpen" class="p-2 text-gray-500 dark:text-gray-400 rounded-lg hover:bg-gray-100 dark:hover:bg-slate-800 transition-colors shrink-0">
                <i data-lucide="menu" class="w-5 h-5"></i>
            </button>

            <!-- Ô tìm kiếm giả ở Topbar để mở Spotlight Search (Chỉ hiển thị trên md trở lên) -->
            <div class="flex-grow max-w-md mx-6 hidden md:block">
                <button @click="$dispatch('open-search')" class="w-full flex items-center justify-between gap-2 px-3 py-1.5 text-xs text-gray-400 dark:text-slate-500 bg-gray-50 dark:bg-slate-800/40 hover:bg-gray-100 dark:hover:bg-slate-800/80 border border-gray-200/80 dark:border-slate-800/85 rounded-lg transition-all focus:outline-none">
                    <span class="flex items-center gap-2">
                        <i data-lucide="search" class="w-3.5 h-3.5 text-gray-400 dark:text-slate-500"></i>
                        <span>{{ __('Tìm kiếm nhanh chức năng & cài đặt...') }}</span>
                    </span>
                    <span class="flex items-center gap-0.5 px-1.5 py-0.5 text-[9px] font-bold text-gray-400 dark:text-slate-500 bg-white dark:bg-slate-900 border border-gray-250 dark:border-slate-800 rounded shadow-sm shrink-0">
                        <span>Ctrl</span>
                        <span>K</span>
                    </span>
                </button>
            </div>

            <!-- Menu bên phải: Các tuỳ chọn nhanh và Dropdown thông tin User -->
            <div class="flex items-center gap-4">
                <!-- Nút tìm kiếm nhanh trên Mobile (Ẩn trên md trở lên) -->
                <button @click="$dispatch('open-search')" class="md:hidden p-2 text-gray-500 hover:text-shopee dark:text-gray-405 dark:hover:text-white rounded-xl transition-all" title="Tìm kiếm nhanh">
                    <i data-lucide="search" class="w-5 h-5"></i>
                </button>
                <!-- Nút chuyển đổi giao diện Sáng / Tối -->
                <button @click="
                    if (document.documentElement.classList.contains('dark')) {
                        document.documentElement.classList.remove('dark');
                        localStorage.setItem('theme', 'light');
                        isDark = false;
                    } else {
                        document.documentElement.classList.add('dark');
                        localStorage.setItem('theme', 'dark');
                        isDark = true;
                    }
                "
                    x-data="{ isDark: document.documentElement.classList.contains('dark') }"
                    class="p-2 text-gray-500 hover:text-shopee dark:hover:text-yellow-400 rounded-xl transition-all"
                    title="Chuyển chế độ sáng/tối">
                    <svg x-show="!isDark" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                    </svg>
                    <svg x-show="isDark" class="w-5 h-5 text-yellow-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" x-cloak>
                        <circle cx="12" cy="12" r="5" stroke-linecap="round" stroke-linejoin="round" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42" />
                    </svg>
                </button>

                <!-- Dropmenu (Dropdown) thông tin tài khoản Admin trên Topbar -->
                <!-- Sử dụng AlpineJS để điều khiển đóng/mở mượt mà -->
                <div class="relative" x-data="{ userMenuOpen: false }" @click.outside="userMenuOpen = false">
                    <!-- Nút bấm kích hoạt Dropdown -->
                    <button @click="userMenuOpen = !userMenuOpen" class="flex items-center gap-2 p-1.5 rounded-xl hover:bg-gray-50 dark:hover:bg-slate-800 border border-transparent hover:border-gray-150 dark:hover:border-slate-700/60 transition-all focus:outline-none">
                        <!-- Hiển thị chữ cái đầu tiên của tên Admin -->
                        <div class="w-8 h-8 rounded-lg bg-gradient-to-tr from-shopee to-shopee-light flex items-center justify-center text-white font-bold text-sm shadow-sm">
                            {{ mb_strtoupper(mb_substr(auth()->user()->name ?? 'A', 0, 1)) }}
                        </div>
                        <!-- Hiển thị Tên & Vai trò nhanh (Ẩn trên màn hình nhỏ) -->
                        <div class="text-left hidden md:block">
                            <p class="text-xs font-semibold text-gray-700 dark:text-slate-200 leading-none">{{ auth()->user()->name }}</p>
                            <p class="text-[10px] text-gray-400 dark:text-slate-400 mt-1 leading-none">{{ __('Quản trị viên') }}</p>
                        </div>
                        <i data-lucide="chevron-down" class="w-3.5 h-3.5 text-gray-400 dark:text-slate-500 transition-transform duration-200" :class="{ 'rotate-180': userMenuOpen }"></i>
                    </button>

                    <!-- Khung Dropdown Menu với các hiệu ứng chuyển động AlpineJS mượt mà -->
                    <div x-show="userMenuOpen" 
                         x-transition:enter="transition ease-out duration-100 transform"
                         x-transition:enter-start="opacity-0 scale-95"
                         x-transition:enter-end="opacity-100 scale-100"
                         x-transition:leave="transition ease-in duration-75 transform"
                         x-transition:leave-start="opacity-100 scale-100"
                         x-transition:leave-end="opacity-0 scale-95"
                         x-cloak
                         class="absolute right-0 mt-2.5 w-60 rounded-xl bg-white dark:bg-slate-900 border border-gray-100 dark:border-slate-800 shadow-xl shadow-slate-200/50 dark:shadow-none z-50 py-1.5 focus:outline-none">
                        
                        <!-- Phần header hiển thị thông tin tài khoản chi tiết -->
                        <div class="px-4 py-2.5 border-b border-gray-100 dark:border-slate-800 flex flex-col gap-0.5">
                            <p class="text-[10px] font-bold text-gray-400 dark:text-slate-500 uppercase tracking-widest">{{ __('Tài khoản') }}</p>
                            <p class="text-sm font-bold text-gray-800 dark:text-slate-200 truncate">{{ auth()->user()->name }}</p>
                            <p class="text-xs text-gray-500 dark:text-slate-400 truncate">{{ auth()->user()->email }}</p>
                        </div>

                        <!-- Các liên kết điều hướng nhanh -->
                        <div class="py-1">
                            <!-- Quay lại Website chính -->
                            <a href="{{ route('home') }}" class="flex items-center gap-2.5 px-4 py-2 text-xs font-semibold text-gray-700 dark:text-slate-300 hover:bg-gray-50 dark:hover:bg-slate-800 hover:text-shopee dark:hover:text-white transition-all">
                                <i data-lucide="globe" class="w-4 h-4 text-gray-400 dark:text-slate-500"></i>
                                {{ __('Trang chủ Website') }}
                            </a>
                            <!-- Trang Dashboard cá nhân ở frontend -->
                            <a href="{{ route('dashboard') }}" class="flex items-center gap-2.5 px-4 py-2 text-xs font-semibold text-gray-700 dark:text-slate-300 hover:bg-gray-50 dark:hover:bg-slate-800 hover:text-shopee dark:hover:text-white transition-all">
                                <i data-lucide="layout-dashboard" class="w-4 h-4 text-gray-400 dark:text-slate-500"></i>
                                {{ __('Khu vực thành viên') }}
                            </a>
                            <!-- Trang chỉnh sửa thông tin cá nhân/mật khẩu -->
                            <a href="{{ route('profile') }}" class="flex items-center gap-2.5 px-4 py-2 text-xs font-semibold text-gray-700 dark:text-slate-300 hover:bg-gray-50 dark:hover:bg-slate-800 hover:text-shopee dark:hover:text-white transition-all">
                                <i data-lucide="user" class="w-4 h-4 text-gray-400 dark:text-slate-500"></i>
                                {{ __('Hồ sơ của tôi') }}
                            </a>
                            <!-- Trạng thái hệ thống (chỉ hiển thị nếu Admin có quyền) -->
                            @if(auth()->user()->hasPermission('view_system_status'))
                            <a href="{{ route('admin.system_status') }}" class="flex items-center gap-2.5 px-4 py-2 text-xs font-semibold text-gray-700 dark:text-slate-300 hover:bg-gray-50 dark:hover:bg-slate-800 hover:text-shopee dark:hover:text-white transition-all">
                                <i data-lucide="activity" class="w-4 h-4 text-gray-400 dark:text-slate-500"></i>
                                {{ __('Trạng thái hệ thống') }}
                            </a>
                            @endif
                            <!-- Dọn dẹp hệ thống (chỉ hiển thị nếu Admin có quyền) -->
                            @if(auth()->user()->hasPermission('manage_system_cleanup'))
                            <a href="{{ route('admin.system_cleanup.index') }}" class="flex items-center gap-2.5 px-4 py-2 text-xs font-semibold text-gray-700 dark:text-slate-300 hover:bg-gray-50 dark:hover:bg-slate-800 hover:text-shopee dark:hover:text-white transition-all">
                                <i data-lucide="shield-alert" class="w-4 h-4 text-gray-400 dark:text-slate-500"></i>
                                {{ __('Dọn dẹp hệ thống') }}
                            </a>
                            @endif
                            <!-- Cài đặt hệ thống (chỉ hiển thị nếu Admin có quyền) -->
                            @if(auth()->user()->hasPermission('manage_settings'))
                            <a href="{{ route('admin.settings.index') }}" class="flex items-center gap-2.5 px-4 py-2 text-xs font-semibold text-gray-700 dark:text-slate-300 hover:bg-gray-50 dark:hover:bg-slate-800 hover:text-shopee dark:hover:text-white transition-all">
                                <i data-lucide="settings" class="w-4 h-4 text-gray-400 dark:text-slate-500"></i>
                                {{ __('Cài đặt hệ thống') }}
                            </a>
                            @endif
                        </div>

                        <!-- Đường phân cách ngang -->
                        <div class="border-t border-gray-100 dark:border-slate-800 my-1"></div>

                        <!-- Nút Đăng xuất tài khoản -->
                        <div class="px-1 py-0.5">
                            <form action="{{ route('logout') }}" method="POST">
                                @csrf
                                <button type="submit" class="w-full flex items-center gap-2.5 px-3 py-2 text-xs font-bold text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-950/20 rounded-lg transition-all text-left">
                                    <i data-lucide="log-out" class="w-4 h-4 text-red-500 dark:text-red-400"></i>
                                    {{ __('Đăng xuất') }}
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="flex-grow p-6">
            @yield('content')
        </main>

        <!-- Chân trang (Footer) của Admin Panel - Hiển thị bản quyền, phiên bản hệ thống và link hỗ trợ -->
        <footer class="mt-auto px-6 py-4 bg-white dark:bg-slate-900 border-t border-gray-200 dark:border-slate-800 text-gray-500 dark:text-slate-450 text-xs transition-colors duration-300 shrink-0">
            <div class="flex flex-col sm:flex-row items-center justify-between gap-3">
                <div class="flex items-center gap-2 text-center sm:text-left">
                    <span>Developer by <a href="https://www.cmsnt.co/?utm_source={{ request()->getHost() }}" target="_blank" rel="noopener" class="font-semibold hover:text-shopee transition-colors">CMSNT.CO</a>.</span>
                </div>
                <div class="flex flex-wrap items-center gap-3 sm:gap-4 shrink-0 justify-center sm:justify-end">
                    {{-- Thêm các nút truy cập nhóm hỗ trợ Zalo và kênh thông báo Telegram theo yêu cầu của Quản trị viên --}}
                    <a href="https://zalo.me/g/idapcx933" target="_blank" rel="noopener" class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-lg bg-blue-50 hover:bg-blue-100 text-blue-600 dark:bg-blue-950/20 dark:text-blue-400 border border-blue-100 dark:border-blue-900/30 transition-colors font-semibold text-[10px]">
                        <i data-lucide="message-circle" class="w-3.5 h-3.5 text-blue-500"></i>
                        {{ __('Nhóm Zalo') }}
                    </a>
                    <a href="https://t.me/cmsntco" target="_blank" rel="noopener" class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-lg bg-sky-50 hover:bg-sky-100 text-sky-600 dark:bg-sky-950/20 dark:text-sky-400 border border-sky-100 dark:border-sky-900/30 transition-colors font-semibold text-[10px]">
                        <i data-lucide="send" class="w-3.5 h-3.5 text-sky-500"></i>
                        {{ __('Nhóm Telegram') }}
                    </a>
                    <a href="https://www.cmsnt.co/p/contact.html" target="_blank" rel="noopener" class="flex items-center gap-1 hover:text-shopee transition-colors font-medium">
                        <i data-lucide="help-circle" class="w-3.5 h-3.5"></i>
                        {{ __('Liên hệ hỗ trợ') }}
                    </a>
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-shopee/10 text-shopee font-medium text-[10px] border border-shopee/20 dark:bg-shopee/20 dark:text-shopee-light">
                        <span class="w-1.5 h-1.5 bg-shopee rounded-full animate-pulse"></span>
                        v{{ ltrim(\App\Models\Setting::getVal('current_version', '1.0.0'), 'v') }}
                    </span>
                </div>
            </div>
        </footer>

        <!-- NÚT QUAY LẠI ĐẦU TRANG (BACK TO TOP) -->
        <!-- Nghiệp vụ: Nút nổi cố định ở góc dưới bên phải vùng nội dung chính.
             Đặt bên trong main container để tránh bị ẩn bởi cấu trúc flex h-full của body.
             JS thuần sẽ lắng nghe sự kiện scroll trên cả window và documentElement để đảm bảo bắt được cuộn trang. -->
        <!-- Nút Back-to-Top được đẩy lên cao hơn trên mobile (bottom-20) để tránh bị menu bottom (h-16) che mất -->
        <button
            id="back-to-top"
            class="fixed bottom-20 md:bottom-6 right-6 z-40 p-3 rounded-full bg-shopee text-white shadow-lg shadow-shopee/25 hover:bg-shopee-dark hover:shadow-shopee/40 hover:scale-110 active:scale-95 transition-all duration-300 transform translate-y-4 opacity-0 pointer-events-none"
            title="Quay lại đầu trang">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="m18 15-6-6-6 6" />
            </svg>
        </button>
    </div>

    <!-- 3. GLOBAL TOAST SYSTEM -->
    @include('components.toast.manager')

    <!-- Hiển thị thông báo Toast từ Session (đặt ngoài thẻ script chính để IDE formatter không phá cú pháp Blade ->) -->
    @if(session('success'))
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            window.dispatchEvent(new CustomEvent('toast', {
                detail: {
                    text: @json(session('success')),
                    type: 'success'
                }
            }));
        });
    </script>
    @endif

    @if(session('error'))
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            window.dispatchEvent(new CustomEvent('toast', {
                detail: {
                    text: @json(session('error')),
                    type: 'error'
                }
            }));
        });
    </script>
    @endif

    @if($errors->any())
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            window.dispatchEvent(new CustomEvent('toast', {
                detail: {
                    text: @json($errors->first()),
                    type: 'error'
                }
            }));
        });
    </script>
    @endif

    <!-- Menu bottom điều hướng nhanh trên thiết bị di động (Mobile Bottom Navigation) -->
    @php
        // Khởi tạo danh sách các tab menu bottom trên mobile dựa theo quyền hạn của tài khoản Admin hiện tại
        $bottomMenuItems = [];
        
        // Tab 1: Trang tổng quan hệ thống (Dashboard)
        $bottomMenuItems[] = [
            'route' => 'admin.dashboard',
            'icon' => 'layout-dashboard',
            'title' => __('Tổng quan'),
            'active' => request()->routeIs('admin.dashboard')
        ];
        
        // Tab 2: Quản lý thành viên (chỉ hiện khi có quyền)
        if(auth()->user()->hasPermission('manage_users')) {
            $bottomMenuItems[] = [
                'route' => 'admin.users.index',
                'icon' => 'users',
                'title' => __('Thành viên'),
                'active' => request()->routeIs('admin.users.*')
            ];
        }
        
        // Tab 3: Đơn hàng hoàn tiền (chỉ hiện khi có quyền)
        if(auth()->user()->hasPermission('manage_cashbacks')) {
            $bottomMenuItems[] = [
                'route' => 'admin.cashback.index',
                'icon' => 'receipt',
                'title' => __('Đơn hàng'),
                'active' => request()->routeIs('admin.cashback.*')
            ];
        }
        
        // Tab 4: Yêu cầu rút tiền (chỉ hiện khi có quyền)
        if(auth()->user()->hasPermission('manage_withdrawals')) {
            $bottomMenuItems[] = [
                'route' => 'admin.withdrawals.index',
                'icon' => 'wallet',
                'title' => __('Rút tiền'),
                'active' => request()->routeIs('admin.withdrawals.*'),
                'badge' => \App\Models\Withdrawal::where('status', 'pending')->count()
            ];
        }
        
        // Tab 5: Cài đặt hệ thống (chỉ hiện khi có quyền)
        if(auth()->user()->hasPermission('manage_settings')) {
            $bottomMenuItems[] = [
                'route' => 'admin.settings.index',
                'icon' => 'settings',
                'title' => __('Cài đặt'),
                'active' => request()->routeIs('admin.settings.*')
            ];
        }
        
        // Tính toán số lượng cột hiển thị thực tế
        $totalCols = count($bottomMenuItems);
    @endphp

    @if($totalCols > 0)
    <div class="fixed bottom-0 left-0 right-0 z-50 md:hidden bg-white/90 dark:bg-slate-900/90 backdrop-blur-lg border-t border-gray-200 dark:border-slate-800 shadow-[0_-4px_20px_rgba(0,0,0,0.05)] dark:shadow-[0_-4px_30px_rgba(0,0,0,0.3)] transition-colors duration-300">
        <!-- Sử dụng grid-cols động dựa theo số lượng item thực tế được phép hiển thị -->
        <div class="grid h-16" style="grid-template-columns: repeat({{ $totalCols }}, minmax(0, 1fr));">
            @foreach($bottomMenuItems as $item)
                <a href="{{ route($item['route']) }}" class="relative flex flex-col items-center justify-center gap-1 group transition-colors {{ $item['active'] ? 'text-shopee' : 'text-gray-500 dark:text-slate-400' }}">
                    <!-- Hiệu ứng micro-interaction nhẹ khi click hoặc active tab -->
                    <div class="p-1 rounded-xl transition-all duration-200 group-active:scale-90 {{ $item['active'] ? 'bg-shopee/10 text-shopee' : 'group-hover:text-shopee' }}">
                        <i data-lucide="{{ $item['icon'] }}" class="w-5 h-5"></i>
                    </div>
                    <span class="text-[9px] font-bold tracking-tight">{{ $item['title'] }}</span>
                    
                    <!-- Badge số lượng yêu cầu cần xử lý (ví dụ: yêu cầu rút tiền chờ duyệt) -->
                    @if(isset($item['badge']) && $item['badge'] > 0)
                        <span class="absolute top-2 right-4 inline-flex items-center justify-center px-1.5 py-0.5 text-[8px] font-black leading-none text-white bg-red-600 rounded-full animate-pulse">
                            {{ $item['badge'] }}
                        </span>
                    @endif
                </a>
            @endforeach
        </div>
    </div>
    @endif

    <!-- Admin Layout JS - Logic chuyển trang, prefetch, back-to-top (tách riêng để IDE formatter không phá cú pháp) -->
    <script src="{{ asset('js/admin-layout.js') }}?v=1.0.4"></script>

    <!-- Flyout hiển thị tên menu (và menu con nếu có) khi rê chuột vào icon lúc sidebar thu gọn -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const sidebar = document.getElementById('admin-sidebar');
            if (!sidebar || sidebar.dataset.flyoutReady) return;
            sidebar.dataset.flyoutReady = '1';

            // Panel flyout dùng chung, gắn vào body để không bị overflow của nav cắt mất
            const flyout = document.createElement('div');
            flyout.className = 'admin-sidebar-flyout';
            document.body.appendChild(flyout);

            let hideTimer = null;

            // Chỉ hiển thị khi sidebar đang thu gọn và ở chế độ desktop
            const canShow = () => document.body.classList.contains('sidebar-collapsed') && window.innerWidth >= 768;

            // Lấy nhãn chữ của mục menu (bỏ qua badge số lượng & mũi tên xổ xuống)
            const getLabel = (el) => {
                const span = el.querySelector(':scope > span');
                return (span ? span.textContent : el.textContent).trim();
            };

            // Hiển thị flyout cho phần tử trigger; submenuPanel (nếu có) chứa các link con
            const show = (triggerEl, submenuPanel) => {
                if (!canShow()) return;
                clearTimeout(hideTimer);
                flyout.innerHTML = '';

                const title = document.createElement('div');
                title.className = 'admin-sidebar-flyout__title';
                title.textContent = getLabel(triggerEl);
                flyout.appendChild(title);

                if (submenuPanel) {
                    flyout.classList.add('has-list');
                    const list = document.createElement('div');
                    list.className = 'admin-sidebar-flyout__list';
                    submenuPanel.querySelectorAll(':scope > a').forEach((a) => {
                        const item = document.createElement('a');
                        item.href = a.getAttribute('href');
                        item.className = 'admin-sidebar-flyout__item' + (a.className.includes('bg-shopee') ? ' is-active' : '');
                        item.textContent = a.textContent.trim();
                        list.appendChild(item);
                    });
                    flyout.appendChild(list);
                } else {
                    flyout.classList.remove('has-list');
                }

                // Định vị bên phải icon, kẹp lại để không tràn khỏi đáy màn hình
                const rect = triggerEl.getBoundingClientRect();
                flyout.style.left = (rect.right + 12) + 'px';
                flyout.classList.add('is-visible');
                const fh = flyout.offsetHeight;
                let top = rect.top;
                if (top + fh > window.innerHeight - 8) {
                    top = Math.max(8, window.innerHeight - 8 - fh);
                }
                flyout.style.top = top + 'px';
            };

            const scheduleHide = () => {
                hideTimer = setTimeout(() => flyout.classList.remove('is-visible'), 150);
            };

            // Giữ flyout mở khi chuột di chuyển vào trong nó (cho menu con)
            flyout.addEventListener('mouseenter', () => clearTimeout(hideTimer));
            flyout.addEventListener('mouseleave', scheduleHide);

            sidebar.querySelectorAll(':scope nav > a, :scope nav > div').forEach((node) => {
                if (node.tagName === 'A') {
                    node.addEventListener('mouseenter', () => show(node, null));
                    node.addEventListener('mouseleave', scheduleHide);
                    return;
                }
                // node là <div>: có thể là wrapper menu con (button + panel) hoặc đường kẻ phân cách
                const btn = node.querySelector(':scope > button');
                const panel = node.querySelector(':scope > div');
                if (btn && panel) {
                    btn.addEventListener('mouseenter', () => show(btn, panel));
                    btn.addEventListener('mouseleave', scheduleHide);
                }
            });
        });
    </script>

    <!-- Global Icon Picker cho tất cả các trang Admin -->
    @include('admin.components.icon_picker')

    <!-- Component tìm kiếm nhanh Spotlight Search toàn cục -->
    @include('admin.components.search_spotlight')

    @yield('scripts')
</body>

</html>