@extends('layouts.app')

@section('title', __('Quên Mật Khẩu') . ' - ' . $siteName)

@section('meta_robots')
<meta name="robots" content="noindex, nofollow">
@endsection

@section('content')
<div class="flex items-center justify-center min-h-[70vh] py-12 px-4 sm:px-6 lg:px-8 bg-gradient-to-b from-shopee-bg/25 to-transparent">
    <div class="w-full max-w-md space-y-8 bg-white p-8 rounded-3xl shadow-xl border border-gray-100">
        <!-- Tiêu đề -->
        <div class="text-center">
            <div class="mx-auto flex items-center justify-center w-12 h-12 text-white rounded-2xl bg-gradient-to-tr from-shopee to-shopee-light shadow-md shadow-shopee/20">
                <i data-lucide="key-round" class="w-6 h-6"></i>
            </div>
            <h2 class="mt-4 text-3xl font-extrabold text-gray-900 tracking-tight">{{ __('Quên mật khẩu?') }}</h2>
            <p class="mt-2 text-sm text-gray-500">
                {{ __('Nhập địa chỉ email của bạn để nhận liên kết khôi phục mật khẩu tài khoản') }}
            </p>
        </div>

        {{-- Ghi chú cho thành viên đăng ký bằng Số điện thoại: khôi phục mật khẩu chỉ hoạt động khi tài khoản đã có email --}}
        @if(\App\Models\Setting::getVal('register_identifier_phone', '0') === '1')
        <div class="mt-6 p-3 bg-amber-50 border border-amber-100 rounded-2xl text-[11px] text-amber-800 flex items-start gap-2">
            <i data-lucide="info" class="w-3.5 h-3.5 shrink-0 mt-px text-amber-500"></i>
            <span>{{ __('Nếu bạn đăng ký bằng số điện thoại và chưa bổ sung email, hệ thống chưa thể gửi liên kết khôi phục. Vui lòng liên hệ bộ phận hỗ trợ để được trợ giúp.') }}</span>
        </div>
        @endif

        <!-- Form gửi link -->
        <form class="mt-8 space-y-6" action="{{ route('password.email') }}" method="POST">
            @csrf

            <div>
                <label for="email" class="block text-sm font-semibold text-gray-700 mb-1">{{ __('Địa chỉ Email của bạn') }}</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                        <i data-lucide="mail" class="w-4 h-4"></i>
                    </div>
                    <input id="email" 
                           name="email" 
                           type="email" 
                           required 
                           value="{{ old('email') }}"
                           class="appearance-none block w-full pl-10 pr-3 py-3 border border-gray-200 rounded-2xl placeholder-gray-400 text-sm focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee transition-all bg-gray-50/50" 
                           placeholder="email-dang-ky@example.com">
                </div>
            </div>

            <!-- Cloudflare Turnstile Captcha -->
            @if(\App\Models\Setting::getVal('turnstile_status', '0') === '1' && \App\Models\Setting::getVal('turnstile_on_forgot_password', '0') === '1')
            <div class="my-3">
                <div class="flex justify-center">
                    <div class="cf-turnstile" data-sitekey="{{ \App\Models\Setting::getVal('turnstile_site_key') }}"></div>
                </div>
                @error('cf-turnstile-response')
                <span class="text-xs text-red-500 block text-center mt-1 font-medium">{{ $message }}</span>
                @enderror
            </div>
            @endif

            <div>
                <button type="submit" class="w-full flex justify-center py-3 px-4 border border-transparent text-sm font-semibold rounded-2xl text-white bg-shopee hover:bg-shopee-dark focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-shopee transition-all shadow-lg shadow-shopee/10">
                    {{ __('Gửi Liên Kết Khôi Phục') }}
                </button>
            </div>
        </form>

        <div class="text-center mt-4">
            <a href="{{ route('login') }}" class="font-bold text-sm text-shopee hover:text-shopee-dark hover:underline flex items-center justify-center gap-1">
                <i data-lucide="arrow-left" class="w-4 h-4"></i> {{ __('Quay lại trang đăng nhập') }}
            </a>
        </div>
    </div>
</div>
@endsection
