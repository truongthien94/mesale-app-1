<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\ActivityLog;
use App\Models\BalanceLog;
use App\Models\Setting;
use App\Models\User;
use App\Models\Withdrawal;
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
                'amount' => (float) $w->amount,
                'fee' => (float) $w->fee,
                'real_amount' => (float) $w->real_amount,
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
        $throttleKey = 'api-withdraw-otp:' . $user->id;
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

        RateLimiter::clear('api-otp-verify:' . $user->id);

        try {
            Setting::sendEmail($user->email, 'otp', [
                'name' => $user->name,
                'email' => $user->email,
                'otp' => $otp,
            ]);
            ActivityLog::log(__('Yêu cầu mã OTP rút tiền (qua Open API)'), $user->id);
            return $this->ok(null, __('Mã OTP rút tiền đã được gửi đến email :email.', ['email' => $user->email]));
        } catch (\Exception $e) {
            \Log::error('Lỗi gửi OTP rút tiền qua API: ' . $e->getMessage());
            return $this->fail(__('Có lỗi xảy ra khi gửi email OTP. Vui lòng thử lại sau.'), 500, 'OTP_SEND_FAILED');
        }
    }

    /**
     * POST /api/v1/openapi/withdrawals
     * Tạo một yêu cầu rút tiền mới.
     */
    public function store(Request $request): JsonResponse
    {
        $user = $this->apiUser($request);

        if (Setting::getVal('withdrawal_enabled', '1') !== '1') {
            return $this->fail(__('Hệ thống rút tiền đang tạm bảo trì.'), 503, 'WITHDRAW_DISABLED');
        }

        // Chống spam tạo đơn rút: tối đa 5 yêu cầu / 5 phút
        $throttleKey = 'api-withdraw-store:' . $user->id;
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            return $this->fail(__('Bạn đã gửi quá nhiều yêu cầu rút tiền. Vui lòng thử lại sau :minutes phút.', ['minutes' => ceil($seconds / 60)]), 429, 'TOO_MANY_REQUESTS');
        }
        RateLimiter::hit($throttleKey, 300);

        // Kiểm tra số đơn pending trước khi validate
        $pendingCount = Withdrawal::where('user_id', $user->id)->where('status', 'pending')->count();
        if ($pendingCount >= self::MAX_PENDING_WITHDRAWALS) {
            return $this->fail(__('Bạn đang có :count yêu cầu rút tiền chờ xử lý. Vui lòng chờ admin duyệt trước khi tạo mới.', ['count' => $pendingCount]), 429, 'MAX_PENDING');
        }

        // Chuẩn hoá ngưỡng rút tối thiểu về số nguyên đồng cho đồng nhất với cách xử lý của giao diện web
        $minWithdraw = \App\Helpers\MoneyHelper::round(Setting::getVal('min_withdraw', 50000));
        $currentBalance = (float) User::where('id', $user->id)->value('balance');

        $allowedBanks = array_map('trim', explode(',', Setting::getVal('allowed_banks', 'Vietcombank,Techcombank,MB Bank,ACB,BIDV,Vietinbank,Agribank,TPBank,VPBank')));
        $allowedWallets = array_map('trim', explode(',', Setting::getVal('allowed_wallets', 'Momo,ZaloPay,ShopeePay')));

        $bankEnabled = Setting::getVal('withdraw_bank_enabled', '1') === '1';
        $walletEnabled = Setting::getVal('withdraw_wallet_enabled', '1') === '1';

        try {
            $request->validate([
                'amount' => "required|numeric|min:{$minWithdraw}|max:{$currentBalance}",
                'payment_method' => 'required|in:bank,wallet,momo',
                'account_number' => 'required|string|max:50',
                'account_name' => 'required|string|max:100',
                'bank_name' => [
                    'required_if:payment_method,bank', 'nullable', 'string', 'max:100',
                    function ($attr, $value, $fail) use ($allowedBanks, $request) {
                        if ($request->payment_method === 'bank' && !in_array($value, $allowedBanks)) {
                            $fail(__('Ngân hàng đã chọn không nằm trong danh sách hỗ trợ của hệ thống.'));
                        }
                    },
                ],
                'wallet_name' => [
                    'required_if:payment_method,wallet', 'nullable', 'string', 'max:100',
                    function ($attr, $value, $fail) use ($allowedWallets, $request) {
                        if ($request->payment_method === 'wallet' && !in_array($value, $allowedWallets)) {
                            $fail(__('Ví điện tử đã chọn không nằm trong danh sách hỗ trợ của hệ thống.'));
                        }
                    },
                ],
            ], [
                'amount.min' => __('Số tiền rút tối thiểu là :min.', ['min' => \App\Helpers\CurrencyHelper::format($minWithdraw)]),
                'amount.max' => __('Số tiền rút không được vượt quá số dư ví hiện tại (:balance).', ['balance' => \App\Helpers\CurrencyHelper::format($currentBalance)]),
            ]);
        } catch (ValidationException $e) {
            return $this->fail(__('Dữ liệu rút tiền không hợp lệ.'), 422, 'VALIDATION_ERROR', $e->errors());
        }

        if ($request->input('payment_method') === 'bank' && !$bankEnabled) {
            return $this->fail(__('Phương thức rút tiền qua Ngân hàng hiện tại đang bị tắt.'), 422, 'METHOD_DISABLED');
        }
        if (in_array($request->input('payment_method'), ['wallet', 'momo']) && !$walletEnabled) {
            return $this->fail(__('Phương thức rút tiền qua Ví điện tử hiện tại đang bị tắt.'), 422, 'METHOD_DISABLED');
        }

        // Chuẩn hoá số tiền rút về số nguyên đồng, làm tròn xuống để không bao giờ vượt quá số dư đã kiểm tra ở bước validate
        $amount = \App\Helpers\MoneyHelper::truncate($request->amount);

        // Tính phí rút tiền theo cấu hình
        $feeType = Setting::getVal('withdrawal_fee_type', 'percentage');
        $feeValue = (float) Setting::getVal('withdrawal_fee_value', 0);
        // Chuẩn hoá phí về số nguyên đồng để đồng nhất với cách tính phí của giao diện web
        $fee = $feeType === 'percentage'
            ? \App\Helpers\MoneyHelper::round(($amount * $feeValue) / 100)
            : \App\Helpers\MoneyHelper::round($feeValue);
        $realAmount = $amount - $fee;

        if ($realAmount <= 0) {
            return $this->fail(__('Số tiền rút phải lớn hơn phí rút tiền của hệ thống (:fee đ).', ['fee' => number_format($fee)]), 422, 'AMOUNT_TOO_LOW');
        }

        // Xác minh OTP nếu hệ thống yêu cầu
        if (Setting::getVal('withdraw_otp_required', '0') === '1') {
            try {
                $request->validate(['otp_code' => 'required|string|size:6'], [
                    'otp_code.required' => __('Vui lòng nhập mã OTP để xác nhận giao dịch rút tiền.'),
                    'otp_code.size' => __('Mã OTP phải gồm đúng 6 chữ số.'),
                ]);
            } catch (ValidationException $e) {
                return $this->fail(__('Thiếu mã OTP xác nhận.'), 422, 'OTP_REQUIRED', $e->errors());
            }

            $otpAttemptKey = 'api-otp-verify:' . $user->id;
            if (RateLimiter::tooManyAttempts($otpAttemptKey, self::MAX_OTP_ATTEMPTS)) {
                User::where('id', $user->id)->update(['otp_code' => null, 'otp_expires_at' => null]);
                RateLimiter::clear($otpAttemptKey);
                return $this->fail(__('Bạn đã nhập sai mã OTP quá nhiều lần. Mã OTP đã bị hủy, vui lòng yêu cầu gửi lại mã mới.'), 429, 'OTP_LOCKED');
            }

            $currentUser = User::find($user->id);
            $otpValid = $currentUser->otp_code
                && Hash::check($request->otp_code, $currentUser->otp_code)
                && $currentUser->otp_expires_at
                && !$currentUser->otp_expires_at->isPast();

            if (!$otpValid) {
                RateLimiter::hit($otpAttemptKey, 600);
                $remaining = self::MAX_OTP_ATTEMPTS - RateLimiter::attempts($otpAttemptKey);
                return $this->fail(__('Mã OTP không chính xác hoặc đã hết hiệu lực. Bạn còn :count lần thử.', ['count' => $remaining]), 422, 'OTP_INVALID');
            }

            // Lưu ý: việc HỦY (tiêu thụ) mã OTP được dời vào bên trong transaction bên dưới — chỉ huỷ khi
            // mọi kiểm tra (số dư/đơn chờ/trùng STK) đã vượt qua, tránh huỷ oan mã khi giao dịch thất bại.
        }

        // Chuẩn hóa số tài khoản để kiểm tra trùng lặp bên trong transaction (đồng bộ với luồng web, tránh race condition TOCTOU)
        $uniqueAccount = Setting::getVal('withdraw_unique_account', '0') === '1';
        $cleanAccountNumber = $uniqueAccount ? preg_replace('/\s+/', '', $request->account_number) : null;
        $otpRequired = Setting::getVal('withdraw_otp_required', '0') === '1';

        try {
            $withdrawal = DB::transaction(function () use ($user, $amount, $fee, $realAmount, $request, $uniqueAccount, $cleanAccountNumber, $otpRequired) {
                // Khóa dòng User để kiểm tra số dư mới nhất, chống race condition
                $userModel = User::where('id', $user->id)->lockForUpdate()->first();

                if ($userModel->balance < $amount) {
                    throw new \Exception('INSUFFICIENT_BALANCE');
                }

                $pendingCount = Withdrawal::where('user_id', $userModel->id)->where('status', 'pending')->count();
                if ($pendingCount >= self::MAX_PENDING_WITHDRAWALS) {
                    throw new \Exception('MAX_PENDING_EXCEEDED');
                }

                // Chặn dùng chung một số tài khoản nhận tiền giữa nhiều tài khoản khác nhau khi admin bật tùy chọn này.
                // Kiểm tra bên trong transaction để hai request song song cùng STK được serialize thay vì bỏ qua nhau.
                if ($uniqueAccount && $cleanAccountNumber) {
                    $duplicateNumber = Withdrawal::where('user_id', '!=', $userModel->id)
                        ->where('status', '!=', 'rejected')
                        ->whereRaw("REPLACE(account_number, ' ', '') = ?", [$cleanAccountNumber])
                        ->exists();
                    if ($duplicateNumber) {
                        throw new \Exception('DUPLICATE_ACCOUNT');
                    }
                }

                // Tiêu thụ mã OTP ngay trong transaction sau khi mọi kiểm tra đã vượt qua (chống replay,
                // đồng thời không huỷ oan mã nếu giao dịch fail ở các bước kiểm tra phía trên).
                if ($otpRequired) {
                    $userModel->otp_code = null;
                    $userModel->otp_expires_at = null;
                    RateLimiter::clear('api-otp-verify:' . $userModel->id);
                }

                $oldBalance = $userModel->balance;
                $userModel->balance -= $amount;
                $userModel->save();

                $bankLabel = $request->payment_method === 'bank'
                    ? $request->bank_name
                    : ($request->payment_method === 'wallet' ? 'Ví ' . $request->wallet_name : 'Ví MoMo');

                BalanceLog::write($userModel, $oldBalance, -$amount, $userModel->balance, 'withdraw_request', 'Yêu cầu rút tiền về ' . $bankLabel);

                $withdrawalCode = Setting::generateWithdrawCode();

                $withdrawal = Withdrawal::create([
                    'user_id' => $user->id,
                    'code' => $withdrawalCode,
                    'amount' => $amount,
                    'fee' => $fee,
                    'real_amount' => $realAmount,
                    'payment_method' => $request->payment_method,
                    'account_number' => $request->account_number,
                    'account_name' => strtoupper($request->account_name),
                    'bank_name' => $request->payment_method === 'bank' ? $request->bank_name : ($request->payment_method === 'wallet' ? $request->wallet_name : 'Ví MoMo'),
                    'status' => 'pending',
                ]);

                ActivityLog::log('Tạo yêu cầu rút ' . number_format($amount) . 'đ (qua Open API)', $user->id);

                \App\Models\Notification::create([
                    'user_id' => $user->id,
                    'title' => __('Yêu cầu rút tiền đang chờ duyệt'),
                    'content' => __('Yêu cầu rút :amount đ của bạn đã được gửi lên hệ thống và đang chờ admin xử lý.', ['amount' => number_format($amount)]),
                ]);

                // Gửi thông báo Telegram cho admin (không chặn luồng nếu lỗi)
                try {
                    Setting::sendTelegramTemplate('telegram_template_withdrawal_created', [
                        'code' => $withdrawalCode,
                        'name' => $userModel->name,
                        'email' => $userModel->email,
                        'amount' => number_format($amount),
                        'payment_method' => $request->payment_method === 'bank' ? 'Chuyển khoản ngân hàng' : 'Ví điện tử',
                        'bank_name' => $withdrawal->bank_name,
                        'account_number' => $request->account_number,
                        'account_name' => strtoupper($request->account_name),
                    ]);
                } catch (\Exception $e) {
                    \Log::error('Lỗi gửi Telegram tạo rút tiền qua API: ' . $e->getMessage());
                }

                return $withdrawal;
            });
        } catch (\Exception $e) {
            \Log::error('API withdrawal transaction failed: ' . $e->getMessage());
            $map = [
                'INSUFFICIENT_BALANCE' => __('Số dư tài khoản của bạn không đủ để thực hiện yêu cầu này.'),
                'MAX_PENDING_EXCEEDED' => __('Bạn đã đạt giới hạn số lượng yêu cầu rút tiền đang chờ xử lý.'),
                'DUPLICATE_ACCOUNT' => __('Số tài khoản này đã được sử dụng bởi một tài khoản khác trong hệ thống.'),
            ];
            return $this->fail($map[$e->getMessage()] ?? __('Có lỗi hệ thống xảy ra khi xử lý giao dịch. Vui lòng thử lại sau.'), 422, 'WITHDRAW_FAILED');
        }

        return $this->ok([
            'id' => $withdrawal->id,
            'code' => $withdrawal->code,
            'amount' => (float) $withdrawal->amount,
            'fee' => (float) $withdrawal->fee,
            'real_amount' => (float) $withdrawal->real_amount,
            'status' => $withdrawal->status,
        ], __('Tạo yêu cầu rút tiền thành công! Vui lòng chờ admin xét duyệt.'), 201);
    }
}
