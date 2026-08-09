@extends('layouts.admin')

@section('title', __('Quản Lý Mã Giảm Giá') . ' - ' . $siteName)

@php
    $hasFilter = request('search') || request('state') || (request('platform') && request('platform') !== 'all') || (request('limit') && request('limit') != 20);
    $couponStatus = \App\Models\Setting::getVal('coupon_status', '1');
    $couponAutoSync = \App\Models\Setting::getVal('coupon_auto_sync', '1');
@endphp

@section('content')
<div class="space-y-6" x-data="couponHandler(@json($coupons->pluck('id')))">

    <!-- Tiêu đề trang -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white uppercase tracking-tight flex items-center gap-2">
                <span class="w-1.5 h-6 rounded-full bg-gradient-to-b from-shopee to-shopee-light shrink-0"></span>
                {{ __('Quản Lý Mã Giảm Giá') }}
            </h1>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                {{ __('Tự thêm, chỉnh sửa mã giảm giá theo ý muốn. Mã tự nhập sẽ không bị dữ liệu đồng bộ từ API ghi đè.') }}
            </p>
        </div>
        <div class="flex items-center gap-2">
            <button @click="showFilter = !showFilter"
                    class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-semibold rounded-xl border border-gray-200 dark:border-slate-800 transition-all shadow-sm"
                    :class="showFilter ? 'bg-shopee text-white border-shopee hover:bg-shopee-dark' : 'bg-white dark:bg-slate-900 hover:bg-gray-50 text-gray-700 dark:text-slate-350'">
                <i data-lucide="filter" class="w-4 h-4"></i>
                <span>{{ __('Bộ lọc') }}</span>
                @if($hasFilter)<span class="w-1.5 h-1.5 rounded-full bg-red-500 animate-pulse"></span>@endif
            </button>

            <a href="{{ route('admin.settings.index', ['tab' => 'coupons']) }}"
               class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-semibold text-gray-700 bg-white hover:bg-gray-50 border border-gray-200 dark:border-slate-800 dark:bg-slate-900 dark:text-gray-300 dark:hover:bg-slate-800 rounded-xl transition-all shadow-sm">
                <i data-lucide="settings" class="w-4 h-4"></i>
                {{ __('Cấu hình') }}
            </a>

            <button @click="openAdd()"
                    class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-semibold text-white bg-shopee hover:bg-shopee-dark rounded-xl transition-all shadow-md">
                <i data-lucide="plus" class="w-4 h-4"></i>
                {{ __('Thêm Mã Mới') }}
            </button>
        </div>
    </div>

    <!-- Cảnh báo trạng thái hiển thị & đồng bộ -->
    @if($couponStatus !== '1')
        <div class="flex items-start gap-2.5 p-3.5 bg-amber-50 dark:bg-amber-950/20 border border-amber-200/60 dark:border-amber-900/50 rounded-2xl text-xs text-amber-700 dark:text-amber-400">
            <i data-lucide="alert-triangle" class="w-4 h-4 shrink-0 mt-0.5"></i>
            <span>{{ __('Chức năng mã giảm giá đang TẮT nên trang mã giảm giá ngoài trang khách sẽ không hiển thị. Bật lại tại') }}
                <a href="{{ route('admin.settings.index', ['tab' => 'coupons']) }}" class="font-bold underline">{{ __('Cấu hình mã giảm giá') }}</a>.</span>
        </div>
    @elseif($couponAutoSync !== '1')
        <div class="flex items-start gap-2.5 p-3.5 bg-blue-50 dark:bg-blue-950/20 border border-blue-100 dark:border-blue-900/50 rounded-2xl text-xs text-blue-700 dark:text-blue-400">
            <i data-lucide="info" class="w-4 h-4 shrink-0 mt-0.5"></i>
            <span>{{ __('Tự động đồng bộ mã giảm giá từ API đang TẮT. Hệ thống chỉ hiển thị các mã bạn tự quản lý tại đây.') }}</span>
        </div>
    @endif

    <!-- Thẻ thống kê nhanh -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
        <div class="bg-white dark:bg-slate-900 p-4 rounded-2xl border border-gray-100 dark:border-slate-800 shadow-sm">
            <div class="flex items-center justify-between mb-2">
                <span class="text-[10px] font-bold uppercase tracking-wider text-gray-400 dark:text-slate-500">{{ __('Tổng số mã') }}</span>
                <div class="p-1.5 bg-shopee/10 rounded-lg"><i data-lucide="ticket" class="w-4 h-4 text-shopee"></i></div>
            </div>
            <p class="text-xl font-extrabold text-gray-900 dark:text-white">{{ number_format($stats['total']) }}</p>
        </div>
        <div class="bg-white dark:bg-slate-900 p-4 rounded-2xl border border-gray-100 dark:border-slate-800 shadow-sm">
            <div class="flex items-center justify-between mb-2">
                <span class="text-[10px] font-bold uppercase tracking-wider text-gray-400 dark:text-slate-500">{{ __('Còn hiệu lực') }}</span>
                <div class="p-1.5 bg-green-50 dark:bg-green-950/30 rounded-lg"><i data-lucide="badge-check" class="w-4 h-4 text-green-500"></i></div>
            </div>
            <p class="text-xl font-extrabold text-gray-900 dark:text-white">{{ number_format($stats['active']) }}</p>
        </div>
        <div class="bg-white dark:bg-slate-900 p-4 rounded-2xl border border-gray-100 dark:border-slate-800 shadow-sm">
            <div class="flex items-center justify-between mb-2">
                <span class="text-[10px] font-bold uppercase tracking-wider text-gray-400 dark:text-slate-500">{{ __('Mã tự nhập') }}</span>
                <div class="p-1.5 bg-blue-50 dark:bg-blue-950/30 rounded-lg"><i data-lucide="pencil" class="w-4 h-4 text-blue-500"></i></div>
            </div>
            <p class="text-xl font-extrabold text-gray-900 dark:text-white">{{ number_format($stats['manual']) }}</p>
        </div>
        <div class="bg-white dark:bg-slate-900 p-4 rounded-2xl border border-gray-100 dark:border-slate-800 shadow-sm">
            <div class="flex items-center justify-between mb-2">
                <span class="text-[10px] font-bold uppercase tracking-wider text-gray-400 dark:text-slate-500">{{ __('Đã hết hạn') }}</span>
                <div class="p-1.5 bg-red-50 dark:bg-red-950/30 rounded-lg"><i data-lucide="clock-alert" class="w-4 h-4 text-red-500"></i></div>
            </div>
            <p class="text-xl font-extrabold text-gray-900 dark:text-white">{{ number_format($stats['expired']) }}</p>
        </div>
    </div>

    <!-- Bộ lọc -->
    <div x-show="showFilter" x-transition class="bg-white dark:bg-slate-900 rounded-3xl p-4 border border-gray-250/50 dark:border-slate-800/80 shadow-sm" style="display:none">
        <form action="{{ route('admin.coupons.index') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
            <select name="limit" class="block w-full px-3 py-2 border border-gray-200 dark:border-slate-700 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-gray-50/50 dark:bg-slate-950 dark:text-white">
                @foreach([20,50,100,200,500] as $l)
                    <option value="{{ $l }}" {{ request('limit', 20) == $l ? 'selected' : '' }}>{{ __('Hiển thị :n dòng', ['n' => $l]) }}</option>
                @endforeach
            </select>
            <select name="state" class="block w-full px-3 py-2 border border-gray-200 dark:border-slate-700 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-gray-50/50 dark:bg-slate-950 dark:text-white">
                <option value="">{{ __('Tất cả trạng thái') }}</option>
                <option value="active" {{ request('state') === 'active' ? 'selected' : '' }}>{{ __('Còn hiệu lực') }}</option>
                <option value="expired" {{ request('state') === 'expired' ? 'selected' : '' }}>{{ __('Đã hết hạn') }}</option>
                <option value="manual" {{ request('state') === 'manual' ? 'selected' : '' }}>{{ __('Mã tự nhập') }}</option>
                <option value="api" {{ request('state') === 'api' ? 'selected' : '' }}>{{ __('Mã đồng bộ API') }}</option>
            </select>
            <select name="platform" class="block w-full px-3 py-2 border border-gray-200 dark:border-slate-700 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-gray-50/50 dark:bg-slate-950 dark:text-white">
                <option value="all">{{ __('Tất cả sàn') }}</option>
                @foreach($platforms as $p)
                    <option value="{{ $p }}" {{ request('platform') === $p ? 'selected' : '' }}>{{ ucfirst($p) }}</option>
                @endforeach
            </select>
            <div class="relative">
                <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-gray-400"><i data-lucide="search" class="w-4 h-4"></i></span>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="{{ __('Mã, tiêu đề, mô tả...') }}" class="block w-full pl-9 pr-4 py-2 border border-gray-200 dark:border-slate-700 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-gray-50/50 dark:bg-slate-950 dark:text-white">
            </div>
            <div class="flex gap-2">
                <button type="submit" class="flex-grow px-5 py-2 text-xs font-semibold text-white bg-shopee hover:bg-shopee-dark rounded-xl transition-all shadow-md">{{ __('Lọc') }}</button>
                @if($hasFilter)
                    <a href="{{ route('admin.coupons.index') }}" class="inline-flex items-center justify-center px-4 py-2 text-xs font-semibold text-gray-500 bg-gray-100 dark:bg-slate-800 hover:bg-gray-200 dark:hover:bg-slate-700 rounded-xl transition-all gap-1"><i data-lucide="x" class="w-3.5 h-3.5"></i></a>
                @endif
            </div>
        </form>
    </div>

    <!-- Thanh hành động hàng loạt -->
    <div x-show="selected.length > 0" x-transition class="flex items-center justify-between gap-3 bg-shopee/5 dark:bg-shopee/10 border border-shopee/20 rounded-2xl px-4 py-2.5" style="display:none">
        <span class="text-xs font-semibold text-shopee" x-text="'{{ __('Đã chọn') }} ' + selected.length + ' {{ __('mã') }}'"></span>
        <button @click="openBulkDelete()" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-white bg-red-600 hover:bg-red-700 rounded-lg transition-all shadow-sm">
            <i data-lucide="trash-2" class="w-3.5 h-3.5"></i> {{ __('Xóa đã chọn') }}
        </button>
    </div>

    @if($coupons->isEmpty())
        <div class="bg-white dark:bg-slate-900 rounded-3xl border border-gray-100 dark:border-slate-800 py-16 text-center">
            <div class="w-16 h-16 mx-auto mb-3 rounded-2xl bg-gray-50 dark:bg-slate-800 flex items-center justify-center">
                <i data-lucide="ticket" class="w-7 h-7 text-gray-300 dark:text-slate-600"></i>
            </div>
            <p class="text-sm font-semibold text-gray-500 dark:text-slate-400">{{ __('Chưa có mã giảm giá nào.') }}</p>
            <button @click="openAdd()" class="mt-4 inline-flex items-center gap-1.5 px-4 py-2 text-xs font-semibold text-white bg-shopee hover:bg-shopee-dark rounded-xl transition-all shadow-md">
                <i data-lucide="plus" class="w-4 h-4"></i> {{ __('Tạo mã đầu tiên') }}
            </button>
        </div>
    @else
        <!-- Bảng Desktop -->
        <div class="hidden md:block bg-white dark:bg-slate-900 rounded-3xl border border-gray-100 dark:border-slate-800 shadow-sm overflow-hidden">
            <table class="w-full text-left">
                <thead class="bg-gray-50/70 dark:bg-slate-800/40 text-[10px] uppercase tracking-wider text-gray-500 dark:text-slate-400">
                    <tr>
                        <th class="px-4 py-3 w-10">
                            <input type="checkbox" @change="toggleAll($event)" :checked="allChecked" class="rounded border-gray-300 text-shopee focus:ring-shopee/30 w-4 h-4">
                        </th>
                        <th class="px-4 py-3 font-bold">{{ __('Mã / Tiêu đề') }}</th>
                        <th class="px-4 py-3 font-bold">{{ __('Ưu đãi') }}</th>
                        <th class="px-4 py-3 font-bold text-center">{{ __('Nguồn') }}</th>
                        <th class="px-4 py-3 font-bold">{{ __('Hạn dùng') }}</th>
                        <th class="px-4 py-3 font-bold text-right">{{ __('Thao tác') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-slate-800 text-xs">
                    @foreach($coupons as $coupon)
                        @php
                            $isBanner = str_starts_with($coupon->code, 'BANNER_');
                            $isManual = $coupon->source === 'manual';
                            $isExpired = $coupon->expired_at && $coupon->expired_at->isPast();
                        @endphp
                        <tr class="hover:bg-gray-50/50 dark:hover:bg-slate-800/30">
                            <td class="px-4 py-3">
                                <input type="checkbox" value="{{ $coupon->id }}" x-model="selected" class="rounded border-gray-300 text-shopee focus:ring-shopee/30 w-4 h-4">
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-xl bg-gray-100 dark:bg-slate-800 overflow-hidden shrink-0 flex items-center justify-center">
                                        @if($coupon->image_url)
                                            <img src="{{ $coupon->image_url }}" alt="" class="w-full h-full object-cover" onerror="this.style.display='none'">
                                        @else
                                            <i data-lucide="ticket-percent" class="w-4 h-4 text-gray-400"></i>
                                        @endif
                                    </div>
                                    <div class="min-w-0">
                                        <div class="font-bold text-gray-900 dark:text-white truncate max-w-[240px]">{{ $coupon->title }}</div>
                                        @if($isBanner)
                                            <span class="text-[10px] text-gray-400">{{ __('Banner (không có mã)') }}</span>
                                        @else
                                            <span class="font-mono font-bold text-shopee text-[11px]">{{ $coupon->code }}</span>
                                        @endif
                                        @if($coupon->category)
                                            <span class="text-[10px] text-gray-400"> · {{ $coupon->category }}</span>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                @if($coupon->discount_percentage > 0)
                                    <span class="font-bold text-green-600 dark:text-green-400">-{{ $coupon->discount_percentage }}%</span>
                                @elseif($coupon->discount_amount > 0)
                                    <span class="font-bold text-green-600 dark:text-green-400">-{{ number_format($coupon->discount_amount, 0, ',', '.') }}đ</span>
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                                @if($coupon->min_spend > 0)
                                    <div class="text-[10px] text-gray-400">{{ __('Đơn tối thiểu:') }} {{ number_format($coupon->min_spend, 0, ',', '.') }}đ</div>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center">
                                @if($isManual)
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-blue-50 text-blue-600 dark:bg-blue-950/30 dark:text-blue-400 border border-blue-200/50 dark:border-blue-900/50 rounded-full text-[10px] font-bold">
                                        <i data-lucide="pencil" class="w-3 h-3"></i> {{ __('Tự nhập') }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-gray-100 text-gray-500 dark:bg-slate-800 dark:text-slate-400 border border-gray-200/60 dark:border-slate-700 rounded-full text-[10px] font-bold">
                                        <i data-lucide="cloud-download" class="w-3 h-3"></i> API
                                    </span>
                                @endif
                                <div class="text-[10px] text-gray-400 mt-0.5">{{ ucfirst($coupon->platform) }}</div>
                            </td>
                            <td class="px-4 py-3">
                                @if($coupon->expired_at)
                                    <span class="{{ $isExpired ? 'text-red-500 font-semibold' : 'text-gray-600 dark:text-slate-400' }}">{{ $coupon->expired_at->format('H:i d/m/Y') }}</span>
                                    @if($isExpired)<div class="text-[10px] text-red-400">{{ __('Đã hết hạn') }}</div>@endif
                                @else
                                    <span class="text-gray-400">{{ __('Không giới hạn') }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-end gap-1.5">
                                    <button @click="openEdit(@js($coupon->only(['id','platform','code','title','description','category','min_spend','discount_amount','discount_percentage','redirect_link','image_url'])), '{{ $coupon->expired_at ? $coupon->expired_at->format('Y-m-d\TH:i') : '' }}', {{ $isBanner ? 'true' : 'false' }})"
                                            class="p-1.5 text-gray-500 hover:text-shopee hover:bg-shopee/10 rounded-lg transition-all" title="{{ __('Sửa') }}">
                                        <i data-lucide="pencil" class="w-4 h-4"></i>
                                    </button>
                                    <button @click="openDelete('{{ $coupon->id }}', @js($coupon->title))"
                                            class="p-1.5 text-gray-500 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-950/30 rounded-lg transition-all" title="{{ __('Xóa') }}">
                                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Card Mobile -->
        <div class="md:hidden space-y-3">
            @foreach($coupons as $coupon)
                @php
                    $isBanner = str_starts_with($coupon->code, 'BANNER_');
                    $isManual = $coupon->source === 'manual';
                    $isExpired = $coupon->expired_at && $coupon->expired_at->isPast();
                @endphp
                <div class="bg-white dark:bg-slate-900 rounded-3xl border border-gray-100 dark:border-slate-800 p-4 shadow-sm">
                    <div class="flex items-start gap-3">
                        <input type="checkbox" value="{{ $coupon->id }}" x-model="selected" class="mt-1 rounded border-gray-300 text-shopee focus:ring-shopee/30 w-4 h-4">
                        <div class="w-11 h-11 rounded-xl bg-gray-100 dark:bg-slate-800 overflow-hidden shrink-0 flex items-center justify-center">
                            @if($coupon->image_url)
                                <img src="{{ $coupon->image_url }}" alt="" class="w-full h-full object-cover" onerror="this.style.display='none'">
                            @else
                                <i data-lucide="ticket-percent" class="w-5 h-5 text-gray-400"></i>
                            @endif
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="font-bold text-gray-900 dark:text-white text-sm leading-snug">{{ $coupon->title }}</div>
                            <div class="mt-0.5 flex flex-wrap items-center gap-x-2 gap-y-1">
                                @if($isBanner)
                                    <span class="text-[10px] text-gray-400">{{ __('Banner (không có mã)') }}</span>
                                @else
                                    <span class="font-mono font-bold text-shopee text-[11px]">{{ $coupon->code }}</span>
                                @endif
                                @if($isManual)
                                    <span class="inline-flex items-center gap-1 px-1.5 py-0.5 bg-blue-50 text-blue-600 dark:bg-blue-950/30 dark:text-blue-400 rounded-full text-[9px] font-bold">{{ __('Tự nhập') }}</span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-1.5 py-0.5 bg-gray-100 text-gray-500 dark:bg-slate-800 dark:text-slate-400 rounded-full text-[9px] font-bold">API</span>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="mt-3 pt-3 border-t border-gray-100 dark:border-slate-800 flex items-center justify-between text-[11px]">
                        <div>
                            @if($coupon->discount_percentage > 0)
                                <span class="font-bold text-green-600 dark:text-green-400">-{{ $coupon->discount_percentage }}%</span>
                            @elseif($coupon->discount_amount > 0)
                                <span class="font-bold text-green-600 dark:text-green-400">-{{ number_format($coupon->discount_amount, 0, ',', '.') }}đ</span>
                            @endif
                            <span class="text-gray-400 ml-1">
                                @if($coupon->expired_at)
                                    · {{ $isExpired ? __('Hết hạn') : 'HSD ' . $coupon->expired_at->format('d/m/Y') }}
                                @else
                                    · {{ __('Không giới hạn') }}
                                @endif
                            </span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <button @click="openEdit(@js($coupon->only(['id','platform','code','title','description','category','min_spend','discount_amount','discount_percentage','redirect_link','image_url'])), '{{ $coupon->expired_at ? $coupon->expired_at->format('Y-m-d\TH:i') : '' }}', {{ $isBanner ? 'true' : 'false' }})"
                                    class="p-1.5 text-gray-500 hover:text-shopee hover:bg-shopee/10 rounded-lg transition-all">
                                <i data-lucide="pencil" class="w-4 h-4"></i>
                            </button>
                            <button @click="openDelete('{{ $coupon->id }}', @js($coupon->title))"
                                    class="p-1.5 text-gray-500 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-950/30 rounded-lg transition-all">
                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                            </button>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div>{{ $coupons->links() }}</div>
    @endif

    <!-- ============ MODAL THÊM / SỬA ============ -->
    {{-- Nghiệp vụ: Sử dụng <template x-teleport="body"> để đưa modal ra ngoài stacking context của layout admin chính,
         giúp lớp phủ backdrop (bg-black/60) và hiệu ứng blur (backdrop-blur-sm) che phủ toàn bộ màn hình (bao gồm cả sidebar và header topbar). --}}
    <template x-teleport="body">
        <div x-show="showFormModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto flex items-start sm:items-center justify-center p-4 bg-black/60 backdrop-blur-sm"
             x-transition.opacity>
            <div @click.away="showFormModal = false" x-show="showFormModal"
                 x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 scale-95 translate-y-4" x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                 class="bg-white dark:bg-slate-900 border border-gray-150 dark:border-slate-800 rounded-3xl max-w-2xl w-full shadow-2xl my-8">
                <form :action="formAction" method="POST" autocomplete="off">
                    @csrf
                    <input type="hidden" name="_method" :value="formMode === 'edit' ? 'PUT' : 'POST'">

                    <!-- Header -->
                    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 dark:border-slate-800">
                        <h3 class="text-sm font-bold text-gray-900 dark:text-white flex items-center gap-2">
                            <i data-lucide="ticket-percent" class="w-5 h-5 text-shopee"></i>
                            <span x-text="formMode === 'edit' ? '{{ __('Chỉnh sửa mã giảm giá') }}' : '{{ __('Thêm mã giảm giá mới') }}'"></span>
                        </h3>
                        <button type="button" @click="showFormModal = false" class="w-7 h-7 flex items-center justify-center rounded-full bg-gray-50 dark:bg-slate-800 text-gray-400 hover:text-gray-600 transition-colors">
                            <i data-lucide="x" class="w-4 h-4"></i>
                        </button>
                    </div>

                    <!-- Body -->
                    <div class="p-6 space-y-4 max-h-[70vh] overflow-y-auto">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <!-- Tiêu đề -->
                            <div class="sm:col-span-2">
                                <label class="block text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider mb-1">{{ __('Tiêu đề ưu đãi') }} <span class="text-red-500">*</span></label>
                                <input type="text" name="title" x-model="form.title" required maxlength="255"
                                       placeholder="{{ __('Ví dụ: Giảm 50K cho đơn từ 250K') }}"
                                       class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-800 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-850 text-gray-700 dark:text-slate-300">
                            </div>

                            <!-- Mã giảm giá -->
                            <div>
                                <label class="block text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider mb-1">{{ __('Mã giảm giá') }}</label>
                                <input type="text" name="code" x-model="form.code" maxlength="100" :disabled="isBanner"
                                       placeholder="{{ __('Ví dụ: SALE50K') }}"
                                       class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-800 rounded-xl text-xs font-mono focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-850 text-gray-700 dark:text-slate-300 disabled:bg-gray-100 disabled:text-gray-400 dark:disabled:bg-slate-800">
                                <label class="flex items-center gap-1.5 mt-1.5 text-[10px] text-gray-500 dark:text-slate-400 cursor-pointer">
                                    <input type="checkbox" x-model="isBanner" @change="if(isBanner) form.code=''" class="rounded border-gray-300 text-shopee focus:ring-shopee/30 w-3.5 h-3.5">
                                    {{ __('Chỉ là banner ưu đãi (không có mã để copy)') }}
                                </label>
                            </div>

                            <!-- Sàn -->
                            <div>
                                <label class="block text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider mb-1">{{ __('Sàn áp dụng') }}</label>
                                <select name="platform" x-model="form.platform"
                                        class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-800 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-850 text-gray-700 dark:text-slate-300">
                                    <option value="shopee">Shopee</option>
                                    <option value="lazada">Lazada</option>
                                    <option value="tiktok">TikTok Shop</option>
                                    <option value="other">{{ __('Khác') }}</option>
                                </select>
                                <span class="text-[9px] text-gray-400 mt-1 block">{{ __('Trang mã giảm giá ngoài trang khách chỉ hiển thị các mã của sàn Shopee.') }}</span>
                            </div>

                            <!-- Danh mục -->
                            <div>
                                <label class="block text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider mb-1">{{ __('Danh mục') }}</label>
                                <input type="text" name="category" x-model="form.category" maxlength="255"
                                       placeholder="{{ __('Ví dụ: Freeship, Toàn sàn...') }}"
                                       class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-800 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-850 text-gray-700 dark:text-slate-300">
                            </div>

                            <!-- Hạn dùng -->
                            <div>
                                <label class="block text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider mb-1">{{ __('Hạn sử dụng') }}</label>
                                <input type="datetime-local" name="expired_at" x-model="form.expired_at"
                                       class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-800 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-850 text-gray-700 dark:text-slate-300">
                                <span class="text-[9px] text-gray-400 mt-1 block">{{ __('Bỏ trống nếu không giới hạn thời gian.') }}</span>
                            </div>

                            <!-- Số tiền giảm -->
                            <div>
                                <label class="block text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider mb-1">{{ __('Số tiền giảm (đ)') }}</label>
                                <input type="number" name="discount_amount" x-model="form.discount_amount" min="0"
                                       placeholder="0"
                                       class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-800 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-850 text-gray-700 dark:text-slate-300">
                            </div>

                            <!-- % giảm -->
                            <div>
                                <label class="block text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider mb-1">{{ __('Phần trăm giảm (%)') }}</label>
                                <input type="number" name="discount_percentage" x-model="form.discount_percentage" min="0" max="100"
                                       placeholder="0"
                                       class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-800 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-850 text-gray-700 dark:text-slate-300">
                            </div>

                            <!-- Đơn tối thiểu -->
                            <div>
                                <label class="block text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider mb-1">{{ __('Đơn tối thiểu (đ)') }}</label>
                                <input type="number" name="min_spend" x-model="form.min_spend" min="0"
                                       placeholder="0"
                                       class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-800 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-850 text-gray-700 dark:text-slate-300">
                            </div>

                            <!-- Ảnh đại diện -->
                            <div>
                                <label class="block text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider mb-1">{{ __('URL ảnh đại diện') }}</label>
                                <input type="url" name="image_url" x-model="form.image_url" maxlength="2000"
                                       placeholder="https://..."
                                       class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-800 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-850 text-gray-700 dark:text-slate-300">
                            </div>

                            <!-- Link chuyển hướng -->
                            <div class="sm:col-span-2">
                                <label class="block text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider mb-1">{{ __('Đường dẫn chuyển hướng (Affiliate)') }}</label>
                                <input type="url" name="redirect_link" x-model="form.redirect_link" maxlength="2000"
                                       placeholder="{{ __('Ví dụ: https://shope.ee/... link tiếp thị của bạn') }}"
                                       class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-800 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-850 text-gray-700 dark:text-slate-300">
                            </div>

                            <!-- Mô tả -->
                            <div class="sm:col-span-2">
                                <label class="block text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider mb-1">{{ __('Mô tả / Điều kiện áp dụng') }}</label>
                                <textarea name="description" x-model="form.description" rows="3" maxlength="5000"
                                          placeholder="{{ __('Nhập điều kiện, hướng dẫn sử dụng mã...') }}"
                                          class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-800 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-850 text-gray-700 dark:text-slate-300"></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-gray-100 dark:border-slate-800">
                        <button type="button" @click="showFormModal = false" class="px-5 py-2.5 text-xs font-bold text-gray-600 dark:text-slate-300 bg-gray-100 dark:bg-slate-800 hover:bg-gray-200 dark:hover:bg-slate-700 rounded-xl transition-all">{{ __('Hủy') }}</button>
                        <button type="submit" class="px-5 py-2.5 text-xs font-bold text-white bg-shopee hover:bg-shopee-dark rounded-xl transition-all shadow-md flex items-center gap-1.5">
                            <i data-lucide="check" class="w-4 h-4"></i>
                            <span x-text="formMode === 'edit' ? '{{ __('Lưu thay đổi') }}' : '{{ __('Thêm mã') }}'"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </template>

    <!-- ============ MODAL XÓA ĐƠN ============ -->
    {{-- Nghiệp vụ: Đưa modal xác nhận xóa đơn ra ngoài body sử dụng x-teleport tương tự modal thêm/sửa,
         giúp backdrop tối phủ mờ toàn diện các thành phần cố định (sticky/fixed) khác. --}}
    <template x-teleport="body">
        <div x-show="showDeleteModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm" x-transition.opacity>
            <div @click.away="showDeleteModal = false" x-show="showDeleteModal"
                 x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                 class="bg-white dark:bg-slate-900 border border-gray-150 dark:border-slate-800 rounded-3xl max-w-sm w-full shadow-2xl p-6 text-center">
                <div class="w-14 h-14 mx-auto mb-4 rounded-2xl bg-red-50 dark:bg-red-950/30 flex items-center justify-center">
                    <i data-lucide="trash-2" class="w-7 h-7 text-red-500"></i>
                </div>
                <h3 class="text-sm font-bold text-gray-900 dark:text-white">{{ __('Xóa mã giảm giá?') }}</h3>
                <p class="text-xs text-gray-500 dark:text-slate-400 mt-1.5">{{ __('Bạn có chắc muốn xóa') }} "<span class="font-semibold text-gray-700 dark:text-slate-300" x-text="deleteTarget.title"></span>"? {{ __('Hành động này không thể hoàn tác.') }}</p>
                <form :action="deleteTarget.action" method="POST" class="flex items-center gap-3 mt-5">
                    @csrf
                    @method('DELETE')
                    <button type="button" @click="showDeleteModal = false" class="flex-1 py-2.5 text-xs font-bold text-gray-600 dark:text-slate-300 bg-gray-100 dark:bg-slate-800 hover:bg-gray-200 dark:hover:bg-slate-700 rounded-xl transition-all">{{ __('Hủy') }}</button>
                    <button type="submit" class="flex-1 py-2.5 text-xs font-bold text-white bg-red-600 hover:bg-red-700 rounded-xl transition-all shadow-md">{{ __('Xóa') }}</button>
                </form>
            </div>
        </div>
    </template>

    <!-- ============ MODAL XÓA HÀNG LOẠT ============ -->
    {{-- Nghiệp vụ: Sử dụng x-teleport đưa modal xóa hàng loạt ra body để sửa lỗi hiển thị đè backdrop lên layout. --}}
    <template x-teleport="body">
        <div x-show="showBulkModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm" x-transition.opacity>
            <div @click.away="showBulkModal = false" x-show="showBulkModal"
                 x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                 class="bg-white dark:bg-slate-900 border border-gray-150 dark:border-slate-800 rounded-3xl max-w-sm w-full shadow-2xl p-6">
                <div class="w-14 h-14 mx-auto mb-4 rounded-2xl bg-red-50 dark:bg-red-950/30 flex items-center justify-center">
                    <i data-lucide="trash-2" class="w-7 h-7 text-red-500"></i>
                </div>
                <h3 class="text-sm font-bold text-gray-900 dark:text-white text-center">{{ __('Xóa hàng loạt mã giảm giá') }}</h3>
                <p class="text-xs text-gray-500 dark:text-slate-400 mt-1.5 text-center" x-text="'{{ __('Bạn sắp xóa') }} ' + selected.length + ' {{ __('mã. Nhập') }} XÓA HÀNG LOẠT {{ __('để xác nhận.') }}'"></p>
                <input type="text" x-model="bulkConfirmText" placeholder="XÓA HÀNG LOẠT" class="block w-full mt-3 px-4 py-2.5 border border-gray-200 dark:border-slate-800 rounded-xl text-xs text-center font-bold focus:outline-none focus:ring-2 focus:ring-red-500/20 focus:border-red-500 bg-white dark:bg-slate-850 text-gray-700 dark:text-slate-300">
                <div class="flex items-center gap-3 mt-5">
                    <button type="button" @click="showBulkModal = false" class="flex-1 py-2.5 text-xs font-bold text-gray-600 dark:text-slate-300 bg-gray-100 dark:bg-slate-800 hover:bg-gray-200 dark:hover:bg-slate-700 rounded-xl transition-all">{{ __('Hủy') }}</button>
                    <button type="button" @click="submitBulkDelete()" class="flex-1 py-2.5 text-xs font-bold text-white bg-red-600 hover:bg-red-700 rounded-xl transition-all shadow-md">{{ __('Xóa đã chọn') }}</button>
                </div>
            </div>
        </div>
    </template>
</div>
@endsection

@section('scripts')
<script>
    function couponHandler(visibleIds) {
        return {
            visibleIds: visibleIds || [],
            selected: [],
            showFilter: {{ $hasFilter ? 'true' : 'false' }},

            // Modal thêm/sửa
            showFormModal: false,
            formMode: 'add',
            formAction: '',
            isBanner: false,
            form: {},

            // Modal xóa
            showDeleteModal: false,
            deleteTarget: { title: '', action: '' },
            showBulkModal: false,
            bulkConfirmText: '',

            get allChecked() {
                return this.visibleIds.length > 0 && this.selected.length === this.visibleIds.length;
            },

            toggleAll(e) {
                this.selected = e.target.checked ? this.visibleIds.map(String) : [];
            },

            defaultForm() {
                return {
                    platform: 'shopee', code: '', title: '', description: '', category: '',
                    min_spend: '', discount_amount: '', discount_percentage: '',
                    expired_at: '', redirect_link: '', image_url: ''
                };
            },

            openAdd() {
                this.formMode = 'add';
                this.formAction = '{{ route('admin.coupons.store') }}';
                this.form = this.defaultForm();
                this.isBanner = false;
                this.showFormModal = true;
                this.$nextTick(() => window.lucide && lucide.createIcons());
            },

            openEdit(data, expiredLocal, isBanner) {
                this.formMode = 'edit';
                this.formAction = '{{ route('admin.coupons.update', ['coupon' => '__ID__']) }}'.replace('__ID__', data.id);
                this.form = Object.assign(this.defaultForm(), data, { expired_at: expiredLocal || '' });
                this.isBanner = !!isBanner;
                this.showFormModal = true;
                this.$nextTick(() => window.lucide && lucide.createIcons());
            },

            openDelete(id, title) {
                this.deleteTarget = {
                    title: title,
                    action: '{{ route('admin.coupons.destroy', ['coupon' => '__ID__']) }}'.replace('__ID__', id)
                };
                this.showDeleteModal = true;
            },

            openBulkDelete() {
                this.bulkConfirmText = '';
                this.showBulkModal = true;
            },

            submitBulkDelete() {
                if (this.bulkConfirmText.trim().toUpperCase() !== 'XÓA HÀNG LOẠT') {
                    window.dispatchEvent(new CustomEvent('toast', { detail: { text: '{{ __('Từ khóa xác nhận không chính xác.') }}', type: 'error' } }));
                    return;
                }
                fetch('{{ route('admin.coupons.bulk_destroy') }}', {
                    method: 'DELETE',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'X-Requested-With': 'XMLHttpRequest' },
                    body: JSON.stringify({ ids: this.selected.join(','), confirm_text: this.bulkConfirmText })
                })
                .then(r => r.json())
                .then(d => {
                    window.dispatchEvent(new CustomEvent('toast', { detail: { text: d.message, type: d.status === 'success' ? 'success' : 'error' } }));
                    if (d.status === 'success') setTimeout(() => window.location.reload(), 800);
                });
            }
        };
    }
</script>
@endsection
