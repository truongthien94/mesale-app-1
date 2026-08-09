@extends('layouts.admin')

@section('title', __('Quản Lý Bản Dịch') . ' - ' . $siteName)

@section('content')
<div class="space-y-6" x-data="translationManager()">
    <!-- Tiêu đề và nút hành động nhanh -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 bg-white dark:bg-slate-900 p-6 rounded-3xl shadow-sm border border-gray-200/80 dark:border-slate-800">
        <div>
            <div class="flex items-center gap-2">
                <a href="{{ route('admin.languages.index') }}" class="inline-flex p-1.5 hover:bg-gray-100 dark:hover:bg-slate-800 rounded-lg text-gray-500 transition-all">
                    <i data-lucide="arrow-left" class="w-4 h-4"></i>
                </a>
                <h1 class="text-xl font-bold text-gray-900 dark:text-white">{{ __('Quản lý bản dịch') }}: <span class="text-shopee">{{ $language->name }} ({{ strtoupper($language->code) }})</span></h1>
            </div>
            <p class="text-xs text-gray-500 dark:text-slate-400 mt-1 pl-8">{{ __('Chỉnh sửa trực tiếp bản dịch của hệ thống. Dữ liệu tự động cập nhật và ghi ra file ngôn ngữ.') }}</p>
        </div>

        <div class="flex items-center gap-2.5 self-end md:self-auto flex-wrap">
            <!-- Nút Lọc chưa dịch -->
            <a href="{{ route('admin.languages.translations', [$language->id, 'filter' => request('filter') === 'untranslated' ? '' : 'untranslated', 'search' => request('search'), 'per_page' => request('per_page')]) }}" 
               class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-semibold rounded-xl border transition-all duration-200 {{ request('filter') === 'untranslated' ? 'bg-amber-50 dark:bg-amber-950/20 border-amber-200 dark:border-amber-900/50 text-amber-700 dark:text-amber-400 hover:bg-amber-100/50 dark:hover:bg-amber-900/30' : 'bg-white dark:bg-slate-900 border-gray-200 dark:border-slate-800 text-gray-700 dark:text-slate-300 hover:bg-gray-50 dark:hover:bg-slate-800' }}">
                <i data-lucide="filter-x" class="w-4 h-4"></i>
                <span>{{ request('filter') === 'untranslated' ? __('Tất cả bản dịch') : __('Chưa dịch') }}</span>
            </a>

            <!-- Nút Quét / Tạo lại bản dịch -->
            <form action="{{ route('admin.languages.translations.rebuild', $language->id) }}" method="POST" onsubmit="return confirm('Hệ thống sẽ quét toàn bộ mã nguồn (.php, .blade) để tìm các chuỗi dịch mới và cập nhật vào bảng bản dịch. Xác nhận?')">
                @csrf
                <button type="submit" class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-semibold text-white bg-purple-600 hover:bg-purple-700 rounded-xl transition-all shadow-sm">
                    <i data-lucide="scan" class="w-4 h-4"></i>
                    <span>{{ __('Tạo lại bản dịch') }}</span>
                </button>
            </form>

            <!-- Nút Đồng bộ bản dịch -->
            <form action="{{ route('admin.languages.translations.sync_file', $language->id) }}" method="POST">
                @csrf
                <button type="submit" class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-semibold text-white bg-emerald-600 hover:bg-emerald-700 rounded-xl transition-all shadow-sm">
                    <i data-lucide="refresh-cw" class="w-4 h-4"></i>
                    <span>{{ __('Đồng bộ bản dịch') }}</span>
                </button>
            </form>
        </div>
    </div>

    <!-- Bảng dữ liệu bản dịch -->
    <div class="bg-white dark:bg-slate-900 rounded-3xl shadow-sm border border-gray-200/80 dark:border-slate-800 overflow-hidden">
        <div class="p-6 border-b border-gray-100 dark:border-slate-800 flex flex-col sm:flex-row items-center justify-between gap-4">
            <h3 class="font-bold text-gray-900 dark:text-white text-sm flex items-center gap-2">
                <span>{{ __('Danh sách chuỗi ngôn ngữ') }}</span>
                <span class="px-2 py-0.5 rounded bg-gray-100 dark:bg-slate-800 text-gray-500 dark:text-slate-400 text-[10px] font-bold font-mono">
                    {{ $translations->total() }} {{ __('chuỗi') }}
                </span>
            </h3>

            <div class="flex items-center gap-3 w-full sm:w-auto">
                <!-- Dropdown Chọn số lượng hiển thị -->
                <div class="flex items-center gap-1.5 shrink-0">
                    <span class="text-[10px] font-bold text-gray-400 dark:text-slate-500 uppercase tracking-wider">{{ __('Hiển thị') }}:</span>
                    <select onchange="window.location.href = this.value" 
                            class="py-1.5 pl-2.5 pr-8 border border-gray-200 dark:border-slate-800 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-gray-50/50 dark:bg-slate-900 text-gray-800 dark:text-slate-100 cursor-pointer">
                        @foreach([10, 25, 50, 100, 200, 500, 1000] as $size)
                            <option value="{{ route('admin.languages.translations', [$language->id, 'per_page' => $size, 'filter' => request('filter'), 'search' => request('search')]) }}" 
                                    {{ request('per_page', 10) == $size ? 'selected' : '' }}>
                                {{ $size }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Form Tìm kiếm -->
                <form action="{{ route('admin.languages.translations', $language->id) }}" method="GET" class="w-full sm:w-64">
                    <input type="hidden" name="filter" value="{{ request('filter') }}">
                    <input type="hidden" name="per_page" value="{{ request('per_page', 10) }}">
                    <div class="relative">
                        <input type="text" 
                               name="search" 
                               value="{{ request('search') }}"
                               placeholder="{{ __('Tìm kiếm từ khóa...') }}" 
                               class="w-full pl-9 pr-4 py-2 border border-gray-200 dark:border-slate-800 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-gray-50/50 dark:bg-slate-900 text-gray-800 dark:text-slate-100">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i data-lucide="search" class="w-4 h-4 text-gray-400 dark:text-slate-500"></i>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse table-fixed">
                <thead>
                    <tr class="border-b border-gray-100 dark:border-slate-800 text-gray-400 dark:text-slate-450 text-[10px] font-bold uppercase tracking-wider bg-gray-50/30 dark:bg-slate-800/30">
                        <th class="py-3 px-4 text-center w-[50px]">
                            <input type="checkbox" @change="toggleSelectAll($event.target.checked)" class="w-5 h-5 rounded border-gray-300 dark:border-slate-700 dark:bg-slate-900 text-shopee focus:ring-shopee cursor-pointer transition-all">
                        </th>
                        <th class="py-3 px-6 w-5/12">{{ __('Mặc định (Tiếng Việt)') }}</th>
                        <th class="py-3 px-6 w-5/12">{{ $language->name }} ({{ strtoupper($language->code) }})</th>
                        <th class="py-3 px-6 text-right w-2/12">{{ __('Hành động') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50 dark:divide-slate-800 text-xs">
                    @forelse($translations as $trans)
                        <tr class="hover:bg-gray-50/30 dark:hover:bg-slate-800/30 transition-colors" id="row-{{ md5($trans->key) }}">
                            <!-- Checkbox chọn -->
                            <td class="py-4 px-4 text-center align-top">
                                <input type="checkbox" name="selected_keys[]" value="{{ $trans->key }}" x-model="selectedKeys" class="w-5 h-5 rounded border-gray-300 dark:border-slate-700 dark:bg-slate-900 text-shopee focus:ring-shopee cursor-pointer transition-all">
                            </td>
                            <!-- Chuỗi mặc định -->
                            <td class="py-4 px-6 align-top">
                                <div class="bg-gray-50/80 dark:bg-slate-950/40 p-3 rounded-xl border border-gray-100 dark:border-slate-800 text-gray-700 dark:text-slate-300 whitespace-pre-wrap select-all font-medium break-words leading-relaxed min-h-[48px]">
                                    {{ $trans->key }}
                                </div>
                            </td>
                            <!-- Bản dịch -->
                            <td class="py-4 px-6 align-top">
                                <textarea 
                                    id="textarea-{{ md5($trans->key) }}"
                                    @blur="updateTranslation('{{ addslashes($trans->key) }}', $event.target.value)"
                                    placeholder="{{ __('Nhấp để nhập bản dịch...') }}"
                                    class="w-full p-3 border border-gray-200 dark:border-slate-800 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-950/50 text-gray-800 dark:text-slate-100 focus:shadow-sm resize-y leading-relaxed min-h-[48px] placeholder:text-gray-400/80 dark:placeholder:text-slate-600"
                                    rows="2"
                                >{{ $trans->value }}</textarea>
                            </td>
                            <!-- Hành động -->
                            <td class="py-4 px-6 align-top text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    <!-- Nút dịch tự động -->
                                    <button type="button" 
                                            @click="autoTranslate('{{ addslashes($trans->key) }}', $event.target)"
                                            class="inline-flex items-center gap-1 px-2.5 py-1.5 bg-blue-50 dark:bg-blue-950/30 hover:bg-blue-100 dark:hover:bg-blue-900/30 text-blue-600 dark:text-blue-400 rounded-lg transition-all font-semibold"
                                            title="{{ __('Dịch tự động') }}">
                                        <i data-lucide="languages" class="w-3.5 h-3.5 shrink-0"></i>
                                        <span>{{ __('Dịch tự động') }}</span>
                                    </button>

                                    <!-- Nút xóa -->
                                    <button type="button" 
                                            @click="deleteTranslation('{{ addslashes($trans->key) }}')"
                                            class="p-1.5 bg-red-50 dark:bg-red-950/30 hover:bg-red-100 dark:hover:bg-red-900/30 text-red-600 dark:text-red-400 rounded-lg transition-all"
                                            title="{{ __('Xoá chuỗi dịch này') }}">
                                        <i data-lucide="trash-2" class="w-3.5 h-3.5 shrink-0"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="py-12 text-center text-gray-400 font-medium">
                                <div class="flex flex-col items-center justify-center gap-2">
                                    <i data-lucide="folder-open" class="w-8 h-8 text-gray-300"></i>
                                    <span>{{ __('Không tìm thấy bản dịch nào phù hợp.') }}</span>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Phân trang -->
        <div class="px-6 py-4 border-t border-gray-50 dark:border-slate-800 bg-gray-50/20 dark:bg-slate-800/10">
            {{ $translations->links() }}
        </div>
    </div>

    <!-- Thanh tác vụ hàng loạt -->
    <div x-show="selectedKeys.length > 0"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 translate-y-4"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 translate-y-4"
         class="fixed bottom-6 left-1/2 -translate-x-1/2 z-40 bg-gray-900 text-white py-3 px-6 rounded-2xl shadow-xl flex items-center gap-4 border border-gray-800"
         style="display: none;">
        <span class="text-xs font-medium">
            {{ __('Đã chọn') }} <span class="font-bold text-shopee" x-text="selectedKeys.length"></span> {{ __('mục') }}
        </span>
        <div class="h-4 w-px bg-gray-800"></div>
        <div class="flex items-center gap-2">
            <button type="button" 
                    @click="bulkAutoTranslate()"
                    class="inline-flex items-center gap-1.5 px-3.5 py-1.5 bg-shopee hover:bg-shopee/90 text-white rounded-xl text-xs font-bold transition-all">
                <i data-lucide="languages" class="w-3.5 h-3.5"></i>
                <span>{{ __('Dịch hàng loạt') }}</span>
            </button>
            <button type="button" 
                    @click="selectedKeys = []"
                    class="px-3 py-1.5 text-xs text-gray-400 hover:text-white transition-colors">
                {{ __('Bỏ chọn') }}
            </button>
        </div>
    </div>

    <!-- Modal Tiến trình Dịch Hàng Loạt -->
    <template x-teleport="body">
        <div x-show="isTranslating" 
             class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-sm"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             style="display: none;">
            <div class="bg-white dark:bg-slate-900 rounded-3xl shadow-xl border border-gray-100 dark:border-slate-850 max-w-md w-full p-6 space-y-6">
                <div class="text-center space-y-2">
                    <div class="inline-flex p-3 bg-purple-50 dark:bg-purple-950/20 rounded-2xl text-purple-600 dark:text-purple-400 animate-bounce">
                        <i data-lucide="languages" class="w-6 h-6"></i>
                    </div>
                    <h3 class="text-base font-bold text-gray-900 dark:text-white">{{ __('Đang dịch tự động...') }}</h3>
                    <p class="text-xs text-gray-500 dark:text-slate-400">{{ __('Hệ thống đang tiến hành dịch tự động hàng loạt qua Google Translate.') }}</p>
                </div>
    
                <!-- Thanh tiến trình -->
                <div class="space-y-2">
                    <div class="flex justify-between items-center text-xs font-semibold">
                        <span class="text-purple-600 dark:text-purple-400" x-text="progressCurrent + ' / ' + progressTotal + ' ' + '{{ __('chuỗi') }}'"></span>
                        <span class="text-gray-900 dark:text-slate-205" x-text="progressPercent + '%'"></span>
                    </div>
                    <div class="w-full bg-gray-100 dark:bg-slate-800 rounded-full h-3 overflow-hidden">
                        <div class="bg-purple-600 h-full transition-all duration-300 rounded-full" :style="'width: ' + progressPercent + '%'"></div>
                    </div>
                </div>
    
                <!-- Log hiện tại -->
                <div class="bg-gray-50 dark:bg-slate-950/50 rounded-xl p-3 border border-gray-100/80 dark:border-slate-800/60">
                    <div class="text-[10px] text-gray-400 dark:text-slate-500 font-bold uppercase tracking-wider mb-1">{{ __('Đang xử lý') }}</div>
                    <p class="text-xs text-gray-700 dark:text-slate-300 font-medium truncate" x-text="currentTranslatingText || 'Chờ bắt đầu...'"></p>
                </div>
    
                <div class="flex items-center justify-end gap-2 pt-2 border-t border-gray-50 dark:border-slate-800">
                    <button type="button" 
                            @click="cancelBulkTranslation()"
                            class="px-4 py-2 text-xs font-semibold text-gray-600 hover:text-gray-800 dark:text-slate-400 dark:hover:text-slate-200 transition-colors"
                            x-show="progressCurrent < progressTotal">
                        {{ __('Hủy bỏ') }}
                    </button>
                    <button type="button" 
                            @click="isTranslating = false"
                            class="px-5 py-2 text-xs font-bold text-white bg-purple-600 hover:bg-purple-700 rounded-xl transition-all shadow-sm"
                            x-show="progressCurrent === progressTotal">
                        {{ __('Hoàn thành') }}
                    </button>
                </div>
            </div>
        </div>
    </template>
</div>
@endsection

@section('scripts')
<script>
    function translationManager() {
        return {
            selectedKeys: [],
            isTranslating: false,
            progressTotal: 0,
            progressCurrent: 0,
            progressPercent: 0,
            currentTranslatingText: '',
            cancelRequested: false,

            toggleSelectAll(checked) {
                if (checked) {
                    const checkboxes = document.querySelectorAll('input[name="selected_keys[]"]');
                    const keys = [];
                    checkboxes.forEach(cb => {
                        keys.push(cb.value);
                    });
                    this.selectedKeys = keys;
                } else {
                    this.selectedKeys = [];
                }
            },

            async bulkAutoTranslate() {
                if (this.selectedKeys.length === 0) return;
                this.isTranslating = true;
                this.progressTotal = this.selectedKeys.length;
                this.progressCurrent = 0;
                this.progressPercent = 0;
                this.cancelRequested = false;

                if (typeof lucide !== 'undefined') {
                    setTimeout(() => lucide.createIcons(), 50);
                }

                for (const key of this.selectedKeys) {
                    if (this.cancelRequested) {
                        break;
                    }
                    this.currentTranslatingText = key;
                    try {
                        const response = await axios.post('{{ route('admin.languages.translations.auto_translate', $language->id) }}', {
                            text: key
                        });
                        if (response.data.success) {
                            const md5Key = md5(key);
                            const textarea = document.getElementById('textarea-' + md5Key);
                            if (textarea) {
                                textarea.value = response.data.translated_text;
                            }
                        }
                    } catch (error) {
                        console.error('Lỗi khi dịch: ' + key, error);
                    }
                    this.progressCurrent++;
                    this.progressPercent = Math.round((this.progressCurrent / this.progressTotal) * 100);
                }

                if (this.cancelRequested) {
                    window.dispatchEvent(new CustomEvent('toast', {
                        detail: { text: 'Đã hủy dịch hàng loạt.', type: 'warning' }
                    }));
                } else {
                    window.dispatchEvent(new CustomEvent('toast', {
                        detail: { text: 'Dịch hàng loạt hoàn tất!', type: 'success' }
                    }));
                }
                this.selectedKeys = [];
            },

            cancelBulkTranslation() {
                this.cancelRequested = true;
                this.isTranslating = false;
                this.selectedKeys = [];
            },

            updateTranslation(key, value) {
                axios.post('{{ route('admin.languages.translations.update', $language->id) }}', {
                    key: key,
                    value: value
                })
                .then(response => {
                    if (response.data.success) {
                        window.dispatchEvent(new CustomEvent('toast', {
                            detail: { text: 'Đã tự động lưu bản dịch thành công!', type: 'success' }
                        }));
                    }
                })
                .catch(error => {
                    console.error(error);
                    window.dispatchEvent(new CustomEvent('toast', {
                        detail: { text: 'Có lỗi xảy ra khi lưu bản dịch.', type: 'error' }
                    }));
                });
            },
            autoTranslate(key, targetElement) {
                const button = targetElement.closest('button');
                if (!button) return;
                const originalText = button.innerHTML;
                button.disabled = true;
                button.innerHTML = '<svg class="animate-spin h-3.5 w-3.5 text-blue-600 dark:text-blue-400 inline-block mr-1" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>Dịch...';
                
                axios.post('{{ route('admin.languages.translations.auto_translate', $language->id) }}', {
                    text: key
                })
                .then(response => {
                    if (response.data.success) {
                        const trRow = button.closest('tr');
                        const textarea = trRow.querySelector('textarea');
                        textarea.value = response.data.translated_text;

                        window.dispatchEvent(new CustomEvent('toast', {
                            detail: { text: 'Dịch tự động thành công!', type: 'success' }
                        }));
                    }
                })
                .catch(error => {
                    console.error(error);
                    window.dispatchEvent(new CustomEvent('toast', {
                        detail: { text: 'Không thể dịch tự động chuỗi này.', type: 'error' }
                    }));
                })
                .finally(() => {
                    button.disabled = false;
                    button.innerHTML = originalText;
                    if (typeof lucide !== 'undefined') {
                        lucide.createIcons();
                    }
                });
            },
            deleteTranslation(key) {
                if (!confirm('Bạn có chắc chắn muốn xóa chuỗi bản dịch này?')) return;

                axios.post('{{ route('admin.languages.translations.delete', $language->id) }}', {
                    key: key
                })
                .then(response => {
                    if (response.data.success) {
                        const md5Key = md5(key);
                        const row = document.getElementById('row-' + md5Key);
                        if (row) {
                            row.classList.add('transition-all', 'duration-500', 'opacity-0', 'scale-95');
                            setTimeout(() => row.remove(), 500);
                        }

                        window.dispatchEvent(new CustomEvent('toast', {
                            detail: { text: 'Đã xóa chuỗi bản dịch thành công!', type: 'success' }
                        }));
                    }
                })
                .catch(error => {
                    console.error(error);
                    window.dispatchEvent(new CustomEvent('toast', {
                        detail: { text: 'Không thể xóa chuỗi bản dịch này.', type: 'error' }
                    }));
                });
            }
        }
    }

    // Helper MD5 để tạo ID cho row
    function md5(string) {
        function RotateLeft(lValue, iShiftBits) {
            return (lValue<<iShiftBits) | (lValue>>>(32-iShiftBits));
        }
        function AddUnsigned(lX,lY) {
            var lX4,lY4,lX8,lY8,lXResult,lYResult;
            lX8 = (lX & 0x80000000);
            lY8 = (lY & 0x80000000);
            lX4 = (lX & 0x40000000);
            lY4 = (lY & 0x40000000);
            lXResult = (lX & 0x3FFFFFFF) + (lY & 0x3FFFFFFF);
            if (lX4 & lY4) {
                return (lXResult ^ 0x80000000 ^ lX8 ^ lY8);
            }
            if (lX4 | lY4) {
                if (lXResult & 0x40000000) {
                    return (lXResult ^ 0xC0000000 ^ lX8 ^ lY8);
                } else {
                    return (lXResult ^ 0x40000000 ^ lX8 ^ lY8);
                }
            } else {
                return (lXResult ^ lX8 ^ lY8);
            }
        }
        function F(x,y,z) { return (x & y) | ((~x) & z); }
        function G(x,y,z) { return (x & z) | (y & (~z)); }
        function H(x,y,z) { return (x ^ y ^ z); }
        function I(x,y,z) { return (y ^ (x | (~z))); }
        function II(a,b,c,d,x,s,ac) {
            a = AddUnsigned(a, AddUnsigned(AddUnsigned(F(b,c,d), x), ac));
            return AddUnsigned(RotateLeft(a,s),b);
        }
        function GG(a,b,c,d,x,s,ac) {
            a = AddUnsigned(a, AddUnsigned(AddUnsigned(G(b,c,d), x), ac));
            return AddUnsigned(RotateLeft(a,s),b);
        }
        function HH(a,b,c,d,x,s,ac) {
            a = AddUnsigned(a, AddUnsigned(AddUnsigned(H(b,c,d), x), ac));
            return AddUnsigned(RotateLeft(a,s),b);
        }
        function II2(a,b,c,d,x,s,ac) {
            a = AddUnsigned(a, AddUnsigned(AddUnsigned(I(b,c,d), x), ac));
            return AddUnsigned(RotateLeft(a,s),b);
        }
        function ConvertToWordArray(string) {
            var lWordCount;
            var lMessageLength = string.length;
            var lNumberOfWords_temp1=lMessageLength + 8;
            var lNumberOfWords_temp2=(lNumberOfWords_temp1 - (lNumberOfWords_temp1 % 64))/64;
            var lNumberOfWords = (lNumberOfWords_temp2 + 1)*16;
            var lWordArray=Array(lNumberOfWords);
            var lBytePosition = 0;
            var lByteCount = 0;
            while ( lByteCount < lMessageLength ) {
                lWordCount = (lByteCount - (lByteCount % 4))/4;
                lBytePosition = (lByteCount % 4)*8;
                lWordArray[lWordCount] = (lWordArray[lWordCount] | (string.charCodeAt(lByteCount)<<lBytePosition));
                lByteCount++;
            }
            lWordCount = (lByteCount - (lByteCount % 4))/4;
            lBytePosition = (lByteCount % 4)*8;
            lWordArray[lWordCount] = lWordArray[lWordCount] | (0x80<<lBytePosition);
            lWordArray[lNumberOfWords-2] = lMessageLength<<3;
            lWordArray[lNumberOfWords-1] = lMessageLength>>>29;
            return lWordArray;
        }
        function WordToHex(lValue) {
            var WordToHexValue="",WordToHexValue_temp="",lByte,lCount;
            for (lCount = 0;lCount<=3;lCount++) {
                lByte = (lValue>>>(lCount*8)) & 255;
                WordToHexValue_temp = "0" + lByte.toString(16);
                WordToHexValue = WordToHexValue + WordToHexValue_temp.substr(WordToHexValue_temp.length-2,2);
            }
            return WordToHexValue;
        }
        function Utf8Encode(string) {
            string = string.replace(/\r\n/g,"\n");
            var utftext = "";
            for (var n = 0; n < string.length; n++) {
                var c = string.charCodeAt(n);
                if (c < 128) {
                    utftext += String.fromCharCode(c);
                }
                else if((c > 127) && (c < 2048)) {
                    utftext += String.fromCharCode((c >> 6) | 192);
                    utftext += String.fromCharCode((c & 63) | 128);
                }
                else {
                    utftext += String.fromCharCode((c >> 12) | 224);
                    utftext += String.fromCharCode(((c >> 6) & 63) | 128);
                    utftext += String.fromCharCode((c & 63) | 128);
                }
            }
            return utftext;
        }
        var x=Array();
        var k,AA,BB,CC,DD,a,b,c,d;
        var S11=7, S12=12, S13=17, S14=22;
        var S21=5, S22=9 , S23=14, S24=20;
        var S31=4, S32=11, S33=16, S34=23;
        var S41=6, S42=10, S43=15, S44=21;
        string = Utf8Encode(string);
        x = ConvertToWordArray(string);
        a = 0x67452301; b = 0xEFCDAB89; c = 0x98BADCFE; d = 0x10325476;
        for (k=0;k<x.length;k+=16) {
            AA=a; BB=b; CC=c; DD=d;
            a=II(a,b,c,d,x[k+0],S11,0xD76AA478);
            d=II(d,a,b,c,x[k+1],S12,0xE8C7B756);
            c=II(c,d,a,b,x[k+2],S13,0x242070DB);
            b=II(b,c,d,a,x[k+3],S14,0xC1BDCEEE);
            a=II(a,b,c,d,x[k+4],S11,0xF57C0FAF);
            d=II(d,a,b,c,x[k+5],S12,0x4787C62A);
            c=II(c,d,a,b,x[k+6],S13,0xA8304613);
            b=II(b,c,d,a,x[k+7],S14,0xFD469501);
            a=II(a,b,c,d,x[k+8],S11,0x698098D8);
            d=II(d,a,b,c,x[k+9],S12,0x8B44F7AF);
            c=II(c,d,a,b,x[k+10],S13,0xFFFF5BB1);
            b=II(b,c,d,a,x[k+11],S14,0x895CD7BE);
            a=II(a,b,c,d,x[k+12],S11,0x6B901122);
            d=II(d,a,b,c,x[k+13],S12,0xFD987193);
            c=II(c,d,a,b,x[k+14],S13,0xA679438E);
            b=II(b,c,d,a,x[k+15],S14,0x49B40821);
            a=GG(a,b,c,d,x[k+1],S21,0xF61E2562);
            d=GG(d,a,b,c,x[k+6],S22,0xC040B340);
            c=GG(c,d,a,b,x[k+11],S23,0x265E5A51);
            b=GG(b,c,d,a,x[k+0],S24,0xE9B6C7AA);
            a=GG(a,b,c,d,x[k+5],S21,0xD62F105D);
            d=GG(d,a,b,c,x[k+10],S22,0x2441453);
            c=GG(c,d,a,b,x[k+15],S23,0xD8A1E681);
            b=GG(b,c,d,a,x[k+4],S24,0xE7D3FBC8);
            a=GG(a,b,c,d,x[k+9],S21,0x21E1CDE6);
            d=GG(d,a,b,c,x[k+14],S22,0xC33707D6);
            c=GG(c,d,a,b,x[k+3],S23,0xF4D50D87);
            b=GG(b,c,d,a,x[k+8],S24,0x455A14ED);
            a=GG(a,b,c,d,x[k+13],S21,0xA9E3E905);
            d=GG(d,a,b,c,x[k+2],S22,0xFCEFA3F8);
            c=GG(c,d,a,b,x[k+7],S23,0x676F02D9);
            b=GG(b,c,d,a,x[k+12],S24,0x8D2A4C8A);
            a=HH(a,b,c,d,x[k+5],S31,0xFFFA3942);
            d=HH(d,a,b,c,x[k+8],S32,0x8771F681);
            c=HH(c,d,a,b,x[k+11],S33,0x6D9D6122);
            b=HH(b,c,d,a,x[k+14],S34,0xFDE5380C);
            a=HH(a,b,c,d,x[k+1],S31,0xA4BEEA44);
            d=HH(d,a,b,c,x[k+4],S32,0x4BDECFA9);
            c=HH(c,d,a,b,x[k+7],S33,0xF6BB4B60);
            b=HH(b,c,d,a,x[k+10],S34,0xBEBFBC70);
            a=HH(a,b,c,d,x[k+13],S31,0x289B7EC6);
            d=HH(d,a,b,c,x[k+0],S32,0xEAA127FA);
            c=HH(c,d,a,b,x[k+3],S33,0xD4EF3085);
            b=HH(b,c,d,a,x[k+6],S34,0x4881D05);
            a=HH(a,b,c,d,x[k+9],S31,0xD9D4D039);
            d=HH(d,a,b,c,x[k+12],S32,0xE6DB99E5);
            c=HH(c,d,a,b,x[k+15],S33,0x1FA27CF8);
            b=HH(b,c,d,a,x[k+2],S34,0xC4AC5665);
            a=II2(a,b,c,d,x[k+0],S41,0xF4292244);
            d=II2(d,a,b,c,x[k+7],S42,0x432AFF97);
            c=II2(c,d,a,b,x[k+14],S43,0xAB9423A7);
            b=II2(b,c,d,a,x[k+5],S44,0xFC93A039);
            a=II2(a,b,c,d,x[k+12],S41,0x655B59C3);
            d=II2(d,a,b,c,x[k+3],S42,0x8F0CCC92);
            c=II2(c,d,a,b,x[k+10],S43,0xFFEFF47D);
            b=II2(b,c,d,a,x[k+1],S44,0x85845DD1);
            a=II2(a,b,c,d,x[k+8],S41,0x6FA87E4F);
            d=II2(d,a,b,c,x[k+15],S42,0xFE2CE6E0);
            c=II2(c,d,a,b,x[k+6],S43,0xA3014314);
            b=II2(b,c,d,a,x[k+13],S44,0x4E0811A1);
            a=II2(a,b,c,d,x[k+4],S41,0xF7537E82);
            d=II2(d,a,b,c,x[k+11],S42,0xBD3AF235);
            c=II2(c,d,a,b,x[k+2],S43,0x2AD7D2BB);
            b=II2(b,c,d,a,x[k+9],S44,0xEB86D391);
            a=AddUnsigned(a,AA);
            b=AddUnsigned(b,BB);
            c=AddUnsigned(c,CC);
            d=AddUnsigned(d,DD);
        }
        var temp = WordToHex(a)+WordToHex(b)+WordToHex(c)+WordToHex(d);
        return temp.toLowerCase();
    }
</script>
@endsection
