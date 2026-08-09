{{--
    Thông báo nhỏ mời thành viên đăng ký bằng Số điện thoại bổ sung địa chỉ Email.
    Quy tắc nghiệp vụ: KHÔNG bắt buộc, thành viên vẫn dùng được toàn bộ tính năng khi chưa cập nhật email.
    Người dùng có thể tạm ẩn thông báo, hệ thống sẽ nhắc lại sau 3 ngày (lưu mốc thời gian trong localStorage).
    Được nhúng trực tiếp phía trên phần nội dung chính (trên tiêu đề/Danh sách đơn hàng) của các trang Dashboard.
--}}
@auth
@if(empty(auth()->user()->email) && !request()->routeIs('profile'))
<div x-data="{
        show: false,
        init() {
            // Chỉ hiển thị lại khi đã quá 3 ngày kể từ lần thành viên bấm tạm ẩn thông báo
            const snoozedUntil = parseInt(localStorage.getItem('email_notice_snoozed_until') || '0', 10);
            this.show = !snoozedUntil || Date.now() > snoozedUntil;
        },
        snooze() {
            localStorage.setItem('email_notice_snoozed_until', (Date.now() + 3 * 24 * 60 * 60 * 1000).toString());
            this.show = false;
        }
     }"
     x-show="show"
     x-transition
     x-cloak
     class="w-full">
    <div class="flex items-start gap-3 p-3.5 sm:p-4 rounded-2xl bg-amber-50 border border-amber-100/90 text-amber-800 dark:bg-amber-950/25 dark:border-amber-900/40 dark:text-amber-300 shadow-xs relative">
        <span class="w-8 h-8 rounded-xl bg-amber-100 text-amber-600 dark:bg-amber-900/40 dark:text-amber-400 flex items-center justify-center shrink-0">
            <i data-lucide="shield-alert" class="w-4.5 h-4.5"></i>
        </span>
        <div class="flex-1 min-w-0 pr-6 sm:pr-0">
            <p class="text-xs font-bold leading-tight">{{ __('Bảo vệ tài khoản của bạn tốt hơn') }}</p>
            <p class="text-[11px] leading-relaxed mt-1 text-amber-700 dark:text-amber-400/90">
                {{ __('Tài khoản của bạn đang đăng ký bằng số điện thoại và chưa có email. Hãy bổ sung email để có thể khôi phục mật khẩu và nhận thông báo quan trọng. Đây là tuỳ chọn, không bắt buộc.') }}
            </p>
            <div class="flex flex-wrap items-center gap-2 mt-2.5">
                <a href="{{ route('profile') }}"
                   class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-[11px] font-bold text-white bg-shopee hover:bg-shopee-dark transition-all shadow-xs shadow-shopee/20">
                    <i data-lucide="mail-plus" class="w-3.5 h-3.5"></i>
                    {{ __('Cập nhật email ngay') }}
                </a>
                <button type="button" @click="snooze()"
                        class="inline-flex items-center px-3 py-1.5 rounded-xl text-[11px] font-bold text-amber-700 dark:text-amber-400 hover:bg-amber-100/70 dark:hover:bg-amber-900/30 transition-all cursor-pointer">
                    {{ __('Để sau') }}
                </button>
            </div>
        </div>
        <button type="button" @click="snooze()"
                aria-label="{{ __('Đóng thông báo') }}"
                class="absolute top-3.5 right-3.5 shrink-0 text-amber-400 hover:text-amber-600 dark:hover:text-amber-300 transition-colors cursor-pointer">
            <i data-lucide="x" class="w-4 h-4"></i>
        </button>
    </div>
</div>
@endif
@endauth
