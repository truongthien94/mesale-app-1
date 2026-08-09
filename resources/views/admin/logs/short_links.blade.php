@extends('layouts.admin')

@section('title', __('Nhật Ký Liên Kết Rút Gọn') . ' - ' . $siteName)

@php
    $hasFilter = request('code') || request('user_search') || request('destination_url') || (request('limit') && request('limit') != 15);
@endphp

@section('content')
<div class="space-y-6" x-data="{ 
    showFilter: @json($hasFilter),
    showClearModal: false
}">
    <!-- Tiêu đề & Hành động -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ __('Nhật Ký Short Link') }}</h1>
            <p class="text-sm text-gray-500">{{ __('Theo dõi chi tiết các liên kết rút gọn được thành viên tạo ra và tổng số lượt click truy cập thực tế') }}</p>
        </div>
        <div class="flex items-center gap-2">
            <!-- Nút bật/tắt bộ lọc -->
            <button @click="showFilter = !showFilter" 
                    class="inline-flex items-center gap-1.5 px-4 py-2.5 text-xs font-semibold rounded-xl border border-gray-200 transition-all shadow-sm"
                    :class="showFilter ? 'bg-shopee text-white border-shopee hover:bg-shopee-dark' : 'bg-white hover:bg-gray-50 text-gray-700'">
                <i data-lucide="filter" class="w-4 h-4"></i>
                <span>{{ __('Bộ lọc') }}</span>
                @if($hasFilter)
                    <span class="w-1.5 h-1.5 rounded-full bg-red-500 animate-pulse"></span>
                @endif
            </button>

            <!-- Nút dọn dẹp Short Link -->
            <button type="button" 
                    @click="showClearModal = true"
                    class="inline-flex items-center gap-1.5 px-4 py-2.5 text-xs font-semibold text-white bg-red-600 hover:bg-red-700 rounded-xl transition-all shadow-md cursor-pointer whitespace-nowrap">
                <i data-lucide="trash-2" class="w-4 h-4"></i>
                <span>{{ __('Dọn dẹp') }}</span>
            </button>
        </div>
    </div>

    <!-- Bộ lọc tìm kiếm -->
    <div x-show="showFilter" 
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 transform -translate-y-2"
         x-transition:enter-end="opacity-100 transform translate-y-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 transform translate-y-0"
         x-transition:leave-end="opacity-0 transform -translate-y-2"
         class="bg-white p-4 rounded-3xl shadow-sm border border-gray-200"
         style="display: {{ $hasFilter ? 'block' : 'none' }}">
        <form action="{{ route('admin.logs.short_links') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
            <div>
                <select name="limit" class="block w-full px-3 py-2 border border-gray-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-gray-50/50">
                    <option value="15" {{ request('limit') == 15 ? 'selected' : '' }}>{{ __('Hiển thị 15 dòng') }}</option>
                    <option value="30" {{ request('limit') == 30 ? 'selected' : '' }}>{{ __('Hiển thị 30 dòng') }}</option>
                    <option value="50" {{ request('limit') == 50 ? 'selected' : '' }}>{{ __('Hiển thị 50 dòng') }}</option>
                    <option value="100" {{ request('limit') == 100 ? 'selected' : '' }}>{{ __('Hiển thị 100 dòng') }}</option>
                    <option value="200" {{ request('limit') == 200 ? 'selected' : '' }}>{{ __('Hiển thị 200 dòng') }}</option>
                    <option value="500" {{ request('limit') == 500 ? 'selected' : '' }}>{{ __('Hiển thị 500 dòng') }}</option>
                </select>
            </div>

            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                    <i data-lucide="hash" class="w-4 h-4"></i>
                </div>
                <input type="text" 
                       name="code" 
                       value="{{ request('code') }}"
                       placeholder="{{ __('Mã rút gọn...') }}" 
                       class="block w-full pl-9 pr-4 py-2 border border-gray-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee transition-all bg-gray-50/50">
            </div>

            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                    <i data-lucide="user" class="w-4 h-4"></i>
                </div>
                <input type="text" 
                       name="user_search" 
                       value="{{ request('user_search') }}"
                       placeholder="{{ __('Thành viên (Tên, email)...') }}" 
                       class="block w-full pl-9 pr-4 py-2 border border-gray-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee transition-all bg-gray-50/50">
            </div>

            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                    <i data-lucide="link" class="w-4 h-4"></i>
                </div>
                <input type="text" 
                       name="destination_url" 
                       value="{{ request('destination_url') }}"
                       placeholder="{{ __('URL Đích (Link gốc)...') }}" 
                       class="block w-full pl-9 pr-4 py-2 border border-gray-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee transition-all bg-gray-50/50">
            </div>

            <div class="flex gap-2">
                <button type="submit" class="flex-grow px-5 py-2 text-xs font-semibold text-white bg-shopee hover:bg-shopee-dark rounded-xl transition-all shadow-md shrink-0">
                    {{ __('Lọc') }}
                </button>
                @if($hasFilter)
                    <a href="{{ route('admin.logs.short_links') }}" class="flex-grow inline-flex items-center justify-center px-4 py-2 text-xs font-semibold text-gray-500 hover:text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-xl transition-all shadow-sm shrink-0 gap-1">
                        <i data-lucide="x" class="w-3.5 h-3.5"></i>
                        <span>{{ __('Bỏ lọc') }}</span>
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Bảng hiển thị danh sách nhật ký Short Links -->
    <div class="bg-white rounded-3xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse whitespace-nowrap">
                <thead>
                    <tr class="border-b border-gray-200 text-xs font-bold text-gray-400 bg-gray-50/50">
                        <th class="p-4">ID</th>
                        <th class="p-4">{{ __('Thành viên tạo') }}</th>
                        <th class="p-4">{{ __('Mã rút gọn') }}</th>
                        <th class="p-4">{{ __('Đường dẫn rút gọn') }}</th>
                        <th class="p-4">{{ __('URL Đích (Link gốc)') }}</th>
                        <th class="p-4 text-center">{{ __('Số click') }}</th>
                        <th class="p-4 text-center">{{ __('Thời gian tạo') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-xs">
                    @forelse($logs as $log)
                        <tr class="hover:bg-gray-50/50 transition-colors">
                            <td class="p-4 font-bold text-gray-400">{{ $log->id }}</td>
                            <td class="p-4">
                                @if($log->user)
                                    {{-- Hiển thị thông tin thành viên tạo kèm nút đi tới trang chỉnh sửa thông tin thành viên --}}
                                    <div class="flex items-center gap-2">
                                        <div>
                                            <p class="font-bold text-gray-950">{{ $log->user->name }}</p>
                                            <p class="text-[10px] text-gray-400 font-mono">{{ $log->user->email }}</p>
                                        </div>
                                        <a href="{{ route('admin.users.edit', $log->user->id) }}" class="p-1 text-blue-600 hover:bg-blue-50 rounded-lg inline-flex items-center justify-center transition-colors shrink-0" title="{{ __('Chỉnh sửa thành viên') }}">
                                            <i data-lucide="edit" class="w-3.5 h-3.5"></i>
                                        </a>
                                    </div>
                                @else
                                    <p class="text-gray-400 italic">{{ __('Khách vãng lai') }}</p>
                                @endif
                            </td>
                            <td class="p-4 font-mono text-gray-650 font-semibold select-all">
                                {{ $log->code }}
                            </td>
                            <td class="p-4 font-mono text-slate-600">
                                @php
                                    $shortUrl = url($log->code);
                                @endphp
                                <div class="flex items-center gap-2">
                                    <span class="select-all">{{ $shortUrl }}</span>
                                    <a href="{{ $shortUrl }}" target="_blank" rel="noopener noreferrer" class="p-1 text-gray-400 hover:text-shopee hover:bg-gray-100 rounded transition-colors" title="{{ __('Mở liên kết') }}">
                                        <i data-lucide="external-link" class="w-3.5 h-3.5"></i>
                                    </a>
                                </div>
                            </td>
                            <td class="p-4 max-w-[320px] truncate" title="{{ $log->destination_url }}">
                                <a href="{{ $log->destination_url }}" target="_blank" rel="noopener noreferrer" class="text-blue-600 hover:underline">
                                    {{ Str::limit($log->destination_url, 60) }}
                                </a>
                            </td>
                            <td class="p-4 text-center font-bold text-slate-800">
                                <span class="px-2 py-1 bg-slate-100 dark:bg-slate-800 rounded-lg">{{ number_format($log->clicks) }}</span>
                            </td>
                            <td class="p-4 text-center text-gray-400 text-[10px]" title="{{ $log->created_at }}">
                                {{ $log->created_at->format('d/m/Y H:i:s') }}
                                <span class="block text-[9px] text-gray-300 font-medium">{{ $log->created_at->diffForHumans() }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="p-8 text-center text-gray-400">{{ __('Không tìm thấy liên kết rút gọn nào.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Phân trang dữ liệu -->
        @if($logs->hasPages())
            <div class="p-4 border-t border-gray-100 bg-gray-50/50">
                {{ $logs->links() }}
            </div>
        @endif
    </div>

    <!-- Modal xác nhận dọn dẹp nhật ký Short Link -->
    <div x-show="showClearModal" 
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto"
         style="display: none;">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" @click="showClearModal = false"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-3xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full p-6 border border-gray-100 relative z-10">
                <div class="flex items-center gap-4 text-red-600 mb-4">
                    <div class="w-12 h-12 rounded-2xl bg-red-50 flex items-center justify-center shrink-0">
                        <i data-lucide="triangle-alert" class="w-6 h-6"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-gray-900">{{ __('Xác Nhận Dọn Dẹp Nhật Ký Short Link') }}</h3>
                        <p class="text-xs text-gray-500">{{ __('Hành động này không thể hoàn tác!') }}</p>
                    </div>
                </div>
                <p class="text-sm text-gray-600 mb-6 leading-relaxed">
                    {{ __('Bạn có chắc chắn muốn xóa toàn bộ nhật ký liên kết rút gọn Short Link hiện có trong hệ thống?') }}
                </p>
                <div class="flex items-center justify-end gap-3">
                    <button type="button" @click="showClearModal = false" class="px-4 py-2.5 text-xs font-semibold text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-xl transition-all">
                        {{ __('Hủy bỏ') }}
                    </button>
                    <form action="{{ route('admin.logs.short_links.clear') }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="px-4 py-2.5 text-xs font-semibold text-white bg-red-600 hover:bg-red-700 rounded-xl transition-all shadow-md">
                            {{ __('Xác nhận dọn dẹp') }}
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
