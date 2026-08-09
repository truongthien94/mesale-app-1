@php $hpSettings = $s; @endphp
            <section aria-labelledby="home-timeline-heading" class="px-4 mx-auto max-w-7xl sm:px-6 lg:px-8 pt-16 md:pt-24 border-t border-gray-100/60 dark:border-slate-800/40">
                <div class="bg-gradient-to-br from-white/90 to-orange-50/45 dark:from-slate-900/60 dark:to-slate-900/20 p-6 md:p-10 rounded-[32px] border border-orange-100/50 dark:border-slate-800/60 shadow-sm overflow-hidden relative"
                     x-data="{ 
                        activeStep: 1,
                        intervalId: null,
                        startAutoPlay() {
                            this.intervalId = setInterval(() => {
                                this.activeStep = this.activeStep < 3 ? this.activeStep + 1 : 1;
                            }, 3500);
                        },
                        stopAutoPlay() {
                            if (this.intervalId) {
                                clearInterval(this.intervalId);
                            }
                        },
                        selectStep(step) {
                            this.activeStep = step;
                            this.stopAutoPlay();
                            this.startAutoPlay();
                        }
                     }"
                     x-init="startAutoPlay()"
                     @mouseenter="stopAutoPlay()"
                     @mouseleave="startAutoPlay()">
                    
                    <!-- Tiêu đề của Timeline -->
                    <div class="text-center max-w-2xl mx-auto mb-8 md:mb-12">
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-orange-50 text-shopee dark:bg-orange-950/30 dark:text-orange-400 border border-orange-100/50 dark:border-orange-900/30">
                            <i data-lucide="activity" class="w-3.5 h-3.5 animate-pulse"></i>
                            {{ trim($hpSettings['hp_timeline_badge'] ?? '') ?: __('Lộ trình hoàn tiền Shopee') }}
                        </span>
                        <h2 id="home-timeline-heading" class="text-2xl md:text-3xl font-extrabold text-gray-900 dark:text-white mt-3 leading-tight">
                            {{ trim($hpSettings['hp_timeline_title'] ?? '') ?: __('Quy Trình Nhận Hoàn Tiền Siêu Tốc') }}
                        </h2>
                        <p class="text-xs md:text-sm text-gray-500 dark:text-slate-400 mt-2 max-w-md mx-auto">
                            {{ trim($hpSettings['hp_timeline_subtitle'] ?? '') ?: __('Hiểu rõ quy trình ghi nhận đơn hàng và thời gian tiền hoàn về tài khoản của bạn.') }}
                        </p>
                    </div>

                    <!-- Trục Timeline -->
                    <div class="relative max-w-5xl mx-auto my-8 md:my-16">
                        <!-- Đường nối ngang trên desktop (Nối từ tâm Node 1 đến tâm Node 3) -->
                        <div class="absolute top-8 left-[16.6%] right-[16.6%] h-1 bg-gray-150 dark:bg-slate-800 -translate-y-1/2 rounded-full hidden md:block"></div>
                        
                        <!-- Thanh tiến trình chạy ngang trên desktop -->
                        <div class="absolute top-8 left-[16.6%] h-1 bg-gradient-to-r from-shopee via-orange-500 to-green-500 -translate-y-1/2 rounded-full transition-all duration-1000 ease-in-out hidden md:block"
                             :style="'width: ' + ((activeStep - 1) * 33.33) + '%'"></div>

                        <!-- Đường nối dọc trên mobile -->
                        <div class="absolute left-8 top-8 bottom-8 w-1 bg-gray-150 dark:bg-slate-800 -translate-x-1/2 rounded-full md:hidden"></div>
                        
                        <!-- Thanh tiến trình chạy dọc trên mobile -->
                        <div class="absolute left-8 top-8 w-1 bg-gradient-to-b from-shopee via-orange-500 to-green-500 -translate-x-1/2 rounded-full transition-all duration-1000 ease-in-out md:hidden"
                             :style="'height: ' + ((activeStep - 1) * 50) + '%'"></div>

                        <!-- Các cột mốc chính -->
                        <div class="relative z-10 flex flex-col md:grid md:grid-cols-3 gap-8 md:gap-6">
                            
                            <!-- Mốc 1: Ngày mua -->
                            <div class="flex items-start md:flex-col md:items-center md:text-center group cursor-pointer" @click="selectStep(1)">
                                <!-- Node mốc tròn -->
                                <div class="w-16 h-16 rounded-2xl flex items-center justify-center border-4 transition-all duration-500 shrink-0 shadow-sm"
                                     :class="activeStep >= 1 ? 'bg-gradient-to-tr from-shopee to-orange-400 border-orange-100 dark:border-orange-950 text-white scale-110 shadow-lg shadow-shopee/25 rotate-6' : 'bg-white dark:bg-slate-850 border-gray-200 dark:border-slate-800 text-gray-400 dark:text-slate-500 group-hover:border-shopee/40 dark:group-hover:border-shopee/30'">
                                    <i data-lucide="shopping-bag" class="w-7 h-7"></i>
                                    
                                    <!-- Pulse sóng lan tỏa động -->
                                    <span x-show="activeStep === 1" class="absolute -inset-1.5 rounded-2xl animate-ping bg-shopee/20 opacity-75"></span>
                                </div>
                                
                                <!-- Content text -->
                                <div class="ml-5 md:ml-0 md:mt-5 flex-grow md:flex-grow-0 space-y-1.5 transition-all duration-300"
                                     :class="activeStep === 1 ? 'scale-105' : 'opacity-70 md:opacity-100'">
                                    <span class="text-[10px] font-extrabold uppercase tracking-wider text-gray-400 dark:text-slate-500 block">{{ trim($hpSettings['hp_timeline_step_1_badge'] ?? '') ?: __('Bước 1: Mua hàng') }}</span>
                                    <h3 class="text-base font-extrabold text-gray-900 dark:text-white">{{ trim($hpSettings['hp_timeline_step_1_title'] ?? '') ?: __('Ngày mua') }}</h3>
                                    <div class="inline-flex px-3 py-1 rounded-full text-xs font-bold transition-all duration-500"
                                         :class="activeStep >= 1 ? 'bg-shopee/10 text-shopee border border-shopee/20 dark:bg-shopee/20 dark:text-orange-400' : 'bg-gray-100 text-gray-450 border-gray-200 dark:bg-slate-800 dark:text-slate-500 dark:border-slate-700'">
                                        {{ trim($hpSettings['hp_timeline_step_1_tag'] ?? '') ?: __('Hôm nay') }}
                                    </div>
                                    <p class="text-[11px] text-gray-500 dark:text-slate-400 mt-2 max-w-xs leading-relaxed block">
                                        {{ trim($hpSettings['hp_timeline_step_1_desc'] ?? '') ?: __('Bạn copy link Shopee dán vào hệ thống, nhận link rút gọn và tiến hành đặt mua hàng.') }}
                                    </p>
                                </div>
                            </div>

                            <!-- Mốc 2: Ghi nhận -->
                            <div class="flex items-start md:flex-col md:items-center md:text-center group cursor-pointer" @click="selectStep(2)">
                                <!-- Node mốc tròn -->
                                <div class="w-16 h-16 rounded-2xl flex items-center justify-center border-4 transition-all duration-500 shrink-0 shadow-sm"
                                     :class="activeStep >= 2 ? 'bg-gradient-to-tr from-orange-500 to-amber-400 border-orange-100 dark:border-orange-950 text-white scale-110 shadow-lg shadow-orange-500/25 -rotate-6' : 'bg-white dark:bg-slate-850 border-gray-200 dark:border-slate-800 text-gray-400 dark:text-slate-500 group-hover:border-orange-400/45 dark:group-hover:border-orange-400/30'">
                                    <i data-lucide="check-circle" class="w-7 h-7"></i>
                                    
                                    <!-- Pulse sóng lan tỏa động -->
                                    <span x-show="activeStep === 2" class="absolute -inset-1.5 rounded-2xl animate-ping bg-orange-500/20 opacity-75"></span>
                                </div>
                                
                                <!-- Content text -->
                                <div class="ml-5 md:ml-0 md:mt-5 flex-grow md:flex-grow-0 space-y-1.5 transition-all duration-300"
                                     :class="activeStep === 2 ? 'scale-105' : 'opacity-70 md:opacity-100'">
                                    <span class="text-[10px] font-extrabold uppercase tracking-wider text-gray-400 dark:text-slate-500 block">{{ trim($hpSettings['hp_timeline_step_2_badge'] ?? '') ?: __('Bước 2: Đối soát') }}</span>
                                    <h3 class="text-base font-extrabold text-gray-900 dark:text-white">{{ trim($hpSettings['hp_timeline_step_2_title'] ?? '') ?: __('Ghi nhận') }}</h3>
                                    <div class="inline-flex px-3 py-1 rounded-full text-xs font-bold transition-all duration-500"
                                         :class="activeStep >= 2 ? 'bg-orange-50 text-orange-600 border border-orange-200/50 dark:bg-orange-950/20 dark:text-orange-400' : 'bg-gray-100 text-gray-450 border-gray-200 dark:bg-slate-800 dark:text-slate-500 dark:border-slate-700'">
                                        {{ trim($hpSettings['hp_timeline_step_2_tag'] ?? '') ?: __('Ngày mai') }}
                                    </div>
                                    <p class="text-[11px] text-gray-500 dark:text-slate-400 mt-2 max-w-xs leading-relaxed block">
                                        {{ trim($hpSettings['hp_timeline_step_2_desc'] ?? '') ?: __('Shopee ghi nhận đơn hàng tạm tính và tự động đồng bộ hiển thị trong lịch sử ví của bạn.') }}
                                    </p>
                                </div>
                            </div>

                            <!-- Mốc 3: Có thể rút -->
                            <div class="flex items-start md:flex-col md:items-center md:text-center group cursor-pointer" @click="selectStep(3)">
                                <!-- Node mốc tròn -->
                                <div class="w-16 h-16 rounded-2xl flex items-center justify-center border-4 transition-all duration-500 shrink-0 shadow-sm"
                                     :class="activeStep >= 3 ? 'bg-gradient-to-tr from-green-500 to-emerald-400 border-green-100 dark:border-green-950 text-white scale-110 shadow-lg shadow-green-500/25 rotate-6' : 'bg-white dark:bg-slate-850 border-gray-200 dark:border-slate-800 text-gray-400 dark:text-slate-500 group-hover:border-green-400/45 dark:group-hover:border-green-400/30'">
                                    <i data-lucide="wallet" class="w-7 h-7"></i>
                                    
                                    <!-- Pulse sóng lan tỏa động -->
                                    <span x-show="activeStep === 3" class="absolute -inset-1.5 rounded-2xl animate-ping bg-green-500/20 opacity-75"></span>
                                </div>
                                
                                <!-- Content text -->
                                <div class="ml-5 md:ml-0 md:mt-5 flex-grow md:flex-grow-0 space-y-1.5 transition-all duration-300"
                                     :class="activeStep === 3 ? 'scale-105' : 'opacity-70 md:opacity-100'">
                                    <span class="text-[10px] font-extrabold uppercase tracking-wider text-gray-400 dark:text-slate-500 block">{{ trim($hpSettings['hp_timeline_step_3_badge'] ?? '') ?: __('Bước 3: Thực nhận') }}</span>
                                    <h3 class="text-base font-extrabold text-gray-900 dark:text-white">{{ trim($hpSettings['hp_timeline_step_3_title'] ?? '') ?: __('Có thể rút') }}</h3>
                                    <div class="inline-flex px-3 py-1 rounded-full text-xs font-bold transition-all duration-500"
                                         :class="activeStep >= 3 ? 'bg-green-50 text-green-600 border border-green-200/50 dark:bg-green-950/20 dark:text-green-400' : 'bg-gray-105 text-gray-450 border-gray-200 dark:bg-slate-800 dark:text-slate-500 dark:border-slate-700'">
                                        {{ trim($hpSettings['hp_timeline_step_3_tag'] ?? '') ?: __('7 ngày') }}
                                    </div>
                                    <p class="text-[11px] text-gray-500 dark:text-slate-400 mt-2 max-w-xs leading-relaxed block">
                                        {{ trim($hpSettings['hp_timeline_step_3_desc'] ?? '') ?: __('Sau khi Shopee đối soát kỳ hoàn thành (khoảng 7 ngày khi nhận hàng), tiền khả dụng sẽ được cộng vào ví và có thể rút ngay.') }}
                                    </p>
                                </div>
                            </div>

                        </div>
                    </div>
                    
                </div>
            </section>
