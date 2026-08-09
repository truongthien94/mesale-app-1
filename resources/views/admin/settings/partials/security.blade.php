{{-- 
    Partial View: Cấu hình bảo mật
    Vai trò: Quản lý tính năng chống Spam bằng Cloudflare Turnstile Captcha tại các trang Đăng ký, Đăng nhập, Quên mật khẩu, Rút tiền.
--}}
<div x-show="tab === 'security'" class="space-y-6" x-transition x-cloak>
    <div class="bg-gray-50/50 dark:bg-slate-900/50 rounded-2xl p-4 sm:p-6 border border-gray-150 dark:border-slate-800 space-y-6 w-full">
        <h3 class="text-xs font-bold text-gray-700 dark:text-slate-300 uppercase tracking-wider pb-2 border-b border-gray-200/65 dark:border-slate-800 flex items-center gap-1.5">
            <i data-lucide="shield-check" class="w-4 h-4 text-shopee"></i>
            {{ __('Bảo mật & Phòng chống tấn công') }}
        </h3>

        <!-- Cấu hình đường dẫn Quản trị -->
        <div class="space-y-4 pb-6 border-b border-gray-200/65 dark:border-slate-800/80">
            <h4 class="text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider pb-1 border-b border-gray-100 dark:border-slate-800/80 flex items-center gap-1.5">
                <i data-lucide="link" class="w-3.5 h-3.5 text-shopee"></i>
                {{ __('Đường dẫn truy cập Admin Panel') }}
            </h4>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Tiền tố Admin -->
                <div>
                    <label for="admin_prefix" class="block text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider mb-1">{{ __('Tiền tố đường dẫn (Admin Prefix)') }}</label>
                    <input type="text"
                        name="admin_prefix"
                        id="admin_prefix"
                        value="{{ $settings['admin_prefix'] ?? 'admin' }}"
                        placeholder="admin"
                        pattern="^[a-zA-Z0-9_-]+$"
                        required
                        autocomplete="off"
                        class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-800 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-300 font-mono">
                    <span class="text-[9px] text-gray-400 mt-1 block">Chỉ cho phép chữ cái không dấu, chữ số, dấu gạch ngang (-) và gạch dưới (_). Mặc định là: <strong>admin</strong>.</span>
                </div>

                <!-- Đường dẫn hiện tại -->
                <div>
                    <label class="block text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider mb-1">{{ __('Đường dẫn truy cập hiện tại') }}</label>
                    <div class="flex items-center px-4 py-2.5 border border-gray-200 dark:border-slate-800 rounded-xl text-xs bg-gray-50 dark:bg-slate-900 text-gray-500 dark:text-slate-400 font-mono break-all select-all">
                        {{ url($settings['admin_prefix'] ?? 'admin') }}
                    </div>
                    <span class="text-[9px] text-red-500 font-semibold mt-1 block">⚠️ Lưu ý: Sau khi đổi, sếp cần đăng nhập lại theo đường dẫn mới này. Hãy lưu lại cẩn thiện để tránh bị mất lối vào quản trị!</span>
                </div>
            </div>

            <!-- Cấu hình hiển thị menu admin ở trang khách -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-4 border-t border-gray-100 dark:border-slate-800/60">
                <div>
                    <label for="show_admin_menu_on_frontend" class="block text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider mb-1">{{ __('Hiển thị menu truy cập Admin Panel ở trang khách') }}</label>
                    <select name="show_admin_menu_on_frontend"
                        id="show_admin_menu_on_frontend"
                        class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-800 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-300">
                        <option value="1" {{ ($settings['show_admin_menu_on_frontend'] ?? '1') === '1' ? 'selected' : '' }}>{{ __('Bật (Hiển thị cho Admin khi ở giao diện ngoài)') }}</option>
                        <option value="0" {{ ($settings['show_admin_menu_on_frontend'] ?? '0') === '0' ? 'selected' : '' }}>{{ __('Tắt (Ẩn nút Admin ở giao diện ngoài)') }}</option>
                    </select>
                    <span class="text-[9px] text-gray-400 mt-1 block">Quyết định việc có hiển thị liên kết truy cập trang quản trị trực tiếp đối với các tài khoản có vai trò quản trị viên khi lướt web ở ngoài trang chủ.</span>
                </div>
            </div>
        </div>

        <!-- 
            Cấu hình Cloudflare Turnstile Captcha 
            Giải thích tại sao đổi: Sử dụng tiền tố 'turnstile_' để khớp chính xác 100% với các khóa kiểm tra
            tại Frontend (Auth views, withdraw view) và Backend (Controllers, Services) nhằm tránh lỗi lệch key khiến captcha không hiển thị.
        -->
        <div class="space-y-4">
            <div class="flex items-center justify-between pb-1 border-b border-gray-100 dark:border-slate-800/80">
                <h4 class="text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider flex items-center gap-1.5">
                    <i data-lucide="shield" class="w-3.5 h-3.5 text-shopee"></i>
                    {{ __('Cloudflare Turnstile Captcha') }}
                </h4>
                <button type="button" @click="guideType = 'captcha'; showGuideModal = true" class="inline-flex items-center gap-1 px-3 py-1 bg-shopee/10 text-shopee hover:bg-shopee hover:text-white text-[10px] font-bold rounded-xl transition-all shadow-sm">
                    <i data-lucide="help-circle" class="w-3.5 h-3.5"></i>
                    {{ __('Hướng dẫn cấu hình') }}
                </button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Trạng thái Captcha -->
                <div>
                    <label for="turnstile_status" class="block text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider mb-1">{{ __('Trạng thái hoạt động') }}</label>
                    <select name="turnstile_status"
                        id="turnstile_status"
                        class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-800 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-300">
                        <option value="1" {{ ($settings['turnstile_status'] ?? '0') === '1' ? 'selected' : '' }}>{{ __('Bật hoạt động (Bảo vệ tất cả các Form)') }}</option>
                        <option value="0" {{ ($settings['turnstile_status'] ?? '0') === '0' ? 'selected' : '' }}>{{ __('Tắt hoạt động (Không yêu cầu Captcha)') }}</option>
                    </select>
                    <span class="text-[9px] text-gray-400 mt-1 block">Bật để yêu cầu xác minh Turnstile tại các form Đăng nhập, Đăng ký, Quên mật khẩu, Rút tiền.</span>
                </div>

                <!-- Các Form áp dụng Captcha -->
                <div>
                    <label class="block text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider mb-1">{{ __('Khu vực áp dụng Captcha') }}</label>
                    <div class="grid grid-cols-2 gap-3 mt-2">
                        <label class="flex items-center gap-2 text-xs font-medium text-gray-700 dark:text-slate-300 cursor-pointer select-none">
                            <input type="hidden" name="turnstile_on_login" value="0">
                            <input type="checkbox" name="turnstile_on_login" value="1" {{ ($settings['turnstile_on_login'] ?? '0') === '1' ? 'checked' : '' }} class="rounded text-shopee focus:ring-shopee border-gray-300">
                            Đăng nhập
                        </label>
                        <label class="flex items-center gap-2 text-xs font-medium text-gray-700 dark:text-slate-300 cursor-pointer select-none">
                            <input type="hidden" name="turnstile_on_register" value="0">
                            <input type="checkbox" name="turnstile_on_register" value="1" {{ ($settings['turnstile_on_register'] ?? '0') === '1' ? 'checked' : '' }} class="rounded text-shopee focus:ring-shopee border-gray-300">
                            Đăng ký
                        </label>
                        <label class="flex items-center gap-2 text-xs font-medium text-gray-700 dark:text-slate-300 cursor-pointer select-none">
                            <input type="hidden" name="turnstile_on_forgot_password" value="0">
                            <input type="checkbox" name="turnstile_on_forgot_password" value="1" {{ ($settings['turnstile_on_forgot_password'] ?? '0') === '1' ? 'checked' : '' }} class="rounded text-shopee focus:ring-shopee border-gray-300">
                            Quên mật khẩu
                        </label>
                        <label class="flex items-center gap-2 text-xs font-medium text-gray-700 dark:text-slate-300 cursor-pointer select-none">
                            <input type="hidden" name="turnstile_on_withdraw" value="0">
                            <input type="checkbox" name="turnstile_on_withdraw" value="1" {{ ($settings['turnstile_on_withdraw'] ?? '0') === '1' ? 'checked' : '' }} class="rounded text-shopee focus:ring-shopee border-gray-300">
                            Rút tiền
                        </label>
                    </div>
                </div>

                <!-- Site Key -->
                <div>
                    <label for="turnstile_site_key" class="block text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider mb-1">{{ __('Turnstile Site Key (Mã công khai)') }}</label>
                    <input type="text"
                        name="turnstile_site_key"
                        id="turnstile_site_key"
                        value="{{ $settings['turnstile_site_key'] ?? '' }}"
                        placeholder="0x4AAAAAA..."
                        autocomplete="off"
                        class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-800 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-300 font-mono">
                    <span class="text-[9px] text-gray-400 mt-1 block">Mã hiển thị Captcha công khai trên trình duyệt của người dùng.</span>
                </div>

                <!-- Secret Key -->
                <div>
                    <label for="turnstile_secret_key" class="block text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider mb-1">{{ __('Turnstile Secret Key (Khóa bí mật)') }}</label>
                    <input type="password"
                        name="turnstile_secret_key"
                        id="turnstile_secret_key"
                        value="{{ $settings['turnstile_secret_key'] ?? '' }}"
                        placeholder="••••••••••••"
                        autocomplete="new-password"
                        class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-800 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-300 font-mono">
                    <span class="text-[9px] text-gray-400 mt-1 block">Khóa bí mật dùng để xác thực phản hồi Captcha từ Cloudflare API trên Máy chủ.</span>
                </div>
            </div>
        </div>

        <!-- Cấu hình chống Brute Force & Khóa Đăng Nhập -->
        <div class="space-y-4 pt-6 border-t border-gray-200/65 dark:border-slate-800/80">
            <h4 class="text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider pb-1 border-b border-gray-100 dark:border-slate-800/80 flex items-center gap-1.5">
                <i data-lucide="lock" class="w-3.5 h-3.5 text-shopee"></i>
                {{ __('Giới hạn đăng nhập sai mật khẩu & Khóa bảo mật') }}
            </h4>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Trạng thái tính năng -->
                <div>
                    <label for="login_lockout_status" class="block text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider mb-1">{{ __('Giới hạn đăng nhập sai') }}</label>
                    <select name="login_lockout_status"
                        id="login_lockout_status"
                        class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-800 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-300">
                        <option value="1" {{ ($settings['login_lockout_status'] ?? '1') === '1' ? 'selected' : '' }}>{{ __('Bật hoạt động') }}</option>
                        <option value="0" {{ ($settings['login_lockout_status'] ?? '1') === '0' ? 'selected' : '' }}>{{ __('Tắt hoạt động') }}</option>
                    </select>
                    <span class="text-[9px] text-gray-400 mt-1 block">Bật để kích hoạt tính năng giới hạn số lần thử mật khẩu và khóa đăng nhập tạm thời.</span>
                </div>

                <!-- Số lần thử sai tối đa -->
                <div>
                    <label for="login_max_attempts" class="block text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider mb-1">{{ __('Số lần thử sai tối đa') }}</label>
                    <input type="number"
                        name="login_max_attempts"
                        id="login_max_attempts"
                        value="{{ $settings['login_max_attempts'] ?? '5' }}"
                        min="3"
                        max="20"
                        class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-800 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-300">
                    <span class="text-[9px] text-gray-400 mt-1 block">Số lần nhập sai mật khẩu tối đa trước khi IP/Email bị khóa đăng nhập tạm thời.</span>
                </div>

                <!-- Thời gian khóa tạm thời -->
                <div>
                    <label for="login_lockout_duration" class="block text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider mb-1">{{ __('Thời gian khóa đăng nhập (Phút)') }}</label>
                    <input type="number"
                        name="login_lockout_duration"
                        id="login_lockout_duration"
                        value="{{ $settings['login_lockout_duration'] ?? '15' }}"
                        min="1"
                        class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-800 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-300">
                    <span class="text-[9px] text-gray-400 mt-1 block">Thời gian tạm khóa tài khoản hoặc IP không cho phép đăng nhập sau khi đạt số lần thử sai (Phút).</span>
                </div>

                <!-- Bật/Tắt tự động khóa IP -->
                <div>
                    <label for="ip_lockout_status" class="block text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider mb-1">{{ __('Tự động khóa IP khi vi phạm') }}</label>
                    <select name="ip_lockout_status"
                        id="ip_lockout_status"
                        class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-800 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-300">
                        <option value="1" {{ ($settings['ip_lockout_status'] ?? '0') === '1' ? 'selected' : '' }}>{{ __('Bật khóa IP') }}</option>
                        <option value="0" {{ ($settings['ip_lockout_status'] ?? '0') === '0' ? 'selected' : '' }}>{{ __('Tắt khóa IP') }}</option>
                    </select>
                    <span class="text-[9px] text-gray-400 mt-1 block">Tự động khóa toàn bộ địa chỉ IP trong khoảng thời gian xác định nếu liên tiếp thử sai mật khẩu nhiều tài khoản.</span>
                </div>

                <!-- Thời gian khóa IP -->
                <div>
                    <label for="ip_lockout_duration" class="block text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider mb-1">{{ __('Thời gian khóa IP (Giờ)') }}</label>
                    <input type="number"
                        name="ip_lockout_duration"
                        id="ip_lockout_duration"
                        value="{{ $settings['ip_lockout_duration'] ?? '24' }}"
                        min="1"
                        class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-800 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-300">
                    <span class="text-[9px] text-gray-400 mt-1 block">Thời gian IP đó bị cấm đăng nhập tạm thời (Tính bằng giờ).</span>
                </div>

                <!-- Bật/Tắt tự động khóa tài khoản -->
                <div>
                    <label for="account_lockout_status" class="block text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider mb-1">{{ __('Tự động khóa tài khoản vĩnh viễn') }}</label>
                    <select name="account_lockout_status"
                        id="account_lockout_status"
                        class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-800 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-300">
                        <option value="1" {{ ($settings['account_lockout_status'] ?? '0') === '1' ? 'selected' : '' }}>{{ __('Bật khóa tài khoản') }}</option>
                        <option value="0" {{ ($settings['account_lockout_status'] ?? '0') === '0' ? 'selected' : '' }}>{{ __('Tắt khóa tài khoản') }}</option>
                    </select>
                    <span class="text-[9px] text-gray-400 mt-1 block">Chuyển trạng thái tài khoản thành 'Ngưng hoạt động' (Suspended) nếu cố gắng dò mật khẩu quá số lần quy định.</span>
                </div>

                <!-- Số lần thử tối đa để khóa tài khoản -->
                <div>
                    <label for="account_lockout_max_attempts" class="block text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider mb-1">{{ __('Số lần thử tối đa để khóa tài khoản') }}</label>
                    <input type="number"
                        name="account_lockout_max_attempts"
                        id="account_lockout_max_attempts"
                        value="{{ $settings['account_lockout_max_attempts'] ?? '10' }}"
                        min="5"
                        class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-800 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-300">
                </div>

                <!-- Giới hạn đăng ký trên 1 IP -->
                <div>
                    <label for="ip_register_limit" class="block text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider mb-1">{{ __('Giới hạn đăng ký tài khoản trên 1 IP') }}</label>
                    <input type="number"
                        name="ip_register_limit"
                        id="ip_register_limit"
                        value="{{ $settings['ip_register_limit'] ?? '5' }}"
                        min="0"
                        class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-800 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-300">
                    <span class="text-[9px] text-gray-400 mt-1 block">{{ __('Số lượng tài khoản tối đa được đăng ký trên cùng một địa chỉ IP (Nhập 0 hoặc để trống nếu không muốn giới hạn).') }}</span>
                </div>

                <!-- Giới hạn tạo link hoàn tiền trong 5 phút của 1 thành viên -->
                <div>
                    <label for="rate_limit_create_link_5m" class="block text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider mb-1">{{ __('Giới hạn tạo link trong 5 phút của 1 thành viên') }}</label>
                    <input type="number"
                        name="rate_limit_create_link_5m"
                        id="rate_limit_create_link_5m"
                        value="{{ $settings['rate_limit_create_link_5m'] ?? '10' }}"
                        min="0"
                        class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-800 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-300">
                    <span class="text-[9px] text-gray-400 mt-1 block">{{ __('Số lượng link hoàn tiền tối đa mà một thành viên được phép tạo trong vòng 5 phút (Nhập 0 hoặc để trống nếu không muốn giới hạn).') }}</span>
                </div>

                <!-- Danh sách đen IP (Banned IPs) -->
                <div class="md:col-span-2">
                    <label for="banned_ips" class="block text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider mb-1">{{ __('Danh sách địa chỉ IP bị cấm thủ công') }}</label>
                    <textarea name="banned_ips"
                        id="banned_ips"
                        rows="4"
                        placeholder="Ví dụ:&#10;192.168.1.1&#10;203.162.4.1"
                        class="block w-full px-4 py-3 border border-gray-200 dark:border-slate-800 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-300 font-mono leading-relaxed">{{ $settings['banned_ips'] ?? '' }}</textarea>
                    <span class="text-[9px] text-gray-400 mt-1 block">Nhập danh sách địa chỉ IP cần cấm truy cập hệ thống đăng nhập, mỗi IP nằm trên một dòng riêng biệt.</span>
                </div>
            </div>
        </div>
        <!-- Cấu hình phòng chống IP ảo (VPN/Proxy/Hosting) -->
        <div class="space-y-4 pt-6 border-t border-gray-200/65 dark:border-slate-800/80">
            <h4 class="text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider pb-1 border-b border-gray-100 dark:border-slate-800/80 flex items-center gap-1.5">
                <i data-lucide="globe" class="w-3.5 h-3.5 text-shopee"></i>
                {{ __('Phòng chống IP ảo (VPN / Proxy / Hosting)') }}
            </h4>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Trạng thái ngăn chặn -->
                <div>
                    <label for="block_vpn_register_status" class="block text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider mb-1">{{ __('Chặn đăng ký bằng VPN/Proxy') }}</label>
                    <select name="block_vpn_register_status"
                        id="block_vpn_register_status"
                        class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-800 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-300">
                        <option value="1" {{ ($settings['block_vpn_register_status'] ?? '0') === '1' ? 'selected' : '' }}>{{ __('Bật (Chặn toàn bộ IP ảo)') }}</option>
                        <option value="0" {{ ($settings['block_vpn_register_status'] ?? '0') === '0' ? 'selected' : '' }}>{{ __('Tắt (Không kiểm tra)') }}</option>
                    </select>
                    <span class="text-[9px] text-gray-400 mt-1 block">Tự động phát hiện và chặn các tài khoản đăng ký sử dụng mạng VPN, Proxy, mạng ẩn danh Tor hoặc địa chỉ IP từ các nhà cung cấp Hosting (VPS) nhằm chống clone.</span>
                </div>

                <!-- API Key ProxyCheck.io -->
                <div>
                    <label for="proxycheck_api_key" class="block text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider mb-1">{{ __('API Key của ProxyCheck.io (Tùy chọn)') }}</label>
                    <input type="text"
                        name="proxycheck_api_key"
                        id="proxycheck_api_key"
                        value="{{ $settings['proxycheck_api_key'] ?? '' }}"
                        placeholder="Ví dụ: 123456-abcdef-..."
                        autocomplete="off"
                        class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-800 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-300 font-mono">
                    <span class="text-[9px] text-gray-400 mt-1 block">Nhập API Key ProxyCheck để tăng giới hạn truy vấn kiểm tra IP lên đến 1,000+ request/ngày. Sếp có thể đăng ký tài khoản miễn phí tại <a href="https://proxycheck.io" target="_blank" class="text-shopee hover:underline font-bold">proxycheck.io</a>.</span>
                </div>
            </div>
        </div>
    </div>
</div>
