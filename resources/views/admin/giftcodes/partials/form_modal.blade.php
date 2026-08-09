{{-- Modal thêm / chỉnh sửa mã Giftcode --}}
<div x-show="showFormModal" x-cloak class="fixed inset-0 z-[60] overflow-y-auto" style="display:none">
    <div class="flex min-h-screen items-center justify-center p-4">
        <div x-show="showFormModal" x-transition.opacity @click="showFormModal = false" class="fixed inset-0 bg-black/60 backdrop-blur-sm"></div>

        <div x-show="showFormModal"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 translate-y-4 scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 scale-100"
             class="relative bg-white dark:bg-slate-900 rounded-3xl shadow-2xl border border-gray-150 dark:border-slate-800 w-full max-w-2xl max-h-[90vh] flex flex-col">

            <!-- Header -->
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 dark:border-slate-800 shrink-0">
                <h3 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
                    <i data-lucide="ticket" class="w-5 h-5 text-shopee"></i>
                    <span x-text="formMode === 'edit' ? '{{ __('Chỉnh sửa Giftcode') }}' : '{{ __('Tạo Giftcode mới') }}'"></span>
                </h3>
                <button @click="showFormModal = false" class="p-1.5 text-gray-400 hover:text-gray-600 hover:bg-gray-100 dark:hover:bg-slate-800 rounded-lg transition-all">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <form :action="formAction" method="POST" @submit="submitForm($event)" class="overflow-y-auto px-6 py-5 space-y-5">
                @csrf
                <input type="hidden" name="_method" :value="formMode === 'edit' ? 'PUT' : 'POST'">
                <input type="hidden" name="require_verified_email" :value="form.require_verified_email ? 1 : 0">
                <input type="hidden" name="status" :value="form.status ? 1 : 0">

                <!-- Mã & Tên -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 dark:text-slate-300 mb-1.5">{{ __('Mã Giftcode') }} <span class="text-red-500">*</span></label>
                        <div class="flex gap-2">
                            <input type="text" name="code" x-model="form.code" @input="form.code = form.code.toUpperCase()" placeholder="VD: WELCOME50K" class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-800 rounded-xl text-xs font-mono uppercase focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-300">
                            <button type="button" @click="generateCode()" class="px-3 py-2 text-xs font-semibold text-shopee bg-shopee/10 hover:bg-shopee/20 rounded-xl transition-all shrink-0" title="{{ __('Tạo mã tự động') }}">
                                <i data-lucide="dices" class="w-4 h-4"></i>
                            </button>
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-700 dark:text-slate-300 mb-1.5">{{ __('Tên chiến dịch') }}</label>
                        <input type="text" name="title" x-model="form.title" placeholder="{{ __('VD: Quà chào mừng thành viên mới') }}" class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-800 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-300">
                    </div>
                </div>

                <!-- Mô tả -->
                <div>
                    <label class="block text-xs font-bold text-gray-700 dark:text-slate-300 mb-1.5">{{ __('Mô tả (hiển thị cho người dùng)') }}</label>
                    <textarea name="description" x-model="form.description" rows="2" placeholder="{{ __('Ghi chú điều kiện hoặc thông điệp hiển thị khi đổi mã...') }}" class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-800 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-300"></textarea>
                </div>

                <!-- Phần thưởng -->
                <div class="bg-gray-50/50 dark:bg-slate-900/50 rounded-2xl p-4 border border-gray-150 dark:border-slate-800 space-y-4">
                    <h4 class="text-xs font-bold text-gray-700 dark:text-slate-300 uppercase tracking-wider flex items-center gap-1.5">
                        <i data-lucide="gift" class="w-4 h-4 text-shopee"></i> {{ __('Phần thưởng') }}
                    </h4>
                    <div class="flex gap-2">
                        <label class="flex-1 cursor-pointer">
                            <input type="radio" name="reward_type" value="fixed" x-model="form.reward_type" class="peer sr-only">
                            <div class="text-center px-3 py-2 text-xs font-semibold rounded-xl border border-gray-200 dark:border-slate-700 peer-checked:bg-shopee peer-checked:text-white peer-checked:border-shopee text-gray-600 dark:text-slate-400 transition-all">{{ __('Số tiền cố định') }}</div>
                        </label>
                        <label class="flex-1 cursor-pointer">
                            <input type="radio" name="reward_type" value="random" x-model="form.reward_type" class="peer sr-only">
                            <div class="text-center px-3 py-2 text-xs font-semibold rounded-xl border border-gray-200 dark:border-slate-700 peer-checked:bg-shopee peer-checked:text-white peer-checked:border-shopee text-gray-600 dark:text-slate-400 transition-all">{{ __('Ngẫu nhiên (lì xì)') }}</div>
                        </label>
                    </div>

                    <div x-show="form.reward_type === 'fixed'">
                        <label class="block text-xs font-bold text-gray-700 dark:text-slate-300 mb-1.5">{{ __('Số tiền thưởng (đ)') }}</label>
                        <input type="number" name="reward_amount" x-model="form.reward_amount" min="0" step="1000" placeholder="50000" class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-800 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-300">
                    </div>
                    <div x-show="form.reward_type === 'random'" class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 dark:text-slate-300 mb-1.5">{{ __('Tối thiểu (đ)') }}</label>
                            <input type="number" name="reward_min" x-model="form.reward_min" min="0" step="1000" placeholder="10000" class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-800 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-300">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-700 dark:text-slate-300 mb-1.5">{{ __('Tối đa (đ)') }}</label>
                            <input type="number" name="reward_max" x-model="form.reward_max" min="0" step="1000" placeholder="100000" class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-800 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-300">
                        </div>
                    </div>
                </div>

                <!-- Giới hạn lượt -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 dark:text-slate-300 mb-1.5">{{ __('Tổng lượt sử dụng') }}</label>
                        <input type="number" name="max_uses" x-model="form.max_uses" min="1" placeholder="{{ __('Để trống = không giới hạn') }}" class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-800 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-300">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-700 dark:text-slate-300 mb-1.5">{{ __('Số lượt mỗi người') }} <span class="text-red-500">*</span></label>
                        <input type="number" name="per_user_limit" x-model="form.per_user_limit" min="1" class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-800 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-300">
                    </div>
                </div>

                <!-- Thời gian hiệu lực -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 dark:text-slate-300 mb-1.5">{{ __('Bắt đầu hiệu lực') }}</label>
                        <input type="datetime-local" name="starts_at" x-model="form.starts_at" class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-800 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-300">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-700 dark:text-slate-300 mb-1.5">{{ __('Hết hạn') }}</label>
                        <input type="datetime-local" name="expires_at" x-model="form.expires_at" class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-800 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-300">
                    </div>
                </div>

                <!-- Điều kiện sử dụng -->
                <div class="bg-gray-50/50 dark:bg-slate-900/50 rounded-2xl p-4 border border-gray-150 dark:border-slate-800 space-y-4">
                    <h4 class="text-xs font-bold text-gray-700 dark:text-slate-300 uppercase tracking-wider flex items-center gap-1.5">
                        <i data-lucide="shield-check" class="w-4 h-4 text-shopee"></i> {{ __('Điều kiện sử dụng') }}
                    </h4>
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-gray-700 dark:text-slate-300">{{ __('Bắt buộc đã xác minh email') }}</span>
                        <button type="button" @click="form.require_verified_email = form.require_verified_email ? 0 : 1" :class="form.require_verified_email ? 'bg-green-500' : 'bg-gray-300 dark:bg-slate-700'" class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors">
                            <span :class="form.require_verified_email ? 'translate-x-5' : 'translate-x-0'" class="inline-block h-5 w-5 transform rounded-full bg-white shadow transition-transform"></span>
                        </button>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div>
                            <label class="block text-[11px] font-bold text-gray-600 dark:text-slate-400 mb-1.5">{{ __('Hoàn tiền tối thiểu (đ)') }}</label>
                            <input type="number" name="min_total_cashback" x-model="form.min_total_cashback" min="0" placeholder="0" class="block w-full px-3 py-2 border border-gray-200 dark:border-slate-800 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-300">
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-gray-600 dark:text-slate-400 mb-1.5">{{ __('Tài khoản tối thiểu (ngày)') }}</label>
                            <input type="number" name="min_account_age_days" x-model="form.min_account_age_days" min="0" placeholder="0" class="block w-full px-3 py-2 border border-gray-200 dark:border-slate-800 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-300">
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-gray-600 dark:text-slate-400 mb-1.5">{{ __('Chỉ user mới (≤ ngày)') }}</label>
                            <input type="number" name="new_user_within_days" x-model="form.new_user_within_days" min="1" placeholder="{{ __('Không') }}" class="block w-full px-3 py-2 border border-gray-200 dark:border-slate-800 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-300">
                        </div>
                    </div>
                </div>

                <!-- Trạng thái -->
                <div class="flex items-center justify-between bg-gray-50/50 dark:bg-slate-900/50 rounded-2xl px-4 py-3 border border-gray-150 dark:border-slate-800">
                    <span class="text-xs font-semibold text-gray-700 dark:text-slate-300">{{ __('Kích hoạt mã ngay') }}</span>
                    <button type="button" @click="form.status = form.status ? 0 : 1" :class="form.status ? 'bg-green-500' : 'bg-gray-300 dark:bg-slate-700'" class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors">
                        <span :class="form.status ? 'translate-x-5' : 'translate-x-0'" class="inline-block h-5 w-5 transform rounded-full bg-white shadow transition-transform"></span>
                    </button>
                </div>

                <!-- Footer -->
                <div class="flex items-center justify-end gap-2 pt-2">
                    <button type="button" @click="showFormModal = false" class="px-5 py-2.5 text-xs font-semibold text-gray-600 dark:text-slate-300 bg-gray-100 dark:bg-slate-800 hover:bg-gray-200 dark:hover:bg-slate-700 rounded-xl transition-all">{{ __('Hủy bỏ') }}</button>
                    <button type="submit" class="inline-flex items-center gap-1.5 px-6 py-2.5 text-xs font-semibold text-white bg-shopee hover:bg-shopee-dark rounded-xl transition-all shadow-md active:scale-95">
                        <i data-lucide="save" class="w-4 h-4"></i>
                        <span x-text="formMode === 'edit' ? '{{ __('Lưu thay đổi') }}' : '{{ __('Tạo mã') }}'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
