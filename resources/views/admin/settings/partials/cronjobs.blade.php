{{-- 
    Partial View: Cron Jobs
    Vai trò: Hướng dẫn cấu hình Laravel Task Scheduler (Artisan schedule:run) và cấu hình giới hạn gửi email của hàng đợi.
--}}
@php
    // Ghi nhận thời điểm Scheduler chạy gần nhất để kiểm tra trạng thái hoạt động toàn cục
    $scheduleLastRun = \App\Models\Setting::getVal('schedule_last_run');
    $isCronRunning = $scheduleLastRun && (time() - strtotime($scheduleLastRun)) < 300; // Hoạt động nếu chạy trong 5 phút qua

    // Lấy thời điểm chạy gần nhất của từng tác vụ cụ thể để hiển thị thông tin trực quan cho quản trị viên
    $emailLastRun = \App\Models\Setting::getVal('cron_last_run_email_queue');
    $isEmailCronRunning = $emailLastRun && (time() - strtotime($emailLastRun)) < 300;

    $telegramLastRun = \App\Models\Setting::getVal('cron_last_run_telegram_queue');
    $isTelegramCronRunning = $telegramLastRun && (time() - strtotime($telegramLastRun)) < 300;

    $shopeeLastRun = \App\Models\Setting::getVal('cron_last_run_shopee_sync');
    $isShopeeCronRunning = $shopeeLastRun && (time() - strtotime($shopeeLastRun)) < 23400; // Chạy mỗi 6 tiếng, cảnh báo nếu quá 6 tiếng 30 phút chưa chạy

    $tiktokLastRun = \App\Models\Setting::getVal('cron_last_run_tiktok_sync');
    $isTiktokCronRunning = $tiktokLastRun && (time() - strtotime($tiktokLastRun)) < 2400; // Chạy mỗi 30 phút, cảnh báo nếu quá 40 phút chưa chạy

    $lazadaLastRun = \App\Models\Setting::getVal('cron_last_run_lazada_sync');
    $isLazadaCronRunning = $lazadaLastRun && (time() - strtotime($lazadaLastRun)) < 2400; // Chạy mỗi 30 phút, cảnh báo nếu quá 40 phút chưa chạy

    $maintenanceLastRun = \App\Models\Setting::getVal('cron_last_run_system_maintenance');
    $isMaintenanceCronRunning = $maintenanceLastRun && (time() - strtotime($maintenanceLastRun)) < 46800; // Chạy mỗi 12 tiếng, cảnh báo nếu quá 13 tiếng chưa chạy

    // Lấy thời điểm sitemap.xml được cập nhật gần nhất bằng cách đọc thời gian sửa đổi file vật lý
    $sitemapLastRun = file_exists(public_path('sitemap.xml')) ? date('Y-m-d H:i:s', filemtime(public_path('sitemap.xml'))) : null;
    $isSitemapCronRunning = $sitemapLastRun && (time() - filemtime(public_path('sitemap.xml'))) < 86400 * 2; // chạy định kỳ trong 2 ngày qua

    // Lấy thời điểm đồng bộ mã giảm giá gần nhất
    $couponsLastRun = \App\Models\Setting::getVal('cron_last_run_coupons_sync');
    $isCouponsCronRunning = $couponsLastRun && (time() - strtotime($couponsLastRun)) < 7200; // Chạy mỗi 1 tiếng, cảnh báo nếu quá 2 tiếng

    // Lấy thời điểm tự động cập nhật hệ thống gần nhất
    $autoUpdateLastRun = \App\Models\Setting::getVal('cron_last_run_system_auto_update');
    $isAutoUpdateCronRunning = $autoUpdateLastRun && (time() - strtotime($autoUpdateLastRun)) < 3600; // Chạy mỗi 30 phút, cảnh báo nếu quá 1 tiếng

    // Lấy 2 ký tự đầu phiên bản PHP (ví dụ: 8.3 -> 83) để hiển thị đường dẫn PHP CLI của aaPanel
    $phpMajorMinor = str_replace('.', '', substr(PHP_VERSION, 0, 3));
@endphp

