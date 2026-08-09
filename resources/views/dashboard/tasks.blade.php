@extends('layouts.app')

@section('title', (\App\Models\Setting::getVal('tasks_title') ?: __('Nhiệm Vụ Nhận Thưởng')) . ' - ' . $siteName)

@section('styles')
{{-- Định dạng hiển thị nội dung HTML (Mô tả ngắn & Hướng dẫn thực hiện) do Admin soạn bằng trình soạn thảo trực quan --}}
<style>
    .task-rich-text > *:first-child { margin-top: 0; }
    .task-rich-text > *:last-child { margin-bottom: 0; }
    .task-rich-text p { margin: 0 0 .5rem; }
    .task-rich-text strong, .task-rich-text b { font-weight: 700; color: #1f2937; }
    .dark .task-rich-text strong, .dark .task-rich-text b { color: #f1f5f9; }
    .task-rich-text em, .task-rich-text i { font-style: italic; }
    .task-rich-text u { text-decoration: underline; }
    .task-rich-text a { color: #ee4d2d; font-weight: 600; text-decoration: none; word-break: break-word; }
    .task-rich-text a:hover { text-decoration: underline; }
    .dark .task-rich-text a { color: #ff7337; }
    .task-rich-text ul, .task-rich-text ol { margin: 0 0 .5rem; padding-left: 1.15rem; }
    .task-rich-text ul { list-style: disc; }
    .task-rich-text ol { list-style: decimal; }
    .task-rich-text li { margin-bottom: .25rem; }
    .task-rich-text h1, .task-rich-text h2, .task-rich-text h3,
    .task-rich-text h4, .task-rich-text h5, .task-rich-text h6 {
        font-weight: 700; color: #111827; margin: .75rem 0 .4rem; line-height: 1.4;
    }
    .dark .task-rich-text h1, .dark .task-rich-text h2, .dark .task-rich-text h3,
    .dark .task-rich-text h4, .dark .task-rich-text h5, .dark .task-rich-text h6 { color: #fff; }
    .task-rich-text h1 { font-size: 1.05rem; }
    .task-rich-text h2 { font-size: 1rem; }
    .task-rich-text h3 { font-size: .95rem; }
    .task-rich-text img { max-width: 100%; height: auto; border-radius: .75rem; margin: .4rem 0; }
    .task-rich-text blockquote {
        margin: .5rem 0; padding: .35rem .75rem;
        border-left: 3px solid #ee4d2d; background: rgba(238, 77, 45, .05); border-radius: .5rem;
    }
    .task-rich-text table { width: 100%; border-collapse: collapse; margin: .5rem 0; font-size: .95em; }
    .task-rich-text th, .task-rich-text td { border: 1px solid #e5e7eb; padding: .35rem .5rem; }
    .dark .task-rich-text th, .dark .task-rich-text td { border-color: #334155; }
    .task-rich-text hr { margin: .75rem 0; border-color: #e5e7eb; }
    .dark .task-rich-text hr { border-color: #334155; }
</style>
@endsection

@section('content')
<div class="px-4 mx-auto max-w-7xl sm:px-6 lg:px-8 py-6 sm:py-10"
     x-data="taskDashboardHandler()">
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">

        {{-- Thanh điều hướng bên trái (Sidebar) - Ẩn trên mobile để tối ưu không gian hiển thị --}}
        <div class="hidden lg:block lg:col-span-3">
            @include('dashboard.sidebar')
        </div>

        {{-- Khu vực nội dung chính của trang Nhiệm Vụ --}}
        <div class="lg:col-span-9 space-y-6">
            {{-- Nhắc nhở thành viên bổ sung email để bảo vệ tài khoản --}}
            @include('components.email_update_notice')

            {{-- 1. Banner Nhiệm Vụ Nhận Thưởng --}}
            <div class="relative overflow-hidden p-5 sm:p-7 bg-gradient-to-r from-[#FFF4EC] via-[#FFF9F5] to-white dark:from-slate-900/40 dark:to-slate-900/20 rounded-2xl border border-orange-50 dark:border-slate-800 shadow-[0_4px_20px_-4px_rgba(0,0,0,0.05)] transition-all duration-300">
                <div class="flex items-start gap-4 max-w-[70%] sm:max-w-[75%] relative z-10">
                    {{-- Icon Cúp vàng bên trái nổi bật trên nền cam nhạt --}}
                    <div class="w-12 h-12 flex items-center justify-center bg-[#FFEFEB] dark:bg-orange-950/30 dark:border dark:border-orange-100/10 text-shopee rounded-2xl shrink-0 shadow-sm">
                        <i data-lucide="trophy" class="w-6 h-6"></i>
                    </div>
                    <div>
                        <h1 class="text-base sm:text-lg font-black text-gray-900 dark:text-white uppercase tracking-tight">
                            {{ \App\Models\Setting::getVal('tasks_title') ?: __('Nhiệm Vụ Nhận Thưởng') }}
                        </h1>
                        <p class="text-[10px] sm:text-xs text-gray-500 dark:text-slate-400 mt-1.5 leading-relaxed">
                            {{ \App\Models\Setting::getVal('tasks_intro') ?: __('Hoàn thành các nhiệm vụ để nhận nhiều phần thưởng hấp dẫn') }}
                        </p>
                    </div>
                </div>
                
                {{-- Hình ảnh cúp vàng 3D trang trí góc phải banner --}}
                <div class="absolute right-0 top-0 bottom-0 w-32 flex items-center justify-end pointer-events-none pr-2 sm:pr-6 z-0">
                    <img src="{{ asset('assets/images/tasks_trophy.webp') }}" 
                         class="h-20 sm:h-24 w-auto object-contain select-none drop-shadow-md" 
                         alt="Trophy">
                </div>
            </div>

            {{-- Thông báo phản hồi thao tác từ hệ thống --}}
            @if(session('success'))
            <div class="p-4 bg-green-50 dark:bg-green-950/20 border border-green-200 dark:border-green-900/30 text-green-800 dark:text-green-300 rounded-2xl flex items-center gap-3 text-xs font-semibold shadow-[0_4px_20px_-4px_rgba(0,0,0,0.05)]">
                <i data-lucide="check-circle" class="w-4 h-4 shrink-0"></i>
                {{ session('success') }}
            </div>
            @endif
            @if(session('error'))
            <div class="p-4 bg-red-50 dark:bg-red-950/20 border border-red-200 dark:border-red-900/30 text-red-800 dark:text-red-300 rounded-2xl flex items-center gap-3 text-xs font-semibold shadow-[0_4px_20px_-4px_rgba(0,0,0,0.05)]">
                <i data-lucide="alert-circle" class="w-4 h-4 shrink-0"></i>
                {{ session('error') }}
            </div>
            @endif

            {{-- 2. Thống Kê Cá Nhân (Grid 2x2 trên Mobile, 1x4 trên Desktop) --}}
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                {{-- Số lượng nhiệm vụ Đang Làm --}}
                <div class="bg-white dark:bg-slate-900 border border-gray-50 dark:border-slate-800 rounded-2xl p-4 sm:p-5 shadow-[0_4px_20px_-4px_rgba(0,0,0,0.05)] flex items-center gap-3 transition-all hover:shadow-md">
                    <div class="w-11 h-11 sm:w-12 sm:h-12 rounded-xl flex items-center justify-center shrink-0 bg-blue-50 dark:bg-blue-900/20 text-blue-500 border border-blue-100 dark:border-blue-900/40">
                        <i data-lucide="clipboard-list" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <p class="text-[10px] font-bold text-gray-400 dark:text-slate-500 uppercase tracking-wider">{{ __('Đang làm') }}</p>
                        <p class="text-lg sm:text-xl font-extrabold text-gray-900 dark:text-white mt-0.5">{{ $inProgressCount }}</p>
                        <p class="text-[9px] sm:text-[10px] font-semibold text-gray-400 dark:text-slate-500 mt-0.5">{{ __('Nhiệm vụ') }}</p>
                    </div>
                </div>

                {{-- Số lượng nhiệm vụ Chờ Nhận thưởng (Đã xong nhưng chưa claim) --}}
                <div class="bg-white dark:bg-slate-900 border border-gray-50 dark:border-slate-800 rounded-2xl p-4 sm:p-5 shadow-[0_4px_20px_-4px_rgba(0,0,0,0.05)] flex items-center gap-3 transition-all hover:shadow-md">
                    <div class="w-11 h-11 sm:w-12 sm:h-12 rounded-xl flex items-center justify-center shrink-0 bg-amber-50 dark:bg-amber-900/20 text-amber-500 border border-amber-100 dark:border-amber-900/40">
                        <i data-lucide="hourglass" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <p class="text-[10px] font-bold text-amber-500 uppercase tracking-wider">{{ __('Chờ nhận') }}</p>
                        <p class="text-lg sm:text-xl font-extrabold text-amber-600 dark:text-amber-400 mt-0.5">{{ $completedCount }}</p>
                        <p class="text-[9px] sm:text-[10px] font-semibold text-gray-400 dark:text-slate-500 mt-0.5">{{ __('Nhiệm vụ') }}</p>
                    </div>
                </div>

                {{-- Số lượng nhiệm vụ Đã Nhận thưởng thành công --}}
                <div class="bg-white dark:bg-slate-900 border border-gray-50 dark:border-slate-800 rounded-2xl p-4 sm:p-5 shadow-[0_4px_20px_-4px_rgba(0,0,0,0.05)] flex items-center gap-3 transition-all hover:shadow-md">
                    <div class="w-11 h-11 sm:w-12 sm:h-12 rounded-xl flex items-center justify-center shrink-0 bg-green-50 dark:bg-green-900/20 text-green-500 border border-green-100 dark:border-green-900/40">
                        <i data-lucide="badge-check" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <p class="text-[10px] font-bold text-green-500 uppercase tracking-wider">{{ __('Đã nhận') }}</p>
                        <p class="text-lg sm:text-xl font-extrabold text-green-600 dark:text-green-400 mt-0.5">{{ $claimedCount }}</p>
                        <p class="text-[9px] sm:text-[10px] font-semibold text-gray-400 dark:text-slate-500 mt-0.5">{{ __('Nhiệm vụ') }}</p>
                    </div>
                </div>

                {{-- Tổng số tiền thưởng tích lũy nhận được từ nhiệm vụ --}}
                <div class="bg-white dark:bg-slate-900 border border-gray-50 dark:border-slate-800 rounded-2xl p-4 sm:p-5 shadow-[0_4px_20px_-4px_rgba(0,0,0,0.05)] flex items-center gap-3 transition-all hover:shadow-md">
                    <div class="w-11 h-11 sm:w-12 sm:h-12 rounded-xl flex items-center justify-center shrink-0 bg-rose-50 dark:bg-rose-900/20 text-rose-500 border border-rose-100 dark:border-rose-900/40">
                        <i data-lucide="wallet" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <p class="text-[10px] font-bold text-rose-500 uppercase tracking-wider">{{ __('Tổng thưởng') }}</p>
                        <p class="text-lg sm:text-xl font-extrabold text-rose-600 dark:text-rose-400 mt-0.5">{{ number_format($totalEarned) }}<span class="text-xs font-semibold ml-0.5">đ</span></p>
                        <p class="text-[9px] sm:text-[10px] font-semibold text-gray-400 dark:text-slate-500 mt-0.5">{{ __('Giá trị') }}</p>
                    </div>
                </div>
            </div>

            @if($tasks->isNotEmpty())
            {{-- 3. Bộ Lọc Trạng Thái Nhiệm Vụ (Tabs filter) --}}
            <div class="flex items-center justify-between bg-gray-50/70 dark:bg-slate-800/40 p-1.5 sm:p-2 rounded-2xl border border-gray-100/50 dark:border-slate-800/60 shadow-[0_4px_20px_-4px_rgba(0,0,0,0.02)]">
                <div class="flex items-center gap-1 overflow-x-auto scrollbar-none flex-1">
                    {{-- Tab hiển thị Tất cả --}}
                    <button @click="filter = 'all'" 
                            :class="filter === 'all' ? 'bg-white dark:bg-slate-900 text-shopee dark:text-shopee-light shadow-sm border border-gray-100/80 dark:border-slate-700/50' : 'text-gray-500 hover:text-gray-900 dark:text-slate-400 dark:hover:text-white border-transparent'"
                            class="px-3.5 py-2 sm:px-4 sm:py-2.5 text-xs sm:text-sm font-bold rounded-xl transition-all duration-200 flex items-center gap-1.5 shrink-0 focus:outline-none">
                        <i data-lucide="layout-grid" class="w-4 h-4"></i>
                        {{ __('Tất cả') }}
                    </button>
                    {{-- Tab hiển thị Nhiệm vụ Đang làm --}}
                    <button @click="filter = 'in_progress'" 
                            :class="filter === 'in_progress' ? 'bg-white dark:bg-slate-900 text-shopee dark:text-shopee-light shadow-sm border border-gray-100/80 dark:border-slate-700/50' : 'text-gray-500 hover:text-gray-900 dark:text-slate-400 dark:hover:text-white border-transparent'"
                            class="px-3.5 py-2 sm:px-4 sm:py-2.5 text-xs sm:text-sm font-bold rounded-xl transition-all duration-200 flex items-center gap-1.5 shrink-0 focus:outline-none">
                        <i data-lucide="loader" class="w-4 h-4"></i>
                        {{ __('Đang làm') }}
                    </button>
                    {{-- Tab hiển thị Nhiệm vụ Chờ nhận thưởng --}}
                    <button @click="filter = 'completed'" 
                            :class="filter === 'completed' ? 'bg-white dark:bg-slate-900 text-shopee dark:text-shopee-light shadow-sm border border-gray-100/80 dark:border-slate-700/50' : 'text-gray-500 hover:text-gray-900 dark:text-slate-400 dark:hover:text-white border-transparent'"
                            class="px-3.5 py-2 sm:px-4 sm:py-2.5 text-xs sm:text-sm font-bold rounded-xl transition-all duration-200 flex items-center gap-1.5 shrink-0 focus:outline-none">
                        <i data-lucide="hourglass" class="w-4 h-4"></i>
                        {{ __('Chờ nhận') }}
                    </button>
                    {{-- Tab hiển thị Nhiệm vụ Đã hoàn thành (Đã nhận thưởng) --}}
                    <button @click="filter = 'claimed'" 
                            :class="filter === 'claimed' ? 'bg-white dark:bg-slate-900 text-shopee dark:text-shopee-light shadow-sm border border-gray-100/80 dark:border-slate-700/50' : 'text-gray-500 hover:text-gray-900 dark:text-slate-400 dark:hover:text-white border-transparent'"
                            class="px-3.5 py-2 sm:px-4 sm:py-2.5 text-xs sm:text-sm font-bold rounded-xl transition-all duration-200 flex items-center gap-1.5 shrink-0 focus:outline-none">
                        <i data-lucide="badge-check" class="w-4 h-4"></i>
                        {{ __('Đã hoàn thành') }}
                    </button>
                </div>
                {{-- Nút Phễu Lọc thiết kế bo tròn giống hình tham chiếu --}}
                <div class="pl-2 shrink-0 border-l border-gray-200/50 dark:border-slate-700/30 ml-1">
                    <button class="w-9 h-9 sm:w-10 sm:h-10 bg-white dark:bg-slate-900 hover:bg-gray-50 dark:hover:bg-slate-800 border border-gray-100 dark:border-slate-700/50 rounded-xl flex items-center justify-center shadow-sm text-gray-500 dark:text-slate-400 transition-all active:scale-95">
                        <i data-lucide="filter" class="w-4 h-4"></i>
                    </button>
                </div>
            </div>
            @endif

            {{-- 4. Danh Sách Nhiệm Vụ được thiết kế lại --}}
            @if($tasks->isEmpty())
            <div class="bg-white dark:bg-slate-900 rounded-2xl border border-gray-50 dark:border-slate-800 p-12 text-center shadow-[0_4px_20px_-4px_rgba(0,0,0,0.05)]">
                <i data-lucide="trophy" class="w-14 h-14 mx-auto text-gray-200 dark:text-slate-700 mb-4"></i>
                <h3 class="text-base font-bold text-gray-700 dark:text-gray-300">{{ __('Chưa có nhiệm vụ nào.') }}</h3>
                <p class="text-xs text-gray-400 dark:text-slate-500 mt-2">{{ __('Các nhiệm vụ sẽ xuất hiện ở đây khi được quản trị viên tạo.') }}</p>
            </div>
            @else
            <div class="space-y-4">
                @foreach($tasks as $task)
                @php
                    $userTask       = $task->userTask;
                    $colors         = $task->getColorClasses();
                    $percent        = $userTask ? $userTask->getProgressPercent() : 0;
                    $isClaimed      = $userTask && $userTask->status === 'claimed';
                    $isCompleted    = $userTask && $userTask->status === 'completed';
                    // Nhiệm vụ thủ công đã gửi yêu cầu xác nhận và đang chờ quản trị viên duyệt
                    $isPending      = $userTask && $userTask->status === 'pending';
                    $rejectReason   = $userTask->reject_reason ?? null;
                    $progress       = $userTask ? $userTask->progress : 0;
                    $percentReached = $task->action !== 'custom' && $percent >= 100;

                    // Xác định trạng thái hiển thị logic để đồng bộ với filter AlpineJS
                    if ($isClaimed) {
                        $displayStatus = 'claimed';
                    } elseif ($isCompleted || $percentReached) {
                        $displayStatus = 'completed';
                    } else {
                        $displayStatus = 'in_progress';
                    }
                @endphp
                <div x-show="shouldShow('{{ $displayStatus }}')"
                     x-transition:enter="transition ease-out duration-300"
                     x-transition:enter-start="opacity-0 transform scale-95"
                     x-transition:enter-end="opacity-100 transform scale-100"
                     class="bg-white dark:bg-slate-900 rounded-2xl border border-gray-50 dark:border-slate-800 shadow-[0_4px_20px_-4px_rgba(0,0,0,0.05)] overflow-hidden transition-all hover:shadow-md flex flex-col {{ $isClaimed ? 'opacity-75' : '' }}">

                    <div class="p-5 flex flex-col gap-4">
                        <div class="flex items-start gap-4 relative">
                            {{-- Icon của nhiệm vụ --}}
                            <div class="w-12 h-12 rounded-2xl {{ $colors['bg'] }} {{ $colors['text'] }} flex items-center justify-center shrink-0 shadow-sm">
                                <i data-lucide="{{ $task->icon ?? 'star' }}" class="w-6 h-6"></i>
                            </div>

                            {{-- Thông tin chi tiết nhiệm vụ --}}
                            <div class="flex-1 min-w-0 pr-6">
                                <div class="flex items-center gap-1.5 flex-wrap">
                                    <h3 class="font-bold text-sm sm:text-base text-gray-900 dark:text-white leading-snug">{{ $task->title }}</h3>
                                    
                                    {{-- Gắn nhãn mới cho nhiệm vụ vừa tạo trong vòng 3 ngày --}}
                                    @if($task->created_at->diffInDays(now()) < 3)
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[9px] font-bold bg-orange-100 text-orange-600 dark:bg-orange-900/30 dark:text-orange-400">
                                        {{ __('Mới') }}
                                    </span>
                                    @endif
                                    
                                    {{-- Nhãn phân loại loại nhiệm vụ (Hằng ngày, hằng tuần...) --}}
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[9px] font-bold {{ $task->getTypeBadgeClass() }}">
                                        {{ $task->getTypeLabel() }}
                                    </span>
                                </div>

                                {{-- Huy hiệu trạng thái xử lý trên giao diện --}}
                                <div class="flex items-center gap-2 mt-1.5 flex-wrap">
                                    @if($isClaimed)
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-green-50 text-green-700 dark:bg-green-950/20 dark:text-green-400 border border-green-100 dark:border-green-900/20">
                                        <i data-lucide="check-circle-2" class="w-3 h-3"></i>
                                        {{ __('Đã nhận') }}
                                    </span>
                                    @elseif($isCompleted || $percentReached)
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 dark:bg-amber-950/20 dark:text-amber-400 border border-amber-100 dark:border-amber-900/20">
                                        <i data-lucide="hourglass" class="w-3 h-3"></i>
                                        {{ __('Chờ nhận') }}
                                    </span>
                                    @elseif($isPending)
                                    {{-- Đã gửi yêu cầu xác nhận, đang đợi quản trị viên kiểm duyệt --}}
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-orange-50 text-orange-700 dark:bg-orange-950/20 dark:text-orange-400 border border-orange-100 dark:border-orange-900/20">
                                        <i data-lucide="clock" class="w-3 h-3"></i>
                                        {{ __('Chờ duyệt') }}
                                    </span>
                                    @else
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-50 text-blue-700 dark:bg-blue-950/20 dark:text-blue-400 border border-blue-100 dark:border-blue-900/20">
                                        <i data-lucide="loader" class="w-3 h-3 animate-spin"></i>
                                        {{ __('Đang thực hiện') }}
                                    </span>
                                    @endif
                                </div>

                                {{-- Mô tả ngắn về nhiệm vụ (hỗ trợ định dạng HTML soạn từ trang quản trị) --}}
                                @if($task->description_html)
                                <div class="task-rich-text text-xs text-gray-500 dark:text-slate-400 mt-2.5 leading-relaxed">{!! $task->description_html !!}</div>
                                @endif

                                {{-- Giá trị phần thưởng hiển thị lớn, in đậm màu xanh lá --}}
                                <div class="mt-2.5">
                                    <span class="text-green-600 dark:text-green-400 font-extrabold text-base sm:text-lg">
                                        +{{ number_format($task->reward_amount) }}đ
                                    </span>
                                </div>
                            </div>

                            {{-- Mũi tên Chevron để xem hướng dẫn/mở rộng --}}
                            @if($task->guide_html)
                            <button @click="toggleGuide({{ $task->id }})" class="absolute right-0 top-0 text-gray-400 hover:text-gray-600 dark:hover:text-white transition-all" title="{{ __('Xem hướng dẫn') }}">
                                <i data-lucide="chevron-right" class="w-5 h-5"></i>
                            </button>
                            @else
                            <div class="absolute right-0 top-0 text-gray-300 dark:text-slate-700">
                                <i data-lucide="chevron-right" class="w-5 h-5"></i>
                            </div>
                            @endif

                            {{-- Nút hành động tương ứng theo tiến trình nhiệm vụ --}}
                            <div class="absolute right-0 bottom-0">
                                @if($isClaimed)
                                    {{-- Không hiển thị nút ở đây vì đã được dời xuống dải footer --}}
                                @elseif($isCompleted || $percentReached)
                                    <button @click="claimReward({{ $task->id }})" :disabled="claimingId === {{ $task->id }}"
                                            class="px-4 py-2 text-xs font-bold text-white bg-gradient-to-r from-orange-500 to-rose-500 hover:brightness-110 active:scale-95 rounded-2xl shadow-sm transition-all duration-200 disabled:opacity-60 disabled:cursor-wait">
                                        <span x-text="claimingId === {{ $task->id }} ? '{{ __('Đang nhận...') }}' : '{{ __('Nhận ngay') }}'"></span>
                                    </button>
                                @elseif($task->action === 'custom')
                                    @if($isPending)
                                        {{-- Đã gửi yêu cầu, chỉ hiển thị trạng thái đang chờ quản trị viên kiểm tra --}}
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs text-orange-600 dark:text-orange-400 font-semibold bg-orange-50 dark:bg-orange-950/20 rounded-xl border border-orange-100 dark:border-orange-900/20">
                                            <i data-lucide="clock" class="w-3.5 h-3.5"></i>
                                            {{ __('Đang chờ duyệt') }}
                                        </span>
                                    @else
                                        {{-- Thành viên tự báo đã hoàn thành để quản trị viên kiểm tra và duyệt --}}
                                        <button type="button"
                                                @click="openSubmitModal({{ $task->id }}, @js($task->title))"
                                                class="px-4 py-2 text-xs font-bold text-white bg-gradient-to-r from-orange-500 to-rose-500 hover:brightness-110 active:scale-95 rounded-2xl shadow-sm transition-all duration-200 flex items-center gap-1.5">
                                            <i data-lucide="send" class="w-3.5 h-3.5"></i>
                                            {{ $rejectReason ? __('Gửi lại xác nhận') : __('Tôi đã hoàn thành') }}
                                        </button>
                                    @endif
                                @else
                                    {{-- Đóng vai trò phím tắt điều hướng nhanh (Shortcut) dẫn người dùng đi thực hiện hành động để hoàn thành nhiệm vụ --}}
                                    @php
                                        $actionRoute = match($task->action) {
                                            'profile'      => route('profile'),
                                            'referral'     => route('referrals'),
                                            'cashback'     => route('home'),
                                            'checkin'      => route('checkin'),
                                            'withdraw'     => route('withdraw'),
                                            'save_product' => route('home'),
                                            default        => null,
                                        };
                                    @endphp
                                    @if($actionRoute)
                                        <a href="{{ $actionRoute }}"
                                           class="px-4 py-2 text-xs font-bold text-shopee bg-shopee/10 hover:bg-shopee/20 dark:bg-shopee-light/10 dark:text-shopee-light rounded-2xl transition-all duration-200 active:scale-95 flex items-center gap-1">
                                            {{ __('Làm ngay') }}
                                            <i data-lucide="arrow-right" class="w-3 h-3"></i>
                                        </a>
                                    @else
                                        <span class="inline-flex items-center gap-1 text-xs text-gray-400 dark:text-slate-500 font-medium">
                                            <i data-lucide="loader" class="w-3.5 h-3.5 animate-spin"></i>
                                            {{ __('Đang làm...') }}
                                        </span>
                                    @endif
                                @endif
                            </div>
                        </div>

                        {{-- Thanh tiến độ (Progress Bar) --}}
                        <div class="space-y-1.5 mt-2">
                            <div class="flex items-center justify-between text-xs">
                                <span class="text-gray-500 dark:text-slate-400 font-semibold">
                                    {{ __('Tiến độ') }}: <span class="font-extrabold text-gray-800 dark:text-gray-200">{{ $progress }}/{{ $task->target_count }}</span>
                                    @if($task->action !== 'custom')
                                    <span class="text-gray-400 dark:text-slate-500 font-normal">({{ $task->getActionLabel() }})</span>
                                    @endif
                                </span>
                                <span class="font-extrabold {{ $percent >= 100 ? 'text-green-600 dark:text-green-400' : 'text-shopee' }}">{{ $percent }}%</span>
                            </div>
                            <div class="h-2 bg-gray-100 dark:bg-slate-800 rounded-full overflow-hidden">
                                <div class="h-full rounded-full transition-all duration-500 {{ $isClaimed ? 'bg-green-500' : 'bg-shopee' }}"
                                     style="width: {{ $percent }}%"></div>
                            </div>
                        </div>

                        {{-- Khối trạng thái kiểm duyệt dành riêng cho nhiệm vụ thủ công --}}
                        @if($task->action === 'custom' && !$isClaimed)
                            @if($isPending)
                            {{-- Yêu cầu đã gửi thành công, chờ quản trị viên kiểm tra --}}
                            <div class="mt-2 p-3 rounded-2xl bg-orange-50/60 dark:bg-orange-950/15 border border-orange-100/70 dark:border-orange-900/20 text-[11px] leading-relaxed">
                                <p class="flex items-center gap-1.5 font-bold text-orange-700 dark:text-orange-400">
                                    <i data-lucide="clock" class="w-3.5 h-3.5 shrink-0"></i>
                                    {{ __('Đã gửi yêu cầu xác nhận') }}
                                    @if($userTask->submitted_at)
                                    <span class="font-medium text-orange-500/80 dark:text-orange-400/70">— {{ $userTask->submitted_at->format('H:i d/m/Y') }}</span>
                                    @endif
                                </p>
                                @if($userTask->submit_note)
                                <p class="mt-1 text-gray-600 dark:text-slate-400 break-words">
                                    <span class="font-semibold">{{ __('Ghi chú của bạn') }}:</span> {{ $userTask->submit_note }}
                                </p>
                                @endif
                                <p class="mt-1 text-orange-600/80 dark:text-orange-400/70">{{ __('Quản trị viên sẽ kiểm tra và duyệt trong thời gian sớm nhất.') }}</p>
                            </div>
                            @elseif($rejectReason)
                            {{-- Yêu cầu bị từ chối, hiển thị lý do để thành viên khắc phục và gửi lại --}}
                            <div class="mt-2 p-3 rounded-2xl bg-red-50/60 dark:bg-red-950/15 border border-red-100/70 dark:border-red-900/20 text-[11px] leading-relaxed">
                                <p class="flex items-center gap-1.5 font-bold text-red-700 dark:text-red-400">
                                    <i data-lucide="x-circle" class="w-3.5 h-3.5 shrink-0"></i>
                                    {{ __('Yêu cầu xác nhận đã bị từ chối') }}
                                </p>
                                <p class="mt-1 text-gray-600 dark:text-slate-400 break-words">
                                    <span class="font-semibold">{{ __('Lý do') }}:</span> {{ $rejectReason }}
                                </p>
                                <p class="mt-1 text-red-600/80 dark:text-red-400/70">{{ __('Bạn có thể khắc phục và gửi lại yêu cầu xác nhận.') }}</p>
                            </div>
                            @endif
                        @endif

                        {{-- Hướng dẫn chi tiết nhiệm vụ (Có thể mở rộng khi click Chevron) --}}
                        @if($task->guide_html)
                        <div x-show="openGuides.includes({{ $task->id }})" x-cloak
                             x-transition:enter="transition ease-out duration-300 transform"
                             x-transition:enter-start="opacity-0 -translate-y-2 scale-98"
                             x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                             x-transition:leave="transition ease-in duration-200 transform"
                             x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                             x-transition:leave-end="opacity-0 -translate-y-2 scale-98"
                             class="mt-3 p-4 sm:p-5 bg-gradient-to-br from-blue-50/40 via-blue-50/20 to-transparent dark:from-blue-950/15 dark:to-transparent border border-blue-100/40 dark:border-blue-900/20 rounded-2xl text-xs leading-relaxed shadow-[inset_0_1px_2px_rgba(0,0,0,0.01)] transition-all">
                            
                            {{-- Tiêu đề Hướng dẫn chi tiết thiết kế tinh tế với icon thẳng hàng --}}
                            <div class="flex items-center gap-2 font-extrabold text-blue-700 dark:text-blue-400 mb-2.5 pb-2 border-b border-blue-100/30 dark:border-blue-900/10 select-none">
                                <span class="p-1 rounded-lg bg-blue-100/60 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 flex items-center justify-center shrink-0">
                                    <i data-lucide="help-circle" class="w-4 h-4"></i>
                                </span>
                                <span class="tracking-wide uppercase text-[10px] sm:text-xs">
                                    {{ __('Hướng dẫn chi tiết') }}
                                </span>
                            </div>
                            
                            {{-- Nội dung hướng dẫn được hiển thị trong block đẹp mắt, dễ đọc (giữ nguyên định dạng HTML admin đã soạn) --}}
                            <div class="task-rich-text text-gray-600 dark:text-slate-350 font-medium pl-0.5 text-xs md:text-[13px] leading-relaxed">
                                {!! $task->guide_html !!}
                            </div>
                        </div>
                        @endif
                    </div>

                    {{-- Dải footer trạng thái cho các nhiệm vụ đã hoàn thiện nhận thưởng --}}
                    @if($isClaimed && $userTask->claimed_at)
                    <div class="px-5 py-3.5 bg-[#F8FDF9] dark:bg-green-950/10 border-t border-green-100/50 dark:border-green-900/20 flex items-center justify-between text-xs">
                        <div class="text-green-600 dark:text-green-500 font-medium flex items-center gap-1.5">
                            <i data-lucide="clock" class="w-3.5 h-3.5"></i>
                            <span>{{ __('Đã nhận thưởng lúc') }} {{ $userTask->claimed_at->format('H:i d/m/Y') }}</span>
                        </div>
                        <span class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-xl text-[10px] font-extrabold bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400">
                            <i data-lucide="check-circle-2" class="w-3.5 h-3.5"></i>
                            {{ __('Đã nhận thưởng') }}
                        </span>
                    </div>
                    @endif
                </div>
                @endforeach

                {{-- Khối thông báo khi bộ lọc hiện tại không tìm thấy nhiệm vụ tương ứng --}}
                <div x-show="!hasVisibleTasks()" x-cloak
                     class="bg-white dark:bg-slate-900 rounded-2xl border border-gray-50 dark:border-slate-800 p-12 text-center shadow-[0_4px_20px_-4px_rgba(0,0,0,0.05)] text-gray-500 dark:text-slate-400">
                    <i data-lucide="info" class="w-14 h-14 mx-auto text-gray-300 dark:text-slate-600 mb-4"></i>
                    <h3 class="text-base font-bold text-gray-700 dark:text-gray-300">{{ __('Không tìm thấy nhiệm vụ nào.') }}</h3>
                    <p class="text-xs text-gray-400 dark:text-slate-500 mt-2">{{ __('Không có nhiệm vụ nào phù hợp với bộ lọc hiện tại của bạn.') }}</p>
                </div>
            </div>
            @endif

            {{-- 5. Box Ghi Chú & Điều Khoản Nghiệp Vụ --}}
            <div class="bg-amber-50 dark:bg-amber-950/20 border border-amber-100 dark:border-amber-900/30 rounded-2xl p-5 flex items-start gap-3 shadow-[0_4px_20px_-4px_rgba(0,0,0,0.05)]">
                <i data-lucide="lightbulb" class="w-4 h-4 text-amber-500 shrink-0 mt-0.5"></i>
                <div class="text-xs text-amber-800 dark:text-amber-300 space-y-1">
                    <p class="font-semibold">{{ __('Lưu ý về nhiệm vụ:') }}</p>
                    <ul class="space-y-1 list-disc list-inside text-amber-700 dark:text-amber-400 opacity-90 mt-2">
                        <li>{{ __('Nhiệm vụ hằng ngày/tuần sẽ tự động reset theo chu kỳ.') }}</li>
                        <li>{{ __('Tiến độ tự động cập nhật theo hành động của bạn trong hệ thống.') }}</li>
                        <li>{{ __('Phần thưởng được cộng ngay vào ví sau khi nhận thành công.') }}</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    {{-- Hộp thoại gửi yêu cầu xác nhận hoàn thành nhiệm vụ thủ công --}}
    <div x-show="submitModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4"
         x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
        <div class="absolute inset-0 bg-black/60" @click="closeSubmitModal()"></div>
        <div class="relative w-full max-w-md bg-white dark:bg-slate-900 rounded-3xl shadow-2xl border border-gray-100 dark:border-slate-800 p-6 space-y-4"
             x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100">

            <div class="flex items-start gap-3">
                <div class="w-10 h-10 rounded-2xl bg-orange-100 dark:bg-orange-900/30 flex items-center justify-center text-shopee shrink-0">
                    <i data-lucide="send" class="w-5 h-5"></i>
                </div>
                <div class="min-w-0">
                    <h3 class="font-bold text-gray-900 dark:text-white text-sm">{{ __('Xác nhận đã hoàn thành nhiệm vụ') }}</h3>
                    <p class="text-xs text-gray-500 dark:text-slate-400 mt-0.5 break-words" x-text="submitTaskTitle"></p>
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-700 dark:text-slate-300 mb-1.5">
                    {{ __('Ghi chú / Bằng chứng') }}
                    <span class="font-normal text-gray-400">({{ __('không bắt buộc') }})</span>
                </label>
                <textarea x-model="submitNote" rows="3" maxlength="500"
                          placeholder="{{ __('Ví dụ: link bài đăng, mã đơn hàng, tên tài khoản đã dùng...') }}"
                          class="w-full px-3 py-2.5 text-sm rounded-xl border border-gray-200 dark:border-slate-700 bg-gray-50 dark:bg-slate-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-shopee/20 focus:border-shopee outline-none transition-all resize-none"></textarea>
                <p class="text-[10px] text-gray-400 mt-1 flex items-center justify-between">
                    <span>{{ __('Ghi chú càng rõ ràng, quản trị viên duyệt càng nhanh.') }}</span>
                    <span x-text="submitNote.length + '/500'"></span>
                </p>
            </div>

            <div class="flex items-center justify-end gap-2 pt-1">
                <button type="button" @click="closeSubmitModal()"
                        class="px-5 py-2.5 text-xs font-semibold text-gray-600 dark:text-gray-400 bg-gray-100 dark:bg-slate-800 hover:bg-gray-200 dark:hover:bg-slate-700 rounded-xl transition-all">
                    {{ __('Hủy') }}
                </button>
                <button type="button" @click="sendSubmit()" :disabled="submitting"
                        class="px-5 py-2.5 text-xs font-bold text-white bg-gradient-to-r from-orange-500 to-rose-500 hover:brightness-110 rounded-xl transition-all active:scale-95 disabled:opacity-60 disabled:cursor-wait flex items-center gap-1.5">
                    <i data-lucide="check" class="w-3.5 h-3.5"></i>
                    <span x-text="submitting ? '{{ __('Đang gửi...') }}' : '{{ __('Gửi yêu cầu duyệt') }}'"></span>
                </button>
            </div>
        </div>
    </div>
</div>

@section('scripts')
<script>
/**
 * Trình xử lý logic của trang Dashboard Nhiệm Vụ bằng AlpineJS.
 */
function taskDashboardHandler() {
    return {
        claimingId: null,
        openGuides: [],
        claimBaseUrl: '{{ rtrim(route('tasks.index'), '/') }}',
        filter: 'all',

        // Trạng thái hộp thoại gửi yêu cầu xác nhận hoàn thành nhiệm vụ thủ công
        submitModalOpen: false,
        submitTaskId: null,
        submitTaskTitle: '',
        submitNote: '',
        submitting: false,

        taskStatuses: @json($tasks->map(function($t) {
            $ut = $t->userTask;
            $pct = $ut ? $ut->getProgressPercent() : 0;
            if ($ut && $ut->status === 'claimed') {
                return 'claimed';
            }
            if (($ut && $ut->status === 'completed') || ($t->action !== 'custom' && $pct >= 100)) {
                return 'completed';
            }
            return 'in_progress';
        })),

        // Toggle ẩn/hiện bảng hướng dẫn chi tiết của từng nhiệm vụ
        toggleGuide(id) {
            if (this.openGuides.includes(id)) {
                this.openGuides = this.openGuides.filter(g => g !== id);
            } else {
                this.openGuides.push(id);
            }
        },

        // Xác định điều kiện lọc hiển thị cho các card nhiệm vụ
        shouldShow(status) {
            if (this.filter === 'all') return true;
            return this.filter === status;
        },

        // Kiểm tra xem có nhiệm vụ nào đáp ứng bộ lọc hiện tại hay không để hiển thị hộp thông báo rỗng
        hasVisibleTasks() {
            if (this.filter === 'all') return this.taskStatuses.length > 0;
            return this.taskStatuses.includes(this.filter);
        },

        // Mở hộp thoại để thành viên báo đã hoàn thành nhiệm vụ thủ công
        openSubmitModal(taskId, taskTitle) {
            this.submitTaskId    = taskId;
            this.submitTaskTitle = taskTitle;
            this.submitNote      = '';
            this.submitModalOpen = true;
            this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
        },

        closeSubmitModal() {
            if (this.submitting) return; // Không cho đóng khi đang gửi để tránh gửi trùng
            this.submitModalOpen = false;
        },

        // Gửi yêu cầu xác nhận hoàn thành lên máy chủ để quản trị viên kiểm duyệt
        sendSubmit() {
            if (this.submitting || !this.submitTaskId) return;
            this.submitting = true;

            fetch(`${this.claimBaseUrl}/${this.submitTaskId}/submit`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ note: this.submitNote })
            })
            .then(r => r.json())
            .then(data => {
                this.submitting = false;
                if (data.success) {
                    window.location.reload();
                } else {
                    alert(data.message || '{{ __('Có lỗi xảy ra. Vui lòng thử lại.') }}');
                    this.submitModalOpen = false;
                }
            })
            .catch(() => {
                this.submitting = false;
                alert('{{ __('Có lỗi kết nối. Vui lòng thử lại.') }}');
            });
        },

        // Gửi yêu cầu AJAX POST để nhận thưởng của nhiệm vụ đã hoàn thành
        claimReward(taskId) {
            if (this.claimingId) return;
            this.claimingId = taskId;

            fetch(`${this.claimBaseUrl}/${taskId}/claim`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                }
            })
            .then(r => r.json())
            .then(data => {
                this.claimingId = null;
                if (data.success) {
                    window.location.reload();
                } else {
                    alert(data.message || '{{ __('Có lỗi xảy ra. Vui lòng thử lại.') }}');
                    window.location.reload();
                }
            })
            .catch(() => {
                this.claimingId = null;
                alert('{{ __('Có lỗi kết nối. Vui lòng thử lại.') }}');
            });
        }
    };
}
</script>
@endsection

@endsection
