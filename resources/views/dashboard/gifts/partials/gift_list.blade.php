<!-- 
    Tệp tin hiển thị danh sách quà tặng (Gift List Partial View)
    Được thiết kế theo phong cách Ticket/Voucher chuyên nghiệp với viền răng cưa đục lỗ
    Hỗ trợ hiển thị lọc theo tag nhanh và phân trang AJAX mượt mà
-->
<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-2">
    <div class="flex items-center gap-2">
        <span class="w-1.5 h-4 bg-shopee rounded-full"></span>
        <h2 class="text-sm font-bold text-gray-800 dark:text-gray-200 uppercase tracking-wider">{{ __('Quà Tặng Có Sẵn') }}</h2>
    </div>
    
    <!-- Bộ lọc theo Tag nhanh dạng Pills -->
    @if(count($availableTags) > 0)
        <div class="flex flex-wrap gap-1.5 items-center">
            <button type="button" 
                    @click="loadGifts('{{ route('gifts.index', array_merge(request()->query(), ['tag' => 'all', 'page' => 1])) }}')"
                    class="px-3 py-1 text-[11px] font-bold rounded-xl transition-all duration-200 {{ request('tag', 'all') === 'all' ? 'bg-shopee text-white shadow-md shadow-shopee/10 scale-[1.02]' : 'bg-gray-100 dark:bg-slate-800 text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-slate-700/80' }}">
                {{ __('Tất cả') }}
            </button>
            @foreach($availableTags as $t)
                <button type="button" 
                        @click="loadGifts('{{ route('gifts.index', array_merge(request()->query(), ['tag' => $t, 'page' => 1])) }}')"
                        class="px-3 py-1 text-[11px] font-bold rounded-xl transition-all duration-200 {{ request('tag') === $t ? 'bg-shopee text-white shadow-md shadow-shopee/10 scale-[1.02]' : 'bg-gray-100 dark:bg-slate-800 text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-slate-700/80' }}">
                    {{ $t }}
                </button>
            @endforeach
        </div>
    @endif
</div>

