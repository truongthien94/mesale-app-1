@extends('layouts.admin')

@section('title', __('Quản Lý Nhiệm Vụ') . ' - ' . $siteName)

@php
    $tab = request('tab', 'tasks');
    $hasFilter = request('search') || request('type') || request('action') ||
        (request('status') !== null && request('status') !== '') ||
        (request('limit') && request('limit') != 15) ||
        request('task_id');

    $typeOptions = [
        'daily'    => 'Hằng ngày',
        'weekly'   => 'Hằng tuần',
        'one_time' => 'Một lần',
    ];
    $actionOptions = [
        'profile'      => 'Hoàn thiện hồ sơ',
        'referral'     => 'Mời bạn bè',
        'cashback'     => 'Đơn hoàn tiền',
        'checkin'      => 'Điểm danh',
        'withdraw'     => 'Rút tiền',
        'save_product' => 'Lưu sản phẩm',
        'custom'       => 'Tùy chỉnh (thủ công)',
    ];
    $colorOptions = [
        'orange' => 'Cam',
        'blue'   => 'Xanh dương',
        'green'  => 'Xanh lá',
        'purple' => 'Tím',
        'red'    => 'Đỏ',
        'yellow' => 'Vàng',
        'pink'   => 'Hồng',
        'cyan'   => 'Xanh lam',
    ];
    $iconOptions = [
        'star','trophy','zap','gift','target','flame','award','medal','crown','rocket',
        'diamond','heart','shield','lock','check-circle','calendar-check','users',
        'shopping-bag','link','bookmark','wallet','ticket'
    ];
@endphp

