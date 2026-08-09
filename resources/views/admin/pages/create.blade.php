@extends('layouts.admin')

@section('title', __('Thêm Trang Mới') . ' - ' . $siteName)

@section('styles')
<!-- TinyMCE CDN để hỗ trợ soạn thảo nội dung trang giàu tính năng -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/6.8.2/tinymce.min.js" referrerpolicy="origin"></script>
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
        if (inputId === 'tinymce_image') {
            if (window.tinymceFilePickerCallback) {
                window.tinymceFilePickerCallback(fileUrl, { alt: 'Ảnh minh họa' });
                window.tinymceFilePickerCallback = null;
            }
            return;
        }
    };

    // Khởi tạo TinyMCE editor cho vùng nội dung trang
    document.addEventListener('DOMContentLoaded', () => {
        tinymce.init({
            selector: '#page-content-editor',
            height: 500,
            plugins: 'advlist autolink lists link image charmap preview anchor searchreplace visualblocks code fullscreen insertdatetime media table code help wordcount',
            toolbar: 'undo redo | blocks | bold italic backcolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | removeformat | image link table | code fullscreen',
            content_style: 'body { font-family:Inter,sans-serif; font-size:14px }',
            skin: document.documentElement.classList.contains('dark') ? 'oxide-dark' : 'oxide',
            content_css: document.documentElement.classList.contains('dark') ? 'dark' : 'default',
            language: 'vi',
            branding: false,
            promotion: false,
            // Tích hợp elFinder để chọn ảnh từ thư viện khi chèn ảnh vào nội dung
            file_picker_callback: function (callback, value, meta) {
                if (meta.filetype === 'image') {
                    window.tinymceFilePickerCallback = callback;
                    openElfinderPopup('tinymce_image');
                }
            }
        });
    });
</script>
@endsection

