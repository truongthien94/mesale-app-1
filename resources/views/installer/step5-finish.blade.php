{{--
    Bước 5: Hoàn tất cài đặt - Xác nhận & Khóa installer
--}}
@extends('installer.layout')
@section('title', 'Hoàn Tất Cài Đặt')
@php $currentStep = 5; @endphp

@section('content')
    <div class="finish-success">
        <div class="finish-icon">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
        </div>
        <h2 class="finish-title">Sẵn Sàng Hoàn Tất!</h2>
        <p class="finish-desc">
            Hệ thống đã được thiết lập thành công. Nhấn nút bên dưới để khóa trình cài đặt và chuyển sang trang chủ.
        </p>
    </div>

    {{-- Thông tin đăng nhập quản trị --}}
    <div class="info-box" style="margin-bottom: 20px;">
        <div class="info-box-title" style="color: #3B82F6;">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 013 3m3 0a6 6 0 01-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1121.75 8.25z" />
            </svg>
            Thông tin đăng nhập quản trị
        </div>
        <p class="finish-desc" style="font-size: 13px; margin-bottom: 15px;">
            Vui lòng lưu lại thông tin đăng nhập để quản trị website sau khi hoàn tất cài đặt:
        </p>

        <div style="background: rgba(0, 0, 0, 0.2); border: 1px solid rgba(255, 255, 255, 0.1); padding: 15px; border-radius: 8px; font-family: monospace; font-size: 13px; line-height: 1.6; position: relative;">
            <div id="adminLoginDetails" style="color: #E5E7EB; white-space: pre-wrap;">Link Website: {{ url('/') }}
Email đăng nhập: {{ $adminEmail }}
Mật khẩu đăng nhập: {{ $adminPassword }}

