@extends('layouts.admin')

@section('title', __('Quản Lý Sản Phẩm') . ' - ' . $siteName)

@php
    $hasFilter = request('search') || request('shopee_id') || request('url') || (request('limit') && request('limit') != 15);
    // Danh sách ID các sản phẩm đang hiển thị ở trang hiện tại, phục vụ tính năng tích chọn tất cả.
    // Ép về kiểu chuỗi vì Alpine luôn đẩy giá trị thuộc tính value (dạng chuỗi) vào mảng x-model của checkbox.
    $selectableProductIds = array_map('strval', $products->pluck('id')->toArray());
@endphp

@section('content')
<div class="space-y-6" x-data="productAdminHandler()">
    <!-- Tiêu đề & Hành động -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ __('Quản Lý Sản Phẩm') }}</h1>
            <p class="text-sm text-gray-500">{{ __('Danh sách các sản phẩm Shopee thành viên đã tìm kiếm và click lấy link hoàn tiền được lưu trữ trong hệ thống') }}</p>
        </div>
        <div class="flex items-center gap-2">
            <!-- Nút mở modal dọn dẹp sản phẩm cache theo mốc thời gian lưu trữ -->
            <button @click="openCleanupModal()"
                    class="inline-flex items-center gap-1.5 px-4 py-2.5 text-xs font-semibold text-white bg-amber-500 hover:bg-amber-600 rounded-xl transition-all shadow-md">
                <i data-lucide="eraser" class="w-4 h-4"></i>
                <span>{{ __('Dọn dẹp') }}</span>
            </button>

            <!-- Nút thống kê click chuyên nghiệp -->
            <button @click="openProductStatsModal()"
                    class="inline-flex items-center gap-1.5 px-4 py-2.5 text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded-xl transition-all shadow-md">
                <i data-lucide="bar-chart-3" class="w-4 h-4"></i>
                <span>{{ __('Thống kê click') }}</span>
            </button>

            <!-- Nút bật/tắt bộ lọc -->
            <button @click="showFilter = !showFilter" 
                    class="inline-flex items-center gap-1.5 px-4 py-2.5 text-xs font-semibold rounded-xl border border-gray-200 dark:border-slate-800 transition-all shadow-sm"
                    :class="showFilter ? 'bg-shopee text-white border-shopee hover:bg-shopee-dark' : 'bg-white dark:bg-slate-900 hover:bg-gray-50 text-gray-700 dark:text-slate-300'">
                <i data-lucide="filter" class="w-4 h-4"></i>
                <span>{{ __('Bộ lọc') }}</span>
                @if($hasFilter)
                    <span class="w-1.5 h-1.5 rounded-full bg-red-500 animate-pulse"></span>
                @endif
            </button>
        </div>
    </div>

    <!-- Bộ lọc tìm kiếm sản phẩm -->
    <div x-show="showFilter" 
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 transform -translate-y-2"
         x-transition:enter-end="opacity-100 transform translate-y-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 transform translate-y-0"
         x-transition:leave-end="opacity-0 transform -translate-y-2"
         class="bg-white dark:bg-slate-900 p-4 rounded-3xl shadow-sm border border-gray-200 dark:border-slate-800"
         style="display: {{ $hasFilter ? 'block' : 'none' }}">
        <form action="{{ route('admin.products.index') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
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
                    <i data-lucide="search" class="w-4 h-4"></i>
                </div>
                <input type="text" 
                       name="search" 
                       value="{{ request('search') }}"
                       placeholder="{{ __('Tên sản phẩm...') }}" 
                       class="block w-full pl-9 pr-4 py-2 border border-gray-200 dark:border-slate-700 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee transition-all bg-gray-50/50 dark:bg-slate-950 dark:text-white">
            </div>

            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                    <i data-lucide="hash" class="w-4 h-4"></i>
                </div>
                <input type="text" 
                       name="shopee_id" 
                       value="{{ request('shopee_id') }}"
                       placeholder="{{ __('Shopee ID...') }}" 
                       class="block w-full pl-9 pr-4 py-2 border border-gray-200 dark:border-slate-700 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee transition-all bg-gray-50/50 dark:bg-slate-950 dark:text-white">
            </div>

            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                    <i data-lucide="link" class="w-4 h-4"></i>
                </div>
                <input type="text" 
                       name="url" 
                       value="{{ request('url') }}"
                       placeholder="{{ __('URL sản phẩm...') }}" 
                       class="block w-full pl-9 pr-4 py-2 border border-gray-200 dark:border-slate-700 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee transition-all bg-gray-50/50 dark:bg-slate-950 dark:text-white">
            </div>

            <div class="flex gap-2">
                <button type="submit" class="flex-grow px-5 py-2 text-xs font-semibold text-white bg-shopee hover:bg-shopee-dark rounded-xl transition-all shadow-md shrink-0">
                    {{ __('Lọc') }}
                </button>
                @if($hasFilter)
                    <a href="{{ route('admin.products.index') }}" class="flex-grow inline-flex items-center justify-center px-4 py-2 text-xs font-semibold text-gray-500 hover:text-gray-700 dark:text-slate-400 bg-gray-100 dark:bg-slate-800 hover:bg-gray-200 dark:hover:bg-slate-700 rounded-xl transition-all shadow-sm shrink-0 gap-1">
                        <i data-lucide="x" class="w-3.5 h-3.5"></i>
                        <span>{{ __('Bỏ lọc') }}</span>
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Bảng hiển thị danh sách sản phẩm -->
    <div class="bg-white dark:bg-slate-900 rounded-3xl shadow-sm border border-gray-200 dark:border-slate-800 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse whitespace-nowrap">
                <thead>
                    <tr class="border-b border-gray-200 dark:border-slate-800 text-xs font-bold text-gray-400 bg-gray-50/50 dark:bg-slate-950/40">
                        <!-- Checkbox tích chọn toàn bộ sản phẩm đang hiển thị trên trang hiện tại -->
                        <th class="p-4 w-10 text-center">
                            <input type="checkbox" x-model="allSelected" @change="toggleAll()" class="rounded border-gray-300 dark:border-slate-800 text-shopee focus:ring-shopee cursor-pointer" title="{{ __('Chọn tất cả sản phẩm trong trang') }}">
                        </th>
                        <th class="p-4">ID</th>
                        <th class="p-4">{{ __('Sản phẩm') }}</th>
                        <th class="p-4">{{ __('Shopee ID') }}</th>
                        <th class="p-4 text-right">{{ __('Giá gốc') }}</th>
                        <th class="p-4 text-right">{{ __('Hoa hồng Shopee') }}</th>
                        <th class="p-4 text-right">{{ __('Hoàn tiền thành viên') }}</th>
                        <th class="p-4 text-center">{{ __('Lượt tìm kiếm') }}</th>
                        <th class="p-4 text-center">{{ __('Thời gian lưu') }}</th>
                        <th class="p-4 text-center">{{ __('Thao tác') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-slate-800 text-xs text-gray-750 dark:text-slate-300">
                    @forelse($products as $product)
                        <tr class="hover:bg-gray-50/50 dark:hover:bg-slate-950/20 transition-colors"
                            :class="selectedProducts.includes('{{ $product->id }}') ? 'bg-orange-50/40 dark:bg-slate-800/30' : ''">
                            <!-- Checkbox tích chọn sản phẩm để xóa nhanh hàng loạt -->
                            <td class="p-4 text-center">
                                <input type="checkbox" value="{{ $product->id }}" x-model="selectedProducts"
                                       @change="allSelected = (selectedProducts.length === selectableProductIds.length)"
                                       class="rounded border-gray-300 dark:border-slate-800 text-shopee focus:ring-shopee cursor-pointer">
                            </td>
                            <td class="p-4 font-bold text-gray-400">{{ $product->id }}</td>
                            <td class="p-4 max-w-[280px]">
                                <div class="flex items-center gap-3">
                                    @if($product->image)
                                        <img src="{{ $product->image }}" alt="{{ $product->name }}" class="w-10 h-10 object-cover rounded-lg border border-gray-100 dark:border-slate-800 shrink-0">
                                    @else
                                        <div class="w-10 h-10 bg-gray-100 dark:bg-slate-800 rounded-lg flex items-center justify-center text-gray-300 dark:text-slate-600 shrink-0">
                                            <i data-lucide="image" class="w-5 h-5"></i>
                                        </div>
                                    @endif
                                    <div class="truncate">
                                        <p class="font-bold text-gray-950 dark:text-white truncate" title="{{ $product->name }}">{{ $product->name }}</p>
                                        @if($product->affiliate_url)
                                            <a href="{{ $product->affiliate_url }}" target="_blank" rel="noopener noreferrer" class="text-[10px] text-shopee hover:underline inline-flex items-center gap-0.5 mt-0.5">
                                                <i data-lucide="external-link" class="w-2.5 h-2.5"></i>
                                                {{ __('Link Affiliate') }}
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="p-4 font-mono font-semibold select-all text-gray-650 dark:text-slate-400">
                                {{ $product->shopee_id }}
                            </td>
                            <td class="p-4 text-right font-semibold text-gray-700 dark:text-slate-350">
                                {{ \App\Helpers\CurrencyHelper::format($product->price) }}
                            </td>
                            <td class="p-4 text-right font-medium">
                                <p class="text-xs text-gray-900 dark:text-white font-bold">{{ \App\Helpers\CurrencyHelper::format($product->commission_amount) }}</p>
                                <p class="text-[10px] text-gray-400 dark:text-slate-500">{{ __('Gốc Shopee:') }} {{ \App\Helpers\CurrencyHelper::format($product->shopee_commission) }}</p>
                            </td>
                            <td class="p-4 text-right font-medium">
                                <p class="text-xs text-green-600 font-bold">+{{ \App\Helpers\CurrencyHelper::format($product->cashback_amount) }}</p>
                                <p class="text-[10px] text-gray-450 dark:text-slate-400">{{ __('Tỷ lệ hoàn:') }} <span class="font-bold text-green-600">{{ $product->cashback_rate }}%</span></p>
                            </td>
                            <td class="p-4 text-center">
                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-blue-50 dark:bg-blue-950/30 text-blue-600 dark:text-blue-400 border border-blue-100 dark:border-blue-900/30">
                                    <i data-lucide="trending-up" class="w-3 h-3 text-blue-500"></i>
                                    {{ $product->search_count }}
                                </span>
                            </td>
                            <td class="p-4 text-center text-gray-450 text-[10px]" title="{{ $product->created_at }}">
                                {{ $product->created_at->format('d/m/Y H:i:s') }}
                                <span class="block text-[9px] text-gray-300 dark:text-slate-650 font-medium">{{ $product->created_at->diffForHumans() }}</span>
                            </td>
                            <td class="p-4 text-center">
                                <!-- Nút xóa một sản phẩm: mở modal xác nhận thay cho hộp thoại confirm mặc định của trình duyệt -->
                                <button type="button"
                                        @click="openDeleteModal({ id: '{{ $product->id }}', name: @js($product->name), image: @js($product->image), shopeeId: @js($product->shopee_id) })"
                                        class="p-2 bg-red-50 dark:bg-red-950/30 hover:bg-red-100 dark:hover:bg-red-900/40 text-red-600 dark:text-red-400 rounded-xl transition-all" title="{{ __('Xóa sản phẩm') }}">
                                    <i data-lucide="trash-2" class="w-4 h-4 pointer-events-none"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="p-8 text-center text-gray-400 dark:text-slate-500">{{ __('Không tìm thấy sản phẩm nào.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Phân trang dữ liệu -->
        @if($products->hasPages())
            <div class="p-4 border-t border-gray-100 dark:border-slate-800 bg-gray-50/50 dark:bg-slate-950/20">
                {{ $products->links() }}
            </div>
        @endif
    </div>

    <!-- THANH HÀNH ĐỘNG NỔI: HIỆN LÊN KHI CÓ SẢN PHẨM ĐƯỢC TÍCH CHỌN -->
    <div x-show="selectedProducts.length > 0" x-cloak
         class="fixed bottom-6 left-1/2 -translate-x-1/2 z-40 bg-white/85 dark:bg-slate-900/95 backdrop-blur-md px-6 py-4 rounded-3xl shadow-2xl border border-gray-150 dark:border-slate-800/80 flex items-center gap-4 transition-all duration-300 transform"
         x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 translate-y-4">
        <div class="flex items-center gap-2.5">
            <span class="relative flex h-3 w-3">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-shopee opacity-75"></span>
                <span class="relative inline-flex rounded-full h-3 w-3 bg-shopee"></span>
            </span>
            <span class="text-xs font-bold text-gray-800 dark:text-slate-200">{{ __('Đã chọn:') }} <strong class="text-shopee dark:text-shopee-light" x-text="selectedProducts.length"></strong> {{ __('sản phẩm') }}</span>
        </div>
        <div class="h-6 w-[1px] bg-gray-200 dark:bg-slate-800"></div>
        <div class="flex items-center gap-2">
            <!-- Nút bỏ chọn toàn bộ -->
            <button type="button" @click="clearSelection()" class="px-4 py-2.5 text-[10px] font-bold text-gray-700 dark:text-slate-350 bg-gray-100 dark:bg-slate-800 hover:bg-gray-200 dark:hover:bg-slate-700 rounded-xl transition-all flex items-center gap-1.5">
                <i data-lucide="x" class="w-3.5 h-3.5"></i>
                {{ __('Bỏ chọn') }}
            </button>

            <!-- Nút xóa nhanh hàng loạt các sản phẩm đã chọn -->
            <button type="button" @click="openBulkDeleteModal()" class="px-4 py-2.5 text-[10px] font-bold text-white bg-red-600 hover:bg-red-700 rounded-xl transition-all shadow-md flex items-center gap-1.5">
                <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                {{ __('Xóa hàng loạt') }}
            </button>
        </div>
    </div>

    <!-- MODAL: DỌN DẸP SẢN PHẨM THEO MỐC THỜI GIAN LƯU TRỮ -->
    <template x-teleport="body">
        <div x-show="cleanupModalOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-slate-950/40 backdrop-blur-sm" x-transition>
            <div class="bg-white dark:bg-slate-900 rounded-3xl max-w-lg w-full shadow-2xl border border-gray-100 dark:border-slate-800 overflow-hidden my-8"
                 @click.away="cleanupModalOpen = false"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95">
                <!-- Header -->
                <div class="px-6 py-4 bg-amber-50 dark:bg-amber-950/20 border-b border-amber-100 dark:border-amber-900/40 flex justify-between items-center">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-amber-100 dark:bg-amber-900/40 rounded-xl">
                            <i data-lucide="eraser" class="w-5 h-5 text-amber-600 dark:text-amber-400"></i>
                        </div>
                        <div>
                            <h3 class="font-bold text-amber-950 dark:text-amber-200 text-sm">{{ __('Dọn dẹp sản phẩm theo thời gian') }}</h3>
                            <p class="text-[10px] text-gray-500 dark:text-slate-450 font-medium">{{ __('Xóa bớt dữ liệu cache sản phẩm cũ để giải phóng dung lượng cơ sở dữ liệu') }}</p>
                        </div>
                    </div>
                    <button @click="cleanupModalOpen = false" class="text-amber-400 hover:text-amber-600 dark:hover:text-amber-300 transition-colors">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>

                <!-- Body -->
                <div class="p-6 space-y-5">
                    <!-- Giải thích nghiệp vụ cho quản trị viên -->
                    <div class="p-4 bg-amber-50/50 dark:bg-amber-950/20 border border-amber-100/80 dark:border-amber-900/30 rounded-2xl text-xs space-y-2">
                        <p class="font-bold text-amber-900 dark:text-amber-200 flex items-center gap-1.5">
                            <i data-lucide="info" class="w-3.5 h-3.5 shrink-0"></i>
                            {{ __('Dữ liệu sản phẩm là bộ nhớ đệm tạm thời') }}
                        </p>
                        <p class="leading-relaxed text-gray-700 dark:text-slate-350 text-[11px]">
                            {{ __('Bảng sản phẩm chỉ lưu tạm thông tin lấy về từ API các sàn thương mại. Dữ liệu để càng lâu thì giá bán và hoa hồng càng sai lệch so với thực tế. Việc dọn dẹp KHÔNG ảnh hưởng tới đơn hoàn tiền, lịch sử click hay số dư của thành viên.') }}
                        </p>
                    </div>

                    <!-- Lựa chọn mốc thời gian dọn dẹp kèm số lượng bản ghi tương ứng -->
                    <div class="space-y-2">
                        <label class="block text-[10px] font-bold text-gray-700 dark:text-slate-350 uppercase tracking-wider">
                            {{ __('Chọn mốc thời gian cần dọn dẹp') }} <span class="text-red-500">*</span>
                        </label>

                        <div class="space-y-2 max-h-[280px] overflow-y-auto pr-1">
                            @foreach($cleanupPeriods as $periodKey => $periodConfig)
                                <label class="flex items-center gap-3 p-3 border rounded-2xl cursor-pointer transition-all group"
                                       :class="cleanupPeriod === '{{ $periodKey }}'
                                            ? '{{ $periodKey === 'all' ? 'border-red-400 bg-red-50/60 dark:border-red-700 dark:bg-red-950/25' : 'border-amber-400 bg-amber-50/60 dark:border-amber-700 dark:bg-amber-950/25' }}'
                                            : 'border-gray-200 dark:border-slate-800 hover:bg-gray-50 dark:hover:bg-slate-950/40'">
                                    <input type="radio" value="{{ $periodKey }}" x-model="cleanupPeriod"
                                           class="w-4 h-4 border-gray-300 dark:border-slate-700 dark:bg-slate-950 cursor-pointer {{ $periodKey === 'all' ? 'text-red-600 focus:ring-red-500/20' : 'text-amber-500 focus:ring-amber-500/20' }}">
                                    <div class="flex-1 min-w-0">
                                        <p class="text-xs font-bold {{ $periodKey === 'all' ? 'text-red-700 dark:text-red-400' : 'text-gray-800 dark:text-slate-200' }}">
                                            {{ __($periodConfig['label']) }}
                                        </p>
                                        <p class="text-[10px] text-gray-400 dark:text-slate-500 font-medium">
                                            @if($periodKey === 'all')
                                                {{ __('Xóa sạch toàn bộ bảng sản phẩm, không xét thời gian lưu') }}
                                            @else
                                                {{ __('Tính theo cột Thời gian lưu của sản phẩm') }}
                                            @endif
                                        </p>
                                    </div>
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold shrink-0 border {{ $cleanupCounts[$periodKey] > 0 ? ($periodKey === 'all' ? 'bg-red-50 dark:bg-red-950/30 text-red-600 dark:text-red-400 border-red-100 dark:border-red-900/40' : 'bg-amber-50 dark:bg-amber-950/30 text-amber-700 dark:text-amber-400 border-amber-100 dark:border-amber-900/40') : 'bg-gray-50 dark:bg-slate-950 text-gray-400 dark:text-slate-600 border-gray-100 dark:border-slate-800' }}">
                                        {{ number_format($cleanupCounts[$periodKey]) }} {{ __('sản phẩm') }}
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <!-- Cảnh báo số lượng bản ghi sẽ bị xóa theo mốc đang chọn -->
                    <div class="p-3 bg-red-50/50 dark:bg-red-950/20 border border-red-100 dark:border-red-900/30 rounded-2xl text-[11px] text-red-800 dark:text-red-300 flex items-start gap-2">
                        <i data-lucide="alert-triangle" class="w-4 h-4 shrink-0 mt-0.5 text-red-500"></i>
                        <span>
                            {{ __('Sẽ có') }}
                            <strong class="font-extrabold text-red-600 dark:text-red-400" x-text="cleanupPeriodCount.toLocaleString('vi-VN')">0</strong>
                            {{ __('sản phẩm bị xóa vĩnh viễn khỏi cơ sở dữ liệu và KHÔNG THỂ hoàn tác.') }}
                        </span>
                    </div>

                    <!-- Checkbox xác nhận bắt buộc trước khi dọn dẹp -->
                    <div class="border-t border-gray-150 dark:border-slate-800/80 pt-4">
                        <label class="flex items-center gap-3 p-3.5 bg-red-50/50 dark:bg-red-950/20 border border-red-100 dark:border-red-900/40 rounded-2xl hover:bg-red-50 dark:hover:bg-red-950/30 transition-colors cursor-pointer group">
                            <input type="checkbox" x-model="confirmCleanupCheckbox" class="w-4 h-4 text-red-600 border-gray-300 dark:border-slate-700 rounded focus:ring-red-500/20 dark:bg-slate-950 cursor-pointer">
                            <span class="text-xs font-bold text-gray-700 dark:text-slate-300 group-hover:text-red-600 dark:group-hover:text-red-400 transition-colors">
                                {{ __('Tôi đã hiểu và xác nhận xóa vĩnh viễn dữ liệu sản phẩm thuộc mốc thời gian đã chọn') }}
                            </span>
                        </label>
                    </div>

                    <!-- Footer hành động -->
                    <form @submit.prevent="submitCleanup()" class="flex justify-end gap-2 pt-1">
                        <button type="button" @click="cleanupModalOpen = false" :disabled="isSubmitting" class="px-4 py-2.5 text-xs font-bold text-gray-700 dark:text-slate-350 bg-gray-100 dark:bg-slate-800 hover:bg-gray-200 dark:hover:bg-slate-700 rounded-xl transition-all disabled:opacity-50">
                            {{ __('Hủy bỏ') }}
                        </button>
                        <button type="submit" :disabled="!confirmCleanupCheckbox || cleanupPeriodCount === 0 || isSubmitting"
                                class="px-5 py-2.5 text-xs font-bold text-white bg-amber-500 hover:bg-amber-600 disabled:opacity-40 disabled:cursor-not-allowed rounded-xl transition-all shadow-md flex items-center justify-center min-w-[150px]">
                            <span x-show="!isSubmitting" class="flex items-center gap-1.5">
                                <i data-lucide="eraser" class="w-3.5 h-3.5"></i>
                                {{ __('Bắt đầu dọn dẹp') }}
                            </span>
                            <span x-show="isSubmitting" class="flex items-center gap-1.5">
                                <svg class="animate-spin -ml-1 mr-1.5 h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                {{ __('Đang dọn dẹp...') }}
                            </span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </template>

    <!-- MODAL: XÁC NHẬN XÓA NHANH HÀNG LOẠT SẢN PHẨM ĐÃ TÍCH CHỌN -->
    <template x-teleport="body">
        <div x-show="bulkDeleteModalOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-slate-950/40 backdrop-blur-sm" x-transition>
            <div class="bg-white dark:bg-slate-900 rounded-3xl max-w-md w-full shadow-2xl border border-gray-100 dark:border-slate-800 overflow-hidden"
                 @click.away="bulkDeleteModalOpen = false"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95">
                <!-- Header -->
                <div class="px-6 py-4 bg-red-50 dark:bg-red-950/20 border-b border-red-100 dark:border-red-900/40 flex justify-between items-center">
                    <h3 class="font-bold text-red-950 dark:text-red-200 text-sm flex items-center gap-2">
                        <i data-lucide="alert-triangle" class="w-4 h-4 text-red-600 dark:text-red-400 animate-pulse"></i>
                        {{ __('Xác nhận xóa hàng loạt sản phẩm') }}
                    </h3>
                    <button @click="bulkDeleteModalOpen = false" class="text-red-400 hover:text-red-600 dark:text-red-450 dark:hover:text-red-300"><i data-lucide="x" class="w-4 h-4"></i></button>
                </div>

                <!-- Body -->
                <div class="p-6 space-y-5">
                    <div class="p-4 bg-red-50/50 dark:bg-red-950/20 border border-red-100/80 dark:border-red-900/30 rounded-2xl text-xs space-y-2">
                        <p class="font-bold text-red-900 dark:text-red-200">{{ __('Cảnh báo xóa dữ liệu hàng loạt:') }}</p>
                        <p class="leading-relaxed text-gray-700 dark:text-slate-350">
                            {{ __('Hành động này sẽ xóa vĩnh viễn') }}
                            <strong class="text-red-600 dark:text-red-400 font-extrabold" x-text="selectedProducts.length"></strong>
                            {{ __('sản phẩm được chọn khỏi hệ thống và không thể hoàn tác.') }}
                        </p>
                        <p class="leading-relaxed text-[11px] text-gray-600 dark:text-slate-400 border-t border-red-100/50 dark:border-red-900/20 pt-2">
                            {{ __('Đây chỉ là dữ liệu cache thông tin sản phẩm, việc xóa không ảnh hưởng tới đơn hoàn tiền, lịch sử click lấy link hay số dư của thành viên.') }}
                        </p>
                    </div>

                    <!-- Checkbox xác nhận bắt buộc -->
                    <div class="border-t border-gray-150 dark:border-slate-800/80 pt-4">
                        <label class="flex items-center gap-3 p-3.5 bg-red-50/50 dark:bg-red-950/20 border border-red-100 dark:border-red-900/40 rounded-2xl hover:bg-red-50 dark:hover:bg-red-950/30 transition-colors cursor-pointer group">
                            <input type="checkbox" x-model="confirmBulkDeleteCheckbox" class="w-4 h-4 text-red-600 border-gray-300 dark:border-slate-700 rounded focus:ring-red-500/20 dark:bg-slate-950 cursor-pointer">
                            <span class="text-xs font-bold text-gray-700 dark:text-slate-300 group-hover:text-red-600 dark:group-hover:text-red-400 transition-colors">
                                {{ __('Tôi xác nhận muốn xóa vĩnh viễn các sản phẩm đã chọn') }}
                            </span>
                        </label>
                    </div>

                    <!-- Footer hành động -->
                    <form @submit.prevent="submitBulkDelete()" class="flex justify-end gap-2 pt-1">
                        <button type="button" @click="bulkDeleteModalOpen = false" :disabled="isSubmitting" class="px-4 py-2.5 text-xs font-bold text-gray-700 dark:text-slate-350 bg-gray-100 dark:bg-slate-800 hover:bg-gray-200 dark:hover:bg-slate-700 rounded-xl transition-all disabled:opacity-50">
                            {{ __('Hủy bỏ') }}
                        </button>
                        <button type="submit" :disabled="!confirmBulkDeleteCheckbox || selectedProducts.length === 0 || isSubmitting"
                                class="px-5 py-2.5 text-xs font-bold text-white bg-red-600 hover:bg-red-700 disabled:opacity-40 disabled:cursor-not-allowed rounded-xl transition-all shadow-md flex items-center justify-center min-w-[150px]">
                            <span x-show="!isSubmitting" class="flex items-center gap-1.5">
                                <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                {{ __('Đồng ý xóa') }}
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

    <!-- MODAL: XÁC NHẬN XÓA MỘT SẢN PHẨM Ở CỘT THAO TÁC -->
    <template x-teleport="body">
        <div x-show="deleteModalOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-slate-950/40 backdrop-blur-sm" x-transition>
            <div class="bg-white dark:bg-slate-900 rounded-3xl max-w-md w-full shadow-2xl border border-gray-100 dark:border-slate-800 overflow-hidden"
                 @click.away="deleteModalOpen = false"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95">
                <!-- Header -->
                <div class="px-6 py-4 bg-red-50 dark:bg-red-950/20 border-b border-red-100 dark:border-red-900/40 flex justify-between items-center">
                    <h3 class="font-bold text-red-950 dark:text-red-200 text-sm flex items-center gap-2">
                        <i data-lucide="alert-triangle" class="w-4 h-4 text-red-600 dark:text-red-400 animate-pulse"></i>
                        {{ __('Xác nhận xóa sản phẩm') }}
                    </h3>
                    <button @click="deleteModalOpen = false" class="text-red-400 hover:text-red-600 dark:text-red-450 dark:hover:text-red-300"><i data-lucide="x" class="w-4 h-4"></i></button>
                </div>

                <!-- Body -->
                <div class="p-6 space-y-5">
                    <!-- Thông tin sản phẩm đang chuẩn bị xóa để quản trị viên đối chiếu trước khi quyết định -->
                    <div class="flex items-center gap-3 p-3.5 bg-gray-50/70 dark:bg-slate-950/40 border border-gray-150 dark:border-slate-800 rounded-2xl">
                        <template x-if="deleteTarget.image">
                            <img :src="deleteTarget.image" :alt="deleteTarget.name" class="w-12 h-12 object-cover rounded-xl border border-gray-100 dark:border-slate-800 shrink-0">
                        </template>
                        <template x-if="!deleteTarget.image">
                            <div class="w-12 h-12 bg-gray-100 dark:bg-slate-800 rounded-xl flex items-center justify-center text-gray-300 dark:text-slate-600 shrink-0">
                                <i data-lucide="image" class="w-5 h-5"></i>
                            </div>
                        </template>
                        <div class="min-w-0">
                            <p class="text-xs font-bold text-gray-950 dark:text-white line-clamp-2" x-text="deleteTarget.name"></p>
                            <p class="text-[10px] text-gray-400 dark:text-slate-500 font-medium mt-0.5">
                                {{ __('Mã sản phẩm:') }} <span class="font-mono font-bold text-gray-600 dark:text-slate-350" x-text="deleteTarget.shopeeId"></span>
                            </p>
                        </div>
                    </div>

                    <!-- Cảnh báo hậu quả của hành động xóa -->
                    <div class="p-4 bg-red-50/50 dark:bg-red-950/20 border border-red-100/80 dark:border-red-900/30 rounded-2xl text-xs space-y-2">
                        <p class="font-bold text-red-900 dark:text-red-200">{{ __('Cảnh báo xóa dữ liệu:') }}</p>
                        <p class="leading-relaxed text-gray-700 dark:text-slate-350">
                            {{ __('Sản phẩm này sẽ bị xóa vĩnh viễn khỏi hệ thống và không thể hoàn tác.') }}
                        </p>
                        <p class="leading-relaxed text-[11px] text-gray-600 dark:text-slate-400 border-t border-red-100/50 dark:border-red-900/20 pt-2">
                            {{ __('Đây chỉ là dữ liệu cache thông tin sản phẩm, việc xóa không ảnh hưởng tới đơn hoàn tiền, lịch sử click lấy link hay số dư của thành viên.') }}
                        </p>
                    </div>

                    <!-- Footer hành động: gửi form DELETE chuẩn để giữ nguyên luồng thông báo flash của controller -->
                    <form :action="deleteFormAction" method="POST" @submit="isSubmitting = true" class="flex justify-end gap-2 pt-1">
                        @csrf
                        @method('DELETE')
                        <button type="button" @click="deleteModalOpen = false" :disabled="isSubmitting" class="px-4 py-2.5 text-xs font-bold text-gray-700 dark:text-slate-350 bg-gray-100 dark:bg-slate-800 hover:bg-gray-200 dark:hover:bg-slate-700 rounded-xl transition-all disabled:opacity-50">
                            {{ __('Hủy bỏ') }}
                        </button>
                        <button type="submit" :disabled="isSubmitting"
                                class="px-5 py-2.5 text-xs font-bold text-white bg-red-600 hover:bg-red-700 disabled:opacity-40 disabled:cursor-not-allowed rounded-xl transition-all shadow-md flex items-center justify-center min-w-[140px]">
                            <span x-show="!isSubmitting" class="flex items-center gap-1.5">
                                <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                {{ __('Đồng ý xóa') }}
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

    <!-- MODAL: THỐNG KÊ LƯỢT LẤY LINK SẢN PHẨM (Chart.js) -->
    <template x-teleport="body">
        <div x-show="productStatsModalOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-slate-950/40 backdrop-blur-sm" x-transition>
            <div class="bg-white dark:bg-slate-900 rounded-3xl max-w-4xl w-full shadow-2xl border border-gray-100 dark:border-slate-800 overflow-hidden" 
                 @click.away="productStatsModalOpen = false"
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
                            <h3 class="font-bold text-gray-900 dark:text-slate-200 text-sm">{{ __('Thống kê lấy link sản phẩm') }}</h3>
                            <p class="text-[10px] text-gray-500 dark:text-slate-450 font-medium">{{ __('Biểu đồ lượt click lấy link & Top sản phẩm được quan tâm nhiều nhất') }}</p>
                        </div>
                    </div>
                    <button @click="productStatsModalOpen = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-slate-350 transition-colors">
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
                                <button @click="loadProductStats('week')" 
                                        class="px-3 py-1.5 text-[11px] font-bold rounded-lg transition-all"
                                        :class="pStatsPeriod === 'week' ? 'bg-white dark:bg-slate-700 text-indigo-600 dark:text-indigo-400 shadow-sm' : 'text-gray-500 dark:text-slate-400 hover:text-gray-700'">
                                    {{ __('Tuần') }}
                                </button>
                                <button @click="loadProductStats('month')" 
                                        class="px-3 py-1.5 text-[11px] font-bold rounded-lg transition-all"
                                        :class="pStatsPeriod === 'month' ? 'bg-white dark:bg-slate-700 text-indigo-600 dark:text-indigo-400 shadow-sm' : 'text-gray-500 dark:text-slate-400 hover:text-gray-700'">
                                    {{ __('Tháng') }}
                                </button>
                                <button @click="loadProductStats('year')" 
                                        class="px-3 py-1.5 text-[11px] font-bold rounded-lg transition-all"
                                        :class="pStatsPeriod === 'year' ? 'bg-white dark:bg-slate-700 text-indigo-600 dark:text-indigo-400 shadow-sm' : 'text-gray-500 dark:text-slate-400 hover:text-gray-700'">
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
                                    <p class="text-sm font-black text-gray-900 dark:text-white" x-text="pStatsTotals.clicks">0</p>
                                </div>
                            </div>

                            <div class="px-4 py-2 bg-emerald-50/50 dark:bg-emerald-950/20 border border-emerald-100/50 dark:border-emerald-900/30 rounded-2xl flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg bg-emerald-100 dark:bg-emerald-900/40 flex items-center justify-center">
                                    <i data-lucide="users" class="w-4 h-4 text-emerald-600 dark:text-emerald-400"></i>
                                </div>
                                <div>
                                    <p class="text-[8px] font-bold text-gray-400 dark:text-slate-500 uppercase tracking-widest">{{ __('Số thành viên') }}</p>
                                    <p class="text-sm font-black text-gray-900 dark:text-white" x-text="pStatsTotals.users">0</p>
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
                                <div x-show="pStatsLoading" class="absolute inset-0 flex items-center justify-center bg-white/60 dark:bg-slate-900/60 rounded-2xl z-10">
                                    <svg class="animate-spin h-8 w-8 text-indigo-500" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                </div>
                                <canvas id="productStatsChart" height="270"></canvas>
                            </div>
                        </div>

                        <!-- Top sản phẩm nhiều click nhất (Chiếm 5 cột) -->
                        <div class="lg:col-span-5 space-y-3">
                            <h4 class="font-bold text-gray-900 dark:text-white uppercase tracking-wider text-[10px] flex items-center gap-1.5">
                                <i data-lucide="award" class="w-3.5 h-3.5 text-amber-500"></i>
                                {{ __('Top sản phẩm được lấy link nhiều nhất') }}
                            </h4>
                            <div class="bg-gray-50/50 dark:bg-slate-950/30 rounded-2xl p-4 border border-gray-100 dark:border-slate-800/50 overflow-y-auto" style="height: 300px;">
                                <div x-show="pStatsLoading" class="flex items-center justify-center h-56 text-gray-400">
                                    <svg class="animate-spin h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <span>{{ __('Đang tải...') }}</span>
                                </div>
                                <template x-if="!pStatsLoading && pStatsTopProducts.length === 0">
                                    <div class="flex flex-col items-center justify-center h-56 text-gray-400 dark:text-slate-500 text-xs">
                                        <i data-lucide="info" class="w-8 h-8 mb-2 opacity-50"></i>
                                        <span>{{ __('Không có dữ liệu lượt click trong khoảng thời gian này.') }}</span>
                                    </div>
                                </template>
                                <template x-if="!pStatsLoading && pStatsTopProducts.length > 0">
                                    <div class="space-y-3">
                                        <template x-for="(prod, idx) in pStatsTopProducts" :key="idx">
                                            <div class="p-3 bg-white dark:bg-slate-900 rounded-xl border border-gray-150 dark:border-slate-800/80 flex items-center justify-between shadow-sm">
                                                <div class="flex items-center gap-2.5 min-w-0 flex-1">
                                                    <span class="w-5 h-5 rounded-md flex items-center justify-center text-[10px] font-black shrink-0 font-mono"
                                                          :class="idx === 0 ? 'bg-amber-100 text-amber-700 dark:bg-amber-950/40 dark:text-amber-400' : (idx === 1 ? 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-400' : (idx === 2 ? 'bg-orange-100 text-orange-700 dark:bg-orange-950/40 dark:text-orange-400' : 'bg-gray-50 text-gray-500 dark:bg-slate-950 dark:text-slate-500'))"
                                                          x-text="idx + 1">
                                                    </span>
                                                    <span class="font-bold text-gray-800 dark:text-slate-200 truncate text-[11px]" x-text="prod.product_name" :title="prod.product_name"></span>
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
                    <button type="button" @click="productStatsModalOpen = false" class="px-5 py-2 text-xs font-semibold text-gray-700 dark:text-slate-350 bg-gray-100 dark:bg-slate-800 hover:bg-gray-200 dark:hover:bg-slate-700 rounded-xl transition-all border border-transparent dark:border-slate-700/50">
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
    function productAdminHandler() {
        return {
            showFilter: @json($hasFilter),
            productStatsModalOpen: false,
            pStatsLoading: false,
            pStatsPeriod: 'week',
            pStatsTotals: { clicks: 0, users: 0 },
            pStatsTopProducts: [],

            // Trạng thái chung khi đang gửi request để chặn double-submit (bấm nhiều lần liên tiếp)
            isSubmitting: false,

            // Trạng thái tích chọn sản phẩm để xóa nhanh hàng loạt
            selectedProducts: [],
            allSelected: false,
            selectableProductIds: @json($selectableProductIds),
            bulkDeleteModalOpen: false,
            confirmBulkDeleteCheckbox: false,

            // Trạng thái modal xác nhận xóa một sản phẩm ở cột thao tác
            deleteModalOpen: false,
            deleteTarget: { id: null, name: '', image: '', shopeeId: '' },

            // Trạng thái modal dọn dẹp sản phẩm theo mốc thời gian
            cleanupModalOpen: false,
            cleanupPeriod: '30_days',
            confirmCleanupCheckbox: false,
            cleanupCounts: @json($cleanupCounts),

            // Số lượng sản phẩm sẽ bị xóa ứng với mốc thời gian đang được chọn trong modal dọn dẹp
            get cleanupPeriodCount() {
                return this.cleanupCounts[this.cleanupPeriod] ?? 0;
            },

            // Địa chỉ gửi form xóa sản phẩm đang được chọn trong modal xác nhận
            get deleteFormAction() {
                return this.deleteTarget.id ? '{{ route('admin.products.index') }}/' + this.deleteTarget.id : '';
            },

            // Tích chọn hoặc bỏ chọn toàn bộ sản phẩm đang hiển thị trên trang hiện tại
            toggleAll() {
                if (this.allSelected) {
                    this.selectedProducts = [...this.selectableProductIds];
                } else {
                    this.selectedProducts = [];
                }
            },

            // Bỏ chọn toàn bộ sản phẩm đang tích chọn
            clearSelection() {
                this.selectedProducts = [];
                this.allSelected = false;
            },

            // Mở modal dọn dẹp sản phẩm, reset lại lựa chọn và ô xác nhận về mặc định
            openCleanupModal() {
                this.cleanupPeriod = '30_days';
                this.confirmCleanupCheckbox = false;
                this.cleanupModalOpen = true;
                setTimeout(() => { if (window.lucide) window.lucide.createIcons(); }, 50);
            },

            // Mở modal xác nhận xóa nhanh hàng loạt các sản phẩm đã tích chọn
            openBulkDeleteModal() {
                if (this.selectedProducts.length === 0) return;
                this.confirmBulkDeleteCheckbox = false;
                this.bulkDeleteModalOpen = true;
                setTimeout(() => { if (window.lucide) window.lucide.createIcons(); }, 50);
            },

            // Mở modal xác nhận xóa một sản phẩm, lưu lại thông tin sản phẩm đang chọn để hiển thị đối chiếu
            openDeleteModal(product) {
                if (!product || !product.id) return;
                this.deleteTarget = {
                    id: product.id,
                    name: product.name || '',
                    image: product.image || '',
                    shopeeId: product.shopeeId || ''
                };
                this.deleteModalOpen = true;
                setTimeout(() => { if (window.lucide) window.lucide.createIcons(); }, 50);
            },

            // Gửi yêu cầu dọn dẹp sản phẩm theo mốc thời gian đã chọn
            async submitCleanup() {
                if (this.isSubmitting) return;

                if (!this.confirmCleanupCheckbox) {
                    Swal.fire({
                        icon: 'error',
                        title: "{{ __('Chưa xác nhận') }}",
                        text: "{{ __('Vui lòng tích chọn ô xác nhận trước khi dọn dẹp dữ liệu.') }}"
                    });
                    return;
                }

                this.isSubmitting = true;

                try {
                    // Gọi thẳng phương thức DELETE đúng như khai báo của route.
                    // Lưu ý: KHÔNG dùng POST kèm _method trong body JSON vì Laravel chỉ đọc _method
                    // từ dữ liệu form-encoded hoặc query string, dẫn đến request bị trả về lỗi 405.
                    const response = await fetch('{{ route('admin.products.cleanup') }}', {
                        method: 'DELETE',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            period: this.cleanupPeriod,
                            confirm: true
                        })
                    });

                    const data = await response.json();

                    if (response.ok && data.status === 'success') {
                        this.cleanupModalOpen = false;

                        await Swal.fire({
                            icon: 'success',
                            title: "{{ __('Dọn dẹp thành công') }}",
                            text: data.message,
                            timer: 2200,
                            showConfirmButton: false
                        });

                        window.location.reload();
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: "{{ __('Dọn dẹp thất bại') }}",
                            text: data.message || "{{ __('Có lỗi xảy ra, vui lòng thử lại.') }}"
                        });
                    }
                } catch (error) {
                    console.error('Cleanup products error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: "{{ __('Lỗi hệ thống') }}",
                        text: "{{ __('Không thể kết nối đến máy chủ. Vui lòng thử lại sau.') }}"
                    });
                } finally {
                    this.isSubmitting = false;
                }
            },

            // Gửi yêu cầu xóa nhanh hàng loạt các sản phẩm đã tích chọn
            async submitBulkDelete() {
                if (this.isSubmitting) return;

                if (!this.confirmBulkDeleteCheckbox || this.selectedProducts.length === 0) {
                    Swal.fire({
                        icon: 'error',
                        title: "{{ __('Chưa xác nhận') }}",
                        text: "{{ __('Vui lòng tích chọn ô xác nhận trước khi xóa hàng loạt.') }}"
                    });
                    return;
                }

                this.isSubmitting = true;

                try {
                    // Gọi thẳng phương thức DELETE đúng như khai báo của route.
                    // Lưu ý: KHÔNG dùng POST kèm _method trong body JSON vì Laravel chỉ đọc _method
                    // từ dữ liệu form-encoded hoặc query string, dẫn đến request bị trả về lỗi 405.
                    const response = await fetch('{{ route('admin.products.bulk_destroy') }}', {
                        method: 'DELETE',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            ids: this.selectedProducts.join(','),
                            confirm: true
                        })
                    });

                    const data = await response.json();

                    if (response.ok && data.status === 'success') {
                        this.bulkDeleteModalOpen = false;
                        this.clearSelection();

                        await Swal.fire({
                            icon: 'success',
                            title: "{{ __('Xóa thành công') }}",
                            text: data.message,
                            timer: 2000,
                            showConfirmButton: false
                        });

                        window.location.reload();
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: "{{ __('Xóa thất bại') }}",
                            text: data.message || "{{ __('Có lỗi xảy ra, vui lòng thử lại.') }}"
                        });
                    }
                } catch (error) {
                    console.error('Bulk destroy products error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: "{{ __('Lỗi hệ thống') }}",
                        text: "{{ __('Không thể kết nối đến máy chủ. Vui lòng thử lại sau.') }}"
                    });
                } finally {
                    this.isSubmitting = false;
                }
            },

            // Mở modal thống kê click sản phẩm và tải dữ liệu tuần đầu tiên
            openProductStatsModal() {
                this.productStatsModalOpen = true;
                setTimeout(() => {
                    if (window.lucide) window.lucide.createIcons();
                    this.loadProductStats('week');
                }, 100);
            },
            
            // Gọi AJAX API lấy dữ liệu thống kê từ server
            async loadProductStats(period) {
                this.pStatsPeriod = period;
                this.pStatsLoading = true;
                
                try {
                    const response = await fetch(`{{ route('admin.products.stats') }}?period=${period}`, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        }
                    });
                    
                    if (!response.ok) throw new Error('Network error');
                    const result = await response.json();
                    
                    if (result.success) {
                        this.pStatsTotals = result.totals;
                        this.pStatsTopProducts = result.top_products;
                        this.renderProductChart(result, period);
                    }
                } catch (error) {
                    console.error('Product stats fetch error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: "{{ __('Lỗi tải dữ liệu') }}",
                        text: "{{ __('Không thể tải dữ liệu thống kê. Vui lòng thử lại.') }}"
                    });
                } finally {
                    this.pStatsLoading = false;
                }
            },
            
            // Render biểu đồ Chart.js cho số lượng click sản phẩm
            renderProductChart(data, period) {
                const canvas = document.getElementById('productStatsChart');
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
                            label: "{{ __('Lượt lấy link') }}",
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
