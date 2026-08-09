{{-- 
    Partial View: Mẫu email
    Vai trò: Quản lý giao diện soạn thảo các mẫu email (Quên mật khẩu, Xác minh OTP, Chào mừng đăng ký, Tạo lệnh rút tiền, Duyệt lệnh rút tiền) thông qua TinyMCE và hỗ trợ chức năng Xem trước (Preview) email thực tế cũng như gửi thư thử nghiệm (Axios).
--}}
<div x-show="tab === 'email_templates'" class="space-y-6" x-transition x-cloak>
    <div class="p-3 bg-orange-50 border border-orange-100 text-[10px] text-orange-800 rounded-2xl dark:bg-orange-950/30 dark:border-orange-800/50 dark:text-orange-300">
        <h4 class="font-bold flex items-center gap-1"><i data-lucide="info" class="w-4 h-4"></i> Hướng dẫn cấu hình Email Template:</h4>
        <p class="mt-1">Sử dụng các thẻ biến động dạng <code>{tên_biến}</code> để tự động chèn thông tin tương ứng khi gửi mail. Hãy giữ nguyên định dạng của chúng để hệ thống hoạt động ổn định.</p>
    </div>

    <div class="space-y-6" x-data="{ activeEmail: 'forgot_password' }">
        <!-- Email Selector Tab Buttons -->
        <div class="flex flex-wrap gap-2 border-b border-gray-150 pb-3">
            <button type="button" @click="activeEmail = 'forgot_password'"
                :class="activeEmail === 'forgot_password' ? 'bg-shopee text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                class="px-4 py-2 rounded-xl text-xs font-bold transition-all focus:outline-none shadow-sm cursor-pointer">
                Quên mật khẩu
            </button>
            <button type="button" @click="activeEmail = 'otp'"
                :class="activeEmail === 'otp' ? 'bg-shopee text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                class="px-4 py-2 rounded-xl text-xs font-bold transition-all focus:outline-none shadow-sm cursor-pointer">
                Xác minh OTP
            </button>
            <button type="button" @click="activeEmail = 'verify_email'"
                :class="activeEmail === 'verify_email' ? 'bg-shopee text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                class="px-4 py-2 rounded-xl text-xs font-bold transition-all focus:outline-none shadow-sm cursor-pointer">
                Xác minh Email
            </button>
            <button type="button" @click="activeEmail = 'welcome'"
                :class="activeEmail === 'welcome' ? 'bg-shopee text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                class="px-4 py-2 rounded-xl text-xs font-bold transition-all focus:outline-none shadow-sm cursor-pointer">
                Chào mừng đăng ký
            </button>
            <button type="button" @click="activeEmail = 'withdrawal_created'"
                :class="activeEmail === 'withdrawal_created' ? 'bg-shopee text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                class="px-4 py-2 rounded-xl text-xs font-bold transition-all focus:outline-none shadow-sm cursor-pointer">
                Tạo lệnh rút tiền
            </button>
            <button type="button" @click="activeEmail = 'withdrawal_approved'"
                :class="activeEmail === 'withdrawal_approved' ? 'bg-shopee text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                class="px-4 py-2 rounded-xl text-xs font-bold transition-all focus:outline-none shadow-sm cursor-pointer">
                Duyệt lệnh rút tiền
            </button>
            <button type="button" @click="activeEmail = 'gift_approved'"
                :class="activeEmail === 'gift_approved' ? 'bg-shopee text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                class="px-4 py-2 rounded-xl text-xs font-bold transition-all focus:outline-none shadow-sm cursor-pointer">
                Duyệt đơn đổi quà
            </button>
            <button type="button" @click="activeEmail = 'gift_created'"
                :class="activeEmail === 'gift_created' ? 'bg-shopee text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                class="px-4 py-2 rounded-xl text-xs font-bold transition-all focus:outline-none shadow-sm cursor-pointer">
                Tạo đơn đổi quà
            </button>
            <button type="button" @click="activeEmail = 'cashback_created'"
                :class="activeEmail === 'cashback_created' ? 'bg-shopee text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                class="px-4 py-2 rounded-xl text-xs font-bold transition-all focus:outline-none shadow-sm cursor-pointer">
                Ghi nhận đơn hàng
            </button>
            <button type="button" @click="activeEmail = 'cashback_approved'"
                :class="activeEmail === 'cashback_approved' ? 'bg-shopee text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                class="px-4 py-2 rounded-xl text-xs font-bold transition-all focus:outline-none shadow-sm cursor-pointer">
                Duyệt đơn hoàn tiền
            </button>
        </div>

        <!-- Email: Quên mật khẩu -->
        <div x-show="activeEmail === 'forgot_password'" class="space-y-4" x-transition>
            <div class="bg-gray-50/50 p-4 rounded-2xl border border-gray-100">
                <h4 class="text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Các biến hỗ trợ:</h4>
                <div class="flex flex-wrap gap-2">
                    <span class="px-2 py-1 bg-white border border-gray-250 rounded-lg text-[10px] text-gray-600 font-mono"><code>{name}</code> - Tên thành viên</span>
                    <span class="px-2 py-1 bg-white border border-gray-250 rounded-lg text-[10px] text-gray-600 font-mono"><code>{email}</code> - Email thành viên</span>
                    <span class="px-2 py-1 bg-white border border-gray-250 rounded-lg text-[10px] text-gray-600 font-mono"><code>{reset_url}</code> - Đường dẫn đặt lại mật khẩu</span>
                    <span class="px-2 py-1 bg-white border border-gray-250 rounded-lg text-[10px] text-gray-600 font-mono"><code>{year}</code> - Năm hiện tại</span>
                </div>
            </div>
            <div class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div class="md:col-span-3">
                        <div class="flex justify-between items-center mb-1">
                            <label for="email_subject_forgot_password" class="block text-xs font-bold text-gray-700 uppercase tracking-wider">Tiêu đề Email</label>
                            <div class="flex gap-2">
                                <button type="button"
                                    @click="$dispatch('open-email-ai', { key: 'forgot_password' })"
                                    class="inline-flex items-center gap-1 px-2.5 py-1 bg-violet-50 hover:bg-violet-100 text-violet-600 dark:text-violet-400 font-bold rounded-lg text-[10px] border border-violet-100 dark:bg-slate-800 dark:border-slate-700 transition-all cursor-pointer">
                                    <i data-lucide="sparkles" class="w-3.5 h-3.5"></i>
                                    Tạo bằng AI
                                </button>
                                <button type="button"
                                    onclick="previewEmail('forgot_password')"
                                    class="inline-flex items-center gap-1 px-2.5 py-1 bg-blue-50 hover:bg-blue-100 text-blue-600 dark:text-blue-400 font-bold rounded-lg text-[10px] border border-blue-100 dark:bg-slate-800 dark:border-slate-700 transition-all cursor-pointer">
                                    <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                                    Xem trước
                                </button>
                                <button type="button"
                                    @click="sendTestEmail('forgot_password')"
                                    class="inline-flex items-center gap-1 px-2.5 py-1 bg-emerald-50 hover:bg-emerald-100 text-emerald-600 dark:text-emerald-400 font-bold rounded-lg text-[10px] border border-emerald-100 dark:bg-slate-800 dark:border-slate-700 transition-all cursor-pointer">
                                    <i data-lucide="send" class="w-3.5 h-3.5"></i>
                                    Gửi thử
                                </button>
                            </div>
                        </div>
                        <input type="text"
                            name="email_subject_forgot_password"
                            id="email_subject_forgot_password"
                            value="{{ $settings['email_subject_forgot_password'] ?? 'Yêu Cầu Đặt Lại Mật Khẩu - Hoàn Tiền Shopee' }}"
                            class="block w-full px-4 py-2.5 border border-gray-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white">
                    </div>
                    <div class="flex items-end pb-3">
                        <input type="hidden" name="email_status_forgot_password" value="0">
                        <label class="relative inline-flex items-center cursor-pointer select-none">
                            <input type="checkbox" name="email_status_forgot_password" id="email_status_forgot_password" value="1" {{ ($settings['email_status_forgot_password'] ?? '1') === '1' ? 'checked' : '' }} class="sr-only peer">
                            <div class="w-10 h-5 bg-gray-200 peer-focus:outline-none rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all dark:border-slate-600 peer-checked:bg-shopee"></div>
                            <span class="ml-3 text-xs font-bold text-gray-750 dark:text-slate-350">{{ __('Bật gửi mail') }}</span>
                        </label>
                    </div>
                </div>
                <div>
                    <label for="email_content_forgot_password" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Nội dung HTML Template</label>
                    <textarea name="email_content_forgot_password"
                        id="email_content_forgot_password"
                        rows="12"
                        class="block w-full px-4 py-2.5 border border-gray-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white font-mono text-gray-800">{{ $settings['email_content_forgot_password'] ?? '<div style="background-color: #f9fafb; padding: 40px 20px; font-family: system-ui, -apple-system, \'Segoe UI\', Roboto, Helvetica, Arial, sans-serif;">
    <div style="max-width: 560px; margin: 0 auto; background-color: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05); border: 1px solid #eaeaea;">
        <div style="height: 6px; background: linear-gradient(90deg, #ff7a45 0%, #ee4d2d 100%);"></div>
        <div style="padding: 32px 24px; text-align: center; border-bottom: 1px solid #f3f4f6;">
            <h2 style="color: #ee4d2d; margin: 0; font-size: 24px; font-weight: 800; letter-spacing: -0.5px;">Hoàn Tiền Shopee</h2>
            <p style="color: #6b7280; margin: 4px 0 0 0; font-size: 13px;">Đặt lại mật khẩu tài khoản của bạn</p>
        </div>
        <div style="padding: 32px 24px;">
            <p style="margin: 0 0 16px 0; font-size: 15px; color: #1f2937; line-height: 1.5;">Xin chào <strong>{name}</strong>,</p>
            <p style="margin: 0 0 24px 0; font-size: 14px; color: #4b5563; line-height: 1.6;">Chúng tôi nhận được yêu cầu đặt lại mật khẩu cho tài khoản liên kết với email <strong style="color: #1f2937;">{email}</strong>. Vui lòng nhấn vào nút bên dưới để thiết lập mật khẩu mới (yêu cầu này có hiệu lực trong vòng 60 phút):</p>
            
            <div style="text-align: center; margin: 32px 0;">
                <a href="{reset_url}" style="background: linear-gradient(135deg, #ff7a45 0%, #ee4d2d 100%); color: #ffffff; padding: 14px 32px; text-decoration: none; border-radius: 12px; font-weight: bold; font-size: 14px; display: inline-block; box-shadow: 0 4px 12px rgba(238, 77, 45, 0.25);">Đặt Lại Mật Khẩu</a>
            </div>
            
            <p style="margin: 0 0 16px 0; font-size: 13px; color: #9ca3af; line-height: 1.6; font-style: italic; border-left: 3px solid #e5e7eb; padding-left: 12px;">Nếu bạn không yêu cầu đặt lại mật khẩu, vui lòng bỏ qua email này. Tài khoản của bạn vẫn được bảo mật an toàn.</p>
        </div>
        <div style="padding: 24px; background-color: #f9fafb; border-top: 1px solid #f3f4f6; text-align: center;">
            <p style="margin: 0; color: #9ca3af; font-size: 11px;">© {year} Hoàn Tiền Shopee. Tất cả quyền được bảo lưu.</p>
        </div>
    </div>
</div>' }}</textarea>
                </div>
            </div>
        </div>

        <!-- Email: Xác minh OTP -->
        <div x-show="activeEmail === 'otp'" class="space-y-4" x-transition>
            <div class="bg-gray-50/50 p-4 rounded-2xl border border-gray-100">
                <h4 class="text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Các biến hỗ trợ:</h4>
                <div class="flex flex-wrap gap-2">
                    <span class="px-2 py-1 bg-white border border-gray-250 rounded-lg text-[10px] text-gray-600 font-mono"><code>{name}</code> - Tên thành viên</span>
                    <span class="px-2 py-1 bg-white border border-gray-250 rounded-lg text-[10px] text-gray-600 font-mono"><code>{email}</code> - Email thành viên</span>
                    <span class="px-2 py-1 bg-white border border-gray-250 rounded-lg text-[10px] text-gray-600 font-mono"><code>{otp}</code> - Mã OTP xác thực (6 chữ số)</span>
                    <span class="px-2 py-1 bg-white border border-gray-250 rounded-lg text-[10px] text-gray-600 font-mono"><code>{year}</code> - Năm hiện tại</span>
                </div>
            </div>
            <div class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div class="md:col-span-3">
                        <div class="flex justify-between items-center mb-1">
                            <label for="email_subject_otp" class="block text-xs font-bold text-gray-700 uppercase tracking-wider">Tiêu đề Email</label>
                            <div class="flex gap-2">
                                <button type="button"
                                    @click="$dispatch('open-email-ai', { key: 'otp' })"
                                    class="inline-flex items-center gap-1 px-2.5 py-1 bg-violet-50 hover:bg-violet-100 text-violet-600 dark:text-violet-400 font-bold rounded-lg text-[10px] border border-violet-100 dark:bg-slate-800 dark:border-slate-700 transition-all cursor-pointer">
                                    <i data-lucide="sparkles" class="w-3.5 h-3.5"></i>
                                    Tạo bằng AI
                                </button>
                                <button type="button"
                                    onclick="previewEmail('otp')"
                                    class="inline-flex items-center gap-1 px-2.5 py-1 bg-blue-50 hover:bg-blue-100 text-blue-600 dark:text-blue-400 font-bold rounded-lg text-[10px] border border-blue-100 dark:bg-slate-800 dark:border-slate-700 transition-all cursor-pointer">
                                    <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                                    Xem trước
                                </button>
                                <button type="button"
                                    @click="sendTestEmail('otp')"
                                    class="inline-flex items-center gap-1 px-2.5 py-1 bg-emerald-50 hover:bg-emerald-100 text-emerald-600 dark:text-emerald-400 font-bold rounded-lg text-[10px] border border-emerald-100 dark:bg-slate-800 dark:border-slate-700 transition-all cursor-pointer">
                                    <i data-lucide="send" class="w-3.5 h-3.5"></i>
                                    Gửi thử
                                </button>
                            </div>
                        </div>
                        <input type="text"
                            name="email_subject_otp"
                            id="email_subject_otp"
                            value="{{ $settings['email_subject_otp'] ?? 'Mã Xác Thực Đăng Nhập - Hoàn Tiền Shopee' }}"
                            class="block w-full px-4 py-2.5 border border-gray-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white">
                    </div>
                    <div class="flex items-end pb-3">
                        <input type="hidden" name="email_status_otp" value="0">
                        <label class="relative inline-flex items-center cursor-pointer select-none">
                            <input type="checkbox" name="email_status_otp" id="email_status_otp" value="1" {{ ($settings['email_status_otp'] ?? '1') === '1' ? 'checked' : '' }} class="sr-only peer">
                            <div class="w-10 h-5 bg-gray-200 peer-focus:outline-none rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all dark:border-slate-600 peer-checked:bg-shopee"></div>
                            <span class="ml-3 text-xs font-bold text-gray-750 dark:text-slate-350">{{ __('Bật gửi mail') }}</span>
                        </label>
                    </div>
                </div>
                <div>
                    <label for="email_content_otp" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Nội dung HTML Template</label>
                    <textarea name="email_content_otp"
                        id="email_content_otp"
                        rows="12"
                        class="block w-full px-4 py-2.5 border border-gray-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white font-mono text-gray-800">{{ $settings['email_content_otp'] ?? '<div style="background-color: #f9fafb; padding: 40px 20px; font-family: system-ui, -apple-system, \'Segoe UI\', Roboto, Helvetica, Arial, sans-serif;">
    <div style="max-width: 560px; margin: 0 auto; background-color: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05); border: 1px solid #eaeaea;">
        <div style="height: 6px; background: linear-gradient(90deg, #ff7a45 0%, #ee4d2d 100%);"></div>
        <div style="padding: 32px 24px; text-align: center; border-bottom: 1px solid #f3f4f6;">
            <h2 style="color: #ee4d2d; margin: 0; font-size: 24px; font-weight: 800; letter-spacing: -0.5px;">Hoàn Tiền Shopee</h2>
            <p style="color: #6b7280; margin: 4px 0 0 0; font-size: 13px;">Mã xác thực giao dịch bảo mật</p>
        </div>
        <div style="padding: 32px 24px;">
            <p style="margin: 0 0 16px 0; font-size: 15px; color: #1f2937; line-height: 1.5;">Xin chào <strong>{name}</strong>,</p>
            <p style="margin: 0 0 24px 0; font-size: 14px; color: #4b5563; line-height: 1.6;">Dưới đây là mã xác thực OTP dùng để đăng nhập hoặc xác thực hoạt động tài khoản của bạn:</p>
            
            <div style="background-color: #fff7f5; border: 1.5px dashed #ee4d2d; border-radius: 12px; padding: 20px; text-align: center; margin: 24px 0;">
                <span style="font-size: 36px; font-weight: 800; letter-spacing: 8px; color: #ee4d2d; font-family: \'Courier New\', Courier, monospace;">{otp}</span>
                <p style="margin: 8px 0 0 0; font-size: 12px; color: #ff7a45;">Mã OTP có hiệu lực trong vòng 10 phút</p>
            </div>
            
            <p style="margin: 0; font-size: 13px; color: #ff4d4d; line-height: 1.6; font-weight: 500;">⚠️ Lưu ý: Tuyệt đối KHÔNG chia sẻ mã OTP này cho bất kỳ ai, kể cả nhân viên hỗ trợ hệ thống.</p>
        </div>
        <div style="padding: 24px; background-color: #f9fafb; border-top: 1px solid #f3f4f6; text-align: center;">
            <p style="margin: 0; color: #9ca3af; font-size: 11px;">© {year} Hoàn Tiền Shopee. Tất cả quyền được bảo lưu.</p>
        </div>
    </div>
</div>' }}</textarea>
                </div>
            </div>
        </div>

        <!-- Email: Xác minh Email -->
        <div x-show="activeEmail === 'verify_email'" class="space-y-4" x-transition>
            <div class="bg-gray-50/50 p-4 rounded-2xl border border-gray-100">
                <h4 class="text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Các biến hỗ trợ:</h4>
                <div class="flex flex-wrap gap-2">
                    <span class="px-2 py-1 bg-white border border-gray-250 rounded-lg text-[10px] text-gray-600 font-mono"><code>{name}</code> - Tên thành viên</span>
                    <span class="px-2 py-1 bg-white border border-gray-250 rounded-lg text-[10px] text-gray-600 font-mono"><code>{email}</code> - Email thành viên</span>
                    <span class="px-2 py-1 bg-white border border-gray-250 rounded-lg text-[10px] text-gray-600 font-mono"><code>{otp}</code> - Mã OTP xác thực (6 chữ số)</span>
                    <span class="px-2 py-1 bg-white border border-gray-250 rounded-lg text-[10px] text-gray-600 font-mono"><code>{year}</code> - Năm hiện tại</span>
                </div>
            </div>
            <div class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div class="md:col-span-3">
                        <div class="flex justify-between items-center mb-1">
                            <label for="email_subject_verify_email" class="block text-xs font-bold text-gray-700 uppercase tracking-wider">Tiêu đề Email</label>
                            <div class="flex gap-2">
                                <button type="button"
                                    @click="$dispatch('open-email-ai', { key: 'verify_email' })"
                                    class="inline-flex items-center gap-1 px-2.5 py-1 bg-violet-50 hover:bg-violet-100 text-violet-600 dark:text-violet-400 font-bold rounded-lg text-[10px] border border-violet-100 dark:bg-slate-800 dark:border-slate-700 transition-all cursor-pointer">
                                    <i data-lucide="sparkles" class="w-3.5 h-3.5"></i>
                                    Tạo bằng AI
                                </button>
                                <button type="button"
                                    onclick="previewEmail('verify_email')"
                                    class="inline-flex items-center gap-1 px-2.5 py-1 bg-blue-50 hover:bg-blue-100 text-blue-600 dark:text-blue-400 font-bold rounded-lg text-[10px] border border-blue-100 dark:bg-slate-800 dark:border-slate-700 transition-all cursor-pointer">
                                    <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                                    Xem trước
                                </button>
                                <button type="button"
                                    @click="sendTestEmail('verify_email')"
                                    class="inline-flex items-center gap-1 px-2.5 py-1 bg-emerald-50 hover:bg-emerald-100 text-emerald-600 dark:text-emerald-400 font-bold rounded-lg text-[10px] border border-emerald-100 dark:bg-slate-800 dark:border-slate-700 transition-all cursor-pointer">
                                    <i data-lucide="send" class="w-3.5 h-3.5"></i>
                                    Gửi thử
                                </button>
                            </div>
                        </div>
                        <input type="text"
                            name="email_subject_verify_email"
                            id="email_subject_verify_email"
                            value="{{ $settings['email_subject_verify_email'] ?? 'Xác Minh Email Tài Khoản - Hoàn Tiền Shopee' }}"
                            class="block w-full px-4 py-2.5 border border-gray-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white">
                    </div>
                    <div class="flex items-end pb-3">
                        <input type="hidden" name="email_status_verify_email" value="0">
                        <label class="relative inline-flex items-center cursor-pointer select-none">
                            <input type="checkbox" name="email_status_verify_email" id="email_status_verify_email" value="1" {{ ($settings['email_status_verify_email'] ?? '1') === '1' ? 'checked' : '' }} class="sr-only peer">
                            <div class="w-10 h-5 bg-gray-200 peer-focus:outline-none rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all dark:border-slate-600 peer-checked:bg-shopee"></div>
                            <span class="ml-3 text-xs font-bold text-gray-750 dark:text-slate-350">{{ __('Bật gửi mail') }}</span>
                        </label>
                    </div>
                </div>
                <div>
                    <label for="email_content_verify_email" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Nội dung HTML Template</label>
                    <textarea name="email_content_verify_email"
                        id="email_content_verify_email"
                        rows="12"
                        class="block w-full px-4 py-2.5 border border-gray-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white font-mono text-gray-800">{{ $settings['email_content_verify_email'] ?? '<div style="background-color: #f9fafb; padding: 40px 20px; font-family: system-ui, -apple-system, \'Segoe UI\', Roboto, Helvetica, Arial, sans-serif;">
    <div style="max-width: 560px; margin: 0 auto; background-color: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05); border: 1px solid #eaeaea;">
        <div style="height: 6px; background: linear-gradient(90deg, #ff7a45 0%, #ee4d2d 100%);"></div>
        <div style="padding: 32px 24px; text-align: center; border-bottom: 1px solid #f3f4f6;">
            <h2 style="color: #ee4d2d; margin: 0; font-size: 24px; font-weight: 800; letter-spacing: -0.5px;">Hoàn Tiền Shopee</h2>
            <p style="color: #6b7280; margin: 4px 0 0 0; font-size: 13px;">Xác minh địa chỉ email của bạn</p>
        </div>
        <div style="padding: 32px 24px;">
            <p style="margin: 0 0 16px 0; font-size: 15px; color: #1f2937; line-height: 1.5;">Xin chào <strong>{name}</strong>,</p>
            <p style="margin: 0 0 24px 0; font-size: 14px; color: #4b5563; line-height: 1.6;">Cảm ơn bạn đã đăng ký tài khoản tại Hoàn Tiền Shopee. Để hoàn tất việc đăng ký và kích hoạt tài khoản, vui lòng sử dụng mã xác thực OTP dưới đây:</p>
            
            <div style="background-color: #fff7f5; border: 1.5px dashed #ee4d2d; border-radius: 12px; padding: 20px; text-align: center; margin: 24px 0;">
                <span style="font-size: 36px; font-weight: 800; letter-spacing: 8px; color: #ee4d2d; font-family: \'Courier New\', Courier, monospace;">{otp}</span>
                <p style="margin: 8px 0 0 0; font-size: 12px; color: #ff7a45;">Mã xác thực có hiệu lực trong vòng 10 phút</p>
            </div>
            
            <p style="margin: 0 0 16px 0; font-size: 13px; color: #9ca3af; line-height: 1.6; font-style: italic; border-left: 3px solid #e5e7eb; padding-left: 12px;">Nếu bạn không thực hiện đăng ký tài khoản trên hệ thống của chúng tôi, vui lòng bỏ qua email này.</p>
        </div>
        <div style="padding: 24px; background-color: #f9fafb; border-top: 1px solid #f3f4f6; text-align: center;">
            <p style="margin: 0; color: #9ca3af; font-size: 11px;">© {year} Hoàn Tiền Shopee. Tất cả quyền được bảo lưu.</p>
        </div>
    </div>
</div>' }}</textarea>
                </div>
            </div>
        </div>

        <!-- Email: Chào mừng đăng ký -->
        <div x-show="activeEmail === 'welcome'" class="space-y-4" x-transition>
            <div class="bg-gray-50/50 p-4 rounded-2xl border border-gray-100">
                <h4 class="text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Các biến hỗ trợ:</h4>
                <div class="flex flex-wrap gap-2">
                    <span class="px-2 py-1 bg-white border border-gray-250 rounded-lg text-[10px] text-gray-600 font-mono"><code>{name}</code> - Tên thành viên</span>
                    <span class="px-2 py-1 bg-white border border-gray-250 rounded-lg text-[10px] text-gray-600 font-mono"><code>{email}</code> - Email thành viên</span>
                    <span class="px-2 py-1 bg-white border border-gray-250 rounded-lg text-[10px] text-gray-600 font-mono"><code>{dashboard_url}</code> - Đường dẫn tới trang dashboard</span>
                    <span class="px-2 py-1 bg-white border border-gray-250 rounded-lg text-[10px] text-gray-600 font-mono"><code>{year}</code> - Năm hiện tại</span>
                </div>
            </div>
            <div class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div class="md:col-span-3">
                        <div class="flex justify-between items-center mb-1">
                            <label for="email_subject_welcome" class="block text-xs font-bold text-gray-700 uppercase tracking-wider">Tiêu đề Email</label>
                            <div class="flex gap-2">
                                <button type="button"
                                    @click="$dispatch('open-email-ai', { key: 'welcome' })"
                                    class="inline-flex items-center gap-1 px-2.5 py-1 bg-violet-50 hover:bg-violet-100 text-violet-600 dark:text-violet-400 font-bold rounded-lg text-[10px] border border-violet-100 dark:bg-slate-800 dark:border-slate-700 transition-all cursor-pointer">
                                    <i data-lucide="sparkles" class="w-3.5 h-3.5"></i>
                                    Tạo bằng AI
                                </button>
                                <button type="button"
                                    onclick="previewEmail('welcome')"
                                    class="inline-flex items-center gap-1 px-2.5 py-1 bg-blue-50 hover:bg-blue-100 text-blue-600 dark:text-blue-400 font-bold rounded-lg text-[10px] border border-blue-100 dark:bg-slate-800 dark:border-slate-700 transition-all cursor-pointer">
                                    <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                                    Xem trước
                                </button>
                                <button type="button"
                                    @click="sendTestEmail('welcome')"
                                    class="inline-flex items-center gap-1 px-2.5 py-1 bg-emerald-50 hover:bg-emerald-100 text-emerald-600 dark:text-emerald-400 font-bold rounded-lg text-[10px] border border-emerald-100 dark:bg-slate-800 dark:border-slate-700 transition-all cursor-pointer">
                                    <i data-lucide="send" class="w-3.5 h-3.5"></i>
                                    Gửi thử
                                </button>
                            </div>
                        </div>
                        <input type="text"
                            name="email_subject_welcome"
                            id="email_subject_welcome"
                            value="{{ $settings['email_subject_welcome'] ?? 'Chào Mừng Bạn Đến Với Hoàn Tiền Shopee' }}"
                            class="block w-full px-4 py-2.5 border border-gray-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white">
                    </div>
                    <div class="flex items-end pb-3">
                        <input type="hidden" name="email_status_welcome" value="0">
                        <label class="relative inline-flex items-center cursor-pointer select-none">
                            <input type="checkbox" name="email_status_welcome" id="email_status_welcome" value="1" {{ ($settings['email_status_welcome'] ?? '1') === '1' ? 'checked' : '' }} class="sr-only peer">
                            <div class="w-10 h-5 bg-gray-200 peer-focus:outline-none rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all dark:border-slate-600 peer-checked:bg-shopee"></div>
                            <span class="ml-3 text-xs font-bold text-gray-750 dark:text-slate-350">{{ __('Bật gửi mail') }}</span>
                        </label>
                    </div>
                </div>
                <div>
                    <label for="email_content_welcome" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Nội dung HTML Template</label>
                    <textarea name="email_content_welcome"
                        id="email_content_welcome"
                        rows="12"
                        class="block w-full px-4 py-2.5 border border-gray-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white font-mono text-gray-800">{{ $settings['email_content_welcome'] ?? '<div style="background-color: #f9fafb; padding: 40px 20px; font-family: system-ui, -apple-system, \'Segoe UI\', Roboto, Helvetica, Arial, sans-serif;">
    <div style="max-width: 560px; margin: 0 auto; background-color: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05); border: 1px solid #eaeaea;">
        <div style="height: 6px; background: linear-gradient(90deg, #ff7a45 0%, #ee4d2d 100%);"></div>
        <div style="padding: 32px 24px; text-align: center; border-bottom: 1px solid #f3f4f6;">
            <h2 style="color: #ee4d2d; margin: 0; font-size: 24px; font-weight: 800; letter-spacing: -0.5px;">Hoàn Tiền Shopee</h2>
            <p style="color: #6b7280; margin: 4px 0 0 0; font-size: 13px;">Chào mừng bạn tham gia cộng đồng của chúng tôi</p>
        </div>
        <div style="padding: 32px 24px;">
            <p style="margin: 0 0 16px 0; font-size: 15px; color: #1f2937; line-height: 1.5;">Xin chào <strong>{name}</strong>,</p>
            <p style="margin: 0 0 16px 0; font-size: 14px; color: #4b5563; line-height: 1.6;">Chúc mừng bạn đã tạo tài khoản thành công tại <strong>Hoàn Tiền Shopee</strong> - Nền tảng cashback mua sắm uy tín hàng đầu Việt Nam.</p>
            <p style="margin: 0 0 24px 0; font-size: 14px; color: #4b5563; line-height: 1.6;">Từ bây giờ, bạn có thể bắt đầu mua sắm thông qua link rút gọn để nhận chiết khấu hoa hồng hấp dẫn từ Shopee, đồng thời tham gia xây dựng đội ngũ tiếp thị 2 tầng (F1 và F2) để gia tăng thu nhập thụ động không giới hạn.</p>
            
            <div style="text-align: center; margin: 32px 0;">
                <a href="{dashboard_url}" style="background: linear-gradient(135deg, #ff7a45 0%, #ee4d2d 100%); color: #ffffff; padding: 14px 32px; text-decoration: none; border-radius: 12px; font-weight: bold; font-size: 14px; display: inline-block; box-shadow: 0 4px 12px rgba(238, 77, 45, 0.25);">Bắt Đầu Trải Nghiệm</a>
            </div>
            
            <div style="background-color: #f9fafb; border-radius: 12px; padding: 16px; border: 1px solid #f3f4f6;">
                <h4 style="margin: 0 0 8px 0; font-size: 13px; color: #1f2937; font-weight: bold;">💡 Mẹo nhỏ cho bạn:</h4>
                <p style="margin: 0; font-size: 12px; color: #6b7280; line-height: 1.5;">Đừng quên điểm danh chuyên cần mỗi ngày tại trang thành viên để nhận xu thưởng miễn phí và tích lũy chuỗi ngày để nhận phần thưởng lớn hơn nhé!</p>
            </div>
        </div>
        <div style="padding: 24px; background-color: #f9fafb; border-top: 1px solid #f3f4f6; text-align: center;">
            <p style="margin: 0; color: #9ca3af; font-size: 11px;">© {year} Hoàn Tiền Shopee. Tất cả quyền được bảo lưu.</p>
        </div>
    </div>
</div>' }}</textarea>
                </div>
            </div>
        </div>

        <!-- Email: Tạo lệnh rút tiền -->
        <div x-show="activeEmail === 'withdrawal_created'" class="space-y-4" x-transition>
            <div class="bg-gray-50/50 p-4 rounded-2xl border border-gray-100">
                <h4 class="text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Các biến hỗ trợ:</h4>
                <div class="flex flex-wrap gap-2">
                    <span class="px-2 py-1 bg-white border border-gray-250 rounded-lg text-[10px] text-gray-600 font-mono"><code>{name}</code> - Tên thành viên</span>
                    <span class="px-2 py-1 bg-white border border-gray-250 rounded-lg text-[10px] text-gray-600 font-mono"><code>{email}</code> - Email thành viên</span>
                    <span class="px-2 py-1 bg-white border border-gray-250 rounded-lg text-[10px] text-gray-600 font-mono"><code>{amount}</code> - Số tiền rút</span>
                    <span class="px-2 py-1 bg-white border border-gray-250 rounded-lg text-[10px] text-gray-600 font-mono"><code>{payment_method}</code> - Phương thức nhận</span>
                    <span class="px-2 py-1 bg-white border border-gray-250 rounded-lg text-[10px] text-gray-600 font-mono"><code>{account_name}</code> - Tên chủ tài khoản</span>
                    <span class="px-2 py-1 bg-white border border-gray-250 rounded-lg text-[10px] text-gray-600 font-mono"><code>{account_number}</code> - Số tài khoản/SĐT ví</span>
                    <span class="px-2 py-1 bg-white border border-gray-250 rounded-lg text-[10px] text-gray-600 font-mono"><code>{bank_row}</code> - Khối thông tin ngân hàng (nếu rút ngân hàng)</span>
                    <span class="px-2 py-1 bg-white border border-gray-250 rounded-lg text-[10px] text-gray-600 font-mono"><code>{year}</code> - Năm hiện tại</span>
                </div>
            </div>
            <div class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div class="md:col-span-3">
                        <div class="flex justify-between items-center mb-1">
                            <label for="email_subject_withdrawal_created" class="block text-xs font-bold text-gray-700 uppercase tracking-wider">Tiêu đề Email</label>
                            <div class="flex gap-2">
                                <button type="button"
                                    @click="$dispatch('open-email-ai', { key: 'withdrawal_created' })"
                                    class="inline-flex items-center gap-1 px-2.5 py-1 bg-violet-50 hover:bg-violet-100 text-violet-600 dark:text-violet-400 font-bold rounded-lg text-[10px] border border-violet-100 dark:bg-slate-800 dark:border-slate-700 transition-all cursor-pointer">
                                    <i data-lucide="sparkles" class="w-3.5 h-3.5"></i>
                                    Tạo bằng AI
                                </button>
                                <button type="button"
                                    onclick="previewEmail('withdrawal_created')"
                                    class="inline-flex items-center gap-1 px-2.5 py-1 bg-blue-50 hover:bg-blue-100 text-blue-600 dark:text-blue-400 font-bold rounded-lg text-[10px] border border-blue-100 dark:bg-slate-800 dark:border-slate-700 transition-all cursor-pointer">
                                    <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                                    Xem trước
                                </button>
                                <button type="button"
                                    @click="sendTestEmail('withdrawal_created')"
                                    class="inline-flex items-center gap-1 px-2.5 py-1 bg-emerald-50 hover:bg-emerald-100 text-emerald-600 dark:text-emerald-400 font-bold rounded-lg text-[10px] border border-emerald-100 dark:bg-slate-800 dark:border-slate-700 transition-all cursor-pointer">
                                    <i data-lucide="send" class="w-3.5 h-3.5"></i>
                                    Gửi thử
                                </button>
                            </div>
                        </div>
                        <input type="text"
                            name="email_subject_withdrawal_created"
                            id="email_subject_withdrawal_created"
                            value="{{ $settings['email_subject_withdrawal_created'] ?? 'Yêu Cầu Rút Tiền Đang Chờ Duyệt - Hoàn Tiền Shopee' }}"
                            class="block w-full px-4 py-2.5 border border-gray-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white">
                    </div>
                    <div class="flex items-end pb-3">
                        <input type="hidden" name="email_status_withdrawal_created" value="0">
                        <label class="relative inline-flex items-center cursor-pointer select-none">
                            <input type="checkbox" name="email_status_withdrawal_created" id="email_status_withdrawal_created" value="1" {{ ($settings['email_status_withdrawal_created'] ?? '1') === '1' ? 'checked' : '' }} class="sr-only peer">
                            <div class="w-10 h-5 bg-gray-200 peer-focus:outline-none rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all dark:border-slate-600 peer-checked:bg-shopee"></div>
                            <span class="ml-3 text-xs font-bold text-gray-750 dark:text-slate-350">{{ __('Bật gửi mail') }}</span>
                        </label>
                    </div>
                </div>
                <div>
                    <label for="email_content_withdrawal_created" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Nội dung HTML Template</label>
                    <textarea name="email_content_withdrawal_created"
                        id="email_content_withdrawal_created"
                        rows="12"
                        class="block w-full px-4 py-2.5 border border-gray-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white font-mono text-gray-800">{{ $settings['email_content_withdrawal_created'] ?? '<div style="background-color: #f9fafb; padding: 40px 20px; font-family: system-ui, -apple-system, \'Segoe UI\', Roboto, Helvetica, Arial, sans-serif;">
    <div style="max-width: 560px; margin: 0 auto; background-color: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05); border: 1px solid #eaeaea;">
        <div style="height: 6px; background: linear-gradient(90deg, #ffb037 0%, #ff8c00 100%);"></div>
        <div style="padding: 32px 24px; text-align: center; border-bottom: 1px solid #f3f4f6;">
            <h2 style="color: #ff8c00; margin: 0; font-size: 24px; font-weight: 800; letter-spacing: -0.5px;">Yêu Cầu Rút Tiền</h2>
            <p style="color: #6b7280; margin: 4px 0 0 0; font-size: 13px;">Hệ thống đang tiến hành xử lý yêu cầu của bạn</p>
        </div>
        <div style="padding: 32px 24px;">
            <p style="margin: 0 0 16px 0; font-size: 15px; color: #1f2937; line-height: 1.5;">Xin chào <strong>{name}</strong>,</p>
            <p style="margin: 0 0 20px 0; font-size: 14px; color: #4b5563; line-height: 1.6;">Yêu cầu rút tiền của bạn đã được ghi nhận trên hệ thống and đang chờ phê duyệt. Số dư tương ứng đã được tạm giữ an toàn trong ví:</p>
            
            <div style="background-color: #f9fafb; border-radius: 12px; border: 1px solid #f3f4f6; padding: 20px; margin: 24px 0;">
                <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
                    <tr style="border-bottom: 1px solid #f3f4f6;">
                        <td style="padding: 10px 0; color: #6b7280;">Số tiền rút:</td>
                        <td style="padding: 10px 0; font-weight: bold; color: #1f2937; text-align: right; font-size: 15px;">{amount}đ</td>
                    </tr>
                    <tr style="border-bottom: 1px solid #f3f4f6;">
                        <td style="padding: 10px 0; color: #6b7280;">Phương thức nhận:</td>
                        <td style="padding: 10px 0; font-weight: 500; color: #1f2937; text-align: right; text-transform: uppercase;">{payment_method}</td>
                    </tr>
                    <tr style="border-bottom: 1px solid #f3f4f6;">
                        <td style="padding: 10px 0; color: #6b7280;">Tên tài khoản:</td>
                        <td style="padding: 10px 0; font-weight: 500; color: #1f2937; text-align: right;">{account_name}</td>
                    </tr>
                    <tr style="border-bottom: 1px solid #f3f4f6;">
                        <td style="padding: 10px 0; color: #6b7280;">Số tài khoản:</td>
                        <td style="padding: 10px 0; font-weight: 500; color: #1f2937; text-align: right; font-family: monospace;">{account_number}</td>
                    </tr>
                    {bank_row}
                </table>
            </div>
            
            <p style="margin: 0; font-size: 13px; color: #6b7280; line-height: 1.6;">Giao dịch rút tiền thường được phê duyệt và giải ngân trong vòng 12 - 24 giờ làm việc. Bạn sẽ nhận được thông báo ngay khi giao dịch hoàn tất.</p>
        </div>
        <div style="padding: 24px; background-color: #f9fafb; border-top: 1px solid #f3f4f6; text-align: center;">
            <p style="margin: 0; color: #9ca3af; font-size: 11px;">© {year} Hoàn Tiền Shopee. Tất cả quyền được bảo lưu.</p>
        </div>
    </div>
</div>' }}</textarea>
                </div>
            </div>
        </div>

        <!-- Email: Duyệt lệnh rút tiền -->
        <div x-show="activeEmail === 'withdrawal_approved'" class="space-y-4" x-transition>
            <div class="bg-gray-50/50 p-4 rounded-2xl border border-gray-100">
                <h4 class="text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Các biến hỗ trợ:</h4>
                <div class="flex flex-wrap gap-2">
                    <span class="px-2 py-1 bg-white border border-gray-250 rounded-lg text-[10px] text-gray-600 font-mono"><code>{name}</code> - Tên thành viên</span>
                    <span class="px-2 py-1 bg-white border border-gray-250 rounded-lg text-[10px] text-gray-600 font-mono"><code>{email}</code> - Email thành viên</span>
                    <span class="px-2 py-1 bg-white border border-gray-250 rounded-lg text-[10px] text-gray-600 font-mono"><code>{amount}</code> - Số tiền thực nhận</span>
                    <span class="px-2 py-1 bg-white border border-gray-250 rounded-lg text-[10px] text-gray-600 font-mono"><code>{payment_method}</code> - Phương thức nhận</span>
                    <span class="px-2 py-1 bg-white border border-gray-250 rounded-lg text-[10px] text-gray-600 font-mono"><code>{account_name}</code> - Tên chủ tài khoản</span>
                    <span class="px-2 py-1 bg-white border border-gray-250 rounded-lg text-[10px] text-gray-600 font-mono"><code>{account_number}</code> - Số tài khoản/SĐT ví</span>
                    <span class="px-2 py-1 bg-white border border-gray-250 rounded-lg text-[10px] text-gray-600 font-mono"><code>{bank_row}</code> - Khối thông tin ngân hàng (nếu rút ngân hàng)</span>
                    <span class="px-2 py-1 bg-white border border-gray-250 rounded-lg text-[10px] text-gray-600 font-mono"><code>{year}</code> - Năm hiện tại</span>
                </div>
            </div>
            <div class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div class="md:col-span-3">
                        <div class="flex justify-between items-center mb-1">
                            <label for="email_subject_withdrawal_approved" class="block text-xs font-bold text-gray-700 uppercase tracking-wider">Tiêu đề Email</label>
                            <div class="flex gap-2">
                                <button type="button"
                                    @click="$dispatch('open-email-ai', { key: 'withdrawal_approved' })"
                                    class="inline-flex items-center gap-1 px-2.5 py-1 bg-violet-50 hover:bg-violet-100 text-violet-600 dark:text-violet-400 font-bold rounded-lg text-[10px] border border-violet-100 dark:bg-slate-800 dark:border-slate-700 transition-all cursor-pointer">
                                    <i data-lucide="sparkles" class="w-3.5 h-3.5"></i>
                                    Tạo bằng AI
                                </button>
                                <button type="button"
                                    onclick="previewEmail('withdrawal_approved')"
                                    class="inline-flex items-center gap-1 px-2.5 py-1 bg-blue-50 hover:bg-blue-100 text-blue-600 dark:text-blue-400 font-bold rounded-lg text-[10px] border border-blue-100 dark:bg-slate-800 dark:border-slate-700 transition-all cursor-pointer">
                                    <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                                    Xem trước
                                </button>
                                <button type="button"
                                    @click="sendTestEmail('withdrawal_approved')"
                                    class="inline-flex items-center gap-1 px-2.5 py-1 bg-emerald-50 hover:bg-emerald-100 text-emerald-600 dark:text-emerald-400 font-bold rounded-lg text-[10px] border border-emerald-100 dark:bg-slate-800 dark:border-slate-700 transition-all cursor-pointer">
                                    <i data-lucide="send" class="w-3.5 h-3.5"></i>
                                    Gửi thử
                                </button>
                            </div>
                        </div>
                        <input type="text"
                            name="email_subject_withdrawal_approved"
                            id="email_subject_withdrawal_approved"
                            value="{{ $settings['email_subject_withdrawal_approved'] ?? 'Yêu Cầu Rút Tiền Thành Công - Hoàn Tiền Shopee' }}"
                            class="block w-full px-4 py-2.5 border border-gray-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white">
                    </div>
                    <div class="flex items-end pb-3">
                        <input type="hidden" name="email_status_withdrawal_approved" value="0">
                        <label class="relative inline-flex items-center cursor-pointer select-none">
                            <input type="checkbox" name="email_status_withdrawal_approved" id="email_status_withdrawal_approved" value="1" {{ ($settings['email_status_withdrawal_approved'] ?? '1') === '1' ? 'checked' : '' }} class="sr-only peer">
                            <div class="w-10 h-5 bg-gray-200 peer-focus:outline-none rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all dark:border-slate-600 peer-checked:bg-shopee"></div>
                            <span class="ml-3 text-xs font-bold text-gray-750 dark:text-slate-350">{{ __('Bật gửi mail') }}</span>
                        </label>
                    </div>
                </div>
                <div>
                    <label for="email_content_withdrawal_approved" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Nội dung HTML Template</label>
                    <textarea name="email_content_withdrawal_approved"
                        id="email_content_withdrawal_approved"
                        rows="12"
                        class="block w-full px-4 py-2.5 border border-gray-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white font-mono text-gray-800">{{ $settings['email_content_withdrawal_approved'] ?? '<div style="background-color: #f9fafb; padding: 40px 20px; font-family: system-ui, -apple-system, \'Segoe UI\', Roboto, Helvetica, Arial, sans-serif;">
    <div style="max-width: 560px; margin: 0 auto; background-color: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05); border: 1px solid #eaeaea;">
        <div style="height: 6px; background: linear-gradient(90deg, #34d399 0%, #10b981 100%);"></div>
        <div style="padding: 32px 24px; text-align: center; border-bottom: 1px solid #f3f4f6;">
            <div style="width: 48px; height: 48px; background-color: #ecfdf5; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 12px;">
                <span style="font-size: 24px; color: #10b981;">✓</span>
            </div>
            <h2 style="color: #10b981; margin: 0; font-size: 24px; font-weight: 800; letter-spacing: -0.5px;">Chuyển Tiền Thành Công</h2>
            <p style="color: #6b7280; margin: 4px 0 0 0; font-size: 13px;">Giao dịch rút tiền của bạn đã được giải ngân</p>
        </div>
        <div style="padding: 32px 24px;">
            <p style="margin: 0 0 16px 0; font-size: 15px; color: #1f2937; line-height: 1.5;">Xin chào <strong>{name}</strong>,</p>
            <p style="margin: 0 0 20px 0; font-size: 14px; color: #4b5563; line-height: 1.6;">Quản trị viên đã phê duyệt thành công yêu cầu và thực hiện chuyển tiền vào tài khoản thụ hưởng của bạn:</p>
            
            <div style="background-color: #f0fdf4; border-radius: 12px; border: 1px solid #d1fae5; padding: 20px; margin: 24px 0;">
                <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
                    <tr style="border-bottom: 1px solid #e6f4ea;">
                        <td style="padding: 10px 0; color: #047857;">Số tiền thực nhận:</td>
                        <td style="padding: 10px 0; font-weight: bold; color: #065f46; text-align: right; font-size: 16px;">{amount}đ</td>
                    </tr>
                    <tr style="border-bottom: 1px solid #e6f4ea;">
                        <td style="padding: 10px 0; color: #047857;">Phương thức nhận:</td>
                        <td style="padding: 10px 0; font-weight: 500; color: #1f2937; text-align: right; text-transform: uppercase;">{payment_method}</td>
                    </tr>
                    <tr style="border-bottom: 1px solid #e6f4ea;">
                        <td style="padding: 10px 0; color: #047857;">Tên tài khoản:</td>
                        <td style="padding: 10px 0; font-weight: 500; color: #1f2937; text-align: right;">{account_name}</td>
                    </tr>
                    <tr style="border-bottom: 1px solid #e6f4ea;">
                        <td style="padding: 10px 0; color: #047857;">Số tài khoản:</td>
                        <td style="padding: 10px 0; font-weight: 500; color: #1f2937; text-align: right; font-family: monospace;">{account_number}</td>
                    </tr>
                    {bank_row}
                </table>
            </div>
            
            <p style="margin: 0; font-size: 13px; color: #6b7280; line-height: 1.6;">Cảm ơn bạn đã tin tưởng sử dụng dịch vụ tích lũy và mua sắm thông minh cùng Hoàn Tiền Shopee.</p>
        </div>
        <div style="padding: 24px; background-color: #f9fafb; border-top: 1px solid #f3f4f6; text-align: center;">
            <p style="margin: 0; color: #9ca3af; font-size: 11px;">© {year} Hoàn Tiền Shopee. Tất cả quyền được bảo lưu.</p>
        </div>
    </div>
</div>' }}</textarea>
                </div>
            </div>
        </div>

        <!-- Email: Duyệt đơn đổi quà -->
        <div x-show="activeEmail === 'gift_approved'" class="space-y-4" x-transition>
            <div class="bg-gray-50/50 p-4 rounded-2xl border border-gray-100">
                <h4 class="text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Các biến hỗ trợ:</h4>
                <div class="flex flex-wrap gap-2">
                    <span class="px-2 py-1 bg-white border border-gray-250 rounded-lg text-[10px] text-gray-600 font-mono"><code>{name}</code> - Tên thành viên</span>
                    <span class="px-2 py-1 bg-white border border-gray-250 rounded-lg text-[10px] text-gray-600 font-mono"><code>{email}</code> - Email thành viên</span>
                    <span class="px-2 py-1 bg-white border border-gray-250 rounded-lg text-[10px] text-gray-600 font-mono"><code>{gift_title}</code> - Tên phần quà</span>
                    <span class="px-2 py-1 bg-white border border-gray-250 rounded-lg text-[10px] text-gray-600 font-mono"><code>{gift_price}</code> - Trị giá (xu/đ)</span>
                    <span class="px-2 py-1 bg-white border border-gray-250 rounded-lg text-[10px] text-gray-600 font-mono"><code>{gift_data}</code> - Mã code/Link voucher</span>
                    <span class="px-2 py-1 bg-white border border-gray-250 rounded-lg text-[10px] text-gray-600 font-mono"><code>{notes}</code> - Ghi chú/Vận đơn</span>
                    <span class="px-2 py-1 bg-white border border-gray-250 rounded-lg text-[10px] text-gray-600 font-mono"><code>{year}</code> - Năm hiện tại</span>
                </div>
            </div>
            <div class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div class="md:col-span-3">
                        <div class="flex justify-between items-center mb-1">
                            <label for="email_subject_gift_approved" class="block text-xs font-bold text-gray-700 uppercase tracking-wider">Tiêu đề Email</label>
                            <div class="flex gap-2">
                                <button type="button"
                                    @click="$dispatch('open-email-ai', { key: 'gift_approved' })"
                                    class="inline-flex items-center gap-1 px-2.5 py-1 bg-violet-50 hover:bg-violet-100 text-violet-600 dark:text-violet-400 font-bold rounded-lg text-[10px] border border-violet-100 dark:bg-slate-800 dark:border-slate-700 transition-all cursor-pointer">
                                    <i data-lucide="sparkles" class="w-3.5 h-3.5"></i>
                                    Tạo bằng AI
                                </button>
                                <button type="button"
                                    onclick="previewEmail('gift_approved')"
                                    class="inline-flex items-center gap-1 px-2.5 py-1 bg-blue-50 hover:bg-blue-100 text-blue-600 dark:text-blue-400 font-bold rounded-lg text-[10px] border border-blue-100 dark:bg-slate-800 dark:border-slate-700 transition-all cursor-pointer">
                                    <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                                    Xem trước
                                </button>
                                <button type="button"
                                    @click="sendTestEmail('gift_approved')"
                                    class="inline-flex items-center gap-1 px-2.5 py-1 bg-emerald-50 hover:bg-emerald-100 text-emerald-600 dark:text-emerald-400 font-bold rounded-lg text-[10px] border border-emerald-100 dark:bg-slate-800 dark:border-slate-700 transition-all cursor-pointer">
                                    <i data-lucide="send" class="w-3.5 h-3.5"></i>
                                    Gửi thử
                                </button>
                            </div>
                        </div>
                        <input type="text"
                            name="email_subject_gift_approved"
                            id="email_subject_gift_approved"
                            value="{{ $settings['email_subject_gift_approved'] ?? 'Yêu cầu đổi quà tặng thành công - Hoàn Tiền Shopee' }}"
                            class="block w-full px-4 py-2.5 border border-gray-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white">
                    </div>
                    <div class="flex items-end pb-3">
                        <input type="hidden" name="email_status_gift_approved" value="0">
                        <label class="relative inline-flex items-center cursor-pointer select-none">
                            <input type="checkbox" name="email_status_gift_approved" id="email_status_gift_approved" value="1" {{ ($settings['email_status_gift_approved'] ?? '1') === '1' ? 'checked' : '' }} class="sr-only peer">
                            <div class="w-10 h-5 bg-gray-200 peer-focus:outline-none rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all dark:border-slate-600 peer-checked:bg-shopee"></div>
                            <span class="ml-3 text-xs font-bold text-gray-750 dark:text-slate-350">{{ __('Bật gửi mail') }}</span>
                        </label>
                    </div>
                </div>
                <div>
                    <label for="email_content_gift_approved" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Nội dung HTML Template</label>
                    <textarea name="email_content_gift_approved"
                        id="email_content_gift_approved"
                        rows="12"
                        class="block w-full px-4 py-2.5 border border-gray-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white font-mono text-gray-800">{{ $settings['email_content_gift_approved'] ?? '<div style="background-color: #f9fafb; padding: 40px 20px; font-family: system-ui, -apple-system, \'Segoe UI\', Roboto, Helvetica, Arial, sans-serif;">
    <div style="max-width: 560px; margin: 0 auto; background-color: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05); border: 1px solid #eaeaea;">
        <div style="height: 6px; background: linear-gradient(90deg, #34d399 0%, #10b981 100%);"></div>
        <div style="padding: 32px 24px; text-align: center; border-bottom: 1px solid #f3f4f6;">
            <div style="width: 48px; height: 48px; background-color: #ecfdf5; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 12px;">
                <span style="font-size: 24px; color: #10b981;">🎁</span>
            </div>
            <h2 style="color: #10b981; margin: 0; font-size: 24px; font-weight: 800; letter-spacing: -0.5px;">Nhận Quà Thành Công</h2>
            <p style="color: #6b7280; margin: 4px 0 0 0; font-size: 13px;">Yêu cầu quy đổi quà tặng đã được hoàn tất</p>
        </div>
        <div style="padding: 32px 24px;">
            <p style="margin: 0 0 16px 0; font-size: 15px; color: #1f2937; line-height: 1.5;">Xin chào <strong>{name}</strong>,</p>
            <p style="margin: 0 0 20px 0; font-size: 14px; color: #4b5563; line-height: 1.6;">Yêu cầu đổi quà tặng từ số dư tích lũy của bạn đã được quản trị viên phê duyệt thành công:</p>
            
            <div style="background-color: #f0fdf4; border-radius: 12px; border: 1px solid #d1fae5; padding: 20px; margin: 24px 0;">
                <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
                    <tr style="border-bottom: 1px solid #e6f4ea;">
                        <td style="padding: 10px 0; color: #047857; width: 120px;">Tên quà tặng:</td>
                        <td style="padding: 10px 0; font-weight: bold; color: #1f2937; text-align: right;">{gift_title}</td>
                    </tr>
                    <tr style="border-bottom: 1px solid #e6f4ea;">
                        <td style="padding: 10px 0; color: #047857;">Số tiền quy đổi:</td>
                        <td style="padding: 10px 0; font-weight: 500; color: #1f2937; text-align: right;">{gift_price}đ</td>
                    </tr>
                    {gift_data_row}
                    {notes_row}
                </table>
            </div>
            
            <p style="margin: 0; font-size: 13px; color: #6b7280; line-height: 1.6;">Bạn có thể theo dõi chi tiết tình trạng vận chuyển hoặc thông tin bảo hành tại mục lịch sử đổi quà trong trang cá nhân.</p>
        </div>
        <div style="padding: 24px; background-color: #f9fafb; border-top: 1px solid #f3f4f6; text-align: center;">
            <p style="margin: 0; color: #9ca3af; font-size: 11px;">© {year} Hoàn Tiền Shopee. Tất cả quyền được bảo lưu.</p>
        </div>
    </div>
</div>' }}</textarea>
                </div>
            </div>
        </div>

        <!-- Email: Tạo đơn đổi quà -->
        <div x-show="activeEmail === 'gift_created'" class="space-y-4" x-transition>
            <div class="bg-gray-50/50 p-4 rounded-2xl border border-gray-100">
                <h4 class="text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Các biến hỗ trợ:</h4>
                <div class="flex flex-wrap gap-2">
                    <span class="px-2 py-1 bg-white border border-gray-250 rounded-lg text-[10px] text-gray-600 font-mono"><code>{name}</code> - Tên thành viên</span>
                    <span class="px-2 py-1 bg-white border border-gray-250 rounded-lg text-[10px] text-gray-600 font-mono"><code>{email}</code> - Email nhận quà</span>
                    <span class="px-2 py-1 bg-white border border-gray-250 rounded-lg text-[10px] text-gray-600 font-mono"><code>{gift_title}</code> - Tên phần quà</span>
                    <span class="px-2 py-1 bg-white border border-gray-250 rounded-lg text-[10px] text-gray-600 font-mono"><code>{gift_price}</code> - Trị giá (đ)</span>
                    <span class="px-2 py-1 bg-white border border-gray-250 rounded-lg text-[10px] text-gray-600 font-mono"><code>{year}</code> - Năm hiện tại</span>
                </div>
            </div>
            <div class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div class="md:col-span-3">
                        <div class="flex justify-between items-center mb-1">
                            <label for="email_subject_gift_created" class="block text-xs font-bold text-gray-700 uppercase tracking-wider">Tiêu đề Email</label>
                            <div class="flex gap-2">
                                <button type="button"
                                    @click="$dispatch('open-email-ai', { key: 'gift_created' })"
                                    class="inline-flex items-center gap-1 px-2.5 py-1 bg-violet-50 hover:bg-violet-100 text-violet-600 dark:text-violet-400 font-bold rounded-lg text-[10px] border border-violet-100 dark:bg-slate-800 dark:border-slate-700 transition-all cursor-pointer">
                                    <i data-lucide="sparkles" class="w-3.5 h-3.5"></i>
                                    Tạo bằng AI
                                </button>
                                <button type="button"
                                    onclick="previewEmail('gift_created')"
                                    class="inline-flex items-center gap-1 px-2.5 py-1 bg-blue-50 hover:bg-blue-100 text-blue-600 dark:text-blue-400 font-bold rounded-lg text-[10px] border border-blue-100 dark:bg-slate-800 dark:border-slate-700 transition-all cursor-pointer">
                                    <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                                    Xem trước
                                </button>
                                <button type="button"
                                    @click="sendTestEmail('gift_created')"
                                    class="inline-flex items-center gap-1 px-2.5 py-1 bg-emerald-50 hover:bg-emerald-100 text-emerald-600 dark:text-emerald-400 font-bold rounded-lg text-[10px] border border-emerald-100 dark:bg-slate-800 dark:border-slate-700 transition-all cursor-pointer">
                                    <i data-lucide="send" class="w-3.5 h-3.5"></i>
                                    Gửi thử
                                </button>
                            </div>
                        </div>
                        <input type="text"
                            name="email_subject_gift_created"
                            id="email_subject_gift_created"
                            value="{{ $settings['email_subject_gift_created'] ?? 'Yêu cầu đổi quà tặng đang chờ duyệt - Hoàn Tiền Shopee' }}"
                            class="block w-full px-4 py-2.5 border border-gray-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white">
                    </div>
                    <div class="flex items-end pb-3">
                        <input type="hidden" name="email_status_gift_created" value="0">
                        <label class="relative inline-flex items-center cursor-pointer select-none">
                            <input type="checkbox" name="email_status_gift_created" id="email_status_gift_created" value="1" {{ ($settings['email_status_gift_created'] ?? '1') === '1' ? 'checked' : '' }} class="sr-only peer">
                            <div class="w-10 h-5 bg-gray-200 peer-focus:outline-none rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all dark:border-slate-600 peer-checked:bg-shopee"></div>
                            <span class="ml-3 text-xs font-bold text-gray-750 dark:text-slate-350">{{ __('Bật gửi mail') }}</span>
                        </label>
                    </div>
                </div>
                <div>
                    <label for="email_content_gift_created" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Nội dung HTML Template</label>
                    <textarea name="email_content_gift_created"
                        id="email_content_gift_created"
                        rows="12"
                        class="block w-full px-4 py-2.5 border border-gray-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white font-mono text-gray-800">{{ $settings['email_content_gift_created'] ?? '<div style="background-color: #f9fafb; padding: 40px 20px; font-family: system-ui, -apple-system, \'Segoe UI\', Roboto, Helvetica, Arial, sans-serif;">
    <div style="max-width: 560px; margin: 0 auto; background-color: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05); border: 1px solid #eaeaea;">
        <div style="height: 6px; background: linear-gradient(90deg, #ffb037 0%, #ff8c00 100%);"></div>
        <div style="padding: 32px 24px; text-align: center; border-bottom: 1px solid #f3f4f6;">
            <div style="width: 48px; height: 48px; background-color: #fffbeb; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 12px;">
                <span style="font-size: 24px; color: #f59e0b;">🎁</span>
            </div>
            <h2 style="color: #f59e0b; margin: 0; font-size: 24px; font-weight: 800; letter-spacing: -0.5px;">Yêu Cầu Đổi Quà</h2>
            <p style="color: #6b7280; margin: 4px 0 0 0; font-size: 13px;">Hệ thống đã nhận được yêu cầu quy đổi của bạn</p>
        </div>
        <div style="padding: 32px 24px;">
            <p style="margin: 0 0 16px 0; font-size: 15px; color: #1f2937; line-height: 1.5;">Xin chào <strong>{name}</strong>,</p>
            <p style="margin: 0 0 20px 0; font-size: 14px; color: #4b5563; line-height: 1.6;">Yêu cầu đổi quà tặng từ số dư tích lũy của bạn đã được ghi nhận trên hệ thống và đang chờ phê duyệt từ phía quản trị viên:</p>
            
            <div style="background-color: #fffbeb; border-radius: 12px; border: 1px solid #fef3c7; padding: 20px; margin: 24px 0;">
                <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
                    <tr style="border-bottom: 1px solid #fde68a;">
                        <td style="padding: 10px 0; color: #b45309; width: 120px;">Tên quà tặng:</td>
                        <td style="padding: 10px 0; font-weight: bold; color: #1f2937; text-align: right;">{gift_title}</td>
                    </tr>
                    <tr style="border-bottom: 1px solid #fde68a;">
                        <td style="padding: 10px 0; color: #b45309;">Số tiền quy đổi:</td>
                        <td style="padding: 10px 0; font-weight: 500; color: #1f2937; text-align: right;">{gift_price}đ</td>
                    </tr>
                </table>
            </div>
            
            <p style="margin: 0; font-size: 13px; color: #6b7280; line-height: 1.6;">Yêu cầu quy đổi sẽ được quản trị viên duyệt và phản hồi trong thời gian sớm nhất. Thông tin nhận quà (mã code/vận đơn) sẽ được cập nhật trực tiếp trong email tiếp theo và phần lịch sử đổi quà của bạn.</p>
        </div>
        <div style="padding: 24px; background-color: #f9fafb; border-top: 1px solid #f3f4f6; text-align: center;">
            <p style="margin: 0; color: #9ca3af; font-size: 11px;">© {year} Hoàn Tiền Shopee. Tất cả quyền được bảo lưu.</p>
        </div>
    </div>
</div>' }}</textarea>
                </div>
            </div>
        </div>

        <!-- Email: Ghi nhận đơn hàng -->
        <div x-show="activeEmail === 'cashback_created'" class="space-y-4" x-transition>
            <div class="bg-gray-50/50 p-4 rounded-2xl border border-gray-100">
                <h4 class="text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Các biến hỗ trợ:</h4>
                <div class="flex flex-wrap gap-2">
                    <span class="px-2 py-1 bg-white border border-gray-250 rounded-lg text-[10px] text-gray-600 font-mono"><code>{name}</code> - Tên thành viên</span>
                    <span class="px-2 py-1 bg-white border border-gray-250 rounded-lg text-[10px] text-gray-600 font-mono"><code>{email}</code> - Email thành viên</span>
                    <span class="px-2 py-1 bg-white border border-gray-250 rounded-lg text-[10px] text-gray-600 font-mono"><code>{order_id}</code> - Mã đơn hàng Shopee</span>
                    <span class="px-2 py-1 bg-white border border-gray-250 rounded-lg text-[10px] text-gray-600 font-mono"><code>{platform}</code> - Nền tảng (Shopee/TikTok Shop)</span>
                    <span class="px-2 py-1 bg-white border border-gray-250 rounded-lg text-[10px] text-gray-600 font-mono"><code>{product_name}</code> - Tên sản phẩm</span>
                    <span class="px-2 py-1 bg-white border border-gray-250 rounded-lg text-[10px] text-gray-600 font-mono"><code>{price}</code> - Giá trị sản phẩm</span>
                    <span class="px-2 py-1 bg-white border border-gray-250 rounded-lg text-[10px] text-gray-600 font-mono"><code>{cashback_amount}</code> - Số tiền hoàn dự kiến</span>
                    <span class="px-2 py-1 bg-white border border-gray-250 rounded-lg text-[10px] text-gray-600 font-mono"><code>{year}</code> - Năm hiện tại</span>
                </div>
            </div>
            <div class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div class="md:col-span-3">
                        <div class="flex justify-between items-center mb-1">
                            <label for="email_subject_cashback_created" class="block text-xs font-bold text-gray-700 uppercase tracking-wider">Tiêu đề Email</label>
                            <div class="flex gap-2">
                                <button type="button"
                                    @click="$dispatch('open-email-ai', { key: 'cashback_created' })"
                                    class="inline-flex items-center gap-1 px-2.5 py-1 bg-violet-50 hover:bg-violet-100 text-violet-600 dark:text-violet-400 font-bold rounded-lg text-[10px] border border-violet-100 dark:bg-slate-800 dark:border-slate-700 transition-all cursor-pointer">
                                    <i data-lucide="sparkles" class="w-3.5 h-3.5"></i>
                                    Tạo bằng AI
                                </button>
                                <button type="button"
                                    onclick="previewEmail('cashback_created')"
                                    class="inline-flex items-center gap-1 px-2.5 py-1 bg-blue-50 hover:bg-blue-100 text-blue-600 dark:text-blue-400 font-bold rounded-lg text-[10px] border border-blue-100 dark:bg-slate-800 dark:border-slate-700 transition-all cursor-pointer">
                                    <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                                    Xem trước
                                </button>
                                <button type="button"
                                    @click="sendTestEmail('cashback_created')"
                                    class="inline-flex items-center gap-1 px-2.5 py-1 bg-emerald-50 hover:bg-emerald-100 text-emerald-600 dark:text-emerald-400 font-bold rounded-lg text-[10px] border border-emerald-100 dark:bg-slate-800 dark:border-slate-700 transition-all cursor-pointer">
                                    <i data-lucide="send" class="w-3.5 h-3.5"></i>
                                    Gửi thử
                                </button>
                            </div>
                        </div>
                        <input type="text"
                            name="email_subject_cashback_created"
                            id="email_subject_cashback_created"
                            value="{{ $settings['email_subject_cashback_created'] ?? 'Đơn Hàng Hoàn Tiền Được Ghi Nhận - Hoàn Tiền Shopee' }}"
                            class="block w-full px-4 py-2.5 border border-gray-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white">
                    </div>
                    <div class="flex items-end pb-3">
                        <input type="hidden" name="email_status_cashback_created" value="0">
                        <label class="relative inline-flex items-center cursor-pointer select-none">
                            <input type="checkbox" name="email_status_cashback_created" id="email_status_cashback_created" value="1" {{ ($settings['email_status_cashback_created'] ?? '1') === '1' ? 'checked' : '' }} class="sr-only peer">
                            <div class="w-10 h-5 bg-gray-200 peer-focus:outline-none rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all dark:border-slate-600 peer-checked:bg-shopee"></div>
                            <span class="ml-3 text-xs font-bold text-gray-750 dark:text-slate-350">{{ __('Bật gửi mail') }}</span>
                        </label>
                    </div>
                </div>
                <div>
                    <label for="email_content_cashback_created" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Nội dung HTML Template</label>
                    <textarea name="email_content_cashback_created"
                        id="email_content_cashback_created"
                        rows="12"
                        class="block w-full px-4 py-2.5 border border-gray-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white font-mono text-gray-800">{{ $settings['email_content_cashback_created'] ?? '<div style="background-color: #f9fafb; padding: 40px 20px; font-family: system-ui, -apple-system, \'Segoe UI\', Roboto, Helvetica, Arial, sans-serif;">
    <div style="max-width: 560px; margin: 0 auto; background-color: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05); border: 1px solid #eaeaea;">
        <div style="height: 6px; background: linear-gradient(90deg, #ff7a45 0%, #ee4d2d 100%);"></div>
        <div style="padding: 32px 24px; text-align: center; border-bottom: 1px solid #f3f4f6;">
            <h2 style="color: #ee4d2d; margin: 0; font-size: 24px; font-weight: 800; letter-spacing: -0.5px;">Ghi Nhận Đơn Hàng</h2>
            <p style="color: #6b7280; margin: 4px 0 0 0; font-size: 13px;">Hệ thống đã nhận được thông tin đơn hàng của bạn</p>
        </div>
        <div style="padding: 32px 24px;">
            <p style="margin: 0 0 16px 0; font-size: 15px; color: #1f2937; line-height: 1.5;">Xin chào <strong>{name}</strong>,</p>
            <p style="margin: 0 0 20px 0; font-size: 14px; color: #4b5563; line-height: 1.6;">Hệ thống đã ghi nhận thành công đơn hàng hoàn tiền mới của bạn từ Shopee. Chi tiết đơn hàng tạm tính như sau:</p>
            
            <div style="background-color: #f9fafb; border-radius: 12px; border: 1px solid #f3f4f6; padding: 20px; margin: 24px 0;">
                <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
                    <tr style="border-bottom: 1px solid #f3f4f6;">
                        <td style="padding: 10px 0; color: #6b7280; width: 120px;">Mã đơn hàng:</td>
                        <td style="padding: 10px 0; font-weight: bold; color: #1f2937; text-align: right; font-family: monospace;">{order_id}</td>
                    </tr>
                    <tr style="border-bottom: 1px solid #f3f4f6;">
                        <td style="padding: 10px 0; color: #6b7280;">Nền tảng:</td>
                        <td style="padding: 10px 0; font-weight: bold; color: #1f2937; text-align: right;">{platform}</td>
                    </tr>
                    <tr style="border-bottom: 1px solid #f3f4f6;">
                        <td style="padding: 10px 0; color: #6b7280;">Sản phẩm:</td>
                        <td style="padding: 10px 0; font-weight: bold; color: #1f2937; text-align: right; line-height: 1.4;">{product_name}</td>
                    </tr>
                    <tr style="border-bottom: 1px solid #f3f4f6;">
                        <td style="padding: 10px 0; color: #6b7280;">Giá gốc sản phẩm:</td>
                        <td style="padding: 10px 0; font-weight: bold; color: #1f2937; text-align: right;">{price}đ</td>
                    </tr>
                    <tr style="border-bottom: 1px solid #f3f4f6;">
                        <td style="padding: 10px 0; color: #6b7280;">Tiền hoàn dự kiến:</td>
                        <td style="padding: 10px 0; font-weight: bold; color: #ee4d2d; text-align: right; font-size: 15px;">+{cashback_amount}đ</td>
                    </tr>
                </table>
            </div>
            
            <p style="margin: 0; font-size: 13px; color: #6b7280; line-height: 1.6;">Đơn hàng hiện đang ở trạng thái <strong>Chờ duyệt</strong> (đối soát từ Shopee). Sau khi Shopee hoàn tất thanh toán và đơn hàng được chuyển sang trạng thái hợp lệ, tiền hoàn sẽ được cộng chính thức vào ví khả dụng của bạn.</p>
        </div>
        <div style="padding: 24px; background-color: #f9fafb; border-top: 1px solid #f3f4f6; text-align: center;">
            <p style="margin: 0; color: #9ca3af; font-size: 11px;">© {year} Hoàn Tiền Shopee. Tất cả quyền được bảo lưu.</p>
        </div>
    </div>
</div>' }}</textarea>
                </div>
            </div>
        </div>

        <!-- Email: Duyệt đơn hoàn tiền -->
        <div x-show="activeEmail === 'cashback_approved'" class="space-y-4" x-transition>
            <div class="bg-gray-50/50 p-4 rounded-2xl border border-gray-100">
                <h4 class="text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Các biến hỗ trợ:</h4>
                <div class="flex flex-wrap gap-2">
                    <span class="px-2 py-1 bg-white border border-gray-250 rounded-lg text-[10px] text-gray-600 font-mono"><code>{name}</code> - Tên thành viên</span>
                    <span class="px-2 py-1 bg-white border border-gray-250 rounded-lg text-[10px] text-gray-600 font-mono"><code>{email}</code> - Email thành viên</span>
                    <span class="px-2 py-1 bg-white border border-gray-250 rounded-lg text-[10px] text-gray-600 font-mono"><code>{order_id}</code> - Mã đơn hàng Shopee</span>
                    <span class="px-2 py-1 bg-white border border-gray-250 rounded-lg text-[10px] text-gray-600 font-mono"><code>{platform}</code> - Nền tảng (Shopee/TikTok Shop)</span>
                    <span class="px-2 py-1 bg-white border border-gray-250 rounded-lg text-[10px] text-gray-600 font-mono"><code>{product_name}</code> - Tên sản phẩm</span>
                    <span class="px-2 py-1 bg-white border border-gray-250 rounded-lg text-[10px] text-gray-600 font-mono"><code>{price}</code> - Giá trị sản phẩm</span>
                    <span class="px-2 py-1 bg-white border border-gray-250 rounded-lg text-[10px] text-gray-600 font-mono"><code>{cashback_amount}</code> - Số tiền hoàn thực tế</span>
                    <span class="px-2 py-1 bg-white border border-gray-250 rounded-lg text-[10px] text-gray-600 font-mono"><code>{dashboard_url}</code> - Đường dẫn tới trang ví</span>
                    <span class="px-2 py-1 bg-white border border-gray-250 rounded-lg text-[10px] text-gray-600 font-mono"><code>{year}</code> - Năm hiện tại</span>
                </div>
            </div>
            <div class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div class="md:col-span-3">
                        <div class="flex justify-between items-center mb-1">
                            <label for="email_subject_cashback_approved" class="block text-xs font-bold text-gray-700 uppercase tracking-wider">Tiêu đề Email</label>
                            <div class="flex gap-2">
                                <button type="button"
                                    @click="$dispatch('open-email-ai', { key: 'cashback_approved' })"
                                    class="inline-flex items-center gap-1 px-2.5 py-1 bg-violet-50 hover:bg-violet-100 text-violet-600 dark:text-violet-400 font-bold rounded-lg text-[10px] border border-violet-100 dark:bg-slate-800 dark:border-slate-700 transition-all cursor-pointer">
                                    <i data-lucide="sparkles" class="w-3.5 h-3.5"></i>
                                    Tạo bằng AI
                                </button>
                                <button type="button"
                                    onclick="previewEmail('cashback_approved')"
                                    class="inline-flex items-center gap-1 px-2.5 py-1 bg-blue-50 hover:bg-blue-100 text-blue-600 dark:text-blue-400 font-bold rounded-lg text-[10px] border border-blue-100 dark:bg-slate-800 dark:border-slate-700 transition-all cursor-pointer">
                                    <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                                    Xem trước
                                </button>
                                <button type="button"
                                    @click="sendTestEmail('cashback_approved')"
                                    class="inline-flex items-center gap-1 px-2.5 py-1 bg-emerald-50 hover:bg-emerald-100 text-emerald-600 dark:text-emerald-400 font-bold rounded-lg text-[10px] border border-emerald-100 dark:bg-slate-800 dark:border-slate-700 transition-all cursor-pointer">
                                    <i data-lucide="send" class="w-3.5 h-3.5"></i>
                                    Gửi thử
                                </button>
                            </div>
                        </div>
                        <input type="text"
                            name="email_subject_cashback_approved"
                            id="email_subject_cashback_approved"
                            value="{{ $settings['email_subject_cashback_approved'] ?? 'Đơn Hàng Hoàn Tiền Được Phê Duyệt - Hoàn Tiền Shopee' }}"
                            class="block w-full px-4 py-2.5 border border-gray-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white">
                    </div>
                    <div class="flex items-end pb-3">
                        <input type="hidden" name="email_status_cashback_approved" value="0">
                        <label class="relative inline-flex items-center cursor-pointer select-none">
                            <input type="checkbox" name="email_status_cashback_approved" id="email_status_cashback_approved" value="1" {{ ($settings['email_status_cashback_approved'] ?? '1') === '1' ? 'checked' : '' }} class="sr-only peer">
                            <div class="w-10 h-5 bg-gray-200 peer-focus:outline-none rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all dark:border-slate-600 peer-checked:bg-shopee"></div>
                            <span class="ml-3 text-xs font-bold text-gray-750 dark:text-slate-350">{{ __('Bật gửi mail') }}</span>
                        </label>
                    </div>
                </div>
                <div>
                    <label for="email_content_cashback_approved" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Nội dung HTML Template</label>
                    <textarea name="email_content_cashback_approved"
                        id="email_content_cashback_approved"
                        rows="12"
                        class="block w-full px-4 py-2.5 border border-gray-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white font-mono text-gray-800">{{ $settings['email_content_cashback_approved'] ?? '<div style="background-color: #f9fafb; padding: 40px 20px; font-family: system-ui, -apple-system, \'Segoe UI\', Roboto, Helvetica, Arial, sans-serif;">
    <div style="max-width: 560px; margin: 0 auto; background-color: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05); border: 1px solid #eaeaea;">
        <div style="height: 6px; background: linear-gradient(90deg, #10b981 0%, #059669 100%);"></div>
        <div style="padding: 32px 24px; text-align: center; border-bottom: 1px solid #f3f4f6;">
            <div style="width: 48px; height: 48px; background-color: #ecfdf5; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 12px; margin-left: auto; margin-right: auto;">
                <span style="font-size: 24px; color: #10b981;">🎉</span>
            </div>
            <h2 style="color: #059669; margin: 0; font-size: 24px; font-weight: 800; letter-spacing: -0.5px;">Đơn Hàng Được Duyệt</h2>
            <p style="color: #6b7280; margin: 4px 0 0 0; font-size: 13px;">Chúc mừng! Tiền hoàn đã được cộng vào tài khoản của bạn</p>
        </div>
        <div style="padding: 32px 24px;">
            <p style="margin: 0 0 16px 0; font-size: 15px; color: #1f2937; line-height: 1.5;">Xin chào <strong>{name}</strong>,</p>
            <p style="margin: 0 0 20px 0; font-size: 14px; color: #4b5563; line-height: 1.6;">Đơn hàng của bạn đã được đối soát thành công và phê duyệt hoàn tiền. Thông tin chi tiết như sau:</p>
            
            <div style="background-color: #f0fdf4; border-radius: 12px; border: 1px solid #dcfce7; padding: 20px; margin: 24px 0;">
                <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
                    <tr style="border-bottom: 1px solid #dcfce7;">
                        <td style="padding: 10px 0; color: #15803d; width: 120px;">Mã đơn hàng:</td>
                        <td style="padding: 10px 0; font-weight: bold; color: #1f2937; text-align: right; font-family: monospace;">{order_id}</td>
                    </tr>
                    <tr style="border-bottom: 1px solid #dcfce7;">
                        <td style="padding: 10px 0; color: #15803d;">Nền tảng:</td>
                        <td style="padding: 10px 0; font-weight: bold; color: #1f2937; text-align: right;">{platform}</td>
                    </tr>
                    <tr style="border-bottom: 1px solid #dcfce7;">
                        <td style="padding: 10px 0; color: #15803d;">Sản phẩm:</td>
                        <td style="padding: 10px 0; font-weight: bold; color: #1f2937; text-align: right; line-height: 1.4;">{product_name}</td>
                    </tr>
                    <tr style="border-bottom: 1px solid #dcfce7;">
                        <td style="padding: 10px 0; color: #15803d;">Giá gốc sản phẩm:</td>
                        <td style="padding: 10px 0; font-weight: bold; color: #1f2937; text-align: right;">{price}đ</td>
                    </tr>
                    <tr style="border-bottom: 1px solid #dcfce7;">
                        <td style="padding: 10px 0; color: #15803d;">Tiền hoàn nhận được:</td>
                        <td style="padding: 10px 0; font-weight: bold; color: #10b981; text-align: right; font-size: 15px;">+{cashback_amount}đ</td>
                    </tr>
                </table>
            </div>
            
            <p style="margin: 0 0 20px 0; font-size: 13px; color: #6b7280; line-height: 1.6;">Số tiền hoàn trên đã được cộng chính thức vào ví tích luỹ khả dụng của bạn. Bạn hiện tại đã có thể tạo yêu cầu rút tiền hoặc đổi quà tặng trên hệ thống.</p>
            
            <div style="text-align: center; margin: 24px 0;">
                <a href="{dashboard_url}" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: #ffffff; padding: 12px 28px; text-decoration: none; border-radius: 12px; font-weight: bold; font-size: 14px; display: inline-block; box-shadow: 0 4px 12px rgba(16, 185, 129, 0.2);">Kiểm tra ví của bạn</a>
            </div>
        </div>
        <div style="padding: 24px; background-color: #f9fafb; border-top: 1px solid #f3f4f6; text-align: center;">
            <p style="margin: 0; color: #9ca3af; font-size: 11px;">© {year} Hoàn Tiền Shopee. Tất cả quyền được bảo lưu.</p>
        </div>
    </div>
</div>' }}</textarea>
                </div>
            </div>
        </div>
    </div>
    {{-- Modal tạo mẫu email bằng AI (dùng chung cho mọi template, mở qua sự kiện open-email-ai) --}}
    <div x-data="emailTemplateAi()" @open-email-ai.window="open($event.detail.key)">
        <div x-show="show" x-cloak
             class="fixed inset-0 z-[80] flex items-center justify-center p-4"
             @keydown.escape.window="show = false">
            <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @click="show = false"></div>
            <div class="relative bg-white dark:bg-slate-900 rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] flex flex-col z-10"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100">
                {{-- Lớp phủ loader khi AI đang xử lý: phủ toàn bộ modal với hiệu ứng xoay --}}
                <div x-show="loading" x-cloak
                     class="absolute inset-0 z-20 flex flex-col items-center justify-center gap-3 bg-white/80 dark:bg-slate-900/80 backdrop-blur-sm rounded-2xl">
                    <div class="w-10 h-10 border-[3px] border-violet-200 dark:border-violet-900 border-t-violet-500 rounded-full animate-spin"></div>
                    <p class="text-xs font-semibold text-violet-600 dark:text-violet-400 animate-pulse">{{ __('AI đang soạn nội dung...') }}</p>
                </div>
                <div class="flex items-center justify-between px-5 py-4 border-b border-gray-200 dark:border-slate-800 shrink-0">
                    <div>
                        <h3 class="font-bold text-gray-900 dark:text-slate-100 text-sm flex items-center gap-2">
                            <i data-lucide="sparkles" class="w-4 h-4 text-violet-500"></i>
                            {{ __('Tạo mẫu email bằng AI') }}
                        </h3>
                        <p class="text-xs text-gray-500 dark:text-slate-400 mt-0.5">
                            {{ __('Mẫu') }}: <span class="font-semibold text-violet-500" x-text="keyLabel"></span>
                        </p>
                    </div>
                    <button @click="show = false" type="button" class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 dark:hover:bg-slate-800 rounded-xl transition-all">
                        <i data-lucide="x" class="w-4 h-4"></i>
                    </button>
                </div>
                <div class="flex-1 overflow-y-auto p-5 space-y-4">
                    <div x-show="error" x-cloak class="p-3 bg-red-50 dark:bg-red-950/20 border border-red-200 dark:border-red-900/30 rounded-xl flex gap-2">
                        <i data-lucide="alert-circle" class="w-4 h-4 text-red-500 shrink-0 mt-0.5"></i>
                        <p class="text-xs text-red-600 dark:text-red-400 leading-relaxed" x-text="error"></p>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-600 dark:text-slate-400 uppercase tracking-wider mb-1.5">
                            {{ __('Yêu cầu bổ sung (tuỳ chọn)') }}
                        </label>
                        <textarea x-model="prompt" rows="3"
                                  placeholder="{{ __('VD: Thiết kế hiện đại, nhấn mạnh tính bảo mật, thêm lời chúc cuối thư...') }}"
                                  class="w-full px-4 py-2.5 text-sm border border-gray-200 dark:border-slate-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-violet-500/20 focus:border-violet-500 bg-gray-50/50 dark:bg-slate-800 dark:text-slate-200"></textarea>
                        <p class="text-[10px] text-gray-400 mt-1">{{ __('Để trống, AI sẽ tự soạn nội dung chuẩn cho loại email này. Các biến động sẽ được giữ nguyên.') }}</p>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-600 dark:text-slate-400 uppercase tracking-wider mb-1.5">
                            {{ __('Giọng điệu') }}
                        </label>
                        <select x-model="tone"
                                class="w-full px-3 py-2.5 text-xs border border-gray-200 dark:border-slate-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-violet-500/20 focus:border-violet-500 bg-gray-50/50 dark:bg-slate-800 dark:text-slate-200">
                            <option value="chuyen-nghiep">{{ __('Chuyên nghiệp, trang trọng') }}</option>
                            <option value="than-thien">{{ __('Thân thiện, gần gũi') }}</option>
                            <option value="hao-hung">{{ __('Hào hứng, nhiều cảm xúc') }}</option>
                        </select>
                    </div>

                    <label class="flex items-center gap-2.5 p-3 border border-gray-200 dark:border-slate-700 rounded-xl cursor-pointer hover:bg-gray-50 dark:hover:bg-slate-800 transition-all">
                        <input type="checkbox" x-model="withSubject" class="text-violet-500 rounded focus:ring-violet-500">
                        <span class="text-xs font-semibold text-gray-700 dark:text-slate-300">{{ __('Cập nhật cả Tiêu đề Email') }}</span>
                    </label>

                    <div class="p-3 bg-violet-50 dark:bg-violet-950/20 rounded-xl border border-violet-100 dark:border-violet-900/30 flex gap-2">
                        <i data-lucide="info" class="w-3.5 h-3.5 text-violet-500 shrink-0 mt-0.5"></i>
                        <p class="text-[10px] text-violet-700 dark:text-violet-400 leading-relaxed">
                            {{ __('Nội dung HTML của mẫu email này sẽ bị thay thế bằng kết quả AI tạo ra.') }}
                        </p>
                    </div>
                </div>
                <div class="flex items-center justify-end gap-2 px-5 py-4 border-t border-gray-200 dark:border-slate-800 shrink-0">
                    <button @click="show = false" type="button"
                            class="px-4 py-2 text-xs font-semibold text-gray-600 dark:text-slate-400 bg-gray-100 dark:bg-slate-800 rounded-xl hover:bg-gray-200 dark:hover:bg-slate-700 transition-all">
                        {{ __('Huỷ') }}
                    </button>
                    <button @click="generate()" type="button" :disabled="loading"
                            class="inline-flex items-center gap-2 px-5 py-2 text-xs font-bold text-white bg-gradient-to-r from-violet-500 to-fuchsia-500 hover:from-violet-600 hover:to-fuchsia-600 rounded-xl transition-all shadow-sm shadow-violet-500/20 disabled:opacity-60 disabled:cursor-not-allowed">
                        <i data-lucide="sparkles" class="w-3.5 h-3.5" :class="{ 'animate-spin': loading }"></i>
                        <span x-text="loading ? '{{ __('Đang tạo...') }}' : '{{ __('Tạo nội dung') }}'"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Component AlpineJS: tạo nhanh mẫu email giao dịch bằng AI (mở qua sự kiện open-email-ai)
    function emailTemplateAi() {
        return {
            aiEnabled: {{ ($settings['ai_status'] ?? '0') === '1' ? 'true' : 'false' }},
            labels: {
                forgot_password: 'Quên mật khẩu',
                otp: 'Xác minh OTP',
                verify_email: 'Xác minh Email',
                welcome: 'Chào mừng đăng ký',
                withdrawal_created: 'Tạo lệnh rút tiền',
                withdrawal_approved: 'Duyệt lệnh rút tiền',
                gift_approved: 'Duyệt đơn đổi quà',
                gift_created: 'Tạo đơn đổi quà',
                cashback_created: 'Ghi nhận đơn hàng',
                cashback_approved: 'Duyệt đơn hoàn tiền',
            },
            show: false,
            currentKey: '',
            keyLabel: '',
            prompt: '',
            tone: 'chuyen-nghiep',
            withSubject: true,
            loading: false,
            error: '',

            open(key) {
                if (!this.aiEnabled) {
                    alert("{{ __('Dịch vụ AI hiện đang tắt. Vui lòng kích hoạt trong tab Kết nối > AI.') }}");
                    return;
                }
                this.currentKey = key;
                this.keyLabel = this.labels[key] || key;
                this.error = '';
                this.prompt = '';
                this.show = true;
                this.$nextTick(() => { if (window.lucide) window.lucide.createIcons(); });
            },

            generate() {
                if (this.loading) return;
                this.loading = true;
                this.error = '';
                fetch('{{ route('admin.settings.generate_email_ai') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        key: this.currentKey,
                        prompt: this.prompt,
                        tone: this.tone,
                        with_subject: this.withSubject,
                    })
                })
                .then(r => r.json().then(data => ({ ok: r.ok, data })))
                .then(({ ok, data }) => {
                    if (!ok || !data.success) {
                        this.error = data.message || "{{ __('Không thể tạo nội dung. Vui lòng thử lại.') }}";
                        return;
                    }
                    // Đổ nội dung HTML vào đúng trình soạn thảo TinyMCE của template
                    if (data.content) {
                        const editor = window.tinymce ? tinymce.get('email_content_' + this.currentKey) : null;
                        if (editor) {
                            editor.setContent(data.content);
                        } else {
                            const ta = document.getElementById('email_content_' + this.currentKey);
                            if (ta) ta.value = data.content;
                        }
                    }
                    // Cập nhật tiêu đề nếu admin chọn
                    if (this.withSubject && data.subject) {
                        const subj = document.getElementById('email_subject_' + this.currentKey);
                        if (subj) subj.value = data.subject;
                    }
                    this.show = false;
                })
                .catch(() => {
                    this.error = "{{ __('Lỗi kết nối. Vui lòng thử lại.') }}";
                })
                .finally(() => { this.loading = false; });
            },
        };
    }
</script>

{{-- Script: Tự động chèn nút "Nhập mẫu mặc định" vào tất cả email template --}}
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Danh sách các template key cần thêm nút
    const emailKeys = [
        'forgot_password', 'otp', 'verify_email', 'welcome',
        'withdrawal_created', 'withdrawal_approved',
        'gift_approved', 'gift_created',
        'cashback_created', 'cashback_approved'
    ];

    emailKeys.forEach(function(key) {
        // Tìm textarea của template hiện tại
        const textarea = document.getElementById('email_content_' + key);
        if (!textarea) return;

        // Tìm container nút (div.flex.gap-2) gần nhất trong cùng section
        const section = textarea.closest('[x-show]');
        if (!section) return;
        const btnGroup = section.querySelector('.flex.gap-2');
        if (!btnGroup) return;

        // Tạo nút "Nhập mẫu mặc định"
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'inline-flex items-center gap-1 px-2.5 py-1 bg-amber-50 hover:bg-amber-100 text-amber-600 dark:text-amber-400 font-bold rounded-lg text-[10px] border border-amber-100 dark:bg-slate-800 dark:border-slate-700 transition-all cursor-pointer';
        btn.innerHTML = '<i data-lucide="rotate-ccw" class="w-3.5 h-3.5"></i> Nhập mẫu mặc định';
        btn.addEventListener('click', function() { resetEmailDefault(key); });

        // Chèn nút vào đầu nhóm nút
        btnGroup.insertBefore(btn, btnGroup.firstChild);
    });

    // Khởi tạo icon Lucide cho các nút vừa thêm
    if (typeof lucide !== 'undefined') lucide.createIcons();
});

// Gọi API xóa setting tùy chỉnh khỏi DB, Blade fallback sẽ tự hiện mẫu gốc khi reload
async function resetEmailDefault(key) {
    if (!confirm('Bạn có chắc muốn khôi phục mẫu email mặc định?\nCả tiêu đề và nội dung hiện tại sẽ bị thay thế bằng mẫu gốc của hệ thống.')) return;
    try {
        await axios.delete('{{ route("admin.settings.reset_email_default", ["key" => "__KEY__"]) }}'.replace('__KEY__', key));
        window.location.reload();
    } catch(e) {
        alert('Có lỗi xảy ra: ' + (e.response?.data?.message || e.message));
    }
}
</script>
