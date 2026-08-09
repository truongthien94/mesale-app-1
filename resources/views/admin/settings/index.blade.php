@extends('layouts.admin')

@section('title', __('Cài đặt') . ' - ' . $siteName)

@section('content')
{{--
    View: Cấu hình hệ thống (Admin)
    Vai trò: Trang quản trị cấu hình hệ thống, quản lý các thiết lập chung, hoàn tiền Shopee, hoa hồng MLM, thưởng điểm danh, rút tiền, rút gọn link, kết nối API, bảo mật Turnstile, và các mẫu Email/Cron Job.
    Kiến trúc: Sử dụng AlpineJS để điều khiển ẩn hiện tab động và đồng bộ hash URL. Các tab được chia nhỏ thành các file view con trong thư mục partials/ để dễ bảo trì.
--}}
<div class="space-y-6"
    x-data="{ 
         tab: '{{ $activeTab }}', 
         showPreviewModal: false,
         showSendTestModal: false,
         showGuideModal: false,
         guideType: '',
         testEmailTarget: '',
         testTemplateKey: '',
         isSendingTest: false,
         siteLogo: '{{ $settings['site_logo'] ?? '' }}',
         siteLogoDark: '{{ $settings['site_logo_dark'] ?? '' }}',
         siteFavicon: '{{ $settings['site_favicon'] ?? '' }}',
         siteOgImage: '{{ $settings['site_og_image'] ?? '' }}',
         init() {
             // Đồng bộ tab từ PHP truyền qua
         },
         sendTestEmail(templateKey) {
             this.testTemplateKey = templateKey;
             this.showSendTestModal = true;
         },
         async executeSendTestEmail() {
             if (!this.testEmailTarget) {
                 window.dispatchEvent(new CustomEvent('toast', { detail: { text: '{{ __('Vui lòng nhập địa chỉ email nhận thử.') }}', type: 'warning' } }));
                 return;
             }
             
             const editor = (typeof tinymce !== 'undefined') ? tinymce.get('email_content_' + this.testTemplateKey) : null;
             if (!editor) return;

             const subject = document.getElementById('email_subject_' + this.testTemplateKey).value;
             const content = editor.getContent();
             
             this.isSendingTest = true;
             
             try {
                 const response = await axios.post('{{ route('admin.settings.test_email') }}', {
                     template_key: this.testTemplateKey,
                     subject: subject,
                     content: content,
                     test_email: this.testEmailTarget
                 });
                 
                 window.dispatchEvent(new CustomEvent('toast', { detail: { text: response.data.message || '{{ __('Gửi email thử nghiệm thành công!') }}', type: 'success' } }));
                 this.showSendTestModal = false;
             } catch (error) {
                 const msg = error.response?.data?.message || 'Có lỗi xảy ra trong quá trình gửi thử email.';
                 window.dispatchEvent(new CustomEvent('toast', { detail: { text: msg, type: 'error' } }));
             } finally {
                 this.isSendingTest = false;
             }
         }
     }">
    <!-- Tiêu đề -->
    <div>
        <h1 class="text-2xl font-bold text-gray-900">{{ __('Cài đặt website') }}</h1>
        <p class="text-sm text-gray-500">{{ __('Cấu hình tham số vận hành, tỷ lệ cashback shopee, hoa hồng giới thiệu MLM và thưởng điểm danh') }}</p>
    </div>

    <!-- Cấu hình các Tabs điều khiển -->
    <div class="bg-white rounded-3xl shadow-sm border border-gray-200 overflow-hidden grid grid-cols-1 md:grid-cols-5">

        <!-- Tab Headers -->
        <div class="flex md:flex-col overflow-x-auto md:overflow-x-visible whitespace-nowrap md:whitespace-normal border-b md:border-b-0 md:border-r border-gray-200 text-xs font-semibold bg-gray-50/50 scrollbar-none">
            <a href="{{ route('admin.settings.index', ['tab' => 'general']) }}"
                class="px-6 py-4 md:py-5 md:px-6 transition-all focus:outline-none flex items-center gap-1.5 shrink-0 md:w-full text-left justify-start {{ $activeTab === 'general' ? 'border-b-2 md:border-b-0 md:border-r-2 border-shopee text-shopee bg-white font-bold' : 'text-gray-500 hover:text-gray-700 hover:bg-white/40' }}">
                <i data-lucide="globe" class="w-4 h-4 shrink-0"></i> <span>{{ __('Cấu hình chung') }}</span>
            </a>
            <a href="{{ route('admin.settings.index', ['tab' => 'shopee']) }}"
                class="px-6 py-4 md:py-5 md:px-6 transition-all focus:outline-none flex items-center gap-1.5 shrink-0 md:w-full text-left justify-start {{ $activeTab === 'shopee' ? 'border-b-2 md:border-b-0 md:border-r-2 border-shopee text-shopee bg-white font-bold' : 'text-gray-500 hover:text-gray-700 hover:bg-white/40' }}">
                <i data-lucide="shopping-bag" class="w-4 h-4 shrink-0"></i> <span>{{ __('Cấu hình hoàn tiền Shopee') }}</span>
            </a>
            <a href="{{ route('admin.settings.index', ['tab' => 'tiktok']) }}"
                class="px-6 py-4 md:py-5 md:px-6 transition-all focus:outline-none flex items-center gap-1.5 shrink-0 md:w-full text-left justify-start {{ $activeTab === 'tiktok' ? 'border-b-2 md:border-b-0 md:border-r-2 border-shopee text-shopee bg-white font-bold' : 'text-gray-500 hover:text-gray-700 hover:bg-white/40' }}">
                <i data-lucide="shopping-cart" class="w-4 h-4 shrink-0"></i> <span>{{ __('Cấu hình hoàn tiền TikTok Shop') }}</span>
            </a>
            <a href="{{ route('admin.settings.index', ['tab' => 'lazada']) }}"
                class="px-6 py-4 md:py-5 md:px-6 transition-all focus:outline-none flex items-center gap-1.5 shrink-0 md:w-full text-left justify-start {{ $activeTab === 'lazada' ? 'border-b-2 md:border-b-0 md:border-r-2 border-shopee text-shopee bg-white font-bold' : 'text-gray-500 hover:text-gray-700 hover:bg-white/40' }}">
                <i data-lucide="shopping-bag" class="w-4 h-4 shrink-0"></i> <span>{{ __('Cấu hình hoàn tiền Lazada') }}</span>
            </a>
            <a href="{{ route('admin.settings.index', ['tab' => 'coupons']) }}"
                class="px-6 py-4 md:py-5 md:px-6 transition-all focus:outline-none flex items-center gap-1.5 shrink-0 md:w-full text-left justify-start {{ $activeTab === 'coupons' ? 'border-b-2 md:border-b-0 md:border-r-2 border-shopee text-shopee bg-white font-bold' : 'text-gray-500 hover:text-gray-700 hover:bg-white/40' }}">
                <i data-lucide="ticket" class="w-4 h-4 shrink-0"></i> <span>{{ __('Cấu hình mã giảm giá') }}</span>
            </a>
            <!-- Cấu hình phím tắt (iOS Shortcuts) cho phép thành viên chuyển đổi link Shopee trực tiếp trên điện thoại -->
            <a href="{{ route('admin.settings.index', ['tab' => 'shortcuts']) }}"
                class="px-6 py-4 md:py-5 md:px-6 transition-all focus:outline-none flex items-center gap-1.5 shrink-0 md:w-full text-left justify-start {{ $activeTab === 'shortcuts' ? 'border-b-2 md:border-b-0 md:border-r-2 border-shopee text-shopee bg-white font-bold' : 'text-gray-500 hover:text-gray-700 hover:bg-white/40' }}">
                <i data-lucide="smartphone" class="w-4 h-4 shrink-0"></i> <span>{{ __('Cấu hình phím tắt') }}</span>
            </a>
            <a href="{{ route('admin.settings.index', ['tab' => 'mlm']) }}"
                class="px-6 py-4 md:py-5 md:px-6 transition-all focus:outline-none flex items-center gap-1.5 shrink-0 md:w-full text-left justify-start {{ $activeTab === 'mlm' ? 'border-b-2 md:border-b-0 md:border-r-2 border-shopee text-shopee bg-white font-bold' : 'text-gray-500 hover:text-gray-700 hover:bg-white/40' }}">
                <i data-lucide="users-2" class="w-4 h-4 shrink-0"></i> <span>{{ __('Hoa hồng giới thiệu (MLM)') }}</span>
            </a>
            <a href="{{ route('admin.settings.index', ['tab' => 'ranking']) }}"
                class="px-6 py-4 md:py-5 md:px-6 transition-all focus:outline-none flex items-center gap-1.5 shrink-0 md:w-full text-left justify-start {{ $activeTab === 'ranking' ? 'border-b-2 md:border-b-0 md:border-r-2 border-shopee text-shopee bg-white font-bold' : 'text-gray-500 hover:text-gray-700 hover:bg-white/40' }}">
                <i data-lucide="trophy" class="w-4 h-4 shrink-0"></i> <span>{{ __('Cấu hình bảng xếp hạng') }}</span>
            </a>
            <a href="{{ route('admin.settings.index', ['tab' => 'reward']) }}"
                class="px-6 py-4 md:py-5 md:px-6 transition-all focus:outline-none flex items-center gap-1.5 shrink-0 md:w-full text-left justify-start {{ $activeTab === 'reward' ? 'border-b-2 md:border-b-0 md:border-r-2 border-shopee text-shopee bg-white font-bold' : 'text-gray-500 hover:text-gray-700 hover:bg-white/40' }}">
                <i data-lucide="gift" class="w-4 h-4 shrink-0"></i> <span>{{ __('Cấu hình điểm danh') }}</span>
            </a>
            <a href="{{ route('admin.settings.index', ['tab' => 'withdraw']) }}"
                class="px-6 py-4 md:py-5 md:px-6 transition-all focus:outline-none flex items-center gap-1.5 shrink-0 md:w-full text-left justify-start {{ $activeTab === 'withdraw' ? 'border-b-2 md:border-b-0 md:border-r-2 border-shopee text-shopee bg-white font-bold' : 'text-gray-500 hover:text-gray-700 hover:bg-white/40' }}">
                <i data-lucide="banknote" class="w-4 h-4 shrink-0"></i> <span>{{ __('Cấu hình rút tiền') }}</span>
            </a>
            <a href="{{ route('admin.settings.index', ['tab' => 'shortlink']) }}"
                class="px-6 py-4 md:py-5 md:px-6 transition-all focus:outline-none flex items-center gap-1.5 shrink-0 md:w-full text-left justify-start {{ $activeTab === 'shortlink' ? 'border-b-2 md:border-b-0 md:border-r-2 border-shopee text-shopee bg-white font-bold' : 'text-gray-500 hover:text-gray-700 hover:bg-white/40' }}">
                <i data-lucide="link" class="w-4 h-4 shrink-0"></i> <span>{{ __('Rút gọn link') }}</span>
            </a>
            <a href="{{ route('admin.settings.index', ['tab' => 'connections']) }}"
                class="px-6 py-4 md:py-5 md:px-6 transition-all focus:outline-none flex items-center gap-1.5 shrink-0 md:w-full text-left justify-start {{ $activeTab === 'connections' ? 'border-b-2 md:border-b-0 md:border-r-2 border-shopee text-shopee bg-white font-bold' : 'text-gray-500 hover:text-gray-700 hover:bg-white/40' }}">
                <i data-lucide="plug" class="w-4 h-4 shrink-0"></i> <span>{{ __('Cấu hình kết nối') }}</span>
            </a>
            <a href="{{ route('admin.settings.index', ['tab' => 'security']) }}"
                class="px-6 py-4 md:py-5 md:px-6 transition-all focus:outline-none flex items-center gap-1.5 shrink-0 md:w-full text-left justify-start {{ $activeTab === 'security' ? 'border-b-2 md:border-b-0 md:border-r-2 border-shopee text-shopee bg-white font-bold' : 'text-gray-500 hover:text-gray-700 hover:bg-white/40' }}">
                <i data-lucide="shield" class="w-4 h-4 shrink-0"></i> <span>{{ __('Cấu hình bảo mật') }}</span>
            </a>
            <a href="{{ route('admin.settings.index', ['tab' => 'email_templates']) }}"
                class="px-6 py-4 md:py-5 md:px-6 transition-all focus:outline-none flex items-center gap-1.5 shrink-0 md:w-full text-left justify-start {{ $activeTab === 'email_templates' ? 'border-b-2 md:border-b-0 md:border-r-2 border-shopee text-shopee bg-white font-bold' : 'text-gray-500 hover:text-gray-700 hover:bg-white/40' }}">
                <i data-lucide="mail" class="w-4 h-4 shrink-0"></i> <span>{{ __('Email Template') }}</span>
            </a>
            <a href="{{ route('admin.settings.index', ['tab' => 'telegram_templates']) }}"
                class="px-6 py-4 md:py-5 md:px-6 transition-all focus:outline-none flex items-center gap-1.5 shrink-0 md:w-full text-left justify-start {{ $activeTab === 'telegram_templates' ? 'border-b-2 md:border-b-0 md:border-r-2 border-shopee text-shopee bg-white font-bold' : 'text-gray-500 hover:text-gray-700 hover:bg-white/40' }}">
                <i data-lucide="send" class="w-4 h-4 shrink-0"></i> <span>{{ __('Telegram Template') }}</span>
            </a>
            <a href="{{ route('admin.settings.index', ['tab' => 'cronjobs']) }}"
                class="px-6 py-4 md:py-5 md:px-6 transition-all focus:outline-none flex items-center gap-1.5 shrink-0 md:w-full text-left justify-start {{ $activeTab === 'cronjobs' ? 'border-b-2 md:border-b-0 md:border-r-2 border-shopee text-shopee bg-white font-bold' : 'text-gray-500 hover:text-gray-700 hover:bg-white/40' }}">
                <i data-lucide="clock" class="w-4 h-4 shrink-0"></i> <span>{{ __('Cron Job') }}</span>
            </a>
            <a href="{{ route('admin.settings.index', ['tab' => 'open_api']) }}"
                class="px-6 py-4 md:py-5 md:px-6 transition-all focus:outline-none flex items-center gap-1.5 shrink-0 md:w-full text-left justify-start {{ $activeTab === 'open_api' ? 'border-b-2 md:border-b-0 md:border-r-2 border-shopee text-shopee bg-white font-bold' : 'text-gray-500 hover:text-gray-700 hover:bg-white/40' }}">
                <i data-lucide="key" class="w-4 h-4 shrink-0"></i> <span>{{ __('Cấu hình Open API') }}</span>
            </a>
            <a href="{{ route('admin.settings.index', ['tab' => 'other']) }}"
                class="px-6 py-4 md:py-5 md:px-6 transition-all focus:outline-none flex items-center gap-1.5 shrink-0 md:w-full text-left justify-start {{ $activeTab === 'other' ? 'border-b-2 md:border-b-0 md:border-r-2 border-shopee text-shopee bg-white font-bold' : 'text-gray-500 hover:text-gray-700 hover:bg-white/40' }}">
                <i data-lucide="settings-2" class="w-4 h-4 shrink-0"></i> <span>{{ __('Cấu hình khác') }}</span>
            </a>
        </div>

        <!-- Form chính cập nhật cấu hình -->
        <form action="{{ route('admin.settings.update') }}" method="POST" enctype="multipart/form-data" class="md:col-span-4 flex flex-col" autocomplete="off">
            @csrf
            <!-- Fake inputs to prevent browser autofill on configuration fields -->
            <input type="text" style="display:none" autocomplete="username">
            <input type="password" style="display:none" autocomplete="new-password">

            <!-- Input ẩn đồng bộ tab hiện tại bằng AlpineJS gửi lên controller để redirect chính xác -->
            <input type="hidden" name="active_tab" :value="tab">

            <!-- Tiêu đề của Tab hiện tại (Đưa lên cao và thẳng hàng với tab active bên trái) -->
            <div class="px-6 py-4 md:px-8 md:py-[18px] border-b border-gray-150 dark:border-slate-800 bg-gray-50/10 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <h2 x-show="tab === 'general'" class="text-sm font-bold text-gray-800 dark:text-slate-200 flex items-center gap-2">
                        <i data-lucide="globe" class="w-5 h-5 text-shopee"></i>
                        <span>{{ __('Cấu hình chung') }}</span>
                    </h2>
                    <h2 x-show="tab === 'shopee'" class="text-sm font-bold text-gray-800 dark:text-slate-200 flex items-center gap-2" x-cloak>
                        <i data-lucide="shopping-bag" class="w-5 h-5 text-shopee"></i>
                        <span>{{ __('Cấu hình hoàn tiền Shopee') }}</span>
                    </h2>
                    <h2 x-show="tab === 'tiktok'" class="text-sm font-bold text-gray-800 dark:text-slate-200 flex items-center gap-2" x-cloak>
                        <i data-lucide="shopping-cart" class="w-5 h-5 text-shopee"></i>
                        <span>{{ __('Cấu hình hoàn tiền TikTok Shop') }}</span>
                    </h2>
                    <h2 x-show="tab === 'lazada'" class="text-sm font-bold text-gray-800 dark:text-slate-200 flex items-center gap-2" x-cloak>
                        <i data-lucide="shopping-bag" class="w-5 h-5 text-shopee"></i>
                        <span>{{ __('Cấu hình hoàn tiền Lazada') }}</span>
                    </h2>
                    <h2 x-show="tab === 'coupons'" class="text-sm font-bold text-gray-800 dark:text-slate-200 flex items-center gap-2" x-cloak>
                        <i data-lucide="ticket" class="w-5 h-5 text-shopee"></i>
                        <span>{{ __('Cấu hình mã giảm giá') }}</span>
                    </h2>
                    <!-- Tiêu đề tab Cấu hình phím tắt -->
                    <h2 x-show="tab === 'shortcuts'" class="text-sm font-bold text-gray-800 dark:text-slate-200 flex items-center gap-2" x-cloak>
                        <i data-lucide="smartphone" class="w-5 h-5 text-shopee"></i>
                        <span>{{ __('Cấu hình phím tắt') }}</span>
                    </h2>
                    <h2 x-show="tab === 'mlm'" class="text-sm font-bold text-gray-800 dark:text-slate-200 flex items-center gap-2" x-cloak>
                        <i data-lucide="users-2" class="w-5 h-5 text-shopee"></i>
                        <span>{{ __('Hoa hồng giới thiệu (MLM)') }}</span>
                    </h2>
                    <h2 x-show="tab === 'ranking'" class="text-sm font-bold text-gray-800 dark:text-slate-200 flex items-center gap-2" x-cloak>
                        <i data-lucide="trophy" class="w-5 h-5 text-shopee"></i>
                        <span>{{ __('Cấu hình bảng xếp hạng') }}</span>
                    </h2>
                    <h2 x-show="tab === 'reward'" class="text-sm font-bold text-gray-800 dark:text-slate-200 flex items-center gap-2" x-cloak>
                        <i data-lucide="gift" class="w-5 h-5 text-shopee"></i>
                        <span>{{ __('Cấu hình điểm danh') }}</span>
                    </h2>
                    <h2 x-show="tab === 'withdraw'" class="text-sm font-bold text-gray-800 dark:text-slate-200 flex items-center gap-2" x-cloak>
                        <i data-lucide="banknote" class="w-5 h-5 text-shopee"></i>
                        <span>{{ __('Cấu hình rút tiền') }}</span>
                    </h2>
                    <h2 x-show="tab === 'shortlink'" class="text-sm font-bold text-gray-800 dark:text-slate-200 flex items-center gap-2" x-cloak>
                        <i data-lucide="link" class="w-5 h-5 text-shopee"></i>
                        <span>{{ __('Rút gọn link') }}</span>
                    </h2>
                    <h2 x-show="tab === 'connections'" class="text-sm font-bold text-gray-800 dark:text-slate-200 flex items-center gap-2" x-cloak>
                        <i data-lucide="plug" class="w-5 h-5 text-shopee"></i>
                        <span>{{ __('Cấu hình kết nối') }}</span>
                    </h2>
                    <h2 x-show="tab === 'security'" class="text-sm font-bold text-gray-800 dark:text-slate-200 flex items-center gap-2" x-cloak>
                        <i data-lucide="shield" class="w-5 h-5 text-shopee"></i>
                        <span>{{ __('Cấu hình bảo mật') }}</span>
                    </h2>
                    <h2 x-show="tab === 'email_templates'" class="text-sm font-bold text-gray-800 dark:text-slate-200 flex items-center gap-2" x-cloak>
                        <i data-lucide="mail" class="w-5 h-5 text-shopee"></i>
                        <span>{{ __('Email Template') }}</span>
                    </h2>
                    <h2 x-show="tab === 'telegram_templates'" class="text-sm font-bold text-gray-800 dark:text-slate-200 flex items-center gap-2" x-cloak>
                        <i data-lucide="send" class="w-5 h-5 text-shopee"></i>
                        <span>{{ __('Telegram Template') }}</span>
                    </h2>
                    <h2 x-show="tab === 'cronjobs'" class="text-sm font-bold text-gray-800 dark:text-slate-200 flex items-center gap-2" x-cloak>
                        <i data-lucide="clock" class="w-5 h-5 text-shopee"></i>
                        <span>{{ __('Cron Job') }}</span>
                    </h2>
                    <h2 x-show="tab === 'open_api'" class="text-sm font-bold text-gray-800 dark:text-slate-200 flex items-center gap-2" x-cloak>
                        <i data-lucide="key" class="w-5 h-5 text-shopee"></i>
                        <span>{{ __('Cấu hình Open API') }}</span>
                    </h2>
                    <h2 x-show="tab === 'other'" class="text-sm font-bold text-gray-800 dark:text-slate-200 flex items-center gap-2" x-cloak>
                        <i data-lucide="settings-2" class="w-5 h-5 text-shopee"></i>
                        <span>{{ __('Cấu hình khác') }}</span>
                    </h2>
                </div>
            </div>

            <!-- Nội dung các Tab cấu hình và nút lưu -->
            <div class="p-3 sm:p-6 md:p-8 space-y-6">
                <!-- 1. Tab Cấu hình chung -->
                @if($activeTab === 'general')
                @include('admin.settings.partials.general')
                @endif

                <!-- 2. Tab Cấu hình hoàn tiền Shopee -->
                @if($activeTab === 'shopee')
                @include('admin.settings.partials.shopee')
                @endif

                <!-- Tab Cấu hình hoàn tiền TikTok Shop -->
                @if($activeTab === 'tiktok')
                @include('admin.settings.partials.tiktok')
                @endif

                <!-- Tab Cấu hình hoàn tiền Lazada -->
                @if($activeTab === 'lazada')
                @include('admin.settings.partials.lazada')
                @endif

                <!-- Tab Cấu hình mã giảm giá -->
                @if($activeTab === 'coupons')
                @include('admin.settings.partials.coupons')
                @endif

                <!-- Nội dung tab Cấu hình phím tắt -->
                @if($activeTab === 'shortcuts')
                @include('admin.settings.partials.shortcuts')
                @endif

                <!-- 3. Tab Hoa hồng giới thiệu MLM -->
                @if($activeTab === 'mlm')
                @include('admin.settings.partials.mlm')
                @endif

                <!-- Tab Cấu hình bảng xếp hạng -->
                @if($activeTab === 'ranking')
                @include('admin.settings.partials.ranking')
                @endif

                <!-- 4. Tab Thưởng điểm danh -->
                @if($activeTab === 'reward')
                @include('admin.settings.partials.reward')
                @endif

                <!-- 5. Tab Cấu hình rút tiền -->
                @if($activeTab === 'withdraw')
                @include('admin.settings.partials.withdraw')
                @endif

                <!-- 6. Tab Rút gọn link -->
                @if($activeTab === 'shortlink')
                @include('admin.settings.partials.shortlink')
                @endif

                <!-- 7. Tab Cấu hình kết nối -->
                @if($activeTab === 'connections')
                @include('admin.settings.partials.connections')
                @endif

                <!-- 8. Tab Cấu hình bảo mật -->
                @if($activeTab === 'security')
                @include('admin.settings.partials.security')
                @endif

                <!-- 9. Tab Mẫu email -->
                @if($activeTab === 'email_templates')
                @include('admin.settings.partials.email_templates')
                @endif

                <!-- 9.5. Tab Telegram Template -->
                @if($activeTab === 'telegram_templates')
                @include('admin.settings.partials.telegram_templates')
                @endif

                <!-- 10. Tab Cron Job -->
                @if($activeTab === 'cronjobs')
                @include('admin.settings.partials.cronjobs')
                @endif

                <!-- Tab Open API -->
                @if($activeTab === 'open_api')
                @include('admin.settings.partials.open_api')
                @endif

                <!-- 11. Tab Cấu hình khác -->
                @if($activeTab === 'other')
                @include('admin.settings.partials.other')
                @endif

                <!-- Nút Lưu cấu hình -->
                <div class="pt-4 border-t border-gray-200 flex justify-end">
                    <button type="submit" class="inline-flex items-center gap-1.5 px-6 py-3 text-xs font-semibold text-white bg-shopee hover:bg-shopee-dark rounded-xl transition-all shadow-md">
                        <i data-lucide="save" class="w-4 h-4"></i>
                        {{ __('Lưu toàn bộ cấu hình') }}
                    </button>
                </div>
            </div>

        </form>

        <!-- Modal Preview Email -->
        <template x-teleport="body">
            <div x-show="showPreviewModal"
                @open-preview-modal.window="showPreviewModal = true"
                class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto"
                x-cloak>
                <div class="fixed inset-0 bg-black/60 backdrop-blur-sm transition-opacity" @click="showPreviewModal = false"></div>

                <div class="relative bg-white dark:bg-slate-900 rounded-3xl shadow-2xl border border-gray-150 dark:border-slate-800 w-full max-w-4xl max-h-[85vh] flex flex-col mx-4 overflow-hidden z-10 transition-all transform scale-100">
                    <!-- Header -->
                    <div class="px-6 py-4 border-b border-gray-100 dark:border-slate-800 flex items-center justify-between bg-gray-50/50 dark:bg-slate-900/50">
                        <div>
                            <h3 class="text-sm font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                <i data-lucide="eye" class="w-4.5 h-4.5 text-shopee"></i>
                                Xem Trước Email Thực Tế
                            </h3>
                            <p class="text-[10px] text-gray-400 mt-0.5">Tiêu đề: <span id="previewSubject" class="font-semibold text-gray-700 dark:text-slate-200"></span></p>
                        </div>
                        <button type="button" @click="showPreviewModal = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-slate-200 transition-colors p-1.5 rounded-xl hover:bg-gray-100 dark:hover:bg-slate-800">
                            <i data-lucide="x" class="w-4.5 h-4.5"></i>
                        </button>
                    </div>

                    <!-- Body -->
                    <div class="flex-grow p-6 bg-gray-100 dark:bg-slate-950 overflow-y-auto">
                        <div class="w-full h-[500px] bg-white rounded-2xl shadow-inner border border-gray-200 overflow-hidden">
                            <iframe id="previewIframe" class="w-full h-full border-none"></iframe>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="px-6 py-4 border-t border-gray-100 dark:border-slate-800 flex justify-end gap-3 bg-gray-50/50 dark:bg-slate-900/50">
                        <button type="button" @click="showPreviewModal = false" class="px-4 py-2 border border-gray-250 dark:border-slate-700 hover:bg-gray-50 dark:hover:bg-slate-800 text-xs font-bold rounded-xl transition-all">
                            Đóng
                        </button>
                    </div>
                </div>
            </div>
        </template>

        <!-- Modal Gửi Thử Email -->
        <template x-teleport="body">
            <div x-show="showSendTestModal"
                @open-send-test-modal.window="showSendTestModal = true"
                class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto"
                x-cloak>
                <div class="fixed inset-0 bg-black/60 backdrop-blur-sm transition-opacity" @click="showSendTestModal = false"></div>

                <div class="relative bg-white dark:bg-slate-900 rounded-3xl shadow-2xl border border-gray-150 dark:border-slate-800 w-full max-w-md mx-4 overflow-hidden z-10 transition-all transform scale-100">
                    <!-- Header -->
                    <div class="px-6 py-4 border-b border-gray-100 dark:border-slate-800 flex items-center justify-between bg-gray-50/50 dark:bg-slate-900/50">
                        <h3 class="text-sm font-bold text-gray-900 dark:text-white flex items-center gap-2">
                            <i data-lucide="send" class="w-4.5 h-4.5 text-emerald-500"></i>
                            Gửi Thử Email Nghiệm Thu
                        </h3>
                        <button type="button" @click="showSendTestModal = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-slate-200 transition-colors p-1.5 rounded-xl hover:bg-gray-100 dark:hover:bg-slate-800">
                            <i data-lucide="x" class="w-4.5 h-4.5"></i>
                        </button>
                    </div>

                    <!-- Body -->
                    <div class="p-6 space-y-4">
                        <p class="text-xs text-gray-500 leading-relaxed">
                            Hệ thống sẽ gửi email chứa nội dung đang soạn thảo (đã được thay thế bằng dữ liệu mẫu) tới địa chỉ bạn nhập dưới đây để nghiệm thu trực tiếp trên hòm thư của bạn.
                        </p>
                        <div>
                            <label for="test_email_address" class="block text-xs font-bold text-gray-700 dark:text-slate-300 uppercase tracking-wider mb-1">Email Nhận Thử</label>
                            <input type="email"
                                id="test_email_address"
                                x-model="testEmailTarget"
                                placeholder="nhap-email-cua-ban@gmail.com"
                                class="block w-full px-4 py-2.5 border border-gray-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-950 dark:border-slate-800 dark:text-white">
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="px-6 py-4 border-t border-gray-100 dark:border-slate-800 flex justify-end gap-3 bg-gray-50/50 dark:bg-slate-900/50">
                        <button type="button" @click="showSendTestModal = false" :disabled="isSendingTest" class="px-4 py-2 border border-gray-250 dark:border-slate-700 hover:bg-gray-50 dark:hover:bg-slate-800 text-xs font-bold rounded-xl transition-all disabled:opacity-50">
                            Hủy
                        </button>
                        <button type="button"
                            @click="executeSendTestEmail()"
                            :disabled="isSendingTest || !testEmailTarget"
                            class="px-5 py-2 bg-emerald-500 hover:bg-emerald-600 text-white text-xs font-bold rounded-xl transition-all flex items-center gap-1.5 disabled:opacity-50">
                            <template x-if="isSendingTest">
                                <span class="w-3.5 h-3.5 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
                            </template>
                            <template x-if="!isSendingTest">
                                <i data-lucide="send" class="w-3.5 h-3.5"></i>
                            </template>
                            <span x-text="isSendingTest ? 'Đang gửi...' : 'Gửi Thử ngay'"></span>
                        </button>
                    </div>
                </div>
            </div>
        </template>

        <!-- Modal Hướng Dẫn Cấu Hình (Google / Telegram) -->
        <template x-teleport="body">
            <div x-show="showGuideModal"
                class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto"
                x-cloak>
                <div class="fixed inset-0 bg-black/60 backdrop-blur-sm transition-opacity" @click="showGuideModal = false"></div>

                <div class="relative bg-white dark:bg-slate-900 rounded-3xl shadow-2xl border border-gray-150 dark:border-slate-800 w-full max-w-2xl mx-4 overflow-hidden z-10 transition-all transform scale-100 flex flex-col max-h-[85vh]">
                    <!-- Header -->
                    <div class="px-6 py-4 border-b border-gray-100 dark:border-slate-800 flex items-center justify-between bg-gray-50/50 dark:bg-slate-900/50 shrink-0">
                        <h3 class="text-sm font-bold text-gray-900 dark:text-white flex items-center gap-2">
                            <i data-lucide="help-circle" class="w-4.5 h-4.5 text-shopee"></i>
                            <span x-text="guideType === 'telegram' ? 'Hướng dẫn cấu hình Telegram Bot' : (guideType === 'google' ? 'Hướng dẫn cấu hình Đăng nhập Google' : (guideType === 'license' ? 'Hướng dẫn cấu hình Bản quyền & Cập nhật' : 'Hướng dẫn cấu hình Cloudflare Turnstile Captcha'))"></span>
                        </h3>
                        <button type="button" @click="showGuideModal = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-slate-200 transition-colors p-1.5 rounded-xl hover:bg-gray-100 dark:hover:bg-slate-800">
                            <i data-lucide="x" class="w-4.5 h-4.5"></i>
                        </button>
                    </div>

                    <!-- Body -->
                    <div class="p-6 overflow-y-auto space-y-4 text-xs leading-relaxed text-gray-650 dark:text-slate-300">
                        <!-- Telegram Bot Guide -->
                        <template x-if="guideType === 'telegram'">
                            <div class="space-y-4">
                                <div class="bg-blue-50/50 dark:bg-blue-950/20 border border-blue-100 dark:border-blue-900/50 rounded-2xl p-4 flex gap-3">
                                    <i data-lucide="info" class="w-5 h-5 text-blue-500 shrink-0 mt-0.5"></i>
                                    <div>
                                        <p class="font-bold text-blue-800 dark:text-blue-400 mb-0.5">Telegram Bot</p>
                                        <p class="text-[11px] text-blue-700/80 dark:text-blue-400/80">Cho phép hệ thống tự động bắn thông báo khi có các giao dịch nhạy cảm như Yêu cầu rút tiền mới, Điểm danh, Đối soát,... đến group chat quản trị của bạn.</p>
                                    </div>
                                </div>

                                <div class="space-y-3">
                                    <div class="flex items-start gap-2.5">
                                        <span class="w-5 h-5 rounded-full bg-shopee/10 text-shopee font-bold flex items-center justify-center shrink-0 text-[10px] mt-0.5">1</span>
                                        <div>
                                            <p class="font-bold text-gray-900 dark:text-white">Tạo Bot và lấy Token</p>
                                            <p class="text-gray-500 dark:text-gray-400 mt-0.5">Tìm kiếm <strong class="text-shopee">@BotFather</strong> trên Telegram. Gửi lệnh <code class="px-1.5 py-0.5 bg-gray-100 dark:bg-slate-800 rounded font-mono">/newbot</code>, làm theo hướng dẫn để đặt tên bot và username. BotFather sẽ gửi cho bạn một chuỗi **HTTP API Token** (ví dụ: <code class="font-mono">123456:ABC...</code>). Hãy copy nó dán vào trường **Telegram Bot Token**.</p>
                                        </div>
                                    </div>

                                    <div class="flex items-start gap-2.5">
                                        <span class="w-5 h-5 rounded-full bg-shopee/10 text-shopee font-bold flex items-center justify-center shrink-0 text-[10px] mt-0.5">2</span>
                                        <div>
                                            <p class="font-bold text-gray-900 dark:text-white">Lấy Telegram Chat ID</p>
                                            <p class="text-gray-500 dark:text-gray-400 mt-0.5">Để lấy Chat ID cá nhân của bạn, hãy tìm kiếm và bắt đầu trò chuyện với bot <strong class="text-shopee">@cmsnt_bot</strong> trên Telegram, gửi lệnh <code class="px-1.5 py-0.5 bg-gray-100 dark:bg-slate-800 rounded font-mono">/myid</code> để nhận ID của mình (ví dụ: <code class="font-mono">123456789</code>).
                                                <br><br>
                                                Nếu bạn muốn nhận thông báo qua Nhóm (Group) Telegram, hãy tạo nhóm chat, thêm Bot của bạn cùng bot <strong class="text-blue-500">@RawDataBot</strong> vào nhóm. Bot sẽ hiển thị ID của nhóm (thường bắt đầu bằng dấu trừ, ví dụ: <code class="font-mono">-100213456789</code>). Copy ID này dán vào ô **Telegram Chat ID**.
                                            </p>
                                        </div>
                                    </div>

                                    <div class="flex items-start gap-2.5">
                                        <span class="w-5 h-5 rounded-full bg-shopee/10 text-shopee font-bold flex items-center justify-center shrink-0 text-[10px] mt-0.5">3</span>
                                        <div>
                                            <p class="font-bold text-gray-900 dark:text-white">Kiểm tra hoạt động</p>
                                            <p class="text-gray-500 dark:text-gray-400 mt-0.5">Bật trạng thái **BẬT (ON)** và bấm **Lưu cấu hình**. Để kiểm tra bot có bắn thông báo chuẩn không, hãy thử thực hiện các giao dịch như tạo yêu cầu rút tiền hoặc chạy thử điểm danh.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>

                        <!-- Google Login Guide -->
                        <template x-if="guideType === 'google'">
                            <div class="space-y-4">
                                <div class="bg-blue-50/50 dark:bg-blue-950/20 border border-blue-100 dark:border-blue-900/50 rounded-2xl p-4 flex gap-3">
                                    <i data-lucide="info" class="w-5 h-5 text-blue-500 shrink-0 mt-0.5"></i>
                                    <div>
                                        <p class="font-bold text-blue-800 dark:text-blue-400 mb-0.5">Đăng nhập bằng Google</p>
                                        <p class="text-[11px] text-blue-700/80 dark:text-blue-400/80">Giúp thành viên đăng nhập, đăng ký chỉ với 1 cú click chuột qua tài khoản Gmail, giúp tối ưu tỷ lệ chuyển đổi khách hàng.</p>
                                    </div>
                                </div>

                                <div class="space-y-3">
                                    <div class="flex items-start gap-2.5">
                                        <span class="w-5 h-5 rounded-full bg-shopee/10 text-shopee font-bold flex items-center justify-center shrink-0 text-[10px] mt-0.5">1</span>
                                        <div>
                                            <p class="font-bold text-gray-900 dark:text-white">Tạo dự án trên Google Cloud</p>
                                            <p class="text-gray-500 dark:text-gray-400 mt-0.5">Truy cập <a href="https://console.cloud.google.com" target="_blank" class="text-blue-500 hover:underline font-bold">Google Cloud Console</a>. Tạo một dự án mới (New Project).</p>
                                        </div>
                                    </div>

                                    <div class="flex items-start gap-2.5">
                                        <span class="w-5 h-5 rounded-full bg-shopee/10 text-shopee font-bold flex items-center justify-center shrink-0 text-[10px] mt-0.5">2</span>
                                        <div>
                                            <p class="font-bold text-gray-900 dark:text-white">OAuth Consent Screen (Màn hình đồng ý)</p>
                                            <p class="text-gray-500 dark:text-gray-400 mt-0.5">Tại menu trái, chọn **APIs & Services** -> **OAuth consent screen**. Chọn User Type là <strong class="text-gray-800 dark:text-slate-200">External</strong>, điền các thông tin app (Tên app, Email hỗ trợ, Domain,...). Lưu lại.</p>
                                        </div>
                                    </div>

                                    <div class="flex items-start gap-2.5">
                                        <span class="w-5 h-5 rounded-full bg-shopee/10 text-shopee font-bold flex items-center justify-center shrink-0 text-[10px] mt-0.5">3</span>
                                        <div>
                                            <p class="font-bold text-gray-900 dark:text-white">Tạo Credentials & Khai báo Redirect URI</p>
                                            <p class="text-gray-500 dark:text-gray-400 mt-0.5">Chọn **Credentials** -> **Create Credentials** -> **OAuth client ID**.
                                                <br>Chọn Application type là <strong class="text-gray-800 dark:text-slate-200">Web application</strong>.
                                                <br>Ở mục **Authorized redirect URIs**, bấm Add URI và dán chính xác link Callback hiển thị ở trang cài đặt (ví dụ: <code class="px-1 py-0.5 bg-gray-100 dark:bg-slate-800 rounded font-mono">{{ url('auth/google/callback') }}</code>).
                                            </p>
                                        </div>
                                    </div>

                                    <div class="flex items-start gap-2.5">
                                        <span class="w-5 h-5 rounded-full bg-shopee/10 text-shopee font-bold flex items-center justify-center shrink-0 text-[10px] mt-0.5">4</span>
                                        <div>
                                            <p class="font-bold text-gray-900 dark:text-white">Lấy Client ID / Client Secret</p>
                                            <p class="text-gray-500 dark:text-gray-400 mt-0.5">Google sẽ tạo cho bạn một bộ **Client ID** và **Client Secret**. Copy 2 chuỗi này dán vào trường tương ứng của trang cài đặt này, đổi trạng thái sang **Bật hoạt động** và bấm lưu.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>

                        <!-- License Guide -->
                        <template x-if="guideType === 'license'">
                            <div class="space-y-4">
                                <div class="bg-blue-50/50 dark:bg-blue-950/20 border border-blue-100 dark:border-blue-900/50 rounded-2xl p-4 flex gap-3">
                                    <i data-lucide="info" class="w-5 h-5 text-blue-500 shrink-0 mt-0.5"></i>
                                    <div>
                                        <p class="font-bold text-blue-800 dark:text-blue-400 mb-0.5">Mã Bản Quyền (License Key)</p>
                                        <p class="text-[11px] text-blue-700/80 dark:text-blue-400/80">Giúp xác thực giấy phép sử dụng mã nguồn và kích hoạt tính năng kiểm tra, tự động nâng cấp phiên bản mới trực tiếp từ máy chủ CMSNT.</p>
                                    </div>
                                </div>

                                <div class="space-y-3">
                                    <div class="flex items-start gap-2.5">
                                        <span class="w-5 h-5 rounded-full bg-shopee/10 text-shopee font-bold flex items-center justify-center shrink-0 text-[10px] mt-0.5">1</span>
                                        <div>
                                            <p class="font-bold text-gray-900 dark:text-white">Truy cập Cổng khách hàng CMSNT</p>
                                            <p class="text-gray-500 dark:text-gray-400 mt-0.5">Truy cập cổng quản lý dịch vụ tại địa chỉ <a href="https://client.cmsnt.co" target="_blank" class="text-blue-500 hover:underline font-bold">https://client.cmsnt.co</a> và đăng nhập tài khoản mua sản phẩm của bạn.</p>
                                        </div>
                                    </div>

                                    <div class="flex items-start gap-2.5">
                                        <span class="w-5 h-5 rounded-full bg-shopee/10 text-shopee font-bold flex items-center justify-center shrink-0 text-[10px] mt-0.5">2</span>
                                        <div>
                                            <p class="font-bold text-gray-900 dark:text-white">Vào mục dịch vụ của tôi</p>
                                            <p class="text-gray-500 dark:text-gray-400 mt-0.5">Nhấp vào mục <strong class="text-gray-800 dark:text-slate-200">Services</strong> (Dịch vụ) -> <strong class="text-gray-800 dark:text-slate-200">My Services</strong> (Dịch vụ của tôi) trên thanh điều hướng chính.</p>
                                        </div>
                                    </div>

                                    <div class="flex items-start gap-2.5">
                                        <span class="w-5 h-5 rounded-full bg-shopee/10 text-shopee font-bold flex items-center justify-center shrink-0 text-[10px] mt-0.5">3</span>
                                        <div>
                                            <p class="font-bold text-gray-900 dark:text-white">Sao chép Mã bản quyền (License Key)</p>
                                            <p class="text-gray-500 dark:text-gray-400 mt-0.5">Chọn sản phẩm/dịch vụ **Hoàn Tiền Shopee** trong danh sách. Tại trang chi tiết dịch vụ, bạn sẽ nhìn thấy mục **License Key** (Mã bản quyền). Hãy bôi đen và sao chép (Copy) toàn bộ chuỗi ký tự này.</p>
                                        </div>
                                    </div>

                                    <div class="flex items-start gap-2.5">
                                        <span class="w-5 h-5 rounded-full bg-shopee/10 text-shopee font-bold flex items-center justify-center shrink-0 text-[10px] mt-0.5">4</span>
                                        <div>
                                            <p class="font-bold text-gray-900 dark:text-white">Dán mã bản quyền & Lưu lại</p>
                                            <p class="text-gray-500 dark:text-gray-400 mt-0.5">Quay lại trang quản trị này, dán (Paste) mã bản quyền vừa copy vào ô **Mã bản quyền (License Key)**, sau đó nhấn nút **Lưu toàn bộ cấu hình** ở cuối trang để hoàn tất kích hoạt.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>

                        <!-- Cloudflare Turnstile Captcha Guide -->
                        <template x-if="guideType === 'captcha'">
                            <div class="space-y-4">
                                <div class="bg-blue-50/50 dark:bg-blue-950/20 border border-blue-100 dark:border-blue-900/50 rounded-2xl p-4 flex gap-3">
                                    <i data-lucide="info" class="w-5 h-5 text-blue-500 shrink-0 mt-0.5"></i>
                                    <div>
                                        <p class="font-bold text-blue-800 dark:text-blue-400 mb-0.5">Cloudflare Turnstile Captcha</p>
                                        <p class="text-[11px] text-blue-700/80 dark:text-blue-400/80">Giải pháp thay thế reCAPTCHA hoàn hảo từ Cloudflare: Bảo mật cao, chống spam/bot vô cùng hiệu quả mà hoàn toàn không làm phiền người dùng thật bằng các câu đố hình ảnh phức tạp.</p>
                                    </div>
                                </div>

                                <div class="space-y-3">
                                    <div class="flex items-start gap-2.5">
                                        <span class="w-5 h-5 rounded-full bg-shopee/10 text-shopee font-bold flex items-center justify-center shrink-0 text-[10px] mt-0.5">1</span>
                                        <div>
                                            <p class="font-bold text-gray-900 dark:text-white">Đăng nhập Cloudflare</p>
                                            <p class="text-gray-500 dark:text-gray-400 mt-0.5">Truy cập bảng điều khiển Cloudflare tại địa chỉ <a href="https://dash.cloudflare.com" target="_blank" class="text-blue-500 hover:underline font-bold">https://dash.cloudflare.com</a> và đăng nhập tài khoản của bạn.</p>
                                        </div>
                                    </div>

                                    <div class="flex items-start gap-2.5">
                                        <span class="w-5 h-5 rounded-full bg-shopee/10 text-shopee font-bold flex items-center justify-center shrink-0 text-[10px] mt-0.5">2</span>
                                        <div>
                                            <p class="font-bold text-gray-900 dark:text-white">Truy cập menu Turnstile và thêm Website</p>
                                            <p class="text-gray-500 dark:text-gray-400 mt-0.5">Tại thanh menu bên trái màn hình Cloudflare, chọn mục **Turnstile**. Bấm nút **Add site** (Thêm trang web) để tạo cấu hình bảo mật mới cho tên miền của bạn.</p>
                                        </div>
                                    </div>

                                    <div class="flex items-start gap-2.5">
                                        <span class="w-5 h-5 rounded-full bg-shopee/10 text-shopee font-bold flex items-center justify-center shrink-0 text-[10px] mt-0.5">3</span>
                                        <div>
                                            <p class="font-bold text-gray-900 dark:text-white">Khai báo thông tin Website</p>
                                            <p class="text-gray-500 dark:text-gray-400 mt-0.5">
                                                - **Site name:** Nhập tên website của bạn để dễ quản lý.
                                                <br>- **Domain:** Nhập chính xác tên miền chạy web (ví dụ: <code class="px-1 py-0.5 bg-gray-100 dark:bg-slate-800 rounded font-mono">{{ request()->getHost() }}</code>).
                                                <br>- **Widget Mode:** Chọn chế độ **Managed** (Khuyên dùng - Cloudflare sẽ tự động tính toán mức độ rủi ro để hiển thị thử thách hoặc cho qua tự động không làm phiền khách hàng).
                                                <br>Bấm **Create** để hoàn thành.
                                            </p>
                                        </div>
                                    </div>

                                    <div class="flex items-start gap-2.5">
                                        <span class="w-5 h-5 rounded-full bg-shopee/10 text-shopee font-bold flex items-center justify-center shrink-0 text-[10px] mt-0.5">4</span>
                                        <div>
                                            <p class="font-bold text-gray-900 dark:text-white">Copy Site Key & Secret Key</p>
                                            <p class="text-gray-500 dark:text-gray-400 mt-0.5">Sau khi tạo, Cloudflare sẽ cung cấp cho bạn một bộ **Site Key** và **Secret Key**. Hãy sao chép 2 chuỗi ký tự này, dán tương ứng vào các ô **Turnstile Site Key** và **Turnstile Secret Key** tại trang cấu hình này.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>

                    <!-- Footer -->
                    <div class="px-6 py-4 border-t border-gray-100 dark:border-slate-800 flex justify-end bg-gray-50/50 dark:bg-slate-900/50 shrink-0">
                        <button type="button" @click="showGuideModal = false" class="px-4 py-2 border border-gray-250 dark:border-slate-700 hover:bg-gray-50 dark:hover:bg-slate-800 text-xs font-bold rounded-xl transition-all">
                            Đóng hướng dẫn
                        </button>
                    </div>
                </div>
            </div>
        </template>

    </div>
