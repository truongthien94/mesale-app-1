{{--
    Bước 2: Kiểm tra quyền ghi file & thư mục
    Laravel cần quyền ghi vào storage/, bootstrap/cache/, .env, public/uploads/
--}}
@extends('installer.layout')
@section('title', 'Kiểm Tra Quyền Thư Mục')

@php $currentStep = 2; @endphp

@section('content')
    {{-- Tiêu đề bước --}}
    <div class="card-title">
        <div class="card-title-icon" style="background: rgba(245, 158, 11, 0.12); color: var(--warning-light);">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
            </svg>
        </div>
        Kiểm Tra Quyền Thư Mục
    </div>
    <p class="card-subtitle">Kiểm tra quyền ghi (writable) cho các thư mục và file quan trọng mà Laravel cần để hoạt động.</p>

    <div class="check-list">
        @foreach ($permissions as $path => $perm)
            <div class="check-item">
                <div class="check-icon {{ $perm['writable'] ? 'passed' : 'failed' }}">
                    @if ($perm['writable'])
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                    @else
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                    @endif
                </div>
                <div class="check-info">
                    <div class="check-name" style="font-family: 'JetBrains Mono', 'Fira Code', monospace; font-size: 0.85rem;">{{ $path }}</div>
                    <div class="check-desc">{{ $perm['description'] }}</div>
                </div>
                <span class="check-badge {{ $perm['writable'] ? 'badge-passed' : 'badge-failed' }}">
                    {{ $perm['writable'] ? 'Ghi được' : 'Không ghi được' }}
                </span>
            </div>
        @endforeach
    </div>

    @if (!$allPassed)
        <div class="warning-notice" style="margin-top: 24px; background: rgba(239, 68, 68, 0.08); border: 1px solid rgba(239, 68, 68, 0.2); padding: 16px; border-radius: 8px;">
            <div style="display: flex; align-items: flex-start; gap: 12px;">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="width: 24px; height: 24px; color: #ef4444; flex-shrink: 0; margin-top: 2px;">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                </svg>
                <div>
                    <h4 style="margin: 0 0 8px 0; color: #ef4444; font-weight: 600;">{{ __('⚠️ Phát Hiện Lỗi Thiếu Quyền Đọc Ghi File') }}</h4>
                    <p style="margin: 0 0 12px 0; font-size: 0.9rem; line-height: 1.5;">{{ __('Hệ thống phát hiện một số thư mục hoặc file chưa có quyền đọc ghi. Để khắc phục triệt để lỗi này trên Hosting / VPS Linux (đặc biệt là aaPanel), vui lòng thực hiện các lệnh sau qua SSH (chạy dưới quyền root):') }}</p>
                    
                    <div style="background: #1e1e2e; padding: 12px; border-radius: 6px; margin-bottom: 12px; border: 1px solid rgba(255,255,255,0.05);">
                        <span style="color: #f38ba8; font-size: 0.8rem; display: block; margin-bottom: 6px; font-weight: 600;">{{ __('👉 Sao chép 1 dòng duy nhất để chạy toàn bộ lệnh (Khuyên dùng):') }}</span>
                        <code style="color: #89b4fa; font-family: monospace; font-size: 0.85rem; word-break: break-all; display: block; background: rgba(137, 180, 250, 0.08); padding: 10px; border-radius: 4px; border: 1px dashed rgba(137, 180, 250, 0.3); margin-bottom: 14px;" id="all-in-one-cmd">chown -R www:www {{ base_path() }} && chmod -R 775 {{ base_path('storage') }} {{ base_path('bootstrap/cache') }} {{ base_path('public/uploads') }} {{ base_path('lang') }} && touch {{ base_path('.env') }} && chown www:www {{ base_path('.env') }} && chmod 664 {{ base_path('.env') }}</code>

                        <span style="color: #6c7086; font-size: 0.8rem; display: block; margin-bottom: 4px;">{{ __('# Hoặc thực hiện chi tiết từng bước:') }}</span>
                        <div style="padding-left: 8px;">
                            <span style="color: #6c7086; font-size: 0.75rem; display: block; margin-top: 4px;">{{ __('- Bước 1: Chuyển quyền sở hữu dự án về user chạy Web Server') }}</span>
                            <code style="color: #a6e3a1; font-family: monospace; font-size: 0.82rem; word-break: break-all; display: block;">chown -R www:www {{ base_path() }}</code>
                            
                            <span style="color: #6c7086; font-size: 0.75rem; display: block; margin-top: 6px;">{{ __('- Bước 2: Phân quyền ghi đệ quy cho các thư mục quan trọng') }}</span>
                            <code style="color: #a6e3a1; font-family: monospace; font-size: 0.82rem; display: block; word-break: break-all;">chmod -R 775 {{ base_path('storage') }} {{ base_path('bootstrap/cache') }} {{ base_path('public/uploads') }} {{ base_path('lang') }}</code>
                            
                            <span style="color: #6c7086; font-size: 0.75rem; display: block; margin-top: 6px;">{{ __('- Bước 3: Tạo và phân quyền cho file .env') }}</span>
                            <code style="color: #a6e3a1; font-family: monospace; font-size: 0.82rem; display: block; word-break: break-all;">touch {{ base_path('.env') }} && chown www:www {{ base_path('.env') }} && chmod 664 {{ base_path('.env') }}</code>
                        </div>
                    </div>

                    <p style="margin: 0; font-size: 0.82rem; color: #a6adc8;">
                        💡 <em>{{ __('Lưu ý: Nếu dùng cPanel hoặc DirectAdmin, bạn có thể vào File Manager, click chuột phải vào các thư mục trên và chọn Change Permissions thành 775 (hoặc 755/777 tùy cấu hình server).') }}</em>
                    </p>
                </div>
            </div>
        </div>
    @else
        <div class="info-notice" style="margin-top: 24px; background: rgba(16, 185, 129, 0.08); border: 1px solid rgba(16, 185, 129, 0.2); padding: 14px; border-radius: 8px;">
            <div style="display: flex; align-items: flex-start; gap: 10px;">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="width: 20px; height: 20px; color: #10b981; flex-shrink: 0; margin-top: 2px;">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <div style="font-size: 0.85rem; line-height: 1.4;">
                    <strong style="color: #10b981;">{{ __('Quyền thư mục hợp lệ!') }}</strong> {{ __('Trên môi trường aaPanel/Linux, hãy đảm bảo rằng chủ sở hữu của toàn bộ thư mục dự án là') }} <code style="background: rgba(16, 185, 129, 0.15); padding: 2px 4px; border-radius: 3px; font-family: monospace;">www:www</code> {{ __('để tránh phát sinh lỗi ghi log hoặc sessions sau này khi vận hành thực tế.') }}
                </div>
            </div>
        </div>
    @endif

    {{-- Nút hành động --}}
    <div class="btn-group">
        <a href="{{ route('installer.step1') }}" class="btn btn-secondary">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" /></svg>
            <span>{{ __('Quay Lại') }}</span>
        </a>
        @if ($allPassed)
            <a href="{{ route('installer.step3') }}" class="btn btn-primary">
                <span>{{ __('Tiếp Tục') }}</span>
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" /></svg>
            </a>
        @else
            <button class="btn btn-primary" disabled>
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" /></svg>
                <span>{{ __('Vui lòng cấp quyền trước') }}</span>
            </button>
        @endif
    </div>
@endsection
