<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $productName }}</title>

    <!-- Thẻ Open Graph dùng cho Facebook, Zalo, Telegram... -->
    <meta property="og:type" content="website">
    <meta property="og:title" content="{{ $productName }}">
    <meta property="og:description" content="{{ $description }}">
    <meta property="og:image" content="{{ $productImage }}">
    <meta property="og:url" content="{{ $currentUrl }}">
    <meta property="og:site_name" content="{{ $siteName }}">

    <!-- Thẻ Twitter Card dùng cho Twitter/X -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $productName }}">
    <meta name="twitter:description" content="{{ $description }}">
    <meta name="twitter:image" content="{{ $productImage }}">

    <!-- Tự động chuyển hướng bằng HTML Meta Refresh dự phòng -->
    <meta http-equiv="refresh" content="0; url={{ $destinationUrl }}">

    <!-- Favicon của hệ thống -->
    <link rel="icon" type="image/png" href="{{ asset($siteFavicon) }}">

    <!-- Phong cách thiết kế hiện đại, cao cấp phòng trường hợp hiển thị lỗi hoặc load chậm -->
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background-color: #0f172a;
            color: #f1f5f9;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            text-align: center;
        }
        .container {
            max-width: 450px;
            padding: 2.5rem;
            background: rgba(30, 41, 59, 0.7);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 24px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }
        .logo {
            font-weight: 800;
            font-size: 1.5rem;
            letter-spacing: -0.025em;
            color: #ff4d2d;
            margin-bottom: 1.5rem;
        }
        .loader {
            position: relative;
            width: 64px;
            height: 64px;
            margin: 0 auto 2rem;
        }
        .loader-circle {
            box-sizing: border-box;
            display: block;
            position: absolute;
            width: 64px;
            height: 64px;
            border: 6px solid #ff4d2d;
            border-radius: 50%;
            animation: loader-spin 1.2s cubic-bezier(0.5, 0, 0.5, 1) infinite;
            border-color: #ff4d2d transparent transparent transparent;
        }
        @keyframes loader-spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        h1 {
            font-size: 1.25rem;
            margin-bottom: 1rem;
            color: #f8fafc;
        }
        p {
            font-size: 0.875rem;
            color: #94a3b8;
            margin-bottom: 1.5rem;
            line-height: 1.5;
        }
        .btn {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            font-size: 0.875rem;
            font-weight: 600;
            color: #ffffff;
            background-color: #ff4d2d;
            border: none;
            border-radius: 12px;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .btn:hover {
            background-color: #e03d1a;
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">{{ $siteName }}</div>
        <div class="loader">
            <div class="loader-circle"></div>
        </div>
        <h1>{{ __('Đang chuyển hướng...') }}</h1>
        <p>{{ __('Hệ thống đang chuyển hướng bạn đến liên kết sản phẩm. Nếu trình duyệt không tự chuyển hướng, vui lòng bấm vào nút bên dưới.') }}</p>
        <a href="{{ $destinationUrl }}" class="btn">{{ __('Đến Cửa Hàng') }}</a>
    </div>

    <!-- Chuyển hướng bằng Javascript lập tức -->
    <script>
        // Thực hiện chuyển hướng ngay khi DOM đã sẵn sàng
        document.addEventListener("DOMContentLoaded", function() {
            window.location.href = "{{ $destinationUrl }}";
        });
    </script>
</body>
</html>
