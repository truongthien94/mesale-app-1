{{-- 
    Partial View: Cấu hình kết nối
    Vai trò: Quản lý thiết lập kết nối SMTP gửi Email, kiểm thử gửi Email SMTP bằng Axios, tích hợp mã Google Analytics (SEO), cấu hình Đăng nhập bằng Google Client ID / Secret, cấu hình Telegram Bot gửi thông báo Admin, cấu hình bản quyền hệ thống License Key.
--}}
<div x-show="tab === 'connections'" class="space-y-6" x-transition x-cloak>

    <!-- Cấu hình SMTP Email -->
    <div class="bg-gray-50/50 dark:bg-slate-900/50 rounded-2xl p-4 sm:p-6 border border-gray-150 dark:border-slate-800 space-y-4" x-data="{ testEmail: '', testing: false, testResult: null }">
        <h3 class="text-xs font-bold text-gray-700 uppercase tracking-wider mb-4 pb-2 border-b border-gray-200 flex items-center gap-2">
            <i data-lucide="mail" class="w-4 h-4 text-shopee"></i>
            {{ __('Cấu hình Gửi Email (SMTP)') }}
        </h3>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label for="smtp_status" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">{{ __('Trạng thái Gửi Thư (SMTP)') }}</label>
                <select name="smtp_status"
                    id="smtp_status"
                    class="block w-full px-4 py-2.5 border border-gray-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-300">
                    <option value="1" {{ ($settings['smtp_status'] ?? '1') === '1' ? 'selected' : '' }}>{{ __('BẬT (ON)') }}</option>
                    <option value="0" {{ ($settings['smtp_status'] ?? '1') === '0' ? 'selected' : '' }}>{{ __('TẮT (OFF)') }}</option>
                </select>
            </div>
            <div>
                <label for="smtp_host" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">{{ __('SMTP Host') }}</label>
                <input type="text"
                    name="smtp_host"
                    id="smtp_host"
                    value="{{ $settings['smtp_host'] ?? '' }}"
                    placeholder="smtp.gmail.com"
                    class="block w-full px-4 py-2.5 border border-gray-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-300">
            </div>
            <div>
                <label for="smtp_port" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">{{ __('SMTP Port') }}</label>
                <input type="number"
                    name="smtp_port"
                    id="smtp_port"
                    value="{{ $settings['smtp_port'] ?? '587' }}"
                    placeholder="587"
                    class="block w-full px-4 py-2.5 border border-gray-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-300">
            </div>
            <div>
                <label for="smtp_username" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">{{ __('Tên đăng nhập SMTP') }}</label>
                <input type="text"
                    name="smtp_username"
                    id="smtp_username"
                    value="{{ $settings['smtp_username'] ?? '' }}"
                    placeholder="example@gmail.com"
                    class="block w-full px-4 py-2.5 border border-gray-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-300">
            </div>
            <div>
                <label for="smtp_password" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">{{ __('Mật khẩu SMTP') }}</label>
                <input type="password"
                    name="smtp_password"
                    id="smtp_password"
                    value="{{ $settings['smtp_password'] ?? '' }}"
                    placeholder="••••••••••••"
                    class="block w-full px-4 py-2.5 border border-gray-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-300">
            </div>
            <div>
                <label for="smtp_encryption" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">{{ __('Giao thức mã hóa') }}</label>
                <select name="smtp_encryption"
                    id="smtp_encryption"
                    class="block w-full px-4 py-2.5 border border-gray-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-300">
                    <option value="tls" {{ ($settings['smtp_encryption'] ?? 'tls') === 'tls' ? 'selected' : '' }}>TLS</option>
                    <option value="ssl" {{ ($settings['smtp_encryption'] ?? 'tls') === 'ssl' ? 'selected' : '' }}>SSL</option>
                    <option value="none" {{ ($settings['smtp_encryption'] ?? 'tls') === 'none' ? 'selected' : '' }}>None</option>
                </select>
            </div>
            <div>
                <label for="smtp_from_name" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">{{ __('Tên người gửi (From Name)') }}</label>
                <input type="text"
                    name="smtp_from_name"
                    id="smtp_from_name"
                    value="{{ $settings['smtp_from_name'] ?? '' }}"
                    placeholder="Hoàn Tiền Shopee"
                    class="block w-full px-4 py-2.5 border border-gray-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-300">
            </div>
            <div>
                <label for="smtp_from_address" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">{{ __('Địa chỉ email gửi đi (From Address)') }}</label>
                <input type="email"
                    name="smtp_from_address"
                    id="smtp_from_address"
                    value="{{ $settings['smtp_from_address'] ?? '' }}"
                    placeholder="no-reply@yourdomain.com"
                    class="block w-full px-4 py-2.5 border border-gray-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-300">
                <span class="text-[9px] text-gray-400 mt-1 block">Bắt buộc phải điền email hợp lệ. Ví dụ với Resend: email tên miền đã xác thực của bạn.</span>
            </div>
        </div>

        <!-- Khu vực kiểm thử kết nối SMTP -->
        <div class="p-4 bg-white dark:bg-slate-900 rounded-2xl border border-gray-150 dark:border-slate-800 space-y-3 mt-4">
            <h4 class="text-xs font-bold text-gray-700 uppercase tracking-wider flex items-center gap-1.5">
                <i data-lucide="send" class="w-3.5 h-3.5 text-blue-500"></i>
                {{ __('Kiểm thử cấu hình gửi thư') }}
            </h4>

            <div class="flex flex-col sm:flex-row gap-3">
                <input type="email"
                    x-model="testEmail"
                    placeholder="Nhập địa chỉ email nhận thư thử nghiệm..."
                    class="flex-grow px-4 py-2 border border-gray-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-gray-50/50 dark:bg-slate-900/50 text-gray-700 dark:text-slate-300">

                <button type="button"
                    @click="
                            if(!testEmail) { alert('Vui lòng nhập email nhận thử nghiệm!'); return; }
                            testing = true;
                            testResult = null;
                            axios.post('{{ route('admin.settings.test_smtp') }}', {
                                smtp_host: document.getElementById('smtp_host').value,
                                smtp_port: document.getElementById('smtp_port').value,
                                smtp_username: document.getElementById('smtp_username').value,
                                smtp_password: document.getElementById('smtp_password').value,
                                smtp_encryption: document.getElementById('smtp_encryption').value,
                                smtp_from_name: document.getElementById('smtp_from_name').value,
                                smtp_from_address: document.getElementById('smtp_from_address').value,
                                test_email: testEmail
                            })
                            .then(res => {
                                testResult = { success: true, message: res.data.message };
                            })
                            .catch(err => {
                                const errMsg = err.response && err.response.data && err.response.data.message ? err.response.data.message : 'Lỗi không xác định';
                                testResult = { success: false, message: errMsg };
                            })
                            .finally(() => {
                                testing = false;
                            });
                        "
                    :disabled="testing"
                    class="px-5 py-2 text-xs font-bold text-white bg-blue-600 hover:bg-blue-700 disabled:bg-blue-300 rounded-xl transition-all shadow-md flex items-center justify-center gap-1.5 shrink-0">
                    <template x-if="testing">
                        <svg class="animate-spin h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </template>
                    <template x-if="!testing">
                        <i data-lucide="play" class="w-3.5 h-3.5"></i>
                    </template>
                    <span x-text="testing ? 'Đang gửi...' : 'Gửi thử nghiệm'"></span>
                </button>
            </div>

            <!-- Kết quả kiểm tra -->
            <div x-show="testResult !== null" x-cloak class="text-xs p-3 rounded-xl border transition-all" :class="testResult && testResult.success ? 'bg-emerald-50 border-emerald-200 text-emerald-800' : 'bg-red-50 border-red-200 text-red-800'">
                <div class="flex items-start gap-2">
                    <template x-if="testResult && testResult.success">
                        <i data-lucide="check-circle" class="w-4 h-4 text-emerald-500 shrink-0 mt-0.5 animate-bounce"></i>
                    </template>
                    <template x-if="testResult && !testResult.success">
                        <i data-lucide="alert-circle" class="w-4 h-4 text-red-500 shrink-0 mt-0.5"></i>
                    </template>
                    <span class="font-medium" x-text="testResult ? testResult.message : ''"></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Tích hợp Google & Tracking -->
    <div class="bg-gray-50/50 dark:bg-slate-900/50 rounded-2xl p-4 sm:p-6 border border-gray-150 dark:border-slate-800 space-y-4">
        <h3 class="text-xs font-bold text-gray-700 uppercase tracking-wider mb-4 pb-2 border-b border-gray-200 flex items-center gap-2">
            <i data-lucide="bar-chart-3" class="w-4 h-4 text-shopee"></i>
            {{ __('Tích hợp Google & Tracking') }}
        </h3>

        <div class="grid grid-cols-1 gap-6">
            <div>
                <label for="google_analytics_id" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">{{ __('Google Analytics ID (G-XXXXXXX)') }}</label>
                <input type="text"
                    name="google_analytics_id"
                    id="google_analytics_id"
                    value="{{ $settings['google_analytics_id'] ?? '' }}"
                    placeholder="G-ABC123XYZ"
                    class="block w-full px-4 py-2.5 border border-gray-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-300">
                <span class="text-[10px] text-gray-400 mt-1 block">Nhập mã theo dõi Google Analytics để theo dõi lưu lượng truy cập của website.</span>
            </div>
        </div>
    </div>

    <!-- Cấu hình Đăng nhập Google -->
    <div class="bg-gray-50/50 dark:bg-slate-900/50 rounded-2xl p-4 sm:p-6 border border-gray-150 dark:border-slate-800 space-y-4">
        <div class="flex items-center justify-between mb-4 pb-2 border-b border-gray-200 dark:border-slate-800">
            <h3 class="text-xs font-bold text-gray-700 dark:text-slate-200 uppercase tracking-wider flex items-center gap-2">
                <i data-lucide="log-in" class="w-4 h-4 text-shopee"></i>
                {{ __('Cấu hình Đăng nhập Google') }}
            </h3>
            <button type="button" @click="guideType = 'google'; showGuideModal = true" class="inline-flex items-center gap-1 px-3 py-1 bg-shopee/10 text-shopee hover:bg-shopee hover:text-white text-[10px] font-bold rounded-xl transition-all shadow-sm">
                <i data-lucide="help-circle" class="w-3.5 h-3.5"></i>
                {{ __('Hướng dẫn cấu hình') }}
            </button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="md:col-span-2">
                <label for="google_login_enabled" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">{{ __('Trạng thái Đăng nhập bằng Google') }}</label>
                <select name="google_login_enabled"
                    id="google_login_enabled"
                    class="block w-full px-4 py-2.5 border border-gray-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-300">
                    <option value="1" {{ ($settings['google_login_enabled'] ?? '0') === '1' ? 'selected' : '' }}>{{ __('Bật hoạt động') }}</option>
                    <option value="0" {{ ($settings['google_login_enabled'] ?? '0') === '0' ? 'selected' : '' }}>{{ __('Tắt hoạt động') }}</option>
                </select>
                <span class="text-[10px] text-gray-400 mt-1 block">Cho phép thành viên đăng nhập nhanh vào hệ thống bằng tài khoản Google.</span>
            </div>

            <div>
                <label for="google_client_id" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">{{ __('Google Client ID') }}</label>
                <input type="text"
                    name="google_client_id"
                    id="google_client_id"
                    value="{{ $settings['google_client_id'] ?? '' }}"
                    placeholder="1234567890-abc123xyz.apps.googleusercontent.com"
                    class="block w-full px-4 py-2.5 border border-gray-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-300">
                <span class="text-[10px] text-gray-400 mt-1 block">Google Client ID lấy từ Google Cloud Console.</span>
            </div>

            <div>
                <label for="google_client_secret" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">{{ __('Google Client Secret') }}</label>
                <input type="password"
                    name="google_client_secret"
                    id="google_client_secret"
                    value="{{ $settings['google_client_secret'] ?? '' }}"
                    placeholder="••••••••••••"
                    class="block w-full px-4 py-2.5 border border-gray-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-300">
                <span class="text-[10px] text-gray-400 mt-1 block">Google Client Secret bảo mật từ Google Cloud Console.</span>
            </div>

            <div class="md:col-span-2">
                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">{{ __('Đường dẫn Callback (Authorized Redirect URI)') }}</label>
                <div class="flex gap-2">
                    <input type="text"
                        readonly
                        value="{{ url('auth/google/callback') }}"
                        id="google_callback_url"
                        class="block w-full px-4 py-2.5 border border-gray-200 rounded-xl text-xs bg-gray-100 dark:bg-slate-900/50 text-gray-500 dark:text-slate-400 outline-none">
                    <button type="button"
                        onclick="navigator.clipboard.writeText(document.getElementById('google_callback_url').value); alert('Đã sao chép đường dẫn callback vào bộ nhớ tạm!');"
                        class="px-4 py-2.5 bg-gray-200 hover:bg-gray-300 dark:bg-slate-700 dark:text-slate-200 dark:hover:bg-slate-600 text-xs font-bold rounded-xl transition-all cursor-pointer">
                        {{ __('Sao chép') }}
                    </button>
                </div>
                <span class="text-[10px] text-gray-400 mt-1 block">Sao chép đường dẫn này để khai báo vào mục "Authorized redirect URIs" trong dự án Google Developer Console của bạn.</span>
            </div>
        </div>
    </div>

    <!-- Cấu hình Telegram Bot Notifications -->
    <div class="bg-gray-50/50 dark:bg-slate-900/50 rounded-2xl p-4 sm:p-6 border border-gray-150 dark:border-slate-800 space-y-4">
        <div class="flex items-center justify-between mb-4 pb-2 border-b border-gray-200 dark:border-slate-800">
            <h3 class="text-xs font-bold text-gray-700 dark:text-slate-200 uppercase tracking-wider flex items-center gap-2">
                <i data-lucide="send" class="w-4 h-4 text-shopee"></i>
                {{ __('Thông báo Telegram (Telegram Bot)') }}
            </h3>
            <button type="button" @click="guideType = 'telegram'; showGuideModal = true" class="inline-flex items-center gap-1 px-3 py-1 bg-shopee/10 text-shopee hover:bg-shopee hover:text-white text-[10px] font-bold rounded-xl transition-all shadow-sm">
                <i data-lucide="help-circle" class="w-3.5 h-3.5"></i>
                {{ __('Hướng dẫn cấu hình') }}
            </button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="md:col-span-2">
                <label for="telegram_status" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">{{ __('Trạng thái Telegram Bot') }}</label>
                <select name="telegram_status"
                    id="telegram_status"
                    class="block w-full px-4 py-2.5 border border-gray-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-300">
                    <option value="1" {{ ($settings['telegram_status'] ?? '1') === '1' ? 'selected' : '' }}>{{ __('BẬT (ON)') }}</option>
                    <option value="0" {{ ($settings['telegram_status'] ?? '1') === '0' ? 'selected' : '' }}>{{ __('TẮT (OFF)') }}</option>
                </select>
            </div>
            <div>
                <label for="telegram_bot_token" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">{{ __('Telegram Bot Token') }}</label>
                <input type="text"
                    name="telegram_bot_token"
                    id="telegram_bot_token"
                    value="{{ $settings['telegram_bot_token'] ?? '' }}"
                    placeholder="123456789:ABCdefGhIJKlmNoPQRsTUVwxyZ"
                    class="block w-full px-4 py-2.5 border border-gray-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-300">
            </div>
            <div>
                <label for="telegram_chat_id" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">{{ __('Telegram Chat ID (Nhận thông báo Admin)') }}</label>
                <input type="text"
                    name="telegram_chat_id"
                    id="telegram_chat_id"
                    value="{{ $settings['telegram_chat_id'] ?? '' }}"
                    placeholder="-100123456789"
                    class="block w-full px-4 py-2.5 border border-gray-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-300">
            </div>
        </div>
        <span class="text-[10px] text-gray-400 mt-2 block">Dùng để gửi thông báo tự động cho Quản trị viên khi có yêu cầu rút tiền mới hoặc sự kiện hệ thống.</span>
    </div>

    <!-- Cấu hình Kết nối AI -->
    <div class="bg-gray-50/50 dark:bg-slate-900/50 rounded-2xl p-4 sm:p-6 border border-gray-150 dark:border-slate-800 space-y-4"
        x-data="{
            testingAI: {
                openai: false,
                deepseek: false,
                claude: false,
                gemini: false
            },
            async testAIConnection(provider) {
                const keyInput = document.getElementById('ai_' + provider + '_key');
                if (!keyInput || !keyInput.value.trim()) {
                    Swal.fire({
                        icon: 'warning',
                        title: '{{ __('Thiếu thông tin') }}',
                        text: '{{ __('Vui lòng nhập API Key trước khi thử kết nối.') }}'
                    });
                    return;
                }
                const apiKey = keyInput.value.trim();

                const modelSelect = document.getElementById('ai_' + provider + '_model');
                let model = modelSelect ? modelSelect.value : '';
                if (model === 'custom') {
                    const customInput = document.querySelector('input[name=&quot;ai_' + provider + '_model_custom&quot;]');
                    model = customInput ? customInput.value.trim() : '';
                }

                this.testingAI[provider] = true;

                // Hiển thị loading của SweetAlert2
                Swal.fire({
                    title: '{{ __('Đang kết nối...') }}',
                    text: '{{ __('Hệ thống đang gửi yêu cầu kiểm tra kết nối tới API...') }}',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                try {
                    const response = await axios.post('{{ route('admin.settings.test_ai') }}', {
                        provider: provider,
                        api_key: apiKey,
                        model: model
                    });

                    if (response.data.success) {
                         Swal.fire({
                             icon: 'success',
                             title: '{{ __('Kết nối thành công') }}',
                             text: response.data.message
                         });
                    } else {
                         Swal.fire({
                             icon: 'error',
                             title: '{{ __('Kết nối thất bại') }}',
                             text: response.data.message
                         });
                    }
                } catch (error) {
                    const msg = error.response?.data?.message || '{{ __('Không thể kết nối tới AI API. Vui lòng kiểm tra lại cấu hình.') }}';
                    Swal.fire({
                        icon: 'error',
                        title: '{{ __('Lỗi kết nối') }}',
                        text: msg
                    });
                } finally {
                    this.testingAI[provider] = false;
                }
            }
        }">
        <h3 class="text-xs font-bold text-gray-700 dark:text-slate-200 uppercase tracking-wider mb-4 pb-2 border-b border-gray-200 dark:border-slate-800 flex items-center gap-2">
            <i data-lucide="bot" class="w-4 h-4 text-shopee"></i>
            {{ __('Cấu hình kết nối AI') }}
        </h3>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label for="ai_status" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">{{ __('Trạng thái dịch vụ AI') }}</label>
                <select name="ai_status"
                    id="ai_status"
                    class="block w-full px-4 py-2.5 border border-gray-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-300">
                    <option value="1" {{ ($settings['ai_status'] ?? '0') === '1' ? 'selected' : '' }}>{{ __('BẬT (ON)') }}</option>
                    <option value="0" {{ ($settings['ai_status'] ?? '0') === '0' ? 'selected' : '' }}>{{ __('TẮT (OFF)') }}</option>
                </select>
                <span class="text-[10px] text-gray-400 mt-1 block">{{ __('Bật hoặc tắt toàn bộ các tính năng AI trên website.') }}</span>
            </div>
            <div>
                <label for="ai_default_provider" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">{{ __('Nhà cung cấp AI mặc định') }}</label>
                <select name="ai_default_provider"
                    id="ai_default_provider"
                    class="block w-full px-4 py-2.5 border border-gray-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-300">
                    <option value="openai" {{ ($settings['ai_default_provider'] ?? 'openai') === 'openai' ? 'selected' : '' }}>OpenAI</option>
                    <option value="deepseek" {{ ($settings['ai_default_provider'] ?? 'openai') === 'deepseek' ? 'selected' : '' }}>DeepSeek</option>
                    <option value="claude" {{ ($settings['ai_default_provider'] ?? 'openai') === 'claude' ? 'selected' : '' }}>Anthropic Claude</option>
                    <option value="gemini" {{ ($settings['ai_default_provider'] ?? 'openai') === 'gemini' ? 'selected' : '' }}>Google Gemini</option>
                </select>
                <span class="text-[10px] text-gray-400 mt-1 block">{{ __('Lựa chọn công cụ AI mặc định để thực hiện các tác vụ xử lý thông minh.') }}</span>
            </div>
        </div>

        <div class="border-t border-gray-200 dark:border-slate-800 pt-4 mt-4">
            <h4 class="text-xs font-bold text-gray-700 dark:text-slate-200 uppercase tracking-wider mb-4 flex items-center gap-1.5">
                <i data-lucide="key" class="w-3.5 h-3.5 text-blue-500"></i>
                {{ __('Thông tin kết nối API & Model của từng nhà cung cấp') }}
            </h4>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- OpenAI Box -->
                <div class="p-4 bg-white dark:bg-slate-800/40 rounded-2xl border border-gray-150 dark:border-slate-800 space-y-4"
                     x-data="{ openAIModel: '{{ in_array($settings['ai_openai_model'] ?? 'gpt-4o-mini', ['gpt-4o-mini', 'gpt-4o', 'gpt-3.5-turbo']) ? ($settings['ai_openai_model'] ?? 'gpt-4o-mini') : 'custom' }}', showKey: false }">
                    <div class="flex items-center justify-between pb-2 border-b border-gray-100 dark:border-slate-800">
                        <span class="font-bold text-xs text-gray-800 dark:text-slate-200 flex items-center gap-1.5">
                            <i data-lucide="cpu" class="w-4 h-4 text-emerald-500"></i>
                            OpenAI
                        </span>
                        <button type="button" 
                            @click="testAIConnection('openai')"
                            :disabled="testingAI.openai"
                            class="inline-flex items-center gap-1 px-2.5 py-1 bg-emerald-50 hover:bg-emerald-100 text-emerald-600 dark:bg-emerald-950/30 dark:hover:bg-emerald-900/40 dark:text-emerald-400 text-[10px] font-bold rounded-lg transition-all border border-emerald-100 dark:border-emerald-900/30 shadow-sm cursor-pointer disabled:opacity-50">
                            <span x-show="testingAI.openai" class="w-3 h-3 border-2 border-emerald-600 border-t-transparent rounded-full animate-spin"></span>
                            <i x-show="!testingAI.openai" data-lucide="play" class="w-3 h-3"></i>
                            {{ __('Kiểm tra kết nối') }}
                        </button>
                    </div>
                    <div>
                        <label for="ai_openai_key" class="block text-[11px] font-bold text-gray-700 dark:text-slate-300 uppercase tracking-wider mb-1">{{ __('OpenAI API Key') }}</label>
                        <div class="relative">
                            <input :type="showKey ? 'text' : 'password'"
                                name="ai_openai_key"
                                id="ai_openai_key"
                                value="{{ $settings['ai_openai_key'] ?? '' }}"
                                placeholder="sk-..."
                                class="block w-full pr-10 px-4 py-2.5 border border-gray-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-300">
                            <button type="button" @click="showKey = !showKey" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 dark:hover:text-slate-250">
                                <span x-show="!showKey"><i data-lucide="eye" class="w-4 h-4"></i></span>
                                <span x-show="showKey" x-cloak><i data-lucide="eye-off" class="w-4 h-4"></i></span>
                            </button>
                        </div>
                    </div>
                    <div>
                        <label for="ai_openai_model" class="block text-[11px] font-bold text-gray-700 dark:text-slate-300 uppercase tracking-wider mb-1">{{ __('OpenAI Model') }}</label>
                        <select name="ai_openai_model"
                            id="ai_openai_model"
                            x-model="openAIModel"
                            class="block w-full px-4 py-2.5 border border-gray-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-300">
                            <option value="gpt-4o-mini">gpt-4o-mini</option>
                            <option value="gpt-4o">gpt-4o</option>
                            <option value="gpt-3.5-turbo">gpt-3.5-turbo</option>
                            <option value="custom">{{ __('Tùy chỉnh model khác...') }}</option>
                        </select>
                        <div class="mt-2" x-show="openAIModel === 'custom'" x-cloak>
                            <input type="text"
                                name="ai_openai_model_custom"
                                value="{{ $settings['ai_openai_model'] ?? '' }}"
                                placeholder="{{ __('Nhập tên model tùy chỉnh...') }}"
                                class="block w-full px-4 py-2 border border-gray-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-300">
                        </div>
                    </div>
                </div>

                <!-- DeepSeek Box -->
                <div class="p-4 bg-white dark:bg-slate-800/40 rounded-2xl border border-gray-150 dark:border-slate-800 space-y-4"
                     x-data="{ deepSeekModel: '{{ in_array($settings['ai_deepseek_model'] ?? 'deepseek-chat', ['deepseek-chat', 'deepseek-reasoner']) ? ($settings['ai_deepseek_model'] ?? 'deepseek-chat') : 'custom' }}', showKey: false }">
                    <div class="flex items-center justify-between pb-2 border-b border-gray-100 dark:border-slate-800">
                        <span class="font-bold text-xs text-gray-800 dark:text-slate-200 flex items-center gap-1.5">
                            <i data-lucide="cpu" class="w-4 h-4 text-blue-500"></i>
                            DeepSeek
                        </span>
                        <button type="button" 
                            @click="testAIConnection('deepseek')"
                            :disabled="testingAI.deepseek"
                            class="inline-flex items-center gap-1 px-2.5 py-1 bg-blue-50 hover:bg-blue-100 text-blue-600 dark:bg-blue-950/30 dark:hover:bg-blue-900/40 dark:text-blue-400 text-[10px] font-bold rounded-lg transition-all border border-blue-100 dark:border-blue-900/30 shadow-sm cursor-pointer disabled:opacity-50">
                            <span x-show="testingAI.deepseek" class="w-3 h-3 border-2 border-blue-600 border-t-transparent rounded-full animate-spin"></span>
                            <i x-show="!testingAI.deepseek" data-lucide="play" class="w-3 h-3"></i>
                            {{ __('Kiểm tra kết nối') }}
                        </button>
                    </div>
                    <div>
                        <label for="ai_deepseek_key" class="block text-[11px] font-bold text-gray-700 dark:text-slate-300 uppercase tracking-wider mb-1">{{ __('DeepSeek API Key') }}</label>
                        <div class="relative">
                            <input :type="showKey ? 'text' : 'password'"
                                name="ai_deepseek_key"
                                id="ai_deepseek_key"
                                value="{{ $settings['ai_deepseek_key'] ?? '' }}"
                                placeholder="sk-..."
                                class="block w-full pr-10 px-4 py-2.5 border border-gray-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-300">
                            <button type="button" @click="showKey = !showKey" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 dark:hover:text-slate-250">
                                <span x-show="!showKey"><i data-lucide="eye" class="w-4 h-4"></i></span>
                                <span x-show="showKey" x-cloak><i data-lucide="eye-off" class="w-4 h-4"></i></span>
                            </button>
                        </div>
                    </div>
                    <div>
                        <label for="ai_deepseek_model" class="block text-[11px] font-bold text-gray-700 dark:text-slate-300 uppercase tracking-wider mb-1">{{ __('DeepSeek Model') }}</label>
                        <select name="ai_deepseek_model"
                            id="ai_deepseek_model"
                            x-model="deepSeekModel"
                            class="block w-full px-4 py-2.5 border border-gray-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-300">
                            <option value="deepseek-chat">deepseek-chat (v3)</option>
                            <option value="deepseek-reasoner">deepseek-reasoner (r1)</option>
                            <option value="custom">{{ __('Tùy chỉnh model khác...') }}</option>
                        </select>
                        <div class="mt-2" x-show="deepSeekModel === 'custom'" x-cloak>
                            <input type="text"
                                name="ai_deepseek_model_custom"
                                value="{{ $settings['ai_deepseek_model'] ?? '' }}"
                                placeholder="{{ __('Nhập tên model tùy chỉnh...') }}"
                                class="block w-full px-4 py-2 border border-gray-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-300">
                        </div>
                    </div>
                </div>

                <!-- Anthropic Claude Box -->
                <div class="p-4 bg-white dark:bg-slate-800/40 rounded-2xl border border-gray-150 dark:border-slate-800 space-y-4"
                     x-data="{ claudeModel: '{{ in_array($settings['ai_claude_model'] ?? 'claude-3-5-sonnet-latest', ['claude-3-5-sonnet-latest', 'claude-3-5-haiku-latest', 'claude-3-opus-latest']) ? ($settings['ai_claude_model'] ?? 'claude-3-5-sonnet-latest') : 'custom' }}', showKey: false }">
                    <div class="flex items-center justify-between pb-2 border-b border-gray-100 dark:border-slate-800">
                        <span class="font-bold text-xs text-gray-800 dark:text-slate-200 flex items-center gap-1.5">
                            <i data-lucide="cpu" class="w-4 h-4 text-orange-500"></i>
                            Anthropic Claude
                        </span>
                        <button type="button" 
                            @click="testAIConnection('claude')"
                            :disabled="testingAI.claude"
                            class="inline-flex items-center gap-1 px-2.5 py-1 bg-orange-50 hover:bg-orange-100 text-orange-600 dark:bg-orange-950/30 dark:hover:bg-orange-900/40 dark:text-orange-400 text-[10px] font-bold rounded-lg transition-all border border-orange-100 dark:border-orange-900/30 shadow-sm cursor-pointer disabled:opacity-50">
                            <span x-show="testingAI.claude" class="w-3 h-3 border-2 border-orange-600 border-t-transparent rounded-full animate-spin"></span>
                            <i x-show="!testingAI.claude" data-lucide="play" class="w-3 h-3"></i>
                            {{ __('Kiểm tra kết nối') }}
                        </button>
                    </div>
                    <div>
                        <label for="ai_claude_key" class="block text-[11px] font-bold text-gray-700 dark:text-slate-300 uppercase tracking-wider mb-1">{{ __('Claude API Key') }}</label>
                        <div class="relative">
                            <input :type="showKey ? 'text' : 'password'"
                                name="ai_claude_key"
                                id="ai_claude_key"
                                value="{{ $settings['ai_claude_key'] ?? '' }}"
                                placeholder="sk-ant-..."
                                class="block w-full pr-10 px-4 py-2.5 border border-gray-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-300">
                            <button type="button" @click="showKey = !showKey" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 dark:hover:text-slate-250">
                                <span x-show="!showKey"><i data-lucide="eye" class="w-4 h-4"></i></span>
                                <span x-show="showKey" x-cloak><i data-lucide="eye-off" class="w-4 h-4"></i></span>
                            </button>
                        </div>
                    </div>
                    <div>
                        <label for="ai_claude_model" class="block text-[11px] font-bold text-gray-700 dark:text-slate-300 uppercase tracking-wider mb-1">{{ __('Claude Model') }}</label>
                        <select name="ai_claude_model"
                            id="ai_claude_model"
                            x-model="claudeModel"
                            class="block w-full px-4 py-2.5 border border-gray-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-300">
                            <option value="claude-3-5-sonnet-latest">claude-3-5-sonnet-latest</option>
                            <option value="claude-3-5-haiku-latest">claude-3-5-haiku-latest</option>
                            <option value="claude-3-opus-latest">claude-3-opus-latest</option>
                            <option value="custom">{{ __('Tùy chỉnh model khác...') }}</option>
                        </select>
                        <div class="mt-2" x-show="claudeModel === 'custom'" x-cloak>
                            <input type="text"
                                name="ai_claude_model_custom"
                                value="{{ $settings['ai_claude_model'] ?? '' }}"
                                placeholder="{{ __('Nhập tên model tùy chỉnh...') }}"
                                class="block w-full px-4 py-2 border border-gray-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-300">
                        </div>
                    </div>
                </div>

                <!-- Google Gemini Box -->
                <div class="p-4 bg-white dark:bg-slate-800/40 rounded-2xl border border-gray-150 dark:border-slate-800 space-y-4"
                     x-data="{ geminiModel: '{{ in_array($settings['ai_gemini_model'] ?? 'gemini-1.5-flash', ['gemini-1.5-flash', 'gemini-1.5-pro']) ? ($settings['ai_gemini_model'] ?? 'gemini-1.5-flash') : 'custom' }}', showKey: false }">
                    <div class="flex items-center justify-between pb-2 border-b border-gray-100 dark:border-slate-800">
                        <span class="font-bold text-xs text-gray-800 dark:text-slate-200 flex items-center gap-1.5">
                            <i data-lucide="cpu" class="w-4 h-4 text-violet-500"></i>
                            Google Gemini
                        </span>
                        <button type="button" 
                            @click="testAIConnection('gemini')"
                            :disabled="testingAI.gemini"
                            class="inline-flex items-center gap-1 px-2.5 py-1 bg-violet-50 hover:bg-violet-100 text-violet-600 dark:bg-violet-950/30 dark:hover:bg-violet-900/40 dark:text-violet-400 text-[10px] font-bold rounded-lg transition-all border border-violet-100 dark:border-violet-900/30 shadow-sm cursor-pointer disabled:opacity-50">
                            <span x-show="testingAI.gemini" class="w-3 h-3 border-2 border-violet-600 border-t-transparent rounded-full animate-spin"></span>
                            <i x-show="!testingAI.gemini" data-lucide="play" class="w-3 h-3"></i>
                            {{ __('Kiểm tra kết nối') }}
                        </button>
                    </div>
                    <div>
                        <label for="ai_gemini_key" class="block text-[11px] font-bold text-gray-700 dark:text-slate-300 uppercase tracking-wider mb-1">{{ __('Gemini API Key') }}</label>
                        <div class="relative">
                            <input :type="showKey ? 'text' : 'password'"
                                name="ai_gemini_key"
                                id="ai_gemini_key"
                                value="{{ $settings['ai_gemini_key'] ?? '' }}"
                                placeholder="AIzaSy..."
                                class="block w-full pr-10 px-4 py-2.5 border border-gray-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-300">
                            <button type="button" @click="showKey = !showKey" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 dark:hover:text-slate-250">
                                <span x-show="!showKey"><i data-lucide="eye" class="w-4 h-4"></i></span>
                                <span x-show="showKey" x-cloak><i data-lucide="eye-off" class="w-4 h-4"></i></span>
                            </button>
                        </div>
                    </div>
                    <div>
                        <label for="ai_gemini_model" class="block text-[11px] font-bold text-gray-700 dark:text-slate-300 uppercase tracking-wider mb-1">{{ __('Gemini Model') }}</label>
                        <select name="ai_gemini_model"
                            id="ai_gemini_model"
                            x-model="geminiModel"
                            class="block w-full px-4 py-2.5 border border-gray-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-300">
                            <option value="gemini-1.5-flash">gemini-1.5-flash</option>
                            <option value="gemini-1.5-pro">gemini-1.5-pro</option>
                            <option value="custom">{{ __('Tùy chỉnh model khác...') }}</option>
                        </select>
                        <div class="mt-2" x-show="geminiModel === 'custom'" x-cloak>
                            <input type="text"
                                name="ai_gemini_model_custom"
                                value="{{ $settings['ai_gemini_model'] ?? '' }}"
                                placeholder="{{ __('Nhập tên model tùy chỉnh...') }}"
                                class="block w-full px-4 py-2 border border-gray-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-300">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- 
        Ẩn phần Cấu hình Bản quyền & Cập nhật khi hệ thống chạy ở chế độ Demo (APP_DEMO = true).
        Lý do: Tránh để khách hàng hoặc người dùng thử nghiệm nhìn thấy mã bản quyền (License Key) 
        hoặc thực hiện thao tác cập nhật hệ thống ảnh hưởng đến dữ liệu Demo.
    --}}
    @if (!config('app.demo'))
        <!-- Cấu hình Bản quyền & Cập nhật -->
        <div class="bg-gray-50/50 dark:bg-slate-900/50 rounded-2xl p-4 sm:p-6 border border-gray-150 dark:border-slate-800 space-y-4">
            <div class="flex items-center justify-between mb-4 pb-2 border-b border-gray-200 dark:border-slate-800">
                <h3 class="text-xs font-bold text-gray-700 dark:text-slate-200 uppercase tracking-wider flex items-center gap-2">
                    <i data-lucide="key" class="w-4 h-4 text-shopee"></i>
                    {{ __('Cấu hình Bản quyền & Cập nhật') }}
                </h3>
                <button type="button" @click="guideType = 'license'; showGuideModal = true" class="inline-flex items-center gap-1 px-3 py-1 bg-shopee/10 text-shopee hover:bg-shopee hover:text-white text-[10px] font-bold rounded-xl transition-all shadow-sm">
                    <i data-lucide="help-circle" class="w-3.5 h-3.5"></i>
                    {{ __('Hướng dẫn cấu hình') }}
                </button>
            </div>

            <div class="grid grid-cols-1 gap-6">
                <div>
                    <label for="license_key" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">{{ __('Mã bản quyền (License Key)') }}</label>
                    <input type="password"
                        name="license_key"
                        id="license_key"
                        value="{{ $settings['license_key'] ?? '' }}"
                        placeholder="Nhập mã bản quyền của bạn..."
                        class="block w-full px-4 py-2.5 border border-gray-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-300">
                    <span class="text-[10px] text-gray-400 mt-1 block">Nhập Mã bản quyền nhận được từ client.cmsnt.co để được hỗ trợ kiểm tra và cập nhật phiên bản vá lỗi tự động từ máy chủ trung gian.</span>
                </div>
            </div>
        </div>
    @endif
</div>
