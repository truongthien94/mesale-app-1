@extends('layouts.app')

{{-- SEO: Title tag trang đăng nhập --}}
@section('title', __('Đăng Nhập Tài Khoản') . ' - ' . $siteName)

{{-- SEO: Meta description cho trang đăng nhập --}}
@section('meta_description', __('Đăng nhập tài khoản thành viên tại :site_name để quản lý ví hoàn tiền, theo dõi đơn hàng và rút tiền nhanh chóng.', ['site_name' => $siteName]))

{{-- 
    SEO: Noindex cho trang xác thực - trang đăng nhập không cần Google index.
    Tránh lãng phí crawl budget và bảo mật thông tin.
--}}
@section('styles')
<meta name="robots" content="noindex, nofollow">
@endsection

@section('content')
@php
    // Cấu hình hiển thị form đăng nhập bằng Email/Mật khẩu và trạng thái đăng nhập Google
    $emailAuthEnabled = \App\Models\Setting::getVal('email_auth_enabled', '1') === '1';
    $googleLoginEnabled = \App\Models\Setting::getVal('google_login_enabled', '0') === '1';
    // Fallback an toàn: chỉ ẩn form Email/Mật khẩu khi Google đã được bật, tránh khóa toàn bộ hệ thống đăng nhập
    $showEmailForm = $emailAuthEnabled || !$googleLoginEnabled;

    // Khi hệ thống cho phép đăng ký bằng Số điện thoại, ô đăng nhập dùng chung cho cả Email lẫn Số điện thoại
    $phoneLoginEnabled = \App\Models\Setting::getVal('register_identifier_phone', '0') === '1';
    $loginLabel = $phoneLoginEnabled ? __('Email hoặc Số điện thoại') : __('Địa chỉ Email');
    $loginPlaceholder = $phoneLoginEnabled ? __('email@example.com hoặc 0987654321') : 'email@example.com';
