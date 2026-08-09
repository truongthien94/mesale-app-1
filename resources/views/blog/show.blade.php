@extends('layouts.app')

@php
    $savedSeoTitle = trim((string) optional($post->seoMeta)->meta_title);
    $savedSeoDescription = trim((string) optional($post->seoMeta)->meta_description);
    $descriptionSource = $savedSeoDescription !== ''
        ? $savedSeoDescription
        : ($post->summary ?: $post->content);
    $postSeoTitle = $savedSeoTitle !== '' ? $savedSeoTitle : $post->title;
    $postSeoDescription = Str::limit(Str::squish(strip_tags($descriptionSource)), 157, '...');
@endphp

{{-- SEO: Ưu tiên cấu hình riêng trong quản trị, fallback về nội dung bài viết --}}
@section('title', e($postSeoTitle))

{{-- SEO: OG Title riêng cho social sharing --}}
@section('og_title', e($post->title))

{{-- Dùng chung mô tả đã chuẩn hóa cho Google, Facebook và Twitter --}}
@section('meta_description', e($postSeoDescription))

{{-- SEO: Canonical URL trang chi tiết bài viết --}}
@section('canonical_url', route('blog.show', $post->slug))

{{-- SEO: OG Type article cho bài viết --}}
@section('og_type', 'article')

{{-- SEO: Dùng ảnh đại diện riêng của bài viết khi chia sẻ Facebook, Zalo và Twitter --}}
@php
    $postOgImage = $post->thumbnail
        ? (filter_var($post->thumbnail, FILTER_VALIDATE_URL)
            ? $post->thumbnail
            : asset(ltrim($post->thumbnail, '/')))
        : ($siteOgImage ?? '');
@endphp
@section('og_image', $postOgImage)
@section('og_image_alt', $post->title)

@section('styles')
{{-- JSON-LD Schema có sẵn từ controller --}}
<script type="application/ld+json">
{!! $schemaJson !!}
</script>
<link rel="stylesheet" href="{{ asset('css/blog.css') }}">
@endsection

@section('content')
<!-- Reading Progress Bar -->
<div x-data="{
    percent: 0,
    init() {
        window.addEventListener('scroll', () => {
            let winScroll = document.body.scrollTop || document.documentElement.scrollTop;
            let height = document.documentElement.scrollHeight - document.documentElement.clientHeight;
            this.percent = (winScroll / height) * 100;
        });
    }
}" x-init="init()" class="fixed top-[64px] left-0 right-0 h-1 bg-gray-100 dark:bg-slate-800 z-40">
    <div class="h-full bg-gradient-to-r from-shopee to-shopee-light" :style="'width: ' + percent + '%'"></div>
</div>

