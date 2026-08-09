<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IsActive
{
    /**
     * Handle an incoming request.
     * Xử lý kiểm tra tài khoản còn hoạt động hay không.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Kiểm tra trạng thái tài khoản nếu người dùng đã đăng nhập
        if (auth()->check()) {
            $user = auth()->user();
            
            if ($user->status !== 'active') {
                auth()->logout();
                return redirect()->route('login')->with('error', __('Tài khoản của bạn đã bị tạm khoá.'));
            }

            // Nếu bắt buộc xác minh Email và tài khoản chưa xác minh email
            if (\App\Models\Setting::getVal('email_verification_enabled', '0') === '1' && is_null($user->email_verified_at)) {
                // Tự động sinh OTP mới và gửi email xác minh nếu chưa có OTP hoặc OTP hết hạn
                if (!$user->otp_code || !$user->otp_expires_at || $user->otp_expires_at->isPast()) {
                    $otp = random_int(100000, 999999);
                    $user->otp_code = \Illuminate\Support\Facades\Hash::make($otp);
                    $user->otp_expires_at = now()->addMinutes(10); // OTP có hiệu lực 10 phút
                    $user->save();

                    try {
                        \App\Models\Setting::sendEmail($user->email, 'verify_email', [
                            'name' => $user->name,
                            'email' => $user->email,
                            'otp' => $otp
                        ]);
                    } catch (\Exception $e) {
                        \Log::error('Lỗi gửi email xác minh qua middleware: ' . $e->getMessage());
                    }
                }
                
                // Đăng xuất và đưa ID người dùng vào session chờ xác thực
                session(['email_verification_user_id' => $user->id]);
                auth()->logout();
                
                return redirect()->route('register.verification')->with('error', __('Vui lòng xác minh địa chỉ email của bạn trước khi truy cập hệ thống.'));
            }
        }

        return $next($request);
    }
}
