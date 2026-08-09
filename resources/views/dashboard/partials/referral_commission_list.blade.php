<!-- Bảng hiển thị trên máy tính (Desktop) -->
<div class="hidden md:block overflow-x-auto">
    <table class="w-full text-left border-collapse whitespace-nowrap">
        <thead>
            <tr class="border-b border-gray-100 dark:border-slate-800 text-[11px] font-bold text-gray-400 dark:text-slate-500 uppercase tracking-wider">
                <th class="pb-3 text-left">{{ __('Thành viên F1/F2') }}</th>
                <th class="pb-3 text-left">{{ __('Cấp hoa hồng') }}</th>
                <th class="pb-3 text-left">{{ __('Hoa hồng gốc') }}</th>
                <th class="pb-3 text-left">{{ __('Tiền thưởng nhận') }}</th>
                <th class="pb-3 text-left">{{ __('Trạng thái') }}</th>
                <th class="pb-3 text-left">{{ __('Thời gian') }}</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 dark:divide-slate-800 text-xs">
            @forelse($commissions as $comm)
                <tr class="hover:bg-gray-50/50 dark:hover:bg-slate-800/40 transition-colors">
                    <td class="py-4 font-semibold text-gray-700 dark:text-slate-300 text-left">
                        {{ $comm->referred ? Str::mask($comm->referred->name, '*', 2, 8) : __('Thành viên đã xoá') }}
                    </td>
                    <td class="py-4 text-left">
                        <span class="inline-flex px-2.5 py-0.5 rounded-full text-[9px] font-bold {{ $comm->level === 1 ? 'bg-orange-50 dark:bg-orange-950/20 text-shopee border border-orange-100/50 dark:border-orange-900/30' : 'bg-blue-50 dark:bg-blue-950/20 text-blue-600 dark:text-blue-400 border border-blue-100/50 dark:border-blue-900/30' }}">
                            {{ __('Tầng :level (F:level)', ['level' => $comm->level]) }}
                        </span>
                    </td>
                    <td class="py-4 font-semibold text-gray-400 dark:text-slate-500 text-left">
                        {{ \App\Helpers\CurrencyHelper::format($comm->cashbackHistory->cashback_amount ?? 0) }}
                    </td>
                    <td class="py-4 font-bold text-green-600 dark:text-green-400 text-sm text-left">
                        +{{ \App\Helpers\CurrencyHelper::format($comm->amount) }}
                    </td>
                    <td class="py-4 text-left">
                        @if($comm->status === 'pending')
                            <span class="inline-flex items-center gap-1.5 text-[11px] font-bold text-yellow-600 dark:text-yellow-400">
                                <span class="w-1.5 h-1.5 rounded-full bg-yellow-500"></span>
                                {{ __('Chờ duyệt') }}
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1.5 text-[11px] font-bold text-green-600 dark:text-green-400">
                                <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span>
                                {{ __('Đã cộng ví') }}
                            </span>
                        @endif
                    </td>
                    <td class="py-4 text-gray-400 dark:text-slate-500 text-[11px] font-mono text-left">
                        {{ $comm->created_at->format('d/m/Y H:i') }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="py-12 text-center text-gray-400 dark:text-slate-500">
                        <div class="flex flex-col items-center justify-center space-y-2">
                            <i data-lucide="inbox" class="w-8 h-8 text-gray-300 dark:text-slate-600"></i>
                            <span>{{ __('Bạn chưa nhận được khoản hoa hồng giới thiệu nào. Hãy chia sẻ link giới thiệu!') }}</span>
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<!-- Danh sách dạng list-item tối ưu hiển thị trên các thiết bị di động (Mobile) — phong cách app -->
<div class="block md:hidden divide-y divide-gray-100 dark:divide-slate-800/70 -mx-2">
    @forelse($commissions as $comm)
        @php
            $commName = $comm->referred ? Str::mask($comm->referred->name, '*', 2, 8) : __('Thành viên đã xoá');
            $commInitial = $comm->referred ? mb_strtoupper(mb_substr($comm->referred->name, 0, 1)) : '?';
        @endphp
        <div class="flex items-center gap-3 px-2 py-3.5">
            <!-- Avatar chữ cái đầu, đổi màu theo cấp F1/F2 -->
            <div class="w-11 h-11 rounded-full flex items-center justify-center shrink-0 text-sm font-black {{ $comm->level === 1 ? 'bg-orange-50 dark:bg-orange-950/30 text-shopee' : 'bg-blue-50 dark:bg-blue-950/30 text-blue-600 dark:text-blue-400' }}">
                {{ $commInitial }}
            </div>

            <!-- Thông tin chính: tên + cấp + thời gian -->
            <div class="min-w-0 flex-1">
                <p class="text-sm font-bold text-gray-800 dark:text-slate-200 truncate">{{ $commName }}</p>
                <div class="flex items-center gap-1.5 mt-1">
                    <span class="inline-flex px-1.5 py-0.5 rounded-md text-[9px] font-bold {{ $comm->level === 1 ? 'bg-orange-50 dark:bg-orange-950/20 text-shopee' : 'bg-blue-50 dark:bg-blue-950/20 text-blue-600 dark:text-blue-400' }}">
                        {{ __('F:level', ['level' => $comm->level]) }}
                    </span>
                    <span class="text-[10px] text-gray-400 dark:text-slate-500 font-mono truncate">{{ $comm->created_at->format('d/m/Y H:i') }}</span>
                </div>
                <p class="text-[10px] text-gray-400 dark:text-slate-500 font-medium mt-0.5 truncate">
                    {{ __('Hoa hồng gốc F0:') }} <span class="font-mono text-gray-500 dark:text-slate-400">{{ \App\Helpers\CurrencyHelper::format($comm->cashbackHistory->cashback_amount ?? 0) }}</span>
                </p>
            </div>

            <!-- Số tiền nhận + trạng thái -->
            <div class="text-right shrink-0">
                <span class="block text-sm font-extrabold text-green-600 dark:text-green-400 whitespace-nowrap">
                    +{{ \App\Helpers\CurrencyHelper::format($comm->amount) }}
                </span>
                @if($comm->status === 'pending')
                    <span class="inline-flex items-center gap-1 text-[10px] font-bold text-yellow-600 mt-1">
                        <span class="w-1.5 h-1.5 rounded-full bg-yellow-500"></span>
                        {{ __('Chờ duyệt') }}
                    </span>
                @else
                    <span class="inline-flex items-center gap-1 text-[10px] font-bold text-green-600 mt-1">
                        <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span>
                        {{ __('Đã cộng ví') }}
                    </span>
                @endif
            </div>
        </div>
    @empty
        <div class="py-10 text-center text-gray-400 dark:text-slate-500 text-xs">
            {{ __('Bạn chưa nhận được khoản hoa hồng giới thiệu nào. Hãy chia sẻ link giới thiệu!') }}
        </div>
    @endforelse
</div>

<!-- Phần chuyển trang phân trang -->
@if($commissions->hasPages())
    <div class="pt-4 border-t border-gray-100 dark:border-slate-800">
        {{ $commissions->links() }}
    </div>
@endif
