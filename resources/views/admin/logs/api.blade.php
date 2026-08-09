{{--
    View: Trang nhật ký gọi API (Admin Panel)
    Mục đích: Giúp Admin giám sát hoạt động sử dụng API của thành viên
    — biết ai gọi endpoint nào, lúc nào, dữ liệu gì, kết quả ra sao.
--}}
@extends('layouts.admin')

@section('title', __('Nhật Ký Gọi API') . ' - ' . $siteName)

@php
    // Kiểm tra có bộ lọc nào đang active không để điều khiển hiển thị panel filter
    $hasFilter = request('user_search') || request('endpoint') || request('method_filter') 
                 || request('api_group') || request('status_code') || request('ip_address') 
                 || (request('limit') && request('limit') != 15);
@endphp

@section('content')
<div class="space-y-6" x-data="{ 
    showFilter: @json($hasFilter),
    expandedRow: null,
    showClearModal: false
}">
    <!-- Tiêu đề & Hành động -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ __('Nhật Ký Gọi API') }}</h1>
            <p class="text-sm text-gray-500">{{ __('Giám sát toàn bộ hoạt động gọi API của thành viên — endpoint, dữ liệu request, thời gian xử lý và trạng thái phản hồi') }}</p>
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

            <!-- Nút dọn dẹp nhật ký gọi API -->
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
        <form action="{{ route('admin.logs.api') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
            <!-- Số dòng hiển thị -->
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

            <!-- Tìm thành viên -->
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

            <!-- Tìm endpoint -->
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                    <i data-lucide="globe" class="w-4 h-4"></i>
                </div>
                <input type="text" 
                       name="endpoint" 
                       value="{{ request('endpoint') }}"
                       placeholder="{{ __('Endpoint (ví dụ: /orders)...') }}" 
                       class="block w-full pl-9 pr-4 py-2 border border-gray-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee transition-all bg-gray-50/50">
            </div>

            <!-- Lọc HTTP Method -->
            <div>
                <select name="method_filter" class="block w-full px-3 py-2 border border-gray-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-gray-50/50">
                    <option value="">{{ __('Tất cả Method') }}</option>
                    <option value="GET" {{ request('method_filter') == 'GET' ? 'selected' : '' }}>GET</option>
                    <option value="POST" {{ request('method_filter') == 'POST' ? 'selected' : '' }}>POST</option>
                    <option value="PUT" {{ request('method_filter') == 'PUT' ? 'selected' : '' }}>PUT</option>
                    <option value="DELETE" {{ request('method_filter') == 'DELETE' ? 'selected' : '' }}>DELETE</option>
                </select>
            </div>

            <!-- Lọc nhóm API -->
            <div>
                <select name="api_group" class="block w-full px-3 py-2 border border-gray-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-gray-50/50">
                    <option value="">{{ __('Tất cả nhóm API') }}</option>
                    <option value="openapi" {{ request('api_group') == 'openapi' ? 'selected' : '' }}>Open API</option>
                    <option value="bot" {{ request('api_group') == 'bot' ? 'selected' : '' }}>Bot API</option>
                </select>
            </div>

            <!-- Lọc HTTP Status Code -->
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                    <i data-lucide="hash" class="w-4 h-4"></i>
                </div>
                <input type="number" 
                       name="status_code" 
                       value="{{ request('status_code') }}"
                       placeholder="{{ __('Status Code (200, 401...)') }}" 
                       class="block w-full pl-9 pr-4 py-2 border border-gray-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee transition-all bg-gray-50/50">
            </div>

            <!-- Lọc IP -->
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                    <i data-lucide="monitor" class="w-4 h-4"></i>
                </div>
                <input type="text" 
                       name="ip_address" 
                       value="{{ request('ip_address') }}"
                       placeholder="{{ __('Địa chỉ IP...') }}" 
                       class="block w-full pl-9 pr-4 py-2 border border-gray-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee transition-all bg-gray-50/50">
            </div>

            <!-- Nút lọc & bỏ lọc -->
            <div class="flex gap-2">
                <button type="submit" class="flex-grow px-5 py-2 text-xs font-semibold text-white bg-shopee hover:bg-shopee-dark rounded-xl transition-all shadow-md shrink-0">
                    {{ __('Lọc') }}
                </button>
                @if($hasFilter)
                    <a href="{{ route('admin.logs.api') }}" class="flex-grow inline-flex items-center justify-center px-4 py-2 text-xs font-semibold text-gray-500 hover:text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-xl transition-all shadow-sm shrink-0 gap-1">
                        <i data-lucide="x" class="w-3.5 h-3.5"></i>
                        <span>{{ __('Bỏ lọc') }}</span>
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Bảng dữ liệu -->
    <div class="bg-white rounded-3xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse whitespace-nowrap">
                <thead>
                    <tr class="border-b border-gray-200 text-xs font-bold text-gray-400 bg-gray-50/50">
                        <th class="p-4">ID</th>
                        <th class="p-4">{{ __('Thành viên') }}</th>
                        <th class="p-4">{{ __('Endpoint') }}</th>
                        <th class="p-4 text-center">{{ __('Nhóm') }}</th>
                        <th class="p-4 text-center">{{ __('Status') }}</th>
                        <th class="p-4 text-center">{{ __('Thời gian XL') }}</th>
                        <th class="p-4 text-center">{{ __('IP') }}</th>
                        <th class="p-4 text-center">{{ __('Thời gian') }}</th>
                        <th class="p-4 text-center">{{ __('Chi tiết') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-xs">
                    @forelse($logs as $log)
                        <tr class="hover:bg-gray-50/50 transition-colors">
                            <td class="p-4 font-bold text-gray-400">{{ $log->id }}</td>
                            
                            {{-- Thông tin thành viên gọi API --}}
                            <td class="p-4">
                                @if($log->user)
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
                                    <p class="text-gray-400 italic">{{ __('Chưa xác thực') }}</p>
                                @endif
                            </td>

                            {{-- HTTP Method + Endpoint --}}
                            <td class="p-4">
                                <div class="flex items-center gap-2">
                                    {{-- Badge HTTP Method với mã màu phân biệt trực quan --}}
                                    @php
                                        $methodColors = [
                                            'GET'    => 'bg-blue-100 text-blue-700',
                                            'POST'   => 'bg-green-100 text-green-700',
                                            'PUT'    => 'bg-amber-100 text-amber-700',
                                            'DELETE' => 'bg-red-100 text-red-700',
                                        ];
                                        $methodClass = $methodColors[$log->method] ?? 'bg-gray-100 text-gray-700';
                                    @endphp
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-extrabold {{ $methodClass }}">
                                        {{ $log->method }}
                                    </span>
                                    <span class="font-mono font-medium text-gray-700 max-w-xs truncate" title="{{ $log->endpoint }}">
                                        {{ $log->endpoint }}
                                    </span>
                                </div>
                            </td>

                            {{-- Nhóm API (openapi / bot) --}}
                            <td class="p-4 text-center">
                                @if($log->api_group === 'bot')
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-violet-100 text-violet-700">
                                        <i data-lucide="bot" class="w-3 h-3"></i>
                                        Bot
                                    </span>
                                @elseif($log->api_group === 'openapi')
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-sky-100 text-sky-700">
                                        <i data-lucide="smartphone" class="w-3 h-3"></i>
                                        Open API
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-gray-100 text-gray-500">
                                        {{ $log->api_group }}
                                    </span>
                                @endif
                            </td>

                            {{-- HTTP Status Code với mã màu theo nhóm 2xx/4xx/5xx --}}
                            <td class="p-4 text-center">
                                @php
                                    $statusCode = $log->status_code;
                                    if ($statusCode >= 200 && $statusCode < 300) {
                                        $statusClass = 'bg-emerald-100 text-emerald-700';
                                    } elseif ($statusCode >= 400 && $statusCode < 500) {
                                        $statusClass = 'bg-amber-100 text-amber-700';
                                    } elseif ($statusCode >= 500) {
                                        $statusClass = 'bg-red-100 text-red-700';
                                    } else {
                                        $statusClass = 'bg-gray-100 text-gray-700';
                                    }
                                @endphp
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-extrabold {{ $statusClass }}">
                                    {{ $statusCode }}
                                </span>
                            </td>

                            {{-- Thời gian xử lý request (ms) --}}
                            <td class="p-4 text-center font-mono text-gray-600 font-medium">
                                @if($log->response_time_ms !== null)
                                    @php
                                        $timeClass = $log->response_time_ms > 1000 ? 'text-red-600 font-bold' : ($log->response_time_ms > 500 ? 'text-amber-600' : 'text-gray-600');
                                    @endphp
                                    <span class="{{ $timeClass }}">{{ number_format($log->response_time_ms) }}ms</span>
                                @else
                                    <span class="text-gray-300">—</span>
                                @endif
                            </td>

                            {{-- Địa chỉ IP nguồn --}}
                            <td class="p-4 text-center font-mono text-gray-600 font-medium">
                                {{ $log->ip_address ?? '—' }}
                            </td>

                            {{-- Thời gian gọi API --}}
                            <td class="p-4 text-center text-gray-400 text-[10px]" title="{{ $log->created_at }}">
                                {{ $log->created_at->format('d/m/Y H:i:s') }}
                                <span class="block text-[9px] text-gray-300 font-medium">{{ $log->created_at->diffForHumans() }}</span>
                            </td>

                            {{-- Nút xem chi tiết Request Data --}}
                            <td class="p-4 text-center">
                                @if($log->request_data)
                                    <button @click="expandedRow = expandedRow === {{ $log->id }} ? null : {{ $log->id }}" 
                                            class="p-1.5 rounded-lg transition-all"
                                            :class="expandedRow === {{ $log->id }} ? 'bg-shopee text-white' : 'text-gray-400 hover:text-shopee hover:bg-shopee/10'"
                                            title="{{ __('Xem dữ liệu request') }}">
                                        <i data-lucide="code" class="w-4 h-4"></i>
                                    </button>
                                @else
                                    <span class="text-gray-300">—</span>
                                @endif
                            </td>
                        </tr>

                        {{-- Hàng mở rộng hiển thị dữ liệu Request Data (JSON) --}}
                        @if($log->request_data)
                            <tr x-show="expandedRow === {{ $log->id }}"
                                x-transition:enter="transition ease-out duration-150"
                                x-transition:enter-start="opacity-0"
                                x-transition:enter-end="opacity-100"
                                x-transition:leave="transition ease-in duration-100"
                                x-transition:leave-start="opacity-100"
                                x-transition:leave-end="opacity-0"
                                style="display: none;">
                                <td colspan="9" class="p-4 bg-slate-50 border-l-4 border-shopee">
                                    <div class="flex items-start gap-3">
                                        <div class="shrink-0 mt-0.5">
                                            <span class="w-7 h-7 rounded-lg bg-slate-200 text-slate-600 flex items-center justify-center">
                                                <i data-lucide="braces" class="w-4 h-4"></i>
                                            </span>
                                        </div>
                                        <div class="flex-grow min-w-0">
                                            <p class="text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">{{ __('Dữ liệu Request') }}</p>
                                            {{-- Hiển thị User-Agent nếu có --}}
                                            @if($log->user_agent)
                                                <p class="text-[10px] text-gray-400 mb-2 truncate" title="{{ $log->user_agent }}">
                                                    <span class="font-bold">User-Agent:</span> {{ $log->user_agent }}
                                                </p>
                                            @endif
                                            <pre class="bg-slate-900 text-emerald-400 p-3 rounded-xl text-[11px] font-mono overflow-x-auto max-h-64 leading-relaxed whitespace-pre-wrap break-all">{{ json_encode(json_decode($log->request_data), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: $log->request_data }}</pre>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endif
                    @empty
                        <tr>
                            <td colspan="9" class="p-8 text-center text-gray-400">
                                <div class="flex flex-col items-center gap-2">
                                    <i data-lucide="webhook" class="w-10 h-10 text-gray-200"></i>
                                    <p class="font-medium">{{ __('Chưa có nhật ký gọi API nào.') }}</p>
                                    <p class="text-[10px] text-gray-300">{{ __('Dữ liệu sẽ xuất hiện tự động khi có request gọi đến hệ thống API.') }}</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Phân trang -->
        @if($logs->hasPages())
            <div class="p-4 border-t border-gray-100 bg-gray-50/50">
                {{ $logs->links() }}
            </div>
        @endif
    </div>

    <!-- Modal xác nhận dọn dẹp nhật ký gọi API -->
    <div x-show="showClearModal" 
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto"
         style="display: none;">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" @click="showClearModal = false"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <div class="inline-block align-bottom bg-white rounded-3xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full p-6 border border-gray-100 relative z-10"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95">
                
                <div class="flex items-center gap-4 text-red-600 mb-4">
                    <div class="w-12 h-12 rounded-2xl bg-red-50 flex items-center justify-center shrink-0">
                        <i data-lucide="triangle-alert" class="w-6 h-6"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-gray-900">{{ __('Xác Nhận Dọn Dẹp Nhật Ký API') }}</h3>
                        <p class="text-xs text-gray-500">{{ __('Hành động này không thể hoàn tác!') }}</p>
                    </div>
                </div>

                <p class="text-sm text-gray-600 mb-6 leading-relaxed">
                    {{ __('Bạn có chắc chắn muốn xóa toàn bộ lịch sử gọi API hiện có trong hệ thống? Việc này sẽ làm sạch dữ liệu trong bảng nhật ký và không thể khôi phục lại dữ liệu đã xóa.') }}
                </p>

                <div class="flex items-center justify-end gap-3">
                    <button type="button" 
                            @click="showClearModal = false"
                            class="px-4 py-2.5 text-xs font-semibold text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-xl transition-all">
                        {{ __('Hủy bỏ') }}
                    </button>
                    <form action="{{ route('admin.logs.api.clear') }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" 
                                class="px-4 py-2.5 text-xs font-semibold text-white bg-red-600 hover:bg-red-700 rounded-xl transition-all shadow-md">
                            {{ __('Xác nhận dọn dẹp') }}
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
