@extends('layouts.admin')

@section('title', __('Chi Tiết Chiến Dịch Email') . ' - ' . $siteName)

@section('content')
<div class="space-y-6" x-data="campaignShow()">

    {{-- Tiêu đề & actions --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.email_campaigns.index') }}"
               class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-slate-200 hover:bg-gray-100 dark:hover:bg-slate-800 rounded-xl transition-all">
                <i data-lucide="arrow-left" class="w-4 h-4"></i>
            </a>
            <div>
                <h1 class="text-xl font-bold text-gray-900 dark:text-slate-100 line-clamp-1">{{ $emailCampaign->name }}</h1>
                <div class="flex items-center gap-2 mt-0.5">
                    @php
                        $color = match($emailCampaign->status) {
                            'draft'     => 'gray',
                            'scheduled' => 'blue',
                            'sending'   => 'yellow',
                            'sent'      => 'green',
                            'cancelled' => 'red',
                            default     => 'gray',
                        };
                        $icon = match($emailCampaign->status) {
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
                        {{ $color === 'gray' ? 'bg-gray-100 text-gray-600 dark:bg-slate-800 dark:text-slate-400' : '' }}">
                        <i data-lucide="{{ $icon }}" class="w-2.5 h-2.5 {{ $emailCampaign->status === 'sending' ? 'animate-spin' : '' }}"></i>
                        {{ __($emailCampaign->status_label) }}
                    </span>
                    <span class="text-xs text-gray-400 dark:text-slate-500">
                        {{ __('Tạo lúc') }} {{ $emailCampaign->created_at->format('d/m/Y H:i') }}
                        @if($emailCampaign->creator) {{ __('bởi') }} {{ $emailCampaign->creator->name }} @endif
                    </span>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-2 shrink-0">
            {{-- Làm mới stats --}}
            @if(in_array($emailCampaign->status, ['sending', 'sent']))
            <form action="{{ route('admin.email_campaigns.refresh_stats', $emailCampaign) }}" method="POST">
                @csrf
                <button type="submit" id="refresh-stats-btn"
                        class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-semibold text-gray-600 dark:text-slate-400 bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 rounded-xl hover:bg-gray-50 dark:hover:bg-slate-750 transition-all">
                    <i data-lucide="refresh-cw" class="w-3.5 h-3.5"></i>
                    {{ __('Làm mới số liệu') }}
                </button>
            </form>
            @endif

            {{-- Nhân bản --}}
            <form action="{{ route('admin.email_campaigns.duplicate', $emailCampaign) }}" method="POST">
                @csrf
                <button type="submit"
                        class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-semibold text-gray-600 dark:text-slate-400 bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 rounded-xl hover:bg-gray-50 dark:hover:bg-slate-750 transition-all">
                    <i data-lucide="copy" class="w-3.5 h-3.5"></i>
                    {{ __('Nhân bản') }}
                </button>
            </form>

            {{-- Sửa (nếu còn draft/scheduled) --}}
            @if(in_array($emailCampaign->status, ['draft', 'scheduled']))
            <a href="{{ route('admin.email_campaigns.edit', $emailCampaign) }}"
               class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-semibold text-white bg-shopee hover:bg-shopee-dark rounded-xl transition-all">
                <i data-lucide="pencil" class="w-3.5 h-3.5"></i>
                {{ __('Chỉnh sửa') }}
            </a>
            @endif

            {{-- Gửi ngay (nếu còn draft/scheduled) --}}
            @if(in_array($emailCampaign->status, ['draft', 'scheduled']))
            <form action="{{ route('admin.email_campaigns.send', $emailCampaign) }}" method="POST"
                  onsubmit="return confirm('{{ __('Xác nhận gửi chiến dịch này đến') }} {{ number_format($recipientCount) }} {{ __('người nhận?') }}')">
                @csrf
                <button type="submit"
                        class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-bold text-white bg-green-600 hover:bg-green-700 rounded-xl transition-all shadow-sm">
                    <i data-lucide="send" class="w-3.5 h-3.5"></i>
                    {{ __('Gửi ngay') }}
                </button>
            </form>
            @endif
        </div>
    </div>

    {{-- Stats Cards --}}
    @if($emailCampaign->total_recipients > 0)
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-2xl p-4">
            <p class="text-xs text-gray-500 dark:text-slate-400 flex items-center gap-1.5 mb-2">
                <i data-lucide="users" class="w-3.5 h-3.5"></i>
                {{ __('Tổng người nhận') }}
            </p>
            <p class="text-2xl font-black text-gray-900 dark:text-slate-100">{{ number_format($emailCampaign->total_recipients) }}</p>
        </div>
        <div class="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-2xl p-4">
            <p class="text-xs text-gray-500 dark:text-slate-400 flex items-center gap-1.5 mb-2">
                <i data-lucide="check-circle-2" class="w-3.5 h-3.5 text-green-500"></i>
                {{ __('Đã gửi thành công') }}
            </p>
            <p class="text-2xl font-black text-green-600 dark:text-green-400">{{ number_format($emailCampaign->total_sent) }}</p>
        </div>
        <div class="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-2xl p-4">
            <p class="text-xs text-gray-500 dark:text-slate-400 flex items-center gap-1.5 mb-2">
                <i data-lucide="x-circle" class="w-3.5 h-3.5 text-red-500"></i>
                {{ __('Gửi thất bại') }}
            </p>
            <p class="text-2xl font-black text-red-600 dark:text-red-400">{{ number_format($emailCampaign->total_failed) }}</p>
        </div>
        <div class="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-2xl p-4">
            <p class="text-xs text-gray-500 dark:text-slate-400 flex items-center gap-1.5 mb-2">
                <i data-lucide="percent" class="w-3.5 h-3.5 text-blue-500"></i>
                {{ __('Tỷ lệ thành công') }}
            </p>
            <p class="text-2xl font-black text-blue-600 dark:text-blue-400">{{ $emailCampaign->success_rate }}%</p>
            <div class="mt-2 w-full bg-gray-200 dark:bg-slate-700 rounded-full h-1.5">
                <div class="bg-blue-500 h-1.5 rounded-full transition-all" style="width: {{ $emailCampaign->success_rate }}%"></div>
            </div>
        </div>
    </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

        {{-- Thông tin chiến dịch --}}
        <div class="lg:col-span-5 space-y-5">

            {{-- Details card --}}
            <div class="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-2xl p-5 space-y-3">
                <h3 class="font-bold text-sm text-gray-800 dark:text-slate-100 flex items-center gap-2 pb-3 border-b border-gray-100 dark:border-slate-800">
                    <i data-lucide="info" class="w-4 h-4 text-shopee"></i>
                    {{ __('Thông tin chiến dịch') }}
                </h3>
                <div class="space-y-2.5">
                    <div class="flex items-start gap-3 text-xs">
                        <span class="text-gray-400 dark:text-slate-500 w-28 shrink-0 font-semibold pt-0.5">{{ __('Tiêu đề email') }}</span>
                        <span class="text-gray-800 dark:text-slate-200 font-medium">{{ $emailCampaign->subject }}</span>
                    </div>
                    <div class="flex items-start gap-3 text-xs">
                        <span class="text-gray-400 dark:text-slate-500 w-28 shrink-0 font-semibold pt-0.5">{{ __('Người gửi') }}</span>
                        <span class="text-gray-800 dark:text-slate-200">{{ $emailCampaign->from_name }} &lt;{{ $emailCampaign->from_email }}&gt;</span>
                    </div>
                    <div class="flex items-start gap-3 text-xs">
                        <span class="text-gray-400 dark:text-slate-500 w-28 shrink-0 font-semibold pt-0.5">{{ __('Đối tượng') }}</span>
                        <span class="text-gray-800 dark:text-slate-200">{{ __($emailCampaign->audience_label) }}</span>
                    </div>
                    @if($emailCampaign->target_filter)
                    <div class="flex items-start gap-3 text-xs">
                        <span class="text-gray-400 dark:text-slate-500 w-28 shrink-0 font-semibold pt-0.5">{{ __('Lọc bổ sung') }}</span>
                        <div class="space-y-1">
                            @if(!empty($emailCampaign->target_filter['min_balance']))
                            <span class="text-gray-700 dark:text-slate-300">{{ __('Số dư tối thiểu') }}: {{ number_format($emailCampaign->target_filter['min_balance']) }}đ</span>
                            @endif
                            @if(!empty($emailCampaign->target_filter['registered_after']))
                            <span class="text-gray-700 dark:text-slate-300 block">{{ __('Đăng ký sau') }}: {{ $emailCampaign->target_filter['registered_after'] }}</span>
                            @endif
                        </div>
                    </div>
                    @endif
                    @if($emailCampaign->scheduled_at)
                    <div class="flex items-start gap-3 text-xs">
                        <span class="text-gray-400 dark:text-slate-500 w-28 shrink-0 font-semibold pt-0.5">{{ __('Lên lịch lúc') }}</span>
                        <span class="text-gray-800 dark:text-slate-200">{{ $emailCampaign->scheduled_at->format('d/m/Y H:i') }}</span>
                    </div>
                    @endif
                    @if($emailCampaign->sent_at)
                    <div class="flex items-start gap-3 text-xs">
                        <span class="text-gray-400 dark:text-slate-500 w-28 shrink-0 font-semibold pt-0.5">{{ __('Bắt đầu gửi') }}</span>
                        <span class="text-gray-800 dark:text-slate-200">{{ $emailCampaign->sent_at->format('d/m/Y H:i') }}</span>
                    </div>
                    @endif
                    @if($emailCampaign->status === 'draft')
                    <div class="flex items-start gap-3 text-xs">
                        <span class="text-gray-400 dark:text-slate-500 w-28 shrink-0 font-semibold pt-0.5">{{ __('Ước tính người nhận') }}</span>
                        <span class="text-shopee font-bold text-sm">{{ number_format($recipientCount) }} {{ __('người') }}</span>
                    </div>
                    @endif
                    @if($pendingCount > 0)
                    <div class="flex items-start gap-3 text-xs">
                        <span class="text-gray-400 dark:text-slate-500 w-28 shrink-0 font-semibold pt-0.5">{{ __('Email chờ gửi') }}</span>
                        <span class="text-yellow-600 dark:text-yellow-400 font-bold">{{ number_format($pendingCount) }} {{ __('email') }}</span>
                    </div>
                    @endif
                </div>
            </div>

            {{-- Nội dung email preview --}}
            <div class="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-2xl p-5 space-y-3">
                <h3 class="font-bold text-sm text-gray-800 dark:text-slate-100 flex items-center gap-2 pb-3 border-b border-gray-100 dark:border-slate-800">
                    <i data-lucide="mail" class="w-4 h-4 text-shopee"></i>
                    {{ __('Nội dung email') }}
                </h3>
                <div class="border border-gray-200 dark:border-slate-700 rounded-xl overflow-hidden">
                    <iframe srcdoc="{{ $emailCampaign->body }}" class="w-full min-h-[300px]" frameborder="0"></iframe>
                </div>
            </div>
        </div>

        {{-- Nhật ký gửi email (AJAX) --}}
        <div class="lg:col-span-7">
            <div class="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-2xl overflow-hidden flex flex-col">
                
                {{-- Header --}}
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 px-5 py-4 border-b border-gray-100 dark:border-slate-800 bg-white dark:bg-slate-900">
                    <h3 class="font-bold text-sm text-gray-800 dark:text-slate-100 flex items-center gap-2">
                        <i data-lucide="scroll-text" class="w-4 h-4 text-shopee"></i>
                        {{ __('Nhật ký gửi email') }}
                        <span class="text-xs font-normal text-gray-400 dark:text-slate-500" x-show="totalLogs > 0" x-text="'(' + totalLogs + ' tổng)'"></span>
                    </h3>
                    
                    {{-- Ô tìm kiếm --}}
                    <div class="relative w-full sm:w-64">
                        <input type="text" 
                               x-model="search" 
                               @keyup.enter="searchLogs()"
                               placeholder="{{ __('Tìm email hoặc tên...') }}"
                               class="w-full pl-8 pr-12 py-1.5 text-xs border border-gray-200 dark:border-slate-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-shopee/20 focus:border-shopee bg-gray-50/50 dark:bg-slate-800 dark:text-slate-200">
                        <i data-lucide="search" class="absolute left-2.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-gray-400"></i>
                        <button @click="searchLogs()" 
                                class="absolute right-1 top-1/2 -translate-y-1/2 px-2 py-1 text-[10px] font-bold text-white bg-shopee hover:bg-shopee-dark rounded-lg transition-all">
                            {{ __('Tìm') }}
                        </button>
                    </div>
                </div>

                {{-- Body log --}}
                <div class="relative min-h-[300px]">
                    {{-- Trạng thái loading --}}
                    <div x-show="loading" class="absolute inset-0 bg-white/70 dark:bg-slate-900/70 z-10 flex items-center justify-center">
                        <div class="flex flex-col items-center gap-2">
                            <svg class="w-8 h-8 text-shopee" style="animation: spin 1s linear infinite;" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span class="text-xs font-semibold text-gray-500 dark:text-slate-400">{{ __('Đang tải dữ liệu...') }}</span>
                        </div>
                    </div>

                    {{-- Không có email nào trong hàng đợi / Không tìm thấy --}}
                    <div x-show="!loading && logs.length === 0" class="flex flex-col items-center justify-center py-20 text-gray-400 dark:text-slate-500">
                        <i data-lucide="inbox" class="w-10 h-10 mb-2 opacity-30"></i>
                        <p class="text-sm font-medium" x-text="search ? '{{ __('Không tìm thấy kết quả phù hợp') }}' : '{{ __('Chưa có email nào trong hàng đợi') }}'"></p>
                        <p class="text-xs mt-1" x-show="!search">{{ __('Nhấn "Gửi ngay" để bắt đầu chiến dịch') }}</p>
                    </div>

                    {{-- Bảng danh sách email --}}
                    <div x-show="logs.length > 0" class="overflow-x-auto">
                        <table class="w-full text-xs">
                            <thead>
                                <tr class="bg-gray-50/70 dark:bg-slate-950/40 border-b border-gray-100 dark:border-slate-800">
                                    <th class="px-4 py-2.5 text-left font-bold text-gray-500 dark:text-slate-400 uppercase tracking-wider">{{ __('Người nhận') }}</th>
                                    <th class="px-4 py-2.5 text-center font-bold text-gray-500 dark:text-slate-400 uppercase tracking-wider">{{ __('Trạng thái') }}</th>
                                    <th class="px-4 py-2.5 text-center font-bold text-gray-500 dark:text-slate-400 uppercase tracking-wider hidden sm:table-cell">{{ __('Lần thử') }}</th>
                                    <th class="px-4 py-2.5 text-right font-bold text-gray-500 dark:text-slate-400 uppercase tracking-wider hidden md:table-cell">{{ __('Gửi lúc') }}</th>
                                </tr>
                            </thead>
                            <template x-for="(log, idx) in logs" :key="idx">
                                <tbody class="divide-y divide-gray-100 dark:divide-slate-800">
                                    <tr class="hover:bg-gray-50/50 dark:hover:bg-slate-800/30 transition-colors">
                                        <td class="px-4 py-2.5">
                                            <div class="font-semibold text-gray-800 dark:text-slate-200 leading-none" x-text="log.to_name"></div>
                                            <div class="text-gray-500 dark:text-slate-400 mt-0.5" x-text="log.to_email"></div>
                                        </td>
                                        <td class="px-4 py-2.5 text-center">
                                            <template x-if="log.status === 'sent'">
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-green-100 text-green-700 dark:bg-green-950/40 dark:text-green-400">
                                                    <i data-lucide="check" class="w-2.5 h-2.5"></i> <span x-text="log.status_label"></span>
                                                </span>
                                            </template>
                                            <template x-if="log.status === 'failed'">
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-red-100 text-red-700 dark:bg-red-950/40 dark:text-red-400"
                                                      :title="log.error_message">
                                                    <i data-lucide="x" class="w-2.5 h-2.5"></i> <span x-text="log.status_label"></span>
                                                </span>
                                            </template>
                                            <template x-if="log.status !== 'sent' && log.status !== 'failed'">
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-yellow-100 text-yellow-700 dark:bg-yellow-950/40 dark:text-yellow-400">
                                                    <i data-lucide="clock" class="w-2.5 h-2.5"></i> <span x-text="log.status_label"></span>
                                                </span>
                                            </template>
                                        </td>
                                        <td class="px-4 py-2.5 text-center text-gray-500 dark:text-slate-400 hidden sm:table-cell" x-text="log.attempts"></td>
                                        <td class="px-4 py-2.5 text-right text-gray-500 dark:text-slate-400 hidden md:table-cell" x-text="log.sent_at_label"></td>
                                    </tr>
                                    {{-- Dòng hiển thị chi tiết mã lỗi gửi mail (chỉ hiện khi trạng thái thất bại và có nội dung lỗi) --}}
                                    <tr x-show="log.status === 'failed' && log.error_message" class="bg-red-50/40 dark:bg-red-950/10">
                                        <td colspan="4" class="px-4 py-2 border-t border-red-100/70 dark:border-red-900/30">
                                            <div class="flex items-start gap-1.5 text-[11px] text-red-600 dark:text-red-400 font-mono break-all">
                                                <i data-lucide="alert-triangle" class="w-3.5 h-3.5 shrink-0 mt-0.5"></i>
                                                <span><span class="font-bold">{{ __('Chi tiết lỗi:') }}</span> <span x-text="log.error_message"></span></span>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </template>
                        </table>
                    </div>
                </div>

                {{-- Pagination Footer --}}
                <div x-show="totalPages > 1" class="px-5 py-4 border-t border-gray-100 dark:border-slate-800 bg-gray-50/30 dark:bg-slate-900/50 flex flex-col sm:flex-row items-center justify-between gap-3 shrink-0">
                    <div class="text-xs text-gray-500 dark:text-slate-400">
                        {{ __('Trang') }} <span class="font-bold text-gray-700 dark:text-slate-300" x-text="page"></span> / <span x-text="totalPages"></span> ({{ __('Tổng cộng') }} <span x-text="totalLogs"></span>)
                    </div>
                    
                    <div class="flex items-center gap-1">
                        {{-- Trang trước --}}
                        <button type="button" 
                                @click="page > 1 && fetchLogs(page - 1)" 
                                :disabled="page <= 1"
                                class="p-1.5 border border-gray-200 dark:border-slate-700 rounded-lg hover:bg-gray-100 dark:hover:bg-slate-800 disabled:opacity-40 transition-all">
                            <i data-lucide="chevron-left" class="w-3.5 h-3.5 dark:text-slate-300"></i>
                        </button>
                        
                        {{-- Danh sách trang --}}
                        <div class="flex items-center gap-1">
                            <template x-for="p in getPaginationPages()" :key="p">
                                <div class="flex items-center">
                                    <template x-if="p === '...'">
                                        <span class="px-2 py-1 text-xs text-gray-400">...</span>
                                    </template>
                                    <template x-if="p !== '...'">
                                        <button type="button" 
                                                @click="fetchLogs(p)" 
                                                x-text="p"
                                                class="px-2.5 py-1 text-xs font-semibold rounded-lg border transition-all"
                                                :class="p === page 
                                                    ? 'bg-shopee border-shopee text-white shadow-sm' 
                                                    : 'border-gray-200 dark:border-slate-700 hover:bg-gray-100 dark:hover:bg-slate-800 dark:text-slate-300'"></button>
                                    </template>
                                </div>
                            </template>
                        </div>

                        {{-- Trang sau --}}
                        <button type="button" 
                                @click="page < totalPages && fetchLogs(page + 1)" 
                                :disabled="page >= totalPages"
                                class="p-1.5 border border-gray-200 dark:border-slate-700 rounded-lg hover:bg-gray-100 dark:hover:bg-slate-800 disabled:opacity-40 transition-all">
                            <i data-lucide="chevron-right" class="w-3.5 h-3.5 dark:text-slate-300"></i>
                        </button>
                    </div>
                </div>

            </div>
        </div>
    </div>

</div>
@endsection

@section('scripts')
@if($emailCampaign->status === 'sending')
<script>
// Tự động làm mới số liệu khi campaign đang gửi (mỗi 30 giây)
let autoRefresh = setInterval(function() {
    fetch('{{ route('admin.email_campaigns.refresh_stats', $emailCampaign) }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        }
    }).then(r => r.json()).then(data => {
        if (data.status === 'sent') {
            clearInterval(autoRefresh);
            location.reload();
        }
    });
}, 30000);
</script>
@endif

<style>
@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}
</style>
<script>
function campaignShow() {
    return {
        // Trạng thái nhật ký gửi email
        logs: [],
        search: '',
        page: 1,
        totalPages: 1,
        totalLogs: 0,
        loading: false,

        init() {
            this.fetchLogs();
        },

        fetchLogs(page = 1) {
            this.page = page;
            this.loading = true;
            
            const params = new URLSearchParams({
                page: this.page,
                search: this.search
            });

            fetch('{{ route('admin.email_campaigns.queue_logs', $emailCampaign) }}?' + params.toString(), {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(r => r.json())
            .then(res => {
                if (res.success) {
                    this.logs = res.data;
                    this.page = res.current_page;
                    this.totalPages = res.last_page;
                    this.totalLogs = res.total;
                }
            })
            .catch(err => console.error(err))
            .finally(() => {
                this.loading = false;
                this.$nextTick(() => { if (window.lucide) window.lucide.createIcons(); });
            });
        },

        searchLogs() {
            this.fetchLogs(1);
        },

        // Sinh danh sách trang phân trang
        getPaginationPages() {
            const pages = [];
            const side = 2; // Số trang hiển thị mỗi bên của trang hiện tại
            let start = Math.max(1, this.page - side);
            let end = Math.min(this.totalPages, this.page + side);

            if (start > 1) {
                pages.push(1);
                if (start > 2) pages.push('...');
            }

            for (let i = start; i <= end; i++) {
                pages.push(i);
            }

            if (end < this.totalPages) {
                if (end < this.totalPages - 1) pages.push('...');
                pages.push(this.totalPages);
            }

            return pages;
        }
    };
}
</script>
@endsection
