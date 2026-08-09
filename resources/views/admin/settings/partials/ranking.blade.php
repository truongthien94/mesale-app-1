{{-- 
    Partial View: Cấu hình Bảng xếp hạng
    Vai trò: Quản lý thiết lập bật tắt trang bảng xếp hạng công khai, cấu hình hiển thị chi tiết các bảng xếp hạng thành viên: top đơn hàng, top tiền hoàn, top điểm danh và top giới thiệu.
--}}
<div x-show="tab === 'ranking'" class="space-y-6" x-transition x-cloak>
    <div class="bg-gray-50/50 dark:bg-slate-900/50 rounded-2xl p-4 sm:p-6 border border-gray-150 dark:border-slate-800 space-y-6 w-full">
        <h3 class="text-xs font-bold text-gray-700 dark:text-slate-300 uppercase tracking-wider pb-2 border-b border-gray-200/65 dark:border-slate-800 flex items-center gap-1.5">
            <i data-lucide="trophy" class="w-4 h-4 text-shopee"></i>
            {{ __('Cấu hình Bảng xếp hạng') }}
        </h3>

        {{-- 
            Đoạn mã hiển thị liên kết truy cập nhanh trang bảng xếp hạng ngoài Storefront.
            Giúp Quản trị viên xem trực tiếp hoặc sao chép link trang bảng xếp hạng thành viên dễ dàng.
        --}}
        <div class="p-3 bg-blue-50 dark:bg-blue-950/20 border border-blue-150 dark:border-blue-900 rounded-xl flex flex-col sm:flex-row sm:items-center justify-between gap-2 text-xs">
            <div class="flex items-center gap-2 text-blue-700 dark:text-blue-400">
                <i data-lucide="external-link" class="w-4 h-4 shrink-0"></i>
                <span class="font-medium">{{ __('Đường dẫn trang bảng xếp hạng:') }}</span>
                <a href="{{ route('ranking.index') }}" target="_blank" class="font-bold underline hover:text-blue-800 dark:hover:text-blue-300 break-all">{{ route('ranking.index') }}</a>
            </div>
            <a href="{{ route('ranking.index') }}" target="_blank" class="px-3 py-1 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-lg transition-colors flex items-center justify-center gap-1 shrink-0">
                <span>{{ __('Xem trang') }}</span>
                <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
            </a>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Cấu hình tổng quát trang BXH -->
            <div class="space-y-4">
                <h4 class="text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider pb-1 border-b border-gray-100 dark:border-slate-800/80 flex items-center gap-1.5">
                    <i data-lucide="layout-template" class="w-3.5 h-3.5 text-shopee"></i>
                    {{ __('Thiết lập Trang bảng xếp hạng') }}
                </h4>

                <div>
                    <!-- Bật/Tắt trang bảng xếp hạng nói chung -->
                    <label for="ranking_status" class="block text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider mb-1">
                        {{ __('Trạng thái trang Bảng xếp hạng') }}
                    </label>
                    <select name="ranking_status"
                        id="ranking_status"
                        class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-800 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-300">
                        <option value="1" {{ ($settings['ranking_status'] ?? '1') === '1' ? 'selected' : '' }}>{{ __('Bật hoạt động') }}</option>
                        <option value="0" {{ ($settings['ranking_status'] ?? '1') === '0' ? 'selected' : '' }}>{{ __('Tắt hoạt động') }}</option>
                    </select>
                    <span class="text-[9px] text-gray-400 mt-1 block">
                        {{ __('Bật hoặc tắt toàn bộ trang bảng xếp hạng thành viên trên giao diện người dùng.') }}
                    </span>
                </div>

                <div>
                    <!-- Số lượng thành viên hiển thị trên BXH -->
                    <label for="ranking_limit" class="block text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider mb-1">
                        {{ __('Số lượng thành viên hiển thị tối đa') }}
                    </label>
                    <input type="number"
                        name="ranking_limit"
                        id="ranking_limit"
                        min="1"
                        max="100"
                        value="{{ $settings['ranking_limit'] ?? '10' }}"
                        class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-800 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-300">
                    <span class="text-[9px] text-gray-400 mt-1 block">
                        {{ __('Số lượng thành viên tối đa được liệt kê trên mỗi bảng xếp hạng (Mặc định là 10).') }}
                    </span>
                </div>

                {{-- Cấu hình hiển thị / ẩn tên thành viên trên bảng xếp hạng --}}
                <div x-data="{ nameMode: '{{ $settings['ranking_name_mode'] ?? 'mask' }}' }" class="space-y-4 pt-4 border-t border-gray-100 dark:border-slate-800/80">
                    <div>
                        <label for="ranking_name_mode" class="block text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider mb-1">
                            {{ __('Hiển thị tên thành viên') }}
                        </label>
                        <select name="ranking_name_mode"
                            id="ranking_name_mode"
                            x-model="nameMode"
                            class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-800 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-300">
                            <option value="mask" {{ ($settings['ranking_name_mode'] ?? 'mask') === 'mask' ? 'selected' : '' }}>{{ __('Ẩn bớt một phần tên (bảo vệ riêng tư)') }}</option>
                            <option value="full" {{ ($settings['ranking_name_mode'] ?? 'mask') === 'full' ? 'selected' : '' }}>{{ __('Hiển thị đầy đủ tên') }}</option>
                        </select>
                        <span class="text-[9px] text-gray-400 mt-1 block">
                            {{ __('Chọn cách hiển thị tên thành viên trên bảng xếp hạng công khai.') }}
                        </span>
                    </div>

                    {{-- Các tùy chọn cấu hình kiểu ẩn, chỉ hiện khi chọn chế độ "Ẩn bớt một phần tên" --}}
                    <div x-show="nameMode === 'mask'" x-transition x-cloak class="space-y-4 p-4 bg-white dark:bg-slate-900 rounded-2xl border border-gray-100 dark:border-slate-800/50">
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label for="ranking_name_visible_start" class="block text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider mb-1">
                                    {{ __('Số ký tự hiện ở đầu') }}
                                </label>
                                <input type="number"
                                    name="ranking_name_visible_start"
                                    id="ranking_name_visible_start"
                                    min="0"
                                    max="20"
                                    value="{{ $settings['ranking_name_visible_start'] ?? '2' }}"
                                    class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-800 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-300">
                            </div>
                            <div>
                                <label for="ranking_name_visible_end" class="block text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider mb-1">
                                    {{ __('Số ký tự hiện ở cuối') }}
                                </label>
                                <input type="number"
                                    name="ranking_name_visible_end"
                                    id="ranking_name_visible_end"
                                    min="0"
                                    max="20"
                                    value="{{ $settings['ranking_name_visible_end'] ?? '2' }}"
                                    class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-800 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-300">
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label for="ranking_name_mask_char" class="block text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider mb-1">
                                    {{ __('Ký tự che') }}
                                </label>
                                <input type="text"
                                    name="ranking_name_mask_char"
                                    id="ranking_name_mask_char"
                                    maxlength="1"
                                    value="{{ $settings['ranking_name_mask_char'] ?? '*' }}"
                                    class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-800 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-300">
                            </div>
                            <div>
                                <label for="ranking_name_mask_length" class="block text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider mb-1">
                                    {{ __('Số ký tự che ở giữa') }}
                                </label>
                                <input type="number"
                                    name="ranking_name_mask_length"
                                    id="ranking_name_mask_length"
                                    min="1"
                                    max="20"
                                    value="{{ $settings['ranking_name_mask_length'] ?? '3' }}"
                                    class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-800 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-300">
                            </div>
                        </div>
                        <span class="text-[9px] text-gray-400 block">
                            {{ __('Ví dụ: hiện 2 ký tự đầu + 2 ký tự cuối, che 3 ký tự giữa → "Ng***ận". Nếu tên quá ngắn sẽ tự che để đảm bảo riêng tư.') }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Cấu hình chi tiết từng BXH cụ thể -->
            <div class="space-y-4">
                <h4 class="text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider pb-1 border-b border-gray-100 dark:border-slate-800/80 flex items-center gap-1.5">
                    <i data-lucide="sliders" class="w-3.5 h-3.5 text-shopee"></i>
                    {{ __('Bật / Tắt từng Bảng xếp hạng cụ thể') }}
                </h4>

                <!-- Bảng cấu hình chi tiết -->
                <div class="bg-white dark:bg-slate-900 rounded-2xl border border-gray-100 dark:border-slate-800/50 overflow-hidden shadow-sm">
                    <table class="w-full text-left border-collapse text-xs whitespace-nowrap">
                        <thead>
                            <tr class="bg-gray-50/70 border-b border-gray-100 dark:bg-slate-800/40 dark:border-slate-800 text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                <th class="px-5 py-3">{{ __('Bảng xếp hạng') }}</th>
                                <th class="px-5 py-3 text-center w-24">{{ __('Trạng thái') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-slate-800/50">
                            <!-- Top đơn hàng -->
                            <tr class="hover:bg-gray-50/50 dark:hover:bg-slate-800/20 transition-all">
                                <td class="px-5 py-3.5 font-bold text-gray-900 dark:text-white">
                                    <span class="inline-flex items-center gap-1.5">
                                        <i data-lucide="shopping-cart" class="w-3.5 h-3.5 text-gray-450 dark:text-slate-450"></i>
                                        {{ __('Bảng xếp hạng Top đơn hàng') }}
                                    </span>
                                    <span class="block text-[9px] text-gray-400 font-normal mt-0.5">
                                        {{ __('Top thành viên có nhiều đơn hàng hoàn tiền thành công nhất.') }}
                                    </span>
                                </td>
                                <td class="px-5 py-3.5 text-center">
                                    <input type="hidden" name="ranking_top_orders_status" value="0">
                                    <label class="relative inline-flex items-center cursor-pointer select-none">
                                        <input type="checkbox" name="ranking_top_orders_status" value="1" {{ ($settings['ranking_top_orders_status'] ?? '1') === '1' ? 'checked' : '' }} class="sr-only peer">
                                        <div class="w-9 h-5 bg-gray-200 peer-focus:outline-none rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all dark:border-slate-600 peer-checked:bg-shopee"></div>
                                    </label>
                                </td>
                            </tr>

                            <!-- Top tiền hoàn -->
                            <tr class="hover:bg-gray-50/50 dark:hover:bg-slate-800/20 transition-all">
                                <td class="px-5 py-3.5 font-bold text-gray-900 dark:text-white">
                                    <span class="inline-flex items-center gap-1.5">
                                        <i data-lucide="badge-dollar-sign" class="w-3.5 h-3.5 text-gray-450 dark:text-slate-450"></i>
                                        {{ __('Bảng xếp hạng Top tiền hoàn') }}
                                    </span>
                                    <span class="block text-[9px] text-gray-400 font-normal mt-0.5">
                                        {{ __('Top thành viên nhận được số tiền hoàn lớn nhất hệ thống.') }}
                                    </span>
                                </td>
                                <td class="px-5 py-3.5 text-center">
                                    <input type="hidden" name="ranking_top_cashback_status" value="0">
                                    <label class="relative inline-flex items-center cursor-pointer select-none">
                                        <input type="checkbox" name="ranking_top_cashback_status" value="1" {{ ($settings['ranking_top_cashback_status'] ?? '1') === '1' ? 'checked' : '' }} class="sr-only peer">
                                        <div class="w-9 h-5 bg-gray-200 peer-focus:outline-none rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all dark:border-slate-600 peer-checked:bg-shopee"></div>
                                    </label>
                                </td>
                            </tr>

                            <!-- Top điểm danh -->
                            <tr class="hover:bg-gray-50/50 dark:hover:bg-slate-800/20 transition-all">
                                <td class="px-5 py-3.5 font-bold text-gray-900 dark:text-white">
                                    <span class="inline-flex items-center gap-1.5">
                                        <i data-lucide="calendar-check" class="w-3.5 h-3.5 text-gray-450 dark:text-slate-450"></i>
                                        {{ __('Bảng xếp hạng Top điểm danh') }}
                                    </span>
                                    <span class="block text-[9px] text-gray-400 font-normal mt-0.5">
                                        {{ __('Top thành viên có chuỗi ngày điểm danh chuyên cần liên tục dài nhất.') }}
                                    </span>
                                </td>
                                <td class="px-5 py-3.5 text-center">
                                    <input type="hidden" name="ranking_top_checkin_status" value="0">
                                    <label class="relative inline-flex items-center cursor-pointer select-none">
                                        <input type="checkbox" name="ranking_top_checkin_status" value="1" {{ ($settings['ranking_top_checkin_status'] ?? '1') === '1' ? 'checked' : '' }} class="sr-only peer">
                                        <div class="w-9 h-5 bg-gray-200 peer-focus:outline-none rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all dark:border-slate-600 peer-checked:bg-shopee"></div>
                                    </label>
                                </td>
                            </tr>

                            <!-- Top giới thiệu -->
                            <tr class="hover:bg-gray-50/50 dark:hover:bg-slate-800/20 transition-all">
                                <td class="px-5 py-3.5 font-bold text-gray-900 dark:text-white">
                                    <span class="inline-flex items-center gap-1.5">
                                        <i data-lucide="user-plus" class="w-3.5 h-3.5 text-gray-450 dark:text-slate-450"></i>
                                        {{ __('Bảng xếp hạng Top giới thiệu') }}
                                    </span>
                                    <span class="block text-[9px] text-gray-400 font-normal mt-0.5">
                                        {{ __('Top tuyển trên giới thiệu được nhiều thành viên cấp dưới F1 nhất.') }}
                                    </span>
                                </td>
                                <td class="px-5 py-3.5 text-center">
                                    <input type="hidden" name="ranking_top_referral_status" value="0">
                                    <label class="relative inline-flex items-center cursor-pointer select-none">
                                        <input type="checkbox" name="ranking_top_referral_status" value="1" {{ ($settings['ranking_top_referral_status'] ?? '1') === '1' ? 'checked' : '' }} class="sr-only peer">
                                        <div class="w-9 h-5 bg-gray-200 peer-focus:outline-none rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all dark:border-slate-600 peer-checked:bg-shopee"></div>
                                    </label>
                                </td>
                            </tr>

                            <!-- Top số dư khả dụng -->
                            <tr class="hover:bg-gray-50/50 dark:hover:bg-slate-800/20 transition-all">
                                <td class="px-5 py-3.5 font-bold text-gray-900 dark:text-white">
                                    <span class="inline-flex items-center gap-1.5">
                                        <i data-lucide="piggy-bank" class="w-3.5 h-3.5 text-gray-450 dark:text-slate-450"></i>
                                        {{ __('Bảng xếp hạng Top số dư khả dụng') }}
                                    </span>
                                    <span class="block text-[9px] text-gray-400 font-normal mt-0.5">
                                        {{ __('Top thành viên có số dư khả dụng hiện tại lớn nhất hệ thống.') }}
                                    </span>
                                </td>
                                <td class="px-5 py-3.5 text-center">
                                    <input type="hidden" name="ranking_top_balance_status" value="0">
                                    <label class="relative inline-flex items-center cursor-pointer select-none">
                                        <input type="checkbox" name="ranking_top_balance_status" value="1" {{ ($settings['ranking_top_balance_status'] ?? '1') === '1' ? 'checked' : '' }} class="sr-only peer">
                                        <div class="w-9 h-5 bg-gray-200 peer-focus:outline-none rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all dark:border-slate-600 peer-checked:bg-shopee"></div>
                                    </label>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
