<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IsAdmin
{
    /**
     * Handle an incoming request.
     * Xử lý kiểm tra quyền Admin và trạng thái của tài khoản.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Kiểm tra xem người dùng đã đăng nhập chưa
        if (!auth()->check()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => __('Bạn cần đăng nhập để truy cập trang này.')
                ], 401);
            }
            return redirect()->route('login')->with('error', __('Bạn cần đăng nhập để truy cập trang này.'));
        }

        // 2. Kiểm tra vai trò quản trị viên
        if (auth()->user()->role !== 'admin') {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => __('Bạn không có quyền truy cập trang quản trị.')
                ], 403);
            }
            return redirect()->route('home')->with('error', __('Bạn không có quyền truy cập trang quản trị.'));
        }

        // 3. Kiểm tra trạng thái hoạt động của tài khoản
        if (auth()->user()->status !== 'active') {
            auth()->logout();
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => __('Tài khoản của bạn đã bị tạm khoá.')
                ], 403);
            }
            return redirect()->route('login')->with('error', __('Tài khoản của bạn đã bị tạm khoá.'));
        }

        return $next($request);
    }
}
