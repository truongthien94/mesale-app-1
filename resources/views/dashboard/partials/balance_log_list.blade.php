<!-- Bảng Dữ Liệu Desktop -->
<div class="hidden md:block bg-white dark:bg-slate-900 rounded-2xl shadow-md border border-gray-100 dark:border-slate-800/80 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse whitespace-nowrap">
            <thead>
                <tr class="border-b border-gray-100 dark:border-slate-800 text-xs font-bold text-gray-400 bg-gray-50/50 dark:bg-slate-900/50">
                    <th class="p-4">{{ __('Nội dung biến động') }}</th>
                    <th class="p-4 text-center">{{ __('Loại') }}</th>
                    <th class="p-4 text-center">{{ __('Số dư trước') }}</th>
                    <th class="p-4 text-center">{{ __('Biến động') }}</th>
                    <th class="p-4 text-center">{{ __('Số dư sau') }}</th>
                    <th class="p-4 text-center">{{ __('Thời gian') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-slate-800 text-xs">
                @forelse($logs as $log)
                    <tr class="hover:bg-gray-50 dark:hover:bg-slate-800/75 transition-colors">
                        <td class="p-4 font-semibold text-gray-700 dark:text-slate-200">
                            {{ $log->description }}
                        </td>
                        <td class="p-4 text-center">
                            @switch($log->type)
                                @case('cashback')
                                    <span class="inline-flex px-2 py-0.5 rounded-full text-[9px] font-bold bg-green-50 text-green-600 border border-green-100 dark:bg-green-950/20 dark:text-green-400 dark:border-green-900/30">{{ __('Hoàn tiền') }}</span>
                                    @break
                                @case('referral')
                                    <span class="inline-flex px-2 py-0.5 rounded-full text-[9px] font-bold bg-blue-50 text-blue-600 border border-blue-100 dark:bg-blue-950/20 dark:text-blue-400 dark:border-blue-900/30">{{ __('Hoa hồng MLM') }}</span>
                                    @break
                                @case('checkin')
                                    <span class="inline-flex px-2 py-0.5 rounded-full text-[9px] font-bold bg-amber-50 text-amber-600 border border-amber-100 dark:bg-amber-950/20 dark:text-amber-400 dark:border-amber-900/30">{{ __('Điểm danh') }}</span>
                                    @break
                                @case('withdraw_request')
                                    <span class="inline-flex px-2 py-0.5 rounded-full text-[9px] font-bold bg-red-50 text-red-600 border border-red-100 dark:bg-red-950/20 dark:text-red-400 dark:border-red-900/30">{{ __('Rút tiền') }}</span>
                                    @break
                                @case('withdraw_refund')
                                    <span class="inline-flex px-2 py-0.5 rounded-full text-[9px] font-bold bg-purple-50 text-purple-600 border border-purple-100 dark:bg-purple-950/20 dark:text-purple-400 dark:border-purple-900/30">{{ __('Hoàn tiền rút') }}</span>
                                    @break
                                @case('admin_adjust')
                                    <span class="inline-flex px-2 py-0.5 rounded-full text-[9px] font-bold bg-gray-50 text-gray-600 border border-gray-100 dark:bg-slate-800 dark:text-slate-400 dark:border-slate-700/50">{{ __('Admin sửa') }}</span>
                                    @break
                                @case('task_reward')
                                    <span class="inline-flex px-2 py-0.5 rounded-full text-[9px] font-bold bg-teal-50 text-teal-600 border border-teal-100 dark:bg-teal-950/20 dark:text-teal-400 dark:border-teal-900/30">{{ __('Nhiệm vụ') }}</span>
                                    @break
                                @default
                                    <span class="inline-flex px-2 py-0.5 rounded-full text-[9px] font-bold bg-slate-50 text-slate-600 border border-slate-100 dark:bg-slate-800 dark:text-slate-400 dark:border-slate-700/50">{{ $log->type }}</span>
                            @endswitch
                        </td>
                        <td class="p-4 text-center font-mono text-gray-500 dark:text-slate-400 font-medium">
                            {{ \App\Helpers\CurrencyHelper::format($log->amount_before) }}
                        </td>
                        <td class="p-4 text-center font-bold text-sm">
                            <span class="{{ $log->amount_change > 0 ? 'text-green-600 dark:text-green-400' : 'text-red-500 dark:text-red-400' }}">
                                {{ ($log->amount_change > 0 ? '+' : '') . \App\Helpers\CurrencyHelper::format($log->amount_change) }}
                            </span>
                        </td>
                        <td class="p-4 text-center font-mono text-gray-700 dark:text-slate-200 font-semibold">
                            {{ \App\Helpers\CurrencyHelper::format($log->amount_after) }}
                        </td>
                        <td class="p-4 text-center text-gray-400 dark:text-slate-500 text-[10px]">
                            {{ $log->created_at->format('d/m/Y H:i') }} ({{ $log->created_at->diffForHumans() }})
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="p-8 text-center text-gray-400 dark:text-slate-500">
                            {{ __('Chưa có biến động số dư nào được ghi nhận.') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Phân trang Desktop -->
    @if($logs->hasPages())
        <div class="p-4 border-t border-gray-100 dark:border-slate-800 ajax-pagination">
            {{ $logs->links() }}
        </div>
    @endif
</div>

<!-- Danh sách dạng Card trên Mobile -->
<div class="block md:hidden space-y-4">
    @forelse($logs as $log)
        <div class="bg-white dark:bg-slate-900 p-4 rounded-2xl border border-gray-100/80 dark:border-slate-800/80 shadow-sm space-y-3 hover:shadow-md transition-all duration-300">
            <!-- Header Card: Loại & Thời gian -->
            <div class="flex justify-between items-center pb-2.5 border-b border-gray-100/50 dark:border-slate-800/50">
                <div>
                    @switch($log->type)
                        @case('cashback')
                            <span class="inline-flex px-2 py-0.5 rounded-full text-[9px] font-bold bg-green-50 text-green-600 border border-green-100 dark:bg-green-950/20 dark:text-green-400 dark:border-green-900/30">{{ __('Hoàn tiền') }}</span>
                            @break
                        @case('referral')
                            <span class="inline-flex px-2 py-0.5 rounded-full text-[9px] font-bold bg-blue-50 text-blue-600 border border-blue-100 dark:bg-blue-950/20 dark:text-blue-400 dark:border-blue-900/30">{{ __('Hoa hồng MLM') }}</span>
                            @break
                        @case('checkin')
                            <span class="inline-flex px-2 py-0.5 rounded-full text-[9px] font-bold bg-amber-50 text-amber-600 border border-amber-100 dark:bg-amber-950/20 dark:text-amber-400 dark:border-amber-900/30">{{ __('Điểm danh') }}</span>
                            @break
                        @case('withdraw_request')
                            <span class="inline-flex px-2 py-0.5 rounded-full text-[9px] font-bold bg-red-50 text-red-600 border border-red-100 dark:bg-red-950/20 dark:text-red-400 dark:border-red-900/30">{{ __('Rút tiền') }}</span>
                            @break
                        @case('withdraw_refund')
                            <span class="inline-flex px-2 py-0.5 rounded-full text-[9px] font-bold bg-purple-50 text-purple-600 border border-purple-100 dark:bg-purple-950/20 dark:text-purple-400 dark:border-purple-900/30">{{ __('Hoàn tiền rút') }}</span>
                            @break
                        @case('admin_adjust')
                            <span class="inline-flex px-2 py-0.5 rounded-full text-[9px] font-bold bg-gray-50 text-gray-600 border border-gray-100 dark:bg-slate-800 dark:text-slate-400 dark:border-slate-700/50">{{ __('Admin sửa') }}</span>
                            @break
                        @case('task_reward')
                            <span class="inline-flex px-2 py-0.5 rounded-full text-[9px] font-bold bg-teal-50 text-teal-600 border border-teal-100 dark:bg-teal-950/20 dark:text-teal-400 dark:border-teal-900/30">{{ __('Nhiệm vụ') }}</span>
                            @break
                        @default
                            <span class="inline-flex px-2 py-0.5 rounded-full text-[9px] font-bold bg-slate-50 text-slate-600 border border-slate-100 dark:bg-slate-800 dark:text-slate-400 dark:border-slate-700/50">{{ $log->type }}</span>
                    @endswitch
                </div>
                <span class="text-[10px] text-gray-400 dark:text-slate-500 font-medium">{{ $log->created_at->format('d/m/Y H:i') }}</span>
            </div>

            <!-- Body Card: Nội dung biến động -->
            <div class="text-xs font-bold text-gray-800 dark:text-slate-200">
                {{ $log->description }}
            </div>

            <!-- Footer Card: Số dư trước/sau & Số tiền biến động -->
            <div class="flex justify-between items-center pt-2.5 border-t border-gray-100/50 dark:border-slate-800/50">
                <div class="text-[10px] text-gray-400 dark:text-slate-500 space-y-0.5 font-medium">
                    <div>{{ __('Trước:') }} <span class="font-mono text-gray-600 dark:text-slate-400">{{ \App\Helpers\CurrencyHelper::format($log->amount_before) }}</span></div>
                    <div>{{ __('Sau:') }} <span class="font-mono text-gray-700 dark:text-slate-300 font-bold">{{ \App\Helpers\CurrencyHelper::format($log->amount_after) }}</span></div>
                </div>
                <div class="text-right">
                    <span class="text-sm font-extrabold {{ $log->amount_change > 0 ? 'text-green-600 dark:text-green-400' : 'text-red-500 dark:text-red-400' }}">
                        {{ ($log->amount_change > 0 ? '+' : '') . \App\Helpers\CurrencyHelper::format($log->amount_change) }}
                    </span>
                </div>
            </div>
        </div>
    @empty
        <div class="bg-white dark:bg-slate-900 p-8 rounded-2xl border border-gray-100 dark:border-slate-800/80 text-center text-gray-400 dark:text-slate-500">
            {{ __('Chưa có biến động số dư nào được ghi nhận.') }}
        </div>
    @endforelse

    <!-- Phân trang Mobile -->
    @if($logs->hasPages())
        <div class="p-4 bg-white dark:bg-slate-900 rounded-2xl border border-gray-100 dark:border-slate-800/80 shadow-sm ajax-pagination">
            {{ $logs->links() }}
        </div>
    @endif
</div>
