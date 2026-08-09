@extends('layouts.admin')

@section('title', __('Quản Lý Menu Tùy Biến') . ' - ' . $siteName)

@section('content')
<div class="space-y-6" x-data="{ 
    isEditMode: false, 
    showFormModal: false,
    editMenu: { id: '', title: '', url: '', icon: '', position: 'header', target: '_self', order: 0, status: true, parent_id: '', auth_rule: 'all' },
    positionType: 'header', // Theo dõi loại vị trí được chọn (header, footer, custom)
    customPosition: '', // Lưu vị trí tự gõ khi chọn custom
    actionUrl: '{{ route('admin.menus.store') }}' + '{{ request()->getQueryString() ? "?" . request()->getQueryString() : "" }}',
    
    init() {
        this.$watch('editMenu.icon', () => {
            this.$nextTick(() => {
                if (window.lucide) window.lucide.createIcons();
            });
        });
    },
    
    // Thiết lập trạng thái sửa menu
    setEditMode(menu) {
        this.isEditMode = true;
        this.editMenu = { 
            id: menu.id, 
            title: menu.title, 
            url: menu.url || '', 
            icon: menu.icon || '', 
            position: menu.position, 
            target: menu.target, 
            order: menu.order, 
            status: menu.status ? true : false, 
            parent_id: menu.parent_id || '',
            auth_rule: menu.auth_rule || 'all'
        };
        
        // Xác định vị trí menu là mặc định hay tự nhập (bổ sung user_dropdown và bottom)
        if (['header', 'footer', 'user_dropdown', 'bottom'].includes(menu.position)) {
            this.positionType = menu.position;
            this.customPosition = '';
        } else {
            this.positionType = 'custom';
            this.customPosition = menu.position;
        }
        
        this.actionUrl = '/' + window.adminPrefix + '/menus/' + menu.id + '{{ request()->getQueryString() ? "?" . request()->getQueryString() : "" }}';
        this.showFormModal = true;
    },

    // Nhân bản menu: sao chép thông tin của menu được chọn và thiết lập chế độ tạo mới
    duplicateMenu(menu) {
        this.isEditMode = false; // Tắt chế độ edit để form submit tới route thêm mới (POST store)
        this.editMenu = { 
            id: '', // Đặt id rỗng để tạo bản ghi hoàn toàn mới
            title: menu.title + ' (Sao chép)', // Tự động thêm hậu tố để quản trị viên phân biệt bản sao
            url: menu.url || '', 
            icon: menu.icon || '', 
            position: menu.position, 
            target: menu.target, 
            order: menu.order, 
            status: menu.status ? true : false, 
            parent_id: menu.parent_id || '',
            auth_rule: menu.auth_rule || 'all'
        };
        
        // Xác định vị trí menu để cập nhật giao diện chọn vị trí
        if (['header', 'footer', 'user_dropdown', 'bottom'].includes(menu.position)) {
            this.positionType = menu.position;
            this.customPosition = '';
        } else {
            this.positionType = 'custom';
            this.customPosition = menu.position;
        }
        
        this.actionUrl = '{{ route('admin.menus.store') }}' + '{{ request()->getQueryString() ? "?" . request()->getQueryString() : "" }}';
        this.showFormModal = true;
    },
    
    // Thiết lập trạng thái thêm mới menu
    setCreateMode() {
        this.isEditMode = false;
        this.editMenu = { id: '', title: '', url: '', icon: '', position: 'header', target: '_self', order: 0, status: true, parent_id: '', auth_rule: 'all' };
        this.positionType = 'header';
        this.customPosition = '';
        this.actionUrl = '{{ route('admin.menus.store') }}' + '{{ request()->getQueryString() ? "?" . request()->getQueryString() : "" }}';
        this.showFormModal = true;
    },

    // Lấy giá trị vị trí thực tế để gửi lên server trước khi submit
    getSubmitPosition() {
        return this.positionType === 'custom' ? this.customPosition : this.positionType;
    }
}">

    <!-- Tiêu đề & Giới thiệu ngắn -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ __('Quản Lý Menu Tùy Biến') }}</h1>
            <p class="text-sm text-gray-500">{{ __('Tạo menu theo ý thích, gán liên kết và chọn vị trí hiển thị trên toàn hệ thống') }}</p>
        </div>
        <div>
            <button type="button" 
                    @click="setCreateMode()" 
                    class="inline-flex items-center gap-2 px-4 py-2 bg-shopee hover:bg-shopee-dark text-white text-xs font-bold rounded-xl transition-all shadow-md active:scale-95">
                <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                {{ __('Thêm Menu Mới') }}
            </button>
        </div>
    </div>

    <!-- Layout Grid 12 cột -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
        
        <!-- Danh sách Menu hiển thị full chiều ngang -->
        <div class="lg:col-span-12 bg-white p-6 rounded-3xl shadow-sm border border-gray-200 space-y-4">
            
            <!-- Bộ lọc và tìm kiếm nhanh -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-gray-100 pb-4">
                <div class="flex items-center gap-2">
                    <h3 class="font-bold text-gray-900 text-sm">{{ __('Danh sách liên kết menu') }}</h3>
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-shopee/10 text-shopee">
                        {{ $menus->count() }} {{ __('Mục') }}
                    </span>
                </div>
                
                <form action="{{ route('admin.menus.index') }}" method="GET" class="flex flex-wrap items-center gap-2">
                    <!-- Lọc theo Vị trí -->
                    <select name="position" onchange="this.form.submit()" class="px-3 py-1.5 border border-gray-200 rounded-xl text-xs bg-gray-50 focus:outline-none focus:ring-1 focus:ring-shopee text-gray-700">
                        <option value="">{{ __('Tất cả vị trí') }}</option>
                        @foreach($suggestedPositions as $pos)
                            <option value="{{ $pos }}" {{ request('position') === $pos ? 'selected' : '' }}>{{ strtoupper($pos) }}</option>
                        @endforeach
                    </select>

                    <!-- Ô tìm kiếm -->
                    <div class="relative w-40 sm:w-48">
                        <input type="text" 
                               name="search" 
                               value="{{ request('search') }}" 
                               placeholder="{{ __('Tìm menu...') }}"
                               class="w-full pl-8 pr-3 py-1.5 border border-gray-200 rounded-xl text-xs bg-gray-50 focus:outline-none focus:ring-1 focus:ring-shopee text-gray-700">
                        <div class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none text-gray-400">
                            <i data-lucide="search" class="w-3.5 h-3.5"></i>
                        </div>
                    </div>

                    <button type="submit" class="px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-semibold rounded-xl transition-all">
                        {{ __('Lọc') }}
                    </button>
                    
                    @if(request()->anyFilled(['position', 'search']))
                        <a href="{{ route('admin.menus.index') }}" class="px-3 py-1.5 bg-red-50 hover:bg-red-100 text-red-600 text-xs font-semibold rounded-xl transition-all">
                            {{ __('Xóa lọc') }}
                        </a>
                    @endif
                </form>
            </div>

            <!-- Hiển thị danh sách các bảng menu riêng biệt theo từng vị trí để admin dễ quản lý và sắp xếp độc lập -->
            <div class="space-y-6">
                @forelse($menus->groupBy('position') as $position => $positionMenus)
                    <div class="border border-gray-200 dark:border-slate-800 rounded-2xl p-4 bg-gray-50/10 dark:bg-slate-900/10 space-y-3">
                        <!-- Tiêu đề của vị trí menu và mô tả cụ thể -->
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between border-b border-gray-100 dark:border-slate-800/60 pb-2 gap-2">
                            <div class="flex items-center gap-2">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase {{ $position === 'header' ? 'bg-purple-50 text-purple-700 border border-purple-100 dark:bg-purple-950/30 dark:text-purple-400 dark:border-purple-900/50' : ($position === 'footer' ? 'bg-orange-50 text-orange-700 border border-orange-100 dark:bg-orange-950/30 dark:text-orange-400 dark:border-orange-900/50' : ($position === 'user_dropdown' ? 'bg-blue-50 text-blue-700 border border-blue-100 dark:bg-blue-950/30 dark:text-blue-400 dark:border-blue-900/50' : ($position === 'bottom' ? 'bg-green-50 text-green-700 border border-green-100 dark:bg-green-950/30 dark:text-green-400 dark:border-green-900/50' : 'bg-gray-50 text-gray-700 border border-gray-150 dark:bg-slate-800 dark:text-slate-350 dark:border-slate-700/80'))) }}">
                                    {{ $position === 'header' ? __('Header') : ($position === 'footer' ? __('Footer') : ($position === 'user_dropdown' ? __('User Dropdown') : ($position === 'bottom' ? __('Bottom Nav') : strtoupper($position)))) }}
                                </span>
                                <span class="text-xs text-gray-500 dark:text-slate-400">({{ $positionMenus->count() }} {{ __('Mục') }})</span>
                            </div>
                            <!-- Mô tả chức năng thực tế của từng vị trí trên giao diện website -->
                            <span class="text-[10px] text-gray-400 dark:text-slate-500 italic">
                                @if($position === 'header')
                                    {{ __('Thanh điều hướng chính nằm ở đầu website') }}
                                @elseif($position === 'footer')
                                    {{ __('Các nhóm liên kết hiển thị ở chân trang website (Footer)') }}
                                @elseif($position === 'user_dropdown')
                                    {{ __('Menu thả xuống của thành viên (khi nhấn vào ảnh đại diện)') }}
                                @elseif($position === 'bottom')
                                    {{ __('Thanh điều hướng dưới đáy màn hình dành cho thiết bị di động') }}
                                @else
                                    {{ __('Vị trí tùy biến tự thiết lập, cần gọi bằng mã code tương ứng trong giao diện') }}
                                @endif
                            </span>
                        </div>

                        <!-- Bảng danh sách liên kết của vị trí hiện tại -->
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse whitespace-nowrap">
                                <thead>
                                    <tr class="border-b border-gray-100 dark:border-slate-850 text-gray-400 text-[10px] font-bold uppercase tracking-wider">
                                        <th class="py-3 px-2 w-10 text-center"></th>
                                        <th class="py-3 px-2">{{ __('Tiêu đề Menu') }}</th>
                                        <th class="py-3 px-2">{{ __('Đường dẫn (URL)') }}</th>
                                        <th class="py-3 px-2 text-center">{{ __('Đối tượng') }}</th>
                                        <th class="py-3 px-2 text-center">{{ __('Cách mở') }}</th>
                                        <th class="py-3 px-2 text-center">{{ __('Trạng thái') }}</th>
                                        <th class="py-3 px-2 text-right">{{ __('Hành động') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="menu-sortable-list divide-y divide-gray-50 dark:divide-slate-850 text-xs" data-position="{{ $position }}">
                                    @foreach($positionMenus as $menu)
                                        <tr class="hover:bg-gray-50/50 dark:hover:bg-slate-850/30 transition-colors" data-id="{{ $menu->id }}">
                                            <!-- Nút kéo thả để thay đổi thứ tự trong nội bộ vị trí -->
                                            <td class="py-3.5 px-2 text-center cursor-grab active:cursor-grabbing handle text-gray-400 hover:text-shopee transition-colors" title="{{ __('Kéo thả để sắp xếp') }}">
                                                <i data-lucide="grip-vertical" class="w-4 h-4 mx-auto"></i>
                                            </td>
                                            
                                            <!-- Tiêu đề và icon của menu (Thụt lề nếu là menu con) -->
                                            <td class="py-3.5 px-2 font-semibold">
                                                <div class="flex items-center gap-1.5">
                                                    @if($menu->parent_id)
                                                        <span class="text-gray-300 dark:text-slate-600 mr-0.5">—</span>
                                                    @endif
                                                    @if($menu->icon)
                                                        <div class="w-6 h-6 flex items-center justify-center bg-gray-50 dark:bg-slate-850/40 rounded-lg border border-gray-150 dark:border-slate-800/80 flex-shrink-0" title="Icon: {{ $menu->icon }}">
                                                            <i data-lucide="{{ $menu->icon }}" class="w-3.5 h-3.5 text-gray-500 dark:text-slate-400"></i>
                                                        </div>
                                                    @endif
                                                    <span class="{{ $menu->parent_id ? 'text-gray-600 dark:text-slate-350 font-medium' : 'text-gray-900 dark:text-slate-200 font-bold' }}">{{ $menu->title }}</span>
                                                </div>
                                            </td>
                                            
                                            <!-- Đường dẫn liên kết -->
                                            <td class="py-3.5 px-2 font-mono text-gray-500 dark:text-slate-400 max-w-[150px] truncate" title="{{ $menu->url }}">
                                                {{ $menu->url ?: '—' }}
                                            </td>
                                            
                                            <!-- Đối tượng hiển thị -->
                                            <td class="py-3.5 px-2 text-center">
                                                @if($menu->auth_rule === 'all')
                                                    <span class="inline-flex px-2 py-0.5 rounded-full text-[9px] font-bold bg-slate-50 text-slate-700 border border-slate-200 dark:bg-slate-800/50 dark:text-slate-300 dark:border-slate-750">
                                                        {{ __('Tất cả') }}
                                                    </span>
                                                @elseif($menu->auth_rule === 'auth')
                                                    <span class="inline-flex px-2 py-0.5 rounded-full text-[9px] font-bold bg-blue-50 text-blue-700 border border-blue-100 dark:bg-blue-950/30 dark:text-blue-400 dark:border-blue-900/40">
                                                        {{ __('Đăng nhập') }}
                                                    </span>
                                                @elseif($menu->auth_rule === 'guest')
                                                    <span class="inline-flex px-2 py-0.5 rounded-full text-[9px] font-bold bg-yellow-50 text-yellow-700 border border-yellow-100 dark:bg-yellow-950/30 dark:text-yellow-400 dark:border-yellow-900/40">
                                                        {{ __('Khách chưa đăng nhập') }}
                                                    </span>
                                                @endif
                                            </td>
                                            
                                            <!-- Cách mở tab -->
                                            <td class="py-3.5 px-2 text-center font-mono text-gray-500 dark:text-slate-400">
                                                {{ $menu->target }}
                                            </td>
                                            
                                            <!-- Bật tắt trạng thái hoạt động -->
                                            <td class="py-3.5 px-2 text-center">
                                                <label class="relative inline-flex items-center cursor-pointer select-none">
                                                    <input type="checkbox" 
                                                           class="sr-only peer toggle-status-btn" 
                                                           data-id="{{ $menu->id }}"
                                                           {{ $menu->status ? 'checked' : '' }}>
                                                    <div class="w-9 h-5 bg-gray-200 peer-focus:outline-none rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all dark:border-slate-600 peer-checked:bg-shopee"></div>
                                                </label>
                                            </td>
                                            
                                            <!-- Thao tác chỉnh sửa và xóa -->
                                            <td class="py-3.5 px-2 text-right">
                                                <div class="flex items-center justify-end gap-2">
                                                    <!-- Nút nhân bản liên kết nhanh -->
                                                    <button type="button" 
                                                            @click="duplicateMenu({
                                                                id: '{{ $menu->id }}',
                                                                title: '{{ addslashes($menu->title) }}',
                                                                url: '{{ addslashes($menu->url) }}',
                                                                icon: '{{ addslashes($menu->icon) }}',
                                                                position: '{{ $menu->position }}',
                                                                target: '{{ $menu->target }}',
                                                                order: '{{ $menu->order }}',
                                                                status: {{ $menu->status ? 'true' : 'false' }},
                                                                parent_id: '{{ $menu->parent_id }}',
                                                                auth_rule: '{{ $menu->auth_rule }}'
                                                            })"
                                                            class="p-1.5 bg-emerald-50 hover:bg-emerald-100 dark:bg-emerald-950/40 dark:hover:bg-emerald-900/60 text-emerald-600 dark:text-emerald-400 rounded-lg transition-all" 
                                                            title="{{ __('Nhân bản') }}">
                                                        <i data-lucide="copy" class="w-3.5 h-3.5"></i>
                                                    </button>

                                                    <!-- Nút sửa liên kết -->
                                                    <button type="button" 
                                                            @click="setEditMode({
                                                                id: '{{ $menu->id }}',
                                                                title: '{{ addslashes($menu->title) }}',
                                                                url: '{{ addslashes($menu->url) }}',
                                                                icon: '{{ addslashes($menu->icon) }}',
                                                                position: '{{ $menu->position }}',
                                                                target: '{{ $menu->target }}',
                                                                order: '{{ $menu->order }}',
                                                                status: {{ $menu->status ? 'true' : 'false' }},
                                                                parent_id: '{{ $menu->parent_id }}',
                                                                auth_rule: '{{ $menu->auth_rule }}'
                                                            })"
                                                            class="p-1.5 bg-blue-50 hover:bg-blue-100 dark:bg-blue-950/40 dark:hover:bg-blue-900/60 text-blue-600 dark:text-blue-400 rounded-lg transition-all" 
                                                            title="{{ __('Sửa menu') }}">
                                                        <i data-lucide="pencil" class="w-3.5 h-3.5"></i>
                                                    </button>
             
                                                    <!-- Nút xóa liên kết -->
                                                    <form action="{{ route('admin.menus.destroy', $menu->id) }}{{ request()->getQueryString() ? '?' . request()->getQueryString() : '' }}" method="POST" onsubmit="return confirm('Bạn có chắc chắn muốn xoá menu này? Các menu con (nếu có) cũng sẽ bị xoá.')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="p-1.5 bg-red-50 hover:bg-red-100 dark:bg-red-950/40 dark:hover:bg-red-900/60 text-red-600 dark:text-red-400 rounded-lg transition-all" title="{{ __('Xoá menu') }}">
                                                            <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @empty
                    <div class="py-8 text-center text-gray-400 dark:text-slate-500">
                        {{ __('Chưa có menu nào được cấu hình trong hệ thống.') }}
                    </div>
                @endforelse
            </div>
        </div>

    </div>

    <!-- Modal Thêm / Chỉnh Sửa Menu -->
    <div x-show="showFormModal" 
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto" 
         aria-labelledby="modal-title" 
         role="dialog" 
         aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <!-- Background overlay -->
            <div x-show="showFormModal" 
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="fixed inset-0 bg-gray-500/70 dark:bg-slate-950/85 backdrop-blur-sm transition-opacity" 
                 @click="showFormModal = false"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <!-- Modal Panel -->
            <div x-show="showFormModal" 
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 class="inline-block align-bottom bg-white dark:bg-slate-900 rounded-3xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full border border-gray-100 dark:border-slate-800/80 p-6 space-y-4">
                
                <div class="flex items-center justify-between border-b border-gray-100 dark:border-slate-850 pb-3">
                    <h3 class="text-xs font-bold text-gray-900 dark:text-slate-200 uppercase tracking-wider flex items-center gap-1.5" x-text="isEditMode ? '{{ __('Chỉnh sửa Menu') }}' : '{{ __('Thêm Menu mới') }}'">
                    </h3>
                    <div class="flex items-center gap-3">
                        <button type="button" 
                                x-show="isEditMode" 
                                @click="setCreateMode()" 
                                class="text-[10px] text-gray-400 hover:text-shopee flex items-center gap-1"
                                x-cloak>
                            <i data-lucide="plus-circle" class="w-3.5 h-3.5"></i>
                            {{ __('Chuyển về thêm mới') }}
                        </button>
                        <button type="button" @click="showFormModal = false" class="text-gray-400 dark:text-slate-500 hover:text-gray-500 dark:hover:text-slate-400">
                            <i data-lucide="x" class="w-4 h-4"></i>
                        </button>
                    </div>
                </div>

                <!-- Form -->
                <form :action="actionUrl" method="POST" class="space-y-4" @submit="editMenu.position = getSubmitPosition()">
                    @csrf
                    <template x-if="isEditMode">
                        <input type="hidden" name="_method" value="PUT">
                    </template>

                    <!-- Tiêu đề Menu -->
                    <div>
                        <label for="title" class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1">{{ __('Tiêu đề Menu') }} <span class="text-red-500">*</span></label>
                        <input type="text" 
                               name="title" 
                               id="title" 
                               required 
                               x-model="editMenu.title"
                               placeholder="Ví dụ: Trang Chủ, Hướng Dẫn..."
                               class="block w-full px-4 py-2 border border-gray-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-gray-50/50 text-gray-800">
                    </div>

                    <!-- Gợi ý liên kết nhanh từ website -->
                    <div>
                        <label for="quick_link" class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1">{{ __('Gợi ý liên kết nhanh') }}</label>
                        <select id="quick_link" 
                                @change="
                                    if ($event.target.value) {
                                        const selected = JSON.parse($event.target.value);
                                        editMenu.url = selected.url;
                                        if (!editMenu.title) {
                                            editMenu.title = selected.title;
                                        }
                                        $event.target.value = '';
                                    }
                                "
                                class="block w-full px-4 py-2 border border-gray-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-gray-50/50 text-gray-700">
                            <option value="">-- {{ __('Chọn liên kết gợi ý để nhập nhanh') }} --</option>
                            <optgroup label="{{ __('Trang Hệ Thống') }}">
                                <option value="{{ json_encode(['url' => '/', 'title' => __('Trang chủ')]) }}">{{ __('Trang chủ') }} (/)</option>
                                <option value="{{ json_encode(['url' => '/blog', 'title' => __('Tin tức')]) }}">{{ __('Tin tức') }} (/blog)</option>
                                <option value="{{ json_encode(['url' => '/coupons', 'title' => __('Mã giảm giá')]) }}">{{ __('Mã giảm giá') }} (/coupons)</option>
                                <option value="{{ json_encode(['url' => '/ranking', 'title' => __('Bảng xếp hạng')]) }}">{{ __('Bảng xếp hạng') }} (/ranking)</option>
                                <option value="{{ json_encode(['url' => '/bot-guide', 'title' => __('Hướng dẫn Bot hoàn tiền')]) }}">{{ __('Hướng dẫn Bot hoàn tiền') }} (/bot-guide)</option>
                                <option value="{{ json_encode(['url' => '/login', 'title' => __('Đăng nhập')]) }}">{{ __('Đăng nhập') }} (/login)</option>
                                <option value="{{ json_encode(['url' => '/register', 'title' => __('Đăng ký')]) }}">{{ __('Đăng ký') }} (/register)</option>
                                <option value="{{ json_encode(['url' => '/forgot-password', 'title' => __('Quên mật khẩu')]) }}">{{ __('Quên mật khẩu') }} (/forgot-password)</option>
                                <option value="{{ json_encode(['url' => '/dashboard', 'title' => __('Tổng quan thành viên')]) }}">{{ __('Tổng quan thành viên') }} (/dashboard)</option>
                                <option value="{{ json_encode(['url' => '/dashboard/cashback', 'title' => __('Lịch sử hoàn tiền')]) }}">{{ __('Lịch sử hoàn tiền') }} (/dashboard/cashback)</option>
                                <option value="{{ json_encode(['url' => '/dashboard/saved-products', 'title' => __('Sản phẩm đã lưu')]) }}">{{ __('Sản phẩm đã lưu') }} (/dashboard/saved-products)</option>
                                <option value="{{ json_encode(['url' => '/dashboard/checkin', 'title' => __('Điểm danh nhận quà')]) }}">{{ __('Điểm danh nhận quà') }} (/dashboard/checkin)</option>
                                <option value="{{ json_encode(['url' => '/dashboard/withdraw', 'title' => __('Yêu cầu rút tiền')]) }}">{{ __('Yêu cầu rút tiền') }} (/dashboard/withdraw)</option>
                                <option value="{{ json_encode(['url' => '/dashboard/gifts', 'title' => __('Đổi quà tặng')]) }}">{{ __('Đổi quà tặng') }} (/dashboard/gifts)</option>
                                <option value="{{ json_encode(['url' => '/dashboard/giftcode', 'title' => __('Nhập Giftcode')]) }}">{{ __('Nhập Giftcode') }} (/dashboard/giftcode)</option>
                                <option value="{{ json_encode(['url' => '/dashboard/referrals', 'title' => __('Giới thiệu bạn bè')]) }}">{{ __('Giới thiệu bạn bè') }} (/dashboard/referrals)</option>
                                <option value="{{ json_encode(['url' => '/dashboard/tasks', 'title' => __('Nhiệm vụ nhận thưởng')]) }}">{{ __('Nhiệm vụ nhận thưởng') }} (/dashboard/tasks)</option>
                                <option value="{{ json_encode(['url' => '/dashboard/profile', 'title' => __('Thông tin cá nhân')]) }}">{{ __('Thông tin cá nhân') }} (/dashboard/profile)</option>
                                <option value="{{ json_encode(['url' => '/dashboard/logs', 'title' => __('Nhật ký hoạt động')]) }}">{{ __('Nhật ký hoạt động') }} (/dashboard/logs)</option>
                                <option value="{{ json_encode(['url' => '/dashboard/balance-logs', 'title' => __('Biến động số dư')]) }}">{{ __('Biến động số dư') }} (/dashboard/balance-logs)</option>
                                <option value="{{ json_encode(['url' => '/dashboard/notifications', 'title' => __('Thông báo thành viên')]) }}">{{ __('Thông báo thành viên') }} (/dashboard/notifications)</option>
                            </optgroup>

                            @if(isset($staticPages) && $staticPages->count() > 0)
                                <optgroup label="{{ __('Trang Tĩnh') }}">
                                    @foreach($staticPages as $page)
                                        <option value="{{ json_encode(['url' => '/page/' . $page->slug, 'title' => $page->title]) }}">{{ $page->title }} (/page/{{ $page->slug }})</option>
                                    @endforeach
                                </optgroup>
                            @endif

                            @if(isset($blogCategories) && $blogCategories->count() > 0)
                                <optgroup label="{{ __('Danh Mục Tin Tức') }}">
                                    @foreach($blogCategories as $cat)
                                        <option value="{{ json_encode(['url' => '/blog/category/' . $cat->slug, 'title' => $cat->name]) }}">{{ $cat->name }} (/blog/category/{{ $cat->slug }})</option>
                                    @endforeach
                                </optgroup>
                            @endif

                            @if(isset($blogPosts) && $blogPosts->count() > 0)
                                <optgroup label="{{ __('Bài Viết Tin Tức') }}">
                                    @foreach($blogPosts as $post)
                                        <option value="{{ json_encode(['url' => '/blog/' . $post->slug, 'title' => $post->title]) }}">{{ Str::limit($post->title, 40) }} (/blog/{{ $post->slug }})</option>
                                    @endforeach
                                </optgroup>
                            @endif
                        </select>
                    </div>

                    <!-- Đường dẫn liên kết (URL) -->
                    <div>
                        <label for="url" class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1">{{ __('Đường dẫn liên kết (URL)') }}</label>
                        <input type="text" 
                               name="url" 
                               id="url" 
                               x-model="editMenu.url"
                               placeholder="Ví dụ: /, /dashboard/withdraw, hoặc link ngoài"
                               class="block w-full px-4 py-2 border border-gray-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-gray-50/50 text-gray-800 font-mono">
                        <p class="text-[10px] text-gray-400 mt-1 leading-normal">
                            {{ __('Nếu liên kết nội bộ, nhập bắt đầu bằng dấu gạch chéo /. Nếu là link ngoài, nhập đầy đủ https://') }}
                        </p>
                    </div>

                    <!-- Icon của Menu (Lucide Icons) -->
                    <div>
                        <label for="icon" class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1">{{ __('Tên Icon Lucide (Tùy chọn)') }}</label>
                        <div class="flex gap-2">
                            <div class="relative flex-1">
                                <input type="text" 
                                       name="icon" 
                                       id="icon" 
                                       x-model="editMenu.icon"
                                       placeholder="Ví dụ: home, user, settings, wallet..."
                                       class="block w-full pl-9 pr-4 py-2 border border-gray-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-gray-50/50 text-gray-800 font-mono">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                                    <i :data-lucide="editMenu.icon || 'link'" class="w-3.5 h-3.5"></i>
                                </div>
                            </div>
                            <button type="button" 
                                    @click="$dispatch('open-icon-picker', { target: 'icon' })" 
                                    class="px-3 py-2 bg-gray-100 hover:bg-gray-200 dark:bg-slate-800 dark:hover:bg-slate-700/80 text-gray-700 dark:text-slate-300 text-xs font-semibold rounded-xl transition-all flex items-center gap-1 border border-gray-200 dark:border-slate-700/50">
                                <i data-lucide="grid" class="w-3.5 h-3.5 text-gray-500"></i>
                                {{ __('Chọn') }}
                            </button>
                        </div>
                        <p class="text-[10px] text-gray-400 mt-1 leading-normal">
                            {!! __('Nhập mã icon hợp lệ của <a href="https://lucide.dev" target="_blank" class="text-shopee underline font-medium">Lucide Icons</a>. Hoặc nhấn <strong>Chọn</strong> để tìm kiếm nhanh.') !!}
                        </p>
                    </div>

                    <!-- Menu cha (Hỗ trợ cấu trúc 2 tầng) -->
                    <div>
                        <label for="parent_id" class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1">{{ __('Menu Cha (Cấp trên)') }}</label>
                        <select name="parent_id" 
                                id="parent_id" 
                                x-model="editMenu.parent_id"
                                class="block w-full px-4 py-2 border border-gray-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-gray-50/50 text-gray-700">
                            <option value="">{{ __('Không có (Mục gốc cấp 1)') }}</option>
                            @foreach($parentMenus as $parent)
                                <!-- Ẩn mục này nếu đang ở chế độ edit để tránh chọn chính mình -->
                                <template x-if="editMenu.id != '{{ $parent->id }}'">
                                    <option value="{{ $parent->id }}">{{ $parent->title }} ({{ strtoupper($parent->position) }})</option>
                                </template>
                            @endforeach
                        </select>
                    </div>

                    <!-- Vị trí hiển thị (Gắn vị trí hiển thị menu theo ý thích) -->
                    <div class="space-y-2">
                        <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1">{{ __('Vị trí hiển thị Menu') }} <span class="text-red-500">*</span></label>
                        
                        <div class="grid grid-cols-5 gap-2">
                            <!-- Vị trí Header -->
                            <label class="flex flex-col items-center justify-center p-2 border border-gray-200 rounded-xl cursor-pointer hover:bg-gray-50 transition-all text-center" :class="{ 'border-shopee bg-shopee/5': positionType === 'header' }">
                                <input type="radio" x-model="positionType" value="header" class="sr-only">
                                <span class="text-[9px] font-bold text-gray-700">{{ __('HEADER') }}</span>
                                <span class="text-[7px] text-gray-400 mt-0.5">{{ __('Đầu trang') }}</span>
                            </label>

                            <!-- Vị trí Footer -->
                            <label class="flex flex-col items-center justify-center p-2 border border-gray-200 rounded-xl cursor-pointer hover:bg-gray-50 transition-all text-center" :class="{ 'border-shopee bg-shopee/5': positionType === 'footer' }">
                                <input type="radio" x-model="positionType" value="footer" class="sr-only">
                                <span class="text-[9px] font-bold text-gray-700">{{ __('FOOTER') }}</span>
                                <span class="text-[7px] text-gray-400 mt-0.5">{{ __('Chân trang') }}</span>
                            </label>

                            <!-- Vị trí User Dropdown -->
                            <label class="flex flex-col items-center justify-center p-2 border border-gray-200 rounded-xl cursor-pointer hover:bg-gray-50 transition-all text-center" :class="{ 'border-shopee bg-shopee/5': positionType === 'user_dropdown' }">
                                <input type="radio" x-model="positionType" value="user_dropdown" class="sr-only">
                                <span class="text-[9px] font-bold text-gray-700">{{ __('DROPDOWN') }}</span>
                                <span class="text-[7px] text-gray-400 mt-0.5">{{ __('Cá nhân') }}</span>
                            </label>

                            <!-- Vị trí Bottom Menu (Di động đáy) -->
                            <label class="flex flex-col items-center justify-center p-2 border border-gray-200 rounded-xl cursor-pointer hover:bg-gray-50 transition-all text-center" :class="{ 'border-shopee bg-shopee/5': positionType === 'bottom' }">
                                <input type="radio" x-model="positionType" value="bottom" class="sr-only">
                                <span class="text-[9px] font-bold text-gray-700">{{ __('BOTTOM') }}</span>
                                <span class="text-[7px] text-gray-400 mt-0.5">{{ __('Menu đáy') }}</span>
                            </label>

                            <!-- Tùy biến khác -->
                            <label class="flex flex-col items-center justify-center p-2 border border-gray-200 rounded-xl cursor-pointer hover:bg-gray-50 transition-all text-center" :class="{ 'border-shopee bg-shopee/5': positionType === 'custom' }">
                                <input type="radio" x-model="positionType" value="custom" class="sr-only">
                                <span class="text-[9px] font-bold text-gray-700">{{ __('TÙY CHỌN') }}</span>
                                <span class="text-[7px] text-gray-400 mt-0.5">{{ __('Tự gõ') }}</span>
                            </label>
                        </div>

                        <!-- Ô nhập vị trí tùy biến nếu chọn custom -->
                        <div x-show="positionType === 'custom'" x-cloak x-transition class="pt-1.5">
                            <input type="text" 
                                   name="position" 
                                   x-model="customPosition"
                                   placeholder="Ví dụ: user_sidebar, top_bar..."
                                   class="block w-full px-4 py-2 border border-gray-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-gray-50/50 text-gray-800 uppercase font-mono">
                            <p class="text-[9px] text-gray-400 mt-1 leading-normal">
                                {{ __('Nhập tên vị trí dạng viết liền không dấu, ví dụ: my_custom_menu. Sử dụng tên này ở giao diện code để lấy ra menu tương ứng.') }}
                            </p>
                        </div>
                        <!-- Trường ẩn gửi giá trị vị trí thực tế lên server -->
                        <input type="hidden" name="position" :value="getSubmitPosition()">
                    </div>

                    <!-- Cách thức mở liên kết (Target) -->
                    <div>
                        <label for="target" class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1">{{ __('Cách thức mở liên kết') }} <span class="text-red-500">*</span></label>
                        <select name="target" 
                                id="target" 
                                required 
                                x-model="editMenu.target"
                                class="block w-full px-4 py-2 border border-gray-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-gray-50/50 text-gray-700">
                            <option value="_self">{{ __('Mở tại trang hiện tại (_self)') }}</option>
                            <option value="_blank">{{ __('Mở tab mới (_blank)') }}</option>
                        </select>
                    </div>

                    <!-- Đối tượng hiển thị (auth_rule) -->
                    <div>
                        <label for="auth_rule" class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1">{{ __('Hiển thị với đối tượng') }} <span class="text-red-500">*</span></label>
                        <select name="auth_rule" 
                                id="auth_rule" 
                                required 
                                x-model="editMenu.auth_rule"
                                class="block w-full px-4 py-2 border border-gray-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-gray-50/50 text-gray-700">
                            <option value="all">{{ __('Tất cả mọi người') }}</option>
                            <option value="auth">{{ __('Chỉ thành viên đã đăng nhập') }}</option>
                            <option value="guest">{{ __('Chỉ khách chưa đăng nhập') }}</option>
                        </select>
                    </div>

                    <!-- Thứ tự sắp xếp hiển thị -->
                    <div>
                        <label for="order" class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1">{{ __('Thứ tự sắp xếp') }} <span class="text-red-500">*</span></label>
                        <input type="number" 
                               name="order" 
                               id="order" 
                               required 
                               min="0"
                               x-model="editMenu.order"
                               class="block w-full px-4 py-2 border border-gray-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-gray-50/50 text-gray-800">
                    </div>

                    <!-- Trạng thái hoạt động (Checkbox) -->
                    <div class="pt-1">
                        <label class="flex items-center gap-2 cursor-pointer select-none">
                            <input type="checkbox" 
                                   name="status" 
                                   value="1" 
                                   x-model="editMenu.status"
                                   class="h-4.5 w-4.5 text-shopee focus:ring-shopee border-gray-300 rounded-lg">
                            <span class="text-xs font-semibold text-gray-700">{{ __('Kích hoạt menu hiển thị') }}</span>
                        </label>
                        <!-- Lưu giá trị checkbox 0 khi tắt status -->
                        <input type="hidden" name="status" :value="editMenu.status ? 1 : 0">
                    </div>

                    <!-- Nút Submit lưu dữ liệu -->
                    <button type="submit" 
                            class="w-full inline-flex items-center justify-center gap-2 px-5 py-2.5 text-xs font-semibold text-white bg-shopee hover:bg-shopee-dark rounded-xl transition-all shadow-md mt-2">
                        <i data-lucide="save" class="w-4 h-4"></i>
                        <span x-text="isEditMode ? '{{ __('Cập nhật Menu') }}' : '{{ __('Thêm Menu') }}'"></span>
                    </button>
                </form>
            </div>
        </div>
    </div>