@section('content')
<div class="space-y-6">
    <!-- Breadcrumb -->
    <div class="flex items-center gap-2 text-xs md:text-sm text-gray-500">
        <a href="{{ route('admin.pages.index') }}" class="hover:text-shopee">{{ __('Trang nội dung') }}</a>
        <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
        <span class="text-gray-900 dark:text-white font-medium">{{ __('Thêm trang mới') }}</span>
    </div>

    <!-- Header -->
    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ __('Thêm Trang Mới') }}</h1>
        <p class="text-sm text-gray-500">{{ __('Tạo trang nội dung tĩnh như Điều khoản dịch vụ, Chính sách bảo mật, Giới thiệu,...') }}</p>
    </div>

    <!-- Form tạo trang -->
    <form action="{{ route('admin.pages.store') }}" method="POST" class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        @csrf
        
        <!-- Cột trái: Nội dung chính của trang -->
        <div class="lg:col-span-2 space-y-6">
            <div class="p-6 bg-white dark:bg-slate-900 border border-gray-150 dark:border-slate-800 rounded-2xl shadow-sm space-y-6">
                <!-- Tiêu đề trang -->
                <div>
                    <label class="block text-xs font-bold text-gray-700 dark:text-slate-350 uppercase tracking-wider mb-2">{{ __('Tiêu đề trang') }} *</label>
                    <input type="text" 
                           name="title" 
                           required 
                           value="{{ old('title') }}" 
                           placeholder="Ví dụ: Điều khoản dịch vụ, Chính sách bảo mật..." 
                           class="w-full px-4 py-2.5 rounded-xl border border-gray-200 dark:border-slate-850 bg-gray-50 dark:bg-slate-950 dark:text-white focus:outline-none focus:ring-2 focus:ring-shopee">
                    @error('title')
                        <span class="text-[10px] text-red-500 font-medium block mt-1">{{ $message }}</span>
                    @enderror
                </div>

                <!-- Slug (đường dẫn URL) -->
                <div>
                    <label class="block text-xs font-bold text-gray-700 dark:text-slate-350 uppercase tracking-wider mb-2">{{ __('Đường dẫn URL (Slug)') }}</label>
                    <div class="flex items-center gap-2">
                        <span class="text-xs text-gray-400 font-mono whitespace-nowrap">/page/</span>
                        <input type="text" 
                               name="slug" 
                               value="{{ old('slug') }}" 
                               placeholder="tu-dong-sinh-tu-tieu-de" 
                               class="flex-1 px-4 py-2.5 rounded-xl border border-gray-200 dark:border-slate-850 bg-gray-50 dark:bg-slate-950 dark:text-white focus:outline-none focus:ring-2 focus:ring-shopee text-xs">
                    </div>
                    <span class="text-[10px] text-gray-400 mt-1 block">{{ __('Để trống sẽ tự động sinh từ tiêu đề.') }}</span>
                    @error('slug')
                        <span class="text-[10px] text-red-500 font-medium block mt-1">{{ $message }}</span>
                    @enderror
                </div>

                <!-- Trình soạn thảo nội dung trang -->
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <label class="block text-xs font-bold text-gray-700 dark:text-slate-350 uppercase tracking-wider">{{ __('Nội dung trang') }} *</label>
                        @include('admin.pages._ai-generator')
                    </div>
                    <textarea name="content" id="page-content-editor" class="w-full">{{ old('content') }}</textarea>
                </div>
            </div>
        </div>

        <!-- Cột phải: Cấu hình bổ sung -->
        <div class="space-y-6">
            <!-- Cài đặt xuất bản -->
            <div class="p-6 bg-white dark:bg-slate-900 border border-gray-150 dark:border-slate-800 rounded-2xl shadow-sm space-y-5">
                <h3 class="text-sm font-bold text-gray-900 dark:text-white uppercase tracking-wider pb-3 border-b border-gray-50 dark:border-slate-850">{{ __('Tùy chọn xuất bản') }}</h3>
                
                <!-- Trạng thái -->
                <div>
                    <label class="block text-xs font-bold text-gray-700 dark:text-slate-350 uppercase mb-2">{{ __('Trạng thái') }} *</label>
                    <select name="status" class="w-full px-3 py-2 text-sm rounded-xl border border-gray-200 dark:border-slate-850 bg-gray-50 dark:bg-slate-950 dark:text-white focus:outline-none">
                        <option value="published" {{ old('status', 'published') === 'published' ? 'selected' : '' }}>{{ __('Xuất bản ngay') }}</option>
                        <option value="draft" {{ old('status') === 'draft' ? 'selected' : '' }}>{{ __('Bản nháp (Draft)') }}</option>
                    </select>
                </div>

                <!-- Thứ tự hiển thị -->
                <div>
                    <label class="block text-xs font-bold text-gray-700 dark:text-slate-350 uppercase mb-2">{{ __('Thứ tự sắp xếp') }}</label>
                    <input type="number" 
                           name="sort_order" 
                           value="{{ old('sort_order', 0) }}" 
                           min="0"
                           class="w-full px-3 py-2 text-sm rounded-xl border border-gray-200 dark:border-slate-850 bg-gray-50 dark:bg-slate-950 dark:text-white focus:outline-none">
                    <span class="text-[9px] text-gray-400 mt-1 block">{{ __('Số càng nhỏ hiển thị càng trước.') }}</span>
                </div>
            </div>

            <!-- SEO Meta -->
            <div class="p-6 bg-white dark:bg-slate-900 border border-gray-150 dark:border-slate-800 rounded-2xl shadow-sm space-y-5">
                <h3 class="text-sm font-bold text-gray-900 dark:text-white uppercase tracking-wider pb-3 border-b border-gray-50 dark:border-slate-850 flex items-center gap-2">
                    <i data-lucide="globe" class="w-4 h-4 text-shopee"></i>
                    {{ __('SEO Meta') }}
                </h3>
                
                <div>
                    <label class="block text-xs font-bold text-gray-700 dark:text-slate-350 uppercase mb-2">{{ __('Meta Description') }}</label>
                    <textarea name="meta_description" rows="3" placeholder="Mô tả ngắn gọn cho trang (dùng cho kết quả tìm kiếm Google)..." class="w-full px-3 py-2 text-sm rounded-xl border border-gray-200 dark:border-slate-850 bg-gray-50 dark:bg-slate-950 dark:text-white focus:outline-none focus:ring-2 focus:ring-shopee">{{ old('meta_description') }}</textarea>
                </div>

                <!-- Tùy chọn chặn các công cụ tìm kiếm đánh chỉ mục (SEO Noindex) và loại bỏ trang khỏi sitemap.xml tự động -->
                <div class="flex items-start gap-2 pt-2 border-t border-gray-50 dark:border-slate-850">
                    <input type="checkbox" 
                           id="is_noindex" 
                           name="is_noindex" 
                           value="1" 
                           {{ old('is_noindex') ? 'checked' : '' }}
                           class="w-4 h-4 text-shopee border-gray-300 rounded focus:ring-shopee mt-0.5">
                    <label for="is_noindex" class="text-xs text-gray-650 dark:text-slate-350 select-none cursor-pointer">
                        <span class="font-bold block text-gray-700 dark:text-slate-300">{{ __('Chặn tìm kiếm & Sitemap') }}</span>
                        <span class="text-[10px] text-gray-400 dark:text-slate-500 mt-0.5 block leading-normal">{{ __('Không cho phép Google/Bing index trang này và loại trừ hoàn toàn khỏi tệp sitemap.xml của hệ thống.') }}</span>
                    </label>
                </div>
            </div>

            <!-- Nút hành động -->
            <div class="flex gap-3">
                <a href="{{ route('admin.pages.index') }}" class="w-1/2 flex items-center justify-center px-4 py-2.5 text-xs font-bold text-gray-500 bg-gray-50 hover:bg-gray-100 rounded-xl border border-gray-200 dark:border-slate-850 transition-all dark:text-slate-300">
                    {{ __('Hủy bỏ') }}
                </a>
                <button type="submit" class="w-1/2 flex items-center justify-center px-4 py-2.5 text-xs font-bold text-white bg-shopee hover:bg-shopee-dark rounded-xl shadow-md transition-all">
                    {{ __('Lưu trang') }}
                </button>
            </div>
        </div>
    </form>
</div>
@endsection
