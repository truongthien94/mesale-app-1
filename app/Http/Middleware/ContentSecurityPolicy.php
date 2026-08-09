<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ContentSecurityPolicy
{
    /**
     * Xử lý request gửi đến.
     * Logic nghiệp vụ: Cấu hình và bổ sung các header bảo mật (đặc biệt là Content Security Policy)
     * giúp trình duyệt chặn đứng các hành vi chèn scripts lạ từ bên ngoài (Stored XSS / MITM)
     * khi chạy ở môi trường thực tế (production).
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Chỉ kích hoạt khi chạy trên môi trường thực tế và dữ liệu trả về thuộc dạng Response có Header
        if (config('app.env') !== 'local' && $response instanceof Response && method_exists($response, 'headers')) {
            
            // Định nghĩa chính sách CSP:
            // - default-src 'self': Chỉ tải dữ liệu từ cùng domain gốc của website.
            // - script-src 'self' 'unsafe-inline' 'unsafe-eval' ...: Cho phép chạy JS nội bộ, inline và các scripts từ các cổng uy tín như Google Analytics, Facebook Pixel, Tawk.to chat.
            // - style-src: Cho phép tải CSS nội bộ, inline và Google Fonts.
            // - img-src: Cho phép tải ảnh nội bộ và ảnh qua giao thức HTTPS bảo mật hoặc Base64.
            // - font-src: Cho phép tải fonts từ thư mục lưu trữ cục bộ và Google Fonts.
            // - connect-src: Cho phép thực hiện API fetch/ajax nội bộ và tới các endpoint theo dõi hành vi uy tín.
            $csp = "default-src 'self'; "
                 . "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://*.google-analytics.com https://*.googletagmanager.com https://connect.facebook.net https://*.facebook.com https://*.tawk.to https://*.google.com https://www.gstatic.com; "
                 . "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://*.tawk.to; "
                 . "img-src 'self' data: https: android-app:; "
                 . "font-src 'self' data: https://fonts.gstatic.com https://*.tawk.to; "
                 . "connect-src 'self' https://*.google-analytics.com https://*.analytics.google.com https://*.googletagmanager.com https://*.facebook.com https://*.tawk.to wss://*.tawk.to; "
                 . "frame-src 'self' https://*.google.com https://*.facebook.com https://*.tawk.to; "
                 . "object-src 'none'; "
                 . "base-uri 'self';";

            // Thiết lập CSP Header vào phản hồi HTTP
            $response->headers->set('Content-Security-Policy', $csp);
            
            // Các HTTP Header bảo mật tiêu chuẩn giúp tăng điểm bảo mật SSL Labs
            $response->headers->set('X-XSS-Protection', '1; mode=block'); // Bật bộ lọc XSS tích hợp của trình duyệt cũ
            $response->headers->set('X-Content-Type-Options', 'nosniff'); // Ngăn chặn trình duyệt đoán định sai kiểu MIME của tài nguyên
            $response->headers->set('X-Frame-Options', 'SAMEORIGIN'); // Phòng chống Clickjacking bằng cách cấm nhúng website qua thẻ iframe của bên thứ ba
            $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin'); // Bảo vệ thông tin URL nội bộ khi chuyển hướng sang site khác
        }

        return $response;
    }
}
