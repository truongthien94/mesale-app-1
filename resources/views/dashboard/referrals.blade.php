@extends('layouts.app')

@section('title', __('Tiếp Thị Liên Kết Nhận Hoa Hồng MLM') . ' - ' . $siteName)

@section('content')
<!-- Container chính sử dụng AlpineJS điều khiển sự kiện sao chép link liên kết và bộ lọc dữ liệu -->
<div class="px-4 mx-auto max-w-7xl sm:px-6 lg:px-8 py-6 sm:py-10" x-data="referralHandler({ copySuccessMsg: '{{ __('Đã sao chép liên kết giới thiệu vào bộ nhớ tạm!') }}', copyErrorMsg: '{{ __('Không thể sao chép liên kết. Vui lòng sao chép thủ công.') }}', loadErrorMsg: '{{ __('Có lỗi xảy ra khi tải dữ liệu.') }}', baseUrl: '{{ route('referrals') }}', initLevel: '{{ request('level') }}', initStatus: '{{ request('status') }}' })">
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
        
        <!-- Sidebar - Cột Danh mục quản lý tài khoản bên trái (Ẩn trên mobile để tối ưu không gian hiển thị) -->
        <div class="hidden lg:block lg:col-span-3">
            @include('dashboard.sidebar')
        </div>

        <!-- Chi tiết - Khu vực hiển thị thông tin tiếp thị liên kết bên phải -->
        <div class="lg:col-span-9 space-y-6">
            {{-- Nhắc nhở thành viên bổ sung email để bảo vệ tài khoản --}}
            @include('components.email_update_notice')
            
            <!-- Banner tiêu đề trang được thiết kế dạng card cao cấp bo góc 24px, tích hợp hình minh họa 3D -->
            <div class="relative flex items-center justify-between p-5 rounded-2xl bg-gradient-to-r from-[#fff0ed] to-[#fff5f3] dark:from-slate-900/40 dark:to-slate-900/20 overflow-hidden border border-orange-100/50 dark:border-orange-950/20">
                <div class="flex items-center gap-4">
                    <!-- Icon nhóm người được đặt trong khối nền màu cam nhạt bo góc -->
                    <div class="w-14 h-14 flex items-center justify-center bg-[#fff0ed] dark:bg-orange-950/30 text-shopee rounded-2xl shrink-0 shadow-sm border border-orange-100/30">
                        <i data-lucide="users" class="w-7 h-7 text-shopee"></i>
                    </div>
                    <div>
                        <!-- Tiêu đề in đậm, viết hoa theo đúng mockup ảnh -->
                        <h1 class="text-base sm:text-lg font-black text-gray-900 dark:text-white tracking-tight uppercase">
                            {{ __('TIẾP THỊ LIÊN KẾT') }}
                        </h1>
                        <p class="text-xs font-bold text-gray-500 dark:text-slate-400 uppercase mt-0.5">
                            {{ __('(AFFILIATE MLM)') }}
                        </p>
                    </div>
                </div>
                <!-- Hình ảnh 3D liên kết xích đỏ cam và 2 avatar người được hiển thị ở góc phải -->
                <div class="absolute right-0 top-0 bottom-0 w-32 flex items-center justify-end pointer-events-none pr-2">
                    <img src="{{ asset('assets/images/affiliate_chain_3d.webp') }}" class="h-20 w-auto object-contain" alt="{{ __('Tiếp thị liên kết') }}">
                </div>
            </div>

            <!-- Khối Nhận Đường Dẫn Giới Thiệu (Nhân bản link & Hiển thị phần trăm hoa hồng + hình ảnh 3D) -->
            <div class="bg-white dark:bg-slate-900 p-6 rounded-2xl shadow-[0_8px_30px_rgb(0,0,0,0.015)] border border-gray-100 dark:border-slate-800/80 space-y-4">
                <!-- Tiêu đề và icon liên kết màu đỏ cam -->
                <h3 class="font-bold text-gray-900 dark:text-white text-sm flex items-center gap-2">
                    <i data-lucide="link" class="w-4.5 h-4.5 text-shopee"></i>
                    {{ __('Đường dẫn giới thiệu của bạn') }}
                </h3>
                
                <!-- Ô nhập chứa đường dẫn giới thiệu tích hợp nút copy nhỏ gọn ở cuối ô -->
                <div class="relative flex items-center">
                    <input type="text" 
                           readonly 
                           x-ref="refLink"
                           value="{{ $referralLink }}" 
                           class="block w-full pl-4 pr-12 py-3.5 border border-gray-200 dark:border-slate-800 rounded-2xl text-xs bg-gray-50/50 dark:bg-slate-800/60 font-mono text-gray-500 dark:text-slate-400 focus:outline-none">
                    <button @click="copyLink" 
                            class="absolute right-2 p-2 bg-[#fff0ed] dark:bg-orange-950/20 text-shopee dark:text-shopee-light rounded-xl hover:scale-105 transition-all cursor-pointer shadow-sm">
                        <i data-lucide="copy" class="w-4 h-4 text-shopee" x-show="!copied"></i>
                        <i data-lucide="check" class="w-4 h-4 text-green-500" x-show="copied" x-cloak></i>
                    </button>
                </div>

                <!-- Nút Sao chép lớn màu cam/đỏ cam bo góc tròn 2xl -->
                <button @click="copyLink"
                        class="w-full flex items-center justify-center gap-2 py-3.5 text-sm font-bold text-white bg-gradient-to-r from-shopee to-shopee-light hover:brightness-110 rounded-2xl transition-all shadow-md shadow-shopee/10 cursor-pointer">
                    <i data-lucide="copy" class="w-4 h-4" x-show="!copied"></i>
                    <i data-lucide="check" class="w-4 h-4 text-green-200" x-show="copied" x-cloak></i>
                    <span x-text="copied ? '{{ __('Đã sao chép') }}' : '{{ __('Sao chép') }}'"></span>
                </button>

                <!-- Hàng nút chia sẻ nhanh link giới thiệu lên các mạng xã hội -->
                @php
                    $shareText = __('Tham gia Hoàn Tiền Shopee để nhận hoàn tiền cho mỗi đơn hàng! Đăng ký ngay qua link của tôi:');
                    $fbShare  = 'https://www.facebook.com/sharer/sharer.php?u=' . urlencode($referralLink);
                    $tgShare  = 'https://t.me/share/url?url=' . urlencode($referralLink) . '&text=' . urlencode($shareText);
                    $msgShare = 'fb-messenger://share/?link=' . urlencode($referralLink);
                    $zlShare  = 'https://zalo.me/share?url=' . urlencode($referralLink);
                    $qrSrc    = 'https://api.qrserver.com/v1/create-qr-code/?size=240x240&margin=0&data=' . urlencode($referralLink);
                @endphp
                <div class="flex flex-wrap items-center gap-3 pt-1" x-data="{ showQr: false }">
                    <span class="text-xs font-bold text-gray-500 dark:text-slate-400 whitespace-nowrap">{{ __('Chia sẻ:') }}</span>
                    <div class="flex items-center flex-wrap gap-2.5">
                        <!-- Facebook -->
                        <a href="{{ $fbShare }}" target="_blank" rel="noopener" title="{{ __('Chia sẻ lên Facebook') }}"
                           class="w-9 h-9 shrink-0 flex items-center justify-center rounded-full bg-[#1877F2] text-white hover:brightness-110 transition-all shadow-sm">
                            <svg viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                        </a>

                        <!-- Messenger -->
                        <a href="{{ $msgShare }}" target="_blank" rel="noopener" title="{{ __('Chia sẻ qua Messenger') }}"
                           class="w-9 h-9 shrink-0 flex items-center justify-center rounded-full bg-gradient-to-tr from-[#00B2FF] via-[#8A2BE2] to-[#FF5280] text-white hover:brightness-110 transition-all shadow-sm">
                            <svg viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5"><path d="M12 2C6.477 2 2 6.145 2 11.258c0 2.908 1.433 5.485 3.66 7.21.139.108.228.268.228.448v2.418c0 .368.397.6.721.424l2.678-1.455a.721.721 0 0 1 .533-.035c.712.186 1.458.287 2.18.287 5.523 0 10-4.146 10-9.258C22 6.145 17.523 2 12 2zm1.025 11.517-2.023-2.158-3.95 2.158 4.341-4.61 2.028 2.158 3.945-2.158-4.341 4.61z"/></svg>
                        </a>

                        <!-- Telegram -->
                        <a href="{{ $tgShare }}" target="_blank" rel="noopener" title="{{ __('Chia sẻ qua Telegram') }}"
                           class="w-9 h-9 shrink-0 flex items-center justify-center rounded-full bg-[#229ED9] text-white hover:brightness-110 transition-all shadow-sm">
                            <svg viewBox="0 0 24 24" fill="currentColor" class="w-4 h-4"><path d="M9.78 18.65l.28-4.28 7.68-6.92c.34-.3-.07-.46-.52-.18L7.69 13.25l-4.14-1.3c-.9-.28-.92-.9.2-1.34l16.16-6.23c.75-.28 1.4.17 1.15.96l-2.75 12.95c-.2.96-.78 1.2-1.58.75l-4.2-3.1-2.02 1.95c-.22.22-.4.4-.82.4z"/></svg>
                        </a>

                        <!-- Zalo -->
                        <a href="{{ $zlShare }}" target="_blank" rel="noopener" title="{{ __('Chia sẻ qua Zalo') }}"
                           class="w-9 h-9 shrink-0 flex items-center justify-center rounded-full bg-[#e8f2ff] dark:bg-blue-950/40 text-[#0068FF] dark:text-blue-450 hover:bg-[#d8e8ff] dark:hover:bg-blue-950/60 transition-all cursor-pointer">
                            <span class="text-[11px] font-black leading-none">Zalo</span>
                        </a>

                        <!-- Mã QR -->
                        <button type="button" @click="showQr = true" title="{{ __('Hiển thị mã QR') }}"
                                class="w-9 h-9 shrink-0 flex items-center justify-center rounded-full bg-[#f3f4f6] dark:bg-slate-800 text-gray-700 dark:text-slate-350 hover:bg-gray-200 dark:hover:bg-slate-700 transition-all cursor-pointer">
                            <i data-lucide="qr-code" class="w-4 h-4"></i>
                        </button>
                    </div>

                    <!-- Modal hiển thị mã QR của link giới thiệu để dễ dàng quét chia sẻ -->
                    <div x-show="showQr" x-cloak
                         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm"
                         x-transition.opacity
                         @click.self="showQr = false" @keydown.escape.window="showQr = false">
                        <div class="bg-white dark:bg-slate-900 rounded-2xl p-6 w-full max-w-xs shadow-2xl border border-gray-100 dark:border-slate-800 text-center space-y-4"
                             x-transition.scale.origin.center>
                            <div class="flex items-center justify-between">
                                <h3 class="font-bold text-gray-900 dark:text-white text-sm">{{ __('Mã QR giới thiệu') }}</h3>
                                <button type="button" @click="showQr = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-slate-200 cursor-pointer">
                                    <i data-lucide="x" class="w-5 h-5"></i>
                                </button>
                            </div>
                            <div class="flex justify-center">
                                <img src="{{ $qrSrc }}" loading="lazy" alt="{{ __('Mã QR giới thiệu') }}"
                                     class="w-44 h-44 rounded-2xl bg-white p-2 border border-gray-100 dark:border-slate-700 shadow-inner">
                            </div>
                            <p class="text-[11px] text-gray-500 dark:text-slate-400 leading-relaxed">{{ __('Dùng app Zalo, Facebook hoặc Camera để quét mã và mở liên kết giới thiệu.') }}</p>
                            <button type="button" @click="downloadQrCode('{{ $referralLink }}')"
                                    class="inline-flex items-center justify-center gap-2 w-full px-4 py-2.5 text-xs font-bold text-white bg-shopee hover:bg-shopee-dark rounded-2xl transition-all cursor-pointer">
                                <i data-lucide="download" class="w-4 h-4"></i>
                                {{ __('Tải mã QR') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Card mức hoa hồng nhận, hiển thị tỷ lệ % tầng 1 (F1) và ảnh 3D quà tặng bên phải -->
            <div class="bg-white dark:bg-slate-900 p-6 rounded-2xl shadow-[0_8px_30px_rgb(0,0,0,0.02)] border border-gray-100 dark:border-slate-800/80 flex items-center justify-between gap-6">
                <div class="space-y-1">
                    <span class="text-[10px] text-gray-400 dark:text-slate-500 font-bold uppercase tracking-wider block">{{ __('MỨC HOA HỒNG NHẬN') }}</span>
                    <h4 class="text-sm font-bold text-gray-900 dark:text-white">{{ __('Tầng 1 (F1)') }}</h4>
                    <!-- Số tỷ lệ phần mềm % cực to màu đỏ cam -->
                    <p class="text-4xl font-black text-shopee leading-none mt-1">{{ $f1Rate }}%</p>
                    @if($f2Enabled)
                    <div class="pt-2 mt-2 border-t border-gray-100 dark:border-slate-800/60 flex items-center gap-1.5">
                        <span class="text-xs text-gray-550 dark:text-slate-400">{{ __('Tầng 2 (F2):') }}</span>
                        <span class="text-xs font-bold text-blue-600 dark:text-blue-400">{{ $f2Rate }}%</span>
                    </div>
                    @endif
                </div>
                <!-- Hình ảnh 3D minh họa hộp quà và tỷ lệ phần trăm được cấu hình đường dẫn -->
                <div class="shrink-0 w-28 h-20 flex items-center justify-center">
                    <img src="{{ asset('assets/images/commission_3d.webp') }}" class="w-24 h-20 object-contain" alt="{{ __('Mức hoa hồng') }}">
                </div>
            </div>

            <!-- Thống kê mạng lưới tiếp thị liên kết (Lượt click, Thành viên F1, Chờ duyệt, Đã nhận) theo grid 2 cột trên mobile -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 {{ $f2Enabled ? 'lg:grid-cols-5' : '' }}">
                <!-- Thống kê Lượt click link giới thiệu -->
                <div class="bg-white dark:bg-slate-900 p-5 rounded-2xl shadow-[0_8px_30px_rgb(0,0,0,0.015)] border border-gray-100/85 dark:border-slate-800/80 flex flex-col justify-between min-h-[110px]">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-teal-50 dark:bg-teal-950/20 text-teal-600 dark:text-teal-400 flex items-center justify-center shrink-0">
                            <i data-lucide="mouse-pointer" class="w-5 h-5 text-teal-650"></i>
                        </div>
                        <div class="min-w-0">
                            <span class="text-[9px] font-bold text-gray-400 dark:text-slate-500 uppercase tracking-wider block truncate">{{ __('LƯỢT CLICK LINK') }}</span>
                            <p class="text-xl font-black text-teal-650 dark:text-teal-500 leading-tight mt-0.5">{{ number_format($user->referral_clicks) }}</p>
                        </div>
                    </div>
                    <span class="text-[10px] text-gray-400 dark:text-slate-500 block font-medium mt-2">{{ __('Tổng lượt truy cập') }}</span>
                </div>

                <!-- Thống kê F1 -->
                <div class="bg-white dark:bg-slate-900 p-5 rounded-2xl shadow-[0_8px_30px_rgb(0,0,0,0.015)] border border-gray-100/85 dark:border-slate-800/80 flex flex-col justify-between min-h-[110px]">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-orange-50 dark:bg-orange-950/20 text-shopee flex items-center justify-center shrink-0">
                            <i data-lucide="users" class="w-5 h-5 text-shopee"></i>
                        </div>
                        <div class="min-w-0">
                            <span class="text-[9px] font-bold text-gray-400 dark:text-slate-500 uppercase tracking-wider block truncate">{{ __('GIỚI THIỆU F1') }}</span>
                            <p class="text-xl font-black text-gray-900 dark:text-white leading-tight mt-0.5">{{ count($f1Users) }}</p>
                        </div>
                    </div>
                    <span class="text-[10px] text-gray-400 dark:text-slate-500 block font-medium mt-2">{{ __('Đăng ký trực tiếp') }}</span>
                </div>

                @if($f2Enabled)
                <!-- Thống kê F2 (Hiển thị nếu hệ thống có bật chế độ MLM 2 cấp) -->
                <div class="bg-white dark:bg-slate-900 p-5 rounded-2xl shadow-[0_8px_30px_rgb(0,0,0,0.015)] border border-gray-100/85 dark:border-slate-800/80 flex flex-col justify-between min-h-[110px]">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-blue-50 dark:bg-blue-950/20 text-blue-600 dark:text-blue-400 flex items-center justify-center shrink-0">
                            <i data-lucide="users-2" class="w-5 h-5 text-blue-600"></i>
                        </div>
                        <div class="min-w-0">
                            <span class="text-[9px] font-bold text-gray-400 dark:text-slate-500 uppercase tracking-wider block truncate">{{ __('GIỚI THIỆU F2') }}</span>
                            <p class="text-xl font-black text-gray-900 dark:text-white leading-tight mt-0.5">{{ count($f2Users) }}</p>
                        </div>
                    </div>
                    <span class="text-[10px] text-gray-400 dark:text-slate-500 block font-medium mt-2">{{ __('Do F1 giới thiệu') }}</span>
                </div>
                @endif

                <!-- Hoa hồng chờ duyệt từ các đơn hàng đang xử lý -->
                <div class="bg-white dark:bg-slate-900 p-5 rounded-2xl shadow-[0_8px_30px_rgb(0,0,0,0.015)] border border-gray-100/85 dark:border-slate-800/80 flex flex-col justify-between min-h-[110px]">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-yellow-50 dark:bg-yellow-950/20 text-yellow-600 dark:text-yellow-400 flex items-center justify-center shrink-0">
                            <i data-lucide="clock" class="w-5 h-5 text-yellow-650"></i>
                        </div>
                        <div class="min-w-0">
                            <span class="text-[9px] font-bold text-gray-400 dark:text-slate-500 uppercase tracking-wider block truncate">{{ __('CHỜ DUYỆT') }}</span>
                            <p class="text-xl font-black text-yellow-650 dark:text-yellow-500 leading-tight mt-0.5">{{ \App\Helpers\CurrencyHelper::format($pendingCommission) }}</p>
                        </div>
                    </div>
                    <span class="text-[10px] text-gray-400 dark:text-slate-500 block font-medium mt-2">{{ __('Đơn gốc đang duyệt') }}</span>
                </div>

                <!-- Hoa hồng đã duyệt và đã được cộng vào ví thành công -->
                <div class="bg-white dark:bg-slate-900 p-5 rounded-2xl shadow-[0_8px_30px_rgb(0,0,0,0.015)] border border-gray-100/85 dark:border-slate-800/80 flex flex-col justify-between min-h-[110px]">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-green-50 dark:bg-green-950/20 text-green-600 dark:text-green-400 flex items-center justify-center shrink-0">
                            <i data-lucide="wallet" class="w-5 h-5 text-green-650"></i>
                        </div>
                        <div class="min-w-0">
                            <span class="text-[9px] font-bold text-gray-400 dark:text-slate-500 uppercase tracking-wider block truncate">{{ __('ĐÃ NHẬN') }}</span>
                            <p class="text-xl font-black text-green-650 dark:text-green-555 leading-tight mt-0.5">{{ \App\Helpers\CurrencyHelper::format($totalCommission) }}</p>
                        </div>
                    </div>
                    <span class="text-[10px] text-gray-400 dark:text-slate-500 block font-medium mt-2">{{ __('Đã cộng vào tài khoản') }}</span>
                </div>
            </div>

            <!-- Tabs: Chuyển đổi hiển thị giữa Lịch sử hoa hồng & Mạng lưới thành viên -->
            <div class="bg-white dark:bg-slate-900 rounded-2xl shadow-[0_8px_30px_rgb(0,0,0,0.02)] border border-gray-100 dark:border-slate-800/80 overflow-hidden" x-data="{ tab: '{{ request('tab', 'history') }}' }">
                <!-- Thanh tiêu đề chuyển tab có đường gạch dưới khi active -->
                <div class="flex border-b border-gray-100 dark:border-slate-800 text-sm font-semibold px-6 gap-6 bg-white dark:bg-slate-900 overflow-x-auto">
                    <button @click="tab = 'history'"
                            :class="tab === 'history' ? 'border-b-2 border-shopee text-shopee font-bold' : 'text-gray-500 hover:text-gray-700 dark:text-slate-400 dark:hover:text-slate-300 border-b-2 border-transparent'"
                            class="py-4 transition-all focus:outline-none cursor-pointer whitespace-nowrap">
                        {{ __('Lịch sử hoa hồng') }}
                    </button>
                    <button @click="tab = 'orders'"
                            :class="tab === 'orders' ? 'border-b-2 border-shopee text-shopee font-bold' : 'text-gray-500 hover:text-gray-700 dark:text-slate-400 dark:hover:text-slate-300 border-b-2 border-transparent'"
                            class="py-4 transition-all focus:outline-none cursor-pointer whitespace-nowrap">
                        {{ __('Đơn hàng thành viên (:count)', ['count' => $memberOrdersCount]) }}
                    </button>
                    <button @click="tab = 'network'"
                            :class="tab === 'network' ? 'border-b-2 border-shopee text-shopee font-bold' : 'text-gray-500 hover:text-gray-700 dark:text-slate-400 dark:hover:text-slate-300 border-b-2 border-transparent'"
                            class="py-4 transition-all focus:outline-none cursor-pointer whitespace-nowrap">
                        {{ __('Mạng lưới thành viên (:count)', ['count' => $f2Enabled ? count($f1Users) + count($f2Users) : count($f1Users)]) }}
                    </button>
                </div>

                <!-- Tab Nội dung 1: Lịch sử hoa hồng giới thiệu -->
                <div x-show="tab === 'history'" class="p-6 space-y-4">
                    <!-- Bộ lọc cấp và trạng thái hiển thị trên cùng một dòng ngang, tránh bị ngắt dòng xuống dưới trên các thiết bị di động -->
                    <div class="flex items-center gap-x-4 sm:gap-x-5">
                        <div class="flex items-center gap-2">
                            <label class="text-[11px] font-bold text-gray-400 dark:text-slate-500 uppercase tracking-wider">{{ __('Cấp:') }}</label>
                            <select x-model="filterLevel" @change="applyFilters()"
                                    class="text-xs font-semibold border border-gray-200 dark:border-slate-700 rounded-xl px-3 py-1.5 bg-gray-50 dark:bg-slate-800/60 text-gray-700 dark:text-slate-350 focus:outline-none focus:ring-2 focus:ring-shopee/20 cursor-pointer">
                                <option value="">{{ __('Tất cả') }}</option>
                                <option value="1">{{ __('Tầng 1 (F1)') }}</option>
                                @if($f2Enabled)
                                <option value="2">{{ __('Tầng 2 (F2)') }}</option>
                                @endif
                            </select>
                        </div>
                        <div class="flex items-center gap-2">
                            <label class="text-[11px] font-bold text-gray-400 dark:text-slate-500 uppercase tracking-wider">{{ __('Trạng thái:') }}</label>
                            <select x-model="filterStatus" @change="applyFilters()"
                                    class="text-xs font-semibold border border-gray-200 dark:border-slate-700 rounded-xl px-3 py-1.5 bg-gray-50 dark:bg-slate-800/60 text-gray-700 dark:text-slate-350 focus:outline-none focus:ring-2 focus:ring-shopee/20 cursor-pointer">
                                <option value="">{{ __('Tất cả') }}</option>
                                <option value="pending">{{ __('Chờ duyệt') }}</option>
                                <option value="approved">{{ __('Đã cộng ví') }}</option>
                            </select>
                        </div>
                    </div>
                    <div id="referral-commission-list-container" class="transition-all duration-300">
                        @include('dashboard.partials.referral_commission_list')
                    </div>
                </div>

                <!-- Tab Nội dung 2: Đơn hàng phát sinh từ mạng lưới thành viên (F1 & F2) -->
                <div x-show="tab === 'orders'" class="p-6 space-y-4" x-cloak>
                    <p class="text-[11px] text-gray-400 dark:text-slate-500 leading-relaxed">
                        {{ __('Danh sách các đơn hàng do thành viên cấp dưới (F1/F2) của bạn thực hiện. Mỗi đơn được duyệt sẽ sinh ra hoa hồng giới thiệu cho bạn.') }}
                    </p>
                    @include('dashboard.partials.member_order_list')
                </div>

                <!-- Tab Nội dung 3: Mạng lưới thành viên (F1 & F2) -->
                <div x-show="tab === 'network'" class="p-6 space-y-6" x-cloak>
                    <!-- Danh sách thành viên cấp dưới trực tiếp (F1) -->
                    <div>
                        <h4 class="font-bold text-gray-900 dark:text-white text-xs uppercase tracking-wider text-shopee border-b border-orange-50 dark:border-orange-950/20 pb-2 mb-4">
                            {{ __('Thành viên F1 trực tiếp (:count)', ['count' => count($f1Users)]) }}
                        </h4>
                        @if(count($f1Users) > 0)
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                @foreach($f1Users as $f1)
                                    <div class="p-3 sm:p-4 bg-gray-50/50 dark:bg-slate-800/40 border border-gray-100 dark:border-slate-800/80 rounded-2xl flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-full flex items-center justify-center shrink-0 text-sm font-black bg-orange-50 dark:bg-orange-950/30 text-shopee">
                                            {{ mb_strtoupper(mb_substr($f1->name, 0, 1)) }}
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <p class="text-xs font-bold text-gray-900 dark:text-white truncate">{{ Str::mask($f1->name, '*', 2, 8) }}</p>
                                            <div class="flex items-center gap-1.5 mt-0.5 text-[10px] text-gray-400 dark:text-slate-500">
                                                <span class="whitespace-nowrap">{{ $f1->created_at->format('d/m/Y') }}</span>
                                                <span class="w-1 h-1 rounded-full bg-gray-300 dark:bg-slate-600 shrink-0"></span>
                                                <span class="font-mono truncate">{{ $f1->referral_code }}</span>
                                            </div>
                                        </div>
                                        <span class="text-[11px] font-extrabold text-green-600 dark:text-green-400 shrink-0 whitespace-nowrap" title="{{ __('Hoa hồng đã tạo ra') }}">+{{ \App\Helpers\CurrencyHelper::format($memberEarnings[$f1->id] ?? 0) }}</span>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-xs text-gray-400 dark:text-slate-500 text-center py-6 bg-gray-50/30 dark:bg-slate-800/20 rounded-2xl border border-gray-100 dark:border-slate-800 border-dashed">{{ __('Bạn chưa có thành viên F1 nào.') }}</p>
                        @endif
                    </div>

                    @if($f2Enabled)
                    <!-- Danh sách thành viên cấp dưới gián tiếp (F2 - do F1 giới thiệu) -->
                    <div class="pt-2">
                        <h4 class="font-bold text-gray-900 dark:text-white text-xs uppercase tracking-wider text-blue-600 dark:text-blue-400 border-b border-blue-50 dark:border-blue-950/20 pb-2 mb-4">
                            {{ __('Thành viên F2 gián tiếp (:count)', ['count' => count($f2Users)]) }}
                        </h4>
                        @if(count($f2Users) > 0)
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                @foreach($f2Users as $f2)
                                    <div class="p-3 sm:p-4 bg-gray-50/50 dark:bg-slate-800/40 border border-gray-100 dark:border-slate-800/80 rounded-2xl flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-full flex items-center justify-center shrink-0 text-sm font-black bg-blue-50 dark:bg-blue-950/30 text-blue-600 dark:text-blue-400">
                                            {{ mb_strtoupper(mb_substr($f2->name, 0, 1)) }}
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <p class="text-xs font-bold text-gray-900 dark:text-white truncate">{{ Str::mask($f2->name, '*', 2, 8) }}</p>
                                            <div class="flex items-center gap-1.5 mt-0.5 text-[10px] text-gray-400 dark:text-slate-500">
                                                <span class="whitespace-nowrap">{{ $f2->created_at->format('d/m/Y') }}</span>
                                                <span class="w-1 h-1 rounded-full bg-gray-300 dark:bg-slate-600 shrink-0"></span>
                                                <span class="text-blue-600 dark:text-blue-400 font-bold">{{ __('Cấp 2 (F2)') }}</span>
                                            </div>
                                        </div>
                                        <span class="text-[11px] font-extrabold text-green-600 dark:text-green-400 shrink-0 whitespace-nowrap" title="{{ __('Hoa hồng đã tạo ra') }}">+{{ \App\Helpers\CurrencyHelper::format($memberEarnings[$f2->id] ?? 0) }}</span>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-xs text-gray-400 dark:text-slate-500 text-center py-6 bg-gray-50/30 dark:bg-slate-800/20 rounded-2xl border border-gray-100 dark:border-slate-800 border-dashed">{{ __('Chưa có thành viên F2 nào tham gia.') }}</p>
                        @endif
                    </div>
                    @endif
                </div>
            </div>

            <!-- Khối Chính sách Tiếp thị liên kết (Hiển thị chi tiết chính sách nhận thưởng MLM) -->
            @if(!empty($referralPolicy))
            <div class="bg-white dark:bg-slate-900 p-6 rounded-2xl shadow-[0_8px_30px_rgb(0,0,0,0.02)] border border-gray-100 dark:border-slate-800/80 space-y-4">
                <h3 class="font-bold text-gray-900 dark:text-white text-sm flex items-center gap-2">
                    <i data-lucide="file-text" class="w-4.5 h-4.5 text-shopee"></i>
                    {{ __('Chính sách tiếp thị liên kết') }}
                </h3>
                <div class="prose prose-sm dark:prose-invert max-w-none text-xs text-gray-650 dark:text-slate-400 leading-relaxed break-words">
                    {!! $referralPolicy !!}
                </div>
            </div>
            @endif

           

        </div>
    </div>
</div>
@endsection

@section('scripts')
<!-- Nhúng tệp js xử lý logic sao chép đường dẫn tiếp thị liên kết -->
<script src="{{ asset('js/referrals.js') }}?v=1.0.1"></script>
@endsection
