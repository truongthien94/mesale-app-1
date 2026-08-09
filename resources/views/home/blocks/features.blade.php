@php
    $hpSettings = $s;

    // Nội dung dự phòng khi Admin để trống ô nhập — tránh hiển thị thẻ tiêu đề/mô tả rỗng
    // (thẻ rỗng vừa xấu về giao diện vừa bị công cụ tìm kiếm đánh giá là nội dung mỏng).
    $featureDefaults = [
        1 => ['icon' => 'percent',    'color' => 'orange', 'title' => __('Tỷ lệ hoàn tiền cao'),  'desc' => __('Nhận lại lên đến :rate% tổng số tiền hoa hồng mà sàn chi trả cho mỗi đơn hàng tiếp thị liên kết thành công.', ['rate' => $cashbackRate ?? 80])],
        2 => ['icon' => 'git-branch', 'color' => 'green',  'title' => __('Hệ thống 2 tầng MLM'),  'desc' => __('Giới thiệu bạn bè đăng ký để nhận thêm hoa hồng từ F1 và F2 trên mỗi đơn hoàn tiền của họ, tạo nguồn thu nhập thụ động.')],
        3 => ['icon' => 'banknote',   'color' => 'blue',   'title' => __('Thanh toán linh hoạt'), 'desc' => __('Hỗ trợ rút tiền qua mã QR ngân hàng (VietQR) hoặc ví điện tử với hạn mức tối thiểu thấp, xử lý nhanh gọn.')],
        4 => ['icon' => 'history',    'color' => 'purple', 'title' => __('Ghi nhận đơn tự động'), 'desc' => __('Đơn hàng được đồng bộ tự động qua API đối soát và hiển thị ngay trong bảng lịch sử ví của bạn.')],
    ];

    // Bảng lớp CSS tĩnh theo màu.
    // Vì sao không ghép chuỗi kiểu "bg-{$color}-50": các lớp ghép động không tồn tại nguyên vẹn
    // trong mã nguồn nên sẽ bị trình biên dịch Tailwind loại bỏ khi dự án chuyển sang build CSS
    // tĩnh (thay cho bản CDN hiện tại), khiến toàn bộ màu icon biến mất. Khai báo tường minh
    // giúp giao diện luôn đúng màu ở mọi cách đóng gói Tailwind.
    $featureColorClasses = [
        'orange' => 'bg-orange-50 dark:bg-orange-950/30 text-orange-600 dark:text-orange-400',
        'green'  => 'bg-green-50 dark:bg-green-950/30 text-green-600 dark:text-green-400',
        'blue'   => 'bg-blue-50 dark:bg-blue-950/30 text-blue-600 dark:text-blue-400',
        'purple' => 'bg-purple-50 dark:bg-purple-950/30 text-purple-600 dark:text-purple-400',
        'red'    => 'bg-red-50 dark:bg-red-950/30 text-red-600 dark:text-red-400',
        'yellow' => 'bg-yellow-50 dark:bg-yellow-950/30 text-yellow-600 dark:text-yellow-400',
        'pink'   => 'bg-pink-50 dark:bg-pink-950/30 text-pink-600 dark:text-pink-400',
    ];

    $featuresBadge     = trim($hpSettings['hp_features_badge'] ?? '') ?: __('Ưu điểm vượt trội');
    $featuresTitle     = trim($hpSettings['hp_features_title'] ?? '');
    $featuresSubtitle  = trim($hpSettings['hp_features_subtitle'] ?? '');
    $featuresHeadingId = 'features-' . substr(md5($featuresTitle), 0, 8);
@endphp
<section @if($featuresTitle !== '') aria-labelledby="{{ $featuresHeadingId }}" @endif class="px-4 mx-auto max-w-7xl sm:px-6 lg:px-8 pt-16 md:pt-24 border-t border-gray-100/60 dark:border-slate-800/40">
    <div class="bg-gradient-to-br from-gray-50/80 to-transparent dark:from-slate-900/40 dark:to-transparent p-8 md:p-12 rounded-[32px] border border-gray-100/70 dark:border-slate-800/60">
        <div class="text-center max-w-2xl mx-auto mb-12">
            <span class="inline-flex items-center gap-1.5 px-3.5 py-1 rounded-full text-xs font-bold bg-green-50 text-green-700 border border-green-200 dark:bg-green-950/30 dark:text-green-400 dark:border-green-900/30">
                <i data-lucide="shield-check" class="w-3.5 h-3.5"></i>
                {{ $featuresBadge }}
            </span>
            @if($featuresTitle !== '')
            <h2 id="{{ $featuresHeadingId }}" class="text-2xl md:text-3xl font-extrabold text-gray-900 dark:text-white mt-3 leading-tight">
                {{ $featuresTitle }}
            </h2>
            @endif
            @if($featuresSubtitle !== '')
            <p class="text-sm text-gray-500 dark:text-slate-400 mt-2">
                {{ $featuresSubtitle }}
            </p>
            @endif
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            @for($fi = 1; $fi <= 4; $fi++)
            @php
                $fd = $featureDefaults[$fi];
                // Ưu tiên nội dung Admin cấu hình, nếu để trống thì dùng nội dung mặc định
                $fTitle = trim($hpSettings["hp_feature_{$fi}_title"] ?? '') ?: $fd['title'];
                $fDesc  = trim($hpSettings["hp_feature_{$fi}_desc"] ?? '')  ?: $fd['desc'];
                $fIcon  = trim($hpSettings["hp_feature_{$fi}_icon"] ?? '')  ?: $fd['icon'];
                $fColor = trim($hpSettings["hp_feature_{$fi}_color"] ?? '') ?: $fd['color'];
                $fClass = $featureColorClasses[$fColor] ?? $featureColorClasses['orange'];
            @endphp
            <div class="bg-white dark:bg-slate-900 p-6 rounded-3xl shadow-md dark:shadow-none border border-gray-150 dark:border-slate-800/80 space-y-3 hover:shadow-lg transition-all duration-300">
                <div class="w-10 h-10 rounded-xl {{ $fClass }} flex items-center justify-center">
                    <i data-lucide="{{ $fIcon }}" class="w-5 h-5"></i>
                </div>
                <h3 class="font-bold text-gray-900 dark:text-white text-base">{{ $fTitle }}</h3>
                <p class="text-xs text-gray-500 dark:text-slate-400 leading-relaxed">
                    {{ $fDesc }}
                </p>
            </div>
            @endfor
        </div>
    </div>
</section>
