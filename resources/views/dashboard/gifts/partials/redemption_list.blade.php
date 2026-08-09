<!-- 
    Tệp tin hiển thị danh sách lịch sử đổi quà (Redemption List Partial View)
    Được thiết kế tối ưu hóa hiển thị responsive:
    - Bảng thông tin (table) chi tiết trên màn hình desktop (md trở lên)
    - Danh sách thẻ giao dịch (cards) trực quan, gọn gàng trên thiết bị di động (dưới md)
-->
<div class="w-full">
    <!-- Giao diện TABLE dành cho màn hình DESKTOP (từ md trở lên) -->
    <div class="hidden md:block overflow-x-auto">
        <table class="w-full text-left border-collapse whitespace-nowrap">
            <thead>
                <tr class="border-b border-gray-100 dark:border-slate-800 text-[10px] sm:text-xs font-bold text-gray-400 bg-gray-50/50 dark:bg-slate-900/50 uppercase tracking-wider">
                    <th class="p-4 flex items-center gap-1"><i data-lucide="hash" class="w-3.5 h-3.5"></i> {{ __('Mã đơn') }}</th>
                    <th class="p-4"><i data-lucide="package" class="w-3.5 h-3.5 inline mr-1"></i>{{ __('Quà tặng') }}</th>
                    <th class="p-4"><i data-lucide="wallet" class="w-3.5 h-3.5 inline mr-1"></i>{{ __('Phí đổi') }}</th>
                    <th class="p-4 text-center"><i data-lucide="activity" class="w-3.5 h-3.5 inline mr-1"></i>{{ __('Trạng thái') }}</th>
                    <th class="p-4 text-center"><i data-lucide="calendar" class="w-3.5 h-3.5 inline mr-1"></i>{{ __('Ngày đổi') }}</th>
                    <th class="p-4 text-center"><i data-lucide="star" class="w-3.5 h-3.5 inline mr-1"></i>{{ __('Phản hồi') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-slate-800/40 text-[11px] sm:text-xs text-gray-600 dark:text-gray-300">
                @forelse($redemptions as $item)
                    <tr class="hover:bg-slate-50/40 dark:hover:bg-slate-800/10 transition-colors">
                        <td class="p-4 font-mono font-bold text-gray-400 dark:text-slate-500">
                            {{ $item->code ?? '#' . $item->id }}
                        </td>
                        <td class="p-4 font-bold text-gray-800 dark:text-white">
                            {{ $item->gift->title }}
                        </td>
                        <td class="p-4 font-mono font-extrabold text-shopee dark:text-shopee-light">
                            -{{ number_format($item->amount) }}đ
                        </td>
                        <td class="p-4 text-center">
                            @if($item->status === 'pending')
                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[9px] font-bold bg-yellow-50 text-yellow-600 border border-yellow-100 dark:bg-yellow-950/20 dark:text-yellow-400 dark:border-yellow-900/30">
                                    <span class="w-1 h-1 rounded-full bg-yellow-500 animate-pulse"></span>
                                    {{ __('Chờ xử lý') }}
                                </span>
                            @elseif($item->status === 'approved')
                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[9px] font-bold bg-green-50 text-green-600 border border-green-100 dark:bg-green-950/20 dark:text-green-400 dark:border-green-900/30">
                                    <span class="w-1 h-1 rounded-full bg-green-500"></span>
                                    {{ __('Thành công') }}
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[9px] font-bold bg-red-50 text-red-600 border border-red-100 dark:bg-red-950/20 dark:text-red-400 dark:border-red-900/30">
                                    <span class="w-1 h-1 rounded-full bg-red-500"></span>
                                    {{ __('Bị từ chối') }}
                                </span>
                            @endif
                        </td>
                        <td class="p-4 text-center text-gray-400 dark:text-slate-500 font-mono text-[10px]">
                            {{ $item->created_at->format('d/m/Y H:i') }}
                        </td>
                        <td class="p-4 text-center">
                            @if($item->status === 'approved')
                                <button onclick="window._giftHandler && window._giftHandler.openDetailModal({{ json_encode($item) }}, '{{ $item->created_at->format('d/m/Y H:i') }}')"
                                        class="inline-flex items-center gap-1 px-3 py-1 text-[10px] font-bold bg-green-50 hover:bg-green-100/80 text-green-700 dark:bg-green-950/20 dark:text-green-400 border border-green-200/50 rounded-xl transition-all shadow-sm active:scale-95">
                                    <i data-lucide="gift" class="w-3 h-3"></i>
                                    {{ __('Nhận Quà') }}
                                </button>
                            @elseif($item->status === 'rejected')
                                <button onclick="window._giftHandler && window._giftHandler.openDetailModal({{ json_encode($item) }}, '{{ $item->created_at->format('d/m/Y H:i') }}')"
                                        class="inline-flex items-center gap-1 px-3 py-1 text-[10px] font-bold bg-red-50 hover:bg-red-100/80 text-red-700 dark:bg-red-950/20 dark:text-red-400 border border-red-200/50 rounded-xl transition-all shadow-sm active:scale-95">
                                    <i data-lucide="alert-circle" class="w-3 h-3"></i>
                                    {{ __('Xem Lý Do') }}
                                </button>
                            @else
                                <span class="text-gray-450 dark:text-slate-600 font-mono text-[10px]">-</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="p-10 text-center text-gray-400 dark:text-slate-500">
                            <div class="w-10 h-10 rounded-2xl bg-gray-50 dark:bg-slate-800/60 text-gray-300 dark:text-slate-650 flex items-center justify-center mx-auto mb-3">
                                <i data-lucide="inbox" class="w-5 h-5"></i>
                            </div>
                            <p class="text-xs">{{ __('Không tìm thấy lịch sử quy đổi nào.') }}</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Giao diện CARDS dành cho màn hình MOBILE (dưới md) -->
    <div class="md:hidden p-4 space-y-4">
        @forelse($redemptions as $item)
            <div class="bg-slate-50/50 dark:bg-slate-900/40 border border-gray-150/40 dark:border-slate-800/80 rounded-2xl p-4 space-y-3 shadow-sm">
                <!-- Đầu thẻ: Mã đơn & Trạng thái -->
                <div class="flex items-center justify-between gap-2 border-b border-gray-100 dark:border-slate-800/60 pb-2">
                    <span class="font-mono font-bold text-gray-400 dark:text-slate-500 text-xs">
                        {{ $item->code ?? '#' . $item->id }}
                    </span>
                    
                    @if($item->status === 'pending')
                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[9px] font-bold bg-yellow-50 text-yellow-600 border border-yellow-100 dark:bg-yellow-950/20 dark:text-yellow-400 dark:border-yellow-900/30">
                            <span class="w-1 h-1 rounded-full bg-yellow-500 animate-pulse"></span>
                            {{ __('Chờ xử lý') }}
                        </span>
                    @elseif($item->status === 'approved')
                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[9px] font-bold bg-green-50 text-green-600 border border-green-100 dark:bg-green-950/20 dark:text-green-400 dark:border-green-900/30">
                            <span class="w-1 h-1 rounded-full bg-green-500"></span>
                            {{ __('Thành công') }}
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[9px] font-bold bg-red-50 text-red-600 border border-red-100 dark:bg-red-950/20 dark:text-red-400 dark:border-red-900/30">
                            <span class="w-1 h-1 rounded-full bg-red-500"></span>
                            {{ __('Bị từ chối') }}
                        </span>
                    @endif
                </div>

                <!-- Thân thẻ: Thông tin quà & phí -->
                <div class="space-y-1">
                    <h4 class="font-bold text-gray-800 dark:text-white text-xs sm:text-sm">
                        {{ $item->gift->title }}
                    </h4>
                    <div class="flex items-center justify-between text-[10px] text-gray-400 dark:text-slate-500 font-medium">
                        <span>{{ __('Phí đổi:') }} <strong class="text-shopee dark:text-shopee-light font-mono text-xs">{{ number_format($item->amount) }}đ</strong></span>
                        <span class="font-mono">{{ $item->created_at->format('d/m/Y H:i') }}</span>
                    </div>
                </div>

                <!-- Chân thẻ: Nút hành động -->
                @if($item->status === 'approved' || $item->status === 'rejected')
                    <div class="pt-2 border-t border-gray-100 dark:border-slate-800/60">
                        @if($item->status === 'approved')
                            <button onclick="window._giftHandler && window._giftHandler.openDetailModal({{ json_encode($item) }}, '{{ $item->created_at->format('d/m/Y H:i') }}')"
                                    class="w-full py-2 flex items-center justify-center gap-1 text-[10px] font-bold bg-green-50 hover:bg-green-100/80 text-green-700 dark:bg-green-950/20 dark:text-green-400 border border-green-200/50 rounded-xl transition-all shadow-sm">
                                <i data-lucide="gift" class="w-3.5 h-3.5"></i>
                                {{ __('Nhận Quà & Phản hồi') }}
                            </button>
                        @elseif($item->status === 'rejected')
                            <button onclick="window._giftHandler && window._giftHandler.openDetailModal({{ json_encode($item) }}, '{{ $item->created_at->format('d/m/Y H:i') }}')"
                                    class="w-full py-2 flex items-center justify-center gap-1 text-[10px] font-bold bg-red-50 hover:bg-red-100/80 text-red-700 dark:bg-red-950/20 dark:text-red-400 border border-red-200/50 rounded-xl transition-all shadow-sm">
                                <i data-lucide="alert-circle" class="w-3.5 h-3.5"></i>
                                {{ __('Xem Lý Do Từ Chối') }}
                            </button>
                        @endif
                    </div>
                @endif
            </div>
        @empty
            <div class="bg-slate-50/50 dark:bg-slate-900/40 border border-gray-150/40 dark:border-slate-800/80 rounded-2xl p-8 text-center text-gray-400 dark:text-slate-550">
                <div class="w-10 h-10 rounded-2xl bg-gray-50 dark:bg-slate-800/60 text-gray-300 dark:text-slate-650 flex items-center justify-center mx-auto mb-3">
                    <i data-lucide="inbox" class="w-5 h-5"></i>
                </div>
                <p class="text-xs">{{ __('Không tìm thấy lịch sử quy đổi nào.') }}</p>
            </div>
        @endforelse
    </div>
</div>

<!-- Phân trang lịch sử đổi quà -->
@if($redemptions->hasPages())
    <div class="p-4 border-t border-gray-100 dark:border-slate-800 bg-gray-50/50 dark:bg-slate-900/50">
        {{ $redemptions->links() }}
    </div>
@endif
