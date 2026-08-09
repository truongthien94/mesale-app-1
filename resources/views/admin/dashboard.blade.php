@extends('layouts.admin')

@section('title', __('Admin Dashboard') . ' - ' . $siteName)

@section('content')
@php
    $cronLastRun = \App\Models\Setting::getVal('schedule_last_run');
    if ($cronLastRun) {
        $lastRunTime = \Carbon\Carbon::parse($cronLastRun);
        $minutesAgo = (int) $lastRunTime->diffInMinutes(now());
        $cronActive = $minutesAgo <= 5;
        $cronTimeLabel = $lastRunTime->diffForHumans();
    } else {
        $cronActive = false;
        $cronTimeLabel = null;
    }
@endphp
<div class="space-y-8">
    <!-- Tiêu đề -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ __('Trang Chủ Quản Trị') }}</h1>
            <p class="text-sm text-gray-500 dark:text-slate-400">{{ __('Xem báo cáo doanh thu, hoa hồng tiếp thị và phê duyệt yêu cầu của thành viên') }}</p>
        </div>
        <div class="flex items-center gap-3">
            <button @click="$dispatch('open-widget-modal')" class="inline-flex items-center gap-2 bg-white hover:bg-gray-50 text-gray-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700 border border-gray-200 dark:border-slate-700 font-bold px-4 py-2 rounded-xl shadow-sm hover:shadow transition-all duration-200 text-xs tracking-wider">
                <i data-lucide="layout-grid" class="w-3.5 h-3.5 text-indigo-500"></i>
                {{ __('BỐ CỤC DASHBOARD') }}
            </button>
        </div>
    </div>

    <!-- Cảnh báo Cron Job quan trọng cho Admin -->
    @if(!$cronActive)
    <div class="bg-red-50 dark:bg-red-950/20 border-l-4 border-red-500 dark:border-red-600 p-4 rounded-xl flex items-start gap-3 shadow-sm">
        <div class="p-2 bg-red-100 dark:bg-red-900/40 text-red-600 dark:text-red-400 rounded-lg shrink-0">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 animate-pulse text-red-600 dark:text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
            </svg>
        </div>
        <div class="flex-grow space-y-1">
            <h4 class="text-sm font-bold text-red-800 dark:text-red-300">
                {{ __('CẢNH BÁO: HỆ THỐNG CHƯA CẤU HÌNH CRON JOB!') }}
            </h4>
            <p class="text-xs text-red-700/85 dark:text-red-400/85 leading-relaxed">
                {{ __('Hệ thống chưa ghi nhận hoạt động của Cron Job (hoặc đã quá hạn 5 phút chưa chạy). Các tính năng quan trọng như tự động cập nhật đơn hàng Shopee/TikTok Shop, cộng tiền hoàn cho thành viên và tính hoa hồng MLM sẽ NGỪNG HOẠT ĐỘNG nếu không cấu hình Cron Job.') }}
            </p>
            <div class="pt-1">
                <a href="{{ route('admin.settings.index', ['tab' => 'cronjobs']) }}" class="inline-flex items-center gap-1.5 text-[11px] font-bold text-red-600 dark:text-red-400 hover:underline">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 17.25v1.007a3 3 0 01-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0115 18.257V17.25m6-12V15a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 15V5.25M19.5 3h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 18h15a2.25 2.25 0 002.25-2.25V5.25A2.25 2.25 0 0019.5 3z" />
                    </svg>
                    {{ __('Nhấp vào đây để lấy câu lệnh cấu hình Cron Job ngay') }}
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                    </svg>
                </a>
            </div>
        </div>
    </div>
    @endif

    {{-- Thông báo ra mắt dịch vụ tạo Bot Hoàn Tiền bằng nick Zalo cá nhân (dùng chung với trang Quản lý Bot) --}}
    @include('admin.components.zalo_bot_notice')

    <!-- ===== TRẠNG THÁI NHANH HỆ THỐNG (Rút gọn - Xem chi tiết tại /system-status) ===== -->
    <div class="flex flex-wrap items-center gap-2">
        {{-- APP_ENV --}}
        @php $appEnv = config('app.env', 'production'); @endphp
        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-[11px] font-bold border
            {{ $appEnv === 'production' ? 'bg-green-50 text-green-700 border-green-200 dark:bg-green-950/30 dark:text-green-400 dark:border-green-800/50' : 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-950/30 dark:text-amber-400 dark:border-amber-800/50' }}">
            <span class="w-2 h-2 rounded-full {{ $appEnv === 'production' ? 'bg-green-500' : 'bg-amber-500' }} animate-pulse"></span>
            {{ strtoupper($appEnv) }}
        </span>

        {{-- APP_DEBUG --}}
        @php $appDebug = config('app.debug'); @endphp
        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-[11px] font-bold border
            {{ $appDebug ? 'bg-red-50 text-red-700 border-red-200 dark:bg-red-950/30 dark:text-red-400 dark:border-red-800/50' : 'bg-green-50 text-green-700 border-green-200 dark:bg-green-950/30 dark:text-green-400 dark:border-green-800/50' }}">
            <i data-lucide="{{ $appDebug ? 'bug' : 'shield-check' }}" class="w-3.5 h-3.5"></i>
            DEBUG: {{ $appDebug ? 'ON' : 'OFF' }}
        </span>

        {{-- Cron Job Status --}}
        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-[11px] font-bold border
            @if(!$cronLastRun)
                bg-gray-50 text-gray-500 border-gray-200 dark:bg-slate-800/50 dark:text-slate-400 dark:border-slate-700
            @elseif($cronActive)
                bg-green-50 text-green-700 border-green-200 dark:bg-green-950/30 dark:text-green-400 dark:border-green-800/50
            @else
                bg-red-50 text-red-700 border-red-200 dark:bg-red-950/30 dark:text-red-400 dark:border-red-800/50
            @endif
        ">
            <i data-lucide="{{ $cronActive ? 'timer' : ($cronLastRun ? 'timer-off' : 'help-circle') }}" class="w-3.5 h-3.5"></i>
            CRON:
            @if(!$cronLastRun)
                {{ __('Chưa cấu hình') }}
            @elseif($cronActive)
                {{ __('Hoạt động') }}
            @else
                {{ __('Ngừng') }}
                <span class="text-[9px] font-medium opacity-75">({{ $cronTimeLabel }})</span>
            @endif
        </span>

        {{-- 
            Cấu hình tự động cập nhật hệ thống (Auto Update Status)
            Business rule: Giúp Admin nhận biết nhanh nếu tính năng tự động cập nhật phiên bản bị tắt, 
            tránh việc hệ thống chạy phiên bản cũ thiếu các bản vá bảo mật quan trọng.
        --}}
        @php
            $autoUpdateStatus = \App\Models\Setting::getVal('auto_update', '1');
        @endphp
        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-[11px] font-bold border 
            {{ $autoUpdateStatus === '1' ? 'bg-green-50 text-green-700 border-green-200 dark:bg-green-950/30 dark:text-green-400 dark:border-green-800/50' : 'bg-red-50 text-red-700 border-red-200 dark:bg-red-950/30 dark:text-red-400 dark:border-red-800/50' }}">
            <span class="w-2 h-2 rounded-full {{ $autoUpdateStatus === '1' ? 'bg-green-500 animate-pulse' : 'bg-red-500' }}"></span>
            {{ __('Cập nhật tự động:') }} {{ $autoUpdateStatus === '1' ? __('Bật') : __('Tắt') }}
        </span>

        {{-- Nút xem chi tiết trang Trạng thái hệ thống --}}
        <a href="{{ route('admin.system_status') }}" class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-xl text-[10px] font-bold text-gray-400 hover:text-shopee border border-transparent hover:border-shopee/20 hover:bg-shopee/5 transition-all">
            <i data-lucide="activity" class="w-3 h-3"></i>
            {{ __('Chi tiết') }}
            <i data-lucide="arrow-right" class="w-3 h-3"></i>
        </a>
    </div>

    <!-- ===== KHỐI CẢNH BÁO CHỜ DUYỆT (Hỗ trợ hiển thị Dark Mode) ===== -->
    @if($pendingCashbackCount > 0 || $pendingWithdrawalCount > 0 || $pendingGiftCount > 0)
    @php
        $widgetCount = ($pendingCashbackCount > 0 ? 1 : 0) + ($pendingWithdrawalCount > 0 ? 1 : 0) + ($pendingGiftCount > 0 ? 1 : 0);
    @endphp
    <div class="grid grid-cols-1 {{ $widgetCount === 2 ? 'md:grid-cols-2' : ($widgetCount >= 3 ? 'md:grid-cols-3' : '') }} gap-4">
        @if($pendingCashbackCount > 0)
        <!-- Thẻ cảnh báo đơn hoàn tiền chờ duyệt - Bổ sung các class dark mode để hiển thị hài hòa trên nền tối -->
        <a href="{{ route('admin.cashback.index', ['status' => 'pending']) }}" class="group relative overflow-hidden bg-gradient-to-r from-amber-50 to-yellow-50 dark:from-amber-950/20 dark:to-yellow-950/20 p-5 rounded-2xl border border-amber-200 dark:border-amber-900/40 hover:border-amber-300 dark:hover:border-amber-800 hover:shadow-md transition-all duration-200">
            <div class="flex items-center gap-4">
                <div class="p-3 bg-amber-100 dark:bg-amber-950/40 text-amber-600 dark:text-amber-400 rounded-xl group-hover:scale-110 transition-transform">
                    <i data-lucide="clock" class="w-6 h-6"></i>
                </div>
                <div>
                    <span class="text-xs font-bold text-amber-500 dark:text-amber-400 uppercase tracking-wider">{{ __('Đơn hoàn tiền chờ duyệt') }}</span>
                    <p class="text-xl font-extrabold text-amber-700 dark:text-amber-300">{{ $pendingCashbackCount }} {{ __('đơn') }} · {{ number_format($pendingCashbackAmount) }}đ</p>
                </div>
            </div>
            <div class="absolute top-3 right-4 text-amber-400 dark:text-amber-500 group-hover:translate-x-1 transition-transform">
                <i data-lucide="arrow-right" class="w-5 h-5"></i>
            </div>
        </a>
        @endif

        @if($pendingWithdrawalCount > 0)
        <!-- Thẻ cảnh báo yêu cầu rút tiền chờ duyệt - Hỗ trợ nền tối và viền hồng đậm đặc trưng trên giao diện tối -->
        <a href="{{ route('admin.withdrawals.index', ['status' => 'pending']) }}" class="group relative overflow-hidden bg-gradient-to-r from-rose-50 to-pink-50 dark:from-rose-950/20 dark:to-pink-950/20 p-5 rounded-2xl border border-rose-200 dark:border-rose-900/40 hover:border-rose-300 dark:hover:border-rose-800 hover:shadow-md transition-all duration-200">
            <div class="flex items-center gap-4">
                <div class="p-3 bg-rose-100 dark:bg-rose-950/40 text-rose-600 dark:text-rose-400 rounded-xl group-hover:scale-110 transition-transform">
                    <i data-lucide="banknote" class="w-6 h-6"></i>
                </div>
                <div>
                    <span class="text-xs font-bold text-rose-500 dark:text-rose-400 uppercase tracking-wider">{{ __('Rút tiền chờ duyệt') }}</span>
                    <p class="text-xl font-extrabold text-rose-700 dark:text-rose-300">{{ $pendingWithdrawalCount }} {{ __('lệnh') }} · {{ number_format($pendingWithdrawalAmount) }}đ</p>
                </div>
            </div>
            <div class="absolute top-3 right-4 text-rose-400 dark:text-rose-500 group-hover:translate-x-1 transition-transform">
                <i data-lucide="arrow-right" class="w-5 h-5"></i>
            </div>
        </a>
        @endif

        @if($pendingGiftCount > 0)
        <!-- Thẻ cảnh báo đơn đổi quà chờ duyệt - Sử dụng các sắc độ xanh dương phù hợp để hiển thị sắc nét trên dark mode -->
        <a href="{{ route('admin.gifts.index', ['tab' => 'redemptions']) }}" class="group relative overflow-hidden bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-950/20 dark:to-indigo-950/20 p-5 rounded-2xl border border-blue-200 dark:border-blue-900/40 hover:border-blue-300 dark:hover:border-blue-800 hover:shadow-md transition-all duration-200">
            <div class="flex items-center gap-4">
                <div class="p-3 bg-blue-100 dark:bg-blue-950/40 text-blue-600 dark:text-blue-400 rounded-xl group-hover:scale-110 transition-transform">
                    <i data-lucide="gift" class="w-6 h-6"></i>
                </div>
                <div>
                    <span class="text-xs font-bold text-blue-500 dark:text-blue-400 uppercase tracking-wider">{{ __('Đơn đổi quà chờ duyệt') }}</span>
                    <p class="text-xl font-extrabold text-blue-700 dark:text-blue-300">{{ $pendingGiftCount }} {{ __('đơn') }} · {{ number_format($pendingGiftAmount) }}đ</p>
                </div>
            </div>
            <div class="absolute top-3 right-4 text-blue-400 dark:text-blue-500 group-hover:translate-x-1 transition-transform">
                <i data-lucide="arrow-right" class="w-5 h-5"></i>
            </div>
        </a>
        @endif
    </div>
    @endif

    <!-- ===== KHỐI THẺ THỐNG KÊ CHÍNH ===== -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
        @foreach($widgetsConfig as $widget)
            @if($widget['visible'])
                @if($widget['key'] === 'total_users')
                    <!-- 1. Tổng Thành Viên -->
                    <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200 flex items-center justify-between">
                        <div class="space-y-2">
                            <span class="text-xs font-bold text-gray-400 uppercase tracking-wider block">{{ __('Tổng thành viên') }}</span>
                            <p class="text-3xl font-extrabold text-gray-900">{{ number_format($totalUsers) }}</p>
                            {{-- 
                                Hiển thị số thành viên đăng ký tài khoản mới trong ngày hôm nay.
                            --}}
                            <span class="text-[10px] text-gray-400 block">{{ __('Đăng ký hôm nay: :amount', ['amount' => number_format($todayUsers)]) }}</span>
                        </div>
                        <div class="p-3 bg-indigo-50 text-indigo-600 rounded-xl">
                            <i data-lucide="users" class="w-6 h-6"></i>
                        </div>
                    </div>
                @elseif($widget['key'] === 'total_orders')
                    <!-- 1.1. Tổng Đơn Hàng -->
                    <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200 flex items-center justify-between">
                        <div class="space-y-2">
                            <span class="text-xs font-bold text-gray-400 uppercase tracking-wider block">{{ __('Tổng đơn hàng') }}</span>
                            <p class="text-3xl font-extrabold text-gray-900">{{ number_format($totalOrders) }}</p>
                            {{-- 
                                Hiển thị số lượng đơn hàng mới phát sinh trong ngày hôm nay.
                            --}}
                            <span class="text-[10px] text-gray-400 block">{{ __('Đơn hàng hôm nay: :amount', ['amount' => number_format($todayOrders)]) }}</span>
                        </div>
                        <div class="p-3 bg-blue-50 text-blue-600 rounded-xl">
                            <i data-lucide="shopping-bag" class="w-6 h-6"></i>
                        </div>
                    </div>
                @elseif($widget['key'] === 'member_balance')
                    <!-- 1.2. Số Dư Thành Viên -->
                    <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200 flex items-center justify-between">
                        <div class="space-y-2">
                            <span class="text-xs font-bold text-gray-400 uppercase tracking-wider block">{{ __('Số dư thành viên') }}</span>
                            <p class="text-3xl font-extrabold text-indigo-600">{{ number_format($totalMemberBalance) }}đ</p>
                            {{-- 
                                Hiển thị tổng số dư hiện có của tất cả thành viên trong hệ thống (không bao gồm admin).
                            --}}
                        </div>
                        <div class="p-3 bg-indigo-50 text-indigo-600 rounded-xl">
                            <i data-lucide="wallet-cards" class="w-6 h-6"></i>
                        </div>
                    </div>
                @elseif($widget['key'] === 'revenue')
                    <!-- 2. Tổng Doanh Thu (Chỉ tính đơn hàng hoàn tiền) -->
                    <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200 flex items-center justify-between">
                        <div class="space-y-2">
                            <span class="text-xs font-bold text-gray-400 uppercase tracking-wider block">{{ __('Doanh thu') }}</span>
                            <p class="text-3xl font-extrabold text-green-600">{{ number_format($totalRevenue) }}đ</p>
                            {{-- 
                                Hiển thị doanh thu chờ đối soát chỉ tính riêng cho các đơn hàng hoàn tiền đang chờ duyệt (pending).
                                Không bao gồm doanh thu từ điểm danh và mã giảm giá để phân tách rõ ràng hiệu quả các nguồn thu.
                            --}}
                            <span class="text-[10px] text-gray-400 block">{{ __('Chờ đối soát: :amountđ', ['amount' => number_format($pendingRevenue)]) }}</span>
                        </div>
                        <div class="p-3 bg-green-50 text-green-600 rounded-xl">
                            <i data-lucide="arrow-up-right" class="w-6 h-6"></i>
                        </div>
                    </div>
                @elseif($widget['key'] === 'checkin_revenue')
                    <!-- 3. Doanh Thu Điểm Danh -->
                    <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200 flex items-center justify-between">
                        <div class="space-y-2">
                            <span class="text-xs font-bold text-gray-400 uppercase tracking-wider block">{{ __('Doanh thu Điểm danh') }}</span>
                            <p class="text-3xl font-extrabold text-amber-500">{{ number_format($totalCheckinRevenue) }}đ</p>
                            <span class="text-[10px] text-gray-400 block">{{ __('Chờ đối soát: :amountđ', ['amount' => number_format($totalCheckinPending)]) }}</span>
                        </div>
                        <div class="p-3 bg-amber-50 text-amber-500 rounded-xl">
                            <i data-lucide="calendar-check" class="w-6 h-6"></i>
                        </div>
                    </div>
                @elseif($widget['key'] === 'coupon_revenue')
                    <!-- 3.1. Doanh Thu Mã Giảm Giá -->
                    <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200 flex items-center justify-between">
                        <div class="space-y-2">
                            <span class="text-xs font-bold text-gray-400 uppercase tracking-wider block">{{ __('Doanh thu Mã giảm giá') }}</span>
                            <p class="text-3xl font-extrabold text-sky-500">{{ number_format($totalCouponRevenue) }}đ</p>
                            <span class="text-[10px] text-gray-400 block">{{ __('Chờ đối soát: :amountđ', ['amount' => number_format($totalCouponPending)]) }}</span>
                        </div>
                        <div class="p-3 bg-sky-50 text-sky-500 rounded-xl">
                            <i data-lucide="tag" class="w-6 h-6"></i>
                        </div>
                    </div>
                @elseif($widget['key'] === 'cashback')
                    <!-- 4. Tổng Cashback Trả F0 -->
                    <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200 flex items-center justify-between">
                        <div class="space-y-2">
                            <span class="text-xs font-bold text-gray-400 uppercase tracking-wider block">{{ __('Cashback trả F0') }}</span>
                            <p class="text-3xl font-extrabold text-shopee">{{ number_format($totalCashback) }}đ</p>
                            {{-- 
                                Hiển thị tổng số tiền hoàn dự kiến trả cho thành viên mua hàng (F0) của các đơn hàng đang chờ duyệt.
                            --}}
                            <span class="text-[10px] text-gray-400 block">{{ __('Chờ đối soát: :amountđ', ['amount' => number_format($pendingCashbackAmount)]) }}</span>
                        </div>
                        <div class="p-3 bg-orange-50 text-shopee rounded-xl">
                            <i data-lucide="gem" class="w-6 h-6"></i>
                        </div>
                    </div>
                @elseif($widget['key'] === 'mlm_commission')
                    <!-- 5. Tổng Hoa Hồng MLM (F1 + F2) -->
                    <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200 flex items-center justify-between">
                        <div class="space-y-2">
                            <span class="text-xs font-bold text-gray-400 uppercase tracking-wider block">{{ __('Hoa hồng MLM (F1+F2)') }}</span>
                            <p class="text-3xl font-extrabold text-purple-600">{{ number_format($totalReferralPaid) }}đ</p>
                        </div>
                        <div class="p-3 bg-purple-50 text-purple-600 rounded-xl">
                            <i data-lucide="network" class="w-6 h-6"></i>
                        </div>
                    </div>
                @elseif($widget['key'] === 'profit')
                    <!-- 6. Lợi Nhuận -->
                    <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200 flex items-center justify-between">
                        <div class="space-y-2">
                            <span class="text-xs font-bold text-gray-400 uppercase tracking-wider block">{{ __('Lợi nhuận') }}</span>
                            <p class="text-3xl font-extrabold {{ $systemProfit >= 0 ? 'text-blue-600' : 'text-red-600' }}">{{ number_format($systemProfit) }}đ</p>
                            {{-- 
                                Hiển thị lợi nhuận chờ đối soát dự kiến nhận được từ các đơn hàng hoàn tiền đang chờ duyệt, 
                                kèm theo doanh thu điểm danh và mã giảm giá đang chờ đối soát.
                            --}}
                            <span class="text-[10px] text-gray-400 block">{{ __('Chờ đối soát: :amountđ', ['amount' => number_format($pendingProfit)]) }}</span>
                            <span class="text-[9px] text-gray-400 block mt-0.5">{{ __('= Shopee - F0 - MLM + Điểm danh + Mã giảm giá') }}</span>
                        </div>
                        <div class="p-3 {{ $systemProfit >= 0 ? 'bg-blue-50 text-blue-600' : 'bg-red-50 text-red-600' }} rounded-xl">
                            <i data-lucide="{{ $systemProfit >= 0 ? 'trending-up' : 'trending-down' }}" class="w-6 h-6"></i>
                        </div>
                    </div>
                @elseif($widget['key'] === 'total_withdrawn')
                    <!-- 7. Tổng Tiền Rút Thành Công -->
                    <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200 flex items-center justify-between">
                        <div class="space-y-2">
                            <span class="text-xs font-bold text-gray-400 uppercase tracking-wider block">{{ __('Tổng rút thành công') }}</span>
                            <p class="text-3xl font-extrabold text-teal-600">{{ number_format($totalWithdrawn) }}đ</p>
                        </div>
                        <div class="p-3 bg-teal-50 text-teal-600 rounded-xl">
                            <i data-lucide="wallet" class="w-6 h-6"></i>
                        </div>
                    </div>
                @elseif($widget['key'] === 'online_users')
                    <!-- 8. Thành Viên Online -->
                    <a href="{{ route('admin.users.index', ['is_online' => 'online']) }}" class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200 flex items-center justify-between hover:shadow-md transition-all duration-200">
                        <div class="space-y-2">
                            <span class="text-xs font-bold text-gray-400 uppercase tracking-wider block">{{ __('Thành viên Online') }}</span>
                            <div class="flex items-center gap-2">
                                <p class="text-3xl font-extrabold text-green-600">{{ number_format($totalOnlineUsers) }}</p>
                                <span class="relative flex h-3 w-3">
                                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                                    <span class="relative inline-flex rounded-full h-3 w-3 bg-green-500"></span>
                                </span>
                            </div>
                            <span class="text-[10px] text-gray-400 block">{{ __('Hoạt động trong 5 phút qua') }}</span>
                        </div>
                        <div class="p-3 bg-green-50 text-green-600 rounded-xl">
                            <i data-lucide="radio" class="w-6 h-6"></i>
                        </div>
                    </a>
                @elseif($widget['key'] === 'checkin_paid')
                    <!-- 9. Chi Trả Điểm Danh -->
                    <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200 flex items-center justify-between">
                        <div class="space-y-2">
                            <span class="text-xs font-bold text-gray-400 uppercase tracking-wider block">{{ __('Chi trả Điểm danh') }}</span>
                            <p class="text-3xl font-extrabold text-orange-500">{{ number_format($totalCheckinPaid) }}đ</p>
                            <span class="text-[10px] text-gray-400 block">{{ __('Chi trả hôm nay: :amountđ', ['amount' => number_format($todayCheckinPaid)]) }}</span>
                        </div>
                        <div class="p-3 bg-orange-50 text-orange-500 rounded-xl">
                            <i data-lucide="award" class="w-6 h-6"></i>
                        </div>
                    </div>
                @elseif($widget['key'] === 'task_paid')
                    <!-- 9.1. Chi Trả Nhiệm Vụ -->
                    <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200 flex items-center justify-between">
                        <div class="space-y-2">
                            <span class="text-xs font-bold text-gray-400 uppercase tracking-wider block">{{ __('Chi trả Nhiệm vụ') }}</span>
                            <p class="text-3xl font-extrabold text-violet-500">{{ number_format($totalTaskPaid) }}đ</p>
                            <span class="text-[10px] text-gray-400 block">{{ __('Chi trả hôm nay: :amountđ', ['amount' => number_format($todayTaskPaid)]) }}</span>
                        </div>
                        <div class="p-3 bg-violet-50 text-violet-500 rounded-xl">
                            <i data-lucide="clipboard-check" class="w-6 h-6"></i>
                        </div>
                    </div>
                @elseif($widget['key'] === 'giftcode_paid')
                    <!-- 9.2. Chi Trả Giftcode -->
                    <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200 flex items-center justify-between">
                        <div class="space-y-2">
                            <span class="text-xs font-bold text-gray-400 uppercase tracking-wider block">{{ __('Chi trả Giftcode') }}</span>
                            <p class="text-3xl font-extrabold text-pink-500">{{ number_format($totalGiftcodePaid) }}đ</p>
                            <span class="text-[10px] text-gray-400 block">{{ __('Chi trả hôm nay: :amountđ', ['amount' => number_format($todayGiftcodePaid)]) }}</span>
                        </div>
                        <div class="p-3 bg-pink-50 text-pink-500 rounded-xl">
                            <i data-lucide="gift" class="w-6 h-6"></i>
                        </div>
                    </div>
                @elseif($widget['key'] === 'gift_exchanged')
                    <!-- 9.3. Quà tặng quy đổi -->
                    <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200 flex items-center justify-between">
                        <div class="space-y-2">
                            <span class="text-xs font-bold text-gray-400 uppercase tracking-wider block">{{ __('Quà tặng quy đổi') }}</span>
                            <p class="text-3xl font-extrabold text-rose-500">{{ number_format($totalGiftExchanged) }}đ</p>
                            <span class="text-[10px] text-gray-400 block">{{ __('Chờ duyệt: :amountđ', ['amount' => number_format($pendingGiftExchanged)]) }}</span>
                            <span class="text-[10px] text-gray-400 block">{{ __('Yêu cầu hôm nay: :amountđ', ['amount' => number_format($todayGiftExchanged)]) }}</span>
                        </div>
                        <div class="p-3 bg-rose-50 text-rose-500 rounded-xl">
                            <i data-lucide="package" class="w-6 h-6"></i>
                        </div>
                    </div>
                @endif
            @endif
        @endforeach
    </div>

    <!-- ===== BIỂU ĐỒ PHÂN TÍCH TĂNG TRƯỞNG ===== -->
    <div x-data="dashboardChartHandler()" x-init="initChart()" class="bg-white dark:bg-slate-900 p-6 rounded-3xl shadow-sm border border-gray-200 dark:border-slate-800 transition-colors">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
            <h2 class="font-bold text-gray-900 dark:text-white text-base flex items-center gap-2">
                <i data-lucide="trending-up" class="w-5 h-5 text-shopee"></i>
                <span>{{ __('Biểu Đồ Tăng Trưởng Doanh Thu & Chi Phí') }}</span>
                <span class="text-xs font-semibold text-gray-400 dark:text-slate-500" x-text="'(' + currentRangeLabel + ')'"></span>
            </h2>

            <!-- Bộ lọc chọn khoảng thời gian bên phải -->
            <div class="flex items-center bg-gray-100 dark:bg-slate-800/80 p-1 rounded-2xl border border-gray-200/80 dark:border-slate-700/60 self-start sm:self-auto shrink-0 shadow-inner">
                <button type="button" @click="setRange('today')"
                    :class="activeRange === 'today' ? 'bg-white dark:bg-slate-700 text-shopee dark:text-shopee font-extrabold shadow-sm' : 'text-gray-500 dark:text-slate-400 hover:text-gray-900 dark:hover:text-white font-medium'"
                    class="px-3 py-1.5 rounded-xl text-xs transition-all duration-200">
                    {{ __('Hôm nay') }}
                </button>
                <button type="button" @click="setRange('7_days')"
                    :class="activeRange === '7_days' ? 'bg-white dark:bg-slate-700 text-shopee dark:text-shopee font-extrabold shadow-sm' : 'text-gray-500 dark:text-slate-400 hover:text-gray-900 dark:hover:text-white font-medium'"
                    class="px-3 py-1.5 rounded-xl text-xs transition-all duration-200">
                    {{ __('7 ngày') }}
                </button>
                <button type="button" @click="setRange('30_days')"
                    :class="activeRange === '30_days' ? 'bg-white dark:bg-slate-700 text-shopee dark:text-shopee font-extrabold shadow-sm' : 'text-gray-500 dark:text-slate-400 hover:text-gray-900 dark:hover:text-white font-medium'"
                    class="px-3 py-1.5 rounded-xl text-xs transition-all duration-200">
                    {{ __('30 ngày') }}
                </button>
                <button type="button" @click="setRange('1_year')"
                    :class="activeRange === '1_year' ? 'bg-white dark:bg-slate-700 text-shopee dark:text-shopee font-extrabold shadow-sm' : 'text-gray-500 dark:text-slate-400 hover:text-gray-900 dark:hover:text-white font-medium'"
                    class="px-3 py-1.5 rounded-xl text-xs transition-all duration-200">
                    {{ __('1 năm') }}
                </button>
            </div>
        </div>

        <div class="relative h-80 w-full">
            <!-- Loading Spinner overlay khi chuyển đổi mốc thời gian -->
            <div x-show="isLoading" x-transition.opacity class="absolute inset-0 bg-white/70 dark:bg-slate-900/70 backdrop-blur-sm z-10 flex items-center justify-center rounded-2xl" style="display: none;">
                <div class="flex items-center gap-2 bg-white dark:bg-slate-800 px-4 py-2 rounded-2xl shadow-lg border border-gray-200 dark:border-slate-700">
                    <svg class="animate-spin h-4 w-4 text-shopee" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span class="text-xs font-bold text-gray-700 dark:text-slate-300">{{ __('Đang tải dữ liệu...') }}</span>
                </div>
            </div>

            <canvas id="growthChart"></canvas>
        </div>
    </div>

    <!-- ===== DANH SÁCH GIAO DỊCH MỚI NHẤT ===== -->
    <div class="grid grid-cols-1 xl:grid-cols-2 gap-8">

        <!-- Danh sách đơn hoàn tiền gần nhất -->
        <div class="bg-white p-6 rounded-3xl shadow-sm border border-gray-200 space-y-4">
            <div class="flex justify-between items-center border-b border-gray-100 pb-3">
                <h3 class="font-bold text-gray-900 text-sm flex items-center gap-2">
                    <i data-lucide="receipt" class="w-4.5 h-4.5 text-gray-400"></i>
                    {{ __('Đơn hàng hoàn tiền mới phát sinh') }}
                </h3>
                <a href="{{ route('admin.cashback.index') }}" class="text-xs font-semibold text-shopee hover:underline">{{ __('Quản lý') }}</a>
            </div>

            <div class="space-y-3">
                @forelse($recentCashbacks as $cb)
                    <!-- Lịch sử click/hoàn tiền: min-w-0 flex-1 giúp chống tràn, text-right shrink-0 và whitespace-nowrap giữ badge trên 1 dòng -->
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-2xl text-xs">
                        <div class="min-w-0 flex-1 mr-3">
                            <p class="font-bold text-gray-800 truncate">{{ Str::limit($cb->product_name, 35) }}</p>
                            <span class="text-[10px] text-gray-400 block truncate">{{ __('Khách') }}: {{ $cb->user?->email ?? __('Không xác định') }} | {{ __('Mã') }}: {{ $cb->order_id }}</span>
                        </div>
                        <div class="text-right shrink-0">
                            <span class="font-extrabold text-orange-600 block">+{{ number_format($cb->cashback_amount) }}đ</span>
                            @if($cb->status === 'pending')
                                <span class="px-1.5 py-0.5 rounded text-[8px] font-bold bg-yellow-50 text-yellow-600 border border-yellow-100 whitespace-nowrap">{{ __('Chờ duyệt') }}</span>
                            @elseif($cb->status === 'approved')
                                <span class="px-1.5 py-0.5 rounded text-[8px] font-bold bg-green-50 text-green-600 border border-green-100 whitespace-nowrap">{{ __('Thành công') }}</span>
                            @else
                                <span class="px-1.5 py-0.5 rounded text-[8px] font-bold bg-red-50 text-red-600 border border-red-100 whitespace-nowrap">{{ __('Từ chối') }}</span>
                            @endif
                        </div>
                    </div>
                @empty
                    <p class="text-xs text-gray-400 text-center py-6">{{ __('Chưa có đơn hàng nào.') }}</p>
                @endforelse
            </div>
        </div>

        <!-- Danh sách yêu cầu rút tiền gần nhất -->
        <div class="bg-white p-6 rounded-3xl shadow-sm border border-gray-200 space-y-4">
            <div class="flex justify-between items-center border-b border-gray-100 pb-3">
                <h3 class="font-bold text-gray-900 text-sm flex items-center gap-2">
                    <i data-lucide="wallet" class="w-4.5 h-4.5 text-gray-400"></i>
                    {{ __('Yêu cầu rút tiền mới') }}
                </h3>
                <a href="{{ route('admin.withdrawals.index') }}" class="text-xs font-semibold text-shopee hover:underline">{{ __('Quản lý') }}</a>
            </div>

            <div class="space-y-3">
                @forelse($recentWithdrawals as $wd)
                    <!-- Yêu cầu rút tiền: min-w-0 flex-1 giúp chống tràn, text-right shrink-0 và whitespace-nowrap giữ badge trên 1 dòng -->
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-2xl text-xs">
                        <div class="min-w-0 flex-1 mr-3">
                            <p class="font-bold text-gray-800 truncate">{{ __('Rút') }} {{ number_format($wd->amount) }}đ {{ __('về') }} {{ $wd->payment_method === 'momo' ? 'Momo' : $wd->bank_name }}</p>
                            <span class="text-[10px] text-gray-400 block truncate">{{ __('Khách') }}: {{ $wd->user?->email ?? __('Không xác định') }} | {{ __('Số tài khoản') }}: {{ $wd->account_number }}</span>
                        </div>
                        <div class="text-right shrink-0">
                            <span class="font-bold text-gray-700 block truncate">{{ $wd->account_name }}</span>
                            @if($wd->status === 'pending')
                                <span class="px-1.5 py-0.5 rounded text-[8px] font-bold bg-yellow-50 text-yellow-600 border border-yellow-100 whitespace-nowrap">{{ __('Chờ duyệt') }}</span>
                            @elseif($wd->status === 'approved')
                                <span class="px-1.5 py-0.5 rounded text-[8px] font-bold bg-green-50 text-green-600 border border-green-100 whitespace-nowrap">{{ __('Thành công') }}</span>
                            @else
                                <span class="px-1.5 py-0.5 rounded text-[8px] font-bold bg-red-50 text-red-600 border border-red-100 whitespace-nowrap">{{ __('Từ chối') }}</span>
                            @endif
                        </div>
                    </div>
                @empty
                    <p class="text-xs text-gray-400 text-center py-6">{{ __('Chưa có yêu cầu rút tiền.') }}</p>
                @endforelse
            </div>
        </div>

    </div>

    <!-- Modal cấu hình Widget thống kê (AlpineJS) -->
    <div x-data="dashboardWidgetConfigHandler()" @open-widget-modal.window="openModal()" x-show="isOpen"
        class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
        <div class="fixed inset-0 bg-slate-900/70 backdrop-blur-md transition-opacity" @click="closeModal()"></div>

        <div class="flex min-h-full items-center justify-center p-4 sm:p-0">
            <div x-show="isOpen"
                x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-5 scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                x-transition:leave-end="opacity-0 translate-y-5 scale-95"
                class="relative w-full sm:max-w-md transform overflow-hidden rounded-3xl bg-white dark:bg-slate-900 text-left shadow-2xl shadow-slate-900/25 transition-all sm:my-8 ring-1 ring-slate-200 dark:ring-slate-700/60">

                <!-- Header -->
                <div class="relative px-6 pt-6 pb-5 overflow-hidden">
                    <!-- Decorative blobs -->
                    <div class="absolute -top-6 -right-6 w-28 h-28 rounded-full bg-indigo-500/8 blur-2xl pointer-events-none"></div>
                    <div class="absolute -bottom-4 -left-4 w-20 h-20 rounded-full bg-violet-500/8 blur-2xl pointer-events-none"></div>

                    <div class="relative flex items-start justify-between gap-4">
                        <div class="flex items-center gap-3.5">
                            <div class="w-11 h-11 rounded-2xl bg-gradient-to-br from-indigo-500 to-violet-600 flex items-center justify-center shadow-lg shadow-indigo-500/30 shrink-0">
                                <i data-lucide="layout-grid" class="w-5 h-5 text-white"></i>
                            </div>
                            <div>
                                <h3 class="text-[15px] font-black text-slate-900 dark:text-white tracking-tight leading-tight">{{ __('Bố cục Dashboard') }}</h3>
                                <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-0.5 font-medium">{{ __('Kéo thả để sắp xếp · Bật/tắt để ẩn hiện') }}</p>
                            </div>
                        </div>
                        <button @click="closeModal()" class="w-8 h-8 rounded-xl flex items-center justify-center text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition-all shrink-0 mt-0.5">
                            <i data-lucide="x" class="w-4 h-4"></i>
                        </button>
                    </div>

                    <!-- Visible count pill -->
                    <div class="relative mt-4">
                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-indigo-50 dark:bg-indigo-950/50 border border-indigo-100 dark:border-indigo-900/60 text-indigo-600 dark:text-indigo-400 text-[11px] font-bold">
                            <span class="w-1.5 h-1.5 rounded-full bg-indigo-500 animate-pulse"></span>
                            <span x-text="widgets.filter(w => w.visible).length + '/' + widgets.length"></span>
                            <span>{{ __('widget đang hiển thị') }}</span>
                        </span>
                    </div>
                </div>

                <div class="h-px bg-gradient-to-r from-transparent via-slate-200 dark:via-slate-700/60 to-transparent mx-6"></div>

                <!-- Widget List -->
                <div class="px-4 py-4 max-h-[50vh] overflow-y-auto space-y-2">
                    <template x-for="(widget, index) in widgets" :key="widget.key">
                        <div
                            draggable="true"
                            @dragstart="dragstart($event, index)"
                            @dragover.prevent="dragover($event, index)"
                            @drop="drop($event, index)"
                            @dragend="dragend()"
                            :class="{
                                'border-indigo-400 dark:border-indigo-600 bg-indigo-50/50 dark:bg-indigo-950/30 ring-2 ring-indigo-500/20 scale-[0.982] shadow-lg shadow-indigo-500/10': draggingIndex === index,
                                'border-slate-100 dark:border-slate-800 bg-white dark:bg-slate-900 hover:border-indigo-200 dark:hover:border-slate-700 hover:shadow-md hover:shadow-slate-100/80 dark:hover:shadow-slate-900/50': draggingIndex !== index,
                            }"
                            class="group flex items-center gap-3 p-3.5 border rounded-2xl shadow-sm transition-all duration-200 cursor-grab active:cursor-grabbing select-none"
                        >
                            <!-- Position badge -->
                            <div class="w-6 h-6 rounded-lg flex items-center justify-center shrink-0 transition-colors"
                                :class="widget.visible ? 'bg-indigo-50 dark:bg-indigo-950/50' : 'bg-slate-100 dark:bg-slate-800'">
                                <span class="text-[10px] font-black leading-none transition-colors"
                                    :class="widget.visible ? 'text-indigo-500 dark:text-indigo-400' : 'text-slate-400 dark:text-slate-500'"
                                    x-text="index + 1"></span>
                            </div>

                            <!-- Grip handle -->
                            <div class="text-slate-200 dark:text-slate-700 group-hover:text-slate-300 dark:group-hover:text-slate-600 transition-colors shrink-0">
                                <i data-lucide="grip-vertical" class="w-3.5 h-3.5"></i>
                            </div>

                            <!-- Widget icon -->
                            <div :class="[iconsMap[widget.key] ? iconsMap[widget.key].color : 'bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400', !widget.visible ? 'opacity-40' : '']"
                                class="w-9 h-9 rounded-xl shrink-0 flex items-center justify-center transition-opacity shadow-sm">
                                <i :data-lucide="iconsMap[widget.key] ? iconsMap[widget.key].name : 'layout-grid'" class="w-4 h-4"></i>
                            </div>

                            <!-- Name & Key -->
                            <div class="flex-1 min-w-0">
                                <span class="text-sm font-bold block truncate transition-colors"
                                    :class="widget.visible ? 'text-slate-800 dark:text-slate-200' : 'text-slate-400 dark:text-slate-500'"
                                    x-text="widget.name"></span>
                                <span class="text-[10px] font-mono block mt-0.5 transition-colors"
                                    :class="widget.visible ? 'text-slate-400 dark:text-slate-600' : 'text-slate-300 dark:text-slate-700'"
                                    x-text="widget.key"></span>
                            </div>

                            <!-- Status badge + Toggle -->
                            <div class="flex items-center gap-2 shrink-0">
                                <span x-show="widget.visible"
                                    class="hidden sm:inline-flex text-[10px] font-bold px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-600 dark:bg-emerald-950/40 dark:text-emerald-400 border border-emerald-100 dark:border-emerald-900/50">
                                    {{ __('Hiện') }}
                                </span>
                                <span x-show="!widget.visible"
                                    class="hidden sm:inline-flex text-[10px] font-bold px-2 py-0.5 rounded-full bg-slate-100 text-slate-400 dark:bg-slate-800 dark:text-slate-500 border border-slate-200 dark:border-slate-700">
                                    {{ __('Ẩn') }}
                                </span>

                                <label class="relative inline-flex items-center cursor-pointer select-none">
                                    <input type="checkbox" x-model="widget.visible" class="sr-only peer">
                                    <div class="w-9 h-5 bg-slate-200 dark:bg-slate-700 rounded-full peer
                                        peer-checked:bg-indigo-500 dark:peer-checked:bg-indigo-500
                                        after:content-[''] after:absolute after:top-[2px] after:left-[2px]
                                        after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all after:shadow-sm
                                        peer-checked:after:translate-x-full transition-colors duration-200"></div>
                                </label>
                            </div>
                        </div>
                    </template>
                </div>

                <div class="h-px bg-gradient-to-r from-transparent via-slate-200 dark:via-slate-700/60 to-transparent mx-6"></div>

                <!-- Footer -->
                <div class="px-6 py-4 flex items-center justify-between gap-3">
                    <p class="text-[11px] text-slate-400 dark:text-slate-500 font-medium hidden sm:block">
                        <span x-text="widgets.filter(w => w.visible).length"></span>/<span x-text="widgets.length"></span> {{ __('widget hiển thị') }}
                    </p>
                    <div class="flex items-center gap-2 w-full sm:w-auto justify-end">
                        <button @click="closeModal()"
                            class="px-4 py-2 text-sm font-semibold text-slate-500 hover:text-slate-700 hover:bg-slate-100 dark:text-slate-400 dark:hover:text-slate-200 dark:hover:bg-slate-800 rounded-xl transition-all duration-200">
                            {{ __('Hủy bỏ') }}
                        </button>
                        <button @click="saveConfig()"
                            class="inline-flex items-center gap-2 px-5 py-2 text-sm font-bold text-white bg-indigo-600 hover:bg-indigo-700 active:scale-95 rounded-xl shadow-md shadow-indigo-500/25 hover:shadow-indigo-500/35 transition-all duration-200">
                            <i data-lucide="save" class="w-3.5 h-3.5"></i>
                            {{ __('Lưu cấu hình') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    /**
     * Component quản lý cấu hình Widget thống kê (AlpineJS).
     * Mọi logic block có comment tiếng Việt giải thích đầy đủ.
     */
    function dashboardWidgetConfigHandler() {
        return {
            isOpen: false,
            draggingIndex: null,
            // Nạp dữ liệu cấu hình widget từ PHP gửi qua
            widgets: @json($widgetsConfig),
            
            // Bản đồ icon để tăng độ trực quan và phân biệt màu sắc
            iconsMap: {
                total_users: { name: 'users', color: 'bg-indigo-50 text-indigo-600 dark:bg-indigo-950/40 dark:text-indigo-400' },
                total_orders: { name: 'shopping-bag', color: 'bg-blue-50 text-blue-600 dark:bg-blue-950/40 dark:text-blue-400' },
                member_balance: { name: 'wallet-cards', color: 'bg-indigo-50 text-indigo-600 dark:bg-indigo-950/40 dark:text-indigo-400' },
                revenue: { name: 'arrow-up-right', color: 'bg-green-50 text-green-600 dark:bg-green-950/40 dark:text-green-400' },
                checkin_revenue: { name: 'calendar-check', color: 'bg-amber-50 text-amber-500 dark:bg-amber-950/40 dark:text-amber-400' },
                coupon_revenue: { name: 'tag', color: 'bg-sky-50 text-sky-500 dark:bg-sky-950/40 dark:text-sky-400' },
                cashback: { name: 'gem', color: 'bg-orange-50 text-orange-500 dark:bg-orange-950/40 dark:text-orange-400' },
                mlm_commission: { name: 'network', color: 'bg-purple-50 text-purple-600 dark:bg-purple-950/40 dark:text-purple-400' },
                profit: { name: 'trending-up', color: 'bg-blue-50 text-blue-600 dark:bg-blue-950/40 dark:text-blue-400' },
                total_withdrawn: { name: 'wallet', color: 'bg-teal-50 text-teal-600 dark:bg-teal-950/40 dark:text-teal-400' },
                online_users: { name: 'radio', color: 'bg-emerald-50 text-emerald-600 dark:bg-emerald-950/40 dark:text-emerald-400' },
                checkin_paid: { name: 'award', color: 'bg-yellow-50 text-yellow-600 dark:bg-yellow-950/40 dark:text-yellow-400' },
                task_paid: { name: 'clipboard-check', color: 'bg-violet-50 text-violet-600 dark:bg-violet-950/40 dark:text-violet-400' },
                giftcode_paid: { name: 'gift', color: 'bg-pink-50 text-pink-600 dark:bg-pink-950/40 dark:text-pink-400' },
                gift_exchanged: { name: 'package', color: 'bg-rose-50 text-rose-600 dark:bg-rose-950/40 dark:text-rose-400' },
            },

            // Bắt đầu kéo
            dragstart(event, index) {
                this.draggingIndex = index;
                // Tạo hiệu ứng di chuyển của trình duyệt
                event.dataTransfer.effectAllowed = 'move';
            },

            // Kéo đè lên item khác
            dragover(event, index) {
                // Cho phép drop
            },

            // Thả item xuống vị trí mới
            drop(event, index) {
                if (this.draggingIndex !== null && this.draggingIndex !== index) {
                    // Cắt phần tử đang kéo và chèn lại vào vị trí mới trong mảng
                    const temp = this.widgets[this.draggingIndex];
                    this.widgets.splice(this.draggingIndex, 1);
                    this.widgets.splice(index, 0, temp);
                }
                this.draggingIndex = null;
                // Kích hoạt lại các biểu tượng icon Lucide sau drop
                this.$nextTick(() => { if (window.lucide) window.lucide.createIcons(); });
            },

            // Kết thúc kéo
            dragend() {
                this.draggingIndex = null;
            },
            
            // Hàm mở Modal
            openModal() {
                this.isOpen = true;
                // Re-render lucide icons sau khi hiển thị modal
                this.$nextTick(() => { if (window.lucide) window.lucide.createIcons(); });
            },
            
            // Hàm đóng Modal
            closeModal() {
                this.isOpen = false;
            },
            
            // Di chuyển widget lên trên (giảm index)
            moveUp(index) {
                if (index > 0) {
                    const temp = this.widgets[index];
                    this.widgets[index] = this.widgets[index - 1];
                    this.widgets[index - 1] = temp;
                    // Gọi Lucide cập nhật lại các biểu tượng icon
                    this.$nextTick(() => { if (window.lucide) window.lucide.createIcons(); });
                }
            },
            
            // Di chuyển widget xuống dưới (tăng index)
            moveDown(index) {
                if (index < this.widgets.length - 1) {
                    const temp = this.widgets[index];
                    this.widgets[index] = this.widgets[index + 1];
                    this.widgets[index + 1] = temp;
                    // Gọi Lucide cập nhật lại các biểu tượng icon
                    this.$nextTick(() => { if (window.lucide) window.lucide.createIcons(); });
                }
            },
            
            // Gửi dữ liệu cấu hình đã chỉnh sửa qua AJAX POST để lưu
            async saveConfig() {
                try {
                    const response = await fetch('{{ route('admin.dashboard.save_widgets_config') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({ widgets: this.widgets })
                    });
                    
                    const result = await response.json();
                    if (result.success) {
                        // Kích hoạt sự kiện toast thay thế alert thô
                        window.dispatchEvent(new CustomEvent('toast', {
                            detail: {
                                text: result.message,
                                type: 'success'
                            }
                        }));
                        this.closeModal();
                        // Tải lại trang sau 1 giây để áp dụng cấu hình sắp xếp/ẩn hiện mới
                        setTimeout(() => {
                            window.location.reload();
                        }, 1000);
                    } else {
                        window.dispatchEvent(new CustomEvent('toast', {
                            detail: {
                                text: 'Đã xảy ra lỗi, vui lòng thử lại.',
                                type: 'error'
                            }
                        }));
                    }
                } catch (error) {
                    console.error(error);
                    window.dispatchEvent(new CustomEvent('toast', {
                        detail: {
                            text: 'Không thể kết nối đến máy chủ. Vui lòng thử lại sau.',
                            type: 'error'
                        }
                    }));
                }
            }
        };
    }

    /**
     * Component quản lý bộ lọc khoảng thời gian của Biểu Đồ Tăng Trưởng Doanh Thu & Chi Phí (AlpineJS & Chart.js).
     * Mọi logic block đều có comment tiếng Việt giải thích chi tiết.
     */
    function dashboardChartHandler() {
        return {
            activeRange: '30_days',
            isLoading: false,

            // Bản đồ nhãn khoảng thời gian hiển thị tương ứng
            rangeLabels: {
                today: '{{ __("Hôm nay") }}',
                '7_days': '{{ __("7 Ngày Gần Nhất") }}',
                '30_days': '{{ __("30 Ngày Gần Nhất") }}',
                '1_year': '{{ __("1 Năm Gần Nhất") }}'
            },

            get currentRangeLabel() {
                return this.rangeLabels[this.activeRange] || this.rangeLabels['30_days'];
            },

            // Khởi tạo biểu đồ khi trang load xong (Lưu instance vào biến toàn cục window để tránh AlpineJS Reactive Proxy gây tràn Call Stack)
            initChart() {
                const ctx = document.getElementById('growthChart').getContext('2d');
                if (window.adminGrowthChart) {
                    window.adminGrowthChart.destroy();
                }
                window.adminGrowthChart = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: {!! json_encode($labels) !!},
                        datasets: [
                            {
                                label: '{{ __("Doanh thu hoa hồng Shopee (VNĐ)") }}',
                                data: {!! json_encode($revenueSeries) !!},
                                borderColor: '#10b981',
                                backgroundColor: 'rgba(16, 185, 129, 0.05)',
                                borderWidth: 3,
                                tension: 0.3,
                                fill: true
                            },
                            {
                                label: '{{ __("Doanh thu chưa ghi nhận (VNĐ)") }}',
                                data: {!! json_encode($pendingRevenueSeries) !!},
                                borderColor: '#f59e0b',
                                backgroundColor: 'rgba(245, 158, 11, 0.03)',
                                borderWidth: 2,
                                borderDash: [5, 5],
                                tension: 0.3,
                                fill: true
                            },
                            {
                                label: '{{ __("Tiền hoàn trả F0 (VNĐ)") }}',
                                data: {!! json_encode($cashbackSeries) !!},
                                borderColor: '#ee4d2d',
                                backgroundColor: 'rgba(238, 77, 45, 0.05)',
                                borderWidth: 3,
                                tension: 0.3,
                                fill: true
                            },
                            {
                                label: '{{ __("Hoa hồng MLM F1+F2 (VNĐ)") }}',
                                data: {!! json_encode($referralSeries) !!},
                                borderColor: '#a855f7',
                                backgroundColor: 'rgba(168, 85, 247, 0.05)',
                                borderWidth: 2,
                                borderDash: [5, 5],
                                tension: 0.3,
                                fill: true
                            },
                            {
                                label: '{{ __("Lợi nhuận đã ghi nhận (VNĐ)") }}',
                                data: {!! json_encode($profitSeries) !!},
                                borderColor: '#3b82f6',
                                backgroundColor: 'rgba(59, 130, 246, 0.05)',
                                borderWidth: 3,
                                tension: 0.3,
                                fill: true
                            },
                            {
                                label: '{{ __("Lợi nhuận chưa ghi nhận (VNĐ)") }}',
                                data: {!! json_encode($pendingProfitSeries) !!},
                                borderColor: '#6366f1',
                                backgroundColor: 'rgba(99, 102, 241, 0.03)',
                                borderWidth: 2,
                                borderDash: [5, 5],
                                tension: 0.3,
                                fill: true
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: {
                            mode: 'index',
                            intersect: false
                        },
                        plugins: {
                            legend: {
                                position: 'top',
                                labels: {
                                    font: {
                                        family: 'Outfit',
                                        size: 12
                                    },
                                    usePointStyle: true,
                                    pointStyle: 'circle'
                                }
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        return context.dataset.label + ': ' + new Intl.NumberFormat('vi-VN', { maximumFractionDigits: 0 }).format(context.parsed.y) + 'đ';
                                    }
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    callback: function(value) {
                                        return new Intl.NumberFormat('vi-VN', { maximumFractionDigits: 0 }).format(value) + 'đ';
                                    },
                                    font: {
                                        family: 'Outfit'
                                    }
                                }
                            },
                            x: {
                                ticks: {
                                    font: {
                                        family: 'Outfit'
                                    },
                                    maxRotation: 45,
                                    autoSkip: true,
                                    maxTicksLimit: 15
                                }
                            }
                        }
                    }
                });
            },

            // Thay đổi mốc thời gian và tải dữ liệu mới qua AJAX
            async setRange(range) {
                if (this.isLoading) return;

                this.activeRange = range;
                this.isLoading = true;

                try {
                    const url = '{{ route('admin.dashboard.chart_data') }}?range=' + range;
                    const response = await fetch(url, {
                        method: 'GET',
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });

                    if (!response.ok) {
                        throw new Error('Mã lỗi HTTP ' + response.status);
                    }

                    const result = await response.json();

                    if (result.success && result.data && window.adminGrowthChart) {
                        const d = result.data;
                        // Cập nhật mảng labels và các datasets của Chart.js
                        window.adminGrowthChart.data.labels = d.labels;
                        window.adminGrowthChart.data.datasets[0].data = d.revenueSeries;
                        window.adminGrowthChart.data.datasets[1].data = d.pendingRevenueSeries;
                        window.adminGrowthChart.data.datasets[2].data = d.cashbackSeries;
                        window.adminGrowthChart.data.datasets[3].data = d.referralSeries;
                        window.adminGrowthChart.data.datasets[4].data = d.profitSeries;
                        window.adminGrowthChart.data.datasets[5].data = d.pendingProfitSeries;

                        // Cập nhật lại biểu đồ mượt mà
                        window.adminGrowthChart.update();
                    } else {
                        throw new Error(result.message || 'Phản hồi từ máy chủ không hợp lệ');
                    }
                } catch (error) {
                    console.error('Lỗi khi tải dữ liệu biểu đồ:', error);
                    window.dispatchEvent(new CustomEvent('toast', {
                        detail: {
                            text: 'Không thể tải dữ liệu biểu đồ (' + (error.message || 'Lỗi kết nối') + ')',
                            type: 'error'
                        }
                    }));
                } finally {
                    this.isLoading = false;
                }
            }
        };
    }
</script>
@endsection
