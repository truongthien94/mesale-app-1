<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\ActivityLog;
use App\Models\Setting;
use App\Models\User;
use App\Models\UserPaymentAccount;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
            ->map(fn(UserPaymentAccount $acc) => [
                'id'             => $acc->id,
                'payment_method' => $acc->payment_method,
                'bank_name'      => $acc->bank_name,
                'account_number' => $acc->account_number,
                'account_name'   => $acc->account_name,
                'is_default'     => $acc->is_default,
                'created_at'     => $acc->created_at?->toIso8601String(),
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
    public function store(Request $request): JsonResponse
    {
        // Kiểm tra tính năng sổ tài khoản có đang bật không
        if (Setting::getVal('withdraw_saved_accounts_enabled', '1') !== '1') {
            return $this->fail(__('Tính năng lưu tài khoản nhận tiền hiện đang bị tắt.'), 403, 'FEATURE_DISABLED');
        }

        $user = $this->apiUser($request);

        // Lấy danh sách ngân hàng / ví điện tử hợp lệ (đồng bộ với luồng rút tiền web)
        $allowedBanks = array_map('trim', explode(',', Setting::getVal('allowed_banks', 'Vietcombank,Techcombank,MB Bank,ACB,BIDV,Vietinbank,Agribank,TPBank,VPBank')));
        $allowedWallets = array_map('trim', explode(',', Setting::getVal('allowed_wallets', 'Momo,ZaloPay,ShopeePay')));

        // Kiểm tra hình thức nhận tiền có đang được kích hoạt không
        $bankEnabled = Setting::getVal('withdraw_bank_enabled', '1') === '1';
        $walletEnabled = Setting::getVal('withdraw_wallet_enabled', '1') === '1';

        $paymentMethod = $request->input('payment_method');

        if ($paymentMethod === 'bank' && !$bankEnabled) {
            return $this->fail(__('Phương thức rút tiền qua Ngân hàng hiện tại đang bị tắt.'), 422);
        }
        if ($paymentMethod === 'wallet' && !$walletEnabled) {
            return $this->fail(__('Phương thức rút tiền qua Ví điện tử hiện tại đang bị tắt.'), 422);
        }

        // Validate đầu vào
        try {
            $request->validate([
                'payment_method' => 'required|in:bank,wallet',
                'account_number' => 'required|string|max:50',
                'account_name'   => 'required|string|max:100',
                // Ràng buộc bảo mật: ngăn gửi ngân hàng/ví ngoài danh sách được hỗ trợ
                'bank_name'      => [
                    'required',
                    'string',
                    'max:100',
                    function ($attribute, $value, $fail) use ($allowedBanks, $allowedWallets, $request) {
                        if ($request->payment_method === 'bank' && !in_array($value, $allowedBanks)) {
                            $fail(__('Ngân hàng đã chọn không nằm trong danh sách hỗ trợ của hệ thống.'));
                        }
                        if ($request->payment_method === 'wallet' && !in_array($value, $allowedWallets)) {
                            $fail(__('Ví điện tử đã chọn không nằm trong danh sách hỗ trợ của hệ thống.'));
                        }
                    }
                ],
                'is_default' => 'nullable|boolean',
            ], [
                'payment_method.required' => __('Vui lòng chọn hình thức nhận tiền.'),
                'payment_method.in'       => __('Hình thức nhận tiền không hợp lệ.'),
                'account_number.required' => __('Vui lòng nhập số tài khoản hoặc số điện thoại ví.'),
                'account_name.required'   => __('Vui lòng nhập tên chủ tài khoản.'),
                'bank_name.required'      => __('Vui lòng chọn ngân hàng hoặc ví nhận tiền.'),
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->fail($e->getMessage(), 422, 'VALIDATION_ERROR', $e->errors());
        }

        $bankName      = trim($request->input('bank_name'));
        $accountNumber = trim($request->input('account_number'));
        $accountName   = strtoupper(trim($request->input('account_name')));
        $makeDefault   = $request->boolean('is_default');

        try {
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

                return UserPaymentAccount::create([
                    'user_id'        => $user->id,
                    'payment_method' => $paymentMethod,
                    'bank_name'      => $bankName,
                    'account_number' => $accountNumber,
                    'account_name'   => $accountName,
                    'is_default'     => $isDefault,
                ]);
            });
        } catch (\Exception $e) {
            if ($e->getMessage() === 'DUPLICATE') {
                return $this->fail(__('Tài khoản này đã tồn tại trong sổ của bạn.'), 422, 'DUPLICATE_ACCOUNT');
            }
            if ($e->getMessage() === 'MAX_REACHED') {
                return $this->fail(__('Bạn chỉ có thể lưu tối đa :max tài khoản nhận tiền.', ['max' => self::MAX_ACCOUNTS]), 422, 'MAX_REACHED');
            }
            \Log::error('API - Lỗi lưu tài khoản nhận tiền: ' . $e->getMessage());
            return $this->fail(__('Có lỗi xảy ra, vui lòng thử lại!'), 500);
        }

        // Ghi nhận lịch sử hoạt động
        ActivityLog::log('Thêm tài khoản nhận tiền vào sổ (API): ' . $bankName . ' - ' . $accountNumber, $user->id);

        return $this->ok([
            'id'             => $account->id,
            'payment_method' => $account->payment_method,
            'bank_name'      => $account->bank_name,
            'account_number' => $account->account_number,
            'account_name'   => $account->account_name,
            'is_default'     => $account->is_default,
            'created_at'     => $account->created_at?->toIso8601String(),
        ], __('Đã lưu tài khoản nhận tiền vào sổ.'));
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
        if (!$account) {
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
        if (!$account) {
            return $this->fail(__('Không tìm thấy tài khoản.'), 404, 'NOT_FOUND');
        }

        $wasDefault = $account->is_default;
        $label      = $account->bank_name . ' - ' . $account->account_number;

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
        ActivityLog::log('Xoá tài khoản nhận tiền khỏi sổ (API): ' . $label, $user->id);

        return $this->ok(null, __('Đã xoá tài khoản khỏi sổ.'));
    }
}
