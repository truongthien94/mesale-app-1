<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\ActivityLog;
use App\Models\Setting;
use App\Models\User;
use App\Models\UserPaymentAccount;
use App\Services\FinancialIdempotencyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * API Sổ tài khoản nhận tiền: lưu, liệt kê, đặt mặc định và xoá tài khoản
 * ngân hàng/ví điện tử dùng để rút tiền.
 *
 * Mọi thao tác ghi đều chạy trong DB::transaction kèm lockForUpdate
 * để chống race condition khi có nhiều request đồng thời.
 */
class PaymentAccountController extends ApiController
{
    // Số lượng tài khoản nhận tiền tối đa mỗi thành viên được phép lưu
    const MAX_ACCOUNTS = 10;

    /**
     * GET /api/v1/openapi/payment-accounts
     * Trả về danh sách tài khoản nhận tiền đã lưu của thành viên (sắp xếp mặc định lên trước).
     */
    public function index(Request $request): JsonResponse
    {
        $user = $this->apiUser($request);

        // Lấy tất cả tài khoản, ưu tiên tài khoản mặc định rồi theo thời gian tạo mới nhất
        $accounts = UserPaymentAccount::where('user_id', $user->id)
            ->orderByDesc('is_default')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (UserPaymentAccount $acc) => [
                'id' => $acc->id,
                'payment_method' => $acc->payment_method,
                'bank_name' => $acc->bank_name,
                'account_number' => $acc->account_number,
                'account_name' => $acc->account_name,
                'is_default' => $acc->is_default,
                'created_at' => $acc->created_at?->toIso8601String(),
            ]);

