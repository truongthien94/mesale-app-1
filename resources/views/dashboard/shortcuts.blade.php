@extends('layouts.app')

{{--
    Trang hướng dẫn thêm và cấu hình phím tắt iPhone (iOS Shortcuts)
    Cung cấp giao diện hiện đại, hướng dẫn người dùng từng bước cài đặt phím tắt tự động hóa lấy link Shopee Cashback.
--}}

@section('title', __('Hướng Dẫn Thêm Phím Tắt iPhone') . ' - ' . $siteName)

@section('content')
<div class="px-4 mx-auto max-w-7xl sm:px-6 lg:px-8 py-6 sm:py-10">
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
        
        <!-- Sidebar điều hướng của thành viên -->
        <div class="hidden lg:block lg:col-span-3">
            @include('dashboard.sidebar')
        </div>

        <!-- Chi tiết hướng dẫn phím tắt -->
        <div class="lg:col-span-9 space-y-6">
            {{-- Nhắc nhở thành viên bổ sung email để bảo vệ tài khoản --}}
            @include('components.email_update_notice')
            
            <!-- Tiêu đề trang với icon điện thoại -->
            <div class="flex items-center gap-3.5 pb-2">
                <div class="w-12 h-12 flex items-center justify-center bg-gradient-to-tr from-shopee/10 to-shopee-light/10 text-shopee rounded-2xl dark:from-shopee/20 dark:to-shopee-light/20 dark:text-shopee-light shrink-0 shadow-sm animate-pulse">
                    <i data-lucide="smartphone" class="w-6 h-6"></i>
                </div>
                <div>
                    <h1 class="text-lg sm:text-xl font-extrabold text-gray-900 dark:text-white tracking-tight uppercase">
                        {{ __('Phím tắt iPhone (iOS Shortcuts)') }}
                    </h1>
                    <p class="hidden sm:block text-xs text-gray-400 dark:text-slate-400 mt-0.5">
                        {{ \App\Models\Setting::getVal('ios_shortcut_desc', __('Hướng dẫn cài đặt phím tắt trên điện thoại iPhone giúp chuyển đổi link hoàn tiền Shopee siêu nhanh trong 1 giây')) }}
                    </p>
                </div>
            </div>

            <!-- Khối Giới Thiệu & Nút Tải Phím Tắt Quick Action -->
            <div class="bg-gradient-to-br from-shopee/5 via-orange-500/[0.02] to-amber-500/[0.05] p-6 rounded-2xl border border-orange-100/50 dark:border-slate-800/80 space-y-6 relative overflow-hidden">
                <!-- Hiệu ứng vòng sáng trang trí nền -->
                <div class="absolute -top-12 -left-12 w-32 h-32 bg-shopee/10 rounded-full blur-xl pointer-events-none"></div>
                <div class="absolute -bottom-12 -right-12 w-32 h-32 bg-amber-500/10 rounded-full blur-xl pointer-events-none"></div>

                <div class="flex flex-col md:flex-row items-center justify-between gap-6 relative z-10">
                    <div class="space-y-2 text-center md:text-left">
                        <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-bold bg-orange-100/80 dark:bg-orange-950/40 text-shopee border border-orange-200/50 dark:border-orange-900/30">
                            <i data-lucide="zap" class="w-3.5 h-3.5 text-orange-500 animate-bounce"></i>
                            {{ __('Mới: Tự động hóa hoàn toàn') }}
                        </span>
                        <h2 class="text-lg sm:text-xl font-extrabold text-gray-900 dark:text-slate-100">
                            {{ __('Rút gọn link hoàn tiền chỉ với 1 lượt click') }}
                        </h2>
                        <p class="text-xs text-gray-500 dark:text-slate-400 leading-relaxed max-w-xl">
                            {{ \App\Models\Setting::getVal('ios_shortcut_guide', __('Không cần truy cập website! Chỉ cần sao chép link Shopee trên điện thoại và chạy Phím tắt (Shortcuts). Hệ thống sẽ tự động nhận diện tài khoản, phân tích sản phẩm và copy lại link hoàn tiền mới vào bộ nhớ tạm.')) }}
                        </p>
                    </div>

                    <!-- Nút Tải Phím Tắt có hiệu ứng ping sóng nước -->
                    <div class="shrink-0 relative z-10" x-data="{ shortcutUrl: '{{ \App\Models\Setting::getVal('ios_shortcut_url', '') }}' }">
                        <div class="absolute -inset-2">
                            <div class="w-full h-full rounded-2xl bg-shopee/10 animate-ping opacity-75"></div>
                        </div>
                        <template x-if="shortcutUrl && shortcutUrl !== '#'">
                            <a :href="shortcutUrl" 
                               target="_blank"
                               class="relative inline-flex items-center gap-2 px-6 py-3.5 bg-gradient-to-r from-shopee via-orange-500 to-amber-500 hover:brightness-110 text-white font-extrabold text-xs rounded-2xl transition-all shadow-lg hover:scale-105 active:scale-95 duration-300">
                                <i data-lucide="download" class="w-4 h-4"></i>
                                <span>{{ __('TẢI PHÍM TẮT NGAY') }}</span>
                            </a>
                        </template>
                        <template x-if="!shortcutUrl || shortcutUrl === '#'">
                            <button @click="alert('{{ __('Quản trị viên chưa cấu hình đường dẫn Phím tắt iCloud. Vui lòng vào Admin Panel -> Cấu hình hoàn tiền Shopee để thiết lập đường dẫn phím tắt.') }}')"
                                    class="relative inline-flex items-center gap-2 px-6 py-3.5 bg-gradient-to-r from-shopee via-orange-500 to-amber-500 hover:brightness-110 text-white font-extrabold text-xs rounded-2xl transition-all shadow-lg hover:scale-105 active:scale-95 duration-300">
                                <i data-lucide="download" class="w-4 h-4"></i>
                                <span>{{ __('TẢI PHÍM TẮT NGAY') }}</span>
                            </button>
                        </template>
                    </div>
                </div>
            </div>

            <!-- Khối Cấu Hình Cá Nhân để điền vào Phím Tắt -->
            <div class="bg-white dark:bg-slate-900 p-6 rounded-2xl shadow-md border border-gray-100 dark:border-slate-800/80 space-y-4">
                <h3 class="font-extrabold text-gray-900 dark:text-slate-100 text-sm flex items-center gap-2">
                    <i data-lucide="key" class="w-4.5 h-4.5 text-orange-500"></i>
                    {{ __('Thông Tin Cấu Hình Cá Nhân') }}
                </h3>
                <p class="text-xs text-gray-500 dark:text-slate-400">
                    {{ __('Khi thiết lập Phím tắt trên iPhone, sếp cần điền các thông tin xác thực sau để hệ thống ghi nhận hoa hồng chính xác vào tài khoản của sếp:') }}
                </p>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-2">
                    <!-- Khóa API Token cá nhân (API Key) -->
                    <div class="p-4 rounded-2xl border border-gray-100 dark:border-slate-800 bg-gray-50/50 dark:bg-slate-950/20 space-y-2 relative" x-data="{ copied: false }">
                        <div class="flex items-center justify-between">
                            <span class="text-[10px] font-bold text-emerald-600 dark:text-emerald-400 uppercase tracking-wider">{{ __('Khóa API Token (API Key)') }}</span>
                            <button @click="navigator.clipboard.writeText('{{ auth()->user()->api_token }}'); copied = true; setTimeout(() => copied = false, 2000)" 
                                    class="text-[10px] font-bold text-shopee hover:underline focus:outline-none flex items-center gap-1">
                                <i x-show="!copied" data-lucide="copy" class="w-3.5 h-3.5"></i>
                                <i x-show="copied" data-lucide="check" class="w-3.5 h-3.5 text-green-500" x-cloak></i>
                                <span x-text="copied ? '{{ __('Đã sao chép') }}' : '{{ __('Sao chép') }}'"></span>
                            </button>
                        </div>
                        <div class="text-xs font-mono font-bold text-gray-800 dark:text-white select-all truncate">
                            {{ auth()->user()->api_token }}
                        </div>
                        <p class="text-[10px] text-gray-400 dark:text-slate-500">
                            {{ __('Khóa bảo mật cá nhân dùng để kết nối Phím tắt và ghi nhận hoa hồng chính xác vào ví của sếp.') }}
                        </p>
                    </div>

                    <!-- Đường dẫn API Endpoint chính thức -->
                    <div class="p-4 rounded-2xl border border-gray-100 dark:border-slate-800 bg-gray-50/50 dark:bg-slate-950/20 space-y-2 relative" x-data="{ copied: false }">
                        <div class="flex items-center justify-between">
                            <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">{{ __('Cổng API Liên Kết (API URL)') }}</span>
                            <button @click="navigator.clipboard.writeText('{{ url('/api/shopee/product') }}'); copied = true; setTimeout(() => copied = false, 2000)" 
                                    class="text-[10px] font-bold text-shopee hover:underline focus:outline-none flex items-center gap-1">
                                <i x-show="!copied" data-lucide="copy" class="w-3.5 h-3.5"></i>
                                <i x-show="copied" data-lucide="check" class="w-3.5 h-3.5 text-green-500" x-cloak></i>
                                <span x-text="copied ? '{{ __('Đã sao chép') }}' : '{{ __('Sao chép') }}'"></span>
                            </button>
                        </div>
                        <div class="text-xs font-semibold text-gray-700 dark:text-slate-300 truncate select-all">
                            {{ url('/api/shopee/product') }}
                        </div>
                        <p class="text-[10px] text-gray-400 dark:text-slate-500">
                            {{ __('Đường dẫn cổng kết nối API để gửi yêu cầu phân tích link sản phẩm.') }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Khối quy trình các bước hướng dẫn cài đặt chi tiết -->
            <div class="bg-white dark:bg-slate-900 p-6 rounded-2xl shadow-md border border-gray-100 dark:border-slate-800/80 space-y-6">
                <h3 class="font-extrabold text-gray-900 dark:text-slate-100 text-sm flex items-center gap-2">
                    <i data-lucide="help-circle" class="w-4.5 h-4.5 text-orange-500"></i>
                    {{ __('Các Bước Cài Đặt & Sử Dụng') }}
                </h3>

                <div class="space-y-6 relative before:absolute before:inset-y-1 before:left-3.5 before:w-0.5 before:bg-gray-100 dark:before:bg-slate-800/80">
                    
                    <!-- Bước 1 -->
                    <div class="flex gap-4 relative">
                        <div class="w-8 h-8 rounded-full bg-orange-500 text-white flex items-center justify-center font-bold text-xs shrink-0 z-10 shadow-sm">
                            1
                        </div>
                        <div class="space-y-1">
                            <h4 class="text-xs font-bold text-gray-800 dark:text-slate-200">{{ __('Tải ứng dụng Phím tắt (Shortcuts) trên App Store') }}</h4>
                            <p class="text-xs text-gray-400 dark:text-slate-500 leading-relaxed">
                                {{ __('Hãy đảm bảo thiết bị iPhone của sếp đã cài đặt ứng dụng Phím tắt chính thức từ Apple. Nếu chưa có, sếp hãy vào App Store và tải xuống miễn phí.') }}
                            </p>
                        </div>
                    </div>

                    <!-- Bước 2 -->
                    <div class="flex gap-4 relative">
                        <div class="w-8 h-8 rounded-full bg-orange-500 text-white flex items-center justify-center font-bold text-xs shrink-0 z-10 shadow-sm">
                            2
                        </div>
                        <div class="space-y-1">
                            <h4 class="text-xs font-bold text-gray-800 dark:text-slate-200">{{ __('Tải phím tắt cấu hình sẵn của hệ thống') }}</h4>
                            <p class="text-xs text-gray-400 dark:text-slate-500 leading-relaxed">
                                {{ __('Ấn vào nút "TẢI PHÍM TẮT NGAY" ở trên. Trình duyệt sẽ tự động chuyển hướng sếp sang app Phím tắt. Hãy bấm "Thêm phím tắt" (Add Shortcut) để thêm vào máy.') }}
                            </p>
                        </div>
                    </div>

                    <!-- Bước 3 -->
                    <div class="flex gap-4 relative">
                        <div class="w-8 h-8 rounded-full bg-orange-500 text-white flex items-center justify-center font-bold text-xs shrink-0 z-10 shadow-sm">
                            3
                        </div>
                        <div class="space-y-1">
                            <h4 class="text-xs font-bold text-gray-800 dark:text-slate-200">{{ __('Điền thông tin cấu hình tài khoản') }}</h4>
                            <p class="text-xs text-gray-400 dark:text-slate-500 leading-relaxed">
                                {{ __('Trong quá trình thêm, phím tắt sẽ yêu cầu sếp nhập Khóa API Token (API Key) và Cổng API Liên Kết (đã được cung cấp ở phần thông tin cấu hình phía trên).') }}
                            </p>
                        </div>
                    </div>

                    <!-- Bước 4 -->
                    <div class="flex gap-4 relative">
                        <div class="w-8 h-8 rounded-full bg-orange-500 text-white flex items-center justify-center font-bold text-xs shrink-0 z-10 shadow-sm">
                            4
                        </div>
                        <div class="space-y-1">
                            <h4 class="text-xs font-bold text-gray-800 dark:text-slate-200">{{ __('Chia sẻ sản phẩm Shopee qua Phím tắt') }}</h4>
                            <p class="text-xs text-gray-400 dark:text-slate-500 leading-relaxed">
                                {{ __('Khi đang xem sản phẩm trên ứng dụng Shopee, sếp bấm vào biểu tượng Chia sẻ (Share) -> chọn phím tắt vừa thêm từ danh sách. Phím tắt sẽ tự động lấy link sản phẩm, gọi API đến website để tạo link hoàn tiền và hiển thị giao diện chứa link hoàn tiền rút gọn cùng nút Sao chép và nút Mở link mua sắm trực tiếp.') }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection
