<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class ForgotPasswordController extends Controller
{
    /**
     * Hiển thị giao diện nhập email để yêu cầu lấy lại mật khẩu.
     */
    public function showLinkRequestForm()
    {
        return view('auth.passwords.email');
    }

    /**
     * Gửi email chứa link đặt lại mật khẩu cho người dùng.
     */
    public function sendResetLinkEmail(Request $request)
    {
        // Kiểm tra Cloudflare Turnstile Captcha nếu được bật
        if (\App\Models\Setting::getVal('turnstile_status', '0') === '1' && \App\Models\Setting::getVal('turnstile_on_forgot_password', '0') === '1') {
            $token = $request->input('cf-turnstile-response');
            if (!\App\Services\TurnstileService::verify($token, $request->ip())) {
                return back()->withErrors([
                    'cf-turnstile-response' => 'Xác thực Captcha không hợp lệ. Vui lòng thử lại.',
                ])->withInput();
            }
        }

        $request->validate(['email' => 'required|email']);

        // Chống spam quên mật khẩu: Giới hạn tối đa 3 lần/phút theo IP để tránh lạm dụng
        $throttleKey = 'forgot-password:' . $request->ip();
        if (\Illuminate\Support\Facades\RateLimiter::tooManyAttempts($throttleKey, 3)) {
            $seconds = \Illuminate\Support\Facades\RateLimiter::availableIn($throttleKey);
            return back()->with('error', "Bạn đã gửi yêu cầu quá nhiều lần. Vui lòng thử lại sau {$seconds} giây.");
        }
        \Illuminate\Support\Facades\RateLimiter::hit($throttleKey, 60);

        $user = User::where('email', $request->email)->first();

        // Trả về thông báo thành công chung để tăng bảo mật (không tiết lộ email có tồn tại hay không)
        if (!$user) {
            return back()->with('success', 'Nếu email tồn tại trong hệ thống, chúng tôi đã gửi liên kết đặt lại mật khẩu.');
        }

        // 1. Tạo token reset và lưu vào bảng password_reset_tokens
        $token = Str::random(60);
        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $request->email],
            [
                'token' => Hash::make($token),
                'created_at' => now()
            ]
        );

        $resetUrl = route('password.reset', ['token' => $token, 'email' => $request->email]);

        // 2. Gửi thư điện tử thông qua hàng đợi (Queue) để phản hồi nhanh hơn
        try {
            \App\Models\Setting::sendEmailQueue($request->email, 'forgot_password', [
                'name' => $user->name,
                'email' => $request->email,
                'reset_url' => $resetUrl
            ]);

            ActivityLog::log("Yêu cầu đặt lại mật khẩu cho email {$request->email}", $user->id);
        } catch (\Exception $e) {
            \Log::error("Failed to send password reset email: " . $e->getMessage());
            return back()->with('error', 'Có lỗi xảy ra khi tạo hàng đợi gửi email.');
        }

        return back()->with('success', 'Chúng tôi đã gửi liên kết đặt lại mật khẩu vào hòm thư điện tử của bạn.');
    }

    /**
     * Hiển thị giao diện đặt lại mật khẩu mới.
     */
    public function showResetForm(Request $request, $token = null)
    {
        return view('auth.passwords.reset')->with([
            'token' => $token,
            'email' => $request->email
        ]);
    }

    /**
     * Xử lý đặt lại mật khẩu mới.
     */
    public function reset(Request $request)
    {
        // 3. Validate dữ liệu mật khẩu mới
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        // 4. Xác thực email và token reset
        $record = DB::table('password_reset_tokens')->where('email', $request->email)->first();

        if (!$record || !Hash::check($request->token, $record->token)) {
            return back()->withErrors(['email' => 'Yêu cầu đặt lại mật khẩu đã hết hạn hoặc không hợp lệ.']);
        }

        // Kiểm tra xem token được tạo trong vòng 60 phút qua không
        $createdAt = \Carbon\Carbon::parse($record->created_at);
        if ($createdAt->addMinutes(60)->isPast()) {
            DB::table('password_reset_tokens')->where('email', $request->email)->delete();
            return back()->withErrors(['email' => 'Yêu cầu đặt lại mật khẩu đã hết hạn.']);
        }

        // 5. Cập nhật mật khẩu mới của người dùng và thu hồi các session hiện tại
        $user = User::where('email', $request->email)->first();
        if ($user) {
            $user->password = Hash::make($request->password);
            $user->setRememberToken(\Illuminate\Support\Str::random(60));
            $user->save();

            // Thu hồi toàn bộ phiên đăng nhập cũ của tài khoản này.
            // Quên mật khẩu là luồng khôi phục khi tài khoản có thể đã bị chiếm, nên phải vô hiệu hóa
            // mọi session đang hoạt động để kẻ tấn công không còn giữ được quyền truy cập sau khi nạn nhân đặt lại mật khẩu.
            DB::table('sessions')->where('user_id', $user->id)->delete();

            // Xoá token đã sử dụng
            DB::table('password_reset_tokens')->where('email', $request->email)->delete();

            ActivityLog::log('Đặt lại mật khẩu thành công qua email', $user->id);

            return redirect()->route('login')->with('success', 'Mật khẩu của bạn đã được cập nhật thành công. Vui lòng đăng nhập lại.');
        }

        return back()->withErrors(['email' => 'Không tìm thấy thông tin tài khoản người dùng.']);
    }
}