</div>
@endsection

@section('scripts')
{{-- TinyMCE: trình soạn thảo trực quan dùng cho popup trang chủ và toàn bộ mẫu email --}}
<script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/6.8.2/tinymce.min.js" referrerpolicy="origin"></script>
<script>
    // Khởi tạo TinyMCE cho ô Nội dung thông báo popup (hỗ trợ HTML / ảnh / video)
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof tinymce === 'undefined' || !document.getElementById('home_popup_content')) return;
        const isDark = document.documentElement.classList.contains('dark');
        tinymce.init({
            selector: '#home_popup_content',
            height: 360,
            menubar: false,
            language: 'vi',
            branding: false,
            promotion: false,
            plugins: 'advlist autolink lists link image media charmap preview anchor searchreplace visualblocks code fullscreen insertdatetime table help wordcount',
            toolbar: 'undo redo | blocks fontsize | bold italic forecolor backcolor | alignleft aligncenter alignright | bullist numlist | link image media table | removeformat code preview fullscreen',
            content_style: 'body { font-family: Inter, sans-serif; font-size: 14px }',
            skin: isDark ? 'oxide-dark' : 'oxide',
            content_css: isDark ? 'dark' : 'default',
            // Giữ nguyên toàn bộ HTML/inline-style để hiển thị popup đúng như nhập
            valid_elements: '*[*]',
            verify_html: false,
            file_picker_types: 'image media',
            file_picker_callback: function(callback, value, meta) {
                if (meta.filetype === 'image' || meta.filetype === 'media') {
                    window.tinymceFilePickerCallback = callback;
                    openElfinderPopup('tinymce_image');
                }
            }
        });
    });

    // Component AlpineJS: tạo nhanh nội dung popup bằng AI
    function popupAiGenerator() {
        return {
            aiEnabled: {{ ($settings['ai_status'] ?? '0') === '1' ? 'true' : 'false' }},
            show: false,
            prompt: '',
            tone: 'than-thien',
            loading: false,
            error: '',

            open() {
                if (!this.aiEnabled) {
                    window.dispatchEvent(new CustomEvent('toast', {
                        detail: {
                            text: "{{ __('Dịch vụ AI hiện đang tắt. Vui lòng kích hoạt trong tab Kết nối > AI.') }}",
                            type: 'warning'
                        }
                    }));
                    return;
                }
                this.error = '';
                this.show = true;
                this.$nextTick(() => {
                    if (window.lucide) window.lucide.createIcons();
                });
            },

            generate() {
                if (this.loading) return;
                if (!this.prompt.trim()) {
                    this.error = "{{ __('Vui lòng mô tả nội dung thông báo bạn muốn tạo.') }}";
                    return;
                }
                this.loading = true;
                this.error = '';
                fetch('{{ route('admin.settings.generate_popup_ai') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify({
                                prompt: this.prompt,
                                tone: this.tone
                            })
                        })
                    .then(r => r.json().then(data => ({
                        ok: r.ok,
                        data
                    })))
                    .then(({
                        ok,
                        data
                    }) => {
                        if (!ok || !data.success) {
                            this.error = data.message || "{{ __('Không thể tạo nội dung. Vui lòng thử lại.') }}";
                            return;
                        }
                        if (data.content) {
                            const editor = window.tinymce ? tinymce.get('home_popup_content') : null;
                            if (editor) {
                                editor.setContent(data.content);
                            } else {
                                const ta = document.getElementById('home_popup_content');
                                if (ta) ta.value = data.content;
                            }
                        }
                        this.show = false;
                    })
                    .catch(() => {
                        this.error = "{{ __('Lỗi kết nối. Vui lòng thử lại.') }}";
                    })
                    .finally(() => {
                        this.loading = false;
                    });
            },
        };
    }
