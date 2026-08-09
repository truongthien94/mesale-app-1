@php
    // Chỉ hiển thị khối này khi người dùng đã đăng nhập tài khoản. Nếu chưa đăng nhập (khách/ẩn danh) sẽ không hiển thị.
    if (!auth()->check()) {
        return;
    }

    $limit = (int) ($s['limit'] ?? 6);
    if ($limit <= 0) {
        $limit = 6;
    }

    // Lấy danh sách link hoàn tiền ban đầu trực tiếp từ CSDL
    $initialClicks = \App\Models\CashbackClick::where('user_id', auth()->id())
        ->with(['cashbackHistory'])
        ->orderBy('created_at', 'desc')
        ->limit($limit + 1)
        ->get();

    // Nếu người dùng chưa từng tạo link hoàn tiền nào, tạm thời không render block
    if ($initialClicks->count() === 0) {
        return;
    }

    $shopeeName = \App\Models\Setting::getVal('shopee_platform_name', 'Shopee');
    $tiktokName = \App\Models\Setting::getVal('tiktok_platform_name', 'TikTok Shop');
    $lazadaName = \App\Models\Setting::getVal('lazada_platform_name', 'Lazada');

    $platformNames = [
        'shopee' => $shopeeName,
        'tiktok' => $tiktokName,
        'lazada' => $lazadaName,
    ];

    $hasMoreInitial = $initialClicks->count() > $limit;
    $initialItems = $initialClicks->slice(0, $limit)->map(function ($click) use ($platformNames) {
        $history = $click->cashbackHistory;
        $status = 'none';
        $statusLabel = __('Chưa ghi nhận đơn');

        if ($history) {
            $status = $history->status;
            if ($history->status === 'approved') {
                $statusLabel = __('Đã ghi nhận (Đã duyệt)');
            } elseif ($history->status === 'pending') {
                $statusLabel = __('Đã ghi nhận (Chờ duyệt)');
            } else {
                $statusLabel = __('Đã ghi nhận (Từ chối)');
            }
        }

        $platformKey = strtolower($click->platform ?? 'shopee');

        return [
            'id' => $click->id,
            'trans_id' => $click->trans_id,
            'platform' => $platformKey,
            'platform_name' => $platformNames[$platformKey] ?? ucfirst($platformKey),
            'product_name' => $click->product_name ?: __('Sản phẩm Shopee'),
            'product_image' => $click->product_image,
            'cashback_amount_formatted' => \App\Helpers\CurrencyHelper::format($click->cashback_amount),
            'affiliate_url' => $click->affiliate_url,
            'created_at_human' => $click->created_at->diffForHumans(),
            'status' => $status,
            'status_label' => $statusLabel,
        ];
    })->values();
@endphp

