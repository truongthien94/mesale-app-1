@php
    /**
     * Component Toast sử dụng thư viện Notiflix
     * Tự động khởi tạo Notiflix.Notify với các cấu hình vị trí, thời gian và hướng chuyển động (animation) tương ứng.
     */
    $toastPosition = \App\Models\Setting::getVal('toast_position', 'top-right');
    $toastDuration = (int)\App\Models\Setting::getVal('toast_duration', '4000');

    // Ánh xạ vị trí tùy chọn sang cấu trúc định dạng vị trí của Notiflix
    $positionMap = [
        'top-center'    => 'center top 10px',
        'top-right'     => 'right top 10px',
        'top-left'      => 'left top 10px',
        'bottom-right'  => 'right bottom 10px',
        'bottom-left'   => 'left bottom 10px',
        'bottom-center' => 'center bottom 10px',
    ];
    $notiflixPos = $positionMap[$toastPosition] ?? 'right top 10px';

    // Ánh xạ hướng trượt xuất hiện tùy thuộc vào vị trí hiển thị
    $animationMap = [
        'top-center' => 'from-top', 'top-right' => 'from-right', 'top-left' => 'from-left',
        'bottom-right' => 'from-right', 'bottom-left' => 'from-left', 'bottom-center' => 'from-bottom',
    ];
    $animation = $animationMap[$toastPosition] ?? 'from-right';
@endphp

{{-- Nạp file AIO (All In One) của Notiflix từ thư mục vendor --}}
<script src="{{ asset('vendor/notiflix/notiflix-aio.min.js') }}"></script>

<script>
// Khởi tạo các tùy chọn ban đầu cho module Notify của Notiflix
Notiflix.Notify.init({
    width: '340px',
    position: '{{ $notiflixPos }}',
    timeout: {{ $toastDuration }},
    cssAnimationStyle: '{{ $animation }}',
    cssAnimationDuration: 300,
    closeButton: true,
    useIcon: true,
    useFontAwesome: false,
    fontAwesomeIconStyle: 'basic',
    borderRadius: '12px',
    rtl: false,
    pauseOnHover: true,
});

/**
 * Đăng ký lắng nghe sự kiện 'toast' toàn cục.
 * Gọi đến các phương thức tương ứng của Notiflix: success, failure, warning, info.
 */
window.addEventListener('toast', function(e) {
    const type = e.detail.type;
    const msg = e.detail.text;
    if (type === 'success')      Notiflix.Notify.success(msg);
    else if (type === 'error')   Notiflix.Notify.failure(msg);
    else if (type === 'warning') Notiflix.Notify.warning(msg);
    else                         Notiflix.Notify.info(msg);
});
</script>
