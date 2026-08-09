{{-- 
    Partial View: Cấu hình chung hệ thống
    Vai trò: Quản lý thông tin tên website, mô tả SEO, múi giờ, màu chủ đạo của theme, logo, favicon, và thông tin hỗ trợ.
--}}
<div x-show="tab === 'general'" class="space-y-6" x-transition>
    <!-- Cấu hình cơ bản -->
    <div class="bg-gray-50/50 dark:bg-slate-900/50 rounded-2xl p-4 sm:p-6 border border-gray-150 dark:border-slate-800 space-y-6 w-full">
        <h3 class="text-xs font-bold text-gray-700 dark:text-slate-300 uppercase tracking-wider pb-2 border-b border-gray-200/65 dark:border-slate-800 flex items-center gap-1.5">
            <i data-lucide="settings" class="w-4 h-4 text-shopee"></i>
            {{ __('Cấu hình chung hệ thống') }}
        </h3>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label for="site_name" class="block text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider mb-1">{{ __('Tên Website') }}</label>
                <input type="text"
                    name="site_name"
                    id="site_name"
                    value="{{ $settings['site_name'] ?? request()->getHost() }}"
                    class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-800 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-300">
            </div>
            <div>
                <label for="site_description" class="block text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider mb-1">{{ __('Mô tả Website (SEO Meta)') }}</label>
                <input type="text"
                    name="site_description"
                    id="site_description"
                    value="{{ $settings['site_description'] ?? 'Website hoàn tiền mua sắm shopee tự động' }}"
                    class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-800 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-300">
            </div>
        </div>

        <!-- Cấu hình Timezone -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label for="app_timezone" class="block text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider mb-1">{{ __('Múi giờ hệ thống (Timezone)') }}</label>
                <select name="app_timezone"
                    id="app_timezone"
                    class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-800 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-300">
                    @php
                    $currentTimezone = $settings['app_timezone'] ?? config('app.timezone', 'Asia/Ho_Chi_Minh');
                    @endphp
                    @foreach(\DateTimeZone::listIdentifiers() as $tz)
                    <option value="{{ $tz }}" {{ $currentTimezone === $tz ? 'selected' : '' }}>
                        {{ $tz }} (UTC{{ (new \DateTime('now', new \DateTimeZone($tz)))->format('P') }})
                    </option>
                    @endforeach
                </select>
                <span class="text-[10px] text-gray-400 mt-1 block">Chọn múi giờ vận hành hệ thống (khuyên dùng Asia/Ho_Chi_Minh cho Việt Nam).</span>
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider mb-1">{{ __('Thời gian hiện tại của hệ thống') }}</label>
                <div class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-800 rounded-xl text-xs bg-gray-100 dark:bg-slate-850 text-gray-500 font-semibold select-none">
                    {{ now()->format('Y-m-d H:i:s') }} ({{ config('app.timezone') }})
                </div>
                <span class="text-[10px] text-gray-400 mt-1 block">Thời gian thực tế đang được máy chủ ghi nhận theo múi giờ hiện tại.</span>
            </div>
        </div>

        <!-- Cấu hình màu sắc Theme -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6" x-data="{ activeColor: '{{ $settings['theme_color'] ?? '#ee4d2d' }}' }">
            <div>
                <label for="theme_color" class="block text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider mb-1">{{ __('Màu chủ đạo giao diện (Theme Color)') }}</label>
                <div class="flex gap-3">
                    <input type="color"
                        name="theme_color"
                        id="theme_color"
                        x-model="activeColor"
                        class="h-10 w-16 px-1 py-0.5 border border-gray-200 dark:border-slate-800 rounded-xl cursor-pointer bg-white dark:bg-slate-900">
                    <input type="text"
                        id="theme_color_text"
                        x-model="activeColor"
                        placeholder="#ee4d2d"
                        class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-800 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-300">
                </div>
                <span class="text-[10px] text-gray-400 mt-1 block">Chọn màu chủ đạo mong muốn cho giao diện Website (mặc định màu cam Shopee: #ee4d2d).</span>
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider mb-2">{{ __('Gợi ý bảng màu phổ biến') }}</label>
                <div class="flex flex-wrap gap-2.5 mt-1">
                    <button type="button" @click="activeColor = '#ee4d2d'" class="w-8 h-8 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm transition-all duration-200 hover:scale-110 relative flex items-center justify-center cursor-pointer" style="background-color: #ee4d2d;" title="Màu cam Shopee" :class="activeColor.toLowerCase() === '#ee4d2d' ? 'ring-2 ring-offset-2 ring-shopee scale-110' : ''">
                        <i data-lucide="check" class="w-4 h-4 text-white" x-show="activeColor.toLowerCase() === '#ee4d2d'"></i>
                    </button>
                    <button type="button" @click="activeColor = '#3b82f6'" class="w-8 h-8 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm transition-all duration-200 hover:scale-110 relative flex items-center justify-center cursor-pointer" style="background-color: #3b82f6;" title="Xanh Dương" :class="activeColor.toLowerCase() === '#3b82f6' ? 'ring-2 ring-offset-2 ring-blue-500 scale-110' : ''">
                        <i data-lucide="check" class="w-4 h-4 text-white" x-show="activeColor.toLowerCase() === '#3b82f6'"></i>
                    </button>
                    <button type="button" @click="activeColor = '#ef4444'" class="w-8 h-8 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm transition-all duration-200 hover:scale-110 relative flex items-center justify-center cursor-pointer" style="background-color: #ef4444;" title="Đỏ Ruby" :class="activeColor.toLowerCase() === '#ef4444' ? 'ring-2 ring-offset-2 ring-red-500 scale-110' : ''">
                        <i data-lucide="check" class="w-4 h-4 text-white" x-show="activeColor.toLowerCase() === '#ef4444'"></i>
                    </button>
                    <button type="button" @click="activeColor = '#10b981'" class="w-8 h-8 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm transition-all duration-200 hover:scale-110 relative flex items-center justify-center cursor-pointer" style="background-color: #10b981;" title="Xanh Ngọc" :class="activeColor.toLowerCase() === '#10b981' ? 'ring-2 ring-offset-2 ring-emerald-500 scale-110' : ''">
                        <i data-lucide="check" class="w-4 h-4 text-white" x-show="activeColor.toLowerCase() === '#10b981'"></i>
                    </button>
                    <button type="button" @click="activeColor = '#8b5cf6'" class="w-8 h-8 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm transition-all duration-200 hover:scale-110 relative flex items-center justify-center cursor-pointer" style="background-color: #8b5cf6;" title="Tím Neon" :class="activeColor.toLowerCase() === '#8b5cf6' ? 'ring-2 ring-offset-2 ring-violet-500 scale-110' : ''">
                        <i data-lucide="check" class="w-4 h-4 text-white" x-show="activeColor.toLowerCase() === '#8b5cf6'"></i>
                    </button>
                    <button type="button" @click="activeColor = '#ec4899'" class="w-8 h-8 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm transition-all duration-200 hover:scale-110 relative flex items-center justify-center cursor-pointer" style="background-color: #ec4899;" title="Hồng Premium" :class="activeColor.toLowerCase() === '#ec4899' ? 'ring-2 ring-offset-2 ring-pink-500 scale-110' : ''">
                        <i data-lucide="check" class="w-4 h-4 text-white" x-show="activeColor.toLowerCase() === '#ec4899'"></i>
                    </button>
                    <button type="button" @click="activeColor = '#0f172a'" class="w-8 h-8 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm transition-all duration-200 hover:scale-110 relative flex items-center justify-center cursor-pointer" style="background-color: #0f172a;" title="Đen Huyền Bí" :class="activeColor.toLowerCase() === '#0f172a' ? 'ring-2 ring-offset-2 ring-slate-800 scale-110' : ''">
                        <i data-lucide="check" class="w-4 h-4 text-white" x-show="activeColor.toLowerCase() === '#0f172a'"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Cấu hình Giao diện mặc định (Default Theme) và Hiệu ứng bong bóng cho hệ thống khi người dùng mới truy cập -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
            <div>
                <label for="default_theme" class="block text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider mb-1">{{ __('Giao diện mặc định (Default Theme)') }}</label>
                <select name="default_theme"
                    id="default_theme"
                    class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-800 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-300">
                    <option value="light" {{ ($settings['default_theme'] ?? 'light') === 'light' ? 'selected' : '' }}>{{ __('Chế độ sáng (Light Mode)') }}</option>
                    <option value="dark" {{ ($settings['default_theme'] ?? 'light') === 'dark' ? 'selected' : '' }}>{{ __('Chế độ tối (Dark Mode)') }}</option>
                    <option value="system" {{ ($settings['default_theme'] ?? 'light') === 'system' ? 'selected' : '' }}>{{ __('Theo thiết bị người dùng (System)') }}</option>
                </select>
                <span class="text-[10px] text-gray-400 mt-1 block">Thiết lập giao diện mặc định (Sáng, Tối hoặc Tự động theo thiết bị) cho người dùng trong lần đầu truy cập khi chưa thiết lập giao diện cá nhân.</span>
            </div>

            <!-- Cấu hình bật/tắt hiệu ứng bong bóng lơ lửng tại trang chủ -->
            <div>
                <label for="hp_enable_bubble_effect" class="block text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider mb-1">{{ __('Hiệu ứng bong bóng trang chủ') }}</label>
                <select name="hp_enable_bubble_effect"
                    id="hp_enable_bubble_effect"
                    class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-800 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-300">
                    <option value="1" {{ ($settings['hp_enable_bubble_effect'] ?? '1') === '1' ? 'selected' : '' }}>{{ __('Bật hiệu ứng bong bóng') }}</option>
                    <option value="0" {{ ($settings['hp_enable_bubble_effect'] ?? '1') === '0' ? 'selected' : '' }}>{{ __('Tắt hiệu ứng bong bóng') }}</option>
                </select>
                <span class="text-[10px] text-gray-400 mt-1 block">Bật hoặc tắt hiệu ứng bong bóng nổi lơ lửng và các vòng tròn background mờ ở trang chủ Storefront.</span>
            </div>
        </div>

        <div class="border-t border-gray-200/65 dark:border-slate-800/80 my-5"></div>

        <!-- Cấu hình chế độ bảo trì -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div>
                <label for="maintenance_mode" class="block text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider mb-1">{{ __('Chế độ bảo trì') }}</label>
                <select name="maintenance_mode"
                    id="maintenance_mode"
                    class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-800 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-300">
                    <option value="0" {{ ($settings['maintenance_mode'] ?? '0') == '0' ? 'selected' : '' }}>{{ __('Hoạt động bình thường') }}</option>
                    <option value="1" {{ ($settings['maintenance_mode'] ?? '0') == '1' ? 'selected' : '' }}>{{ __('Bật chế độ bảo trì') }}</option>
                </select>
                <span class="text-[10px] text-gray-400 mt-1 block">Khi bật, khách truy cập (trừ quản lý) sẽ thấy thông báo bảo trì.</span>
            </div>
            <div class="md:col-span-2">
                <label for="maintenance_message" class="block text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider mb-1">{{ __('Lời nhắn bảo trì') }}</label>
                <input type="text"
                    name="maintenance_message"
                    id="maintenance_message"
                    value="{{ $settings['maintenance_message'] ?? 'Hệ thống đang bảo trì nâng cấp định kỳ. Vui lòng quay lại sau!' }}"
                    class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-800 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-300">
                <span class="text-[10px] text-gray-400 mt-1 block">Lời nhắn hiển thị tới khách hàng khi website đang trong trạng thái bảo trì.</span>
            </div>
        </div>

        <div class="border-t border-gray-200/65 dark:border-slate-800/80 my-5"></div>

        <!-- Cấu hình tự động cập nhật hệ thống -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label for="auto_update" class="block text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider mb-1">{{ __('Tự động cập nhật hệ thống') }}</label>
                <select name="auto_update"
                    id="auto_update"
                    class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-800 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-300">
                    <option value="0" {{ ($settings['auto_update'] ?? '0') == '0' ? 'selected' : '' }}>{{ __('Tắt tự động cập nhật') }}</option>
                    <option value="1" {{ ($settings['auto_update'] ?? '0') == '1' ? 'selected' : '' }}>{{ __('Bật tự động cập nhật') }}</option>
                </select>
                <span class="text-[10px] text-gray-400 mt-1 block">Khi bật, hệ thống sẽ sử dụng cron job kiểm tra phiên bản mới mỗi 30 phút và tự động tải, giải nén, cài đặt nếu có bản cập nhật mới.</span>
            </div>
        </div>
    </div>

    <!-- Cấu hình Toast Notifications -->
    <div class="bg-gray-50/50 dark:bg-slate-900/50 rounded-2xl p-4 sm:p-6 border border-gray-150 dark:border-slate-800 space-y-6 w-full">
        <h3 class="text-xs font-bold text-gray-700 dark:text-slate-300 uppercase tracking-wider pb-2 border-b border-gray-200/65 dark:border-slate-800 flex items-center gap-1.5">
            <i data-lucide="bell-ring" class="w-4 h-4 text-shopee"></i>
            {{ __('Cấu hình thông báo Toast (Toast Notifications)') }}
        </h3>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div>
                <label for="toast_style" class="block text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider mb-1">{{ __('Kiểu thư viện hiển thị') }}</label>
                <select name="toast_style"
                    id="toast_style"
                    class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-800 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-300">
                    <option value="alpine" {{ ($settings['toast_style'] ?? 'alpine') === 'alpine' ? 'selected' : '' }}>{{ __('Custom Glassmorphism (AlpineJS)') }}</option>
                    <option value="sweetalert2" {{ ($settings['toast_style'] ?? 'alpine') === 'sweetalert2' ? 'selected' : '' }}>{{ __('SweetAlert2 Toast') }}</option>
                    <option value="izitoast" {{ ($settings['toast_style'] ?? 'alpine') === 'izitoast' ? 'selected' : '' }}>{{ __('iziToast') }}</option>
                    <option value="notyf" {{ ($settings['toast_style'] ?? 'alpine') === 'notyf' ? 'selected' : '' }}>{{ __('Notyf') }}</option>
                    <option value="notiflix" {{ ($settings['toast_style'] ?? 'alpine') === 'notiflix' ? 'selected' : '' }}>{{ __('Notiflix Notify') }}</option>
                    <option value="toastr" {{ ($settings['toast_style'] ?? 'alpine') === 'toastr' ? 'selected' : '' }}>{{ __('Toastr.js') }}</option>
                </select>
                <span class="text-[10px] text-gray-400 mt-1 block">Chọn thư viện hiển thị thông báo góc màn hình khi tương tác.</span>
            </div>

            <div>
                <label for="toast_position" class="block text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider mb-1">{{ __('Vị trí hiển thị') }}</label>
                <select name="toast_position"
                    id="toast_position"
                    class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-800 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-300">
                    <option value="top-left" {{ ($settings['toast_position'] ?? 'top-right') === 'top-left' ? 'selected' : '' }}>{{ __('Góc trên bên trái') }}</option>
                    <option value="top-center" {{ ($settings['toast_position'] ?? 'top-right') === 'top-center' ? 'selected' : '' }}>{{ __('Góc trên ở giữa') }}</option>
                    <option value="top-right" {{ ($settings['toast_position'] ?? 'top-right') === 'top-right' ? 'selected' : '' }}>{{ __('Góc trên bên phải') }}</option>
                    <option value="bottom-left" {{ ($settings['toast_position'] ?? 'top-right') === 'bottom-left' ? 'selected' : '' }}>{{ __('Góc dưới bên trái') }}</option>
                    <option value="bottom-center" {{ ($settings['toast_position'] ?? 'top-right') === 'bottom-center' ? 'selected' : '' }}>{{ __('Góc dưới ở giữa') }}</option>
                    <option value="bottom-right" {{ ($settings['toast_position'] ?? 'top-right') === 'bottom-right' ? 'selected' : '' }}>{{ __('Góc dưới bên phải') }}</option>
                </select>
                <span class="text-[10px] text-gray-400 mt-1 block">Chọn góc hiển thị của thông báo trên màn hình (đối với thư viện tương thích).</span>
            </div>

            <div>
                <label for="toast_duration" class="block text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider mb-1">{{ __('Thời gian tự đóng (ms)') }}</label>
                <input type="number"
                    name="toast_duration"
                    id="toast_duration"
                    min="1000"
                    step="500"
                    value="{{ $settings['toast_duration'] ?? 3000 }}"
                    class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-800 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-300">
                <span class="text-[10px] text-gray-400 mt-1 block">Thời gian tự đóng thông báo tính bằng mili giây (1s = 1000ms, mặc định 3000ms).</span>
            </div>
        </div>
    </div>


    <!-- 
        Cấu hình thông báo nổi (popup) tự động hiển thị ở trang chủ Storefront & cấu hình PWA.
        Lý do: Giúp Admin dễ dàng truyền tải thông báo quan trọng và kiểm soát hiển thị popup cài đặt ứng dụng (PWA).
    -->
    <div class="bg-gray-50/50 dark:bg-slate-900/50 rounded-2xl p-4 sm:p-6 border border-gray-150 dark:border-slate-800 space-y-6 w-full">
        <h3 class="text-xs font-bold text-gray-700 dark:text-slate-300 uppercase tracking-wider pb-2 border-b border-gray-200/65 dark:border-slate-800 flex items-center gap-1.5">
            <i data-lucide="info" class="w-4 h-4 text-shopee"></i>
            {{ __('Thông báo Popup & Cài đặt ứng dụng (PWA)') }}
        </h3>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label for="home_popup_status" class="block text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider mb-1">{{ __('Trạng thái thông báo Popup') }}</label>
                <select name="home_popup_status"
                    id="home_popup_status"
                    class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-800 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-300">
                    <option value="0" {{ ($settings['home_popup_status'] ?? '0') == '0' ? 'selected' : '' }}>{{ __('Tắt thông báo') }}</option>
                    <option value="1" {{ ($settings['home_popup_status'] ?? '0') == '1' ? 'selected' : '' }}>{{ __('Bật thông báo') }}</option>
                </select>
                <span class="text-[10px] text-gray-400 mt-1 block">{{ __('Bật hoặc tắt hiển thị hộp thoại thông báo (popup) tự động xuất hiện khi người dùng truy cập trang chủ.') }}</span>
            </div>

            <div>
                <label for="pwa_install_prompt_status" class="block text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider mb-1">{{ __('Trạng thái gợi ý cài đặt ứng dụng (PWA)') }}</label>
                <select name="pwa_install_prompt_status"
                    id="pwa_install_prompt_status"
                    class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-800 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-300">
                    <option value="1" {{ ($settings['pwa_install_prompt_status'] ?? '1') == '1' ? 'selected' : '' }}>{{ __('Bật gợi ý cài đặt') }}</option>
                    <option value="0" {{ ($settings['pwa_install_prompt_status'] ?? '1') == '0' ? 'selected' : '' }}>{{ __('Tắt gợi ý cài đặt') }}</option>
                </select>
                <span class="text-[10px] text-gray-400 mt-1 block">{{ __('Bật hoặc tắt hiển thị hộp thoại gợi ý cài đặt website thành ứng dụng di động (PWA) sau 3 giây khi truy cập.') }}</span>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6">
            <div x-data="popupAiGenerator()">
                <div class="flex items-center justify-between mb-1">
                    <label for="home_popup_content" class="block text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider">{{ __('Nội dung thông báo (Hỗ trợ HTML / Ảnh / Video)') }}</label>
                    <button type="button" @click="open()"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-[11px] font-semibold text-white bg-gradient-to-r from-violet-500 to-fuchsia-500 hover:from-violet-600 hover:to-fuchsia-600 rounded-lg transition-all shadow-sm shadow-violet-500/20">
                        <i data-lucide="sparkles" class="w-3.5 h-3.5"></i>
                        {{ __('Tạo bằng AI') }}
                    </button>
                </div>
                <textarea name="home_popup_content"
                    id="home_popup_content"
                    rows="6"
                    class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-800 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-300"
                    placeholder="{{ __('Nhập nội dung thông báo hiển thị tại đây...') }}">{{ $settings['home_popup_content'] ?? '' }}</textarea>
                <span class="text-[10px] text-gray-400 mt-1 block">{{ __('Nội dung hiển thị bên trong popup. Bạn có thể sử dụng HTML để định dạng hoặc nhúng hình ảnh/nội dung tùy biến.') }}</span>

                {{-- Modal tạo nội dung popup bằng AI --}}
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
                                    {{ __('Tạo nội dung popup bằng AI') }}
                                </h3>
                                <p class="text-xs text-gray-500 dark:text-slate-400 mt-0.5">{{ __('Mô tả thông báo, AI sẽ soạn HTML hiển thị trong popup') }}</p>
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
                                    {{ __('Mô tả nội dung thông báo') }} <span class="text-red-500">*</span>
                                </label>
                                <textarea x-model="prompt" rows="4"
                                          placeholder="{{ __('VD: Thông báo chương trình hoàn tiền 50% nhân dịp sinh nhật, kèm nút Mua sắm ngay...') }}"
                                          class="w-full px-4 py-2.5 text-sm border border-gray-200 dark:border-slate-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-violet-500/20 focus:border-violet-500 bg-gray-50/50 dark:bg-slate-800 dark:text-slate-200"></textarea>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-gray-600 dark:text-slate-400 uppercase tracking-wider mb-1.5">
                                    {{ __('Giọng điệu') }}
                                </label>
                                <select x-model="tone"
                                        class="w-full px-3 py-2.5 text-xs border border-gray-200 dark:border-slate-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-violet-500/20 focus:border-violet-500 bg-gray-50/50 dark:bg-slate-800 dark:text-slate-200">
                                    <option value="than-thien">{{ __('Thân thiện, gần gũi') }}</option>
                                    <option value="chuyen-nghiep">{{ __('Chuyên nghiệp, trang trọng') }}</option>
                                    <option value="khan-cap">{{ __('Khẩn cấp, thúc đẩy hành động') }}</option>
                                    <option value="hao-hung">{{ __('Hào hứng, nhiều cảm xúc') }}</option>
                                </select>
                            </div>

                            <div class="p-3 bg-violet-50 dark:bg-violet-950/20 rounded-xl border border-violet-100 dark:border-violet-900/30 flex gap-2">
                                <i data-lucide="info" class="w-3.5 h-3.5 text-violet-500 shrink-0 mt-0.5"></i>
                                <p class="text-[10px] text-violet-700 dark:text-violet-400 leading-relaxed">
                                    {{ __('Nội dung hiện tại trong trình soạn thảo sẽ bị thay thế bằng kết quả AI tạo ra.') }}
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
    </div>

    <!-- Cấu hình Hình ảnh (Logo, Favicon, OG Image) -->
    <div class="bg-gray-50/50 dark:bg-slate-900/50 rounded-2xl p-4 sm:p-6 border border-gray-150 dark:border-slate-800 space-y-6 w-full">
        <h3 class="text-xs font-bold text-gray-700 dark:text-slate-300 uppercase tracking-wider pb-2 border-b border-gray-200/65 dark:border-slate-800 flex items-center gap-1.5">
            <i data-lucide="image" class="w-4 h-4 text-shopee"></i>
            {{ __('Hình ảnh & Nhận diện thương hiệu') }}
        </h3>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
            <!-- 1. Logo Light -->
            <div class="space-y-2 p-4 bg-gray-50/50 rounded-2xl border border-gray-100 dark:bg-slate-900/50 dark:border-slate-800 flex flex-col justify-between">
                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider">{{ __('Logo Website (Light)') }}</label>

                <div class="space-y-3 flex-grow flex flex-col justify-end">
                    <!-- Preview Image -->
                    <div class="h-20 w-full flex items-center justify-center bg-white dark:bg-slate-900 border border-dashed border-gray-200 dark:border-slate-800 rounded-xl overflow-hidden relative">
                        <template x-if="siteLogo">
                            <div class="w-full h-full relative group flex items-center justify-center bg-white dark:bg-slate-900 p-2">
                                <img :src="siteLogo" alt="Logo Preview" class="max-h-16 max-w-full object-contain">
                                <button type="button" @click="siteLogo = ''; document.getElementById('logo-file-input').value = ''" class="absolute top-1 right-1 bg-red-500 hover:bg-red-600 text-white rounded-full p-1 shadow-md opacity-0 group-hover:opacity-100 transition-opacity">
                                    <i data-lucide="trash-2" class="w-3 h-3"></i>
                                </button>
                            </div>
                        </template>
                        <template x-if="!siteLogo">
                            <div class="text-center text-gray-400">
                                <i data-lucide="image" class="w-5 h-5 mx-auto mb-1 text-gray-300"></i>
                                <span class="text-[9px]">{{ __('Chưa có Logo') }}</span>
                            </div>
                        </template>
                    </div>

                    <!-- Input Fields -->
                    <div class="space-y-2">
                        <!-- Nhập/Lưu URL ảnh -->
                        <input type="hidden" name="site_logo" id="site_logo" x-model="siteLogo">

                        <div class="grid grid-cols-2 gap-1.5">
                            <!-- Tải file lên -->
                            <div>
                                <input type="file"
                                    name="logo_file"
                                    id="logo-file-input"
                                    accept="image/*"
                                    @change="
                                            const file = $event.target.files[0];
                                            if (file) {
                                                siteLogo = URL.createObjectURL(file);
                                            }
                                       "
                                    class="hidden">
                                <label for="logo-file-input" class="w-full flex items-center justify-center gap-1 py-1.5 text-[9px] font-bold text-gray-700 bg-white border border-gray-200 hover:bg-gray-50 rounded-lg cursor-pointer transition-all dark:text-slate-300 dark:bg-slate-800 dark:border-slate-700">
                                    <i data-lucide="upload" class="w-2.5 h-2.5"></i> {{ __('Tải lên') }}
                                </label>
                            </div>

                            <!-- Chọn từ elFinder -->
                            <button type="button"
                                @click="openElfinderPopup('site_logo')"
                                class="w-full flex items-center justify-center gap-1 py-1.5 text-[9px] font-bold text-shopee bg-shopee/10 border border-shopee/20 hover:bg-shopee/20 rounded-lg transition-all">
                                <i data-lucide="folder-open" class="w-2.5 h-2.5"></i> {{ __('Thư viện') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 1b. Logo Website (Dark) -->
            <div class="space-y-2 p-4 bg-gray-50/50 rounded-2xl border border-gray-100 dark:bg-slate-900/50 dark:border-slate-800 flex flex-col justify-between">
                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider">{{ __('Logo Website (Dark)') }}</label>

                <div class="space-y-3 flex-grow flex flex-col justify-end">
                    <!-- Preview Image -->
                    <div class="h-20 w-full flex items-center justify-center bg-slate-800 border border-dashed border-gray-750 rounded-xl overflow-hidden relative">
                        <template x-if="siteLogoDark">
                            <div class="w-full h-full relative group flex items-center justify-center bg-slate-800 p-2">
                                <img :src="siteLogoDark" alt="Logo Dark Preview" class="max-h-16 max-w-full object-contain">
                                <button type="button" @click="siteLogoDark = ''; document.getElementById('logo-dark-file-input').value = ''" class="absolute top-1 right-1 bg-red-500 hover:bg-red-600 text-white rounded-full p-1 shadow-md opacity-0 group-hover:opacity-100 transition-opacity">
                                    <i data-lucide="trash-2" class="w-3 h-3"></i>
                                </button>
                            </div>
                        </template>
                        <template x-if="!siteLogoDark">
                            <div class="text-center text-gray-400">
                                <i data-lucide="image" class="w-5 h-5 mx-auto mb-1 text-gray-500"></i>
                                <span class="text-[9px]">{{ __('Chưa có Logo Dark') }}</span>
                            </div>
                        </template>
                    </div>

                    <!-- Input Fields -->
                    <div class="space-y-2">
                        <!-- Nhập/Lưu URL ảnh -->
                        <input type="hidden" name="site_logo_dark" id="site_logo_dark" x-model="siteLogoDark">

                        <div class="grid grid-cols-2 gap-1.5">
                            <!-- Tải file lên -->
                            <div>
                                <input type="file"
                                    name="logo_dark_file"
                                    id="logo-dark-file-input"
                                    accept="image/*"
                                    @change="
                                            const file = $event.target.files[0];
                                            if (file) {
                                                siteLogoDark = URL.createObjectURL(file);
                                            }
                                       "
                                    class="hidden">
                                <label for="logo-dark-file-input" class="w-full flex items-center justify-center gap-1 py-1.5 text-[9px] font-bold text-gray-700 bg-white border border-gray-200 hover:bg-gray-50 rounded-lg cursor-pointer transition-all dark:text-slate-350 dark:bg-slate-800 dark:border-slate-700">
                                    <i data-lucide="upload" class="w-2.5 h-2.5"></i> {{ __('Tải lên') }}
                                </label>
                            </div>

                            <!-- Chọn từ elFinder -->
                            <button type="button"
                                @click="openElfinderPopup('site_logo_dark')"
                                class="w-full flex items-center justify-center gap-1 py-1.5 text-[9px] font-bold text-shopee bg-shopee/10 border border-shopee/20 hover:bg-shopee/20 rounded-lg transition-all">
                                <i data-lucide="folder-open" class="w-2.5 h-2.5"></i> {{ __('Thư viện') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 2. Favicon -->
            <div class="space-y-2 p-4 bg-gray-50/50 rounded-2xl border border-gray-100 dark:bg-slate-900/50 dark:border-slate-800 flex flex-col justify-between">
                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider">{{ __('Favicon Website') }}</label>

                <div class="space-y-3 flex-grow flex flex-col justify-end">
                    <!-- Preview Image -->
                    <div class="h-20 w-full flex items-center justify-center bg-white dark:bg-slate-900 border border-dashed border-gray-200 dark:border-slate-800 rounded-xl overflow-hidden relative">
                        <template x-if="siteFavicon">
                            <div class="w-full h-full relative group flex items-center justify-center bg-white dark:bg-slate-900 p-2">
                                <img :src="siteFavicon" alt="Favicon Preview" class="h-10 w-10 object-contain">
                                <button type="button" @click="siteFavicon = ''; document.getElementById('favicon-file-input').value = ''" class="absolute top-1 right-1 bg-red-500 hover:bg-red-600 text-white rounded-full p-1 shadow-md opacity-0 group-hover:opacity-100 transition-opacity">
                                    <i data-lucide="trash-2" class="w-3 h-3"></i>
                                </button>
                            </div>
                        </template>
                        <template x-if="!siteFavicon">
                            <div class="text-center text-gray-400">
                                <i data-lucide="image" class="w-5 h-5 mx-auto mb-1 text-gray-300"></i>
                                <span class="text-[9px]">{{ __('Chưa có Favicon') }}</span>
                            </div>
                        </template>
                    </div>

                    <!-- Input Fields -->
                    <div class="space-y-2">
                        <!-- Nhập/Lưu URL ảnh -->
                        <input type="hidden" name="site_favicon" id="site_favicon" x-model="siteFavicon">

                        <div class="grid grid-cols-2 gap-1.5">
                            <!-- Tải file lên -->
                            <div>
                                <input type="file"
                                    name="favicon_file"
                                    id="favicon-file-input"
                                    accept="image/*"
                                    @change="
                                            const file = $event.target.files[0];
                                            if (file) {
                                                siteFavicon = URL.createObjectURL(file);
                                            }
                                       "
                                    class="hidden">
                                <label for="favicon-file-input" class="w-full flex items-center justify-center gap-1 py-1.5 text-[9px] font-bold text-gray-700 bg-white border border-gray-200 hover:bg-gray-50 rounded-lg cursor-pointer transition-all dark:text-slate-300 dark:bg-slate-800 dark:border-slate-700">
                                    <i data-lucide="upload" class="w-2.5 h-2.5"></i> {{ __('Tải lên') }}
                                </label>
                            </div>

                            <!-- Chọn từ elFinder -->
                            <button type="button"
                                @click="openElfinderPopup('site_favicon')"
                                class="w-full flex items-center justify-center gap-1 py-1.5 text-[9px] font-bold text-shopee bg-shopee/10 border border-shopee/20 hover:bg-shopee/20 rounded-lg transition-all">
                                <i data-lucide="folder-open" class="w-2.5 h-2.5"></i> {{ __('Thư viện') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 3. OG Image -->
            <div class="space-y-2 p-4 bg-gray-50/50 rounded-2xl border border-gray-100 dark:bg-slate-900/50 dark:border-slate-800 flex flex-col justify-between">
                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider">{{ __('Ảnh share MXH (SEO)') }}</label>

                <div class="space-y-3 flex-grow flex flex-col justify-end">
                    <!-- Preview Image -->
                    <div class="h-20 w-full flex items-center justify-center bg-white dark:bg-slate-900 border border-dashed border-gray-200 dark:border-slate-800 rounded-xl overflow-hidden relative">
                        <template x-if="siteOgImage">
                            <div class="w-full h-full relative group">
                                <img :src="siteOgImage" alt="OG Image Preview" class="h-full w-full object-cover">
                                <button type="button" @click="siteOgImage = ''; document.getElementById('og-image-file-input').value = ''" class="absolute top-1 right-1 bg-red-500 hover:bg-red-600 text-white rounded-full p-1 shadow-md opacity-0 group-hover:opacity-100 transition-opacity">
                                    <i data-lucide="trash-2" class="w-3 h-3"></i>
                                </button>
                            </div>
                        </template>
                        <template x-if="!siteOgImage">
                            <div class="text-center text-gray-400">
                                <i data-lucide="image" class="w-5 h-5 mx-auto mb-1 text-gray-300"></i>
                                <span class="text-[9px]">{{ __('Chưa có ảnh share') }}</span>
                            </div>
                        </template>
                    </div>

                    <!-- Input Fields -->
                    <div class="space-y-2">
                        <!-- Nhập/Lưu URL ảnh -->
                        <input type="hidden" name="site_og_image" id="site_og_image" x-model="siteOgImage">

                        <div class="grid grid-cols-2 gap-1.5">
                            <!-- Tải file lên -->
                            <div>
                                <input type="file"
                                    name="og_image_file"
                                    id="og-image-file-input"
                                    accept="image/*"
                                    @change="
                                            const file = $event.target.files[0];
                                            if (file) {
                                                siteOgImage = URL.createObjectURL(file);
                                            }
                                       "
                                    class="hidden">
                                <label for="og-image-file-input" class="w-full flex items-center justify-center gap-1 py-1.5 text-[9px] font-bold text-gray-700 bg-white border border-gray-200 hover:bg-gray-50 rounded-lg cursor-pointer transition-all dark:text-slate-300 dark:bg-slate-800 dark:border-slate-700">
                                    <i data-lucide="upload" class="w-2.5 h-2.5"></i> {{ __('Tải lên') }}
                                </label>
                            </div>

                            <!-- Chọn từ elFinder -->
                            <button type="button"
                                @click="openElfinderPopup('site_og_image')"
                                class="w-full flex items-center justify-center gap-1 py-1.5 text-[9px] font-bold text-shopee bg-shopee/10 border border-shopee/20 hover:bg-shopee/20 rounded-lg transition-all">
                                <i data-lucide="folder-open" class="w-2.5 h-2.5"></i> {{ __('Thư viện') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Mẹo sử dụng AI và công cụ cắt ảnh cho Logo -->
        <div class="p-4 bg-amber-50 dark:bg-amber-950/20 rounded-xl border border-amber-200 dark:border-amber-900/30 flex gap-3 text-xs leading-relaxed text-amber-800 dark:text-amber-300">
            <i data-lucide="lightbulb" class="w-4 h-4 text-amber-500 shrink-0 mt-0.5 animate-pulse"></i>
            <div>
                <span class="font-bold">{{ __('Mẹo hữu ích:') }}</span>
                {{ __('Bạn có thể sử dụng') }}
                <a href="https://chatgpt.com/" class="text-amber-500 underline font-semibold" target="_blank">ChatGPT</a>
                {{ __('để tạo logo không nền. Sau đó, sử dụng công cụ cắt ảnh tại') }}
                <a href="https://www.img2go.com/vi/crop-image" target="_blank" rel="noopener noreferrer" class="font-bold underline text-amber-600 dark:text-amber-400 hover:text-shopee dark:hover:text-shopee-light transition-colors">img2go.com</a>
                {{ __('để cắt bớt khoảng trắng dư thừa giúp logo hiển thị cân đối và đẹp mắt nhất.') }}
            </div>
        </div>
    </div>

    <!-- Cấu hình thông tin hỗ trợ -->
    <div class="bg-gray-50/50 dark:bg-slate-900/50 rounded-2xl p-4 sm:p-6 border border-gray-150 dark:border-slate-800 space-y-6 w-full">
        <h3 class="text-xs font-bold text-gray-700 dark:text-slate-300 uppercase tracking-wider pb-2 border-b border-gray-200/65 dark:border-slate-800 flex items-center gap-1.5">
            <i data-lucide="help-circle" class="w-4 h-4 text-shopee"></i>
            {{ __('Thông tin liên hệ & Hỗ trợ') }}
        </h3>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div>
                <label for="support_email" class="block text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider mb-1">{{ __('Email Hỗ Trợ') }}</label>
                <input type="email"
                    name="support_email"
                    id="support_email"
                    value="{{ $settings['support_email'] ?? 'support@hoantienshopee.ddev.site' }}"
                    class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-800 rounded-xl text-xs bg-white dark:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee text-gray-900 dark:text-white">
                <span class="text-[10px] text-gray-400 dark:text-gray-500 mt-1 block">Email hiển thị ở chân trang (Footer) hỗ trợ khách hàng.</span>
            </div>
            <div>
                <label for="support_hotline" class="block text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider mb-1">{{ __('Số điện thoại / Hotline') }}</label>
                <input type="text"
                    name="support_hotline"
                    id="support_hotline"
                    value="{{ $settings['support_hotline'] ?? '1900 1234' }}"
                    class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-800 rounded-xl text-xs bg-white dark:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee text-gray-900 dark:text-white">
                <span class="text-[10px] text-gray-400 dark:text-gray-500 mt-1 block">Hotline liên hệ hiển thị ở chân trang.</span>
            </div>
            <div>
                <label for="support_work_time" class="block text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider mb-1">{{ __('Thời gian làm việc / Ghi chú') }}</label>
                <input type="text"
                    name="support_work_time"
                    id="support_work_time"
                    value="{{ $settings['support_work_time'] ?? 'Tổng đài (8h - 18h)' }}"
                    class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-800 rounded-xl text-xs bg-white dark:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee text-gray-900 dark:text-white">
                <span class="text-[10px] text-gray-400 dark:text-gray-500 mt-1 block">Ghi chú thời gian hoạt động hiển thị ở chân trang.</span>
            </div>
        </div>

        <!-- 
            Phần liên kết mạng xã hội & kênh hỗ trợ liên lạc trực tuyến
            Lý do: Giúp admin điền link hỗ trợ (Telegram, Zalo, Facebook, YouTube, TikTok) để tích hợp vào footer, giúp người dùng dễ dàng kết nối.
        -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div>
                <label for="telegram_link" class="block text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider mb-1">{{ __('Kênh / Nhóm Telegram') }}</label>
                <input type="url"
                    name="telegram_link"
                    id="telegram_link"
                    value="{{ $settings['telegram_link'] ?? '' }}"
                    placeholder="https://t.me/your_channel"
                    class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-800 rounded-xl text-xs bg-white dark:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee text-gray-900 dark:text-white">
                <span class="text-[10px] text-gray-400 dark:text-gray-500 mt-1 block">Liên kết Telegram hỗ trợ khách hàng (ẩn nếu bỏ trống).</span>
            </div>
            <div>
                <label for="zalo_link" class="block text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider mb-1">{{ __('Liên kết Zalo (Cá nhân/OA)') }}</label>
                <input type="url"
                    name="zalo_link"
                    id="zalo_link"
                    value="{{ $settings['zalo_link'] ?? '' }}"
                    placeholder="https://zalo.me/your_phone_or_oa"
                    class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-800 rounded-xl text-xs bg-white dark:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee text-gray-900 dark:text-white">
                <span class="text-[10px] text-gray-400 dark:text-gray-500 mt-1 block">Liên kết Zalo hỗ trợ khách hàng (ẩn nếu bỏ trống).</span>
            </div>
            <div>
                <label for="facebook_link" class="block text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider mb-1">{{ __('Trang / Nhóm Facebook') }}</label>
                <input type="url"
                    name="facebook_link"
                    id="facebook_link"
                    value="{{ $settings['facebook_link'] ?? '' }}"
                    placeholder="https://facebook.com/your_page"
                    class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-800 rounded-xl text-xs bg-white dark:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee text-gray-900 dark:text-white">
                <span class="text-[10px] text-gray-400 dark:text-gray-500 mt-1 block">Liên kết Facebook hỗ trợ khách hàng (ẩn nếu bỏ trống).</span>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label for="youtube_link" class="block text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider mb-1">{{ __('Kênh Youtube') }}</label>
                <input type="url"
                    name="youtube_link"
                    id="youtube_link"
                    value="{{ $settings['youtube_link'] ?? '' }}"
                    placeholder="https://youtube.com/@your_channel"
                    class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-800 rounded-xl text-xs bg-white dark:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee text-gray-900 dark:text-white">
                <span class="text-[10px] text-gray-400 dark:text-gray-500 mt-1 block">Kênh YouTube hướng dẫn, giới thiệu (ẩn nếu bỏ trống).</span>
            </div>
            <div>
                <label for="tiktok_link" class="block text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider mb-1">{{ __('Kênh TikTok') }}</label>
                <input type="url"
                    name="tiktok_link"
                    id="tiktok_link"
                    value="{{ $settings['tiktok_link'] ?? '' }}"
                    placeholder="https://tiktok.com/@your_channel"
                    class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-800 rounded-xl text-xs bg-white dark:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee text-gray-900 dark:text-white">
                <span class="text-[10px] text-gray-400 dark:text-gray-500 mt-1 block">Kênh TikTok truyền thông thương hiệu (ẩn nếu bỏ trống).</span>
            </div>
        </div>

        <div class="border-t border-gray-200/65 dark:border-slate-800/80 my-5"></div>

        <!-- Cấu hình hiển thị bong bóng liên hệ -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label for="show_contact_bubble" class="block text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider mb-1">{{ __('Bong bóng liên hệ nổi (Floating Contact Bubble)') }}</label>
                <select name="show_contact_bubble"
                    id="show_contact_bubble"
                    class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-800 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-300">
                    <option value="1" {{ ($settings['show_contact_bubble'] ?? '1') == '1' ? 'selected' : '' }}>{{ __('Hiển thị bong bóng liên hệ') }}</option>
                    <option value="0" {{ ($settings['show_contact_bubble'] ?? '1') == '0' ? 'selected' : '' }}>{{ __('Ẩn bong bóng liên hệ') }}</option>
                </select>
                <span class="text-[10px] text-gray-400 mt-1 block">Cho phép hiển thị một bong bóng liên hệ nhanh (Zalo, Messenger, Telegram, Hotline) nổi ở góc màn hình ngoài Storefront.</span>
            </div>
            <div>
                <label for="contact_bubble_position" class="block text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider mb-1">{{ __('Vị trí bong bóng liên hệ') }}</label>
                <select name="contact_bubble_position"
                    id="contact_bubble_position"
                    class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-800 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-300">
                    <option value="bottom-left" {{ ($settings['contact_bubble_position'] ?? 'bottom-left') == 'bottom-left' ? 'selected' : '' }}>{{ __('Góc dưới bên trái (Mặc định)') }}</option>
                    <option value="bottom-right" {{ ($settings['contact_bubble_position'] ?? 'bottom-left') == 'bottom-right' ? 'selected' : '' }}>{{ __('Góc dưới bên phải') }}</option>
                </select>
                <span class="text-[10px] text-gray-400 mt-1 block">Chọn vị trí hiển thị bong bóng trên giao diện Storefront.</span>
            </div>
        </div>

        <div class="border-t border-gray-200/65 dark:border-slate-800/80 my-5"></div>

        <!-- Cấu hình Footer Copyright -->
        <div>
            <label for="footer_copyright" class="block text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider mb-1">{{ __('Bản quyền chân trang (Footer Copyright)') }}</label>
            <input type="text"
                name="footer_copyright"
                id="footer_copyright"
                value="{{ $settings['footer_copyright'] ?? '&copy; ' . date('Y') . ' Cashback Shopee Platform. Bảo lưu mọi quyền.' }}"
                class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-800 rounded-xl text-xs bg-white dark:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee text-gray-900 dark:text-white">
            <span class="text-[10px] text-gray-400 dark:text-gray-500 mt-1 block">Nội dung bản quyền hiển thị ở chân trang của trang khách (hỗ trợ HTML).</span>
        </div>

        <div class="border-t border-gray-200/65 dark:border-slate-800/80 my-5"></div>

        <!-- Cấu hình Footer Commitments -->
        <div class="space-y-4">
            <h4 class="text-xs font-bold text-gray-700 dark:text-slate-300 uppercase tracking-wider flex items-center gap-1.5">
                <i data-lucide="check-square" class="w-4 h-4 text-shopee"></i>
                {{ __('Cấu hình khối Cam Kết ở chân trang (Footer Commitments)') }}
            </h4>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <label for="footer_commitment_title" class="block text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider mb-1">{{ __('Tiêu đề khối cam kết') }}</label>
                    <input type="text"
                        name="footer_commitment_title"
                        id="footer_commitment_title"
                        value="{{ $settings['footer_commitment_title'] ?? 'Cam Kết' }}"
                        class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-800 rounded-xl text-xs bg-white dark:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee text-gray-900 dark:text-white">
                    <span class="text-[10px] text-gray-400 dark:text-gray-500 mt-1 block">Tiêu đề hiển thị trên cùng của khối cam kết ở footer.</span>
                </div>
                
                <div class="md:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Cam kết 1 -->
                    <div class="space-y-3 p-4 bg-gray-100/40 dark:bg-slate-900/30 rounded-xl border border-gray-150 dark:border-slate-800/50">
                        <span class="text-[11px] font-bold text-gray-700 dark:text-slate-300 flex items-center gap-1">
                            <i data-lucide="badge-check" class="w-4 h-4 text-amber-500"></i>
                            {{ __('Mục cam kết thứ nhất') }}
                        </span>
                        <div>
                            <label for="footer_commitment_text_1" class="block text-[10px] font-medium text-gray-500 dark:text-slate-400 uppercase tracking-wider mb-1">{{ __('Nội dung cam kết 1') }}</label>
                            <input type="text"
                                name="footer_commitment_text_1"
                                id="footer_commitment_text_1"
                                value="{{ $settings['footer_commitment_text_1'] ?? 'Tỷ lệ hoàn tiền cao hàng đầu' }}"
                                class="block w-full px-3 py-2 border border-gray-200 dark:border-slate-800 rounded-lg text-xs bg-white dark:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee text-gray-900 dark:text-white">
                        </div>
                        <div>
                            <label for="footer_commitment_icon_1" class="block text-[10px] font-medium text-gray-500 dark:text-slate-400 uppercase tracking-wider mb-1">{{ __('Icon Lucide cam kết 1') }}</label>
                            <div class="flex gap-2">
                                <input type="text"
                                    name="footer_commitment_icon_1"
                                    id="footer_commitment_icon_1"
                                    value="{{ $settings['footer_commitment_icon_1'] ?? 'badge-check' }}"
                                    class="block w-full px-3 py-2 border border-gray-200 dark:border-slate-800 rounded-lg text-xs bg-white dark:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee text-gray-900 dark:text-white font-mono flex-1">
                                <button type="button" 
                                        @click="$dispatch('open-icon-picker', { target: 'footer_commitment_icon_1' })" 
                                        class="px-3 py-2 bg-gray-100 hover:bg-gray-200 dark:bg-slate-800 dark:hover:bg-slate-700/80 text-gray-700 dark:text-slate-300 text-xs font-semibold rounded-lg transition-all flex items-center gap-1 border border-gray-200 dark:border-slate-700/50">
                                    <i data-lucide="grid" class="w-3.5 h-3.5 text-gray-500"></i>
                                    {{ __('Chọn') }}
                                </button>
                            </div>
                            <span class="text-[9px] text-gray-400 dark:text-gray-500 mt-1 block">Tên icon Lucide (ví dụ: badge-check, shield, check-circle).</span>
                        </div>
                    </div>

                    <!-- Cam kết 2 -->
                    <div class="space-y-3 p-4 bg-gray-100/40 dark:bg-slate-900/30 rounded-xl border border-gray-150 dark:border-slate-800/50">
                        <span class="text-[11px] font-bold text-gray-700 dark:text-slate-300 flex items-center gap-1">
                            <i data-lucide="zap" class="w-4 h-4 text-blue-500"></i>
                            {{ __('Mục cam kết thứ hai') }}
                        </span>
                        <div>
                            <label for="footer_commitment_text_2" class="block text-[10px] font-medium text-gray-500 dark:text-slate-400 uppercase tracking-wider mb-1">{{ __('Nội dung cam kết 2') }}</label>
                            <input type="text"
                                name="footer_commitment_text_2"
                                id="footer_commitment_text_2"
                                value="{{ $settings['footer_commitment_text_2'] ?? 'Rút tiền nhanh chóng, bảo mật' }}"
                                class="block w-full px-3 py-2 border border-gray-200 dark:border-slate-800 rounded-lg text-xs bg-white dark:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee text-gray-900 dark:text-white">
                        </div>
                        <div>
                            <label for="footer_commitment_icon_2" class="block text-[10px] font-medium text-gray-500 dark:text-slate-400 uppercase tracking-wider mb-1">{{ __('Icon Lucide cam kết 2') }}</label>
                            <div class="flex gap-2">
                                <input type="text"
                                    name="footer_commitment_icon_2"
                                    id="footer_commitment_icon_2"
                                    value="{{ $settings['footer_commitment_icon_2'] ?? 'zap' }}"
                                    class="block w-full px-3 py-2 border border-gray-200 dark:border-slate-800 rounded-lg text-xs bg-white dark:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee text-gray-900 dark:text-white font-mono flex-1">
                                <button type="button" 
                                        @click="$dispatch('open-icon-picker', { target: 'footer_commitment_icon_2' })" 
                                        class="px-3 py-2 bg-gray-100 hover:bg-gray-200 dark:bg-slate-800 dark:hover:bg-slate-700/80 text-gray-700 dark:text-slate-300 text-xs font-semibold rounded-lg transition-all flex items-center gap-1 border border-gray-200 dark:border-slate-700/50">
                                    <i data-lucide="grid" class="w-3.5 h-3.5 text-gray-500"></i>
                                    {{ __('Chọn') }}
                                </button>
                            </div>
                            <span class="text-[9px] text-gray-400 dark:text-gray-500 mt-1 block">Tên icon Lucide (ví dụ: zap, lock, star, award).</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="border-t border-gray-200/65 dark:border-slate-800/80 my-5"></div>

        <!-- Cấu hình Footer Links (Điều khoản & Chính sách) -->
        <div class="space-y-4">
            <h4 class="text-xs font-bold text-gray-700 dark:text-slate-300 uppercase tracking-wider flex items-center gap-1.5">
                <i data-lucide="link" class="w-4 h-4 text-shopee"></i>
                {{ __('Liên kết pháp lý chân trang (Footer Links)') }}
            </h4>

            @php
                // Lấy danh sách các trang đã được xuất bản để hiển thị trong select box chọn nhanh
                $availablePages = \App\Models\Page::published()->orderBy('title')->get();
            @endphp

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Cấu hình Link Điều khoản dịch vụ -->
                <div class="space-y-2">
                    <label for="footer_terms_url" class="block text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider mb-1">{{ __('Link Điều khoản dịch vụ') }}</label>
                    
                    <!-- Select box hỗ trợ chọn nhanh trang có sẵn -->
                    <select onchange="if(this.value) { document.getElementById('footer_terms_url').value = this.value; }" 
                            class="block w-full px-3 py-2 border border-gray-200 dark:border-slate-800 rounded-xl text-xs bg-gray-50 dark:bg-slate-800 focus:outline-none text-gray-500 dark:text-slate-400">
                        <option value="">-- {{ __('Chọn nhanh trang có sẵn') }} --</option>
                        @foreach($availablePages as $p)
                            <option value="/page/{{ $p->slug }}">{{ $p->title }}</option>
                        @endforeach
                    </select>

                    <input type="text"
                        name="footer_terms_url"
                        id="footer_terms_url"
                        value="{{ $settings['footer_terms_url'] ?? '#' }}"
                        placeholder="/page/dieu-khoan-dich-vu"
                        class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-800 rounded-xl text-xs bg-white dark:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee text-gray-900 dark:text-white">
                    <span class="text-[10px] text-gray-400 dark:text-gray-500 mt-1 block">Chọn nhanh trang có sẵn ở trên hoặc tự điền liên kết thủ công.</span>
                </div>

                <!-- Cấu hình Link Chính sách bảo mật -->
                <div class="space-y-2">
                    <label for="footer_privacy_url" class="block text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider mb-1">{{ __('Link Chính sách bảo mật') }}</label>
                    
                    <!-- Select box hỗ trợ chọn nhanh trang có sẵn -->
                    <select onchange="if(this.value) { document.getElementById('footer_privacy_url').value = this.value; }" 
                            class="block w-full px-3 py-2 border border-gray-200 dark:border-slate-800 rounded-xl text-xs bg-gray-50 dark:bg-slate-800 focus:outline-none text-gray-500 dark:text-slate-400">
                        <option value="">-- {{ __('Chọn nhanh trang có sẵn') }} --</option>
                        @foreach($availablePages as $p)
                            <option value="/page/{{ $p->slug }}">{{ $p->title }}</option>
                        @endforeach
                    </select>

                    <input type="text"
                        name="footer_privacy_url"
                        id="footer_privacy_url"
                        value="{{ $settings['footer_privacy_url'] ?? '#' }}"
                        placeholder="/page/chinh-sach-bao-mat"
                        class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-800 rounded-xl text-xs bg-white dark:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee text-gray-900 dark:text-white">
                    <span class="text-[10px] text-gray-400 dark:text-gray-500 mt-1 block">Chọn nhanh trang có sẵn ở trên hoặc tự điền liên kết thủ công.</span>
                </div>
            </div>
        </div>
    </div>
</div>
