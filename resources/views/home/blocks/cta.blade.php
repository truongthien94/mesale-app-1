{{-- Block Dải kêu gọi hành động (CTA Band) — chốt chuyển đổi, 3 kiểu nền --}}
@php
    use App\Helpers\LinkHelper;

    $ctaStyle = $s['style'] ?? 'gradient';
    $ctaTitle = trim($s['title'] ?? '');
    $ctaBtnText = trim($s['btn_text'] ?? '');
    // Nút chính: mặc định trỏ tới trang đăng ký khi Admin để trống liên kết.
    // Mọi liên kết do Admin nhập đều đi qua LinkHelper::safe để loại bỏ giao thức nguy hiểm (javascript:).
    $ctaBtnLink = LinkHelper::safe($s['btn_link'] ?? '') ?: (Auth::check() ? route('dashboard') : route('register'));
    $ctaBtn2Text = trim($s['btn2_text'] ?? '');
    $ctaBtn2Link = LinkHelper::safe($s['btn2_link'] ?? '');
    $ctaHeadingId = 'cta-' . substr(md5($ctaTitle . $ctaStyle), 0, 8);

    // Bảng lớp CSS theo kiểu nền — dùng biến màu thương hiệu động (shopee)
    $ctaWrap = match ($ctaStyle) {
        'solid' => 'bg-shopee text-white',
        'soft'  => 'bg-orange-50 dark:bg-slate-900/60 text-gray-900 dark:text-white border border-orange-100 dark:border-slate-800',
        default => 'bg-gradient-to-br from-shopee to-shopee-dark text-white',
    };
    $isDark = $ctaStyle !== 'soft';
    $subColor = $isDark ? 'text-white/85' : 'text-gray-500 dark:text-slate-400';
    $iconWrap = $isDark ? 'bg-white/15 text-white' : 'bg-shopee/10 text-shopee';
    $btnMain  = $isDark ? 'bg-white text-shopee hover:bg-white/90' : 'bg-shopee text-white hover:bg-shopee-dark';
    $btnAlt   = $isDark ? 'bg-white/10 text-white hover:bg-white/20 border border-white/25' : 'bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-200 hover:bg-gray-50 dark:hover:bg-slate-700 border border-gray-200 dark:border-slate-700';
@endphp
@if($ctaTitle !== '')
<section aria-labelledby="{{ $ctaHeadingId }}" class="px-4 mx-auto max-w-7xl sm:px-6 lg:px-8 pt-16 md:pt-24">
    <div class="relative overflow-hidden rounded-[32px] p-8 md:p-12 shadow-lg {{ $ctaWrap }}">
        {{-- Hoạ tiết trang trí nền (chỉ với kiểu nền đậm) --}}
        @if($isDark)
        <div class="absolute -top-16 -right-16 w-56 h-56 rounded-full bg-white/10 blur-2xl pointer-events-none"></div>
        <div class="absolute -bottom-20 -left-10 w-52 h-52 rounded-full bg-black/10 blur-2xl pointer-events-none"></div>
        @endif

        <div class="relative flex flex-col md:flex-row md:items-center md:justify-between gap-6">
            <div class="flex items-start gap-4 max-w-2xl">
                @if(!empty($s['icon']))
                <div class="w-12 h-12 rounded-2xl {{ $iconWrap }} flex items-center justify-center shrink-0">
                    <i data-lucide="{{ $s['icon'] }}" class="w-6 h-6"></i>
                </div>
                @endif
                <div>
                    <h2 id="{{ $ctaHeadingId }}" class="text-2xl md:text-3xl font-extrabold leading-tight">{{ $ctaTitle }}</h2>
                    @if(!empty(trim($s['subtitle'] ?? '')))
                    <p class="text-sm mt-2 {{ $subColor }} leading-relaxed">{{ $s['subtitle'] }}</p>
                    @endif
                </div>
            </div>

            @if($ctaBtnText !== '' || $ctaBtn2Text !== '')
            <div class="flex flex-col sm:flex-row gap-3 shrink-0">
                @if($ctaBtnText !== '')
                <a href="{{ $ctaBtnLink }}" {!! LinkHelper::anchorAttrs($ctaBtnLink) !!} class="inline-flex items-center justify-center gap-2 px-6 py-3 rounded-2xl text-sm font-bold shadow-md transition-all {{ $btnMain }}">
                    {{ $ctaBtnText }}
                    <i data-lucide="arrow-right" class="w-4 h-4"></i>
                </a>
                @endif
                @if($ctaBtn2Text !== '' && $ctaBtn2Link !== '')
                <a href="{{ $ctaBtn2Link }}" {!! LinkHelper::anchorAttrs($ctaBtn2Link) !!} class="inline-flex items-center justify-center gap-2 px-6 py-3 rounded-2xl text-sm font-bold transition-all {{ $btnAlt }}">
                    {{ $ctaBtn2Text }}
                </a>
                @endif
            </div>
            @endif
        </div>
    </div>
</section>
@endif
