<?php

namespace App\Http\Controllers\Api\V1;

use App\Helpers\CurrencyHelper;
use App\Helpers\MoneyHelper;
use App\Models\ActivityLog;
use App\Models\BalanceLog;
use App\Models\Notification;
use App\Models\Setting;
use App\Models\User;
use App\Models\Withdrawal;
use App\Services\FinancialIdempotencyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

/**
 * API Rút tiền: tạo yêu cầu rút tiền, gửi mã OTP xác nhận và xem lịch sử rút tiền.
 *
 * Toàn bộ thao tác thay đổi số dư được thực hiện trong DB::transaction kèm lockForUpdate
 * để chống race condition / double-spending, tương đương luồng web an toàn.
 */
class WithdrawalController extends ApiController
{
    // Số đơn rút pending tối đa mỗi thành viên được phép có cùng lúc
    const MAX_PENDING_WITHDRAWALS = 3;

    // Số lần nhập sai OTP tối đa trước khi hủy mã (chống brute-force)
    const MAX_OTP_ATTEMPTS = 5;

    /**
     * GET /api/v1/openapi/withdrawals
     * Lịch sử yêu cầu rút tiền của thành viên (có phân trang, lọc trạng thái).
     */
    public function index(Request $request): JsonResponse
    {
        $user = $this->apiUser($request);

        $query = Withdrawal::where('user_id', $user->id);

        $status = $request->query('status');
        if ($status && in_array($status, ['pending', 'approved', 'rejected'], true)) {
            $query->where('status', $status);
        }

        $perPage = (int) $request->query('per_page', 15);
        $perPage = max(1, min($perPage, 50));

        $withdrawals = $query->orderByDesc('created_at')->paginate($perPage);

        $items = $withdrawals->getCollection()->map(function (Withdrawal $w) {
            return [
                'id' => $w->id,
                'code' => $w->code,
                'amount' => (int) MoneyHelper::round($w->amount),
                'fee' => (int) MoneyHelper::round($w->fee),
                'real_amount' => (int) MoneyHelper::round($w->real_amount),
                'payment_method' => $w->payment_method,
                'bank_name' => $w->bank_name,
                'account_number' => $w->account_number,
                'account_name' => $w->account_name,
                'status' => $w->status,
                'notes' => $w->notes,
                'processed_at' => optional($w->processed_at)->toIso8601String(),
                'created_at' => optional($w->created_at)->toIso8601String(),
            ];
        });

        return $this->ok([
            'items' => $items,
            'pagination' => [
                'current_page' => $withdrawals->currentPage(),
                'per_page' => $withdrawals->perPage(),
                'total' => $withdrawals->total(),
                'last_page' => $withdrawals->lastPage(),
            ],
        ]);
    }

    /**
     * POST /api/v1/openapi/withdrawals/otp
     * Gửi mã OTP xác nhận rút tiền về email (chỉ khi hệ thống bật yêu cầu OTP).
     */
    public function sendOtp(Request $request): JsonResponse
    {
        $user = $this->apiUser($request);

        if (Setting::getVal('withdraw_otp_required', '0') !== '1') {
            return $this->fail(__('Hệ thống rút tiền hiện tại không yêu cầu xác minh mã OTP.'), 400, 'OTP_NOT_REQUIRED');
        }

        // Chống spam gửi OTP: tối đa 3 lần/phút
        $throttleKey = 'api-withdraw-otp:'.$user->id;
        if (RateLimiter::tooManyAttempts($throttleKey, 3)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            return $this->fail(__('Bạn đã yêu cầu gửi mã quá nhanh. Vui lòng thử lại sau :seconds giây.', ['seconds' => $seconds]), 429, 'TOO_MANY_REQUESTS');
        }
        RateLimiter::hit($throttleKey, 60);

        $otp = (string) random_int(100000, 999999);
        $model = User::find($user->id);
        $model->otp_code = Hash::make($otp);
        $model->otp_expires_at = now()->addMinutes(10);
        $model->save();

        RateLimiter::clear('api-otp-verify:'.$user->id);

        try {
            Setting::sendEmail($user->email, 'otp', [
                'name' => $user->name,
                'email' => $user->email,
                'otp' => $otp,
            ]);
            ActivityLog::log(__('Yêu cầu mã OTP rút tiền (qua Open API)'), $user->id);

            return $this->ok(null, __('Mã OTP rút tiền đã được gửi đến email :email.', ['email' => $user->email]));
        } catch (\Exception $e) {
            \Log::error('Lỗi gửi OTP rút tiền qua API.', ['exception' => $e::class]);

            return $this->fail(__('Có lỗi xảy ra khi gửi email OTP. Vui lòng thử lại sau.'), 500, 'OTP_SEND_FAILED');
        }
    }

