<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\ActivityLog;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use PragmaRX\Google2FA\Google2FA;

/**
 * API Quản lý bảo mật tài khoản: bật/tắt 2FA (Google Authenticator) và Email OTP.
 *
 * Thiết kế phi trạng thái (stateless) cho App: bước setup trả về secret để App tự giữ,
 * gửi lại kèm mã xác thực ở bước enable (không phụ thuộc session như luồng web).
 */
class SecurityController extends ApiController
{
    /**
     * GET /api/v1/openapi/security
     * Trạng thái các lớp bảo mật của tài khoản.
     */
    public function show(Request $request): JsonResponse
    {
        $user = $this->apiUser($request);

        return $this->ok([
            'google2fa_enabled' => (bool) $user->google2fa_enabled,
            'email_otp_enabled' => (bool) $user->email_otp_enabled,
            'email_verified' => !is_null($user->email_verified_at),
        ]);
    }

    /**
     * POST /api/v1/openapi/security/2fa/setup
     * Sinh secret key mới + URL/QR để App hiển thị cho người dùng quét bằng Google Authenticator.
     * App cần lưu tạm secret_key để gửi lại ở bước enable.
     */
    public function setup2FA(Request $request): JsonResponse
    {
        $user = $this->apiUser($request);

        if ($user->google2fa_enabled) {
            return $this->fail(__('Tài khoản của bạn đã kích hoạt bảo mật 2FA trước đó.'), 400, 'ALREADY_ENABLED');
        }

        $google2fa = new Google2FA();
        $secretKey = $google2fa->generateSecretKey();
        $otpauthUrl = $google2fa->getQRCodeUrl('HoanTienShopee', $user->email, $secretKey);

        return $this->ok([
            'secret_key' => $secretKey,
            'otpauth_url' => $otpauthUrl,
            'qr_code_url' => 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' . urlencode($otpauthUrl),
        ]);
    }

    /**
     * POST /api/v1/openapi/security/2fa/enable
     * Body: secret (từ bước setup), otp_code — xác minh & kích hoạt 2FA.
     */
    public function enable2FA(Request $request): JsonResponse
    {
        $user = $this->apiUser($request);

        if ($user->google2fa_enabled) {
            return $this->fail(__('Tài khoản của bạn đã kích hoạt bảo mật 2FA trước đó.'), 400, 'ALREADY_ENABLED');
        }

        try {
            $validated = $request->validate([
                'secret' => 'required|string',
                'otp_code' => 'required|string|size:6',
            ]);
        } catch (ValidationException $e) {
            return $this->fail(__('Dữ liệu không hợp lệ.'), 422, 'VALIDATION_ERROR', $e->errors());
        }

        $google2fa = new Google2FA();
        try {
            $isValid = $google2fa->verifyKey($validated['secret'], $validated['otp_code']);
        } catch (\Exception $e) {
            \Log::error('Lỗi xác thực Google 2FA (Open API): ' . $e->getMessage());
            return $this->fail(__('Lỗi hệ thống khi xác thực 2FA. Vui lòng thử lại sau.'), 500, 'TFA_ERROR');
        }

        if (!$isValid) {
            return $this->fail(__('Mã OTP Google Authenticator không chính xác. Vui lòng kiểm tra lại.'), 400, 'INVALID_OTP');
        }

        $model = User::find($user->id);
        // Mã hóa secret trước khi lưu để chống lộ khi DB bị tấn công
        $model->google2fa_secret = Crypt::encryptString($validated['secret']);
        $model->google2fa_enabled = true;
        $model->save();

        ActivityLog::log(__('Kích hoạt bảo mật 2FA Google Authenticator (qua Open API)'), $user->id);

        return $this->ok(null, __('Kích hoạt bảo mật 2FA thành công!'));
    }

    /**
     * POST /api/v1/openapi/security/2fa/disable
     * Body: password, otp_code — xác minh mật khẩu + mã hiện tại rồi tắt 2FA.
     */
    public function disable2FA(Request $request): JsonResponse
    {
        $user = $this->apiUser($request);

        if (!$user->google2fa_enabled) {
            return $this->fail(__('Tài khoản chưa bật bảo mật 2FA.'), 400, 'NOT_ENABLED');
        }

        try {
            $validated = $request->validate([
                'password' => 'required|string',
                'otp_code' => 'required|string|size:6',
            ]);
        } catch (ValidationException $e) {
            return $this->fail(__('Dữ liệu không hợp lệ.'), 422, 'VALIDATION_ERROR', $e->errors());
        }

        if (!Hash::check($validated['password'], $user->password)) {
            return $this->fail(__('Mật khẩu tài khoản hiện tại không chính xác.'), 400, 'WRONG_PASSWORD');
        }

        $google2fa = new Google2FA();
        try {
            $decryptedSecret = Crypt::decryptString($user->google2fa_secret);
            $isValid = $google2fa->verifyKey($decryptedSecret, $validated['otp_code']);
        } catch (\Exception $e) {
            \Log::error('Lỗi huỷ 2FA (Open API): ' . $e->getMessage());
            return $this->fail(__('Lỗi hệ thống khi xác thực 2FA. Vui lòng thử lại sau.'), 500, 'TFA_ERROR');
        }

        if (!$isValid) {
            return $this->fail(__('Mã OTP Google Authenticator không chính xác.'), 400, 'INVALID_OTP');
        }

        $model = User::find($user->id);
        $model->google2fa_secret = null;
        $model->google2fa_enabled = false;
        $model->save();

        ActivityLog::log(__('Huỷ kích hoạt bảo mật 2FA Google Authenticator (qua Open API)'), $user->id);

        return $this->ok(null, __('Đã huỷ kích hoạt bảo mật 2FA thành công.'));
    }

