@extends('layouts.admin')

@section('title', __('Nhật Ký Thông Báo') . ' - ' . $siteName)

@php
    // Kiểm tra xem trang hiện tại có đang lọc dữ liệu hay không để tự động mở khung bộ lọc
    $hasFilter = request('user_search') || request('search') || request('is_read') !== null || request('type') || (request('limit') && request('limit') != 15);
@endphp

@section('content')
<div class="space-y-6" x-data="{ 
    showFilter: @json($hasFilter),
    showClearModal: false
}">
    <!-- Tiêu đề và Mô tả chức năng -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-slate-100">{{ __('Nhật Ký Thông Báo') }}</h1>
            <p class="text-sm text-gray-500 dark:text-slate-400">
                {{ __('Quản lý và theo dõi lịch sử thông báo đã gửi cho người dùng, bao gồm trạng thái đã đọc hay chưa') }}
            </p>
        </div>
        <div class="flex items-center gap-2">
            <!-- Nút bật/tắt bộ lọc tìm kiếm -->
            <button @click="showFilter = !showFilter" 
                    class="inline-flex items-center gap-1.5 px-4 py-2.5 text-xs font-semibold rounded-xl border border-gray-200 dark:border-slate-800 transition-all shadow-sm"
                    :class="showFilter ? 'bg-shopee text-white border-shopee hover:bg-shopee-dark' : 'bg-white dark:bg-slate-900 dark:text-slate-300 dark:hover:bg-slate-800 hover:bg-gray-50'">
                <i data-lucide="filter" class="w-4 h-4"></i>
                <span>{{ __('Bộ lọc') }}</span>
                @if($hasFilter)
                    <span class="w-1.5 h-1.5 rounded-full bg-red-500 animate-pulse"></span>
                @endif
            </button>

            <!-- Nút dọn dẹp tất cả nhật ký thông báo -->
            <button type="button" 
                    @click="showClearModal = true"
                    class="inline-flex items-center gap-1.5 px-4 py-2.5 text-xs font-semibold text-white bg-red-600 hover:bg-red-700 rounded-xl transition-all shadow-md cursor-pointer whitespace-nowrap">
                <i data-lucide="trash-2" class="w-4 h-4"></i>
                <span>{{ __('Dọn dẹp tất cả') }}</span>
            </button>
        </div>
    </div>

    <!-- Bộ lọc tìm kiếm thông minh -->
    <div x-show="showFilter" 
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 transform -translate-y-2"
         x-transition:enter-end="opacity-100 transform translate-y-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 transform translate-y-0"
         x-transition:leave-end="opacity-0 transform -translate-y-2"
         class="bg-white dark:bg-slate-900 p-4 rounded-3xl shadow-sm border border-gray-200 dark:border-slate-800"
         style="display: {{ $hasFilter ? 'block' : 'none' }}">
        <form action="{{ route('admin.logs.notifications') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3">
            <!-- Số dòng hiển thị -->
            <div>
                <select name="limit" class="block w-full px-3 py-2 border border-gray-200 dark:border-slate-800 dark:bg-slate-950 dark:text-slate-300 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-gray-50/50">
                    <option value="15" {{ request('limit') == 15 ? 'selected' : '' }}>{{ __('Hiển thị 15 dòng') }}</option>
                    <option value="30" {{ request('limit') == 30 ? 'selected' : '' }}>{{ __('Hiển thị 30 dòng') }}</option>
                    <option value="50" {{ request('limit') == 50 ? 'selected' : '' }}>{{ __('Hiển thị 50 dòng') }}</option>
                    <option value="100" {{ request('limit') == 100 ? 'selected' : '' }}>{{ __('Hiển thị 100 dòng') }}</option>
                </select>
            </div>

            <!-- Tìm kiếm người nhận -->
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                    <i data-lucide="user" class="w-4 h-4"></i>
                </div>
                <input type="text" 
                       name="user_search" 
                       value="{{ request('user_search') }}"
                       placeholder="{{ __('Người nhận (Tên, email)...') }}" 
                       class="block w-full pl-9 pr-4 py-2 border border-gray-200 dark:border-slate-800 dark:bg-slate-950 dark:text-slate-300 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee transition-all bg-gray-50/50">
            </div>

            <!-- Tìm kiếm tiêu đề/nội dung -->
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                    <i data-lucide="search" class="w-4 h-4"></i>
                </div>
                <input type="text" 
                       name="search" 
                       value="{{ request('search') }}"
                       placeholder="{{ __('Tiêu đề, nội dung...') }}" 
                       class="block w-full pl-9 pr-4 py-2 border border-gray-200 dark:border-slate-800 dark:bg-slate-950 dark:text-slate-300 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee transition-all bg-gray-50/50">
            </div>

            <!-- Lọc trạng thái đã đọc -->
            <div>
                <select name="is_read" class="block w-full px-3 py-2 border border-gray-200 dark:border-slate-800 dark:bg-slate-950 dark:text-slate-300 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-gray-50/50">
                    <option value="">{{ __('Trạng thái đọc (Tất cả)') }}</option>
                    <option value="1" {{ request('is_read') === '1' ? 'selected' : '' }}>{{ __('Đã đọc') }}</option>
                    <option value="0" {{ request('is_read') === '0' ? 'selected' : '' }}>{{ __('Chưa đọc') }}</option>
                </select>
            </div>

            <!-- Lọc loại thông báo -->
            <div>
                <select name="type" class="block w-full px-3 py-2 border border-gray-200 dark:border-slate-800 dark:bg-slate-950 dark:text-slate-300 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-gray-50/50">
                    <option value="">{{ __('Loại thông báo (Tất cả)') }}</option>
                    <option value="personal" {{ request('type') == 'personal' ? 'selected' : '' }}>{{ __('Cá nhân') }}</option>
                    <option value="general" {{ request('type') == 'general' ? 'selected' : '' }}>{{ __('Chung / Hệ thống') }}</option>
                </select>
            </div>

            <!-- Nút gửi lọc và Bỏ lọc -->
            <div class="flex gap-2">
                <button type="submit" class="flex-grow px-5 py-2 text-xs font-semibold text-white bg-shopee hover:bg-shopee-dark rounded-xl transition-all shadow-md shrink-0">
                    {{ __('Lọc') }}
                </button>
                @if($hasFilter)
                    <a href="{{ route('admin.logs.notifications') }}" class="flex-grow inline-flex items-center justify-center px-4 py-2 text-xs font-semibold text-gray-500 hover:text-gray-700 dark:text-slate-400 dark:hover:text-slate-200 bg-gray-100 hover:bg-gray-200 dark:bg-slate-800 dark:hover:bg-slate-700 rounded-xl transition-all shadow-sm shrink-0 gap-1">
                        <i data-lucide="x" class="w-3.5 h-3.5"></i>
                        <span>{{ __('Bỏ lọc') }}</span>
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Bảng danh sách nhật ký thông báo -->
    <div class="bg-white dark:bg-slate-900 rounded-3xl shadow-sm border border-gray-200 dark:border-slate-800 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse whitespace-nowrap">
                <thead>
                    <tr class="border-b border-gray-200 dark:border-slate-800 text-xs font-bold text-gray-400 dark:text-slate-500 bg-gray-50/50 dark:bg-slate-950/40">
                        <th class="p-4 w-12 text-center">ID</th>
                        <th class="p-4 w-60">{{ __('Người nhận') }}</th>
                        <th class="p-4">{{ __('Thông tin thông báo') }}</th>
                        <th class="p-4 text-center w-32">{{ __('Loại') }}</th>
                        <th class="p-4 text-center w-32">{{ __('Trạng thái đọc') }}</th>
                        <th class="p-4 text-center w-40">{{ __('Thời gian gửi') }}</th>
                        <th class="p-4 text-center w-20">{{ __('Hành động') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-slate-800 text-xs text-gray-700 dark:text-slate-300">
                    @forelse($logs as $log)
                        <tr x-data="{ expanded: false }">
                            <td class="p-4 text-center font-bold text-gray-400 dark:text-slate-500">{{ $log->id }}</td>
                            <td class="p-4">
                                @if($log->user)
                                    <!-- Hiển thị thông tin user nhận kèm avatar và liên kết chỉnh sửa hồ sơ -->
                                    <div class="flex items-center gap-2.5">
                                        <div class="w-7 h-7 rounded-lg bg-gradient-to-tr from-shopee/20 to-shopee/10 flex items-center justify-center text-shopee font-bold text-xs shrink-0">
                                            {{ mb_strtoupper(mb_substr($log->user->name ?? 'U', 0, 1)) }}
                                        </div>
                                        <div class="min-w-0">
                                            <p class="font-bold text-gray-950 dark:text-slate-200 truncate">{{ $log->user->name }}</p>
                                            <p class="text-[10px] text-gray-400 dark:text-slate-500 font-mono truncate">{{ $log->user->email }}</p>
                                        </div>
                                        <a href="{{ route('admin.users.edit', $log->user->id) }}" 
                                           class="p-1 text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-950/20 rounded-lg inline-flex items-center justify-center transition-colors shrink-0" 
                                           title="{{ __('Chỉnh sửa thành viên') }}">
                                            <i data-lucide="edit" class="w-3.5 h-3.5"></i>
                                        </a>
                                    </div>
                                @else
                                    <!-- Trường hợp thông báo chung hệ thống không có user cụ thể -->
                                    <div class="flex items-center gap-2 text-gray-400 dark:text-slate-500 italic">
                                        <i data-lucide="globe" class="w-4 h-4 text-gray-400 shrink-0"></i>
                                        <span>{{ __('Gửi toàn bộ thành viên') }}</span>
                                    </div>
                                @endif
                            </td>
                            <td class="p-4">
                                <!-- Hiển thị tiêu đề và nội dung thu gọn/mở rộng -->
                                <div class="max-w-md">
                                    <p class="font-bold text-gray-900 dark:text-slate-200 truncate" title="{{ $log->title }}">
                                        {{ $log->title }}
                                    </p>
                                    <div class="mt-1 text-gray-500 dark:text-slate-400 text-[11px]">
                                        <!-- Đoạn text thu gọn -->
                                        <div x-show="!expanded" class="flex items-center gap-1">
                                            <span class="truncate max-w-sm">{{ Str::limit(strip_tags($log->content), 60) }}</span>
                                            @if(strlen(strip_tags($log->content)) > 60)
                                                <button @click="expanded = true" class="text-shopee hover:underline font-semibold text-[10px] shrink-0">
                                                    {{ __('Xem thêm') }}
                                                </button>
                                            @endif
                                        </div>
                                        <!-- Đoạn text đầy đủ khi mở rộng -->
                                        <div x-show="expanded" x-cloak class="space-y-1 bg-gray-55 dark:bg-slate-950 p-2.5 rounded-xl border border-gray-100 dark:border-slate-800/80 whitespace-normal break-words">
                                            <div>{!! nl2br(e($log->content)) !!}</div>
                                            <button @click="expanded = false" class="text-shopee hover:underline font-semibold text-[10px] block mt-1">
                                                {{ __('Thu gọn') }}
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="p-4 text-center">
                                <!-- Phân loại thông báo -->
                                @if($log->type == 'personal')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold bg-blue-50 text-blue-700 border border-blue-100 dark:bg-blue-950/30 dark:text-blue-400 dark:border-blue-900/40">
                                        <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span>
                                        {{ __('Cá nhân') }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold bg-purple-50 text-purple-700 border border-purple-100 dark:bg-purple-950/30 dark:text-purple-400 dark:border-purple-900/40">
                                        <span class="w-1.5 h-1.5 rounded-full bg-purple-500"></span>
                                        {{ __('Hệ thống') }}
                                    </span>
                                @endif
                            </td>
                            <td class="p-4 text-center">
                                <!-- Trạng thái người dùng đã đọc thông báo hay chưa -->
                                @if($log->is_read)
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold bg-green-50 text-green-700 border border-green-100 dark:bg-green-950/30 dark:text-green-400 dark:border-green-900/40">
                                        <i data-lucide="check" class="w-3 h-3 text-green-500"></i>
                                        {{ __('Đã đọc') }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-100 dark:bg-amber-950/30 dark:text-amber-400 dark:border-amber-900/40">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                                        {{ __('Chưa đọc') }}
                                    </span>
                                @endif
                            </td>
                            <td class="p-4 text-center text-gray-400 dark:text-slate-500 text-[10px]" title="{{ $log->created_at }}">
                                {{ $log->created_at->format('d/m/Y H:i:s') }}
                                <span class="block text-[9px] text-gray-300 dark:text-slate-600 font-medium">{{ $log->created_at->diffForHumans() }}</span>
                            </td>
                            <td class="p-4 text-center">
                                <!-- Nút xóa thông báo kèm popup xác nhận an toàn -->
                                <form action="{{ route('admin.logs.notifications.delete', $log->id) }}" 
                                      method="POST" 
                                      class="inline" 
                                      onsubmit="return confirm('{{ __('Bạn có chắc chắn muốn xóa thông báo này không?') }}')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" 
                                            class="p-2 text-red-600 hover:bg-red-50 dark:hover:bg-red-950/20 rounded-xl inline-flex items-center justify-center transition-all"
                                            title="{{ __('Xóa thông báo') }}">
                                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="p-8 text-center text-gray-400 dark:text-slate-500 italic">
                                {{ __('Không tìm thấy lịch sử thông báo nào.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Phân trang dữ liệu -->
        @if($logs->hasPages())
            <div class="p-4 border-t border-gray-100 dark:border-slate-800 bg-gray-55/50 dark:bg-slate-950/40">
                {{ $logs->links() }}
            </div>
        @endif
    </div>

    <!-- Modal xác nhận dọn dẹp nhật ký thông báo -->
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
                        <h3 class="text-lg font-bold text-gray-900">{{ __('Xác Nhận Dọn Dẹp Nhật Ký Thông Báo') }}</h3>
                        <p class="text-xs text-gray-500">{{ __('Hành động này không thể hoàn tác!') }}</p>
                    </div>
                </div>
                <p class="text-sm text-gray-600 mb-6 leading-relaxed">
                    {{ __('Bạn có chắc chắn muốn xóa toàn bộ lịch sử thông báo đã gửi cho người dùng trong hệ thống?') }}
                </p>
                <div class="flex items-center justify-end gap-3">
                    <button type="button" @click="showClearModal = false" class="px-4 py-2.5 text-xs font-semibold text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-xl transition-all">
                        {{ __('Hủy bỏ') }}
                    </button>
                    <form action="{{ route('admin.logs.notifications.clear') }}" method="POST" class="inline">
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