@section('content')
@php $tasksEnabled = \App\Models\Setting::getVal('tasks_enabled', '0') === '1'; @endphp
<div class="space-y-6" x-data="taskAdminHandler()">

    {{-- Banner cảnh báo khi tính năng đang tắt --}}
    @if(!$tasksEnabled)
    <div class="flex items-center gap-3 px-4 py-3 rounded-2xl bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-900/30 text-amber-800 dark:text-amber-300">
        <i data-lucide="eye-off" class="w-4 h-4 shrink-0 text-amber-500"></i>
        <p class="text-xs font-semibold flex-1">{{ __('Tính năng Nhiệm vụ nhận thưởng đang bị tắt — thành viên không thể xem. Bật lên để kích hoạt.') }}</p>
    </div>
    @endif

    {{-- Tiêu đề trang --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white uppercase tracking-tight flex items-center gap-2">
                <span class="w-1.5 h-6 rounded-full bg-gradient-to-b from-shopee to-shopee-light shrink-0"></span>
                {{ __('Quản Lý Nhiệm Vụ') }}
            </h1>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                {{ __('Tạo và quản lý các nhiệm vụ để người dùng hoàn thành và nhận phần thưởng.') }}
            </p>
        </div>
        <div class="flex items-center gap-2 flex-wrap">
            {{-- Nút Thống kê mở Modal chart thống kê nhiệm vụ --}}
            <button @click="openTaskStatsModal()"
                    class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-semibold text-gray-700 bg-white hover:bg-gray-50 border border-gray-200 dark:border-slate-800 dark:bg-slate-900 dark:text-gray-300 dark:hover:bg-slate-800 rounded-xl transition-all shadow-sm">
                <i data-lucide="bar-chart-3" class="w-4 h-4 text-indigo-500"></i>
                {{ __('Thống kê') }}
            </button>

            {{-- Nút Cấu hình mở Modal cấu hình --}}
            <button @click="openConfig()"
                    class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-semibold text-gray-700 bg-white hover:bg-gray-50 border border-gray-200 dark:border-slate-800 dark:bg-slate-900 dark:text-gray-300 dark:hover:bg-slate-800 rounded-xl transition-all shadow-sm">
                <i data-lucide="settings" class="w-4 h-4"></i>
                {{ __('Cấu hình') }}
            </button>

            <button @click="showFilter = !showFilter"
                    class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-semibold rounded-xl border border-gray-200 dark:border-slate-800 transition-all shadow-sm"
                    :class="showFilter ? 'bg-shopee text-white border-shopee' : 'bg-white dark:bg-slate-900 hover:bg-gray-50 text-gray-700 dark:text-slate-300'">
                <i data-lucide="filter" class="w-4 h-4"></i>
                <span>{{ __('Bộ lọc') }}</span>
                @if($hasFilter)
                    <span class="w-1.5 h-1.5 rounded-full bg-red-500 animate-pulse"></span>
                @endif
            </button>

            @if($tab === 'tasks')
            <button @click="openCreateModal()"
                    class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-semibold bg-shopee hover:bg-shopee-dark text-white rounded-xl transition-all shadow-sm">
                <i data-lucide="plus" class="w-4 h-4"></i>
                {{ __('Thêm nhiệm vụ') }}
            </button>
            @endif
        </div>
    </div>

    {{-- Thống kê tổng quan --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-gray-100 dark:border-slate-800 p-4 shadow-sm">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-shopee/10 flex items-center justify-center text-shopee shrink-0">
                    <i data-lucide="list-checks" class="w-5 h-5"></i>
                </div>
                <div>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('Tổng nhiệm vụ') }}</p>
                    <p class="text-xl font-bold text-gray-900 dark:text-white">{{ number_format($totalTasks) }}</p>
                </div>
            </div>
        </div>
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-gray-100 dark:border-slate-800 p-4 shadow-sm">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-green-100 dark:bg-green-900/30 flex items-center justify-center text-green-600 dark:text-green-400 shrink-0">
                    <i data-lucide="check-circle" class="w-5 h-5"></i>
                </div>
                <div>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('Đang hoạt động') }}</p>
                    <p class="text-xl font-bold text-gray-900 dark:text-white">{{ number_format($activeTasks) }}</p>
                </div>
            </div>
        </div>
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-gray-100 dark:border-slate-800 p-4 shadow-sm">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center text-amber-600 dark:text-amber-400 shrink-0">
                    <i data-lucide="clock" class="w-5 h-5"></i>
                </div>
                <div>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('Chờ nhận thưởng') }}</p>
                    <p class="text-xl font-bold text-gray-900 dark:text-white">{{ number_format($pendingClaim) }}</p>
                </div>
            </div>
        </div>
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-gray-100 dark:border-slate-800 p-4 shadow-sm">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center text-purple-600 dark:text-purple-400 shrink-0">
                    <i data-lucide="gift" class="w-5 h-5"></i>
                </div>
                <div>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('Đã nhận thưởng') }}</p>
                    <p class="text-xl font-bold text-gray-900 dark:text-white">{{ number_format($totalClaimed) }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Tabs --}}
    <div class="flex gap-1 border-b border-gray-200 dark:border-slate-800">
        <a href="{{ route('admin.tasks.index', ['tab' => 'tasks']) }}"
           class="px-4 py-2.5 text-xs font-semibold border-b-2 transition-all {{ $tab === 'tasks' ? 'border-shopee text-shopee' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300' }}">
            <span class="flex items-center gap-1.5">
                <i data-lucide="list-checks" class="w-3.5 h-3.5"></i>
                {{ __('Danh sách nhiệm vụ') }}
                <span class="px-1.5 py-0.5 text-[9px] font-bold rounded-full bg-gray-100 dark:bg-slate-800 text-gray-600 dark:text-gray-400">{{ $totalTasks }}</span>
            </span>
        </a>
        <a href="{{ route('admin.tasks.index', ['tab' => 'submissions']) }}"
           class="px-4 py-2.5 text-xs font-semibold border-b-2 transition-all {{ $tab === 'submissions' ? 'border-shopee text-shopee' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300' }}">
            <span class="flex items-center gap-1.5">
                <i data-lucide="users" class="w-3.5 h-3.5"></i>
                {{ __('Tiến độ thành viên') }}
                {{-- Số yêu cầu xác nhận của nhiệm vụ thủ công đang chờ Admin kiểm duyệt --}}
                @if($pendingReview > 0)
                    <span class="px-1.5 py-0.5 text-[9px] font-bold rounded-full bg-orange-100 dark:bg-orange-900/30 text-orange-700 dark:text-orange-400 animate-pulse" title="{{ __('Yêu cầu chờ duyệt') }}">{{ $pendingReview }}</span>
                @endif
                @if($pendingClaim > 0)
                    <span class="px-1.5 py-0.5 text-[9px] font-bold rounded-full bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400" title="{{ __('Chờ thành viên nhận thưởng') }}">{{ $pendingClaim }}</span>
                @endif
            </span>
        </a>
    </div>

    {{-- Bộ lọc --}}
    <div x-show="showFilter" x-cloak
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0 -translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0">
        <form method="GET" action="{{ route('admin.tasks.index') }}" class="bg-white dark:bg-slate-900 rounded-2xl border border-gray-100 dark:border-slate-800 p-4 shadow-sm">
            <input type="hidden" name="tab" value="{{ $tab }}">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-gray-600 dark:text-slate-400 mb-1.5">{{ __('Tìm kiếm') }}</label>
                    <input type="text" name="search" value="{{ request('search') }}"
                           placeholder="{{ __('Tiêu đề, mô tả, email...') }}"
                           class="w-full px-3 py-2 text-xs rounded-xl border border-gray-200 dark:border-slate-700 bg-gray-50 dark:bg-slate-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-shopee/20 focus:border-shopee outline-none transition-all">
                </div>

                @if($tab === 'tasks')
                <div>
                    <label class="block text-xs font-semibold text-gray-600 dark:text-slate-400 mb-1.5">{{ __('Loại') }}</label>
                    <select name="type" class="w-full px-3 py-2 text-xs rounded-xl border border-gray-200 dark:border-slate-700 bg-gray-50 dark:bg-slate-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-shopee/20 outline-none">
                        <option value="">{{ __('Tất cả') }}</option>
                        @foreach($typeOptions as $val => $label)
                            <option value="{{ $val }}" {{ request('type') === $val ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 dark:text-slate-400 mb-1.5">{{ __('Hành động') }}</label>
                    <select name="action" class="w-full px-3 py-2 text-xs rounded-xl border border-gray-200 dark:border-slate-700 bg-gray-50 dark:bg-slate-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-shopee/20 outline-none">
                        <option value="">{{ __('Tất cả') }}</option>
                        @foreach($actionOptions as $val => $label)
                            <option value="{{ $val }}" {{ request('action') === $val ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                @else
                <div>
                    <label class="block text-xs font-semibold text-gray-600 dark:text-slate-400 mb-1.5">{{ __('Nhiệm vụ') }}</label>
                    <select name="task_id" class="w-full px-3 py-2 text-xs rounded-xl border border-gray-200 dark:border-slate-700 bg-gray-50 dark:bg-slate-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-shopee/20 outline-none">
                        <option value="">{{ __('Tất cả') }}</option>
                        @foreach($allTasks as $t)
                            <option value="{{ $t->id }}" {{ request('task_id') == $t->id ? 'selected' : '' }}>{{ $t->title }}</option>
                        @endforeach
                    </select>
                </div>
                @endif

                <div>
                    <label class="block text-xs font-semibold text-gray-600 dark:text-slate-400 mb-1.5">{{ __('Trạng thái') }}</label>
                    <select name="status" class="w-full px-3 py-2 text-xs rounded-xl border border-gray-200 dark:border-slate-700 bg-gray-50 dark:bg-slate-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-shopee/20 outline-none">
                        <option value="">{{ __('Tất cả') }}</option>
                        @if($tab === 'tasks')
                            <option value="1" {{ request('status') === '1' ? 'selected' : '' }}>{{ __('Hoạt động') }}</option>
                            <option value="0" {{ request('status') === '0' ? 'selected' : '' }}>{{ __('Tắt') }}</option>
                        @else
                            <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>{{ __('Chờ duyệt') }}</option>
                            <option value="in_progress" {{ request('status') === 'in_progress' ? 'selected' : '' }}>{{ __('Đang thực hiện') }}</option>
                            <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>{{ __('Hoàn thành (chờ nhận)') }}</option>
                            <option value="claimed" {{ request('status') === 'claimed' ? 'selected' : '' }}>{{ __('Đã nhận thưởng') }}</option>
                        @endif
                    </select>
                </div>
            </div>

            <div class="flex items-center gap-2 mt-3 pt-3 border-t border-gray-100 dark:border-slate-800">
                <button type="submit" class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-semibold bg-shopee hover:bg-shopee-dark text-white rounded-xl transition-all">
                    <i data-lucide="search" class="w-3.5 h-3.5"></i>
                    {{ __('Tìm kiếm') }}
                </button>
                @if($hasFilter)
                <a href="{{ route('admin.tasks.index', ['tab' => $tab]) }}" class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-semibold text-gray-600 dark:text-gray-400 bg-gray-100 dark:bg-slate-800 hover:bg-gray-200 dark:hover:bg-slate-700 rounded-xl transition-all">
                    <i data-lucide="x" class="w-3.5 h-3.5"></i>
                    {{ __('Xóa bộ lọc') }}
                </a>
                @endif
            </div>
        </form>
    </div>

    {{-- Session messages --}}
    @if(session('success'))
    <div class="p-4 bg-green-50 dark:bg-green-950/20 border border-green-200 dark:border-green-900/30 text-green-800 dark:text-green-300 rounded-2xl flex items-center gap-3 text-xs font-semibold shadow-sm">
        <i data-lucide="check-circle" class="w-4 h-4 shrink-0"></i>
        {{ session('success') }}
    </div>
    @endif
    @if(session('error'))
    <div class="p-4 bg-red-50 dark:bg-red-950/20 border border-red-200 dark:border-red-900/30 text-red-800 dark:text-red-300 rounded-2xl flex items-center gap-3 text-xs font-semibold shadow-sm">
        <i data-lucide="alert-circle" class="w-4 h-4 shrink-0"></i>
        {{ session('error') }}
    </div>
    @endif

    {{-- ===== TAB: TASKS ===== --}}
    @if($tab === 'tasks')
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-gray-100 dark:border-slate-800 shadow-sm overflow-hidden">

        {{-- Toolbar --}}
        <div class="flex items-center justify-between px-5 py-3.5 border-b border-gray-100 dark:border-slate-800 gap-3">
            <div class="flex items-center gap-2">
                <input type="checkbox" x-model="selectAll" @change="toggleSelectAll()" class="rounded border-gray-300 dark:border-slate-600 text-shopee focus:ring-shopee/20">
                <span class="text-xs text-gray-500 dark:text-gray-400">
                    <span x-show="selectedTasks.length === 0">{{ $tasks->total() }} {{ __('nhiệm vụ') }}</span>
                    <span x-show="selectedTasks.length > 0" x-text="selectedTasks.length + ' {{ __('đã chọn') }}'"></span>
                </span>
            </div>
            <div class="flex items-center gap-2" x-show="selectedTasks.length > 0" x-cloak>
                <button @click="openBulkDeleteModal()" class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-semibold text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-950/20 hover:bg-red-100 dark:hover:bg-red-950/40 rounded-xl transition-all border border-red-200 dark:border-red-900/30">
                    <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                    {{ __('Xóa đã chọn') }}
                </button>
            </div>
        </div>

        {{-- Desktop table --}}
        <div class="hidden md:block overflow-x-auto">
            <table class="w-full text-xs">
                <thead>
                    <tr class="border-b border-gray-100 dark:border-slate-800 bg-gray-50/50 dark:bg-slate-800/30">
                        <th class="py-3 px-4 text-left w-8"></th>
                        <th class="py-3 px-4 text-center font-semibold text-gray-600 dark:text-slate-400 w-8" title="{{ __('Kéo thả để sắp xếp thứ tự') }}">#</th>
                        <th class="py-3 px-4 text-left font-semibold text-gray-600 dark:text-slate-400">{{ __('Nhiệm vụ') }}</th>
                        <th class="py-3 px-4 text-left font-semibold text-gray-600 dark:text-slate-400">{{ __('Loại / Hành động') }}</th>
                        <th class="py-3 px-4 text-left font-semibold text-gray-600 dark:text-slate-400">{{ __('Mục tiêu') }}</th>
                        <th class="py-3 px-4 text-left font-semibold text-gray-600 dark:text-slate-400">{{ __('Thưởng') }}</th>
                        <th class="py-3 px-4 text-left font-semibold text-gray-600 dark:text-slate-400">{{ __('Đã chi trả') }}</th>
                        <th class="py-3 px-4 text-left font-semibold text-gray-600 dark:text-slate-400">{{ __('Người dùng') }}</th>
                        <th class="py-3 px-4 text-left font-semibold text-gray-600 dark:text-slate-400">{{ __('Trạng thái') }}</th>
                        <th class="py-3 px-4 text-right font-semibold text-gray-600 dark:text-slate-400">{{ __('Thao tác') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50 dark:divide-slate-800 task-sortable-list">
                    @forelse($tasks as $task)
                    @php $colors = $task->getColorClasses(); @endphp
                    <tr class="hover:bg-gray-50/50 dark:hover:bg-slate-800/30 transition-colors" data-id="{{ $task->id }}">
                        <td class="py-3 px-4">
                            <input type="checkbox" :value="{{ $task->id }}" x-model="selectedTasks" class="rounded border-gray-300 dark:border-slate-600 text-shopee focus:ring-shopee/20">
                        </td>
                        <td class="py-3 px-4">
                            <div class="flex items-center gap-1.5 text-gray-400">
                                <span class="handle cursor-grab active:cursor-grabbing text-gray-300 dark:text-slate-600 hover:text-shopee transition-colors shrink-0" title="{{ __('Kéo thả để sắp xếp') }}">
                                    <i data-lucide="grip-vertical" class="w-4 h-4"></i>
                                </span>
                                <span class="sort-order-label">{{ $task->sort_order }}</span>
                            </div>
                        </td>
                        <td class="py-3 px-4">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-xl {{ $colors['bg'] }} {{ $colors['text'] }} flex items-center justify-center shrink-0">
                                    <i data-lucide="{{ $task->icon ?? 'star' }}" class="w-4 h-4"></i>
                                </div>
                                <div>
                                    <p class="font-semibold text-gray-900 dark:text-white">{{ $task->title }}</p>
                                    @if($task->description_plain)
                                        {{-- Lược bỏ thẻ HTML để dòng mô tả trong bảng danh sách luôn gọn gàng --}}
                                        <p class="text-gray-400 dark:text-slate-500 truncate max-w-[200px]">{{ $task->description_plain }}</p>
                                    @endif
                                    @if($task->start_at || $task->end_at)
                                    <p class="text-[10px] text-gray-400 mt-0.5">
                                        @if($task->start_at) {{ $task->start_at->format('d/m/Y') }} @endif
                                        @if($task->start_at && $task->end_at) → @endif
                                        @if($task->end_at) {{ $task->end_at->format('d/m/Y') }} @endif
                                    </p>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="py-3 px-4">
                            <div class="space-y-1">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold {{ $task->getTypeBadgeClass() }}">
                                    {{ $task->getTypeLabel() }}
                                </span>
                                <p class="text-gray-500 dark:text-gray-400 text-[10px]">{{ $task->getActionLabel() }}</p>
                            </div>
                        </td>
                        <td class="py-3 px-4 font-semibold text-gray-700 dark:text-gray-300">
                            <div>{{ number_format($task->target_count) }}x</div>
                            @if(($task->action === 'cashback' || ($task->action === 'referral' && $task->referral_require_order)) && $task->min_order_amount > 0)
                                <div class="text-[10px] text-red-500 mt-0.5 whitespace-nowrap" title="{{ __('Tiền hoàn tối thiểu') }}">
                                    ≥ {{ number_format($task->min_order_amount) }}đ
                                </div>
                            @endif
                        </td>
                        <td class="py-3 px-4">
                            <span class="font-bold text-green-600 dark:text-green-400">+{{ number_format($task->reward_amount) }}đ</span>
                        </td>
                        <td class="py-3 px-4">
                            <span class="font-semibold text-red-500 dark:text-red-400">{{ number_format($task->claimed_tasks_count * $task->reward_amount) }}đ</span>
                        </td>
                        <td class="py-3 px-4">
                            <span class="text-gray-600 dark:text-gray-400">{{ number_format($task->user_tasks_count) }}</span>
                        </td>
                        <td class="py-3 px-4">
                            <button @click="toggleStatus({{ $task->id }}, {{ $task->is_active ? 'true' : 'false' }})"
                                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold transition-all {{ $task->is_active ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : 'bg-gray-100 text-gray-500 dark:bg-slate-800 dark:text-gray-500' }}">
                                <span class="w-1.5 h-1.5 rounded-full {{ $task->is_active ? 'bg-green-500 animate-pulse' : 'bg-gray-400' }}"></span>
                                {{ $task->is_active ? __('Hoạt động') : __('Tắt') }}
                            </button>
                        </td>
                        <td class="py-3 px-4 text-right">
                            <div class="flex items-center justify-end gap-1">
                                {{-- Nút nhân bản nhiệm vụ nhanh --}}
                                <button @click="duplicateTask({{ json_encode([
                                    'id'                     => $task->id,
                                    'title'                  => $task->title,
                                    'description'            => $task->description_html,
                                    'guide'                  => $task->guide_html,
                                    'type'                   => $task->type,
                                    'action'                 => $task->action,
                                    'referral_require_order' => $task->referral_require_order ? 1 : 0,
                                    'target_count'           => $task->target_count,
                                    'min_order_amount'       => $task->min_order_amount,
                                    'reward_amount'          => $task->reward_amount,
                                    'is_active'              => $task->is_active ? 1 : 0,
                                    'start_at'               => $task->start_at ? $task->start_at->format('Y-m-d') : null,
                                    'end_at'                 => $task->end_at ? $task->end_at->format('Y-m-d') : null,
                                    'icon'                   => $task->icon,
                                    'badge_color'            => $task->badge_color,
                                    'sort_order'             => $task->sort_order,
                                ]) }})"
                                        class="p-1.5 text-emerald-600 dark:text-emerald-400 hover:bg-emerald-50 dark:hover:bg-emerald-900/20 rounded-lg transition-all" title="{{ __('Nhân bản') }}">
                                    <i data-lucide="copy" class="w-3.5 h-3.5"></i>
                                </button>
                                <button @click="openEditModal({{ json_encode([
                                    'id'                     => $task->id,
                                    'title'                  => $task->title,
                                    'description'            => $task->description_html,
                                    'guide'                  => $task->guide_html,
                                    'type'                   => $task->type,
                                    'action'                 => $task->action,
                                    'referral_require_order' => $task->referral_require_order ? 1 : 0,
                                    'target_count'           => $task->target_count,
                                    'min_order_amount'       => $task->min_order_amount,
                                    'reward_amount'          => $task->reward_amount,
                                    'is_active'              => $task->is_active ? 1 : 0,
                                    'start_at'               => $task->start_at ? $task->start_at->format('Y-m-d') : null,
                                    'end_at'                 => $task->end_at ? $task->end_at->format('Y-m-d') : null,
                                    'icon'                   => $task->icon,
                                    'badge_color'            => $task->badge_color,
                                    'sort_order'             => $task->sort_order,
                                ]) }})"
                                        class="p-1.5 text-blue-600 dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg transition-all" title="{{ __('Chỉnh sửa') }}">
                                    <i data-lucide="pencil" class="w-3.5 h-3.5"></i>
                                </button>
                                <a href="{{ route('admin.tasks.index', ['tab' => 'submissions', 'task_id' => $task->id]) }}"
                                   class="p-1.5 text-purple-600 dark:text-purple-400 hover:bg-purple-50 dark:hover:bg-purple-900/20 rounded-lg transition-all" title="{{ __('Xem tiến độ') }}">
                                    <i data-lucide="users" class="w-3.5 h-3.5"></i>
                                </a>
                                <button @click="openDeleteModal({{ $task->id }}, '{{ addslashes($task->title) }}')"
                                        class="p-1.5 text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-all" title="{{ __('Xóa') }}">
                                    <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="10" class="py-16 text-center">
                            <div class="flex flex-col items-center gap-3 text-gray-400 dark:text-slate-500">
                                <i data-lucide="list-checks" class="w-10 h-10 opacity-30"></i>
                                <p class="text-sm font-medium">{{ __('Chưa có nhiệm vụ nào.') }}</p>
                                <button @click="openCreateModal()" class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-semibold bg-shopee text-white rounded-xl hover:bg-shopee-dark transition-all">
                                    <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                                    {{ __('Tạo nhiệm vụ đầu tiên') }}
                                </button>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Mobile cards --}}
        <div class="block md:hidden divide-y divide-gray-50 dark:divide-slate-800">
            @forelse($tasks as $task)
            @php $colors = $task->getColorClasses(); @endphp
            <div class="p-4 space-y-3">
                <div class="flex items-start gap-3">
                    <input type="checkbox" :value="{{ $task->id }}" x-model="selectedTasks" class="mt-1 rounded border-gray-300 dark:border-slate-600 text-shopee">
                    <div class="w-10 h-10 rounded-xl {{ $colors['bg'] }} {{ $colors['text'] }} flex items-center justify-center shrink-0">
                        <i data-lucide="{{ $task->icon ?? 'star' }}" class="w-5 h-5"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="font-bold text-sm text-gray-900 dark:text-white">{{ $task->title }}</p>
                        @if($task->description_plain)
                        {{-- Lược bỏ thẻ HTML để dòng mô tả trên thẻ danh sách mobile luôn gọn gàng --}}
                        <p class="text-xs text-gray-400 dark:text-slate-500 truncate">{{ $task->description_plain }}</p>
                        @endif
                        <div class="flex flex-wrap gap-1.5 mt-1.5">
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $task->getTypeBadgeClass() }}">{{ $task->getTypeLabel() }}</span>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-medium bg-gray-100 dark:bg-slate-800 text-gray-600 dark:text-gray-400">{{ $task->getActionLabel() }}</span>
                        </div>
                    </div>
                    <button @click="toggleStatus({{ $task->id }}, {{ $task->is_active ? 'true' : 'false' }})"
                            class="shrink-0 w-8 h-8 rounded-xl {{ $task->is_active ? 'bg-green-100 text-green-600 dark:bg-green-900/30' : 'bg-gray-100 text-gray-400 dark:bg-slate-800' }} flex items-center justify-center">
                        <i data-lucide="{{ $task->is_active ? 'eye' : 'eye-off' }}" class="w-4 h-4"></i>
                    </button>
                </div>
                <div class="flex items-center justify-between text-xs">
                    <div class="flex items-center gap-3 flex-wrap">
                        <span class="text-gray-500 dark:text-gray-400">Mục tiêu: <span class="font-bold text-gray-700 dark:text-gray-300">{{ $task->target_count }}x</span>
                        @if(($task->action === 'cashback' || ($task->action === 'referral' && $task->referral_require_order)) && $task->min_order_amount > 0)
                            <span class="text-red-500 text-[10px] font-bold"> (≥{{ number_format($task->min_order_amount) }}đ)</span>
                        @endif
                        </span>
                        <span class="font-bold text-green-600 dark:text-green-400">+{{ number_format($task->reward_amount) }}đ</span>
                        <span class="text-gray-500 dark:text-gray-400">| {{ __('Đã chi') }}: <span class="font-bold text-red-500 dark:text-red-400">{{ number_format($task->claimed_tasks_count * $task->reward_amount) }}đ</span></span>
                    </div>
                    <div class="flex items-center gap-1">
                        {{-- Nút nhân bản nhiệm vụ nhanh trên mobile --}}
                        <button @click="duplicateTask({{ json_encode(['id'=>$task->id,'title'=>$task->title,'description'=>$task->description_html,'guide'=>$task->guide_html,'type'=>$task->type,'action'=>$task->action,'referral_require_order'=>$task->referral_require_order?1:0,'target_count'=>$task->target_count,'min_order_amount'=>$task->min_order_amount,'reward_amount'=>$task->reward_amount,'is_active'=>$task->is_active?1:0,'start_at'=>$task->start_at?$task->start_at->format('Y-m-d'):null,'end_at'=>$task->end_at?$task->end_at->format('Y-m-d'):null,'icon'=>$task->icon,'badge_color'=>$task->badge_color,'sort_order'=>$task->sort_order]) }})"
                                class="p-1.5 text-emerald-600 dark:text-emerald-400 hover:bg-emerald-50 dark:hover:bg-emerald-900/20 rounded-lg" title="{{ __('Nhân bản') }}">
                            <i data-lucide="copy" class="w-4 h-4"></i>
                        </button>
                        <button @click="openEditModal({{ json_encode(['id'=>$task->id,'title'=>$task->title,'description'=>$task->description_html,'guide'=>$task->guide_html,'type'=>$task->type,'action'=>$task->action,'referral_require_order'=>$task->referral_require_order?1:0,'target_count'=>$task->target_count,'min_order_amount'=>$task->min_order_amount,'reward_amount'=>$task->reward_amount,'is_active'=>$task->is_active?1:0,'start_at'=>$task->start_at?$task->start_at->format('Y-m-d'):null,'end_at'=>$task->end_at?$task->end_at->format('Y-m-d'):null,'icon'=>$task->icon,'badge_color'=>$task->badge_color,'sort_order'=>$task->sort_order]) }})"
                                class="p-1.5 text-blue-600 dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg">
                            <i data-lucide="pencil" class="w-4 h-4"></i>
                        </button>
                        <button @click="openDeleteModal({{ $task->id }}, '{{ addslashes($task->title) }}')"
                                class="p-1.5 text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg">
                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                        </button>
                    </div>
                </div>
            </div>
            @empty
            <div class="py-16 text-center text-gray-400 dark:text-slate-500">
                <i data-lucide="list-checks" class="w-10 h-10 mx-auto opacity-30 mb-3"></i>
                <p class="text-sm">{{ __('Chưa có nhiệm vụ nào.') }}</p>
            </div>
            @endforelse
        </div>

        {{-- Pagination --}}
        @if($tasks->hasPages())
        <div class="px-5 py-4 border-t border-gray-100 dark:border-slate-800">
            {{ $tasks->links() }}
        </div>
        @endif
    </div>
    @endif

    {{-- ===== TAB: SUBMISSIONS ===== --}}
    @if($tab === 'submissions')
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-gray-100 dark:border-slate-800 shadow-sm overflow-hidden">
        {{-- Desktop table --}}
        <div class="hidden md:block overflow-x-auto">
            <table class="w-full text-xs">
                <thead>
                    <tr class="border-b border-gray-100 dark:border-slate-800 bg-gray-50/50 dark:bg-slate-800/30">
                        <th class="py-3 px-4 text-left font-semibold text-gray-600 dark:text-slate-400">{{ __('Thành viên') }}</th>
                        <th class="py-3 px-4 text-left font-semibold text-gray-600 dark:text-slate-400">{{ __('Nhiệm vụ') }}</th>
                        <th class="py-3 px-4 text-left font-semibold text-gray-600 dark:text-slate-400">{{ __('Tiến độ') }}</th>
                        <th class="py-3 px-4 text-left font-semibold text-gray-600 dark:text-slate-400">{{ __('Trạng thái') }}</th>
                        <th class="py-3 px-4 text-left font-semibold text-gray-600 dark:text-slate-400">{{ __('Chu kỳ') }}</th>
                        <th class="py-3 px-4 text-left font-semibold text-gray-600 dark:text-slate-400">{{ __('Thời gian') }}</th>
                        <th class="py-3 px-4 text-right font-semibold text-gray-600 dark:text-slate-400">{{ __('Thao tác') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50 dark:divide-slate-800">
                    @forelse($submissions as $sub)
                    @php $colors = optional($sub->task)->getColorClasses() ?? \App\Models\Task::$colorClasses['orange']; @endphp
                    <tr class="hover:bg-gray-50/50 dark:hover:bg-slate-800/30 transition-colors">
                        <td class="py-3 px-4">
                            @if($sub->user)
                            <div class="flex items-center gap-2">
                                <div class="w-7 h-7 rounded-full bg-orange-600 text-white flex items-center justify-center font-bold text-[10px] shrink-0">
                                    {{ strtoupper(substr($sub->user->name, 0, 1)) }}
                                </div>
                                <div>
                                    <p class="font-semibold text-gray-900 dark:text-white">{{ $sub->user->name }}</p>
                                    <p class="text-gray-400 dark:text-slate-500">{{ $sub->user->email }}</p>
                                </div>
                            </div>
                            @else
                            <span class="text-gray-400">—</span>
                            @endif
                        </td>
                        <td class="py-3 px-4">
                            @if($sub->task)
                            <div class="flex items-center gap-2">
                                <div class="w-7 h-7 rounded-lg {{ $colors['bg'] }} {{ $colors['text'] }} flex items-center justify-center shrink-0">
                                    <i data-lucide="{{ $sub->task->icon ?? 'star' }}" class="w-3.5 h-3.5"></i>
                                </div>
                                <div>
                                    <p class="font-semibold text-gray-900 dark:text-white">{{ $sub->task->title }}</p>
                                    <p class="text-gray-400 dark:text-slate-500">{{ $sub->task->getTypeLabel() }}</p>
                                    {{-- Ghi chú/bằng chứng thành viên gửi kèm khi tự xác nhận hoàn thành --}}
                                    @if($sub->submit_note)
                                    <p class="mt-1 max-w-[240px] text-[10px] text-orange-600 dark:text-orange-400 bg-orange-50 dark:bg-orange-950/20 border border-orange-100 dark:border-orange-900/30 rounded-lg px-2 py-1 break-words">
                                        <i data-lucide="message-square-quote" class="w-3 h-3 inline-block align-[-2px]"></i>
                                        {{ $sub->submit_note }}
                                    </p>
                                    @endif
                                    {{-- Lý do quản trị viên đã từ chối ở lần gửi trước --}}
                                    @if($sub->reject_reason)
                                    <p class="mt-1 max-w-[240px] text-[10px] text-red-600 dark:text-red-400 break-words">
                                        <i data-lucide="x-circle" class="w-3 h-3 inline-block align-[-2px]"></i>
                                        {{ __('Đã từ chối') }}: {{ $sub->reject_reason }}
                                    </p>
                                    @endif
                                </div>
                            </div>
                            @else
                            <span class="text-gray-400">—</span>
                            @endif
                        </td>
                        <td class="py-3 px-4">
                            @if($sub->task)
                            <div class="space-y-1 min-w-[100px]">
                                <div class="flex items-center justify-between">
                                    <span class="font-semibold text-gray-700 dark:text-gray-300">{{ $sub->progress }}/{{ $sub->task->target_count }}</span>
                                    <span class="text-gray-400">{{ $sub->getProgressPercent() }}%</span>
                                </div>
                                <div class="h-1.5 bg-gray-100 dark:bg-slate-800 rounded-full overflow-hidden">
                                    <div class="h-full bg-shopee rounded-full transition-all" style="width: {{ $sub->getProgressPercent() }}%"></div>
                                </div>
                            </div>
                            @else
                            <span class="text-gray-400">—</span>
                            @endif
                        </td>
                        <td class="py-3 px-4">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-bold {{ $sub->getStatusBadgeClass() }}">
                                {{ $sub->getStatusLabel() }}
                            </span>
                        </td>
                        <td class="py-3 px-4 text-gray-500 dark:text-gray-400">
                            {{ $sub->period_key ?? __('Một lần') }}
                        </td>
                        <td class="py-3 px-4 text-gray-500 dark:text-gray-400">
                            <div class="space-y-0.5">
                                {{-- Thời điểm thành viên gửi yêu cầu xác nhận hoàn thành --}}
                                @if($sub->submitted_at)
                                <p class="text-orange-600 dark:text-orange-400">✋ {{ $sub->submitted_at->format('d/m H:i') }}</p>
                                @endif
                                @if($sub->completed_at)
                                <p>✓ {{ $sub->completed_at->format('d/m H:i') }}</p>
                                @endif
                                @if($sub->claimed_at)
                                <p class="text-green-600 dark:text-green-400">↳ {{ $sub->claimed_at->format('d/m H:i') }}</p>
                                @endif
                                @if(!$sub->completed_at && !$sub->submitted_at)
                                <p class="text-gray-300 dark:text-slate-600">{{ $sub->created_at->format('d/m H:i') }}</p>
                                @endif
                            </div>
                        </td>
                        <td class="py-3 px-4 text-right">
                            <div class="flex items-center justify-end gap-1">
                                @if(in_array($sub->status, ['pending', 'in_progress']) && optional($sub->task)->action === 'custom')
                                <form method="POST" action="{{ route('admin.tasks.submissions.approve', $sub->id) }}">
                                    @csrf
                                    <button type="submit" class="inline-flex items-center gap-1 px-2.5 py-1 text-[10px] font-bold text-green-700 dark:text-green-400 bg-green-50 dark:bg-green-900/20 hover:bg-green-100 dark:hover:bg-green-900/40 rounded-lg transition-all border border-green-200 dark:border-green-900/30">
                                        <i data-lucide="check" class="w-3 h-3"></i>
                                        {{ __('Duyệt') }}
                                    </button>
                                </form>
                                @endif
                                @if(in_array($sub->status, ['pending', 'in_progress', 'completed']))
                                <button @click="openRejectModal({{ $sub->id }})" class="inline-flex items-center gap-1 px-2.5 py-1 text-[10px] font-bold text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/20 hover:bg-red-100 dark:hover:bg-red-900/40 rounded-lg transition-all border border-red-200 dark:border-red-900/30">
                                    <i data-lucide="x" class="w-3 h-3"></i>
                                    {{ $sub->status === 'pending' ? __('Từ chối') : __('Reset') }}
                                </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="py-16 text-center text-gray-400 dark:text-slate-500">
                            <i data-lucide="users" class="w-10 h-10 mx-auto opacity-30 mb-3"></i>
                            <p class="text-sm">{{ __('Chưa có thành viên nào tham gia nhiệm vụ.') }}</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Mobile cards --}}
        <div class="block md:hidden divide-y divide-gray-50 dark:divide-slate-800">
            @forelse($submissions as $sub)
            <div class="p-4 space-y-3">
                <div class="flex items-start justify-between gap-3">
                    <div class="space-y-1">
                        <p class="font-bold text-sm text-gray-900 dark:text-white">{{ optional($sub->user)->name ?? '—' }}</p>
                        <p class="text-xs text-gray-400">{{ optional($sub->user)->email }}</p>
                        <p class="text-xs font-semibold text-gray-600 dark:text-gray-400">{{ optional($sub->task)->title ?? '—' }}</p>
                    </div>
                    <span class="shrink-0 inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-bold {{ $sub->getStatusBadgeClass() }}">
                        {{ $sub->getStatusLabel() }}
                    </span>
                </div>
                @if($sub->task)
                <div class="space-y-1">
                    <div class="flex items-center justify-between text-xs">
                        <span class="text-gray-500">{{ $sub->progress }}/{{ $sub->task->target_count }}</span>
                        <span class="text-gray-500">{{ $sub->getProgressPercent() }}%</span>
                    </div>
                    <div class="h-2 bg-gray-100 dark:bg-slate-800 rounded-full overflow-hidden">
                        <div class="h-full bg-shopee rounded-full" style="width: {{ $sub->getProgressPercent() }}%"></div>
                    </div>
                </div>
                @endif
                {{-- Ghi chú thành viên gửi kèm và lý do từ chối trước đó --}}
                @if($sub->submit_note)
                <p class="text-[11px] text-orange-600 dark:text-orange-400 bg-orange-50 dark:bg-orange-950/20 border border-orange-100 dark:border-orange-900/30 rounded-xl px-3 py-2 break-words">
                    <span class="font-semibold">{{ __('Ghi chú') }}:</span> {{ $sub->submit_note }}
                    @if($sub->submitted_at)
                    <span class="block mt-0.5 text-orange-500/80">{{ $sub->submitted_at->format('H:i d/m/Y') }}</span>
                    @endif
                </p>
                @endif
                @if($sub->reject_reason)
                <p class="text-[11px] text-red-600 dark:text-red-400 break-words">
                    <span class="font-semibold">{{ __('Đã từ chối') }}:</span> {{ $sub->reject_reason }}
                </p>
                @endif

                <div class="flex items-center gap-2">
                    @if(in_array($sub->status, ['pending', 'in_progress']) && optional($sub->task)->action === 'custom')
                    <form method="POST" action="{{ route('admin.tasks.submissions.approve', $sub->id) }}" class="flex-1">
                        @csrf
                        <button type="submit" class="w-full inline-flex items-center justify-center gap-1 px-3 py-1.5 text-xs font-bold text-green-700 dark:text-green-400 bg-green-50 dark:bg-green-900/20 hover:bg-green-100 dark:hover:bg-green-900/40 rounded-xl border border-green-200 dark:border-green-900/30 transition-all">
                            <i data-lucide="check" class="w-3.5 h-3.5"></i>
                            {{ __('Xác nhận hoàn thành') }}
                        </button>
                    </form>
                    @endif
                    @if(in_array($sub->status, ['pending', 'in_progress', 'completed']))
                    <button @click="openRejectModal({{ $sub->id }})" class="flex-1 inline-flex items-center justify-center gap-1 px-3 py-1.5 text-xs font-bold text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/20 hover:bg-red-100 dark:hover:bg-red-900/40 rounded-xl border border-red-200 dark:border-red-900/30 transition-all">
                        <i data-lucide="x" class="w-3.5 h-3.5"></i>
                        {{ $sub->status === 'pending' ? __('Từ chối') : __('Reset') }}
                    </button>
                    @endif
                </div>
            </div>
            @empty
            <div class="py-12 text-center text-gray-400 dark:text-slate-500">
                <p class="text-sm">{{ __('Chưa có dữ liệu.') }}</p>
            </div>
            @endforelse
        </div>

        @if($submissions->hasPages())
        <div class="px-5 py-4 border-t border-gray-100 dark:border-slate-800">
            {{ $submissions->links() }}
        </div>
        @endif
    </div>
    @endif

