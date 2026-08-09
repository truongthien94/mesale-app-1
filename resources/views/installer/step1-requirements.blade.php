{{--
    Bước 1: Kiểm tra yêu cầu môi trường hệ thống
    - PHP version >= 8.2
    - Các extension PHP bắt buộc
    - Các extension khuyến nghị
--}}
@extends('installer.layout')
@section('title', 'Kiểm Tra Yêu Cầu Hệ Thống')

@php $currentStep = 1; @endphp

@section('content')
    {{-- Tiêu đề bước --}}
    <div class="card-title">
        <div class="card-title-icon" style="background: rgba(99, 102, 241, 0.12); color: var(--primary-light);">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 3.75H6.912a2.25 2.25 0 00-2.15 1.588L2.35 13.177a2.25 2.25 0 00-.1.661V18a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18v-4.162c0-.224-.034-.447-.1-.661L19.24 5.338a2.25 2.25 0 00-2.15-1.588H15M2.25 13.5h3.86a2.25 2.25 0 012.012 1.244l.256.512a2.25 2.25 0 002.013 1.244h3.218a2.25 2.25 0 002.013-1.244l.256-.512a2.25 2.25 0 012.013-1.244h3.859" />
            </svg>
        </div>
        Kiểm Tra Yêu Cầu Hệ Thống
    </div>
    <p class="card-subtitle">Hệ thống đang kiểm tra các yêu cầu kỹ thuật cần thiết để cài đặt và vận hành.</p>

    <div class="check-list">
        {{-- Phiên bản PHP --}}
        <div class="check-section-title">Phiên bản PHP</div>
        <div class="check-item">
            <div class="check-icon {{ $phpVersionOk ? 'passed' : 'failed' }}">
                @if ($phpVersionOk)
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                @else
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                @endif
            </div>
            <div class="check-info">
                <div class="check-name">PHP {{ $phpVersion }}</div>
                <div class="check-desc">Yêu cầu tối thiểu PHP 8.2 trở lên cho Laravel 11.x</div>
            </div>
            <span class="check-badge {{ $phpVersionOk ? 'badge-passed' : 'badge-failed' }}">
                {{ $phpVersionOk ? 'Đạt' : 'Không đạt' }}
            </span>
        </div>

        {{-- Extension bắt buộc --}}
        <div class="check-section-title">Extension bắt buộc</div>
        @foreach ($requiredExtensions as $name => $ext)
            <div class="check-item">
                <div class="check-icon {{ $ext['loaded'] ? 'passed' : 'failed' }}">
                    @if ($ext['loaded'])
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                    @else
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                    @endif
                </div>
                <div class="check-info">
                    <div class="check-name">{{ $name }}</div>
                    <div class="check-desc">{{ $ext['description'] }}</div>
                </div>
                <span class="check-badge {{ $ext['loaded'] ? 'badge-passed' : 'badge-failed' }}">
                    {{ $ext['loaded'] ? 'Đã cài' : 'Thiếu' }}
                </span>
            </div>
        @endforeach

        {{-- Extension khuyến nghị --}}
        <div class="check-section-title">Extension khuyến nghị (không bắt buộc)</div>
        @foreach ($recommendedExtensions as $name => $ext)
            <div class="check-item">
                <div class="check-icon {{ $ext['loaded'] ? 'passed' : 'warning' }}">
                    @if ($ext['loaded'])
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                    @else
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" /></svg>
                    @endif
                </div>
                <div class="check-info">
                    <div class="check-name">{{ $name }}</div>
                    <div class="check-desc">{{ $ext['description'] }}</div>
                </div>
                <span class="check-badge {{ $ext['loaded'] ? 'badge-passed' : 'badge-warning' }}">
                    {{ $ext['loaded'] ? 'Đã cài' : 'Chưa cài' }}
                </span>
            </div>
        @endforeach
    </div>

    {{-- Nút hành động --}}
    <div class="btn-group">
        <div></div>
        @if ($canProceed)
            <a href="{{ route('installer.step2') }}" class="btn btn-primary">
                <span>Tiếp Tục</span>
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" /></svg>
            </a>
        @else
            <button class="btn btn-primary" disabled>
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" /></svg>
                <span>Vui lòng cài đặt các extension bị thiếu</span>
            </button>
        @endif
    </div>
@endsection
