{{-- 
    Partial View: Cấu hình Phím tắt iPhone (iOS Shortcuts)
    Vai trò: Quản lý trạng thái hoạt động, đường dẫn phím tắt iCloud, mô tả hướng dẫn trang phím tắt thành viên.
--}}
<!-- Bổ sung x-data quản lý việc hiển thị modal tài liệu API dành cho phím tắt -->
<div x-show="tab === 'shortcuts'" x-data="{ showApiDocsModal: false }" class="space-y-6" x-transition x-cloak>
    <div class="bg-gray-50/50 dark:bg-slate-900/50 rounded-2xl p-4 sm:p-6 border border-gray-150 dark:border-slate-800 space-y-6 w-full">
        <h3 class="text-xs font-bold text-gray-700 dark:text-slate-300 uppercase tracking-wider pb-2 border-b border-gray-200/65 dark:border-slate-800 flex items-center gap-1.5">
            <i data-lucide="smartphone" class="w-4 h-4 text-shopee"></i>
            {{ __('Cấu hình Phím tắt iPhone (iOS Shortcuts)') }}
        </h3>

        {{-- 
            Đoạn mã hiển thị liên kết truy cập nhanh trang hướng dẫn phím tắt ngoài Storefront.
            Giúp Quản trị viên dễ dàng nhấp để xem trước và kiểm tra giao diện hiển thị của thành viên.
        --}}
        <!-- Khối thông tin trang hướng dẫn phím tắt và tài liệu API tích hợp -->
        <div class="p-4 bg-blue-50 dark:bg-blue-950/20 border border-blue-150 dark:border-blue-900 rounded-2xl flex flex-col md:flex-row md:items-center justify-between gap-4 text-xs">
            <div class="flex items-start gap-2.5 text-blue-700 dark:text-blue-400">
                <i data-lucide="info" class="w-5 h-5 shrink-0 mt-0.5"></i>
                <div class="space-y-1">
                    <p class="font-bold">{{ __('Cổng API và Trang hướng dẫn phím tắt dành cho thành viên') }}</p>
                    <p class="text-[11px] text-gray-550 dark:text-slate-400">Đường dẫn: <a href="{{ route('shortcuts') }}" target="_blank" class="font-bold underline hover:text-blue-800 dark:hover:text-blue-300 break-all">{{ route('shortcuts') }}</a></p>
                </div>
            </div>
            <div class="flex items-center gap-2 shrink-0">
                <a href="{{ route('shortcuts') }}" target="_blank" class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl transition-all flex items-center justify-center gap-1">
                    <i data-lucide="external-link" class="w-3.5 h-3.5"></i>
                    <span>{{ __('Xem trang') }}</span>
                </a>
                <button type="button" @click="showApiDocsModal = true" class="px-3 py-1.5 bg-slate-200 hover:bg-slate-300 dark:bg-slate-850 dark:hover:bg-slate-700 text-gray-700 dark:text-slate-350 font-bold rounded-xl transition-all flex items-center justify-center gap-1">
                    <i data-lucide="code" class="w-3.5 h-3.5"></i>
                    <span>{{ __('Tài liệu API') }}</span>
                </button>
            </div>
        </div>

        <!-- Trạng thái phím tắt -->
        <div>
            <label for="ios_shortcut_status" class="block text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider mb-1">{{ __('Trạng thái hoạt động') }}</label>
            <select name="ios_shortcut_status"
                id="ios_shortcut_status"
                class="block w-full px-4 py-2.5 border border-gray-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-300">
                <option value="1" {{ ($settings['ios_shortcut_status'] ?? '0') === '1' ? 'selected' : '' }}>{{ __('BẬT (Cho phép thành viên sử dụng và tải phím tắt)') }}</option>
                <option value="0" {{ ($settings['ios_shortcut_status'] ?? '0') === '0' ? 'selected' : '' }}>{{ __('TẮT (Ẩn trang hướng dẫn phím tắt ngoài thành viên)') }}</option>
            </select>
            <span class="text-[9px] text-gray-400 mt-1 block">Bật để hiển thị menu và trang hướng dẫn Phím tắt trên Dashboard của thành viên.</span>
        </div>

        <!-- Đường dẫn Phím tắt iCloud -->
        <div>
            <label for="ios_shortcut_url" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1 dark:text-slate-300">{{ __('Đường dẫn Phím tắt iCloud (iOS Shortcut URL)') }}</label>
            <input type="text"
                name="ios_shortcut_url"
                id="ios_shortcut_url"
                value="{{ $settings['ios_shortcut_url'] ?? '' }}"
                placeholder="https://www.icloud.com/shortcuts/..."
                class="block w-full px-4 py-2.5 border border-gray-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-300">
            <span class="text-[9px] text-gray-400 mt-1 block">Đường dẫn chia sẻ phím tắt iCloud chính thức của sếp được lấy từ ứng dụng Phím tắt trên iPhone.</span>
        </div>

        <!-- Mô tả phụ trang hướng dẫn -->
        <div>
            <label for="ios_shortcut_desc" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1 dark:text-slate-300">{{ __('Mô tả ngắn trang hướng dẫn') }}</label>
            <input type="text"
                name="ios_shortcut_desc"
                id="ios_shortcut_desc"
                value="{{ $settings['ios_shortcut_desc'] ?? __('Hướng dẫn cài đặt phím tắt trên điện thoại iPhone giúp chuyển đổi link hoàn tiền Shopee siêu nhanh trong 1 giây') }}"
                class="block w-full px-4 py-2.5 border border-gray-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-300">
            <span class="text-[9px] text-gray-400 mt-1 block">Đoạn mô tả ngắn hiển thị dưới tiêu đề trang phím tắt của thành viên.</span>
        </div>

        <!-- Mô tả giới thiệu cách hoạt động -->
        <div>
            <label for="ios_shortcut_guide" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1 dark:text-slate-300">{{ __('Mô tả giới thiệu / cách hoạt động') }}</label>
            <textarea name="ios_shortcut_guide"
                id="ios_shortcut_guide"
                rows="4"
                class="block w-full px-4 py-2.5 border border-gray-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-300">{{ $settings['ios_shortcut_guide'] ?? __('Không cần truy cập website! Chỉ cần sao chép link Shopee trên điện thoại và chạy Phím tắt (Shortcuts). Hệ thống sẽ tự động nhận diện tài khoản, phân tích sản phẩm và copy lại link hoàn tiền mới vào bộ nhớ tạm.') }}</textarea>
            <span class="text-[9px] text-gray-400 mt-1 block">Nội dung giải thích chi tiết cách hoạt động của phím tắt hiển thị trong trang hướng dẫn.</span>
        </div>

        <!-- Modal tài liệu API phím tắt dành cho chủ web tích hợp -->
        <template x-teleport="body">
            <div x-show="showApiDocsModal"
                class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto"
                x-cloak>
                <div class="fixed inset-0 bg-black/60 backdrop-blur-sm transition-opacity" @click="showApiDocsModal = false"></div>

                <div class="relative bg-white dark:bg-slate-900 rounded-3xl shadow-2xl border border-gray-150 dark:border-slate-800 w-full max-w-2xl mx-4 overflow-hidden z-10 transition-all transform scale-100 flex flex-col max-h-[85vh]">
                    <!-- Header -->
                    <div class="px-6 py-4 border-b border-gray-100 dark:border-slate-800 flex items-center justify-between bg-gray-50/50 dark:bg-slate-900/50 shrink-0">
                        <h3 class="text-sm font-bold text-gray-900 dark:text-white flex items-center gap-2">
                            <i data-lucide="code" class="w-4.5 h-4.5 text-shopee"></i>
                            Tài Liệu API Tích Hợp Phím Tắt (iOS Shortcuts API)
                        </h3>
                        <button type="button" @click="showApiDocsModal = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-slate-200 transition-colors p-1.5 rounded-xl hover:bg-gray-100 dark:hover:bg-slate-800">
                            <i data-lucide="x" class="w-4.5 h-4.5"></i>
                        </button>
                    </div>

                    <!-- Body -->
                    <div class="p-6 overflow-y-auto space-y-6 text-xs leading-relaxed text-gray-650 dark:text-slate-350">
                        <p class="text-gray-500 dark:text-slate-400">
                            Hệ thống cung cấp cổng API mở để nhận diện sản phẩm từ Shopee & TikTok Shop, tự động sinh mã đối soát và chuyển đổi sang link affiliate hoàn tiền của thành viên. Sếp hoặc các lập trình viên có thể dùng API này để xây dựng phím tắt tự chế, Extension Chrome hoặc Bot Telegram riêng.
                        </p>

                        <!-- Method & Route -->
                        <div class="space-y-2">
                            <h4 class="font-bold text-gray-900 dark:text-white uppercase tracking-wider">1. Đường dẫn Endpoint & Phương thức</h4>
                            <div class="bg-gray-950 text-emerald-400 p-3.5 rounded-xl font-mono flex items-center gap-2 select-all overflow-x-auto">
                                <span class="bg-emerald-500/10 text-emerald-400 px-2 py-0.5 rounded text-[10px] font-bold">POST / GET</span>
                                <span>{{ url('/api/shopee/product') }}</span>
                            </div>
                        </div>

                        <!-- Parameters -->
                        <div class="space-y-2">
                            <h4 class="font-bold text-gray-900 dark:text-white uppercase tracking-wider">2. Tham số truyền vào (Query String / JSON Body)</h4>
                            <div class="border border-gray-200 dark:border-slate-800 rounded-2xl overflow-hidden">
                                <table class="w-full text-left border-collapse">
                                    <thead>
                                        <tr class="bg-gray-50 dark:bg-slate-800/50 text-[10px] font-bold text-gray-500 dark:text-slate-450 uppercase tracking-wider border-b border-gray-200 dark:border-slate-800">
                                            <th class="px-4 py-2.5">Tham số</th>
                                            <th class="px-4 py-2.5">Loại</th>
                                            <th class="px-4 py-2.5">Bắt buộc</th>
                                            <th class="px-4 py-2.5">Mô tả</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200 dark:divide-slate-800 text-gray-600 dark:text-slate-400">
                                        <tr>
                                            <td class="px-4 py-2.5 font-mono font-bold text-emerald-600 dark:text-emerald-400">api_token</td>
                                            <td class="px-4 py-2.5">string</td>
                                            <td class="px-4 py-2.5 text-red-500 font-bold">Bắt buộc (Ngoại trừ Session)</td>
                                            <td class="px-4 py-2.5">Mã API Key / Personal Access Token bí mật cá nhân của tài khoản (có thể truyền qua Header <code class="font-mono bg-gray-100 dark:bg-slate-800 px-1 py-0.5 rounded">Authorization: Bearer {token}</code> hoặc tham số <code class="font-mono bg-gray-100 dark:bg-slate-800 px-1 py-0.5 rounded">api_token</code> / <code class="font-mono bg-gray-100 dark:bg-slate-800 px-1 py-0.5 rounded">api_key</code>). Khoá này dùng để xác thực an toàn tuyệt đối, ngăn ngừa việc giả mạo tài khoản.</td>
                                        </tr>
                                        <tr>
                                            <td class="px-4 py-2.5 font-mono font-bold text-gray-800 dark:text-slate-200">url</td>
                                            <td class="px-4 py-2.5">string</td>
                                            <td class="px-4 py-2.5 text-red-500 font-bold">Bắt buộc</td>
                                            <td class="px-4 py-2.5">Đường dẫn sản phẩm Shopee, TikTok Shop cần rút gọn hoàn tiền.</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Response Format -->
                        <div class="space-y-3">
                            <h4 class="font-bold text-gray-900 dark:text-white uppercase tracking-wider">3. Cấu trúc kết quả phản hồi (Response JSON)</h4>
                            
                            <div class="space-y-1.5">
                                <p class="font-semibold text-gray-700 dark:text-slate-300 flex items-center gap-1.5">
                                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                    Thành công (HTTP Status 200 OK):
                                </p>
                                <pre class="bg-gray-950 text-gray-300 p-4 rounded-xl font-mono overflow-x-auto select-all leading-relaxed"><code class="text-emerald-400">{
  "status": "success",
  "message": "Lấy thông tin sản phẩm thành công!",
  "data": {
    "name": "Áo Khoác Blazer Nam Cao Cấp",
    "image": "https://cf.shopee.vn/file/vn-11134207...",
    "price": 250000,
    "cashback_amount": 17500,
    "cashback_rate": 7,
    "commission_amount": 25000,
    "affiliate_url": "{{ url('/sgxyz') }}",
    "platform": "shopee",
    "demo_order_id": "SG-167890",
    "cashback_notice": "Hoa hồng sẽ được duyệt sau khi nhận hàng..."
  },
  "logged_in": true
}</code></pre>
                            </div>

                            <div class="space-y-1.5">
                                <p class="font-semibold text-gray-700 dark:text-slate-300 flex items-center gap-1.5">
                                    <span class="w-2 h-2 rounded-full bg-red-500"></span>
                                    Thất bại hoặc quá lượt giới hạn (HTTP Status 400 / 429):
                                </p>
                                <pre class="bg-gray-950 text-gray-300 p-4 rounded-xl font-mono overflow-x-auto select-all leading-relaxed"><code class="text-rose-400">{
  "status": "error",
  "message": "Bạn đã đạt giới hạn tạo link hoàn tiền (10 link/5 phút). Vui lòng thử lại sau 5 phút."
}</code></pre>
                            </div>
                        </div>

                        <!-- Integration Guide -->
                        <div class="space-y-2">
                            <h4 class="font-bold text-gray-900 dark:text-white uppercase tracking-wider">4. Các Bước Tích Hợp Trên iOS Shortcuts</h4>
                            <div class="p-4 bg-orange-50/50 dark:bg-slate-950 border border-orange-100 dark:border-slate-800 rounded-2xl space-y-2">
                                <p class="font-bold text-orange-850 dark:text-orange-400">Cách thiết lập Phím tắt thủ công:</p>
                                <ul class="list-decimal list-inside space-y-1.5 text-gray-500 dark:text-slate-400">
                                    <li>Tạo mới một phím tắt, đặt tên phím tắt là <code class="font-bold text-gray-800 dark:text-slate-200">Hoàn Tiền Shopee</code>.</li>
                                    <li>Thêm khối hành động <strong class="text-gray-800 dark:text-slate-200">Get Contents of URL (Lấy nội dung của URL)</strong>.</li>
                                    <li>Điền URL: <code class="font-mono bg-gray-150 dark:bg-slate-800 px-1.5 py-0.5 rounded text-[11px]">{{ url('/api/shopee/product') }}</code>.</li>
                                    <li>Chọn Method (Phương thức) là <strong class="text-gray-800 dark:text-slate-200">POST</strong>.</li>
                                    <li>Thêm Request Body dạng JSON gồm 2 trường:
                                        <ul class="list-disc list-inside pl-4 mt-0.5 space-y-0.5">
                                            <li>Khóa <code class="font-mono">url</code>: Gán bằng biến <code class="font-semibold text-blue-500">Shortcut Input</code> (Đầu vào phím tắt).</li>
                                            <li>Khóa <code class="font-mono">api_token</code>: Nhập Mã API Key cá nhân của bạn.</li>
                                        </ul>
                                    </li>
                                    <li>Thêm khối <strong class="text-gray-800 dark:text-slate-200">Get Value from Dictionary (Lấy giá trị từ từ điển)</strong>, đặt khóa là <code class="font-mono">data.affiliate_url</code> từ kết quả trả về của API.</li>
                                    <li>Thêm khối <strong class="text-gray-800 dark:text-slate-200">Copy to Clipboard (Sao chép vào bộ nhớ tạm)</strong> để lưu lại link hoàn tiền.</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="px-6 py-4 border-t border-gray-100 dark:border-slate-800 flex justify-end bg-gray-50/50 dark:bg-slate-900/50 shrink-0">
                        <button type="button" @click="showApiDocsModal = false" class="px-4 py-2 border border-gray-250 dark:border-slate-700 hover:bg-gray-50 dark:hover:bg-slate-800 text-xs font-bold rounded-xl transition-all">
                            Đóng tài liệu
                        </button>
                    </div>
                </div>
            </div>
        </template>
    </div>
</div>
