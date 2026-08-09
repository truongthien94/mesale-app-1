@extends('layouts.admin')

@section('title', __('Quản Lý Trang Nội Dung') . ' - ' . $siteName)

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h2 class="text-xl font-bold text-gray-900 dark:text-white">{{ __('Quản lý trang nội dung') }}</h2>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ __('Tạo và quản lý các trang nội dung tĩnh như Điều khoản dịch vụ, Chính sách bảo mật, v.v.') }}</p>
        </div>
        <a href="{{ route('admin.pages.create') }}" 
           class="inline-flex items-center gap-1.5 px-4 py-2 bg-shopee hover:bg-shopee-dark text-white font-bold rounded-2xl text-xs transition-all shadow-lg shadow-shopee/15 shrink-0 self-start md:self-auto">
            <i data-lucide="plus" class="w-4 h-4"></i>
            <span>{{ __('Thêm trang mới') }}</span>
        </a>
    </div>

    <!-- Danh sách trang nội dung -->
    <div class="bg-white dark:bg-slate-900 rounded-3xl shadow-sm border border-gray-100 dark:border-slate-800/50 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs whitespace-nowrap">
                <thead>
                    <tr class="bg-gray-50/70 border-b border-gray-100 dark:bg-slate-800/40 dark:border-slate-800 text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        <th class="px-6 py-4"># ID</th>
                        <th class="px-6 py-4">{{ __('Tiêu đề trang') }}</th>
                        <th class="px-6 py-4">{{ __('Đường dẫn (Slug)') }}</th>
                        <th class="px-6 py-4 text-center">{{ __('Trạng thái') }}</th>
                        <th class="px-6 py-4 text-center">{{ __('Thứ tự') }}</th>
                        <th class="px-6 py-4">{{ __('Ngày tạo') }}</th>
                        <th class="px-6 py-4 text-right">{{ __('Hành động') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-slate-800/50">
                    @forelse($pages as $page)
                        <tr class="hover:bg-gray-50/50 dark:hover:bg-slate-800/20 transition-all">
                            <td class="px-6 py-4 font-bold text-gray-400">#{{ $page->id }}</td>
                            <td class="px-6 py-4">
                                <div class="flex flex-col">
                                    <span class="font-bold text-gray-900 dark:text-white">{{ $page->title }}</span>
                                    @if($page->is_noindex)
                                        <span class="inline-flex items-center gap-0.5 text-[10px] text-amber-600 dark:text-amber-400 font-semibold mt-0.5" title="{{ __('Chặn công cụ tìm kiếm và loại khỏi sitemap') }}">
                                            <i data-lucide="eye-off" class="w-3.5 h-3.5"></i>
                                            {{ __('Chặn index/sitemap') }}
                                        </span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <a href="{{ url('/page/' . $page->slug) }}" 
                                   target="_blank" 
                                   class="text-shopee hover:underline font-semibold">
                                    /page/{{ $page->slug }}
                                </a>
                            </td>
                            <td class="px-6 py-4 text-center">
                                @if($page->status === 'published')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold bg-green-50 text-green-700 border border-green-100/50 dark:bg-green-950/20 dark:text-green-400 dark:border-green-900/30">
                                        <span class="w-1.5 h-1.5 bg-green-500 rounded-full"></span>
                                        {{ __('Đã xuất bản') }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold bg-gray-50 text-gray-500 border border-gray-100/50 dark:bg-slate-800 dark:text-gray-400 dark:border-slate-700/30">
                                        <span class="w-1.5 h-1.5 bg-gray-400 rounded-full"></span>
                                        {{ __('Bản nháp') }}
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span class="inline-flex items-center justify-center font-bold h-6 min-w-6 px-1.5 rounded-full bg-gray-100 text-gray-700 text-[10px] dark:bg-slate-800 dark:text-gray-300">
                                    {{ $page->sort_order }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-gray-500 dark:text-gray-400">
                                {{ $page->created_at->format('d/m/Y H:i') }}
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <!-- Xem trang trên frontend -->
                                    <a href="{{ url('/page/' . $page->slug) }}" 
                                       target="_blank"
                                       class="p-1.5 text-gray-400 hover:text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-950/30 rounded-lg transition-all"
                                       title="Xem trang">
                                        <i data-lucide="external-link" class="w-4 h-4 pointer-events-none"></i>
                                    </a>

                                    <!-- Chỉnh sửa -->
                                    <a href="{{ route('admin.pages.edit', $page->id) }}" 
                                       class="p-1.5 text-gray-400 hover:text-shopee hover:bg-shopee/5 rounded-lg transition-all"
                                       title="Chỉnh sửa">
                                        <i data-lucide="edit-3" class="w-4 h-4 pointer-events-none"></i>
                                    </a>
                                    
                                    <!-- Xóa -->
                                    <form action="{{ route('admin.pages.destroy', $page->id) }}" method="POST" 
                                          onsubmit="return confirm('Bạn có chắc chắn muốn xóa trang này?');"
                                          class="inline-block">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" 
                                                class="p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-950/30 rounded-lg transition-all"
                                                title="Xóa trang">
                                            <i data-lucide="trash-2" class="w-4 h-4 pointer-events-none"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-10 text-center text-gray-400 dark:text-gray-500 italic">
                                <div class="flex flex-col items-center justify-center space-y-2">
                                    <i data-lucide="file-x" class="w-8 h-8 text-gray-300"></i>
                                    <span>{{ __('Chưa có trang nội dung nào được tạo.') }}</span>
                                    <a href="{{ route('admin.pages.create') }}" class="text-shopee font-bold hover:underline text-xs">{{ __('Tạo trang đầu tiên →') }}</a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
