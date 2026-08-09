@extends('layouts.app')

{{-- SEO: Title tag trang đăng ký --}}
@section('title', __('Đăng Ký Tài Khoản Mới') . ' - ' . $siteName)

{{-- SEO: Meta description cho trang đăng ký --}}
@section('meta_description', 'Đăng ký tài khoản thành viên miễn phí tại ' . $siteName . '. Nhận hoàn tiền Cashback Shopee và hoa hồng giới thiệu 2 tầng không giới hạn.')

{{-- SEO: Noindex cho trang xác thực --}}
@section('styles')
<meta name="robots" content="noindex, nofollow">
@endsection

@section('content')
<div class="flex items-center justify-center min-h-[85vh] py-12 px-4 sm:px-6 lg:px-8 bg-gradient-to-b from-shopee-bg/25 to-transparent">
    <div class="w-full max-w-md space-y-8 bg-white p-8 rounded-3xl shadow-xl border border-gray-100">
        <!-- Logo và Tiêu đề -->
        <div class="text-center">
            <div class="mx-auto flex items-center justify-center w-12 h-12 text-white rounded-2xl bg-gradient-to-tr from-shopee to-shopee-light shadow-md shadow-shopee/20">
                <i data-lucide="user-plus" class="w-6 h-6"></i>
            </div>
            <h2 class="mt-4 text-3xl font-extrabold text-gray-900 tracking-tight">{{ __('Đăng ký tài khoản') }}</h2>
            <p class="mt-2 text-sm text-gray-500">
                {{ __('Gia nhập Cashback Shopee để nhận hoàn tiền mua hàng không giới hạn') }}
            </p>
        </div>

        <!-- Thông báo người giới thiệu (nếu có cookie giới thiệu) -->
        @if(request()->has('ref') || Cookie::get('referred_by_code'))
            <div class="p-3 bg-orange-50 border border-orange-200 rounded-2xl text-xs text-orange-800 flex items-center gap-2">
                <i data-lucide="gift" class="w-4 h-4 text-shopee animate-bounce"></i>
                <span>{{ __('Bạn đang đăng ký thông qua mã giới thiệu:') }} <strong>{{ request()->get('ref', Cookie::get('referred_by_code')) }}</strong>{{ __('. Nhận ngay nhiều đặc quyền!') }}</span>
            </div>
        @endif

        <!-- Form đăng ký -->
        @php
            // Đọc cấu hình bật/tắt và bắt buộc/tùy chọn của từng trường dữ liệu đăng ký từ bảng settings
            $showName = \App\Models\Setting::getVal('register_field_name', '1') === '1';
            $requireName = \App\Models\Setting::getVal('register_field_name_required', '1') === '1';
            $showPhone = \App\Models\Setting::getVal('register_field_phone', '1') === '1';
            $requirePhone = \App\Models\Setting::getVal('register_field_phone_required', '0') === '1';
            $showTerms = \App\Models\Setting::getVal('register_field_terms', '1') === '1';

            // Phương thức định danh tài khoản mà admin cho phép khi đăng ký (Email và/hoặc Số điện thoại)
            $identifierEmail = \App\Models\Setting::getVal('register_identifier_email', '1') === '1';
            $identifierPhone = \App\Models\Setting::getVal('register_identifier_phone', '0') === '1';

            // Fallback an toàn: nếu admin lỡ tắt cả hai, mặc định quay về đăng ký bằng Email
            if (!$identifierEmail && !$identifierPhone) {
                $identifierEmail = true;
            }

            // Khi bật cả hai, thành viên được tự chọn đăng ký bằng Email hoặc Số điện thoại
            $bothIdentifiers = $identifierEmail && $identifierPhone;
            $defaultRegisterType = old('register_type', $identifierEmail ? 'email' : 'phone');

            // Cấu hình hiển thị form đăng ký bằng Email/Mật khẩu và trạng thái đăng nhập Google
            $emailAuthEnabled = \App\Models\Setting::getVal('email_auth_enabled', '1') === '1';
            $googleLoginEnabled = \App\Models\Setting::getVal('google_login_enabled', '0') === '1';
            // Fallback an toàn: chỉ ẩn form Email/Mật khẩu khi Google đã được bật, tránh khóa toàn bộ chức năng đăng ký
            $showEmailForm = $emailAuthEnabled || !$googleLoginEnabled;
        @endphp
        @if($showEmailForm)
        <form id="register-form" class="mt-6 space-y-4" action="{{ route('register') }}" method="POST"
              x-data="{ registerType: '{{ $defaultRegisterType }}' }">
            @csrf

            <!-- Phương thức định danh tài khoản được gửi kèm để máy chủ biết thành viên đăng ký bằng Email hay Số điện thoại -->
            <input type="hidden" name="register_type" :value="registerType">

            <!-- Bộ chọn hình thức đăng ký (chỉ hiển thị khi admin bật cả Email lẫn Số điện thoại) -->
            @if($bothIdentifiers)
            <div class="grid grid-cols-2 gap-2 p-1 bg-gray-100 rounded-2xl">
                <button type="button"
                        tabindex="-1"
                        @click="registerType = 'email'"
                        :class="registerType === 'email' ? 'bg-white text-shopee shadow-sm' : 'text-gray-500 hover:text-gray-700'"
                        class="flex items-center justify-center gap-1.5 py-2.5 px-3 rounded-xl text-xs font-bold transition-all cursor-pointer">
                    <i data-lucide="mail" class="w-3.5 h-3.5"></i>
                    {{ __('Dùng Email') }}
                </button>
                <button type="button"
                        tabindex="-1"
                        @click="registerType = 'phone'"
                        :class="registerType === 'phone' ? 'bg-white text-shopee shadow-sm' : 'text-gray-500 hover:text-gray-700'"
                        class="flex items-center justify-center gap-1.5 py-2.5 px-3 rounded-xl text-xs font-bold transition-all cursor-pointer">
                    <i data-lucide="smartphone" class="w-3.5 h-3.5"></i>
                    {{ __('Dùng Số điện thoại') }}
                </button>
            </div>
            @endif

            <!-- Họ tên (hiển thị tùy thuộc cấu hình admin) -->
            @if($showName)
            <div>
                <label for="name" class="block text-sm font-semibold text-gray-700 mb-1">{{ __('Họ và tên') }} @if(!$requireName)<span class="text-xs text-gray-400 font-normal">({{ __('Tùy chọn') }})</span>@endif</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                        <i data-lucide="user" class="w-4 h-4"></i>
                    </div>
                    <input id="name" 
                           name="name" 
                           type="text" 
                           tabindex="1"
                           {{ $requireName ? 'required' : '' }}
                           value="{{ old('name') }}"
                           class="appearance-none block w-full pl-10 pr-3 py-3 border border-gray-200 rounded-2xl placeholder-gray-400 text-sm focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee transition-all bg-gray-50/50" 
                           placeholder="{{ __('Nguyễn Văn A') }}">
                </div>
            </div>
            @endif

            <!-- Email (chỉ hiển thị khi thành viên đăng ký bằng Email) -->
            @if($identifierEmail)
            <div x-show="registerType === 'email'" x-cloak>
                <label for="email" class="block text-sm font-semibold text-gray-700 mb-1">{{ __('Địa chỉ Email') }}</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                        <i data-lucide="mail" class="w-4 h-4"></i>
                    </div>
                    <input id="email"
                           name="email"
                           type="email"
                           :required="registerType === 'email'"
                           tabindex="{{ $showName ? '2' : '1' }}"
                           value="{{ old('email') }}"
                           class="appearance-none block w-full pl-10 pr-3 py-3 border border-gray-200 rounded-2xl placeholder-gray-400 text-sm focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee transition-all bg-gray-50/50"
                           placeholder="email-cua-ban@gmail.com">
                </div>
            </div>
            @endif

            <!-- Số điện thoại: vừa là định danh khi đăng ký bằng SĐT, vừa là thông tin liên hệ khi đăng ký bằng Email -->
            @if($showPhone || $identifierPhone)
            <div x-show="registerType === 'phone' || {{ $showPhone ? 'true' : 'false' }}" x-cloak>
                <label for="phone" class="block text-sm font-semibold text-gray-700 mb-1">
                    {{ __('Số điện thoại') }}
                    @if($showPhone && !$requirePhone)
                    <span class="text-xs text-gray-400 font-normal" x-show="registerType === 'email'">({{ __('Tùy chọn') }})</span>
                    @endif
                </label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                        <i data-lucide="phone" class="w-4 h-4"></i>
                    </div>
                    <input id="phone"
                           name="phone"
                           type="tel"
                           inputmode="numeric"
                           tabindex="{{ $identifierPhone ? '2' : '4' }}"
                           :required="registerType === 'phone'{{ $requirePhone ? ' || true' : '' }}"
                           value="{{ old('phone') }}"
                           class="appearance-none block w-full pl-10 pr-3 py-3 border border-gray-200 rounded-2xl placeholder-gray-400 text-sm focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee transition-all bg-gray-50/50"
                           placeholder="0987654321">
                </div>
                <!-- Nhắc nhở nhẹ nhàng: đăng ký bằng SĐT vẫn nên bổ sung email sau để bảo mật tài khoản -->
                <p class="mt-1.5 text-[11px] text-gray-400 leading-relaxed" x-show="registerType === 'phone'" x-cloak>
                    {{ __('Số điện thoại này sẽ là tài khoản đăng nhập của bạn. Bạn có thể bổ sung email sau trong trang Hồ sơ để tăng bảo mật.') }}
                </p>
            </div>
            @endif

            <!-- Mật khẩu (tích hợp nút ẩn/hiện mật khẩu bằng AlpineJS) -->
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
                           class="appearance-none block w-full pl-10 pr-10 py-3 border border-gray-200 rounded-2xl placeholder-gray-400 text-sm focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee transition-all bg-gray-50/50" 
                           placeholder="{{ __('Tối thiểu 8 ký tự') }}">
                    <!-- Nút bấm bật/tắt ẩn hiển thị mật khẩu (sử dụng tabindex="-1" để không làm đứt đoạn phím Tab khi người dùng nhập liệu) -->
                    <button type="button" 
                            tabindex="-1"
                            @click="showPassword = !showPassword"
                            class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600 focus:outline-none cursor-pointer"
                            aria-label="{{ __('Hiển thị hoặc ẩn mật khẩu') }}">
                        <i x-show="!showPassword" data-lucide="eye" class="w-4.5 h-4.5"></i>
                        <i x-show="showPassword" data-lucide="eye-off" class="w-4.5 h-4.5" x-cloak style="display: none;"></i>
                    </button>
                </div>
            </div>

            <!-- Xác nhận Mật khẩu (tích hợp nút ẩn/hiện mật khẩu bằng AlpineJS - Đảm bảo tab thứ 3 luôn focus vào ô này) -->
            <div>
                <label for="password_confirmation" class="block text-sm font-semibold text-gray-700 mb-1">{{ __('Xác nhận mật khẩu') }}</label>
                <div class="relative" x-data="{ showConfirmPassword: false }">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                        <i data-lucide="shield-check" class="w-4 h-4"></i>
                    </div>
                    <input id="password_confirmation" 
                           name="password_confirmation" 
                           :type="showConfirmPassword ? 'text' : 'password'" 
                           required 
                           tabindex="3"
                           class="appearance-none block w-full pl-10 pr-10 py-3 border border-gray-200 rounded-2xl placeholder-gray-400 text-sm focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee transition-all bg-gray-50/50" 
                           placeholder="{{ __('Nhập lại mật khẩu trên') }}">
                    <!-- Nút bấm bật/tắt ẩn hiển thị mật khẩu xác nhận (sử dụng tabindex="-1" để phím Tab điều hướng chuẩn xác) -->
                    <button type="button" 
                            tabindex="-1"
                            @click="showConfirmPassword = !showConfirmPassword"
                            class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600 focus:outline-none cursor-pointer"
                            aria-label="{{ __('Hiển thị hoặc ẩn mật khẩu xác nhận') }}">
                        <i x-show="!showConfirmPassword" data-lucide="eye" class="w-4.5 h-4.5"></i>
                        <i x-show="showConfirmPassword" data-lucide="eye-off" class="w-4.5 h-4.5" x-cloak style="display: none;"></i>
                    </button>
                </div>
            </div>

            <!-- Đồng ý điều khoản (hiển thị tùy thuộc cấu hình admin) -->
            @if($showTerms)
            <div class="flex items-center">
                <input id="terms" 
                       name="terms" 
                       type="checkbox" 
                       required
                       tabindex="5"
                       class="h-4.5 w-4.5 text-shopee focus:ring-shopee border-gray-300 rounded-lg cursor-pointer">
                <label for="terms" class="ml-2 block text-xs font-semibold text-gray-500 cursor-pointer select-none">
                    {{ __('Tôi đồng ý với các') }} <a href="{{ \App\Models\Setting::getVal('footer_terms_url', '#') }}" target="_blank" tabindex="-1" class="text-shopee hover:underline">{{ __('Điều khoản dịch vụ') }}</a> {{ __('và') }} <a href="{{ \App\Models\Setting::getVal('footer_privacy_url', '#') }}" target="_blank" tabindex="-1" class="text-shopee hover:underline">{{ __('Chính sách bảo mật') }}</a>.
                </label>
            </div>
            @endif

            <!-- Cloudflare Turnstile Captcha -->
            @if(\App\Models\Setting::getVal('turnstile_status', '0') === '1' && \App\Models\Setting::getVal('turnstile_on_register', '0') === '1')
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
            <div class="pt-2">
                <button type="submit" id="btn-register" tabindex="6" class="w-full flex justify-center items-center py-3 px-4 border border-transparent text-sm font-semibold rounded-2xl text-white bg-shopee hover:bg-shopee-dark focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-shopee transition-all shadow-lg shadow-shopee/10">
                    <span id="btn-register-text">{{ __('Đăng Ký Tài Khoản') }}</span>
                    <svg id="btn-register-loading" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white hidden" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
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
            <span class="relative px-4 text-xs text-gray-400 bg-white uppercase font-bold tracking-wider">{{ __('Hoặc đăng ký bằng') }}</span>
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

        <div class="text-center mt-4">
            <p class="text-sm text-gray-500">
                {{ __('Đã có tài khoản?') }} 
                <a href="{{ route('login') }}" class="font-bold text-shopee hover:text-shopee-dark hover:underline">{{ __('Đăng nhập tại đây') }}</a>
            </p>
        </div>
    </div>
