@php
    $hpSettings = $s;

    // Nội dung dự phòng khi Admin để trống, tránh render thẻ tiêu đề/mô tả rỗng
    $stepDefaults = [
        1 => ['icon' => 'copy',         'title' => __('Sao chép link sản phẩm'),        'desc' => __('Mở ứng dụng hoặc trang web Shopee, tìm sản phẩm bạn yêu thích rồi sao chép đường dẫn (link) sản phẩm đó.')],
        2 => ['icon' => 'search',       'title' => __('Dán link & Lấy link hoàn tiền'), 'desc' => __('Dán link vừa sao chép vào ô tìm kiếm tại trang chủ, hệ thống sẽ phân tích và tạo ngay link hoàn tiền cho bạn.')],
        3 => ['icon' => 'check-square', 'title' => __('Mua hàng và nhận tiền hoàn'),    'desc' => __('Hoàn tất đặt hàng qua link hoàn tiền, đơn hàng được ghi nhận tự động và tiền hoàn sẽ cộng vào ví của bạn.')],
    ];

    $stepsBadge     = trim($hpSettings['hp_steps_badge'] ?? '') ?: __('Hướng dẫn nhanh');
    $stepsTitle     = trim($hpSettings['hp_steps_title'] ?? '');
    $stepsSubtitle  = trim($hpSettings['hp_steps_subtitle'] ?? '');
    $stepsHeadingId = 'steps-' . substr(md5($stepsTitle), 0, 8);
@endphp
<section @if($stepsTitle !== '') aria-labelledby="{{ $stepsHeadingId }}" @endif class="px-4 mx-auto max-w-7xl sm:px-6 lg:px-8 pt-16 md:pt-24 border-t border-gray-100/60 dark:border-slate-800/40">
    <div class="bg-gradient-to-br from-white/90 to-orange-50/45 dark:from-slate-900/60 dark:to-slate-900/20 p-8 md:p-12 rounded-[32px] border border-orange-100/50 dark:border-slate-800/60 shadow-sm">
        <div class="text-center max-w-2xl mx-auto mb-12">
            <span class="inline-flex items-center gap-1.5 px-3.5 py-1 rounded-full text-xs font-bold bg-orange-100 text-shopee dark:bg-orange-950/30 dark:text-orange-400">
                <i data-lucide="help-circle" class="w-3.5 h-3.5"></i>
                {{ $stepsBadge }}
            </span>
            @if($stepsTitle !== '')
            <h2 id="{{ $stepsHeadingId }}" class="text-2xl md:text-3xl font-extrabold text-gray-900 dark:text-white mt-3 leading-tight">
                {{ $stepsTitle }}
            </h2>
            @endif
            @if($stepsSubtitle !== '')
            <p class="text-sm text-gray-500 dark:text-slate-400 mt-2">
                {{ $stepsSubtitle }}
            </p>
            @endif
        </div>

        {{--
            Dùng danh sách có thứ tự <ol> thay cho các thẻ <div> rời rạc: đây là quy trình gồm các
            bước nối tiếp nhau, cấu trúc này giúp công cụ tìm kiếm và trình đọc màn hình hiểu đúng
            trình tự, đồng thời khớp với dữ liệu có cấu trúc HowTo khai báo cho trang chủ.
        --}}
        <ol class="grid grid-cols-1 md:grid-cols-3 gap-8 relative list-none">
            @for($si = 1; $si <= 3; $si++)
            @php
                $sd = $stepDefaults[$si];
                $sTitle = trim($hpSettings["hp_step_{$si}_title"] ?? '') ?: $sd['title'];
                $sDesc  = trim($hpSettings["hp_step_{$si}_desc"] ?? '')  ?: $sd['desc'];
                $sIcon  = trim($hpSettings["hp_step_{$si}_icon"] ?? '')  ?: $sd['icon'];
            @endphp
            {{-- id="buoc-N" là neo được tham chiếu trong dữ liệu có cấu trúc HowTo của trang chủ --}}
            <li id="buoc-{{ $si }}" class="relative bg-white dark:bg-slate-900 p-6 rounded-2xl shadow-md dark:shadow-none border border-gray-150 dark:border-slate-800/80 flex flex-col items-center text-center space-y-4 hover:shadow-lg transition-all duration-300">
                <div class="absolute -top-4 left-6 w-8 h-8 rounded-full bg-shopee text-white font-bold flex items-center justify-center shadow-lg shadow-shopee/25" aria-hidden="true">{{ $si }}</div>
                <div class="w-14 h-14 rounded-full bg-orange-50 dark:bg-orange-950/30 flex items-center justify-center text-shopee dark:text-orange-400 mt-2">
                    <i data-lucide="{{ $sIcon }}" class="w-7 h-7"></i>
                </div>
                <h3 class="font-bold text-gray-900 dark:text-white text-base">{{ $sTitle }}</h3>
                <p class="text-xs text-gray-500 dark:text-slate-400 leading-relaxed">
                    {{ $sDesc }}
                </p>
            </li>
            @endfor
        </ol>
    </div>
</section>
