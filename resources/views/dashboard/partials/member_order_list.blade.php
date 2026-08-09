@php
    // Tra cứu nhanh ID thành viên F1 để xác định cấp (F1/F2) của mỗi đơn nhằm áp đúng tỷ lệ hoa hồng
    $f1Lookup = array_flip($f1Ids ?? []);

    // Che một phần email thành viên cấp dưới (giữ 2 ký tự đầu + domain) để bảo mật thông tin
    $maskEmail = function ($email) {
        if (!$email || !str_contains($email, '@')) {
            return $email ?: __('Thành viên đã xoá');
        }
        $at = strpos($email, '@');
        return \Illuminate\Support\Str::mask($email, '*', 2, max(1, $at - 2));
    };

    // Hàm nội bộ tính hoa hồng ước tính người xem nhận được từ một đơn của thành viên cấp dưới
    // Công thức: cashback_amount (hoàn tiền gốc F0) × tỷ lệ hoa hồng theo cấp (F1 hoặc F2)
    $estimateCommission = function ($order) use ($f1Lookup, $f1Rate, $f2Rate) {
        $level = isset($f1Lookup[$order->user_id]) ? 1 : 2;
        $rate = $level === 1 ? (float) $f1Rate : (float) $f2Rate;
        return [
            'level'  => $level,
            'amount' => round(($order->cashback_amount ?? 0) * $rate / 100),
        ];
    };
@endphp

