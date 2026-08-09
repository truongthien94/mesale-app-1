{{-- 
    View: Hiển thị trang nội dung tĩnh (Page) cho người dùng frontend.
    Dùng cho: Điều khoản dịch vụ, Chính sách bảo mật, và các trang tùy chỉnh khác.
    SEO: Tối ưu hoàn toàn theo chuẩn WordPress với JSON-LD Schema, Breadcrumb, OG Tags, Canonical URL.
--}}
@extends('layouts.app')

@php
    $plainContent = html_entity_decode(
        strip_tags((string) ($page->content ?? '')),
        ENT_QUOTES | ENT_HTML5,
        'UTF-8'
    );
    $plainContent = trim((string) preg_replace('/\s+/u', ' ', $plainContent));
    $pageDescription = $page->meta_description ?: Str::limit($plainContent, 160, '...');
@endphp

{{-- ===== SEO META TAGS ===== --}}

{{-- Title tag: tên trang ngắn gọn kèm thương hiệu --}}
@section('title', $page->title . ' | Mê Sale')

{{-- OG Title riêng cho social sharing (không cần kèm tên site) --}}
@section('og_title', $page->title)

{{-- Meta description: ưu tiên meta_description tùy chỉnh của trang, nếu không có thì tự cắt 160 ký tự đầu từ nội dung --}}
@section('meta_description', $pageDescription)

{{-- Canonical URL giúp Google xác định đường dẫn chính thức, tránh trùng lặp nội dung --}}
@section('canonical_url', url('/page/' . $page->slug))

{{-- Chặn tìm kiếm (noindex) nếu trang được cấu hình ẩn trên công cụ tìm kiếm --}}
@if($page->is_noindex)
@section('meta_robots')
    <meta name="robots" content="noindex, nofollow">
@endsection
@endif

{{-- OG Type: article phù hợp hơn website cho trang nội dung có nội dung bài viết --}}
@section('og_type', 'article')

