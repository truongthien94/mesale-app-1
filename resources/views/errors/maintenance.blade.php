<!DOCTYPE html>
<html lang="vi" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('Hệ Thống Đang Bảo Trì') }} - {{ \App\Models\Setting::getVal('site_name', request()->getHost()) }}</title>
    <!-- Google Fonts: Outfit (Hiện đại, bo tròn tinh tế) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Outfit', 'sans-serif'],
                    },
                    colors: {
                        shopee: {
                            DEFAULT: '#ee4d2d',
                            dark: '#ff5733',
                            light: '#ff8a65'
                        }
                    }
                }
            }
        }
    </script>
    <style>
        /* Hiệu ứng di chuyển chậm cho các đốm màu nền (Ambient Glows) */
        @keyframes float-slow {
            0%, 100% { transform: translateY(0) scale(1); }
            50% { transform: translateY(-20px) scale(1.1); }
        }
        @keyframes float-reverse {
            0%, 100% { transform: translateY(0) scale(1.1); }
            50% { transform: translateY(20px) scale(0.95); }
        }
        .glow-1 { animation: float-slow 8s ease-in-out infinite; }
        .glow-2 { animation: float-reverse 10s ease-in-out infinite; }

        /* CSS Animation cho bánh răng cơ khí hoạt động đồng bộ */
        @keyframes gear-spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        @keyframes gear-spin-reverse {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(-360deg); }
        }
        .spin-clockwise { animation: gear-spin 10s linear infinite; }
        .spin-counter { animation: gear-spin-reverse 7s linear infinite; }
    </style>
