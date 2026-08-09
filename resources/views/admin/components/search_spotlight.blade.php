@php
    $adminItems = [
        // 1. Nhóm chức năng chính
        [ 'name' => __('Tổng quan hệ thống'), 'category' => __('Chức năng'), 'url' => route("admin.dashboard"), 'icon' => 'layout-dashboard', 'keywords' => 'tong quan dashboard bieu do thong ke doanh thu hoa hong' ],
        [ 'name' => __('Cập nhật phiên bản'), 'category' => __('Chức năng'), 'url' => route("admin.update.index"), 'icon' => 'git-pull-request', 'keywords' => 'cap nhat phien ban he thong update phien ban nang cap version' ],
        [ 'name' => __('Quản lý thành viên'), 'category' => __('Chức năng'), 'url' => route("admin.users.index"), 'icon' => 'users', 'keywords' => 'quan ly thanh vien user nguoi dung khach hang tai khoan admin balance so du' ],
        [ 'name' => __('Thống kê đăng ký thành viên'), 'category' => __('Chức năng'), 'url' => route("admin.users.index") . '#stats', 'icon' => 'bar-chart-3', 'keywords' => 'thong ke dang ky thanh vien bieu do chart registration statistics user signup' ],
        [ 'name' => __('Xuất danh sách thành viên (CSV)'), 'category' => __('Chức năng'), 'url' => route("admin.users.index") . '#export', 'icon' => 'file-spreadsheet', 'keywords' => 'xuat file csv danh sach thanh vien export users excel tuy chon cot sap xep cot id email so dien thoai' ],
        [ 'name' => __('Quản lý sản phẩm'), 'category' => __('Chức năng'), 'url' => route("admin.products.index"), 'icon' => 'shopping-bag', 'keywords' => 'quan ly san pham shopee products danh sach san pham' ],
        [ 'name' => __('Dọn dẹp sản phẩm theo thời gian'), 'category' => __('Chức năng'), 'url' => route("admin.products.index"), 'icon' => 'eraser', 'keywords' => 'don dep san pham cleanup cache xoa san pham cu theo thoi gian giai phong dung luong database' ],
        [ 'name' => __('Xóa hàng loạt sản phẩm'), 'category' => __('Chức năng'), 'url' => route("admin.products.index"), 'icon' => 'trash-2', 'keywords' => 'xoa hang loat san pham bulk delete products tich chon xoa nhanh nhieu san pham' ],
        [ 'name' => __('Đơn hàng hoàn tiền'), 'category' => __('Chức năng'), 'url' => route("admin.cashback.index"), 'icon' => 'receipt', 'keywords' => 'don hang hoan tien cashback don hang shopee don cho duyet' ],
        [ 'name' => __('Thống kê đơn hoàn tiền'), 'category' => __('Chức năng'), 'url' => route("admin.cashback.index") . '#stats', 'icon' => 'bar-chart-3', 'keywords' => 'thong ke don hoan tien bieu do chart cashback statistics don hang cho duyet thanh cong tu choi' ],
        [ 'name' => __('Quản lý rút tiền'), 'category' => __('Chức năng'), 'url' => route("admin.withdrawals.index"), 'icon' => 'wallet', 'keywords' => 'quan ly rut tien yeu cau rut withdraw yeu cau duyet rut duyet nhanh hang loat xoa nhanh hang loat tich chon nhieu yeu cau bulk approve bulk delete' ],
        [ 'name' => __('Quản lý quà tặng'), 'category' => __('Chức năng'), 'url' => route("admin.gifts.index"), 'icon' => 'gift', 'keywords' => 'quan ly qua tang doi qua gift redemptions voucher code' ],
        [ 'name' => __('Quản lý Giftcode'), 'category' => __('Chức năng'), 'url' => route("admin.giftcodes.index"), 'icon' => 'ticket', 'keywords' => 'quan ly giftcode gift code ma qua tang nhap ma nhan thuong khuyen mai promo code redeem ma uu dai' ],
        [ 'name' => __('Quản lý Mã giảm giá'), 'category' => __('Chức năng'), 'url' => route("admin.coupons.index"), 'icon' => 'ticket-percent', 'keywords' => 'quan ly ma giam gia coupon voucher ma khuyen mai discount code them sua xoa tu nhap thu cong shopee' ],
        [ 'name' => __('Quản lý Nhiệm vụ'), 'category' => __('Chức năng'), 'url' => route("admin.tasks.index"), 'icon' => 'list-checks', 'keywords' => 'quan ly nhiem vu task mission nhiem vu nhan thuong phan thuong reward tien thuong thuc hien' ],
        [ 'name' => __('Tiến độ Nhiệm vụ thành viên'), 'category' => __('Chức năng'), 'url' => route("admin.tasks.index", ["tab" => "submissions"]), 'icon' => 'users', 'keywords' => 'tien do nhiem vu submission user tasks ket qua hoan thanh xac nhan admin duyet' ],
        [ 'name' => __('Nhiệm vụ chờ duyệt'), 'category' => __('Chức năng'), 'url' => route("admin.tasks.index", ["tab" => "submissions", "status" => "pending"]), 'icon' => 'clock', 'keywords' => 'nhiem vu cho duyet pending xac nhan hoan thanh thu cong custom yeu cau duyet kiem duyet' ],
        [ 'name' => __('Quản lý Page tĩnh'), 'category' => __('Chức năng'), 'url' => route("admin.pages.index"), 'icon' => 'file-text', 'keywords' => 'quan ly page trang tinh static page builder landing page' ],
        [ 'name' => __('Quản lý Menu'), 'category' => __('Chức năng'), 'url' => route("admin.menus.index"), 'icon' => 'menu', 'keywords' => 'quan ly menu navigation thanh dieu huong link footer header' ],
        [ 'name' => __('Banner quảng cáo'), 'category' => __('Chức năng'), 'url' => route("admin.banners.index"), 'icon' => 'image', 'keywords' => 'banner quang cao anh slide trinh chieu quang cao' ],
        [ 'name' => __('Quản lý Media'), 'category' => __('Chức năng'), 'url' => route("admin.media.index"), 'icon' => 'folder-open', 'keywords' => 'quan ly media thu vien anh tep tin elfinder upload' ],
        [ 'name' => __('Gửi thông báo'), 'category' => __('Chức năng'), 'url' => route("admin.notifications.index"), 'icon' => 'bell', 'keywords' => 'gui thong bao notification tin nhan he thong thong bao chung' ],
        [ 'name' => __('Email Campaign'), 'category' => __('Chức năng'), 'url' => route("admin.email_campaigns.index"), 'icon' => 'mail-check', 'keywords' => 'email campaign chien dich email marketing gui email hang loat newsletter bulk email smtp marketing' ],
        [ 'name' => __('Tạo chiến dịch email'), 'category' => __('Chức năng'), 'url' => route("admin.email_campaigns.create"), 'icon' => 'mail-plus', 'keywords' => 'tao chien dich email moi create campaign email marketing newsletter' ],
        [ 'name' => __('Vai trò & Quyền hạn'), 'category' => __('Chức năng'), 'url' => route("admin.roles.index"), 'icon' => 'shield-check', 'keywords' => 'vai tro quyen han roles permissions phan quyen admin nhan vien' ],
        [ 'name' => __('Quản lý ngôn ngữ'), 'category' => __('Chức năng'), 'url' => route("admin.languages.index"), 'icon' => 'languages', 'keywords' => 'quan ly ngon ngu da ngon ngu language translations dich thuat' ],
        [ 'name' => __('Quản lý tiền tệ'), 'category' => __('Chức năng'), 'url' => route("admin.currencies.index"), 'icon' => 'coins', 'keywords' => 'quan ly tien te currency ty gia dong usdt' ],
        [ 'name' => __('Công cụ hệ thống'), 'category' => __('Chức năng'), 'url' => route("admin.tools.index"), 'icon' => 'wrench', 'keywords' => 'cong cu tools import don hang excel shopee dong bo cookie affiliate tai khoan' ],
        [ 'name' => __('Quản lý Bot'), 'category' => __('Chức năng'), 'url' => route("admin.bots.index"), 'icon' => 'bot', 'keywords' => 'quan ly bot zalo telegram chatbot webhook tin nhan tu dong link hoan tien' ],
        [ 'name' => __('Cấu hình Zalo Bot'), 'category' => __('Chức năng'), 'url' => route("admin.bots.index", ["tab" => "zalo"]), 'icon' => 'message-circle', 'keywords' => 'cau hinh zalo bot token webhook zalo chatbot tin nhan tu dong link hoan tien' ],
        [ 'name' => __('Cấu hình Telegram Bot'), 'category' => __('Chức năng'), 'url' => route("admin.bots.index", ["tab" => "telegram"]), 'icon' => 'send', 'keywords' => 'cau hinh telegram bot token botfather webhook chatbot tin nhan tu dong link hoan tien' ],
        [ 'name' => __('Lịch sử tin nhắn Bot'), 'category' => __('Chức năng'), 'url' => route("admin.bots.index", ["tab" => "messages"]), 'icon' => 'message-square', 'keywords' => 'lich su tin nhan bot zalo telegram chat history log message user gui nhan' ],
        [ 'name' => __('Giao diện website'), 'category' => __('Chức năng'), 'url' => route("admin.appearance.index"), 'icon' => 'palette', 'keywords' => 'giao dien website theme mau sac shopee logo layout' ],
        [ 'name' => __('Trạng thái hệ thống'), 'category' => __('Chức năng'), 'url' => route("admin.system_status"), 'icon' => 'activity', 'keywords' => 'trang thai he thong suc khoe server cpu ram disk php laravel cron job monitor giam sat cache clear optimize config route' ],
        [ 'name' => __('Dọn dẹp hệ thống'), 'category' => __('Chức năng'), 'url' => route("admin.system_cleanup.index"), 'icon' => 'shield-alert', 'keywords' => 'don dep he thong database backup sao luu du lieu cache logs failed jobs' ],
        [ 'name' => __('Cài đặt hệ thống'), 'category' => __('Chức năng'), 'url' => route("admin.settings.index"), 'icon' => 'settings', 'keywords' => 'cai dat he thong settings cau hinh' ],
        
        // 2. Nhóm Nhật ký & Logs
        [ 'name' => __('Biến động số dư'), 'category' => __('Nhật ký & Logs'), 'url' => route("admin.balance_logs.index"), 'icon' => 'arrow-left-right', 'keywords' => 'bien dong so du lich su cong tru tien balance logs' ],
        [ 'name' => __('Nhật ký hoạt động'), 'category' => __('Nhật ký & Logs'), 'url' => route("admin.logs.activity"), 'icon' => 'history', 'keywords' => 'nhat ky hoat dong activity logs admin thao tac lich su' ],
        [ 'name' => __('Nhật ký điểm danh'), 'category' => __('Nhật ký & Logs'), 'url' => route("admin.logs.checkin"), 'icon' => 'calendar-check', 'keywords' => 'nhat ky diem danh checkin log diem danh hang ngay coin hang ngay' ],
        [ 'name' => __('Nhật ký Affiliate'), 'category' => __('Nhật ký & Logs'), 'url' => route("admin.logs.referrals"), 'icon' => 'users-2', 'keywords' => 'nhat ky affiliate hoa hong gioi thieu f1 f2 referral commission' ],
        [ 'name' => __('Lượt click hoàn tiền'), 'category' => __('Nhật ký & Logs'), 'url' => route("admin.logs.cashback_clicks"), 'icon' => 'mouse-pointer-click', 'keywords' => 'luot click hoan tien click logs cashback click trans id doi soat' ],
        [ 'name' => __('Nhật ký Short Link'), 'category' => __('Nhật ký & Logs'), 'url' => route("admin.logs.short_links"), 'icon' => 'link', 'keywords' => 'nhat ky short link lien ket rut gon link rut gon clicks short links' ],
        [ 'name' => __('Hàng đợi Email'), 'category' => __('Nhật ký & Logs'), 'url' => route("admin.logs.email_queue"), 'icon' => 'mail', 'keywords' => 'hang doi email email queue logs gui thu smtp' ],
        [ 'name' => __('Hàng đợi Telegram'), 'category' => __('Nhật ký & Logs'), 'url' => route("admin.logs.telegram_queue"), 'icon' => 'send', 'keywords' => 'hang doi telegram telegram queue logs gui tin nhan bot' ],
        [ 'name' => __('Nhật ký thông báo'), 'category' => __('Nhật ký & Logs'), 'url' => route("admin.logs.notifications"), 'icon' => 'bell', 'keywords' => 'nhat ky thong bao logs notification gui tin nhan nguoi dung read' ],
        [ 'name' => __('Sản phẩm đã lưu'), 'category' => __('Nhật ký & Logs'), 'url' => route("admin.logs.saved_products"), 'icon' => 'heart', 'keywords' => 'san pham da luu saved products wishlist san pham quan tam quan tri nguoi dung' ],
        [ 'name' => __('Nhật ký gọi API'), 'category' => __('Nhật ký & Logs'), 'url' => route("admin.logs.api"), 'icon' => 'webhook', 'keywords' => 'nhat ky goi api api logs request endpoint bot openapi token don dep xoa log clear' ],
        [ 'name' => __('Nhật ký hệ thống'), 'category' => __('Nhật ký & Logs'), 'url' => route("admin.logs.system"), 'icon' => 'terminal', 'keywords' => 'nhat ky he thong loi system logs bug errors laravel log' ],

        // 3. Nhóm Blog CMS
        [ 'name' => __('Bài viết Blog'), 'category' => __('Blog CMS'), 'url' => route("admin.blog.posts.index"), 'icon' => 'file-text', 'keywords' => 'bai viet blog posts tin tuc viet bai' ],
        [ 'name' => __('Chuyên mục Blog'), 'category' => __('Blog CMS'), 'url' => route("admin.blog.categories.index"), 'icon' => 'folder', 'keywords' => 'chuyen muc blog categories danh muc tin tuc' ],
        [ 'name' => __('Thẻ tag Blog'), 'category' => __('Blog CMS'), 'url' => route("admin.blog.tags.index"), 'icon' => 'tag', 'keywords' => 'the tag blog post tags tu khoa' ],
        [ 'name' => __('Bình luận Blog'), 'category' => __('Blog CMS'), 'url' => route("admin.blog.comments.index"), 'icon' => 'message-square', 'keywords' => 'binh luan blog comments danh gia tuong tac' ],
        [ 'name' => __('Cấu hình Blog'), 'category' => __('Blog CMS'), 'url' => route("admin.blog.settings.index"), 'icon' => 'settings-2', 'keywords' => 'cau hinh blog settings seo blog layout blog' ],

        // 4. Nhóm Cấu hình chi tiết (Setting Tabs)
        [ 'name' => __('Cấu hình chung (Website)'), 'category' => __('Cài đặt hệ thống'), 'url' => route("admin.settings.index", ["tab" => "general"]), 'icon' => 'globe', 'keywords' => 'cau hinh chung cai dat he thong site name ten web logo favicon facebook link lien ket social bao tri maintenance mode' ],
        [ 'name' => __('Kiểu thông báo Toast'), 'category' => __('Cài đặt hệ thống'), 'url' => route("admin.settings.index", ["tab" => "general"]) . '#toast_style', 'icon' => 'bell-ring', 'keywords' => 'kieu thong bao toast notification hien thi thong bao pop up izitoast notyf notiflix toastr sweetalert alpine position duration' ],
        [ 'name' => __('Cấu hình hoàn tiền Shopee'), 'category' => __('Cài đặt hệ thống'), 'url' => route("admin.settings.index", ["tab" => "shopee"]), 'icon' => 'shopping-bag', 'keywords' => 'cau hinh hoan tien shopee cashback rate affiliate ty le hoan tien api key appid' ],
        [ 'name' => __('Cấu hình hoàn tiền TikTok Shop'), 'category' => __('Cài đặt hệ thống'), 'url' => route("admin.settings.index", ["tab" => "tiktok"]), 'icon' => 'shopping-cart', 'keywords' => 'cau hinh hoan tien tiktok shop cashback rate affiliate ty le hoan tien api key appid' ],
        [ 'name' => __('Cấu hình hoàn tiền Lazada'), 'category' => __('Cài đặt hệ thống'), 'url' => route("admin.settings.index", ["tab" => "lazada"]), 'icon' => 'shopping-bag', 'keywords' => 'cau hinh hoan tien lazada cashback rate affiliate ty le hoan tien user token api url subid api san pham lazada thong tin san pham gia hoa hong apilazada product key x-api-key' ],
        [ 'name' => __('Cấu hình mã giảm giá'), 'category' => __('Cài đặt hệ thống'), 'url' => route("admin.settings.index", ["tab" => "coupons"]), 'icon' => 'ticket', 'keywords' => 'cai dat ma giam gia coupons shopee lazada tiktok vouchers api key' ],
        [ 'name' => __('Cấu hình phím tắt (iOS Shortcuts)'), 'category' => __('Cài đặt hệ thống'), 'url' => route("admin.settings.index", ["tab" => "shortcuts"]), 'icon' => 'smartphone', 'keywords' => 'cau hinh phim tat iphone ios shortcuts url status guide' ],
        [ 'name' => __('Hoa hồng giới thiệu (MLM)'), 'category' => __('Cài đặt hệ thống'), 'url' => route("admin.settings.index", ["tab" => "mlm"]), 'icon' => 'users-2', 'keywords' => 'cau hinh hoa hong mlm f1 f2 referral rate chiet khau gioi thieu tuyen tren' ],
        [ 'name' => __('Cấu hình bảng xếp hạng'), 'category' => __('Cài đặt hệ thống'), 'url' => route("admin.settings.index", ["tab" => "ranking"]), 'icon' => 'trophy', 'keywords' => 'cai dat bang xep hang bxh top don hang top tien hoan top diem danh top gioi thieu' ],
        [ 'name' => __('Cấu hình điểm danh'), 'category' => __('Cài đặt hệ thống'), 'url' => route("admin.settings.index", ["tab" => "reward"]), 'icon' => 'gift', 'keywords' => 'cau hinh diem danh checkin coin hang ngay streak chuoi diem danh' ],
        [ 'name' => __('Cấu hình rút tiền & Ngân hàng'), 'category' => __('Cài đặt hệ thống'), 'url' => route("admin.settings.index", ["tab" => "withdraw"]), 'icon' => 'banknote', 'keywords' => 'cau hinh rut tien min withdraw rut toi thieu ngan hang rut' ],
        [ 'name' => __('Cấu hình Rút gọn link'), 'category' => __('Cài đặt hệ thống'), 'url' => route("admin.settings.index", ["tab" => "shortlink"]), 'icon' => 'link', 'keywords' => 'rut gon link shortlink bitly tinyurl shortener' ],
        [ 'name' => __('Cấu hình kết nối API & Key'), 'category' => __('Cài đặt hệ thống'), 'url' => route("admin.settings.index", ["tab" => "connections"]), 'icon' => 'plug', 'keywords' => 'cau hinh ket noi connections shoppee api access token ai openai deepseek claude gemini' ],
        [ 'name' => __('Cấu hình bảo mật (Turnstile/Bot)'), 'category' => __('Cài đặt hệ thống'), 'url' => route("admin.settings.index", ["tab" => "security"]), 'icon' => 'shield', 'keywords' => 'cau hinh bao mat turnstile cloudflare recaptcha spam chong bot spam gioi han ip dang ky tai khoan ip_register_limit chan vpn proxy ip ao vpncheck proxycheck' ],
        [ 'name' => __('Mẫu Email Template (SMTP)'), 'category' => __('Cài đặt hệ thống'), 'url' => route("admin.settings.index", ["tab" => "email_templates"]), 'icon' => 'mail', 'keywords' => 'email template mau email smtp gui thu welcome quen mat khau otp' ],
        [ 'name' => __('Mẫu Telegram Template (Notification)'), 'category' => __('Cài đặt hệ thống'), 'url' => route("admin.settings.index", ["tab" => "telegram_templates"]), 'icon' => 'send', 'keywords' => 'telegram template bot token chat id thong bao telegram' ],
        [ 'name' => __('Quản lý Cron Job tự động'), 'category' => __('Cài đặt hệ thống'), 'url' => route("admin.settings.index", ["tab" => "cronjobs"]), 'icon' => 'clock', 'keywords' => 'cron job lich chay tu dong tu dong duyet dong bo' ],
        [ 'name' => __('Cấu hình Open API'), 'category' => __('Cài đặt hệ thống'), 'url' => route("admin.settings.index", ["tab" => "open_api"]), 'icon' => 'key', 'keywords' => 'cai dat open api token bat tat endpoints web service integration' ],
        [ 'name' => __('Cấu hình khác & Mã nhúng'), 'category' => __('Cài đặt hệ thống'), 'url' => route("admin.settings.index", ["tab" => "other"]), 'icon' => 'settings-2', 'keywords' => 'cau hinh khac custom css js header footer debug dang ky tai khoan xac minh email truong dang ky' ],
        [ 'name' => __('Tài liệu API (Bật/Tắt cho thành viên)'), 'category' => __('Cài đặt hệ thống'), 'url' => route("admin.settings.index", ["tab" => "other"]) . '#api_docs_enabled', 'icon' => 'book-open', 'keywords' => 'tai lieu api documentation docs bat tat hien thi thanh vien profile api key tao link hoan tien danh sach don hang' ],
        [ 'name' => __('Hiển thị đơn chưa ghi nhận'), 'category' => __('Cài đặt hệ thống'), 'url' => route("admin.settings.index", ["tab" => "other"]) . '#cashback_show_pending_clicks', 'icon' => 'clock', 'keywords' => 'hien thi don chua ghi nhan cho san ghi nhan link da tao cashback clicks lich su hoan tien pending click uoc tinh doi soat' ]
    ];
