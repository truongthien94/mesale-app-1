<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use App\Models\User;
use App\Models\Referral;
use App\Models\Setting;
use App\Services\ReferralOnboardingService;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Hash;

class LoginController extends Controller
{
    public function __construct(private readonly ReferralOnboardingService $referralOnboarding) {}

    /**
     * Hiển thị giao diện đăng nhập.
     */
    public function showLoginForm()
    {
        // 1. Kiểm tra danh sách địa chỉ IP bị cấm thủ công
        $bannedIpsRaw = \App\Models\Setting::getVal('banned_ips', '');
        if (!empty($bannedIpsRaw)) {
            $bannedIps = preg_split('/[\s,]+/', trim($bannedIpsRaw));
            if (in_array(request()->ip(), $bannedIps)) {
                abort(403, 'Địa chỉ IP của bạn đã bị cấm truy cập hệ thống.');
            }
        }

        // 2. Kiểm tra địa chỉ IP có bị khóa tạm thời trong Cache hay không
        if (\App\Models\Setting::getVal('ip_lockout_status', '0') === '1') {
            if (\Illuminate\Support\Facades\Cache::has("lockout_ip:" . request()->ip())) {
                abort(403, 'Địa chỉ IP của bạn bị khóa tạm thời do nhập sai mật khẩu quá nhiều lần. Vui lòng quay lại sau.');
            }
        }

        // Nếu đã đăng nhập, chuyển hướng về trang chủ thay vì dashboard
        if (Auth::check()) {
            return redirect()->route('home');
        }
        return view('auth.login');
    }

