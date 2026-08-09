@extends('layouts.admin')

@section('title', __('Dọn Dẹp & Sao Lưu Hệ Thống') . ' - ' . $siteName)

@section('content')
<div class="space-y-8" x-data="{
    selectedActions: ['cache', 'logs'],
    // Phương thức chọn nhanh tất cả các mục trong một nhóm cụ thể
    toggleGroup(group) {
        let groupItems = [];
        if (group === 'system') {
            groupItems = ['cache', 'logs', 'activity_logs', 'api_logs', 'email_queues', 'telegram_queues', 'notifications'];
        } else if (group === 'business') {
            groupItems = ['cashback_clicks', 'cashback_histories', 'withdrawals', 'gift_redemptions', 'gift_code_redemptions', 'balance_logs', 'daily_checkins', 'referral_commissions', 'short_links', 'saved_products'];
        }
        
        let allSelected = groupItems.every(item => this.selectedActions.includes(item));
        if (allSelected) {
            // Nếu đã chọn hết thì gỡ chọn toàn bộ nhóm đó
            this.selectedActions = this.selectedActions.filter(item => !groupItems.includes(item));
        } else {
            // Nếu chưa chọn hết thì đưa toàn bộ item chưa chọn vào mảng
            groupItems.forEach(item => {
                if (!this.selectedActions.includes(item)) {
                    this.selectedActions.push(item);
                }
            });
        }
    },
    // Kiểm tra xem toàn bộ nhóm đã được chọn hay chưa để hiển thị text nút tương ứng
    isGroupAllSelected(group) {
        let groupItems = [];
        if (group === 'system') {
            groupItems = ['cache', 'logs', 'activity_logs', 'api_logs', 'email_queues', 'telegram_queues', 'notifications'];
        } else if (group === 'business') {
            groupItems = ['cashback_clicks', 'cashback_histories', 'withdrawals', 'gift_redemptions', 'gift_code_redemptions', 'balance_logs', 'daily_checkins', 'referral_commissions', 'short_links', 'saved_products'];
        }
        return groupItems.every(item => this.selectedActions.includes(item));
    }
}">
    <!-- Tiêu đề trang với gradient nhạt sang trọng ở background -->
    <div class="relative overflow-hidden bg-gradient-to-r from-gray-50 to-white dark:from-slate-900 dark:to-slate-950 p-6 sm:p-8 rounded-3xl border border-gray-100 dark:border-slate-800/60 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="relative z-10">
            <h1 class="text-2xl font-black text-gray-900 dark:text-white flex items-center gap-2.5">
                <span class="p-2 bg-shopee/10 text-shopee rounded-2xl flex items-center justify-center">
                    <i data-lucide="shield-alert" class="w-6 h-6"></i>
                </span>
                {{ __('Dọn Dẹp & Sao Lưu Hệ Thống') }}
            </h1>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-2 max-w-2xl leading-relaxed">
                {{ __('Giải phóng và làm sạch cache ứng dụng, dọn dẹp các tệp tin log dung lượng lớn cùng lịch sử giao dịch cũ để duy trì hiệu năng tải trang nhanh nhất.') }}
            </p>
        </div>
        <div class="flex items-center gap-2">
            <span class="flex h-2.5 w-2.5 relative">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-emerald-500"></span>
            </span>
            <span class="text-[11px] font-bold text-gray-500 dark:text-gray-400 tracking-wide uppercase">{{ __('Hệ thống ổn định') }}</span>
        </div>
    </div>

    @if(config('app.demo'))
        <div class="bg-yellow-50 dark:bg-yellow-950/20 border border-yellow-200 dark:border-yellow-900/50 text-yellow-800 dark:text-yellow-400 rounded-3xl p-6 sm:p-8 flex flex-col items-center justify-center text-center space-y-3 py-16">
            <div class="w-16 h-16 bg-yellow-100 dark:bg-yellow-900/30 rounded-2xl flex items-center justify-center text-yellow-500 animate-pulse">
                <i data-lucide="shield-alert" class="w-8 h-8"></i>
            </div>
            <h3 class="text-sm font-bold text-gray-900 dark:text-white mt-4">{{ __('Tính năng bị ẩn ở chế độ Demo') }}</h3>
            <p class="text-xs text-gray-500 dark:text-gray-400 max-w-md leading-relaxed">
                {{ __('Để đảm bảo an toàn dữ liệu và tính toàn vẹn của hệ thống, các chức năng dọn dẹp dữ liệu, nhật ký hoạt động và sao lưu cơ sở dữ liệu không được hiển thị và thực hiện ở chế độ dùng thử (Demo).') }}
            </p>
        </div>
    @else

    <!-- Alert Thông báo thành công / lỗi -->
    @if(session('success'))
        <div class="p-4 bg-emerald-50 dark:bg-emerald-950/20 border border-emerald-200/60 dark:border-emerald-950/30 rounded-2xl flex items-start gap-3 text-emerald-800 dark:text-emerald-300 shadow-sm animate-fade-in">
            <i data-lucide="check-circle-2" class="w-5 h-5 shrink-0 mt-0.5 text-emerald-500"></i>
            <div>
                <p class="text-xs font-bold">{{ __('Thao tác thành công!') }}</p>
                <p class="text-[11px] mt-0.5 opacity-90 leading-relaxed">{{ session('success') }}</p>
            </div>
        </div>
    @endif

    @if(session('error'))
        <div class="p-4 bg-rose-50 dark:bg-rose-950/20 border border-rose-200/60 dark:border-rose-950/30 rounded-2xl flex items-start gap-3 text-rose-800 dark:text-rose-300 shadow-sm animate-fade-in">
            <i data-lucide="alert-circle" class="w-5 h-5 shrink-0 mt-0.5 text-rose-500"></i>
            <div>
                <p class="text-xs font-bold">{{ __('Thao tác thất bại!') }}</p>
                <p class="text-[11px] mt-0.5 opacity-90 leading-relaxed">{{ session('error') }}</p>
            </div>
        </div>
    @endif

    <!-- Thống kê dung lượng thiết kế dạng Card trực quan -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Database size -->
        <div class="relative overflow-hidden bg-white dark:bg-slate-900 rounded-3xl p-6 shadow-sm border border-gray-100 dark:border-slate-800/50 flex flex-col justify-between group hover:border-blue-500/20 transition-all duration-300">
            <div class="flex items-start justify-between">
                <div class="flex items-center gap-4">
                    <div class="p-3 bg-blue-50 dark:bg-blue-950/30 text-blue-600 dark:text-blue-400 rounded-2xl">
                        <i data-lucide="database" class="w-6 h-6"></i>
                    </div>
                    <div>
                        <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider">{{ __('Dung lượng Cơ sở dữ liệu') }}</h3>
                        <p class="text-2xl font-black text-gray-900 dark:text-white mt-1">
                            {{ $dbSize }} <span class="text-xs font-bold text-gray-400">MB</span>
                        </p>
                    </div>
                </div>
                <!-- Badge đánh giá mức độ sử dụng -->
                <span class="px-2.5 py-1 text-[9px] font-bold rounded-lg {{ $dbSize > 100 ? 'bg-amber-50 text-amber-600 border border-amber-100 dark:bg-amber-950/20 dark:text-amber-400' : 'bg-emerald-50 text-emerald-600 border border-emerald-100 dark:bg-emerald-950/20 dark:text-emerald-400' }}">
                    {{ $dbSize > 100 ? __('Cần kiểm tra') : __('Tối ưu') }}
                </span>
            </div>
            <!-- Progress Bar trực quan hóa -->
            <div class="mt-5 space-y-1.5">
                <div class="w-full bg-gray-100 dark:bg-slate-800 h-2 rounded-full overflow-hidden">
                    <div class="h-full rounded-full transition-all duration-500 {{ $dbSize > 100 ? 'bg-amber-500' : 'bg-blue-500' }}" 
                         style="width: {{ min(100, max(5, ($dbSize / 200) * 100)) }}%"></div>
                </div>
                <span class="block text-[9px] text-gray-400 text-right leading-relaxed">{{ __('Ngưỡng tối ưu: dưới 200 MB') }}</span>
            </div>
        </div>

        <!-- Log size -->
        <div class="relative overflow-hidden bg-white dark:bg-slate-900 rounded-3xl p-6 shadow-sm border border-gray-100 dark:border-slate-800/50 flex flex-col justify-between group hover:border-amber-500/20 transition-all duration-300">
            <div class="flex items-start justify-between">
                <div class="flex items-center gap-4">
                    <div class="p-3 bg-amber-50 dark:bg-amber-950/30 text-amber-600 dark:text-amber-400 rounded-2xl">
                        <i data-lucide="file-text" class="w-6 h-6"></i>
                    </div>
                    <div>
                        <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider">{{ __('Dung lượng Laravel Log') }}</h3>
                        <p class="text-2xl font-black text-gray-900 dark:text-white mt-1">
                            {{ $logSize }} <span class="text-xs font-bold text-gray-400">MB</span>
                        </p>
                    </div>
                </div>
                <span class="px-2.5 py-1 text-[9px] font-bold rounded-lg {{ $logSize > 10 ? 'bg-rose-50 text-rose-600 border border-rose-100 dark:bg-rose-950/20 dark:text-rose-400' : 'bg-emerald-50 text-emerald-600 border border-emerald-100 dark:bg-emerald-950/20 dark:text-emerald-400' }}">
                    {{ $logSize > 10 ? __('Nên dọn dẹp') : __('Bình thường') }}
                </span>
            </div>
            <!-- Progress Bar trực quan hóa -->
            <div class="mt-5 space-y-1.5">
                <div class="w-full bg-gray-100 dark:bg-slate-800 h-2 rounded-full overflow-hidden">
                    <div class="h-full rounded-full transition-all duration-500 {{ $logSize > 10 ? 'bg-rose-500 animate-pulse' : 'bg-amber-500' }}" 
                         style="width: {{ min(100, max(5, ($logSize / 25) * 100)) }}%"></div>
                </div>
                <span class="block text-[9px] text-gray-400 text-right leading-relaxed">{{ __('Ngưỡng tối ưu: dưới 25 MB') }}</span>
            </div>
        </div>
    </div>

    <!-- Main Content Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Cột Trái: Form Dọn Dẹp Dữ Liệu (2/3 chiều rộng) -->
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 md:p-8 shadow-sm border border-gray-100 dark:border-slate-800/50">
                <form action="{{ route('admin.system_cleanup.cleanup') }}" method="POST" id="form-cleanup" class="space-y-8">
                    @csrf
                    
                    <!-- PHÂN NHÓM 1: DỌN DẸP HỆ THỐNG & LOGS -->
                    <div class="space-y-4">
                        <div class="flex items-center justify-between border-b border-gray-100 dark:border-slate-800/80 pb-3">
                            <h3 class="text-xs font-black uppercase text-gray-800 dark:text-gray-200 tracking-wider flex items-center gap-2">
                                <span class="w-1.5 h-4 bg-blue-500 rounded-full"></span>
                                {{ __('Dữ Liệu Tạm & Nhật Ký Hệ Thống') }}
                            </h3>
                            <button type="button" @click="toggleGroup('system')"
                                    class="text-[10px] font-bold px-2.5 py-1 bg-gray-50 dark:bg-slate-800 text-gray-600 dark:text-gray-400 rounded-lg hover:bg-shopee/10 hover:text-shopee border border-gray-200/50 dark:border-slate-700/50 transition-all select-none">
                                <span x-text="isGroupAllSelected('system') ? '{{ __('Bỏ chọn tất cả') }}' : '{{ __('Chọn tất cả') }}'"></span>
                            </button>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Cache -->
                            <div class="border p-4 rounded-2xl flex flex-col justify-between transition-all duration-300 relative overflow-hidden group cursor-pointer"
                                 @click="let cb = $el.querySelector('input[type=checkbox]'); if($event.target !== cb && !$el.querySelector('select')?.contains($event.target)) { cb.checked = !cb.checked; cb.dispatchEvent(new Event('change')) }"
                                 :class="selectedActions.includes('cache') ? 'border-shopee bg-shopee/5 dark:bg-shopee/10' : 'border-gray-100 dark:border-slate-800/80 bg-white dark:bg-slate-900 hover:border-gray-200 dark:hover:border-slate-700'">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="p-2 rounded-xl bg-blue-500/10 text-blue-500 group-hover:scale-110 transition-transform">
                                        <i data-lucide="refresh-cw" class="w-4 h-4"></i>
                                    </div>
                                    <input type="checkbox" name="actions[]" value="cache" x-model="selectedActions"
                                           class="text-shopee focus:ring-shopee rounded border-gray-300 dark:border-slate-700 w-4.5 h-4.5 cursor-pointer">
                                </div>
                                <div class="mt-4">
                                    <h4 class="text-xs font-bold text-gray-800 dark:text-gray-200">{{ __('Bộ nhớ đệm (Cache)') }}</h4>
                                    <p class="text-[10px] text-gray-400 dark:text-gray-500 mt-1 leading-normal">
                                        {{ __('Làm sạch cache cấu hình, tuyến đường và giao diện biên dịch.') }}
                                    </p>
                                </div>
                            </div>

                            <!-- System Logs -->
                            <div class="border p-4 rounded-2xl flex flex-col justify-between transition-all duration-300 relative overflow-hidden group cursor-pointer"
                                 @click="let cb = $el.querySelector('input[type=checkbox]'); if($event.target !== cb && !$el.querySelector('select')?.contains($event.target)) { cb.checked = !cb.checked; cb.dispatchEvent(new Event('change')) }"
                                 :class="selectedActions.includes('logs') ? 'border-shopee bg-shopee/5 dark:bg-shopee/10' : 'border-gray-100 dark:border-slate-800/80 bg-white dark:bg-slate-900 hover:border-gray-200 dark:hover:border-slate-700'">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="p-2 rounded-xl bg-amber-500/10 text-amber-500 group-hover:scale-110 transition-transform">
                                        <i data-lucide="file-warning" class="w-4 h-4"></i>
                                    </div>
                                    <input type="checkbox" name="actions[]" value="logs" x-model="selectedActions"
                                           class="text-shopee focus:ring-shopee rounded border-gray-300 dark:border-slate-700 w-4.5 h-4.5 cursor-pointer">
                                </div>
                                <div class="mt-4">
                                    <h4 class="text-xs font-bold text-gray-800 dark:text-gray-200">{{ __('Nhật ký Laravel Log') }}</h4>
                                    <p class="text-[10px] text-gray-400 dark:text-gray-500 mt-1 leading-normal">
                                        {{ __('Làm rỗng tệp log lỗi laravel.log hiện có của hệ thống.') }}
                                    </p>
                                </div>
                            </div>

                            <!-- Activity Logs -->
                            <div class="border p-4 rounded-2xl flex flex-col justify-between transition-all duration-300 relative overflow-hidden group cursor-pointer md:col-span-2"
                                 @click="let cb = $el.querySelector('input[type=checkbox]'); if($event.target !== cb && !$el.querySelector('select')?.contains($event.target)) { cb.checked = !cb.checked; cb.dispatchEvent(new Event('change')) }"
                                 :class="selectedActions.includes('activity_logs') ? 'border-shopee bg-shopee/5 dark:bg-shopee/10' : 'border-gray-100 dark:border-slate-800/80 bg-white dark:bg-slate-900 hover:border-gray-200 dark:hover:border-slate-700'">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="flex items-center gap-3">
                                        <div class="p-2 rounded-xl bg-purple-500/10 text-purple-500 group-hover:scale-110 transition-transform">
                                            <i data-lucide="user-cog" class="w-4 h-4"></i>
                                        </div>
                                        <div>
                                            <h4 class="text-xs font-bold text-gray-800 dark:text-gray-200">{{ __('Nhật ký hoạt động thành viên') }}</h4>
                                            <span class="inline-block mt-0.5 px-2 py-0.5 bg-purple-50 dark:bg-purple-950/20 text-purple-600 dark:text-purple-400 text-[9px] font-bold rounded-lg border border-purple-100 dark:border-purple-900/30">
                                                {{ number_format($counts['activity_logs']) }} {{ __('bản ghi') }}
                                            </span>
                                        </div>
                                    </div>
                                    <input type="checkbox" name="actions[]" value="activity_logs" x-model="selectedActions"
                                           class="text-shopee focus:ring-shopee rounded border-gray-300 dark:border-slate-700 w-4.5 h-4.5 cursor-pointer">
                                </div>
                                <!-- Dropdown xuất hiện động khi check -->
                                <div x-show="selectedActions.includes('activity_logs')" 
                                     x-transition:enter="transition ease-out duration-200"
                                     x-transition:enter-start="opacity-0 transform -translate-y-2"
                                     x-transition:enter-end="opacity-100 transform translate-y-0"
                                     class="mt-4 pt-4 border-t border-dashed border-gray-200/60 dark:border-slate-800 flex items-center justify-between">
                                    <span class="text-[10px] text-gray-400">{{ __('Lựa chọn dọn dẹp:') }}</span>
                                    <select name="keep_recent_activity_logs" class="px-2.5 py-1.5 border border-gray-200 dark:border-slate-700 rounded-xl text-[10px] focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-300 font-medium">
                                        <option value="3">{{ __('Giữ lại 3 ngày gần nhất') }}</option>
                                        <option value="7" selected>{{ __('Giữ lại 7 ngày gần nhất') }}</option>
                                        <option value="14">{{ __('Giữ lại 14 ngày gần nhất') }}</option>
                                        <option value="30">{{ __('Giữ lại 30 ngày gần nhất') }}</option>
                                        <option value="60">{{ __('Giữ lại 60 ngày gần nhất') }}</option>
                                        <option value="180">{{ __('Giữ lại 180 ngày gần nhất') }}</option>
                                        <option value="365">{{ __('Giữ lại 1 năm gần nhất') }}</option>
                                        <option value="0">{{ __('Xóa toàn bộ (Không giữ lại)') }}</option>
                                    </select>
                                </div>
                            </div>

                             <!-- Api Logs -->
                            <div class="border p-4 rounded-2xl flex flex-col justify-between transition-all duration-300 relative overflow-hidden group cursor-pointer md:col-span-2"
                                 @click="let cb = $el.querySelector('input[type=checkbox]'); if($event.target !== cb && !$el.querySelector('select')?.contains($event.target)) { cb.checked = !cb.checked; cb.dispatchEvent(new Event('change')) }"
                                 :class="selectedActions.includes('api_logs') ? 'border-shopee bg-shopee/5 dark:bg-shopee/10' : 'border-gray-100 dark:border-slate-800/80 bg-white dark:bg-slate-900 hover:border-gray-200 dark:hover:border-slate-700'">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="flex items-center gap-3">
                                        <div class="p-2 rounded-xl bg-cyan-500/10 text-cyan-500 group-hover:scale-110 transition-transform">
                                            <i data-lucide="webhook" class="w-4 h-4"></i>
                                        </div>
                                        <div>
                                            <h4 class="text-xs font-bold text-gray-800 dark:text-gray-200">{{ __('Nhật ký gọi API (API Request Logs)') }}</h4>
                                            <span class="inline-block mt-0.5 px-2 py-0.5 bg-cyan-50 dark:bg-cyan-950/20 text-cyan-600 dark:text-cyan-400 text-[9px] font-bold rounded-lg border border-cyan-100 dark:border-cyan-900/30">
                                                {{ number_format($counts['api_logs']) }} {{ __('bản ghi') }}
                                            </span>
                                        </div>
                                    </div>
                                    <input type="checkbox" name="actions[]" value="api_logs" x-model="selectedActions"
                                           class="text-shopee focus:ring-shopee rounded border-gray-300 dark:border-slate-700 w-4.5 h-4.5 cursor-pointer">
                                </div>
                                <div x-show="selectedActions.includes('api_logs')" 
                                     x-transition:enter="transition ease-out duration-200"
                                     x-transition:enter-start="opacity-0 transform -translate-y-2"
                                     x-transition:enter-end="opacity-100 transform translate-y-0"
                                     class="mt-4 pt-4 border-t border-dashed border-gray-200/60 dark:border-slate-800 flex items-center justify-between">
                                    <span class="text-[10px] text-gray-400">{{ __('Lựa chọn dọn dẹp:') }}</span>
                                    <select name="keep_recent_api_logs" class="px-2.5 py-1.5 border border-gray-200 dark:border-slate-700 rounded-xl text-[10px] focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-300 font-medium">
                                        <option value="3">{{ __('Giữ lại 3 ngày gần nhất') }}</option>
                                        <option value="7" selected>{{ __('Giữ lại 7 ngày gần nhất') }}</option>
                                        <option value="14">{{ __('Giữ lại 14 ngày gần nhất') }}</option>
                                        <option value="30">{{ __('Giữ lại 30 ngày gần nhất') }}</option>
                                        <option value="60">{{ __('Giữ lại 60 ngày gần nhất') }}</option>
                                        <option value="180">{{ __('Giữ lại 180 ngày gần nhất') }}</option>
                                        <option value="365">{{ __('Giữ lại 1 năm gần nhất') }}</option>
                                        <option value="0">{{ __('Xóa toàn bộ (Không giữ lại)') }}</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Email Queue -->
                            <div class="border p-4 rounded-2xl flex flex-col justify-between transition-all duration-300 relative overflow-hidden group cursor-pointer"
                                 @click="let cb = $el.querySelector('input[type=checkbox]'); if($event.target !== cb && !$el.querySelector('select')?.contains($event.target)) { cb.checked = !cb.checked; cb.dispatchEvent(new Event('change')) }"
                                 :class="selectedActions.includes('email_queues') ? 'border-shopee bg-shopee/5 dark:bg-shopee/10' : 'border-gray-100 dark:border-slate-800/80 bg-white dark:bg-slate-900 hover:border-gray-200 dark:hover:border-slate-700'">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="p-2 rounded-xl bg-pink-500/10 text-pink-500 group-hover:scale-110 transition-transform">
                                        <i data-lucide="mail" class="w-4 h-4"></i>
                                    </div>
                                    <input type="checkbox" name="actions[]" value="email_queues" x-model="selectedActions"
                                           class="text-shopee focus:ring-shopee rounded border-gray-300 dark:border-slate-700 w-4.5 h-4.5 cursor-pointer">
                                </div>
                                <div class="mt-4">
                                    <h4 class="text-xs font-bold text-gray-800 dark:text-gray-200">{{ __('Hàng đợi gửi Email') }}</h4>
                                    <span class="inline-block mt-1 px-2 py-0.5 bg-pink-50 dark:bg-pink-950/20 text-pink-600 dark:text-pink-400 text-[9px] font-bold rounded-lg border border-pink-100 dark:border-pink-900/30">
                                        {{ number_format($counts['email_queues']) }} {{ __('bản ghi') }}
                                    </span>
                                </div>
                            </div>

                            <!-- Telegram Queue -->
                            <div class="border p-4 rounded-2xl flex flex-col justify-between transition-all duration-300 relative overflow-hidden group cursor-pointer"
                                 @click="let cb = $el.querySelector('input[type=checkbox]'); if($event.target !== cb && !$el.querySelector('select')?.contains($event.target)) { cb.checked = !cb.checked; cb.dispatchEvent(new Event('change')) }"
                                 :class="selectedActions.includes('telegram_queues') ? 'border-shopee bg-shopee/5 dark:bg-shopee/10' : 'border-gray-100 dark:border-slate-800/80 bg-white dark:bg-slate-900 hover:border-gray-200 dark:hover:border-slate-700'">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="p-2 rounded-xl bg-indigo-500/10 text-indigo-500 group-hover:scale-110 transition-transform">
                                        <i data-lucide="send" class="w-4 h-4"></i>
                                    </div>
                                    <input type="checkbox" name="actions[]" value="telegram_queues" x-model="selectedActions"
                                           class="text-shopee focus:ring-shopee rounded border-gray-300 dark:border-slate-700 w-4.5 h-4.5 cursor-pointer">
                                </div>
                                <div class="mt-4">
                                    <h4 class="text-xs font-bold text-gray-800 dark:text-gray-200">{{ __('Hàng đợi Telegram') }}</h4>
                                    <span class="inline-block mt-1 px-2 py-0.5 bg-indigo-50 dark:bg-indigo-950/20 text-indigo-600 dark:text-indigo-400 text-[9px] font-bold rounded-lg border border-indigo-100 dark:border-indigo-900/30">
                                        {{ number_format($counts['telegram_queues']) }} {{ __('bản ghi') }}
                                    </span>
                                </div>
                            </div>

                            <!-- Notifications Logs -->
                            <div class="border p-4 rounded-2xl flex flex-col justify-between transition-all duration-300 relative overflow-hidden group cursor-pointer md:col-span-2"
                                 @click="let cb = $el.querySelector('input[type=checkbox]'); if($event.target !== cb && !$el.querySelector('select')?.contains($event.target)) { cb.checked = !cb.checked; cb.dispatchEvent(new Event('change')) }"
                                 :class="selectedActions.includes('notifications') ? 'border-shopee bg-shopee/5 dark:bg-shopee/10' : 'border-gray-100 dark:border-slate-800/80 bg-white dark:bg-slate-900 hover:border-gray-200 dark:hover:border-slate-700'">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="flex items-center gap-3">
                                        <div class="p-2 rounded-xl bg-blue-500/10 text-blue-500 group-hover:scale-110 transition-transform">
                                            <i data-lucide="bell" class="w-4 h-4"></i>
                                        </div>
                                        <div>
                                            <h4 class="text-xs font-bold text-gray-800 dark:text-gray-200">{{ __('Nhật ký thông báo người dùng') }}</h4>
                                            <span class="inline-block mt-0.5 px-2 py-0.5 bg-blue-50 dark:bg-blue-950/20 text-blue-600 dark:text-blue-400 text-[9px] font-bold rounded-lg border border-blue-100 dark:border-blue-900/30">
                                                {{ number_format($counts['notifications']) }} {{ __('bản ghi') }}
                                            </span>
                                        </div>
                                    </div>
                                    <input type="checkbox" name="actions[]" value="notifications" x-model="selectedActions"
                                           class="text-shopee focus:ring-shopee rounded border-gray-300 dark:border-slate-700 w-4.5 h-4.5 cursor-pointer">
                                </div>
                                <div x-show="selectedActions.includes('notifications')" 
                                     x-transition:enter="transition ease-out duration-200"
                                     x-transition:enter-start="opacity-0 transform -translate-y-2"
                                     x-transition:enter-end="opacity-100 transform translate-y-0"
                                     class="mt-4 pt-4 border-t border-dashed border-gray-200/60 dark:border-slate-800 flex items-center justify-between">
                                    <span class="text-[10px] text-gray-400">{{ __('Lựa chọn dọn dẹp:') }}</span>
                                    <select name="keep_recent_notifications" class="px-2.5 py-1.5 border border-gray-200 dark:border-slate-700 rounded-xl text-[10px] focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-300 font-medium">
                                        <option value="3">{{ __('Giữ lại 3 ngày gần nhất') }}</option>
                                        <option value="7">{{ __('Giữ lại 7 ngày gần nhất') }}</option>
                                        <option value="14">{{ __('Giữ lại 14 ngày gần nhất') }}</option>
                                        <option value="30" selected>{{ __('Giữ lại 30 ngày gần nhất') }}</option>
                                        <option value="60">{{ __('Giữ lại 60 ngày gần nhất') }}</option>
                                        <option value="180">{{ __('Giữ lại 180 ngày gần nhất') }}</option>
                                        <option value="365">{{ __('Giữ lại 1 năm gần nhất') }}</option>
                                        <option value="0">{{ __('Xóa toàn bộ (Không giữ lại)') }}</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- PHÂN NHÓM 2: DỌN DẸP NGHIỆP VỤ & GIAO DỊCH -->
                    <div class="space-y-4 pt-4">
                        <div class="flex items-center justify-between border-b border-gray-100 dark:border-slate-800/80 pb-3">
                            <h3 class="text-xs font-black uppercase text-gray-800 dark:text-gray-200 tracking-wider flex items-center gap-2">
                                <span class="w-1.5 h-4 bg-orange-500 rounded-full"></span>
                                {{ __('Dữ Liệu Nghiệp Vụ & Giao Dịch') }}
                            </h3>
                            <button type="button" @click="toggleGroup('business')"
                                    class="text-[10px] font-bold px-2.5 py-1 bg-gray-50 dark:bg-slate-800 text-gray-600 dark:text-gray-400 rounded-lg hover:bg-shopee/10 hover:text-shopee border border-gray-200/50 dark:border-slate-700/50 transition-all select-none">
                                <span x-text="isGroupAllSelected('business') ? '{{ __('Bỏ chọn tất cả') }}' : '{{ __('Chọn tất cả') }}'"></span>
                            </button>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Cashback Click Logs -->
                            <div class="border p-4 rounded-2xl flex flex-col justify-between transition-all duration-300 relative overflow-hidden group cursor-pointer md:col-span-2"
                                 @click="let cb = $el.querySelector('input[type=checkbox]'); if($event.target !== cb && !$el.querySelector('select')?.contains($event.target)) { cb.checked = !cb.checked; cb.dispatchEvent(new Event('change')) }"
                                 :class="selectedActions.includes('cashback_clicks') ? 'border-shopee bg-shopee/5 dark:bg-shopee/10' : 'border-gray-100 dark:border-slate-800/80 bg-white dark:bg-slate-900 hover:border-gray-200 dark:hover:border-slate-700'">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="flex items-center gap-3">
                                        <div class="p-2 rounded-xl bg-orange-500/10 text-orange-500 group-hover:scale-110 transition-transform">
                                            <i data-lucide="mouse-pointer-click" class="w-4 h-4"></i>
                                        </div>
                                        <div>
                                            <h4 class="text-xs font-bold text-gray-800 dark:text-gray-200">{{ __('Nhật ký click hoàn tiền') }}</h4>
                                            <span class="inline-block mt-0.5 px-2 py-0.5 bg-orange-50 dark:bg-orange-950/20 text-orange-600 dark:text-orange-400 text-[9px] font-bold rounded-lg border border-orange-100 dark:border-orange-900/30">
                                                {{ number_format($counts['cashback_clicks']) }} {{ __('lượt click') }}
                                            </span>
                                        </div>
                                    </div>
                                    <input type="checkbox" name="actions[]" value="cashback_clicks" x-model="selectedActions"
                                           class="text-shopee focus:ring-shopee rounded border-gray-300 dark:border-slate-700 w-4.5 h-4.5 cursor-pointer">
                                </div>
                                <div x-show="selectedActions.includes('cashback_clicks')" 
                                     x-transition:enter="transition ease-out duration-200"
                                     x-transition:enter-start="opacity-0 transform -translate-y-2"
                                     x-transition:enter-end="opacity-100 transform translate-y-0"
                                     class="mt-4 pt-4 border-t border-dashed border-gray-200/60 dark:border-slate-800 flex items-center justify-between">
                                    <span class="text-[10px] text-gray-400">{{ __('Thời hạn giữ lại:') }}</span>
                                    <select name="keep_recent_clicks" class="px-2.5 py-1.5 border border-gray-200 dark:border-slate-700 rounded-xl text-[10px] focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-300 font-medium">
                                        <option value="3">{{ __('Giữ lại 3 ngày gần nhất') }}</option>
                                        <option value="7">{{ __('Giữ lại 7 ngày gần nhất') }}</option>
                                        <option value="14">{{ __('Giữ lại 14 ngày gần nhất') }}</option>
                                        <option value="30" selected>{{ __('Giữ lại 30 ngày gần nhất') }}</option>
                                        <option value="60">{{ __('Giữ lại 60 ngày gần nhất') }}</option>
                                        <option value="180">{{ __('Giữ lại 180 ngày gần nhất') }}</option>
                                        <option value="365">{{ __('Giữ lại 1 năm gần nhất') }}</option>
                                        <option value="0">{{ __('Xóa toàn bộ (Không giữ lại)') }}</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Cashback Histories -->
                            <div class="border p-4 rounded-2xl flex flex-col justify-between transition-all duration-300 relative overflow-hidden group cursor-pointer md:col-span-2"
                                 @click="let cb = $el.querySelector('input[type=checkbox]'); if($event.target !== cb && !$el.querySelector('select')?.contains($event.target)) { cb.checked = !cb.checked; cb.dispatchEvent(new Event('change')) }"
                                 :class="selectedActions.includes('cashback_histories') ? 'border-shopee bg-shopee/5 dark:bg-shopee/10' : 'border-gray-100 dark:border-slate-800/80 bg-white dark:bg-slate-900 hover:border-gray-200 dark:hover:border-slate-700'">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="flex items-center gap-3">
                                        <div class="p-2 rounded-xl bg-rose-500/10 text-rose-500 group-hover:scale-110 transition-transform">
                                            <i data-lucide="shopping-bag" class="w-4 h-4"></i>
                                        </div>
                                        <div>
                                            <h4 class="text-xs font-bold text-gray-800 dark:text-gray-200">{{ __('Đơn hàng hoàn tiền (Cashback Histories)') }}</h4>
                                            <span class="inline-block mt-0.5 px-2 py-0.5 bg-rose-50 dark:bg-rose-950/20 text-rose-600 dark:text-rose-400 text-[9px] font-bold rounded-lg border border-rose-100 dark:border-rose-900/30">
                                                {{ number_format($counts['cashback_histories']) }} {{ __('đơn hàng') }}
                                            </span>
                                        </div>
                                    </div>
                                    <input type="checkbox" name="actions[]" value="cashback_histories" x-model="selectedActions"
                                           class="text-shopee focus:ring-shopee rounded border-gray-300 dark:border-slate-700 w-4.5 h-4.5 cursor-pointer">
                                </div>
                                <div x-show="selectedActions.includes('cashback_histories')" 
                                     x-transition:enter="transition ease-out duration-200"
                                     x-transition:enter-start="opacity-0 transform -translate-y-2"
                                     x-transition:enter-end="opacity-100 transform translate-y-0"
                                     class="mt-4 pt-4 border-t border-dashed border-gray-200/60 dark:border-slate-800 flex items-center justify-between">
                                    <span class="text-[10px] text-gray-400">{{ __('Thời hạn giữ lại:') }}</span>
                                    <select name="keep_orders" class="px-2.5 py-1.5 border border-gray-200 dark:border-slate-700 rounded-xl text-[10px] focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-300 font-medium">
                                        <option value="3">{{ __('Giữ lại 3 ngày gần nhất') }}</option>
                                        <option value="7">{{ __('Giữ lại 7 ngày gần nhất') }}</option>
                                        <option value="14">{{ __('Giữ lại 14 ngày gần nhất') }}</option>
                                        <option value="30" selected>{{ __('Giữ lại 30 ngày gần nhất') }}</option>
                                        <option value="60">{{ __('Giữ lại 60 ngày gần nhất') }}</option>
                                        <option value="180">{{ __('Giữ lại 180 ngày gần nhất') }}</option>
                                        <option value="365">{{ __('Giữ lại 1 năm gần nhất') }}</option>
                                        <option value="0">{{ __('Xóa toàn bộ (Không giữ lại)') }}</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Withdrawals -->
                            <div class="border p-4 rounded-2xl flex flex-col justify-between transition-all duration-300 relative overflow-hidden group cursor-pointer md:col-span-2"
                                 @click="let cb = $el.querySelector('input[type=checkbox]'); if($event.target !== cb && !$el.querySelector('select')?.contains($event.target)) { cb.checked = !cb.checked; cb.dispatchEvent(new Event('change')) }"
                                 :class="selectedActions.includes('withdrawals') ? 'border-shopee bg-shopee/5 dark:bg-shopee/10' : 'border-gray-100 dark:border-slate-800/80 bg-white dark:bg-slate-900 hover:border-gray-200 dark:hover:border-slate-700'">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="flex items-center gap-3">
                                        <div class="p-2 rounded-xl bg-emerald-500/10 text-emerald-500 group-hover:scale-110 transition-transform">
                                            <i data-lucide="wallet" class="w-4 h-4"></i>
                                        </div>
                                        <div>
                                            <h4 class="text-xs font-bold text-gray-800 dark:text-gray-200">{{ __('Lịch sử rút tiền') }}</h4>
                                            <span class="inline-block mt-0.5 px-2 py-0.5 bg-emerald-50 dark:bg-emerald-950/20 text-emerald-600 dark:text-emerald-400 text-[9px] font-bold rounded-lg border border-emerald-100 dark:border-emerald-900/30">
                                                {{ number_format($counts['withdrawals']) }} {{ __('giao dịch') }}
                                            </span>
                                        </div>
                                    </div>
                                    <input type="checkbox" name="actions[]" value="withdrawals" x-model="selectedActions"
                                           class="text-shopee focus:ring-shopee rounded border-gray-300 dark:border-slate-700 w-4.5 h-4.5 cursor-pointer">
                                </div>
                                <div x-show="selectedActions.includes('withdrawals')" 
                                     x-transition:enter="transition ease-out duration-200"
                                     x-transition:enter-start="opacity-0 transform -translate-y-2"
                                     x-transition:enter-end="opacity-100 transform translate-y-0"
                                     class="mt-4 pt-4 border-t border-dashed border-gray-200/60 dark:border-slate-800 flex items-center justify-between">
                                    <span class="text-[10px] text-gray-400">{{ __('Thời hạn giữ lại:') }}</span>
                                    <select name="keep_recent_withdrawals" class="px-2.5 py-1.5 border border-gray-200 dark:border-slate-700 rounded-xl text-[10px] focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-300 font-medium">
                                        <option value="3">{{ __('Giữ lại 3 ngày gần nhất') }}</option>
                                        <option value="7">{{ __('Giữ lại 7 ngày gần nhất') }}</option>
                                        <option value="14">{{ __('Giữ lại 14 ngày gần nhất') }}</option>
                                        <option value="30" selected>{{ __('Giữ lại 30 ngày gần nhất') }}</option>
                                        <option value="60">{{ __('Giữ lại 60 ngày gần nhất') }}</option>
                                        <option value="180">{{ __('Giữ lại 180 ngày gần nhất') }}</option>
                                        <option value="365">{{ __('Giữ lại 1 năm gần nhất') }}</option>
                                        <option value="0">{{ __('Xóa toàn bộ (Không giữ lại)') }}</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Gift Redemptions -->
                            <div class="border p-4 rounded-2xl flex flex-col justify-between transition-all duration-300 relative overflow-hidden group cursor-pointer"
                                 @click="let cb = $el.querySelector('input[type=checkbox]'); if($event.target !== cb && !$el.querySelector('select')?.contains($event.target)) { cb.checked = !cb.checked; cb.dispatchEvent(new Event('change')) }"
                                 :class="selectedActions.includes('gift_redemptions') ? 'border-shopee bg-shopee/5 dark:bg-shopee/10' : 'border-gray-100 dark:border-slate-800/80 bg-white dark:bg-slate-900 hover:border-gray-200 dark:hover:border-slate-700'">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="p-2 rounded-xl bg-cyan-500/10 text-cyan-500 group-hover:scale-110 transition-transform">
                                        <i data-lucide="gift" class="w-4 h-4"></i>
                                    </div>
                                    <input type="checkbox" name="actions[]" value="gift_redemptions" x-model="selectedActions"
                                           class="text-shopee focus:ring-shopee rounded border-gray-300 dark:border-slate-700 w-4.5 h-4.5 cursor-pointer">
                                </div>
                                <div class="mt-4">
                                    <h4 class="text-xs font-bold text-gray-800 dark:text-gray-200">{{ __('Lịch sử đổi quà tặng') }}</h4>
                                    <span class="inline-block mt-1 px-2 py-0.5 bg-cyan-50 dark:bg-cyan-950/20 text-cyan-600 dark:text-cyan-400 text-[9px] font-bold rounded-lg border border-cyan-100 dark:border-cyan-900/30">
                                        {{ number_format($counts['gift_redemptions']) }} {{ __('bản ghi') }}
                                    </span>
                                </div>
                                <div x-show="selectedActions.includes('gift_redemptions')" 
                                     x-transition:enter="transition ease-out duration-200"
                                     x-transition:enter-start="opacity-0 transform -translate-y-2"
                                     x-transition:enter-end="opacity-100 transform translate-y-0"
                                     class="mt-4 pt-4 border-t border-dashed border-gray-200/60 dark:border-slate-800 flex items-center justify-between">
                                    <span class="text-[10px] text-gray-400">{{ __('Thời hạn giữ lại:') }}</span>
                                    <select name="keep_recent_gift_redemptions" class="px-2.5 py-1.5 border border-gray-200 dark:border-slate-700 rounded-xl text-[10px] focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-300 font-medium">
                                        <option value="3">{{ __('Giữ lại 3 ngày gần nhất') }}</option>
                                        <option value="7">{{ __('Giữ lại 7 ngày gần nhất') }}</option>
                                        <option value="14">{{ __('Giữ lại 14 ngày gần nhất') }}</option>
                                        <option value="30" selected>{{ __('Giữ lại 30 ngày gần nhất') }}</option>
                                        <option value="60">{{ __('Giữ lại 60 ngày gần nhất') }}</option>
                                        <option value="180">{{ __('Giữ lại 180 ngày gần nhất') }}</option>
                                        <option value="365">{{ __('Giữ lại 1 năm gần nhất') }}</option>
                                        <option value="0">{{ __('Xóa toàn bộ (Không giữ lại)') }}</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Gift Code Redemptions -->
                            <div class="border p-4 rounded-2xl flex flex-col justify-between transition-all duration-300 relative overflow-hidden group cursor-pointer"
                                 @click="let cb = $el.querySelector('input[type=checkbox]'); if($event.target !== cb && !$el.querySelector('select')?.contains($event.target)) { cb.checked = !cb.checked; cb.dispatchEvent(new Event('change')) }"
                                 :class="selectedActions.includes('gift_code_redemptions') ? 'border-shopee bg-shopee/5 dark:bg-shopee/10' : 'border-gray-100 dark:border-slate-800/80 bg-white dark:bg-slate-900 hover:border-gray-200 dark:hover:border-slate-700'">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="p-2 rounded-xl bg-teal-500/10 text-teal-500 group-hover:scale-110 transition-transform">
                                        <i data-lucide="ticket" class="w-4 h-4"></i>
                                    </div>
                                    <input type="checkbox" name="actions[]" value="gift_code_redemptions" x-model="selectedActions"
                                           class="text-shopee focus:ring-shopee rounded border-gray-300 dark:border-slate-700 w-4.5 h-4.5 cursor-pointer">
                                </div>
                                <div class="mt-4">
                                    <h4 class="text-xs font-bold text-gray-800 dark:text-gray-200">{{ __('Lịch sử nhập Giftcode') }}</h4>
                                    <span class="inline-block mt-1 px-2 py-0.5 bg-teal-50 dark:bg-teal-950/20 text-teal-600 dark:text-teal-400 text-[9px] font-bold rounded-lg border border-teal-100 dark:border-teal-900/30">
                                        {{ number_format($counts['gift_code_redemptions']) }} {{ __('bản ghi') }}
                                    </span>
                                </div>
                                <div x-show="selectedActions.includes('gift_code_redemptions')" 
                                     x-transition:enter="transition ease-out duration-200"
                                     x-transition:enter-start="opacity-0 transform -translate-y-2"
                                     x-transition:enter-end="opacity-100 transform translate-y-0"
                                     class="mt-4 pt-4 border-t border-dashed border-gray-200/60 dark:border-slate-800 flex items-center justify-between">
                                    <span class="text-[10px] text-gray-400">{{ __('Thời hạn giữ lại:') }}</span>
                                    <select name="keep_recent_gift_code_redemptions" class="px-2.5 py-1.5 border border-gray-200 dark:border-slate-700 rounded-xl text-[10px] focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-300 font-medium">
                                        <option value="3">{{ __('Giữ lại 3 ngày gần nhất') }}</option>
                                        <option value="7">{{ __('Giữ lại 7 ngày gần nhất') }}</option>
                                        <option value="14">{{ __('Giữ lại 14 ngày gần nhất') }}</option>
                                        <option value="30" selected>{{ __('Giữ lại 30 ngày gần nhất') }}</option>
                                        <option value="60">{{ __('Giữ lại 60 ngày gần nhất') }}</option>
                                        <option value="180">{{ __('Giữ lại 180 ngày gần nhất') }}</option>
                                        <option value="365">{{ __('Giữ lại 1 năm gần nhất') }}</option>
                                        <option value="0">{{ __('Xóa toàn bộ (Không giữ lại)') }}</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Balance Logs -->
                            <div class="border p-4 rounded-2xl flex flex-col justify-between transition-all duration-300 relative overflow-hidden group cursor-pointer md:col-span-2"
                                 @click="let cb = $el.querySelector('input[type=checkbox]'); if($event.target !== cb && !$el.querySelector('select')?.contains($event.target)) { cb.checked = !cb.checked; cb.dispatchEvent(new Event('change')) }"
                                 :class="selectedActions.includes('balance_logs') ? 'border-shopee bg-shopee/5 dark:bg-shopee/10' : 'border-gray-100 dark:border-slate-800/80 bg-white dark:bg-slate-900 hover:border-gray-200 dark:hover:border-slate-700'">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="flex items-center gap-3">
                                        <div class="p-2 rounded-xl bg-lime-500/10 text-lime-600 group-hover:scale-110 transition-transform">
                                            <i data-lucide="history" class="w-4 h-4"></i>
                                        </div>
                                        <div>
                                            <h4 class="text-xs font-bold text-gray-800 dark:text-gray-200">{{ __('Biến động số dư dòng tiền') }}</h4>
                                            <span class="inline-block mt-0.5 px-2 py-0.5 bg-lime-50 dark:bg-lime-950/20 text-lime-600 dark:text-lime-400 text-[9px] font-bold rounded-lg border border-lime-100 dark:border-lime-900/30">
                                                {{ number_format($counts['balance_logs']) }} {{ __('bản ghi') }}
                                            </span>
                                        </div>
                                    </div>
                                    <input type="checkbox" name="actions[]" value="balance_logs" x-model="selectedActions"
                                           class="text-shopee focus:ring-shopee rounded border-gray-300 dark:border-slate-700 w-4.5 h-4.5 cursor-pointer">
                                </div>
                                <div x-show="selectedActions.includes('balance_logs')" 
                                     x-transition:enter="transition ease-out duration-200"
                                     x-transition:enter-start="opacity-0 transform -translate-y-2"
                                     x-transition:enter-end="opacity-100 transform translate-y-0"
                                     class="mt-4 pt-4 border-t border-dashed border-gray-200/60 dark:border-slate-800 flex items-center justify-between">
                                    <span class="text-[10px] text-gray-400">{{ __('Thời hạn giữ lại:') }}</span>
                                    <select name="keep_recent_balance_logs" class="px-2.5 py-1.5 border border-gray-200 dark:border-slate-700 rounded-xl text-[10px] focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-300 font-medium">
                                        <option value="3">{{ __('Giữ lại 3 ngày gần nhất') }}</option>
                                        <option value="7">{{ __('Giữ lại 7 ngày gần nhất') }}</option>
                                        <option value="14">{{ __('Giữ lại 14 ngày gần nhất') }}</option>
                                        <option value="30" selected>{{ __('Giữ lại 30 ngày gần nhất') }}</option>
                                        <option value="60">{{ __('Giữ lại 60 ngày gần nhất') }}</option>
                                        <option value="180">{{ __('Giữ lại 180 ngày gần nhất') }}</option>
                                        <option value="365">{{ __('Giữ lại 1 năm gần nhất') }}</option>
                                        <option value="0">{{ __('Xóa toàn bộ (Không giữ lại)') }}</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Daily Checkins -->
                            <div class="border p-4 rounded-2xl flex flex-col justify-between transition-all duration-300 relative overflow-hidden group cursor-pointer"
                                 @click="let cb = $el.querySelector('input[type=checkbox]'); if($event.target !== cb && !$el.querySelector('select')?.contains($event.target)) { cb.checked = !cb.checked; cb.dispatchEvent(new Event('change')) }"
                                 :class="selectedActions.includes('daily_checkins') ? 'border-shopee bg-shopee/5 dark:bg-shopee/10' : 'border-gray-100 dark:border-slate-800/80 bg-white dark:bg-slate-900 hover:border-gray-200 dark:hover:border-slate-700'">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="p-2 rounded-xl bg-purple-500/10 text-purple-500 group-hover:scale-110 transition-transform">
                                        <i data-lucide="calendar-check" class="w-4 h-4"></i>
                                    </div>
                                    <input type="checkbox" name="actions[]" value="daily_checkins" x-model="selectedActions"
                                           class="text-shopee focus:ring-shopee rounded border-gray-300 dark:border-slate-700 w-4.5 h-4.5 cursor-pointer">
                                </div>
                                <div class="mt-4">
                                    <h4 class="text-xs font-bold text-gray-800 dark:text-gray-200">{{ __('Nhật ký điểm danh') }}</h4>
                                    <span class="inline-block mt-1 px-2 py-0.5 bg-purple-50 dark:bg-purple-950/20 text-purple-600 dark:text-purple-400 text-[9px] font-bold rounded-lg border border-purple-100 dark:border-purple-900/30">
                                        {{ number_format($counts['daily_checkins']) }} {{ __('bản ghi') }}
                                    </span>
                                </div>
                                <div x-show="selectedActions.includes('daily_checkins')" 
                                     x-transition:enter="transition ease-out duration-200"
                                     x-transition:enter-start="opacity-0 transform -translate-y-2"
                                     x-transition:enter-end="opacity-100 transform translate-y-0"
                                     class="mt-4 pt-4 border-t border-dashed border-gray-200/60 dark:border-slate-800 flex items-center justify-between">
                                    <span class="text-[10px] text-gray-400">{{ __('Thời hạn giữ lại:') }}</span>
                                    <select name="keep_recent_daily_checkins" class="px-2.5 py-1.5 border border-gray-200 dark:border-slate-700 rounded-xl text-[10px] focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-300 font-medium">
                                        <option value="3">{{ __('Giữ lại 3 ngày gần nhất') }}</option>
                                        <option value="7">{{ __('Giữ lại 7 ngày gần nhất') }}</option>
                                        <option value="14">{{ __('Giữ lại 14 ngày gần nhất') }}</option>
                                        <option value="30" selected>{{ __('Giữ lại 30 ngày gần nhất') }}</option>
                                        <option value="60">{{ __('Giữ lại 60 ngày gần nhất') }}</option>
                                        <option value="180">{{ __('Giữ lại 180 ngày gần nhất') }}</option>
                                        <option value="365">{{ __('Giữ lại 1 năm gần nhất') }}</option>
                                        <option value="0">{{ __('Xóa toàn bộ (Không giữ lại)') }}</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Referral Commissions -->
                            <div class="border p-4 rounded-2xl flex flex-col justify-between transition-all duration-300 relative overflow-hidden group cursor-pointer"
                                 @click="let cb = $el.querySelector('input[type=checkbox]'); if($event.target !== cb && !$el.querySelector('select')?.contains($event.target)) { cb.checked = !cb.checked; cb.dispatchEvent(new Event('change')) }"
                                 :class="selectedActions.includes('referral_commissions') ? 'border-shopee bg-shopee/5 dark:bg-shopee/10' : 'border-gray-100 dark:border-slate-800/80 bg-white dark:bg-slate-900 hover:border-gray-200 dark:hover:border-slate-700'">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="p-2 rounded-xl bg-indigo-500/10 text-indigo-500 group-hover:scale-110 transition-transform">
                                        <i data-lucide="users-2" class="w-4 h-4"></i>
                                    </div>
                                    <input type="checkbox" name="actions[]" value="referral_commissions" x-model="selectedActions"
                                           class="text-shopee focus:ring-shopee rounded border-gray-300 dark:border-slate-700 w-4.5 h-4.5 cursor-pointer">
                                </div>
                                <div class="mt-4">
                                    <h4 class="text-xs font-bold text-gray-800 dark:text-gray-200">{{ __('Hoa hồng Affiliate F1/F2') }}</h4>
                                    <span class="inline-block mt-1 px-2 py-0.5 bg-indigo-50 dark:bg-indigo-950/20 text-indigo-600 dark:text-indigo-400 text-[9px] font-bold rounded-lg border border-indigo-100 dark:border-indigo-900/30">
                                        {{ number_format($counts['referral_commissions']) }} {{ __('bản ghi') }}
                                    </span>
                                </div>
                                <div x-show="selectedActions.includes('referral_commissions')" 
                                     x-transition:enter="transition ease-out duration-200"
                                     x-transition:enter-start="opacity-0 transform -translate-y-2"
                                     x-transition:enter-end="opacity-100 transform translate-y-0"
                                     class="mt-4 pt-4 border-t border-dashed border-gray-200/60 dark:border-slate-800 flex items-center justify-between">
                                    <span class="text-[10px] text-gray-400">{{ __('Thời hạn giữ lại:') }}</span>
                                    <select name="keep_recent_referral_commissions" class="px-2.5 py-1.5 border border-gray-200 dark:border-slate-700 rounded-xl text-[10px] focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-300 font-medium">
                                        <option value="3">{{ __('Giữ lại 3 ngày gần nhất') }}</option>
                                        <option value="7">{{ __('Giữ lại 7 ngày gần nhất') }}</option>
                                        <option value="14">{{ __('Giữ lại 14 ngày gần nhất') }}</option>
                                        <option value="30" selected>{{ __('Giữ lại 30 ngày gần nhất') }}</option>
                                        <option value="60">{{ __('Giữ lại 60 ngày gần nhất') }}</option>
                                        <option value="180">{{ __('Giữ lại 180 ngày gần nhất') }}</option>
                                        <option value="365">{{ __('Giữ lại 1 năm gần nhất') }}</option>
                                        <option value="0">{{ __('Xóa toàn bộ (Không giữ lại)') }}</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Short Links -->
                            <div class="border p-4 rounded-2xl flex flex-col justify-between transition-all duration-300 relative overflow-hidden group cursor-pointer"
                                 @click="let cb = $el.querySelector('input[type=checkbox]'); if($event.target !== cb && !$el.querySelector('select')?.contains($event.target)) { cb.checked = !cb.checked; cb.dispatchEvent(new Event('change')) }"
                                 :class="selectedActions.includes('short_links') ? 'border-shopee bg-shopee/5 dark:bg-shopee/10' : 'border-gray-100 dark:border-slate-800/80 bg-white dark:bg-slate-900 hover:border-gray-200 dark:hover:border-slate-700'">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="p-2 rounded-xl bg-sky-500/10 text-sky-500 group-hover:scale-110 transition-transform">
                                        <i data-lucide="link" class="w-4 h-4"></i>
                                    </div>
                                    <input type="checkbox" name="actions[]" value="short_links" x-model="selectedActions"
                                           class="text-shopee focus:ring-shopee rounded border-gray-300 dark:border-slate-700 w-4.5 h-4.5 cursor-pointer">
                                </div>
                                <div class="mt-4">
                                    <h4 class="text-xs font-bold text-gray-800 dark:text-gray-200">{{ __('Nhật ký Short Link') }}</h4>
                                    <span class="inline-block mt-1 px-2 py-0.5 bg-sky-50 dark:bg-sky-950/20 text-sky-600 dark:text-sky-400 text-[9px] font-bold rounded-lg border border-sky-100 dark:border-sky-900/30">
                                        {{ number_format($counts['short_links']) }} {{ __('bản ghi') }}
                                    </span>
                                </div>
                                <div x-show="selectedActions.includes('short_links')" 
                                     x-transition:enter="transition ease-out duration-200"
                                     x-transition:enter-start="opacity-0 transform -translate-y-2"
                                     x-transition:enter-end="opacity-100 transform translate-y-0"
                                     class="mt-4 pt-4 border-t border-dashed border-gray-200/60 dark:border-slate-800 flex items-center justify-between">
                                    <span class="text-[10px] text-gray-400">{{ __('Thời hạn giữ lại:') }}</span>
                                    <select name="keep_recent_short_links" class="px-2.5 py-1.5 border border-gray-200 dark:border-slate-700 rounded-xl text-[10px] focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-300 font-medium">
                                        <option value="3">{{ __('Giữ lại 3 ngày gần nhất') }}</option>
                                        <option value="7">{{ __('Giữ lại 7 ngày gần nhất') }}</option>
                                        <option value="14">{{ __('Giữ lại 14 ngày gần nhất') }}</option>
                                        <option value="30" selected>{{ __('Giữ lại 30 ngày gần nhất') }}</option>
                                        <option value="60">{{ __('Giữ lại 60 ngày gần nhất') }}</option>
                                        <option value="180">{{ __('Giữ lại 180 ngày gần nhất') }}</option>
                                        <option value="365">{{ __('Giữ lại 1 năm gần nhất') }}</option>
                                        <option value="0">{{ __('Xóa toàn bộ (Không giữ lại)') }}</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Saved Products -->
                            <div class="border p-4 rounded-2xl flex flex-col justify-between transition-all duration-300 relative overflow-hidden group cursor-pointer"
                                 @click="let cb = $el.querySelector('input[type=checkbox]'); if($event.target !== cb && !$el.querySelector('select')?.contains($event.target)) { cb.checked = !cb.checked; cb.dispatchEvent(new Event('change')) }"
                                 :class="selectedActions.includes('saved_products') ? 'border-shopee bg-shopee/5 dark:bg-shopee/10' : 'border-gray-100 dark:border-slate-800/80 bg-white dark:bg-slate-900 hover:border-gray-200 dark:hover:border-slate-700'">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="p-2 rounded-xl bg-red-500/10 text-red-500 group-hover:scale-110 transition-transform">
                                        <i data-lucide="heart" class="w-4 h-4"></i>
                                    </div>
                                    <input type="checkbox" name="actions[]" value="saved_products" x-model="selectedActions"
                                           class="text-shopee focus:ring-shopee rounded border-gray-300 dark:border-slate-700 w-4.5 h-4.5 cursor-pointer">
                                </div>
                                <div class="mt-4">
                                    <h4 class="text-xs font-bold text-gray-800 dark:text-gray-200">{{ __('Sản phẩm đã lưu') }}</h4>
                                    <span class="inline-block mt-1 px-2 py-0.5 bg-red-50 dark:bg-red-950/20 text-red-600 dark:text-red-400 text-[9px] font-bold rounded-lg border border-red-100 dark:border-red-900/30">
                                        {{ number_format($counts['saved_products']) }} {{ __('bản ghi') }}
                                    </span>
                                </div>
                                <div x-show="selectedActions.includes('saved_products')" 
                                     x-transition:enter="transition ease-out duration-200"
                                     x-transition:enter-start="opacity-0 transform -translate-y-2"
                                     x-transition:enter-end="opacity-100 transform translate-y-0"
                                     class="mt-4 pt-4 border-t border-dashed border-gray-200/60 dark:border-slate-800 flex items-center justify-between">
                                    <span class="text-[10px] text-gray-400">{{ __('Thời hạn giữ lại:') }}</span>
                                    <select name="keep_recent_saved_products" class="px-2.5 py-1.5 border border-gray-200 dark:border-slate-700 rounded-xl text-[10px] focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-300 font-medium">
                                        <option value="3">{{ __('Giữ lại 3 ngày gần nhất') }}</option>
                                        <option value="7">{{ __('Giữ lại 7 ngày gần nhất') }}</option>
                                        <option value="14">{{ __('Giữ lại 14 ngày gần nhất') }}</option>
                                        <option value="30" selected>{{ __('Giữ lại 30 ngày gần nhất') }}</option>
                                        <option value="60">{{ __('Giữ lại 60 ngày gần nhất') }}</option>
                                        <option value="180">{{ __('Giữ lại 180 ngày gần nhất') }}</option>
                                        <option value="365">{{ __('Giữ lại 1 năm gần nhất') }}</option>
                                        <option value="0">{{ __('Xóa toàn bộ (Không giữ lại)') }}</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Nút Thực hiện Action -->
                    <div class="pt-6 border-t border-gray-150 dark:border-slate-800 flex items-center justify-end">
                        <button type="submit" id="btn-submit-cleanup"
                                class="inline-flex items-center gap-2 px-7 py-3.5 bg-gradient-to-r from-red-500 to-orange-500 hover:from-red-600 hover:to-orange-600 text-white font-bold rounded-2xl text-xs transition-all shadow-lg shadow-orange-500/20 whitespace-nowrap active:scale-95 duration-200 cursor-pointer">
                            <i data-lucide="brush" class="w-4 h-4"></i>
                            {{ __('Bắt đầu dọn dẹp hệ thống') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Cột Phải: Tạo & Quản lý Bản Sao Lưu Database (1/3 chiều rộng) -->
        <div class="space-y-6">
            <!-- Thẻ Tạo bản sao lưu mới -->
            <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 shadow-sm border border-gray-100 dark:border-slate-800/50 space-y-4">
                <div class="flex items-center gap-2.5 pb-3 border-b border-gray-100 dark:border-slate-800">
                    <span class="p-1.5 bg-shopee/10 text-shopee rounded-xl">
                        <i data-lucide="download-cloud" class="w-4.5 h-4.5"></i>
                    </span>
                    <h3 class="text-xs font-black uppercase text-gray-800 dark:text-gray-200 tracking-wider">
                        {{ __('Sao Lưu Dữ Liệu') }}
                    </h3>
                </div>
                <p class="text-[11px] text-gray-400 dark:text-gray-500 leading-relaxed">
                    {{ __('Tự động kết xuất toàn bộ cấu trúc và dữ liệu của website sang tệp tin SQL để bảo vệ an toàn dự phòng trước khi chạy dọn dẹp.') }}
                </p>
                <form action="{{ route('admin.system_cleanup.backup.store') }}" method="POST" id="form-backup">
                    @csrf
                    <button type="submit" id="btn-submit-backup"
                            class="w-full inline-flex items-center justify-center gap-2 px-5 py-3.5 bg-shopee hover:bg-shopee-dark text-white font-bold rounded-2xl text-xs transition-all shadow-lg shadow-shopee/20 active:scale-95 duration-200 cursor-pointer">
                        <i data-lucide="database-backup" class="w-4 h-4"></i>
                        {{ __('Tạo Sao Lưu (.SQL) Mới') }}
                    </button>
                </form>
            </div>

            <!-- Danh sách các bản sao lưu hiện có -->
            <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 shadow-sm border border-gray-100 dark:border-slate-800/50 space-y-4">
                <div class="flex items-center gap-2.5 pb-3 border-b border-gray-100 dark:border-slate-800">
                    <span class="p-1.5 bg-gray-100 dark:bg-slate-800 text-gray-500 dark:text-gray-400 rounded-xl">
                        <i data-lucide="archive" class="w-4.5 h-4.5"></i>
                    </span>
                    <h3 class="text-xs font-black uppercase text-gray-800 dark:text-gray-200 tracking-wider">
                        {{ __('Lịch Sử Bản Sao Lưu') }}
                    </h3>
                </div>
                
                @if(empty($backups))
                    <div class="text-center py-8 text-gray-400 dark:text-gray-500 italic text-xs flex flex-col items-center justify-center gap-2">
                        <i data-lucide="folder-open" class="w-8 h-8 text-gray-300 dark:text-slate-700"></i>
                        <span>{{ __('Chưa có bản sao lưu nào được lưu.') }}</span>
                    </div>
                @else
                    <div class="space-y-3 max-h-[400px] overflow-y-auto pr-1">
                        @foreach($backups as $bk)
                            <div class="p-3.5 bg-gray-50 dark:bg-slate-800/35 rounded-2xl border border-gray-150/40 dark:border-slate-850 flex flex-col gap-2.5 hover:border-gray-250 dark:hover:border-slate-750 transition-all group">
                                <div class="flex items-start gap-2.5">
                                    <div class="p-1.5 bg-blue-500/10 text-blue-500 rounded-lg shrink-0 mt-0.5">
                                        <i data-lucide="file-code-2" class="w-4 h-4"></i>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <p class="text-[11px] font-bold text-gray-800 dark:text-gray-200 truncate" title="{{ $bk['filename'] }}">
                                            {{ $bk['filename'] }}
                                        </p>
                                        <p class="text-[9px] text-gray-400 dark:text-gray-500 mt-1 flex items-center gap-1">
                                            <i data-lucide="calendar" class="w-3 h-3"></i>
                                            {{ $bk['created_at'] }}
                                        </p>
                                    </div>
                                </div>
                                <div class="flex items-center justify-between pt-2.5 border-t border-gray-200/50 dark:border-slate-800/50">
                                    <span class="inline-flex items-center px-2 py-0.5 bg-blue-50/50 dark:bg-blue-950/20 text-blue-600 dark:text-blue-400 text-[8px] font-bold rounded-lg border border-blue-100/60 dark:border-blue-900/30 shrink-0">
                                        {{ $bk['size'] }} KB
                                    </span>
                                    <div class="flex items-center gap-3">
                                        <!-- Tải về -->
                                        <a href="{{ route('admin.system_cleanup.backup.download', $bk['filename']) }}"
                                           class="inline-flex items-center gap-1 text-[10px] font-bold text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300">
                                            <i data-lucide="download" class="w-3.5 h-3.5"></i>
                                            {{ __('Tải về') }}
                                        </a>
                                        <span class="text-gray-200 dark:text-slate-800">|</span>
                                        <!-- Xoá bản sao lưu -->
                                        <form action="{{ route('admin.system_cleanup.backup.destroy', $bk['filename']) }}" method="POST"
                                              class="form-delete-backup">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="inline-flex items-center gap-1 text-[10px] font-bold text-red-500 hover:text-red-600 cursor-pointer">
                                                <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                                {{ __('Xoá') }}
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
    @endif
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Xử lý xác nhận dọn dẹp hệ thống bằng SweetAlert2
        const formCleanup = document.getElementById('form-cleanup');
        if (formCleanup) {
            formCleanup.addEventListener('submit', async function(e) {
                e.preventDefault();
                
                // Thu thập số lượng các action được check để thông báo rõ ràng cho Admin
                const checkboxes = formCleanup.querySelectorAll('input[name="actions[]"]:checked');
                if (checkboxes.length === 0) {
                    Swal.fire({
                        icon: 'warning',
                        title: '{{ __('Cảnh báo') }}',
                        text: '{{ __('Vui lòng chọn ít nhất một mục để thực hiện dọn dẹp.') }}',
                        customClass: {
                            confirmButton: 'inline-flex justify-center items-center gap-2 rounded-xl bg-shopee px-5 py-2.5 text-xs font-bold text-white hover:bg-shopee-dark transition shadow-lg'
                        },
                        buttonsStyling: false
                    });
                    return;
                }

                const result = await Swal.fire({
                    title: '{{ __('Xác nhận dọn dẹp hệ thống?') }}',
                    text: '{{ __('Hành động dọn dẹp các mục đã chọn sẽ thay đổi vĩnh viễn dữ liệu. Hãy chắc chắn rằng bạn đã sao lưu database trước khi thực hiện!') }}',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#ff5722',
                    cancelButtonColor: '#6B7280',
                    confirmButtonText: '🧹 {{ __('Đồng ý dọn dẹp') }}',
                    cancelButtonText: '{{ __('Hủy bỏ') }}',
                    customClass: {
                        container: 'admin-modal',
                        confirmButton: 'inline-flex justify-center items-center gap-2 rounded-xl bg-shopee px-5 py-2.5 text-xs font-bold text-white hover:bg-shopee-dark transition shadow-lg mr-3 cursor-pointer',
                        cancelButton: 'inline-flex justify-center items-center gap-2 rounded-xl bg-gray-500 px-5 py-2.5 text-xs font-bold text-white hover:bg-gray-600 transition shadow-lg cursor-pointer'
                    },
                    buttonsStyling: false
                });

                if (result.isConfirmed) {
                    Swal.fire({
                        title: '{{ __('Đang xử lý dọn dẹp...') }}',
                        html: '{{ __('Hệ thống đang tiến hành giải phóng dữ liệu. Vui lòng không đóng tab trình duyệt.') }}',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });
                    formCleanup.submit();
                }
            });
        }

        // Xử lý loader hiệu ứng khi bấm Tạo Sao Lưu database
        const formBackup = document.getElementById('form-backup');
        if (formBackup) {
            formBackup.addEventListener('submit', function(e) {
                e.preventDefault();
                
                Swal.fire({
                    title: '{{ __('Đang sao lưu Database...') }}',
                    html: '{{ __('Hệ thống đang xuất tệp tin SQL và nén dữ liệu. Vui lòng chờ trong giây lát...') }}',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
                formBackup.submit();
            });
        }

        // Xử lý xác nhận xóa tệp backup bằng SweetAlert2 thay thế confirm mặc định của trình duyệt
        const deleteForms = document.querySelectorAll('.form-delete-backup');
        deleteForms.forEach(form => {
            form.addEventListener('submit', async function(e) {
                e.preventDefault();
                
                const result = await Swal.fire({
                    title: '{{ __('Xóa bản sao lưu?') }}',
                    text: '{{ __('Bản sao lưu cơ sở dữ liệu này sẽ bị xóa vĩnh viễn khỏi máy chủ. Bạn không thể hoàn tác hành động này!') }}',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#ef4444',
                    cancelButtonColor: '#6B7280',
                    confirmButtonText: '🗑️ {{ __('Đồng ý xóa') }}',
                    cancelButtonText: '{{ __('Hủy') }}',
                    customClass: {
                        confirmButton: 'inline-flex justify-center items-center gap-2 rounded-xl bg-red-500 px-5 py-2.5 text-xs font-bold text-white hover:bg-red-600 transition shadow-lg mr-3 cursor-pointer',
                        cancelButton: 'inline-flex justify-center items-center gap-2 rounded-xl bg-gray-500 px-5 py-2.5 text-xs font-bold text-white hover:bg-gray-600 transition shadow-lg cursor-pointer'
                    },
                    buttonsStyling: false
                });

                if (result.isConfirmed) {
                    Swal.fire({
                        title: '{{ __('Đang xóa tệp...') }}',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });
                    form.submit();
                }
            });
        });
    });
</script>
@endsection
