@extends('layouts.app')

@section('title', __('Thiết Lập Tài Khoản') . ' - ' . $siteName)

@section('content')
<div class="px-4 mx-auto max-w-7xl sm:px-6 lg:px-8 py-6 sm:py-10">
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">

        <!-- Sidebar -->
        <div class="hidden lg:block lg:col-span-3">
            @include('dashboard.sidebar')
        </div>

        <!-- Chi tiết -->
        <div class="lg:col-span-9 space-y-5 sm:space-y-8">
            {{-- Nhắc nhở thành viên bổ sung email để bảo vệ tài khoản --}}
            @include('components.email_update_notice')

            <!-- ===== Hero hồ sơ: tổng quan tài khoản & trạng thái bảo mật ===== -->
            <div class="rounded-2xl shadow-md border border-orange-100/70 dark:border-slate-800/80 bg-gradient-to-br from-orange-50 to-white dark:from-slate-800/40 dark:to-slate-900 p-5 sm:p-6">
                <!-- Avatar + tên + email (canh giữa cùng hàng) -->
                <div class="flex items-center gap-4">
                    <div class="w-16 h-16 sm:w-20 sm:h-20 rounded-2xl bg-gradient-to-br from-shopee to-shopee-dark text-white flex items-center justify-center font-extrabold text-2xl sm:text-3xl shrink-0 shadow-lg shadow-shopee/30">
                        {{ strtoupper(substr($user->name, 0, 1)) }}
                    </div>
                    <div class="min-w-0 flex-1 space-y-1.5">
                        <div class="flex flex-wrap items-center gap-2">
                            <h1 class="text-lg sm:text-xl font-extrabold text-gray-900 dark:text-white tracking-tight truncate">
                                {{ $user->name }}
                            </h1>
                            @if(!is_null($user->email_verified_at))
                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[9px] font-bold bg-emerald-50 text-emerald-600 border border-emerald-100 dark:bg-emerald-950/30 dark:text-emerald-400 dark:border-emerald-900/30">
                                <i data-lucide="badge-check" class="w-3 h-3"></i>
                                {{ __('Đã xác minh') }}
                            </span>
                            @elseif(empty($user->email))
                            {{-- Tài khoản đăng ký bằng Số điện thoại: chưa có email nên chưa thể xác minh --}}
                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[9px] font-bold bg-amber-50 text-amber-600 border border-amber-100 dark:bg-amber-950/30 dark:text-amber-400 dark:border-amber-900/30">
                                <i data-lucide="mail-question" class="w-3 h-3"></i>
                                {{ __('Chưa có email') }}
                            </span>
                            @else
                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[9px] font-bold bg-amber-50 text-amber-600 border border-amber-100 dark:bg-amber-950/30 dark:text-amber-400 dark:border-amber-900/30">
                                <i data-lucide="alert-circle" class="w-3 h-3 animate-pulse"></i>
                                {{ __('Chưa xác minh') }}
                            </span>
                            @endif
                        </div>
                        <p class="flex flex-wrap items-center gap-x-2.5 gap-y-1 text-xs text-gray-500 dark:text-slate-400 min-w-0">
                            {{-- Tài khoản đăng ký bằng Số điện thoại chưa có email nên hiển thị số điện thoại làm định danh --}}
                            <span class="inline-flex items-center gap-1.5 min-w-0">
                                <i data-lucide="{{ $user->email ? 'mail' : 'phone' }}" class="w-3.5 h-3.5 shrink-0 text-gray-400 dark:text-slate-500"></i>
                                <span class="truncate">{{ $user->email ?: $user->phone }}</span>
                            </span>
                            <span class="hidden sm:inline-flex items-center gap-1 pl-2.5 border-l border-orange-200/70 dark:border-slate-700 text-gray-400 dark:text-slate-500">
                                <span class="w-1 h-1 rounded-full bg-emerald-500 animate-pulse"></span>
                                {{ __('Tài khoản chính') }}
                            </span>
                        </p>
                    </div>
                </div>

                <!-- Dải trạng thái bảo mật nhanh (đổi màu theo tình trạng) -->
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-2.5 sm:gap-3 mt-5 pt-5 border-t border-orange-100/60 dark:border-slate-800">
                    <!-- Google 2FA -->
                    <div class="flex items-center gap-2.5 rounded-2xl bg-white dark:bg-slate-800/40 border border-orange-100/50 dark:border-slate-800 px-3 py-2.5 shadow-sm">
                        <span class="w-8 h-8 rounded-xl flex items-center justify-center shrink-0 {{ $user->google2fa_enabled ? 'bg-emerald-50 text-emerald-500 dark:bg-emerald-950/40 dark:text-emerald-400' : 'bg-gray-100 text-gray-400 dark:bg-slate-800 dark:text-slate-500' }}">
                            <i data-lucide="smartphone" class="w-4 h-4"></i>
                        </span>
                        <div class="min-w-0">
                            <span class="block text-[9px] uppercase tracking-wider text-gray-400 dark:text-slate-500 font-bold leading-tight">Google 2FA</span>
                            <span class="block text-xs font-bold leading-tight truncate {{ $user->google2fa_enabled ? 'text-emerald-600 dark:text-emerald-400' : 'text-gray-500 dark:text-slate-400' }}">{{ $user->google2fa_enabled ? __('Đang bật') : __('Chưa bật') }}</span>
                        </div>
                    </div>
                    <!-- OTP Email -->
                    <div class="flex items-center gap-2.5 rounded-2xl bg-white dark:bg-slate-800/40 border border-orange-100/50 dark:border-slate-800 px-3 py-2.5 shadow-sm">
                        <span class="w-8 h-8 rounded-xl flex items-center justify-center shrink-0 {{ $user->email_otp_enabled ? 'bg-emerald-50 text-emerald-500 dark:bg-emerald-950/40 dark:text-emerald-400' : 'bg-gray-100 text-gray-400 dark:bg-slate-800 dark:text-slate-500' }}">
                            <i data-lucide="mail-check" class="w-4 h-4"></i>
                        </span>
                        <div class="min-w-0">
                            <span class="block text-[9px] uppercase tracking-wider text-gray-400 dark:text-slate-500 font-bold leading-tight">OTP Email</span>
                            <span class="block text-xs font-bold leading-tight truncate {{ $user->email_otp_enabled ? 'text-emerald-600 dark:text-emerald-400' : 'text-gray-500 dark:text-slate-400' }}">{{ $user->email_otp_enabled ? __('Đang bật') : __('Chưa bật') }}</span>
                        </div>
                    </div>
                    <!-- Ngày tham gia -->
                    <div class="flex items-center gap-2.5 rounded-2xl bg-white dark:bg-slate-800/40 border border-orange-100/50 dark:border-slate-800 px-3 py-2.5 shadow-sm col-span-2 sm:col-span-1">
                        <span class="w-8 h-8 rounded-xl flex items-center justify-center shrink-0 bg-shopee/10 text-shopee dark:bg-shopee/20 dark:text-shopee-light">
                            <i data-lucide="calendar-days" class="w-4 h-4"></i>
                        </span>
                        <div class="min-w-0">
                            <span class="block text-[9px] uppercase tracking-wider text-gray-400 dark:text-slate-500 font-bold leading-tight">{{ __('Ngày tham gia') }}</span>
                            <span class="block text-xs font-bold leading-tight truncate text-gray-700 dark:text-slate-300">{{ $user->created_at?->format('d/m/Y') }}</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Lời mời bổ sung email cho tài khoản đăng ký bằng Số điện thoại (KHÔNG bắt buộc) --}}
            @if(empty($user->email))
            <div class="p-4 text-xs text-amber-850 dark:text-amber-300 rounded-2xl bg-amber-50 dark:bg-amber-950/20 border border-amber-200 dark:border-amber-900/30 flex items-start gap-2.5 shadow-sm">
                <i data-lucide="shield-alert" class="w-4.5 h-4.5 text-amber-500 shrink-0 mt-0.5"></i>
                <div class="space-y-1">
                    <span class="font-bold block">{{ __('Tài khoản của bạn chưa có địa chỉ Email') }}</span>
                    <span>{{ __('Bạn đã đăng ký bằng số điện thoại nên chưa thể dùng chức năng Quên mật khẩu, nhận email thông báo và bảo mật OTP qua Email. Hãy bổ sung email ngay bên dưới để bảo vệ tài khoản tốt hơn (không bắt buộc).') }}</span>
                </div>
            </div>
            {{-- Hiển thị cảnh báo chưa xác minh email --}}
            @elseif(is_null($user->email_verified_at))
            <div class="p-4 text-xs text-amber-850 dark:text-amber-300 rounded-2xl bg-amber-50 dark:bg-amber-950/20 border border-amber-200 dark:border-amber-900/30 flex items-start gap-2.5 shadow-sm">
                <i data-lucide="alert-circle" class="w-4.5 h-4.5 text-amber-500 shrink-0 mt-0.5 animate-pulse"></i>
                <div class="space-y-1">
                    <span class="font-bold block">{{ __('Tài khoản chưa xác minh Email!') }}</span>
                    <span>{{ __('Hồ sơ của bạn hiện chưa được xác minh địa chỉ email. Một số tính năng quan trọng như điểm danh nhận thưởng có thể bị hạn chế.') }}</span>
                    <a href="{{ route('register.verification') }}" class="inline-flex items-center gap-1 font-bold text-shopee hover:underline mt-1">
                        {{ __('Nhấn vào đây để tiến hành xác minh ngay') }} <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                    </a>
                </div>
            </div>
            @endif

            {{-- Hiển thị cảnh báo Demo màu đỏ để hướng dẫn người dùng tài khoản bị giới hạn --}}
            @if(config('app.demo'))
            <div class="p-4 text-xs text-red-800 dark:text-red-300 rounded-2xl bg-red-50 dark:bg-red-950/20 border border-red-150 dark:border-red-900/30 flex items-start gap-2.5 shadow-sm">
                <i data-lucide="alert-triangle" class="w-4.5 h-4.5 text-red-500 shrink-0 mt-0.5 animate-pulse"></i>
                <div>
                    <span class="font-bold">{{ __('Chế độ Demo:') }}</span>
                    {{ __('Các tính năng thay đổi thông tin cá nhân, đổi mật khẩu và thiết lập bảo mật (2FA, Email OTP) đang bị khóa để bảo vệ tài khoản thử nghiệm của bạn.') }}
                </div>
            </div>
            @endif

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5 sm:gap-8">
                <!-- Cập nhật thông tin cá nhân -->
                <div class="bg-white dark:bg-slate-900 p-5 sm:p-6 rounded-2xl shadow-md border border-gray-100 dark:border-slate-800/80 space-y-5">
                    <div class="flex items-center gap-2.5 border-b border-gray-100 dark:border-slate-800 pb-3.5">
                        <span class="w-9 h-9 rounded-xl bg-shopee/10 dark:bg-shopee/20 text-shopee dark:text-shopee-light flex items-center justify-center shrink-0">
                            <i data-lucide="user" class="w-4.5 h-4.5"></i>
                        </span>
                        <div>
                            <h3 class="font-bold text-gray-900 dark:text-white text-sm leading-tight">{{ __('Thông tin cá nhân') }}</h3>
                            <p class="text-[11px] text-gray-400 dark:text-slate-500 leading-tight mt-0.5">{{ __('Họ tên & số điện thoại') }}</p>
                        </div>
                    </div>

                    <form action="{{ route('profile.update') }}" method="POST" class="space-y-4">
                        @csrf

                        {{-- Tài khoản đăng ký bằng Số điện thoại: cho phép tự bổ sung email (chỉ nhập được một lần, sau đó khoá lại) --}}
                        @if(empty($user->email))
                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <label for="email" class="block text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider">{{ __('Địa chỉ Email') }}</label>
                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[9px] font-bold bg-gray-100 text-gray-500 border border-gray-200 dark:bg-slate-800 dark:border-slate-700 dark:text-slate-400">
                                    {{ __('Tùy chọn') }}
                                </span>
                            </div>
                            <div class="relative">
                                <i data-lucide="mail-plus" class="w-4 h-4 text-gray-400 dark:text-slate-500 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                                <input type="email" name="email" id="email" value="{{ old('email') }}" placeholder="email-cua-ban@gmail.com" class="block w-full pl-10 pr-4 py-3 sm:py-2.5 border border-gray-200 dark:border-slate-700 rounded-2xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-gray-50/50 dark:bg-slate-800/50 dark:text-slate-200 dark:placeholder-slate-500">
                            </div>
                            <p class="text-[10px] text-gray-400 dark:text-slate-500 mt-1.5 leading-relaxed">
                                {{ __('Bổ sung email giúp bạn khôi phục mật khẩu khi quên và nhận các thông báo quan trọng. Lưu ý: email chỉ được nhập một lần và không thể tự thay đổi về sau.') }}
                            </p>
                            @error('email')
                            <p class="text-[10px] text-red-500 mt-1 font-semibold">{{ $message }}</p>
                            @enderror
                        </div>
                        @else
                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <label class="block text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider">{{ __('Địa chỉ Email') }}</label>
                                @if(!is_null($user->email_verified_at))
                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[9px] font-bold bg-emerald-50 text-emerald-600 border border-emerald-100 dark:bg-emerald-950/20 dark:border-emerald-900/30 dark:text-emerald-450">
                                    <i data-lucide="check-circle" class="w-3 h-3 text-emerald-500"></i>
                                    {{ __('Đã xác minh') }}
                                </span>
                                @else
                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[9px] font-bold bg-amber-50 text-amber-600 border border-amber-100 dark:bg-amber-950/20 dark:border-amber-900/30 dark:text-amber-450">
                                    <i data-lucide="alert-circle" class="w-3 h-3 text-amber-500 animate-pulse"></i>
                                    {{ __('Chưa xác minh') }}
                                </span>
                                @endif
                            </div>
                            <div class="relative">
                                <i data-lucide="mail" class="w-4 h-4 text-gray-400 dark:text-slate-500 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                                <input type="email" disabled value="{{ $user->email }}" class="block w-full pl-10 pr-4 py-3 sm:py-2.5 border border-gray-200 dark:border-slate-800 rounded-2xl text-xs bg-gray-100 dark:bg-slate-800 text-gray-400 dark:text-slate-500 cursor-not-allowed">
                            </div>
                        </div>
                        @endif

                        <div>
                            <label for="name" class="block text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider mb-1.5">{{ __('Họ và tên') }}</label>
                            <div class="relative">
                                <i data-lucide="user-round" class="w-4 h-4 text-gray-400 dark:text-slate-500 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                                <input type="text" name="name" id="name" required value="{{ old('name', $user->name) }}" class="block w-full pl-10 pr-4 py-3 sm:py-2.5 border border-gray-200 dark:border-slate-700 rounded-2xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-gray-50/50 dark:bg-slate-800/50 dark:text-slate-200 dark:placeholder-slate-500">
                            </div>
                        </div>

                        <div>
                            <label for="phone" class="block text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider mb-1.5">{{ __('Số điện thoại') }}</label>
                            <div class="relative">
                                <i data-lucide="phone" class="w-4 h-4 text-gray-400 dark:text-slate-500 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                                <input type="text" name="phone" id="phone" value="{{ old('phone', $user->phone) }}" placeholder="{{ __('Chưa cập nhật...') }}" class="block w-full pl-10 pr-4 py-3 sm:py-2.5 border border-gray-200 dark:border-slate-700 rounded-2xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-gray-50/50 dark:bg-slate-800/50 dark:text-slate-200 dark:placeholder-slate-500">
                            </div>
                            @error('phone')
                            <p class="text-[10px] text-red-500 mt-1 font-semibold">{{ $message }}</p>
                            @enderror
                        </div>

                        @if($user->referral_code)
                        <div x-data="{ copied: false }">
                            <label class="block text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider mb-1.5">{{ __('Mã giới thiệu') }}</label>
                            <div class="relative">
                                <i data-lucide="gift" class="w-4 h-4 text-gray-400 dark:text-slate-500 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                                <input type="text" readonly value="{{ $user->referral_code }}" class="block w-full pl-10 pr-10 py-3 sm:py-2.5 border border-gray-200 dark:border-slate-800 rounded-2xl text-xs bg-gray-100 dark:bg-slate-800 text-gray-500 dark:text-slate-400 cursor-default font-mono tracking-widest select-all">
                                <button type="button"
                                    @click="navigator.clipboard.writeText('{{ $user->referral_code }}').then(() => { copied = true; setTimeout(() => copied = false, 2000) })"
                                    class="absolute right-2.5 top-1/2 -translate-y-1/2 w-6 h-6 flex items-center justify-center text-gray-400 dark:text-slate-500 hover:text-shopee dark:hover:text-shopee-light transition-colors">
                                    <i data-lucide="check" class="w-3.5 h-3.5 text-emerald-500" x-show="copied" x-cloak></i>
                                    <i data-lucide="copy" class="w-3.5 h-3.5" x-show="!copied"></i>
                                </button>
                            </div>
                        </div>
                        @endif

                        <!-- Khóa API Token cá nhân dùng cho Phím tắt / Open API -->
                        <div x-data="{ 
                            copied: false, 
                            copiedModal: false,
                            showApiDocs: false, 
                            showApiTokenModal: {{ request()->query('open') === 'apitoken' ? 'true' : 'false' }},
                            revealTokenModal: false,
                            showKey: false
                        }">
                            <div class="flex items-center justify-between mb-1.5">
                                <label class="block text-xs font-bold text-emerald-600 dark:text-emerald-400 uppercase tracking-wider">{{ __('Khóa API Token (API Key)') }}</label>
                                <div class="flex items-center gap-2">
                                    <button type="button" @click="showApiTokenModal = true" class="text-[10px] font-bold text-emerald-600 dark:text-emerald-400 hover:underline flex items-center gap-1 focus:outline-none">
                                        <i data-lucide="external-link" class="w-3 h-3"></i>
                                        {{ __('Sao chép nhanh') }}
                                    </button>
                                    <span class="text-gray-300 dark:text-slate-700">|</span>
                                    <form action="{{ route('profile.regenerate_api_key') }}" method="POST" onsubmit="return confirm('{{ __('Sếp có chắc chắn muốn tạo lại Mã API Key mới? Mã cũ trên Phím tắt sẽ không còn sử dụng được.') }}')">
                                        @csrf
                                        <button type="submit" class="text-[10px] font-bold text-rose-500 hover:underline flex items-center gap-1 focus:outline-none">
                                            <i data-lucide="refresh-cw" class="w-3 h-3"></i>
                                            {{ __('Tạo lại mã mới') }}
                                        </button>
                                    </form>
                                </div>
                            </div>
                            <div class="relative">
                                <i data-lucide="key" class="w-4 h-4 text-emerald-500 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                                <input type="text" readonly :value="showKey ? '{{ $user->api_token }}' : '{{ Str::limit($user->api_token, 8, '••••••••••••••••••••••••') }}'" class="block w-full pl-10 pr-16 py-3 sm:py-2.5 border border-emerald-200 dark:border-emerald-900/40 rounded-2xl text-xs bg-emerald-50/30 dark:bg-emerald-950/20 text-emerald-700 dark:text-emerald-400 cursor-default font-mono select-all">
                                <div class="absolute right-2.5 top-1/2 -translate-y-1/2 flex items-center gap-1.5">
                                    <button type="button" @click="showKey = !showKey" class="w-6 h-6 flex items-center justify-center text-gray-400 hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors" title="{{ __('Ẩn / Hiện mã') }}">
                                        <i data-lucide="eye" class="w-3.5 h-3.5" x-show="!showKey"></i>
                                        <i data-lucide="eye-off" class="w-3.5 h-3.5" x-show="showKey" x-cloak></i>
                                    </button>
                                    <button type="button"
                                        @click="navigator.clipboard.writeText('{{ $user->api_token }}').then(() => { copied = true; setTimeout(() => copied = false, 2000) })"
                                        class="w-6 h-6 flex items-center justify-center text-emerald-500 hover:text-emerald-700 transition-colors" title="{{ __('Sao chép API Token') }}">
                                        <i data-lucide="check" class="w-3.5 h-3.5 text-emerald-500" x-show="copied" x-cloak></i>
                                        <i data-lucide="copy" class="w-3.5 h-3.5" x-show="!copied"></i>
                                    </button>
                                </div>
                            </div>
                            <p class="text-[10px] text-gray-400 dark:text-slate-500 mt-1">
                                {{ __('Sử dụng Khóa này để xác thực an toàn trên ứng dụng Phím tắt iPhone (iOS Shortcuts) hoặc API.') }}
                            </p>

                            {{-- Modal Sao chép API Token nổi bật (tự động bật khi truy cập ?open=apitoken) --}}
                            @include('dashboard.partials.api_token_modal')

                            {{-- Nút mở Tài liệu API — mặc định bật, quản trị viên có thể tắt qua cấu hình `api_docs_enabled` --}}
                            @if(\App\Models\Setting::getVal('api_docs_enabled', '1') === '1')
                                <button type="button" @click="showApiDocs = true"
                                    class="mt-2.5 w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 text-xs font-bold text-shopee dark:text-shopee-light bg-shopee/5 dark:bg-shopee/10 hover:bg-shopee/10 dark:hover:bg-shopee/20 border border-shopee/20 dark:border-shopee/30 rounded-2xl transition-all active:scale-[0.98]">
                                    <i data-lucide="book-open" class="w-4 h-4"></i>
                                    {{ __('Xem tài liệu API') }}
                                </button>

                                @include('dashboard.partials.api_docs_modal')
                            @endif
                        </div>

                        <button type="submit"
                            @if(config('app.demo')) disabled @endif
                            class="w-full inline-flex items-center justify-center gap-2 px-5 py-3 sm:py-2.5 text-xs font-semibold text-white @if(config('app.demo')) bg-gray-300 text-gray-500 cursor-not-allowed opacity-60 @else bg-shopee hover:bg-shopee-dark active:scale-[0.98] @endif rounded-2xl transition-all shadow-md shadow-shopee/20">
                            <i data-lucide="save" class="w-4 h-4"></i>
                            {{ config('app.demo') ? __('Tính năng bị khóa ở chế độ Demo') : __('Lưu thông tin') }}
                        </button>
                    </form>
                </div>

                <!-- Thay đổi mật khẩu -->
                <div class="bg-white dark:bg-slate-900 p-5 sm:p-6 rounded-2xl shadow-md border border-gray-100 dark:border-slate-800/80 space-y-5">
                    <div class="flex items-center gap-2.5 border-b border-gray-100 dark:border-slate-800 pb-3.5">
                        <span class="w-9 h-9 rounded-xl bg-shopee/10 dark:bg-shopee/20 text-shopee dark:text-shopee-light flex items-center justify-center shrink-0">
                            <i data-lucide="lock" class="w-4.5 h-4.5"></i>
                        </span>
                        <div>
                            <h3 class="font-bold text-gray-900 dark:text-white text-sm leading-tight">{{ __('Đổi mật khẩu bảo mật') }}</h3>
                            <p class="text-[11px] text-gray-400 dark:text-slate-500 leading-tight mt-0.5">{{ __('Tối thiểu 8 ký tự') }}</p>
                        </div>
                    </div>

                    <form action="{{ route('profile.password') }}" method="POST" class="space-y-4">
                        @csrf

                        <div>
                            <label for="current_password" class="block text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider mb-1.5">{{ __('Mật khẩu hiện tại') }}</label>
                            <div class="relative">
                                <i data-lucide="key-round" class="w-4 h-4 text-gray-400 dark:text-slate-500 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                                <input type="password" name="current_password" id="current_password" required placeholder="{{ __('Nhập mật khẩu hiện tại...') }}" class="block w-full pl-10 pr-4 py-3 sm:py-2.5 border border-gray-200 dark:border-slate-700 rounded-2xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-gray-50/50 dark:bg-slate-800/50 dark:text-slate-200 dark:placeholder-slate-500">
                            </div>
                        </div>

                        <div>
                            <label for="password" class="block text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider mb-1.5">{{ __('Mật khẩu mới') }}</label>
                            <div class="relative">
                                <i data-lucide="lock-keyhole" class="w-4 h-4 text-gray-400 dark:text-slate-500 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                                <input type="password" name="password" id="password" required placeholder="{{ __('Tối thiểu 8 ký tự...') }}" class="block w-full pl-10 pr-4 py-3 sm:py-2.5 border border-gray-200 dark:border-slate-700 rounded-2xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-gray-50/50 dark:bg-slate-800/50 dark:text-slate-200 dark:placeholder-slate-500">
                            </div>
                        </div>

                        <div>
                            <label for="password_confirmation" class="block text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider mb-1.5">{{ __('Xác nhận mật khẩu mới') }}</label>
                            <div class="relative">
                                <i data-lucide="lock-keyhole" class="w-4 h-4 text-gray-400 dark:text-slate-500 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                                <input type="password" name="password_confirmation" id="password_confirmation" required placeholder="{{ __('Nhập lại mật khẩu mới...') }}" class="block w-full pl-10 pr-4 py-3 sm:py-2.5 border border-gray-200 dark:border-slate-700 rounded-2xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-gray-50/50 dark:bg-slate-800/50 dark:text-slate-200 dark:placeholder-slate-500">
                            </div>
                        </div>

                        <button type="submit"
                            @if(config('app.demo')) disabled @endif
                            class="w-full inline-flex items-center justify-center gap-2 px-5 py-3 sm:py-2.5 text-xs font-semibold text-white @if(config('app.demo')) bg-gray-300 text-gray-500 cursor-not-allowed opacity-60 @else bg-shopee hover:bg-shopee-dark active:scale-[0.98] @endif rounded-2xl transition-all shadow-md shadow-shopee/20">
                            <i data-lucide="shield-check" class="w-4 h-4"></i>
                            {{ config('app.demo') ? __('Tính năng bị khóa ở chế độ Demo') : __('Cập nhật mật khẩu') }}
                        </button>
                    </form>
                </div>
            </div>

            <!-- Bảo mật hai lớp (2FA & Email OTP) -->
            <div class="bg-white dark:bg-slate-900 p-5 sm:p-6 rounded-2xl shadow-md border border-gray-100 dark:border-slate-800/80 space-y-6"
                x-data="{
                    show2faSetup: false,
                    show2faDisable: false,
                    showEmailOtpSetup: false,
                    showEmailOtpDisable: false,
                    qrCodeUrl: '',
                    secretKey: '',
                    loading2faSetup: false,
                    loading2faDisable: false,
                    sendingEmailOtp: false,
                    loadingEmailSetup: false,
                    loadingEmailDisable: false,
                    emailSent: false,
                    emailMessage: '',
                    emailOtpCountdown: 0,
                    emailOtpTimer: null,

                    errorMessage2faSetup: '',
                    successMessage2faSetup: '',
                    errorMessage2faDisable: '',
                    successMessage2faDisable: '',
                    errorMessageEmailSetup: '',
                    successMessageEmailSetup: '',
                    errorMessageEmailDisable: '',
                    successMessageEmailDisable: '',

                    startEmailOtpCountdown() {
                        this.emailOtpCountdown = 60;
                        if (this.emailOtpTimer) {
                            clearInterval(this.emailOtpTimer);
                        }
                        this.emailOtpTimer = setInterval(() => {
                            if (this.emailOtpCountdown > 0) {
                                this.emailOtpCountdown--;
                            } else {
                                clearInterval(this.emailOtpTimer);
                            }
                        }, 1000);
                    },

                    // Gọi API lấy thông tin QR Code và Secret Key
                    async init2FASetup() {
                        this.loading2faSetup = true;
                        this.errorMessage2faSetup = '';
                        this.successMessage2faSetup = '';
                        try {
                             let response = await fetch('{{ route('profile.2fa.setup') }}');
                             let data = await response.json();
                             if (data.success) {
                                 this.qrCodeUrl = data.qr_code_url;
                                 this.secretKey = data.secret_key;
                                 this.show2faSetup = true;
                             } else {
                                 alert(data.message || 'Có lỗi xảy ra khi thiết lập 2FA.');
                             }
                        } catch (error) {
                             alert('Không thể kết nối đến máy chủ.');
                        } finally {
                             this.loading2faSetup = false;
                        }
                    },

                    // Yêu cầu gửi mã OTP kích hoạt hoặc huỷ kích hoạt về email
                    async sendOTP(type) {
                        if (this.emailOtpCountdown > 0) {
                            return;
                        }
                        this.sendingEmailOtp = true;
                        try {
                             let response = await fetch('{{ route('profile.email_otp.send') }}', {
                                 method: 'POST',
                                 headers: {
                                     'Content-Type': 'application/json',
                                     'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                 }
                             });
                             let data = await response.json();
                             if (data.success) {
                                 this.emailMessage = data.message;
                                 this.emailSent = true;
                                 this.startEmailOtpCountdown();
                             } else {
                                 alert(data.message || 'Có lỗi xảy ra.');
                             }
                        } catch (error) {
                             alert('Không thể kết nối đến máy chủ.');
                        } finally {
                             this.sendingEmailOtp = false;
                        }
                    },

                    // Xác nhận bật 2FA bằng AJAX
                    async submitEnable2FA(e) {
                        this.loading2faSetup = true;
                        this.errorMessage2faSetup = '';
                        this.successMessage2faSetup = '';

                        let formData = new FormData(e.target);
                        try {
                             let response = await fetch('{{ route('profile.2fa.enable') }}', {
                                 method: 'POST',
                                 headers: {
                                     'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                     'Accept': 'application/json'
                                 },
                                 body: formData
                             });
                             let data = await response.json();
                             if (response.ok && data.success) {
                                 this.successMessage2faSetup = data.message;
                                 setTimeout(() => {
                                     window.location.reload();
                                 }, 1500);
                             } else {
                                 this.errorMessage2faSetup = data.message || 'Mã xác thực không chính xác.';
                             }
                        } catch (error) {
                             this.errorMessage2faSetup = 'Không thể kết nối đến máy chủ.';
                        } finally {
                             this.loading2faSetup = false;
                        }
                    },

                    // Xác nhận tắt 2FA bằng AJAX
                    async submitDisable2FA(e) {
                        this.loading2faDisable = true;
                        this.errorMessage2faDisable = '';
                        this.successMessage2faDisable = '';

                        let formData = new FormData(e.target);
                        try {
                             let response = await fetch('{{ route('profile.2fa.disable') }}', {
                                 method: 'POST',
                                 headers: {
                                     'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                     'Accept': 'application/json'
                                 },
                                 body: formData
                             });
                             let data = await response.json();
                             if (response.ok && data.success) {
                                 this.successMessage2faDisable = data.message;
                                 setTimeout(() => {
                                     window.location.reload();
                                 }, 1500);
                             } else {
                                 this.errorMessage2faDisable = data.message || 'Có lỗi xảy ra.';
                             }
                        } catch (error) {
                             this.errorMessage2faDisable = 'Không thể kết nối đến máy chủ.';
                        } finally {
                             this.loading2faDisable = false;
                        }
                    },

                    // Xác nhận bật OTP Email bằng AJAX
                    async submitEnableEmail(e) {
                        this.loadingEmailSetup = true;
                        this.errorMessageEmailSetup = '';
                        this.successMessageEmailSetup = '';

                        let formData = new FormData(e.target);
                        try {
                             let response = await fetch('{{ route('profile.email_otp.enable') }}', {
                                 method: 'POST',
                                 headers: {
                                     'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                     'Accept': 'application/json'
                                 },
                                 body: formData
                             });
                             let data = await response.json();
                             if (response.ok && data.success) {
                                 this.successMessageEmailSetup = data.message;
                                 setTimeout(() => {
                                     window.location.reload();
                                 }, 1500);
                             } else {
                                 this.errorMessageEmailSetup = data.message || 'Mã xác thực không chính xác.';
                             }
                        } catch (error) {
                             this.errorMessageEmailSetup = 'Không thể kết nối đến máy chủ.';
                        } finally {
                             this.loadingEmailSetup = false;
                        }
                    },

                    // Xác nhận tắt OTP Email bằng AJAX
                    async submitDisableEmail(e) {
                        this.loadingEmailDisable = true;
                        this.errorMessageEmailDisable = '';
                        this.successMessageEmailDisable = '';

                        let formData = new FormData(e.target);
                        try {
                             let response = await fetch('{{ route('profile.email_otp.disable') }}', {
                                 method: 'POST',
                                 headers: {
                                     'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                     'Accept': 'application/json'
                                 },
                                 body: formData
                             });
                             let data = await response.json();
                             if (response.ok && data.success) {
                                 this.successMessageEmailDisable = data.message;
                                 setTimeout(() => {
                                     window.location.reload();
                                 }, 1500);
                             } else {
                                 this.errorMessageEmailDisable = data.message || 'Có lỗi xảy ra.';
                             }
                        } catch (error) {
                             this.errorMessageEmailDisable = 'Không thể kết nối đến máy chủ.';
                        } finally {
                             this.loadingEmailDisable = false;
                        }
                    }
                 }">

                <div class="flex items-center gap-2.5 border-b border-gray-100 dark:border-slate-800/80 pb-3.5">
                    <span class="w-9 h-9 rounded-xl bg-shopee/10 dark:bg-shopee/20 text-shopee dark:text-shopee-light flex items-center justify-center shrink-0">
                        <i data-lucide="shield-check" class="w-4.5 h-4.5"></i>
                    </span>
                    <div>
                        <h3 class="font-bold text-gray-900 dark:text-slate-100 text-sm leading-tight">{{ __('Bảo mật hai lớp') }}</h3>
                        <p class="text-[11px] text-gray-400 dark:text-slate-500 leading-tight mt-0.5">{{ __('Google Authenticator (2FA) & Email OTP') }}</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5 sm:gap-6">

                    <!-- Xác thực Google Authenticator (2FA) -->
                    <div class="p-5 sm:p-6 border border-gray-100 dark:border-slate-800/60 rounded-2xl bg-gray-50/20 dark:bg-slate-900/40 space-y-5 flex flex-col justify-between hover:border-shopee/20 dark:hover:border-shopee/20 transition-all duration-300 shadow-sm hover:shadow-md">
                        <div class="space-y-4">
                            <div class="flex items-center justify-between gap-4">
                                <h4 class="font-bold text-gray-900 dark:text-slate-200 text-xs flex items-center gap-2">
                                    <span class="p-1.5 rounded-xl bg-blue-50 dark:bg-blue-950/40 text-blue-500 flex items-center justify-center">
                                        <i data-lucide="smartphone" class="w-4 h-4"></i>
                                    </span>
                                    Google Authenticator (2FA)
                                </h4>
                                <!-- Trạng thái bật/tắt 2FA -->
                                @if($user->google2fa_enabled)
                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 dark:bg-emerald-950/30 text-emerald-600 dark:text-emerald-400 border border-emerald-100 dark:border-emerald-900/30">
                                    <span class="w-1 h-1 rounded-full bg-emerald-500 dark:bg-emerald-400 animate-pulse"></span>
                                    Đã bật
                                </span>
                                @else
                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-gray-100 dark:bg-slate-800 text-gray-500 dark:text-slate-400 border border-gray-200 dark:border-slate-700/50">
                                    <span class="w-1 h-1 rounded-full bg-gray-400 dark:bg-slate-500"></span>
                                    Chưa bật
                                </span>
                                @endif
                            </div>
                            <p class="text-[11px] text-gray-500 dark:text-slate-400 leading-relaxed">
                                Quét mã QR bằng ứng dụng Authenticator (Google/Authy) để tạo mã OTP 6 số mỗi khi đăng nhập.
                            </p>
                        </div>

                        <div class="pt-2">
                            @if(!$user->google2fa_enabled)
                            <button type="button"
                                @if(config('app.demo')) disabled @else @click="init2FASetup()" @endif
                                :disabled="loading2faSetup || {{ config('app.demo') ? 'true' : 'false' }}"
                                class="w-full inline-flex items-center justify-center px-4 py-3 sm:py-2.5 text-xs font-semibold text-white @if(config('app.demo')) bg-gray-300 text-gray-500 cursor-not-allowed opacity-60 @else bg-shopee hover:bg-shopee-dark @endif rounded-xl transition-all shadow-md active:scale-[0.98]">
                                <span x-show="!loading2faSetup">{{ config('app.demo') ? __('Bị khóa ở Demo') : __('Thiết lập 2FA') }}</span>
                                <span x-show="loading2faSetup" class="flex items-center gap-1">
                                    <svg class="animate-spin h-3.5 w-3.5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    Đang tải...
                                </span>
                            </button>
                            @else
                            <button type="button"
                                @if(config('app.demo')) disabled @else @click="show2faDisable = true; errorMessage2faDisable = ''; successMessage2faDisable = '';" @endif
                                :disabled="{{ config('app.demo') ? 'true' : 'false' }}"
                                class="w-full inline-flex items-center justify-center px-4 py-3 sm:py-2.5 text-xs font-semibold text-red-600 dark:text-red-400 @if(config('app.demo')) bg-gray-150 text-gray-400 cursor-not-allowed opacity-60 @else bg-red-50 dark:bg-red-950/20 hover:bg-red-100 dark:hover:bg-red-900/30 border border-red-200 dark:border-red-900/30 @endif rounded-xl transition-all active:scale-[0.98]">
                                {{ config('app.demo') ? __('Bị khóa ở Demo') : __('Huỷ kích hoạt 2FA') }}
                            </button>
                            @endif
                        </div>
                    </div>

                    <!-- Xác thực OTP qua Email -->
                    <div class="p-5 sm:p-6 border border-gray-100 dark:border-slate-800/60 rounded-2xl bg-gray-50/20 dark:bg-slate-900/40 space-y-5 flex flex-col justify-between hover:border-shopee/20 dark:hover:border-shopee/20 transition-all duration-300 shadow-sm hover:shadow-md">
                        <div class="space-y-4">
                            <div class="flex items-center justify-between gap-4">
                                <h4 class="font-bold text-gray-900 dark:text-slate-200 text-xs flex items-center gap-2">
                                    <span class="p-1.5 rounded-xl bg-orange-50 dark:bg-orange-950/40 text-orange-500 flex items-center justify-center">
                                        <i data-lucide="mail" class="w-4 h-4"></i>
                                    </span>
                                    Xác thực OTP qua Email
                                </h4>
                                <!-- Trạng thái bật/tắt Email OTP -->
                                @if($user->email_otp_enabled)
                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 dark:bg-emerald-950/30 text-emerald-600 dark:text-emerald-400 border border-emerald-100 dark:border-emerald-900/30">
                                    <span class="w-1 h-1 rounded-full bg-emerald-500 dark:bg-emerald-400 animate-pulse"></span>
                                    Đã bật
                                </span>
                                @else
                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-gray-100 dark:bg-slate-800 text-gray-500 dark:text-slate-400 border border-gray-200 dark:border-slate-700/50">
                                    <span class="w-1 h-1 rounded-full bg-gray-400 dark:bg-slate-500"></span>
                                    Chưa bật
                                </span>
                                @endif
                            </div>
                            <p class="text-[11px] text-gray-500 dark:text-slate-400 leading-relaxed">
                                Hệ thống tự động gửi mã xác thực 6 số về hòm thư của bạn mỗi khi phát sinh yêu cầu đăng nhập.
                            </p>
                        </div>

                        <div class="pt-2">
                            {{-- Tài khoản chưa có email (đăng ký bằng SĐT) không thể dùng lớp bảo mật OTP qua Email --}}
                            @if(empty($user->email))
                            <button type="button" disabled
                                class="w-full inline-flex items-center justify-center gap-1.5 px-4 py-3 sm:py-2.5 text-xs font-semibold rounded-xl bg-gray-100 dark:bg-slate-800 text-gray-400 dark:text-slate-500 border border-gray-200 dark:border-slate-700 cursor-not-allowed">
                                <i data-lucide="lock" class="w-3.5 h-3.5"></i>
                                {{ __('Cần bổ sung email trước') }}
                            </button>
                            @elseif(!$user->email_otp_enabled)
                            <button type="button"
                                @if(config('app.demo')) disabled @else @click="showEmailOtpSetup = true; errorMessageEmailSetup = ''; successMessageEmailSetup = ''; emailSent = false; emailMessage = '';" @endif
                                :disabled="{{ config('app.demo') ? 'true' : 'false' }}"
                                class="w-full inline-flex items-center justify-center px-4 py-3 sm:py-2.5 text-xs font-semibold text-white @if(config('app.demo')) bg-gray-300 text-gray-500 cursor-not-allowed opacity-60 @else bg-shopee hover:bg-shopee-dark @endif rounded-xl transition-all shadow-md active:scale-[0.98]">
                                {{ config('app.demo') ? __('Bị khóa ở Demo') : __('Kích hoạt OTP Email') }}
                            </button>
                            @else
                            <button type="button"
                                @if(config('app.demo')) disabled @else @click="showEmailOtpDisable = true; errorMessageEmailDisable = ''; successMessageEmailDisable = ''; emailSent = false; emailMessage = '';" @endif
                                :disabled="{{ config('app.demo') ? 'true' : 'false' }}"
                                class="w-full inline-flex items-center justify-center px-4 py-3 sm:py-2.5 text-xs font-semibold text-red-600 dark:text-red-400 @if(config('app.demo')) bg-gray-150 text-gray-400 cursor-not-allowed opacity-60 @else bg-red-50 dark:bg-red-950/20 hover:bg-red-100 dark:hover:bg-red-900/30 border border-red-200 dark:border-red-900/30 @endif rounded-xl transition-all active:scale-[0.98]">
                                {{ config('app.demo') ? __('Bị khóa ở Demo') : __('Huỷ kích hoạt OTP Email') }}
                            </button>
                            @endif
                        </div>
                    </div>

                </div>

                <!-- Modals cấu hình bảo mật -->

                <!-- 1. Modal kích hoạt 2FA -->
                {{-- Business Rule: Sử dụng <template x-teleport="body"> để đưa modal ra khỏi stacking context của main/container, tránh bị Header/Footer đè lên backdrop. Sử dụng bg-slate-950/60 backdrop-blur-sm để tạo hiệu ứng phủ mờ cao cấp. --}}
                <template x-teleport="body">
                    <div x-show="show2faSetup" @click.self="show2faSetup = false" @keydown.escape.window="show2faSetup = false" class="fixed inset-0 z-[999] flex items-center justify-center bg-slate-950/60 backdrop-blur-sm p-4" x-cloak x-transition>
                        <div class="bg-white dark:bg-slate-900 rounded-2xl max-w-md w-full p-6 space-y-4 shadow-xl border border-gray-100 dark:border-slate-800 transform transition-all" x-transition>
                            <div class="flex items-center justify-between border-b border-gray-100 dark:border-slate-800 pb-3">
                                <h3 class="font-bold text-gray-900 dark:text-white text-sm flex items-center gap-1.5">
                                    <i data-lucide="smartphone" class="w-4.5 h-4.5 text-blue-500"></i> Thiết lập Google 2FA
                                </h3>
                                <button type="button" @click="show2faSetup = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-slate-200">
                                    <i data-lucide="x" class="w-5 h-5"></i>
                                </button>
                            </div>

                            <p class="text-xs text-gray-500 dark:text-slate-400 leading-relaxed">
                                Mở ứng dụng Google Authenticator hoặc Authy trên điện thoại của bạn, quét mã QR dưới đây hoặc điền thủ công Khoá bí mật để lấy mã OTP 6 số.
                            </p>

                            <!-- Thông báo lỗi/thành công -->
                            <div x-show="errorMessage2faSetup" class="p-3 text-red-700 dark:text-red-300 bg-red-50 dark:bg-red-950/20 border border-red-100 dark:border-red-900/30 rounded-xl text-xs" x-text="errorMessage2faSetup" x-cloak></div>
                            <div x-show="successMessage2faSetup" class="p-3 text-emerald-700 dark:text-emerald-300 bg-emerald-50 dark:bg-emerald-950/20 border border-emerald-100 dark:border-emerald-900/30 rounded-xl text-xs" x-text="successMessage2faSetup" x-cloak></div>

                            <div class="flex flex-col items-center justify-center p-4 bg-gray-50 dark:bg-slate-800/50 rounded-2xl border border-gray-100 dark:border-slate-800">
                                <!-- Mã QR -->
                                <img :src="qrCodeUrl" class="w-44 h-44 rounded-lg bg-white p-2 border shadow-inner mb-3" alt="QR Code">
                                <div class="w-full text-center space-y-1">
                                    <span class="block text-[10px] text-gray-400 dark:text-slate-500 font-bold uppercase tracking-wider">Khoá bí mật (Secret Key)</span>
                                    <code class="block text-xs font-mono font-bold text-shopee bg-shopee/5 dark:bg-shopee/10 px-3 py-1.5 rounded-lg select-all" x-text="secretKey"></code>
                                </div>
                            </div>

                            <form @submit.prevent="submitEnable2FA" class="space-y-4">
                                @csrf
                                <div>
                                    <label for="otp_2fa_code" class="block text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider mb-1">Mã xác thực OTP (6 chữ số)</label>
                                    <input type="text" name="otp_code" id="otp_2fa_code" required placeholder="Ví dụ: 123456" class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-700 rounded-xl text-xs text-center font-bold tracking-widest focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-gray-50/50 dark:bg-slate-800/50 dark:text-slate-200 dark:placeholder-slate-500">
                                </div>
                                <div class="flex gap-3">
                                    <button type="button" @click="show2faSetup = false" class="w-1/2 px-4 py-2.5 text-xs font-semibold text-gray-700 dark:text-slate-300 bg-gray-100 dark:bg-slate-800 hover:bg-gray-200 dark:hover:bg-slate-700 rounded-xl transition-all">Huỷ bỏ</button>
                                    <button type="submit" :disabled="loading2faSetup" class="w-1/2 px-4 py-2.5 text-xs font-semibold text-white bg-shopee hover:bg-shopee-dark rounded-xl transition-all shadow-md">
                                        <span x-show="!loading2faSetup">Xác nhận bật</span>
                                        <span x-show="loading2faSetup" class="flex items-center gap-1">
                                            <svg class="animate-spin h-3.5 w-3.5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                            </svg>
                                            Đang bật...
                                        </span>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </template>

                <!-- 2. Modal huỷ kích hoạt 2FA -->
                {{-- Business Rule: Sử dụng <template x-teleport="body"> để đưa modal ra body, khắc phục sự cố backdrop không đè lên header. --}}
                <template x-teleport="body">
                    <div x-show="show2faDisable" @click.self="show2faDisable = false" @keydown.escape.window="show2faDisable = false" class="fixed inset-0 z-[999] flex items-center justify-center bg-slate-950/60 backdrop-blur-sm p-4" x-cloak x-transition>
                        <div class="bg-white dark:bg-slate-900 rounded-2xl max-w-md w-full p-6 space-y-4 shadow-xl border border-gray-100 dark:border-slate-800" x-cloak x-transition>
                            <div class="flex items-center justify-between border-b border-gray-100 dark:border-slate-800 pb-3">
                                <h3 class="font-bold text-red-600 dark:text-red-400 text-sm flex items-center gap-1.5">
                                    <i data-lucide="shield-alert" class="w-4.5 h-4.5 text-red-500"></i> Huỷ kích hoạt Google 2FA
                                </h3>
                                <button type="button" @click="show2faDisable = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-slate-200">
                                    <i data-lucide="x" class="w-5 h-5"></i>
                                </button>
                            </div>

                            <p class="text-xs text-gray-500 dark:text-slate-400">
                                Vui lòng nhập mật khẩu tài khoản và mã xác thực OTP từ ứng dụng di động để tắt tính năng 2FA.
                            </p>

                            <!-- Thông báo lỗi/thành công -->
                            <div x-show="errorMessage2faDisable" class="p-3 text-red-700 dark:text-red-300 bg-red-50 dark:bg-red-950/20 border border-red-100 dark:border-red-900/30 rounded-xl text-xs" x-text="errorMessage2faDisable" x-cloak></div>
                            <div x-show="successMessage2faDisable" class="p-3 text-emerald-700 dark:text-emerald-300 bg-emerald-50 dark:bg-emerald-950/20 border border-emerald-100 dark:border-emerald-900/30 rounded-xl text-xs" x-text="successMessage2faDisable" x-cloak></div>

                            <form @submit.prevent="submitDisable2FA" class="space-y-4">
                                @csrf
                                <div>
                                    <label for="disable_2fa_password" class="block text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider mb-1">Mật khẩu tài khoản</label>
                                    <input type="password" name="password" id="disable_2fa_password" required placeholder="Nhập mật khẩu của bạn..." class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-700 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-gray-50/50 dark:bg-slate-800/50 dark:text-slate-200 dark:placeholder-slate-500">
                                </div>
                                <div>
                                    <label for="disable_2fa_code" class="block text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider mb-1">Mã xác thực OTP (6 chữ số)</label>
                                    <input type="text" name="otp_code" id="disable_2fa_code" required placeholder="Ví dụ: 123456" class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-700 rounded-xl text-xs text-center font-bold tracking-widest focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-gray-50/50 dark:bg-slate-800/50 dark:text-slate-200 dark:placeholder-slate-500">
                                </div>
                                <div class="flex gap-3">
                                    <button type="button" @click="show2faDisable = false" class="w-1/2 px-4 py-2.5 text-xs font-semibold text-gray-700 dark:text-slate-300 bg-gray-100 dark:bg-slate-800 hover:bg-gray-200 dark:hover:bg-slate-700 rounded-xl transition-all">Quay lại</button>
                                    <button type="submit" :disabled="loading2faDisable" class="w-1/2 px-4 py-2.5 text-xs font-semibold text-white bg-red-600 hover:bg-red-700 rounded-xl transition-all shadow-md shadow-red-100 dark:shadow-none">
                                        <span x-show="!loading2faDisable">Xác nhận huỷ</span>
                                        <span x-show="loading2faDisable" class="flex items-center gap-1">
                                            <svg class="animate-spin h-3.5 w-3.5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                            </svg>
                                            Đang huỷ...
                                        </span>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </template>

                <!-- 3. Modal kích hoạt OTP Email -->
                {{-- Business Rule: Sử dụng <template x-teleport="body"> để đảm bảo backdrop của modal này được đưa ra body, không bị các container của trang đè z-index. --}}
                <template x-teleport="body">
                    <div x-show="showEmailOtpSetup" @click.self="showEmailOtpSetup = false" @keydown.escape.window="showEmailOtpSetup = false" class="fixed inset-0 z-[999] flex items-center justify-center bg-slate-950/60 backdrop-blur-sm p-4" x-cloak x-transition>
                        <div class="bg-white dark:bg-slate-900 rounded-2xl max-w-md w-full p-6 space-y-4 shadow-xl border border-gray-100 dark:border-slate-800" x-cloak x-transition>
                            <div class="flex items-center justify-between border-b border-gray-100 dark:border-slate-800 pb-3">
                                <h3 class="font-bold text-gray-900 dark:text-white text-sm flex items-center gap-1.5">
                                    <i data-lucide="mail" class="w-4.5 h-4.5 text-orange-500"></i> Kích hoạt OTP qua Email
                                </h3>
                                <button type="button" @click="showEmailOtpSetup = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-slate-200">
                                    <i data-lucide="x" class="w-5 h-5"></i>
                                </button>
                            </div>

                            <!-- Nếu chưa gửi OTP -->
                            <div x-show="!emailSent" class="space-y-4 text-center py-4">
                                <p class="text-xs text-gray-500 dark:text-slate-400 leading-relaxed">
                                    Để kích hoạt tính năng xác thực OTP qua Email, hệ thống sẽ gửi một mã xác minh gồm 6 chữ số đến email đăng ký của bạn. Vui lòng bấm nút dưới đây để tiếp tục.
                                </p>
                                <button type="button" @click="sendOTP('setup')" :disabled="sendingEmailOtp || emailOtpCountdown > 0" class="w-full inline-flex items-center justify-center px-4 py-2.5 text-xs font-semibold text-white bg-shopee hover:bg-shopee-dark rounded-xl transition-all shadow-md">
                                    <span x-show="!sendingEmailOtp && emailOtpCountdown <= 0">Gửi mã OTP đến Email</span>
                                    <span x-show="emailOtpCountdown > 0" x-text="'Gửi lại mã OTP sau ' + emailOtpCountdown + 's'"></span>
                                    <span x-show="sendingEmailOtp" class="flex items-center gap-1">
                                        <svg class="animate-spin h-3.5 w-3.5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        Đang gửi mã...
                                    </span>
                                </button>
                            </div>

                            <!-- Nếu đã gửi OTP -->
                            <div x-show="emailSent" class="space-y-4" x-cloak>
                                <p class="text-xs text-emerald-700 dark:text-emerald-300 bg-emerald-50 dark:bg-emerald-950/20 border border-emerald-200 dark:border-emerald-900/30 p-3 rounded-xl leading-relaxed" x-text="emailMessage"></p>
                                <p class="text-xs text-gray-500 dark:text-slate-400">
                                    Vui lòng nhập mã OTP 6 chữ số vừa được gửi đến hòm thư đăng ký của bạn.
                                </p>

                                <!-- Thông báo lỗi/thành công -->
                                <div x-show="errorMessageEmailSetup" class="p-3 text-red-700 dark:text-red-300 bg-red-50 dark:bg-red-950/20 border border-red-100 dark:border-red-900/30 rounded-xl text-xs" x-text="errorMessageEmailSetup" x-cloak></div>
                                <div x-show="successMessageEmailSetup" class="p-3 text-emerald-700 dark:text-emerald-300 bg-emerald-50 dark:bg-emerald-950/20 border border-emerald-100 dark:border-emerald-900/30 rounded-xl text-xs" x-text="successMessageEmailSetup" x-cloak></div>

                                <form @submit.prevent="submitEnableEmail" class="space-y-4">
                                    @csrf
                                    <div>
                                        <label for="email_otp_setup_code" class="block text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider mb-1">Mã xác thực OTP Email</label>
                                        <input type="text" name="otp_code" id="email_otp_setup_code" required placeholder="Ví dụ: 123456" class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-700 rounded-xl text-xs text-center font-bold tracking-widest focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-gray-50/50 dark:bg-slate-800/50 dark:text-slate-200 dark:placeholder-slate-500">
                                    </div>
                                    <div class="flex gap-3">
                                        <button type="button" @click="showEmailOtpSetup = false" class="w-1/2 px-4 py-2.5 text-xs font-semibold text-gray-700 dark:text-slate-300 bg-gray-100 dark:bg-slate-800 hover:bg-gray-200 dark:hover:bg-slate-700 rounded-xl transition-all">Huỷ bỏ</button>
                                        <button type="submit" :disabled="loadingEmailSetup" class="w-1/2 px-4 py-2.5 text-xs font-semibold text-white bg-shopee hover:bg-shopee-dark rounded-xl transition-all shadow-md">
                                            <span x-show="!loadingEmailSetup">Xác nhận bật</span>
                                            <span x-show="loadingEmailSetup" class="flex items-center gap-1">
                                                <svg class="animate-spin h-3.5 w-3.5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                </svg>
                                                Đang bật...
                                            </span>
                                        </button>
                                    </div>
                                    <div class="text-center">
                                        <button type="button" @click="sendOTP('setup')" :disabled="sendingEmailOtp || emailOtpCountdown > 0" class="text-xs text-shopee hover:underline font-semibold flex items-center justify-center gap-1 mx-auto">
                                            <span x-show="!sendingEmailOtp && emailOtpCountdown <= 0">Gửi lại mã OTP</span>
                                            <span x-show="emailOtpCountdown > 0" x-text="'Gửi lại mã OTP sau ' + emailOtpCountdown + 's'"></span>
                                            <span x-show="sendingEmailOtp" class="flex items-center gap-1">
                                                <svg class="animate-spin h-3 w-3 text-shopee" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                </svg>
                                                Đang gửi...
                                            </span>
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </template>

                <!-- 4. Modal huỷ kích hoạt OTP Email -->
                {{-- Business Rule: Sử dụng <template x-teleport="body"> để đưa modal ra ngoài, khắc phục sự cố backdrop không đè lên header. Đóng đầy đủ thẻ div của emailSent, bg-white và modal container bên trong template để không làm lệch cấu trúc thẻ đóng ở bên ngoài. --}}
                <template x-teleport="body">
                    <div x-show="showEmailOtpDisable" @click.self="showEmailOtpDisable = false" @keydown.escape.window="showEmailOtpDisable = false" class="fixed inset-0 z-[999] flex items-center justify-center bg-slate-950/60 backdrop-blur-sm p-4" x-cloak x-transition>
                        <div class="bg-white dark:bg-slate-900 rounded-2xl max-w-md w-full p-6 space-y-4 shadow-xl border border-gray-100 dark:border-slate-800" x-cloak x-transition>
                            <div class="flex items-center justify-between border-b border-gray-100 dark:border-slate-800 pb-3">
                                <h3 class="font-bold text-red-600 dark:text-red-400 text-sm flex items-center gap-1.5">
                                    <i data-lucide="shield-alert" class="w-4.5 h-4.5 text-red-500"></i> Huỷ kích hoạt OTP Email
                                </h3>
                                <button type="button" @click="showEmailOtpDisable = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-slate-200">
                                    <i data-lucide="x" class="w-5 h-5"></i>
                                </button>
                            </div>

                            <!-- Nếu chưa gửi OTP -->
                            <div x-show="!emailSent" class="space-y-4 text-center py-4">
                                <p class="text-xs text-gray-500 dark:text-slate-400 leading-relaxed">
                                    Để huỷ kích hoạt xác thực OTP qua Email, bạn cần xác nhận bằng một mã OTP gửi về hòm thư của bạn. Vui lòng bấm nút dưới đây để tiếp tục.
                                </p>
                                <button type="button" @click="sendOTP('disable')" :disabled="sendingEmailOtp || emailOtpCountdown > 0" class="w-full inline-flex items-center justify-center px-4 py-2.5 text-xs font-semibold text-white bg-red-600 hover:bg-red-700 rounded-xl transition-all shadow-md">
                                    <span x-show="!sendingEmailOtp && emailOtpCountdown <= 0">Gửi mã OTP xác nhận huỷ</span>
                                    <span x-show="emailOtpCountdown > 0" x-text="'Gửi lại mã OTP sau ' + emailOtpCountdown + 's'"></span>
                                    <span x-show="sendingEmailOtp" class="flex items-center gap-1">
                                        <svg class="animate-spin h-3.5 w-3.5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        Đang gửi mã...
                                    </span>
                                </button>
                            </div>

                            <!-- Nếu đã gửi OTP -->
                            <div x-show="emailSent" class="space-y-4" x-cloak>
                                <p class="text-xs text-emerald-700 dark:text-emerald-300 bg-emerald-50 dark:bg-emerald-950/20 border border-emerald-200 dark:border-emerald-900/30 p-3 rounded-xl leading-relaxed" x-text="emailMessage"></p>
                                <p class="text-xs text-gray-500 dark:text-slate-400">
                                    Nhập mật khẩu tài khoản và mã OTP vừa gửi về email để tắt tính năng xác thực OTP qua email.
                                </p>

                                <!-- Thông báo lỗi/thành công -->
                                <div x-show="errorMessageEmailDisable" class="p-3 text-red-700 dark:text-red-300 bg-red-50 dark:bg-red-950/20 border border-red-100 dark:border-red-900/30 rounded-xl text-xs" x-text="errorMessageEmailDisable" x-cloak></div>
                                <div x-show="successMessageEmailDisable" class="p-3 text-emerald-700 dark:text-emerald-300 bg-emerald-50 dark:bg-emerald-950/20 border border-emerald-100 dark:border-emerald-900/30 rounded-xl text-xs" x-text="successMessageEmailDisable" x-cloak></div>

                                <form @submit.prevent="submitDisableEmail" class="space-y-4">
                                    @csrf
                                    <div>
                                        <label for="disable_email_password" class="block text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider mb-1">Mật khẩu tài khoản</label>
                                        <input type="password" name="password" id="disable_email_password" required placeholder="Nhập mật khẩu của bạn..." class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-700 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-gray-50/50 dark:bg-slate-800/50 dark:text-slate-200 dark:placeholder-slate-500">
                                    </div>
                                    <div>
                                        <label for="disable_email_code" class="block text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider mb-1">Mã xác thực OTP Email</label>
                                        <input type="text" name="otp_code" id="disable_email_code" required placeholder="Ví dụ: 123456" class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-700 rounded-xl text-xs text-center font-bold tracking-widest focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-gray-50/50 dark:bg-slate-800/50 dark:text-slate-200 dark:placeholder-slate-500">
                                    </div>
                                    <div class="flex gap-3">
                                        <button type="button" @click="showEmailOtpDisable = false" class="w-1/2 px-4 py-2.5 text-xs font-semibold text-gray-700 dark:text-slate-300 bg-gray-100 dark:bg-slate-800 hover:bg-gray-200 dark:hover:bg-slate-700 rounded-xl transition-all">Quay lại</button>
                                        <button type="submit" :disabled="loadingEmailDisable" class="w-1/2 px-4 py-2.5 text-xs font-semibold text-white bg-red-600 hover:bg-red-700 rounded-xl transition-all shadow-md shadow-red-100 dark:shadow-none">
                                            <span x-show="!loadingEmailDisable">Xác nhận huỷ</span>
                                            <span x-show="loadingEmailDisable" class="flex items-center gap-1">
                                                <svg class="animate-spin h-3.5 w-3.5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                </svg>
                                                Đang huỷ...
                                            </span>
                                        </button>
                                    </div>
                                    <div class="text-center pt-2">
                                        <button type="button" @click="sendOTP('disable')" :disabled="sendingEmailOtp || emailOtpCountdown > 0" class="text-xs text-shopee hover:underline font-semibold flex items-center justify-center gap-1 mx-auto">
                                            <span x-show="!sendingEmailOtp && emailOtpCountdown <= 0">Gửi lại mã OTP</span>
                                            <span x-show="emailOtpCountdown > 0" x-text="'Gửi lại mã OTP sau ' + emailOtpCountdown + 's'"></span>
                                            <span x-show="sendingEmailOtp" class="flex items-center gap-1">
                                                <svg class="animate-spin h-3 w-3 text-shopee" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                </svg>
                                                Đang gửi...
                                            </span>
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            <!-- Liên kết Bot Telegram & Zalo -->
            <div class="bg-white dark:bg-slate-900 p-5 sm:p-6 rounded-2xl shadow-md border border-gray-100 dark:border-slate-800/80 space-y-5">
                <div class="flex items-center justify-between border-b border-gray-100 dark:border-slate-800 pb-3.5 flex-wrap gap-2">
                    <div class="flex items-center gap-2.5">
                        <span class="w-9 h-9 rounded-xl bg-sky-50 dark:bg-sky-950/30 text-sky-500 flex items-center justify-center shrink-0">
                            <i data-lucide="bot" class="w-4.5 h-4.5"></i>
                        </span>
                        <div>
                            <h3 class="font-bold text-gray-900 dark:text-white text-sm leading-tight">{{ __('Liên kết Bot Telegram & Zalo') }}</h3>
                            <p class="text-[11px] text-gray-400 dark:text-slate-500 leading-tight mt-0.5">{{ __('Tự động gửi thông báo hoàn tiền & nhận link săn deal qua Bot') }}</p>
                        </div>
                    </div>
                    <a href="{{ route('bot.guide') }}" class="inline-flex items-center gap-1 text-xs font-bold text-shopee hover:underline">
                        <i data-lucide="help-circle" class="w-3.5 h-3.5"></i>
                        {{ __('Xem hướng dẫn liên kết') }}
                    </a>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- 1. Telegram Bot -->
                    <div class="p-4 sm:p-5 rounded-2xl border transition-all duration-300 {{ !empty($user->bot_telegram_chat_id) ? 'border-sky-200 dark:border-sky-900/40 bg-sky-50/20 dark:bg-sky-950/10' : 'border-gray-100 dark:border-slate-800 bg-gray-50/30 dark:bg-slate-900/40' }} flex flex-col justify-between space-y-4">
                        <div class="space-y-3">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2.5">
                                    <span class="w-8 h-8 rounded-xl bg-sky-500 text-white flex items-center justify-center shrink-0 shadow-sm shadow-sky-500/20">
                                        <i data-lucide="send" class="w-4 h-4"></i>
                                    </span>
                                    <div>
                                        <h4 class="font-bold text-xs text-gray-900 dark:text-slate-100">Telegram Bot</h4>
                                        <p class="text-[10px] text-gray-400 dark:text-slate-500">
                                            @if(!empty($user->bot_telegram_chat_id))
                                                ID: <span class="font-mono font-semibold text-gray-700 dark:text-slate-300">{{ $user->bot_telegram_chat_id }}</span>
                                            @else
                                                {{ __('Chưa liên kết tài khoản') }}
                                            @endif
                                        </p>
                                    </div>
                                </div>
                                @if(!empty($user->bot_telegram_chat_id))
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-600 border border-emerald-100 dark:bg-emerald-950/30 dark:text-emerald-400 dark:border-emerald-900/30">
                                        <span class="w-1 h-1 rounded-full bg-emerald-500 animate-pulse"></span>
                                        {{ __('Đã liên kết') }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-600 border border-amber-100 dark:bg-amber-950/30 dark:text-amber-400 dark:border-amber-900/30">
                                        <span class="w-1 h-1 rounded-full bg-amber-500"></span>
                                        {{ __('Chưa liên kết') }}
                                    </span>
                                @endif
                            </div>
                            <p class="text-[11px] text-gray-500 dark:text-slate-400 leading-relaxed">
                                {{ __('Nhận thông báo số dư, biến động đơn hàng hoàn tiền và tương tác trực tiếp với Telegram Bot.') }}
                            </p>
                        </div>

                        <div class="pt-2 border-t border-gray-100 dark:border-slate-800/80">
                            @if(!empty($user->bot_telegram_chat_id))
                                <form action="{{ route('profile.unlink.bot') }}" method="POST" onsubmit="return confirm('{{ __('Sếp có chắc chắn muốn gỡ liên kết Bot Telegram khỏi tài khoản?') }}')">
                                    @csrf
                                    <input type="hidden" name="type" value="telegram">
                                    <button type="submit"
                                        @if(config('app.demo')) disabled @endif
                                        class="w-full inline-flex items-center justify-center gap-1.5 px-4 py-2.5 text-xs font-semibold text-red-600 dark:text-red-400 @if(config('app.demo')) bg-gray-100 text-gray-400 cursor-not-allowed opacity-60 @else bg-red-50 dark:bg-red-950/20 hover:bg-red-100 dark:hover:bg-red-900/30 border border-red-200 dark:border-red-900/30 @endif rounded-xl transition-all active:scale-[0.98]">
                                        <i data-lucide="link-2-off" class="w-3.5 h-3.5"></i>
                                        {{ config('app.demo') ? __('Bị khóa ở Demo') : __('Gỡ liên kết Telegram Bot') }}
                                    </button>
                                </form>
                            @else
                                <a href="{{ route('bot.guide') }}" class="w-full inline-flex items-center justify-center gap-1.5 px-4 py-2.5 text-xs font-semibold text-sky-600 dark:text-sky-400 bg-sky-50 dark:bg-sky-950/20 hover:bg-sky-100 dark:hover:bg-sky-900/30 border border-sky-200 dark:border-sky-900/30 rounded-xl transition-all active:scale-[0.98]">
                                    <i data-lucide="link-2" class="w-3.5 h-3.5"></i>
                                    {{ __('Liên kết Telegram Bot ngay') }}
                                </a>
                            @endif
                        </div>
                    </div>

                    <!-- 2. Zalo Bot -->
                    <div class="p-4 sm:p-5 rounded-2xl border transition-all duration-300 {{ !empty($user->bot_zalo_chat_id) ? 'border-blue-200 dark:border-blue-900/40 bg-blue-50/20 dark:bg-blue-950/10' : 'border-gray-100 dark:border-slate-800 bg-gray-50/30 dark:bg-slate-900/40' }} flex flex-col justify-between space-y-4">
                        <div class="space-y-3">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2.5">
                                    <span class="w-8 h-8 rounded-xl bg-blue-600 text-white flex items-center justify-center shrink-0 shadow-sm shadow-blue-600/20">
                                        <i data-lucide="message-circle" class="w-4 h-4"></i>
                                    </span>
                                    <div>
                                        <h4 class="font-bold text-xs text-gray-900 dark:text-slate-100">Zalo Bot</h4>
                                        <p class="text-[10px] text-gray-400 dark:text-slate-500">
                                            @if(!empty($user->bot_zalo_chat_id))
                                                ID: <span class="font-mono font-semibold text-gray-700 dark:text-slate-300">{{ $user->bot_zalo_chat_id }}</span>
                                            @else
                                                {{ __('Chưa liên kết tài khoản') }}
                                            @endif
                                        </p>
                                    </div>
                                </div>
                                @if(!empty($user->bot_zalo_chat_id))
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-600 border border-emerald-100 dark:bg-emerald-950/30 dark:text-emerald-400 dark:border-emerald-900/30">
                                        <span class="w-1 h-1 rounded-full bg-emerald-500 animate-pulse"></span>
                                        {{ __('Đã liên kết') }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-600 border border-amber-100 dark:bg-amber-950/30 dark:text-amber-400 dark:border-amber-900/30">
                                        <span class="w-1 h-1 rounded-full bg-amber-500"></span>
                                        {{ __('Chưa liên kết') }}
                                    </span>
                                @endif
                            </div>
                            <p class="text-[11px] text-gray-500 dark:text-slate-400 leading-relaxed">
                                {{ __('Nhận thông báo qua Zalo Official Account / OA Bot khi có biến động tài khoản và cashback mới.') }}
                            </p>
                        </div>

                        <div class="pt-2 border-t border-gray-100 dark:border-slate-800/80">
                            @if(!empty($user->bot_zalo_chat_id))
                                <form action="{{ route('profile.unlink.bot') }}" method="POST" onsubmit="return confirm('{{ __('Sếp có chắc chắn muốn gỡ liên kết Bot Zalo khỏi tài khoản?') }}')">
                                    @csrf
                                    <input type="hidden" name="type" value="zalo">
                                    <button type="submit"
                                        @if(config('app.demo')) disabled @endif
                                        class="w-full inline-flex items-center justify-center gap-1.5 px-4 py-2.5 text-xs font-semibold text-red-600 dark:text-red-400 @if(config('app.demo')) bg-gray-100 text-gray-400 cursor-not-allowed opacity-60 @else bg-red-50 dark:bg-red-950/20 hover:bg-red-100 dark:hover:bg-red-900/30 border border-red-200 dark:border-red-900/30 @endif rounded-xl transition-all active:scale-[0.98]">
                                        <i data-lucide="link-2-off" class="w-3.5 h-3.5"></i>
                                        {{ config('app.demo') ? __('Bị khóa ở Demo') : __('Gỡ liên kết Zalo Bot') }}
                                    </button>
                                </form>
                            @else
                                <a href="{{ route('bot.guide') }}" class="w-full inline-flex items-center justify-center gap-1.5 px-4 py-2.5 text-xs font-semibold text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-950/20 hover:bg-blue-100 dark:hover:bg-blue-900/30 border border-blue-200 dark:border-blue-900/30 rounded-xl transition-all active:scale-[0.98]">
                                    <i data-lucide="link-2" class="w-3.5 h-3.5"></i>
                                    {{ __('Liên kết Zalo Bot ngay') }}
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Các phiên hoạt động gần đây -->
            <div class="bg-white dark:bg-slate-900 p-5 sm:p-6 rounded-2xl shadow-md border border-gray-100 dark:border-slate-800/80 space-y-5">
                <div class="flex items-center gap-2.5 border-b border-gray-100 dark:border-slate-800 pb-3.5">
                    <span class="w-9 h-9 rounded-xl bg-shopee/10 dark:bg-shopee/20 text-shopee dark:text-shopee-light flex items-center justify-center shrink-0">
                        <i data-lucide="history" class="w-4.5 h-4.5"></i>
                    </span>
                    <div>
                        <h3 class="font-bold text-gray-900 dark:text-white text-sm leading-tight">{{ __('Các phiên hoạt động gần đây') }}</h3>
                        <p class="text-[11px] text-gray-400 dark:text-slate-500 leading-tight mt-0.5">{{ __('Thiết bị & địa chỉ IP đã đăng nhập') }}</p>
                    </div>
                </div>

                <div class="space-y-3">
                    @forelse($loginSessions as $session)
                    <div class="flex items-start gap-3 p-3.5 sm:p-4 rounded-2xl border transition-all {{ $session->is_current_device ? 'border-shopee/20 dark:border-shopee/30 bg-shopee/5 dark:bg-shopee/10' : 'border-gray-100 dark:border-slate-800 bg-gray-50/40 dark:bg-slate-800/30' }}">
                        <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0 border {{ $session->is_current_device ? 'bg-shopee/10 dark:bg-shopee/20 text-shopee dark:text-shopee-light border-shopee/20 dark:border-shopee/30' : 'bg-white dark:bg-slate-900 text-gray-400 dark:text-slate-500 border-gray-100 dark:border-slate-800' }}">
                            <i data-lucide="{{ $session->icon }}" class="w-5 h-5"></i>
                        </div>
                        <div class="flex-1 min-w-0 space-y-1.5">
                            <div class="flex flex-wrap items-center gap-1.5">
                                <span class="text-xs font-bold text-gray-800 dark:text-slate-200">{{ $session->device_os }}</span>
                                <span class="text-[10px] px-2 py-0.5 rounded-full font-semibold bg-gray-100 dark:bg-slate-800 text-gray-500 dark:text-slate-400">
                                    {{ $session->browser }}
                                </span>
                                @if($session->is_current_device)
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[9px] font-bold bg-emerald-50 dark:bg-emerald-950/30 text-emerald-600 dark:text-emerald-400 border border-emerald-100 dark:border-emerald-900/30">
                                    <span class="w-1 h-1 rounded-full bg-emerald-500 animate-pulse"></span>
                                    {{ __('Phiên hiện tại') }}
                                </span>
                                @endif
                            </div>
                            <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-[10px] text-gray-400 dark:text-slate-500">
                                <span class="font-mono">IP: <span class="font-bold text-gray-600 dark:text-slate-300">{{ $session->ip_address }}</span></span>
                                <span class="inline-flex items-center gap-1" title="{{ $session->created_at }}">
                                    <i data-lucide="clock" class="w-3 h-3"></i>
                                    {{ $session->created_at->diffForHumans() }}
                                </span>
                                <span class="hidden sm:inline">{{ $session->created_at->format('H:i d/m/Y') }}</span>
                            </div>
                        </div>
                        @if(!$session->is_current_device)
                        <form action="{{ route('profile.logout_session', $session->id) }}" method="POST" class="inline shrink-0">
                            @csrf
                            <button type="submit"
                                onclick="return confirm('Bạn có chắc chắn muốn đăng xuất thiết bị này không?')"
                                class="p-2 rounded-xl text-gray-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-950/30 transition-all"
                                title="Đăng xuất thiết bị này">
                                <i data-lucide="log-out" class="w-4 h-4"></i>
                            </button>
                        </form>
                        @endif
                    </div>
                    @empty
                    <div class="py-8 text-center">
                        <i data-lucide="monitor-off" class="w-8 h-8 text-gray-300 dark:text-slate-600 mx-auto mb-2"></i>
                        <p class="text-xs text-gray-400 dark:text-slate-500">{{ __('Chưa có dữ liệu phiên hoạt động.') }}</p>
                    </div>
                    @endforelse
                </div>
            </div>

            <!-- Đăng xuất thiết bị khác -->
            <div class="bg-white dark:bg-slate-900 p-5 sm:p-6 rounded-2xl shadow-md border border-gray-100 dark:border-slate-800/80 space-y-5">
                <div class="flex items-center gap-2.5 border-b border-gray-100 dark:border-slate-800 pb-3.5">
                    <span class="w-9 h-9 rounded-xl bg-red-50 dark:bg-red-950/30 text-red-500 flex items-center justify-center shrink-0">
                        <i data-lucide="shield-alert" class="w-4.5 h-4.5"></i>
                    </span>
                    <div>
                        <h3 class="font-bold text-red-600 dark:text-red-400 text-sm leading-tight">{{ __('Quản lý các phiên đăng nhập') }}</h3>
                        <p class="text-[11px] text-gray-400 dark:text-slate-500 leading-tight mt-0.5">{{ __('Bảo mật nâng cao') }}</p>
                    </div>
                </div>
                <p class="text-xs text-gray-500 dark:text-slate-400 leading-relaxed">
                    {{ __('Nếu bạn nghi ngờ tài khoản của bạn bị rò rỉ, bạn có thể thực hiện đăng xuất tài khoản khỏi tất cả các thiết bị, trình duyệt khác ngoại trừ phiên hiện tại.') }}
                </p>

                <form action="{{ route('profile.logout_devices') }}" method="POST" class="max-w-md space-y-4">
                    @csrf
                    <div>
                        <label for="confirm_password" class="block text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider mb-1.5">{{ __('Nhập mật khẩu tài khoản để xác nhận') }}</label>
                        <div class="relative">
                            <i data-lucide="key-round" class="w-4 h-4 text-gray-400 dark:text-slate-500 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                            <input type="password" name="password" id="confirm_password" required placeholder="{{ __('Nhập mật khẩu hiện tại...') }}" class="block w-full pl-10 pr-4 py-3 sm:py-2.5 border border-gray-200 dark:border-slate-700 rounded-2xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-gray-50/50 dark:bg-slate-800/50 dark:text-slate-200 dark:placeholder-slate-500">
                        </div>
                    </div>
                    <button type="submit"
                        @if(config('app.demo')) disabled @endif
                        class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-5 py-3 sm:py-2.5 text-xs font-semibold text-white @if(config('app.demo')) bg-gray-300 text-gray-500 cursor-not-allowed opacity-60 @else bg-red-600 hover:bg-red-700 shadow-red-100 dark:shadow-none active:scale-[0.98] @endif rounded-2xl transition-all shadow-md">
                        <i data-lucide="log-out" class="w-4 h-4"></i>
                        {{ config('app.demo') ? __('Tính năng bị khóa ở chế độ Demo') : __('Đăng xuất khỏi tất cả các thiết bị khác') }}
                    </button>
                </form>
            </div>



            {{-- ===== VÙNG NGUY HIỂM: TỰ XÓA TÀI KHOẢN ===== --}}
            @if(\App\Models\Setting::getVal('allow_self_delete_account', '0') === '1')
            <div class="bg-white dark:bg-slate-900 p-5 sm:p-6 rounded-2xl shadow-md border border-red-100 dark:border-red-900/40 space-y-5"
                x-data="{ showDeleteAccount: false }">
                <div class="flex items-center gap-2.5 border-b border-gray-100 dark:border-slate-800 pb-3.5">
                    <span class="w-9 h-9 rounded-xl bg-red-50 dark:bg-red-950/30 text-red-500 flex items-center justify-center shrink-0">
                        <i data-lucide="trash-2" class="w-4.5 h-4.5"></i>
                    </span>
                    <div>
                        <h3 class="font-bold text-red-600 dark:text-red-400 text-sm leading-tight">{{ __('Xóa tài khoản') }}</h3>
                        <p class="text-[11px] text-gray-400 dark:text-slate-500 leading-tight mt-0.5">{{ __('Vùng nguy hiểm') }}</p>
                    </div>
                </div>

                <div class="p-3 bg-red-50 border border-red-100 text-[11px] text-red-700 rounded-2xl dark:bg-red-950/20 dark:border-red-900/30 dark:text-red-300 leading-relaxed">
                    {{ __('Khi xóa tài khoản, toàn bộ dữ liệu của bạn (lịch sử hoàn tiền, hoa hồng giới thiệu, thông báo, phiên đăng nhập...) sẽ bị xóa vĩnh viễn và không thể khôi phục. Vui lòng rút hết số dư trong ví trước khi thực hiện.') }}
                </div>

                <button type="button"
                    @if(config('app.demo')) disabled @else @click="showDeleteAccount = true" @endif
                    class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-5 py-3 sm:py-2.5 text-xs font-semibold text-white @if(config('app.demo')) bg-gray-300 text-gray-500 cursor-not-allowed opacity-60 @else bg-red-600 hover:bg-red-700 shadow-red-100 dark:shadow-none active:scale-[0.98] @endif rounded-2xl transition-all shadow-md">
                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                    {{ config('app.demo') ? __('Tính năng bị khóa ở chế độ Demo') : __('Xóa tài khoản của tôi') }}
                </button>

                {{-- Modal xác nhận xóa tài khoản --}}
                {{-- Business Rule: Sử dụng <template x-teleport="body"> để đưa modal ra ngoài stacking context, tránh bị đè backdrop. Sử dụng backdrop-blur-sm với z-[999] để đảm bảo trải nghiệm đồng nhất với các modal bảo mật khác. --}}
                <template x-teleport="body">
                    <div x-show="showDeleteAccount" @click.self="showDeleteAccount = false" @keydown.escape.window="showDeleteAccount = false" class="fixed inset-0 z-[999] flex items-center justify-center bg-slate-950/60 backdrop-blur-sm p-4" x-cloak x-transition>
                        <div class="bg-white dark:bg-slate-900 rounded-2xl max-w-md w-full p-6 space-y-4 shadow-xl border border-gray-100 dark:border-slate-800" x-transition>
                            <div class="flex items-center justify-between border-b border-gray-100 dark:border-slate-800 pb-3">
                                <h3 class="font-bold text-red-600 dark:text-red-400 text-sm flex items-center gap-1.5">
                                    <i data-lucide="alert-triangle" class="w-4.5 h-4.5 text-red-500"></i> {{ __('Xác nhận xóa tài khoản') }}
                                </h3>
                                <button type="button" @click="showDeleteAccount = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-slate-200">
                                    <i data-lucide="x" class="w-5 h-5"></i>
                                </button>
                            </div>

                            <p class="text-xs text-gray-500 dark:text-slate-400 leading-relaxed">
                                {{ __('Hành động này không thể hoàn tác. Toàn bộ dữ liệu tài khoản của bạn sẽ bị xóa vĩnh viễn. Vui lòng nhập mật khẩu để xác nhận.') }}
                            </p>

                            <form action="{{ route('profile.delete') }}" method="POST" class="space-y-4">
                                @csrf
                                <div>
                                    <label for="delete_account_password" class="block text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider mb-1">{{ __('Mật khẩu tài khoản') }}</label>
                                    <input type="password" name="password" id="delete_account_password" required placeholder="{{ __('Nhập mật khẩu của bạn...') }}" class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-700 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-red-500/20 focus:border-red-500 bg-gray-50/50 dark:bg-slate-800/50 dark:text-slate-200 dark:placeholder-slate-500">
                                </div>
                                <div class="flex gap-3">
                                    <button type="button" @click="showDeleteAccount = false" class="w-1/2 px-4 py-2.5 text-xs font-semibold text-gray-700 dark:text-slate-300 bg-gray-100 dark:bg-slate-800 hover:bg-gray-200 dark:hover:bg-slate-700 rounded-xl transition-all">{{ __('Quay lại') }}</button>
                                    <button type="submit" class="w-1/2 px-4 py-2.5 text-xs font-semibold text-white bg-red-600 hover:bg-red-700 rounded-xl transition-all shadow-md shadow-red-100 dark:shadow-none">{{ __('Xác nhận xóa') }}</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </template>
            </div>
            @endif

        </div>
    </div>
</div>
@endsection