<!-- Bảng hiển thị trên máy tính (Desktop) -->
<div class="hidden md:block overflow-x-auto">
    <table class="w-full text-left border-collapse whitespace-nowrap">
        <thead>
            <tr class="border-b border-gray-100 dark:border-slate-800 text-[11px] font-bold text-gray-400 dark:text-slate-500 uppercase tracking-wider">
                <th class="pb-3 text-left">{{ __('Sản phẩm') }}</th>
                <th class="pb-3 text-left">{{ __('Thành viên') }}</th>
                <th class="pb-3 text-right">{{ __('Hoàn tiền') }}</th>
                <th class="pb-3 text-right pr-10">{{ __('Hoa hồng ước tính') }}</th>
                <th class="pb-3 text-left">{{ __('Trạng thái') }}</th>
                <th class="pb-3 text-left">{{ __('Thời gian') }}</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 dark:divide-slate-800 text-xs">
            @forelse($memberOrders as $order)
                <tr class="hover:bg-gray-50/50 dark:hover:bg-slate-800/40 transition-colors">
                    <td class="py-4 text-left">
                        <div class="flex items-center gap-3 max-w-[280px]">
                            @include('dashboard.partials.order_thumb', ['order' => $order])
                            <div class="min-w-0">
                                <p class="font-semibold text-gray-700 dark:text-slate-300 truncate">{{ $order->product_name ?: __('Đơn hàng Shopee') }}</p>
                                @if($order->shop_name)
                                    <span class="text-[10px] text-gray-400 dark:text-slate-500 truncate block">{{ $order->shop_name }}</span>
                                @endif
                            </div>
                        </div>
                    </td>
                    <td class="py-4 font-semibold text-gray-700 dark:text-slate-300 text-left">
                        <span class="block max-w-[200px] truncate lowercase">{{ $order->user ? $maskEmail($order->user->email) : __('Thành viên đã xoá') }}</span>
                    </td>
                    <td class="py-4 font-semibold text-gray-500 dark:text-slate-400 text-right">
                        {{ \App\Helpers\CurrencyHelper::format($order->cashback_amount ?? 0) }}
                    </td>
                    @php($est = $estimateCommission($order))
                    <td class="py-4 text-right pr-10">
                        <div class="flex items-center justify-end gap-2">
                            <span class="font-bold text-shopee text-sm">+{{ \App\Helpers\CurrencyHelper::format($est['amount']) }}</span>
                            <span class="inline-flex px-2 py-0.5 rounded-full text-[9px] font-bold {{ $est['level'] === 1 ? 'bg-orange-50 dark:bg-orange-950/20 text-shopee border border-orange-100/50 dark:border-orange-900/30' : 'bg-blue-50 dark:bg-blue-950/20 text-blue-600 dark:text-blue-400 border border-blue-100/50 dark:border-blue-900/30' }}">
                                {{ __('F:level', ['level' => $est['level']]) }}
                            </span>
                        </div>
                    </td>
                    <td class="py-4 text-left">
                        @include('dashboard.partials.order_status_badge', ['status' => $order->status])
                    </td>
                    <td class="py-4 text-gray-400 dark:text-slate-500 text-[11px] font-mono text-left">
                        {{ $order->created_at->format('d/m/Y H:i') }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="py-12 text-center text-gray-400 dark:text-slate-500">
                        <div class="flex flex-col items-center justify-center space-y-2">
                            <i data-lucide="package-search" class="w-8 h-8 text-gray-300 dark:text-slate-600"></i>
                            <span>{{ __('Chưa có thành viên nào của bạn phát sinh đơn hàng.') }}</span>
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<!-- Danh sách dạng list-item tối ưu hiển thị trên các thiết bị di động (Mobile) — phong cách app -->
<div class="block md:hidden divide-y divide-gray-100 dark:divide-slate-800/70 -mx-2">
    @forelse($memberOrders as $order)
        @php($est = $estimateCommission($order))
        <div class="flex gap-3 px-2 py-3.5">
            @include('dashboard.partials.order_thumb', ['order' => $order, 'size' => 'w-14 h-14', 'iconSize' => 'w-5 h-5', 'rounded' => 'rounded-xl'])
            <div class="min-w-0 flex-1">
                <!-- Tên sản phẩm + hoa hồng ước tính nằm cùng hàng -->
                <div class="flex items-start justify-between gap-2">
                    <p class="text-xs font-bold text-gray-800 dark:text-slate-200 leading-snug line-clamp-2 min-w-0">{{ $order->product_name ?: __('Đơn hàng Shopee') }}</p>
                    <span class="text-sm font-extrabold text-shopee whitespace-nowrap shrink-0">
                        +{{ \App\Helpers\CurrencyHelper::format($est['amount']) }}
                        <span class="text-[9px] font-bold {{ $est['level'] === 1 ? 'text-shopee' : 'text-blue-600 dark:text-blue-400' }}">{{ __('F:level', ['level' => $est['level']]) }}</span>
                    </span>
                </div>

                <!-- Email thành viên -->
                <div class="flex items-center gap-1.5 mt-1 text-[11px] font-semibold text-gray-500 dark:text-slate-400 min-w-0">
                    <i data-lucide="mail" class="w-3.5 h-3.5 text-gray-400 shrink-0"></i>
                    <span class="truncate lowercase">{{ $order->user ? $maskEmail($order->user->email) : __('Thành viên đã xoá') }}</span>
                </div>

                <!-- Hoàn tiền gốc + trạng thái + thời gian -->
                <div class="flex items-center justify-between gap-2 mt-1.5">
                    <div class="flex items-center gap-3 min-w-0">
                        <span class="text-[10px] text-gray-400 dark:text-slate-500 whitespace-nowrap">
                            {{ __('Hoàn tiền') }}: <span class="font-bold text-gray-600 dark:text-slate-300">{{ \App\Helpers\CurrencyHelper::format($order->cashback_amount ?? 0) }}</span>
                        </span>
                        @include('dashboard.partials.order_status_badge', ['status' => $order->status])
                    </div>
                    <span class="text-[10px] text-gray-400 dark:text-slate-500 font-mono whitespace-nowrap shrink-0">{{ $order->created_at->format('d/m/Y') }}</span>
                </div>
            </div>
        </div>
    @empty
        <div class="py-10 text-center text-gray-400 dark:text-slate-500 text-xs">
            {{ __('Chưa có thành viên nào của bạn phát sinh đơn hàng.') }}
        </div>
    @endforelse
</div>

<!-- Phần chuyển trang phân trang -->
@if($memberOrders->hasPages())
    <div class="pt-4 border-t border-gray-100 dark:border-slate-800">
        {{ $memberOrders->links() }}
    </div>
@endif