<!-- Lưới hiển thị danh sách quà tặng -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    @forelse($gifts as $gift)
        <!-- Card Quà Tặng dạng Ticket (Vé đổi thưởng) -->
        <div class="relative flex bg-white dark:bg-slate-900 border border-gray-100 dark:border-slate-800/80 rounded-2xl shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all duration-300 group overflow-hidden">
            
            <!-- Phần trái: Cuống vé (Ảnh sản phẩm/nhãn hàng) -->
            <div class="w-24 sm:w-32 flex-shrink-0 bg-slate-50/50 dark:bg-slate-800/10 p-3 flex flex-col items-center justify-center relative border-r border-dashed border-gray-150 dark:border-slate-800/80">
                <!-- Lỗ đục vé ở trên mép nối dọc -->
                <div class="absolute -top-2 -right-2 w-4 h-4 rounded-full bg-gray-50 dark:bg-slate-950 border-b border-gray-150/40 dark:border-slate-900/30 z-10"></div>
                <!-- Lỗ đục vé ở dưới mép nối dọc -->
                <div class="absolute -bottom-2 -right-2 w-4 h-4 rounded-full bg-gray-50 dark:bg-slate-950 border-t border-gray-150/40 dark:border-slate-900/30 z-10"></div>
                
                <!-- Container ảnh bo góc -->
                <div class="w-16 h-16 sm:w-24 sm:h-24 rounded-xl bg-white dark:bg-slate-900 border border-gray-100 dark:border-slate-800/60 overflow-hidden flex items-center justify-center shadow-inner group-hover:scale-[1.03] transition-transform duration-300">
                    @if($gift->image)
                        <img src="{{ $gift->image }}" alt="{{ $gift->title }}" class="w-full h-full object-cover">
                    @else
                        <div class="w-full h-full bg-gradient-to-tr from-shopee/5 to-shopee-light/5 flex items-center justify-center text-shopee/50 dark:text-shopee-light/50">
                            <i data-lucide="gift" class="w-7 h-7 sm:w-10 sm:h-10"></i>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Phần phải: Nội dung vé (Thông tin chi tiết quà tặng) -->
            <div class="flex-grow p-3.5 sm:p-5 flex flex-col justify-between min-w-0">
                <div class="space-y-1.5">
                    <!-- Tag quà tặng -->
                    @if($gift->tag)
                        <div class="flex items-center justify-between gap-2">
                            <span class="text-[9px] font-bold text-gray-400 dark:text-slate-500 uppercase tracking-wider">#{{ $gift->tag }}</span>
                        </div>
                    @endif

                    <!-- Tiêu đề quà -->
                    <h3 class="font-bold text-gray-900 dark:text-white text-xs sm:text-sm md:text-base leading-snug truncate group-hover:text-shopee dark:group-hover:text-shopee-light transition-colors" title="{{ $gift->title }}">
                        {{ $gift->title }}
                    </h3>
                    
                    <!-- Mô tả quà tặng ngắn -->
                    <p class="text-[10px] sm:text-xs text-gray-400 dark:text-slate-400 line-clamp-2 leading-relaxed" title="{{ $gift->description }}">
                        {{ $gift->description }}
                    </p>
                </div>

                <!-- Footer của card: Chi phí và Nút quy đổi -->
                <div class="flex items-center justify-between gap-2 pt-3 border-t border-gray-50 dark:border-slate-800/60 mt-3.5">
                    <div class="flex flex-col">
                        <span class="text-[8px] uppercase font-bold text-gray-400 tracking-wider block">{{ __('Phí quy đổi') }}</span>
                        <div class="flex items-baseline gap-1">
                            <span class="text-xs sm:text-sm font-extrabold text-shopee dark:text-shopee-light font-mono">{{ number_format($gift->price) }}đ</span>
                            <span class="text-[9px] text-gray-400">/ {{ __('Kho') }}: <strong class="{{ $gift->stock <= 0 ? 'text-red-500 font-bold' : 'text-gray-600 dark:text-gray-300' }}">{{ $gift->stock }}</strong></span>
                        </div>
                    </div>
                    
                    <!-- Nút Đổi quà dựa trên số dư khả dụng và tồn kho -->
                    <div class="w-28 sm:w-32">
                        @if($gift->stock <= 0)
                            <button disabled class="w-full py-2 bg-gray-150 dark:bg-slate-800 text-gray-400 dark:text-slate-500 font-bold rounded-xl text-[10px] cursor-not-allowed border border-gray-200 dark:border-slate-700/50 flex items-center justify-center gap-1">
                                <i data-lucide="slash" class="w-3 h-3"></i>
                                {{ __('Hết hàng') }}
                            </button>
                        @elseif(auth()->user()->balance < $gift->price)
                            <button disabled class="w-full py-2 bg-gray-50 dark:bg-slate-800/40 text-gray-400 dark:text-slate-550 font-bold rounded-xl text-[10px] cursor-not-allowed border border-gray-100 dark:border-slate-800/60 flex items-center justify-center gap-0.5" title="{{ __('Số dư của bạn không đủ để đổi phần quà này') }}">
                                <i data-lucide="alert-circle" class="w-3 h-3 shrink-0"></i>
                                <span>{{ __('Thiếu') }} {{ number_format($gift->price - auth()->user()->balance) }}đ</span>
                            </button>
                        @else
                            <button @click="openExchangeModal({{ json_encode($gift) }})" class="w-full py-2 bg-gradient-to-r from-shopee to-shopee-light hover:brightness-110 text-white font-bold rounded-xl text-[10px] transition-all shadow-md shadow-shopee/10 flex items-center justify-center gap-1 active:scale-95">
                                <i data-lucide="sparkles" class="w-3 h-3"></i>
                                {{ __('Đổi Ngay') }}
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @empty
        <!-- Trạng thái không tìm thấy quà tặng phù hợp bộ lọc -->
        <div class="lg:col-span-2 bg-white dark:bg-slate-900 border border-gray-100 dark:border-slate-800/80 p-10 text-center rounded-2xl shadow-sm">
            <div class="w-12 h-12 rounded-2xl bg-gray-50 dark:bg-slate-800/60 text-gray-300 dark:text-slate-600 flex items-center justify-center mx-auto mb-3">
                <i data-lucide="package-search" class="w-6 h-6"></i>
            </div>
            <h3 class="text-sm font-bold text-gray-750 dark:text-slate-300 mb-1">{{ __('Không tìm thấy quà tặng') }}</h3>
            <p class="text-xs text-gray-400">{{ __('Vui lòng thử tìm kiếm với từ khóa khác hoặc xóa bộ lọc để xem danh sách đầy đủ.') }}</p>
        </div>
    @endforelse
</div>

<!-- Phân trang danh sách quà tặng -->
@if($gifts->hasPages())
    <div class="pt-4">
        {{ $gifts->links() }}
    </div>
@endif
