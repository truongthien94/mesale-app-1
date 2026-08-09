{{-- Modal xác nhận xóa một mã --}}
<div x-show="showDeleteModal" x-cloak class="fixed inset-0 z-[60] overflow-y-auto" style="display:none">
    <div class="flex min-h-screen items-center justify-center p-4">
        <div x-show="showDeleteModal" x-transition.opacity @click="showDeleteModal = false" class="fixed inset-0 bg-black/60 backdrop-blur-sm"></div>
        <div x-show="showDeleteModal"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 translate-y-4 scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 scale-100"
             class="relative bg-white dark:bg-slate-900 rounded-3xl shadow-2xl border border-gray-150 dark:border-slate-800 w-full max-w-md p-6 text-center">
            <div class="w-14 h-14 mx-auto mb-4 rounded-2xl bg-red-50 dark:bg-red-950/30 flex items-center justify-center">
                <i data-lucide="trash-2" class="w-7 h-7 text-red-500"></i>
            </div>
            <h3 class="text-base font-bold text-gray-900 dark:text-white">{{ __('Xóa Giftcode?') }}</h3>
            <p class="text-xs text-gray-500 dark:text-slate-400 mt-2">
                {{ __('Bạn sắp xóa mã') }} <span class="font-mono font-bold text-red-600" x-text="deleteTarget.code"></span>.
                {{ __('Toàn bộ lịch sử đổi mã liên quan cũng sẽ bị xóa. Hành động này không thể hoàn tác.') }}
            </p>
            <form :action="deleteTarget.action" method="POST" class="flex items-center justify-center gap-2 mt-5">
                @csrf
                @method('DELETE')
                <button type="button" @click="showDeleteModal = false" class="px-5 py-2.5 text-xs font-semibold text-gray-600 dark:text-slate-300 bg-gray-100 dark:bg-slate-800 hover:bg-gray-200 dark:hover:bg-slate-700 rounded-xl transition-all">{{ __('Hủy bỏ') }}</button>
                <button type="submit" class="inline-flex items-center gap-1.5 px-6 py-2.5 text-xs font-semibold text-white bg-red-600 hover:bg-red-700 rounded-xl transition-all shadow-md active:scale-95">
                    <i data-lucide="trash-2" class="w-4 h-4"></i> {{ __('Xác nhận xóa') }}
                </button>
            </form>
        </div>
    </div>
</div>

{{-- Modal xác nhận xóa hàng loạt --}}
<div x-show="showBulkModal" x-cloak class="fixed inset-0 z-[60] overflow-y-auto" style="display:none">
    <div class="flex min-h-screen items-center justify-center p-4">
        <div x-show="showBulkModal" x-transition.opacity @click="showBulkModal = false" class="fixed inset-0 bg-black/60 backdrop-blur-sm"></div>
        <div x-show="showBulkModal"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 translate-y-4 scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 scale-100"
             class="relative bg-white dark:bg-slate-900 rounded-3xl shadow-2xl border border-gray-150 dark:border-slate-800 w-full max-w-md p-6">
            <div class="text-center mb-4">
                <div class="w-14 h-14 mx-auto mb-4 rounded-2xl bg-red-50 dark:bg-red-950/30 flex items-center justify-center">
                    <i data-lucide="trash-2" class="w-7 h-7 text-red-500"></i>
                </div>
                <h3 class="text-base font-bold text-gray-900 dark:text-white">{{ __('Xóa hàng loạt') }}</h3>
                <p class="text-xs text-gray-500 dark:text-slate-400 mt-2">
                    {{ __('Bạn sắp xóa') }} <span class="font-bold text-red-600" x-text="selected.length"></span> {{ __('mã được chọn. Nhập') }} <span class="font-bold text-red-600">XÓA HÀNG LOẠT</span> {{ __('để xác nhận.') }}
                </p>
            </div>
            <input type="text" x-model="bulkConfirmText" placeholder="XÓA HÀNG LOẠT" class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-800 rounded-xl text-xs text-center font-bold focus:outline-none focus:ring-2 focus:ring-red-500/20 focus:border-red-500 bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-300">
            <div class="flex items-center justify-end gap-2 mt-5">
                <button type="button" @click="showBulkModal = false" class="px-5 py-2.5 text-xs font-semibold text-gray-600 dark:text-slate-300 bg-gray-100 dark:bg-slate-800 hover:bg-gray-200 dark:hover:bg-slate-700 rounded-xl transition-all">{{ __('Hủy bỏ') }}</button>
                <button type="button" @click="submitBulkDelete()" class="inline-flex items-center gap-1.5 px-6 py-2.5 text-xs font-semibold text-white bg-red-600 hover:bg-red-700 rounded-xl transition-all shadow-md active:scale-95">
                    <i data-lucide="trash-2" class="w-4 h-4"></i> {{ __('Xác nhận xóa') }}
                </button>
            </div>
        </div>
    </div>
</div>