<div class="px-4 mx-auto max-w-7xl sm:px-6 lg:px-8 py-8 md:py-12 relative z-10" x-data="{ liked: false, likes: {{ $post->like_count }}, commentReplyId: null, commentReplyAuthor: '' }">
    <!-- Breadcrumb -->
    <nav class="flex mb-6 text-xs md:text-sm overflow-x-auto whitespace-nowrap scrollbar-none py-1" aria-label="Breadcrumb">
        <ol class="inline-flex items-center space-x-1 md:space-x-3 shrink-0">
            <li class="inline-flex items-center">
                <a href="{{ route('home') }}" class="inline-flex items-center text-gray-500 hover:text-shopee dark:text-slate-400">
                    <i data-lucide="home" class="w-3.5 h-3.5 mr-1.5"></i>
                    Trang chủ
                </a>
            </li>
            <li>
                <div class="flex items-center">
                    <i data-lucide="chevron-right" class="w-3.5 h-3.5 text-gray-400"></i>
                    <a href="{{ route('blog.index') }}" class="ml-1 text-gray-500 hover:text-shopee dark:text-slate-400 md:ml-2">Blog tin tức</a>
                </div>
            </li>
            @if($post->category)
            <li>
                <div class="flex items-center">
                    <i data-lucide="chevron-right" class="w-3.5 h-3.5 text-gray-400"></i>
                    <a href="{{ route('blog.category', $post->category->slug) }}" class="ml-1 text-gray-500 hover:text-shopee dark:text-slate-400 md:ml-2">{{ $post->category->name }}</a>
                </div>
            </li>
            @endif
            <li aria-current="page">
                <div class="flex items-center">
                    <i data-lucide="chevron-right" class="w-3.5 h-3.5 text-gray-400"></i>
                    <span class="ml-1 text-gray-700 dark:text-slate-200 font-medium md:ml-2 truncate max-w-[150px] md:max-w-[250px]">{{ $post->title }}</span>
                </div>
            </li>
        </ol>
    </nav>

    <!-- Main Container -->
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-12">
        <!-- Cột chính hiển thị bài viết (Col span 3) -->
        <div class="lg:col-span-3 space-y-8">
            <article class="bg-white dark:bg-slate-900 border border-gray-100 dark:border-slate-800 rounded-2xl sm:rounded-3xl p-4 sm:p-6 md:p-10 shadow-sm">
                <!-- Meta Info -->
                <div class="flex flex-wrap items-center gap-x-3 gap-y-2 text-xs md:text-sm text-gray-500 dark:text-slate-400 mb-6">
                    @if($post->category)
                    <a href="{{ route('blog.category', $post->category->slug) }}" class="px-3 py-1 font-bold text-shopee dark:text-shopee-light bg-shopee/10 dark:bg-shopee/25 rounded-full shrink-0">
                        {{ $post->category->name }}
                    </a>
                    @endif
                    <span class="flex items-center gap-1 shrink-0"><i data-lucide="user" class="w-3.5 h-3.5"></i> {{ $post->author->name ?? 'Admin' }}</span>
                    <span class="text-gray-300 dark:text-slate-700 hidden sm:inline">•</span>
                    {{-- Thẻ <time> với datetime chuẩn ISO 8601 giúp Google hiểu chính xác thời gian xuất bản --}}
                    <span class="flex items-center gap-1 shrink-0"><i data-lucide="calendar" class="w-3.5 h-3.5"></i> <time datetime="{{ $post->published_at ? $post->published_at->toIso8601String() : $post->created_at->toIso8601String() }}">{{ $post->published_at ? $post->published_at->format('d/m/Y') : $post->created_at->format('d/m/Y') }}</time></span>
                    <span class="text-gray-300 dark:text-slate-700 hidden sm:inline">•</span>
                    <span class="flex items-center gap-1 shrink-0"><i data-lucide="eye" class="w-3.5 h-3.5"></i> {{ number_format($post->view_count) }} lượt xem</span>
                </div>

                <!-- Tiêu đề -->
                <h1 class="text-xl sm:text-2xl md:text-4xl font-extrabold text-gray-900 dark:text-white leading-tight mb-5 md:mb-6">
                    {{ $post->title }}
                </h1>

                <!-- Tóm tắt ngắn -->
                @if($post->summary)
                <div class="p-4 md:p-6 rounded-2xl bg-gray-50 dark:bg-slate-800 border-l-4 border-shopee text-sm md:text-base text-gray-700 dark:text-slate-300 italic mb-6 md:mb-8">
                    {{ $post->summary }}
                </div>
                @endif

                <!-- Ảnh Thumbnail lớn -->
                @if($post->thumbnail)
                <div class="rounded-2xl sm:rounded-3xl overflow-hidden mb-6 md:mb-8 aspect-[16/9]">
                    <img src="{{ $post->thumbnail }}" alt="{{ $post->title }}" width="1600" height="900" loading="eager" fetchpriority="high" decoding="async" class="w-full h-full object-cover">
                </div>
                @endif

                <!-- 2. TABLE OF CONTENTS (Mục lục) - Mobile -->
                @if(count($toc) > 0)
                <div x-data="{ open: false }" class="rounded-2xl bg-gray-50 dark:bg-slate-800/25 border border-gray-200/60 dark:border-slate-800 mb-6 max-w-lg lg:hidden overflow-hidden transition-all duration-300">
                    <button @click="open = !open" class="w-full flex items-center justify-between px-5 py-4 font-bold text-slate-800 dark:text-white text-sm focus:outline-none hover:bg-gray-100/50 dark:hover:bg-slate-800/40 transition-colors">
                        <span class="flex items-center gap-2.5">
                            <i data-lucide="list" class="w-4.5 h-4.5 text-shopee"></i> Mục lục bài viết
                        </span>
                        <i data-lucide="chevron-down" class="w-4 h-4 text-gray-500 transition-transform duration-300" :class="open ? 'rotate-180' : ''"></i>
                    </button>
                    <div x-show="open" x-collapse x-cloak>
                        <div class="px-5 pb-5 pt-1 border-t border-gray-200/60 dark:border-slate-800/60">
                            <div class="relative border-l border-gray-250 dark:border-slate-700/60 py-1 space-y-1">
                                @foreach($toc as $item)
                                    @if($item['level'] == 2)
                                        <a href="#{{ $item['slug'] }}" 
                                           class="toc-link block text-gray-550 dark:text-slate-400 hover:text-shopee dark:hover:text-shopee-light text-sm transition-all duration-200 relative py-1.5 pl-4">
                                            <span class="toc-dot absolute -left-1 top-1/2 -translate-y-1/2 w-2 h-2 rounded-full bg-gray-300 dark:bg-slate-700 transition-all duration-200 border-2 border-white dark:border-slate-900"></span>
                                            {{ $item['title'] }}
                                        </a>
                                    @else
                                        <a href="#{{ $item['slug'] }}" 
                                           class="toc-link block text-gray-400 dark:text-slate-500 hover:text-shopee dark:hover:text-shopee-light text-[13px] transition-all duration-200 relative py-1.5 pl-8">
                                            <span class="toc-dot absolute -left-[3px] top-1/2 -translate-y-1/2 w-1.5 h-1.5 rounded-full bg-gray-300 dark:bg-slate-700 transition-all duration-200 border border-white dark:border-slate-900"></span>
                                            {{ $item['title'] }}
                                        </a>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                <!-- Nội dung chính HTML -->
                <div class="blog-content prose dark:prose-invert max-w-none">
                    @php
                        // Tạo anchor tags động cho các tiêu đề trong nội dung dựa theo TOC.
                        // Sử dụng 'raw_title' (có chứa HTML entities thô) để đảm bảo khớp Regex chính xác với bài viết gốc,
                        // phòng trường hợp tiêu đề chứa các thực thể HTML như &Agrave;, &Egrave;,...
                        $processedContent = $post->content;
                        foreach($toc as $item) {
                            $titleEscaped = preg_quote($item['raw_title'] ?? $item['title'], '/');
                            $processedContent = preg_replace(
                                '/<h' . $item['level'] . '([^>]*)>(' . $titleEscaped . ')<\/h' . $item['level'] . '>/i',
                                '<h' . $item['level'] . ' id="' . $item['slug'] . '"$1>$2</h' . $item['level'] . '>',
                                $processedContent,
                                1
                            );
                        }
                    @endphp
                    {!! $processedContent !!}
                </div>

                <!-- CTA nội bộ dẫn người đọc về công cụ tạo link hoàn tiền trên trang chủ -->
                <aside class="mt-8 rounded-2xl border border-shopee/20 bg-gradient-to-br from-shopee/10 via-white to-orange-50 p-5 dark:from-shopee/15 dark:via-slate-900 dark:to-slate-800 sm:p-6" aria-label="Công cụ hoàn tiền Mê Sale">
                    <div class="flex flex-col gap-5 sm:flex-row sm:items-center sm:justify-between">
                        <div class="flex items-start gap-4">
                            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-shopee text-white shadow-md shadow-shopee/20">
                                <i data-lucide="search" class="h-5 w-5"></i>
                            </div>
                            <div>
                                <h2 class="text-lg font-bold text-gray-900 dark:text-white">Kiểm tra tiền hoàn trước khi mua</h2>
                                <p class="mt-1 text-sm leading-relaxed text-gray-600 dark:text-slate-300">
                                    Dán link sản phẩm Shopee hoặc TikTok Shop để xem tiền hoàn dự kiến. Hãy đăng nhập trước khi mua để giao dịch được ghi nhận vào ví.
                                    Bạn cũng có thể <a href="{{ route('coupons.index') }}" class="font-semibold text-shopee hover:underline">xem mã giảm giá Shopee mới nhất</a> trước khi đặt hàng.
                                </p>
                            </div>
                        </div>
                        <a href="{{ route('home') }}#cong-cu-hoan-tien" class="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl bg-shopee px-5 py-3 text-sm font-bold text-white shadow-md shadow-shopee/20 transition-all hover:bg-shopee-dark hover:brightness-110">
                            Mở công cụ hoàn tiền
                            <i data-lucide="arrow-right" class="h-4 w-4"></i>
                        </a>
                    </div>
                </aside>

                <!-- Tags list -->
                @if($post->tags->count() > 0)
                <div class="flex flex-wrap gap-2 mt-8 pt-8 border-t border-gray-100 dark:border-slate-800">
                    @foreach($post->tags as $tag)
                    <a href="{{ route('blog.tag', $tag->slug) }}" class="px-3 py-1 text-xs text-gray-600 dark:text-slate-300 bg-gray-50 dark:bg-slate-800 hover:bg-shopee/10 hover:text-shopee rounded-lg transition-colors border border-gray-200/50 dark:border-slate-700/50">
                        #{{ $tag->name }}
                    </a>
                    @endforeach
                </div>
                @endif

                <!-- Like / Share Actions -->
                <div class="flex flex-wrap items-center justify-between gap-4 mt-8 pt-6 border-t border-gray-100 dark:border-slate-800">
                    <!-- Like button -->
                    <button @click="
                        axios.post('/blog/{{ $post->id }}/like')
                            .then(res => {
                                if(res.data.success) {
                                    liked = res.data.liked;
                                    likes = res.data.likes;
                                    window.dispatchEvent(new CustomEvent('toast', { detail: { text: liked ? 'Đã thích bài viết!' : 'Đã bỏ thích!', type: 'success' } }));
                                }
                            });
                    "
                    class="flex items-center gap-2 px-5 py-2.5 rounded-full border text-sm font-semibold transition-all"
                    :class="liked ? 'bg-shopee text-white border-shopee shadow-md shadow-shopee/20' : 'bg-white dark:bg-slate-900 border-gray-200 dark:border-slate-800 text-gray-700 dark:text-slate-300 hover:bg-gray-50 dark:hover:bg-slate-800'">
                        <i data-lucide="thumbs-up" class="w-4 h-4" :class="liked ? 'fill-current' : ''"></i>
                        <span>Thích bài viết</span>
                        <span class="px-2 py-0.5 rounded-full text-xs" :class="liked ? 'bg-white/20 text-white' : 'bg-gray-100 dark:bg-slate-800 text-gray-500'">
                            <span x-text="likes"></span>
                        </span>
                    </button>

                    <!-- Share buttons -->
                    <div class="flex items-center gap-2">
                        <span class="text-sm text-gray-500 dark:text-slate-400 font-medium">Chia sẻ:</span>
                        <!-- Sử dụng SVG trực tiếp cho Facebook vì thư viện Lucide local bị rút gọn thiếu icon này -->
                        <button @click="
                            axios.post('/blog/{{ $post->id }}/share', { platform: 'facebook' });
                            window.open('https://www.facebook.com/sharer/sharer.php?u=' + encodeURIComponent(window.location.href), '_blank');
                        " class="w-9 h-9 rounded-full bg-[#1877F2] text-white flex items-center justify-center hover:scale-105 transition-transform shadow-md">
                            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/>
                            </svg>
                        </button>
                        <!-- Sử dụng SVG trực tiếp cho Twitter vì thư viện Lucide local bị rút gọn thiếu icon này -->
                        <button @click="
                            axios.post('/blog/{{ $post->id }}/share', { platform: 'twitter' });
                            window.open('https://twitter.com/intent/tweet?url=' + encodeURIComponent(window.location.href), '_blank');
                        " class="w-9 h-9 rounded-full bg-[#1DA1F2] text-white flex items-center justify-center hover:scale-105 transition-transform shadow-md">
                            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M22 4s-.7 2.1-2 3.4c1.6 10-9.4 17.3-18 11.6 2.2.1 4.4-.6 6-2C3 15.5.5 9.6 3 5c2.2 2.6 5.6 4.1 9 4-.9-4.2 4-6.6 7-3.8 1.1 0 3-1.2 3-1.2z"/>
                            </svg>
                        </button>
                        <button @click="
                            axios.post('/blog/{{ $post->id }}/share', { platform: 'telegram' });
                            window.open('https://t.me/share/url?url=' + encodeURIComponent(window.location.href), '_blank');
                        " class="w-9 h-9 rounded-full bg-[#0088cc] text-white flex items-center justify-center hover:scale-105 transition-transform shadow-md">
                            <i data-lucide="send" class="w-5 h-5"></i>
                        </button>
                        <button @click="
                            navigator.clipboard.writeText(window.location.href);
                            axios.post('/blog/{{ $post->id }}/share', { platform: 'copy' });
                            window.dispatchEvent(new CustomEvent('toast', { detail: { text: 'Đã sao chép liên kết vào bộ nhớ tạm!', type: 'success' } }));
                        " class="w-9 h-9 rounded-full bg-gray-500 text-white flex items-center justify-center hover:scale-105 transition-transform shadow-md">
                            <i data-lucide="copy" class="w-5 h-5"></i>
                        </button>
                    </div>
                </div>
            </article>

            <!-- 3. COMMENTS SECTION -->
            <div class="bg-white dark:bg-slate-900 border border-gray-100 dark:border-slate-800 rounded-2xl sm:rounded-3xl p-4 sm:p-6 md:p-10 shadow-sm space-y-6 md:space-y-8">
                <h3 class="text-lg md:text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                    <i data-lucide="message-square" class="w-5 h-5 text-shopee"></i> Bình luận ({{ $post->comment_count }})
                </h3>

                <!-- Alert success/error -->
                @if(session('success'))
                <div class="p-4 rounded-xl bg-green-50 text-green-800 border border-green-200 flex items-center gap-2">
                    <i data-lucide="check-circle-2" class="w-5 h-5 text-green-500 shrink-0"></i>
                    <span>{{ session('success') }}</span>
                </div>
                @endif
                @if(session('error'))
                <div class="p-4 rounded-xl bg-red-50 text-red-800 border border-red-200 flex items-center gap-2">
                    <i data-lucide="alert-circle" class="w-5 h-5 text-red-500 shrink-0"></i>
                    <span>{{ session('error') }}</span>
                </div>
                @endif

                <!-- Comment Form -->
                <form action="/blog/{{ $post->id }}/comment" method="POST" class="space-y-4">
                    @csrf
                    <input type="hidden" name="parent_id" :value="commentReplyId">

                    <!-- Info Reply Badge -->
                    <div x-show="commentReplyId" x-cloak class="p-3 rounded-xl bg-gray-50 dark:bg-slate-800 flex items-center justify-between text-xs text-gray-600 dark:text-slate-400">
                        <span>Đang trả lời bình luận của <strong x-text="commentReplyAuthor"></strong></span>
                        <button type="button" @click="commentReplyId = null; commentReplyAuthor = ''" class="text-red-500 font-bold hover:underline">Hủy</button>
                    </div>

                    @guest
                    <!-- Khách điền thông tin -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 dark:text-slate-300 uppercase tracking-wider mb-2">Họ & Tên *</label>
                            <input type="text" name="author_name" required placeholder="Ví dụ: Nguyễn Văn A" class="w-full px-4 py-2.5 text-sm rounded-xl border border-gray-200 dark:border-slate-800 bg-gray-50 dark:bg-slate-900 focus:ring-2 focus:ring-shopee focus:outline-none dark:text-white">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-700 dark:text-slate-300 uppercase tracking-wider mb-2">Địa chỉ Email *</label>
                            <input type="email" name="author_email" required placeholder="Ví dụ: email@gmail.com" class="w-full px-4 py-2.5 text-sm rounded-xl border border-gray-200 dark:border-slate-800 bg-gray-50 dark:bg-slate-900 focus:ring-2 focus:ring-shopee focus:outline-none dark:text-white">
                        </div>
                    </div>
                    @endguest

                    <!-- Nội dung bình luận -->
                    <div>
                        <label class="block text-xs font-bold text-gray-700 dark:text-slate-300 uppercase tracking-wider mb-2">Nội dung bình luận *</label>
                        <textarea name="content" rows="4" required placeholder="Hãy chia sẻ suy nghĩ hoặc đặt câu hỏi của bạn tại đây..." class="w-full px-4 py-3 text-sm rounded-xl border border-gray-200 dark:border-slate-800 bg-gray-50 dark:bg-slate-900 focus:ring-2 focus:ring-shopee focus:outline-none dark:text-white"></textarea>
                    </div>

                    <!-- Gửi nút -->
                    <div class="flex justify-end">
                        <button type="submit" class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-shopee to-shopee-light text-white font-semibold rounded-xl hover:brightness-110 shadow-md shadow-shopee/10 transition-all">
                            Gửi bình luận <i data-lucide="send" class="w-4 h-4"></i>
                        </button>
                    </div>
                </form>

                <!-- List Comments -->
                <div class="space-y-6 pt-6 border-t border-gray-100 dark:border-slate-800">
                    @forelse($comments as $comment)
                    <div class="flex gap-3 md:gap-4 p-3 md:p-4 rounded-2xl bg-gray-50/50 dark:bg-slate-800/20 border border-gray-100/50 dark:border-slate-800/30">
                        <!-- Avatar -->
                        <div class="w-9 h-9 md:w-10 md:h-10 rounded-xl bg-shopee/10 text-shopee flex items-center justify-center font-bold text-sm shrink-0">
                            {{ substr($comment->author_name, 0, 1) }}
                        </div>
                        <div class="flex-grow min-w-0">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-1">
                                <span class="font-bold text-gray-900 dark:text-white text-sm truncate">{{ $comment->author_name }}</span>
                                <span class="text-[10px] md:text-xs text-gray-400">{{ $comment->created_at->diffForHumans() }}</span>
                            </div>
                            <p class="text-xs md:text-sm text-gray-700 dark:text-slate-300 mt-2 leading-relaxed whitespace-pre-line">
                                {{ $comment->content }}
                            </p>
                            <!-- Actions -->
                            <div class="flex items-center gap-4 mt-3 text-xs text-gray-500">
                                {{-- Sử dụng chỉ thị @json để ngăn chặn triệt để lỗ hổng Stored XSS khi gán chuỗi tên tác giả vào thuộc tính @click của AlpineJS --}}
                                <button @click="commentReplyId = {{ $comment->id }}; commentReplyAuthor = @json($comment->author_name)" class="hover:text-shopee flex items-center gap-1 font-semibold">
                                    <i data-lucide="reply" class="w-3.5 h-3.5"></i> Trả lời
                                </button>
                            </div>

                            <!-- Replies List -->
                            @if($comment->replies->count() > 0)
                            <div class="mt-4 pl-4 border-l-2 border-gray-200 dark:border-slate-800 space-y-4">
                                @foreach($comment->replies as $reply)
                                <div class="flex gap-3">
                                    <div class="w-8 h-8 rounded-lg bg-purple-500/10 text-purple-500 flex items-center justify-center font-bold text-xs shrink-0">
                                        {{ substr($reply->author_name, 0, 1) }}
                                    </div>
                                    <div class="flex-grow min-w-0">
                                        <div class="flex items-center justify-between">
                                            <span class="font-bold text-gray-800 dark:text-slate-200 text-xs">{{ $reply->author_name }}</span>
                                            <span class="text-[10px] text-gray-400">{{ $reply->created_at->diffForHumans() }}</span>
                                        </div>
                                        <p class="text-xs text-gray-600 dark:text-slate-400 mt-1 leading-relaxed">
                                            {{ $reply->content }}
                                        </p>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                            @endif
                        </div>
                    </div>
                    @empty
                    <div class="text-center text-gray-450 dark:text-slate-500 py-6">
                        Chưa có bình luận nào cho bài viết này. Hãy là người đầu tiên để lại ý kiến!
                    </div>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Cột Sidebar: TOC (Mục lục cuộn) và Bài viết liên quan -->
        <div class="lg:col-span-1 space-y-8">
            <!-- Widget 1: TOC Desktop Sticky -->
            @if(count($toc) > 0)
            <div class="p-6 rounded-2xl bg-white dark:bg-slate-900 border border-gray-100 dark:border-slate-850 shadow-sm sticky top-24 hidden lg:block transition-all duration-300">
                <h3 class="text-xs font-extrabold text-slate-900 dark:text-slate-100 uppercase tracking-widest mb-5 flex items-center gap-2">
                    <span class="w-1.5 h-3.5 bg-shopee rounded-full"></span>
                    Mục lục bài viết
                </h3>
                <div class="relative border-l border-gray-200 dark:border-slate-800/60 py-1 space-y-1">
                    @foreach($toc as $item)
                        @if($item['level'] == 2)
                            <a href="#{{ $item['slug'] }}" 
                               class="toc-link block text-gray-500 dark:text-slate-400 hover:text-shopee dark:hover:text-shopee-light text-sm transition-all duration-200 relative py-1.5 pl-4">
                                <span class="toc-dot absolute -left-1 top-1/2 -translate-y-1/2 w-2 h-2 rounded-full bg-gray-300 dark:bg-slate-700 transition-all duration-200 border-2 border-white dark:border-slate-900"></span>
                                {{ $item['title'] }}
                            </a>
                        @else
                            <a href="#{{ $item['slug'] }}" 
                               class="toc-link block text-gray-400 dark:text-slate-500 hover:text-shopee dark:hover:text-shopee-light text-[13px] transition-all duration-200 relative py-1.5 pl-8">
                                <span class="toc-dot absolute -left-[3px] top-1/2 -translate-y-1/2 w-1.5 h-1.5 rounded-full bg-gray-300 dark:bg-slate-700 transition-all duration-200 border border-white dark:border-slate-900"></span>
                                {{ $item['title'] }}
                            </a>
                        @endif
                    @endforeach
                </div>
            </div>
            @endif

            <!-- Widget 2: Related posts -->
            @if($relatedPosts->count() > 0)
            <div class="p-6 rounded-2xl bg-white dark:bg-slate-900 border border-gray-100 dark:border-slate-800 shadow-sm">
                <h3 class="text-sm font-bold text-gray-900 dark:text-white uppercase tracking-wider mb-4 flex items-center gap-2">
                    <i data-lucide="link" class="w-4 h-4 text-shopee"></i> Bài viết liên quan
                </h3>
                <div class="space-y-4">
                    @foreach($relatedPosts as $rPost)
                    <div class="group flex gap-3">
                        @if($rPost->thumbnail)
                        <div class="w-16 h-12 rounded-lg overflow-hidden shrink-0">
                            <img src="{{ $rPost->thumbnail }}" alt="{{ $rPost->title }}" width="160" height="120" loading="lazy" decoding="async" class="w-full h-full object-cover">
                        </div>
                        @endif
                        <div class="min-w-0">
                            <h4 class="text-xs font-bold text-gray-800 dark:text-slate-200 group-hover:text-shopee transition-colors line-clamp-2">
                                <a href="{{ route('blog.show', $rPost->slug) }}">{{ $rPost->title }}</a>
                            </h4>
                            <span class="text-[10px] text-gray-400 block mt-1">
                                {{ $rPost->published_at ? $rPost->published_at->format('d/m/Y') : $rPost->created_at->format('d/m/Y') }}
                            </span>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="{{ asset('js/blog-toc.js') }}"></script>
@endsection


