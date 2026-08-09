{{-- Nhãn trạng thái đơn hoàn tiền — dùng chung cho các danh sách đơn hàng. Nhận biến $status --}}
@switch($status)
    @case('approved')
        <span class="inline-flex items-center gap-1.5 text-[11px] font-bold text-green-600 dark:text-green-400">
            <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span>
            {{ __('Đã duyệt') }}
        </span>
        @break
    @case('rejected')
    @case('fraud')
        <span class="inline-flex items-center gap-1.5 text-[11px] font-bold text-red-600 dark:text-red-400">
            <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>
            {{ __('Từ chối') }}
        </span>
        @break
    @default
        <span class="inline-flex items-center gap-1.5 text-[11px] font-bold text-yellow-600 dark:text-yellow-400">
            <span class="w-1.5 h-1.5 rounded-full bg-yellow-500"></span>
            {{ __('Chờ duyệt') }}
        </span>
@endswitch
