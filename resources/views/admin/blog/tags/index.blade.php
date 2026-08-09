@extends('layouts.admin')

@section('title', 'Quản lý Thẻ Tag Blog - Trang Quản Trị')

@section('content')
<div class="space-y-6" x-data="{ 
    isEdit: false, 
    editId: null, 
    editName: '', 
    setAction(tag) {
        this.isEdit = true;
        this.editId = tag.id;
        this.editName = tag.name;
        $refs.form.action = '/' + window.adminPrefix + '/blog/tags/' + tag.id;
    },
    resetForm() {
        this.isEdit = false;
        this.editId = null;
        this.editName = '';
        $refs.form.action = '{{ route('admin.blog.tags.store') }}';
    }
}">
    <!-- Header -->
    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Quản lý Thẻ Tag Blog</h1>
        <p class="text-sm text-gray-500">Quản lý các nhãn từ khóa ngắn gắn liền với các chủ đề bài viết Blog để hỗ trợ công tác SEO.</p>
    </div>

    <!-- Main Grid Layout -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Cột trái: Danh sách tag (Col span 2) -->
        <div class="lg:col-span-2 bg-white dark:bg-slate-900 border border-gray-150 dark:border-slate-800 rounded-2xl shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse whitespace-nowrap">
                    <thead>
                        <tr class="bg-gray-50 dark:bg-slate-950 border-b border-gray-100 dark:border-slate-850 text-xs font-bold text-gray-600 dark:text-slate-300 uppercase tracking-wider">
                            <th class="px-6 py-4">Thẻ tag</th>
                            <th class="px-6 py-4">Slug</th>
                            <th class="px-6 py-4">Số bài viết sử dụng</th>
                            <th class="px-6 py-4 text-right">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-slate-850 text-sm">
                        @forelse($tags as $tag)
                        <tr class="hover:bg-gray-50/50 dark:hover:bg-slate-800/20">
                            <td class="px-6 py-4 font-bold text-gray-900 dark:text-white">
                                #{{ $tag->name }}
                            </td>
                            <td class="px-6 py-4 text-gray-500 dark:text-slate-400">
                                {{ $tag->slug }}
                            </td>
                            <td class="px-6 py-4 text-gray-750 dark:text-slate-350">
                                <span class="px-2 py-0.5 bg-gray-100 dark:bg-slate-800 rounded-full text-xs font-semibold">
                                    {{ $tag->posts_count }} bài viết
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <button type="button" 
                                            @click="setAction({
                                                id: {{ $tag->id }},
                                                name: '{{ addslashes($tag->name) }}'
                                            })" 
                                            class="p-1.5 bg-gray-50 hover:bg-shopee/10 text-gray-600 hover:text-shopee rounded-lg border border-gray-150 transition-all" 
                                            title="Sửa thẻ tag">
                                        <i data-lucide="edit-3" class="w-4 h-4 pointer-events-none"></i>
                                    </button>
                                    <form action="{{ route('admin.blog.tags.destroy', $tag->id) }}" method="POST" onsubmit="return confirm('Bạn có chắc chắn muốn xóa thẻ tag này?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-1.5 bg-gray-50 hover:bg-red-50 text-gray-600 hover:text-red-500 rounded-lg border border-gray-150 transition-all" title="Xóa thẻ tag">
                                            <i data-lucide="trash-2" class="w-4 h-4 pointer-events-none"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="px-6 py-8 text-center text-gray-500">
                                <i data-lucide="tag" class="w-12 h-12 text-gray-300 mx-auto mb-3"></i>
                                Chưa có thẻ tag nào được tạo.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Cột phải: Form Thêm / Sửa tag -->
        <div class="bg-white dark:bg-slate-900 border border-gray-150 dark:border-slate-800 rounded-2xl shadow-sm p-6">
            <h3 class="text-sm font-bold text-gray-900 dark:text-white uppercase tracking-wider pb-3 border-b border-gray-50 dark:border-slate-850 mb-5"
                x-text="isEdit ? 'Cập Nhật Thẻ Tag' : 'Thêm Thẻ Tag Mới'">
                Thêm Thẻ Tag Mới
            </h3>

            <form x-ref="form" action="{{ route('admin.blog.tags.store') }}" method="POST" class="space-y-4">
                @csrf
                <!-- Method spoofing when in edit mode -->
                <template x-if="isEdit">
                    <input type="hidden" name="_method" value="PUT">
                </template>

                <!-- Name -->
                <div>
                    <label class="block text-xs font-bold text-gray-700 dark:text-slate-350 uppercase mb-2">Tên thẻ tag *</label>
                    <input type="text" 
                           name="name" 
                           required 
                           x-model="editName"
                           placeholder="Ví dụ: shopeesale" 
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
</div>
@endsection
