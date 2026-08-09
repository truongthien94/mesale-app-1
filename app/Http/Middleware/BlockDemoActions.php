<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BlockDemoActions
{
    /**
     * Xử lý kiểm tra và chặn các hành động thay đổi dữ liệu khi đang ở chế độ Demo (APP_DEMO = true).
     * 
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Kiểm tra xem hệ thống có đang bật chế độ Demo (APP_DEMO = true) hay không
        if (config('app.demo')) {
            
            // A. Đối với khu vực quản trị (Admin Panel): 
            // Lấy dynamic admin prefix từ cấu hình hệ thống hoặc mặc định là 'admin'
            // Chặn tất cả các thao tác thay đổi dữ liệu (POST, PUT, PATCH, DELETE)
            $adminPrefix = \App\Models\Setting::getVal('admin_prefix', 'admin');
            if ($request->is($adminPrefix) || $request->is($adminPrefix . '/*')) {
                // Cho phép các tiến trình cập nhật hệ thống (check, apply, unlock, save license) hoạt động bình thường ở chế độ Demo theo yêu cầu của sếp Thành
                // Ngoại trừ hành động bật/tắt nhanh chế độ tự động cập nhật hệ thống
                if ($request->route() && str_starts_with($request->route()->getName(), 'admin.update.') && $request->route()->getName() !== 'admin.update.toggle-auto') {
                    return $next($request);
                }

                if (!$request->isMethod('GET') && !$request->isMethod('HEAD')) {
                    return $this->denyResponse($request);
                }
            }

            // B. Đối với khu vực trang khách hàng (Client Side):
            // Khóa các tính năng nhạy cảm như cập nhật tài khoản, đổi mật khẩu, bảo mật 2FA, rút tiền, lấy link hoàn tiền và quên/reset mật khẩu
            $blockedRoutes = [
                'profile.update',              // Cập nhật thông tin cá nhân
                'profile.password',            // Thay đổi mật khẩu
                'withdraw.post',               // Gửi yêu cầu rút tiền
                'withdraw.send_otp',           // Yêu cầu gửi mã OTP rút tiền
                'payment_accounts.store',      // Lưu tài khoản nhận tiền vào sổ
                'payment_accounts.default',    // Đặt tài khoản nhận tiền mặc định
                'payment_accounts.destroy',    // Xoá tài khoản nhận tiền khỏi sổ
                'profile.2fa.enable',          // Bật bảo mật 2FA Google
                'profile.2fa.disable',         // Tắt bảo mật 2FA Google
                'profile.email_otp.enable',    // Bật bảo mật OTP qua Email
                'profile.email_otp.disable',   // Tắt bảo mật OTP qua Email
                'profile.logout_devices',      // Đăng xuất khỏi các thiết bị khác
                'profile.logout_session',      // Đăng xuất phiên làm việc cụ thể của tài khoản
                'profile.delete',              // Tự xóa tài khoản thành viên
                'gifts.store',                 // Thực hiện đổi quà tặng
                'blog.comment',                // Gửi bình luận bài viết blog
                'blog.like',                   // Thích bài viết blog
                'shopee.product',              // Khóa chức năng phân tích link và lấy link hoàn tiền
                'password.request',            // Yêu cầu quên mật khẩu
                'password.email',              // Gửi email reset mật khẩu
                'password.reset',              // Hiển thị form đặt lại mật khẩu
                'password.update',             // Cập nhật mật khẩu mới
            ];

            // Nếu route hiện tại nằm trong danh sách bị khóa, từ chối xử lý
            if ($request->route() && in_array($request->route()->getName(), $blockedRoutes)) {
                return $this->denyResponse($request);
            }
        }

        return $next($request);
    }

    /**
     * Trả về phản hồi từ chối thao tác dựa trên định dạng request (JSON hoặc Redirect).
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    private function denyResponse(Request $request): Response
    {
        $message = 'Hệ thống đang chạy ở chế độ Demo thử nghiệm. Bạn không thể thực hiện thao tác thay đổi dữ liệu này!';

        // Nếu là hành động dán link hoàn tiền tại trang chủ, trả về thông báo cụ thể hơn
        if ($request->route() && $request->route()->getName() === 'shopee.product') {
            $message = 'Hệ thống đang chạy ở chế độ Demo thử nghiệm. Tính năng lấy link hoàn tiền Shopee tạm thời bị khóa!';
        }

        // Trả về JSON nếu request là AJAX/API
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => false,
                'message' => $message
            ], 403);
        }

        // Trực tiếp redirect trở lại kèm thông báo lỗi cho người dùng
        return redirect()->back()->with('error', $message);
    }
}
