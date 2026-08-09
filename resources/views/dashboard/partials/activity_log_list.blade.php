<!-- Bảng Dữ Liệu Desktop -->
<div class="hidden md:block bg-white dark:bg-slate-900 rounded-2xl shadow-md border border-gray-100 dark:border-slate-800/80 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse whitespace-nowrap">
            <thead>
                <tr class="border-b border-gray-100 dark:border-slate-800 text-xs font-bold text-gray-400 bg-gray-50/50 dark:bg-slate-900/50">
                    <th class="p-4">{{ __('Nội dung hoạt động') }}</th>
                    <th class="p-4 text-center">{{ __('Địa chỉ IP') }}</th>
                    <th class="p-4 text-center">{{ __('Thời gian phát sinh') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-slate-800 text-xs">
                @forelse($logs as $log)
                    <tr class="hover:bg-gray-50 dark:hover:bg-slate-800/75 transition-colors">
                        <td class="p-4 font-semibold text-gray-700 dark:text-slate-200">
                            {{ $log->activity }}
                        </td>
                        <td class="p-4 text-center font-mono text-gray-500 dark:text-slate-400">
                            {{ $log->ip_address ?? '127.0.0.1' }}
                        </td>
                        <td class="p-4 text-center text-gray-400 dark:text-slate-500 text-[10px]">
                            {{ $log->created_at->format('d/m/Y H:i:s') }} ({{ $log->created_at->diffForHumans() }})
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="p-8 text-center text-gray-400 dark:text-slate-500">
                            {{ __('Chưa có nhật ký hoạt động nào được ghi nhận.') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($logs->hasPages())
        <div class="p-4 border-t border-gray-100 dark:border-slate-800 ajax-pagination">
            {{ $logs->links() }}
        </div>
    @endif
</div>

<!-- Danh sách dạng Card trên Mobile -->
<div class="block md:hidden space-y-4">
    @forelse($logs as $log)
        <div class="bg-white dark:bg-slate-900 p-4 rounded-2xl border border-gray-100/80 dark:border-slate-800/80 shadow-sm space-y-2 hover:shadow-md transition-all duration-300">
            <!-- Header Card: IP & Thời gian -->
            <div class="flex justify-between items-center pb-2 border-b border-gray-100/50 dark:border-slate-800/50">
                <span class="inline-flex px-2 py-0.5 rounded-md bg-gray-50 dark:bg-slate-800 border border-gray-200 dark:border-slate-700 font-mono text-[9px] text-gray-500 dark:text-slate-400">
                    IP: {{ $log->ip_address ?? '127.0.0.1' }}
                </span>
                <span class="text-[10px] text-gray-400 dark:text-slate-500 font-medium">{{ $log->created_at->format('d/m/Y H:i') }}</span>
            </div>

            <!-- Body Card: Nội dung hoạt động -->
            <div class="text-xs font-bold text-gray-800 dark:text-slate-200 pt-1">
                {{ $log->activity }}
            </div>
        </div>
    @empty
        <div class="bg-white dark:bg-slate-900 p-8 rounded-2xl border border-gray-100 dark:border-slate-800/80 text-center text-gray-400 dark:text-slate-500">
            {{ __('Chưa có nhật ký hoạt động nào được ghi nhận.') }}
        </div>
    @endforelse

    <!-- Phân trang Mobile -->
    @if($logs->hasPages())
        <div class="p-4 bg-white dark:bg-slate-900 rounded-2xl border border-gray-100 dark:border-slate-800/80 shadow-sm ajax-pagination animate-fade-in">
            {{ $logs->links() }}
        </div>
    @endif
</div>
