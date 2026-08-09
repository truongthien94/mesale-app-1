<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware kiểm soát trạng thái bật/tắt của nhóm API dành cho hệ thống ngoài (Bot).
 *
 * ĐỘC LẬP HOÀN TOÀN với hệ thống Open API (`openapi_status`):
 *  - Open API phục vụ chủ website tự build App Mobile, xác thực bằng token phiên đăng nhập.
 *  - Nhóm API này phục vụ Bot/hệ thống bên thứ ba của thành viên, xác thực bằng
 *    Khóa API Token cá nhân (users.api_token) lấy trong trang Hồ sơ.
 *
 * Nhờ tách riêng, quản trị viên có thể tắt toàn bộ Open API mà Bot của thành viên vẫn chạy bình thường,
 * và ngược lại. Công tắc điều khiển là cấu hình `api_docs_enabled` (mặc định BẬT).
 */
class BotApiEnabled
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Setting::getVal('api_docs_enabled', '1') !== '1') {
            return response()->json([
                'success' => false,
                'code' => 'API_DISABLED',
                'message' => __('Chức năng API hiện đang tắt. Vui lòng liên hệ quản trị viên.'),
            ], 503);
        }

        return $next($request);
    }
}
