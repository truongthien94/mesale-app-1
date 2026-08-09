@extends('layouts.app')

@section('title', __('Biến Động Số Dư Tài Khoản') . ' - ' . $siteName)

@section('content')
<div class="px-4 mx-auto max-w-7xl sm:px-6 lg:px-8 py-6 sm:py-10" x-data="balanceLogsHandler({ loadErrorMsg: '{{ __('Không thể tải dữ liệu biến động số dư.') }}' })">
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
        
        <!-- Sidebar -->
        <div class="hidden lg:block lg:col-span-3">
            @include('dashboard.sidebar')
        </div>

        <!-- Chi tiết -->
        <div class="lg:col-span-9 space-y-6">
            {{-- Nhắc nhở thành viên bổ sung email để bảo vệ tài khoản --}}
            @include('components.email_update_notice')

            <!-- Tiêu đề trang -->
            <div class="flex items-center gap-3.5 pb-2">
                <div class="w-12 h-12 flex items-center justify-center bg-gradient-to-tr from-shopee/10 to-shopee-light/10 text-shopee rounded-2xl dark:from-shopee/20 dark:to-shopee-light/20 dark:text-shopee-light shrink-0 shadow-sm">
                    <i data-lucide="history" class="w-6 h-6"></i>
                </div>
                <div>
                    <h1 class="text-lg sm:text-xl font-extrabold text-gray-900 dark:text-white tracking-tight uppercase">
                        {{ __('Biến Động Số Dư') }}
                    </h1>
                    <p class="hidden sm:block text-xs text-gray-400 dark:text-slate-400 mt-0.5">{{ __('Theo dõi chi tiết lịch sử cộng/trừ tiền trong ví của bạn') }}</p>
                </div>
            </div>

            <!-- Bộ lọc & Tìm kiếm -->
            <div class="bg-white dark:bg-slate-900 p-3 rounded-2xl shadow-sm border border-gray-100 dark:border-slate-800/80">
                <form id="balance-logs-filter-form" @submit.prevent="submitFilter()" class="flex items-center gap-2">
                    <!-- Ô tìm kiếm -->
                    <div class="flex-grow relative">
                        <div class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none text-gray-400">
                            <i data-lucide="search" class="w-3.5 h-3.5"></i>
                        </div>
                        <input type="text" 
                               id="balance-logs-search-input"
                               value="{{ request('search') }}"
                               @keydown.enter.prevent="submitFilter()"
                               placeholder="{{ __('Tìm kiếm theo mô tả...') }}" 
                               class="block w-full pl-8 pr-2 py-1.5 border border-gray-200 dark:border-slate-800 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee transition-all bg-gray-50/50 dark:bg-slate-900 dark:text-slate-100">
                    </div>

                    <!-- Dropdown loại -->
                    <div class="w-28 sm:w-40 shrink-0">
                        <select id="balance-logs-type-select" @change="submitFilter()" class="block w-full px-2 py-1.5 border border-gray-200 dark:border-slate-800 rounded-xl text-[11px] sm:text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-gray-50/50 dark:bg-slate-900 dark:text-slate-100 font-medium text-gray-700">
                            <option value="">{{ __('Tất cả loại') }}</option>
                            <option value="cashback" {{ request('type') === 'cashback' ? 'selected' : '' }}>{{ __('Hoàn tiền') }}</option>
                            <option value="referral" {{ request('type') === 'referral' ? 'selected' : '' }}>{{ __('Hoa hồng MLM') }}</option>
                            <option value="checkin" {{ request('type') === 'checkin' ? 'selected' : '' }}>{{ __('Điểm danh') }}</option>
                            <option value="withdraw_request" {{ request('type') === 'withdraw_request' ? 'selected' : '' }}>{{ __('Rút tiền') }}</option>
                            <option value="withdraw_refund" {{ request('type') === 'withdraw_refund' ? 'selected' : '' }}>{{ __('Hoàn tiền rút') }}</option>
                            <option value="admin_adjust" {{ request('type') === 'admin_adjust' ? 'selected' : '' }}>{{ __('Admin sửa') }}</option>
                            <option value="task_reward" {{ request('type') === 'task_reward' ? 'selected' : '' }}>{{ __('Thưởng nhiệm vụ') }}</option>
                        </select>
                    </div>

                    <!-- Nút Submit tìm kiếm -->
                    <button type="submit" class="px-4 py-1.5 shrink-0 inline-flex items-center gap-1.5 text-white bg-gradient-to-r from-shopee to-shopee-light hover:brightness-110 rounded-xl text-xs font-bold transition-all shadow-sm hover:scale-[1.02] active:scale-[0.98]">
                        <svg x-show="loading" class="animate-spin w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" style="display: none;">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        <i x-show="!loading" data-lucide="filter" class="w-3.5 h-3.5"></i>
                        <span>{{ __('Tìm kiếm') }}</span>
                    </button>

                    <!-- Nút Xoá lọc -->
                    <button type="button" @click="resetFilters()" x-show="hasActiveFilters" x-cloak class="w-8 h-8 shrink-0 inline-flex items-center justify-center text-gray-500 bg-gray-100 hover:bg-gray-200 dark:bg-slate-800 dark:text-slate-300 rounded-xl transition-all hover:scale-[1.02] active:scale-[0.98]" title="{{ __('Xoá bộ lọc') }}">
                        <i data-lucide="x" class="w-3.5 h-3.5"></i>
                    </button>
                </form>
            </div>

            <!-- Bảng Dữ Liệu Load Ajax -->
            <div id="balance-logs-list-container" class="transition-all duration-300">
                @include('dashboard.partials.balance_log_list')
            </div>

        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="{{ asset('js/balance_logs.js') }}?v=1.0.2"></script>
@endsection