    /**
     * Xử lý yêu cầu đăng nhập.
     * Kiểm tra thông tin, nếu người dùng đã bật bảo mật hai lớp (2FA/OTP) thì chuyển hướng họ đến trang nhập mã xác thực thay vì đăng nhập trực tiếp.
     */
    public function login(Request $request)
    {
        // 0. Chặn đăng nhập bằng Email/Mật khẩu nếu admin đã tắt form này (chỉ cho phép đăng nhập bằng Google)
        // Fallback an toàn: chỉ chặn khi đăng nhập Google đang bật, tránh khóa toàn bộ hệ thống đăng nhập.
        if (
            \App\Models\Setting::getVal('email_auth_enabled', '1') === '0'
            && \App\Models\Setting::getVal('google_login_enabled', '0') === '1'
        ) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => __('Hệ thống hiện chỉ cho phép đăng nhập bằng Google.')
                ], 403);
            }
            return redirect()->route('login')->with('error', __('Hệ thống hiện chỉ cho phép đăng nhập bằng Google.'));
        }

        // 1. Kiểm tra danh sách địa chỉ IP bị cấm thủ công
        $bannedIpsRaw = \App\Models\Setting::getVal('banned_ips', '');
        if (!empty($bannedIpsRaw)) {
            // Tách danh sách IP dựa trên khoảng trắng, dấu phẩy hoặc dòng mới
            $bannedIps = preg_split('/[\s,]+/', trim($bannedIpsRaw));
            if (in_array($request->ip(), $bannedIps)) {
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => __('Địa chỉ IP của bạn đã bị cấm truy cập hệ thống đăng nhập.')
                    ], 403);
                }
                return back()->withErrors([
                    'email' => 'Địa chỉ IP của bạn đã bị cấm truy cập hệ thống đăng nhập.',
                ]);
            }
        }

        // 2. Kiểm tra địa chỉ IP có bị khóa tạm thời trong Cache hay không
        if (\App\Models\Setting::getVal('ip_lockout_status', '0') === '1') {
            if (\Illuminate\Support\Facades\Cache::has("lockout_ip:{$request->ip()}")) {
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => __('Địa chỉ IP của bạn bị khóa tạm thời do nhập sai mật khẩu quá nhiều lần. Vui lòng quay lại sau.')
                    ], 403);
                }
                return back()->withErrors([
                    'email' => 'Địa chỉ IP của bạn bị khóa tạm thời do nhập sai mật khẩu quá nhiều lần. Vui lòng quay lại sau.',
                ]);
            }
        }

        // Kiểm tra Cloudflare Turnstile Captcha nếu được bật
        if (\App\Models\Setting::getVal('turnstile_status', '0') === '1' && \App\Models\Setting::getVal('turnstile_on_login', '0') === '1') {
            $token = $request->input('cf-turnstile-response');
            if (!\App\Services\TurnstileService::verify($token, $request->ip())) {
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => __('Xác thực Captcha không hợp lệ. Vui lòng thử lại.')
                    ], 422);
                }
                return back()->withErrors([
                    'cf-turnstile-response' => 'Xác thực Captcha không hợp lệ. Vui lòng thử lại.',
                ])->withInput();
            }
        }

        // 3. Validate dữ liệu đầu vào (Email hoặc Số điện thoại, kèm Mật khẩu)
        // Ô đăng nhập dùng chung cho cả hai định dạng nên chỉ kiểm tra là chuỗi ký tự, việc phân loại do User::findByLogin đảm nhiệm
        $request->validate([
            'email' => 'required|string|max:255',
            'password' => 'required|string',
        ], [
            'email.required' => __('Vui lòng nhập Email hoặc Số điện thoại của bạn.'),
            'password.required' => __('Vui lòng nhập mật khẩu.'),
        ]);

        $remember = $request->has('remember');

        // Chuẩn hoá thông tin đăng nhập thành một khoá duy nhất để đếm số lần thử sai,
        // tránh việc cùng một số điện thoại nhập ở nhiều định dạng (0xxx, +84xxx, có khoảng trắng) lại được tính là các khoá khác nhau
        $loginInput = trim((string) $request->input('email'));
        $loginKey = filter_var($loginInput, FILTER_VALIDATE_EMAIL)
            ? Str::lower($loginInput)
            : (User::normalizePhone($loginInput) ?? Str::lower($loginInput));

        $throttleKey = $loginKey . '|' . $request->ip();

        // Lấy cấu hình giới hạn đăng nhập từ cơ sở dữ liệu
        $loginLockoutStatus = \App\Models\Setting::getVal('login_lockout_status', '1') === '1';
        $maxAttempts = $loginLockoutStatus ? (int)\App\Models\Setting::getVal('login_max_attempts', 5) : 5;
        $lockoutMinutes = $loginLockoutStatus ? (int)\App\Models\Setting::getVal('login_lockout_duration', 15) : 15;
        $lockoutSeconds = $lockoutMinutes * 60;

        // 4. Chống brute force login bằng Rate Limiter (dựa trên email và IP kết hợp)
        if (RateLimiter::tooManyAttempts($throttleKey, $maxAttempts)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            $timeStr = $seconds > 60 ? ceil($seconds / 60) . ' phút' : $seconds . ' giây';
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => __("Tài khoản hoặc IP tạm khoá đăng nhập. Vui lòng thử lại sau :time.", ['time' => $timeStr])
                ], 429);
            }
            return back()->withErrors([
                'email' => "Tài khoản hoặc IP tạm khoá đăng nhập. Vui lòng thử lại sau {$timeStr}.",
            ]);
        }

        // 5. Thực hiện kiểm tra thông tin đăng nhập trước khi đăng nhập chính thức để lọc bước 2FA/OTP
        // Tìm tài khoản theo Email HOẶC Số điện thoại rồi tự đối chiếu mật khẩu đã băm (thay cho Auth::validate chỉ hỗ trợ email)
        $user = User::findByLogin($request->input('email'));

        if ($user && Hash::check($request->password, $user->password)) {
            // Kiểm tra trạng thái hoạt động của tài khoản
            if ($user->status !== 'active') {
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => __('Tài khoản của bạn đã bị khoá hoặc ngưng hoạt động.')
                    ], 403);
                }
                return back()->withErrors([
                    'email' => 'Tài khoản của bạn đã bị khoá hoặc ngưng hoạt động.',
                ]);
            }

            // Kiểm tra xem cấu hình bắt buộc xác minh Email khi đăng ký có bật không và user đã xác minh chưa
            // Lưu ý: Tài khoản đăng ký bằng Số điện thoại chưa có email nên được bỏ qua bước xác minh này
            if (\App\Models\Setting::getVal('email_verification_enabled', '0') === '1' && is_null($user->email_verified_at) && !empty($user->email)) {
                // Sinh mã OTP xác thực 6 chữ số
                $otp = random_int(100000, 999999);
                $user->otp_code = \Illuminate\Support\Facades\Hash::make($otp);
                $user->otp_expires_at = now()->addMinutes(10); // OTP có hiệu lực trong 10 phút
                $user->save();

                // Gửi email chứa mã OTP xác minh tài khoản cho thành viên
                try {
                    \App\Models\Setting::sendEmail($user->email, 'verify_email', [
                        'name' => $user->name,
                        'email' => $user->email,
                        'otp' => $otp
                    ]);
                } catch (\Exception $e) {
                    \Log::error('Lỗi gửi email xác minh khi đăng nhập: ' . $e->getMessage());
                }

                // Lưu ID của user vào session tạm thời để phục vụ xác minh
                session(['email_verification_user_id' => $user->id]);

                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json([
                        'success' => true,
                        'redirect' => route('register.verification'),
                        'message' => __('Tài khoản của bạn chưa được kích hoạt. Vui lòng xác minh email để kích hoạt.')
                    ]);
                }
                return redirect()->route('register.verification')->with('success', __('Tài khoản của bạn chưa được kích hoạt. Vui lòng xác minh email để kích hoạt.'));
            }

            // Kiểm tra xem tài khoản có kích hoạt một trong các tính năng bảo mật hai lớp không
            if ($user->google2fa_enabled || $user->email_otp_enabled) {
                // Lưu tạm thời ID người dùng và tùy chọn ghi nhớ vào session tạm thời có kèm thời gian hết hạn 10 phút
                session([
                    'auth_2fa_user_id' => $user->id,
                    'auth_2fa_remember' => $remember,
                    'auth_2fa_expires_at' => now()->addMinutes(10)->timestamp
                ]);

                // Nếu người dùng bật OTP qua email, tự động gửi mã OTP xác thực sang hòm thư của họ ngay lập tức
                if ($user->email_otp_enabled) {
                    // Sinh mã OTP bảo mật 6 chữ số (CSPRNG đảm bảo entropy cao)
                    $otp = random_int(100000, 999999);
                    // Mã hóa OTP trước khi lưu vào DB để chống rò rỉ dữ liệu
                    $user->otp_code = \Illuminate\Support\Facades\Hash::make($otp);
                    $user->otp_expires_at = now()->addMinutes(10);
                    $user->save();

                    try {
                        \App\Models\Setting::sendEmail($user->email, 'otp', [
                            'name' => $user->name,
                            'email' => $user->email,
                            'otp' => $otp
                        ]);
                    } catch (\Exception $e) {
                        \Log::error('Lỗi gửi mã OTP đăng nhập: ' . $e->getMessage());
                    }
                }

                // Xoá lịch sử thử đăng nhập sai trước đó do thông tin đã hợp lệ
                RateLimiter::clear($throttleKey);
                \Illuminate\Support\Facades\Cache::forget('ip_attempts:' . $request->ip());
                \Illuminate\Support\Facades\Cache::forget('email_attempts:' . $loginKey);

                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json([
                        'success' => true,
                        'redirect' => route('login.verification'),
                        'message' => __('Vui lòng nhập mã xác thực bảo mật.')
                    ]);
                }
                // Chuyển hướng sang giao diện nhập mã xác thực
                return redirect()->route('login.verification');
            }

            // Nếu không bật bảo mật 2 lớp, tiến hành đăng nhập trực tiếp bình thường
            Auth::login($user, $remember);

            // Xoá lịch sử thử đăng nhập sai
            RateLimiter::clear($throttleKey);
            \Illuminate\Support\Facades\Cache::forget('ip_attempts:' . $request->ip());
            \Illuminate\Support\Facades\Cache::forget('email_attempts:' . $loginKey);

            // Tạo lại session ID chính thức để chống tấn công Session Fixation
            $request->session()->regenerate();

            // Ghi nhật ký đăng nhập của người dùng
            ActivityLog::log('Đăng nhập thành công', $user->id);

            // Phân quyền chuyển hướng người dùng sau khi đăng nhập: Admin về admin dashboard, User thường về trang chủ
            $redirectUrl = $user->isAdmin() ? route('admin.dashboard') : route('home');
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'redirect' => $redirectUrl,
                    'message' => __('Đăng nhập thành công!')
                ]);
            }

            if ($user->isAdmin()) {
                return redirect()->intended(route('admin.dashboard'));
            }

            // Chuyển hướng trực tiếp thành viên thường về trang chủ sau khi đăng nhập thành công
            return redirect()->route('home');
        }

        // 6. Nếu thông tin tài khoản hoặc mật khẩu bị sai, tăng số lần thử để kích hoạt khoá tạm thời
        RateLimiter::hit($throttleKey, $lockoutSeconds);

        // Tăng số lần thử sai của địa chỉ IP để tự động khóa IP nếu đạt giới hạn
        if (\App\Models\Setting::getVal('ip_lockout_status', '0') === '1') {
            $ipAttemptsKey = 'ip_attempts:' . $request->ip();
            $ipAttempts = (int)\Illuminate\Support\Facades\Cache::get($ipAttemptsKey, 0) + 1;
            \Illuminate\Support\Facades\Cache::put($ipAttemptsKey, $ipAttempts, now()->addMinutes($lockoutMinutes));

            if ($ipAttempts >= $maxAttempts) {
                $ipLockoutHours = (int)\App\Models\Setting::getVal('ip_lockout_duration', 24);
                \Illuminate\Support\Facades\Cache::put("lockout_ip:{$request->ip()}", true, now()->addHours($ipLockoutHours));
                ActivityLog::log("Hệ thống tự động khóa IP {$request->ip()} do đăng nhập sai liên tục {$ipAttempts} lần.", null);
            }
        }

        // Tăng số lần thử sai của tài khoản Email để tự động khóa tài khoản nếu đạt giới hạn
        if (\App\Models\Setting::getVal('account_lockout_status', '0') === '1') {
            $emailAttemptsKey = 'email_attempts:' . $loginKey;
            $emailAttempts = (int)\Illuminate\Support\Facades\Cache::get($emailAttemptsKey, 0) + 1;
            \Illuminate\Support\Facades\Cache::put($emailAttemptsKey, $emailAttempts, now()->addMinutes($lockoutMinutes));

            $accountMaxAttempts = (int)\App\Models\Setting::getVal('account_lockout_max_attempts', 10);
            if ($emailAttempts >= $accountMaxAttempts) {
                // Sử dụng lại tài khoản đã được dò tìm theo Email hoặc Số điện thoại ở bước xác thực phía trên
                if ($user && $user->status === 'active') {
                    $user->status = 'suspended';
                    $user->save();
                    ActivityLog::log("Tài khoản {$loginKey} bị khóa vĩnh viễn (status=suspended) do đăng nhập sai liên tục {$emailAttempts} lần.", $user->id);

                    // Xóa các bộ đếm tạm thời
                    \Illuminate\Support\Facades\Cache::forget($emailAttemptsKey);

                    if ($request->expectsJson() || $request->ajax()) {
                        return response()->json([
                            'success' => false,
                            'message' => __('Tài khoản của bạn đã bị khóa tự động do nhập sai mật khẩu quá nhiều lần.')
                        ], 403);
                    }
                    return back()->withErrors([
                        'email' => 'Tài khoản của bạn đã bị khóa tự động do nhập sai mật khẩu quá nhiều lần.',
                    ]);
                }
            }
        }

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => false,
                'message' => __('Thông tin đăng nhập hoặc mật khẩu không chính xác.')
            ], 422);
        }

        return back()->withErrors([
            'email' => 'Thông tin đăng nhập hoặc mật khẩu không chính xác.',
        ]);
    }

    /**
     * Hiển thị giao diện nhập mã xác thực bảo mật 2 lớp.
     */
    public function showVerificationForm()
    {
        // Yêu cầu bắt buộc phải có session tạm thời của tài khoản chờ xác thực và chưa bị hết hạn 10 phút
        if (!session()->has('auth_2fa_user_id') || !session()->has('auth_2fa_expires_at') || session('auth_2fa_expires_at') < now()->timestamp) {
            session()->forget(['auth_2fa_user_id', 'auth_2fa_remember', 'auth_2fa_expires_at']);
            return redirect()->route('login')->with('error', 'Phiên xác thực 2FA đã hết hạn. Vui lòng đăng nhập lại.');
        }

        $user = \App\Models\User::find(session('auth_2fa_user_id'));

        if (!$user) {
            return redirect()->route('login');
        }

        return view('auth.verification', compact('user'));
    }

    /**
     * Xử lý kiểm tra mã OTP/2FA để tiến hành đăng nhập chính thức.
     */
    public function verify(Request $request)
    {
        // Yêu cầu bắt buộc phải có session tạm thời của tài khoản chờ xác thực và chưa bị hết hạn 10 phút
        if (!session()->has('auth_2fa_user_id') || !session()->has('auth_2fa_expires_at') || session('auth_2fa_expires_at') < now()->timestamp) {
            session()->forget(['auth_2fa_user_id', 'auth_2fa_remember', 'auth_2fa_expires_at']);
            return redirect()->route('login')->with('error', 'Phiên xác thực 2FA đã hết hạn. Vui lòng đăng nhập lại.');
        }

        $user = \App\Models\User::find(session('auth_2fa_user_id'));

        if (!$user) {
            return redirect()->route('login');
        }

        // Chống brute force: Giới hạn tối đa 5 lần thử trong 10 phút (600 giây)
        $throttleKey = 'verify-2fa:' . $user->id;
        if (\Illuminate\Support\Facades\RateLimiter::tooManyAttempts($throttleKey, 5)) {
            // Hủy phiên đăng nhập tạm thời và xoá OTP khi bị brute force liên tục
            session()->forget(['auth_2fa_user_id', 'auth_2fa_remember', 'auth_2fa_expires_at']);
            $user->otp_code = null;
            $user->otp_expires_at = null;
            $user->save();
            \Illuminate\Support\Facades\RateLimiter::clear($throttleKey);
            return redirect()->route('login')->with('error', 'Bạn đã nhập sai mã xác thực quá nhiều lần. Vui lòng đăng nhập lại.');
        }

        // Xác định quy tắc validate tuỳ thuộc vào các lớp bảo mật tài khoản đã bật
        $rules = [];
        $messages = [];

        if ($user->google2fa_enabled) {
            $rules['google2fa_code'] = 'required|string|size:6';
            $messages['google2fa_code.required'] = 'Vui lòng nhập mã Google Authenticator.';
            $messages['google2fa_code.size'] = 'Mã Google Authenticator phải gồm đúng 6 chữ số.';
        }

        if ($user->email_otp_enabled) {
            $rules['email_otp_code'] = 'required|string|size:6';
            $messages['email_otp_code.required'] = 'Vui lòng nhập mã OTP Email.';
            $messages['email_otp_code.size'] = 'Mã OTP Email phải gồm đúng 6 chữ số.';
        }

        $request->validate($rules, $messages);

        // 1. Xác thực mã Google Authenticator
        if ($user->google2fa_enabled) {
            $google2fa = new \PragmaRX\Google2FA\Google2FA();
            try {
                $decryptedSecret = \Illuminate\Support\Facades\Crypt::decryptString($user->google2fa_secret);
                $isValid2fa = $google2fa->verifyKey($decryptedSecret, $request->google2fa_code);
            } catch (\Exception $e) {
                $isValid2fa = false;
            }
            if (!$isValid2fa) {
                // Ghi nhận lượt thử sai để tránh brute force
                \Illuminate\Support\Facades\RateLimiter::hit($throttleKey, 600);
                return back()->withErrors(['google2fa_code' => 'Mã xác thực Google Authenticator không chính xác.']);
            }
        }

        // 2. Xác thực mã OTP qua Email bằng cách kiểm tra so sánh Hash
        if ($user->email_otp_enabled) {
            if (!$user->otp_code || !\Illuminate\Support\Facades\Hash::check($request->email_otp_code, $user->otp_code) || !$user->otp_expires_at || $user->otp_expires_at->isPast()) {
                // Ghi nhận lượt thử sai để tránh brute force
                \Illuminate\Support\Facades\RateLimiter::hit($throttleKey, 600);
                return back()->withErrors(['email_otp_code' => 'Mã OTP Email không chính xác hoặc đã hết hiệu lực.']);
            }
        }

        // Xoá số lần thử sai khi xác thực thành công
        \Illuminate\Support\Facades\RateLimiter::clear($throttleKey);

        // 3. Đăng nhập chính thức người dùng sau khi vượt qua tất cả các lớp xác minh
        $remember = session('auth_2fa_remember', false);
        Auth::login($user, $remember);

        // Dọn dẹp dữ liệu OTP tạm thời để tránh rò rỉ mã
        if ($user->email_otp_enabled) {
            $user->otp_code = null;
            $user->otp_expires_at = null;
            $user->save();
        }

        // Xoá bỏ các session tạm thời
        session()->forget(['auth_2fa_user_id', 'auth_2fa_remember', 'auth_2fa_expires_at']);

        // Tạo lại session chính thức
        $request->session()->regenerate();

        ActivityLog::log('Đăng nhập thành công (Đã xác minh bảo mật 2 lớp)', $user->id);

        if ($user->isAdmin()) {
            return redirect()->intended(route('admin.dashboard'));
        }

        // Chuyển hướng trực tiếp thành viên thường về trang chủ sau khi xác thực 2FA/OTP thành công
        return redirect()->route('home');
    }

    /**
     * Gửi lại mã OTP qua email cho người dùng tại trang xác thực đăng nhập.
     */
    public function resendOTP(Request $request)
    {
        if (!session()->has('auth_2fa_user_id')) {
            return response()->json(['success' => false, 'message' => 'Phiên làm việc đã hết hạn. Vui lòng đăng nhập lại.'], 400);
        }

        $user = \App\Models\User::find(session('auth_2fa_user_id'));

        if (!$user || !$user->email_otp_enabled) {
            return response()->json(['success' => false, 'message' => 'Yêu cầu không hợp lệ.'], 400);
        }

        // Chống spam gửi lại OTP: Giới hạn tối đa 3 lần/phút để tránh lạm dụng ngân sách SMTP
        $throttleKey = 'resend-otp:' . $user->id;
        if (RateLimiter::tooManyAttempts($throttleKey, 3)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            return response()->json(['success' => false, 'message' => "Bạn đã yêu cầu quá nhiều lần. Vui lòng thử lại sau {$seconds} giây."], 429);
        }
        RateLimiter::hit($throttleKey, 60);

        // Sinh mã OTP bảo mật 6 chữ số (CSPRNG đảm bảo entropy cao)
        // Sinh mã OTP bảo mật 6 chữ số (CSPRNG đảm bảo entropy cao)
        $otp = random_int(100000, 999999);
        // Mã hóa OTP trước khi lưu vào DB để chống rò rỉ thông tin
        $user->otp_code = \Illuminate\Support\Facades\Hash::make($otp);
        $user->otp_expires_at = now()->addMinutes(10);
        $user->save();

        try {
            \App\Models\Setting::sendEmail($user->email, 'otp', [
                'name' => $user->name,
                'email' => $user->email,
                'otp' => $otp
            ]);

            ActivityLog::log('Yêu cầu gửi lại mã OTP đăng nhập', $user->id);

            return response()->json(['success' => true, 'message' => 'Mã OTP mới đã được gửi vào email của bạn.']);
        } catch (\Exception $e) {
            \Log::error('Lỗi gửi lại OTP đăng nhập: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Có lỗi xảy ra khi gửi lại email. Vui lòng liên hệ quản trị viên.'], 500);
        }
    }

    /**
     * Đăng xuất phiên làm việc hiện tại.
     */
    public function logout(Request $request)
    {
        if (Auth::check()) {
            ActivityLog::log('Đăng xuất khỏi hệ thống', Auth::id());
            Auth::logout();
        }

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home')->with('success', 'Đăng xuất thành công.');
    }

    /**
     * Đăng xuất khỏi toàn bộ các thiết bị.
     */
    public function logoutAllDevices(Request $request)
    {
        $request->validate([
            'password' => 'required|string'
        ]);

        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();

        // Kiểm tra mật khẩu hiện tại
        if (!\Hash::check($request->password, $user->password)) {
            return back()->with('error', 'Mật khẩu xác nhận không chính xác.');
        }

        // Thực hiện đăng xuất khỏi tất cả các thiết bị khác bằng cách thay đổi remember token
        $user->setRememberToken(Str::random(60));
        $user->save();

        // Xoá toàn bộ các session khác trong session driver (nếu dùng Redis/Database)
        // Cách nhanh và chuẩn trong Laravel là dùng logoutOtherDevices
        Auth::logoutOtherDevices($request->password);

        ActivityLog::log('Đăng xuất khỏi tất cả thiết bị', $user->id);

        return back()->with('success', 'Đã đăng xuất tài khoản khỏi tất cả thiết bị.');
    }

    /**
     * Chuyển hướng người dùng đến trang xác thực của Google.
     * Kiểm tra cấu hình Google Login trước khi thực hiện chuyển hướng.
     */
    public function redirectToGoogle()
    {
        // Kiểm tra xem tính năng đăng nhập bằng Google có được bật trong cài đặt hay không
        $googleLoginEnabled = Setting::getVal('google_login_enabled', '0');
        if ($googleLoginEnabled !== '1') {
            return redirect()->route('login')->with('error', 'Chức năng đăng nhập bằng Google hiện đang bị khóa.');
        }

        // Chuyển hướng người dùng qua cổng Google OAuth
        return Socialite::driver('google')->redirect();
    }

    /**
     * Xử lý kết quả trả về từ Google OAuth.
     * Đăng nhập hoặc tạo mới tài khoản thành viên dựa trên thông tin nhận được từ Google.
     */
    public function handleGoogleCallback(Request $request)
    {
        // 1. Áp dụng cơ chế chặn IP (Blacklist và khóa IP tạm thời) để bảo mật hệ thống
        $bannedIps = Setting::getVal('banned_ips', '');
        if (!empty($bannedIps)) {
            $ipList = array_map('trim', explode(',', $bannedIps));
            if (in_array($request->ip(), $ipList, true)) {
                return redirect()->route('login')->with('error', 'Địa chỉ IP của bạn đã bị cấm truy cập hệ thống.');
            }
        }

        if (\Illuminate\Support\Facades\Cache::has("lockout_ip:{$request->ip()}")) {
            return redirect()->route('login')->with('error', 'Địa chỉ IP của bạn tạm thời bị khóa do có nhiều hoạt động bất thường.');
        }

        // Kiểm tra xem tính năng đăng nhập bằng Google có được bật trong cài đặt hay không
        $googleLoginEnabled = Setting::getVal('google_login_enabled', '0');
        if ($googleLoginEnabled !== '1') {
            return redirect()->route('login')->with('error', 'Chức năng đăng nhập bằng Google hiện đang bị khóa.');
        }

        try {
            // Lấy thông tin user từ Google
            $googleUser = Socialite::driver('google')->user();
        } catch (\Exception $e) {
            \Log::error('Lỗi kết nối Google OAuth: ' . $e->getMessage());
            return redirect()->route('login')->with('error', 'Đăng nhập bằng Google thất bại. Vui lòng thử lại.');
        }

        // Lấy và chuẩn hóa email, Google ID từ đối tượng Google User
        $googleEmail = strtolower(trim((string)$googleUser->getEmail()));
        $googleId = (string)$googleUser->getId();

        // Khóa Atomic Lock theo Google Email / Google ID trong 5 giây để chống race condition khi Google OAuth Callback gửi 2 request trùng nhau
        $googleLockKey = 'google_auth_lock:' . md5($googleEmail ?: $googleId);
        $googleLock = \Illuminate\Support\Facades\Cache::lock($googleLockKey, 5);

        try {
            // Chờ tối đa 3 giây nếu có request callback trùng trước đó đang xử lý
            $googleLock->block(3);
        } catch (\Illuminate\Contracts\Cache\LockTimeoutException $e) {
            // Nếu chờ hết 3 giây vẫn bị lock, kiểm tra xem user đã kịp được tạo bởi request trước hay chưa
            $existingUser = User::where('google_id', $googleId)->orWhereRaw('LOWER(email) = ?', [$googleEmail])->first();
            if ($existingUser) {
                Auth::login($existingUser, true);
                return redirect()->route('home')->with('success', __('Đăng nhập thành công bằng tài khoản Google!'));
            }
            return redirect()->route('login')->with('error', __('Yêu cầu đăng nhập bằng Google đang được xử lý. Vui lòng thử lại.'));
        }

        // Tìm tài khoản liên kết Google hiện tại bằng google_id hoặc email (chuẩn hóa chữ thường)
        $user = User::where('google_id', $googleId)->first();

        if (!$user && !empty($googleEmail)) {
            $user = User::whereRaw('LOWER(email) = ?', [$googleEmail])->first();
        }

        if ($user) {
            // Nếu tài khoản bị khóa thì không cho phép đăng nhập
            if ($user->status !== 'active') {
                return redirect()->route('login')->with('error', __('Tài khoản của bạn đã bị khoá hoặc ngưng hoạt động.'));
            }

            // Cập nhật google_id và ảnh đại diện nếu chưa có
            $needSave = false;
            if (empty($user->google_id)) {
                $user->google_id = $googleId;
                $needSave = true;
            }
            if (empty($user->avatar) && $googleUser->getAvatar()) {
                $user->avatar = $googleUser->getAvatar();
                $needSave = true;
            }
            if (empty($user->email_verified_at)) {
                $user->email_verified_at = now();
                $needSave = true;
            }
            if ($needSave) {
                $user->save();
            }

            // Kiểm tra bảo mật 2 lớp trước khi đăng nhập chính thức
            if ($user->google2fa_enabled || $user->email_otp_enabled) {
                session([
                    'auth_2fa_user_id' => $user->id,
                    'auth_2fa_remember' => true,
                    'auth_2fa_expires_at' => now()->addMinutes(10)->timestamp
                ]);

                if ($user->email_otp_enabled) {
                    $otp = random_int(100000, 999999);
                    $user->otp_code = \Illuminate\Support\Facades\Hash::make($otp);
                    $user->otp_expires_at = now()->addMinutes(10);
                    $user->save();

                    try {
                        Setting::sendEmail($user->email, 'otp', [
                            'name' => $user->name,
                            'email' => $user->email,
                            'otp' => $otp
                        ]);
                        ActivityLog::log('Yêu cầu gửi OTP đăng nhập qua Google OAuth', $user->id);
                    } catch (\Exception $e) {
                        \Log::error('Lỗi gửi OTP khi đăng nhập bằng Google: ' . $e->getMessage());
                    }
                }

                return redirect()->route('login.verification');
            }

            // Tiến hành đăng nhập trực tiếp
            Auth::login($user, true);
            ActivityLog::log('Đăng nhập thành công bằng Google', $user->id);

            if ($user->isAdmin()) {
                return redirect()->intended(route('admin.dashboard'));
            }
            // Chuyển hướng trực tiếp thành viên thường về trang chủ sau khi đăng nhập Google thành công
            return redirect()->route('home');
        }

        // Nếu chưa có tài khoản nào, tiến hành tạo mới (Đăng ký nhanh qua Google)

        // Kiểm tra chặn đăng ký bằng địa chỉ IP ảo (VPN/Proxy/Hosting) nếu admin bật cấu hình
        if (Setting::getVal('block_vpn_register_status', '0') === '1') {
            if ($this->isVpnOrProxy(request()->ip())) {
                return redirect()->route('login')->with('error', __('Hệ thống phát hiện bạn đang sử dụng kết nối VPN/Proxy/Hosting. Vui lòng tắt VPN để tiếp tục đăng ký tài khoản qua Google.'));
            }
        }

        // Kiểm tra giới hạn đăng ký tài khoản trên cùng địa chỉ IP để chống spam/gian lận clonings
        $ipRegisterLimit = (int)Setting::getVal('ip_register_limit', 5);
        if ($ipRegisterLimit > 0) {
            $ipAddress = request()->ip();
            $registeredCount = User::where('ip_address', $ipAddress)->count();
            if ($registeredCount >= $ipRegisterLimit) {
                return redirect()->route('login')->with('error', __('Địa chỉ IP của bạn đã đạt giới hạn đăng ký tối đa :limit tài khoản.', ['limit' => $ipRegisterLimit]));
            }
        }

        // 1. Xử lý mã giới thiệu từ cookie (nếu có)
        $referredBy = null;
        $cookieRefCode = request()->cookie('referred_by_code');
        $referralEnabled = Setting::getVal('referral_enabled', '1') === '1';

        if ($referralEnabled && $cookieRefCode) {
            $referrerUser = User::where('referral_code', $cookieRefCode)->first();
            if ($referrerUser) {
                $referredBy = $referrerUser->id;
            }
        }

        // 2. Tạo mã giới thiệu ngẫu nhiên và duy nhất cho user mới
        do {
            $newReferralCode = 'REF' . strtoupper(Str::random(6));
        } while (User::where('referral_code', $newReferralCode)->exists());

        // 3. Lấy nguồn utm_source từ cookie hoặc trực tiếp trên URL request (giống đăng ký thường)
        $utmSource = Cookie::get('utm_source') ?? $request->query('utm_source');

        // Định vị quốc gia dựa vào IP đăng ký của người dùng thông qua API ip-api.com
        $country = 'Unknown';
        try {
            $ipAddress = $request->ip();
            if ($ipAddress === '127.0.0.1' || $ipAddress === '::1') {
                $country = 'Localhost';
            } else {
                // Đặt timeout 1 giây để tránh ảnh hưởng đến trải nghiệm người dùng nếu API phản hồi chậm
                $response = \Illuminate\Support\Facades\Http::timeout(1)->get("http://ip-api.com/json/{$ipAddress}");
                if ($response->successful()) {
                    $resData = $response->json();
                    if (isset($resData['status']) && $resData['status'] === 'success' && isset($resData['country'])) {
                        $country = $resData['country'];
                    }
                }
            }
        } catch (\Exception $e) {
            \Log::error('Lỗi khi định vị quốc gia của người dùng qua IP đăng ký bằng Google: ' . $e->getMessage());
        }

        // 4. Tạo user mới trong cơ sở dữ liệu với đầy đủ thông tin UTM và metadata
        // Kiểm tra lại CSDL một lần nữa ngay trước khi tạo mới để xử lý kết nối trùng từ trình duyệt
        $existingUser = User::where('google_id', $googleId)->orWhereRaw('LOWER(email) = ?', [$googleEmail])->first();
        if ($existingUser) {
            $user = $existingUser;
            if (empty($user->google_id)) {
                $user->google_id = $googleId;
                $user->save();
            }
            Auth::login($user, true);
            ActivityLog::log(__('Đăng nhập thành công bằng Google'), $user->id);
            return redirect()->route('home')->with('success', __('Đăng nhập thành công bằng tài khoản Google!'));
        }

        try {
            $user = User::create([
                'name' => $googleUser->getName() ?? $googleEmail,
                'email' => $googleEmail,
                'password' => Hash::make(Str::random(16)), // Mật khẩu ngẫu nhiên an toàn
                'google_id' => $googleId,
                'avatar' => $googleUser->getAvatar(),
                // role='user', status='active', balance=0, total_*=0 dùng giá trị mặc định của DB
                'email_verified_at' => now(), // Đăng ký qua Google được coi là đã xác minh email tự động
                'referral_code' => $newReferralCode,
                'referred_by' => $referredBy,
                'utm_source' => $utmSource, // Thu thập nguồn chiến dịch để tối ưu hiệu quả Marketing
                'ip_address' => $request->ip(), // Lưu IP để hỗ trợ đối soát chống gian lận đa tài khoản
                'user_agent' => strip_tags(\Illuminate\Support\Str::limit($request->userAgent(), 500)), // Lưu thông tin thiết bị/trình duyệt đã lọc mã HTML
                'country' => $country, // Lưu quốc gia của người dùng
                ...$this->referralOnboarding->registrationAttributes($referredBy),
            ]);
        } catch (\Throwable $e) {
            // Xử lý dự phòng trường hợp bị trùng lặp email do luồng kết nối song song từ trình duyệt
            $user = User::where('google_id', $googleId)->orWhereRaw('LOWER(email) = ?', [$googleEmail])->first();

            if (!$user) {
                // Nếu vì lý do nào đó không tìm thấy user thì quăng lại exception
                throw $e;
            }

            // Cập nhật thông tin google_id nếu chưa có
            if (empty($user->google_id)) {
                $user->google_id = $googleId;
                $user->save();
            }

            // Đăng nhập thành công và chuyển hướng về trang chủ
            Auth::login($user, true);
            ActivityLog::log(__('Đăng nhập thành công bằng Google'), $user->id);

            return redirect()->route('home')->with('success', __('Đăng nhập thành công bằng tài khoản Google!'));
        }

        // 5. Ghi nhận lịch sử giới thiệu trong bảng referrals
        if ($referredBy) {
            Referral::create([
                'referrer_id' => $referredBy,
                'referred_id' => $user->id,
            ]);

            // Thêm một thông báo chào mừng cho người giới thiệu
            \App\Models\Notification::create([
                'user_id' => $referredBy,
                'title' => 'Bạn có thành viên mới đăng ký',
                'content' => "Thành viên {$user->name} đã đăng ký tài khoản qua liên kết giới thiệu của bạn (Đăng ký bằng Google).",
            ]);
        }

        // Ghi nhận nhật ký hoạt động đăng ký
        ActivityLog::log('Đăng ký tài khoản thành công qua Google', $user->id);

        // Gửi email chào mừng khi đăng ký tài khoản mới thành công (dùng Queue để tối ưu tốc độ phản hồi)
        try {
            Setting::sendEmailQueue($user->email, 'welcome', [
                'name' => $user->name,
                'email' => $user->email,
                'dashboard_url' => route('dashboard')
            ]);
        } catch (\Exception $e) {
            \Log::error('Lỗi gửi email chào mừng khi đăng ký bằng Google: ' . $e->getMessage());
        }

        // Gửi thông báo Telegram cho Admin về việc có thành viên mới đăng ký thông qua Google OAuth
        try {
            Setting::sendTelegramTemplate('telegram_template_new_user', [
                'name' => $user->name . ' (Google)',
                'email' => $user->email,
            ]);
        } catch (\Exception $e) {
            \Log::error('Lỗi gửi telegram đăng ký mới qua Google: ' . $e->getMessage());
        }

        // Đăng nhập ngay
        Auth::login($user, true);

        // Xoá cookie giới thiệu và cookie utm_source sau khi đăng ký thành công để làm sạch session
        Cookie::queue(Cookie::forget('referred_by_code'));
        Cookie::queue(Cookie::forget('utm_source'));

        // Chuyển hướng thành viên thường về trang chủ sau khi đăng ký & đăng nhập qua Google
        return redirect()->route('home')->with('success', __('Đăng ký và đăng nhập tài khoản bằng Google thành công!'));
    }

    /**
     * Kiểm tra xem địa chỉ IP đăng ký có sử dụng VPN, Proxy hoặc Hosting không.
     * Giải thích logic: Gọi API ProxyCheck.io với key cấu hình trong database.
     */
    protected function isVpnOrProxy(string $ipAddress): bool
    {
        // Bỏ qua kiểm tra IP nếu chạy trên localhost
        if ($ipAddress === '127.0.0.1' || $ipAddress === '::1') {
            return false;
        }

        try {
            // Lấy cấu hình API Key từ database settings
            $apiKey = Setting::getVal('proxycheck_api_key', '');

            // Xây dựng URL truy vấn API ProxyCheck.io
            $url = "https://proxycheck.io/v2/{$ipAddress}?vpn=1&asn=1";
            if (!empty($apiKey)) {
                $url .= "&key=" . urlencode($apiKey);
            }

            // Thực hiện gọi API với timeout tối đa 1.5 giây để tránh treo đăng ký
            $response = \Illuminate\Support\Facades\Http::timeout(1.5)->get($url);

            if ($response->successful()) {
                $data = $response->json();

                // Nếu API trả về trạng thái hoạt động 'ok' và chứa thông tin của IP hiện tại
                if (isset($data['status']) && $data['status'] === 'ok' && isset($data[$ipAddress])) {
                    $ipInfo = $data[$ipAddress];

                    // proxy = yes nghĩa là IP này thuộc VPN, Proxy, Tor hoặc Hosting (máy chủ VPS)
                    if (isset($ipInfo['proxy']) && $ipInfo['proxy'] === 'yes') {
                        return true;
                    }
                }
            }
        } catch (\Exception $e) {
            \Log::error('Lỗi kiểm tra VPN/Proxy đăng ký Google: ' . $e->getMessage());
        }

        return false;
    }
}
