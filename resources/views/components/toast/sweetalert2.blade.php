@php
    /**
     * Component Toast sử dụng SweetAlert2
     * Tự động kiểm tra và tải thư viện SweetAlert2 nếu trang hiện tại chưa tích hợp sẵn (ví dụ: Frontend).
     * Ánh xạ các cấu hình vị trí và thời gian tương thích với các option cài đặt.
     */
    $toastPosition = \App\Models\Setting::getVal('toast_position', 'top-right');
    $toastDuration = (int)\App\Models\Setting::getVal('toast_duration', '4000');

    // Ánh xạ vị trí tùy chọn sang từ khóa cấu hình của SweetAlert2
    $positionMap = [
        'top-center'    => 'top',
        'top-right'     => 'top-end',
        'top-left'      => 'top-start',
        'bottom-right'  => 'bottom-end',
        'bottom-left'   => 'bottom-start',
        'bottom-center' => 'bottom',
    ];
    $swal2Position = $positionMap[$toastPosition] ?? 'top-end';
@endphp

{{-- Chỉ tải thư viện SweetAlert2 một lần duy nhất nếu chưa tồn tại trong biến Window --}}
@once
<script>
if (typeof Swal === 'undefined') {
    var s = document.createElement('script');
    s.src = '{{ asset('vendor/sweetalert2/sweetalert2.all.min.js') }}';
    document.head.appendChild(s);
}
</script>
@endonce

<script>
/**
 * Lắng nghe sự kiện toàn cục 'toast' và hiển thị dạng Toast thông qua SweetAlert2.
 * Hỗ trợ các trạng thái icon: success, error, warning, info.
 */
window.addEventListener('toast', function(e) {
    const iconMap = { success: 'success', error: 'error', warning: 'warning', info: 'info' };
    Swal.fire({
        toast: true,
        position: '{{ $swal2Position }}',
        icon: iconMap[e.detail.type] || 'info',
        title: e.detail.text,
        showConfirmButton: false,
        timer: {{ $toastDuration }},
        timerProgressBar: true,
        showCloseButton: true,
        customClass: {
            popup: 'swal2-toast-custom',
        }
    });
});
</script>
