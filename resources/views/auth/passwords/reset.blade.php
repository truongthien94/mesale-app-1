@extends('layouts.app')

@section('title', __('Đặt Lại Mật Khẩu') . ' - ' . $siteName)

@section('meta_robots')
<meta name="robots" content="noindex, nofollow">
@endsection

@section('content')
<div class="flex items-center justify-center min-h-[75vh] py-12 px-4 sm:px-6 lg:px-8 bg-gradient-to-b from-shopee-bg/25 to-transparent">
    <div class="w-full max-w-md space-y-8 bg-white p-8 rounded-3xl shadow-xl border border-gray-100">
        <!-- Tiêu đề -->
        <div class="text-center">
            <div class="mx-auto flex items-center justify-center w-12 h-12 text-white rounded-2xl bg-gradient-to-tr from-shopee to-shopee-light shadow-md shadow-shopee/20">
                <i data-lucide="shield-alert" class="w-6 h-6"></i>
            </div>
            <h2 class="mt-4 text-3xl font-extrabold text-gray-900 tracking-tight">{{ __('Mật khẩu mới') }}</h2>
            <p class="mt-2 text-sm text-gray-500">
                {{ __('Nhập mật khẩu mới cho tài khoản email') }} <strong>{{ $email }}</strong>
            </p>
        </div>

        <!-- Form đặt lại -->
        <form class="mt-8 space-y-4" action="{{ route('password.update') }}" method="POST">
            @csrf
            
            <!-- Token ẩn -->
            <input type="hidden" name="token" value="{{ $token }}">
            <input type="hidden" name="email" value="{{ $email }}">

            <!-- Mật khẩu mới -->
            <div>
                <label for="password" class="block text-sm font-semibold text-gray-700 mb-1">{{ __('Mật khẩu mới') }}</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                        <i data-lucide="lock" class="w-4 h-4"></i>
                    </div>
                    <input id="password" 
                           name="password" 
                           type="password" 
                           required 
                           class="appearance-none block w-full pl-10 pr-3 py-3 border border-gray-200 rounded-2xl placeholder-gray-400 text-sm focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee transition-all bg-gray-50/50" 
                           placeholder="{{ __('Tối thiểu 8 ký tự') }}">
                </div>
            </div>

            <!-- Xác nhận mật khẩu mới -->
            <div>
                <label for="password_confirmation" class="block text-sm font-semibold text-gray-700 mb-1">{{ __('Xác nhận mật khẩu mới') }}</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                        <i data-lucide="shield-check" class="w-4 h-4"></i>
                    </div>
                    <input id="password_confirmation" 
                           name="password_confirmation" 
                           type="password" 
                           required 
                           class="appearance-none block w-full pl-10 pr-3 py-3 border border-gray-200 rounded-2xl placeholder-gray-400 text-sm focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee transition-all bg-gray-50/50" 
                           placeholder="{{ __('Nhập lại mật khẩu mới') }}">
                </div>
            </div>

            <div class="pt-2">
                <button type="submit" class="w-full flex justify-center py-3 px-4 border border-transparent text-sm font-semibold rounded-2xl text-white bg-shopee hover:bg-shopee-dark focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-shopee transition-all shadow-lg shadow-shopee/10">
                    {{ __('Cập Nhật Mật Khẩu') }}
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