    /**
     * POST /api/v1/openapi/withdrawals
     * Tạo một yêu cầu rút tiền mới.
     */
    public function store(Request $request, FinancialIdempotencyService $idempotency): JsonResponse
    {
        $user = $this->apiUser($request);

        if (Setting::getVal('withdrawal_enabled', '1') !== '1') {
            return $this->fail(__('Hệ thống rút tiền đang tạm bảo trì.'), 503, 'WITHDRAW_DISABLED');
        }

        $minWithdraw = MoneyHelper::round(Setting::getVal('min_withdraw', 50000));
        $allowedBanks = array_map('trim', explode(',', Setting::getVal('allowed_banks', 'Vietcombank,Techcombank,MB Bank,ACB,BIDV,Vietinbank,Agribank,TPBank,VPBank')));
        $allowedWallets = array_map('trim', explode(',', Setting::getVal('allowed_wallets', 'Momo,ZaloPay,ShopeePay')));

        try {
            $validated = $request->validate([
                'amount' => "required|numeric|min:{$minWithdraw}",
                'payment_method' => 'required|in:bank,wallet,momo',
                'account_number' => 'required|string|max:50',
                'account_name' => 'required|string|max:100',
                'bank_name' => [
                    'required_if:payment_method,bank', 'nullable', 'string', 'max:100',
                    function ($attr, $value, $fail) use ($allowedBanks, $request) {
                        if ($request->payment_method === 'bank' && ! in_array($value, $allowedBanks)) {
                            $fail(__('Ngân hàng đã chọn không nằm trong danh sách hỗ trợ của hệ thống.'));
                        }
                    },
                ],
                'wallet_name' => [
                    'required_if:payment_method,wallet', 'nullable', 'string', 'max:100',
                    function ($attr, $value, $fail) use ($allowedWallets, $request) {
                        if ($request->payment_method === 'wallet' && ! in_array($value, $allowedWallets)) {
                            $fail(__('Ví điện tử đã chọn không nằm trong danh sách hỗ trợ của hệ thống.'));
                        }
                    },
                ],
            ], [
                'amount.min' => __('Số tiền rút tối thiểu là :min.', ['min' => CurrencyHelper::format($minWithdraw)]),
            ]);
        } catch (ValidationException $e) {
            return $this->fail(__('Dữ liệu rút tiền không hợp lệ.'), 422, 'VALIDATION_ERROR', $e->errors());
        }

        if ($validated['payment_method'] === 'bank' && Setting::getVal('withdraw_bank_enabled', '1') !== '1') {
            return $this->fail(__('Phương thức rút tiền qua Ngân hàng hiện tại đang bị tắt.'), 422, 'METHOD_DISABLED');
        }
        if (in_array($validated['payment_method'], ['wallet', 'momo'], true) && Setting::getVal('withdraw_wallet_enabled', '1') !== '1') {
            return $this->fail(__('Phương thức rút tiền qua Ví điện tử hiện tại đang bị tắt.'), 422, 'METHOD_DISABLED');
        }

        $amount = MoneyHelper::truncate($validated['amount']);
        $feeType = Setting::getVal('withdrawal_fee_type', 'percentage');
        $feeValue = (float) Setting::getVal('withdrawal_fee_value', 0);
        $fee = $feeType === 'percentage'
            ? MoneyHelper::round(($amount * $feeValue) / 100)
            : MoneyHelper::round($feeValue);
        $realAmount = $amount - $fee;

        if ($realAmount <= 0) {
            return $this->fail(__('Số tiền rút phải lớn hơn phí rút tiền của hệ thống (:fee đ).', ['fee' => number_format($fee)]), 422, 'AMOUNT_TOO_LOW');
        }

        $withdrawalInput = [
            'amount' => (int) $amount,
            'payment_method' => $validated['payment_method'],
            'account_number' => trim($validated['account_number']),
            'account_name' => strtoupper(trim($validated['account_name'])),
            'bank_name' => isset($validated['bank_name']) && trim($validated['bank_name']) !== ''
                ? trim($validated['bank_name'])
                : null,
            'wallet_name' => isset($validated['wallet_name']) && trim($validated['wallet_name']) !== ''
                ? trim($validated['wallet_name'])
                : null,
        ];

        try {
            $result = $idempotency->execute(
                (int) $user->id,
                'withdrawal.create',
                (string) $request->attributes->get('idempotency_key'),
                $withdrawalInput,
                function () use ($user, $request, $withdrawalInput, $amount, $fee, $realAmount): array {
                    $throttleKey = 'api-withdraw-store:'.$user->id;
                    if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
                        throw new \RuntimeException('TOO_MANY_REQUESTS');
                    }
                    RateLimiter::hit($throttleKey, 300);

                    $otpRequired = Setting::getVal('withdraw_otp_required', '0') === '1';
                    if ($otpRequired && (! is_string($request->input('otp_code')) || strlen($request->input('otp_code')) !== 6)) {
                        throw new \RuntimeException('OTP_REQUIRED');
                    }

                    $userModel = User::where('id', $user->id)->lockForUpdate()->firstOrFail();

                    if ($otpRequired) {
                        $otpAttemptKey = 'api-otp-verify:'.$userModel->id;
                        if (RateLimiter::tooManyAttempts($otpAttemptKey, self::MAX_OTP_ATTEMPTS)) {
                            RateLimiter::clear($otpAttemptKey);
                            throw new \RuntimeException('OTP_LOCKED');
                        }

                        $otpValid = $userModel->otp_code
                            && Hash::check($request->input('otp_code'), $userModel->otp_code)
                            && $userModel->otp_expires_at
                            && ! $userModel->otp_expires_at->isPast();

                        if (! $otpValid) {
                            RateLimiter::hit($otpAttemptKey, 600);
                            throw new \RuntimeException('OTP_INVALID');
                        }
                    }

                    if ($userModel->balance < $amount) {
                        throw new \RuntimeException('INSUFFICIENT_BALANCE');
                    }

                    $pendingCount = Withdrawal::where('user_id', $userModel->id)->where('status', 'pending')->count();
                    if ($pendingCount >= self::MAX_PENDING_WITHDRAWALS) {
                        throw new \RuntimeException('MAX_PENDING_EXCEEDED');
                    }

                    $uniqueAccount = Setting::getVal('withdraw_unique_account', '0') === '1';
                    $cleanAccountNumber = preg_replace('/\s+/', '', $withdrawalInput['account_number']);
                    if ($uniqueAccount && $cleanAccountNumber) {
                        $duplicateNumber = Withdrawal::where('user_id', '!=', $userModel->id)
                            ->where('status', '!=', 'rejected')
                            ->whereRaw("REPLACE(account_number, ' ', '') = ?", [$cleanAccountNumber])
                            ->exists();
                        if ($duplicateNumber) {
                            throw new \RuntimeException('DUPLICATE_ACCOUNT');
                        }
                    }

                    if ($otpRequired) {
                        $userModel->otp_code = null;
                        $userModel->otp_expires_at = null;
                        RateLimiter::clear('api-otp-verify:'.$userModel->id);
                    }

                    $oldBalance = $userModel->balance;
                    $userModel->balance -= $amount;
                    $userModel->save();

                    $bankLabel = $withdrawalInput['payment_method'] === 'bank'
                        ? $withdrawalInput['bank_name']
                        : ($withdrawalInput['payment_method'] === 'wallet' ? 'Ví '.$withdrawalInput['wallet_name'] : 'Ví MoMo');
                    BalanceLog::write($userModel, $oldBalance, -$amount, $userModel->balance, 'withdraw_request', 'Yêu cầu rút tiền về '.$bankLabel);

                    $withdrawal = Withdrawal::create([
                        'user_id' => $userModel->id,
                        'code' => Setting::generateWithdrawCode(),
                        'amount' => $amount,
                        'fee' => $fee,
                        'real_amount' => $realAmount,
                        'payment_method' => $withdrawalInput['payment_method'],
                        'account_number' => $withdrawalInput['account_number'],
                        'account_name' => $withdrawalInput['account_name'],
                        'bank_name' => $withdrawalInput['payment_method'] === 'bank'
                            ? $withdrawalInput['bank_name']
                            : ($withdrawalInput['payment_method'] === 'wallet' ? $withdrawalInput['wallet_name'] : 'Ví MoMo'),
                        'status' => 'pending',
                    ]);

                    ActivityLog::log('Tạo yêu cầu rút '.number_format($amount).'đ (qua Open API)', $userModel->id);
                    Notification::create([
                        'user_id' => $userModel->id,
                        'title' => __('Yêu cầu rút tiền đang chờ duyệt'),
                        'content' => __('Yêu cầu rút :amount đ của bạn đã được gửi lên hệ thống và đang chờ admin xử lý.', ['amount' => number_format($amount)]),
                    ]);

                    return [
                        'status' => 201,
                        'data' => [
                            'id' => $withdrawal->id,
                            'code' => $withdrawal->code,
                            'amount' => (int) MoneyHelper::round($withdrawal->amount),
                            'fee' => (int) MoneyHelper::round($withdrawal->fee),
                            'real_amount' => (int) MoneyHelper::round($withdrawal->real_amount),
                            'status' => $withdrawal->status,
                        ],
                    ];
                }
            );

            if ($result['outcome'] === FinancialIdempotencyService::OUTCOME_CONFLICT) {
                return $this->fail(__('Idempotency-Key đã được sử dụng với dữ liệu khác.'), 409, 'IDEMPOTENCY_KEY_REUSED');
            }
            if ($result['outcome'] === FinancialIdempotencyService::OUTCOME_IN_PROGRESS) {
                return $this->fail(__('Yêu cầu cùng Idempotency-Key đang được xử lý.'), 409, 'IDEMPOTENCY_REQUEST_IN_PROGRESS');
            }

            if ($result['outcome'] === FinancialIdempotencyService::OUTCOME_COMPLETED) {
                $withdrawal = Withdrawal::find($result['data']['id']);
                if ($withdrawal) {
                    try {
                        Setting::sendTelegramTemplate('telegram_template_withdrawal_created', [
                            'code' => $withdrawal->code,
                            'name' => $user->name,
                            'email' => $user->email,
                            'amount' => number_format($withdrawal->amount),
                            'payment_method' => $withdrawal->payment_method === 'bank' ? 'Chuyển khoản ngân hàng' : 'Ví điện tử',
                            'bank_name' => $withdrawal->bank_name,
                            'account_number' => $withdrawal->account_number,
                            'account_name' => $withdrawal->account_name,
                        ]);
                    } catch (\Throwable $e) {
                        \Log::warning('Withdrawal Telegram notification failed.', ['exception' => $e::class]);
                    }
                }
            }

            return $this->ok(
                $result['data'],
                __('Tạo yêu cầu rút tiền thành công! Vui lòng chờ admin xét duyệt.'),
                $result['status']
            );
        } catch (\RuntimeException $e) {
            if ($e->getMessage() === 'OTP_LOCKED') {
                User::where('id', $user->id)->update([
                    'otp_code' => null,
                    'otp_expires_at' => null,
                ]);
                RateLimiter::clear('api-otp-verify:'.$user->id);
            }

            $failures = [
                'TOO_MANY_REQUESTS' => [429, 'TOO_MANY_REQUESTS', __('Bạn đã gửi quá nhiều yêu cầu rút tiền. Vui lòng thử lại sau.')],
                'OTP_REQUIRED' => [422, 'OTP_REQUIRED', __('Thiếu mã OTP xác nhận.')],
                'OTP_LOCKED' => [429, 'OTP_LOCKED', __('Bạn đã nhập sai mã OTP quá nhiều lần. Vui lòng yêu cầu mã mới.')],
                'OTP_INVALID' => [422, 'OTP_INVALID', __('Mã OTP không chính xác hoặc đã hết hiệu lực.')],
                'INSUFFICIENT_BALANCE' => [422, 'WITHDRAW_FAILED', __('Số dư tài khoản của bạn không đủ để thực hiện yêu cầu này.')],
                'MAX_PENDING_EXCEEDED' => [429, 'MAX_PENDING', __('Bạn đã đạt giới hạn số lượng yêu cầu rút tiền đang chờ xử lý.')],
                'DUPLICATE_ACCOUNT' => [422, 'WITHDRAW_FAILED', __('Số tài khoản này đã được sử dụng bởi một tài khoản khác trong hệ thống.')],
            ];

            if (isset($failures[$e->getMessage()])) {
                [$status, $code, $message] = $failures[$e->getMessage()];

                return $this->fail($message, $status, $code);
            }

            \Log::error('API withdrawal transaction failed.', ['exception' => $e::class]);

            return $this->fail(__('Có lỗi hệ thống xảy ra khi xử lý giao dịch. Vui lòng thử lại sau.'), 500, 'WITHDRAW_ERROR');
        } catch (\Throwable $e) {
            \Log::error('API withdrawal transaction failed.', ['exception' => $e::class]);

            return $this->fail(__('Có lỗi hệ thống xảy ra khi xử lý giao dịch. Vui lòng thử lại sau.'), 500, 'WITHDRAW_ERROR');
        }
    }
}