</script>
<script>
    // Hàm mở elFinder dạng popup window
    function openElfinderPopup(inputId) {
        var width = 900;
        var height = 600;
        var left = (screen.width - width) / 2;
        var top = (screen.height - height) / 2;
        var url = '{{ url("elfinder/popup") }}/' + inputId;
        window.open(url, 'elfinderPicker', 'width=' + width + ',height=' + height + ',left=' + left + ',top=' + top + ',resizable=yes,scrollbars=yes,status=no');
    }

    // Hàm callback toàn cục được gọi từ elFinder standalonepopup
    window.processSelectedFile = function(fileUrl, inputId) {
        // Trường hợp chọn ảnh cho trình soạn thảo TinyMCE (popup thông báo trang chủ)
        if (inputId === 'tinymce_image' && window.tinymceFilePickerCallback) {
            window.tinymceFilePickerCallback(fileUrl, {
                alt: 'Ảnh thông báo'
            });
            window.tinymceFilePickerCallback = null;
            return;
        }

        var inputElement = document.getElementById(inputId);
        if (inputElement) {
            inputElement.value = fileUrl;
            // Gửi sự kiện input để AlpineJS đồng bộ dữ liệu vào biến x-model tương ứng
            inputElement.dispatchEvent(new Event('input'));

            // Reset các input file tải lên tương ứng
            if (inputId === 'site_logo') {
                var fileInput = document.getElementById('logo-file-input');
                if (fileInput) fileInput.value = '';
            } else if (inputId === 'site_logo_dark') {
                var fileInput = document.getElementById('logo-dark-file-input');
                if (fileInput) fileInput.value = '';
            } else if (inputId === 'site_favicon') {
                var fileInput = document.getElementById('favicon-file-input');
                if (fileInput) fileInput.value = '';
            } else if (inputId === 'site_og_image') {
                var fileInput = document.getElementById('og-image-file-input');
                if (fileInput) fileInput.value = '';
            }
        }
    };
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof tinymce === 'undefined') return;
        const isDark = document.documentElement.classList.contains('dark');

        // Danh sách các textarea mẫu email cần biến thành trình soạn thảo TinyMCE
        const editors = [
            'email_content_forgot_password',
            'email_content_otp',
            'email_content_verify_email',
            'email_content_welcome',
            'email_content_withdrawal_created',
            'email_content_withdrawal_approved',
            'email_content_gift_approved',
            'email_content_gift_created',
            'email_content_cashback_created',
            'email_content_cashback_approved',
            'referral_policy'
        ];
        const selector = editors.filter(id => document.getElementById(id)).map(id => '#' + id).join(',');
        if (selector) {
            tinymce.init({
                selector: selector,
                height: 420,
                menubar: false,
                language: 'vi',
                branding: false,
                promotion: false,
                plugins: 'advlist autolink lists link image media charmap preview anchor searchreplace visualblocks code fullscreen insertdatetime table help wordcount',
                toolbar: 'undo redo | blocks fontsize | bold italic forecolor backcolor | alignleft aligncenter alignright | bullist numlist | link image media table | removeformat code preview fullscreen',
                content_style: 'body { font-family: Inter, sans-serif; font-size: 14px }',
                skin: isDark ? 'oxide-dark' : 'oxide',
                content_css: isDark ? 'dark' : 'default',
                // Giữ nguyên toàn bộ HTML/inline-style của mẫu email, không để TinyMCE lọc bỏ
                valid_elements: '*[*]',
                verify_html: false,
                file_picker_types: 'image media',
                file_picker_callback: function(callback, value, meta) {
                    if (meta.filetype === 'image' || meta.filetype === 'media') {
                        window.tinymceFilePickerCallback = callback;
                        openElfinderPopup('tinymce_image');
                    }
                }
            });
        }
    });

    function previewEmail(templateKey) {
        // Lấy instance TinyMCE của mẫu email tương ứng
        const editor = (typeof tinymce !== 'undefined') ? tinymce.get('email_content_' + templateKey) : null;
        if (!editor) return;

        let htmlContent = editor.getContent();
        const subject = document.getElementById('email_subject_' + templateKey).value;

        // Giả lập dữ liệu mẫu để thay thế các thẻ biến động
        const demoData = {
            '{name}': 'Nguyễn Văn A',
            '{email}': 'nguyenvana@gmail.com',
            '{reset_url}': 'https://hoantienshopee.ddev.site/password/reset/token123456',
            '{otp}': '123456',
            '{dashboard_url}': 'https://hoantienshopee.ddev.site/dashboard',
            '{amount}': '150,000',
            '{payment_method}': 'Chuyển khoản ngân hàng',
            '{account_name}': 'NGUYEN VAN A',
            '{account_number}': '19036789123456',
            '{bank_row}': '<tr><td style="padding: 8px 0; color: #666666;">Ngân hàng nhận:</td><td style="padding: 8px 0; text-align: right; font-weight: 500;">Techcombank (Chi nhánh Hà Nội)</td></tr>',
            '{gift_title}': 'Thẻ cào Viettel 50k',
            '{gift_price}': '50,000',
            '{gift_data}': 'Code: 1234567890 | Seri: 0987654321',
            '{gift_data_row}': '<tr><td style="padding: 8px 0; color: #666666;">Thông tin quà tặng:</td><td style="padding: 8px 0; text-align: right; font-weight: 500; font-family: monospace;">Code: 1234567890 | Seri: 0987654321</td></tr>',
            '{notes}': 'Đã duyệt qua hệ thống tự động.',
            '{notes_row}': '<tr><td style="padding: 8px 0; color: #666666;">Ghi chú:</td><td style="padding: 8px 0; text-align: right; font-style: italic;">Đã duyệt qua hệ thống tự động.</td></tr>',
            '{order_id}': 'SG-123456789',
            '{product_name}': 'Áo thun Nam Cotton dáng basic trẻ trung',
            '{price}': '120,000',
            '{cashback_amount}': '8,400',
            '{year}': new Date().getFullYear()
        };

        // Thay thế các thẻ biến động bằng dữ liệu giả lập
        for (const [key, val] of Object.entries(demoData)) {
            htmlContent = htmlContent.replaceAll(key, val);
        }

        // Ghi nội dung vào Iframe
        const iframe = document.getElementById('previewIframe');
        const iframeDoc = iframe.contentDocument || iframe.contentWindow.document;
        iframeDoc.open();
        iframeDoc.write(htmlContent);
        iframeDoc.close();

        // Cập nhật tiêu đề email lên header modal
        document.getElementById('previewSubject').innerText = subject;

        // Dispatch sự kiện để mở modal qua AlpineJS
        window.dispatchEvent(new CustomEvent('open-preview-modal'));
    }
</script>
@endsection