{{--
    Block Câu hỏi thường gặp (FAQ) — accordion Alpine.
    Lưu ý SEO: dữ liệu có cấu trúc FAQPage KHÔNG được in tại đây mà gom chung cho toàn trang chủ ở
    App\Services\HomeSchemaService, vì Google chỉ chấp nhận duy nhất một khối FAQPage cho mỗi URL.
    Trước đây mỗi block tự in schema riêng nên khi Admin thêm từ 2 block FAQ trở lên sẽ sinh ra
    nhiều thẻ FAQPage trùng lặp và bị Search Console báo lỗi, mất rich snippet.
--}}
@php
    $faqItems = collect($s['items'] ?? [])
        ->filter(fn($it) => !empty(trim($it['question'] ?? '')))
        ->values();
    // Mã định danh duy nhất cho tiêu đề khu vực, dùng cho aria-labelledby (trợ năng + ngữ nghĩa SEO)
    $faqHeadingId = 'faq-' . substr(md5(($s['title'] ?? '') . $faqItems->count() . ($s['badge'] ?? '')), 0, 8);
@endphp
@if($faqItems->count() > 0)
<section aria-labelledby="{{ $faqHeadingId }}" class="px-4 mx-auto max-w-7xl sm:px-6 lg:px-8 pt-16 md:pt-24 border-t border-gray-100/60 dark:border-slate-800/40">
    <div class="bg-gradient-to-br from-white/90 to-orange-50/45 dark:from-slate-900/60 dark:to-slate-900/20 p-8 md:p-12 rounded-[32px] border border-orange-100/50 dark:border-slate-800/60 shadow-sm">
        {{-- Tiêu đề khu vực --}}
        <div class="text-center max-w-2xl mx-auto mb-10 md:mb-12">
            @if(!empty($s['badge']))
            <span class="inline-flex items-center gap-1.5 px-3.5 py-1 rounded-full text-xs font-bold bg-orange-100 text-shopee dark:bg-orange-950/30 dark:text-orange-400">
                <i data-lucide="help-circle" class="w-3.5 h-3.5"></i>
                {{ $s['badge'] }}
            </span>
            @endif
            @if(!empty($s['title']))
            <h2 id="{{ $faqHeadingId }}" class="text-2xl md:text-3xl font-extrabold text-gray-900 dark:text-white mt-3 leading-tight">{{ $s['title'] }}</h2>
            @endif
            @if(!empty($s['subtitle']))
            <p class="text-sm text-gray-500 dark:text-slate-400 mt-2">{{ $s['subtitle'] }}</p>
            @endif
        </div>

        {{-- Danh sách accordion --}}
        <div class="max-w-3xl mx-auto space-y-3" x-data="{ open: 0 }">
            @foreach($faqItems as $index => $item)
            @php $panelId = $faqHeadingId . '-panel-' . ($index + 1); @endphp
            <div class="bg-white dark:bg-slate-900 rounded-2xl border border-gray-150 dark:border-slate-800/80 overflow-hidden transition-all">
                {{--
                    Câu hỏi dùng thẻ <h3> để giữ đúng thứ bậc tiêu đề (h2 khu vực > h3 câu hỏi),
                    giúp công cụ tìm kiếm và trình đọc màn hình hiểu chính xác cấu trúc nội dung.
                --}}
                <h3>
                    <button type="button" @click="open === {{ $index + 1 }} ? open = 0 : open = {{ $index + 1 }}"
                        :aria-expanded="open === {{ $index + 1 }} ? 'true' : 'false'" aria-controls="{{ $panelId }}"
                        class="w-full flex items-center justify-between gap-4 px-5 py-4 text-left">
                        <span class="text-sm font-bold text-gray-900 dark:text-white">{{ $item['question'] }}</span>
                        <i data-lucide="chevron-down" class="w-5 h-5 text-shopee shrink-0 transition-transform duration-300"
                            :class="open === {{ $index + 1 }} ? 'rotate-180' : ''"></i>
                    </button>
                </h3>
                @if(!empty(trim($item['answer'] ?? '')))
                <div id="{{ $panelId }}" x-show="open === {{ $index + 1 }}" x-collapse x-cloak>
                    <div class="px-5 pb-4 text-sm text-gray-500 dark:text-slate-400 leading-relaxed border-t border-gray-100 dark:border-slate-800 pt-3">
                        {!! nl2br(e($item['answer'])) !!}
                    </div>
                </div>
                @endif
            </div>
            @endforeach
        </div>
    </div>
</section>
@endif