    /**
     * POST /api/v1/openapi/security/email-otp/send
     * Gửi mã OTP 6 số về email để chuẩn bị bật/tắt Email OTP (giới hạn 1 lần/phút).
     */
    public function sendEmailOTP(Request $request): JsonResponse
    {
        $user = $this->apiUser($request);

        $throttleKey = 'api-send-email-otp:' . $user->id;
        if (RateLimiter::tooManyAttempts($throttleKey, 1)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            return $this->fail(__('Bạn đã gửi mã OTP gần đây. Vui lòng chờ :seconds giây.', ['seconds' => $seconds]), 429, 'RATE_LIMITED');
        }
        RateLimiter::hit($throttleKey, 60);

        $otp = random_int(100000, 999999);

        $model = User::find($user->id);
        $model->otp_code = Hash::make($otp);
        $model->otp_expires_at = now()->addMinutes(10);
        $model->save();

        try {
            Setting::sendEmail($user->email, 'otp', [
                'name' => $user->name,
                'email' => $user->email,
                'otp' => $otp,
            ]);
        } catch (\Exception $e) {
            \Log::error('Lỗi gửi OTP Email (Open API): ' . $e->getMessage());
            return $this->fail(__('Có lỗi xảy ra khi gửi email OTP. Vui lòng thử lại sau.'), 500, 'EMAIL_FAILED');
        }

        ActivityLog::log(__('Yêu cầu gửi mã OTP xác thực về email (qua Open API)'), $user->id);

        return $this->ok(null, __('Mã OTP đã được gửi đến email của bạn. Vui lòng kiểm tra hộp thư.'));
    }

    /**
     * POST /api/v1/openapi/security/email-otp/enable
     * Body: otp_code — xác minh mã đã gửi và bật Email OTP.
     */
    public function enableEmailOTP(Request $request): JsonResponse
    {
        $user = $this->apiUser($request);

        try {
            $validated = $request->validate(['otp_code' => 'required|string|size:6']);
        } catch (ValidationException $e) {
            return $this->fail(__('Dữ liệu không hợp lệ.'), 422, 'VALIDATION_ERROR', $e->errors());
        }

        if (!$this->verifyEmailOtp($user, $validated['otp_code'])) {
            return $this->fail(__('Mã OTP không chính xác hoặc đã hết hiệu lực. Vui lòng bấm nhận lại mã.'), 400, 'INVALID_OTP');
        }

        $model = User::find($user->id);
        $model->email_otp_enabled = true;
        $model->otp_code = null;
        $model->otp_expires_at = null;
        $model->save();

        ActivityLog::log(__('Kích hoạt bảo mật xác thực OTP qua Email (qua Open API)'), $user->id);

        return $this->ok(null, __('Kích hoạt bảo mật xác thực OTP qua Email thành công!'));
    }

    /**
     * POST /api/v1/openapi/security/email-otp/disable
     * Body: password, otp_code — xác minh rồi tắt Email OTP.
     */
    public function disableEmailOTP(Request $request): JsonResponse
    {
        $user = $this->apiUser($request);

        try {
            $validated = $request->validate([
                'password' => 'required|string',
                'otp_code' => 'required|string|size:6',
            ]);
        } catch (ValidationException $e) {
            return $this->fail(__('Dữ liệu không hợp lệ.'), 422, 'VALIDATION_ERROR', $e->errors());
        }

        if (!Hash::check($validated['password'], $user->password)) {
            return $this->fail(__('Mật khẩu tài khoản hiện tại không chính xác.'), 400, 'WRONG_PASSWORD');
        }

        if (!$this->verifyEmailOtp($user, $validated['otp_code'])) {
            return $this->fail(__('Mã OTP không chính xác hoặc đã hết hiệu lực.'), 400, 'INVALID_OTP');
        }

        $model = User::find($user->id);
        $model->email_otp_enabled = false;
        $model->otp_code = null;
        $model->otp_expires_at = null;
        $model->save();

        ActivityLog::log(__('Huỷ kích hoạt bảo mật xác thực OTP qua Email (qua Open API)'), $user->id);

        return $this->ok(null, __('Đã huỷ kích hoạt bảo mật OTP qua Email thành công.'));
    }

    /**
     * Kiểm tra mã OTP email còn hạn và trùng khớp bản băm đã lưu.
     */
    private function verifyEmailOtp(User $user, string $code): bool
    {
        return $user->otp_code
            && Hash::check($code, $user->otp_code)
            && $user->otp_expires_at
            && !$user->otp_expires_at->isPast();
    }
}