{{-- ===== MODAL TẠO/SỬA NHIỆM VỤ ===== --}}
<div x-show="showModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 !mt-0"
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0">
    <div class="absolute inset-0 bg-black/60" @click="closeModal()"></div>
    <div class="relative w-full max-w-2xl max-h-[90vh] overflow-y-auto bg-white dark:bg-slate-900 rounded-3xl shadow-2xl border border-gray-100 dark:border-slate-800"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100">

        <div class="flex items-center justify-between px-6 py-5 border-b border-gray-100 dark:border-slate-800 sticky top-0 bg-white dark:bg-slate-900 z-10 rounded-t-3xl">
            <h3 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
                <i data-lucide="list-checks" class="w-5 h-5 text-shopee"></i>
                <span x-text="editMode ? '{{ __('Chỉnh sửa nhiệm vụ') }}' : '{{ __('Thêm nhiệm vụ mới') }}'"></span>
            </h3>
            <button @click="closeModal()" class="p-1.5 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-slate-800 rounded-xl transition-all">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>

        <!-- Thanh điều hướng các bước (Steps Indicator) -->
        <div class="px-6 py-4 bg-gray-50/50 dark:bg-slate-950/20 border-b border-gray-100 dark:border-slate-800 flex items-center justify-between gap-4 text-[11px] sm:text-xs font-semibold select-none">
            <div class="flex items-center gap-2 cursor-pointer transition-colors" @click="currentStep = 1" :class="currentStep === 1 ? 'text-shopee' : 'text-gray-400 dark:text-slate-500'">
                <span class="w-5 h-5 rounded-full flex items-center justify-center border text-[10px] transition-all" :class="currentStep === 1 ? 'border-shopee bg-shopee text-white font-bold' : 'border-gray-300 dark:border-slate-700 bg-white dark:bg-slate-800'">1</span>
                <span>{{ __('Thông tin chung') }}</span>
            </div>
            <div class="h-[1px] flex-1 bg-gray-200 dark:bg-slate-800"></div>
            <div class="flex items-center gap-2 cursor-pointer transition-colors" @click="if(form.title.trim()) currentStep = 2; else alert('{{ __('Vui lòng nhập tiêu đề nhiệm vụ!') }}')" :class="currentStep === 2 ? 'text-shopee' : 'text-gray-400 dark:text-slate-500'">
                <span class="w-5 h-5 rounded-full flex items-center justify-center border text-[10px] transition-all" :class="currentStep === 2 ? 'border-shopee bg-shopee text-white font-bold' : 'border-gray-300 dark:border-slate-700 bg-white dark:bg-slate-800'">2</span>
                <span>{{ __('Mục tiêu & Điều kiện') }}</span>
            </div>
            <div class="h-[1px] flex-1 bg-gray-200 dark:bg-slate-800"></div>
            <div class="flex items-center gap-2 cursor-pointer transition-colors" @click="if(form.title.trim()) currentStep = 3; else alert('{{ __('Vui lòng nhập tiêu đề nhiệm vụ!') }}')" :class="currentStep === 3 ? 'text-shopee' : 'text-gray-400 dark:text-slate-500'">
                <span class="w-5 h-5 rounded-full flex items-center justify-center border text-[10px] transition-all" :class="currentStep === 3 ? 'border-shopee bg-shopee text-white font-bold' : 'border-gray-300 dark:border-slate-700 bg-white dark:bg-slate-800'">3</span>
                <span>{{ __('Thưởng & Hiển thị') }}</span>
            </div>
        </div>

        {{-- Trước khi gửi form phải đẩy nội dung từ trình soạn thảo trực quan về lại thẻ textarea --}}
        <form :action="editMode ? tasksBaseUrl + '/' + form.id : '{{ route('admin.tasks.store') }}'" method="POST" class="p-6 space-y-4"
              @submit="syncEditors()">
            @csrf
            <template x-if="editMode"><input type="hidden" name="_method" value="PUT"></template>

            <!-- Bước 1: Thông tin cơ bản -->
            <div x-show="currentStep === 1" x-cloak class="space-y-4">
                {{-- Tiêu đề --}}
                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-slate-300 mb-1.5">{{ __('Tiêu đề nhiệm vụ') }} <span class="text-red-500">*</span></label>
                    <input type="text" name="title" x-model="form.title" required maxlength="255"
                           placeholder="{{ __('Ví dụ: Hoàn thiện hồ sơ cá nhân') }}"
                           class="w-full px-3 py-2.5 text-sm rounded-xl border border-gray-200 dark:border-slate-700 bg-gray-50 dark:bg-slate-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-shopee/20 focus:border-shopee outline-none transition-all">
                    @error('title') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>

                {{-- Mô tả ngắn (trình soạn thảo trực quan TinyMCE) --}}
                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-slate-300 mb-1.5">{{ __('Mô tả ngắn') }}</label>
                    <textarea id="task-description-editor" name="description" rows="3"
                              placeholder="{{ __('Mô tả mục tiêu của nhiệm vụ...') }}"
                              class="w-full px-3 py-2.5 text-sm rounded-xl border border-gray-200 dark:border-slate-700 bg-gray-50 dark:bg-slate-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-shopee/20 focus:border-shopee outline-none transition-all resize-none"></textarea>
                    <p class="text-[10px] text-gray-400 mt-1">{{ __('Có thể in đậm, in nghiêng, đổi màu chữ hoặc chèn liên kết cho phần mô tả.') }}</p>
                </div>

                {{-- Hướng dẫn thực hiện (trình soạn thảo trực quan TinyMCE) --}}
                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-slate-300 mb-1.5">{{ __('Hướng dẫn thực hiện') }}</label>
                    <textarea id="task-guide-editor" name="guide" rows="5"
                              placeholder="{{ __('Hướng dẫn chi tiết từng bước để user hoàn thành nhiệm vụ...') }}"
                              class="w-full px-3 py-2.5 text-sm rounded-xl border border-gray-200 dark:border-slate-700 bg-gray-50 dark:bg-slate-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-shopee/20 focus:border-shopee outline-none transition-all resize-none"></textarea>
                    <p class="text-[10px] text-gray-400 mt-1">{{ __('Hỗ trợ danh sách từng bước, chèn liên kết, chèn ảnh minh họa và bảng biểu.') }}</p>
                </div>
            </div>

            <!-- Bước 2: Mục tiêu & Điều kiện -->
            <div x-show="currentStep === 2" x-cloak class="space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    {{-- Loại nhiệm vụ --}}
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-slate-300 mb-1.5">{{ __('Loại nhiệm vụ') }} <span class="text-red-500">*</span></label>
                        <select name="type" x-model="form.type" required
                                class="w-full px-3 py-2.5 text-sm rounded-xl border border-gray-200 dark:border-slate-700 bg-gray-50 dark:bg-slate-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-shopee/20 outline-none">
                            @foreach($typeOptions as $val => $label)
                            <option value="{{ $val }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        <p class="text-[10px] text-gray-400 mt-1">
                            <span x-show="form.type === 'daily'">{{ __('Reset mỗi ngày — user làm lại hàng ngày để nhận thưởng.') }}</span>
                            <span x-show="form.type === 'weekly'">{{ __('Reset mỗi tuần — user làm lại mỗi 7 ngày.') }}</span>
                            <span x-show="form.type === 'one_time'">{{ __('Chỉ làm một lần duy nhất trong đời.') }}</span>
                        </p>
                    </div>

                    {{-- Hành động --}}
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-slate-300 mb-1.5">{{ __('Loại hành động') }} <span class="text-red-500">*</span></label>
                        <select name="action" x-model="form.action" required
                                class="w-full px-3 py-2.5 text-sm rounded-xl border border-gray-200 dark:border-slate-700 bg-gray-50 dark:bg-slate-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-shopee/20 outline-none">
                            @foreach($actionOptions as $val => $label)
                            <option value="{{ $val }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        <p class="text-[10px] text-gray-400 mt-1">
                            <span x-show="form.action === 'custom'">⚠️ {{ __('Hành động thủ công: Admin cần duyệt từng user trong tab Tiến độ.') }}</span>
                            <span x-show="form.action !== 'custom'">{{ __('Tiến độ tự động cập nhật dựa theo dữ liệu hệ thống.') }}</span>
                        </p>

                        {{-- Điều kiện bổ sung cho hành động Mời bạn bè --}}
                        <div x-show="form.action === 'referral'" x-cloak class="mt-3">
                            <label class="flex items-start gap-2.5 cursor-pointer p-3 rounded-xl border border-purple-200 dark:border-purple-900/40 bg-purple-50 dark:bg-purple-950/20 hover:bg-purple-100 dark:hover:bg-purple-950/40 transition-colors">
                                <input type="hidden" name="referral_require_order" value="0">
                                <input type="checkbox" name="referral_require_order" value="1"
                                       x-model="form.referral_require_order"
                                       class="mt-0.5 rounded border-purple-300 dark:border-purple-700 text-purple-600 focus:ring-purple-500/20 shrink-0">
                                <div>
                                    <p class="text-xs font-semibold text-purple-800 dark:text-purple-300">{{ __('Yêu cầu bạn bè có đơn hàng thành công') }}</p>
                                    <p class="text-[10px] text-purple-600 dark:text-purple-400 mt-0.5">{{ __('Chỉ tính bạn bè hợp lệ khi họ đã có ít nhất 1 đơn hoàn tiền được Admin duyệt.') }}</p>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    {{-- Mục tiêu --}}
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-slate-300 mb-1.5">{{ __('Số lần mục tiêu') }} <span class="text-red-500">*</span></label>
                        <input type="number" name="target_count" x-model="form.target_count" required min="1"
                               :max="form.action === 'profile' ? 3 : 9999"
                               class="w-full px-3 py-2.5 text-sm rounded-xl border border-gray-200 dark:border-slate-700 bg-gray-50 dark:bg-slate-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-shopee/20 focus:border-shopee outline-none transition-all">

                        {{-- Gợi ý động theo loại hành động --}}
                        <div class="mt-2 space-y-1">
                            <div x-show="form.action === 'profile'" class="rounded-xl bg-blue-50 dark:bg-blue-950/30 border border-blue-100 dark:border-blue-900/30 px-3 py-2 text-[10px] text-blue-700 dark:text-blue-300 space-y-1">
                                <p class="font-semibold">{{ __('Tối đa 3 mục tiêu — mỗi tiêu chí tính 1 điểm:') }}</p>
                                <p>① {{ __('Họ và tên đầy đủ') }}</p>
                                <p>② {{ __('Số điện thoại') }}</p>
                                <p>③ {{ __('Email đã xác minh') }}</p>
                                <p class="pt-0.5 text-blue-500 dark:text-blue-400">→ {{ __('Đặt mục tiêu = 3 để yêu cầu hoàn thiện cả 3 tiêu chí.') }}</p>
                                <p x-show="form.target_count > 3" class="text-amber-600 dark:text-amber-400 font-semibold">⚠ {{ __('Không thể vượt quá 3 — hệ thống chỉ kiểm tra đúng 3 trường trên.') }}</p>
                            </div>

                            <div x-show="form.action === 'referral'" class="rounded-xl bg-purple-50 dark:bg-purple-950/30 border border-purple-100 dark:border-purple-900/30 px-3 py-2 text-[10px] text-purple-700 dark:text-purple-300 space-y-1">
                                <p class="font-semibold">{{ __('Số người dùng mới đăng ký qua link giới thiệu.') }}</p>
                                <p x-show="!form.referral_require_order">→ {{ __('Ví dụ: 5 = mời thành công 5 người đăng ký tài khoản mới.') }}</p>
                                <p x-show="form.referral_require_order">→ {{ __('Ví dụ: 5 = mời 5 người đã đăng ký VÀ có ít nhất 1 đơn hoàn tiền được duyệt.') }}</p>
                            </div>

                            <div x-show="form.action === 'cashback'" class="rounded-xl bg-green-50 dark:bg-green-950/30 border border-green-100 dark:border-green-900/30 px-3 py-2 text-[10px] text-green-700 dark:text-green-300 space-y-1">
                                <p class="font-semibold">{{ __('Số đơn hoàn tiền đã được Admin duyệt.') }}</p>
                                <p>→ {{ __('Ví dụ: 3 = hoàn thành 3 đơn cashback được duyệt thành công.') }}</p>
                            </div>

                            <div x-show="form.action === 'checkin'" class="rounded-xl bg-amber-50 dark:bg-amber-950/30 border border-amber-100 dark:border-amber-900/30 px-3 py-2 text-[10px] text-amber-700 dark:text-amber-300 space-y-1">
                                <p class="font-semibold">{{ __('Số lần điểm danh tích lũy trong kỳ.') }}</p>
                                <p>→ {{ __('Nhiệm vụ hàng ngày: đặt 1 = điểm danh mỗi ngày là hoàn thành.') }}</p>
                                <p>→ {{ __('Nhiệm vụ hàng tuần: đặt 5 = điểm danh 5 ngày trong tuần.') }}</p>
                            </div>

                            <div x-show="form.action === 'withdraw'" class="rounded-xl bg-red-50 dark:bg-red-950/30 border border-red-100 dark:border-red-900/30 px-3 py-2 text-[10px] text-red-700 dark:text-red-300 space-y-1">
                                <p class="font-semibold">{{ __('Số lần rút tiền đã được Admin duyệt.') }}</p>
                                <p>→ {{ __('Ví dụ: 1 = hoàn thành 1 lần rút tiền thành công.') }}</p>
                            </div>

                            <div x-show="form.action === 'save_product'" class="rounded-xl bg-teal-50 dark:bg-teal-950/30 border border-teal-100 dark:border-teal-900/30 px-3 py-2 text-[10px] text-teal-700 dark:text-teal-300 space-y-1">
                                <p class="font-semibold">{{ __('Số sản phẩm Shopee đã phân tích qua hệ thống.') }}</p>
                                <p>→ {{ __('Ví dụ: 5 = dán link và phân tích 5 sản phẩm khác nhau.') }}</p>
                            </div>

                            <div x-show="form.action === 'custom'" class="rounded-xl bg-gray-50 dark:bg-slate-800 border border-gray-200 dark:border-slate-700 px-3 py-2 text-[10px] text-gray-600 dark:text-slate-400 space-y-1">
                                <p class="font-semibold">{{ __('Admin xác nhận tiến độ thủ công trong tab Tiến độ.') }}</p>
                                <p>→ {{ __('Con số này chỉ là mốc tham chiếu, thường đặt = 1.') }}</p>
                            </div>
                        </div>
                    </div>

                    {{-- Đơn hoàn tiền tối thiểu bao nhiêu --}}
                    <div x-show="form.action === 'cashback' || (form.action === 'referral' && form.referral_require_order)" x-cloak>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-slate-300 mb-1.5">{{ __('Tiền hoàn tối thiểu') }}</label>
                        <div class="relative">
                            <input type="number" name="min_order_amount" x-model="form.min_order_amount" min="0" step="any"
                                   placeholder="{{ __('Ví dụ: 5000') }}"
                                   class="w-full pl-3 pr-10 py-2.5 text-sm rounded-xl border border-gray-200 dark:border-slate-700 bg-gray-50 dark:bg-slate-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-shopee/20 focus:border-shopee outline-none transition-all">
                            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-gray-400 font-bold">đ</span>
                        </div>
                        <p class="text-[10px] text-gray-400 mt-1">
                            {{ __('Đơn hoàn tiền phải có số tiền hoàn (tiền cashback thực tế) tối thiểu bằng mức này mới được tính tiến độ. Nhập 0 hoặc để trống nếu không giới hạn.') }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Bước 3: Thưởng & Hiển thị -->
            <div x-show="currentStep === 3" x-cloak class="space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    {{-- Phần thưởng --}}
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-slate-300 mb-1.5">{{ __('Phần thưởng (VNĐ)') }} <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <input type="number" name="reward_amount" x-model="form.reward_amount" required min="0"
                                   class="w-full pl-3 pr-10 py-2.5 text-sm rounded-xl border border-gray-200 dark:border-slate-700 bg-gray-50 dark:bg-slate-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-shopee/20 focus:border-shopee outline-none transition-all">
                            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-gray-400 font-bold">đ</span>
                        </div>
                    </div>

                    {{-- Icon --}}
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-slate-300 mb-1.5">{{ __('Icon (Lucide)') }}</label>
                        <div class="flex gap-2">
                            <div class="relative flex-1">
                                <input type="text" 
                                       name="icon" 
                                       id="task_icon" 
                                       x-model="form.icon"
                                       placeholder="Ví dụ: star, trophy, zap..."
                                       class="block w-full pl-9 pr-4 py-2.5 text-sm rounded-xl border border-gray-200 dark:border-slate-700 bg-gray-50 dark:bg-slate-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-shopee/20 focus:border-shopee outline-none transition-all font-mono">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                                    <i :data-lucide="form.icon || 'star'" class="w-4 h-4"></i>
                                </div>
                            </div>
                            <button type="button" 
                                    @click="$dispatch('open-icon-picker', { target: 'task_icon' })" 
                                    class="px-4 py-2.5 bg-gray-100 hover:bg-gray-200 dark:bg-slate-800 dark:hover:bg-slate-750 text-gray-700 dark:text-slate-300 text-xs font-semibold rounded-xl transition-all flex items-center gap-1.5 border border-gray-200 dark:border-slate-700/50 active:scale-95">
                                <i data-lucide="grid" class="w-3.5 h-3.5 text-gray-500"></i>
                                {{ __('Chọn') }}
                            </button>
                        </div>
                    </div>

                    {{-- Màu badge --}}
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-slate-300 mb-1.5">{{ __('Màu sắc') }}</label>
                        <div class="flex flex-wrap gap-2">
                            @foreach($colorOptions as $val => $label)
                            <label class="cursor-pointer">
                                <input type="radio" name="badge_color" value="{{ $val }}" x-model="form.badge_color" class="sr-only">
                                <span class="inline-block w-7 h-7 rounded-lg border-2 transition-all"
                                      :class="form.badge_color === '{{ $val }}' ? 'border-gray-900 dark:border-white scale-110' : 'border-transparent'"
                                      style="background-color: {{ match($val) {
                                        'orange' => '#f97316', 'blue' => '#3b82f6', 'green' => '#22c55e',
                                        'purple' => '#a855f7', 'red' => '#ef4444', 'yellow' => '#eab308',
                                        'pink' => '#ec4899', 'cyan' => '#06b6d4', default => '#f97316'
                                      } }}"
                                      title="{{ $label }}"></span>
                            </label>
                            @endforeach
                        </div>
                    </div>

                    {{-- Thứ tự hiển thị --}}
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-slate-300 mb-1.5">{{ __('Thứ tự hiển thị') }}</label>
                        <input type="number" name="sort_order" x-model="form.sort_order" min="0"
                               class="w-full px-3 py-2.5 text-sm rounded-xl border border-gray-200 dark:border-slate-700 bg-gray-50 dark:bg-slate-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-shopee/20 focus:border-shopee outline-none transition-all">
                    </div>

                    {{-- Ngày bắt đầu/kết thúc --}}
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-slate-300 mb-1.5">{{ __('Ngày bắt đầu') }}</label>
                        <input type="date" name="start_at" x-model="form.start_at"
                               class="w-full px-3 py-2.5 text-sm rounded-xl border border-gray-200 dark:border-slate-700 bg-gray-50 dark:bg-slate-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-shopee/20 focus:border-shopee outline-none transition-all">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-slate-300 mb-1.5">{{ __('Ngày kết thúc') }}</label>
                        <input type="date" name="end_at" x-model="form.end_at"
                               class="w-full px-3 py-2.5 text-sm rounded-xl border border-gray-200 dark:border-slate-700 bg-gray-50 dark:bg-slate-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-shopee/20 focus:border-shopee outline-none transition-all">
                    </div>

                    {{-- Trạng thái --}}
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-semibold text-gray-700 dark:text-slate-300 mb-1.5">{{ __('Trạng thái') }}</label>
                        <div class="flex items-center gap-3 mt-2">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" name="is_active" value="1" x-model="form.is_active" class="text-shopee">
                                <span class="text-xs font-medium text-gray-700 dark:text-gray-300">{{ __('Hoạt động') }}</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" name="is_active" value="0" x-model="form.is_active" class="text-shopee">
                                <span class="text-xs font-medium text-gray-700 dark:text-gray-300">{{ __('Tắt') }}</span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer các nút điều hướng -->
            <div class="flex items-center justify-between pt-4 border-t border-gray-100 dark:border-slate-800 sticky bottom-0 bg-white dark:bg-slate-900 py-4 -mx-6 px-6 rounded-b-3xl">
                <!-- Nút Hủy (luôn hiển thị) -->
                <button type="button" @click="closeModal()" class="px-5 py-2.5 text-xs font-semibold text-gray-600 dark:text-gray-400 bg-gray-100 dark:bg-slate-800 hover:bg-gray-200 dark:hover:bg-slate-750 rounded-xl transition-all">
                    {{ __('Hủy') }}
                </button>

                <div class="flex items-center gap-2">
                    <!-- Nút Quay lại (hiển thị từ bước 2) -->
                    <button type="button" x-show="currentStep > 1" @click="currentStep--" class="px-5 py-2.5 text-xs font-semibold text-gray-700 dark:text-slate-300 bg-gray-100 dark:bg-slate-800 hover:bg-gray-200 dark:hover:bg-slate-750 border border-gray-200 dark:border-slate-700 rounded-xl transition-all flex items-center gap-1">
                        <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i>
                        {{ __('Quay lại') }}
                    </button>

                    <!-- Nút Tiếp theo (hiển thị ở bước 1 và 2) -->
                    <button type="button" x-show="currentStep < 3" 
                            @click="if(!form.title.trim()) { alert('{{ __('Vui lòng nhập tiêu đề nhiệm vụ!') }}'); return; } currentStep++" 
                            class="px-5 py-2.5 text-xs font-semibold bg-shopee hover:bg-shopee-dark text-white rounded-xl transition-all flex items-center gap-1 active:scale-95">
                        {{ __('Tiếp tục') }}
                        <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                    </button>

                    <!-- Nút Lưu/Tạo (chỉ hiển thị ở bước 3) -->
                    <button type="submit" x-show="currentStep === 3" class="inline-flex items-center gap-1.5 px-5 py-2.5 text-xs font-semibold bg-shopee hover:bg-shopee-dark text-white rounded-xl transition-all shadow-sm active:scale-95">
                        <i data-lucide="save" class="w-3.5 h-3.5"></i>
                        <span x-text="editMode ? '{{ __('Lưu thay đổi') }}' : '{{ __('Tạo nhiệm vụ') }}'"></span>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- Modal Xóa --}}
