@if(isset($shopeeCoupons) && count($shopeeCoupons) > 0)
            <div class="px-4 mx-auto max-w-7xl sm:px-6 lg:px-8 pt-16 md:pt-24 border-t border-gray-100/60 dark:border-slate-800/40" x-data="{
                scrollLeft() {
                    $refs.couponSlider.scrollBy({ left: -344, behavior: 'smooth' });
                },
                scrollRight() {
                    $refs.couponSlider.scrollBy({ left: 344, behavior: 'smooth' });
                }
            }">
                <div class="bg-gradient-to-br from-white/90 to-orange-50/45 dark:from-slate-900/60 dark:to-slate-900/20 p-8 md:p-12 rounded-[32px] border border-orange-100/50 dark:border-slate-800/60 shadow-sm">
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-end gap-4 mb-8 md:mb-12">
                        <div>
                            <span class="inline-flex items-center gap-1.5 px-3.5 py-1 rounded-full text-xs font-bold bg-orange-100 text-shopee border border-orange-200/50 dark:bg-orange-950/30 dark:text-orange-400">
                                <i data-lucide="ticket" class="w-3.5 h-3.5"></i>
                                {{ __('Ưu đãi hot hôm nay') }}
                            </span>
                            <h2 class="text-2xl md:text-3xl font-extrabold text-gray-900 dark:text-white mt-3 leading-tight">
                                {{ __('Mã khuyến mãi') }} <span class="text-shopee">{{ __('Shopee') }}</span>
                            </h2>
                        </div>
                        
                        <div class="flex items-center gap-2">
                            <!-- Nút điều hướng Trái -->
                            <button @click="scrollLeft()" class="w-10 h-10 rounded-xl bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 hover:border-shopee dark:hover:border-shopee text-gray-500 hover:text-shopee flex items-center justify-center transition-all shadow-sm focus:outline-none cursor-pointer">
                                <i data-lucide="chevron-left" class="w-5 h-5"></i>
                            </button>
                            <!-- Nút điều hướng Phải -->
                            <button @click="scrollRight()" class="w-10 h-10 rounded-xl bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 hover:border-shopee dark:hover:border-shopee text-gray-500 hover:text-shopee flex items-center justify-center transition-all shadow-sm focus:outline-none cursor-pointer">
                                <i data-lucide="chevron-right" class="w-5 h-5"></i>
                            </button>
                        </div>
                    </div>

                <!-- Carousel Container -->
                <div class="relative">
                    <div x-ref="couponSlider" class="flex gap-6 overflow-x-auto scrollbar-none pb-4 snap-x snap-mandatory" style="-ms-overflow-style: none; scrollbar-width: none;">
                        @foreach($shopeeCoupons as $coupon)
                            @php
                                $discountText = '';
                                // Chỉ coi là voucher giảm theo % khi giá trị nằm trong khoảng phần trăm hợp lệ (1-100).
                                // Một số voucher giảm theo số tiền vẫn trả về discount_percentage > 100 (ví dụ 150000),
                                // nếu không kiểm tra sẽ hiển thị sai thành "150000%".
                                $isPercentage = $coupon->discount_percentage > 0 && $coupon->discount_percentage <= 100;
                                if ($isPercentage) {
                                    $discountText = $coupon->discount_percentage . '%';
                                } else {
                                    // Ưu tiên discount_amount, nếu trống thì dùng giá trị trong discount_percentage làm số tiền
                                    $amount = $coupon->discount_amount > 0 ? $coupon->discount_amount : $coupon->discount_percentage;
                                    if ($amount >= 1000000) {
                                        $discountText = number_format($amount / 1000000, $amount % 1000000 === 0 ? 0 : 1) . 'Tr';
                                    } elseif ($amount >= 1000) {
                                        $discountText = number_format($amount / 1000, 0) . 'k';
                                    } else {
                                        $discountText = number_format($amount) . 'đ';
                                    }
                                }

                                $usedPercentage = min(100, max(5, (($coupon->clicks ?? 0) % 90) + 10));

                                // Xác định voucher có mã thực tế (không có tiền tố BANNER_ và không rỗng)
                                $isRealCoupon = !str_starts_with($coupon->code, 'BANNER_') && !empty($coupon->code);

                                // Tạo link chuyển hướng của voucher và đính kèm utm_source tracking
                                $couponRedirectLink = $coupon->redirect_link ?: 'https://shopee.vn';
                                $couponUtmSource = \App\Models\Setting::getVal('coupon_utm_source', 'magiamgia');
                                if (!empty($couponRedirectLink) && !empty($couponUtmSource)) {
                                    $separator = str_contains($couponRedirectLink, '?') ? '&' : '?';
                                    if (!str_contains($couponRedirectLink, 'utm_source=')) {
                                        $couponRedirectLink .= $separator . 'utm_source=' . urlencode($couponUtmSource);
                                    }
                                }

                                // Đường dẫn chuyển hướng khi người dùng sao chép (copy) mã giảm giá
                                $copyRedirectUrl = \App\Models\Setting::getVal('coupon_redirect_url') ?: $couponRedirectLink;
                                if (\App\Models\Setting::getVal('coupon_redirect_url') && !empty($couponUtmSource)) {
                                    $separator = str_contains($copyRedirectUrl, '?') ? '&' : '?';
                                    if (!str_contains($copyRedirectUrl, 'utm_source=')) {
                                        $copyRedirectUrl .= $separator . 'utm_source=' . urlencode($couponUtmSource);
                                    }
                                }
                            @endphp
                            <!-- Card Voucher -->
                            <div class="flex-shrink-0 w-80 bg-white dark:bg-slate-900 rounded-2xl border border-gray-150 dark:border-slate-800 shadow-sm overflow-hidden flex relative snap-align-start group hover:shadow-md transition-shadow duration-300">
                                <!-- Phần bên trái (Cuống vé): Màu cam -->
                                <div class="w-1/3 bg-gradient-to-br from-orange-500 to-shopee text-white flex flex-col items-center justify-center p-3 text-center relative select-none">
                                    <!-- Viền răng cưa (lõm ở 2 đầu phân cách) -->
                                    <div class="absolute -right-1.5 top-0 bottom-0 w-3 flex flex-col justify-around pointer-events-none z-10">
                                        @for ($i = 0; $i < 6; $i++)
                                            <div class="w-3 h-3 bg-white dark:bg-slate-900 rounded-full -mr-1.5"></div>
                                        @endfor
                                    </div>
                                    
                                    <span class="text-[9px] font-bold opacity-90 tracking-wider uppercase mb-1">{{ __('ƯU ĐÃI') }}</span>
                                    <span class="text-2xl font-black tracking-tight leading-none mb-1">{{ $discountText }}</span>
                                    @if ($isPercentage && $coupon->discount_amount > 0)
                                        <span class="text-[9px] font-medium opacity-95">Tối đa {{ number_format($coupon->discount_amount) }}đ</span>
                                    @endif
                                    <span class="text-[8px] font-extrabold opacity-75 border-t border-white/20 mt-1.5 pt-1.5 uppercase block tracking-tighter w-full">
                                        @if ($coupon->min_spend > 0)
                                            ĐƠN TỪ {{ number_format($coupon->min_spend / 1000) }}k
                                        @else
                                            MỌI ĐƠN HÀNG
                                        @endif
                                    </span>
                                </div>

                                <!-- Phần bên phải (Nội dung): Màu trắng -->
                                <div class="w-2/3 p-4 bg-white dark:bg-slate-900 flex flex-col justify-between space-y-2 relative">
                                    <div class="space-y-1">
                                        <div class="flex justify-between items-start">
                                            <h4 class="font-bold text-gray-900 dark:text-white text-xs line-clamp-2 leading-snug pr-4" title="{{ $coupon->title }}">
                                                {{ $coupon->title }}
                                            </h4>
                                            <!-- Biểu tượng Shopee góc phải -->
                                            <span class="absolute right-3 top-3 bg-orange-100 dark:bg-orange-950/30 text-shopee w-5 h-5 rounded-full flex items-center justify-center shrink-0">
                                                <i data-lucide="shopping-bag" class="w-3 h-3"></i>
                                            </span>
                                        </div>
                                        
                                        @if($isRealCoupon)
                                            <div class="mt-1 flex items-center gap-1.5">
                                                <a href="{{ $copyRedirectUrl }}"
                                                   target="_blank"
                                                   rel="noopener noreferrer"
                                                   class="px-2 py-0.5 bg-orange-50/80 dark:bg-orange-950/20 border border-dashed border-orange-350 dark:border-orange-900/50 rounded-lg text-[10px] font-mono font-extrabold text-[#f95522] dark:text-orange-400 select-all cursor-pointer"
                                                   @click="
                                                       navigator.clipboard.writeText('{{ $coupon->code }}');
                                                       $dispatch('toast', { text: '{{ \App\Models\Setting::getVal('coupon_redirect_on_copy', '0') === '1' ? __('Đã sao chép mã :code! Đang mở liên kết...', ['code' => $coupon->code]) : __('Đã sao chép mã :code!', ['code' => $coupon->code]) }}', type: 'success' });
                                                       @if(\App\Models\Setting::getVal('coupon_redirect_on_copy', '0') !== '1')
                                                           $event.preventDefault();
                                                       @endif
                                                   "
                                                   title="{{ __('Bấm để sao chép nhanh') }}">
                                                    {{ $coupon->code }}
                                                </a>
                                            </div>
                                        @endif
                                        
                                        <!-- Tỷ lệ đã dùng -->
                                        <div class="space-y-1">
                                            <span class="text-[10px] text-gray-400 dark:text-slate-500 block font-medium">Đã dùng {{ $usedPercentage }}%</span>
                                            <div class="w-full bg-gray-100 dark:bg-slate-800 h-1.5 rounded-full overflow-hidden">
                                                <div class="bg-shopee h-full rounded-full" style="width: {{ $usedPercentage }}%"></div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="flex justify-between items-end pt-1">
                                        <div class="space-y-0.5">
                                            <span class="text-[9px] text-gray-450 dark:text-slate-400 font-bold flex items-center gap-1">
                                                <i data-lucide="clock" class="w-3 h-3"></i>
                                                @if ($coupon->expired_at)
                                                    {{ __('CÒN :days NGÀY', ['days' => max(1, (int) ceil(now()->diffInDays($coupon->expired_at)))]) }}
                                                @else
                                                    {{ __('CÒN :days NGÀY', ['days' => 3]) }}
                                                @endif
                                            </span>
                                            <span class="text-[9px] text-shopee font-bold px-1.5 py-0.5 bg-orange-50 dark:bg-orange-950/20 rounded w-max block">
                                                {{ $coupon->category ?: __('Toàn sàn') }}
                                            </span>
                                        </div>
                                        
                                        @if(!$isRealCoupon)
                                            <a href="{{ $couponRedirectLink }}" target="_blank" class="px-3 py-1.5 bg-orange-50 dark:bg-orange-950/20 hover:bg-shopee dark:hover:bg-shopee text-shopee hover:text-white dark:text-orange-400 dark:hover:text-white font-extrabold rounded-xl text-[10px] border border-orange-100 dark:border-slate-800 transition-all select-none">
                                                {{ __('Dùng ngay') }}
                                            </a>
                                        @else
                                            <a href="{{ $copyRedirectUrl }}"
                                               target="_blank"
                                               rel="noopener noreferrer"
                                               @click="
                                                   navigator.clipboard.writeText('{{ $coupon->code }}');
                                                   $dispatch('toast', { text: '{{ \App\Models\Setting::getVal('coupon_redirect_on_copy', '0') === '1' ? __('Đã sao chép mã :code! Đang mở liên kết...', ['code' => $coupon->code]) : __('Đã sao chép mã :code thành công!', ['code' => $coupon->code]) }}', type: 'success' });
                                                   @if(\App\Models\Setting::getVal('coupon_redirect_on_copy', '0') !== '1')
                                                       $event.preventDefault();
                                                   @endif
                                               "
                                               class="px-3 py-1.5 bg-orange-50 dark:bg-orange-950/20 hover:bg-shopee dark:hover:bg-shopee text-shopee hover:text-white dark:text-orange-400 dark:hover:text-white font-extrabold rounded-xl text-[10px] border border-orange-100 dark:border-slate-800 transition-all select-none cursor-pointer text-center">
                                                {{ __('Sao chép') }}
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                        </div>
                    </div>
                </div>
            </div>

@endif
