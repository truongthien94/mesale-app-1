{{--
    Sidebar dành cho thành viên (Dashboard Sidebar)
    Cung cấp các liên kết điều hướng và tóm tắt thông tin tài khoản của người dùng.
--}}
<div class="hidden lg:block bg-white dark:bg-slate-900 rounded-2xl shadow-xl border border-gray-100 dark:border-slate-800/80 px-3 py-6 space-y-6">
    <!-- Tóm tắt thông tin tài khoản -->
    <div class="flex items-center gap-3 border-b border-gray-100 dark:border-slate-800 pb-5 px-2">
        <div class="w-12 h-12 rounded-full bg-orange-600 text-white flex items-center justify-center font-extrabold text-lg shrink-0 shadow-md">
            {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
        </div>
        <div class="truncate space-y-0.5">
            <h3 class="font-bold text-gray-900 dark:text-white truncate text-xs sm:text-sm">{{ auth()->user()->name }}</h3>
            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[9px] font-bold bg-green-50 text-green-700 border border-green-150/50 dark:bg-green-950/20 dark:text-green-400 dark:border-green-900/30">
                <span class="w-1 h-1 rounded-full bg-green-500 animate-pulse"></span>
                {{ __('Tài khoản chính') }}
            </span>
        </div>
    </div>

    <!-- Danh sách liên kết điều hướng -->
    <nav class="space-y-1">
        <a href="{{ route('dashboard') }}" class="flex items-center gap-3 px-3 py-2.5 text-xs font-semibold rounded-2xl transition-all {{ request()->routeIs('dashboard') ? 'bg-orange-50/50 text-shopee dark:bg-slate-800 dark:text-orange-400' : 'text-gray-600 dark:text-slate-400 hover:bg-gray-50 dark:hover:bg-slate-800/50 hover:text-shopee dark:hover:text-orange-400' }}">
            <i data-lucide="wallet" class="w-4 h-4 shrink-0 {{ request()->routeIs('dashboard') ? 'text-shopee dark:text-orange-400' : 'text-gray-400' }}"></i>
            {{ __('Ví của tôi') }}
        </a>
        
        <a href="{{ route('cashback.history') }}" class="flex items-center gap-3 px-3 py-2.5 text-xs font-semibold rounded-2xl transition-all {{ request()->routeIs('cashback.history') ? 'bg-orange-50/50 text-shopee dark:bg-slate-800 dark:text-orange-400' : 'text-gray-600 dark:text-slate-400 hover:bg-gray-50 dark:hover:bg-slate-800/50 hover:text-shopee dark:hover:text-orange-400' }}">
            <i data-lucide="history" class="w-4 h-4 shrink-0 {{ request()->routeIs('cashback.history') ? 'text-shopee dark:text-orange-400' : 'text-gray-400' }}"></i>
            {{ __('Lịch sử hoàn tiền') }}
        </a>

        <a href="{{ route('saved-products') }}" class="flex items-center gap-3 px-3 py-2.5 text-xs font-semibold rounded-2xl transition-all {{ request()->routeIs('saved-products') ? 'bg-orange-50/50 text-shopee dark:bg-slate-800 dark:text-orange-400' : 'text-gray-600 dark:text-slate-400 hover:bg-gray-50 dark:hover:bg-slate-800/50 hover:text-shopee dark:hover:text-orange-400' }}">
            <i data-lucide="bookmark" class="w-4 h-4 shrink-0 {{ request()->routeIs('saved-products') ? 'text-shopee dark:text-orange-400' : 'text-gray-400' }}"></i>
            {{ __('Sản phẩm đã lưu') }}
        </a>

        {{-- Business Rule: Hiển thị liên kết đến trang Hướng Dẫn Bot Hoàn Tiền nếu có ít nhất 1 bot đang bật (Zalo/Telegram) --}}
        @if(\App\Models\BotConfig::where('is_enabled', true)->exists())
        <a href="{{ route('bot.guide') }}" class="flex items-center gap-3 px-3 py-2.5 text-xs font-semibold rounded-2xl transition-all {{ request()->routeIs('bot.guide') ? 'bg-orange-50/50 text-shopee dark:bg-slate-800 dark:text-orange-400' : 'text-gray-600 dark:text-slate-400 hover:bg-gray-50 dark:hover:bg-slate-800/50 hover:text-shopee dark:hover:text-orange-400' }}">
            <i data-lucide="bot" class="w-4 h-4 shrink-0 {{ request()->routeIs('bot.guide') ? 'text-shopee dark:text-orange-400' : 'text-gray-400' }}"></i>
            {{ __('Bot hoàn tiền') }}
        </a>
        @endif

        @if(\App\Models\Setting::getVal('daily_checkin_enabled', '1') === '1' && \App\Models\Setting::getVal('checkin_device_allow', 'both') !== 'mobile')
        <a href="{{ route('checkin') }}" class="flex items-center gap-3 px-3 py-2.5 text-xs font-semibold rounded-2xl transition-all {{ request()->routeIs('checkin') ? 'bg-orange-50/50 text-shopee dark:bg-slate-800 dark:text-orange-400' : 'text-gray-600 dark:text-slate-400 hover:bg-gray-50 dark:hover:bg-slate-800/50 hover:text-shopee dark:hover:text-orange-400' }}">
            <i data-lucide="calendar-check" class="w-4 h-4 shrink-0 {{ request()->routeIs('checkin') ? 'text-shopee dark:text-orange-400' : 'text-gray-400' }}"></i>
            {{ __('Điểm danh nhận xu') }}
        </a>
        @endif

        @if(\App\Models\Setting::getVal('referral_enabled', '1') === '1')
        <a href="{{ route('referrals') }}" class="flex items-center gap-3 px-3 py-2.5 text-xs font-semibold rounded-2xl transition-all {{ request()->routeIs('referrals') ? 'bg-orange-50/50 text-shopee dark:bg-slate-800 dark:text-orange-400' : 'text-gray-600 dark:text-slate-400 hover:bg-gray-50 dark:hover:bg-slate-800/50 hover:text-shopee dark:hover:text-orange-400' }}">
            <i data-lucide="link-2" class="w-4 h-4 shrink-0 {{ request()->routeIs('referrals') ? 'text-shopee dark:text-orange-400' : 'text-gray-400' }}"></i>
            {{ __('Tiếp thị liên kết') }}
        </a>
        @endif

        @if(\App\Models\Setting::getVal('withdrawal_enabled', '1') === '1')
        <a href="{{ route('withdraw') }}" class="flex items-center gap-3 px-3 py-2.5 text-xs font-semibold rounded-2xl transition-all {{ request()->routeIs('withdraw') ? 'bg-orange-50/50 text-shopee dark:bg-slate-800 dark:text-orange-400' : 'text-gray-600 dark:text-slate-400 hover:bg-gray-50 dark:hover:bg-slate-800/50 hover:text-shopee dark:hover:text-orange-400' }}">
            <i data-lucide="banknote" class="w-4 h-4 shrink-0 {{ request()->routeIs('withdraw') ? 'text-shopee dark:text-orange-400' : 'text-gray-400' }}"></i>
            {{ __('Yêu cầu rút tiền') }}
        </a>
        @endif

        @if(\App\Models\Setting::getVal('gift_redemption_enabled', '0') === '1')
        <a href="{{ route('gifts.index') }}" class="flex items-center gap-3 px-3 py-2.5 text-xs font-semibold rounded-2xl transition-all {{ request()->routeIs('gifts.index') ? 'bg-orange-50/50 text-shopee dark:bg-slate-800 dark:text-orange-400' : 'text-gray-600 dark:text-slate-400 hover:bg-gray-50 dark:hover:bg-slate-800/50 hover:text-shopee dark:hover:text-orange-400' }}">
            <i data-lucide="gift" class="w-4 h-4 shrink-0 {{ request()->routeIs('gifts.index') ? 'text-shopee dark:text-orange-400' : 'text-gray-400' }}"></i>
            {{ __('Đổi quà tặng') }}
        </a>
        @endif

        @if(\App\Models\Setting::getVal('gift_code_enabled', '1') === '1')
        <a href="{{ route('giftcode.index') }}" class="flex items-center gap-3 px-3 py-2.5 text-xs font-semibold rounded-2xl transition-all {{ request()->routeIs('giftcode.index') ? 'bg-orange-50/50 text-shopee dark:bg-slate-800 dark:text-orange-400' : 'text-gray-600 dark:text-slate-400 hover:bg-gray-50 dark:hover:bg-slate-800/50 hover:text-shopee dark:hover:text-orange-400' }}">
            <i data-lucide="ticket" class="w-4 h-4 shrink-0 {{ request()->routeIs('giftcode.index') ? 'text-shopee dark:text-orange-400' : 'text-gray-400' }}"></i>
            {{ __('Nhập Giftcode') }}
        </a>
        @endif

        @if(\App\Models\Setting::getVal('tasks_enabled', '0') === '1')
        @php $pendingTasksUser = \App\Models\UserTask::where('user_id', auth()->id())->where('status', 'completed')->count(); @endphp
        <a href="{{ route('tasks.index') }}" class="flex items-center justify-between px-3 py-2.5 text-xs font-semibold rounded-2xl transition-all {{ request()->routeIs('tasks.index') ? 'bg-orange-50/50 text-shopee dark:bg-slate-800 dark:text-orange-400' : 'text-gray-600 dark:text-slate-400 hover:bg-gray-50 dark:hover:bg-slate-800/50 hover:text-shopee dark:hover:text-orange-400' }}">
            <div class="flex items-center gap-3">
                <i data-lucide="trophy" class="w-4 h-4 shrink-0 {{ request()->routeIs('tasks.index') ? 'text-shopee dark:text-orange-400' : 'text-gray-400' }}"></i>
                <span>{{ \App\Models\Setting::getVal('tasks_title') ?: __('Nhiệm vụ nhận thưởng') }}</span>
            </div>
            @if($pendingTasksUser > 0)
                <span class="px-2 py-0.5 text-[9px] font-bold text-white bg-amber-500 rounded-full animate-pulse">{{ $pendingTasksUser }}</span>
            @endif
        </a>
        @endif

        <a href="{{ route('profile') }}" class="flex items-center gap-3 px-3 py-2.5 text-xs font-semibold rounded-2xl transition-all {{ request()->routeIs('profile') ? 'bg-orange-50 text-shopee font-bold dark:bg-slate-800 dark:text-orange-400' : 'text-gray-600 dark:text-slate-400 hover:bg-gray-50 dark:hover:bg-slate-800/50 hover:text-shopee dark:hover:text-orange-400' }}">
            <i data-lucide="user-cog" class="w-4 h-4 shrink-0 {{ request()->routeIs('profile') ? 'text-shopee dark:text-orange-400' : 'text-gray-400' }}"></i>
            {{ __('Thiết lập tài khoản') }}
        </a>

        <a href="{{ route('notifications') }}" class="flex items-center justify-between px-3 py-2.5 text-xs font-semibold rounded-2xl transition-all {{ request()->routeIs('notifications') ? 'bg-orange-50/50 text-shopee dark:bg-slate-800 dark:text-orange-400' : 'text-gray-600 dark:text-slate-400 hover:bg-gray-50 dark:hover:bg-slate-800/50 hover:text-shopee dark:hover:text-orange-400' }}">
            <div class="flex items-center gap-3">
                <i data-lucide="bell" class="w-4 h-4 shrink-0 {{ request()->routeIs('notifications') ? 'text-shopee dark:text-orange-400' : 'text-gray-400' }}"></i>
                <span>{{ __('Thông báo') }}</span>
            </div>
            @php
                $sidebarUnreadCount = \App\Models\Notification::where('user_id', auth()->id())->where('is_read', false)->count();
            @endphp
            @if($sidebarUnreadCount > 0)
                <span class="px-2 py-0.5 text-[9px] font-bold text-white bg-red-500 rounded-full">
                    {{ $sidebarUnreadCount }}
                </span>
            @endif
        </a>

        <a href="{{ route('balance.logs') }}" class="flex items-center gap-3 px-3 py-2.5 text-xs font-semibold rounded-2xl transition-all {{ request()->routeIs('balance.logs') ? 'bg-orange-50/50 text-shopee dark:bg-slate-800 dark:text-orange-400' : 'text-gray-600 dark:text-slate-400 hover:bg-gray-50 dark:hover:bg-slate-800/50 hover:text-shopee dark:hover:text-orange-400' }}">
            <i data-lucide="arrow-left-right" class="w-4 h-4 shrink-0 {{ request()->routeIs('balance.logs') ? 'text-shopee dark:text-orange-400' : 'text-gray-400' }}"></i>
            {{ __('Biến động số dư') }}
        </a>

        <a href="{{ route('activity.logs') }}" class="flex items-center gap-3 px-3 py-2.5 text-xs font-semibold rounded-2xl transition-all {{ request()->routeIs('activity.logs') ? 'bg-orange-50/50 text-shopee dark:bg-slate-800 dark:text-orange-400' : 'text-gray-600 dark:text-slate-400 hover:bg-gray-50 dark:hover:bg-slate-800/50 hover:text-shopee dark:hover:text-orange-400' }}">
            <i data-lucide="history" class="w-4 h-4 shrink-0 {{ request()->routeIs('activity.logs') ? 'text-shopee dark:text-orange-400' : 'text-gray-400' }}"></i>
            {{ __('Nhật ký hoạt động') }}
        </a>
    </nav>

    @if(\App\Models\Setting::getVal('referral_enabled', '1') === '1')
    <!-- Banner mời bạn bè với ảnh hộp quà 3D -->
    <div class="relative bg-orange-50/50 dark:bg-slate-900/40 rounded-2xl p-4 border border-orange-100/50 dark:border-slate-800/80 overflow-hidden space-y-3 mx-1">
        <div class="space-y-1 relative z-10">
            <h4 class="text-xs font-bold text-gray-900 dark:text-white leading-snug">Mời bạn bè<br>nhận xu thưởng hấp dẫn!</h4>
            <p class="text-[9px] text-gray-400 dark:text-slate-450 leading-relaxed max-w-[100px]">
                Chia sẻ link giới thiệu và nhận đến 15% hoa hồng.
            </p>
        </div>
        <a href="{{ route('referrals') }}" class="relative z-10 inline-flex items-center gap-1 px-3 py-1.5 bg-white dark:bg-slate-800 hover:bg-orange-50 dark:hover:bg-slate-700 text-orange-600 dark:text-orange-400 text-[9px] font-bold rounded-xl border border-orange-100/50 dark:border-slate-700 transition-all shadow-sm">
            <span>Xem ngay</span>
            <i data-lucide="arrow-right" class="w-3 h-3"></i>
        </a>
        <img src="{{ asset('uploads/gift_coins_3d.png') }}" class="absolute right-[-10px] bottom-[-5px] w-24 h-24 object-contain pointer-events-none drop-shadow-md z-0" alt="Referral Gift">
    </div>
    @endif

    <!-- Đăng xuất -->
    <div class="border-t border-gray-100 dark:border-slate-800 pt-4 px-1">
        <form action="{{ route('logout') }}" method="POST">
            @csrf
            <button type="submit" class="w-full flex items-center gap-3 px-3 py-2.5 text-xs font-semibold text-red-650 hover:bg-red-50/50 dark:hover:bg-red-950/20 rounded-2xl transition-all">
                <i data-lucide="log-out" class="w-4 h-4 shrink-0 text-red-500"></i>
                {{ __('Đăng xuất') }}
            </button>
        </form>
    </div>
</div>