<div x-show="showDeleteModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 !mt-0"
     x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
    <div class="absolute inset-0 bg-black/60" @click="showDeleteModal = false"></div>
    <div class="relative w-full max-w-sm bg-white dark:bg-slate-900 rounded-3xl shadow-2xl border border-gray-100 dark:border-slate-800 p-6 space-y-4"
         x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-2xl bg-red-100 dark:bg-red-900/30 flex items-center justify-center text-red-600 dark:text-red-400 shrink-0">
                <i data-lucide="trash-2" class="w-5 h-5"></i>
            </div>
            <div>
                <h3 class="font-bold text-gray-900 dark:text-white text-sm">{{ __('Xác nhận xóa nhiệm vụ') }}</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5" x-text="'{{ __('Bạn sắp xóa: ') }}' + deleteTitle"></p>
            </div>
        </div>
        <p class="text-xs text-gray-600 dark:text-gray-400 bg-red-50 dark:bg-red-950/20 border border-red-100 dark:border-red-900/20 rounded-xl p-3">
            {{ __('Tất cả tiến độ của thành viên cho nhiệm vụ này cũng sẽ bị xóa. Hành động không thể hoàn tác.') }}
        </p>
        <form :action="tasksBaseUrl + '/' + deleteId" method="POST" class="flex items-center gap-2 justify-end">
            @csrf @method('DELETE')
            <button type="button" @click="showDeleteModal = false" class="px-4 py-2 text-xs font-semibold text-gray-600 dark:text-gray-400 bg-gray-100 dark:bg-slate-800 hover:bg-gray-200 dark:hover:bg-slate-700 rounded-xl transition-all">{{ __('Hủy') }}</button>
            <button type="submit" class="px-4 py-2 text-xs font-semibold text-white bg-red-500 hover:bg-red-600 rounded-xl transition-all">{{ __('Xóa nhiệm vụ') }}</button>
        </form>
    </div>
