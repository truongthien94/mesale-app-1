@extends('layouts.admin')

@section('title', __('Email Campaign') . ' - ' . $siteName)

@section('content')
<div class="space-y-6">

    {{-- Tiêu đề trang --}}
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-slate-100">{{ __('Email Campaign') }}</h1>
            <p class="text-sm text-gray-500 dark:text-slate-400">{{ __('Quản lý các chiến dịch email marketing gửi đến thành viên') }}</p>
        </div>
        <a href="{{ route('admin.email_campaigns.create') }}"
           class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-semibold text-white bg-shopee hover:bg-shopee-dark rounded-xl shadow-sm transition-all shrink-0">
            <i data-lucide="plus" class="w-4 h-4"></i>
            {{ __('Tạo chiến dịch mới') }}
        </a>
    </div>

    {{-- Stats Cards --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-2xl p-4 flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-blue-50 dark:bg-blue-950/30 flex items-center justify-center shrink-0">
                <i data-lucide="mail" class="w-5 h-5 text-blue-600 dark:text-blue-400"></i>
            </div>
            <div>
                <p class="text-xs text-gray-500 dark:text-slate-400">{{ __('Tổng chiến dịch') }}</p>
                <p class="text-xl font-bold text-gray-900 dark:text-slate-100">{{ number_format($stats['total']) }}</p>
            </div>
        </div>
        <div class="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-2xl p-4 flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-green-50 dark:bg-green-950/30 flex items-center justify-center shrink-0">
                <i data-lucide="check-circle" class="w-5 h-5 text-green-600 dark:text-green-400"></i>
            </div>
            <div>
                <p class="text-xs text-gray-500 dark:text-slate-400">{{ __('Đã gửi xong') }}</p>
                <p class="text-xl font-bold text-gray-900 dark:text-slate-100">{{ number_format($stats['sent']) }}</p>
            </div>
        </div>
        <div class="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-2xl p-4 flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-gray-100 dark:bg-slate-800 flex items-center justify-center shrink-0">
                <i data-lucide="file-edit" class="w-5 h-5 text-gray-500 dark:text-slate-400"></i>
            </div>
            <div>
                <p class="text-xs text-gray-500 dark:text-slate-400">{{ __('Đang soạn thảo') }}</p>
                <p class="text-xl font-bold text-gray-900 dark:text-slate-100">{{ number_format($stats['draft']) }}</p>
            </div>
        </div>
        <div class="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-2xl p-4 flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-blue-50 dark:bg-blue-950/30 flex items-center justify-center shrink-0">
                <i data-lucide="clock" class="w-5 h-5 text-blue-500 dark:text-blue-400"></i>
            </div>
            <div>
                <p class="text-xs text-gray-500 dark:text-slate-400">{{ __('Đã lên lịch') }}</p>
                <p class="text-xl font-bold text-gray-900 dark:text-slate-100">{{ number_format($stats['scheduled']) }}</p>
            </div>
        </div>
    </div>

    {{-- Bộ lọc --}}
    <div class="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-2xl p-5">
        <form action="{{ route('admin.email_campaigns.index') }}" method="GET" class="flex flex-wrap gap-3 items-end">
            <div class="flex-1 min-w-[200px]">
                <label class="block text-xs font-semibold text-gray-600 dark:text-slate-400 mb-1">{{ __('Tìm kiếm') }}</label>
                <div class="relative">
                    <input type="text" name="search" value="{{ request('search') }}"
                           placeholder="{{ __('Tên chiến dịch, tiêu đề email...') }}"
                           class="w-full pl-8 pr-3 py-2 text-xs border border-gray-200 dark:border-slate-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-800 dark:text-slate-200">
                    <i data-lucide="search" class="w-3.5 h-3.5 text-gray-400 absolute left-2.5 top-1/2 -translate-y-1/2"></i>
                </div>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 dark:text-slate-400 mb-1">{{ __('Trạng thái') }}</label>
                <select name="status" class="px-3 py-2 text-xs border border-gray-200 dark:border-slate-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-white dark:bg-slate-800 dark:text-slate-200">
                    <option value="all" {{ request('status') === 'all' || !request('status') ? 'selected' : '' }}>{{ __('Tất cả') }}</option>
                    @foreach(\App\Models\EmailCampaign::STATUSES as $val => $label)
                        <option value="{{ $val }}" {{ request('status') === $val ? 'selected' : '' }}>{{ __($label) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex gap-2">
                @if(request('search') || (request('status') && request('status') !== 'all'))
                    <a href="{{ route('admin.email_campaigns.index') }}"
                       class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-medium text-gray-600 dark:text-slate-400 bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 rounded-xl hover:bg-gray-50 dark:hover:bg-slate-750 transition-all">
                        <i data-lucide="rotate-ccw" class="w-3.5 h-3.5"></i>
                        {{ __('Xóa bộ lọc') }}
                    </a>
                @endif
                <button type="submit"
                        class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-semibold text-white bg-shopee hover:bg-shopee-dark rounded-xl transition-all">
                    <i data-lucide="filter" class="w-3.5 h-3.5"></i>
                    {{ __('Lọc') }}
                </button>
            </div>
        </form>
    </div>

    {{-- Danh sách chiến dịch --}}
    <div class="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-2xl overflow-hidden">
        @if($campaigns->isEmpty())
            <div class="flex flex-col items-center justify-center py-16 text-gray-400 dark:text-slate-500">
                <i data-lucide="mail-x" class="w-12 h-12 mb-3 opacity-30"></i>
                <p class="font-semibold text-sm">{{ __('Chưa có chiến dịch email nào') }}</p>
                <p class="text-xs mt-1">{{ __('Tạo chiến dịch đầu tiên để bắt đầu email marketing.') }}</p>
                <a href="{{ route('admin.email_campaigns.create') }}"
                   class="mt-4 inline-flex items-center gap-2 px-4 py-2 text-xs font-semibold text-white bg-shopee rounded-xl hover:bg-shopee-dark transition-all">
                    <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                    {{ __('Tạo chiến dịch mới') }}
                </a>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-xs">
                    <thead>
                        <tr class="border-b border-gray-100 dark:border-slate-800 bg-gray-50/70 dark:bg-slate-950/40">
                            <th class="px-4 py-3 text-left font-bold text-gray-500 dark:text-slate-400 uppercase tracking-wider">{{ __('Chiến dịch') }}</th>
                            <th class="px-4 py-3 text-left font-bold text-gray-500 dark:text-slate-400 uppercase tracking-wider hidden md:table-cell">{{ __('Đối tượng') }}</th>
                            <th class="px-4 py-3 text-center font-bold text-gray-500 dark:text-slate-400 uppercase tracking-wider hidden lg:table-cell">{{ __('Đã gửi / Tổng') }}</th>
                            <th class="px-4 py-3 text-center font-bold text-gray-500 dark:text-slate-400 uppercase tracking-wider">{{ __('Trạng thái') }}</th>
                            <th class="px-4 py-3 text-left font-bold text-gray-500 dark:text-slate-400 uppercase tracking-wider hidden lg:table-cell">{{ __('Ngày tạo') }}</th>
                            <th class="px-4 py-3 text-right font-bold text-gray-500 dark:text-slate-400 uppercase tracking-wider">{{ __('Thao tác') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-slate-800">
                        @foreach($campaigns as $campaign)
                        <tr class="hover:bg-gray-50/50 dark:hover:bg-slate-800/30 transition-colors">
                            <td class="px-4 py-3">
                                <div class="flex items-start gap-3">
                                    <div class="w-9 h-9 rounded-xl bg-shopee/10 dark:bg-shopee/20 flex items-center justify-center shrink-0 mt-0.5">
                                        <i data-lucide="mail" class="w-4 h-4 text-shopee"></i>
                                    </div>
                                    <div>
                                        <a href="{{ route('admin.email_campaigns.show', $campaign) }}"
                                           class="font-semibold text-gray-900 dark:text-slate-100 hover:text-shopee dark:hover:text-shopee transition-colors line-clamp-1">
                                            {{ $campaign->name }}
                                        </a>
                                        <p class="text-gray-500 dark:text-slate-400 text-[11px] mt-0.5 line-clamp-1">{{ $campaign->subject }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-gray-600 dark:text-slate-300 hidden md:table-cell">
                                <span class="inline-flex items-center gap-1">
                                    <i data-lucide="users" class="w-3 h-3 text-gray-400"></i>
                                    {{ __($campaign->audience_label) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-center hidden lg:table-cell">
                                @if($campaign->total_recipients > 0)
                                    <div class="flex flex-col items-center gap-1">
                                        <span class="font-semibold text-gray-800 dark:text-slate-200">
                                            {{ number_format($campaign->total_sent) }} / {{ number_format($campaign->total_recipients) }}
                                        </span>
                                        <div class="w-24 bg-gray-200 dark:bg-slate-700 rounded-full h-1.5">
                                            <div class="bg-green-500 h-1.5 rounded-full" style="width: {{ $campaign->success_rate }}%"></div>
                                        </div>
                                        <span class="text-[10px] text-gray-400">{{ $campaign->success_rate }}%</span>
                                    </div>
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center">
                                @php
                                    $color = match($campaign->status) {
                                        'draft'     => 'gray',
                                        'scheduled' => 'blue',
                                        'sending'   => 'yellow',
                                        'sent'      => 'green',
                                        'cancelled' => 'red',
                                        default     => 'gray',
                                    };
                                    $icon = match($campaign->status) {
                                        'draft'     => 'file-edit',
                                        'scheduled' => 'clock',
                                        'sending'   => 'loader',
                                        'sent'      => 'check-circle-2',
                                        'cancelled' => 'x-circle',
                                        default     => 'help-circle',
                                    };
                                @endphp
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold
                                    {{ $color === 'green' ? 'bg-green-100 text-green-700 dark:bg-green-950/40 dark:text-green-400' : '' }}
                                    {{ $color === 'blue' ? 'bg-blue-100 text-blue-700 dark:bg-blue-950/40 dark:text-blue-400' : '' }}
                                    {{ $color === 'yellow' ? 'bg-yellow-100 text-yellow-700 dark:bg-yellow-950/40 dark:text-yellow-400' : '' }}
                                    {{ $color === 'red' ? 'bg-red-100 text-red-700 dark:bg-red-950/40 dark:text-red-400' : '' }}
                                    {{ $color === 'gray' ? 'bg-gray-100 text-gray-600 dark:bg-slate-800 dark:text-slate-400' : '' }}
                                ">
                                    <i data-lucide="{{ $icon }}" class="w-2.5 h-2.5 {{ $campaign->status === 'sending' ? 'animate-spin' : '' }}"></i>
                                    {{ __($campaign->status_label) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-gray-500 dark:text-slate-400 hidden lg:table-cell">
                                {{ $campaign->created_at->format('d/m/Y H:i') }}
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-end gap-1"
                                     x-data="{ open: false, menuX: 0, menuY: 0, toggle(e) { const r = e.currentTarget.getBoundingClientRect(); this.menuX = r.right - 176; this.menuY = r.bottom + 4; this.open = !this.open; } }"
                                     @click.outside="open = false" @keydown.escape.window="open = false" @scroll.window="open = false" @resize.window="open = false">
                                    {{-- Nút xem chi tiết --}}
                                    <a href="{{ route('admin.email_campaigns.show', $campaign) }}"
                                       class="p-1.5 text-gray-400 hover:text-shopee hover:bg-shopee/5 rounded-lg transition-all"
                                       title="{{ __('Xem chi tiết') }}">
                                        <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                                    </a>

                                    {{-- Nút gửi (chỉ draft/scheduled) --}}
                                    @if(in_array($campaign->status, ['draft', 'scheduled']))
                                        <form action="{{ route('admin.email_campaigns.send', $campaign) }}" method="POST"
                                              onsubmit="return confirm('{{ __('Bạn có chắc muốn gửi chiến dịch này đến toàn bộ người nhận?') }}')">
                                            @csrf
                                            <button type="submit"
                                                    class="p-1.5 text-green-500 hover:text-green-700 hover:bg-green-50 dark:hover:bg-green-950/20 rounded-lg transition-all"
                                                    title="{{ __('Gửi ngay') }}">
                                                <i data-lucide="send" class="w-3.5 h-3.5"></i>
                                            </button>
                                        </form>
                                    @endif

                                    {{-- Dropdown thêm --}}
                                    <div class="relative">
                                        <button @click="toggle($event)"
                                                class="p-1.5 text-gray-400 hover:text-gray-600 hover:bg-gray-50 dark:hover:bg-slate-800 rounded-lg transition-all">
                                            <i data-lucide="more-horizontal" class="w-3.5 h-3.5"></i>
                                        </button>
                                        {{-- Dùng position:fixed + toạ độ động để dropdown không bị overflow của bảng cắt mất --}}
                                        <div x-show="open" x-cloak x-transition
                                             :style="`left: ${menuX}px; top: ${menuY}px`"
                                             class="fixed w-44 bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-700 rounded-xl shadow-lg z-50 py-1">
                                            @if(in_array($campaign->status, ['draft', 'scheduled']))
                                            <a href="{{ route('admin.email_campaigns.edit', $campaign) }}"
                                               class="flex items-center gap-2 px-3 py-2 text-xs text-gray-700 dark:text-slate-300 hover:bg-gray-50 dark:hover:bg-slate-800 transition-colors">
                                                <i data-lucide="pencil" class="w-3.5 h-3.5 text-gray-400"></i>
                                                {{ __('Chỉnh sửa') }}
                                            </a>
                                            @endif
                                            <form action="{{ route('admin.email_campaigns.duplicate', $campaign) }}" method="POST">
                                                @csrf
                                                <button type="submit"
                                                        class="w-full flex items-center gap-2 px-3 py-2 text-xs text-gray-700 dark:text-slate-300 hover:bg-gray-50 dark:hover:bg-slate-800 transition-colors">
                                                    <i data-lucide="copy" class="w-3.5 h-3.5 text-gray-400"></i>
                                                    {{ __('Nhân bản') }}
                                                </button>
                                            </form>
                                            @if(in_array($campaign->status, ['sending', 'sent']))
                                            <form action="{{ route('admin.email_campaigns.refresh_stats', $campaign) }}" method="POST">
                                                @csrf
                                                <button type="submit"
                                                        class="w-full flex items-center gap-2 px-3 py-2 text-xs text-gray-700 dark:text-slate-300 hover:bg-gray-50 dark:hover:bg-slate-800 transition-colors">
                                                    <i data-lucide="refresh-cw" class="w-3.5 h-3.5 text-gray-400"></i>
                                                    {{ __('Làm mới thống kê') }}
                                                </button>
                                            </form>
                                            @endif
                                            <div class="border-t border-gray-100 dark:border-slate-800 my-1"></div>
                                            <form action="{{ route('admin.email_campaigns.destroy', $campaign) }}" method="POST"
                                                  onsubmit="return confirm('{{ __('Xoá chiến dịch này? Hành động không thể hoàn tác.') }}')">
                                                @csrf @method('DELETE')
                                                <button type="submit"
                                                        class="w-full flex items-center gap-2 px-3 py-2 text-xs text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-950/20 transition-colors">
                                                    <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                                    {{ __('Xoá chiến dịch') }}
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Phân trang --}}
            @if($campaigns->hasPages())
            <div class="px-4 py-3 border-t border-gray-100 dark:border-slate-800">
                {{ $campaigns->links() }}
            </div>
            @endif
        @endif
    </div>

</div>
@endsection
