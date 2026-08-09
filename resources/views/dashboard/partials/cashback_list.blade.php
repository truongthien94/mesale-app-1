@php
    $shopeeShow = \App\Models\Setting::getVal('shopee_show_current_price', '1');
    $tiktokShow = \App\Models\Setting::getVal('tiktok_show_current_price', '1');
    $globalShowPrice = ($shopeeShow === '1' || $tiktokShow === '1');
@endphp
<!-- Bảng Dữ Liệu Desktop -->
<div class="hidden md:block bg-white dark:bg-slate-900 rounded-2xl shadow-md border border-gray-100 dark:border-slate-800/80 overflow-hidden divide-y divide-gray-100 dark:divide-slate-800/60">
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse whitespace-nowrap">
            <thead>
                <tr class="border-b border-gray-100 dark:border-slate-800 text-xs font-bold text-gray-400 bg-gray-50/50 dark:bg-slate-800/30">
                    <th class="p-4">{{ __('Thông tin sản phẩm') }}</th>
                    <th class="p-4 text-right">{{ $globalShowPrice ? __('Giá trị / Tiền hoàn') : __('Tiền hoàn') }}</th>
                    <th class="p-4 text-center">{{ __('Trạng thái & Ngày tạo') }}</th>
                    <th class="p-4 text-center">{{ __('Hành động') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-slate-800/60 text-xs">
                @forelse($histories as $item)
                    @php
                        // Cờ nhận biết bản ghi là link vừa tạo nhưng sàn chưa ghi nhận đơn (lấy từ bảng cashback_clicks)
                        $isPending = (bool) ($item->is_pending_click ?? false);
                        $platformKey = $item->platform ?? 'shopee';

                        $showPrice = '1';
                        if ($platformKey === 'tiktok') {
                            $showPrice = \App\Models\Setting::getVal('tiktok_show_current_price', '1');
                        } else {
                            $showPrice = \App\Models\Setting::getVal('shopee_show_current_price', '1');
                        }

                        // Dữ liệu truyền sang Modal chi tiết. Với link chưa ghi nhận, các trường chỉ tồn tại
                        // ở đơn hàng thật (mã đơn, ngày duyệt, lý do từ chối...) sẽ để trống.
                        $modalItem = [
                            'order_id' => $isPending ? '' : $item->order_id,
                            'product_name' => $item->product_name,
                            'product_image' => $item->product_image,
                            'original_price' => \App\Helpers\CurrencyHelper::format($item->original_price),
                            'cashback_amount' => \App\Helpers\CurrencyHelper::format($item->cashback_amount),
                            'cashback_rate' => $item->cashback_rate,
                            'commission_amount' => \App\Helpers\CurrencyHelper::format($item->commission_amount),
                            'affiliate_url' => $item->affiliate_url,
                            'status' => $isPending ? 'unrecorded' : $item->status,
                            'rejected_reason' => $isPending ? '' : $item->rejected_reason,
                            'created_at' => $item->created_at->format('d/m/Y H:i'),
                            'approved_at' => (!$isPending && $item->approved_at) ? $item->approved_at->format('d/m/Y H:i') : '',
                            'clicks' => (string) ($item->getShortLink()?->clicks ?? 0),
                            'trans_id' => $item->trans_id,
                            'shop_name' => $isPending ? '' : $item->shop_name,
                            'platform' => $platformKey,
                            'show_price' => $showPrice,
                            'status_timeline' => $isPending ? [] : ($item->status_timeline ?? []),
                            'updated_at' => $item->updated_at ? $item->updated_at->toIso8601String() : '',
                        ];
                    @endphp
                    <tr class="hover:bg-gray-50 dark:hover:bg-slate-800/50 transition-colors">
                        <!-- Cột 1: Thông tin sản phẩm & Mã đơn hàng -->
                        <td class="p-4">
                            <div class="flex items-center gap-3.5 max-w-sm md:max-w-md">
                                <div class="relative w-12 h-12 shrink-0 rounded-xl overflow-hidden bg-gray-50 dark:bg-slate-800/40 border border-gray-100 dark:border-slate-800 shadow-sm flex items-center justify-center">
                                    <div class="absolute inset-0 bg-gray-50 dark:bg-slate-800/40 flex items-center justify-center text-gray-400">
                                        <i data-lucide="image" class="w-4 h-4"></i>
                                    </div>
                                    <img src="{{ $item->product_image }}" alt="{{ __('Ảnh sản phẩm') }}" class="absolute inset-0 w-full h-full object-cover z-10" onerror="this.style.display='none';">
                                </div>
                                <div class="min-w-0 flex-1 space-y-1">
                                    <div class="flex items-center gap-1.5 flex-wrap">
                                        @if($platformKey === 'shopee')
                                            <span class="inline-flex px-1.5 py-0.5 rounded text-[8px] font-extrabold text-white shadow-sm" style="background-color: #ee4d2d;">{{ \App\Models\Setting::getVal('shopee_platform_name', 'Shopee') }}</span>
                                        @elseif($platformKey === 'tiktok')
                                            <span class="inline-flex px-1.5 py-0.5 rounded text-[8px] font-extrabold bg-black text-white shadow-sm">{{ \App\Models\Setting::getVal('tiktok_platform_name', 'TikTok Shop') }}</span>
                                        @elseif($platformKey === 'lazada')
                                            <span class="inline-flex px-1.5 py-0.5 rounded text-[8px] font-extrabold bg-blue-800 text-white shadow-sm">{{ __('Lazada') }}</span>
                                        @else
                                            <span class="inline-flex px-1.5 py-0.5 rounded text-[8px] font-extrabold bg-gray-600 text-white shadow-sm">{{ strtoupper($platformKey) }}</span>
                                        @endif
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-gray-100 dark:bg-slate-850 text-gray-600 dark:text-slate-400 font-bold text-[9px] uppercase tracking-wider" title="{{ $isPending ? __('Mã đối soát của link đã tạo') : __('Mã đơn hàng') }}">
                                            <i data-lucide="hash" class="w-2.5 h-2.5"></i>
                                            {{ $isPending ? $item->trans_id : $item->order_id }}
                                        </span>
                                    </div>
                                    <p class="font-bold text-gray-900 dark:text-slate-100 truncate text-xs" title="{{ $item->product_name }}">
                                        {{ $item->product_name }}
                                    </p>
                                    <a href="{{ $item->affiliate_url }}" target="_blank" class="inline-flex items-center gap-1 text-[10px] text-shopee hover:text-shopee-dark font-semibold">
                                        <span>{{ __('Link mua hàng') }}</span>
                                        <i data-lucide="external-link" class="w-2.5 h-2.5"></i>
                                    </a>
                                </div>
                            </div>
                        </td>

                        <!-- Cột 2: Tiền hoàn / Giá gốc -->
                        <td class="p-4 text-right">
                            <div class="space-y-0.5">
                                @if($isPending)
                                    {{-- Link chưa được sàn ghi nhận: số tiền chỉ mang tính ước tính nên hiển thị nhạt hơn --}}
                                    <p class="font-extrabold text-emerald-600/70 dark:text-emerald-400/70 text-sm">
                                        ~{{ \App\Helpers\CurrencyHelper::format($item->cashback_amount) }}
                                    </p>
                                    <p class="text-[9px] text-gray-400 font-medium">{{ __('Ước tính') }}</p>
                                @else
                                    <p class="font-extrabold text-green-600 text-sm">
                                        +{{ \App\Helpers\CurrencyHelper::format($item->cashback_amount) }}
                                    </p>
                                @endif
                                @if($showPrice !== '0')
                                    <div class="flex flex-col text-[10px] text-gray-400 font-medium">
                                        <span>{{ __('Gốc:') }} {{ \App\Helpers\CurrencyHelper::format($item->original_price) }}</span>
                                    </div>
                                @endif
                            </div>
                        </td>

                        <!-- Cột 3: Trạng thái & Ngày tạo -->
                        <td class="p-4 text-center">
                            <div class="flex flex-col items-center gap-1.5">
                                @if($isPending)
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 text-[10px] font-bold rounded-full bg-blue-50 dark:bg-blue-950/20 text-blue-600 dark:text-blue-400 border border-blue-100 dark:border-blue-900/30" title="{{ __('Sàn thường cần vài giờ tới vài ngày để ghi nhận đơn hàng về hệ thống.') }}">
                                        <span class="w-1 h-1 rounded-full bg-blue-500 animate-pulse"></span>
                                        {{ __('Chờ sàn ghi nhận') }}
                                    </span>
                                @elseif($item->status === 'pending')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 text-[10px] font-bold rounded-full bg-yellow-50 dark:bg-yellow-950/20 text-yellow-600 dark:text-yellow-500 border border-yellow-100 dark:border-yellow-900/30">
                                        <span class="w-1 h-1 rounded-full bg-yellow-500 animate-pulse"></span>
                                        {{ __('Chờ duyệt') }}
                                    </span>
                                @elseif($item->status === 'approved')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 text-[10px] font-bold rounded-full bg-green-50 dark:bg-green-950/20 text-green-600 dark:text-green-500 border border-green-100 dark:border-green-900/30" title="{{ __('Đã duyệt lúc:') }} {{ $item->approved_at }}">
                                        <span class="w-1 h-1 rounded-full bg-green-500"></span>
                                        {{ __('Đã cộng ví') }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 text-[10px] font-bold rounded-full bg-red-50 dark:bg-red-950/20 text-red-600 dark:text-red-500 border border-red-100 dark:border-red-900/30">
                                        <span class="w-1 h-1 rounded-full bg-red-500"></span>
                                        {{ __('Bị từ chối') }}
                                    </span>
                                @endif
                                <span class="text-[10px] text-gray-400 font-medium">{{ $item->created_at->format('d/m/Y H:i') }}</span>
                            </div>
                        </td>

                        <!-- Cột 4: Hành động -->
                        <td class="p-4 text-center">
                            <button @click="selectedItem = {{ \Illuminate\Support\Js::from($modalItem) }}; openModal = true" class="inline-flex items-center gap-1 px-3 py-1.5 text-[11px] font-bold text-shopee bg-shopee/10 hover:bg-shopee/20 rounded-xl transition-all shadow-sm hover:scale-[1.02] active:scale-[0.98]">
                                <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                                <span>{{ __('Xem') }}</span>
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="p-8 text-center text-gray-400">
                            <i data-lucide="search-code" class="w-8 h-8 mx-auto mb-2 text-gray-300"></i>
                            {{ __('Không tìm thấy lịch sử hoàn tiền phù hợp.') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Phân trang Desktop -->
    @if($histories->hasPages())
        <div class="p-4 border-t border-gray-100 dark:border-slate-800 ajax-pagination">
            {{ $histories->links() }}
        </div>
    @endif
</div>

<!-- Danh sách dạng Card trên Mobile - App Style -->
<div class="block md:hidden space-y-2.5">
    @forelse($histories as $item)
        @php
            // Cờ nhận biết bản ghi là link vừa tạo nhưng sàn chưa ghi nhận đơn (lấy từ bảng cashback_clicks)
            $isPending = (bool) ($item->is_pending_click ?? false);
            $platformKey = $item->platform ?? 'shopee';

            $showPrice = '1';
            if ($platformKey === 'tiktok') {
                $showPrice = \App\Models\Setting::getVal('tiktok_show_current_price', '1');
            } else {
                $showPrice = \App\Models\Setting::getVal('shopee_show_current_price', '1');
            }

            // Dữ liệu truyền sang Modal chi tiết (đồng bộ với bản Desktop phía trên)
            $modalItem = [
                'order_id' => $isPending ? '' : $item->order_id,
                'product_name' => $item->product_name,
                'product_image' => $item->product_image,
                'original_price' => \App\Helpers\CurrencyHelper::format($item->original_price),
                'cashback_amount' => \App\Helpers\CurrencyHelper::format($item->cashback_amount),
                'cashback_rate' => $item->cashback_rate,
                'commission_amount' => \App\Helpers\CurrencyHelper::format($item->commission_amount),
                'affiliate_url' => $item->affiliate_url,
                'status' => $isPending ? 'unrecorded' : $item->status,
                'rejected_reason' => $isPending ? '' : $item->rejected_reason,
                'created_at' => $item->created_at->format('d/m/Y H:i'),
                'approved_at' => (!$isPending && $item->approved_at) ? $item->approved_at->format('d/m/Y H:i') : '',
                'clicks' => (string) ($item->getShortLink()?->clicks ?? 0),
                'trans_id' => $item->trans_id,
                'shop_name' => $isPending ? '' : $item->shop_name,
                'platform' => $platformKey,
                'show_price' => $showPrice,
                'status_timeline' => $isPending ? [] : ($item->status_timeline ?? []),
                'updated_at' => $item->updated_at ? $item->updated_at->toIso8601String() : '',
            ];
        @endphp
        <div
            class="relative bg-white dark:bg-slate-900 rounded-2xl border border-gray-100 dark:border-slate-800/60 shadow-sm overflow-hidden cursor-pointer active:scale-[0.99] active:opacity-95 transition-all duration-150 select-none"
            @click="selectedItem = {{ \Illuminate\Support\Js::from($modalItem) }}; openModal = true"
        >
            <!-- Dải màu trạng thái bên trái -->
            @if($isPending)
                <div class="absolute left-0 top-0 bottom-0 w-[3px] bg-blue-400 dark:bg-blue-500"></div>
            @elseif($item->status === 'pending')
                <div class="absolute left-0 top-0 bottom-0 w-[3px] bg-yellow-400 dark:bg-yellow-500"></div>
            @elseif($item->status === 'approved')
                <div class="absolute left-0 top-0 bottom-0 w-[3px] bg-green-500 dark:bg-green-400"></div>
            @else
                <div class="absolute left-0 top-0 bottom-0 w-[3px] bg-red-400 dark:bg-red-500"></div>
            @endif

            <!-- Hàng 1: Nền tảng + Mã đơn + Ngày giờ -->
            <div class="flex items-center justify-between pl-4 pr-3.5 pt-3 pb-0 gap-2">
                <div class="flex items-center gap-1.5 min-w-0">
                    @if($platformKey === 'shopee')
                        <span class="inline-flex items-center px-1.5 py-0.5 rounded-[4px] text-[7px] font-extrabold text-white bg-shopee shadow-sm shrink-0 leading-none">{{ \App\Models\Setting::getVal('shopee_platform_name', 'Shopee') }}</span>
                    @elseif($platformKey === 'tiktok')
                        <span class="inline-flex items-center px-1.5 py-0.5 rounded-[4px] text-[7px] font-extrabold bg-black text-white shadow-sm shrink-0 leading-none">{{ \App\Models\Setting::getVal('tiktok_platform_name', 'TikTok Shop') }}</span>
                    @elseif($platformKey === 'lazada')
                        <span class="inline-flex items-center px-1.5 py-0.5 rounded-[4px] text-[7px] font-extrabold bg-blue-700 text-white shadow-sm shrink-0 leading-none">{{ __('Lazada') }}</span>
                    @else
                        <span class="inline-flex items-center px-1.5 py-0.5 rounded-[4px] text-[7px] font-extrabold bg-gray-600 text-white shadow-sm shrink-0 leading-none">{{ strtoupper($platformKey) }}</span>
                    @endif
                    <span class="text-[9px] text-gray-400 dark:text-slate-500 font-mono truncate">#{{ $isPending ? $item->trans_id : $item->order_id }}</span>
                </div>
                <span class="text-[9px] text-gray-400 dark:text-slate-500 shrink-0 tabular-nums">{{ $item->created_at->format('d/m H:i') }}</span>
            </div>

            <!-- Hàng 2: Ảnh + Tên sản phẩm + Số tiền hoàn -->
            <div class="flex items-center gap-3 pl-4 pr-3.5 py-2.5">
                <div class="relative w-[52px] h-[52px] shrink-0 rounded-xl overflow-hidden bg-gray-50 dark:bg-slate-800/40 border border-gray-100 dark:border-slate-700/50 flex items-center justify-center">
                    <div class="absolute inset-0 bg-gray-50 dark:bg-slate-800/40 flex items-center justify-center text-gray-400">
                        <i data-lucide="image" class="w-5 h-5"></i>
                    </div>
                    <img src="{{ $item->product_image }}" alt="{{ __('Ảnh sản phẩm') }}" class="absolute inset-0 w-full h-full object-cover z-10" onerror="this.style.display='none';">
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-xs font-semibold text-gray-900 dark:text-slate-100 line-clamp-2 leading-snug">{{ $item->product_name }}</p>
                </div>
                <div class="text-right shrink-0 ml-1">
                    @if($isPending)
                        <p class="text-[13px] font-extrabold text-emerald-600/70 dark:text-emerald-400/70 leading-tight tabular-nums">
                            ~{{ \App\Helpers\CurrencyHelper::format($item->cashback_amount) }}
                        </p>
                        <p class="text-[9px] text-gray-400 dark:text-slate-500 mt-0.5 leading-tight">{{ __('Ước tính') }}</p>
                    @else
                        <p class="text-[13px] font-extrabold text-green-600 dark:text-green-400 leading-tight tabular-nums">
                            +{{ \App\Helpers\CurrencyHelper::format($item->cashback_amount) }}
                        </p>
                        @if($showPrice !== '0')
                            <p class="text-[9px] text-gray-400 dark:text-slate-500 mt-0.5 leading-tight tabular-nums">
                                {{ \App\Helpers\CurrencyHelper::format($item->original_price) }}
                            </p>
                        @endif
                    @endif
                </div>
            </div>

            <!-- Hàng 3: Trạng thái + Mũi tên -->
            <div class="flex items-center justify-between pl-4 pr-3.5 pb-3">
                @if($isPending)
                    <div class="flex items-center gap-1.5">
                        <span class="w-1.5 h-1.5 rounded-full bg-blue-400 animate-pulse shrink-0"></span>
                        <span class="text-[10px] font-semibold text-blue-600 dark:text-blue-400">{{ __('Chờ sàn ghi nhận') }}</span>
                    </div>
                @elseif($item->status === 'pending')
                    <div class="flex items-center gap-1.5">
                        <span class="w-1.5 h-1.5 rounded-full bg-yellow-400 animate-pulse shrink-0"></span>
                        <span class="text-[10px] font-semibold text-yellow-600 dark:text-yellow-500">{{ __('Chờ duyệt') }}</span>
                    </div>
                @elseif($item->status === 'approved')
                    <div class="flex items-center gap-1.5">
                        <span class="w-1.5 h-1.5 rounded-full bg-green-500 shrink-0"></span>
                        <span class="text-[10px] font-semibold text-green-600 dark:text-green-400">{{ __('Đã cộng ví') }}</span>
                    </div>
                @else
                    <div class="flex items-center gap-1.5">
                        <span class="w-1.5 h-1.5 rounded-full bg-red-400 shrink-0"></span>
                        <span class="text-[10px] font-semibold text-red-500 dark:text-red-400">{{ __('Bị từ chối') }}</span>
                    </div>
                @endif
                <i data-lucide="chevron-right" class="w-[15px] h-[15px] text-gray-300 dark:text-slate-600 shrink-0"></i>
            </div>
        </div>
    @empty
        <div class="bg-white dark:bg-slate-900 p-10 rounded-2xl border border-gray-100 dark:border-slate-800 text-center">
            <div class="w-14 h-14 rounded-2xl bg-gray-50 dark:bg-slate-800 flex items-center justify-center mx-auto mb-3">
                <i data-lucide="search-code" class="w-7 h-7 text-gray-300 dark:text-slate-600"></i>
            </div>
            <p class="text-xs font-semibold text-gray-500 dark:text-slate-400">{{ __('Không tìm thấy lịch sử hoàn tiền phù hợp.') }}</p>
        </div>
    @endforelse

    <!-- Phân trang Mobile -->
    @if($histories->hasPages())
        <div class="pt-1 ajax-pagination">
            {{ $histories->links() }}
        </div>
    @endif
</div>