</div>

{{-- Modal Bulk Delete --}}
<div x-show="showBulkDeleteModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 !mt-0"
     x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
    <div class="absolute inset-0 bg-black/60" @click="showBulkDeleteModal = false"></div>
    <div class="relative w-full max-w-sm bg-white dark:bg-slate-900 rounded-3xl shadow-2xl border border-gray-100 dark:border-slate-800 p-6 space-y-4"
         x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100">
        <h3 class="font-bold text-gray-900 dark:text-white text-sm flex items-center gap-2">
            <i data-lucide="trash-2" class="w-4 h-4 text-red-500"></i>
            {{ __('Xóa hàng loạt') }}
        </h3>
        <p class="text-xs text-gray-600 dark:text-gray-400">
            {{ __('Nhập "XÓA HÀNG LOẠT" để xác nhận xóa ') }}<span class="font-bold text-red-600" x-text="selectedTasks.length"></span> {{ __('nhiệm vụ đã chọn.') }}
        </p>
        <input type="text" x-model="bulkConfirmText" placeholder="XÓA HÀNG LOẠT"
               class="w-full px-3 py-2.5 text-sm rounded-xl border border-gray-200 dark:border-slate-700 bg-gray-50 dark:bg-slate-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-red-500/20 focus:border-red-400 outline-none transition-all uppercase tracking-wider font-bold">
        <div class="flex items-center gap-2 justify-end">
            <button @click="showBulkDeleteModal = false" class="px-4 py-2 text-xs font-semibold text-gray-600 dark:text-gray-400 bg-gray-100 dark:bg-slate-800 hover:bg-gray-200 dark:hover:bg-slate-700 rounded-xl transition-all">{{ __('Hủy') }}</button>
            <button @click="executeBulkDelete()" :disabled="bulkConfirmText.toUpperCase() !== 'XÓA HÀNG LOẠT'" class="px-4 py-2 text-xs font-semibold text-white bg-red-500 hover:bg-red-600 rounded-xl transition-all disabled:opacity-40 disabled:cursor-not-allowed">{{ __('Xóa ngay') }}</button>
        </div>
    </div>
