@extends('layouts.admin')

@section('title', __('Nhật Ký Lượt Click Hoàn Tiền') . ' - ' . $siteName)

@php
    $hasFilter = request('trans_id') || request('user_search') || request('product_name') || (request('limit') && request('limit') != 15);
@endphp

@section('content')
<div class="space-y-6" x-data="cashbackClicksAdminHandler()">
    <!-- Tiêu đề & Hành động -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ __('Nhật Ký Lượt Click Hoàn Tiền') }}</h1>
            <p class="text-sm text-gray-500 dark:text-slate-400">{{ __('Theo dõi chi tiết các lượt click tạo link mua sắm của thành viên và trạng thái chuyển đổi thành đơn hàng thực tế') }}</p>
        </div>
        <div class="flex items-center gap-2">
            <!-- Nút thống kê click chuyên nghiệp -->
            <button @click="openClicksStatsModal()" 
                    class="inline-flex items-center gap-1.5 px-4 py-2.5 text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded-xl transition-all shadow-md">
                <i data-lucide="bar-chart-3" class="w-4 h-4"></i>
                <span>{{ __('Thống kê click') }}</span>
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

            <!-- Nút dọn dẹp nhật ký click hoàn tiền -->
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
         class="bg-white dark:bg-slate-900 p-4 rounded-3xl shadow-sm border border-gray-200 dark:border-slate-800"
         style="display: {{ $hasFilter ? 'block' : 'none' }}">
        <form action="{{ route('admin.logs.cashback_clicks') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
            <div>
                <select name="limit" class="block w-full px-3 py-2 border border-gray-200 dark:border-slate-700 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-gray-50/50 dark:bg-slate-950 dark:text-white">
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
                    <i data-lucide="key" class="w-4 h-4"></i>
                </div>
                <input type="text" 
                       name="trans_id" 
                       value="{{ request('trans_id') }}"
                       placeholder="{{ __('Mã Trans ID...') }}" 
                       class="block w-full pl-9 pr-4 py-2 border border-gray-200 dark:border-slate-700 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee transition-all bg-gray-50/50 dark:bg-slate-950 dark:text-white">
            </div>

            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                    <i data-lucide="user" class="w-4 h-4"></i>
                </div>
                <input type="text" 
                       name="user_search" 
                       value="{{ request('user_search') }}"
                       placeholder="{{ __('Thành viên (Tên, email)...') }}" 
                       class="block w-full pl-9 pr-4 py-2 border border-gray-200 dark:border-slate-700 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee transition-all bg-gray-50/50 dark:bg-slate-950 dark:text-white">
            </div>

            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                    <i data-lucide="shopping-bag" class="w-4 h-4"></i>
                </div>
                <input type="text" 
                       name="product_name" 
                       value="{{ request('product_name') }}"
                       placeholder="{{ __('Tên sản phẩm...') }}" 
                       class="block w-full pl-9 pr-4 py-2 border border-gray-200 dark:border-slate-700 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee transition-all bg-gray-50/50 dark:bg-slate-950 dark:text-white">
            </div>

            <div class="flex gap-2">
                <button type="submit" class="flex-grow px-5 py-2 text-xs font-semibold text-white bg-shopee hover:bg-shopee-dark rounded-xl transition-all shadow-md shrink-0">
                    {{ __('Lọc') }}
                </button>
                @if($hasFilter)
                    <a href="{{ route('admin.logs.cashback_clicks') }}" class="flex-grow inline-flex items-center justify-center px-4 py-2 text-xs font-semibold text-gray-500 hover:text-gray-700 dark:text-slate-400 bg-gray-100 dark:bg-slate-800 hover:bg-gray-200 dark:hover:bg-slate-700 rounded-xl transition-all shadow-sm shrink-0 gap-1">
                        <i data-lucide="x" class="w-3.5 h-3.5"></i>
                        <span>{{ __('Bỏ lọc') }}</span>
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Bảng hiển thị danh sách nhật ký click -->
    <div class="bg-white dark:bg-slate-900 rounded-3xl shadow-sm border border-gray-200 dark:border-slate-800 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse whitespace-nowrap">
                <thead>
                    <tr class="border-b border-gray-200 dark:border-slate-800 text-xs font-bold text-gray-400 bg-gray-50/50 dark:bg-slate-950/40">
                        <th class="p-4">ID</th>
                        <th class="p-4">{{ __('Thành viên') }}</th>
                        <th class="p-4 text-center">{{ __('Nền tảng') }}</th>
                        <th class="p-4">{{ __('Mã Trans ID') }}</th>
                        <th class="p-4">{{ __('Sản phẩm') }}</th>
                        <th class="p-4 text-right">{{ __('Giá gốc / Hoàn tiền') }}</th>
                        <th class="p-4 text-center">{{ __('Affiliate Link') }}</th>
                        <th class="p-4 text-center">{{ __('Trạng thái đơn') }}</th>
                        <th class="p-4 text-center">{{ __('Thời gian click') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-slate-800 text-xs text-gray-750 dark:text-slate-300">
                    @forelse($logs as $log)
                        <tr class="hover:bg-gray-50/50 dark:hover:bg-slate-950/20 transition-colors">
                            <td class="p-4 font-bold text-gray-400">{{ $log->id }}</td>
                            <td class="p-4">
                                @if($log->user)
                                    {{-- Hiển thị thông tin thành viên kèm nút đi tới trang chỉnh sửa thông tin (admin.users.edit) để quản trị viên dễ dàng tra cứu, kiểm tra hoặc xử lý nhanh khi có vấn đề đối soát --}}
                                    <div class="flex items-center gap-2">
                                        <div>
                                            <p class="font-bold text-gray-950 dark:text-white">{{ $log->user->name }}</p>
                                            <p class="text-[10px] text-gray-400 dark:text-slate-500 font-mono">{{ $log->user->email }}</p>
                                        </div>
                                        <a href="{{ route('admin.users.edit', $log->user->id) }}" class="p-1 text-blue-600 hover:bg-blue-50 dark:hover:bg-slate-800 rounded-lg inline-flex items-center justify-center transition-colors shrink-0" title="{{ __('Chỉnh sửa thành viên') }}">
                                            <i data-lucide="edit" class="w-3.5 h-3.5"></i>
                                        </a>
                                    </div>
                                @else
                                    <p class="text-gray-400 italic">{{ __('Khách vãng lai') }}</p>
                                @endif
                            </td>
                            <td class="p-4 text-center">
                                @if(($log->platform ?? 'shopee') === 'shopee')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-extrabold bg-[#ff5722] text-white border border-[#ff5722] shadow-sm select-none">
                                        <i data-lucide="shopping-bag" class="w-3 h-3 text-white"></i>
                                        {{ \App\Models\Setting::getVal('shopee_platform_name', 'Shopee') }}
                                    </span>
                                @elseif(($log->platform ?? 'shopee') === 'tiktok')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-extrabold bg-black text-white border border-gray-950 dark:border-slate-800 shadow-sm select-none">
                                        <i data-lucide="shopping-cart" class="w-3 h-3 text-white"></i>
                                        {{ \App\Models\Setting::getVal('tiktok_platform_name', 'TikTok Shop') }}
                                    </span>
                                @elseif(($log->platform ?? 'shopee') === 'lazada')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-extrabold bg-[#0f146d] text-white border border-[#0f146d] shadow-sm select-none">
                                        <i data-lucide="shopping-bag" class="w-3 h-3 text-white"></i>
                                        {{ \App\Models\Setting::getVal('lazada_platform_name', 'Lazada') }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-extrabold bg-gray-700 text-white border border-gray-800 shadow-sm select-none">
                                        <i data-lucide="shopping-bag" class="w-3 h-3 text-white"></i>
                                        {{ ucfirst($log->platform) }}
                                    </span>
                                @endif
                            </td>
                            <td class="p-4 font-mono text-gray-650 dark:text-slate-450 font-semibold select-all">
                                {{ $log->trans_id }}
                            </td>
                            <td class="p-4 max-w-[280px]">
                                <div class="flex items-center gap-3">
                                    @if($log->product_image)
                                        <img src="{{ $log->product_image }}" alt="{{ $log->product_name }}" class="w-10 h-10 object-cover rounded-lg border border-gray-100 dark:border-slate-800 shrink-0">
                                    @else
                                        <div class="w-10 h-10 bg-gray-100 dark:bg-slate-800 rounded-lg flex items-center justify-center text-gray-300 dark:text-slate-600 shrink-0">
                                            <i data-lucide="image" class="w-5 h-5"></i>
                                        </div>
                                    @endif
                                    <div class="truncate">
                                        <p class="font-bold text-gray-850 dark:text-slate-200 truncate" title="{{ $log->product_name }}">{{ $log->product_name }}</p>
                                        <p class="text-[10px] text-slate-400 dark:text-slate-500">{{ __('Tỷ lệ hoàn:') }} <span class="font-bold text-slate-500 dark:text-slate-400">{{ $log->cashback_rate }}%</span></p>
                                    </div>
                                </div>
                            </td>
                            <td class="p-4 text-right font-medium">
                                <p class="text-gray-400 dark:text-slate-500 line-through text-[10px]">{{ \App\Helpers\CurrencyHelper::format($log->original_price) }}</p>
                                <p class="text-shopee font-bold text-xs">+{{ \App\Helpers\CurrencyHelper::format($log->cashback_amount) }}</p>
                            </td>
                            <td class="p-4 text-center">
                                @if($log->affiliate_url)
                                    <a href="{{ $log->affiliate_url }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1 px-2 py-1 bg-gray-150 hover:bg-shopee/10 hover:text-shopee text-gray-650 rounded-lg font-semibold transition-all">
                                        <i data-lucide="external-link" class="w-3 h-3"></i>
                                        <span>Link</span>
                                    </a>
                                @else
                                    <span class="text-gray-300 italic">{{ __('Không có') }}</span>
                                @endif
                            </td>
                            <td class="p-4 text-center">
                                @if($log->cashbackHistory)
                                    @if($log->cashbackHistory->status === 'approved')
                                        <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-[10px] font-bold bg-green-50 text-green-600 border border-green-100">
                                            <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span>
                                            {{ __('Đã duyệt') }}
                                        </span>
                                    @elseif($log->cashbackHistory->status === 'rejected')
                                        <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-[10px] font-bold bg-red-50 text-red-600 border border-red-100">
                                            <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>
                                            {{ __('Đã hủy') }}
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-[10px] font-bold bg-yellow-50 text-yellow-600 border border-yellow-100">
                                            <span class="w-1.5 h-1.5 rounded-full bg-yellow-500 animate-pulse"></span>
                                            {{ __('Chờ duyệt') }}
                                        </span>
                                    @endif
                                @else
                                    <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-[10px] font-bold bg-gray-50 text-gray-500 border border-gray-200">
                                        {{ __('Chưa chuyển đổi') }}
                                    </span>
                                @endif
                            </td>
                            <td class="p-4 text-center text-gray-400 text-[10px]" title="{{ $log->created_at }}">
                                {{ $log->created_at->format('d/m/Y H:i:s') }}
                                <span class="block text-[9px] text-gray-300 font-medium">{{ $log->created_at->diffForHumans() }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="p-8 text-center text-gray-400">{{ __('Không tìm thấy lịch sử click hoàn tiền nào.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Phân trang dữ liệu -->
        @if($logs->hasPages())
            <div class="p-4 border-t border-gray-100 dark:border-slate-800 bg-gray-50/50 dark:bg-slate-950/20">
                {{ $logs->links() }}
            </div>
        @endif
    </div>

    <!-- MODAL: THỐNG KÊ LƯỢT CLICK HOÀN TIỀN (Chart.js) -->
    <template x-teleport="body">
        <div x-show="clicksStatsModalOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-slate-950/40 backdrop-blur-sm" x-transition>
            <div class="bg-white dark:bg-slate-900 rounded-3xl max-w-4xl w-full shadow-2xl border border-gray-100 dark:border-slate-800 overflow-hidden" 
                 @click.away="clicksStatsModalOpen = false"
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
                            <h3 class="font-bold text-gray-900 dark:text-slate-200 text-sm">{{ __('Thống kê click hoàn tiền') }}</h3>
                            <p class="text-[10px] text-gray-500 dark:text-slate-455 font-medium">{{ __('Biểu đồ lượt click lấy link & Top sản phẩm được quan tâm nhiều nhất') }}</p>
                        </div>
                    </div>
                    <button @click="clicksStatsModalOpen = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-slate-350 transition-colors">
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
                                <button @click="loadClicksStats('week')" 
                                        class="px-3 py-1.5 text-[11px] font-bold rounded-lg transition-all"
                                        :class="cStatsPeriod === 'week' ? 'bg-white dark:bg-slate-700 text-indigo-600 dark:text-indigo-400 shadow-sm' : 'text-gray-500 dark:text-slate-400 hover:text-gray-700'">
                                    {{ __('Tuần') }}
                                </button>
                                <button @click="loadClicksStats('month')" 
                                        class="px-3 py-1.5 text-[11px] font-bold rounded-lg transition-all"
                                        :class="cStatsPeriod === 'month' ? 'bg-white dark:bg-slate-700 text-indigo-600 dark:text-indigo-400 shadow-sm' : 'text-gray-500 dark:text-slate-400 hover:text-gray-700'">
                                    {{ __('Tháng') }}
                                </button>
                                <button @click="loadClicksStats('year')" 
                                        class="px-3 py-1.5 text-[11px] font-bold rounded-lg transition-all"
                                        :class="cStatsPeriod === 'year' ? 'bg-white dark:bg-slate-700 text-indigo-600 dark:text-indigo-400 shadow-sm' : 'text-gray-500 dark:text-slate-400 hover:text-gray-700'">
                                    {{ __('Năm') }}
                                </button>
                            </div>
                        </div>

                        <!-- Cards tổng hợp nhanh -->
                        <div class="flex items-center gap-4">
                            <div class="px-4 py-2 bg-indigo-50/50 dark:bg-indigo-950/20 border border-indigo-100/50 dark:border-indigo-900/30 rounded-2xl flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg bg-indigo-100 dark:bg-indigo-900/40 flex items-center justify-center">
                                    <i data-lucide="mouse-pointer-click" class="w-4 h-4 text-indigo-600 dark:text-indigo-400"></i>
                                </div>
                                <div>
                                    <p class="text-[8px] font-bold text-gray-400 dark:text-slate-500 uppercase tracking-widest">{{ __('Tổng lượt click') }}</p>
                                    <p class="text-sm font-black text-gray-900 dark:text-white" x-text="cStatsTotals.clicks">0</p>
                                </div>
                            </div>

                            <div class="px-4 py-2 bg-emerald-50/50 dark:bg-emerald-950/20 border border-emerald-100/50 dark:border-emerald-900/30 rounded-2xl flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg bg-emerald-100 dark:bg-emerald-900/40 flex items-center justify-center">
                                    <i data-lucide="users" class="w-4 h-4 text-emerald-600 dark:text-emerald-400"></i>
                                </div>
                                <div>
                                    <p class="text-[8px] font-bold text-gray-400 dark:text-slate-500 uppercase tracking-widest">{{ __('Số thành viên') }}</p>
                                    <p class="text-sm font-black text-gray-900 dark:text-white" x-text="cStatsTotals.users">0</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Nội dung chính: Cột Trái Biểu đồ | Cột Phải Top Sản phẩm -->
                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                        <!-- Biểu đồ (Chiếm 7 cột) -->
                        <div class="lg:col-span-7 space-y-3">
                            <h4 class="font-bold text-gray-900 dark:text-white uppercase tracking-wider text-[10px] flex items-center gap-1.5">
                                <i data-lucide="activity" class="w-3.5 h-3.5 text-indigo-500"></i>
                                {{ __('Biểu đồ tăng trưởng click') }}
                            </h4>
                            <div class="relative bg-gray-50/50 dark:bg-slate-950/30 rounded-2xl p-4 border border-gray-100 dark:border-slate-800/50" style="height: 300px;">
                                <div x-show="cStatsLoading" class="absolute inset-0 flex items-center justify-center bg-white/60 dark:bg-slate-900/60 rounded-2xl z-10">
                                    <svg class="animate-spin h-8 w-8 text-indigo-500" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                </div>
                                <canvas id="clicksStatsChart" height="270"></canvas>
                            </div>
                        </div>

                        <!-- Top sản phẩm nhiều click nhất (Chiếm 5 cột) -->
                        <div class="lg:col-span-5 space-y-3">
                            <h4 class="font-bold text-gray-900 dark:text-white uppercase tracking-wider text-[10px] flex items-center gap-1.5">
                                <i data-lucide="award" class="w-3.5 h-3.5 text-amber-500"></i>
                                {{ __('Top sản phẩm được lấy link nhiều nhất') }}
                            </h4>
                            <div class="bg-gray-50/50 dark:bg-slate-950/30 rounded-2xl p-4 border border-gray-100 dark:border-slate-800/50 overflow-y-auto" style="height: 300px;">
                                <div x-show="cStatsLoading" class="flex items-center justify-center h-56 text-gray-400">
                                    <svg class="animate-spin h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <span>{{ __('Đang tải...') }}</span>
                                </div>
                                <template x-if="!cStatsLoading && cStatsTopProducts.length === 0">
                                    <div class="flex flex-col items-center justify-center h-56 text-gray-400 dark:text-slate-500 text-xs">
                                        <i data-lucide="info" class="w-8 h-8 mb-2 opacity-50"></i>
                                        <span>{{ __('Không có dữ liệu lượt click trong khoảng thời gian này.') }}</span>
                                    </div>
                                </template>
                                <template x-if="!cStatsLoading && cStatsTopProducts.length > 0">
                                    <div class="space-y-3">
                                        <template x-for="(prod, idx) in cStatsTopProducts" :key="idx">
                                            <div class="p-3 bg-white dark:bg-slate-900 rounded-xl border border-gray-150 dark:border-slate-800/80 flex items-center justify-between shadow-sm">
                                                <div class="flex items-center gap-2.5 min-w-0 flex-1">
                                                    <span class="w-5 h-5 rounded-md flex items-center justify-center text-[10px] font-black shrink-0 font-mono"
                                                          :class="idx === 0 ? 'bg-amber-100 text-amber-700 dark:bg-amber-950/40 dark:text-amber-400' : (idx === 1 ? 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-400' : (idx === 2 ? 'bg-orange-100 text-orange-700 dark:bg-orange-950/40 dark:text-orange-400' : 'bg-gray-50 text-gray-500 dark:bg-slate-950 dark:text-slate-500'))"
                                                          x-text="idx + 1">
                                                    </span>
                                                    <span class="font-bold text-gray-800 dark:text-slate-200 truncate text-[11px]" x-text="prod.product_name || '{{ __('Sản phẩm không tên') }}'" :title="prod.product_name"></span>
                                                </div>
                                                <span class="inline-flex px-2 py-0.5 rounded-full text-[9px] font-bold bg-indigo-50 dark:bg-indigo-950/30 text-indigo-600 dark:text-indigo-400 border border-indigo-100 dark:border-indigo-900/50 shrink-0 ml-2" x-text="prod.total_clicks + ' clicks'"></span>
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
                    <button type="button" @click="clicksStatsModalOpen = false" class="px-5 py-2 text-xs font-semibold text-gray-700 dark:text-slate-350 bg-gray-100 dark:bg-slate-800 hover:bg-gray-200 dark:hover:bg-slate-700 rounded-xl transition-all border border-transparent dark:border-slate-700/50">
                        {{ __('Đóng') }}
                    </button>
                </div>
            </div>
        </div>
    </template>

    <!-- Modal xác nhận dọn dẹp nhật ký click hoàn tiền (AlpineJS Teleport) -->
    <template x-teleport="body">
        <div x-show="showClearModal" 
             x-cloak
             class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm"
             x-transition>
            <div class="bg-white dark:bg-slate-900 rounded-3xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:max-w-lg w-full p-6 border border-gray-100 dark:border-slate-800 relative z-10"
                 @click.away="showClearModal = false">
                <div class="flex items-center gap-4 text-red-600 mb-4">
                    <div class="w-12 h-12 rounded-2xl bg-red-50 dark:bg-red-950/40 flex items-center justify-center shrink-0">
                        <i data-lucide="triangle-alert" class="w-6 h-6 text-red-600 dark:text-red-400"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-slate-200">{{ __('Xác Nhận Dọn Dẹp Click Hoàn Tiền') }}</h3>
                        <p class="text-xs text-gray-500 dark:text-slate-400">{{ __('Hành động này không thể hoàn tác!') }}</p>
                    </div>
                </div>
                <p class="text-sm text-gray-600 dark:text-slate-300 mb-6 leading-relaxed">
                    {{ __('Bạn có chắc chắn muốn xóa toàn bộ nhật ký click hoàn tiền hiện có trong hệ thống? Việc này sẽ xóa toàn bộ lịch sử click chuyển đổi mua sắm.') }}
                </p>
                <div class="flex items-center justify-end gap-3">
                    <button type="button" @click="showClearModal = false" class="px-4 py-2.5 text-xs font-semibold text-gray-700 dark:text-slate-350 bg-gray-100 dark:bg-slate-800 hover:bg-gray-200 dark:hover:bg-slate-700 rounded-xl transition-all">
                        {{ __('Hủy bỏ') }}
                    </button>
                    <form action="{{ route('admin.logs.cashback_clicks.clear') }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="px-4 py-2.5 text-xs font-semibold text-white bg-red-600 hover:bg-red-700 rounded-xl transition-all shadow-md">
                            {{ __('Xác nhận dọn dẹp') }}
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </template>
</div>
@endsection

@section('scripts')
<script>
    function cashbackClicksAdminHandler() {
        return {
            showFilter: @json($hasFilter),
            showClearModal: false,
            clicksStatsModalOpen: false,
            cStatsLoading: false,
            cStatsPeriod: 'week',
            cStatsTotals: { clicks: 0, users: 0 },
            cStatsTopProducts: [],
            
            // Mở modal thống kê click sản phẩm và tải dữ liệu tuần đầu tiên
            openClicksStatsModal() {
                this.clicksStatsModalOpen = true;
                setTimeout(() => {
                    if (window.lucide) window.lucide.createIcons();
                    this.loadClicksStats('week');
                }, 100);
            },
            
            // Gọi AJAX API lấy dữ liệu thống kê từ server
            async loadClicksStats(period) {
                this.cStatsPeriod = period;
                this.cStatsLoading = true;
                
                try {
                    const response = await fetch(`{{ route('admin.logs.cashback_clicks.stats') }}?period=${period}`, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        }
                    });
                    
                    if (!response.ok) throw new Error('Network error');
                    const result = await response.json();
                    
                    if (result.success) {
                        this.cStatsTotals = result.totals;
                        this.cStatsTopProducts = result.top_products;
                        this.renderClicksChart(result, period);
                    }
                } catch (error) {
                    console.error('Clicks stats fetch error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: "{{ __('Lỗi tải dữ liệu') }}",
                        text: "{{ __('Không thể tải dữ liệu thống kê. Vui lòng thử lại.') }}"
                    });
                } finally {
                    this.cStatsLoading = false;
                }
            },
            
            // Render biểu đồ Chart.js cho số lượng click sản phẩm
            renderClicksChart(data, period) {
                const canvas = document.getElementById('clicksStatsChart');
                if (!canvas) return;
                
                // Hủy biểu đồ cũ nếu đã tồn tại để tránh xung đột
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
                
                canvas._chartInstance = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: data.labels,
                        datasets: [{
                            label: "{{ __('Lượt click') }}",
                            data: data.clicks,
                            backgroundColor: 'rgba(99, 102, 241, 0.7)',
                            hoverBackgroundColor: 'rgba(99, 102, 241, 0.9)',
                            borderColor: 'rgba(99, 102, 241, 1)',
                            borderWidth: 1,
                            borderRadius: 6,
                            borderSkipped: false,
                            maxBarThickness: 32
                        }]
                    },
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
                                callbacks: {
                                    label: (item) => `${item.dataset.label}: ${item.parsed.y} lượt`
                                }
                            }
                        },
                        scales: {
                            x: {
                                grid: { display: false },
                                ticks: { font: { size: 10, weight: '600' }, color: '#94a3b8', maxRotation: period === 'year' ? 45 : 0 }
                            },
                            y: {
                                beginAtZero: true,
                                grid: { color: 'rgba(148, 163, 184, 0.1)' },
                                ticks: {
                                    font: { size: 10, weight: '600' },
                                    color: '#94a3b8',
                                    stepSize: 1,
                                    precision: 0
                                }
                            }
                        }
                    }
                });
            }
        };
    }
</script>
@endsection
