<!-- Global Lucide Icon Picker Modal -->
<div x-data="globalIconPicker()" 
     @open-icon-picker.window="openPicker($event.detail)"
     x-show="show"
     x-cloak
     class="fixed inset-0 z-50 overflow-y-auto" 
     aria-labelledby="modal-title" 
     role="dialog" 
     aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <!-- Background overlay -->
        <div x-show="show" 
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 bg-gray-500/70 dark:bg-slate-950/85 backdrop-blur-sm transition-opacity" 
             @click="show = false"></div>

        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

        <!-- Modal Panel -->
        <div x-show="show" 
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             class="inline-block align-bottom bg-white dark:bg-slate-900 rounded-3xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full border border-gray-100 dark:border-slate-800/80 p-6 space-y-4">
            
            <div class="flex items-center justify-between border-b border-gray-100 dark:border-slate-850 pb-3">
                <h3 class="text-xs font-bold text-gray-900 dark:text-slate-200 uppercase tracking-wider flex items-center gap-1.5">
                    <i data-lucide="grid" class="w-4 h-4 text-shopee"></i>
                    {{ __('Chọn Icon Lucide') }}
                </h3>
                <button type="button" @click="show = false" class="text-gray-400 dark:text-slate-500 hover:text-gray-500 dark:hover:text-slate-400">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>

            <!-- Lọc tìm kiếm nhanh -->
            <div class="relative" x-show="!isLoadingIcons">
                <input type="text" 
                       x-model="iconSearch" 
                       placeholder="{{ __('Tìm nhanh tên icon...') }}" 
                       class="w-full pl-9 pr-4 py-2 border border-gray-200 dark:border-slate-800 rounded-xl text-xs bg-gray-50 dark:bg-slate-800/30 focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee text-gray-700 dark:text-slate-300">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400 dark:text-slate-500">
                    <i data-lucide="search" class="w-3.5 h-3.5"></i>
                </div>
            </div>

            <!-- Trạng thái Loading AJAX -->
            <div x-show="isLoadingIcons" class="py-12 flex flex-col items-center justify-center gap-3">
                <svg class="animate-spin h-8 w-8 text-shopee" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span class="text-xs font-semibold text-gray-500 dark:text-slate-400">{{ __('Đang tải 1900+ Icon Lucide...') }}</span>
            </div>

            <!-- Danh sách Grid Icon -->
            <div x-show="!isLoadingIcons" 
                 @scroll="if ($el.scrollTop + $el.clientHeight >= $el.scrollHeight - 30) { displayLimit += 50; $nextTick(() => { if (window.lucide) window.lucide.createIcons(); }); }"
                 class="grid grid-cols-4 sm:grid-cols-5 gap-2 max-h-[320px] overflow-y-auto p-1.5 border border-gray-100 dark:border-slate-800/80 rounded-2xl bg-gray-50/40 dark:bg-slate-900/60 scrollbar-thin">
                <template x-for="icon in filteredIcons" :key="icon">
                    <button type="button" 
                            @click="selectIcon(icon)"
                            class="flex flex-col items-center justify-center p-2.5 rounded-xl border border-gray-150 dark:border-slate-800/60 bg-white dark:bg-slate-900 hover:bg-orange-50/70 dark:hover:bg-shopee/10 hover:border-orange-200 dark:hover:border-shopee/30 transition-all text-gray-600 dark:text-slate-400 hover:text-shopee dark:hover:text-shopee-light group gap-1.5">
                        <i :data-lucide="icon" class="w-4 h-4 text-gray-400 dark:text-slate-500 group-hover:text-shopee dark:group-hover:text-shopee-light transition-colors"></i>
                        <span class="text-[8px] font-mono text-gray-500 dark:text-slate-500 group-hover:text-shopee-dark dark:group-hover:text-shopee-light truncate w-full text-center" x-text="icon"></span>
                    </button>
                </template>
            </div>
            
            <div x-show="!isLoadingIcons" class="flex justify-between items-center text-[10px] text-gray-400 dark:text-slate-500 italic">
                <div>
                    {{ __('Đã hiển thị:') }} <span class="font-bold text-shopee" x-text="Math.min(displayLimit, filteredIcons.length)"></span> / <span class="font-bold" x-text="filteredIcons.length"></span> {{ __('icon') }}
                </div>
                <div>
                    {{ __('Cuộn xuống để xem thêm...') }}
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function globalIconPicker() {
        return {
            show: false,
            iconSearch: '',
            availableIcons: [],
            displayLimit: 60,
            isLoadingIcons: false,
            targetInputId: null,
            callbackFn: null,

            openPicker(detail) {
                this.show = true;
                this.iconSearch = '';
                this.displayLimit = 60;
                this.targetInputId = detail.target || null;
                this.callbackFn = detail.callback || null;

                // Load danh sách bằng AJAX/Fetch từ tệp JSON tĩnh local
                if (this.availableIcons.length === 0) {
                    this.isLoadingIcons = true;
                    fetch('/vendor/lucide/lucide-list.json')
                        .then(res => res.json())
                        .then(data => {
                            this.availableIcons = data;
                            this.isLoadingIcons = false;
                            this.$nextTick(() => {
                                if (window.lucide) window.lucide.createIcons();
                            });
                        })
                        .catch(err => {
                            console.error('Lỗi khi tải danh sách icon Lucide:', err);
                            this.isLoadingIcons = false;
                        });
                } else {
                    this.$nextTick(() => {
                        if (window.lucide) window.lucide.createIcons();
                    });
                }
            },

            get filteredIcons() {
                let list = this.availableIcons;
                if (this.iconSearch) {
                    list = this.availableIcons.filter(icon => icon.toLowerCase().includes(this.iconSearch.toLowerCase()));
                }
                return list.slice(0, this.displayLimit);
            },

            selectIcon(iconName) {
                // 1. Gán giá trị vào ô input đích qua ID
                if (this.targetInputId) {
                    const input = document.getElementById(this.targetInputId);
                    if (input) {
                        input.value = iconName;
                        // Kích hoạt các sự kiện input/change để cập nhật x-model của AlpineJS ở trang cha
                        input.dispatchEvent(new Event('input', { bubbles: true }));
                        input.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                }

                // 2. Gọi hàm callback nếu có
                if (typeof this.callbackFn === 'function') {
                    this.callbackFn(iconName);
                }

                this.show = false;
            },

            init() {
                // Watcher theo dõi tìm kiếm để reset phân trang và vẽ lại icon
                this.$watch('iconSearch', () => {
                    this.displayLimit = 60;
                    this.$nextTick(() => {
                        if (window.lucide) window.lucide.createIcons();
                    });
                });
                this.$watch('show', (val) => {
                    if (val) {
                        this.$nextTick(() => {
                            if (window.lucide) window.lucide.createIcons();
                        });
                    }
                });
            }
        }
    }
</script>