</div>

{{-- Modal Reset Submission --}}
<div x-show="showRejectModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 !mt-0"
     x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
    <div class="absolute inset-0 bg-black/60" @click="showRejectModal = false"></div>
    <div class="relative w-full max-w-sm bg-white dark:bg-slate-900 rounded-3xl shadow-2xl border border-gray-100 dark:border-slate-800 p-6 space-y-4"
         x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100">
        <h3 class="font-bold text-gray-900 dark:text-white text-sm flex items-center gap-2">
            <i data-lucide="rotate-ccw" class="w-4 h-4 text-amber-500"></i>
            {{ __('Reset tiến độ nhiệm vụ') }}
        </h3>
        <p class="text-xs text-gray-600 dark:text-gray-400">{{ __('Tiến độ sẽ được đặt lại về 0, thành viên cần làm lại từ đầu.') }}</p>
        <p class="text-[11px] text-amber-600 dark:text-amber-400 bg-amber-50 dark:bg-amber-950/20 border border-amber-100 dark:border-amber-900/30 rounded-xl px-3 py-2">
            {{ __('Lý do bên dưới sẽ được gửi thông báo và hiển thị cho thành viên để họ khắc phục rồi gửi lại yêu cầu.') }}
        </p>
        <form :action="tasksSubmissionsUrl + rejectId + '/reject'" method="POST">
            @csrf
            <textarea name="notes" placeholder="{{ __('Lý do từ chối (tùy chọn)...') }}" rows="2"
                      class="w-full px-3 py-2.5 text-sm rounded-xl border border-gray-200 dark:border-slate-700 bg-gray-50 dark:bg-slate-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-shopee/20 outline-none resize-none mb-3"></textarea>
            <div class="flex items-center gap-2 justify-end">
                <button type="button" @click="showRejectModal = false" class="px-4 py-2 text-xs font-semibold text-gray-600 dark:text-gray-400 bg-gray-100 dark:bg-slate-800 hover:bg-gray-200 dark:hover:bg-slate-700 rounded-xl transition-all">{{ __('Hủy') }}</button>
                <button type="submit" class="px-4 py-2 text-xs font-semibold text-white bg-amber-500 hover:bg-amber-600 rounded-xl transition-all">{{ __('Xác nhận Reset') }}</button>
            </div>
        </form>
    </div>
