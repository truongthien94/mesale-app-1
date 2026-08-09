<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Referral;
use App\Models\ActivityLog;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class RegisterController extends Controller
{
    /**
     * Hiển thị giao diện đăng ký tài khoản.
     */
    public function showRegisterForm(Request $request)
    {
        // Kiểm tra xem hệ thống có cho phép đăng ký tài khoản mới hay không
        if (Setting::getVal('registration_enabled', '1') !== '1') {
            return redirect()->route('login')->with('error', __('Hệ thống hiện tại đã tạm ngưng tính năng đăng ký thành viên mới.'));
        }

        // Nếu đã đăng nhập, chuyển hướng về trang chủ thay vì dashboard
        if (Auth::check()) {
            return redirect()->route('home');
        }

        // 1. Kiểm tra tham số ref trên URL để lưu cookie giới thiệu
        if ($request->has('ref')) {
            $refCode = $request->query('ref');

            // Lấy số ngày lưu cookie từ cấu hình hệ thống (mặc định 30 ngày)
            $cookieDays = (int)Setting::getVal('referral_cookie_days', 30);

            // Thiết lập cookie lưu mã giới thiệu
            Cookie::queue('referred_by_code', $refCode, $cookieDays * 24 * 60);
        }

        return view('auth.register');
    }

    /**
     * Xử lý yêu cầu đăng ký tài khoản mới.
     */
    public function register(Request $request)
    {
        // Kiểm tra xem hệ thống có cho phép đăng ký tài khoản mới hay không
        if (Setting::getVal('registration_enabled', '1') !== '1') {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => __('Hệ thống hiện tại đã tạm ngưng tính năng đăng ký thành viên mới.')
                ], 403);
            }
            return back()->with('error', __('Hệ thống hiện tại đã tạm ngưng tính năng đăng ký thành viên mới.'));
        }

        // Chặn đăng ký bằng Email/Mật khẩu nếu admin đã tắt form này (chỉ cho phép đăng ký bằng Google)
        // Fallback an toàn: chỉ chặn khi đăng nhập Google đang bật, tránh khóa toàn bộ chức năng đăng ký.
        if (
            Setting::getVal('email_auth_enabled', '1') === '0'
            && Setting::getVal('google_login_enabled', '0') === '1'
        ) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => __('Hệ thống hiện chỉ cho phép đăng ký bằng Google.')
                ], 403);
            }
            return redirect()->route('register')->with('error', __('Hệ thống hiện chỉ cho phép đăng ký bằng Google.'));
        }

        // Kiểm tra Cloudflare Turnstile Captcha nếu được bật
        if (\App\Models\Setting::getVal('turnstile_status', '0') === '1' && \App\Models\Setting::getVal('turnstile_on_register', '0') === '1') {
            $token = $request->input('cf-turnstile-response');
            if (!\App\Services\TurnstileService::verify($token, $request->ip())) {
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => __('Xác thực Captcha không hợp lệ. Vui lòng thử lại.')
                    ], 422);
                }
                return back()->withErrors([
                    'cf-turnstile-response' => __('Xác thực Captcha không hợp lệ. Vui lòng thử lại.'),
                ])->withInput();
            }
        }

        // Chống bot đăng ký hàng loạt: Giới hạn tối đa 5 tài khoản/phút từ cùng 1 IP
        $throttleKey = 'register:' . $request->ip();
        if (\Illuminate\Support\Facades\RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = \Illuminate\Support\Facades\RateLimiter::availableIn($throttleKey);
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => __('Bạn đã gửi yêu cầu quá nhiều lần. Vui lòng thử lại sau :seconds giây.', ['seconds' => $seconds])
                ], 429);
            }
            return back()->with('error', __('Bạn đã gửi yêu cầu quá nhiều lần. Vui lòng thử lại sau :seconds giây.', ['seconds' => $seconds]))->withInput();
        }
        \Illuminate\Support\Facades\RateLimiter::hit($throttleKey, 60);

        // Kiểm tra chặn đăng ký bằng địa chỉ IP ảo (VPN/Proxy/Hosting) nếu admin bật cấu hình
        if (Setting::getVal('block_vpn_register_status', '0') === '1') {
            if ($this->isVpnOrProxy($request->ip())) {
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => __('Hệ thống phát hiện bạn đang kết nối bằng VPN/Proxy/Hosting. Vui lòng tắt VPN để tiếp tục đăng ký tài khoản.')
                    ], 403);
                }
                return back()->with('error', __('Hệ thống phát hiện bạn đang kết nối bằng VPN/Proxy/Hosting. Vui lòng tắt VPN để tiếp tục đăng ký tài khoản.'))->withInput();
            }
        }

        // Kiểm tra giới hạn đăng ký tài khoản trên cùng địa chỉ IP để chống spam/gian lận clonings
        $ipRegisterLimit = (int)Setting::getVal('ip_register_limit', 5);
        if ($ipRegisterLimit > 0) {
            $ipAddress = $request->ip();
            $registeredCount = User::where('ip_address', $ipAddress)->count();
            if ($registeredCount >= $ipRegisterLimit) {
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => __('Địa chỉ IP của bạn đã đạt giới hạn đăng ký tối đa :limit tài khoản.', ['limit' => $ipRegisterLimit])
                    ], 403);
                }
                return back()->with('error', __('Địa chỉ IP của bạn đã đạt giới hạn đăng ký tối đa :limit tài khoản.', ['limit' => $ipRegisterLimit]))->withInput();
            }
        }

        // Chuẩn hóa địa chỉ email (loại bỏ khoảng trắng và chuyển về chữ thường)
        if ($request->has('email')) {
            $request->merge(['email' => strtolower(trim($request->email))]);
        }

        // Chuẩn hóa số điện thoại về dạng thống nhất (chỉ giữ chữ số, quy đổi đầu số +84/84 về 0)
        if ($request->filled('phone')) {
            $request->merge(['phone' => User::normalizePhone($request->phone)]);
        }

        // 2. Xây dựng quy tắc validate linh hoạt dựa trên cấu hình trường đăng ký của admin
        $showName = Setting::getVal('register_field_name', '1') === '1';
        $requireName = Setting::getVal('register_field_name_required', '1') === '1';
        $showPhone = Setting::getVal('register_field_phone', '1') === '1';
        $requirePhone = Setting::getVal('register_field_phone_required', '0') === '1';

        // Xác định phương thức định danh tài khoản mà thành viên đang sử dụng để đăng ký (Email hoặc Số điện thoại)
        $registerType = $this->resolveRegisterType($request->input('register_type'));

        $rules = [
            'password' => 'required|string|min:8|confirmed',
        ];

        if ($registerType === 'phone') {
            // Đăng ký bằng Số điện thoại: SĐT đóng vai trò định danh tài khoản nên bắt buộc và phải là duy nhất
            $rules['phone'] = ['required', 'string', 'regex:/^0\d{8,10}$/', 'unique:users,phone'];
        } else {
            // Đăng ký bằng Email: Email là định danh bắt buộc và duy nhất
            $rules['email'] = 'required|string|email|max:255|unique:users,email';

            // Chỉ validate trường "phone" khi admin bật hiển thị, bắt buộc hay tùy chọn tùy cấu hình
            if ($showPhone) {
                $phoneRules = [$requirePhone ? 'required' : 'nullable', 'string', 'regex:/^0\d{8,10}$/'];

                // Khi hệ thống cho phép đăng ký/đăng nhập bằng Số điện thoại, SĐT phải là duy nhất để tránh đăng nhập nhầm tài khoản
                if (Setting::getVal('register_identifier_phone', '0') === '1') {
                    $phoneRules[] = 'unique:users,phone';
                }

                $rules['phone'] = $phoneRules;
            }
        }

        // Chỉ validate trường "name" khi admin bật hiển thị, bắt buộc hay tùy chọn tùy cấu hình
        if ($showName) {
            $rules['name'] = ($requireName ? 'required' : 'nullable') . '|string|max:255';
        }

        $messages = [
            'email.required' => __('Vui lòng nhập địa chỉ email của bạn.'),
            'email.string' => __('Địa chỉ email phải là dạng chuỗi ký tự.'),
            'email.email' => __('Địa chỉ email không đúng định dạng.'),
            'email.max' => __('Địa chỉ email không được vượt quá :max ký tự.', ['max' => 255]),
            'email.unique' => __('Địa chỉ email này đã được sử dụng trên hệ thống.'),

            'password.required' => __('Vui lòng nhập mật khẩu.'),
            'password.string' => __('Mật khẩu phải là dạng chuỗi ký tự.'),
            'password.min' => __('Mật khẩu phải chứa ít nhất 8 ký tự.'),
            'password.confirmed' => __('Mật khẩu xác nhận không trùng khớp với mật khẩu đã nhập.'),

            'name.required' => __('Vui lòng nhập họ và tên của bạn.'),
            'name.string' => __('Họ và tên phải là dạng chuỗi ký tự.'),
            'name.max' => __('Họ và tên không được vượt quá :max ký tự.', ['max' => 255]),

            'phone.required' => __('Vui lòng nhập số điện thoại của bạn.'),
            'phone.string' => __('Số điện thoại phải là dạng chuỗi ký tự.'),
            'phone.max' => __('Số điện thoại không được vượt quá :max ký tự.', ['max' => 15]),
            'phone.regex' => __('Số điện thoại không đúng định dạng (bắt đầu bằng số 0 và có từ 9 đến 11 chữ số).'),
            'phone.unique' => __('Số điện thoại này đã được sử dụng trên hệ thống.'),
        ];

        $request->validate($rules, $messages);

        // 3. Xử lý mã giới thiệu từ cookie
        $referredBy = null;
        $cookieRefCode = Cookie::get('referred_by_code');

        // Kiểm tra xem tính năng tiếp thị liên kết có bật hay không
        $referralEnabled = Setting::getVal('referral_enabled', '1') === '1';

        if ($referralEnabled && $cookieRefCode) {
            $referrerUser = User::where('referral_code', $cookieRefCode)->first();
            if ($referrerUser) {
                // Kiểm tra xem cấu hình chặn trùng IP giới thiệu có bật không (mặc định là ON)
                $blockSameIpReferral = Setting::getVal('block_same_ip_referral', '1') === '1';

                // Nếu bật cấu hình chặn trùng IP, tiến hành đối chiếu IP đăng ký của tài khoản mới (F0) và người giới thiệu (F1)
                // Điều này giúp ngăn chặn hành vi tự tạo nhiều tài khoản (clone) dưới máy của mình để nhận hoa hồng giới thiệu
                if ($blockSameIpReferral && !empty($referrerUser->ip_address) && $referrerUser->ip_address === $request->ip()) {
                    // Ghi lại log hệ thống để phục vụ đối soát gian lận khi cần thiết
                    \Log::info("Chặn ghi nhận quan hệ giới thiệu do phát hiện trùng IP đăng ký giữa F0 và F1 (Người giới thiệu ID: {$referrerUser->id}, IP: {$referrerUser->ip_address})");
                    $referredBy = null;
                } else {
                    $referredBy = $referrerUser->id;
                }
            }
        }

        // 4. Tạo mã giới thiệu ngẫu nhiên và duy nhất cho user mới
        do {
            $newReferralCode = 'REF' . strtoupper(Str::random(6));
        } while (User::where('referral_code', $newReferralCode)->exists());

        // 4.5. Lấy nguồn utm_source từ cookie (hoặc trực tiếp trên URL request)
        $utmSource = Cookie::get('utm_source') ?? $request->query('utm_source');

        // Lấy quốc gia từ địa chỉ IP của người dùng qua API ip-api.com để hiển thị thông tin chi tiết cho Admin
        $country = 'Unknown';
        try {
            $ipAddress = $request->ip();
            if ($ipAddress === '127.0.0.1' || $ipAddress === '::1') {
                $country = 'Localhost';
            } else {
                // Giảm timeout định vị quốc gia xuống 1 giây để tránh chặn luồng đăng ký của người dùng
                $response = \Illuminate\Support\Facades\Http::timeout(1)->get("http://ip-api.com/json/{$ipAddress}");
                if ($response->successful()) {
                    $resData = $response->json();
                    if (isset($resData['status']) && $resData['status'] === 'success' && isset($resData['country'])) {
                        $country = $resData['country'];
                    }
                }
            }
        } catch (\Exception $e) {
            \Log::error('Lỗi khi định vị quốc gia của người dùng qua IP đăng ký: ' . $e->getMessage());
        }

        // 5. Lưu user mới vào cơ sở dữ liệu (Có bọc try-catch an toàn tuyệt đối chống nổ trang 500)
        // Với tài khoản đăng ký bằng Số điện thoại, cột email được để trống (NULL) cho tới khi thành viên tự bổ sung trong trang hồ sơ
        $registerEmail = $registerType === 'email' ? $request->email : null;
        $registerPhone = User::normalizePhone($request->phone);

        // Nếu thành viên không nhập họ tên, tự sinh tên hiển thị từ email hoặc số điện thoại đăng ký
        $userName = $request->name ?: ($registerEmail ? explode('@', $registerEmail)[0] : $registerPhone);

        $userData = [
            'name' => $userName,
            'email' => $registerEmail,
            'phone' => $registerPhone,
            'password' => Hash::make($request->password),
            // role='user', status='active', balance=0, total_*=0 dùng giá trị mặc định của DB
            // (các cột này đã được loại khỏi mass-assignment vì lý do bảo mật, xem App\Models\User).
            'referral_code' => $newReferralCode,
            'referred_by' => $referredBy,
            'utm_source' => $utmSource, // Lưu nguồn UTM chiến dịch tiếp thị liên kết
            'ip_address' => $request->ip(), // Lưu địa chỉ IP khi đăng ký để kiểm soát giới hạn tài khoản
            'user_agent' => strip_tags(\Illuminate\Support\Str::limit($request->userAgent(), 500)), // Lưu thông tin trình duyệt và thiết bị đã lọc sạch HTML (giới hạn 500 ký tự)
            'country' => $country, // Lưu quốc gia của người dùng đăng ký
        ];

        // Thử tối đa 2 lần: lần 2 chỉ nhằm xử lý trường hợp đụng độ khóa chính ID hiếm gặp
        // (2 request đăng ký gần như đồng thời), không liên quan tới trùng email thật sự.
        $user = null;
        for ($attempt = 1; $attempt <= 2; $attempt++) {
            try {
                $user = User::create($userData);
                break;
            } catch (\Illuminate\Database\QueryException $e) {
                // Xử lý hiện tượng Race Condition / Double Submit (và cả trường hợp câu lệnh tạo tài khoản bị thực thi lặp lại):
                // Nếu tài khoản với đúng định danh này vừa được tạo trong vòng 10 giây trước từ cùng địa chỉ IP
                // thì ghi nhận đăng ký thành công thay vì báo lỗi trùng lặp cho người dùng.
                // Kiểm tra chung cho mọi lỗi trùng khoá (email, số điện thoại, mã giới thiệu, api_token...).
                if ($e->getCode() == 23000) {
                    $justCreatedUser = $registerEmail
                        ? User::where('email', $registerEmail)->first()
                        : User::where('phone', $registerPhone)->first();

                    if ($justCreatedUser
                        && $justCreatedUser->ip_address === $request->ip()
                        && $justCreatedUser->created_at
                        && $justCreatedUser->created_at->gt(now()->subSeconds(10))
                    ) {
                        $user = $justCreatedUser;
                        break;
                    }
                }

                // Kiểm tra nếu lỗi do trùng lặp email ở mức cơ sở dữ liệu
                if (str_contains($e->getMessage(), 'users_email_unique')) {
                    if ($request->expectsJson() || $request->ajax()) {
                        return response()->json([
                            'success' => false,
                            'message' => __('Địa chỉ email này đã được sử dụng trên hệ thống.'),
                            'errors' => [
                                'email' => [__('Địa chỉ email này đã được sử dụng trên hệ thống.')]
                            ]
                        ], 422);
                    }
                    return back()->withErrors(['email' => __('Địa chỉ email này đã được sử dụng trên hệ thống.')])->withInput();
                }

                // Kiểm tra nếu lỗi do trùng lặp số điện thoại ở mức cơ sở dữ liệu (tài khoản đăng ký bằng SĐT)
                if (str_contains($e->getMessage(), 'users_phone_unique')) {
                    if ($request->expectsJson() || $request->ajax()) {
                        return response()->json([
                            'success' => false,
                            'message' => __('Số điện thoại này đã được sử dụng trên hệ thống.'),
                            'errors' => [
                                'phone' => [__('Số điện thoại này đã được sử dụng trên hệ thống.')]
                            ]
                        ], 422);
                    }
                    return back()->withErrors(['phone' => __('Số điện thoại này đã được sử dụng trên hệ thống.')])->withInput();
                }

                if ($e->getCode() != 23000) {
                    throw $e;
                }

                if ($attempt === 2) {
                    \Log::error('Lỗi đăng ký tài khoản do trùng khóa sau khi đã thử lại: ' . $e->getMessage());
                    if ($request->expectsJson() || $request->ajax()) {
                        return response()->json([
                            'success' => false,
                            'message' => __('Có lỗi xảy ra, vui lòng thử lại sau ít phút.')
                        ], 500);
                    }
                    return back()->with('error', __('Có lỗi xảy ra, vui lòng thử lại sau ít phút.'))->withInput();
                }
                // Không phải trùng email (thường là đụng độ ID) -> thử lại lần nữa
            }
        }

        // 6. Ghi nhận lịch sử giới thiệu & nhật ký hoạt động (Bọc try-catch để tránh các lỗi phụ ngắt luồng đăng ký đã thành công)
        try {
            if ($referredBy) {
                Referral::create([
                    'referrer_id' => $referredBy,
                    'referred_id' => $user->id,
                ]);

                // Thêm một thông báo chào mừng cho người giới thiệu
                \App\Models\Notification::create([
                    'user_id' => $referredBy,
                    'title' => __('Bạn có thành viên mới đăng ký'),
                    'content' => __('Thành viên :name đã đăng ký tài khoản qua liên kết giới thiệu của bạn.', ['name' => $user->name]),
                ]);
            }

            // Gửi ghi nhận nhật ký hoạt động
            ActivityLog::log(__('Đăng ký tài khoản thành công'), $user->id);
        } catch (\Exception $e) {
            \Log::error('Lỗi phụ ghi nhận giới thiệu/log sau khi đăng ký tài khoản: ' . $e->getMessage());
        }

        // Kiểm tra xem cấu hình bắt buộc xác minh Email khi đăng ký có bật không
        // Lưu ý: Tài khoản đăng ký bằng Số điện thoại chưa có email nên được bỏ qua bước xác minh này
        if (Setting::getVal('email_verification_enabled', '0') === '1' && !empty($user->email)) {
            // Sinh mã OTP xác thực 6 chữ số (sử dụng random_int để đảm bảo độ bảo mật cao)
            $otp = random_int(100000, 999999);

            // Mã hóa mã OTP trước khi lưu vào cơ sở dữ liệu để tránh lộ thông tin
            $user->otp_code = Hash::make($otp);
            $user->otp_expires_at = now()->addMinutes(10); // OTP có hiệu lực trong 10 phút
            $user->save();

            // Gửi email chứa mã OTP xác minh tài khoản cho thành viên
            try {
                Setting::sendEmail($user->email, 'verify_email', [
                    'name' => $user->name,
                    'email' => $user->email,
                    'otp' => $otp
                ]);
            } catch (\Exception $e) {
                \Log::error('Lỗi gửi email xác minh khi đăng ký: ' . $e->getMessage());
            }

            // Gửi thông báo Telegram khi đăng ký tài khoản mới thành công (chờ xác minh)
            try {
                Setting::sendTelegramTemplate('telegram_template_new_user', [
                    'name' => $user->name,
                    'email' => $user->email,
                ]);
            } catch (\Exception $e) {
                \Log::error('Lỗi gửi telegram đăng ký mới: ' . $e->getMessage());
            }

            // Lưu ID của user tạm thời vào session để biết tài khoản nào cần xác minh
            session(['email_verification_user_id' => $user->id]);

            // Xoá cookie giới thiệu và nguồn utm_source sau khi đăng ký thành công để giải phóng bộ nhớ
            Cookie::queue(Cookie::forget('referred_by_code'));
            Cookie::queue(Cookie::forget('utm_source'));

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'redirect' => route('register.verification'),
                    'message' => __('Đăng ký tài khoản thành công! Vui lòng kiểm tra email để lấy mã kích hoạt tài khoản.')
                ]);
            }
            return redirect()->route('register.verification')->with('success', __('Đăng ký tài khoản thành công! Vui lòng kiểm tra email để lấy mã kích hoạt tài khoản.'));
        }

        // Nếu KHÔNG bắt buộc xác minh email, tiến hành đăng nhập và gửi mail chào mừng trực tiếp
        // Đưa email chào mừng vào hàng đợi (Queue) để gửi ngầm, tránh làm chậm trang đăng ký
        try {
            Setting::sendEmailQueue($user->email, 'welcome', [
                'name' => $user->name,
                'email' => $user->email,
                'dashboard_url' => route('dashboard')
            ]);
        } catch (\Exception $e) {
            \Log::error('Lỗi gửi email chào mừng đăng ký: ' . $e->getMessage());
        }

        // Gửi thông báo Telegram khi đăng ký tài khoản mới thành công
        // (Tài khoản đăng ký bằng Số điện thoại chưa có email nên hiển thị số điện thoại thay thế)
        try {
            Setting::sendTelegramTemplate('telegram_template_new_user', [
                'name' => $user->name,
                'email' => $user->email ?: $user->phone,
            ]);
        } catch (\Exception $e) {
            \Log::error('Lỗi gửi telegram đăng ký mới: ' . $e->getMessage());
        }

        // 7. Đăng nhập ngay cho tài khoản mới
        Auth::login($user);

        // Xoá cookie giới thiệu và nguồn utm_source sau khi đăng ký thành công để giải phóng bộ nhớ
        Cookie::queue(Cookie::forget('referred_by_code'));
        Cookie::queue(Cookie::forget('utm_source'));

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'redirect' => route('home'),
                'message' => __('Đăng ký tài khoản thành công!')
            ]);
        }
        // Chuyển hướng người dùng về trang chủ sau khi đăng ký thành công trực tiếp
        return redirect()->route('home')->with('success', __('Đăng ký tài khoản thành công!'));
    }

    /**
     * Xác định phương thức định danh tài khoản khi đăng ký (email hoặc phone).
     * Giải thích logic: Dựa trên 2 cấu hình register_identifier_email / register_identifier_phone của admin.
     * Nếu admin lỡ tắt cả hai, hệ thống tự động quay về đăng ký bằng Email để không khoá toàn bộ chức năng đăng ký.
     */
    public static function resolveRegisterType(?string $requested): string
    {
        $emailEnabled = Setting::getVal('register_identifier_email', '1') === '1';
        $phoneEnabled = Setting::getVal('register_identifier_phone', '0') === '1';

        // Fallback an toàn khi admin tắt cả hai phương thức định danh
        if (!$emailEnabled && !$phoneEnabled) {
            return 'email';
        }

        // Chỉ chấp nhận phương thức mà admin đang cho phép, các giá trị lạ đều bị bỏ qua
        if ($requested === 'phone' && $phoneEnabled) {
            return 'phone';
        }

        if ($requested === 'email' && $emailEnabled) {
            return 'email';
        }

        return $emailEnabled ? 'email' : 'phone';
    }

    /**
     * Hiển thị giao diện nhập mã OTP xác minh email tài khoản.
     * Giải thích logic: Chỉ cho phép truy cập nếu có ID người dùng đang chờ xác minh lưu trong session.
     */
    public function showVerificationForm()
    {
        // Nếu người dùng đã đăng nhập nhưng chưa xác minh email, tự động thiết lập phiên xác minh
        if (Auth::check()) {
            $user = Auth::user();
            if (!is_null($user->email_verified_at)) {
                return redirect()->route('dashboard')->with('success', __('Tài khoản của bạn đã được xác minh trước đó.'));
            }

            // Gán ID người dùng đang đăng nhập vào session chờ xác minh email
            session(['email_verification_user_id' => $user->id]);

            // Nếu chưa có mã OTP hoặc OTP cũ đã hết hạn, tự động sinh mã mới và gửi email xác thực
            if (empty($user->otp_code) || is_null($user->otp_expires_at) || $user->otp_expires_at->isPast()) {
                try {
                    $otp = random_int(100000, 999999);
                    $user->otp_code = Hash::make($otp);
                    $user->otp_expires_at = now()->addMinutes(10);
                    $user->save();

                    Setting::sendEmail($user->email, 'verify_email', [
                        'name' => $user->name,
                        'email' => $user->email,
                        'otp' => $otp
                    ]);
                } catch (\Exception $e) {
                    \Log::error('Lỗi gửi email xác minh tự động cho user đang đăng nhập: ' . $e->getMessage());
                }
            }
        }

        if (!session()->has('email_verification_user_id')) {
            return redirect()->route('register')->with('error', __('Không tìm thấy phiên làm việc xác minh. Vui lòng đăng ký lại.'));
        }

        return view('auth.register_verification');
    }

    /**
     * Xử lý xác minh OTP để kích hoạt tài khoản người dùng.
     * Giải thích logic: Kiểm tra mã OTP người dùng nhập vào so với mã đã băm trong database,
     * nếu chính xác và còn hạn thì kích hoạt tài khoản bằng cách gán email_verified_at = now() và tiến hành đăng nhập.
     */
    public function verify(Request $request)
    {
        if (!session()->has('email_verification_user_id')) {
            return redirect()->route('register')->with('error', __('Phiên làm việc đã hết hạn. Vui lòng thử lại.'));
        }

        $request->validate([
            'otp_code' => 'required|string|size:6',
        ], [
            'otp_code.required' => __('Vui lòng nhập mã OTP xác thực.'),
            'otp_code.size' => __('Mã OTP phải chứa đúng 6 ký tự số.'),
        ]);

        $user = User::find(session('email_verification_user_id'));

        if (!$user) {
            session()->forget('email_verification_user_id');
            return redirect()->route('register')->with('error', __('Không tìm thấy thông tin tài khoản cần xác thực.'));
        }

        // Áp dụng giới hạn số lần nhập sai OTP (Brute force protection)
        $throttleKey = 'verify-register-otp:' . $user->id;
        if (\Illuminate\Support\Facades\RateLimiter::tooManyAttempts($throttleKey, 5)) {
            // Xóa session tạm thời và reset OTP khi người dùng spam/nhập sai quá nhiều lần
            session()->forget('email_verification_user_id');
            $user->otp_code = null;
            $user->otp_expires_at = null;
            $user->save();
            \Illuminate\Support\Facades\RateLimiter::clear($throttleKey);
            return redirect()->route('register')->with('error', __('Bạn đã nhập sai mã xác minh quá nhiều lần. Vui lòng đăng ký lại tài khoản.'));
        }

        // Đối chiếu so sánh mã OTP băm lưu trong DB với mã người dùng gửi lên
        if (!$user->otp_code || !Hash::check($request->otp_code, $user->otp_code) || !$user->otp_expires_at || $user->otp_expires_at->isPast()) {
            // Ghi nhận một lần thử nhập sai OTP để giới hạn tần suất
            \Illuminate\Support\Facades\RateLimiter::hit($throttleKey, 600);
            return back()->withErrors(['otp_code' => __('Mã OTP không chính xác hoặc đã hết hiệu lực.')]);
        }

        // Xóa bộ đếm số lần nhập sai sau khi xác thực thành công
        \Illuminate\Support\Facades\RateLimiter::clear($throttleKey);

        // Kích hoạt tài khoản thành công
        $user->email_verified_at = now();
        $user->otp_code = null;
        $user->otp_expires_at = null;
        $user->save();

        // Gửi email chào mừng khi tài khoản kích hoạt thành công (dùng Queue để tối ưu tốc độ phản hồi)
        try {
            Setting::sendEmailQueue($user->email, 'welcome', [
                'name' => $user->name,
                'email' => $user->email,
                'dashboard_url' => route('dashboard')
            ]);
        } catch (\Exception $e) {
            \Log::error('Lỗi gửi email chào mừng sau kích hoạt: ' . $e->getMessage());
        }

        // Đăng nhập chính thức người dùng vào hệ thống
        Auth::login($user);

        // Xóa thông tin ID chờ xác minh trong session
        session()->forget('email_verification_user_id');

        ActivityLog::log(__('Xác minh Email và kích hoạt tài khoản thành công'), $user->id);

        // Chuyển hướng người dùng về trang chủ sau khi xác minh email thành công
        return redirect()->route('home')->with('success', __('Xác minh tài khoản và đăng nhập thành công!'));
    }

    /**
     * Gửi lại mã OTP xác minh tài khoản qua Email.
     * Giải thích logic: Áp dụng cơ chế Rate Limiter để giới hạn tần suất gửi lại mã OTP (tối đa 1 lần/phút) nhằm chống spam mail.
     */
    public function resendVerificationOTP(Request $request)
    {
        if (!session()->has('email_verification_user_id')) {
            return response()->json(['success' => false, 'message' => __('Phiên làm việc đã hết hạn. Vui lòng thử lại.')], 400);
        }

        $user = User::find(session('email_verification_user_id'));

        if (!$user) {
            return response()->json(['success' => false, 'message' => __('Không tìm thấy tài khoản cần xác thực.')], 400);
        }

        // Chống spam gửi mail liên tục: Giới hạn tối đa 1 lần gửi mỗi 60 giây cho mỗi tài khoản
        $throttleKey = 'resend-register-otp:' . $user->id;
        if (\Illuminate\Support\Facades\RateLimiter::tooManyAttempts($throttleKey, 1)) {
            $seconds = \Illuminate\Support\Facades\RateLimiter::availableIn($throttleKey);
            return response()->json(['success' => false, 'message' => __('Vui lòng đợi :seconds giây trước khi yêu cầu gửi lại mã mới.', ['seconds' => $seconds])], 429);
        }
        \Illuminate\Support\Facades\RateLimiter::hit($throttleKey, 60);

        // Sinh mã OTP 6 chữ số mới
        $otp = random_int(100000, 999999);
        $user->otp_code = Hash::make($otp);
        $user->otp_expires_at = now()->addMinutes(10);
        $user->save();

        // Tiến hành gửi lại Email xác minh
        try {
            Setting::sendEmail($user->email, 'verify_email', [
                'name' => $user->name,
                'email' => $user->email,
                'otp' => $otp
            ]);

            ActivityLog::log(__('Yêu cầu gửi lại mã OTP xác minh email đăng ký'), $user->id);

            return response()->json(['success' => true, 'message' => __('Mã xác thực mới đã được gửi vào hòm thư của bạn.')]);
        } catch (\Exception $e) {
            \Log::error('Lỗi gửi lại email xác minh đăng ký: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => __('Có lỗi xảy ra khi gửi email. Vui lòng liên hệ quản trị viên.')], 500);
        }
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
            \Log::error('Lỗi kiểm tra VPN/Proxy đăng ký tài khoản: ' . $e->getMessage());
        }

        return false;
    }
}
