<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DetectAppSession
{
    /**
     * Handle an incoming request.
     * Xử lý request đi vào: nhận diện nếu người dùng truy cập từ ứng dụng di động (app)
     * thông qua tham số query `?from=app` và ghi nhận vào session.
     * Điều này giúp ẩn đi phần đầu trang (header) và thanh điều hướng dưới (bottom navigation)
     * khi chạy bên trong Webview của app di động mà không ảnh hưởng đến người dùng duyệt web thông thường.
     *
     * @param  Request  $request
     * @param  Closure(Request): (Response)  $next
     * @return Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Nếu URL chứa tham số ?from=app, thiết lập session là đang chạy từ app
        if ($request->query('from') === 'app') {
            session(['from_app' => true]);
        } 
        // Hỗ trợ tham số ?from=web để xóa trạng thái app khi cần thử nghiệm hoặc chuyển về chế độ web thường
        elseif ($request->query('from') === 'web') {
            session()->forget('from_app');
        }

        return $next($request);
    }
}