</div>

    <!-- MODAL: THỐNG KÊ TIẾN ĐỘ & CHI PHÍ NHIỆM VỤ (Chart.js) -->
    <template x-teleport="body">
        <div x-show="taskStatsModalOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-slate-950/40 backdrop-blur-sm" x-transition>
            <div class="bg-white dark:bg-slate-900 rounded-3xl max-w-4xl w-full shadow-2xl border border-gray-100 dark:border-slate-800 overflow-hidden" 
                 @click.away="taskStatsModalOpen = false"
                 x-transition:enter="transition ease-out duration-200" 
                 x-transition:enter-start="opacity-0 scale-95" 
                 x-transition:enter-end="opacity-100 scale-100" 
                 x-transition:leave="transition ease-in duration-150" 
                 x-transition:leave-start="opacity-100 scale-100" 
                 x-transition:leave-end="opacity-0 scale-95">
                <!-- Header của Modal -->
                <div class="px-6 py-4 bg-gradient-to-r from-indigo-50 to-purple-50 dark:from-indigo-950/30 dark:to-purple-950/30 border-b border-gray-100 dark:border-slate-800 flex justify-between items-center">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-indigo-100 dark:bg-indigo-900/40 rounded-xl">
                            <i data-lucide="bar-chart-3" class="w-5 h-5 text-indigo-600 dark:text-indigo-400"></i>
                        </div>
                        <div>
                            <h3 class="font-bold text-gray-900 dark:text-slate-200 text-sm">{{ __('Thống kê tiến độ & chi phí nhiệm vụ') }}</h3>
                            <p class="text-[10px] text-gray-500 dark:text-slate-450 font-medium">{{ __('Phân tích chi tiết số lượt làm nhiệm vụ và quỹ tiền thưởng đã phát') }}</p>
                        </div>
                    </div>
                    <button @click="taskStatsModalOpen = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-slate-350 transition-colors">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>

                <!-- Body của Modal -->
                <div class="p-6 space-y-4">
                    <!-- Hàng điều khiển: Chọn khoảng thời gian và chọn chế độ xem biểu đồ -->
                    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                        <!-- Chọn khoảng thời gian (Tuần, Tháng, Năm) -->
                        <div class="inline-flex bg-gray-100 dark:bg-slate-800 rounded-xl p-1 gap-1">
                            <button @click="loadTaskStats('week')" 
                                    class="px-3 py-1.5 text-[11px] font-bold rounded-lg transition-all"
                                    :class="taskStatsPeriod === 'week' ? 'bg-white dark:bg-slate-700 text-indigo-600 dark:text-indigo-400 shadow-sm' : 'text-gray-500 dark:text-slate-400 hover:text-gray-700'">
                                {{ __('Tuần') }}
                            </button>
                            <button @click="loadTaskStats('month')" 
                                    class="px-3 py-1.5 text-[11px] font-bold rounded-lg transition-all"
                                    :class="taskStatsPeriod === 'month' ? 'bg-white dark:bg-slate-700 text-indigo-600 dark:text-indigo-400 shadow-sm' : 'text-gray-500 dark:text-slate-400 hover:text-gray-700'">
                                {{ __('Tháng') }}
                            </button>
                            <button @click="loadTaskStats('year')" 
                                    class="px-3 py-1.5 text-[11px] font-bold rounded-lg transition-all"
                                    :class="taskStatsPeriod === 'year' ? 'bg-white dark:bg-slate-700 text-indigo-600 dark:text-indigo-400 shadow-sm' : 'text-gray-500 dark:text-slate-400 hover:text-gray-700'">
                                {{ __('Năm') }}
                            </button>
                        </div>
                        <!-- Chọn chế độ xem (Số lượt làm nhiệm vụ hoặc Tiền thưởng) -->
                        <div class="inline-flex bg-gray-100 dark:bg-slate-800 rounded-xl p-1 gap-1">
                            <button @click="switchChartView('submissions')" 
                                    class="px-3 py-1.5 text-[11px] font-bold rounded-lg transition-all flex items-center gap-1"
                                    :class="taskChartView === 'submissions' ? 'bg-white dark:bg-slate-700 text-indigo-600 dark:text-indigo-400 shadow-sm' : 'text-gray-500 dark:text-slate-400 hover:text-gray-700'">
                                <i data-lucide="hash" class="w-3 h-3"></i> {{ __('Lượt làm') }}
                            </button>
                            <button @click="switchChartView('rewards')" 
                                    class="px-3 py-1.5 text-[11px] font-bold rounded-lg transition-all flex items-center gap-1"
                                    :class="taskChartView === 'rewards' ? 'bg-white dark:bg-slate-700 text-green-600 dark:text-green-400 shadow-sm' : 'text-gray-500 dark:text-slate-400 hover:text-gray-700'">
                                <i data-lucide="wallet" class="w-3 h-3"></i> {{ __('Tiền thưởng') }}
                            </button>
                        </div>
                    </div>

                    <!-- Hàng thẻ thông tin tổng số liệu tích lũy -->
                    <!-- Chế độ 1: Thống kê số lượt làm nhiệm vụ của thành viên -->
                    <div x-show="taskChartView === 'submissions'" class="grid grid-cols-4 gap-3">
                        <div class="p-2.5 bg-gray-50 dark:bg-slate-800/30 rounded-2xl border border-gray-100 dark:border-slate-800/50 text-center">
                            <p class="text-[8px] font-bold text-gray-400 dark:text-slate-500 uppercase tracking-widest">{{ __('Tổng lượt') }}</p>
                            <p class="text-base font-black text-gray-800 dark:text-slate-200" x-text="taskStatsTotals.all">0</p>
                        </div>
                        <div class="p-2.5 bg-blue-50/60 dark:bg-blue-950/10 rounded-2xl border border-blue-100 dark:border-blue-900/30 text-center">
                            <p class="text-[8px] font-bold text-blue-500 uppercase tracking-widest">{{ __('Đang làm') }}</p>
                            <p class="text-base font-black text-blue-600 dark:text-blue-400" x-text="taskStatsTotals.in_progress">0</p>
                        </div>
                        <div class="p-2.5 bg-amber-50/60 dark:bg-amber-950/10 rounded-2xl border border-amber-100 dark:border-amber-900/30 text-center">
                            <p class="text-[8px] font-bold text-amber-500 uppercase tracking-widest">{{ __('Chờ duyệt') }}</p>
                            <p class="text-base font-black text-amber-600 dark:text-amber-400" x-text="taskStatsTotals.completed">0</p>
                        </div>
                        <div class="p-2.5 bg-emerald-50/60 dark:bg-emerald-950/10 rounded-2xl border border-emerald-100 dark:border-emerald-900/30 text-center">
                            <p class="text-[8px] font-bold text-emerald-500 uppercase tracking-widest">{{ __('Đã nhận thưởng') }}</p>
                            <p class="text-base font-black text-emerald-600 dark:text-emerald-400" x-text="taskStatsTotals.claimed">0</p>
                        </div>
                    </div>
                    <!-- Chế độ 2: Thống kê chi phí quỹ tiền thưởng đã phát / chờ duyệt -->
                    <div x-show="taskChartView === 'rewards'" class="grid grid-cols-2 gap-3 flex-wrap">
                        <div class="p-3 bg-emerald-50/60 dark:bg-emerald-950/10 rounded-2xl border border-emerald-100 dark:border-emerald-900/30 text-center">
                            <p class="text-[8px] font-bold text-emerald-500 uppercase tracking-widest">{{ __('Thưởng đã phát') }}</p>
                            <p class="text-base font-black text-emerald-600 dark:text-emerald-400" x-text="formatCurrency(taskStatsTotals.reward)">0đ</p>
                        </div>
                        <div class="p-3 bg-amber-50/60 dark:bg-amber-950/10 rounded-2xl border border-amber-100 dark:border-amber-900/30 text-center">
                            <p class="text-[8px] font-bold text-amber-500 uppercase tracking-widest">{{ __('Thưởng chờ duyệt') }}</p>
                            <p class="text-base font-black text-amber-600 dark:text-amber-400" x-text="formatCurrency(taskStatsTotals.reward_pending)">0đ</p>
                        </div>
                    </div>

                    <!-- Khung vẽ biểu đồ Chart.js có hiệu ứng loading khi đang fetch -->
                    <div class="relative bg-gray-50/50 dark:bg-slate-950/30 rounded-2xl p-4 border border-gray-100 dark:border-slate-800/50" style="min-height: 300px;">
                        <div x-show="taskStatsLoading" class="absolute inset-0 flex items-center justify-center bg-white/60 dark:bg-slate-900/60 rounded-2xl z-10">
                            <svg class="animate-spin h-8 w-8 text-indigo-500" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </div>
                        <canvas id="taskStatsChart" height="280"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </template>

    @include('admin.tasks.partials.config_modal')
</div>{{-- /.space-y-6 [x-data="taskAdminHandler()"] --}}

@endsection

@section('scripts')
{{-- TinyMCE: trình soạn thảo trực quan cho ô Mô tả ngắn và Hướng dẫn thực hiện của nhiệm vụ --}}
<script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/6.8.2/tinymce.min.js" referrerpolicy="origin"></script>
<script>
    // Mở cửa sổ quản lý tệp elFinder để chọn ảnh minh họa chèn vào nội dung
    function openElfinderPopup(inputId) {
        const width  = 900;
        const height = 600;
        const left   = (screen.width - width) / 2;
        const top    = (screen.height - height) / 2;
        window.open('{{ url("elfinder/popup") }}/' + inputId, 'elfinderPicker',
            'width=' + width + ',height=' + height + ',left=' + left + ',top=' + top + ',resizable=yes,scrollbars=yes,status=no');
    }

    // Hàm callback toàn cục được cửa sổ elFinder gọi ngược lại sau khi chọn xong tệp
    window.processSelectedFile = function (fileUrl, inputId) {
        if (inputId === 'tinymce_image' && window.tinymceFilePickerCallback) {
            window.tinymceFilePickerCallback(fileUrl, { alt: '{{ __('Ảnh minh họa nhiệm vụ') }}' });
            window.tinymceFilePickerCallback = null;
        }
    };

    // Cấu hình dùng chung cho cả hai trình soạn thảo trong modal nhiệm vụ
    function taskEditorConfig(height) {
        const isDark = document.documentElement.classList.contains('dark');
        return {
            height: height,
            menubar: false,
            language: 'vi',
            branding: false,
            promotion: false,
            statusbar: false,
            plugins: 'advlist autolink lists link image charmap searchreplace visualblocks code fullscreen table help wordcount',
            toolbar: 'undo redo | blocks | bold italic underline forecolor backcolor | alignleft aligncenter alignright | bullist numlist | link image table | removeformat code fullscreen',
            content_style: 'body { font-family: Inter, sans-serif; font-size: 14px; margin: 10px } p { margin: 0 0 8px }',
            skin: isDark ? 'oxide-dark' : 'oxide',
            content_css: isDark ? 'dark' : 'default',
            // Chặn dán ảnh dạng base64 để tránh nội dung phình to vượt giới hạn lưu trữ của cột dữ liệu
            paste_data_images: false,
            file_picker_types: 'image',
            file_picker_callback: function (callback, value, meta) {
                if (meta.filetype === 'image') {
                    window.tinymceFilePickerCallback = callback;
                    openElfinderPopup('tinymce_image');
                }
            }
        };
    }
