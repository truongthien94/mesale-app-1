{{--
    Bước 4: Tạo tài khoản Admin đầu tiên & đặt tên website
--}}
@extends('installer.layout')
@section('title', 'Tạo Tài Khoản Admin')
@php $currentStep = 4; @endphp

@section('content')
    <div class="card-title">
        <div class="card-title-icon" style="background: rgba(139, 92, 246, 0.12); color: #a78bfa;">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
            </svg>
        </div>
        Tạo Tài Khoản Quản Trị
    </div>
    <p class="card-subtitle">Thiết lập tài khoản Admin chính (Super Admin) để quản lý toàn bộ hệ thống.</p>

    @if ($errors->any())
        @foreach ($errors->all() as $error)
            <div class="error-message">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" /></svg>
                <p>{{ $error }}</p>
            </div>
        @endforeach
    @endif

    <form action="{{ route('installer.step4.process') }}" method="POST" id="adminForm">
        @csrf

        <div class="form-group">
            <label class="form-label">Tên Website <span class="required">*</span></label>
            <input type="text" name="site_name" class="form-input" value="{{ old('site_name', 'Hoàn Tiền Shopee') }}" placeholder="Hoàn Tiền Shopee" required>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Họ và tên Admin <span class="required">*</span></label>
                <input type="text" name="admin_name" class="form-input" value="{{ old('admin_name') }}" placeholder="Nguyễn Văn A" required>
            </div>
            <div class="form-group">
                <label class="form-label">Số điện thoại</label>
                <input type="text" name="admin_phone" class="form-input" value="{{ old('admin_phone') }}" placeholder="0987654321">
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">Email đăng nhập <span class="required">*</span></label>
            <input type="email" name="admin_email" class="form-input" value="{{ old('admin_email') }}" placeholder="admin@example.com" required>
        </div>

        <div class="form-row">
            <div class="form-group">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                    <label class="form-label" style="margin-bottom: 0;">Mật khẩu <span class="required">*</span></label>
                    <div style="display: flex; gap: 8px; font-size: 0.78rem;">
                        <button type="button" id="btnRandomPass" style="background: none; border: none; color: #a78bfa; cursor: pointer; font-weight: 500; padding: 0; text-decoration: underline;" onmouseover="this.style.color='#c084fc'" onmouseout="this.style.color='#a78bfa'">
                            Tạo ngẫu nhiên
                        </button>
                        <button type="button" id="btnCopyPass" style="background: none; border: none; color: #94a3b8; cursor: pointer; font-weight: 500; padding: 0; text-decoration: underline; display: none;" onmouseover="this.style.color='#f1f5f9'" onmouseout="this.style.color='#94a3b8'">
                            Sao chép
                        </button>
                    </div>
                </div>
                <input type="password" name="admin_password" id="admin_password" class="form-input" placeholder="Tối thiểu 6 ký tự" required minlength="6">
            </div>
            <div class="form-group">
                <label class="form-label">Xác nhận mật khẩu <span class="required">*</span></label>
                <input type="password" name="admin_password_confirmation" id="admin_password_confirmation" class="form-input" placeholder="Nhập lại mật khẩu" required>
            </div>
        </div>

        <div class="warning-notice">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" /></svg>
            <p>Hãy ghi nhớ email và mật khẩu này. Đây là tài khoản duy nhất có quyền truy cập vào trang quản trị Admin Panel sau khi cài đặt.</p>
        </div>

        <div class="btn-group">
            <a href="{{ route('installer.step3') }}" class="btn btn-secondary">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" /></svg>
                <span>Quay Lại</span>
            </a>
            <button type="submit" class="btn btn-primary" id="btnSubmitAdmin">
                <span class="btn-text">Tạo Tài Khoản & Tiếp Tục</span>
                <span class="btn-loading"><span class="spinner"></span> Đang tạo...</span>
            </button>
        </div>
    </form>
@endsection

@section('scripts')
<script>
    // Logic hiển thị spinner khi submit form tạo tài khoản admin
    document.getElementById('adminForm').addEventListener('submit', function() {
        document.getElementById('btnSubmitAdmin').classList.add('loading');
    });

    // Logic tạo mật khẩu ngẫu nhiên bảo mật cao (chữ thường, chữ hoa, số, ký tự đặc biệt)
    document.getElementById('btnRandomPass').addEventListener('click', function() {
        const chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
        let password = '';
        for (let i = 0; i < 12; i++) {
            password += chars.charAt(Math.floor(Math.random() * chars.length));
        }

        const passInput = document.getElementById('admin_password');
        const confirmInput = document.getElementById('admin_password_confirmation');
        const btnCopy = document.getElementById('btnCopyPass');

        // Gán mật khẩu vừa sinh vào cả 2 ô input
        passInput.value = password;
        confirmInput.value = password;

        // Chuyển kiểu input thành text để quản trị viên có thể xem trực quan mật khẩu
        passInput.type = 'text';
        confirmInput.type = 'text';

        // Hiển thị nút sao chép nhanh
        btnCopy.style.display = 'inline-block';
    });

    // Logic sao chép mật khẩu đã sinh vào bộ nhớ tạm (clipboard)
    document.getElementById('btnCopyPass').addEventListener('click', function() {
        const password = document.getElementById('admin_password').value;
        if (!password) return;

        navigator.clipboard.writeText(password).then(() => {
            const btnCopy = document.getElementById('btnCopyPass');
            const originalText = btnCopy.textContent;
            
            // Thay đổi nhãn nút tạm thời để thông báo cho người dùng
            btnCopy.textContent = 'Đã sao chép ✔';
            btnCopy.style.color = '#22c55e'; // Hiển thị màu xanh lá thành công
            
            setTimeout(() => {
                btnCopy.textContent = originalText;
                btnCopy.style.color = '#94a3b8';
            }, 2000);
        }).catch(err => {
            alert('Không thể sao chép mật khẩu: ' + err);
        });
    });
</script>
@endsection
