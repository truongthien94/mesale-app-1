@php
    /**
     * Component Toast sử dụng thư viện Toastr.js
     * Yêu cầu jQuery đi kèm để hoạt động. Load cả hai từ thư mục vendor.
     * Cấu hình vị trí hiển thị và thời gian đóng thông báo.
     */
    $toastPosition = \App\Models\Setting::getVal('toast_position', 'top-right');
    $toastDuration = (int)\App\Models\Setting::getVal('toast_duration', '4000');

    // Ánh xạ vị trí tùy chọn sang class cấu hình của Toastr
    $positionMap = [
        'top-center'    => 'toast-top-center',
        'top-right'     => 'toast-top-right',
        'top-left'      => 'toast-top-left',
        'bottom-right'  => 'toast-bottom-right',
        'bottom-left'   => 'toast-bottom-left',
        'bottom-center' => 'toast-bottom-center',
    ];
    $toastrPos = $positionMap[$toastPosition] ?? 'toast-top-right';
@endphp

{{-- Nạp tài nguyên Toastr CSS, jQuery và Toastr JS từ vendor --}}
<link rel="stylesheet" href="{{ asset('vendor/toastr/toastr.min.css') }}">
<script src="{{ asset('vendor/jquery/jquery.min.js') }}"></script>
<script src="{{ asset('vendor/toastr/toastr.min.js') }}"></script>

<script>
// Thiết lập các thuộc tính mặc định cho Toastr.js
toastr.options = {
    positionClass: '{{ $toastrPos }}',
    timeOut: {{ $toastDuration }},
    extendedTimeOut: 1000,
    closeButton: true,
    progressBar: true,
    newestOnTop: true,
    preventDuplicates: false,
    showDuration: 250,
    hideDuration: 200,
    showEasing: 'swing',
    hideEasing: 'linear',
    showMethod: 'fadeIn',
    hideMethod: 'fadeOut',
};

/**
 * Đăng ký lắng nghe sự kiện 'toast' toàn cục.
 * Gọi đến các phương thức hiển thị tương ứng của Toastr: success, error, warning, info.
 */
window.addEventListener('toast', function(e) {
    const type = e.detail.type;
    const msg = e.detail.text;
    if (type === 'success')      toastr.success(msg);
    else if (type === 'error')   toastr.error(msg);
    else if (type === 'warning') toastr.warning(msg);
    else                         toastr.info(msg);
});
</script>
