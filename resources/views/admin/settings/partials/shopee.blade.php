{{-- 
    Partial View: Cấu hình hoàn tiền Shopee
    Vai trò: Quản lý tỷ lệ hoàn tiền cho khách, Shopee App ID, API URL, API Key, cấu hình dọn dẹp cache sản phẩm cào Shopee.
--}}
<div x-show="tab === 'shopee'" class="space-y-6" x-transition x-cloak>
    {{-- 
        Kiểm tra chế độ Demo:
        Nếu hệ thống đang chạy ở chế độ Demo (APP_DEMO = true), ẩn toàn bộ form cấu hình
        để tránh rò rỉ thông tin nhạy cảm của API Shopee và cookie liên kết.
    --}}
    @if(config('app.demo'))
        <div class="bg-yellow-50 dark:bg-yellow-950/20 border border-yellow-250 dark:border-yellow-900/50 text-yellow-800 dark:text-yellow-400 rounded-3xl p-6 sm:p-8 flex flex-col items-center justify-center text-center space-y-3 py-16">
            <div class="w-16 h-16 bg-yellow-100 dark:bg-yellow-900/30 rounded-2xl flex items-center justify-center text-yellow-500 animate-pulse">
                <i data-lucide="shield-alert" class="w-8 h-8"></i>
            </div>
            <h3 class="text-sm font-bold text-gray-900 dark:text-white mt-4">{{ __('Tính năng bị ẩn ở chế độ Demo') }}</h3>
            <p class="text-xs text-gray-500 dark:text-gray-400 max-w-md leading-relaxed">
                {{ __('Để bảo vệ an toàn thông tin và cấu hình kết nối API của hệ thống, các thiết lập liên quan đến hoàn tiền Shopee không được hiển thị và chỉnh sửa ở chế độ dùng thử (Demo).') }}
            </p>
        </div>
    @else
        <!-- Cấu hình Tỷ lệ Cashback -->
        <div class="bg-gray-50/50 dark:bg-slate-900/50 rounded-2xl p-4 sm:p-6 border border-gray-150 dark:border-slate-800 space-y-6 w-full">
            <h3 class="text-xs font-bold text-gray-700 dark:text-slate-300 uppercase tracking-wider flex items-center gap-1.5 pb-2 border-b border-gray-200/65 dark:border-slate-800">
                <i data-lucide="percent" class="w-4 h-4 text-shopee"></i>
                {{ __('Cấu hình tỷ lệ hoàn tiền') }}
            </h3>

            <div class="p-3 bg-orange-50 border border-orange-100 text-[10px] text-orange-800 rounded-2xl dark:bg-orange-950/30 dark:border-orange-800/50 dark:text-orange-300">
                <h4 class="font-bold flex items-center gap-1"><i data-lucide="info" class="w-4 h-4"></i> {{ __('Cách hoạt động của tỷ lệ Cashback:') }}</h4>
                <p class="mt-1">{{ __('Khi Shopee trả hoa hồng cho đơn hàng, hệ thống sẽ trích xuất tỷ lệ bên dưới để hoàn lại cho khách hàng mua sản phẩm. Phần hoa hồng còn lại (100% - Tỷ lệ cashback) hệ thống sẽ giữ lại làm lợi nhuận hoạt động (và trích tiếp để trả hoa hồng MLM F1/F2 nếu có).') }}</p>
                {{-- Ghi chú nghiệp vụ: giải thích rủi ro tài chính khi đặt tỷ lệ vượt mốc 100% --}}
                <p class="mt-1.5 font-semibold">{{ __('Có thể đặt tỷ lệ vượt mốc 100% (tối đa :max%) để chạy chiến dịch bù lỗ hút khách. Khi vượt 100%, số tiền hoàn cho khách sẽ lớn hơn phần hoa hồng sàn trả về, hệ thống chịu lỗ phần chênh lệch — hãy cân nhắc kỹ trước khi lưu.', ['max' => $maxCashbackRate]) }}</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-6 gap-6">
                <div>
                    <!-- Cấu hình Tên nền tảng hiển thị của Shopee ở giao diện khách hàng -->
                    <label for="shopee_platform_name" class="block text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider mb-1">{{ __('Tên sàn hiển thị') }}</label>
                    <input type="text"
                        name="shopee_platform_name"
                        id="shopee_platform_name"
                        value="{{ $settings['shopee_platform_name'] ?? 'Shopee' }}"
                        placeholder="{{ __('Shopee...') }}"
                        class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-800 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-855 text-gray-700 dark:text-slate-300">
                    <span class="text-[9px] text-gray-400 mt-1 block">{{ __('Tên nền tảng Shopee hiển thị ở giao diện khách hàng (Mặc định: Shopee).') }}</span>
                </div>

                <div>
                    <!-- Cấu hình Trạng thái hoạt động của hoàn tiền Shopee (Bật/Tắt) -->
                    <label for="shopee_status" class="block text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider mb-1">{{ __('Trạng thái Hoàn tiền Shopee') }}</label>
                    <select name="shopee_status"
                        id="shopee_status"
                        class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-800 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-855 text-gray-700 dark:text-slate-300">
                        <option value="1" {{ ($settings['shopee_status'] ?? '1') === '1' ? 'selected' : '' }}>{{ __('BẬT (ON)') }}</option>
                        <option value="0" {{ ($settings['shopee_status'] ?? '1') === '0' ? 'selected' : '' }}>{{ __('TẮT (OFF)') }}</option>
                    </select>
                    <span class="text-[9px] text-gray-400 mt-1 block">{{ __('Bật hoặc tắt toàn bộ chức năng dán link và nhận hoa hồng hoàn tiền từ sàn Shopee.') }}</span>
                </div>

                <div>
                    <label for="shopee_app_id" class="block text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider mb-1">{{ __('Shopee App ID (Affiliate ID)') }}</label>
                    <input type="text"
                        name="shopee_app_id"
                        id="shopee_app_id"
                        value="{{ $settings['shopee_app_id'] ?? '' }}"
                        placeholder="{{ __('Affiliate ID từ Shopee...') }}"
                        class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-800 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-855 text-gray-700 dark:text-slate-300">
                    {{-- 
                        Giải thích: Hướng dẫn này giúp Quản trị viên lấy chính xác mã Affiliate ID
                        của tài khoản Shopee Affiliate nhằm đảm bảo việc chèn ID vào URL affiliate hoạt động đúng,
                        ngăn ngừa lỗi ghi nhận hoa hồng do cấu hình sai ID.
                    --}}
                    <span class="text-[9px] text-gray-400 mt-1 block leading-relaxed">
                        {{ __('Mã Affiliate ID để hệ thống tạo link rút gọn chứa ID của bạn.') }}
                        <br>
                        <a href="https://affiliate.shopee.vn/account_setting" target="_blank" class="text-shopee hover:underline font-semibold inline-flex items-center gap-0.5 mt-0.5">
                            {{ __('Click vào đây để đi tới trang Thiết lập tài khoản Shopee Affiliate') }}
                            <i data-lucide="external-link" class="w-3.5 h-3.5"></i>
                        </a>
                        <br>
                        {{ __('(Sau khi đăng nhập, copy dãy số Affiliate ID hiển thị ở phần Thiết lập tài khoản)') }}
                    </span>
                </div>

                <div>
                    <label for="shopee_cashback_rate" class="block text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider mb-1">{{ __('Tỷ lệ hoàn trả khách (%)') }}</label>
                    <input type="number"
                        name="shopee_cashback_rate"
                        id="shopee_cashback_rate"
                        value="{{ $settings['shopee_cashback_rate'] ?? '50' }}"
                        step="0.01"
                        min="0"
                        max="{{ $maxCashbackRate }}"
                        class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-800 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-855 text-gray-700 dark:text-slate-300">
                    <span class="text-[9px] text-gray-400 mt-1 block">{{ __('Tỷ lệ thực tế dùng để cộng số dư vào ví khách khi duyệt đơn Shopee từ API/Excel.') }}</span>
                </div>

                <div>
                    <label for="shopee_fake_cashback_rate" class="block text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider mb-1">{{ __('Tỷ lệ hoàn ảo (%)') }}</label>
                    <input type="number"
                        name="shopee_fake_cashback_rate"
                        id="shopee_fake_cashback_rate"
                        value="{{ $settings['shopee_fake_cashback_rate'] ?? '50' }}"
                        step="0.01"
                        min="0"
                        max="{{ $maxCashbackRate }}"
                        class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-800 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-855 text-gray-700 dark:text-slate-300">
                    <span class="text-[9px] text-gray-400 mt-1 block">{{ __('Tỷ lệ dùng để tính tiền hoàn hiển thị cho khách khi dán link lấy link hoàn tiền. Nếu muốn hiển thị tỷ lệ hoàn thật thì điền giống như Tỷ lệ hoàn trả khách.') }}</span>
                </div>

                <div>
                    <!-- Cấu hình ẩn hoặc hiển thị Giá bán hiện tại của Shopee -->
                    <label for="shopee_show_current_price" class="block text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider mb-1">{{ __('Hiển thị Giá bán') }}</label>
                    <select name="shopee_show_current_price"
                        id="shopee_show_current_price"
                        class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-800 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-855 text-gray-700 dark:text-slate-300">
                        <option value="1" {{ ($settings['shopee_show_current_price'] ?? '1') === '1' ? 'selected' : '' }}>{{ __('HIỂN THỊ') }}</option>
                        <option value="0" {{ ($settings['shopee_show_current_price'] ?? '1') === '0' ? 'selected' : '' }}>{{ __('ẨN') }}</option>
                    </select>
                    <span class="text-[9px] text-gray-400 mt-1 block">{{ __('Tùy chỉnh Hiển thị hoặc Ẩn giá bán hiện tại của sản phẩm khi lấy link hoàn tiền Shopee.') }}</span>
                </div>
            </div>
        </div>

        <!-- Cấu hình API chuyển đổi link Shopee -->
        <div class="bg-gray-50/50 dark:bg-slate-900/50 rounded-2xl p-4 sm:p-6 border border-gray-150 dark:border-slate-800 space-y-6 w-full">
            <h3 class="text-xs font-bold text-gray-700 dark:text-slate-300 uppercase tracking-wider flex items-center gap-1.5 pb-2 border-b border-gray-200/65 dark:border-slate-800">
                <i data-lucide="git-compare" class="w-4 h-4 text-shopee"></i>
                {{ __('Cấu hình API chuyển đổi link Shopee') }}
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="shopee_link_converter" class="block text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider mb-1">
                        {{ __('API Chuyển đổi link') }}
                    </label>
                    <select name="shopee_link_converter"
                        id="shopee_link_converter"
                        class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-800 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-855 text-gray-700 dark:text-slate-300">
                        <option value="shopee_origin" {{ ($settings['shopee_link_converter'] ?? 'shopee_origin') === 'shopee_origin' ? 'selected' : '' }}>{{ __('API gốc từ Shopee') }}</option>
                        <option value="shptoday" {{ ($settings['shopee_link_converter'] ?? 'shopee_origin') === 'shptoday' ? 'selected' : '' }}>{{ __('shp.today API') }}</option>
                    </select>
                    <span class="text-[9px] text-gray-400 mt-1 block">
                        {{ __('Chọn API dùng để chuyển đổi link Shopee thông thường sang link affiliate.') }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Cấu hình API Shopee -->
        <div class="bg-gray-50/50 dark:bg-slate-900/50 rounded-2xl p-4 sm:p-6 border border-gray-150 dark:border-slate-800 space-y-6 w-full">
            <h3 class="text-xs font-bold text-gray-700 dark:text-slate-300 uppercase tracking-wider flex items-center gap-1.5 pb-2 border-b border-gray-200/65 dark:border-slate-800">
                <i data-lucide="key-round" class="w-4 h-4 text-shopee"></i>
                {{ __('Cấu hình kết nối API Shopee') }}
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <label for="apishopee_status" class="block text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider mb-1">{{ __('Trạng thái kết nối API') }}</label>
                    <select name="apishopee_status"
                        id="apishopee_status"
                        class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-800 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-850 text-gray-700 dark:text-slate-300">
                        <option value="1" {{ ($settings['apishopee_status'] ?? '1') === '1' ? 'selected' : '' }}>{{ __('BẬT (ON)') }}</option>
                        <option value="0" {{ ($settings['apishopee_status'] ?? '1') === '0' ? 'selected' : '' }}>{{ __('TẮT (OFF)') }}</option>
                    </select>
                    <span class="text-[9px] text-gray-400 mt-1 block">{{ __('Bật để lấy thông tin sản phẩm từ API ví dụ: Tên sp, ảnh sp, giá sp,...') }}</span>
                </div>

                <div>
                    <label for="apishopee_url" class="block text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider mb-1">{{ __('Đường dẫn API (API URL)') }}</label>
                    <input type="text"
                        name="apishopee_url"
                        id="apishopee_url"
                        value="{{ $settings['apishopee_url'] ?? '' }}"
                        placeholder="https://example.com/api/v1/shopee/product"
                        class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-800 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-850 text-gray-700 dark:text-slate-300">
                    <span class="text-[9px] text-gray-400 mt-1 block">{{ __('Nhập đường dẫn endpoint API dùng để lấy thông tin sản phẩm Shopee.') }}</span>
                </div>

                <div>
                    <label for="apishopee_key" class="block text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider mb-1">{{ __('API Key (X-API-KEY)') }}</label>
                    <input type="text"
                        name="apishopee_key"
                        id="apishopee_key"
                        value="{{ $settings['apishopee_key'] ?? '' }}"
                        placeholder="Nhập API Key..."
                        class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-800 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-850 text-gray-700 dark:text-slate-300">
                    <span class="text-[9px] text-gray-400 mt-1 block">{{ __('Nhập mã API Key dùng để kết nối và xác thực với hệ thống API Shopee.') }}</span>
                </div>
            </div>
        </div>

        <!-- Cấu hình dọn dẹp cache sản phẩm Shopee -->
        <div class="bg-gray-50/50 dark:bg-slate-900/50 rounded-2xl p-4 sm:p-6 border border-gray-150 dark:border-slate-800 space-y-6 w-full">
            <h3 class="text-xs font-bold text-gray-700 dark:text-slate-300 uppercase tracking-wider flex items-center gap-1.5 pb-2 border-b border-gray-200/65 dark:border-slate-800">
                <i data-lucide="database" class="w-4 h-4 text-orange-500"></i>
                {{ __('Cấu hình dọn dẹp Cache sản phẩm') }}
            </h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="cache_clean_estimated_hours" class="block text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider mb-1">
                        {{ __('Thời gian xóa sản phẩm cào ước tính (Giờ)') }}
                    </label>
                    <input type="number"
                        name="cache_clean_estimated_hours"
                        id="cache_clean_estimated_hours"
                        value="{{ $settings['cache_clean_estimated_hours'] ?? '24' }}"
                        min="1"
                        class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-800 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-855 text-gray-700 dark:text-slate-300">
                    <span class="text-[9px] text-gray-400 mt-1 block">{{ __('Tự động xóa các sản phẩm cào fallback (giá = 0) sau số giờ cấu hình để ép hệ thống tải lại thông tin mới chính xác hơn khi khách hàng tra cứu lại.') }}</span>
                </div>

                <div>
                    <label for="cache_clean_normal_days" class="block text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider mb-1">
                        {{ __('Thời gian xóa cache thông thường (Ngày)') }}
                    </label>
                    <input type="number"
                        name="cache_clean_normal_days"
                        id="cache_clean_normal_days"
                        value="{{ $settings['cache_clean_normal_days'] ?? '30' }}"
                        min="1"
                        class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-800 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-855 text-gray-700 dark:text-slate-300">
                    <span class="text-[9px] text-gray-400 mt-1 block">{{ __('Tự động xóa các sản phẩm cache thông thường nếu không có lượt tra cứu hoặc cập nhật nào mới trong khoảng số ngày cấu hình.') }}</span>
                </div>
            </div>
        </div>

        <!-- Cấu hình đối soát và tự động hoàn tiền -->
        <div class="bg-gray-50/50 dark:bg-slate-900/50 rounded-2xl p-4 sm:p-6 border border-gray-150 dark:border-slate-800 space-y-6 w-full">
            <h3 class="text-xs font-bold text-gray-700 dark:text-slate-300 uppercase tracking-wider flex items-center gap-1.5 pb-2 border-b border-gray-200/65 dark:border-slate-800">
                <i data-lucide="check-square" class="w-4 h-4 text-shopee"></i>
                {{ __('Cấu hình đối soát & Tự động hoàn tiền') }}
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="shopee_check_product_match" class="block text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider mb-1">
                        {{ __('Bắt buộc khớp tên sản phẩm') }}
                    </label>
                    <select name="shopee_check_product_match"
                        id="shopee_check_product_match"
                        class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-800 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-850 text-gray-700 dark:text-slate-300">
                        <option value="1" {{ ($settings['shopee_check_product_match'] ?? '1') === '1' ? 'selected' : '' }}>{{ __('BẬT (ON) - Phải mua đúng sản phẩm') }}</option>
                        <option value="0" {{ ($settings['shopee_check_product_match'] ?? '1') === '0' ? 'selected' : '' }}>{{ __('TẮT (OFF) - Mua sản phẩm nào cũng được hoàn') }}</option>
                    </select>
                    <span class="text-[9px] text-gray-400 mt-1 block">{{ __('Bật để bắt buộc tên sản phẩm khách mua thực tế phải khớp với link lấy ban đầu. Tắt đi nếu muốn khách mua sản phẩm nào trong giỏ hàng cũng được hoàn tiền.') }}</span>
                </div>

                <div>
                    <label for="shopee_auto_cashback_future_orders" class="block text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider mb-1">
                        {{ __('Hoàn tiền đơn hàng tương lai (Cookie)') }}
                    </label>
                    <select name="shopee_auto_cashback_future_orders"
                        id="shopee_auto_cashback_future_orders"
                        class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-800 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-850 text-gray-700 dark:text-slate-300">
                        <option value="1" {{ ($settings['shopee_auto_cashback_future_orders'] ?? '0') === '1' ? 'selected' : '' }}>{{ __('BẬT (ON) - Hoàn tiền tự động các đơn mua trực tiếp sau đó') }}</option>
                        <option value="0" {{ ($settings['shopee_auto_cashback_future_orders'] ?? '0') === '0' ? 'selected' : '' }}>{{ __('TẮT (OFF) - Chỉ hoàn tiền cho đơn có click lấy link trực tiếp') }}</option>
                    </select>
                    <span class="text-[9px] text-gray-400 mt-1 block">{{ __('If Bật, khi khách hàng click link 1 lần, các đơn hàng mua sau đó trực tiếp trên Shopee (trong thời gian cookie affiliate còn hiệu lực) vẫn sẽ được tự động hoàn tiền.') }}</span>
                </div>
            </div>
        </div>

        <!-- Cấu hình Lưu ý Cashback hiển thị ở trang chủ -->
        <div class="bg-gray-50/50 dark:bg-slate-900/50 rounded-2xl p-4 sm:p-6 border border-gray-150 dark:border-slate-800 space-y-6 w-full">
            <h3 class="text-xs font-bold text-gray-700 dark:text-slate-300 uppercase tracking-wider flex items-center gap-1.5 pb-2 border-b border-gray-200/65 dark:border-slate-800">
                <i data-lucide="info" class="w-4 h-4 text-shopee"></i>
                {{ __('Cấu hình lưu ý Cashback') }}
            </h3>
            
            <div>
                <label for="hp_cashback_notice" class="block text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider mb-1">
                    {{ __('Nội dung lưu ý hiển thị ở kết quả tìm kiếm') }}
                </label>
                <textarea name="hp_cashback_notice"
                    id="hp_cashback_notice"
                    rows="5"
                    placeholder="{{ __('Nhập lưu ý về cashback hiển thị ở trang chủ sau khi phân tích sản phẩm thành công...') }}"
                    class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-800 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-850 text-gray-700 dark:text-slate-300">{{ $settings['hp_cashback_notice'] ?? '' }}</textarea>
                <span class="text-[9px] text-gray-400 mt-1 block">{{ __('Nội dung này sẽ hiển thị ngay dưới thẻ kết quả sản phẩm Shopee sau khi khách hàng dán link phân tích thành công.') }}</span>
            </div>
        </div>

        <!-- Cấu hình tùy chỉnh mã đơn hàng -->
        <div class="bg-gray-50/50 dark:bg-slate-900/50 rounded-2xl p-4 sm:p-6 border border-gray-150 dark:border-slate-800 space-y-6 w-full">
            <h3 class="text-xs font-bold text-gray-700 dark:text-slate-300 uppercase tracking-wider flex items-center gap-1.5 pb-2 border-b border-gray-200/65 dark:border-slate-800">
                <i data-lucide="hash" class="w-4 h-4 text-shopee"></i>
                {{ __('Cấu hình tùy chỉnh mã đơn hàng') }}
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="order_code_prefix" class="block text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider mb-1">
                        {{ __('Tiền tố mã đơn hàng (Prefix)') }}
                    </label>
                    <input type="text"
                        name="order_code_prefix"
                        id="order_code_prefix"
                        value="{{ $settings['order_code_prefix'] ?? 'SHP' }}"
                        placeholder="{{ __('Ví dụ: SHP, DH, CASHBACK...') }}"
                        class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-800 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-850 text-gray-700 dark:text-slate-300">
                    <span class="text-[9px] text-gray-400 mt-1 block">{{ __('Ký tự viết liền không dấu đi kèm mã đơn hàng để nhận diện nguồn traffic hoặc thương hiệu.') }}</span>
                </div>

                <div>
                    <label for="order_code_prefix_position" class="block text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider mb-1">
                        {{ __('Vị trí tiền tố') }}
                    </label>
                    <select name="order_code_prefix_position"
                        id="order_code_prefix_position"
                        class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-800 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-850 text-gray-700 dark:text-slate-300">
                        <option value="left" {{ ($settings['order_code_prefix_position'] ?? 'left') === 'left' ? 'selected' : '' }}>{{ __('Bên trái (Ví dụ: SHPxxxxxx)') }}</option>
                        <option value="right" {{ ($settings['order_code_prefix_position'] ?? 'left') === 'right' ? 'selected' : '' }}>{{ __('Bên phải (Ví dụ: xxxxxxSHP)') }}</option>
                    </select>
                    <span class="text-[9px] text-gray-400 mt-1 block">{{ __('Chọn vị trí hiển thị của tiền tố nằm trước hay nằm sau chuỗi ký tự ngẫu nhiên.') }}</span>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-2">
                <div>
                    <label for="order_code_random_length" class="block text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider mb-1">
                        {{ __('Số ký tự ngẫu nhiên') }}
                    </label>
                    <input type="number"
                        name="order_code_random_length"
                        id="order_code_random_length"
                        value="{{ $settings['order_code_random_length'] ?? '10' }}"
                        min="4"
                        max="32"
                        class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-800 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-850 text-gray-700 dark:text-slate-300">
                    <span class="text-[9px] text-gray-400 mt-1 block">{{ __('Độ dài phần chuỗi ngẫu nhiên được sinh ra (giới hạn từ 4 đến 32 ký tự để đảm bảo tính duy nhất và bảo mật).') }}</span>
                </div>

                <div>
                    <label for="order_code_random_type" class="block text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider mb-1">
                        {{ __('Kiểu ký tự ngẫu nhiên') }}
                    </label>
                    <select name="order_code_random_type"
                        id="order_code_random_type"
                        class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-800 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-850 text-gray-700 dark:text-slate-300">
                        <option value="alphanumeric_upper" {{ ($settings['order_code_random_type'] ?? 'alphanumeric_upper') === 'alphanumeric_upper' ? 'selected' : '' }}>{{ __('Chữ và số in hoa (Ví dụ: A1B2C3)') }}</option>
                        <option value="alphanumeric" {{ ($settings['order_code_random_type'] ?? 'alphanumeric_upper') === 'alphanumeric' ? 'selected' : '' }}>{{ __('Chữ và số (Ví dụ: a1B2c3)') }}</option>
                        <option value="numeric" {{ ($settings['order_code_random_type'] ?? 'alphanumeric_upper') === 'numeric' ? 'selected' : '' }}>{{ __('Chỉ số (Ví dụ: 123456)') }}</option>
                        <option value="alpha_upper" {{ ($settings['order_code_random_type'] ?? 'alphanumeric_upper') === 'alpha_upper' ? 'selected' : '' }}>{{ __('Chỉ chữ in hoa (Ví dụ: ABCDEF)') }}</option>
                        <option value="alpha" {{ ($settings['order_code_random_type'] ?? 'alphanumeric_upper') === 'alpha' ? 'selected' : '' }}>{{ __('Chỉ chữ (Ví dụ: abcDEF)') }}</option>
                    </select>
                    <span class="text-[9px] text-gray-400 mt-1 block">{{ __('Lựa chọn định dạng ký tự ngẫu nhiên phù hợp nhất với mong muốn quản trị của bạn.') }}</span>
                </div>
            </div>
        </div>
    @endif
</div>