</div>

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const registerForm = document.getElementById('register-form');
        const btnRegister = document.getElementById('btn-register');
        const btnRegisterText = document.getElementById('btn-register-text');
        const btnRegisterLoading = document.getElementById('btn-register-loading');

        let isRegisterSubmitting = false;
        if (registerForm) {
            registerForm.addEventListener('submit', function (e) {
                e.preventDefault();
                e.stopPropagation();

                if (isRegisterSubmitting) return false;
                isRegisterSubmitting = true;

                // Vô hiệu hóa nút bấm và hiển thị trạng thái loading
                btnRegister.disabled = true;
                btnRegisterText.classList.add('hidden');
                btnRegisterLoading.classList.remove('hidden');

                const formData = new FormData(registerForm);
                
                // Thực hiện gửi request Ajax đăng ký bằng thư viện Axios
                axios.post(registerForm.action, formData, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => {
                    if (response.data && response.data.success) {
                        // Kích hoạt hiển thị Toast thông báo thành công
                        window.dispatchEvent(new CustomEvent('toast', {
                            detail: {
                                text: response.data.message || "{{ __('Đăng ký tài khoản thành công!') }}",
                                type: 'success'
                            }
                        }));

                        // Chuyển hướng người dùng sau 1 giây
                        setTimeout(() => {
                            window.location.href = response.data.redirect || "{{ route('home') }}";
                        }, 1000);
                    } else {
                        isRegisterSubmitting = false;
                        const errorMsg = (response.data && response.data.message) ? response.data.message : "{{ __('Có lỗi xảy ra, vui lòng thử lại.') }}";
                        window.dispatchEvent(new CustomEvent('toast', {
                            detail: {
                                text: errorMsg,
                                type: 'error'
                            }
                        }));
                        btnRegister.disabled = false;
                        btnRegisterText.classList.remove('hidden');
                        btnRegisterLoading.classList.add('hidden');
                    }
                })
                .catch(error => {
                    // Nếu request bị cancel do đang chuyển hướng trang, bỏ qua không hiện thông báo lỗi rác
                    if (axios.isCancel(error)) return;

                    isRegisterSubmitting = false;
                    let errorMessage = "{{ __('Có lỗi xảy ra, vui lòng thử lại.') }}";
                    
                    if (error.response && error.response.data) {
                        const data = error.response.data;
                        if (data.errors) {
                            const firstKey = Object.keys(data.errors)[0];
                            errorMessage = data.errors[firstKey][0];
                        } else if (data.message && data.message !== 'Server Error') {
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
                    btnRegister.disabled = false;
                    btnRegisterText.classList.remove('hidden');
                    btnRegisterLoading.classList.add('hidden');

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
@endsection
