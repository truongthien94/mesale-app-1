@extends('layouts.app')

@section('title', __('Thông Báo Hệ Thống') . ' - ' . $siteName)

@section('content')
<div class="px-4 mx-auto max-w-7xl sm:px-6 lg:px-8 py-6 sm:py-10" x-data="notificationHandler({ initialTab: '{{ $tab }}', initialFilter: '{{ $filter }}', baseUrl: '{{ route('notifications') }}', readAllRoute: '{{ route('notifications.read_all') }}', loadErrorMsg: '{{ __('Không thể tải dữ liệu thông báo.') }}', generalErrorMsg: '{{ __('Có lỗi xảy ra khi xử lý.') }}' })">
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">

        <!-- Sidebar -->
        <div class="hidden lg:block lg:col-span-3">
            @include('dashboard.sidebar')
        </div>

        <!-- Chi tiết -->
        <div class="lg:col-span-9 space-y-6">
            {{-- Nhắc nhở thành viên bổ sung email để bảo vệ tài khoản --}}
            @include('components.email_update_notice')

            <!-- Header card với nền gradient -->
            <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-shopee to-shopee-light dark:from-shopee-dark dark:to-shopee shadow-lg shadow-shopee/20">
                <!-- Hoạ tiết trang trí -->
                <div class="absolute -top-10 -right-10 w-48 h-48 rounded-full bg-white/10 blur-2xl pointer-events-none"></div>
                <div class="absolute -bottom-16 -left-8 w-56 h-56 rounded-full bg-black/5 blur-3xl pointer-events-none"></div>

                <div class="relative p-5 sm:p-7 flex flex-col sm:flex-row sm:items-center justify-between gap-4 sm:gap-5">
                    <div class="flex items-center gap-3.5 sm:gap-4 min-w-0">
                        <div class="w-11 h-11 sm:w-14 sm:h-14 flex items-center justify-center bg-white/20 backdrop-blur-sm text-white rounded-2xl shrink-0 ring-1 ring-white/30 shadow-inner">
                            <i data-lucide="bell-ring" class="w-5 h-5 sm:w-7 sm:h-7"></i>
                        </div>
                        <div class="text-white min-w-0">
                            <h1 class="text-lg sm:text-2xl font-extrabold tracking-tight leading-tight">
                                {{ __('Hộp Thư Thông Báo') }}
                            </h1>
                            <p class="text-[11px] sm:text-sm text-white/80 mt-0.5 sm:mt-1 max-w-md leading-relaxed">
                                {{ __('Cập nhật các sự kiện, giao dịch và thông tin mới nhất từ hệ thống') }}
                            </p>
                        </div>
                    </div>

                    <button @click="markAllAsRead"
                            class="w-full sm:w-auto shrink-0 inline-flex items-center justify-center gap-2 px-4 py-2.5 text-xs sm:text-sm font-bold text-shopee bg-white hover:bg-white/90 rounded-xl transition-all shadow-md hover:shadow-lg hover:-translate-y-0.5 active:translate-y-0 whitespace-nowrap">
                        <i data-lucide="check-check" class="w-4 h-4"></i>
                        <span>{{ __('Đánh dấu tất cả đã đọc') }}</span>
                    </button>
                </div>
            </div>

            <!-- Thanh điều hướng: Tabs + Bộ lọc -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <!-- Tabs Navigation dạng segmented -->
                <div class="grid grid-cols-2 sm:flex sm:items-center gap-1.5 p-1.5 bg-gray-100/80 dark:bg-slate-800/60 rounded-2xl border border-gray-100 dark:border-slate-800">
                    <a href="{{ route('notifications', ['tab' => 'general']) }}"
                       @click.prevent="switchTab('general'); $event.stopPropagation();"
                       class="flex items-center justify-center gap-2 px-2 sm:px-4 py-2.5 text-xs sm:text-sm font-bold rounded-xl transition-all duration-200 whitespace-nowrap"
                       :class="currentTab === 'general' ? 'bg-white dark:bg-slate-900 text-shopee shadow-sm' : 'text-gray-500 hover:text-gray-700 dark:text-slate-400 dark:hover:text-slate-200'">
                        <i data-lucide="megaphone" class="w-4 h-4 shrink-0"></i>
                        <span class="truncate">{{ __('Thông báo chung') }}</span>
                        @if($generalUnread > 0)
                            <span class="unread-badge inline-flex items-center justify-center min-w-[18px] h-[18px] px-1.5 text-[10px] font-bold text-white bg-red-500 rounded-full">{{ $generalUnread > 99 ? '99+' : $generalUnread }}</span>
                        @endif
                    </a>
                    <a href="{{ route('notifications', ['tab' => 'personal']) }}"
                       @click.prevent="switchTab('personal'); $event.stopPropagation();"
                       class="flex items-center justify-center gap-2 px-2 sm:px-4 py-2.5 text-xs sm:text-sm font-bold rounded-xl transition-all duration-200 whitespace-nowrap"
                       :class="currentTab === 'personal' ? 'bg-white dark:bg-slate-900 text-shopee shadow-sm' : 'text-gray-500 hover:text-gray-700 dark:text-slate-400 dark:hover:text-slate-200'">
                        <i data-lucide="user-round" class="w-4 h-4 shrink-0"></i>
                        <span class="truncate">{{ __('Thông báo của bạn') }}</span>
                        @if($personalUnread > 0)
                            <span class="unread-badge inline-flex items-center justify-center min-w-[18px] h-[18px] px-1.5 text-[10px] font-bold text-white bg-red-500 rounded-full">{{ $personalUnread > 99 ? '99+' : $personalUnread }}</span>
                        @endif
                    </a>
                </div>

                <!-- Bộ lọc trạng thái đọc -->
                <div class="grid grid-cols-2 sm:flex sm:items-center gap-1 p-1 bg-gray-100/80 dark:bg-slate-800/60 rounded-xl border border-gray-100 dark:border-slate-800 shrink-0">
                    <button type="button"
                            @click="setFilter('all')"
                            class="inline-flex items-center justify-center gap-1.5 px-3 py-1.5 text-[11px] sm:text-xs font-bold rounded-lg transition-all duration-200"
                            :class="currentFilter === 'all' ? 'bg-white dark:bg-slate-900 text-shopee shadow-sm' : 'text-gray-500 hover:text-gray-700 dark:text-slate-400 dark:hover:text-slate-200'">
                        <i data-lucide="layers" class="w-3.5 h-3.5 shrink-0"></i>
                        {{ __('Tất cả') }}
                    </button>
                    <button type="button"
                            @click="setFilter('unread')"
                            class="inline-flex items-center justify-center gap-1.5 px-3 py-1.5 text-[11px] sm:text-xs font-bold rounded-lg transition-all duration-200"
                            :class="currentFilter === 'unread' ? 'bg-white dark:bg-slate-900 text-shopee shadow-sm' : 'text-gray-500 hover:text-gray-700 dark:text-slate-400 dark:hover:text-slate-200'">
                        <i data-lucide="mail" class="w-3.5 h-3.5 shrink-0"></i>
                        {{ __('Chưa đọc') }}
                    </button>
                </div>
            </div>

            <!-- Container chứa danh sách load động qua Ajax -->
            <div id="notification-list-container" class="transition-all duration-300">
                @include('dashboard.partials.notification_list')
            </div>

        </div>
    </div>

    <!-- Modal Chi Tiết Thông Báo — Bottom Sheet trên Mobile, Popup trên Desktop -->
    <div x-show="openModal"
         @keydown.escape.window="openModal = false"
         class="fixed inset-0 z-50 flex items-end sm:items-center justify-center sm:p-4"
         x-cloak>

        <!-- Backdrop -->
        <div class="fixed inset-0 bg-black/60 backdrop-blur-sm"
             @click="openModal = false"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"></div>

        <!-- Sheet / Modal Box -->
        <div class="relative bg-white dark:bg-slate-900 w-full sm:max-w-lg rounded-t-3xl sm:rounded-2xl overflow-hidden shadow-2xl z-10 border-t border-x border-gray-100 dark:border-slate-800 sm:border max-h-[92vh] flex flex-col"
             x-show="openModal"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-full sm:translate-y-4 sm:scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave-end="opacity-0 translate-y-full sm:translate-y-4 sm:scale-95">

            <!-- Drag handle (mobile only) -->
            <div class="sm:hidden pt-3 pb-0 flex justify-center shrink-0">
                <div class="w-9 h-1 rounded-full bg-gray-200 dark:bg-slate-700"></div>
            </div>

            <!-- Header -->
            <div class="flex items-center justify-between px-5 pt-4 pb-3 sm:pt-5 sm:border-b sm:border-gray-100 sm:dark:border-slate-800 shrink-0">
                <div class="flex items-center gap-3 min-w-0">
                    <div class="w-10 h-10 flex items-center justify-center rounded-2xl bg-gradient-to-tr from-shopee/15 to-shopee-light/15 text-shopee dark:from-shopee/25 dark:to-shopee-light/25 dark:text-shopee-light shrink-0">
                        <i data-lucide="megaphone" class="w-5 h-5" x-show="selectedItem && selectedItem.type === 'general'"></i>
                        <i data-lucide="bell" class="w-5 h-5" x-show="!selectedItem || selectedItem.type !== 'general'"></i>
                    </div>
                    <div class="min-w-0">
                        <h3 class="text-sm font-extrabold text-gray-900 dark:text-white truncate" x-text="selectedItem ? selectedItem.title : ''"></h3>
                    </div>
                </div>
                <button @click="openModal = false"
                    class="w-8 h-8 flex items-center justify-center rounded-xl text-gray-400 dark:text-slate-500 hover:bg-gray-100 dark:hover:bg-slate-800 hover:text-gray-600 dark:hover:text-slate-300 transition-all shrink-0 ml-2">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>

            <!-- Body (scrollable) -->
            <div class="overflow-y-auto flex-1 px-5 pb-2">
                <template x-if="selectedItem">
                    <div class="space-y-4 pb-1 pt-1">

                        <!-- Thời gian -->
                        <div class="flex items-center gap-1.5 text-[11px] text-gray-400 dark:text-slate-500">
                            <i data-lucide="clock" class="w-3.5 h-3.5 shrink-0"></i>
                            <span x-text="selectedItem.created_at"></span>
                            <span class="text-gray-300 dark:text-slate-600">•</span>
                            <span x-text="selectedItem.created_human"></span>
                        </div>

                        <!-- Nội dung đầy đủ -->
                        <div class="p-4 bg-gray-50 dark:bg-slate-800/40 rounded-2xl border border-gray-100/60 dark:border-slate-700/40">
                            <p class="text-[13px] leading-relaxed text-gray-700 dark:text-slate-200 whitespace-pre-line break-words" x-text="selectedItem.content"></p>
                        </div>

                    </div>
                </template>
            </div>

            <!-- Footer (sticky) -->
            <div class="shrink-0 px-5 pt-3 pb-5 sm:pb-4 border-t border-gray-100 dark:border-slate-800 bg-white dark:bg-slate-900 rounded-b-none sm:rounded-b-3xl">
                <button @click="openModal = false"
                    class="flex items-center justify-center gap-2 w-full py-3.5 sm:py-3 text-sm font-bold text-white bg-gradient-to-r from-shopee to-shopee-light rounded-2xl shadow-md shadow-shopee/15 hover:brightness-105 active:scale-[0.98] transition-all">
                    <i data-lucide="check" class="w-4 h-4"></i>
                    <span>{{ __('Đã hiểu') }}</span>
                </button>
            </div>

        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="{{ asset('js/notifications.js') }}?v=1.0.1"></script>
@endsection