{{-- ===== JSON-LD STRUCTURED DATA (Google Rich Results) ===== --}}
@section('seo_schema')
<script type="application/ld+json">
{!! json_encode([
    // Schema WebPage: giúp Google hiểu đây là trang nội dung chính thức
    '@' . 'context' => 'https://schema.org',
    '@graph' => [
        [
            '@type' => 'WebPage',
            '@id' => url('/page/' . $page->slug) . '#webpage',
            'url' => url('/page/' . $page->slug),
            'name' => $page->title,
            'description' => $pageDescription,
            'isPartOf' => [
                '@id' => url('/') . '#website',
            ],
            'datePublished' => $page->created_at->toIso8601String(),
            'dateModified' => $page->updated_at->toIso8601String(),
            'inLanguage' => 'vi-VN',
        ],
        // Schema Website: đại diện cho toàn bộ website
        [
            '@type' => 'WebSite',
            '@id' => url('/') . '#website',
            'url' => url('/'),
            'name' => $siteName,
            'description' => $siteDescription,
            'inLanguage' => 'vi-VN',
        ],
        // Schema BreadcrumbList: giúp Google hiển thị breadcrumb trong kết quả tìm kiếm
        [
            '@type' => 'BreadcrumbList',
            'itemListElement' => [
                [
                    '@type' => 'ListItem',
                    'position' => 1,
                    'name' => 'Trang chủ',
                    'item' => url('/'),
                ],
                [
                    '@type' => 'ListItem',
                    'position' => 2,
                    'name' => $page->title,
                    'item' => url('/page/' . $page->slug),
                ],
            ],
        ],
    ],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}
</script>
@endsection

{{-- ===== NỘI DUNG TRANG ===== --}}
@section('content')
<div class="max-w-4xl mx-auto px-4 py-8 sm:px-6 lg:px-8">

    {{-- 
        Breadcrumb Navigation (SEO + UX)
        Sử dụng semantic HTML <nav> với aria-label để screen reader và bot hiểu cấu trúc điều hướng.
        Thẻ <ol> giúp Google bot nhận diện thứ tự phân cấp trang.
    --}}
    <nav aria-label="Breadcrumb" class="mb-6">
        <ol class="flex items-center gap-2 text-xs md:text-sm text-gray-500 dark:text-slate-400">
            <li>
                <a href="{{ route('home') }}" class="hover:text-shopee dark:hover:text-shopee-light transition-all">
                    {{ __('Trang chủ') }}
                </a>
            </li>
            <li class="flex items-center gap-2">
                <i data-lucide="chevron-right" class="w-3.5 h-3.5 text-gray-400"></i>
                <span class="text-gray-900 dark:text-white font-medium" aria-current="page">{{ $page->title }}</span>
            </li>
        </ol>
    </nav>

    {{-- 
        Khối nội dung chính bọc trong thẻ <article> theo semantic HTML.
        Thẻ <article> giúp crawler xác định đây là khối nội dung chính của trang,
        tương tự cách WordPress render single page content.
    --}}
    <article class="bg-white/70 dark:bg-slate-900/50 backdrop-blur-sm border border-gray-100/80 dark:border-slate-800/60 rounded-2xl shadow-sm p-6 sm:p-8 md:p-10">
        
        {{-- 
            Tiêu đề trang dùng thẻ <h1> duy nhất trên mỗi trang (chuẩn SEO).
            Một trang chỉ nên có duy nhất 1 thẻ <h1>.
        --}}
        <header class="mb-6 pb-4 border-b border-gray-100 dark:border-slate-800">
            <h1 class="text-2xl md:text-3xl font-bold text-gray-900 dark:text-white leading-tight">
                {{ $page->title }}
            </h1>
            {{-- Hiển thị thời gian cập nhật ngay dưới tiêu đề giúp Google đánh giá độ mới nội dung --}}
            <div class="flex items-center gap-3 mt-3 text-xs text-gray-400 dark:text-slate-500">
                <span class="flex items-center gap-1">
                    <i data-lucide="calendar" class="w-3.5 h-3.5"></i>
                    {{ __('Cập nhật') }}: 
                    {{-- Thẻ <time> với datetime chuẩn ISO 8601 giúp Google hiểu chính xác thời gian --}}
                    <time datetime="{{ $page->updated_at->toIso8601String() }}">
                        {{ $page->updated_at->format('d/m/Y') }}
                    </time>
                </span>
            </div>
        </header>

        {{-- 
            Mục lục tự động (Table of Contents) - Tự phát hiện các heading h2 trong nội dung.
            Giúp cải thiện:
            - UX: Người dùng dễ dàng nhảy đến phần mong muốn
            - SEO: Google có thể hiển thị In-page links trong SERP (sitelinks)
            Chỉ hiển thị khi nội dung có ít nhất 1 heading h2.
        --}}
        @php
            // Trích xuất tất cả thẻ <h2> từ nội dung HTML để xây dựng mục lục tự động
            preg_match_all('/<h2[^>]*>(.*?)<\/h2>/i', $page->content ?? '', $headings);
            $tocItems = array_map(static function ($heading) {
                $heading = html_entity_decode(
                    strip_tags((string) $heading),
                    ENT_QUOTES | ENT_HTML5,
                    'UTF-8'
                );

                return trim((string) preg_replace('/\s+/u', ' ', $heading));
            }, $headings[1] ?? []);
        @endphp

        @if(count($tocItems) >= 2)
        <nav aria-label="{{ __('Mục lục') }}" class="mb-8 p-4 sm:p-5 bg-gray-50/80 dark:bg-slate-800/40 border border-gray-100 dark:border-slate-700/50 rounded-xl">
            <h2 class="text-sm font-bold text-gray-700 dark:text-slate-300 uppercase tracking-wider mb-3 flex items-center gap-2">
                <i data-lucide="list" class="w-4 h-4 text-shopee"></i>
                {{ __('Mục lục') }}
            </h2>
            <ol class="space-y-1.5 list-decimal list-inside text-sm text-gray-600 dark:text-slate-400">
                @foreach($tocItems as $index => $heading)
                    <li>
                        <a href="#section-{{ $index + 1 }}" class="hover:text-shopee transition-all hover:underline">
                            {{ $heading }}
                        </a>
                    </li>
                @endforeach
            </ol>
        </nav>
        @endif

        {{-- 
            Nội dung HTML chính từ trình soạn thảo TinyMCE.
            Các class prose của Tailwind Typography giúp render nội dung HTML đẹp mắt.
            Tự động gán ID cho mỗi h2 để mục lục có thể scroll đến đúng vị trí.
        --}}
        <div class="prose prose-sm md:prose-base max-w-none dark:prose-invert
                    prose-headings:font-bold prose-headings:text-gray-900 dark:prose-headings:text-white
                    prose-p:text-gray-600 dark:prose-p:text-slate-300
                    prose-a:text-shopee prose-a:no-underline hover:prose-a:underline
                    prose-strong:text-gray-800 dark:prose-strong:text-white
                    prose-ul:text-gray-600 dark:prose-ul:text-slate-300
                    prose-ol:text-gray-600 dark:prose-ol:text-slate-300
                    prose-li:marker:text-shopee
                    prose-img:rounded-xl prose-img:shadow-sm
                    prose-table:text-sm prose-th:bg-gray-50 dark:prose-th:bg-slate-800
                    [&_h2]:text-xl [&_h2]:mt-8 [&_h2]:mb-4 [&_h2]:scroll-mt-20
                    [&_h3]:text-lg [&_h3]:mt-6 [&_h3]:mb-3
                    [&_p]:leading-relaxed [&_p]:mb-4
                    [&_ul]:my-4 [&_ol]:my-4
                    [&_li]:mb-2">
            @php
                // Tự động thêm id anchor vào mỗi thẻ <h2> để hỗ trợ liên kết nội bộ từ mục lục
                $contentHtml = $page->content ?? '';
                $h2Counter = 0;
                $contentHtml = preg_replace_callback('/<h2([^>]*)>/i', function($matches) use (&$h2Counter) {
                    $h2Counter++;
                    // Giữ nguyên attributes gốc và thêm id nếu chưa có
                    $attrs = $matches[1];
                    if (stripos($attrs, 'id=') === false) {
                        return '<h2' . $attrs . ' id="section-' . $h2Counter . '">';
                    }
                    return $matches[0];
                }, $contentHtml);
            @endphp
            {!! $contentHtml !!}
        </div>

        {{-- 
            Footer của article: thông tin thời gian xuất bản và cập nhật.
            Sử dụng thẻ <footer> semantic HTML bên trong <article>.
        --}}
        <footer class="mt-8 pt-4 border-t border-gray-100 dark:border-slate-800">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 text-xs text-gray-400 dark:text-slate-500">
                <div class="flex items-center gap-4">
                    <span class="flex items-center gap-1">
                        <i data-lucide="file-text" class="w-3.5 h-3.5"></i>
                        {{ __('Xuất bản') }}: 
                        <time datetime="{{ $page->created_at->toIso8601String() }}">
                            {{ $page->created_at->format('d/m/Y') }}
                        </time>
                    </span>
                    @if($page->created_at->format('Y-m-d') !== $page->updated_at->format('Y-m-d'))
                    <span class="flex items-center gap-1">
                        <i data-lucide="edit-3" class="w-3.5 h-3.5"></i>
                        {{ __('Chỉnh sửa') }}: 
                        <time datetime="{{ $page->updated_at->toIso8601String() }}">
                            {{ $page->updated_at->format('d/m/Y') }}
                        </time>
                    </span>
                    @endif
                </div>
            </div>
        </footer>
    </article>
</div>
@endsection