</head>
<body class="h-full bg-slate-950 text-slate-100 flex flex-col justify-between items-center relative overflow-hidden px-4 py-8 sm:px-6">

    <!-- LỚP ĐỐM SÁNG NỀN ĐỘNG (Ambient Backdrop Background) -->
    <div class="absolute inset-0 overflow-hidden pointer-events-none z-0">
        <!-- Đốm sáng màu Cam Shopee bên trái -->
        <div class="absolute top-1/4 -left-1/4 w-[500px] h-[500px] rounded-full bg-shopee/15 blur-[120px] glow-1"></div>
        <!-- Đốm sáng màu Vàng ấm áp bên phải -->
        <div class="absolute bottom-1/4 -right-1/4 w-[500px] h-[500px] rounded-full bg-amber-500/10 blur-[130px] glow-2"></div>
    </div>

    <!-- Vùng trống trên đầu để căn giữa toàn cục -->
    <div class="z-10"></div>

    <!-- KHUNG CARD CHÍNH (Glassmorphism Card) -->
    <div class="max-w-lg w-full bg-slate-900/60 backdrop-blur-md rounded-3xl p-8 border border-white/5 shadow-2xl relative z-10 text-center space-y-8">
        
        <!-- BIỂU TƯỢNG BÁNH RĂNG CƠ KHÍ ĐỘNG (Animated Mechanical Gears) -->
        <div class="relative w-32 h-24 mx-auto flex items-center justify-center">
            <!-- Bánh răng lớn màu Cam (Chạy xuôi chiều) -->
            <svg class="absolute w-16 h-16 text-shopee/80 spin-clockwise top-1 left-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                <circle cx="12" cy="12" r="3" />
            </svg>
            <!-- Bánh răng nhỏ màu Vàng (Chạy ngược chiều) -->
            <svg class="absolute w-10 h-10 text-amber-400/90 spin-counter bottom-2 right-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                <circle cx="12" cy="12" r="3" />
            </svg>
        </div>

        <!-- TIÊU ĐỀ CHÍNH -->
        <div class="space-y-2">
            <h1 class="text-3xl font-extrabold tracking-tight bg-gradient-to-r from-shopee via-orange-400 to-amber-300 bg-clip-text text-transparent">
                HỆ THỐNG ĐANG BẢO TRÌ
            </h1>
            <p class="text-[10px] font-bold tracking-widest text-slate-500 uppercase">System Maintenance Mode</p>
        </div>

        <!-- LỜI NHẮN BẢO TRÌ CHI TIẾT -->
        <div class="bg-slate-950/50 rounded-2xl p-6 border border-white/5 shadow-inner">
            <p class="text-sm font-medium text-slate-300 leading-relaxed">
                {{ $message }}
            </p>
        </div>

        <!-- THANH TIẾN TRÌNH GIẢ LẬP ĐANG CHẠY (Progress Bar Animation) -->
        <div class="space-y-2">
            <div class="h-1.5 w-full bg-slate-800 rounded-full overflow-hidden relative border border-white/5">
                <div class="absolute h-full rounded-full bg-gradient-to-r from-shopee to-amber-400 w-1/3 animate-[shopee-progress_2.5s_ease-in-out_infinite]"></div>
            </div>
            <span class="text-[10px] text-slate-500 font-medium tracking-wide">Đang đồng bộ hóa dữ liệu và tối ưu hóa máy chủ...</span>
        </div>

        <div class="border-t border-white/5 my-6"></div>

        <!-- THÔNG TIN LIÊN HỆ & HỖ TRỢ KHÁCH HÀNG -->
        <div class="space-y-4">
            <p class="text-xs font-semibold text-slate-400">Nếu bạn cần hỗ trợ khẩn cấp, vui lòng liên hệ:</p>
            <div class="flex items-center justify-center gap-3">
                <!-- Nút Gọi Hotline -->
                @if($hotline = \App\Models\Setting::getVal('support_hotline'))
                <a href="tel:{{ str_replace(' ', '', $hotline) }}" class="flex items-center gap-2 px-4 py-2 text-xs font-semibold rounded-xl bg-white/5 hover:bg-white/10 border border-white/10 hover:border-white/20 transition-all text-slate-200">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h2.28a1 1 0 01.63.23l2.87 2.87a1 1 0 010 1.41L9.27 8.05a10.02 10.02 0 005.65 5.65l1.53-1.53a1 1 0 011.41 0l2.87 2.87a1 1 0 01.23.63V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                    </svg>
                    <span>{{ $hotline }}</span>
                </a>
                @endif

                <!-- Nút Gửi Email -->
                @if($email = \App\Models\Setting::getVal('support_email'))
                <a href="mailto:{{ $email }}" class="flex items-center gap-2 px-4 py-2 text-xs font-semibold rounded-xl bg-white/5 hover:bg-white/10 border border-white/10 hover:border-white/20 transition-all text-slate-200">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                    <span>Email Hỗ Trợ</span>
                </a>
                @endif
            </div>
        </div>

        <!-- NÚT TẢI LẠI TRANG (Reload Page Button) -->
        <div class="pt-2">
            <button onclick="window.location.reload()" class="px-5 py-2.5 text-xs font-bold bg-gradient-to-r from-shopee to-orange-500 hover:from-orange-500 hover:to-shopee text-white rounded-xl shadow-lg shadow-shopee/10 transition-all duration-300 hover:scale-[1.03] active:scale-[0.98] cursor-pointer flex items-center justify-center gap-2 mx-auto">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 1121.21 7.89H18v3" />
                </svg>
                <span>Kiểm tra lại trang web</span>
            </button>
        </div>
    </div>

    <!-- FOOTER -->
    <div class="z-10 text-center">
        <p class="text-[10px] font-bold text-slate-600 uppercase tracking-widest">
            &copy; {{ date('Y') }} {{ \App\Models\Setting::getVal('site_name', request()->getHost()) }} - All rights reserved
        </p>
    </div>

    <!-- Tự tạo Keyframe cho thanh tiến trình chạy vô hạn của Shopee -->
    <style>
        @keyframes shopee-progress {
            0% { left: -35%; }
            100% { left: 100%; }
        }
    </style>
</body>
</html>
