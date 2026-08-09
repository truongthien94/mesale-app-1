@extends('layouts.admin')

@section('title', 'Quản lý Bài Viết Blog - Trang Quản Trị')

@section('content')
<div class="space-y-6">
    <!-- Breadcrumb & Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Danh Sách Bài Viết</h1>
            <p class="text-sm text-gray-500 dark:text-slate-400">Xem và quản lý tất cả bài viết tin tức, hướng dẫn mua sắm.</p>
        </div>
        <div>
            <a href="{{ route('admin.blog.posts.create') }}" class="inline-flex items-center justify-center px-4 py-2 text-sm font-semibold text-white bg-shopee hover:bg-shopee-dark rounded-xl shadow-md transition-all gap-2">
                <i data-lucide="plus-circle" class="w-4.5 h-4.5"></i>
                Viết bài mới
            </a>
        </div>
    </div>

    <!-- Filters & Search -->
    <div class="p-4 bg-white dark:bg-slate-900 border border-gray-150 dark:border-slate-800 rounded-2xl shadow-sm">
        <form action="{{ route('admin.blog.posts.index') }}" method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <!-- Search field -->
            <div class="relative md:col-span-2">
                <input type="text" 
                       name="search" 
                       value="{{ request('search') }}" 
                       placeholder="Tìm kiếm theo tiêu đề..." 
                       class="w-full pl-10 pr-4 py-2 text-sm rounded-xl border border-gray-200 dark:border-slate-800 bg-gray-50 dark:bg-slate-950 dark:text-white focus:outline-none focus:ring-2 focus:ring-shopee">
                <i data-lucide="search" class="absolute left-3.5 top-3 w-4 h-4 text-gray-400"></i>
            </div>
            <!-- Category dropdown -->
            <div>
                <select name="category_id" class="w-full px-3 py-2 text-sm rounded-xl border border-gray-200 dark:border-slate-800 bg-gray-50 dark:bg-slate-950 dark:text-white focus:outline-none focus:ring-2 focus:ring-shopee">
                    <option value="">-- Tất cả danh mục --</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" {{ request('category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                    @endforeach
                </select>
            </div>
            <!-- Submit Button -->
            <div>
                <button type="submit" class="w-full flex items-center justify-center gap-2 px-4 py-2 text-sm font-semibold text-white bg-slate-800 dark:bg-slate-700 hover:bg-slate-900 dark:hover:bg-slate-600 rounded-xl transition-all">
                    <i data-lucide="filter" class="w-4 h-4"></i> Lọc kết quả
                </button>
            </div>
        </form>
    </div>

    <!-- Data Table -->
    <div class="bg-white dark:bg-slate-900 border border-gray-150 dark:border-slate-800 rounded-2xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse whitespace-nowrap">
                <thead>
                    <tr class="bg-gray-55 dark:bg-slate-950 border-b border-gray-100 dark:border-slate-850 text-xs font-bold text-gray-600 dark:text-slate-300 uppercase tracking-wider">
                        <th class="px-6 py-4 min-w-[280px]">Bài viết</th>
                        <th class="px-6 py-4">Danh mục</th>
                        <th class="px-6 py-4 text-center">Ghim trang chủ</th>
                        <th class="px-6 py-4 text-center">Nổi bật</th>
                        <th class="px-6 py-4">Trạng thái</th>
                        <th class="px-6 py-4">Thống kê</th>
                        <th class="px-6 py-4 text-right">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-slate-850 text-sm">
                    @forelse($posts as $post)
                    <tr class="hover:bg-gray-50/50 dark:hover:bg-slate-800/20">
                        <td class="px-6 py-4 min-w-[280px] whitespace-normal">
                            <div class="flex items-center gap-3">
                                <div class="w-12 h-10 rounded-lg overflow-hidden shrink-0 border border-gray-100 dark:border-slate-800">
                                    <img src="{{ $post->thumbnail ?: asset('assets/images/default-thumbnail.jpg') }}" alt="{{ $post->title }}" class="w-full h-full object-cover">
                                </div>
                                <div class="min-w-0">
                                    <a href="{{ route('blog.show', $post->slug) }}" target="_blank" class="font-semibold text-gray-900 dark:text-white hover:text-shopee block truncate max-w-xs md:max-w-md">
                                        {{ $post->title }}
                                    </a>
                                    <span class="text-xs text-gray-400 block mt-0.5">Viết bởi: {{ $post->author->name ?? 'Admin' }} | {{ $post->created_at->format('H:i d/m/Y') }}</span>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-gray-700 dark:text-slate-350">
                            {{ $post->category->name ?? 'Không xác định' }}
                        </td>
                        <td class="px-6 py-4 text-center">
                            <!-- Toggle Sticky Checkbox -->
                            <button @click="
                                axios.post('/' + window.adminPrefix + '/blog/posts/{{ $post->id }}/toggle-sticky')
                                    .then(res => {
                                        if (res.data.success) {
                                            $el.setAttribute('data-sticky', res.data.is_sticky);
                                            window.dispatchEvent(new CustomEvent('toast', { detail: { text: res.data.message, type: 'success' } }));
                                        }
                                    });
                            "
                            data-sticky="{{ $post->is_sticky ? 'true' : 'false' }}"
                            class="inline-flex items-center justify-center p-1 rounded-lg border transition-all cursor-pointer"
                            :class="$el.getAttribute('data-sticky') === 'true' ? 'bg-amber-50 border-amber-200 text-amber-500' : 'bg-gray-50 border-gray-200 text-gray-450 hover:bg-amber-50 hover:text-amber-500'">
                                <i data-lucide="pin" class="w-4 h-4"></i>
                            </button>
                        </td>
                        <td class="px-6 py-4 text-center">
                            @if($post->is_featured)
                                <span class="px-2 py-0.5 text-xs font-bold text-blue-700 bg-blue-50 dark:bg-blue-950/20 border border-blue-200 rounded-md">Nổi bật</span>
                            @else
                                <span class="text-xs text-gray-400">-</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            @if($post->status === 'published')
                                <span class="px-2.5 py-0.5 text-xs font-bold text-green-700 bg-green-50 dark:bg-green-950/20 border border-green-200 rounded-full">Đã đăng</span>
                            @elseif($post->status === 'draft')
                                <span class="px-2.5 py-0.5 text-xs font-bold text-gray-600 bg-gray-50 dark:bg-slate-800 border border-gray-200 dark:border-slate-700 rounded-full">Dự thảo</span>
                            @else
                                <span class="px-2.5 py-0.5 text-xs font-bold text-amber-700 bg-amber-50 dark:bg-amber-950/20 border border-amber-200 rounded-full">Lên lịch</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex flex-col text-xs text-gray-550 space-y-0.5">
                                <span class="flex items-center gap-1"><i data-lucide="eye" class="w-3.5 h-3.5 text-gray-400"></i> {{ number_format($post->view_count) }} xem</span>
                                <span class="flex items-center gap-1"><i data-lucide="thumbs-up" class="w-3.5 h-3.5 text-gray-400"></i> {{ $post->like_count }} thích</span>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('admin.blog.posts.edit', $post->id) }}" class="p-1.5 bg-gray-50 hover:bg-shopee/10 text-gray-600 hover:text-shopee rounded-lg border border-gray-150 transition-all" title="Sửa bài viết">
                                    <i data-lucide="edit-3" class="w-4 h-4 pointer-events-none"></i>
                                </a>
                                <form action="{{ route('admin.blog.posts.destroy', $post->id) }}" method="POST" onsubmit="return confirm('Bạn có chắc chắn muốn xóa bài viết này?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="p-1.5 bg-gray-50 hover:bg-red-50 text-gray-600 hover:text-red-500 rounded-lg border border-gray-150 transition-all" title="Xóa bài viết">
                                        <i data-lucide="trash-2" class="w-4 h-4 pointer-events-none"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-8 text-center text-gray-500">
                            <i data-lucide="newspaper" class="w-12 h-12 text-gray-300 mx-auto mb-3"></i>
                            Không tìm thấy bài viết nào.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($posts->hasPages())
        <div class="px-6 py-4 border-t border-gray-100 dark:border-slate-850">
            {{ $posts->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
