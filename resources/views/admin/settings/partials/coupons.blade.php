@php
    // Lấy thời điểm chạy đồng bộ mã giảm giá gần nhất từ cấu hình hệ thống
    $couponsLastRun = \App\Models\Setting::getVal('cron_last_run_coupons_sync');
    // Cảnh báo nếu scheduler không chạy quá 1 giờ (bình thường chạy mỗi 30 phút)
    $isCouponsCronRunning = $couponsLastRun && (time() - strtotime($couponsLastRun)) < 3600;
@endphp

<div x-show="tab === 'coupons'" 
     x-data="{ 
         runningTask: '', 
         async runTask(taskName) {
             if (this.runningTask) return;
             this.runningTask = taskName;
             try {
                 // Gửi request AJAX thực hiện kích hoạt đồng bộ coupons thủ công lập tức
                 const response = await axios.post('{{ route('admin.settings.run_cron') }}', {
                     task: taskName
                 });
                 this.$dispatch('toast', { text: response.data.message || '{{ __('Thực thi tác vụ thành công!') }}', type: 'success' });
                 
                 // Đợi 1.5 giây để quản trị viên đọc thông báo trước khi tải lại trang cập nhật dữ liệu mới
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

    <!-- Quản lý mã giảm giá thủ công (Tự nhập tay theo ý muốn) -->
    @if(auth()->user()->hasPermission('manage_coupons'))
    <div class="bg-gradient-to-r from-shopee/5 to-shopee/10 dark:from-shopee/10 dark:to-shopee/5 rounded-2xl p-4 sm:p-5 border border-shopee/20 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="flex items-start gap-3">
            <div class="w-10 h-10 rounded-xl bg-shopee/10 flex items-center justify-center shrink-0">
                <i data-lucide="ticket-percent" class="w-5 h-5 text-shopee"></i>
            </div>
            <div>
                <h3 class="text-sm font-bold text-gray-800 dark:text-slate-200">{{ __('Quản lý mã giảm giá thủ công') }}</h3>
                <p class="text-[11px] text-gray-500 dark:text-slate-400 mt-0.5 max-w-xl">{{ __('Tự thêm, chỉnh sửa và xóa mã giảm giá theo ý muốn thay vì phụ thuộc hoàn toàn vào API. Mã tự nhập sẽ không bị dữ liệu đồng bộ ghi đè.') }}</p>
            </div>
        </div>
        <a href="{{ route('admin.coupons.index') }}"
           class="inline-flex items-center justify-center gap-1.5 px-4 py-2.5 bg-shopee hover:bg-shopee-dark text-white text-xs font-bold rounded-xl transition-all shadow-md active:scale-95 shrink-0">
            <i data-lucide="list" class="w-4 h-4"></i>
            {{ __('Mở trang quản lý mã') }}
        </a>
    </div>
    @endif

    <!-- Cấu hình kết nối API Mã giảm giá -->
    <div class="bg-gray-50/50 dark:bg-slate-900/50 rounded-2xl p-4 sm:p-6 border border-gray-150 dark:border-slate-800 space-y-6 w-full">
        <h3 class="text-xs font-bold text-gray-700 dark:text-slate-300 uppercase tracking-wider flex items-center gap-1.5 pb-2 border-b border-gray-200/65 dark:border-slate-800">
            <i data-lucide="key-round" class="w-4 h-4 text-shopee"></i>
            {{ __('Cấu hình API mã giảm giá đa sàn') }}
        </h3>

        <!-- Đường dẫn trang khách -->
        <div class="p-3 bg-blue-50/50 dark:bg-blue-950/20 border border-blue-100 dark:border-blue-900/50 rounded-2xl flex items-center justify-between gap-3 text-xs text-blue-700 dark:text-blue-400">
            <div class="flex items-center gap-2">
                <i data-lucide="info" class="w-4.5 h-4.5 text-blue-500 shrink-0"></i>
                <span>{{ __('Đường dẫn trang mã giảm giá ngoài trang khách:') }} <a href="{{ route('coupons.index') }}" target="_blank" class="font-bold underline hover:text-blue-800 dark:hover:text-blue-300 break-all">{{ route('coupons.index') }}</a></span>
            </div>
            <button type="button" @click="navigator.clipboard.writeText('{{ route('coupons.index') }}'); $dispatch('toast', { text: '{{ __('Đã sao chép đường dẫn thành công!') }}', type: 'success' })" class="px-2.5 py-1.5 bg-white dark:bg-slate-800 hover:bg-gray-50 dark:hover:bg-slate-750 border border-gray-200 dark:border-slate-700 text-[10px] font-bold rounded-xl transition-all active:scale-95 shadow-sm flex items-center gap-1 cursor-pointer shrink-0">
                <i data-lucide="copy" class="w-3 h-3 text-gray-400"></i>
                {{ __('Sao chép') }}
            </button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label for="coupon_status" class="block text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider mb-1">{{ __('Trạng thái hiển thị mã giảm giá') }}</label>
                <select name="coupon_status"
                    id="coupon_status"
                    class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-800 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-850 text-gray-700 dark:text-slate-300">
                    <option value="1" {{ ($settings['coupon_status'] ?? '1') === '1' ? 'selected' : '' }}>{{ __('BẬT (ON)') }}</option>
                    <option value="0" {{ ($settings['coupon_status'] ?? '1') === '0' ? 'selected' : '' }}>{{ __('TẮT (OFF)') }}</option>
                </select>
                <span class="text-[9px] text-gray-400 mt-1 block">{{ __('Bật để hiển thị trang mã giảm giá ngoài trang khách. Tắt sẽ ẩn toàn bộ tính năng mã giảm giá.') }}</span>
            </div>

            <div>
                <label for="coupon_auto_sync" class="block text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider mb-1">{{ __('Tự động đồng bộ từ API') }}</label>
                <select name="coupon_auto_sync"
                    id="coupon_auto_sync"
                    class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-800 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-850 text-gray-700 dark:text-slate-300">
                    <option value="1" {{ ($settings['coupon_auto_sync'] ?? '1') === '1' ? 'selected' : '' }}>{{ __('BẬT (ON)') }}</option>
                    <option value="0" {{ ($settings['coupon_auto_sync'] ?? '1') === '0' ? 'selected' : '' }}>{{ __('TẮT (OFF)') }}</option>
                </select>
                <span class="text-[9px] text-gray-400 mt-1 block">{{ __('Bật để định kỳ tự động lấy mã mới từ API. Tắt nếu bạn chỉ muốn dùng các mã tự nhập tay ở trang quản lý.') }}</span>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label for="coupon_api_url" class="block text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider mb-1">{{ __('Đường dẫn API (API URL)') }}</label>
                <input type="text"
                    name="coupon_api_url"
                    id="coupon_api_url"
                    value="{{ $settings['coupon_api_url'] ?? 'https://apishopee.cmsnt.co/api/v1/shopee/vouchers' }}"
                    placeholder="https://apishopee.cmsnt.co/api/v1/shopee/vouchers"
                    class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-800 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-850 text-gray-700 dark:text-slate-300">
                <span class="text-[9px] text-gray-400 mt-1 block">{{ __('Nhập endpoint API dùng để đồng bộ mã giảm giá đa sàn (mặc định lấy từ API Shopee).') }}</span>
            </div>

            <div>
                <label for="coupon_api_key" class="block text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider mb-1">{{ __('API Key (X-API-KEY)') }}</label>
                <input type="text"
                    name="coupon_api_key"
                    id="coupon_api_key"
                    value="{{ $settings['coupon_api_key'] ?? '' }}"
                    placeholder="Nhập API Key..."
                    class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-800 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-850 text-gray-700 dark:text-slate-300">
                <span class="text-[9px] text-gray-400 mt-1 block">{{ __('Mã API Key dùng để kết nối và xác thực bảo mật với hệ thống API đối tác.') }}</span>
            </div>
        </div>

        <!-- Cấu hình hành động sao chép mã giảm giá (Chuyển hướng người dùng sau khi copy thành công) -->
        <div class="pt-6 mt-6 border-t border-gray-150 dark:border-slate-800/80">
            <h4 class="text-xs font-bold text-gray-700 dark:text-slate-300 uppercase tracking-wider mb-4 flex items-center gap-1.5">
                <i data-lucide="copy" class="w-4 h-4 text-shopee"></i>
                {{ __('Hành vi sao chép mã giảm giá') }}
            </h4>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Trạng thái bật/tắt chuyển hướng tự động -->
                <div>
                    <label for="coupon_redirect_on_copy" class="block text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider mb-1">{{ __('Chuyển hướng khi sao chép') }}</label>
                    <select name="coupon_redirect_on_copy"
                        id="coupon_redirect_on_copy"
                        class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-800 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-850 text-gray-700 dark:text-slate-300">
                        <option value="1" {{ ($settings['coupon_redirect_on_copy'] ?? '0') === '1' ? 'selected' : '' }}>{{ __('BẬT (ON)') }}</option>
                        <option value="0" {{ ($settings['coupon_redirect_on_copy'] ?? '0') === '0' ? 'selected' : '' }}>{{ __('TẮT (OFF)') }}</option>
                    </select>
                    <span class="text-[9px] text-gray-400 mt-1 block">{{ __('Khi được bật, hệ thống tự động mở link chuyển hướng Shopee trong tab mới khi người dùng bấm copy mã.') }}</span>
                </div>

                <!-- URL chuyển hướng Shopee tuỳ chỉnh thay cho link mặc định của voucher -->
                <div>
                    <label for="coupon_redirect_url" class="block text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider mb-1">{{ __('Đường dẫn chuyển hướng Shopee (Tự định nghĩa)') }}</label>
                    <input type="text"
                        name="coupon_redirect_url"
                        id="coupon_redirect_url"
                        value="{{ $settings['coupon_redirect_url'] ?? '' }}"
                        placeholder="{{ __('Ví dụ: https://shope.ee/... hoặc link tiếp thị của sếp') }}"
                        class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-800 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-850 text-gray-700 dark:text-slate-300">
                    <span class="text-[9px] text-gray-400 mt-1 block">{{ __('Nhập link tiếp thị liên kết Shopee của sếp. Khi người dùng click copy, hệ thống sẽ chuyển hướng đến link này. Nếu bỏ trống, hệ thống sẽ sử dụng link riêng của voucher đó.') }}</span>
                </div>

                <!-- UTM Source cho mã giảm giá -->
                <div>
                    <label for="coupon_utm_source" class="block text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider mb-1">{{ __('UTM Source cho mã giảm giá') }}</label>
                    <input type="text"
                        name="coupon_utm_source"
                        id="coupon_utm_source"
                        value="{{ $settings['coupon_utm_source'] ?? 'magiamgia' }}"
                        placeholder="{{ __('Ví dụ: magiamgia, coupon, ...') }}"
                        class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-800 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-850 text-gray-700 dark:text-slate-300">
                    <span class="text-[9px] text-gray-400 mt-1 block">{{ __('Nhập utm_source dùng để tracking đơn hàng từ tính năng mã giảm giá. Khi chuyển hướng, hệ thống sẽ tự động đính kèm tham số này.') }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Tác vụ đồng bộ mã giảm giá thủ công -->
    <div class="bg-gray-50/50 dark:bg-slate-900/50 rounded-2xl p-4 sm:p-6 border border-gray-150 dark:border-slate-800 space-y-4 w-full">
        <h3 class="text-xs font-bold text-gray-700 dark:text-slate-300 uppercase tracking-wider pb-2 border-b border-gray-200/65 dark:border-slate-800 flex items-center gap-1.5">
            <i data-lucide="refresh-cw" class="w-4 h-4 text-shopee"></i>
            {{ __('Đồng bộ thủ công & Tình trạng Scheduler') }}
        </h3>

        <div class="flex items-center justify-between flex-wrap gap-4 p-4 bg-white dark:bg-slate-900 rounded-2xl border border-gray-150 dark:border-slate-850 shadow-sm">
            <div class="space-y-1">
                <h4 class="text-xs font-bold text-gray-800 dark:text-slate-200 flex items-center gap-1.5">
                    <i data-lucide="clock" class="w-4 h-4 text-gray-500"></i>
                    {{ __('Trạng thái tự động đồng bộ (mỗi 30 phút)') }}
                </h4>
                <p class="text-[10px] text-gray-400">
                    {{ __('Thời điểm cập nhật cuối cùng:') }} 
                    <span class="font-semibold text-gray-700 dark:text-slate-300">
                        {{ $couponsLastRun ? date('H:i:s d/m/Y', strtotime($couponsLastRun)) : __('Chưa từng chạy') }}
                    </span>
                </p>
            </div>
            <div class="flex items-center gap-3">
                @if($isCouponsCronRunning)
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-green-50 text-green-700 dark:bg-green-950/30 dark:text-green-400 border border-green-200/50 dark:border-green-900/50 rounded-xl text-[10px] font-bold">
                        <span class="w-1.5 h-1.5 rounded-full bg-green-500 animate-pulse"></span>
                        {{ __('Đang hoạt động') }}
                    </span>
                @else
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-amber-50 text-amber-700 dark:bg-amber-950/30 dark:text-amber-400 border border-amber-200/50 dark:border-amber-900/50 rounded-xl text-[10px] font-bold">
                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                        {{ __('Chờ chạy / Quá hạn') }}
                    </span>
                @endif

                <button type="button"
                    @click="runTask('coupons:sync')"
                    :disabled="runningTask !== ''"
                    class="inline-flex items-center gap-1.5 px-4 py-2 bg-shopee hover:bg-shopee-dark disabled:bg-gray-300 disabled:cursor-not-allowed text-white text-xs font-bold rounded-xl transition-all shadow-md cursor-pointer active:scale-95">
                    <template x-if="runningTask === 'coupons:sync'">
                        <span class="w-3.5 h-3.5 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
                    </template>
                    <template x-if="runningTask !== 'coupons:sync'">
                        <i data-lucide="refresh-cw" class="w-3.5 h-3.5"></i>
                    </template>
                    <span>{{ __('Đồng bộ ngay') }}</span>
                </button>
            </div>
        </div>
    </div>
</div>
