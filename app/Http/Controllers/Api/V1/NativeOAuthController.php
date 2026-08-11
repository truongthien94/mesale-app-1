<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\NativeOAuthVerificationException;
use App\Models\ActivityLog;
use App\Models\Setting;
use App\Models\User;
use App\Services\NativeOAuthTokenVerifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class NativeOAuthController extends AuthController
{
    public function google(Request $request, NativeOAuthTokenVerifier $oauthVerifier): JsonResponse
    {
        try {
            $validated = $request->validate([
                'id_token' => 'required|string|max:10000',
                'device_name' => 'nullable|string|max:100',
            ]);
        } catch (ValidationException $e) {
            return $this->fail(__('Dữ liệu đăng nhập Google không hợp lệ.'), 422, 'VALIDATION_ERROR', $e->errors());
        }

        try {
            $identity = $oauthVerifier->verifyGoogle($validated['id_token']);
        } catch (NativeOAuthVerificationException $e) {
            return $this->verificationFailure('Google', $e);
        }

        return $this->complete('google', $identity, $request, $validated['device_name'] ?? null);
    }

    public function apple(Request $request, NativeOAuthTokenVerifier $oauthVerifier): JsonResponse
    {
        try {
            $validated = $request->validate([
                'identity_token' => 'required|string|max:10000',
                'authorization_code' => 'required|string|max:2048',
                'nonce' => 'required|string|min:16|max:512',
                'device_name' => 'nullable|string|max:100',
            ]);
        } catch (ValidationException $e) {
            return $this->fail(__('Dữ liệu đăng nhập Apple không hợp lệ.'), 422, 'VALIDATION_ERROR', $e->errors());
        }

        try {
            $identity = $oauthVerifier->verifyApple(
                $validated['identity_token'],
                $validated['nonce'],
                $validated['authorization_code']
            );
        } catch (NativeOAuthVerificationException $e) {
            return $this->verificationFailure('Apple', $e);
        }

        return $this->complete('apple', $identity, $request, $validated['device_name'] ?? null);
    }

    private function complete(
        string $provider,
        array $identity,
        Request $request,
        ?string $deviceName
    ): JsonResponse {
        $providerColumn = $provider === 'apple' ? 'apple_id' : 'google_id';
        $lock = Cache::lock("native_oauth:{$provider}:".hash('sha256', $identity['sub']), 10);
        if (! $lock->get()) {
            return $this->fail(__('Yêu cầu đăng nhập đang được xử lý. Vui lòng thử lại.'), 409, 'OAUTH_REQUEST_IN_PROGRESS');
        }

        try {
            $result = DB::transaction(function () use ($provider, $providerColumn, $identity, $request) {
                $providerUsers = User::where($providerColumn, $identity['sub'])
                    ->lockForUpdate()
                    ->limit(2)
                    ->get();

                if ($providerUsers->count() > 1) {
                    return $this->fail(
                        __('Danh tính đăng nhập đang liên kết không nhất quán. Vui lòng liên hệ hỗ trợ.'),
                        409,
                        'OAUTH_IDENTITY_AMBIGUOUS'
                    );
                }

                if ($providerUsers->count() === 1) {
                    $user = $providerUsers->first();
                    if ($user->status !== 'active') {
                        return $user;
                    }

                    if ($identity['email_verified']
                        && ! empty($identity['email'])
                        && ! empty($user->email)
                        && strtolower($user->email) === $identity['email']
                        && is_null($user->email_verified_at)
                    ) {
                        $user->email_verified_at = now();
                        $user->save();
                    }

                    return $user;
                }

                if (! empty($identity['email'])) {
                    if (! $identity['email_verified']) {
                        return $this->fail(__('Email từ nhà cung cấp chưa được xác minh.'), 422, 'OAUTH_EMAIL_UNVERIFIED');
                    }

                    if (User::whereRaw('LOWER(email) = ?', [$identity['email']])->lockForUpdate()->exists()) {
                        return $this->fail(
                            __('Email này đã thuộc một tài khoản khác. Vui lòng đăng nhập bằng phương thức hiện tại để liên kết an toàn.'),
                            409,
                            'ACCOUNT_LINK_REQUIRED'
                        );
                    }
                } elseif ($provider === 'google') {
                    return $this->fail(__('Google không cung cấp email đã xác minh.'), 422, 'OAUTH_EMAIL_REQUIRED');
                }

                if (Setting::getVal('registration_enabled', '1') !== '1') {
                    return $this->fail(__('Hệ thống hiện tại đã tạm ngưng đăng ký thành viên mới.'), 403, 'REGISTRATION_DISABLED');
                }

                $ipRegisterLimit = (int) Setting::getVal('ip_register_limit', 5);
                if ($ipRegisterLimit > 0 && User::where('ip_address', $request->ip())->count() >= $ipRegisterLimit) {
                    return $this->fail(__('Địa chỉ IP đã đạt giới hạn đăng ký tài khoản.'), 403, 'IP_LIMIT_REACHED');
                }

                do {
                    $referralCode = 'REF'.strtoupper(Str::random(6));
                } while (User::where('referral_code', $referralCode)->exists());

                $email = $identity['email'];
                $user = new User;
                $user->forceFill([
                    'name' => $identity['name'] ?? ($email ? explode('@', $email)[0] : 'Apple Member'),
                    'email' => $email,
                    'password' => Hash::make(Str::random(64)),
                    $providerColumn => $identity['sub'],
                    'avatar' => $identity['picture'] ?? null,
                    'email_verified_at' => $identity['email_verified'] && $email ? now() : null,
                    'referral_code' => $referralCode,
                    'ip_address' => $request->ip(),
                    'user_agent' => strip_tags(Str::limit($request->userAgent() ?? 'API Client', 500)),
                    'country' => 'Unknown',
                    ...$this->referralOnboarding->registrationAttributes(),
                ]);
                $user->save();

                return $user->refresh();
            });
        } finally {
            $lock->release();
        }

        if ($result instanceof JsonResponse) {
            return $result;
        }

        if ($result->status !== 'active') {
            return $this->fail(__('Tài khoản của bạn đã bị khóa hoặc ngưng hoạt động.'), 403, 'ACCOUNT_INACTIVE');
        }

        if ($result->google2fa_enabled || $result->email_otp_enabled) {
            $challenge = $this->startTwoFactorChallenge($result);

            return $this->ok([
                'two_factor_required' => true,
                'challenge_token' => $challenge['challenge_token'],
                'methods' => $challenge['methods'],
            ], __('Vui lòng nhập mã xác thực bảo mật 2 lớp để hoàn tất đăng nhập.'));
        }

        ActivityLog::log(__('Đăng nhập thành công qua Open API OAuth :provider', ['provider' => $provider]), $result->id);

        return $this->respondWithToken($result, $request, $deviceName, __('Đăng nhập thành công!'));
    }

    private function verificationFailure(string $provider, NativeOAuthVerificationException $e): JsonResponse
    {
        $message = $e->httpStatus === 503
            ? __('Không thể xác minh :provider vào lúc này. Vui lòng thử lại sau.', ['provider' => $provider])
            : __('Thông tin đăng nhập :provider không hợp lệ, đã hết hạn hoặc đã được sử dụng.', ['provider' => $provider]);

        return $this->fail($message, $e->httpStatus, $e->errorCode);
    }
}