</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        // Khởi tạo tính năng kéo thả sắp xếp (SortableJS) cho từng bảng menu riêng biệt.
        // Việc khởi tạo độc lập giúp admin sắp xếp thứ tự hiển thị của các liên kết trong nội bộ từng vị trí mà không bị ảnh hưởng chéo sang các vị trí khác.
        document.querySelectorAll('.menu-sortable-list').forEach(el => {
            new Sortable(el, {
                handle: '.handle', // Kéo thả qua icon có class handle
                animation: 150,
                ghostClass: 'bg-orange-50/50', // Hiệu ứng làm mờ hàng đang kéo
                onEnd: function (evt) {
                    const rows = el.querySelectorAll('tr[data-id]');
                    const orders = [];
                    rows.forEach((row, index) => {
                        orders.push({
                            id: row.getAttribute('data-id'),
                            order: index + 1
                        });
                    });

                    // Gửi AJAX lưu thứ tự mới của vị trí hiện tại lên server
                    fetch('{{ route("admin.menus.update_order") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({ orders: orders })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            window.dispatchEvent(new CustomEvent('toast', {
                                detail: {
                                    text: '{{ __("Cập nhật vị trí menu thành công!") }}',
                                    type: 'success'
                                }
                            }));
                        } else {
                            window.dispatchEvent(new CustomEvent('toast', {
                                detail: {
                                    text: data.message || '{{ __("Đã xảy ra lỗi khi cập nhật vị trí menu.") }}',
                                    type: 'error'
                                }
                            }));
                        }
                    })
                    .catch(error => {
                        console.error('Error updating menu order:', error);
                        window.dispatchEvent(new CustomEvent('toast', {
                            detail: {
                                text: '{{ __("Không thể kết nối tới máy chủ.") }}',
                                type: 'error'
                              }
                        }));
                    });
                }
            });
        });

        // Bắt sự kiện thay đổi trạng thái nhanh bằng checkbox toggle
        document.querySelectorAll('.toggle-status-btn').forEach(btn => {
            btn.addEventListener('change', function () {
                const menuId = this.getAttribute('data-id');
                const isChecked = this.checked;
                
                fetch(`/${window.adminPrefix}/menus/${menuId}/toggle-status`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        window.dispatchEvent(new CustomEvent('toast', {
                            detail: {
                                text: data.message,
                                type: 'success'
                            }
                        }));
                    } else {
                        // Reset lại checkbox nếu thất bại
                        this.checked = !isChecked;
                        window.dispatchEvent(new CustomEvent('toast', {
                            detail: {
                                text: data.message || '{{ __("Lỗi khi cập nhật trạng thái menu.") }}',
                                type: 'error'
                            }
                        }));
                    }
                })
                .catch(error => {
                    console.error('Error toggling menu status:', error);
                    this.checked = !isChecked;
                    window.dispatchEvent(new CustomEvent('toast', {
                        detail: {
                            text: '{{ __("Không thể kết nối tới máy chủ.") }}',
                            type: 'error'
                        }
                    }));
                });
            });
        });
    });
</script>
@endsection
