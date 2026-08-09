@extends('layouts.app')

@section('title', __('Xác Minh Địa Chỉ Email') . ' - ' . $siteName)

@section('content')
<div class="flex items-center justify-center min-h-[75vh] py-12 px-4 sm:px-6 lg:px-8 bg-gradient-to-b from-shopee-bg/25 to-transparent"
     x-data="{
        countdown: 0,
        resending: false,
        resendMessage: '',
        resendStatus: '',

        // Bắt đầu đếm ngược thời gian gửi lại OTP (ngăn chặn spam email)
        startCountdown() {
            this.countdown = 60;
            let timer = setInterval(() => {
                this.countdown--;
                if (this.countdown <= 0) {
                    clearInterval(timer);
                }
            }, 1000);
        },

        // Gửi yêu cầu lấy lại OTP email qua API
        async resendOTP() {
            if (this.countdown > 0 || this.resending) return;
            this.resending = true;
            this.resendMessage = '';
            
            try {
                let response = await fetch('{{ route('register.verification.resend') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });
                let data = await response.json();
                if (data.success) {
                    this.resendStatus = 'success';
                    this.resendMessage = data.message;
                    this.startCountdown();
                } else {
                    this.resendStatus = 'error';
                    this.resendMessage = data.message || '{{ __('Gửi lại mã OTP thất bại.') }}';
                }
            } catch (error) {
                this.resendStatus = 'error';
                this.resendMessage = '{{ __('Không thể kết nối đến máy chủ.') }}';
            } finally {
                this.resending = false;
            }
        }
     }">
    <div class="w-full max-w-md space-y-8 bg-white p-8 rounded-3xl shadow-xl border border-gray-100">
        <!-- Logo và Tiêu đề Xác thực -->
        <div class="text-center">
            <div class="mx-auto flex items-center justify-center w-12 h-12 text-white rounded-2xl bg-gradient-to-tr from-shopee to-shopee-light shadow-md shadow-shopee/20">
                <i data-lucide="mail" class="w-6 h-6"></i>
            </div>
            <h2 class="mt-4 text-2xl font-extrabold text-gray-900 tracking-tight">{{ __('Xác minh địa chỉ Email') }}</h2>
            <p class="mt-2 text-xs text-gray-500 leading-relaxed">
                {{ __('Hệ thống đã gửi một mã OTP gồm 6 chữ số tới địa chỉ email của bạn. Vui lòng nhập mã này để kích hoạt tài khoản.') }}
            </p>
        </div>

        @if(session('error'))
            <div class="p-3 bg-red-50 border border-red-100 text-red-700 text-xs rounded-xl font-medium">
                {{ session('error') }}
            </div>
        @endif

        @if(session('success'))
            <div class="p-3 bg-emerald-50 border border-emerald-100 text-emerald-700 text-xs rounded-xl font-medium">
                {{ session('success') }}
            </div>
        @endif

        <!-- Form nhập mã xác thực -->
        <form class="mt-6 space-y-6" action="{{ route('register.verify') }}" method="POST">
            @csrf

            <div class="space-y-5 rounded-md">
                <div>
                    <label for="otp_code" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1 flex items-center gap-1.5">
                        <i data-lucide="key-round" class="w-4 h-4 text-orange-500"></i> {{ __('Mã xác thực OTP Email') }}
                    </label>
                    <div class="relative mb-2">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                            <i data-lucide="mail-open" class="w-4 h-4"></i>
                        </div>
                        <input id="otp_code" 
                               name="otp_code" 
                               type="text" 
                               required 
                               class="appearance-none block w-full pl-10 pr-3 py-3 border border-gray-200 rounded-2xl placeholder-gray-400 text-sm focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee transition-all bg-gray-50/50 font-bold tracking-widest text-center" 
                               placeholder="Mã gồm 6 chữ số">
                    </div>
                    
                    @error('otp_code')
                        <p class="mt-1 text-xs text-red-600 font-semibold mb-2">{{ $message }}</p>
                    @enderror

                    <!-- Khu vực gửi lại OTP với bộ đếm ngược -->
                    <div class="flex items-center justify-between text-xs">
                        <span class="text-gray-400">{{ __('Không nhận được mã?') }}</span>
                        <button type="button" 
                                @click="resendOTP()" 
                                :disabled="countdown > 0 || resending"
                                class="font-bold text-shopee hover:text-shopee-dark hover:underline disabled:text-gray-400 disabled:no-underline transition-all cursor-pointer">
                            <span x-show="countdown === 0 && !resending">{{ __('Gửi lại mã OTP') }}</span>
                            <span x-show="countdown > 0" x-text="'{{ __('Gửi lại sau') }} ' + countdown + 's'"></span>
                            <span x-show="resending" class="flex items-center gap-1"><i data-lucide="loader-2" class="w-3 h-3 animate-spin"></i> {{ __('Đang gửi...') }}</span>
                        </button>
                    </div>

                    <!-- Thông báo trạng thái gửi lại OTP -->
                    <template x-if="resendMessage">
                        <div class="mt-2 p-2.5 rounded-xl text-[11px] leading-relaxed border"
                             :class="resendStatus === 'success' ? 'bg-emerald-50 text-emerald-700 border-emerald-100' : 'bg-red-50 text-red-700 border-red-100'">
                            <span x-text="resendMessage"></span>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Nút kích hoạt tài khoản -->
            <div class="space-y-3">
                <button type="submit" class="group relative w-full flex justify-center py-3 px-4 border border-transparent text-sm font-semibold rounded-2xl text-white bg-shopee hover:bg-shopee-dark focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-shopee transition-all shadow-lg shadow-shopee/10 cursor-pointer">
                    {{ __('Kích hoạt tài khoản') }}
                </button>
                
                <!-- Quay lại trang login chính -->
                <a href="{{ route('login') }}" class="w-full flex justify-center py-2.5 px-4 text-xs font-bold text-gray-500 hover:text-gray-700 bg-gray-50 hover:bg-gray-100 rounded-2xl transition-all border border-gray-200/50">
                    {{ __('Quay lại trang Đăng nhập') }}
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
