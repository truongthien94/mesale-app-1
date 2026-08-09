@extends('layouts.admin')

@section('title', __('Chỉnh Sửa Vai Trò') . ' - ' . $siteName)

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <!-- Header -->
    <div class="flex items-center gap-3">
        <a href="{{ route('admin.roles.index') }}" 
           class="p-2 bg-white dark:bg-slate-900 border border-gray-100 dark:border-slate-800 rounded-xl text-gray-500 hover:text-shopee hover:border-shopee/30 transition-all shrink-0">
            <i data-lucide="chevron-left" class="w-4 h-4"></i>
        </a>
        <div>
            <h2 class="text-xl font-bold text-gray-900 dark:text-white">{{ __('Chỉnh sửa vai trò') }}</h2>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ __('Cập nhật vai trò và điều chỉnh quyền hạn truy cập.') }}</p>
        </div>
    </div>

    <!-- Form -->
    <form action="{{ route('admin.roles.update', $role->id) }}" method="POST" class="bg-white dark:bg-slate-900 rounded-3xl p-6 md:p-8 shadow-sm border border-gray-100 dark:border-slate-800/50 space-y-6">
        @csrf
        @method('PUT')

        <!-- Tên vai trò -->
        <div class="space-y-1">
            <label for="name" class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">{{ __('Tên vai trò') }}</label>
            <input type="text" 
                   name="name" 
                   id="name" 
                   required 
                   value="{{ old('name', $role->name) }}"
                   placeholder="Ví dụ: Kế toán, Chăm sóc khách hàng..." 
                   class="block w-full px-4 py-2.5 border border-gray-200 dark:border-slate-700 rounded-2xl text-xs focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-gray-50/50 dark:bg-slate-800">
            @error('name')
                <span class="text-[10px] text-red-500 font-medium block mt-1">{{ $message }}</span>
            @enderror
        </div>

        <!-- Thiết lập quyền hạn -->
        <div class="space-y-4 pt-4 border-t border-gray-100 dark:border-slate-800">
            <div class="flex items-center justify-between">
                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">{{ __('Quyền hạn truy cập') }}</label>
                <div class="flex gap-2">
                    <button type="button" 
                            onclick="toggleAllPermissions(true)"
                            class="text-[10px] font-bold text-shopee hover:underline focus:outline-none">
                        {{ __('Chọn tất cả') }}
                    </button>
                    <span class="text-gray-300">|</span>
                    <button type="button" 
                            onclick="toggleAllPermissions(false)"
                            class="text-[10px] font-bold text-gray-400 hover:underline focus:outline-none">
                        {{ __('Bỏ chọn tất cả') }}
                    </button>
                </div>
            </div>

            <!-- Grid Checkbox Permissions theo Nhóm -->
            <div class="space-y-6" id="permissions-grid">
                @php
                    $rolePermissions = $role->permissions ?? [];
                @endphp
                @foreach($permissions as $groupName => $groupItems)
                    @php
                        $groupSlug = Str::slug($groupName);
                    @endphp
                    <div class="space-y-2.5">
                        <div class="flex items-center justify-between border-b border-gray-100 dark:border-slate-800 pb-1">
                            <h3 class="text-xs font-bold text-gray-400 uppercase tracking-widest pl-1 border-l-2 border-shopee">{{ $groupName }}</h3>
                            <label class="flex items-center gap-1.5 cursor-pointer select-none">
                                <input type="checkbox" 
                                       data-group="{{ $groupSlug }}"
                                       onclick="toggleGroupPermissions('{{ $groupSlug }}', this.checked)"
                                       class="group-toggle-checkbox w-3.5 h-3.5 text-shopee focus:ring-shopee rounded border-gray-300 dark:border-slate-700">
                                <span class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider">{{ __('Chọn tất cả') }}</span>
                            </label>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            @foreach($groupItems as $key => $label)
                                <label class="border border-gray-100 dark:border-slate-800/80 p-4 rounded-2xl flex items-start gap-3 cursor-pointer select-none transition-all hover:bg-gray-50/50 dark:hover:bg-slate-800/30 bg-white dark:bg-slate-900">
                                    <input type="checkbox" 
                                           name="permissions[]" 
                                           value="{{ $key }}" 
                                           data-group="{{ $groupSlug }}"
                                           onchange="checkGroupStatus('{{ $groupSlug }}')"
                                           @checked(in_array($key, $rolePermissions))
                                           class="permission-checkbox text-shopee focus:ring-shopee rounded border-gray-300 dark:border-slate-700 mt-0.5">
                                    <div class="space-y-0.5">
                                        <span class="text-xs font-bold text-gray-800 dark:text-gray-200">{{ $label }}</span>
                                        <span class="block text-[10px] text-gray-400 dark:text-gray-500 font-medium">{{ __('Cho phép tài khoản thực hiện hành động liên quan đến') }} {{ strtolower($label) }}</span>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Nút hành động -->
        <div class="pt-4 border-t border-gray-100 dark:border-slate-800 flex items-center justify-end gap-3">
            <a href="{{ route('admin.roles.index') }}" 
               class="px-4 py-2 border border-gray-200 dark:border-slate-700 rounded-2xl text-xs font-bold text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-slate-800 transition-all">
                {{ __('Hủy bỏ') }}
            </a>
            <button type="submit" 
                    class="px-5 py-2.5 bg-shopee hover:bg-shopee-dark text-white font-bold rounded-2xl text-xs transition-all shadow-lg shadow-shopee/15">
                {{ __('Lưu thay đổi') }}
            </button>
        </div>
    </form>
</div>

<script>
    /**
     * Chọn hoặc bỏ chọn tất cả các checkbox quyền hạn
     */
    function toggleAllPermissions(checked) {
        const checkboxes = document.querySelectorAll('.permission-checkbox');
        checkboxes.forEach(cb => {
            cb.checked = checked;
        });

        // Cập nhật trạng thái của tất cả các checkbox nhóm
        const groupToggles = document.querySelectorAll('.group-toggle-checkbox');
        groupToggles.forEach(toggle => {
            toggle.checked = checked;
        });
    }

    /**
     * Chọn hoặc bỏ chọn tất cả các checkbox trong 1 nhóm cụ thể
     */
    function toggleGroupPermissions(groupSlug, checked) {
        const checkboxes = document.querySelectorAll(`.permission-checkbox[data-group="${groupSlug}"]`);
        checkboxes.forEach(cb => {
            cb.checked = checked;
        });
    }

    /**
     * Kiểm tra trạng thái của các checkbox con để cập nhật checkbox "Chọn tất cả" của nhóm
     */
    function checkGroupStatus(groupSlug) {
        const checkboxes = document.querySelectorAll(`.permission-checkbox[data-group="${groupSlug}"]`);
        const groupToggle = document.querySelector(`.group-toggle-checkbox[data-group="${groupSlug}"]`);
        if (groupToggle) {
            const allChecked = Array.from(checkboxes).every(cb => cb.checked);
            groupToggle.checked = allChecked;
        }
    }

    // Tự động kiểm tra trạng thái ban đầu của các nhóm khi load trang
    document.addEventListener('DOMContentLoaded', function() {
        const groupToggles = document.querySelectorAll('.group-toggle-checkbox');
        groupToggles.forEach(toggle => {
            const groupSlug = toggle.getAttribute('data-group');
            checkGroupStatus(groupSlug);
        });
    });
</script>
@endsection
