@extends('layouts.admin')

@section('title', __('Trạng Thái Hệ Thống') . ' - ' . $siteName)

@section('content')
<div class="space-y-8">
    <!-- Tiêu đề trang và nút hành động -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                <i data-lucide="activity" class="w-6 h-6 text-shopee"></i>
                {{ __('Trạng Thái Hệ Thống') }}
            </h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ __('Giám sát sức khỏe máy chủ, tài nguyên, dịch vụ và cấu hình hệ thống theo thời gian thực.') }}</p>
        </div>
        @if(($licenseValid ?? false) && !config('app.demo'))
        <div class="flex items-center gap-2 shrink-0">
            <button type="button" id="btn-optimize-cache"
                    class="inline-flex items-center gap-2 px-4 py-2.5 bg-gradient-to-r from-emerald-500 to-teal-500 hover:from-emerald-600 hover:to-teal-600 text-white font-bold rounded-2xl text-xs transition-all shadow-lg shadow-teal-500/15 whitespace-nowrap active:scale-95 duration-200">
                <i data-lucide="zap" class="w-4 h-4"></i>
                {{ __('Tối ưu hóa (Bật Cache)') }}
            </button>
            <button type="button" id="btn-clear-cache"
                    class="inline-flex items-center gap-2 px-4 py-2.5 bg-gradient-to-r from-red-500 to-orange-500 hover:from-red-600 hover:to-orange-600 text-white font-bold rounded-2xl text-xs transition-all shadow-lg shadow-orange-500/15 whitespace-nowrap active:scale-95 duration-200">
                <i data-lucide="trash-2" class="w-4 h-4"></i>
                {{ __('Xóa bộ nhớ đệm (Cache)') }}
            </button>
        </div>
        @endif
    </div>

    {{-- === KIỂM TRA GIẤY PHÉP BẢN QUYỀN === --}}
    @if(!($licenseValid ?? false))
    <div class="space-y-6">
        {{-- Card yêu cầu nhập mã bản quyền --}}
        <div class="relative overflow-hidden bg-gradient-to-r from-red-50 to-orange-50 dark:from-red-950/10 dark:to-orange-950/5 p-6 rounded-3xl border border-red-200/60 dark:border-red-950/30 shadow-sm" id="license-config-card">
            <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6">
                <div class="flex items-start gap-4">
                    <div class="p-3 bg-red-100 dark:bg-red-950/30 text-red-500 rounded-2xl shrink-0">
                        <i data-lucide="key" class="w-6 h-6"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-gray-900 dark:text-white" id="license-alert-title">
                            {{ __('Yêu cầu Mã bản quyền') }}
                        </h3>
                        <p class="text-xs text-gray-600 dark:text-gray-400 leading-relaxed mt-1" id="license-alert-desc">
                            @if(!empty($licenseError))
                                {{ $licenseError }}
                            @else
                                {{ __('Vui lòng nhập mã bản quyền (License Key) để xem thông tin trạng thái hệ thống. Bạn có thể lấy mã này tại trang quản lý tài khoản CMSNT.') }}
                            @endif
                        </p>
                    </div>
                </div>
                <div class="flex items-center gap-2 w-full lg:w-auto shrink-0">
                    <input type="text" id="input-license-key" placeholder="{{ __('Nhập mã bản quyền...') }}" value="{{ $licenseKey ?? '' }}"
                           class="px-4 py-2.5 text-xs border border-gray-200 dark:border-slate-700 rounded-2xl bg-white dark:bg-slate-800 focus:ring-2 focus:ring-shopee/20 focus:border-shopee w-full lg:w-64 focus:outline-none">
                    <button type="button" id="btn-save-license"
                            class="px-4 py-2.5 bg-shopee hover:bg-shopee-dark text-white font-bold rounded-2xl text-xs transition-all shadow-lg shadow-shopee/15 whitespace-nowrap">
                        {{ __('Kích hoạt') }}
                    </button>
                </div>
            </div>
        </div>

        {{-- Thông báo tính năng bị khóa --}}
        <div class="flex flex-col items-center justify-center py-16 text-center">
            <div class="p-5 bg-gray-100 dark:bg-slate-800 text-gray-400 dark:text-slate-500 rounded-2xl mb-6">
                <i data-lucide="lock" class="w-12 h-12"></i>
            </div>
            <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-2">{{ __('Cần kích hoạt bản quyền') }}</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 max-w-md">{{ __('Thông tin trạng thái hệ thống chỉ khả dụng sau khi kích hoạt giấy phép bản quyền hợp lệ.') }}</p>
        </div>
    </div>

    {{-- Script xử lý lưu license key --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const btnSaveLicense = document.getElementById('btn-save-license');
            if (btnSaveLicense) {
                btnSaveLicense.addEventListener('click', async function() {
                    const inputLicense = document.getElementById('input-license-key');
                    const licenseKey = inputLicense.value.trim();

                    if (!licenseKey) {
                        alert('{{ __("Vui lòng nhập mã bản quyền.") }}');
                        inputLicense.focus();
                        return;
                    }

                    btnSaveLicense.disabled = true;
                    btnSaveLicense.textContent = '{{ __("Đang xác thực...") }}';

                    try {
                        // Gọi API lưu license (tái sử dụng route save-license của UpdateController)
                        const res = await fetch('{{ route("admin.update.save-license") }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({ license_key: licenseKey })
                        });

                        const data = await res.json();

                        if (res.ok && data.status === 'success') {
                            // Reload trang để kiểm tra license mới
                            window.location.reload();
                        } else {
                            document.getElementById('license-alert-title').textContent = '{{ __("Lỗi kích hoạt") }}';
                            document.getElementById('license-alert-desc').textContent = data.message || '{{ __("Đã xảy ra lỗi không xác định.") }}';
                        }
                    } catch (err) {
                        document.getElementById('license-alert-desc').textContent = '{{ __("Lỗi kết nối. Vui lòng thử lại.") }}';
                    } finally {
                        btnSaveLicense.disabled = false;
                        btnSaveLicense.textContent = '{{ __("Kích hoạt") }}';
                    }
                });
            }
        });
    </script>
    @else

    {{-- Nếu DEMO mode đang bật → hiển thị thông báo thay vì nội dung hệ thống nhạy cảm --}}
    @if(config('app.demo'))
    <div class="flex flex-col items-center justify-center py-20 text-center">
        <div class="p-5 bg-amber-50 dark:bg-amber-950/20 text-amber-500 rounded-2xl mb-6">
            <i data-lucide="shield-alert" class="w-12 h-12"></i>
        </div>
        <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-2">{{ __('Chức năng bị giới hạn trong chế độ Demo') }}</h2>
        <p class="text-sm text-gray-500 dark:text-gray-400 max-w-md">{{ __('Thông tin trạng thái hệ thống (CPU, RAM, Disk, Database, IP Server...) không được hiển thị trong môi trường Demo để bảo vệ an toàn máy chủ.') }}</p>
    </div>
    @else
    <div class="flex flex-wrap items-center gap-2">
        {{-- APP_ENV --}}
        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-[11px] font-bold border
            {{ $appInfo['app_env'] === 'production' ? 'bg-green-50 text-green-700 border-green-200 dark:bg-green-950/30 dark:text-green-400 dark:border-green-800/50' : 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-950/30 dark:text-amber-400 dark:border-amber-800/50' }}">
            <span class="w-2 h-2 rounded-full {{ $appInfo['app_env'] === 'production' ? 'bg-green-500' : 'bg-amber-500' }} animate-pulse"></span>
            APP_ENV: {{ strtoupper($appInfo['app_env']) }}
        </span>

        {{-- APP_DEBUG --}}
        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-[11px] font-bold border
            {{ $appInfo['app_debug'] ? 'bg-red-50 text-red-700 border-red-200 dark:bg-red-950/30 dark:text-red-400 dark:border-red-800/50' : 'bg-green-50 text-green-700 border-green-200 dark:bg-green-950/30 dark:text-green-400 dark:border-green-800/50' }}">
            <i data-lucide="{{ $appInfo['app_debug'] ? 'bug' : 'shield-check' }}" class="w-3.5 h-3.5"></i>
            DEBUG: {{ $appInfo['app_debug'] ? 'ON' : 'OFF' }}
        </span>

        {{-- APP_DEMO --}}
        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-[11px] font-bold border
            {{ $appInfo['app_demo'] ? 'bg-purple-50 text-purple-700 border-purple-200 dark:bg-purple-950/30 dark:text-purple-400 dark:border-purple-800/50' : 'bg-gray-50 text-gray-500 border-gray-200 dark:bg-slate-800/50 dark:text-slate-400 dark:border-slate-700' }}">
            <i data-lucide="{{ $appInfo['app_demo'] ? 'play-circle' : 'circle-off' }}" class="w-3.5 h-3.5"></i>
            DEMO: {{ $appInfo['app_demo'] ? 'ON' : 'OFF' }}
        </span>

        {{-- PHP --}}
        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-[11px] font-bold bg-blue-50 text-blue-600 border border-blue-200 dark:bg-blue-950/30 dark:text-blue-400 dark:border-blue-800/50">
            <i data-lucide="code" class="w-3.5 h-3.5"></i>
            PHP {{ $phpInfo['version'] }}
        </span>

        {{-- Laravel --}}
        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-[11px] font-bold bg-rose-50 text-rose-600 border border-rose-200 dark:bg-rose-950/30 dark:text-rose-400 dark:border-rose-800/50">
            <i data-lucide="layers" class="w-3.5 h-3.5"></i>
            Laravel {{ $laravelInfo['version'] }}
        </span>

        {{-- Cron Job --}}
        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-[11px] font-bold border
            @if(!$cronInfo['last_run'])
                bg-gray-50 text-gray-500 border-gray-200 dark:bg-slate-800/50 dark:text-slate-400 dark:border-slate-700
            @elseif($cronInfo['active'])
                bg-green-50 text-green-700 border-green-200 dark:bg-green-950/30 dark:text-green-400 dark:border-green-800/50
            @else
                bg-red-50 text-red-700 border-red-200 dark:bg-red-950/30 dark:text-red-400 dark:border-red-800/50
            @endif
        ">
            <i data-lucide="{{ $cronInfo['active'] ? 'timer' : ($cronInfo['last_run'] ? 'timer-off' : 'help-circle') }}" class="w-3.5 h-3.5"></i>
            CRON:
            @if(!$cronInfo['last_run'])
                {{ __('Chưa cấu hình') }}
            @elseif($cronInfo['active'])
                {{ __('Hoạt động') }}
                <span class="text-[9px] font-medium opacity-75">({{ $cronInfo['time_label'] }})</span>
            @else
                {{ __('Ngừng') }}
                <span class="text-[9px] font-medium opacity-75">({{ $cronInfo['time_label'] }})</span>
            @endif
        </span>
    </div>

    {{-- Alert cảnh báo thiếu quyền đọc ghi thư mục/file --}}
    @if($hasPermissionError ?? false)
    <div class="bg-gradient-to-r from-red-50 to-orange-50 dark:from-red-950/10 dark:to-orange-950/5 p-6 rounded-3xl border border-red-200/60 dark:border-red-950/30 shadow-sm flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6">
        <div class="flex items-start gap-4">
            <div class="p-3 bg-red-100 dark:bg-red-950/30 text-red-500 rounded-2xl shrink-0">
                <i data-lucide="shield-alert" class="w-6 h-6 animate-bounce"></i>
            </div>
            <div>
                <h3 class="text-sm font-bold text-red-600 dark:text-red-400">
                    {{ __('⚠️ Cảnh báo: Lỗi Phân Quyền Thư Mục & File') }}
                </h3>
                <p class="text-xs text-gray-600 dark:text-gray-400 leading-relaxed mt-1">
                    {{ __('Hệ thống phát hiện một số thư mục hoặc file quan trọng chưa có quyền đọc ghi, điều này có thể gây lỗi nghiêm trọng khi vận hành (lỗi lưu cache, ghi log, tải ảnh hoặc sửa ngôn ngữ).') }}
                </p>
                <div class="mt-3 flex flex-wrap gap-2">
                    @foreach($permissionsInfo as $path => $perm)
                        @if(!$perm['writable'])
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-xl text-[10px] font-bold bg-red-100 text-red-700 dark:bg-red-950/40 dark:text-red-400 border border-red-200/50 dark:border-red-900/30">
                                <i data-lucide="folder-x" class="w-3.5 h-3.5"></i>
                                {{ $path }}
                            </span>
                        @endif
                    @endforeach
                </div>
            </div>
        </div>
        <div class="w-full lg:w-auto shrink-0 space-y-2 bg-slate-950 p-4 rounded-2xl border border-slate-800 dark:border-slate-800/80 font-mono text-[11px] text-slate-300">
            <span class="block text-[10px] font-bold text-gray-500 dark:text-gray-400">{{ __('👉 Lệnh SSH khắc phục nhanh (Chạy với quyền root):') }}</span>
            <code class="block text-emerald-400 select-all break-all" style="font-family: monospace;">chown -R www:www {{ base_path() }} && chmod -R 775 {{ base_path('storage') }} {{ base_path('bootstrap/cache') }} {{ base_path('public/uploads') }} {{ base_path('lang') }} && touch {{ base_path('.env') }} && chown www:www {{ base_path('.env') }} && chmod 664 {{ base_path('.env') }}</code>
        </div>
    </div>
    @endif

    {{-- Alert cảnh báo thiếu PHP extension bắt buộc --}}
    @if($hasExtensionError ?? false)
    <div class="bg-gradient-to-r from-red-50 to-orange-50 dark:from-red-950/10 dark:to-orange-950/5 p-6 rounded-3xl border border-red-200/60 dark:border-red-950/30 shadow-sm flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6">
        <div class="flex items-start gap-4">
            <div class="p-3 bg-red-100 dark:bg-red-950/30 text-red-500 rounded-2xl shrink-0">
                <i data-lucide="cpu" class="w-6 h-6 animate-bounce"></i>
            </div>
            <div>
                <h3 class="text-sm font-bold text-red-600 dark:text-red-400">
                    {{ __('⚠️ Cảnh báo: Thiếu Phần Mở Rộng PHP Bắt Buộc') }}
                </h3>
                <p class="text-xs text-gray-600 dark:text-gray-400 leading-relaxed mt-1">
                    {{ __('Hệ thống phát hiện máy chủ hiện tại đang thiếu một số phần mở rộng (PHP extensions) bắt buộc. Điều này có thể làm ngừng hoạt động của một số tính năng cốt lõi như kết nối database, xử lý ảnh, hoặc cron job chạy ngầm.') }}
                </p>
                <div class="mt-3 flex flex-wrap gap-2">
                    @foreach($phpInfo['extensions'] as $ext => $info)
                        @if($info['required'] && !$info['loaded'])
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-xl text-[10px] font-bold bg-red-100 text-red-700 dark:bg-red-950/40 dark:text-red-400 border border-red-200/50 dark:border-red-900/30">
                                <i data-lucide="x-circle" class="w-3.5 h-3.5"></i>
                                {{ $ext }}
                            </span>
                        @endif
                    @endforeach
                </div>
            </div>
        </div>
        <div class="w-full lg:w-auto shrink-0 space-y-2 bg-slate-950 p-4 rounded-2xl border border-slate-800 dark:border-slate-800/80 font-mono text-[11px] text-slate-300 max-w-sm">
            <span class="block text-[10px] font-bold text-gray-500 dark:text-gray-400">{{ __('👉 Hướng dẫn khắc phục:') }}</span>
            <p class="text-xs text-slate-300 leading-relaxed">{{ __('Vui lòng truy cập trang quản lý Hosting/VPS (aaPanel, cPanel, DirectAdmin) và kích hoạt/cài đặt các phần mở rộng PHP bị thiếu ở trên, sau đó khởi động lại PHP.') }}</p>
        </div>
    </div>
    @endif

    <!-- ===== HÀNG 2: TÀI NGUYÊN MÁY CHỦ (CPU / RAM / DISK) ===== -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- CPU -->
        <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 shadow-sm border border-gray-100 dark:border-slate-800/50">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-2">
                    <div class="p-2.5 bg-blue-50 dark:bg-blue-950/30 text-blue-600 dark:text-blue-400 rounded-xl">
                        <i data-lucide="cpu" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-gray-900 dark:text-white">CPU</h3>
                        @if($resources['cpu_cores'])
                            <span class="text-[10px] text-gray-400">{{ $resources['cpu_cores'] }} {{ __('cores') }}</span>
                        @endif
                    </div>
                </div>
            </div>
            @if($resources['cpu_usage'] !== null)
                @php
                    // Tính phần trăm CPU dựa trên load average / số cores
                    $cpuPercent = $resources['cpu_cores'] ? round(($resources['cpu_usage'] / $resources['cpu_cores']) * 100, 1) : null;
                    $cpuColor = ($cpuPercent ?? 0) > 80 ? 'red' : (($cpuPercent ?? 0) > 50 ? 'amber' : 'green');
                @endphp
                <div class="space-y-2">
                    <div class="flex items-end justify-between">
                        <span class="text-3xl font-extrabold text-gray-900 dark:text-white">{{ $resources['cpu_usage'] }}</span>
                        <span class="text-xs text-gray-400">{{ __('Load Avg 1m') }}</span>
                    </div>
                    @if($cpuPercent !== null)
                    <div class="w-full bg-gray-100 dark:bg-slate-800 rounded-full h-2.5 overflow-hidden">
                        <div class="h-2.5 rounded-full transition-all duration-500 bg-{{ $cpuColor }}-500" style="width: {{ min($cpuPercent, 100) }}%"></div>
                    </div>
                    <span class="text-[10px] text-gray-400">{{ $cpuPercent }}% {{ __('sử dụng') }}</span>
                    @endif
                </div>
            @else
                <p class="text-xs text-gray-400 italic mt-2">{{ __('Không thể đo CPU trên môi trường này') }}</p>
            @endif
        </div>

        <!-- RAM -->
        <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 shadow-sm border border-gray-100 dark:border-slate-800/50">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-2">
                    <div class="p-2.5 bg-purple-50 dark:bg-purple-950/30 text-purple-600 dark:text-purple-400 rounded-xl">
                        <i data-lucide="memory-stick" class="w-5 h-5"></i>
                    </div>
                    <h3 class="text-sm font-bold text-gray-900 dark:text-white">RAM</h3>
                </div>
            </div>
            @if($resources['ram_total'] !== null)
                @php
                    $ramColor = $resources['ram_percent'] > 80 ? 'red' : ($resources['ram_percent'] > 50 ? 'amber' : 'green');
                @endphp
                <div class="space-y-2">
                    <div class="flex items-end justify-between">
                        <span class="text-3xl font-extrabold text-gray-900 dark:text-white">{{ $resources['ram_used'] }} <span class="text-sm font-bold text-gray-400">GB</span></span>
                        <span class="text-xs text-gray-400">/ {{ $resources['ram_total'] }} GB</span>
                    </div>
                    <div class="w-full bg-gray-100 dark:bg-slate-800 rounded-full h-2.5 overflow-hidden">
                        <div class="h-2.5 rounded-full transition-all duration-500 bg-{{ $ramColor }}-500" style="width: {{ $resources['ram_percent'] }}%"></div>
                    </div>
                    <span class="text-[10px] text-gray-400">{{ $resources['ram_percent'] }}% {{ __('sử dụng') }}</span>
                </div>
            @else
                <p class="text-xs text-gray-400 italic mt-2">{{ __('Không thể đo RAM trên môi trường này') }}</p>
            @endif
        </div>

        <!-- DISK -->
        <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 shadow-sm border border-gray-100 dark:border-slate-800/50">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-2">
                    <div class="p-2.5 bg-teal-50 dark:bg-teal-950/30 text-teal-600 dark:text-teal-400 rounded-xl">
                        <i data-lucide="hard-drive" class="w-5 h-5"></i>
                    </div>
                    <h3 class="text-sm font-bold text-gray-900 dark:text-white">{{ __('Ổ đĩa') }}</h3>
                </div>
            </div>
            @if($resources['disk_total'] !== null)
                @php
                    $diskColor = $resources['disk_percent'] > 85 ? 'red' : ($resources['disk_percent'] > 60 ? 'amber' : 'green');
                @endphp
                <div class="space-y-2">
                    <div class="flex items-end justify-between">
                        <span class="text-3xl font-extrabold text-gray-900 dark:text-white">{{ $resources['disk_used'] }} <span class="text-sm font-bold text-gray-400">GB</span></span>
                        <span class="text-xs text-gray-400">/ {{ $resources['disk_total'] }} GB</span>
                    </div>
                    <div class="w-full bg-gray-100 dark:bg-slate-800 rounded-full h-2.5 overflow-hidden">
                        <div class="h-2.5 rounded-full transition-all duration-500 bg-{{ $diskColor }}-500" style="width: {{ $resources['disk_percent'] }}%"></div>
                    </div>
                    <div class="flex justify-between text-[10px] text-gray-400">
                        <span>{{ $resources['disk_percent'] }}% {{ __('sử dụng') }}</span>
                        <span>{{ $resources['disk_free'] }} GB {{ __('trống') }}</span>
                    </div>
                </div>
            @else
                <p class="text-xs text-gray-400 italic mt-2">{{ __('Không thể đo dung lượng ổ đĩa') }}</p>
            @endif
        </div>
    </div>

    <!-- ===== HÀNG 3: CHI TIẾT HỆ THỐNG ===== -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        <!-- Thông tin Ứng dụng & Máy chủ -->
        <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 shadow-sm border border-gray-100 dark:border-slate-800/50 space-y-5">
            <h3 class="text-sm font-bold text-gray-900 dark:text-white flex items-center gap-2 pb-3 border-b border-gray-100 dark:border-slate-800">
                <i data-lucide="server" class="w-4.5 h-4.5 text-gray-400"></i>
                {{ __('Thông Tin Máy Chủ') }}
            </h3>
            <div class="space-y-3">
                @php
                    $serverRows = [
                        ['icon' => 'globe',       'label' => __('Tên miền'),         'value' => $appInfo['app_url']],
                        ['icon' => 'monitor',     'label' => __('Hệ điều hành'),     'value' => $serverInfo['os']],
                        ['icon' => 'server',      'label' => __('Hostname'),          'value' => $serverInfo['hostname']],
                        ['icon' => 'wifi',        'label' => __('IP Server'),         'value' => $serverInfo['server_ip']],
                        ['icon' => 'box',         'label' => __('Web Server'),        'value' => $serverInfo['server_software']],
                        ['icon' => 'folder-root', 'label' => __('Document Root'),     'value' => $serverInfo['document_root']],
                        ['icon' => 'clock',       'label' => __('Múi giờ'),           'value' => $appInfo['timezone']],
                        ['icon' => 'languages',   'label' => __('Ngôn ngữ mặc định'),'value' => strtoupper($appInfo['app_locale'])],
                    ];
                @endphp
                @foreach($serverRows as $row)
                    <div class="flex items-center justify-between py-2 {{ !$loop->last ? 'border-b border-gray-50 dark:border-slate-800/50' : '' }}">
                        <span class="inline-flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                            <i data-lucide="{{ $row['icon'] }}" class="w-3.5 h-3.5 text-gray-300 dark:text-slate-600"></i>
                            {{ $row['label'] }}
                        </span>
                        <span class="text-xs font-bold text-gray-800 dark:text-gray-200 text-right max-w-[60%] truncate" title="{{ $row['value'] }}">{{ $row['value'] }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Thông tin PHP -->
        <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 shadow-sm border border-gray-100 dark:border-slate-800/50 space-y-5">
            <h3 class="text-sm font-bold text-gray-900 dark:text-white flex items-center gap-2 pb-3 border-b border-gray-100 dark:border-slate-800">
                <i data-lucide="file-code" class="w-4.5 h-4.5 text-gray-400"></i>
                {{ __('Cấu Hình PHP') }}
            </h3>
            <div class="space-y-3">
                @php
                    $phpRows = [
                        ['icon' => 'hash',           'label' => __('Phiên bản PHP'),          'value' => $phpInfo['version']],
                        ['icon' => 'database',       'label' => 'SAPI',                       'value' => strtoupper($phpInfo['sapi'])],
                        ['icon' => 'brain',          'label' => __('Giới hạn bộ nhớ'),        'value' => $phpInfo['memory_limit']],
                        ['icon' => 'timer',          'label' => __('Thời gian thực thi tối đa'), 'value' => $phpInfo['max_execution_time']],
                        ['icon' => 'upload',         'label' => __('Upload tối đa'),          'value' => $phpInfo['upload_max_size']],
                        ['icon' => 'file-up',        'label' => __('POST tối đa'),            'value' => $phpInfo['post_max_size']],
                    ];
                @endphp
                @foreach($phpRows as $row)
                    <div class="flex items-center justify-between py-2 {{ !$loop->last ? 'border-b border-gray-50 dark:border-slate-800/50' : '' }}">
                        <span class="inline-flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                            <i data-lucide="{{ $row['icon'] }}" class="w-3.5 h-3.5 text-gray-300 dark:text-slate-600"></i>
                            {{ $row['label'] }}
                        </span>
                        <span class="text-xs font-bold text-gray-800 dark:text-gray-200">{{ $row['value'] }}</span>
                    </div>
                @endforeach
            </div>

            <!-- PHP Extensions -->
            <div class="pt-3 border-t border-gray-100 dark:border-slate-800 space-y-4">
                <div>
                    <h4 class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-2">{{ __('Phần mở rộng bắt buộc (Required Extensions)') }}</h4>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                        @foreach($phpInfo['extensions'] as $ext => $info)
                            @if($info['required'])
                                <div class="flex items-center justify-between p-2 rounded-xl border border-gray-50 dark:border-slate-800 bg-gray-50/30 dark:bg-slate-900/20">
                                    <div class="flex flex-col min-w-0 pr-2">
                                        <span class="text-xs font-bold text-gray-800 dark:text-gray-200 font-mono">{{ $ext }}</span>
                                        <span class="text-[10px] text-gray-400 dark:text-slate-500 truncate" title="{{ $info['description'] }}">{{ $info['description'] }}</span>
                                    </div>
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-lg text-[9px] font-bold border shrink-0
                                        {{ $info['loaded'] 
                                            ? 'bg-green-50 text-green-600 border-green-100 dark:bg-green-950/20 dark:text-green-400 dark:border-green-900/30' 
                                            : 'bg-red-50 text-red-600 border-red-100 dark:bg-red-950/20 dark:text-red-400 dark:border-red-900/30' }}">
                                        <i data-lucide="{{ $info['loaded'] ? 'check' : 'x' }}" class="w-2.5 h-2.5"></i>
                                        {{ $info['loaded'] ? __('Đã cài') : __('Thiếu') }}
                                    </span>
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>

                <div>
                    <h4 class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-2">{{ __('Phần mở rộng khuyến nghị (Recommended Extensions)') }}</h4>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                        @foreach($phpInfo['extensions'] as $ext => $info)
                            @if(!$info['required'])
                                <div class="flex items-center justify-between p-2 rounded-xl border border-gray-50 dark:border-slate-800 bg-gray-50/30 dark:bg-slate-900/20">
                                    <div class="flex flex-col min-w-0 pr-2">
                                        <span class="text-xs font-bold text-gray-800 dark:text-gray-200 font-mono">{{ $ext }}</span>
                                        <span class="text-[10px] text-gray-400 dark:text-slate-500 truncate" title="{{ $info['description'] }}">{{ $info['description'] }}</span>
                                    </div>
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-lg text-[9px] font-bold border shrink-0
                                        {{ $info['loaded'] 
                                            ? 'bg-green-50 text-green-600 border-green-100 dark:bg-green-950/20 dark:text-green-400 dark:border-green-900/30' 
                                            : 'bg-amber-50 text-amber-600 border-amber-100 dark:bg-amber-950/20 dark:text-amber-400 dark:border-amber-900/30' }}">
                                        <i data-lucide="{{ $info['loaded'] ? 'check' : 'x' }}" class="w-2.5 h-2.5"></i>
                                        {{ $info['loaded'] ? __('Đã cài') : __('Thiếu') }}
                                    </span>
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <!-- Thông tin Database -->
        <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 shadow-sm border border-gray-100 dark:border-slate-800/50 space-y-5">
            <h3 class="text-sm font-bold text-gray-900 dark:text-white flex items-center gap-2 pb-3 border-b border-gray-100 dark:border-slate-800">
                <i data-lucide="database" class="w-4.5 h-4.5 text-gray-400"></i>
                {{ __('Thông Tin Database') }}
            </h3>
            <div class="space-y-3">
                @php
                    $dbRows = [
                        ['icon' => 'plug',           'label' => __('Driver'),        'value' => strtoupper($dbInfo['driver'])],
                        ['icon' => 'server',         'label' => __('Host'),          'value' => $dbInfo['host']],
                        ['icon' => 'database',       'label' => __('Database'),      'value' => $dbInfo['database_name']],
                        ['icon' => 'hash',           'label' => __('Phiên bản'),     'value' => $dbInfo['version'] ?? 'N/A'],
                        ['icon' => 'table-2',        'label' => __('Tổng số bảng'),  'value' => $dbInfo['total_tables']],
                        ['icon' => 'hard-drive',     'label' => __('Dung lượng'),    'value' => $dbInfo['total_size_mb'] . ' MB'],
                    ];
                @endphp
                @foreach($dbRows as $row)
                    <div class="flex items-center justify-between py-2 {{ !$loop->last ? 'border-b border-gray-50 dark:border-slate-800/50' : '' }}">
                        <span class="inline-flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                            <i data-lucide="{{ $row['icon'] }}" class="w-3.5 h-3.5 text-gray-300 dark:text-slate-600"></i>
                            {{ $row['label'] }}
                        </span>
                        <span class="text-xs font-bold text-gray-800 dark:text-gray-200">{{ $row['value'] }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Cache, Session & Laravel -->
        <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 shadow-sm border border-gray-100 dark:border-slate-800/50 space-y-5">
            <h3 class="text-sm font-bold text-gray-900 dark:text-white flex items-center gap-2 pb-3 border-b border-gray-100 dark:border-slate-800">
                <i data-lucide="zap" class="w-4.5 h-4.5 text-gray-400"></i>
                {{ __('Cache, Session & Laravel') }}
            </h3>
            <div class="space-y-3">
                @php
                    $cacheRows = [
                        ['icon' => 'database',  'label' => __('Cache Driver'),   'value' => strtoupper($cacheInfo['cache_driver'])],
                        ['icon' => 'key',       'label' => __('Session Driver'), 'value' => strtoupper($cacheInfo['session_driver'])],
                        ['icon' => 'list',      'label' => __('Queue Driver'),   'value' => strtoupper($cacheInfo['queue_driver'])],
                    ];
                @endphp
                @foreach($cacheRows as $row)
                    <div class="flex items-center justify-between py-2 border-b border-gray-50 dark:border-slate-800/50">
                        <span class="inline-flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                            <i data-lucide="{{ $row['icon'] }}" class="w-3.5 h-3.5 text-gray-300 dark:text-slate-600"></i>
                            {{ $row['label'] }}
                        </span>
                        <span class="text-xs font-bold text-gray-800 dark:text-gray-200">{{ $row['value'] }}</span>
                    </div>
                @endforeach
            </div>

            <!-- Laravel Optimization Status -->
            <div class="pt-3 border-t border-gray-100 dark:border-slate-800">
                <h4 class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-3">{{ __('Trạng thái tối ưu hóa Laravel') }}</h4>
                <div class="space-y-2">
                    @php
                        $optimizations = [
                            ['label' => __('Config Cache'),  'active' => $laravelInfo['config_cached'], 'cmd' => 'php artisan config:cache'],
                            ['label' => __('Route Cache'),   'active' => $laravelInfo['route_cached'],  'cmd' => 'php artisan route:cache'],
                            ['label' => __('View Cache'),    'active' => $laravelInfo['view_cached'],   'cmd' => 'php artisan view:cache'],
                        ];
                    @endphp
                    @foreach($optimizations as $opt)
                        <div class="flex items-center justify-between py-1.5">
                            <span class="text-xs text-gray-600 dark:text-gray-400">{{ $opt['label'] }}</span>
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[9px] font-bold
                                {{ $opt['active'] 
                                    ? 'bg-green-50 text-green-600 border border-green-100 dark:bg-green-950/20 dark:text-green-400 dark:border-green-900/30' 
                                    : 'bg-gray-100 text-gray-500 border border-gray-200 dark:bg-slate-800 dark:text-slate-400 dark:border-slate-700' }}">
                                <i data-lucide="{{ $opt['active'] ? 'check-circle' : 'circle' }}" class="w-2.5 h-2.5"></i>
                                {{ $opt['active'] ? __('Đã bật') : __('Chưa bật') }}
                            </span>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Dung lượng Storage -->
            <div class="pt-3 border-t border-gray-100 dark:border-slate-800">
                <h4 class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-3">{{ __('Dung lượng thư mục') }}</h4>
                <div class="space-y-2">
                    @foreach($storageInfo as $name => $info)
                        @php
                            $sizeMB = round($info['size'] / 1024 / 1024, 2);
                            $sizeLabel = $sizeMB >= 1024 ? round($sizeMB / 1024, 2) . ' GB' : $sizeMB . ' MB';
                        @endphp
                        <div class="flex items-center justify-between py-1.5">
                            <span class="inline-flex items-center gap-1.5 text-xs text-gray-600 dark:text-gray-400">
                                <i data-lucide="folder" class="w-3 h-3 text-gray-300"></i>
                                {{ ucfirst($name) }}
                            </span>
                            <span class="text-xs font-bold {{ $sizeMB > 500 ? 'text-amber-600' : 'text-gray-700 dark:text-gray-300' }}">{{ $sizeLabel }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Quyền Thư Mục & File -->
        <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 shadow-sm border border-gray-100 dark:border-slate-800/50 space-y-5">
            <h3 class="text-sm font-bold text-gray-900 dark:text-white flex items-center gap-2 pb-3 border-b border-gray-100 dark:border-slate-800">
                <i data-lucide="shield-check" class="w-4.5 h-4.5 text-gray-400"></i>
                {{ __('Quyền Thư Mục & File') }}
            </h3>
            <div class="space-y-3">
                @foreach($permissionsInfo ?? [] as $path => $perm)
                    <div class="flex items-center justify-between py-2 border-b border-gray-50 dark:border-slate-800/50">
                        <div class="flex flex-col gap-0.5">
                            <span class="text-xs font-bold text-gray-800 dark:text-gray-200 font-mono">{{ $path }}</span>
                            <span class="text-[10px] text-gray-400">{{ __($perm['description']) }}</span>
                        </div>
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-xl text-[10px] font-bold border
                            {{ $perm['writable'] 
                                ? 'bg-green-50 text-green-700 border-green-200 dark:bg-green-950/20 dark:text-green-400 dark:border-green-800/30' 
                                : 'bg-red-50 text-red-700 border-red-200 dark:bg-red-950/20 dark:text-red-400 dark:border-red-800/30' }}">
                            <span class="w-1.5 h-1.5 rounded-full {{ $perm['writable'] ? 'bg-green-500' : 'bg-red-500' }}"></span>
                            {{ $perm['writable'] ? __('Ghi được') : __('Không ghi được') }}
                        </span>
                    </div>
                @endforeach
            </div>
            
            <div class="pt-3 border-t border-gray-100 dark:border-slate-800">
                <h4 class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-2">{{ __('Hướng dẫn phân quyền trên máy chủ Linux/aaPanel') }}</h4>
                <p class="text-xs text-gray-500 dark:text-gray-400 leading-relaxed mb-3">
                    {{ __('Nhằm đảm bảo Web server ghi được các file log, cache và ảnh upload, chủ sở hữu (owner) của thư mục dự án cần được chuyển về cho user chạy PHP (mặc định là www trên aaPanel) và phân quyền 775.') }}
                </p>
                <div class="space-y-2 bg-slate-950 p-3 rounded-2xl border border-slate-800 font-mono text-[11px] text-slate-300">
                    <span class="text-slate-500 block"># Chuyển owner và phân quyền đệ quy:</span>
                    <code class="block text-emerald-400 select-all break-all" style="font-family: monospace;">chown -R www:www {{ base_path() }} && chmod -R 775 {{ base_path('storage') }} {{ base_path('bootstrap/cache') }} {{ base_path('public/uploads') }} {{ base_path('lang') }} && touch {{ base_path('.env') }} && chown www:www {{ base_path('.env') }} && chmod 664 {{ base_path('.env') }}</code>
                </div>
            </div>
        </div>
    </div>
    @endif
    @endif {{-- Đóng khối kiểm tra giấy phép bản quyền --}}
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Xử lý sự kiện click nút Tối ưu hóa (Bật Cache)
        const btnOptimizeCache = document.getElementById('btn-optimize-cache');
        if (btnOptimizeCache) {
            btnOptimizeCache.addEventListener('click', async function() {
                const result = await Swal.fire({
                    title: '{{ __('Xác nhận tối ưu hóa hệ thống?') }}',
                    text: '{{ __('Hệ thống sẽ tiến hành biên dịch và lưu cache cấu hình (Config Cache) cùng định tuyến (Route Cache) để đạt hiệu năng và tốc độ tải trang nhanh nhất.') }}',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#10B981',
                    cancelButtonColor: '#6B7280',
                    confirmButtonText: '⚡ {{ __('Bật tối ưu hóa') }}',
                    cancelButtonText: '{{ __('Hủy') }}',
                    customClass: {
                        container: 'admin-modal',
                        confirmButton: 'inline-flex justify-center items-center gap-2 rounded-xl bg-emerald-500 px-5 py-2.5 text-xs font-bold text-white hover:bg-emerald-600 transition shadow-lg mr-3',
                        cancelButton: 'inline-flex justify-center items-center gap-2 rounded-xl bg-gray-500 px-5 py-2.5 text-xs font-bold text-white hover:bg-gray-600 transition shadow-lg'
                    },
                    buttonsStyling: false
                });

                if (!result.isConfirmed) return;

                Swal.showLoading();
                try {
                    const res = await fetch('{{ route("admin.system_status.optimize") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json',
                        },
                    });
                    const data = await res.json();
                    if (data.status === 'success') {
                        Swal.fire({
                            icon: 'success',
                            title: '{{ __('Thành công') }}',
                            text: data.message,
                            timer: 1500,
                            showConfirmButton: false
                        });
                        setTimeout(() => window.location.reload(), 1500);
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: '{{ __('Lỗi') }}',
                            text: data.message
                        });
                    }
                } catch (e) {
                    Swal.fire({
                        icon: 'error',
                        title: '{{ __('Lỗi') }}',
                        text: '{{ __('Không thể kết nối đến máy chủ.') }}'
                    });
                }
            });
        }

        // Xử lý sự kiện click nút Xóa bộ nhớ đệm
        const btnClearCache = document.getElementById('btn-clear-cache');
        if (btnClearCache) {
            btnClearCache.addEventListener('click', async function() {
                const result = await Swal.fire({
                    title: '{{ __('Xác nhận xóa bộ nhớ đệm?') }}',
                    text: '{{ __('Hệ thống sẽ thực hiện xóa toàn bộ cache ứng dụng, cấu hình, route và view để làm sạch dữ liệu tạm.') }}',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#ff5722',
                    cancelButtonColor: '#6B7280',
                    confirmButtonText: '🧹 {{ __('Xóa cache ngay') }}',
                    cancelButtonText: '{{ __('Hủy') }}',
                    customClass: {
                        container: 'admin-modal',
                        confirmButton: 'inline-flex justify-center items-center gap-2 rounded-xl bg-shopee px-5 py-2.5 text-xs font-bold text-white hover:bg-shopee-dark transition shadow-lg mr-3',
                        cancelButton: 'inline-flex justify-center items-center gap-2 rounded-xl bg-gray-500 px-5 py-2.5 text-xs font-bold text-white hover:bg-gray-600 transition shadow-lg'
                    },
                    buttonsStyling: false
                });

                if (!result.isConfirmed) return;

                Swal.showLoading();
                try {
                    const res = await fetch('{{ route("admin.system_status.clear_cache") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json',
                        },
                    });
                    const data = await res.json();
                    if (data.status === 'success') {
                        Swal.fire({
                            icon: 'success',
                            title: '{{ __('Thành công') }}',
                            text: data.message,
                            timer: 1500,
                            showConfirmButton: false
                        });
                        setTimeout(() => window.location.reload(), 1500);
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: '{{ __('Lỗi') }}',
                            text: data.message
                        });
                    }
                } catch (e) {
                    Swal.fire({
                        icon: 'error',
                        title: '{{ __('Lỗi') }}',
                        text: '{{ __('Không thể kết nối đến máy chủ.') }}'
                    });
                }
            });
        }
    });
</script>
@endsection