<div x-data="{
    ready: false,
    items: {{ json_encode($initialItems) }},
    limit: {{ $limit }},
    hasMore: {{ $hasMoreInitial ? 'true' : 'false' }},
    loadingMore: false,

    init() {
        // Giả lập hiệu ứng Skeleton Loading Shimmer mượt mà khi vừa F5/tải trang
        setTimeout(() => {
            this.ready = true;
            this.$nextTick(() => { if (window.lucide) window.lucide.createIcons(); });
        }, 350);

        // Lắng nghe sự kiện link-created khi người dùng vừa chuyển đổi link mới ở form hero phía trên
        window.addEventListener('link-created', () => {
            this.refreshList();
        });
    },

    refreshList() {
        fetch('{{ route("dashboard.created_links") }}?offset=0&limit=' + this.limit, {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(res => res.json())
        .then(res => {
            if (res.status === 'success') {
                this.items = res.data;
                this.hasMore = res.has_more;
                this.$nextTick(() => { if (window.lucide) window.lucide.createIcons(); });
            }
        })
        .catch(err => console.error(err));
    },

    loadMore() {
        if (this.loadingMore || !this.hasMore) return;
        this.loadingMore = true;

        fetch('{{ route("dashboard.created_links") }}?offset=' + this.items.length + '&limit=' + this.limit, {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(res => res.json())
        .then(res => {
            if (res.status === 'success') {
                this.items = [...this.items, ...res.data];
                this.hasMore = res.has_more;
                this.$nextTick(() => { if (window.lucide) window.lucide.createIcons(); });
            }
        })
        .catch(err => console.error(err))
        .finally(() => {
            this.loadingMore = false;
        });
    }
}" x-show="items.length > 0" class="px-4 mx-auto max-w-7xl sm:px-6 lg:px-8 pt-12 md:pt-16 transition-opacity duration-300">
    <div class="bg-white/90 dark:bg-slate-900/80 p-6 md:p-10 rounded-[32px] border border-gray-100 dark:border-slate-800 shadow-sm backdrop-blur-md">
        
        <!-- Header của Khối Danh Sách Link -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-end gap-4 mb-8 md:mb-10">
            <div>
                <span class="inline-flex items-center gap-1.5 px-3.5 py-1 rounded-full text-xs font-bold bg-orange-50 text-orange-600 dark:bg-orange-950/30 dark:text-orange-400 border border-orange-200 dark:border-orange-900/30">
                    <i data-lucide="user-check" class="w-3.5 h-3.5"></i>
                    {{ __('Link hoàn tiền của bạn') }}
                </span>
                <h2 class="text-2xl md:text-3xl font-extrabold text-gray-900 dark:text-white mt-2.5 leading-tight">
                    {{ $s['title'] ?? __('Link Hoàn Tiền Của Bạn Đã Tạo') }}
                </h2>
                <p class="text-xs md:text-sm text-gray-500 dark:text-slate-400 mt-1">
                    {{ !empty($s['subtitle']) ? $s['subtitle'] : __('Danh sách các sản phẩm bạn vừa chuyển đổi link và trạng thái đối soát ghi nhận đơn.') }}
                </p>
            </div>
            
            <a href="{{ route('cashback.history') }}" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-bold text-[#FF451A] bg-orange-50 dark:bg-orange-950/30 hover:bg-orange-100 dark:hover:bg-orange-900/50 border border-orange-200/60 dark:border-orange-900/40 transition-all shrink-0 shadow-sm">
                <i data-lucide="receipt" class="w-3.5 h-3.5"></i>
                <span>{{ __('Xem đơn hàng') }}</span>
                <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
            </a>
        </div>

        <!-- Danh sách hiển thị các link vừa được tạo -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 md:gap-6">
            
            <!-- 1. Skeleton Loading Placeholders nhấp nháy khi vừa F5/Tải trang (ready = false) -->
            <template x-if="!ready">
                <template x-for="i in [1, 2, 3, 4, 5, 6]" :key="'init-skel-' + i">
                    <div class="bg-gray-50/70 dark:bg-slate-800/40 p-4 rounded-2xl border border-gray-100 dark:border-slate-800 animate-pulse flex flex-col justify-between space-y-3">
                        <div class="flex items-start gap-3">
                            <div class="w-14 h-14 rounded-xl bg-gray-200 dark:bg-slate-700/80 shrink-0"></div>
                            <div class="flex-1 space-y-2">
                                <div class="flex items-center gap-2">
                                    <div class="w-14 h-4 bg-gray-200 dark:bg-slate-700/80 rounded-md"></div>
                                    <div class="w-16 h-3 bg-gray-200 dark:bg-slate-700/60 rounded"></div>
                                </div>
                                <div class="w-full h-3.5 bg-gray-200 dark:bg-slate-700/80 rounded-md"></div>
                                <div class="w-3/4 h-3.5 bg-gray-200 dark:bg-slate-700/60 rounded-md"></div>
                            </div>
                        </div>
                        <div class="pt-3 border-t border-gray-200/60 dark:border-slate-700/60 space-y-2.5">
                            <div class="flex items-center justify-between gap-2">
                                <div class="w-20 h-4 bg-gray-200 dark:bg-slate-700/80 rounded-md"></div>
                                <div class="w-24 h-5 bg-gray-200 dark:bg-slate-700/80 rounded-lg"></div>
                            </div>
                            <div class="grid grid-cols-2 gap-2 pt-1">
                                <div class="h-8 bg-gray-200 dark:bg-slate-700/80 rounded-xl"></div>
                                <div class="h-8 bg-gray-200 dark:bg-slate-700/80 rounded-xl"></div>
                            </div>
                        </div>
                    </div>
                </template>
            </template>

            <!-- 2. Thẻ sản phẩm thật hiển thị khi đã sẵn sàng (ready = true) -->
            <template x-if="ready">
                <template x-for="item in items" :key="item.id || item.trans_id">
                    <div class="bg-gray-50/70 dark:bg-slate-800/40 p-4 rounded-2xl border border-gray-100 dark:border-slate-800 hover:border-orange-200 dark:hover:border-orange-900/50 transition-all group flex flex-col justify-between space-y-3">
                        
                        <!-- Thông tin sản phẩm -->
                        <div class="flex items-start gap-3">
                            <!-- Ảnh sản phẩm -->
                            <div class="relative w-14 h-14 rounded-xl overflow-hidden bg-gray-200 dark:bg-slate-700 shrink-0 border border-gray-200/60 dark:border-slate-700 flex items-center justify-center">
                                <div class="absolute inset-0 bg-gray-100 dark:bg-slate-800 flex items-center justify-center text-gray-400">
                                    <i data-lucide="image" class="w-5 h-5"></i>
                                </div>
                                <template x-if="item.product_image">
                                    <img :src="item.product_image" alt="" class="absolute inset-0 w-full h-full object-cover z-10" x-on:error="$el.style.display='none'">
                                </template>
                            </div>

                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-1.5 text-[10px] font-bold text-gray-400 dark:text-slate-500 mb-0.5">
                                    <template x-if="item.platform === 'shopee'">
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-extrabold bg-[#ff5722] text-white shadow-xs">
                                            <i data-lucide="shopping-bag" class="w-2.5 h-2.5 text-white"></i>
                                            <span x-text="item.platform_name || '{{ $shopeeName }}'"></span>
                                        </span>
                                    </template>
                                    <template x-if="item.platform === 'tiktok'">
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-extrabold bg-black text-white shadow-xs">
                                            <i data-lucide="shopping-cart" class="w-2.5 h-2.5 text-white"></i>
                                            <span x-text="item.platform_name || '{{ $tiktokName }}'"></span>
                                        </span>
                                    </template>
                                    <template x-if="item.platform === 'lazada'">
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-extrabold bg-[#0f146d] text-white shadow-xs">
                                            <i data-lucide="shopping-bag" class="w-2.5 h-2.5 text-white"></i>
                                            <span x-text="item.platform_name || '{{ $lazadaName }}'"></span>
                                        </span>
                                    </template>
                                    <template x-if="item.platform !== 'shopee' && item.platform !== 'tiktok' && item.platform !== 'lazada'">
                                        <span class="capitalize px-2 py-0.5 rounded text-[10px] font-extrabold bg-gray-200/70 dark:bg-slate-700 text-gray-700 dark:text-slate-300" x-text="item.platform_name || item.platform"></span>
                                    </template>
                                    <span>•</span>
                                    <span x-text="item.created_at_human"></span>
                                </div>
                                <h4 class="text-xs font-bold text-gray-900 dark:text-white line-clamp-2 leading-snug group-hover:text-[#FF451A] transition-colors" x-text="item.product_name"></h4>
                            </div>
                        </div>

                        <!-- Hàng Tiền tiết kiệm dự kiến, Trạng thái đơn & Nút Mua ngay / Sao chép link -->
                        <div class="pt-3 border-t border-gray-200/60 dark:border-slate-700/60 space-y-2.5">
                            <div class="flex items-center justify-between gap-2">
                                <div>
                                    <span class="block text-[9px] font-bold text-gray-400 dark:text-slate-500 uppercase tracking-wider">{{ __('Tiết kiệm dự kiến') }}</span>
                                    <span class="text-xs font-black text-[#FF451A] dark:text-orange-400" x-text="'+' + item.cashback_amount_formatted"></span>
                                </div>

                                <!-- Trạng thái ghi nhận đơn -->
                                <div class="shrink-0">
                                    <template x-if="item.status === 'approved'">
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-[10px] font-extrabold bg-emerald-50 dark:bg-emerald-950/30 text-emerald-600 dark:text-emerald-400 border border-emerald-200/60 dark:border-emerald-900/40">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                            <span x-text="item.status_label"></span>
                                        </span>
                                    </template>
                                    <template x-if="item.status === 'pending'">
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-[10px] font-extrabold bg-amber-50 dark:bg-amber-950/30 text-amber-600 dark:text-amber-400 border border-amber-200/60 dark:border-amber-900/40">
                                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                                            <span x-text="item.status_label"></span>
                                        </span>
                                    </template>
                                    <template x-if="item.status === 'rejected'">
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-[10px] font-extrabold bg-red-50 dark:bg-red-950/30 text-red-600 dark:text-red-400 border border-red-200/60 dark:border-red-900/40">
                                            <span x-text="item.status_label"></span>
                                        </span>
                                    </template>
                                    <template x-if="item.status === 'none'">
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-[10px] font-extrabold bg-gray-100 dark:bg-slate-800 text-gray-500 dark:text-slate-400 border border-gray-200 dark:border-slate-700">
                                            <i data-lucide="clock" class="w-3 h-3 text-gray-400"></i>
                                            <span x-text="item.status_label"></span>
                                        </span>
                                    </template>
                                </div>
                            </div>

                            <!-- Nút Mua ngay & Sao chép link -->
                            <template x-if="item.affiliate_url">
                                <div class="grid grid-cols-2 gap-2 pt-1">
                                    <a :href="item.affiliate_url"
                                       target="_blank"
                                       rel="noopener noreferrer"
                                       class="inline-flex items-center justify-center gap-1.5 px-3 py-2 rounded-xl text-xs font-extrabold text-white bg-[#FF451A] hover:bg-[#e03a13] transition-all shadow-sm active:scale-95">
                                        <i data-lucide="external-link" class="w-3.5 h-3.5"></i>
                                        <span>{{ __('Mua ngay') }}</span>
                                    </a>

                                    <button type="button"
                                            x-data="{ copied: false }"
                                            @click="navigator.clipboard.writeText(item.affiliate_url); copied = true; setTimeout(() => copied = false, 2000)"
                                            class="inline-flex items-center justify-center gap-1.5 px-3 py-2 rounded-xl text-xs font-extrabold text-[#FF451A] bg-orange-50 dark:bg-orange-950/30 hover:bg-orange-100 dark:hover:bg-orange-900/50 border border-orange-200/60 dark:border-orange-900/40 transition-all shadow-sm active:scale-95">
                                        <i data-lucide="copy" class="w-3.5 h-3.5 text-[#FF451A]" x-show="!copied"></i>
                                        <i data-lucide="check" class="w-3.5 h-3.5 text-emerald-500" x-show="copied" x-cloak></i>
                                        <span x-text="copied ? '{{ __('Đã sao chép') }}' : '{{ __('Sao chép link') }}'"></span>
                                    </button>
                                </div>
                            </template>
                        </div>

                    </div>
                </template>
            </template>

            <!-- 3. Skeleton Loading Card Placeholders hiển thị khi đang tải thêm link (ready = true && loadingMore = true) -->
            <template x-if="ready && loadingMore">
                <template x-for="i in [1, 2, 3]" :key="'skel-' + i">
                    <div class="bg-gray-50/70 dark:bg-slate-800/40 p-4 rounded-2xl border border-gray-100 dark:border-slate-800 animate-pulse flex flex-col justify-between space-y-3">
                        <div class="flex items-start gap-3">
                            <div class="w-14 h-14 rounded-xl bg-gray-200 dark:bg-slate-700/80 shrink-0"></div>
                            <div class="flex-1 space-y-2">
                                <div class="flex items-center gap-2">
                                    <div class="w-14 h-4 bg-gray-200 dark:bg-slate-700/80 rounded-md"></div>
                                    <div class="w-16 h-3 bg-gray-200 dark:bg-slate-700/60 rounded"></div>
                                </div>
                                <div class="w-full h-3.5 bg-gray-200 dark:bg-slate-700/80 rounded-md"></div>
                                <div class="w-3/4 h-3.5 bg-gray-200 dark:bg-slate-700/60 rounded-md"></div>
                            </div>
                        </div>
                        <div class="pt-3 border-t border-gray-200/60 dark:border-slate-700/60 space-y-2.5">
                            <div class="flex items-center justify-between gap-2">
                                <div class="w-20 h-4 bg-gray-200 dark:bg-slate-700/80 rounded-md"></div>
                                <div class="w-24 h-5 bg-gray-200 dark:bg-slate-700/80 rounded-lg"></div>
                            </div>
                            <div class="grid grid-cols-2 gap-2 pt-1">
                                <div class="h-8 bg-gray-200 dark:bg-slate-700/80 rounded-xl"></div>
                                <div class="h-8 bg-gray-200 dark:bg-slate-700/80 rounded-xl"></div>
                            </div>
                        </div>
                    </div>
                </template>
            </template>
        </div>

        <!-- Nút Tải Thêm Link -->
        <div x-show="hasMore" class="mt-8 md:mt-10 text-center">
            <button type="button"
                    @click="loadMore()"
                    :disabled="loadingMore"
                    class="inline-flex items-center justify-center gap-2 px-6 py-3 rounded-2xl text-xs font-extrabold text-gray-700 dark:text-slate-200 bg-gray-50 dark:bg-slate-800/80 hover:bg-orange-50 dark:hover:bg-orange-950/30 hover:text-[#FF451A] dark:hover:text-orange-400 border border-gray-200/80 dark:border-slate-700/80 transition-all shadow-sm active:scale-95 disabled:opacity-60">
                <template x-if="loadingMore">
                    <div class="w-4 h-4 border-2 border-[#FF451A] border-t-transparent rounded-full animate-spin"></div>
                </template>
                <i data-lucide="arrow-down-circle" class="w-4 h-4 text-[#FF451A]" x-show="!loadingMore"></i>
                <span x-text="loadingMore ? '{{ __('Đang tải thêm...') }}' : '{{ __('Tải thêm link hoàn tiền') }}'"></span>
            </button>
        </div>

    </div>
</div>
