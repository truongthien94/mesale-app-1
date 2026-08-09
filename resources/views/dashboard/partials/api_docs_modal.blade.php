{{--
    Partial View: Modal Tài liệu API dành cho thành viên
    Vai trò: Hiển thị tài liệu tích hợp cho 2 nhóm API được công bố cho thành viên:
        1. API tạo link hoàn tiền          — POST /cashback/link
        2. API tra cứu đơn hàng đã ghi nhận — GET /orders và GET /orders/{order_id}

    Điều kiện hiển thị: cấu hình `api_docs_enabled` = 1 — mặc định BẬT, Admin có thể tắt tại Cài đặt → Cấu hình khác.
    Xác thực: dùng chính Khóa API Token (API Key) cá nhân hiển thị phía trên trong trang Hồ sơ,
    gửi qua header `Authorization: Bearer <API Key>` (middleware api.auth:allow_key).

    Lưu ý kiến trúc: nhóm API này nằm ở tiền tố /api/v1/bot và ĐỘC LẬP hoàn toàn với hệ thống
    Open API (/api/v1/openapi). Tắt Open API không ảnh hưởng tới các endpoint mô tả trong tài liệu này.

    Yêu cầu Alpine: biến `showApiDocs` phải tồn tại trong scope cha bao quanh partial này.
--}}
@php
    // Địa chỉ gốc của nhóm API dành cho hệ thống ngoài / Bot
    $apiBase = rtrim(url('/api/v1/bot'), '/');
    // Khóa API cá nhân của thành viên đang đăng nhập (dùng làm ví dụ thật, sao chép chạy được ngay)
    $apiKey = auth()->user()->api_token;
    // Phiên bản che bớt của khóa, hiển thị mặc định để tránh lộ khóa khi người dùng chia sẻ màn hình
    $apiKeyMasked = Str::limit($apiKey, 12, '') . str_repeat('•', 16);
@endphp

