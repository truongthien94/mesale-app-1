@php
    /**
     * Component Toast sử dụng thư viện iziToast
     * Load đầy đủ CSS/JS từ thư mục vendor.
     * Cấu hình chuyển động, thanh tiến trình tương thích với từng loại trạng thái thông báo.
     */
    $toastPosition = \App\Models\Setting::getVal('toast_position', 'top-right');
    $toastDuration = (int)\App\Models\Setting::getVal('toast_duration', '4000');

    // Ánh xạ vị trí tùy chọn sang cấu hình của iziToast
    $positionMap = [
        'top-center'    => 'topCenter',
        'top-right'     => 'topRight',
        'top-left'      => 'topLeft',
        'bottom-right'  => 'bottomRight',
        'bottom-left'   => 'bottomLeft',
        'bottom-center' => 'bottomCenter',
    ];
    $iziPosition = $positionMap[$toastPosition] ?? 'topRight';
@endphp

{{-- Import tài nguyên CSS và JS của iziToast từ thư mục vendor --}}
<link rel="stylesheet" href="{{ asset('vendor/izitoast/iziToast.min.css') }}">
<script src="{{ asset('vendor/izitoast/iziToast.min.js') }}"></script>

<script>
(function() {
    const POSITION = '{{ $iziPosition }}';
    const DURATION = {{ $toastDuration }};

    /**
     * Đăng ký lắng nghe sự kiện 'toast' toàn cục.
     * Tự động xác định màu thanh tiến trình (progress bar color) cho từng loại thông báo.
     */
    window.addEventListener('toast', function(e) {
        const methodMap = {
            success: 'success',
            error: 'error',
            warning: 'warning',
            info: 'info'
        };
        const method = methodMap[e.detail.type] || 'info';
        iziToast[method]({
            message: e.detail.text,
            position: POSITION,
            timeout: DURATION,
            transitionIn: 'fadeInDown',
            transitionOut: 'fadeOutUp',
            close: true,
            progressBar: true,
            progressBarColor: method === 'success' ? '#22c55e'
                            : method === 'error'   ? '#ef4444'
                            : method === 'warning' ? '#f59e0b'
                            : '#3b82f6',
        });
    });
})();
</script>
