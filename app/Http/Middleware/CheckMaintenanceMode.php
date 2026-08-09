<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Setting;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware: CheckMaintenanceMode
 * Vai trò: Kiểm tra trạng thái bảo trì của hệ thống được cấu hình trong bảng settings.
 * Logic hoạt động:
 *   - Nếu chế độ bảo trì đang bật (maintenance_mode = 1):
 *     - Ngoại trừ các request thuộc Admin Panel (đầu URL bắt đầu bằng "admin" hoặc "admin/*") để admin luôn đăng nhập/truy cập cấu hình được.
 *     - Ngoại trừ người dùng đã đăng nhập và có vai trò là quản trị viên (role === 'admin').
 *     - Mọi truy cập khác sẽ bị chuyển tới trang thông báo lỗi bảo trì với HTTP status code 503.
 */
class CheckMaintenanceMode
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Kiểm tra cấu hình bảo trì từ database
        if (Setting::getVal('maintenance_mode', '0') === '1') {
            
            // Cho phép các request vào admin panel, trang đăng nhập/đăng xuất (để admin có thể đăng nhập/đăng xuất và tắt bảo trì) hoặc tài khoản quản trị viên đã đăng nhập
            if ($request->is('admin') || $request->is('admin/*') || $request->is('login') || $request->is('login/*') || $request->is('logout') || (auth()->check() && auth()->user()->role === 'admin')) {
                return $next($request);
            }

            // Trả về view thông báo bảo trì errors.maintenance kèm theo mã 503 (Service Unavailable)
            $message = Setting::getVal('maintenance_message', 'Hệ thống đang bảo trì nâng cấp định kỳ. Vui lòng quay lại sau!');
            return response()->view('errors.maintenance', compact('message'), 503);
        }

        return $next($request);
    }
}
