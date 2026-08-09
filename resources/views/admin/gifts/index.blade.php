@extends('layouts.admin')

@section('title', __('Quản Lý Quà Tặng & Đổi Thưởng') . ' - ' . $siteName)

@php
    $tab = request('tab', 'gifts');
    $hasFilter = false;
    if ($tab === 'gifts') {
        $hasFilter = request('search') || request('tag') || request('type') || (request('status') !== null && request('status') !== '') || (request('limit') && request('limit') != 15);
    } else {
        $hasFilter = request('search') || request('user_search') || (request('status') !== null && request('status') !== '') || (request('limit') && request('limit') != 15);
    }
@endphp

@section('content')
<div class="space-y-6" x-data="{ showFilter: @json($hasFilter), ...giftAdminHandler('{{ $tab }}', @json($gifts->pluck('id'))) }">
    <!-- Tiêu đề trang -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white uppercase tracking-tight flex items-center gap-2">
                <span class="w-1.5 h-6 rounded-full bg-gradient-to-b from-shopee to-shopee-light shrink-0"></span>
                {{ __('Quản Lý Quà Tặng & Đổi Thưởng') }}
            </h1>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                {{ __('Cấu hình danh mục quà tặng quy đổi (voucher, thẻ cào, quà vật lý) và phê duyệt các yêu cầu đổi thưởng từ thành viên.') }}
            </p>
        </div>
        <div class="flex items-center gap-2">
            <!-- Nút bật/tắt bộ lọc -->
            <button @click="showFilter = !showFilter" 
                    class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-semibold rounded-xl border border-gray-200 dark:border-slate-800 transition-all shadow-sm"
                    :class="showFilter ? 'bg-shopee text-white border-shopee hover:bg-shopee-dark' : 'bg-white dark:bg-slate-900 hover:bg-gray-50 text-gray-700 dark:text-slate-350'">
                <i data-lucide="filter" class="w-4 h-4"></i>
                <span>{{ __('Bộ lọc') }}</span>
                @if($hasFilter)
                    <span class="w-1.5 h-1.5 rounded-full bg-red-500 animate-pulse"></span>
                @endif
            </button>

            <button @click="openConfigModal()" 
                    class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-semibold text-gray-700 bg-white hover:bg-gray-50 border border-gray-200 dark:border-slate-800 dark:bg-slate-900 dark:text-gray-300 dark:hover:bg-slate-800 rounded-xl transition-all shadow-sm">
                <i data-lucide="settings" class="w-4 h-4"></i>
                {{ __('Cấu hình') }}
            </button>
            
            <!-- Nhập File Quà Tặng (Chỉ cho tab gifts) -->
            <button x-show="activeTab === 'gifts'"
                    @click="openImportModal()" 
                    class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-semibold text-gray-700 bg-white hover:bg-gray-50 border border-gray-200 dark:border-slate-800 dark:bg-slate-900 dark:text-gray-300 dark:hover:bg-slate-800 rounded-xl transition-all shadow-sm">
                <i data-lucide="upload" class="w-4 h-4 text-emerald-500"></i>
                {{ __('Nhập File') }}
            </button>
            
            <!-- Xuất File Quà Tặng (Chỉ cho tab gifts) -->
            <div x-show="activeTab === 'gifts'" class="relative" x-data="{ openExport: false }" @click.away="openExport = false">
                <button @click="openExport = !openExport" 
                        class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-semibold text-gray-700 bg-white hover:bg-gray-50 border border-gray-200 dark:border-slate-800 dark:bg-slate-900 dark:text-gray-300 dark:hover:bg-slate-800 rounded-xl transition-all shadow-sm">
                    <i data-lucide="download" class="w-4 h-4 text-blue-500"></i>
                    <span x-text="selectedGifts.length ? '{{ __('Xuất Đã Chọn') }} (' + selectedGifts.length + ')' : '{{ __('Xuất File') }}'"></span>
                    <i data-lucide="chevron-down" class="w-3.5 h-3.5 transition-transform" :class="openExport ? 'rotate-180' : ''"></i>
                </button>
                <div x-show="openExport" 
                     x-transition:enter="transition ease-out duration-100"
                     x-transition:enter-start="transform opacity-0 scale-95"
                     x-transition:enter-end="transform opacity-100 scale-100"
                     x-transition:leave="transition ease-in duration-75"
                     x-transition:leave-start="transform opacity-100 scale-100"
                     x-transition:leave-end="transform opacity-0 scale-95"
                     class="absolute right-0 mt-2 w-44 rounded-xl bg-white dark:bg-slate-900 shadow-lg ring-1 ring-black/5 dark:ring-slate-800 border border-gray-100 dark:border-slate-800 z-50 divide-y divide-gray-100 dark:divide-slate-800 focus:outline-none"
                     style="display: none;">
                    <div class="py-1">
                        <a no-loader :href="'{{ route('admin.gifts.export', ['format' => 'json']) }}' + (selectedGifts.length ? '?ids=' + selectedGifts.join(',') : '')" 
                           class="flex items-center gap-2 px-4 py-2 text-xs text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-slate-800/50">
                            <i data-lucide="file-json" class="w-4 h-4 text-amber-500"></i>
                            <span x-text="selectedGifts.length ? '{{ __('Xuất JSON Đã Chọn') }}' : '{{ __('Xuất JSON') }}'"></span>
                        </a>
                        <a no-loader :href="'{{ route('admin.gifts.export', ['format' => 'csv']) }}' + (selectedGifts.length ? '?ids=' + selectedGifts.join(',') : '')" 
                           class="flex items-center gap-2 px-4 py-2 text-xs text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-slate-800/50">
                            <i data-lucide="file-spreadsheet" class="w-4 h-4 text-emerald-500"></i>
                            <span x-text="selectedGifts.length ? '{{ __('Xuất CSV Đã Chọn') }}' : '{{ __('Xuất CSV') }}'"></span>
                        </a>
                    </div>
                </div>
            </div>

            <button x-show="activeTab === 'gifts'" 
                    @click="openAddGiftModal()" 
                    class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-semibold text-white bg-shopee hover:bg-shopee-dark rounded-xl transition-all shadow-md">
                <i data-lucide="plus" class="w-4 h-4"></i>
                {{ __('Thêm Quà Mới') }}
            </button>
        </div>
    </div>

    <!-- Thống kê đổi quà nhanh -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- 1. Hôm nay -->
        <div class="bg-white dark:bg-slate-900 p-6 rounded-2xl shadow-sm border border-gray-200 dark:border-slate-800 flex items-center justify-between">
            <div class="space-y-1">
                <span class="text-xs font-bold text-gray-400 dark:text-slate-500 uppercase tracking-wider block">{{ __('Đổi quà hôm nay') }}</span>
                <p class="text-2xl font-extrabold text-orange-600 dark:text-orange-400">{{ number_format($todayGiftExchanged) }}đ</p>
                <span class="text-[10px] text-gray-400 dark:text-slate-500 block">{{ __('Giá trị đã duyệt thành công') }}</span>
            </div>
            <div class="p-3 bg-orange-50 dark:bg-orange-950/30 text-orange-600 dark:text-orange-400 rounded-xl">
                <i data-lucide="calendar" class="w-6 h-6"></i>
            </div>
        </div>

        <!-- 2. Tuần này -->
        <div class="bg-white dark:bg-slate-900 p-6 rounded-2xl shadow-sm border border-gray-200 dark:border-slate-800 flex items-center justify-between">
            <div class="space-y-1">
                <span class="text-xs font-bold text-gray-400 dark:text-slate-500 uppercase tracking-wider block">{{ __('Đổi quà tuần này') }}</span>
                <p class="text-2xl font-extrabold text-sky-600 dark:text-sky-400">{{ number_format($weekGiftExchanged) }}đ</p>
                <span class="text-[10px] text-gray-400 dark:text-slate-500 block">{{ __('Từ đầu tuần đến nay') }}</span>
            </div>
            <div class="p-3 bg-sky-50 dark:bg-sky-950/30 text-sky-600 dark:text-sky-400 rounded-xl">
                <i data-lucide="calendar-days" class="w-6 h-6"></i>
            </div>
        </div>

        <!-- 3. Tháng này -->
        <div class="bg-white dark:bg-slate-900 p-6 rounded-2xl shadow-sm border border-gray-200 dark:border-slate-800 flex items-center justify-between">
            <div class="space-y-1">
                <span class="text-xs font-bold text-gray-400 dark:text-slate-500 uppercase tracking-wider block">{{ __('Đổi quà tháng này') }}</span>
                <p class="text-2xl font-extrabold text-purple-600 dark:text-purple-400">{{ number_format($monthGiftExchanged) }}đ</p>
                <span class="text-[10px] text-gray-400 dark:text-slate-500 block">{{ __('Từ đầu tháng đến nay') }}</span>
            </div>
            <div class="p-3 bg-purple-50 dark:bg-purple-950/30 text-purple-600 dark:text-purple-400 rounded-xl">
                <i data-lucide="calendar-range" class="w-6 h-6"></i>
            </div>
        </div>

        <!-- 4. Toàn thời gian -->
        <div class="bg-white dark:bg-slate-900 p-6 rounded-2xl shadow-sm border border-gray-200 dark:border-slate-800 flex items-center justify-between">
            <div class="space-y-1">
                <span class="text-xs font-bold text-gray-400 dark:text-slate-500 uppercase tracking-wider block">{{ __('Đổi quà toàn thời gian') }}</span>
                <p class="text-2xl font-extrabold text-pink-600 dark:text-pink-400">{{ number_format($totalGiftExchanged) }}đ</p>
                <span class="text-[10px] text-gray-400 dark:text-slate-500 block">{{ __('Tổng tích lũy trọn đời') }}</span>
            </div>
            <div class="p-3 bg-pink-50 dark:bg-pink-950/30 text-pink-600 dark:text-pink-400 rounded-xl">
                <i data-lucide="gift" class="w-6 h-6"></i>
            </div>
        </div>
    </div>

    <!-- Navigation Tab -->
    <div class="flex border-b border-gray-200 dark:border-slate-800">
        <button @click="changeTab('gifts')" 
                :class="activeTab === 'gifts' ? 'border-shopee text-shopee font-bold' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300'"
                class="px-6 py-3 border-b-2 font-medium text-xs transition-all uppercase tracking-wider">
            {{ __('Kho Quà Tặng') }}
        </button>
        <button @click="changeTab('redemptions')" 
                :class="activeTab === 'redemptions' ? 'border-shopee text-shopee font-bold' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300'"
                class="px-6 py-3 border-b-2 font-medium text-xs transition-all uppercase tracking-wider flex items-center gap-1.5">
            {{ __('Đơn Đổi Quà') }}
            @php
                $pendingCount = \App\Models\GiftRedemption::where('status', 'pending')->count();
            @endphp
            @if($pendingCount > 0)
            <span class="inline-flex items-center justify-center px-1.5 py-0.5 text-[9px] font-black leading-none text-white bg-red-600 rounded-full">
                {{ $pendingCount }}
            </span>
            @endif
        </button>
    </div>

    <!-- TAB 1: KHO QUÀ TẶNG -->
    <div x-show="activeTab === 'gifts'" x-transition class="space-y-4">
        <!-- Bộ lọc tìm kiếm -->
        <div x-show="showFilter"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 transform -translate-y-2"
             x-transition:enter-end="opacity-100 transform translate-y-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 transform translate-y-0"
             x-transition:leave-end="opacity-0 transform -translate-y-2"
             class="bg-white dark:bg-slate-900 rounded-3xl p-4 border border-gray-250/50 dark:border-slate-800/80 shadow-sm"
             style="display: {{ $hasFilter && $tab === 'gifts' ? 'block' : 'none' }}">
            <form action="{{ route('admin.gifts.index') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
                <input type="hidden" name="tab" value="gifts">
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
                <div>
                    <select name="tag" class="block w-full px-3 py-2 border border-gray-200 dark:border-slate-700 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-gray-50/50 dark:bg-slate-950 dark:text-white">
                        <option value="">{{ __('Tất cả tag lọc') }}</option>
                        @foreach($tags as $t)
                            <option value="{{ $t }}" {{ request('tag') === $t ? 'selected' : '' }}>{{ $t }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <select name="type" class="block w-full px-3 py-2 border border-gray-200 dark:border-slate-700 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-gray-50/50 dark:bg-slate-950 dark:text-white">
                        <option value="">{{ __('Tất cả phân loại') }}</option>
                        <option value="voucher" {{ request('type') === 'voucher' ? 'selected' : '' }}>Voucher</option>
                        <option value="phone_card" {{ request('type') === 'phone_card' ? 'selected' : '' }}>Thẻ cào</option>
                        <option value="giftcode" {{ request('type') === 'giftcode' ? 'selected' : '' }}>Giftcode</option>
                        <option value="physical" {{ request('type') === 'physical' ? 'selected' : '' }}>Vật lý</option>
                    </select>
                </div>
                <div>
                    <select name="status" class="block w-full px-3 py-2 border border-gray-200 dark:border-slate-700 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-gray-50/50 dark:bg-slate-950 dark:text-white">
                        <option value="">{{ __('Trạng thái') }}</option>
                        <option value="1" {{ request('status') === '1' ? 'selected' : '' }}>{{ __('Bật') }}</option>
                        <option value="0" {{ request('status') === '0' ? 'selected' : '' }}>{{ __('Tắt') }}</option>
                    </select>
                </div>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-gray-400">
                        <i data-lucide="search" class="w-4 h-4"></i>
                    </span>
                    <input type="text" name="search" value="{{ request('tab') === 'gifts' ? request('search') : '' }}" placeholder="{{ __('Tên quà tặng...') }}" class="block w-full pl-9 pr-4 py-2 border border-gray-200 dark:border-slate-700 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-gray-50/50 dark:bg-slate-950 dark:text-white transition-all">
                </div>
                <div class="lg:col-span-4"></div>
                <div class="flex gap-2">
                    <button type="submit" class="flex-grow px-5 py-2 text-xs font-semibold text-white bg-shopee hover:bg-shopee-dark rounded-xl transition-all shadow-md shrink-0">
                        {{ __('Lọc') }}
                    </button>
                    @if(request('tab') === 'gifts' && $hasFilter)
                        <a href="{{ route('admin.gifts.index', ['tab' => 'gifts']) }}" class="flex-grow inline-flex items-center justify-center px-4 py-2 text-xs font-semibold text-gray-500 hover:text-gray-700 dark:text-slate-400 bg-gray-100 dark:bg-slate-800 hover:bg-gray-200 dark:hover:bg-slate-700 rounded-xl transition-all shadow-sm shrink-0 gap-1">
                            <i data-lucide="x" class="w-3.5 h-3.5"></i>
                            <span>{{ __('Bỏ lọc') }}</span>
                        </a>
                    @endif
                </div>
            </form>
        </div>

        <div class="bg-white dark:bg-slate-900 rounded-3xl shadow-sm border border-gray-250/50 dark:border-slate-800/80 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse whitespace-nowrap">
                    <thead>
                        <tr class="border-b border-gray-200 dark:border-slate-800 text-xs font-bold text-gray-400 bg-gray-50/50 dark:bg-slate-900/50">
                            <th class="p-4 w-12 text-center">
                                <input type="checkbox" @change="toggleSelectAll(visibleGiftIds)" :checked="selectedGifts.length === visibleGiftIds.length && visibleGiftIds.length > 0" class="rounded border-gray-300 dark:border-slate-700 text-shopee focus:ring-shopee bg-white dark:bg-slate-800">
                            </th>
                            <th class="p-4 w-20">{{ __('Ảnh') }}</th>
                            <th class="p-4">{{ __('Tên quà tặng') }}</th>
                            <th class="p-4">{{ __('Giá quy đổi') }}</th>
                            <th class="p-4 text-center">{{ __('Tồn kho') }}</th>
                            <th class="p-4">{{ __('Phân loại') }}</th>
                            <th class="p-4">{{ __('Tag lọc') }}</th>
                            <th class="p-4 text-center">{{ __('Trạng thái') }}</th>
                            <th class="p-4 text-center">{{ __('Hành động') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-slate-800/50 text-xs text-gray-600 dark:text-gray-300">
                        @forelse($gifts as $gift)
                            <tr :class="selectedGifts.includes({{ $gift->id }}) ? 'bg-orange-55/50 dark:bg-slate-850/50' : ''" class="hover:bg-gray-50 dark:hover:bg-slate-800/30 transition-all">
                                <td class="p-4 text-center">
                                    <input type="checkbox" :value="{{ $gift->id }}" x-model="selectedGifts" class="rounded border-gray-300 dark:border-slate-700 text-shopee focus:ring-shopee bg-white dark:bg-slate-800">
                                </td>
                                <td class="p-4">
                                    <div class="w-12 h-12 rounded-xl bg-gray-100 dark:bg-slate-800 border border-gray-150 dark:border-slate-800 overflow-hidden flex items-center justify-center">
                                        @if($gift->image)
                                            <img src="{{ $gift->image }}" alt="{{ $gift->title }}" class="w-full h-full object-cover">
                                        @else
                                            <i data-lucide="gift" class="w-5 h-5 text-gray-400"></i>
                                        @endif
                                    </div>
                                </td>
                                <td class="p-4">
                                    <h4 class="font-bold text-gray-900 dark:text-white">{{ $gift->title }}</h4>
                                    <p class="text-[10px] text-gray-400 dark:text-gray-550 mt-1 max-w-sm truncate" title="{{ $gift->description }}">{{ $gift->description }}</p>
                                </td>
                                <td class="p-4 font-extrabold text-slate-900 dark:text-white">
                                    {{ number_format($gift->price) }}đ
                                </td>
                                <td class="p-4 text-center font-bold">
                                    <span class="{{ $gift->stock <= 0 ? 'text-red-500' : 'text-gray-700 dark:text-gray-300' }}">
                                        {{ $gift->stock }}
                                    </span>
                                </td>
                                <td class="p-4 font-semibold">
                                    @if($gift->type === 'voucher')
                                        <span class="inline-flex px-2 py-0.5 rounded-full text-[9px] bg-purple-50 text-purple-600 border border-purple-100 dark:bg-purple-950/20 dark:text-purple-400 dark:border-purple-900/30">{{ __('Voucher') }}</span>
                                    @elseif($gift->type === 'phone_card')
                                        <span class="inline-flex px-2 py-0.5 rounded-full text-[9px] bg-sky-50 text-sky-600 border border-sky-100 dark:bg-sky-950/20 dark:text-sky-400 dark:border-sky-900/30">{{ __('Thẻ cào ĐT') }}</span>
                                    @elseif($gift->type === 'giftcode')
                                        <span class="inline-flex px-2 py-0.5 rounded-full text-[9px] bg-amber-50 text-amber-600 border border-amber-100 dark:bg-amber-950/20 dark:text-amber-400 dark:border-amber-900/30">{{ __('Giftcode') }}</span>
                                    @else
                                        <span class="inline-flex px-2 py-0.5 rounded-full text-[9px] bg-pink-50 text-pink-600 border border-pink-100 dark:bg-pink-950/20 dark:text-pink-400 dark:border-pink-900/30">{{ __('Quà vật lý') }}</span>
                                    @endif
                                </td>
                                <td class="p-4 font-semibold text-orange-600 dark:text-orange-450">
                                    {{ $gift->tag ?? '-' }}
                                </td>
                                <td class="p-4 text-center">
                                    @if($gift->status)
                                        <span class="inline-flex px-2 py-0.5 rounded-full text-[9px] font-bold bg-green-50 text-green-600 border border-green-100 dark:bg-green-950/20 dark:text-green-400 dark:border-green-900/30">{{ __('Hoạt động') }}</span>
                                    @else
                                        <span class="inline-flex px-2 py-0.5 rounded-full text-[9px] font-bold bg-gray-100 text-gray-500 border border-gray-200 dark:bg-slate-800 dark:text-gray-400 dark:border-slate-700">{{ __('Tạm khóa') }}</span>
                                    @endif
                                </td>
                                <td class="p-4 text-center">
                                    <div class="flex items-center justify-center gap-1.5">
                                        <button @click="openEditGiftModal({{ json_encode($gift) }})" class="p-1.5 text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-950/30 rounded-lg transition-all" title="Chỉnh sửa">
                                            <i data-lucide="edit-3" class="w-3.5 h-3.5"></i>
                                        </button>
                                        <form action="{{ route('admin.gifts.destroy', $gift->id) }}" method="POST" onsubmit="return confirm('Bạn có chắc chắn muốn xoá món quà này?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="p-1.5 text-red-600 hover:bg-red-50 dark:hover:bg-red-950/30 rounded-lg transition-all" title="Xóa">
                                                <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="p-8 text-center text-gray-400">{{ __('Không có quà tặng nào trong kho.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($gifts->hasPages())
                <div class="p-4 border-t border-gray-100 dark:border-slate-800 bg-gray-50/50 dark:bg-slate-900/50">
                    {{ $gifts->appends(['tab' => 'gifts', 'gifts_page' => $gifts->currentPage()])->links() }}
                </div>
            @endif
        </div>
    </div>

    <!-- TAB 2: ĐƠN ĐỔI QUÀ -->
    <div x-show="activeTab === 'redemptions'" x-transition class="space-y-4">
        <!-- Bộ lọc tìm kiếm đơn đổi quà -->
        <div x-show="showFilter"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 transform -translate-y-2"
             x-transition:enter-end="opacity-100 transform translate-y-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 transform translate-y-0"
             x-transition:leave-end="opacity-0 transform -translate-y-2"
             class="bg-white dark:bg-slate-900 rounded-3xl p-4 border border-gray-250/50 dark:border-slate-800/80 shadow-sm"
             style="display: {{ $hasFilter && $tab === 'redemptions' ? 'block' : 'none' }}">
            <form action="{{ route('admin.gifts.index') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
                <input type="hidden" name="tab" value="redemptions">
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
                <div>
                    <select name="status" class="block w-full px-3 py-2 border border-gray-200 dark:border-slate-700 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-gray-50/50 dark:bg-slate-950 dark:text-white">
                        <option value="">{{ __('Tất cả trạng thái') }}</option>
                        <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>{{ __('Chờ xử lý') }}</option>
                        <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>{{ __('Thành công') }}</option>
                        <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>{{ __('Từ chối') }}</option>
                    </select>
                </div>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-gray-400">
                        <i data-lucide="user" class="w-4 h-4"></i>
                    </span>
                    <input type="text" name="user_search" value="{{ request('user_search') }}" placeholder="{{ __('Thành viên (Tên, email)...') }}" class="block w-full pl-9 pr-4 py-2 border border-gray-200 dark:border-slate-700 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-gray-50/50 dark:bg-slate-950 dark:text-white transition-all">
                </div>
                <div class="relative lg:col-span-2">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-gray-400">
                        <i data-lucide="search" class="w-4 h-4"></i>
                    </span>
                    <input type="text" name="search" value="{{ request('tab') === 'redemptions' ? request('search') : '' }}" placeholder="{{ __('Mã đơn, nội dung, tên quà...') }}" class="block w-full pl-9 pr-4 py-2 border border-gray-200 dark:border-slate-700 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-gray-50/50 dark:bg-slate-950 dark:text-white transition-all">
                </div>
                <div class="lg:col-span-4"></div>
                <div class="flex gap-2">
                    <button type="submit" class="flex-grow px-5 py-2 text-xs font-semibold text-white bg-shopee hover:bg-shopee-dark rounded-xl transition-all shadow-md shrink-0">
                        {{ __('Lọc') }}
                    </button>
                    @if(request('tab') === 'redemptions' && $hasFilter)
                        <a href="{{ route('admin.gifts.index', ['tab' => 'redemptions']) }}" class="flex-grow inline-flex items-center justify-center px-4 py-2 text-xs font-semibold text-gray-500 hover:text-gray-700 dark:text-slate-400 bg-gray-100 dark:bg-slate-800 hover:bg-gray-200 dark:hover:bg-slate-700 rounded-xl transition-all shadow-sm shrink-0 gap-1">
                            <i data-lucide="x" class="w-3.5 h-3.5"></i>
                            <span>{{ __('Bỏ lọc') }}</span>
                        </a>
                    @endif
                </div>
            </form>
        </div>

        <div class="bg-white dark:bg-slate-900 rounded-3xl shadow-sm border border-gray-250/50 dark:border-slate-800/80 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse whitespace-nowrap">
                    <thead>
                        <tr class="border-b border-gray-200 dark:border-slate-800 text-xs font-bold text-gray-400 bg-gray-50/50 dark:bg-slate-900/50">
                            <th class="p-4">{{ __('Mã đơn') }}</th>
                            <th class="p-4">{{ __('Thành viên') }}</th>
                            <th class="p-4">{{ __('Quà tặng') }}</th>
                            <th class="p-4">{{ __('Giá trị') }}</th>
                            <th class="p-4">{{ __('Thông tin giao hàng') }}</th>
                            <th class="p-4 text-center">{{ __('Trạng thái') }}</th>
                            <th class="p-4 text-center">{{ __('Hành động') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-slate-800/50 text-xs text-gray-600 dark:text-gray-300">
                        @forelse($redemptions as $item)
                            <tr class="hover:bg-gray-50 dark:hover:bg-slate-800/30 transition-all">
                                <td class="p-4 font-mono font-bold text-gray-500">
                                    {{ $item->code ?? '#' . $item->id }}
                                </td>
                                <td class="p-4">
                                    <h4 class="font-bold text-gray-900 dark:text-white">{{ $item->user?->name ?? __('Không xác định') }}</h4>
                                    <p class="text-[10px] text-gray-400 dark:text-gray-550 mt-0.5 font-mono">{{ $item->user?->email ?? __('Không xác định') }}</p>
                                </td>
                                <td class="p-4 font-semibold text-gray-800 dark:text-gray-200">
                                    {{ $item->gift->title }}
                                </td>
                                <td class="p-4 font-extrabold text-slate-900 dark:text-white">
                                    {{ number_format($item->amount) }}đ
                                </td>
                                <td class="p-4">
                                    @php
                                        $info = $item->shipping_info ?? [];
                                    @endphp
                                    <div class="space-y-0.5 text-[10px] text-gray-500 dark:text-gray-400">
                                        <p>Người nhận: <strong class="text-gray-700 dark:text-gray-300">{{ $info['fullname'] ?? '' }}</strong> ({{ $info['phone'] ?? '' }})</p>
                                        <p>Email: <span class="font-mono">{{ $info['email'] ?? '' }}</span></p>
                                        @if(!empty($info['address']))
                                            <p>Địa chỉ: <span>{{ $info['address'] }}</span></p>
                                        @endif
                                        @if(!empty($info['notes']))
                                            <p class="text-red-400">Lời nhắn: <span class="italic">"{{ $info['notes'] }}"</span></p>
                                        @endif
                                    </div>
                                </td>
                                <td class="p-4 text-center">
                                    @if($item->status === 'pending')
                                        <span class="inline-flex px-2 py-0.5 rounded-full text-[9px] font-bold bg-yellow-50 text-yellow-600 border border-yellow-100 dark:bg-yellow-950/20 dark:text-yellow-400 dark:border-yellow-900/30">{{ __('Chờ xử lý') }}</span>
                                    @elseif($item->status === 'approved')
                                        <span class="inline-flex px-2 py-0.5 rounded-full text-[9px] font-bold bg-green-50 text-green-600 border border-green-100 dark:bg-green-950/20 dark:text-green-400 dark:border-green-900/30">{{ __('Thành công') }}</span>
                                    @else
                                        <span class="inline-flex px-2 py-0.5 rounded-full text-[9px] font-bold bg-red-50 text-red-600 border border-red-100 dark:bg-red-950/20 dark:text-red-400 dark:border-red-900/30">{{ __('Từ chối') }}</span>
                                    @endif
                                </td>
                                <td class="p-4 text-center">
                                    <button @click="openDetailModal({{ json_encode($item) }})" class="inline-flex items-center gap-1 px-2.5 py-1 text-[10px] font-bold bg-slate-100 hover:bg-shopee dark:bg-slate-800 text-gray-700 dark:text-gray-300 hover:text-white dark:hover:text-white rounded-xl transition-all shadow-sm border border-gray-200 dark:border-slate-700/50 hover:border-transparent">
                                        <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                                        {{ __('Xem') }}
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="p-8 text-center text-gray-400">{{ __('Không có đơn đổi quà nào.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($redemptions->hasPages())
                <div class="p-4 border-t border-gray-100 dark:border-slate-800 bg-gray-50/50 dark:bg-slate-900/50">
                    {{ $redemptions->appends(['tab' => 'redemptions', 'redemptions_page' => $redemptions->currentPage()])->links() }}
                </div>
            @endif
        </div>
    </div>

    <!-- MODAL 1: THÊM / SỬA QUÀ TẶNG -->
    <template x-teleport="body">
        <div x-show="giftModalOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-slate-950/40 backdrop-blur-sm" x-transition>
            <div class="bg-white dark:bg-slate-900 rounded-3xl max-w-lg w-full shadow-2xl border border-gray-100 dark:border-slate-800 overflow-hidden" @click.away="giftModalOpen = false" x-transition>
                <div class="px-6 py-4 bg-gray-50 dark:bg-slate-800/50 border-b border-gray-100 dark:border-slate-800 flex justify-between items-center">
                    <h3 class="font-bold text-gray-950 dark:text-white text-sm" x-text="isEdit ? '{{ __('Chỉnh sửa quà tặng') }}' : '{{ __('Thêm quà tặng mới') }}'"></h3>
                    <button @click="giftModalOpen = false" class="text-gray-400 hover:text-gray-600"><i data-lucide="x" class="w-4 h-4"></i></button>
                </div>
                
                <form :action="isEdit ? '/' + window.adminPrefix + '/gifts/' + activeGift.id : '/' + window.adminPrefix + '/gifts'" method="POST" class="p-6 space-y-4">
                    @csrf
                    <template x-if="isEdit">
                        <input type="hidden" name="_method" value="PUT">
                    </template>

                    <!-- Tên quà tặng -->
                    <div>
                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">{{ __('Tên quà tặng') }}</label>
                        <input type="text" name="title" x-model="activeGift.title" required class="block w-full px-4 py-2 border border-gray-200 dark:border-slate-700 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-800 text-gray-800 dark:text-white">
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <!-- Giá quy đổi -->
                        <div>
                            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">{{ __('Giá quy đổi (đ)') }}</label>
                            <input type="number" name="price" x-model="activeGift.price" required min="0" class="block w-full px-4 py-2 border border-gray-200 dark:border-slate-700 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-800 text-gray-800 dark:text-white">
                        </div>

                        <!-- Tồn kho -->
                        <div>
                            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">{{ __('Số lượng kho') }}</label>
                            <input type="number" name="stock" x-model="activeGift.stock" required min="0" class="block w-full px-4 py-2 border border-gray-200 dark:border-slate-700 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-800 text-gray-800 dark:text-white">
                        </div>
                    </div>

                    <div class="grid grid-cols-3 gap-4">
                        <!-- Phân loại -->
                        <div>
                            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">{{ __('Phân loại') }}</label>
                            <select name="type" x-model="activeGift.type" required class="block w-full px-3 py-2 border border-gray-200 dark:border-slate-700 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-800 text-gray-800 dark:text-white">
                                <option value="voucher">Voucher</option>
                                <option value="phone_card">Thẻ cào</option>
                                <option value="giftcode">Giftcode</option>
                                <option value="physical">Vật lý</option>
                            </select>
                        </div>

                        <!-- Tag lọc -->
                        <div>
                            <div class="flex justify-between items-center mb-1">
                                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">{{ __('Tag lọc') }}</label>
                                <button type="button" @click="openManageTagsModal()" class="text-[10px] text-shopee hover:text-shopee-dark font-bold flex items-center gap-0.5">
                                    <i data-lucide="settings" class="w-3 h-3"></i>
                                    {{ __('Quản lý tag') }}
                                </button>
                            </div>
                            <select name="tag" x-model="activeGift.tag" class="block w-full px-3 py-2 border border-gray-200 dark:border-slate-700 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-800 text-gray-800 dark:text-white">
                                <option value="">{{ __('--- Không có tag ---') }}</option>
                                <template x-for="t in tags" :key="t">
                                    <option :value="t" x-text="t" :selected="activeGift.tag === t"></option>
                                </template>
                            </select>
                        </div>

                        <!-- Trạng thái -->
                        <div>
                            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">{{ __('Trạng thái') }}</label>
                            <select name="status" x-model="activeGift.status" required class="block w-full px-3 py-2 border border-gray-200 dark:border-slate-700 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-800 text-gray-800 dark:text-white">
                                <option value="1">{{ __('Bật') }}</option>
                                <option value="0">{{ __('Tắt') }}</option>
                            </select>
                        </div>
                    </div>

                    <!-- URL Hình ảnh -->
                    <div>
                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">{{ __('Đường dẫn ảnh quà tặng') }}</label>
                        <input type="text" name="image" x-model="activeGift.image" placeholder="https://domain.com/path-to-image.png" class="block w-full px-4 py-2 border border-gray-200 dark:border-slate-700 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-800 text-gray-800 dark:text-white">
                    </div>

                    <!-- Mô tả -->
                    <div>
                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">{{ __('Mô tả quà tặng') }}</label>
                        <textarea name="description" x-model="activeGift.description" rows="3" placeholder="Nhập mô tả, nội dung sử dụng quà tặng..." class="block w-full px-4 py-2 border border-gray-200 dark:border-slate-700 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-800 text-gray-800 dark:text-white"></textarea>
                    </div>

                    <div class="pt-3 border-t border-gray-150 dark:border-slate-800 flex justify-end gap-2">
                        <button type="button" @click="giftModalOpen = false" class="px-4 py-2 text-xs font-semibold text-gray-700 dark:text-slate-350 bg-gray-100 dark:bg-slate-800 hover:bg-gray-200 dark:hover:bg-slate-700 rounded-xl border border-transparent dark:border-slate-700/50 transition-all">{{ __('Huỷ') }}</button>
                        <button type="submit" class="px-5 py-2 text-xs font-semibold text-white bg-shopee hover:bg-shopee-dark rounded-xl transition-all shadow-md">{{ __('Lưu lại') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </template>

    <!-- MODAL CHI TIẾT & XỬ LÝ ĐƠN ĐỔI QUÀ -->
    <template x-teleport="body">
        <div x-show="detailModalOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-slate-950/40 backdrop-blur-sm" x-transition>
            <div class="bg-white dark:bg-slate-900 rounded-3xl max-w-lg w-full shadow-2xl border border-gray-100 dark:border-slate-800 overflow-hidden" @click.away="detailModalOpen = false" x-transition>
                <div class="px-6 py-4 bg-gray-50 dark:bg-slate-800/50 border-b border-gray-100 dark:border-slate-800 flex justify-between items-center">
                    <h3 class="font-bold text-gray-950 dark:text-white text-sm flex items-center gap-1.5">
                        <i data-lucide="info" class="w-4.5 h-4.5 text-shopee"></i>
                        <span x-text="'Chi tiết yêu cầu: ' + (activeRedemption.code || '#' + activeRedemption.id)"></span>
                    </h3>
                    <button @click="detailModalOpen = false" class="text-gray-400 hover:text-gray-600"><i data-lucide="x" class="w-4 h-4"></i></button>
                </div>
                
                <div class="p-6 space-y-5 max-h-[75vh] overflow-y-auto">
                    <!-- Trạng thái đơn & Thông tin cơ bản -->
                    <div class="grid grid-cols-2 gap-4 pb-3 border-b border-gray-100 dark:border-slate-800">
                        <div>
                            <span class="text-[9px] uppercase font-bold text-gray-400 tracking-wider block">{{ __('Thành viên') }}</span>
                            <strong class="text-gray-900 dark:text-white text-xs block mt-0.5" x-text="activeRedemption.user?.name"></strong>
                            <span class="text-[10px] text-gray-400 font-mono block" x-text="activeRedemption.user?.email"></span>
                        </div>
                        <div class="text-right">
                            <span class="text-[9px] uppercase font-bold text-gray-400 tracking-wider block">{{ __('Trạng thái') }}</span>
                            <template x-if="activeRedemption.status === 'pending'">
                                <span class="inline-flex px-2.5 py-0.5 rounded-full text-[9px] font-bold bg-yellow-50 text-yellow-600 border border-yellow-100 dark:bg-yellow-950/20 dark:text-yellow-400 dark:border-yellow-900/30 mt-1">{{ __('Chờ xử lý') }}</span>
                            </template>
                            <template x-if="activeRedemption.status === 'approved'">
                                <span class="inline-flex px-2.5 py-0.5 rounded-full text-[9px] font-bold bg-green-50 text-green-600 border border-green-100 dark:bg-green-950/20 dark:text-green-400 dark:border-green-900/30 mt-1">{{ __('Thành công') }}</span>
                            </template>
                            <template x-if="activeRedemption.status === 'rejected'">
                                <span class="inline-flex px-2.5 py-0.5 rounded-full text-[9px] font-bold bg-red-50 text-red-600 border border-red-100 dark:bg-red-950/20 dark:text-red-400 dark:border-red-900/30 mt-1">{{ __('Từ chối') }}</span>
                            </template>
                        </div>
                    </div>

                    <!-- Thông tin quà tặng -->
                    <div class="p-3 bg-gray-50 dark:bg-slate-800/30 rounded-2xl border border-gray-100 dark:border-slate-800/50 flex items-center gap-3">
                        <div class="w-12 h-12 rounded-xl bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-850 overflow-hidden flex items-center justify-center shrink-0">
                            <template x-if="activeRedemption.gift?.image">
                                <img :src="activeRedemption.gift?.image" class="w-full h-full object-cover">
                            </template>
                            <template x-if="!activeRedemption.gift?.image">
                                <i data-lucide="gift" class="w-5 h-5 text-gray-400"></i>
                            </template>
                        </div>
                        <div class="min-w-0">
                            <h4 class="font-bold text-gray-900 dark:text-white text-xs truncate" x-text="activeRedemption.gift?.title"></h4>
                            <p class="text-[10px] text-gray-400 font-medium mt-0.5">
                                {{ __('Phí đổi:') }} <span class="font-bold text-shopee" x-text="activeRedemption.amount ? new Intl.NumberFormat('vi-VN', { maximumFractionDigits: 0 }).format(activeRedemption.amount) + 'đ' : ''"></span>
                                <span class="mx-1">|</span>
                                <span x-text="activeRedemption.gift?.type === 'physical' ? 'Quà vật lý (cần ship)' : 'Sản phẩm số'"></span>
                            </p>
                        </div>
                    </div>

                    <!-- Thông tin nhận hàng của thành viên -->
                    <div class="space-y-2">
                        <h4 class="text-xs font-bold text-gray-900 dark:text-white uppercase tracking-wider pb-1 border-b border-gray-100 dark:border-slate-800 flex items-center gap-1.5">
                            <i data-lucide="truck" class="w-3.5 h-3.5 text-gray-400"></i>
                            {{ __('Thông tin nhận hàng') }}
                        </h4>
                        <div class="p-3.5 bg-slate-50 dark:bg-slate-850/50 rounded-2xl text-[11px] space-y-1.5 text-gray-605 dark:text-gray-300 border border-gray-150/40 dark:border-slate-800/30">
                            <div class="flex justify-between">
                                <span class="text-gray-400">{{ __('Người nhận:') }}</span>
                                <strong class="text-gray-805 dark:text-white" x-text="activeRedemption.shipping_info?.fullname"></strong>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-400">{{ __('Số điện thoại:') }}</span>
                                <span class="font-mono font-semibold" x-text="activeRedemption.shipping_info?.phone"></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-400">{{ __('Email nhận:') }}</span>
                                <span class="font-mono" x-text="activeRedemption.shipping_info?.email"></span>
                            </div>
                            <template x-if="activeRedemption.gift?.type === 'physical'">
                                <div class="flex justify-between gap-4">
                                    <span class="text-gray-400 shrink-0">{{ __('Địa chỉ nhận:') }}</span>
                                    <span class="text-right text-gray-805 dark:text-white font-semibold animate-pulse" x-text="activeRedemption.shipping_info?.address || '-'"></span>
                                </div>
                            </template>
                            <template x-if="activeRedemption.shipping_info?.notes">
                                <div class="border-t border-gray-150/50 dark:border-slate-800/40 pt-2 mt-1">
                                    <span class="text-gray-400 block mb-0.5">{{ __('Lời nhắn của khách:') }}</span>
                                    <span class="italic text-gray-700 dark:text-gray-300" x-text="'&ldquo;' + activeRedemption.shipping_info?.notes + '&rdquo;'"></span>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- KẾT QUẢ ĐÃ XỬ LÝ (APPROVED / REJECTED) -->
                    <template x-if="activeRedemption.status !== 'pending'">
                        <div class="space-y-2">
                            <h4 class="text-xs font-bold uppercase tracking-wider pb-1 border-b flex items-center gap-1.5"
                                :class="activeRedemption.status === 'approved' ? 'text-green-700 dark:text-green-400 border-green-100 dark:border-green-900/30' : 'text-red-700 dark:text-red-400 border-red-100 dark:border-red-900/30'">
                                <i :data-lucide="activeRedemption.status === 'approved' ? 'check-circle' : 'x-circle'" class="w-3.5 h-3.5"></i>
                                <span x-text="activeRedemption.status === 'approved' ? '{{ __('Thông tin xử lý thành công') }}' : '{{ __('Thông tin từ chối') }}'"></span>
                            </h4>
                            <div class="p-3.5 rounded-2xl text-[11px] space-y-2 border"
                                 :class="activeRedemption.status === 'approved' ? 'bg-green-50/50 dark:bg-green-950/10 border-green-100 dark:border-green-900/20 text-green-800 dark:text-green-400' : 'bg-red-50/50 dark:bg-red-950/10 border-red-100 dark:border-red-900/20 text-red-800 dark:text-red-400'">
                                
                                <template x-if="activeRedemption.status === 'approved'">
                                    <div>
                                        <!-- Khi KHÔNG ở chế độ edit bảo hành -->
                                        <template x-if="!editMode">
                                            <div class="space-y-2.5">
                                                <template x-if="activeRedemption.gift_data">
                                                    <div>
                                                        <span class="text-gray-400 block">{{ __('Mã quà tặng/Link gửi khách:') }}</span>
                                                        <strong class="font-mono text-xs text-green-700 dark:text-green-300 select-all whitespace-pre-line block mt-0.5" x-text="activeRedemption.gift_data"></strong>
                                                    </div>
                                                </template>
                                                <template x-if="activeRedemption.notes">
                                                    <div>
                                                        <span class="text-gray-400 block">{{ __('Ghi chú duyệt/Vận đơn:') }}</span>
                                                        <span class="text-gray-750 dark:text-gray-200" x-text="activeRedemption.notes"></span>
                                                    </div>
                                                </template>
                                                <div class="pt-2 border-t border-green-100/50 dark:border-green-900/20 flex justify-end">
                                                    <button type="button" @click="editMode = true; setTimeout(() => { if (window.lucide) window.lucide.createIcons(); }, 50);" class="inline-flex items-center gap-1 px-2.5 py-1 bg-green-600 hover:bg-green-700 text-white text-[10px] font-bold rounded-xl shadow-md transition-all">
                                                        <i data-lucide="edit-3" class="w-3 h-3"></i>
                                                        {{ __('Chỉnh sửa / Bảo hành') }}
                                                    </button>
                                                </div>
                                            </div>
                                        </template>

                                        <!-- Khi ĐANG ở chế độ edit bảo hành -->
                                        <template x-if="editMode">
                                            <form :action="'/' + window.adminPrefix + '/gifts/redemptions/' + activeRedemption.id + '/approve'" method="POST" class="space-y-3">
                                                @csrf
                                                <div>
                                                    <label class="block text-[10px] font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">{{ __('Mã thẻ / Giftcode / Voucher Link') }}</label>
                                                    <textarea name="gift_data" rows="3" placeholder="Điền mã code hoặc link voucher để gửi khách..." class="block w-full px-3 py-1.5 border border-gray-200 dark:border-slate-700 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-900 text-gray-805 dark:text-white" x-model="activeRedemption.gift_data"></textarea>
                                                </div>
                                                <div>
                                                    <label class="block text-[10px] font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">{{ __('Ghi chú duyệt / Mã vận đơn') }}</label>
                                                    <textarea name="notes" rows="2" placeholder="Ví dụ: Mã vận đơn..." class="block w-full px-3 py-1.5 border border-gray-200 dark:border-slate-700 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-900 text-gray-805 dark:text-white" x-model="activeRedemption.notes"></textarea>
                                                </div>
                                                <div class="flex justify-end gap-1.5">
                                                    <button type="button" @click="editMode = false" class="px-2.5 py-1 bg-gray-150 dark:bg-slate-800 text-gray-700 dark:text-gray-300 text-[10px] font-bold rounded-xl border border-transparent dark:border-slate-700/50 transition-all">{{ __('Huỷ') }}</button>
                                                    <button type="submit" class="px-3 py-1 bg-green-600 hover:bg-green-700 text-white text-[10px] font-bold rounded-xl shadow-md transition-all">{{ __('Lưu thay đổi') }}</button>
                                                </div>
                                            </form>
                                        </template>
                                    </div>
                                </template>

                                <template x-if="activeRedemption.status === 'rejected'">
                                    <div>
                                        <span class="text-gray-400 block">{{ __('Lý do từ chối & hoàn tiền ví:') }}</span>
                                        <strong class="text-red-700 dark:text-red-305" x-text="activeRedemption.notes"></strong>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>

                    <!-- KHU VỰC XỬ LÝ (CHỈ DÀNH CHO TRẠNG THÁI PENDING) -->
                    <template x-if="activeRedemption.status === 'pending'">
                        <div class="space-y-3 bg-gray-50 dark:bg-slate-800/40 p-4 rounded-2xl border border-gray-150 dark:border-slate-800/60">
                            <h4 class="text-xs font-bold text-gray-905 dark:text-white uppercase tracking-wider flex items-center gap-1.5">
                                <i data-lucide="sliders" class="w-3.5 h-3.5 text-shopee"></i>
                                {{ __('Xử lý yêu cầu') }}
                            </h4>
                            
                            <!-- Tab lựa chọn hành động -->
                            <div class="flex bg-white dark:bg-slate-900 p-1 rounded-xl border border-gray-200/60 dark:border-slate-800">
                                <button type="button" @click="actionType = 'approve'"
                                        :class="actionType === 'approve' ? 'bg-green-600 text-white shadow-sm' : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300'"
                                        class="flex-1 py-1.5 text-[11px] font-bold rounded-lg transition-all text-center">
                                    {{ __('Đồng ý duyệt') }}
                                </button>
                                <button type="button" @click="actionType = 'reject'"
                                        :class="actionType === 'reject' ? 'bg-red-600 text-white shadow-sm' : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300'"
                                        class="flex-1 py-1.5 text-[11px] font-bold rounded-lg transition-all text-center">
                                    {{ __('Từ chối đơn') }}
                                </button>
                            </div>

                            <!-- Form phê duyệt -->
                            <template x-if="actionType === 'approve'">
                                <form :action="'/' + window.adminPrefix + '/gifts/redemptions/' + activeRedemption.id + '/approve'" method="POST" class="space-y-3 pt-1">
                                    @csrf
                                    <div>
                                        <label class="block text-[10px] font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">{{ __('Mã thẻ / Giftcode / Voucher Link') }}</label>
                                        <textarea name="gift_data" rows="3" placeholder="Điền mã code hoặc link voucher để gửi khách (hỗ trợ nhiều dòng)..." class="block w-full px-4 py-2 border border-gray-200 dark:border-slate-700 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-900 text-gray-805 dark:text-white"></textarea>
                                        <span class="text-[9px] text-gray-400 mt-1 block">Để trống đối với quà vật lý. Thông tin này sẽ gửi cho User.</span>
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">{{ __('Ghi chú duyệt / Mã vận đơn') }}</label>
                                        <textarea name="notes" rows="2" placeholder="Ví dụ: Mã vận đơn GHTK: 123456789, dự kiến giao sau 2-3 ngày..." class="block w-full px-4 py-2 border border-gray-200 dark:border-slate-700 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-900 text-gray-805 dark:text-white"></textarea>
                                    </div>
                                    <div class="flex justify-end pt-1">
                                        <button type="submit" class="w-full px-5 py-2 text-xs font-bold text-white bg-green-600 hover:bg-green-700 rounded-xl transition-all shadow-md flex items-center justify-center gap-1">
                                            <i data-lucide="check" class="w-3.5 h-3.5"></i>
                                            {{ __('Xác nhận Duyệt & Gửi quà') }}
                                        </button>
                                    </div>
                                </form>
                            </template>

                            <!-- Form từ chối -->
                            <template x-if="actionType === 'reject'">
                                <form :action="'/' + window.adminPrefix + '/gifts/redemptions/' + activeRedemption.id + '/reject'" method="POST" class="space-y-3 pt-1">
                                    @csrf
                                    <div>
                                        <label class="block text-[10px] font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">{{ __('Lý do từ chối (Sẽ gửi thông báo cho user)') }}</label>
                                        <textarea name="notes" required rows="3" placeholder="Ví dụ: Quà tặng này tạm thời hết hàng, hệ thống đã hoàn trả lại tiền vào ví khả dụng của bạn..." class="block w-full px-4 py-2 border border-gray-200 dark:border-slate-700 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-900 text-gray-805 dark:text-white"></textarea>
                                    </div>
                                    <div class="flex justify-end pt-1">
                                        <button type="submit" class="w-full px-5 py-2 text-xs font-bold text-white bg-red-600 hover:bg-red-700 rounded-xl transition-all shadow-md flex items-center justify-center gap-1">
                                            <i data-lucide="x" class="w-3.5 h-3.5"></i>
                                            {{ __('Xác nhận Từ chối & Hoàn tiền') }}
                                        </button>
                                    </div>
                                </form>
                            </template>
                        </div>
                    </template>
                </div>
                
                <div class="px-6 py-4 bg-gray-50 dark:bg-slate-800/50 border-t border-gray-100 dark:border-slate-800 flex justify-end">
                    <button type="button" @click="detailModalOpen = false" class="px-5 py-2 text-xs font-semibold text-gray-700 dark:text-slate-350 bg-gray-100 dark:bg-slate-800 hover:bg-gray-200 dark:hover:bg-slate-700 rounded-xl border border-transparent dark:border-slate-700/50 transition-all">{{ __('Đóng') }}</button>
                </div>
            </div>
        </div>
    </template>

    <!-- MODAL CẤU HÌNH QUY ĐỔI QUÀ TẶNG -->
    <template x-teleport="body">
        <div x-show="configModalOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-slate-950/40 backdrop-blur-sm" x-transition>
            <div class="bg-white dark:bg-slate-900 rounded-3xl max-w-md w-full shadow-2xl border border-gray-150 dark:border-slate-800 overflow-hidden" @click.away="configModalOpen = false" x-transition>
                <div class="px-6 py-4 bg-gray-50 dark:bg-slate-800/50 border-b border-gray-100 dark:border-slate-800 flex justify-between items-center">
                    <h3 class="text-sm font-bold text-gray-900 dark:text-white uppercase tracking-wider flex items-center gap-2">
                        <i data-lucide="settings" class="w-4 h-4 text-shopee"></i>
                        {{ __('Cấu hình Quy đổi Quà') }}
                    </h3>
                    <button type="button" @click="configModalOpen = false" class="text-gray-400 hover:text-gray-650"><i data-lucide="x" class="w-4 h-4"></i></button>
                </div>
                
                <form action="{{ route('admin.gifts.config.update') }}" method="POST" class="p-6 space-y-4">
                    @csrf
                    <div class="flex items-center justify-between p-3.5 bg-gray-50 dark:bg-slate-800/40 rounded-2xl border border-gray-150 dark:border-slate-800/50">
                        <div>
                            <label class="block text-xs font-bold text-gray-805 dark:text-white">{{ __('Chức năng đổi quà tặng') }}</label>
                            <span class="text-[10px] text-gray-400 block mt-0.5">{{ __('Bật hoặc tắt toàn bộ trang đổi quà tặng ngoài thành viên.') }}</span>
                        </div>
                        
                        <!-- Toggle switch -->
                        <label class="relative inline-flex items-center cursor-pointer">
                            @php
                                $isEnabled = \App\Models\Setting::getVal('gift_redemption_enabled', '0') === '1';
                            @endphp
                            <input type="hidden" name="gift_redemption_enabled" value="0">
                            <input type="checkbox" name="gift_redemption_enabled" value="1" {{ $isEnabled ? 'checked' : '' }} class="sr-only peer">
                            <div class="w-9 h-5 bg-gray-200 dark:bg-slate-700 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all dark:border-gray-650 peer-checked:bg-shopee"></div>
                        </label>
                    </div>
                    
                    <div class="pt-4 border-t border-gray-150 dark:border-slate-800 flex justify-end gap-2">
                        <button type="button" @click="configModalOpen = false" class="px-4 py-2 text-xs font-semibold text-gray-700 dark:text-slate-350 bg-gray-100 dark:bg-slate-800 hover:bg-gray-200 dark:hover:bg-slate-700 rounded-xl border border-transparent dark:border-slate-700/50 transition-all">{{ __('Huỷ') }}</button>
                        <button type="submit" class="px-4 py-2 text-xs font-semibold text-white bg-shopee hover:bg-shopee-dark rounded-xl transition-all shadow-md">{{ __('Lưu cấu hình') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </template>

    <!-- MODAL 4: NHẬP DANH SÁCH QUÀ TẶNG (IMPORT) -->
    <template x-teleport="body">
        <div x-show="importModalOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-slate-950/40 backdrop-blur-sm" x-transition>
            <div class="bg-white dark:bg-slate-900 rounded-3xl max-w-md w-full shadow-2xl border border-gray-150 dark:border-slate-800 overflow-hidden" @click.away="importModalOpen = false" x-transition>
                <div class="px-6 py-4 bg-gray-50 dark:bg-slate-800/50 border-b border-gray-100 dark:border-slate-800 flex justify-between items-center">
                    <h3 class="text-sm font-bold text-gray-900 dark:text-white uppercase tracking-wider flex items-center gap-2">
                        <i data-lucide="upload" class="w-4 h-4 text-shopee"></i>
                        {{ __('Nhập Danh Sách Quà Tặng') }}
                    </h3>
                    <button type="button" @click="importModalOpen = false" class="text-gray-400 hover:text-gray-650"><i data-lucide="x" class="w-4 h-4"></i></button>
                </div>
                
                <form action="{{ route('admin.gifts.import') }}" method="POST" enctype="multipart/form-data" class="p-6 space-y-4">
                    @csrf
                    
                    <div class="p-4 bg-blue-50/50 dark:bg-blue-950/10 border border-blue-100 dark:border-blue-900/30 rounded-2xl text-[11px] text-blue-800 dark:text-blue-400 space-y-2">
                        <p class="font-bold flex items-center gap-1">
                            <i data-lucide="info" class="w-3.5 h-3.5"></i>
                            {{ __('Hướng dẫn nhập file:') }}
                        </p>
                        <ul class="list-disc pl-4 space-y-1">
                            <li>{{ __('Chấp nhận tệp tin định dạng .json hoặc .csv') }}</li>
                            <li>{{ __('Cấu trúc cột tệp CSV (hoặc thuộc tính JSON):') }} 
                                <span class="font-mono text-gray-600 dark:text-gray-400 block mt-1 bg-white dark:bg-slate-950/50 p-1.5 rounded border border-gray-200/50 dark:border-slate-800 overflow-x-auto whitespace-nowrap">
                                    title, image, description, price, stock, type, tag, status
                                </span>
                            </li>
                            <li>{{ __('Phân loại (type) hợp lệ: voucher, phone_card, giftcode, physical') }}</li>
                            <li>{{ __('Trạng thái (status): 1 (Hoạt động) hoặc 0 (Tắt)') }}</li>
                        </ul>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-2">{{ __('Chọn tệp tin dữ liệu') }}</label>
                        <div class="relative group">
                            <input type="file" name="file" required accept=".json,.csv"
                                   class="block w-full text-xs text-gray-500 dark:text-gray-400
                                          file:mr-4 file:py-2 file:px-4
                                          file:rounded-xl file:border-0
                                          file:text-xs file:font-semibold
                                          file:bg-shopee/10 file:text-shopee
                                          hover:file:bg-shopee/20
                                          border border-gray-200 dark:border-slate-700 rounded-xl p-1 bg-gray-50 dark:bg-slate-800 focus:outline-none">
                        </div>
                    </div>
                    
                    <div class="pt-4 border-t border-gray-150 dark:border-slate-800 flex justify-end gap-2">
                        <button type="button" @click="importModalOpen = false" class="px-4 py-2 text-xs font-semibold text-gray-700 dark:text-slate-350 bg-gray-100 dark:bg-slate-800 hover:bg-gray-200 dark:hover:bg-slate-700 rounded-xl border border-transparent dark:border-slate-700/50 transition-all">{{ __('Huỷ') }}</button>
                        <button type="submit" class="px-4 py-2 text-xs font-semibold text-white bg-shopee hover:bg-shopee-dark rounded-xl transition-all shadow-md flex items-center gap-1">
                            <i data-lucide="check" class="w-3.5 h-3.5"></i>
                            {{ __('Bắt đầu Nhập') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </template>

    <!-- MODAL 5: QUẢN LÝ TAG LỌC (MANAGE TAGS) -->
    <template x-teleport="body">
        <div x-show="manageTagsModalOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-slate-950/40 backdrop-blur-sm" x-transition>
            <div class="bg-white dark:bg-slate-900 rounded-3xl max-w-md w-full shadow-2xl border border-gray-150 dark:border-slate-800 overflow-hidden" @click.away="manageTagsModalOpen = false" x-transition>
                <div class="px-6 py-4 bg-gray-50 dark:bg-slate-800/50 border-b border-gray-100 dark:border-slate-800 flex justify-between items-center">
                    <h3 class="text-sm font-bold text-gray-900 dark:text-white uppercase tracking-wider flex items-center gap-2">
                        <i data-lucide="tag" class="w-4 h-4 text-shopee"></i>
                        {{ __('Quản Lý Tag Lọc') }}
                    </h3>
                    <button type="button" @click="manageTagsModalOpen = false" class="text-gray-400 hover:text-gray-650"><i data-lucide="x" class="w-4 h-4"></i></button>
                </div>
                
                <div class="p-6 space-y-4">
                    <!-- Input thêm Tag mới -->
                    <div class="flex gap-2">
                        <input type="text" x-model="newTagName" @keydown.enter.prevent="addLocalTag()" placeholder="Nhập tên tag mới..." class="block flex-grow px-4 py-2 border border-gray-200 dark:border-slate-700 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-800 text-gray-800 dark:text-white">
                        <button type="button" @click="addLocalTag()" class="px-4 py-2 text-xs font-semibold text-white bg-shopee hover:bg-shopee-dark rounded-xl transition-all shadow-md flex items-center gap-1">
                            <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                            {{ __('Thêm') }}
                        </button>
                    </div>

                    <!-- Danh sách Tag hiện tại -->
                    <div class="space-y-2 max-h-60 overflow-y-auto custom-scrollbar pr-1">
                        <template x-for="(tag, index) in localTags" :key="index">
                            <div class="flex items-center justify-between p-2.5 bg-gray-50 dark:bg-slate-800/40 rounded-xl border border-gray-150 dark:border-slate-800/50">
                                <input type="text" x-model="localTags[index]" class="bg-transparent border-0 focus:ring-0 text-xs font-medium text-gray-800 dark:text-white w-full py-0">
                                <button type="button" @click="removeLocalTag(index)" class="text-red-500 hover:text-red-700 ml-2">
                                    <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                </button>
                            </div>
                        </template>
                        <template x-if="localTags.length === 0">
                            <div class="text-center py-6 text-xs text-gray-400 italic">
                                {{ __('Chưa có tag lọc nào.') }}
                            </div>
                        </template>
                    </div>
                    
                    <div class="pt-4 border-t border-gray-150 dark:border-slate-800 flex justify-end gap-2">
                        <button type="button" @click="manageTagsModalOpen = false" class="px-4 py-2 text-xs font-semibold text-gray-700 dark:text-slate-350 bg-gray-100 dark:bg-slate-800 hover:bg-gray-200 dark:hover:bg-slate-700 rounded-xl border border-transparent dark:border-slate-700/50 transition-all">{{ __('Huỷ') }}</button>
                        <button type="button" @click="saveTags()" :disabled="savingTags" class="px-4 py-2 text-xs font-semibold text-white bg-shopee hover:bg-shopee-dark rounded-xl transition-all shadow-md flex items-center gap-1 disabled:opacity-50">
                            <template x-if="savingTags">
                                <span class="animate-spin rounded-full h-3 w-3 border-b-2 border-white"></span>
                            </template>
                            <i x-show="!savingTags" data-lucide="check" class="w-3.5 h-3.5"></i>
                            {{ __('Lưu Thay Đổi') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </template>

    <!-- FLOATING BULK ACTIONS TOOLBAR -->
    <div x-show="selectedGifts.length > 0" x-cloak class="fixed bottom-6 left-1/2 -translate-x-1/2 z-40 bg-white/85 dark:bg-slate-900/95 backdrop-blur-md px-6 py-4 rounded-3xl shadow-2xl border border-gray-150 dark:border-slate-800/80 flex items-center gap-4 transition-all duration-300 transform" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 translate-y-4">
        <div class="flex items-center gap-2.5">
            <span class="relative flex h-3 w-3">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-shopee opacity-75"></span>
                <span class="relative inline-flex rounded-full h-3 w-3 bg-shopee"></span>
            </span>
            <span class="text-xs font-bold text-gray-800 dark:text-slate-200">{{ __('Đã chọn:') }} <strong class="text-shopee dark:text-shopee-light" x-text="selectedGifts.length"></strong> {{ __('quà tặng') }}</span>
        </div>
        <div class="h-6 w-[1px] bg-gray-200 dark:bg-slate-800"></div>
        <div class="flex items-center gap-2">
            <!-- Nút sửa hàng loạt -->
            <button type="button" @click="openBulkEditModal()" class="px-4 py-2.5 text-[10px] font-bold text-white bg-indigo-600 hover:bg-indigo-700 rounded-xl transition-all shadow-md flex items-center gap-1.5">
                <i data-lucide="edit" class="w-3.5 h-3.5"></i>
                {{ __('Sửa hàng loạt') }}
            </button>

            <!-- Nút xóa hàng loạt -->
            <button type="button" @click="openBulkDeleteModal()" class="px-4 py-2.5 text-[10px] font-bold text-white bg-red-600 hover:bg-red-700 rounded-xl transition-all shadow-md flex items-center gap-1.5">
                <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                {{ __('Xóa hàng loạt') }}
            </button>
        </div>
    </div>

    <!-- MODAL 6: CHỈNH SỬA HÀNG LOẠT (BULK EDIT) -->
    <template x-teleport="body">
        <div x-show="bulkEditModalOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-slate-950/40 backdrop-blur-sm" x-transition>
            <div class="bg-white dark:bg-slate-900 rounded-3xl max-w-md w-full shadow-2xl border border-gray-150 dark:border-slate-800 overflow-hidden" @click.away="bulkEditModalOpen = false" x-transition>
                <div class="px-6 py-4 bg-gray-50 dark:bg-slate-800/50 border-b border-gray-100 dark:border-slate-800 flex justify-between items-center">
                    <h3 class="text-sm font-bold text-gray-900 dark:text-white uppercase tracking-wider flex items-center gap-2">
                        <i data-lucide="edit" class="w-4 h-4 text-shopee"></i>
                        {{ __('Sửa Hàng Loạt Quà Tặng') }}
                    </h3>
                    <button type="button" @click="bulkEditModalOpen = false" class="text-gray-400 hover:text-gray-650"><i data-lucide="x" class="w-4 h-4"></i></button>
                </div>
                
                <form @submit.prevent="submitBulkEdit()" class="p-6 space-y-4">
                    @csrf
                    
                    <div class="p-3 bg-blue-50 dark:bg-blue-950/30 border border-blue-100 dark:border-blue-900/40 rounded-2xl text-[10px] text-blue-800 dark:text-blue-300 font-medium">
                        {{ __('Đang chỉnh sửa cho:') }} <strong class="dark:text-blue-200 text-blue-950" x-text="selectedGifts.length"></strong> {{ __('quà tặng đã chọn.') }}
                    </div>

                    <!-- Trường Phân Loại -->
                    <div class="space-y-2">
                        <label class="flex items-center gap-2 text-xs font-bold text-gray-700 dark:text-slate-300">
                            <input type="checkbox" x-model="bulkFields.type" class="rounded border-gray-300 dark:border-slate-700 text-shopee focus:ring-shopee">
                            <span>{{ __('Cập nhật Phân Loại') }}</span>
                        </label>
                        <select x-show="bulkFields.type" x-model="bulkData.type" class="block w-full px-3 py-2 border border-gray-200 dark:border-slate-700 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-800 text-gray-800 dark:text-white">
                            <option value="voucher">{{ __('Voucher / Mã giảm giá') }}</option>
                            <option value="phone_card">{{ __('Thẻ cào điện thoại') }}</option>
                            <option value="giftcode">{{ __('Giftcode game') }}</option>
                            <option value="physical">{{ __('Quà tặng vật lý') }}</option>
                        </select>
                    </div>

                    <!-- Trường Tag lọc -->
                    <div class="space-y-2">
                        <label class="flex items-center gap-2 text-xs font-bold text-gray-700 dark:text-slate-300">
                            <input type="checkbox" x-model="bulkFields.tag" class="rounded border-gray-300 dark:border-slate-700 text-shopee focus:ring-shopee">
                            <span>{{ __('Cập nhật Tag lọc') }}</span>
                        </label>
                        <select x-show="bulkFields.tag" x-model="bulkData.tag" class="block w-full px-3 py-2 border border-gray-200 dark:border-slate-700 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-800 text-gray-800 dark:text-white">
                            <option value="">-- {{ __('Chọn tag lọc') }} --</option>
                            <template x-for="t in tags" :key="t">
                                <option :value="t" x-text="t"></option>
                            </template>
                        </select>
                    </div>

                    <!-- Trường Trạng thái -->
                    <div class="space-y-2">
                        <label class="flex items-center gap-2 text-xs font-bold text-gray-700 dark:text-slate-300">
                            <input type="checkbox" x-model="bulkFields.status" class="rounded border-gray-300 dark:border-slate-700 text-shopee focus:ring-shopee">
                            <span>{{ __('Cập nhật Trạng thái') }}</span>
                        </label>
                        <select x-show="bulkFields.status" x-model="bulkData.status" class="block w-full px-3 py-2 border border-gray-200 dark:border-slate-700 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-800 text-gray-800 dark:text-white">
                            <option value="1">{{ __('Hoạt động') }}</option>
                            <option value="0">{{ __('Tạm khóa') }}</option>
                        </select>
                    </div>

                    <!-- Trường Tồn kho -->
                    <div class="space-y-2">
                        <label class="flex items-center gap-2 text-xs font-bold text-gray-700 dark:text-slate-300">
                            <input type="checkbox" x-model="bulkFields.stock" class="rounded border-gray-300 dark:border-slate-700 text-shopee focus:ring-shopee">
                            <span>{{ __('Cập nhật Số lượng tồn kho') }}</span>
                        </label>
                        <input x-show="bulkFields.stock" type="number" x-model="bulkData.stock" required min="0" class="block w-full px-4 py-2 border border-gray-200 dark:border-slate-700 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-800 text-gray-800 dark:text-white">
                    </div>

                    <div class="pt-4 border-t border-gray-150 dark:border-slate-800 flex justify-end gap-2">
                        <button type="button" @click="bulkEditModalOpen = false" class="px-4 py-2 text-xs font-semibold text-gray-700 dark:text-slate-350 bg-gray-100 dark:bg-slate-800 hover:bg-gray-200 dark:hover:bg-slate-700 rounded-xl border border-transparent dark:border-slate-700/50 transition-all">{{ __('Huỷ') }}</button>
                        <button type="submit" :disabled="isSubmitting || !Object.values(bulkFields).some(v => v)" class="px-4 py-2 text-xs font-semibold text-white bg-shopee hover:bg-shopee-dark rounded-xl transition-all shadow-md flex items-center gap-1 disabled:opacity-50">
                            <span x-show="isSubmitting" class="animate-spin rounded-full h-3 w-3 border-b-2 border-white"></span>
                            <i x-show="!isSubmitting" data-lucide="check" class="w-3.5 h-3.5"></i>
                            {{ __('Lưu Thay Đổi') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </template>

    <!-- MODAL 7: XÁC NHẬN XÓA HÀNG LOẠT (BULK DELETE) -->
    <template x-teleport="body">
        <div x-show="bulkDeleteModalOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-slate-950/40 backdrop-blur-sm" x-transition>
            <div class="bg-white dark:bg-slate-900 rounded-3xl max-w-md w-full shadow-2xl border border-gray-100 dark:border-slate-800 overflow-hidden" @click.away="bulkDeleteModalOpen = false" x-transition>
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
                            {{ __('Hành động này sẽ xóa vĩnh viễn') }} <strong class="text-red-600 dark:text-red-400 font-extrabold" x-text="selectedGifts.length"></strong> {{ __('quà tặng đã chọn khỏi hệ thống.') }}
                        </p>
                    </div>

                    <!-- Safeguard Input Verification -->
                    <div class="space-y-2 border-t border-gray-150 dark:border-slate-800/80 pt-4">
                        <label class="block text-[10px] font-bold text-gray-700 dark:text-slate-350 uppercase tracking-wider">
                            {{ __('Nhập cụm từ') }} <span class="text-red-600 dark:text-red-400 font-black select-all">XÓA HÀNG LOẠT</span> {{ __('để xác nhận xóa:') }}
                        </label>
                        <input type="text" x-model="confirmBulkText" placeholder="{{ __('Nhập XÓA HÀNG LOẠT...') }}" class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-800 dark:bg-slate-950 dark:text-white rounded-xl text-xs font-semibold focus:outline-none focus:ring-2 focus:ring-red-500/20 focus:border-red-500 transition-all placeholder-gray-400 dark:placeholder-slate-500">
                    </div>

                    <!-- Footer Action Buttons -->
                    <form @submit.prevent="submitBulkDelete()" class="flex justify-end gap-2 pt-2">
                        @csrf
                        <button type="button" @click="bulkDeleteModalOpen = false" :disabled="isSubmitting" class="px-4 py-2.5 text-xs font-bold text-gray-700 dark:text-slate-350 bg-gray-100 dark:bg-slate-800 hover:bg-gray-200 dark:hover:bg-slate-700 rounded-xl transition-all disabled:opacity-50">{{ __('Hủy bỏ') }}</button>
                        <button type="submit" :disabled="confirmBulkText.toUpperCase() !== 'XÓA HÀNG LOẠT' || isSubmitting" class="px-5 py-2.5 text-xs font-bold text-white bg-red-600 hover:bg-red-700 disabled:opacity-40 disabled:cursor-not-allowed rounded-xl transition-all shadow-md flex items-center justify-center min-w-[160px]">
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
</div>
@endsection

@section('scripts')
<script>
    function giftAdminHandler(defaultTab, visibleGiftIds) {
        return {
            activeTab: defaultTab || 'gifts',
            visibleGiftIds: visibleGiftIds || [],
            selectedGifts: [],
            toggleSelectAll(giftIds) {
                if (this.selectedGifts.length === giftIds.length) {
                    this.selectedGifts = [];
                } else {
                    this.selectedGifts = [...giftIds];
                }
            },
            giftModalOpen: false,
            bulkEditModalOpen: false,
            bulkDeleteModalOpen: false,
            confirmBulkText: '',
            isSubmitting: false,
            bulkFields: {
                type: false,
                tag: false,
                status: false,
                stock: false
            },
            bulkData: {
                type: 'voucher',
                tag: '',
                status: 1,
                stock: 0
            },
            detailModalOpen: false,
            configModalOpen: false,
            importModalOpen: false,
            manageTagsModalOpen: false,
            tags: @json($tags),
            localTags: [],
            newTagName: '',
            savingTags: false,
            actionType: 'approve',
            editMode: false,
            isEdit: false,
            activeGift: {
                id: null,
                title: '',
                price: 0,
                stock: 0,
                type: 'voucher',
                tag: '',
                status: 1,
                image: '',
                description: ''
            },
            activeRedemption: {},

            init() {
                setTimeout(() => { if (window.lucide) window.lucide.createIcons(); }, 50);
            },

            changeTab(tabName) {
                this.activeTab = tabName;
                const url = new URL(window.location.href);
                url.searchParams.set('tab', tabName);
                window.history.pushState({}, '', url.toString());
            },

            openConfigModal() {
                this.configModalOpen = true;
                setTimeout(() => { if (window.lucide) window.lucide.createIcons(); }, 50);
            },

            openImportModal() {
                this.importModalOpen = true;
                setTimeout(() => { if (window.lucide) window.lucide.createIcons(); }, 50);
            },

            openManageTagsModal() {
                this.localTags = [...this.tags];
                this.newTagName = '';
                this.manageTagsModalOpen = true;
                setTimeout(() => { if (window.lucide) window.lucide.createIcons(); }, 50);
            },

            addLocalTag() {
                const name = this.newTagName.trim();
                if (!name) return;
                if (this.localTags.includes(name)) {
                    window.dispatchEvent(new CustomEvent('toast', { detail: { text: '{{ __('Tag này đã tồn tại!') }}', type: 'warning' } }));
                    return;
                }
                this.localTags.push(name);
                this.newTagName = '';
                setTimeout(() => { if (window.lucide) window.lucide.createIcons(); }, 50);
            },

            removeLocalTag(index) {
                this.localTags.splice(index, 1);
            },

            saveTags() {
                this.savingTags = true;
                fetch('{{ route("admin.gifts.tags.update") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ tags: this.localTags })
                })
                .then(res => res.json())
                .then(data => {
                    this.savingTags = false;
                    if (data.success) {
                        this.tags = data.tags;
                        this.manageTagsModalOpen = false;
                        window.dispatchEvent(new CustomEvent('toast', { detail: { text: '{{ __('Cập nhật danh sách tag thành công!') }}', type: 'success' } }));
                    } else {
                        window.dispatchEvent(new CustomEvent('toast', { detail: { text: data.message || '{{ __('Lỗi không xác định!') }}', type: 'error' } }));
                    }
                })
                .catch(err => {
                    this.savingTags = false;
                    window.dispatchEvent(new CustomEvent('toast', { detail: { text: '{{ __('Lỗi kết nối máy chủ!') }}', type: 'error' } }));
                    console.error(err);
                });
            },

            openAddGiftModal() {
                this.isEdit = false;
                this.activeGift = {
                    id: null,
                    title: '',
                    price: 0,
                    stock: 0,
                    type: 'voucher',
                    tag: '',
                    status: 1,
                    image: '',
                    description: ''
                };
                this.giftModalOpen = true;
                setTimeout(() => { if (window.lucide) window.lucide.createIcons(); }, 50);
            },

            openEditGiftModal(gift) {
                this.isEdit = true;
                this.activeGift = { ...gift };
                // Chuyển kiểu dữ liệu sang String/Int để bind với form select chuẩn
                this.activeGift.status = gift.status ? 1 : 0;
                this.giftModalOpen = true;
                setTimeout(() => { if (window.lucide) window.lucide.createIcons(); }, 50);
            },

            openDetailModal(redemption) {
                this.activeRedemption = { ...redemption };
                if (typeof this.activeRedemption.shipping_info === 'string') {
                    try {
                        this.activeRedemption.shipping_info = JSON.parse(this.activeRedemption.shipping_info);
                    } catch(e) {
                        this.activeRedemption.shipping_info = {};
                    }
                }
                this.actionType = 'approve';
                this.editMode = false;
                this.detailModalOpen = true;
                setTimeout(() => { if (window.lucide) window.lucide.createIcons(); }, 50);
            },

            openBulkEditModal() {
                this.bulkFields = {
                    type: false,
                    tag: false,
                    status: false,
                    stock: false
                };
                this.bulkData = {
                    type: 'voucher',
                    tag: '',
                    status: 1,
                    stock: 0
                };
                this.bulkEditModalOpen = true;
                setTimeout(() => { if (window.lucide) window.lucide.createIcons(); }, 50);
            },

            openBulkDeleteModal() {
                this.confirmBulkText = '';
                this.bulkDeleteModalOpen = true;
                setTimeout(() => { if (window.lucide) window.lucide.createIcons(); }, 50);
            },

            async submitBulkEdit() {
                if (this.isSubmitting) return;
                
                // Kiểm tra xem có chọn ít nhất một trường hay không
                const hasSelectedField = Object.values(this.bulkFields).some(v => v);
                if (!hasSelectedField) {
                    Swal.fire({
                        icon: 'error',
                        title: '{{ __("Lỗi dữ liệu") }}',
                        text: '{{ __("Vui lòng chọn ít nhất một trường để cập nhật hàng loạt.") }}'
                    });
                    return;
                }

                this.isSubmitting = true;
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

                try {
                    const response = await fetch('{{ route("admin.gifts.bulk_update") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            gift_ids: this.selectedGifts.join(','),
                            fields: this.bulkFields,
                            data: this.bulkData
                        })
                    });

                    const data = await response.json();

                    if (data.status === 'success') {
                        this.bulkEditModalOpen = false;
                        this.selectedGifts = [];
                        
                        await Swal.fire({
                            icon: 'success',
                            title: '{{ __("Thành công") }}',
                            text: data.message,
                            timer: 2000,
                            showConfirmButton: false
                        });

                        window.location.reload();
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: '{{ __("Thất bại") }}',
                            text: data.message || '{{ __("Có lỗi xảy ra.") }}'
                        });
                    }
                } catch (error) {
                    Swal.fire({
                        icon: 'error',
                        title: '{{ __("Lỗi hệ thống") }}',
                        text: '{{ __("Không thể kết nối đến máy chủ. Vui lòng thử lại sau.") }}'
                    });
                } finally {
                    this.isSubmitting = false;
                }
            },

            async submitBulkDelete() {
                if (this.isSubmitting) return;

                if (this.confirmBulkText.toUpperCase() !== 'XÓA HÀNG LOẠT') {
                    Swal.fire({
                        icon: 'error',
                        title: '{{ __("Lỗi kiểm tra dữ liệu") }}',
                        text: '{{ __("Vui lòng nhập chính xác từ khóa \"XÓA HÀNG LOẠT\" để xác nhận.") }}'
                    });
                    return;
                }

                this.isSubmitting = true;
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

                try {
                    // Gọi thẳng phương thức DELETE đúng như khai báo của route.
                    // Lưu ý: KHÔNG dùng POST kèm _method trong body JSON vì Laravel chỉ đọc _method
                    // từ dữ liệu form-encoded hoặc query string, dẫn đến request bị trả về lỗi 405.
                    const response = await fetch('{{ route("admin.gifts.bulk_destroy") }}', {
                        method: 'DELETE',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            gift_ids: this.selectedGifts.join(','),
                            confirm_text: this.confirmBulkText
                        })
                    });

                    const data = await response.json();

                    if (data.status === 'success') {
                        this.bulkDeleteModalOpen = false;
                        this.confirmBulkText = '';
                        this.selectedGifts = [];

                        await Swal.fire({
                            icon: 'success',
                            title: '{{ __("Thành công") }}',
                            text: data.message,
                            timer: 2000,
                            showConfirmButton: false
                        });

                        window.location.reload();
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: '{{ __("Thất bại") }}',
                            text: data.message || '{{ __("Không thể thực hiện xóa hàng loạt.") }}'
                        });
                    }
                } catch (error) {
                    Swal.fire({
                        icon: 'error',
                        title: '{{ __("Lỗi hệ thống") }}',
                        text: '{{ __("Không thể kết nối đến máy chủ. Vui lòng kiểm tra lại.") }}'
                    });
                } finally {
                    this.isSubmitting = false;
                }
            }
        }
    }
</script>
@endsection