<div x-show="tab === 'cronjobs'" 
     x-data="{ 
         runningTask: '', 
         activeHosting: 'cpanel',
         cronKey: '{{ \App\Models\Setting::getVal('cron_secret_key') }}',
         regenerateKey() {
             const chars = '0123456789abcdef';
             let key = '';
             for (let i = 0; i < 32; i++) {
                 key += chars[Math.floor(Math.random() * 16)];
             }
             this.cronKey = key;
             this.$dispatch('toast', { text: '{{ __('Đã tạo khóa mới! Vui lòng bấm Lưu cài đặt ở góc dưới để áp dụng.') }}', type: 'info' });
         },
         async runTask(taskName) {
             if (this.runningTask) return;
             this.runningTask = taskName;
             try {
                 // Gọi AJAX đến route run_cron để thực thi nóng tác vụ mà không cần chờ đến giờ của cron job hệ thống
                 const response = await axios.post('{{ route('admin.settings.run_cron') }}', {
                     task: taskName
                 });
                 this.$dispatch('toast', { text: response.data.message || '{{ __('Thực thi tác vụ thành công!') }}', type: 'success' });
                 
                 // Đợi một khoảng ngắn để quản trị viên đọc thông báo trước khi tải lại trang
                 setTimeout(() => {
                     window.location.reload();
                 }, 1500);
             } catch (error) {
                 const msg = error.response?.data?.message || '{{ __('Có lỗi xảy ra khi thực thi tác vụ.') }}';
                 this.$dispatch('toast', { text: msg, type: 'danger' });
             } finally {
                 this.runningTask = '';
             }
         }
     }"
     class="space-y-6" 
     x-transition 
     x-cloak>
    <div class="bg-gray-50/50 dark:bg-slate-900/50 rounded-2xl p-4 sm:p-6 border border-gray-150 dark:border-slate-800 space-y-6 w-full">
        <h3 class="text-xs font-bold text-gray-700 dark:text-slate-300 uppercase tracking-wider pb-2 border-b border-gray-200/65 dark:border-slate-800 flex items-center gap-1.5">
            <i data-lucide="clock" class="w-4 h-4 text-shopee"></i>
            {{ __('Cấu hình tác vụ tự động (Cron Jobs)') }}
        </h3>

        <!-- Hướng dẫn cấu hình Laravel Task Scheduler (schedule:run) -->
        <div class="bg-blue-50/50 dark:bg-blue-950/20 border border-blue-100 dark:border-blue-900 rounded-2xl p-5 space-y-4">
            <div class="flex items-start justify-between flex-wrap gap-4">
                <div class="space-y-1.5">
                    <h4 class="text-xs font-bold text-blue-900 dark:text-blue-300 uppercase tracking-wider flex items-center gap-1.5">
                        <i data-lucide="terminal" class="w-4 h-4 text-blue-600 dark:text-blue-400"></i>
                        {{ __('Cấu hình Laravel Task Scheduler') }}
                    </h4>
                    <p class="text-[11px] text-blue-700/80 dark:text-blue-400/80 max-w-2xl leading-relaxed">
                        {{ __('Hệ thống sử dụng Laravel Scheduler để tự động quản lý các tác vụ nền như xử lý hàng đợi email, bảo trì hệ thống và tự động cập nhật Sitemap. Hãy chọn loại hosting bạn đang sử dụng để xem lệnh cấu hình phù hợp.') }}
                    </p>
                </div>
                <div class="shrink-0 flex items-center gap-2">
                    <span class="text-[10px] text-gray-500 font-bold dark:text-slate-400">{{ __('Trạng thái Scheduler:') }}</span>
                    @if($isCronRunning)
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-green-50 text-green-700 dark:bg-green-950/30 dark:text-green-400 border border-green-200/50 dark:border-green-900/50 rounded-xl text-[10px] font-bold">
                            <span class="w-1.5 h-1.5 rounded-full bg-green-500 animate-pulse"></span>
                            {{ __('Đang hoạt động') }}
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-amber-50 text-amber-700 dark:bg-amber-950/30 dark:text-amber-400 border border-amber-200/50 dark:border-amber-900/50 rounded-xl text-[10px] font-bold">
                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                            {{ __('Chưa chạy / Quá hạn') }}
                        </span>
                    @endif
                </div>
            </div>

            {{-- 
                Thanh chọn loại Hosting (Tabs Switcher)
                Giúp tối ưu không gian hiển thị, quản trị viên dễ dàng chọn đúng loại hosting đang sử dụng 
                thay vì hiển thị tất cả các lệnh trên cùng một hàng gây rối mắt.
            --}}
            <div class="flex flex-wrap gap-2 p-1 bg-gray-200/50 dark:bg-slate-800/40 rounded-xl w-fit">
                <button type="button" 
                        @click="activeHosting = 'cpanel'"
                        :class="activeHosting === 'cpanel' ? 'bg-white dark:bg-slate-900 text-blue-600 dark:text-blue-400 shadow-sm' : 'text-gray-500 dark:text-slate-400 hover:text-gray-700 dark:hover:text-slate-300'"
                        class="px-4 py-1.5 rounded-lg text-[10px] font-bold transition-all flex items-center gap-1.5 cursor-pointer">
                    <span class="w-2 h-2 rounded-full bg-blue-500" x-show="activeHosting === 'cpanel'"></span>
                    <span>cPanel Hosting</span>
                </button>
                <button type="button" 
                        @click="activeHosting = 'aapanel'"
                        :class="activeHosting === 'aapanel' ? 'bg-white dark:bg-slate-900 text-green-600 dark:text-green-400 shadow-sm' : 'text-gray-500 dark:text-slate-400 hover:text-gray-700 dark:hover:text-slate-300'"
                        class="px-4 py-1.5 rounded-lg text-[10px] font-bold transition-all flex items-center gap-1.5 cursor-pointer">
                    <span class="w-2 h-2 rounded-full bg-green-500" x-show="activeHosting === 'aapanel'"></span>
                    <span>aaPanel / VPS</span>
                </button>
                <button type="button" 
                        @click="activeHosting = 'hostinger'"
                        :class="activeHosting === 'hostinger' ? 'bg-white dark:bg-slate-900 text-purple-600 dark:text-purple-400 shadow-sm' : 'text-gray-500 dark:text-slate-400 hover:text-gray-700 dark:hover:text-slate-300'"
                        class="px-4 py-1.5 rounded-lg text-[10px] font-bold transition-all flex items-center gap-1.5 cursor-pointer">
                    <span class="w-2 h-2 rounded-full bg-purple-500" x-show="activeHosting === 'hostinger'"></span>
                    <span>Hostinger</span>
                </button>
                <!-- Thêm nút chọn Webhook URL dành cho shared hosting không có proc_open -->
                <button type="button" 
                        @click="activeHosting = 'webhook'"
                        :class="activeHosting === 'webhook' ? 'bg-white dark:bg-slate-900 text-amber-600 dark:text-amber-400 shadow-sm' : 'text-gray-500 dark:text-slate-400 hover:text-gray-700 dark:hover:text-slate-300'"
                        class="px-4 py-1.5 rounded-lg text-[10px] font-bold transition-all flex items-center gap-1.5 cursor-pointer">
                    <span class="w-2 h-2 rounded-full bg-amber-500" x-show="activeHosting === 'webhook'"></span>
                    <span>Cron Webhook (URL)</span>
                </button>
            </div>

            {{-- Nội dung chi tiết cấu hình theo từng loại Hosting --}}
            <div class="mt-4 pt-4 border-t border-blue-100/50 dark:border-blue-900/30">
                <!-- Cấu hình cPanel / Hosting thường -->
                <div x-show="activeHosting === 'cpanel'" x-transition class="space-y-4">
                    <div class="space-y-2">
                        <label class="block text-[10px] font-bold text-blue-900/70 dark:text-blue-300/70 uppercase tracking-wider flex items-center gap-1.5">
                            <span class="inline-flex px-1.5 py-0.5 rounded bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300 text-[9px] font-mono">cPanel / Standard (PHP Mặc định)</span>
                            {{ __('Cách 1: Lệnh PHP mặc định') }}
                        </label>
                        <div class="flex items-stretch gap-2.5" x-data="{ cmd: 'cd {{ base_path() }} && /usr/local/bin/php artisan schedule:run >> /dev/null 2>&1' }">
                            <code class="flex-grow flex items-center px-4 py-2.5 border border-blue-100 dark:border-blue-900/50 rounded-xl text-[10px] bg-white dark:bg-slate-900 text-blue-800 dark:text-blue-300 font-mono break-all" x-text="cmd"></code>
                            <button type="button"
                                @click="
                                    navigator.clipboard.writeText(cmd);
                                    $dispatch('toast', { text: '{{ __('Đã sao chép lệnh PHP mặc định!') }}', type: 'success' });
                                "
                                class="px-4 py-2.5 bg-blue-600 hover:bg-blue-700 active:scale-95 text-white text-[10px] font-bold rounded-xl transition-all shrink-0 flex items-center justify-center gap-1.5 shadow-sm border border-blue-700 cursor-pointer"
                                title="{{ __('Sao chép lệnh') }}">
                                <i data-lucide="copy" class="w-3.5 h-3.5"></i>
                                <span>{{ __('Sao chép') }}</span>
                            </button>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <label class="block text-[10px] font-bold text-blue-900/70 dark:text-blue-300/70 uppercase tracking-wider flex items-center gap-1.5">
                            <span class="inline-flex px-1.5 py-0.5 rounded bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300 text-[9px] font-mono">cPanel / Standard (ea-php{{ $phpMajorMinor }})</span>
                            {{ __('Cách 2: Chỉ định PHP :version (Khuyên dùng)', ['version' => PHP_VERSION]) }}
                        </label>
                        <div class="flex items-stretch gap-2.5" x-data="{ cmd: 'cd {{ base_path() }} && /usr/local/bin/ea-php{{ $phpMajorMinor }} artisan schedule:run >> /dev/null 2>&1' }">
                            <code class="flex-grow flex items-center px-4 py-2.5 border border-blue-100 dark:border-blue-900/50 rounded-xl text-[10px] bg-white dark:bg-slate-900 text-blue-800 dark:text-blue-300 font-mono break-all" x-text="cmd"></code>
                            <button type="button"
                                @click="
                                    navigator.clipboard.writeText(cmd);
                                    $dispatch('toast', { text: '{{ __('Đã sao chép lệnh ea-php!') }}', type: 'success' });
                                "
                                class="px-4 py-2.5 bg-blue-600 hover:bg-blue-700 active:scale-95 text-white text-[10px] font-bold rounded-xl transition-all shrink-0 flex items-center justify-center gap-1.5 shadow-sm border border-blue-700 cursor-pointer"
                                title="{{ __('Sao chép lệnh') }}">
                                <i data-lucide="copy" class="w-3.5 h-3.5"></i>
                                <span>{{ __('Sao chép') }}</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Cấu hình aaPanel / VPS -->
                <div x-show="activeHosting === 'aapanel'" x-transition class="space-y-2">
                    <label class="block text-[10px] font-bold text-green-900/70 dark:text-green-300/70 uppercase tracking-wider flex items-center gap-1.5">
                        <span class="inline-flex px-1.5 py-0.5 rounded bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300 text-[9px] font-mono">aaPanel / VPS</span>
                        {{ __('Lệnh Cron (Task Shell Script - 1 phút / lần)') }}
                    </label>
                    <div class="flex items-stretch gap-2.5" x-data="{ cmd: 'cd {{ base_path() }} && /www/server/php/{{ $phpMajorMinor }}/bin/php artisan schedule:run >> /dev/null 2>&1' }">
                        <code class="flex-grow flex items-center px-4 py-2.5 border border-green-100 dark:border-green-900/30 rounded-xl text-[10px] bg-white dark:bg-slate-900 text-green-800 dark:text-green-300 font-mono break-all" x-text="cmd"></code>
                        <button type="button"
                            @click="
                                navigator.clipboard.writeText(cmd);
                                $dispatch('toast', { text: '{{ __('Đã sao chép lệnh aaPanel!') }}', type: 'success' });
                            "
                            class="px-4 py-2.5 bg-green-600 hover:bg-green-700 active:scale-95 text-white text-[10px] font-bold rounded-xl transition-all shrink-0 flex items-center justify-center gap-1.5 shadow-sm border border-green-700 cursor-pointer"
                            title="{{ __('Sao chép lệnh') }}">
                            <i data-lucide="copy" class="w-3.5 h-3.5"></i>
                            <span>{{ __('Sao chép') }}</span>
                        </button>
                    </div>
                </div>

                <!-- Cấu hình Hostinger -->
                <div x-show="activeHosting === 'hostinger'" x-transition class="space-y-2">
                    <label class="block text-[10px] font-bold text-purple-900/70 dark:text-purple-300/70 uppercase tracking-wider flex items-center gap-1.5">
                        <span class="inline-flex px-1.5 py-0.5 rounded bg-purple-100 text-purple-800 dark:bg-purple-900/40 dark:text-purple-300 text-[9px] font-mono">Hostinger</span>
                        {{ __('Lệnh Cron (Chạy trực tiếp 1 phút / lần)') }}
                    </label>
                    <div class="flex items-stretch gap-2.5" x-data="{ cmd: '/usr/bin/php {{ base_path() }}/artisan schedule:run >> /dev/null 2>&1' }">
                        <code class="flex-grow flex items-center px-4 py-2.5 border border-purple-100 dark:border-purple-900/30 rounded-xl text-[10px] bg-white dark:bg-slate-900 text-purple-800 dark:text-purple-300 font-mono break-all" x-text="cmd"></code>
                        <button type="button"
                            @click="
                                navigator.clipboard.writeText(cmd);
                                $dispatch('toast', { text: '{{ __('Đã sao chép lệnh Hostinger!') }}', type: 'success' });
                            "
                            class="px-4 py-2.5 bg-purple-600 hover:bg-purple-700 active:scale-95 text-white text-[10px] font-bold rounded-xl transition-all shrink-0 flex items-center justify-center gap-1.5 shadow-sm border border-purple-700 cursor-pointer"
                            title="{{ __('Sao chép lệnh') }}">
                            <i data-lucide="copy" class="w-3.5 h-3.5"></i>
                            <span>{{ __('Sao chép') }}</span>
                        </button>
                    </div>
                </div>

                <!-- Cấu hình Cron Webhook (Gọi qua URL - Dành cho Shared Hosting bị chặn proc_open) -->
                <div x-show="activeHosting === 'webhook'" x-transition class="space-y-4">
                    <div class="bg-amber-50 dark:bg-amber-950/20 border border-amber-100 dark:border-amber-900/30 rounded-xl p-3.5 text-[11px] text-amber-800 dark:text-amber-300/90 leading-relaxed">
                        <strong>⚠️ {{ __('Giải pháp thay thế cho Shared Hosting:') }}</strong> {!! __('Nếu hosting của bạn bị nhà cung cấp khóa hàm `proc_open` (dẫn đến lỗi khi chạy Artisan schedule:run), hãy sử dụng các liên kết Webhook này kết hợp với một dịch vụ gọi URL tự động (như :link) để kích hoạt các tác vụ nền định kỳ.', ['link' => '<a href="https://cronjob.vn/" target="_blank" class="text-blue-600 dark:text-blue-400 hover:underline font-bold">cronjob.vn</a>']) !!}
                    </div>

                    <!-- Trường nhập/thay đổi cron_secret_key -->
                    <div class="bg-white dark:bg-slate-900 border border-gray-150 dark:border-slate-800/80 rounded-2xl p-4 space-y-3 shadow-sm">
                        <div class="flex items-center justify-between flex-wrap gap-2">
                            <label class="block text-[10px] font-bold text-gray-700 dark:text-slate-300 uppercase tracking-wider">
                                {{ __('Khóa bảo mật Cron Webhook (cron_secret_key)') }}
                            </label>
                            <span class="text-[9px] text-gray-500 dark:text-slate-400">
                                {{ __('Thay đổi hoặc tạo mới khóa xác thực chạy tác vụ qua URL') }}
                            </span>
                        </div>
                        <div class="flex items-stretch gap-2.5">
                            <div class="relative flex-grow">
                                <input type="text" 
                                       name="cron_secret_key" 
                                       x-model="cronKey"
                                       class="w-full px-4 py-2.5 pr-10 border border-gray-205 dark:border-slate-800 rounded-xl text-[11px] bg-gray-50/50 dark:bg-slate-950 text-gray-700 dark:text-slate-350 font-mono focus:outline-none focus:border-blue-500 transition-colors" 
                                       placeholder="{{ __('Nhập khóa bảo mật hoặc tạo ngẫu nhiên') }}" />
                                <button type="button"
                                        @click="cronKey = ''"
                                        class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-650 cursor-pointer"
                                        title="{{ __('Xóa trống') }}">
                                    <i data-lucide="x" class="w-3.5 h-3.5"></i>
                                </button>
                            </div>
                            <button type="button"
                                    @click="regenerateKey()"
                                    class="px-4 py-2.5 bg-gray-100 hover:bg-gray-200 dark:bg-slate-800 dark:hover:bg-slate-700 active:scale-95 text-gray-750 dark:text-slate-300 text-[10px] font-bold rounded-xl transition-all shrink-0 flex items-center justify-center gap-1.5 border border-gray-200 dark:border-slate-750 cursor-pointer"
                                    title="{{ __('Tạo ngẫu nhiên khóa mới') }}">
                                <i data-lucide="refresh-cw" class="w-3.5 h-3.5"></i>
                                <span>{{ __('Tạo ngẫu nhiên') }}</span>
                            </button>
                        </div>
                    </div>

                    <div class="space-y-3">
                        <div class="space-y-1.5">
                            <label class="block text-[10px] font-bold text-gray-650 dark:text-slate-400 uppercase tracking-wider">
                                {{ __('1. Xử lý hàng đợi gửi Email (Chạy mỗi 1 phút)') }}
                            </label>
                            <div class="flex items-stretch gap-2.5" x-data="{ get url() { return '{{ url('/cron/process-email-queue') }}?key=' + cronKey } }">
                                <code class="flex-grow flex items-center px-4 py-2.5 border border-gray-200 dark:border-slate-800 rounded-xl text-[10px] bg-white dark:bg-slate-900 text-gray-700 dark:text-slate-300 font-mono break-all" x-text="url"></code>
                                <button type="button"
                                    @click="
                                        navigator.clipboard.writeText(url);
                                        $dispatch('toast', { text: '{{ __('Đã sao chép link Cron Email!') }}', type: 'success' });
                                    "
                                    class="px-4 py-2.5 bg-blue-600 hover:bg-blue-700 active:scale-95 text-white text-[10px] font-bold rounded-xl transition-all shrink-0 flex items-center justify-center gap-1.5 shadow-sm border border-blue-700 cursor-pointer"
                                    title="{{ __('Sao chép') }}">
                                    <i data-lucide="copy" class="w-3.5 h-3.5"></i>
                                    <span>{{ __('Sao chép') }}</span>
                                </button>
                            </div>
                        </div>

                        <div class="space-y-1.5">
                            <label class="block text-[10px] font-bold text-gray-650 dark:text-slate-400 uppercase tracking-wider">
                                {{ __('2. Xử lý hàng đợi gửi Telegram (Chạy mỗi 1 phút)') }}
                            </label>
                            <div class="flex items-stretch gap-2.5" x-data="{ get url() { return '{{ url('/cron/process-telegram-queue') }}?key=' + cronKey } }">
                                <code class="flex-grow flex items-center px-4 py-2.5 border border-gray-200 dark:border-slate-800 rounded-xl text-[10px] bg-white dark:bg-slate-900 text-gray-700 dark:text-slate-300 font-mono break-all" x-text="url"></code>
                                <button type="button"
                                    @click="
                                        navigator.clipboard.writeText(url);
                                        $dispatch('toast', { text: '{{ __('Đã sao chép link Cron Telegram!') }}', type: 'success' });
                                    "
                                    class="px-4 py-2.5 bg-blue-600 hover:bg-blue-700 active:scale-95 text-white text-[10px] font-bold rounded-xl transition-all shrink-0 flex items-center justify-center gap-1.5 shadow-sm border border-blue-700 cursor-pointer"
                                    title="{{ __('Sao chép') }}">
                                    <i data-lucide="copy" class="w-3.5 h-3.5"></i>
                                    <span>{{ __('Sao chép') }}</span>
                                </button>
                            </div>
                        </div>

                        <div class="space-y-1.5">
                            <label class="block text-[10px] font-bold text-gray-650 dark:text-slate-400 uppercase tracking-wider">
                                {{ __('3. Đồng bộ hoa hồng Shopee Affiliate (Chạy mỗi 6 tiếng)') }}
                            </label>
                            <div class="flex items-stretch gap-2.5" x-data="{ get url() { return '{{ url('/cron/sync-shopee-commissions') }}?key=' + cronKey } }">
                                <code class="flex-grow flex items-center px-4 py-2.5 border border-gray-200 dark:border-slate-800 rounded-xl text-[10px] bg-white dark:bg-slate-900 text-gray-700 dark:text-slate-300 font-mono break-all" x-text="url"></code>
                                <button type="button"
                                    @click="
                                        navigator.clipboard.writeText(url);
                                        $dispatch('toast', { text: '{{ __('Đã sao chép link Cron Shopee!') }}', type: 'success' });
                                    "
                                    class="px-4 py-2.5 bg-blue-600 hover:bg-blue-700 active:scale-95 text-white text-[10px] font-bold rounded-xl transition-all shrink-0 flex items-center justify-center gap-1.5 shadow-sm border border-blue-700 cursor-pointer"
                                    title="{{ __('Sao chép') }}">
                                    <i data-lucide="copy" class="w-3.5 h-3.5"></i>
                                    <span>{{ __('Sao chép') }}</span>
                                </button>
                            </div>
                        </div>

                        <div class="space-y-1.5">
                            <label class="block text-[10px] font-bold text-gray-650 dark:text-slate-400 uppercase tracking-wider">
                                {{ __('4. Đồng bộ hoa hồng TikTok Shop Affiliate (Chạy mỗi 30 phút)') }}
                            </label>
                            <div class="flex items-stretch gap-2.5" x-data="{ get url() { return '{{ url('/cron/sync-tiktok-commissions') }}?key=' + cronKey } }">
                                <code class="flex-grow flex items-center px-4 py-2.5 border border-gray-200 dark:border-slate-800 rounded-xl text-[10px] bg-white dark:bg-slate-900 text-gray-700 dark:text-slate-300 font-mono break-all" x-text="url"></code>
                                <button type="button"
                                    @click="
                                        navigator.clipboard.writeText(url);
                                        $dispatch('toast', { text: '{{ __('Đã sao chép link Cron TikTok!') }}', type: 'success' });
                                    "
                                    class="px-4 py-2.5 bg-blue-600 hover:bg-blue-700 active:scale-95 text-white text-[10px] font-bold rounded-xl transition-all shrink-0 flex items-center justify-center gap-1.5 shadow-sm border border-blue-700 cursor-pointer"
                                    title="{{ __('Sao chép') }}">
                                    <i data-lucide="copy" class="w-3.5 h-3.5"></i>
                                    <span>{{ __('Sao chép') }}</span>
                                </button>
                            </div>
                        </div>

                        <div class="space-y-1.5">
                            <label class="block text-[10px] font-bold text-gray-650 dark:text-slate-400 uppercase tracking-wider">
                                {{ __('4.1. Đồng bộ hoa hồng Lazada Affiliate (Chạy mỗi 30 phút)') }}
                            </label>
                            <div class="flex items-stretch gap-2.5" x-data="{ get url() { return '{{ url('/cron/sync-lazada-commissions') }}?key=' + cronKey } }">
                                <code class="flex-grow flex items-center px-4 py-2.5 border border-gray-200 dark:border-slate-800 rounded-xl text-[10px] bg-white dark:bg-slate-900 text-gray-700 dark:text-slate-300 font-mono break-all" x-text="url"></code>
                                <button type="button"
                                    @click="
                                        navigator.clipboard.writeText(url);
                                        $dispatch('toast', { text: '{{ __('Đã sao chép link Cron Lazada!') }}', type: 'success' });
                                    "
                                    class="px-4 py-2.5 bg-blue-600 hover:bg-blue-700 active:scale-95 text-white text-[10px] font-bold rounded-xl transition-all shrink-0 flex items-center justify-center gap-1.5 shadow-sm border border-blue-700 cursor-pointer"
                                    title="{{ __('Sao chép') }}">
                                    <i data-lucide="copy" class="w-3.5 h-3.5"></i>
                                    <span>{{ __('Sao chép') }}</span>
                                </button>
                            </div>
                        </div>

                        <div class="space-y-1.5">
                            <label class="block text-[10px] font-bold text-gray-650 dark:text-slate-400 uppercase tracking-wider">
                                {{ __('5. Đồng bộ mã giảm giá đa sàn (Chạy mỗi 1 tiếng)') }}
                            </label>
                            <div class="flex items-stretch gap-2.5" x-data="{ get url() { return '{{ url('/cron/sync-coupons') }}?key=' + cronKey } }">
                                <code class="flex-grow flex items-center px-4 py-2.5 border border-gray-200 dark:border-slate-800 rounded-xl text-[10px] bg-white dark:bg-slate-900 text-gray-700 dark:text-slate-300 font-mono break-all" x-text="url"></code>
                                <button type="button"
                                    @click="
                                        navigator.clipboard.writeText(url);
                                        $dispatch('toast', { text: '{{ __('Đã sao chép link Cron Coupons!') }}', type: 'success' });
                                    "
                                    class="px-4 py-2.5 bg-blue-600 hover:bg-blue-700 active:scale-95 text-white text-[10px] font-bold rounded-xl transition-all shrink-0 flex items-center justify-center gap-1.5 shadow-sm border border-blue-700 cursor-pointer"
                                    title="{{ __('Sao chép') }}">
                                    <i data-lucide="copy" class="w-3.5 h-3.5"></i>
                                    <span>{{ __('Sao chép') }}</span>
                                </button>
                            </div>
                        </div>

                        <div class="space-y-1.5">
                            <label class="block text-[10px] font-bold text-gray-650 dark:text-slate-400 uppercase tracking-wider">
                                {{ __('6. Tự động kiểm tra và cập nhật hệ thống (Chạy mỗi 30 phút)') }}
                            </label>
                            <div class="flex items-stretch gap-2.5" x-data="{ get url() { return '{{ url('/cron/system-auto-update') }}?key=' + cronKey } }">
                                <code class="flex-grow flex items-center px-4 py-2.5 border border-gray-200 dark:border-slate-800 rounded-xl text-[10px] bg-white dark:bg-slate-900 text-gray-700 dark:text-slate-300 font-mono break-all" x-text="url"></code>
                                <button type="button"
                                    @click="
                                        navigator.clipboard.writeText(url);
                                        $dispatch('toast', { text: '{{ __('Đã sao chép link Cron Cập nhật!') }}', type: 'success' });
                                    "
                                    class="px-4 py-2.5 bg-blue-600 hover:bg-blue-700 active:scale-95 text-white text-[10px] font-bold rounded-xl transition-all shrink-0 flex items-center justify-center gap-1.5 shadow-sm border border-blue-700 cursor-pointer"
                                    title="{{ __('Sao chép') }}">
                                    <i data-lucide="copy" class="w-3.5 h-3.5"></i>
                                    <span>{{ __('Sao chép') }}</span>
                                </button>
                            </div>
                        </div>

                        <div class="space-y-1.5">
                            <label class="block text-[10px] font-bold text-gray-650 dark:text-slate-400 uppercase tracking-wider">
                                {{ __('7. Bảo trì và dọn dẹp hệ thống định kỳ (Chạy mỗi 12 tiếng)') }}
                            </label>
                            <div class="flex items-stretch gap-2.5" x-data="{ get url() { return '{{ url('/cron/system-maintenance') }}?key=' + cronKey } }">
                                <code class="flex-grow flex items-center px-4 py-2.5 border border-gray-200 dark:border-slate-800 rounded-xl text-[10px] bg-white dark:bg-slate-900 text-gray-700 dark:text-slate-350 font-mono break-all" x-text="url"></code>
                                <button type="button"
                                    @click="
                                        navigator.clipboard.writeText(url);
                                        $dispatch('toast', { text: '{{ __('Đã sao chép link Cron Bảo trì!') }}', type: 'success' });
                                    "
                                    class="px-4 py-2.5 bg-blue-600 hover:bg-blue-700 active:scale-95 text-white text-[10px] font-bold rounded-xl transition-all shrink-0 flex items-center justify-center gap-1.5 shadow-sm border border-blue-700 cursor-pointer"
                                    title="{{ __('Sao chép') }}">
                                    <i data-lucide="copy" class="w-3.5 h-3.5"></i>
                                    <span>{{ __('Sao chép') }}</span>
                                </button>
                            </div>
                        </div>

                        <div class="space-y-1.5">
                            <label class="block text-[10px] font-bold text-gray-650 dark:text-slate-400 uppercase tracking-wider">
                                {{ __('8. Sinh sitemap.xml phục vụ tìm kiếm SEO (Chạy hằng ngày)') }}
                            </label>
                            <div class="flex items-stretch gap-2.5" x-data="{ get url() { return '{{ url('/cron/generate-sitemap') }}?key=' + cronKey } }">
                                <code class="flex-grow flex items-center px-4 py-2.5 border border-gray-200 dark:border-slate-800 rounded-xl text-[10px] bg-white dark:bg-slate-900 text-gray-700 dark:text-slate-300 font-mono break-all" x-text="url"></code>
                                <button type="button"
                                    @click="
                                        navigator.clipboard.writeText(url);
                                        $dispatch('toast', { text: '{{ __('Đã sao chép link Cron Sitemap!') }}', type: 'success' });
                                    "
                                    class="px-4 py-2.5 bg-blue-600 hover:bg-blue-700 active:scale-95 text-white text-[10px] font-bold rounded-xl transition-all shrink-0 flex items-center justify-center gap-1.5 shadow-sm border border-blue-700 cursor-pointer"
                                    title="{{ __('Sao chép') }}">
                                    <i data-lucide="copy" class="w-3.5 h-3.5"></i>
                                    <span>{{ __('Sao chép') }}</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-between text-[9px] text-blue-700/60 dark:text-blue-400/60 mt-1.5">
                <span>💡 {{ __('Lưu ý thay thế đường dẫn chính xác của website nếu triển khai trên máy chủ khác.') }}</span>
                @if($scheduleLastRun)
                    <span class="font-semibold">{{ __('Chạy lần cuối:') }} {{ date('H:i:s d/m/Y', strtotime($scheduleLastRun)) }}</span>
                @else
                    <span class="font-semibold text-amber-600">{{ __('Chưa ghi nhận dữ liệu chạy Scheduler.') }}</span>
                @endif
            </div>
        </div>

        <!-- 
            Danh sách các tác vụ tự động (Scheduled Tasks) chạy ngầm trong hệ thống
            Giúp quản trị viên theo dõi được các cron job đang hoạt động khi cấu hình Artisan schedule:run
        -->
        <div class="space-y-4">
            <h4 class="text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider pb-1 border-b border-gray-100 dark:border-slate-800/80 flex items-center gap-1.5">
                <i data-lucide="list-todo" class="w-3.5 h-3.5 text-shopee"></i>
                {{ __('Danh sách tác vụ tự động được lên lịch') }}
            </h4>
            <div class="bg-white dark:bg-slate-900 rounded-2xl border border-gray-100 dark:border-slate-800/50 overflow-hidden shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs whitespace-nowrap">
                        <thead>
                            <tr class="bg-gray-50/70 border-b border-gray-100 dark:bg-slate-800/40 dark:border-slate-800 text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                <th class="px-5 py-3">{{ __('Tác vụ / Lệnh') }}</th>
                                <th class="px-5 py-3">{{ __('Tần suất') }}</th>
                                <th class="px-5 py-3">{{ __('Mô tả chi tiết tác vụ') }}</th>
                                <th class="px-5 py-3">{{ __('Chạy lần cuối') }}</th>
                                <th class="px-5 py-3">{{ __('Trạng thái') }}</th>
                                <th class="px-5 py-3 text-right">{{ __('Hành động') }}</th>
                            </tr>
                        </thead>
                                                <tbody class="divide-y divide-gray-100 dark:divide-slate-800/50">
                            <!-- Tác vụ 1: Ghi nhận trạng thái Scheduler -->
                            <tr class="hover:bg-gray-50/50 dark:hover:bg-slate-800/20 transition-all">
                                <td class="px-5 py-3.5 font-bold text-gray-900 dark:text-white whitespace-nowrap">
                                    <span class="inline-flex items-center gap-1.5 font-mono text-[10px] px-2 py-0.5 rounded-lg bg-gray-100 text-gray-700 border border-gray-200/50 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700">
                                        schedule:heartbeat
                                    </span>
                                </td>
                                <td class="px-5 py-3.5 whitespace-nowrap">
                                    <span class="inline-flex px-2 py-0.5 rounded-full text-[9px] font-bold bg-blue-50 text-blue-600 border border-blue-100 dark:bg-blue-950/20 dark:text-blue-400 dark:border-blue-900/30">
                                        {{ __('Mỗi phút') }} (everyMinute)
                                    </span>
                                </td>
                                <td class="px-5 py-3.5 text-gray-500 dark:text-gray-400 max-w-[280px] truncate" title="{{ __('Ghi lại thời điểm chạy gần nhất của Laravel Task Scheduler để hệ thống kiểm tra trạng thái hoạt động.') }}">
                                    {{ __('Ghi lại thời điểm chạy gần nhất của Laravel Task Scheduler để hệ thống kiểm tra trạng thái hoạt động.') }}
                                </td>
                                <td class="px-5 py-3.5 text-gray-550 dark:text-slate-300 whitespace-nowrap">
                                    {{ $scheduleLastRun ? date('H:i:s d/m/Y', strtotime($scheduleLastRun)) : __('Chưa chạy') }}
                                </td>
                                <td class="px-5 py-3.5 whitespace-nowrap">
                                    @if($isCronRunning)
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-green-50 text-green-700 border border-green-200/50 dark:bg-green-950/30 dark:text-green-400 dark:border-green-900/50">
                                            <span class="w-1 h-1 rounded-full bg-green-500 animate-pulse"></span>
                                            {{ __('Đang hoạt động') }}
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200/50 dark:bg-amber-950/30 dark:text-amber-400 dark:border-amber-900/50">
                                            <span class="w-1 h-1 rounded-full bg-amber-500 animate-pulse"></span>
                                            {{ __('Chờ chạy') }}
                                        </span>
                                    @endif
                                </td>
                                <td class="px-5 py-3.5 text-right whitespace-nowrap">
                                    <button type="button"
                                        @click="runTask('schedule:heartbeat')"
                                        :disabled="runningTask !== ''"
                                        class="inline-flex items-center gap-1 px-2.5 py-1.5 bg-shopee hover:bg-shopee-dark disabled:bg-gray-300 disabled:cursor-not-allowed text-white text-[10px] font-bold rounded-xl transition-all shadow-sm cursor-pointer active:scale-95">
                                        <span x-show="runningTask === 'schedule:heartbeat'" class="w-3 h-3 border-2 border-white border-t-transparent rounded-full animate-spin" x-cloak></span>
                                        <i x-show="runningTask !== 'schedule:heartbeat'" data-lucide="play" class="w-3 h-3"></i>
                                        <span>{{ __('Chạy ngay') }}</span>
                                    </button>
                                </td>
                            </tr>

                            <!-- Tác vụ 2: Xử lý hàng đợi gửi Email -->
                            <tr class="hover:bg-gray-50/50 dark:hover:bg-slate-800/20 transition-all">
                                <td class="px-5 py-3.5 font-bold text-gray-900 dark:text-white whitespace-nowrap">
                                    <span class="inline-flex items-center gap-1.5 font-mono text-[10px] px-2 py-0.5 rounded-lg bg-gray-100 text-gray-700 border border-gray-200/50 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700">
                                        process-email-queue
                                    </span>
                                </td>
                                <td class="px-5 py-3.5 whitespace-nowrap">
                                    <span class="inline-flex px-2 py-0.5 rounded-full text-[9px] font-bold bg-blue-50 text-blue-600 border border-blue-100 dark:bg-blue-950/20 dark:text-blue-400 dark:border-blue-900/30">
                                        {{ __('Mỗi phút') }} (everyMinute)
                                    </span>
                                </td>
                                <td class="px-5 py-3.5 text-gray-550 dark:text-gray-400 max-w-[280px] truncate" title="{{ __('Tự động xử lý và gửi các email trong hàng đợi (email chiến dịch marketing, thông báo biến động số dư, đổi mật khẩu...).') }}">
                                    {{ __('Tự động xử lý và gửi các email trong hàng đợi (email chiến dịch marketing, thông báo biến động số dư, đổi mật khẩu...).') }}
                                </td>
                                <td class="px-5 py-3.5 text-gray-550 dark:text-slate-300 whitespace-nowrap">
                                    {{ $emailLastRun ? date('H:i:s d/m/Y', strtotime($emailLastRun)) : __('Chưa chạy') }}
                                </td>
                                <td class="px-5 py-3.5 whitespace-nowrap">
                                    @if($isEmailCronRunning)
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-green-50 text-green-700 border border-green-200/50 dark:bg-green-950/30 dark:text-green-400 dark:border-green-900/50">
                                            <span class="w-1 h-1 rounded-full bg-green-500 animate-pulse"></span>
                                            {{ __('Đang hoạt động') }}
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200/50 dark:bg-amber-950/30 dark:text-amber-400 dark:border-amber-900/50">
                                            <span class="w-1 h-1 rounded-full bg-amber-500 animate-pulse"></span>
                                            {{ __('Chờ chạy') }}
                                        </span>
                                    @endif
                                </td>
                                <td class="px-5 py-3.5 text-right whitespace-nowrap">
                                    <button type="button"
                                        @click="runTask('process-email-queue')"
                                        :disabled="runningTask !== ''"
                                        class="inline-flex items-center gap-1 px-2.5 py-1.5 bg-shopee hover:bg-shopee-dark disabled:bg-gray-300 disabled:cursor-not-allowed text-white text-[10px] font-bold rounded-xl transition-all shadow-sm cursor-pointer active:scale-95">
                                        <span x-show="runningTask === 'process-email-queue'" class="w-3 h-3 border-2 border-white border-t-transparent rounded-full animate-spin" x-cloak></span>
                                        <i x-show="runningTask !== 'process-email-queue'" data-lucide="play" class="w-3 h-3"></i>
                                        <span>{{ __('Chạy ngay') }}</span>
                                    </button>
                                </td>
                            </tr>

                            <!-- Tác vụ 2.5: Xử lý hàng đợi gửi Telegram -->
                            <tr class="hover:bg-gray-50/50 dark:hover:bg-slate-800/20 transition-all">
                                <td class="px-5 py-3.5 font-bold text-gray-900 dark:text-white whitespace-nowrap">
                                    <span class="inline-flex items-center gap-1.5 font-mono text-[10px] px-2 py-0.5 rounded-lg bg-gray-100 text-gray-700 border border-gray-200/50 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700">
                                        process-telegram-queue
                                    </span>
                                </td>
                                <td class="px-5 py-3.5 whitespace-nowrap">
                                    <span class="inline-flex px-2 py-0.5 rounded-full text-[9px] font-bold bg-blue-50 text-blue-600 border border-blue-100 dark:bg-blue-950/20 dark:text-blue-400 dark:border-blue-900/30">
                                        {{ __('Mỗi phút') }} (everyMinute)
                                    </span>
                                </td>
                                <td class="px-5 py-3.5 text-gray-550 dark:text-gray-400 max-w-[280px] truncate" title="{{ __('Tự động xử lý và gửi các tin nhắn Telegram trong hàng đợi (thông báo duyệt đơn hoàn tiền, doanh thu giới thiệu MLM...).') }}">
                                    {{ __('Tự động xử lý và gửi các tin nhắn Telegram trong hàng đợi (thông báo duyệt đơn hoàn tiền, doanh thu giới thiệu MLM...).') }}
                                </td>
                                <td class="px-5 py-3.5 text-gray-550 dark:text-slate-300 whitespace-nowrap">
                                    {{ $telegramLastRun ? date('H:i:s d/m/Y', strtotime($telegramLastRun)) : __('Chưa chạy') }}
                                </td>
                                <td class="px-5 py-3.5 whitespace-nowrap">
                                    @if($isTelegramCronRunning)
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-green-50 text-green-700 border border-green-200/50 dark:bg-green-950/30 dark:text-green-400 border-green-900/50 rounded-xl">
                                            <span class="w-1 h-1 rounded-full bg-green-500 animate-pulse"></span>
                                            {{ __('Đang hoạt động') }}
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200/50 dark:bg-amber-950/30 dark:text-amber-400 border-amber-900/50 rounded-xl">
                                            <span class="w-1 h-1 rounded-full bg-amber-500 animate-pulse"></span>
                                            {{ __('Chờ chạy') }}
                                        </span>
                                    @endif
                                </td>
                                <td class="px-5 py-3.5 text-right whitespace-nowrap">
                                    <button type="button"
                                        @click="runTask('process-telegram-queue')"
                                        :disabled="runningTask !== ''"
                                        class="inline-flex items-center gap-1 px-2.5 py-1.5 bg-shopee hover:bg-shopee-dark disabled:bg-gray-300 disabled:cursor-not-allowed text-white text-[10px] font-bold rounded-xl transition-all shadow-sm cursor-pointer active:scale-95">
                                        <span x-show="runningTask === 'process-telegram-queue'" class="w-3 h-3 border-2 border-white border-t-transparent rounded-full animate-spin" x-cloak></span>
                                        <i x-show="runningTask !== 'process-telegram-queue'" data-lucide="play" class="w-3 h-3"></i>
                                        <span>{{ __('Chạy ngay') }}</span>
                                    </button>
                                </td>
                            </tr>

                            <!-- Tác vụ 2.8: Đồng bộ hoa hồng Shopee Affiliate -->
                            <tr class="hover:bg-gray-50/50 dark:hover:bg-slate-800/20 transition-all">
                                <td class="px-5 py-3.5 font-bold text-gray-900 dark:text-white whitespace-nowrap">
                                    <span class="inline-flex items-center gap-1.5 font-mono text-[10px] px-2 py-0.5 rounded-lg bg-gray-100 text-gray-700 border border-gray-200/50 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700">
                                        shopee:sync-commissions
                                    </span>
                                </td>
                                <td class="px-5 py-3.5 whitespace-nowrap">
                                    <span class="inline-flex px-2 py-0.5 rounded-full text-[9px] font-bold bg-amber-50 text-amber-600 border border-amber-100 dark:bg-amber-950/20 dark:text-amber-400 dark:border-amber-900/30">
                                        {{ __('Mỗi 6 tiếng') }} (everySixHours)
                                    </span>
                                </td>
                                <td class="px-5 py-3.5 text-gray-550 dark:text-gray-400 max-w-[280px] truncate" title="{{ __('Tự động kết nối API Shopee Affiliate để đồng bộ và cập nhật doanh thu/đối soát trạng thái của các đơn hàng cashback.') }}">
                                    {{ __('Tự động kết nối API Shopee Affiliate để đồng bộ và cập nhật doanh thu/đối soát trạng thái của các đơn hàng cashback.') }}
                                </td>
                                <td class="px-5 py-3.5 text-gray-550 dark:text-slate-300 whitespace-nowrap">
                                    {{ $shopeeLastRun ? date('H:i:s d/m/Y', strtotime($shopeeLastRun)) : __('Chưa chạy') }}
                                </td>
                                <td class="px-5 py-3.5 whitespace-nowrap">
                                    @if($isShopeeCronRunning)
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-green-50 text-green-700 border border-green-200/50 dark:bg-green-950/30 dark:text-green-400 border-green-900/50 rounded-xl">
                                            <span class="w-1 h-1 rounded-full bg-green-500 animate-pulse"></span>
                                            {{ __('Đang hoạt động') }}
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200/50 dark:bg-amber-950/30 dark:text-amber-400 border-amber-900/50 rounded-xl">
                                            <span class="w-1 h-1 rounded-full bg-amber-500 animate-pulse"></span>
                                            {{ __('Chờ chạy') }}
                                        </span>
                                    @endif
                                </td>
                                <td class="px-5 py-3.5 text-right whitespace-nowrap">
                                    <button type="button"
                                        @click="runTask('shopee:sync-commissions')"
                                        :disabled="runningTask !== ''"
                                        class="inline-flex items-center gap-1 px-2.5 py-1.5 bg-shopee hover:bg-shopee-dark disabled:bg-gray-300 disabled:cursor-not-allowed text-white text-[10px] font-bold rounded-xl transition-all shadow-sm cursor-pointer active:scale-95">
                                        <span x-show="runningTask === 'shopee:sync-commissions'" class="w-3 h-3 border-2 border-white border-t-transparent rounded-full animate-spin" x-cloak></span>
                                        <i x-show="runningTask !== 'shopee:sync-commissions'" data-lucide="play" class="w-3 h-3"></i>
                                        <span>{{ __('Chạy ngay') }}</span>
                                    </button>
                                </td>
                            </tr>

                            <!-- Tác vụ 2.85: Đồng bộ hoa hồng TikTok Shop Affiliate -->
                            <tr class="hover:bg-gray-50/50 dark:hover:bg-slate-800/20 transition-all">
                                <td class="px-5 py-3.5 font-bold text-gray-900 dark:text-white whitespace-nowrap">
                                    <span class="inline-flex items-center gap-1.5 font-mono text-[10px] px-2 py-0.5 rounded-lg bg-gray-100 text-gray-700 border border-gray-200/50 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700">
                                        tiktok:sync-commissions
                                    </span>
                                </td>
                                <td class="px-5 py-3.5 whitespace-nowrap">
                                    <span class="inline-flex px-2 py-0.5 rounded-full text-[9px] font-bold bg-amber-50 text-amber-600 border border-amber-100 dark:bg-amber-950/20 dark:text-amber-400 dark:border-amber-900/30">
                                        {{ __('Mỗi 30 phút') }} (everyThirtyMinutes)
                                    </span>
                                </td>
                                <td class="px-5 py-3.5 text-gray-550 dark:text-gray-400 max-w-[280px] truncate" title="{{ __('Tự động kết nối API TikTok Shop để đồng bộ và cập nhật doanh thu/đối soát trạng thái của các đơn hàng cashback.') }}">
                                    {{ __('Tự động kết nối API TikTok Shop để đồng bộ và cập nhật doanh thu/đối soát trạng thái của các đơn hàng cashback.') }}
                                </td>
                                <td class="px-5 py-3.5 text-gray-550 dark:text-slate-300 whitespace-nowrap">
                                    {{ $tiktokLastRun ? date('H:i:s d/m/Y', strtotime($tiktokLastRun)) : __('Chưa chạy') }}
                                </td>
                                <td class="px-5 py-3.5 whitespace-nowrap">
                                    @if($isTiktokCronRunning)
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-green-50 text-green-700 border border-green-200/50 dark:bg-green-950/30 dark:text-green-400 border-green-900/50 rounded-xl">
                                            <span class="w-1 h-1 rounded-full bg-green-500 animate-pulse"></span>
                                            {{ __('Đang hoạt động') }}
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200/50 dark:bg-amber-950/30 dark:text-amber-400 border-amber-900/50 rounded-xl">
                                            <span class="w-1 h-1 rounded-full bg-amber-500 animate-pulse"></span>
                                            {{ __('Chờ chạy') }}
                                        </span>
                                    @endif
                                </td>
                                <td class="px-5 py-3.5 text-right whitespace-nowrap">
                                    <button type="button"
                                        @click="runTask('tiktok:sync-commissions')"
                                        :disabled="runningTask !== ''"
                                        class="inline-flex items-center gap-1 px-2.5 py-1.5 bg-shopee hover:bg-shopee-dark disabled:bg-gray-300 disabled:cursor-not-allowed text-white text-[10px] font-bold rounded-xl transition-all shadow-sm cursor-pointer active:scale-95">
                                        <span x-show="runningTask === 'tiktok:sync-commissions'" class="w-3 h-3 border-2 border-white border-t-transparent rounded-full animate-spin" x-cloak></span>
                                        <i x-show="runningTask !== 'tiktok:sync-commissions'" data-lucide="play" class="w-3 h-3"></i>
                                        <span>{{ __('Chạy ngay') }}</span>
                                    </button>
                                </td>
                            </tr>

                            <!-- Tác vụ 2.87: Đồng bộ hoa hồng Lazada Affiliate -->
                            <tr class="hover:bg-gray-50/50 dark:hover:bg-slate-800/20 transition-all">
                                <td class="px-5 py-3.5 font-bold text-gray-900 dark:text-white whitespace-nowrap">
                                    <span class="inline-flex items-center gap-1.5 font-mono text-[10px] px-2 py-0.5 rounded-lg bg-gray-100 text-gray-700 border border-gray-200/50 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700">
                                        lazada:sync-commissions
                                    </span>
                                </td>
                                <td class="px-5 py-3.5 whitespace-nowrap">
                                    <span class="inline-flex px-2 py-0.5 rounded-full text-[9px] font-bold bg-amber-50 text-amber-600 border border-amber-100 dark:bg-amber-950/20 dark:text-amber-400 dark:border-amber-900/30">
                                        {{ __('Mỗi 30 phút') }} (everyThirtyMinutes)
                                    </span>
                                </td>
                                <td class="px-5 py-3.5 text-gray-550 dark:text-gray-400 max-w-[280px] truncate" title="{{ __('Tự động kết nối API Lazada Affiliate để đồng bộ và cập nhật doanh thu/đối soát trạng thái của các đơn hàng cashback.') }}">
                                    {{ __('Tự động kết nối API Lazada Affiliate để đồng bộ và cập nhật doanh thu/đối soát trạng thái của các đơn hàng cashback.') }}
                                </td>
                                <td class="px-5 py-3.5 text-gray-550 dark:text-slate-300 whitespace-nowrap">
                                    {{ $lazadaLastRun ? date('H:i:s d/m/Y', strtotime($lazadaLastRun)) : __('Chưa chạy') }}
                                </td>
                                <td class="px-5 py-3.5 whitespace-nowrap">
                                    @if($isLazadaCronRunning)
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-green-50 text-green-700 border border-green-200/50 dark:bg-green-950/30 dark:text-green-400 border-green-900/50 rounded-xl">
                                            <span class="w-1 h-1 rounded-full bg-green-500 animate-pulse"></span>
                                            {{ __('Đang hoạt động') }}
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200/50 dark:bg-amber-950/30 dark:text-amber-400 border-amber-900/50 rounded-xl">
                                            <span class="w-1 h-1 rounded-full bg-amber-500 animate-pulse"></span>
                                            {{ __('Chờ chạy') }}
                                        </span>
                                    @endif
                                </td>
                                <td class="px-5 py-3.5 text-right whitespace-nowrap">
                                    <button type="button"
                                        @click="runTask('lazada:sync-commissions')"
                                        :disabled="runningTask !== ''"
                                        class="inline-flex items-center gap-1 px-2.5 py-1.5 bg-shopee hover:bg-shopee-dark disabled:bg-gray-300 disabled:cursor-not-allowed text-white text-[10px] font-bold rounded-xl transition-all shadow-sm cursor-pointer active:scale-95">
                                        <span x-show="runningTask === 'lazada:sync-commissions'" class="w-3 h-3 border-2 border-white border-t-transparent rounded-full animate-spin" x-cloak></span>
                                        <i x-show="runningTask !== 'lazada:sync-commissions'" data-lucide="play" class="w-3 h-3"></i>
                                        <span>{{ __('Chạy ngay') }}</span>
                                    </button>
                                </td>
                            </tr>

                            <!-- Tác vụ 2.9: Đồng bộ Mã giảm giá Đa sàn -->
                            <tr class="hover:bg-gray-50/50 dark:hover:bg-slate-800/20 transition-all">
                                <td class="px-5 py-3.5 font-bold text-gray-900 dark:text-white whitespace-nowrap">
                                    <span class="inline-flex items-center gap-1.5 font-mono text-[10px] px-2 py-0.5 rounded-lg bg-gray-100 text-gray-700 border border-gray-200/50 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700">
                                        coupons:sync
                                    </span>
                                </td>
                                <td class="px-5 py-3.5 whitespace-nowrap">
                                    <span class="inline-flex px-2 py-0.5 rounded-full text-[9px] font-bold bg-amber-50 text-amber-600 border border-amber-100 dark:bg-amber-950/20 dark:text-amber-400 dark:border-amber-900/30">
                                        {{ __('Mỗi tiếng') }} (hourly)
                                    </span>
                                </td>
                                <td class="px-5 py-3.5 text-gray-550 dark:text-gray-400 max-w-[280px] truncate" title="{{ __('Tự động kết nối API đối tác để đồng bộ danh sách mã giảm giá, voucher đa sàn (Shopee, Lazada, Tiktok...).') }}">
                                    {{ __('Tự động kết nối API đối tác để đồng bộ danh sách mã giảm giá, voucher đa sàn (Shopee, Lazada, Tiktok...).') }}
                                </td>
                                <td class="px-5 py-3.5 text-gray-550 dark:text-slate-300 whitespace-nowrap">
                                    {{ $couponsLastRun ? date('H:i:s d/m/Y', strtotime($couponsLastRun)) : __('Chưa chạy') }}
                                </td>
                                <td class="px-5 py-3.5 whitespace-nowrap">
                                    @if($isCouponsCronRunning)
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-green-50 text-green-700 border border-green-200/50 dark:bg-green-950/30 dark:text-green-400 border-green-900/50 rounded-xl">
                                            <span class="w-1 h-1 rounded-full bg-green-500 animate-pulse"></span>
                                            {{ __('Đang hoạt động') }}
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200/50 dark:bg-amber-950/30 dark:text-amber-400 border-amber-900/50 rounded-xl">
                                            <span class="w-1 h-1 rounded-full bg-amber-500 animate-pulse"></span>
                                            {{ __('Chờ chạy') }}
                                        </span>
                                    @endif
                                </td>
                                <td class="px-5 py-3.5 text-right whitespace-nowrap">
                                    <button type="button"
                                        @click="runTask('coupons:sync')"
                                        :disabled="runningTask !== ''"
                                        class="inline-flex items-center gap-1 px-2.5 py-1.5 bg-shopee hover:bg-shopee-dark disabled:bg-gray-300 disabled:cursor-not-allowed text-white text-[10px] font-bold rounded-xl transition-all shadow-sm cursor-pointer active:scale-95">
                                        <span x-show="runningTask === 'coupons:sync'" class="w-3 h-3 border-2 border-white border-t-transparent rounded-full animate-spin" x-cloak></span>
                                        <i x-show="runningTask !== 'coupons:sync'" data-lucide="play" class="w-3 h-3"></i>
                                        <span>{{ __('Chạy ngay') }}</span>
                                    </button>
                                </td>
                            </tr>

                            <!-- Tác vụ 2.10: Tự động cập nhật hệ thống -->
                            <tr class="hover:bg-gray-50/50 dark:hover:bg-slate-800/20 transition-all">
                                <td class="px-5 py-3.5 font-bold text-gray-900 dark:text-white whitespace-nowrap">
                                    <span class="inline-flex items-center gap-1.5 font-mono text-[10px] px-2 py-0.5 rounded-lg bg-gray-100 text-gray-700 border border-gray-200/50 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700">
                                        system:auto-update
                                    </span>
                                </td>
                                <td class="px-5 py-3.5 whitespace-nowrap">
                                    <span class="inline-flex px-2 py-0.5 rounded-full text-[9px] font-bold bg-amber-50 text-amber-600 border border-amber-100 dark:bg-amber-950/20 dark:text-amber-400 dark:border-amber-900/30">
                                        {{ __('Mỗi 30 phút') }} (everyThirtyMinutes)
                                    </span>
                                </td>
                                <td class="px-5 py-3.5 text-gray-550 dark:text-gray-400 max-w-[280px] truncate" title="{{ __('Tự động kiểm tra phiên bản mới từ máy chủ cập nhật trung tâm và tự động cập nhật hệ thống nếu có.') }}">
                                    {{ __('Tự động kiểm tra phiên bản mới từ máy chủ cập nhật trung tâm và tự động cập nhật hệ thống nếu có.') }}
                                </td>
                                <td class="px-5 py-3.5 text-gray-550 dark:text-slate-300 whitespace-nowrap">
                                    {{ $autoUpdateLastRun ? date('H:i:s d/m/Y', strtotime($autoUpdateLastRun)) : __('Chưa chạy') }}
                                </td>
                                <td class="px-5 py-3.5 whitespace-nowrap">
                                    @if($isAutoUpdateCronRunning)
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-green-50 text-green-700 border border-green-200/50 dark:bg-green-950/30 dark:text-green-400 border-green-900/50 rounded-xl">
                                            <span class="w-1 h-1 rounded-full bg-green-500 animate-pulse"></span>
                                            {{ __('Đang hoạt động') }}
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200/50 dark:bg-amber-950/30 dark:text-amber-400 border-amber-900/50 rounded-xl">
                                            <span class="w-1 h-1 rounded-full bg-amber-500 animate-pulse"></span>
                                            {{ __('Chờ chạy') }}
                                        </span>
                                    @endif
                                </td>
                                <td class="px-5 py-3.5 text-right whitespace-nowrap">
                                    <button type="button"
                                        @click="runTask('system:auto-update')"
                                        :disabled="runningTask !== ''"
                                        class="inline-flex items-center gap-1 px-2.5 py-1.5 bg-shopee hover:bg-shopee-dark disabled:bg-gray-300 disabled:cursor-not-allowed text-white text-[10px] font-bold rounded-xl transition-all shadow-sm cursor-pointer active:scale-95">
                                        <span x-show="runningTask === 'system:auto-update'" class="w-3 h-3 border-2 border-white border-t-transparent rounded-full animate-spin" x-cloak></span>
                                        <i x-show="runningTask !== 'system:auto-update'" data-lucide="play" class="w-3 h-3"></i>
                                        <span>{{ __('Chạy ngay') }}</span>
                                    </button>
                                </td>
                            </tr>

                            <!-- Tác vụ 3: Bảo trì hệ thống định kỳ -->
                            <tr class="hover:bg-gray-50/50 dark:hover:bg-slate-800/20 transition-all">
                                <td class="px-5 py-3.5 font-bold text-gray-900 dark:text-white whitespace-nowrap">
                                    <span class="inline-flex items-center gap-1.5 font-mono text-[10px] px-2 py-0.5 rounded-lg bg-gray-100 text-gray-700 border border-gray-200/50 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700">
                                        system-maintenance
                                    </span>
                                </td>
                                <td class="px-5 py-3.5 whitespace-nowrap">
                                    <span class="inline-flex px-2 py-0.5 rounded-full text-[9px] font-bold bg-teal-50 text-teal-600 border border-teal-100 dark:bg-teal-950/20 dark:text-teal-400 dark:border-teal-900/30">
                                        {{ __('Mỗi 12 tiếng') }} (everyTwelveHours)
                                    </span>
                                </td>
                                <td class="px-5 py-3.5 text-gray-550 dark:text-gray-400 max-w-[280px] truncate" title="{{ __('Dọn dẹp nhật ký (log) cũ, giải phóng cache sản phẩm đã hết hiệu lực để giữ cho dung lượng cơ sở dữ liệu tối ưu.') }}">
                                    {{ __('Dọn dẹp nhật ký (log) cũ, giải phóng cache sản phẩm đã hết hiệu lực để giữ cho dung lượng cơ sở dữ liệu tối ưu.') }}
                                </td>
                                <td class="px-5 py-3.5 text-gray-550 dark:text-slate-300 whitespace-nowrap">
                                    {{ $maintenanceLastRun ? date('H:i:s d/m/Y', strtotime($maintenanceLastRun)) : __('Chưa chạy') }}
                                </td>
                                <td class="px-5 py-3.5 whitespace-nowrap">
                                    @if($isMaintenanceCronRunning)
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-green-50 text-green-700 border border-green-200/50 dark:bg-green-950/30 dark:text-green-400 border-green-900/50 rounded-xl">
                                            <span class="w-1 h-1 rounded-full bg-green-500 animate-pulse"></span>
                                            {{ __('Đang hoạt động') }}
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200/50 dark:bg-amber-950/30 dark:text-amber-400 border-amber-900/50 rounded-xl">
                                            <span class="w-1 h-1 rounded-full bg-amber-500 animate-pulse"></span>
                                            {{ __('Chờ chạy') }}
                                        </span>
                                    @endif
                                </td>
                                <td class="px-5 py-3.5 text-right whitespace-nowrap">
                                    <button type="button"
                                        @click="runTask('system-maintenance')"
                                        :disabled="runningTask !== ''"
                                        class="inline-flex items-center gap-1 px-2.5 py-1.5 bg-shopee hover:bg-shopee-dark disabled:bg-gray-300 disabled:cursor-not-allowed text-white text-[10px] font-bold rounded-xl transition-all shadow-sm cursor-pointer active:scale-95">
                                        <span x-show="runningTask === 'system-maintenance'" class="w-3 h-3 border-2 border-white border-t-transparent rounded-full animate-spin" x-cloak></span>
                                        <i x-show="runningTask !== 'system-maintenance'" data-lucide="play" class="w-3 h-3"></i>
                                        <span>{{ __('Chạy ngay') }}</span>
                                    </button>
                                </td>
                            </tr>

                            <!-- Tác vụ 4: Tự động cập nhật Sitemap SEO -->
                            <tr class="hover:bg-gray-50/50 dark:hover:bg-slate-800/20 transition-all">
                                <td class="px-5 py-3.5 font-bold text-gray-900 dark:text-white whitespace-nowrap">
                                    <span class="inline-flex items-center gap-1.5 font-mono text-[10px] px-2 py-0.5 rounded-lg bg-gray-100 text-gray-700 border border-gray-200/50 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700">
                                        sitemap:generate
                                    </span>
                                </td>
                                <td class="px-5 py-3.5 whitespace-nowrap">
                                    <span class="inline-flex px-2 py-0.5 rounded-full text-[9px] font-bold bg-purple-50 text-purple-600 border border-purple-100 dark:bg-purple-950/20 dark:text-purple-400 dark:border-purple-900/30">
                                        {{ __('Hằng ngày') }} (daily)
                                    </span>
                                </td>
                                <td class="px-5 py-3.5 text-gray-500 dark:text-gray-400 max-w-[280px] truncate" title="{{ __('Tự động quét toàn bộ bài viết, danh mục và trang tĩnh để tái tạo file sitemap.xml phục vụ Google Search Console.') }}">
                                    {{ __('Tự động quét toàn bộ bài viết, danh mục và trang tĩnh để tái tạo file sitemap.xml phục vụ Google Search Console.') }}
                                </td>
                                <td class="px-5 py-3.5 text-gray-550 dark:text-slate-300 whitespace-nowrap">
                                    {{ $sitemapLastRun ? date('H:i:s d/m/Y', strtotime($sitemapLastRun)) : __('Chưa chạy') }}
                                </td>
                                <td class="px-5 py-3.5 whitespace-nowrap">
                                    @if($isSitemapCronRunning)
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-green-50 text-green-700 border border-green-200/50 dark:bg-green-950/30 dark:text-green-400 border-green-900/50 rounded-xl">
                                            <span class="w-1 h-1 rounded-full bg-green-500 animate-pulse"></span>
                                            {{ __('Đang hoạt động') }}
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200/50 dark:bg-amber-950/30 dark:text-amber-400 border-amber-900/50 rounded-xl">
                                            <span class="w-1 h-1 rounded-full bg-amber-500 animate-pulse"></span>
                                            {{ __('Chờ chạy') }}
                                        </span>
                                    @endif
                                </td>
                                <td class="px-5 py-3.5 text-right whitespace-nowrap">
                                    <button type="button"
                                        @click="runTask('sitemap:generate')"
                                        :disabled="runningTask !== ''"
                                        class="inline-flex items-center gap-1 px-2.5 py-1.5 bg-shopee hover:bg-shopee-dark disabled:bg-gray-300 disabled:cursor-not-allowed text-white text-[10px] font-bold rounded-xl transition-all shadow-sm cursor-pointer active:scale-95">
                                        <span x-show="runningTask === 'sitemap:generate'" class="w-3 h-3 border-2 border-white border-t-transparent rounded-full animate-spin" x-cloak></span>
                                        <i x-show="runningTask !== 'sitemap:generate'" data-lucide="play" class="w-3 h-3"></i>
                                        <span>{{ __('Chạy ngay') }}</span>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Cấu hình hàng đợi gửi tin -->
        <div class="space-y-4">
            <h4 class="text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider pb-1 border-b border-gray-100 dark:border-slate-800/80 flex items-center gap-1.5">
                <i data-lucide="mail" class="w-3.5 h-3.5 text-shopee"></i>
                {{ __('Cấu hình hàng đợi gửi tin') }}
            </h4>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="email_queue_batch_size" class="block text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider mb-1">{{ __('Số email gửi mỗi phút') }}</label>
                    <input type="number"
                        name="email_queue_batch_size"
                        id="email_queue_batch_size"
                        value="{{ $settings['email_queue_batch_size'] ?? '10' }}"
                        placeholder="10"
                        min="1" max="100"
                        class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-800 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-300">
                    <span class="text-[9px] text-gray-400 mt-1 block">Số lượng email tối đa được hệ thống gửi đi mỗi phút (mỗi lần Laravel Scheduler thực thi). Khuyến nghị: 5 - 20 (tùy thuộc vào giới hạn nhà cung cấp SMTP).</span>
                </div>

                <div>
                    <label for="telegram_queue_batch_size" class="block text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider mb-1">{{ __('Số tin nhắn Telegram gửi mỗi phút') }}</label>
                    <input type="number"
                        name="telegram_queue_batch_size"
                        id="telegram_queue_batch_size"
                        value="{{ $settings['telegram_queue_batch_size'] ?? '10' }}"
                        placeholder="10"
                        min="1" max="100"
                        class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-800 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-300">
                    <span class="text-[9px] text-gray-400 mt-1 block">Số lượng tin nhắn Telegram tối đa gửi đi mỗi phút. Khuyến nghị: 5 - 30 (mỗi tin nhắn giãn cách 1 giây để tránh lỗi spam từ Telegram).</span>
                </div>
            </div>
        </div>
    </div>
</div>
