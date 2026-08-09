@extends('layouts.admin')

@section('title', __('Chỉnh Sửa Thành Viên') . ' - ' . $siteName)

@section('content')
<div class="space-y-6" x-data="userEditHandler()">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.users.index') }}" 
               class="p-2 bg-white dark:bg-slate-900 border border-gray-100 dark:border-slate-850 rounded-xl text-gray-500 hover:text-shopee hover:border-shopee/30 transition-all shrink-0">
                <i data-lucide="chevron-left" class="w-4 h-4"></i>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ __('Chỉnh sửa thành viên') }}</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                    {{ __('Quản lý tài khoản:') }} <span class="font-semibold text-gray-700 dark:text-gray-300">{{ $user->name }}</span> (ID: {{ $user->id }})
                </p>
            </div>
        </div>
        <div class="flex items-center gap-2 flex-wrap">
            @if($user->id !== auth()->id())
                @if(config('app.demo'))
                    <button type="button" onclick="Swal.fire({
                        icon: 'error',
                        title: '{{ __('Thao tác bị chặn') }}',
                        text: '{{ __('Chức năng đăng nhập nhanh dưới danh nghĩa thành viên bị khóa ở chế độ Demo.') }}',
                        customClass: {
                            container: 'admin-modal',
                            confirmButton: 'inline-flex justify-center items-center gap-2 rounded-xl bg-shopee px-5 py-2.5 text-xs font-bold text-white hover:bg-shopee-dark transition shadow-lg'
                        },
                        buttonsStyling: false
                    })" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold bg-gray-100 text-gray-400 dark:bg-slate-800 dark:text-slate-500 transition-all">
                        <i data-lucide="log-in" class="w-4 h-4"></i>
                        {{ __('Đăng nhập nhanh') }}
                    </button>
                @else
                    <a href="{{ route('admin.users.login_as', $user->id) }}" target="_blank" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold bg-green-50 text-green-600 border border-green-100 hover:bg-green-100 dark:bg-green-950/20 dark:text-green-400 dark:border-green-900/30 dark:hover:bg-green-950/40 transition-all">
                        <i data-lucide="log-in" class="w-4 h-4"></i>
                        {{ __('Đăng nhập nhanh') }}
                    </a>
                @endif
            @endif
            <span class="inline-flex px-3 py-1 rounded-full text-xs font-bold {{ $user->status === 'active' ? 'bg-green-50 text-green-600 border border-green-100 dark:bg-green-950/20 dark:text-green-400 dark:border-green-900/30' : 'bg-red-50 text-red-600 border border-red-100 dark:bg-red-950/20 dark:text-red-400 dark:border-red-900/30' }}">
                {{ $user->status === 'active' ? __('Đang hoạt động') : __('Bị khoá') }}
            </span>
            <span class="inline-flex px-3 py-1 rounded-full text-xs font-bold bg-purple-50 text-purple-600 border border-purple-100 dark:bg-purple-950/20 dark:text-purple-400 dark:border-purple-900/30">
                {{ $user->role === 'admin' ? __('Quản trị viên') : __('Thành viên') }}
            </span>
        </div>
    </div>

    <!-- Alert thông báo thành công / thất bại -->
    @if(session('success'))
        <div class="p-4 bg-green-50 dark:bg-green-950/20 border border-green-200 dark:border-green-900/30 text-green-800 dark:text-green-400 rounded-2xl text-xs flex items-center gap-2">
            <i data-lucide="check-circle" class="w-4 h-4 shrink-0"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif
    @if(session('error'))
        <div class="p-4 bg-red-50 dark:bg-red-950/20 border border-red-200 dark:border-red-900/30 text-red-800 dark:text-red-400 rounded-2xl text-xs flex items-center gap-2">
            <i data-lucide="alert-circle" class="w-4 h-4 shrink-0"></i>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 sm:gap-6">
        
        <!-- CỘT 1 & 2: THÔNG TIN TÀI KHOẢN & BẢO MẬT (col-span-2) -->
        <div class="lg:col-span-2 space-y-4 sm:space-y-6">
            {{-- Form cập nhật thông tin cơ bản: Tối ưu hóa padding p-4 trên mobile, p-6 trên desktop giúp giao diện thoáng đãng --}}
            <form action="{{ route('admin.users.update', $user->id) }}" method="POST" autocomplete="off" class="bg-white dark:bg-slate-900 rounded-3xl p-4 sm:p-6 shadow-sm border border-gray-150 dark:border-slate-800/50 space-y-4 sm:space-y-6">
                @csrf
                
                <!-- Bẫy tự động điền (Autofill Trap) để ngăn trình duyệt tự động điền thông tin đăng nhập của Admin vào form -->
                <input type="text" class="absolute h-0 w-0 opacity-0 pointer-events-none" tabindex="-1" readonly autocomplete="off">
                <input type="password" class="absolute h-0 w-0 opacity-0 pointer-events-none" tabindex="-1" readonly autocomplete="new-password">
                
                <h3 class="text-sm font-bold text-gray-900 dark:text-white uppercase tracking-wider border-b border-gray-100 dark:border-slate-800 pb-3 flex items-center gap-2">
                    <i data-lucide="user" class="w-4 h-4 text-shopee"></i>
                    {{ __('Thông tin tài khoản cơ bản') }}
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        {{-- Hiển thị nhãn Email đăng nhập kèm theo Badge trạng thái xác minh email của thành viên để admin dễ dàng nhận biết --}}
                        <div class="flex items-center justify-between mb-1.5">
                            <label class="block text-[11px] font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">{{ __('Email đăng nhập') }}</label>
                            @if(!is_null($user->email_verified_at))
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[9px] font-bold bg-green-50 text-green-600 border border-green-100 dark:bg-green-950/20 dark:text-green-400 dark:border-green-900/30">
                                    <i data-lucide="check-circle" class="w-3 h-3"></i>
                                    {{ __('Đã xác minh') }}
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[9px] font-bold bg-amber-50 text-amber-600 border border-amber-100 dark:bg-amber-950/20 dark:text-amber-400 dark:border-amber-900/30">
                                    <i data-lucide="alert-circle" class="w-3 h-3"></i>
                                    {{ __('Chưa xác minh') }}
                                </span>
                            @endif
                        </div>
                        {{-- Email được phép để trống với các tài khoản đăng ký bằng Số điện thoại --}}
                        <input type="email" name="email" id="email" value="{{ old('email', $user->email) }}" autocomplete="off" placeholder="{{ __('Chưa thiết lập (đăng ký bằng số điện thoại)') }}" class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-700 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-850 text-gray-900 dark:text-white transition-all">
                        @error('email')
                            <span class="text-[10px] text-red-500 font-medium block mt-1">{{ $message }}</span>
                        @enderror
                    </div>

                    <div>
                        <label for="name" class="block text-[11px] font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1.5">{{ __('Họ và Tên') }}</label>
                        <input type="text" name="name" id="name" required value="{{ old('name', $user->name) }}" autocomplete="off" class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-700 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-850 text-gray-900 dark:text-white transition-all">
                        @error('name')
                            <span class="text-[10px] text-red-500 font-medium block mt-1">{{ $message }}</span>
                        @enderror
                    </div>

                    <div>
                        <label for="phone" class="block text-[11px] font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1.5">{{ __('Số điện thoại') }}</label>
                        <input type="text" name="phone" id="phone" value="{{ old('phone', $user->phone) }}" autocomplete="off" placeholder="{{ __('Chưa thiết lập') }}" class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-700 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-850 text-gray-900 dark:text-white transition-all">
                        @error('phone')
                            <span class="text-[10px] text-red-500 font-medium block mt-1">{{ $message }}</span>
                        @enderror
                    </div>

                    <div>
                        <label for="referral_code" class="block text-[11px] font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1.5">{{ __('Mã giới thiệu (Referral Code)') }}</label>
                        <input type="text" disabled value="{{ $user->referral_code ?? 'N/A' }}" class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-700 rounded-xl text-xs bg-gray-55 dark:bg-slate-800 text-gray-400 dark:text-gray-500 cursor-not-allowed">
                    </div>

                    <!-- Hiển thị chiến dịch UTM Source khi thành viên đăng ký -->
                    <div>
                        <label for="utm_source" class="block text-[11px] font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1.5">{{ __('Nguồn chiến dịch (UTM Source)') }}</label>
                        <input type="text" disabled value="{{ $user->utm_source ?? __('Trực tiếp (Không có)') }}" class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-700 rounded-xl text-xs bg-gray-55 dark:bg-slate-800 text-gray-400 dark:text-gray-500 cursor-not-allowed font-mono">
                    </div>

                    <!-- Địa chỉ IP đăng ký của người dùng -->
                    <div>
                        <label for="ip_address" class="block text-[11px] font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1.5">{{ __('Địa chỉ IP đăng ký') }}</label>
                        <input type="text" disabled value="{{ $user->ip_address ?? __('Không xác định') }}" class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-700 rounded-xl text-xs bg-gray-55 dark:bg-slate-800 text-gray-400 dark:text-gray-500 cursor-not-allowed font-mono">
                    </div>

                    <!-- Quốc gia đăng ký (Geolocated) -->
                    <div>
                        <label for="country" class="block text-[11px] font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1.5">{{ __('Quốc gia đăng ký') }}</label>
                        <input type="text" disabled value="{{ $user->country ?? __('Không xác định') }}" class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-700 rounded-xl text-xs bg-gray-55 dark:bg-slate-800 text-gray-400 dark:text-gray-500 cursor-not-allowed">
                    </div>

                    <!-- Lượt click link giới thiệu -->
                    <div>
                        <label for="referral_clicks" class="block text-[11px] font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1.5">{{ __('Lượt click link giới thiệu') }}</label>
                        <input type="text" disabled value="{{ number_format($user->referral_clicks) }}" class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-700 rounded-xl text-xs bg-gray-55 dark:bg-slate-800 text-gray-400 dark:text-gray-500 cursor-not-allowed">
                    </div>

                    <!-- Thiết bị đăng ký (User Agent) -->
                    <div class="md:col-span-2">
                        <label for="user_agent" class="block text-[11px] font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1.5">{{ __('Thiết bị / Trình duyệt khi đăng ký') }}</label>
                        <textarea disabled rows="2" class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-700 rounded-xl text-xs bg-gray-55 dark:bg-slate-800 text-gray-400 dark:text-gray-500 cursor-not-allowed leading-relaxed">{{ $user->user_agent ?? __('Không xác định') }}</textarea>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 pt-2">
                    <div>
                        <label for="role" class="block text-[11px] font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1.5">{{ __('Vai trò hệ thống') }}</label>
                        <select name="role" id="role" x-model="role" required class="block w-full px-3 py-2.5 border border-gray-200 dark:border-slate-700 rounded-xl text-xs focus:outline-none bg-white dark:bg-slate-850 text-gray-900 dark:text-white">
                            <option value="user">User</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>

                    <div>
                        <label for="status" class="block text-[11px] font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1.5">{{ __('Trạng thái tài khoản') }}</label>
                        <select name="status" id="status" required class="block w-full px-3 py-2.5 border border-gray-200 dark:border-slate-700 rounded-xl text-xs focus:outline-none bg-white dark:bg-slate-850 text-gray-900 dark:text-white">
                            <option value="active" {{ $user->status === 'active' ? 'selected' : '' }}>{{ __('Hoạt động') }}</option>
                            <option value="suspended" {{ $user->status === 'suspended' ? 'selected' : '' }}>{{ __('Khoá tài khoản') }}</option>
                        </select>
                    </div>

                    <div>
                        <label for="email_verified" class="block text-[11px] font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1.5">{{ __('Xác minh Email') }}</label>
                        <select name="email_verified" id="email_verified" required class="block w-full px-3 py-2.5 border border-gray-200 dark:border-slate-700 rounded-xl text-xs focus:outline-none bg-white dark:bg-slate-850 text-gray-900 dark:text-white">
                            <option value="1" {{ !is_null($user->email_verified_at) ? 'selected' : '' }}>{{ __('Đã xác minh') }}</option>
                            <option value="0" {{ is_null($user->email_verified_at) ? 'selected' : '' }}>{{ __('Chưa xác minh') }}</option>
                        </select>
                    </div>
                </div>

                <!-- Phân quyền vai trò cụ thể cho Admin -->
                <div x-show="role === 'admin'" x-transition class="bg-gray-55 dark:bg-slate-800/40 p-4 rounded-2xl border border-gray-100 dark:border-slate-800 space-y-2">
                    <label for="role_id" class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">{{ __('Vai trò cụ thể (Role)') }}</label>
                    <select name="role_id" id="role_id" class="block w-full px-3 py-2.5 border border-gray-200 dark:border-slate-700 rounded-xl text-xs focus:outline-none bg-white dark:bg-slate-900 text-gray-900 dark:text-white">
                        <option value="">{{ __('Super Admin (Toàn quyền hệ thống)') }}</option>
                        @foreach($roles as $r)
                            <option value="{{ $r->id }}" {{ $user->role_id == $r->id ? 'selected' : '' }}>{{ $r->name }}</option>
                        @endforeach
                    </select>
                    <span class="text-[10px] text-gray-400 dark:text-gray-500 font-medium block leading-relaxed">{{ __('Super Admin mặc định có toàn bộ quyền hạn. Chọn vai trò khác để giới hạn các danh mục quản lý cho tài khoản này.') }}</span>
                </div>

                <!-- ĐỔI MẬT KHẨU -->
                <div class="pt-4 border-t border-gray-100 dark:border-slate-800 space-y-4">
                    <h3 class="text-sm font-bold text-gray-900 dark:text-white uppercase tracking-wider flex items-center gap-2">
                        <i data-lucide="key" class="w-4 h-4 text-shopee"></i>
                        {{ __('Đổi mật khẩu tài khoản') }}
                    </h3>
                    <div>
                        <label for="password" class="block text-[11px] font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1.5">{{ __('Mật khẩu mới') }}</label>
                        <input type="password" name="password" id="password" autocomplete="new-password" placeholder="{{ __('Nhập mật khẩu mới nếu muốn thay đổi (tối thiểu 8 ký tự)...') }}" class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-700 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-850 text-gray-900 dark:text-white transition-all">
                        @error('password')
                            <span class="text-[10px] text-red-500 font-medium block mt-1">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <!-- CẤU HÌNH BẢO MẬT -->
                <div class="pt-4 border-t border-gray-100 dark:border-slate-800 space-y-4">
                    <h3 class="text-sm font-bold text-gray-900 dark:text-white uppercase tracking-wider flex items-center gap-2">
                        <i data-lucide="shield-check" class="w-4 h-4 text-shopee"></i>
                        {{ __('Cấu hình bảo mật nâng cao') }}
                    </h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Email OTP -->
                        <div class="border border-gray-150 dark:border-slate-800 p-4 rounded-2xl space-y-3 bg-gray-50/50 dark:bg-slate-800/10">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <div class="p-1.5 bg-orange-50 dark:bg-orange-950/20 text-orange-600 rounded-lg">
                                        <i data-lucide="mail" class="w-4 h-4"></i>
                                    </div>
                                    <div>
                                        <h4 class="text-xs font-bold text-gray-800 dark:text-gray-200">{{ __('Xác thực Email OTP') }}</h4>
                                        <p class="text-[10px] text-gray-400 dark:text-gray-500 font-medium">{{ __('Yêu cầu OTP khi đăng nhập') }}</p>
                                    </div>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer select-none">
                                    <input type="checkbox" name="email_otp_enabled" value="1" {{ $user->email_otp_enabled ? 'checked' : '' }} class="sr-only peer">
                                    <div class="w-8 h-4 bg-gray-200 dark:bg-slate-700 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-3 after:w-3 after:transition-all peer-checked:bg-shopee"></div>
                                </label>
                            </div>
                        </div>

                        <!-- Google 2FA -->
                        <div class="border border-gray-150 dark:border-slate-800 p-4 rounded-2xl space-y-3 bg-gray-50/50 dark:bg-slate-800/10">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <div class="p-1.5 bg-blue-50 dark:bg-blue-950/20 text-blue-600 rounded-lg">
                                        <i data-lucide="smartphone" class="w-4 h-4"></i>
                                    </div>
                                    <div>
                                        <h4 class="text-xs font-bold text-gray-800 dark:text-gray-200">{{ __('Google Authenticator 2FA') }}</h4>
                                        <p class="text-[10px] text-gray-400 dark:text-gray-500 font-medium">{{ __('Mã xác thực 2FA trên thiết bị') }}</p>
                                    </div>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer select-none">
                                    <input type="checkbox" name="google2fa_enabled" value="1" {{ $user->google2fa_enabled ? 'checked' : '' }} class="sr-only peer">
                                    <div class="w-8 h-4 bg-gray-200 dark:bg-slate-700 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-3 after:w-3 after:transition-all peer-checked:bg-shopee"></div>
                                </label>
                            </div>
                            
                            @if($user->google2fa_secret)
                                <div class="pt-2 border-t border-gray-100 dark:border-slate-800 flex justify-between items-center">
                                    <span class="text-[10px] text-gray-500 dark:text-gray-400 font-medium">{{ __('Đã lưu khoá bí mật 2FA') }}</span>
                                    <label class="flex items-center gap-1.5 cursor-pointer text-red-500 hover:text-red-600 select-none">
                                        <input type="checkbox" name="reset_2fa" value="1" class="w-3.5 h-3.5 text-red-600 focus:ring-red-500 rounded border-gray-300 dark:border-slate-700">
                                        <span class="text-[10px] font-bold uppercase tracking-wider">{{ __('Huỷ khoá bí mật') }}</span>
                                    </label>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Nút lưu -->
                <div class="pt-4 border-t border-gray-100 dark:border-slate-800 flex justify-end gap-3">
                    <a href="{{ route('admin.users.index') }}" class="px-4 py-2 border border-gray-200 dark:border-slate-700 rounded-xl text-xs font-bold text-gray-550 dark:text-gray-400 hover:bg-gray-55 dark:hover:bg-slate-800 transition-all">
                        {{ __('Quay lại') }}
                    </a>
                    <button type="submit" class="px-5 py-2.5 bg-shopee hover:bg-shopee-dark text-white font-bold rounded-xl text-xs transition-all shadow-md shadow-shopee/10">
                        {{ __('Lưu thay đổi') }}
                    </button>
                </div>
            </form>
        </div>

        <!-- CỘT 3: VÍ SỐ DƯ & ĐIỀU CHỈNH TIỀN (col-span-1) -->
        <div class="space-y-6">
            <!-- Widget số dư hiện tại -->
            <div class="bg-gradient-to-br from-shopee to-orange-600 dark:from-slate-900 dark:to-slate-800 text-white rounded-3xl p-6 shadow-md relative overflow-hidden">
                <div class="absolute -right-10 -bottom-10 opacity-10 text-white pointer-events-none">
                    <i data-lucide="wallet" class="w-40 h-40"></i>
                </div>
                
                <h4 class="text-xs font-bold uppercase tracking-widest text-orange-100 dark:text-gray-400">{{ __('Ví tiền thành viên') }}</h4>
                <div class="mt-4 space-y-1">
                    <p class="text-xs text-orange-200 dark:text-gray-500 font-medium">{{ __('Khả dụng:') }}</p>
                    <p class="text-3xl font-extrabold tracking-tight">{{ number_format($user->balance) }}đ</p>
                </div>
                
                <div class="grid grid-cols-3 gap-2 mt-6 pt-4 border-t border-white/10 dark:border-slate-800">
                    <!-- Tiền nhận từ hoàn tiền đơn hàng thực tế (approved) -->
                    <div>
                        <p class="text-[9px] sm:text-[10px] text-orange-200 dark:text-gray-550 font-bold uppercase tracking-wider">{{ __('Hoàn tiền đơn') }}</p>
                        <p class="text-xs sm:text-sm font-extrabold text-white mt-0.5">+{{ number_format($order_cashback_earned) }}đ</p>
                    </div>
                    <!-- Tổng toàn bộ số tiền user đã nhận được từ trước đến nay (bao gồm tất cả nguồn) -->
                    <div>
                        <p class="text-[9px] sm:text-[10px] text-orange-200 dark:text-gray-550 font-bold uppercase tracking-wider">{{ __('Tổng nhận được') }}</p>
                        <p class="text-xs sm:text-sm font-extrabold text-white mt-0.5">+{{ number_format($user->total_cashback) }}đ</p>
                    </div>
                    <!-- Tổng tiền đã rút thành công -->
                    <div>
                        <p class="text-[9px] sm:text-[10px] text-orange-200 dark:text-gray-505 font-bold uppercase tracking-wider">{{ __('Đã rút') }}</p>
                        <p class="text-xs sm:text-sm font-extrabold text-white mt-0.5">-{{ number_format($user->total_withdrawn) }}đ</p>
                    </div>
                </div>
            </div>

            <!-- Widget thống kê thưởng sự kiện: Điểm danh, Giftcode, Nhiệm vụ -->
            <div class="bg-white dark:bg-slate-900 rounded-3xl p-4 sm:p-6 shadow-sm border border-gray-150 dark:border-slate-800/50 space-y-4">
                <h3 class="text-xs font-bold text-gray-900 dark:text-white uppercase tracking-wider border-b border-gray-100 dark:border-slate-800 pb-3 flex items-center gap-2">
                    <i data-lucide="gift" class="w-4 h-4 text-shopee"></i>
                    {{ __('Thống kê phần thưởng sự kiện') }}
                </h3>
                <div class="grid grid-cols-3 gap-2">
                    <!-- Tổng tiền thưởng nhận được từ Điểm danh hằng ngày -->
                    <div class="bg-gray-50/50 dark:bg-slate-850 p-3 rounded-2xl text-center space-y-1">
                        <p class="text-[9px] text-gray-400 dark:text-gray-500 font-bold uppercase tracking-wider">{{ __('Điểm danh') }}</p>
                        <p class="text-xs font-extrabold text-green-600 dark:text-green-400 mt-0.5">+{{ number_format($checkin_earned) }}đ</p>
                    </div>
                    <!-- Tổng tiền thưởng nhận được từ việc nhập Giftcode -->
                    <div class="bg-gray-50/50 dark:bg-slate-850 p-3 rounded-2xl text-center space-y-1">
                        <p class="text-[9px] text-gray-400 dark:text-gray-550 font-bold uppercase tracking-wider">{{ __('Giftcode') }}</p>
                        <p class="text-xs font-extrabold text-green-600 dark:text-green-400 mt-0.5">+{{ number_format($giftcode_earned) }}đ</p>
                    </div>
                    <!-- Tổng tiền thưởng nhận được từ việc hoàn thành các Nhiệm vụ -->
                    <div class="bg-gray-50/50 dark:bg-slate-850 p-3 rounded-2xl text-center space-y-1">
                        <p class="text-[9px] text-gray-400 dark:text-gray-550 font-bold uppercase tracking-wider">{{ __('Nhiệm vụ') }}</p>
                        <p class="text-xs font-extrabold text-green-600 dark:text-green-400 mt-0.5">+{{ number_format($tasks_earned) }}đ</p>
                    </div>
                </div>
            </div>

            <!-- Form cộng/trừ tiền thủ công: Tối ưu hóa card padding trên mobile (p-4) và desktop (p-6) -->
            <form action="{{ route('admin.users.adjust_balance', $user->id) }}" method="POST" class="bg-white dark:bg-slate-900 rounded-3xl p-4 sm:p-6 shadow-sm border border-gray-150 dark:border-slate-800/50 space-y-4">
                @csrf
                
                <h3 class="text-xs font-bold text-gray-900 dark:text-white uppercase tracking-wider border-b border-gray-100 dark:border-slate-800 pb-3 flex items-center gap-2">
                    <i data-lucide="coins" class="w-4 h-4 text-shopee"></i>
                    {{ __('Điều chỉnh số dư ví') }}
                </h3>

                <!-- Chọn loại giao dịch -->
                <div>
                    <label class="block text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">{{ __('Loại điều chỉnh') }}</label>
                    <div class="grid grid-cols-2 gap-2">
                        <label class="border p-2 rounded-xl flex items-center justify-center gap-1.5 cursor-pointer select-none transition-all text-xs"
                               :class="balanceType === 'add' ? 'border-shopee bg-orange-50/20 text-shopee font-bold dark:border-shopee dark:bg-shopee/10' : 'border-gray-200 dark:border-slate-700 hover:bg-gray-55 dark:hover:bg-slate-800 text-gray-600 dark:text-gray-300'">
                            <input type="radio" name="type" value="add" x-model="balanceType" class="text-shopee focus:ring-shopee w-3.5 h-3.5">
                            {{ __('Cộng tiền (+)') }}
                        </label>
                        <label class="border p-2 rounded-xl flex items-center justify-center gap-1.5 cursor-pointer select-none transition-all text-xs"
                               :class="balanceType === 'subtract' ? 'border-shopee bg-orange-50/20 text-shopee font-bold dark:border-shopee dark:bg-shopee/10' : 'border-gray-200 dark:border-slate-700 hover:bg-gray-55 dark:hover:bg-slate-800 text-gray-600 dark:text-gray-300'">
                            <input type="radio" name="type" value="subtract" x-model="balanceType" class="text-shopee focus:ring-shopee w-3.5 h-3.5">
                            {{ __('Trừ tiền (-)') }}
                        </label>
                    </div>
                </div>

                <!-- Số tiền -->
                <div>
                    <label for="adjust_amount" class="block text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1.5">{{ __('Số tiền (VNĐ)') }}</label>
                    <input type="number" name="amount" id="adjust_amount" required min="1" placeholder="{{ __('Nhập số tiền muốn cộng/trừ...') }}" class="block w-full px-4 py-2 border border-gray-200 dark:border-slate-700 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-850 text-gray-900 dark:text-white">
                </div>

                <!-- Lý do -->
                <div>
                    <label for="adjust_reason" class="block text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1.5">{{ __('Lý do điều chỉnh') }}</label>
                    <textarea name="reason" id="adjust_reason" required placeholder="{{ __('Ví dụ: Hoàn tiền đơn Shopee bị sót, xử lý lỗi hệ thống...') }}" rows="3" class="block w-full px-4 py-2 border border-gray-200 dark:border-slate-700 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-850 text-gray-900 dark:text-white"></textarea>
                </div>

                <button type="submit" class="w-full py-2.5 bg-orange-600 hover:bg-orange-700 text-white font-bold rounded-xl text-xs transition-all shadow-md mt-2">
                    {{ __('Thực thi giao dịch') }}
                </button>
            </form>
        </div>
    </div>

    <!-- PHẦN DƯỚI: HỆ THỐNG MLM & NHẬT KÝ HOẠT ĐỘNG (Tabs điều hướng AJAX) -->
    <div class="bg-white dark:bg-slate-900 rounded-3xl border border-gray-150 dark:border-slate-800/50 shadow-sm overflow-hidden">
        
        <!-- Headers tabs: Bổ sung -webkit-overflow-scrolling giúp cuộn ngang mượt mà trên thiết bị di động (đặc biệt iOS Safari) -->
        <div class="flex border-b border-gray-100 dark:border-slate-800 bg-gray-50/50 dark:bg-slate-900/60 overflow-x-auto scrollbar-none" style="-webkit-overflow-scrolling: touch;">
            {{-- Điều chỉnh px-4 py-3 trên mobile để tab gọn gàng hơn, px-6 py-4 trên desktop để hiển thị đầy đủ --}}
            <button @click="activeTab = 'mlm'" 
                    class="px-4 py-3 sm:px-6 sm:py-4 text-xs font-bold transition-all border-b-2 whitespace-nowrap flex items-center gap-2"
                    :class="activeTab === 'mlm' ? 'border-shopee text-shopee bg-white dark:bg-slate-900' : 'border-transparent text-gray-400 hover:text-gray-600 dark:hover:text-gray-300'">
                <i data-lucide="users" class="w-4 h-4"></i>
                {{ __('Hệ thống giới thiệu MLM') }}
                <span x-text="mlm.total" class="ml-1.5 px-2 py-0.5 rounded-full text-[10px] bg-gray-100 text-gray-600 dark:bg-slate-800 dark:text-gray-400">0</span>
            </button>
            <button @click="activeTab = 'balance_logs'" 
                    class="px-4 py-3 sm:px-6 sm:py-4 text-xs font-bold transition-all border-b-2 whitespace-nowrap flex items-center gap-2"
                    :class="activeTab === 'balance_logs' ? 'border-shopee text-shopee bg-white dark:bg-slate-900' : 'border-transparent text-gray-400 hover:text-gray-600 dark:hover:text-gray-300'">
                <i data-lucide="file-text" class="w-4 h-4"></i>
                {{ __('Lịch sử ví') }}
                <span x-text="balanceLogs.total" class="ml-1.5 px-2 py-0.5 rounded-full text-[10px] bg-gray-100 text-gray-600 dark:bg-slate-800 dark:text-gray-400">0</span>
            </button>
            <button @click="activeTab = 'cashbacks'" 
                    class="px-4 py-3 sm:px-6 sm:py-4 text-xs font-bold transition-all border-b-2 whitespace-nowrap flex items-center gap-2"
                    :class="activeTab === 'cashbacks' ? 'border-shopee text-shopee bg-white dark:bg-slate-900' : 'border-transparent text-gray-400 hover:text-gray-600 dark:hover:text-gray-300'">
                <i data-lucide="shopping-bag" class="w-4 h-4"></i>
                {{ __('Đơn hàng Cashback') }}
                <span x-text="cashbacks.total" class="ml-1.5 px-2 py-0.5 rounded-full text-[10px] bg-gray-100 text-gray-600 dark:bg-slate-800 dark:text-gray-400">0</span>
            </button>
            <button @click="activeTab = 'activity_logs'" 
                    class="px-4 py-3 sm:px-6 sm:py-4 text-xs font-bold transition-all border-b-2 whitespace-nowrap flex items-center gap-2"
                    :class="activeTab === 'activity_logs' ? 'border-shopee text-shopee bg-white dark:bg-slate-900' : 'border-transparent text-gray-400 hover:text-gray-600 dark:hover:text-gray-300'">
                <i data-lucide="shield-alert" class="w-4 h-4"></i>
                {{ __('Nhật ký hoạt động') }}
                <span x-text="activityLogs.total" class="ml-1.5 px-2 py-0.5 rounded-full text-[10px] bg-gray-100 text-gray-600 dark:bg-slate-800 dark:text-gray-400">0</span>
            </button>
            <button @click="activeTab = 'sessions'" 
                    class="px-4 py-3 sm:px-6 sm:py-4 text-xs font-bold transition-all border-b-2 whitespace-nowrap flex items-center gap-2"
                    :class="activeTab === 'sessions' ? 'border-shopee text-shopee bg-white dark:bg-slate-900' : 'border-transparent text-gray-400 hover:text-gray-600 dark:hover:text-gray-300'">
                <i data-lucide="monitor" class="w-4 h-4"></i>
                {{ __('Phiên hoạt động') }}
                <span x-text="sessions.total" class="ml-1.5 px-2 py-0.5 rounded-full text-[10px] bg-gray-100 text-gray-600 dark:bg-slate-800 dark:text-gray-400">0</span>
            </button>
        </div>

        <!-- Contents tabs: Tối ưu padding p-4 trên thiết bị di động, p-6 trên màn hình lớn để tránh mất không gian ngang hữu ích -->
        <div class="p-4 sm:p-6">
            
            <!-- TAB 1: MLM -->
            <div x-show="activeTab === 'mlm'" x-transition class="space-y-6">
                <!-- Tuyến trên (Người giới thiệu trực tiếp) -->
                <div class="bg-gray-55 dark:bg-slate-800/10 border border-gray-150 dark:border-slate-800 p-4 rounded-2xl">
                    <h4 class="text-xs font-bold text-gray-900 dark:text-white uppercase tracking-wider mb-3 flex items-center gap-1.5">
                        <i data-lucide="arrow-up-circle" class="w-4 h-4 text-indigo-500"></i>
                        {{ __('Người giới thiệu trực tiếp (Tuyến trên)') }}
                    </h4>
                    @if($user->referrer)
                        <div class="flex items-center justify-between text-xs">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-full bg-indigo-50 dark:bg-indigo-950/20 text-indigo-600 flex items-center justify-center font-bold">
                                    {{ strtoupper(substr($user->referrer->name, 0, 1)) }}
                                </div>
                                <div>
                                    <p class="font-bold text-gray-800 dark:text-gray-200">{{ $user->referrer->name }}</p>
                                    <p class="text-[10px] text-gray-400 font-mono">{{ $user->referrer->email }}</p>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="text-[10px] text-gray-400 font-medium">{{ __('Mã giới thiệu:') }} <strong class="text-indigo-600">{{ $user->referrer->referral_code }}</strong></p>
                                <a href="{{ route('admin.users.edit', $user->referrer->id) }}" class="text-[10px] font-bold text-shopee hover:underline mt-1 inline-block">{{ __('Xem chi tiết') }} &rarr;</a>
                            </div>
                        </div>
                    @else
                        <p class="text-xs text-gray-400 dark:text-gray-500 font-medium">{{ __('Không có người giới thiệu (Đăng ký trực tiếp hoặc là Admin chính).') }}</p>
                    @endif
                </div>

                <!-- Lọc & Tìm kiếm MLM -->
                <div class="flex flex-col sm:flex-row gap-3 items-center justify-between">
                    <h4 class="text-xs font-bold text-gray-900 dark:text-white uppercase tracking-wider flex items-center gap-1.5 self-start sm:self-auto">
                        <span class="w-2 h-2 rounded-full bg-shopee"></span>
                        {{ __('Danh sách cấp dưới được giới thiệu') }}
                    </h4>
                    <div class="flex flex-col sm:flex-row gap-2 w-full sm:w-auto items-stretch sm:items-center">
                        <!-- Chọn Level F1/F2 -->
                        <select x-model="mlm.level" @change="loadMlm(true)" class="px-3 py-2 border border-gray-200 dark:border-slate-700 rounded-xl text-xs focus:outline-none bg-white dark:bg-slate-850 text-gray-900 dark:text-white">
                            <option value="all">{{ __('Tất cả các cấp') }}</option>
                            <option value="f1">{{ __('Cấp F1 - Trực tiếp') }}</option>
                            <option value="f2">{{ __('Cấp F2 - Gián tiếp') }}</option>
                        </select>
                        <!-- Tìm kiếm -->
                        <div class="relative w-full sm:w-64">
                            <input type="text" x-model="mlm.search" @keyup.enter="loadMlm(true)" placeholder="{{ __('Tìm tên hoặc email...') }}" class="w-full pl-8 pr-4 py-2 border border-gray-200 dark:border-slate-700 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-850 text-gray-900 dark:text-white">
                            <div class="absolute left-2.5 top-2.5 text-gray-400">
                                <i data-lucide="search" class="w-3.5 h-3.5"></i>
                            </div>
                        </div>
                        <button @click="loadMlm(true)" class="px-3 py-2 bg-shopee hover:bg-shopee-dark text-white rounded-xl text-xs font-bold transition-all shrink-0">
                            {{ __('Tìm') }}
                        </button>
                    </div>
                </div>

                <!-- Bảng danh sách MLM cấp dưới -->
                <div class="border border-gray-100 dark:border-slate-800 rounded-2xl overflow-hidden">
                    {{-- Thêm thanh cuộn ngang và cuộn mượt cho di động, cố định độ rộng tối thiểu để tiêu đề cột không bị co cụm rách chữ --}}
                    <div class="overflow-x-auto scrollbar-thin" style="-webkit-overflow-scrolling: touch;">
                        <table class="w-full text-left border-collapse text-xs min-w-[700px] whitespace-nowrap">
                            <thead>
                                <tr class="bg-gray-50/50 dark:bg-slate-900 border-b border-gray-100 dark:border-slate-800 text-gray-400 font-bold">
                                    <th class="p-3.5">ID</th>
                                    <th class="p-3.5">{{ __('Cấp giới thiệu') }}</th>
                                    <th class="p-3.5">{{ __('Tên / Email') }}</th>
                                    <th class="p-3.5 text-right">{{ __('Số dư khả dụng') }}</th>
                                    <th class="p-3.5 text-center">{{ __('Ngày tham gia') }}</th>
                                    <th class="p-3.5 text-center">{{ __('Hành động') }}</th>
                                </tr>
                            </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-slate-800 text-gray-600 dark:text-gray-300">
                            <template x-for="item in mlm.items" :key="item.id">
                                <tr class="hover:bg-gray-50/50 dark:hover:bg-slate-800/10">
                                    <td class="p-3.5 font-semibold text-gray-400" x-text="item.id"></td>
                                    <td class="p-3.5">
                                        <span class="inline-flex px-2 py-0.5 rounded-full text-[9px] font-bold"
                                              :class="item.level === 'F1' ? 'bg-green-50 text-green-600 border border-green-100 dark:bg-green-950/20 dark:text-green-400 dark:border-green-900/30' : 'bg-blue-50 text-blue-600 border border-blue-100 dark:bg-blue-950/20 dark:text-blue-400 dark:border-blue-900/30'"
                                              x-text="item.level"></span>
                                    </td>
                                    <td class="p-3.5">
                                        <p class="font-bold text-gray-800 dark:text-gray-200" x-text="item.name"></p>
                                        <p class="text-[10px] text-gray-400 font-mono" x-text="item.email"></p>
                                    </td>
                                    <td class="p-3.5 text-right font-extrabold text-indigo-600 dark:text-indigo-400" x-text="item.formatted_balance"></td>
                                    <td class="p-3.5 text-center text-gray-400 font-medium" x-text="item.created_at"></td>
                                    <td class="p-3.5 text-center">
                                        <a :href="item.detail_url" class="text-shopee hover:underline font-bold text-[11px]">{{ __('Xem chi tiết') }} &rarr;</a>
                                    </td>
                                </tr>
                            </template>
                            
                            <!-- Trạng thái trống -->
                            <tr x-show="mlm.items.length === 0 && !mlm.loading">
                                <td colspan="6" class="p-8 text-center text-gray-400">{{ __('Thành viên chưa giới thiệu cấp dưới nào phù hợp với bộ lọc.') }}</td>
                            </tr>

                            <!-- Skeleton Loader khi tải trang hoặc tải thêm -->
                            <tr x-show="mlm.loading">
                                <td colspan="6" class="p-4 space-y-2">
                                    <div class="h-6 bg-gray-100 dark:bg-slate-800 rounded-lg animate-pulse w-full"></div>
                                    <div class="h-6 bg-gray-100 dark:bg-slate-800 rounded-lg animate-pulse w-full"></div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    </div>
                </div>

                <!-- Nút Tải thêm MLM -->
                <div class="flex justify-center" x-show="mlm.hasMore">
                    <button @click="loadMoreMlm()" :disabled="mlm.loading" class="px-6 py-2 border border-gray-250 dark:border-slate-700 hover:border-shopee/40 text-gray-600 hover:text-shopee dark:text-gray-300 dark:hover:text-shopee font-bold rounded-xl text-xs transition-all flex items-center gap-1.5 bg-white dark:bg-slate-900 shadow-sm">
                        <i data-lucide="refresh-cw" class="w-3.5 h-3.5" :class="mlm.loading ? 'animate-spin' : ''"></i>
                        <span x-text="mlm.loading ? '{{ __('Đang tải...') }}' : '{{ __('Tải thêm thành viên') }}'"></span>
                    </button>
                </div>
            </div>

            <!-- TAB 2: LỊCH SỬ VÍ (Balance logs) -->
            <div x-show="activeTab === 'balance_logs'" x-transition class="space-y-6">
                <!-- Lọc & Tìm kiếm Balance Logs -->
                <div class="flex flex-col sm:flex-row gap-3 items-center justify-between">
                    <h4 class="text-xs font-bold text-gray-900 dark:text-white uppercase tracking-wider flex items-center gap-1.5 self-start sm:self-auto">
                        <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                        {{ __('Nhật ký biến động dòng tiền') }}
                    </h4>
                    <div class="flex flex-col sm:flex-row gap-2 w-full sm:w-auto items-stretch sm:items-center">
                        <!-- Chọn Type -->
                        <select x-model="balanceLogs.type" @change="loadBalanceLogs(true)" class="px-3 py-2 border border-gray-200 dark:border-slate-700 rounded-xl text-xs focus:outline-none bg-white dark:bg-slate-850 text-gray-900 dark:text-white">
                            <option value="all">{{ __('Tất cả hoạt động') }}</option>
                            <option value="admin_adjust">{{ __('Admin điều chỉnh') }}</option>
                            <option value="checkin">{{ __('Điểm danh') }}</option>
                            <option value="withdraw">{{ __('Rút tiền') }}</option>
                            <option value="cashback">{{ __('Hoàn tiền mua sắm') }}</option>
                            <option value="referral">{{ __('Hoa hồng giới thiệu') }}</option>
                        </select>
                        <!-- Tìm kiếm -->
                        <div class="relative w-full sm:w-64">
                            <input type="text" x-model="balanceLogs.search" @keyup.enter="loadBalanceLogs(true)" placeholder="{{ __('Tìm nội dung thay đổi...') }}" class="w-full pl-8 pr-4 py-2 border border-gray-200 dark:border-slate-700 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-850 text-gray-900 dark:text-white">
                            <div class="absolute left-2.5 top-2.5 text-gray-400">
                                <i data-lucide="search" class="w-3.5 h-3.5"></i>
                            </div>
                        </div>
                        <button @click="loadBalanceLogs(true)" class="px-3 py-2 bg-shopee hover:bg-shopee-dark text-white rounded-xl text-xs font-bold transition-all shrink-0">
                            {{ __('Tìm') }}
                        </button>
                    </div>
                </div>

                <!-- Bảng Balance Logs -->
                <div class="border border-gray-100 dark:border-slate-800 rounded-2xl overflow-hidden">
                    {{-- Thêm thanh cuộn ngang mượt mà cho bảng khi xem trên thiết bị di động (mobile) --}}
                    <div class="overflow-x-auto scrollbar-thin" style="-webkit-overflow-scrolling: touch;">
                        <table class="w-full text-left border-collapse text-xs min-w-[750px] whitespace-nowrap">
                            <thead>
                                <tr class="bg-gray-50/50 dark:bg-slate-900 border-b border-gray-100 dark:border-slate-800 text-gray-400 font-bold">
                                    <th class="p-4">ID</th>
                                    <th class="p-4">{{ __('Số dư trước') }}</th>
                                    <th class="p-4 text-center">{{ __('Biến động') }}</th>
                                    <th class="p-4 text-right">{{ __('Số dư sau') }}</th>
                                    <th class="p-4">{{ __('Nội dung thay đổi') }}</th>
                                    <th class="p-4 text-center">{{ __('Thời gian') }}</th>
                                </tr>
                            </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-slate-800 text-gray-600 dark:text-gray-300">
                            <template x-for="log in balanceLogs.items" :key="log.id">
                                <tr class="hover:bg-gray-50/50 dark:hover:bg-slate-800/10">
                                    <td class="p-4 text-gray-400 font-medium" x-text="log.id"></td>
                                    <td class="p-4 font-mono" x-text="log.formatted_amount_before"></td>
                                    <td class="p-4 text-center font-bold font-mono">
                                        <span :class="log.amount_change > 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'"
                                              x-text="log.formatted_amount_change"></span>
                                    </td>
                                    <td class="p-4 text-right font-mono font-bold text-gray-900 dark:text-white" x-text="log.formatted_amount_after"></td>
                                    <td class="p-4 max-w-xs break-words">
                                        <span class="inline-flex px-2 py-0.5 text-[9px] font-bold rounded-md bg-gray-100 text-gray-600 dark:bg-slate-800 dark:text-gray-400 mr-1.5 uppercase" x-text="log.type"></span>
                                        <span class="font-medium text-gray-700 dark:text-gray-300" x-text="log.description"></span>
                                    </td>
                                    <td class="p-4 text-center text-gray-400 text-[10px]" x-text="log.created_at"></td>
                                </tr>
                            </template>
                            
                            <!-- Trạng thái trống -->
                            <tr x-show="balanceLogs.items.length === 0 && !balanceLogs.loading">
                                <td colspan="6" class="p-8 text-center text-gray-400">{{ __('Thành viên chưa có biến động dòng tiền nào phù hợp với bộ lọc.') }}</td>
                            </tr>

                            <!-- Skeleton Loader -->
                            <tr x-show="balanceLogs.loading">
                                <td colspan="6" class="p-4 space-y-2">
                                    <div class="h-6 bg-gray-100 dark:bg-slate-800 rounded-lg animate-pulse w-full"></div>
                                    <div class="h-6 bg-gray-100 dark:bg-slate-800 rounded-lg animate-pulse w-full"></div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    </div>
                </div>

                <!-- Nút Tải thêm Lịch sử ví -->
                <div class="flex justify-center" x-show="balanceLogs.hasMore">
                    <button @click="loadMoreBalanceLogs()" :disabled="balanceLogs.loading" class="px-6 py-2 border border-gray-250 dark:border-slate-700 hover:border-shopee/40 text-gray-600 hover:text-shopee dark:text-gray-300 dark:hover:text-shopee font-bold rounded-xl text-xs transition-all flex items-center gap-1.5 bg-white dark:bg-slate-900 shadow-sm">
                        <i data-lucide="refresh-cw" class="w-3.5 h-3.5" :class="balanceLogs.loading ? 'animate-spin' : ''"></i>
                        <span x-text="balanceLogs.loading ? '{{ __('Đang tải...') }}' : '{{ __('Tải thêm nhật ký') }}'"></span>
                    </button>
                </div>
            </div>

            <!-- TAB 3: ĐƠN HÀNG CASHBACK -->
            <div x-show="activeTab === 'cashbacks'" x-transition class="space-y-6">
                <!-- Lọc & Tìm kiếm Cashback -->
                <div class="flex flex-col sm:flex-row gap-3 items-center justify-between">
                    <h4 class="text-xs font-bold text-gray-900 dark:text-white uppercase tracking-wider flex items-center gap-1.5 self-start sm:self-auto">
                        <span class="w-2 h-2 rounded-full bg-green-500"></span>
                        {{ __('Lịch sử mua sắm hoàn tiền Shopee') }}
                    </h4>
                    <div class="flex flex-col sm:flex-row gap-2 w-full sm:w-auto items-stretch sm:items-center">
                        <!-- Chọn Status -->
                        <select x-model="cashbacks.status" @change="loadCashbacks(true)" class="px-3 py-2 border border-gray-200 dark:border-slate-700 rounded-xl text-xs focus:outline-none bg-white dark:bg-slate-850 text-gray-900 dark:text-white">
                            <option value="all">{{ __('Tất cả trạng thái') }}</option>
                            <option value="pending">{{ __('Chờ duyệt') }}</option>
                            <option value="approved">{{ __('Đã duyệt') }}</option>
                            <option value="rejected">{{ __('Từ chối') }}</option>
                        </select>
                        <!-- Tìm kiếm -->
                        <div class="relative w-full sm:w-64">
                            <input type="text" x-model="cashbacks.search" @keyup.enter="loadCashbacks(true)" placeholder="{{ __('Tìm tên sản phẩm hoặc mã đơn...') }}" class="w-full pl-8 pr-4 py-2 border border-gray-200 dark:border-slate-700 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-850 text-gray-900 dark:text-white">
                            <div class="absolute left-2.5 top-2.5 text-gray-400">
                                <i data-lucide="search" class="w-3.5 h-3.5"></i>
                            </div>
                        </div>
                        <button @click="loadCashbacks(true)" class="px-3 py-2 bg-shopee hover:bg-shopee-dark text-white rounded-xl text-xs font-bold transition-all shrink-0">
                            {{ __('Tìm') }}
                        </button>
                    </div>
                </div>

                <!-- Bảng Cashback Histories -->
                <div class="border border-gray-100 dark:border-slate-800 rounded-2xl overflow-hidden">
                    {{-- Thêm thanh cuộn ngang mượt mà cho bảng khi xem trên thiết bị di động (mobile) --}}
                    <div class="overflow-x-auto scrollbar-thin" style="-webkit-overflow-scrolling: touch;">
                        <table class="w-full text-left border-collapse text-xs min-w-[800px] whitespace-nowrap">
                            <thead>
                                <tr class="bg-gray-50/50 dark:bg-slate-900 border-b border-gray-100 dark:border-slate-800 text-gray-400 font-bold">
                                    <th class="p-4">ID</th>
                                    <th class="p-4">{{ __('Sản phẩm / Đơn hàng') }}</th>
                                    <th class="p-4 text-right">{{ __('Giá bán') }}</th>
                                    <th class="p-4 text-right">{{ __('Hoàn tiền') }}</th>
                                    <th class="p-4 text-center">{{ __('Trạng thái') }}</th>
                                    <th class="p-4 text-center">{{ __('Ngày mua') }}</th>
                                </tr>
                            </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-slate-800 text-gray-600 dark:text-gray-300">
                            <template x-for="cb in cashbacks.items" :key="cb.id">
                                <tr class="hover:bg-gray-50/50 dark:hover:bg-slate-800/10">
                                    <td class="p-4 text-gray-400 font-medium" x-text="cb.id"></td>
                                    <td class="p-4">
                                        <div class="flex items-center gap-2">
                                            <template x-if="cb.product_image">
                                                <img :src="cb.product_image" alt="product" class="w-8 h-8 rounded-lg object-cover shrink-0">
                                            </template>
                                            <div class="min-w-0">
                                                <p class="font-bold text-gray-800 dark:text-gray-200 truncate max-w-xs" x-text="cb.product_name"></p>
                                                <p class="text-[9px] text-gray-400 font-mono" x-text="'Code: ' + cb.order_code"></p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="p-4 text-right font-medium" x-text="cb.formatted_order_price"></td>
                                    <td class="p-4 text-right font-extrabold text-green-600 dark:text-green-400" x-text="cb.formatted_cashback_amount"></td>
                                    <td class="p-4 text-center">
                                        <span class="inline-flex px-2 py-0.5 rounded-full text-[9px] font-bold border"
                                              :class="{
                                                  'bg-amber-50 text-amber-600 border-amber-100 dark:bg-amber-950/20 dark:text-amber-400 dark:border-amber-900/30': cb.status === 'pending',
                                                  'bg-green-50 text-green-600 border-green-100 dark:bg-green-950/20 dark:text-green-400 dark:border-green-900/30': cb.status === 'approved',
                                                  'bg-red-50 text-red-600 border-red-100 dark:bg-red-950/20 dark:text-red-400 dark:border-red-900/30': cb.status === 'rejected'
                                              }"
                                              x-text="cb.status === 'pending' ? '{{ __('Chờ duyệt') }}' : (cb.status === 'approved' ? '{{ __('Đã duyệt') }}' : '{{ __('Từ chối') }}')">
                                        </span>
                                    </td>
                                    <td class="p-4 text-center text-gray-400 text-[10px]" x-text="cb.created_at"></td>
                                </tr>
                            </template>
                            
                            <!-- Trạng thái trống -->
                            <tr x-show="cashbacks.items.length === 0 && !cashbacks.loading">
                                <td colspan="6" class="p-8 text-center text-gray-400">{{ __('Thành viên chưa có đơn hàng cashback nào phù hợp với bộ lọc.') }}</td>
                            </tr>

                            <!-- Skeleton Loader -->
                            <tr x-show="cashbacks.loading">
                                <td colspan="6" class="p-4 space-y-2">
                                    <div class="h-6 bg-gray-100 dark:bg-slate-800 rounded-lg animate-pulse w-full"></div>
                                    <div class="h-6 bg-gray-100 dark:bg-slate-800 rounded-lg animate-pulse w-full"></div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    </div>
                </div>

                <!-- Nút Tải thêm Cashback -->
                <div class="flex justify-center" x-show="cashbacks.hasMore">
                    <button @click="loadMoreCashbacks()" :disabled="cashbacks.loading" class="px-6 py-2 border border-gray-250 dark:border-slate-700 hover:border-shopee/40 text-gray-600 hover:text-shopee dark:text-gray-300 dark:hover:text-shopee font-bold rounded-xl text-xs transition-all flex items-center gap-1.5 bg-white dark:bg-slate-900 shadow-sm">
                        <i data-lucide="refresh-cw" class="w-3.5 h-3.5" :class="cashbacks.loading ? 'animate-spin' : ''"></i>
                        <span x-text="cashbacks.loading ? '{{ __('Đang tải...') }}' : '{{ __('Tải thêm đơn hàng') }}'"></span>
                    </button>
                </div>
            </div>

            <!-- TAB 4: NHẬT KÝ HOẠT ĐỘNG (Activity logs) -->
            <div x-show="activeTab === 'activity_logs'" x-transition class="space-y-6">
                <!-- Lọc & Tìm kiếm Activity Logs -->
                <div class="flex flex-col sm:flex-row gap-3 items-center justify-between">
                    <h4 class="text-xs font-bold text-gray-900 dark:text-white uppercase tracking-wider flex items-center gap-1.5 self-start sm:self-auto">
                        <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                        {{ __('Nhật ký hành động & Bảo mật') }}
                    </h4>
                    <div class="flex flex-col sm:flex-row gap-2 w-full sm:w-auto items-stretch sm:items-center">
                        <!-- Tìm kiếm -->
                        <div class="relative w-full sm:w-64">
                            <input type="text" x-model="activityLogs.search" @keyup.enter="loadActivityLogs(true)" placeholder="{{ __('Tìm hoạt động, IP, trình duyệt...') }}" class="w-full pl-8 pr-4 py-2 border border-gray-250 dark:border-slate-700 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-850 text-gray-900 dark:text-white">
                            <div class="absolute left-2.5 top-2.5 text-gray-400">
                                <i data-lucide="search" class="w-3.5 h-3.5"></i>
                            </div>
                        </div>
                        <button @click="loadActivityLogs(true)" class="px-3 py-2 bg-shopee hover:bg-shopee-dark text-white rounded-xl text-xs font-bold transition-all shrink-0">
                            {{ __('Tìm') }}
                        </button>
                    </div>
                </div>

                <!-- Bảng Activity Logs -->
                <div class="border border-gray-100 dark:border-slate-800 rounded-2xl overflow-hidden">
                    {{-- Thêm thanh cuộn ngang mượt mà cho bảng khi xem trên thiết bị di động (mobile) --}}
                    <div class="overflow-x-auto scrollbar-thin" style="-webkit-overflow-scrolling: touch;">
                        <table class="w-full text-left border-collapse text-xs min-w-[750px] whitespace-nowrap">
                            <thead>
                                <tr class="bg-gray-50/50 dark:bg-slate-900 border-b border-gray-100 dark:border-slate-800 text-gray-400 font-bold">
                                    <th class="p-4">ID</th>
                                    <th class="p-4">{{ __('Hoạt động') }}</th>
                                    <th class="p-4 text-center">{{ __('IP Address') }}</th>
                                    <th class="p-4">{{ __('Thiết bị / Trình duyệt') }}</th>
                                    <th class="p-4 text-center">{{ __('Thời gian') }}</th>
                                </tr>
                            </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-slate-800 text-gray-600 dark:text-gray-300">
                            <template x-for="log in activityLogs.items" :key="log.id">
                                <tr class="hover:bg-gray-50/50 dark:hover:bg-slate-800/10">
                                    <td class="p-4 text-gray-400 font-medium" x-text="log.id"></td>
                                    <td class="p-4 font-semibold text-gray-700 dark:text-gray-200" x-text="log.activity"></td>
                                    <td class="p-4 text-center font-mono text-indigo-600 dark:text-indigo-400" x-text="log.ip_address"></td>
                                    <td class="p-4 max-w-xs truncate text-[10px] text-gray-450 dark:text-gray-400" :title="log.user_agent" x-text="log.user_agent"></td>
                                    <td class="p-4 text-center text-gray-400 text-[10px]" x-text="log.created_at"></td>
                                </tr>
                            </template>
                            
                            <!-- Trạng thái trống -->
                            <tr x-show="activityLogs.items.length === 0 && !activityLogs.loading">
                                <td colspan="5" class="p-8 text-center text-gray-400">{{ __('Thành viên chưa có nhật ký hoạt động nào phù hợp với bộ lọc.') }}</td>
                            </tr>

                            <!-- Skeleton Loader -->
                            <tr x-show="activityLogs.loading">
                                <td colspan="5" class="p-4 space-y-2">
                                    <div class="h-6 bg-gray-100 dark:bg-slate-800 rounded-lg animate-pulse w-full"></div>
                                    <div class="h-6 bg-gray-100 dark:bg-slate-800 rounded-lg animate-pulse w-full"></div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    </div>
                </div>

                <!-- Nút Tải thêm Lịch sử hoạt động -->
                <div class="flex justify-center" x-show="activityLogs.hasMore">
                    <button @click="loadMoreActivityLogs()" :disabled="activityLogs.loading" class="px-6 py-2 border border-gray-250 dark:border-slate-700 hover:border-shopee/40 text-gray-600 hover:text-shopee dark:text-gray-300 dark:hover:text-shopee font-bold rounded-xl text-xs transition-all flex items-center gap-1.5 bg-white dark:bg-slate-900 shadow-sm">
                        <i data-lucide="refresh-cw" class="w-3.5 h-3.5" :class="activityLogs.loading ? 'animate-spin' : ''"></i>
                        <span x-text="activityLogs.loading ? '{{ __('Đang tải...') }}' : '{{ __('Tải thêm nhật ký') }}'"></span>
                    </button>
                </div>
            </div>
            
            <!-- TAB 5: PHIÊN HOẠT ĐỘNG (Sessions) -->
            <div x-show="activeTab === 'sessions'" x-transition class="space-y-6">
                <div class="flex flex-col sm:flex-row gap-3 items-center justify-between">
                    <h4 class="text-xs font-bold text-gray-900 dark:text-white uppercase tracking-wider flex items-center gap-1.5 self-start sm:self-auto">
                        <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                        {{ __('Các thiết bị & phiên đăng nhập đang hoạt động') }}
                    </h4>
                </div>

                <!-- Bảng Sessions -->
                <div class="border border-gray-100 dark:border-slate-800 rounded-2xl overflow-hidden">
                    <div class="overflow-x-auto scrollbar-thin" style="-webkit-overflow-scrolling: touch;">
                        <table class="w-full text-left border-collapse text-xs min-w-[750px] whitespace-nowrap">
                            <thead>
                                <tr class="bg-gray-50/50 dark:bg-slate-900 border-b border-gray-100 dark:border-slate-800 text-gray-400 font-bold">
                                    <th class="p-4">IP Address</th>
                                    <th class="p-4">{{ __('Thiết bị / OS') }}</th>
                                    <th class="p-4">{{ __('Trình duyệt') }}</th>
                                    <th class="p-4 text-center">{{ __('Hoạt động gần nhất') }}</th>
                                    <th class="p-4 text-center">{{ __('Hành động') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-slate-800 text-gray-600 dark:text-gray-300">
                                <template x-for="session in sessions.items" :key="session.id">
                                    <tr class="hover:bg-gray-50/50 dark:hover:bg-slate-800/10">
                                        <td class="p-4 font-mono text-indigo-600 dark:text-indigo-400" x-text="session.ip_address"></td>
                                        <td class="p-4">
                                            <div class="flex items-center gap-2">
                                                <i :data-lucide="session.icon" class="w-4 h-4 text-gray-400"></i>
                                                <span class="font-semibold text-gray-700 dark:text-gray-200" x-text="session.device_os"></span>
                                            </div>
                                        </td>
                                        <td class="p-4">
                                            <span class="px-2 py-0.5 rounded-full text-[9px] font-bold bg-gray-100 text-gray-500 dark:bg-slate-800 dark:text-gray-400 border border-gray-150/80 dark:border-slate-800" x-text="session.browser"></span>
                                        </td>
                                        <td class="p-4 text-center text-gray-400 text-[10px]">
                                            <span class="block font-medium" :title="session.created_at" x-text="session.time_ago"></span>
                                            <span x-text="session.created_at"></span>
                                        </td>
                                        <td class="p-4 text-center">
                                            <form :action="'/' + window.adminPrefix + '/users/{{ $user->id }}/logout-session/' + session.id" method="POST" class="inline" @submit="return confirm('Bạn có chắc chắn muốn buộc đăng xuất thiết bị này?')">
                                                @csrf
                                                <button type="submit" class="px-3 py-1.5 bg-red-50 hover:bg-red-100 dark:bg-red-950/20 dark:hover:bg-red-950/40 text-red-650 dark:text-red-400 font-bold rounded-xl text-[10px] transition-all">
                                                    {{ __('Đăng xuất phiên') }}
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                </template>
                                
                                <!-- Trạng thái trống -->
                                <tr x-show="sessions.items.length === 0 && !sessions.loading">
                                    <td colspan="5" class="p-8 text-center text-gray-400">{{ __('Thành viên không có phiên hoạt động nào.') }}</td>
                                </tr>

                                <!-- Skeleton Loader -->
                                <tr x-show="sessions.loading">
                                    <td colspan="5" class="p-4 space-y-2">
                                        <div class="h-6 bg-gray-100 dark:bg-slate-800 rounded-lg animate-pulse w-full"></div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    function userEditHandler() {
        return {
            role: '{{ $user->role }}',
            balanceType: 'add',
            activeTab: 'mlm',
            
            // Trạng thái tab MLM
            mlm: {
                items: [],
                page: 1,
                search: '',
                level: 'all',
                loading: false,
                hasMore: false,
                total: 0
            },

            // Trạng thái tab Lịch sử ví
            balanceLogs: {
                items: [],
                page: 1,
                search: '',
                type: 'all',
                loading: false,
                hasMore: false,
                total: 0
            },

            // Trạng thái tab Cashback
            cashbacks: {
                items: [],
                page: 1,
                search: '',
                status: 'all',
                loading: false,
                hasMore: false,
                total: 0
            },

            // Trạng thái tab Nhật ký hoạt động
            activityLogs: {
                items: [],
                page: 1,
                search: '',
                loading: false,
                hasMore: false,
                total: 0
            },

            // Trạng thái tab Phiên hoạt động
            sessions: {
                items: [],
                loading: false,
                total: 0
            },

            init() {
                // Tải dữ liệu ban đầu cho các tab
                this.loadMlm(true);
                this.loadBalanceLogs(true);
                this.loadCashbacks(true);
                this.loadActivityLogs(true);
                this.loadSessions();

                // Khởi động lại Lucide Icons sau khi DOM thay đổi từ template
                this.$watch('activeTab', () => {
                    this.$nextTick(() => {
                        if (typeof lucide !== 'undefined') {
                            lucide.createIcons();
                        }
                    });
                });
            },

            // Hàm tải MLM
            async loadMlm(reset = false) {
                if (reset) {
                    this.mlm.page = 1;
                    this.mlm.items = [];
                }
                this.mlm.loading = true;
                try {
                    let url = `/${window.adminPrefix}/users/{{ $user->id }}/mlm-ajax?page=${this.mlm.page}&search=${encodeURIComponent(this.mlm.search)}&level=${this.mlm.level}`;
                    let response = await fetch(url);
                    let result = await response.json();
                    if (result.success) {
                        this.mlm.items = [...this.mlm.items, ...result.data];
                        this.mlm.hasMore = result.has_more;
                        this.mlm.total = result.total;
                    }
                } catch (e) {
                    console.error(e);
                } finally {
                    this.mlm.loading = false;
                    this.$nextTick(() => {
                        if (typeof lucide !== 'undefined') {
                            lucide.createIcons();
                        }
                    });
                }
            },

            loadMoreMlm() {
                if (this.mlm.hasMore && !this.mlm.loading) {
                    this.mlm.page++;
                    this.loadMlm();
                }
            },

            // Hàm tải Lịch sử ví
            async loadBalanceLogs(reset = false) {
                if (reset) {
                    this.balanceLogs.page = 1;
                    this.balanceLogs.items = [];
                }
                this.balanceLogs.loading = true;
                try {
                    let url = `/${window.adminPrefix}/users/{{ $user->id }}/balance-logs-ajax?page=${this.balanceLogs.page}&search=${encodeURIComponent(this.balanceLogs.search)}&type=${this.balanceLogs.type}`;
                    let response = await fetch(url);
                    let result = await response.json();
                    if (result.success) {
                        this.balanceLogs.items = [...this.balanceLogs.items, ...result.data];
                        this.balanceLogs.hasMore = result.has_more;
                        this.balanceLogs.total = result.total;
                    }
                } catch (e) {
                    console.error(e);
                } finally {
                    this.balanceLogs.loading = false;
                    this.$nextTick(() => {
                        if (typeof lucide !== 'undefined') {
                            lucide.createIcons();
                        }
                    });
                }
            },

            loadMoreBalanceLogs() {
                if (this.balanceLogs.hasMore && !this.balanceLogs.loading) {
                    this.balanceLogs.page++;
                    this.loadBalanceLogs();
                }
            },

            // Hàm tải Cashback
            async loadCashbacks(reset = false) {
                if (reset) {
                    this.cashbacks.page = 1;
                    this.cashbacks.items = [];
                }
                this.cashbacks.loading = true;
                try {
                    let url = `/${window.adminPrefix}/users/{{ $user->id }}/cashbacks-ajax?page=${this.cashbacks.page}&search=${encodeURIComponent(this.cashbacks.search)}&status=${this.cashbacks.status}`;
                    let response = await fetch(url);
                    let result = await response.json();
                    if (result.success) {
                        this.cashbacks.items = [...this.cashbacks.items, ...result.data];
                        this.cashbacks.hasMore = result.has_more;
                        this.cashbacks.total = result.total;
                    }
                } catch (e) {
                    console.error(e);
                } finally {
                    this.cashbacks.loading = false;
                    this.$nextTick(() => {
                        if (typeof lucide !== 'undefined') {
                            lucide.createIcons();
                        }
                    });
                }
            },

            loadMoreCashbacks() {
                if (this.cashbacks.hasMore && !this.cashbacks.loading) {
                    this.cashbacks.page++;
                    this.loadCashbacks();
                }
            },

            // Hàm tải Nhật ký hoạt động
            async loadActivityLogs(reset = false) {
                if (reset) {
                    this.activityLogs.page = 1;
                    this.activityLogs.items = [];
                }
                this.activityLogs.loading = true;
                try {
                    let url = `/${window.adminPrefix}/users/{{ $user->id }}/activity-logs-ajax?page=${this.activityLogs.page}&search=${encodeURIComponent(this.activityLogs.search)}`;
                    let response = await fetch(url);
                    let result = await response.json();
                    if (result.success) {
                        this.activityLogs.items = [...this.activityLogs.items, ...result.data];
                        this.activityLogs.hasMore = result.has_more;
                        this.activityLogs.total = result.total;
                    }
                } catch (e) {
                    console.error(e);
                } finally {
                    this.activityLogs.loading = false;
                    this.$nextTick(() => {
                        if (typeof lucide !== 'undefined') {
                            lucide.createIcons();
                        }
                    });
                }
            },

            loadMoreActivityLogs() {
                if (this.activityLogs.hasMore && !this.activityLogs.loading) {
                    this.activityLogs.page++;
                    this.loadActivityLogs();
                }
            },

            // Hàm tải Sessions
            async loadSessions() {
                this.sessions.loading = true;
                try {
                    let url = `/${window.adminPrefix}/users/{{ $user->id }}/sessions-ajax`;
                    let response = await fetch(url);
                    let result = await response.json();
                    if (result.success) {
                        this.sessions.items = result.data;
                        this.sessions.total = result.total;
                    }
                } catch (e) {
                    console.error(e);
                } finally {
                    this.sessions.loading = false;
                    this.$nextTick(() => {
                        if (typeof lucide !== 'undefined') {
                            lucide.createIcons();
                        }
                    });
                }
            }
        }
    }
</script>
@endsection
