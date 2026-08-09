@php
    /**
     * Bộ điều hướng hệ thống thông báo Toast (Toast Notification Manager)
     * Đọc cấu hình 'toast_style' từ cơ sở dữ liệu để nhúng file cấu hình toast tương ứng.
     * Mặc định sử dụng kiểu hiển thị Alpine.js Glassmorphism nếu không được thiết lập.
     */
    $toastStyle = \App\Models\Setting::getVal('toast_style', 'alpine');
@endphp

@if($toastStyle === 'alpine')
    @include('components.toast.alpine')
@elseif($toastStyle === 'sweetalert2')
    @include('components.toast.sweetalert2')
@elseif($toastStyle === 'izitoast')
    @include('components.toast.izitoast')
@elseif($toastStyle === 'notyf')
    @include('components.toast.notyf')
@elseif($toastStyle === 'notiflix')
    @include('components.toast.notiflix')
@elseif($toastStyle === 'toastr')
    @include('components.toast.toastr')
@else
    @include('components.toast.alpine')
@endif
