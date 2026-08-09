{{-- Modal cấu hình chung của chức năng Giftcode --}}
<div x-show="showConfigModal" x-cloak class="fixed inset-0 z-[60] overflow-y-auto" style="display:none"
     x-data="{ enabled: {{ \App\Models\Setting::getVal('gift_code_enabled', '1') === '1' ? 1 : 0 }} }">
    <div class="flex min-h-screen items-center justify-center p-4">
        <div x-show="showConfigModal" x-transition.opacity @click="showConfigModal = false" class="fixed inset-0 bg-black/60 backdrop-blur-sm"></div>

        <div x-show="showConfigModal"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 translate-y-4 scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 scale-100"
             class="relative bg-white dark:bg-slate-900 rounded-3xl shadow-2xl border border-gray-150 dark:border-slate-800 w-full max-w-lg">

            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 dark:border-slate-800">
                <h3 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
                    <i data-lucide="settings" class="w-5 h-5 text-shopee"></i> {{ __('Cấu hình Giftcode') }}
                </h3>
                <button @click="showConfigModal = false" class="p-1.5 text-gray-400 hover:text-gray-600 hover:bg-gray-100 dark:hover:bg-slate-800 rounded-lg transition-all">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <form action="{{ route('admin.giftcodes.config.update') }}" method="POST" class="px-6 py-5 space-y-5">
                @csrf
                <input type="hidden" name="gift_code_enabled" :value="enabled ? 1 : 0">

                <div class="flex items-center justify-between bg-gray-50/50 dark:bg-slate-900/50 rounded-2xl px-4 py-3 border border-gray-150 dark:border-slate-800">
                    <div>
                        <span class="text-xs font-bold text-gray-700 dark:text-slate-300 block">{{ __('Bật chức năng nhập Giftcode') }}</span>
                        <span class="text-[11px] text-gray-400">{{ __('Khi tắt, người dùng sẽ không thấy trang nhập mã.') }}</span>
                    </div>
                    <button type="button" @click="enabled = enabled ? 0 : 1" :class="enabled ? 'bg-green-500' : 'bg-gray-300 dark:bg-slate-700'" class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors shrink-0">
                        <span :class="enabled ? 'translate-x-5' : 'translate-x-0'" class="inline-block h-5 w-5 transform rounded-full bg-white shadow transition-transform"></span>
                    </button>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 dark:text-slate-300 mb-1.5">{{ __('Mô tả hiển thị ở trang khách') }}</label>
                    <textarea name="gift_code_intro" rows="3" class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-800 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-300">{{ \App\Models\Setting::getVal('gift_code_intro', '') }}</textarea>
                </div>

                <div class="flex items-center justify-end gap-2 pt-1">
                    <button type="button" @click="showConfigModal = false" class="px-5 py-2.5 text-xs font-semibold text-gray-600 dark:text-slate-300 bg-gray-100 dark:bg-slate-800 hover:bg-gray-200 dark:hover:bg-slate-700 rounded-xl transition-all">{{ __('Hủy') }}</button>
                    <button type="submit" class="inline-flex items-center gap-1.5 px-6 py-2.5 text-xs font-semibold text-white bg-shopee hover:bg-shopee-dark rounded-xl transition-all shadow-md active:scale-95">
                        <i data-lucide="save" class="w-4 h-4"></i> {{ __('Lưu cấu hình') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
