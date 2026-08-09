{{--
    Partial View: Cấu hình khác
    Vai trò: Quản lý các cấu hình phụ, bổ sung và nâng cao bao gồm mã nhúng Custom CSS/JS để chèn vào Header hoặc Footer của trang khách.
--}}
<div x-show="tab === 'other'" class="space-y-4" x-transition x-cloak>
    <div class="bg-gray-50/50 dark:bg-slate-900/50 rounded-2xl p-4 sm:p-6 border border-gray-150 dark:border-slate-800 space-y-6 w-full">
        <h3 class="text-xs font-bold text-gray-700 dark:text-slate-300 uppercase tracking-wider pb-2 border-b border-gray-200/65 dark:border-slate-800 flex items-center gap-1.5">
            <i data-lucide="settings-2" class="w-4 h-4 text-shopee"></i>
            {{ __('Cấu hình khác & Mã nhúng tùy biến') }}
        </h3>

        <div class="p-3 bg-blue-50 border border-blue-100 text-[10px] text-blue-800 rounded-2xl dark:bg-blue-950/30 dark:border-blue-800/50 dark:text-blue-300">
            <h4 class="font-bold flex items-center gap-1"><i data-lucide="info" class="w-4 h-4"></i> {{ __('Tích hợp mã nhúng và Scripts:') }}</h4>
            <p class="mt-1">{{ __('Sử dụng các thiết lập dưới đây để chèn các mã script theo dõi (Google Analytics, Facebook Pixel, chat widget) hoặc mã CSS tùy biến vào trang chủ và trang thành viên của khách hàng mà không cần chỉnh sửa trực tiếp mã nguồn của hệ thống.') }}</p>
        </div>

        <!-- Cấu hình Chế độ Debug hệ thống (APP_DEBUG) -->
        <div class="space-y-2">
            <label for="app_debug" class="block text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider mb-1">
                {{ __('Chế độ Debug hệ thống (APP_DEBUG)') }}
            </label>
            <div class="relative w-48">
                <select name="app_debug"
                    id="app_debug"
                    class="block w-full px-4 py-3 border border-gray-200 dark:border-slate-800 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-300">
                    <option value="1" {{ ($settings['app_debug'] ?? (config('app.debug') ? '1' : '0')) === '1' ? 'selected' : '' }}>{{ __('Bật (Hiển thị chi tiết lỗi)') }}</option>
                    <option value="0" {{ ($settings['app_debug'] ?? (config('app.debug') ? '1' : '0')) === '0' ? 'selected' : '' }}>{{ __('Tắt (Ẩn chi tiết lỗi)') }}</option>
                </select>
            </div>
            <span class="text-[9px] text-gray-400 mt-1 block">{{ __('Bật để hiển thị thông tin lỗi chi tiết lỗi khi lập trình, tắt đi khi chạy thực tế (production) để bảo mật hệ thống.') }}</span>
        </div>

        <div class="border-t border-gray-200/65 dark:border-slate-800/80 my-5"></div>

        <!-- Cấu hình Đăng ký thành viên mới -->
        <div class="space-y-2">
            <label for="registration_enabled" class="block text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider mb-1">
                {{ __('Cho phép đăng ký tài khoản mới') }}
            </label>
            <div class="relative w-48">
                <select name="registration_enabled"
                    id="registration_enabled"
                    class="block w-full px-4 py-3 border border-gray-200 dark:border-slate-800 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-300">
                    <option value="1" {{ ($settings['registration_enabled'] ?? '1') === '1' ? 'selected' : '' }}>{{ __('Bật (Cho phép)') }}</option>
                    <option value="0" {{ ($settings['registration_enabled'] ?? '1') === '0' ? 'selected' : '' }}>{{ __('Tắt (Tạm ngưng)') }}</option>
                </select>
            </div>
            <span class="text-[9px] text-gray-400 mt-1 block">{{ __('Tùy chọn bật hoặc tắt chức năng đăng ký thành viên mới trên toàn bộ hệ thống.') }}</span>
        </div>

        <div class="border-t border-gray-200/65 dark:border-slate-800/80 my-5"></div>

        <!-- Cấu hình Hiển thị form đăng nhập/đăng ký bằng Email & Mật khẩu -->
        <div class="space-y-2">
            <label for="email_auth_enabled" class="block text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider mb-1">
                {{ __('Hiển thị form đăng nhập/đăng ký bằng Email & Mật khẩu') }}
            </label>
            <div class="relative w-48">
                <select name="email_auth_enabled"
                    id="email_auth_enabled"
                    class="block w-full px-4 py-3 border border-gray-200 dark:border-slate-800 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-300">
                    <option value="1" {{ ($settings['email_auth_enabled'] ?? '1') === '1' ? 'selected' : '' }}>{{ __('Bật (Hiển thị)') }}</option>
                    <option value="0" {{ ($settings['email_auth_enabled'] ?? '1') === '0' ? 'selected' : '' }}>{{ __('Tắt (Ẩn - Chỉ dùng Google)') }}</option>
                </select>
            </div>
            <span class="text-[9px] text-gray-400 mt-1 block">{{ __('Khi tắt, hệ thống sẽ ẩn form nhập Email/Mật khẩu ở trang đăng nhập và đăng ký, chỉ cho phép người dùng đăng nhập/đăng ký bằng Google. Lưu ý: bắt buộc phải bật "Đăng nhập bằng Google" ở tab Kết nối, nếu không form Email/Mật khẩu vẫn được hiển thị để tránh khóa toàn bộ hệ thống đăng nhập.') }}</span>
        </div>

        <div class="border-t border-gray-200/65 dark:border-slate-800/80 my-5"></div>

        <!-- Cấu hình Bắt buộc xác minh Email khi đăng ký -->
        <div class="space-y-2">
            <label for="email_verification_enabled" class="block text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider mb-1">
                {{ __('Bắt buộc xác minh Email khi đăng ký') }}
            </label>
            <div class="relative w-48">
                <select name="email_verification_enabled"
                    id="email_verification_enabled"
                    class="block w-full px-4 py-3 border border-gray-200 dark:border-slate-800 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-300">
                    <option value="1" {{ ($settings['email_verification_enabled'] ?? '0') === '1' ? 'selected' : '' }}>{{ __('Bật (Bắt buộc)') }}</option>
                    <option value="0" {{ ($settings['email_verification_enabled'] ?? '0') === '0' ? 'selected' : '' }}>{{ __('Tắt (Không bắt buộc)') }}</option>
                </select>
            </div>
            <span class="text-[9px] text-gray-400 mt-1 block">{{ __('Nếu bật, người dùng đăng ký tài khoản mới bắt buộc phải kích hoạt tài khoản bằng mã OTP gửi qua Email.') }}</span>
        </div>

        <div class="border-t border-gray-200/65 dark:border-slate-800/80 my-5"></div>

        <!-- Cấu hình Cho phép thành viên tự xóa tài khoản -->
        <div class="space-y-2">
            <label for="allow_self_delete_account" class="block text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider mb-1">
                {{ __('Cho phép thành viên tự xóa tài khoản') }}
            </label>
            <div class="relative w-48">
                <select name="allow_self_delete_account"
                    id="allow_self_delete_account"
                    class="block w-full px-4 py-3 border border-gray-200 dark:border-slate-800 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-300">
                    <option value="1" {{ ($settings['allow_self_delete_account'] ?? '0') === '1' ? 'selected' : '' }}>{{ __('Bật (Cho phép)') }}</option>
                    <option value="0" {{ ($settings['allow_self_delete_account'] ?? '0') === '0' ? 'selected' : '' }}>{{ __('Tắt (Không cho phép)') }}</option>
                </select>
            </div>
            <span class="text-[9px] text-gray-400 mt-1 block">{{ __('Nếu bật, thành viên có thể tự xóa vĩnh viễn tài khoản của mình trong trang hồ sơ. Hệ thống yêu cầu nhập mật khẩu và phải rút hết số dư trước khi xóa.') }}</span>
        </div>

        <div class="border-t border-gray-200/65 dark:border-slate-800/80 my-5"></div>

        <!-- Cấu hình hiển thị Tài liệu API cho thành viên -->
        <div class="space-y-2">
            <label for="api_docs_enabled" class="block text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider mb-1">
                {{ __('Tài liệu API') }}
            </label>
            <div class="relative w-48">
                <select name="api_docs_enabled"
                    id="api_docs_enabled"
                    class="block w-full px-4 py-3 border border-gray-200 dark:border-slate-800 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-300">
                    <option value="1" {{ ($settings['api_docs_enabled'] ?? '1') === '1' ? 'selected' : '' }}>{{ __('Bật (Hiển thị)') }}</option>
                    <option value="0" {{ ($settings['api_docs_enabled'] ?? '1') === '0' ? 'selected' : '' }}>{{ __('Tắt (Ẩn)') }}</option>
                </select>
            </div>
            <span class="text-[9px] text-gray-400 mt-1 block">{{ __('Khi bật, thành viên sẽ thấy nút "Xem tài liệu API" ngay dưới phần Khóa API Token (API Key) trong trang Hồ sơ, mở ra tài liệu hướng dẫn tích hợp API tạo link hoàn tiền và API tra cứu đơn hàng đã ghi nhận. Đây cũng là công tắc bật/tắt chính các endpoint đó — khi tắt, mọi yêu cầu gửi tới sẽ nhận mã lỗi 503.') }}</span>

            <div class="mt-2 p-3 bg-blue-50 border border-blue-100 text-[10px] text-blue-800 rounded-2xl dark:bg-blue-950/30 dark:border-blue-800/50 dark:text-blue-300 flex items-start gap-1.5">
                <i data-lucide="info" class="w-3.5 h-3.5 shrink-0 mt-px"></i>
                <span>
                    {{ __('Nhóm API này (tiền tố /api/v1/bot) hoạt động ĐỘC LẬP với hệ thống Open API: dành cho Bot / hệ thống bên thứ ba của thành viên, xác thực bằng Khóa API Token cá nhân. Việc bật hay tắt Open API hoàn toàn không ảnh hưởng tới nhóm API này và ngược lại.') }}
                    <a href="{{ route('admin.settings.index', ['tab' => 'open_api']) }}" class="font-bold underline hover:text-shopee">{{ __('Xem tab Open API') }}</a>
                </span>
            </div>
        </div>

        <div class="border-t border-gray-200/65 dark:border-slate-800/80 my-5"></div>

        <!-- Cấu hình hiển thị các link đã tạo nhưng sàn chưa ghi nhận đơn tại trang Lịch sử hoàn tiền -->
        <div class="space-y-2">
            <label for="cashback_show_pending_clicks" class="block text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider mb-1">
                {{ __('Hiển thị đơn chưa ghi nhận') }}
            </label>
            <div class="relative w-48">
                <select name="cashback_show_pending_clicks"
                    id="cashback_show_pending_clicks"
                    class="block w-full px-4 py-3 border border-gray-200 dark:border-slate-800 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-300">
                    <option value="1" {{ ($settings['cashback_show_pending_clicks'] ?? '1') === '1' ? 'selected' : '' }}>{{ __('Bật (Hiển thị)') }}</option>
                    <option value="0" {{ ($settings['cashback_show_pending_clicks'] ?? '1') === '0' ? 'selected' : '' }}>{{ __('Tắt (Ẩn)') }}</option>
                </select>
            </div>
            <span class="text-[9px] text-gray-400 mt-1 block">{{ __('Khi bật, trang Lịch sử hoàn tiền của thành viên sẽ hiển thị gộp thêm các sản phẩm vừa tạo link mua hàng nhưng sàn chưa đối soát xong đơn, kèm nhãn "Chờ sàn ghi nhận" và chip lọc riêng. Tính năng này giúp khách hàng thấy ngay giao dịch của mình thay vì nhìn thấy danh sách trống trong lúc chờ sàn trả dữ liệu về. Số tiền hoàn ở nhóm này chỉ là ước tính và không được cộng vào các thống kê tiền hoàn.') }}</span>
        </div>

        <div class="border-t border-gray-200/65 dark:border-slate-800/80 my-5"></div>

        <!-- Cấu hình các trường dữ liệu hiển thị trong form đăng ký -->
        <div class="space-y-4">
            <h4 class="text-xs font-bold text-gray-700 dark:text-slate-300 uppercase tracking-wider pb-2 border-b border-gray-200/65 dark:border-slate-800 flex items-center gap-1.5">
                <i data-lucide="list-checks" class="w-4 h-4 text-shopee"></i>
                {{ __('Tùy chỉnh trường dữ liệu khi đăng ký') }}
            </h4>
            <p class="text-[10px] text-gray-500 dark:text-slate-400 leading-relaxed -mt-1">
                {{ __('Cấu hình các trường thông tin hiển thị trong form đăng ký thành viên mới. Trường Mật khẩu là bắt buộc và không thể tắt.') }}
            </p>

            <!-- Phương thức định danh tài khoản khi đăng ký (Email và/hoặc Số điện thoại) -->
            <div class="bg-white dark:bg-slate-900 rounded-2xl border border-gray-100 dark:border-slate-800/50 p-5 shadow-sm space-y-4"
                x-data="{
                    identifierEmail: {{ ($settings['register_identifier_email'] ?? '1') === '1' ? 'true' : 'false' }},
                    identifierPhone: {{ ($settings['register_identifier_phone'] ?? '0') === '1' ? 'true' : 'false' }},
                    warning: false,
                    /* Luôn phải còn ít nhất một phương thức định danh được bật, nếu không thành viên sẽ không thể đăng ký */
                    ensureOne(field) {
                        if (!this.identifierEmail && !this.identifierPhone) {
                            if (field === 'email') { this.identifierPhone = true; } else { this.identifierEmail = true; }
                            this.warning = true;
                            setTimeout(() => this.warning = false, 4000);
                        }
                    }
                }">
                <div class="flex items-center gap-2">
                    <i data-lucide="fingerprint" class="w-4 h-4 text-shopee"></i>
                    <h5 class="text-[11px] font-bold text-gray-800 dark:text-slate-200 uppercase tracking-wider">{{ __('Phương thức định danh tài khoản') }}</h5>
                </div>
                <p class="text-[10px] text-gray-500 dark:text-slate-400 leading-relaxed">
                    {{ __('Chọn thông tin dùng để định danh (đăng ký & đăng nhập) tài khoản thành viên. Nếu bật cả hai, form đăng ký sẽ hiển thị 2 lựa chọn Email hoặc Số điện thoại cho thành viên tự chọn. Trang đăng nhập luôn dùng chung một ô nhập cho cả Email lẫn Số điện thoại.') }}
                </p>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <!-- Định danh bằng Email -->
                    <div>
                        <input type="hidden" name="register_identifier_email" value="0">
                        <label class="flex items-start gap-3 p-3.5 rounded-2xl border cursor-pointer transition-all select-none"
                            :class="identifierEmail ? 'border-shopee/40 bg-shopee/5 dark:bg-shopee/10' : 'border-gray-150 dark:border-slate-800 bg-gray-50/50 dark:bg-slate-800/30'">
                            <input type="checkbox" name="register_identifier_email" value="1" {{ ($settings['register_identifier_email'] ?? '1') === '1' ? 'checked' : '' }} x-model="identifierEmail" @change="ensureOne('email')" class="sr-only peer">
                            <span class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0 transition-colors"
                                :class="identifierEmail ? 'bg-shopee/15 text-shopee' : 'bg-gray-100 text-gray-400 dark:bg-slate-800 dark:text-slate-500'">
                                <i data-lucide="mail" class="w-4 h-4"></i>
                            </span>
                            <span class="flex-1 min-w-0">
                                <span class="flex items-center justify-between gap-2">
                                    <span class="text-xs font-bold text-gray-900 dark:text-white">{{ __('Đăng ký bằng Email') }}</span>
                                    <span class="w-9 h-5 rounded-full relative transition-colors shrink-0" :class="identifierEmail ? 'bg-shopee' : 'bg-gray-200 dark:bg-slate-700'">
                                        <span class="absolute top-[2px] left-[2px] w-4 h-4 bg-white rounded-full transition-transform" :class="identifierEmail ? 'translate-x-4' : ''"></span>
                                    </span>
                                </span>
                                <span class="block text-[10px] text-gray-500 dark:text-slate-400 mt-1 leading-relaxed">{{ __('Thành viên dùng địa chỉ email làm tài khoản. Hỗ trợ đầy đủ xác minh email và khôi phục mật khẩu.') }}</span>
                            </span>
                        </label>
                    </div>

                    <!-- Định danh bằng Số điện thoại -->
                    <div>
                        <input type="hidden" name="register_identifier_phone" value="0">
                        <label class="flex items-start gap-3 p-3.5 rounded-2xl border cursor-pointer transition-all select-none"
                            :class="identifierPhone ? 'border-shopee/40 bg-shopee/5 dark:bg-shopee/10' : 'border-gray-150 dark:border-slate-800 bg-gray-50/50 dark:bg-slate-800/30'">
                            <input type="checkbox" name="register_identifier_phone" value="1" {{ ($settings['register_identifier_phone'] ?? '0') === '1' ? 'checked' : '' }} x-model="identifierPhone" @change="ensureOne('phone')" class="sr-only peer">
                            <span class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0 transition-colors"
                                :class="identifierPhone ? 'bg-shopee/15 text-shopee' : 'bg-gray-100 text-gray-400 dark:bg-slate-800 dark:text-slate-500'">
                                <i data-lucide="smartphone" class="w-4 h-4"></i>
                            </span>
                            <span class="flex-1 min-w-0">
                                <span class="flex items-center justify-between gap-2">
                                    <span class="text-xs font-bold text-gray-900 dark:text-white">{{ __('Đăng ký bằng Số điện thoại') }}</span>
                                    <span class="w-9 h-5 rounded-full relative transition-colors shrink-0" :class="identifierPhone ? 'bg-shopee' : 'bg-gray-200 dark:bg-slate-700'">
                                        <span class="absolute top-[2px] left-[2px] w-4 h-4 bg-white rounded-full transition-transform" :class="identifierPhone ? 'translate-x-4' : ''"></span>
                                    </span>
                                </span>
                                <span class="block text-[10px] text-gray-500 dark:text-slate-400 mt-1 leading-relaxed">{{ __('Thành viên dùng số điện thoại làm tài khoản, không cần email. Hệ thống sẽ nhắc họ bổ sung email sau để bảo mật.') }}</span>
                            </span>
                        </label>
                    </div>
                </div>

                <!-- Cảnh báo khi admin cố tắt cả hai phương thức định danh -->
                <div x-show="warning" x-cloak class="p-3 bg-red-50 border border-red-100 text-[10px] text-red-700 rounded-2xl dark:bg-red-950/30 dark:border-red-900/40 dark:text-red-300 flex items-start gap-1.5">
                    <i data-lucide="alert-triangle" class="w-3.5 h-3.5 shrink-0 mt-px"></i>
                    <span>{{ __('Phải giữ lại ít nhất một phương thức định danh, nếu không thành viên mới sẽ không thể đăng ký tài khoản.') }}</span>
                </div>

                <!-- Ghi chú nghiệp vụ khi bật đăng ký bằng số điện thoại -->
                <div x-show="identifierPhone" x-cloak class="p-3 bg-amber-50 border border-amber-100 text-[10px] text-amber-800 rounded-2xl dark:bg-amber-950/30 dark:border-amber-900/40 dark:text-amber-300 flex items-start gap-1.5">
                    <i data-lucide="info" class="w-3.5 h-3.5 shrink-0 mt-px"></i>
                    <span>{{ __('Tài khoản đăng ký bằng Số điện thoại sẽ chưa có email nên không nhận được email thông báo, không dùng được chức năng Quên mật khẩu và bảo mật OTP qua Email. Hệ thống hiển thị một thông báo nhỏ (không bắt buộc) trên website để mời họ bổ sung email trong trang Hồ sơ.') }}</span>
                </div>
            </div>

            <!-- Bảng cấu hình trường đăng ký -->
            <div class="bg-white dark:bg-slate-900 rounded-2xl border border-gray-100 dark:border-slate-800/50 overflow-x-auto shadow-sm">
                <table class="w-full text-left border-collapse text-xs whitespace-nowrap">
                    <thead>
                        <tr class="bg-gray-50/70 border-b border-gray-100 dark:bg-slate-800/40 dark:border-slate-800 text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            <th class="px-5 py-3">{{ __('Trường dữ liệu') }}</th>
                            <th class="px-5 py-3 text-center">{{ __('Hiển thị') }}</th>
                            <th class="px-5 py-3 text-center">{{ __('Bắt buộc') }}</th>
                            <th class="px-5 py-3">{{ __('Ghi chú') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-slate-800/50">
                        <!-- Trường: Họ và tên -->
                        <tr class="hover:bg-gray-50/50 dark:hover:bg-slate-800/20 transition-all"
                            x-data="{ nameVisible: {{ ($settings['register_field_name'] ?? '1') === '1' ? 'true' : 'false' }} }">
                            <td class="px-5 py-3.5 font-bold text-gray-900 dark:text-white whitespace-nowrap">
                                <span class="inline-flex items-center gap-1.5">
                                    <i data-lucide="user" class="w-3.5 h-3.5 text-gray-400"></i>
                                    {{ __('Họ và tên') }}
                                </span>
                            </td>
                            <td class="px-5 py-3.5 text-center">
                                <input type="hidden" name="register_field_name" value="0">
                                <label class="relative inline-flex items-center cursor-pointer select-none">
                                    <input type="checkbox" name="register_field_name" value="1" {{ ($settings['register_field_name'] ?? '1') === '1' ? 'checked' : '' }} class="sr-only peer" @change="nameVisible = $el.checked">
                                    <div class="w-9 h-5 bg-gray-200 peer-focus:outline-none rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all dark:border-slate-600 peer-checked:bg-shopee"></div>
                                </label>
                            </td>
                            <td class="px-5 py-3.5 text-center">
                                <div :class="!nameVisible ? 'opacity-40 pointer-events-none' : ''" class="transition-opacity">
                                    <input type="hidden" name="register_field_name_required" value="0">
                                    <label class="relative inline-flex items-center cursor-pointer select-none">
                                        <input type="checkbox" name="register_field_name_required" value="1" {{ ($settings['register_field_name_required'] ?? '1') === '1' ? 'checked' : '' }} class="sr-only peer" :disabled="!nameVisible">
                                        <div class="w-9 h-5 bg-gray-200 peer-focus:outline-none rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all dark:border-slate-600 peer-checked:bg-blue-500"></div>
                                    </label>
                                </div>
                            </td>
                            <td class="px-5 py-3.5 text-gray-500 dark:text-gray-400">
                                {{ __('Tên hiển thị của thành viên trong hệ thống') }}
                            </td>
                        </tr>

                        <!-- Trường: Số điện thoại -->
                        <tr class="hover:bg-gray-50/50 dark:hover:bg-slate-800/20 transition-all"
                            x-data="{ phoneVisible: {{ ($settings['register_field_phone'] ?? '1') === '1' ? 'true' : 'false' }} }">
                            <td class="px-5 py-3.5 font-bold text-gray-900 dark:text-white whitespace-nowrap">
                                <span class="inline-flex items-center gap-1.5">
                                    <i data-lucide="phone" class="w-3.5 h-3.5 text-gray-400"></i>
                                    {{ __('Số điện thoại') }}
                                </span>
                            </td>
                            <td class="px-5 py-3.5 text-center">
                                <input type="hidden" name="register_field_phone" value="0">
                                <label class="relative inline-flex items-center cursor-pointer select-none">
                                    <input type="checkbox" name="register_field_phone" value="1" {{ ($settings['register_field_phone'] ?? '1') === '1' ? 'checked' : '' }} class="sr-only peer" @change="phoneVisible = $el.checked">
                                    <div class="w-9 h-5 bg-gray-200 peer-focus:outline-none rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all dark:border-slate-600 peer-checked:bg-shopee"></div>
                                </label>
                            </td>
                            <td class="px-5 py-3.5 text-center">
                                <div :class="!phoneVisible ? 'opacity-40 pointer-events-none' : ''" class="transition-opacity">
                                    <input type="hidden" name="register_field_phone_required" value="0">
                                    <label class="relative inline-flex items-center cursor-pointer select-none">
                                        <input type="checkbox" name="register_field_phone_required" value="1" {{ ($settings['register_field_phone_required'] ?? '0') === '1' ? 'checked' : '' }} class="sr-only peer" :disabled="!phoneVisible">
                                        <div class="w-9 h-5 bg-gray-200 peer-focus:outline-none rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all dark:border-slate-600 peer-checked:bg-blue-500"></div>
                                    </label>
                                </div>
                            </td>
                            <td class="px-5 py-3.5 text-gray-500 dark:text-gray-400">
                                {{ __('Số điện thoại liên hệ khi đăng ký bằng Email') }}
                            </td>
                        </tr>

                        <!-- Trường: Đồng ý điều khoản -->
                        <tr class="hover:bg-gray-50/50 dark:hover:bg-slate-800/20 transition-all">
                            <td class="px-5 py-3.5 font-bold text-gray-900 dark:text-white whitespace-nowrap">
                                <span class="inline-flex items-center gap-1.5">
                                    <i data-lucide="shield-check" class="w-3.5 h-3.5 text-gray-400"></i>
                                    {{ __('Đồng ý điều khoản') }}
                                </span>
                            </td>
                            <td class="px-5 py-3.5 text-center">
                                <input type="hidden" name="register_field_terms" value="0">
                                <label class="relative inline-flex items-center cursor-pointer select-none">
                                    <input type="checkbox" name="register_field_terms" value="1" {{ ($settings['register_field_terms'] ?? '1') === '1' ? 'checked' : '' }} class="sr-only peer">
                                    <div class="w-9 h-5 bg-gray-200 peer-focus:outline-none rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all dark:border-slate-600 peer-checked:bg-shopee"></div>
                                </label>
                            </td>
                            <td class="px-5 py-3.5 text-center">
                                <span class="text-[10px] text-gray-400 italic">{{ __('Luôn bắt buộc') }}</span>
                            </td>
                            <td class="px-5 py-3.5 text-gray-500 dark:text-gray-400">
                                {{ __('Checkbox yêu cầu đồng ý Điều khoản & Chính sách') }}
                            </td>
                        </tr>

                        <!-- Trường cố định: Email (điều khiển bởi phần Phương thức định danh tài khoản phía trên) -->
                        <tr class="bg-gray-50/30 dark:bg-slate-800/10">
                            <td class="px-5 py-3.5 font-bold text-gray-500 dark:text-slate-400 whitespace-nowrap">
                                <span class="inline-flex items-center gap-1.5">
                                    <i data-lucide="mail" class="w-3.5 h-3.5 text-gray-300"></i>
                                    {{ __('Email') }}
                                </span>
                            </td>
                            <td class="px-5 py-3.5 text-center">
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[9px] font-bold bg-green-50 text-green-600 border border-green-100 dark:bg-green-950/20 dark:text-green-400 dark:border-green-900/30">{{ __('Theo định danh') }}</span>
                            </td>
                            <td class="px-5 py-3.5 text-center">
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[9px] font-bold bg-blue-50 text-blue-600 border border-blue-100 dark:bg-blue-950/20 dark:text-blue-400 dark:border-blue-900/30">{{ __('Bắt buộc') }}</span>
                            </td>
                            <td class="px-5 py-3.5 text-gray-400 dark:text-slate-500 italic">
                                {{ __('Bắt buộc khi thành viên chọn đăng ký bằng Email') }}
                            </td>
                        </tr>

                        <!-- Trường cố định: Mật khẩu (không cho tắt) -->
                        <tr class="bg-gray-50/30 dark:bg-slate-800/10">
                            <td class="px-5 py-3.5 font-bold text-gray-500 dark:text-slate-400 whitespace-nowrap">
                                <span class="inline-flex items-center gap-1.5">
                                    <i data-lucide="lock" class="w-3.5 h-3.5 text-gray-300"></i>
                                    {{ __('Mật khẩu') }}
                                </span>
                            </td>
                            <td class="px-5 py-3.5 text-center">
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[9px] font-bold bg-green-50 text-green-600 border border-green-100 dark:bg-green-950/20 dark:text-green-400 dark:border-green-900/30">{{ __('Luôn bật') }}</span>
                            </td>
                            <td class="px-5 py-3.5 text-center">
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[9px] font-bold bg-blue-50 text-blue-600 border border-blue-100 dark:bg-blue-950/20 dark:text-blue-400 dark:border-blue-900/30">{{ __('Bắt buộc') }}</span>
                            </td>
                            <td class="px-5 py-3.5 text-gray-400 dark:text-slate-500 italic">
                                {{ __('Trường bắt buộc, không thể tắt') }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="border-t border-gray-200/65 dark:border-slate-800/80 my-5"></div>

        @php
        // Ràng buộc bảo mật (Stored XSS): Kiểm tra xem người dùng đăng nhập hiện tại có quyền chỉnh sửa mã nhúng tùy biến hay không
        $hasCustomCodePermission = auth()->user()->hasPermission('manage_custom_code');
        @endphp

        <!-- 1. Custom CSS -->
        <div class="space-y-2">
            <label for="custom_css" class="block text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider mb-1">
                {{ __('Custom CSS (Mã CSS tùy biến)') }}
            </label>
            <textarea name="custom_css"
                id="custom_css"
                rows="5"
                @if(!$hasCustomCodePermission) disabled readonly @endif
                placeholder="{{ !$hasCustomCodePermission ? __('Bạn không có quyền chỉnh sửa mã CSS tùy biến.') : __('/* Nhập mã CSS của bạn vào đây. Ví dụ: body { background-color: #f3f4f6; } */') }}"
                class="block w-full px-4 py-3 border border-gray-200 dark:border-slate-800 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-300 font-mono @if(!$hasCustomCodePermission) opacity-60 bg-gray-100 dark:bg-slate-900 cursor-not-allowed @endif">{{ $settings['custom_css'] ?? '' }}</textarea>
            <span class="text-[9px] text-gray-400 mt-1 block">{{ __('Mã CSS sẽ được tự động đưa vào thẻ <style> ở phần Header của trang khách.') }}</span>
        </div>

        <div class="border-t border-gray-200/65 dark:border-slate-800/80 my-5"></div>

        <!-- 2. Custom JS Header -->
        <div class="space-y-2">
            <label for="custom_js_header" class="block text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider mb-1">
                {{ __('Mã nhúng Header (Trước thẻ </head>)') }}
            </label>
            <textarea name="custom_js_header"
                id="custom_js_header"
                rows="6"
                @if(!$hasCustomCodePermission) disabled readonly @endif
                placeholder="{{ !$hasCustomCodePermission ? __('Bạn không có quyền chỉnh sửa mã nhúng Header.') : __('<!-- Nhập mã nhúng JS hoặc Meta tag theo dõi vào đây. Ví dụ: Google Tag Manager, Verification Tags -->') }}"
                class="block w-full px-4 py-3 border border-gray-200 dark:border-slate-800 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-300 font-mono @if(!$hasCustomCodePermission) opacity-60 bg-gray-100 dark:bg-slate-900 cursor-not-allowed @endif">{{ $settings['custom_js_header'] ?? '' }}</textarea>
            <span class="text-[9px] text-gray-400 mt-1 block">{{ __('Mã nhúng này sẽ được chèn vào trước thẻ đóng </head> trên toàn bộ các trang giao diện người dùng.') }}</span>
        </div>

        <div class="border-t border-gray-200/65 dark:border-slate-800/80 my-5"></div>

        <!-- 3. Custom JS Footer -->
        <div class="space-y-2">
            <label for="custom_js_body" class="block text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider mb-1">
                {{ __('Mã nhúng Footer (Trước thẻ </body>)') }}
            </label>
            <textarea name="custom_js_body"
                id="custom_js_body"
                rows="6"
                @if(!$hasCustomCodePermission) disabled readonly @endif
                placeholder="{{ !$hasCustomCodePermission ? __('Bạn không có quyền chỉnh sửa mã nhúng Footer.') : __('<!-- Nhập mã nhúng Chat widget, Popup, scripts theo dõi vào đây. Ví dụ: Subiz Chat, Facebook Chat Messenger -->') }}"
                class="block w-full px-4 py-3 border border-gray-200 dark:border-slate-800 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-300 font-mono @if(!$hasCustomCodePermission) opacity-60 bg-gray-100 dark:bg-slate-900 cursor-not-allowed @endif">{{ $settings['custom_js_body'] ?? '' }}</textarea>
            <span class="text-[9px] text-gray-400 mt-1 block">{{ __('Mã nhúng này sẽ được chèn vào trước thẻ đóng </body> trên toàn bộ các trang giao diện người dùng.') }}</span>
        </div>
    </div>
</div>