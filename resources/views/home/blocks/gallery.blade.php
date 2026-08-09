{{-- Block Ảnh + Văn bản / Gallery — hỗ trợ 3 kiểu hiển thị: grid, split, logos --}}
@php
    use App\Helpers\LinkHelper;

    $galleryItems = collect($s['items'] ?? [])->filter(fn($it) => !empty($it['image']) || !empty($it['title']) || !empty($it['text']))->values();
    $galleryLayout = $s['layout'] ?? 'grid';
    $galleryHeadingId = 'gallery-' . substr(md5(($s['title'] ?? '') . $galleryLayout . $galleryItems->count()), 0, 8);
@endphp
@if($galleryItems->count() > 0)
<section @if(!empty($s['title'])) aria-labelledby="{{ $galleryHeadingId }}" @endif class="px-4 mx-auto max-w-7xl sm:px-6 lg:px-8 pt-16 md:pt-24 border-t border-gray-100/60 dark:border-slate-800/40">
    <div class="bg-gradient-to-br from-white/90 to-gray-50/45 dark:from-slate-900/60 dark:to-slate-900/20 p-8 md:p-12 rounded-[32px] border border-gray-100/50 dark:border-slate-800/60 shadow-sm">
        {{-- Tiêu đề khu vực --}}
        @if(!empty($s['title']) || !empty($s['subtitle']))
        <div class="text-center max-w-2xl mx-auto mb-10 md:mb-12">
            @if(!empty($s['title']))
            <h2 id="{{ $galleryHeadingId }}" class="text-2xl md:text-3xl font-extrabold text-gray-900 dark:text-white leading-tight">{{ $s['title'] }}</h2>
            @endif
            @if(!empty($s['subtitle']))
            <p class="text-sm text-gray-500 dark:text-slate-400 mt-2">{{ $s['subtitle'] }}</p>
            @endif
        </div>
        @endif

        @if($galleryLayout === 'split')
            {{-- Kiểu 2 cột: ảnh bên trái, văn bản bên phải, xen kẽ --}}
            <div class="space-y-10 md:space-y-16">
            @foreach($galleryItems as $index => $item)
            @php
                // Lọc URL chặn giao thức nguy hiểm (javascript:) trước khi in ra thuộc tính href/src
                $itemLink = LinkHelper::safe($item['link'] ?? '');
                $itemImage = LinkHelper::safe($item['image'] ?? '');
                // Thuộc tính alt luôn có nội dung mô tả: ưu tiên tiêu đề mục, sau đó tới tiêu đề khu vực
                $itemAlt = trim($item['title'] ?? '') ?: (trim($s['title'] ?? '') ?: $siteName);
            @endphp
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 md:gap-10 items-center">
                <div class="{{ $index % 2 === 1 ? 'md:order-2' : '' }}">
                    @if($itemImage !== '')
                    <div class="rounded-3xl overflow-hidden shadow-lg border border-gray-100 dark:border-slate-800">
                        {{-- width/height giữ sẵn tỉ lệ khung ảnh, tránh nhảy bố cục (CLS) — yếu tố xếp hạng Core Web Vitals --}}
                        <img src="{{ $itemImage }}" alt="{{ $itemAlt }}" width="800" height="600" class="w-full h-full object-cover" loading="lazy" decoding="async">
                    </div>
                    @endif
                </div>
                <div class="{{ $index % 2 === 1 ? 'md:order-1' : '' }} space-y-3">
                    @if(!empty($item['title']))
                    <h3 class="text-xl font-extrabold text-gray-900 dark:text-white leading-snug">{{ $item['title'] }}</h3>
                    @endif
                    @if(!empty($item['text']))
                    <p class="text-sm text-gray-500 dark:text-slate-400 leading-relaxed">{{ $item['text'] }}</p>
                    @endif
                    @if($itemLink !== '')
                    <a href="{{ $itemLink }}" {!! LinkHelper::anchorAttrs($itemLink) !!} class="inline-flex items-center gap-1.5 text-sm font-bold text-shopee hover:text-shopee-dark transition-colors">
                        {{ __('Xem thêm') }} <i data-lucide="arrow-right" class="w-4 h-4"></i>
                    </a>
                    @endif
                </div>
            </div>
            @endforeach
        </div>

    @elseif($galleryLayout === 'logos')
        {{-- Dải logo đối tác --}}
        <div class="flex flex-wrap items-center justify-center gap-6 md:gap-10">
            @foreach($galleryItems as $item)
            @php
                $itemLink = LinkHelper::safe($item['link'] ?? '');
                $itemImage = LinkHelper::safe($item['image'] ?? '');
                $itemAlt = trim($item['title'] ?? '') ?: __('Logo đối tác');
                $logo = $itemLink !== '' ? 'a' : 'div';
            @endphp
            <{{ $logo }} @if($itemLink !== '') href="{{ $itemLink }}" {!! LinkHelper::anchorAttrs($itemLink) !!} @endif class="opacity-70 hover:opacity-100 transition-all grayscale hover:grayscale-0">
                @if($itemImage !== '')
                <img src="{{ $itemImage }}" alt="{{ $itemAlt }}" width="160" height="48" class="h-10 md:h-12 w-auto object-contain" loading="lazy" decoding="async">
                @else
                <span class="text-sm font-bold text-gray-500 dark:text-slate-400">{{ $item['title'] ?? '' }}</span>
                @endif
            </{{ $logo }}>
            @endforeach
        </div>

    @else
        {{-- Kiểu lưới thẻ (grid) mặc định --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($galleryItems as $item)
            @php
                $itemLink = LinkHelper::safe($item['link'] ?? '');
                $itemImage = LinkHelper::safe($item['image'] ?? '');
                $itemAlt = trim($item['title'] ?? '') ?: (trim($s['title'] ?? '') ?: $siteName);
                $wrap = $itemLink !== '' ? 'a' : 'div';
            @endphp
            <{{ $wrap }} @if($itemLink !== '') href="{{ $itemLink }}" {!! LinkHelper::anchorAttrs($itemLink) !!} @endif
                class="group bg-white dark:bg-slate-900 rounded-3xl overflow-hidden shadow-md dark:shadow-none border border-gray-150 dark:border-slate-800/80 hover:shadow-xl transition-all duration-300 flex flex-col">
                @if($itemImage !== '')
                <div class="relative h-48 overflow-hidden bg-gray-100 dark:bg-slate-800 shrink-0">
                    <img src="{{ $itemImage }}" alt="{{ $itemAlt }}" width="600" height="384" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" loading="lazy" decoding="async">
                </div>
                @endif
                @if(!empty($item['title']) || !empty($item['text']))
                <div class="p-6 flex-grow space-y-2">
                    @if(!empty($item['title']))
                    <h3 class="font-bold text-gray-900 dark:text-white text-base leading-snug group-hover:text-shopee transition-colors">{{ $item['title'] }}</h3>
                    @endif
                    @if(!empty($item['text']))
                    <p class="text-xs text-gray-500 dark:text-slate-400 leading-relaxed">{{ $item['text'] }}</p>
                    @endif
                </div>
                @endif
            </{{ $wrap }}>
            @endforeach
        </div>
        @endif
    </div>
</section>
@endif
