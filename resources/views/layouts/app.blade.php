<!DOCTYPE html>
<html lang="{{ $currentLocale ?? app()->getLocale() }}" class="h-full bg-gray-50" style="--theme-color: {{ $themeColor }}">

<head><meta charset="utf-8">
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
    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>
    
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="{{ $themeColor }}">

    <title>@yield('title', $siteName)</title>
    <meta name="description" content="@yield('meta_description', $siteDescription)">

    <!-- Canonical URL giúp tránh trùng lặp nội dung cho SEO -->
    <link rel="canonical" href="@yield('canonical_url', request()->url())">
    @yield('meta_robots')

    <!-- Favicon -->
    @php
        $versionLocalAsset = static function (?string $url): ?string {
            if (empty($url)) {
                return $url;
            }

            $path = parse_url($url, PHP_URL_PATH);
            $localPath = $path ? public_path(ltrim($path, '/')) : null;

            if (!$localPath || !is_file($localPath)) {
                return $url;
            }

            return $url . (str_contains($url, '?') ? '&' : '?') . 'v=' . filemtime($localPath);
        };
        $siteFaviconUrl = $versionLocalAsset($siteFavicon ?? null);
        $siteLogoUrl = $versionLocalAsset($siteLogo ?? null);
        $siteLogoDarkUrl = $versionLocalAsset($siteLogoDark ?? null);
        $siteOgImageUrl = $versionLocalAsset($siteOgImage ?? null);
    @endphp
    @if(!empty($siteFavicon))
    <link rel="icon" href="{{ $siteFaviconUrl }}">
    <link rel="shortcut icon" href="{{ $siteFaviconUrl }}">
    <link rel="apple-touch-icon" href="{{ $siteFaviconUrl }}">
    <link rel="manifest" href="{{ route('manifest.json') }}?v=1.0.4">
    @endif

    <!-- Cấu hình PWA tối ưu cho thiết bị iOS Safari (Giao diện standalone và thanh trạng thái) -->
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="{{ $siteName }}">

    <!-- Open Graph / Facebook / Zalo -->
    <meta property="og:title" content="@yield('og_title', $siteName)">
    <meta property="og:description" content="@yield('meta_description', $siteDescription)">
    <meta property="og:type" content="@yield('og_type', 'website')">
    <meta property="og:url" content="@yield('canonical_url', request()->url())">
    @php
        $pageOgImage = trim($__env->yieldContent('og_image', $siteOgImageUrl ?? ''));
        $pageOgImageAlt = trim($__env->yieldContent('og_image_alt', $__env->yieldContent('og_title', $siteName ?? '')));
    @endphp
    @if(!empty($pageOgImage))
    <meta property="og:image" content="{{ $pageOgImage }}">
    <meta property="og:image:secure_url" content="{{ $pageOgImage }}">
    @php
        $pageOgImageExtension = strtolower(pathinfo(parse_url($pageOgImage, PHP_URL_PATH) ?? '', PATHINFO_EXTENSION));
        $pageOgImageType = match ($pageOgImageExtension) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'webp' => 'image/webp',
            'gif' => 'image/gif',
            default => null,
        };
    @endphp
    @if($pageOgImageType)
    <meta property="og:image:type" content="{{ $pageOgImageType }}">
    @endif
    <meta property="og:image:alt" content="{{ $pageOgImageAlt }}">
    @endif
    <meta property="og:site_name" content="{{ $siteName }}">
    @php
        $ogLocaleMap = ['vi' => 'vi_VN', 'en' => 'en_US', 'zh' => 'zh_CN', 'ko' => 'ko_KR', 'ja' => 'ja_JP', 'th' => 'th_TH'];
        $ogLocaleCode = $currentLocale ?? app()->getLocale();
    @endphp
    <meta property="og:locale" content="{{ $ogLocaleMap[$ogLocaleCode] ?? 'vi_VN' }}">

    <!-- Twitter Card: hiển thị đẹp khi chia sẻ lên X/Twitter -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="@yield('og_title', $siteName)">
    <meta name="twitter:description" content="@yield('meta_description', $siteDescription)">
    @if(!empty($pageOgImage))
    <meta name="twitter:image" content="{{ $pageOgImage }}">
    <meta name="twitter:image:alt" content="{{ $pageOgImageAlt }}">
    @endif

    <!-- SEO Schema / JSON-LD cho phép child view inject dữ liệu có cấu trúc -->
    @yield('seo_schema')

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
                        'lg': '0.375rem',  // Giảm từ 8px xuống 6px
                        'xl': '0.5rem',    // Giảm từ 12px xuống 8px
                        '2xl': '0.75rem',  // Giảm từ 16px xuống 12px
                        '3xl': '1rem',     // Giảm từ 24px xuống 16px
                    }
                }
            }
        }
    </script>

    <!-- AlpineJS Collapse Plugin (Local Asset) -->
    <script defer src="{{ asset('vendor/alpinejs/collapse.min.js') }}"></script>

    <!-- AlpineJS (Local Asset) -->
    <script defer src="{{ asset('vendor/alpinejs/alpine.min.js') }}"></script>

    <!-- Axios (Local Asset) -->
    <script src="{{ asset('vendor/axios/axios.min.js') }}"></script>

    <!-- Lucide Icons (Local Asset) -->
    <script src="{{ asset('vendor/lucide/lucide.min.js') }}"></script>

    <!-- NProgress Loading Bar (Local Asset) -->
    <link rel="stylesheet" href="{{ asset('vendor/nprogress/nprogress.min.css') }}">
    <script src="{{ asset('vendor/nprogress/nprogress.min.js') }}"></script>

    <!-- Custom global styles and behaviors -->
    <link href="{{ asset('css/app-custom.css') }}?v={{ filemtime(public_path('css/app-custom.css')) }}" rel="stylesheet">

    <!-- Custom Bubbles & Dark Mode CSS -->
    @if(request()->routeIs('home') && \App\Models\Setting::getVal('hp_enable_bubble_effect', '1') === '1')
    <link href="{{ asset('css/bubbles.css') }}" rel="stylesheet">
    @endif
    <link href="{{ asset('css/darkmode.css') }}" rel="stylesheet">
    <!-- Cloudflare Turnstile Captcha Integration -->
    @if(\App\Models\Setting::getVal('turnstile_status', '0') === '1')
    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
    @endif

    <!-- Custom CSS & JS Header tùy biến từ Admin Panel -->
    @php
        $customCss = \App\Models\Setting::getVal('custom_css');
        $customJsHeader = \App\Models\Setting::getVal('custom_js_header');
        $customJsBody = \App\Models\Setting::getVal('custom_js_body');
    @endphp
    @if(!empty($customCss))
    <style>
        {!! $customCss !!}
    </style>
    @endif
    @if(!empty($customJsHeader))
        {!! $customJsHeader !!}
    @endif

    @yield('styles')
</head>

