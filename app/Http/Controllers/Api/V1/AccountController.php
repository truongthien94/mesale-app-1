<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\NativeOAuthVerificationException;
use App\Helpers\MoneyHelper;
use App\Models\ActivityLog;
use App\Models\ApiToken;
use App\Models\CashbackHistory;
use App\Models\Currency;
use App\Models\Language;
use App\Models\Notification;
use App\Models\Referral;
use App\Models\Setting;
use App\Models\User;
use App\Models\Withdrawal;
use App\Services\NativeOAuthTokenVerifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * API Tài khoản: Lấy thông tin hồ sơ, số dư, cập nhật hồ sơ, đổi mật khẩu và xóa tài khoản.
 */
class AccountController extends ApiController
{
    /**
     * GET /api/v1/openapi/account
     * Trả về thông tin tài khoản, số dư ví và các thống kê tổng hợp.
     */
    public function show(Request $request): JsonResponse
    {
        $user = $this->apiUser($request);

        // Thống kê nhanh số lượng đơn hoàn tiền theo trạng thái và số người giới thiệu
        $cashbackStats = CashbackHistory::where('user_id', $user->id)
            ->selectRaw('status, COUNT(*) as total, COALESCE(SUM(cashback_amount), 0) as cashback_total')
            ->groupBy('status')
            ->get()
            ->keyBy('status');

        $pendingCashback = $cashbackStats->get('pending');
        $approvedCashback = $cashbackStats->get('approved');

        $pendingWithdraw = Withdrawal::where('user_id', $user->id)->where('status', 'pending')->count();

        return $this->ok([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'avatar' => $user->avatar,
            'referral_code' => $user->referral_code,
            'referral_prompt_pending' => is_null($user->referral_prompt_decided_at)
                && Setting::getVal('referral_enabled', '1') === '1',
            'status' => $user->status,
            'email_verified' => ! is_null($user->email_verified_at),
            'preferences' => $this->preferencesFor($user),
            'wallet' => [
                // Số dư khả dụng có thể rút
                'balance' => (int) MoneyHelper::round($user->balance),
                'pending_cashback' => (int) MoneyHelper::round($pendingCashback?->cashback_total),
                'approved_cashback' => (int) MoneyHelper::round($approvedCashback?->cashback_total),
                'total_cashback' => (int) MoneyHelper::round($user->total_cashback),
                'total_referral_earned' => (int) MoneyHelper::round($user->total_referral_earned),
                'total_withdrawn' => (int) MoneyHelper::round($user->total_withdrawn),
                'currency' => 'VND',
            ],
            'stats' => [
                'orders_total' => (int) $cashbackStats->sum('total'),
                'orders_pending' => (int) ($cashbackStats->get('pending')?->total ?? 0),
                'orders_approved' => (int) ($cashbackStats->get('approved')?->total ?? 0),
                'orders_rejected' => (int) ($cashbackStats->get('rejected')?->total ?? 0),
                'referrals_count' => (int) $user->referredUsers()->count(),
                'withdrawals_pending' => (int) $pendingWithdraw,
            ],
            'created_at' => optional($user->created_at)->toIso8601String(),
        ]);
    }

