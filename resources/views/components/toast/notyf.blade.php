@php
    /**
     * Component Toast sử dụng thư viện Notyf
     * Load CSS/JS từ thư mục vendor.
     * Cấu hình vị trí (x, y) và định nghĩa thêm các type warning/info tùy biến vì mặc định Notyf chỉ có success/error.
     */
    $toastPosition = \App\Models\Setting::getVal('toast_position', 'top-right');
    $toastDuration = (int)\App\Models\Setting::getVal('toast_duration', '4000');

    // Tách cấu hình vị trí thành trục X và Y để tương thích với cấu trúc config của Notyf
    $xMap = [
        'top-center' => 'center', 'top-right' => 'right', 'top-left' => 'left',
        'bottom-right' => 'right', 'bottom-left' => 'left', 'bottom-center' => 'center',
    ];
    $yMap = [
        'top-center' => 'top', 'top-right' => 'top', 'top-left' => 'top',
        'bottom-right' => 'bottom', 'bottom-left' => 'bottom', 'bottom-center' => 'bottom',
    ];
    $x = $xMap[$toastPosition] ?? 'right';
    $y = $yMap[$toastPosition] ?? 'top';
@endphp

{{-- Nạp tài nguyên thư viện Notyf từ vendor --}}
<link rel="stylesheet" href="{{ asset('vendor/notyf/notyf.min.css') }}">
<script src="{{ asset('vendor/notyf/notyf.min.js') }}"></script>

<script>
(function() {
    // Khởi tạo instance Notyf với cấu hình ban đầu
    const notyf = new Notyf({
        duration: {{ $toastDuration }},
        position: { x: '{{ $x }}', y: '{{ $y }}' },
        ripple: true,
        dismissible: true,
        types: [
            {
                type: 'warning',
                background: '#f59e0b',
                icon: {
                    className: 'notyf__icon--warning',
                    tagName: 'span',
                    text: '⚠'
                }
            },
            {
                type: 'info',
                background: '#3b82f6',
                icon: {
                    className: 'notyf__icon--info',
                    tagName: 'span',
                    text: 'ℹ'
                }
            }
        ]
    });

    /**
     * Bắt sự kiện 'toast' toàn cục và điều hướng đến hàm tương ứng của Notyf.
     * Sử dụng các hàm mặc định (success, error) và gọi .open cho các kiểu tùy biến (warning, info).
     */
    window.addEventListener('toast', function(e) {
        const type = e.detail.type;
        const msg = e.detail.text;
        if (type === 'success') notyf.success(msg);
        else if (type === 'error') notyf.error(msg);
        else notyf.open({ type: type, message: msg });
    });
})();
</script>
