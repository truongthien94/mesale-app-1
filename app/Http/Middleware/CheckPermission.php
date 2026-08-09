<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Handle an incoming request.
     * Kiểm tra quyền cụ thể của tài khoản trước khi vào route quản trị.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string $permission Tên quyền cần kiểm tra (ví dụ: 'manage_settings')
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        // 1. Kiểm tra đăng nhập
        if (!auth()->check()) {
            return redirect()->route('login')->with('error', 'Bạn cần đăng nhập để truy cập.');
        }

        // 2. Kiểm tra quyền của Admin
        if (!auth()->user()->hasPermission($permission)) {
            // Nếu không có quyền, chuyển hướng về trang admin dashboard và báo lỗi
            return redirect()->route('admin.dashboard')->with('error', 'Bạn không có quyền thực hiện chức năng này.');
        }

        return $next($request);
    }
}
