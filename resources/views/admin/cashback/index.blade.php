@extends('layouts.admin')

@section('title', __('Quản Lý Đơn Hoàn Tiền - Admin Panel'))

@php
    $selectableCashbackIds = $histories->pluck('id')->toArray();
    $hasFilter = request('order_code') || request('product_name') || request('user_search') || request('platform') || request('status') || request('start_date') || request('end_date') || (request('limit') && request('limit') != 15);
@endphp

@section('content')
<div class="space-y-6" x-data="cashbackAdminHandler()">
    <!-- Tiêu đề & Hành động -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ __('Đơn Hàng Hoàn Tiền Shopee') }}</h1>
            <p class="text-sm text-gray-500">{{ __('Quản lý đơn hàng hoàn tiền do người dùng dán link và mua sắm phát sinh') }}</p>
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
            <!-- Nút đồng bộ API Shopee + TikTok + Lazada -->
            @if(!config('app.demo'))
            <button @click="openSyncApiModal()"
                    class="inline-flex items-center gap-1.5 px-4 py-2.5 text-xs font-semibold text-white bg-emerald-600 hover:bg-emerald-700 rounded-xl transition-all shadow-md">
                <i data-lucide="refresh-cw" class="w-4 h-4"></i>
                {{ __('Sync API') }}
            </button>
            @endif
            <!-- Nút mở modal thống kê đơn hoàn tiền -->
            <button @click="openCashbackStatsModal()"
                    class="inline-flex items-center gap-1.5 px-4 py-2.5 text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded-xl transition-all shadow-md">
                <i data-lucide="bar-chart-3" class="w-4 h-4"></i>
                {{ __('Thống kê') }}
            </button>
        </div>
    </div>

    <!-- Tabs trạng thái lọc nhanh -->
    <div class="flex flex-wrap items-center gap-2 bg-gray-50 dark:bg-slate-800/40 p-1.5 rounded-2xl border border-gray-200/60 dark:border-slate-800/80">
        <!-- Tất cả -->
        <a href="{{ route('admin.cashback.index', request()->except(['status', 'page'])) }}" 
           @click.prevent="loadTab('{{ route('admin.cashback.index', request()->except(['status', 'page'])) }}', '')"
           class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-bold rounded-xl transition-all"
           :class="!activeStatus ? 'bg-shopee text-white shadow-sm' : 'hover:bg-white dark:hover:bg-slate-800 text-gray-650 dark:text-gray-400'">
            <span>{{ __('Tất cả') }}</span>
            <span class="px-1.5 py-0.5 rounded-md text-[10px]"
                  :class="!activeStatus ? 'bg-white/20 text-white font-extrabold' : 'bg-gray-200/70 dark:bg-slate-700 text-gray-500 dark:text-gray-400'"
                  x-text="statusCounts.all">
            </span>
        </a>

        <!-- Chờ duyệt -->
        <a href="{{ route('admin.cashback.index', array_merge(request()->except('page'), ['status' => 'pending'])) }}" 
           @click.prevent="loadTab('{{ route('admin.cashback.index', array_merge(request()->except('page'), ['status' => 'pending'])) }}', 'pending')"
           class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-bold rounded-xl transition-all"
           :class="activeStatus === 'pending' ? 'bg-amber-500 text-white shadow-sm' : 'hover:bg-white dark:hover:bg-slate-800 text-gray-650 dark:text-gray-400'">
            <span>{{ __('Chờ duyệt') }}</span>
            <span class="px-1.5 py-0.5 rounded-md text-[10px]"
                  :class="activeStatus === 'pending' ? 'bg-white/20 text-white font-extrabold' : 'bg-amber-50 dark:bg-amber-950/20 text-amber-600 dark:text-amber-400 border border-amber-100 dark:border-amber-900/30'"
                  x-text="statusCounts.pending">
            </span>
        </a>

        <!-- Thành công (Đã duyệt) -->
        <a href="{{ route('admin.cashback.index', array_merge(request()->except('page'), ['status' => 'approved'])) }}" 
           @click.prevent="loadTab('{{ route('admin.cashback.index', array_merge(request()->except('page'), ['status' => 'approved'])) }}', 'approved')"
           class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-bold rounded-xl transition-all"
           :class="activeStatus === 'approved' ? 'bg-emerald-600 text-white shadow-sm' : 'hover:bg-white dark:hover:bg-slate-800 text-gray-650 dark:text-gray-400'">
            <span>{{ __('Thành công') }}</span>
            <span class="px-1.5 py-0.5 rounded-md text-[10px]"
                  :class="activeStatus === 'approved' ? 'bg-white/20 text-white font-extrabold' : 'bg-emerald-50 dark:bg-emerald-950/20 text-emerald-600 dark:text-emerald-400 border border-emerald-100 dark:border-emerald-900/30'"
                  x-text="statusCounts.approved">
            </span>
        </a>

        <!-- Bị từ chối -->
        <a href="{{ route('admin.cashback.index', array_merge(request()->except('page'), ['status' => 'rejected'])) }}" 
           @click.prevent="loadTab('{{ route('admin.cashback.index', array_merge(request()->except('page'), ['status' => 'rejected'])) }}', 'rejected')"
           class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-bold rounded-xl transition-all"
           :class="activeStatus === 'rejected' ? 'bg-rose-600 text-white shadow-sm' : 'hover:bg-white dark:hover:bg-slate-800 text-gray-650 dark:text-gray-400'">
            <span>{{ __('Bị từ chối') }}</span>
            <span class="px-1.5 py-0.5 rounded-md text-[10px]"
                  :class="activeStatus === 'rejected' ? 'bg-white/20 text-white font-extrabold' : 'bg-rose-50 dark:bg-rose-950/20 text-rose-600 dark:text-rose-400 border border-rose-100 dark:border-rose-900/30'"
                  x-text="statusCounts.rejected">
            </span>
        </a>
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
        <form id="cashback-filter-form" action="{{ route('admin.cashback.index') }}" method="GET" @submit.prevent="submitFilterForm($event)" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-9 gap-3">
            <!-- Lưu vết cột sắp xếp và hướng sắp xếp hiện tại để AJAX gửi lên controller -->
            <input type="hidden" name="sort_by" value="{{ request('sort_by', 'created_at') }}">
            <input type="hidden" name="sort_order" value="{{ request('sort_order', 'desc') }}">
            <!-- Mã đơn / Mã GD -->
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                    <i data-lucide="hash" class="w-4 h-4"></i>
                </div>
                <input type="text" 
                       name="order_code" 
                       value="{{ request('order_code') }}"
                       placeholder="{{ __('Mã đơn / Mã GD...') }}" 
                       class="block w-full h-9 pl-9 pr-4 py-2 border border-gray-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee transition-all bg-gray-50/50">
            </div>

            <!-- Tên sản phẩm -->
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                    <i data-lucide="shopping-bag" class="w-4 h-4"></i>
                </div>
                <input type="text" 
                       name="product_name" 
                       value="{{ request('product_name') }}"
                       placeholder="{{ __('Tên sản phẩm...') }}" 
                       class="block w-full h-9 pl-9 pr-4 py-2 border border-gray-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee transition-all bg-gray-50/50">
            </div>

            <!-- Khách hàng (Tên/Email) -->
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                    <i data-lucide="user" class="w-4 h-4"></i>
                </div>
                <input type="text" 
                       name="user_search" 
                       value="{{ request('user_search') }}"
                       placeholder="{{ __('Khách hàng (Tên/Email)...') }}" 
                       class="block w-full h-9 pl-9 pr-4 py-2 border border-gray-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee transition-all bg-gray-50/50">
            </div>

            <!-- Nền tảng -->
            <div>
                <select name="platform" class="block w-full h-9 px-3 py-2 border border-gray-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-gray-50/50">
                    <option value="">{{ __('Tất cả nền tảng') }}</option>
                    <option value="shopee" {{ request('platform') == 'shopee' ? 'selected' : '' }}>{{ \App\Models\Setting::getVal('shopee_platform_name', 'Shopee') }}</option>
                    <option value="tiktok" {{ request('platform') == 'tiktok' ? 'selected' : '' }}>{{ \App\Models\Setting::getVal('tiktok_platform_name', 'TikTok Shop') }}</option>
                    <option value="lazada" {{ request('platform') == 'lazada' ? 'selected' : '' }}>{{ \App\Models\Setting::getVal('lazada_platform_name', 'Lazada') }}</option>
                </select>
            </div>

            <!-- Trạng thái -->
            <div>
                <select name="status" x-model="activeStatus" class="block w-full h-9 px-3 py-2 border border-gray-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-gray-50/50">
                    <option value="">{{ __('Tất cả trạng thái') }}</option>
                    <option value="pending">{{ __('Chờ duyệt (Pending)') }}</option>
                    <option value="approved">{{ __('Đã duyệt (Approved)') }}</option>
                    <option value="rejected">{{ __('Bị từ chối (Rejected)') }}</option>
                </select>
            </div>

            <!-- Từ ngày -->
            <!-- Cho phép lọc các bản ghi hoàn tiền được tạo bắt đầu từ ngày này -->
            <!-- Dùng nhãn nổi vì input type=date không hỗ trợ placeholder, tránh hiển thị ô trống trơn trên mobile -->
            <div class="relative">
                <label for="cashback-start-date" class="absolute -top-2 left-3 z-10 px-1 bg-white text-[10px] font-medium text-gray-400 pointer-events-none">{{ __('Từ ngày') }}</label>
                <input type="date"
                       id="cashback-start-date"
                       name="start_date"
                       value="{{ request('start_date') }}"
                       class="block w-full h-9 px-3 py-2 border border-gray-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee transition-all bg-gray-50/50"
                       title="{{ __('Từ ngày') }}">
            </div>

            <!-- Đến ngày -->
            <!-- Cho phép lọc các bản ghi hoàn tiền được tạo cho đến ngày này -->
            <!-- Dùng nhãn nổi vì input type=date không hỗ trợ placeholder, tránh hiển thị ô trống trơn trên mobile -->
            <div class="relative">
                <label for="cashback-end-date" class="absolute -top-2 left-3 z-10 px-1 bg-white text-[10px] font-medium text-gray-400 pointer-events-none">{{ __('Đến ngày') }}</label>
                <input type="date"
                       id="cashback-end-date"
                       name="end_date"
                       value="{{ request('end_date') }}"
                       class="block w-full h-9 px-3 py-2 border border-gray-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee transition-all bg-gray-50/50"
                       title="{{ __('Đến ngày') }}">
            </div>

            <!-- Số dòng hiển thị -->
            <div>
                <select name="limit" class="block w-full h-9 px-3 py-2 border border-gray-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-gray-50/50">
                    <option value="15" {{ request('limit') == 15 ? 'selected' : '' }}>{{ __('15 dòng / trang') }}</option>
                    <option value="30" {{ request('limit') == 30 ? 'selected' : '' }}>{{ __('30 dòng / trang') }}</option>
                    <option value="50" {{ request('limit') == 50 ? 'selected' : '' }}>{{ __('50 dòng / trang') }}</option>
                    <option value="100" {{ request('limit') == 100 ? 'selected' : '' }}>{{ __('100 dòng / trang') }}</option>
                    <option value="200" {{ request('limit') == 200 ? 'selected' : '' }}>{{ __('200 dòng / trang') }}</option>
                    <option value="500" {{ request('limit') == 500 ? 'selected' : '' }}>{{ __('500 dòng / trang') }}</option>
                </select>
            </div>

            <div class="flex gap-2">
                <button type="submit" class="flex-1 h-9 px-5 text-xs font-semibold text-white bg-shopee hover:bg-shopee-dark rounded-xl transition-all shadow-md shrink-0">
                    {{ __('Lọc') }}
                </button>
                <button type="button" 
                        @click="resetFilter()"
                        class="flex-1 h-9 px-4 text-xs font-semibold text-gray-500 hover:text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-xl transition-all shadow-sm shrink-0 flex items-center justify-center gap-1">
                    <i data-lucide="refresh-ccw" class="w-3.5 h-3.5"></i>
                    <span>{{ __('Xóa lọc') }}</span>
                </button>
            </div>
        </form>
    </div>

    <!-- Bảng Đơn Hàng Wrapper -->
    <div id="cashback-table-container" class="relative">
        <!-- Hiệu ứng loading mờ khi tải AJAX -->
        <div id="cashback-loading" class="absolute inset-0 bg-white/50 dark:bg-slate-900/50 backdrop-blur-sm z-30 flex items-center justify-center transition-opacity duration-300 opacity-0 pointer-events-none rounded-3xl">
            <div class="flex flex-col items-center gap-2">
                <svg class="w-8 h-8 animate-spin text-shopee" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span class="text-xs font-semibold text-gray-500">{{ __('Đang tải dữ liệu...') }}</span>
            </div>
        </div>
        
        <div id="cashback-table-content">
            @include('admin.cashback.partials.table')
        </div>
    </div>

        <!-- MODAL XÁC NHẬN DUYỆT ĐƠN HÀNG -->
    <template x-teleport="body">
        <div x-show="approveModalOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-slate-950/40 backdrop-blur-sm" x-transition>
            <div class="bg-white dark:bg-slate-900 rounded-3xl max-w-md w-full shadow-2xl border border-gray-100 dark:border-slate-800 overflow-hidden" @click.away="approveModalOpen = false">
                <div class="px-6 py-4 bg-gray-50 dark:bg-slate-800/50 border-b border-gray-100 dark:border-slate-800 flex justify-between items-center">
                    <h3 class="font-bold text-gray-950 dark:text-white text-sm flex items-center gap-1.5">
                        <i data-lucide="check-circle-2" class="w-4 h-4 text-green-600"></i>
                        {{ __('Xác nhận duyệt đơn hoàn tiền') }}
                    </h3>
                    <button @click="approveModalOpen = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                        <i data-lucide="x" class="w-4 h-4"></i>
                    </button>
                </div>
                
                <form :action="'/' + window.adminPrefix + '/cashback/' + activeApproveCashback.id + '/approve'" method="POST" class="p-6 space-y-4">
                    @csrf
                    
                    <div class="text-xs text-gray-600 dark:text-gray-300 space-y-2">
                        <p class="text-gray-500 dark:text-gray-400">{{ __('Hệ thống sẽ tiến hành duyệt đơn hàng và cộng số tiền hoàn dưới đây vào ví khả dụng của khách hàng, đồng thời trích hoa hồng MLM F1/F2 (nếu có).') }}</p>
                        
                        <div class="p-3.5 bg-green-50 dark:bg-green-950/20 border border-green-100 dark:border-green-900/50 rounded-2xl text-[10px] text-green-800 dark:text-green-300 space-y-1">
                            <!-- Hiển thị mã giao dịch ngẫu nhiên lúc đầu tạo link thay vì order_id Shopee -->
                            <div>{{ __('Mã giao dịch:') }} <strong class="font-mono text-gray-900 dark:text-white" x-text="activeApproveCashback.trans_id || activeApproveCashback.order_id"></strong></div>
                            <template x-if="activeApproveCashback.trans_id && activeApproveCashback.order_id && activeApproveCashback.trans_id !== activeApproveCashback.order_id">
                                <div>{{ __('Mã đơn Shopee:') }} <span class="font-mono" x-text="activeApproveCashback.order_id"></span></div>
                            </template>
                            <div class="truncate">{{ __('Sản phẩm:') }} <span x-text="activeApproveCashback.product_name"></span></div>
                            <div>{{ __('Khách hàng:') }} <span x-text="activeApproveCashback.user?.name"></span> (<span class="font-mono" x-text="activeApproveCashback.user?.email"></span>)</div>
                            <div class="pt-1.5 border-t border-green-100/50 dark:border-green-900/30 flex justify-between items-center text-xs">
                                <span>{{ __('Số tiền hoàn trả:') }}</span>
                                <strong class="text-sm text-green-700 dark:text-green-400" x-text="formatCurrency(activeApproveCashback.cashback_amount)"></strong>
                            </div>
                        </div>
                    </div>

                    <!-- Tùy chỉnh số tiền cashback -->
                    <div class="space-y-1.5">
                        <label for="cashback_amount" class="block text-xs font-bold text-gray-700 dark:text-slate-300 uppercase tracking-wider font-semibold">{{ __('Số tiền hoàn trả tùy chỉnh (đ)') }}</label>
                        <div class="relative">
                            <input type="number" 
                                   name="cashback_amount" 
                                   id="cashback_amount" 
                                   x-model.number="activeApproveCashback.cashback_amount" 
                                   required 
                                   min="0" 
                                   step="1"
                                   class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-800 rounded-xl text-xs bg-white dark:bg-slate-900 focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee text-gray-900 dark:text-white font-semibold">
                            <div class="absolute inset-y-0 right-0 pr-3.5 flex items-center pointer-events-none text-gray-450 dark:text-slate-500 text-xs font-bold">
                                VNĐ
                            </div>
                        </div>
                        <p class="text-[10px] text-gray-400 dark:text-gray-500">{{ __('Mặc định hiển thị số tiền hoàn đề xuất. Bạn có thể chỉnh sửa số tiền này trước khi duyệt.') }}</p>
                    </div>

                    <div class="pt-2 flex justify-end gap-2 border-t border-gray-100 dark:border-slate-800">
                        <button type="button" @click="approveModalOpen = false" class="px-4 py-2 text-xs font-semibold text-gray-700 dark:text-slate-300 bg-gray-100 dark:bg-slate-850 hover:bg-gray-200 dark:hover:bg-slate-800 rounded-xl transition-all">{{ __('Huỷ') }}</button>
                        <button type="submit" class="px-5 py-2 text-xs font-semibold text-white bg-green-600 hover:bg-green-700 rounded-xl transition-all shadow-md flex items-center gap-1">
                            <i data-lucide="check" class="w-3.5 h-3.5"></i>
                            {{ __('Đồng ý duyệt') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </template>

    <!-- MODAL TỪ CHỐI DUYỆT ĐƠN HÀNG -->
    <template x-teleport="body">
        <div x-show="rejectModalOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-slate-950/40 backdrop-blur-sm" x-transition>
            <div class="bg-white rounded-3xl max-w-md w-full shadow-2xl border border-gray-100 overflow-hidden" @click.away="rejectModalOpen = false">
            <div class="px-6 py-4 bg-gray-50 border-b border-gray-100 flex justify-between items-center">
                <h3 class="font-bold text-gray-950 text-sm">{{ __('Từ chối duyệt đơn hoàn tiền') }}</h3>
                <button @click="rejectModalOpen = false" class="text-gray-400 hover:text-gray-600"><i data-lucide="x" class="w-4 h-4"></i></button>
            </div>
            
            <form :action="'/' + window.adminPrefix + '/cashback/' + activeCashback.id + '/reject'" method="POST" class="p-6 space-y-4">
                @csrf
                
                <div class="p-3 bg-red-50 border border-red-100 rounded-2xl text-[10px] text-red-800">
                    <!-- Hiển thị mã giao dịch ngẫu nhiên lúc đầu tạo link thay vì order_id Shopee -->
                    {{ __('Mã giao dịch:') }} <strong x-text="activeCashback.trans_id || activeCashback.order_id"></strong> <br>
                    <template x-if="activeCashback.trans_id && activeCashback.order_id && activeCashback.trans_id !== activeCashback.order_id">
                        <span>{{ __('Mã đơn Shopee:') }} <span class="font-mono" x-text="activeCashback.order_id"></span></span> <br>
                    </template>
                    {{ __('Sản phẩm:') }} <span x-text="activeCashback.product_name"></span> <br>
                    {{ __('Số tiền hoàn:') }} <strong x-text="formatCurrency(activeCashback.cashback_amount)"></strong>
                </div>

                <!-- Lý do -->
                <div>
                    <label for="rejected_reason" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1 font-semibold">{{ __('Lý do từ chối duyệt') }}</label>
                    <textarea name="rejected_reason" id="rejected_reason" required placeholder="{{ __('Vui lòng nhập lý do từ chối (Ví dụ: Đơn bị hủy trên Shopee)...') }}" rows="3" class="block w-full px-4 py-2 border border-gray-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee"></textarea>
                </div>

                <div class="pt-2 flex justify-end gap-2 border-t border-gray-100">
                    <button type="button" @click="rejectModalOpen = false" class="px-4 py-2 text-xs font-semibold text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-xl transition-all">{{ __('Huỷ') }}</button>
                    <button type="submit" class="px-5 py-2 text-xs font-semibold text-white bg-red-600 hover:bg-red-700 rounded-xl transition-all shadow-md">{{ __('Từ chối đơn') }}</button>
                </div>
            </form>
        </div>
    </template>

    <!-- MODAL XEM CHI TIẾT ĐƠN HÀNG -->
    <template x-teleport="body">
        <div x-show="detailModalOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-slate-950/40 backdrop-blur-sm" x-transition>
            <div class="bg-white dark:bg-slate-900 rounded-3xl max-w-5xl w-full max-h-[90vh] flex flex-col shadow-2xl border border-gray-100 dark:border-slate-800 overflow-hidden" @click.away="detailModalOpen = false">
                <!-- Header (Cố định ở đỉnh modal) -->
                <div class="px-6 py-4 bg-gray-50 dark:bg-slate-800/50 border-b border-gray-100 dark:border-slate-800 flex justify-between items-center shrink-0">
                    <h3 class="font-bold text-gray-950 dark:text-white text-sm flex items-center gap-2">
                        <i data-lucide="eye" class="w-4 h-4 text-shopee"></i>
                        {{ __('Chi Tiết Đơn Hàng Hoàn Tiền & Click Tracking') }}
                    </h3>
                    <button @click="detailModalOpen = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                        <i data-lucide="x" class="w-4 h-4"></i>
                    </button>
                </div>
                
                <!-- Body (Cuộn độc lập khi nội dung dài, giữ header và footer luôn cố định) -->
                <div class="p-6 text-xs text-gray-600 dark:text-gray-300 overflow-y-auto flex-1 custom-scrollbar">
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                    
                    <!-- CỘT TRÁI: CHI TIẾT ĐƠN HÀNG (Chiếm 7/12 cột trên màn hình lớn) -->
                    <div class="lg:col-span-7 space-y-6">
                        <!-- Thông tin đơn hàng & Trạng thái -->
                        <div class="grid grid-cols-3 gap-4 p-4 bg-gray-50 dark:bg-slate-800/30 rounded-2xl border border-gray-100 dark:border-slate-800/50">
                            <div>
                                <!-- Hiển thị mã giao dịch ngẫu nhiên lúc đầu tạo link thay vì order_id Shopee -->
                                <p class="text-[10px] text-gray-400 dark:text-gray-500 font-bold uppercase tracking-wider">{{ __('Mã giao dịch (Hệ thống)') }}</p>
                                <p class="font-bold text-gray-900 dark:text-white text-sm mt-0.5 font-mono" x-text="detailCashback.trans_id || detailCashback.order_id || 'N/A'"></p>
                            </div>
                            <div>
                                <p class="text-[10px] text-gray-400 dark:text-gray-500 font-bold uppercase tracking-wider"
                                   x-text="!detailCashback.platform || detailCashback.platform === 'shopee' ? '{{ __('Mã đơn Shopee') }}' : (detailCashback.platform === 'tiktok' ? '{{ __('Mã đơn TikTok Shop') }}' : (detailCashback.platform === 'lazada' ? '{{ __('Mã đơn Lazada') }}' : '{{ __('Mã đơn ') }}' + detailCashback.platform.toUpperCase()))"></p>
                                <p class="font-bold text-gray-900 dark:text-white text-sm mt-0.5 font-mono" x-text="detailCashback.order_id || 'N/A'"></p>
                            </div>
                            <div>
                                <p class="text-[10px] text-gray-400 dark:text-gray-500 font-bold uppercase tracking-wider">{{ __('Nền tảng') }}</p>
                                <div class="mt-1">
                                    <template x-if="!detailCashback.platform || detailCashback.platform === 'shopee'">
                                        <span class="inline-flex px-1.5 py-0.5 rounded text-[8px] font-extrabold text-white shadow-sm" style="background-color: #ee4d2d;">{{ __('Shopee') }}</span>
                                    </template>
                                    <template x-if="detailCashback.platform === 'tiktok'">
                                        <span class="inline-flex px-1.5 py-0.5 rounded text-[8px] font-extrabold bg-black text-white shadow-sm">{{ __('TikTok Shop') }}</span>
                                    </template>
                                    <template x-if="detailCashback.platform === 'lazada'">
                                        <span class="inline-flex px-1.5 py-0.5 rounded text-[8px] font-extrabold bg-blue-800 text-white shadow-sm">{{ __('Lazada') }}</span>
                                    </template>
                                    <template x-if="detailCashback.platform && detailCashback.platform !== 'shopee' && detailCashback.platform !== 'tiktok' && detailCashback.platform !== 'lazada'">
                                        <span class="inline-flex px-1.5 py-0.5 rounded text-[8px] font-extrabold bg-gray-650 text-white shadow-sm" x-text="detailCashback.platform.toUpperCase()"></span>
                                    </template>
                                </div>
                            </div>
                            <div>
                                <p class="text-[10px] text-gray-400 dark:text-gray-500 font-bold uppercase tracking-wider">{{ __('Trạng thái') }}</p>
                                <div class="mt-1">
                                    <template x-if="detailCashback.status === 'pending'">
                                        <span class="inline-flex px-2 py-0.5 rounded-full text-[9px] font-bold bg-yellow-50 dark:bg-yellow-950/30 text-yellow-600 dark:text-yellow-400 border border-yellow-100 dark:border-yellow-900/50">{{ __('Chờ duyệt') }}</span>
                                    </template>
                                    <template x-if="detailCashback.status === 'approved'">
                                        <span class="inline-flex px-2 py-0.5 rounded-full text-[9px] font-bold bg-green-50 dark:bg-green-950/30 text-green-600 dark:text-green-400 border border-green-100 dark:border-green-900/50">{{ __('Thành công') }}</span>
                                    </template>
                                    <template x-if="detailCashback.status === 'rejected'">
                                        <span class="inline-flex px-2 py-0.5 rounded-full text-[9px] font-bold bg-red-50 dark:bg-red-950/30 text-red-600 dark:text-red-400 border border-red-100 dark:border-red-900/50">{{ __('Bị từ chối') }}</span>
                                    </template>
                                </div>
                            </div>
                            <div>
                                <p class="text-[10px] text-gray-400 dark:text-gray-500 font-bold uppercase tracking-wider">{{ __('Nguồn đối soát') }}</p>
                                <div class="mt-1">
                                    <template x-if="detailCashback.status === 'pending'">
                                        <span class="text-gray-500 font-medium">{{ __('Chờ đối soát') }}</span>
                                    </template>
                                    <template x-if="detailCashback.status !== 'pending'">
                                        <div>
                                            <template x-if="detailCashback.click_metadata?.approve_source === 'sync'">
                                                <span class="inline-flex px-2 py-0.5 rounded-full text-[9px] font-bold bg-orange-50 dark:bg-orange-950/30 text-orange-650 dark:text-orange-400 border border-orange-100 dark:border-orange-900/50"
                                                      x-text="getPlatformApiName(detailCashback.platform)"></span>
                                            </template>
                                            <template x-if="detailCashback.click_metadata?.approve_source === 'csv'">
                                                <span class="inline-flex px-2 py-0.5 rounded-full text-[9px] font-bold bg-purple-50 dark:bg-purple-950/30 text-purple-600 dark:text-purple-400 border border-purple-100 dark:border-purple-900/50">{{ __('Đối soát CSV') }}</span>
                                            </template>
                                            <template x-if="!detailCashback.click_metadata?.approve_source || detailCashback.click_metadata?.approve_source === 'manual'">
                                                <span class="inline-flex px-2 py-0.5 rounded-full text-[9px] font-bold bg-gray-50 dark:bg-slate-800/30 text-gray-650 dark:text-slate-350 border border-gray-150 dark:border-slate-700/50">{{ __('Duyệt thủ công') }}</span>
                                            </template>
                                        </div>
                                    </template>
                                </div>
                            </div>
                            <div>
                                <p class="text-[10px] text-gray-400 dark:text-gray-500 font-bold uppercase tracking-wider">{{ __('Người đối soát') }}</p>
                                <p class="font-semibold text-gray-950 dark:text-slate-200 mt-0.5" x-text="detailCashback.status === 'pending' ? '{{ __('Chờ xử lý') }}' : (detailCashback.click_metadata?.approve_source === 'sync' ? '{{ __('Hệ thống (Cron)') }}' : (detailCashback.click_metadata?.approve_source === 'csv' ? '{{ __('Hệ thống (CSV)') }}' : '{{ __('Quản trị viên') }}'))"></p>
                            </div>
                            <div>
                                <p class="text-[10px] text-gray-400 dark:text-gray-500 font-bold uppercase tracking-wider">{{ __('Ngày tạo') }}</p>
                                <p class="font-semibold text-gray-900 dark:text-white mt-0.5" x-text="formatDate(detailCashback.created_at)"></p>
                            </div>
                            <div>
                                <p class="text-[10px] text-gray-400 dark:text-gray-500 font-bold uppercase tracking-wider">{{ __('Thời gian duyệt') }}</p>
                                <p class="font-semibold text-gray-900 dark:text-white mt-0.5" x-text="detailCashback.approved_at ? formatDate(detailCashback.approved_at) : 'N/A'"></p>
                            </div>
                        </div>

                        <!-- Thông tin khách hàng -->
                        <div class="space-y-3">
                            <div class="flex items-center justify-between border-b border-gray-100 dark:border-slate-800 pb-1.5">
                                <h4 class="font-bold text-gray-900 dark:text-white uppercase tracking-wider text-[10px]">{{ __('Thông tin thành viên mua hàng') }}</h4>
                                <a :href="'{{ route('admin.users.edit', ['user' => 'USER_ID']) }}'.replace('USER_ID', detailCashback.user_id)" class="text-blue-500 hover:text-blue-700 flex items-center gap-1 font-semibold text-[10px]" title="{{ __('Chỉnh sửa thành viên') }}">
                                    <i data-lucide="edit-3" class="w-3.5 h-3.5"></i> {{ __('Chỉnh sửa') }}
                                </a>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <p class="text-gray-400 dark:text-gray-500">{{ __('Họ tên') }}</p>
                                    <p class="font-bold text-gray-800 dark:text-slate-200 mt-0.5" x-text="detailCashback.user?.name || 'N/A'"></p>
                                </div>
                                <div>
                                    <p class="text-gray-400 dark:text-gray-500">{{ __('Địa chỉ Email') }}</p>
                                    <p class="font-mono text-gray-800 dark:text-slate-200 mt-0.5" x-text="detailCashback.user?.email || 'N/A'"></p>
                                </div>
                            </div>
                        </div>

                        <!-- Thông tin sản phẩm -->
                        <div class="space-y-3">
                            <h4 class="font-bold text-gray-900 dark:text-white uppercase tracking-wider text-[10px] border-b border-gray-100 dark:border-slate-800 pb-1.5">{{ __('Thông tin sản phẩm') }}</h4>
                            <div class="flex gap-4">
                                <img :src="detailCashback.product_image" alt="" class="w-16 h-16 object-cover rounded-2xl bg-gray-50 shrink-0 border border-gray-100 dark:border-slate-800">
                                <div class="space-y-1.5 flex-1 min-w-0">
                                    <p class="font-bold text-gray-900 dark:text-white text-xs break-words font-semibold" x-text="detailCashback.product_name"></p>
                                    <div class="flex flex-col gap-1 mt-1">
                                        <a :href="detailCashback.affiliate_url" target="_blank" class="inline-flex items-center gap-1 text-[10px] text-shopee hover:underline font-semibold">
                                            <i data-lucide="external-link" class="w-3.5 h-3.5"></i>
                                            <span x-text="!detailCashback.platform || detailCashback.platform === 'shopee' ? '{{ __('Mở link Affiliate Shopee') }}' : (detailCashback.platform === 'tiktok' ? '{{ __('Mở link Affiliate TikTok Shop') }}' : (detailCashback.platform === 'lazada' ? '{{ __('Mở link Affiliate Lazada') }}' : '{{ __('Mở link Affiliate ') }}' + detailCashback.platform.toUpperCase()))"></span>
                                        </a>
                                        <template x-if="detailCashback.shop_name">
                                            <p class="text-[10px] text-gray-500 dark:text-gray-400 flex items-center gap-1 mt-0.5">
                                                <i data-lucide="store" class="w-3.5 h-3.5 text-orange-500"></i>
                                                {{ __('Shop:') }} <strong class="text-gray-800 dark:text-gray-250 font-bold" x-text="detailCashback.shop_name"></strong>
                                            </p>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Chi tiết dòng tiền -->
                        <div class="space-y-3">
                            <h4 class="font-bold text-gray-900 dark:text-white uppercase tracking-wider text-[10px] border-b border-gray-100 dark:border-slate-800 pb-1.5">{{ __('Giá trị & Phân chia hoa hồng') }}</h4>
                            <div class="grid grid-cols-3 gap-4">
                                <div class="p-3 bg-gray-50 dark:bg-slate-800/20 rounded-2xl border border-gray-100/80 dark:border-slate-800/40 text-center">
                                    <p class="text-gray-400 dark:text-gray-500 mb-0.5">{{ __('Giá bán') }}</p>
                                    <p class="font-bold text-gray-800 dark:text-slate-200" x-text="formatCurrency(detailCashback.original_price)"></p>
                                </div>
                                <div class="p-3 bg-green-50 dark:bg-green-950/20 rounded-2xl border border-green-100 dark:border-green-900/30 text-center">
                                    <p class="text-green-600 dark:text-green-400 mb-0.5">{{ __('Sàn trả hoa hồng') }}</p>
                                    <p class="font-bold text-green-700 dark:text-green-300" x-text="formatCurrency(detailCashback.commission_amount)"></p>
                                </div>
                                <div class="p-3 bg-shopee/5 dark:bg-shopee/10 rounded-2xl border border-shopee/10 dark:border-shopee/20 text-center">
                                    <p class="text-shopee mb-0.5">{{ __('Hoàn khách') }}</p>
                                    <p class="font-bold text-shopee" x-text="formatCurrency(detailCashback.cashback_amount)"></p>
                                    <span class="text-[8px] text-gray-400 dark:text-gray-500 block mt-0.5">Tỷ lệ: <span x-text="detailCashback.cashback_rate"></span>%</span>
                                </div>
                            </div>
                        </div>

                        <!-- Lý do từ chối (nếu có) -->
                        <template x-if="detailCashback.status === 'rejected' && (detailCashback.rejected_reason || detailCashback.fraud_reason)">
                            <div class="space-y-3.5">
                                <template x-if="detailCashback.rejected_reason">
                                    <div class="p-4 bg-red-50 dark:bg-red-950/20 border border-red-100 dark:border-red-900/30 rounded-2xl space-y-1">
                                        <p class="text-[10px] text-red-800 dark:text-red-400 font-bold uppercase tracking-wider">{{ __('Lý do từ chối của hệ thống') }}</p>
                                        <p class="text-red-700 dark:text-red-300 font-medium" x-text="detailCashback.rejected_reason"></p>
                                    </div>
                                </template>
                                <template x-if="detailCashback.fraud_reason">
                                    <div class="p-4 bg-orange-50 dark:bg-orange-950/20 border border-orange-100 dark:border-orange-900/30 rounded-2xl space-y-1">
                                        <p class="text-[10px] text-orange-850 dark:text-orange-400 font-bold uppercase tracking-wider">{{ __('Lý do gian lận từ Shopee') }}</p>
                                        <p class="text-orange-700 dark:text-orange-300 font-medium" x-text="detailCashback.fraud_reason"></p>
                                    </div>
                                </template>
                            </div>
                        </template>

                        <!-- Hoa hồng tiếp thị liên kết phát sinh (F1/F2) -->
                        <div class="space-y-3">
                            <h4 class="font-bold text-gray-900 dark:text-white uppercase tracking-wider text-[10px] border-b border-gray-100 dark:border-slate-800 pb-1.5 flex items-center gap-1.5">
                                <i data-lucide="git-branch" class="w-3.5 h-3.5 text-purple-500"></i>
                                {{ __('Hoa Hồng Tiếp Thị Liên Kết (MLM)') }}
                            </h4>

                            <!-- Đang tải -->
                            <template x-if="commissionsLoading">
                                <div class="p-4 text-center text-gray-400 dark:text-gray-500">
                                    <svg class="w-5 h-5 animate-spin mx-auto mb-1.5 text-purple-500" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <span class="text-[10px]">{{ __('Đang tải...') }}</span>
                                </div>
                            </template>

                            <!-- Có dữ liệu hoa hồng -->
                            <template x-if="!commissionsLoading && commissions.length > 0">
                                <div class="space-y-2.5">
                                    <template x-for="comm in commissions" :key="comm.id">
                                        <div class="p-3.5 rounded-2xl border transition-all hover:shadow-sm"
                                             :class="comm.level === 1 
                                                 ? 'bg-orange-50/60 dark:bg-orange-950/10 border-orange-100 dark:border-orange-900/30' 
                                                 : 'bg-blue-50/60 dark:bg-blue-950/10 border-blue-100 dark:border-blue-900/30'">
                                            <!-- Header: Tầng + Trạng thái -->
                                            <div class="flex items-center justify-between mb-2.5">
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[9px] font-bold"
                                                      :class="comm.level === 1 
                                                          ? 'bg-orange-100 dark:bg-orange-900/30 text-shopee' 
                                                          : 'bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400'">
                                                    <i data-lucide="award" class="w-2.5 h-2.5"></i>
                                                    <span x-text="'Tầng ' + comm.level + ' (F' + comm.level + ')'"></span>
                                                </span>
                                                <span class="px-2 py-0.5 rounded-full text-[8px] font-bold"
                                                      :class="comm.status === 'approved' 
                                                          ? 'bg-green-50 dark:bg-green-950/20 text-green-600 dark:text-green-400 border border-green-100 dark:border-green-900/30' 
                                                          : 'bg-yellow-50 dark:bg-yellow-950/20 text-yellow-600 dark:text-yellow-400 border border-yellow-100 dark:border-yellow-900/30'"
                                                      x-text="comm.status === 'approved' ? 'Đã cộng ví' : 'Chờ duyệt'">
                                                </span>
                                            </div>
                                            <!-- Thông tin tài khoản nhận -->
                                            <div class="grid grid-cols-2 gap-2 text-[10px]">
                                                <div>
                                                    <p class="text-gray-400 dark:text-gray-500 font-medium">{{ __('Người nhận') }}</p>
                                                    <p class="font-bold text-gray-800 dark:text-slate-200 mt-0.5" x-text="comm.referrer_name"></p>
                                                </div>
                                                <div>
                                                    <p class="text-gray-400 dark:text-gray-500 font-medium">{{ __('Email') }}</p>
                                                    <p class="font-mono text-gray-700 dark:text-slate-300 mt-0.5 truncate" x-text="comm.referrer_email" :title="comm.referrer_email"></p>
                                                </div>
                                                <div>
                                                    <p class="text-gray-400 dark:text-gray-500 font-medium">{{ __('Mã giới thiệu') }}</p>
                                                    <p class="font-mono font-semibold text-gray-700 dark:text-slate-300 mt-0.5" x-text="comm.referrer_code"></p>
                                                </div>
                                                <div>
                                                    <p class="text-gray-400 dark:text-gray-500 font-medium">{{ __('Hoa hồng nhận') }}</p>
                                                    <p class="font-extrabold mt-0.5"
                                                       :class="comm.level === 1 ? 'text-shopee' : 'text-blue-600 dark:text-blue-400'"
                                                       x-text="'+' + formatCurrency(comm.amount)"></p>
                                                </div>
                                            </div>
                                            <!-- Thời gian -->
                                            <div class="mt-2 pt-2 border-t text-[9px] text-gray-400 dark:text-gray-500 font-medium"
                                                 :class="comm.level === 1 ? 'border-orange-100/60 dark:border-orange-900/20' : 'border-blue-100/60 dark:border-blue-900/20'">
                                                <span x-text="'Thời gian: ' + formatDate(comm.created_at)"></span>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </template>

                            <!-- Không có hoa hồng -->
                            <template x-if="!commissionsLoading && commissions.length === 0">
                                <div class="p-4 bg-gray-50 dark:bg-slate-800/20 rounded-2xl border border-gray-100 dark:border-slate-800/30 text-center text-gray-400 dark:text-gray-500 text-[10px]">
                                    <i data-lucide="info" class="w-4 h-4 mx-auto mb-1 opacity-50"></i>
                                    <p>{{ __('Đơn hàng này chưa phát sinh hoa hồng tiếp thị liên kết.') }}</p>
                                    <p class="mt-0.5 text-[9px]">{{ __('(Người mua không có người giới thiệu hoặc đơn chưa được duyệt)') }}</p>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- CỘT PHẢI: DÒNG THỜI GIAN TRẠNG THÁI + LỊCH SỬ LƯỢT CLICK (Chiếm 5/12 cột trên màn hình lớn) -->
                    <div class="lg:col-span-5 lg:border-l lg:border-gray-100 lg:dark:border-slate-800 lg:pl-6 space-y-6">

                        <!-- DÒNG THỜI GIAN THAY ĐỔI TRẠNG THÁI ĐƠN HÀNG -->
                        <div class="space-y-3">
                            <h4 class="font-bold text-gray-900 dark:text-white uppercase tracking-wider text-[10px] border-b border-gray-100 dark:border-slate-800 pb-1.5 flex items-center gap-1.5">
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
                                                <span class="text-[9px] text-gray-400 dark:text-gray-500 shrink-0" x-text="formatDate(ev.at)"></span>
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

                        <div class="space-y-3">
                            <h4 class="font-bold text-gray-900 dark:text-white uppercase tracking-wider text-[10px] border-b border-gray-100 dark:border-slate-800 pb-1.5 flex items-center justify-between">
                                <div class="flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5 text-shopee" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122"></path>
                                    </svg>
                                    {{ __('Lịch sử lượt Click') }}
                                </div>
                                <template x-if="clickLogsTotal > 0">
                                    <span class="text-[9px] font-semibold text-gray-400 dark:text-gray-500" x-text="'Tổng: ' + clickLogsTotalClicks + ' | Đã tải: ' + clickLogs.length"></span>
                                </template>
                            </h4>

                            <!-- Trạng thái đang tải -->
                            <template x-if="clickLogsLoading">
                                <div class="p-8 text-center text-gray-400 dark:text-gray-500">
                                    <svg class="w-6 h-6 animate-spin mx-auto mb-2 text-shopee" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <span class="text-xs">{{ __('Đang tải lịch sử click...') }}</span>
                                </div>
                            </template>

                            <!-- Danh sách click logs (tăng chiều cao hiển thị lên max-h-[380px] cho cân xứng với cột trái) -->
                            <template x-if="!clickLogsLoading && clickLogs.length > 0">
                                <div class="max-h-[380px] overflow-y-auto space-y-2 pr-1 custom-scrollbar">
                                    <template x-for="(log, index) in clickLogs" :key="log.id">
                                        <div class="p-3 bg-gray-50/80 dark:bg-slate-800/30 rounded-xl border border-gray-100 dark:border-slate-800/40 hover:border-shopee/30 transition-colors">
                                            <div class="flex items-center justify-between mb-2">
                                                <span class="text-[9px] font-bold text-shopee bg-shopee/5 px-1.5 py-0.5 rounded-md" x-text="'Lượt #' + (clickLogs.length - index)"></span>
                                                <span class="text-[9px] text-gray-400 dark:text-gray-500" x-text="formatDate(log.created_at)"></span>
                                            </div>
                                            <div class="grid grid-cols-3 gap-2 text-[10px]">
                                                <div>
                                                    <p class="text-gray-400 dark:text-gray-500 font-medium">{{ __('IP') }}</p>
                                                    <p class="font-semibold text-gray-800 dark:text-slate-200 font-mono" x-text="log.ip_address || 'N/A'"></p>
                                                </div>
                                                <div>
                                                    <p class="text-gray-400 dark:text-gray-500 font-medium">{{ __('Thiết bị') }}</p>
                                                    <p class="font-semibold text-gray-800 dark:text-slate-200" x-text="log.device || 'N/A'"></p>
                                                </div>
                                                <div>
                                                    <p class="text-gray-400 dark:text-gray-500 font-medium">{{ __('Vị trí') }}</p>
                                                    <p class="font-semibold text-gray-800 dark:text-slate-200 truncate" x-text="log.location || 'N/A'" :title="log.location"></p>
                                                </div>
                                            </div>
                                            <div class="mt-1.5 pt-1.5 border-t border-gray-100 dark:border-slate-800/30">
                                                <p class="text-[8px] font-mono text-gray-400 dark:text-slate-500 break-all" x-text="log.user_agent || 'N/A'"></p>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </template>

                            <!-- Không có dữ liệu -->
                            <template x-if="!clickLogsLoading && clickLogs.length === 0">
                                <div class="p-8 bg-gray-50 dark:bg-slate-800/20 rounded-2xl border border-gray-100 dark:border-slate-800/30 text-center text-gray-400 dark:text-gray-500 text-[10px]">
                                    {{ __('Chưa ghi nhận lượt click nào qua link rút gọn.') }}
                                </div>
                            </template>
                        </div>
                    </div>

                </div>
            </div>

            <!-- Footer (Cố định ở chân modal) -->
            <div class="px-6 py-4 bg-gray-50 dark:bg-slate-800/50 border-t border-gray-100 dark:border-slate-800 flex justify-end gap-2 shrink-0">
                <!-- Nút xóa đơn hàng trực tiếp trong modal chi tiết (chuyển sang mở modal xác nhận an toàn kèm checkbox theo yêu cầu sếp Thành) -->
                <button type="button" @click="detailModalOpen = false; openDeleteModal(detailCashback)" class="px-4 py-2 text-xs font-semibold text-white bg-red-600 hover:bg-red-700 rounded-xl transition-all shadow-md mr-2 flex items-center gap-1">
                    <i data-lucide="trash-2" class="w-3.5 h-3.5"></i> {{ __('Xóa đơn hàng') }}
                </button>

                <button type="button" @click="detailModalOpen = false" class="px-5 py-2 text-xs font-semibold text-gray-700 dark:text-slate-300 bg-gray-100 dark:bg-slate-800 hover:bg-gray-200 dark:hover:bg-slate-700 rounded-xl transition-all border border-transparent dark:border-slate-700/50">
                    {{ __('Đóng') }}
                </button>
                <template x-if="detailCashback.status === 'pending'">
                    <div class="flex gap-2">
                        <!-- Nút Từ chối nhanh từ Modal -->
                        <button type="button" @click="detailModalOpen = false; openRejectModal(detailCashback)" class="px-4 py-2 text-xs font-semibold text-white bg-red-600 hover:bg-red-700 rounded-xl transition-all shadow-md">
                            {{ __('Từ chối') }}
                        </button>
                        <!-- Nút Duyệt nhanh từ Modal -->
                        <button type="button" @click="detailModalOpen = false; openApproveModal(detailCashback)" class="px-4 py-2 text-xs font-semibold text-white bg-green-600 hover:bg-green-700 rounded-xl transition-all shadow-md flex items-center gap-1">
                            <i data-lucide="check" class="w-3.5 h-3.5"></i> {{ __('Duyệt đơn') }}
                        </button>
                    </div>
                </template>
            </div>
        </div>
    </template>

    <!-- FLOATING BULK ACTIONS TOOLBAR -->
    <div x-show="selectedCashbacks.length > 0" x-cloak class="fixed bottom-6 left-1/2 -translate-x-1/2 z-40 bg-white/85 dark:bg-slate-900/95 backdrop-blur-md px-6 py-4 rounded-3xl shadow-2xl border border-gray-150 dark:border-slate-800/80 flex items-center gap-4 transition-all duration-300 transform" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 translate-y-4">
        <div class="flex items-center gap-2.5">
            <span class="relative flex h-3 w-3">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-shopee opacity-75"></span>
                <span class="relative inline-flex rounded-full h-3 w-3 bg-shopee"></span>
            </span>
            <span class="text-xs font-bold text-gray-800 dark:text-slate-200">{{ __('Đã chọn:') }} <strong class="text-shopee dark:text-shopee-light" x-text="selectedCashbacks.length"></strong> {{ __('đơn hàng') }}</span>
        </div>
        <div class="h-6 w-[1px] bg-gray-200 dark:bg-slate-800"></div>
        <div class="flex items-center gap-2">
            <!-- Nút duyệt hàng loạt -->
            <button type="button" @click="openBulkApproveModal()" class="px-4 py-2.5 text-[10px] font-bold text-white bg-green-600 hover:bg-green-700 rounded-xl transition-all shadow-md flex items-center gap-1.5">
                <i data-lucide="check" class="w-3.5 h-3.5"></i>
                {{ __('Duyệt hàng loạt') }}
            </button>

            <!-- Nút từ chối hàng loạt -->
            <button type="button" @click="openBulkRejectModal()" class="px-4 py-2.5 text-[10px] font-bold text-white bg-yellow-600 hover:bg-yellow-700 rounded-xl transition-all shadow-md flex items-center gap-1.5">
                <i data-lucide="x" class="w-3.5 h-3.5"></i>
                {{ __('Từ chối hàng loạt') }}
            </button>

            <!-- Nút xóa hàng loạt -->
            <button type="button" @click="openBulkDeleteModal()" class="px-4 py-2.5 text-[10px] font-bold text-white bg-red-600 hover:bg-red-700 rounded-xl transition-all shadow-md flex items-center gap-1.5">
                <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                {{ __('Xóa hàng loạt') }}
            </button>
        </div>
    </div>

    <!-- MODAL XÁC NHẬN DUYỆT HÀNG LOẠT -->
    <template x-teleport="body">
        <div x-show="bulkApproveModalOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-slate-950/40 backdrop-blur-sm" x-transition>
            <div class="bg-white dark:bg-slate-900 rounded-3xl max-w-md w-full shadow-2xl border border-gray-100 dark:border-slate-800 overflow-hidden" @click.away="bulkApproveModalOpen = false">
                <div class="px-6 py-4 bg-gray-50 dark:bg-slate-800/50 border-b border-gray-100 dark:border-slate-800 flex justify-between items-center">
                    <h3 class="font-bold text-gray-950 dark:text-white text-sm flex items-center gap-1.5">
                        <i data-lucide="check-circle-2" class="w-4 h-4 text-green-600"></i>
                        {{ __('Duyệt đơn hàng loạt') }}
                    </h3>
                    <button @click="bulkApproveModalOpen = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                        <i data-lucide="x" class="w-4 h-4"></i>
                    </button>
                </div>
                
                <form @submit.prevent="submitBulkApprove()" class="p-6 space-y-4">
                    <div class="text-xs text-gray-600 dark:text-gray-300 space-y-2">
                        <p class="text-gray-500 dark:text-gray-400">{{ __('Hệ thống sẽ duyệt toàn bộ các đơn hoàn tiền đang chờ xử lý được chọn, cộng số tiền hoàn đề xuất cho từng khách hàng và trích hoa hồng MLM F1/F2.') }}</p>
                        
                        <div class="p-3.5 bg-green-50 dark:bg-green-950/20 border border-green-100 dark:border-green-900/50 rounded-2xl text-[10px] text-green-800 dark:text-green-300 space-y-1">
                            <div>{{ __('Số lượng đơn hàng đã chọn:') }} <strong class="text-gray-900 dark:text-white text-xs" x-text="selectedCashbacks.length"></strong></div>
                        </div>
                    </div>

                    <div class="pt-2 flex justify-end gap-2 border-t border-gray-100 dark:border-slate-800">
                        <button type="button" @click="bulkApproveModalOpen = false" :disabled="isSubmitting" class="px-4 py-2 text-xs font-semibold text-gray-700 dark:text-slate-350 bg-gray-100 dark:bg-slate-800 hover:bg-gray-200 dark:hover:bg-slate-700 rounded-xl transition-all disabled:opacity-50">{{ __('Huỷ') }}</button>
                        <button type="submit" :disabled="isSubmitting" class="px-5 py-2 text-xs font-semibold text-white bg-green-600 hover:bg-green-700 rounded-xl transition-all shadow-md flex items-center justify-center min-w-[140px] disabled:opacity-50 disabled:cursor-not-allowed">
                            <span x-show="!isSubmitting" class="flex items-center gap-1.5">
                                <i data-lucide="check" class="w-3.5 h-3.5"></i>
                                {{ __('Đồng ý duyệt') }}
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

    <!-- MODAL TỪ CHỐI HÀNG LOẠT -->
    <template x-teleport="body">
        <div x-show="bulkRejectModalOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-slate-950/40 backdrop-blur-sm" x-transition>
            <div class="bg-white dark:bg-slate-900 rounded-3xl max-w-md w-full shadow-2xl border border-gray-100 dark:border-slate-800 overflow-hidden" @click.away="bulkRejectModalOpen = false">
                <div class="px-6 py-4 bg-gray-50 dark:bg-slate-950/40 border-b border-gray-100 dark:border-slate-850 flex justify-between items-center">
                    <h3 class="font-bold text-gray-950 dark:text-slate-200 text-sm flex items-center gap-2">
                        <i data-lucide="x-circle" class="w-4.5 h-4.5 text-red-650 dark:text-red-400"></i>
                        {{ __('Từ chối duyệt đơn hàng loạt') }}
                    </h3>
                    <button @click="bulkRejectModalOpen = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-slate-350"><i data-lucide="x" class="w-4 h-4"></i></button>
                </div>
                
                <form @submit.prevent="submitBulkReject()" class="p-6 space-y-4">
                    <div class="p-3 bg-red-50 dark:bg-red-950/30 border border-red-100 dark:border-red-900/40 rounded-2xl text-[10px] text-red-800 dark:text-red-300 font-medium">
                        {{ __('Đang thực thi từ chối cho:') }} <strong class="dark:text-red-200 text-red-950" x-text="selectedCashbacks.length"></strong> {{ __('đơn hoàn tiền đã chọn.') }}
                    </div>

                    <!-- Lý do -->
                    <div>
                        <label for="bulk_rejected_reason" class="block text-xs font-bold text-gray-700 dark:text-slate-350 uppercase tracking-wider mb-1 font-semibold">{{ __('Lý do từ chối chung') }}</label>
                        <textarea name="rejected_reason" id="bulk_rejected_reason" x-model="bulkRejectedReasonInput" required placeholder="{{ __('Vui lòng nhập lý do từ chối chung áp dụng cho các đơn đã chọn...') }}" rows="3" class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-800 dark:bg-slate-950 dark:text-white rounded-xl text-xs font-semibold focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee"></textarea>
                    </div>

                    <div class="pt-2 flex justify-end gap-2 border-t border-gray-100 dark:border-slate-800">
                        <button type="button" @click="bulkRejectModalOpen = false" :disabled="isSubmitting" class="px-4 py-2 text-xs font-semibold text-gray-700 dark:text-slate-350 bg-gray-100 dark:bg-slate-800 hover:bg-gray-200 dark:hover:bg-slate-700 rounded-xl transition-all disabled:opacity-50">{{ __('Huỷ') }}</button>
                        <button type="submit" :disabled="isSubmitting" class="px-5 py-2 text-xs font-semibold text-white bg-red-600 hover:bg-red-750 rounded-xl transition-all shadow-md flex items-center justify-center min-w-[140px] disabled:opacity-50 disabled:cursor-not-allowed">
                            <span x-show="!isSubmitting" class="flex items-center gap-1.5">
                                <i data-lucide="play" class="w-3.5 h-3.5"></i>
                                {{ __('Từ chối hàng loạt') }}
                            </span>
                            <span x-show="isSubmitting" class="flex items-center gap-1.5">
                                <svg class="animate-spin -ml-1 mr-1.5 h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                {{ __('Đang xử lý...') }}
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </template>

    <!-- MODAL XÁC NHẬN XÓA ĐƠN HÀNG HOÀN TIỀN ĐƠN LẺ -->
    <template x-teleport="body">
        <div x-show="deleteModalOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-slate-950/40 backdrop-blur-sm" x-transition>
            <div class="bg-white dark:bg-slate-900 rounded-3xl max-w-md w-full shadow-2xl border border-gray-100 dark:border-slate-800 overflow-hidden" @click.away="deleteModalOpen = false" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95">
                <!-- Header -->
                <div class="px-6 py-4 bg-red-50 dark:bg-red-950/20 border-b border-red-100 dark:border-red-900/40 flex justify-between items-center">
                    <h3 class="font-bold text-red-950 dark:text-red-200 text-sm flex items-center gap-2">
                        <i data-lucide="alert-triangle" class="w-4.5 h-4.5 text-red-600 dark:text-red-400 animate-pulse"></i>
                        {{ __('Xác nhận xóa đơn hàng hoàn tiền') }}
                    </h3>
                    <button @click="deleteModalOpen = false" class="text-red-400 hover:text-red-600 dark:text-red-450 dark:hover:text-red-300">
                        <i data-lucide="x" class="w-4 h-4"></i>
                    </button>
                </div>
                
                <!-- Body -->
                <div class="p-6 space-y-5">
                    <!-- Card thông tin đơn hàng cần xóa -->
                    <div class="flex items-start gap-3.5 p-4 bg-gray-50 dark:bg-slate-950/40 border border-gray-100 dark:border-slate-800/80 rounded-2xl">
                        <template x-if="activeDeleteCashback.product_image">
                            <img :src="activeDeleteCashback.product_image" alt="" class="w-12 h-12 rounded-xl object-cover bg-white shrink-0 border border-gray-200 dark:border-slate-800">
                        </template>
                        <template x-if="!activeDeleteCashback.product_image">
                            <div class="w-12 h-12 rounded-xl bg-red-100 dark:bg-red-950/50 flex items-center justify-center text-red-600 dark:text-red-400 shrink-0 shadow-sm border border-red-200/40 dark:border-red-900/30">
                                <i data-lucide="package-x" class="w-5 h-5"></i>
                            </div>
                        </template>
                        <div class="min-w-0 flex-1 space-y-1">
                            <h4 class="text-xs font-black text-gray-800 dark:text-slate-200 line-clamp-2" x-text="activeDeleteCashback.product_name || '{{ __('Đơn hoàn tiền') }}'"></h4>
                            <div class="text-[10px] font-medium text-gray-500 dark:text-slate-400 flex flex-wrap items-center gap-x-2 gap-y-0.5">
                                <span>{{ __('Mã GD:') }} <strong class="font-mono text-gray-700 dark:text-slate-300" x-text="activeDeleteCashback.trans_id || activeDeleteCashback.order_id || 'N/A'"></strong></span>
                                <span x-show="activeDeleteCashback.user?.name">• <span class="text-gray-700 dark:text-slate-300 font-semibold" x-text="activeDeleteCashback.user?.name"></span></span>
                            </div>
                        </div>
                    </div>

                    <!-- Tóm tắt số tiền hoàn trả & trạng thái -->
                    <div class="grid grid-cols-2 gap-3 text-[10px]">
                        <div class="p-3 bg-gray-50 dark:bg-slate-950/20 border border-gray-100 dark:border-slate-800/50 rounded-xl">
                            <span class="text-gray-450 dark:text-slate-500 block text-[9px] font-bold uppercase tracking-wider mb-0.5">{{ __('Số tiền hoàn trả') }}</span>
                            <strong class="text-shopee font-extrabold text-xs" x-text="formatCurrency(activeDeleteCashback.cashback_amount || 0)"></strong>
                        </div>
                        <div class="p-3 bg-gray-50 dark:bg-slate-950/20 border border-gray-100 dark:border-slate-800/50 rounded-xl">
                            <span class="text-gray-450 dark:text-slate-500 block text-[9px] font-bold uppercase tracking-wider mb-0.5">{{ __('Trạng thái đơn') }}</span>
                            <span class="font-bold text-xs" 
                                  :class="{
                                      'text-amber-600 dark:text-amber-400': activeDeleteCashback.status === 'pending',
                                      'text-emerald-600 dark:text-emerald-400': activeDeleteCashback.status === 'approved',
                                      'text-rose-600 dark:text-rose-400': activeDeleteCashback.status === 'rejected'
                                  }"
                                  x-text="activeDeleteCashback.status === 'pending' ? '{{ __('Chờ duyệt') }}' : (activeDeleteCashback.status === 'approved' ? '{{ __('Thành công') }}' : '{{ __('Bị từ chối') }}')">
                            </span>
                        </div>
                    </div>

                    <!-- Danh sách dữ liệu sẽ bị xóa khi xác nhận -->
                    <div class="p-4 bg-red-50/40 dark:bg-red-950/10 border border-red-100/50 dark:border-red-900/20 rounded-2xl space-y-3">
                        <div class="flex items-center gap-1.5">
                            <span class="w-1.5 h-1.5 rounded-full bg-red-500 shrink-0"></span>
                            <span class="text-[9px] font-black text-red-800 dark:text-red-300 uppercase tracking-widest">{{ __('Những gì sẽ bị xóa khi xác nhận:') }}</span>
                        </div>
                        <ul class="space-y-2 text-[10px] text-gray-600 dark:text-slate-350 font-medium">
                            <li class="flex items-start gap-2">
                                <i data-lucide="x-circle" class="w-3.5 h-3.5 text-red-500 dark:text-red-400 shrink-0 mt-0.5"></i>
                                <span>{{ __('Bản ghi đơn hàng hoàn tiền Cashback khỏi hệ thống') }}</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <i data-lucide="x-circle" class="w-3.5 h-3.5 text-red-500 dark:text-red-400 shrink-0 mt-0.5"></i>
                                <span>{{ __('Toàn bộ lịch sử hoa hồng tiếp thị liên kết (MLM F1 & F2) liên quan đến đơn hàng này') }}</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <i data-lucide="x-circle" class="w-3.5 h-3.5 text-red-500 dark:text-red-400 shrink-0 mt-0.5"></i>
                                <span>{{ __('Lịch sử log ghi nhận giao dịch của đơn hàng') }}</span>
                            </li>
                        </ul>
                    </div>

                    <!-- Checkbox Xác Nhận Bắt Buộc -->
                    <div class="pt-2 border-t border-gray-150 dark:border-slate-800/80">
                        <label class="flex items-center gap-3 p-3.5 bg-red-50/50 dark:bg-red-950/20 border border-red-100 dark:border-red-900/40 rounded-2xl hover:bg-red-50 dark:hover:bg-red-950/30 transition-colors cursor-pointer group">
                            <input type="checkbox" x-model="confirmDeleteCheckbox" class="w-4 h-4 text-red-600 border-gray-300 dark:border-slate-700 rounded focus:ring-red-500/20 dark:bg-slate-950 cursor-pointer">
                            <span class="text-xs font-bold text-gray-700 dark:text-slate-300 group-hover:text-red-600 dark:group-hover:text-red-400 transition-colors">
                                {{ __('Tôi xác nhận muốn xóa vĩnh viễn đơn hàng này') }}
                            </span>
                        </label>
                    </div>

                    <!-- Footer Action Buttons -->
                    <form :action="'/' + window.adminPrefix + '/cashback/' + activeDeleteCashback.id" method="POST" class="flex justify-end gap-2 pt-2">
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

    <!-- MODAL XÁC NHẬN XÓA HÀNG LOẠT -->
    <template x-teleport="body">
        <div x-show="bulkDeleteModalOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-slate-950/40 backdrop-blur-sm" x-transition>
            <div class="bg-white dark:bg-slate-900 rounded-3xl max-w-md w-full shadow-2xl border border-gray-100 dark:border-slate-800 overflow-hidden" @click.away="bulkDeleteModalOpen = false">
                <!-- Header -->
                <div class="px-6 py-4 bg-red-50 dark:bg-red-950/20 border-b border-red-100 dark:border-red-900/40 flex justify-between items-center">
                    <h3 class="font-bold text-red-950 dark:text-red-200 text-sm flex items-center gap-2">
                        <i data-lucide="alert-triangle" class="w-4.5 h-4.5 text-red-600 dark:text-red-400 animate-pulse"></i>
                        {{ __('Xác nhận xóa hàng loạt') }}
                    </h3>
                    <button @click="bulkDeleteModalOpen = false" class="text-red-400 hover:text-red-600 dark:text-red-450 dark:hover:text-red-300"><i data-lucide="x" class="w-4 h-4"></i></button>
                </div>
                
                <!-- Body -->
                <div class="p-6 space-y-5">
                    <div class="p-4 bg-red-50/50 dark:bg-red-950/20 border border-red-100/80 dark:border-red-900/30 rounded-2xl text-xs text-red-800 dark:text-red-300 space-y-3">
                        <p class="font-bold text-red-900 dark:text-red-200">{{ __('Cảnh báo xóa dữ liệu hàng loạt:') }}</p>
                        <p class="leading-relaxed text-gray-700 dark:text-slate-350">
                            {{ __('Hành động này sẽ xóa vĩnh viễn') }} <strong class="text-red-600 dark:text-red-400 font-extrabold" x-text="selectedCashbacks.length"></strong> {{ __('đơn hoàn tiền được chọn khỏi hệ thống.') }}
                        </p>
                        <p class="leading-relaxed text-[11px] text-red-750/90 dark:text-red-350/80 border-t border-red-100/50 dark:border-red-900/20 pt-2">
                            {{ __('Mọi dữ liệu liên kết bao gồm lịch sử hoa hồng tiếp thị liên kết (MLM F1/F2) phát sinh từ các đơn hàng này cũng sẽ bị xóa vĩnh viễn khỏi hệ thống!') }}
                        </p>
                    </div>

                    <!-- Checklist các mục sẽ xóa -->
                    <div class="p-3 bg-red-50/40 dark:bg-red-950/10 border border-red-100/50 dark:border-red-900/20 rounded-2xl space-y-2 text-[10px] text-gray-600 dark:text-slate-350 font-medium">
                        <p class="font-bold text-red-800 dark:text-red-300 uppercase tracking-widest text-[9px] flex items-center gap-1">
                            <span class="w-1.5 h-1.5 rounded-full bg-red-500 shrink-0"></span>
                            {{ __('Những gì sẽ bị xóa khi xác nhận:') }}
                        </p>
                        <ul class="space-y-1.5 pl-1">
                            <li class="flex items-center gap-1.5">
                                <i data-lucide="x-circle" class="w-3.5 h-3.5 text-red-500 shrink-0"></i>
                                <span>{{ __('Các bản ghi đơn hoàn tiền Cashback được chọn khỏi CSDL') }}</span>
                            </li>
                            <li class="flex items-center gap-1.5">
                                <i data-lucide="x-circle" class="w-3.5 h-3.5 text-red-500 shrink-0"></i>
                                <span>{{ __('Toàn bộ lịch sử hoa hồng giới thiệu MLM F1/F2 của các đơn hàng này') }}</span>
                            </li>
                        </ul>
                    </div>

                    <!-- Checkbox Xác Nhận Bắt Buộc & Input Verification -->
                    <div class="space-y-3 border-t border-gray-150 dark:border-slate-800/80 pt-3">
                        <label class="flex items-center gap-3 p-3 bg-red-50/50 dark:bg-red-950/20 border border-red-100 dark:border-red-900/40 rounded-2xl hover:bg-red-50 dark:hover:bg-red-950/30 transition-colors cursor-pointer group">
                            <input type="checkbox" x-model="confirmBulkDeleteCheckbox" class="w-4 h-4 text-red-600 border-gray-300 dark:border-slate-700 rounded focus:ring-red-500/20 dark:bg-slate-950 cursor-pointer">
                            <span class="text-xs font-bold text-gray-700 dark:text-slate-300 group-hover:text-red-600 dark:group-hover:text-red-400 transition-colors">
                                {{ __('Tôi xác nhận muốn xóa vĩnh viễn các đơn hàng đã chọn') }}
                            </span>
                        </label>

                        <div class="space-y-1.5">
                            <label class="block text-[10px] font-bold text-gray-700 dark:text-slate-350 uppercase tracking-wider">
                                {{ __('Nhập cụm từ') }} <span class="text-red-600 dark:text-red-400 font-black select-all">XÓA HÀNG LOẠT</span> {{ __('để xác nhận xóa:') }}
                            </label>
                            <input type="text" x-model="confirmBulkText" placeholder="{{ __('Nhập XÓA HÀNG LOẠT...') }}" class="block w-full px-4 py-2 border border-gray-200 dark:border-slate-800 dark:bg-slate-950 dark:text-white rounded-xl text-xs font-semibold focus:outline-none focus:ring-2 focus:ring-red-500/20 focus:border-red-500 transition-all placeholder-gray-400 dark:placeholder-slate-500">
                        </div>
                    </div>

                    <!-- Footer Action Buttons -->
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
    <!-- MODAL: SYNC API (Shopee + TikTok Shop + Lazada) -->
    <template x-teleport="body">
        <div x-show="syncApiModalOpen"
             @keydown.escape.window="closeSyncApiModal()"
             class="fixed inset-0 z-50 overflow-y-auto"
             style="display: none;"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0">

            <div @click="closeSyncApiModal()" class="fixed inset-0 bg-gray-950/40 dark:bg-slate-950/70 backdrop-blur-sm"></div>

            <div @click="closeSyncApiModal()" class="flex min-h-screen items-center justify-center p-4 sm:p-0">
                <div @click.stop
                     class="relative transform overflow-hidden rounded-3xl bg-white dark:bg-slate-900 border border-gray-100 dark:border-slate-800/80 p-6 text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-[90vw] xl:max-w-[85vw]"
                     x-transition:enter="transition ease-out duration-300"
                     x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                     x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                     x-transition:leave="transition ease-in duration-200"
                     x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                     x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">

                    <!-- Nút đóng -->
                    <div class="absolute right-4 top-4">
                        <button @click="closeSyncApiModal()"
                                class="rounded-xl p-1 text-gray-450 hover:bg-gray-100 dark:hover:bg-slate-800 hover:text-gray-650 dark:hover:text-gray-200 transition-all">
                            <i data-lucide="x" class="w-5 h-5"></i>
                        </button>
                    </div>

                    <!-- Tiêu đề modal -->
                    <div class="mb-4 pr-8">
                        <h3 class="text-sm font-bold text-gray-800 dark:text-gray-200 uppercase tracking-wider flex items-center gap-2">
                            <i data-lucide="refresh-cw" class="w-4 h-4 text-emerald-500"></i>
                            {{ __('Báo cáo đối soát API') }}
                        </h3>
                        <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">
                            {{ __('Đồng bộ tự động từ tất cả tài khoản Shopee Affiliate, TikTok Shop và Lazada đang được bật (30 ngày gần nhất).') }}
                        </p>
                    </div>

                    <!-- Trạng thái: Đang tải -->
                    <div x-show="syncApiLoading" class="py-12 flex flex-col items-center justify-center space-y-4">
                        <div class="w-12 h-12 rounded-full border-4 border-emerald-500/20 border-t-emerald-500 animate-spin"></div>
                        <div class="text-center space-y-1">
                            <p class="text-xs font-bold text-gray-700 dark:text-gray-300 animate-pulse">{{ __('Đang kết nối đến API của các sàn đang được bật...') }}</p>
                            <p class="text-[10px] text-gray-400 dark:text-gray-500 font-medium">{{ __('Hệ thống đang quét các đơn hàng phát sinh trong 30 ngày qua và thực hiện đối soát tự động.') }}</p>
                        </div>
                    </div>

                    <!-- Trạng thái: Lỗi -->
                    <div x-show="syncApiError && !syncApiLoading" class="py-6 space-y-4" style="display: none;">
                        <div class="p-4 bg-red-50/50 dark:bg-red-950/20 border border-red-100/40 dark:border-red-900/20 rounded-2xl flex items-start gap-3">
                            <div class="w-8 h-8 rounded-full bg-red-500/10 text-red-500 flex items-center justify-center shrink-0">
                                <i data-lucide="alert-circle" class="w-4 h-4"></i>
                            </div>
                            <div class="space-y-1 text-xs">
                                <h4 class="font-bold text-red-800 dark:text-red-400">{{ __('Đồng bộ thất bại!') }}</h4>
                                <p class="text-gray-600 dark:text-gray-450 font-medium" x-text="syncApiError"></p>
                            </div>
                        </div>
                        <div class="flex justify-end pt-2">
                            <button @click="closeSyncApiModal()"
                                    class="px-4 py-2 bg-gray-150 hover:bg-gray-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-gray-750 dark:text-gray-300 font-bold rounded-xl text-xs transition-all">
                                {{ __('Đóng và tải lại trang') }}
                            </button>
                        </div>
                    </div>

                    <!-- Trạng thái: Thành công -->
                    <div x-show="syncApiData && !syncApiLoading" class="space-y-5" style="display: none;">

                        <!-- Cảnh báo nếu một số nền tảng bị lỗi -->
                        <template x-if="syncApiData?.errors && syncApiData.errors.length > 0">
                            <div class="p-3 bg-amber-50/60 dark:bg-amber-950/20 border border-amber-100 dark:border-amber-900/30 rounded-2xl flex items-start gap-2.5 text-[10px]">
                                <i data-lucide="alert-triangle" class="w-4 h-4 text-amber-500 shrink-0 mt-0.5"></i>
                                <div>
                                    <p class="font-bold text-amber-800 dark:text-amber-400">{{ __('Một số nguồn đồng bộ gặp lỗi (dữ liệu từ các nguồn còn lại vẫn được xử lý):') }}</p>
                                    <template x-for="(err, i) in syncApiData.errors" :key="i">
                                        <p class="text-amber-700 dark:text-amber-300 font-medium mt-0.5" x-text="'• ' + err"></p>
                                    </template>
                                </div>
                            </div>
                        </template>

                        <!-- Ghi chú các sàn bị bỏ qua do Admin đã tắt trong cấu hình hệ thống -->
                        <template x-if="syncApiData?.skipped_platforms && syncApiData.skipped_platforms.length > 0">
                            <div class="p-3 bg-gray-50 dark:bg-slate-800/40 border border-gray-100 dark:border-slate-800/40 rounded-2xl flex items-start gap-2.5 text-[10px]">
                                <i data-lucide="power-off" class="w-4 h-4 text-gray-400 dark:text-gray-500 shrink-0 mt-0.5"></i>
                                <div>
                                    <p class="font-bold text-gray-700 dark:text-gray-300">{{ __('Đã bỏ qua các sàn đang TẮT (không gọi API):') }}</p>
                                    <p class="text-gray-500 dark:text-gray-400 font-medium mt-0.5" x-text="syncApiData.skipped_platforms.join(', ')"></p>
                                </div>
                            </div>
                        </template>

                        <!-- Tổng hợp số liệu -->
                        <div class="grid grid-cols-2 sm:grid-cols-5 gap-3">
                            <div class="p-3 bg-gray-50 dark:bg-slate-800/40 rounded-2xl border border-gray-100/70 dark:border-slate-800/30">
                                <span class="block text-[9px] font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500">{{ __('Quét được') }}</span>
                                <span class="block text-base font-bold text-gray-800 dark:text-gray-200 mt-0.5" x-text="syncApiData?.summary?.total_scanned ?? 0"></span>
                            </div>
                            <div class="p-3 bg-green-50/30 dark:bg-green-950/5 rounded-2xl border border-green-100/40 dark:border-green-900/10">
                                <span class="block text-[9px] font-bold uppercase tracking-wider text-green-600/75 dark:text-green-500/75">{{ __('Đã duyệt') }}</span>
                                <span class="block text-base font-bold text-green-600 dark:text-green-400 mt-0.5" x-text="syncApiData?.summary?.approved ?? 0"></span>
                            </div>
                            <div class="p-3 bg-red-50/30 dark:bg-red-950/5 rounded-2xl border border-red-100/40 dark:border-red-900/10">
                                <span class="block text-[9px] font-bold uppercase tracking-wider text-red-600/75 dark:text-red-500/75">{{ __('Từ chối / Hủy') }}</span>
                                <span class="block text-base font-bold text-red-650 dark:text-red-400 mt-0.5" x-text="syncApiData?.summary?.rejected ?? 0"></span>
                            </div>
                            <div class="p-3 bg-blue-50/30 dark:bg-blue-950/5 rounded-2xl border border-blue-100/40 dark:border-blue-900/10">
                                <span class="block text-[9px] font-bold uppercase tracking-wider text-blue-600/75 dark:text-blue-500/75">{{ __('Đã xử lý trước') }}</span>
                                <span class="block text-base font-bold text-blue-600 dark:text-blue-400 mt-0.5" x-text="syncApiData?.summary?.already_processed ?? 0"></span>
                            </div>
                            <div class="p-3 bg-gray-50 dark:bg-slate-800/40 rounded-2xl border border-gray-100/70 dark:border-slate-800/30">
                                <span class="block text-[9px] font-bold uppercase tracking-wider text-gray-500/75 dark:text-gray-400/75">{{ __('Bỏ qua') }}</span>
                                <span class="block text-base font-bold text-gray-600 dark:text-gray-400 mt-0.5" x-text="syncApiData?.summary?.ignored ?? 0"></span>
                            </div>
                        </div>

                        <!-- Bảng chi tiết đơn hàng -->
                        <div class="space-y-2">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                <h4 class="text-xs font-bold text-gray-600 dark:text-gray-400 uppercase tracking-wide">
                                    {{ __('Danh sách đơn hàng quét được') }}
                                </h4>
                                <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2 w-full sm:w-auto">
                                    <!-- Ô tìm kiếm nhanh -->
                                    <div class="relative w-full sm:w-56">
                                        <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-gray-400 dark:text-gray-500">
                                            <i data-lucide="search" class="w-3.5 h-3.5"></i>
                                        </span>
                                        <input type="text"
                                               x-model="syncApiSearchQuery"
                                               placeholder="{{ __('Tìm sản phẩm, sub_id, mã đơn...') }}"
                                               class="w-full pl-9 pr-3 py-1.5 rounded-xl border border-gray-200 dark:border-slate-800 bg-transparent text-xs text-gray-850 dark:text-slate-100 placeholder-gray-400 focus:ring-1 focus:ring-shopee focus:border-shopee focus:outline-none">
                                    </div>
                                    <!-- Tabs lọc nền tảng -->
                                    <div class="flex items-center gap-1 bg-gray-100 dark:bg-slate-800 p-0.5 rounded-lg shrink-0">
                                        <button @click="syncApiPlatformFilter = 'all'" :class="syncApiPlatformFilter === 'all' ? 'bg-white dark:bg-slate-700 text-gray-800 dark:text-white shadow-sm' : 'text-gray-500 dark:text-gray-400'" class="px-2 py-1 rounded-md text-[9px] font-bold transition-all whitespace-nowrap">{{ __('Tất cả') }}</button>
                                        <button @click="syncApiPlatformFilter = 'shopee'" :class="syncApiPlatformFilter === 'shopee' ? 'text-white shadow-sm' : 'text-gray-500 dark:text-gray-400'" :style="syncApiPlatformFilter === 'shopee' ? 'background-color:#ee4d2d' : ''" class="px-2 py-1 rounded-md text-[9px] font-bold transition-all whitespace-nowrap">Shopee</button>
                                        <button @click="syncApiPlatformFilter = 'tiktok'" :class="syncApiPlatformFilter === 'tiktok' ? 'bg-black text-white shadow-sm' : 'text-gray-500 dark:text-gray-400'" class="px-2 py-1 rounded-md text-[9px] font-bold transition-all whitespace-nowrap">TikTok</button>
                                        <button @click="syncApiPlatformFilter = 'lazada'" :class="syncApiPlatformFilter === 'lazada' ? 'text-white shadow-sm' : 'text-gray-500 dark:text-gray-400'" :style="syncApiPlatformFilter === 'lazada' ? 'background-color:#0f156d' : ''" class="px-2 py-1 rounded-md text-[9px] font-bold transition-all whitespace-nowrap">Lazada</button>
                                    </div>
                                    <!-- Tabs lọc trạng thái -->
                                    <div class="flex items-center gap-1 bg-gray-100 dark:bg-slate-800 p-0.5 rounded-lg shrink-0 overflow-x-auto">
                                        <button @click="syncApiActiveTab = 'all'" :class="syncApiActiveTab === 'all' ? 'bg-white dark:bg-slate-700 text-gray-800 dark:text-white shadow-sm' : 'text-gray-500 dark:text-gray-400'" class="px-2 py-1 rounded-md text-[9px] font-bold transition-all whitespace-nowrap">{{ __('Tất cả') }}</button>
                                        <button @click="syncApiActiveTab = 'approved'" :class="syncApiActiveTab === 'approved' ? 'bg-green-500 text-white shadow-sm' : 'text-gray-500 dark:text-gray-400'" class="px-2 py-1 rounded-md text-[9px] font-bold transition-all whitespace-nowrap">{{ __('Duyệt') }}</button>
                                        <button @click="syncApiActiveTab = 'rejected'" :class="syncApiActiveTab === 'rejected' ? 'bg-red-500 text-white shadow-sm' : 'text-gray-500 dark:text-gray-400'" class="px-2 py-1 rounded-md text-[9px] font-bold transition-all whitespace-nowrap">{{ __('Hủy') }}</button>
                                        <button @click="syncApiActiveTab = 'already_processed'" :class="syncApiActiveTab === 'already_processed' ? 'bg-blue-500 text-white shadow-sm' : 'text-gray-500 dark:text-gray-400'" class="px-2 py-1 rounded-md text-[9px] font-bold transition-all whitespace-nowrap">{{ __('Đã xử lý') }}</button>
                                        <button @click="syncApiActiveTab = 'ignored'" :class="syncApiActiveTab === 'ignored' ? 'bg-gray-500 text-white shadow-sm' : 'text-gray-500 dark:text-gray-400'" class="px-2 py-1 rounded-md text-[9px] font-bold transition-all whitespace-nowrap">{{ __('Bỏ qua') }}</button>
                                    </div>
                                </div>
                            </div>

                            <div class="overflow-x-auto rounded-2xl border border-gray-100 dark:border-slate-800 max-h-[520px] overflow-y-auto">
                                <table class="w-full text-left border-collapse whitespace-nowrap text-nowrap">
                                    <thead class="sticky top-0 bg-white dark:bg-slate-900 z-10">
                                        <tr class="bg-gray-50/50 dark:bg-slate-800/30 border-b border-gray-100 dark:border-slate-800 text-[9px] font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500">
                                            <th class="p-2.5">{{ __('Nền tảng') }}</th>
                                            <th class="p-2.5">{{ __('Thời gian / Mã đơn') }}</th>
                                            <th class="p-2.5">{{ __('utm_content / sub_id') }}</th>
                                            <th class="p-2.5">{{ __('Tên sản phẩm') }}</th>
                                            <th class="p-2.5">{{ __('Giá trị / Hoa hồng') }}</th>
                                            <th class="p-2.5">{{ __('Hoàn tiền khách') }}</th>
                                            <th class="p-2.5">{{ __('Lợi nhuận') }}</th>
                                            <th class="p-2.5 text-center">{{ __('Trạng thái') }}</th>
                                            <th class="p-2.5">{{ __('Hành động đối soát') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100 dark:divide-slate-800 text-[11px]">
                                        <template x-for="item in syncApiData?.details" :key="item.order_sn + (item.platform || '')">
                                            <tr x-show="(syncApiActiveTab === 'all' || syncApiActiveTab === item.status_system) && (syncApiPlatformFilter === 'all' || syncApiPlatformFilter === (item.platform || 'shopee')) && (!syncApiSearchQuery || (item.product_name || '').toLowerCase().includes(syncApiSearchQuery.toLowerCase()) || (item.utm_content || '').toLowerCase().includes(syncApiSearchQuery.toLowerCase()) || (item.order_sn || '').toLowerCase().includes(syncApiSearchQuery.toLowerCase()))"
                                                class="hover:bg-gray-50/30 dark:hover:bg-slate-800/10 transition-colors">
                                                <!-- Nền tảng -->
                                                <td class="p-2.5">
                                                    <template x-if="item.platform === 'tiktok'">
                                                        <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[8px] font-extrabold bg-black text-white shadow-sm">
                                                            <svg class="w-2.5 h-2.5" viewBox="0 0 24 24" fill="currentColor"><path d="M19.59 6.69a4.83 4.83 0 01-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 01-2.88 2.5 2.89 2.89 0 01-2.89-2.89 2.89 2.89 0 012.89-2.89c.28 0 .54.04.79.1V9.01a6.33 6.33 0 00-.79-.05A6.34 6.34 0 003.15 15.3a6.34 6.34 0 006.34 6.34 6.34 6.34 0 006.34-6.34V8.69a8.18 8.18 0 004.78 1.52V6.76a4.85 4.85 0 01-1.02-.07z"/></svg>
                                                            TikTok
                                                        </span>
                                                    </template>
                                                    <template x-if="item.platform === 'lazada'">
                                                        <span class="inline-flex px-1.5 py-0.5 rounded text-[8px] font-extrabold text-white shadow-sm" style="background-color: #0f156d;">Lazada</span>
                                                    </template>
                                                    <template x-if="item.platform !== 'tiktok' && item.platform !== 'lazada'">
                                                        <span class="inline-flex px-1.5 py-0.5 rounded text-[8px] font-extrabold text-white shadow-sm" style="background-color: #ee4d2d;">Shopee</span>
                                                    </template>
                                                    <span class="block text-[8px] text-gray-400 dark:text-gray-500 mt-0.5 max-w-[80px] truncate" x-text="item.source_name || ''" :title="item.source_name"></span>
                                                </td>
                                                <!-- Thời gian + Mã đơn -->
                                                <td class="p-2.5 whitespace-nowrap">
                                                    <span class="block text-[9px] text-gray-400" x-text="item.purchase_time"></span>
                                                    <span class="block font-mono font-bold text-gray-800 dark:text-gray-200" x-text="item.order_sn"></span>
                                                </td>
                                                <!-- utm_content / sub_id -->
                                                <td class="p-2.5 font-mono text-[10px] text-gray-600 dark:text-gray-400" x-text="item.utm_content || '-'"></td>
                                                <!-- Tên sản phẩm -->
                                                <td class="p-2.5 max-w-[200px] truncate text-gray-650 dark:text-gray-400" :title="item.product_name" x-text="item.product_name"></td>
                                                <!-- Giá trị / Hoa hồng -->
                                                <td class="p-2.5 whitespace-nowrap">
                                                    <span class="block text-[10px] text-gray-500 dark:text-gray-450">{{ __('Giá trị:') }} <strong class="text-gray-700 dark:text-gray-300" x-text="new Intl.NumberFormat('vi-VN', {style: 'currency', currency: 'VND'}).format(item.actual_amount)"></strong></span>
                                                    <span class="block text-[10px] text-green-600 dark:text-green-500">{{ __('Hoa hồng:') }} <strong x-text="new Intl.NumberFormat('vi-VN', {style: 'currency', currency: 'VND'}).format(item.commission)"></strong></span>
                                                </td>
                                                <!-- Hoàn tiền khách -->
                                                <td class="p-2.5 whitespace-nowrap">
                                                    <span class="block text-[10px] text-shopee dark:text-orange-500 font-bold" x-text="new Intl.NumberFormat('vi-VN', {style: 'currency', currency: 'VND'}).format(item.cashback_amount || 0)"></span>
                                                </td>
                                                <!-- Lợi nhuận (Hoa hồng nhận - Hoàn tiền khách) -->
                                                <td class="p-2.5 whitespace-nowrap">
                                                    <span class="block text-[10px] text-blue-600 dark:text-blue-500 font-bold" x-text="new Intl.NumberFormat('vi-VN', {style: 'currency', currency: 'VND'}).format(item.commission - (item.cashback_amount || 0))"></span>
                                                </td>
                                                <!-- Trạng thái nền tảng -->
                                                <td class="p-2.5 text-center whitespace-nowrap">
                                                    <span class="px-2 py-0.5 rounded-full text-[9px] font-bold"
                                                          :class="{
                                                              'bg-green-50 dark:bg-green-950/20 text-green-600 border border-green-100 dark:border-green-900/20':
                                                                  (item.status_shopee || '').toLowerCase() === 'completed' ||
                                                                  (item.status_shopee || '').toLowerCase() === 'hoàn thành' ||
                                                                  (item.status_shopee || '').toLowerCase() === 'đơn hợp lệ' ||
                                                                  (item.status_shopee || '').toLowerCase().includes('thanh toán') ||
                                                                  (item.status_shopee || '').toLowerCase().includes('đối soát'),
                                                              'bg-red-50 dark:bg-red-950/20 text-red-600 border border-red-100 dark:border-red-900/20':
                                                                  (item.status_shopee || '').toLowerCase().includes('cancel') ||
                                                                  (item.status_shopee || '').toLowerCase().includes('hủy'),
                                                              'bg-gray-100 dark:bg-slate-800 text-gray-500 border border-gray-200 dark:border-slate-700/20': true
                                                          }"
                                                          x-text="item.status_shopee"></span>
                                                </td>
                                                <!-- Hành động đối soát -->
                                                <td class="p-2.5">
                                                    <div class="flex items-center gap-2">
                                                        <span class="px-2 py-0.5 rounded-full text-[9px] font-bold shrink-0 text-white"
                                                              :class="{
                                                                  'bg-green-500': item.status_system === 'approved',
                                                                  'bg-red-500': item.status_system === 'rejected' || item.status_system === 'recalled',
                                                                  'bg-blue-500': item.status_system === 'already_processed',
                                                                  'bg-gray-400': item.status_system === 'ignored',
                                                                  'bg-yellow-500': item.status_system === 'pending'
                                                              }"
                                                              x-text="item.status_system === 'approved' ? '{{ __('Đã duyệt') }}' : (item.status_system === 'rejected' ? '{{ __('Từ chối') }}' : (item.status_system === 'recalled' ? '{{ __('Thu hồi') }}' : (item.status_system === 'already_processed' ? '{{ __('Đã xử lý') }}' : (item.status_system === 'ignored' ? '{{ __('Bỏ qua') }}' : '{{ __('Chờ tiếp') }}'))))"></span>
                                                        <span class="text-[9px] text-gray-500 dark:text-gray-450 font-medium line-clamp-1" x-text="item.message_system"></span>
                                                    </div>
                                                </td>
                                            </tr>
                                        </template>
                                        <template x-if="syncApiData?.details && syncApiData.details.length > 0 && !syncApiHasFilteredItems()">
                                            <tr>
                                                <td colspan="9" class="p-8 text-center text-gray-400 dark:text-gray-500">
                                                    <i data-lucide="search" class="w-8 h-8 mx-auto mb-2 text-gray-300 dark:text-slate-700"></i>
                                                    {{ __('Không tìm thấy đơn hàng nào khớp với từ khóa tìm kiếm.') }}
                                                </td>
                                            </tr>
                                        </template>
                                        <template x-if="!syncApiData?.details || syncApiData?.details.length === 0">
                                            <tr>
                                                <td colspan="9" class="p-8 text-center text-gray-400 dark:text-gray-500">
                                                    <i data-lucide="inbox" class="w-8 h-8 mx-auto mb-2 text-gray-300 dark:text-slate-750"></i>
                                                    {{ __('Không quét được đơn hàng nào trong 30 ngày qua từ tất cả các API.') }}
                                                </td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Footer -->
                        <div class="flex items-center justify-between border-t border-gray-150 dark:border-slate-800/80 pt-4 gap-4">
                            <span class="text-[9px] text-gray-400 dark:text-gray-500 font-medium leading-normal">
                                * {{ __('Dữ liệu ví khả dụng và doanh thu hệ thống đã được đồng bộ hóa tương ứng với các đơn đã duyệt / hủy.') }}
                            </span>
                            <button @click="closeSyncApiModal()"
                                    class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-2xl text-xs transition-all shadow-lg shadow-emerald-600/15 flex items-center gap-2 shrink-0">
                                <i data-lucide="check" class="w-4 h-4"></i>
                                {{ __('Hoàn tất & Tải lại trang') }}
                            </button>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </template>

    <!-- MODAL: THỐNG KÊ ĐƠN HOÀN TIỀN (Chart.js) -->
    <template x-teleport="body">
        <div x-show="cashbackStatsModalOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-slate-950/40 backdrop-blur-sm" x-transition>
            <div class="bg-white dark:bg-slate-900 rounded-3xl max-w-4xl w-full shadow-2xl border border-gray-100 dark:border-slate-800 overflow-hidden" 
                 @click.away="cashbackStatsModalOpen = false"
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
                            <h3 class="font-bold text-gray-900 dark:text-slate-200 text-sm">{{ __('Thống kê đơn hoàn tiền') }}</h3>
                            <p class="text-[10px] text-gray-500 dark:text-slate-450 font-medium">{{ __('Biểu đồ đơn hoàn tiền, doanh thu & lợi nhuận') }}</p>
                        </div>
                    </div>
                    <button @click="cashbackStatsModalOpen = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-slate-350 transition-colors">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>

                <!-- Body -->
                <div class="p-6 space-y-4">
                    <!-- Hàng điều khiển: Tabs khoảng thời gian + Tabs loại biểu đồ -->
                    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                        <!-- Tabs khoảng thời gian -->
                        <div class="inline-flex bg-gray-100 dark:bg-slate-800 rounded-xl p-1 gap-1">
                            <button @click="loadCashbackStats('week')" 
                                    class="px-3 py-1.5 text-[11px] font-bold rounded-lg transition-all"
                                    :class="cbStatsPeriod === 'week' ? 'bg-white dark:bg-slate-700 text-indigo-600 dark:text-indigo-400 shadow-sm' : 'text-gray-500 dark:text-slate-400 hover:text-gray-700'">
                                {{ __('Tuần') }}
                            </button>
                            <button @click="loadCashbackStats('month')" 
                                    class="px-3 py-1.5 text-[11px] font-bold rounded-lg transition-all"
                                    :class="cbStatsPeriod === 'month' ? 'bg-white dark:bg-slate-700 text-indigo-600 dark:text-indigo-400 shadow-sm' : 'text-gray-500 dark:text-slate-400 hover:text-gray-700'">
                                {{ __('Tháng') }}
                            </button>
                            <button @click="loadCashbackStats('year')" 
                                    class="px-3 py-1.5 text-[11px] font-bold rounded-lg transition-all"
                                    :class="cbStatsPeriod === 'year' ? 'bg-white dark:bg-slate-700 text-indigo-600 dark:text-indigo-400 shadow-sm' : 'text-gray-500 dark:text-slate-400 hover:text-gray-700'">
                                {{ __('Năm') }}
                            </button>
                        </div>
                        <!-- Tabs loại biểu đồ: Số đơn / Doanh thu / Tiền hoàn / Lợi nhuận -->
                        <div class="inline-flex bg-gray-100 dark:bg-slate-800 rounded-xl p-1 gap-1">
                            <button @click="switchChartView('orders')" 
                                    class="px-3 py-1.5 text-[11px] font-bold rounded-lg transition-all flex items-center gap-1"
                                    :class="cbChartView === 'orders' ? 'bg-white dark:bg-slate-700 text-indigo-600 dark:text-indigo-400 shadow-sm' : 'text-gray-500 dark:text-slate-400 hover:text-gray-700'">
                                <i data-lucide="hash" class="w-3 h-3"></i> {{ __('Số đơn') }}
                            </button>
                            <button @click="switchChartView('revenue')" 
                                    class="px-3 py-1.5 text-[11px] font-bold rounded-lg transition-all flex items-center gap-1"
                                    :class="cbChartView === 'revenue' ? 'bg-white dark:bg-slate-700 text-indigo-600 dark:text-indigo-400 shadow-sm' : 'text-gray-500 dark:text-slate-400 hover:text-gray-700'">
                                <i data-lucide="coins" class="w-3 h-3"></i> {{ __('Doanh thu') }}
                            </button>
                            <button @click="switchChartView('cashback')" 
                                    class="px-3 py-1.5 text-[11px] font-bold rounded-lg transition-all flex items-center gap-1"
                                    :class="cbChartView === 'cashback' ? 'bg-white dark:bg-slate-700 text-shopee shadow-sm' : 'text-gray-500 dark:text-slate-400 hover:text-gray-700'">
                                <i data-lucide="wallet" class="w-3 h-3"></i> {{ __('Tiền hoàn') }}
                            </button>
                            <button @click="switchChartView('profit')" 
                                    class="px-3 py-1.5 text-[11px] font-bold rounded-lg transition-all flex items-center gap-1"
                                    :class="cbChartView === 'profit' ? 'bg-white dark:bg-slate-700 text-emerald-600 dark:text-emerald-400 shadow-sm' : 'text-gray-500 dark:text-slate-400 hover:text-gray-700'">
                                <i data-lucide="trending-up" class="w-3 h-3"></i> {{ __('Lợi nhuận') }}
                            </button>
                        </div>
                    </div>

                    <!-- Cards tổng hợp - thay đổi theo tab đang chọn -->
                    <!-- View: Số đơn -->
                    <div x-show="cbChartView === 'orders'" class="grid grid-cols-4 gap-3">
                        <div class="p-2.5 bg-gray-50 dark:bg-slate-800/30 rounded-2xl border border-gray-100 dark:border-slate-800/50 text-center">
                            <p class="text-[8px] font-bold text-gray-400 dark:text-slate-500 uppercase tracking-widest">{{ __('Tổng') }}</p>
                            <p class="text-base font-black text-gray-800 dark:text-slate-200" x-text="cbStatsTotals.all">0</p>
                        </div>
                        <div class="p-2.5 bg-amber-50/60 dark:bg-amber-950/10 rounded-2xl border border-amber-100 dark:border-amber-900/30 text-center">
                            <p class="text-[8px] font-bold text-amber-500 uppercase tracking-widest">{{ __('Chờ duyệt') }}</p>
                            <p class="text-base font-black text-amber-600 dark:text-amber-400" x-text="cbStatsTotals.pending">0</p>
                        </div>
                        <div class="p-2.5 bg-emerald-50/60 dark:bg-emerald-950/10 rounded-2xl border border-emerald-100 dark:border-emerald-900/30 text-center">
                            <p class="text-[8px] font-bold text-emerald-500 uppercase tracking-widest">{{ __('Thành công') }}</p>
                            <p class="text-base font-black text-emerald-600 dark:text-emerald-400" x-text="cbStatsTotals.approved">0</p>
                        </div>
                        <div class="p-2.5 bg-rose-50/60 dark:bg-rose-950/10 rounded-2xl border border-rose-100 dark:border-rose-900/30 text-center">
                            <p class="text-[8px] font-bold text-rose-500 uppercase tracking-widest">{{ __('Từ chối') }}</p>
                            <p class="text-base font-black text-rose-600 dark:text-rose-400" x-text="cbStatsTotals.rejected">0</p>
                        </div>
                    </div>
                    <!-- View: Doanh thu -->
                    <div x-show="cbChartView === 'revenue'" class="grid grid-cols-2 gap-3">
                        <div class="p-3 bg-indigo-50/60 dark:bg-indigo-950/10 rounded-2xl border border-indigo-100 dark:border-indigo-900/30 text-center">
                            <p class="text-[8px] font-bold text-indigo-500 uppercase tracking-widest">{{ __('Doanh thu đã duyệt') }}</p>
                            <p class="text-base font-black text-indigo-600 dark:text-indigo-400" x-text="formatCurrency(cbStatsTotals.commission)">0đ</p>
                        </div>
                        <div class="p-3 bg-blue-50/60 dark:bg-blue-950/10 rounded-2xl border border-blue-100 dark:border-blue-900/30 text-center">
                            <p class="text-[8px] font-bold text-blue-500 uppercase tracking-widest">{{ __('Doanh thu chờ duyệt') }}</p>
                            <p class="text-base font-black text-blue-600 dark:text-blue-400" x-text="formatCurrency(cbStatsTotals.commission_pending)">0đ</p>
                        </div>
                    </div>
                    <!-- View: Tiền hoàn -->
                    <div x-show="cbChartView === 'cashback'" class="grid grid-cols-2 gap-3">
                        <div class="p-3 bg-shopee/5 dark:bg-shopee/10 rounded-2xl border border-shopee/10 dark:border-shopee/20 text-center">
                            <p class="text-[8px] font-bold text-shopee uppercase tracking-widest">{{ __('Tiền hoàn khách đã duyệt') }}</p>
                            <p class="text-base font-black text-shopee" x-text="formatCurrency(cbStatsTotals.cashback)">0đ</p>
                        </div>
                        <div class="p-3 bg-amber-50/60 dark:bg-amber-950/10 rounded-2xl border border-amber-100 dark:border-amber-900/30 text-center">
                            <p class="text-[8px] font-bold text-amber-500 uppercase tracking-widest">{{ __('Tiền hoàn khách chưa duyệt') }}</p>
                            <p class="text-base font-black text-amber-600 dark:text-amber-400" x-text="formatCurrency(cbStatsTotals.cashback_pending)">0đ</p>
                        </div>
                    </div>
                    <!-- View: Lợi nhuận -->
                    <div x-show="cbChartView === 'profit'" class="grid grid-cols-2 gap-3">
                        <div class="p-3 bg-emerald-50/60 dark:bg-emerald-950/10 rounded-2xl border border-emerald-100 dark:border-emerald-900/30 text-center">
                            <p class="text-[8px] font-bold text-emerald-500 uppercase tracking-widest">{{ __('Lợi nhuận đã duyệt') }}</p>
                            <p class="text-base font-black text-emerald-600 dark:text-emerald-400" x-text="formatCurrency(cbStatsTotals.profit)">0đ</p>
                        </div>
                        <div class="p-3 bg-blue-50/60 dark:bg-blue-950/10 rounded-2xl border border-blue-100 dark:border-blue-900/30 text-center">
                            <p class="text-[8px] font-bold text-blue-500 uppercase tracking-widest">{{ __('Lợi nhuận chờ duyệt') }}</p>
                            <p class="text-base font-black text-blue-600 dark:text-blue-400" x-text="formatCurrency(cbStatsTotals.profit_pending)">0đ</p>
                        </div>
                    </div>

                    <!-- Biểu đồ -->
                    <div class="relative bg-gray-50/50 dark:bg-slate-950/30 rounded-2xl p-4 border border-gray-100 dark:border-slate-800/50" style="min-height: 300px;">
                        <div x-show="cbStatsLoading" class="absolute inset-0 flex items-center justify-center bg-white/60 dark:bg-slate-900/60 rounded-2xl z-10">
                            <svg class="animate-spin h-8 w-8 text-indigo-500" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </div>
                        <canvas id="cashbackStatsChart" height="280"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </template>
</div>
@endsection

@section('scripts')
<script>
    function cashbackAdminHandler() {
        return {
            showFilter: @json($hasFilter),
            rejectModalOpen: false,
            detailModalOpen: false,
            approveModalOpen: false,
            bulkApproveModalOpen: false,
            bulkRejectModalOpen: false,
            bulkDeleteModalOpen: false,
            deleteModalOpen: false,
            activeCashback: {},
            activeApproveCashback: {},
            activeDeleteCashback: {},
            detailCashback: {},
            confirmDeleteCheckbox: false,
            confirmBulkDeleteCheckbox: false,

            // Dữ liệu click logs cho modal chi tiết
            clickLogs: [],
            clickLogsTotal: 0,
            clickLogsTotalClicks: 0,
            clickLogsLoading: false,

            // Dữ liệu hoa hồng MLM cho modal chi tiết
            commissions: [],
            commissionsLoading: false,

            // Thuộc tính phục vụ bulk actions
            selectedCashbacks: [],
            allSelected: false,
            selectableCashbackIds: @json($selectableCashbackIds),
            bulkRejectedReasonInput: '',
            confirmBulkText: '',
            isSubmitting: false,

            // Biến trạng thái cho modal Sync API (Shopee + TikTok + Lazada)
            syncApiModalOpen: false,
            syncApiLoading: false,
            syncApiData: null,
            syncApiError: null,
            syncApiActiveTab: 'all',
            syncApiSearchQuery: '',
            syncApiPlatformFilter: 'all',
            syncApiHasFilteredItems() {
                if (!this.syncApiData || !this.syncApiData.details) return false;
                return this.syncApiData.details.some(item => {
                    const matchTab = this.syncApiActiveTab === 'all' || this.syncApiActiveTab === item.status_system;
                    const matchPlatform = this.syncApiPlatformFilter === 'all' || this.syncApiPlatformFilter === (item.platform || 'shopee');
                    const matchSearch = !this.syncApiSearchQuery ||
                        (item.product_name || '').toLowerCase().includes(this.syncApiSearchQuery.toLowerCase()) ||
                        (item.utm_content || '').toLowerCase().includes(this.syncApiSearchQuery.toLowerCase()) ||
                        (item.order_sn || '').toLowerCase().includes(this.syncApiSearchQuery.toLowerCase());
                    return matchTab && matchPlatform && matchSearch;
                });
            },

            // Biến trạng thái cho modal thống kê đơn hoàn tiền
            cashbackStatsModalOpen: false,
            cbStatsPeriod: 'week',
            cbStatsTotals: { all: 0, pending: 0, approved: 0, rejected: 0, cashback: 0, cashback_pending: 0, profit: 0, profit_pending: 0, commission: 0, commission_pending: 0 },
            cbStatsLoading: false,
            // Không lưu instance chart vào AlpineJS reactive state (cbStatsChart) để tránh xung đột Proxy làm trắng canvas khi chuyển tab nhiều lần.
            // Biểu đồ thực tế được gắn trực tiếp trên đối tượng canvas (canvas._chartInstance).
            cbChartView: 'orders', // 'orders' | 'revenue' | 'cashback' | 'profit'
            cbStatsRawData: null, // Lưu cache dữ liệu thô để chuyển tab không cần gọi API lại

            // Thuộc tính phục vụ lọc dữ liệu và chuyển trang qua AJAX
            activeStatus: @json(request('status') ?? ''),
            statusCounts: @json($statusCounts),

            // Hàm xử lý việc thay đổi cột và hướng sắp xếp (tăng/giảm) danh sách đơn hoàn tiền
            changeSort(field) {
                const form = document.getElementById('cashback-filter-form');
                if (!form) return;

                const sortByInput = form.querySelector('input[name="sort_by"]');
                const sortOrderInput = form.querySelector('input[name="sort_order"]');
                if (!sortByInput || !sortOrderInput) return;

                let currentSort = sortByInput.value;
                let currentOrder = sortOrderInput.value;

                if (currentSort === field) {
                    // Nếu click lại cột đang sắp xếp -> đảo chiều (tăng dần/giảm dần)
                    sortOrderInput.value = currentOrder === 'asc' ? 'desc' : 'asc';
                } else {
                    // Nếu click sang cột mới -> mặc định sắp xếp giảm dần (desc)
                    sortByInput.value = field;
                    sortOrderInput.value = 'desc';
                }
                
                // Tự động build lại URL theo form tìm kiếm hiện tại và thực hiện tải AJAX
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

            // Hàm khởi tạo tự động khi component AlpineJS sẵn sàng
            init() {
                // Sử dụng Event Delegation để bắt sự kiện click phân trang mượt mà bằng AJAX
                const container = document.getElementById('cashback-table-container');
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

            // Hàm thực hiện gọi AJAX cập nhật bảng và số lượng đếm đơn hàng
            async fetchData(url) {
                const loadingEl = document.getElementById('cashback-loading');
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
                    
                    // Cập nhật HTML bảng đơn hàng
                    const tableContentEl = document.getElementById('cashback-table-content');
                    if (tableContentEl) {
                        tableContentEl.innerHTML = data.html;
                    }

                    // Cập nhật số lượng đếm trạng thái trong AlpineJS
                    if (data.statusCounts) {
                        this.statusCounts = data.statusCounts;
                    }

                    // Cập nhật danh sách ID được chọn
                    if (data.selectableCashbackIds) {
                        this.selectableCashbackIds = data.selectableCashbackIds;
                    }

                    // Reset trạng thái chọn checkbox hàng loạt
                    this.selectedCashbacks = [];
                    this.allSelected = false;

                    // Khởi tạo lại các icon Lucide trong HTML vừa cập nhật
                    if (window.lucide) {
                        window.lucide.createIcons();
                    }

                    // Cập nhật URL trình duyệt để lưu lại trạng thái lọc khi tải lại trang
                    window.history.pushState({}, '', url);

                } catch (error) {
                    console.error('AJAX Load Error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Lỗi tải dữ liệu',
                        text: 'Không thể kết nối đến máy chủ. Vui lòng thử lại sau.'
                    });
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

                // Loại bỏ tham số rỗng để URL gọn gàng
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
                const form = document.getElementById('cashback-filter-form');
                if (form) {
                    form.reset();
                    form.querySelectorAll('input').forEach(input => input.value = '');
                    form.querySelectorAll('select').forEach(select => select.value = select.options[0]?.value || '');
                }
                this.activeStatus = '';
                this.fetchData('{{ route("admin.cashback.index") }}');
            },

            // Kiểm tra xem bộ lọc có đang hoạt động hay không
            // Hàm này kiểm tra tất cả các giá trị của các ô tìm kiếm để xác định có hiển thị chỉ báo đang lọc hay không
            hasFilterActive() {
                const orderCode = document.querySelector('input[name="order_code"]')?.value || '';
                const productName = document.querySelector('input[name="product_name"]')?.value || '';
                const userSearch = document.querySelector('input[name="user_search"]')?.value || '';
                const platform = document.querySelector('select[name="platform"]')?.value || '';
                const limit = document.querySelector('select[name="limit"]')?.value || '15';
                const startDate = document.querySelector('input[name="start_date"]')?.value || '';
                const endDate = document.querySelector('input[name="end_date"]')?.value || '';
                
                return orderCode.trim() !== '' || 
                       productName.trim() !== '' || 
                       userSearch.trim() !== '' || 
                       platform.trim() !== '' || 
                       startDate.trim() !== '' || 
                       endDate.trim() !== '' || 
                       this.activeStatus !== '' || 
                       limit !== '15';
            },

            toggleAll() {
                if (this.allSelected) {
                    this.selectedCashbacks = [...this.selectableCashbackIds];
                } else {
                    this.selectedCashbacks = [];
                }
            },

            openSyncApiModal() {
                this.syncApiModalOpen = true;
                this.syncApiLoading = true;
                this.syncApiData = null;
                this.syncApiError = null;
                this.syncApiActiveTab = 'all';
                this.syncApiSearchQuery = '';
                this.syncApiPlatformFilter = 'all';

                fetch('{{ route("admin.cashback.sync_api") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                })
                .then(response => {
                    if (!response.ok) return response.json().then(err => { throw err; });
                    return response.json();
                })
                .then(data => {
                    this.syncApiLoading = false;
                    this.syncApiData = data;
                    setTimeout(() => { if (window.lucide) window.lucide.createIcons(); }, 100);
                })
                .catch(err => {
                    this.syncApiLoading = false;
                    this.syncApiError = err.error || err.message || '{{ __("Lỗi kết nối không xác định.") }}';
                    setTimeout(() => { if (window.lucide) window.lucide.createIcons(); }, 100);
                });
            },

            closeSyncApiModal() {
                this.syncApiModalOpen = false;
                if (!this.syncApiLoading && (this.syncApiData || this.syncApiError)) {
                    window.location.reload();
                }
            },

            openRejectModal(item) {
                this.activeCashback = { ...item };
                this.rejectModalOpen = true;
                setTimeout(() => { if (window.lucide) window.lucide.createIcons(); }, 50);
            },

            openApproveModal(item) {
                this.activeApproveCashback = { ...item };
                this.approveModalOpen = true;
                setTimeout(() => { if (window.lucide) window.lucide.createIcons(); }, 50);
            },

            // Mở modal xác nhận xóa 1 đơn hàng hoàn tiền (kèm checkbox và danh sách thông tin sẽ xóa)
            openDeleteModal(item) {
                this.activeDeleteCashback = { ...item };
                this.confirmDeleteCheckbox = false;
                this.deleteModalOpen = true;
                setTimeout(() => { if (window.lucide) window.lucide.createIcons(); }, 50);
            },

            // Mở modal xác nhận xóa hàng loạt các đơn hàng hoàn tiền đã chọn
            openBulkDeleteModal() {
                this.confirmBulkText = '';
                this.confirmBulkDeleteCheckbox = false;
                this.bulkDeleteModalOpen = true;
                setTimeout(() => { if (window.lucide) window.lucide.createIcons(); }, 50);
            },

            openDetailModal(item) {
                this.detailCashback = { ...item };
                this.detailModalOpen = true;

                // Reset và tải dữ liệu qua AJAX
                this.clickLogs = [];
                this.clickLogsTotal = 0;
                this.clickLogsTotalClicks = 0;
                this.clickLogsLoading = true;
                this.commissions = [];
                this.commissionsLoading = true;

                fetch('/' + window.adminPrefix + '/cashback/' + item.id + '/click-logs', {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(res => res.json())
                .then(data => {
                    // Cập nhật click logs
                    this.clickLogs = data.logs || [];
                    this.clickLogsTotal = data.total || 0;
                    this.clickLogsTotalClicks = data.total_clicks || 0;
                    this.clickLogsLoading = false;

                    // Cập nhật hoa hồng MLM
                    this.commissions = data.commissions || [];
                    this.commissionsLoading = false;
                })
                .catch(() => {
                    this.clickLogsLoading = false;
                    this.commissionsLoading = false;
                });

                setTimeout(() => { if (window.lucide) window.lucide.createIcons(); }, 50);
            },

            openBulkApproveModal() {
                this.bulkApproveModalOpen = true;
                setTimeout(() => { if (window.lucide) window.lucide.createIcons(); }, 50);
            },

            openBulkRejectModal() {
                this.bulkRejectedReasonInput = '';
                this.bulkRejectModalOpen = true;
                setTimeout(() => { if (window.lucide) window.lucide.createIcons(); }, 50);
            },

            async submitBulkApprove() {
                if (this.isSubmitting) return;
                this.isSubmitting = true;
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

                try {
                    const response = await fetch('{{ route("admin.cashback.bulk_approve") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            ids: this.selectedCashbacks.join(',')
                        })
                    });

                    const data = await response.json();

                    if (data.status === 'success') {
                        this.bulkApproveModalOpen = false;
                        this.selectedCashbacks = [];
                        this.allSelected = false;

                        await Swal.fire({
                            icon: 'success',
                            title: 'Thành công',
                            text: data.message,
                            timer: 2000,
                            showConfirmButton: false
                        });

                        window.location.reload();
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Thất bại',
                            text: data.message || 'Có lỗi xảy ra.'
                        });
                    }
                } catch (error) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Lỗi hệ thống',
                        text: 'Không thể kết nối đến máy chủ. Vui lòng thử lại sau.'
                    });
                } finally {
                    this.isSubmitting = false;
                }
            },

            async submitBulkReject() {
                if (this.isSubmitting) return;

                if (!this.bulkRejectedReasonInput || !this.bulkRejectedReasonInput.trim()) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Lỗi dữ liệu',
                        text: 'Vui lòng nhập lý do từ chối.'
                    });
                    return;
                }

                this.isSubmitting = true;
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

                try {
                    const response = await fetch('{{ route("admin.cashback.bulk_reject") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            ids: this.selectedCashbacks.join(','),
                            rejected_reason: this.bulkRejectedReasonInput
                        })
                    });

                    const data = await response.json();

                    if (data.status === 'success') {
                        this.bulkRejectModalOpen = false;
                        this.bulkRejectedReasonInput = '';
                        this.selectedCashbacks = [];
                        this.allSelected = false;

                        await Swal.fire({
                            icon: 'success',
                            title: 'Thành công',
                            text: data.message,
                            timer: 2000,
                            showConfirmButton: false
                        });

                        window.location.reload();
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Thất bại',
                            text: data.message || 'Có lỗi xảy ra.'
                        });
                    }
                } catch (error) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Lỗi hệ thống',
                        text: 'Không thể kết nối đến máy chủ. Vui lòng thử lại sau.'
                    });
                } finally {
                    this.isSubmitting = false;
                }
            },

            async submitBulkDelete() {
                if (this.isSubmitting) return;

                if (this.confirmBulkText.toUpperCase() !== 'XÓA HÀNG LOẠT' || !this.confirmBulkDeleteCheckbox) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Lỗi dữ liệu',
                        text: 'Vui lòng tích chọn xác nhận và nhập chính xác từ khóa "XÓA HÀNG LOẠT" để tiếp tục.'
                    });
                    return;
                }

                this.isSubmitting = true;
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

                try {
                    // Gọi thẳng phương thức DELETE đúng như khai báo của route.
                    // Lưu ý: KHÔNG dùng POST kèm _method trong body JSON vì Laravel chỉ đọc _method
                    // từ dữ liệu form-encoded hoặc query string, dẫn đến request bị trả về lỗi 405.
                    const response = await fetch('{{ route("admin.cashback.bulk_destroy") }}', {
                        method: 'DELETE',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            ids: this.selectedCashbacks.join(','),
                            confirm_text: this.confirmBulkText
                        })
                    });

                    const data = await response.json();

                    if (data.status === 'success') {
                        this.bulkDeleteModalOpen = false;
                        this.confirmBulkText = '';
                        this.selectedCashbacks = [];
                        this.allSelected = false;

                        await Swal.fire({
                            icon: 'success',
                            title: 'Thành công',
                            text: data.message,
                            timer: 2000,
                            showConfirmButton: false
                        });

                        window.location.reload();
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Thất bại',
                            text: data.message || 'Có lỗi xảy ra.'
                        });
                    }
                } catch (error) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Lỗi hệ thống',
                        text: 'Không thể kết nối đến máy chủ. Vui lòng thử lại sau.'
                    });
                } finally {
                    this.isSubmitting = false;
                }
            },

            // Xác định tên API hiển thị động theo platform khi đồng bộ tự động
            // Tránh hiển thị cứng "Shopee API" cho các đơn hàng TikTok Shop hay Lazada
            getPlatformApiName(platform) {
                if (!platform || platform === 'shopee') return 'Shopee API';
                if (platform === 'tiktok') return 'TikTok API';
                if (platform === 'lazada') return 'Lazada API';
                return platform.toUpperCase() + ' API';
            },

            formatCurrency(value) {
                if (!value) return '0đ';
                return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(value);
            },

            // Diễn giải nguồn xử lý đơn (manual/sync/csv) thành nhãn hiển thị thân thiện
            timelineSourceLabel(source) {
                if (source === 'sync') return '{{ __('Hệ thống · đồng bộ') }} ' + this.getPlatformApiName(this.detailCashback.platform);
                if (source === 'csv') return '{{ __('Hệ thống · đối soát CSV') }}';
                if (source === 'manual') return '{{ __('Quản trị viên · thao tác thủ công') }}';
                return '';
            },

            // Dựng danh sách các mốc thời gian thay đổi trạng thái của đơn hàng để hiển thị timeline.
            // Luôn có mốc "ghi nhận đơn" lấy từ created_at, sau đó là các sự kiện trong status_timeline.
            // Có cơ chế dự phòng cho các đơn cũ chưa lưu status_timeline.
            buildTimeline() {
                const cb = this.detailCashback;
                if (!cb || !cb.created_at) return [];

                const events = [];

                // Mốc đầu tiên: đơn hàng được ghi nhận vào hệ thống
                events.push({
                    event: 'created',
                    at: cb.created_at,
                    title: '{{ __('Đơn hàng được ghi nhận') }}',
                    icon: 'plus-circle',
                    iconColor: 'text-blue-500',
                    titleColor: 'text-gray-800 dark:text-slate-200',
                    ring: 'border-blue-200 dark:border-blue-900/60',
                    source: '{{ __('Khởi tạo từ lượt click link rút gọn') }}',
                });

                // Bản đồ cấu hình hiển thị cho từng loại sự kiện đổi trạng thái
                const map = {
                    approved: {
                        title: '{{ __('Đơn được duyệt hoàn tiền') }}',
                        icon: 'check-circle', iconColor: 'text-green-500',
                        titleColor: 'text-green-600 dark:text-green-400',
                        ring: 'border-green-200 dark:border-green-900/60',
                    },
                    rejected: {
                        title: '{{ __('Đơn bị từ chối') }}',
                        icon: 'x-circle', iconColor: 'text-red-500',
                        titleColor: 'text-red-600 dark:text-red-400',
                        ring: 'border-red-200 dark:border-red-900/60',
                    },
                    clawback: {
                        title: '{{ __('Thu hồi tiền hoàn (đơn bị huỷ)') }}',
                        icon: 'rotate-ccw', iconColor: 'text-orange-500',
                        titleColor: 'text-orange-600 dark:text-orange-400',
                        ring: 'border-orange-200 dark:border-orange-900/60',
                    },
                };

                const timeline = Array.isArray(cb.status_timeline) ? cb.status_timeline : [];

                if (timeline.length > 0) {
                    // Dữ liệu timeline đầy đủ: hiển thị từng sự kiện đã ghi nhận
                    timeline.forEach(t => {
                        const cfg = map[t.event];
                        if (!cfg) return;
                        events.push({
                            ...cfg,
                            at: t.at,
                            source: this.timelineSourceLabel(t.source),
                            reason: t.reason || '',
                            amount: t.amount ? ('{{ __('Số tiền:') }} ' + this.formatCurrency(t.amount)) : '',
                        });
                    });
                } else {
                    // Dự phòng cho đơn cũ chưa lưu status_timeline: suy ra từ trạng thái hiện tại
                    if (cb.status === 'approved' && cb.approved_at) {
                        events.push({ ...map.approved, at: cb.approved_at, source: '', reason: '', amount: '' });
                    } else if (cb.status === 'rejected') {
                        events.push({
                            ...map.rejected,
                            at: cb.approved_at || cb.updated_at || cb.created_at,
                            source: '',
                            reason: cb.rejected_reason || cb.fraud_reason || '',
                            amount: '',
                        });
                    }
                }

                return events;
            },

            formatDate(dateString) {
                if (!dateString) return 'N/A';
                try {
                    const date = new Date(dateString);
                    return date.toLocaleDateString('vi-VN', {
                        day: '2-digit',
                        month: '2-digit',
                        year: 'numeric',
                        hour: '2-digit',
                        minute: '2-digit'
                    });
                } catch (e) {
                    return dateString;
                }
            },

            // Mở modal thống kê đơn hoàn tiền và tải dữ liệu lần đầu
            openCashbackStatsModal() {
                this.cashbackStatsModalOpen = true;
                this.cbChartView = 'orders';
                setTimeout(() => {
                    if (window.lucide) window.lucide.createIcons();
                    this.loadCashbackStats('week');
                }, 100);
            },

            // Chuyển tab biểu đồ (Số đơn / Tiền hoàn / Lợi nhuận) mà không gọi lại API
            switchChartView(view) {
                this.cbChartView = view;
                if (this.cbStatsRawData) {
                    this.renderCashbackChart(this.cbStatsRawData, this.cbStatsPeriod);
                }
                // Refresh icon Lucide cho các tab mới hiển thị
                setTimeout(() => { if (window.lucide) window.lucide.createIcons(); }, 50);
            },

            // Gọi API lấy dữ liệu thống kê đơn hoàn tiền
            async loadCashbackStats(period) {
                this.cbStatsPeriod = period;
                this.cbStatsLoading = true;

                try {
                    const response = await fetch(`{{ route('admin.cashback.stats') }}?period=${period}`, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        }
                    });

                    if (!response.ok) throw new Error('Network error');
                    const result = await response.json();

                    if (result.success) {
                        this.cbStatsTotals = result.totals;
                        // Lưu cache dữ liệu thô để chuyển tab không cần gọi API lại
                        this.cbStatsRawData = result;
                        this.renderCashbackChart(result, period);
                    }
                } catch (error) {
                    console.error('Cashback stats fetch error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: "{{ __('Lỗi tải dữ liệu') }}",
                        text: "{{ __('Không thể tải dữ liệu thống kê. Vui lòng thử lại.') }}"
                    });
                } finally {
                    this.cbStatsLoading = false;
                }
            },

            // Render biểu đồ Chart.js dựa trên tab đang chọn (orders/cashback/profit)
            renderCashbackChart(data, period) {
                const canvas = document.getElementById('cashbackStatsChart');
                if (!canvas) return;

                // Hủy biểu đồ cũ được gắn trên phần tử canvas (tránh lưu trong AlpineJS reactive state gây lỗi xung đột Proxy làm trắng biểu đồ khi chuyển tab nhiều lần)
                if (canvas._chartInstance) {
                    canvas._chartInstance.destroy();
                    canvas._chartInstance = null;
                }

                const ctx = canvas.getContext('2d');
                const periodLabels = {
                    week: "{{ __('7 ngày gần nhất') }}",
                    month: "{{ __('30 ngày gần nhất') }}",
                    year: "{{ __('12 tháng gần nhất') }}"
                };

                // Helper format tiền VND cho tooltip
                const fmtVND = (v) => new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(v);

                let datasets, stacked, tooltipCb, yTickCb;

                if (this.cbChartView === 'orders') {
                    // Biểu đồ stacked bar: 3 trạng thái đơn hàng
                    stacked = true;
                    datasets = [
                        { label: "{{ __('Chờ duyệt') }}", data: data.pending, backgroundColor: 'rgba(245, 158, 11, 0.7)', hoverBackgroundColor: 'rgba(245, 158, 11, 0.9)', borderColor: 'rgba(245, 158, 11, 1)', borderWidth: 1, borderRadius: 4, borderSkipped: false },
                        { label: "{{ __('Thành công') }}", data: data.approved, backgroundColor: 'rgba(16, 185, 129, 0.7)', hoverBackgroundColor: 'rgba(16, 185, 129, 0.9)', borderColor: 'rgba(16, 185, 129, 1)', borderWidth: 1, borderRadius: 4, borderSkipped: false },
                        { label: "{{ __('Từ chối') }}", data: data.rejected, backgroundColor: 'rgba(244, 63, 94, 0.7)', hoverBackgroundColor: 'rgba(244, 63, 94, 0.9)', borderColor: 'rgba(244, 63, 94, 1)', borderWidth: 1, borderRadius: 4, borderSkipped: false }
                    ];
                    tooltipCb = (item) => `${item.dataset.label}: ${item.parsed.y} {{ __('đơn') }}`;
                    yTickCb = (v) => v;
                } else if (this.cbChartView === 'revenue') {
                    // Chuyển sang biểu đồ cột chồng (stacked bar) để gộp doanh thu đã duyệt và chờ duyệt vào 1 cột giúp giao diện gọn gàng hơn
                    stacked = true;
                    datasets = [
                        {
                            label: "{{ __('Doanh thu đã duyệt') }}",
                            data: data.commission_data,
                            backgroundColor: 'rgba(99, 102, 241, 0.7)',
                            hoverBackgroundColor: 'rgba(99, 102, 241, 0.9)',
                            borderColor: 'rgba(99, 102, 241, 1)',
                            borderWidth: 1, borderRadius: 4, borderSkipped: false
                        },
                        {
                            label: "{{ __('Doanh thu chờ duyệt') }}",
                            data: data.commission_pending_data,
                            backgroundColor: 'rgba(59, 130, 246, 0.7)',
                            hoverBackgroundColor: 'rgba(59, 130, 246, 0.9)',
                            borderColor: 'rgba(59, 130, 246, 1)',
                            borderWidth: 1, borderRadius: 4, borderSkipped: false
                        }
                    ];
                    tooltipCb = (item) => `${item.dataset.label}: ${fmtVND(item.parsed.y)}`;
                    yTickCb = (v) => v >= 1000000 ? (v / 1000000).toFixed(1) + 'M' : (v >= 1000 ? (v / 1000).toFixed(0) + 'K' : v);
                } else if (this.cbChartView === 'cashback') {
                    // Chuyển sang biểu đồ cột chồng (stacked bar) để gộp tiền hoàn đã duyệt và chờ duyệt vào 1 cột
                    stacked = true;
                    datasets = [
                        {
                            label: "{{ __('Tiền hoàn đã duyệt') }}",
                            data: data.cashback_data,
                            backgroundColor: 'rgba(238, 77, 45, 0.7)',
                            hoverBackgroundColor: 'rgba(238, 77, 45, 0.9)',
                            borderColor: 'rgba(238, 77, 45, 1)',
                            borderWidth: 1, borderRadius: 4, borderSkipped: false
                        },
                        {
                            label: "{{ __('Tiền hoàn chờ duyệt') }}",
                            data: data.cashback_pending_data,
                            backgroundColor: 'rgba(245, 158, 11, 0.7)',
                            hoverBackgroundColor: 'rgba(245, 158, 11, 0.9)',
                            borderColor: 'rgba(245, 158, 11, 1)',
                            borderWidth: 1, borderRadius: 4, borderSkipped: false
                        }
                    ];
                    tooltipCb = (item) => `${item.dataset.label}: ${fmtVND(item.parsed.y)}`;
                    yTickCb = (v) => v >= 1000000 ? (v / 1000000).toFixed(1) + 'M' : (v >= 1000 ? (v / 1000).toFixed(0) + 'K' : v);
                } else {
                    // Chuyển sang biểu đồ cột chồng (stacked bar) để gộp lợi nhuận đã duyệt và chờ duyệt vào 1 cột duy nhất theo yêu cầu của sếp Thành
                    stacked = true;
                    datasets = [
                        {
                            label: "{{ __('Lợi nhuận đã duyệt') }}",
                            data: data.profit_data,
                            backgroundColor: 'rgba(16, 185, 129, 0.7)',
                            hoverBackgroundColor: 'rgba(16, 185, 129, 0.9)',
                            borderColor: 'rgba(16, 185, 129, 1)',
                            borderWidth: 1, borderRadius: 4, borderSkipped: false
                        },
                        {
                            label: "{{ __('Lợi nhuận chờ duyệt') }}",
                            data: data.profit_pending_data,
                            backgroundColor: 'rgba(59, 130, 246, 0.7)',
                            hoverBackgroundColor: 'rgba(59, 130, 246, 0.9)',
                            borderColor: 'rgba(59, 130, 246, 1)',
                            borderWidth: 1, borderRadius: 4, borderSkipped: false
                        }
                    ];
                    tooltipCb = (item) => `${item.dataset.label}: ${fmtVND(item.parsed.y)}`;
                    yTickCb = (v) => v >= 1000000 ? (v / 1000000).toFixed(1) + 'M' : (v >= 1000 ? (v / 1000).toFixed(0) + 'K' : v);
                }

                // Lưu instance của Chart.js trực tiếp trên DOM node canvas để bỏ qua cơ chế reactive Proxy của AlpineJS
                canvas._chartInstance = new Chart(ctx, {
                    type: 'bar',
                    data: { labels: data.labels, datasets },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        animation: { duration: 600, easing: 'easeInOutQuart' },
                        interaction: { mode: 'index', intersect: false },
                        plugins: {
                            legend: {
                                display: true,
                                position: 'top',
                                labels: { usePointStyle: true, pointStyle: 'circle', padding: 16, font: { size: 11, weight: '600' } }
                            },
                            title: {
                                display: true,
                                text: periodLabels[period] || '',
                                font: { size: 13, weight: '700' },
                                color: '#6366f1',
                                padding: { bottom: 8 }
                            },
                            tooltip: {
                                backgroundColor: 'rgba(15, 23, 42, 0.9)',
                                titleFont: { size: 12, weight: '700' },
                                bodyFont: { size: 11 },
                                padding: 12,
                                cornerRadius: 10,
                                callbacks: { label: tooltipCb }
                            }
                        },
                        scales: {
                            x: {
                                stacked: stacked,
                                grid: { display: false },
                                ticks: { font: { size: 10, weight: '600' }, color: '#94a3b8', maxRotation: period === 'year' ? 45 : 0 }
                            },
                            y: {
                                stacked: stacked,
                                beginAtZero: true,
                                grid: { color: 'rgba(148, 163, 184, 0.1)' },
                                ticks: {
                                    font: { size: 10, weight: '600' },
                                    color: '#94a3b8',
                                    callback: yTickCb,
                                    ...(this.cbChartView === 'orders' ? { stepSize: 1, precision: 0 } : {})
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