    /**
     * POST /api/v1/openapi/account/referral-code
     * Apply one referral code or permanently skip the post-registration prompt.
     */
    public function decideReferralPrompt(Request $request): JsonResponse
    {
        if ($request->exists('referral_code') && is_string($request->input('referral_code'))) {
            $request->merge(['referral_code' => trim($request->input('referral_code'))]);
        }

        try {
            $validated = $request->validate([
                'referral_code' => 'required_without:skip|string|max:50|prohibits:skip',
                'skip' => 'sometimes|boolean|accepted|prohibits:referral_code',
            ]);
        } catch (ValidationException $e) {
            return $this->fail(
                __('Dữ liệu mã giới thiệu không hợp lệ.'),
                422,
                'VALIDATION_ERROR',
                $e->errors()
            );
        }

        $outcome = DB::transaction(function () use ($request, $validated): array {
            $user = User::query()
                ->whereKey($this->apiUser($request)->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if (! is_null($user->referral_prompt_decided_at)) {
                return ['state' => 'already_decided'];
            }

            if (($validated['skip'] ?? false) === true) {
                $user->referral_prompt_decided_at = now();
                $user->save();

                return ['state' => 'skipped'];
            }

            if (Setting::getVal('referral_enabled', '1') !== '1') {
                return ['state' => 'invalid'];
            }

            $referrer = User::query()
                ->where('referral_code', $validated['referral_code'])
                ->first();

            $sameUser = $referrer && $referrer->getKey() === $user->getKey();
            $sameIpBlocked = $referrer
                && Setting::getVal('block_same_ip_referral', '1') === '1'
                && ! empty($referrer->ip_address)
                && $referrer->ip_address === $request->ip();

            if (! $referrer || $sameUser || $sameIpBlocked) {
                return ['state' => 'invalid'];
            }

            $user->referred_by = $referrer->getKey();
            $user->referral_prompt_decided_at = now();
            $user->save();

            Referral::create([
                'referrer_id' => $referrer->getKey(),
                'referred_id' => $user->getKey(),
            ]);
            Notification::create([
                'user_id' => $referrer->getKey(),
                'title' => __('Bạn có thành viên mới đăng ký'),
                'content' => __('Thành viên :name đã đăng ký tài khoản qua liên kết giới thiệu của bạn.', ['name' => $user->name]),
            ]);

            return ['state' => 'applied'];
        });

        if ($outcome['state'] === 'already_decided') {
            return $this->fail(
                __('Không thể cập nhật mã giới thiệu.'),
                409,
                'REFERRAL_PROMPT_ALREADY_DECIDED'
            );
        }

        if ($outcome['state'] === 'invalid') {
            $message = __('Mã giới thiệu không hợp lệ hoặc không thể sử dụng.');

            return $this->fail($message, 422, 'REFERRAL_CODE_INVALID', [
                'referral_code' => [$message],
            ]);
        }

        return $this->ok(
            ['referral_prompt_pending' => false],
            $outcome['state'] === 'applied'
                ? __('Áp dụng mã giới thiệu thành công!')
                : __('Đã bỏ qua mã giới thiệu.')
        );
    }

    /**
     * POST /api/v1/openapi/account/preferences
     * Persist the member's display preferences without changing wallet accounting currency.
     */
    public function updatePreferences(Request $request): JsonResponse
    {
        if ($request->exists('locale') && is_string($request->input('locale'))) {
            $request->merge(['locale' => trim($request->input('locale'))]);
        }

        if ($request->exists('currency') && is_string($request->input('currency'))) {
            $request->merge(['currency' => strtoupper(trim($request->input('currency')))]);
        }

        try {
            $validated = $request->validate([
                'locale' => 'required_without:currency|string|max:10',
                'currency' => 'required_without:locale|string|max:10',
            ]);

            $language = null;
            if (array_key_exists('locale', $validated)) {
                $language = Language::query()
                    ->where('code', $validated['locale'])
                    ->where('is_active', true)
                    ->first();

                if (! $language) {
                    throw ValidationException::withMessages([
                        'locale' => [__('Ngôn ngữ đã chọn hiện không khả dụng.')],
                    ]);
                }
            }

            $currency = null;
            if (array_key_exists('currency', $validated)) {
                $currency = Currency::query()
                    ->where('code', $validated['currency'])
                    ->where('is_active', true)
                    ->first();

                if (! $currency) {
                    throw ValidationException::withMessages([
                        'currency' => [__('Tiền tệ đã chọn hiện không khả dụng.')],
                    ]);
                }
            }
        } catch (ValidationException $e) {
            return $this->fail(__('Dữ liệu tùy chọn không hợp lệ.'), 422, 'VALIDATION_ERROR', $e->errors());
        }

        $user = $this->apiUser($request);

        if ($language) {
            $user->locale = $language->code;
        }

        if ($currency) {
            $user->currency = $currency->code;
        }

        $user->save();

        return $this->ok(
            $this->preferencesFor($user->refresh()),
            __('Cập nhật tùy chọn hiển thị thành công!')
        );
    }

    /**
     * POST /api/v1/openapi/account/profile
     * Cập nhật thông tin cá nhân (họ tên, số điện thoại).
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $user = $this->apiUser($request);

        // Chuẩn hoá số điện thoại về dạng thống nhất trước khi kiểm tra tính duy nhất
        if ($request->filled('phone')) {
            $request->merge(['phone' => User::normalizePhone($request->phone)]);
        }

        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                // Số điện thoại có thể là định danh đăng nhập nên phải là duy nhất trên toàn hệ thống
                'phone' => 'nullable|string|max:15|unique:users,phone,'.$user->id,
            ]);
        } catch (ValidationException $e) {
            return $this->fail(__('Dữ liệu không hợp lệ.'), 422, 'VALIDATION_ERROR', $e->errors());
        }

        $model = User::find($user->id);
        $model->name = $validated['name'];
        // Số điện thoại để trống phải lưu là NULL (không phải chuỗi rỗng) vì cột này có ràng buộc UNIQUE
        $model->phone = ! empty($validated['phone']) ? $validated['phone'] : null;
        $model->save();

        ActivityLog::log(__('Cập nhật thông tin cá nhân (qua Open API)'), $user->id);

        return $this->ok([
            'id' => $model->id,
            'name' => $model->name,
            'phone' => $model->phone,
        ], __('Cập nhật thông tin cá nhân thành công!'));
    }

    /**
     * POST /api/v1/openapi/account/password
     * Đổi mật khẩu tài khoản; thu hồi token trên các thiết bị khác để bảo mật.
     */
    public function changePassword(Request $request): JsonResponse
    {
        $user = $this->apiUser($request);

        try {
            $validated = $request->validate([
                'current_password' => 'required|string',
                'password' => 'required|string|min:8|confirmed',
            ], [
                'password.confirmed' => __('Xác nhận mật khẩu mới không khớp.'),
            ]);
        } catch (ValidationException $e) {
            return $this->fail(__('Dữ liệu đổi mật khẩu không hợp lệ.'), 422, 'VALIDATION_ERROR', $e->errors());
        }

        if (! Hash::check($validated['current_password'], $user->password)) {
            return $this->fail(__('Mật khẩu hiện tại không chính xác.'), 422, 'WRONG_PASSWORD');
        }

        $model = User::find($user->id);
        $model->password = Hash::make($validated['password']);
        $model->save();

        // Thu hồi mọi token khác, chỉ giữ lại token của thiết bị đang thao tác
        $currentToken = $request->attributes->get('api_token');
        $keepId = $currentToken instanceof ApiToken ? $currentToken->id : 0;
        ApiToken::where('user_id', $user->id)->where('id', '!=', $keepId)->delete();

        ActivityLog::log(__('Đổi mật khẩu và thu hồi token thiết bị khác (qua Open API)'), $user->id);

        return $this->ok(null, __('Đổi mật khẩu thành công! Các thiết bị khác đã bị đăng xuất.'));
    }

    /**
     * POST /api/v1/openapi/account/delete
     * Thành viên tự xóa vĩnh viễn tài khoản (yêu cầu bắt buộc của App Store / Google Play).
     * Cho phép xóa dù còn số dư; audit sau xóa chỉ giữ mã đối soát, không giữ email hay số dư.
     */
    public function deleteAccount(Request $request, NativeOAuthTokenVerifier $oauthVerifier): JsonResponse
    {
        if (config('app.demo')) {
            return $this->fail(__('Chức năng này bị vô hiệu hóa trong chế độ Demo.'), 403, 'DEMO_DISABLED');
        }

        // Chỉ cho phép khi quản trị viên đã bật tùy chọn tự xóa tài khoản
        if (Setting::getVal('allow_self_delete_account', '0') !== '1') {
            return $this->fail(__('Tính năng tự xóa tài khoản hiện đang bị tắt.'), 403, 'FEATURE_DISABLED');
        }

        $user = $this->apiUser($request);

        $confirmationFailure = $this->validateDeletionConfirmation($request, $user, $oauthVerifier);
        if ($confirmationFailure !== null) {
            return $confirmationFailure;
        }

        $appleRefreshToken = $request->attributes->get('apple_refresh_token');
        if (is_string($appleRefreshToken) && $appleRefreshToken !== '') {
            try {
                $oauthVerifier->revokeAppleRefreshToken(
                    $appleRefreshToken,
                    $request->attributes->get('apple_oauth_audience')
                );
            } catch (NativeOAuthVerificationException $e) {
                Log::warning('Apple provider disconnect failed before account deletion.', [
                    'user_id' => $user->id,
                    'error_code' => $e->errorCode,
                ]);

                return $this->fail(
                    __('Không thể ngắt liên kết Sign in with Apple vào lúc này. Tài khoản chưa bị xóa; vui lòng thử lại sau.'),
                    503,
                    'APPLE_PROVIDER_DISCONNECT_FAILED'
                );
            } finally {
                $request->attributes->remove('apple_refresh_token');
                $request->attributes->remove('apple_oauth_audience');
            }
        }

        $deletionReference = (string) Str::uuid();

        try {
            DB::transaction(function () use ($user) {
                $model = User::where('id', $user->id)->lockForUpdate()->first();
                if (! $model) {
                    throw new \Exception('USER_NOT_FOUND');
                }
                $email = $model->email;
                // Dọn dẹp dữ liệu không tự cascade (api_tokens có cascade nên tự xóa theo user)
                DB::table('activity_logs')->where('user_id', $model->id)->delete();
                DB::table('password_reset_tokens')->where('email', $email)->delete();
                DB::table('sessions')->where('user_id', $model->id)->delete();
                $model->delete();
            });
        } catch (\Throwable $e) {
            Log::error('Account deletion transaction failed.', [
                'deletion_reference' => $deletionReference,
                'exception' => $e::class,
            ]);

            return $this->fail(__('Có lỗi xảy ra trong quá trình xóa tài khoản. Vui lòng thử lại sau.'), 500, 'DELETE_FAILED');
        }

        // The authenticated model no longer exists; subsequent middleware must log this request anonymously.
        $request->setUserResolver(static fn () => null);
        $request->attributes->remove('api_token');

        try {
            ActivityLog::create([
                'user_id' => null,
                'activity' => __('Tài khoản đã tự xóa qua Open API (mã đối soát: :reference).', [
                    'reference' => $deletionReference,
                ]),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        } catch (\Throwable $e) {
            // Account deletion is committed; an audit sink outage must not turn success into HTTP 500.
            Log::warning('Account deletion committed but audit logging failed.', [
                'deletion_reference' => $deletionReference,
                'exception' => $e::class,
            ]);
        }

        return $this->ok(null, __('Tài khoản của bạn đã được xóa vĩnh viễn.'));
    }

    /**
     * Require either the local password or a fresh credential for a linked provider.
     */
    private function validateDeletionConfirmation(
        Request $request,
        User $user,
        NativeOAuthTokenVerifier $oauthVerifier
    ): ?JsonResponse {
        // Apple grants are revoked only after a fresh Apple reauthentication. A
        // local password alone cannot prove that the caller can disconnect the
        // linked provider grant, so never allow it to bypass this requirement.
        if (! empty($user->apple_id)) {
            if (! $request->filled('apple_identity_token')) {
                return $this->fail(
                    __('Vui lòng đăng nhập lại bằng Sign in with Apple trước khi xóa tài khoản để ngắt liên kết Apple.'),
                    403,
                    'APPLE_REAUTH_REQUIRED_FOR_DELETION'
                );
            }

            return $this->validateAppleDeletionCredential($request, $user, $oauthVerifier);
        }

        $passwordHash = $user->getRawOriginal('password');
        $hasLocalPassword = is_string($passwordHash) && trim($passwordHash) !== '';

        if ($hasLocalPassword) {
            if (! $request->filled('password') && $request->filled('google_id_token')) {
                return $this->validateGoogleDeletionCredential($request, $user, $oauthVerifier);
            }

            if (! $request->filled('password') && $request->filled('apple_identity_token')) {
                return $this->validateAppleDeletionCredential($request, $user, $oauthVerifier);
            }

            if (! $request->filled('password') && (! empty($user->google_id) || ! empty($user->apple_id))) {
                return $this->fail(
                    __('Vui lòng nhập mật khẩu hiện tại hoặc đăng nhập lại bằng nhà cung cấp danh tính đã liên kết trước khi xóa tài khoản.'),
                    403,
                    'PASSWORD_OR_PROVIDER_REAUTHENTICATION_REQUIRED'
                );
            }

            try {
                $validated = $request->validate(['password' => 'required|string'], [
                    'password.required' => __('Vui lòng nhập mật khẩu để xác nhận xóa tài khoản.'),
                ]);
            } catch (ValidationException $e) {
                return $this->fail(__('Thiếu mật khẩu xác nhận.'), 422, 'VALIDATION_ERROR', $e->errors());
            }

            if (! Hash::check($validated['password'], $passwordHash)) {
                return $this->fail(__('Mật khẩu không chính xác.'), 422, 'WRONG_PASSWORD');
            }

            return null;
        }

        if ($request->filled('google_id_token')) {
            return $this->validateGoogleDeletionCredential($request, $user, $oauthVerifier);
        }

        if ($request->filled('apple_identity_token')) {
            return $this->validateAppleDeletionCredential($request, $user, $oauthVerifier);
        }

        return $this->fail(
            __('Vui lòng đăng nhập lại bằng nhà cung cấp danh tính đã liên kết trước khi xóa tài khoản.'),
            403,
            'PROVIDER_REAUTHENTICATION_REQUIRED'
        );
    }

    private function validateGoogleDeletionCredential(
        Request $request,
        User $user,
        NativeOAuthTokenVerifier $oauthVerifier
    ): ?JsonResponse {
        if (empty($user->google_id)) {
            return $this->fail(
                __('Tài khoản này chưa liên kết với Google.'),
                403,
                'OAUTH_IDENTITY_NOT_LINKED'
            );
        }

        try {
            $validated = $request->validate([
                'google_id_token' => 'required|string|max:10000',
            ]);
        } catch (ValidationException $e) {
            return $this->fail(__('Dữ liệu xác minh Google không hợp lệ.'), 422, 'VALIDATION_ERROR', $e->errors());
        }

        try {
            $identity = $oauthVerifier->verifyGoogle($validated['google_id_token']);
        } catch (NativeOAuthVerificationException $e) {
            $message = $e->httpStatus === 503
                ? __('Không thể xác minh Google vào lúc này. Vui lòng thử lại sau.')
                : __('Thông tin xác minh Google không hợp lệ hoặc đã hết hạn.');

            return $this->fail($message, $e->httpStatus, $e->errorCode);
        }

        if (! hash_equals((string) $user->google_id, $identity['sub'])) {
            return $this->fail(
                __('Tài khoản Google xác minh không khớp với tài khoản đang đăng nhập.'),
                403,
                'OAUTH_IDENTITY_MISMATCH'
            );
        }

        if (User::where('google_id', $identity['sub'])->where('id', '!=', $user->id)->exists()) {
            return $this->fail(
                __('Danh tính Google này đang liên kết không nhất quán. Vui lòng liên hệ hỗ trợ.'),
                409,
                'OAUTH_IDENTITY_AMBIGUOUS'
            );
        }

        return null;
    }

    private function validateAppleDeletionCredential(
        Request $request,
        User $user,
        NativeOAuthTokenVerifier $oauthVerifier
    ): ?JsonResponse {
        if (empty($user->apple_id)) {
            return $this->fail(
                __('Tài khoản này chưa liên kết với Apple.'),
                403,
                'OAUTH_IDENTITY_NOT_LINKED'
            );
        }

        try {
            $validated = $request->validate([
                'apple_identity_token' => 'required|string|max:10000',
                'apple_authorization_code' => 'required|string|max:2048',
                'apple_nonce' => 'required|string|min:16|max:512',
            ]);
        } catch (ValidationException $e) {
            return $this->fail(__('Dữ liệu xác minh Apple không hợp lệ.'), 422, 'VALIDATION_ERROR', $e->errors());
        }

        try {
            $identity = $oauthVerifier->verifyApple(
                $validated['apple_identity_token'],
                $validated['apple_nonce'],
                $validated['apple_authorization_code']
            );
        } catch (NativeOAuthVerificationException $e) {
            $message = $e->httpStatus === 503
                ? __('Không thể xác minh Apple vào lúc này. Vui lòng thử lại sau.')
                : __('Thông tin xác minh Apple không hợp lệ, đã hết hạn hoặc đã được sử dụng.');

            return $this->fail($message, $e->httpStatus, $e->errorCode);
        }

        if (! hash_equals((string) $user->apple_id, $identity['sub'])) {
            return $this->fail(
                __('Tài khoản Apple xác minh không khớp với tài khoản đang đăng nhập.'),
                403,
                'OAUTH_IDENTITY_MISMATCH'
            );
        }

        if (User::where('apple_id', $identity['sub'])->where('id', '!=', $user->id)->exists()) {
            return $this->fail(
                __('Danh tính Apple này đang liên kết không nhất quán. Vui lòng liên hệ hỗ trợ.'),
                409,
                'OAUTH_IDENTITY_AMBIGUOUS'
            );
        }

        $refreshToken = $identity['provider_refresh_token'] ?? null;
        if (! is_string($refreshToken) || trim($refreshToken) === '') {
            return $this->fail(
                __('Không thể chuẩn bị ngắt liên kết Sign in with Apple. Tài khoản chưa bị xóa; vui lòng thử lại.'),
                503,
                'APPLE_PROVIDER_DISCONNECT_FAILED'
            );
        }

        $request->attributes->set('apple_refresh_token', trim($refreshToken));
        $request->attributes->set('apple_oauth_audience', $identity['audience'] ?? null);

        return null;
    }

    /**
     * Return active stored preferences, falling back to the same dynamic defaults as the web app.
     *
     * @return array{locale: string, currency: string}
     */
    private function preferencesFor(User $user): array
    {
        $locale = null;
        if (is_string($user->locale) && $user->locale !== '') {
            $locale = Language::query()
                ->where('code', $user->locale)
                ->where('is_active', true)
                ->value('code');
        }

        $currency = null;
        if (is_string($user->currency) && $user->currency !== '') {
            $currency = Currency::query()
                ->where('code', $user->currency)
                ->where('is_active', true)
                ->value('code');
        }

        return [
            'locale' => $locale ?: Language::query()
                ->where('is_active', true)
                ->where('is_default', true)
                ->value('code') ?: config('app.locale', 'vi'),
            'currency' => $currency ?: Currency::query()
                ->where('is_active', true)
                ->where('is_default', true)
                ->value('code') ?: 'VND',
        ];
    }
}
