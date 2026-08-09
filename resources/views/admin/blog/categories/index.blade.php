@extends('layouts.admin')

@section('title', 'Quản lý Chuyên Mục Blog - Trang Quản Trị')

@section('content')
<div class="space-y-6" x-data="categoryManager">
    <!-- Header -->
    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Quản lý Chuyên Mục Blog</h1>
        <p class="text-sm text-gray-500">Phân loại các bài viết Blog theo từng chuyên mục riêng biệt để người đọc dễ tìm kiếm.</p>
    </div>

    <!-- Main Grid Layout -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Cột trái: Danh sách chuyên mục (Col span 2) -->
        <div class="lg:col-span-2 bg-white dark:bg-slate-900 border border-gray-150 dark:border-slate-800 rounded-2xl shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse whitespace-nowrap">
                    <thead>
                        <tr class="bg-gray-55 dark:bg-slate-950 border-b border-gray-100 dark:border-slate-855 text-xs font-bold text-gray-600 dark:text-slate-300 uppercase tracking-wider">
                            <th class="px-6 py-4 min-w-[200px]">Chuyên mục</th>
                            <th class="px-6 py-4">Slug</th>
                            <th class="px-6 py-4">Số bài viết</th>
                            <th class="px-6 py-4 text-center">Thứ tự</th>
                            <th class="px-6 py-4 text-right">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-slate-855 text-sm">
                        @forelse($categories as $cat)
                        <tr class="hover:bg-gray-50/50 dark:hover:bg-slate-800/20">
                            <td class="px-6 py-4 min-w-[200px] whitespace-normal">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-xl bg-shopee/10 text-shopee flex items-center justify-center shrink-0">
                                        <i data-lucide="{{ $cat->icon ?: 'folder' }}" class="w-5 h-5"></i>
                                    </div>
                                    <div>
                                        <span class="font-semibold text-gray-900 dark:text-white">{{ $cat->name }}</span>
                                        @if($cat->description)
                                        <p class="text-xs text-gray-400 line-clamp-1 mt-0.5">{{ $cat->description }}</p>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-gray-500 dark:text-slate-400">
                                {{ $cat->slug }}
                            </td>
                            <td class="px-6 py-4 text-gray-700 dark:text-slate-300 font-medium">
                                <span class="px-2 py-0.5 bg-gray-100 dark:bg-slate-800 rounded-full text-xs">
                                    {{ $cat->posts_count }} bài
                                </span>
                            </td>
                            <td class="px-6 py-4 text-center text-gray-500">
                                {{ $cat->sort_order }}
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <button type="button" 
                                            @click="setAction({
                                                id: {{ $cat->id }},
                                                name: {{ json_encode($cat->name) }},
                                                icon: {{ json_encode($cat->icon ?: 'folder') }},
                                                description: {{ json_encode($cat->description ?: '') }},
                                                sort_order: {{ $cat->sort_order ?? 0 }}
                                            })" 
                                            class="p-1.5 bg-gray-50 hover:bg-shopee/10 text-gray-600 hover:text-shopee rounded-lg border border-gray-150 transition-all" 
                                            title="Sửa chuyên mục">
                                        <i data-lucide="edit-3" class="w-4 h-4 pointer-events-none"></i>
                                    </button>
                                    <form action="{{ route('admin.blog.categories.destroy', $cat->id) }}" method="POST" onsubmit="return confirm('Bạn có chắc chắn muốn xóa chuyên mục này?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-1.5 bg-gray-50 hover:bg-red-50 text-gray-600 hover:text-red-500 rounded-lg border border-gray-150 transition-all" title="Xóa chuyên mục">
                                            <i data-lucide="trash-2" class="w-4 h-4 pointer-events-none"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="px-6 py-8 text-center text-gray-500">
                                <i data-lucide="folder" class="w-12 h-12 text-gray-300 mx-auto mb-3"></i>
                                Chưa có chuyên mục nào được tạo.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Cột phải: Form Thêm / Sửa chuyên mục -->
        <div class="bg-white dark:bg-slate-900 border border-gray-150 dark:border-slate-800 rounded-2xl shadow-sm p-6">
            <h3 class="text-sm font-bold text-gray-900 dark:text-white uppercase tracking-wider pb-3 border-b border-gray-50 dark:border-slate-850 mb-5"
                x-text="isEdit ? 'Cập Nhật Chuyên Mục' : 'Thêm Chuyên Mục Mới'">
                Thêm Chuyên Mục Mới
            </h3>

            <form x-ref="form" action="{{ route('admin.blog.categories.store') }}" method="POST" class="space-y-4">
                @csrf
                <!-- Method spoofing when in edit mode -->
                <template x-if="isEdit">
                    <input type="hidden" name="_method" value="PUT">
                </template>

                <!-- Name -->
                <div>
                    <label class="block text-xs font-bold text-gray-700 dark:text-slate-350 uppercase mb-2">Tên chuyên mục *</label>
                    <input type="text" 
                           name="name" 
                           required 
                           x-model="editName"
                           placeholder="Ví dụ: Hướng dẫn hoàn tiền" 
                           class="w-full px-4 py-2 text-sm rounded-xl border border-gray-200 dark:border-slate-850 bg-gray-50 dark:bg-slate-950 dark:text-white focus:outline-none focus:ring-2 focus:ring-shopee">
                </div>

                <!-- Icon Lucide -->
                <div>
                    <label class="block text-xs font-bold text-gray-700 dark:text-slate-350 uppercase mb-2">Biểu tượng (Icon)</label>
                    <div class="flex gap-2">
                        <div class="w-10 h-10 rounded-xl bg-shopee/10 text-shopee flex items-center justify-center shrink-0 border border-shopee/20">
                            <i :data-lucide="editIcon" class="w-5 h-5"></i>
                        </div>
                        <input type="text" 
                               name="icon" 
                               x-model="editIcon"
                               placeholder="folder, tag, book..." 
                               class="flex-grow px-4 py-2 text-sm rounded-xl border border-gray-200 dark:border-slate-850 bg-gray-50 dark:bg-slate-950 dark:text-white focus:outline-none focus:ring-2 focus:ring-shopee">
                        <button type="button" 
                                @click="isOpenIconModal = true" 
                                class="px-3.5 bg-gray-50 hover:bg-gray-100 dark:bg-slate-950 dark:hover:bg-slate-900 text-gray-700 dark:text-slate-300 rounded-xl border border-gray-200 dark:border-slate-850 text-xs font-bold transition-all">
                            Chọn
                        </button>
                    </div>
                </div>

                <!-- Description -->
                <div>
                    <label class="block text-xs font-bold text-gray-700 dark:text-slate-355 uppercase mb-2">Mô tả ngắn</label>
                    <textarea name="description" 
                              rows="3" 
                              x-model="editDescription"
                              placeholder="Mô tả tóm tắt vai trò của chuyên mục này..." 
                              class="w-full px-4 py-2 text-sm rounded-xl border border-gray-200 dark:border-slate-855 bg-gray-50 dark:bg-slate-955 dark:text-white focus:outline-none focus:ring-2 focus:ring-shopee"></textarea>
                </div>

                <!-- Sort Order -->
                <div>
                    <label class="block text-xs font-bold text-gray-700 dark:text-slate-350 uppercase mb-2">Thứ tự hiển thị</label>
                    <input type="number" 
                           name="sort_order" 
                           x-model="editSortOrder"
                           placeholder="0" 
                           class="w-full px-4 py-2 text-sm rounded-xl border border-gray-200 dark:border-slate-850 bg-gray-50 dark:bg-slate-950 dark:text-white focus:outline-none focus:ring-2 focus:ring-shopee">
                </div>

                <!-- Submit buttons -->
                <div class="pt-4 border-t border-gray-50 dark:border-slate-850 flex gap-3">
                    <button type="button" 
                            x-show="isEdit"
                            @click="resetForm()" 
                            class="w-1/2 flex items-center justify-center px-4 py-2.5 text-xs font-bold text-gray-500 bg-gray-50 hover:bg-gray-100 rounded-xl border border-gray-200 dark:border-slate-850 transition-all">
                        Hủy
                    </button>
                    <button type="submit" 
                            class="flex-grow flex items-center justify-center px-4 py-2.5 text-xs font-bold text-white bg-shopee hover:bg-shopee-dark rounded-xl shadow-md transition-all"
                            x-text="isEdit ? 'Cập Nhật' : 'Thêm Mới'">
                        Thêm Mới
                    </button>
                </div>
            </form>
        </div>
    </div>
    <!-- Modal Chọn Icon Lucide -->
    <template x-teleport="body">
        <div x-show="isOpenIconModal" 
             x-cloak
             class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm transition-opacity"
             @keydown.escape.window="isOpenIconModal = false">
            <div class="bg-white dark:bg-slate-900 w-full max-w-xl rounded-2xl shadow-xl overflow-hidden transform transition-all border border-gray-150 dark:border-slate-800"
                 @click.away="isOpenIconModal = false">
                <!-- Modal Header -->
                <div class="px-6 py-4 border-b border-gray-100 dark:border-slate-855 flex items-center justify-between">
                    <h3 class="font-bold text-gray-900 dark:text-white text-base flex items-center gap-2">
                        <i data-lucide="grid" class="w-5 h-5 text-shopee"></i>
                        Chọn Biểu Tượng Chuyên Mục
                    </h3>
                    <button type="button" @click="isOpenIconModal = false" class="text-gray-400 hover:text-gray-650 dark:hover:text-white">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>
                
                <!-- Tìm kiếm nhanh icon -->
                <div class="p-6 space-y-4">
                    <div class="relative">
                        <i data-lucide="search" class="w-4 h-4 text-gray-400 absolute left-3 top-3"></i>
                        <input type="text" 
                               x-model="searchQuery" 
                               @input="$nextTick(() => { if (window.lucide) { window.lucide.createIcons(); } })"
                               placeholder="Tìm kiếm nhanh biểu tượng (ví dụ: bag, cart, tag...)" 
                               class="w-full pl-9 pr-4 py-2.5 text-sm rounded-xl border border-gray-200 dark:border-slate-850 bg-gray-50 dark:bg-slate-950 dark:text-white focus:outline-none focus:ring-2 focus:ring-shopee">
                    </div>
                    
                    <!-- Grid Icons -->
                    <div class="grid grid-cols-5 sm:grid-cols-6 md:grid-cols-8 gap-3 max-h-[300px] overflow-y-auto pr-1">
                        <template x-for="iconName in icons.filter(name => name.includes(searchQuery.toLowerCase()))" :key="iconName">
                            <button type="button" 
                                    @click="selectIcon(iconName)" 
                                    class="flex flex-col items-center justify-center p-2.5 rounded-xl border border-gray-100 dark:border-slate-855 bg-gray-50/50 dark:bg-slate-955 hover:border-shopee hover:bg-shopee/5 text-gray-700 dark:text-slate-300 hover:text-shopee dark:hover:text-shopee transition-all gap-1.5"
                                    :class="{ 'border-shopee bg-shopee/10 text-shopee': editIcon === iconName }"
                                    :title="iconName">
                                <i :data-lucide="iconName" class="w-5 h-5"></i>
                                <span class="text-[9px] truncate max-w-full text-center" x-text="iconName"></span>
                            </button>
                        </template>
                    </div>
                </div>
                
                <!-- Modal Footer -->
                <div class="px-6 py-3 bg-gray-50 dark:bg-slate-950 border-t border-gray-100 dark:border-slate-855 flex justify-end">
                    <button type="button" @click="isOpenIconModal = false" class="px-4 py-2 text-xs font-bold text-gray-500 bg-white hover:bg-gray-100 dark:bg-slate-900 dark:hover:bg-slate-800 rounded-xl border border-gray-200 dark:border-slate-800 transition-all">
                        Đóng
                    </button>
                </div>
            </div>
        </div>
    </template>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('categoryManager', () => ({
            isEdit: false, 
            editId: null, 
            editName: '', 
            editIcon: 'folder', 
            editDescription: '', 
            editSortOrder: 0,
            isOpenIconModal: false,
            searchQuery: '',
            icons: [
                'folder', 'book', 'tag', 'shopping-bag', 'calendar', 'home', 'user', 'settings', 
                'bell', 'heart', 'star', 'info', 'help-circle', 'image', 'file-text', 'link', 
                'globe', 'share-2', 'trending-up', 'award', 'gift', 'activity', 'percent', 
                'credit-card', 'database', 'shield', 'check-circle-2', 'alert-triangle', 'zap', 
                'coffee', 'map-pin', 'phone', 'mail', 'search', 'clock', 'eye', 'thumbs-up', 
                'message-square', 'bar-chart-2', 'briefcase', 'compass', 'code', 'download', 
                'upload', 'lock', 'unlock', 'key', 'trash-2', 'edit-3', 'plus', 'minus', 
                'book-open', 'list', 'grid', 'sliders', 'tv', 'smartphone', 'monitor', 
                'thumbs-down', 'heart-off', 'flag', 'navigation', 'bookmark', 'paperclip',
                'dollar-sign', 'hash', 'tool', 'archive', 'life-buoy', 'send', 'target',
                'shopping-cart', 'store', 'truck', 'package', 'wallet', 'users', 'user-plus',
                'user-check', 'user-minus', 'shield-check', 'shield-alert', 'check', 'x',
                'alert-circle', 'refresh-cw', 'rotate-cw', 'camera', 'video', 'headphones'
            ],
            
            init() {
                this.$watch('editIcon', value => {
                    this.$nextTick(() => {
                        if (window.lucide) {
                            window.lucide.createIcons();
                        }
                    });
                });
                this.$watch('isOpenIconModal', value => {
                    if (value) {
                        this.$nextTick(() => {
                            if (window.lucide) {
                                window.lucide.createIcons();
                            }
                        });
                    }
                });
            },
            
            setAction(cat) {
                // Đặt cờ chỉnh sửa
                this.isEdit = true;
                this.editId = cat.id;
                this.editName = cat.name;
                this.editIcon = cat.icon || 'folder';
                this.editDescription = cat.description || '';
                this.editSortOrder = cat.sort_order || 0;
                
                // Cập nhật action cho form
                if (this.$refs && this.$refs.form) {
                    this.$refs.form.action = '/' + window.adminPrefix + '/blog/categories/' + cat.id;
                }
                
                // Tự động cuộn và focus vào form bên phải để người dùng nhận biết
                this.$nextTick(() => {
                    if (this.$refs && this.$refs.form) {
                        const formElement = this.$refs.form;
                        formElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        const nameInput = formElement.querySelector('input[name="name"]');
                        if (nameInput) {
                            nameInput.focus();
                        }
                    }
                });
            },
            resetForm() {
                this.isEdit = false;
                this.editId = null;
                this.editName = '';
                this.editIcon = 'folder';
                this.editDescription = '';
                this.editSortOrder = 0;
                if (this.$refs && this.$refs.form) {
                    this.$refs.form.action = '{{ route('admin.blog.categories.store') }}';
                }
            },
            selectIcon(name) {
                this.editIcon = name;
                this.isOpenIconModal = false;
            }
        }));
    });
</script>
@endsection