Hướng dẫn sử dụng: https://hdsdcashback.cmsnt.co/</div>
            <button type="button" onclick="copyText('adminLoginDetails')" class="btn-copy" style="position: absolute; top: 12px; right: 12px; background: #3B82F6; border: none; color: white; padding: 6px 12px; border-radius: 6px; cursor: pointer; font-size: 11px; font-family: sans-serif; transition: background 0.2s;">
                Sao chép nhanh
            </button>
        </div>
    </div>

    {{-- Tóm tắt cấu hình --}}
    <div class="info-box">
        <div class="info-box-title">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" /></svg>
            Tóm tắt hệ thống
        </div>
        <div class="info-row">
            <span class="label">Phiên bản PHP</span>
            <span class="value">{{ PHP_VERSION }}</span>
        </div>
        <div class="info-row">
            <span class="label">Laravel Framework</span>
            <span class="value">{{ app()->version() }}</span>
        </div>
        <div class="info-row">
            <span class="label">Địa chỉ website</span>
            <span class="value">{{ url('/') }}</span>
        </div>
        <div class="info-row">
            <span class="label">Database</span>
            <span class="value">{{ config('database.connections.mariadb.database', 'N/A') }}</span>
        </div>
    </div>

    <div class="warning-notice">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
        </svg>
        <p>
            <strong>Quan trọng:</strong> Sau khi nhấn "Hoàn Tất", trình cài đặt sẽ bị khóa vĩnh viễn. Hệ thống sẽ chuyển sang chế độ <strong>Production</strong> (tắt Debug, tắt hiển thị lỗi). Nếu cần cài đặt lại, hãy xóa file <code style="color: var(--primary-light);">storage/installed.lock</code>.
        </p>
    </div>

    {{-- Hướng dẫn cấu hình Cron Job chạy ngầm --}}
    @php
        // Lấy 2 ký tự đầu phiên bản PHP (ví dụ: 8.3 -> 83, 8.4 -> 84) để sinh đường dẫn PHP CLI tương ứng
        $phpMajorMinor = str_replace('.', '', substr(PHP_VERSION, 0, 3));
    @endphp
    <div class="info-box" style="margin-top: 20px;">
        <div class="info-box-title" style="color: #10B981;">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            Cấu hình Cron Job (Bắt buộc chạy ngầm)
        </div>
        <p class="finish-desc" style="font-size: 13px; margin-bottom: 15px;">
            Vui lòng cấu hình Cron Job với chu kỳ <strong>1 phút/lần</strong> để hệ thống tự động cập nhật trạng thái đơn hàng và tính toán hoa hồng MLM:
        </p>

        <!-- Nếu sử dụng cPanel -->
        <div style="margin-bottom: 15px;">
            <div style="font-size: 12px; font-weight: bold; color: #E5E7EB; margin-bottom: 8px; display: flex; align-items: center; gap: 6px;">
                <span style="background: #3B82F6; color: white; font-size: 10px; padding: 2px 6px; border-radius: 4px; font-family: sans-serif;">cPanel</span>
                Thiết lập Cron Job 1 phút/lần (* * * * *) bằng một trong hai cách dưới đây:
            </div>
            
            <!-- Cách 1: Mặc định -->
            <div style="margin-bottom: 8px;">
                <div style="font-size: 11px; color: #9CA3AF; margin-bottom: 4px; font-weight: 500;">
                    Cách 1: Lệnh PHP mặc định (Nhanh & Tiện lợi)
                </div>
                <div class="cron-command-box" style="background: rgba(0, 0, 0, 0.2); border: 1px solid rgba(255, 255, 255, 0.1); padding: 12px; border-radius: 8px; font-family: monospace; font-size: 12px; display: flex; align-items: center; justify-content: space-between;">
                    <code id="cronCpanelDefault" style="color: #10B981; word-break: break-all;">cd {{ base_path() }} && /usr/local/bin/php artisan schedule:run >> /dev/null 2>&1</code>
                    <button type="button" onclick="copyText('cronCpanelDefault')" class="btn-copy" style="background: #3B82F6; border: none; color: white; padding: 6px 12px; border-radius: 6px; cursor: pointer; font-size: 11px; margin-left: 10px; flex-shrink: 0; font-family: sans-serif; transition: background 0.2s;">
                        Sao chép
                    </button>
                </div>
            </div>

            <!-- Cách 2: ea-php động -->
            <div>
                <div style="font-size: 11px; color: #9CA3AF; margin-bottom: 4px; font-weight: 500;">
                    Cách 2: Chỉ định phiên bản PHP {{ PHP_VERSION }} (Khuyên dùng nếu cách 1 lỗi phiên bản)
                </div>
                <div class="cron-command-box" style="background: rgba(0, 0, 0, 0.2); border: 1px solid rgba(255, 255, 255, 0.1); padding: 12px; border-radius: 8px; font-family: monospace; font-size: 12px; display: flex; align-items: center; justify-content: space-between;">
                    <code id="cronCpanelEa" style="color: #10B981; word-break: break-all;">cd {{ base_path() }} && /usr/local/bin/ea-php{{ $phpMajorMinor }} artisan schedule:run >> /dev/null 2>&1</code>
                    <button type="button" onclick="copyText('cronCpanelEa')" class="btn-copy" style="background: #3B82F6; border: none; color: white; padding: 6px 12px; border-radius: 6px; cursor: pointer; font-size: 11px; margin-left: 10px; flex-shrink: 0; font-family: sans-serif; transition: background 0.2s;">
                        Sao chép
                    </button>
                </div>
            </div>
        </div>

        <!-- Nếu sử dụng VPS aaPanel -->
        <div>
            <div style="font-size: 12px; font-weight: bold; color: #E5E7EB; margin-bottom: 5px; display: flex; align-items: center; gap: 6px;">
                <span style="background: #10B981; color: white; font-size: 10px; padding: 2px 6px; border-radius: 4px; font-family: sans-serif;">aaPanel</span>
                Thêm Task (Shell Script), chu kỳ 1 phút với nội dung:
            </div>
            <div class="cron-command-box" style="background: rgba(0, 0, 0, 0.2); border: 1px solid rgba(255, 255, 255, 0.1); padding: 12px; border-radius: 8px; font-family: monospace; font-size: 12px; display: flex; align-items: center; justify-content: space-between;">
                <code id="cronAapanel" style="color: #10B981; word-break: break-all; white-space: pre-wrap;">cd {{ base_path() }} && /www/server/php/{{ $phpMajorMinor }}/bin/php artisan schedule:run >> /dev/null 2>&1</code>
                <button type="button" onclick="copyText('cronAapanel')" class="btn-copy" style="background: #3B82F6; border: none; color: white; padding: 6px 12px; border-radius: 6px; cursor: pointer; font-size: 11px; margin-left: 10px; flex-shrink: 0; font-family: sans-serif; transition: background 0.2s;">
                    Sao chép
                </button>
            </div>
        </div>

        {{-- 
            Cấu hình Cron Job dành riêng cho Hostinger
            Tại sao cần cấu hình riêng: Hostinger yêu cầu chạy lệnh trực tiếp bằng đường dẫn tuyệt đối của PHP CLI 
            và tệp tin thực thi artisan, không thông qua chuyển đổi thư mục (lệnh cd) để đảm bảo tiến trình được khởi chạy cô lập thành công.
        --}}
        <div style="margin-top: 15px;">
            <div style="font-size: 12px; font-weight: bold; color: #E5E7EB; margin-bottom: 5px; display: flex; align-items: center; gap: 6px;">
                <span style="background: #8B5CF6; color: white; font-size: 10px; padding: 2px 6px; border-radius: 4px; font-family: sans-serif;">Hostinger</span>
                Thiết lập Cron Job 1 phút/lần bằng lệnh tuyệt đối:
            </div>
            <div class="cron-command-box" style="background: rgba(0, 0, 0, 0.2); border: 1px solid rgba(255, 255, 255, 0.1); padding: 12px; border-radius: 8px; font-family: monospace; font-size: 12px; display: flex; align-items: center; justify-content: space-between;">
                <code id="cronHostinger" style="color: #10B981; word-break: break-all;">/usr/bin/php {{ base_path() }}/artisan schedule:run</code>
                <button type="button" onclick="copyText('cronHostinger')" class="btn-copy" style="background: #3B82F6; border: none; color: white; padding: 6px 12px; border-radius: 6px; cursor: pointer; font-size: 11px; margin-left: 10px; flex-shrink: 0; font-family: sans-serif; transition: background 0.2s;">
                    Sao chép
                </button>
            </div>
        </div>
    </div>

    @if ($errors->any())
        @foreach ($errors->all() as $error)
            <div class="error-message">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" /></svg>
                <p>{{ $error }}</p>
            </div>
        @endforeach
    @endif

    <form action="{{ route('installer.step5.process') }}" method="POST" id="finishForm">
        @csrf
        <div class="btn-group" style="justify-content: center;">
            <button type="submit" class="btn btn-success" id="btnFinish">
                <span class="btn-text">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.59 14.37a6 6 0 01-5.84 7.38v-4.8m5.84-2.58a14.98 14.98 0 006.16-12.12A14.98 14.98 0 009.631 8.41m5.96 5.96a14.926 14.926 0 01-5.841 2.58m-.119-8.54a6 6 0 00-7.381 5.84h4.8m2.58-5.84a14.927 14.927 0 00-2.58 5.84m2.699 2.7c-.103.021-.207.041-.311.06a15.09 15.09 0 01-2.448-2.448 14.9 14.9 0 01.06-.312m-2.24 2.39a4.493 4.493 0 00-1.757 4.306 4.493 4.493 0 004.306-1.758M16.5 9a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z" /></svg>
                    Hoàn Tất Cài Đặt & Truy Cập Website
                </span>
                <span class="btn-loading"><span class="spinner"></span> Đang hoàn tất...</span>
            </button>
        </div>
    </form>

    {{-- Toast thông báo mượt mà tự động ẩn --}}
    <div id="toast" style="visibility: hidden; min-width: 280px; background: rgba(16, 185, 129, 0.9); backdrop-filter: blur(10px); color: #fff; text-align: center; border-radius: 12px; padding: 14px 20px; position: fixed; z-index: 99999; left: 50%; bottom: 40px; transform: translate(-50%, 0); font-size: 13px; font-weight: 600; box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.4); border: 1px solid rgba(255, 255, 255, 0.2); font-family: 'Inter', sans-serif; transition: visibility 0s, opacity 0.3s ease-in-out, transform 0.3s ease-in-out; opacity: 0; display: flex; align-items: center; justify-content: center; gap: 8px;">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" style="width: 18px; height: 18px; flex-shrink: 0; color: #fff;">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <span id="toast-message">Đã sao chép thành công!</span>
    </div>
