@extends('layouts.admin')

@section('title', __('Quản Lý Banner Quảng Cáo - Admin Panel'))



@section('content')
{{-- Quản lý trạng thái modal edit bằng AlpineJS ở cấp trang --}}
<div class="space-y-6" x-data="{
    editModal: false,
    editId: null,
    editTitle: '',
    editImageUrl: '',
    editLink: '',
    editOrder: 0,
    editIsActive: true,
    editPreview: '',

    {{-- Hàm mở modal và nạp dữ liệu banner cần chỉnh sửa --}}
    openEdit(banner) {
        this.editId = banner.id;
        this.editTitle = banner.title || '';
        this.editImageUrl = banner.image_url || '';
        this.editLink = banner.link || '';
        this.editOrder = banner.order || 0;
        this.editIsActive = banner.is_active == 1;
        this.editPreview = banner.image_url || '';
        this.editModal = true;
    }
}">
    <!-- Tiêu đề trang -->
    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ __('Banner Quảng Cáo') }}</h1>
        <p class="text-sm text-gray-500">{{ __('Quản lý các slide banner hiển thị ở trang chủ') }}</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">

        <!-- Danh sách banner (Left) -->
        <div class="lg:col-span-7 bg-white dark:bg-slate-900 p-6 rounded-3xl shadow-sm border border-gray-200 dark:border-slate-800 space-y-4">
            <h3 class="font-bold text-gray-900 dark:text-white text-sm border-b border-gray-100 dark:border-slate-800 pb-3">{{ __('Danh sách banner đang chạy') }}</h3>

            <div class="space-y-4">
                @forelse($banners as $banner)
                    <div class="p-4 bg-gray-50 dark:bg-slate-950 border border-gray-100 dark:border-slate-800 rounded-2xl flex flex-col sm:flex-row gap-4 items-center justify-between">
                        <div class="flex items-center gap-4 w-full sm:w-auto">
                            <!-- Banner Image Preview -->
                            <div class="w-24 h-16 rounded-lg overflow-hidden shrink-0 bg-gray-200 dark:bg-slate-800 border dark:border-slate-700">
                                <img src="{{ $banner->image_url }}" alt="" class="w-full h-full object-cover">
                            </div>
                            <div class="truncate">
                                <h4 class="font-bold text-gray-900 dark:text-white text-xs truncate max-w-[200px]">{{ $banner->title ?: __('Chưa có tiêu đề') }}</h4>
                                @if($banner->link)
                                    <a href="{{ $banner->link }}" target="_blank" class="text-[10px] text-shopee hover:underline truncate block max-w-[200px]">Link: {{ $banner->link }}</a>
                                @endif
                                <div class="flex items-center gap-2 mt-1">
                                    <span class="inline-flex px-1.5 py-0.5 rounded text-[8px] font-bold {{ $banner->is_active ? 'bg-green-50 dark:bg-green-950/30 text-green-700 dark:text-green-400' : 'bg-gray-100 dark:bg-slate-800 text-gray-400 dark:text-slate-500' }}">
                                        {{ $banner->is_active ? __('Đang hiển thị') : __('Tạm ẩn') }}
                                    </span>
                                    <span class="text-[8px] text-gray-400 dark:text-slate-500">{{ __('Thứ tự:') }} {{ $banner->order }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Nút hành động -->
                        <div class="shrink-0 flex items-center gap-2">
                            <!-- Nút chỉnh sửa → Mở modal -->
                            <button type="button"
                                    @click="openEdit({{ json_encode([
                                        'id' => $banner->id,
                                        'title' => $banner->title,
                                        'image_url' => $banner->image_url,
                                        'link' => $banner->link,
                                        'order' => $banner->order,
                                        'is_active' => $banner->is_active,
                                    ]) }})"
                                    class="p-2 bg-blue-50 dark:bg-blue-950/30 hover:bg-blue-100 dark:hover:bg-blue-900/40 text-blue-600 dark:text-blue-400 rounded-xl transition-all"
                                    title="{{ __('Chỉnh sửa banner') }}">
                                <i data-lucide="pencil" class="w-4 h-4 pointer-events-none"></i>
                            </button>
                            <!-- Nút xoá -->
                            <form action="{{ route('admin.banners.destroy', $banner->id) }}" method="POST" onsubmit="return confirm('{{ __('Bạn có chắc chắn muốn xoá banner này?') }}')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="p-2 bg-red-50 dark:bg-red-950/30 hover:bg-red-100 dark:hover:bg-red-900/40 text-red-600 dark:text-red-400 rounded-xl transition-all" title="{{ __('Xoá banner') }}">
                                    <i data-lucide="trash-2" class="w-4 h-4 pointer-events-none"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                @empty
                    <p class="text-xs text-gray-400 text-center py-8">{{ __('Chưa có banner nào được tạo.') }}</p>
                @endforelse
            </div>
        </div>

        <!-- Form thêm banner mới (Right) -->
        <div class="lg:col-span-5 bg-white dark:bg-slate-900 p-6 rounded-3xl shadow-sm border border-gray-200 dark:border-slate-800 space-y-4">
            <h3 class="font-bold text-gray-900 dark:text-white text-sm border-b border-gray-100 dark:border-slate-800 pb-3">{{ __('Thêm banner mới') }}</h3>

            <form action="{{ route('admin.banners.store') }}" method="POST" class="space-y-4"
                  x-data="{
                      createPreview: null,
                      createImageUrl: ''
                  }">
                @csrf

                <!-- Tiêu đề banner -->
                <div>
                    <label for="create-title" class="block text-xs font-bold text-gray-700 dark:text-slate-350 uppercase tracking-wider mb-1">{{ __('Tiêu đề banner') }}</label>
                    <input type="text"
                           name="title"
                           id="create-title"
                           placeholder="{{ __('Nhập tiêu đề quảng cáo...') }}"
                           class="block w-full px-4 py-2 border border-gray-200 dark:border-slate-700 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-gray-50/50 dark:bg-slate-950 dark:text-white">
                </div>

                <!-- Preview ảnh khi nhập URL hoặc chọn từ thư viện -->
                <div class="w-full aspect-[16/6] bg-gray-50 dark:bg-slate-950 border border-dashed border-gray-200 dark:border-slate-700 rounded-xl overflow-hidden flex items-center justify-center relative">
                    <template x-if="createPreview">
                        <div class="w-full h-full relative group">
                            <img :src="createPreview" class="w-full h-full object-cover">
                            <!-- Nút xoá preview đã chọn -->
                            <button type="button" @click="createPreview = null; createImageUrl = ''" class="absolute top-2 right-2 bg-red-500 hover:bg-red-600 text-white rounded-full p-1.5 shadow-md opacity-0 group-hover:opacity-100 transition-opacity">
                                <i data-lucide="x" class="w-3.5 h-3.5 pointer-events-none"></i>
                            </button>
                        </div>
                    </template>
                    <template x-if="!createPreview">
                        <div class="text-center text-gray-400">
                            <i data-lucide="image" class="w-8 h-8 mx-auto mb-1 text-gray-300 dark:text-slate-600"></i>
                            <span class="text-xs">{{ __('Xem trước ảnh banner') }}</span>
                        </div>
                    </template>
                </div>

                <!-- Bộ chọn ảnh: Nhập URL hoặc Chọn từ thư viện CKFinder -->
                <div>
                    <label for="create-image-url" class="block text-xs font-bold text-gray-700 dark:text-slate-350 uppercase tracking-wider mb-1">{{ __('Đường dẫn ảnh (URL)') }}</label>
                    <div class="flex gap-2">
                        <input type="text"
                               name="image_url"
                               id="create-image-url"
                               x-model="createImageUrl"
                               @input="createPreview = createImageUrl"
                               required
                               placeholder="{{ __('Nhập URL hình ảnh hoặc chọn từ thư viện...') }}"
                               class="flex-1 px-4 py-2 border border-gray-200 dark:border-slate-700 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-gray-50/50 dark:bg-slate-950 dark:text-white">
                        <!-- Nút chọn ảnh từ thư viện elFinder -->
                        <button type="button"
                                @click="openElfinderPopup('create-image-url')"
                                class="shrink-0 flex items-center gap-1.5 px-3 py-2 text-xs font-bold text-shopee bg-shopee/10 border border-shopee/20 hover:bg-shopee/20 rounded-xl transition-all"
                                title="{{ __('Chọn ảnh từ thư viện') }}">
                            <i data-lucide="folder-open" class="w-3.5 h-3.5 pointer-events-none"></i>
                            {{ __('Thư viện') }}
                        </button>
                    </div>
                </div>

                <!-- Liên kết đích khi click -->
                <div>
                    <label for="create-link" class="block text-xs font-bold text-gray-700 dark:text-slate-350 uppercase tracking-wider mb-1">{{ __('Liên kết đích khi click (Tuỳ chọn)') }}</label>
                    <input type="url"
                           name="link"
                           id="create-link"
                           placeholder="{{ __('Nhập URL chuyển hướng...') }}"
                           class="block w-full px-4 py-2 border border-gray-200 dark:border-slate-700 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-gray-50/50 dark:bg-slate-950 dark:text-white">
                </div>

                <!-- Thứ tự hiển thị -->
                <div>
                    <label for="create-order" class="block text-xs font-bold text-gray-700 dark:text-slate-350 uppercase tracking-wider mb-1">{{ __('Thứ tự hiển thị') }}</label>
                    <input type="number"
                           name="order"
                           id="create-order"
                           value="0"
                           min="0"
                           class="block w-full px-4 py-2 border border-gray-200 dark:border-slate-700 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-gray-50/50 dark:bg-slate-950 dark:text-white">
                </div>

                <!-- Trạng thái hiển thị -->
                <div>
                    <label class="flex items-center gap-2 cursor-pointer select-none">
                        <input type="checkbox" name="is_active" value="1" checked class="h-4 w-4 text-shopee focus:ring-shopee border-gray-300 dark:border-slate-600 rounded">
                        <span class="text-xs font-semibold text-gray-700 dark:text-slate-300">{{ __('Kích hoạt hiển thị ngay lập tức') }}</span>
                    </label>
                </div>

                <!-- Nút submit thêm mới -->
                <button type="submit" class="w-full inline-flex items-center justify-center gap-2 px-5 py-2.5 text-xs font-semibold text-white bg-shopee hover:bg-shopee-dark rounded-xl transition-all shadow-md">
                    <i data-lucide="plus" class="w-4 h-4 pointer-events-none"></i>
                    {{ __('Thêm Banner mới') }}
                </button>
            </form>
        </div>

    </div>

    <!-- ======================== -->
    <!-- MODAL CHỈNH SỬA BANNER  -->
    <!-- ======================== -->
    <div x-show="editModal"
         x-cloak
         class="fixed inset-0 z-[60] flex items-center justify-center p-4"
         @keydown.escape.window="editModal = false">

        <!-- Overlay nền mờ -->
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm"
             x-show="editModal"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             @click="editModal = false"></div>

        <!-- Nội dung modal -->
        <div class="relative bg-white dark:bg-slate-900 rounded-3xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto border border-gray-200 dark:border-slate-800"
             x-show="editModal"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95 translate-y-4"
             x-transition:enter-end="opacity-100 scale-100 translate-y-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100 translate-y-0"
             x-transition:leave-end="opacity-0 scale-95 translate-y-4"
             @click.stop>

            <!-- Header modal -->
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 dark:border-slate-800">
                <h3 class="text-sm font-bold text-gray-900 dark:text-white flex items-center gap-2">
                    <i data-lucide="pencil" class="w-4 h-4 text-shopee"></i>
                    {{ __('Chỉnh sửa Banner') }}
                </h3>
                <button type="button" @click="editModal = false" class="p-1.5 rounded-xl hover:bg-gray-100 dark:hover:bg-slate-800 text-gray-400 hover:text-gray-600 dark:hover:text-slate-300 transition-all">
                    <i data-lucide="x" class="w-5 h-5 pointer-events-none"></i>
                </button>
            </div>

            <!-- Form chỉnh sửa banner bên trong modal -->
            <form :action="'/' + window.adminPrefix + '/banners/' + editId" method="POST" class="p-6 space-y-5">
                @csrf
                @method('PUT')

                <!-- Preview ảnh hiện tại -->
                <div class="w-full aspect-[16/6] bg-gray-50 dark:bg-slate-950 border border-dashed border-gray-200 dark:border-slate-700 rounded-xl overflow-hidden flex items-center justify-center relative">
                    <template x-if="editPreview">
                        <img :src="editPreview" class="w-full h-full object-cover">
                    </template>
                    <template x-if="!editPreview">
                        <div class="text-center text-gray-400">
                            <i data-lucide="image" class="w-8 h-8 mx-auto mb-1 text-gray-300 dark:text-slate-600"></i>
                            <span class="text-xs">{{ __('Chưa chọn ảnh') }}</span>
                        </div>
                    </template>
                </div>

                <!-- Bộ chọn ảnh: Nhập URL hoặc Chọn từ thư viện CKFinder -->
                <div>
                    <label class="block text-xs font-bold text-gray-700 dark:text-slate-350 uppercase tracking-wider mb-1">{{ __('Đường dẫn ảnh') }}</label>
                    <div class="flex gap-2">
                        <input type="text"
                               name="image_url"
                               id="edit-image-url"
                               x-model="editImageUrl"
                               @input="editPreview = editImageUrl"
                               required
                               placeholder="{{ __('Nhập URL hình ảnh hoặc chọn từ thư viện...') }}"
                               class="flex-1 px-3 py-2 border border-gray-200 dark:border-slate-700 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-950 dark:text-white">
                        <!-- Nút chọn ảnh từ thư viện elFinder -->
                        <button type="button"
                                @click="openElfinderPopup('edit-image-url')"
                                class="shrink-0 flex items-center gap-1.5 px-3 py-2 text-xs font-bold text-shopee bg-shopee/10 border border-shopee/20 hover:bg-shopee/20 rounded-xl transition-all"
                                title="{{ __('Chọn ảnh từ thư viện') }}">
                            <i data-lucide="folder-open" class="w-3.5 h-3.5 pointer-events-none"></i>
                            {{ __('Thư viện') }}
                        </button>
                    </div>
                </div>

                <!-- Tiêu đề -->
                <div>
                    <label class="block text-xs font-bold text-gray-700 dark:text-slate-350 uppercase tracking-wider mb-1">{{ __('Tiêu đề') }}</label>
                    <input type="text"
                           name="title"
                           x-model="editTitle"
                           placeholder="{{ __('Nhập tiêu đề quảng cáo...') }}"
                           class="w-full px-3 py-2 border border-gray-200 dark:border-slate-700 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-950 dark:text-white">
                </div>

                <!-- Liên kết đích -->
                <div>
                    <label class="block text-xs font-bold text-gray-700 dark:text-slate-350 uppercase tracking-wider mb-1">{{ __('Liên kết đích (URL)') }}</label>
                    <input type="url"
                           name="link"
                           x-model="editLink"
                           placeholder="{{ __('Nhập URL chuyển hướng...') }}"
                           class="w-full px-3 py-2 border border-gray-200 dark:border-slate-700 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-950 dark:text-white">
                </div>

                <!-- Thứ tự & Trạng thái -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 dark:text-slate-350 uppercase tracking-wider mb-1">{{ __('Thứ tự') }}</label>
                        <input type="number"
                               name="order"
                               x-model="editOrder"
                               min="0"
                               class="w-full px-3 py-2 border border-gray-200 dark:border-slate-700 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-950 dark:text-white">
                    </div>
                    <div class="flex items-end pb-0.5">
                        <label class="flex items-center gap-2 cursor-pointer select-none">
                            <input type="checkbox" name="is_active" value="1" :checked="editIsActive" class="h-4 w-4 text-shopee focus:ring-shopee border-gray-300 dark:border-slate-600 rounded">
                            <span class="text-xs font-semibold text-gray-700 dark:text-slate-300">{{ __('Hiển thị') }}</span>
                        </label>
                    </div>
                </div>

                <!-- Nút hành động -->
                <div class="flex gap-3 pt-3 border-t border-gray-100 dark:border-slate-800">
                    <button type="submit" class="flex-1 flex items-center justify-center gap-1.5 px-4 py-2.5 text-xs font-bold text-white bg-shopee hover:bg-shopee-dark rounded-xl transition-all shadow-sm">
                        <i data-lucide="check" class="w-4 h-4 pointer-events-none"></i>
                        {{ __('Lưu thay đổi') }}
                    </button>
                    <button type="button" @click="editModal = false" class="px-5 py-2.5 text-xs font-bold text-gray-500 dark:text-slate-400 bg-gray-50 dark:bg-slate-800 hover:bg-gray-100 dark:hover:bg-slate-700 rounded-xl border border-gray-200 dark:border-slate-700 transition-all">
                        {{ __('Hủy') }}
                    </button>
                </div>
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
        }
    };
</script>
@endsection
