@extends('layouts.app')

@section('title', __('Nhật Ký Hoạt Động Tài Khoản') . ' - ' . $siteName)

@section('content')
<div class="px-4 mx-auto max-w-7xl sm:px-6 lg:px-8 py-6 sm:py-10" x-data="activityLogsHandler({ loadErrorMsg: '{{ __('Không thể tải dữ liệu nhật ký hoạt động.') }}' })">
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
                    <i data-lucide="activity" class="w-6 h-6"></i>
                </div>
                <div>
                    <h1 class="text-lg sm:text-xl font-extrabold text-gray-900 dark:text-white tracking-tight uppercase">
                        {{ __('Nhật Ký Hoạt Động') }}
                    </h1>
                    <p class="hidden sm:block text-xs text-gray-400 dark:text-slate-400 mt-0.5">{{ __('Xem lịch sử các thao tác bảo mật, giao dịch tài chính và điểm danh trên tài khoản của bạn') }}</p>
                </div>
            </div>

            <!-- Bộ lọc & Tìm kiếm -->
            <div class="bg-white dark:bg-slate-900 p-3 rounded-2xl shadow-sm border border-gray-100 dark:border-slate-800/80">
                <form id="activity-logs-filter-form" @submit.prevent="submitFilter()" class="flex items-center gap-2">
                    <!-- Ô tìm kiếm -->
                    <div class="flex-grow relative">
                        <div class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none text-gray-400">
                            <i data-lucide="search" class="w-3.5 h-3.5"></i>
                        </div>
                        <input type="text" 
                               id="activity-logs-search-input"
                               value="{{ request('search') }}"
                               @keydown.enter.prevent="submitFilter()"
                               placeholder="{{ __('Tìm kiếm hoạt động, địa chỉ IP hoặc trình duyệt...') }}" 
                               class="block w-full pl-8 pr-2 py-1.5 border border-gray-200 dark:border-slate-800 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee transition-all bg-gray-50/50 dark:bg-slate-900 dark:text-slate-100">
                    </div>

                    <!-- Nút Tìm kiếm -->
                    <button type="submit" :disabled="loading" class="inline-flex items-center justify-center gap-1.5 px-3 py-1.5 h-8 text-white bg-gradient-to-r from-shopee to-shopee-light hover:brightness-110 rounded-xl transition-all shadow-sm font-bold text-xs disabled:opacity-50" title="{{ __('Tìm kiếm') }}">
                        <svg x-show="loading" class="animate-spin w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" style="display: none;">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <i x-show="!loading" data-lucide="search" class="w-3.5 h-3.5"></i>
                        <span>{{ __('Tìm kiếm') }}</span>
                    </button>

                    <!-- Nút Xoá lọc -->
                    <button type="button" @click="resetFilters()" x-show="hasActiveFilters" x-cloak class="w-8 h-8 shrink-0 inline-flex items-center justify-center text-gray-500 bg-gray-100 hover:bg-gray-200 dark:bg-slate-800 dark:text-slate-300 rounded-xl transition-all hover:scale-[1.02] active:scale-[0.98]" title="{{ __('Xoá bộ lọc') }}">
                        <i data-lucide="x" class="w-3.5 h-3.5"></i>
                    </button>
                </form>
            </div>

            <!-- Bảng Dữ Liệu Load Ajax -->
            <div id="activity-logs-list-container" class="transition-all duration-300" :class="loading ? 'opacity-40 pointer-events-none' : ''">
                @include('dashboard.partials.activity_log_list')
            </div>

        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="{{ asset('js/activity_logs.js') }}?v=1.0.2"></script>
@endsection