<template x-teleport="body">
    <div x-show="showApiDocs"
        @click.self="showApiDocs = false"
        @keydown.escape.window="showApiDocs = false"
        class="fixed inset-0 z-[999] flex items-center justify-center bg-slate-950/60 backdrop-blur-sm p-3 sm:p-4"
        x-cloak x-transition>

        <div class="bg-white dark:bg-slate-900 rounded-3xl max-w-3xl w-full shadow-xl border border-gray-100 dark:border-slate-800 flex flex-col max-h-[92vh]" x-transition>

            <!-- Tiêu đề modal -->
            <div class="flex items-center justify-between gap-3 border-b border-gray-100 dark:border-slate-800 p-5 shrink-0">
                <div class="flex items-center gap-2.5 min-w-0">
                    <span class="w-9 h-9 rounded-xl bg-shopee/10 dark:bg-shopee/20 text-shopee dark:text-shopee-light flex items-center justify-center shrink-0">
                        <i data-lucide="book-open" class="w-4.5 h-4.5"></i>
                    </span>
                    <div class="min-w-0">
                        <h3 class="font-bold text-gray-900 dark:text-white text-sm leading-tight truncate">{{ __('Tài liệu API') }}</h3>
                        <p class="text-[11px] text-gray-400 dark:text-slate-500 leading-tight mt-0.5">{{ __('Hướng dẫn tích hợp tạo link hoàn tiền & tra cứu đơn hàng') }}</p>
                    </div>
                </div>
                <button type="button" @click="showApiDocs = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-slate-200 shrink-0 transition-colors">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <!-- Nội dung tài liệu (cuộn dọc) -->
            <div class="overflow-y-auto p-5 space-y-6 text-xs text-gray-600 dark:text-slate-300 leading-relaxed">

                {{-- ============ 1. THÔNG TIN CHUNG & XÁC THỰC ============ --}}
                <section class="space-y-3">
                    <h4 class="text-[11px] font-extrabold text-gray-800 dark:text-slate-200 uppercase tracking-wider flex items-center gap-1.5">
                        <i data-lucide="info" class="w-4 h-4 text-shopee"></i>
                        {{ __('1. Thông tin chung & Xác thực') }}
                    </h4>

                    <div class="rounded-2xl border border-gray-100 dark:border-slate-800 bg-gray-50/60 dark:bg-slate-950/30 p-4 space-y-3">
                        <!-- Địa chỉ gốc -->
                        <div x-data="{ copied: false }" class="space-y-1">
                            <span class="block text-[10px] font-bold text-gray-400 dark:text-slate-500 uppercase tracking-wider">{{ __('Địa chỉ gốc (Base URL)') }}</span>
                            <div class="flex items-center gap-2">
                                <code class="flex-1 min-w-0 font-mono text-[11px] text-gray-800 dark:text-slate-200 break-all select-all">{{ $apiBase }}</code>
                                <button type="button"
                                    @click="navigator.clipboard.writeText('{{ $apiBase }}').then(() => { copied = true; setTimeout(() => copied = false, 2000) })"
                                    class="shrink-0 text-[10px] font-bold text-shopee hover:underline flex items-center gap-1">
                                    <i data-lucide="check" class="w-3 h-3 text-emerald-500" x-show="copied" x-cloak></i>
                                    <i data-lucide="copy" class="w-3 h-3" x-show="!copied"></i>
                                    <span x-text="copied ? '{{ __('Đã sao chép') }}' : '{{ __('Sao chép') }}'"></span>
                                </button>
                            </div>
                        </div>

                        <div class="border-t border-gray-200/70 dark:border-slate-800"></div>

                        <!-- Khóa xác thực -->
                        <div x-data="{ copied: false, reveal: false }" class="space-y-1">
                            <span class="block text-[10px] font-bold text-gray-400 dark:text-slate-500 uppercase tracking-wider">{{ __('Khóa xác thực của bạn (API Key)') }}</span>
                            <div class="flex items-center gap-2">
                                <code class="flex-1 min-w-0 font-mono text-[11px] text-emerald-600 dark:text-emerald-400 break-all select-all"
                                    x-text="reveal ? @js($apiKey) : @js($apiKeyMasked)"></code>
                                <button type="button" @click="reveal = !reveal" class="shrink-0 text-[10px] font-bold text-gray-400 hover:text-shopee flex items-center gap-1">
                                    <i data-lucide="eye" class="w-3 h-3" x-show="!reveal"></i>
                                    <i data-lucide="eye-off" class="w-3 h-3" x-show="reveal" x-cloak></i>
                                    <span x-text="reveal ? '{{ __('Ẩn') }}' : '{{ __('Hiện') }}'"></span>
                                </button>
                                <button type="button"
                                    @click="navigator.clipboard.writeText('{{ $apiKey }}').then(() => { copied = true; setTimeout(() => copied = false, 2000) })"
                                    class="shrink-0 text-[10px] font-bold text-shopee hover:underline flex items-center gap-1">
                                    <i data-lucide="check" class="w-3 h-3 text-emerald-500" x-show="copied" x-cloak></i>
                                    <i data-lucide="copy" class="w-3 h-3" x-show="!copied"></i>
                                    <span x-text="copied ? '{{ __('Đã sao chép') }}' : '{{ __('Sao chép') }}'"></span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <p>{{ __('Mọi yêu cầu đều phải gửi kèm Khóa API Token cá nhân ở header sau:') }}</p>
                    <div class="bg-gray-900 dark:bg-slate-950 rounded-2xl p-3.5 font-mono text-[10.5px] text-gray-100 overflow-x-auto shadow-inner select-all leading-relaxed">Authorization: Bearer {{ $apiKey }}</div>

                    <div class="p-3.5 rounded-2xl bg-rose-50 dark:bg-rose-950/20 border border-rose-200 dark:border-rose-900/40 text-rose-800 dark:text-rose-300 flex items-start gap-2">
                        <i data-lucide="shield-alert" class="w-4 h-4 shrink-0 mt-px"></i>
                        <span class="text-[11px]">{{ __('Tuyệt đối không chia sẻ Khóa API Token cho người khác và không nhúng khóa vào mã nguồn phía trình duyệt. Nếu nghi ngờ bị lộ, hãy bấm "Tạo lại mã mới" trong trang Hồ sơ để vô hiệu hóa khóa cũ ngay lập tức.') }}</span>
                    </div>

                    <p class="font-semibold text-gray-700 dark:text-slate-200">{{ __('Cấu trúc phản hồi chuẩn:') }}</p>
                    <ul class="list-disc pl-5 space-y-0.5 text-[11px]">
                        <li><code class="font-mono text-shopee">success</code> — {{ __('true nếu thành công, false nếu có lỗi.') }}</li>
                        <li><code class="font-mono text-shopee">message</code> — {{ __('nội dung thông báo dành cho người dùng.') }}</li>
                        <li><code class="font-mono text-shopee">data</code> — {{ __('dữ liệu trả về khi thành công.') }}</li>
                        <li><code class="font-mono text-shopee">code</code> — {{ __('mã lỗi dạng chữ khi thất bại (dùng để xử lý tự động).') }}</li>
                    </ul>
                    <p class="text-[10px] text-gray-400 dark:text-slate-500">{{ __('Giới hạn tần suất chung: tối đa 120 yêu cầu mỗi phút cho mỗi địa chỉ IP.') }}</p>
                </section>

                {{-- ============ 2. API TẠO LINK HOÀN TIỀN ============ --}}
                <section class="space-y-3 pt-1">
                    <h4 class="text-[11px] font-extrabold text-gray-800 dark:text-slate-200 uppercase tracking-wider flex items-center gap-1.5">
                        <i data-lucide="link" class="w-4 h-4 text-shopee"></i>
                        {{ __('2. API tạo link hoàn tiền') }}
                    </h4>

                    <div class="flex items-center gap-1.5 flex-wrap">
                        <span class="text-[9px] font-bold px-1.5 py-0.5 rounded bg-emerald-100 dark:bg-emerald-500/15 text-emerald-700 dark:text-emerald-400">POST</span>
                        <code class="text-[11px] text-gray-600 dark:text-slate-300 font-mono break-all">{{ $apiBase }}/cashback/link</code>
                    </div>

                    <p>{{ __('Gửi lên link sản phẩm Shopee, TikTok Shop hoặc Lazada, hệ thống trả về link hoàn tiền rút gọn kèm số tiền hoàn dự kiến. Người mua bấm vào link này để đơn hàng được ghi nhận hoàn tiền.') }}</p>

                    <p class="font-semibold text-gray-700 dark:text-slate-200">{{ __('Tham số (JSON body):') }}</p>
                    <ul class="list-disc pl-5 space-y-0.5 text-[11px]">
                        <li><code class="font-mono text-shopee">url</code> — {{ __('bắt buộc. Link sản phẩm gốc từ Shopee, TikTok Shop hoặc Lazada (hỗ trợ cả link rút gọn shope.ee, shp.ee, lzd.co).') }}</li>
                    </ul>

                    <p class="font-semibold text-gray-700 dark:text-slate-200">{{ __('Ví dụ yêu cầu:') }}</p>
                    <div class="bg-gray-900 dark:bg-slate-950 rounded-2xl p-3.5 font-mono text-[10.5px] text-gray-100 overflow-x-auto shadow-inner select-all leading-relaxed">curl -X POST {{ $apiBase }}/cashback/link \<br>&nbsp;&nbsp;-H "Authorization: Bearer {{ $apiKey }}" \<br>&nbsp;&nbsp;-H "Content-Type: application/json" \<br>&nbsp;&nbsp;-d '{"url":"https://shopee.vn/product/123456/7890123"}'</div>

                    <p class="font-semibold text-gray-700 dark:text-slate-200">{{ __('Phản hồi thành công:') }}</p>
                    <div class="bg-gray-100 dark:bg-slate-950/60 rounded-2xl p-3.5 font-mono text-[10.5px] text-gray-700 dark:text-slate-300 overflow-x-auto leading-relaxed">{<br>&nbsp;&nbsp;"success": true,<br>&nbsp;&nbsp;"message": "{{ __('Lấy link hoàn tiền thành công!') }}",<br>&nbsp;&nbsp;"data": {<br>&nbsp;&nbsp;&nbsp;&nbsp;"trans_id": "SP8F3A21C",<br>&nbsp;&nbsp;&nbsp;&nbsp;"platform": "shopee",<br>&nbsp;&nbsp;&nbsp;&nbsp;"name": "{{ __('Tên sản phẩm') }}",<br>&nbsp;&nbsp;&nbsp;&nbsp;"image": "https://cf.shopee.vn/file/...",<br>&nbsp;&nbsp;&nbsp;&nbsp;"price": 250000,<br>&nbsp;&nbsp;&nbsp;&nbsp;"commission_amount": 25000,<br>&nbsp;&nbsp;&nbsp;&nbsp;"cashback_amount": 12000,<br>&nbsp;&nbsp;&nbsp;&nbsp;"cashback_rate": 4.8,<br>&nbsp;&nbsp;&nbsp;&nbsp;"is_estimated": false,<br>&nbsp;&nbsp;&nbsp;&nbsp;"affiliate_url": "{{ rtrim(url('/'), '/') }}/a1b2c3d4"<br>&nbsp;&nbsp;}<br>}</div>

                    <p class="font-semibold text-gray-700 dark:text-slate-200">{{ __('Mô tả trường dữ liệu:') }}</p>
                    <div class="rounded-2xl border border-gray-100 dark:border-slate-800 overflow-x-auto">
                        <table class="w-full text-left border-collapse text-[11px]">
                            <thead>
                                <tr class="bg-gray-50/70 dark:bg-slate-800/40 border-b border-gray-100 dark:border-slate-800 text-[10px] font-bold text-gray-500 dark:text-slate-400 uppercase tracking-wider">
                                    <th class="px-3 py-2">{{ __('Trường') }}</th>
                                    <th class="px-3 py-2">{{ __('Ý nghĩa') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-slate-800/60">
                                <tr><td class="px-3 py-2 font-mono text-shopee whitespace-nowrap">trans_id</td><td class="px-3 py-2">{{ __('Mã đối soát của lượt tạo link. Dùng để khớp với đơn hàng khi sàn trả dữ liệu về.') }}</td></tr>
                                <tr><td class="px-3 py-2 font-mono text-shopee whitespace-nowrap">affiliate_url</td><td class="px-3 py-2">{{ __('Link hoàn tiền rút gọn — đây là link cần gửi cho người mua.') }}</td></tr>
                                <tr><td class="px-3 py-2 font-mono text-shopee whitespace-nowrap">commission_amount</td><td class="px-3 py-2">{{ __('Số tiền hoa hồng dự kiến nhận từ sàn (VNĐ), chỉ mang tính ước tính tại thời điểm tạo link.') }}</td></tr>
                                <tr><td class="px-3 py-2 font-mono text-shopee whitespace-nowrap">cashback_amount</td><td class="px-3 py-2">{{ __('Số tiền hoàn dự kiến (VNĐ), chỉ mang tính ước tính tại thời điểm tạo link.') }}</td></tr>
                                <tr><td class="px-3 py-2 font-mono text-shopee whitespace-nowrap">cashback_rate</td><td class="px-3 py-2">{{ __('Tỉ lệ hoàn tiền dự kiến (%).') }}</td></tr>
                                <tr><td class="px-3 py-2 font-mono text-shopee whitespace-nowrap">is_estimated</td><td class="px-3 py-2">{{ __('true khi không lấy được giá bán thật (hay gặp ở Lazada). Lúc này price và cashback_amount có thể bằng 0 — hãy hiển thị theo cashback_rate dạng "hoàn khoảng X%" thay vì báo 0đ cho khách.') }}</td></tr>
                                <tr><td class="px-3 py-2 font-mono text-shopee whitespace-nowrap">platform</td><td class="px-3 py-2">{{ __('Sàn thương mại điện tử: shopee, tiktok hoặc lazada.') }}</td></tr>
                            </tbody>
                        </table>
                    </div>

                    <p class="font-semibold text-gray-700 dark:text-slate-200">{{ __('Các mã lỗi có thể gặp:') }}</p>
                    <ul class="list-disc pl-5 space-y-0.5 text-[11px]">
                        <li><code class="font-mono">VALIDATION_ERROR</code> (422) — {{ __('thiếu tham số url hoặc link sai định dạng.') }}</li>
                        <li><code class="font-mono">DOMAIN_NOT_SUPPORTED</code> (422) — {{ __('link không thuộc tên miền chính thức của sàn liên kết.') }}</li>
                        <li><code class="font-mono">PLATFORM_MAINTENANCE</code> (422) — {{ __('sàn tương ứng đang tạm ngưng hoàn tiền.') }}</li>
                        <li><code class="font-mono">RESOLVE_FAILED</code> (400) — {{ __('không phân tích được thông tin sản phẩm, hãy kiểm tra lại link.') }}</li>
                        <li><code class="font-mono">RATE_LIMITED</code> (429) — {{ __('vượt giới hạn số link được tạo trong 5 phút.') }}</li>
                    </ul>
                </section>

                {{-- ============ 3. API DANH SÁCH ĐƠN HÀNG ============ --}}
                <section class="space-y-3 pt-1">
                    <h4 class="text-[11px] font-extrabold text-gray-800 dark:text-slate-200 uppercase tracking-wider flex items-center gap-1.5">
                        <i data-lucide="receipt-text" class="w-4 h-4 text-shopee"></i>
                        {{ __('3. API danh sách đơn hàng đã ghi nhận') }}
                    </h4>

                    <div class="flex items-center gap-1.5 flex-wrap">
                        <span class="text-[9px] font-bold px-1.5 py-0.5 rounded bg-sky-100 dark:bg-sky-500/15 text-sky-700 dark:text-sky-400">GET</span>
                        <code class="text-[11px] text-gray-600 dark:text-slate-300 font-mono break-all">{{ $apiBase }}/orders</code>
                    </div>

                    <p>{{ __('Lấy danh sách các đơn hàng hoàn tiền đã được hệ thống ghi nhận cho tài khoản của bạn, kèm trạng thái xử lý của từng đơn. Hỗ trợ lọc và phân trang.') }}</p>

                    <p class="font-semibold text-gray-700 dark:text-slate-200">{{ __('Tham số truy vấn (query string):') }}</p>
                    <ul class="list-disc pl-5 space-y-0.5 text-[11px]">
                        <li><code class="font-mono text-shopee">status</code> — {{ __('lọc theo trạng thái: pending / approved / rejected (tùy chọn).') }}</li>
                        <li><code class="font-mono text-shopee">platform</code> — {{ __('lọc theo sàn: shopee / tiktok / lazada (tùy chọn).') }}</li>
                        <li><code class="font-mono text-shopee">search</code> — {{ __('tìm theo mã đơn hàng hoặc tên sản phẩm (tùy chọn).') }}</li>
                        <li><code class="font-mono text-shopee">page</code> — {{ __('số trang, mặc định 1.') }}</li>
                        <li><code class="font-mono text-shopee">per_page</code> — {{ __('số đơn mỗi trang, mặc định 15, tối đa 50.') }}</li>
                    </ul>

                    <div class="p-3.5 rounded-2xl bg-shopee/5 dark:bg-shopee/10 border border-shopee/20 flex items-start gap-2">
                        <i data-lucide="key-round" class="w-4 h-4 text-shopee shrink-0 mt-px"></i>
                        <span class="text-[11px]">{{ __('Dùng order_id (mã đơn hàng thật từ sàn) làm khóa duy nhất để đối chiếu và lưu trữ ở hệ thống của bạn. API không trả về mã định danh nội bộ của hệ thống nhằm đảm bảo an toàn dữ liệu.') }}</span>
                    </div>

                    <p class="font-semibold text-gray-700 dark:text-slate-200">{{ __('Ví dụ yêu cầu:') }}</p>
                    <div class="bg-gray-900 dark:bg-slate-950 rounded-2xl p-3.5 font-mono text-[10.5px] text-gray-100 overflow-x-auto shadow-inner select-all leading-relaxed">curl "{{ $apiBase }}/orders?status=approved&per_page=20" \<br>&nbsp;&nbsp;-H "Authorization: Bearer {{ $apiKey }}"</div>

                    <p class="font-semibold text-gray-700 dark:text-slate-200">{{ __('Phản hồi thành công:') }}</p>
                    <div class="bg-gray-100 dark:bg-slate-950/60 rounded-2xl p-3.5 font-mono text-[10.5px] text-gray-700 dark:text-slate-300 overflow-x-auto leading-relaxed">{<br>&nbsp;&nbsp;"success": true,<br>&nbsp;&nbsp;"data": {<br>&nbsp;&nbsp;&nbsp;&nbsp;"items": [<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;{<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"order_id": "240915ABCDEFG",<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"trans_id": "SP8F3A21C",<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"platform": "shopee",<br>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"product_name": "{{ __('Tên sản phẩm') }}",<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"product_image": "https://cf.shopee.vn/file/...",<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"original_price": 250000,<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"commission_amount": 25000,<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"cashback_amount": 12000,<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"cashback_rate": 4.8,<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"status": "approved",<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"rejected_reason": null,<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"approved_at": "2026-07-20T10:15:00+07:00",<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"created_at": "2026-07-18T09:02:31+07:00"<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;}<br>&nbsp;&nbsp;&nbsp;&nbsp;],<br>&nbsp;&nbsp;&nbsp;&nbsp;"pagination": { "current_page": 1, "per_page": 20, "total": 42, "last_page": 3 }<br>&nbsp;&nbsp;}<br>}</div>

                    <p class="font-semibold text-gray-700 dark:text-slate-200">{{ __('Mô tả trường dữ liệu trả về:') }}</p>
                    <div class="rounded-2xl border border-gray-100 dark:border-slate-800 overflow-x-auto">
                        <table class="w-full text-left border-collapse text-[11px]">
                            <thead>
                                <tr class="bg-gray-50/70 dark:bg-slate-800/40 border-b border-gray-100 dark:border-slate-800 text-[10px] font-bold text-gray-500 dark:text-slate-400 uppercase tracking-wider">
                                    <th class="px-3 py-2">{{ __('Trường') }}</th>
                                    <th class="px-3 py-2">{{ __('Ý nghĩa') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-slate-800/60">
                                <tr><td class="px-3 py-2 font-mono text-shopee whitespace-nowrap">order_id</td><td class="px-3 py-2">{{ __('Mã đơn hàng thực tế trên sàn.') }}</td></tr>
                                <tr><td class="px-3 py-2 font-mono text-shopee whitespace-nowrap">trans_id</td><td class="px-3 py-2">{{ __('Mã giao dịch đối soát sub_id lượt click.') }}</td></tr>
                                <tr><td class="px-3 py-2 font-mono text-shopee whitespace-nowrap">platform</td><td class="px-3 py-2">{{ __('Sàn thương mại điện tử: shopee, tiktok hoặc lazada.') }}</td></tr>
                                <tr><td class="px-3 py-2 font-mono text-shopee whitespace-nowrap">product_name</td><td class="px-3 py-2">{{ __('Tên sản phẩm mua.') }}</td></tr>
                                <tr><td class="px-3 py-2 font-mono text-shopee whitespace-nowrap">original_price</td><td class="px-3 py-2">{{ __('Giá trị đơn hàng / giá trị sản phẩm gốc (VNĐ).') }}</td></tr>
                                <tr><td class="px-3 py-2 font-mono text-shopee whitespace-nowrap">commission_amount</td><td class="px-3 py-2">{{ __('Tổng số tiền hoa hồng thực tế nhận từ sàn (VNĐ).') }}</td></tr>
                                <tr><td class="px-3 py-2 font-mono text-shopee whitespace-nowrap">cashback_amount</td><td class="px-3 py-2">{{ __('Số tiền hoàn trả lại cho người mua (VNĐ).') }}</td></tr>
                                <tr><td class="px-3 py-2 font-mono text-shopee whitespace-nowrap">cashback_rate</td><td class="px-3 py-2">{{ __('Tỷ lệ hoàn tiền (%).') }}</td></tr>
                                <tr><td class="px-3 py-2 font-mono text-shopee whitespace-nowrap">status</td><td class="px-3 py-2">{{ __('Trạng thái đơn: pending (Chờ duyệt), approved (Đã duyệt), rejected (Bị từ chối).') }}</td></tr>
                            </tbody>
                        </table>
                    </div>
                </section>

                {{-- ============ 4. API CHI TIẾT ĐƠN HÀNG ============ --}}
                <section class="space-y-3 pt-1">
                    <h4 class="text-[11px] font-extrabold text-gray-800 dark:text-slate-200 uppercase tracking-wider flex items-center gap-1.5">
                        <i data-lucide="file-search" class="w-4 h-4 text-shopee"></i>
                        {{ __('4. API chi tiết & trạng thái của một đơn hàng') }}
                    </h4>

                    <div class="flex items-center gap-1.5 flex-wrap">
                        <span class="text-[9px] font-bold px-1.5 py-0.5 rounded bg-sky-100 dark:bg-sky-500/15 text-sky-700 dark:text-sky-400">GET</span>
                        <code class="text-[11px] text-gray-600 dark:text-slate-300 font-mono break-all">{{ $apiBase }}/orders/{order_id}</code>
                    </div>

                    <p>{{ __('Xem chi tiết đầy đủ của một đơn hàng, bao gồm mã đối soát, hoa hồng gốc từ sàn, tỉ lệ hoàn và lý do từ chối (nếu đơn bị từ chối). Thay phần {order_id} trên đường dẫn bằng mã đơn hàng lấy từ API danh sách.') }}</p>

                    <p class="font-semibold text-gray-700 dark:text-slate-200">{{ __('Ví dụ yêu cầu:') }}</p>
                    <div class="bg-gray-900 dark:bg-slate-950 rounded-2xl p-3.5 font-mono text-[10.5px] text-gray-100 overflow-x-auto shadow-inner select-all leading-relaxed">curl {{ $apiBase }}/orders/240915ABCDEFG \<br>&nbsp;&nbsp;-H "Authorization: Bearer {{ $apiKey }}"</div>

                    <p class="font-semibold text-gray-700 dark:text-slate-200">{{ __('Phản hồi thành công:') }}</p>
                    <div class="bg-gray-100 dark:bg-slate-950/60 rounded-2xl p-3.5 font-mono text-[10.5px] text-gray-700 dark:text-slate-300 overflow-x-auto leading-relaxed">{<br>&nbsp;&nbsp;"success": true,<br>&nbsp;&nbsp;"data": {<br>&nbsp;&nbsp;&nbsp;&nbsp;"order_id": "240915ABCDEFG",<br>&nbsp;&nbsp;&nbsp;&nbsp;"trans_id": "SP8F3A21C",<br>&nbsp;&nbsp;&nbsp;&nbsp;"platform": "shopee",<br>&nbsp;&nbsp;&nbsp;&nbsp;"product_name": "{{ __('Tên sản phẩm') }}",<br>&nbsp;&nbsp;&nbsp;&nbsp;"product_image": "https://cf.shopee.vn/file/...",<br>&nbsp;&nbsp;&nbsp;&nbsp;"shop_name": "{{ __('Tên gian hàng') }}",<br>&nbsp;&nbsp;&nbsp;&nbsp;"original_price": 250000,<br>&nbsp;&nbsp;&nbsp;&nbsp;"commission_amount": 25000,<br>&nbsp;&nbsp;&nbsp;&nbsp;"cashback_amount": 12000,<br>&nbsp;&nbsp;&nbsp;&nbsp;"cashback_rate": 4.8,<br>&nbsp;&nbsp;&nbsp;&nbsp;"status": "approved",<br>&nbsp;&nbsp;&nbsp;&nbsp;"rejected_reason": null,<br>&nbsp;&nbsp;&nbsp;&nbsp;"approved_at": "2026-07-20T10:15:00+07:00",<br>&nbsp;&nbsp;&nbsp;&nbsp;"created_at": "2026-07-18T09:02:31+07:00"<br>&nbsp;&nbsp;}<br>}</div>

                    <p class="font-semibold text-gray-700 dark:text-slate-200">{{ __('Mô tả các trường bổ sung:') }}</p>
                    <div class="rounded-2xl border border-gray-100 dark:border-slate-800 overflow-x-auto">
                        <table class="w-full text-left border-collapse text-[11px]">
                            <thead>
                                <tr class="bg-gray-50/70 dark:bg-slate-800/40 border-b border-gray-100 dark:border-slate-800 text-[10px] font-bold text-gray-500 dark:text-slate-400 uppercase tracking-wider">
                                    <th class="px-3 py-2">{{ __('Trường') }}</th>
                                    <th class="px-3 py-2">{{ __('Ý nghĩa') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-slate-800/60">
                                <tr><td class="px-3 py-2 font-mono text-shopee whitespace-nowrap">trans_id</td><td class="px-3 py-2">{{ __('Mã giao dịch đối soát sub_id lượt click.') }}</td></tr>
                                <tr><td class="px-3 py-2 font-mono text-shopee whitespace-nowrap">shop_name</td><td class="px-3 py-2">{{ __('Tên gian hàng / Shop bán sản phẩm.') }}</td></tr>
                                <tr><td class="px-3 py-2 font-mono text-shopee whitespace-nowrap">commission_amount</td><td class="px-3 py-2">{{ __('Tổng số tiền hoa hồng thực tế nhận từ sàn (VNĐ).') }}</td></tr>
                            </tbody>
                        </table>
                    </div>

                    <p class="text-[11px]">{{ __('Nếu đơn hàng không tồn tại hoặc không thuộc tài khoản của bạn, API trả về mã lỗi') }} <code class="font-mono">ORDER_NOT_FOUND</code> (404).</p>
                </section>

                {{-- ============ 5. BẢNG TRẠNG THÁI ĐƠN HÀNG ============ --}}
                <section class="space-y-3 pt-1">
                    <h4 class="text-[11px] font-extrabold text-gray-800 dark:text-slate-200 uppercase tracking-wider flex items-center gap-1.5">
                        <i data-lucide="list-checks" class="w-4 h-4 text-shopee"></i>
                        {{ __('5. Ý nghĩa trạng thái đơn hàng') }}
                    </h4>

                    <div class="rounded-2xl border border-gray-100 dark:border-slate-800 overflow-x-auto">
                        <table class="w-full text-left border-collapse text-[11px]">
                            <thead>
                                <tr class="bg-gray-50/70 dark:bg-slate-800/40 border-b border-gray-100 dark:border-slate-800 text-[10px] font-bold text-gray-500 dark:text-slate-400 uppercase tracking-wider">
                                    <th class="px-3 py-2">{{ __('Giá trị') }}</th>
                                    <th class="px-3 py-2">{{ __('Trạng thái') }}</th>
                                    <th class="px-3 py-2">{{ __('Diễn giải') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-slate-800/60">
                                <tr>
                                    <td class="px-3 py-2 font-mono whitespace-nowrap">pending</td>
                                    <td class="px-3 py-2 whitespace-nowrap">
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[9px] font-bold bg-amber-50 text-amber-600 border border-amber-100 dark:bg-amber-950/30 dark:text-amber-400 dark:border-amber-900/30">{{ __('Chờ duyệt') }}</span>
                                    </td>
                                    <td class="px-3 py-2">{{ __('Đơn đã được sàn ghi nhận và đang chờ đối soát. Tiền hoàn chưa được cộng vào ví.') }}</td>
                                </tr>
                                <tr>
                                    <td class="px-3 py-2 font-mono whitespace-nowrap">approved</td>
                                    <td class="px-3 py-2 whitespace-nowrap">
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[9px] font-bold bg-emerald-50 text-emerald-600 border border-emerald-100 dark:bg-emerald-950/30 dark:text-emerald-400 dark:border-emerald-900/30">{{ __('Đã duyệt') }}</span>
                                    </td>
                                    <td class="px-3 py-2">{{ __('Đơn đã được duyệt, số tiền cashback_amount đã cộng vào số dư ví. Thời điểm duyệt nằm ở trường approved_at.') }}</td>
                                </tr>
                                <tr>
                                    <td class="px-3 py-2 font-mono whitespace-nowrap">rejected</td>
                                    <td class="px-3 py-2 whitespace-nowrap">
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[9px] font-bold bg-rose-50 text-rose-600 border border-rose-100 dark:bg-rose-950/30 dark:text-rose-400 dark:border-rose-900/30">{{ __('Từ chối') }}</span>
                                    </td>
                                    <td class="px-3 py-2">{{ __('Đơn bị hủy hoặc không hợp lệ nên không được hoàn tiền. Lý do cụ thể nằm ở trường rejected_reason.') }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="p-3.5 rounded-2xl bg-shopee/5 dark:bg-shopee/10 border border-shopee/20 flex items-start gap-2">
                        <i data-lucide="lightbulb" class="w-4 h-4 text-shopee shrink-0 mt-px"></i>
                        <span class="text-[11px]">{{ __('Đơn hàng chỉ xuất hiện trong API sau khi hệ thống đồng bộ dữ liệu đơn thực tế từ sàn về (thường mất vài giờ đến vài ngày kể từ lúc đặt hàng), không xuất hiện ngay khi vừa tạo link.') }}</span>
                    </div>
                </section>
            </div>

            <!-- Chân modal -->
            <div class="border-t border-gray-100 dark:border-slate-800 p-4 shrink-0">
                <button type="button" @click="showApiDocs = false"
                    class="w-full inline-flex items-center justify-center gap-2 px-5 py-3 sm:py-2.5 text-xs font-semibold text-white bg-shopee hover:bg-shopee-dark active:scale-[0.98] rounded-2xl transition-all shadow-md shadow-shopee/20">
                    <i data-lucide="check" class="w-4 h-4"></i>
                    {{ __('Đã hiểu') }}
                </button>
            </div>
        </div>
    </div>
</template>
