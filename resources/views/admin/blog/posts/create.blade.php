@extends('layouts.admin')

@section('title', 'Viết Bài Mới - Trang Quản Trị')

@section('styles')
<!-- TinyMCE CDN để hỗ trợ soạn thảo văn bản giàu tính năng -->
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
        
        var inputElement = document.getElementById(inputId);
        if (inputElement) {
            inputElement.value = fileUrl;
            // Gửi sự kiện input để AlpineJS đồng bộ dữ liệu vào biến x-model tương ứng
            inputElement.dispatchEvent(new Event('input'));
            
            // Xóa file input nếu chọn từ thư viện
            if (inputId === 'thumbnail-url-input') {
                var fileInput = document.getElementById('thumbnail-file-input');
                if (fileInput) fileInput.value = '';
            }
        }
    };

    document.addEventListener('DOMContentLoaded', () => {
        tinymce.init({
            selector: '#content-editor',
            height: 500,
            plugins: 'advlist autolink lists link image charmap preview anchor searchreplace visualblocks code fullscreen insertdatetime media table code help wordcount',
            toolbar: 'undo redo | blocks | bold italic backcolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | removeformat | image link table | code fullscreen',
            content_style: 'body { font-family:Inter,sans-serif; font-size:14px }',
            skin: document.documentElement.classList.contains('dark') ? 'oxide-dark' : 'oxide',
            content_css: document.documentElement.classList.contains('dark') ? 'dark' : 'default',
            language: 'vi', // cấu hình tiếng việt nếu được tải
            branding: false,
            promotion: false,
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
<div class="w-full space-y-6">
    <!-- Breadcrumb -->
    <div class="flex items-center gap-2 text-xs md:text-sm text-gray-500">
        <a href="{{ route('admin.blog.posts.index') }}" class="hover:text-shopee">Bài viết</a>
        <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
        <span class="text-gray-900 dark:text-white font-medium">Viết bài mới</span>
    </div>

    <!-- Header -->
    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Viết Bài Mới</h1>
        <p class="text-sm text-gray-500">Soạn thảo nội dung bài viết hướng dẫn mua sắm, chia sẻ kinh nghiệm.</p>
    </div>

    <!-- Form -->
    <form action="{{ route('admin.blog.posts.store') }}" method="POST" enctype="multipart/form-data" class="grid grid-cols-1 lg:grid-cols-3 2xl:grid-cols-4 gap-8">
        @csrf
        
        <!-- Cột trái: Nội dung chính bài viết -->
        <div class="lg:col-span-2 2xl:col-span-3 space-y-6">
            <div class="p-6 bg-white dark:bg-slate-900 border border-gray-150 dark:border-slate-800 rounded-2xl shadow-sm space-y-6">
                <!-- Tiêu đề bài viết & Đường dẫn tĩnh (slug) -->
                <div x-data="{
                        slug: '{{ old('slug') }}',
                        slugTouched: {{ old('slug') ? 'true' : 'false' }},
                        // Chuyển tiêu đề tiếng Việt có dấu thành slug thân thiện URL
                        slugify(text) {
                            return (text || '').toString()
                                .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
                                .replace(/[đĐ]/g, 'd')
                                .toLowerCase()
                                .replace(/[^a-z0-9\s-]/g, '')
                                .trim()
                                .replace(/[\s_-]+/g, '-')
                                .replace(/^-+|-+$/g, '');
                        }
                     }" class="space-y-6">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 dark:text-slate-350 uppercase tracking-wider mb-2">Tiêu đề bài viết *</label>
                        <input type="text"
                               name="title"
                               required
                               value="{{ old('title') }}"
                               placeholder="Nhập tiêu đề hấp dẫn..."
                               @input="if (!slugTouched) slug = slugify($event.target.value)"
                               class="w-full px-4 py-2.5 rounded-xl border border-gray-200 dark:border-slate-850 bg-gray-50 dark:bg-slate-950 dark:text-white focus:outline-none focus:ring-2 focus:ring-shopee">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 dark:text-slate-350 uppercase tracking-wider mb-2">Đường dẫn tĩnh (Slug)</label>
                        <div class="flex items-stretch rounded-xl border border-gray-200 dark:border-slate-850 bg-gray-50 dark:bg-slate-950 overflow-hidden focus-within:ring-2 focus-within:ring-shopee">
                            <span class="hidden sm:flex items-center px-3 text-xs text-gray-500 dark:text-slate-400 bg-gray-100 dark:bg-slate-900 border-r border-gray-200 dark:border-slate-850 whitespace-nowrap">{{ url('/blog') }}/</span>
                            <input type="text"
                                   name="slug"
                                   x-model="slug"
                                   @input="slugTouched = true"
                                   placeholder="tu-dong-tao-tu-tieu-de"
                                   class="flex-1 min-w-0 px-4 py-2.5 bg-transparent dark:text-white focus:outline-none">
                            <button type="button"
                                    @click="slug = slugify(document.querySelector('input[name=title]').value); slugTouched = false"
                                    title="Tạo lại slug từ tiêu đề"
                                    class="px-3 flex items-center text-gray-500 hover:text-shopee transition-colors">
                                <i data-lucide="refresh-cw" class="w-4 h-4"></i>
                            </button>
                        </div>
                        <p class="mt-1.5 text-xs text-gray-500">Để trống nếu muốn hệ thống tự tạo slug từ tiêu đề. Slug sẽ tự động bỏ dấu, viết thường và nối bằng dấu gạch ngang.</p>
                    </div>
                </div>

                <!-- Tóm tắt ngắn -->
                <div>
                    <label class="block text-xs font-bold text-gray-700 dark:text-slate-350 uppercase tracking-wider mb-2">Tóm tắt ngắn (Summary) *</label>
                    <textarea name="summary" 
                              required 
                              rows="3" 
                              placeholder="Mô tả tóm tắt nội dung bài viết dưới 500 ký tự..." 
                              class="w-full px-4 py-2.5 rounded-xl border border-gray-200 dark:border-slate-850 bg-gray-50 dark:bg-slate-950 dark:text-white focus:outline-none focus:ring-2 focus:ring-shopee">{{ old('summary') }}</textarea>
                </div>

                <!-- Trình soạn thảo nội dung -->
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <label class="block text-xs font-bold text-gray-700 dark:text-slate-350 uppercase tracking-wider">Nội dung chi tiết *</label>
                        @include('admin.blog.posts._ai-generator')
                    </div>
                    <textarea name="content" id="content-editor" class="w-full">{{ old('content') }}</textarea>
                </div>
            </div>

            <!-- Tab Cấu hình SEO -->
            <div x-data="{ seoExpanded: false }" class="p-6 bg-white dark:bg-slate-900 border border-gray-150 dark:border-slate-800 rounded-2xl shadow-sm space-y-4">
                <button type="button" @click="seoExpanded = !seoExpanded" class="w-full flex items-center justify-between font-bold text-gray-900 dark:text-white text-sm">
                    <span class="flex items-center gap-2">
                        <i data-lucide="globe" class="w-4 h-4 text-shopee"></i>
                        Cấu hình SEO Meta (Tùy chọn)
                    </span>
                    <i data-lucide="chevron-down" class="w-4 h-4 transition-transform duration-200" :class="{ 'rotate-180': seoExpanded }"></i>
                </button>

                <div x-show="seoExpanded" x-cloak class="space-y-4 pt-4 border-t border-gray-50 dark:border-slate-850">
                    <div>
                        <label class="block text-xs font-bold text-gray-750 dark:text-slate-350 uppercase mb-2">SEO Title</label>
                        <input type="text" name="seo_title" value="{{ old('seo_title') }}" placeholder="Mặc định sử dụng Tiêu đề bài viết..." class="w-full px-4 py-2 text-sm rounded-xl border border-gray-200 dark:border-slate-850 bg-gray-50 dark:bg-slate-950 dark:text-white focus:outline-none focus:ring-2 focus:ring-shopee">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-750 dark:text-slate-350 uppercase mb-2">SEO Description</label>
                        <textarea name="seo_description" rows="2.5" placeholder="Mặc định sử dụng Tóm tắt bài viết..." class="w-full px-4 py-2 text-sm rounded-xl border border-gray-200 dark:border-slate-850 bg-gray-50 dark:bg-slate-950 dark:text-white focus:outline-none focus:ring-2 focus:ring-shopee">{{ old('seo_description') }}</textarea>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-750 dark:text-slate-350 uppercase mb-2">Từ khóa SEO (Keywords)</label>
                        <input type="text" name="seo_keywords" value="{{ old('seo_keywords') }}" placeholder="sale shopee, hoan tien shopee, cach mua sam,..." class="w-full px-4 py-2 text-sm rounded-xl border border-gray-200 dark:border-slate-850 bg-gray-50 dark:bg-slate-950 dark:text-white focus:outline-none focus:ring-2 focus:ring-shopee">
                    </div>
                </div>
            </div>
        </div>

        <!-- Cột phải: Cấu hình bổ sung, Phân loại, Thumbnail, Lưu -->
        <div class="space-y-6">
            <!-- Thumbnail & Publish Widget -->
            <div class="p-6 bg-white dark:bg-slate-900 border border-gray-150 dark:border-slate-800 rounded-2xl shadow-sm space-y-5">
                <h3 class="text-sm font-bold text-gray-900 dark:text-white uppercase tracking-wider pb-3 border-b border-gray-50 dark:border-slate-850">Ảnh Đại Diện</h3>
                
                <!-- Preview Thumbnail bằng AlpineJS và CKFinder -->
                <div x-data="{ previewUrl: null, useUrl: '' }" x-init="$watch('useUrl', value => { if(value) previewUrl = value; })" class="space-y-4">
                    <div class="w-full aspect-[16/10] bg-gray-50 dark:bg-slate-950 border border-dashed border-gray-200 dark:border-slate-800 rounded-xl overflow-hidden flex items-center justify-center relative">
                        <template x-if="previewUrl">
                            <div class="w-full h-full relative group">
                                <img :src="previewUrl" class="w-full h-full object-cover">
                                <button type="button" @click="previewUrl = null; useUrl = ''; document.getElementById('thumbnail-file-input').value = ''" class="absolute top-2 right-2 bg-red-500 hover:bg-red-600 text-white rounded-full p-1.5 shadow-md opacity-0 group-hover:opacity-100 transition-opacity">
                                    <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                </button>
                            </div>
                        </template>
                        <template x-if="!previewUrl">
                            <div class="text-center text-gray-400">
                                <i data-lucide="image" class="w-8 h-8 mx-auto mb-2 text-gray-300"></i>
                                <span class="text-xs">Chưa chọn ảnh</span>
                            </div>
                        </template>
                    </div>
                    
                    <!-- Lưu đường dẫn ảnh khi chọn từ thư viện -->
                    <input type="hidden" name="thumbnail" id="thumbnail-url-input" x-model="useUrl">
                    
                    <div class="grid grid-cols-2 gap-2">
                        <!-- Chọn file từ máy tính -->
                        <div>
                            <input type="file" 
                                   name="thumbnail_file" 
                                   id="thumbnail-file-input"
                                   @change="
                                        const file = $event.target.files[0];
                                        if (file) {
                                            previewUrl = URL.createObjectURL(file);
                                            useUrl = '';
                                        }
                                   "
                                   class="hidden">
                            <label for="thumbnail-file-input" class="w-full flex items-center justify-center gap-1.5 px-3 py-2 text-xs font-bold text-gray-700 bg-gray-50 dark:bg-slate-950 border border-gray-200 dark:border-slate-850 hover:bg-gray-100 rounded-xl cursor-pointer transition-all dark:text-slate-350">
                                <i data-lucide="upload" class="w-3.5 h-3.5"></i> Chọn file máy
                            </label>
                        </div>
                        
                        <!-- Chọn từ thư viện elFinder -->
                        <button type="button" 
                                @click="openElfinderPopup('thumbnail-url-input')"
                                class="w-full flex items-center justify-center gap-1.5 px-3 py-2 text-xs font-bold text-shopee bg-shopee/10 border border-shopee/20 hover:bg-shopee/20 rounded-xl transition-all">
                            <i data-lucide="folder-open" class="w-3.5 h-3.5"></i> Từ thư viện
                        </button>
                    </div>
                </div>
            </div>

            <!-- Phân loại bài viết (Category & Tags) -->
            <div class="p-6 bg-white dark:bg-slate-900 border border-gray-150 dark:border-slate-800 rounded-2xl shadow-sm space-y-5">
                <h3 class="text-sm font-bold text-gray-900 dark:text-white uppercase tracking-wider pb-3 border-b border-gray-50 dark:border-slate-850">Phân Loại & Thẻ</h3>
                
                <!-- Category Select -->
                <div>
                    <label class="block text-xs font-bold text-gray-700 dark:text-slate-350 uppercase mb-2">Chuyên mục chính *</label>
                    <select name="category_id" required class="w-full px-3 py-2 text-sm rounded-xl border border-gray-200 dark:border-slate-850 bg-gray-50 dark:bg-slate-950 dark:text-white focus:outline-none">
                        <option value="">-- Chọn chuyên mục --</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}" {{ old('category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Tags list checkboxes -->
                <div>
                    <label class="block text-xs font-bold text-gray-700 dark:text-slate-350 uppercase mb-2">Chọn thẻ tag</label>
                    <div class="max-h-40 overflow-y-auto border border-gray-100 dark:border-slate-850 rounded-xl p-3 bg-gray-50 dark:bg-slate-950 space-y-2">
                        @forelse($tags as $t)
                        <label class="flex items-center gap-2.5 text-xs text-gray-600 dark:text-slate-300 cursor-pointer">
                            <input type="checkbox" name="tags[]" value="{{ $t->id }}" class="rounded text-shopee focus:ring-shopee w-3.5 h-3.5">
                            <span>{{ $t->name }}</span>
                        </label>
                        @empty
                        <span class="text-xs text-gray-400">Chưa có thẻ tag nào.</span>
                        @endforelse
                    </div>
                </div>
            </div>

            <!-- Cài đặt xuất bản (Status & Options) -->
            <div class="p-6 bg-white dark:bg-slate-900 border border-gray-150 dark:border-slate-800 rounded-2xl shadow-sm space-y-5">
                <h3 class="text-sm font-bold text-gray-900 dark:text-white uppercase tracking-wider pb-3 border-b border-gray-50 dark:border-slate-850">Tùy chọn xuất bản</h3>
                
                <!-- Status select -->
                <div>
                    <label class="block text-xs font-bold text-gray-700 dark:text-slate-350 uppercase mb-2">Trạng thái đăng *</label>
                    <select name="status" x-data="{ status: 'published' }" @change="status = $event.target.value" class="w-full px-3 py-2 text-sm rounded-xl border border-gray-200 dark:border-slate-850 bg-gray-50 dark:bg-slate-950 dark:text-white focus:outline-none">
                        <option value="published" selected>Xuất bản ngay</option>
                        <option value="draft">Bản nháp (Draft)</option>
                    </select>
                </div>

                <!-- Checkbox sticky / featured -->
                <div class="space-y-3 pt-2">
                    <label class="flex items-center gap-2.5 text-xs text-gray-700 dark:text-slate-350 cursor-pointer">
                        <input type="checkbox" name="is_featured" value="1" class="rounded text-shopee focus:ring-shopee w-4 h-4">
                        <span>Bài viết nổi bật (Featured)</span>
                    </label>
                    <label class="flex items-center gap-2.5 text-xs text-gray-700 dark:text-slate-350 cursor-pointer">
                        <input type="checkbox" name="is_sticky" value="1" class="rounded text-shopee focus:ring-shopee w-4 h-4">
                        <span>Ghim bài viết lên đầu trang</span>
                    </label>
                </div>

                <!-- Submit buttons -->
                <div class="pt-4 border-t border-gray-50 dark:border-slate-850 flex gap-3">
                    <a href="{{ route('admin.blog.posts.index') }}" class="w-1/2 flex items-center justify-center px-4 py-2.5 text-xs font-bold text-gray-500 bg-gray-50 hover:bg-gray-100 rounded-xl border border-gray-200 dark:border-slate-850 transition-all dark:text-slate-300">
                        Hủy bỏ
                    </a>
                    <button type="submit" class="w-1/2 flex items-center justify-center px-4 py-2.5 text-xs font-bold text-white bg-shopee hover:bg-shopee-dark rounded-xl shadow-md transition-all">
                        Lưu bài viết
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection
