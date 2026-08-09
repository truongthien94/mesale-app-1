{{-- 
    Partial View: Rút gọn link
    Vai trò: Quản lý tính năng tự động tạo link rút gọn cashback, cấu hình độ dài mã rút gọn, tên miền rút gọn riêng (Custom Domain), và hiển thị modal hướng dẫn cấu hình DNS tên miền phụ/tên miền chính.
--}}
<div x-show="tab === 'shortlink'" class="space-y-6" x-transition x-cloak>
    <div class="bg-gray-50/50 dark:bg-slate-900/50 rounded-2xl p-4 sm:p-6 border border-gray-150 dark:border-slate-800 space-y-6 w-full">
        <h3 class="text-xs font-bold text-gray-700 dark:text-slate-300 uppercase tracking-wider pb-2 border-b border-gray-200/65 dark:border-slate-800 flex items-center justify-between gap-1.5">
            <div class="flex items-center gap-1.5">
                <i data-lucide="link" class="w-4 h-4 text-shopee"></i>
                {{ __('Cấu hình Rút Gọn Link Hoàn Tiền') }}
            </div>
            {{-- Tạm thời ẩn nút Hướng dẫn cấu hình tên miền
            <button type="button" @click="showGuideModal = true" class="text-xs font-semibold text-shopee hover:text-orange-600 transition-colors flex items-center gap-1">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <span>{{ __('Hướng dẫn cấu hình tên miền') }}</span>
            </button>
            --}}
        </h3>

        <!-- Cấu hình Trạng thái tính năng -->
        <div>
            <label for="shortlink_status" class="block text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider mb-1">{{ __('Trạng thái tự động rút gọn link') }}</label>
            <select name="shortlink_status"
                id="shortlink_status"
                class="block w-full px-4 py-2.5 border border-gray-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-300">
                <option value="1" {{ ($settings['shortlink_status'] ?? '0') === '1' ? 'selected' : '' }}>{{ __('Bật hoạt động (Tự động tạo mã rút gọn)') }}</option>
                <option value="0" {{ ($settings['shortlink_status'] ?? '0') === '0' ? 'selected' : '' }}>{{ __('Tắt hoạt động (Dùng link affiliate gốc)') }}</option>
            </select>
            <span class="text-[9px] text-gray-400 mt-1 block">Bật để hệ thống tự động sinh link rút gọn ngắn (ví dụ: domain/abcxyz) thay vị dùng link affiliate gốc của Shopee.</span>
        </div>

        <!-- Cấu hình Độ dài của mã rút gọn -->
        <div>
            <label for="shortlink_length" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1 dark:text-slate-300">{{ __('Độ dài mã rút gọn (Số ký tự)') }}</label>
            <input type="number"
                name="shortlink_length"
                id="shortlink_length"
                value="{{ $settings['shortlink_length'] ?? '8' }}"
                min="3"
                max="32"
                class="block w-full px-4 py-2.5 border border-gray-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-300">
            <span class="text-[9px] text-gray-400 mt-1 block">Quy định số lượng ký tự ngẫu nhiên trong mã rút gọn (Ví dụ: nhập 6 để sinh mã dạng abcxyz, khuyên dùng từ 6-8 ký tự).</span>
        </div>

        <!-- Cấu hình Ẩn ô nhập link rút gọn ở trang khách -->
        <div>
            <label for="shortlink_hide_input" class="block text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider mb-1">{{ __('Ẩn ô hiển thị link rút gọn ở trang khách') }}</label>
            <select name="shortlink_hide_input"
                id="shortlink_hide_input"
                class="block w-full px-4 py-2.5 border border-gray-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-300">
                <option value="0" {{ ($settings['shortlink_hide_input'] ?? '0') === '1' ? '' : 'selected' }}>{{ __('Hiển thị ô link rút gọn (Mặc định)') }}</option>
                <option value="1" {{ ($settings['shortlink_hide_input'] ?? '0') === '1' ? 'selected' : '' }}>{{ __('Ẩn ô link rút gọn (Chỉ hiện nút Mở Mua Hàng)') }}</option>
            </select>
            <span class="text-[9px] text-gray-400 mt-1 block">Khi bật ẩn, trang khách sẽ không còn hiển thị ô "Liên kết rút gọn hoàn tiền của bạn" mà chỉ hiển thị nút Mở Mua Hàng Nhận Hoàn Tiền.</span>
        </div>

        {{-- Tạm thời ẩn cấu hình Tên miền rút gọn riêng (Custom Domain)
        <div>
            <label for="shortlink_domain" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1 dark:text-slate-300">{{ __('Tên miền rút gọn riêng (Custom Domain)') }}</label>
            <input type="text"
                name="shortlink_domain"
                id="shortlink_domain"
                value="{{ $settings['shortlink_domain'] ?? '' }}"
                placeholder="Ví dụ: my-shortlink.com hoặc https://my-shortlink.com"
                class="block w-full px-4 py-2.5 border border-gray-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-300">
            <span class="text-[9px] text-gray-400 mt-1 block">Nhập tên miền bạn muốn sử dụng làm tên miền rút gọn link (ví dụ: mydomain.com). Nếu để trống, hệ thống sẽ sử dụng tên miền mặc định của trang web này ({{ request()->getHost() }}).</span>
        </div>
        --}}
    </div>

    {{-- Tạm thời ẩn Modal Hướng dẫn cấu hình Custom Domain
    <template x-teleport="body">
        <div x-show="showGuideModal"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            x-cloak>

            <div class="bg-white dark:bg-slate-800 rounded-3xl shadow-2xl max-w-2xl w-full max-h-[85vh] overflow-y-auto border border-gray-150 dark:border-slate-700" @click.away="showGuideModal = false">
                <!-- Modal Header -->
                <div class="px-6 py-5 border-b border-gray-100 dark:border-slate-700 flex items-center justify-between sticky top-0 bg-white dark:bg-slate-800 z-10">
                    <h3 class="text-sm font-bold text-gray-800 dark:text-slate-100 flex items-center gap-2">
                        <svg class="w-5 h-5 text-shopee" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        {{ __('Hướng Dẫn Cấu Hình Tên Miền Rút Gọn') }}
                    </h3>
                    <button type="button" @click="showGuideModal = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-slate-200 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <!-- Modal Content -->
                <div class="p-6 space-y-6 text-xs text-gray-600 dark:text-slate-300 leading-relaxed">
                    <!-- Bước 1 -->
                    <div class="space-y-2">
                        <h4 class="font-bold text-gray-800 dark:text-slate-200 flex items-center gap-2">
                            <span class="bg-shopee/10 text-shopee px-2 py-0.5 rounded-lg text-[10px]">Bước 1</span>
                            Cấu hình trong Admin Panel
                        </h4>
                        <p>Nhập tên miền bạn muốn sử dụng để rút gọn link (ví dụ: <code class="bg-gray-100 dark:bg-slate-700 px-1 py-0.5 rounded text-shopee">rutgon.co</code>) vào ô cấu hình phía sau và bấm <strong>Lưu cấu hình</strong>.</p>
                    </div>

                    <!-- Bước 2 -->
                    <div class="space-y-2">
                        <h4 class="font-bold text-gray-800 dark:text-slate-200 flex items-center gap-2">
                            <span class="bg-shopee/10 text-shopee px-2 py-0.5 rounded-lg text-[10px]">Bước 2</span>
                            Cấu hình DNS của Tên miền
                        </h4>
                        <p>Đăng nhập vào trang quản lý DNS tên miền rút gọn của bạn (ví dụ Cloudflare, Tenten) và thêm bản ghi:</p>
                        <ul class="list-disc pl-5 space-y-1 mt-1">
                            <li><strong>Nếu dùng Tên miền phụ (Subdomain - ví dụ: <code class="bg-gray-100 dark:bg-slate-700 px-1 py-0.5 rounded">s.yourdomain.com</code>):</strong> Tạo bản ghi <code class="font-bold">CNAME</code>, Name: <code class="text-shopee font-semibold">s</code>, Value: trỏ về tên miền chính của web hiện tại (<code class="font-semibold">{{ request()->getHost() }}</code>).</li>
                            <li><strong>Nếu dùng Tên miền chính (Root domain - ví dụ: <code class="bg-gray-100 dark:bg-slate-700 px-1 py-0.5 rounded">rutgon.co</code>):</strong> Tạo bản ghi <code class="font-bold">A</code>, Name: <code class="text-shopee font-semibold">@</code>, Value: trỏ về địa chỉ IP Máy chủ / VPS hiện tại của bạn.</li>
                        </ul>
                    </div>

                    <!-- Bước 3 -->
                    <div class="space-y-2">
                        <h4 class="font-bold text-gray-800 dark:text-slate-200 flex items-center gap-2">
                            <span class="bg-shopee/10 text-shopee px-2 py-0.5 rounded-lg text-[10px]">Bước 3</span>
                            Cấu hình Máy chủ (Web Server)
                        </h4>
                        <p>Cấu hình máy chủ để trỏ cả hai tên miền về chung thư mục gốc (Document Root) của dự án này:</p>
                        <div class="bg-gray-50 dark:bg-slate-900 p-3 rounded-xl border border-gray-150 dark:border-slate-800 space-y-2">
                            <p class="font-medium text-gray-700 dark:text-slate-400">Cách A: Dùng cPanel / Hosting thường</p>
                            <p>Vào mục <strong>Aliases</strong> (hoặc Parked Domains), thêm tên miền rút gọn vào và cấu hình đường dẫn Document Root trùng với thư mục gốc của trang chính.</p>
                            <hr class="border-gray-150 dark:border-slate-800">
                            <p class="font-medium text-gray-700 dark:text-slate-400">Cách B: Dùng VPS Nginx</p>
                            <p>Thêm tên miền rút gọn vào dòng <code class="bg-gray-200 dark:bg-slate-800 px-1 py-0.5 rounded">server_name</code> trong tệp cấu hình block Nginx:</p>
                            <pre class="bg-gray-900 text-slate-300 p-2 rounded-lg font-mono text-[10px] mt-1 overflow-x-auto">server_name {{ request()->getHost() }} <strong>your-shortdomain.com</strong>;</pre>
                        </div>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="px-6 py-4 border-t border-gray-100 dark:border-slate-700 flex justify-end bg-gray-50 dark:bg-slate-900/50 rounded-b-3xl">
                    <button type="button" @click="showGuideModal = false" class="px-5 py-2 bg-shopee text-white hover:bg-orange-600 font-semibold rounded-xl transition-all shadow-md">
                        {{ __('Đã hiểu') }}
                    </button>
                </div>
            </div>
        </div>
    </template>
    --}}
</div>
