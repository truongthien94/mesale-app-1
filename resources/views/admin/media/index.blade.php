@extends('layouts.admin')

@section('title', __('Quản Lý Hình Ảnh & Media') . ' - ' . $siteName)

@section('content')
<div class="space-y-6">
    <!-- Tiêu đề trang -->
    <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
        <div>
            <h1 class="text-xl font-bold text-gray-900 dark:text-white sm:text-2xl">
                {{ __('Quản Lý Hình Ảnh & Media') }}
            </h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">
                {{ __('Quản lý thư viện hình ảnh, tệp tin tải lên của toàn bộ hệ thống bằng elFinder.') }}
            </p>
        </div>
        <div class="flex items-center gap-2">
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium bg-shopee/10 text-shopee border border-shopee/20">
                <i data-lucide="shield-check" class="w-3.5 h-3.5"></i>
                {{ __('Kết nối bảo mật') }}
            </span>
        </div>
    </div>

    <!-- Khung hiển thị elFinder bằng iframe để cô lập giao diện tránh xung đột CSS -->
    <div class="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800/80 rounded-xl overflow-hidden shadow-sm">
        <div class="px-5 py-4 border-b border-gray-200 dark:border-slate-800/80 bg-gray-50 dark:bg-slate-900/50 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <i data-lucide="folder-open" class="w-5 h-5 text-shopee"></i>
                <span class="font-bold text-gray-800 dark:text-slate-200 text-sm">{{ __('Thư viện lưu trữ') }}</span>
            </div>
            <div class="text-xs text-gray-400">
                {{ __('Thư mục gốc: /public/uploads/') }}
            </div>
        </div>
        
        <!-- Khung chứa iframe elFinder -->
        <div class="p-2 bg-gray-100 dark:bg-slate-950">
            <iframe src="{{ route('elfinder.index') }}" class="w-full rounded-lg border border-gray-200 dark:border-slate-800 shadow-inner" style="height: calc(100vh - 270px); min-height: 600px;"></iframe>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Khởi tạo lại Lucide Icons cho các biểu tượng động
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    });
</script>
@endsection
