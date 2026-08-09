@extends('layouts.admin')

@section('title', __('Quản Lý Vai Trò Phân Quyền') . ' - ' . $siteName)

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h2 class="text-xl font-bold text-gray-900 dark:text-white">{{ __('Vai trò & Quyền hạn') }}</h2>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ __('Quản lý danh sách vai trò phân quyền chi tiết cho nhân viên quản trị.') }}</p>
        </div>
        <a href="{{ route('admin.roles.create') }}" 
           class="inline-flex items-center gap-1.5 px-4 py-2 bg-shopee hover:bg-shopee-dark text-white font-bold rounded-2xl text-xs transition-all shadow-lg shadow-shopee/15 shrink-0 self-start md:self-auto">
            <i data-lucide="plus" class="w-4 h-4"></i>
            <span>{{ __('Thêm vai trò mới') }}</span>
        </a>
    </div>

    <!-- Thông báo nhanh -->
    @if(session('success'))
        <div class="p-4 bg-green-50 border border-green-100 text-green-700 text-xs font-medium rounded-2xl flex items-center gap-2 dark:bg-green-900/20 dark:border-green-800/30 dark:text-green-400">
            <i data-lucide="check-circle" class="w-4 h-4 shrink-0"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif
    @if(session('error'))
        <div class="p-4 bg-red-50 border border-red-100 text-red-700 text-xs font-medium rounded-2xl flex items-center gap-2 dark:bg-red-900/20 dark:border-red-800/30 dark:text-red-400">
            <i data-lucide="alert-circle" class="w-4 h-4 shrink-0"></i>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    <!-- Danh sách vai trò -->
    <div class="bg-white dark:bg-slate-900 rounded-3xl shadow-sm border border-gray-100 dark:border-slate-800/50 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs whitespace-nowrap">
                <thead>
                    <tr class="bg-gray-50/70 border-b border-gray-100 dark:bg-slate-800/40 dark:border-slate-800 text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        <th class="px-6 py-4"># ID</th>
                        <th class="px-6 py-4">{{ __('Tên vai trò') }}</th>
                        <th class="px-6 py-4">{{ __('Mã Slug') }}</th>
                        <th class="px-6 py-4">{{ __('Quyền hạn gán') }}</th>
                        <th class="px-6 py-4 text-center">{{ __('Số tài khoản') }}</th>
                        <th class="px-6 py-4 text-right">{{ __('Hành động') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-slate-800/50">
                    @forelse($roles as $role)
                        <tr class="hover:bg-gray-50/50 dark:hover:bg-slate-800/20 transition-all">
                            <td class="px-6 py-4 font-bold text-gray-400">#{{ $role->id }}</td>
                            <td class="px-6 py-4">
                                <span class="font-bold text-gray-900 dark:text-white">{{ $role->name }}</span>
                            </td>
                            <td class="px-6 py-4 text-gray-500 dark:text-gray-400">{{ $role->slug }}</td>
                            <td class="px-6 py-4 max-w-xs md:max-w-sm">
                                <div class="flex flex-wrap gap-1">
                                    @php
                                        $permissionsList = $role->permissions ?? [];
                                        $permissionsMap = \App\Http\Controllers\Admin\RoleController::$permissionsMap;
                                    @endphp
                                    @forelse($permissionsList as $perm)
                                        <span class="inline-flex px-2 py-0.5 rounded-full text-[9px] font-bold bg-orange-50 text-shopee border border-orange-100/50 dark:bg-orange-950/20 dark:text-orange-400 dark:border-orange-900/30">
                                            {{ $permissionsMap[$perm] ?? $perm }}
                                        </span>
                                    @empty
                                        <span class="text-[10px] text-gray-400 italic">{{ __('Không có quyền nào') }}</span>
                                    @endforelse
                                </div>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span class="inline-flex items-center justify-center font-bold h-6 min-w-6 px-1.5 rounded-full bg-gray-100 text-gray-700 text-[10px] dark:bg-slate-800 dark:text-gray-300">
                                    {{ $role->users_count }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('admin.roles.edit', $role->id) }}" 
                                       class="p-1.5 text-gray-400 hover:text-shopee hover:bg-shopee/5 rounded-lg transition-all"
                                       title="Chỉnh sửa">
                                        <i data-lucide="edit-3" class="w-4 h-4 pointer-events-none"></i>
                                    </a>
                                    
                                    <form action="{{ route('admin.roles.destroy', $role->id) }}" method="POST" 
                                          onsubmit="return confirm('Bạn có chắc chắn muốn xóa vai trò này?');"
                                          class="inline-block">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" 
                                                class="p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-950/30 rounded-lg transition-all"
                                                title="Xóa vai trò">
                                            <i data-lucide="trash-2" class="w-4 h-4 pointer-events-none"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-10 text-center text-gray-400 dark:text-gray-500 italic">
                                <div class="flex flex-col items-center justify-center space-y-2">
                                    <i data-lucide="shield-alert" class="w-8 h-8 text-gray-300"></i>
                                    <span>{{ __('Chưa có vai trò nào được tạo.') }}</span>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
