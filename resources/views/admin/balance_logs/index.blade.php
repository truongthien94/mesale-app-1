@extends('layouts.admin')

@section('title', __('Nhật Ký Biến Động Số Dư - Admin Panel'))

@php
    $hasFilter = request('user_search') || request('type') || (request('limit') && request('limit') != 15);
@endphp

@section('content')
<div class="space-y-6" x-data="{ 
    showFilter: @json($hasFilter),
    showClearModal: false
}">
    <!-- Tiêu đề & Hành động -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ __('Nhật Ký Biến Động Số Dư') }}</h1>
            <p class="text-sm text-gray-500">{{ __('Theo dõi toàn bộ lịch sử dòng tiền, biến động số dư ví của các thành viên trên hệ thống') }}</p>
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

            <!-- Nút dọn dẹp tất cả biến động số dư -->
            <button type="button" 
                    @click="showClearModal = true"
                    class="inline-flex items-center gap-1.5 px-4 py-2.5 text-xs font-semibold text-white bg-red-600 hover:bg-red-700 rounded-xl transition-all shadow-md cursor-pointer whitespace-nowrap">
                <i data-lucide="trash-2" class="w-4 h-4"></i>
                <span>{{ __('Dọn dẹp tất cả') }}</span>
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
        <form action="{{ route('admin.balance_logs.index') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
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

            <div>
                <select name="type" class="block w-full px-3 py-2 border border-gray-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-gray-50/50">
                    <option value="">{{ __('Tất cả loại giao dịch') }}</option>
                    <option value="cashback" {{ request('type') === 'cashback' ? 'selected' : '' }}>{{ __('Hoàn tiền Shopee') }}</option>
                    <option value="referral" {{ request('type') === 'referral' ? 'selected' : '' }}>{{ __('Hoa hồng giới thiệu') }}</option>
                    <option value="checkin" {{ request('type') === 'checkin' ? 'selected' : '' }}>{{ __('Điểm danh nhận xu') }}</option>
                    <option value="withdraw_request" {{ request('type') === 'withdraw_request' ? 'selected' : '' }}>{{ __('Lệnh rút tiền') }}</option>
                    <option value="withdraw_refund" {{ request('type') === 'withdraw_refund' ? 'selected' : '' }}>{{ __('Hoàn tiền lệnh rút') }}</option>
                    <option value="admin_adjust" {{ request('type') === 'admin_adjust' ? 'selected' : '' }}>{{ __('Admin điều chỉnh') }}</option>
                    <option value="task_reward" {{ request('type') === 'task_reward' ? 'selected' : '' }}>{{ __('Thưởng nhiệm vụ') }}</option>
                </select>
            </div>

            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                    <i data-lucide="user" class="w-4 h-4"></i>
                </div>
                <input type="text" 
                       name="user_search" 
                       value="{{ request('user_search') }}"
                       placeholder="{{ __('Thành viên (Tên, email, sđt)...') }}" 
                       class="block w-full pl-9 pr-4 py-2 border border-gray-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee transition-all bg-gray-50/50">
            </div>

            <div class="flex gap-2">
                <button type="submit" class="flex-grow px-5 py-2 text-xs font-semibold text-white bg-shopee hover:bg-shopee-dark rounded-xl transition-all shadow-md shrink-0">
                    {{ __('Lọc') }}
                </button>
                @if($hasFilter)
                    <a href="{{ route('admin.balance_logs.index') }}" class="flex-grow inline-flex items-center justify-center px-4 py-2 text-xs font-semibold text-gray-500 hover:text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-xl transition-all shadow-sm shrink-0 gap-1">
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
                        <th class="p-4 text-center">{{ __('Số dư trước') }}</th>
                        <th class="p-4 text-center">{{ __('Biến động') }}</th>
                        <th class="p-4 text-center">{{ __('Số dư sau') }}</th>
                        <th class="p-4 text-center">{{ __('Phân loại') }}</th>
                        <th class="p-4">{{ __('Nội dung chi tiết') }}</th>
                        <th class="p-4 text-center">{{ __('Thời gian') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-xs">
                    @forelse($logs as $log)
                        <tr>
                            <td class="p-4 font-bold text-gray-400">{{ $log->id }}</td>
                            <td class="p-4">
                                @if($log->user)
                                    {{-- Hiển thị thông tin thành viên kèm liên kết chỉnh sửa thông tin (admin.users.edit) phục vụ đối soát, kiểm tra tài chính của admin --}}
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
                                    <p class="text-gray-400 italic">{{ __('Không rõ thành viên') }}</p>
                                @endif
                            </td>
                            <td class="p-4 text-center font-mono text-gray-500 font-medium">
                                {{ number_format($log->amount_before) }}đ
                            </td>
                            <td class="p-4 text-center font-bold text-sm">
                                <span class="{{ $log->amount_change > 0 ? 'text-green-600' : 'text-red-500' }}">
                                    {{ ($log->amount_change > 0 ? '+' : '') . number_format($log->amount_change) }}đ
                                </span>
                            </td>
                            <td class="p-4 text-center font-mono text-gray-700 font-semibold">
                                {{ number_format($log->amount_after) }}đ
                            </td>
                            <td class="p-4 text-center">
                                @switch($log->type)
                                    @case('cashback')
                                        <span class="inline-flex px-2 py-0.5 rounded-full text-[9px] font-bold bg-green-50 text-green-600 border border-green-100">{{ __('Hoàn tiền') }}</span>
                                        @break
                                    @case('referral')
                                        <span class="inline-flex px-2 py-0.5 rounded-full text-[9px] font-bold bg-blue-50 text-blue-600 border border-blue-100">{{ __('Hoa hồng MLM') }}</span>
                                        @break
                                    @case('checkin')
                                        <span class="inline-flex px-2 py-0.5 rounded-full text-[9px] font-bold bg-amber-50 text-amber-600 border border-amber-100">{{ __('Điểm danh') }}</span>
                                        @break
                                    @case('withdraw_request')
                                        <span class="inline-flex px-2 py-0.5 rounded-full text-[9px] font-bold bg-red-50 text-red-600 border border-red-100">{{ __('Rút tiền') }}</span>
                                        @break
                                    @case('withdraw_refund')
                                        <span class="inline-flex px-2 py-0.5 rounded-full text-[9px] font-bold bg-purple-50 text-purple-600 border border-purple-100">{{ __('Hoàn tiền rút') }}</span>
                                        @break
                                    @case('admin_adjust')
                                        <span class="inline-flex px-2 py-0.5 rounded-full text-[9px] font-bold bg-gray-50 text-gray-600 border border-gray-100">{{ __('Admin điều chỉnh') }}</span>
                                        @break
                                    @case('task_reward')
                                        <span class="inline-flex px-2 py-0.5 rounded-full text-[9px] font-bold bg-teal-50 text-teal-600 border border-teal-100">{{ __('Nhiệm vụ') }}</span>
                                        @break
                                    @default
                                        <span class="inline-flex px-2 py-0.5 rounded-full text-[9px] font-bold bg-slate-50 text-slate-600 border border-slate-100">{{ $log->type }}</span>
                                @endswitch
                            </td>
                            <td class="p-4 font-medium text-gray-700 max-w-xs truncate" title="{{ $log->description }}">
                                {{ $log->description }}
                            </td>
                            <td class="p-4 text-center text-gray-400 text-[10px]" title="{{ $log->created_at }}">
                                {{ $log->created_at->format('d/m/Y H:i') }}
                                <span class="block text-[9px] text-gray-300 font-medium">{{ $log->created_at->diffForHumans() }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="p-8 text-center text-gray-400">{{ __('Không tìm thấy dòng tiền biến động nào.') }}</td>
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

    <!-- Modal xác nhận dọn dẹp biến động số dư -->
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
                        <h3 class="text-lg font-bold text-gray-900">{{ __('Xác Nhận Dọn Dẹp Biến Động Số Dư') }}</h3>
                        <p class="text-xs text-gray-500">{{ __('Hành động này không thể hoàn tác!') }}</p>
                    </div>
                </div>
                <p class="text-sm text-gray-600 mb-6 leading-relaxed">
                    {{ __('Bạn có chắc chắn muốn xóa toàn bộ lịch sử biến động số dư dòng tiền trong hệ thống?') }}
                </p>
                <div class="flex items-center justify-end gap-3">
                    <button type="button" @click="showClearModal = false" class="px-4 py-2.5 text-xs font-semibold text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-xl transition-all">
                        {{ __('Hủy bỏ') }}
                    </button>
                    <form action="{{ route('admin.balance_logs.clear') }}" method="POST" class="inline">
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