        return $this->ok([
            'items' => $accounts,
            'total' => $accounts->count(),
        ]);
    }

    /**
     * POST /api/v1/openapi/payment-accounts
     * Lưu một tài khoản nhận tiền mới vào sổ. Chống trùng lặp, giới hạn tối đa,
     * và validate ngân hàng/ví nằm trong danh sách hợp lệ từ Settings.
     */
    public function store(Request $request, FinancialIdempotencyService $idempotency): JsonResponse
    {
        $user = $this->apiUser($request);

        $paymentMethodInput = $request->input('payment_method');
        $bankNameInput = $request->input('bank_name');
        $accountNumberInput = $request->input('account_number');
        $accountNameInput = $request->input('account_name');
        $paymentMethod = is_string($paymentMethodInput) ? trim($paymentMethodInput) : '';
        $bankName = is_string($bankNameInput) ? trim($bankNameInput) : '';
        $accountNumber = is_string($accountNumberInput) ? trim($accountNumberInput) : '';
        $accountName = is_string($accountNameInput) ? strtoupper(trim($accountNameInput)) : '';
        $makeDefault = $request->boolean('is_default');
        $accountInput = [
            'payment_method' => $paymentMethod,
            'bank_name' => $bankName,
            'account_number' => $accountNumber,
            'account_name' => $accountName,
            'is_default' => $makeDefault,
        ];

        $existingResult = $idempotency->replayIfPresent(
            (int) $user->id,
            'payment_account.create',
            (string) $request->attributes->get('idempotency_key'),
            $accountInput
        );
        if ($existingResult !== null) {
            return $this->storeResponse($existingResult);
        }

        // Lấy danh sách ngân hàng / ví điện tử hợp lệ (đồng bộ với luồng rút tiền web)
        $allowedBanks = array_map('trim', explode(',', Setting::getVal('allowed_banks', 'Vietcombank,Techcombank,MB Bank,ACB,BIDV,Vietinbank,Agribank,TPBank,VPBank')));
        $allowedWallets = array_map('trim', explode(',', Setting::getVal('allowed_wallets', 'Momo,ZaloPay,ShopeePay')));

        // Validate đầu vào
        try {
            $request->validate([
                'payment_method' => 'required|in:bank,wallet',
                'account_number' => 'required|string|max:50',
                'account_name' => 'required|string|max:100',
                // Ràng buộc bảo mật: ngăn gửi ngân hàng/ví ngoài danh sách được hỗ trợ
                'bank_name' => [
                    'required',
                    'string',
                    'max:100',
                    function ($attribute, $value, $fail) use ($allowedBanks, $allowedWallets, $request) {
                        if ($request->payment_method === 'bank' && ! in_array($value, $allowedBanks)) {
                            $fail(__('Ngân hàng đã chọn không nằm trong danh sách hỗ trợ của hệ thống.'));
                        }
                        if ($request->payment_method === 'wallet' && ! in_array($value, $allowedWallets)) {
                            $fail(__('Ví điện tử đã chọn không nằm trong danh sách hỗ trợ của hệ thống.'));
                        }
                    },
                ],
                'is_default' => 'nullable|boolean',
            ], [
                'payment_method.required' => __('Vui lòng chọn hình thức nhận tiền.'),
                'payment_method.in' => __('Hình thức nhận tiền không hợp lệ.'),
                'account_number.required' => __('Vui lòng nhập số tài khoản hoặc số điện thoại ví.'),
                'account_name.required' => __('Vui lòng nhập tên chủ tài khoản.'),
                'bank_name.required' => __('Vui lòng chọn ngân hàng hoặc ví nhận tiền.'),
            ]);
        } catch (ValidationException $e) {
            return $this->fail($e->getMessage(), 422, 'VALIDATION_ERROR', $e->errors());
        }

        if (Setting::getVal('withdraw_saved_accounts_enabled', '1') !== '1') {
            return $this->fail(__('Tính năng lưu tài khoản nhận tiền hiện đang bị tắt.'), 403, 'FEATURE_DISABLED');
        }

        $bankEnabled = Setting::getVal('withdraw_bank_enabled', '1') === '1';
        $walletEnabled = Setting::getVal('withdraw_wallet_enabled', '1') === '1';
        if ($paymentMethod === 'bank' && ! $bankEnabled) {
            return $this->fail(__('Phương thức rút tiền qua Ngân hàng hiện tại đang bị tắt.'), 422);
        }
        if ($paymentMethod === 'wallet' && ! $walletEnabled) {
            return $this->fail(__('Phương thức rút tiền qua Ví điện tử hiện tại đang bị tắt.'), 422);
        }

        try {
            $result = $idempotency->execute(
                (int) $user->id,
                'payment_account.create',
                (string) $request->attributes->get('idempotency_key'),
                $accountInput,
                function () use ($user, $paymentMethod, $bankName, $accountNumber, $accountName, $makeDefault): array {
                    $account = DB::transaction(function () use ($user, $paymentMethod, $bankName, $accountNumber, $accountName, $makeDefault) {
                        // Khoá dòng User để serialize hoàn toàn các request lưu tài khoản song song
                        // (tránh 2 request đồng thời tạo ra bản ghi trùng hoặc 2 cờ mặc định)
                        User::where('id', $user->id)->lockForUpdate()->first();
                        $existing = UserPaymentAccount::where('user_id', $user->id)->get();

                        // Chống trùng lặp: cùng hình thức + ngân hàng/ví + số tài khoản
                        $duplicate = $existing->first(function ($acc) use ($paymentMethod, $bankName, $accountNumber) {
                            return $acc->payment_method === $paymentMethod
                                && $acc->bank_name === $bankName
                                && $acc->account_number === $accountNumber;
                        });
                        if ($duplicate) {
                            throw new \Exception('DUPLICATE');
                        }

                        // Giới hạn số lượng tài khoản tối đa mỗi user
                        if ($existing->count() >= self::MAX_ACCOUNTS) {
                            throw new \Exception('MAX_REACHED');
                        }

                        // Tài khoản đầu tiên luôn được đặt làm mặc định
                        $isDefault = $makeDefault || $existing->isEmpty();

                        if ($isDefault) {
                            UserPaymentAccount::where('user_id', $user->id)->update(['is_default' => false]);
                        }

                        $account = UserPaymentAccount::create([
                            'user_id' => $user->id,
                            'payment_method' => $paymentMethod,
                            'bank_name' => $bankName,
                            'account_number' => $accountNumber,
                            'account_name' => $accountName,
                            'is_default' => $isDefault,
                        ]);

                        ActivityLog::log(
                            'Thêm tài khoản nhận tiền vào sổ (API): '.$bankName.' - '.$this->maskedAccountNumber($accountNumber),
                            $user->id
                        );

                        return $account;
                    });

                    return [
                        'status' => 200,
                        'data' => [
                            'id' => $account->id,
                            'payment_method' => $account->payment_method,
                            'bank_name' => $account->bank_name,
                            'account_number' => $account->account_number,
                            'account_name' => $account->account_name,
                            'is_default' => $account->is_default,
                            'created_at' => $account->created_at?->toIso8601String(),
                        ],
                    ];
                }
            );
        } catch (\Exception $e) {
            if ($e->getMessage() === 'DUPLICATE') {
                return $this->fail(__('Tài khoản này đã tồn tại trong sổ của bạn.'), 422, 'DUPLICATE_ACCOUNT');
            }
            if ($e->getMessage() === 'MAX_REACHED') {
                return $this->fail(__('Bạn chỉ có thể lưu tối đa :max tài khoản nhận tiền.', ['max' => self::MAX_ACCOUNTS]), 422, 'MAX_REACHED');
            }
            \Log::error('API - Lỗi lưu tài khoản nhận tiền: '.$e->getMessage());

            return $this->fail(__('Có lỗi xảy ra, vui lòng thử lại!'), 500);
        }

        return $this->storeResponse($result);
    }

    /**
     * POST /api/v1/openapi/payment-accounts/{id}/default
     * Đặt một tài khoản làm mặc định để tự điền sẵn khi rút tiền.
     */
    public function setDefault(Request $request, int $id): JsonResponse
    {
        // Kiểm tra tính năng sổ tài khoản có đang bật không
        if (Setting::getVal('withdraw_saved_accounts_enabled', '1') !== '1') {
            return $this->fail(__('Tính năng lưu tài khoản nhận tiền hiện đang bị tắt.'), 403, 'FEATURE_DISABLED');
        }

        $user = $this->apiUser($request);

        $account = UserPaymentAccount::where('user_id', $user->id)->find($id);
        if (! $account) {
            return $this->fail(__('Không tìm thấy tài khoản.'), 404, 'NOT_FOUND');
        }

        DB::transaction(function () use ($user, $account) {
            UserPaymentAccount::where('user_id', $user->id)->update(['is_default' => false]);
            $account->is_default = true;
            $account->save();
        });

        return $this->ok(null, __('Đã đặt làm tài khoản mặc định.'));
    }

    /**
     * DELETE /api/v1/openapi/payment-accounts/{id}
     * Xoá một tài khoản khỏi sổ. Nếu là mặc định thì gán mặc định cho tài khoản mới nhất còn lại.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        // Kiểm tra tính năng sổ tài khoản có đang bật không
        if (Setting::getVal('withdraw_saved_accounts_enabled', '1') !== '1') {
            return $this->fail(__('Tính năng lưu tài khoản nhận tiền hiện đang bị tắt.'), 403, 'FEATURE_DISABLED');
        }

        $user = $this->apiUser($request);

        $account = UserPaymentAccount::where('user_id', $user->id)->find($id);
        if (! $account) {
            return $this->fail(__('Không tìm thấy tài khoản.'), 404, 'NOT_FOUND');
        }

        $wasDefault = $account->is_default;
        $label = $account->bank_name.' - '.$this->maskedAccountNumber($account->account_number);

        DB::transaction(function () use ($user, $account, $wasDefault) {
            $account->delete();

            // Nếu vừa xoá tài khoản mặc định, chọn tài khoản mới nhất còn lại làm mặc định
            if ($wasDefault) {
                $next = UserPaymentAccount::where('user_id', $user->id)
                    ->orderByDesc('created_at')
                    ->first();
                if ($next) {
                    $next->is_default = true;
                    $next->save();
                }
            }
        });

        // Ghi nhận lịch sử hoạt động
        ActivityLog::log('Xoá tài khoản nhận tiền khỏi sổ (API): '.$label, $user->id);

        return $this->ok(null, __('Đã xoá tài khoản khỏi sổ.'));
    }

    private function storeResponse(array $result): JsonResponse
    {
        if ($result['outcome'] === FinancialIdempotencyService::OUTCOME_CONFLICT) {
            return $this->fail(__('Idempotency-Key đã được sử dụng với dữ liệu khác.'), 409, 'IDEMPOTENCY_KEY_REUSED');
        }
        if ($result['outcome'] === FinancialIdempotencyService::OUTCOME_IN_PROGRESS) {
            return $this->fail(__('Yêu cầu cùng Idempotency-Key đang được xử lý.'), 409, 'IDEMPOTENCY_REQUEST_IN_PROGRESS');
        }

        return $this->ok($result['data'], __('Đã lưu tài khoản nhận tiền vào sổ.'), $result['status']);
    }

    private function maskedAccountNumber(?string $accountNumber): string
    {
        $accountNumber = preg_replace('/\s+/', '', trim((string) $accountNumber)) ?? '';
        $length = strlen($accountNumber);
        if ($length === 0) {
            return '****';
        }
        if ($length <= 4) {
            return str_repeat('*', $length);
        }

        return str_repeat('*', max(4, $length - 4)).substr($accountNumber, -4);
    }
}
