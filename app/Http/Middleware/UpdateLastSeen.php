<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class UpdateLastSeen
{
    /**
     * Handle an incoming request.
     * Middleware cập nhật thời gian hoạt động (online) gần nhất của người dùng.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Kiểm tra xem người dùng đã đăng nhập chưa
        if (auth()->check()) {
            $user = auth()->user();
            
            // Chỉ cập nhật nếu trường last_seen_at chưa có dữ liệu hoặc đã qua 1 phút từ lần cập nhật trước
            // Điều này giúp tối ưu hóa hiệu năng, giảm số lượng truy vấn UPDATE liên tục vào database
            if (!$user->last_seen_at || $user->last_seen_at->diffInMinutes(now()) >= 1) {
                $user->update([
                    'last_seen_at' => now()
                ]);
            }
        }

        return $next($request);
    }
}
