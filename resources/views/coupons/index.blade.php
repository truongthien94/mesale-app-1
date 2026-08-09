@extends('layouts.app')

{{-- SEO: Tiêu đề trang động kèm theo tháng/năm hiện tại để tối ưu tỷ lệ click chuột (CTR) khi người dùng tìm kiếm trên Google --}}
@section('title', 'Mã Giảm Giá Shopee ' . date('m/Y') . ' | Mê Sale')

{{-- SEO: Meta description chứa từ khóa chính, tỷ lệ hoàn tiền thực tế của hệ thống để thu hút khách hàng --}}
@section('meta_description', 'Săn mã giảm giá Shopee ' . date('m/Y') . ', voucher Live, Freeship Xtra và ưu đãi tới 50%. Mua qua Mê Sale để nhận hoàn tiền đến ' . (\App\Models\Setting::getVal('cashback_rate', 70)) . '%.')

{{-- SEO: Tiêu đề Open Graph phục vụ hiển thị tối ưu khi chia sẻ đường dẫn lên mạng xã hội (Facebook, Zalo, Telegram) --}}
@section('og_title', __('Mã Giảm Giá Shopee Mới Nhất ' . date('m/Y') . ' - Hoàn Tiền Cashback'))

{{-- SEO: Đường dẫn chuẩn hóa (Canonical) giúp các công cụ tìm kiếm index đúng trang chính, tránh lỗi trùng lặp nội dung khi lọc --}}
@section('canonical_url', route('coupons.index'))

{{-- SEO: Cấu trúc JSON-LD Schema (BreadcrumbList, FAQPage và ItemList ưu đãi hàng đầu) cung cấp siêu dữ liệu có cấu trúc cho Google hiển thị Rich Snippets --}}
@section('seo_schema')
<script type="application/ld+json">
@php
    // Khởi tạo đồ thị dữ liệu cấu trúc chuẩn Google
    $graph = [
        [
            '@type' => 'BreadcrumbList',
            '@id' => route('coupons.index') . '#breadcrumb',
            'itemListElement' => [
                [
                    '@type' => 'ListItem',
                    'position' => 1,
                    'name' => __('Trang chủ'),
                    'item' => route('home'),
                ],
                [
                    '@type' => 'ListItem',
                    'position' => 2,
                    'name' => __('Mã giảm giá Shopee'),
                    'item' => route('coupons.index'),
                ]
            ]
        ],
        [
            '@type' => 'FAQPage',
            '@id' => route('coupons.index') . '#faq',
            'mainEntity' => [
                [
                    '@type' => 'Question',
                    'name' => __('Mã giảm giá Shopee là gì?'),
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text' => __('Mã giảm giá Shopee (Voucher Shopee) là một chuỗi các ký tự chữ và số giúp bạn nhận được ưu đãi giảm giá trực tiếp, hoàn xu hoặc miễn phí vận chuyển trong quá trình mua sắm trên Shopee.')
                    ]
                ],
                [
                    '@type' => 'Question',
                    'name' => __('Mã Shopee Live và Shopee Video là gì?'),
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text' => __('Mã Shopee Live chỉ áp dụng cho sản phẩm được bán trực tiếp trên livestream hoặc được gắn tag Shopee Live. Tương tự, mã Shopee Video chỉ áp dụng cho các sản phẩm được mua thông qua video được đăng tải trên Shopee.')
                    ]
                ],
                [
                    '@type' => 'Question',
                    'name' => __('Tại sao mã giảm giá Shopee không sử dụng được?'),
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text' => __('Có nhiều nguyên nhân khiến bạn không áp dụng được mã: đơn hàng chưa đạt giá trị tối thiểu, mã không áp dụng cho tài khoản của bạn (ví dụ mã dành riêng cho khách hàng mới), mã giới hạn lượt dùng hoặc mã đó yêu cầu phải lưu trên banner trước khi sử dụng thay vì nhập tay.')
                    ]
                ],
                [
                    '@type' => 'Question',
                    'name' => __('Làm sao để săn mã giảm giá Shopee thành công?'),
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text' => __('Bạn nên canh đúng các khung giờ sale lớn (0h, 9h, 12h, 15h, 18h, 21h). Với các mã có giá trị giảm cao thường hết lượt rất nhanh trong 1-2 giây, bạn cần lưu mã sẵn trong Kho voucher và chuẩn bị trước đơn hàng để tiến hành thanh toán nhanh nhất.')
                    ]
                ],
                [
                    '@type' => 'Question',
                    'name' => __('Tại sao đã dùng mã miễn phí vận chuyển nhưng vẫn bị tính phí ship?'),
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text' => __('Bản chất mã miễn phí vận chuyển của Shopee là mã hỗ trợ giảm tối đa một mức phí nhất định (ví dụ giảm 15K, 25K hoặc 50K). Nếu phí ship thực tế của đơn hàng cao hơn mức giảm tối đa này, bạn sẽ phải trả phần chênh lệch còn lại.')
                    ]
                ],
                [
                    '@type' => 'Question',
                    'name' => __('Mã giảm giá Shopee back lượt là gì?'),
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text' => __('Mã back lượt là những voucher Shopee giá trị đã hết lượt sử dụng trước đó, nhưng được hệ thống Shopee bổ sung (back) thêm lượt dùng mới vào một số khung giờ ngẫu nhiên trong ngày để người dùng tiếp tục săn.')
                    ]
                ]
            ]
        ]
    ];

    // Tích hợp danh sách 5 ưu đãi hàng đầu vào ItemList để nâng cao điểm SEO nội dung
    if (!$coupons->isEmpty()) {
        $graph[] = [
            '@type' => 'ItemList',
            '@id' => route('coupons.index') . '#itemlist',
            'name' => __('Danh sách mã giảm giá Shopee hoạt động tốt nhất'),
            'itemListElement' => $coupons->slice(0, 5)->map(function ($coupon, $index) {
                return [
                    '@type' => 'ListItem',
                    'position' => $index + 1,
                    'name' => $coupon->title,
                    'description' => $coupon->description ?: $coupon->title,
                    'url' => route('coupons.index')
                ];
            })->values()->toArray()
        ];
    }
