<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\ActivityLog;
use App\Models\ApiToken;
use App\Models\Referral;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use PragmaRX\Google2FA\Google2FA;

/**
 * API Xác thực: Đăng ký tài khoản, Đăng nhập lấy token, Đăng xuất thu hồi token.
 *
 * Bảo mật: giới hạn tần suất theo IP để chống dò mật khẩu / spam tạo tài khoản,
 * token trả về dạng Bearer chỉ hiển thị một lần và được lưu băm SHA-256 trong DB.
 */
class AuthController extends ApiController
{
    /**
     * Số ngày hiệu lực của token, đọc từ cấu hình (0 = vĩnh viễn).
     */
    protected function tokenTtlDays(): ?int
    {
        $days = (int) Setting::getVal('openapi_token_ttl_days', 0);
        return $days > 0 ? $days : null;
    }

    /**
     * Chuẩn hóa dữ liệu thành viên trả về cho client (loại bỏ trường nhạy cảm).
     */
    protected function userResource(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'avatar' => $user->avatar,
            'balance' => (float) $user->balance,
            'total_cashback' => (float) $user->total_cashback,
            'total_referral_earned' => (float) $user->total_referral_earned,
            'total_withdrawn' => (float) $user->total_withdrawn,
            'referral_code' => $user->referral_code,
            'status' => $user->status,
            'email_verified' => !is_null($user->email_verified_at),
            'created_at' => optional($user->created_at)->toIso8601String(),
        ];
    }

    /**
     * Cấp token mới cho thành viên và trả về phản hồi đăng nhập chuẩn.
     */
    protected function respondWithToken(User $user, Request $request, ?string $deviceName, string $message, int $status = 200): JsonResponse
    {
        [$plainToken] = ApiToken::generateFor(
            $user,
            $deviceName ?? 'API Client',
            $this->tokenTtlDays(),
            $request->ip()
        );

        return $this->ok([
            'token' => $plainToken,
            'token_type' => 'Bearer',
            'user' => $this->userResource($user),
        ], $message, $status);
    }

    /**
     * Tạo phiên chờ xác thực 2 lớp (2FA) và lưu tạm vào cache (stateless, không dùng session).
     * Nếu tài khoản bật OTP qua email thì gửi mã ngay lập tức.
     *
     * @return array{challenge_token: string, methods: array<int, string>}
     */
    protected function startTwoFactorChallenge(User $user): array
    {
        $methods = [];
        if ($user->google2fa_enabled) {
            $methods[] = 'google2fa';
        }
        if ($user->email_otp_enabled) {
            $methods[] = 'email_otp';
        }

        // Nếu bật OTP email: sinh mã, băm lưu DB và gửi mail cho người dùng
        if ($user->email_otp_enabled) {
            $otp = (string) random_int(100000, 999999);
            $user->otp_code = Hash::make($otp);
            $user->otp_expires_at = now()->addMinutes(10);
            $user->save();

            try {
                Setting::sendEmail($user->email, 'otp', [
                    'name' => $user->name,
                    'email' => $user->email,
                    'otp' => $otp,
                ]);
            } catch (\Exception $e) {
                \Log::error('Lỗi gửi OTP đăng nhập 2FA qua API: ' . $e->getMessage());
            }
        }

        // Sinh challenge token ngẫu nhiên, lưu băm SHA-256 trong cache 10 phút
        $challenge = Str::random(64);
        Cache::put(
            'api_2fa_challenge:' . hash('sha256', $challenge),
            ['user_id' => $user->id, 'methods' => $methods],
            now()->addMinutes(10)
        );

        return ['challenge_token' => $challenge, 'methods' => $methods];
    }

    /**
     * POST /api/v1/openapi/auth/register
     * Đăng ký tài khoản mới và trả về token truy cập.
     */
    public function register(Request $request): JsonResponse
    {
        // Hệ thống có cho phép đăng ký thành viên mới không
        if (Setting::getVal('registration_enabled', '1') !== '1') {
            return $this->fail(__('Hệ thống hiện tại đã tạm ngưng tính năng đăng ký thành viên mới.'), 403, 'REGISTRATION_DISABLED');
        }

        // Chống bot đăng ký hàng loạt: tối đa 5 tài khoản/phút từ cùng một IP
        $throttleKey = 'api-register:' . $request->ip();
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            return $this->fail(__('Bạn đã gửi yêu cầu quá nhiều lần. Vui lòng thử lại sau :seconds giây.', ['seconds' => $seconds]), 429, 'TOO_MANY_REQUESTS');
        }
        RateLimiter::hit($throttleKey, 60);

        if ($request->has('email')) {
            $request->merge(['email' => strtolower(trim($request->email))]);
        }

        // Chuẩn hoá số điện thoại về dạng thống nhất trước khi kiểm tra tính duy nhất
        if ($request->filled('phone')) {
            $request->merge(['phone' => User::normalizePhone($request->phone)]);
        }

        try {
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

                'name.string' => __('Họ và tên phải là dạng chuỗi ký tự.'),
                'name.max' => __('Họ và tên không được vượt quá :max ký tự.', ['max' => 255]),

                'phone.string' => __('Số điện thoại phải là dạng chuỗi ký tự.'),
                'phone.max' => __('Số điện thoại không được vượt quá :max ký tự.', ['max' => 15]),
                'phone.unique' => __('Số điện thoại này đã được sử dụng trên hệ thống.'),
            ];

            $validated = $request->validate([
                'name' => 'nullable|string|max:255',
                'email' => 'required|string|email|max:255|unique:users,email',
                // Số điện thoại có thể là định danh đăng nhập nên phải là duy nhất trên toàn hệ thống
                'phone' => 'nullable|string|max:15|unique:users,phone',
                'password' => 'required|string|min:8|confirmed',
                'referral_code' => 'nullable|string|max:50',
                'device_name' => 'nullable|string|max:100',
            ], $messages);
        } catch (ValidationException $e) {
            return $this->fail(__('Dữ liệu đăng ký không hợp lệ.'), 422, 'VALIDATION_ERROR', $e->errors());
        }

        // Giới hạn số tài khoản đăng ký trên cùng một IP để chống tạo tài khoản ảo
        $ipRegisterLimit = (int) Setting::getVal('ip_register_limit', 5);
        if ($ipRegisterLimit > 0 && User::where('ip_address', $request->ip())->count() >= $ipRegisterLimit) {
            return $this->fail(__('Địa chỉ IP của bạn đã đạt giới hạn đăng ký tối đa :limit tài khoản.', ['limit' => $ipRegisterLimit]), 403, 'IP_LIMIT_REACHED');
        }

        // Xử lý mã giới thiệu (nếu tính năng tiếp thị liên kết đang bật)
        $referredBy = null;
        if (Setting::getVal('referral_enabled', '1') === '1' && !empty($validated['referral_code'])) {
            $referrer = User::where('referral_code', $validated['referral_code'])->first();
            if ($referrer) {
                // Chặn ghi nhận giới thiệu nếu trùng IP với người giới thiệu (chống tự cày hoa hồng)
                $blockSameIp = Setting::getVal('block_same_ip_referral', '1') === '1';
                if (!($blockSameIp && !empty($referrer->ip_address) && $referrer->ip_address === $request->ip())) {
                    $referredBy = $referrer->id;
                }
            }
        }

        // Sinh mã giới thiệu duy nhất cho tài khoản mới
        do {
            $newReferralCode = 'REF' . strtoupper(Str::random(6));
        } while (User::where('referral_code', $newReferralCode)->exists());

        $userName = $validated['name'] ?? explode('@', $validated['email'])[0];

        // Dữ liệu tạo tài khoản mới, tách riêng để dùng chung cho cơ chế retry bên dưới
        $userData = [
            'name' => $userName,
            'email' => $validated['email'],
            // Số điện thoại để trống phải lưu là NULL (không phải chuỗi rỗng) vì cột này có ràng buộc UNIQUE
            'phone' => !empty($validated['phone']) ? $validated['phone'] : null,
            'password' => Hash::make($validated['password']),
            // role='user', status='active', balance=0, total_*=0 dùng giá trị mặc định của DB
            // (các cột này đã được loại khỏi mass-assignment vì lý do bảo mật, xem App\Models\User).
            'referral_code' => $newReferralCode,
            'referred_by' => $referredBy,
            'email_verified_at' => now(),
            'ip_address' => $request->ip(),
            'user_agent' => strip_tags(Str::limit($request->userAgent() ?? 'API Client', 500)),
            'country' => 'Unknown',
        ];

        // Thử tối đa 2 lần: lần 2 chỉ nhằm xử lý trường hợp đụng độ khóa chính ID hiếm gặp
        // (2 request đăng ký gần như đồng thời), không liên quan tới trùng email thật sự.
        $user = null;
        for ($attempt = 1; $attempt <= 2; $attempt++) {
            try {
                $user = User::create($userData);
                break;
            } catch (\Illuminate\Database\QueryException $e) {
                // Kiểm tra nếu lỗi do trùng lặp email ở mức cơ sở dữ liệu
                if (str_contains($e->getMessage(), 'users_email_unique')) {
                    // Xử lý hiện tượng Race Condition / Double Submit: Nếu tài khoản với email này
                    // vừa mới được tạo trong vòng 10 giây trước từ cùng địa chỉ IP
                    // thì ghi nhận đăng ký thành công từ request trước thay vì báo lỗi email đã tồn tại.
                    $existingUser = User::where('email', $validated['email'])->first();
                    if ($existingUser && $existingUser->ip_address === $request->ip() && $existingUser->created_at->gt(now()->subSeconds(10))) {
                        $user = $existingUser;
                        break;
                    }

                    return $this->fail(__('Địa chỉ email này đã được sử dụng trên hệ thống.'), 422, 'EMAIL_EXISTS');
                }

                // Không phải trùng email (thường là đụng độ ID): nếu chưa hết lần thử thì thử lại
                if ($e->getCode() != 23000) {
                    throw $e;
                }

                if ($attempt === 2) {
                    \Log::error('Lỗi đăng ký tài khoản API do trùng khóa sau khi đã thử lại: ' . $e->getMessage());
                    return $this->fail(__('Có lỗi xảy ra, vui lòng thử lại sau ít phút.'), 500, 'REGISTRATION_FAILED');
                }
            }
        }

        // Ghi nhận quan hệ giới thiệu & nhật ký (Bọc try-catch để bảo vệ luồng đăng ký)
        try {
            if ($referredBy) {
                Referral::create([
                    'referrer_id' => $referredBy,
                    'referred_id' => $user->id,
                ]);
                \App\Models\Notification::create([
                    'user_id' => $referredBy,
                    'title' => __('Bạn có thành viên mới đăng ký'),
                    'content' => __('Thành viên :name đã đăng ký tài khoản qua liên kết giới thiệu của bạn.', ['name' => $user->name]),
                ]);
            }

            ActivityLog::log(__('Đăng ký tài khoản thành công (qua Open API)'), $user->id);
        } catch (\Exception $e) {
            \Log::error('Lỗi phụ khi tạo referral/log cho API đăng ký: ' . $e->getMessage());
        }

        // Nếu hệ thống bắt buộc xác minh email: chưa cấp token, gửi mã OTP và yêu cầu xác minh
        if (Setting::getVal('email_verification_enabled', '0') === '1') {
            $user->email_verified_at = null;
            $otp = random_int(100000, 999999);
            $user->otp_code = Hash::make($otp);
            $user->otp_expires_at = now()->addMinutes(10);
            $user->save();

            try {
                Setting::sendEmail($user->email, 'verify_email', [
                    'name' => $user->name,
                    'email' => $user->email,
                    'otp' => $otp,
                ]);
            } catch (\Exception $e) {
                \Log::error('Lỗi gửi email xác minh khi đăng ký qua API: ' . $e->getMessage());
            }

            return $this->ok([
                'email_verification_required' => true,
                'email' => $user->email,
            ], __('Đăng ký thành công! Vui lòng kiểm tra email để lấy mã kích hoạt tài khoản.'), 201);
        }

        return $this->respondWithToken($user, $request, $validated['device_name'] ?? null, __('Đăng ký tài khoản thành công!'), 201);
    }

    /**
     * POST /api/v1/openapi/auth/verify-email
     * Xác minh email bằng mã OTP để kích hoạt tài khoản và cấp token đăng nhập.
     */
    public function verifyEmail(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'email' => 'required|email',
                'otp_code' => 'required|string|size:6',
                'device_name' => 'nullable|string|max:100',
            ]);
        } catch (ValidationException $e) {
            return $this->fail(__('Dữ liệu xác minh không hợp lệ.'), 422, 'VALIDATION_ERROR', $e->errors());
        }

        // Chống brute-force mã xác minh theo email + IP
        $throttleKey = 'api-verify-email:' . Str::lower($validated['email']) . '|' . $request->ip();
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            return $this->fail(__('Bạn đã nhập sai mã quá nhiều lần. Vui lòng thử lại sau.'), 429, 'TOO_MANY_ATTEMPTS');
        }

        $user = User::where('email', $validated['email'])->first();
        if (!$user) {
            RateLimiter::hit($throttleKey, 600);
            return $this->fail(__('Mã xác minh không chính xác hoặc đã hết hiệu lực.'), 422, 'OTP_INVALID');
        }

        if (!is_null($user->email_verified_at)) {
            return $this->fail(__('Tài khoản đã được xác minh trước đó. Vui lòng đăng nhập.'), 409, 'ALREADY_VERIFIED');
        }

        $otpValid = $user->otp_code
            && Hash::check($validated['otp_code'], $user->otp_code)
            && $user->otp_expires_at
            && !$user->otp_expires_at->isPast();

        if (!$otpValid) {
            RateLimiter::hit($throttleKey, 600);
            return $this->fail(__('Mã xác minh không chính xác hoặc đã hết hiệu lực.'), 422, 'OTP_INVALID');
        }

        RateLimiter::clear($throttleKey);
        $user->email_verified_at = now();
        $user->otp_code = null;
        $user->otp_expires_at = null;
        $user->save();

        ActivityLog::log(__('Xác minh email và kích hoạt tài khoản thành công (qua Open API)'), $user->id);

        return $this->respondWithToken($user, $request, $validated['device_name'] ?? null, __('Xác minh tài khoản thành công!'));
    }

    /**
     * POST /api/v1/openapi/auth/verify-email/resend
     * Gửi lại mã OTP xác minh email cho tài khoản chưa kích hoạt.
     */
    public function resendEmailVerification(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate(['email' => 'required|email']);
        } catch (ValidationException $e) {
            return $this->fail(__('Email không hợp lệ.'), 422, 'VALIDATION_ERROR', $e->errors());
        }

        // Chống spam gửi lại mã: tối đa 1 lần/phút theo email
        $throttleKey = 'api-resend-verify:' . Str::lower($validated['email']);
        if (RateLimiter::tooManyAttempts($throttleKey, 1)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            return $this->fail(__('Vui lòng đợi :seconds giây trước khi yêu cầu gửi lại mã mới.', ['seconds' => $seconds]), 429, 'TOO_MANY_REQUESTS');
        }
        RateLimiter::hit($throttleKey, 60);

        $user = User::where('email', $validated['email'])->first();

        // Phản hồi chung, chỉ thực sự gửi khi tài khoản tồn tại và chưa xác minh (tránh dò email)
        if ($user && is_null($user->email_verified_at)) {
            $otp = random_int(100000, 999999);
            $user->otp_code = Hash::make($otp);
            $user->otp_expires_at = now()->addMinutes(10);
            $user->save();
            try {
                Setting::sendEmail($user->email, 'verify_email', [
                    'name' => $user->name,
                    'email' => $user->email,
                    'otp' => $otp,
                ]);
            } catch (\Exception $e) {
                \Log::error('Lỗi gửi lại email xác minh qua API: ' . $e->getMessage());
            }
        }

        return $this->ok(null, __('Nếu tài khoản chưa xác minh, mã kích hoạt mới đã được gửi vào email của bạn.'));
    }

    /**
     * POST /api/v1/openapi/auth/forgot-password
     * Gửi email chứa liên kết đặt lại mật khẩu.
     */
    public function forgotPassword(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate(['email' => 'required|email']);
        } catch (ValidationException $e) {
            return $this->fail(__('Email không hợp lệ.'), 422, 'VALIDATION_ERROR', $e->errors());
        }

        // Chống spam quên mật khẩu: tối đa 3 lần/phút theo IP
        $throttleKey = 'api-forgot-password:' . $request->ip();
        if (RateLimiter::tooManyAttempts($throttleKey, 3)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            return $this->fail(__('Bạn đã gửi yêu cầu quá nhiều lần. Vui lòng thử lại sau :seconds giây.', ['seconds' => $seconds]), 429, 'TOO_MANY_REQUESTS');
        }
        RateLimiter::hit($throttleKey, 60);

        $user = User::where('email', $validated['email'])->first();

        // Chỉ thực sự gửi khi email tồn tại, nhưng luôn trả phản hồi chung để chống dò email
        if ($user) {
            $token = Str::random(60);
            \Illuminate\Support\Facades\DB::table('password_reset_tokens')->updateOrInsert(
                ['email' => $user->email],
                ['token' => Hash::make($token), 'created_at' => now()]
            );
            $resetUrl = route('password.reset', ['token' => $token, 'email' => $user->email]);
            try {
                Setting::sendEmailQueue($user->email, 'forgot_password', [
                    'name' => $user->name,
                    'email' => $user->email,
                    'reset_url' => $resetUrl,
                ]);
                ActivityLog::log("Yêu cầu đặt lại mật khẩu qua Open API cho email {$user->email}", $user->id);
            } catch (\Exception $e) {
                \Log::error('Lỗi gửi email đặt lại mật khẩu qua API: ' . $e->getMessage());
            }
        }

        return $this->ok(null, __('Nếu email tồn tại trong hệ thống, chúng tôi đã gửi liên kết đặt lại mật khẩu.'));
    }

    /**
     * POST /api/v1/openapi/auth/reset-password
     * Đặt lại mật khẩu mới bằng token nhận được từ email.
     */
    public function resetPassword(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'token' => 'required|string',
                'email' => 'required|email',
                'password' => 'required|string|min:8|confirmed',
            ]);
        } catch (ValidationException $e) {
            return $this->fail(__('Dữ liệu đặt lại mật khẩu không hợp lệ.'), 422, 'VALIDATION_ERROR', $e->errors());
        }

        $record = \Illuminate\Support\Facades\DB::table('password_reset_tokens')->where('email', $validated['email'])->first();
        if (!$record || !Hash::check($validated['token'], $record->token)) {
            return $this->fail(__('Yêu cầu đặt lại mật khẩu đã hết hạn hoặc không hợp lệ.'), 422, 'INVALID_TOKEN');
        }

        // Token chỉ có hiệu lực trong 60 phút
        if (\Carbon\Carbon::parse($record->created_at)->addMinutes(60)->isPast()) {
            \Illuminate\Support\Facades\DB::table('password_reset_tokens')->where('email', $validated['email'])->delete();
            return $this->fail(__('Yêu cầu đặt lại mật khẩu đã hết hạn.'), 422, 'TOKEN_EXPIRED');
        }

        $user = User::where('email', $validated['email'])->first();
        if (!$user) {
            return $this->fail(__('Không tìm thấy thông tin tài khoản.'), 404, 'USER_NOT_FOUND');
        }

        $user->password = Hash::make($validated['password']);
        $user->setRememberToken(Str::random(60));
        $user->save();

        // Thu hồi mọi token API cũ để buộc đăng nhập lại trên mọi thiết bị
        ApiToken::where('user_id', $user->id)->delete();
        \Illuminate\Support\Facades\DB::table('password_reset_tokens')->where('email', $validated['email'])->delete();

        ActivityLog::log('Đặt lại mật khẩu thành công qua Open API', $user->id);

        return $this->ok(null, __('Mật khẩu của bạn đã được cập nhật thành công. Vui lòng đăng nhập lại.'));
    }

    /**
     * POST /api/v1/openapi/auth/login
     * Đăng nhập bằng email + mật khẩu, trả về token truy cập.
     */
    public function login(Request $request): JsonResponse
    {
        try {
            // Trường email chấp nhận cả Email lẫn Số điện thoại để đồng bộ với form đăng nhập trên web
            $validated = $request->validate([
                'email' => 'required|string|max:255',
                'password' => 'required|string',
                'device_name' => 'nullable|string|max:100',
            ]);
        } catch (ValidationException $e) {
            return $this->fail(__('Vui lòng nhập Email hoặc Số điện thoại và mật khẩu.'), 422, 'VALIDATION_ERROR', $e->errors());
        }

        // Chuẩn hoá thông tin đăng nhập để bộ đếm chống dò mật khẩu không bị lệch khi nhập SĐT ở nhiều định dạng
        $loginInput = trim($validated['email']);
        $loginKey = filter_var($loginInput, FILTER_VALIDATE_EMAIL)
            ? Str::lower($loginInput)
            : (User::normalizePhone($loginInput) ?? Str::lower($loginInput));

        // Chống dò mật khẩu (brute-force) theo thông tin đăng nhập + IP
        $throttleKey = 'api-login:' . $loginKey . '|' . $request->ip();
        $maxAttempts = (int) Setting::getVal('login_max_attempts', 5);
        if ($maxAttempts <= 0) {
            $maxAttempts = 5;
        }
        if (RateLimiter::tooManyAttempts($throttleKey, $maxAttempts)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            return $this->fail(__('Bạn đã đăng nhập sai quá nhiều lần. Vui lòng thử lại sau :seconds giây.', ['seconds' => $seconds]), 429, 'TOO_MANY_ATTEMPTS');
        }

        // Dò tìm tài khoản theo Email HOẶC Số điện thoại
        $user = User::findByLogin($loginInput);

        // Đối chiếu thông tin đăng nhập
        if (!$user || !Hash::check($validated['password'], $user->password)) {
            RateLimiter::hit($throttleKey, (int) Setting::getVal('login_lockout_duration', 15) * 60);
            return $this->fail(__('Thông tin đăng nhập hoặc mật khẩu không chính xác.'), 401, 'INVALID_CREDENTIALS');
        }

        // Tài khoản bị khóa/ngưng hoạt động
        if ($user->status !== 'active') {
            return $this->fail(__('Tài khoản của bạn đã bị khóa hoặc ngưng hoạt động.'), 403, 'ACCOUNT_INACTIVE');
        }

        // Mật khẩu đã đúng: xóa bộ đếm chống dò mật khẩu
        RateLimiter::clear($throttleKey);

        // Bắt buộc xác minh email: nếu hệ thống yêu cầu và tài khoản chưa kích hoạt thì
        // KHÔNG cấp token, gửi lại mã OTP và yêu cầu App hoàn tất bước xác minh email trước.
        // Lưu ý: Tài khoản đăng ký bằng Số điện thoại chưa có email nên được bỏ qua bước xác minh này
        if (Setting::getVal('email_verification_enabled', '0') === '1' && is_null($user->email_verified_at) && !empty($user->email)) {
            $otp = random_int(100000, 999999);
            $user->otp_code = Hash::make($otp);
            $user->otp_expires_at = now()->addMinutes(10);
            $user->save();

            try {
                Setting::sendEmail($user->email, 'verify_email', [
                    'name' => $user->name,
                    'email' => $user->email,
                    'otp' => $otp,
                ]);
            } catch (\Exception $e) {
                \Log::error('Lỗi gửi email xác minh khi đăng nhập qua API: ' . $e->getMessage());
            }

            return $this->ok([
                'email_verification_required' => true,
                'email' => $user->email,
            ], __('Tài khoản của bạn chưa được kích hoạt. Vui lòng xác minh email để tiếp tục.'));
        }

        // Tài khoản bật bảo mật 2 lớp (2FA): chuyển sang bước xác thực mã, chưa cấp token
        if ($user->google2fa_enabled || $user->email_otp_enabled) {
            $challenge = $this->startTwoFactorChallenge($user);

            return $this->ok([
                'two_factor_required' => true,
                'challenge_token' => $challenge['challenge_token'],
                'methods' => $challenge['methods'],
            ], __('Vui lòng nhập mã xác thực bảo mật 2 lớp để hoàn tất đăng nhập.'));
        }

        // Không bật 2FA: cấp token đăng nhập ngay
        ActivityLog::log(__('Đăng nhập thành công (qua Open API)'), $user->id);

        return $this->respondWithToken($user, $request, $validated['device_name'] ?? null, __('Đăng nhập thành công!'));
    }

    /**
     * POST /api/v1/openapi/auth/login/2fa
     * Hoàn tất đăng nhập cho tài khoản bật 2FA bằng cách xác thực mã Google Authenticator
     * và/hoặc mã OTP email, dựa trên challenge_token nhận được ở bước đăng nhập.
     */
    public function loginTwoFactor(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'challenge_token' => 'required|string',
                'google2fa_code' => 'nullable|string',
                'email_otp_code' => 'nullable|string',
                'device_name' => 'nullable|string|max:100',
            ]);
        } catch (ValidationException $e) {
            return $this->fail(__('Dữ liệu xác thực không hợp lệ.'), 422, 'VALIDATION_ERROR', $e->errors());
        }

        $cacheKey = 'api_2fa_challenge:' . hash('sha256', $validated['challenge_token']);

        // Chống brute-force mã 2FA theo từng phiên challenge (tối đa 5 lần)
        $throttleKey = 'api-2fa-verify:' . hash('sha256', $validated['challenge_token']);
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            Cache::forget($cacheKey);
            return $this->fail(__('Bạn đã nhập sai mã xác thực quá nhiều lần. Vui lòng đăng nhập lại.'), 429, 'TOO_MANY_ATTEMPTS');
        }

        $challenge = Cache::get($cacheKey);
        if (!is_array($challenge) || empty($challenge['user_id'])) {
            return $this->fail(__('Phiên xác thực đã hết hạn. Vui lòng đăng nhập lại.'), 401, 'CHALLENGE_EXPIRED');
        }

        $user = User::find($challenge['user_id']);
        if (!$user || $user->status !== 'active') {
            Cache::forget($cacheKey);
            return $this->fail(__('Tài khoản của bạn đã bị khóa hoặc ngưng hoạt động.'), 403, 'ACCOUNT_INACTIVE');
        }

        $methods = $challenge['methods'] ?? [];

        // 1. Xác thực mã Google Authenticator (nếu tài khoản bật)
        if (in_array('google2fa', $methods, true)) {
            if (empty($validated['google2fa_code']) || strlen($validated['google2fa_code']) !== 6) {
                RateLimiter::hit($throttleKey, 600);
                return $this->fail(__('Vui lòng nhập mã Google Authenticator gồm 6 chữ số.'), 422, 'GOOGLE2FA_REQUIRED');
            }
            try {
                $secret = Crypt::decryptString($user->google2fa_secret);
                $valid = (new Google2FA())->verifyKey($secret, $validated['google2fa_code']);
            } catch (\Exception $e) {
                $valid = false;
            }
            if (!$valid) {
                RateLimiter::hit($throttleKey, 600);
                return $this->fail(__('Mã xác thực Google Authenticator không chính xác.'), 422, 'GOOGLE2FA_INVALID');
            }
        }

        // 2. Xác thực mã OTP qua email (nếu tài khoản bật)
        if (in_array('email_otp', $methods, true)) {
            if (empty($validated['email_otp_code']) || strlen($validated['email_otp_code']) !== 6) {
                RateLimiter::hit($throttleKey, 600);
                return $this->fail(__('Vui lòng nhập mã OTP email gồm 6 chữ số.'), 422, 'EMAIL_OTP_REQUIRED');
            }
            $otpValid = $user->otp_code
                && Hash::check($validated['email_otp_code'], $user->otp_code)
                && $user->otp_expires_at
                && !$user->otp_expires_at->isPast();
            if (!$otpValid) {
                RateLimiter::hit($throttleKey, 600);
                return $this->fail(__('Mã OTP email không chính xác hoặc đã hết hiệu lực.'), 422, 'EMAIL_OTP_INVALID');
            }
        }

        // Xác thực thành công: dọn dẹp challenge, OTP và cấp token
        RateLimiter::clear($throttleKey);
        Cache::forget($cacheKey);
        if (in_array('email_otp', $methods, true)) {
            $user->otp_code = null;
            $user->otp_expires_at = null;
            $user->save();
        }

        ActivityLog::log(__('Đăng nhập thành công qua Open API (đã xác minh 2FA)'), $user->id);

        return $this->respondWithToken($user, $request, $validated['device_name'] ?? null, __('Đăng nhập thành công!'));
    }

    /**
     * POST /api/v1/openapi/auth/login/2fa/resend
     * Gửi lại mã OTP email cho phiên đăng nhập 2FA đang chờ xác thực.
     */
    public function resendTwoFactorOtp(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'challenge_token' => 'required|string',
            ]);
        } catch (ValidationException $e) {
            return $this->fail(__('Thiếu mã phiên xác thực.'), 422, 'VALIDATION_ERROR', $e->errors());
        }

        $cacheKey = 'api_2fa_challenge:' . hash('sha256', $validated['challenge_token']);
        $challenge = Cache::get($cacheKey);
        if (!is_array($challenge) || empty($challenge['user_id']) || !in_array('email_otp', $challenge['methods'] ?? [], true)) {
            return $this->fail(__('Phiên xác thực không hợp lệ hoặc không dùng OTP email.'), 400, 'INVALID_CHALLENGE');
        }

        // Chống spam gửi lại OTP: tối đa 3 lần/phút mỗi phiên
        $throttleKey = 'api-2fa-resend:' . hash('sha256', $validated['challenge_token']);
        if (RateLimiter::tooManyAttempts($throttleKey, 3)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            return $this->fail(__('Bạn đã yêu cầu gửi mã quá nhanh. Vui lòng thử lại sau :seconds giây.', ['seconds' => $seconds]), 429, 'TOO_MANY_REQUESTS');
        }
        RateLimiter::hit($throttleKey, 60);

        $user = User::find($challenge['user_id']);
        if (!$user) {
            return $this->fail(__('Không tìm thấy tài khoản.'), 404, 'USER_NOT_FOUND');
        }

        $otp = (string) random_int(100000, 999999);
        $user->otp_code = Hash::make($otp);
        $user->otp_expires_at = now()->addMinutes(10);
        $user->save();

        try {
            Setting::sendEmail($user->email, 'otp', [
                'name' => $user->name,
                'email' => $user->email,
                'otp' => $otp,
            ]);
            return $this->ok(null, __('Mã OTP mới đã được gửi vào email của bạn.'));
        } catch (\Exception $e) {
            \Log::error('Lỗi gửi lại OTP 2FA qua API: ' . $e->getMessage());
            return $this->fail(__('Có lỗi xảy ra khi gửi email OTP. Vui lòng thử lại sau.'), 500, 'OTP_SEND_FAILED');
        }
    }

    /**
     * POST /api/v1/openapi/auth/logout
     * Thu hồi token hiện tại (đăng xuất thiết bị đang gọi).
     */
    public function logout(Request $request): JsonResponse
    {
        $token = $request->attributes->get('api_token');
        if ($token instanceof ApiToken) {
            ActivityLog::log(__('Đăng xuất Open API (thu hồi token)'), $request->user()->id);
            $token->delete();
        }

        return $this->ok(null, __('Đăng xuất thành công.'));
    }
}
