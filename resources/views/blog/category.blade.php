@extends('layouts.app')

{{-- SEO: Title tag trang danh mục blog --}}
@section('title', 'Chuyên mục ' . $category->name . ' | Mê Sale')

{{-- SEO: Meta description dựa trên mô tả danh mục hoặc tự sinh --}}
@php
    $categoryMetaDescription = trim((string) $category->description);
    if (mb_strlen($categoryMetaDescription) < 100) {
        $categoryMetaDescription = trim($categoryMetaDescription . ' Khám phá mẹo săn sale, mã giảm giá và cách nhận hoàn tiền Shopee, TikTok Shop cùng Mê Sale.');
    }
@endphp
@section('meta_description', Str::limit($categoryMetaDescription, 157, '...'))

{{-- SEO: Canonical URL trang danh mục --}}
@section('canonical_url', route('blog.category', $category->slug))

{{-- SEO: JSON-LD BreadcrumbList cho trang danh mục --}}
@section('seo_schema')
<script type="application/ld+json">
{!! json_encode([
    '@' . 'context' => 'https://schema.org',
    '@type' => 'BreadcrumbList',
    'itemListElement' => [
        ['@type' => 'ListItem', 'position' => 1, 'name' => 'Trang chủ', 'item' => url('/')],
        ['@type' => 'ListItem', 'position' => 2, 'name' => 'Blog tin tức', 'item' => route('blog.index')],
        ['@type' => 'ListItem', 'position' => 3, 'name' => $category->name, 'item' => route('blog.category', $category->slug)],
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
                <a href="{{ route('home') }}" class="inline-flex items-center text-gray-500 hover:text-shopee dark:text-slate-400">
                    <i data-lucide="home" class="w-4 h-4 mr-2"></i>
                    Trang chủ
                </a>
            </li>
            <li>
                <div class="flex items-center">
                    <i data-lucide="chevron-right" class="w-4 h-4 text-gray-400"></i>
                    <a href="{{ route('blog.index') }}" class="ml-1 text-gray-500 hover:text-shopee dark:text-slate-400 md:ml-2">Blog tin tức</a>
                </div>
            </li>
            <li aria-current="page">
                <div class="flex items-center">
                    <i data-lucide="chevron-right" class="w-4 h-4 text-gray-400"></i>
                    <span class="ml-1 text-gray-700 dark:text-slate-200 font-medium md:ml-2">{{ $category->name }}</span>
                </div>
            </li>
        </ol>
    </nav>

    <!-- Header Section -->
    <div class="mb-12 text-center md:text-left bg-white dark:bg-slate-900 border border-gray-100 dark:border-slate-800 rounded-3xl p-6 md:p-8 shadow-sm flex items-center gap-4">
        <div class="w-12 h-12 rounded-2xl bg-shopee/10 text-shopee flex items-center justify-center shrink-0">
            <i data-lucide="{{ $category->icon ?: 'folder' }}" class="w-6 h-6"></i>
        </div>
        <div>
            <h1 class="text-2xl md:text-3xl font-extrabold text-gray-900 dark:text-white">
                Chuyên mục: {{ $category->name }}
            </h1>
            @if($category->description)
            <p class="mt-2 text-sm md:text-base text-gray-600 dark:text-slate-400 max-w-2xl leading-relaxed">
                {{ $category->description }}
            </p>
            @endif
        </div>
    </div>

    <!-- Main Layout: List & Sidebar -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-12">
        <!-- Cột trái: Danh sách bài viết -->
        <div class="lg:col-span-2 space-y-8">
            <h2 class="sr-only">Bài viết trong chuyên mục {{ $category->name }}</h2>
            @if($posts->count() > 0)
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                @foreach($posts as $post)
                <article class="group bg-white dark:bg-slate-900 border border-gray-100 dark:border-slate-800/80 rounded-2xl shadow-sm hover:shadow-lg transition-all duration-300 flex flex-col justify-between overflow-hidden">
                    <div>
                        <div class="relative overflow-hidden aspect-[16/10]">
                            <img src="{{ $post->thumbnail ?: asset('assets/images/default-thumbnail.jpg') }}" 
                                 alt="{{ $post->title }}" 
                                 width="800"
                                 height="500"
                                 loading="lazy"
                                 decoding="async"
                                 class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105">
                        </div>
                        <div class="p-5">
                            <div class="flex items-center gap-3 text-[11px] text-gray-500 dark:text-slate-400 mb-2">
                                <span class="flex items-center gap-1"><i data-lucide="calendar" class="w-3 h-3"></i> {{ $post->published_at ? $post->published_at->format('d/m/Y') : $post->created_at->format('d/m/Y') }}</span>
                                <span>•</span>
                                <span class="flex items-center gap-1"><i data-lucide="eye" class="w-3 h-3"></i> {{ number_format($post->view_count) }} lượt xem</span>
                            </div>
                            <h3 class="text-base md:text-lg font-bold text-gray-900 dark:text-white group-hover:text-shopee dark:group-hover:text-shopee-light transition-colors line-clamp-2">
                                <a href="{{ route('blog.show', $post->slug) }}">{{ $post->title }}</a>
                            </h3>
                            <p class="mt-2 text-xs md:text-sm text-gray-600 dark:text-slate-400 line-clamp-3">
                                {{ $post->summary }}
                            </p>
                        </div>
                    </div>
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

            <div class="pt-8">
                {{ $posts->links() }}
            </div>
            @else
            <div class="p-12 text-center rounded-2xl bg-white dark:bg-slate-900 border border-gray-100 dark:border-slate-800 text-gray-500">
                <i data-lucide="newspaper" class="w-12 h-12 text-gray-300 dark:text-slate-700 mx-auto mb-4"></i>
                <p class="text-lg font-semibold">Chưa có bài viết nào thuộc chuyên mục này.</p>
            </div>
            @endif
        </div>

        <!-- Cột phải: Sidebar -->
        <div class="lg:col-span-1 space-y-8">
            <div class="p-6 rounded-2xl bg-white dark:bg-slate-900 border border-gray-100 dark:border-slate-800 shadow-sm">
                <h3 class="text-sm font-bold text-gray-900 dark:text-white uppercase tracking-wider mb-4 flex items-center gap-2">
                    <i data-lucide="folder-open" class="w-4 h-4 text-shopee"></i> Chuyên mục khác
                </h3>
                <ul class="space-y-2">
                    @foreach($categories as $cat)
                    <li>
                        <a href="{{ route('blog.category', $cat->slug) }}" class="flex items-center justify-between px-3 py-2 text-sm text-gray-600 dark:text-slate-300 hover:bg-gray-50 dark:hover:bg-slate-800/50 rounded-xl hover:text-shopee transition-all duration-200 group {{ $cat->id === $category->id ? 'text-shopee bg-shopee/5 font-bold' : '' }}">
                            <span class="flex items-center gap-2">
                                <i data-lucide="{{ $cat->icon ?: 'folder' }}" class="w-4 h-4 {{ $cat->id === $category->id ? 'text-shopee' : 'text-gray-400 group-hover:text-shopee' }}"></i>
                                {{ $cat->name }}
                            </span>
                        </a>
                    </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection
