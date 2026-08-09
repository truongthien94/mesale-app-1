@extends('layouts.admin')

@section('title', __('Quản Lý Đa Ngôn Ngữ') . ' - ' . $siteName)

@section('content')
<div class="space-y-6" x-data="{ 
    isEditMode: false, 
    editLanguage: { id: '', name: '', code: '', flag: '', order: 0, is_active: true, is_default: false },
    actionUrl: '{{ route('admin.languages.store') }}',
    setEditMode(lang) {
        this.isEditMode = true;
        this.editLanguage = { ...lang };
        this.actionUrl = '/' + window.adminPrefix + '/languages/' + lang.id;
    },
    setCreateMode() {
        this.isEditMode = false;
        this.editLanguage = { id: '', name: '', code: '', flag: '', order: 0, is_active: true, is_default: false };
        this.actionUrl = '{{ route('admin.languages.store') }}';
    }
}">
    <!-- Tiêu đề -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ __('Quản Lý Ngôn Ngữ') }}</h1>
            <p class="text-sm text-gray-500">{{ __('Cấu hình các ngôn ngữ hiển thị và bản dịch trên hệ thống') }}</p>
        </div>
    </div>

    <!-- Layout Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
        
        <!-- Danh sách ngôn ngữ (Cột trái) -->
        <div class="lg:col-span-7 bg-white p-6 rounded-3xl shadow-sm border border-gray-200 space-y-4">
            <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                <h3 class="font-bold text-gray-900 text-sm">{{ __('Các ngôn ngữ đang hoạt động') }}</h3>
                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-shopee/10 text-shopee">
                    {{ $languages->count() }} {{ __('Ngôn ngữ') }}
                </span>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse whitespace-nowrap">
                    <thead>
                        <tr class="border-b border-gray-100 text-gray-400 text-[10px] font-bold uppercase tracking-wider">
                            <th class="py-3 px-2">{{ __('Lá cờ') }}</th>
                            <th class="py-3 px-2">{{ __('Ngôn ngữ') }}</th>
                            <th class="py-3 px-2">{{ __('Mã Code') }}</th>
                            <th class="py-3 px-2 text-center">{{ __('Thứ tự') }}</th>
                            <th class="py-3 px-2 text-center">{{ __('Trạng thái') }}</th>
                            <th class="py-3 px-2 text-right">{{ __('Hành động') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50 text-xs">
                        @forelse($languages as $lang)
                            <tr class="hover:bg-gray-50/50 transition-colors">
                                <!-- Lá cờ -->
                                <td class="py-3.5 px-2">
                                    <div class="w-8 h-5 rounded overflow-hidden shadow-sm border border-gray-100 bg-gray-50 flex items-center justify-center shrink-0">
                                        @if($lang->flag)
                                            <img src="{{ $lang->flag }}" alt="{{ $lang->name }}" class="w-full h-full object-cover">
                                        @else
                                            <span class="text-xs font-bold text-gray-400">{{ strtoupper($lang->code) }}</span>
                                        @endif
                                    </div>
                                </td>
                                <!-- Tên ngôn ngữ -->
                                <td class="py-3.5 px-2 font-semibold text-gray-900">
                                    <div class="flex items-center gap-1.5">
                                        <span>{{ $lang->name }}</span>
                                        @if($lang->is_default)
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[8px] font-extrabold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                {{ __('Mặc định') }}
                                            </span>
                                        @endif
                                    </div>
                                </td>
                                <!-- Mã Code -->
                                <td class="py-3.5 px-2 font-mono text-gray-500 uppercase">{{ $lang->code }}</td>
                                <!-- Thứ tự -->
                                <td class="py-3.5 px-2 text-center font-medium text-gray-600">{{ $lang->order }}</td>
                                <!-- Trạng thái -->
                                <td class="py-3.5 px-2 text-center">
                                    <span class="inline-flex px-2 py-0.5 rounded-full text-[9px] font-bold {{ $lang->is_active ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-700' }}">
                                        {{ $lang->is_active ? __('Đang bật') : __('Tạm ẩn') }}
                                    </span>
                                </td>
                                <!-- Hành động -->
                                <td class="py-3.5 px-2 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <!-- Nút Bản dịch -->
                                        <a href="{{ route('admin.languages.translations', $lang->id) }}" 
                                           class="p-1.5 bg-purple-50 hover:bg-purple-100 text-purple-600 rounded-lg transition-all" 
                                           title="{{ __('Quản lý bản dịch') }}">
                                            <i data-lucide="languages" class="w-3.5 h-3.5"></i>
                                        </a>

                                        <!-- Nút Sửa (Alpine kích hoạt chế độ Edit) -->
                                        <button type="button" 
                                                @click="setEditMode({
                                                    id: '{{ $lang->id }}',
                                                    name: '{{ $lang->name }}',
                                                    code: '{{ $lang->code }}',
                                                    flag: '{{ $lang->flag }}',
                                                    order: '{{ $lang->order }}',
                                                    is_active: {{ $lang->is_active ? 'true' : 'false' }},
                                                    is_default: {{ $lang->is_default ? 'true' : 'false' }}
                                                })"
                                                class="p-1.5 bg-blue-50 hover:bg-blue-100 text-blue-600 rounded-lg transition-all" 
                                                title="{{ __('Sửa ngôn ngữ') }}">
                                            <i data-lucide="pencil" class="w-3.5 h-3.5"></i>
                                        </button>

                                        <!-- Nút Xóa (Bảo vệ không xóa ngôn ngữ mặc định) -->
                                        @if(!$lang->is_default)
                                            <form action="{{ route('admin.languages.destroy', $lang->id) }}" method="POST" onsubmit="return confirm('Bạn có chắc chắn muốn xoá ngôn ngữ này? Thao tác này cũng sẽ xoá tệp dịch tương ứng!')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="p-1.5 bg-red-50 hover:bg-red-100 text-red-600 rounded-lg transition-all" title="{{ __('Xoá ngôn ngữ') }}">
                                                    <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                                </button>
                                            </form>
                                        @else
                                            <button class="p-1.5 bg-gray-50 text-gray-300 rounded-lg cursor-not-allowed" disabled title="{{ __('Không thể xoá ngôn ngữ mặc định') }}">
                                                <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-8 text-center text-gray-400">{{ __('Chưa có ngôn ngữ nào được định nghĩa.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Cột Phải: Form Thêm / Sửa Ngôn Ngữ -->
        <div class="lg:col-span-5 bg-white p-6 rounded-3xl shadow-sm border border-gray-200 space-y-4">
            
            <!-- Header của Form (Động) -->
            <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                <h3 class="font-bold text-gray-900 text-sm" x-text="isEditMode ? '{{ __('Chỉnh sửa ngôn ngữ') }}' : '{{ __('Thêm ngôn ngữ mới') }}'"></h3>
                <button type="button" 
                        x-show="isEditMode" 
                        @click="setCreateMode()" 
                        class="text-[10px] text-gray-400 hover:text-shopee flex items-center gap-1"
                        x-cloak>
                    <i data-lucide="plus-circle" class="w-3.5 h-3.5"></i>
                    {{ __('Chuyển về Thêm mới') }}
                </button>
            </div>

            <!-- Form xử lý -->
            <form :action="actionUrl" method="POST" enctype="multipart/form-data" class="space-y-4">
                @csrf
                <!-- Directive động của Laravel Method -->
                <template x-if="isEditMode">
                    <input type="hidden" name="_method" value="PUT">
                </template>

                <!-- Tên Ngôn Ngữ -->
                <div>
                    <label for="name" class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1">{{ __('Tên Ngôn Ngữ') }} <span class="text-red-500">*</span></label>
                    <input type="text" 
                           name="name" 
                           id="name" 
                           required 
                           x-model="editLanguage.name"
                           placeholder="Ví dụ: Tiếng Việt, English..."
                           class="block w-full px-4 py-2 border border-gray-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-gray-50/50 text-gray-800">
                </div>

                <!-- Mã Code Ngôn Ngữ -->
                <div>
                    <label for="code" class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1">{{ __('Mã Code Ngôn Ngữ (ISO)') }} <span class="text-red-500">*</span></label>
                    <input type="text" 
                           name="code" 
                           id="code" 
                           required 
                           x-model="editLanguage.code"
                           placeholder="Ví dụ: vi, en, zh, ja..."
                           class="block w-full px-4 py-2 border border-gray-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-gray-50/50 font-mono text-gray-800">
                    <p class="text-[10px] text-gray-400 mt-1 leading-relaxed">{{ __('Mã code viết thường. Hệ thống sẽ tự động tạo tệp dịch json tương ứng trong thư mục lang/ (Ví dụ: lang/en.json).') }}</p>
                </div>

                <!-- Lá cờ đại diện -->
                <div>
                    <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-2">{{ __('Ảnh lá cờ đại diện') }}</label>
                    
                    <!-- Preview Flag bằng AlpineJS và CKFinder -->
                    <div class="w-32 aspect-[8/5] bg-gray-50 dark:bg-slate-950 border border-dashed border-gray-200 dark:border-slate-800 rounded-xl overflow-hidden flex items-center justify-center relative mb-3 mx-auto">
                        <template x-if="editLanguage.flag">
                            <div class="w-full h-full relative group">
                                <img :src="editLanguage.flag" class="w-full h-full object-cover">
                                <button type="button" @click="editLanguage.flag = ''; document.getElementById('flag-file-input').value = ''" class="absolute top-1 right-1 bg-red-500 hover:bg-red-600 text-white rounded-full p-1 shadow-md opacity-0 group-hover:opacity-100 transition-opacity">
                                    <i data-lucide="trash-2" class="w-3 h-3"></i>
                                </button>
                            </div>
                        </template>
                        <template x-if="!editLanguage.flag">
                            <div class="text-center text-gray-400">
                                <i data-lucide="image" class="w-5 h-5 mx-auto mb-1 text-gray-300"></i>
                                <span class="text-[9px]">{{ __('Chưa chọn ảnh') }}</span>
                            </div>
                        </template>
                    </div>

                    <!-- Lưu đường dẫn ảnh khi chọn từ thư viện -->
                    <input type="hidden" name="flag" id="flag-url-input" x-model="editLanguage.flag">

                    <div class="grid grid-cols-2 gap-2">
                        <!-- Chọn file từ máy tính -->
                        <div>
                            <input type="file" 
                                   name="flag_file" 
                                   id="flag-file-input"
                                   @change="
                                        const file = $event.target.files[0];
                                        if (file) {
                                            editLanguage.flag = URL.createObjectURL(file);
                                        }
                                   "
                                   class="hidden">
                            <label for="flag-file-input" class="w-full flex items-center justify-center gap-1 px-2 py-1.5 text-[10px] font-bold text-gray-700 bg-gray-50 border border-gray-200 hover:bg-gray-100 rounded-xl cursor-pointer transition-all dark:text-slate-350">
                                <i data-lucide="upload" class="w-3 h-3"></i> {{ __('Tải lên file') }}
                            </label>
                        </div>
                        
                        <!-- Chọn từ thư viện elFinder -->
                        <button type="button" 
                                @click="openElfinderPopup('flag-url-input')"
                                class="w-full flex items-center justify-center gap-1 px-2 py-1.5 text-[10px] font-bold text-shopee bg-shopee/10 border border-shopee/20 hover:bg-shopee/20 rounded-xl transition-all">
                            <i data-lucide="folder-open" class="w-3 h-3"></i> {{ __('Thư viện') }}
                        </button>
                    </div>
                </div>

                <!-- Thứ tự sắp xếp hiển thị -->
                <div>
                    <label for="order" class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1">{{ __('Thứ tự sắp xếp') }}</label>
                    <input type="number" 
                           name="order" 
                           id="order" 
                           x-model="editLanguage.order"
                           class="block w-full px-4 py-2 border border-gray-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-gray-50/50 text-gray-800">
                </div>

                <!-- Checkbox Trạng Thái Hoạt Động -->
                <div class="pt-1">
                    <label class="flex items-center gap-2 cursor-pointer select-none">
                        <input type="checkbox" 
                               name="is_active" 
                               value="1" 
                               x-model="editLanguage.is_active"
                               :disabled="editLanguage.is_default"
                               class="h-4.5 w-4.5 text-shopee focus:ring-shopee border-gray-300 rounded-lg">
                        <span class="text-xs font-semibold text-gray-700" :class="editLanguage.is_default ? 'text-gray-400' : ''">{{ __('Kích hoạt hoạt động trên hệ thống') }}</span>
                    </label>
                    <span x-show="editLanguage.is_default" class="text-[10px] text-gray-400 block mt-1 leading-normal" x-cloak>{{ __('Không thể vô hiệu hóa ngôn ngữ mặc định.') }}</span>
                </div>

                <!-- Checkbox Ngôn Ngữ Mặc Định -->
                <div class="pt-1">
                    <label class="flex items-center gap-2 cursor-pointer select-none">
                        <input type="checkbox" 
                               name="is_default" 
                               value="1" 
                               x-model="editLanguage.is_default"
                               :disabled="editLanguage.is_default"
                               class="h-4.5 w-4.5 text-shopee focus:ring-shopee border-gray-300 rounded-lg">
                        <span class="text-xs font-semibold text-gray-700" :class="editLanguage.is_default ? 'text-gray-400' : ''">{{ __('Đặt làm ngôn ngữ mặc định hệ thống') }}</span>
                    </label>
                    <span x-show="editLanguage.is_default" class="text-[10px] text-gray-400 block mt-1 leading-normal" x-cloak>{{ __('Đây đã là ngôn ngữ mặc định.') }}</span>
                </div>

                <!-- Nút Submit (Động) -->
                <button type="submit" 
                        class="w-full inline-flex items-center justify-center gap-2 px-5 py-2.5 text-xs font-semibold text-white bg-shopee hover:bg-shopee-dark rounded-xl transition-all shadow-md mt-2">
                    <i data-lucide="save" class="w-4 h-4"></i>
                    <span x-text="isEditMode ? '{{ __('Lưu thay đổi') }}' : '{{ __('Thêm ngôn ngữ') }}'"></span>
                </button>
            </form>
        </div>

    </div>
</div>
@endsection

@section('scripts')
<script>
    // Hàm mở elFinder dạng popup window
    function openElfinderPopup(inputId) {
        var width = 900;
        var height = 600;
        var left = (screen.width - width) / 2;
        var top = (screen.height - height) / 2;
        var url = '{{ url("elfinder/popup") }}/' + inputId;
        window.open(url, 'elfinderPicker', 'width=' + width + ',height=' + height + ',left=' + left + ',top=' + top + ',resizable=yes,scrollbars=yes,status=no');
    }

    // Hàm callback toàn cục được gọi từ elFinder standalonepopup
    window.processSelectedFile = function(fileUrl, inputId) {
        var inputElement = document.getElementById(inputId);
        if (inputElement) {
            inputElement.value = fileUrl;
            // Gửi sự kiện input để AlpineJS đồng bộ dữ liệu vào biến x-model tương ứng
            inputElement.dispatchEvent(new Event('input'));
            
            // Xóa file input nếu chọn từ thư viện
            if (inputId === 'flag-url-input') {
                var fileInput = document.getElementById('flag-file-input');
                if (fileInput) fileInput.value = '';
            }
        }
    };
</script>
@endsection
