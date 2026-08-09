@if(!isset($type) || $type === 'desktop')
<div id="ajax-desktop-content">
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse whitespace-nowrap">
            <thead>
                <tr class="border-b border-gray-100 dark:border-slate-800 text-xs font-bold text-gray-400 dark:text-slate-500 bg-gray-50/50 dark:bg-slate-900/50">
                    <th class="p-4">{{ __('Số tiền rút') }}</th>
                    <th class="p-4">{{ __('Phương thức') }}</th>
                    <th class="p-4">{{ __('Thông tin tài khoản') }}</th>
                    <th class="p-4 text-center">{{ __('Trạng thái') }}</th>
                    <th class="p-4 text-center">{{ __('Ngày tạo') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-slate-800 text-xs">
                @forelse($withdrawals as $w)
                    <tr class="hover:bg-gray-50 dark:hover:bg-slate-800/75 transition-colors">
                        <td class="p-4">
                            <p class="font-extrabold text-gray-900 dark:text-white text-sm">{{ \App\Helpers\CurrencyHelper::format($w->amount) }}</p>
                            <p class="text-[10px] text-gray-400 dark:text-slate-500 font-medium mt-0.5">
                                {{ __('Phí:') }} {{ \App\Helpers\CurrencyHelper::format($w->fee ?? 0) }} | {{ __('Thực nhận:') }} <span class="text-shopee font-bold">{{ \App\Helpers\CurrencyHelper::format($w->real_amount ?? $w->amount) }}</span>
                            </p>
                        </td>
                        <td class="p-4 font-bold text-gray-700 dark:text-slate-300">
                            {{ $w->payment_method === 'bank' ? 'Chuyển khoản' : ($w->payment_method === 'wallet' ? 'Ví ' . $w->bank_name : 'Ví MoMo') }}
                        </td>
                        <td class="p-4 text-left">
                            <p class="font-bold text-gray-800 dark:text-slate-200 uppercase">{{ $w->account_name }}</p>
                            <p class="text-[10px] text-gray-400 dark:text-slate-550">STK: <span class="font-mono">{{ $w->account_number }}</span> @if($w->bank_name) ({{ $w->bank_name }}) @endif</p>
                        </td>
                        <td class="p-4 text-center">
                            @if($w->status === 'pending')
                                <span class="inline-flex px-2.5 py-0.5 text-[9px] font-bold rounded-full bg-yellow-50 dark:bg-yellow-950/20 text-yellow-600 dark:text-yellow-400 border border-yellow-100 dark:border-yellow-900/30">{{ __('Chờ duyệt') }}</span>
                            @elseif($w->status === 'approved')
                                <span class="inline-flex px-2.5 py-0.5 text-[9px] font-bold rounded-full bg-green-50 dark:bg-green-950/20 text-green-600 dark:text-green-400 border border-green-100 dark:border-green-900/30" title="{{ __('Duyệt ngày:') }} {{ $w->processed_at }}">{{ __('Thành công') }}</span>
                            @else
                                <div class="flex flex-col items-center gap-0.5">
                                    <span class="inline-flex px-2.5 py-0.5 text-[9px] font-bold rounded-full bg-red-50 dark:bg-red-950/20 text-red-600 dark:text-red-400 border border-red-100 dark:border-red-900/30">{{ __('Từ chối') }}</span>
                                    @if($w->notes)
                                        <span class="text-[8px] text-red-400 dark:text-red-500 max-w-[150px] truncate" title="{{ $w->notes }}">Lý do: {{ $w->notes }}</span>
                                    @endif
                                </div>
                            @endif
                        </td>
                        <td class="p-4 text-center text-gray-400 dark:text-slate-500 text-[10px] font-mono">
                            {{ $w->created_at->format('d/m/Y H:i') }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="p-8 text-center text-gray-400 dark:text-slate-500">
                            {{ __('Bạn chưa thực hiện lệnh rút tiền nào.') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($withdrawals->hasPages())
        <div class="p-4 border-t border-gray-100 dark:border-slate-800">
            {{ $withdrawals->links() }}
        </div>
    @endif
</div>
@endif

@if(!isset($type) || $type === 'mobile')
<div id="ajax-mobile-content">
    <div class="space-y-3">
        @forelse($withdrawals as $w)
            <div class="bg-white dark:bg-slate-900 p-4 rounded-xl border border-gray-150 dark:border-slate-800/80 shadow-sm hover:shadow-md transition-all duration-300">
                <div class="flex items-start gap-3">
                    <!-- Icon dạng tròn của app -->
                    @php
                        $isBank = $w->payment_method === 'bank';
                        $iconColorClass = $isBank ? 'bg-blue-50 dark:bg-blue-950/20 text-blue-600' : 'bg-purple-50 dark:bg-purple-950/20 text-purple-600';
                        $iconName = $isBank ? 'landmark' : 'wallet';
                    @endphp
                    <div class="w-10 h-10 rounded-full flex items-center justify-center shrink-0 {{ $iconColorClass }}">
                        <i data-lucide="{{ $iconName }}" class="w-5 h-5"></i>
                    </div>

                    <!-- Nội dung chính -->
                    <div class="flex-1 min-w-0">
                        <div class="flex justify-between items-start">
                            <div>
                                <h4 class="text-xs font-bold text-gray-950 dark:text-white truncate">
                                    {{ $w->payment_method === 'bank' ? $w->bank_name : ($w->payment_method === 'wallet' ? $w->bank_name : __('Ví MoMo')) }}
                                </h4>
                                <p class="text-[10px] text-gray-500 dark:text-slate-400 font-medium mt-0.5">
                                    {{ __('STK:') }} <span class="font-mono font-bold">{{ $w->account_number }}</span>
                                </p>
                                <p class="text-[10px] text-gray-400 dark:text-slate-500 uppercase mt-0.5 truncate">
                                    {{ $w->account_name }}
                                </p>
                            </div>
                            
                            <!-- Số tiền -->
                            <div class="text-right">
                                <p class="text-xs font-extrabold text-gray-900 dark:text-white">
                                    -{{ \App\Helpers\CurrencyHelper::format($w->amount) }}
                                </p>
                                <p class="text-[9px] text-gray-400 dark:text-slate-500 mt-0.5">
                                    {{ __('Thực nhận:') }} <span class="text-shopee font-bold">{{ \App\Helpers\CurrencyHelper::format($w->real_amount ?? $w->amount) }}</span>
                                </p>
                            </div>
                        </div>

                        <!-- Divider -->
                        <div class="border-t border-gray-100 dark:border-slate-800/60 my-2.5"></div>

                        <!-- Trạng thái và thời gian -->
                        <div class="flex items-center justify-between">
                            <span class="text-[9px] text-gray-400 dark:text-slate-500 font-semibold flex items-center gap-1">
                                <i data-lucide="calendar" class="w-3 h-3"></i>
                                {{ $w->created_at->format('d/m/Y H:i') }}
                            </span>
                            
                            <div>
                                @if($w->status === 'pending')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 text-[9px] font-bold rounded-full bg-amber-50 dark:bg-amber-950/20 text-amber-600 dark:text-amber-400 border border-amber-100 dark:border-amber-900/30">
                                        <span class="w-1 h-1 rounded-full bg-amber-500 animate-pulse"></span>
                                        {{ __('Chờ duyệt') }}
                                    </span>
                                @elseif($w->status === 'approved')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 text-[9px] font-bold rounded-full bg-emerald-50 dark:bg-emerald-950/20 text-emerald-600 dark:text-emerald-400 border border-emerald-100 dark:border-emerald-900/30">
                                        <i data-lucide="check-circle" class="w-2.5 h-2.5"></i>
                                        {{ __('Thành công') }}
                                    </span>
                                @else
                                    <div class="flex flex-col items-end gap-1">
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 text-[9px] font-bold rounded-full bg-rose-50 dark:bg-rose-950/20 text-rose-600 dark:text-rose-450 border border-rose-100 dark:border-rose-900/30">
                                            <i data-lucide="x-circle" class="w-2.5 h-2.5"></i>
                                            {{ __('Từ chối') }}
                                        </span>
                                        @if($w->notes)
                                            <span class="text-[8px] text-rose-500 dark:text-rose-450 max-w-[150px] truncate" title="{{ $w->notes }}">
                                                {{ __('Lý do:') }} {{ $w->notes }}
                                            </span>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        @empty
            <div class="bg-white dark:bg-slate-900 p-8 rounded-xl border border-gray-150 dark:border-slate-800 text-center text-gray-400 dark:text-slate-500">
                {{ __('Bạn chưa thực hiện lệnh rút tiền nào.') }}
            </div>
        @endforelse

        <!-- Phân trang Mobile -->
        @if($withdrawals->hasPages())
            <div class="p-4 bg-white dark:bg-slate-900 rounded-xl border border-gray-150 dark:border-slate-800 shadow-sm mt-4">
                {{ $withdrawals->links() }}
            </div>
        @endif
    </div>
</div>
@endif