@endphp
{{-- 
    Component: Spotlight Search (Tìm kiếm nhanh chức năng & cấu hình)
    Vai trò: Cho phép quản trị viên tìm kiếm và truy cập nhanh bất kỳ trang chức năng hoặc mục cấu hình nào trong Admin Panel.
    Kiến trúc:
        - Giao diện được dựng bằng Tailwind CSS, hỗ trợ cả Dark Mode.
        - Logic tìm kiếm, lọc và điều hướng bàn phím do AlpineJS quản lý hoàn toàn ở phía client (không gọi API/Database nên tốc độ phản hồi tức thì).
        - Hỗ trợ phím tắt Ctrl+K hoặc Cmd+K toàn cục, tự động bỏ qua nếu người dùng đang gõ trong ô input khác.
        - Hỗ trợ chuyển đổi tiếng Việt có dấu sang không dấu để tìm kiếm thông minh hơn.
--}}
<div x-data="searchSpotlight(@js($adminItems))"
     @open-search.window="openModal()"
     x-show="isOpen"
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     class="fixed inset-0 z-50 overflow-y-auto p-4 sm:p-6 md:p-20"
     role="dialog"
     aria-modal="true"
     x-cloak>
    
    <!-- Lớp phủ nền mờ phía sau (Backdrop Overlay) -->
    <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" @click="closeModal()"></div>

    <!-- Khung tìm kiếm chính (Modal Card) -->
    <div x-show="isOpen"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         class="mx-auto max-w-xl transform divide-y divide-gray-100 dark:divide-slate-800 overflow-hidden rounded-2xl bg-white dark:bg-slate-900 shadow-2xl ring-1 ring-black ring-opacity-5 transition-all">
        
        <!-- Hộp nhập từ khóa tìm kiếm -->
        <div class="relative flex items-center px-4 py-3.5">
            <i data-lucide="search" class="h-5 w-5 text-gray-400 dark:text-slate-500 shrink-0"></i>
            <input type="text"
                   x-model="searchQuery"
                   x-ref="searchInput"
                   @keydown.arrow-down.prevent="navigateDown()"
                   @keydown.arrow-up.prevent="navigateUp()"
                   @keydown.enter.prevent="selectCurrent()"
                   @keydown.escape.prevent="closeModal()"
                   class="ml-3 h-8 w-full border-0 bg-transparent text-sm text-gray-800 dark:text-slate-100 placeholder-gray-400 focus:ring-0 focus:outline-none dark:placeholder-slate-500"
                   placeholder="Tìm chức năng, cài đặt (ví dụ: hoa hồng, rút tiền...)"
                   role="combobox"
                   aria-expanded="false">
            <button @click="closeModal()" class="text-xs text-gray-400 hover:text-gray-600 dark:hover:text-slate-200 border border-gray-200 dark:border-slate-800 rounded px-1.5 py-0.5 bg-gray-50 dark:bg-slate-950/40">
                ESC
            </button>
        </div>

        <!-- Vùng hiển thị kết quả tìm kiếm -->
        <div class="max-h-96 overflow-y-auto py-2 scrollbar-none" x-ref="resultsContainer">
            <!-- Trạng thái chưa nhập từ khóa: Hiển thị các chức năng truy cập nhanh phổ biến -->
            <template x-if="searchQuery === ''">
                <div>
                    <div class="px-4 py-2 text-[10px] font-bold text-gray-400 dark:text-slate-500 uppercase tracking-widest">
                        Gợi ý truy cập nhanh
                    </div>
                    <ul class="text-sm text-gray-700 dark:text-slate-350">
                        <template x-for="(item, index) in popularItems" :key="'popular-' + index">
                            <li>
                                <a :href="item.url"
                                   @mouseenter="selectedIndex = index"
                                   @click="closeModal()"
                                   class="flex items-center gap-3 px-4 py-3 transition-colors text-xs font-semibold"
                                   :class="selectedIndex === index ? 'bg-shopee/5 text-shopee dark:bg-shopee/10' : 'hover:bg-gray-50 dark:hover:bg-slate-850/40'">
                                    <div class="flex items-center justify-center w-7 h-7 rounded-lg bg-gray-100 dark:bg-slate-800 text-gray-500 dark:text-slate-400 shrink-0"
                                         :class="selectedIndex === index ? 'text-shopee bg-shopee/10 dark:text-shopee dark:bg-shopee/20' : ''">
                                        <i :data-lucide="item.icon || 'link'" class="w-4 h-4"></i>
                                    </div>
                                    <div class="flex-grow min-w-0">
                                        <span x-text="item.name" class="block truncate"></span>
                                        <span x-text="item.category" class="block text-[10px] text-gray-400 dark:text-slate-500 font-medium"></span>
                                    </div>
                                    <i data-lucide="chevron-right" class="w-3.5 h-3.5 text-gray-300 dark:text-slate-700"></i>
                                </a>
                            </li>
                        </template>
                    </ul>
                </div>
            </template>

            <!-- Trạng thái có kết quả tìm kiếm -->
            <template x-if="searchQuery !== '' && filteredItems.length > 0">
                <ul class="text-sm text-gray-700 dark:text-slate-350">
                    <template x-for="(item, index) in filteredItems" :key="'result-' + index">
                        <li>
                            <a :href="item.url"
                               :id="'search-item-' + index"
                               @mouseenter="selectedIndex = index"
                               @click="closeModal()"
                               class="flex items-center gap-3 px-4 py-3 transition-colors text-xs font-semibold"
                               :class="selectedIndex === index ? 'bg-shopee/5 text-shopee dark:bg-shopee/10 border-l-4 border-shopee' : 'hover:bg-gray-50 dark:hover:bg-slate-850/40 pl-5'">
                                <div class="flex items-center justify-center w-7 h-7 rounded-lg bg-gray-100 dark:bg-slate-800 text-gray-500 dark:text-slate-400 shrink-0"
                                     :class="selectedIndex === index ? 'text-shopee bg-shopee/10 dark:text-shopee dark:bg-shopee/20' : ''">
                                    <i :data-lucide="item.icon || 'link'" class="w-4 h-4"></i>
                                </div>
                                <div class="flex-grow min-w-0">
                                    <span x-text="item.name" class="block truncate"></span>
                                    <span x-text="item.category" class="block text-[10px] text-gray-400 dark:text-slate-500 font-medium"></span>
                                </div>
                                <div class="flex items-center gap-1 shrink-0">
                                    <span class="text-[10px] text-gray-400 dark:text-slate-500 bg-gray-100 dark:bg-slate-800 px-1.5 py-0.5 rounded font-normal" x-text="item.category"></span>
                                    <i data-lucide="chevron-right" class="w-3.5 h-3.5 text-gray-300 dark:text-slate-700"></i>
                                </div>
                            </a>
                        </li>
                    </template>
                </ul>
            </template>

            <!-- Trạng thái không có kết quả tìm kiếm (Empty State) -->
            <template x-if="searchQuery !== '' && filteredItems.length === 0">
                <div class="px-6 py-14 text-center sm:px-14">
                    <i data-lucide="search-code" class="mx-auto h-8 w-8 text-gray-400 dark:text-slate-500"></i>
                    <p class="mt-4 text-xs font-bold text-gray-900 dark:text-slate-200">Không tìm thấy kết quả nào</p>
                    <p class="mt-2 text-xs text-gray-500">Không tìm thấy chức năng hoặc cài đặt nào khớp với từ khóa "<span class="font-semibold" x-text="searchQuery"></span>".</p>
                </div>
            </template>
        </div>

        <!-- Hướng dẫn sử dụng phím tắt dưới chân thanh tìm kiếm (Footer) -->
        <div class="flex items-center justify-between bg-gray-50 dark:bg-slate-950/40 px-4 py-2.5 text-[10px] text-gray-400 dark:text-slate-500 font-medium">
            <div class="flex items-center gap-3">
                <span class="flex items-center gap-1">
                    <kbd class="bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 px-1 rounded shadow-sm">↑↓</kbd> Di chuyển
                </span>
                <span class="flex items-center gap-1">
                    <kbd class="bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 px-1 rounded shadow-sm">Enter</kbd> Chọn
                </span>
                <span class="flex items-center gap-1">
                    <kbd class="bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 px-1 rounded shadow-sm">ESC</kbd> Đóng
                </span>
            </div>
            <div class="hidden sm:block">
                <span>Hệ thống Hoàn Tiền Shopee</span>
            </div>
        </div>
    </div>
</div>

<script src="{{ asset('js/search-spotlight-admin.js') }}?v=1.0.0"></script>
