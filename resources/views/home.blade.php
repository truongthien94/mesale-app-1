@extends('layouts.app')

{{-- SEO: Title tag tối ưu cho trang chủ --}}
@section('title', $siteName)

{{-- SEO: Giữ mô tả quản trị nếu đủ chi tiết, nếu quá ngắn thì dùng bản chuẩn của trang chủ. --}}
@php
    $homeMetaDescription = mb_strlen(trim((string) $siteDescription)) >= 100
        ? trim((string) $siteDescription)
        : 'Mê Sale giúp tạo link hoàn tiền Shopee và TikTok Shop, săn mã giảm giá và theo dõi giao dịch mua sắm trực tuyến nhanh chóng, minh bạch.';
@endphp
@section('meta_description', $homeMetaDescription)

{{-- SEO: Canonical URL cho trang chủ --}}
@section('canonical_url', url('/'))

{{--
    SEO: JSON-LD Schema cho trang chủ.
    Toàn bộ thực thể (Organization, WebSite, WebPage, BreadcrumbList, FAQPage, HowTo) được dựng
    tập trung tại App\Services\HomeSchemaService dựa trên các block đang bật trong trình dựng trang.
    Cách làm này bảo đảm trang chủ chỉ có DUY NHẤT một khối FAQPage dù Admin thêm nhiều block FAQ
    (Google chỉ chấp nhận một FAQPage cho mỗi URL, khai báo trùng sẽ bị loại bỏ rich snippet).
--}}
@section('seo_schema')
<script type="application/ld+json">
{!! app(\App\Services\HomeSchemaService::class)->build($blocks, $siteName, $homeMetaDescription, $siteLogo ?? null) !!}
</script>
@endsection

@section('content')
<div class="space-y-12 pb-16" x-data="shopeeCashbackHandler({ 
    endpoint: '{{ route('shopee.product') }}', 
    errorMessage: '{{ __('Không thể tải thông tin sản phẩm. Vui lòng kiểm tra lại link.') }}',
    loggedIn: {{ Auth::check() ? 'true' : 'false' }},
    loginUrl: '{{ route('login') }}',
    currency: {
        code: '{{ $currentCurrency->code }}',
        symbol: '{{ $currentCurrency->symbol }}',
        rate: {{ $currentCurrency->exchange_rate }},
        position: '{{ $currentCurrency->symbol_position }}'
    }
})">


    {{-- ===================================================================
         TRÌNH DỰNG TRANG (PAGE BUILDER)
         Render các block homepage theo thứ tự & trạng thái bật/tắt do Admin
         cấu hình tại /admin/appearance. Mỗi block là một partial trong
         resources/views/home/blocks/. Xem AppearanceController::blockDefinitions().
    ==================================================================== --}}
    @foreach($blocks as $block)
        @if($block->enabled && view()->exists('home.blocks.'.$block->type))
            @include('home.blocks.'.$block->type, ['s' => $block->settings ?? []])
        @endif
    @endforeach