@endphp
<div class="flex items-center justify-center min-h-[75vh] py-12 px-4 sm:px-6 lg:px-8 bg-gradient-to-b from-shopee-bg/25 to-transparent">
    <div class="w-full max-w-md space-y-8 bg-white p-8 rounded-3xl shadow-xl border border-gray-100">
        <!-- Logo và Tiêu đề -->
        <div class="text-center">
            <div class="mx-auto flex items-center justify-center w-12 h-12 text-white rounded-2xl bg-gradient-to-tr from-shopee to-shopee-light shadow-md shadow-shopee/20">
                <i data-lucide="log-in" class="w-6 h-6"></i>
            </div>
            <h2 class="mt-4 text-3xl font-extrabold text-gray-900 tracking-tight">{{ __('Chào mừng quay lại') }}</h2>
            <p class="mt-2 text-sm text-gray-500">
                {{ __('Đăng nhập để quản lý ví tiền hoàn và thực hiện rút tiền') }}
            </p>
        </div>

        <!-- Form đăng nhập -->
        @if($showEmailForm)
        <form id="login-form" class="mt-8 space-y-6" action="{{ route('login') }}" method="POST">
            @csrf

            <div class="space-y-4 rounded-md">
                <!-- Email hoặc Số điện thoại (dùng chung một ô nhập, hệ thống tự nhận diện định dạng) -->
                <div>
                    <label for="email" class="block text-sm font-semibold text-gray-700 mb-1">{{ $loginLabel }}</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                            <i data-lucide="{{ $phoneLoginEnabled ? 'at-sign' : 'mail' }}" class="w-4 h-4"></i>
                        </div>
                        <input id="email"
                            name="email"
                            type="{{ $phoneLoginEnabled ? 'text' : 'email' }}"
                            required
                            tabindex="1"
                            autocomplete="username"
                            value="{{ app()->environment('local') ? 'user@gmail.com' : old('email') }}"
                            class="appearance-none block w-full pl-10 pr-3 py-3 border border-gray-200 rounded-2xl placeholder-gray-400 text-sm focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee transition-all bg-gray-50/50"
                            placeholder="{{ $loginPlaceholder }}">
                    </div>
                </div>

                <!-- Password -->
                <div>
                    <label for="password" class="block text-sm font-semibold text-gray-700 mb-1">{{ __('Mật khẩu') }}</label>
                    <div class="relative" x-data="{ showPassword: false }">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                            <i data-lucide="lock" class="w-4 h-4"></i>
                        </div>
                        <input id="password"
                            name="password"
                            :type="showPassword ? 'text' : 'password'"
                            required
                            tabindex="2"
                            value="{{ app()->environment('local') ? 'user123' : '' }}"
                            class="appearance-none block w-full pl-10 pr-10 py-3 border border-gray-200 rounded-2xl placeholder-gray-400 text-sm focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee transition-all bg-gray-50/50"
                            placeholder="••••••••">
                        <button type="button" 
                            tabindex="-1"
                            @click="showPassword = !showPassword"
                            class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600 focus:outline-none cursor-pointer">
                            <i x-show="!showPassword" data-lucide="eye" class="w-4.5 h-4.5"></i>
                            <i x-show="showPassword" data-lucide="eye-off" class="w-4.5 h-4.5" x-cloak style="display: none;"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Remember Me & Forgot Password -->
            <!-- Quy tắc nghiệp vụ: Mặc định luôn tích chọn 'Ghi nhớ đăng nhập' để cải thiện trải nghiệm người dùng, tránh phải nhập lại thông tin nhiều lần khi mở ứng dụng PWA -->
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <input id="remember"
                        name="remember"
                        type="checkbox"
                        checked
                        tabindex="3"
                        class="h-4.5 w-4.5 text-shopee focus:ring-shopee border-gray-300 rounded-lg cursor-pointer">
                    <label for="remember" class="ml-2 block text-sm font-medium text-gray-600 cursor-pointer select-none">
                        {{ __('Ghi nhớ đăng nhập') }}
                    </label>
                </div>
                <div class="text-sm">
                    <a href="{{ route('password.request') }}" tabindex="4" class="text-xs font-semibold text-shopee hover:text-shopee-dark hover:underline">{{ __('Quên mật khẩu?') }}</a>
                </div>
            </div>

            <!-- Cloudflare Turnstile Captcha -->
            @if(\App\Models\Setting::getVal('turnstile_status', '0') === '1' && \App\Models\Setting::getVal('turnstile_on_login', '0') === '1')
            <div class="my-3">
                <div class="flex justify-center">
                    <div class="cf-turnstile" data-sitekey="{{ \App\Models\Setting::getVal('turnstile_site_key') }}"></div>
                </div>
                @error('cf-turnstile-response')
                <span class="text-xs text-red-500 block text-center mt-1 font-medium">{{ $message }}</span>
                @enderror
            </div>
            @endif

            <!-- Nút gửi -->
            <div>
                <button type="submit" id="btn-login" tabindex="5" class="group relative w-full flex justify-center items-center py-3 px-4 border border-transparent text-sm font-semibold rounded-2xl text-white bg-shopee hover:bg-shopee-dark focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-shopee transition-all shadow-lg shadow-shopee/10">
                    <span id="btn-login-text">{{ __('Đăng Nhập') }}</span>
                    <svg id="btn-login-loading" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white hidden" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </button>
            </div>
        </form>
        @endif

        @if($googleLoginEnabled)
        @if($showEmailForm)
        <div class="relative flex items-center justify-center my-4">
            <div class="absolute inset-0 flex items-center">
                <div class="w-full border-t border-gray-150"></div>
            </div>
            <span class="relative px-4 text-xs text-gray-400 bg-white uppercase font-bold tracking-wider">{{ __('Hoặc đăng nhập bằng') }}</span>
        </div>
        @endif

        <div class="{{ $showEmailForm ? '' : 'mt-8' }}">
            <a href="{{ route('auth.google') }}" onclick="if(this.dataset.clicked) return false; this.dataset.clicked = 'true'; this.style.pointerEvents = 'none'; this.style.opacity = '0.7';" class="w-full flex justify-center items-center gap-2.5 py-3 px-4 border border-gray-200 text-sm font-semibold rounded-2xl text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-150 transition-all shadow-sm">
                <svg class="w-5 h-5" viewBox="0 0 48 48">
                    <path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/>
                    <path fill="#4285F4" d="M46.5 24c0-1.61-.15-3.16-.42-4.67H24v8.86h12.67C35.15 31.75 30.2 35.8 24 35.8c-6.26 0-11.57-4.22-13.46-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48c12.43 0 22.5-10.07 22.5-22.5z"/>
                    <path fill="#FBBC05" d="M10.54 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.98-6.19z"/>
                    <path fill="#34A853" d="M24 38.5c-6.26 0-11.57-4.22-13.46-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48c6.47 0 11.9-2.38 16.14-6.45l-6.85-6.85c-2.42 1.62-5.53 2.8-9.29 2.8z"/>
                </svg>
                <span>{{ __('Tiếp tục với Google') }}</span>
            </a>
        </div>
        @endif

        @if($showEmailForm && (app()->environment('local') || config('app.demo')))
        <div class="mt-2 border-t border-dashed border-gray-200 pt-4 text-center">
            <span class="text-[10px] font-bold text-gray-400 tracking-wider block mb-2">{{ __('ĐĂNG NHẬP NHANH (MÔI TRƯỜNG DEV)') }}</span>
            <div class="flex justify-center gap-2" x-data>
                <button type="button"
                    @click="document.getElementById('email').value = 'user@gmail.com'; document.getElementById('password').value = 'user123'; $nextTick(() => document.getElementById('btn-login').click());"
                    class="px-3 py-1.5 text-xs font-bold bg-shopee/10 text-shopee rounded-xl hover:bg-shopee/20 transition-all duration-200 cursor-pointer">
                    {{ __('User (F1)') }}
                </button>
                <button type="button"
                    @click="document.getElementById('email').value = 'admin@cmsnt.co'; document.getElementById('password').value = '123456'; $nextTick(() => document.getElementById('btn-login').click());"
                    class="px-3 py-1.5 text-xs font-bold bg-red-50 text-red-600 rounded-xl hover:bg-red-100 transition-all duration-200 cursor-pointer">
                    {{ __('Admin') }}
                </button>
            </div>
        </div>
        @endif

        @if(\App\Models\Setting::getVal('registration_enabled', '1') === '1')
        <div class="text-center mt-4">
            <p class="text-sm text-gray-500">
                {{ __('Chưa có tài khoản?') }}
                <a href="{{ route('register') }}" class="font-bold text-shopee hover:text-shopee-dark hover:underline">{{ __('Đăng ký thành viên ngay') }}</a>
            </p>
        </div>
        @endif
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const loginForm = document.getElementById('login-form');
        const btnLogin = document.getElementById('btn-login');
        const btnLoginText = document.getElementById('btn-login-text');
        const btnLoginLoading = document.getElementById('btn-login-loading');

        if (loginForm) {
            loginForm.addEventListener('submit', function (e) {
                e.preventDefault();

                // Vô hiệu hóa nút bấm và hiển thị trạng thái loading
                btnLogin.disabled = true;
                btnLoginText.classList.add('hidden');
                btnLoginLoading.classList.remove('hidden');

                const formData = new FormData(loginForm);
                
                // Thực hiện gửi request Ajax đăng nhập bằng thư viện Axios
                axios.post(loginForm.action, formData, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => {
                    if (response.data.success) {
                        // Kích hoạt hiển thị Toast thông báo thành công
                        window.dispatchEvent(new CustomEvent('toast', {
                            detail: {
                                text: response.data.message || "{{ __('Đăng nhập thành công!') }}",
                                type: 'success'
                            }
                        }));

                        // Chuyển hướng người dùng sau 1 giây để họ kịp thấy thông báo
                        setTimeout(() => {
                            window.location.href = response.data.redirect;
                        }, 1000);
                    } else {
                        // Xử lý lỗi phát sinh không mong muốn nhưng trả về status 200
                        window.dispatchEvent(new CustomEvent('toast', {
                            detail: {
                                text: response.data.message || "{{ __('Có lỗi xảy ra, vui lòng thử lại.') }}",
                                type: 'error'
                            }
                        }));
                        // Khôi phục lại nút bấm
                        btnLogin.disabled = false;
                        btnLoginText.classList.remove('hidden');
                        btnLoginLoading.classList.add('hidden');
                    }
                })
                .catch(error => {
                    let errorMessage = "{{ __('Có lỗi xảy ra, vui lòng thử lại.') }}";
                    
                    if (error.response) {
                        const data = error.response.data;
                        if (data.errors) {
                            // Trích xuất lỗi đầu tiên từ danh sách lỗi validation của Laravel
                            const firstKey = Object.keys(data.errors)[0];
                            errorMessage = data.errors[firstKey][0];
                        } else if (data.message) {
                            errorMessage = data.message;
                        }
                    }
                    
                    // Kích hoạt hiển thị Toast thông báo lỗi
                    window.dispatchEvent(new CustomEvent('toast', {
                        detail: {
                            text: errorMessage,
                            type: 'error'
                        }
                    }));

                    // Khôi phục lại nút bấm
                    btnLogin.disabled = false;
                    btnLoginText.classList.remove('hidden');
                    btnLoginLoading.classList.add('hidden');

                    // Reset Cloudflare Captcha nếu có để người dùng có thể thực hiện lại
                    if (typeof turnstile !== 'undefined') {
                        turnstile.reset();
                    }
                });
            });
        }
    });
</script>
@endsection