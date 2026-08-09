@extends('layouts.app')

@section('title', 'Hướng Dẫn Bot Hoàn Tiền | Mê Sale')

@section('meta_description', 'Cách dùng Zalo Bot và Telegram Bot để tạo link hoàn tiền Shopee, TikTok Shop nhanh chóng trên Mê Sale. Gửi link sản phẩm và nhận link mua ngay.')

@section('og_title', __('Hướng Dẫn Bot Hoàn Tiền Shopee - Zalo & Telegram'))

@section('canonical_url', route('bot.guide'))

@section('seo_schema')
{{-- Cấu trúc schema SEO để tối ưu hóa tìm kiếm FAQ và đường dẫn Breadcrumb trên Google --}}
<script type="application/ld+json">
{!! json_encode([
    '@context' => 'https://schema.org',
    '@graph' => [
        [
            '@type' => 'BreadcrumbList',
            '@id' => route('bot.guide') . '#breadcrumb',
            'itemListElement' => [
                ['@type' => 'ListItem', 'position' => 1, 'name' => __('Trang chủ'), 'item' => route('home')],
                ['@type' => 'ListItem', 'position' => 2, 'name' => __('Hướng dẫn Bot'), 'item' => route('bot.guide')],
            ],
        ],
        [
            '@type' => 'FAQPage',
            '@id' => route('bot.guide') . '#faq',
            'mainEntity' => [
                [
                    '@type' => 'Question',
                    'name' => __('Bot hoàn tiền Shopee là gì?'),
                    'acceptedAnswer' => ['@type' => 'Answer', 'text' => __('Bot hoàn tiền là chatbot tự động trên Zalo và Telegram. Bạn chỉ cần gửi link sản phẩm Shopee hoặc TikTok Shop vào bot, hệ thống sẽ tự động tạo link affiliate và gửi lại ngay lập tức.')],
                ],
                [
                    '@type' => 'Question',
                    'name' => __('Sử dụng bot có cần đăng ký tài khoản không?'),
                    'acceptedAnswer' => ['@type' => 'Answer', 'text' => __('Bạn cần có tài khoản thành viên và liên kết ID Zalo/Telegram của mình trong phần Hồ sơ để hoàn tiền được ghi nhận vào đúng tài khoản của bạn.')],
                ],
            ],
        ],
    ],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}
</script>
@endsection

@section('content')
@php
    // Kiểm tra xem trang này có hiển thị bố cục Dashboard Sidebar hay không.
    // Business rule: Nếu người dùng đã đăng nhập và có ít nhất 1 trong 2 bot đang hoạt động,
    // ta sẽ render giao diện dạng Dashboard để tạo sự đồng bộ, dễ thao tác như các trang quản lý khác.
    $hasSidebar = auth()->check() && ($zaloConfig->is_enabled || $telegramConfig->is_enabled);
@endphp

<div class="px-4 mx-auto max-w-7xl sm:px-6 lg:px-8 py-6 sm:py-10">
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">

        @if($hasSidebar)
        <!-- Cột trái: Sidebar điều hướng của thành viên (chỉ hiển thị trên màn hình desktop khi đã đăng nhập) -->
        <div class="hidden lg:block lg:col-span-3">
            @include('dashboard.sidebar')
        </div>
        @endif

        <!-- Cột phải hoặc Cột chính: Nội dung hướng dẫn sử dụng bot -->
        <div class="{{ $hasSidebar ? 'lg:col-span-9' : 'lg:col-span-12 max-w-4xl mx-auto w-full' }} space-y-6 sm:space-y-8">
            {{-- Nhắc nhở thành viên bổ sung email để bảo vệ tài khoản --}}
            @include('components.email_update_notice')

            {{-- Khối tiêu đề trang được thiết kế sang trọng, hiện đại với hiệu ứng gradient --}}
            <div class="relative overflow-hidden bg-gradient-to-tr from-shopee/5 via-white to-orange-50/50 dark:from-slate-800/20 dark:via-slate-900 dark:to-slate-800/10 border border-orange-100/30 dark:border-slate-800/80 rounded-3xl p-6 sm:p-8 flex flex-col md:flex-row items-center gap-6 shadow-sm">
                <div class="w-14 h-14 sm:w-16 sm:h-16 rounded-2xl bg-gradient-to-tr from-shopee to-shopee-light text-white flex items-center justify-center shrink-0 shadow-lg shadow-shopee/20">
                    <i data-lucide="bot" class="w-7 h-7 sm:w-8 sm:h-8"></i>
                </div>
                <div class="text-center md:text-left space-y-1 flex-1">
                    <h1 class="text-lg sm:text-2xl font-extrabold text-gray-905 dark:text-white tracking-tight uppercase">
                        {{ __('Hướng Dẫn Sử Dụng Bot Hoàn Tiền') }}
                    </h1>
                    <p class="text-xs sm:text-sm text-gray-500 dark:text-slate-400 leading-relaxed">
                        {{ __('Gửi link sản phẩm Shopee hoặc TikTok Shop cho chatbot Zalo/Telegram để nhận link hoàn tiền chỉ trong vài giây. Tiện lợi, tự động và chính xác!') }}
                    </p>
                </div>
            </div>

            {{-- Khối 3 card giới thiệu lợi ích nổi bật của bot --}}
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="flex items-start gap-3.5 p-4 rounded-2xl bg-white dark:bg-slate-900 border border-gray-100 dark:border-slate-800/80 shadow-sm hover:shadow-md hover:border-orange-100 dark:hover:border-slate-700 transition-all duration-300 group">
                    <div class="w-9 h-9 rounded-xl bg-orange-50 dark:bg-orange-950/20 text-shopee dark:text-shopee-light flex items-center justify-center shrink-0 group-hover:scale-110 transition-transform">
                        <i data-lucide="zap" class="w-4.5 h-4.5"></i>
                    </div>
                    <div class="space-y-0.5">
                        <h4 class="text-xs font-bold text-gray-900 dark:text-white">{{ __('Tốc Độ Vượt Trội') }}</h4>
                        <p class="text-[10px] sm:text-[11px] text-gray-400 dark:text-slate-500 leading-relaxed">{{ __('Nhận link affiliate hoàn tiền lập tức sau 1 giây gửi link.') }}</p>
                    </div>
                </div>
                <div class="flex items-start gap-3.5 p-4 rounded-2xl bg-white dark:bg-slate-900 border border-gray-100 dark:border-slate-800/80 shadow-sm hover:shadow-md hover:border-orange-100 dark:hover:border-slate-700 transition-all duration-300 group">
                    <div class="w-9 h-9 rounded-xl bg-blue-50 dark:bg-blue-950/20 text-blue-500 dark:text-blue-400 flex items-center justify-center shrink-0 group-hover:scale-110 transition-transform">
                        <i data-lucide="smartphone" class="w-4.5 h-4.5"></i>
                    </div>
                    <div class="space-y-0.5">
                        <h4 class="text-xs font-bold text-gray-900 dark:text-white">{{ __('Cực Kỳ Tiện Lợi') }}</h4>
                        <p class="text-[10px] sm:text-[11px] text-gray-400 dark:text-slate-500 leading-relaxed">{{ __('Thao tác trực tiếp ngay trên các ứng dụng trò chuyện quen thuộc.') }}</p>
                    </div>
                </div>
                <div class="flex items-start gap-3.5 p-4 rounded-2xl bg-white dark:bg-slate-900 border border-gray-100 dark:border-slate-800/80 shadow-sm hover:shadow-md hover:border-orange-100 dark:hover:border-slate-700 transition-all duration-300 group">
                    <div class="w-9 h-9 rounded-xl bg-green-50 dark:bg-green-950/20 text-green-500 dark:text-green-400 flex items-center justify-center shrink-0 group-hover:scale-110 transition-transform">
                        <i data-lucide="coins" class="w-4.5 h-4.5"></i>
                    </div>
                    <div class="space-y-0.5">
                        <h4 class="text-xs font-bold text-gray-900 dark:text-white">{{ __('Ghi Nhận Tự Động') }}</h4>
                        <p class="text-[10px] sm:text-[11px] text-gray-400 dark:text-slate-500 leading-relaxed">{{ __('Hoa hồng cashback tự động ghi nhận và cộng thẳng vào ví cá nhân.') }}</p>
                    </div>
                </div>
            </div>

            {{-- Phần chính: Trình quản lý chọn Bot và hướng dẫn sử dụng chi tiết --}}
            @if($zaloConfig->is_enabled || $telegramConfig->is_enabled)
                <div x-data="{ tab: '{{ $zaloConfig->is_enabled ? 'zalo' : 'telegram' }}' }" class="space-y-6">
                    
                    {{-- Thanh chọn Tab chuyển đổi qua lại giữa Zalo và Telegram --}}
                    @if($zaloConfig->is_enabled && $telegramConfig->is_enabled)
                        <div class="flex gap-2 p-1.5 bg-gray-100 dark:bg-slate-800/40 rounded-2xl border border-gray-250/30 dark:border-slate-800/80 max-w-xs shadow-inner">
                            <button @click="tab = 'zalo'"
                                :class="tab === 'zalo' ? 'bg-white dark:bg-slate-900 text-blue-600 dark:text-blue-400 shadow-sm' : 'text-gray-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200'"
                                class="flex-1 flex items-center justify-center gap-1.5 py-2 px-3 rounded-xl font-bold text-xs transition-all duration-200">
                                <svg class="w-4 h-4 flex-shrink-0" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <rect width="48" height="48" rx="12" fill="#0068FF"/>
                                    <path d="M24 10C16.268 10 10 15.82 10 23c0 4.09 1.98 7.74 5.1 10.22L13.5 38l5.04-2.46C20.2 35.83 22.07 36 24 36c7.732 0 14-5.82 14-13S31.732 10 24 10Z" fill="white"/>
                                    <path d="M17 22h14M17 26h8" stroke="#0068FF" stroke-width="2" stroke-linecap="round"/>
                                </svg>
                                Zalo Bot
                            </button>
                            <button @click="tab = 'telegram'"
                                :class="tab === 'telegram' ? 'bg-white dark:bg-slate-900 text-sky-500 dark:text-sky-400 shadow-sm' : 'text-gray-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200'"
                                class="flex-1 flex items-center justify-center gap-1.5 py-2 px-3 rounded-xl font-bold text-xs transition-all duration-200">
                                <svg class="w-4 h-4 flex-shrink-0" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <rect width="48" height="48" rx="12" fill="#29A9EB"/>
                                    <path d="M10 24L35.5 13.5L27 35.5L21 28L10 24Z" fill="white"/>
                                    <path d="M21 28L27 22" stroke="#29A9EB" stroke-width="2" stroke-linecap="round"/>
                                </svg>
                                Telegram
                            </button>
                        </div>
                    @endif

                    {{-- HƯỚNG DẪN CHI TIẾT BẢN ZALO BOT --}}
                    @if($zaloConfig->is_enabled)
                        <div x-show="tab === 'zalo'" x-transition:enter="transition ease-out duration-200" class="space-y-6">
                            
                            {{-- Profile Bot & Connection Status --}}
                            <div class="bg-white dark:bg-slate-900 border border-gray-150 dark:border-slate-800/80 rounded-3xl p-5 shadow-sm space-y-4">
                                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 border-b border-gray-100 dark:border-slate-800 pb-5">
                                    <div class="flex items-center gap-3">
                                        <div class="w-12 h-12 rounded-2xl bg-blue-600/10 text-blue-600 flex items-center justify-center shrink-0 shadow-sm">
                                            <svg class="w-7 h-7" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <rect width="48" height="48" rx="12" fill="#0068FF"/>
                                                <path d="M24 10C16.268 10 10 15.82 10 23c0 4.09 1.98 7.74 5.1 10.22L13.5 38l5.04-2.46C20.2 35.83 22.07 36 24 36c7.732 0 14-5.82 14-13S31.732 10 24 10Z" fill="white"/>
                                            </svg>
                                        </div>
                                        <div class="truncate">
                                            <h2 class="font-extrabold text-gray-905 dark:text-white text-sm sm:text-base">{{ $zaloConfig->bot_name ?: 'Zalo Cashback Bot' }}</h2>
                                            @if(!empty($zaloConfig->bot_username))
                                                @php
                                                    // Kiểm tra xem bot_username đã được nhập dạng liên kết đầy đủ hay chỉ là ID.
                                                    $zaloLink = (str_starts_with($zaloConfig->bot_username, 'http://') || str_starts_with($zaloConfig->bot_username, 'https://'))
                                                        ? $zaloConfig->bot_username
                                                        : 'https://zalo.me/' . $zaloConfig->bot_username;
                                                @endphp
                                                <span class="text-xs text-gray-400 dark:text-slate-500 font-medium block mt-0.5">{{ __('Liên kết:') }} <a href="{{ $zaloLink }}" target="_blank" class="font-semibold text-blue-600 dark:text-blue-400 hover:underline truncate max-w-[200px] inline-block align-bottom">{{ $zaloConfig->bot_username }}</a></span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-3 w-full sm:w-auto shrink-0">
                                        @if(!empty($zaloConfig->extra['qr_code']))
                                            <div x-data="{ openQr: false }" class="relative w-full sm:w-auto">
                                                <button @click="openQr = !openQr" type="button" class="w-full sm:w-auto inline-flex items-center justify-center gap-1.5 px-4 py-2.5 bg-gray-100 hover:bg-gray-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-gray-700 dark:text-slate-300 font-bold rounded-xl text-xs shadow-sm border border-gray-200 dark:border-slate-700 transition-all active:scale-95">
                                                    <i data-lucide="qr-code" class="w-4 h-4"></i>
                                                    {{ __('Quét mã QR') }}
                                                </button>
                                                
                                                <!-- Popover QR Code -->
                                                <div x-show="openQr" @click.outside="openQr = false" x-transition class="absolute right-0 mt-2 bg-white dark:bg-slate-800 p-3 rounded-2xl border border-gray-200 dark:border-slate-700 shadow-xl z-20 w-48 text-center space-y-2" x-cloak>
                                                    <img src="{{ $zaloConfig->extra['qr_code'] }}" alt="QR Code Zalo Bot" class="w-full aspect-square object-contain rounded-xl border border-gray-100 dark:border-slate-700">
                                                    <span class="text-[10px] text-gray-500 dark:text-slate-400 block font-medium">{{ __('Quét mã bằng Zalo để nhắn tin') }}</span>
                                                </div>
                                            </div>
                                        @endif

                                        @if(!empty($zaloConfig->bot_username))
                                            <a href="{{ $zaloLink }}" target="_blank" rel="nofollow noopener"
                                                class="w-full sm:w-auto inline-flex items-center justify-center gap-1.5 px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl text-xs shadow-md shadow-blue-500/10 transition-all active:scale-95">
                                                <i data-lucide="message-square-plus" class="w-4 h-4"></i>
                                                {{ __('Mở Zalo Bot ngay') }}
                                            </a>
                                        @endif
                                    </div>
                                </div>

                                {{-- Trạng thái liên kết tài khoản --}}
                                @auth
                                    @php $zaloLinked = !empty(auth()->user()->bot_zalo_chat_id); @endphp
                                    <div class="p-4 rounded-2xl border {{ $zaloLinked ? 'border-green-200 dark:border-green-800/40 bg-green-50/50 dark:bg-green-950/10' : 'border-amber-200 dark:border-amber-800/40 bg-amber-50/50 dark:bg-amber-950/10' }} flex flex-col sm:flex-row sm:items-center justify-between gap-3 shadow-inner">
                                        <div class="flex items-start gap-2.5">
                                            <div class="p-1 rounded-full {{ $zaloLinked ? 'bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400' : 'text-amber-500 dark:text-amber-400 bg-amber-100 dark:bg-amber-900/30' }} shrink-0 mt-0.5">
                                                <i data-lucide="{{ $zaloLinked ? 'check-circle-2' : 'alert-circle' }}" class="w-4 h-4"></i>
                                            </div>
                                            <div class="space-y-0.5">
                                                <span class="text-xs font-bold text-gray-800 dark:text-white block">{{ $zaloLinked ? __('Tài khoản đã liên kết với Zalo Bot!') : __('Tài khoản chưa được liên kết với Zalo Bot!') }}</span>
                                                <span class="text-[11px] text-gray-500 dark:text-slate-400 block">{{ $zaloLinked ? __('Bạn đã sẵn sàng để gửi link và tích lũy hoàn tiền vào ví.') : __('Để nhận được cashback đúng tài khoản, bạn bắt buộc phải thực hiện liên kết trước khi mua hàng.') }}</span>
                                            </div>
                                        </div>
                                        @if(!$zaloLinked)
                                            <div class="space-y-2 shrink-0 w-full sm:w-auto">
                                                <span class="text-[10px] text-gray-400 dark:text-slate-500 block font-semibold">{{ __('Copy cú pháp này gửi vào Bot để liên kết:') }}</span>
                                                <div x-data="{ copied: false }" class="relative flex items-center bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 rounded-xl px-3 py-2 pr-12 font-mono text-xs text-gray-850 dark:text-slate-205">
                                                    <span>/link {{ auth()->user()->api_token }}</span>
                                                    <button @click="
                                                        navigator.clipboard.writeText('/link {{ auth()->user()->api_token }}');
                                                        copied = true;
                                                        setTimeout(() => copied = false, 2000);
                                                    " class="absolute right-1 top-1 bottom-1 w-7 h-7 rounded-lg bg-gray-50 dark:bg-slate-700 border border-gray-100 dark:border-slate-600 hover:text-shopee dark:hover:text-shopee-light flex items-center justify-center transition-all shadow-sm">
                                                        <i x-show="!copied" data-lucide="copy" class="w-3.5 h-3.5"></i>
                                                        <i x-show="copied" data-lucide="check" class="w-3.5 h-3.5 text-green-500" x-cloak></i>
                                                    </button>
                                                    <span x-show="copied" x-transition class="absolute right-10 top-1.5 bg-emerald-500 text-white text-[9px] font-bold px-1.5 py-0.5 rounded-md" x-cloak>
                                                        {{ __('Đã sao chép!') }}
                                                    </span>
                                                </div>
                                            </div>
                                        @else
                                            <a href="{{ route('profile') }}" class="inline-flex items-center gap-1 text-xs font-bold text-shopee hover:underline">
                                                {{ __('Quản lý liên kết') }} <i data-lucide="arrow-right" class="w-3 h-3"></i>
                                            </a>
                                        @endif
                                    </div>
                                @else
                                    {{-- CTA đăng nhập cho guest --}}
                                    <div class="p-4 rounded-2xl border border-blue-100 dark:border-slate-800 bg-blue-50/20 dark:bg-slate-900/30 flex flex-col sm:flex-row items-center justify-between gap-4">
                                        <div class="space-y-0.5">
                                            <span class="text-xs font-bold text-gray-905 dark:text-white block">{{ __('Bạn chưa đăng nhập!') }}</span>
                                            <span class="text-[11px] text-gray-500 dark:text-slate-400 block">{{ __('Hãy đăng nhập tài khoản để nhận mã liên kết và bắt đầu tích lũy hoàn tiền.') }}</span>
                                        </div>
                                        <a href="{{ route('login') }}" class="shrink-0 w-full sm:w-auto inline-flex items-center justify-center gap-1.5 px-4 py-2 bg-gradient-to-r from-shopee to-shopee-light text-white font-extrabold rounded-xl text-xs shadow-md shadow-shopee/10 transition-colors">
                                            <i data-lucide="log-in" class="w-3.5 h-3.5"></i>
                                            {{ __('Đăng nhập ngay') }}
                                        </a>
                                    </div>
                                @endauth
                            </div>

                            {{-- Hướng dẫn các bước sử dụng và Mockup Chat --}}
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="bg-white dark:bg-slate-900 border border-gray-150 dark:border-slate-800/80 rounded-3xl p-5 shadow-sm space-y-4">
                                    <h3 class="font-extrabold text-gray-900 dark:text-white text-xs sm:text-sm uppercase tracking-wider flex items-center gap-2">
                                        <i data-lucide="list-ordered" class="w-4 h-4 text-blue-600"></i>
                                        {{ __('Các bước thực hiện') }}
                                    </h3>
                                    <div class="relative pl-6 border-l border-blue-100 dark:border-slate-800 space-y-6">
                                        <div class="relative">
                                            <span class="absolute -left-[35px] top-0 flex items-center justify-center w-6 h-6 rounded-full bg-blue-600 text-white text-[10px] font-bold shadow-md">1</span>
                                            <h4 class="text-xs font-bold text-gray-950 dark:text-white">{{ __('Kết bạn & Nhắn tin') }}</h4>
                                            <p class="text-[11px] text-gray-400 dark:text-slate-500 leading-relaxed mt-0.5">{{ __('Mở Zalo, quét mã QR bên dưới hoặc click nút bên trên để nhắn tin với Bot.') }}</p>
                                            @if(!empty($zaloConfig->extra['qr_code']))
                                                <div class="mt-2.5 p-2 bg-gray-50 dark:bg-slate-950 rounded-2xl border border-gray-100 dark:border-slate-800/80 inline-block">
                                                    <img src="{{ $zaloConfig->extra['qr_code'] }}" alt="QR Code Zalo Bot" class="w-28 h-28 object-contain rounded-xl">
                                                </div>
                                            @endif
                                        </div>
                                        <div class="relative">
                                            <span class="absolute -left-[35px] top-0 flex items-center justify-center w-6 h-6 rounded-full bg-blue-600 text-white text-[10px] font-bold shadow-md">2</span>
                                            <h4 class="text-xs font-bold text-gray-950 dark:text-white">{{ __('Liên kết tài khoản') }}</h4>
                                            <p class="text-[11px] text-gray-400 dark:text-slate-500 leading-relaxed mt-0.5">{{ __('Gửi cú pháp /link kèm Khóa API Token cá nhân để lưu tài khoản vào Bot.') }}</p>
                                        </div>
                                        <div class="relative">
                                            <span class="absolute -left-[35px] top-0 flex items-center justify-center w-6 h-6 rounded-full bg-blue-600 text-white text-[10px] font-bold shadow-md">3</span>
                                            <h4 class="text-xs font-bold text-gray-950 dark:text-white">{{ __('Gửi link sản phẩm') }}</h4>
                                            <p class="text-[11px] text-gray-400 dark:text-slate-500 leading-relaxed mt-0.5">{{ __('Copy bất kỳ link sản phẩm Shopee/TikTok Shop nào bạn muốn mua dán vào Chat.') }}</p>
                                        </div>
                                        <div class="relative">
                                            <span class="absolute -left-[35px] top-0 flex items-center justify-center w-6 h-6 rounded-full bg-green-600 text-white text-[10px] font-bold shadow-md">
                                                <i data-lucide="check" class="w-3 h-3"></i>
                                            </span>
                                            <h4 class="text-xs font-bold text-gray-950 dark:text-white">{{ __('Mua sắm & Cashback') }}</h4>
                                            <p class="text-[11px] text-gray-400 dark:text-slate-500 leading-relaxed mt-0.5">{{ __('Bấm vào link rút gọn phản hồi từ Bot để mua hàng. Hệ thống tự cộng hoa hồng sau khi đơn hoàn tất.') }}</p>
                                        </div>
                                    </div>
                                </div>

                                {{-- Mô phỏng Chat Mockup Zalo --}}
                                <div class="bg-white dark:bg-slate-900 border border-gray-150 dark:border-slate-800/80 rounded-3xl p-5 shadow-sm space-y-4">
                                    <h3 class="font-extrabold text-gray-900 dark:text-white text-xs sm:text-sm uppercase tracking-wider flex items-center gap-2">
                                        <i data-lucide="message-square" class="w-4 h-4 text-blue-600"></i>
                                        {{ __('Trải nghiệm thực tế từ Bot') }}
                                    </h3>
                                    
                                    <div class="relative bg-slate-50 dark:bg-slate-950 border border-gray-100 dark:border-slate-800/80 rounded-2xl p-4 shadow-inner space-y-4 max-h-[320px] overflow-y-auto">
                                        <!-- Top Info Bar -->
                                        <div class="flex items-center justify-between border-b border-gray-200/50 dark:border-slate-800 pb-2 mb-2">
                                            <div class="flex items-center gap-2">
                                                <span class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></span>
                                                <span class="text-[10px] font-extrabold text-slate-705 dark:text-slate-300">Zalo Assistant</span>
                                            </div>
                                            <span class="text-[9px] text-slate-400 dark:text-slate-500 font-bold uppercase">{{ __('Trực tuyến') }}</span>
                                        </div>
                                        <!-- Bubbles -->
                                        <div class="space-y-3">
                                            <!-- User message -->
                                            <div class="flex justify-end items-end gap-1.5">
                                                <div class="bg-blue-600 text-white text-xs rounded-2xl rounded-tr-none px-3.5 py-2 max-w-[80%] shadow-sm">
                                                    https://shopee.vn/product/123456
                                                </div>
                                            </div>
                                            <!-- Bot message -->
                                            <div class="flex justify-start items-start gap-1.5">
                                                <div class="w-6 h-6 rounded-full bg-blue-600 text-white flex items-center justify-center text-[9px] font-extrabold shrink-0 shadow">Z</div>
                                                <div class="bg-white dark:bg-slate-800 border border-gray-100 dark:border-slate-700 rounded-2xl rounded-tl-none p-3 text-[11px] text-gray-750 dark:text-slate-300 shadow-sm max-w-[85%] space-y-2">
                                                    <p class="font-bold text-green-600 dark:text-green-400">✅ {{ __('Đã nhận link hoàn tiền thành công!') }}</p>
                                                    <div class="border-t border-gray-100 dark:border-slate-700 pt-1.5 space-y-1 font-sans">
                                                        <p class="font-bold text-gray-900 dark:text-white line-clamp-1">🛍️ {{ __('Áo thun nam Uniqlo dáng rộng cổ tròn thoáng khí') }}</p>
                                                        <p>💰 {{ __('Hoàn tiền ước tính:') }} <strong class="text-shopee text-xs font-bold">15.000đ</strong> (7.5%)</p>
                                                    </div>
                                                    <a href="#" class="mt-2 w-full inline-flex items-center justify-center gap-1.5 px-3 py-1.5 bg-gradient-to-r from-shopee to-shopee-light text-white font-bold rounded-xl text-[10px] text-center active:scale-95 transition-transform shadow-sm">
                                                        <i data-lucide="shopping-cart" class="w-3.5 h-3.5"></i>
                                                        {{ __('Bấm Vào Đây Để Mua Hàng') }}
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Lưu ý Zalo --}}
                            <div class="bg-amber-50 dark:bg-amber-900/10 border border-amber-200/50 dark:border-amber-800/40 rounded-2xl p-4 flex gap-3 shadow-inner">
                                <i data-lucide="triangle-alert" class="w-5 h-5 text-amber-600 dark:text-amber-400 flex-shrink-0 mt-0.5"></i>
                                <div class="text-xs text-amber-800 dark:text-amber-300 space-y-1">
                                    <p class="font-bold">{{ __('Lưu ý quan trọng') }}</p>
                                    <ul class="list-disc list-inside space-y-1 text-amber-700/90 dark:text-amber-400">
                                        <li>{{ __('Cần đăng nhập và liên kết ID Zalo trong Hồ sơ để hoàn tiền được ghi vào tài khoản của bạn.') }}</li>
                                        <li>{{ __('Chỉ hỗ trợ link sản phẩm Shopee và TikTok Shop.') }}</li>
                                        <li>{{ __('Nhớ mua hàng qua link bot gửi, không thay đổi link.') }}</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- HƯỚNG DẪN CHI TIẾT BẢN TELEGRAM BOT --}}
                    @if($telegramConfig->is_enabled)
                        <div x-show="tab === 'telegram'" x-transition:enter="transition ease-out duration-200" class="space-y-6">
                            
                            {{-- Profile Bot & Connection Status --}}
                            <div class="bg-white dark:bg-slate-900 border border-gray-150 dark:border-slate-800/80 rounded-3xl p-5 shadow-sm space-y-4">
                                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 border-b border-gray-100 dark:border-slate-800 pb-5">
                                    <div class="flex items-center gap-3">
                                        <div class="w-12 h-12 rounded-2xl bg-sky-500/10 text-sky-500 flex items-center justify-center shrink-0 shadow-sm">
                                            <svg class="w-7 h-7" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <rect width="48" height="48" rx="12" fill="#29A9EB"/>
                                                <path d="M10 24L35.5 13.5L27 35.5L21 28L10 24Z" fill="white"/>
                                            </svg>
                                        </div>
                                        <div class="truncate">
                                            <h2 class="font-extrabold text-gray-905 dark:text-white text-sm sm:text-base">{{ $telegramConfig->bot_name ?: 'Telegram Cashback Bot' }}</h2>
                                            @if(!empty($telegramConfig->bot_username))
                                                <span class="text-xs text-gray-400 dark:text-slate-500 font-medium block mt-0.5">Username: <span class="font-semibold text-sky-500 dark:text-sky-400">{{ '@' . ltrim($telegramConfig->bot_username, '@') }}</span></span>
                                            @endif
                                        </div>
                                    </div>
                                    @if(!empty($telegramConfig->bot_username))
                                        <a href="https://t.me/{{ ltrim($telegramConfig->bot_username, '@') }}" target="_blank" rel="nofollow noopener"
                                            class="w-full sm:w-auto inline-flex items-center justify-center gap-1.5 px-5 py-2.5 bg-sky-500 hover:bg-sky-600 text-white font-bold rounded-xl text-xs shadow-md shadow-sky-500/10 transition-all active:scale-95">
                                            <i data-lucide="send" class="w-4 h-4"></i>
                                            {{ __('Mở Telegram Bot ngay') }}
                                        </a>
                                    @endif
                                </div>

                                {{-- Trạng thái liên kết tài khoản --}}
                                @auth
                                    @php $telegramLinked = !empty(auth()->user()->bot_telegram_chat_id); @endphp
                                    <div class="p-4 rounded-2xl border {{ $telegramLinked ? 'border-green-200 dark:border-green-800/40 bg-green-50/50 dark:bg-green-950/10' : 'border-amber-200 dark:border-amber-800/40 bg-amber-50/50 dark:bg-amber-950/10' }} flex flex-col sm:flex-row sm:items-center justify-between gap-3 shadow-inner">
                                        <div class="flex items-start gap-2.5">
                                            <div class="p-1 rounded-full {{ $telegramLinked ? 'bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400' : 'text-amber-500 dark:text-amber-400 bg-amber-100 dark:bg-amber-900/30' }} shrink-0 mt-0.5">
                                                <i data-lucide="{{ $telegramLinked ? 'check-circle-2' : 'alert-circle' }}" class="w-4 h-4"></i>
                                            </div>
                                            <div class="space-y-0.5">
                                                <span class="text-xs font-bold text-gray-800 dark:text-white block">{{ $telegramLinked ? __('Tài khoản đã liên kết với Telegram Bot!') : __('Tài khoản chưa được liên kết với Telegram Bot!') }}</span>
                                                <span class="text-[11px] text-gray-500 dark:text-slate-400 block">{{ $telegramLinked ? __('Bạn đã sẵn sàng để gửi link và tích lũy hoàn tiền vào ví.') : __('Để nhận được cashback đúng tài khoản, bạn bắt buộc phải thực hiện liên kết trước khi mua hàng.') }}</span>
                                            </div>
                                        </div>
                                        @if(!$telegramLinked)
                                            <div class="space-y-2 shrink-0 w-full sm:w-auto">
                                                <span class="text-[10px] text-gray-400 dark:text-slate-500 block font-semibold">{{ __('Copy cú pháp này gửi vào Bot để liên kết:') }}</span>
                                                <div x-data="{ copied: false }" class="relative flex items-center bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 rounded-xl px-3 py-2 pr-12 font-mono text-xs text-gray-850 dark:text-slate-205">
                                                    <span>/link {{ auth()->user()->api_token }}</span>
                                                    <button @click="
                                                        navigator.clipboard.writeText('/link {{ auth()->user()->api_token }}');
                                                        copied = true;
                                                        setTimeout(() => copied = false, 2000);
                                                    " class="absolute right-1 top-1 bottom-1 w-7 h-7 rounded-lg bg-gray-50 dark:bg-slate-700 border border-gray-100 dark:border-slate-600 hover:text-shopee dark:hover:text-shopee-light flex items-center justify-center transition-all shadow-sm">
                                                        <i x-show="!copied" data-lucide="copy" class="w-3.5 h-3.5"></i>
                                                        <i x-show="copied" data-lucide="check" class="w-3.5 h-3.5 text-green-500" x-cloak></i>
                                                    </button>
                                                    <span x-show="copied" x-transition class="absolute right-10 top-1.5 bg-emerald-500 text-white text-[9px] font-bold px-1.5 py-0.5 rounded-md" x-cloak>
                                                        {{ __('Đã sao chép!') }}
                                                    </span>
                                                </div>
                                            </div>
                                        @else
                                            <a href="{{ route('profile') }}" class="inline-flex items-center gap-1 text-xs font-bold text-shopee hover:underline">
                                                {{ __('Quản lý liên kết') }} <i data-lucide="arrow-right" class="w-3 h-3"></i>
                                            </a>
                                        @endif
                                    </div>
                                @else
                                    {{-- CTA đăng nhập cho guest --}}
                                    <div class="p-4 rounded-2xl border border-sky-100 dark:border-slate-800 bg-sky-50/20 dark:bg-slate-900/30 flex flex-col sm:flex-row items-center justify-between gap-4">
                                        <div class="space-y-0.5">
                                            <span class="text-xs font-bold text-gray-955 dark:text-white block">{{ __('Bạn chưa đăng nhập!') }}</span>
                                            <span class="text-[11px] text-gray-500 dark:text-slate-400 block">{{ __('Hãy đăng nhập tài khoản để nhận mã liên kết và bắt đầu tích lũy hoàn tiền.') }}</span>
                                        </div>
                                        <a href="{{ route('login') }}" class="shrink-0 w-full sm:w-auto inline-flex items-center justify-center gap-1.5 px-4 py-2 bg-gradient-to-r from-shopee to-shopee-light text-white font-extrabold rounded-xl text-xs shadow-md shadow-shopee/10 transition-colors">
                                            <i data-lucide="log-in" class="w-3.5 h-3.5"></i>
                                            {{ __('Đăng nhập ngay') }}
                                        </a>
                                    </div>
                                @endauth
                            </div>

                            {{-- Hướng dẫn các bước sử dụng và Mockup Chat --}}
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="bg-white dark:bg-slate-900 border border-gray-150 dark:border-slate-800/80 rounded-3xl p-5 shadow-sm space-y-4">
                                    <h3 class="font-extrabold text-gray-900 dark:text-white text-xs sm:text-sm uppercase tracking-wider flex items-center gap-2">
                                        <i data-lucide="list-ordered" class="w-4 h-4 text-sky-500"></i>
                                        {{ __('Các bước thực hiện') }}
                                    </h3>
                                    <div class="relative pl-6 border-l border-sky-100 dark:border-slate-800 space-y-6">
                                        <div class="relative">
                                            <span class="absolute -left-[35px] top-0 flex items-center justify-center w-6 h-6 rounded-full bg-sky-500 text-white text-[10px] font-bold shadow-md">1</span>
                                            <h4 class="text-xs font-bold text-gray-950 dark:text-white">{{ __('Tìm kiếm Bot') }}</h4>
                                            <p class="text-[11px] text-gray-400 dark:text-slate-500 leading-relaxed mt-0.5">{{ __('Tìm username bot trên ứng dụng Telegram hoặc click vào nút Mở Telegram.') }}</p>
                                        </div>
                                        <div class="relative">
                                            <span class="absolute -left-[35px] top-0 flex items-center justify-center w-6 h-6 rounded-full bg-sky-500 text-white text-[10px] font-bold shadow-md">2</span>
                                            <h4 class="text-xs font-bold text-gray-955 dark:text-white">{{ __('Kích hoạt Bot (/start)') }}</h4>
                                            <p class="text-[11px] text-gray-400 dark:text-slate-500 leading-relaxed mt-0.5">{{ __('Ấn nút Start hoặc gõ lệnh /start để bắt đầu cuộc hội thoại.') }}</p>
                                        </div>
                                        <div class="relative">
                                            <span class="absolute -left-[35px] top-0 flex items-center justify-center w-6 h-6 rounded-full bg-sky-500 text-white text-[10px] font-bold shadow-md">3</span>
                                            <h4 class="text-xs font-bold text-gray-955 dark:text-white">{{ __('Liên kết tài khoản') }}</h4>
                                            <p class="text-[11px] text-gray-400 dark:text-slate-500 leading-relaxed mt-0.5">{{ __('Gửi cú pháp /link kèm Khóa API Token cá nhân của bạn để bắt đầu ghi nhận hoàn tiền.') }}</p>
                                        </div>
                                        <div class="relative">
                                            <span class="absolute -left-[35px] top-0 flex items-center justify-center w-6 h-6 rounded-full bg-green-600 text-white text-[10px] font-bold shadow-md">
                                                <i data-lucide="check" class="w-3 h-3"></i>
                                            </span>
                                            <h4 class="text-xs font-bold text-gray-955 dark:text-white">{{ __('Gửi link & Mua sắm') }}</h4>
                                            <p class="text-[11px] text-gray-400 dark:text-slate-500 leading-relaxed mt-0.5">{{ __('Gửi link Shopee/TikTok Shop, bấm link trả về để mua hàng & nhận cashback.') }}</p>
                                        </div>
                                    </div>
                                </div>

                                {{-- Mô phỏng Chat Mockup Telegram --}}
                                <div class="bg-white dark:bg-slate-900 border border-gray-150 dark:border-slate-800/80 rounded-3xl p-5 shadow-sm space-y-4">
                                    <h3 class="font-extrabold text-gray-900 dark:text-white text-xs sm:text-sm uppercase tracking-wider flex items-center gap-2">
                                        <i data-lucide="message-square" class="w-4 h-4 text-sky-500"></i>
                                        {{ __('Trải nghiệm thực tế từ Bot') }}
                                    </h3>
                                    
                                    <div class="relative bg-slate-50 dark:bg-slate-950 border border-gray-100 dark:border-slate-800/80 rounded-2xl p-4 shadow-inner space-y-4 max-h-[320px] overflow-y-auto">
                                        <!-- Top Info Bar -->
                                        <div class="flex items-center justify-between border-b border-gray-200/50 dark:border-slate-800 pb-2 mb-2">
                                            <div class="flex items-center gap-2">
                                                <span class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></span>
                                                <span class="text-[10px] font-extrabold text-slate-705 dark:text-slate-300">Telegram Bot</span>
                                            </div>
                                            <span class="text-[9px] text-slate-400 dark:text-slate-500 font-bold uppercase">{{ __('bot') }}</span>
                                        </div>
                                        <!-- Bubbles -->
                                        <div class="space-y-3">
                                            <!-- User message -->
                                            <div class="flex justify-end items-end gap-1.5">
                                                <div class="bg-sky-500 text-white text-xs rounded-2xl rounded-tr-none px-3.5 py-2 max-w-[80%] shadow-sm">
                                                    https://shopee.vn/product/123456
                                                </div>
                                            </div>
                                            <!-- Bot message -->
                                            <div class="flex justify-start items-start gap-1.5">
                                                <div class="w-6 h-6 rounded-full bg-sky-500 text-white flex items-center justify-center text-[9px] font-extrabold shrink-0 shadow">T</div>
                                                <div class="bg-white dark:bg-slate-800 border border-gray-100 dark:border-slate-700 rounded-2xl rounded-tl-none p-3 text-[11px] text-gray-750 dark:text-slate-300 shadow-sm max-w-[85%] space-y-2">
                                                    <p class="font-bold text-green-600 dark:text-green-400">✅ {{ __('Đã nhận link hoàn tiền thành công!') }}</p>
                                                    <div class="border-t border-gray-100 dark:border-slate-700 pt-1.5 space-y-1 font-sans">
                                                        <p class="font-bold text-gray-900 dark:text-white line-clamp-1">🛍️ {{ __('Áo thun nam Uniqlo dáng rộng cổ tròn thoáng khí') }}</p>
                                                        <p>💰 {{ __('Hoàn tiền ước tính:') }} <strong class="text-shopee text-xs font-bold">15.000đ</strong> (7.5%)</p>
                                                    </div>
                                                    <a href="#" class="mt-2 w-full inline-flex items-center justify-center gap-1.5 px-3 py-1.5 bg-gradient-to-r from-shopee to-shopee-light text-white font-bold rounded-xl text-[10px] text-center active:scale-95 transition-transform shadow-sm">
                                                        <i data-lucide="shopping-cart" class="w-3.5 h-3.5"></i>
                                                        {{ __('Bấm Vào Đây Để Mua Hàng') }}
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Lưu ý Telegram --}}
                            <div class="bg-amber-50 dark:bg-amber-900/10 border border-amber-200/50 dark:border-amber-800/40 rounded-2xl p-4 flex gap-3 shadow-inner">
                                <i data-lucide="triangle-alert" class="w-5 h-5 text-amber-600 dark:text-amber-400 flex-shrink-0 mt-0.5"></i>
                                <div class="text-xs text-amber-800 dark:text-amber-300 space-y-1">
                                    <p class="font-bold">{{ __('Lưu ý quan trọng') }}</p>
                                    <ul class="list-disc list-inside space-y-1 text-amber-700/90 dark:text-amber-400">
                                        <li>{{ __('Cần đăng nhập và liên kết Telegram ID trong Hồ sơ để hoàn tiền được ghi đúng tài khoản.') }}</li>
                                        <li>{{ __('Chỉ hỗ trợ link sản phẩm Shopee và TikTok Shop.') }}</li>
                                        <li>{{ __('Telegram Bot hỗ trợ định dạng HTML/Markdown nâng cao hiển thị tối ưu nhất.') }}</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    @endif

                </div>
            @else
                {{-- Trạng thái bảo trì bot --}}
                <div class="rounded-3xl bg-slate-50 dark:bg-slate-800/40 border border-gray-150 dark:border-slate-800/80 p-8 sm:p-12 text-center max-w-lg mx-auto">
                    <div class="w-12 h-12 rounded-2xl bg-slate-200/50 dark:bg-slate-800 text-slate-400 dark:text-slate-500 flex items-center justify-center mx-auto mb-4">
                        <i data-lucide="bot-off" class="w-6 h-6"></i>
                    </div>
                    <h2 class="font-extrabold text-gray-905 dark:text-white text-sm sm:text-base">{{ __('Dịch vụ Bot Đang Bảo Trì') }}</h2>
                    <p class="text-xs text-gray-400 dark:text-slate-500 mt-1 max-w-sm mx-auto">{{ __('Chúng tôi đang nâng cấp hệ thống chatbot hoàn tiền. Vui lòng quay lại sau ít phút.') }}</p>
                </div>
            @endif

            {{-- Đăng ký nhanh nếu chưa đăng nhập --}}
            @guest
            <div class="rounded-3xl bg-shopee/5 dark:bg-shopee/10 border border-shopee/20 p-6 flex flex-col sm:flex-row items-center gap-4 justify-between">
                <div>
                    <p class="font-extrabold text-gray-905 dark:text-white">{{ __('Bạn chưa có tài khoản?') }}</p>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">{{ __('Đăng ký miễn phí để liên kết Bot và nhận hoàn tiền vào ví của bạn.') }}</p>
                </div>
                <a href="{{ route('register') }}"
                    class="w-full sm:w-auto justify-center inline-flex items-center gap-2 px-6 py-2.5 rounded-xl bg-shopee hover:bg-shopee-dark text-white font-extrabold text-xs transition-colors shadow-md shadow-shopee/10">
                    <i data-lucide="user-plus" class="w-4 h-4"></i>
                    {{ __('Đăng ký ngay') }}
                </a>
            </div>
            @endguest

        </div>
    </div>
</div>
@endsection
