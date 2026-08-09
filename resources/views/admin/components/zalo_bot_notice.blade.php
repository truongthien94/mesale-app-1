{{-- ===== THÔNG BÁO RA MẮT DỊCH VỤ TẠO BOT HOÀN TIỀN BẰNG NICK ZALO CÁ NHÂN =====
     Nghiệp vụ: Banner giới thiệu nhỏ gọn dành cho Quản trị viên với 2 lựa chọn ẩn thông báo:
       1. Nút "Ẩn trong 24 giờ": ghi mốc thời gian hết hạn vào khóa `..._snooze`,
          hết 24 giờ banner sẽ tự động hiển thị lại.
       2. Nút X góc phải: ẩn vĩnh viễn, không hiển thị lại nữa.
     Cả 2 trạng thái đều lưu trong localStorage theo khóa có phiên bản.
     Muốn thông báo lại toàn bộ Quản trị viên về sau chỉ cần tăng số phiên bản trong khóa này.

     Cách sử dụng: @include('admin.components.zalo_bot_notice')
     Có thể truyền biến $storageKey để mỗi vị trí hiển thị có trạng thái ẩn/hiện riêng biệt:
       @include('admin.components.zalo_bot_notice', ['storageKey' => 'notice_zalo_personal_bot_bots_v1'])
--}}
@php
    // Khóa lưu trạng thái ẩn/hiện trong localStorage (mặc định dùng cho Bảng điều khiển)
    $noticeStorageKey = $storageKey ?? 'notice_zalo_personal_bot_v1';
@endphp
<div x-data="{
        key: '{{ $noticeStorageKey }}',
        show: false,
        init() {
            // Quản trị viên đã chọn ẩn vĩnh viễn thì không hiển thị nữa
            if (localStorage.getItem(this.key) === 'hidden') return;
            // Đang trong khoảng thời gian tạm ẩn 24 giờ thì cũng bỏ qua
            const snoozeUntil = parseInt(localStorage.getItem(this.key + '_snooze') || '0', 10);
            if (snoozeUntil > Date.now()) return;
            // Đã hết hạn tạm ẩn: dọn dẹp khóa cũ và hiển thị lại thông báo
            localStorage.removeItem(this.key + '_snooze');
            this.show = true;
        },
        hideForever() {
            this.show = false;
            localStorage.setItem(this.key, 'hidden');
        },
        snooze24h() {
            this.show = false;
            localStorage.setItem(this.key + '_snooze', Date.now() + 24 * 60 * 60 * 1000);
        }
    }"
    x-show="show" x-cloak x-transition.opacity.duration.300ms
    class="relative overflow-hidden bg-gradient-to-r from-blue-50 via-sky-50 to-white dark:from-blue-950/30 dark:via-sky-950/20 dark:to-slate-900 border border-blue-100 dark:border-blue-900/40 rounded-2xl p-4 sm:p-5 shadow-sm">

    <!-- Nút đóng thông báo vĩnh viễn -->
    <button type="button" @click="hideForever()"
        title="{{ __('Không hiển thị thông báo này nữa') }}"
        class="absolute top-3 right-3 p-1.5 rounded-lg text-blue-400 hover:text-blue-600 hover:bg-blue-100/70 dark:text-slate-500 dark:hover:text-slate-300 dark:hover:bg-slate-800 transition-all active:scale-95">
        <i data-lucide="x" class="w-4 h-4"></i>
    </button>

    <div class="flex flex-col sm:flex-row items-start gap-4">
        <!-- Biểu tượng Bot -->
        <div class="p-2.5 rounded-xl bg-blue-100 dark:bg-blue-900/40 text-blue-600 dark:text-blue-400 shrink-0">
            <i data-lucide="bot" class="w-5 h-5"></i>
        </div>

        <div class="flex-grow space-y-2 pr-6 sm:pr-8">
            <!-- Nhãn MỚI + Tiêu đề thông báo -->
            <div class="flex flex-wrap items-center gap-2">
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-blue-600 text-white text-[10px] font-bold uppercase tracking-wider shadow-sm">
                    <i data-lucide="sparkles" class="w-3 h-3"></i>
                    {{ __('MỚI') }}
                </span>
                <h4 class="text-sm font-bold text-gray-900 dark:text-slate-100">
                    {{ __('Ra mắt hệ thống tạo Bot Hoàn Tiền chạy trên nick Zalo cá nhân') }}
                </h4>
            </div>

            <!-- Nội dung giới thiệu dịch vụ -->
            <p class="text-xs text-gray-600 dark:text-slate-400 leading-relaxed">
                {{ __('Không cần tài khoản Zalo OA, không cần treo máy tính 24/24. Chỉ cần kết nối nick Zalo cá nhân của bạn, Bot sẽ tự động bóc tách link Shopee, TikTok Shop, Lazada mà khách gửi tới, gắn mã Affiliate và gửi lại link hoàn tiền chỉ trong 0.5 giây. Bot còn tự trả lời tin nhắn theo từ khóa bạn cấu hình và kết nối trực tiếp với hệ thống CashBack này để đồng bộ đơn hàng.') }}
            </p>

            <!-- Các điểm nổi bật của dịch vụ -->
            <div class="flex flex-wrap items-center gap-1.5 pt-0.5">
                @foreach ([
                    __('Chuyển link tự động 0.5 giây'),
                    __('Trả lời tin nhắn theo từ khóa'),
                    __('Chạy 24/7 trên Cloud'),
                    __('Proxy IP Việt Nam riêng'),
                    __('Mã hóa AES-256 an toàn'),
                ] as $highlight)
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-lg bg-white/80 dark:bg-slate-800/70 border border-blue-100 dark:border-slate-700 text-[10px] font-semibold text-gray-600 dark:text-slate-300">
                    <i data-lucide="check" class="w-3 h-3 text-green-500"></i>
                    {{ $highlight }}
                </span>
                @endforeach
            </div>

            <!-- Liên kết tới trang dịch vụ để xem chi tiết, bảng giá và nút tạm ẩn thông báo -->
            <div class="flex flex-wrap items-center gap-2 pt-1.5">
                <a href="https://taobothoantien.com/?utm_source={{ request()->getHost() }}" target="_blank" rel="noopener"
                    class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-[11px] font-bold shadow-sm transition-all duration-200 hover:scale-[1.02] active:scale-[0.98]">
                    {{ __('Xem chi tiết') }}
                    <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                </a>
                <!-- Tạm ẩn thông báo trong 24 giờ, sau đó tự động hiển thị lại -->
                <button type="button" @click="snooze24h()"
                    title="{{ __('Tạm ẩn thông báo, sẽ hiển thị lại sau 24 giờ') }}"
                    class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-white dark:bg-slate-800 hover:bg-gray-50 dark:hover:bg-slate-700 border border-gray-200 dark:border-slate-700 text-gray-600 dark:text-slate-300 text-[11px] font-bold shadow-sm transition-all duration-200 hover:scale-[1.02] active:scale-[0.98]">
                    <i data-lucide="clock" class="w-3.5 h-3.5 text-gray-400 dark:text-slate-500"></i>
                    {{ __('Ẩn thông báo trong 24 giờ') }}
                </button>
            </div>
        </div>
    </div>
</div>
