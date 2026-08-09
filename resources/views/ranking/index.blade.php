@extends('layouts.app')

{{-- SEO: Tiêu đề trang tối ưu --}}
@section('title', __('Bảng Xếp Hạng Thành Viên Hoạt Động Tích Cực') . ' - ' . $siteName)

{{-- SEO: Meta Description riêng cho trang bảng xếp hạng --}}
@section('meta_description', __('Bảng vinh danh những thành viên mua sắm hoàn tiền Shopee xuất sắc nhất, có số lượng đơn hàng, số tiền hoàn và chuỗi ngày điểm danh, số lượng giới thiệu tuyến dưới nhiều nhất hệ thống.'))

@section('content')
@php
    // Đọc cấu hình hiển thị tên thành viên trên bảng xếp hạng (Admin tùy chỉnh tại Cài đặt > Bảng xếp hạng)
    $nameMode      = \App\Models\Setting::getVal('ranking_name_mode', 'mask');            // 'full' = hiện đầy đủ | 'mask' = ẩn bớt
    $nameStart     = (int) \App\Models\Setting::getVal('ranking_name_visible_start', 2);  // Số ký tự hiển thị ở đầu tên
    $nameEnd       = (int) \App\Models\Setting::getVal('ranking_name_visible_end', 2);    // Số ký tự hiển thị ở cuối tên
    $nameMaskChar  = \App\Models\Setting::getVal('ranking_name_mask_char', '*');          // Ký tự dùng để che
    $nameMaskLen   = (int) \App\Models\Setting::getVal('ranking_name_mask_length', 3);    // Số ký tự che ở giữa

    // Hàm phụ trợ che tên thành viên bảo vệ quyền riêng tư theo cấu hình động của Admin
    $maskName = function($name) use ($nameMode, $nameStart, $nameEnd, $nameMaskChar, $nameMaskLen) {
        if (empty($name)) return '';

        // Chế độ hiển thị đầy đủ: trả về nguyên tên không che
        if ($nameMode === 'full') {
            return $name;
        }

        // Chuẩn hóa tham số cấu hình về giá trị an toàn
        $maskChar = $nameMaskChar !== '' ? mb_substr($nameMaskChar, 0, 1, 'UTF-8') : '*';
        $start    = max(0, $nameStart);
        $end      = max(0, $nameEnd);
        $maskLen  = max(1, $nameMaskLen);
        $length   = mb_strlen($name, 'UTF-8');

        // Nếu tên quá ngắn so với số ký tự muốn hiển thị, chỉ giữ ký tự đầu rồi che phần còn lại
        if ($length <= $start + $end) {
            $visible = mb_substr($name, 0, 1, 'UTF-8');
            return $visible . str_repeat($maskChar, $maskLen);
        }

        $head = $start > 0 ? mb_substr($name, 0, $start, 'UTF-8') : '';
        $tail = $end > 0 ? mb_substr($name, -$end, $end, 'UTF-8') : '';
        return $head . str_repeat($maskChar, $maskLen) . $tail;
    };

    // Tìm tab mặc định đầu tiên được bật trong cấu hình
    $activeTab = '';
    if ($topOrdersEnabled) {
        $activeTab = 'orders';
    } elseif ($topCashbackEnabled) {
        $activeTab = 'cashback';
    } elseif ($topCheckinEnabled) {
        $activeTab = 'checkin';
    } elseif ($topReferralEnabled) {
        $activeTab = 'referrals';
    } elseif ($topBalanceEnabled) {
        $activeTab = 'balance';
    }
@endphp

