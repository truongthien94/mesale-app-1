@extends('layouts.admin')

@section('title', __('Cập Nhật Phiên Bản') . ' - ' . $siteName)

@section('content')
@php
    $licenseKey = \App\Models\Setting::where('key', 'license_key')->value('value');
@endphp
<div class="space-y-6">
    <!-- Tiêu đề & Mô tả trang cập nhật -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-white dark:bg-slate-900 p-6 rounded-3xl border border-gray-100 dark:border-slate-800/60 shadow-sm">
        <div class="space-y-1">
            <h1 class="text-2xl font-black tracking-tight text-gray-900 dark:text-white flex items-center gap-2.5">
                <span class="p-2 bg-shopee/10 rounded-2xl text-shopee inline-flex">
                    <i data-lucide="refresh-cw" class="w-6 h-6 animate-spin-slow"></i>
                </span>
                {{ __('Cập Nhật Hệ Thống') }}
            </h1>
            <p class="text-xs text-gray-500 dark:text-gray-400 max-w-xl">
                {{ __('Hệ thống quản lý phiên bản cập nhật, sửa lỗi bảo mật và nâng cấp tính năng mới tự động thông qua máy chủ cập nhật trung gian CMSNT.') }}
            </p>
            {{-- Thêm liên kết nhóm Zalo & Telegram để hỗ trợ khách hàng theo dõi Changelog cập nhật hệ thống --}}
            <div class="flex flex-wrap gap-2.5 pt-2">
                <a href="https://zalo.me/g/idapcx933" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-950/30 hover:bg-blue-100 dark:hover:bg-blue-900/40 rounded-xl transition duration-300">
                    <span class="w-2 h-2 rounded-full bg-blue-500 animate-pulse"></span>
                    <i data-lucide="message-circle" class="w-4 h-4"></i>
                    {{ __('Nhóm Zalo Changelog') }}
                </a>
                <a href="https://t.me/cmsntco" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold text-sky-600 dark:text-sky-400 bg-sky-50 dark:bg-sky-950/30 hover:bg-sky-100 dark:hover:bg-sky-900/40 rounded-xl transition duration-300">
                    <span class="w-2 h-2 rounded-full bg-sky-500 animate-pulse"></span>
                    <i data-lucide="send" class="w-4 h-4"></i>
                    {{ __('Nhóm Telegram Changelog') }}
                </a>
            </div>
        </div>
        
        <!-- Nút mở khóa nhanh khi bị kẹt tiến trình -->
        <button onclick="forceUnlockUpdateDirect()" 
            class="inline-flex items-center gap-2 px-4 py-2.5 text-xs font-bold text-gray-600 dark:text-gray-300 hover:text-shopee dark:hover:text-shopee bg-gray-50 hover:bg-shopee/5 dark:bg-slate-950 dark:hover:bg-slate-900/60 border border-gray-200 dark:border-slate-800/80 rounded-xl transition duration-300 shadow-sm">
            <i data-lucide="unlock" class="w-4 h-4 text-amber-500"></i>
            <span>{{ __('Giải phóng tài nguyên') }}</span>
        </button>
    </div>

    <!-- Card nhập mã bản quyền (license-config-card) -->
    <div class="relative overflow-hidden bg-gradient-to-r from-red-50 to-orange-50 dark:from-red-950/10 dark:to-orange-950/5 p-6 rounded-3xl border border-red-200/60 dark:border-red-950/30 shadow-sm flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6 {{ !empty($licenseKey) ? 'hidden' : '' }}" id="license-config-card">
        <!-- Background Decor -->
        <div class="absolute -right-16 -top-16 w-36 h-36 bg-red-400/10 rounded-full blur-2xl"></div>
        <div class="absolute -left-16 -bottom-16 w-36 h-36 bg-orange-400/10 rounded-full blur-2xl"></div>

        <div class="space-y-2 max-w-2xl relative z-10">
            <div class="flex items-center gap-2.5 text-red-600 dark:text-red-400 font-extrabold text-sm">
                <span class="p-1.5 bg-red-100 dark:bg-red-950/40 rounded-lg text-red-600 dark:text-red-400">
                    <i data-lucide="shield-alert" class="w-5 h-5"></i>
                </span>
                <span id="license-alert-title">{{ __('Yêu cầu Mã bản quyền') }}</span>
            </div>
            <p class="text-xs text-gray-600 dark:text-gray-400 leading-relaxed" id="license-alert-desc">
                {{ __('Hệ thống chưa được cấu hình Mã bản quyền (License Key) hoặc giấy phép hiện tại đã hết hạn/không hợp lệ. Vui lòng cập nhật mã bản quyền của bạn để kích hoạt hệ thống cập nhật tự động.') }}
            </p>
        </div>
        <div class="w-full lg:w-auto flex flex-col sm:flex-row gap-3 shrink-0 relative z-10">
            <input type="text" id="input-license-key" placeholder="{{ __('Nhập mã bản quyền của bạn...') }}" value="{{ $licenseKey }}"
                class="w-full sm:w-80 rounded-2xl border border-red-200 dark:border-red-950/30 bg-white dark:bg-slate-950 px-4 py-3 text-xs text-gray-900 dark:text-white placeholder-gray-400 focus:border-red-400 focus:ring-1 focus:ring-red-400 focus:outline-none transition duration-300 shadow-inner">
            <button type="button" id="btn-save-license"
                class="inline-flex items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-red-500 to-orange-500 hover:from-red-600 hover:to-orange-600 px-6 py-3 text-xs font-black text-white transition duration-300 shadow-md shadow-orange-500/10 hover:shadow-lg hover:shadow-orange-500/20 active:scale-95">
                <i data-lucide="key-round" class="w-4 h-4"></i>
                <span>{{ __('Kích hoạt ngay') }}</span>
            </button>
        </div>
    </div>

    <!-- Grid thông số phiên bản với UI đẳng cấp -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        <!-- Phiên bản hiện tại -->
        <div class="group relative overflow-hidden bg-white dark:bg-slate-900 p-6 rounded-3xl shadow-sm border border-gray-100 dark:border-slate-800/60 transition-all duration-300 hover:shadow-md hover:border-shopee/30">
            <div class="flex items-center justify-between gap-4">
                <div class="space-y-1">
                    <span class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider block">{{ __('Phiên bản hiện tại') }}</span>
                    <div class="text-2xl font-black text-shopee font-mono tracking-tight" id="current-version">
                        {{ \App\Models\Setting::where('key', 'current_version')->value('value') ?: 'v1.0.0' }}
                    </div>
                </div>
                <div class="p-3.5 bg-shopee/5 rounded-2xl text-shopee group-hover:scale-110 transition duration-300">
                    <i data-lucide="package" class="w-6 h-6"></i>
                </div>
            </div>
            <div class="absolute bottom-0 left-0 right-0 h-1 bg-gradient-to-r from-shopee to-orange-400 transform scale-x-0 group-hover:scale-x-100 transition duration-300"></div>
        </div>

        <!-- Phiên bản mới nhất -->
        <div class="group relative overflow-hidden bg-white dark:bg-slate-900 p-6 rounded-3xl shadow-sm border border-gray-100 dark:border-slate-800/60 transition-all duration-300 hover:shadow-md hover:border-blue-500/30">
            <div class="flex items-center justify-between gap-4">
                <div class="space-y-1">
                    <span class="text-[10px] font-bold text-gray-400 dark:text-gray-550 uppercase tracking-wider block">{{ __('Phiên bản mới nhất') }}</span>
                    <div class="text-2xl font-black text-gray-800 dark:text-white font-mono tracking-tight" id="latest-version-text">
                        {{ __('Chưa kiểm tra') }}
                    </div>
                </div>
                <div class="p-3.5 bg-blue-500/5 rounded-2xl text-blue-500 group-hover:scale-110 transition duration-300">
                    <i data-lucide="server" class="w-6 h-6"></i>
                </div>
            </div>
            <div class="absolute bottom-0 left-0 right-0 h-1 bg-gradient-to-r from-blue-500 to-indigo-500 transform scale-x-0 group-hover:scale-x-100 transition duration-300"></div>
        </div>

        <!-- Cập nhật tự động -->
        <div class="group relative overflow-hidden bg-white dark:bg-slate-900 p-6 rounded-3xl shadow-sm border border-gray-100 dark:border-slate-800/60 transition-all duration-300 hover:shadow-md hover:border-purple-500/30">
            <div class="flex items-center justify-between gap-4">
                <div class="space-y-2">
                    <span class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider block">{{ __('Cập nhật tự động') }}</span>
                    <div class="flex items-center gap-3">
                        <span id="auto-update-badge" class="transition-all duration-300">
                            @if(($autoUpdate ?? '1') === '1')
                                <span class="inline-flex items-center rounded-full bg-emerald-50 dark:bg-emerald-950/20 px-2.5 py-0.5 text-[10px] font-bold text-emerald-600 border border-emerald-100 dark:border-emerald-900/30">
                                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-500 mr-1.5 animate-pulse"></span>{{ __('Đang bật') }}
                                </span>
                            @else
                                <span class="inline-flex items-center rounded-full bg-slate-100 dark:bg-slate-800 px-2.5 py-0.5 text-[10px] font-bold text-slate-600 dark:text-slate-400 border border-slate-200 dark:border-slate-700/50">
                                    {{ __('Đang tắt') }}
                                </span>
                            @endif
                        </span>
                        
                        <!-- Toggle Switch -->
                        <label class="relative inline-flex items-center cursor-pointer select-none">
                            <input type="checkbox" id="toggle-auto-update" class="sr-only peer" {{ ($autoUpdate ?? '1') === '1' ? 'checked' : '' }}>
                            <div class="w-9 h-5 bg-gray-200 dark:bg-slate-800 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all dark:border-gray-600 peer-checked:bg-purple-600"></div>
                        </label>
                    </div>
                </div>
                <div class="p-3.5 bg-purple-500/5 rounded-2xl text-purple-500 group-hover:scale-110 transition duration-300">
                    <i data-lucide="refresh-cw" class="w-6 h-6"></i>
                </div>
            </div>
            <div class="absolute bottom-0 left-0 right-0 h-1 bg-gradient-to-r from-purple-500 to-indigo-500 transform scale-x-0 group-hover:scale-x-100 transition duration-300"></div>
        </div>

        <!-- Cập nhật lần cuối -->
        <div class="group relative overflow-hidden bg-white dark:bg-slate-900 p-6 rounded-3xl shadow-sm border border-gray-100 dark:border-slate-800/60 transition-all duration-300 hover:shadow-md hover:border-emerald-500/30">
            <div class="flex items-center justify-between gap-4">
                <div class="space-y-1">
                    <span class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider block">{{ __('Cập nhật lần cuối') }}</span>
                    <div class="text-base font-extrabold text-gray-700 dark:text-gray-300 pt-1 tracking-tight">
                        @php
                            $lastUpdate = \App\Models\Setting::where('key', 'last_update_at')->value('value');
                        @endphp
                        {{ $lastUpdate ? \Carbon\Carbon::parse($lastUpdate)->diffForHumans() : __('Chưa rõ') }}
                    </div>
                </div>
                <div class="p-3.5 bg-emerald-500/5 rounded-2xl text-emerald-500 group-hover:scale-110 transition duration-300">
                    <i data-lucide="calendar" class="w-6 h-6"></i>
                </div>
            </div>
            <div class="absolute bottom-0 left-0 right-0 h-1 bg-gradient-to-r from-emerald-500 to-teal-500 transform scale-x-0 group-hover:scale-x-100 transition duration-300"></div>
        </div>
    </div>

    <!-- Bảng điều khiển kiểm tra cập nhật -->
    <div class="bg-white dark:bg-slate-900 p-6 rounded-3xl shadow-sm border border-gray-100 dark:border-slate-800/60 flex flex-col md:flex-row items-start md:items-center justify-between gap-5">
        <div class="space-y-1">
            <h3 class="text-sm font-extrabold text-gray-900 dark:text-white">{{ __('Bắt đầu kiểm tra phiên bản mới') }}</h3>
            <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('Hệ thống sẽ kết nối đến máy chủ cập nhật trung gian để tải các bản phát hành mới nhất') }}</p>
        </div>
        <div class="w-full md:w-auto flex flex-col sm:flex-row items-stretch sm:items-center gap-4 shrink-0">
            <div id="check-status" class="text-xs text-gray-500 dark:text-gray-400 flex items-center gap-1.5 self-center"></div>
            
            <button type="button" id="btn-check-update"
                class="inline-flex items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-shopee to-orange-500 hover:from-shopee hover:to-orange-600 px-6 py-3 text-xs font-black text-white transition duration-300 shadow-md shadow-shopee/10 hover:shadow-lg hover:shadow-shopee/20 active:scale-95 disabled:opacity-50">
                <i data-lucide="refresh-cw" class="w-4 h-4" id="check-icon"></i>
                <svg class="h-4 w-4 animate-spin hidden" id="check-spinner" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span id="check-text">{{ __('Kiểm tra cập nhật') }}</span>
            </button>
        </div>
    </div>

    <!-- Kết quả kiểm tra cập nhật (Releases Changelog) -->
    <div id="update-result" class="hidden space-y-6">
        <!-- Banner: Đã cập nhật mới nhất -->
        <div id="up-to-date" class="hidden rounded-3xl border border-emerald-200/70 bg-emerald-50/20 p-5 dark:border-emerald-800/30 dark:bg-emerald-950/5 backdrop-blur-sm">
            <div class="flex items-start gap-3">
                <span class="p-2 bg-emerald-500/10 rounded-xl text-emerald-500 shrink-0">
                    <i data-lucide="check-circle-2" class="w-6 h-6"></i>
                </span>
                <div class="space-y-0.5">
                    <h4 class="font-extrabold text-emerald-800 dark:text-emerald-400 text-sm">{{ __('Hệ thống đã cập nhật!') }}</h4>
                    <p class="text-xs text-emerald-600 dark:text-emerald-500">{{ __('Bạn đang chạy trên phiên bản mới nhất từ máy chủ cập nhật. Không cần nâng cấp.') }}</p>
                </div>
            </div>
        </div>

        <!-- Banner: Có bản cập nhật mới -->
        <div id="has-update" class="hidden rounded-3xl border border-amber-200/70 bg-amber-50/20 p-5 dark:border-amber-800/30 dark:bg-amber-950/5 backdrop-blur-sm">
            <div class="flex items-start gap-3">
                <span class="p-2 bg-amber-500/10 rounded-xl text-amber-500 shrink-0 animate-pulse">
                    <i data-lucide="sparkles" class="w-6 h-6"></i>
                </span>
                <div class="space-y-0.5">
                    <h4 class="font-extrabold text-amber-800 dark:text-amber-400 text-sm">{{ __('Phát hiện phiên bản mới!') }}</h4>
                    <p class="text-xs text-amber-600 dark:text-amber-500">
                        {{ __('Phiên bản hiện tại:') }} <span id="version-from" class="font-bold font-mono"></span> &rarr; {{ __('Phiên bản mới nhất:') }} <span id="version-to" class="font-extrabold font-mono text-amber-700 dark:text-amber-300"></span>
                    </p>
                </div>
            </div>
        </div>

        <!-- Lịch sử phát hành (Releases Changelog) -->
        <div class="bg-white dark:bg-slate-900 rounded-3xl shadow-sm border border-gray-100 dark:border-slate-800/60 overflow-hidden">
            <div class="px-6 py-4 bg-gray-50/50 dark:bg-slate-950/40 border-b border-gray-100 dark:border-slate-800/60 flex items-center justify-between">
                <h4 class="text-xs font-black text-gray-700 dark:text-slate-300 uppercase tracking-wider flex items-center gap-2">
                    <i data-lucide="history" class="w-4 h-4 text-shopee"></i>
                    {{ __('Bản phát hành khả dụng (Changelog)') }}
                </h4>
            </div>
            <div id="changelog" class="divide-y divide-gray-100 dark:divide-slate-800/60 max-h-[600px] overflow-y-auto">
                <!-- Nội dung danh sách Releases được chèn tự động bằng Javascript -->
            </div>
        </div>

        <!-- Thông tin lưu ý triển khai -->
        <div class="bg-white dark:bg-slate-900 rounded-3xl shadow-sm border border-gray-100 dark:border-slate-800/60 p-6 space-y-4">
            <h4 class="text-xs font-black text-gray-700 dark:text-slate-300 uppercase tracking-wider flex items-center gap-2">
                <i data-lucide="info" class="w-4 h-4 text-shopee"></i>
                {{ __('Thông tin chi tiết quy trình cập nhật') }}
            </h4>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
                <div class="flex items-start gap-3 p-4 bg-gray-50 dark:bg-slate-950/40 rounded-2xl border border-gray-100 dark:border-slate-800/40">
                    <span class="p-2 bg-emerald-500/10 rounded-xl text-emerald-500 shrink-0">
                        <i data-lucide="folder-check" class="w-5 h-5"></i>
                    </span>
                    <div>
                        <span class="font-extrabold text-gray-800 dark:text-gray-200 block mb-0.5">{{ __('Các thư mục sẽ ghi đè') }}</span>
                        <p class="text-[11px] text-gray-500 dark:text-gray-400 leading-relaxed">app/, config/, database/, resources/, routes/, public/,...</p>
                    </div>
                </div>
                <div class="flex items-start gap-3 p-4 bg-gray-50 dark:bg-slate-950/40 rounded-2xl border border-gray-100 dark:border-slate-800/40">
                    <span class="p-2 bg-blue-500/10 rounded-xl text-blue-500 shrink-0">
                        <i data-lucide="shield-check" class="w-5 h-5"></i>
                    </span>
                    <div>
                        <span class="font-extrabold text-gray-800 dark:text-gray-200 block mb-0.5">{{ __('Các tệp cấu hình được bảo vệ') }}</span>
                        <p class="text-[11px] text-gray-500 dark:text-gray-400 leading-relaxed">.env, storage/ (ảnh tải lên), CSDL và dữ liệu người dùng được bảo toàn.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MODAL LỊCH TRÌNH DEPLOY (TERMINAL LOGS) -->
