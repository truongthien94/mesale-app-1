<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware chặn toàn bộ API khi hệ thống chạy ở chế độ Demo (APP_DEMO = true).
 *
 * Vai trò: bổ sung cho `BlockDemoActions` — middleware đó chỉ nằm trong nhóm `web` nên
 * không bảo vệ được các route API. Middleware này được gắn vào nhóm `api` để mọi endpoint
 * (Open API cho App Mobile, API cho hệ thống Bot bên ngoài và mọi endpoint thêm về sau)
 * đều ngừng phục vụ trên bản demo.
 *
 * Chặn TẤT CẢ phương thức, kể cả GET đọc dữ liệu, vì trên bản demo dữ liệu thành viên
 * là dữ liệu mẫu dùng chung — không nên cho phép người lạ truy vấn qua API.
 */
class BlockDemoApi
{
    public function handle(Request $request, Closure $next): Response
    {
        if (config('app.demo')) {
            return response()->json([
                'success' => false,
                'code' => 'DEMO_MODE',
                'message' => __('Hệ thống đang chạy ở chế độ Demo thử nghiệm nên toàn bộ API đã bị tạm khóa.'),
            ], 403);
        }

        return $next($request);
    }
}
