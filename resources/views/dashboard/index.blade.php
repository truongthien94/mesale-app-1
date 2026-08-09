@extends('layouts.app')

@section('title', __('Ví Tiền Của Tôi') . ' - ' . $siteName)

@section('content')
<div class="px-4 mx-auto max-w-7xl sm:px-6 lg:px-8 py-5 sm:py-10" x-data="{ showBalance: true, openModal: false, selectedItem: null }">
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">

        <!-- Cột trái: Sidebar Menu -->
        <div class="hidden lg:block lg:col-span-3">
            @include('dashboard.sidebar')
        </div>

        <!-- Cột phải: Chi tiết ví -->
        <!-- GIAO DIỆN DESKTOP (Giữ nguyên cấu trúc nguyên bản) -->
        <div class="hidden lg:block lg:col-span-9 space-y-5 sm:space-y-8">
            {{-- Nhắc nhở thành viên bổ sung email để bảo vệ tài khoản --}}
            @include('components.email_update_notice')

            <!-- Tiêu đề trang -->
            <div class="flex items-center gap-3 sm:gap-3.5">
                <div class="w-10 h-10 sm:w-12 sm:h-12 flex items-center justify-center bg-gradient-to-tr from-shopee/10 to-shopee-light/10 text-shopee rounded-2xl dark:from-shopee/20 dark:to-shopee-light/20 dark:text-shopee-light shrink-0 shadow-sm">
                    <i data-lucide="wallet" class="w-5 h-5 sm:w-6 sm:h-6"></i>
                </div>
                <div>
                    <h1 class="text-base sm:text-xl font-extrabold text-gray-900 dark:text-white tracking-tight uppercase">
                        {{ __('Ví Tiền Của Tôi') }}
                    </h1>
                    <p class="hidden sm:block text-xs text-gray-400 dark:text-slate-400 mt-0.5">{{ __('Quản lý số dư, theo dõi lịch sử hoàn tiền và tạo yêu cầu rút tiền') }}</p>
                </div>
            </div>

            <!-- Khối Thống Kê Tài Chỉ -->
            <div class="grid grid-cols-2 md:grid-cols-3 gap-3 sm:gap-6">
                <!-- Card 1: Số dư khả dụng (hero - chiếm trọn hàng trên mobile) -->
                <div class="col-span-2 md:col-span-1 relative bg-gradient-to-tr from-[#FF5733] via-[#FF451A] to-[#E02F05] text-white p-5 sm:p-7 rounded-2xl shadow-lg shadow-orange-500/20 overflow-hidden">
                    <div class="absolute -right-8 -top-8 w-32 h-32 bg-white/20 rounded-full blur-2xl pointer-events-none"></div>
                    <div class="absolute -left-6 -bottom-10 w-28 h-28 bg-black/10 rounded-full blur-2xl pointer-events-none"></div>
                    
                    <div class="relative flex justify-between items-start mb-4 sm:mb-5">
                        <span class="text-[11px] sm:text-xs font-bold uppercase tracking-wider text-white/90">{{ __('Số dư khả dụng') }}</span>
                        <div class="w-10 h-10 sm:w-12 sm:h-12 bg-white/20 backdrop-blur-md rounded-xl border border-white/20 flex items-center justify-center shrink-0">
                            <i data-lucide="coins" class="w-5 h-5 sm:w-6 sm:h-6 text-white drop-shadow-sm"></i>
                        </div>
                    </div>
                    
                    <p class="relative text-3xl sm:text-[32px] font-black tracking-tight leading-none" x-html="showBalance ? `{{ \App\Helpers\CurrencyHelper::format($user->balance) }}` : `<span class='tracking-widest'>••••••</span>`"><span class="inline-block w-32 h-7 sm:h-8 bg-white/20 animate-pulse rounded"></span></p>

                    <!-- Hành động nhanh -->
                    <div class="relative flex items-center gap-2.5 mt-5 sm:mt-7">
                        <a href="{{ route('withdraw') }}" class="flex-1 md:flex-none inline-flex items-center justify-center gap-1.5 px-4 py-2.5 sm:py-3 text-[11px] sm:text-xs font-extrabold text-[#FF451A] bg-white rounded-xl shadow-sm active:scale-95 transition-transform hover:shadow-md uppercase tracking-wide">
                            <i data-lucide="banknote" class="w-4 h-4"></i>
                            {{ __('Rút tiền') }}
                        </a>
                        <a href="{{ route('cashback.history') }}" class="flex-1 md:flex-none inline-flex items-center justify-center gap-1.5 px-4 py-2.5 sm:py-3 text-[11px] sm:text-xs font-extrabold text-white bg-white/20 hover:bg-white/30 backdrop-blur-sm rounded-xl border border-white/20 active:scale-95 transition-all uppercase tracking-wide">
                            <i data-lucide="history" class="w-4 h-4"></i>
                            {{ __('Lịch sử') }}
                        </a>
                    </div>
                </div>

                <!-- Card 2: Tổng hoàn tiền -->
                <div class="relative bg-white dark:bg-slate-900 p-5 sm:p-7 rounded-2xl shadow-[0_4px_20px_-4px_rgba(0,0,0,0.05)] border border-gray-50 dark:border-slate-800 overflow-hidden flex flex-col justify-between">
                    <div>
                        <div class="flex justify-between items-start mb-3 sm:mb-4">
                            <span class="text-[11px] sm:text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-slate-400 leading-tight">{{ __('Tổng cashback') }}</span>
                            <div class="w-10 h-10 sm:w-12 sm:h-12 bg-emerald-50 dark:bg-emerald-900/20 rounded-xl flex items-center justify-center border border-emerald-100 dark:border-emerald-900/30 shrink-0">
                                <i data-lucide="gem" class="w-5 h-5 sm:w-6 sm:h-6 text-emerald-500"></i>
                            </div>
                        </div>
                        <p class="text-xl sm:text-3xl font-black text-gray-900 dark:text-white tracking-tight" x-text="showBalance ? '{{ \App\Helpers\CurrencyHelper::format($user->total_cashback) }}' : '***'"><span class="inline-block w-20 h-5 sm:h-7 bg-gray-200 dark:bg-slate-700 animate-pulse rounded"></span></p>
                    </div>
                    
                    <span class="inline-flex items-center gap-1.5 text-[10px] sm:text-[11px] font-bold text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-900/30 px-2.5 py-1.5 rounded-lg mt-4 sm:mt-0 w-max border border-emerald-100 dark:border-emerald-900/40">
                        <i data-lucide="trending-up" class="w-3.5 h-3.5"></i>
                        {{ __('Tích lũy từ hoạt động') }}
                    </span>
                </div>

                <!-- Card 3: Đã rút -->
                <div class="relative bg-white dark:bg-slate-900 p-5 sm:p-7 rounded-2xl shadow-[0_4px_20px_-4px_rgba(0,0,0,0.05)] border border-gray-50 dark:border-slate-800 overflow-hidden flex flex-col justify-between">
                    <div>
                        <div class="flex justify-between items-start mb-3 sm:mb-4">
                            <span class="text-[11px] sm:text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-slate-400 leading-tight">{{ __('Tổng đã rút') }}</span>
                            <div class="w-10 h-10 sm:w-12 sm:h-12 bg-blue-50 dark:bg-blue-900/20 rounded-xl flex items-center justify-center border border-blue-100 dark:border-blue-900/30 shrink-0">
                                <i data-lucide="credit-card" class="w-5 h-5 sm:w-6 sm:h-6 text-blue-500"></i>
                            </div>
                        </div>
                        <p class="text-xl sm:text-3xl font-black text-gray-900 dark:text-white tracking-tight" x-text="showBalance ? '{{ \App\Helpers\CurrencyHelper::format($user->total_withdrawn) }}' : '***'"><span class="inline-block w-20 h-5 sm:h-7 bg-gray-200 dark:bg-slate-700 animate-pulse rounded"></span></p>
                    </div>
                    
                    <span class="inline-flex items-center gap-1.5 text-[10px] sm:text-[11px] font-bold text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/30 px-2.5 py-1.5 rounded-lg mt-4 sm:mt-0 w-max border border-blue-100 dark:border-blue-900/40">
                        <i data-lucide="check-circle" class="w-3.5 h-3.5"></i>
                        {{ __('Đã về tài khoản') }}
                    </span>
                </div>
            </div>

            <!-- Biểu đồ Thống Kê Tiết Kiệm (Desktop) -->
            @include('dashboard.partials.savings_chart')

            <!-- Giao dịch gần nhất & Thông báo -->
            <div class="grid grid-cols-1 xl:grid-cols-2 gap-5 sm:gap-8">
                <!-- Đơn hoàn tiền gần nhất -->
                <div class="bg-white dark:bg-slate-900 p-5 sm:p-7 rounded-2xl shadow-[0_4px_20px_-4px_rgba(0,0,0,0.05)] border border-gray-50 dark:border-slate-800 flex flex-col">
                    <div class="flex items-center justify-between gap-3 mb-6 sm:mb-8">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-orange-50 dark:bg-orange-900/20 text-[#FF451A] rounded-xl flex items-center justify-center border border-orange-100 dark:border-orange-900/40 relative shrink-0">
                                <i data-lucide="receipt" class="w-5 h-5 stroke-[2]"></i>
                                <i data-lucide="sparkles" class="w-2.5 h-2.5 text-[#FF451A] fill-[#FF451A] absolute top-[18px] left-[18px]"></i>
                            </div>
                            <div>
                                <h3 class="text-[14px] sm:text-[15px] font-black text-slate-800 dark:text-slate-100 uppercase tracking-wide flex items-center gap-1.5">
                                    {{ __('ĐƠN HOÀN TIỀN MỚI') }}
                                </h3>
                                <p class="text-[11px] sm:text-[12px] font-medium text-gray-500 dark:text-slate-400">{{ __('Giao dịch phát sinh gần đây') }}</p>
                            </div>
                        </div>
                        <a href="{{ route('cashback.history') }}" class="text-[11px] sm:text-[12px] font-bold text-[#FF451A] bg-orange-50 dark:bg-orange-900/20 px-3 py-1.5 rounded-lg hover:bg-orange-100 dark:hover:bg-orange-900/40 transition-colors shrink-0">{{ __('Tất cả') }}</a>
                    </div>

                    <div class="space-y-3 flex-1">
                        @forelse($recentCashback as $item)
                            @php
                                $showPrice = '1';
                                if (($item->platform ?? 'shopee') === 'tiktok') {
                                    $showPrice = \App\Models\Setting::getVal('tiktok_show_current_price', '1');
                                } else {
                                    $showPrice = \App\Models\Setting::getVal('shopee_show_current_price', '1');
                                }
                            @endphp
                            <div class="flex items-center justify-between gap-3 p-3 bg-gray-50/60 dark:bg-slate-800/40 rounded-xl border border-gray-100 dark:border-slate-800 transition-colors hover:bg-gray-100 dark:hover:bg-slate-800 cursor-pointer active:scale-[0.99] select-none"
                                 @click="selectedItem = {
                                     order_id: '{{ $item->order_id }}',
                                     product_name: '{{ addslashes($item->product_name) }}',
                                     product_image: '{{ $item->product_image }}',
                                     original_price: '{{ \App\Helpers\CurrencyHelper::format($item->original_price) }}',
                                     cashback_amount: '{{ \App\Helpers\CurrencyHelper::format($item->cashback_amount) }}',
                                     cashback_rate: '{{ $item->cashback_rate }}',
                                     commission_amount: '{{ \App\Helpers\CurrencyHelper::format($item->commission_amount) }}',
                                     affiliate_url: '{{ $item->affiliate_url }}',
                                     status: '{{ $item->status }}',
                                     rejected_reason: '{{ addslashes($item->rejected_reason) }}',
                                     created_at: '{{ $item->created_at->format('d/m/Y H:i') }}',
                                     approved_at: '{{ $item->approved_at ? $item->approved_at->format('d/m/Y H:i') : '' }}',
                                     clicks: '{{ $item->getShortLink()?->clicks ?? 0 }}',
                                     trans_id: '{{ $item->trans_id }}',
                                     shop_name: '{{ addslashes($item->shop_name) }}',
                                     platform: '{{ $item->platform ?? 'shopee' }}',
                                     show_price: '{{ $showPrice }}',
                                     status_timeline: {{ json_encode($item->status_timeline ?? []) }},
                                     updated_at: '{{ $item->updated_at ? $item->updated_at->toIso8601String() : '' }}'
                                 }; openModal = true">
                                <div class="flex items-center gap-3 min-w-0">
                                    <div class="relative w-10 h-10 rounded-lg overflow-hidden bg-gray-100 dark:bg-slate-800 shrink-0 flex items-center justify-center border border-gray-100 dark:border-slate-700">
                                        <div class="absolute inset-0 bg-gray-100 dark:bg-slate-800 flex items-center justify-center text-gray-400">
                                            <i data-lucide="image" class="w-4 h-4"></i>
                                        </div>
                                        <img src="{{ $item->product_image }}" alt="" class="absolute inset-0 w-full h-full object-cover z-10" onerror="this.style.display='none';">
                                    </div>
                                    <div class="min-w-0">
                                        <p class="text-[13px] font-bold text-gray-900 dark:text-white truncate">{{ $item->product_name }}</p>
                                        <span class="text-[10px] font-semibold text-gray-400 dark:text-slate-500 mt-0.5 block">Mã đơn: #{{ $item->order_id }}</span>
                                    </div>
                                </div>
                                <div class="text-right shrink-0">
                                    <p class="text-[13px] font-extrabold text-shopee leading-none mb-1.5">+{{ \App\Helpers\CurrencyHelper::format($item->cashback_amount) }}</p>
                                    @if($item->status === 'pending')
                                        <span class="inline-flex px-1.5 py-0.5 text-[9px] font-bold rounded bg-yellow-50 dark:bg-yellow-950/30 text-yellow-600 dark:text-yellow-400 border border-yellow-100 dark:border-yellow-900/30">{{ __('Chờ duyệt') }}</span>
                                    @elseif($item->status === 'approved')
                                        <span class="inline-flex px-1.5 py-0.5 text-[9px] font-bold rounded bg-green-50 dark:bg-green-950/30 text-green-600 dark:text-green-400 border border-green-100 dark:border-green-900/30">{{ __('Đã cộng tiền') }}</span>
                                    @else
                                        <span class="inline-flex px-1.5 py-0.5 text-[9px] font-bold rounded bg-red-50 dark:bg-red-950/30 text-red-600 dark:text-red-400 border border-red-150 dark:border-red-900/30">{{ __('Từ chối') }}</span>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <div class="py-10 text-center flex flex-col items-center justify-center h-full">
                                <div class="w-12 h-12 rounded-full bg-gray-50 dark:bg-slate-800 flex items-center justify-center mb-3">
                                    <i data-lucide="shopping-cart" class="w-6 h-6 text-gray-400 dark:text-slate-500"></i>
                                </div>
                                <p class="text-[13px] font-medium text-gray-500 dark:text-slate-400">{{ __('Bạn chưa có giao dịch hoàn tiền nào.') }}</p>
                            </div>
                        @endforelse
                    </div>
                </div>

                <!-- Thông báo mới nhận -->
                <div class="bg-white dark:bg-slate-900 p-5 sm:p-7 rounded-2xl shadow-[0_4px_20px_-4px_rgba(0,0,0,0.05)] border border-gray-50 dark:border-slate-800 flex flex-col">
                    <div class="flex items-center justify-between gap-3 mb-6 sm:mb-8">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-blue-50 dark:bg-blue-900/20 text-blue-500 rounded-xl flex items-center justify-center border border-blue-100 dark:border-blue-900/40 relative shrink-0">
                                <i data-lucide="bell" class="w-5 h-5 stroke-[2]"></i>
                                <span class="absolute top-[18px] left-[18px] w-2.5 h-2.5 rounded-full bg-red-500 animate-pulse border-2 border-white dark:border-slate-900"></span>
                            </div>
                            <div>
                                <h3 class="text-[14px] sm:text-[15px] font-black text-slate-800 dark:text-slate-100 uppercase tracking-wide flex items-center gap-1.5">
                                    {{ __('THÔNG BÁO HỆ THỐNG') }}
                                </h3>
                                <p class="text-[11px] sm:text-[12px] font-medium text-gray-500 dark:text-slate-400">{{ __('Tin tức & cập nhật mới nhất') }}</p>
                            </div>
                        </div>
                        <a href="{{ route('notifications') }}" class="text-[11px] sm:text-[12px] font-bold text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/20 px-3 py-1.5 rounded-lg hover:bg-blue-100 dark:hover:bg-blue-900/40 transition-colors shrink-0">{{ __('Tất cả') }}</a>
                    </div>

                    <div class="space-y-3 flex-1">
                        @forelse($recentNotifications as $noti)
                            <div class="flex items-center gap-3 p-3.5 bg-gray-50/60 dark:bg-slate-800/40 rounded-xl border border-gray-100 dark:border-slate-800 transition-colors hover:bg-gray-100 dark:hover:bg-slate-800 cursor-pointer active:scale-[0.99] select-none group" onclick="window.location.href='{{ route('notifications') }}'">
                                <div class="w-10 h-10 rounded-full flex items-center justify-center shrink-0 border transition-colors
                                    {{ !$noti->is_read ? 'bg-red-50 text-red-500 border-red-100 dark:bg-red-900/20 dark:border-red-900/40' : 'bg-gray-100 text-gray-400 border-gray-200 dark:bg-slate-800 dark:text-slate-500 dark:border-slate-700' }}">
                                    @if(!$noti->is_read)
                                        <i data-lucide="bell-ring" class="w-4 h-4 shadow-[0_0_8px_rgba(239,68,68,0.5)] rounded-full"></i>
                                    @else
                                        <i data-lucide="bell" class="w-4 h-4"></i>
                                    @endif
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="text-[13px] font-bold text-gray-900 dark:text-white line-clamp-1 group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">{{ $noti->title }}</p>
                                    <p class="text-[11px] text-gray-500 dark:text-slate-400 line-clamp-1 mt-0.5">{{ $noti->content }}</p>
                                </div>
                                <div class="text-right shrink-0 pl-2">
                                    <span class="text-[10px] font-semibold text-gray-400 dark:text-slate-500 whitespace-nowrap block">{{ $noti->created_at->diffForHumans() }}</span>
                                </div>
                            </div>
                        @empty
                            <div class="py-10 text-center flex flex-col items-center justify-center h-full">
                                <div class="w-12 h-12 rounded-full bg-gray-50 dark:bg-slate-800 flex items-center justify-center mb-3">
                                    <i data-lucide="bell-off" class="w-6 h-6 text-gray-400 dark:text-slate-500"></i>
                                </div>
                                <p class="text-[13px] font-medium text-gray-500 dark:text-slate-400">{{ __('Không có thông báo nào mới.') }}</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        <!-- GIAO DIỆN MOBILE (Tối ưu hóa và làm mới theo ảnh mock-up) -->
        <div class="block lg:hidden space-y-5">
            {{-- Nhắc nhở thành viên bổ sung email để bảo vệ tài khoản --}}
            @include('components.email_update_notice')

            <!-- 1. Banner Ví Tiền (Wallet Card 3D) -->
            <div class="relative bg-gradient-to-br from-[#FF5733] via-[#FF451A] to-[#E02F05] text-white p-5 rounded-2xl shadow-lg shadow-orange-500/15 overflow-hidden">
                <!-- Ảnh ví 3D trang trí đặt tuyệt đối ở góc phải dưới -->
                <img src="{{ asset('assets/images/withdraw_banner.webp') }}" 
                     class="absolute right-[-15px] bottom-[-10px] h-36 w-auto object-contain pointer-events-none select-none drop-shadow-md z-0" 
                     alt="Wallet 3D">
                
                <!-- Nội dung chính của banner -->
                <div class="relative z-10 space-y-5">
                    <!-- Hàng tiêu đề & Nút Chi tiết ví -->
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-white/95">{{ __('Ví tiền của tôi') }}</span>
                        <a href="{{ route('balance.logs') }}" class="inline-flex items-center gap-1 px-3 py-1 bg-white/15 hover:bg-white/20 active:scale-95 transition-all rounded-full text-[10px] font-bold text-white border border-white/10">
                            {{ __('Chi tiết ví') }}
                            <i data-lucide="chevron-right" class="w-3 h-3"></i>
                        </a>
                    </div>

                    <!-- Hàng số dư và nút ẩn hiện -->
                    <div>
                        <div class="flex items-center gap-1.5">
                            <span class="text-[11px] font-medium text-white/80 uppercase tracking-wider">{{ __('Số dư khả dụng') }}</span>
                            <button type="button" @click="showBalance = !showBalance" class="text-white/80 hover:text-white transition-colors focus:outline-none flex items-center">
                                <i data-lucide="eye" x-show="showBalance" class="w-3.5 h-3.5"></i>
                                <i data-lucide="eye-off" x-show="!showBalance" class="w-3.5 h-3.5" x-cloak></i>
                            </button>
                        </div>
                        
                        <!-- Hiển thị số dư được định dạng -->
                        <p class="text-4xl font-black tracking-tight mt-1.5 leading-none" x-html="showBalance ? `{{ number_format($user->balance / $currentCurrency->exchange_rate, $currentCurrency->code === 'VND' ? 0 : 2) }}<span class='text-2xl font-bold border-b-2 border-white ml-0.5 align-middle leading-none uppercase'>đ</span>` : `<span class='tracking-widest'>••••••</span>`">
                            <span class="inline-block w-32 h-9 bg-white/20 animate-pulse rounded"></span>
                        </p>
                    </div>

                    <!-- 2 Nút hành động nhanh: Rút tiền & Lịch sử -->
                    <div class="flex items-center gap-3 pt-1">
                        <a href="{{ route('withdraw') }}" class="flex-1 inline-flex items-center justify-center gap-1.5 py-3 px-4 bg-white hover:bg-gray-50 active:scale-95 transition-all rounded-xl text-xs font-bold text-[#FF451A] shadow-sm">
                            <i data-lucide="wallet" class="w-4 h-4 text-[#FF451A]"></i>
                            {{ __('Rút tiền') }}
                        </a>
                        <a href="{{ route('cashback.history') }}" class="flex-1 inline-flex items-center justify-center gap-1.5 py-3 px-4 bg-white/15 hover:bg-white/20 active:scale-95 transition-all rounded-xl text-xs font-bold text-white border border-white/10 backdrop-blur-sm">
                            <i data-lucide="history" class="w-4 h-4 text-white"></i>
                            {{ __('Lịch sử') }}
                        </a>
                    </div>
                </div>
            </div>

            <!-- 2. Khối Thống Kê Số Dư 3 Cột (Chung một block lớn bo góc) -->
            @php
                // Tính toán số liệu thống kê đơn hàng chờ duyệt trực tiếp từ database
                $pendingOrdersCount = \App\Models\CashbackHistory::where('user_id', $user->id)->where('status', 'pending')->count();
                $pendingOrdersAmount = \App\Models\CashbackHistory::where('user_id', $user->id)->where('status', 'pending')->sum('cashback_amount');
            @endphp
            <div class="bg-white dark:bg-slate-900 rounded-2xl p-4 shadow-sm border border-gray-100 dark:border-slate-800/80 grid grid-cols-3 gap-1">
                <!-- Cột 1: Tổng Cashback -->
                <div class="flex items-center gap-2 pr-1 min-w-0">
                    <div class="w-9 h-9 bg-emerald-50 dark:bg-emerald-950/30 rounded-full flex items-center justify-center shrink-0 text-emerald-500 shadow-sm">
                        <i data-lucide="gem" class="w-4 h-4"></i>
                    </div>
                    <div class="min-w-0">
                        <span class="block text-[9px] font-bold text-gray-400 dark:text-slate-400 uppercase tracking-wide truncate">{{ __('Tổng Cashback') }}</span>
                        <span class="block text-xs sm:text-sm font-black text-emerald-600 dark:text-emerald-400 mt-0.5 truncate" x-text="showBalance ? '{{ \App\Helpers\CurrencyHelper::format($user->total_cashback) }}' : '***'"><span class="inline-block w-16 h-3.5 sm:h-4 bg-emerald-200/60 dark:bg-emerald-900/40 animate-pulse rounded"></span></span>
                        <span class="block text-[8px] text-gray-400 dark:text-slate-500 truncate mt-0.5 leading-none">{{ __('Tích lũy từ hoạt động') }}</span>
                    </div>
                </div>
                
                <!-- Cột 2: Đã rút -->
                <div class="flex items-center gap-2 px-1 border-l border-gray-100 dark:border-slate-800 min-w-0">
                    <div class="w-9 h-9 bg-blue-50 dark:bg-blue-950/30 rounded-full flex items-center justify-center shrink-0 text-blue-500 shadow-sm">
                        <i data-lucide="credit-card" class="w-4 h-4"></i>
                    </div>
                    <div class="min-w-0">
                        <span class="block text-[9px] font-bold text-gray-400 dark:text-slate-400 uppercase tracking-wide truncate">{{ __('Đã rút') }}</span>
                        <span class="block text-xs sm:text-sm font-black text-blue-600 dark:text-blue-400 mt-0.5 truncate" x-text="showBalance ? '{{ \App\Helpers\CurrencyHelper::format($user->total_withdrawn) }}' : '***'"><span class="inline-block w-16 h-3.5 sm:h-4 bg-blue-200/60 dark:bg-blue-900/40 animate-pulse rounded"></span></span>
                        <span class="block text-[8px] text-gray-400 dark:text-slate-500 truncate mt-0.5 leading-none">{{ __('Đã về tài khoản') }}</span>
                    </div>
                </div>
                
                <!-- Cột 3: Đang chờ duyệt -->
                <div class="flex items-center gap-2 pl-1 border-l border-gray-100 dark:border-slate-800 min-w-0">
                    <div class="w-9 h-9 bg-orange-50 dark:bg-orange-950/30 rounded-full flex items-center justify-center shrink-0 text-orange-500 shadow-sm">
                        <i data-lucide="history" class="w-4 h-4"></i>
                    </div>
                    <div class="min-w-0">
                        <span class="block text-[9px] font-bold text-gray-400 dark:text-slate-400 uppercase tracking-wide truncate">{{ __('Chờ duyệt') }}</span>
                        <span class="block text-xs sm:text-sm font-black text-orange-600 dark:text-orange-400 mt-0.5 truncate" x-text="showBalance ? '{{ $pendingOrdersCount }} {{ __('đơn') }}' : '***'"><span class="inline-block w-12 h-3.5 sm:h-4 bg-orange-200/60 dark:bg-orange-900/40 animate-pulse rounded"></span></span>
                        <span class="block text-[8px] text-gray-400 dark:text-slate-500 truncate mt-0.5 leading-none" x-text="showBalance ? '{{ __('Trị giá ') }}{{ \App\Helpers\CurrencyHelper::format($pendingOrdersAmount) }}' : '***'"><span class="inline-block w-20 h-2 bg-gray-200 dark:bg-slate-700 animate-pulse rounded"></span></span>
                    </div>
                </div>
            </div>

            <!-- Biểu đồ Thống Kê Tiết Kiệm (Mobile) -->
            @include('dashboard.partials.savings_chart')

            <!-- 3. Danh Sách Đơn Hoàn Tiền Gần Đây -->
            <div>
                <!-- Tiêu đề phần & Nút Xem tất cả -->
                <div class="flex items-center justify-between mb-3">
                    <h2 class="text-sm font-extrabold text-gray-900 dark:text-white flex items-center gap-2">
                        <i data-lucide="receipt" class="w-4 h-4 text-[#FF451A]"></i>
                        {{ __('Đơn hoàn tiền gần đây') }}
                    </h2>
                    <a href="{{ route('cashback.history') }}" class="text-xs font-bold text-[#FF451A] hover:underline flex items-center gap-0.5 shrink-0">
                        {{ __('Xem tất cả') }}
                        <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
                    </a>
                </div>

                <!-- Danh sách các đơn hàng -->
                <div class="space-y-3">
                    @forelse($recentCashback as $item)
                        @php
                            // Cấu hình class màu viền trái và nhãn trạng thái tương ứng
                            $statusBorderClass = 'border-l-yellow-400';
                            $statusLabelColor = 'text-yellow-600 bg-yellow-50 dark:bg-yellow-950/30 border-yellow-100 dark:border-yellow-900/30';
                            $dotColor = 'bg-yellow-500';
                            
                            if ($item->status === 'approved') {
                                $statusBorderClass = 'border-l-emerald-500';
                                $statusLabelColor = 'text-emerald-600 bg-emerald-50 dark:bg-emerald-950/30 border-emerald-100 dark:border-emerald-900/30';
                                $dotColor = 'bg-emerald-500';
                            } elseif ($item->status === 'rejected') {
                                $statusBorderClass = 'border-l-rose-500';
                                $statusLabelColor = 'text-rose-600 bg-rose-50 dark:bg-rose-950/30 border-rose-100 dark:border-rose-900/30';
                                $dotColor = 'bg-rose-500';
                            }
                        @endphp
                        @php
                            $showPrice = '1';
                            if (($item->platform ?? 'shopee') === 'tiktok') {
                                $showPrice = \App\Models\Setting::getVal('tiktok_show_current_price', '1');
                            } else {
                                $showPrice = \App\Models\Setting::getVal('shopee_show_current_price', '1');
                            }
                        @endphp
                        <div class="flex items-center justify-between gap-3 p-3 bg-white dark:bg-slate-900 rounded-xl border-l-[4px] {{ $statusBorderClass }} border-y border-r border-gray-100 dark:border-slate-800 shadow-sm hover:bg-gray-50 dark:hover:bg-slate-800 transition-all cursor-pointer active:scale-[0.99] select-none"
                             @click="selectedItem = {
                                 order_id: '{{ $item->order_id }}',
                                 product_name: '{{ addslashes($item->product_name) }}',
                                 product_image: '{{ $item->product_image }}',
                                 original_price: '{{ \App\Helpers\CurrencyHelper::format($item->original_price) }}',
                                 cashback_amount: '{{ \App\Helpers\CurrencyHelper::format($item->cashback_amount) }}',
                                 cashback_rate: '{{ $item->cashback_rate }}',
                                 commission_amount: '{{ \App\Helpers\CurrencyHelper::format($item->commission_amount) }}',
                                 affiliate_url: '{{ $item->affiliate_url }}',
                                 status: '{{ $item->status }}',
                                 rejected_reason: '{{ addslashes($item->rejected_reason) }}',
                                 created_at: '{{ $item->created_at->format('d/m/Y H:i') }}',
                                 approved_at: '{{ $item->approved_at ? $item->approved_at->format('d/m/Y H:i') : '' }}',
                                 clicks: '{{ $item->getShortLink()?->clicks ?? 0 }}',
                                 trans_id: '{{ $item->trans_id }}',
                                 shop_name: '{{ addslashes($item->shop_name) }}',
                                 platform: '{{ $item->platform ?? 'shopee' }}',
                                 show_price: '{{ $showPrice }}',
                                 status_timeline: {{ json_encode($item->status_timeline ?? []) }},
                                 updated_at: '{{ $item->updated_at ? $item->updated_at->toIso8601String() : '' }}'
                             }; openModal = true">
                            <div class="flex items-center gap-3 min-w-0">
                                <!-- Container ảnh sản phẩm & Tag Sàn thương mại -->
                                <div class="relative w-16 h-16 rounded-lg overflow-hidden bg-gray-100 dark:bg-slate-800 shrink-0 flex items-center justify-center border border-gray-100 dark:border-slate-700">
                                    <!-- Icon placeholder khi ảnh lỗi -->
                                    <div class="absolute inset-0 bg-gray-100 dark:bg-slate-800 flex items-center justify-center text-gray-400">
                                        <i data-lucide="image" class="w-6 h-6"></i>
                                    </div>
                                    <!-- Ảnh sản phẩm chính thức -->
                                    <img src="{{ $item->product_image }}" alt="" class="absolute inset-0 w-full h-full object-cover z-10" onerror="this.style.display='none';">
                                    
                                    <!-- Tag nền màu Shopee / TikTok / Lazada -->
                                    @if(strtolower($item->platform) === 'tiktok')
                                        <span class="absolute top-0.5 left-0.5 px-1 py-0.5 text-[6.5px] font-black tracking-wide text-white bg-black rounded flex items-center gap-0.5 shadow-sm leading-none scale-[0.9] origin-top-left z-20">
                                            <svg class="w-1.5 h-1.5 fill-current" viewBox="0 0 24 24"><path d="M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2zm4.64 6.8c-.15 1.58-.8 5.42-1.13 7.19-.14.75-.42 1-.68 1.03-.58.05-1.02-.38-1.58-.75-.88-.58-1.38-.94-2.23-1.5-.99-.65-.35-1.01.22-1.59.15-.15 2.71-2.48 2.76-2.69.01-.03.01-.14-.07-.2-.08-.06-.19-.04-.27-.02-.12.02-1.96 1.24-5.54 3.65-.52.36-.99.53-1.41.52-.46-.01-1.35-.26-2.01-.48-.81-.27-1.46-.42-1.4-.88.03-.24.37-.49 1.02-.75 3.99-1.74 6.66-2.88 8-3.44 3.81-1.58 4.6-1.86 5.12-1.87.11 0 .37.03.54.17.14.12.18.28.2.45-.02.07-.02.16-.03.25z"/></svg>
                                            {{ \App\Models\Setting::getVal('tiktok_platform_name', 'TikTok Shop') }}
                                        </span>
                                    @elseif(strtolower($item->platform) === 'lazada')
                                        <span class="absolute top-0.5 left-0.5 px-1 py-0.5 text-[6.5px] font-black tracking-wide text-white bg-[#0f146d] rounded flex items-center gap-0.5 shadow-sm leading-none scale-[0.9] origin-top-left z-20">
                                            {{ \App\Models\Setting::getVal('lazada_platform_name', 'Lazada') }}
                                        </span>
                                    @else
                                        <span class="absolute top-0.5 left-0.5 px-1 py-0.5 text-[6.5px] font-black tracking-wide text-white bg-[#FF4E27] rounded flex items-center gap-0.5 shadow-sm leading-none scale-[0.9] origin-top-left z-20">
                                            {{ \App\Models\Setting::getVal('shopee_platform_name', 'Shopee') }}
                                        </span>
                                    @endif
                                </div>

                                <!-- Tên sản phẩm & Mã đơn -->
                                <div class="min-w-0">
                                    <p class="text-xs font-bold text-gray-900 dark:text-white line-clamp-2 leading-snug">{{ $item->product_name }}</p>
                                    <span class="text-[9px] font-bold text-gray-400 dark:text-slate-500 block mt-1">Mã đơn: #{{ $item->order_id }}</span>
                                </div>
                            </div>

                            <!-- Hoa hồng hoàn tiền & Nút trạng thái -->
                            <div class="text-right shrink-0">
                                <p class="text-xs sm:text-sm font-black text-rose-600 dark:text-rose-400 leading-none">+{{ \App\Helpers\CurrencyHelper::format($item->cashback_amount) }}</p>
                                
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 text-[9px] font-bold rounded-full {{ $statusLabelColor }} mt-2">
                                    <span class="w-1.5 h-1.5 rounded-full {{ $dotColor }} shrink-0"></span>
                                    @if($item->status === 'pending')
                                        {{ __('Chờ duyệt') }}
                                    @elseif($item->status === 'approved')
                                        {{ __('Đã cộng tiền') }}
                                    @else
                                        {{ __('Từ chối') }}
                                    @endif
                                </span>
                            </div>
                        </div>
                    @empty
                        <!-- Trạng thái chưa có giao dịch hoàn tiền -->
                        <div class="py-10 text-center bg-white dark:bg-slate-900 rounded-2xl border border-gray-100 dark:border-slate-800/80 shadow-sm">
                            <i data-lucide="shopping-cart" class="w-8 h-8 mx-auto mb-2 text-gray-300 dark:text-slate-650"></i>
                            <p class="text-xs text-gray-400 dark:text-slate-500 font-semibold">{{ __('Bạn chưa có giao dịch hoàn tiền nào.') }}</p>
                        </div>
                    @endforelse
                </div>
            </div>
            <!-- 4. Thông Báo Mới Nhất (Mobile) -->
            <div>
                <!-- Tiêu đề phần & Nút Xem tất cả -->
                <div class="flex items-center justify-between mb-3">
                    <h2 class="text-sm font-extrabold text-gray-900 dark:text-white flex items-center gap-2">
                        <i data-lucide="bell" class="w-4 h-4 text-shopee"></i>
                        {{ __('Thông Báo') }}
                    </h2>
                    <a href="{{ route('notifications') }}" class="text-[10px] font-bold text-shopee bg-shopee/10 px-2 py-1 rounded-lg">{{ __('Xem tất cả') }}</a>
                </div>
                
                <div class="bg-white dark:bg-slate-900 rounded-2xl shadow-sm border border-gray-100 dark:border-slate-800/80 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead>
                                <tr class="text-[9px] uppercase tracking-wider text-gray-400 dark:text-slate-500 font-bold bg-gray-50/50 dark:bg-slate-800/50 border-b border-gray-100 dark:border-slate-800">
                                    <th class="py-2.5 px-3 text-center w-8">#</th>
                                    <th class="py-2.5 px-2">{{ __('Nội dung') }}</th>
                                    <th class="py-2.5 px-3 text-right whitespace-nowrap">{{ __('Thời gian') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-slate-800">
                                @forelse($recentNotifications as $noti)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-slate-800/40 transition-colors active:bg-gray-100" onclick="window.location.href='{{ route('notifications') }}'">
                                        <td class="py-3 px-3 text-center align-middle">
                                            @if(!$noti->is_read)
                                                <span class="inline-block w-1.5 h-1.5 bg-red-500 rounded-full animate-pulse shadow-[0_0_6px_rgba(239,68,68,0.6)]"></span>
                                            @else
                                                <i data-lucide="check-check" class="w-3 h-3 text-green-500 mx-auto opacity-50"></i>
                                            @endif
                                        </td>
                                        <td class="py-3 px-2">
                                            <p class="text-[11px] font-bold text-gray-800 dark:text-slate-100 line-clamp-1">{{ $noti->title }}</p>
                                            <p class="text-[10px] text-gray-500 dark:text-slate-400 line-clamp-1 mt-0.5">{{ $noti->content }}</p>
                                        </td>
                                        <td class="py-3 px-3 text-right whitespace-nowrap text-[9px] text-gray-400 dark:text-slate-500 align-middle">
                                            {{ $noti->created_at->diffForHumans() }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="py-6 text-center text-gray-400 dark:text-slate-500 text-xs">
                                            <i data-lucide="bell-off" class="w-6 h-6 mx-auto mb-1 text-gray-300 dark:text-slate-650"></i>
                                            {{ __('Không có thông báo.') }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
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
                    <span class="text-xs font-mono font-bold text-gray-500 dark:text-slate-400 truncate" x-text="'#' + (selectedItem ? selectedItem.order_id : '')"></span>
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
                                    alt=""
                                    class="absolute inset-0 w-full h-full object-cover z-10"
                                    onerror="this.style.display='none';">
                            </div>
                            <div class="min-w-0 flex-1 space-y-1.5 pt-0.5">
                                <h4 class="text-xs font-bold text-gray-900 dark:text-white leading-snug line-clamp-3" x-text="selectedItem.product_name"></h4>
                                <template x-if="selectedItem.shop_name">
                                    <div class="flex items-center gap-1 text-[10px] text-orange-600 dark:text-orange-400 font-semibold w-max max-w-full">
                                        <i data-lucide="store" class="w-3.5 h-3.5 shrink-0"></i>
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
                                <p class="text-[22px] font-extrabold text-green-600 dark:text-green-400 leading-tight tabular-nums" x-text="'+' + selectedItem.cashback_amount"></p>
                            </div>
                            <div class="w-11 h-11 rounded-2xl bg-green-500/10 dark:bg-green-400/10 flex items-center justify-center shrink-0">
                                <i data-lucide="wallet" class="w-5 h-5 text-green-500 dark:text-green-400"></i>
                            </div>
                        </div>

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

                            <!-- Mã đơn hàng + copy -->
                            <div class="flex items-center justify-between px-4 py-3 bg-white dark:bg-slate-900">
                                <span class="text-[11px] text-gray-400 dark:text-slate-500 font-medium shrink-0">{{ __('Mã đơn hàng') }}</span>
                                <div class="flex items-center gap-1.5 min-w-0 ml-4">
                                    <span class="text-[11px] font-mono font-bold text-gray-800 dark:text-slate-200 truncate" x-text="selectedItem.order_id"></span>
                                    <button
                                        @click="navigator.clipboard.writeText(selectedItem.order_id); $dispatch('toast', { text: '{{ __('Đã sao chép mã đơn Shopee!') }}', type: 'success' })"
                                        class="text-gray-300 dark:text-slate-650 hover:text-shopee dark:hover:text-shopee-light transition-colors shrink-0"
                                        title="{{ __('Sao chép') }}">
                                        <i data-lucide="copy" class="w-3.5 h-3.5"></i>
                                    </button>
                                </div>
                            </div>

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
                                <template x-for="(ev, idx) in buildTimeline(selectedItem)" :key="idx">
                                    <div class="relative flex gap-3 pb-4 last:pb-0">
                                        <!-- Đường kẻ dọc nối các mốc (ẩn ở mốc cuối) -->
                                        <div x-show="idx < buildTimeline(selectedItem).length - 1" class="absolute left-[11px] top-5 bottom-0 w-px bg-gray-200 dark:bg-slate-700"></div>
                                        <!-- Chấm mốc thời gian -->
                                        <div class="relative z-10 shrink-0 w-[23px] h-[23px] rounded-full flex items-center justify-center border-2 bg-white dark:bg-slate-900"
                                             :class="ev.ring">
                                            <i :data-lucide="ev.icon" class="w-3.5 h-3.5" :class="ev.iconColor"></i>
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
                        <span>{{ __('Mua lại sản phẩm') }}</span>
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

<script>
    function formatCurrency(value) {
        if (!value) return '0đ';
        if (typeof value === 'string' && (value.includes('đ') || value.includes('₫') || value.includes('$'))) {
            return value;
        }
        const num = parseFloat(value.toString().replace(/[^0-9.-]+/g,''));
        if (isNaN(num)) return value;
        return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(num);
    }

    function formatDate(dateString) {
        if (!dateString) return 'N/A';
        if (typeof dateString === 'string' && dateString.includes('/') && dateString.includes(':')) {
            return dateString;
        }
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
    }

    function buildTimeline(cb) {
        if (!cb || !cb.created_at) return [];

        const events = [];
        events.push({
            event: 'created',
            at: cb.created_at,
            title: '{{ __('Đơn hàng được ghi nhận') }}',
            icon: 'plus-circle',
            iconColor: 'text-blue-500',
            titleColor: 'text-gray-800 dark:text-slate-200',
            ring: 'border-blue-200 dark:border-blue-900/60',
            source: '',
        });

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
            timeline.forEach(t => {
                const cfg = map[t.event];
                if (!cfg) return;
                events.push({
                    ...cfg,
                    at: formatDate(t.at),
                    source: '',
                    reason: t.reason || '',
                    amount: t.amount ? ('{{ __('Số tiền:') }}' + ' ' + formatCurrency(t.amount)) : '',
                });
            });
        } else {
            if (cb.status === 'approved' && cb.approved_at) {
                events.push({ ...map.approved, at: cb.approved_at, source: '', reason: '', amount: '' });
            } else if (cb.status === 'rejected') {
                events.push({
                    ...map.rejected,
                    at: cb.approved_at || cb.updated_at || cb.created_at,
                    source: '',
                    reason: cb.rejected_reason || '',
                    amount: '',
                });
            }
        }

        return events;
    }
</script>
@endsection