<div id="update-log-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-md">
    <div class="relative w-full max-w-3xl transform overflow-hidden rounded-3xl bg-[#0f141f] text-left align-middle shadow-2xl border border-slate-800/80 flex flex-col max-h-[85vh] animate-scale-up">
        <!-- Header Terminal dạng macOS Window -->
        <div class="px-5 py-4 bg-[#161c28] border-b border-slate-800/80 flex items-center justify-between shrink-0">
            <div class="flex items-center gap-3">
                <div class="flex gap-2">
                    <div class="w-3.5 h-3.5 rounded-full bg-[#ff5f56] border border-[#e0443e] cursor-pointer active:scale-90 transition"></div>
                    <div class="w-3.5 h-3.5 rounded-full bg-[#ffbd2e] border border-[#dea123] cursor-pointer active:scale-90 transition"></div>
                    <div class="w-3.5 h-3.5 rounded-full bg-[#27c93f] border border-[#1aab29] cursor-pointer active:scale-90 transition"></div>
                </div>
                <span class="text-xs text-slate-400 font-mono tracking-wider ml-2 flex items-center gap-2">
                    <i data-lucide="terminal" class="w-4 h-4 text-emerald-500"></i>
                    {{ __('deployment_terminal.sh') }}
                </span>
                <svg id="modal-spinner" class="h-4 w-4 animate-spin text-shopee hidden" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
            </div>
            
            <button onclick="closeUpdateModal()" class="text-slate-400 hover:text-white transition p-1.5 rounded-xl hover:bg-slate-800" id="modal-close-btn" style="display:none">
                <i data-lucide="x" class="w-4.5 h-4.5"></i>
            </button>
        </div>
        <!-- Nội dung logs dạng terminal đen -->
        <div id="log-content" class="p-6 font-mono text-xs leading-relaxed text-[#5af78e] overflow-y-auto bg-[#0b0e14] flex-1 min-h-[350px]">
            <!-- Nội dung log -->
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    let isUpdating = false;
    window.addEventListener('beforeunload', function (e) {
        if (isUpdating) {
            e.preventDefault();
            e.returnValue = '{{ __('Hệ thống đang tiến hành cập nhật mã nguồn, vui lòng KHÔNG đóng trang hoặc chuyển hướng để tránh gây lỗi cấu trúc tệp tin.') }}';
            return e.returnValue;
        }
    });

    document.addEventListener('DOMContentLoaded', function() {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

        // Nút mở khóa nhanh direct
        window.forceUnlockUpdateDirect = async function() {
            const result = await Swal.fire({
                title: '{{ __('Xác nhận mở khoá tiến trình?') }}',
                text: '{{ __('Nếu tiến trình cập nhật trước đó bị lỗi hoặc bị kẹt lâu, hãy chạy chức năng này để xóa các tệp lock và giải phóng tài nguyên.') }}',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ff5722',
                cancelButtonColor: '#6B7280',
                confirmButtonText: '🔓 {{ __('Giải phóng ngay') }}',
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
                const res = await fetch('{{ route("admin.update.unlock") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
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
                    text: '{{ __('Không thể kết nối đến API giải phóng.') }}'
                });
            }
        };

        // Bấm nút lưu/kích hoạt mã bản quyền trực tiếp
        const btnSaveLicense = document.getElementById('btn-save-license');
        if (btnSaveLicense) {
            btnSaveLicense.addEventListener('click', async function() {
                const inputLicense = document.getElementById('input-license-key');
                const licenseKey = inputLicense.value.trim();

                if (!licenseKey) {
                    Swal.fire({
                        icon: 'error',
                        title: '{{ __('Lỗi') }}',
                        text: '{{ __('Vui lòng nhập Mã bản quyền trước khi kích hoạt.') }}',
                        customClass: {
                            container: 'admin-modal',
                            confirmButton: 'inline-flex justify-center items-center gap-2 rounded-xl bg-shopee px-5 py-2.5 text-xs font-bold text-white hover:bg-shopee-dark transition shadow-lg'
                        },
                        buttonsStyling: false
                    });
                    return;
                }

                btnSaveLicense.disabled = true;
                const originalText = btnSaveLicense.innerHTML;
                btnSaveLicense.innerHTML = `<svg class="h-4 w-4 animate-spin text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> <span>{{ __('Đang kích hoạt...') }}</span>`;

                try {
                    const res = await fetch('{{ route("admin.update.save-license") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({ license_key: licenseKey })
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
                        
                        // Ẩn card nhập license đi
                        document.getElementById('license-config-card')?.classList.add('hidden');
                        
                        // Tự động kích hoạt kiểm tra cập nhật ngay lập tức
                        document.getElementById('btn-check-update')?.click();
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: '{{ __('Lỗi') }}',
                            text: data.message || '{{ __('Không thể kích hoạt mã bản quyền.') }}',
                            customClass: {
                                container: 'admin-modal',
                                confirmButton: 'inline-flex justify-center items-center gap-2 rounded-xl bg-shopee px-5 py-2.5 text-xs font-bold text-white hover:bg-shopee-dark transition shadow-lg'
                            },
                            buttonsStyling: false
                        });
                    }
                } catch (e) {
                    Swal.fire({
                        icon: 'error',
                        title: '{{ __('Lỗi') }}',
                        text: '{{ __('Lỗi hệ thống hoặc kết nối mạng.') }}',
                        customClass: {
                            container: 'admin-modal',
                            confirmButton: 'inline-flex justify-center items-center gap-2 rounded-xl bg-shopee px-5 py-2.5 text-xs font-bold text-white hover:bg-shopee-dark transition shadow-lg'
                        },
                        buttonsStyling: false
                    });
                } finally {
                    btnSaveLicense.disabled = false;
                    btnSaveLicense.innerHTML = originalText;
                }
            });
        }

        // Bấm nút kiểm tra cập nhật
        const btnCheckUpdate = document.getElementById('btn-check-update');
        if (btnCheckUpdate) {
            btnCheckUpdate.addEventListener('click', async function() {
            const btn = this;
            const icon = document.getElementById('check-icon');
            const spinner = document.getElementById('check-spinner');
            const status = document.getElementById('check-status');
            const result = document.getElementById('update-result');
            const latestText = document.getElementById('latest-version-text');

            btn.disabled = true;
            icon.classList.add('hidden');
            spinner.classList.remove('hidden');
            status.innerHTML = `<span class="flex items-center gap-1.5"><svg class="h-3.5 w-3.5 animate-spin text-gray-500" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> {{ __('Đang gọi máy chủ cập nhật...') }}</span>`;
            status.className = 'text-xs text-gray-500';

            try {
                const res = await fetch('{{ route("admin.update.check") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    },
                });

                const data = await res.json();

                if (!res.ok || data.status === 'error' || data.success === false) {
                    status.textContent = '';
                    
                    // Hiện Swal thông báo chi tiết
                    Swal.fire({
                        icon: 'error',
                        title: '{{ __('Kiểm tra thất bại') }}',
                        text: data.message || '{{ __('Lỗi kết nối hoặc yêu cầu bị từ chối.') }}',
                        customClass: {
                            container: 'admin-modal',
                            confirmButton: 'inline-flex justify-center items-center gap-2 rounded-xl bg-shopee px-5 py-2.5 text-xs font-bold text-white hover:bg-shopee-dark transition shadow-lg'
                        },
                        buttonsStyling: false
                    });
                    
                    // Nếu lỗi do giấy phép không hợp lệ, hiển thị form nhập license để điều chỉnh
                    if (data.invalid_license) {
                        const licenseCard = document.getElementById('license-config-card');
                        if (licenseCard) {
                            licenseCard.classList.remove('hidden');
                            document.getElementById('license-alert-title').textContent = '{{ __('Giấy phép không hợp lệ!') }}';
                            document.getElementById('license-alert-desc').textContent = data.message;
                        }
                    }
                    return;
                }

                result.classList.remove('hidden');
                status.textContent = '';
                latestText.textContent = data.latest_version;

                if (data.up_to_date) {
                    document.getElementById('up-to-date').classList.remove('hidden');
                    document.getElementById('has-update').classList.add('hidden');
                } else {
                    document.getElementById('up-to-date').classList.add('hidden');
                    document.getElementById('has-update').classList.remove('hidden');
                    document.getElementById('version-from').textContent = data.current_version;
                    document.getElementById('version-to').textContent = data.latest_version;
                }

                // Render releases
                const changelog = document.getElementById('changelog');
                changelog.innerHTML = '';

                if (!data.releases || data.releases.length === 0) {
                    changelog.innerHTML = '<div class="px-6 py-5 text-xs text-gray-500 text-center">{{ __('Không có thông tin bản phát hành nào.') }}</div>';
                    return;
                }

                data.releases.forEach((release, index) => {
                    const date = release.date ? new Date(release.date).toLocaleString('vi-VN') : '';
                    const isCurrent = release.current;
                    const isLatest = index === 0;

                    let badge = '';
                    let btnHtml = '';

                    if (isCurrent) {
                        badge = `<span class="inline-flex items-center rounded-full bg-emerald-50 dark:bg-emerald-950/20 px-2.5 py-1 text-[10px] font-bold text-emerald-600 border border-emerald-100 dark:border-emerald-900/30"><span class="h-1.5 w-1.5 rounded-full bg-emerald-500 mr-1.5 animate-ping"></span>{{ __('Đang chạy') }}</span>`;
                    } else if (isLatest) {
                        badge = `<span class="inline-flex items-center rounded-full bg-orange-50 dark:bg-orange-950/20 px-2.5 py-1 text-[10px] font-bold text-shopee border border-orange-100 dark:border-orange-900/30">{{ __('Mới nhất') }}</span>`;
                    }

                    if (!isCurrent && release.asset_url) {
                        const btnClass = isLatest ? 'bg-gradient-to-r from-shopee to-orange-500 hover:from-shopee hover:to-orange-600 text-white shadow-md shadow-shopee/10' : 'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-slate-800 dark:text-gray-300 dark:hover:bg-slate-700';
                        const text = isLatest ? '{{ __('Cập nhật ngay') }}' : '{{ __('Triển khai hạ cấp') }}';

                        btnHtml = `<button onclick="event.stopPropagation(); deployRelease('${release.asset_url}', '${release.version}')" class="shrink-0 inline-flex items-center gap-1.5 rounded-2xl ${btnClass} px-4 py-2.5 text-xs font-black transition duration-300 hover:shadow-lg active:scale-95">
                            <i data-lucide="download-cloud" class="w-3.5 h-3.5"></i>
                            ${text}
                        </button>`;
                    } else if (!release.asset_url) {
                        btnHtml = `<span class="text-xs text-red-500 font-bold flex items-center gap-1"><i data-lucide="x-circle" class="w-4 h-4"></i>{{ __('Thiếu file update-package.zip') }}</span>`;
                    }

                    changelog.insertAdjacentHTML('beforeend', `
                        <div class="px-6 py-5 hover:bg-gray-50/50 dark:hover:bg-slate-900/20 transition-all ${isCurrent ? 'bg-emerald-500/5 dark:bg-emerald-950/5' : ''}">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                <div class="flex items-center gap-3 min-w-0">
                                    <code class="rounded-xl bg-gray-150 dark:bg-slate-800 px-3 py-1.5 text-xs font-black font-mono text-gray-800 dark:text-slate-200 border border-gray-250 dark:border-slate-700 shrink-0">${release.version}</code>
                                    <span class="text-xs font-extrabold text-gray-900 dark:text-white truncate">${release.name}</span>
                                    ${badge}
                                </div>
                                ${btnHtml}
                            </div>
                            <div class="mt-2.5 flex items-center gap-2.5 text-[10px] text-gray-400 dark:text-gray-500">
                                <span class="flex items-center gap-1"><i data-lucide="clock" class="w-3.5 h-3.5 inline"></i>${date}</span>
                            </div>
                            ${release.message ? `<div class="mt-3.5 text-xs text-gray-600 dark:text-gray-400 bg-gray-50/50 dark:bg-slate-950/20 p-4 rounded-2xl border border-gray-150 dark:border-slate-800/80 whitespace-pre-wrap leading-relaxed">${release.message}</div>` : ''}
                        </div>
                    `);
                });

                if (window.lucide) {
                    window.lucide.createIcons();
                }
            } catch (e) {
                status.textContent = '';
                Swal.fire({
                    icon: 'error',
                    title: '{{ __('Lỗi kết nối') }}',
                    text: '{{ __('Lỗi kết nối hoặc dữ liệu không hợp lệ. Vui lòng thử lại sau.') }}',
                    customClass: {
                        container: 'admin-modal',
                        confirmButton: 'inline-flex justify-center items-center gap-2 rounded-xl bg-shopee px-5 py-2.5 text-xs font-bold text-white hover:bg-shopee-dark transition shadow-lg'
                    },
                    buttonsStyling: false
                });
            } finally {
                btn.disabled = false;
                icon.classList.remove('hidden');
                spinner.classList.add('hidden');
            }
        });
        }

        // Bấm nút cập nhật một release
        window.deployRelease = async function(assetUrl, version) {
            const confirmed = await Swal.fire({
                title: '{{ __('Xác nhận cập nhật?') }}',
                html: `{{ __('Bạn đang chuẩn bị cập nhật hoặc đổi sang phiên bản') }} <code class="px-2.5 py-1 bg-gray-150 dark:bg-slate-800 rounded-xl font-mono text-xs font-black border border-gray-250 dark:border-slate-700">${version}</code>.<br><br><div class="text-[11px] text-left max-w-sm mx-auto p-4 bg-amber-500/5 border border-amber-500/10 rounded-2xl text-amber-600 dark:text-amber-400 space-y-1.5 leading-relaxed"><strong>{{ __('Lưu ý quan trọng:') }}</strong><br>• {{ __('Hệ thống tự động tải file zip, giải nén và ghi đè.') }}<br>• {{ __('Mọi cấu hình database và file người dùng tải lên sẽ được bảo mật tuyệt đối.') }}<br>• {{ __('Quá trình này thường tốn từ 5 - 15 giây.') }}</div>`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ff5722',
                cancelButtonColor: '#6B7280',
                confirmButtonText: '🚀 {{ __('Tiến hành Cập nhật') }}',
                cancelButtonText: '{{ __('Huỷ bỏ') }}',
                customClass: {
                    container: 'admin-modal',
                    actions: 'flex gap-3 mt-4',
                    confirmButton: 'inline-flex justify-center items-center gap-2 rounded-xl bg-shopee px-5 py-2.5 text-xs font-bold text-white hover:bg-shopee-dark transition shadow-lg',
                    cancelButton: 'inline-flex justify-center items-center gap-2 rounded-xl bg-gray-500 px-5 py-2.5 text-xs font-bold text-white hover:bg-gray-600 transition shadow-lg'
                },
                buttonsStyling: false
            });

            if (!confirmed.isConfirmed) return;

            isUpdating = true; // Khóa hành động reload/đóng tab của trình duyệt

            const modal = document.getElementById('update-log-modal');
            const logContent = document.getElementById('log-content');
            const modalSpinner = document.getElementById('modal-spinner');
            const closeBtn = document.getElementById('modal-close-btn');

            modal.classList.remove('hidden');
            modalSpinner.classList.remove('hidden');
            closeBtn.style.display = 'none';
            logContent.innerHTML = `<div class="text-shopee font-bold">🚀 Starting installation for version ${version}...</div>`;
            logContent.insertAdjacentHTML('beforeend', `<div class="text-slate-400 mb-4 animate-pulse">Downloading package from update server...</div>`);
            document.querySelectorAll('#changelog button').forEach(b => b.disabled = true);

            try {
                const res = await fetch('{{ route("admin.update.apply") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        asset_url: assetUrl,
                        target_version: version
                    }),
                });

                const data = await res.json();

                isUpdating = false; // Mở khóa hành động đóng tab sau khi có kết quả
                logContent.innerHTML = '';
                if (data.logs) {
                    data.logs.forEach(log => {
                        logContent.insertAdjacentHTML('beforeend', `
                            <div class="text-[#ff5f56] font-bold mt-4 font-mono">$ ${log.step}</div>
                            <div class="text-slate-350 whitespace-pre-wrap pl-3 border-l border-slate-800 mt-1 font-mono">${log.output}</div>
                        `);
                    });
                }

                modalSpinner.classList.add('hidden');
                closeBtn.style.display = '';

                if (data.status === 'success') {
                    logContent.insertAdjacentHTML('beforeend', `<div class="text-emerald-400 font-bold mt-5 px-4 py-3 bg-emerald-500/10 border border-emerald-500/20 rounded-2xl">✅ Cập nhật thành công! Phiên bản mới: ${data.new_version}</div>`);
                    logContent.insertAdjacentHTML('beforeend', `<div class="mt-6 flex justify-end"><button onclick="window.location.reload()" class="inline-flex items-center gap-2 rounded-xl bg-shopee px-5 py-2.5 text-xs font-bold text-white hover:bg-shopee-dark transition shadow-lg"><svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>Tải lại trang Admin</button></div>`);
                    document.getElementById('current-version').textContent = data.new_version;
                } else {
                    logContent.insertAdjacentHTML('beforeend', `<div class="text-red-400 font-bold mt-4 px-4 py-3 bg-red-500/10 border border-red-500/20 rounded-2xl">❌ Cập nhật thất bại: ${data.message}</div>`);
                    if (data.can_force_unlock) {
                        logContent.insertAdjacentHTML('beforeend', `<div class="mt-4 flex justify-end"><button onclick="forceUnlockUpdate()" class="inline-flex items-center gap-2 rounded-xl bg-amber-500 px-5 py-2.5 text-xs font-bold text-white hover:bg-amber-600 transition shadow-lg"><i data-lucide="unlock" class="w-4 h-4"></i>Mở khoá tiến trình</button></div>`);
                        if (window.lucide) window.lucide.createIcons();
                    }
                    document.querySelectorAll('#changelog button').forEach(b => b.disabled = false);
                }
            } catch (e) {
                isUpdating = false; // Mở khóa đóng tab nếu gặp lỗi kết nối
                modalSpinner.classList.add('hidden');
                closeBtn.style.display = '';
                logContent.insertAdjacentHTML('beforeend', `<div class="text-red-400 font-bold mt-4 px-4 py-3 bg-red-500/10 border border-red-500/20 rounded-2xl">❌ Lỗi kết nối hoặc mất kết nối. Lock file đang bị khoá. Vui lòng mở khoá để tiếp tục.</div>`);
                logContent.insertAdjacentHTML('beforeend', `<div class="mt-4 flex justify-end"><button onclick="forceUnlockUpdate()" class="inline-flex items-center gap-2 rounded-xl bg-amber-500 px-5 py-2.5 text-xs font-bold text-white hover:bg-amber-600 transition shadow-lg"><i data-lucide="unlock" class="w-4 h-4"></i>Mở khoá tiến trình</button></div>`);
                if (window.lucide) window.lucide.createIcons();
                document.querySelectorAll('#changelog button').forEach(b => b.disabled = false);
            }
        };

        // Gặp lỗi kẹt tiến trình, bấm nút mở khoá bằng tay
        window.forceUnlockUpdate = async function() {
            const logContent = document.getElementById('log-content');
            logContent.insertAdjacentHTML('beforeend', `<div class="text-amber-400 mt-3 animate-pulse">🔓 Đang mở khoá và giải phóng tài nguyên...</div>`);

            try {
                const res = await fetch('{{ route("admin.update.unlock") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    },
                });
                const data = await res.json();

                if (data.status === 'success') {
                    logContent.insertAdjacentHTML('beforeend', `<div class="text-emerald-400 font-bold mt-3 px-4 py-3 bg-emerald-500/10 border border-emerald-500/20 rounded-2xl">🔓 ${data.message}</div>`);
                    setTimeout(() => closeUpdateModal(), 1500);
                } else {
                    logContent.insertAdjacentHTML('beforeend', `<div class="text-red-400 mt-3">${data.message}</div>`);
                }
            } catch (e) {
                logContent.insertAdjacentHTML('beforeend', `<div class="text-red-400 mt-3">Lỗi kết nối khi gọi API mở khoá.</div>`);
            }
        };

        window.closeUpdateModal = function() {
            document.getElementById('update-log-modal').classList.add('hidden');
        };

        // Bật/tắt cập nhật tự động nhanh qua AJAX
        const toggleAutoUpdate = document.getElementById('toggle-auto-update');
        if (toggleAutoUpdate) {
            toggleAutoUpdate.addEventListener('change', async function() {
                const isChecked = this.checked;
                const badgeContainer = document.getElementById('auto-update-badge');
                
                this.disabled = true;

                try {
                    const res = await fetch('{{ route("admin.update.toggle-auto") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({ status: isChecked })
                    });

                    const data = await res.json();

                    if (data.success) {
                        // Kích hoạt thông báo toast thành công
                        window.dispatchEvent(new CustomEvent('toast', { 
                            detail: { 
                                text: data.message, 
                                type: 'success' 
                            } 
                        }));

                        // Cập nhật lại HTML của Badge tương ứng
                        if (data.status === '1') {
                            badgeContainer.innerHTML = `
                                <span class="inline-flex items-center rounded-full bg-emerald-50 dark:bg-emerald-950/20 px-2.5 py-0.5 text-[10px] font-bold text-emerald-600 border border-emerald-100 dark:border-emerald-900/30">
                                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-500 mr-1.5 animate-pulse"></span>{{ __('Đang bật') }}
                                </span>
                            `;
                        } else {
                            badgeContainer.innerHTML = `
                                <span class="inline-flex items-center rounded-full bg-slate-100 dark:bg-slate-800 px-2.5 py-0.5 text-[10px] font-bold text-slate-600 dark:text-slate-400 border border-slate-200 dark:border-slate-700/50">
                                    {{ __('Đang tắt') }}
                                </span>
                            `;
                        }
                    } else {
                        this.checked = !isChecked;
                        Swal.fire({
                            icon: 'error',
                            title: '{{ __('Lỗi') }}',
                            text: data.message || '{{ __('Không thể thay đổi cấu hình.') }}',
                            customClass: {
                                container: 'admin-modal',
                                confirmButton: 'inline-flex justify-center items-center gap-2 rounded-xl bg-shopee px-5 py-2.5 text-xs font-bold text-white hover:bg-shopee-dark transition shadow-lg'
                            },
                            buttonsStyling: false
                        });
                    }
                } catch (e) {
                    this.checked = !isChecked;
                    Swal.fire({
                        icon: 'error',
                        title: '{{ __('Lỗi') }}',
                        text: '{{ __('Lỗi hệ thống hoặc kết nối mạng khi cập nhật.') }}',
                        customClass: {
                            container: 'admin-modal',
                            confirmButton: 'inline-flex justify-center items-center gap-2 rounded-xl bg-shopee px-5 py-2.5 text-xs font-bold text-white hover:bg-shopee-dark transition shadow-lg'
                        },
                        buttonsStyling: false
                    });
                } finally {
                    this.disabled = false;
                }
            });
        }

        // Tự động kích hoạt kiểm tra cập nhật nếu đã cấu hình mã bản quyền
        @if(!empty($licenseKey))
            document.getElementById('btn-check-update')?.click();
        @endif
    });
</script>
@endsection
