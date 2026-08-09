@extends('layouts.app')

{{-- SEO: Title tag trang danh sách blog --}}
@section('title', 'Blog Săn Sale & Hoàn Tiền | Mê Sale')

{{-- SEO: Tiêu đề social riêng, không fallback về site_name dài --}}
@section('og_title', 'Blog Săn Sale & Hoàn Tiền')
@section('og_image_alt', 'Blog Săn Sale và Hoàn Tiền')

{{-- SEO: Meta description trang blog tổng --}}
@section('meta_description', 'Mẹo săn sale Shopee, TikTok Shop và hướng dẫn tối ưu hoàn tiền giúp bạn mua sắm tiết kiệm, kiểm soát chi tiêu cùng Mê Sale.')

{{-- SEO: Canonical URL trang blog --}}
@section('canonical_url', route('blog.index'))

{{-- SEO: JSON-LD CollectionPage + BreadcrumbList --}}
@section('seo_schema')
<script type="application/ld+json">
{!! json_encode([
    '@' . 'context' => 'https://schema.org',
    '@graph' => [
        [
            '@type' => 'CollectionPage',
            '@id' => route('blog.index') . '#webpage',
            'url' => route('blog.index'),
            'name' => 'Blog Chia Sẻ & Hướng Dẫn - ' . $siteName,
            'isPartOf' => ['@id' => url('/') . '#website'],
            'inLanguage' => 'vi-VN',
        ],
        [
            '@type' => 'BreadcrumbList',
            'itemListElement' => [
                ['@type' => 'ListItem', 'position' => 1, 'name' => 'Trang chủ', 'item' => url('/')],
                ['@type' => 'ListItem', 'position' => 2, 'name' => 'Blog tin tức', 'item' => route('blog.index')],
            ],
        ],
    ],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}
</script>
@endsection

@section('content')
<div class="px-4 mx-auto max-w-7xl sm:px-6 lg:px-8 py-8 md:py-12 relative z-10">
    <!-- Breadcrumb -->
    <nav class="flex mb-8 text-sm" aria-label="Breadcrumb">
        <ol class="inline-flex items-center space-x-1 md:space-x-3">
            <li class="inline-flex items-center">
                <a href="{{ route('home') }}" class="inline-flex items-center text-gray-500 hover:text-shopee dark:text-slate-400 dark:hover:text-shopee-light">
                    <i data-lucide="home" class="w-4 h-4 mr-2"></i>
                    Trang chủ
                </a>
            </li>
            <li aria-current="page">
                <div class="flex items-center">
                    <i data-lucide="chevron-right" class="w-4 h-4 text-gray-400"></i>
                    <span class="ml-1 text-gray-700 dark:text-slate-200 font-medium md:ml-2">Blog tin tức</span>
                </div>
            </li>
        </ol>
    </nav>

    <!-- Header Section -->
    <div class="text-center md:text-left mb-12">
        <h1 class="text-3xl md:text-4xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-gray-900 to-gray-700 dark:from-white dark:to-slate-300 tracking-tight">
            Blog Chia Sẻ & Hướng Dẫn
        </h1>
        <p class="mt-3 text-lg text-gray-600 dark:text-slate-400 max-w-2xl leading-relaxed">
            Nơi tổng hợp các mẹo săn sale Shopee, bí quyết hoàn tiền tối đa và kiến thức MMO giúp bạn tạo dựng nguồn thu nhập thụ động bền vững.
        </p>
    </div>

    <!-- 1. FEATURED POSTS (Bài viết nổi bật hàng đầu) -->
    @if($featuredPosts->count() > 0)
    <div class="mb-16">
        <div class="flex items-center gap-2 mb-6">
            <div class="w-2.5 h-6 bg-shopee rounded-full"></div>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Bài viết nổi bật</h2>
        </div>
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Bài viết ghim/nổi bật lớn nhất -->
            <div class="lg:col-span-2 relative group overflow-hidden rounded-3xl bg-white dark:bg-slate-900 border border-gray-100 dark:border-slate-800 shadow-sm transition-all duration-300 hover:shadow-xl flex flex-col justify-between">
                <div class="relative overflow-hidden aspect-[16/9]">
                    <img src="{{ $featuredPosts[0]->thumbnail ?: asset('assets/images/default-thumbnail.jpg') }}" 
                         alt="{{ $featuredPosts[0]->title }}" 
                         width="1600"
                         height="900"
                         loading="eager"
                         fetchpriority="high"
                         decoding="async"
                         class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105">
                    <div class="absolute top-4 left-4 flex gap-2">
                        <span class="px-3 py-1 text-xs font-bold text-white bg-shopee rounded-full shadow-md">
                            {{ $featuredPosts[0]->category->name ?? 'Mẹo vặt' }}
                        </span>
                        @if($featuredPosts[0]->is_sticky)
                        <span class="px-3 py-1 text-xs font-bold text-white bg-amber-500 rounded-full shadow-md flex items-center gap-1">
                            <i data-lucide="pin" class="w-3.5 h-3.5"></i> Ghim
                        </span>
                        @endif
                    </div>
                </div>
                <div class="p-6 md:p-8 flex-grow flex flex-col justify-between">
                    <div>
                        <div class="flex items-center gap-3 text-xs text-gray-500 dark:text-slate-400 mb-3">
                            <span class="flex items-center gap-1"><i data-lucide="user" class="w-3.5 h-3.5"></i> {{ $featuredPosts[0]->author->name ?? 'Admin' }}</span>
                            <span>•</span>
                            <span class="flex items-center gap-1"><i data-lucide="calendar" class="w-3.5 h-3.5"></i> {{ $featuredPosts[0]->published_at ? $featuredPosts[0]->published_at->format('d/m/Y') : $featuredPosts[0]->created_at->format('d/m/Y') }}</span>
                            <span>•</span>
                            <span class="flex items-center gap-1"><i data-lucide="eye" class="w-3.5 h-3.5"></i> {{ number_format($featuredPosts[0]->view_count) }}</span>
                        </div>
                        <h3 class="text-xl md:text-2xl font-bold text-gray-900 dark:text-white group-hover:text-shopee dark:group-hover:text-shopee-light transition-colors duration-200 line-clamp-2">
                            <a href="{{ route('blog.show', $featuredPosts[0]->slug) }}">{{ $featuredPosts[0]->title }}</a>
                        </h3>
                        <p class="mt-3 text-gray-600 dark:text-slate-400 text-sm md:text-base line-clamp-3">
                            {{ $featuredPosts[0]->summary }}
                        </p>
                    </div>
                    <div class="mt-6 pt-6 border-t border-gray-100 dark:border-slate-800 flex items-center justify-between">
                        <a href="{{ route('blog.show', $featuredPosts[0]->slug) }}" class="inline-flex items-center gap-2 text-sm font-bold text-shopee dark:text-shopee-light hover:underline">
                            Đọc bài viết <i data-lucide="arrow-right" class="w-4 h-4"></i>
                        </a>
                        <div class="flex items-center gap-3 text-gray-400">
                            <span class="flex items-center gap-1 text-xs"><i data-lucide="thumbs-up" class="w-4 h-4"></i> {{ $featuredPosts[0]->like_count }}</span>
                            <span class="flex items-center gap-1 text-xs"><i data-lucide="message-square" class="w-4 h-4"></i> {{ $featuredPosts[0]->comment_count }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 2 bài viết nổi bật phụ kế bên -->
            <div class="space-y-8 flex flex-col justify-between">
                @foreach($featuredPosts->skip(1) as $fPost)
                <div class="group flex items-start gap-4 p-4 rounded-2xl bg-white dark:bg-slate-900 border border-gray-100 dark:border-slate-800/80 shadow-sm hover:shadow-md transition-all duration-200">
                    <div class="relative w-28 md:w-36 aspect-[4/3] rounded-xl overflow-hidden shrink-0">
                        <img src="{{ $fPost->thumbnail ?: asset('assets/images/default-thumbnail.jpg') }}" 
                             alt="{{ $fPost->title }}" 
                             width="600"
                             height="450"
                             loading="lazy"
                             decoding="async"
                             class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                    </div>
                    <div class="flex-grow min-w-0">
                        <span class="text-[11px] font-bold text-shopee dark:text-shopee-light block mb-1">
                            {{ $fPost->category->name ?? 'Kinh nghiệm' }}
                        </span>
                        <h4 class="text-sm font-bold text-gray-900 dark:text-white group-hover:text-shopee dark:group-hover:text-shopee-light transition-colors line-clamp-2">
                            <a href="{{ route('blog.show', $fPost->slug) }}">{{ $fPost->title }}</a>
                        </h4>
                        <p class="text-xs text-gray-500 dark:text-slate-400 mt-1 line-clamp-2">
                            {{ $fPost->summary }}
                        </p>
                        <div class="flex items-center gap-2 text-[10px] text-gray-400 mt-2">
                            <span>{{ $fPost->published_at ? $fPost->published_at->format('d/m/Y') : $fPost->created_at->format('d/m/Y') }}</span>
                            <span>•</span>
                            <span class="flex items-center gap-0.5"><i data-lucide="eye" class="w-3 h-3"></i> {{ $fPost->view_count }}</span>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif

    <!-- 2. MAIN LAYOUT: LIST & SIDEBAR -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-12">
        <!-- Cột trái: Danh sách bài viết chính -->
        <div class="lg:col-span-2 space-y-8">
            <div class="flex items-center justify-between pb-4 border-b border-gray-100 dark:border-slate-800">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                    <i data-lucide="rss" class="w-5 h-5 text-shopee"></i> Bài viết mới nhất
                </h2>
            </div>

            @if($posts->count() > 0)
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                @foreach($posts as $post)
                <article class="group bg-white dark:bg-slate-900 border border-gray-100 dark:border-slate-800/80 rounded-2xl shadow-sm hover:shadow-lg transition-all duration-300 flex flex-col justify-between overflow-hidden">
                    <div>
                        <!-- Thumbnail -->
                        <div class="relative overflow-hidden aspect-[16/10]">
                            <img src="{{ $post->thumbnail ?: asset('assets/images/default-thumbnail.jpg') }}" 
                                 alt="{{ $post->title }}" 
                                 width="800"
                                 height="500"
                                 loading="lazy"
                                 decoding="async"
                                 class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105">
                            <span class="absolute top-3 left-3 px-2.5 py-0.5 text-[11px] font-bold text-white bg-slate-950/80 backdrop-blur-md rounded-full shadow-sm">
                                {{ $post->category->name ?? 'Tin tức' }}
                            </span>
                            @if($post->is_sticky)
                            <span class="absolute top-3 right-3 px-2 py-0.5 text-[10px] font-bold text-white bg-amber-500 rounded-lg flex items-center gap-0.5">
                                <i data-lucide="pin" class="w-3 h-3"></i> Ghim
                            </span>
                            @endif
                        </div>
                        <!-- Content -->
                        <div class="p-5">
                            <div class="flex items-center gap-3 text-[11px] text-gray-500 dark:text-slate-400 mb-2">
                                <span class="flex items-center gap-1"><i data-lucide="calendar" class="w-3 h-3"></i> {{ $post->published_at ? $post->published_at->format('d/m/Y') : $post->created_at->format('d/m/Y') }}</span>
                                <span>•</span>
                                <span class="flex items-center gap-1"><i data-lucide="eye" class="w-3 h-3"></i> {{ number_format($post->view_count) }}</span>
                            </div>
                            <h3 class="text-base md:text-lg font-bold text-gray-900 dark:text-white group-hover:text-shopee dark:group-hover:text-shopee-light transition-colors line-clamp-2">
                                <a href="{{ route('blog.show', $post->slug) }}">{{ $post->title }}</a>
                            </h3>
                            <p class="mt-2 text-xs md:text-sm text-gray-600 dark:text-slate-400 line-clamp-3">
                                {{ $post->summary }}
                            </p>
                        </div>
                    </div>
                    <!-- Footer card -->
                    <div class="px-5 pb-5 pt-3 border-t border-gray-50 dark:border-slate-800/50 flex items-center justify-between">
                        <a href="{{ route('blog.show', $post->slug) }}" class="inline-flex items-center gap-1.5 text-xs font-bold text-shopee dark:text-shopee-light hover:underline">
                            Đọc tiếp <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                        </a>
                        <div class="flex items-center gap-2 text-gray-400 text-xs">
                            <span class="flex items-center gap-0.5"><i data-lucide="thumbs-up" class="w-3.5 h-3.5"></i> {{ $post->like_count }}</span>
                            <span class="flex items-center gap-0.5"><i data-lucide="message-square" class="w-3.5 h-3.5"></i> {{ $post->comment_count }}</span>
                        </div>
                    </div>
                </article>
                @endforeach
            </div>

            <!-- Phân trang (AJAX/Normal pagination) -->
            <div class="pt-8">
                {{ $posts->links() }}
            </div>

            @else
            <div class="p-12 text-center rounded-2xl bg-white dark:bg-slate-900 border border-gray-100 dark:border-slate-800 text-gray-500">
                <i data-lucide="newspaper" class="w-12 h-12 text-gray-300 dark:text-slate-700 mx-auto mb-4"></i>
                <p class="text-lg font-semibold">Chưa có bài viết nào được đăng tải.</p>
            </div>
            @endif
        </div>

        <!-- Cột phải: Sidebar (Sticky) -->
        <div class="space-y-8 lg:sticky lg:top-24 h-fit">
            <!-- Widget 1: Search -->
            <div class="p-6 rounded-2xl bg-white dark:bg-slate-900 border border-gray-100 dark:border-slate-800 shadow-sm">
                <h3 class="text-sm font-bold text-gray-900 dark:text-white uppercase tracking-wider mb-4 flex items-center gap-2">
                    <i data-lucide="search" class="w-4 h-4 text-shopee"></i> Tìm kiếm bài viết
                </h3>
                <form action="{{ route('blog.search') }}" method="GET" class="relative">
                    <input type="text" 
                           name="q" 
                           placeholder="Nhập từ khóa tìm kiếm..." 
                           required
                           class="w-full pl-4 pr-10 py-2 text-sm rounded-xl border border-gray-200 dark:border-slate-700 bg-gray-50 dark:bg-slate-850 dark:text-white focus:outline-none focus:ring-2 focus:ring-shopee">
                    <button type="submit" class="absolute right-3 top-2.5 text-gray-400 hover:text-shopee">
                        <i data-lucide="search" class="w-4 h-4"></i>
                    </button>
                </form>
            </div>

            <!-- Widget 2: Categories -->
            <div class="p-6 rounded-2xl bg-white dark:bg-slate-900 border border-gray-100 dark:border-slate-800 shadow-sm">
                <h3 class="text-sm font-bold text-gray-900 dark:text-white uppercase tracking-wider mb-4 flex items-center gap-2">
                    <i data-lucide="folder-open" class="w-4 h-4 text-shopee"></i> Danh mục blog
                </h3>
                <ul class="space-y-2">
                    @foreach($categories as $cat)
                    <li>
                        <a href="{{ route('blog.category', $cat->slug) }}" class="flex items-center justify-between px-3 py-2 text-sm text-gray-600 dark:text-slate-300 hover:bg-gray-50 dark:hover:bg-slate-800/50 rounded-xl hover:text-shopee transition-all duration-200 group">
                            <span class="flex items-center gap-2">
                                <i data-lucide="{{ $cat->icon ?: 'folder' }}" class="w-4 h-4 text-gray-400 group-hover:text-shopee"></i>
                                {{ $cat->name }}
                            </span>
                            <span class="px-2 py-0.5 text-xs font-bold text-gray-400 bg-gray-100 dark:bg-slate-800 rounded-full group-hover:bg-shopee/10 group-hover:text-shopee">
                                {{ $cat->posts_count }}
                            </span>
                        </a>
                    </li>
                    @endforeach
                </ul>
            </div>

            <!-- Widget 3: Popular posts -->
            <div class="p-6 rounded-2xl bg-white dark:bg-slate-900 border border-gray-100 dark:border-slate-800 shadow-sm">
                <h3 class="text-sm font-bold text-gray-900 dark:text-white uppercase tracking-wider mb-4 flex items-center gap-2">
                    <i data-lucide="trending-up" class="w-4 h-4 text-shopee"></i> Xem nhiều nhất
                </h3>
                <div class="space-y-4">
                    @foreach($popularPosts as $popPost)
                    <div class="flex gap-3 group">
                        <div class="w-16 h-12 rounded-lg overflow-hidden shrink-0">
                            <img src="{{ $popPost->thumbnail ?: asset('assets/images/default-thumbnail.jpg') }}" 
                                 alt="{{ $popPost->title }}" 
                                 width="160"
                                 height="120"
                                 loading="lazy"
                                 decoding="async"
                                 class="w-full h-full object-cover">
                        </div>
                        <div class="min-w-0">
                            <h4 class="text-xs font-bold text-gray-800 dark:text-slate-200 group-hover:text-shopee transition-colors line-clamp-2">
                                <a href="{{ route('blog.show', $popPost->slug) }}">{{ $popPost->title }}</a>
                            </h4>
                            <span class="text-[10px] text-gray-450 dark:text-slate-400 block mt-1">
                                <i data-lucide="eye" class="w-3 h-3 inline mr-0.5"></i> {{ number_format($popPost->view_count) }} lượt xem
                            </span>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>

            <!-- Widget 4: Tags Cloud -->
            <div class="p-6 rounded-2xl bg-white dark:bg-slate-900 border border-gray-100 dark:border-slate-800 shadow-sm">
                <h3 class="text-sm font-bold text-gray-900 dark:text-white uppercase tracking-wider mb-4 flex items-center gap-2">
                    <i data-lucide="tag" class="w-4 h-4 text-shopee"></i> Thẻ phổ biến
                </h3>
                <div class="flex flex-wrap gap-2">
                    @foreach($tags as $t)
                    <a href="{{ route('blog.tag', $t->slug) }}" class="px-2.5 py-1 text-xs text-gray-600 dark:text-slate-300 bg-gray-50 dark:bg-slate-800 hover:bg-shopee/10 hover:text-shopee dark:hover:bg-shopee/20 border border-gray-150 dark:border-slate-700/50 rounded-lg transition-all">
                        #{{ $t->name }}
                    </a>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
