{{--
    Partial View: Cấu hình Open API
    Vai trò: Bật/tắt hệ thống Open API và từng nhóm endpoint, kèm tài liệu tích hợp mở rộng (accordion) cho từng loại API.
    Mỗi loại API hiển thị trên một dòng, có nút mở rộng để xem tài liệu chi tiết (endpoint, tham số, ví dụ request/response).
--}}
@php
    // Địa chỉ gốc của Open API dùng để dựng ví dụ tài liệu
    $apiBase = rtrim(url('/api/v1/openapi'), '/');
@endphp

<div x-show="tab === 'open_api'" class="space-y-6" x-transition x-cloak>

    <!-- Khối cấu hình tổng quan hệ thống Open API -->
    <div class="bg-gray-50/50 dark:bg-slate-900/50 rounded-2xl p-4 sm:p-6 border border-gray-150 dark:border-slate-800 space-y-6 w-full">
        <h3 class="text-xs font-bold text-gray-700 dark:text-slate-300 uppercase tracking-wider pb-2 border-b border-gray-200/65 dark:border-slate-800 flex items-center gap-1.5">
            <i data-lucide="key-round" class="w-4 h-4 text-shopee"></i>
            {{ __('Cấu hình hệ thống Open API') }}
        </h3>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Công tắc tổng của toàn hệ thống Open API -->
            <div>
                <label for="openapi_status" class="block text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider mb-1">{{ __('Trạng thái hệ thống Open API') }}</label>
                <select name="openapi_status" id="openapi_status"
                    class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-800 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-300">
                    <option value="1" {{ ($settings['openapi_status'] ?? '0') === '1' ? 'selected' : '' }}>{{ __('Bật hoạt động (Cho phép App/Frontend gọi API)') }}</option>
                    <option value="0" {{ ($settings['openapi_status'] ?? '0') === '0' ? 'selected' : '' }}>{{ __('Tắt hoạt động (Vô hiệu hóa toàn bộ API)') }}</option>
                </select>
                <span class="text-[9px] text-gray-400 mt-1 block">{{ __('Công tắc tổng. Khi tắt, mọi endpoint bên dưới đều ngừng phục vụ (trả về mã 503).') }}</span>
            </div>

            <!-- Thời hạn hiệu lực của token đăng nhập -->
            <div>
                <label for="openapi_token_ttl_days" class="block text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider mb-1">{{ __('Thời hạn token đăng nhập (ngày)') }}</label>
                <input type="number" min="0" name="openapi_token_ttl_days" id="openapi_token_ttl_days"
                    value="{{ $settings['openapi_token_ttl_days'] ?? '0' }}"
                    class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-800 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-300 font-mono">
                <span class="text-[9px] text-gray-400 mt-1 block">{{ __('Số ngày token còn hiệu lực sau khi đăng nhập. Nhập 0 để token không hết hạn (vĩnh viễn).') }}</span>
            </div>
        </div>

        <!-- Ghi chú bảo mật cơ chế xác thực -->
        <div class="bg-shopee/5 dark:bg-shopee/10 rounded-xl p-4 border border-shopee/20 text-xs leading-relaxed text-gray-600 dark:text-slate-300 space-y-2">
            <p class="font-semibold text-gray-700 dark:text-slate-200 flex items-center gap-1.5">
                <i data-lucide="shield-check" class="w-4 h-4 text-shopee"></i>
                {{ __('Cơ chế bảo mật xác thực (Bearer Token)') }}
            </p>
            <ul class="list-disc pl-5 space-y-1 text-[11px]">
                <li>{{ __('Ứng dụng gọi API Đăng ký / Đăng nhập để nhận về một access token duy nhất cho thành viên.') }}</li>
                <li>{{ __('Mọi endpoint riêng tư (số dư, đơn hàng, rút tiền, lấy link) phải gửi kèm header:') }} <code class="font-mono text-shopee font-semibold">Authorization: Bearer &lt;token&gt;</code></li>
                <li>{{ __('Token được lưu dạng băm SHA-256 trong máy chủ, hỗ trợ hết hạn và thu hồi khi đăng xuất.') }}</li>
                <li>{{ __('Địa chỉ gốc (Base URL):') }} <code class="font-mono text-gray-700 dark:text-slate-200 select-all">{{ $apiBase }}</code></li>
            </ul>
        </div>
    </div>

    <!-- Khối cấu hình App Mobile: Push Notification (FCM) & Kiểm tra phiên bản -->
    <div class="bg-gray-50/50 dark:bg-slate-900/50 rounded-2xl p-4 sm:p-6 border border-gray-150 dark:border-slate-800 space-y-6 w-full">
        <h3 class="text-xs font-bold text-gray-700 dark:text-slate-300 uppercase tracking-wider pb-2 border-b border-gray-200/65 dark:border-slate-800 flex items-center gap-1.5">
            <i data-lucide="smartphone" class="w-4 h-4 text-shopee"></i>
            {{ __('Cấu hình App Mobile') }}
        </h3>

        <!-- Firebase Service Account cho Push Notification -->
        <div>
            <label for="fcm_service_account" class="block text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider mb-1">{{ __('Firebase Service Account (JSON) — Push Notification') }}</label>
            <textarea name="fcm_service_account" id="fcm_service_account" rows="4"
                placeholder='{"type":"service_account","project_id":"...","private_key":"...","client_email":"..."}'
                class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-800 rounded-xl text-[11px] focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-300 font-mono">{{ $settings['fcm_service_account'] ?? '' }}</textarea>
            <span class="text-[9px] text-gray-400 mt-1 block">{{ __('Dán nội dung file JSON Service Account từ Firebase Console → Project Settings → Service accounts. Bỏ trống để tắt gửi thông báo đẩy. Khi có bản ghi thông báo mới, hệ thống tự gửi push tới thiết bị đã đăng ký (FCM HTTP v1).') }}</span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label for="mobile_latest_version" class="block text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider mb-1">{{ __('Phiên bản App mới nhất') }}</label>
                <input type="text" name="mobile_latest_version" id="mobile_latest_version" value="{{ $settings['mobile_latest_version'] ?? '' }}" placeholder="1.0.0"
                    class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-800 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-300 font-mono">
            </div>
            <div>
                <label for="mobile_min_version" class="block text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider mb-1">{{ __('Phiên bản tối thiểu được hỗ trợ') }}</label>
                <input type="text" name="mobile_min_version" id="mobile_min_version" value="{{ $settings['mobile_min_version'] ?? '' }}" placeholder="1.0.0"
                    class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-800 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-300 font-mono">
            </div>
            <div>
                <label for="mobile_android_store_url" class="block text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider mb-1">{{ __('Link cửa hàng Android (Google Play)') }}</label>
                <input type="text" name="mobile_android_store_url" id="mobile_android_store_url" value="{{ $settings['mobile_android_store_url'] ?? '' }}" placeholder="https://play.google.com/store/apps/details?id=..."
                    class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-800 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-300">
            </div>
            <div>
                <label for="mobile_ios_store_url" class="block text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider mb-1">{{ __('Link cửa hàng iOS (App Store)') }}</label>
                <input type="text" name="mobile_ios_store_url" id="mobile_ios_store_url" value="{{ $settings['mobile_ios_store_url'] ?? '' }}" placeholder="https://apps.apple.com/app/..."
                    class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-800 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-300">
            </div>
        </div>

        <div>
            <label for="mobile_update_message" class="block text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider mb-1">{{ __('Thông điệp yêu cầu cập nhật') }}</label>
            <input type="text" name="mobile_update_message" id="mobile_update_message" value="{{ $settings['mobile_update_message'] ?? '' }}" placeholder="{{ __('Đã có phiên bản mới với nhiều cải tiến. Vui lòng cập nhật để tiếp tục sử dụng.') }}"
                class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-800 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-300">
        </div>

        <!-- Bắt buộc cập nhật -->
        <div x-data="{ force: {{ ($settings['mobile_force_update'] ?? '0') === '1' ? 'true' : 'false' }} }" class="flex items-center justify-between gap-3 bg-white dark:bg-slate-800/40 border border-gray-200 dark:border-slate-800 rounded-xl p-4">
            <div class="min-w-0">
                <span class="block text-sm font-bold text-gray-800 dark:text-slate-200">{{ __('Bắt buộc cập nhật') }}</span>
                <span class="text-[10px] text-gray-400">{{ __('Khi bật, App có phiên bản thấp hơn mức tối thiểu sẽ bị chặn sử dụng cho tới khi cập nhật.') }}</span>
            </div>
            <input type="hidden" name="mobile_force_update" :value="force ? '1' : '0'">
            <button type="button" @click="force = !force" :class="force ? 'bg-shopee' : 'bg-gray-300 dark:bg-slate-600'"
                class="relative shrink-0 inline-flex h-6 w-11 items-center rounded-full transition-colors" title="{{ __('Bật/Tắt bắt buộc cập nhật') }}">
                <span :class="force ? 'translate-x-5.5' : 'translate-x-0.5'" class="inline-block h-5 w-5 transform rounded-full bg-white shadow transition-transform"></span>
            </button>
        </div>
    </div>

    <!-- Danh sách các loại API dạng accordion (mỗi loại 1 dòng, mở rộng xem tài liệu) -->
    <div class="bg-gray-50/50 dark:bg-slate-900/50 rounded-2xl p-4 sm:p-6 border border-gray-150 dark:border-slate-800 space-y-3 w-full">
        <h3 class="text-xs font-bold text-gray-700 dark:text-slate-300 uppercase tracking-wider pb-2 border-b border-gray-200/65 dark:border-slate-800 flex items-center gap-1.5">
            <i data-lucide="book-open" class="w-4 h-4 text-shopee"></i>
            {{ __('Danh sách API & Tài liệu tích hợp') }}
        </h3>

        {{-- =================== 0. API Cấu hình khởi động ứng dụng (public) =================== --}}
        <div x-data="{ open: false, enabled: {{ ($settings['openapi_config_status'] ?? '1') === '1' ? 'true' : 'false' }} }"
            class="bg-white dark:bg-slate-800/40 border border-gray-200 dark:border-slate-800 rounded-2xl overflow-hidden">
            <div class="flex items-center gap-3 p-4">
                <button type="button" @click="open = !open" class="flex items-center gap-3 flex-1 text-left min-w-0">
                    <span class="shrink-0 w-9 h-9 rounded-xl bg-shopee/10 text-shopee flex items-center justify-center">
                        <i data-lucide="settings-2" class="w-4.5 h-4.5"></i>
                    </span>
                    <span class="min-w-0">
                        <span class="block text-sm font-bold text-gray-800 dark:text-slate-200 truncate">{{ __('Cấu hình khởi động ứng dụng') }}</span>
                        <span class="flex items-center gap-1.5 mt-0.5 flex-wrap">
                            <span class="text-[9px] font-bold px-1.5 py-0.5 rounded bg-sky-100 dark:bg-sky-500/15 text-sky-700 dark:text-sky-400">GET</span>
                            <span class="text-[9px] font-bold px-1.5 py-0.5 rounded bg-gray-100 dark:bg-slate-700 text-gray-500 dark:text-slate-300">{{ __('Công khai') }}</span>
                            <code class="text-[10px] text-gray-500 dark:text-slate-400 font-mono">/config</code>
                        </span>
                    </span>
                </button>
                <input type="hidden" name="openapi_config_status" :value="enabled ? '1' : '0'">
                <button type="button" @click="enabled = !enabled" :class="enabled ? 'bg-shopee' : 'bg-gray-300 dark:bg-slate-600'"
                    class="relative shrink-0 inline-flex h-6 w-11 items-center rounded-full transition-colors" title="{{ __('Bật/Tắt API này') }}">
                    <span :class="enabled ? 'translate-x-5.5' : 'translate-x-0.5'" class="inline-block h-5 w-5 transform rounded-full bg-white shadow transition-transform"></span>
                </button>
                <button type="button" @click="open = !open" class="shrink-0 text-gray-400 hover:text-shopee transition-colors">
                    <i data-lucide="chevron-down" class="w-5 h-5 transition-transform" :class="open ? 'rotate-180' : ''"></i>
                </button>
            </div>
            <div x-show="open" x-collapse x-cloak class="px-4 pb-4 border-t border-gray-100 dark:border-slate-800 pt-4 space-y-3 text-xs text-gray-600 dark:text-slate-300 leading-relaxed">
                <p>{{ __('Endpoint công khai (không cần token) để App gọi một lần khi khởi động: lấy tên/logo, màu thương hiệu, tỉ lệ hoàn tiền, trạng thái sàn, cấu hình rút tiền, banner và cờ bật/tắt tính năng.') }}</p>
                <div class="bg-gray-900 dark:bg-slate-950 rounded-xl p-3.5 font-mono text-[10.5px] text-gray-100 overflow-x-auto shadow-inner select-all leading-relaxed">curl {{ $apiBase }}/config</div>
                <p class="font-semibold text-gray-700 dark:text-slate-200">{{ __('Phản hồi mẫu:') }}</p>
                <div class="bg-gray-100 dark:bg-slate-950/60 rounded-xl p-3.5 font-mono text-[10.5px] text-gray-700 dark:text-slate-300 overflow-x-auto leading-relaxed">{<br>&nbsp;&nbsp;"success": true,<br>&nbsp;&nbsp;"data": { "site": { "name": "..." }, "theme": { "color": "#ee4d2d" }, "cashback": { "shopee_rate": 5 }, "withdraw": { "min_amount": 50000 }, "features": {...} }<br>}</div>
            </div>
        </div>

        {{-- =================== 1. API Tạo tài khoản & Đăng nhập =================== --}}
        <div x-data="{ open: false, enabled: {{ ($settings['openapi_auth_status'] ?? '1') === '1' ? 'true' : 'false' }} }"
            class="bg-white dark:bg-slate-800/40 border border-gray-200 dark:border-slate-800 rounded-2xl overflow-hidden">
            <div class="flex items-center gap-3 p-4">
                <button type="button" @click="open = !open" class="flex items-center gap-3 flex-1 text-left min-w-0">
                    <span class="shrink-0 w-9 h-9 rounded-xl bg-shopee/10 text-shopee flex items-center justify-center">
                        <i data-lucide="user-plus" class="w-4.5 h-4.5"></i>
                    </span>
                    <span class="min-w-0">
                        <span class="block text-sm font-bold text-gray-800 dark:text-slate-200 truncate">{{ __('Tạo tài khoản & Đăng nhập') }}</span>
                        <span class="flex items-center gap-1.5 mt-0.5 flex-wrap">
                            <span class="text-[9px] font-bold px-1.5 py-0.5 rounded bg-emerald-100 dark:bg-emerald-500/15 text-emerald-700 dark:text-emerald-400">POST</span>
                            <code class="text-[10px] text-gray-500 dark:text-slate-400 font-mono">/auth/register · /auth/login</code>
                        </span>
                    </span>
                </button>
                <input type="hidden" name="openapi_auth_status" :value="enabled ? '1' : '0'">
                <button type="button" @click="enabled = !enabled" :class="enabled ? 'bg-shopee' : 'bg-gray-300 dark:bg-slate-600'"
                    class="relative shrink-0 inline-flex h-6 w-11 items-center rounded-full transition-colors" title="{{ __('Bật/Tắt API này') }}">
                    <span :class="enabled ? 'translate-x-5.5' : 'translate-x-0.5'" class="inline-block h-5 w-5 transform rounded-full bg-white shadow transition-transform"></span>
                </button>
                <button type="button" @click="open = !open" class="shrink-0 text-gray-400 hover:text-shopee transition-colors">
                    <i data-lucide="chevron-down" class="w-5 h-5 transition-transform" :class="open ? 'rotate-180' : ''"></i>
                </button>
            </div>
            <div x-show="open" x-collapse x-cloak class="px-4 pb-4 border-t border-gray-100 dark:border-slate-800 pt-4 space-y-3 text-xs text-gray-600 dark:text-slate-300 leading-relaxed">
                <p>{{ __('Đăng ký tài khoản mới hoặc đăng nhập để nhận access token. Đây là các endpoint công khai (không cần token), được giới hạn tần suất chống spam/dò mật khẩu.') }}</p>
                <p class="font-semibold text-gray-700 dark:text-slate-200">{{ __('Tham số (JSON body):') }}</p>
                <ul class="list-disc pl-5 space-y-0.5 text-[11px]">
                    <li><code class="font-mono text-shopee">email</code>, <code class="font-mono text-shopee">password</code> — {{ __('bắt buộc.') }}</li>
                    <li><code class="font-mono">password_confirmation</code>, <code class="font-mono">name</code>, <code class="font-mono">phone</code> — {{ __('khi đăng ký (name, phone tùy chọn).') }}</li>
                    <li><code class="font-mono text-shopee">referral_code</code> — {{ __('tùy chọn, mã giới thiệu của người tuyến trên (App tự truyền vào từ link mời). Bị bỏ qua nếu tắt tiếp thị liên kết hoặc trùng IP người giới thiệu.') }}</li>
                    <li><code class="font-mono">device_name</code> — {{ __('tùy chọn, tên thiết bị để quản lý phiên.') }}</li>
                </ul>
                <div class="bg-gray-900 dark:bg-slate-950 rounded-xl p-3.5 font-mono text-[10.5px] text-gray-100 overflow-x-auto shadow-inner select-all leading-relaxed">curl -X POST {{ $apiBase }}/auth/register \<br>&nbsp;&nbsp;-H "Content-Type: application/json" \<br>&nbsp;&nbsp;-d '{"email":"user@gmail.com","password":"secret123",<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"password_confirmation":"secret123","referral_code":"REFABC123"}'</div>
                <p class="font-semibold text-gray-700 dark:text-slate-200">{{ __('Phản hồi mẫu:') }}</p>
                <div class="bg-gray-100 dark:bg-slate-950/60 rounded-xl p-3.5 font-mono text-[10.5px] text-gray-700 dark:text-slate-300 overflow-x-auto leading-relaxed">{<br>&nbsp;&nbsp;"success": true,<br>&nbsp;&nbsp;"message": "Đăng nhập thành công!",<br>&nbsp;&nbsp;"data": { "token": "xxx...", "token_type": "Bearer", "user": { "id": 1, "balance": 150000 } }<br>}</div>

                <div class="bg-amber-50 dark:bg-amber-500/10 border border-amber-200 dark:border-amber-500/25 rounded-xl p-3 space-y-2">
                    <p class="font-semibold text-amber-800 dark:text-amber-300 flex items-center gap-1.5">
                        <i data-lucide="shield-alert" class="w-3.5 h-3.5"></i>
                        {{ __('Tài khoản bật bảo mật 2 lớp (2FA)') }}
                    </p>
                    <p class="text-[11px] text-amber-800/90 dark:text-amber-200/90">{{ __('Nếu bật 2FA, /auth/login KHÔNG trả token ngay mà trả về challenge_token và danh sách methods (google2fa / email_otp). Gọi tiếp bước 2 để lấy token:') }}</p>
                    <ul class="list-disc pl-5 space-y-0.5 text-[11px] text-amber-800/90 dark:text-amber-200/90">
                        <li><code class="font-mono">POST /auth/login/2fa</code> — {{ __('gửi challenge_token + google2fa_code và/hoặc email_otp_code.') }}</li>
                        <li><code class="font-mono">POST /auth/login/2fa/resend</code> — {{ __('gửi lại mã OTP email (nếu dùng).') }}</li>
                    </ul>
                    <div class="bg-gray-900 dark:bg-slate-950 rounded-lg p-3 font-mono text-[10.5px] text-gray-100 overflow-x-auto select-all leading-relaxed">curl -X POST {{ $apiBase }}/auth/login/2fa \<br>&nbsp;&nbsp;-H "Content-Type: application/json" \<br>&nbsp;&nbsp;-d '{"challenge_token":"...","google2fa_code":"123456"}'</div>
                </div>

                <p class="font-semibold text-gray-700 dark:text-slate-200 pt-1">{{ __('Các endpoint hỗ trợ khác:') }}</p>
                <ul class="list-disc pl-5 space-y-0.5 text-[11px]">
                    <li><code class="font-mono">POST /auth/verify-email</code> — {{ __('xác minh email bằng OTP (khi hệ thống bắt buộc kích hoạt), trả về token.') }}</li>
                    <li><code class="font-mono">POST /auth/verify-email/resend</code> — {{ __('gửi lại mã kích hoạt.') }}</li>
                    <li><code class="font-mono">POST /auth/forgot-password</code> — {{ __('gửi email đặt lại mật khẩu (body: email).') }}</li>
                    <li><code class="font-mono">POST /auth/reset-password</code> — {{ __('đặt mật khẩu mới (body: token, email, password, password_confirmation).') }}</li>
                    <li><code class="font-mono">POST /auth/logout</code> — {{ __('đăng xuất, thu hồi token của phiên hiện tại (cần gửi kèm Authorization: Bearer).') }}</li>
                </ul>
                <p class="text-[10px] text-gray-400">{{ __('Lưu ý: nếu bật "bắt buộc xác minh email", API đăng ký sẽ trả email_verification_required = true thay vì token — App cần gọi tiếp /auth/verify-email.') }}</p>
            </div>
        </div>

        {{-- =================== 2. API Lấy link hoàn tiền =================== --}}
        <div x-data="{ open: false, enabled: {{ ($settings['openapi_cashback_link_status'] ?? '1') === '1' ? 'true' : 'false' }} }"
            class="bg-white dark:bg-slate-800/40 border border-gray-200 dark:border-slate-800 rounded-2xl overflow-hidden">
            <div class="flex items-center gap-3 p-4">
                <button type="button" @click="open = !open" class="flex items-center gap-3 flex-1 text-left min-w-0">
                    <span class="shrink-0 w-9 h-9 rounded-xl bg-shopee/10 text-shopee flex items-center justify-center">
                        <i data-lucide="link" class="w-4.5 h-4.5"></i>
                    </span>
                    <span class="min-w-0">
                        <span class="block text-sm font-bold text-gray-800 dark:text-slate-200 truncate">{{ __('Lấy link hoàn tiền') }}</span>
                        <span class="flex items-center gap-1.5 mt-0.5 flex-wrap">
                            <span class="text-[9px] font-bold px-1.5 py-0.5 rounded bg-emerald-100 dark:bg-emerald-500/15 text-emerald-700 dark:text-emerald-400">POST</span>
                            <code class="text-[10px] text-gray-500 dark:text-slate-400 font-mono">/cashback/link</code>
                        </span>
                    </span>
                </button>
                <input type="hidden" name="openapi_cashback_link_status" :value="enabled ? '1' : '0'">
                <button type="button" @click="enabled = !enabled" :class="enabled ? 'bg-shopee' : 'bg-gray-300 dark:bg-slate-600'"
                    class="relative shrink-0 inline-flex h-6 w-11 items-center rounded-full transition-colors" title="{{ __('Bật/Tắt API này') }}">
                    <span :class="enabled ? 'translate-x-5.5' : 'translate-x-0.5'" class="inline-block h-5 w-5 transform rounded-full bg-white shadow transition-transform"></span>
                </button>
                <button type="button" @click="open = !open" class="shrink-0 text-gray-400 hover:text-shopee transition-colors">
                    <i data-lucide="chevron-down" class="w-5 h-5 transition-transform" :class="open ? 'rotate-180' : ''"></i>
                </button>
            </div>
            <div x-show="open" x-collapse x-cloak class="px-4 pb-4 border-t border-gray-100 dark:border-slate-800 pt-4 space-y-3 text-xs text-gray-600 dark:text-slate-300 leading-relaxed">
                <p>{{ __('Nhận link sản phẩm Shopee/TikTok Shop/Lazada và trả về link affiliate rút gọn kèm thông tin hoàn tiền. Yêu cầu xác thực token và bị giới hạn tần suất tạo link.') }}</p>
                <p class="font-semibold text-gray-700 dark:text-slate-200">{{ __('Header & Tham số:') }}</p>
                <ul class="list-disc pl-5 space-y-0.5 text-[11px]">
                    <li><code class="font-mono text-shopee">Authorization: Bearer &lt;token&gt;</code> — {{ __('bắt buộc.') }}</li>
                    <li><code class="font-mono text-shopee">url</code> — {{ __('link sản phẩm Shopee, TikTok Shop hoặc Lazada (bắt buộc).') }}</li>
                </ul>
                <div class="bg-gray-900 dark:bg-slate-950 rounded-xl p-3.5 font-mono text-[10.5px] text-gray-100 overflow-x-auto shadow-inner select-all leading-relaxed">curl -X POST {{ $apiBase }}/cashback/link \<br>&nbsp;&nbsp;-H "Authorization: Bearer &lt;token&gt;" \<br>&nbsp;&nbsp;-H "Content-Type: application/json" \<br>&nbsp;&nbsp;-d '{"url":"https://shopee.vn/product/123/456"}'</div>
                <p class="font-semibold text-gray-700 dark:text-slate-200">{{ __('Phản hồi mẫu:') }}</p>
                <div class="bg-gray-100 dark:bg-slate-950/60 rounded-xl p-3.5 font-mono text-[10.5px] text-gray-700 dark:text-slate-300 overflow-x-auto leading-relaxed">{<br>&nbsp;&nbsp;"success": true,<br>&nbsp;&nbsp;"data": { "trans_id": "...", "affiliate_url": "https://.../abcd1234", "cashback_amount": 12000 }<br>}</div>
            </div>
        </div>

        {{-- =================== 3. API Thông tin tài khoản & Số dư =================== --}}
        <div x-data="{ open: false, enabled: {{ ($settings['openapi_profile_status'] ?? '1') === '1' ? 'true' : 'false' }} }"
            class="bg-white dark:bg-slate-800/40 border border-gray-200 dark:border-slate-800 rounded-2xl overflow-hidden">
            <div class="flex items-center gap-3 p-4">
                <button type="button" @click="open = !open" class="flex items-center gap-3 flex-1 text-left min-w-0">
                    <span class="shrink-0 w-9 h-9 rounded-xl bg-shopee/10 text-shopee flex items-center justify-center">
                        <i data-lucide="wallet" class="w-4.5 h-4.5"></i>
                    </span>
                    <span class="min-w-0">
                        <span class="block text-sm font-bold text-gray-800 dark:text-slate-200 truncate">{{ __('Thông tin tài khoản & Số dư') }}</span>
                        <span class="flex items-center gap-1.5 mt-0.5 flex-wrap">
                            <span class="text-[9px] font-bold px-1.5 py-0.5 rounded bg-sky-100 dark:bg-sky-500/15 text-sky-700 dark:text-sky-400">GET</span>
                            <code class="text-[10px] text-gray-500 dark:text-slate-400 font-mono">/account</code>
                        </span>
                    </span>
                </button>
                <input type="hidden" name="openapi_profile_status" :value="enabled ? '1' : '0'">
                <button type="button" @click="enabled = !enabled" :class="enabled ? 'bg-shopee' : 'bg-gray-300 dark:bg-slate-600'"
                    class="relative shrink-0 inline-flex h-6 w-11 items-center rounded-full transition-colors" title="{{ __('Bật/Tắt API này') }}">
                    <span :class="enabled ? 'translate-x-5.5' : 'translate-x-0.5'" class="inline-block h-5 w-5 transform rounded-full bg-white shadow transition-transform"></span>
                </button>
                <button type="button" @click="open = !open" class="shrink-0 text-gray-400 hover:text-shopee transition-colors">
                    <i data-lucide="chevron-down" class="w-5 h-5 transition-transform" :class="open ? 'rotate-180' : ''"></i>
                </button>
            </div>
            <div x-show="open" x-collapse x-cloak class="px-4 pb-4 border-t border-gray-100 dark:border-slate-800 pt-4 space-y-3 text-xs text-gray-600 dark:text-slate-300 leading-relaxed">
                <p>{{ __('Lấy hồ sơ thành viên, số dư ví và các thống kê tổng hợp (số đơn, số người giới thiệu). Yêu cầu xác thực token.') }}</p>
                <p class="font-semibold text-gray-700 dark:text-slate-200">{{ __('Header:') }} <code class="font-mono text-shopee">Authorization: Bearer &lt;token&gt;</code></p>
                <div class="bg-gray-900 dark:bg-slate-950 rounded-xl p-3.5 font-mono text-[10.5px] text-gray-100 overflow-x-auto shadow-inner select-all leading-relaxed">curl {{ $apiBase }}/account \<br>&nbsp;&nbsp;-H "Authorization: Bearer &lt;token&gt;"</div>
                <p class="font-semibold text-gray-700 dark:text-slate-200">{{ __('Phản hồi mẫu:') }}</p>
                <div class="bg-gray-100 dark:bg-slate-950/60 rounded-xl p-3.5 font-mono text-[10.5px] text-gray-700 dark:text-slate-300 overflow-x-auto leading-relaxed">{<br>&nbsp;&nbsp;"success": true,<br>&nbsp;&nbsp;"data": { "id": 1, "email": "...", "wallet": { "balance": 150000, "currency": "VND" }, "stats": { "orders_approved": 5 } }<br>}</div>

                <p class="font-semibold text-gray-700 dark:text-slate-200 pt-1">{{ __('Quản lý tài khoản (cần token):') }}</p>
                <ul class="list-disc pl-5 space-y-0.5 text-[11px]">
                    <li><code class="font-mono">POST /account/profile</code> — {{ __('cập nhật họ tên, số điện thoại.') }}</li>
                    <li><code class="font-mono">POST /account/password</code> — {{ __('đổi mật khẩu (current_password, password, password_confirmation); thu hồi token thiết bị khác.') }}</li>
                    <li><code class="font-mono">POST /account/delete</code> — {{ __('tự xóa tài khoản (yêu cầu bắt buộc của App Store/Google Play). Cần bật "Cho phép tự xóa tài khoản" (hệ thống tự lưu vết số dư còn lại vào nhật ký hoạt động).') }}</li>
                </ul>
            </div>
        </div>

        {{-- =================== 4. API Rút tiền =================== --}}
        <div x-data="{ open: false, enabled: {{ ($settings['openapi_withdraw_status'] ?? '1') === '1' ? 'true' : 'false' }} }"
            class="bg-white dark:bg-slate-800/40 border border-gray-200 dark:border-slate-800 rounded-2xl overflow-hidden">
            <div class="flex items-center gap-3 p-4">
                <button type="button" @click="open = !open" class="flex items-center gap-3 flex-1 text-left min-w-0">
                    <span class="shrink-0 w-9 h-9 rounded-xl bg-shopee/10 text-shopee flex items-center justify-center">
                        <i data-lucide="banknote-arrow-down" class="w-4.5 h-4.5"></i>
                    </span>
                    <span class="min-w-0">
                        <span class="block text-sm font-bold text-gray-800 dark:text-slate-200 truncate">{{ __('Rút tiền') }}</span>
                        <span class="flex items-center gap-1.5 mt-0.5 flex-wrap">
                            <span class="text-[9px] font-bold px-1.5 py-0.5 rounded bg-emerald-100 dark:bg-emerald-500/15 text-emerald-700 dark:text-emerald-400">POST</span>
                            <span class="text-[9px] font-bold px-1.5 py-0.5 rounded bg-sky-100 dark:bg-sky-500/15 text-sky-700 dark:text-sky-400">GET</span>
                            <code class="text-[10px] text-gray-500 dark:text-slate-400 font-mono">/withdrawals</code>
                        </span>
                    </span>
                </button>
                <input type="hidden" name="openapi_withdraw_status" :value="enabled ? '1' : '0'">
                <button type="button" @click="enabled = !enabled" :class="enabled ? 'bg-shopee' : 'bg-gray-300 dark:bg-slate-600'"
                    class="relative shrink-0 inline-flex h-6 w-11 items-center rounded-full transition-colors" title="{{ __('Bật/Tắt API này') }}">
                    <span :class="enabled ? 'translate-x-5.5' : 'translate-x-0.5'" class="inline-block h-5 w-5 transform rounded-full bg-white shadow transition-transform"></span>
                </button>
                <button type="button" @click="open = !open" class="shrink-0 text-gray-400 hover:text-shopee transition-colors">
                    <i data-lucide="chevron-down" class="w-5 h-5 transition-transform" :class="open ? 'rotate-180' : ''"></i>
                </button>
            </div>
            <div x-show="open" x-collapse x-cloak class="px-4 pb-4 border-t border-gray-100 dark:border-slate-800 pt-4 space-y-3 text-xs text-gray-600 dark:text-slate-300 leading-relaxed">
                <p>{{ __('Tạo yêu cầu rút tiền, xem lịch sử rút và gửi mã OTP (nếu hệ thống bật). Mọi thao tác trừ số dư đều chạy trong giao dịch khóa dòng chống double-spending.') }}</p>
                <p class="font-semibold text-gray-700 dark:text-slate-200">{{ __('Các endpoint:') }}</p>
                <ul class="list-disc pl-5 space-y-0.5 text-[11px]">
                    <li><code class="font-mono"><span class="text-sky-600 dark:text-sky-400">GET</span> /withdrawals</code> — {{ __('lịch sử rút tiền (phân trang).') }}</li>
                    <li><code class="font-mono"><span class="text-emerald-600 dark:text-emerald-400">POST</span> /withdrawals</code> — {{ __('tạo lệnh rút mới.') }}</li>
                    <li><code class="font-mono"><span class="text-emerald-600 dark:text-emerald-400">POST</span> /withdrawals/otp</code> — {{ __('gửi mã OTP về email khi cần.') }}</li>
                </ul>
                <p class="font-semibold text-gray-700 dark:text-slate-200">{{ __('Tham số tạo lệnh (JSON body):') }}</p>
                <ul class="list-disc pl-5 space-y-0.5 text-[11px]">
                    <li><code class="font-mono text-shopee">amount</code>, <code class="font-mono text-shopee">payment_method</code> (bank/wallet/momo), <code class="font-mono text-shopee">account_number</code>, <code class="font-mono text-shopee">account_name</code> — {{ __('bắt buộc.') }}</li>
                    <li><code class="font-mono">bank_name</code> / <code class="font-mono">wallet_name</code>, <code class="font-mono">otp_code</code> — {{ __('theo phương thức & cấu hình OTP.') }}</li>
                </ul>
                <div class="bg-gray-900 dark:bg-slate-950 rounded-xl p-3.5 font-mono text-[10.5px] text-gray-100 overflow-x-auto shadow-inner select-all leading-relaxed">curl -X POST {{ $apiBase }}/withdrawals \<br>&nbsp;&nbsp;-H "Authorization: Bearer &lt;token&gt;" -H "Content-Type: application/json" \<br>&nbsp;&nbsp;-d '{"amount":50000,"payment_method":"bank","bank_name":"Techcombank","account_number":"190123","account_name":"NGUYEN VAN A"}'</div>
                <p class="font-semibold text-gray-700 dark:text-slate-200">{{ __('Phản hồi mẫu:') }}</p>
                <div class="bg-gray-100 dark:bg-slate-950/60 rounded-xl p-3.5 font-mono text-[10.5px] text-gray-700 dark:text-slate-300 overflow-x-auto leading-relaxed">{<br>&nbsp;&nbsp;"success": true,<br>&nbsp;&nbsp;"message": "Tạo yêu cầu rút tiền thành công! Vui lòng chờ admin xét duyệt.",<br>&nbsp;&nbsp;"data": { "id": 12, "code": "WD8F3A21C", "amount": 50000, "fee": 0, "real_amount": 50000, "status": "pending" }<br>}</div>
            </div>
        </div>

        {{-- =================== 4b. API Sổ tài khoản nhận tiền =================== --}}
        <div x-data="{ open: false, enabled: {{ ($settings['openapi_payment_accounts_status'] ?? '1') === '1' ? 'true' : 'false' }} }"
            class="bg-white dark:bg-slate-800/40 border border-gray-200 dark:border-slate-800 rounded-2xl overflow-hidden">
            <div class="flex items-center gap-3 p-4">
                <button type="button" @click="open = !open" class="flex items-center gap-3 flex-1 text-left min-w-0">
                    <span class="shrink-0 w-9 h-9 rounded-xl bg-shopee/10 text-shopee flex items-center justify-center">
                        <i data-lucide="landmark" class="w-4.5 h-4.5"></i>
                    </span>
                    <span class="min-w-0">
                        <span class="block text-sm font-bold text-gray-800 dark:text-slate-200 truncate">{{ __('Sổ tài khoản nhận tiền') }}</span>
                        <span class="flex items-center gap-1.5 mt-0.5 flex-wrap">
                            <span class="text-[9px] font-bold px-1.5 py-0.5 rounded bg-sky-100 dark:bg-sky-500/15 text-sky-700 dark:text-sky-400">GET</span>
                            <span class="text-[9px] font-bold px-1.5 py-0.5 rounded bg-emerald-100 dark:bg-emerald-500/15 text-emerald-700 dark:text-emerald-400">POST</span>
                            <span class="text-[9px] font-bold px-1.5 py-0.5 rounded bg-red-100 dark:bg-red-500/15 text-red-700 dark:text-red-400">DELETE</span>
                            <code class="text-[10px] text-gray-500 dark:text-slate-400 font-mono">/payment-accounts</code>
                        </span>
                    </span>
                </button>
                <input type="hidden" name="openapi_payment_accounts_status" :value="enabled ? '1' : '0'">
                <button type="button" @click="enabled = !enabled" :class="enabled ? 'bg-shopee' : 'bg-gray-300 dark:bg-slate-600'"
                    class="relative shrink-0 inline-flex h-6 w-11 items-center rounded-full transition-colors" title="{{ __('Bật/Tắt API này') }}">
                    <span :class="enabled ? 'translate-x-5.5' : 'translate-x-0.5'" class="inline-block h-5 w-5 transform rounded-full bg-white shadow transition-transform"></span>
                </button>
                <button type="button" @click="open = !open" class="shrink-0 text-gray-400 hover:text-shopee transition-colors">
                    <i data-lucide="chevron-down" class="w-5 h-5 transition-transform" :class="open ? 'rotate-180' : ''"></i>
                </button>
            </div>
            <div x-show="open" x-collapse x-cloak class="px-4 pb-4 border-t border-gray-100 dark:border-slate-800 pt-4 space-y-3 text-xs text-gray-600 dark:text-slate-300 leading-relaxed">
                <p>{{ __('Quản lý sổ tài khoản nhận tiền (ngân hàng, ví điện tử) của thành viên. Lưu trước thông tin tài khoản để điền nhanh khi rút tiền. Yêu cầu xác thực token.') }}</p>
                <p class="font-semibold text-gray-700 dark:text-slate-200">{{ __('Các endpoint:') }}</p>
                <ul class="list-disc pl-5 space-y-0.5 text-[11px]">
                    <li><code class="font-mono"><span class="text-sky-600 dark:text-sky-400">GET</span> /payment-accounts</code> — {{ __('danh sách tài khoản đã lưu (sắp xếp mặc định lên trước).') }}</li>
                    <li><code class="font-mono"><span class="text-emerald-600 dark:text-emerald-400">POST</span> /payment-accounts</code> — {{ __('lưu tài khoản mới.') }}</li>
                    <li><code class="font-mono"><span class="text-emerald-600 dark:text-emerald-400">POST</span> /payment-accounts/{id}/default</code> — {{ __('đặt tài khoản làm mặc định.') }}</li>
                    <li><code class="font-mono"><span class="text-red-600 dark:text-red-400">DELETE</span> /payment-accounts/{id}</code> — {{ __('xoá tài khoản khỏi sổ.') }}</li>
                </ul>
                <p class="font-semibold text-gray-700 dark:text-slate-200">{{ __('Tham số tạo mới (JSON body):') }}</p>
                <ul class="list-disc pl-5 space-y-0.5 text-[11px]">
                    <li><code class="font-mono text-shopee">payment_method</code> — <code class="font-mono">bank</code> {{ __('hoặc') }} <code class="font-mono">wallet</code> {{ __('(bắt buộc).') }}</li>
                    <li><code class="font-mono text-shopee">bank_name</code> — {{ __('tên ngân hàng hoặc ví điện tử (phải nằm trong danh sách hỗ trợ, bắt buộc).') }}</li>
                    <li><code class="font-mono text-shopee">account_number</code> — {{ __('số tài khoản hoặc số điện thoại ví (bắt buộc).') }}</li>
                    <li><code class="font-mono text-shopee">account_name</code> — {{ __('tên chủ tài khoản (bắt buộc, hệ thống tự chuyển HOA).') }}</li>
                    <li><code class="font-mono">is_default</code> — {{ __('true/false, đặt làm mặc định (tùy chọn, mặc định false). Tài khoản đầu tiên luôn tự mặc định.') }}</li>
                </ul>
                <div class="bg-gray-900 dark:bg-slate-950 rounded-xl p-3.5 font-mono text-[10.5px] text-gray-100 overflow-x-auto shadow-inner select-all leading-relaxed">curl -X POST {{ $apiBase }}/payment-accounts \<br>&nbsp;&nbsp;-H "Authorization: Bearer &lt;token&gt;" -H "Content-Type: application/json" \<br>&nbsp;&nbsp;-d '{"payment_method":"bank","bank_name":"Techcombank","account_number":"190123456","account_name":"NGUYEN VAN A"}'</div>
                <p class="font-semibold text-gray-700 dark:text-slate-200">{{ __('Phản hồi mẫu (tạo mới):') }}</p>
                <div class="bg-gray-100 dark:bg-slate-950/60 rounded-xl p-3.5 font-mono text-[10.5px] text-gray-700 dark:text-slate-300 overflow-x-auto leading-relaxed">{<br>&nbsp;&nbsp;"success": true,<br>&nbsp;&nbsp;"message": "Đã lưu tài khoản nhận tiền vào sổ.",<br>&nbsp;&nbsp;"data": { "id": 1, "payment_method": "bank", "bank_name": "Techcombank", "account_number": "190123456", "account_name": "NGUYEN VAN A", "is_default": true }<br>}</div>
                <p class="font-semibold text-gray-700 dark:text-slate-200">{{ __('Phản hồi mẫu (danh sách):') }}</p>
                <div class="bg-gray-100 dark:bg-slate-950/60 rounded-xl p-3.5 font-mono text-[10.5px] text-gray-700 dark:text-slate-300 overflow-x-auto leading-relaxed">{<br>&nbsp;&nbsp;"success": true,<br>&nbsp;&nbsp;"data": { "items": [ { "id": 1, "payment_method": "bank", "bank_name": "Techcombank", "account_number": "190123456", "account_name": "NGUYEN VAN A", "is_default": true } ], "total": 1 }<br>}</div>
                <div class="bg-amber-50 dark:bg-amber-500/10 border border-amber-200 dark:border-amber-500/25 rounded-xl p-3 space-y-1">
                    <p class="font-semibold text-amber-800 dark:text-amber-300 flex items-center gap-1.5">
                        <i data-lucide="info" class="w-3.5 h-3.5"></i>
                        {{ __('Lưu ý') }}
                    </p>
                    <ul class="list-disc pl-5 space-y-0.5 text-[11px] text-amber-800/90 dark:text-amber-200/90">
                        <li>{{ __('Mỗi thành viên lưu được tối đa 10 tài khoản.') }}</li>
                        <li>{{ __('Không cho phép trùng lặp (cùng hình thức + ngân hàng + số tài khoản).') }}</li>
                        <li>{{ __('Khi xoá tài khoản mặc định, hệ thống tự gán mặc định cho tài khoản mới nhất còn lại.') }}</li>
                        <li>{{ __('API này phụ thuộc vào cài đặt "Cho phép lưu tài khoản nhận tiền" trong tab Rút tiền.') }}</li>
                    </ul>
                </div>
            </div>
        </div>

        {{-- =================== 5. API Danh sách đơn hàng =================== --}}
        <div x-data="{ open: false, enabled: {{ ($settings['openapi_orders_status'] ?? '1') === '1' ? 'true' : 'false' }} }"
            class="bg-white dark:bg-slate-800/40 border border-gray-200 dark:border-slate-800 rounded-2xl overflow-hidden">
            <div class="flex items-center gap-3 p-4">
                <button type="button" @click="open = !open" class="flex items-center gap-3 flex-1 text-left min-w-0">
                    <span class="shrink-0 w-9 h-9 rounded-xl bg-shopee/10 text-shopee flex items-center justify-center">
                        <i data-lucide="receipt-text" class="w-4.5 h-4.5"></i>
                    </span>
                    <span class="min-w-0">
                        <span class="block text-sm font-bold text-gray-800 dark:text-slate-200 truncate">{{ __('Danh sách đơn hàng') }}</span>
                        <span class="flex items-center gap-1.5 mt-0.5 flex-wrap">
                            <span class="text-[9px] font-bold px-1.5 py-0.5 rounded bg-sky-100 dark:bg-sky-500/15 text-sky-700 dark:text-sky-400">GET</span>
                            <code class="text-[10px] text-gray-500 dark:text-slate-400 font-mono">/orders</code>
                        </span>
                    </span>
                </button>
                <input type="hidden" name="openapi_orders_status" :value="enabled ? '1' : '0'">
                <button type="button" @click="enabled = !enabled" :class="enabled ? 'bg-shopee' : 'bg-gray-300 dark:bg-slate-600'"
                    class="relative shrink-0 inline-flex h-6 w-11 items-center rounded-full transition-colors" title="{{ __('Bật/Tắt API này') }}">
                    <span :class="enabled ? 'translate-x-5.5' : 'translate-x-0.5'" class="inline-block h-5 w-5 transform rounded-full bg-white shadow transition-transform"></span>
                </button>
                <button type="button" @click="open = !open" class="shrink-0 text-gray-400 hover:text-shopee transition-colors">
                    <i data-lucide="chevron-down" class="w-5 h-5 transition-transform" :class="open ? 'rotate-180' : ''"></i>
                </button>
            </div>
            <div x-show="open" x-collapse x-cloak class="px-4 pb-4 border-t border-gray-100 dark:border-slate-800 pt-4 space-y-3 text-xs text-gray-600 dark:text-slate-300 leading-relaxed">
                <p>{{ __('Lấy danh sách đơn hàng hoàn tiền của thành viên, hỗ trợ lọc theo trạng thái, nền tảng, tìm kiếm và phân trang. Yêu cầu xác thực token.') }}</p>
                <p class="font-semibold text-gray-700 dark:text-slate-200">{{ __('Các endpoint:') }}</p>
                <ul class="list-disc pl-5 space-y-0.5 text-[11px]">
                    <li><code class="font-mono"><span class="text-sky-600 dark:text-sky-400">GET</span> /orders</code> — {{ __('danh sách đơn (lọc & phân trang).') }}</li>
                    <li><code class="font-mono"><span class="text-sky-600 dark:text-sky-400">GET</span> /orders/{id}</code> — {{ __('chi tiết một đơn hàng (kèm giá gốc, hoa hồng, tỉ lệ hoàn, lý do từ chối nếu có).') }}</li>
                </ul>
                <p class="font-semibold text-gray-700 dark:text-slate-200">{{ __('Tham số truy vấn (query):') }}</p>
                <ul class="list-disc pl-5 space-y-0.5 text-[11px]">
                    <li><code class="font-mono">status</code> — pending / approved / rejected.</li>
                    <li><code class="font-mono">platform</code> — shopee / tiktok.</li>
                    <li><code class="font-mono">search</code>, <code class="font-mono">page</code>, <code class="font-mono">per_page</code> {{ __('(tối đa 50).') }}</li>
                </ul>
                <div class="bg-gray-900 dark:bg-slate-950 rounded-xl p-3.5 font-mono text-[10.5px] text-gray-100 overflow-x-auto shadow-inner select-all leading-relaxed">curl "{{ $apiBase }}/orders?status=approved&per_page=20" \<br>&nbsp;&nbsp;-H "Authorization: Bearer &lt;token&gt;"</div>
                <p class="font-semibold text-gray-700 dark:text-slate-200">{{ __('Phản hồi mẫu:') }}</p>
                <div class="bg-gray-100 dark:bg-slate-950/60 rounded-xl p-3.5 font-mono text-[10.5px] text-gray-700 dark:text-slate-300 overflow-x-auto leading-relaxed">{<br>&nbsp;&nbsp;"success": true,<br>&nbsp;&nbsp;"data": { "items": [ { "order_id": "...", "product_name": "...", "cashback_amount": 12000, "status": "approved" } ], "pagination": { "total": 42 } }<br>}</div>
            </div>
        </div>

        {{-- =================== 6. API Thông báo =================== --}}
        <div x-data="{ open: false, enabled: {{ ($settings['openapi_notifications_status'] ?? '1') === '1' ? 'true' : 'false' }} }"
            class="bg-white dark:bg-slate-800/40 border border-gray-200 dark:border-slate-800 rounded-2xl overflow-hidden">
            <div class="flex items-center gap-3 p-4">
                <button type="button" @click="open = !open" class="flex items-center gap-3 flex-1 text-left min-w-0">
                    <span class="shrink-0 w-9 h-9 rounded-xl bg-shopee/10 text-shopee flex items-center justify-center">
                        <i data-lucide="bell" class="w-4.5 h-4.5"></i>
                    </span>
                    <span class="min-w-0">
                        <span class="block text-sm font-bold text-gray-800 dark:text-slate-200 truncate">{{ __('Thông báo') }}</span>
                        <span class="flex items-center gap-1.5 mt-0.5 flex-wrap">
                            <span class="text-[9px] font-bold px-1.5 py-0.5 rounded bg-sky-100 dark:bg-sky-500/15 text-sky-700 dark:text-sky-400">GET</span>
                            <span class="text-[9px] font-bold px-1.5 py-0.5 rounded bg-emerald-100 dark:bg-emerald-500/15 text-emerald-700 dark:text-emerald-400">POST</span>
                            <code class="text-[10px] text-gray-500 dark:text-slate-400 font-mono">/notifications</code>
                        </span>
                    </span>
                </button>
                <input type="hidden" name="openapi_notifications_status" :value="enabled ? '1' : '0'">
                <button type="button" @click="enabled = !enabled" :class="enabled ? 'bg-shopee' : 'bg-gray-300 dark:bg-slate-600'"
                    class="relative shrink-0 inline-flex h-6 w-11 items-center rounded-full transition-colors" title="{{ __('Bật/Tắt API này') }}">
                    <span :class="enabled ? 'translate-x-5.5' : 'translate-x-0.5'" class="inline-block h-5 w-5 transform rounded-full bg-white shadow transition-transform"></span>
                </button>
                <button type="button" @click="open = !open" class="shrink-0 text-gray-400 hover:text-shopee transition-colors">
                    <i data-lucide="chevron-down" class="w-5 h-5 transition-transform" :class="open ? 'rotate-180' : ''"></i>
                </button>
            </div>
            <div x-show="open" x-collapse x-cloak class="px-4 pb-4 border-t border-gray-100 dark:border-slate-800 pt-4 space-y-3 text-xs text-gray-600 dark:text-slate-300 leading-relaxed">
                <p>{{ __('Hộp thư thông báo trong ứng dụng: danh sách, đếm số chưa đọc (badge) và đánh dấu đã đọc. Yêu cầu xác thực token.') }}</p>
                <p class="font-semibold text-gray-700 dark:text-slate-200">{{ __('Các endpoint:') }}</p>
                <ul class="list-disc pl-5 space-y-0.5 text-[11px]">
                    <li><code class="font-mono">GET /notifications</code> — {{ __('danh sách (lọc type, filter=unread, phân trang).') }}</li>
                    <li><code class="font-mono">GET /notifications/unread-count</code> — {{ __('số thông báo chưa đọc cho badge.') }}</li>
                    <li><code class="font-mono">POST /notifications/{id}/read</code> — {{ __('đánh dấu 1 thông báo đã đọc.') }}</li>
                    <li><code class="font-mono">POST /notifications/read-all</code> — {{ __('đánh dấu tất cả đã đọc.') }}</li>
                </ul>
                <div class="bg-gray-900 dark:bg-slate-950 rounded-xl p-3.5 font-mono text-[10.5px] text-gray-100 overflow-x-auto shadow-inner select-all leading-relaxed">curl "{{ $apiBase }}/notifications?filter=unread" \<br>&nbsp;&nbsp;-H "Authorization: Bearer &lt;token&gt;"</div>
                <p class="font-semibold text-gray-700 dark:text-slate-200">{{ __('Phản hồi mẫu:') }}</p>
                <div class="bg-gray-100 dark:bg-slate-950/60 rounded-xl p-3.5 font-mono text-[10.5px] text-gray-700 dark:text-slate-300 overflow-x-auto leading-relaxed">{<br>&nbsp;&nbsp;"success": true,<br>&nbsp;&nbsp;"data": { "items": [ { "id": 1, "title": "...", "content": "...", "type": "personal", "is_read": false, "created_at": "2026-07-10T09:12:00+07:00" } ], "unread_total": 3, "pagination": {...} }<br>}</div>
            </div>
        </div>

        {{-- =================== 7. API Nhiệm vụ nhận thưởng =================== --}}
        <div x-data="{ open: false, enabled: {{ ($settings['openapi_tasks_status'] ?? '1') === '1' ? 'true' : 'false' }} }"
            class="bg-white dark:bg-slate-800/40 border border-gray-200 dark:border-slate-800 rounded-2xl overflow-hidden">
            <div class="flex items-center gap-3 p-4">
                <button type="button" @click="open = !open" class="flex items-center gap-3 flex-1 text-left min-w-0">
                    <span class="shrink-0 w-9 h-9 rounded-xl bg-shopee/10 text-shopee flex items-center justify-center">
                        <i data-lucide="target" class="w-4.5 h-4.5"></i>
                    </span>
                    <span class="min-w-0">
                        <span class="block text-sm font-bold text-gray-800 dark:text-slate-200 truncate">{{ __('Nhiệm vụ nhận thưởng') }}</span>
                        <span class="flex items-center gap-1.5 mt-0.5 flex-wrap">
                            <span class="text-[9px] font-bold px-1.5 py-0.5 rounded bg-sky-100 dark:bg-sky-500/15 text-sky-700 dark:text-sky-400">GET</span>
                            <span class="text-[9px] font-bold px-1.5 py-0.5 rounded bg-emerald-100 dark:bg-emerald-500/15 text-emerald-700 dark:text-emerald-400">POST</span>
                            <code class="text-[10px] text-gray-500 dark:text-slate-400 font-mono">/tasks</code>
                        </span>
                    </span>
                </button>
                <input type="hidden" name="openapi_tasks_status" :value="enabled ? '1' : '0'">
                <button type="button" @click="enabled = !enabled" :class="enabled ? 'bg-shopee' : 'bg-gray-300 dark:bg-slate-600'"
                    class="relative shrink-0 inline-flex h-6 w-11 items-center rounded-full transition-colors" title="{{ __('Bật/Tắt API này') }}">
                    <span :class="enabled ? 'translate-x-5.5' : 'translate-x-0.5'" class="inline-block h-5 w-5 transform rounded-full bg-white shadow transition-transform"></span>
                </button>
                <button type="button" @click="open = !open" class="shrink-0 text-gray-400 hover:text-shopee transition-colors">
                    <i data-lucide="chevron-down" class="w-5 h-5 transition-transform" :class="open ? 'rotate-180' : ''"></i>
                </button>
            </div>
            <div x-show="open" x-collapse x-cloak class="px-4 pb-4 border-t border-gray-100 dark:border-slate-800 pt-4 space-y-3 text-xs text-gray-600 dark:text-slate-300 leading-relaxed">
                <p>{{ __('Danh sách nhiệm vụ đang hoạt động kèm tiến độ của thành viên, đồng bộ tiến độ và nhận thưởng khi hoàn thành. Yêu cầu xác thực token. Chỉ hoạt động khi tính năng Nhiệm vụ được bật.') }}</p>
                <p class="font-semibold text-gray-700 dark:text-slate-200">{{ __('Các endpoint:') }}</p>
                <ul class="list-disc pl-5 space-y-0.5 text-[11px]">
                    <li><code class="font-mono"><span class="text-sky-600 dark:text-sky-400">GET</span> /tasks</code> — {{ __('danh sách nhiệm vụ kèm tiến độ & thống kê (đã nhận, hoàn thành, đang làm, tổng thưởng).') }}</li>
                    <li><code class="font-mono"><span class="text-sky-600 dark:text-sky-400">GET</span> /tasks/{id}/sync</code> — {{ __('đồng bộ lại tiến độ nhiệm vụ theo dữ liệu thực tế.') }}</li>
                    <li><code class="font-mono"><span class="text-emerald-600 dark:text-emerald-400">POST</span> /tasks/{id}/claim</code> — {{ __('nhận thưởng khi nhiệm vụ đã hoàn thành (cộng tiền vào ví).') }}</li>
                    <li><code class="font-mono"><span class="text-emerald-600 dark:text-emerald-400">POST</span> /tasks/{id}/submit</code> — {{ __('gửi yêu cầu xác nhận đã hoàn thành nhiệm vụ thủ công, kèm tham số note (tối đa 500 ký tự), chờ Admin duyệt.') }}</li>
                </ul>
                <div class="bg-gray-900 dark:bg-slate-950 rounded-xl p-3.5 font-mono text-[10.5px] text-gray-100 overflow-x-auto shadow-inner select-all leading-relaxed">curl {{ $apiBase }}/tasks \<br>&nbsp;&nbsp;-H "Authorization: Bearer &lt;token&gt;"</div>
                <p class="font-semibold text-gray-700 dark:text-slate-200">{{ __('Phản hồi mẫu:') }}</p>
                <div class="bg-gray-100 dark:bg-slate-950/60 rounded-xl p-3.5 font-mono text-[10.5px] text-gray-700 dark:text-slate-300 overflow-x-auto leading-relaxed">{<br>&nbsp;&nbsp;"success": true,<br>&nbsp;&nbsp;"data": { "items": [ { "id": 1, "title": "...", "target_count": 3, "reward_amount": 10000, "progress": 2, "percent": 66, "status": "in_progress" } ], "stats": { "claimed": 4, "completed": 1, "in_progress": 2, "total_earned": 40000 } }<br>}</div>
                <p class="font-semibold text-gray-700 dark:text-slate-200 pt-1">{{ __('Nhận thưởng:') }}</p>
                <div class="bg-gray-900 dark:bg-slate-950 rounded-xl p-3.5 font-mono text-[10.5px] text-gray-100 overflow-x-auto shadow-inner select-all leading-relaxed">curl -X POST {{ $apiBase }}/tasks/1/claim \<br>&nbsp;&nbsp;-H "Authorization: Bearer &lt;token&gt;"</div>
                <p class="font-semibold text-gray-700 dark:text-slate-200 pt-1">{{ __('Gửi yêu cầu xác nhận nhiệm vụ thủ công:') }}</p>
                <div class="bg-gray-900 dark:bg-slate-950 rounded-xl p-3.5 font-mono text-[10.5px] text-gray-100 overflow-x-auto shadow-inner select-all leading-relaxed">curl -X POST {{ $apiBase }}/tasks/1/submit \<br>&nbsp;&nbsp;-H "Authorization: Bearer &lt;token&gt;" \<br>&nbsp;&nbsp;-d "note=Link bài đăng: https://..."</div>
            </div>
        </div>

        {{-- =================== 8. API Điểm danh hàng ngày =================== --}}
        <div x-data="{ open: false, enabled: {{ ($settings['openapi_checkin_status'] ?? '1') === '1' ? 'true' : 'false' }} }"
            class="bg-white dark:bg-slate-800/40 border border-gray-200 dark:border-slate-800 rounded-2xl overflow-hidden">
            <div class="flex items-center gap-3 p-4">
                <button type="button" @click="open = !open" class="flex items-center gap-3 flex-1 text-left min-w-0">
                    <span class="shrink-0 w-9 h-9 rounded-xl bg-shopee/10 text-shopee flex items-center justify-center">
                        <i data-lucide="calendar-check" class="w-4.5 h-4.5"></i>
                    </span>
                    <span class="min-w-0">
                        <span class="block text-sm font-bold text-gray-800 dark:text-slate-200 truncate">{{ __('Điểm danh hàng ngày') }}</span>
                        <span class="flex items-center gap-1.5 mt-0.5 flex-wrap">
                            <span class="text-[9px] font-bold px-1.5 py-0.5 rounded bg-sky-100 dark:bg-sky-500/15 text-sky-700 dark:text-sky-400">GET</span>
                            <span class="text-[9px] font-bold px-1.5 py-0.5 rounded bg-emerald-100 dark:bg-emerald-500/15 text-emerald-700 dark:text-emerald-400">POST</span>
                            <code class="text-[10px] text-gray-500 dark:text-slate-400 font-mono">/checkin</code>
                        </span>
                    </span>
                </button>
                <input type="hidden" name="openapi_checkin_status" :value="enabled ? '1' : '0'">
                <button type="button" @click="enabled = !enabled" :class="enabled ? 'bg-shopee' : 'bg-gray-300 dark:bg-slate-600'"
                    class="relative shrink-0 inline-flex h-6 w-11 items-center rounded-full transition-colors" title="{{ __('Bật/Tắt API này') }}">
                    <span :class="enabled ? 'translate-x-5.5' : 'translate-x-0.5'" class="inline-block h-5 w-5 transform rounded-full bg-white shadow transition-transform"></span>
                </button>
                <button type="button" @click="open = !open" class="shrink-0 text-gray-400 hover:text-shopee transition-colors">
                    <i data-lucide="chevron-down" class="w-5 h-5 transition-transform" :class="open ? 'rotate-180' : ''"></i>
                </button>
            </div>
            <div x-show="open" x-collapse x-cloak class="px-4 pb-4 border-t border-gray-100 dark:border-slate-800 pt-4 space-y-3 text-xs text-gray-600 dark:text-slate-300 leading-relaxed">
                <p>{{ __('Lấy trạng thái điểm danh (chuỗi streak, mốc thưởng, lịch sử, điều kiện) và thực hiện điểm danh nhận thưởng vào ví. Yêu cầu xác thực token. Chỉ hoạt động khi tính năng Điểm danh được bật.') }}</p>
                <ul class="list-disc pl-5 space-y-0.5 text-[11px]">
                    <li><code class="font-mono"><span class="text-sky-600 dark:text-sky-400">GET</span> /checkin</code> — {{ __('trạng thái: đã điểm danh hôm nay chưa, streak, có thể điểm danh không (kèm lý do), mốc thưởng và lịch sử.') }}</li>
                    <li><code class="font-mono"><span class="text-emerald-600 dark:text-emerald-400">POST</span> /checkin</code> — {{ __('thực hiện điểm danh; cộng thưởng vào ví trong giao dịch khóa dòng chống điểm danh trùng.') }}</li>
                </ul>
                <div class="bg-gray-900 dark:bg-slate-950 rounded-xl p-3.5 font-mono text-[10.5px] text-gray-100 overflow-x-auto shadow-inner select-all leading-relaxed">curl -X POST {{ $apiBase }}/checkin \<br>&nbsp;&nbsp;-H "Authorization: Bearer &lt;token&gt;"</div>
                <p class="font-semibold text-gray-700 dark:text-slate-200">{{ __('Phản hồi mẫu:') }}</p>
                <div class="bg-gray-100 dark:bg-slate-950/60 rounded-xl p-3.5 font-mono text-[10.5px] text-gray-700 dark:text-slate-300 overflow-x-auto leading-relaxed">{<br>&nbsp;&nbsp;"success": true,<br>&nbsp;&nbsp;"data": { "coins_earned": 500, "streak_days": 3, "is_bonus": false, "new_balance": 150500 }<br>}</div>
            </div>
        </div>

        {{-- =================== 9. API Giới thiệu F1/F2 =================== --}}
        <div x-data="{ open: false, enabled: {{ ($settings['openapi_referrals_status'] ?? '1') === '1' ? 'true' : 'false' }} }"
            class="bg-white dark:bg-slate-800/40 border border-gray-200 dark:border-slate-800 rounded-2xl overflow-hidden">
            <div class="flex items-center gap-3 p-4">
                <button type="button" @click="open = !open" class="flex items-center gap-3 flex-1 text-left min-w-0">
                    <span class="shrink-0 w-9 h-9 rounded-xl bg-shopee/10 text-shopee flex items-center justify-center">
                        <i data-lucide="users" class="w-4.5 h-4.5"></i>
                    </span>
                    <span class="min-w-0">
                        <span class="block text-sm font-bold text-gray-800 dark:text-slate-200 truncate">{{ __('Giới thiệu F1/F2 (Tiếp thị liên kết)') }}</span>
                        <span class="flex items-center gap-1.5 mt-0.5 flex-wrap">
                            <span class="text-[9px] font-bold px-1.5 py-0.5 rounded bg-sky-100 dark:bg-sky-500/15 text-sky-700 dark:text-sky-400">GET</span>
                            <code class="text-[10px] text-gray-500 dark:text-slate-400 font-mono">/referrals</code>
                        </span>
                    </span>
                </button>
                <input type="hidden" name="openapi_referrals_status" :value="enabled ? '1' : '0'">
                <button type="button" @click="enabled = !enabled" :class="enabled ? 'bg-shopee' : 'bg-gray-300 dark:bg-slate-600'"
                    class="relative shrink-0 inline-flex h-6 w-11 items-center rounded-full transition-colors" title="{{ __('Bật/Tắt API này') }}">
                    <span :class="enabled ? 'translate-x-5.5' : 'translate-x-0.5'" class="inline-block h-5 w-5 transform rounded-full bg-white shadow transition-transform"></span>
                </button>
                <button type="button" @click="open = !open" class="shrink-0 text-gray-400 hover:text-shopee transition-colors">
                    <i data-lucide="chevron-down" class="w-5 h-5 transition-transform" :class="open ? 'rotate-180' : ''"></i>
                </button>
            </div>
            <div x-show="open" x-collapse x-cloak class="px-4 pb-4 border-t border-gray-100 dark:border-slate-800 pt-4 space-y-3 text-xs text-gray-600 dark:text-slate-300 leading-relaxed">
                <p>{{ __('Lấy mã & link giới thiệu, thống kê hoa hồng, danh sách thành viên tuyến dưới F1/F2 và lịch sử hoa hồng (phân trang). Email thành viên cấp dưới được che một phần để bảo vệ quyền riêng tư. Yêu cầu xác thực token.') }}</p>
                <p class="font-semibold text-gray-700 dark:text-slate-200">{{ __('Tham số truy vấn (query):') }}</p>
                <ul class="list-disc pl-5 space-y-0.5 text-[11px]">
                    <li><code class="font-mono">level</code> — 1 (F1) / 2 (F2), <code class="font-mono">status</code> — pending / approved {{ __('(lọc lịch sử hoa hồng).') }}</li>
                    <li><code class="font-mono">page</code>, <code class="font-mono">per_page</code> {{ __('(tối đa 50).') }}</li>
                </ul>
                <div class="bg-gray-900 dark:bg-slate-950 rounded-xl p-3.5 font-mono text-[10.5px] text-gray-100 overflow-x-auto shadow-inner select-all leading-relaxed">curl {{ $apiBase }}/referrals \<br>&nbsp;&nbsp;-H "Authorization: Bearer &lt;token&gt;"</div>
                <p class="font-semibold text-gray-700 dark:text-slate-200">{{ __('Phản hồi mẫu:') }}</p>
                <div class="bg-gray-100 dark:bg-slate-950/60 rounded-xl p-3.5 font-mono text-[10.5px] text-gray-700 dark:text-slate-300 overflow-x-auto leading-relaxed">{<br>&nbsp;&nbsp;"success": true,<br>&nbsp;&nbsp;"data": { "referral_code": "REFABC123", "referral_link": "...", "stats": { "f1_count": 5, "f2_count": 12, "total_commission": 250000 }, "f1_members": [...], "commissions": { "items": [...] } }<br>}</div>
            </div>
        </div>

        {{-- =================== 10. API Biến động số dư =================== --}}
        <div x-data="{ open: false, enabled: {{ ($settings['openapi_balance_logs_status'] ?? '1') === '1' ? 'true' : 'false' }} }"
            class="bg-white dark:bg-slate-800/40 border border-gray-200 dark:border-slate-800 rounded-2xl overflow-hidden">
            <div class="flex items-center gap-3 p-4">
                <button type="button" @click="open = !open" class="flex items-center gap-3 flex-1 text-left min-w-0">
                    <span class="shrink-0 w-9 h-9 rounded-xl bg-shopee/10 text-shopee flex items-center justify-center">
                        <i data-lucide="arrow-left-right" class="w-4.5 h-4.5"></i>
                    </span>
                    <span class="min-w-0">
                        <span class="block text-sm font-bold text-gray-800 dark:text-slate-200 truncate">{{ __('Biến động số dư') }}</span>
                        <span class="flex items-center gap-1.5 mt-0.5 flex-wrap">
                            <span class="text-[9px] font-bold px-1.5 py-0.5 rounded bg-sky-100 dark:bg-sky-500/15 text-sky-700 dark:text-sky-400">GET</span>
                            <code class="text-[10px] text-gray-500 dark:text-slate-400 font-mono">/balance-logs</code>
                        </span>
                    </span>
                </button>
                <input type="hidden" name="openapi_balance_logs_status" :value="enabled ? '1' : '0'">
                <button type="button" @click="enabled = !enabled" :class="enabled ? 'bg-shopee' : 'bg-gray-300 dark:bg-slate-600'"
                    class="relative shrink-0 inline-flex h-6 w-11 items-center rounded-full transition-colors" title="{{ __('Bật/Tắt API này') }}">
                    <span :class="enabled ? 'translate-x-5.5' : 'translate-x-0.5'" class="inline-block h-5 w-5 transform rounded-full bg-white shadow transition-transform"></span>
                </button>
                <button type="button" @click="open = !open" class="shrink-0 text-gray-400 hover:text-shopee transition-colors">
                    <i data-lucide="chevron-down" class="w-5 h-5 transition-transform" :class="open ? 'rotate-180' : ''"></i>
                </button>
            </div>
            <div x-show="open" x-collapse x-cloak class="px-4 pb-4 border-t border-gray-100 dark:border-slate-800 pt-4 space-y-3 text-xs text-gray-600 dark:text-slate-300 leading-relaxed">
                <p>{{ __('Lịch sử mọi giao dịch cộng/trừ số dư ví (điểm danh, đổi quà, giftcode, rút tiền, hoa hồng...). Hỗ trợ lọc theo loại giao dịch, tìm kiếm và phân trang. Yêu cầu xác thực token.') }}</p>
                <ul class="list-disc pl-5 space-y-0.5 text-[11px]">
                    <li><code class="font-mono">type</code> — {{ __('lọc theo loại giao dịch (checkin, gift_exchange, withdraw_request...).') }}</li>
                    <li><code class="font-mono">search</code>, <code class="font-mono">page</code>, <code class="font-mono">per_page</code> {{ __('(tối đa 50).') }}</li>
                </ul>
                <div class="bg-gray-900 dark:bg-slate-950 rounded-xl p-3.5 font-mono text-[10.5px] text-gray-100 overflow-x-auto shadow-inner select-all leading-relaxed">curl "{{ $apiBase }}/balance-logs?per_page=20" \<br>&nbsp;&nbsp;-H "Authorization: Bearer &lt;token&gt;"</div>
                <p class="font-semibold text-gray-700 dark:text-slate-200">{{ __('Phản hồi mẫu:') }}</p>
                <div class="bg-gray-100 dark:bg-slate-950/60 rounded-xl p-3.5 font-mono text-[10.5px] text-gray-700 dark:text-slate-300 overflow-x-auto leading-relaxed">{<br>&nbsp;&nbsp;"success": true,<br>&nbsp;&nbsp;"data": { "items": [ { "type": "checkin", "amount_change": 500, "amount_after": 150500, "is_credit": true, "description": "..." } ], "pagination": {...} }<br>}</div>
            </div>
        </div>

        {{-- =================== 11. API Đổi quà tặng =================== --}}
        <div x-data="{ open: false, enabled: {{ ($settings['openapi_gifts_status'] ?? '1') === '1' ? 'true' : 'false' }} }"
            class="bg-white dark:bg-slate-800/40 border border-gray-200 dark:border-slate-800 rounded-2xl overflow-hidden">
            <div class="flex items-center gap-3 p-4">
                <button type="button" @click="open = !open" class="flex items-center gap-3 flex-1 text-left min-w-0">
                    <span class="shrink-0 w-9 h-9 rounded-xl bg-shopee/10 text-shopee flex items-center justify-center">
                        <i data-lucide="gift" class="w-4.5 h-4.5"></i>
                    </span>
                    <span class="min-w-0">
                        <span class="block text-sm font-bold text-gray-800 dark:text-slate-200 truncate">{{ __('Đổi quà tặng') }}</span>
                        <span class="flex items-center gap-1.5 mt-0.5 flex-wrap">
                            <span class="text-[9px] font-bold px-1.5 py-0.5 rounded bg-sky-100 dark:bg-sky-500/15 text-sky-700 dark:text-sky-400">GET</span>
                            <span class="text-[9px] font-bold px-1.5 py-0.5 rounded bg-emerald-100 dark:bg-emerald-500/15 text-emerald-700 dark:text-emerald-400">POST</span>
                            <code class="text-[10px] text-gray-500 dark:text-slate-400 font-mono">/gifts</code>
                        </span>
                    </span>
                </button>
                <input type="hidden" name="openapi_gifts_status" :value="enabled ? '1' : '0'">
                <button type="button" @click="enabled = !enabled" :class="enabled ? 'bg-shopee' : 'bg-gray-300 dark:bg-slate-600'"
                    class="relative shrink-0 inline-flex h-6 w-11 items-center rounded-full transition-colors" title="{{ __('Bật/Tắt API này') }}">
                    <span :class="enabled ? 'translate-x-5.5' : 'translate-x-0.5'" class="inline-block h-5 w-5 transform rounded-full bg-white shadow transition-transform"></span>
                </button>
                <button type="button" @click="open = !open" class="shrink-0 text-gray-400 hover:text-shopee transition-colors">
                    <i data-lucide="chevron-down" class="w-5 h-5 transition-transform" :class="open ? 'rotate-180' : ''"></i>
                </button>
            </div>
            <div x-show="open" x-collapse x-cloak class="px-4 pb-4 border-t border-gray-100 dark:border-slate-800 pt-4 space-y-3 text-xs text-gray-600 dark:text-slate-300 leading-relaxed">
                <p>{{ __('Danh sách quà tặng, lịch sử đổi quà và thực hiện quy đổi (trừ ví trong giao dịch khóa dòng chống vượt kho/âm ví). Yêu cầu xác thực token. Chỉ hoạt động khi tính năng Đổi quà được bật.') }}</p>
                <ul class="list-disc pl-5 space-y-0.5 text-[11px]">
                    <li><code class="font-mono"><span class="text-sky-600 dark:text-sky-400">GET</span> /gifts</code> — {{ __('danh sách quà (lọc search, tag, type, sort, phân trang).') }}</li>
                    <li><code class="font-mono"><span class="text-sky-600 dark:text-sky-400">GET</span> /gifts/redemptions</code> — {{ __('lịch sử đổi quà của thành viên.') }}</li>
                    <li><code class="font-mono"><span class="text-emerald-600 dark:text-emerald-400">POST</span> /gifts/redeem</code> — {{ __('đổi quà (body: gift_id, fullname, phone, email, address, notes).') }}</li>
                </ul>
                <div class="bg-gray-900 dark:bg-slate-950 rounded-xl p-3.5 font-mono text-[10.5px] text-gray-100 overflow-x-auto shadow-inner select-all leading-relaxed">curl -X POST {{ $apiBase }}/gifts/redeem \<br>&nbsp;&nbsp;-H "Authorization: Bearer &lt;token&gt;" -H "Content-Type: application/json" \<br>&nbsp;&nbsp;-d '{"gift_id":1,"fullname":"NGUYEN VAN A","phone":"09xx","email":"a@gmail.com","address":"..."}'</div>
                <p class="font-semibold text-gray-700 dark:text-slate-200">{{ __('Phản hồi mẫu:') }}</p>
                <div class="bg-gray-100 dark:bg-slate-950/60 rounded-xl p-3.5 font-mono text-[10.5px] text-gray-700 dark:text-slate-300 overflow-x-auto leading-relaxed">{<br>&nbsp;&nbsp;"success": true,<br>&nbsp;&nbsp;"message": "Gửi yêu cầu đổi quà thành công! Vui lòng chờ admin duyệt và gửi dữ liệu.",<br>&nbsp;&nbsp;"data": { "code": "GFT8A2BC1D9", "amount": 20000, "status": "pending" }<br>}</div>
            </div>
        </div>

        {{-- =================== 12. API Nhập Giftcode =================== --}}
        <div x-data="{ open: false, enabled: {{ ($settings['openapi_giftcode_status'] ?? '1') === '1' ? 'true' : 'false' }} }"
            class="bg-white dark:bg-slate-800/40 border border-gray-200 dark:border-slate-800 rounded-2xl overflow-hidden">
            <div class="flex items-center gap-3 p-4">
                <button type="button" @click="open = !open" class="flex items-center gap-3 flex-1 text-left min-w-0">
                    <span class="shrink-0 w-9 h-9 rounded-xl bg-shopee/10 text-shopee flex items-center justify-center">
                        <i data-lucide="ticket" class="w-4.5 h-4.5"></i>
                    </span>
                    <span class="min-w-0">
                        <span class="block text-sm font-bold text-gray-800 dark:text-slate-200 truncate">{{ __('Nhập Giftcode') }}</span>
                        <span class="flex items-center gap-1.5 mt-0.5 flex-wrap">
                            <span class="text-[9px] font-bold px-1.5 py-0.5 rounded bg-emerald-100 dark:bg-emerald-500/15 text-emerald-700 dark:text-emerald-400">POST</span>
                            <code class="text-[10px] text-gray-500 dark:text-slate-400 font-mono">/giftcode/redeem</code>
                        </span>
                    </span>
                </button>
                <input type="hidden" name="openapi_giftcode_status" :value="enabled ? '1' : '0'">
                <button type="button" @click="enabled = !enabled" :class="enabled ? 'bg-shopee' : 'bg-gray-300 dark:bg-slate-600'"
                    class="relative shrink-0 inline-flex h-6 w-11 items-center rounded-full transition-colors" title="{{ __('Bật/Tắt API này') }}">
                    <span :class="enabled ? 'translate-x-5.5' : 'translate-x-0.5'" class="inline-block h-5 w-5 transform rounded-full bg-white shadow transition-transform"></span>
                </button>
                <button type="button" @click="open = !open" class="shrink-0 text-gray-400 hover:text-shopee transition-colors">
                    <i data-lucide="chevron-down" class="w-5 h-5 transition-transform" :class="open ? 'rotate-180' : ''"></i>
                </button>
            </div>
            <div x-show="open" x-collapse x-cloak class="px-4 pb-4 border-t border-gray-100 dark:border-slate-800 pt-4 space-y-3 text-xs text-gray-600 dark:text-slate-300 leading-relaxed">
                <p>{{ __('Nhập mã Giftcode để nhận thưởng vào ví khả dụng (kiểm tra hiệu lực, điều kiện & giới hạn lượt trong giao dịch khóa dòng). Yêu cầu xác thực token. Chỉ hoạt động khi tính năng Giftcode được bật.') }}</p>
                <p class="font-semibold text-gray-700 dark:text-slate-200">{{ __('Tham số (JSON body):') }} <code class="font-mono text-shopee">code</code></p>
                <div class="bg-gray-900 dark:bg-slate-950 rounded-xl p-3.5 font-mono text-[10.5px] text-gray-100 overflow-x-auto shadow-inner select-all leading-relaxed">curl -X POST {{ $apiBase }}/giftcode/redeem \<br>&nbsp;&nbsp;-H "Authorization: Bearer &lt;token&gt;" -H "Content-Type: application/json" \<br>&nbsp;&nbsp;-d '{"code":"WELCOME2025"}'</div>
                <p class="font-semibold text-gray-700 dark:text-slate-200">{{ __('Phản hồi mẫu:') }}</p>
                <div class="bg-gray-100 dark:bg-slate-950/60 rounded-xl p-3.5 font-mono text-[10.5px] text-gray-700 dark:text-slate-300 overflow-x-auto leading-relaxed">{ "success": true, "message": "...", "data": { "amount": 20000 } }</div>
            </div>
        </div>

        {{-- =================== 13. API Sản phẩm đã lưu =================== --}}
        <div x-data="{ open: false, enabled: {{ ($settings['openapi_saved_products_status'] ?? '1') === '1' ? 'true' : 'false' }} }"
            class="bg-white dark:bg-slate-800/40 border border-gray-200 dark:border-slate-800 rounded-2xl overflow-hidden">
            <div class="flex items-center gap-3 p-4">
                <button type="button" @click="open = !open" class="flex items-center gap-3 flex-1 text-left min-w-0">
                    <span class="shrink-0 w-9 h-9 rounded-xl bg-shopee/10 text-shopee flex items-center justify-center">
                        <i data-lucide="bookmark" class="w-4.5 h-4.5"></i>
                    </span>
                    <span class="min-w-0">
                        <span class="block text-sm font-bold text-gray-800 dark:text-slate-200 truncate">{{ __('Sản phẩm đã lưu') }}</span>
                        <span class="flex items-center gap-1.5 mt-0.5 flex-wrap">
                            <span class="text-[9px] font-bold px-1.5 py-0.5 rounded bg-sky-100 dark:bg-sky-500/15 text-sky-700 dark:text-sky-400">GET</span>
                            <span class="text-[9px] font-bold px-1.5 py-0.5 rounded bg-emerald-100 dark:bg-emerald-500/15 text-emerald-700 dark:text-emerald-400">POST</span>
                            <span class="text-[9px] font-bold px-1.5 py-0.5 rounded bg-rose-100 dark:bg-rose-500/15 text-rose-700 dark:text-rose-400">DELETE</span>
                            <code class="text-[10px] text-gray-500 dark:text-slate-400 font-mono">/saved-products</code>
                        </span>
                    </span>
                </button>
                <input type="hidden" name="openapi_saved_products_status" :value="enabled ? '1' : '0'">
                <button type="button" @click="enabled = !enabled" :class="enabled ? 'bg-shopee' : 'bg-gray-300 dark:bg-slate-600'"
                    class="relative shrink-0 inline-flex h-6 w-11 items-center rounded-full transition-colors" title="{{ __('Bật/Tắt API này') }}">
                    <span :class="enabled ? 'translate-x-5.5' : 'translate-x-0.5'" class="inline-block h-5 w-5 transform rounded-full bg-white shadow transition-transform"></span>
                </button>
                <button type="button" @click="open = !open" class="shrink-0 text-gray-400 hover:text-shopee transition-colors">
                    <i data-lucide="chevron-down" class="w-5 h-5 transition-transform" :class="open ? 'rotate-180' : ''"></i>
                </button>
            </div>
            <div x-show="open" x-collapse x-cloak class="px-4 pb-4 border-t border-gray-100 dark:border-slate-800 pt-4 space-y-3 text-xs text-gray-600 dark:text-slate-300 leading-relaxed">
                <p>{{ __('Quản lý danh sách sản phẩm lưu để mua sau: xem danh sách, thêm và xóa. Yêu cầu xác thực token.') }}</p>
                <ul class="list-disc pl-5 space-y-0.5 text-[11px]">
                    <li><code class="font-mono"><span class="text-sky-600 dark:text-sky-400">GET</span> /saved-products</code> — {{ __('danh sách (phân trang).') }}</li>
                    <li><code class="font-mono"><span class="text-emerald-600 dark:text-emerald-400">POST</span> /saved-products</code> — {{ __('lưu sản phẩm (body: name, price, cashback_amount, affiliate_url, product_url, image, platform).') }}</li>
                    <li><code class="font-mono"><span class="text-rose-600 dark:text-rose-400">DELETE</span> /saved-products/{id}</code> — {{ __('xóa sản phẩm khỏi danh sách.') }}</li>
                </ul>
                <div class="bg-gray-900 dark:bg-slate-950 rounded-xl p-3.5 font-mono text-[10.5px] text-gray-100 overflow-x-auto shadow-inner select-all leading-relaxed">curl {{ $apiBase }}/saved-products \<br>&nbsp;&nbsp;-H "Authorization: Bearer &lt;token&gt;"</div>
                <p class="font-semibold text-gray-700 dark:text-slate-200">{{ __('Phản hồi mẫu:') }}</p>
                <div class="bg-gray-100 dark:bg-slate-950/60 rounded-xl p-3.5 font-mono text-[10.5px] text-gray-700 dark:text-slate-300 overflow-x-auto leading-relaxed">{<br>&nbsp;&nbsp;"success": true,<br>&nbsp;&nbsp;"data": { "items": [ { "id": 1, "platform": "shopee", "name": "...", "image": "...", "price": 199000, "cashback_amount": 12000, "affiliate_url": "...", "product_url": "..." } ], "pagination": {...} }<br>}</div>
            </div>
        </div>

        {{-- =================== 14. API Mã giảm giá =================== --}}
        <div x-data="{ open: false, enabled: {{ ($settings['openapi_coupons_status'] ?? '1') === '1' ? 'true' : 'false' }} }"
            class="bg-white dark:bg-slate-800/40 border border-gray-200 dark:border-slate-800 rounded-2xl overflow-hidden">
            <div class="flex items-center gap-3 p-4">
                <button type="button" @click="open = !open" class="flex items-center gap-3 flex-1 text-left min-w-0">
                    <span class="shrink-0 w-9 h-9 rounded-xl bg-shopee/10 text-shopee flex items-center justify-center">
                        <i data-lucide="badge-percent" class="w-4.5 h-4.5"></i>
                    </span>
                    <span class="min-w-0">
                        <span class="block text-sm font-bold text-gray-800 dark:text-slate-200 truncate">{{ __('Mã giảm giá') }}</span>
                        <span class="flex items-center gap-1.5 mt-0.5 flex-wrap">
                            <span class="text-[9px] font-bold px-1.5 py-0.5 rounded bg-sky-100 dark:bg-sky-500/15 text-sky-700 dark:text-sky-400">GET</span>
                            <code class="text-[10px] text-gray-500 dark:text-slate-400 font-mono">/coupons</code>
                        </span>
                    </span>
                </button>
                <input type="hidden" name="openapi_coupons_status" :value="enabled ? '1' : '0'">
                <button type="button" @click="enabled = !enabled" :class="enabled ? 'bg-shopee' : 'bg-gray-300 dark:bg-slate-600'"
                    class="relative shrink-0 inline-flex h-6 w-11 items-center rounded-full transition-colors" title="{{ __('Bật/Tắt API này') }}">
                    <span :class="enabled ? 'translate-x-5.5' : 'translate-x-0.5'" class="inline-block h-5 w-5 transform rounded-full bg-white shadow transition-transform"></span>
                </button>
                <button type="button" @click="open = !open" class="shrink-0 text-gray-400 hover:text-shopee transition-colors">
                    <i data-lucide="chevron-down" class="w-5 h-5 transition-transform" :class="open ? 'rotate-180' : ''"></i>
                </button>
            </div>
            <div x-show="open" x-collapse x-cloak class="px-4 pb-4 border-t border-gray-100 dark:border-slate-800 pt-4 space-y-3 text-xs text-gray-600 dark:text-slate-300 leading-relaxed">
                <p>{{ __('Danh sách mã giảm giá Shopee đang còn hiệu lực, hỗ trợ lọc theo danh mục, tìm kiếm và phân trang. Yêu cầu xác thực token. Chỉ hoạt động khi tính năng Mã giảm giá được bật.') }}</p>
                <ul class="list-disc pl-5 space-y-0.5 text-[11px]">
                    <li><code class="font-mono">category</code>, <code class="font-mono">search</code>, <code class="font-mono">page</code>, <code class="font-mono">per_page</code> {{ __('(tối đa 50).') }}</li>
                </ul>
                <div class="bg-gray-900 dark:bg-slate-950 rounded-xl p-3.5 font-mono text-[10.5px] text-gray-100 overflow-x-auto shadow-inner select-all leading-relaxed">curl "{{ $apiBase }}/coupons?per_page=20" \<br>&nbsp;&nbsp;-H "Authorization: Bearer &lt;token&gt;"</div>
                <p class="font-semibold text-gray-700 dark:text-slate-200">{{ __('Phản hồi mẫu:') }}</p>
                <div class="bg-gray-100 dark:bg-slate-950/60 rounded-xl p-3.5 font-mono text-[10.5px] text-gray-700 dark:text-slate-300 overflow-x-auto leading-relaxed">{<br>&nbsp;&nbsp;"success": true,<br>&nbsp;&nbsp;"data": { "items": [ { "id": 1, "platform": "shopee", "code": "FREESHIP", "title": "...", "category": "...", "min_spend": 0, "discount_amount": 15000, "discount_percentage": 0, "redirect_link": "...", "expired_at": "..." } ], "categories": [...], "pagination": {...} }<br>}</div>
            </div>
        </div>

        {{-- =================== 15. API Bảng xếp hạng =================== --}}
        <div x-data="{ open: false, enabled: {{ ($settings['openapi_ranking_status'] ?? '1') === '1' ? 'true' : 'false' }} }"
            class="bg-white dark:bg-slate-800/40 border border-gray-200 dark:border-slate-800 rounded-2xl overflow-hidden">
            <div class="flex items-center gap-3 p-4">
                <button type="button" @click="open = !open" class="flex items-center gap-3 flex-1 text-left min-w-0">
                    <span class="shrink-0 w-9 h-9 rounded-xl bg-shopee/10 text-shopee flex items-center justify-center">
                        <i data-lucide="trophy" class="w-4.5 h-4.5"></i>
                    </span>
                    <span class="min-w-0">
                        <span class="block text-sm font-bold text-gray-800 dark:text-slate-200 truncate">{{ __('Bảng xếp hạng') }}</span>
                        <span class="flex items-center gap-1.5 mt-0.5 flex-wrap">
                            <span class="text-[9px] font-bold px-1.5 py-0.5 rounded bg-sky-100 dark:bg-sky-500/15 text-sky-700 dark:text-sky-400">GET</span>
                            <code class="text-[10px] text-gray-500 dark:text-slate-400 font-mono">/ranking</code>
                        </span>
                    </span>
                </button>
                <input type="hidden" name="openapi_ranking_status" :value="enabled ? '1' : '0'">
                <button type="button" @click="enabled = !enabled" :class="enabled ? 'bg-shopee' : 'bg-gray-300 dark:bg-slate-600'"
                    class="relative shrink-0 inline-flex h-6 w-11 items-center rounded-full transition-colors" title="{{ __('Bật/Tắt API này') }}">
                    <span :class="enabled ? 'translate-x-5.5' : 'translate-x-0.5'" class="inline-block h-5 w-5 transform rounded-full bg-white shadow transition-transform"></span>
                </button>
                <button type="button" @click="open = !open" class="shrink-0 text-gray-400 hover:text-shopee transition-colors">
                    <i data-lucide="chevron-down" class="w-5 h-5 transition-transform" :class="open ? 'rotate-180' : ''"></i>
                </button>
            </div>
            <div x-show="open" x-collapse x-cloak class="px-4 pb-4 border-t border-gray-100 dark:border-slate-800 pt-4 space-y-3 text-xs text-gray-600 dark:text-slate-300 leading-relaxed">
                <p>{{ __('Bảng xếp hạng thành viên: top đơn hàng, top tiền hoàn, top điểm danh, top giới thiệu và top số dư (tùy cấu hình admin bật/tắt từng bảng). Tên hiển thị được che một phần để bảo vệ quyền riêng tư. Yêu cầu xác thực token.') }}</p>
                <div class="bg-gray-900 dark:bg-slate-950 rounded-xl p-3.5 font-mono text-[10.5px] text-gray-100 overflow-x-auto shadow-inner select-all leading-relaxed">curl {{ $apiBase }}/ranking \<br>&nbsp;&nbsp;-H "Authorization: Bearer &lt;token&gt;"</div>
                <p class="font-semibold text-gray-700 dark:text-slate-200">{{ __('Phản hồi mẫu:') }}</p>
                <div class="bg-gray-100 dark:bg-slate-950/60 rounded-xl p-3.5 font-mono text-[10.5px] text-gray-700 dark:text-slate-300 overflow-x-auto leading-relaxed">{<br>&nbsp;&nbsp;"success": true,<br>&nbsp;&nbsp;"data": { "top_orders": [ { "name": "N***A", "value": 42 } ], "top_cashback": [...], "top_checkin": [...] }<br>}</div>
            </div>
        </div>

        {{-- =================== 16. API Nhật ký hoạt động =================== --}}
        <div x-data="{ open: false, enabled: {{ ($settings['openapi_activity_logs_status'] ?? '1') === '1' ? 'true' : 'false' }} }"
            class="bg-white dark:bg-slate-800/40 border border-gray-200 dark:border-slate-800 rounded-2xl overflow-hidden">
            <div class="flex items-center gap-3 p-4">
                <button type="button" @click="open = !open" class="flex items-center gap-3 flex-1 text-left min-w-0">
                    <span class="shrink-0 w-9 h-9 rounded-xl bg-shopee/10 text-shopee flex items-center justify-center">
                        <i data-lucide="scroll-text" class="w-4.5 h-4.5"></i>
                    </span>
                    <span class="min-w-0">
                        <span class="block text-sm font-bold text-gray-800 dark:text-slate-200 truncate">{{ __('Nhật ký hoạt động') }}</span>
                        <span class="flex items-center gap-1.5 mt-0.5 flex-wrap">
                            <span class="text-[9px] font-bold px-1.5 py-0.5 rounded bg-sky-100 dark:bg-sky-500/15 text-sky-700 dark:text-sky-400">GET</span>
                            <code class="text-[10px] text-gray-500 dark:text-slate-400 font-mono">/logs</code>
                        </span>
                    </span>
                </button>
                <input type="hidden" name="openapi_activity_logs_status" :value="enabled ? '1' : '0'">
                <button type="button" @click="enabled = !enabled" :class="enabled ? 'bg-shopee' : 'bg-gray-300 dark:bg-slate-600'"
                    class="relative shrink-0 inline-flex h-6 w-11 items-center rounded-full transition-colors" title="{{ __('Bật/Tắt API này') }}">
                    <span :class="enabled ? 'translate-x-5.5' : 'translate-x-0.5'" class="inline-block h-5 w-5 transform rounded-full bg-white shadow transition-transform"></span>
                </button>
                <button type="button" @click="open = !open" class="shrink-0 text-gray-400 hover:text-shopee transition-colors">
                    <i data-lucide="chevron-down" class="w-5 h-5 transition-transform" :class="open ? 'rotate-180' : ''"></i>
                </button>
            </div>
            <div x-show="open" x-collapse x-cloak class="px-4 pb-4 border-t border-gray-100 dark:border-slate-800 pt-4 space-y-3 text-xs text-gray-600 dark:text-slate-300 leading-relaxed">
                <p>{{ __('Lịch sử hoạt động bảo mật của tài khoản (đăng nhập, đổi mật khẩu, điểm danh, đổi quà...). Hỗ trợ tìm kiếm và phân trang. Yêu cầu xác thực token.') }}</p>
                <ul class="list-disc pl-5 space-y-0.5 text-[11px]">
                    <li><code class="font-mono">search</code>, <code class="font-mono">page</code>, <code class="font-mono">per_page</code> {{ __('(tối đa 50).') }}</li>
                </ul>
                <div class="bg-gray-900 dark:bg-slate-950 rounded-xl p-3.5 font-mono text-[10.5px] text-gray-100 overflow-x-auto shadow-inner select-all leading-relaxed">curl "{{ $apiBase }}/logs?per_page=20" \<br>&nbsp;&nbsp;-H "Authorization: Bearer &lt;token&gt;"</div>
                <p class="font-semibold text-gray-700 dark:text-slate-200">{{ __('Phản hồi mẫu:') }}</p>
                <div class="bg-gray-100 dark:bg-slate-950/60 rounded-xl p-3.5 font-mono text-[10.5px] text-gray-700 dark:text-slate-300 overflow-x-auto leading-relaxed">{<br>&nbsp;&nbsp;"success": true,<br>&nbsp;&nbsp;"data": { "items": [ { "id": 1, "activity": "Đăng nhập thành công", "ip_address": "113.161.xx.xx", "user_agent": "...", "created_at": "2026-07-10T09:12:00+07:00" } ], "pagination": {...} }<br>}</div>
            </div>
        </div>

        {{-- =================== 17. API Trang tĩnh =================== --}}
        <div x-data="{ open: false, enabled: {{ ($settings['openapi_pages_status'] ?? '1') === '1' ? 'true' : 'false' }} }"
            class="bg-white dark:bg-slate-800/40 border border-gray-200 dark:border-slate-800 rounded-2xl overflow-hidden">
            <div class="flex items-center gap-3 p-4">
                <button type="button" @click="open = !open" class="flex items-center gap-3 flex-1 text-left min-w-0">
                    <span class="shrink-0 w-9 h-9 rounded-xl bg-shopee/10 text-shopee flex items-center justify-center">
                        <i data-lucide="file-text" class="w-4.5 h-4.5"></i>
                    </span>
                    <span class="min-w-0">
                        <span class="block text-sm font-bold text-gray-800 dark:text-slate-200 truncate">{{ __('Trang tĩnh (Điều khoản, Chính sách)') }}</span>
                        <span class="flex items-center gap-1.5 mt-0.5 flex-wrap">
                            <span class="text-[9px] font-bold px-1.5 py-0.5 rounded bg-sky-100 dark:bg-sky-500/15 text-sky-700 dark:text-sky-400">GET</span>
                            <span class="text-[9px] font-bold px-1.5 py-0.5 rounded bg-gray-100 dark:bg-slate-700 text-gray-500 dark:text-slate-300">{{ __('Công khai') }}</span>
                            <code class="text-[10px] text-gray-500 dark:text-slate-400 font-mono">/pages</code>
                        </span>
                    </span>
                </button>
                <input type="hidden" name="openapi_pages_status" :value="enabled ? '1' : '0'">
                <button type="button" @click="enabled = !enabled" :class="enabled ? 'bg-shopee' : 'bg-gray-300 dark:bg-slate-600'"
                    class="relative shrink-0 inline-flex h-6 w-11 items-center rounded-full transition-colors" title="{{ __('Bật/Tắt API này') }}">
                    <span :class="enabled ? 'translate-x-5.5' : 'translate-x-0.5'" class="inline-block h-5 w-5 transform rounded-full bg-white shadow transition-transform"></span>
                </button>
                <button type="button" @click="open = !open" class="shrink-0 text-gray-400 hover:text-shopee transition-colors">
                    <i data-lucide="chevron-down" class="w-5 h-5 transition-transform" :class="open ? 'rotate-180' : ''"></i>
                </button>
            </div>
            <div x-show="open" x-collapse x-cloak class="px-4 pb-4 border-t border-gray-100 dark:border-slate-800 pt-4 space-y-3 text-xs text-gray-600 dark:text-slate-300 leading-relaxed">
                <p>{{ __('Nội dung các trang tĩnh đã xuất bản (Điều khoản sử dụng, Chính sách bảo mật, Giới thiệu...). Endpoint công khai — cần thiết để hiển thị chính sách bảo mật trong App khi duyệt App Store / Google Play.') }}</p>
                <ul class="list-disc pl-5 space-y-0.5 text-[11px]">
                    <li><code class="font-mono"><span class="text-sky-600 dark:text-sky-400">GET</span> /pages</code> — {{ __('danh sách trang (slug, tiêu đề).') }}</li>
                    <li><code class="font-mono"><span class="text-sky-600 dark:text-sky-400">GET</span> /pages/{slug}</code> — {{ __('nội dung chi tiết một trang.') }}</li>
                </ul>
                <div class="bg-gray-900 dark:bg-slate-950 rounded-xl p-3.5 font-mono text-[10.5px] text-gray-100 overflow-x-auto shadow-inner select-all leading-relaxed">curl {{ $apiBase }}/pages/chinh-sach-bao-mat</div>
                <p class="font-semibold text-gray-700 dark:text-slate-200">{{ __('Phản hồi mẫu:') }}</p>
                <div class="bg-gray-100 dark:bg-slate-950/60 rounded-xl p-3.5 font-mono text-[10.5px] text-gray-700 dark:text-slate-300 overflow-x-auto leading-relaxed">{<br>&nbsp;&nbsp;"success": true,<br>&nbsp;&nbsp;"data": { "slug": "chinh-sach-bao-mat", "title": "Chính sách bảo mật", "content": "&lt;p&gt;...&lt;/p&gt;", "meta_description": "...", "updated_at": "2026-07-10T09:12:00+07:00" }<br>}</div>
            </div>
        </div>

        {{-- =================== 18. API Thông báo đẩy (Push) =================== --}}
        <div x-data="{ open: false, enabled: {{ ($settings['openapi_push_status'] ?? '1') === '1' ? 'true' : 'false' }} }"
            class="bg-white dark:bg-slate-800/40 border border-gray-200 dark:border-slate-800 rounded-2xl overflow-hidden">
            <div class="flex items-center gap-3 p-4">
                <button type="button" @click="open = !open" class="flex items-center gap-3 flex-1 text-left min-w-0">
                    <span class="shrink-0 w-9 h-9 rounded-xl bg-shopee/10 text-shopee flex items-center justify-center">
                        <i data-lucide="bell-ring" class="w-4.5 h-4.5"></i>
                    </span>
                    <span class="min-w-0">
                        <span class="block text-sm font-bold text-gray-800 dark:text-slate-200 truncate">{{ __('Thông báo đẩy (Push Notification)') }}</span>
                        <span class="flex items-center gap-1.5 mt-0.5 flex-wrap">
                            <span class="text-[9px] font-bold px-1.5 py-0.5 rounded bg-sky-100 dark:bg-sky-500/15 text-sky-700 dark:text-sky-400">GET</span>
                            <span class="text-[9px] font-bold px-1.5 py-0.5 rounded bg-emerald-100 dark:bg-emerald-500/15 text-emerald-700 dark:text-emerald-400">POST</span>
                            <code class="text-[10px] text-gray-500 dark:text-slate-400 font-mono">/devices</code>
                        </span>
                    </span>
                </button>
                <input type="hidden" name="openapi_push_status" :value="enabled ? '1' : '0'">
                <button type="button" @click="enabled = !enabled" :class="enabled ? 'bg-shopee' : 'bg-gray-300 dark:bg-slate-600'"
                    class="relative shrink-0 inline-flex h-6 w-11 items-center rounded-full transition-colors" title="{{ __('Bật/Tắt API này') }}">
                    <span :class="enabled ? 'translate-x-5.5' : 'translate-x-0.5'" class="inline-block h-5 w-5 transform rounded-full bg-white shadow transition-transform"></span>
                </button>
                <button type="button" @click="open = !open" class="shrink-0 text-gray-400 hover:text-shopee transition-colors">
                    <i data-lucide="chevron-down" class="w-5 h-5 transition-transform" :class="open ? 'rotate-180' : ''"></i>
                </button>
            </div>
            <div x-show="open" x-collapse x-cloak class="px-4 pb-4 border-t border-gray-100 dark:border-slate-800 pt-4 space-y-3 text-xs text-gray-600 dark:text-slate-300 leading-relaxed">
                <p>{{ __('Đăng ký token thiết bị (FCM/APNs) để nhận thông báo đẩy. App gọi register sau khi đăng nhập và unregister khi đăng xuất. Mỗi khi có thông báo mới trong hệ thống, server tự động gửi push tới các thiết bị đã đăng ký (cần dán Firebase Service Account ở khối Cấu hình App Mobile phía trên).') }}</p>
                <ul class="list-disc pl-5 space-y-0.5 text-[11px]">
                    <li><code class="font-mono"><span class="text-sky-600 dark:text-sky-400">GET</span> /devices</code> — {{ __('danh sách thiết bị đang đăng ký.') }}</li>
                    <li><code class="font-mono"><span class="text-emerald-600 dark:text-emerald-400">POST</span> /devices/register</code> — {{ __('lưu token (body: token, platform, device_name).') }}</li>
                    <li><code class="font-mono"><span class="text-emerald-600 dark:text-emerald-400">POST</span> /devices/unregister</code> — {{ __('hủy token (body: token).') }}</li>
                </ul>
                <div class="bg-gray-900 dark:bg-slate-950 rounded-xl p-3.5 font-mono text-[10.5px] text-gray-100 overflow-x-auto shadow-inner select-all leading-relaxed">curl -X POST {{ $apiBase }}/devices/register \<br>&nbsp;&nbsp;-H "Authorization: Bearer &lt;token&gt;" -H "Content-Type: application/json" \<br>&nbsp;&nbsp;-d '{"token":"&lt;fcm_token&gt;","platform":"android","device_name":"Samsung S23"}'</div>
                <p class="font-semibold text-gray-700 dark:text-slate-200">{{ __('Phản hồi mẫu:') }}</p>
                <div class="bg-gray-100 dark:bg-slate-950/60 rounded-xl p-3.5 font-mono text-[10.5px] text-gray-700 dark:text-slate-300 overflow-x-auto leading-relaxed">{<br>&nbsp;&nbsp;"success": true,<br>&nbsp;&nbsp;"message": "Đăng ký nhận thông báo đẩy thành công.",<br>&nbsp;&nbsp;"data": { "id": 5 }<br>}</div>
            </div>
        </div>

        {{-- =================== 19. API Quản lý bảo mật (2FA/OTP) =================== --}}
        <div x-data="{ open: false, enabled: {{ ($settings['openapi_security_status'] ?? '1') === '1' ? 'true' : 'false' }} }"
            class="bg-white dark:bg-slate-800/40 border border-gray-200 dark:border-slate-800 rounded-2xl overflow-hidden">
            <div class="flex items-center gap-3 p-4">
                <button type="button" @click="open = !open" class="flex items-center gap-3 flex-1 text-left min-w-0">
                    <span class="shrink-0 w-9 h-9 rounded-xl bg-shopee/10 text-shopee flex items-center justify-center">
                        <i data-lucide="shield-check" class="w-4.5 h-4.5"></i>
                    </span>
                    <span class="min-w-0">
                        <span class="block text-sm font-bold text-gray-800 dark:text-slate-200 truncate">{{ __('Quản lý bảo mật (2FA & Email OTP)') }}</span>
                        <span class="flex items-center gap-1.5 mt-0.5 flex-wrap">
                            <span class="text-[9px] font-bold px-1.5 py-0.5 rounded bg-sky-100 dark:bg-sky-500/15 text-sky-700 dark:text-sky-400">GET</span>
                            <span class="text-[9px] font-bold px-1.5 py-0.5 rounded bg-emerald-100 dark:bg-emerald-500/15 text-emerald-700 dark:text-emerald-400">POST</span>
                            <code class="text-[10px] text-gray-500 dark:text-slate-400 font-mono">/security</code>
                        </span>
                    </span>
                </button>
                <input type="hidden" name="openapi_security_status" :value="enabled ? '1' : '0'">
                <button type="button" @click="enabled = !enabled" :class="enabled ? 'bg-shopee' : 'bg-gray-300 dark:bg-slate-600'"
                    class="relative shrink-0 inline-flex h-6 w-11 items-center rounded-full transition-colors" title="{{ __('Bật/Tắt API này') }}">
                    <span :class="enabled ? 'translate-x-5.5' : 'translate-x-0.5'" class="inline-block h-5 w-5 transform rounded-full bg-white shadow transition-transform"></span>
                </button>
                <button type="button" @click="open = !open" class="shrink-0 text-gray-400 hover:text-shopee transition-colors">
                    <i data-lucide="chevron-down" class="w-5 h-5 transition-transform" :class="open ? 'rotate-180' : ''"></i>
                </button>
            </div>
            <div x-show="open" x-collapse x-cloak class="px-4 pb-4 border-t border-gray-100 dark:border-slate-800 pt-4 space-y-3 text-xs text-gray-600 dark:text-slate-300 leading-relaxed">
                <p>{{ __('Cho phép thành viên tự bật/tắt bảo mật 2 lớp trong App: Google Authenticator (2FA) và mã OTP qua Email. Yêu cầu xác thực token.') }}</p>
                <ul class="list-disc pl-5 space-y-0.5 text-[11px]">
                    <li><code class="font-mono"><span class="text-sky-600 dark:text-sky-400">GET</span> /security</code> — {{ __('trạng thái các lớp bảo mật.') }}</li>
                    <li><code class="font-mono"><span class="text-emerald-600 dark:text-emerald-400">POST</span> /security/2fa/setup</code> — {{ __('sinh secret + QR để quét.') }}</li>
                    <li><code class="font-mono"><span class="text-emerald-600 dark:text-emerald-400">POST</span> /security/2fa/enable</code> — {{ __('body: secret, otp_code.') }}</li>
                    <li><code class="font-mono"><span class="text-emerald-600 dark:text-emerald-400">POST</span> /security/2fa/disable</code> — {{ __('body: password, otp_code.') }}</li>
                    <li><code class="font-mono"><span class="text-emerald-600 dark:text-emerald-400">POST</span> /security/email-otp/send · /enable · /disable</code></li>
                </ul>
                <div class="bg-gray-900 dark:bg-slate-950 rounded-xl p-3.5 font-mono text-[10.5px] text-gray-100 overflow-x-auto shadow-inner select-all leading-relaxed">curl -X POST {{ $apiBase }}/security/2fa/setup \<br>&nbsp;&nbsp;-H "Authorization: Bearer &lt;token&gt;"</div>
                <p class="font-semibold text-gray-700 dark:text-slate-200">{{ __('Phản hồi mẫu:') }}</p>
                <div class="bg-gray-100 dark:bg-slate-950/60 rounded-xl p-3.5 font-mono text-[10.5px] text-gray-700 dark:text-slate-300 overflow-x-auto leading-relaxed">{<br>&nbsp;&nbsp;"success": true,<br>&nbsp;&nbsp;"data": { "secret_key": "JBSWY3DPEHPK3PXP", "otpauth_url": "otpauth://totp/HoanTienShopee:...", "qr_code_url": "https://api.qrserver.com/v1/create-qr-code/?size=200x200&amp;data=..." }<br>}</div>
            </div>
        </div>

        {{-- =================== 20. API Quản lý phiên đăng nhập =================== --}}
        <div x-data="{ open: false, enabled: {{ ($settings['openapi_sessions_status'] ?? '1') === '1' ? 'true' : 'false' }} }"
            class="bg-white dark:bg-slate-800/40 border border-gray-200 dark:border-slate-800 rounded-2xl overflow-hidden">
            <div class="flex items-center gap-3 p-4">
                <button type="button" @click="open = !open" class="flex items-center gap-3 flex-1 text-left min-w-0">
                    <span class="shrink-0 w-9 h-9 rounded-xl bg-shopee/10 text-shopee flex items-center justify-center">
                        <i data-lucide="monitor-smartphone" class="w-4.5 h-4.5"></i>
                    </span>
                    <span class="min-w-0">
                        <span class="block text-sm font-bold text-gray-800 dark:text-slate-200 truncate">{{ __('Quản lý phiên đăng nhập') }}</span>
                        <span class="flex items-center gap-1.5 mt-0.5 flex-wrap">
                            <span class="text-[9px] font-bold px-1.5 py-0.5 rounded bg-sky-100 dark:bg-sky-500/15 text-sky-700 dark:text-sky-400">GET</span>
                            <span class="text-[9px] font-bold px-1.5 py-0.5 rounded bg-emerald-100 dark:bg-emerald-500/15 text-emerald-700 dark:text-emerald-400">POST</span>
                            <code class="text-[10px] text-gray-500 dark:text-slate-400 font-mono">/sessions</code>
                        </span>
                    </span>
                </button>
                <input type="hidden" name="openapi_sessions_status" :value="enabled ? '1' : '0'">
                <button type="button" @click="enabled = !enabled" :class="enabled ? 'bg-shopee' : 'bg-gray-300 dark:bg-slate-600'"
                    class="relative shrink-0 inline-flex h-6 w-11 items-center rounded-full transition-colors" title="{{ __('Bật/Tắt API này') }}">
                    <span :class="enabled ? 'translate-x-5.5' : 'translate-x-0.5'" class="inline-block h-5 w-5 transform rounded-full bg-white shadow transition-transform"></span>
                </button>
                <button type="button" @click="open = !open" class="shrink-0 text-gray-400 hover:text-shopee transition-colors">
                    <i data-lucide="chevron-down" class="w-5 h-5 transition-transform" :class="open ? 'rotate-180' : ''"></i>
                </button>
            </div>
            <div x-show="open" x-collapse x-cloak class="px-4 pb-4 border-t border-gray-100 dark:border-slate-800 pt-4 space-y-3 text-xs text-gray-600 dark:text-slate-300 leading-relaxed">
                <p>{{ __('Xem danh sách thiết bị đang đăng nhập (mỗi token là một phiên) và thu hồi từ xa. Yêu cầu xác thực token.') }}</p>
                <ul class="list-disc pl-5 space-y-0.5 text-[11px]">
                    <li><code class="font-mono"><span class="text-sky-600 dark:text-sky-400">GET</span> /sessions</code> — {{ __('danh sách phiên, đánh dấu phiên hiện tại.') }}</li>
                    <li><code class="font-mono"><span class="text-emerald-600 dark:text-emerald-400">POST</span> /sessions/{id}/revoke</code> — {{ __('thu hồi một phiên (không cho thu hồi phiên hiện tại).') }}</li>
                    <li><code class="font-mono"><span class="text-emerald-600 dark:text-emerald-400">POST</span> /sessions/revoke-others</code> — {{ __('đăng xuất tất cả thiết bị khác.') }}</li>
                </ul>
                <div class="bg-gray-900 dark:bg-slate-950 rounded-xl p-3.5 font-mono text-[10.5px] text-gray-100 overflow-x-auto shadow-inner select-all leading-relaxed">curl {{ $apiBase }}/sessions \<br>&nbsp;&nbsp;-H "Authorization: Bearer &lt;token&gt;"</div>
                <p class="font-semibold text-gray-700 dark:text-slate-200">{{ __('Phản hồi mẫu:') }}</p>
                <div class="bg-gray-100 dark:bg-slate-950/60 rounded-xl p-3.5 font-mono text-[10.5px] text-gray-700 dark:text-slate-300 overflow-x-auto leading-relaxed">{<br>&nbsp;&nbsp;"success": true,<br>&nbsp;&nbsp;"data": { "items": [ { "id": 1, "device_name": "Samsung S23", "last_ip": "113.161.xx.xx", "last_used_at": "2026-07-10T09:12:00+07:00", "created_at": "...", "expires_at": null, "is_current": true } ] }<br>}</div>
            </div>
        </div>


    </div>
</div>
