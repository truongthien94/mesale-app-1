{{--
    Partial View: Cấu hình hoàn tiền Lazada
    Vai trò: Quản lý tỷ lệ hoàn tiền cho khách, cấu hình kết nối Lazada Affiliate API (User Token, API URL),
    tham số sub-id đối soát, dọn dẹp cache, đối soát tự động và tùy chỉnh mã đơn hàng Lazada.
--}}
<div x-show="tab === 'lazada'" class="space-y-6" x-transition x-cloak>
    @if(config('app.demo'))
        {{--
            Kiểm tra chế độ Demo:
            Nếu hệ thống đang chạy ở chế độ Demo (APP_DEMO = true), ẩn toàn bộ form cấu hình
            để tránh rò rỉ thông tin nhạy cảm của Lazada Affiliate API.
        --}}
        <div class="bg-yellow-50 dark:bg-yellow-950/20 border border-yellow-250 dark:border-yellow-900/50 text-yellow-800 dark:text-yellow-400 rounded-3xl p-6 sm:p-8 flex flex-col items-center justify-center text-center space-y-3 py-16">
            <div class="w-16 h-16 bg-yellow-100 dark:bg-yellow-900/30 rounded-2xl flex items-center justify-center text-yellow-500 animate-pulse">
                <i data-lucide="shield-alert" class="w-8 h-8"></i>
            </div>
            <h3 class="text-sm font-bold text-gray-900 dark:text-white mt-4">{{ __('Tính năng bị ẩn ở chế độ Demo') }}</h3>
            <p class="text-xs text-gray-500 dark:text-gray-400 max-w-md leading-relaxed">
                {{ __('Để bảo vệ an toàn thông tin và cấu hình kết nối API của hệ thống, các thiết lập liên quan đến hoàn tiền Lazada không được hiển thị và chỉnh sửa ở chế độ dùng thử (Demo).') }}
            </p>
        </div>
    @else
        <!-- Cấu hình Tỷ lệ Cashback -->
        <div class="bg-gray-50/50 dark:bg-slate-900/50 rounded-2xl p-4 sm:p-6 border border-gray-150 dark:border-slate-800 space-y-6 w-full">
            <h3 class="text-xs font-bold text-gray-700 dark:text-slate-300 uppercase tracking-wider flex items-center gap-1.5 pb-2 border-b border-gray-200/65 dark:border-slate-800">
                <i data-lucide="percent" class="w-4 h-4 text-shopee"></i>
                {{ __('Cấu hình tỷ lệ hoàn tiền Lazada') }}
            </h3>

            <div class="p-3 bg-orange-50 border border-orange-100 text-[10px] text-orange-800 rounded-2xl dark:bg-orange-950/30 dark:border-orange-800/50 dark:text-orange-300">
                <h4 class="font-bold flex items-center gap-1"><i data-lucide="info" class="w-4 h-4"></i> {{ __('Cách hoạt động của tỷ lệ Cashback:') }}</h4>
                <p class="mt-1">{{ __('Khi Lazada trả hoa hồng cho đơn hàng, hệ thống sẽ trích xuất tỷ lệ bên dưới để hoàn lại cho khách hàng mua sản phẩm. Phần hoa hồng còn lại (100% - Tỷ lệ cashback) hệ thống sẽ giữ lại làm lợi nhuận hoạt động (và trích tiếp để trả hoa hồng MLM F1/F2 nếu có).') }}</p>
                {{-- Ghi chú nghiệp vụ: giải thích rủi ro tài chính khi đặt tỷ lệ vượt mốc 100% --}}
                <p class="mt-1.5 font-semibold">{{ __('Có thể đặt tỷ lệ vượt mốc 100% (tối đa :max%) để chạy chiến dịch bù lỗ hút khách. Khi vượt 100%, số tiền hoàn cho khách sẽ lớn hơn phần hoa hồng sàn trả về, hệ thống chịu lỗ phần chênh lệch — hãy cân nhắc kỹ trước khi lưu.', ['max' => $maxCashbackRate]) }}</p>
            </div>

            <div class="p-3 bg-sky-50 border border-sky-100 text-[10px] text-sky-800 rounded-2xl dark:bg-sky-950/30 dark:border-sky-800/50 dark:text-sky-300">
                <h4 class="font-bold flex items-center gap-1"><i data-lucide="triangle-alert" class="w-4 h-4"></i> {{ __('Lưu ý riêng của Lazada:') }}</h4>
                <p class="mt-1">{{ __('API tạo link của Lazada chỉ trả về tên sản phẩm và tỷ lệ hoa hồng (%), KHÔNG có giá bán và ảnh sản phẩm. Nếu bạn bật thêm mục "Cấu hình API lấy thông tin sản phẩm Lazada" bên dưới, hệ thống sẽ lấy được đầy đủ giá bán, ảnh và hoa hồng để hiển thị số tiền hoàn cụ thể. Nếu không bật, khi khách dán link hệ thống chỉ hiển thị tỷ lệ hoàn ước tính. Giá trị đơn và hoa hồng thực tế luôn được cập nhật chính xác lại khi đồng bộ báo cáo đơn hàng.') }}</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6">
                <div>
                    <!-- Cấu hình Tên nền tảng hiển thị của Lazada ở giao diện khách hàng -->
                    <label for="lazada_platform_name" class="block text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider mb-1">{{ __('Tên sàn hiển thị') }}</label>
                    <input type="text"
                        name="lazada_platform_name"
                        id="lazada_platform_name"
                        value="{{ $settings['lazada_platform_name'] ?? 'Lazada' }}"
                        placeholder="{{ __('Lazada...') }}"
                        class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-800 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-855 text-gray-700 dark:text-slate-300">
                    <span class="text-[9px] text-gray-400 mt-1 block">{{ __('Tên nền tảng Lazada hiển thị ở giao diện khách hàng (Mặc định: Lazada).') }}</span>
                </div>

                <div>
                    <!-- Cấu hình Trạng thái hoạt động của hoàn tiền Lazada (Bật/Tắt) -->
                    <label for="lazada_status" class="block text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider mb-1">{{ __('Trạng thái Hoàn tiền Lazada') }}</label>
                    <select name="lazada_status"
                        id="lazada_status"
                        class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-800 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-855 text-gray-700 dark:text-slate-300">
                        <option value="1" {{ ($settings['lazada_status'] ?? '0') === '1' ? 'selected' : '' }}>{{ __('BẬT (ON)') }}</option>
                        <option value="0" {{ ($settings['lazada_status'] ?? '0') === '0' ? 'selected' : '' }}>{{ __('TẮT (OFF)') }}</option>
                    </select>
                    <span class="text-[9px] text-gray-400 mt-1 block">{{ __('Bật hoặc tắt toàn bộ chức năng dán link và nhận hoa hồng hoàn tiền từ sàn Lazada.') }}</span>
                </div>

                <div>
                    <label for="lazada_cashback_rate" class="block text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider mb-1">{{ __('Tỷ lệ hoàn trả khách (%)') }}</label>
                    <input type="number"
                        name="lazada_cashback_rate"
                        id="lazada_cashback_rate"
                        value="{{ $settings['lazada_cashback_rate'] ?? '50' }}"
                        step="0.01"
                        min="0"
                        max="{{ $maxCashbackRate }}"
                        class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-800 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-855 text-gray-700 dark:text-slate-300">
                    <span class="text-[9px] text-gray-400 mt-1 block">{{ __('Tỷ lệ thực tế dùng để cộng số dư vào ví khách khi duyệt đơn Lazada từ API.') }}</span>
                </div>

                <div>
                    <label for="lazada_fake_cashback_rate" class="block text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider mb-1">{{ __('Tỷ lệ hoàn ảo (%)') }}</label>
                    <input type="number"
                        name="lazada_fake_cashback_rate"
                        id="lazada_fake_cashback_rate"
                        value="{{ $settings['lazada_fake_cashback_rate'] ?? '50' }}"
                        step="0.01"
                        min="0"
                        max="{{ $maxCashbackRate }}"
                        class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-800 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-855 text-gray-700 dark:text-slate-300">
                    <span class="text-[9px] text-gray-400 mt-1 block">{{ __('Tỷ lệ dùng để tính tỷ lệ hoàn hiển thị cho khách khi dán link. Nếu muốn hiển thị tỷ lệ hoàn thật thì điền giống như Tỷ lệ hoàn trả khách.') }}</span>
                </div>

                <div>
                    <!-- Cấu hình ẩn hoặc hiển thị Giá bán hiện tại của Lazada -->
                    <label for="lazada_show_current_price" class="block text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider mb-1">{{ __('Hiển thị Giá bán') }}</label>
                    <select name="lazada_show_current_price"
                        id="lazada_show_current_price"
                        class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-800 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-855 text-gray-700 dark:text-slate-300">
                        <option value="1" {{ ($settings['lazada_show_current_price'] ?? '1') === '1' ? 'selected' : '' }}>{{ __('HIỂN THỊ') }}</option>
                        <option value="0" {{ ($settings['lazada_show_current_price'] ?? '1') === '0' ? 'selected' : '' }}>{{ __('ẨN') }}</option>
                    </select>
                    <span class="text-[9px] text-gray-400 mt-1 block">{{ __('Ẩn hoặc hiển thị giá bán hiện tại của sản phẩm. Chỉ có tác dụng khi đã bật cấu hình API lấy thông tin sản phẩm Lazada bên dưới (vì API tạo link của Lazada không trả về giá).') }}</span>
                </div>
            </div>
        </div>

        <!-- Cấu hình API Lazada -->
        <div class="bg-gray-50/50 dark:bg-slate-900/50 rounded-2xl p-4 sm:p-6 border border-gray-150 dark:border-slate-800 space-y-6 w-full">
            <h3 class="text-xs font-bold text-gray-700 dark:text-slate-300 uppercase tracking-wider flex items-center gap-1.5 pb-2 border-b border-gray-200/65 dark:border-slate-800">
                <i data-lucide="key-round" class="w-4 h-4 text-shopee"></i>
                {{ __('Cấu hình kết nối Lazada Affiliate API') }}
            </h3>

            {{--
                Giải thích: Hướng dẫn Quản trị viên tới đúng trang Open API của Lazada Affiliate (Adsense)
                để lấy bộ thông tin App Key, App Secret và User Token, tránh nhập sai thông tin kết nối
                dẫn tới lỗi tạo tracking link và lỗi đồng bộ báo cáo đơn hàng.
            --}}
            <div class="p-3 bg-indigo-50 border border-indigo-100 text-[10px] text-indigo-800 rounded-2xl dark:bg-indigo-950/30 dark:border-indigo-800/50 dark:text-indigo-300">
                <h4 class="font-bold flex items-center gap-1"><i data-lucide="key-square" class="w-4 h-4"></i> {{ __('Lấy thông tin API ở đâu?') }}</h4>
                <p class="mt-1 leading-relaxed">{{ __('App Key, App Secret và User Token được cấp trong mục Open API của trang quản trị Lazada Affiliate (Adsense). Đăng nhập tài khoản affiliate của bạn rồi sao chép thông tin dán vào các ô bên dưới.') }}</p>
                <a href="https://adsense.lazada.vn/index.htm#!/integration/open_api" target="_blank" rel="noopener noreferrer" class="mt-2 inline-flex items-center gap-1 font-bold underline hover:text-indigo-600 dark:hover:text-indigo-200 break-all">
                    {{ __('Click vào đây để đi tới trang lấy Lazada Open API') }}
                    <i data-lucide="external-link" class="w-3.5 h-3.5"></i>
                </a>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <div>
                    <label for="apilazada_status" class="block text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider mb-1">{{ __('Trạng thái kết nối API') }}</label>
                    <select name="apilazada_status"
                        id="apilazada_status"
                        class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-800 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-850 text-gray-700 dark:text-slate-300">
                        <option value="1" {{ ($settings['apilazada_status'] ?? '1') === '1' ? 'selected' : '' }}>{{ __('BẬT (ON)') }}</option>
                        <option value="0" {{ ($settings['apilazada_status'] ?? '1') === '0' ? 'selected' : '' }}>{{ __('TẮT (OFF)') }}</option>
                    </select>
                    <span class="text-[9px] text-gray-400 mt-1 block">{{ __('Bật để tạo tracking link và đồng bộ đơn hàng từ Lazada Affiliate API.') }}</span>
                </div>

                <div>
                    <label for="apilazada_url" class="block text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider mb-1">{{ __('Đường dẫn API (API URL)') }}</label>
                    <input type="text"
                        name="apilazada_url"
                        id="apilazada_url"
                        value="{{ !empty($settings['apilazada_url']) ? $settings['apilazada_url'] : 'https://api.lazada.vn/rest' }}"
                        placeholder="https://api.lazada.vn/rest"
                        class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-800 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-850 text-gray-700 dark:text-slate-300">
                    <span class="text-[9px] text-gray-400 mt-1 block">{{ __('Host Lazada theo khu vực. Ví dụ: api.lazada.vn, api.lazada.sg, api.lazada.co.id... (Mặc định: https://api.lazada.vn/rest)') }}</span>
                </div>

                <div>
                    <label for="apilazada_app_key" class="block text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider mb-1">{{ __('Lazada App Key') }}</label>
                    <input type="text"
                        name="apilazada_app_key"
                        id="apilazada_app_key"
                        value="{{ $settings['apilazada_app_key'] ?? '' }}"
                        placeholder="{{ __('Nhập App Key...') }}"
                        class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-800 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-850 text-gray-700 dark:text-slate-300 font-mono">
                    <span class="text-[9px] text-gray-400 mt-1 block">{{ __('App Key của ứng dụng đăng ký trên Lazada Open Platform. Bắt buộc để ký request (khi gọi trực tiếp api.lazada.*).') }}</span>
                </div>

                <div>
                    <label for="apilazada_app_secret" class="block text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider mb-1">{{ __('Lazada App Secret') }}</label>
                    <input type="password"
                        name="apilazada_app_secret"
                        id="apilazada_app_secret"
                        value="{{ $settings['apilazada_app_secret'] ?? '' }}"
                        placeholder="{{ __('Nhập App Secret...') }}"
                        class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-800 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-850 text-gray-700 dark:text-slate-300 font-mono">
                    <span class="text-[9px] text-gray-400 mt-1 block">{{ __('App Secret dùng để ký chữ HMAC-SHA256 cho mỗi request. Để trống App Key/Secret nếu bạn gọi qua hệ thống trung gian.') }}</span>
                </div>

                <div>
                    <label for="apilazada_user_token" class="block text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider mb-1">{{ __('Lazada User Token') }}</label>
                    <input type="password"
                        name="apilazada_user_token"
                        id="apilazada_user_token"
                        value="{{ $settings['apilazada_user_token'] ?? '' }}"
                        placeholder="{{ __('Nhập User Token...') }}"
                        class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-800 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-850 text-gray-700 dark:text-slate-300">
                    <span class="text-[9px] text-gray-400 mt-1 block">{{ __('Access Token của tài khoản affiliate (tài liệu gọi là userToken) để tạo link và lấy báo cáo.') }}</span>
                </div>

                <div>
                    <label for="lazada_subid_param" class="block text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider mb-1">{{ __('Tham số Sub-ID đối soát') }}</label>
                    <input type="text"
                        name="lazada_subid_param"
                        id="lazada_subid_param"
                        value="{{ $settings['lazada_subid_param'] ?? 'sub_id1' }}"
                        placeholder="sub_id1"
                        class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-800 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-850 text-gray-700 dark:text-slate-300">
                    <span class="text-[9px] text-gray-400 mt-1 block">{{ __('Tên tham số sub-id được nối vào tracking link để đối soát đơn hàng (mặc định sub_id1, tương ứng subId1 trong báo cáo).') }}</span>
                </div>
            </div>
        </div>

        <!-- Cấu hình API lấy thông tin sản phẩm Lazada (tên, ảnh, giá bán, hoa hồng) -->
        <div class="bg-gray-50/50 dark:bg-slate-900/50 rounded-2xl p-4 sm:p-6 border border-gray-150 dark:border-slate-800 space-y-6 w-full">
            <h3 class="text-xs font-bold text-gray-700 dark:text-slate-300 uppercase tracking-wider flex items-center gap-1.5 pb-2 border-b border-gray-200/65 dark:border-slate-800">
                <i data-lucide="package-search" class="w-4 h-4 text-shopee"></i>
                {{ __('Cấu hình API lấy thông tin sản phẩm Lazada') }}
            </h3>

            <div class="p-3 bg-emerald-50 border border-emerald-100 text-[10px] text-emerald-800 rounded-2xl dark:bg-emerald-950/30 dark:border-emerald-800/50 dark:text-emerald-300">
                <h4 class="font-bold flex items-center gap-1"><i data-lucide="info" class="w-4 h-4"></i> {{ __('Vai trò của cấu hình này:') }}</h4>
                <p class="mt-1">{{ __('API tạo link của Lazada không trả về giá bán và ảnh sản phẩm. Bật cấu hình API này (cấu trúc giống API Shopee: GET ?product_link=... kèm Header X-API-KEY) để hệ thống lấy đầy đủ tên, ảnh, giá bán và hoa hồng thực tế, nhờ đó hiển thị được số tiền hoàn cụ thể thay vì chỉ hiển thị tỷ lệ hoàn ước tính.') }}</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <label for="apilazada_product_status" class="block text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider mb-1">{{ __('Trạng thái kết nối API') }}</label>
                    <select name="apilazada_product_status"
                        id="apilazada_product_status"
                        class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-800 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-850 text-gray-700 dark:text-slate-300">
                        <option value="1" {{ ($settings['apilazada_product_status'] ?? '0') === '1' ? 'selected' : '' }}>{{ __('BẬT (ON)') }}</option>
                        <option value="0" {{ ($settings['apilazada_product_status'] ?? '0') === '0' ? 'selected' : '' }}>{{ __('TẮT (OFF)') }}</option>
                    </select>
                    <span class="text-[9px] text-gray-400 mt-1 block">{{ __('Bật để lấy thông tin sản phẩm Lazada từ API ví dụ: Tên sp, ảnh sp, giá sp, hoa hồng,...') }}</span>
                </div>

                <div>
                    <label for="apilazada_product_url" class="block text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider mb-1">{{ __('Đường dẫn API (API URL)') }}</label>
                    <input type="text"
                        name="apilazada_product_url"
                        id="apilazada_product_url"
                        value="{{ !empty($settings['apilazada_product_url']) ? $settings['apilazada_product_url'] : 'https://apishopee.cmsnt.co/api/v1/lazada/product' }}"
                        placeholder="https://example.com/api/v1/lazada/product"
                        class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-800 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-850 text-gray-700 dark:text-slate-300">
                    <span class="text-[9px] text-gray-400 mt-1 block">{{ __('Nhập đường dẫn endpoint API dùng để lấy thông tin sản phẩm Lazada (Mặc định: https://apishopee.cmsnt.co/api/v1/lazada/product).') }}</span>
                </div>

                <div>
                    <label for="apilazada_product_key" class="block text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider mb-1">{{ __('API Key (X-API-KEY)') }}</label>
                    <input type="text"
                        name="apilazada_product_key"
                        id="apilazada_product_key"
                        value="{{ $settings['apilazada_product_key'] ?? '' }}"
                        placeholder="{{ __('Nhập API Key...') }}"
                        class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-800 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-850 text-gray-700 dark:text-slate-300 font-mono">
                    <span class="text-[9px] text-gray-400 mt-1 block">{{ __('Nhập mã API Key dùng để kết nối và xác thực với hệ thống API thông tin sản phẩm Lazada.') }}</span>
                </div>
            </div>
        </div>

        <!-- Cấu hình dọn dẹp cache sản phẩm Lazada -->
        <div class="bg-gray-50/50 dark:bg-slate-900/50 rounded-2xl p-4 sm:p-6 border border-gray-150 dark:border-slate-800 space-y-6 w-full">
            <h3 class="text-xs font-bold text-gray-700 dark:text-slate-300 uppercase tracking-wider flex items-center gap-1.5 pb-2 border-b border-gray-200/65 dark:border-slate-800">
                <i data-lucide="database" class="w-4 h-4 text-orange-500"></i>
                {{ __('Cấu hình dọn dẹp Cache sản phẩm Lazada') }}
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="cache_clean_estimated_hours_lazada" class="block text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider mb-1">
                        {{ __('Thời gian xóa sản phẩm ước tính (Giờ)') }}
                    </label>
                    <input type="number"
                        name="cache_clean_estimated_hours_lazada"
                        id="cache_clean_estimated_hours_lazada"
                        value="{{ $settings['cache_clean_estimated_hours_lazada'] ?? '24' }}"
                        min="1"
                        class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-800 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-855 text-gray-700 dark:text-slate-300">
                    <span class="text-[9px] text-gray-400 mt-1 block">{{ __('Tự động làm mới các sản phẩm cache ước tính sau số giờ cấu hình để tải lại thông tin mới chính xác hơn.') }}</span>
                </div>

                <div>
                    <label for="cache_clean_normal_days_lazada" class="block text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider mb-1">
                        {{ __('Thời gian xóa cache thông thường (Ngày)') }}
                    </label>
                    <input type="number"
                        name="cache_clean_normal_days_lazada"
                        id="cache_clean_normal_days_lazada"
                        value="{{ $settings['cache_clean_normal_days_lazada'] ?? '30' }}"
                        min="1"
                        class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-800 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-855 text-gray-700 dark:text-slate-300">
                    <span class="text-[9px] text-gray-400 mt-1 block">{{ __('Tự động xóa các sản phẩm cache thông thường nếu không có lượt tra cứu hoặc cập nhật nào mới.') }}</span>
                </div>
            </div>
        </div>

        <!-- Cấu hình đối soát và tự động hoàn tiền Lazada -->
        <div class="bg-gray-50/50 dark:bg-slate-900/50 rounded-2xl p-4 sm:p-6 border border-gray-150 dark:border-slate-800 space-y-6 w-full">
            <h3 class="text-xs font-bold text-gray-700 dark:text-slate-300 uppercase tracking-wider flex items-center gap-1.5 pb-2 border-b border-gray-200/65 dark:border-slate-800">
                <i data-lucide="check-square" class="w-4 h-4 text-shopee"></i>
                {{ __('Cấu hình đối soát & Tự động hoàn tiền Lazada') }}
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="lazada_check_product_match" class="block text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider mb-1">
                        {{ __('Bắt buộc khớp tên sản phẩm') }}
                    </label>
                    <select name="lazada_check_product_match"
                        id="lazada_check_product_match"
                        class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-800 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-850 text-gray-700 dark:text-slate-300">
                        <option value="1" {{ ($settings['lazada_check_product_match'] ?? '1') === '1' ? 'selected' : '' }}>{{ __('BẬT (ON) - Phải mua đúng sản phẩm') }}</option>
                        <option value="0" {{ ($settings['lazada_check_product_match'] ?? '1') === '0' ? 'selected' : '' }}>{{ __('TẮT (OFF) - Mua sản phẩm nào cũng được hoàn') }}</option>
                    </select>
                    <span class="text-[9px] text-gray-400 mt-1 block">{{ __('Bật để bắt buộc tên sản phẩm khách mua thực tế phải khớp với link lấy ban đầu. Tắt đi nếu muốn khách mua sản phẩm nào trong giỏ hàng cũng được hoàn tiền.') }}</span>
                </div>

                <div>
                    <label for="lazada_auto_cashback_future_orders" class="block text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider mb-1">
                        {{ __('Hoàn tiền đơn hàng tương lai (Cookie)') }}
                    </label>
                    <select name="lazada_auto_cashback_future_orders"
                        id="lazada_auto_cashback_future_orders"
                        class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-800 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-850 text-gray-700 dark:text-slate-300">
                        <option value="1" {{ ($settings['lazada_auto_cashback_future_orders'] ?? '0') === '1' ? 'selected' : '' }}>{{ __('BẬT (ON) - Hoàn tiền tự động các đơn mua trực tiếp sau đó') }}</option>
                        <option value="0" {{ ($settings['lazada_auto_cashback_future_orders'] ?? '0') === '0' ? 'selected' : '' }}>{{ __('TẮT (OFF) - Chỉ hoàn tiền cho đơn có click lấy link trực tiếp') }}</option>
                    </select>
                    <span class="text-[9px] text-gray-400 mt-1 block">{{ __('Nếu Bật, khi khách hàng click link 1 lần, các đơn hàng mua sau đó trực tiếp trên Lazada (trong thời gian cookie affiliate còn hiệu lực) vẫn sẽ được tự động hoàn tiền.') }}</span>
                </div>

                <div>
                    <label for="lazada_apply_bonus_commission" class="block text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider mb-1">
                        {{ __('Áp dụng hoàn tiền hoa hồng thưởng') }}
                    </label>
                    <select name="lazada_apply_bonus_commission"
                        id="lazada_apply_bonus_commission"
                        class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-800 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-850 text-gray-700 dark:text-slate-300">
                        <option value="1" {{ ($settings['lazada_apply_bonus_commission'] ?? '1') === '1' ? 'selected' : '' }}>{{ __('BẬT (ON) - Gồm cả hoa hồng thưởng (bonusPayout)') }}</option>
                        <option value="0" {{ ($settings['lazada_apply_bonus_commission'] ?? '1') === '0' ? 'selected' : '' }}>{{ __('TẮT (OFF) - Loại trừ hoa hồng thưởng') }}</option>
                    </select>
                    <span class="text-[9px] text-gray-400 mt-1 block">{{ __('Nếu Bật, hoa hồng tính hoàn tiền sẽ gồm cả hoa hồng cơ bản (basePayout) và thưởng (bonusPayout). Nếu Tắt, chỉ tính hoa hồng cơ bản (basePayout), loại trừ phần thưởng.') }}</span>
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
                <label for="hp_cashback_notice_lazada" class="block text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider mb-1">
                    {{ __('Nội dung lưu ý hiển thị ở kết quả tìm kiếm') }}
                </label>
                <textarea name="hp_cashback_notice_lazada"
                    id="hp_cashback_notice_lazada"
                    rows="5"
                    placeholder="{{ __('Nhập lưu ý về cashback hiển thị ở trang chủ sau khi phân tích sản phẩm thành công...') }}"
                    class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-800 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-850 text-gray-700 dark:text-slate-300">{{ $settings['hp_cashback_notice_lazada'] ?? '' }}</textarea>
                <span class="text-[9px] text-gray-400 mt-1 block">{{ __('Nội dung này sẽ hiển thị ngay dưới thẻ kết quả sản phẩm Lazada sau khi khách dán link phân tích thành công.') }}</span>
            </div>
        </div>

        <!-- Cấu hình tùy chỉnh mã đơn hàng Lazada -->
        <div class="bg-gray-50/50 dark:bg-slate-900/50 rounded-2xl p-4 sm:p-6 border border-gray-150 dark:border-slate-800 space-y-6 w-full">
            <h3 class="text-xs font-bold text-gray-700 dark:text-slate-300 uppercase tracking-wider flex items-center gap-1.5 pb-2 border-b border-gray-200/65 dark:border-slate-800">
                <i data-lucide="hash" class="w-4 h-4 text-shopee"></i>
                {{ __('Cấu hình tùy chỉnh mã đơn hàng Lazada') }}
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="order_code_prefix_lazada" class="block text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider mb-1">
                        {{ __('Tiền tố mã đơn hàng (Prefix)') }}
                    </label>
                    <input type="text"
                        name="order_code_prefix_lazada"
                        id="order_code_prefix_lazada"
                        value="{{ $settings['order_code_prefix_lazada'] ?? 'LZD' }}"
                        placeholder="{{ __('Ví dụ: LZD, DH, CASHBACK...') }}"
                        class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-800 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-850 text-gray-700 dark:text-slate-300">
                    <span class="text-[9px] text-gray-400 mt-1 block">{{ __('Ký tự viết liền không dấu đi kèm mã đơn hàng để nhận diện nguồn traffic hoặc thương hiệu.') }}</span>
                </div>

                <div>
                    <label for="order_code_prefix_position_lazada" class="block text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider mb-1">
                        {{ __('Vị trí tiền tố') }}
                    </label>
                    <select name="order_code_prefix_position_lazada"
                        id="order_code_prefix_position_lazada"
                        class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-800 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-850 text-gray-700 dark:text-slate-300">
                        <option value="left" {{ ($settings['order_code_prefix_position_lazada'] ?? 'left') === 'left' ? 'selected' : '' }}>{{ __('Bên trái (Ví dụ: LZDxxxxxx)') }}</option>
                        <option value="right" {{ ($settings['order_code_prefix_position_lazada'] ?? 'left') === 'right' ? 'selected' : '' }}>{{ __('Bên phải (Ví dụ: xxxxxxLZD)') }}</option>
                    </select>
                    <span class="text-[9px] text-gray-400 mt-1 block">{{ __('Chọn vị trí hiển thị của tiền tố nằm trước hay nằm sau chuỗi ký tự ngẫu nhiên.') }}</span>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-2">
                <div>
                    <label for="order_code_random_length_lazada" class="block text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider mb-1">
                        {{ __('Số ký tự ngẫu nhiên') }}
                    </label>
                    <input type="number"
                        name="order_code_random_length_lazada"
                        id="order_code_random_length_lazada"
                        value="{{ $settings['order_code_random_length_lazada'] ?? '10' }}"
                        min="4"
                        max="32"
                        class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-800 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-855 text-gray-700 dark:text-slate-300">
                    <span class="text-[9px] text-gray-400 mt-1 block">{{ __('Độ dài phần chuỗi ngẫu nhiên được sinh ra (giới hạn từ 4 đến 32 ký tự để đảm bảo tính duy nhất và bảo mật).') }}</span>
                </div>

                <div>
                    <label for="order_code_random_type_lazada" class="block text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider mb-1">
                        {{ __('Kiểu ký tự ngẫu nhiên') }}
                    </label>
                    <select name="order_code_random_type_lazada"
                        id="order_code_random_type_lazada"
                        class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-800 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-850 text-gray-700 dark:text-slate-300">
                        <option value="alphanumeric_upper" {{ ($settings['order_code_random_type_lazada'] ?? 'alphanumeric_upper') === 'alphanumeric_upper' ? 'selected' : '' }}>{{ __('Chữ và số in hoa (Ví dụ: A1B2C3)') }}</option>
                        <option value="alphanumeric" {{ ($settings['order_code_random_type_lazada'] ?? 'alphanumeric_upper') === 'alphanumeric' ? 'selected' : '' }}>{{ __('Chữ và số (Ví dụ: a1B2c3)') }}</option>
                        <option value="numeric" {{ ($settings['order_code_random_type_lazada'] ?? 'alphanumeric_upper') === 'numeric' ? 'selected' : '' }}>{{ __('Chỉ số (Ví dụ: 123456)') }}</option>
                        <option value="alpha_upper" {{ ($settings['order_code_random_type_lazada'] ?? 'alphanumeric_upper') === 'alpha_upper' ? 'selected' : '' }}>{{ __('Chỉ chữ in hoa (Ví dụ: ABCDEF)') }}</option>
                        <option value="alpha" {{ ($settings['order_code_random_type_lazada'] ?? 'alphanumeric_upper') === 'alpha' ? 'selected' : '' }}>{{ __('Chỉ chữ (Ví dụ: abcDEF)') }}</option>
                    </select>
                    <span class="text-[9px] text-gray-400 mt-1 block">{{ __('Lựa chọn định dạng ký tự ngẫu nhiên phù hợp nhất với mong muốn quản trị của bạn.') }}</span>
                </div>
            </div>
        </div>
    @endif
</div>
