{{-- 
    Partial View: Hoa hồng giới thiệu (MLM)
    Vai trò: Quản lý thiết lập bật tắt tính năng MLM 2 tầng (F1 và F2) và tỷ lệ hoa hồng chia sẻ cho tuyến trên khi F0 được hoàn tiền.
--}}
<div x-show="tab === 'mlm'" class="space-y-4" x-transition x-cloak>
    <div class="bg-gray-50/50 dark:bg-slate-900/50 rounded-2xl p-4 sm:p-6 border border-gray-150 dark:border-slate-800 space-y-6 w-full">
        <h3 class="text-xs font-bold text-gray-700 dark:text-slate-300 uppercase tracking-wider pb-2 border-b border-gray-200/65 dark:border-slate-800 flex items-center gap-1.5">
            <i data-lucide="users-2" class="w-4 h-4 text-shopee"></i>
            {{ __('Cấu hình hoa hồng giới thiệu (MLM)') }}
        </h3>

        <div class="p-3 bg-blue-50 border border-blue-100 text-[10px] text-blue-800 rounded-2xl dark:bg-blue-950/30 dark:border-blue-800/50 dark:text-blue-300">
            <h4 class="font-bold flex items-center gap-1"><i data-lucide="info" class="w-4 h-4"></i> Cơ chế tiếp thị 2 tầng (F1 & F2):</h4>
            <p class="mt-1">Khi F0 được duyệt đơn hoàn tiền, người giới thiệu trực tiếp (F1) và người giới thiệu gián tiếp (F2) sẽ nhận được hoa hồng trích từ số tiền cashback thực tế của F0 dựa trên các tỷ lệ cấu hình dưới đây.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div>
                <label for="referral_enabled" class="block text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider mb-1">Tính năng Tiếp thị liên kết (MLM)</label>
                <select name="referral_enabled"
                    id="referral_enabled"
                    class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-800 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-300">
                    <option value="1" {{ ($settings['referral_enabled'] ?? '1') === '1' ? 'selected' : '' }}>{{ __('BẬT (ON)') }}</option>
                    <option value="0" {{ ($settings['referral_enabled'] ?? '1') === '0' ? 'selected' : '' }}>{{ __('TẮT (OFF)') }}</option>
                </select>
                <span class="text-[9px] text-gray-400 mt-1 block">Bật hoặc tắt toàn bộ tính năng phân chia hoa hồng giới thiệu F1 và F2 trên hệ thống.</span>
            </div>

            <div>
                <label for="referral_f2_enabled" class="block text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider mb-1">Tính năng hoa hồng F2</label>
                <select name="referral_f2_enabled"
                    id="referral_f2_enabled"
                    class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-800 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-300">
                    <option value="1" {{ ($settings['referral_f2_enabled'] ?? '1') === '1' ? 'selected' : '' }}>{{ __('BẬT (ON)') }}</option>
                    <option value="0" {{ ($settings['referral_f2_enabled'] ?? '1') === '0' ? 'selected' : '' }}>{{ __('TẮT (OFF)') }}</option>
                </select>
                <span class="text-[9px] text-gray-400 mt-1 block">Bật hoặc tắt hoa hồng MLM đối với tuyến trên gián tiếp (F2). F1 vẫn nhận bình thường nếu MLM BẬT.</span>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div>
                <label for="block_same_ip_referral" class="block text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider mb-1">Chặn trùng IP giới thiệu</label>
                <select name="block_same_ip_referral"
                    id="block_same_ip_referral"
                    class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-800 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-300">
                    <option value="1" {{ ($settings['block_same_ip_referral'] ?? '1') === '1' ? 'selected' : '' }}>{{ __('BẬT (ON)') }}</option>
                    <option value="0" {{ ($settings['block_same_ip_referral'] ?? '1') === '0' ? 'selected' : '' }}>{{ __('TẮT (OFF)') }}</option>
                </select>
                <span class="text-[9px] text-gray-400 mt-1 block">Tự động chặn ghi nhận quan hệ giới thiệu tuyến trên nếu phát hiện IP của tài khoản mới (F0) trùng với IP đăng ký của người giới thiệu (F1).</span>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label for="referral_f1_rate" class="block text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider mb-1">Tỷ lệ hoa hồng cho F1 (%)</label>
                <input type="number"
                    name="referral_f1_rate"
                    id="referral_f1_rate"
                    value="{{ $settings['referral_f1_rate'] ?? '5' }}"
                    min="0"
                    max="100"
                    class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-800 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-300">
                <span class="text-[9px] text-gray-400 mt-1 block">F1 sẽ nhận {{ $settings['referral_f1_rate'] ?? '5' }}% trên số tiền cashback của F0.</span>
            </div>

            <div>
                <label for="referral_f2_rate" class="block text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider mb-1">Tỷ lệ hoa hồng cho F2 (%)</label>
                <input type="number"
                    name="referral_f2_rate"
                    id="referral_f2_rate"
                    value="{{ $settings['referral_f2_rate'] ?? '2' }}"
                    min="0"
                    max="100"
                    class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-800 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-300">
                <span class="text-[9px] text-gray-400 mt-1 block">F2 sẽ nhận {{ $settings['referral_f2_rate'] ?? '2' }}% trên số tiền cashback của F0.</span>
            </div>
        </div>

        <div class="mt-6">
            <label for="referral_policy" class="block text-xs font-bold text-gray-700 dark:text-slate-400 uppercase tracking-wider mb-1">Chính sách tiếp thị liên kết</label>
            <textarea name="referral_policy"
                id="referral_policy"
                class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-800 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-300">{{ $settings['referral_policy'] ?? '' }}</textarea>
            <span class="text-[9px] text-gray-400 mt-1 block">Nội dung chi tiết về chính sách, điều khoản và hướng dẫn tiếp thị liên kết sẽ hiển thị ở trang thành viên.</span>
        </div>
    </div>
</div>
