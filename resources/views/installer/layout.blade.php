{{-- 
    Layout chính cho Trình cài đặt hệ thống.
    Giao diện wizard với thanh tiến trình 5 bước.
    Hoàn toàn tách biệt khỏi layout chính của ứng dụng (không cần DB, Auth,...).
--}}
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('title', 'Cài Đặt Hệ Thống') - Installer</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        /* ============================================
           DESIGN SYSTEM - Installer Theme
           Thiết kế Dark Mode cao cấp với hiệu ứng gradient
           ============================================ */
        :root {
            --primary: #6366f1;
            --primary-light: #818cf8;
            --primary-dark: #4f46e5;
            --success: #22c55e;
            --success-light: #4ade80;
            --warning: #f59e0b;
            --warning-light: #fbbf24;
            --danger: #ef4444;
            --danger-light: #f87171;
            --info: #3b82f6;

            --bg-body: #0f0f23;
            --bg-card: #1a1a2e;
            --bg-card-hover: #1e1e35;
            --bg-input: #16162a;
            --bg-input-focus: #1c1c38;

            --border: rgba(99, 102, 241, 0.15);
            --border-hover: rgba(99, 102, 241, 0.3);

            --text-primary: #f1f5f9;
            --text-secondary: #94a3b8;
            --text-muted: #64748b;

            --radius-sm: 8px;
            --radius-md: 12px;
            --radius-lg: 16px;
            --radius-xl: 20px;

            --shadow-card: 0 8px 32px rgba(0, 0, 0, 0.3);
            --shadow-glow: 0 0 30px rgba(99, 102, 241, 0.15);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--bg-body);
            color: var(--text-primary);
            min-height: 100vh;
            line-height: 1.6;
            overflow-x: hidden;
        }

        /* Background pattern động với particles */
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: 
                radial-gradient(ellipse at 20% 50%, rgba(99, 102, 241, 0.08) 0%, transparent 60%),
                radial-gradient(ellipse at 80% 20%, rgba(139, 92, 246, 0.06) 0%, transparent 50%),
                radial-gradient(ellipse at 50% 80%, rgba(59, 130, 246, 0.05) 0%, transparent 50%);
            pointer-events: none;
            z-index: 0;
        }

        /* Container chính */
        .installer-wrapper {
            position: relative;
            z-index: 1;
            max-width: 820px;
            margin: 0 auto;
            padding: 40px 24px 60px;
            min-height: 100vh;
        }

        /* Header với logo & branding */
        .installer-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .installer-logo {
            width: 64px;
            height: 64px;
            background: linear-gradient(135deg, var(--primary) 0%, #8b5cf6 100%);
            border-radius: var(--radius-lg);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 16px;
            box-shadow: 0 8px 24px rgba(99, 102, 241, 0.3);
        }

        .installer-logo svg {
            width: 32px;
            height: 32px;
            color: white;
        }

        .installer-header h1 {
            font-size: 1.75rem;
            font-weight: 700;
            background: linear-gradient(135deg, var(--text-primary) 0%, var(--primary-light) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .installer-header p {
            color: var(--text-secondary);
            margin-top: 6px;
            font-size: 0.95rem;
        }

        /* ============================================
           PROGRESS BAR - Thanh tiến trình 5 bước
           ============================================ */
        .progress-steps {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 40px;
            gap: 0;
            padding: 0 20px;
        }

        .step-item {
            display: flex;
            align-items: center;
            gap: 8px;
            position: relative;
        }

        .step-circle {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 0.85rem;
            transition: all 0.3s ease;
            flex-shrink: 0;
            border: 2px solid transparent;
        }

        /* Step trạng thái: chưa đến */
        .step-item.upcoming .step-circle {
            background: var(--bg-card);
            color: var(--text-muted);
            border-color: var(--border);
        }

        /* Step trạng thái: đang active */
        .step-item.active .step-circle {
            background: linear-gradient(135deg, var(--primary) 0%, #8b5cf6 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(99, 102, 241, 0.4);
            animation: pulse-glow 2s infinite;
        }

        /* Step trạng thái: đã hoàn thành */
        .step-item.completed .step-circle {
            background: linear-gradient(135deg, var(--success) 0%, #16a34a 100%);
            color: white;
            box-shadow: 0 4px 12px rgba(34, 197, 94, 0.3);
        }

        .step-label {
            font-size: 0.75rem;
            font-weight: 500;
            display: none; /* Ẩn label trên mobile */
        }

        .step-item.active .step-label {
            color: var(--primary-light);
        }

        .step-item.completed .step-label {
            color: var(--success-light);
        }

        .step-item.upcoming .step-label {
            color: var(--text-muted);
        }

        /* Đường nối giữa các bước */
        .step-connector {
            width: 60px;
            height: 2px;
            background: var(--border);
            position: relative;
            overflow: hidden;
        }

        .step-connector.completed {
            background: var(--success);
        }

        .step-connector.active {
            background: linear-gradient(90deg, var(--success), var(--primary));
        }

        @media (min-width: 640px) {
            .step-label { display: block; }
            .step-connector { width: 80px; }
        }

        @keyframes pulse-glow {
            0%, 100% { box-shadow: 0 4px 15px rgba(99, 102, 241, 0.4); }
            50% { box-shadow: 0 4px 25px rgba(99, 102, 241, 0.6); }
        }

        /* ============================================
           CARD CHÍNH - Nội dung từng bước
           ============================================ */
        .installer-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius-xl);
            padding: 40px;
            box-shadow: var(--shadow-card);
            position: relative;
            overflow: hidden;
        }

        /* Viền gradient trên cùng */
        .installer-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--primary), #8b5cf6, var(--info));
        }

        .card-title {
            font-size: 1.4rem;
            font-weight: 700;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .card-title-icon {
            width: 44px;
            height: 44px;
            border-radius: var(--radius-md);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .card-title-icon svg {
            width: 22px;
            height: 22px;
        }

        .card-subtitle {
            color: var(--text-secondary);
            font-size: 0.9rem;
            margin-bottom: 30px;
            padding-left: 56px;
        }

        /* ============================================
           CHECK LIST - Danh sách kiểm tra
           ============================================ */
        .check-list {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .check-item {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 14px 18px;
            background: var(--bg-input);
            border-radius: var(--radius-md);
            border: 1px solid transparent;
            transition: all 0.2s ease;
        }

        .check-item:hover {
            border-color: var(--border-hover);
            background: var(--bg-input-focus);
        }

        .check-icon {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .check-icon svg {
            width: 14px;
            height: 14px;
        }

        .check-icon.passed {
            background: rgba(34, 197, 94, 0.15);
            color: var(--success);
        }

        .check-icon.failed {
            background: rgba(239, 68, 68, 0.15);
            color: var(--danger);
        }

        .check-icon.warning {
            background: rgba(245, 158, 11, 0.15);
            color: var(--warning);
        }

        .check-info {
            flex: 1;
            min-width: 0;
        }

        .check-name {
            font-weight: 600;
            font-size: 0.9rem;
            color: var(--text-primary);
        }

        .check-desc {
            font-size: 0.8rem;
            color: var(--text-muted);
            margin-top: 2px;
        }

        .check-badge {
            padding: 4px 10px;
            border-radius: 99px;
            font-size: 0.7rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            flex-shrink: 0;
        }

        .badge-passed {
            background: rgba(34, 197, 94, 0.12);
            color: var(--success);
            border: 1px solid rgba(34, 197, 94, 0.2);
        }

        .badge-failed {
            background: rgba(239, 68, 68, 0.12);
            color: var(--danger);
            border: 1px solid rgba(239, 68, 68, 0.2);
        }

        .badge-warning {
            background: rgba(245, 158, 11, 0.12);
            color: var(--warning);
            border: 1px solid rgba(245, 158, 11, 0.2);
        }

        /* Section divider trong check list */
        .check-section-title {
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 1px;
            padding: 16px 0 4px;
        }

        /* ============================================
           FORM ELEMENTS - Input, Select, Button
           ============================================ */
        .form-group {
            margin-bottom: 22px;
        }

        .form-label {
            display: block;
            font-weight: 600;
            font-size: 0.85rem;
            margin-bottom: 8px;
            color: var(--text-primary);
        }

        .form-label .required {
            color: var(--danger);
            margin-left: 2px;
        }

        .form-hint {
            font-size: 0.78rem;
            color: var(--text-muted);
            font-weight: 400;
        }

        .form-input {
            width: 100%;
            padding: 12px 16px;
            background: var(--bg-input);
            border: 1.5px solid var(--border);
            border-radius: var(--radius-md);
            color: var(--text-primary);
            font-size: 0.9rem;
            font-family: inherit;
            transition: all 0.2s ease;
            outline: none;
        }

        .form-input:focus {
            border-color: var(--primary);
            background: var(--bg-input-focus);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.12);
        }

        .form-input::placeholder {
            color: var(--text-muted);
        }

        .form-input.is-invalid {
            border-color: var(--danger);
            box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1);
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }

        @media (max-width: 640px) {
            .form-row { grid-template-columns: 1fr; }
        }

        /* ============================================
           ERROR MESSAGES - Thông báo lỗi
           ============================================ */
        .error-message {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.25);
            border-radius: var(--radius-md);
            padding: 14px 18px;
            margin-bottom: 24px;
            display: flex;
            align-items: flex-start;
            gap: 12px;
        }

        .error-message svg {
            width: 20px;
            height: 20px;
            color: var(--danger);
            flex-shrink: 0;
            margin-top: 1px;
        }

        .error-message p {
            color: var(--danger-light);
            font-size: 0.85rem;
            line-height: 1.5;
        }

        /* ============================================
           CHECKBOX CUSTOM
           ============================================ */
        .form-checkbox-wrapper {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            padding: 14px 18px;
            background: rgba(245, 158, 11, 0.06);
            border: 1px solid rgba(245, 158, 11, 0.2);
            border-radius: var(--radius-md);
            cursor: pointer;
        }

        .form-checkbox-wrapper input[type="checkbox"] {
            width: 18px;
            height: 18px;
            accent-color: var(--primary);
            margin-top: 2px;
            flex-shrink: 0;
        }

        .form-checkbox-label {
            font-size: 0.85rem;
            color: var(--warning-light);
            line-height: 1.5;
        }

        /* ============================================
           BUTTONS - Nút hành động
           ============================================ */
        .btn-group {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 36px;
            gap: 12px;
        }

        .btn {
            padding: 12px 28px;
            border-radius: var(--radius-md);
            font-weight: 600;
            font-size: 0.9rem;
            font-family: inherit;
            cursor: pointer;
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.25s ease;
            text-decoration: none;
        }

        .btn svg {
            width: 18px;
            height: 18px;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary) 0%, #7c3aed 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(99, 102, 241, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(99, 102, 241, 0.4);
        }

        .btn-primary:active {
            transform: translateY(0);
        }

        .btn-primary:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none !important;
            box-shadow: none !important;
        }

        .btn-secondary {
            background: var(--bg-input);
            color: var(--text-secondary);
            border: 1px solid var(--border);
        }

        .btn-secondary:hover {
            background: var(--bg-input-focus);
            border-color: var(--border-hover);
            color: var(--text-primary);
        }

        .btn-success {
            background: linear-gradient(135deg, var(--success) 0%, #16a34a 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(34, 197, 94, 0.3);
        }

        .btn-success:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(34, 197, 94, 0.4);
        }

        .btn-danger {
            background: linear-gradient(135deg, var(--danger) 0%, #dc2626 100%);
            color: white;
        }

        /* Loading state cho button */
        .btn.loading {
            pointer-events: none;
            opacity: 0.7;
        }

        .btn.loading .btn-text { display: none; }
        .btn .btn-loading { display: none; }
        .btn.loading .btn-loading { 
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .spinner {
            width: 18px;
            height: 18px;
            border: 2px solid rgba(255,255,255,0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 0.7s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* ============================================
           FINISH PAGE - Trang hoàn tất
           ============================================ */
        .finish-success {
            text-align: center;
            padding: 20px 0;
        }

        .finish-icon {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--success) 0%, #16a34a 100%);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
            box-shadow: 0 8px 30px rgba(34, 197, 94, 0.3);
            animation: bounce-in 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        .finish-icon svg {
            width: 40px;
            height: 40px;
            color: white;
        }

        @keyframes bounce-in {
            0% { transform: scale(0); opacity: 0; }
            60% { transform: scale(1.1); }
            100% { transform: scale(1); opacity: 1; }
        }

        .finish-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 8px;
            color: var(--text-primary);
        }

        .finish-desc {
            color: var(--text-secondary);
            font-size: 0.9rem;
            margin-bottom: 30px;
            max-width: 500px;
            margin-left: auto;
            margin-right: auto;
        }

        /* Info box cho thông tin đăng nhập */
        .info-box {
            background: var(--bg-input);
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            padding: 20px;
            text-align: left;
            margin-bottom: 24px;
        }

        .info-box-title {
            font-weight: 600;
            font-size: 0.85rem;
            color: var(--primary-light);
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .info-box-title svg {
            width: 16px;
            height: 16px;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid var(--border);
            font-size: 0.85rem;
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-row .label {
            color: var(--text-muted);
        }

        .info-row .value {
            color: var(--text-primary);
            font-weight: 600;
            font-family: 'JetBrains Mono', 'Fira Code', monospace;
        }

        /* Warning notice box */
        .warning-notice {
            background: rgba(245, 158, 11, 0.08);
            border: 1px solid rgba(245, 158, 11, 0.2);
            border-radius: var(--radius-md);
            padding: 14px 18px;
            display: flex;
            align-items: flex-start;
            gap: 12px;
            margin-bottom: 20px;
        }

        .warning-notice svg {
            width: 20px;
            height: 20px;
            color: var(--warning);
            flex-shrink: 0;
            margin-top: 1px;
        }

        .warning-notice p {
            color: var(--warning-light);
            font-size: 0.82rem;
            line-height: 1.5;
        }

        /* Footer bản quyền */
        .installer-footer {
            text-align: center;
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid var(--border);
            color: var(--text-muted);
            font-size: 0.78rem;
        }

        .installer-footer a {
            color: var(--primary-light);
            text-decoration: none;
        }

        .installer-footer a:hover {
            text-decoration: underline;
        }

        /* Responsive mobile */
        @media (max-width: 640px) {
            .installer-wrapper { padding: 24px 16px 40px; }
            .installer-card { padding: 24px; }
            .card-subtitle { padding-left: 0; }
            .btn-group { flex-direction: column; }
            .btn { width: 100%; justify-content: center; }
        }
    </style>
</head>
<body>
    <div class="installer-wrapper">
        {{-- Header Logo --}}
        <div class="installer-header">
            <div class="installer-logo">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 1.5H8.25A2.25 2.25 0 006 3.75v16.5a2.25 2.25 0 002.25 2.25h7.5A2.25 2.25 0 0018 20.25V3.75a2.25 2.25 0 00-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3" />
                </svg>
            </div>
            <h1>Trình Cài Đặt Hệ Thống</h1>
            <p>Thiết lập nhanh hệ thống Hoàn Tiền Shopee chỉ với vài bước đơn giản</p>
        </div>

        {{-- Progress Steps --}}
        <div class="progress-steps">
            @php
                $currentStep = (int) ($currentStep ?? 1);
            @endphp

            @for ($i = 1; $i <= 5; $i++)
                @if ($i > 1)
                    <div class="step-connector {{ $i <= $currentStep ? ($i == $currentStep ? 'active' : 'completed') : '' }}"></div>
                @endif
                <div class="step-item {{ $i < $currentStep ? 'completed' : ($i == $currentStep ? 'active' : 'upcoming') }}">
                    <div class="step-circle">
                        @if ($i < $currentStep)
                            {{-- Icon checkmark cho bước đã hoàn thành --}}
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3" width="16" height="16">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                            </svg>
                        @else
                            {{ $i }}
                        @endif
                    </div>
                    <span class="step-label">
                        @switch($i)
                            @case(1) Hệ Thống @break
                            @case(2) Quyền @break
                            @case(3) Database @break
                            @case(4) Admin @break
                            @case(5) Hoàn Tất @break
                        @endswitch
                    </span>
                </div>
            @endfor
        </div>

        {{-- Nội dung bước hiện tại --}}
        <div class="installer-card">
            @yield('content')
        </div>

        {{-- Footer --}}
        <div class="installer-footer">
            Powered by <a href="https://cmsnt.co" target="_blank">CMSNT.CO</a> &copy; {{ date('Y') }}
        </div>
    </div>

    @yield('scripts')
</body>
</html>
