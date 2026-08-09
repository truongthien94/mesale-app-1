@php $hpSettings = $s; @endphp
<style>
@keyframes mesale-hero-banner-drift {
    0%, 100% { transform: scale(1.03) translate3d(0, 0, 0); }
    50% { transform: scale(1.08) translate3d(-0.8%, -0.6%, 0); }
}
@keyframes mesale-hero-banner-shimmer {
    0%, 100% { background-position: 50% 100%; }
    50% { background-position: 55% 70%; }
}
.mesale-hero-banner-motion {
    animation: mesale-hero-banner-drift 10s ease-in-out infinite;
    transform-origin: center;
    will-change: transform;
}
.mesale-hero-banner-overlay {
    background-size: 140% 140%;
    animation: mesale-hero-banner-shimmer 9s ease-in-out infinite;
}
@keyframes mesale-hero-banner-sheen {
    0%, 100% { opacity: 0.05; transform: translate3d(-18%, 0, 0); }
    45%, 60% { opacity: 0.22; transform: translate3d(18%, -1%, 0); }
}
.mesale-hero-banner-overlay::before {
    content: "";
    position: absolute;
    inset: 0;
    pointer-events: none;
    background: linear-gradient(115deg, transparent 20%, rgba(255, 255, 255, 0.24) 50%, transparent 80%);
    animation: mesale-hero-banner-sheen 9s ease-in-out infinite;
}
.mesale-hero-banner-overlay > * {
    position: relative;
    z-index: 1;
}
@media (prefers-reduced-motion: reduce) {
.mesale-hero-banner-motion,
.mesale-hero-banner-overlay,
.mesale-hero-banner-overlay::before {
    animation: none;
    opacity: 0;
    transform: none;
}
}
.mesale-flow-shell {
    position: relative;
    height: 620px;
    overflow: hidden;
}
.mesale-flow-frame {
    position: absolute;
    top: 0;
    border: 0;
    left: 50%;
    width: 600px;
    height: 720px;
    transform: translateX(-50%) scale(.86);
    transform-origin: top center;
}
@media (min-width: 1024px) {
    .mesale-flow-shell {
        height: 520px;
    }
    .mesale-flow-frame {
        left: 0;
        width: 138.8889%;
        height: 138.8889%;
        transform: scale(.72);
        transform-origin: top left;
    }
}
</style>
    <!-- 1. HERO SECTION & BANNER SLIDE -->
    <div id="cong-cu-hoan-tien" class="relative scroll-mt-24 bg-gradient-to-b from-shopee-bg to-transparent pt-0 pb-12 md:pb-20 overflow-hidden" x-data="{
        openDemoModal: false, 
        openNoticeModal: false,
        activeStep: 1, 
        shopeeMenuOpen: false, 
        copiedLink: false, 
        typedLink: '', 
        analyzing: false, 
        analyzed: false, 
        shopeeUrl: 'https://shopee.vn/tai-nghe-bluetooth-hoco-w35-i.12345678.98765432',
        
        resetDemo() {
            this.activeStep = 1;
            this.shopeeMenuOpen = false;
            this.copiedLink = false;
            this.typedLink = '';
            this.analyzing = false;
            this.analyzed = false;
        },
        
        startStep2() {
            this.activeStep = 2;
            this.typedLink = '';
            this.analyzing = false;
            this.analyzed = false;
            // Hiệu ứng chạy chữ mô phỏng gõ phím
            let i = 0;
            let str = this.shopeeUrl;
            let interval = setInterval(() => {
                if (i < str.length) {
                    this.typedLink += str.charAt(i);
                    i++;
                } else {
                    clearInterval(interval);
                }
            }, 15);
        },
        
        analyzeLink() {
            this.analyzing = true;
            setTimeout(() => {
                this.analyzing = false;
                this.analyzed = true;
            }, 1200);
        }
    }" x-effect="document.body.classList.toggle('overflow-hidden', openDemoModal || openNoticeModal); document.body.classList.toggle('demo-modal-open', openDemoModal || openNoticeModal)">
        {{-- 
            Logic hiển thị bố cục (layout) Hero Section động dựa trên lựa chọn của Admin.
            Hỗ trợ 3 loại bố cục:
            - classic: Bố cục 2 cột truyền thống với banner slider bên phải.
            - centered: Bố cục căn giữa, tập trung tuyệt đối vào CTA nhập link hoàn tiền.
            - split: Bố cục chia đôi, bên phải hiển thị số liệu thống kê hệ thống để tạo uy tín.
        --}}
        @if(($hpSettings['hp_hero_layout'] ?? 'classic') === 'classic')
        <div class="px-4 mx-auto max-w-7xl sm:px-6 lg:px-8 relative z-10">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-center">

                <!-- Slogan & Form -->
                <div class="lg:col-span-7 space-y-6">
                    @if(!empty($hpSettings['hp_hero_badge']))
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-shopee/10 text-shopee">
                        <i data-lucide="sparkles" class="w-3.5 h-3.5"></i>
                        {{ str_replace('{cashback_rate}', $cashbackRate, $hpSettings['hp_hero_badge']) }}
                    </span>
                    @endif
                    <h1 class="text-[26px] sm:text-4xl md:text-[2.5rem] font-extrabold tracking-tight text-gray-900 dark:text-white leading-snug sm:leading-tight [text-wrap:balance]">
                        {{ $hpSettings['hp_hero_title_1'] ?? '' }} <br class="hidden sm:block">
                        <span class="text-shopee">{{ $hpSettings['hp_hero_title_2'] ?? '' }}</span> {{ $hpSettings['hp_hero_title_3'] ?? '' }}
                    </h1>
                    <p class="text-base text-gray-600 dark:text-slate-400 max-w-xl">
                        {{ $hpSettings['hp_hero_description'] ?? '' }}
                    </p>

                    <!-- Form Nhập Link Shopee -->
                    <div class="bg-gradient-to-br from-white/90 to-orange-50/45 dark:from-slate-900/80 dark:to-slate-900/60 backdrop-blur-md p-4 rounded-3xl shadow-xl shadow-gray-200/50 dark:shadow-none border border-orange-100/50 dark:border-slate-800/60 max-w-2xl">
                        <form @submit.prevent="fetchProductInfo" class="space-y-3">
                            <div class="flex items-center gap-2 border-b border-gray-100 dark:border-slate-800/60 pb-3">
                                <span class="text-xs font-bold text-gray-400 uppercase tracking-wider">{{ __('Nền tảng hỗ trợ:') }}</span>
                                @if(\App\Models\Setting::getVal('shopee_status', '1') === '1')
                                <span class="flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-[#ff5722] text-white border border-[#ff5722]">
                                    <i data-lucide="shopping-bag" class="w-3 h-3 text-white"></i> {{ \App\Models\Setting::getVal('shopee_platform_name', 'Shopee') }}
                                </span>
                                @endif
                                @if(\App\Models\Setting::getVal('tiktok_status', '1') === '1')
                                <span class="flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-black text-white border border-gray-950 dark:border-slate-800">
                                    <i data-lucide="shopping-cart" class="w-3 h-3 text-white"></i> {{ \App\Models\Setting::getVal('tiktok_platform_name', 'TikTok Shop') }}
                                </span>
                                @endif
                                @if(\App\Models\Setting::getVal('lazada_status', '0') === '1')
                                <span class="flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-[#0f146d] text-white border border-[#0f146d]">
                                    <i data-lucide="shopping-bag" class="w-3 h-3 text-white"></i> {{ \App\Models\Setting::getVal('lazada_platform_name', 'Lazada') }}
                                </span>
                                @endif
                            </div>
                            <div class="flex flex-col sm:flex-row gap-2">
                                <div class="relative flex-grow">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                                        <i data-lucide="link-2" class="w-5 h-5"></i>
                                    </div>
                                    <input type="url"
                                        x-model="url"
                                        @paste="setTimeout(() => fetchProductInfo(), 100)"
                                        required
                                        placeholder="{{ $hpSettings['hp_hero_placeholder'] ?? '' }}"
                                        class="block w-full pl-10 pr-12 py-3.5 border border-gray-200 dark:border-slate-700/60 rounded-2xl text-sm focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee transition-all bg-gray-50/50 dark:bg-slate-800/50 dark:text-white placeholder-gray-400 dark:placeholder-slate-500">
                                    {{-- Nút dán từ clipboard / Xóa link đã nhập --}}
                                    <button type="button"
                                        x-show="!url"
                                        @click="pasteClipboard()"
                                        class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-shopee transition-colors"
                                        title="{{ __('Dán từ bộ nhớ tạm') }}">
                                        <i data-lucide="clipboard" class="w-5 h-5"></i>
                                    </button>
                                    <button type="button"
                                        x-show="url"
                                        @click="url = ''; $el.parentElement.querySelector('input').focus()"
                                        class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-red-500 transition-colors"
                                        title="{{ __('Xóa link') }}"
                                        x-cloak>
                                        <i data-lucide="x-circle" class="w-5 h-5"></i>
                                    </button>
                                </div>
                                <button type="submit"
                                    :disabled="loading"
                                    class="inline-flex items-center justify-center gap-2 px-6 py-3.5 text-sm font-semibold text-white bg-shopee hover:bg-shopee-dark rounded-2xl transition-all shadow-lg shadow-shopee/20 disabled:opacity-55 shrink-0">
                                    <template x-if="loading">
                                        <div class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
                                    </template>
                                    <i data-lucide="search" x-show="!loading" class="w-4 h-4"></i>
                                    <span x-text="loading ? 'Đang phân tích...' : '{{ $hpSettings['hp_hero_btn_text'] ?? '' }}'">{{ $hpSettings['hp_hero_btn_text'] ?? '' }}</span>
                                </button>
                            </div>
                        </form>
                        <div class="mt-3 flex items-center justify-between border-t border-gray-100 dark:border-slate-800/60 pt-3">
                            <button type="button" @click="openDemoModal = true; resetDemo()" class="inline-flex items-center gap-1.5 text-xs font-bold text-shopee hover:text-shopee-dark transition-all" {!! ($hpSettings['hp_show_demo_modal'] ?? '1') !== '1' ? 'style="display:none"' : '' !!}>
                                <i data-lucide="play-circle" class="w-4 h-4 text-shopee"></i>
                                {{ __('Bạn chưa biết cách lấy link?') }}
                            </button>
                            {{-- Kiểm tra cấu hình có bật hiển thị nút Lưu ý khi sử dụng hay không --}}
                            @if(($hpSettings['hp_show_notice_modal'] ?? '1') === '1')
                            <button type="button" @click="openNoticeModal = true" class="inline-flex items-center gap-1.5 text-xs font-bold text-amber-600 hover:text-amber-700 transition-all">
                                <i data-lucide="alert-circle" class="w-4 h-4 text-amber-500"></i>
                                {{ __('Cần lưu ý gì khi sử dụng?') }}
                            </button>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Luong mua sam tu dong dung lam banner Hero -->
                <div class="lg:col-span-5">
                    <div class="mesale-flow-shell">
                        <iframe
                            src="{{ asset('mesale-phone-flow-auto-paste-price-fixed.html') }}?embed=hero"
                            title="{{ __('Luồng mua Shopee và nhận hoàn tiền trên Mê Sale') }}"
                            class="mesale-flow-frame"
                            loading="eager"
                            sandbox="allow-scripts"
                            referrerpolicy="strict-origin-when-cross-origin"></iframe>
                    </div>
                </div>

                {{-- Giu lai nhanh banner cu de co the doi chieu, nhung khong render ra production. --}}
                @if(false)
                <!-- Slide Banner / Video giới thiệu -->
                <div class="lg:col-span-5 hidden lg:block" x-data="{ showVideoModal: false }">
                    <div class="relative h-[320px] rounded-3xl overflow-hidden shadow-2xl shadow-gray-200 border border-white bg-slate-900/5">
                        @if(($hpSettings['hp_hero_right_type'] ?? 'slider') === 'video' && !empty($hpSettings['hp_hero_right_video_url']))
                            @php
                                $videoUrl = $hpSettings['hp_hero_right_video_url'];
                                $isYoutube = false;
                                $youtubeId = '';
                                if (str_contains($videoUrl, 'youtube.com') || str_contains($videoUrl, 'youtu.be')) {
                                    $isYoutube = true;
                                    if (preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i', $videoUrl, $match)) {
                                        $youtubeId = $match[1];
                                    }
                                }
                                $posterUrl = $hpSettings['hp_hero_right_video_poster'] ?? '';
                            @endphp

                            <!-- Khung hiển thị Poster tĩnh và nút Play mở Modal -->
                            <div class="absolute inset-0 z-20 cursor-pointer group" @click="showVideoModal = true">
                                @if(!empty($posterUrl))
                                    <img src="{{ \App\Helpers\LinkHelper::safe($posterUrl) }}" alt="{{ __('Video giới thiệu mua sắm hoàn tiền trên Mê Sale') }}" width="800" height="450" fetchpriority="high" decoding="async" class="w-full h-full object-contain bg-slate-950 rounded-3xl transition-transform duration-700 group-hover:scale-103">
                                @else
                                    <div class="w-full h-full bg-slate-950 flex flex-col items-center justify-center text-gray-400 gap-2">
                                        <i data-lucide="video" class="w-12 h-12 text-gray-500"></i>
                                        <span class="text-xs text-gray-500">{{ __('Nhấn để xem video giới thiệu') }}</span>
                                    </div>
                                @endif
                                <div class="absolute inset-0 bg-black/30 flex items-center justify-center transition-colors group-hover:bg-black/25">
                                    <!-- Nút Play hiệu ứng xung động (Pulse Ping) -->
                                    <div class="w-16 h-16 rounded-full bg-shopee text-white flex items-center justify-center shadow-lg shadow-shopee/35 transition-all duration-300 group-hover:scale-110 relative">
                                        <span class="absolute -inset-2 rounded-full bg-shopee/30 animate-ping opacity-75"></span>
                                        <i data-lucide="play" class="w-6 h-6 fill-current ml-1"></i>
                                    </div>
                                </div>
                            </div>

                            <!-- Modal Popup xem video nền tối toàn màn hình -->
                            <template x-teleport="body">
                                <div x-show="showVideoModal" 
                                     x-transition:enter="transition ease-out duration-300"
                                     x-transition:enter-start="opacity-0"
                                     x-transition:enter-end="opacity-100"
                                     x-transition:leave="transition ease-in duration-200"
                                     x-transition:leave-start="opacity-100"
                                     x-transition:leave-end="opacity-0"
                                     class="fixed inset-0 z-[999] flex items-center justify-center p-4 md:p-6 bg-black/90 backdrop-blur-sm"
                                     x-cloak>
                                    
                                    <!-- Click outside to close -->
                                    <div class="absolute inset-0 cursor-pointer" @click="showVideoModal = false"></div>
                                    
                                    <!-- Modal Content -->
                                    <div x-show="showVideoModal"
                                         x-transition:enter="transition ease-out duration-300"
                                         x-transition:enter-start="opacity-0 scale-95 translate-y-4"
                                         x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                                         x-transition:leave="transition ease-in duration-200"
                                         x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                                         x-transition:leave-end="opacity-0 scale-95 translate-y-4"
                                         class="relative w-full max-w-4xl bg-slate-950 rounded-3xl overflow-hidden shadow-2xl border border-white/10 flex items-center justify-center p-1">
                                        
                                        <!-- Nút đóng Close Button -->
                                        <button @click="showVideoModal = false" class="absolute top-4 right-4 z-[1000] p-2 rounded-full bg-black/60 hover:bg-black/80 text-white/80 hover:text-white transition-all shadow-md">
                                            <i data-lucide="x" class="w-5 h-5"></i>
                                        </button>
                                        
                                        <div class="w-full flex items-center justify-center">
                                            @if($isYoutube && !empty($youtubeId))
                                                <div class="w-full aspect-video">
                                                    <template x-if="showVideoModal">
                                                        <iframe class="w-full h-full rounded-2xl" 
                                                            src="https://www.youtube.com/embed/{{ $youtubeId }}?autoplay=1&rel=0" 
                                                            title="YouTube video player" 
                                                            frameborder="0" 
                                                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" 
                                                            allowfullscreen>
                                                        </iframe>
                                                    </template>
                                                </div>
                                            @else
                                                <template x-if="showVideoModal">
                                                    <video class="max-h-[80vh] w-auto max-w-full rounded-2xl object-contain" controls autoplay playsinline>
                                                        <source src="{{ $videoUrl }}" type="video/mp4">
                                                        {{ __('Trình duyệt của bạn không hỗ trợ phát video.') }}
                                                    </video>
                                                </template>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </template>
                        @else
                            @if(count($banners) > 0)
                                <div x-data="{ activeSlide: 0, slidesCount: {{ count($banners) }} }" x-init="setInterval(() => { activeSlide = (activeSlide + 1) % slidesCount }, 5000)" class="w-full h-full relative">
                                    @foreach($banners as $index => $banner)
                                    <div x-show="activeSlide === {{ $index }}" x-transition:enter="transition ease-out duration-500" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" class="absolute inset-0">
                                        {{--
                                            Banner đầu tiên nằm ngay màn hình đầu (above the fold) nên được tải ưu tiên
                                            để cải thiện chỉ số LCP; các banner còn lại tải trễ (lazy) cho nhẹ trang.
                                        --}}
                                        <img src="{{ $banner->image_url }}" alt="{{ !empty($banner->title) ? __(':title - ưu đãi mua sắm hoàn tiền trên Mê Sale', ['title' => $banner->title]) : __('Mua sắm Shopee và TikTok Shop nhận hoàn tiền qua Mê Sale') }}" width="800" height="600"
                                             loading="{{ $index === 0 ? 'eager' : 'lazy' }}" @if($index === 0) fetchpriority="high" @endif decoding="async"
                                             class="w-full h-full object-cover mesale-hero-banner-motion">
                                        <div class="absolute inset-0 bg-gradient-to-t from-gray-950/70 via-transparent to-transparent flex flex-col justify-end p-6 text-white mesale-hero-banner-overlay">
                                            <h3 class="font-bold text-lg leading-tight">{{ $banner->title }}</h3>
                                            @if($banner->link)
                                            @php $bannerLink = \App\Helpers\LinkHelper::safe($banner->link); @endphp
                                            @if($bannerLink !== '')
                                            <a href="{{ $bannerLink }}" {!! \App\Helpers\LinkHelper::anchorAttrs($bannerLink) !!} class="text-xs text-shopee-light font-bold hover:underline mt-1 flex items-center gap-1">{{ __('Khám phá ngay') }} <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i></a>
                                            @endif
                                            @endif
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="w-full h-full flex items-center justify-center bg-gray-50 dark:bg-slate-800 text-gray-400">
                                    <i data-lucide="image" class="w-12 h-12 opacity-30"></i>
                                </div>
                            @endif
                        @endif
                    </div>
                </div>
                @endif
            </div>
        </div>

        @elseif(($hpSettings['hp_hero_layout'] ?? 'classic') === 'centered')
        <div class="px-4 mx-auto max-w-7xl sm:px-6 lg:px-8 relative z-10 text-center">
            <div class="max-w-3xl mx-auto space-y-6">
                @if(!empty($hpSettings['hp_hero_badge']))
                <span class="inline-flex items-center gap-1.5 px-4 py-1.5 rounded-full text-xs font-semibold bg-shopee/10 text-shopee">
                    <i data-lucide="sparkles" class="w-3.5 h-3.5"></i>
                    {{ str_replace('{cashback_rate}', $cashbackRate, $hpSettings['hp_hero_badge']) }}
                </span>
                @endif
                <h1 class="text-4xl md:text-6xl font-extrabold tracking-tight text-gray-900 dark:text-white leading-tight">
                    {{ $hpSettings['hp_hero_title_1'] ?? '' }} <br>
                    <span class="text-shopee block sm:inline">{{ $hpSettings['hp_hero_title_2'] ?? '' }}</span> {{ $hpSettings['hp_hero_title_3'] ?? '' }}
                </h1>
                <p class="text-base md:text-lg text-gray-650 dark:text-slate-400 max-w-2xl mx-auto leading-relaxed">
                    {{ $hpSettings['hp_hero_description'] ?? '' }}
                </p>

                <!-- Form Nhập Link Shopee ở giữa -->
                <div class="bg-gradient-to-br from-white/90 to-orange-50/45 dark:from-slate-900/80 dark:to-slate-900/60 backdrop-blur-md p-5 rounded-3xl shadow-2xl shadow-orange-100/40 dark:shadow-none border border-orange-100/50 dark:border-slate-800/60 max-w-2xl mx-auto">
                    <form @submit.prevent="fetchProductInfo" class="space-y-4">
                        <div class="flex items-center justify-center gap-2 border-b border-gray-50 dark:border-slate-800/60 pb-3">
                            <span class="text-xs font-bold text-gray-450 uppercase tracking-wider">{{ __('Nền tảng hỗ trợ:') }}</span>
                            @if(\App\Models\Setting::getVal('shopee_status', '1') === '1')
                            <span class="flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-[#ff5722] text-white border border-[#ff5722]">
                                <i data-lucide="shopping-bag" class="w-3 h-3 text-white"></i> {{ \App\Models\Setting::getVal('shopee_platform_name', 'Shopee') }}
                            </span>
                            @endif
                            @if(\App\Models\Setting::getVal('tiktok_status', '1') === '1')
                            <span class="flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-black text-white border border-gray-950 dark:border-slate-800">
                                <i data-lucide="shopping-cart" class="w-3 h-3 text-white"></i> {{ \App\Models\Setting::getVal('tiktok_platform_name', 'TikTok Shop') }}
                            </span>
                            @endif
                            @if(\App\Models\Setting::getVal('lazada_status', '0') === '1')
                            <span class="flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-[#0f146d] text-white border border-[#0f146d]">
                                <i data-lucide="shopping-bag" class="w-3 h-3 text-white"></i> {{ \App\Models\Setting::getVal('lazada_platform_name', 'Lazada') }}
                            </span>
                            @endif
                        </div>
                        <div class="flex flex-col sm:flex-row gap-2.5">
                            <div class="relative flex-grow">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                                    <i data-lucide="link-2" class="w-5 h-5"></i>
                                </div>
                                <input type="url"
                                    x-model="url"
                                    @paste="setTimeout(() => fetchProductInfo(), 100)"
                                    required
                                    placeholder="{{ $hpSettings['hp_hero_placeholder'] ?? '' }}"
                                    class="block w-full pl-10 pr-12 py-4 border border-gray-200 dark:border-slate-700/60 rounded-2xl text-sm focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee transition-all bg-gray-50/50 dark:bg-slate-800/50 dark:text-white placeholder-gray-400 dark:placeholder-slate-500">
                                {{-- Nút dán từ clipboard / Xóa link đã nhập --}}
                                <button type="button"
                                    x-show="!url"
                                    @click="pasteClipboard()"
                                    class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-shopee transition-colors"
                                    title="{{ __('Dán từ bộ nhớ tạm') }}">
                                    <i data-lucide="clipboard" class="w-5 h-5"></i>
                                </button>
                                <button type="button"
                                    x-show="url"
                                    @click="url = ''; $el.parentElement.querySelector('input').focus()"
                                    class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-red-500 transition-colors"
                                    title="{{ __('Xóa link') }}"
                                    x-cloak>
                                    <i data-lucide="x-circle" class="w-5 h-5"></i>
                                </button>
                            </div>
                            <button type="submit"
                                :disabled="loading"
                                class="inline-flex items-center justify-center gap-2 px-8 py-4 text-sm font-bold text-white bg-gradient-to-r from-shopee to-shopee-light hover:opacity-95 rounded-2xl transition-all shadow-lg shadow-shopee/20 disabled:opacity-55 shrink-0">
                                <template x-if="loading">
                                    <div class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
                                </template>
                                <i data-lucide="search" x-show="!loading" class="w-4 h-4"></i>
                                <span x-text="loading ? 'Đang phân tích...' : '{{ $hpSettings['hp_hero_btn_text'] ?? '' }}'">{{ $hpSettings['hp_hero_btn_text'] ?? '' }}</span>
                            </button>
                        </div>
                    </form>
                    <div class="mt-3 flex items-center justify-center gap-6 border-t border-gray-100 dark:border-slate-800/60 pt-3">
                        <button type="button" @click="openDemoModal = true; resetDemo()" class="inline-flex items-center gap-1.5 text-xs font-bold text-shopee hover:text-shopee-dark transition-all" {!! ($hpSettings['hp_show_demo_modal'] ?? '1') !== '1' ? 'style="display:none"' : '' !!}>
                            <i data-lucide="play-circle" class="w-4 h-4 text-shopee"></i>
                            {{ __('Bạn chưa biết cách lấy link?') }}
                        </button>
                        {{-- Kiểm tra cấu hình có bật hiển thị nút Lưu ý khi sử dụng hay không --}}
                        @if(($hpSettings['hp_show_notice_modal'] ?? '1') === '1')
                        <button type="button" @click="openNoticeModal = true" class="inline-flex items-center gap-1.5 text-xs font-bold text-amber-600 hover:text-amber-700 transition-all">
                            <i data-lucide="alert-circle" class="w-4 h-4 text-amber-500"></i>
                            {{ __('Cần lưu ý gì khi sử dụng?') }}
                        </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        @elseif(($hpSettings['hp_hero_layout'] ?? 'classic') === 'split')
        <div class="px-4 mx-auto max-w-7xl sm:px-6 lg:px-8 relative z-10">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-center">

                <!-- Slogan & Form -->
                <div class="lg:col-span-7 space-y-6">
                    @if(!empty($hpSettings['hp_hero_badge']))
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-shopee/10 text-shopee">
                        <i data-lucide="sparkles" class="w-3.5 h-3.5"></i>
                        {{ str_replace('{cashback_rate}', $cashbackRate, $hpSettings['hp_hero_badge']) }}
                    </span>
                    @endif
                    <h1 class="text-[26px] sm:text-4xl md:text-[2.5rem] font-extrabold tracking-tight text-gray-900 dark:text-white leading-snug sm:leading-tight [text-wrap:balance]">
                        {{ $hpSettings['hp_hero_title_1'] ?? '' }} <br class="hidden sm:block">
                        <span class="text-shopee">{{ $hpSettings['hp_hero_title_2'] ?? '' }}</span> {{ $hpSettings['hp_hero_title_3'] ?? '' }}
                    </h1>
                    <p class="text-base text-gray-600 dark:text-slate-400 max-w-xl">
                        {{ $hpSettings['hp_hero_description'] ?? '' }}
                    </p>

                    <!-- Form Nhập Link Shopee -->
                    <div class="bg-gradient-to-br from-white/90 to-orange-50/45 dark:from-slate-900/80 dark:to-slate-900/60 backdrop-blur-md p-4 rounded-3xl shadow-xl shadow-gray-200/50 dark:shadow-none border border-orange-100/50 dark:border-slate-800/60 max-w-2xl">
                        <form @submit.prevent="fetchProductInfo" class="space-y-3">
                            <div class="flex items-center gap-2 border-b border-gray-100 dark:border-slate-800/60 pb-3">
                                <span class="text-xs font-bold text-gray-400 uppercase tracking-wider">{{ __('Nền tảng hỗ trợ:') }}</span>
                                @if(\App\Models\Setting::getVal('shopee_status', '1') === '1')
                                <span class="flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-[#ff5722] text-white border border-[#ff5722]">
                                    <i data-lucide="shopping-bag" class="w-3 h-3 text-white"></i> {{ \App\Models\Setting::getVal('shopee_platform_name', 'Shopee') }}
                                </span>
                                @endif
                                @if(\App\Models\Setting::getVal('tiktok_status', '1') === '1')
                                <span class="flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-black text-white border border-gray-950 dark:border-slate-800">
                                    <i data-lucide="shopping-cart" class="w-3 h-3 text-white"></i> {{ \App\Models\Setting::getVal('tiktok_platform_name', 'TikTok Shop') }}
                                </span>
                                @endif
                                @if(\App\Models\Setting::getVal('lazada_status', '0') === '1')
                                <span class="flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-[#0f146d] text-white border border-[#0f146d]">
                                    <i data-lucide="shopping-bag" class="w-3 h-3 text-white"></i> {{ \App\Models\Setting::getVal('lazada_platform_name', 'Lazada') }}
                                </span>
                                @endif
                            </div>
                            <div class="flex flex-col sm:flex-row gap-2">
                                <div class="relative flex-grow">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                                        <i data-lucide="link-2" class="w-5 h-5"></i>
                                    </div>
                                    <input type="url"
                                        x-model="url"
                                        @paste="setTimeout(() => fetchProductInfo(), 100)"
                                        required
                                        placeholder="{{ $hpSettings['hp_hero_placeholder'] ?? '' }}"
                                        class="block w-full pl-10 pr-12 py-3.5 border border-gray-200 dark:border-slate-700/60 rounded-2xl text-sm focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee transition-all bg-gray-50/50 dark:bg-slate-800/50 dark:text-white placeholder-gray-400 dark:placeholder-slate-500">
                                    {{-- Nút dán từ clipboard / Xóa link đã nhập --}}
                                    <button type="button"
                                        x-show="!url"
                                        @click="pasteClipboard()"
                                        class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-shopee transition-colors"
                                        title="{{ __('Dán từ bộ nhớ tạm') }}">
                                        <i data-lucide="clipboard" class="w-5 h-5"></i>
                                    </button>
                                    <button type="button"
                                        x-show="url"
                                        @click="url = ''; $el.parentElement.querySelector('input').focus()"
                                        class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-red-500 transition-colors"
                                        title="{{ __('Xóa link') }}"
                                        x-cloak>
                                        <i data-lucide="x-circle" class="w-5 h-5"></i>
                                    </button>
                                </div>
                                <button type="submit"
                                    :disabled="loading"
                                    class="inline-flex items-center justify-center gap-2 px-6 py-3.5 text-sm font-semibold text-white bg-shopee hover:bg-shopee-dark rounded-2xl transition-all shadow-lg shadow-shopee/20 disabled:opacity-55 shrink-0">
                                    <template x-if="loading">
                                        <div class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
                                    </template>
                                    <i data-lucide="search" x-show="!loading" class="w-4 h-4"></i>
                                    <span x-text="loading ? 'Đang phân tích...' : '{{ $hpSettings['hp_hero_btn_text'] ?? '' }}'">{{ $hpSettings['hp_hero_btn_text'] ?? '' }}</span>
                                </button>
                            </div>
                        </form>
                        <div class="mt-3 flex items-center justify-between border-t border-gray-100 dark:border-slate-800/60 pt-3">
                            <button type="button" @click="openDemoModal = true; resetDemo()" class="inline-flex items-center gap-1.5 text-xs font-bold text-shopee hover:text-shopee-dark transition-all" {!! ($hpSettings['hp_show_demo_modal'] ?? '1') !== '1' ? 'style="display:none"' : '' !!}>
                                <i data-lucide="play-circle" class="w-4 h-4 text-shopee"></i>
                                {{ __('Bạn chưa biết cách lấy link?') }}
                            </button>
                            {{-- Kiểm tra cấu hình có bật hiển thị nút Lưu ý khi sử dụng hay không --}}
                            @if(($hpSettings['hp_show_notice_modal'] ?? '1') === '1')
                            <button type="button" @click="openNoticeModal = true" class="inline-flex items-center gap-1.5 text-xs font-bold text-amber-600 hover:text-amber-700 transition-all">
                                <i data-lucide="alert-circle" class="w-4 h-4 text-amber-500"></i>
                                {{ __('Cần lưu ý gì khi sử dụng?') }}
                            </button>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Thống kê Hệ thống (Stats Widget) - Thiết kế Glassmorphism / Premium Gradient -->
                <div class="lg:col-span-5 hidden lg:block space-y-4">
                    <div class="bg-gradient-to-br from-white/90 to-orange-50/45 p-6 rounded-3xl border border-orange-100/50 shadow-xl shadow-gray-200/50 space-y-4">
                        <h4 class="text-xs font-bold text-gray-400 uppercase tracking-wider border-b border-orange-100 pb-2.5 flex items-center gap-1.5">
                            <i data-lucide="activity" class="w-3.5 h-3.5 text-shopee"></i>
                            {{ $hpSettings['hp_stats_title'] ?: __('Thống kê hệ thống thực tế') }}
                        </h4>
                        
                        <!-- Lượt click hoàn tiền -->
                        <div class="flex items-center justify-between p-3 bg-white/70 rounded-2xl border border-gray-100 hover:border-orange-100 transition-all">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-orange-50 flex items-center justify-center text-shopee">
                                    <i data-lucide="mouse-pointer" class="w-5 h-5"></i>
                                </div>
                                <div>
                                    <span class="text-xs font-semibold text-gray-500 block">{{ $hpSettings['hp_stats_clicks_label'] ?: __('Lượt nhấp hoàn tiền') }}</span>
                                    <span class="text-base font-extrabold text-gray-900 block">{{ number_format($totalClicks) }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Thành viên hoạt động -->
                        <div class="flex items-center justify-between p-3 bg-white/70 rounded-2xl border border-gray-100 hover:border-orange-100 transition-all">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-green-50 flex items-center justify-center text-green-600">
                                    <i data-lucide="users" class="w-5 h-5"></i>
                                </div>
                                <div>
                                    <span class="text-xs font-semibold text-gray-500 block">{{ $hpSettings['hp_stats_users_label'] ?: __('Thành viên hoạt động') }}</span>
                                    <span class="text-base font-extrabold text-gray-900 block">{{ number_format($totalUsers) }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Tổng tiền đã hoàn trả -->
                        <div class="flex items-center justify-between p-3 bg-white/70 rounded-2xl border border-gray-100 hover:border-orange-100 transition-all">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-blue-50 flex items-center justify-center text-blue-600">
                                    <i data-lucide="banknote" class="w-5 h-5"></i>
                                </div>
                                <div>
                                    <span class="text-xs font-semibold text-gray-500 block">{{ $hpSettings['hp_stats_paid_label'] ?: __('Tổng hoa hồng đã chi trả') }}</span>
                                    <span class="text-base font-extrabold text-shopee block">{{ \App\Helpers\CurrencyHelper::format($totalCashbackPaid) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
        @endif

        <!-- NOTICE MODAL -->
        <div x-show="openNoticeModal" 
            @keydown.escape.window="openNoticeModal = false"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-950/45 backdrop-blur-sm"
            x-cloak>
            
            <div @click.away="openNoticeModal = false"
                class="bg-white w-full max-w-xl rounded-[32px] shadow-2xl border border-gray-100 overflow-hidden flex flex-col max-h-[90vh]"
                x-transition:enter="transition ease-out duration-300 transform"
                x-transition:enter-start="scale-95 translate-y-4"
                x-transition:enter-end="scale-100 translate-y-0"
                x-transition:leave="transition ease-in duration-200 transform"
                x-transition:leave-start="scale-100 translate-y-0"
                x-transition:leave-end="scale-95 translate-y-4">
                
                <!-- Modal Header -->
                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between bg-gray-50/50 shrink-0">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-full bg-amber-50 text-amber-500 flex items-center justify-center">
                            <i data-lucide="alert-circle" class="w-4 h-4"></i>
                        </div>
                        <h3 class="font-extrabold text-gray-900 text-sm md:text-base">
                            {{ __('Lưu ý quan trọng khi mua hàng hoàn tiền') }}
                        </h3>
                    </div>
                    <button @click="openNoticeModal = false" class="p-2 rounded-xl text-gray-400 hover:bg-gray-100 hover:text-gray-600 transition-all focus:outline-none" type="button">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>
                
                <!-- Modal Body -->
                {{-- Hiển thị nội dung lưu ý cấu hình động từ trang quản trị (hỗ trợ định dạng HTML) --}}
                <div class="p-6 overflow-y-auto flex-grow bg-white text-xs md:text-sm text-gray-700 leading-relaxed">
                    {!! $hpSettings['hp_notice_modal_content'] ?? '' !!}
                </div>

                <!-- Modal Footer -->
                <div class="px-6 py-4 border-t border-gray-100 flex justify-end bg-gray-50/50 shrink-0">
                    <button @click="openNoticeModal = false" class="px-5 py-2 rounded-xl bg-shopee hover:bg-shopee-dark text-white font-bold text-xs transition-all shadow-md shadow-shopee/15" type="button">
                        {{ __('Đã hiểu') }}
                    </button>
                </div>
            </div>
        </div>

        <!-- INTERACTIVE DEMO MODAL -->
        <div x-show="openDemoModal" 
            @keydown.escape.window="openDemoModal = false"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-950/45 backdrop-blur-sm"
            x-cloak>
            
            <div @click.away="openDemoModal = false"
                class="bg-white w-full max-w-2xl rounded-[32px] shadow-2xl border border-gray-100 overflow-hidden flex flex-col max-h-[90vh]"
                x-transition:enter="transition ease-out duration-300 transform"
                x-transition:enter-start="scale-95 translate-y-4"
                x-transition:enter-end="scale-100 translate-y-0"
                x-transition:leave="transition ease-in duration-200 transform"
                x-transition:leave-start="scale-100 translate-y-0"
                x-transition:leave-end="scale-95 translate-y-4">
                
                <!-- Modal Header -->
                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between bg-gray-50/50 shrink-0">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-full bg-orange-50 text-shopee flex items-center justify-center">
                            <i data-lucide="play-circle" class="w-4 h-4"></i>
                        </div>
                        <h3 class="font-extrabold text-gray-900 text-sm md:text-base">
                            {{ ($hpSettings['hp_demo_modal_type'] ?? 'interactive') === 'custom' ? __('Hướng Dẫn Lấy Link Hoàn Tiền') : __('Hướng Dẫn Tương Tác: Cách Lấy Link Hoàn Tiền') }}
                        </h3>
                    </div>
                    <button @click="openDemoModal = false" class="p-2 rounded-xl text-gray-400 hover:bg-gray-100 hover:text-gray-600 transition-all focus:outline-none" type="button">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>
                
                <!-- Progress Steps Bar -->
                @if(($hpSettings['hp_demo_modal_type'] ?? 'interactive') === 'interactive')
                <div class="px-6 py-4 bg-gray-50/30 border-b border-gray-100 shrink-0">
                    <div class="flex items-center justify-center gap-4 text-xs font-bold">
                        <button @click="resetDemo()" class="flex items-center gap-2 transition-colors focus:outline-none" :class="activeStep === 1 ? 'text-shopee' : 'text-gray-400'" type="button">
                            <span class="w-5 h-5 rounded-full flex items-center justify-center border text-[10px]" :class="activeStep === 1 ? 'border-shopee bg-shopee text-white' : 'border-gray-300 bg-white'">1</span>
                            {{ __('Bước 1: Copy link Shopee') }}
                        </button>
                        <i data-lucide="chevron-right" class="w-3.5 h-3.5 text-gray-300"></i>
                        <button @click="if(copiedLink) startStep2()" :disabled="!copiedLink" class="flex items-center gap-2 transition-colors focus:outline-none disabled:opacity-50" :class="activeStep === 2 ? 'text-shopee' : 'text-gray-400'" type="button">
                            <span class="w-5 h-5 rounded-full flex items-center justify-center border text-[10px]" :class="activeStep === 2 ? 'border-shopee bg-shopee text-white' : 'border-gray-300 bg-white'">2</span>
                            {{ __('Bước 2: Dán link hệ thống') }}
                        </button>
                    </div>
                </div>
                @endif
                
                <!-- Modal Body -->
                <div class="p-6 overflow-y-auto flex-grow bg-gray-50/50 flex flex-col justify-center">
                    @if(($hpSettings['hp_demo_modal_type'] ?? 'interactive') === 'custom')
                        @php
                            $customContent = $hpSettings['hp_demo_modal_custom_content'] ?? '';
                            $isUrl = filter_var($customContent, FILTER_VALIDATE_URL);
                            $isYoutube = false;
                            $youtubeId = '';
                            $isVideoFile = false;

                            // Kiểm tra nếu nội dung tùy chỉnh là một URL hợp lệ
                            if ($isUrl) {
                                // Kiểm tra liên kết Youtube
                                if (str_contains($customContent, 'youtube.com') || str_contains($customContent, 'youtu.be')) {
                                    $isYoutube = true;
                                    if (preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i', $customContent, $match)) {
                                        $youtubeId = $match[1];
                                    }
                                } 
                                // Kiểm tra file video trực tiếp
                                elseif (preg_match('/\.(mp4|webm|ogg|mov)$/i', $customContent) || str_contains($customContent, '/uploads/')) {
                                    $isVideoFile = true;
                                }
                            }
                        @endphp

                        <div class="w-full flex items-center justify-center">
                            @if($isYoutube && !empty($youtubeId))
                                {{-- Tự động nhúng video Youtube --}}
                                <div class="w-full aspect-video">
                                    <iframe class="w-full h-full rounded-2xl shadow-lg border border-gray-150" 
                                        src="https://www.youtube.com/embed/{{ $youtubeId }}?rel=0" 
                                        title="YouTube video player" 
                                        frameborder="0" 
                                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" 
                                        allowfullscreen>
                                    </iframe>
                                </div>
                            @elseif($isVideoFile || $isUrl)
                                {{-- Tự động nhúng video trực tiếp hoặc link URL khác --}}
                                <div class="w-full aspect-video">
                                    <video class="w-full h-full rounded-2xl object-cover shadow-lg border border-gray-150" controls playsinline>
                                        <source src="{{ $customContent }}" type="video/mp4">
                                        {{ __('Trình duyệt của bạn không hỗ trợ phát video.') }}
                                    </video>
                                </div>
                            @else
                                {{-- Hiển thị nội dung HTML tùy chỉnh tĩnh --}}
                                <div class="prose dark:prose-invert max-w-none text-xs md:text-sm text-gray-700 dark:text-gray-300 space-y-4">
                                    {!! $customContent ?: __('Chưa cấu hình nội dung hướng dẫn.') !!}
                                </div>
                            @endif
                        </div>
                    @else
                        <!-- STEP 1: Shopee App Simulation -->
                        <div x-show="activeStep === 1" class="space-y-6">
                            <div class="text-center space-y-1">
                            <h4 class="font-extrabold text-gray-800 text-sm md:text-base">{{ __('Nhấp vào nút Chia sẻ (Share) trên Shopee') }}</h4>
                            <p class="text-xs text-gray-400">{{ __('Hãy nhấp vào các biểu tượng được nhấp nháy màu cam để thực hiện mô phỏng') }}</p>
                        </div>
                        
                        <!-- Phone Mockup Container -->
                        <div class="w-[260px] h-[460px] mx-auto rounded-[36px] border-[5px] border-gray-900 shadow-2xl relative overflow-hidden bg-white flex flex-col shrink-0 select-none">
                            <!-- Camera Notch / Dynamic Island -->
                            <div class="absolute top-2 left-1/2 -translate-x-1/2 w-20 h-4 bg-gray-900 rounded-full z-30 flex items-center justify-center">
                                <div class="w-2.5 h-2.5 rounded-full bg-gray-800 ml-auto mr-1.5"></div>
                            </div>
                            
                            <!-- App Header Mockup -->
                            <div class="h-12 bg-white border-b border-gray-100 flex items-center justify-between px-4 pt-4 shrink-0 z-20">
                                <i data-lucide="arrow-left" class="w-4 h-4 text-gray-600"></i>
                                <span class="text-[10px] font-bold text-gray-450 bg-gray-100 px-3 py-1 rounded-full w-24 text-center truncate">{{ __('shopee.vn/product...') }}</span>
                                <div class="relative">
                                    <button @click="shopeeMenuOpen = true" class="p-1 rounded-lg hover:bg-gray-100 text-gray-650 relative focus:outline-none" type="button">
                                        <i data-lucide="share-2" class="w-4 h-4 text-shopee"></i>
                                        <!-- Ring Ping Indicator -->
                                        <span x-show="!shopeeMenuOpen" class="absolute inset-0 flex items-center justify-center">
                                            <span class="animate-ping absolute inline-flex h-6 w-6 rounded-full bg-orange-400 opacity-75"></span>
                                        </span>
                                    </button>
                                </div>
                            </div>
                            
                            <!-- App Content Mockup -->
                            <div class="flex-grow overflow-hidden flex flex-col p-3 space-y-2 relative">
                                <!-- Product Image CSS Mockup -->
                                <div class="h-44 rounded-2xl bg-gradient-to-tr from-orange-400 to-amber-200 relative overflow-hidden flex items-center justify-center shrink-0">
                                    <i data-lucide="headphones" class="w-16 h-16 text-white/90"></i>
                                </div>
                                
                                <!-- Product Text -->
                                <div class="space-y-1">
                                    <span class="text-[9px] font-bold text-white bg-shopee px-1.5 py-0.5 rounded">{{ __('Mall') }}</span>
                                    <h5 class="font-bold text-gray-900 text-xs leading-tight line-clamp-2">
                                        {{ __('Tai nghe chụp tai Bluetooth Hoco W35 cao cấp - Hàng chính hãng chất lượng âm thanh Hi-Fi') }}
                                    </h5>
                                    <div class="flex items-baseline gap-1.5">
                                        <span class="text-sm font-extrabold text-shopee">{{ __('250.000₫') }}</span>
                                        <span class="text-[9px] text-gray-400 line-through">{{ __('450.000₫') }}</span>
                                    </div>
                                </div>
                                
                                <!-- Shop Info Mockup -->
                                <div class="flex items-center gap-2 border-t border-b border-gray-100 py-2">
                                    <div class="w-7 h-7 rounded-full bg-gray-250 shrink-0 flex items-center justify-center text-[10px] text-gray-450 font-bold">H</div>
                                    <div class="flex-grow">
                                        <span class="text-[10px] font-bold text-gray-800 block">{{ __('Hoco Store') }}</span>
                                        <span class="text-[8px] text-gray-400 block">{{ __('Thành phố Hồ Chí Minh') }}</span>
                                    </div>
                                    <span class="text-[8px] border border-shopee text-shopee px-2 py-0.5 rounded font-bold">{{ __('Xem Shop') }}</span>
                                </div>
                                
                                <!-- Shopee Share Menu Bottom Sheet -->
                                <div x-show="shopeeMenuOpen" 
                                    x-transition:enter="transition ease-out duration-300 transform"
                                    x-transition:enter-start="translate-y-full"
                                    x-transition:enter-end="translate-y-0"
                                    x-transition:leave="transition ease-in duration-200 transform"
                                    x-transition:leave-start="translate-y-0"
                                    x-transition:leave-end="translate-y-full"
                                    class="absolute inset-x-0 bottom-0 bg-white border-t border-gray-200 rounded-t-3xl shadow-2xl p-4 z-20 space-y-4"
                                    x-cloak>
                                    <div class="flex justify-between items-center border-b border-gray-100 pb-2">
                                        <span class="text-[10px] font-bold text-gray-500 uppercase tracking-wider">{{ __('Chia sẻ sản phẩm này') }}</span>
                                        <button @click="shopeeMenuOpen = false" class="text-gray-400" type="button"><i data-lucide="x" class="w-3.5 h-3.5"></i></button>
                                    </div>
                                    
                                    <!-- Social Icons -->
                                    <div class="grid grid-cols-4 gap-2 text-center">
                                        <div class="flex flex-col items-center gap-1 opacity-45">
                                            <div class="w-9 h-9 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center"><i data-lucide="message-square" class="w-4 h-4"></i></div>
                                            <span class="text-[8px] text-gray-500">{{ __('Messenger') }}</span>
                                        </div>
                                        <div class="flex flex-col items-center gap-1 opacity-45">
                                            <div class="w-9 h-9 rounded-full bg-blue-600 text-white flex items-center justify-center">
                                                <!-- Sử dụng SVG trực tiếp vì thư viện Lucide local rút gọn không chứa icon Facebook -->
                                                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/>
                                                </svg>
                                            </div>
                                            <span class="text-[8px] text-gray-500">{{ __('Facebook') }}</span>
                                        </div>
                                        <!-- Copy Link Button (Interactive) -->
                                        <div class="flex flex-col items-center gap-1 relative cursor-pointer" @click="copiedLink = true; setTimeout(() => { startStep2() }, 1500)">
                                            <div class="w-9 h-9 rounded-full bg-orange-100 text-shopee flex items-center justify-center relative">
                                                <i data-lucide="link" class="w-4 h-4"></i>
                                                <!-- Ring Ping for Copy Link -->
                                                <span class="absolute inset-0 flex items-center justify-center">
                                                    <span class="animate-ping absolute inline-flex h-9 w-9 rounded-full bg-orange-400 opacity-75"></span>
                                                </span>
                                            </div>
                                            <span class="text-[8px] text-gray-800 font-bold">{{ __('Sao chép') }}</span>
                                        </div>
                                        <div class="flex flex-col items-center gap-1 opacity-45">
                                            <div class="w-9 h-9 rounded-full bg-green-500 text-white flex items-center justify-center"><i data-lucide="send" class="w-4 h-4"></i></div>
                                            <span class="text-[8px] text-gray-500">{{ __('Zalo') }}</span>
                                        </div>
                                    </div>
                                    
                                    <!-- Notification Toast inside phone -->
                                    <div x-show="copiedLink" 
                                        x-transition:enter="transition ease-out duration-200"
                                        x-transition:enter-start="opacity-0 scale-90"
                                        x-transition:enter-end="opacity-100 scale-100"
                                        class="absolute inset-x-4 top-[-60px] bg-gray-900/90 text-white text-[9px] font-bold py-2 rounded-xl text-center shadow-lg flex items-center justify-center gap-1.5"
                                        x-cloak>
                                        <i data-lucide="check" class="w-3.5 h-3.5 text-green-500"></i>
                                        {{ __('Đã sao chép liên kết thành công!') }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- STEP 2: Website Simulation -->
                    <div x-show="activeStep === 2" class="space-y-6">
                        <div class="text-center space-y-1">
                            <h4 class="font-extrabold text-gray-800 text-sm md:text-base">{{ __('Dán link vào form hoàn tiền và kiểm tra') }}</h4>
                            <p class="text-xs text-gray-400">{{ __('Đường dẫn đã tự động điền. Nhấn nút phân tích màu cam bên dưới để kiểm thử.') }}</p>
                        </div>
                        
                        <!-- Laptop/Browser Mockup -->
                        <div class="w-full max-w-md mx-auto bg-white rounded-2xl border border-gray-200 shadow-xl overflow-hidden flex flex-col shrink-0 select-none">
                            <!-- Browser Bar -->
                            <div class="h-8 bg-gray-50 border-b border-gray-150 flex items-center px-4 gap-1.5 shrink-0">
                                <div class="w-2.5 h-2.5 rounded-full bg-red-400"></div>
                                <div class="w-2.5 h-2.5 rounded-full bg-yellow-400"></div>
                                <div class="w-2.5 h-2.5 rounded-full bg-green-400"></div>
                                <div class="bg-white border border-gray-150 rounded-md text-[9px] px-3 py-0.5 w-60 ml-4 truncate text-gray-400 text-center font-mono">
                                    {{ request()->getSchemeAndHttpHost() }}
                                </div>
                            </div>
                            
                            <!-- Browser content -->
                            <div class="p-4 space-y-4 bg-gray-50/50 min-h-[220px] flex flex-col justify-center">
                                
                                <!-- Main Form Simulation -->
                                <div x-show="!analyzed" class="space-y-3">
                                    <div class="flex items-center gap-1">
                                        <div class="w-2 h-2 rounded-full bg-shopee animate-pulse"></div>
                                        <span class="text-[9px] font-extrabold text-gray-500 uppercase tracking-wider">{{ __('DÁN LINK NHẬN HOÀN TIỀN') }}</span>
                                    </div>
                                    
                                    <div class="flex flex-col gap-2">
                                        <div class="relative">
                                            <input type="text"
                                                :value="typedLink"
                                                readonly
                                                class="block w-full px-3 py-2 border border-gray-200 rounded-xl text-[10px] bg-white text-gray-600 focus:outline-none font-mono">
                                        </div>
                                        
                                        <button type="button"
                                            @click="analyzeLink()"
                                            :disabled="analyzing || typedLink.length < shopeeUrl.length"
                                            class="inline-flex items-center justify-center gap-1.5 px-4 py-2.5 text-[10px] font-bold text-white bg-shopee hover:bg-shopee-dark rounded-xl transition-all shadow-md shadow-shopee/15 relative disabled:opacity-50">
                                            <template x-if="analyzing">
                                                <div class="w-3 h-3 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
                                            </template>
                                            <i data-lucide="search" x-show="!analyzing" class="w-3 h-3"></i>
                                            <span x-text="analyzing ? 'Đang phân tích...' : 'Lấy Link Hoàn Tiền'"></span>
                                            
                                            <!-- Ring Ping for Submit -->
                                            <span x-show="!analyzing && typedLink.length >= shopeeUrl.length" class="absolute inset-0 flex items-center justify-center">
                                                <span class="animate-ping absolute inline-flex h-full w-full rounded-xl bg-orange-400 opacity-60"></span>
                                            </span>
                                        </button>
                                    </div>
                                </div>
                                
                                <!-- Product Card Result Simulation -->
                                <div x-show="analyzed" 
                                    x-transition:enter="transition ease-out duration-300 transform"
                                    x-transition:enter-start="opacity-0 scale-95"
                                    x-transition:enter-end="opacity-100 scale-100"
                                    class="bg-white p-3 rounded-2xl border border-orange-100 shadow-lg space-y-3"
                                    x-cloak>
                                    <div class="flex gap-3">
                                        <div class="w-14 h-14 rounded-xl bg-gradient-to-tr from-orange-400 to-amber-200 flex items-center justify-center text-white shrink-0">
                                            <i data-lucide="headphones" class="w-7 h-7"></i>
                                        </div>
                                        <div class="flex-grow space-y-1">
                                            <span class="inline-block text-[8px] font-bold text-orange-600 bg-orange-50 px-1.5 py-0.5 rounded border border-orange-100">{{ __('Hợp lệ nhận hoàn tiền') }}</span>
                                            <h6 class="font-bold text-gray-900 text-[10px] leading-tight line-clamp-1">{{ __('Tai nghe chụp tai Bluetooth Hoco W35 cao cấp') }}</h6>
                                            <div class="flex justify-between items-baseline">
                                                <span class="text-[8px] text-gray-400 block">{{ __('Tỷ lệ hoàn:') }} <strong class="text-green-600 font-extrabold">14%</strong></span>
                                                <span class="text-[8px] text-gray-400 block font-semibold">{{ __('Giá bán: 250k') }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="bg-gradient-to-r from-orange-50 to-amber-50 p-2 rounded-xl border border-orange-100/50 flex items-center justify-between">
                                        <span class="text-[9px] text-orange-850 font-bold">{{ __('Tiền hoàn dự kiến của bạn:') }}</span>
                                        <span class="text-xs font-extrabold text-shopee">{{ __('+35.000đ') }}</span>
                                    </div>
                                    
                                    <button type="button" @click="openDemoModal = false" class="w-full inline-flex items-center justify-center gap-1.5 px-3 py-2 text-[10px] font-bold text-white bg-gradient-to-r from-shopee to-shopee-light rounded-xl shadow shadow-shopee/20 hover:opacity-90">
                                        <i data-lucide="external-link" class="w-3 h-3"></i>
                                        {{ __('Bắt Đầu Trải Nghiệm Thật Ngay') }}
                                    </button>
                                </div>
                                
                            </div>
                        </div>
                    @endif
                    
                </div>
                
                <!-- Modal Footer -->
                <div class="px-6 py-4 border-t border-gray-100 bg-gray-50/50 flex items-center justify-between shrink-0">
                    @if(($hpSettings['hp_demo_modal_type'] ?? 'interactive') === 'interactive')
                        <button type="button" 
                            x-show="activeStep === 2"
                            @click="resetDemo()" 
                            class="px-4 py-2 text-xs font-bold text-gray-600 hover:bg-gray-100 rounded-xl transition-all focus:outline-none flex items-center gap-1">
                            <i data-lucide="chevron-left" class="w-4 h-4"></i>
                            {{ __('Quay lại') }}
                        </button>
                        <div x-show="activeStep === 1" class="text-xs text-gray-400 font-semibold">{{ __('Hãy bấm vào biểu tượng chia sẻ trên góc phải điện thoại') }}</div>
                    @endif
                    
                    <button type="button"
                        @click="openDemoModal = false"
                        class="ml-auto px-4 py-2 text-xs font-bold text-gray-500 hover:text-gray-700 transition-all focus:outline-none">
                        {{ __('Đóng') }}
                    </button>
                </div>
                
            </div>
        </div>
    </div>






    <!-- 2. KHU VỰC HIỂN THỊ THÔNG TIN SẢN PHẨM SAU KHI PHÂN TÍCH -->
    <div class="px-4 mx-auto max-w-7xl sm:px-6 lg:px-8">
        <!-- Loading Skeleton -->
        <div x-show="loading" x-cloak class="max-w-2xl mx-auto bg-white p-6 rounded-3xl shadow-xl border border-gray-100 space-y-4 mt-10 md:mt-12">
            <div class="flex gap-4">
                <div class="w-28 h-28 bg-gray-100 rounded-2xl animate-pulse"></div>
                <div class="flex-grow space-y-3">
                    <div class="h-4 bg-gray-100 rounded w-3/4 animate-pulse"></div>
                    <div class="h-6 bg-gray-100 rounded w-1/3 animate-pulse"></div>
                    <div class="h-4 bg-gray-100 rounded w-1/2 animate-pulse"></div>
                </div>
            </div>
            <div class="h-12 bg-gray-100 rounded-2xl w-full animate-pulse"></div>
        </div>

        <!-- Product Card Result -->
        <template x-if="product">
        <div id="product-result-card" class="relative max-w-2xl mx-auto bg-white p-6 rounded-3xl shadow-xl border border-orange-100 space-y-6 mt-10 md:mt-12" x-transition x-cloak>
            <!-- Nút Lưu Lại Mua Sau ở góc trên bên phải -->
            <!-- Quy tắc nghiệp vụ: Ẩn nút lưu với các sản phẩm lấy link bị thiếu thông tin giá bán (giá = 0, dạng ước tính) -->
            <button @click="saveProductForLater()"
                x-show="product && Number(product.price) > 0"
                x-cloak
                type="button"
                class="absolute top-4 right-4 z-10 w-9 h-9 inline-flex items-center justify-center text-gray-400 hover:text-shopee bg-gray-50/80 hover:bg-orange-50/80 dark:bg-slate-800 dark:text-slate-400 dark:hover:bg-slate-800/80 border border-gray-200 dark:border-slate-700 rounded-xl transition-all shadow-sm focus:outline-none"
                :class="savedLater ? 'text-green-600 border-green-200 bg-green-50/30' : ''"
                :disabled="savingLater"
                title="{{ __('Lưu lại mua sau') }}">
                <svg x-show="savingLater" class="animate-spin w-5 h-5 text-shopee" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" style="display: none;">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
                <i x-show="!savingLater" :data-lucide="savedLater ? 'check-circle' : 'bookmark'" class="w-5 h-5"></i>
            </button>
            <div class="flex flex-col sm:flex-row gap-6">
                <!-- Product Image -->
                <div class="relative w-full sm:w-36 h-36 rounded-2xl overflow-hidden border border-gray-100 dark:border-slate-800 bg-gray-100 dark:bg-slate-800 flex items-center justify-center shrink-0">
                    <div class="absolute inset-0 flex items-center justify-center text-gray-400 dark:text-slate-500">
                        <i data-lucide="image" class="w-8 h-8 opacity-40"></i>
                    </div>
                    <template x-if="product && product.image">
                        <img :src="product.image"
                             :alt="product.name || 'Ảnh sản phẩm Shopee hoặc TikTok Shop cần lấy link hoàn tiền'"
                             width="144"
                             height="144"
                             decoding="async"
                             class="absolute inset-0 w-full h-full object-cover rounded-2xl z-10"
                             x-on:error="$el.style.display='none'"
                             onerror="this.style.display='none';">
                    </template>
                </div>

                <!-- Product Information -->
                <div class="flex-grow space-y-2">
                    <div class="flex items-center gap-1.5 text-xs text-orange-600 font-bold bg-orange-50 px-2.5 py-1 rounded-lg w-max border border-orange-100">
                        <i data-lucide="shopping-cart" class="w-3.5 h-3.5"></i>
                        {{ __('Sản phẩm hợp lệ nhận hoàn tiền') }}
                    </div>
                    {{--
                        Dùng thẻ <p> thay cho <h2>: đây là tên sản phẩm trong popup kết quả, chỉ được
                        đổ dữ liệu bằng JavaScript sau khi người dùng dán link. Nếu để thẻ tiêu đề,
                        mã HTML gửi tới công cụ tìm kiếm sẽ luôn chứa một thẻ <h2> rỗng nằm ngay đầu
                        trang chủ — lỗi cấu trúc tiêu đề bị các công cụ kiểm tra SEO cảnh báo.
                    --}}
                    <p class="font-bold text-gray-900 dark:text-white text-lg leading-snug" x-text="product.name"></p>

                    <!-- Thông tin chi tiết giá bán & hoàn tiền -->
                    <div class="grid grid-cols-1 gap-4 pt-2" :class="product.show_price !== '0' ? 'sm:grid-cols-2' : ''" x-show="!product.is_estimated">
                        <!-- Giá bán hiện tại -->
                        <div x-show="product.show_price !== '0'" class="p-3 bg-gray-50 dark:bg-slate-800/40 rounded-2xl border border-gray-100 dark:border-slate-800/80 flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-gray-200/50 dark:bg-slate-700/50 flex items-center justify-center text-gray-500 shrink-0">
                                <i data-lucide="tag" class="w-5 h-5"></i>
                            </div>
                            <div>
                                <span class="text-[10px] text-gray-400 dark:text-slate-500 font-bold block uppercase tracking-wider">{{ __('Giá bán hiện tại:') }}</span>
                                <span class="text-base font-extrabold text-gray-900 dark:text-white" x-text="formatCurrency(product.price)"></span>
                            </div>
                        </div>

                        <!-- Số tiền được hoàn dự kiến (Màu cam nổi bật) -->
                        <div class="p-3 bg-orange-50 dark:bg-orange-950/20 rounded-2xl border border-orange-100 dark:border-orange-900/30 flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-orange-100 dark:bg-orange-900/50 flex items-center justify-center text-shopee shrink-0">
                                <i data-lucide="wallet" class="w-5 h-5"></i>
                            </div>
                            <div>
                                <span class="text-[10px] text-orange-850 dark:text-orange-400 font-bold block uppercase tracking-wider">{{ __('Tiền hoàn dự kiến:') }}</span>
                                <span class="text-base font-black text-shopee" x-text="formatCurrency(product.cashback_amount)"></span>
                            </div>
                        </div>
                    </div>

                    <!-- Tỷ lệ hoàn ước tính (chỉ hiển thị khi sản phẩm cào dạng ước tính và có tỷ lệ hoàn lớn hơn 0) -->
                    <div class="grid grid-cols-1 gap-4 pt-2" x-show="product.is_estimated && product.cashback_rate > 0">
                        <div class="p-3 bg-amber-50 dark:bg-amber-950/20 rounded-2xl border border-amber-100 dark:border-amber-900/30 flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-amber-100 dark:bg-amber-900/50 flex items-center justify-center text-amber-700 dark:text-amber-400 shrink-0">
                                <i data-lucide="percent" class="w-5 h-5"></i>
                            </div>
                            <div>
                                <span class="text-[10px] text-amber-800 dark:text-amber-400 font-bold block uppercase tracking-wider">{{ __('Tỷ lệ hoàn ước tính:') }}</span>
                                <span class="text-base font-bold text-amber-900 dark:text-white" x-text="product.cashback_rate + '%'"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Cashback Banner Info Fallback (Hiển thị khi sản phẩm cào dạng ước tính) -->
            <div x-show="product.is_estimated" class="bg-gradient-to-r from-amber-50/40 to-orange-50/40 dark:from-amber-950/10 dark:to-orange-950/10 p-4 rounded-2xl border border-amber-100/50 dark:border-amber-900/20 space-y-2">
                <div class="flex items-start gap-2.5">
                    <i data-lucide="info" class="w-5 h-5 text-orange-600 dark:text-orange-400 shrink-0 mt-0.5"></i>
                    <div>
                        <span class="text-xs text-amber-950 dark:text-amber-300 font-bold block">{{ __('Hoa hồng hoàn tiền ước tính:') }}</span>
                        <p class="text-xs text-amber-800/80 dark:text-amber-400/80 leading-relaxed mt-0.5">
                            <template x-if="product.cashback_rate > 0">
                                <span>Bạn sẽ được hoàn trả khoảng <strong class="text-orange-600 dark:text-orange-400 text-sm" x-text="product.cashback_rate + '%'"></strong> giá trị sản phẩm. </span>
                            </template>
                            {{ __('Số tiền hoàn tiền thực tế và chi tiết đơn hàng sẽ được hệ thống cập nhật tự động ngay sau khi bạn mua hàng thành công qua liên kết rút gọn bên dưới.') }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Lưu ý về Cashback (Cấu hình động từ Admin Panel) -->
            <div x-show="product.cashback_notice" class="bg-gray-50/50 dark:bg-slate-800/40 p-4 rounded-2xl border border-gray-150 dark:border-slate-805 space-y-2">
                <div class="flex items-start gap-2.5">
                    <i data-lucide="alert-circle" class="w-4 h-4 text-shopee shrink-0 mt-0.5"></i>
                    <div class="space-y-1">
                        <span class="text-xs font-bold text-gray-800 dark:text-slate-200 block">{{ __('Lưu ý:') }}</span>
                        <p class="text-[11px] text-gray-650 dark:text-slate-400 leading-relaxed" x-html="product.cashback_notice">
                        </p>
                    </div>
                </div>
            </div>

            <!-- Ô hiển thị Link Rút Gọn & Nút Copy bên trong -->
            @if(\App\Models\Setting::getVal('shortlink_hide_input', '0') !== '1')
            <div class="space-y-1.5">
                <label class="text-xs font-bold text-gray-500 block">{{ __('Liên kết rút gọn hoàn tiền của bạn:') }}</label>
                <div class="relative rounded-2xl overflow-hidden border border-gray-200 focus-within:ring-2 focus-within:ring-shopee/20 focus-within:border-shopee transition-all bg-gray-50/50">
                    <input type="text"
                        :value="product.affiliate_url"
                        readonly
                        @click="$el.select()"
                        class="block w-full pl-4 pr-20 py-3.5 text-sm bg-transparent border-0 focus:ring-0 focus:outline-none text-gray-600 font-medium">

                    <!-- Nút hiển thị Mã QR -->
                    <button @click="showQr = !showQr"
                        type="button"
                        class="absolute right-10 top-1/2 -translate-y-1/2 p-2 rounded-xl transition-all focus:outline-none flex items-center justify-center"
                        :class="showQr ? 'text-shopee bg-orange-50 dark:bg-slate-800/80' : 'text-gray-400 hover:text-shopee hover:bg-gray-100 dark:hover:bg-slate-800'"
                        title="{{ __('Hiển thị mã QR') }}">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5">
                            <rect width="5" height="5" x="3" y="3" rx="1" />
                            <rect width="5" height="5" x="16" y="3" rx="1" />
                            <rect width="5" height="5" x="3" y="16" rx="1" />
                            <path d="M21 16h-3a2 2 0 0 0-2 2v3" />
                            <path d="M21 21v.01" />
                            <path d="M12 7v3a2 2 0 0 1-2 2H7" />
                            <path d="M3 12h.01" />
                            <path d="M12 3h.01" />
                            <path d="M12 16v.01" />
                            <path d="M16 12h1" />
                            <path d="M21 12v.01" />
                            <path d="M12 21v-1" />
                        </svg>
                    </button>

                    <!-- Nút Sao chép liên kết -->
                    <button @click="copyToClipboard(product.affiliate_url)"
                        type="button"
                        class="absolute right-2 top-1/2 -translate-y-1/2 p-2 rounded-xl hover:bg-gray-100 dark:hover:bg-slate-800 transition-all focus:outline-none flex items-center justify-center"
                        :class="copied ? 'text-green-600 dark:text-green-500' : 'text-gray-400 hover:text-shopee'"
                        title="{{ __('Sao chép liên kết') }}">
                        <!-- Icon Copy (hiển thị khi chưa copy) -->
                        <svg x-show="!copied" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5">
                            <rect width="14" height="14" x="8" y="8" rx="2" ry="2" />
                            <path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2" />
                        </svg>
                        <!-- Icon Check (hiển thị sau khi copy thành công) -->
                        <svg x-show="copied" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5" x-cloak>
                            <path d="M20 6 9 17l-5-5" />
                        </svg>
                    </button>
                </div>
            </div>
            @endif

            <!-- Khung hiển thị mã QR mua hàng -->
            <div x-show="showQr"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 transform -translate-y-2"
                x-transition:enter-end="opacity-100 transform translate-y-0"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 transform translate-y-0"
                x-transition:leave-end="opacity-0 transform -translate-y-2"
                class="p-4 bg-gray-50 dark:bg-slate-800/40 rounded-2xl border border-gray-200 dark:border-slate-800/80 flex flex-col items-center justify-center space-y-3"
                x-cloak>
                <div class="bg-white p-3 rounded-xl shadow-sm border border-gray-150 flex items-center justify-center">
                    <img :src="'https://api.qrserver.com/v1/create-qr-code/?size=256x256&data=' + encodeURIComponent(product.affiliate_url)"
                        alt="Mã QR hoàn tiền Shopee"
                        width="144"
                        height="144"
                        loading="lazy"
                        decoding="async"
                        class="w-36 h-36 object-contain">
                </div>
                <div class="text-center">
                    <p class="text-xs font-semibold text-gray-600 dark:text-slate-300">{{ __('Quét mã QR bằng điện thoại để mua hàng nhanh chóng') }}</p>
                    <button @click="downloadQrCode(product.affiliate_url)"
                        type="button"
                        class="mt-1.5 inline-flex items-center gap-1 text-[11px] font-bold text-shopee hover:underline focus:outline-none">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-3.5 h-3.5">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                            <polyline points="7 10 12 15 17 10" />
                            <line x1="12" x2="12" y1="15" y2="3" />
                        </svg>
                        {{ __('Tải ảnh QR về máy') }}
                    </button>
                </div>
            </div>

            <!-- Action Button -->
            <div class="space-y-2">
                <a :href="product.affiliate_url"
                    target="_blank"
                    class="w-full inline-flex items-center justify-center gap-2 px-6 py-4 text-base font-bold text-white bg-gradient-to-r from-shopee to-shopee-light hover:brightness-110 rounded-2xl transition-all shadow-lg shadow-shopee/25">
                    <i data-lucide="external-link" class="w-5 h-5"></i>
                    {{ __('Mở Mua Hàng Nhận Hoàn Tiền') }}
                </a>

                <template x-if="!logged_in">
                    <p class="text-xs text-gray-400 text-center">
                        <i data-lucide="info" class="w-3.5 h-3.5 inline mr-1"></i>
                        {{ __('Bạn chưa đăng nhập. Bạn có thể nhấn mua hàng nhưng sẽ không được cộng tiền hoàn vào ví.') }} <a href="{{ route('login') }}" class="text-shopee font-bold hover:underline">{{ __('Đăng nhập ngay') }}</a>.
                    </p>
                </template>
            </div>
        </div>
        </template>
    </div>