</div>

    <!-- 
        POPUP THÔNG BÁO TRANG CHỦ (HOMEPAGE POPUP NOTIFICATION)
        - Tại sao cần dùng block này: Hiển thị thông báo toàn hệ thống (sự kiện, bảo trì, khuyến mãi...) khi người dùng truy cập trang chủ.
        - Tại sao dời ra ngoài div container chính: Tránh bị ảnh hưởng bởi class spacing 'space-y-12' của container trang chủ (lỗi margin-top làm lệch modal và hở header).
        - Tránh làm phiền người dùng: Sử dụng sessionStorage để lưu trạng thái đã đóng. Khi chuyển trang hoặc tải lại trang trong cùng một phiên làm việc, popup sẽ không hiện lại.
        - Hỗ trợ đa ngôn ngữ và định dạng HTML động từ Admin Panel.
    -->
    @if(\App\Models\Setting::getVal('home_popup_status', '0') === '1')
    <div x-data="{
        showPopup: false,
        init() {
            // Kiểm tra trạng thái đã đóng trong Session Storage để tránh hiển thị lại liên tục khi người dùng reload trang
            const isClosed = sessionStorage.getItem('home_popup_closed');
            if (!isClosed) {
                // Sử dụng thời gian trễ nhỏ để tối ưu trải nghiệm người dùng sau khi Hero section đã tải xong
                setTimeout(() => {
                    this.showPopup = true;
                }, 850);
            }
        },
        closePopup() {
            this.showPopup = false;
            // Lưu vào sessionStorage để đánh dấu người dùng đã đóng popup này trong phiên làm việc hiện tại
            sessionStorage.setItem('home_popup_closed', 'true');
        }
    }"
    x-show="showPopup"
    @keydown.escape.window="closePopup()"
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0 scale-90 translate-y-4"
    x-transition:enter-end="opacity-100 scale-100 translate-y-0"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100 scale-100 translate-y-0"
    x-transition:leave-end="opacity-0 scale-90 translate-y-4"
    class="fixed inset-0 z-[999] !mt-0 !space-y-0 flex items-center justify-center p-4 bg-black/60 backdrop-blur-md"
    x-cloak>
        
        <div @click.away="closePopup()"
            class="relative bg-[#FAF6F0] dark:bg-slate-900 rounded-[32px] p-6 sm:p-8 max-w-md w-full shadow-2xl border border-orange-100/30 dark:border-slate-800/80 text-center z-10 transform transition-all select-none"
            x-transition:enter="transition ease-out duration-300 transform"
            x-transition:enter-start="scale-90 translate-y-4"
            x-transition:enter-end="scale-100 translate-y-0"
            x-transition:leave="transition ease-in duration-200 transform"
            x-transition:leave-start="scale-100 translate-y-0"
            x-transition:leave-end="scale-90 translate-y-4">
            
            <!-- Nút đóng nhanh chéo ở góc phải phía trên của modal -->
            <button @click="closePopup()" class="absolute top-5 right-5 p-1.5 rounded-xl text-gray-400 dark:text-slate-500 hover:bg-black/5 dark:hover:bg-white/5 hover:text-gray-650 dark:hover:text-slate-350 transition-all focus:outline-none" type="button">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>

            <!-- Biểu tượng quả chuông thông báo thiết kế nổi bật, đồng bộ với hộp quà điểm danh -->
            <div class="relative w-20 h-20 bg-gradient-to-tr from-shopee via-orange-500 to-amber-500 rounded-full flex items-center justify-center mx-auto mb-5 shadow-lg shadow-orange-500/25">
                <i data-lucide="bell" class="w-9 h-9 text-white animate-pulse"></i>
                <!-- Chấm thông báo đỏ lấp lánh để thu hút sự chú ý -->
                <span class="absolute top-0.5 right-0.5 w-3 h-3 bg-red-500 rounded-full border-2 border-[#FAF6F0] dark:border-slate-900"></span>
            </div>

            <!-- Tiêu đề và nhãn phụ đề -->
            <h3 class="text-xl font-extrabold text-gray-900 dark:text-slate-100 tracking-tight mb-1">
                {{ __('Thông báo hệ thống') }}
            </h3>
            <p class="text-[10px] text-gray-400 dark:text-slate-500 font-black tracking-widest uppercase mb-5">
                {{ __('Cập nhật mới nhất') }}
            </p>

            <!-- Khung hiển thị nội dung chi tiết (thiết kế dạng card lồng nền trắng/slate-800 để nội dung có khung viền rõ ràng và đẹp mắt) -->
            <div class="bg-white dark:bg-slate-800 border border-orange-100/60 dark:border-slate-700/50 rounded-2xl p-5 mb-6 text-left text-xs md:text-sm text-gray-700 dark:text-slate-350 leading-relaxed shadow-sm max-h-[35vh] overflow-y-auto scrollbar-thin prose dark:prose-invert max-w-none">
                {!! \App\Models\Setting::getVal('home_popup_content') !!}
            </div>

            <!-- Nút đóng phong cách cao cấp màu cam thương hiệu giống nút Điểm Danh -->
            <button @click="closePopup()" 
                    class="w-full py-3.5 bg-gradient-to-r from-shopee to-shopee-light hover:brightness-110 active:scale-[0.98] transition-all duration-200 text-white font-extrabold rounded-2xl text-xs tracking-wider uppercase shadow-md shadow-shopee/20 focus:outline-none flex items-center justify-center gap-1.5">
                <i data-lucide="check" class="w-4 h-4"></i>
                {{ __('Tuyệt vời') }}
            </button>
        </div>
    </div>
    @endif
@endsection

@section('scripts')
<script src="{{ asset('js/home.js') }}?v=1.0.8"></script>
@endsection
