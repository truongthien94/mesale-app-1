{{--
    Bước 3: Cấu hình kết nối Database
--}}
@extends('installer.layout')
@section('title', 'Cấu Hình Database')
@php $currentStep = 3; @endphp

@section('content')
    <div class="card-title">
        <div class="card-title-icon" style="background: rgba(59, 130, 246, 0.12); color: var(--info);">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 6.375c0 2.278-3.694 4.125-8.25 4.125S3.75 8.653 3.75 6.375m16.5 0c0-2.278-3.694-4.125-8.25-4.125S3.75 4.097 3.75 6.375m16.5 0v11.25c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125V6.375m16.5 0v3.75m-16.5-3.75v3.75m16.5 0v3.75C20.25 16.153 16.556 18 12 18s-8.25-1.847-8.25-4.125v-3.75m16.5 0c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125" />
            </svg>
        </div>
        Cấu Hình Cơ Sở Dữ Liệu
    </div>
    <p class="card-subtitle">Nhập thông tin kết nối MySQL/MariaDB. Hệ thống sẽ tự động tạo cấu trúc bảng và dữ liệu mặc định.</p>

    @if ($errors->any())
        @foreach ($errors->all() as $error)
            <div class="error-message">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" /></svg>
                <p>{{ $error }}</p>
            </div>
        @endforeach
    @endif

    <form action="{{ route('installer.step3.process') }}" method="POST" id="dbForm">
        @csrf
        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Máy chủ Database <span class="required">*</span></label>
                <input type="text" name="db_host" class="form-input" value="{{ old('db_host', '127.0.0.1') }}" placeholder="127.0.0.1" required>
            </div>
            <div class="form-group">
                <label class="form-label">Cổng <span class="required">*</span></label>
                <input type="number" name="db_port" class="form-input" value="{{ old('db_port', '3306') }}" placeholder="3306" required>
            </div>
        </div>
        <div class="form-group">
            <label class="form-label">Tên Database <span class="required">*</span> <span class="form-hint">(Database phải được tạo sẵn)</span></label>
            <input type="text" name="db_database" class="form-input" value="{{ old('db_database') }}" placeholder="hoantienshopee" required>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Tên người dùng <span class="required">*</span></label>
                <input type="text" name="db_username" class="form-input" value="{{ old('db_username', 'root') }}" placeholder="root" required>
            </div>
            <div class="form-group">
                <label class="form-label">Mật khẩu</label>
                <input type="password" name="db_password" class="form-input" value="{{ old('db_password') }}" placeholder="Để trống nếu không có">
            </div>
        </div>

        <div class="form-group" style="margin-top: 1rem; border-top: 1px dashed #e2e8f0; padding-top: 1rem;">
            <label class="form-label">Mã Giấy Phép (License Key) <span class="required">*</span> <span class="form-hint">(Giấy phép kích hoạt mã nguồn được cung cấp bởi CMSNT.CO)</span></label>
            <input type="text" name="license_key" class="form-input" value="{{ old('license_key') }}" placeholder="Nhập mã giấy phép bản quyền của bạn..." required>
        </div>

        @if (session('show_force_option'))
            <label class="form-checkbox-wrapper">
                <input type="checkbox" name="force_overwrite" value="1">
                <span class="form-checkbox-label"><strong>⚠️ Ghi đè dữ liệu cũ:</strong> Tôi hiểu rằng toàn bộ dữ liệu hiện có sẽ bị XÓA HOÀN TOÀN. Hành động này không thể hoàn tác.</span>
            </label>
        @endif

        <div id="testResult" style="display: none; margin-bottom: 1.5rem;"></div>

        <div class="btn-group">
            <a href="{{ route('installer.step2') }}" class="btn btn-secondary">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" /></svg>
                <span>Quay Lại</span>
            </a>
            <button type="button" class="btn btn-success" id="btnTestDb">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" width="18" height="18">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span>Kiểm Tra Kết Nối</span>
                <span class="btn-loading"><span class="spinner"></span> Đang thử...</span>
            </button>
            <button type="submit" class="btn btn-primary" id="btnSubmitDb">
                <span class="btn-text">Kết Nối & Cài Đặt Database</span>
                <span class="btn-loading"><span class="spinner"></span> Đang thiết lập...</span>
            </button>
        </div>
    </form>
@endsection

@section('scripts')
<script>
    // Loading state cho button khi submit (migration có thể mất vài giây)
    document.getElementById('dbForm').addEventListener('submit', function() {
        document.getElementById('btnSubmitDb').classList.add('loading');
    });

    // Xử lý AJAX kiểm tra kết nối database
    document.getElementById('btnTestDb').addEventListener('click', function() {
        const btn = this;
        const resultDiv = document.getElementById('testResult');
        
        btn.classList.add('loading');
        resultDiv.style.display = 'none';
        resultDiv.className = '';
        resultDiv.innerHTML = '';

        const formData = new FormData(document.getElementById('dbForm'));

        // Sử dụng đường dẫn tương đối để tránh lỗi Mixed Content (HTTPS -> HTTP)
        fetch('{{ route('installer.step3.test', [], false) }}', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => {
            // Nếu không phải mã thành công (200) hoặc lỗi validation (400), ném lỗi HTTP cụ thể (404, 500...)
            if (!response.ok && response.status !== 400) {
                throw new Error(`Mã lỗi HTTP ${response.status} (${response.statusText || 'Kiểm tra cấu hình Nginx URL Rewrite hoặc PHP logs'})`);
            }
            return response.json().then(data => ({ status: response.status, data }));
        })
        .then(({ status, data }) => {
            btn.classList.remove('loading');
            resultDiv.style.display = 'flex';
            if (status === 200 && data.success) {
                resultDiv.className = 'check-item';
                resultDiv.style.borderColor = 'var(--success)';
                resultDiv.style.background = 'rgba(34, 197, 94, 0.05)';
                resultDiv.innerHTML = `
                    <div class="check-icon passed">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3" width="14" height="14">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                    <div class="check-info">
                        <div class="check-name" style="color: var(--success-light); font-size: 0.85rem;">${data.message}</div>
                    </div>
                `;
            } else {
                resultDiv.className = 'error-message';
                resultDiv.innerHTML = `
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                    </svg>
                    <p>${data.message || 'Không thể kết nối đến cơ sở dữ liệu.'}</p>
                `;
            }
        })
        .catch(error => {
            btn.classList.remove('loading');
            resultDiv.style.display = 'flex';
            resultDiv.className = 'error-message';
            resultDiv.innerHTML = `
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                </svg>
                <p>Đã xảy ra lỗi kết nối: ${error.message}</p>
            `;
        });
    });
</script>
@endsection
