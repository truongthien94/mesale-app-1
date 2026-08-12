<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\NativeOAuthVerificationException;
use App\Models\ActivityLog;
use App\Models\ApiToken;
use App\Models\Setting;
use App\Models\User;
use App\Services\GoogleNativeOAuthTokenVerifier;
use App\Services\ReferralOnboardingService;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class GoogleNativeOAuthController extends ApiController
{
    public function __construct(private readonly ReferralOnboardingService $referralOnboarding) {}

    public function __invoke(Request $request, GoogleNativeOAuthTokenVerifier $verifier): JsonResponse
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
            $identity = $verifier->verify($validated['id_token']);
        } catch (NativeOAuthVerificationException $e) {
            $message = $e->httpStatus === 503
                ? __('Không thể xác minh Google vào lúc này. Vui lòng thử lại sau.')
                : __('Thông tin đăng nhập Google không hợp lệ hoặc đã hết hạn.');

            return $this->fail($message, $e->httpStatus, $e->errorCode);
        }

        $lock = Cache::lock('native_oauth:google:'.hash('sha256', $identity['sub']), 10);
        if (! $lock->get()) {
            return $this->fail(__('Yêu cầu đăng nhập đang được xử lý. Vui lòng thử lại.'), 409, 'OAUTH_REQUEST_IN_PROGRESS');
        }

        try {
            $result = $this->resolveGoogleUser($identity, $request);
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

        try {
            ActivityLog::log(__('Đăng nhập thành công qua Open API OAuth Google'), $result->id);
        } catch (\Throwable) {
            // Audit logging is secondary and must not invalidate an authenticated session.
        }

        return $this->respondWithToken($result, $request, $validated['device_name'] ?? null);
    }

    private function resolveGoogleUser(array $identity, Request $request): User|JsonResponse
    {
        $providerUsers = User::where('google_id', $identity['sub'])->limit(2)->get();

        if ($providerUsers->count() > 1) {
            return $this->fail(
                __('Danh tính đăng nhập đang liên kết không nhất quán. Vui lòng liên hệ hỗ trợ.'),
                409,
                'OAUTH_IDENTITY_AMBIGUOUS'
            );
        }

        if ($providerUsers->count() === 1) {
            $user = $providerUsers->first();
            if ($user->status === 'active'
                && $identity['email_verified']
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

        if (empty($identity['email'])) {
            return $this->fail(__('Google không cung cấp email đã xác minh.'), 422, 'OAUTH_EMAIL_REQUIRED');
        }

        if (! $identity['email_verified']) {
            return $this->fail(__('Email từ Google chưa được xác minh.'), 422, 'OAUTH_EMAIL_UNVERIFIED');
        }

        if (User::whereRaw('LOWER(email) = ?', [$identity['email']])->exists()) {
            return $this->fail(
                __('Email này đã thuộc một tài khoản khác. Vui lòng đăng nhập bằng phương thức hiện tại để liên kết an toàn.'),
                409,
                'ACCOUNT_LINK_REQUIRED'
            );
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

        $user = new User;
        $user->forceFill([
            'name' => $identity['name'] ?? explode('@', $identity['email'])[0],
            'email' => $identity['email'],
            'password' => Hash::make(Str::random(64)),
            'google_id' => $identity['sub'],
            'avatar' => $identity['picture'] ?? null,
            'email_verified_at' => now(),
            'referral_code' => $referralCode,
            'ip_address' => $request->ip(),
            'user_agent' => strip_tags(Str::limit($request->userAgent() ?? 'API Client', 500)),
            'country' => 'Unknown',
            ...$this->referralOnboarding->registrationAttributes(),
        ]);

        try {
            $user->save();
        } catch (QueryException $e) {
            if ($e->getCode() === '23000' && $this->googleIdentityOrEmailExists($identity)) {
                return $this->fail(
                    __('Danh tính Google hoặc email này vừa được liên kết. Vui lòng thử đăng nhập lại.'),
                    409,
                    'ACCOUNT_LINK_REQUIRED'
                );
            }
            throw $e;
        }

        return $user->refresh();
    }

    private function googleIdentityOrEmailExists(array $identity): bool
    {
        if (User::where('google_id', $identity['sub'])->exists()) {
            return true;
        }

        return ! empty($identity['email'])
            && User::whereRaw('LOWER(email) = ?', [$identity['email']])->exists();
    }

    private function respondWithToken(User $user, Request $request, ?string $deviceName): JsonResponse
    {
        $days = (int) Setting::getVal('openapi_token_ttl_days', 0);
        [$plainToken, $apiToken] = ApiToken::generateFor(
            $user,
            $deviceName ?? 'Mesale Mobile',
            $days > 0 ? $days : null,
            $request->ip()
        );

        return $this->ok([
            'access_token' => $plainToken,
            'token' => $plainToken,
            'token_type' => 'Bearer',
            'expires_at' => optional($apiToken->expires_at)->toIso8601String(),
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'avatar' => $user->avatar,
                'balance' => (int) round((float) $user->balance),
                'total_cashback' => (int) round((float) $user->total_cashback),
                'total_referral_earned' => (int) round((float) $user->total_referral_earned),
                'total_withdrawn' => (int) round((float) $user->total_withdrawn),
                'referral_code' => $user->referral_code,
                ...$this->referralOnboarding->apiFields($user),
                'status' => $user->status,
                'email_verified' => ! is_null($user->email_verified_at),
                'created_at' => optional($user->created_at)->toIso8601String(),
            ],
        ], __('Đăng nhập thành công!'));
    }

    /** @return array{challenge_token: string, methods: array<int, string>} */
    private function startTwoFactorChallenge(User $user): array
    {
        $methods = [];
        if ($user->google2fa_enabled) {
            $methods[] = 'google2fa';
        }
        if ($user->email_otp_enabled) {
            $methods[] = 'email_otp';
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
            } catch (\Throwable) {
                // The challenge remains valid if email delivery is temporarily unavailable.
            }
        }

        $challenge = Str::random(64);
        Cache::put(
            'api_2fa_challenge:'.hash('sha256', $challenge),
            ['user_id' => $user->id, 'methods' => $methods],
            now()->addMinutes(10)
        );

        return ['challenge_token' => $challenge, 'methods' => $methods];
    }
}