</script>
<script>
function taskAdminHandler() {
    return {
        tasksBaseUrl: '{{ route('admin.tasks.store') }}',
        tasksSubmissionsUrl: '{{ rtrim(route('admin.tasks.index'), '/') }}/submissions/',
        showFilter: @json($hasFilter),
        showModal: false,
        currentStep: 1, // Quản lý bước nhập liệu trong modal (step 1, 2, 3)
        editMode: false,
        showDeleteModal: false,
        showBulkDeleteModal: false,
        showRejectModal: false,
        showConfigModal: false,
        deleteId: null,
        deleteTitle: '',
        rejectId: null,
        bulkConfirmText: '',
        selectedTasks: [],
        selectAll: false,

        // Trạng thái điều khiển của modal biểu đồ thống kê nhiệm vụ
        taskStatsModalOpen: false,
        taskStatsPeriod: 'week',
        taskStatsTotals: { all: 0, in_progress: 0, completed: 0, claimed: 0, reward: 0, reward_pending: 0 },
        taskStatsLoading: false,
        taskChartView: 'submissions',
        taskStatsRawData: null,

        form: {
            id: null, title: '', description: '', guide: '',
            type: 'one_time', action: 'custom',
            referral_require_order: false,
            target_count: 1, min_order_amount: 0, reward_amount: 0,
            is_active: '1', start_at: null, end_at: null,
            icon: 'star', badge_color: 'orange', sort_order: 0,
        },

        init() {
            // Theo dõi sự thay đổi của trường form.icon để vẽ lại icon Lucide tương ứng ngay khi người dùng chọn xong từ modal picker
            this.$watch('form.icon', () => {
                this.$nextTick(() => {
                    if (window.lucide) window.lucide.createIcons();
                });
            });
        },

        // Nội dung chờ nạp vào từng trình soạn thảo và danh sách trình soạn thảo đã bắt đầu khởi tạo
        editorValues: {},
        editorsStarted: [],

        // Khởi tạo (hoặc nạp lại nội dung) trình soạn thảo trực quan mỗi khi mở modal nhiệm vụ
        setupEditors() {
            if (typeof tinymce === 'undefined') return;

            const fields = [
                { id: 'task-description-editor', value: this.form.description || '', height: 220 },
                { id: 'task-guide-editor',       value: this.form.guide || '',       height: 320 },
            ];

            fields.forEach(field => {
                // Luôn ghi nhận nội dung mới nhất cần nạp để dùng cho cả trường hợp đang khởi tạo dở
                this.editorValues[field.id] = field.value;

                const existed = tinymce.get(field.id);

                // Trường hợp trình soạn thảo đã được khởi tạo từ lần mở modal trước: chỉ cần nạp lại nội dung
                if (existed) {
                    existed.setContent(field.value);
                    existed.undoManager.clear();
                    return;
                }

                // Đang khởi tạo dở (do mở modal liên tiếp): bỏ qua để tránh khởi tạo trùng hai trình soạn thảo
                // trên cùng một ô nhập, nội dung mới nhất sẽ được nạp ở sự kiện init bên dưới
                if (this.editorsStarted.includes(field.id)) return;
                this.editorsStarted.push(field.id);

                tinymce.init(Object.assign(taskEditorConfig(field.height), {
                    selector: '#' + field.id,
                    setup: (editor) => {
                        editor.on('init', () => {
                            editor.setContent(this.editorValues[field.id] || '');
                            editor.undoManager.clear();
                        });
                    }
                }));
            });
        },

        // Đẩy nội dung đang soạn từ TinyMCE về lại thẻ textarea gốc trước khi gửi form lên máy chủ
        syncEditors() {
            if (typeof tinymce !== 'undefined') tinymce.triggerSave();
        },

        openCreateModal() {
            this.editMode = false;
            this.form = { id: null, title: '', description: '', guide: '', type: 'one_time', action: 'custom', referral_require_order: false, target_count: 1, min_order_amount: 0, reward_amount: 0, is_active: '1', start_at: null, end_at: null, icon: 'star', badge_color: 'orange', sort_order: 0 };
            this.currentStep = 1;
            this.showModal = true;
            this.$nextTick(() => { if (window.lucide) lucide.createIcons(); this.setupEditors(); });
        },

        openEditModal(task) {
            this.editMode = true;
            this.form = {
                id: task.id, title: task.title, description: task.description || '',
                guide: task.guide || '', type: task.type, action: task.action,
                referral_require_order: !!task.referral_require_order,
                target_count: task.target_count, min_order_amount: task.min_order_amount || 0,
                reward_amount: task.reward_amount,
                is_active: String(task.is_active), start_at: task.start_at || null,
                end_at: task.end_at || null, icon: task.icon || 'star',
                badge_color: task.badge_color || 'orange', sort_order: task.sort_order || 0,
            };
            this.currentStep = 1;
            this.showModal = true;
            this.$nextTick(() => { if (window.lucide) lucide.createIcons(); this.setupEditors(); });
        },

        // Nhân bản nhiệm vụ: điền sẵn thông tin của nhiệm vụ được chọn vào form tạo mới
        duplicateTask(task) {
            this.editMode = false; // Tắt chế độ edit để form submit tới route thêm mới (POST store)
            this.form = {
                id: null, // Đặt id = null để tạo bản ghi hoàn toàn mới
                title: task.title + ' (Sao chép)', // Tự động thêm hậu tố để admin dễ phân biệt
                description: task.description || '',
                guide: task.guide || '',
                type: task.type,
                action: task.action,
                referral_require_order: !!task.referral_require_order,
                target_count: task.target_count,
                min_order_amount: task.min_order_amount || 0,
                reward_amount: task.reward_amount,
                is_active: String(task.is_active),
                start_at: task.start_at || null,
                end_at: task.end_at || null,
                icon: task.icon || 'star',
                badge_color: task.badge_color || 'orange',
                sort_order: task.sort_order || 0,
            };
            this.currentStep = 1;
            this.showModal = true; // Mở modal form
            this.$nextTick(() => { if (window.lucide) lucide.createIcons(); this.setupEditors(); });
        },

        closeModal() {
            this.showModal = false;
            // Đóng mọi hộp thoại phụ của TinyMCE (chèn liên kết, chèn ảnh...) nếu đang mở
            if (typeof tinymce !== 'undefined') {
                tinymce.editors.forEach(editor => editor.windowManager.close());
            }
        },

        openDeleteModal(id, title) {
            this.deleteId = id;
            this.deleteTitle = title;
            this.showDeleteModal = true;
        },

        openBulkDeleteModal() {
            this.bulkConfirmText = '';
            this.showBulkDeleteModal = true;
        },

        openConfig() {
            this.showConfigModal = true;
            this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
        },

        openRejectModal(id) {
            this.rejectId = id;
            this.showRejectModal = true;
        },

        toggleSelectAll() {
            if (this.selectAll) {
                const checkboxes = document.querySelectorAll('input[type="checkbox"][x-model="selectedTasks"]');
                this.selectedTasks = Array.from(checkboxes).map(cb => parseInt(cb.value)).filter(v => !isNaN(v));
            } else {
                this.selectedTasks = [];
            }
        },

        toggleStatus(id, currentStatus) {
            fetch(this.tasksBaseUrl + '/' + id + '/toggle-status', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' }
            }).then(r => r.json()).then(data => {
                if (data.success) {
                    window.location.reload();
                }
            });
        },

        executeBulkDelete() {
            if (this.bulkConfirmText.toUpperCase() !== 'XÓA HÀNG LOẠT') return;

            fetch('{{ route('admin.tasks.bulk_destroy') }}', {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ task_ids: this.selectedTasks.join(','), confirm_text: this.bulkConfirmText })
            }).then(r => r.json()).then(data => {
                if (data.status === 'success') {
                    window.location.reload();
                } else {
                    alert(data.message);
                }
            });
        },

        // Mở modal thống kê nhiệm vụ và load dữ liệu tuần đầu tiên
        openTaskStatsModal() {
            this.taskStatsModalOpen = true;
            this.taskChartView = 'submissions';
            setTimeout(() => {
                if (window.lucide) window.lucide.createIcons();
                this.loadTaskStats('week');
            }, 100);
        },

        // Chuyển chế độ xem biểu đồ (lượt làm / tiền thưởng) mà không gọi lại API
        switchChartView(view) {
            this.taskChartView = view;
            if (this.taskStatsRawData) {
                this.renderTaskChart(this.taskStatsRawData, this.taskStatsPeriod);
            }
            // Tải lại các icon Lucide cho tab mới hiển thị
            setTimeout(() => { if (window.lucide) window.lucide.createIcons(); }, 50);
        },

        // Gọi API tải dữ liệu thống kê nhiệm vụ từ máy chủ
        async loadTaskStats(period) {
            this.taskStatsPeriod = period;
            this.taskStatsLoading = true;

            try {
                const response = await fetch(`{{ route('admin.tasks.stats') }}?period=${period}`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                });

                if (!response.ok) throw new Error('Không thể kết nối đến máy chủ');
                const result = await response.json();

                if (result.success) {
                    this.taskStatsTotals = result.totals;
                    // Lưu cache dữ liệu thô để chuyển tab mượt mà không cần fetch lại
                    this.taskStatsRawData = result;
                    this.renderTaskChart(result, period);
                }
            } catch (error) {
                console.error('Lỗi tải dữ liệu thống kê nhiệm vụ:', error);
                Swal.fire({
                    icon: 'error',
                    title: "{{ __('Lỗi tải dữ liệu') }}",
                    text: "{{ __('Không thể tải dữ liệu thống kê nhiệm vụ. Vui lòng thử lại.') }}"
                });
            } finally {
                this.taskStatsLoading = false;
            }
        },

        // Định dạng tiền tệ VND cho tooltip và hiển thị ngoài storefront
        formatCurrency(value) {
            return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(value);
        },

        // Render biểu đồ Chart.js dựa trên tab view đang chọn (submissions / rewards)
        renderTaskChart(data, period) {
            const canvas = document.getElementById('taskStatsChart');
            if (!canvas) return;

            // Hủy chart cũ gán trực tiếp trên canvas DOM node để tránh xung đột với proxy reactive của AlpineJS
            if (canvas._chartInstance) {
                canvas._chartInstance.destroy();
                canvas._chartInstance = null;
            }

            const ctx = canvas.getContext('2d');
            const periodLabels = {
                week: "{{ __('7 ngày gần nhất') }}",
                month: "{{ __('30 ngày gần nhất') }}",
                year: "{{ __('12 tháng gần nhất') }}"
            };

            const fmtVND = (v) => this.formatCurrency(v);
            let datasets, stacked, tooltipCb, yTickCb;

            if (this.taskChartView === 'submissions') {
                // Biểu đồ cột chồng (stacked bar) cho 3 trạng thái tiến độ làm nhiệm vụ
                stacked = true;
                datasets = [
                    {
                        label: "{{ __('Đang làm') }}",
                        data: data.in_progress,
                        backgroundColor: 'rgba(59, 130, 246, 0.7)',
                        hoverBackgroundColor: 'rgba(59, 130, 246, 0.9)',
                        borderColor: 'rgba(59, 130, 246, 1)',
                        borderWidth: 1, borderRadius: 4, borderSkipped: false
                    },
                    {
                        label: "{{ __('Chờ duyệt') }}",
                        data: data.completed,
                        backgroundColor: 'rgba(245, 158, 11, 0.7)',
                        hoverBackgroundColor: 'rgba(245, 158, 11, 0.9)',
                        borderColor: 'rgba(245, 158, 11, 1)',
                        borderWidth: 1, borderRadius: 4, borderSkipped: false
                    },
                    {
                        label: "{{ __('Đã nhận thưởng') }}",
                        data: data.claimed,
                        backgroundColor: 'rgba(16, 185, 129, 0.7)',
                        hoverBackgroundColor: 'rgba(16, 185, 129, 0.9)',
                        borderColor: 'rgba(16, 185, 129, 1)',
                        borderWidth: 1, borderRadius: 4, borderSkipped: false
                    }
                ];
                tooltipCb = (item) => `${item.dataset.label}: ${item.parsed.y} lượt`;
                yTickCb = (v) => v;
            } else {
                // Biểu đồ cột nhóm (grouped bar) cho ngân sách thưởng đã phát vs chờ duyệt
                stacked = false;
                datasets = [
                    {
                        label: "{{ __('Thưởng đã phát') }}",
                        data: data.reward_data,
                        backgroundColor: 'rgba(16, 185, 129, 0.7)',
                        hoverBackgroundColor: 'rgba(16, 185, 129, 0.9)',
                        borderColor: 'rgba(16, 185, 129, 1)',
                        borderWidth: 1, borderRadius: 6, borderSkipped: false, maxBarThickness: 32
                    },
                    {
                        label: "{{ __('Thưởng chờ duyệt') }}",
                        data: data.reward_pending_data,
                        backgroundColor: 'rgba(245, 158, 11, 0.7)',
                        hoverBackgroundColor: 'rgba(245, 158, 11, 0.9)',
                        borderColor: 'rgba(245, 158, 11, 1)',
                        borderWidth: 1, borderRadius: 6, borderSkipped: false, maxBarThickness: 32
                    }
                ];
                tooltipCb = (item) => `${item.dataset.label}: ${fmtVND(item.parsed.y)}`;
                yTickCb = (v) => v >= 1000000 ? (v / 1000000).toFixed(1) + 'M' : (v >= 1000 ? (v / 1000).toFixed(0) + 'K' : v);
            }

            canvas._chartInstance = new Chart(ctx, {
                type: 'bar',
                data: { labels: data.labels, datasets },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    animation: { duration: 600, easing: 'easeInOutQuart' },
                    interaction: { mode: 'index', intersect: false },
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top',
                            labels: { usePointStyle: true, pointStyle: 'circle', padding: 16, font: { size: 11, weight: '600' } }
                        },
                        title: {
                            display: true,
                            text: periodLabels[period] || '',
                            font: { size: 13, weight: '700' },
                            color: '#6366f1',
                            padding: { bottom: 8 }
                        },
                        tooltip: {
                            backgroundColor: 'rgba(15, 23, 42, 0.9)',
                            titleFont: { size: 12, weight: '700' },
                            bodyFont: { size: 11 },
                            padding: 12,
                            cornerRadius: 10,
                            callbacks: { label: tooltipCb }
                        }
                    },
                    scales: {
                        x: {
                            stacked: stacked,
                            grid: { display: false },
                            ticks: { font: { size: 10, weight: '600' }, color: '#94a3b8', maxRotation: period === 'year' ? 45 : 0 }
                        },
                        y: {
                            stacked: stacked,
                            beginAtZero: true,
                            grid: { color: 'rgba(148, 163, 184, 0.1)' },
                            ticks: {
                                font: { size: 10, weight: '600' },
                                color: '#94a3b8',
                                callback: yTickCb,
                                ...(this.taskChartView === 'submissions' ? { stepSize: 1, precision: 0 } : {})
                            }
                        }
                    }
                }
            });
        }
    };
}
</script>

@if($tab === 'tasks')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        // Kéo thả sắp xếp thứ tự nhiệm vụ (SortableJS) trên bảng desktop.
        const list = document.querySelector('.task-sortable-list');
        if (!list || typeof Sortable === 'undefined') return;

        // Mốc bắt đầu của trang hiện tại để giữ thứ tự đúng khi có phân trang.
        const startIndex = {{ $tasks->firstItem() ? $tasks->firstItem() - 1 : 0 }};

        new Sortable(list, {
            handle: '.handle',
            animation: 150,
            ghostClass: 'bg-shopee/5',
            onEnd: function () {
                const rows = list.querySelectorAll('tr[data-id]');
                // Gửi dạng object {sort_order: id} để controller gán trực tiếp giá trị sort.
                const orders = {};
                rows.forEach((row, index) => {
                    const sort = startIndex + index;
                    orders[sort] = parseInt(row.getAttribute('data-id'));
                    // Cập nhật nhãn số thứ tự hiển thị ngay trên giao diện.
                    const label = row.querySelector('.sort-order-label');
                    if (label) label.textContent = sort;
                });

                fetch('{{ route("admin.tasks.update_order") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ orders: orders })
                })
                .then(response => response.json())
                .then(data => {
                    window.dispatchEvent(new CustomEvent('toast', {
                        detail: {
                            text: data.message || '{{ __("Đã cập nhật thứ tự nhiệm vụ.") }}',
                            type: data.success ? 'success' : 'error'
                        }
                    }));
                })
                .catch(() => {
                    window.dispatchEvent(new CustomEvent('toast', {
                        detail: { text: '{{ __("Không thể kết nối tới máy chủ.") }}', type: 'error' }
                    }));
                });
            }
        });
    });
</script>
@endif
@endsection