@endsection

@section('scripts')
<script>
    // Lắng nghe sự kiện submit của form để hiển thị trạng thái loading
    document.getElementById('finishForm').addEventListener('submit', function() {
        document.getElementById('btnFinish').classList.add('loading');
    });

    // Hàm thực hiện sao chép lệnh cấu hình Cron Job vào clipboard
    function copyText(id) {
        const text = document.getElementById(id).innerText;
        navigator.clipboard.writeText(text).then(function() {
            showToast('Đã sao chép thành công!');
        }, function(err) {
            console.error('Không thể sao chép: ', err);
            showToast('Không thể sao chép!', 'error');
        });
    }

    // Hàm hiển thị Toast thông báo đẹp mắt tự ẩn
    function showToast(message, type = 'success') {
        const toast = document.getElementById('toast');
        const toastMessage = document.getElementById('toast-message');
        toastMessage.innerText = message;
        
        if (type === 'error') {
            toast.style.background = 'rgba(239, 68, 68, 0.9)'; // Màu đỏ nếu lỗi
        } else {
            toast.style.background = 'rgba(16, 185, 129, 0.9)'; // Màu xanh lá nếu thành công
        }

        toast.style.visibility = 'visible';
        toast.style.opacity = '1';
        toast.style.transform = 'translate(-50%, -10px)'; // Nhích nhẹ lên tạo hiệu ứng mượt
        
        setTimeout(function() {
            toast.style.opacity = '0';
            toast.style.transform = 'translate(-50%, 0)';
            setTimeout(function() {
                toast.style.visibility = 'hidden';
            }, 300);
        }, 2500);
    }
</script>
@endsection
