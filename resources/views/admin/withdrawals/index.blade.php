@extends('layouts.admin')

@section('title', __('Quản Lý Yêu Cầu Rút Tiền') . ' - ' . $siteName)

@php
    $hasFilter = request('user_search') || request('account_search') || request('bank_name') || request('status') || request('start_date') || request('end_date') || (request('limit') && request('limit') != 15);
@endphp

@section('content')
<div class="space-y-6" x-data="{ showFilter: @json($hasFilter), ...withdrawAdminHandler() }">
    <!-- Tiêu đề & Hành động -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ __('Yêu Cầu Rút Tiền') }}</h1>
            <p class="text-sm text-gray-500 dark:text-slate-400">{{ __('Phê duyệt giải ngân hoặc từ chối hoàn trả số dư các lệnh rút tiền từ thành viên') }}</p>
        </div>
        <div class="flex items-center gap-2">
            <!-- Nút cấu hình rút tiền -->
            <a href="{{ route('admin.settings.index', ['tab' => 'withdraw']) }}" 
               class="inline-flex items-center gap-1.5 px-4 py-2.5 text-xs font-semibold text-gray-700 dark:text-slate-250 bg-white hover:bg-gray-50 dark:bg-slate-900 dark:hover:bg-slate-850 border border-gray-200 dark:border-slate-800 rounded-xl transition-all shadow-sm">
                <i data-lucide="settings" class="w-4 h-4 text-gray-500"></i>
                <span>{{ __('Cấu hình') }}</span>
            </a>

            <!-- Nút thống kê rút tiền -->
            <button @click="openWithdrawStatsModal()" 
                    class="inline-flex items-center gap-1.5 px-4 py-2.5 text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded-xl transition-all shadow-md">
                <i data-lucide="bar-chart-3" class="w-4 h-4"></i>
                <span>{{ __('Thống kê') }}</span>
            </button>

            <!-- Nút bật/tắt bộ lọc -->
            <button @click="showFilter = !showFilter" 
                    class="inline-flex items-center gap-1.5 px-4 py-2.5 text-xs font-semibold rounded-xl border border-gray-200 dark:border-slate-800 transition-all shadow-sm"
                    :class="showFilter ? 'bg-shopee text-white border-shopee hover:bg-shopee-dark' : 'bg-white dark:bg-slate-900 hover:bg-gray-50 dark:hover:bg-slate-800 text-gray-700 dark:text-slate-350'">
                <i data-lucide="filter" class="w-4 h-4"></i>
                <span>{{ __('Bộ lọc') }}</span>
                @if($hasFilter)
                    <span class="w-1.5 h-1.5 rounded-full bg-red-500 animate-pulse"></span>
                @endif
            </button>
        </div>
    </div>

    <!-- Thống kê rút tiền nhanh -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- 1. Hôm nay -->
        <div class="bg-white dark:bg-slate-900 p-6 rounded-2xl shadow-sm border border-gray-200 dark:border-slate-800 flex items-center justify-between">
            <div class="space-y-1">
                <span class="text-xs font-bold text-gray-400 dark:text-slate-500 uppercase tracking-wider block">{{ __('Rút hôm nay') }}</span>
                <p class="text-2xl font-extrabold text-green-600 dark:text-green-400">{{ number_format($todayWithdrawn) }}đ</p>
                <span class="text-[10px] text-gray-400 dark:text-slate-500 block">{{ __('Lệnh đã duyệt thành công') }}</span>
            </div>
            <div class="p-3 bg-green-50 dark:bg-green-950/30 text-green-600 dark:text-green-400 rounded-xl">
                <i data-lucide="calendar" class="w-6 h-6"></i>
            </div>
        </div>

        <!-- 2. Tuần này -->
        <div class="bg-white dark:bg-slate-900 p-6 rounded-2xl shadow-sm border border-gray-200 dark:border-slate-800 flex items-center justify-between">
            <div class="space-y-1">
                <span class="text-xs font-bold text-gray-400 dark:text-slate-500 uppercase tracking-wider block">{{ __('Rút tuần này') }}</span>
                <p class="text-2xl font-extrabold text-blue-600 dark:text-blue-400">{{ number_format($weekWithdrawn) }}đ</p>
                <span class="text-[10px] text-gray-400 dark:text-slate-500 block">{{ __('Từ đầu tuần đến nay') }}</span>
            </div>
            <div class="p-3 bg-blue-50 dark:bg-blue-950/30 text-blue-600 dark:text-blue-400 rounded-xl">
                <i data-lucide="calendar-days" class="w-6 h-6"></i>
            </div>
        </div>

        <!-- 3. Tháng này -->
        <div class="bg-white dark:bg-slate-900 p-6 rounded-2xl shadow-sm border border-gray-200 dark:border-slate-800 flex items-center justify-between">
            <div class="space-y-1">
                <span class="text-xs font-bold text-gray-400 dark:text-slate-500 uppercase tracking-wider block">{{ __('Rút tháng này') }}</span>
                <p class="text-2xl font-extrabold text-purple-600 dark:text-purple-400">{{ number_format($monthWithdrawn) }}đ</p>
                <span class="text-[10px] text-gray-400 dark:text-slate-500 block">{{ __('Từ đầu tháng đến nay') }}</span>
            </div>
            <div class="p-3 bg-purple-50 dark:bg-purple-950/30 text-purple-600 dark:text-purple-400 rounded-xl">
                <i data-lucide="calendar-range" class="w-6 h-6"></i>
            </div>
        </div>

        <!-- 4. Toàn thời gian -->
        <div class="bg-white dark:bg-slate-900 p-6 rounded-2xl shadow-sm border border-gray-200 dark:border-slate-800 flex items-center justify-between">
            <div class="space-y-1">
                <span class="text-xs font-bold text-gray-400 dark:text-slate-500 uppercase tracking-wider block">{{ __('Rút toàn thời gian') }}</span>
                <p class="text-2xl font-extrabold text-teal-600 dark:text-teal-400">{{ number_format($totalWithdrawn) }}đ</p>
                <span class="text-[10px] text-gray-400 dark:text-slate-500 block">{{ __('Tổng tích lũy trọn đời') }}</span>
            </div>
            <div class="p-3 bg-teal-50 dark:bg-teal-950/30 text-teal-600 dark:text-teal-400 rounded-xl">
                <i data-lucide="globe" class="w-6 h-6"></i>
            </div>
        </div>
    </div>

    <!-- Tab Lọc Trạng Thái Nhanh -->
    <div class="flex flex-wrap gap-2 pb-2">
        <a href="javascript:void(0)" 
           @click="loadTab('{{ route("admin.withdrawals.index", array_merge(request()->except(['status', 'page']))) }}', '')"
           class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-bold rounded-xl border transition-all shadow-sm"
           :class="activeStatus === '' ? 'bg-shopee text-white border-shopee' : 'bg-white hover:bg-gray-50 text-gray-700 border-gray-200'">
            <span>{{ __('Tất cả') }}</span>
            <span class="px-1.5 py-0.5 rounded-md text-[10px]"
                  :class="activeStatus === '' ? 'bg-white/20 text-white font-extrabold' : 'bg-gray-100 dark:bg-slate-800 text-gray-600 dark:text-slate-350 border border-gray-200/50 dark:border-slate-700/50'"
                  x-text="statusCounts.all">
            </span>
        </a>
        <a href="javascript:void(0)" 
           @click="loadTab('{{ route("admin.withdrawals.index", array_merge(request()->except(['status', 'page']), ['status' => 'pending'])) }}', 'pending')"
           class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-bold rounded-xl border transition-all shadow-sm"
           :class="activeStatus === 'pending' ? 'bg-shopee text-white border-shopee' : 'bg-white hover:bg-gray-50 text-gray-700 border-gray-200'">
            <span>{{ __('Chờ duyệt') }}</span>
            <span class="px-1.5 py-0.5 rounded-md text-[10px]"
                  :class="activeStatus === 'pending' ? 'bg-white/20 text-white font-extrabold' : 'bg-yellow-50 dark:bg-yellow-950/20 text-yellow-600 dark:text-yellow-400 border border-yellow-100 dark:border-yellow-900/30'"
                  x-text="statusCounts.pending">
            </span>
        </a>
        <a href="javascript:void(0)" 
           @click="loadTab('{{ route("admin.withdrawals.index", array_merge(request()->except(['status', 'page']), ['status' => 'approved'])) }}', 'approved')"
           class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-bold rounded-xl border transition-all shadow-sm"
           :class="activeStatus === 'approved' ? 'bg-shopee text-white border-shopee' : 'bg-white hover:bg-gray-50 text-gray-700 border-gray-200'">
            <span>{{ __('Đã thanh toán') }}</span>
            <span class="px-1.5 py-0.5 rounded-md text-[10px]"
                  :class="activeStatus === 'approved' ? 'bg-white/20 text-white font-extrabold' : 'bg-green-50 dark:bg-green-950/20 text-green-600 dark:text-green-400 border border-green-100 dark:border-green-900/30'"
                  x-text="statusCounts.approved">
            </span>
        </a>
        <a href="javascript:void(0)" 
           @click="loadTab('{{ route("admin.withdrawals.index", array_merge(request()->except(['status', 'page']), ['status' => 'rejected'])) }}', 'rejected')"
           class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-bold rounded-xl border transition-all shadow-sm"
           :class="activeStatus === 'rejected' ? 'bg-shopee text-white border-shopee' : 'bg-white hover:bg-gray-50 text-gray-700 border-gray-200'">
            <span>{{ __('Bị từ chối') }}</span>
            <span class="px-1.5 py-0.5 rounded-md text-[10px]"
                  :class="activeStatus === 'rejected' ? 'bg-white/20 text-white font-extrabold' : 'bg-rose-50 dark:bg-rose-950/20 text-rose-600 dark:text-rose-400 border border-rose-100 dark:border-rose-900/30'"
                  x-text="statusCounts.rejected">
            </span>
        </a>
    </div>

    <!-- Bộ lọc tìm kiếm yêu cầu rút tiền -->
    <div x-show="showFilter" 
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 transform -translate-y-2"
         x-transition:enter-end="opacity-100 transform translate-y-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 transform translate-y-0"
         x-transition:leave-end="opacity-0 transform -translate-y-2"
         class="bg-white p-4 rounded-3xl shadow-sm border border-gray-200"
         style="display: {{ $hasFilter ? 'block' : 'none' }}">
        <form id="withdrawal-filter-form" action="{{ route('admin.withdrawals.index') }}" method="GET" @submit.prevent="submitFilterForm($event)" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-8 gap-3">
            <!-- Lưu vết cột sắp xếp và hướng sắp xếp hiện tại để AJAX gửi lên controller -->
            <input type="hidden" name="sort_by" value="{{ request('sort_by', 'created_at') }}">
            <input type="hidden" name="sort_order" value="{{ request('sort_order', 'desc') }}">
            
            <div>
                <select name="limit" class="block w-full h-9 px-3 py-2 border border-gray-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-gray-50/50">
                    <option value="15" {{ request('limit') == 15 ? 'selected' : '' }}>{{ __('Hiển thị 15 dòng') }}</option>
                    <option value="30" {{ request('limit') == 30 ? 'selected' : '' }}>{{ __('Hiển thị 30 dòng') }}</option>
                    <option value="50" {{ request('limit') == 50 ? 'selected' : '' }}>{{ __('Hiển thị 50 dòng') }}</option>
                    <option value="100" {{ request('limit') == 100 ? 'selected' : '' }}>{{ __('Hiển thị 100 dòng') }}</option>
                    <option value="200" {{ request('limit') == 200 ? 'selected' : '' }}>{{ __('Hiển thị 200 dòng') }}</option>
                    <option value="500" {{ request('limit') == 500 ? 'selected' : '' }}>{{ __('Hiển thị 500 dòng') }}</option>
                </select>
            </div>

            <div>
                <select name="status" class="block w-full h-9 px-3 py-2 border border-gray-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-gray-50/50">
                    <option value="">{{ __('Tất cả trạng thái') }}</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>{{ __('Chờ duyệt') }}</option>
                    <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>{{ __('Thành công') }}</option>
                    <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>{{ __('Từ chối') }}</option>
                </select>
            </div>

            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                    <i data-lucide="landmark" class="w-4 h-4"></i>
                </div>
                <input type="text" 
                       name="bank_name" 
                       value="{{ request('bank_name') }}"
                       placeholder="{{ __('Ngân hàng/Ví nhận...') }}" 
                       class="block w-full h-9 pl-9 pr-4 py-2 border border-gray-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee transition-all bg-gray-50/50">
            </div>

            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                    <i data-lucide="user" class="w-4 h-4"></i>
                </div>
                <input type="text" 
                       name="user_search" 
                       value="{{ request('user_search') }}"
                       placeholder="{{ __('Thành viên (Tên, email)...') }}" 
                       class="block w-full h-9 pl-9 pr-4 py-2 border border-gray-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee transition-all bg-gray-50/50">
            </div>

            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                    <i data-lucide="credit-card" class="w-4 h-4"></i>
                </div>
                <input type="text" 
                       name="account_search" 
                       value="{{ request('account_search') }}"
                       placeholder="{{ __('Tài khoản nhận (STK, Tên)...') }}" 
                       class="block w-full h-9 pl-9 pr-4 py-2 border border-gray-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee transition-all bg-gray-50/50">
            </div>

            <!-- Từ ngày -->
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                    <i data-lucide="calendar" class="w-4 h-4"></i>
                </div>
                <input type="date" 
                       name="start_date" 
                       value="{{ request('start_date') }}"
                       class="block w-full h-9 pl-9 pr-3 py-2 border border-gray-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee transition-all bg-gray-50/50">
            </div>

            <!-- Đến ngày -->
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                    <i data-lucide="calendar" class="w-4 h-4"></i>
                </div>
                <input type="date" 
                       name="end_date" 
                       value="{{ request('end_date') }}"
                       class="block w-full h-9 pl-9 pr-3 py-2 border border-gray-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee transition-all bg-gray-50/50">
            </div>

            <div class="flex gap-2 h-9">
                <button type="submit" class="flex-grow h-9 px-5 text-xs font-semibold text-white bg-shopee hover:bg-shopee-dark rounded-xl transition-all shadow-md shrink-0">
                    {{ __('Lọc') }}
                </button>
                <button type="button" @click="resetFilter()" class="flex-grow inline-flex items-center justify-center h-9 px-4 text-xs font-semibold text-gray-500 hover:text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-xl transition-all shadow-sm shrink-0 gap-1">
                    <i data-lucide="refresh-ccw" class="w-3.5 h-3.5"></i>
                    <span>{{ __('Xóa lọc') }}</span>
                </button>
            </div>
        </form>
    </div>

    <!-- Khu vực hiển thị bảng và hiệu ứng tải dữ liệu AJAX -->
    <div class="relative min-h-[300px]" id="withdrawal-table-container">
        <!-- Lớp phủ Loading mượt mà khi đang thực hiện cuộc gọi AJAX -->
        <div id="withdrawal-loading" class="absolute inset-0 bg-white/60 dark:bg-slate-900/60 backdrop-blur-[1px] z-20 flex items-center justify-center transition-opacity duration-300 opacity-0 pointer-events-none">
            <div class="flex flex-col items-center gap-2 bg-white dark:bg-slate-800 p-4 rounded-2xl shadow-lg border border-gray-100 dark:border-slate-700/50">
                <div class="w-8 h-8 border-4 border-shopee border-t-transparent rounded-full animate-spin"></div>
                <span class="text-xs font-bold text-gray-700 dark:text-slate-300">{{ __('Đang tải dữ liệu...') }}</span>
            </div>
        </div>

        <!-- Nội dung bảng động render từ partial template -->
        <div id="withdrawal-table-content">
            @include('admin.withdrawals.partials.table')
        </div>
    </div>

    <!-- THANH HÀNH ĐỘNG HÀNG LOẠT NỔI (Hiện khi có ít nhất 1 yêu cầu rút tiền được tích chọn) -->
    <div x-show="selectedWithdrawals.length > 0" x-cloak
         class="fixed bottom-6 left-1/2 -translate-x-1/2 z-40 bg-white/85 dark:bg-slate-900/95 backdrop-blur-md px-6 py-4 rounded-3xl shadow-2xl border border-gray-150 dark:border-slate-800/80 flex items-center gap-4 transition-all duration-300 transform"
         x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 translate-y-4">
        <div class="flex items-center gap-2.5">
            <span class="relative flex h-3 w-3">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-shopee opacity-75"></span>
                <span class="relative inline-flex rounded-full h-3 w-3 bg-shopee"></span>
            </span>
            <span class="text-xs font-bold text-gray-800 dark:text-slate-200">
                {{ __('Đã chọn:') }} <strong class="text-shopee dark:text-shopee-light" x-text="selectedWithdrawals.length"></strong> {{ __('yêu cầu') }}
                <span class="text-[10px] font-semibold text-gray-400 dark:text-slate-500" x-show="selectedPendingCount() > 0">
                    (<span x-text="selectedPendingCount()"></span> {{ __('chờ duyệt') }})
                </span>
            </span>
        </div>
        <div class="h-6 w-[1px] bg-gray-200 dark:bg-slate-800"></div>
        <div class="flex items-center gap-2">
            <!-- Nút duyệt chi nhanh hàng loạt (Chỉ kích hoạt khi có lệnh đang chờ duyệt được chọn) -->
            <button type="button" @click="openBulkApproveModal()" :disabled="selectedPendingCount() === 0"
                    class="px-4 py-2.5 text-[10px] font-bold text-white bg-green-600 hover:bg-green-700 disabled:opacity-40 disabled:cursor-not-allowed rounded-xl transition-all shadow-md flex items-center gap-1.5"
                    :title="selectedPendingCount() === 0 ? @js(__('Không có yêu cầu nào đang chờ duyệt trong danh sách đã chọn')) : ''">
                <i data-lucide="check" class="w-3.5 h-3.5"></i>
                {{ __('Duyệt hàng loạt') }}
            </button>

            <!-- Nút xóa nhanh hàng loạt -->
            <button type="button" @click="openBulkDeleteModal()" class="px-4 py-2.5 text-[10px] font-bold text-white bg-red-600 hover:bg-red-700 rounded-xl transition-all shadow-md flex items-center gap-1.5">
                <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                {{ __('Xóa hàng loạt') }}
            </button>

            <!-- Nút bỏ chọn toàn bộ -->
            <button type="button" @click="clearSelection()" class="px-3 py-2.5 text-[10px] font-bold text-gray-600 dark:text-slate-350 bg-gray-100 dark:bg-slate-800 hover:bg-gray-200 dark:hover:bg-slate-700 rounded-xl transition-all flex items-center gap-1.5" title="{{ __('Bỏ chọn tất cả') }}">
                <i data-lucide="x" class="w-3.5 h-3.5"></i>
                {{ __('Bỏ chọn') }}
            </button>
        </div>
    </div>

    <!-- MODAL XÁC NHẬN DUYỆT CHI HÀNG LOẠT -->
    <template x-teleport="body">
        <div x-show="bulkApproveModalOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-slate-950/40 backdrop-blur-sm" x-transition>
            <div class="bg-white dark:bg-slate-900 rounded-3xl max-w-md w-full shadow-2xl border border-gray-100 dark:border-slate-800 overflow-hidden" @click.away="!isSubmitting && (bulkApproveModalOpen = false)">
                <div class="px-6 py-4 bg-gray-50 dark:bg-slate-800/50 border-b border-gray-100 dark:border-slate-800 flex justify-between items-center">
                    <h3 class="font-bold text-gray-950 dark:text-white text-sm flex items-center gap-1.5">
                        <i data-lucide="check-circle-2" class="w-4 h-4 text-green-600"></i>
                        {{ __('Duyệt chi hàng loạt') }}
                    </h3>
                    <button type="button" @click="!isSubmitting && (bulkApproveModalOpen = false)" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                        <i data-lucide="x" class="w-4 h-4"></i>
                    </button>
                </div>

                <form @submit.prevent="submitBulkApprove()" class="p-6 space-y-4">
                    <div class="text-xs text-gray-600 dark:text-gray-300 space-y-3">
                        <p class="text-gray-500 dark:text-gray-400 leading-relaxed">
                            {{ __('Hệ thống sẽ phê duyệt toàn bộ các yêu cầu rút tiền đang chờ xử lý được chọn, ghi nhận tổng tiền đã rút cho từng thành viên và gửi thông báo xác nhận đến họ.') }}
                        </p>
                        <p class="text-[10px] text-red-500 font-medium">
                            {{ __('Chỉ thao tác khi đã thực sự chuyển khoản cho thành viên vì hành động này không thể hoàn tác.') }}
                        </p>

                        <div class="p-3.5 bg-green-50 dark:bg-green-950/20 border border-green-100 dark:border-green-900/50 rounded-2xl text-[10px] text-green-800 dark:text-green-300 space-y-1.5">
                            <div class="flex justify-between items-center">
                                <span>{{ __('Số yêu cầu sẽ được duyệt chi:') }}</span>
                                <strong class="text-gray-900 dark:text-white text-xs" x-text="selectedPendingCount()"></strong>
                            </div>
                            <div class="flex justify-between items-center pt-1.5 border-t border-green-100/70 dark:border-green-900/40">
                                <span>{{ __('Tổng tiền thực chi:') }}</span>
                                <strong class="text-green-700 dark:text-green-400 text-xs" x-text="formatCurrency(selectedPendingRealAmount())"></strong>
                            </div>
                        </div>

                        <!-- Cảnh báo khi danh sách chọn có lẫn các lệnh đã xử lý trước đó -->
                        <template x-if="selectedWithdrawals.length > selectedPendingCount()">
                            <p class="text-[10px] text-amber-600 dark:text-amber-400 font-medium flex items-start gap-1.5">
                                <i data-lucide="alert-circle" class="w-3.5 h-3.5 shrink-0 mt-px"></i>
                                <span>
                                    {{ __('Hệ thống sẽ tự động bỏ qua') }}
                                    <strong x-text="selectedWithdrawals.length - selectedPendingCount()"></strong>
                                    {{ __('yêu cầu đã được duyệt hoặc từ chối trước đó.') }}
                                </span>
                            </p>
                        </template>
                    </div>

                    <div class="pt-2 flex justify-end gap-2 border-t border-gray-100 dark:border-slate-800">
                        <button type="button" @click="bulkApproveModalOpen = false" :disabled="isSubmitting" class="px-4 py-2 text-xs font-semibold text-gray-700 dark:text-slate-350 bg-gray-100 dark:bg-slate-800 hover:bg-gray-200 dark:hover:bg-slate-700 rounded-xl transition-all disabled:opacity-50">{{ __('Huỷ') }}</button>
                        <button type="submit" :disabled="isSubmitting || selectedPendingCount() === 0" class="px-5 py-2 text-xs font-semibold text-white bg-green-600 hover:bg-green-700 rounded-xl transition-all shadow-md flex items-center justify-center min-w-[150px] disabled:opacity-50 disabled:cursor-not-allowed">
                            <span x-show="!isSubmitting" class="flex items-center gap-1.5">
                                <i data-lucide="check" class="w-3.5 h-3.5"></i>
                                {{ __('Xác nhận duyệt chi') }}
                            </span>
                            <span x-show="isSubmitting" class="flex items-center gap-1.5">
                                <svg class="animate-spin -ml-1 mr-1.5 h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                {{ __('Đang duyệt...') }}
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </template>

    <!-- MODAL XÁC NHẬN XÓA HÀNG LOẠT YÊU CẦU RÚT TIỀN -->
    <template x-teleport="body">
        <div x-show="bulkDeleteModalOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-slate-950/40 backdrop-blur-sm" x-transition>
            <div class="bg-white dark:bg-slate-900 rounded-3xl max-w-md w-full shadow-2xl border border-gray-100 dark:border-slate-800 overflow-hidden" @click.away="!isSubmitting && (bulkDeleteModalOpen = false)">
                <!-- Header -->
                <div class="px-6 py-4 bg-red-50 dark:bg-red-950/20 border-b border-red-100 dark:border-red-900/40 flex justify-between items-center">
                    <h3 class="font-bold text-red-950 dark:text-red-200 text-sm flex items-center gap-2">
                        <i data-lucide="alert-triangle" class="w-4.5 h-4.5 text-red-600 dark:text-red-400 animate-pulse"></i>
                        {{ __('Xác nhận xóa hàng loạt') }}
                    </h3>
                    <button type="button" @click="!isSubmitting && (bulkDeleteModalOpen = false)" class="text-red-400 hover:text-red-600 dark:text-red-450 dark:hover:text-red-300">
                        <i data-lucide="x" class="w-4 h-4"></i>
                    </button>
                </div>

                <!-- Body -->
                <div class="p-6 space-y-5">
                    <div class="p-4 bg-red-50/50 dark:bg-red-950/20 border border-red-100/80 dark:border-red-900/30 rounded-2xl text-xs text-red-800 dark:text-red-300 space-y-3">
                        <p class="font-bold text-red-900 dark:text-red-200">{{ __('Cảnh báo xóa dữ liệu hàng loạt:') }}</p>
                        <p class="leading-relaxed text-gray-700 dark:text-slate-350">
                            {{ __('Hành động này sẽ xóa vĩnh viễn') }} <strong class="text-red-600 dark:text-red-400 font-extrabold" x-text="selectedWithdrawals.length"></strong> {{ __('yêu cầu rút tiền được chọn khỏi hệ thống.') }}
                        </p>
                    </div>

                    <!-- Checklist tác động dữ liệu khi xóa hàng loạt -->
                    <div class="p-3 bg-red-50/40 dark:bg-red-950/10 border border-red-100/50 dark:border-red-900/20 rounded-2xl space-y-2 text-[10px] text-gray-600 dark:text-slate-350 font-medium">
                        <p class="font-bold text-red-800 dark:text-red-300 uppercase tracking-widest text-[9px] flex items-center gap-1">
                            <span class="w-1.5 h-1.5 rounded-full bg-red-500 shrink-0"></span>
                            {{ __('Tác động dữ liệu khi xóa:') }}
                        </p>
                        <ul class="space-y-1.5 pl-1">
                            <li class="flex items-start gap-1.5">
                                <i data-lucide="x-circle" class="w-3.5 h-3.5 text-red-500 shrink-0 mt-px"></i>
                                <span>{{ __('Xóa vĩnh viễn các bản ghi yêu cầu rút tiền được chọn khỏi CSDL') }}</span>
                            </li>
                            <li class="flex items-start gap-1.5 text-rose-700 dark:text-rose-400 font-semibold">
                                <i data-lucide="alert-circle" class="w-3.5 h-3.5 text-rose-500 shrink-0 mt-px"></i>
                                <span>{{ __('Hành động này chỉ xóa bản ghi và KHÔNG hoàn trả lại tiền vào ví cho thành viên.') }}</span>
                            </li>
                            <li class="flex items-start gap-1.5 text-amber-700 dark:text-amber-400">
                                <i data-lucide="alert-circle" class="w-3.5 h-3.5 text-amber-500 shrink-0 mt-px"></i>
                                <span>{{ __('Với các lệnh đã thanh toán, tổng tiền tích lũy đã rút (total_withdrawn) của thành viên sẽ bị giảm tương ứng.') }}</span>
                            </li>
                        </ul>
                    </div>

                    <!-- Checkbox xác nhận bắt buộc & nhập cụm từ xác thực -->
                    <div class="space-y-3 border-t border-gray-150 dark:border-slate-800/80 pt-3">
                        <label class="flex items-center gap-3 p-3 bg-red-50/50 dark:bg-red-950/20 border border-red-100 dark:border-red-900/40 rounded-2xl hover:bg-red-50 dark:hover:bg-red-950/30 transition-colors cursor-pointer group">
                            <input type="checkbox" x-model="confirmBulkDeleteCheckbox" class="w-4 h-4 text-red-600 border-gray-300 dark:border-slate-700 rounded focus:ring-red-500/20 dark:bg-slate-950 cursor-pointer">
                            <span class="text-xs font-bold text-gray-700 dark:text-slate-300 group-hover:text-red-600 dark:group-hover:text-red-400 transition-colors">
                                {{ __('Tôi xác nhận muốn xóa vĩnh viễn các yêu cầu rút tiền đã chọn') }}
                            </span>
                        </label>

                        <div class="space-y-1.5">
                            <label class="block text-[10px] font-bold text-gray-700 dark:text-slate-350 uppercase tracking-wider">
                                {{ __('Nhập cụm từ') }} <span class="text-red-600 dark:text-red-400 font-black select-all">XÓA HÀNG LOẠT</span> {{ __('để xác nhận xóa:') }}
                            </label>
                            <input type="text" x-model="confirmBulkText" placeholder="{{ __('Nhập XÓA HÀNG LOẠT...') }}" class="block w-full px-4 py-2 border border-gray-200 dark:border-slate-800 dark:bg-slate-950 dark:text-white rounded-xl text-xs font-semibold focus:outline-none focus:ring-2 focus:ring-red-500/20 focus:border-red-500 transition-all placeholder-gray-400 dark:placeholder-slate-500">
                        </div>
                    </div>

                    <!-- Nút hành động -->
                    <form @submit.prevent="submitBulkDelete()" class="flex justify-end gap-2 pt-2">
                        <button type="button" @click="bulkDeleteModalOpen = false" :disabled="isSubmitting" class="px-4 py-2.5 text-xs font-bold text-gray-700 dark:text-slate-350 bg-gray-100 dark:bg-slate-800 hover:bg-gray-200 dark:hover:bg-slate-700 rounded-xl transition-all disabled:opacity-50">{{ __('Hủy bỏ') }}</button>
                        <button type="submit" :disabled="confirmBulkText.toUpperCase() !== 'XÓA HÀNG LOẠT' || !confirmBulkDeleteCheckbox || isSubmitting" class="px-5 py-2.5 text-xs font-bold text-white bg-red-600 hover:bg-red-700 disabled:opacity-40 disabled:cursor-not-allowed rounded-xl transition-all shadow-md flex items-center justify-center min-w-[160px]">
                            <span x-show="!isSubmitting" class="flex items-center gap-1.5">
                                <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                {{ __('Đồng ý xóa hàng loạt') }}
                            </span>
                            <span x-show="isSubmitting" class="flex items-center gap-1.5">
                                <svg class="animate-spin -ml-1 mr-1.5 h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                {{ __('Đang xóa...') }}
                            </span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </template>

    <!-- MODAL TỪ CHỐI DUYỆT RÚT TIỀN (Có kèm điền lý do) -->
    <template x-teleport="body">
        <div x-show="rejectModalOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-slate-950/40 backdrop-blur-sm" x-transition>
            <div class="bg-white rounded-3xl max-w-md w-full shadow-2xl border border-gray-100 overflow-hidden" @click.away="rejectModalOpen = false">
                <div class="px-6 py-4 bg-gray-50 border-b border-gray-100 flex justify-between items-center">
                    <h3 class="font-bold text-gray-950 text-sm">{{ __('Từ chối yêu cầu rút tiền') }}</h3>
                    <button @click="rejectModalOpen = false" class="text-gray-400 hover:text-gray-600"><i data-lucide="x" class="w-4 h-4"></i></button>
                </div>
                
                <form :action="'/' + window.adminPrefix + '/withdrawals/' + activeWithdrawal.id + '/reject'" method="POST" class="p-6 space-y-4">
                    @csrf
                    
                    <div class="p-3 bg-red-50 border border-red-100 rounded-2xl text-[10px] text-red-800">
                        {{ __('Thành viên:') }} <strong x-text="activeWithdrawal.account_name"></strong> <br>
                        {{ __('Số tài khoản:') }} <span x-text="activeWithdrawal.account_number"></span> <br>
                        {{ __('Số tiền rút:') }} <strong x-text="formatCurrency(activeWithdrawal.amount)"></strong> <br>
                        {{ __('Phí rút:') }} <span x-text="formatCurrency(activeWithdrawal.fee || 0)"></span> <br>
                        {{ __('Thực nhận:') }} <strong class="text-shopee" x-text="formatCurrency(activeWithdrawal.real_amount || activeWithdrawal.amount)"></strong>
                    </div>

                    <!-- Ô điền lý do từ chối -->
                    <div>
                        <label for="notes" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1 font-semibold">{{ __('Lý do từ chối (Sẽ gửi thông báo cho user và hoàn tiền ví)') }}</label>
                        <textarea name="notes" id="notes" required placeholder="{{ __('Ví dụ: Sai số tài khoản ngân hàng, vui lòng kiểm tra lại...') }}" rows="3" class="block w-full px-4 py-2 border border-gray-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee"></textarea>
                    </div>

                    <div class="pt-2 flex justify-end gap-2 border-t border-gray-100">
                        <button type="button" @click="rejectModalOpen = false" class="px-4 py-2 text-xs font-semibold text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-xl transition-all">{{ __('Huỷ') }}</button>
                        <button type="submit" class="px-5 py-2 text-xs font-semibold text-white bg-red-600 hover:bg-red-700 rounded-xl transition-all shadow-md">{{ __('Từ chối & hoàn ví') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </template>

    <!-- MODAL XÁC NHẬN DUYỆT CHI -->
    <template x-teleport="body">
        <div x-show="approveModalOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-slate-950/40 backdrop-blur-sm" x-transition>
            <div class="bg-white dark:bg-slate-900 rounded-3xl max-w-md w-full shadow-2xl border border-gray-100 dark:border-slate-800 overflow-hidden" @click.away="!loadingApprove && (approveModalOpen = false)">
                <div class="px-6 py-4 bg-gray-50 dark:bg-slate-800/50 border-b border-gray-100 dark:border-slate-800 flex justify-between items-center">
                    <h3 class="font-bold text-gray-950 dark:text-white text-sm flex items-center gap-1.5">
                        <i data-lucide="check-circle-2" class="w-4 h-4 text-green-600"></i>
                        {{ __('Xác nhận duyệt chi') }}
                    </h3>
                    <button type="button" @click="!loadingApprove && (approveModalOpen = false)" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200" :disabled="loadingApprove">
                        <i data-lucide="x" class="w-4 h-4"></i>
                    </button>
                </div>
                
                <form @submit.prevent="submitApprove" class="p-6 space-y-4">
                    @csrf
                    
                    <div class="text-center space-y-2">
                        <p class="text-xs text-gray-600 dark:text-gray-400">
                            {{ __('Bạn có chắc chắn đã chuyển tiền và phê duyệt giao dịch rút này?') }}
                        </p>
                        <p class="text-[10px] text-red-500 font-medium">
                            {{ __('Hành động này không thể hoàn tác sau khi thực hiện.') }}
                        </p>
                    </div>

                    <div class="p-4 bg-green-50/50 dark:bg-green-950/20 border border-green-100/50 dark:border-green-900/30 rounded-2xl text-xs space-y-1.5 text-gray-700 dark:text-gray-300">
                        <div>{{ __('Thành viên:') }} <strong class="text-slate-900 dark:text-white" x-text="activeWithdrawal.account_name"></strong></div>
                        <div>{{ __('Số tài khoản:') }} <span class="font-mono" x-text="activeWithdrawal.account_number"></span> <span x-show="activeWithdrawal.bank_name" x-text="'(' + activeWithdrawal.bank_name + ')'"></span></div>
                        <div class="pt-1.5 border-t border-green-100/60 dark:border-green-900/40 flex justify-between items-center">
                            <span>{{ __('Số tiền thực nhận:') }}</span>
                            <strong class="text-green-600 dark:text-green-400 text-sm font-extrabold" x-text="formatCurrency(activeWithdrawal.real_amount || activeWithdrawal.amount)"></strong>
                        </div>
                    </div>

                    <div class="pt-2 flex justify-end gap-2 border-t border-gray-100 dark:border-slate-800">
                        <button type="button" @click="approveModalOpen = false" class="px-4 py-2 text-xs font-semibold text-gray-700 dark:text-slate-350 bg-gray-100 dark:bg-slate-800 hover:bg-gray-200 dark:hover:bg-slate-700 rounded-xl transition-all border border-transparent dark:border-slate-700/50" :disabled="loadingApprove">{{ __('Huỷ') }}</button>
                        <button type="submit" 
                                :disabled="loadingApprove"
                                class="px-5 py-2 text-xs font-semibold text-white bg-green-600 hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed rounded-xl transition-all shadow-md flex items-center gap-1.5">
                            <template x-if="loadingApprove">
                                <span class="w-3 h-3 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
                            </template>
                            <template x-if="!loadingApprove">
                                <i data-lucide="check" class="w-3.5 h-3.5"></i>
                            </template>
                            <span x-text="loadingApprove ? '{{ __('Đang xử lý...') }}' : '{{ __('Xác nhận duyệt') }}'"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </template>

    <!-- MODAL XÁC NHẬN XÓA YÊU CẦU RÚT TIỀN -->
    <template x-teleport="body">
        <div x-show="deleteModalOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-slate-950/40 backdrop-blur-sm" x-transition>
            <div class="bg-white dark:bg-slate-900 rounded-3xl max-w-md w-full shadow-2xl border border-gray-100 dark:border-slate-800 overflow-hidden" @click.away="deleteModalOpen = false" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95">
                <!-- Header -->
                <div class="px-6 py-4 bg-red-50 dark:bg-red-950/20 border-b border-red-100 dark:border-red-900/40 flex justify-between items-center">
                    <h3 class="font-bold text-red-950 dark:text-red-200 text-sm flex items-center gap-2">
                        <i data-lucide="alert-triangle" class="w-4.5 h-4.5 text-red-600 dark:text-red-400 animate-pulse"></i>
                        {{ __('Xác nhận xóa yêu cầu rút tiền') }}
                    </h3>
                    <button @click="deleteModalOpen = false" class="text-red-400 hover:text-red-600 dark:text-red-450 dark:hover:text-red-300">
                        <i data-lucide="x" class="w-4 h-4"></i>
                    </button>
                </div>
                
                <!-- Body -->
                <div class="p-6 space-y-5">
                    <!-- Card thông tin yêu cầu rút tiền -->
                    <div class="p-4 bg-gray-50 dark:bg-slate-950/40 border border-gray-100 dark:border-slate-800/80 rounded-2xl space-y-2 text-xs">
                        <div class="flex justify-between items-center">
                            <span class="text-gray-400 dark:text-gray-500 font-bold uppercase tracking-wider text-[10px]">{{ __('Mã lệnh rút') }}</span>
                            <span class="font-mono font-bold text-gray-900 dark:text-white" x-text="activeDeleteWithdrawal.code || ('HTS W' + activeDeleteWithdrawal.id)"></span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-400 dark:text-gray-500 font-bold uppercase tracking-wider text-[10px]">{{ __('Thành viên') }}</span>
                            <span class="font-semibold text-gray-800 dark:text-slate-200" x-text="activeDeleteWithdrawal.user?.name || activeDeleteWithdrawal.account_name"></span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-400 dark:text-gray-500 font-bold uppercase tracking-wider text-[10px]">{{ __('Số tiền rút') }}</span>
                            <strong class="text-shopee font-extrabold text-sm" x-text="formatCurrency(activeDeleteWithdrawal.amount || 0)"></strong>
                        </div>
                        <div class="flex justify-between items-center pt-1.5 border-t border-gray-100 dark:border-slate-800">
                            <span class="text-gray-400 dark:text-gray-500 font-bold uppercase tracking-wider text-[10px]">{{ __('Trạng thái hiện tại') }}</span>
                            <span class="font-bold text-xs" 
                                  :class="{
                                      'text-amber-600 dark:text-amber-400': activeDeleteWithdrawal.status === 'pending',
                                      'text-emerald-600 dark:text-emerald-400': activeDeleteWithdrawal.status === 'approved',
                                      'text-rose-600 dark:text-rose-400': activeDeleteWithdrawal.status === 'rejected'
                                  }"
                                  x-text="activeDeleteWithdrawal.status === 'pending' ? '{{ __('Chờ duyệt') }}' : (activeDeleteWithdrawal.status === 'approved' ? '{{ __('Đã thanh toán') }}' : '{{ __('Bị từ chối') }}')">
                            </span>
                        </div>
                    </div>

                    <!-- Checklist những gì sẽ xảy ra khi xác nhận xóa -->
                    <div class="p-4 bg-red-50/40 dark:bg-red-950/10 border border-red-100/50 dark:border-red-900/20 rounded-2xl space-y-3">
                        <div class="flex items-center gap-1.5">
                            <span class="w-1.5 h-1.5 rounded-full bg-red-500 shrink-0"></span>
                            <span class="text-[9px] font-black text-red-800 dark:text-red-300 uppercase tracking-widest">{{ __('Tác động dữ liệu khi xóa:') }}</span>
                        </div>
                        <ul class="space-y-2 text-[10px] text-gray-600 dark:text-slate-350 font-medium">
                            <li class="flex items-start gap-2">
                                <i data-lucide="x-circle" class="w-3.5 h-3.5 text-red-500 dark:text-red-400 shrink-0 mt-0.5"></i>
                                <span>{{ __('Xóa vĩnh viễn bản ghi yêu cầu rút tiền này khỏi hệ thống') }}</span>
                            </li>
                            <li class="flex items-start gap-2 text-rose-700 dark:text-rose-400 font-semibold">
                                <i data-lucide="alert-circle" class="w-3.5 h-3.5 text-rose-500 shrink-0 mt-0.5"></i>
                                <span>{{ __('Hành động này chỉ xóa bản ghi và KHÔNG hoàn trả lại tiền vào ví cho thành viên.') }}</span>
                            </li>
                            <template x-if="activeDeleteWithdrawal.status === 'approved'">
                                <li class="flex items-start gap-2 text-amber-700 dark:text-amber-400">
                                    <i data-lucide="alert-circle" class="w-3.5 h-3.5 text-amber-500 shrink-0 mt-0.5"></i>
                                    <span>{{ __('Giảm bớt tổng tiền tích lũy đã rút (total_withdrawn) của thành viên.') }}</span>
                                </li>
                            </template>
                        </ul>
                    </div>

                    <!-- Checkbox Xác Nhận Bắt Buộc -->
                    <div class="pt-2 border-t border-gray-150 dark:border-slate-800/80">
                        <label class="flex items-center gap-3 p-3.5 bg-red-50/50 dark:bg-red-950/20 border border-red-100 dark:border-red-900/40 rounded-2xl hover:bg-red-50 dark:hover:bg-red-950/30 transition-colors cursor-pointer group">
                            <input type="checkbox" x-model="confirmDeleteCheckbox" class="w-4 h-4 text-red-600 border-gray-300 dark:border-slate-700 rounded focus:ring-red-500/20 dark:bg-slate-950 cursor-pointer">
                            <span class="text-xs font-bold text-gray-700 dark:text-slate-300 group-hover:text-red-600 dark:group-hover:text-red-400 transition-colors">
                                {{ __('Tôi xác nhận muốn xóa vĩnh viễn yêu cầu rút tiền này') }}
                            </span>
                        </label>
                    </div>

                    <!-- Footer Action Buttons -->
                    <form :action="'/' + window.adminPrefix + '/withdrawals/' + activeDeleteWithdrawal.id" method="POST" class="flex justify-end gap-2 pt-2">
                        @csrf
                        @method('DELETE')
                        <button type="button" @click="deleteModalOpen = false" class="px-4 py-2.5 text-xs font-bold text-gray-700 dark:text-slate-350 bg-gray-100 dark:bg-slate-800 hover:bg-gray-200 dark:hover:bg-slate-700 rounded-xl transition-all">{{ __('Hủy bỏ') }}</button>
                        <button type="submit" :disabled="!confirmDeleteCheckbox" class="px-5 py-2.5 text-xs font-bold text-white bg-red-600 hover:bg-red-700 disabled:opacity-40 disabled:cursor-not-allowed rounded-xl transition-all shadow-md flex items-center gap-1.5">
                            <i data-lucide="trash-2" class="w-3.5 h-3.5 inline"></i>
                            {{ __('Đồng ý xóa') }}
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </template>

    <!-- MODAL CHI TIẾT YÊU CẦU RÚT TIỀN, LỊCH SỬ DÒNG TIỀN & VIETQR THANH TOÁN NHANH -->
    <template x-teleport="body">
        <div x-show="detailsModalOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-slate-950/40 backdrop-blur-sm" x-transition>
            <div class="bg-white dark:bg-slate-900 rounded-3xl max-w-4xl w-full max-h-[90vh] flex flex-col shadow-2xl border border-gray-100 dark:border-slate-800 overflow-hidden" @click.away="detailsModalOpen = false" x-transition>
                <div class="px-6 py-4 bg-gray-50 dark:bg-slate-800/50 border-b border-gray-100 dark:border-slate-800 flex justify-between items-center">
                    <h3 class="font-bold text-gray-900 dark:text-white text-sm flex items-center gap-2">
                        <i data-lucide="eye" class="w-4 h-4 text-shopee"></i>
                        {{ __('Chi Tiết Yêu Cầu Rút Tiền & Lịch Sử Dòng Tiền') }}
                    </h3>
                    <button @click="detailsModalOpen = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                        <i data-lucide="x" class="w-4 h-4"></i>
                    </button>
                </div>
                
                <div class="p-6 space-y-6 max-h-[80vh] overflow-y-auto text-gray-600 dark:text-gray-300">
                    <!-- Hiển thị hiệu ứng tải dữ liệu -->
                    <div x-show="loadingDetails" class="py-12 flex flex-col items-center justify-center gap-2">
                        <div class="w-8 h-8 border-4 border-shopee border-t-transparent rounded-full animate-spin"></div>
                        <span class="text-xs text-gray-400 dark:text-gray-550 font-medium">{{ __('Đang tải dữ liệu tài chính...') }}</span>
                    </div>

                    <!-- Giao diện hiển thị chi tiết khi tải xong -->
                    <div x-show="!loadingDetails && detailedWithdrawal.id" class="grid grid-cols-1 md:grid-cols-12 gap-6" x-transition>
                        
                        <!-- Cột trái: Thông tin tài chính & Biến động ví -->
                        <div :class="detailedWithdrawal.payment_method === 'momo' ? 'md:col-span-12' : 'md:col-span-7'" class="space-y-6">
                            <!-- Grid hiển thị thông tin thành viên và ngân hàng nhận -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <!-- Box thông tin thành viên -->
                                <div class="bg-slate-50 dark:bg-slate-800/30 p-4 rounded-2xl border border-slate-100 dark:border-slate-800/50 space-y-2">
                                    <div class="flex justify-between items-center border-b border-slate-200/60 dark:border-slate-800 pb-1.5">
                                        <h4 class="text-xs font-bold text-slate-850 dark:text-slate-200 uppercase tracking-wide">{{ __('Thông tin thành viên') }}</h4>
                                        <div class="flex items-center gap-2">
                                            <!-- Nút Xem đơn hoàn tiền của user để đối soát kiểm tra nhanh lịch sử mua sắm -->
                                            <template x-if="detailedWithdrawal.user?.email">
                                                <a :href="'/' + window.adminPrefix + '/cashback?user_search=' + encodeURIComponent(detailedWithdrawal.user.email)" target="_blank" class="text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300 text-[10px] font-bold flex items-center gap-1 transition-all active:scale-95" title="{{ __('Xem đơn hoàn tiền của user') }}">
                                                    <i data-lucide="shopping-bag" class="w-3 h-3"></i>
                                                    {{ __('Đơn hàng') }}
                                                </a>
                                            </template>

                                            <!-- Nút chỉnh sửa chi tiết tài khoản user -->
                                            <template x-if="detailedWithdrawal.user?.id">
                                                <a :href="'/' + window.adminPrefix + '/users/' + detailedWithdrawal.user.id + '/edit'" target="_blank" class="text-shopee hover:text-shopee-dark dark:text-orange-400 dark:hover:text-orange-300 text-[10px] font-bold flex items-center gap-1 transition-all active:scale-95" title="{{ __('Chỉnh sửa thành viên') }}">
                                                    <i data-lucide="edit-3" class="w-3 h-3"></i>
                                                    {{ __('Chỉnh sửa') }}
                                                </a>
                                            </template>
                                        </div>
                                    </div>
                                    <div class="text-[11px] space-y-1 text-slate-600 dark:text-gray-455">
                                        <p>{{ __('Họ tên:') }} <strong class="text-slate-800 dark:text-white" x-text="detailedWithdrawal.user?.name"></strong></p>
                                        <p>{{ __('Email:') }} <span class="font-mono text-slate-700 dark:text-slate-300" x-text="detailedWithdrawal.user?.email"></span></p>
                                        <p>{{ __('Số dư ví hiện tại:') }} <strong class="text-green-600 dark:text-green-400 text-xs" x-text="formatCurrency(detailedWithdrawal.user?.balance)"></strong></p>
                                    </div>
                                </div>

                                <!-- Box thông tin tài khoản nhận tiền -->
                                <div class="bg-orange-50/40 dark:bg-orange-950/10 p-4 rounded-2xl border border-orange-100/50 dark:border-orange-900/30 space-y-2">
                                    <h4 class="text-xs font-bold text-orange-850 dark:text-orange-400 uppercase tracking-wide border-b border-orange-100 dark:border-orange-900/30 pb-1.5">{{ __('Thông tin nhận tiền') }}</h4>
                                    <div class="text-[11px] space-y-1 text-slate-600 dark:text-gray-455">
                                        <p>{{ __('Ngân hàng/Ví:') }} <strong class="text-slate-800 dark:text-white" x-text="detailedWithdrawal.bank_name"></strong></p>
                                        <p>{{ __('Số tài khoản:') }} <strong class="text-slate-800 dark:text-white font-mono" x-text="detailedWithdrawal.account_number"></strong></p>
                                        <p>{{ __('Chủ tài khoản:') }} <strong class="text-slate-800 dark:text-white uppercase" x-text="detailedWithdrawal.account_name"></strong></p>
                                    </div>
                                </div>
                            </div>

                            <!-- Box phân tích dòng tiền giao dịch -->
                            <div class="bg-slate-50/50 dark:bg-slate-800/20 p-4 rounded-2xl border border-slate-100 dark:border-slate-800 grid grid-cols-2 sm:grid-cols-4 gap-4 text-center">
                                <div>
                                    <span class="text-[10px] text-gray-400 dark:text-gray-550 block uppercase font-bold tracking-wider mb-0.5">{{ __('Số tiền rút') }}</span>
                                    <strong class="text-sm text-slate-800 dark:text-white" x-text="formatCurrency(detailedWithdrawal.amount)"></strong>
                                </div>
                                <div>
                                    <span class="text-[10px] text-gray-400 dark:text-gray-550 block uppercase font-bold tracking-wider mb-0.5">{{ __('Phí rút tiền') }}</span>
                                    <span class="text-sm text-slate-600 dark:text-slate-300" x-text="formatCurrency(detailedWithdrawal.fee)"></span>
                                </div>
                                <div>
                                    <span class="text-[10px] text-gray-400 dark:text-gray-550 block uppercase font-bold tracking-wider mb-0.5">{{ __('Thực nhận') }}</span>
                                    <strong class="text-sm text-shopee" x-text="formatCurrency(detailedWithdrawal.real_amount)"></strong>
                                </div>
                                <div>
                                    <span class="text-[10px] text-gray-400 dark:text-gray-550 block uppercase font-bold tracking-wider mb-0.5">{{ __('Trạng thái') }}</span>
                                    <div>
                                        <template x-if="detailedWithdrawal.status === 'pending'">
                                            <span class="inline-flex px-2 py-0.5 rounded-full text-[9px] font-bold bg-yellow-50 dark:bg-yellow-950/30 text-yellow-600 dark:text-yellow-400 border border-yellow-100 dark:border-yellow-900/50">{{ __('Chờ duyệt') }}</span>
                                        </template>
                                        <template x-if="detailedWithdrawal.status === 'approved'">
                                            <span class="inline-flex px-2 py-0.5 rounded-full text-[9px] font-bold bg-green-50 dark:bg-green-950/30 text-green-600 dark:text-green-400 border border-green-100 dark:border-green-900/50" x-text="'Thành công (' + detailedWithdrawal.processed_at + ')'"></span>
                                        </template>
                                        <template x-if="detailedWithdrawal.status === 'rejected'">
                                            <div class="flex flex-col items-center gap-0.5">
                                                <span class="inline-flex px-2 py-0.5 rounded-full text-[9px] font-bold bg-red-50 dark:bg-red-950/30 text-red-600 dark:text-red-400 border border-red-100 dark:border-red-900/50">{{ __('Từ chối') }}</span>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </div>

                            <!-- Hiển thị lý do từ chối nếu có -->
                            <template x-if="detailedWithdrawal.status === 'rejected' && detailedWithdrawal.notes">
                                <div class="p-3 bg-red-50 dark:bg-red-950/20 border border-red-100/50 dark:border-red-900/30 rounded-2xl text-xs text-red-800 dark:text-red-400">
                                    <strong>{{ __('Lý do từ chối:') }}</strong> <span x-text="detailedWithdrawal.notes"></span>
                                </div>
                            </template>

                            <!-- Danh sách 15 biến động số dư gần đây của user -->
                            <div class="space-y-3">
                                <h4 class="text-xs font-bold text-slate-800 dark:text-white uppercase tracking-wider flex items-center gap-1.5 border-b border-slate-100 dark:border-slate-800 pb-2">
                                    <i data-lucide="history" class="w-3.5 h-3.5 text-gray-400"></i>
                                    {{ __('Lịch sử biến động tài chính gần đây (15 giao dịch)') }}
                                </h4>
                                
                                <div class="space-y-2 max-h-[220px] overflow-y-auto pr-1">
                                    <template x-if="financialLogs.length === 0">
                                        <p class="text-center text-xs text-gray-400 py-4">{{ __('Chưa có giao dịch biến động số dư nào.') }}</p>
                                    </template>

                                    <template x-for="log in financialLogs" :key="log.type + '_' + log.id + '_' + log.created_at">
                                        <div class="p-3 bg-slate-50/50 dark:bg-slate-950/40 hover:bg-slate-100/50 dark:hover:bg-slate-900/50 rounded-2xl border border-slate-100/60 dark:border-slate-800/50 flex items-center justify-between text-xs transition-all">
                                            <div class="space-y-1">
                                                <div class="font-semibold text-slate-850 dark:text-slate-200" x-text="log.description"></div>
                                                <div class="flex items-center gap-1.5 text-[10px] text-gray-500 dark:text-gray-400 font-medium">
                                                    <span class="bg-gray-100 dark:bg-slate-800/80 px-1.5 py-0.5 rounded text-gray-600 dark:text-slate-300 border border-gray-200/50 dark:border-slate-700/40 font-mono" x-text="new Intl.NumberFormat('vi-VN', { maximumFractionDigits: 0 }).format(log.amount_before) + 'đ'"></span>
                                                    <span class="text-gray-400 dark:text-gray-550">&rarr;</span>
                                                    <span class="bg-gray-100 dark:bg-slate-800/80 px-1.5 py-0.5 rounded text-gray-600 dark:text-slate-300 border border-gray-200/50 dark:border-slate-700/40 font-mono" x-text="new Intl.NumberFormat('vi-VN', { maximumFractionDigits: 0 }).format(log.amount_after) + 'đ'"></span>
                                                </div>
                                                <div class="text-[9px] text-gray-400 dark:text-gray-550 font-mono" x-text="log.created_at"></div>
                                            </div>
                                            <div class="font-extrabold text-sm shrink-0 pl-4"
                                                 :class="log.amount_change > 0 ? 'text-green-600 dark:text-green-400' : 'text-red-500 dark:text-red-400'">
                                                <span x-text="(log.amount_change > 0 ? '+' : '') + new Intl.NumberFormat('vi-VN', { maximumFractionDigits: 0 }).format(log.amount_change) + 'đ'"></span>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </div>

                            <!-- Footer của cột trái chứa các nút hành động -->
                            <div class="pt-4 border-t border-gray-100 dark:border-slate-800 flex justify-end gap-2">
                                <button type="button" @click="detailsModalOpen = false; openDeleteModal(detailedWithdrawal)" class="px-4 py-2 text-xs font-semibold text-white bg-red-600 hover:bg-red-700 rounded-xl transition-all shadow-md flex items-center gap-1">
                                    <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                    {{ __('Xóa yêu cầu') }}
                                </button>
                                <button type="button" @click="detailsModalOpen = false" class="px-4 py-2 text-xs font-semibold text-gray-700 dark:text-slate-350 bg-gray-100 dark:bg-slate-800 hover:bg-gray-200 dark:hover:bg-slate-700 rounded-xl transition-all border border-transparent dark:border-slate-700/50">{{ __('Đóng') }}</button>
                                
                                <template x-if="detailedWithdrawal.status === 'pending'">
                                    <div class="flex gap-2">
                                        <button type="button" @click="openApproveModal(detailedWithdrawal)" class="px-4 py-2 text-xs font-semibold text-white bg-green-600 hover:bg-green-700 rounded-xl transition-all shadow-md">
                                            {{ __('Duyệt chi') }}
                                        </button>
                                        <button type="button" @click="openRejectModal(detailedWithdrawal)" class="px-4 py-2 text-xs font-semibold text-white bg-red-600 hover:bg-red-700 rounded-xl transition-all shadow-md">
                                            {{ __('Từ chối') }}
                                        </button>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <!-- Cột phải: VietQR thanh toán nhanh cho Admin -->
                        <div x-show="detailedWithdrawal.payment_method !== 'momo'" class="md:col-span-5 bg-slate-50 dark:bg-slate-850 p-5 rounded-2xl border border-slate-100 dark:border-slate-800/80 flex flex-col items-center space-y-4 relative overflow-hidden h-fit">
                            <h4 class="text-xs font-bold text-slate-800 dark:text-slate-200 uppercase tracking-wide border-b border-slate-200/60 dark:border-slate-800 pb-1.5 w-full text-center">
                                {{ __('Mã QR Thanh Toán (VietQR)') }}
                            </h4>
                            
                            <!-- Lớp phủ an toàn: Chỉ hiển thị QR khi yêu cầu rút tiền ở trạng thái chờ duyệt (Pending) -->
                            <div x-show="detailedWithdrawal.status !== 'pending'" class="absolute inset-0 bg-white/95 dark:bg-slate-900/95 backdrop-blur-[2px] flex flex-col items-center justify-center p-4 text-center z-10 transition-all">
                                <span class="p-2.5 bg-slate-100 dark:bg-slate-800 rounded-full mb-2">
                                    <i data-lucide="shield-alert" class="w-6 h-6 text-shopee animate-pulse"></i>
                                </span>
                                <h5 class="text-xs font-bold text-slate-850 dark:text-slate-200">
                                    <template x-if="detailedWithdrawal.status === 'approved'">
                                        <span>{{ __('Giao dịch đã duyệt') }}</span>
                                    </template>
                                    <template x-if="detailedWithdrawal.status === 'rejected'">
                                        <span>{{ __('Giao dịch đã từ chối') }}</span>
                                    </template>
                                </h5>
                                <p class="text-[10px] text-gray-400 dark:text-gray-555 mt-1 max-w-[180px] mx-auto leading-relaxed">
                                    {{ __('QR thanh toán tự động bị vô hiệu hóa để tránh chuyển khoản nhầm lẫn.') }}
                                </p>
                            </div>
                            
                            <!-- Hiển thị mã QR Code -->
                            <div class="bg-white p-3 rounded-2xl shadow-sm border border-gray-100 flex items-center justify-center w-full max-w-[200px] aspect-square relative group">
                                <img :src="getVietQrUrl()" alt="VietQR Code" class="w-full h-full object-contain rounded-lg">
                            </div>
                            
                            <!-- Hướng dẫn & Nút tải QR -->
                            <div class="text-center space-y-1">
                                <span class="text-[10px] text-gray-450 dark:text-gray-400 font-medium block leading-relaxed">
                                    {{ __('Mở ứng dụng Ngân hàng hoặc Ví điện tử quét QR để chuyển khoản nhanh với thông tin điền tự động.') }}
                                </span>
                                <div class="flex items-center justify-center gap-2 mt-2">
                                    <a :href="getVietQrUrl()" download="VietQR_Withdraw.jpg" target="_blank" class="px-3 py-1.5 bg-white dark:bg-slate-900 hover:bg-slate-100 dark:hover:bg-slate-800 text-[10px] font-bold text-slate-750 dark:text-slate-200 rounded-lg transition-all flex items-center gap-1 border border-gray-200/60 dark:border-slate-700/80 shadow-sm">
                                        <i data-lucide="download" class="w-3.5 h-3.5 text-gray-500"></i>
                                        {{ __('Tải mã QR') }}
                                    </a>
                                </div>
                            </div>

                            <!-- Panel sao chép nhanh thông tin chuyển khoản -->
                            <div class="w-full pt-3 border-t border-slate-200/60 dark:border-slate-800 space-y-2 text-[10px]">
                                <!-- Tài khoản nhận -->
                                <div class="flex justify-between items-center bg-white dark:bg-slate-900 p-2.5 rounded-xl border border-slate-100 dark:border-slate-800/80">
                                    <div class="truncate pr-2">
                                        <span class="text-gray-400 dark:text-gray-550 block text-[8px] uppercase tracking-wider font-bold mb-0.5">{{ __('Tài khoản nhận') }}</span>
                                        <strong class="text-slate-850 dark:text-slate-100 font-mono text-xs" x-text="detailedWithdrawal.account_number"></strong>
                                        <span class="text-gray-400 dark:text-gray-550 font-medium block text-[9px] mt-0.5" x-text="detailedWithdrawal.bank_name"></span>
                                    </div>
                                    <button @click="copyText(detailedWithdrawal.account_number)" class="p-2 bg-slate-50 dark:bg-slate-800 hover:bg-slate-100 dark:hover:bg-slate-700 text-slate-550 dark:text-slate-300 rounded-lg transition-all active:scale-95 shrink-0 border border-slate-150/40 dark:border-slate-700/30" title="Sao chép số tài khoản">
                                        <i data-lucide="copy" class="w-3.5 h-3.5"></i>
                                    </button>
                                </div>

                                <!-- Số tiền chuyển -->
                                <div class="flex justify-between items-center bg-white dark:bg-slate-900 p-2.5 rounded-xl border border-slate-100 dark:border-slate-800/80">
                                    <div>
                                        <span class="text-gray-400 dark:text-gray-550 block text-[8px] uppercase tracking-wider font-bold mb-0.5">{{ __('Số tiền chuyển') }}</span>
                                        <strong class="text-shopee text-xs font-extrabold" x-text="formatCurrency(detailedWithdrawal.real_amount)"></strong>
                                    </div>
                                    <button @click="copyText(detailedWithdrawal.real_amount)" class="p-2 bg-slate-50 dark:bg-slate-800 hover:bg-slate-100 dark:hover:bg-slate-700 text-slate-550 dark:text-slate-300 rounded-lg transition-all active:scale-95 shrink-0 border border-slate-150/40 dark:border-slate-700/30" title="Sao chép số tiền">
                                        <i data-lucide="copy" class="w-3.5 h-3.5"></i>
                                    </button>
                                </div>

                                <!-- Nội dung chuyển khoản -->
                                <div class="flex justify-between items-center bg-white dark:bg-slate-900 p-2.5 rounded-xl border border-slate-100 dark:border-slate-800/80">
                                    <div class="truncate pr-2">
                                        <span class="text-gray-400 dark:text-gray-550 block text-[8px] uppercase tracking-wider font-bold mb-0.5">{{ __('Nội dung ck') }}</span>
                                        <strong class="text-slate-850 dark:text-slate-100 font-mono text-xs" x-text="detailedWithdrawal.code || `HTS W${detailedWithdrawal.id}`"></strong>
                                    </div>
                                    <button @click="copyText(detailedWithdrawal.code || `HTS W${detailedWithdrawal.id}`)" class="p-2 bg-slate-50 dark:bg-slate-800 hover:bg-slate-100 dark:hover:bg-slate-700 text-slate-550 dark:text-slate-300 rounded-lg transition-all active:scale-95 shrink-0 border border-slate-150/40 dark:border-slate-700/30" title="Sao chép nội dung">
                                        <i data-lucide="copy" class="w-3.5 h-3.5"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </template>

    <!-- MODAL: THỐNG KÊ YÊU CẦU RÚT TIỀN (Chart.js) -->
    <template x-teleport="body">
        <div x-show="withdrawStatsModalOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-slate-950/40 backdrop-blur-sm" x-transition>
            <div class="bg-white dark:bg-slate-900 rounded-3xl max-w-4xl w-full shadow-2xl border border-gray-100 dark:border-slate-800 overflow-hidden" 
                 @click.away="withdrawStatsModalOpen = false"
                 x-transition:enter="transition ease-out duration-200" 
                 x-transition:enter-start="opacity-0 scale-95" 
                 x-transition:enter-end="opacity-100 scale-100" 
                 x-transition:leave="transition ease-in duration-150" 
                 x-transition:leave-start="opacity-100 scale-100" 
                 x-transition:leave-end="opacity-0 scale-95">
                <!-- Header -->
                <div class="px-6 py-4 bg-gradient-to-r from-indigo-50 to-purple-50 dark:from-indigo-950/30 dark:to-purple-950/30 border-b border-gray-100 dark:border-slate-800 flex justify-between items-center">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-indigo-100 dark:bg-indigo-900/40 rounded-xl">
                            <i data-lucide="bar-chart-3" class="w-5 h-5 text-indigo-600 dark:text-indigo-400"></i>
                        </div>
                        <div>
                            <h3 class="font-bold text-gray-900 dark:text-slate-200 text-sm">{{ __('Thống kê rút tiền') }}</h3>
                            <p class="text-[10px] text-gray-500 dark:text-slate-455 font-medium">{{ __('Biểu đồ tổng tiền đã chi trả & Top thành viên rút nhiều nhất') }}</p>
                        </div>
                    </div>
                    <button @click="withdrawStatsModalOpen = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-slate-350 transition-colors">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>

                <!-- Body -->
                <div class="p-6 space-y-6">
                    <!-- Hàng điều khiển: Tabs khoảng thời gian -->
                    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 border-b border-gray-150 dark:border-slate-800 pb-4">
                        <div class="flex items-center">
                            <span class="text-xs text-gray-400 dark:text-slate-500 font-bold uppercase tracking-wider">{{ __('Khoảng thời gian') }}</span>
                            <div class="inline-flex bg-gray-100 dark:bg-slate-800 rounded-xl p-1 gap-1 ml-3">
                                <button @click="loadWithdrawStats('week')" 
                                        class="px-3 py-1.5 text-[11px] font-bold rounded-lg transition-all"
                                        :class="wStatsPeriod === 'week' ? 'bg-white dark:bg-slate-700 text-indigo-600 dark:text-indigo-400 shadow-sm' : 'text-gray-500 dark:text-slate-400 hover:text-gray-700'">
                                    {{ __('Tuần') }}
                                </button>
                                <button @click="loadWithdrawStats('month')" 
                                        class="px-3 py-1.5 text-[11px] font-bold rounded-lg transition-all"
                                        :class="wStatsPeriod === 'month' ? 'bg-white dark:bg-slate-700 text-indigo-600 dark:text-indigo-400 shadow-sm' : 'text-gray-500 dark:text-slate-400 hover:text-gray-700'">
                                    {{ __('Tháng') }}
                                </button>
                                <button @click="loadWithdrawStats('year')" 
                                        class="px-3 py-1.5 text-[11px] font-bold rounded-lg transition-all"
                                        :class="wStatsPeriod === 'year' ? 'bg-white dark:bg-slate-700 text-indigo-600 dark:text-indigo-400 shadow-sm' : 'text-gray-500 dark:text-slate-400 hover:text-gray-700'">
                                    {{ __('Năm') }}
                                </button>
                            </div>
                        </div>

                        <!-- Cards tổng hợp nhanh -->
                        <div class="flex items-center gap-4">
                            <div class="px-4 py-2 bg-green-50/50 dark:bg-green-950/20 border border-green-100/50 dark:border-green-900/30 rounded-2xl flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg bg-green-100 dark:bg-green-900/40 flex items-center justify-center">
                                    <i data-lucide="check-circle-2" class="w-4 h-4 text-green-600 dark:text-green-400"></i>
                                </div>
                                <div>
                                    <p class="text-[8px] font-bold text-gray-400 dark:text-slate-500 uppercase tracking-widest">{{ __('Đã chi trả') }}</p>
                                    <p class="text-xs font-black text-gray-900 dark:text-white" x-text="formatCurrency(wStatsTotals.approved)">0đ</p>
                                </div>
                            </div>

                            <div class="px-4 py-2 bg-yellow-50/50 dark:bg-yellow-950/20 border border-yellow-100/50 dark:border-yellow-900/30 rounded-2xl flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg bg-yellow-100 dark:bg-yellow-900/40 flex items-center justify-center">
                                    <i data-lucide="help-circle" class="w-4 h-4 text-yellow-600 dark:text-yellow-400"></i>
                                </div>
                                <div>
                                    <p class="text-[8px] font-bold text-gray-400 dark:text-slate-500 uppercase tracking-widest">{{ __('Đang chờ duyệt') }}</p>
                                    <p class="text-xs font-black text-gray-900 dark:text-white" x-text="formatCurrency(wStatsTotals.pending)">0đ</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Nội dung chính: Cột Trái Biểu đồ | Cột Phải Top Thành viên -->
                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                        <!-- Biểu đồ (Chiếm 7 cột) -->
                        <div class="lg:col-span-7 space-y-3">
                            <h4 class="font-bold text-gray-900 dark:text-white uppercase tracking-wider text-[10px] flex items-center gap-1.5">
                                <i data-lucide="activity" class="w-3.5 h-3.5 text-indigo-500"></i>
                                {{ __('Biểu đồ tổng tiền đã thanh toán') }}
                            </h4>
                            <div class="relative bg-gray-50/50 dark:bg-slate-950/30 rounded-2xl p-4 border border-gray-100 dark:border-slate-800/50" style="height: 300px;">
                                <div x-show="wStatsLoading" class="absolute inset-0 flex items-center justify-center bg-white/60 dark:bg-slate-900/60 rounded-2xl z-10">
                                    <svg class="animate-spin h-8 w-8 text-indigo-500" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                </div>
                                <canvas id="withdrawStatsChart" height="270"></canvas>
                            </div>
                        </div>

                        <!-- Top thành viên rút nhiều nhất (Chiếm 5 cột) -->
                        <div class="lg:col-span-5 space-y-3">
                            <h4 class="font-bold text-gray-900 dark:text-white uppercase tracking-wider text-[10px] flex items-center gap-1.5">
                                <i data-lucide="award" class="w-3.5 h-3.5 text-amber-500"></i>
                                {{ __('Top thành viên rút nhiều tiền nhất') }}
                            </h4>
                            <div class="bg-gray-50/50 dark:bg-slate-950/30 rounded-2xl p-4 border border-gray-100 dark:border-slate-800/50 overflow-y-auto" style="height: 300px;">
                                <div x-show="wStatsLoading" class="flex items-center justify-center h-56 text-gray-400">
                                    <svg class="animate-spin h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <span>{{ __('Đang tải...') }}</span>
                                </div>
                                <template x-if="!wStatsLoading && wStatsTopUsers.length === 0">
                                    <div class="flex flex-col items-center justify-center h-56 text-gray-400 dark:text-slate-500 text-xs">
                                        <i data-lucide="info" class="w-8 h-8 mb-2 opacity-50"></i>
                                        <span>{{ __('Không có dữ liệu rút tiền trong khoảng thời gian này.') }}</span>
                                    </div>
                                </template>
                                <template x-if="!wStatsLoading && wStatsTopUsers.length > 0">
                                    <div class="space-y-3">
                                        <template x-for="(usr, idx) in wStatsTopUsers" :key="idx">
                                            <div class="p-3 bg-white dark:bg-slate-900 rounded-xl border border-gray-150 dark:border-slate-800/80 flex items-center justify-between shadow-sm">
                                                <div class="flex items-center gap-2.5 min-w-0 flex-1">
                                                    <span class="w-5 h-5 rounded-md flex items-center justify-center text-[10px] font-black shrink-0 font-mono"
                                                          :class="idx === 0 ? 'bg-amber-100 text-amber-700 dark:bg-amber-950/40 dark:text-amber-400' : (idx === 1 ? 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-400' : (idx === 2 ? 'bg-orange-100 text-orange-700 dark:bg-orange-950/40 dark:text-orange-400' : 'bg-gray-50 text-gray-500 dark:bg-slate-950 dark:text-slate-500'))"
                                                          x-text="idx + 1">
                                                    </span>
                                                    <div class="truncate">
                                                        <p class="font-bold text-gray-800 dark:text-slate-200 truncate text-[11px]" x-text="usr.user_name" :title="usr.user_name"></p>
                                                        <p class="text-[9px] text-gray-400 dark:text-slate-500 font-mono truncate" x-text="usr.user_email"></p>
                                                    </div>
                                                </div>
                                                <span class="inline-flex px-2 py-0.5 rounded-full text-[9px] font-bold bg-green-50 dark:bg-green-950/30 text-green-600 dark:text-green-400 border border-green-100 dark:border-green-900/50 shrink-0 ml-2" x-text="formatCurrency(usr.total_amount)"></span>
                                            </div>
                                        </template>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="px-6 py-4 bg-gray-50 dark:bg-slate-800/50 border-t border-gray-100 dark:border-slate-800 flex justify-end">
                    <button type="button" @click="withdrawStatsModalOpen = false" class="px-5 py-2 text-xs font-semibold text-gray-700 dark:text-slate-350 bg-gray-100 dark:bg-slate-800 hover:bg-gray-200 dark:hover:bg-slate-700 rounded-xl transition-all border border-transparent dark:border-slate-700/50">
                        {{ __('Đóng') }}
                    </button>
                </div>
            </div>
        </div>
    </template>
</div>
@endsection

@section('scripts')
<script>
    // Khai báo thực thể biểu đồ ngoài x-data để tránh lỗi Proxy của Alpine.js làm trắng biểu đồ khi vẽ lại
    let withdrawChartInstanceGlobal = null;

    // Handler quản lý logic Alpine.js cho view Yêu cầu rút tiền phía Admin
    function withdrawAdminHandler() {
        return {
            // Thuộc tính phục vụ lọc dữ liệu và chuyển trang qua AJAX
            activeStatus: @json(request('status') ?? ''),
            statusCounts: @json($statusCounts),

            // Thuộc tính phục vụ tích chọn nhanh nhiều yêu cầu để duyệt chi / xóa hàng loạt
            selectedWithdrawals: [],                                     // Danh sách ID đang được tích chọn
            allSelected: false,                                          // Trạng thái ô tích chọn tất cả trên đầu bảng
            selectableWithdrawalIds: @json($selectableWithdrawalIds),    // Toàn bộ ID đang hiển thị trên trang hiện tại
            pendingWithdrawalIds: @json($pendingWithdrawalIds),          // Các ID đang chờ duyệt (được phép duyệt chi hàng loạt)
            pendingWithdrawalAmounts: @json((object)$pendingWithdrawalAmounts), // Số tiền thực nhận theo từng ID chờ duyệt
            bulkApproveModalOpen: false,                                 // Modal xác nhận duyệt chi hàng loạt
            bulkDeleteModalOpen: false,                                  // Modal xác nhận xóa hàng loạt
            confirmBulkDeleteCheckbox: false,                            // Checkbox xác nhận xóa hàng loạt
            confirmBulkText: '',                                         // Ô nhập cụm từ "XÓA HÀNG LOẠT" để xác thực
            isSubmitting: false,                                         // Trạng thái đang gửi request hàng loạt

            rejectModalOpen: false,
            approveModalOpen: false, // Biến trạng thái của modal xác nhận duyệt chi
            deleteModalOpen: false, // Biến trạng thái của modal xác nhận xóa yêu cầu rút tiền
            loadingApprove: false, // Trạng thái đang tải khi phê duyệt
            detailsModalOpen: false,
            loadingDetails: false,
            activeWithdrawal: {},
            activeDeleteWithdrawal: {},
            detailedWithdrawal: {},
            confirmDeleteCheckbox: false,
            financialLogs: [],

            // Thuộc tính phục vụ thống kê rút tiền
            withdrawStatsModalOpen: false,
            wStatsLoading: false,
            wStatsPeriod: 'week',
            wStatsTotals: { approved: 0, pending: 0 },
            wStatsTopUsers: [],

            // Hàm khởi tạo tự động khi component AlpineJS sẵn sàng
            init() {
                // Sử dụng Event Delegation để bắt sự kiện click phân trang mượt mà bằng AJAX
                const container = document.getElementById('withdrawal-table-container');
                if (container) {
                    container.addEventListener('click', (e) => {
                        const pageLink = e.target.closest('.pagination a') || e.target.closest('a[href*="page="]');
                        if (pageLink && pageLink.href) {
                            e.preventDefault();
                            this.fetchData(pageLink.href);
                        }
                    });
                }
            },

            // Hàm thực hiện gọi AJAX cập nhật bảng và số lượng đếm
            async fetchData(url) {
                const loadingEl = document.getElementById('withdrawal-loading');
                if (loadingEl) {
                    loadingEl.classList.remove('opacity-0', 'pointer-events-none');
                    loadingEl.classList.add('opacity-100');
                }

                try {
                    const response = await fetch(url, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        }
                    });
                    
                    if (!response.ok) throw new Error('Network response was not ok');
                    
                    const data = await response.json();
                    
                    // Cập nhật HTML bảng
                    const tableContentEl = document.getElementById('withdrawal-table-content');
                    if (tableContentEl) {
                        tableContentEl.innerHTML = data.html;
                    }

                    // Cập nhật số lượng đếm trạng thái trong AlpineJS
                    if (data.statusCounts) {
                        this.statusCounts = data.statusCounts;
                    }

                    // Đồng bộ lại danh sách ID có thể tích chọn của trang vừa tải
                    if (data.selectableWithdrawalIds) {
                        this.selectableWithdrawalIds = data.selectableWithdrawalIds;
                    }
                    if (data.pendingWithdrawalIds) {
                        this.pendingWithdrawalIds = data.pendingWithdrawalIds;
                    }
                    if (data.pendingWithdrawalAmounts) {
                        this.pendingWithdrawalAmounts = data.pendingWithdrawalAmounts;
                    }

                    // Xóa các lựa chọn cũ để tránh thao tác nhầm lên dữ liệu không còn hiển thị
                    this.clearSelection();

                    // Reset các icon Lucide trong HTML vừa cập nhật
                    if (window.lucide) {
                        window.lucide.createIcons();
                    }

                    // Cập nhật URL trình duyệt để lưu lại trạng thái lọc khi tải lại trang
                    window.history.pushState({}, '', url);

                } catch (error) {
                    console.error('AJAX Load Error:', error);
                    alert('Không thể kết nối đến máy chủ. Vui lòng thử lại sau.');
                } finally {
                    if (loadingEl) {
                        loadingEl.classList.add('opacity-0', 'pointer-events-none');
                        loadingEl.classList.remove('opacity-100');
                    }
                }
            },

            // Thực hiện tải dữ liệu khi chuyển đổi qua lại giữa các tab trạng thái lọc nhanh
            loadTab(url, status) {
                this.activeStatus = status;
                // Tự động đồng bộ với input select trong form tìm kiếm
                const statusSelect = document.querySelector('select[name="status"]');
                if (statusSelect) {
                    statusSelect.value = status;
                }
                this.fetchData(url);
            },

            // Xử lý gửi form tìm kiếm qua AJAX
            submitFilterForm(e) {
                const form = e.target;
                const formData = new FormData(form);
                const params = new URLSearchParams();

                for (const [key, value] of formData.entries()) {
                    if (value && value.trim() !== '') {
                        params.append(key, value);
                    }
                }

                const url = form.action + '?' + params.toString();
                this.activeStatus = params.get('status') || '';
                this.fetchData(url);
            },

            // Reset toàn bộ bộ lọc và tải lại dữ liệu ban đầu
            resetFilter() {
                const form = document.getElementById('withdrawal-filter-form');
                if (form) {
                    form.reset();
                    form.querySelectorAll('input').forEach(input => {
                        if (input.type !== 'hidden') input.value = '';
                    });
                    form.querySelectorAll('select').forEach(select => select.value = select.options[0]?.value || '');
                }
                this.activeStatus = '';
                this.fetchData('{{ route("admin.withdrawals.index") }}');
            },

            // Hàm xử lý việc thay đổi cột và hướng sắp xếp (tăng/giảm) danh sách yêu cầu rút tiền
            changeSort(field) {
                const form = document.getElementById('withdrawal-filter-form');
                if (!form) return;

                const sortByInput = form.querySelector('input[name="sort_by"]');
                const sortOrderInput = form.querySelector('input[name="sort_order"]');
                if (!sortByInput || !sortOrderInput) return;

                let currentSort = sortByInput.value;
                let currentOrder = sortOrderInput.value;

                if (currentSort === field) {
                    sortOrderInput.value = currentOrder === 'asc' ? 'desc' : 'asc';
                } else {
                    sortByInput.value = field;
                    sortOrderInput.value = 'desc';
                }
                
                const formData = new FormData(form);
                const params = new URLSearchParams();
                for (const [key, value] of formData.entries()) {
                    if (value && value.trim() !== '') {
                        params.append(key, value);
                    }
                }
                params.set('page', 1);
                const url = form.action + '?' + params.toString();
                this.activeStatus = params.get('status') || '';
                this.fetchData(url);
            },

            // ============================================================
            // NHÓM HÀM XỬ LÝ TÍCH CHỌN & HÀNH ĐỘNG HÀNG LOẠT
            // ============================================================

            // Tích chọn hoặc bỏ chọn toàn bộ yêu cầu rút tiền đang hiển thị trên trang
            toggleAll() {
                this.selectedWithdrawals = this.allSelected ? [...this.selectableWithdrawalIds] : [];
            },

            // Đồng bộ lại trạng thái ô "chọn tất cả" mỗi khi tích/bỏ tích một dòng đơn lẻ
            syncAllSelected() {
                this.allSelected = this.selectableWithdrawalIds.length > 0
                    && this.selectedWithdrawals.length === this.selectableWithdrawalIds.length;
            },

            // Bỏ chọn toàn bộ và reset ô chọn tất cả
            clearSelection() {
                this.selectedWithdrawals = [];
                this.allSelected = false;
            },

            // Đếm số yêu cầu đang chờ duyệt trong danh sách đã tích chọn (chỉ nhóm này mới được duyệt chi)
            selectedPendingCount() {
                return this.selectedWithdrawals.filter(id => this.pendingWithdrawalIds.includes(Number(id))).length;
            },

            // Tính tổng số tiền thực chi của các yêu cầu chờ duyệt đã tích chọn
            selectedPendingRealAmount() {
                return this.selectedWithdrawals.reduce((total, id) => {
                    const amount = this.pendingWithdrawalAmounts[id];
                    return total + (amount ? Number(amount) : 0);
                }, 0);
            },

            // Mở modal xác nhận duyệt chi hàng loạt
            openBulkApproveModal() {
                if (this.selectedPendingCount() === 0) return;
                this.bulkApproveModalOpen = true;
                setTimeout(() => { if (window.lucide) window.lucide.createIcons(); }, 50);
            },

            // Mở modal xác nhận xóa hàng loạt (reset lại checkbox và ô nhập xác thực)
            openBulkDeleteModal() {
                this.confirmBulkText = '';
                this.confirmBulkDeleteCheckbox = false;
                this.bulkDeleteModalOpen = true;
                setTimeout(() => { if (window.lucide) window.lucide.createIcons(); }, 50);
            },

            // Gửi yêu cầu duyệt chi hàng loạt lên hệ thống qua AJAX
            async submitBulkApprove() {
                if (this.isSubmitting || this.selectedPendingCount() === 0) return;
                this.isSubmitting = true;

                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

                try {
                    const response = await fetch('{{ route("admin.withdrawals.bulk_approve") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            ids: this.selectedWithdrawals.join(',')
                        })
                    });

                    const data = await response.json();

                    if (data.status === 'success') {
                        this.bulkApproveModalOpen = false;
                        this.clearSelection();

                        await Swal.fire({
                            icon: 'success',
                            title: @js(__('Thành công')),
                            text: data.message,
                            timer: 2000,
                            showConfirmButton: false
                        });

                        window.location.reload();
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: @js(__('Thất bại')),
                            text: data.message || @js(__('Có lỗi xảy ra trong quá trình duyệt chi hàng loạt.'))
                        });
                    }
                } catch (error) {
                    console.error(error);
                    Swal.fire({
                        icon: 'error',
                        title: @js(__('Lỗi hệ thống')),
                        text: @js(__('Không thể kết nối đến máy chủ. Vui lòng thử lại sau.'))
                    });
                } finally {
                    this.isSubmitting = false;
                }
            },

            // Gửi yêu cầu xóa hàng loạt lên hệ thống qua AJAX
            async submitBulkDelete() {
                if (this.isSubmitting) return;

                // Kiểm tra lại điều kiện xác thực ở phía client trước khi gửi lên máy chủ
                if (this.confirmBulkText.toUpperCase() !== 'XÓA HÀNG LOẠT' || !this.confirmBulkDeleteCheckbox) {
                    Swal.fire({
                        icon: 'error',
                        title: @js(__('Lỗi dữ liệu')),
                        text: @js(__('Vui lòng tích chọn xác nhận và nhập chính xác từ khóa "XÓA HÀNG LOẠT" để tiếp tục.'))
                    });
                    return;
                }

                this.isSubmitting = true;
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

                try {
                    // Gọi thẳng phương thức DELETE đúng như khai báo của route.
                    // Lưu ý: KHÔNG dùng POST kèm _method trong body JSON vì Laravel chỉ đọc _method
                    // từ dữ liệu form-encoded hoặc query string, dẫn đến request bị trả về lỗi 405.
                    const response = await fetch('{{ route("admin.withdrawals.bulk_destroy") }}', {
                        method: 'DELETE',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            ids: this.selectedWithdrawals.join(','),
                            confirm_text: this.confirmBulkText
                        })
                    });

                    const data = await response.json();

                    if (data.status === 'success') {
                        this.bulkDeleteModalOpen = false;
                        this.confirmBulkText = '';
                        this.confirmBulkDeleteCheckbox = false;
                        this.clearSelection();

                        await Swal.fire({
                            icon: 'success',
                            title: @js(__('Thành công')),
                            text: data.message,
                            timer: 2000,
                            showConfirmButton: false
                        });

                        window.location.reload();
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: @js(__('Thất bại')),
                            text: data.message || @js(__('Có lỗi xảy ra trong quá trình xóa hàng loạt.'))
                        });
                    }
                } catch (error) {
                    console.error(error);
                    Swal.fire({
                        icon: 'error',
                        title: @js(__('Lỗi hệ thống')),
                        text: @js(__('Không thể kết nối đến máy chủ. Vui lòng thử lại sau.'))
                    });
                } finally {
                    this.isSubmitting = false;
                }
            },

            // Mở modal từ chối và tạo lại các Lucide icons
            openRejectModal(item) {
                this.activeWithdrawal = { ...item };
                this.detailsModalOpen = false; // Đóng modal chi tiết
                this.rejectModalOpen = true;
                setTimeout(() => { if (window.lucide) window.lucide.createIcons(); }, 50);
            },

            // Mở modal xác nhận duyệt chi và tạo lại các Lucide icons
            openApproveModal(item) {
                this.activeWithdrawal = { ...item };
                this.detailsModalOpen = false; // Đóng modal chi tiết
                this.approveModalOpen = true;
                this.loadingApprove = false; // Reset trạng thái load
                setTimeout(() => { if (window.lucide) window.lucide.createIcons(); }, 50);
            },

            // Mở modal xác nhận xóa yêu cầu rút tiền (kèm checkbox và mô tả tác động dữ liệu)
            openDeleteModal(item) {
                this.activeDeleteWithdrawal = { ...item };
                this.confirmDeleteCheckbox = false;
                this.detailsModalOpen = false; // Đóng modal chi tiết nếu đang mở
                this.deleteModalOpen = true;
                setTimeout(() => { if (window.lucide) window.lucide.createIcons(); }, 50);
            },

            // Gửi yêu cầu duyệt chi bằng AJAX lên hệ thống để tránh race condition
            async submitApprove() {
                if (this.loadingApprove) return;
                this.loadingApprove = true;

                try {
                    const token = document.querySelector('input[name="_token"]').value;
                    let response = await fetch(`/${window.adminPrefix}/withdrawals/${this.activeWithdrawal.id}/approve`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': token,
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: JSON.stringify({})
                    });

                    let resData = await response.json();
                    
                    if (resData.success) {
                        // Kích hoạt toast thông báo thành công
                        window.dispatchEvent(new CustomEvent('toast', { 
                            detail: { 
                                text: resData.message || 'Phê duyệt yêu cầu rút tiền thành công!', 
                                type: 'success' 
                            } 
                        }));
                        
                        // Đóng modal và reload trang sau 1 giây để cập nhật số dư/trạng thái
                        this.approveModalOpen = false;
                        setTimeout(() => {
                            window.location.reload();
                        }, 1000);
                    } else {
                        alert(resData.message || 'Có lỗi xảy ra trong quá trình phê duyệt.');
                        this.loadingApprove = false;
                    }
                } catch (error) {
                    console.error(error);
                    alert('Lỗi kết nối hệ thống khi thực hiện phê duyệt. Vui lòng thử lại sau.');
                    this.loadingApprove = false;
                }
            },

            // Lấy chi tiết yêu cầu rút tiền và lịch sử số dư từ API
            async openDetailsModal(id) {
                this.detailedWithdrawal = {};
                this.financialLogs = [];
                this.loadingDetails = true;
                this.detailsModalOpen = true;

                try {
                    let response = await fetch(`/${window.adminPrefix}/withdrawals/${id}/details`);
                    let resData = await response.json();
                    if (resData.success) {
                        this.detailedWithdrawal = resData.withdrawal;
                        this.financialLogs = resData.logs;
                    } else {
                        alert(resData.message || 'Không thể tải chi tiết yêu cầu rút.');
                        this.detailsModalOpen = false;
                    }
                } catch (error) {
                    console.error(error);
                    alert('Lỗi kết nối hệ thống khi lấy chi tiết dòng tiền.');
                    this.detailsModalOpen = false;
                } finally {
                    this.loadingDetails = false;
                    setTimeout(() => { if (window.lucide) window.lucide.createIcons(); }, 50);
                }
            },

            // Định dạng số thành tiền tệ VND
            formatCurrency(value) {
                if (value === undefined || value === null) return '0đ';
                return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(value);
            },

            // Tạo mã URL VietQR động theo các thông số yêu cầu chuyển khoản
            getVietQrUrl() {
                if (!this.detailedWithdrawal.id) return '';
                
                let bankCode = '';
                if (this.detailedWithdrawal.payment_method === 'momo') {
                    bankCode = 'MOMO';
                } else {
                    bankCode = this.getVietQRBankCode(this.detailedWithdrawal.bank_name);
                }
                
                const accountNo = this.detailedWithdrawal.account_number || '';
                const amount = this.detailedWithdrawal.real_amount || 0;
                const accountName = encodeURIComponent(this.detailedWithdrawal.account_name || '');
                const addInfo = encodeURIComponent(this.detailedWithdrawal.code || `HTS W${this.detailedWithdrawal.id}`);
                
                return `https://img.vietqr.io/image/${bankCode}-${accountNo}-compact2.jpg?amount=${amount}&addInfo=${addInfo}&accountName=${accountName}`;
            },

            // Chuyển đổi tên ngân hàng thành viên chọn sang mã chuẩn VietQR
            // Nghiệp vụ: Sử dụng danh sách 65 ngân hàng chính thức từ API VietQR để tự động quy đổi tên ngân hàng của thành viên sang mã BIN/Code của VietQR.
            // Điều này giải quyết triệt để lỗi so khớp sai ngân hàng (ví dụ: Sacombank bị match nhầm thành MB Bank do chứa chuỗi "mb").
            getVietQRBankCode(bankName) {
                if (!bankName) return '';
                const name = bankName.toLowerCase().replace(/\s+/g, '');
                
                // Mảng danh sách ngân hàng tĩnh lấy từ VietQR API (api.vietqr.io/v2/banks)
                const banks = [
                    { id: 17, name: 'Ngân hàng TMCP Công thương Việt Nam', code: 'ICB', shortName: 'VietinBank' },
                    { id: 43, name: 'Ngân hàng TMCP Ngoại Thương Việt Nam', code: 'VCB', shortName: 'Vietcombank' },
                    { id: 4, name: 'Ngân hàng TMCP Đầu tư và Phát triển Việt Nam', code: 'BIDV', shortName: 'BIDV' },
                    { id: 42, name: 'Ngân hàng Nông nghiệp và Phát triển Nông thôn Việt Nam', code: 'VBA', shortName: 'Agribank' },
                    { id: 26, name: 'Ngân hàng TMCP Phương Đông', code: 'OCB', shortName: 'OCB' },
                    { id: 21, name: 'Ngân hàng TMCP Quân đội', code: 'MB', shortName: 'MBBank' },
                    { id: 38, name: 'Ngân hàng TMCP Kỹ thương Việt Nam', code: 'TCB', shortName: 'Techcombank' },
                    { id: 2, name: 'Ngân hàng TMCP Á Châu', code: 'ACB', shortName: 'ACB' },
                    { id: 47, name: 'Ngân hàng TMCP Việt Nam Thịnh Vượng', code: 'VPB', shortName: 'VPBank' },
                    { id: 39, name: 'Ngân hàng TMCP Tiên Phong', code: 'TPB', shortName: 'TPBank' },
                    { id: 36, name: 'Ngân hàng TMCP Sài Gòn Thương Tín', code: 'STB', shortName: 'Sacombank' },
                    { id: 12, name: 'Ngân hàng TMCP Phát triển Thành phố Hồ Chí Minh', code: 'HDB', shortName: 'HDBank' },
                    { id: 44, name: 'Ngân hàng TMCP Bản Việt', code: 'VCCB', shortName: 'VietCapitalBank' },
                    { id: 31, name: 'Ngân hàng TMCP Sài Gòn', code: 'SCB', shortName: 'SCB' },
                    { id: 45, name: 'Ngân hàng TMCP Quốc tế Việt Nam', code: 'VIB', shortName: 'VIB' },
                    { id: 35, name: 'Ngân hàng TMCP Sài Gòn - Hà Nội', code: 'SHB', shortName: 'SHB' },
                    { id: 10, name: 'Ngân hàng TMCP Xuất Nhập khẩu Việt Nam', code: 'EIB', shortName: 'Eximbank' },
                    { id: 22, name: 'Ngân hàng TMCP Hàng Hải Việt Nam', code: 'MSB', shortName: 'MSB' },
                    { id: 53, name: 'TMCP Việt Nam Thịnh Vượng - Ngân hàng số CAKE by VPBank', code: 'CAKE', shortName: 'CAKE' },
                    { id: 54, name: 'TMCP Việt Nam Thịnh Vượng - Ngân hàng số Ubank by VPBank', code: 'Ubank', shortName: 'Ubank' },
                    { id: 57, name: 'Tổng Công ty Dịch vụ số Viettel - Chi nhánh tập đoàn công nghiệp viễn thông Quân Đội', code: 'VTLMONEY', shortName: 'ViettelMoney' },
                    { id: 58, name: 'Ngân hàng số Timo by Ban Viet Bank (Timo by Ban Viet Bank)', code: 'TIMO', shortName: 'Timo' },
                    { id: 56, name: 'VNPT Money', code: 'VNPTMONEY', shortName: 'VNPTMoney' },
                    { id: 34, name: 'Ngân hàng TMCP Sài Gòn Công Thương', code: 'SGICB', shortName: 'SaigonBank' },
                    { id: 3, name: 'Ngân hàng TMCP Bắc Á', code: 'BAB', shortName: 'BacABank' },
                    { id: 65, name: 'CTCP Dịch Vụ Di Động Trực Tuyến', code: 'momo', shortName: 'MoMo' },
                    { id: 64, name: 'Ngân hàng TMCP Đại Chúng Việt Nam Ngân hàng số', code: 'PVDB', shortName: 'PVcomBank Pay' },
                    { id: 30, name: 'Ngân hàng TMCP Đại Chúng Việt Nam', code: 'PVCB', shortName: 'PVcomBank' },
                    { id: 27, name: 'Ngân hàng TNHH MTV Việt Nam Hiện Đại', code: 'MBV', shortName: 'MBV' },
                    { id: 24, name: 'Ngân hàng TMCP Quốc Dân', code: 'NCB', shortName: 'NCB' },
                    { id: 37, name: 'Ngân hàng TNHH MTV Shinhan Việt Nam', code: 'SHBVN', shortName: 'ShinhanBank' },
                    { id: 1, name: 'Ngân hàng TMCP An Bình', code: 'ABB', shortName: 'ABBANK' },
                    { id: 41, name: 'Ngân hàng TMCP Việt Á', code: 'VAB', shortName: 'VietABank' },
                    { id: 23, name: 'Ngân hàng TMCP Nam Á', code: 'NAB', shortName: 'NamABank' },
                    { id: 29, name: 'Ngân hàng TMCP Thịnh vượng và Phát triển', code: 'PGB', shortName: 'PGBank' },
                    { id: 46, name: 'Ngân hàng TMCP Việt Nam Thương Tín', code: 'VIETBANK', shortName: 'VietBank' },
                    { id: 5, name: 'Ngân hàng TMCP Bảo Việt', code: 'BVB', shortName: 'BaoVietBank' },
                    { id: 33, name: 'Ngân hàng TMCP Đông Nam Á', code: 'SEAB', shortName: 'SeABank' },
                    { id: 52, name: 'Ngân hàng Hợp tác xã Việt Nam', code: 'COOPBANK', shortName: 'COOPBANK' },
                    { id: 20, name: 'Ngân hàng TMCP Lộc Phát Việt Nam', code: 'LPB', shortName: 'LPBank' },
                    { id: 19, name: 'Ngân hàng TMCP Kiên Long', code: 'KLB', shortName: 'KienLongBank' },
                    { id: 55, name: 'Ngân hàng Đại chúng TNHH Kasikornbank', code: 'KBank', shortName: 'KBank' },
                    { id: 62, name: 'Công ty Tài chính TNHH MTV Mirae Asset (Việt Nam) ', code: 'MAFC', shortName: 'MAFC' },
                    { id: 13, name: 'Ngân hàng TNHH MTV Hồng Leong Việt Nam', code: 'HLBVN', shortName: 'HongLeong' },
                    { id: 61, name: 'Ngân hàng KEB Hana – Chi nhánh Hà Nội', code: 'KEBHANAHN', shortName: 'KEBHANAHN' },
                    { id: 60, name: 'Ngân hàng KEB Hana – Chi nhánh Thành phố Hồ Chí Minh', code: 'KEBHANAHCM', shortName: 'KEBHanaHCM' },
                    { id: 59, name: 'Ngân hàng Citibank, N.A. - Chi nhánh Hà Nội', code: 'CITIBANK', shortName: 'Citibank' },
                    { id: 6, name: 'Ngân hàng Thương mại TNHH MTV Xây dựng Việt Nam', code: 'CBB', shortName: 'CBBank' },
                    { id: 7, name: 'Ngân hàng TNHH MTV CIMB Việt Nam', code: 'CIMB', shortName: 'CIMB' },
                    { id: 8, name: 'DBS Bank Ltd - Chi nhánh Thành phố Hồ Chí Minh', code: 'DBS', shortName: 'DBSBank' },
                    { id: 9, name: 'Ngân hàng TNHH MTV Số Vikki', code: 'Vikki', shortName: 'Vikki' },
                    { id: 63, name: 'Ngân hàng Chính sách Xã hội', code: 'VBSP', shortName: 'VBSP' },
                    { id: 11, name: 'Ngân hàng Thương mại TNHH MTV Dầu Khí Toàn Cầu', code: 'GPB', shortName: 'GPBank' },
                    { id: 51, name: 'Ngân hàng Kookmin - Chi nhánh Thành phố Hồ Chí Minh', code: 'KBHCM', shortName: 'KookminHCM' },
                    { id: 50, name: 'Ngân hàng Kookmin - Chi nhánh Hà Nội', code: 'KBHN', shortName: 'KookminHN' },
                    { id: 49, name: 'Ngân hàng TNHH MTV Woori Việt Nam', code: 'WVN', shortName: 'Woori' },
                    { id: 48, name: 'Ngân hàng Liên doanh Việt - Nga', code: 'VRB', shortName: 'VRB' },
                    { id: 14, name: 'Ngân hàng TNHH MTV HSBC (Việt Nam)', code: 'HSBC', shortName: 'HSBC' },
                    { id: 15, name: 'Ngân hàng Công nghiệp Hàn Quốc - Chi nhánh Hà Nội', code: 'IBK - HN', shortName: 'IBKHN' },
                    { id: 16, name: 'Ngân hàng Công nghiệp Hàn Quốc - Chi nhánh TP. Hồ Chí Minh', code: 'IBK - HCM', shortName: 'IBKHCM' },
                    { id: 18, name: 'Ngân hàng TNHH Indovina', code: 'IVB', shortName: 'IndovinaBank' },
                    { id: 40, name: 'Ngân hàng United Overseas - Chi nhánh TP. Hồ Chí Minh', code: 'UOB', shortName: 'UnitedOverseas' },
                    { id: 25, name: 'Ngân hàng Nonghyup - Chi nhánh Hà Nội', code: 'NHB HN', shortName: 'Nonghyup' },
                    { id: 32, name: 'Ngân hàng TNHH MTV Standard Chartered Bank Việt Nam', code: 'SCVN', shortName: 'StandardChartered' },
                    { id: 28, name: 'Ngân hàng TNHH MTV Public Việt Nam', code: 'PBVN', shortName: 'PublicBank' }
                ];
                
                // Bước 1: So khớp chính xác tuyệt đối tên viết tắt (shortName), mã (code) hoặc tên đầy đủ (name) để có độ chính xác cao nhất.
                let found = banks.find(b => {
                    const sn = b.shortName.toLowerCase().replace(/\s+/g, '');
                    const c = b.code.toLowerCase().replace(/\s+/g, '');
                    const full = b.name.toLowerCase().replace(/\s+/g, '');
                    return name === sn || name === c || name === full;
                });
                
                if (found) return found.code;
                
                // Bước 2: Nếu không khớp chính xác, tìm kiếm tương đối (so khớp chứa chuỗi).
                // Sắp xếp mảng để đưa ngân hàng MB Bank xuống cuối cùng để tránh match nhầm các từ chứa "mb" như Sacombank, PVcombank.
                const sortedBanks = [...banks].sort((a, b) => {
                    if (a.code === 'MB') return 1;
                    if (b.code === 'MB') return -1;
                    return 0;
                });
                
                found = sortedBanks.find(b => {
                    const sn = b.shortName.toLowerCase().replace(/\s+/g, '');
                    const c = b.code.toLowerCase().replace(/\s+/g, '');
                    const full = b.name.toLowerCase().replace(/\s+/g, '');
                    
                    // Đối với MB Bank, bắt buộc phải có kiểm tra nghiêm ngặt không được lẫn với Sacombank, PVcombank
                    if (b.code === 'MB') {
                        return name.includes('mb') && !name.includes('sacom') && !name.includes('pvcom');
                    }
                    
                    return name.includes(sn) || name.includes(c) || name.includes(full) || full.includes(name);
                });
                
                if (found) return found.code;
                
                return bankName.replace(/\s+/g, '');
            },

            // Sao chép nhanh văn bản vào Clipboard & thông báo thành công
            copyText(text) {
                if (!text) return;
                navigator.clipboard.writeText(text).then(() => {
                    window.dispatchEvent(new CustomEvent('toast', { 
                        detail: { 
                            text: 'Đã sao chép thông tin thành công!', 
                            type: 'success' 
                        } 
                    }));
                }).catch(err => {
                    console.error('Không thể sao chép văn bản: ', err);
                });
            },

            // Mở modal thống kê yêu cầu rút tiền
            openWithdrawStatsModal() {
                this.withdrawStatsModalOpen = true;
                this.loadWithdrawStats(this.wStatsPeriod);
            },

            // Tải dữ liệu thống kê rút tiền từ API qua AJAX
            async loadWithdrawStats(period = 'week') {
                this.wStatsPeriod = period;
                this.wStatsLoading = true;

                try {
                    const response = await fetch(`/${window.adminPrefix}/withdrawals/stats?period=${period}`, {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });
                    const data = await response.json();
                    
                    if (data.success) {
                        this.wStatsTotals = data.totals;
                        this.wStatsTopUsers = data.top_users;
                        this.renderWithdrawChart(data, period);
                    } else {
                        alert(data.message || 'Không thể tải thống kê rút tiền.');
                    }
                } catch (error) {
                    console.error('Lỗi khi lấy dữ liệu thống kê rút tiền:', error);
                } finally {
                    this.wStatsLoading = false;
                    setTimeout(() => { if (window.lucide) window.lucide.createIcons(); }, 50);
                }
            },

            // Vẽ biểu đồ số tiền rút đã thanh toán (VND) bằng Chart.js
            // Sử dụng thực thể withdrawChartInstanceGlobal ở phạm vi ngoài để tránh lỗi Proxy của Alpine.js
            renderWithdrawChart(data, period) {
                const ctx = document.getElementById('withdrawStatsChart');
                if (!ctx) return;

                if (withdrawChartInstanceGlobal) {
                    withdrawChartInstanceGlobal.destroy();
                    withdrawChartInstanceGlobal = null;
                }

                // Cấu hình nhãn trục hoành và nhãn biểu đồ tùy thuộc vào period
                let label = 'Số tiền đã duyệt rút (đ)';
                if (period === 'week') {
                    label = 'Tổng tiền rút 7 ngày gần nhất (đ)';
                } else if (period === 'month') {
                    label = 'Tổng tiền rút 30 ngày gần nhất (đ)';
                } else if (period === 'year') {
                    label = 'Tổng tiền rút 12 tháng gần nhất (đ)';
                }

                const isDark = document.documentElement.classList.contains('dark');
                const gridColor = isDark ? 'rgba(148, 163, 184, 0.1)' : 'rgba(226, 232, 240, 0.8)';
                const textColor = isDark ? '#94a3b8' : '#64748b';

                withdrawChartInstanceGlobal = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: data.labels,
                        datasets: [{
                            label: label,
                            data: data.amounts,
                            backgroundColor: 'rgba(79, 70, 229, 0.85)',
                            borderColor: 'rgb(79, 70, 229)',
                            borderWidth: 1,
                            borderRadius: 6,
                            borderSkipped: false,
                            maxBarThickness: 32
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: true,
                                position: 'top',
                                labels: {
                                    boxWidth: 12,
                                    font: { size: 10, weight: 'bold', family: 'system-ui' },
                                    color: textColor
                                }
                            },
                            tooltip: {
                                padding: 10,
                                bodyFont: { size: 11, family: 'system-ui' },
                                titleFont: { size: 11, weight: 'bold', family: 'system-ui' },
                                callbacks: {
                                    label: function(context) {
                                        let val = context.raw || 0;
                                        return ' ' + context.dataset.label + ': ' + new Intl.NumberFormat('vi-VN', { maximumFractionDigits: 0 }).format(val) + 'đ';
                                    }
                                }
                            }
                        },
                        scales: {
                            y: {
                                grid: { color: gridColor },
                                ticks: {
                                    color: textColor,
                                    font: { size: 9, family: 'monospace' },
                                    callback: function(value) {
                                        if (value >= 1000000) {
                                            return (value / 1000000) + 'M';
                                        }
                                        if (value >= 1000) {
                                            return (value / 1000) + 'K';
                                        }
                                        return value;
                                    }
                                }
                            },
                            x: {
                                grid: { display: false },
                                ticks: {
                                    color: textColor,
                                    font: { size: 9, weight: 'bold', family: 'system-ui' }
                                }
                            }
                        }
                    }
                });
            }
        }
    }
</script>
@endsection