<div class="py-12 px-4 mx-auto max-w-7xl sm:px-6 lg:px-8 relative z-10">
    
    <!-- KHU VỰC HEADER BANNER 3D VINH DANH -->
    <div class="relative bg-gradient-to-b from-orange-100/60 via-orange-50/20 to-white dark:from-slate-950 dark:to-slate-900 overflow-hidden py-10 sm:py-12 lg:py-14 mb-8 rounded-3xl border border-orange-100/40 dark:border-slate-800/60">
        <!-- Ảnh 3D bên trái (Bục vinh danh và cúp) -->
        <div class="absolute top-1/2 -translate-y-1/2 left-2 xl:left-6 w-48 h-48 xl:w-56 xl:h-56 2xl:w-64 2xl:h-64 hidden lg:block select-none pointer-events-none">
            <img src="{{ asset('assets/images/rank_banner_3d.png') }}" alt="Podium 3D" class="w-full h-full object-contain">
        </div>
        <!-- Ảnh 3D bên phải (Túi shopee và hóa đơn) -->
        <div class="absolute top-1/2 -translate-y-1/2 right-2 xl:right-6 w-48 h-48 xl:w-56 xl:h-56 2xl:w-64 2xl:h-64 hidden lg:block select-none pointer-events-none">
            <img src="{{ asset('assets/images/shopee_bag_3d.png') }}" alt="Shopee Bag 3D" class="w-full h-full object-contain">
        </div>
        
        <!-- Nội dung chính ở giữa -->
        <div class="max-w-2xl mx-auto px-4 text-center space-y-3 sm:space-y-4 relative z-10">
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[9px] font-black bg-orange-150 text-orange-600 border border-orange-200/40 uppercase tracking-wider shadow-sm">
                🏆 {{ __('BẢNG VÀNG VINH DANH') }}
            </span>
            <h1 class="text-2xl sm:text-3xl md:text-4xl font-black text-gray-900 dark:text-white tracking-tight leading-tight">
                {{ __('Bảng Xếp Hạng') }} <span class="text-transparent bg-clip-text bg-gradient-to-r from-shopee to-orange-600">{{ __('Cao Thủ Hoàn Tiền') }}</span>
            </h1>
            <p class="text-xs sm:text-sm text-gray-500 dark:text-slate-400 max-w-lg mx-auto leading-relaxed font-medium">
                {{ __('Nơi tôn vinh những thành viên tích cực nhất hệ thống hoàn tiền Shopee.') }}<br>
                {{ __('Hãy mua sắm, điểm danh và chia sẻ liên kết để ghi danh vào bảng vàng ngay hôm nay!') }}
            </p>
            
            <!-- THANH CHỌN TAB BXH (CHỈ HIỂN THỊ KHI CÓ TRÊN 1 BXH ĐƯỢC BẬT) -->
            @if(!empty($activeTab))
                @php
                    $enabledCount = ($topOrdersEnabled ? 1 : 0) + ($topCashbackEnabled ? 1 : 0) + ($topCheckinEnabled ? 1 : 0) + ($topReferralEnabled ? 1 : 0) + ($topBalanceEnabled ? 1 : 0);
                @endphp
                
                @if($enabledCount > 1)
                <div x-data="{ activeTab: '{{ $activeTab }}' }" x-init="$watch('activeTab', value => $dispatch('tab-changed', value))" class="pt-4 overflow-x-auto scrollbar-none [-ms-overflow-style:none] [scrollbar-width:none] [&::-webkit-scrollbar]:hidden w-full">
                    <div class="flex items-center justify-center gap-1.5 p-1 bg-white dark:bg-slate-900 rounded-2xl w-max mx-auto border border-gray-150/80 dark:border-slate-800/80 shadow-sm">
                        @if($topOrdersEnabled)
                        <button @click="activeTab = 'orders'" 
                                :class="activeTab === 'orders' ? 'bg-shopee text-white shadow-md shadow-orange-500/20' : 'text-gray-600 hover:text-gray-900 dark:text-slate-400 dark:hover:text-white hover:bg-gray-50 dark:hover:bg-slate-800/50'"
                                class="flex items-center justify-center gap-2 px-4 sm:px-5 py-2 rounded-xl text-xs font-bold transition-all duration-200 shrink-0">
                            <i data-lucide="trophy" class="w-4 h-4"></i>
                            {{ __('Top Đơn Hàng') }}
                        </button>
                        @endif
                        
                        @if($topCashbackEnabled)
                        <button @click="activeTab = 'cashback'" 
                                :class="activeTab === 'cashback' ? 'bg-shopee text-white shadow-md shadow-orange-500/20' : 'text-gray-600 hover:text-gray-900 dark:text-slate-400 dark:hover:text-white hover:bg-gray-50 dark:hover:bg-slate-800/50'"
                                class="flex items-center justify-center gap-2 px-4 sm:px-5 py-2 rounded-xl text-xs font-bold transition-all duration-200 shrink-0">
                            <i data-lucide="wallet" class="w-4 h-4"></i>
                            {{ __('Top Tiền Hoàn') }}
                        </button>
                        @endif
                        
                        @if($topCheckinEnabled)
                        <button @click="activeTab = 'checkin'" 
                                :class="activeTab === 'checkin' ? 'bg-shopee text-white shadow-md shadow-orange-500/20' : 'text-gray-600 hover:text-gray-900 dark:text-slate-400 dark:hover:text-white hover:bg-gray-50 dark:hover:bg-slate-800/50'"
                                class="flex items-center justify-center gap-2 px-4 sm:px-5 py-2 rounded-xl text-xs font-bold transition-all duration-200 shrink-0">
                            <i data-lucide="flame" class="w-4 h-4"></i>
                            {{ __('Top Điểm Danh') }}
                        </button>
                        @endif
                        
                        @if($topReferralEnabled)
                        <button @click="activeTab = 'referrals'" 
                                :class="activeTab === 'referrals' ? 'bg-shopee text-white shadow-md shadow-orange-500/20' : 'text-gray-600 hover:text-gray-900 dark:text-slate-400 dark:hover:text-white hover:bg-gray-50 dark:hover:bg-slate-800/50'"
                                class="flex items-center justify-center gap-2 px-4 sm:px-5 py-2 rounded-xl text-xs font-bold transition-all duration-200 shrink-0">
                            <i data-lucide="users" class="w-4 h-4"></i>
                            {{ __('Top Giới Thiệu') }}
                        </button>
                        @endif

                        @if($topBalanceEnabled)
                        <button @click="activeTab = 'balance'" 
                                :class="activeTab === 'balance' ? 'bg-shopee text-white shadow-md shadow-orange-500/20' : 'text-gray-600 hover:text-gray-900 dark:text-slate-400 dark:hover:text-white hover:bg-gray-50 dark:hover:bg-slate-800/50'"
                                class="flex items-center justify-center gap-2 px-4 sm:px-5 py-2 rounded-xl text-xs font-bold transition-all duration-200 shrink-0">
                            <i data-lucide="piggy-bank" class="w-4.5 h-4.5"></i>
                            {{ __('Top Số Dư') }}
                        </button>
                        @endif
                    </div>
                </div>
                @endif
            @endif
        </div>
    </div>

    <!-- KHU VỰC HIỂN THỊ NỘI DUNG BXH DẠNG LIST (AlpineJS Đồng bộ trạng thái tab từ Header) -->
    @if(!empty($activeTab))
    <div x-data="{ activeTab: '{{ $activeTab }}' }" @tab-changed.window="activeTab = $event.detail" class="max-w-4xl mx-auto">
        
        <!-- 1. CỘT TOP ĐƠN HÀNG -->
        @if($topOrdersEnabled)
        <div x-show="activeTab === 'orders'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0" class="bg-white dark:bg-slate-900 rounded-3xl border border-gray-150/60 dark:border-slate-800/80 shadow-xl shadow-gray-100/40 dark:shadow-none p-6 space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between border-b border-gray-100 dark:border-slate-800/80 pb-4 gap-2">
                <h2 class="font-extrabold text-gray-950 dark:text-white text-base flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-xl bg-orange-100/70 dark:bg-orange-950/40 flex items-center justify-center text-shopee shrink-0">
                        <i data-lucide="package" class="w-4.5 h-4.5"></i>
                    </div>
                    {{ __('Bảng Xếp Hạng Top Đơn Hàng') }}
                </h2>
                <span class="inline-flex px-3 py-1 rounded-full text-[10px] font-extrabold bg-orange-50 text-orange-600 border border-orange-100/60 dark:bg-orange-950/20 dark:border-orange-900/30 self-start sm:self-center items-center gap-1">
                    ✓ {{ __('Tính đơn hàng đã được Admin duyệt thành công') }}
                </span>
            </div>

            <div class="space-y-3 pt-2">
                @forelse($topOrders as $index => $item)
                    @php
                        $rank = $index + 1;
                        $isTop3 = $rank <= 3;
                        
                        $isMe = false;
                        if (auth()->check()) {
                            $isMe = $item->user_id === auth()->id();
                        }
                    @endphp
                    
                    @if($rank === 1)
                        <!-- HẠNG 1: ĐẶC BIỆT NỔI BẬT -->
                        <div class="flex items-center justify-between p-4 bg-gradient-to-r from-amber-500/5 via-amber-500/10 to-transparent dark:from-amber-950/20 dark:to-transparent rounded-2xl border-2 border-amber-400/40 dark:border-amber-500/30 relative overflow-hidden shadow-md shadow-amber-500/5">
                            <div class="absolute top-0 left-0 bg-amber-500 text-[8px] text-white font-black px-2 py-0.5 rounded-br-lg rounded-tl-xl uppercase tracking-wider">#1</div>
                            <div class="flex items-center gap-4">
                                <!-- Huy chương Hạng 1 -->
                                <div class="w-12 h-12 rounded-full bg-white dark:bg-slate-850 flex items-center justify-center shadow-md border border-amber-200 shrink-0 relative">
                                    <i data-lucide="crown" class="w-6 h-6 text-amber-500 fill-amber-400"></i>
                                </div>

                                <!-- Avatar -->
                                <div class="w-10 h-10 rounded-full flex items-center justify-center font-black text-amber-950 bg-amber-300 ring-2 ring-white dark:ring-slate-900 shadow shrink-0">
                                    {{ substr($item->user->name ?? 'U', 0, 1) }}
                                </div>

                                <!-- Thông tin thành viên -->
                                <div>
                                    <div class="flex items-center gap-2">
                                        <span class="font-black text-gray-950 dark:text-white text-base block leading-none">
                                            {{ $maskName($item->user->name ?? __('Thành viên ẩn danh')) }}
                                        </span>
                                        @if($isMe)
                                            <span class="px-2 py-0.5 rounded-full text-[9px] font-bold bg-orange-100 text-orange-600 dark:bg-orange-950/40 dark:text-orange-400 border border-orange-200/20 uppercase tracking-wide">YOU</span>
                                        @endif
                                    </div>
                                    <span class="text-[10px] text-gray-400 dark:text-slate-500 font-semibold block mt-1">
                                        {{ __('Tham gia: :date', ['date' => $item->user->created_at ? $item->user->created_at->format('d/m/Y') : '']) }}
                                    </span>
                                </div>
                            </div>

                            <!-- Chỉ số bên phải -->
                            <div class="text-right">
                                <span class="text-[9px] text-gray-400 dark:text-slate-500 font-extrabold uppercase block tracking-wider mb-1">
                                    {{ __('ĐƠN HÀNG') }}
                                </span>
                                <span class="text-2xl font-black text-shopee dark:text-shopee-light block leading-none">
                                    {{ $item->total_orders }}
                                </span>
                            </div>
                        </div>
                    @else
                        <!-- HẠNG TỪ 2 TRỞ ĐI -->
                        <div class="flex items-center justify-between p-4 bg-white dark:bg-slate-900 rounded-2xl border border-gray-100/80 dark:border-slate-800/80 shadow-sm hover:shadow hover:border-gray-200 transition-all duration-200">
                            <div class="flex items-center gap-4">
                                <!-- Vòng tròn thứ hạng -->
                                <div class="w-8 h-8 rounded-full bg-gray-50 border border-gray-100 dark:bg-slate-800 dark:border-slate-700 flex items-center justify-center font-bold text-xs text-gray-500 dark:text-slate-400 shrink-0">
                                    {{ $rank }}
                                </div>

                                <!-- Avatar -->
                                <div class="w-10 h-10 rounded-full flex items-center justify-center font-black text-sm shrink-0 border
                                    {{ $rank === 2 ? 'bg-blue-100 text-blue-700 border-blue-200' :
                                       ($rank === 3 ? 'bg-indigo-100 text-indigo-700 border-indigo-200' : 'bg-gray-100 dark:bg-slate-800 text-gray-600 dark:text-slate-400 border-gray-250 dark:border-slate-700') }}">
                                    {{ substr($item->user->name ?? 'U', 0, 1) }}
                                </div>

                                <!-- Thông tin thành viên -->
                                <div>
                                    <div class="flex items-center gap-2">
                                        <span class="font-bold text-gray-800 dark:text-slate-200 text-sm block leading-none">
                                            {{ $maskName($item->user->name ?? __('Thành viên ẩn danh')) }}
                                        </span>
                                        @if($isMe)
                                            <span class="px-2 py-0.5 rounded-full text-[9px] font-bold bg-orange-100 text-orange-600 dark:bg-orange-950/40 dark:text-orange-400 border border-orange-200/20 uppercase tracking-wide">YOU</span>
                                        @endif
                                    </div>
                                    <span class="text-[10px] text-gray-400 dark:text-slate-500 font-semibold block mt-1">
                                        {{ __('Tham gia: :date', ['date' => $item->user->created_at ? $item->user->created_at->format('d/m/Y') : '']) }}
                                    </span>
                                </div>
                            </div>

                            <!-- Chỉ số bên phải -->
                            <div class="text-right">
                                <span class="text-[9px] text-gray-400 dark:text-slate-500 font-extrabold uppercase block tracking-wider mb-1">
                                    {{ __('ĐƠN HÀNG') }}
                                </span>
                                <span class="text-lg font-black text-gray-800 dark:text-slate-200 block leading-none">
                                    {{ $item->total_orders }}
                                </span>
                            </div>
                        </div>
                    @endif
                @empty
                    <div class="flex flex-col items-center justify-center py-16 text-center space-y-4">
                        <div class="w-16 h-16 rounded-full bg-gray-50 dark:bg-slate-800/60 flex items-center justify-center text-gray-400">
                            <i data-lucide="inbox" class="w-8 h-8"></i>
                        </div>
                        <p class="text-sm text-gray-450 dark:text-slate-500 font-medium">{{ __('Chưa có dữ liệu xếp hạng.') }}</p>
                    </div>
                @endforelse
            </div>
        </div>
        @endif

        <!-- 2. TAB TOP TIỀN HOÀN -->
        @if($topCashbackEnabled)
        <div x-show="activeTab === 'cashback'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0" class="bg-white dark:bg-slate-900 rounded-3xl border border-gray-150/60 dark:border-slate-800/80 shadow-xl shadow-gray-100/40 dark:shadow-none p-6 space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between border-b border-gray-100 dark:border-slate-800/80 pb-4 gap-2">
                <h2 class="font-extrabold text-gray-950 dark:text-white text-base flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-xl bg-orange-100/70 dark:bg-orange-950/40 flex items-center justify-center text-shopee shrink-0">
                        <i data-lucide="wallet" class="w-4.5 h-4.5"></i>
                    </div>
                    {{ __('Bảng Xếp Hạng Top Tiền Hoàn') }}
                </h2>
                <span class="inline-flex px-3 py-1 rounded-full text-[10px] font-extrabold bg-orange-50 text-orange-600 border border-orange-100/60 dark:bg-orange-950/20 dark:border-orange-900/30 self-start sm:self-center items-center gap-1">
                    ✓ {{ __('Tính tổng hoa hồng hoàn lại cho người mua') }}
                </span>
            </div>

            <div class="space-y-3 pt-2">
                @forelse($topCashback as $index => $item)
                    @php
                        $rank = $index + 1;
                        $isTop3 = $rank <= 3;
                        
                        $isMe = false;
                        if (auth()->check()) {
                            $isMe = $item->id === auth()->id();
                        }
                    @endphp
                    
                    @if($rank === 1)
                        <!-- HẠNG 1 -->
                        <div class="flex items-center justify-between p-4 bg-gradient-to-r from-amber-500/5 via-amber-500/10 to-transparent dark:from-amber-950/20 dark:to-transparent rounded-2xl border-2 border-amber-400/40 dark:border-amber-500/30 relative overflow-hidden shadow-md shadow-amber-500/5">
                            <div class="absolute top-0 left-0 bg-amber-500 text-[8px] text-white font-black px-2 py-0.5 rounded-br-lg rounded-tl-xl uppercase tracking-wider">#1</div>
                            <div class="flex items-center gap-4">
                                <div class="w-12 h-12 rounded-full bg-white dark:bg-slate-855 flex items-center justify-center shadow-md border border-amber-200 shrink-0 relative">
                                    <i data-lucide="crown" class="w-6 h-6 text-amber-500 fill-amber-400"></i>
                                </div>
                                <div class="w-10 h-10 rounded-full flex items-center justify-center font-black text-amber-950 bg-amber-300 ring-2 ring-white dark:ring-slate-900 shadow shrink-0">
                                    {{ substr($item->name ?? 'U', 0, 1) }}
                                </div>
                                <div>
                                    <div class="flex items-center gap-2">
                                        <span class="font-black text-gray-950 dark:text-white text-base block leading-none">
                                            {{ $maskName($item->name ?? __('Thành viên ẩn danh')) }}
                                        </span>
                                        @if($isMe)
                                            <span class="px-2 py-0.5 rounded-full text-[9px] font-bold bg-orange-100 text-orange-600 dark:bg-orange-950/40 dark:text-orange-400 border border-orange-200/20 uppercase tracking-wide">YOU</span>
                                        @endif
                                    </div>
                                    <span class="text-[10px] text-gray-400 dark:text-slate-500 font-semibold block mt-1">
                                        {{ __('Tham gia: :date', ['date' => $item->created_at ? $item->created_at->format('d/m/Y') : '']) }}
                                    </span>
                                </div>
                            </div>
                            <div class="text-right">
                                <span class="text-[9px] text-gray-400 dark:text-slate-500 font-extrabold uppercase block tracking-wider mb-1">
                                    {{ __('TIỀN HOÀN') }}
                                </span>
                                <span class="text-2xl font-black text-shopee dark:text-shopee-light block leading-none">
                                    {{ \App\Helpers\CurrencyHelper::format($item->total_cashback) }}
                                </span>
                            </div>
                        </div>
                    @else
                        <!-- HẠNG 2, 3... -->
                        <div class="flex items-center justify-between p-4 bg-white dark:bg-slate-900 rounded-2xl border border-gray-100/80 dark:border-slate-800/80 shadow-sm hover:shadow hover:border-gray-200 transition-all duration-200">
                            <div class="flex items-center gap-4">
                                <div class="w-8 h-8 rounded-full bg-gray-50 border border-gray-100 dark:bg-slate-800 dark:border-slate-700 flex items-center justify-center font-bold text-xs text-gray-500 dark:text-slate-400 shrink-0">
                                    {{ $rank }}
                                </div>
                                <div class="w-10 h-10 rounded-full flex items-center justify-center font-black text-sm shrink-0 border
                                    {{ $rank === 2 ? 'bg-blue-100 text-blue-700 border-blue-200' :
                                       ($rank === 3 ? 'bg-indigo-100 text-indigo-700 border-indigo-200' : 'bg-gray-100 dark:bg-slate-800 text-gray-600 dark:text-slate-400 border-gray-250 dark:border-slate-700') }}">
                                    {{ substr($item->name ?? 'U', 0, 1) }}
                                </div>
                                <div>
                                    <div class="flex items-center gap-2">
                                        <span class="font-bold text-gray-800 dark:text-slate-200 text-sm block leading-none">
                                            {{ $maskName($item->name ?? __('Thành viên ẩn danh')) }}
                                        </span>
                                        @if($isMe)
                                            <span class="px-2 py-0.5 rounded-full text-[9px] font-bold bg-orange-100 text-orange-600 dark:bg-orange-950/40 dark:text-orange-400 border border-orange-200/20 uppercase tracking-wide">YOU</span>
                                        @endif
                                    </div>
                                    <span class="text-[10px] text-gray-400 dark:text-slate-500 font-semibold block mt-1">
                                        {{ __('Tham gia: :date', ['date' => $item->created_at ? $item->created_at->format('d/m/Y') : '']) }}
                                    </span>
                                </div>
                            </div>
                            <div class="text-right">
                                <span class="text-[9px] text-gray-400 dark:text-slate-500 font-extrabold uppercase block tracking-wider mb-1">
                                    {{ __('TIỀN HOÀN') }}
                                </span>
                                <span class="text-lg font-black text-gray-800 dark:text-slate-200 block leading-none">
                                    {{ \App\Helpers\CurrencyHelper::format($item->total_cashback) }}
                                </span>
                            </div>
                        </div>
                    @endif
                @empty
                    <div class="flex flex-col items-center justify-center py-16 text-center space-y-4">
                        <div class="w-16 h-16 rounded-full bg-gray-50 dark:bg-slate-800/60 flex items-center justify-center text-gray-400">
                            <i data-lucide="inbox" class="w-8 h-8"></i>
                        </div>
                        <p class="text-sm text-gray-450 dark:text-slate-500 font-medium">{{ __('Chưa có dữ liệu xếp hạng.') }}</p>
                    </div>
                @endforelse
            </div>
        </div>
        @endif

        <!-- 3. TAB TOP ĐIỂM DANH -->
        @if($topCheckinEnabled)
        <div x-show="activeTab === 'checkin'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0" class="bg-white dark:bg-slate-900 rounded-3xl border border-gray-150/60 dark:border-slate-800/80 shadow-xl shadow-gray-100/40 dark:shadow-none p-6 space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between border-b border-gray-100 dark:border-slate-800/80 pb-4 gap-2">
                <h2 class="font-extrabold text-gray-950 dark:text-white text-base flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-xl bg-orange-100/70 dark:bg-orange-950/40 flex items-center justify-center text-shopee shrink-0">
                        <i data-lucide="flame" class="w-4.5 h-4.5"></i>
                    </div>
                    {{ __('Bảng Xếp Hạng Top Điểm Danh') }}
                </h2>
                <span class="inline-flex px-3 py-1 rounded-full text-[10px] font-extrabold bg-orange-50 text-orange-600 border border-orange-100/60 dark:bg-orange-950/20 dark:border-orange-900/30 self-start sm:self-center items-center gap-1">
                    ✓ {{ __('Chuỗi điểm danh chuyên cần liên tiếp dài nhất') }}
                </span>
            </div>

            <div class="space-y-3 pt-2">
                @forelse($topCheckin as $index => $item)
                    @php
                        $rank = $index + 1;
                        $isTop3 = $rank <= 3;
                        
                        $isMe = false;
                        if (auth()->check()) {
                            $isMe = $item->user_id === auth()->id();
                        }
                    @endphp
                    
                    @if($rank === 1)
                        <!-- HẠNG 1 -->
                        <div class="flex items-center justify-between p-4 bg-gradient-to-r from-amber-500/5 via-amber-500/10 to-transparent dark:from-amber-950/20 dark:to-transparent rounded-2xl border-2 border-amber-400/40 dark:border-amber-500/30 relative overflow-hidden shadow-md shadow-amber-500/5">
                            <div class="absolute top-0 left-0 bg-amber-500 text-[8px] text-white font-black px-2 py-0.5 rounded-br-lg rounded-tl-xl uppercase tracking-wider">#1</div>
                            <div class="flex items-center gap-4">
                                <div class="w-12 h-12 rounded-full bg-white dark:bg-slate-855 flex items-center justify-center shadow-md border border-amber-200 shrink-0 relative">
                                    <i data-lucide="crown" class="w-6 h-6 text-amber-500 fill-amber-400"></i>
                                </div>
                                <div class="w-10 h-10 rounded-full flex items-center justify-center font-black text-amber-955 bg-amber-300 ring-2 ring-white dark:ring-slate-900 shadow shrink-0">
                                    {{ substr($item->user->name ?? 'U', 0, 1) }}
                                </div>
                                <div>
                                    <div class="flex items-center gap-2">
                                        <span class="font-black text-gray-950 dark:text-white text-base block leading-none">
                                            {{ $maskName($item->user->name ?? __('Thành viên ẩn danh')) }}
                                        </span>
                                        @if($isMe)
                                            <span class="px-2 py-0.5 rounded-full text-[9px] font-bold bg-orange-100 text-orange-600 dark:bg-orange-950/40 dark:text-orange-400 border border-orange-200/20 uppercase tracking-wide">YOU</span>
                                        @endif
                                    </div>
                                    <span class="text-[10px] text-gray-400 dark:text-slate-500 font-semibold block mt-1">
                                        {{ __('Tham gia: :date', ['date' => $item->user->created_at ? $item->user->created_at->format('d/m/Y') : '']) }}
                                    </span>
                                </div>
                            </div>
                            <div class="text-right flex items-center gap-2 justify-end">
                                <div>
                                    <span class="text-[9px] text-gray-400 dark:text-slate-500 font-extrabold uppercase block tracking-wider mb-1">
                                        {{ __('CHUỖI') }}
                                    </span>
                                    <span class="text-2xl font-black text-shopee dark:text-shopee-light block leading-none">
                                        {{ $item->max_streak }}
                                    </span>
                                </div>
                                <i data-lucide="flame" class="w-6 h-6 text-orange-500 fill-orange-400 shrink-0"></i>
                            </div>
                        </div>
                    @else
                        <!-- HẠNG 2, 3... -->
                        <div class="flex items-center justify-between p-4 bg-white dark:bg-slate-900 rounded-2xl border border-gray-100/80 dark:border-slate-800/80 shadow-sm hover:shadow hover:border-gray-200 transition-all duration-200">
                            <div class="flex items-center gap-4">
                                <div class="w-8 h-8 rounded-full bg-gray-50 border border-gray-100 dark:bg-slate-800 dark:border-slate-700 flex items-center justify-center font-bold text-xs text-gray-500 dark:text-slate-400 shrink-0">
                                    {{ $rank }}
                                </div>
                                <div class="w-10 h-10 rounded-full flex items-center justify-center font-black text-sm shrink-0 border
                                    {{ $rank === 2 ? 'bg-blue-100 text-blue-700 border-blue-200' :
                                       ($rank === 3 ? 'bg-indigo-100 text-indigo-700 border-indigo-200' : 'bg-gray-100 dark:bg-slate-800 text-gray-600 dark:text-slate-400 border-gray-255 dark:border-slate-700') }}">
                                    {{ substr($item->user->name ?? 'U', 0, 1) }}
                                </div>
                                <div>
                                    <div class="flex items-center gap-2">
                                        <span class="font-bold text-gray-800 dark:text-slate-200 text-sm block leading-none">
                                            {{ $maskName($item->user->name ?? __('Thành viên ẩn danh')) }}
                                        </span>
                                        @if($isMe)
                                            <span class="px-2 py-0.5 rounded-full text-[9px] font-bold bg-orange-100 text-orange-600 dark:bg-orange-950/40 dark:text-orange-400 border border-orange-200/20 uppercase tracking-wide">YOU</span>
                                        @endif
                                    </div>
                                    <span class="text-[10px] text-gray-400 dark:text-slate-500 font-semibold block mt-1">
                                        {{ __('Tham gia: :date', ['date' => $item->user->created_at ? $item->user->created_at->format('d/m/Y') : '']) }}
                                    </span>
                                </div>
                            </div>
                            <div class="text-right flex items-center gap-1.5 justify-end">
                                <div>
                                    <span class="text-[9px] text-gray-400 dark:text-slate-500 font-extrabold uppercase block tracking-wider mb-1">
                                        {{ __('CHUỖI') }}
                                    </span>
                                    <span class="text-lg font-black text-gray-800 dark:text-slate-200 block leading-none">
                                        {{ $item->max_streak }}
                                    </span>
                                </div>
                                <i data-lucide="flame" class="w-4 h-4 text-orange-500 shrink-0"></i>
                            </div>
                        </div>
                    @endif
                @empty
                    <div class="flex flex-col items-center justify-center py-16 text-center space-y-4">
                        <div class="w-16 h-16 rounded-full bg-gray-50 dark:bg-slate-800/60 flex items-center justify-center text-gray-400">
                            <i data-lucide="inbox" class="w-8 h-8"></i>
                        </div>
                        <p class="text-sm text-gray-450 dark:text-slate-500 font-medium">{{ __('Chưa có dữ liệu xếp hạng.') }}</p>
                    </div>
                @endforelse
            </div>
        </div>
        @endif

        <!-- 4. TAB TOP GIỚI THIỆU -->
        @if($topReferralEnabled)
        <div x-show="activeTab === 'referrals'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0" class="bg-white dark:bg-slate-900 rounded-3xl border border-gray-150/60 dark:border-slate-800/80 shadow-xl shadow-gray-100/40 dark:shadow-none p-6 space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between border-b border-gray-100 dark:border-slate-800/80 pb-4 gap-2">
                <h2 class="font-extrabold text-gray-950 dark:text-white text-base flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-xl bg-orange-100/70 dark:bg-orange-950/40 flex items-center justify-center text-shopee shrink-0">
                        <i data-lucide="users" class="w-4.5 h-4.5"></i>
                    </div>
                    {{ __('Bảng Xếp Hạng Top Giới Thiệu') }}
                </h2>
                <span class="inline-flex px-3 py-1 rounded-full text-[10px] font-extrabold bg-orange-50 text-orange-600 border border-orange-100/60 dark:bg-orange-950/20 dark:border-orange-900/30 self-start sm:self-center items-center gap-1">
                    ✓ {{ __('Tính số lượng F1 giới thiệu trực tiếp hoạt động') }}
                </span>
            </div>

            <div class="space-y-3 pt-2">
                @forelse($topReferral as $index => $item)
                    @php
                        $rank = $index + 1;
                        $isTop3 = $rank <= 3;
                        
                        $isMe = false;
                        if (auth()->check()) {
                            $isMe = $item->user_id === auth()->id();
                        }
                    @endphp
                    
                    @if($rank === 1)
                        <!-- HẠNG 1 -->
                        <div class="flex items-center justify-between p-4 bg-gradient-to-r from-amber-500/5 via-amber-500/10 to-transparent dark:from-amber-950/20 dark:to-transparent rounded-2xl border-2 border-amber-400/40 dark:border-amber-500/30 relative overflow-hidden shadow-md shadow-amber-500/5">
                            <div class="absolute top-0 left-0 bg-amber-500 text-[8px] text-white font-black px-2 py-0.5 rounded-br-lg rounded-tl-xl uppercase tracking-wider">#1</div>
                            <div class="flex items-center gap-4">
                                <div class="w-12 h-12 rounded-full bg-white dark:bg-slate-855 flex items-center justify-center shadow-md border border-amber-200 shrink-0 relative">
                                    <i data-lucide="crown" class="w-6 h-6 text-amber-500 fill-amber-400"></i>
                                </div>
                                <div class="w-10 h-10 rounded-full flex items-center justify-center font-black text-amber-955 bg-amber-300 ring-2 ring-white dark:ring-slate-900 shadow shrink-0">
                                    {{ substr($item->user->name ?? 'U', 0, 1) }}
                                </div>
                                <div>
                                    <div class="flex items-center gap-2">
                                        <span class="font-black text-gray-950 dark:text-white text-base block leading-none">
                                            {{ $maskName($item->user->name ?? __('Thành viên ẩn danh')) }}
                                        </span>
                                        @if($isMe)
                                            <span class="px-2 py-0.5 rounded-full text-[9px] font-bold bg-orange-100 text-orange-600 dark:bg-orange-950/40 dark:text-orange-400 border border-orange-200/20 uppercase tracking-wide">YOU</span>
                                        @endif
                                    </div>
                                    <span class="text-[10px] text-gray-400 dark:text-slate-500 font-semibold block mt-1">
                                        {{ __('Tham gia: :date', ['date' => $item->user->created_at ? $item->user->created_at->format('d/m/Y') : '']) }}
                                    </span>
                                </div>
                            </div>
                            <div class="text-right">
                                <span class="text-[9px] text-gray-400 dark:text-slate-500 font-extrabold uppercase block tracking-wider mb-1">
                                    {{ __('BẠN BÈ') }}
                                </span>
                                <span class="text-2xl font-black text-shopee dark:text-shopee-light block leading-none">
                                    {{ $item->total_referrals }}
                                </span>
                            </div>
                        </div>
                    @else
                        <!-- HẠNG 2, 3... -->
                        <div class="flex items-center justify-between p-4 bg-white dark:bg-slate-900 rounded-2xl border border-gray-100/80 dark:border-slate-800/80 shadow-sm hover:shadow hover:border-gray-200 transition-all duration-200">
                            <div class="flex items-center gap-4">
                                <div class="w-8 h-8 rounded-full bg-gray-50 border border-gray-100 dark:bg-slate-800 dark:border-slate-700 flex items-center justify-center font-bold text-xs text-gray-500 dark:text-slate-400 shrink-0">
                                    {{ $rank }}
                                </div>
                                <div class="w-10 h-10 rounded-full flex items-center justify-center font-black text-sm shrink-0 border
                                    {{ $rank === 2 ? 'bg-blue-100 text-blue-700 border-blue-200' :
                                       ($rank === 3 ? 'bg-indigo-100 text-indigo-700 border-indigo-200' : 'bg-gray-100 dark:bg-slate-800 text-gray-600 dark:text-slate-400 border-gray-255 dark:border-slate-700') }}">
                                    {{ substr($item->user->name ?? 'U', 0, 1) }}
                                </div>
                                <div>
                                    <div class="flex items-center gap-2">
                                        <span class="font-bold text-gray-800 dark:text-slate-200 text-sm block leading-none">
                                            {{ $maskName($item->user->name ?? __('Thành viên ẩn danh')) }}
                                        </span>
                                        @if($isMe)
                                            <span class="px-2 py-0.5 rounded-full text-[9px] font-bold bg-orange-100 text-orange-600 dark:bg-orange-950/40 dark:text-orange-400 border border-orange-200/20 uppercase tracking-wide">YOU</span>
                                        @endif
                                    </div>
                                    <span class="text-[10px] text-gray-400 dark:text-slate-500 font-semibold block mt-1">
                                        {{ __('Tham gia: :date', ['date' => $item->user->created_at ? $item->user->created_at->format('d/m/Y') : '']) }}
                                    </span>
                                </div>
                            </div>
                            <div class="text-right">
                                <span class="text-[9px] text-gray-400 dark:text-slate-500 font-extrabold uppercase block tracking-wider mb-1">
                                    {{ __('BẠN BÈ') }}
                                </span>
                                <span class="text-lg font-black text-gray-800 dark:text-slate-200 block leading-none">
                                    {{ $item->total_referrals }}
                                </span>
                            </div>
                        </div>
                    @endif
                @empty
                    <div class="flex flex-col items-center justify-center py-16 text-center space-y-4">
                        <div class="w-16 h-16 rounded-full bg-gray-50 dark:bg-slate-800/60 flex items-center justify-center text-gray-400">
                            <i data-lucide="inbox" class="w-8 h-8"></i>
                        </div>
                        <p class="text-sm text-gray-450 dark:text-slate-500 font-medium">{{ __('Chưa có dữ liệu xếp hạng.') }}</p>
                    </div>
                @endforelse
            </div>
        </div>
        @endif
        <!-- 5. TAB TOP SỐ DƯ KHẢ DỤNG -->
        @if($topBalanceEnabled)
        <div x-show="activeTab === 'balance'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0" class="bg-white dark:bg-slate-900 rounded-3xl border border-gray-150/60 dark:border-slate-800/80 shadow-xl shadow-gray-100/40 dark:shadow-none p-6 space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between border-b border-gray-100 dark:border-slate-800/80 pb-4 gap-2">
                <h2 class="font-extrabold text-gray-950 dark:text-white text-base flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-xl bg-orange-100/70 dark:bg-orange-950/40 flex items-center justify-center text-shopee shrink-0">
                        <i data-lucide="piggy-bank" class="w-4.5 h-4.5"></i>
                    </div>
                    {{ __('Bảng Xếp Hạng Top Số Dư') }}
                </h2>
                <span class="inline-flex px-3 py-1 rounded-full text-[10px] font-extrabold bg-orange-50 text-orange-600 border border-orange-100/60 dark:bg-orange-950/20 dark:border-orange-900/30 self-start sm:self-center items-center gap-1">
                    ✓ {{ __('Tính số dư ví khả dụng hiện tại') }}
                </span>
            </div>

            <div class="space-y-3 pt-2">
                @forelse($topBalance as $index => $item)
                    @php
                        $rank = $index + 1;
                        $isTop3 = $rank <= 3;
                        
                        $isMe = false;
                        if (auth()->check()) {
                            $isMe = $item->id === auth()->id();
                        }
                    @endphp
                    
                    @if($rank === 1)
                        <!-- HẠNG 1 -->
                        <div class="flex items-center justify-between p-4 bg-gradient-to-r from-amber-500/5 via-amber-500/10 to-transparent dark:from-amber-950/20 dark:to-transparent rounded-2xl border-2 border-amber-400/40 dark:border-amber-500/30 relative overflow-hidden shadow-md shadow-amber-500/5">
                            <div class="absolute top-0 left-0 bg-amber-500 text-[8px] text-white font-black px-2 py-0.5 rounded-br-lg rounded-tl-xl uppercase tracking-wider">#1</div>
                            <div class="flex items-center gap-4">
                                <div class="w-12 h-12 rounded-full bg-white dark:bg-slate-800 flex items-center justify-center shadow-md border border-amber-200 shrink-0 relative">
                                    <i data-lucide="crown" class="w-6 h-6 text-amber-500 fill-amber-400"></i>
                                </div>
                                <div class="w-10 h-10 rounded-full flex items-center justify-center font-black text-amber-955 bg-amber-300 ring-2 ring-white dark:ring-slate-900 shadow shrink-0">
                                    {{ substr($item->name ?? 'U', 0, 1) }}
                                </div>
                                <div>
                                    <div class="flex items-center gap-2">
                                        <span class="font-black text-gray-950 dark:text-white text-base block leading-none">
                                            {{ $maskName($item->name ?? __('Thành viên ẩn danh')) }}
                                        </span>
                                        @if($isMe)
                                            <span class="px-2 py-0.5 rounded-full text-[9px] font-bold bg-orange-100 text-orange-600 dark:bg-orange-950/40 dark:text-orange-400 border border-orange-200/20 uppercase tracking-wide">YOU</span>
                                        @endif
                                    </div>
                                    <span class="text-[10px] text-gray-400 dark:text-slate-500 font-semibold block mt-1">
                                        {{ __('Tham gia: :date', ['date' => $item->created_at ? $item->created_at->format('d/m/Y') : '']) }}
                                    </span>
                                </div>
                            </div>
                            <div class="text-right">
                                <span class="text-[9px] text-gray-400 dark:text-slate-500 font-extrabold uppercase block tracking-wider mb-1">
                                    {{ __('SỐ DƯ') }}
                                </span>
                                <span class="text-2xl font-black text-shopee dark:text-shopee-light block leading-none">
                                    {{ \App\Helpers\CurrencyHelper::format($item->balance) }}
                                </span>
                            </div>
                        </div>
                    @else
                        <!-- HẠNG 2, 3... -->
                        <div class="flex items-center justify-between p-4 bg-white dark:bg-slate-900 rounded-2xl border border-gray-150/60 dark:border-slate-800/80 shadow-sm hover:shadow hover:border-gray-200 dark:hover:border-slate-800 transition-all duration-200">
                            <div class="flex items-center gap-4">
                                <div class="w-8 h-8 rounded-full bg-gray-50 border border-gray-100 dark:bg-slate-800 dark:border-slate-700 flex items-center justify-center font-bold text-xs text-gray-500 dark:text-slate-400 shrink-0">
                                    {{ $rank }}
                                </div>
                                <div class="w-10 h-10 rounded-full flex items-center justify-center font-black text-sm shrink-0 border
                                    {{ $rank === 2 ? 'bg-blue-100 text-blue-700 border-blue-250 dark:bg-blue-950/30 dark:text-blue-400 dark:border-blue-900/50' :
                                       ($rank === 3 ? 'bg-indigo-100 text-indigo-700 border-indigo-200 dark:bg-indigo-950/30 dark:text-indigo-400 dark:border-indigo-900/50' : 'bg-gray-100 dark:bg-slate-800 text-gray-600 dark:text-slate-400 border-gray-200 dark:border-slate-700') }}">
                                    {{ substr($item->name ?? 'U', 0, 1) }}
                                </div>
                                <div>
                                    <div class="flex items-center gap-2">
                                        <span class="font-bold text-gray-800 dark:text-slate-200 text-sm block leading-none">
                                            {{ $maskName($item->name ?? __('Thành viên ẩn danh')) }}
                                        </span>
                                        @if($isMe)
                                            <span class="px-2 py-0.5 rounded-full text-[9px] font-bold bg-orange-100 text-orange-600 dark:bg-orange-950/40 dark:text-orange-400 border border-orange-200/20 uppercase tracking-wide">YOU</span>
                                        @endif
                                    </div>
                                    <span class="text-[10px] text-gray-400 dark:text-slate-500 font-semibold block mt-1">
                                        {{ __('Tham gia: :date', ['date' => $item->created_at ? $item->created_at->format('d/m/Y') : '']) }}
                                    </span>
                                </div>
                            </div>
                            <div class="text-right">
                                <span class="text-[9px] text-gray-400 dark:text-slate-500 font-extrabold uppercase block tracking-wider mb-1">
                                    {{ __('SỐ DƯ') }}
                                </span>
                                <span class="text-lg font-black text-gray-800 dark:text-slate-200 block leading-none">
                                    {{ \App\Helpers\CurrencyHelper::format($item->balance) }}
                                </span>
                            </div>
                        </div>
                    @endif
                @empty
                    <div class="flex flex-col items-center justify-center py-16 text-center space-y-4">
                        <div class="w-16 h-16 rounded-full bg-gray-50 dark:bg-slate-800/60 flex items-center justify-center text-gray-400">
                            <i data-lucide="inbox" class="w-8 h-8"></i>
                        </div>
                        <p class="text-sm text-gray-450 dark:text-slate-500 font-medium">{{ __('Chưa có dữ liệu xếp hạng.') }}</p>
                    </div>
                @endforelse
            </div>
        </div>
        @endif

    </div>
    @else
    <div class="text-center py-16 max-w-md mx-auto bg-white dark:bg-slate-900 rounded-3xl border border-gray-150/60 dark:border-slate-800 shadow-lg p-8">
        <div class="w-16 h-16 rounded-full bg-red-50 dark:bg-red-950/20 text-red-500 flex items-center justify-center mx-auto mb-4">
            <i data-lucide="alert-triangle" class="w-8 h-8"></i>
        </div>
        <h3 class="font-extrabold text-gray-900 dark:text-white text-base">{{ __('Không có bảng xếp hạng nào khả dụng') }}</h3>
        <p class="text-xs text-gray-500 dark:text-slate-450 mt-2">{{ __('Quản trị viên hiện đã tắt tất cả các bảng xếp hạng cụ thể trong trang cấu hình hệ thống.') }}</p>
    </div>
    @endif
</div>
@endsection
