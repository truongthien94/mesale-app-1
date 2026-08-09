@extends('layouts.admin')

@section('title', __('Quản lý Bot') . ' - ' . $siteName)

@section('content')
<div class="space-y-6" x-data="{ tab: '{{ $tab }}' }">

    {{-- Tiêu đề --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-slate-100">{{ __('Quản lý Bot') }}</h1>
            <p class="text-sm text-gray-500 dark:text-slate-400 mt-1">{{ __('Cấu hình Bot Zalo & Telegram để người dùng lấy link hoàn tiền qua tin nhắn.') }}</p>
        </div>
        <div class="flex items-center gap-2">
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold {{ $zaloConfig->is_enabled ? 'bg-green-50 text-green-700 dark:bg-green-900/20 dark:text-green-400' : 'bg-gray-100 text-gray-500 dark:bg-slate-800 dark:text-slate-400' }}">
                <span class="w-1.5 h-1.5 rounded-full {{ $zaloConfig->is_enabled ? 'bg-green-500 animate-pulse' : 'bg-gray-400' }}"></span>
                Zalo Bot
            </span>
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold {{ $telegramConfig->is_enabled ? 'bg-blue-50 text-blue-700 dark:bg-blue-900/20 dark:text-blue-400' : 'bg-gray-100 text-gray-500 dark:bg-slate-800 dark:text-slate-400' }}">
                <span class="w-1.5 h-1.5 rounded-full {{ $telegramConfig->is_enabled ? 'bg-blue-500 animate-pulse' : 'bg-gray-400' }}"></span>
                Telegram Bot
            </span>
        </div>
    </div>

    {{-- Liên kết trang khách liên quan --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
        <a href="{{ route('bot.guide') }}" target="_blank"
            class="flex items-center gap-3 px-4 py-3 rounded-xl bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 hover:border-shopee dark:hover:border-shopee hover:shadow-sm transition-all group">
            <div class="w-8 h-8 rounded-lg bg-shopee/10 flex items-center justify-center flex-shrink-0">
                <i data-lucide="book-open" class="w-4 h-4 text-shopee"></i>
            </div>
            <div class="min-w-0">
                <p class="text-xs font-bold text-gray-800 dark:text-slate-200 group-hover:text-shopee transition-colors truncate">{{ __('Trang hướng dẫn Bot') }}</p>
                <p class="text-[10px] text-gray-400 dark:text-slate-500 font-mono truncate">/bot-guide</p>
            </div>
            <i data-lucide="external-link" class="w-3.5 h-3.5 text-gray-300 dark:text-slate-600 group-hover:text-shopee transition-colors ml-auto flex-shrink-0"></i>
        </a>
        @php
            $zaloWebhookUrl = $zaloConfig->webhook_secret
                ? url('/webhook/zalo-bot/' . $zaloConfig->webhook_secret)
                : url('/webhook/zalo-bot');
            $telegramWebhookUrl = $telegramConfig->webhook_secret
                ? url('/webhook/telegram-bot/' . $telegramConfig->webhook_secret)
                : url('/webhook/telegram-bot');
            $zaloSecured = !empty($zaloConfig->webhook_secret);
            $telegramSecured = !empty($telegramConfig->webhook_secret);
        @endphp
        <div class="flex items-center gap-3 px-4 py-3 rounded-xl bg-white dark:bg-slate-900 border {{ $zaloSecured ? 'border-green-200 dark:border-green-800/40' : 'border-amber-200 dark:border-amber-800/40' }}">
            <div class="w-8 h-8 rounded-lg {{ $zaloSecured ? 'bg-green-100 dark:bg-green-900/20' : 'bg-amber-100 dark:bg-amber-900/20' }} flex items-center justify-center flex-shrink-0">
                <i data-lucide="{{ $zaloSecured ? 'shield-check' : 'shield-alert' }}" class="w-4 h-4 {{ $zaloSecured ? 'text-green-600 dark:text-green-400' : 'text-amber-600 dark:text-amber-400' }}"></i>
            </div>
            <div class="min-w-0">
                <p class="text-xs font-bold text-gray-800 dark:text-slate-200 flex items-center gap-1 truncate">
                    {{ __('Webhook Zalo') }}
                    <span class="text-[9px] font-semibold px-1 py-0.5 rounded {{ $zaloSecured ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400' }}">
                        {{ $zaloSecured ? __('Đã bảo mật') : __('Chưa bảo mật') }}
                    </span>
                </p>
                <p class="text-[10px] text-gray-400 dark:text-slate-500 font-mono truncate" title="{{ $zaloWebhookUrl }}">/webhook/zalo-bot/{{ $zaloSecured ? substr($zaloConfig->webhook_secret, 0, 8) . '...' : '(chưa bảo mật)' }}</p>
            </div>
            <button onclick="navigator.clipboard.writeText('{{ $zaloWebhookUrl }}')" title="{{ __('Sao chép URL đầy đủ') }}"
                class="ml-auto flex-shrink-0 p-1 rounded-lg hover:bg-gray-100 dark:hover:bg-slate-800 transition-colors">
                <i data-lucide="copy" class="w-3.5 h-3.5 text-gray-400 dark:text-slate-500"></i>
            </button>
        </div>
        <div class="flex items-center gap-3 px-4 py-3 rounded-xl bg-white dark:bg-slate-900 border {{ $telegramSecured ? 'border-green-200 dark:border-green-800/40' : 'border-amber-200 dark:border-amber-800/40' }}">
            <div class="w-8 h-8 rounded-lg {{ $telegramSecured ? 'bg-green-100 dark:bg-green-900/20' : 'bg-amber-100 dark:bg-amber-900/20' }} flex items-center justify-center flex-shrink-0">
                <i data-lucide="{{ $telegramSecured ? 'shield-check' : 'shield-alert' }}" class="w-4 h-4 {{ $telegramSecured ? 'text-green-600 dark:text-green-400' : 'text-amber-600 dark:text-amber-400' }}"></i>
            </div>
            <div class="min-w-0">
                <p class="text-xs font-bold text-gray-800 dark:text-slate-200 flex items-center gap-1 truncate">
                    {{ __('Webhook Telegram') }}
                    <span class="text-[9px] font-semibold px-1 py-0.5 rounded {{ $telegramSecured ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400' }}">
                        {{ $telegramSecured ? __('Đã bảo mật') : __('Chưa bảo mật') }}
                    </span>
                </p>
                <p class="text-[10px] text-gray-400 dark:text-slate-500 font-mono truncate" title="{{ $telegramWebhookUrl }}">/webhook/telegram-bot/{{ $telegramSecured ? substr($telegramConfig->webhook_secret, 0, 8) . '...' : '(chưa bảo mật)' }}</p>
            </div>
            <button onclick="navigator.clipboard.writeText('{{ $telegramWebhookUrl }}')" title="{{ __('Sao chép URL đầy đủ') }}"
                class="ml-auto flex-shrink-0 p-1 rounded-lg hover:bg-gray-100 dark:hover:bg-slate-800 transition-colors">
                <i data-lucide="copy" class="w-3.5 h-3.5 text-gray-400 dark:text-slate-500"></i>
            </button>
        </div>
    </div>

    {{-- Tabs --}}
    <div class="bg-white dark:bg-slate-900 rounded-2xl shadow-sm border border-gray-200 dark:border-slate-800 overflow-hidden">
        {{-- Tab Headers --}}
        <div class="flex border-b border-gray-200 dark:border-slate-800 bg-gray-50/50 dark:bg-slate-900/50 overflow-x-auto">
            <a href="{{ route('admin.bots.index', ['tab' => 'zalo']) }}"
                class="flex items-center gap-2 px-6 py-4 text-sm font-semibold shrink-0 transition-all border-b-2 {{ $tab === 'zalo' ? 'border-shopee text-shopee bg-white dark:bg-slate-900' : 'border-transparent text-gray-500 dark:text-slate-400 hover:text-gray-700 dark:hover:text-slate-200 hover:bg-white/60 dark:hover:bg-slate-800/40' }}">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 14H9V8h2v8zm4 0h-2V8h2v8z"/></svg>
                {{ __('Zalo Bot') }}
            </a>
            <a href="{{ route('admin.bots.index', ['tab' => 'telegram']) }}"
                class="flex items-center gap-2 px-6 py-4 text-sm font-semibold shrink-0 transition-all border-b-2 {{ $tab === 'telegram' ? 'border-shopee text-shopee bg-white dark:bg-slate-900' : 'border-transparent text-gray-500 dark:text-slate-400 hover:text-gray-700 dark:hover:text-slate-200 hover:bg-white/60 dark:hover:bg-slate-800/40' }}">
                <i data-lucide="send" class="w-4 h-4"></i>
                {{ __('Telegram Bot') }}
            </a>
            <a href="{{ route('admin.bots.index', ['tab' => 'messages']) }}"
                class="flex items-center gap-2 px-6 py-4 text-sm font-semibold shrink-0 transition-all border-b-2 {{ $tab === 'messages' ? 'border-shopee text-shopee bg-white dark:bg-slate-900' : 'border-transparent text-gray-500 dark:text-slate-400 hover:text-gray-700 dark:hover:text-slate-200 hover:bg-white/60 dark:hover:bg-slate-800/40' }}">
                <i data-lucide="message-square" class="w-4 h-4"></i>
                {{ __('Lịch sử tin nhắn') }}
                @php $msgCount = \App\Models\BotMessage::count(); @endphp
                @if($msgCount > 0)
                <span class="inline-flex items-center justify-center px-1.5 py-0.5 text-[9px] font-black leading-none text-white bg-shopee rounded-full">{{ $msgCount > 999 ? '999+' : $msgCount }}</span>
                @endif
            </a>
        </div>

        {{-- ===================== TAB ZALO ===================== --}}
        @if($tab === 'zalo')
        <div class="p-6 space-y-6">
            {{-- Thông báo ra mắt dịch vụ tạo Bot Hoàn Tiền bằng nick Zalo cá nhân
                 Dùng khóa localStorage riêng cho trang Quản lý Bot để trạng thái ẩn/hiện
                 không phụ thuộc vào thông báo cùng nội dung ở Bảng điều khiển --}}
            @include('admin.components.zalo_bot_notice', ['storageKey' => 'notice_zalo_personal_bot_bots_v1'])

            {{-- Hướng dẫn --}}
            <div class="bg-blue-50 dark:bg-blue-900/10 border border-blue-200 dark:border-blue-800/40 rounded-xl p-4 text-xs text-blue-700 dark:text-blue-400">
                <div class="flex items-start gap-2">
                    <i data-lucide="info" class="w-4 h-4 shrink-0 mt-0.5"></i>
                    <div class="space-y-1">
                        <p class="font-bold">{{ __('Hướng dẫn cài đặt Zalo Bot') }}</p>
                        <p>1. Truy cập <a href="https://bot.zapps.me" target="_blank" class="underline font-semibold">bot.zapps.me</a> → Tạo Bot mới → Sao chép Bot Token.</p>
                        <p>2. Dán Bot Token vào ô bên dưới → Nhấn <strong>Lưu cấu hình</strong>.</p>
                        <p>3. Nhấn <strong>Đặt Webhook</strong> để kết nối Bot với hệ thống (cần domain HTTPS).</p>
                        <p>4. Bật trạng thái <strong>Kích hoạt Bot</strong> để nhận tin nhắn.</p>
                        <p class="mt-1 text-blue-600 dark:text-blue-500">📌 URL Webhook: <code class="bg-blue-100 dark:bg-blue-900/30 px-1 rounded">{{ route('webhook.zalo_bot') }}</code></p>
                    </div>
                </div>
            </div>

            <form action="{{ route('admin.bots.zalo.save') }}" method="POST" class="space-y-6">
                @csrf

                {{-- Kích hoạt --}}
                <div class="bg-gray-50/50 dark:bg-slate-900/50 rounded-2xl p-6 border border-gray-150 dark:border-slate-800">
                    <h3 class="text-xs font-bold text-gray-700 dark:text-slate-300 uppercase tracking-wider pb-3 border-b border-gray-200/65 dark:border-slate-800 flex items-center gap-1.5 mb-4">
                        <i data-lucide="toggle-right" class="w-4 h-4 text-shopee"></i>
                        {{ __('Trạng thái Bot') }}
                    </h3>
                    <label class="flex items-center gap-3 cursor-pointer select-none">
                        <div class="relative">
                            <input type="checkbox" name="is_enabled" value="1" {{ $zaloConfig->is_enabled ? 'checked' : '' }} class="sr-only peer">
                            <div class="w-11 h-6 bg-gray-200 dark:bg-slate-700 rounded-full peer peer-checked:bg-shopee transition-colors"></div>
                            <div class="absolute left-0.5 top-0.5 w-5 h-5 bg-white rounded-full shadow transition-transform peer-checked:translate-x-5"></div>
                        </div>
                        <span class="text-sm font-semibold text-gray-700 dark:text-slate-300">{{ __('Kích hoạt Zalo Bot') }}</span>
                    </label>
                </div>

                {{-- Token & Username --}}
                <div class="bg-gray-50/50 dark:bg-slate-900/50 rounded-2xl p-6 border border-gray-150 dark:border-slate-800 space-y-4">
                    <h3 class="text-xs font-bold text-gray-700 dark:text-slate-300 uppercase tracking-wider pb-3 border-b border-gray-200/65 dark:border-slate-800 flex items-center gap-1.5">
                        <i data-lucide="key-round" class="w-4 h-4 text-shopee"></i>
                        {{ __('Thông tin xác thực') }}
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 dark:text-slate-300 mb-1.5">{{ __('Bot Token') }} <span class="text-red-500">*</span></label>
                            <input type="text" name="bot_token" value="{{ old('bot_token', $zaloConfig->bot_token) }}" placeholder="Paste Bot Token từ bot.zapps.me..."
                                class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-800 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-300 font-mono">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 dark:text-slate-300 mb-1.5">{{ __('Liên kết Zalo Bot (hoặc ID Bot)') }}</label>
                            <input type="text" name="bot_username" value="{{ old('bot_username', $zaloConfig->bot_username) }}" placeholder="Ví dụ: https://zalo.me/s/123456789... hoặc ID Bot"
                                class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-800 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-300">
                            <span class="text-[10px] text-gray-400 dark:text-slate-500 mt-1 block">{{ __('Nhập liên kết truy cập Zalo Bot của bạn (ví dụ: https://zalo.me/s/12345...).') }}</span>
                        </div>
                    </div>
                    {{-- Nút test kết nối --}}
                    <div class="flex items-center gap-2 pt-1">
                        <button type="button" id="btn-test-zalo"
                            class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-xl transition-all">
                            <i data-lucide="zap" class="w-3.5 h-3.5"></i>
                            {{ __('Kiểm tra kết nối') }}
                        </button>
                        <button type="button" id="btn-set-webhook-zalo"
                            class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-semibold text-white bg-purple-600 hover:bg-purple-700 rounded-xl transition-all">
                            <i data-lucide="link-2" class="w-3.5 h-3.5"></i>
                            {{ __('Đặt Webhook') }}
                        </button>
                        <span id="zalo-test-result" class="text-xs font-semibold hidden"></span>
                    </div>
                </div>

                {{-- QR Code Zalo Bot --}}
                <div class="bg-gray-50/50 dark:bg-slate-900/50 rounded-2xl p-6 border border-gray-150 dark:border-slate-800 space-y-4"
                     x-data="{
                         qrPreview: '{{ $zaloConfig->extra['qr_code'] ?? '' }}',
                         qrCodeUrl: '{{ $zaloConfig->extra['qr_code'] ?? '' }}'
                     }">
                    <h3 class="text-xs font-bold text-gray-700 dark:text-slate-300 uppercase tracking-wider pb-3 border-b border-gray-200/65 dark:border-slate-800 flex items-center gap-1.5">
                        <i data-lucide="qr-code" class="w-4 h-4 text-shopee"></i>
                        {{ __('Mã QR Code Zalo Bot') }}
                    </h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 items-start">
                        <!-- Preview QR -->
                        <div class="md:col-span-1 flex flex-col items-center justify-center p-3 bg-white dark:bg-slate-850 rounded-2xl border border-gray-150 dark:border-slate-800 aspect-square w-32 h-32 mx-auto md:mx-0 overflow-hidden relative">
                            <template x-if="qrPreview">
                                <div class="w-full h-full relative group">
                                    <img :src="qrPreview" class="w-full h-full object-contain">
                                    <!-- Nút xoá preview đã chọn -->
                                    <button type="button" @click="qrPreview = ''; qrCodeUrl = ''" class="absolute top-1 right-1 bg-red-500 hover:bg-red-600 text-white rounded-full p-1 shadow-md opacity-0 group-hover:opacity-100 transition-opacity">
                                        <i data-lucide="x" class="w-3 h-3 pointer-events-none"></i>
                                    </button>
                                </div>
                            </template>
                            <template x-if="!qrPreview">
                                <div class="text-center text-gray-400">
                                    <i data-lucide="image" class="w-6 h-6 mx-auto mb-1 text-gray-300 dark:text-slate-650"></i>
                                    <span class="text-[10px]">{{ __('Không có QR') }}</span>
                                </div>
                            </template>
                        </div>
                        
                        <!-- Upload/Select URL -->
                        <div class="md:col-span-3 space-y-3">
                            <label class="block text-xs font-semibold text-gray-700 dark:text-slate-300">{{ __('Đường dẫn ảnh mã QR (URL)') }}</label>
                            <div class="flex gap-2">
                                <input type="text"
                                       name="qr_code"
                                       id="zalo-qr-code"
                                       x-model="qrCodeUrl"
                                       @input="qrPreview = qrCodeUrl"
                                       placeholder="{{ __('Nhập URL mã QR hoặc chọn từ thư viện...') }}"
                                       class="flex-1 px-4 py-2.5 border border-gray-200 dark:border-slate-800 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-300">
                                <!-- Nút chọn ảnh từ thư viện elFinder -->
                                <button type="button"
                                        @click="openElfinderPopup('zalo-qr-code')"
                                        class="shrink-0 flex items-center gap-1.5 px-4 py-2.5 text-xs font-bold text-shopee bg-shopee/10 border border-shopee/20 hover:bg-shopee/20 rounded-xl transition-all"
                                        title="{{ __('Chọn ảnh từ thư viện') }}">
                                    <i data-lucide="folder-open" class="w-3.5 h-3.5 pointer-events-none"></i>
                                    {{ __('Thư viện') }}
                                </button>
                            </div>
                            <span class="text-[10px] text-gray-400 dark:text-slate-500 block">{{ __('Tải lên hoặc chọn hình ảnh QR Code của Zalo Bot. User có thể quét mã QR này trực tiếp trên trang hướng dẫn để vào chat với bot.') }}</span>
                        </div>
                    </div>
                </div>

                {{-- Mẫu tin nhắn --}}
                <div class="bg-gray-50/50 dark:bg-slate-900/50 rounded-2xl p-6 border border-gray-150 dark:border-slate-800 space-y-4">
                    <h3 class="text-xs font-bold text-gray-700 dark:text-slate-300 uppercase tracking-wider pb-3 border-b border-gray-200/65 dark:border-slate-800 flex items-center gap-1.5">
                        <i data-lucide="message-circle" class="w-4 h-4 text-shopee"></i>
                        {{ __('Mẫu tin nhắn Bot') }}
                    </h3>
                    <p class="text-xs text-gray-500 dark:text-slate-500">{{ __('Biến hỗ trợ:') }} <code class="bg-gray-100 dark:bg-slate-800 px-1 rounded">{product_name}</code> <code class="bg-gray-100 dark:bg-slate-800 px-1 rounded">{cashback_amount}</code> <code class="bg-gray-100 dark:bg-slate-800 px-1 rounded">{cashback_link}</code></p>
                    <div class="space-y-3">
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 dark:text-slate-300 mb-1.5">{{ __('Tin nhắn chào mừng') }}</label>
                            <textarea name="welcome_message" rows="2" class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-800 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-300 resize-none">{{ old('welcome_message', $zaloConfig->welcome_message) }}</textarea>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 dark:text-slate-300 mb-1.5">{{ __('Mẫu trả lời link hoàn tiền') }}</label>
                            <textarea name="cashback_template" rows="4" class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-800 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-300 resize-none font-mono">{{ old('cashback_template', $zaloConfig->cashback_template) }}</textarea>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 dark:text-slate-300 mb-1.5">{{ __('Không tìm thấy sản phẩm') }}</label>
                                <textarea name="not_found_message" rows="2" class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-800 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-300 resize-none">{{ old('not_found_message', $zaloConfig->not_found_message) }}</textarea>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 dark:text-slate-300 mb-1.5">{{ __('Hướng dẫn liên kết tài khoản') }}</label>
                                <textarea name="login_required_message" rows="6" class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-800 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-300">{{ old('login_required_message', $zaloConfig->login_required_message) }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end">
                    <button type="submit" class="inline-flex items-center gap-1.5 px-6 py-3 text-xs font-semibold text-white bg-shopee hover:bg-shopee-dark rounded-xl transition-all shadow-md active:scale-95">
                        <i data-lucide="save" class="w-4 h-4"></i>
                        {{ __('Lưu cấu hình Zalo Bot') }}
                    </button>
                </div>
            </form>
        </div>
        @endif

        {{-- ===================== TAB TELEGRAM ===================== --}}
        @if($tab === 'telegram')
        <div class="p-6 space-y-6">
            {{-- Hướng dẫn --}}
            <div class="bg-blue-50 dark:bg-blue-900/10 border border-blue-200 dark:border-blue-800/40 rounded-xl p-4 text-xs text-blue-700 dark:text-blue-400">
                <div class="flex items-start gap-2">
                    <i data-lucide="info" class="w-4 h-4 shrink-0 mt-0.5"></i>
                    <div class="space-y-1">
                        <p class="font-bold">{{ __('Hướng dẫn cài đặt Telegram Bot') }}</p>
                        <p>1. Nhắn tin cho <a href="https://t.me/BotFather" target="_blank" class="underline font-semibold">@BotFather</a> trên Telegram → <code>/newbot</code> → Sao chép HTTP API Token.</p>
                        <p>2. Dán Bot Token vào ô bên dưới → Nhấn <strong>Lưu cấu hình</strong>.</p>
                        <p>3. Nhấn <strong>Đặt Webhook</strong> để kết nối Bot với hệ thống (cần domain HTTPS).</p>
                        <p>4. Bật trạng thái <strong>Kích hoạt Bot</strong> để nhận tin nhắn.</p>
                        <p class="mt-1 text-blue-600 dark:text-blue-500">📌 URL Webhook: <code class="bg-blue-100 dark:bg-blue-900/30 px-1 rounded">{{ route('webhook.telegram_bot') }}</code></p>
                    </div>
                </div>
            </div>

            <form action="{{ route('admin.bots.telegram.save') }}" method="POST" class="space-y-6">
                @csrf

                {{-- Kích hoạt --}}
                <div class="bg-gray-50/50 dark:bg-slate-900/50 rounded-2xl p-6 border border-gray-150 dark:border-slate-800">
                    <h3 class="text-xs font-bold text-gray-700 dark:text-slate-300 uppercase tracking-wider pb-3 border-b border-gray-200/65 dark:border-slate-800 flex items-center gap-1.5 mb-4">
                        <i data-lucide="toggle-right" class="w-4 h-4 text-shopee"></i>
                        {{ __('Trạng thái Bot') }}
                    </h3>
                    <label class="flex items-center gap-3 cursor-pointer select-none">
                        <div class="relative">
                            <input type="checkbox" name="is_enabled" value="1" {{ $telegramConfig->is_enabled ? 'checked' : '' }} class="sr-only peer">
                            <div class="w-11 h-6 bg-gray-200 dark:bg-slate-700 rounded-full peer peer-checked:bg-shopee transition-colors"></div>
                            <div class="absolute left-0.5 top-0.5 w-5 h-5 bg-white rounded-full shadow transition-transform peer-checked:translate-x-5"></div>
                        </div>
                        <span class="text-sm font-semibold text-gray-700 dark:text-slate-300">{{ __('Kích hoạt Telegram Bot') }}</span>
                    </label>
                </div>

                {{-- Token & Username --}}
                <div class="bg-gray-50/50 dark:bg-slate-900/50 rounded-2xl p-6 border border-gray-150 dark:border-slate-800 space-y-4">
                    <h3 class="text-xs font-bold text-gray-700 dark:text-slate-300 uppercase tracking-wider pb-3 border-b border-gray-200/65 dark:border-slate-800 flex items-center gap-1.5">
                        <i data-lucide="key-round" class="w-4 h-4 text-shopee"></i>
                        {{ __('Thông tin xác thực') }}
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 dark:text-slate-300 mb-1.5">{{ __('Bot Token (từ BotFather)') }} <span class="text-red-500">*</span></label>
                            <input type="text" name="bot_token" value="{{ old('bot_token', $telegramConfig->bot_token) }}" placeholder="123456789:ABCdefGhIjKlmNoPQRsTUVwxyZ..."
                                class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-800 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-300 font-mono">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 dark:text-slate-300 mb-1.5">{{ __('Username Bot') }}</label>
                            <input type="text" name="bot_username" value="{{ old('bot_username', $telegramConfig->bot_username) }}" placeholder="@MyShopeeBot"
                                class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-800 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-300">
                        </div>
                    </div>
                    {{-- Nút test kết nối --}}
                    <div class="flex items-center gap-2 pt-1">
                        <button type="button" id="btn-test-telegram"
                            class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-xl transition-all">
                            <i data-lucide="zap" class="w-3.5 h-3.5"></i>
                            {{ __('Kiểm tra kết nối') }}
                        </button>
                        <button type="button" id="btn-set-webhook-telegram"
                            class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-semibold text-white bg-purple-600 hover:bg-purple-700 rounded-xl transition-all">
                            <i data-lucide="link-2" class="w-3.5 h-3.5"></i>
                            {{ __('Đặt Webhook') }}
                        </button>
                        <span id="telegram-test-result" class="text-xs font-semibold hidden"></span>
                    </div>
                </div>

                {{-- Mẫu tin nhắn --}}
                <div class="bg-gray-50/50 dark:bg-slate-900/50 rounded-2xl p-6 border border-gray-150 dark:border-slate-800 space-y-4">
                    <h3 class="text-xs font-bold text-gray-700 dark:text-slate-300 uppercase tracking-wider pb-3 border-b border-gray-200/65 dark:border-slate-800 flex items-center gap-1.5">
                        <i data-lucide="message-circle" class="w-4 h-4 text-shopee"></i>
                        {{ __('Mẫu tin nhắn Bot') }}
                    </h3>
                    <p class="text-xs text-gray-500 dark:text-slate-500">{{ __('Biến hỗ trợ:') }} <code class="bg-gray-100 dark:bg-slate-800 px-1 rounded">{product_name}</code> <code class="bg-gray-100 dark:bg-slate-800 px-1 rounded">{cashback_amount}</code> <code class="bg-gray-100 dark:bg-slate-800 px-1 rounded">{cashback_link}</code> — Hỗ trợ *bold* và _italic_ (Markdown Telegram)</p>
                    <div class="space-y-3">
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 dark:text-slate-300 mb-1.5">{{ __('Tin nhắn chào mừng') }}</label>
                            <textarea name="welcome_message" rows="2" class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-800 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-300 resize-none">{{ old('welcome_message', $telegramConfig->welcome_message) }}</textarea>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 dark:text-slate-300 mb-1.5">{{ __('Mẫu trả lời link hoàn tiền') }}</label>
                            <textarea name="cashback_template" rows="5" class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-800 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-300 resize-none font-mono">{{ old('cashback_template', $telegramConfig->cashback_template) }}</textarea>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 dark:text-slate-300 mb-1.5">{{ __('Không tìm thấy sản phẩm') }}</label>
                                <textarea name="not_found_message" rows="2" class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-800 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-300 resize-none">{{ old('not_found_message', $telegramConfig->not_found_message) }}</textarea>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 dark:text-slate-300 mb-1.5">{{ __('Hướng dẫn liên kết tài khoản') }}</label>
                                <textarea name="login_required_message" rows="6" class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-800 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-300">{{ old('login_required_message', $telegramConfig->login_required_message) }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end">
                    <button type="submit" class="inline-flex items-center gap-1.5 px-6 py-3 text-xs font-semibold text-white bg-shopee hover:bg-shopee-dark rounded-xl transition-all shadow-md active:scale-95">
                        <i data-lucide="save" class="w-4 h-4"></i>
                        {{ __('Lưu cấu hình Telegram Bot') }}
                    </button>
                </div>
            </form>
        </div>
        @endif

        {{-- ===================== TAB MESSAGES ===================== --}}
        @if($tab === 'messages')
        <div class="p-6 space-y-4">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center gap-2">
                    {{-- Lọc theo bot --}}
                    <a href="{{ route('admin.bots.index', ['tab' => 'messages', 'bot' => '']) }}"
                        class="px-3 py-1.5 text-xs font-semibold rounded-xl transition-all {{ $botTypeForMessages === '' ? 'bg-shopee text-white' : 'bg-gray-100 dark:bg-slate-800 text-gray-600 dark:text-slate-400 hover:bg-gray-200 dark:hover:bg-slate-700' }}">
                        {{ __('Tất cả') }}
                    </a>
                    <a href="{{ route('admin.bots.index', ['tab' => 'messages', 'bot' => 'zalo']) }}"
                        class="px-3 py-1.5 text-xs font-semibold rounded-xl transition-all {{ $botTypeForMessages === 'zalo' ? 'bg-shopee text-white' : 'bg-gray-100 dark:bg-slate-800 text-gray-600 dark:text-slate-400 hover:bg-gray-200 dark:hover:bg-slate-700' }}">
                        Zalo Bot
                    </a>
                    <a href="{{ route('admin.bots.index', ['tab' => 'messages', 'bot' => 'telegram']) }}"
                        class="px-3 py-1.5 text-xs font-semibold rounded-xl transition-all {{ $botTypeForMessages === 'telegram' ? 'bg-shopee text-white' : 'bg-gray-100 dark:bg-slate-800 text-gray-600 dark:text-slate-400 hover:bg-gray-200 dark:hover:bg-slate-700' }}">
                        Telegram Bot
                    </a>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" id="btn-clear-zalo"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-red-600 dark:text-red-400 border border-red-200 dark:border-red-800/40 rounded-xl hover:bg-red-50 dark:hover:bg-red-900/20 transition-all">
                        <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                        Xoá Zalo
                    </button>
                    <button type="button" id="btn-clear-telegram"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-red-600 dark:text-red-400 border border-red-200 dark:border-red-800/40 rounded-xl hover:bg-red-50 dark:hover:bg-red-900/20 transition-all">
                        <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                        Xoá Telegram
                    </button>
                </div>
            </div>

            {{-- Desktop Table --}}
            <div class="hidden md:block">
                <table class="w-full text-xs">
                    <thead>
                        <tr class="border-b border-gray-200 dark:border-slate-800">
                            <th class="text-left py-2.5 px-3 font-semibold text-gray-500 dark:text-slate-400 w-28">{{ __('Thời gian') }}</th>
                            <th class="text-left py-2.5 px-3 font-semibold text-gray-500 dark:text-slate-400 w-20">{{ __('Bot') }}</th>
                            <th class="text-left py-2.5 px-3 font-semibold text-gray-500 dark:text-slate-400 w-24">{{ __('Chat ID') }}</th>
                            <th class="text-left py-2.5 px-3 font-semibold text-gray-500 dark:text-slate-400 w-28">{{ __('Người dùng') }}</th>
                            <th class="text-left py-2.5 px-3 font-semibold text-gray-500 dark:text-slate-400 w-16">{{ __('Chiều') }}</th>
                            <th class="text-left py-2.5 px-3 font-semibold text-gray-500 dark:text-slate-400">{{ __('Nội dung') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-slate-800/60">
                        @forelse($messages as $msg)
                        <tr class="hover:bg-gray-50/50 dark:hover:bg-slate-800/20 transition-colors">
                            <td class="py-3 px-3 text-gray-500 dark:text-slate-500 whitespace-nowrap">{{ $msg->created_at->format('d/m H:i') }}</td>
                            <td class="py-3 px-3">
                                @if($msg->bot_type === 'zalo')
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-400">Zalo</span>
                                @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-sky-50 dark:bg-sky-900/20 text-sky-700 dark:text-sky-400">Telegram</span>
                                @endif
                            </td>
                            <td class="py-3 px-3 text-gray-500 dark:text-slate-400 font-mono">{{ Str::limit($msg->chat_id, 14) }}</td>
                            <td class="py-3 px-3 text-gray-700 dark:text-slate-300">{{ $msg->user_name ? Str::limit($msg->user_name, 18) : '—' }}</td>
                            <td class="py-3 px-3">
                                @if($msg->direction === 'inbound')
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400">
                                    <i data-lucide="arrow-down-left" class="w-2.5 h-2.5"></i> Nhận
                                </span>
                                @else
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-orange-50 dark:bg-orange-900/20 text-orange-700 dark:text-orange-400">
                                    <i data-lucide="arrow-up-right" class="w-2.5 h-2.5"></i> Gửi
                                </span>
                                @endif
                            </td>
                            <td class="py-3 px-3 text-gray-700 dark:text-slate-300 max-w-xs">
                                <span title="{{ $msg->content }}">{{ Str::limit($msg->content, 80) }}</span>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="py-12 text-center text-gray-400 dark:text-slate-600">
                                <i data-lucide="message-square-off" class="w-10 h-10 mx-auto mb-2 opacity-40"></i>
                                <p class="text-sm font-semibold">{{ __('Chưa có tin nhắn nào') }}</p>
                                <p class="text-xs mt-1">{{ __('Tin nhắn sẽ xuất hiện khi user gửi link cho Bot') }}</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Mobile Cards --}}
            <div class="block md:hidden space-y-3">
                @forelse($messages as $msg)
                <div class="bg-white dark:bg-slate-800/50 rounded-2xl border border-gray-100 dark:border-slate-800 p-4 space-y-2">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            @if($msg->bot_type === 'zalo')
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-400">Zalo</span>
                            @else
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-sky-50 dark:bg-sky-900/20 text-sky-700 dark:text-sky-400">Telegram</span>
                            @endif
                            @if($msg->direction === 'inbound')
                            <span class="text-[10px] font-bold text-green-600 dark:text-green-400">↙ Nhận</span>
                            @else
                            <span class="text-[10px] font-bold text-orange-600 dark:text-orange-400">↗ Gửi</span>
                            @endif
                        </div>
                        <span class="text-[10px] text-gray-400 dark:text-slate-500">{{ $msg->created_at->format('d/m H:i') }}</span>
                    </div>
                    <div class="flex items-center gap-1.5 text-[11px]">
                        <span class="text-gray-400">Chat:</span>
                        <span class="font-mono text-gray-600 dark:text-slate-400">{{ $msg->chat_id }}</span>
                        @if($msg->user_name)
                        <span class="text-gray-400">·</span>
                        <span class="text-gray-700 dark:text-slate-300">{{ $msg->user_name }}</span>
                        @endif
                    </div>
                    <p class="text-xs text-gray-700 dark:text-slate-300 leading-relaxed">{{ Str::limit($msg->content, 120) }}</p>
                </div>
                @empty
                <div class="py-12 text-center text-gray-400 dark:text-slate-600">
                    <i data-lucide="message-square-off" class="w-10 h-10 mx-auto mb-2 opacity-40"></i>
                    <p class="text-sm font-semibold">{{ __('Chưa có tin nhắn nào') }}</p>
                </div>
                @endforelse
            </div>

            {{-- Pagination --}}
            @if($messages->hasPages())
            <div class="mt-4">
                {{ $messages->links() }}
            </div>
            @endif
        </div>
        @endif
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

    // ====== Zalo Bot ======
    const btnTestZalo = document.getElementById('btn-test-zalo');
    const btnWebhookZalo = document.getElementById('btn-set-webhook-zalo');
    const zaloResult = document.getElementById('zalo-test-result');

    function showResult(el, msg, ok) {
        if (!el) return;
        el.textContent = msg;
        el.className = 'text-xs font-semibold ' + (ok ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400');
        el.classList.remove('hidden');
        setTimeout(() => el.classList.add('hidden'), 8000);
    }

    if (btnTestZalo) {
        btnTestZalo.addEventListener('click', async () => {
            btnTestZalo.disabled = true;
            btnTestZalo.textContent = 'Đang kiểm tra...';
            try {
                const res = await axios.post('{{ route('admin.bots.zalo.test') }}', {}, { headers: { 'X-CSRF-TOKEN': csrfToken } });
                showResult(zaloResult, res.data.message, res.data.success);
            } catch (e) {
                showResult(zaloResult, e.response?.data?.message || 'Lỗi kết nối.', false);
            } finally {
                btnTestZalo.disabled = false;
                btnTestZalo.innerHTML = '<i data-lucide="zap" class="w-3.5 h-3.5"></i> Kiểm tra kết nối';
                lucide.createIcons();
            }
        });
    }

    if (btnWebhookZalo) {
        btnWebhookZalo.addEventListener('click', async () => {
            btnWebhookZalo.disabled = true;
            btnWebhookZalo.textContent = 'Đang đặt webhook...';
            try {
                const res = await axios.post('{{ route('admin.bots.zalo.set_webhook') }}', {}, { headers: { 'X-CSRF-TOKEN': csrfToken } });
                showResult(zaloResult, res.data.message + (res.data.url ? ' → ' + res.data.url : ''), res.data.success);
            } catch (e) {
                showResult(zaloResult, e.response?.data?.message || 'Lỗi kết nối.', false);
            } finally {
                btnWebhookZalo.disabled = false;
                btnWebhookZalo.innerHTML = '<i data-lucide="link-2" class="w-3.5 h-3.5"></i> Đặt Webhook';
                lucide.createIcons();
            }
        });
    }

    // ====== Telegram Bot ======
    const btnTestTg = document.getElementById('btn-test-telegram');
    const btnWebhookTg = document.getElementById('btn-set-webhook-telegram');
    const tgResult = document.getElementById('telegram-test-result');

    if (btnTestTg) {
        btnTestTg.addEventListener('click', async () => {
            btnTestTg.disabled = true;
            btnTestTg.textContent = 'Đang kiểm tra...';
            try {
                const res = await axios.post('{{ route('admin.bots.telegram.test') }}', {}, { headers: { 'X-CSRF-TOKEN': csrfToken } });
                showResult(tgResult, res.data.message, res.data.success);
            } catch (e) {
                showResult(tgResult, e.response?.data?.message || 'Lỗi kết nối.', false);
            } finally {
                btnTestTg.disabled = false;
                btnTestTg.innerHTML = '<i data-lucide="zap" class="w-3.5 h-3.5"></i> Kiểm tra kết nối';
                lucide.createIcons();
            }
        });
    }

    if (btnWebhookTg) {
        btnWebhookTg.addEventListener('click', async () => {
            btnWebhookTg.disabled = true;
            btnWebhookTg.textContent = 'Đang đặt webhook...';
            try {
                const res = await axios.post('{{ route('admin.bots.telegram.set_webhook') }}', {}, { headers: { 'X-CSRF-TOKEN': csrfToken } });
                showResult(tgResult, res.data.message + (res.data.url ? ' → ' + res.data.url : ''), res.data.success);
            } catch (e) {
                showResult(tgResult, e.response?.data?.message || 'Lỗi kết nối.', false);
            } finally {
                btnWebhookTg.disabled = false;
                btnWebhookTg.innerHTML = '<i data-lucide="link-2" class="w-3.5 h-3.5"></i> Đặt Webhook';
                lucide.createIcons();
            }
        });
    }

    // ====== Clear messages ======
    async function clearMessages(botType) {
        if (!confirm('Xoá toàn bộ lịch sử tin nhắn ' + botType + '? Hành động không thể hoàn tác!')) return;
        try {
            const res = await axios.post('{{ route('admin.bots.clear_messages') }}', { bot_type: botType }, { headers: { 'X-CSRF-TOKEN': csrfToken } });
            if (res.data.success) {
                window.dispatchEvent(new CustomEvent('toast', { detail: { text: res.data.message, type: 'success' } }));
                setTimeout(() => location.reload(), 1000);
            } else {
                window.dispatchEvent(new CustomEvent('toast', { detail: { text: res.data.message, type: 'error' } }));
            }
        } catch (e) {
            window.dispatchEvent(new CustomEvent('toast', { detail: { text: 'Có lỗi xảy ra.', type: 'error' } }));
        }
    }

    document.getElementById('btn-clear-zalo')?.addEventListener('click', () => clearMessages('zalo'));
    document.getElementById('btn-clear-telegram')?.addEventListener('click', () => clearMessages('telegram'));
});

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
    var inputElement = document.getElementById(inputId);
    if (inputElement) {
        inputElement.value = fileUrl;
        // Gửi sự kiện input để AlpineJS đồng bộ dữ liệu vào biến x-model tương ứng
        inputElement.dispatchEvent(new Event('input'));
    }
};
</script>
@endsection
