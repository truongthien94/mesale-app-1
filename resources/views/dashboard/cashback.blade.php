@extends('layouts.app')

@section('title', __('Lịch Sử Hoàn Tiền Đơn Hàng') . ' - ' . $siteName)

@section('content')
<div class="px-4 mx-auto max-w-7xl sm:px-6 lg:px-8 py-6 sm:py-10" x-data="cashbackHistoryHandler({
    loadErrorMsg: @js(__('Không thể tải dữ liệu lịch sử hoàn tiền.')),
    translations: {
        order_recorded: @js(__('Đơn hàng được ghi nhận')),
        approved_title: @js(__('Đơn được duyệt hoàn tiền')),
        rejected_title: @js(__('Đơn bị từ chối')),
        clawback_title: @js(__('Thu hồi tiền hoàn (đơn bị huỷ)')),
        amount_label: @js(__('Số tiền:')),
        link_created_title: @js(__('Bạn đã tạo link mua hàng')),
        waiting_platform_title: @js(__('Đang chờ sàn ghi nhận đơn')),
        waiting_platform_note: @js(__('Đơn sẽ tự động xuất hiện sau khi sàn đối soát xong.'))
    }
})">
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
        
        <!-- Sidebar -->
        <div class="hidden lg:block lg:col-span-3">
            @include('dashboard.sidebar')
        </div>

        <!-- Chi tiết -->
        <div class="lg:col-span-9 space-y-5">
            {{-- Nhắc nhở thành viên bổ sung email để bảo vệ tài khoản --}}
            @include('components.email_update_notice')

            <!-- 1. Banner Lịch Sử Hoàn Tiền Đơn Hàng Premium -->
            <div class="relative overflow-hidden p-6 bg-gradient-to-r from-[#FFF4EC] via-[#FFF9F5] to-white dark:from-slate-900/40 dark:to-slate-900/20 rounded-2xl border border-orange-100/50 dark:border-orange-950/20 shadow-sm transition-all duration-300">
                <div class="flex items-start gap-4 max-w-[70%] sm:max-w-[75%] relative z-10">
                    {{-- Icon túi mua sắm nổi bật trên nền cam nhạt --}}
                    <div class="w-12 h-12 flex items-center justify-center bg-[#FFEFEB] dark:bg-orange-950/30 dark:border dark:border-orange-100/10 text-shopee rounded-2xl shrink-0 shadow-sm">
                        <i data-lucide="shopping-bag" class="w-6 h-6"></i>
                    </div>
                    <div>
                        <h1 class="text-base sm:text-lg font-black text-gray-900 dark:text-white uppercase tracking-tight leading-none pt-1">
                            {{ __('Danh sách đơn hàng') }}
                        </h1>
                        <p class="text-[10px] sm:text-xs text-gray-500 dark:text-slate-400 mt-2.5 leading-relaxed whitespace-nowrap">
                            {{ __('Theo dõi tất cả giao dịch hoàn tiền của bạn') }}
                        </p>
                    </div>
                </div>
                
                {{-- Hình ảnh ví tiền 3D trang trí góc phải --}}
                <div class="absolute right-0 top-0 bottom-0 w-32 flex items-center justify-end pointer-events-none pr-2 sm:pr-6 z-0">
                    <img src="{{ asset('assets/images/withdraw_banner.webp') }}" 
                         class="h-20 sm:h-24 w-auto object-contain select-none drop-shadow-md" 
                         alt="Cashback">
                </div>
            </div>

            <!-- 2. Bộ lọc & Tìm kiếm thiết kế mới đồng bộ Chip Trạng Thái & Nút Bộ lọc -->
            <div class="bg-white dark:bg-slate-900 rounded-2xl border border-gray-150 dark:border-slate-800/80 shadow-sm overflow-hidden">
                <form id="cashback-filter-form" @submit.prevent="submitFilter()">
                    {{-- Select ẩn để giữ tính tương thích với file js AJAX hiện có --}}
                    <select id="cashback-status-select" class="hidden">
                        <option value=""></option>
                        @if($showPendingClicks)
                            <option value="unrecorded" {{ request('status') === 'unrecorded' ? 'selected' : '' }}></option>
                        @endif
                        <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}></option>
                        <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}></option>
                        <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}></option>
                    </select>

                    <div class="p-3 flex items-center justify-between gap-3">
                        <!-- Danh sách các Chips chọn trạng thái nhanh (Cuộn ngang mượt mà) -->
                        <div class="flex-1 min-w-0 flex items-center gap-2 overflow-x-auto scrollbar-none py-0.5">
                            <button type="button"
                                @click="activeChip = ''; document.getElementById('cashback-status-select').value = ''; submitFilter()"
                                :class="activeChip === '' ? 'bg-gradient-to-r from-orange-500 to-rose-500 text-white shadow-md shadow-shopee/10' : 'bg-gray-50 dark:bg-slate-800/40 text-gray-600 dark:text-slate-400 hover:bg-gray-100/50 dark:hover:bg-slate-800/80 border border-transparent'"
                                class="shrink-0 px-4 py-2 rounded-2xl text-xs font-bold transition-all whitespace-nowrap cursor-pointer focus:outline-none">
                                {{ __('Tất cả') }}
                            </button>
                            @if($showPendingClicks)
                                {{-- Chip lọc riêng nhóm link đã tạo nhưng sàn chưa ghi nhận đơn --}}
                                <button type="button"
                                    @click="activeChip = 'unrecorded'; document.getElementById('cashback-status-select').value = 'unrecorded'; submitFilter()"
                                    :class="activeChip === 'unrecorded' ? 'bg-blue-500 text-white shadow-md shadow-blue-500/10' : 'bg-blue-50 dark:bg-blue-950/20 text-blue-600 dark:text-blue-400 hover:bg-blue-100/50 border border-transparent'"
                                    class="shrink-0 px-4 py-2 rounded-2xl text-xs font-bold transition-all whitespace-nowrap cursor-pointer focus:outline-none">
                                    {{ __('Chờ sàn ghi nhận') }}
                                </button>
                            @endif
                            <button type="button"
                                @click="activeChip = 'pending'; document.getElementById('cashback-status-select').value = 'pending'; submitFilter()"
                                :class="activeChip === 'pending' ? 'bg-yellow-400 text-white shadow-md shadow-yellow-500/10' : 'bg-yellow-50 dark:bg-yellow-950/20 text-yellow-600 dark:text-yellow-500 hover:bg-yellow-100/50 border border-transparent'"
                                class="shrink-0 px-4 py-2 rounded-2xl text-xs font-bold transition-all whitespace-nowrap cursor-pointer focus:outline-none">
                                {{ __('Chờ duyệt') }}
                            </button>
                            <button type="button"
                                @click="activeChip = 'approved'; document.getElementById('cashback-status-select').value = 'approved'; submitFilter()"
                                :class="activeChip === 'approved' ? 'bg-green-500 text-white shadow-md shadow-green-500/10' : 'bg-green-50 dark:bg-green-950/20 text-green-600 dark:text-green-500 hover:bg-green-100/50 border border-transparent'"
                                class="shrink-0 px-4 py-2 rounded-2xl text-xs font-bold transition-all whitespace-nowrap cursor-pointer focus:outline-none">
                                {{ __('Đã duyệt') }}
                            </button>
                            <button type="button"
                                @click="activeChip = 'rejected'; document.getElementById('cashback-status-select').value = 'rejected'; submitFilter()"
                                :class="activeChip === 'rejected' ? 'bg-red-500 text-white shadow-md shadow-red-500/10' : 'bg-red-50 dark:bg-red-950/20 text-red-550 dark:text-red-400 hover:bg-red-100/50 border border-transparent'"
                                class="shrink-0 px-4 py-2 rounded-2xl text-xs font-bold transition-all whitespace-nowrap cursor-pointer focus:outline-none">
                                {{ __('Từ chối') }}
                            </button>
                        </div>

                        <!-- Vạch phân tách thẩm mỹ -->
                        <div class="shrink-0 w-px h-5 bg-gray-200 dark:bg-slate-700"></div>

                        <!-- Nút mở rộng Bộ lọc (Tìm nâng cao & Thời gian) -->
                        <button type="button" @click="searchExpanded = !searchExpanded"
                            :class="searchExpanded ? 'bg-shopee/10 text-shopee border-shopee/30 dark:bg-shopee/20 dark:text-shopee-light dark:border-shopee/40 shadow-sm' : 'bg-white dark:bg-slate-805 text-gray-650 dark:text-slate-300 border-gray-200 dark:border-slate-700'"
                            class="px-3.5 py-2 shrink-0 inline-flex items-center gap-1.5 border rounded-2xl text-xs font-bold transition-all hover:scale-[1.02] active:scale-[0.98] cursor-pointer focus:outline-none"
                            title="{{ __('Bộ lọc') }}">
                            <i data-lucide="filter" class="w-3.5 h-3.5"></i>
                            <span>{{ __('Bộ lọc') }}</span>
                            <i data-lucide="chevron-down" class="w-3 h-3 transition-transform duration-200" :class="searchExpanded ? 'rotate-180' : ''"></i>
                        </button>

                        <!-- Nút xoá bộ lọc (Chỉ hiển thị khi có từ khóa/thời gian/trạng thái đang chọn) -->
                        <button type="button" @click="resetFilters()" x-show="hasActiveFilters" x-cloak
                            class="w-9 h-9 shrink-0 inline-flex items-center justify-center text-gray-500 bg-gray-50 hover:bg-gray-100 dark:bg-slate-800 dark:text-slate-300 rounded-2xl border border-gray-200 dark:border-slate-700 transition-all hover:scale-[1.02] active:scale-[0.98] cursor-pointer focus:outline-none"
                            title="{{ __('Xoá bộ lọc') }}">
                            <i data-lucide="x" class="w-3.5 h-3.5"></i>
                        </button>
                    </div>

                    <!-- Khu vực tìm kiếm mở rộng (Accordion Collapse) -->
                    <div x-show="searchExpanded" x-collapse x-cloak>
                        <div class="border-t border-gray-100/60 dark:border-slate-800/50 p-4 space-y-3 bg-gray-50/30 dark:bg-slate-900/40">
                            <!-- Input Tìm kiếm từ khoá -->
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                                    <i data-lucide="search" class="w-3.5 h-3.5"></i>
                                </div>
                                <input type="text"
                                    id="cashback-search-input"
                                    value="{{ request('search') }}"
                                    @keydown.enter.prevent="submitFilter()"
                                    placeholder="{{ __('Tìm kiếm sản phẩm, mã đơn hàng...') }}"
                                    class="block w-full pl-9 pr-3 py-2.5 border border-gray-200 dark:border-slate-700 rounded-2xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee transition-all bg-white dark:bg-slate-800 dark:text-slate-100 placeholder:text-gray-400 dark:placeholder:text-slate-650 shadow-sm">
                            </div>

                            <!-- Lọc theo nền tảng -->
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                                    <i data-lucide="shopping-bag" class="w-3.5 h-3.5"></i>
                                </div>
                                <select id="cashback-platform-select" 
                                    @change="submitFilter()"
                                    class="block w-full pl-9 pr-8 py-2.5 border border-gray-200 dark:border-slate-700 rounded-2xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee transition-all bg-white dark:bg-slate-800 dark:text-slate-100 shadow-sm appearance-none cursor-pointer">
                                    <option value="">{{ __('Tất cả các sàn') }}</option>
                                    <option value="shopee" {{ request('platform') === 'shopee' ? 'selected' : '' }}>{{ \App\Models\Setting::getVal('shopee_platform_name', 'Shopee') }}</option>
                                    <option value="tiktok" {{ request('platform') === 'tiktok' ? 'selected' : '' }}>{{ \App\Models\Setting::getVal('tiktok_platform_name', 'TikTok Shop') }}</option>
                                    <option value="lazada" {{ request('platform') === 'lazada' ? 'selected' : '' }}>Lazada</option>
                                </select>
                                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none text-gray-400">
                                    <i data-lucide="chevron-down" class="w-3.5 h-3.5"></i>
                                </div>
                            </div>

                            <!-- Lọc theo khoảng thời gian (Từ ngày - Đến ngày) -->
                            <div class="grid grid-cols-2 gap-3">
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                                        <i data-lucide="calendar" class="w-3.5 h-3.5"></i>
                                    </div>
                                    <input type="date"
                                        id="cashback-start-date"
                                        value="{{ request('start_date') }}"
                                        @change="submitFilter()"
                                        title="{{ __('Từ ngày') }}"
                                        class="block w-full pl-9 pr-2 py-2.5 border border-gray-200 dark:border-slate-700 rounded-2xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee transition-all bg-white dark:bg-slate-800 dark:text-slate-100 shadow-sm">
                                </div>

                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                                        <i data-lucide="calendar" class="w-3.5 h-3.5"></i>
                                    </div>
                                    <input type="date"
                                        id="cashback-end-date"
                                        value="{{ request('end_date') }}"
                                        @change="submitFilter()"
                                        title="{{ __('Đến ngày') }}"
                                        class="block w-full pl-9 pr-2 py-2.5 border border-gray-200 dark:border-slate-700 rounded-2xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee transition-all bg-white dark:bg-slate-800 dark:text-slate-100 shadow-sm">
                                </div>
                            </div>

                            <!-- Nút áp dụng tìm kiếm -->
                            <button type="submit" class="w-full py-3 inline-flex items-center justify-center gap-1.5 text-white bg-gradient-to-r from-shopee to-shopee-light hover:brightness-110 rounded-2xl text-xs font-bold transition-all shadow-md shadow-shopee/10 active:scale-[0.99] cursor-pointer">
                                <svg x-show="loading" class="animate-spin w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" style="display: none;">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                                <i x-show="!loading" data-lucide="search" class="w-3.5 h-3.5"></i>
                                <span>{{ __('Tìm kiếm') }}</span>
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            @if($showPendingClicks)
                <!-- Ghi chú trấn an khách hàng về độ trễ đối soát đơn hàng của sàn thương mại điện tử -->
                <div class="flex items-start gap-2.5 p-3.5 bg-blue-50/70 dark:bg-blue-950/20 border border-blue-100 dark:border-blue-900/30 rounded-2xl">
                    <div class="w-6 h-6 rounded-lg bg-blue-500/10 text-blue-500 flex items-center justify-center shrink-0">
                        <i data-lucide="info" class="w-3.5 h-3.5"></i>
                    </div>
                    <p class="text-[11px] leading-relaxed text-blue-700/90 dark:text-blue-300/90">
                        {{ __('Các đơn có nhãn "Chờ sàn ghi nhận" là sản phẩm bạn vừa tạo link mua hàng. Sàn thương mại điện tử thường cần từ vài giờ đến vài ngày để đối soát và trả dữ liệu đơn hàng về hệ thống, sau đó đơn sẽ tự động chuyển sang trạng thái chờ duyệt. Số tiền hoàn hiển thị ở nhóm này chỉ là ước tính.') }}
                    </p>
                </div>
            @endif

            <!-- Container Danh Sách Lịch Sử Hoàn Tiền Load Ajax -->
            <div id="cashback-list-container" class="transition-all duration-300">
                @include('dashboard.partials.cashback_list')
            </div>

        </div>
    </div>

    <!-- Modal Chi Tiết Đơn Hàng — Bottom Sheet trên Mobile, Popup trên Desktop -->
    <div x-show="openModal"
         @keydown.escape.window="openModal = false"
         x-init="$watch('openModal', value => { if (value) { setTimeout(() => { if (window.lucide) window.lucide.createIcons(); }, 50); } })"
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
        <div class="relative bg-white dark:bg-slate-900 w-full sm:max-w-lg rounded-t-3xl sm:rounded-2xl shadow-2xl z-10 border-t border-x border-gray-100 dark:border-slate-800 sm:border max-h-[92vh] flex flex-col"
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
                <div class="flex items-center gap-2 min-w-0">
                    <template x-if="selectedItem && selectedItem.platform === 'shopee'">
                        <span class="inline-flex items-center px-2 py-1 rounded-lg text-[9px] font-extrabold text-white bg-shopee shadow-sm shrink-0 leading-none">{{ \App\Models\Setting::getVal('shopee_platform_name', 'Shopee') }}</span>
                    </template>
                    <template x-if="selectedItem && selectedItem.platform === 'tiktok'">
                        <span class="inline-flex items-center px-2 py-1 rounded-lg text-[9px] font-extrabold bg-black text-white shadow-sm shrink-0 leading-none">{{ \App\Models\Setting::getVal('tiktok_platform_name', 'TikTok Shop') }}</span>
                    </template>
                    <template x-if="selectedItem && selectedItem.platform === 'lazada'">
                        <span class="inline-flex items-center px-2 py-1 rounded-lg text-[9px] font-extrabold bg-blue-700 text-white shadow-sm shrink-0 leading-none">Lazada</span>
                    </template>
                    {{-- Đơn chưa được sàn ghi nhận chưa có mã đơn hàng nên hiển thị tạm mã đối soát --}}
                    <span class="text-xs font-mono font-bold text-gray-500 dark:text-slate-400 truncate" x-text="'#' + (selectedItem ? (selectedItem.order_id || selectedItem.trans_id) : '')"></span>
                </div>
                <button @click="openModal = false"
                    class="w-8 h-8 flex items-center justify-center rounded-xl text-gray-400 dark:text-slate-500 hover:bg-gray-100 dark:hover:bg-slate-800 hover:text-gray-600 dark:hover:text-slate-300 transition-all shrink-0 ml-2">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>

            <!-- Body (scrollable) -->
            <div class="overflow-y-auto flex-1 px-5 pb-2">
                <template x-if="selectedItem">
                    <div class="space-y-4 pb-1">

                        <!-- Sản phẩm -->
                        <div class="flex gap-3.5 p-3.5 bg-gray-50 dark:bg-slate-800/40 rounded-2xl border border-gray-100/60 dark:border-slate-700/40">
                            <div class="relative w-[72px] h-[72px] shrink-0 rounded-xl overflow-hidden bg-gray-100 dark:bg-slate-800 border border-gray-200 dark:border-slate-700 flex items-center justify-center">
                                <div class="absolute inset-0 bg-gray-100 dark:bg-slate-800 flex items-center justify-center text-gray-400">
                                    <i data-lucide="image" class="w-6 h-6"></i>
                                </div>
                                <img :src="selectedItem.product_image"
                                    alt="{{ __('Ảnh sản phẩm') }}"
                                    class="absolute inset-0 w-full h-full object-cover z-10"
                                    onerror="this.style.display='none';">
                            </div>
                            <div class="min-w-0 flex-1 space-y-1.5 pt-0.5">
                                <h4 class="text-xs font-bold text-gray-900 dark:text-white leading-snug line-clamp-3" x-text="selectedItem.product_name"></h4>
                                <template x-if="selectedItem.shop_name">
                                    <div class="flex items-center gap-1 text-[10px] text-orange-600 dark:text-orange-400 font-semibold w-max max-w-full">
                                        <i data-lucide="store" class="w-3 h-3 shrink-0"></i>
                                        <span class="truncate" x-text="selectedItem.shop_name"></span>
                                    </div>
                                </template>
                                <a :href="selectedItem.affiliate_url" target="_blank"
                                    class="inline-flex items-center gap-1 text-[10px] text-shopee hover:underline font-semibold">
                                    <span>{{ __('Link mua hàng') }}</span>
                                    <i data-lucide="external-link" class="w-2.5 h-2.5"></i>
                                </a>
                            </div>
                        </div>

                        <!-- Số tiền hoàn nổi bật -->
                        <div class="flex items-center justify-between p-4 bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-950/20 dark:to-emerald-950/20 rounded-2xl border border-green-100/70 dark:border-green-900/30">
                            <div>
                                <p class="text-[10px] text-green-600/70 dark:text-green-400/60 font-medium mb-0.5">{{ __('Hoàn tiền ước tính') }}</p>
                                <p class="text-[22px] font-extrabold text-green-600 dark:text-green-400 leading-tight tabular-nums" x-text="(selectedItem.status === 'unrecorded' ? '~' : '+') + selectedItem.cashback_amount"></p>
                            </div>
                            <div class="w-11 h-11 rounded-2xl bg-green-500/10 dark:bg-green-400/10 flex items-center justify-center shrink-0">
                                <i data-lucide="wallet" class="w-5 h-5 text-green-500 dark:text-green-400"></i>
                            </div>
                        </div>

                        <!-- Ghi chú dành riêng cho đơn sàn chưa ghi nhận -->
                        <template x-if="selectedItem.status === 'unrecorded'">
                            <div class="flex items-start gap-2.5 p-3.5 bg-blue-50/70 dark:bg-blue-950/20 border border-blue-100 dark:border-blue-900/30 rounded-2xl">
                                <i data-lucide="clock" class="w-3.5 h-3.5 text-blue-500 shrink-0 mt-px"></i>
                                <p class="text-[11px] leading-relaxed text-blue-700/90 dark:text-blue-300/90">
                                    {{ __('Sàn chưa đối soát xong đơn hàng này. Nếu bạn đã mua hàng thành công qua link trên, đơn sẽ tự động xuất hiện với trạng thái chờ duyệt trong vòng vài giờ đến vài ngày. Số tiền hoàn phía trên chỉ là ước tính.') }}
                                </p>
                            </div>
                        </template>

                        <!-- Stats: Giá gốc + Lượt click -->
                        <div class="grid gap-2.5" :class="selectedItem.show_price !== '0' ? 'grid-cols-2' : 'grid-cols-1'">
                            <div x-show="selectedItem.show_price !== '0'" class="bg-gray-50 dark:bg-slate-800/40 rounded-xl p-3 flex items-center gap-2.5 border border-gray-100/50 dark:border-slate-700/30">
                                <div class="w-7 h-7 rounded-lg bg-blue-500/10 text-blue-500 flex items-center justify-center shrink-0">
                                    <i data-lucide="tag" class="w-3.5 h-3.5"></i>
                                </div>
                                <div class="min-w-0">
                                    <p class="text-[9px] text-gray-400 dark:text-slate-500 font-medium">{{ __('Giá gốc') }}</p>
                                    <p class="text-[11px] font-bold text-gray-700 dark:text-slate-200 tabular-nums truncate" x-text="selectedItem.original_price"></p>
                                </div>
                            </div>
                            <div class="bg-gray-50 dark:bg-slate-800/40 rounded-xl p-3 flex items-center gap-2.5 border border-gray-100/50 dark:border-slate-700/30">
                                <div class="w-7 h-7 rounded-lg bg-purple-500/10 text-purple-500 flex items-center justify-center shrink-0">
                                    <i data-lucide="mouse-pointer-click" class="w-3.5 h-3.5"></i>
                                </div>
                                <div>
                                    <p class="text-[9px] text-gray-400 dark:text-slate-500 font-medium">{{ __('Lượt click') }}</p>
                                    <p class="text-[11px] font-bold text-gray-700 dark:text-slate-200" x-text="selectedItem.clicks + ' click'"></p>
                                </div>
                            </div>
                        </div>

                        <!-- Thông tin chi tiết (grouped rows) -->
                        <div class="border border-gray-100 dark:border-slate-800/60 rounded-2xl overflow-hidden divide-y divide-gray-50 dark:divide-slate-800/60">
                            <!-- Trạng thái -->
                            <div class="flex items-center justify-between px-4 py-3 bg-white dark:bg-slate-900">
                                <span class="text-[11px] text-gray-400 dark:text-slate-500 font-medium">{{ __('Trạng thái') }}</span>
                                <template x-if="selectedItem.status === 'unrecorded'">
                                    <div class="flex items-center gap-1.5">
                                        <span class="w-1.5 h-1.5 rounded-full bg-blue-400 animate-pulse shrink-0"></span>
                                        <span class="text-[11px] font-bold text-blue-600 dark:text-blue-400">{{ __('Chờ sàn ghi nhận') }}</span>
                                    </div>
                                </template>
                                <template x-if="selectedItem.status === 'pending'">
                                    <div class="flex items-center gap-1.5">
                                        <span class="w-1.5 h-1.5 rounded-full bg-yellow-400 animate-pulse shrink-0"></span>
                                        <span class="text-[11px] font-bold text-yellow-600 dark:text-yellow-500">{{ __('Chờ duyệt') }}</span>
                                    </div>
                                </template>
                                <template x-if="selectedItem.status === 'approved'">
                                    <div class="flex items-center gap-1.5">
                                        <span class="w-1.5 h-1.5 rounded-full bg-green-500 shrink-0"></span>
                                        <span class="text-[11px] font-bold text-green-600 dark:text-green-400">{{ __('Đã cộng ví') }}</span>
                                    </div>
                                </template>
                                <template x-if="selectedItem.status === 'rejected'">
                                    <div class="flex items-center gap-1.5">
                                        <span class="w-1.5 h-1.5 rounded-full bg-red-400 shrink-0"></span>
                                        <span class="text-[11px] font-bold text-red-500 dark:text-red-400">{{ __('Bị từ chối') }}</span>
                                    </div>
                                </template>
                            </div>

                            <!-- Mã đơn hàng + copy (chỉ có ở đơn đã được sàn ghi nhận) -->
                            <template x-if="selectedItem.order_id">
                                <div class="flex items-center justify-between px-4 py-3 bg-white dark:bg-slate-900">
                                    <span class="text-[11px] text-gray-400 dark:text-slate-500 font-medium shrink-0">{{ __('Mã đơn hàng') }}</span>
                                    <div class="flex items-center gap-1.5 min-w-0 ml-4">
                                        <span class="text-[11px] font-mono font-bold text-gray-800 dark:text-slate-200 truncate" x-text="selectedItem.order_id"></span>
                                        <button
                                            @click="navigator.clipboard.writeText(selectedItem.order_id); $dispatch('toast', { text: '{{ __('Đã sao chép mã đơn Shopee!') }}', type: 'success' })"
                                            class="text-gray-300 dark:text-slate-600 hover:text-shopee dark:hover:text-shopee-light transition-colors shrink-0"
                                            title="{{ __('Sao chép') }}">
                                            <i data-lucide="copy" class="w-3.5 h-3.5"></i>
                                        </button>
                                    </div>
                                </div>
                            </template>

                            <!-- Mã đối soát (nếu có) -->
                            <template x-if="selectedItem.trans_id">
                                <div class="flex items-center justify-between px-4 py-3 bg-white dark:bg-slate-900">
                                    <span class="text-[11px] text-gray-400 dark:text-slate-500 font-medium shrink-0">{{ __('Mã đối soát') }}</span>
                                    <span class="text-[11px] font-mono text-gray-600 dark:text-slate-400 ml-4 truncate" x-text="selectedItem.trans_id"></span>
                                </div>
                            </template>

                            <!-- Ngày duyệt (nếu đã duyệt) -->
                            <template x-if="selectedItem.status === 'approved' && selectedItem.approved_at">
                                <div class="flex items-center justify-between px-4 py-3 bg-white dark:bg-slate-900">
                                    <span class="text-[11px] text-gray-400 dark:text-slate-500 font-medium">{{ __('Ngày duyệt') }}</span>
                                    <span class="text-[11px] font-semibold text-green-600 dark:text-green-400" x-text="selectedItem.approved_at"></span>
                                </div>
                            </template>

                            <!-- Thời gian tạo -->
                            <div class="flex items-center justify-between px-4 py-3 bg-white dark:bg-slate-900">
                                <span class="text-[11px] text-gray-400 dark:text-slate-500 font-medium">{{ __('Thời gian tạo') }}</span>
                                <span class="text-[11px] font-semibold text-gray-600 dark:text-slate-400" x-text="selectedItem.created_at"></span>
                            </div>
                        </div>
                        
                        <!-- Dòng thời gian trạng thái -->
                        <div class="p-4 bg-gray-50 dark:bg-slate-800/40 rounded-2xl border border-gray-100/60 dark:border-slate-700/40 space-y-3">
                            <h4 class="font-bold text-gray-900 dark:text-white uppercase tracking-wider text-[10px] border-b border-gray-100 dark:border-slate-800/50 pb-1.5 flex items-center gap-1.5">
                                <i data-lucide="history" class="w-3.5 h-3.5 text-shopee"></i>
                                {{ __('Dòng thời gian trạng thái') }}
                            </h4>

                            <div class="relative pl-1">
                                <template x-for="(ev, idx) in buildTimeline()" :key="idx">
                                    <div class="relative flex gap-3 pb-4 last:pb-0">
                                        <!-- Đường kẻ dọc nối các mốc (ẩn ở mốc cuối) -->
                                        <div x-show="idx < buildTimeline().length - 1" class="absolute left-[11px] top-5 bottom-0 w-px bg-gray-200 dark:bg-slate-700"></div>
                                        <!-- Chấm mốc thời gian -->
                                        <div class="relative z-10 shrink-0 w-[23px] h-[23px] rounded-full flex items-center justify-center border-2 bg-white dark:bg-slate-900"
                                             :class="ev.ring">
                                            <i :data-lucide="ev.icon" class="w-3 h-3" :class="ev.iconColor"></i>
                                        </div>
                                        <!-- Nội dung mốc -->
                                        <div class="flex-1 min-w-0 -mt-0.5">
                                            <div class="flex items-center justify-between gap-2">
                                                <p class="font-bold text-[11px]" :class="ev.titleColor" x-text="ev.title"></p>
                                                <span class="text-[9px] text-gray-400 dark:text-gray-500 shrink-0" x-text="ev.at"></span>
                                            </div>
                                            <template x-if="ev.source">
                                                <p class="text-[9px] text-gray-500 dark:text-gray-400 mt-0.5" x-text="ev.source"></p>
                                            </template>
                                            <template x-if="ev.amount">
                                                <p class="text-[9px] font-semibold mt-0.5" :class="ev.titleColor" x-text="ev.amount"></p>
                                            </template>
                                            <template x-if="ev.reason">
                                                <p class="text-[9px] text-gray-500 dark:text-gray-400 mt-1 p-2 rounded-lg bg-gray-50 dark:bg-slate-800/40 border border-gray-100 dark:border-slate-800 break-words" x-text="ev.reason"></p>
                                            </template>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <!-- Lý do từ chối (nếu bị từ chối) -->
                        <template x-if="selectedItem.status === 'rejected' && selectedItem.rejected_reason">
                            <div class="p-3.5 bg-red-50 dark:bg-red-950/20 border border-red-100 dark:border-red-900/40 rounded-2xl">
                                <p class="text-[11px] font-bold text-red-600 dark:text-red-400 flex items-center gap-1.5 mb-1.5">
                                    <i data-lucide="alert-circle" class="w-3.5 h-3.5 shrink-0"></i>
                                    {{ __('Lý do từ chối') }}
                                </p>
                                <p class="text-[11px] text-red-600/80 dark:text-red-400/80 leading-relaxed" x-text="selectedItem.rejected_reason"></p>
                            </div>
                        </template>

                    </div>
                </template>
            </div>

            <!-- Footer (sticky) -->
            <div class="shrink-0 px-5 pt-3 pb-5 sm:pb-4 border-t border-gray-100 dark:border-slate-800 bg-white dark:bg-slate-900">
                <template x-if="selectedItem">
                    <a :href="selectedItem.affiliate_url" target="_blank"
                        class="flex items-center justify-center gap-2 w-full py-3.5 sm:py-3 text-sm font-bold text-white bg-gradient-to-r from-shopee to-shopee-light rounded-2xl shadow-md shadow-shopee/15 hover:brightness-105 active:scale-[0.98] transition-all">
                        <i data-lucide="shopping-cart" class="w-4 h-4"></i>
                        <span x-text="selectedItem.status === 'unrecorded' ? @js(__('Mua ngay sản phẩm')) : @js(__('Mua lại sản phẩm'))"></span>
                    </a>
                </template>
                <button @click="openModal = false"
                    class="mt-2.5 w-full py-2 text-xs font-semibold text-gray-400 dark:text-slate-500 hover:text-gray-600 dark:hover:text-slate-300 transition-colors">
                    {{ __('Đóng') }}
                </button>
            </div>

        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="{{ asset('js/cashback.js') }}?v=1.1.4"></script>
@endsection