@endphp
{!! json_encode([
    '@' . 'context' => 'https://schema.org',
    '@graph' => $graph
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}
</script>
@endsection

@section('content')
<div class="py-8 md:py-12 px-4 mx-auto max-w-7xl sm:px-6 lg:px-8 relative"
     x-data="{ 
         searchQuery: '{{ request('search', '') }}',
         activeCategory: '{{ request('category', 'all') }}',
         filterCoupons() {
             let url = new URL(window.location.href);
             if (this.searchQuery) {
                 url.searchParams.set('search', this.searchQuery);
             } else {
                 url.searchParams.delete('search');
             }
             if (this.activeCategory !== 'all') {
                 url.searchParams.set('category', this.activeCategory);
             } else {
                 url.searchParams.delete('category');
             }
             url.searchParams.delete('page'); // Reset trang khi thực hiện lọc mới
             window.location.href = url.toString();
         }
     }">
    
    <!-- Tiêu đề & Giới thiệu -->
    <div class="text-center max-w-3xl mx-auto mb-8 md:mb-12 space-y-4">
        <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-shopee/10 text-shopee dark:bg-shopee/25 dark:text-shopee-light rounded-full text-xs font-bold uppercase tracking-wider animate-pulse">
            <i data-lucide="sparkles" class="w-3.5 h-3.5"></i>
            {{ __('Voucher Shopee Hot Mỗi Ngày') }}
        </span>
        <h1 class="text-3xl md:text-5xl font-extrabold text-gray-900 dark:text-white tracking-tight">
            {{ __('Mã Giảm Giá') }} <span class="text-transparent bg-clip-text bg-gradient-to-r from-shopee to-shopee-light">{{ __('Shopee') }}</span>
        </h1>
        <p class="text-sm md:text-base text-gray-500 dark:text-slate-400 max-w-2xl mx-auto leading-relaxed">
            {{ __('Sử dụng mã giảm giá kết hợp với luồng hoàn tiền Shopee để nhân đôi ưu đãi, mua sắm thông minh và tiết kiệm tối đa chi phí của bạn.') }}
        </p>
    </div>

    <!-- Bộ lọc & Tìm kiếm (Glassmorphism card) -->
    <div class="bg-white/70 dark:bg-slate-900/75 backdrop-blur-md border border-gray-100 dark:border-slate-800 rounded-2xl p-4 shadow-sm mb-6 flex flex-row items-center gap-3">
        <!-- Tìm kiếm -->
        <div class="relative flex-1">
            <input type="text" 
                   id="coupons-search-input"
                   x-model="searchQuery"
                   @keyup.enter="filterCoupons()"
                   placeholder="{{ __('Tìm kiếm mã, tiêu đề ưu đãi Shopee...') }}" 
                   class="w-full pl-10 pr-4 py-2 border border-gray-200 dark:border-slate-850 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-850 text-gray-700 dark:text-slate-300 transition-all">
            <i data-lucide="search" class="absolute left-3.5 top-3 w-4 h-4 text-gray-400 dark:text-slate-500"></i>
        </div>

        <!-- Nút áp dụng bộ lọc -->
        <button @click="filterCoupons()" 
                id="coupons-apply-filter-btn"
                class="px-5 py-2 bg-gradient-to-r from-shopee to-shopee-light hover:brightness-110 text-white rounded-xl text-xs font-bold shadow-md shadow-shopee/10 cursor-pointer active:scale-95 transition-all flex items-center justify-center gap-1.5 shrink-0">
            <i data-lucide="filter" class="w-3.5 h-3.5"></i>
            {{ __('Áp dụng') }}
        </button>
    </div>

    <!-- Tiêu đề Danh mục và Thanh trượt Danh mục -->
    <div class="mb-8" id="coupons-category-container">
        <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
            <i data-lucide="grid" class="w-5 h-5 text-teal-600 dark:text-teal-400"></i>
            {{ __('Danh mục') }}
        </h2>
        
        <div class="relative flex items-center">
            <!-- Nút Trượt Trái -->
            <button @click="$refs.catSlider.scrollBy({left: -150, behavior: 'smooth'})" 
                    id="coupons-slider-prev-btn"
                    class="absolute left-0 z-10 w-7 h-7 bg-white dark:bg-slate-800 rounded-full border border-gray-200 dark:border-slate-700 flex items-center justify-center shadow-sm hover:bg-gray-50 dark:hover:bg-slate-700 cursor-pointer transition-all">
                <i data-lucide="chevron-left" class="w-4 h-4 text-gray-600 dark:text-slate-400"></i>
            </button>
            
            <!-- Danh sách các Danh mục dạng Pill Slider -->
            <div x-ref="catSlider" id="coupons-category-slider" class="flex items-center gap-2 overflow-x-auto px-8 w-full scrollbar-none whitespace-nowrap scroll-smooth py-1">
                <!-- Nút Tất cả -->
                <button @click="activeCategory = 'all'; filterCoupons()"
                        id="coupons-cat-btn-all"
                        :class="activeCategory === 'all' ? 'border-2 border-teal-600 dark:border-teal-500 bg-white dark:bg-slate-850 text-gray-900 dark:text-white font-extrabold' : 'border border-gray-200 dark:border-slate-800 bg-gray-50/70 dark:bg-slate-800 text-gray-600 dark:text-slate-400 hover:bg-gray-100 dark:hover:bg-slate-750/80'"
                        class="flex items-center gap-1.5 px-3.5 py-1.5 rounded-full text-xs transition-all cursor-pointer">
                    <i data-lucide="gift" class="w-3.5 h-3.5 text-teal-600 dark:text-teal-400"></i>
                    <span>{{ __('Tất cả') }}</span>
                </button>
                
                <!-- Lặp danh mục động từ cơ sở dữ liệu -->
                @foreach($categories as $cat)
                    @php
                        // Gán icon trực quan tương ứng với danh mục
                        $icon = 'tag';
                        $catLower = strtolower($cat);
                        if (str_contains($catLower, 'toàn sàn')) $icon = 'check-square';
                        elseif (str_contains($catLower, 'xtra')) $icon = 'ticket';
                        elseif (str_contains($catLower, 'freeship') || str_contains($catLower, 'vận chuyển') || str_contains($catLower, 'ship')) $icon = 'truck';
                        elseif (str_contains($catLower, 'mall')) $icon = 'shopping-bag';
                        elseif (str_contains($catLower, 'nổi bật') || str_contains($catLower, 'shop')) $icon = 'star';
                        elseif (str_contains($catLower, 'quốc tế')) $icon = 'globe';
                        elseif (str_contains($catLower, 'công nghệ') || str_contains($catLower, 'điện tử')) $icon = 'smartphone';
                    @endphp
                    <button @click="activeCategory = '{{ $cat }}'; filterCoupons()"
                            id="coupons-cat-btn-{{ \Illuminate\Support\Str::slug($cat) }}"
                            :class="activeCategory === '{{ $cat }}' ? 'border-2 border-teal-600 dark:border-teal-500 bg-white dark:bg-slate-850 text-gray-900 dark:text-white font-extrabold' : 'border border-gray-200 dark:border-slate-800 bg-gray-50/70 dark:bg-slate-800 text-gray-600 dark:text-slate-400 hover:bg-gray-100 dark:hover:bg-slate-750/80'"
                            class="flex items-center gap-1.5 px-3.5 py-1.5 rounded-full text-xs transition-all cursor-pointer">
                        <i data-lucide="{{ $icon }}" class="w-3.5 h-3.5 text-teal-600 dark:text-teal-400"></i>
                        <span>{{ $cat }}</span>
                    </button>
                @endforeach
            </div>

            <!-- Nút Trượt Phải -->
            <button @click="$refs.catSlider.scrollBy({left: 150, behavior: 'smooth'})" 
                    id="coupons-slider-next-btn"
                    class="absolute right-0 z-10 w-7 h-7 bg-white dark:bg-slate-800 rounded-full border border-gray-200 dark:border-slate-700 flex items-center justify-center shadow-sm hover:bg-gray-50 dark:hover:bg-slate-700 cursor-pointer transition-all">
                <i data-lucide="chevron-right" class="w-4 h-4 text-gray-600 dark:text-slate-400"></i>
            </button>
        </div>
    </div>

    <!-- Danh sách Coupon -->
    @if($coupons->isEmpty())
        <div id="coupons-empty-container" class="text-center py-16 bg-white/50 dark:bg-slate-900/50 backdrop-blur-sm rounded-2xl border border-gray-150 dark:border-slate-850 shadow-sm max-w-xl mx-auto space-y-4">
            <div class="w-16 h-16 bg-gray-100 dark:bg-slate-800 text-gray-400 dark:text-slate-600 rounded-full flex items-center justify-center mx-auto shadow-inner">
                <i data-lucide="ticket" class="w-8 h-8"></i>
            </div>
            <h3 class="text-base font-bold text-gray-800 dark:text-slate-200">{{ __('Không tìm thấy mã giảm giá') }}</h3>
            <p class="text-xs text-gray-500 dark:text-slate-400 max-w-xs mx-auto">
                {{ __('Hiện tại không có mã giảm giá Shopee nào phù hợp với bộ lọc của bạn. Vui lòng quay lại sau hoặc thử từ khoá khác.') }}
            </p>
            <a href="{{ route('coupons.index') }}" 
               id="coupons-reset-filter-btn"
               class="inline-flex items-center gap-1.5 px-4 py-2 bg-shopee hover:bg-shopee-dark text-white rounded-xl text-xs font-bold shadow-md transition-all cursor-pointer">
                <i data-lucide="rotate-ccw" class="w-3.5 h-3.5"></i>
                {{ __('Đặt lại bộ lọc') }}
            </a>
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6" id="coupons-grid-list">
            @foreach($coupons as $coupon)
                @php
                    // Tính toán % thời gian còn lại của Coupon để hiển thị cảnh báo
                    $isExpiredSoon = false;
                    $hsdText = __('HSD: 31/12');
                    if ($coupon->expired_at) {
                        $hsdText = 'HSD: ' . date('d/m', strtotime($coupon->expired_at));
                        $diffInHours = now()->diffInHours($coupon->expired_at, false);
                        if ($diffInHours >= 0 && $diffInHours <= 24) {
                            $isExpiredSoon = true;
                            $hsdText = 'Hiệu lực: ' . date('H:i d/m', strtotime($coupon->expired_at));
                        }
                    }

                    // Xác định voucher có mã thực tế (không có tiền tố BANNER_ và không rỗng)
                    $isRealCoupon = !str_starts_with($coupon->code, 'BANNER_') && !empty($coupon->code);

                    // Tạo link chuyển hướng của voucher và đính kèm utm_source tracking
                    $couponRedirectLink = $coupon->redirect_link ?: route('home');
                    $couponUtmSource = \App\Models\Setting::getVal('coupon_utm_source', 'magiamgia');
                    if (!empty($couponRedirectLink) && !empty($couponUtmSource)) {
                        $separator = str_contains($couponRedirectLink, '?') ? '&' : '?';
                        if (!str_contains($couponRedirectLink, 'utm_source=')) {
                            $couponRedirectLink .= $separator . 'utm_source=' . urlencode($couponUtmSource);
                        }
                    }

                    // Đường dẫn chuyển hướng khi người dùng sao chép (copy) mã giảm giá
                    $copyRedirectUrl = \App\Models\Setting::getVal('coupon_redirect_url') ?: $couponRedirectLink;
                    if (\App\Models\Setting::getVal('coupon_redirect_url') && !empty($couponUtmSource)) {
                        $separator = str_contains($copyRedirectUrl, '?') ? '&' : '?';
                        if (!str_contains($copyRedirectUrl, 'utm_source=')) {
                            $copyRedirectUrl .= $separator . 'utm_source=' . urlencode($couponUtmSource);
                        }
                    }
                @endphp
                
                <!-- Ticket Card chia 2 phần ngang chuẩn mockup -->
                <div x-data="{ showDetail: false }" 
                     id="coupon-card-{{ $coupon->id }}"
                     class="bg-white dark:bg-slate-900 border border-gray-150 dark:border-slate-800 rounded-2xl shadow-sm hover:shadow-md transition-all duration-300 flex flex-row overflow-hidden relative group min-h-[148px]">
                    
                    <!-- 1. CỘT TRÁI: Brand & Platform màu xanh ngọc bích -->
                    <div class="w-[32%] sm:w-[35%] shrink-0 bg-[#1ea089] dark:bg-[#198a76] text-white p-3 flex flex-col items-center justify-between text-center relative border-r border-dashed border-white/30 z-10">
                        
                        <!-- Logo sàn/ảnh tròn ở giữa -->
                        <div class="w-12 h-12 sm:w-13 sm:h-13 rounded-full bg-white/10 backdrop-blur-sm p-1 flex items-center justify-center border-2 border-white/20 overflow-hidden shadow-inner mt-1">
                            @if($coupon->image_url)
                                <img src="{{ $coupon->image_url }}" alt="{{ $coupon->title }}" class="w-full h-full object-cover rounded-full" onerror="this.style.display='none'">
                            @else
                                <!-- Logo text dự phòng -->
                                <div class="w-full h-full rounded-full bg-white flex items-center justify-center text-orange-500 font-extrabold text-[10px] sm:text-xs">{{ $coupon->platform === 'tiktok' ? \App\Models\Setting::getVal('tiktok_platform_name', 'TikTok Shop') : \App\Models\Setting::getVal('shopee_platform_name', 'Shopee') }}</div>
                            @endif
                        </div>

                        <!-- Phân loại danh mục -->
                        <span class="text-[10px] font-bold tracking-wide mt-1.5 truncate max-w-full text-white/95">
                            {{ $coupon->category ?: __('Toàn Sàn') }}
                        </span>

                        <!-- Hạn sử dụng nhỏ kèm icon đồng hồ -->
                        <div class="text-[9px] text-white/80 flex items-center justify-center gap-1 mt-auto pt-2.5 border-t border-white/10 w-full truncate">
                            <i data-lucide="clock" class="w-2.5 h-2.5 shrink-0"></i>
                            <span>{{ $hsdText }}</span>
                        </div>
                    </div>

                    <!-- Vết tròn khuyết ở mép trên và mép dưới tại đúng đường đứt nét phân cách -->
                    <div class="absolute left-[32%] sm:left-[35%] top-0 -translate-x-1/2 -translate-y-1/2 w-4.5 h-4.5 bg-gray-50 dark:bg-slate-950 rounded-full border border-gray-150 dark:border-slate-800 z-20"></div>
                    <div class="absolute left-[32%] sm:left-[35%] bottom-0 -translate-x-1/2 translate-y-1/2 w-4.5 h-4.5 bg-gray-50 dark:bg-slate-950 rounded-full border border-gray-150 dark:border-slate-800 z-20"></div>

                    <!-- 2. CỘT PHẢI: Thông tin ưu đãi & Nút copy + chuyển hướng -->
                    <div class="flex-1 p-4 sm:p-5 flex flex-col justify-between min-w-0 bg-white dark:bg-slate-900">
                        <div class="space-y-1.5">
                            <!-- Tiêu đề mã giảm giá nguyên bản từ API -->
                            <h4 class="text-xs sm:text-sm font-bold text-gray-800 dark:text-slate-200 line-clamp-2 leading-snug min-h-[36px]">
                                {{ $coupon->title }}
                            </h4>

                            <!-- Hiển thị mã giảm giá trực quan để sao chép nhanh -->
                            @if($isRealCoupon)
                                <div class="mt-1 flex items-center gap-1.5">
                                    <a href="{{ $copyRedirectUrl }}"
                                       target="_blank"
                                       rel="noopener noreferrer"
                                       class="px-2 py-0.5 bg-orange-50/80 dark:bg-orange-950/20 border border-dashed border-orange-350 dark:border-orange-900/50 rounded-lg text-[10px] sm:text-[11px] font-mono font-extrabold text-[#f95522] dark:text-orange-400 select-all cursor-pointer"
                                       @click="
                                           navigator.clipboard.writeText('{{ $coupon->code }}');
                                           $dispatch('toast', { text: '{{ \App\Models\Setting::getVal('coupon_redirect_on_copy', '0') === '1' ? __('Đã sao chép mã :code! Đang mở liên kết...', ['code' => $coupon->code]) : __('Đã sao chép mã :code!', ['code' => $coupon->code]) }}', type: 'success' });
                                           @if(\App\Models\Setting::getVal('coupon_redirect_on_copy', '0') !== '1')
                                               $event.preventDefault();
                                           @endif
                                       "
                                       title="{{ __('Bấm để sao chép nhanh') }}">
                                        {{ $coupon->code }}
                                    </a>
                                </div>
                            @endif

                            <!-- Khoảng trống giữ bố cục card cân đối -->
                            <div class="min-h-[24px]"></div>
                        </div>

                        <!-- Hàng nút hành động ở dưới cùng -->
                        <div class="flex items-center justify-between gap-1.5 pt-2 border-t border-gray-50 dark:border-slate-850 mt-2">
                            <!-- Nút Xem chi tiết dùng chung cho tất cả mã giảm giá -->
                            <button type="button"
                                    id="coupon-detail-btn-{{ $coupon->id }}"
                                    @click="showDetail = true"
                                    class="text-[10px] sm:text-xs font-bold text-teal-600 dark:text-teal-400 hover:underline flex items-center gap-1 cursor-pointer">
                                <i data-lucide="info" class="w-3.5 h-3.5 text-teal-600 dark:text-teal-400 shrink-0"></i>
                                <span>{{ __('Xem chi tiết') }}</span>
                            </button>

                            <!-- Nút chuyển hướng hoặc sao chép hành động -->
                            @if(!$isRealCoupon)
                                <a href="{{ $couponRedirectLink }}" 
                                   id="coupon-action-btn-{{ $coupon->id }}"
                                   target="_blank" 
                                   rel="noopener noreferrer"
                                   @click="$dispatch('toast', { text: '{{ __('Đang mở trang lưu mã giảm giá Shopee...', []) }}', type: 'success' })"
                                   class="px-2.5 py-1.5 bg-[#1ea089] hover:bg-[#198a76] text-white text-[10px] sm:text-[11px] font-bold rounded-lg transition-all active:scale-95 shadow-sm whitespace-nowrap">
                                    {{ __('Đến Banner') }}
                                </a>
                            @else
                                <a href="{{ $copyRedirectUrl }}"
                                   target="_blank"
                                   rel="noopener noreferrer"
                                   id="coupon-action-btn-{{ $coupon->id }}"
                                   @click="
                                       navigator.clipboard.writeText('{{ $coupon->code }}');
                                       $dispatch('toast', { text: '{{ \App\Models\Setting::getVal('coupon_redirect_on_copy', '0') === '1' ? __('Đã sao chép mã :code! Đang mở liên kết...', ['code' => $coupon->code]) : __('Đã sao chép mã :code thành công!', ['code' => $coupon->code]) }}', type: 'success' });
                                       @if(\App\Models\Setting::getVal('coupon_redirect_on_copy', '0') !== '1')
                                           $event.preventDefault();
                                       @endif
                                   "
                                   class="px-2.5 py-1.5 bg-[#1ea089] hover:bg-[#198a76] text-white text-[10px] sm:text-[11px] font-bold rounded-lg transition-all active:scale-95 shadow-sm whitespace-nowrap cursor-pointer text-center">
                                    {{ __('Sao chép') }}
                                </a>
                            @endif
                        </div>
                    </div>

                    <!-- Modal chi tiết mã giảm giá (AlpineJS) -->
                    <div x-show="showDetail" 
                         id="coupon-modal-{{ $coupon->id }}"
                         x-transition:enter="transition ease-out duration-300"
                         x-transition:enter-start="opacity-0"
                         x-transition:enter-end="opacity-100"
                         x-transition:leave="transition ease-in duration-200"
                         x-transition:leave-start="opacity-100"
                         x-transition:leave-end="opacity-0"
                         class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm"
                         style="display: none;">
                        
                        <!-- Panel Modal -->
                        <div @click.away="showDetail = false"
                             x-show="showDetail"
                             x-transition:enter="transition ease-out duration-300"
                             x-transition:enter-start="opacity-0 scale-95 translate-y-4"
                             x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                             x-transition:leave="transition ease-in duration-200"
                             x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                             x-transition:leave-end="opacity-0 scale-95 translate-y-4"
                             class="bg-white dark:bg-slate-900 border border-gray-150 dark:border-slate-800 rounded-3xl max-w-md w-full shadow-2xl overflow-hidden relative">
                            
                            <!-- Nút đóng góc phải -->
                            <button type="button" 
                                    id="coupon-modal-close-btn-{{ $coupon->id }}"
                                    @click="showDetail = false"
                                    class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 dark:hover:text-slate-350 w-7 h-7 flex items-center justify-center rounded-full bg-gray-50 dark:bg-slate-800 transition-colors cursor-pointer z-10">
                                <i data-lucide="x" class="w-4 h-4"></i>
                            </button>

                            <div class="p-6 space-y-4">
                                <!-- Header Modal -->
                                <div class="flex items-center gap-3">
                                    <div class="w-12 h-12 rounded-full bg-teal-50 dark:bg-teal-950/30 flex items-center justify-center shrink-0">
                                        <i data-lucide="ticket-percent" class="w-6 h-6 text-[#1ea089]"></i>
                                    </div>
                                    <div>
                                        <span class="inline-flex px-2 py-0.5 bg-teal-50 dark:bg-teal-950/30 border border-teal-150 dark:border-teal-900/40 rounded-full text-[10px] font-bold text-[#1ea089] uppercase">
                                            {{ $coupon->category ?: __('Voucher') }}
                                        </span>
                                        <h3 class="text-sm sm:text-base font-extrabold text-gray-800 dark:text-slate-100 mt-0.5 leading-snug">
                                            {{ $coupon->title }}
                                        </h3>
                                    </div>
                                </div>

                                <!-- Thông tin chi tiết -->
                                <div class="space-y-3 pt-3 border-t border-gray-100 dark:border-slate-800">
                                    <!-- Mã giảm giá -->
                                    @if($isRealCoupon)
                                        <div class="flex items-center justify-between bg-orange-50/50 dark:bg-orange-950/10 border border-dashed border-orange-200 dark:border-orange-900/30 p-3 rounded-xl">
                                            <div class="flex flex-col">
                                                <span class="text-[10px] text-gray-400 font-medium">{{ __('Mã voucher') }}</span>
                                                <span class="text-sm font-mono font-extrabold text-[#f95522] dark:text-orange-400 mt-0.5 select-all">{{ $coupon->code }}</span>
                                            </div>
                                            <a href="{{ $copyRedirectUrl }}"
                                               target="_blank"
                                               rel="noopener noreferrer"
                                               id="coupon-modal-copy-btn-{{ $coupon->id }}"
                                               @click="
                                                   navigator.clipboard.writeText('{{ $coupon->code }}');
                                                   $dispatch('toast', { text: '{{ \App\Models\Setting::getVal('coupon_redirect_on_copy', '0') === '1' ? __('Đã sao chép mã :code! Đang mở liên kết...', ['code' => $coupon->code]) : __('Đã sao chép mã :code!', ['code' => $coupon->code]) }}', type: 'success' });
                                                   @if(\App\Models\Setting::getVal('coupon_redirect_on_copy', '0') !== '1')
                                                       $event.preventDefault();
                                                   @endif
                                               "
                                               class="flex items-center gap-1.5 px-3 py-1.5 bg-[#f95522] hover:bg-[#e04516] text-white text-xs font-bold rounded-lg transition-all active:scale-95 shadow-sm cursor-pointer text-center">
                                                <i data-lucide="copy" class="w-3.5 h-3.5"></i>
                                                <span>{{ __('Sao chép') }}</span>
                                            </a>
                                        </div>
                                    @endif

                                    <!-- Hạn sử dụng -->
                                    <div class="flex items-center gap-2 text-xs text-gray-500 dark:text-slate-400">
                                        <i data-lucide="clock" class="w-4 h-4 text-[#1ea089] shrink-0"></i>
                                        <span>
                                            <strong>{{ __('Hạn dùng:') }}</strong> 
                                            {{ $coupon->expired_at ? date('H:i d/m/Y', strtotime($coupon->expired_at)) : __('Chưa xác định') }}
                                        </span>
                                    </div>

                                    <!-- Mô tả đầy đủ -->
                                    @if(!empty($coupon->description))
                                        <div class="space-y-1">
                                            <h4 class="text-xs font-extrabold text-gray-700 dark:text-slate-350">{{ __('Chi tiết ưu đãi & Điều kiện:') }}</h4>
                                            <div class="text-xs text-gray-600 dark:text-slate-400 leading-relaxed bg-gray-50 dark:bg-slate-950 p-3.5 rounded-xl max-h-[160px] overflow-y-auto whitespace-pre-line border border-gray-100 dark:border-slate-800">
                                                {{ $coupon->description }}
                                            </div>
                                        </div>
                                    @endif
                                </div>

                                <!-- Footer Modal -->
                                <div class="flex items-center gap-3 pt-3 border-t border-gray-100 dark:border-slate-800">
                                    <button type="button" 
                                            id="coupon-modal-footer-close-btn-{{ $coupon->id }}"
                                            @click="showDetail = false"
                                            class="flex-1 py-2.5 bg-gray-100 dark:bg-slate-800 hover:bg-gray-200 dark:hover:bg-slate-700 text-gray-700 dark:text-slate-350 text-xs font-bold rounded-xl transition-all cursor-pointer">
                                        {{ __('Đóng') }}
                                    </button>
                                    
                                    <a href="{{ $isRealCoupon ? $copyRedirectUrl : $couponRedirectLink }}" 
                                       id="coupon-modal-go-shopee-btn-{{ $coupon->id }}"
                                       target="_blank" 
                                       rel="noopener noreferrer"
                                       @click="if('{{ $isRealCoupon }}') { navigator.clipboard.writeText('{{ $coupon->code }}'); $dispatch('toast', { text: '{{ __('Đã sao chép mã! Đang mở liên kết...', []) }}', type: 'success' }) } else { $dispatch('toast', { text: '{{ __('Đang mở :platform...', ['platform' => $coupon->platform === 'tiktok' ? \App\Models\Setting::getVal('tiktok_platform_name', 'TikTok Shop') : \App\Models\Setting::getVal('shopee_platform_name', 'Shopee')]) }}', type: 'success' }) }; showDetail = false;"
                                       class="flex-1 py-2.5 bg-[#1ea089] hover:bg-[#198a76] text-white text-xs font-bold rounded-xl text-center transition-all active:scale-95 shadow-md">
                                        {{ $isRealCoupon ? __('Đến :platform mua', ['platform' => $coupon->platform === 'tiktok' ? \App\Models\Setting::getVal('tiktok_platform_name', 'TikTok Shop') : \App\Models\Setting::getVal('shopee_platform_name', 'Shopee')]) : __('Đến Banner') }}
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Phân trang -->
        <div class="mt-10" id="coupons-pagination-container">
            {{ $coupons->appends(request()->query())->links() }}
        </div>
    @endif

    {{-- Giải thích logic nghiệp vụ: Khu vực FAQ này giúp nâng cao điểm SEO, tăng thời gian on-site và giải quyết thắc mắc mua hàng trực tiếp cho người dùng. --}}
    <section class="mt-20 max-w-4xl mx-auto space-y-6" id="coupons-faq-section">
        {{-- Tiêu đề phần Hỏi Đáp --}}
        <div class="text-center space-y-3 mb-10">
            <span class="inline-flex items-center gap-1.5 px-3.5 py-1 bg-orange-50/80 dark:bg-orange-950/20 text-[#f95522] rounded-full text-[11px] font-bold uppercase tracking-widest border border-orange-100 dark:border-orange-900/30">
                <i data-lucide="help-circle" class="w-3.5 h-3.5 text-[#f95522]"></i>
                {{ __('Hỏi đáp thông tin') }}
            </span>
            <h2 class="text-2xl md:text-3.5xl font-extrabold text-gray-900 dark:text-white tracking-tight mt-3">
                {{ __('Giải Đáp') }} <span class="text-[#f95522]">{{ __('Thắc Mắc Thường Gặp') }}</span> {{ __('Khi Săn Mã Shopee') }}
            </h2>
            <p class="text-xs md:text-sm text-gray-550 dark:text-slate-400 max-w-xl mx-auto">
                {{ __('Tổng hợp các thắc mắc phổ biến nhất của người dùng khi sử dụng và săn voucher Shopee.') }}
            </p>
        </div>

        {{-- Danh sách các câu hỏi dạng Accordion (Được thiết lập mở mặc định câu số 1 như bản vẽ mẫu) --}}
        <div class="space-y-4" x-data="{ activeFaq: 1 }">
            
            <!-- FAQ Item 1 -->
            <div class="group border rounded-2xl transition-all duration-300 relative overflow-hidden"
                 :class="activeFaq === 1 ? 'border-orange-200/70 bg-gradient-to-br from-orange-50/60 to-orange-50/20 dark:border-orange-900/40 dark:from-orange-950/10 dark:to-orange-950/5 shadow-md shadow-orange-500/5' : 'border-gray-150 dark:border-slate-800/80 bg-white dark:bg-slate-900 shadow-sm hover:border-orange-200/50 dark:hover:border-orange-900/40 hover:shadow-md'"
                 id="faq-item-1">
                
                <!-- Trạng thái khi mở: Hiển thị đầy đủ câu trả lời kèm hình minh họa 3D Shopee cực kỳ bắt mắt -->
                <div x-show="activeFaq === 1" x-collapse>
                    <div class="p-6 md:p-8 flex items-start gap-4 pr-6 md:pr-[190px] relative min-h-[140px]">
                        <button @click="activeFaq = null" class="w-6 h-6 rounded-full bg-[#f95522] text-white flex items-center justify-center shrink-0 mt-1 cursor-pointer focus:outline-none hover:bg-[#e04516] transition-colors">
                            <i data-lucide="minus" class="w-3.5 h-3.5"></i>
                        </button>
                        <div class="space-y-2 flex-1">
                            <h3 class="font-extrabold text-gray-900 dark:text-white text-[16px] md:text-[18px]">
                                {{ __('Mã giảm giá Shopee là gì?') }}
                            </h3>
                            <p class="text-xs md:text-sm text-gray-600 dark:text-slate-400 leading-relaxed font-medium">
                                {{ __('Mã giảm giá Shopee (hay voucher Shopee) là các mã khuyến mãi do Shopee hoặc đối tác cung cấp, giúp bạn giảm giá cho đơn hàng khi đáp ứng điều kiện sử dụng. Mã có thể có dạng giảm theo %, theo số tiền cố định hoặc miễn phí vận chuyển.') }}
                            </p>
                        </div>
                        <div class="hidden md:flex absolute right-6 bottom-0 top-0 items-center pointer-events-none w-[150px] h-full justify-end">
                            <img src="{{ asset('assets/images/shopee_bag_3d.png') }}" class="w-[120px] h-auto object-contain" alt="Shopee 3D Bag">
                        </div>
                    </div>
                </div>

                <!-- Trạng thái khi thu gọn: Tối giản diện tích và hiển thị số thứ tự -->
                <button x-show="activeFaq !== 1"
                        @click="activeFaq = 1"
                        class="w-full px-6 py-5 text-left flex justify-between items-center gap-4 focus:outline-none cursor-pointer select-none"
                        id="faq-btn-1">
                    <div class="flex items-center gap-4">
                        <span class="w-8 h-8 rounded-full bg-orange-50 dark:bg-orange-950/20 text-[#f95522] flex items-center justify-center font-extrabold text-xs sm:text-sm shrink-0">
                            01
                        </span>
                        <span class="font-extrabold text-[15px] md:text-[17px] text-gray-800 dark:text-slate-200 transition-colors duration-250 group-hover:text-[#f95522]">
                            {{ __('Mã giảm giá Shopee là gì?') }}
                        </span>
                    </div>
                    <div class="w-8 h-8 rounded-full flex items-center justify-center bg-gray-55 dark:bg-slate-800/80 text-gray-400 group-hover:bg-orange-100/40 group-hover:text-[#f95522] transition-colors shrink-0">
                        <i data-lucide="chevron-down" class="w-4 h-4"></i>
                    </div>
                </button>
            </div>

            <!-- FAQ Item 2 -->
            <div class="group border rounded-2xl transition-all duration-300 relative overflow-hidden"
                 :class="activeFaq === 2 ? 'border-orange-200/70 bg-gradient-to-br from-orange-50/60 to-orange-50/20 dark:border-orange-900/40 dark:from-orange-950/10 dark:to-orange-950/5 shadow-md shadow-orange-500/5' : 'border-gray-150 dark:border-slate-800/80 bg-white dark:bg-slate-900 shadow-sm hover:border-orange-200/50 dark:hover:border-orange-900/40 hover:shadow-md'"
                 id="faq-item-2">
                
                <div x-show="activeFaq === 2" x-collapse>
                    <div class="p-6 md:p-8 flex items-start gap-4 pr-6 md:pr-[190px] relative min-h-[140px]">
                        <button @click="activeFaq = null" class="w-6 h-6 rounded-full bg-[#f95522] text-white flex items-center justify-center shrink-0 mt-1 cursor-pointer focus:outline-none hover:bg-[#e04516] transition-colors">
                            <i data-lucide="minus" class="w-3.5 h-3.5"></i>
                        </button>
                        <div class="space-y-2 flex-1">
                            <h3 class="font-extrabold text-gray-900 dark:text-white text-[16px] md:text-[18px]">
                                {{ __('Mã Shopee Live và Shopee Video là gì?') }}
                            </h3>
                            <p class="text-xs md:text-sm text-gray-600 dark:text-slate-400 leading-relaxed font-medium">
                                {{ __('Mã Shopee Live chỉ áp dụng cho sản phẩm được bán trực tiếp trên livestream hoặc được gắn tag Shopee Live. Tương tự, mã Shopee Video chỉ áp dụng cho các sản phẩm được mua thông qua video được đăng tải trên Shopee.') }}
                            </p>
                        </div>
                        <div class="hidden md:flex absolute right-6 bottom-0 top-0 items-center pointer-events-none w-[150px] h-full justify-end">
                            <img src="{{ asset('assets/images/shopee_bag_3d.png') }}" class="w-[120px] h-auto object-contain" alt="Shopee 3D Bag">
                        </div>
                    </div>
                </div>

                <button x-show="activeFaq !== 2"
                        @click="activeFaq = 2"
                        class="w-full px-6 py-5 text-left flex justify-between items-center gap-4 focus:outline-none cursor-pointer select-none"
                        id="faq-btn-2">
                    <div class="flex items-center gap-4">
                        <span class="w-8 h-8 rounded-full bg-orange-50 dark:bg-orange-950/20 text-[#f95522] flex items-center justify-center font-extrabold text-xs sm:text-sm shrink-0">
                            02
                        </span>
                        <span class="font-extrabold text-[15px] md:text-[17px] text-gray-800 dark:text-slate-200 transition-colors duration-250 group-hover:text-[#f95522]">
                            {{ __('Mã Shopee Live và Shopee Video là gì?') }}
                        </span>
                    </div>
                    <div class="w-8 h-8 rounded-full flex items-center justify-center bg-gray-55 dark:bg-slate-800/80 text-gray-400 group-hover:bg-orange-100/40 group-hover:text-[#f95522] transition-colors shrink-0">
                        <i data-lucide="chevron-down" class="w-4 h-4"></i>
                    </div>
                </button>
            </div>

            <!-- FAQ Item 3 -->
            <div class="group border rounded-2xl transition-all duration-300 relative overflow-hidden"
                 :class="activeFaq === 3 ? 'border-orange-200/70 bg-gradient-to-br from-orange-50/60 to-orange-50/20 dark:border-orange-900/40 dark:from-orange-950/10 dark:to-orange-950/5 shadow-md shadow-orange-500/5' : 'border-gray-150 dark:border-slate-800/80 bg-white dark:bg-slate-900 shadow-sm hover:border-orange-200/50 dark:hover:border-orange-900/40 hover:shadow-md'"
                 id="faq-item-3">
                
                <div x-show="activeFaq === 3" x-collapse>
                    <div class="p-6 md:p-8 flex items-start gap-4 pr-6 md:pr-[190px] relative min-h-[140px]">
                        <button @click="activeFaq = null" class="w-6 h-6 rounded-full bg-[#f95522] text-white flex items-center justify-center shrink-0 mt-1 cursor-pointer focus:outline-none hover:bg-[#e04516] transition-colors">
                            <i data-lucide="minus" class="w-3.5 h-3.5"></i>
                        </button>
                        <div class="space-y-2 flex-1">
                            <h3 class="font-extrabold text-gray-900 dark:text-white text-[16px] md:text-[18px]">
                                {{ __('Tại sao mã giảm giá Shopee không sử dụng được?') }}
                            </h3>
                            <p class="text-xs md:text-sm text-gray-600 dark:text-slate-400 leading-relaxed font-medium">
                                {{ __('Có nhiều nguyên nhân khiến bạn không áp dụng được mã: đơn hàng chưa đạt giá trị tối thiểu, mã không áp dụng cho tài khoản của bạn (ví dụ mã dành riêng cho khách hàng mới), mã giới hạn lượt dùng hoặc mã đó yêu cầu phải lưu trên banner trước khi sử dụng thay vì nhập tay.') }}
                            </p>
                        </div>
                        <div class="hidden md:flex absolute right-6 bottom-0 top-0 items-center pointer-events-none w-[150px] h-full justify-end">
                            <img src="{{ asset('assets/images/shopee_bag_3d.png') }}" class="w-[120px] h-auto object-contain" alt="Shopee 3D Bag">
                        </div>
                    </div>
                </div>

                <button x-show="activeFaq !== 3"
                        @click="activeFaq = 3"
                        class="w-full px-6 py-5 text-left flex justify-between items-center gap-4 focus:outline-none cursor-pointer select-none"
                        id="faq-btn-3">
                    <div class="flex items-center gap-4">
                        <span class="w-8 h-8 rounded-full bg-orange-50 dark:bg-orange-950/20 text-[#f95522] flex items-center justify-center font-extrabold text-xs sm:text-sm shrink-0">
                            03
                        </span>
                        <span class="font-extrabold text-[15px] md:text-[17px] text-gray-800 dark:text-slate-200 transition-colors duration-250 group-hover:text-[#f95522]">
                            {{ __('Tại sao mã giảm giá Shopee không sử dụng được?') }}
                        </span>
                    </div>
                    <div class="w-8 h-8 rounded-full flex items-center justify-center bg-gray-55 dark:bg-slate-800/80 text-gray-400 group-hover:bg-orange-100/40 group-hover:text-[#f95522] transition-colors shrink-0">
                        <i data-lucide="chevron-down" class="w-4 h-4"></i>
                    </div>
                </button>
            </div>

            <!-- FAQ Item 4 -->
            <div class="group border rounded-2xl transition-all duration-300 relative overflow-hidden"
                 :class="activeFaq === 4 ? 'border-orange-200/70 bg-gradient-to-br from-orange-50/60 to-orange-50/20 dark:border-orange-900/40 dark:from-orange-950/10 dark:to-orange-950/5 shadow-md shadow-orange-500/5' : 'border-gray-150 dark:border-slate-800/80 bg-white dark:bg-slate-900 shadow-sm hover:border-orange-200/50 dark:hover:border-orange-900/40 hover:shadow-md'"
                 id="faq-item-4">
                
                <div x-show="activeFaq === 4" x-collapse>
                    <div class="p-6 md:p-8 flex items-start gap-4 pr-6 md:pr-[190px] relative min-h-[140px]">
                        <button @click="activeFaq = null" class="w-6 h-6 rounded-full bg-[#f95522] text-white flex items-center justify-center shrink-0 mt-1 cursor-pointer focus:outline-none hover:bg-[#e04516] transition-colors">
                            <i data-lucide="minus" class="w-3.5 h-3.5"></i>
                        </button>
                        <div class="space-y-2 flex-1">
                            <h3 class="font-extrabold text-gray-900 dark:text-white text-[16px] md:text-[18px]">
                                {{ __('Làm sao để săn mã giảm giá Shopee thành công?') }}
                            </h3>
                            <p class="text-xs md:text-sm text-gray-600 dark:text-slate-400 leading-relaxed font-medium">
                                {{ __('Bạn nên canh đúng các khung giờ sale lớn (0h, 9h, 12h, 15h, 18h, 21h). Với các mã có giá trị giảm cao thường hết lượt rất nhanh trong 1-2 giây, bạn cần lưu mã sẵn trong Kho voucher và chuẩn bị trước đơn hàng để tiến hành thanh toán nhanh nhất.') }}
                            </p>
                        </div>
                        <div class="hidden md:flex absolute right-6 bottom-0 top-0 items-center pointer-events-none w-[150px] h-full justify-end">
                            <img src="{{ asset('assets/images/shopee_bag_3d.png') }}" class="w-[120px] h-auto object-contain" alt="Shopee 3D Bag">
                        </div>
                    </div>
                </div>

                <button x-show="activeFaq !== 4"
                        @click="activeFaq = 4"
                        class="w-full px-6 py-5 text-left flex justify-between items-center gap-4 focus:outline-none cursor-pointer select-none"
                        id="faq-btn-4">
                    <div class="flex items-center gap-4">
                        <span class="w-8 h-8 rounded-full bg-orange-50 dark:bg-orange-950/20 text-[#f95522] flex items-center justify-center font-extrabold text-xs sm:text-sm shrink-0">
                            04
                        </span>
                        <span class="font-extrabold text-[15px] md:text-[17px] text-gray-800 dark:text-slate-200 transition-colors duration-250 group-hover:text-[#f95522]">
                            {{ __('Làm sao để săn mã giảm giá Shopee thành công?') }}
                        </span>
                    </div>
                    <div class="w-8 h-8 rounded-full flex items-center justify-center bg-gray-55 dark:bg-slate-800/80 text-gray-400 group-hover:bg-orange-100/40 group-hover:text-[#f95522] transition-colors shrink-0">
                        <i data-lucide="chevron-down" class="w-4 h-4"></i>
                    </div>
                </button>
            </div>

            <!-- FAQ Item 5 -->
            <div class="group border rounded-2xl transition-all duration-300 relative overflow-hidden"
                 :class="activeFaq === 5 ? 'border-orange-200/70 bg-gradient-to-br from-orange-50/60 to-orange-50/20 dark:border-orange-900/40 dark:from-orange-950/10 dark:to-orange-950/5 shadow-md shadow-orange-500/5' : 'border-gray-150 dark:border-slate-800/80 bg-white dark:bg-slate-900 shadow-sm hover:border-orange-200/50 dark:hover:border-orange-900/40 hover:shadow-md'"
                 id="faq-item-5">
                
                <div x-show="activeFaq === 5" x-collapse>
                    <div class="p-6 md:p-8 flex items-start gap-4 pr-6 md:pr-[190px] relative min-h-[140px]">
                        <button @click="activeFaq = null" class="w-6 h-6 rounded-full bg-[#f95522] text-white flex items-center justify-center shrink-0 mt-1 cursor-pointer focus:outline-none hover:bg-[#e04516] transition-colors">
                            <i data-lucide="minus" class="w-3.5 h-3.5"></i>
                        </button>
                        <div class="space-y-2 flex-1">
                            <h3 class="font-extrabold text-gray-900 dark:text-white text-[16px] md:text-[18px]">
                                {{ __('Tại sao đã dùng mã miễn phí vận chuyển nhưng vẫn bị tính phí ship?') }}
                            </h3>
                            <p class="text-xs md:text-sm text-gray-600 dark:text-slate-400 leading-relaxed font-medium">
                                {{ __('Bản chất mã miễn phí vận chuyển của Shopee là mã hỗ trợ giảm tối đa một mức phí nhất định (ví dụ giảm 15K, 25K hoặc 50K). Nếu phí ship thực tế của đơn hàng cao hơn mức giảm tối đa này, bạn sẽ phải trả phần chênh lệch còn lại.') }}
                            </p>
                        </div>
                        <div class="hidden md:flex absolute right-6 bottom-0 top-0 items-center pointer-events-none w-[150px] h-full justify-end">
                            <img src="{{ asset('assets/images/shopee_bag_3d.png') }}" class="w-[120px] h-auto object-contain" alt="Shopee 3D Bag">
                        </div>
                    </div>
                </div>

                <button x-show="activeFaq !== 5"
                        @click="activeFaq = 5"
                        class="w-full px-6 py-5 text-left flex justify-between items-center gap-4 focus:outline-none cursor-pointer select-none"
                        id="faq-btn-5">
                    <div class="flex items-center gap-4">
                        <span class="w-8 h-8 rounded-full bg-orange-50 dark:bg-orange-950/20 text-[#f95522] flex items-center justify-center font-extrabold text-xs sm:text-sm shrink-0">
                            05
                        </span>
                        <span class="font-extrabold text-[15px] md:text-[17px] text-gray-800 dark:text-slate-200 transition-colors duration-250 group-hover:text-[#f95522]">
                            {{ __('Tại sao đã dùng mã miễn phí vận chuyển nhưng vẫn bị tính phí ship?') }}
                        </span>
                    </div>
                    <div class="w-8 h-8 rounded-full flex items-center justify-center bg-gray-55 dark:bg-slate-800/80 text-gray-400 group-hover:bg-orange-100/40 group-hover:text-[#f95522] transition-colors shrink-0">
                        <i data-lucide="chevron-down" class="w-4 h-4"></i>
                    </div>
                </button>
            </div>

            <!-- FAQ Item 6 -->
            <div class="group border rounded-2xl transition-all duration-300 relative overflow-hidden"
                 :class="activeFaq === 6 ? 'border-orange-200/70 bg-gradient-to-br from-orange-50/60 to-orange-50/20 dark:border-orange-900/40 dark:from-orange-950/10 dark:to-orange-950/5 shadow-md shadow-orange-500/5' : 'border-gray-150 dark:border-slate-800/80 bg-white dark:bg-slate-900 shadow-sm hover:border-orange-200/50 dark:hover:border-orange-900/40 hover:shadow-md'"
                 id="faq-item-6">
                
                <div x-show="activeFaq === 6" x-collapse>
                    <div class="p-6 md:p-8 flex items-start gap-4 pr-6 md:pr-[190px] relative min-h-[140px]">
                        <button @click="activeFaq = null" class="w-6 h-6 rounded-full bg-[#f95522] text-white flex items-center justify-center shrink-0 mt-1 cursor-pointer focus:outline-none hover:bg-[#e04516] transition-colors">
                            <i data-lucide="minus" class="w-3.5 h-3.5"></i>
                        </button>
                        <div class="space-y-2 flex-1">
                            <h3 class="font-extrabold text-gray-900 dark:text-white text-[16px] md:text-[18px]">
                                {{ __('Mã giảm giá Shopee back lượt là gì?') }}
                            </h3>
                            <p class="text-xs md:text-sm text-gray-600 dark:text-slate-400 leading-relaxed font-medium">
                                {{ __('Mã back lượt là những voucher Shopee giá trị đã hết lượt sử dụng trước đó, nhưng được hệ thống Shopee bổ sung (back) thêm lượt dùng mới vào một số khung giờ ngẫu nhiên trong ngày để người dùng tiếp tục săn.') }}
                            </p>
                        </div>
                        <div class="hidden md:flex absolute right-6 bottom-0 top-0 items-center pointer-events-none w-[150px] h-full justify-end">
                            <img src="{{ asset('assets/images/shopee_bag_3d.png') }}" class="w-[120px] h-auto object-contain" alt="Shopee 3D Bag">
                        </div>
                    </div>
                </div>

                <button x-show="activeFaq !== 6"
                        @click="activeFaq = 6"
                        class="w-full px-6 py-5 text-left flex justify-between items-center gap-4 focus:outline-none cursor-pointer select-none"
                        id="faq-btn-6">
                    <div class="flex items-center gap-4">
                        <span class="w-8 h-8 rounded-full bg-orange-50 dark:bg-orange-950/20 text-[#f95522] flex items-center justify-center font-extrabold text-xs sm:text-sm shrink-0">
                            06
                        </span>
                        <span class="font-extrabold text-[15px] md:text-[17px] text-gray-800 dark:text-slate-200 transition-colors duration-250 group-hover:text-[#f95522]">
                            {{ __('Mã giảm giá Shopee back lượt là gì?') }}
                        </span>
                    </div>
                    <div class="w-8 h-8 rounded-full flex items-center justify-center bg-gray-55 dark:bg-slate-800/80 text-gray-450 group-hover:bg-orange-100/40 group-hover:text-[#f95522] transition-colors shrink-0">
                        <i data-lucide="chevron-down" class="w-4 h-4"></i>
                    </div>
                </button>
            </div>
        </div>


    </section>
</div>
@endsection