<body class="flex flex-col min-h-screen text-gray-800 relative overflow-x-hidden" x-data="{ mobileMenuOpen: false, mobileDashboardMenuOpen: false }">
    @if(session()->has('admin_impersonator_id'))
    <div class="bg-gradient-to-r from-red-600 to-orange-500 text-white py-2 px-4 relative z-50 shadow-sm border-b border-red-700/30">
        <div class="max-w-7xl mx-auto flex items-center justify-between flex-wrap gap-2 text-xs md:text-sm font-medium">
            <div class="flex items-center gap-2">
                <i data-lucide="shield-alert" class="w-4 h-4 md:w-5 h-5 text-white animate-pulse"></i>
                <span>{!! __('Bạn đang đăng nhập giả lập bằng tài khoản <strong>:name</strong>.', ['name' => e(auth()->user()->name)]) !!}</span>
            </div>
            <form action="{{ route('admin.users.return_to_admin') }}" method="POST" class="inline">
                @csrf
                <button type="submit" class="inline-flex items-center gap-1.5 bg-white text-red-600 hover:bg-gray-50 px-3 py-1 rounded-md text-xs font-bold transition-all shadow-sm">
                    <i data-lucide="arrow-left-right" class="w-3.5 h-3.5"></i>
                    {{ __('Quay lại Admin') }}
                </button>
            </form>
        </div>
    </div>
    @endif
    <!-- Animated background bubbles -->
    <div class="fixed inset-0 overflow-hidden pointer-events-none z-0">
        <!-- Bubble 1: Cam Shopee -->
        <div class="absolute top-[-5%] left-[-5%] w-[350px] md:w-[600px] h-[350px] md:h-[600px] rounded-full bg-shopee/[0.12] dark:bg-shopee/[0.15] blur-[50px] md:blur-[90px] animate-blob"></div>
        <!-- Bubble 2: Tím neon -->
        <div class="absolute bottom-[5%] right-[-5%] w-[350px] md:w-[650px] h-[350px] md:h-[650px] rounded-full bg-purple-500/[0.08] dark:bg-purple-600/[0.12] blur-[60px] md:blur-[100px] animate-blob animation-delay-2000"></div>
        <!-- Bubble 3: Hồng neon -->
        <div class="absolute top-[35%] left-[15%] w-[300px] md:w-[500px] h-[300px] md:h-[500px] rounded-full bg-pink-500/[0.08] dark:bg-pink-600/[0.1] blur-[50px] md:blur-[80px] animate-blob animation-delay-4000"></div>
    </div>

    <!-- Floating bubbles animation -->
    @if(request()->routeIs('home') && \App\Models\Setting::getVal('hp_enable_bubble_effect', '1') === '1')
    <div class="bubbles">
        <div class="bubble" style="left: 8%; width: 45px; height: 45px; animation-delay: 0s; animation-duration: 16s;"></div>
        <div class="bubble" style="left: 20%; width: 25px; height: 25px; animation-delay: 3s; animation-duration: 11s;"></div>
        <div class="bubble" style="left: 35%; width: 55px; height: 55px; animation-delay: 6s; animation-duration: 20s;"></div>
        <div class="bubble" style="left: 48%; width: 30px; height: 30px; animation-delay: 1.5s; animation-duration: 14s;"></div>
        <div class="bubble" style="left: 60%; width: 50px; height: 50px; animation-delay: 8s; animation-duration: 18s;"></div>
        <div class="bubble" style="left: 72%; width: 20px; height: 20px; animation-delay: 4.5s; animation-duration: 9s;"></div>
        <div class="bubble" style="left: 85%; width: 40px; height: 40px; animation-delay: 2s; animation-duration: 15s;"></div>
        <div class="bubble" style="left: 15%; width: 35px; height: 35px; animation-delay: 9s; animation-duration: 13s;"></div>
        <div class="bubble" style="left: 53%; width: 28px; height: 28px; animation-delay: 11s; animation-duration: 12s;"></div>
        <div class="bubble" style="left: 90%; width: 42px; height: 42px; animation-delay: 7s; animation-duration: 17s;"></div>
    </div>
    @endif

    <!-- 1. HEADER / NAVIGATION -->
    @if(!session('from_app'))
    <header class="sticky top-0 z-20 bg-white/70 dark:bg-slate-900/75 backdrop-blur-md border-b border-gray-100/80 dark:border-slate-800/80 shadow-sm transition-all duration-300">
        <div class="px-4 mx-auto max-w-7xl sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <!-- Logo (Đã được điều chỉnh tăng kích thước từ h-8 sm:h-10 lên h-10 sm:h-12 để hiển thị rõ nét và to hơn) -->
                <div class="flex items-center">
                    <a href="{{ route('home') }}" class="flex items-center gap-2">
                        @if(!empty($siteLogo) || !empty($siteLogoDark))
                        @if(!empty($siteLogo))
                        <img src="{{ $siteLogoUrl }}" alt="Mê Sale" class="h-10 sm:h-12 w-auto object-contain {{ !empty($siteLogoDark) ? 'dark:hidden' : '' }}">
                        @endif
                        @if(!empty($siteLogoDark))
                        <img src="{{ $siteLogoDarkUrl }}" alt="Mê Sale" class="h-10 sm:h-12 w-auto object-contain hidden dark:block">
                        @endif
                        @else
                        <div class="flex items-center justify-center w-11 h-11 text-white rounded-xl bg-gradient-to-tr from-shopee to-shopee-light shadow-md shadow-shopee/20">
                            <i data-lucide="shopping-bag" class="w-5.5 h-5.5"></i>
                        </div>
                        <span class="text-2xl font-bold tracking-tight text-transparent bg-clip-text bg-gradient-to-r from-shopee to-shopee-light">
                            {{ $siteName }}
                        </span>
                        @endif
                    </a>
                </div>

                <!-- Desktop Menu (Glassmorphism Pill Style) -->
                @php
                    // Hàm phụ trợ tự động gán icon Lucide phù hợp theo tên hoặc URL của menu
                    $getIcon = function($url, $title) {
                        $url = strtolower($url);
                        $title = strtolower($title);
                        if (str_contains($url, 'home') || $url === '/' || $url === '') return 'home';
                        if (str_contains($url, 'cashback') || str_contains($title, 'đơn hàng')) return 'shopping-bag';
                        if (str_contains($url, 'checkin') || str_contains($title, 'điểm danh')) return 'calendar';
                        if (str_contains($url, 'referrals') || str_contains($title, 'giới thiệu') || str_contains($title, 'tiếp thị')) return 'users';
                        if (str_contains($url, 'withdraw') || str_contains($title, 'rút tiền')) return 'wallet';
                        if (str_contains($url, 'blog') || str_contains($title, 'blog') || str_contains($title, 'tin tức')) return 'book-open';
                        if (str_contains($url, 'profile') || str_contains($title, 'hồ sơ') || str_contains($title, 'tài khoản')) return 'user';
                        if (str_contains($url, 'contact') || str_contains($title, 'liên hệ')) return 'mail';
                        return 'link'; // icon mặc định
                    };
                @endphp
                <nav class="hidden md:flex items-center gap-1 bg-gray-50/50 dark:bg-slate-800/40 p-1 rounded-2xl border border-gray-100/50 dark:border-slate-800/50">
                    @if(isset($groupedMenus) && $groupedMenus->has('header'))
                        @foreach($groupedMenus['header'] as $hMenu)
                            @php
                                $showMenu = false;
                                if ($hMenu->auth_rule === 'all') {
                                    $showMenu = true;
                                } elseif ($hMenu->auth_rule === 'auth' && auth()->check()) {
                                    $showMenu = true;
                                } elseif ($hMenu->auth_rule === 'guest' && auth()->guest()) {
                                    $showMenu = true;
                                }
                            @endphp
                            @if($showMenu)
                                @php
                                    $isActive = request()->url() === url($hMenu->url) || (request()->path() === ltrim($hMenu->url, '/'));
                                @endphp
                                <a href="{{ $hMenu->url ? url($hMenu->url) : '#' }}" 
                                   target="{{ $hMenu->target }}"
                                   class="flex items-center gap-1.5 px-4 py-2 text-sm font-semibold rounded-xl transition-all duration-200 {{ $isActive ? 'text-shopee dark:text-shopee-light bg-white dark:bg-slate-800 shadow-sm border border-gray-100/80 dark:border-slate-700/50' : 'text-gray-600 dark:text-slate-300 hover:text-shopee dark:hover:text-shopee-light hover:bg-gray-100/80 dark:hover:bg-slate-800/50' }}">
                                    @if(!empty($hMenu->icon))
                                        <i data-lucide="{{ $hMenu->icon }}" class="w-4 h-4"></i>
                                    @endif
                                    <span>{{ __($hMenu->title) }}</span>
                                </a>
                            @endif
                        @endforeach
                    @else
                        <!-- Phương án dự phòng (Fallback) nếu chưa cấu hình menu trong database -->
                        <a href="{{ route('home') }}" class="flex items-center gap-1.5 px-4 py-2 text-sm font-semibold rounded-xl transition-all duration-200 {{ request()->routeIs('home') ? 'text-shopee dark:text-shopee-light bg-white dark:bg-slate-800 shadow-sm border border-gray-100/80 dark:border-slate-700/50' : 'text-gray-600 dark:text-slate-300 hover:text-shopee dark:hover:text-shopee-light hover:bg-gray-100/80 dark:hover:bg-slate-800/50' }}">
                            <i data-lucide="home" class="w-4 h-4"></i>
                            <span>{{ __('Trang chủ') }}</span>
                        </a>
                        @auth
                        <a href="{{ route('cashback.history') }}" class="flex items-center gap-1.5 px-4 py-2 text-sm font-semibold rounded-xl transition-all duration-200 {{ request()->routeIs('cashback.history') ? 'text-shopee dark:text-shopee-light bg-white dark:bg-slate-800 shadow-sm border border-gray-100/80 dark:border-slate-700/50' : 'text-gray-600 dark:text-slate-300 hover:text-shopee dark:hover:text-shopee-light hover:bg-gray-100/80 dark:hover:bg-slate-800/50' }}">
                            <i data-lucide="shopping-bag" class="w-4 h-4"></i>
                            <span>{{ __('Đơn hàng') }}</span>
                        </a>
                        @if(\App\Models\Setting::getVal('daily_checkin_enabled', '1') === '1')
                        <a href="{{ route('checkin') }}" class="flex items-center gap-1.5 px-4 py-2 text-sm font-semibold rounded-xl transition-all duration-200 {{ request()->routeIs('checkin') ? 'text-shopee dark:text-shopee-light bg-white dark:bg-slate-800 shadow-sm border border-gray-100/80 dark:border-slate-700/50' : 'text-gray-600 dark:text-slate-300 hover:text-shopee dark:hover:text-shopee-light hover:bg-gray-100/80 dark:hover:bg-slate-800/50' }}">
                            <i data-lucide="calendar" class="w-4 h-4"></i>
                            <span>{{ __('Điểm danh') }}</span>
                        </a>
                        @endif
                        @if(\App\Models\Setting::getVal('referral_enabled', '1') === '1')
                        <a href="{{ route('referrals') }}" class="flex items-center gap-1.5 px-4 py-2 text-sm font-semibold rounded-xl transition-all duration-200 {{ request()->routeIs('referrals') ? 'text-shopee dark:text-shopee-light bg-white dark:bg-slate-800 shadow-sm border border-gray-100/80 dark:border-slate-700/50' : 'text-gray-600 dark:text-slate-300 hover:text-shopee dark:hover:text-shopee-light hover:bg-gray-100/80 dark:hover:bg-slate-800/50' }}">
                            <i data-lucide="users" class="w-4 h-4"></i>
                            <span>{{ __('Giới thiệu') }}</span>
                        </a>
                        @endif
                        @if(\App\Models\Setting::getVal('withdrawal_enabled', '1') === '1')
                        <a href="{{ route('withdraw') }}" class="flex items-center gap-1.5 px-4 py-2 text-sm font-semibold rounded-xl transition-all duration-200 {{ request()->routeIs('withdraw') ? 'text-shopee dark:text-shopee-light bg-white dark:bg-slate-800 shadow-sm border border-gray-100/80 dark:border-slate-700/50' : 'text-gray-600 dark:text-slate-300 hover:text-shopee dark:hover:text-shopee-light hover:bg-gray-100/80 dark:hover:bg-slate-800/50' }}">
                            <i data-lucide="wallet" class="w-4 h-4"></i>
                            <span>{{ __('Rút tiền') }}</span>
                        </a>
                        @endif
                        @endauth
                        @if(\App\Models\Setting::getVal('ranking_status', '1') == '1')
                        <a href="{{ route('ranking.index') }}" class="flex items-center gap-1.5 px-4 py-2 text-sm font-semibold rounded-xl transition-all duration-200 {{ request()->routeIs('ranking.index') ? 'text-shopee dark:text-shopee-light bg-white dark:bg-slate-800 shadow-sm border border-gray-100/80 dark:border-slate-700/50' : 'text-gray-600 dark:text-slate-300 hover:text-shopee dark:hover:text-shopee-light hover:bg-gray-100/80 dark:hover:bg-slate-800/50' }}">
                            <i data-lucide="trophy" class="w-4 h-4"></i>
                            <span>{{ __('Bảng xếp hạng') }}</span>
                        </a>
                        @endif
                        @if(\App\Models\BotConfig::where('is_enabled', true)->exists())
                        <a href="{{ route('bot.guide') }}" class="flex items-center gap-1.5 px-4 py-2 text-sm font-semibold rounded-xl transition-all duration-200 {{ request()->routeIs('bot.guide') ? 'text-shopee dark:text-shopee-light bg-white dark:bg-slate-800 shadow-sm border border-gray-100/80 dark:border-slate-700/50' : 'text-gray-600 dark:text-slate-300 hover:text-shopee dark:hover:text-shopee-light hover:bg-gray-100/80 dark:hover:bg-slate-800/50' }}">
                            <i data-lucide="bot" class="w-4 h-4"></i>
                            <span>{{ __('Bot hoàn tiền') }}</span>
                        </a>
                        @endif
                        @if(\App\Models\Setting::getVal('coupon_status', '1') == '1')
                        <a href="{{ route('coupons.index') }}" class="flex items-center gap-1.5 px-4 py-2 text-sm font-semibold rounded-xl transition-all duration-200 {{ request()->routeIs('coupons.index') ? 'text-shopee dark:text-shopee-light bg-white dark:bg-slate-800 shadow-sm border border-gray-100/80 dark:border-slate-700/50' : 'text-gray-600 dark:text-slate-300 hover:text-shopee dark:hover:text-shopee-light hover:bg-gray-100/80 dark:hover:bg-slate-800/50' }}">
                            <i data-lucide="ticket" class="w-4 h-4"></i>
                            <span>{{ __('Mã giảm giá') }}</span>
                        </a>
                        @endif
                        @if($blogEnabled == '1')
                        <a href="/blog" class="flex items-center gap-1.5 px-4 py-2 text-sm font-semibold rounded-xl transition-all duration-200 {{ request()->is('blog*') ? 'text-shopee dark:text-shopee-light bg-white dark:bg-slate-800 shadow-sm border border-gray-100/80 dark:border-slate-700/50' : 'text-gray-600 dark:text-slate-300 hover:text-shopee dark:hover:text-shopee-light hover:bg-gray-100/80 dark:hover:bg-slate-800/50' }}">
                            <i data-lucide="book-open" class="w-4 h-4"></i>
                            <span>{{ __('Blog') }}</span>
                        </a>
                        @endif
                    @endif
                </nav>

                <!-- Authentication / Dropdown -->
                <div class="hidden md:flex md:items-center md:gap-3">
                    <!-- Spotlight Search Button (Desktop) -->
                    <button @click="$dispatch('open-search')" 
                            class="w-10 h-10 bg-gray-50/80 dark:bg-slate-800/40 hover:bg-gray-100 dark:hover:bg-slate-800/80 border border-gray-100 dark:border-slate-800/60 text-gray-500 dark:text-slate-300 hover:text-shopee dark:hover:text-shopee-light rounded-xl flex items-center justify-center transition-all duration-200"
                            title="{{ __('Tìm kiếm nhanh (Ctrl+K)') }}">
                        <i data-lucide="search" class="w-5 h-5"></i>
                    </button>

                    <!-- Dark Mode Toggle Button -->
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
                        class="w-10 h-10 bg-gray-50/80 dark:bg-slate-800/40 hover:bg-gray-100 dark:hover:bg-slate-800/80 border border-gray-100 dark:border-slate-800/60 text-gray-500 dark:text-slate-300 hover:text-shopee dark:hover:text-yellow-400 rounded-xl flex items-center justify-center transition-all duration-200"
                        title="{{ __('Chuyển chế độ sáng/tối') }}">
                        <svg x-show="!isDark" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                        </svg>
                        <svg x-show="isDark" class="w-5 h-5 text-yellow-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" x-cloak>
                            <circle cx="12" cy="12" r="5" stroke-linecap="round" stroke-linejoin="round" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42" />
                        </svg>
                    </button>

                    @guest
                    <a href="{{ route('login') }}" class="text-sm font-semibold text-gray-600 dark:text-slate-300 hover:text-shopee dark:hover:text-shopee-light px-3 py-2 rounded-xl hover:bg-gray-50 dark:hover:bg-slate-800/40 transition-all duration-200">{{ __('Đăng nhập') }}</a>
                    @if(\App\Models\Setting::getVal('registration_enabled', '1') === '1')
                    <a href="{{ route('register') }}" class="inline-flex items-center justify-center px-4 py-2 text-sm font-semibold text-white transition-all bg-gradient-to-r from-shopee to-shopee-light hover:brightness-110 rounded-xl shadow-md shadow-shopee/10">
                        {{ __('Đăng ký ngay') }}
                    </a>
                    @endif
                    @else
                    <!-- Notifications link -->
                    <a href="{{ route('notifications') }}" class="w-10 h-10 bg-gray-50/80 dark:bg-slate-800/40 hover:bg-gray-100 dark:hover:bg-slate-800/80 border border-gray-100 dark:border-slate-800/60 text-gray-500 dark:text-slate-300 hover:text-shopee dark:hover:text-shopee-light rounded-xl flex items-center justify-center relative transition-all duration-200">
                        <i data-lucide="bell" class="w-5 h-5"></i>
                        @php
                        $unreadCount = \App\Models\Notification::where('user_id', auth()->id())->where('is_read', false)->count();
                        @endphp
                        @if($unreadCount > 0)
                        <span class="absolute -top-1 -right-1 w-5 h-5 bg-red-500 text-[10px] font-bold text-white rounded-full flex items-center justify-center animate-pulse border-2 border-white dark:border-slate-900">
                            {{ $unreadCount }}
                        </span>
                        @endif
                    </a>

                    <!-- Dropdown User -->
                    <div class="relative" x-data="{ open: false }" @click.away="open = false">
                        <button @click="open = !open" class="flex items-center gap-2 p-1.5 rounded-xl hover:bg-gray-50 dark:hover:bg-slate-800/40 transition-all border border-transparent hover:border-gray-100 dark:hover:border-slate-800/60">
                            <div class="w-8 h-8 rounded-lg bg-gradient-to-tr from-shopee to-shopee-light text-white flex items-center justify-center font-bold text-sm shadow-md shadow-shopee/10">
                                {{ substr(auth()->user()->name, 0, 1) }}
                            </div>
                            <span class="text-sm font-semibold text-gray-700 dark:text-slate-200">{{ auth()->user()->name }}</span>
                            <i data-lucide="chevron-down" class="w-4 h-4 text-gray-400"></i>
                        </button>
                        <div x-show="open" x-transition x-cloak class="absolute right-0 mt-2 w-52 bg-white dark:bg-slate-900 border border-gray-100 dark:border-slate-800/80 rounded-2xl shadow-xl z-50 py-1.5">
                            <!-- Nút Trang quản trị luôn hiển thị cố định ở trên cùng cho tài khoản Admin -->
                            @if(auth()->user()->isAdmin() && \App\Models\Setting::getVal('show_admin_menu_on_frontend', '1') === '1')
                            <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-gray-700 dark:text-slate-300 hover:bg-gray-50 dark:hover:bg-slate-800/40 hover:text-shopee dark:hover:text-shopee-light">
                                <i data-lucide="shield-check" class="w-4 h-4 text-gray-400 dark:text-slate-500"></i>
                                {{ __('Trang quản trị') }}
                            </a>
                            @endif

                            @if(isset($groupedMenus) && $groupedMenus->has('user_dropdown') && $groupedMenus['user_dropdown']->isNotEmpty())
                                @foreach($groupedMenus['user_dropdown'] as $menuItem)
                                    @php
                                        // Kiểm tra điều kiện hiển thị theo đối tượng (auth_rule)
                                        $showMenu = false;
                                        if ($menuItem->auth_rule === 'all') {
                                            $showMenu = true;
                                        } elseif ($menuItem->auth_rule === 'auth' && auth()->check()) {
                                            $showMenu = true;
                                        } elseif ($menuItem->auth_rule === 'guest' && auth()->guest()) {
                                            $showMenu = true;
                                        }
                                        
                                        // Loại bỏ các link admin ở phần menu động để tránh bị hiển thị lặp
                                        if ($showMenu && (str_contains($menuItem->url, 'admin') || $menuItem->url === '/admin')) {
                                            $showMenu = false;
                                        }
                                    @endphp
                                    @if($showMenu)
                                        <a href="{{ str_starts_with($menuItem->url, 'http') ? $menuItem->url : url($menuItem->url) }}" 
                                           target="{{ $menuItem->target }}" 
                                           class="flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-gray-700 dark:text-slate-300 hover:bg-gray-50 dark:hover:bg-slate-800/40 hover:text-shopee dark:hover:text-shopee-light">
                                            @if($menuItem->icon)
                                                <i data-lucide="{{ $menuItem->icon }}" class="w-4 h-4 text-gray-400 dark:text-slate-500"></i>
                                            @elseif($menuItem->url === '/dashboard')
                                                <i data-lucide="layout-dashboard" class="w-4 h-4 text-gray-400 dark:text-slate-500"></i>
                                            @elseif($menuItem->url === '/dashboard/profile' || str_contains($menuItem->url, 'profile'))
                                                <i data-lucide="user" class="w-4 h-4 text-gray-400 dark:text-slate-500"></i>
                                            @else
                                                <i data-lucide="link" class="w-4 h-4 text-gray-400 dark:text-slate-500"></i>
                                            @endif
                                            {{ $menuItem->title }}
                                        </a>
                                    @endif
                                @endforeach
                            @else
                                <a href="{{ route('dashboard') }}" class="flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-gray-700 dark:text-slate-300 hover:bg-gray-50 dark:hover:bg-slate-800/40 hover:text-shopee dark:hover:text-shopee-light">
                                    <i data-lucide="layout-dashboard" class="w-4 h-4 text-gray-400 dark:text-slate-500"></i>
                                    {{ __('Ví của tôi') }}
                                </a>
                                <a href="{{ route('profile') }}" class="flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-gray-700 dark:text-slate-300 hover:bg-gray-50 dark:hover:bg-slate-800/40 hover:text-shopee dark:hover:text-shopee-light">
                                    <i data-lucide="user" class="w-4 h-4 text-gray-400 dark:text-slate-500"></i>
                                    {{ __('Cấu hình hồ sơ') }}
                                </a>
                            @endif
                            <div class="border-t border-gray-100 dark:border-slate-800 my-1"></div>
                            <form action="{{ route('logout') }}" method="POST" class="block w-full">
                                @csrf
                                <button type="submit" class="w-full flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-red-600 hover:bg-red-50 dark:hover:bg-red-950/20 text-left">
                                    <i data-lucide="log-out" class="w-4 h-4 text-red-400"></i>
                                    {{ __('Đăng xuất') }}
                                </button>
                            </form>
                        </div>
                    </div>
                    @endguest
                </div>

                <!-- Mobile Menu Button -->
                <div class="flex items-center md:hidden gap-2">
                    <!-- Spotlight Search Button (Mobile) -->
                    <button @click="$dispatch('open-search')" 
                            class="w-9 h-9 bg-gray-50/80 dark:bg-slate-800/40 text-gray-500 dark:text-slate-300 rounded-xl flex items-center justify-center border border-gray-100 dark:border-slate-800/60 transition-all duration-200"
                            title="{{ __('Tìm kiếm nhanh') }}">
                        <i data-lucide="search" class="w-4 h-4"></i>
                    </button>

                    <!-- Nút Dark mode trên mobile -->
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
                        class="w-9 h-9 bg-gray-50/80 dark:bg-slate-800/40 text-gray-500 dark:text-slate-300 rounded-xl flex items-center justify-center border border-gray-100 dark:border-slate-800/60 transition-all duration-200"
                        title="Chuyển chế độ sáng/tối">
                        <svg x-show="!isDark" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                        </svg>
                        <svg x-show="isDark" class="w-4 h-4 text-yellow-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" x-cloak>
                            <circle cx="12" cy="12" r="5" stroke-linecap="round" stroke-linejoin="round" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42" />
                        </svg>
                    </button>

                    <!-- Nút Notification trên mobile -->
                    @auth
                    <a href="{{ route('notifications') }}" class="w-9 h-9 bg-gray-50/80 dark:bg-slate-800/40 text-gray-500 dark:text-slate-300 rounded-xl flex items-center justify-center border border-gray-100 dark:border-slate-800/60 relative transition-all duration-200">
                        <i data-lucide="bell" class="w-4 h-4"></i>
                        @if($unreadCount > 0)
                        <span class="absolute top-1.5 right-1.5 w-1.5 h-1.5 bg-red-500 rounded-full animate-ping"></span>
                        <span class="absolute top-1.5 right-1.5 w-1.5 h-1.5 bg-red-500 rounded-full"></span>
                        @endif
                    </a>
                    @endauth

                    <!-- Nút Hamburger Menu chính -->
                    <button @click="mobileMenuOpen = !mobileMenuOpen" 
                            class="w-9 h-9 flex items-center justify-center text-gray-600 dark:text-slate-300 hover:text-shopee dark:hover:text-shopee-light bg-gray-50/80 dark:bg-slate-800/40 hover:bg-gray-100 dark:hover:bg-slate-800/80 border border-gray-100 dark:border-slate-800/60 rounded-xl transition-all duration-200 focus:outline-none relative">
                        <span class="sr-only">Open main menu</span>
                        <div class="w-4 h-3 flex flex-col justify-between items-center transition-all duration-300" :class="mobileMenuOpen ? 'rotate-180' : ''">
                            <span class="block h-0.5 w-4 bg-current rounded-full transition-transform duration-300 origin-center" :class="mobileMenuOpen ? 'rotate-45 translate-y-[5px]' : ''"></span>
                            <span class="block h-0.5 w-3 bg-current rounded-full transition-opacity duration-200" :class="mobileMenuOpen ? 'opacity-0' : ''"></span>
                            <span class="block h-0.5 w-4 bg-current rounded-full transition-transform duration-300 origin-center" :class="mobileMenuOpen ? '-rotate-45 -translate-y-[5px]' : ''"></span>
                        </div>
                    </button>
                </div>
            </div>
        </div>

        <!-- Mobile Menu Container (Slide-down drawer) -->
        <div x-show="mobileMenuOpen" 
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 -translate-y-4"
             x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 translate-y-0"
             x-transition:leave-end="opacity-0 -translate-y-4"
             x-cloak 
             class="md:hidden bg-white/95 dark:bg-slate-900/95 backdrop-blur-md border-b border-gray-100/80 dark:border-slate-800/80 shadow-xl px-5 py-3 absolute left-0 right-0 z-30">
            <div class="flex flex-col divide-y divide-gray-100 dark:divide-slate-800/60">
                <!-- Navigation Links -->
                @if(isset($groupedMenus) && $groupedMenus->has('header'))
                    @foreach($groupedMenus['header'] as $hMenu)
                        @php
                            $showMenu = false;
                            if ($hMenu->auth_rule === 'all') {
                                $showMenu = true;
                            } elseif ($hMenu->auth_rule === 'auth' && auth()->check()) {
                                $showMenu = true;
                            } elseif ($hMenu->auth_rule === 'guest' && auth()->guest()) {
                                $showMenu = true;
                            }
                        @endphp
                        @if($showMenu)
                            @php
                                $isActive = request()->url() === url($hMenu->url) || (request()->path() === ltrim($hMenu->url, '/'));
                            @endphp
                            <a href="{{ $hMenu->url ? url($hMenu->url) : '#' }}" 
                               target="{{ $hMenu->target }}"
                               class="flex items-center justify-between py-3.5 transition-all duration-200 group">
                                <div class="flex items-center gap-3">
                                    @if(!empty($hMenu->icon))
                                        <i data-lucide="{{ $hMenu->icon }}" class="w-5 h-5 {{ $isActive ? 'text-shopee dark:text-shopee-light font-bold' : 'text-gray-400 dark:text-slate-500' }}"></i>
                                    @endif
                                    <span class="text-sm font-semibold {{ $isActive ? 'text-shopee dark:text-shopee-light font-bold' : 'text-gray-700 dark:text-slate-300' }}">{{ __($hMenu->title) }}</span>
                                </div>
                                <i data-lucide="chevron-right" class="w-4 h-4 text-gray-300 dark:text-slate-600 group-hover:translate-x-0.5 transition-transform"></i>
                            </a>
                        @endif
                    @endforeach
                    @auth
                        @if(auth()->user()->isAdmin() && \App\Models\Setting::getVal('show_admin_menu_on_frontend', '1') === '1')
                        <a href="{{ route('admin.dashboard') }}" class="flex items-center justify-between py-3.5 transition-all duration-200 group border-t border-gray-100/50 dark:border-slate-800/50">
                            <div class="flex items-center gap-3">
                                <i data-lucide="shield-check" class="w-5 h-5 text-red-500 dark:text-red-400"></i>
                                <span class="text-sm font-semibold text-red-600 dark:text-red-400">{{ __('Trang quản trị') }}</span>
                            </div>
                            <i data-lucide="chevron-right" class="w-4 h-4 text-red-400 dark:text-red-500/50 group-hover:translate-x-0.5 transition-transform"></i>
                        </a>
                        @endif
                        <form action="{{ route('logout') }}" method="POST" class="block w-full">
                            @csrf
                            <button type="submit" class="w-full flex items-center justify-between py-3.5 text-left group">
                                <div class="flex items-center gap-3">
                                    <i data-lucide="log-out" class="w-5 h-5 text-gray-400 dark:text-slate-500"></i>
                                    <span class="text-sm font-semibold text-gray-700 dark:text-slate-300">{{ __('Đăng xuất') }}</span>
                                </div>
                                <i data-lucide="chevron-right" class="w-4 h-4 text-gray-300 dark:text-slate-600 group-hover:translate-x-0.5 transition-transform"></i>
                            </button>
                        </form>
                    @else
                        <div class="grid {{ \App\Models\Setting::getVal('registration_enabled', '1') === '1' ? 'grid-cols-2' : 'grid-cols-1' }} gap-3 py-4">
                            <a href="{{ route('login') }}" class="flex items-center justify-center px-4 py-2.5 text-sm font-semibold text-gray-700 dark:text-slate-300 bg-gray-50 dark:bg-slate-800/60 rounded-xl hover:bg-gray-100 dark:hover:bg-slate-800 border border-gray-100 dark:border-slate-800 transition-all duration-200">{{ __('Đăng nhập') }}</a>
                            @if(\App\Models\Setting::getVal('registration_enabled', '1') === '1')
                            <a href="{{ route('register') }}" class="flex items-center justify-center px-4 py-2.5 text-sm font-semibold text-white bg-gradient-to-r from-shopee to-shopee-light hover:brightness-110 rounded-xl shadow-md shadow-shopee/10 transition-all duration-200">{{ __('Đăng ký') }}</a>
                            @endif
                        </div>
                    @endauth
                @else
                    <!-- Phương án dự phòng (Fallback) nếu chưa cấu hình menu trong database -->
                    <a href="{{ route('home') }}" class="flex items-center justify-between py-3.5 transition-all duration-200 group">
                        <div class="flex items-center gap-3">
                            <i data-lucide="home" class="w-5 h-5 {{ request()->routeIs('home') ? 'text-shopee dark:text-shopee-light' : 'text-gray-400 dark:text-slate-500' }}"></i>
                            <span class="text-sm font-semibold {{ request()->routeIs('home') ? 'text-shopee dark:text-shopee-light font-bold' : 'text-gray-700 dark:text-slate-300' }}">{{ __('Trang chủ') }}</span>
                        </div>
                        <i data-lucide="chevron-right" class="w-4 h-4 text-gray-300 dark:text-slate-600 group-hover:translate-x-0.5 transition-transform"></i>
                    </a>
                    
                    @auth
                    <a href="{{ route('cashback.history') }}" class="flex items-center justify-between py-3.5 transition-all duration-200 group">
                        <div class="flex items-center gap-3">
                            <i data-lucide="shopping-bag" class="w-5 h-5 {{ request()->routeIs('cashback.history') ? 'text-shopee dark:text-shopee-light' : 'text-gray-400 dark:text-slate-500' }}"></i>
                            <span class="text-sm font-semibold {{ request()->routeIs('cashback.history') ? 'text-shopee dark:text-shopee-light font-bold' : 'text-gray-700 dark:text-slate-300' }}">{{ __('Đơn hàng') }}</span>
                        </div>
                        <i data-lucide="chevron-right" class="w-4 h-4 text-gray-300 dark:text-slate-600 group-hover:translate-x-0.5 transition-transform"></i>
                    </a>
    
                    @if(\App\Models\Setting::getVal('daily_checkin_enabled', '1') === '1')
                    <a href="{{ route('checkin') }}" class="flex items-center justify-between py-3.5 transition-all duration-200 group">
                        <div class="flex items-center gap-3">
                            <i data-lucide="calendar" class="w-5 h-5 {{ request()->routeIs('checkin') ? 'text-shopee dark:text-shopee-light' : 'text-gray-400 dark:text-slate-500' }}"></i>
                            <span class="text-sm font-semibold {{ request()->routeIs('checkin') ? 'text-shopee dark:text-shopee-light font-bold' : 'text-gray-700 dark:text-slate-300' }}">{{ __('Điểm danh') }}</span>
                        </div>
                        <i data-lucide="chevron-right" class="w-4 h-4 text-gray-300 dark:text-slate-600 group-hover:translate-x-0.5 transition-transform"></i>
                    </a>
                    @endif
    
                    @if(\App\Models\Setting::getVal('referral_enabled', '1') === '1')
                    <a href="{{ route('referrals') }}" class="flex items-center justify-between py-3.5 transition-all duration-200 group">
                        <div class="flex items-center gap-3">
                            <i data-lucide="users" class="w-5 h-5 {{ request()->routeIs('referrals') ? 'text-shopee dark:text-shopee-light' : 'text-gray-400 dark:text-slate-500' }}"></i>
                            <span class="text-sm font-semibold {{ request()->routeIs('referrals') ? 'text-shopee dark:text-shopee-light font-bold' : 'text-gray-700 dark:text-slate-300' }}">{{ __('Giới thiệu bạn bè') }}</span>
                        </div>
                        <i data-lucide="chevron-right" class="w-4 h-4 text-gray-300 dark:text-slate-600 group-hover:translate-x-0.5 transition-transform"></i>
                    </a>
                    @endif
    
                    @if(\App\Models\Setting::getVal('withdrawal_enabled', '1') === '1')
                    <a href="{{ route('withdraw') }}" class="flex items-center justify-between py-3.5 transition-all duration-200 group">
                        <div class="flex items-center gap-3">
                            <i data-lucide="wallet" class="w-5 h-5 {{ request()->routeIs('withdraw') ? 'text-shopee dark:text-shopee-light' : 'text-gray-400 dark:text-slate-500' }}"></i>
                            <span class="text-sm font-semibold {{ request()->routeIs('withdraw') ? 'text-shopee dark:text-shopee-light font-bold' : 'text-gray-700 dark:text-slate-300' }}">{{ __('Rút tiền') }}</span>
                        </div>
                        <i data-lucide="chevron-right" class="w-4 h-4 text-gray-300 dark:text-slate-600 group-hover:translate-x-0.5 transition-transform"></i>
                    </a>
                    @endif
    
                    @if(\App\Models\Setting::getVal('coupon_status', '1') == '1')
                    <a href="{{ route('coupons.index') }}" class="flex items-center justify-between py-3.5 transition-all duration-200 group">
                        <div class="flex items-center gap-3">
                            <i data-lucide="ticket" class="w-5 h-5 {{ request()->routeIs('coupons.index') ? 'text-shopee dark:text-shopee-light' : 'text-gray-400 dark:text-slate-500' }}"></i>
                            <span class="text-sm font-semibold {{ request()->routeIs('coupons.index') ? 'text-shopee dark:text-shopee-light font-bold' : 'text-gray-700 dark:text-slate-300' }}">{{ __('Mã giảm giá') }}</span>
                        </div>
                        <i data-lucide="chevron-right" class="w-4 h-4 text-gray-300 dark:text-slate-600 group-hover:translate-x-0.5 transition-transform"></i>
                    </a>
                    @endif
                    @if(\App\Models\Setting::getVal('ranking_status', '1') == '1')
                    <a href="{{ route('ranking.index') }}" class="flex items-center justify-between py-3.5 transition-all duration-200 group">
                        <div class="flex items-center gap-3">
                            <i data-lucide="trophy" class="w-5 h-5 {{ request()->routeIs('ranking.index') ? 'text-shopee dark:text-shopee-light' : 'text-gray-400 dark:text-slate-500' }}"></i>
                            <span class="text-sm font-semibold {{ request()->routeIs('ranking.index') ? 'text-shopee dark:text-shopee-light font-bold' : 'text-gray-700 dark:text-slate-300' }}">{{ __('Bảng xếp hạng') }}</span>
                        </div>
                        <i data-lucide="chevron-right" class="w-4 h-4 text-gray-300 dark:text-slate-600 group-hover:translate-x-0.5 transition-transform"></i>
                    </a>
                    @endif
                    @if(\App\Models\BotConfig::where('is_enabled', true)->exists())
                    <a href="{{ route('bot.guide') }}" class="flex items-center justify-between py-3.5 transition-all duration-200 group">
                        <div class="flex items-center gap-3">
                            <i data-lucide="bot" class="w-5 h-5 {{ request()->routeIs('bot.guide') ? 'text-shopee dark:text-shopee-light' : 'text-gray-400 dark:text-slate-500' }}"></i>
                            <span class="text-sm font-semibold {{ request()->routeIs('bot.guide') ? 'text-shopee dark:text-shopee-light font-bold' : 'text-gray-700 dark:text-slate-300' }}">{{ __('Bot hoàn tiền') }}</span>
                        </div>
                        <i data-lucide="chevron-right" class="w-4 h-4 text-gray-300 dark:text-slate-600 group-hover:translate-x-0.5 transition-transform"></i>
                    </a>
                    @endif
                    @if($blogEnabled == '1')
                    <a href="/blog" class="flex items-center justify-between py-3.5 transition-all duration-200 group">
                        <div class="flex items-center gap-3">
                            <i data-lucide="book-open" class="w-5 h-5 {{ request()->is('blog*') ? 'text-shopee dark:text-shopee-light' : 'text-gray-400 dark:text-slate-500' }}"></i>
                            <span class="text-sm font-semibold {{ request()->is('blog*') ? 'text-shopee dark:text-shopee-light font-bold' : 'text-gray-700 dark:text-slate-300' }}">{{ __('Blog tin tức') }}</span>
                        </div>
                        <i data-lucide="chevron-right" class="w-4 h-4 text-gray-300 dark:text-slate-600 group-hover:translate-x-0.5 transition-transform"></i>
                    </a>
                    @endif
    
                    <a href="{{ route('profile') }}" class="flex items-center justify-between py-3.5 transition-all duration-200 group">
                        <div class="flex items-center gap-3">
                            <i data-lucide="user" class="w-5 h-5 {{ request()->routeIs('profile') ? 'text-shopee dark:text-shopee-light' : 'text-gray-400 dark:text-slate-500' }}"></i>
                            <span class="text-sm font-semibold {{ request()->routeIs('profile') ? 'text-shopee dark:text-shopee-light font-bold' : 'text-gray-700 dark:text-slate-300' }}">{{ __('Hồ sơ tài khoản') }}</span>
                        </div>
                        <i data-lucide="chevron-right" class="w-4 h-4 text-gray-300 dark:text-slate-600 group-hover:translate-x-0.5 transition-transform"></i>
                    </a>
    
    
                    @if(auth()->user()->isAdmin() && \App\Models\Setting::getVal('show_admin_menu_on_frontend', '1') === '1')
                    <a href="{{ route('admin.dashboard') }}" class="flex items-center justify-between py-3.5 transition-all duration-200 group">
                        <div class="flex items-center gap-3">
                            <i data-lucide="shield-check" class="w-5 h-5 text-red-500 dark:text-red-400"></i>
                            <span class="text-sm font-semibold text-red-600 dark:text-red-400">{{ __('Trang quản trị') }}</span>
                        </div>
                        <i data-lucide="chevron-right" class="w-4 h-4 text-red-400 dark:text-red-500/50 group-hover:translate-x-0.5 transition-transform"></i>
                    </a>
                    @endif
    
                    <form action="{{ route('logout') }}" method="POST" class="block w-full">
                        @csrf
                        <button type="submit" class="w-full flex items-center justify-between py-3.5 text-left group">
                            <div class="flex items-center gap-3">
                                <i data-lucide="log-out" class="w-5 h-5 text-gray-400 dark:text-slate-500"></i>
                                <span class="text-sm font-semibold text-gray-700 dark:text-slate-300">{{ __('Đăng xuất') }}</span>
                            </div>
                            <i data-lucide="chevron-right" class="w-4 h-4 text-gray-300 dark:text-slate-600 group-hover:translate-x-0.5 transition-transform"></i>
                        </button>
                    </form>
                    @else
                    <div class="grid {{ \App\Models\Setting::getVal('registration_enabled', '1') === '1' ? 'grid-cols-2' : 'grid-cols-1' }} gap-3 py-4">
                        <a href="{{ route('login') }}" class="flex items-center justify-center px-4 py-2.5 text-sm font-semibold text-gray-700 dark:text-slate-300 bg-gray-50 dark:bg-slate-800/60 rounded-xl hover:bg-gray-100 dark:hover:bg-slate-800 border border-gray-100 dark:border-slate-800 transition-all duration-200">{{ __('Đăng nhập') }}</a>
                        @if(\App\Models\Setting::getVal('registration_enabled', '1') === '1')
                        <a href="{{ route('register') }}" class="flex items-center justify-center px-4 py-2.5 text-sm font-semibold text-white bg-gradient-to-r from-shopee to-shopee-light hover:brightness-110 rounded-xl shadow-md shadow-shopee/10 transition-all duration-200">{{ __('Đăng ký') }}</a>
                        @endif
                    </div>
                    @endauth
                @endif

                <!-- Mobile Dark Mode Toggle (Premium minimalist integration) -->
                <div class="flex items-center justify-between py-3.5">
                    <div class="flex items-center gap-3">
                        <i data-lucide="moon" class="w-5 h-5 text-gray-400 dark:hidden"></i>
                        <i data-lucide="sun" class="w-5 h-5 hidden dark:block text-yellow-400"></i>
                        <span class="text-sm font-semibold text-gray-700 dark:text-slate-300">{{ __('Chế độ tối') }}</span>
                    </div>
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
                        class="relative inline-flex h-5 w-9 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none"
                        :class="isDark ? 'bg-shopee' : 'bg-gray-200 dark:bg-slate-800'"
                        role="switch" aria-checked="false">
                        <span aria-hidden="true" class="pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"
                            :class="isDark ? 'translate-x-4' : 'translate-x-0'"></span>
                    </button>
                </div>
            </div>
        </div>
    </header>
    @endif

    <!-- 2. MAIN CONTENT -->
    <main class="flex-grow {{ session('from_app') ? '' : 'pb-16 md:pb-0' }} relative">
        @yield('content')
    </main>

    <!-- 3. FOOTER -->
    @if(!session('from_app'))
    <footer class="bg-slate-950/90 dark:bg-slate-950/80 backdrop-blur-md border-t border-slate-900/80 dark:border-slate-800/60 text-slate-400 pt-12 pb-28 md:py-12 mt-auto relative z-0">
        <div class="px-4 mx-auto max-w-7xl sm:px-6 lg:px-8">
            <!-- ============ FOOTER DESKTOP (từ md trở lên) ============ -->
            <div class="hidden md:grid md:grid-cols-4 gap-8">
                <!-- Branding & Socials -->
                <div class="space-y-4">
                    <div class="flex items-center gap-2 text-white">
                        @if(!empty($siteLogoDark) || !empty($siteLogo))
                            <img src="{{ !empty($siteLogoDark) ? $siteLogoDarkUrl : $siteLogoUrl }}" alt="Mê Sale" loading="lazy" decoding="async" class="h-10 w-auto object-contain">
                        @else
                        <div class="flex items-center justify-center w-9 h-9 rounded-lg bg-shopee text-white">
                            <i data-lucide="shopping-bag" class="w-5 h-5"></i>
                        </div>
                        <span class="text-xl font-bold tracking-tight text-white">{{ $siteName }}</span>
                        @endif
                    </div>
                    <!-- Lấy mô tả website từ cấu hình SEO Meta trong quản trị hệ thống -->
                    <p class="text-sm leading-relaxed max-w-sm text-slate-400 dark:text-slate-400/90">
                        {{ $siteDescription }}
                    </p>
                    <address class="not-italic text-sm text-slate-400 dark:text-slate-400/90 flex items-center gap-2">
                        <i data-lucide="map-pin" class="w-4 h-4 shrink-0 text-shopee"></i>
                        <span>{{ __('Bắc Ninh - Việt Nam') }}</span>
                    </address>
                    <!--
                        Khối hiển thị liên kết mạng xã hội hỗ trợ
                        Lý do: Tải động các đường dẫn mạng xã hội từ bảng cấu hình settings. 
                        Ẩn hoàn toàn nếu Admin không nhập thông tin để tránh hiển thị icon trống ảnh hưởng tới trải nghiệm người dùng.
                    -->
                    @php
                        $facebookLink = \App\Models\Setting::getVal('facebook_link');
                        $telegramLink = \App\Models\Setting::getVal('telegram_link');
                        $zaloLink = \App\Models\Setting::getVal('zalo_link');
                        $youtubeLink = \App\Models\Setting::getVal('youtube_link');
                        $tiktokLink = \App\Models\Setting::getVal('tiktok_link');
                    @endphp
                    @if(!empty($facebookLink) || !empty($telegramLink) || !empty($zaloLink) || !empty($youtubeLink) || !empty($tiktokLink))
                    <div class="flex items-center gap-2.5 pt-1 flex-wrap">
                        @if(!empty($facebookLink))
                        <a href="{{ $facebookLink }}" target="_blank" rel="noopener noreferrer" class="w-8 h-8 rounded-xl bg-slate-900/50 dark:bg-slate-900/30 text-slate-400 hover:text-white hover:bg-shopee border border-slate-800/80 dark:border-slate-800/50 flex items-center justify-center transition-all duration-200" title="Facebook">
                            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/>
                            </svg>
                        </a>
                        @endif
                        @if(!empty($telegramLink))
                        <a href="{{ $telegramLink }}" target="_blank" rel="noopener noreferrer" class="w-8 h-8 rounded-xl bg-slate-900/50 dark:bg-slate-900/30 text-slate-400 hover:text-white hover:bg-shopee border border-slate-800/80 dark:border-slate-800/50 flex items-center justify-center transition-all duration-200" title="Telegram">
                            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="22" y1="2" x2="11" y2="13"></line>
                                <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
                            </svg>
                        </a>
                        @endif
                        @if(!empty($zaloLink))
                        <a href="{{ $zaloLink }}" target="_blank" rel="noopener noreferrer" class="w-8 h-8 rounded-xl bg-slate-900/50 dark:bg-slate-900/30 text-slate-400 hover:text-white hover:bg-shopee border border-slate-800/80 dark:border-slate-800/50 flex items-center justify-center transition-all duration-200" title="Zalo">
                            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/>
                                <path d="M9 10h6l-6 5h6" stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"/>
                            </svg>
                        </a>
                        @endif
                        @if(!empty($youtubeLink))
                        <a href="{{ $youtubeLink }}" target="_blank" rel="noopener noreferrer" class="w-8 h-8 rounded-xl bg-slate-900/50 dark:bg-slate-900/30 text-slate-400 hover:text-white hover:bg-shopee border border-slate-800/80 dark:border-slate-800/50 flex items-center justify-center transition-all duration-200" title="Youtube">
                            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M22.54 6.42a2.78 2.78 0 0 0-1.94-2C18.88 4 12 4 12 4s-6.88 0-8.6.46a2.78 2.78 0 0 0-1.94 2A29 29 0 0 0 1 11.75a29 29 0 0 0 .46 5.33A2.78 2.78 0 0 0 3.4 19c1.72.46 8.6.46 8.6.46s6.88 0 8.6-.46a2.78 2.78 0 0 0 1.94-2 29 29 0 0 0 .46-5.25 29 29 0 0 0-.46-5.33z"/>
                                <polygon points="9.75 15.02 15.5 11.75 9.75 8.48 9.75 15.02"/>
                            </svg>
                        </a>
                        @endif
                        @if(!empty($tiktokLink))
                        <a href="{{ $tiktokLink }}" target="_blank" rel="noopener noreferrer" class="w-8 h-8 rounded-xl bg-slate-900/50 dark:bg-slate-900/30 text-slate-400 hover:text-white hover:bg-shopee border border-slate-800/80 dark:border-slate-800/50 flex items-center justify-center transition-all duration-200" title="TikTok">
                            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M9 12a4 4 0 1 0 4 4V4a5 5 0 0 0 5 5"/>
                            </svg>
                        </a>
                        @endif
                    </div>
                    @endif
                </div>

                <!-- Quick links -->
                <div>
                    <h3 class="text-white font-bold text-sm tracking-wide uppercase mb-4">{{ __('Danh Mục') }}</h3>
                    <ul class="space-y-3 text-sm">
                        @if(isset($groupedMenus) && $groupedMenus->has('footer'))
                            @foreach($groupedMenus['footer'] as $fMenu)
                                @php
                                    $showMenu = false;
                                    if ($fMenu->auth_rule === 'all') {
                                        $showMenu = true;
                                    } elseif ($fMenu->auth_rule === 'auth' && auth()->check()) {
                                        $showMenu = true;
                                    } elseif ($fMenu->auth_rule === 'guest' && auth()->guest()) {
                                        $showMenu = true;
                                    }
                                @endphp
                                @if($showMenu)
                                    <li>
                                        <a href="{{ $fMenu->url ? url($fMenu->url) : '#' }}" 
                                           target="{{ $fMenu->target }}"
                                           class="flex items-center gap-2 text-slate-300 hover:text-white transition-all duration-200 group">
                                            <i data-lucide="{{ $fMenu->icon ?: 'chevron-right' }}" class="w-3.5 h-3.5 text-slate-600 group-hover:translate-x-0.5 transition-transform"></i>
                                            {{ __($fMenu->title) }}
                                        </a>
                                    </li>
                                @endif
                            @endforeach
                        @else
                            <!-- Phương án dự phòng (Fallback) nếu chưa cấu hình menu footer -->
                            <li>
                                <a href="{{ route('home') }}" class="flex items-center gap-2 text-slate-300 hover:text-white transition-all duration-200 group">
                                    <i data-lucide="chevron-right" class="w-3.5 h-3.5 text-slate-600 group-hover:translate-x-0.5 transition-transform"></i>
                                    {{ __('Trang chủ') }}
                                </a>
                            </li>
                            @if(\App\Models\Setting::getVal('ranking_status', '1') == '1')
                            <li>
                                <a href="{{ route('ranking.index') }}" class="flex items-center gap-2 text-slate-300 hover:text-white transition-all duration-200 group">
                                    <i data-lucide="chevron-right" class="w-3.5 h-3.5 text-slate-600 group-hover:translate-x-0.5 transition-transform"></i>
                                    {{ __('Bảng xếp hạng') }}
                                </a>
                            </li>
                            @endif
                            @if(\App\Models\Setting::getVal('coupon_status', '1') == '1')
                            <li>
                                <a href="{{ route('coupons.index') }}" class="flex items-center gap-2 text-slate-300 hover:text-white transition-all duration-200 group">
                                    <i data-lucide="chevron-right" class="w-3.5 h-3.5 text-slate-600 group-hover:translate-x-0.5 transition-transform"></i>
                                    {{ __('Mã giảm giá') }}
                                </a>
                            </li>
                            @endif
                            @auth
                            <li>
                                <a href="{{ route('checkin') }}" class="flex items-center gap-2 text-slate-300 hover:text-white transition-all duration-200 group">
                                    <i data-lucide="chevron-right" class="w-3.5 h-3.5 text-slate-600 group-hover:translate-x-0.5 transition-transform"></i>
                                    {{ __('Điểm danh hàng ngày') }}
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('referrals') }}" class="flex items-center gap-2 text-slate-300 hover:text-white transition-all duration-200 group">
                                    <i data-lucide="chevron-right" class="w-3.5 h-3.5 text-slate-600 group-hover:translate-x-0.5 transition-transform"></i>
                                    {{ __('Tiếp thị liên kết') }}
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('withdraw') }}" class="flex items-center gap-2 text-slate-300 hover:text-white transition-all duration-200 group">
                                    <i data-lucide="chevron-right" class="w-3.5 h-3.5 text-slate-600 group-hover:translate-x-0.5 transition-transform"></i>
                                    {{ __('Yêu cầu rút tiền') }}
                                </a>
                            </li>
                            @endauth
                        @endif
                    </ul>
                </div>

                <!-- Support info -->
                <!--
                    Khối thông tin hỗ trợ khách hàng (Email, Hotline, Zalo, Facebook, Telegram)
                    Lý do: Cung cấp đầy đủ các kênh liên hệ hỗ trợ trực tiếp. 
                    Chỉ hiển thị các kênh có cấu hình URL/Hotline hợp lệ từ admin panel để tránh các đường link hỏng, tối ưu hóa giao diện.
                -->
                @php
                    $supportEmail = \App\Models\Setting::getVal('support_email');
                    $supportHotline = \App\Models\Setting::getVal('support_hotline');
                @endphp
                <div>
                    <h3 class="text-white font-bold text-sm tracking-wide uppercase mb-4">{{ __('Hỗ Trợ') }}</h3>
                    <ul class="space-y-3 text-sm">
                        @if(!empty($supportEmail))
                        <li class="flex items-start gap-2.5">
                            <div class="w-5 h-5 rounded-lg bg-shopee/10 text-shopee flex items-center justify-center mt-0.5 flex-shrink-0">
                                <i data-lucide="mail" class="w-3 h-3"></i>
                            </div>
                            <span class="text-slate-300 break-all">{{ $supportEmail }}</span>
                        </li>
                        @endif
                        @if(!empty($supportHotline))
                        <li class="flex items-start gap-2.5">
                            <div class="w-5 h-5 rounded-lg bg-emerald-500/10 text-emerald-500 flex items-center justify-center mt-0.5 flex-shrink-0">
                                <i data-lucide="phone" class="w-3 h-3"></i>
                            </div>
                            <div class="flex flex-col">
                                <span class="font-semibold text-slate-200">{{ $supportHotline }}</span>
                                <span class="text-[11px] text-slate-500">{{ \App\Models\Setting::getVal('support_work_time', 'Tổng đài (8h - 18h)') }}</span>
                            </div>
                        </li>
                        @endif
                        @if(!empty($zaloLink))
                        <li class="flex items-start gap-2.5">
                            <div class="w-5 h-5 rounded-lg bg-blue-500/10 text-blue-500 flex items-center justify-center mt-0.5 flex-shrink-0">
                                <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/>
                                    <path d="M9 10h6l-6 5h6" stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"/>
                                </svg>
                            </div>
                            <div class="flex flex-col">
                                <a href="{{ $zaloLink }}" target="_blank" rel="noopener noreferrer" class="font-semibold text-slate-200 hover:text-white hover:underline transition-colors">{{ __('Hỗ trợ qua Zalo') }}</a>
                            </div>
                        </li>
                        @endif
                        @if(!empty($facebookLink))
                        <li class="flex items-start gap-2.5">
                            <div class="w-5 h-5 rounded-lg bg-indigo-500/10 text-indigo-400 flex items-center justify-center mt-0.5 flex-shrink-0">
                                <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/>
                                </svg>
                            </div>
                            <div class="flex flex-col">
                                <a href="{{ $facebookLink }}" target="_blank" rel="noopener noreferrer" class="font-semibold text-slate-200 hover:text-white hover:underline transition-colors">{{ __('Liên hệ Fanpage') }}</a>
                            </div>
                        </li>
                        @endif
                        @if(!empty($telegramLink))
                        <li class="flex items-start gap-2.5">
                            <div class="w-5 h-5 rounded-lg bg-sky-500/10 text-sky-400 flex items-center justify-center mt-0.5 flex-shrink-0">
                                <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <line x1="22" y1="2" x2="11" y2="13"></line>
                                    <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
                                </svg>
                            </div>
                            <div class="flex flex-col">
                                <a href="{{ $telegramLink }}" target="_blank" rel="noopener noreferrer" class="font-semibold text-slate-200 hover:text-white hover:underline transition-colors">{{ __('Cộng đồng Telegram') }}</a>
                            </div>
                        </li>
                        @endif
                    </ul>
                </div>

                <!-- Commitments -->
                <div>
                    <h3 class="text-white font-bold text-sm tracking-wide uppercase mb-4">
                        {{ \App\Models\Setting::getVal('footer_commitment_title', __('Cam Kết')) }}
                    </h3>
                    <ul class="space-y-3 text-sm">
                        <li class="flex items-start gap-2.5">
                            <div class="w-5 h-5 rounded-lg bg-amber-500/10 text-amber-500 flex items-center justify-center mt-0.5 flex-shrink-0">
                                <i data-lucide="{{ \App\Models\Setting::getVal('footer_commitment_icon_1', 'badge-check') }}" class="w-3 h-3"></i>
                            </div>
                            <span class="text-slate-300">
                                {{ \App\Models\Setting::getVal('footer_commitment_text_1', __('Tỷ lệ hoàn tiền cao hàng đầu')) }}
                            </span>
                        </li>
                        <li class="flex items-start gap-2.5">
                            <div class="w-5 h-5 rounded-lg bg-blue-500/10 text-blue-500 flex items-center justify-center mt-0.5 flex-shrink-0">
                                <i data-lucide="{{ \App\Models\Setting::getVal('footer_commitment_icon_2', 'zap') }}" class="w-3 h-3"></i>
                            </div>
                            <span class="text-slate-300">
                                {{ \App\Models\Setting::getVal('footer_commitment_text_2', __('Rút tiền nhanh chóng, bảo mật')) }}
                            </span>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- ============ FOOTER MOBILE (KIỂU WEB APP) ============ -->
            @php
                // === Nguồn dữ liệu dùng riêng cho footer mobile (self-contained) ===
                // Liên kết mạng xã hội — chỉ hiển thị kênh đã được cấu hình trong admin
                $mFacebook = \App\Models\Setting::getVal('facebook_link');
                $mTelegram = \App\Models\Setting::getVal('telegram_link');
                $mZalo = \App\Models\Setting::getVal('zalo_link');
                $mYoutube = \App\Models\Setting::getVal('youtube_link');
                $mTiktok = \App\Models\Setting::getVal('tiktok_link');

                // Bộ dựng SVG icon dùng chung cho hàng mạng xã hội và danh sách hỗ trợ
                $mSvgIcon = function($key, $cls = 'w-5 h-5') {
                    return match($key) {
                        'facebook' => '<svg class="'.$cls.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg>',
                        'telegram' => '<svg class="'.$cls.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"></line><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon></svg>',
                        'zalo' => '<svg class="'.$cls.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/><path d="M9 10h6l-6 5h6" stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"/></svg>',
                        'youtube' => '<svg class="'.$cls.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22.54 6.42a2.78 2.78 0 0 0-1.94-2C18.88 4 12 4 12 4s-6.88 0-8.6.46a2.78 2.78 0 0 0-1.94 2A29 29 0 0 0 1 11.75a29 29 0 0 0 .46 5.33A2.78 2.78 0 0 0 3.4 19c1.72.46 8.6.46 8.6.46s6.88 0 8.6-.46a2.78 2.78 0 0 0 1.94-2 29 29 0 0 0 .46-5.25 29 29 0 0 0-.46-5.33z"/><polygon points="9.75 15.02 15.5 11.75 9.75 8.48 9.75 15.02"/></svg>',
                        'tiktok' => '<svg class="'.$cls.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 12a4 4 0 1 0 4 4V4a5 5 0 0 0 5 5"/></svg>',
                        default => '',
                    };
                };

                // Danh sách mạng xã hội để lặp
                $mSocials = [];
                if (!empty($mFacebook)) $mSocials[] = ['href' => $mFacebook, 'key' => 'facebook', 'label' => 'Facebook'];
                if (!empty($mTelegram)) $mSocials[] = ['href' => $mTelegram, 'key' => 'telegram', 'label' => 'Telegram'];
                if (!empty($mZalo)) $mSocials[] = ['href' => $mZalo, 'key' => 'zalo', 'label' => 'Zalo'];
                if (!empty($mYoutube)) $mSocials[] = ['href' => $mYoutube, 'key' => 'youtube', 'label' => 'Youtube'];
                if (!empty($mTiktok)) $mSocials[] = ['href' => $mTiktok, 'key' => 'tiktok', 'label' => 'TikTok'];

                // Danh sách liên kết cột "Danh Mục" (đồng bộ logic với footer desktop)
                $mLinks = [];
                if (isset($groupedMenus) && $groupedMenus->has('footer')) {
                    foreach ($groupedMenus['footer'] as $fMenu) {
                        $showMenu = $fMenu->auth_rule === 'all'
                            || ($fMenu->auth_rule === 'auth' && auth()->check())
                            || ($fMenu->auth_rule === 'guest' && auth()->guest());
                        if ($showMenu) {
                            $mLinks[] = ['title' => __($fMenu->title), 'url' => $fMenu->url ? url($fMenu->url) : '#', 'icon' => $fMenu->icon ?: 'chevron-right', 'target' => $fMenu->target];
                        }
                    }
                } else {
                    $mLinks[] = ['title' => __('Trang chủ'), 'url' => route('home'), 'icon' => 'home', 'target' => '_self'];
                    if (\App\Models\Setting::getVal('ranking_status', '1') == '1') $mLinks[] = ['title' => __('Bảng xếp hạng'), 'url' => route('ranking.index'), 'icon' => 'trophy', 'target' => '_self'];
                    if (\App\Models\Setting::getVal('coupon_status', '1') == '1') $mLinks[] = ['title' => __('Mã giảm giá'), 'url' => route('coupons.index'), 'icon' => 'ticket-percent', 'target' => '_self'];
                    if (auth()->check()) {
                        $mLinks[] = ['title' => __('Điểm danh hàng ngày'), 'url' => route('checkin'), 'icon' => 'calendar-check', 'target' => '_self'];
                        $mLinks[] = ['title' => __('Tiếp thị liên kết'), 'url' => route('referrals'), 'icon' => 'link-2', 'target' => '_self'];
                        $mLinks[] = ['title' => __('Yêu cầu rút tiền'), 'url' => route('withdraw'), 'icon' => 'banknote', 'target' => '_self'];
                    }
                }

                // Danh sách kênh hỗ trợ
                $mSupportEmail = \App\Models\Setting::getVal('support_email');
                $mSupportHotline = \App\Models\Setting::getVal('support_hotline');
                $mSupport = [];
                if (!empty($mSupportEmail)) $mSupport[] = ['type' => 'lucide', 'key' => 'mail', 'color' => 'bg-shopee/10 text-shopee', 'label' => $mSupportEmail, 'sub' => null, 'href' => null];
                if (!empty($mSupportHotline)) $mSupport[] = ['type' => 'lucide', 'key' => 'phone', 'color' => 'bg-emerald-500/10 text-emerald-500', 'label' => $mSupportHotline, 'sub' => \App\Models\Setting::getVal('support_work_time', 'Tổng đài (8h - 18h)'), 'href' => null];
                if (!empty($mZalo)) $mSupport[] = ['type' => 'svg', 'key' => 'zalo', 'color' => 'bg-blue-500/10 text-blue-500', 'label' => __('Hỗ trợ qua Zalo'), 'sub' => null, 'href' => $mZalo];
                if (!empty($mFacebook)) $mSupport[] = ['type' => 'svg', 'key' => 'facebook', 'color' => 'bg-indigo-500/10 text-indigo-400', 'label' => __('Liên hệ Fanpage'), 'sub' => null, 'href' => $mFacebook];
                if (!empty($mTelegram)) $mSupport[] = ['type' => 'svg', 'key' => 'telegram', 'color' => 'bg-sky-500/10 text-sky-400', 'label' => __('Cộng đồng Telegram'), 'sub' => null, 'href' => $mTelegram];
            @endphp

            <div class="md:hidden">
                <!-- Thương hiệu + mô tả (căn giữa cho cảm giác app) -->
                <div class="flex flex-col items-center text-center gap-3">
                    <div class="flex items-center gap-2 text-white">
                        @if(!empty($siteLogoDark) || !empty($siteLogo))
                            <img src="{{ !empty($siteLogoDark) ? $siteLogoDarkUrl : $siteLogoUrl }}" alt="Mê Sale" loading="lazy" decoding="async" class="h-9 w-auto object-contain">
                        @else
                        <div class="flex items-center justify-center w-9 h-9 rounded-xl bg-shopee text-white">
                            <i data-lucide="shopping-bag" class="w-5 h-5"></i>
                        </div>
                        <span class="text-lg font-bold tracking-tight text-white">{{ $siteName }}</span>
                        @endif
                    </div>
                    <p class="text-[13px] leading-relaxed text-slate-400 max-w-xs">{{ $siteDescription }}</p>
                    <address class="not-italic text-[13px] text-slate-400 flex items-center justify-center gap-2">
                        <i data-lucide="map-pin" class="w-4 h-4 shrink-0 text-shopee"></i>
                        <span>{{ __('Bắc Ninh - Việt Nam') }}</span>
                    </address>

                    @if(count($mSocials))
                    <div class="flex items-center justify-center gap-2.5 pt-1">
                        @foreach($mSocials as $social)
                        <a href="{{ $social['href'] }}" target="_blank" rel="noopener noreferrer"
                           class="w-9 h-9 rounded-xl bg-slate-900/60 dark:bg-slate-900/40 text-slate-400 hover:text-white active:text-white hover:bg-shopee active:bg-shopee border border-slate-800/70 flex items-center justify-center transition-all duration-200"
                           title="{{ $social['label'] }}">
                            {!! $mSvgIcon($social['key']) !!}
                        </a>
                        @endforeach
                    </div>
                    @endif
                </div>

                <!-- Nhóm liên kết dạng accordion (thu gọn giống web app) -->
                <div class="mt-6 border-y border-slate-900/80 divide-y divide-slate-900/70">
                    <!-- Danh Mục -->
                    <div x-data="{ open: false }">
                        <button type="button" @click="open = !open" class="w-full flex items-center justify-between py-4 text-left group">
                            <span class="text-white font-bold text-[13px] tracking-wide uppercase">{{ __('Danh Mục') }}</span>
                            <i data-lucide="chevron-down" class="w-4 h-4 text-slate-500 group-active:text-shopee transition-transform duration-200" :class="{ 'rotate-180': open }"></i>
                        </button>
                        <ul x-show="open" x-collapse x-cloak class="pb-2">
                            @foreach($mLinks as $link)
                            <li>
                                <a href="{{ $link['url'] }}" target="{{ $link['target'] }}"
                                   class="flex items-center gap-3 py-2.5 text-sm text-slate-300 hover:text-white active:text-white transition-colors">
                                    <i data-lucide="{{ $link['icon'] }}" class="w-4 h-4 text-slate-600 flex-shrink-0"></i>
                                    <span>{{ $link['title'] }}</span>
                                </a>
                            </li>
                            @endforeach
                        </ul>
                    </div>

                    <!-- Hỗ Trợ -->
                    @if(count($mSupport))
                    <div x-data="{ open: false }">
                        <button type="button" @click="open = !open" class="w-full flex items-center justify-between py-4 text-left group">
                            <span class="text-white font-bold text-[13px] tracking-wide uppercase">{{ __('Hỗ Trợ') }}</span>
                            <i data-lucide="chevron-down" class="w-4 h-4 text-slate-500 group-active:text-shopee transition-transform duration-200" :class="{ 'rotate-180': open }"></i>
                        </button>
                        <ul x-show="open" x-collapse x-cloak class="space-y-3 pb-4">
                            @foreach($mSupport as $item)
                            <li class="flex items-start gap-3">
                                <div class="w-8 h-8 rounded-xl {{ $item['color'] }} flex items-center justify-center flex-shrink-0">
                                    @if($item['type'] === 'lucide')
                                        <i data-lucide="{{ $item['key'] }}" class="w-4 h-4"></i>
                                    @else
                                        {!! $mSvgIcon($item['key'], 'w-4 h-4') !!}
                                    @endif
                                </div>
                                <div class="flex flex-col justify-center min-h-[2rem]">
                                    @if(!empty($item['href']))
                                        <a href="{{ $item['href'] }}" target="_blank" rel="noopener noreferrer" class="text-sm font-semibold text-slate-200 hover:text-white transition-colors">{{ $item['label'] }}</a>
                                    @else
                                        <span class="text-sm font-semibold text-slate-200 break-all">{{ $item['label'] }}</span>
                                    @endif
                                    @if(!empty($item['sub']))
                                        <span class="text-[11px] text-slate-500 mt-0.5">{{ $item['sub'] }}</span>
                                    @endif
                                </div>
                            </li>
                            @endforeach
                        </ul>
                    </div>
                    @endif
                </div>

                <!-- Cam kết -->
                <div class="mt-6 space-y-2">
                    <h3 class="text-center text-white font-bold text-[13px] tracking-wide uppercase mb-3">
                        {{ \App\Models\Setting::getVal('footer_commitment_title', __('Cam Kết')) }}
                    </h3>
                    <div class="flex items-center gap-3 px-4 py-3 rounded-2xl bg-slate-900/50 border border-slate-800/60">
                        <div class="w-8 h-8 rounded-xl bg-amber-500/10 text-amber-500 flex items-center justify-center flex-shrink-0">
                            <i data-lucide="{{ \App\Models\Setting::getVal('footer_commitment_icon_1', 'badge-check') }}" class="w-4 h-4"></i>
                        </div>
                        <span class="text-[13px] text-slate-300">{{ \App\Models\Setting::getVal('footer_commitment_text_1', __('Tỷ lệ hoàn tiền cao hàng đầu')) }}</span>
                    </div>
                    <div class="flex items-center gap-3 px-4 py-3 rounded-2xl bg-slate-900/50 border border-slate-800/60">
                        <div class="w-8 h-8 rounded-xl bg-blue-500/10 text-blue-500 flex items-center justify-center flex-shrink-0">
                            <i data-lucide="{{ \App\Models\Setting::getVal('footer_commitment_icon_2', 'zap') }}" class="w-4 h-4"></i>
                        </div>
                        <span class="text-[13px] text-slate-300">{{ \App\Models\Setting::getVal('footer_commitment_text_2', __('Rút tiền nhanh chóng, bảo mật')) }}</span>
                    </div>
                </div>
            </div>

            <div class="border-t border-slate-900 mt-8 pt-8 text-center text-xs flex flex-col sm:flex-row items-center justify-between gap-4 text-slate-500">
                <p>{!! \App\Models\Setting::getVal('footer_copyright', '&copy; ' . date('Y') . ' Cashback Shopee Platform. Bảo lưu mọi quyền.') !!}</p>
                <div class="flex items-center gap-4 flex-wrap justify-center sm:justify-end">
                    <a href="{{ \App\Models\Setting::getVal('footer_terms_url', '#') }}" class="hover:text-white transition-all">{{ __('Điều khoản dịch vụ') }}</a>
                    <a href="{{ \App\Models\Setting::getVal('footer_privacy_url', '#') }}" class="hover:text-white transition-all">{{ __('Chính sách bảo mật') }}</a>
                    
                    <!-- Footer Language Selector Dropdown -->
                    @if(isset($activeLanguages) && $activeLanguages->count() > 1)
                    <div class="relative" x-data="{ open: false }" @click.away="open = false">
                        <button @click="open = !open" 
                                class="flex items-center gap-1.5 px-2.5 py-1 rounded bg-slate-900 hover:bg-slate-800 text-slate-400 hover:text-white border border-slate-800 transition-all text-[11px] font-semibold"
                                title="Thay đổi ngôn ngữ">
                            @if(isset($currentLanguage) && $currentLanguage->flag)
                                {{-- Cờ quốc gia là ảnh trang trí (tên ngôn ngữ đã hiển thị ngay cạnh) nên để alt rỗng theo chuẩn trợ năng --}}
                                <img src="{{ $currentLanguage->flag }}" alt="" width="16" height="10" loading="lazy" class="w-4 h-2.5 object-cover rounded shadow-sm">
                            @endif
                            <span class="uppercase font-mono text-[10px]">{{ $currentLocale }}</span>
                            <i data-lucide="chevron-up" class="w-3 h-3 text-slate-500 transition-transform" :class="{ 'rotate-180': open }"></i>
                        </button>
                        
                        <div x-show="open" 
                             x-transition 
                             x-cloak 
                             class="absolute bottom-full right-0 mb-2 w-36 bg-slate-900 border border-slate-800 rounded-xl shadow-2xl z-50 py-1">
                            @foreach($activeLanguages as $lang)
                                <a href="{{ route('change-language', $lang->code) }}" 
                                   class="flex items-center gap-2 px-3 py-1.5 text-[11px] font-semibold text-slate-400 hover:bg-slate-800 hover:text-white {{ $currentLocale === $lang->code ? 'bg-shopee/10 text-shopee' : '' }}">
                                    @if($lang->flag)
                                        <img src="{{ $lang->flag }}" alt="" width="16" height="10" loading="lazy" class="w-4 h-2.5 object-cover rounded shadow-sm">
                                    @endif
                                    <span>{{ $lang->name }}</span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    <!-- Footer Currency Selector Dropdown -->
                    @if(isset($activeCurrencies) && $activeCurrencies->count() > 1)
                    <div class="relative" x-data="{ open: false }" @click.away="open = false">
                        <button @click="open = !open" 
                                class="flex items-center gap-1.5 px-2.5 py-1 rounded bg-slate-900 hover:bg-slate-800 text-slate-400 hover:text-white border border-slate-800 transition-all text-[11px] font-semibold"
                                title="Thay đổi tiền tệ">
                            <i data-lucide="coins" class="w-3.5 h-3.5 text-slate-400"></i>
                            <span class="uppercase font-mono text-[10px]">{{ $currentCurrency->code }}</span>
                            <i data-lucide="chevron-up" class="w-3 h-3 text-slate-500 transition-transform" :class="{ 'rotate-180': open }"></i>
                        </button>
                        
                        <div x-show="open" 
                             x-transition 
                             x-cloak 
                             class="absolute bottom-full right-0 mb-2 w-36 bg-slate-900 border border-slate-800 rounded-xl shadow-2xl z-50 py-1">
                            @foreach($activeCurrencies as $curr)
                                <a href="{{ route('change-currency', $curr->code) }}" 
                                   class="flex items-center gap-2 px-3 py-1.5 text-[11px] font-semibold text-slate-400 hover:bg-slate-800 hover:text-white {{ $currentCurrency->code === $curr->code ? 'bg-shopee/10 text-shopee' : '' }}">
                                    <span class="w-4 text-center font-bold text-shopee">{{ $curr->symbol }}</span>
                                    <span>{{ $curr->name }}</span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </footer>
    @endif

    <!-- BOTTOM NAVIGATION BAR FOR MOBILE (WEBAPP STYLE) -->
    @if(!session('from_app'))
    @php
        // Hàm kiểm tra xem URL của menu có trùng khớp với request URL hiện tại không để kích hoạt trạng thái active
        $isMenuHtmlActive = function($menuUrl) {
            $currentUrl = request()->url();
            if ($currentUrl === $menuUrl || url($menuUrl) === $currentUrl) {
                return true;
            }
            $path = request()->getPathInfo();
            if (trim($menuUrl, '/') === trim($path, '/')) {
                return true;
            }
            return false;
        };

        // Lấy danh sách menu vị trí bottom từ database thông qua biến $groupedMenus chia sẻ toàn cục
        $bottomDbMenus = isset($groupedMenus['bottom']) ? $groupedMenus['bottom'] : collect();

        // Lọc menu theo phân quyền hiển thị (auth_rule) và giới hạn tối đa 4 menu đầu tiên theo yêu cầu
        if (auth()->check()) {
            $bottomActiveMenus = $bottomDbMenus->filter(fn($m) => in_array($m->auth_rule, ['all', 'auth']))->take(4);
        } else {
            $bottomActiveMenus = $bottomDbMenus->filter(fn($m) => in_array($m->auth_rule, ['all', 'guest']))->take(4);
        }
    @endphp
    <div class="fixed bottom-0 left-0 right-0 z-40 bg-white/90 dark:bg-slate-900/90 backdrop-blur-xl border-t border-gray-100/80 dark:border-slate-800/80 shadow-[0_-8px_24px_-8px_rgba(0,0,0,0.12)] md:hidden safe-bottom">
        @auth
        @php
            // Số cột grid sẽ bằng số lượng menu động + 1 (cho menu Thêm cố định)
            $gridColsClass = 'grid-cols-' . ($bottomActiveMenus->count() + 1);
        @endphp
        <div class="grid {{ $gridColsClass }} h-[68px] px-1.5 pt-1.5">
            @foreach($bottomActiveMenus as $menu)
                @php
                    $isActive = false;
                    if (isset($menu->active_route)) {
                        $isActive = request()->routeIs($menu->active_route);
                    } else {
                        $isActive = $isMenuHtmlActive($menu->url);
                    }
                @endphp
                <a href="{{ $menu->url }}"
                   target="{{ $menu->target ?? '_self' }}"
                   class="group relative flex flex-col items-center justify-center gap-1 select-none transition-transform active:scale-90">
                    @if($isActive)
                        <span class="absolute top-0 w-8 h-1 rounded-full bg-gradient-to-r from-shopee to-shopee-light"></span>
                    @endif
                    <span class="flex items-center justify-center w-12 h-7 rounded-full transition-all duration-200 {{ $isActive ? 'bg-shopee/10 dark:bg-shopee/20 text-shopee dark:text-shopee-light' : 'text-gray-400 dark:text-slate-400 group-hover:text-gray-600 dark:group-hover:text-slate-200' }}">
                        <i data-lucide="{{ $menu->icon ?: 'link' }}" class="w-[22px] h-[22px]"></i>
                    </span>
                    <span class="text-[10px] leading-none transition-colors {{ $isActive ? 'text-shopee dark:text-shopee-light font-bold' : 'text-gray-500 dark:text-slate-400 font-medium' }}">{{ $menu->title }}</span>
                </a>
            @endforeach
            <!-- Thêm (Menu cố định ở vị trí cuối cùng) -->
            @php
                $moreActive = request()->routeIs('dashboard') || request()->routeIs('profile') || request()->routeIs('referrals') || request()->routeIs('activity.logs') || request()->routeIs('notifications');
            @endphp
            <button @click="mobileDashboardMenuOpen = !mobileDashboardMenuOpen"
                    class="group relative flex flex-col items-center justify-center gap-1 select-none transition-transform active:scale-90">
                <span class="flex items-center justify-center w-12 h-7 rounded-full transition-all duration-200"
                      :class="mobileDashboardMenuOpen ? 'bg-shopee/10 dark:bg-shopee/20 text-shopee dark:text-shopee-light' : '{{ $moreActive ? 'bg-shopee/10 dark:bg-shopee/20 text-shopee dark:text-shopee-light' : 'text-gray-400 dark:text-slate-400 group-hover:text-gray-600 dark:group-hover:text-slate-200' }}'">
                    <i data-lucide="menu" class="w-[22px] h-[22px]"></i>
                </span>
                <span class="text-[10px] leading-none transition-colors {{ $moreActive ? 'text-shopee dark:text-shopee-light font-bold' : 'text-gray-500 dark:text-slate-400 font-medium' }}">{{ __('Thêm') }}</span>
            </button>
        </div>
        @else
        @php
            $gridColsClass = 'grid-cols-' . $bottomActiveMenus->count();
        @endphp
        <div class="grid {{ $gridColsClass }} h-[68px] px-1.5 pt-1.5">
            @foreach($bottomActiveMenus as $menu)
                @php
                    $isActive = false;
                    if (isset($menu->active_route)) {
                        $isActive = request()->routeIs($menu->active_route);
                    } else {
                        $isActive = $isMenuHtmlActive($menu->url);
                    }
                @endphp
                <a href="{{ $menu->url }}"
                   target="{{ $menu->target ?? '_self' }}"
                   class="group relative flex flex-col items-center justify-center gap-1 select-none transition-transform active:scale-90">
                    @if($isActive)
                        <span class="absolute top-0 w-8 h-1 rounded-full bg-gradient-to-r from-shopee to-shopee-light"></span>
                    @endif
                    <span class="flex items-center justify-center w-12 h-7 rounded-full transition-all duration-200 {{ $isActive ? 'bg-shopee/10 dark:bg-shopee/20 text-shopee dark:text-shopee-light' : 'text-gray-400 dark:text-slate-400 group-hover:text-gray-600 dark:group-hover:text-slate-200' }}">
                        <i data-lucide="{{ $menu->icon ?: 'link' }}" class="w-[22px] h-[22px]"></i>
                    </span>
                    <span class="text-[10px] leading-none transition-colors {{ $isActive ? 'text-shopee dark:text-shopee-light font-bold' : 'text-gray-500 dark:text-slate-400 font-medium' }}">{{ $menu->title }}</span>
                </a>
            @endforeach
        </div>
        @endauth
    </div>

    <!-- BOTTOM SHEET MENU FOR MOBILE DASHBOARD (WEBAPP STYLE) -->
    @auth
    <div x-show="mobileDashboardMenuOpen"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-50 bg-black/60 backdrop-blur-sm md:hidden"
        @click="mobileDashboardMenuOpen = false"
        x-cloak>

        <div x-show="mobileDashboardMenuOpen"
            x-transition:enter="transition ease-out duration-300 transform translate-y-full"
            x-transition:enter-end="transform translate-y-0"
            x-transition:leave="transition ease-in duration-200 transform translate-y-0"
            x-transition:leave-end="transform translate-y-full"
            class="absolute bottom-0 left-0 right-0 bg-white dark:bg-slate-900 rounded-t-[32px] p-6 space-y-6 shadow-[0_-8px_30px_rgb(0,0,0,0.12)] max-h-[85vh] overflow-y-auto"
            @click.stop>

            <!-- Drag Handle -->
            <div class="w-12 h-1.5 bg-gray-200 dark:bg-slate-700 rounded-full mx-auto mb-2"></div>

            <!-- User Summary Info -->
            <div class="flex items-center justify-between border-b border-gray-100 dark:border-slate-800 pb-5">
                <div class="flex items-center gap-4 truncate">
                    <div class="w-12 h-12 rounded-2xl bg-shopee/10 text-shopee flex items-center justify-center font-bold text-lg shrink-0">
                        {{ substr(auth()->user()->name, 0, 1) }}
                    </div>
                    <div class="truncate">
                        <h3 class="font-bold text-gray-900 dark:text-white truncate">{{ auth()->user()->name }}</h3>
                        <span class="text-xs text-gray-400 font-semibold block">Mã giới thiệu: {{ auth()->user()->referral_code }}</span>
                    </div>
                </div>

                <!-- Nút đóng Menu Mobile Dashboard -->
                <button @click="mobileDashboardMenuOpen = false" 
                        class="w-9 h-9 rounded-xl bg-gray-50 hover:bg-gray-100 dark:bg-slate-800 dark:hover:bg-slate-700/80 text-gray-400 hover:text-gray-600 dark:text-slate-400 dark:hover:text-white flex items-center justify-center transition-all focus:outline-none shrink-0"
                        title="{{ __('Đóng menu') }}">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                </button>
            </div>

            <!-- Navigation Links -->
            @php
                // Tự động đồng bộ hóa icon và tiêu đề của menu hệ thống trên mobile từ database (nếu có cấu hình trùng URL)
                $getSyncMenu = function($url, $defaultIcon, $defaultTitle) use ($groupedMenus) {
                    if (isset($groupedMenus)) {
                        foreach ($groupedMenus as $pos => $menus) {
                            foreach ($menus as $mItem) {
                                $mUrl = trim($mItem->url, '/');
                                $tUrl = trim($url, '/');
                                if ($mUrl === $tUrl || url($mUrl) === url($tUrl)) {
                                    return [
                                        'icon' => $mItem->icon ?: $defaultIcon,
                                        'title' => $mItem->title ?: $defaultTitle
                                    ];
                                }
                            }
                        }
                    }
                    return [
                        'icon' => $defaultIcon,
                        'title' => $defaultTitle
                    ];
                };

                $syncDashboard = $getSyncMenu(route('dashboard'), 'wallet', __('Ví của tôi'));
                $syncHistory = $getSyncMenu(route('cashback.history'), 'history', __('Lịch sử hoàn tiền'));
                // Đồng bộ hóa menu Sản phẩm đã lưu từ cơ sở dữ liệu nếu có, hoặc dùng giá trị mặc định
                $syncSavedProducts = $getSyncMenu(route('saved-products'), 'bookmark', __('Sản phẩm đã lưu'));
                // Đồng bộ hóa menu hướng dẫn sử dụng bot hoàn tiền từ cơ sở dữ liệu hoặc mặc định
                $syncBotGuide = $getSyncMenu(route('bot.guide'), 'bot', __('Bot hoàn tiền'));
                $syncCheckin = $getSyncMenu(route('checkin'), 'calendar-check', __('Điểm danh nhận xu'));
                $syncReferrals = $getSyncMenu(route('referrals'), 'link-2', __('Tiếp thị liên kết'));
                $syncWithdraw = $getSyncMenu(route('withdraw'), 'banknote', __('Yêu cầu rút tiền'));
                $syncGifts = $getSyncMenu(route('gifts.index'), 'gift', __('Đổi quà tặng'));
                $syncTasks = $getSyncMenu(route('tasks.index'), 'trophy', \App\Models\Setting::getVal('tasks_title') ?: __('Nhiệm vụ nhận thưởng'));
                $syncProfile = $getSyncMenu(route('profile'), 'user-cog', __('Thiết lập tài khoản'));
                $syncShortcuts = $getSyncMenu(route('shortcuts'), 'smartphone', __('Phím tắt iPhone'));
                $syncNotifications = $getSyncMenu(route('notifications'), 'bell', __('Thông báo'));
                $syncBalance = $getSyncMenu(route('balance.logs'), 'arrow-left-right', __('Biến động số dư'));
                $syncActivity = $getSyncMenu(route('activity.logs'), 'history', __('Nhật ký hoạt động'));
            @endphp
            <nav class="grid grid-cols-1 gap-2">
                <a href="{{ route('dashboard') }}"
                    @click="mobileDashboardMenuOpen = false"
                    class="flex items-center gap-3 px-4 py-3.5 text-sm font-semibold rounded-2xl transition-all {{ request()->routeIs('dashboard') ? 'bg-shopee/5 text-shopee dark:bg-shopee/10' : 'text-gray-700 dark:text-slate-300 hover:bg-gray-50 dark:hover:bg-slate-800/50' }}">
                    <i data-lucide="{{ $syncDashboard['icon'] }}" class="w-5 h-5 shrink-0 {{ request()->routeIs('dashboard') ? 'text-shopee' : 'text-gray-400' }}"></i>
                    <span>{{ $syncDashboard['title'] }}</span>
                </a>

                <a href="{{ route('cashback.history') }}"
                    @click="mobileDashboardMenuOpen = false"
                    class="flex items-center gap-3 px-4 py-3.5 text-sm font-semibold rounded-2xl transition-all {{ request()->routeIs('cashback.history') ? 'bg-shopee/5 text-shopee dark:bg-shopee/10' : 'text-gray-700 dark:text-slate-300 hover:bg-gray-50 dark:hover:bg-slate-800/50' }}">
                    <i data-lucide="{{ $syncHistory['icon'] }}" class="w-5 h-5 shrink-0 {{ request()->routeIs('cashback.history') ? 'text-shopee' : 'text-gray-400' }}"></i>
                    <span>{{ $syncHistory['title'] }}</span>
                </a>

                <!-- Liên kết điều hướng đến trang Sản phẩm đã lưu cho Mobile -->
                <a href="{{ route('saved-products') }}"
                    @click="mobileDashboardMenuOpen = false"
                    class="flex items-center gap-3 px-4 py-3.5 text-sm font-semibold rounded-2xl transition-all {{ request()->routeIs('saved-products') ? 'bg-shopee/5 text-shopee dark:bg-shopee/10' : 'text-gray-700 dark:text-slate-300 hover:bg-gray-50 dark:hover:bg-slate-800/50' }}">
                    <i data-lucide="{{ $syncSavedProducts['icon'] }}" class="w-5 h-5 shrink-0 {{ request()->routeIs('saved-products') ? 'text-shopee' : 'text-gray-400' }}"></i>
                    <span>{{ $syncSavedProducts['title'] }}</span>
                </a>

                {{-- Business Rule: Hiển thị liên kết hướng dẫn sử dụng bot hoàn tiền trên Mobile nếu có ít nhất 1 bot hoạt động --}}
                @if(\App\Models\BotConfig::where('is_enabled', true)->exists())
                <a href="{{ route('bot.guide') }}"
                    @click="mobileDashboardMenuOpen = false"
                    class="flex items-center gap-3 px-4 py-3.5 text-sm font-semibold rounded-2xl transition-all {{ request()->routeIs('bot.guide') ? 'bg-shopee/5 text-shopee dark:bg-shopee/10' : 'text-gray-700 dark:text-slate-300 hover:bg-gray-50 dark:hover:bg-slate-800/50' }}">
                    <i data-lucide="{{ $syncBotGuide['icon'] }}" class="w-5 h-5 shrink-0 {{ request()->routeIs('bot.guide') ? 'text-shopee' : 'text-gray-400' }}"></i>
                    <span>{{ $syncBotGuide['title'] }}</span>
                </a>
                @endif

                @if(\App\Models\Setting::getVal('daily_checkin_enabled', '1') === '1')
                <a href="{{ route('checkin') }}"
                    @click="mobileDashboardMenuOpen = false"
                    class="flex items-center gap-3 px-4 py-3.5 text-sm font-semibold rounded-2xl transition-all {{ request()->routeIs('checkin') ? 'bg-shopee/5 text-shopee dark:bg-shopee/10' : 'text-gray-700 dark:text-slate-300 hover:bg-gray-50 dark:hover:bg-slate-800/50' }}">
                    <i data-lucide="{{ $syncCheckin['icon'] }}" class="w-5 h-5 shrink-0 {{ request()->routeIs('checkin') ? 'text-shopee' : 'text-gray-400' }}"></i>
                    <span>{{ $syncCheckin['title'] }}</span>
                </a>
                @endif

                @if(\App\Models\Setting::getVal('referral_enabled', '1') === '1')
                <a href="{{ route('referrals') }}"
                    @click="mobileDashboardMenuOpen = false"
                    class="flex items-center gap-3 px-4 py-3.5 text-sm font-semibold rounded-2xl transition-all {{ request()->routeIs('referrals') ? 'bg-shopee/5 text-shopee dark:bg-shopee/10' : 'text-gray-700 dark:text-slate-300 hover:bg-gray-50 dark:hover:bg-slate-800/50' }}">
                    <i data-lucide="{{ $syncReferrals['icon'] }}" class="w-5 h-5 shrink-0 {{ request()->routeIs('referrals') ? 'text-shopee' : 'text-gray-400' }}"></i>
                    <span>{{ $syncReferrals['title'] }}</span>
                </a>
                @endif

                @if(\App\Models\Setting::getVal('withdrawal_enabled', '1') === '1')
                <a href="{{ route('withdraw') }}"
                    @click="mobileDashboardMenuOpen = false"
                    class="flex items-center gap-3 px-4 py-3.5 text-sm font-semibold rounded-2xl transition-all {{ request()->routeIs('withdraw') ? 'bg-shopee/5 text-shopee dark:bg-shopee/10' : 'text-gray-700 dark:text-slate-300 hover:bg-gray-50 dark:hover:bg-slate-800/50' }}">
                    <i data-lucide="{{ $syncWithdraw['icon'] }}" class="w-5 h-5 shrink-0 {{ request()->routeIs('withdraw') ? 'text-shopee' : 'text-gray-400' }}"></i>
                    <span>{{ $syncWithdraw['title'] }}</span>
                </a>
                @endif

                @if(\App\Models\Setting::getVal('gift_redemption_enabled', '0') === '1')
                <a href="{{ route('gifts.index') }}"
                    @click="mobileDashboardMenuOpen = false"
                    class="flex items-center gap-3 px-4 py-3.5 text-sm font-semibold rounded-2xl transition-all {{ request()->routeIs('gifts.index') ? 'bg-shopee/5 text-shopee dark:bg-shopee/10' : 'text-gray-700 dark:text-slate-300 hover:bg-gray-50 dark:hover:bg-slate-800/50' }}">
                    <i data-lucide="{{ $syncGifts['icon'] }}" class="w-5 h-5 shrink-0 {{ request()->routeIs('gifts.index') ? 'text-shopee' : 'text-gray-400' }}"></i>
                    <span>{{ $syncGifts['title'] }}</span>
                </a>
                @endif

                @if(\App\Models\Setting::getVal('tasks_enabled', '0') === '1')
                @php $pendingTasksMobile = auth()->check() ? \App\Models\UserTask::where('user_id', auth()->id())->where('status', 'completed')->count() : 0; @endphp
                <a href="{{ route('tasks.index') }}"
                    @click="mobileDashboardMenuOpen = false"
                    class="flex items-center justify-between px-4 py-3.5 text-sm font-semibold rounded-2xl transition-all {{ request()->routeIs('tasks.index') ? 'bg-shopee/5 text-shopee dark:bg-shopee/10' : 'text-gray-700 dark:text-slate-300 hover:bg-gray-50 dark:hover:bg-slate-800/50' }}">
                    <div class="flex items-center gap-3">
                        <i data-lucide="{{ $syncTasks['icon'] }}" class="w-5 h-5 shrink-0 {{ request()->routeIs('tasks.index') ? 'text-shopee' : 'text-gray-400' }}"></i>
                        <span>{{ $syncTasks['title'] }}</span>
                    </div>
                    @if($pendingTasksMobile > 0)
                        <span class="px-2 py-0.5 text-[10px] font-bold text-white bg-amber-500 rounded-full">{{ $pendingTasksMobile }}</span>
                    @endif
                </a>
                @endif

                <a href="{{ route('profile') }}"
                    @click="mobileDashboardMenuOpen = false"
                    class="flex items-center gap-3 px-4 py-3.5 text-sm font-semibold rounded-2xl transition-all {{ request()->routeIs('profile') ? 'bg-shopee/5 text-shopee dark:bg-shopee/10' : 'text-gray-700 dark:text-slate-300 hover:bg-gray-50 dark:hover:bg-slate-800/50' }}">
                    <i data-lucide="{{ $syncProfile['icon'] }}" class="w-5 h-5 shrink-0 {{ request()->routeIs('profile') ? 'text-shopee' : 'text-gray-400' }}"></i>
                    <span>{{ $syncProfile['title'] }}</span>
                </a>

                @if(\App\Models\Setting::getVal('ios_shortcut_status', '0') === '1')
                <a href="{{ route('shortcuts') }}"
                    @click="mobileDashboardMenuOpen = false"
                    class="flex items-center gap-3 px-4 py-3.5 text-sm font-semibold rounded-2xl transition-all {{ request()->routeIs('shortcuts') ? 'bg-shopee/5 text-shopee dark:bg-shopee/10' : 'text-gray-700 dark:text-slate-300 hover:bg-gray-50 dark:hover:bg-slate-800/50' }}">
                    <i data-lucide="{{ $syncShortcuts['icon'] }}" class="w-5 h-5 shrink-0 {{ request()->routeIs('shortcuts') ? 'text-shopee' : 'text-gray-400' }}"></i>
                    <span>{{ $syncShortcuts['title'] }}</span>
                </a>
                @endif

                <a href="{{ route('notifications') }}"
                    @click="mobileDashboardMenuOpen = false"
                    class="flex items-center justify-between px-4 py-3.5 text-sm font-semibold rounded-2xl transition-all {{ request()->routeIs('notifications') ? 'bg-shopee/5 text-shopee dark:bg-shopee/10' : 'text-gray-700 dark:text-slate-300 hover:bg-gray-50 dark:hover:bg-slate-800/50' }}">
                    <div class="flex items-center gap-3">
                        <i data-lucide="{{ $syncNotifications['icon'] }}" class="w-5 h-5 shrink-0 {{ request()->routeIs('notifications') ? 'text-shopee' : 'text-gray-400' }}"></i>
                        <span>{{ $syncNotifications['title'] }}</span>
                    </div>
                    @php
                    $sidebarUnreadCount = \App\Models\Notification::where('user_id', auth()->id())->where('is_read', false)->count();
                    @endphp
                    @if($sidebarUnreadCount > 0)
                    <span class="px-2.5 py-0.5 text-xs font-bold text-white bg-red-500 rounded-full">
                        {{ $sidebarUnreadCount }}
                    </span>
                    @endif
                </a>

                <a href="{{ route('balance.logs') }}"
                    @click="mobileDashboardMenuOpen = false"
                    class="flex items-center gap-3 px-4 py-3.5 text-sm font-semibold rounded-2xl transition-all {{ request()->routeIs('balance.logs') ? 'bg-shopee/5 text-shopee dark:bg-shopee/10' : 'text-gray-700 dark:text-slate-300 hover:bg-gray-50 dark:hover:bg-slate-800/50' }}">
                    <i data-lucide="{{ $syncBalance['icon'] }}" class="w-5 h-5 shrink-0 {{ request()->routeIs('balance.logs') ? 'text-shopee' : 'text-gray-400' }}"></i>
                    <span>{{ $syncBalance['title'] }}</span>
                </a>

                <a href="{{ route('activity.logs') }}"
                    @click="mobileDashboardMenuOpen = false"
                    class="flex items-center gap-3 px-4 py-3.5 text-sm font-semibold rounded-2xl transition-all {{ request()->routeIs('activity.logs') ? 'bg-shopee/5 text-shopee dark:bg-shopee/10' : 'text-gray-700 dark:text-slate-300 hover:bg-gray-50 dark:hover:bg-slate-800/50' }}">
                    <i data-lucide="{{ $syncActivity['icon'] }}" class="w-5 h-5 shrink-0 {{ request()->routeIs('activity.logs') ? 'text-shopee' : 'text-gray-400' }}"></i>
                    <span>{{ $syncActivity['title'] }}</span>
                </a>
            </nav>

            <div class="border-t border-gray-100 dark:border-slate-800 pt-4 pb-6">
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="w-full flex items-center gap-3 px-4 py-3.5 text-sm font-semibold text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-950/20 rounded-2xl transition-all">
                        <i data-lucide="log-out" class="w-5 h-5 shrink-0"></i>
                        <span>{{ __('Đăng xuất ví') }}</span>
                    </button>
                </form>
            </div>
        </div>
    </div>
    @endauth
    @endif

    <!-- BACK TO TOP BUTTON WITH CIRCULAR PROGRESS INDICATOR -->
    <div x-data="{ 
            showButton: false, 
            progress: 0,
            init() {
                window.addEventListener('scroll', () => {
                    const scrollPercent = (window.scrollY / (document.documentElement.scrollHeight - window.innerHeight)) * 100;
                    this.progress = isNaN(scrollPercent) ? 0 : Math.min(scrollPercent, 100);
                    this.showButton = window.scrollY > 300;
                });
            },
            scrollToTop() {
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
         }"
        x-init="init()"
        x-show="showButton"
        x-transition:enter="transition ease-out duration-300 transform"
        x-transition:enter-start="opacity-0 translate-y-8 scale-75"
        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
        x-transition:leave="transition ease-in duration-200 transform"
        x-transition:leave-start="opacity-100 translate-y-0 scale-100"
        x-transition:leave-end="opacity-0 translate-y-8 scale-75"
        class="fixed {{ session('from_app') ? 'bottom-6 md:bottom-8' : 'bottom-20 md:bottom-8' }} right-6 md:right-8 z-40"
        x-cloak>

        <button @click="scrollToTop()"
            class="relative flex items-center justify-center w-12 h-12 rounded-full bg-white/80 dark:bg-slate-900/80 backdrop-blur-md border border-gray-100 dark:border-slate-800 shadow-lg shadow-gray-200/50 dark:shadow-none hover:-translate-y-1 hover:bg-shopee dark:hover:bg-shopee text-shopee dark:text-shopee-light hover:text-white dark:hover:text-white hover:border-shopee dark:hover:border-shopee transition-all duration-300 group"
            title="Trở lại đầu trang">

            <!-- SVG Circular Progress -->
            <svg class="absolute inset-0 w-full h-full transform -rotate-90 pointer-events-none" viewBox="0 0 36 36">
                <path class="text-gray-100 dark:text-slate-800" stroke-width="1.5" stroke="currentColor" fill="none" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"></path>
                <path class="text-shopee group-hover:text-white transition-all duration-300" stroke-width="1.8" :stroke-dasharray="progress + ', 100'" stroke-linecap="round" stroke="currentColor" fill="none" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"></path>
            </svg>

            <!-- Up Arrow Icon -->
            <i data-lucide="arrow-up" class="w-5 h-5 relative z-10"></i>
        </button>
    </div>

    <!-- 4. GLOBAL TOAST SYSTEM -->
    @include('components.toast.manager')

    <!-- Trigger initial session messages & Next.js page load transitions -->
    <script src="{{ asset('js/app-custom.js') }}?v=1.0.4"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            @if(session('success'))
            window.dispatchEvent(new CustomEvent('toast', {
                detail: {
                    text: "{{ session('success') }}",
                    type: 'success'
                }
            }));
            @endif
            @if(session('error'))
            window.dispatchEvent(new CustomEvent('toast', {
                detail: {
                    text: "{{ session('error') }}",
                    type: 'error'
                }
            }));
            @endif
            @if($errors->any())
            window.dispatchEvent(new CustomEvent('toast', {
                detail: {
                    text: "{{ $errors->first() }}",
                    type: 'error'
                }
            }));
            @endif
        });
    </script>
    <!-- 4. GỢI Ý THÊM WEBSITE VÀO MÀN HÌNH CHÍNH (PWA ADD TO HOME SCREEN PROMPT) -->
    <!-- Lý do: Chỉ hiển thị popup gợi ý cài đặt PWA khi Admin cấu hình bật (pwa_install_prompt_status = '1') -->
    @if(\App\Models\Setting::getVal('pwa_install_prompt_status', '1') === '1')
    <!-- Widget nổi hỗ trợ hiển thị thông báo hướng dẫn cài đặt website làm ứng dụng di động -->
    <div id="pwa-install-prompt" class="fixed bottom-[5.5rem] md:bottom-6 left-1/2 -translate-x-1/2 w-[calc(100%-2rem)] max-w-sm bg-white/95 dark:bg-slate-900/95 backdrop-blur-md border border-gray-200/50 dark:border-slate-800/80 shadow-2xl rounded-2xl p-4 z-40 hidden transition-all duration-300 transform translate-y-8 opacity-0">
        <div class="relative flex items-start gap-3">
            <!-- Nút đóng thông báo nhanh -->
            <button onclick="dismissPWAPrompt()" class="absolute -top-1 -right-1 p-1 text-gray-400 hover:text-gray-600 dark:hover:text-slate-350 transition-colors" title="{{ __('Đóng') }}">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>

            <!-- Ảnh đại diện website (Favicon) hoặc Icon thay thế -->
            <div class="w-12 h-12 rounded-xl bg-gradient-to-tr from-shopee to-shopee-light flex items-center justify-center text-white shrink-0 shadow-md shadow-shopee/10 overflow-hidden">
                @if(!empty($siteFavicon))
                                <img src="{{ $siteFaviconUrl }}" alt="Biểu tượng ứng dụng Mê Sale" width="48" height="48" loading="lazy" decoding="async" class="w-full h-full object-cover">
                @else
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                    </svg>
                @endif
            </div>

            <!-- Nội dung văn bản hướng dẫn -->
            <div class="flex-grow pr-5">
                <h4 class="text-sm font-bold text-gray-800 dark:text-slate-200 leading-tight">{{ __('Cài đặt ứng dụng') }}</h4>
                <p class="text-xs text-gray-500 dark:text-slate-400 mt-1 leading-normal">{{ __('Thêm :site vào màn hình chính để truy cập nhanh chóng và tiện lợi hơn.', ['site' => $siteName]) }}</p>
                
                <!-- Khu vực nút cài đặt tự động cho Android (Chrome / Firefox) -->
                <div id="pwa-android-install" class="mt-3 hidden">
                    <button onclick="triggerPWAInstall()" class="w-full inline-flex items-center justify-center gap-1.5 px-3 py-2 text-xs font-bold text-white bg-gradient-to-r from-shopee to-shopee-light hover:brightness-110 rounded-xl transition-all shadow-md shadow-shopee/10">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                        </svg>
                        {{ __('Cài đặt ngay') }}
                    </button>
                </div>

                <!-- Hướng dẫn thủ công cho hệ điều hành iOS (Safari) -->
                <div id="pwa-ios-instructions" class="mt-3 text-[11px] text-gray-600 dark:text-slate-400 bg-gray-50 dark:bg-slate-800/40 p-2.5 rounded-xl border border-gray-150/60 dark:border-slate-800/60 hidden">
                    <div class="flex items-start gap-1.5 mb-2">
                        <span class="flex items-center justify-center w-5 h-5 rounded-full bg-shopee/10 text-shopee font-bold text-[9px] shrink-0 mt-0.5">1</span>
                        <span>{!! __('Nhấp vào biểu tượng chia sẻ <strong>Chia sẻ</strong> :icon ở thanh công cụ Safari.', ['icon' => '<svg xmlns="http://www.w3.org/2000/svg" class="inline-block w-4 h-4 mx-0.5 align-middle text-blue-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8"/><polyline points="16 6 12 2 8 6"/><line x1="12" y1="2" x2="12" y2="15"/></svg>']) !!}</span>
                    </div>
                    <div class="flex items-start gap-1.5">
                        <span class="flex items-center justify-center w-5 h-5 rounded-full bg-shopee/10 text-shopee font-bold text-[9px] shrink-0 mt-0.5">2</span>
                        <span>{!! __('Chọn mục <strong>Thêm vào MH chính</strong> :icon từ danh sách hiện lên.', ['icon' => '<svg xmlns="http://www.w3.org/2000/svg" class="inline-block w-4 h-4 mx-0.5 align-middle text-gray-700 dark:text-slate-350" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg>']) !!}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Script điều khiển hiển thị và logic cài đặt PWA -->
    <script>
        let deferredPrompt = null;
        const pwaPrompt = document.getElementById('pwa-install-prompt');
        const androidInstallDiv = document.getElementById('pwa-android-install');
        const iosInstructionsDiv = document.getElementById('pwa-ios-instructions');

        // Hàm kiểm tra xem website đã được mở dưới dạng App độc lập (Standalone) chưa
        function isStandalone() {
            return window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
        }

        // Hàm kiểm tra thiết bị có phải hệ điều hành iOS (iPhone/iPad) hay không
        function isIOS() {
            return /iPhone|iPad|iPod/i.test(navigator.userAgent);
        }

        // Hàm kiểm tra xem người dùng có đang truy cập bằng trình duyệt Safari hay không
        function isSafari() {
            const ua = navigator.userAgent;
            return /Safari/i.test(ua) && !/CriOS/i.test(ua) && !/FxiOS/i.test(ua) && !/OxiOS/i.test(ua) && !/mercury/i.test(ua);
        }

        // Hiển thị popup gợi ý với hiệu ứng chuyển động mượt mà
        function showPWAPrompt() {
            if (pwaPrompt) {
                pwaPrompt.classList.remove('hidden');
                setTimeout(() => {
                    pwaPrompt.classList.remove('translate-y-8', 'opacity-0');
                }, 50);
            }
        }

        // Đóng popup gợi ý cài đặt và lưu trạng thái tắt tạm thời
        function dismissPWAPrompt() {
            if (pwaPrompt) {
                pwaPrompt.classList.add('translate-y-8', 'opacity-0');
                setTimeout(() => {
                    pwaPrompt.classList.add('hidden');
                }, 300);
                // Lưu thời điểm tắt gợi ý nhằm chặn không làm phiền người dùng trong 7 ngày tới
                localStorage.setItem('pwa_prompt_dismissed_time', Date.now());
            }
        }

        // Lắng nghe sự kiện trước khi hiển thị cài đặt mặc định trên Android Chrome/Firefox
        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            deferredPrompt = e;

            const dismissedTime = localStorage.getItem('pwa_prompt_dismissed_time');
            const oneWeek = 7 * 24 * 60 * 60 * 1000;
            
            // Hiển thị gợi ý nếu chưa cài đặt và chưa từng đóng trong vòng 7 ngày gần đây
            if (!isStandalone() && (!dismissedTime || (Date.now() - dismissedTime > oneWeek))) {
                if (androidInstallDiv) androidInstallDiv.classList.remove('hidden');
                if (iosInstructionsDiv) iosInstructionsDiv.classList.add('hidden');
                
                // Trì hoãn hiển thị popup sau 3 giây từ lúc tải xong trang
                setTimeout(showPWAPrompt, 3000);
            }
        });

        // Gọi lệnh kích hoạt popup cài đặt gốc của hệ điều hành Android
        function triggerPWAInstall() {
            if (deferredPrompt) {
                deferredPrompt.prompt();
                deferredPrompt.userChoice.then((choiceResult) => {
                    if (choiceResult.outcome === 'accepted') {
                        console.log('User accepted the PWA install prompt');
                    }
                    deferredPrompt = null;
                    dismissPWAPrompt();
                });
            }
        }

        // Khởi tạo kiểm tra và xử lý gợi ý cài đặt riêng cho iOS Safari
        document.addEventListener('DOMContentLoaded', () => {
            const dismissedTime = localStorage.getItem('pwa_prompt_dismissed_time');
            const oneWeek = 7 * 24 * 60 * 60 * 1000;

            // Chỉ kích hoạt nếu là thiết bị iOS, chạy Safari, chưa cài đặt và chưa tắt gợi ý trong 7 ngày
            if (isIOS() && isSafari() && !isStandalone() && (!dismissedTime || (Date.now() - dismissedTime > oneWeek))) {
                if (androidInstallDiv) androidInstallDiv.classList.add('hidden');
                if (iosInstructionsDiv) iosInstructionsDiv.classList.remove('hidden');

                // Trì hoãn hiển thị popup sau 3 giây
                setTimeout(showPWAPrompt, 3000);
            }
        });
    </script>
    @endif

    <script>
        // Đăng ký Service Worker để đáp ứng điều kiện cài đặt PWA và nhận dữ liệu từ Share Sheet
        // Lý do: Cần chạy độc lập ngoài điều kiện bật/tắt popup để người dùng cài đặt thủ công vẫn nhận được Share Sheet và caching.
        document.addEventListener('DOMContentLoaded', () => {
            if ('serviceWorker' in navigator) {
                navigator.serviceWorker.register('/sw.js?v=1.0.3')
                    .then((registration) => {
                        console.log('Service Worker registered successfully with scope:', registration.scope);
                    })
                    .catch((error) => {
                        console.error('Service Worker registration failed:', error);
                    });
            }
        });
    </script>

    @yield('scripts')
    <!-- Custom JS Body tùy biến được cấu hình từ Admin Panel -->
    @if(!empty($customJsBody))
        {!! $customJsBody !!}
    @endif
    <!-- BONG BÓNG LIÊN HỆ NỔI (FLOATING CONTACT BUBBLE) -->
    @if(\App\Models\Setting::getVal('show_contact_bubble', '1') == '1')
        @php
            $fbLink = \App\Models\Setting::getVal('facebook_link');
            $teleLink = \App\Models\Setting::getVal('telegram_link');
            $zlLink = \App\Models\Setting::getVal('zalo_link');
            $hotline = \App\Models\Setting::getVal('support_hotline');
            $bubblePosition = \App\Models\Setting::getVal('contact_bubble_position', 'bottom-left');
            
            // Chuyển đổi link Facebook sang link chat Messenger m.me
            $messengerLink = $fbLink;
            if (!empty($fbLink)) {
                if (preg_match('/facebook\.com\/([a-zA-Z0-9\.]+)\/?$/', $fbLink, $matches)) {
                    $messengerLink = 'https://m.me/' . $matches[1];
                }
            }

            // Thiết lập class vị trí (đẩy lên cao nếu ở bên phải để tránh đè nút Back to Top) và hướng hiển thị của Tooltip dựa theo cấu hình
            if ($bubblePosition === 'bottom-right') {
                $positionClass = session('from_app') 
                    ? 'right-6 md:right-8 bottom-20 md:bottom-24' 
                    : 'right-6 md:right-8 bottom-36 md:bottom-24';
                $tooltipClass = 'right-full mr-3';
            } else {
                $positionClass = session('from_app') 
                    ? 'left-6 md:left-8 bottom-6 md:bottom-8' 
                    : 'left-6 md:left-8 bottom-20 md:bottom-8';
                $tooltipClass = 'left-full ml-3';
            }
        @endphp

        @if(!empty($fbLink) || !empty($teleLink) || !empty($zlLink) || !empty($hotline))
        <div x-data="{ open: false }" class="fixed {{ $positionClass }} z-40 flex flex-col-reverse items-center gap-3">
            <!-- Nút kích hoạt chính (Main Toggle Button) -->
            <button @click="open = !open" 
                    class="w-12 h-12 rounded-full bg-gradient-to-tr from-shopee to-shopee-light hover:brightness-110 text-white shadow-lg shadow-shopee/25 flex items-center justify-center transition-all duration-300 relative group focus:outline-none"
                    :class="{ 'rotate-135': open }">
                <!-- Ripple Effect thu hút chú ý -->
                <span class="absolute inset-0 rounded-full bg-shopee/30 animate-ping opacity-75 group-hover:hidden" x-show="!open"></span>
                
                <!-- Icon Chat / Close -->
                <i data-lucide="message-circle" class="w-6 h-6 transition-all duration-300 absolute" :class="{ 'opacity-0 scale-75': open, 'opacity-100 scale-100': !open }"></i>
                <i data-lucide="x" class="w-5 h-5 transition-all duration-300 absolute" :class="{ 'opacity-100 scale-100': open, 'opacity-0 scale-75': !open }"></i>
            </button>

            <!-- Danh sách các nút liên hệ con -->
            <div x-show="open"
                 x-transition:enter="transition ease-out duration-300 transform"
                 x-transition:enter-start="opacity-0 translate-y-8 scale-75"
                 x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                 x-transition:leave="transition ease-in duration-200 transform"
                 x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                 x-transition:leave-end="opacity-0 translate-y-8 scale-75"
                 class="flex flex-col items-center gap-2.5 mb-1"
                 x-cloak>
                
                <!-- Hotline Gọi điện -->
                @if(!empty($hotline))
                <a href="tel:{{ str_replace(' ', '', $hotline) }}" 
                   class="w-10 h-10 rounded-full bg-emerald-500 hover:bg-emerald-600 text-white flex items-center justify-center shadow-md hover:-translate-y-0.5 transition-all duration-200 relative group" 
                   title="{{ __('Gọi Hotline: :phone', ['phone' => $hotline]) }}">
                    <i data-lucide="phone" class="w-4 h-4"></i>
                    <span class="absolute {{ $tooltipClass }} px-2 py-1.5 bg-slate-900 text-white text-[9px] font-bold rounded-lg opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap shadow-md pointer-events-none border border-slate-800">{{ __('Hotline: :phone', ['phone' => $hotline]) }}</span>
                </a>
                @endif

                <!-- Zalo Chat -->
                @if(!empty($zlLink))
                <a href="{{ $zlLink }}" 
                   target="_blank" 
                   rel="noopener noreferrer" 
                   class="w-10 h-10 rounded-full bg-blue-500 hover:bg-blue-600 text-white flex items-center justify-center shadow-md hover:-translate-y-0.5 transition-all duration-200 relative group" 
                   title="{{ __('Chat qua Zalo') }}">
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/>
                        <path d="M9 10h6l-6 5h6" stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"/>
                    </svg>
                    <span class="absolute {{ $tooltipClass }} px-2 py-1.5 bg-slate-900 text-white text-[9px] font-bold rounded-lg opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap shadow-md pointer-events-none border border-slate-800">{{ __('Hỗ trợ Zalo') }}</span>
                </a>
                @endif

                <!-- Facebook Messenger -->
                @if(!empty($fbLink))
                <a href="{{ $messengerLink }}" 
                   target="_blank" 
                   rel="noopener noreferrer" 
                   class="w-10 h-10 rounded-full bg-gradient-to-tr from-blue-600 to-cyan-500 hover:brightness-110 text-white flex items-center justify-center shadow-md hover:-translate-y-0.5 transition-all duration-200 relative group" 
                   title="{{ __('Chat qua Messenger') }}">
                    <svg class="w-5 h-5 fill-current" viewBox="0 0 24 24">
                        <path d="M12 2C6.477 2 2 6.145 2 11.258c0 2.914 1.448 5.518 3.7 7.22v3.666a.75.75 0 0 0 1.157.636l4.086-2.502a11.238 11.238 0 0 0 1.057.05c5.523 0 10-4.146 10-9.258C22 6.145 17.523 2 12 2zm1.025 12.115l-2.26-2.42-4.4 2.42 4.832-5.137 2.26 2.42 4.4-2.42-4.832 5.137z"/>
                    </svg>
                    <span class="absolute {{ $tooltipClass }} px-2 py-1.5 bg-slate-900 text-white text-[9px] font-bold rounded-lg opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap shadow-md pointer-events-none border border-slate-800">{{ __('Chat Messenger') }}</span>
                </a>
                @endif

                <!-- Telegram -->
                @if(!empty($teleLink))
                <a href="{{ $teleLink }}" 
                   target="_blank" 
                   rel="noopener noreferrer" 
                   class="w-10 h-10 rounded-full bg-sky-500 hover:bg-sky-600 text-white flex items-center justify-center shadow-md hover:-translate-y-0.5 transition-all duration-200 relative group" 
                   title="{{ __('Hỗ trợ Telegram') }}">
                    <svg class="w-5 h-5 fill-current" viewBox="0 0 24 24">
                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm4.64 6.8c-.15 1.58-.8 5.42-1.13 7.19-.14.75-.42 1-.68 1.03-.58.05-1.02-.38-1.58-.75-.88-.58-1.38-.94-2.23-1.5-.99-.65-.35-1.01.22-1.59.15-.15 2.71-2.48 2.76-2.69.01-.03.01-.14-.07-.2-.08-.06-.19-.04-.27-.02-.12.02-1.96 1.24-5.54 3.65-.52.36-.99.53-1.41.52-.46-.01-1.35-.26-2.01-.48-.81-.27-1.46-.42-1.4-.88.03-.24.37-.49 1.02-.75 3.99-1.74 6.66-2.88 8-3.44 3.81-1.58 4.6-1.86 5.12-1.87.11 0 .37.03.54.17.14.12.18.28.2.45-.02.07-.02.16-.03.25z"/>
                    </svg>
                    <span class="absolute {{ $tooltipClass }} px-2 py-1.5 bg-slate-900 text-white text-[9px] font-bold rounded-lg opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap shadow-md pointer-events-none border border-slate-800">{{ __('Kênh Telegram') }}</span>
                </a>
                @endif

            </div>
        </div>
        @endif
    @endif

    <!-- Spotlight Search cho giao diện thành viên & trang khách -->
    @include('components.search_spotlight_client')

    @stack('scripts')
</body>

</html>
