@extends('layouts.app')

@section('title', __('Sản Phẩm Đã Lưu - Mua Sau') . ' - ' . $siteName)

@section('content')
<div class="px-4 mx-auto max-w-7xl sm:px-6 lg:px-8 py-6 sm:py-10" x-data="{ 
    openDeleteModal: false, 
    deleteActionUrl: '',
    copyToClipboard(text) {
        navigator.clipboard.writeText(text).then(() => {
            window.dispatchEvent(new CustomEvent('toast', {
                detail: {
                    text: '{{ __('Đã sao chép liên kết mua hàng!') }}',
                    type: 'success'
                }
            }));
        }).catch(err => {
            console.error('Không thể sao chép liên kết: ', err);
            window.dispatchEvent(new CustomEvent('toast', {
                detail: {
                    text: '{{ __('Lỗi khi sao chép liên kết.') }}',
                    type: 'error'
                }
            }));
        });
    }
}">
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
        
        <!-- Sidebar điều hướng của thành viên -->
        <div class="hidden lg:block lg:col-span-3">
            @include('dashboard.sidebar')
        </div>

        <!-- Nội dung chính -->
        <div class="lg:col-span-9 space-y-6">
            {{-- Nhắc nhở thành viên bổ sung email để bảo vệ tài khoản --}}
            @include('components.email_update_notice')

            <!-- Banner Sản Phẩm Đã Lưu -->
            <div class="relative overflow-hidden p-5 sm:p-7 bg-gradient-to-r from-[#FFF4EC] via-[#FFF9F5] to-white dark:from-slate-900/40 dark:to-slate-900/20 rounded-2xl border border-orange-50 dark:border-slate-800 shadow-[0_4px_20px_-4px_rgba(0,0,0,0.05)] transition-all duration-300">
                <div class="flex items-start gap-4 max-w-[70%] sm:max-w-[75%] relative z-10">
                    <div class="w-12 h-12 flex items-center justify-center bg-[#FFEFEB] dark:bg-orange-950/30 dark:border dark:border-orange-100/10 text-shopee rounded-2xl shrink-0 shadow-sm">
                        <i data-lucide="bookmark" class="w-6 h-6"></i>
                    </div>
                    <div>
                        <h1 class="text-base sm:text-lg font-black text-gray-900 dark:text-white uppercase tracking-tight">
                            {{ __('Sản Phẩm Đã Lưu') }}
                        </h1>
                        <p class="text-[10px] sm:text-xs text-gray-500 dark:text-slate-400 mt-1.5 leading-relaxed">
                            {{ __('Danh sách sản phẩm bạn đã lấy link hoàn tiền và lưu lại để mua sau') }}
                        </p>
                    </div>
                </div>
                
                <!-- Hình ảnh trang trí góc phải banner -->
                <div class="absolute right-0 top-0 bottom-0 w-32 flex items-center justify-end pointer-events-none pr-2 sm:pr-6 z-0">
                    <img src="{{ asset('assets/images/shopee_bag_3d.png') }}" 
                         class="h-20 sm:h-24 w-auto object-contain select-none drop-shadow-md" 
                         alt="Saved Products">
                </div>
            </div>

            <!-- Thông báo thành công / thất bại nhanh -->
            @if(session('success'))
            <div class="p-4 bg-green-50 dark:bg-green-950/20 border border-green-200 dark:border-green-900/30 text-green-800 dark:text-green-300 rounded-2xl flex items-center gap-3 text-xs font-semibold shadow-[0_4px_20px_-4px_rgba(0,0,0,0.05)]">
                <i data-lucide="check-circle" class="w-4 h-4 shrink-0"></i>
                {{ session('success') }}
            </div>
            @endif

            @if($savedProducts->isEmpty())
                <!-- Trạng thái trống (Empty State) -->
                <div class="bg-white dark:bg-slate-900 rounded-2xl p-12 text-center border border-gray-50 dark:border-slate-800 shadow-[0_4px_20px_-4px_rgba(0,0,0,0.05)] space-y-4">
                    <div class="w-16 h-16 bg-[#FFEFEB] dark:bg-orange-950/30 dark:border dark:border-orange-100/10 text-shopee rounded-2xl flex items-center justify-center mx-auto shadow-sm">
                        <i data-lucide="bookmark-x" class="w-8 h-8"></i>
                    </div>
                    <div class="space-y-1 max-w-sm mx-auto">
                        <h3 class="text-base font-bold text-gray-700 dark:text-gray-300">{{ __('Không có sản phẩm nào') }}</h3>
                        <p class="text-xs text-gray-400 dark:text-slate-500 leading-relaxed mt-2">
                            {{ __('Bạn chưa lưu sản phẩm nào để mua sau. Hãy dán link Shopee ở trang chủ và chọn lưu sản phẩm nhé!') }}
                        </p>
                    </div>
                    <div class="pt-2">
                        <a href="{{ route('home') }}" class="inline-flex items-center gap-2 px-5 py-2.5 bg-gradient-to-r from-shopee to-shopee-light hover:brightness-110 text-white text-xs font-bold rounded-xl transition-all shadow-[0_4px_20px_-4px_rgba(0,0,0,0.05)] active:scale-[0.98]">
                            <i data-lucide="plus-circle" class="w-4 h-4"></i>
                            <span>{{ __('Dán Link Mua Hàng Ngay') }}</span>
                        </a>
                    </div>
                </div>
            @else
                <!-- Danh sách sản phẩm: dạng list (ngang) trên mobile, lưới thẻ trên tablet/desktop -->
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3 sm:gap-6">
                    @foreach($savedProducts as $product)
                        @php $platform = $product->platform ?? 'shopee'; $isTiktok = $platform === 'tiktok'; $isLazada = $platform === 'lazada'; @endphp
                        <div class="bg-white dark:bg-slate-900 rounded-2xl sm:rounded-2xl border border-gray-50 dark:border-slate-800 overflow-hidden shadow-[0_4px_20px_-4px_rgba(0,0,0,0.05)] flex flex-row sm:flex-col group transition-all duration-300 hover:shadow-md hover:border-orange-100 dark:hover:border-orange-950/40">

                            <!-- Ảnh sản phẩm (hình vuông, nhỏ gọn trên mobile) -->
                            <div class="relative shrink-0 w-32 aspect-square self-center m-3 rounded-xl sm:m-0 sm:rounded-none sm:self-auto sm:w-full bg-gray-50 dark:bg-slate-950/40 overflow-hidden border border-gray-100 sm:border-0 sm:border-b sm:border-gray-50 dark:border-slate-800">
                                @if($product->image)
                                    <img src="{{ $product->image }}" alt="" class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-105" onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">
                                    <div style="display:none" class="w-full h-full flex items-center justify-center text-gray-300">
                                        <i data-lucide="image" class="w-8 h-8 sm:w-12 sm:h-12"></i>
                                    </div>
                                @else
                                    <div class="w-full h-full flex items-center justify-center text-gray-300">
                                        <i data-lucide="image" class="w-8 h-8 sm:w-12 sm:h-12"></i>
                                    </div>
                                @endif

                                <!-- Badge Nền tảng -->
                                <div class="absolute top-1.5 left-1.5 sm:top-3 sm:left-3 z-10">
                                    @if($isTiktok)
                                        <span class="flex items-center gap-1 px-1.5 sm:px-2.5 py-0.5 sm:py-1 rounded-lg sm:rounded-xl text-[8px] sm:text-[9px] font-extrabold bg-black text-white border border-gray-950 dark:border-slate-800 shadow-sm select-none">
                                            <i data-lucide="shopping-cart" class="w-2.5 h-2.5 sm:w-3 sm:h-3 text-white"></i> {{ \App\Models\Setting::getVal('tiktok_platform_name', 'TikTok Shop') }}
                                        </span>
                                    @elseif($isLazada)
                                        <span class="flex items-center gap-1 px-1.5 sm:px-2.5 py-0.5 sm:py-1 rounded-lg sm:rounded-xl text-[8px] sm:text-[9px] font-extrabold bg-[#0f146d] text-white border border-[#0f146d] shadow-sm select-none">
                                            <i data-lucide="shopping-bag" class="w-2.5 h-2.5 sm:w-3 sm:h-3 text-white"></i> {{ \App\Models\Setting::getVal('lazada_platform_name', 'Lazada') }}
                                        </span>
                                    @else
                                        <span class="flex items-center gap-1 px-1.5 sm:px-2.5 py-0.5 sm:py-1 rounded-lg sm:rounded-xl text-[8px] sm:text-[9px] font-extrabold bg-[#ff5722] text-white border border-[#ff5722] shadow-sm select-none">
                                            <i data-lucide="shopping-bag" class="w-2.5 h-2.5 sm:w-3 sm:h-3 text-white"></i> {{ \App\Models\Setting::getVal('shopee_platform_name', 'Shopee') }}
                                        </span>
                                    @endif
                                </div>

                                <!-- Nút xóa nhanh góc ảnh (chỉ hiện trên tablet/desktop) -->
                                <button type="button"
                                        @click="deleteActionUrl = '{{ route('saved-products.destroy', $product->id) }}'; openDeleteModal = true"
                                        class="hidden sm:flex absolute top-3 right-3 z-10 w-8 h-8 rounded-full bg-white/90 hover:bg-red-50 text-gray-500 hover:text-red-650 items-center justify-center transition-all shadow-sm focus:outline-none"
                                        title="{{ __('Xóa sản phẩm') }}">
                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                </button>
                            </div>

                            <!-- Nội dung thẻ sản phẩm -->
                            <div class="p-3 sm:p-4 flex-grow flex flex-col justify-between gap-2 sm:gap-4 min-w-0">
                                <div class="space-y-1.5 sm:space-y-2">
                                    <!-- Tên sản phẩm + nút xóa (mobile) -->
                                    <div class="flex items-start justify-between gap-2">
                                        <h3 class="text-xs font-bold text-gray-800 dark:text-slate-200 line-clamp-2 leading-snug group-hover:text-shopee transition-colors">
                                            {{ $product->name }}
                                        </h3>
                                        <button type="button"
                                                @click="deleteActionUrl = '{{ route('saved-products.destroy', $product->id) }}'; openDeleteModal = true"
                                                class="sm:hidden shrink-0 -mt-0.5 -mr-1 w-7 h-7 rounded-lg text-gray-400 hover:text-red-650 hover:bg-red-50 dark:hover:bg-red-950/20 flex items-center justify-center transition-all focus:outline-none"
                                                title="{{ __('Xóa sản phẩm') }}">
                                            <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                        </button>
                                    </div>

                                    <!-- Chi tiết Giá & Hoàn tiền -->
                                    <div class="space-y-1 sm:space-y-1.5 pt-0.5 sm:pt-1">
                                        <div class="flex items-center justify-between text-[10px]">
                                            <span class="text-gray-400 font-medium">{{ __('Giá bán:') }}</span>
                                            <span class="font-bold text-gray-800 dark:text-slate-350">
                                                {{ \App\Helpers\CurrencyHelper::format($product->price) }}
                                            </span>
                                        </div>
                                        <div class="flex items-center justify-between text-[10px] bg-orange-50/50 dark:bg-orange-950/10 px-2 py-1.5 sm:p-2 rounded-lg sm:rounded-xl border border-orange-100/30 dark:border-orange-900/10">
                                            <span class="text-orange-650 dark:text-orange-400 font-bold flex items-center gap-1">
                                                <i data-lucide="wallet" class="w-3.5 h-3.5"></i>
                                                {{ __('Hoàn tiền:') }}
                                            </span>
                                            <span class="font-extrabold text-orange-600 dark:text-orange-400">
                                                {{ \App\Helpers\CurrencyHelper::format($product->cashback_amount) }}
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Các Action Button -->
                                <div class="flex gap-2 sm:gap-2.5">
                                    <!-- Nút mua hàng nhận hoàn tiền -->
                                    <a href="{{ $product->affiliate_url }}"
                                       onclick="window.openAffiliateUrl('{{ $product->affiliate_url }}', '{{ $product->platform ?? 'shopee' }}', event)"
                                       class="flex-grow inline-flex items-center justify-center gap-1.5 py-2 px-3 bg-gradient-to-r {{ $isTiktok ? 'from-black to-slate-800 hover:brightness-95' : 'from-shopee to-shopee-light' }} hover:brightness-110 text-white text-[11px] font-bold rounded-xl transition-all shadow-sm active:scale-95">
                                        @if($isTiktok)
                                            <i data-lucide="shopping-cart" class="w-3.5 h-3.5 text-white"></i>
                                        @else
                                            <i data-lucide="shopping-bag" class="w-3.5 h-3.5 text-white"></i>
                                        @endif
                                        <span>{{ __('Mua Ngay') }}</span>
                                    </a>

                                     <!-- Nút sao chép liên kết -->
                                     <button type="button"
                                             @click="copyToClipboard('{{ $product->affiliate_url }}')"
                                             class="w-9 h-9 inline-flex items-center justify-center text-gray-500 hover:text-shopee bg-gray-50 hover:bg-orange-50/50 dark:bg-slate-800 dark:text-slate-400 dark:hover:bg-slate-800/80 border border-gray-100 dark:border-slate-700/80 rounded-xl transition-all shrink-0 active:scale-95"
                                             title="{{ __('Sao chép liên kết mua hàng') }}">
                                         <i data-lucide="copy" class="w-4 h-4"></i>
                                     </button>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Phân trang -->
                <div class="pt-4">
                    {{ $savedProducts->links() }}
                </div>
            @endif

        </div>
    </div>

    <!-- Modal xác nhận xóa sản phẩm đã lưu (Delete Confirmation Modal) -->
    <div x-show="openDeleteModal" 
         @keydown.escape.window="openDeleteModal = false"
         class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4" 
         x-cloak>
        
        <!-- Backdrop mờ ẩn phía sau -->
        <div class="fixed inset-0 bg-black/60 backdrop-blur-sm transition-opacity"
             x-show="openDeleteModal"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             @click="openDeleteModal = false"></div>

        <!-- Hộp nội dung Modal -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl overflow-hidden shadow-[0_4px_20px_-4px_rgba(0,0,0,0.05)] transform transition-all sm:max-w-md w-full z-10 border border-gray-50 dark:border-slate-800"
             x-show="openDeleteModal"
             x-transition:enter="transition ease-out duration-300 transform scale-95 translate-y-4"
             x-transition:enter-end="transform scale-100 translate-y-0"
             x-transition:leave="transition ease-in duration-200 transform scale-100 translate-y-0"
             x-transition:leave-end="transform scale-95 translate-y-4"
             @click.away="openDeleteModal = false">
             
            <!-- Body của Modal -->
            <div class="p-6 text-center space-y-4">
                <!-- Icon Cảnh báo màu đỏ nổi bật -->
                <div class="w-14 h-14 bg-red-50 dark:bg-red-950/20 text-red-600 dark:text-red-400 rounded-2xl flex items-center justify-center mx-auto shadow-inner">
                    <i data-lucide="alert-triangle" class="w-7 h-7"></i>
                </div>
                
                <div class="space-y-1.5">
                    <h3 class="text-sm font-bold text-gray-900 dark:text-white">
                        {{ __('Xác nhận xóa sản phẩm') }}
                    </h3>
                    <p class="text-xs text-gray-400 dark:text-slate-400 leading-relaxed max-w-xs mx-auto">
                        {{ __('Bạn có chắc chắn muốn xóa sản phẩm này khỏi danh sách đã lưu? Hành động này không thể hoàn tác.') }}
                    </p>
                </div>
            </div>

            <!-- Footer của Modal chứa các nút hành động -->
            <div class="px-6 py-4 border-t border-gray-100 dark:border-slate-800/60 bg-gray-50/50 dark:bg-slate-900/50 flex justify-end gap-2.5">
                <button @click="openDeleteModal = false" class="px-4 py-2 text-xs font-semibold text-gray-700 dark:text-slate-350 hover:bg-gray-100 dark:hover:bg-slate-800 rounded-xl transition-all border border-gray-200 dark:border-slate-700">
                    {{ __('Hủy bỏ') }}
                </button>
                <form :action="deleteActionUrl" method="POST" class="inline-block">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="px-4 py-2 text-xs font-bold text-white bg-red-600 hover:bg-red-700 rounded-xl transition-all shadow-md shadow-red-500/10">
                        {{ __('Đồng ý xóa') }}
                    </button>
                </form>
            </div>

        </div>
    </div>
</div>
@endsection
