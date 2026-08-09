<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Setting;
use App\Models\User;
use App\Models\UserPaymentAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PaymentAccountController extends Controller
{
    // Số lượng tài khoản nhận tiền tối đa mỗi thành viên được phép lưu trong sổ
    const MAX_ACCOUNTS = 10;

    /**
     * Kiểm tra tính năng sổ tài khoản có đang được bật hay không.
     */
    private function ensureEnabled()
    {
        if (Setting::getVal('withdraw_saved_accounts_enabled', '1') !== '1') {
            return response()->json([
                'success' => false,
                'message' => __('Tính năng lưu tài khoản nhận tiền hiện đang bị tắt.')
            ], 403);
        }
        return null;
    }

    /**
     * Lưu một tài khoản nhận tiền mới vào sổ của thành viên.
     * Chống trùng lặp và validate ngân hàng/ví nằm trong danh sách được phép giống luồng rút tiền.
     */
    public function store(Request $request)
    {
        if ($blocked = $this->ensureEnabled()) {
            return $blocked;
        }

        $user = Auth::user();

        // Lấy danh sách ngân hàng / ví điện tử hợp lệ để validate ở backend (đồng bộ với luồng rút tiền)
        $allowedBanks = array_map('trim', explode(',', Setting::getVal('allowed_banks', 'Vietcombank,Techcombank,MB Bank,ACB,BIDV,Vietinbank,Agribank,TPBank,VPBank')));
        $allowedWallets = array_map('trim', explode(',', Setting::getVal('allowed_wallets', 'Momo,ZaloPay,ShopeePay')));

        // Kiểm tra hình thức nhận tiền có đang được kích hoạt không
        $bankEnabled = Setting::getVal('withdraw_bank_enabled', '1') === '1';
        $walletEnabled = Setting::getVal('withdraw_wallet_enabled', '1') === '1';

        if ($request->input('payment_method') === 'bank' && !$bankEnabled) {
            return response()->json(['success' => false, 'message' => __('Phương thức rút tiền qua Ngân hàng hiện tại đang bị tắt.')], 422);
        }
        if ($request->input('payment_method') === 'wallet' && !$walletEnabled) {
            return response()->json(['success' => false, 'message' => __('Phương thức rút tiền qua Ví điện tử hiện tại đang bị tắt.')], 422);
        }

        $request->validate([
            'payment_method' => 'required|in:bank,wallet',
            'account_number' => 'required|string|max:50',
            'account_name' => 'required|string|max:100',
            // Ràng buộc bảo mật: ngăn người dùng gửi ngân hàng/ví ngoài danh sách được hỗ trợ
            'bank_name' => [
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
            'account_number.required' => __('Vui lòng nhập số tài khoản hoặc số điện thoại ví.'),
            'account_name.required' => __('Vui lòng nhập tên chủ tài khoản.'),
            'bank_name.required' => __('Vui lòng chọn ngân hàng hoặc ví nhận tiền.'),
        ]);

        $paymentMethod = $request->input('payment_method');
        $bankName = trim($request->input('bank_name'));
        $accountNumber = trim($request->input('account_number'));
        $accountName = strtoupper(trim($request->input('account_name')));
        $makeDefault = $request->boolean('is_default');
        $destinationHash = UserPaymentAccount::destinationHash($paymentMethod, $bankName, $accountNumber);

        try {
            $account = DB::transaction(function () use ($user, $paymentMethod, $bankName, $accountNumber, $accountName, $makeDefault, $destinationHash) {
                // Khoá dòng User để serialize hoàn toàn các request lưu tài khoản song song của cùng một thành viên
                // (tránh việc 2 request "tài khoản đầu tiên" cùng chạy tạo ra 2 bản ghi trùng hoặc 2 cờ mặc định).
                User::where('id', $user->id)->lockForUpdate()->first();
                $existing = UserPaymentAccount::where('user_id', $user->id)->get();

                // Chống trùng lặp: cùng hình thức + ngân hàng/ví + số tài khoản
                // Payout destinations are globally owned, not just unique per user.
                $duplicate = UserPaymentAccount::where('destination_hash', $destinationHash)->first();
                if ($duplicate) {
                    throw new \Exception($duplicate->user_id === $user->id
                        ? 'DUPLICATE'
                        : 'ACCOUNT_ALREADY_CLAIMED');
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
                    'user_id' => $user->id,
                    'payment_method' => $paymentMethod,
                    'bank_name' => $bankName,
                    'account_number' => $accountNumber,
                    'account_name' => $accountName,
                    'destination_hash' => $destinationHash,
                    'is_default' => $isDefault,
                ]);
            });
        } catch (\Exception $e) {
            if ($e->getMessage() === 'DUPLICATE') {
                return response()->json(['success' => false, 'message' => __('Tài khoản này đã tồn tại trong sổ của bạn.')], 422);
            }
            if ($e->getMessage() === 'ACCOUNT_ALREADY_CLAIMED') {
                return response()->json(['success' => false, 'code' => 'ACCOUNT_ALREADY_CLAIMED', 'message' => __('Số tài khoản này đã được sử dụng bởi một tài khoản khác trong hệ thống.')], 422);
            }
            if ($this->isDestinationUniqueConstraintViolation($e)) {
                $duplicate = UserPaymentAccount::where('destination_hash', $destinationHash)->first();

                return response()->json([
                    'success' => false,
                    'code' => $duplicate?->user_id === $user->id ? 'DUPLICATE_ACCOUNT' : 'ACCOUNT_ALREADY_CLAIMED',
                    'message' => $duplicate?->user_id === $user->id
                        ? __('Tài khoản này đã tồn tại trong sổ của bạn.')
                        : __('Số tài khoản này đã được sử dụng bởi một tài khoản khác trong hệ thống.'),
                ], 422);
            }
            if ($e->getMessage() === 'MAX_REACHED') {
                return response()->json(['success' => false, 'message' => __('Bạn chỉ có thể lưu tối đa :max tài khoản nhận tiền.', ['max' => self::MAX_ACCOUNTS])], 422);
            }
            \Log::error('Lỗi lưu tài khoản nhận tiền: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => __('Có lỗi xảy ra, vui lòng thử lại!')], 500);
        }

        ActivityLog::log('Thêm tài khoản nhận tiền vào sổ: ' . $bankName . ' - ' . $accountNumber, $user->id);

        return response()->json([
            'success' => true,
            'message' => __('Đã lưu tài khoản nhận tiền vào sổ.'),
            'account' => $account,
        ]);
    }

    private function isDestinationUniqueConstraintViolation(\Throwable $exception): bool
    {
        $message = strtolower($exception->getMessage());

        return (str_contains($message, 'destination_hash') || str_contains($message, 'user_payment_accounts_destination_hash_unique'))
            && (str_contains($message, 'unique') || str_contains($message, 'constraint'));
    }

    /**
     * Đặt một tài khoản làm mặc định để tự điền sẵn khi mở trang rút tiền.
     */
    public function setDefault(Request $request, $id)
    {
        if ($blocked = $this->ensureEnabled()) {
            return $blocked;
        }

        $user = Auth::user();

        $account = UserPaymentAccount::where('user_id', $user->id)->find($id);
        if (!$account) {
            return response()->json(['success' => false, 'message' => __('Không tìm thấy tài khoản.')], 404);
        }

        DB::transaction(function () use ($user, $account) {
            UserPaymentAccount::where('user_id', $user->id)->update(['is_default' => false]);
            $account->is_default = true;
            $account->save();
        });

        return response()->json([
            'success' => true,
            'message' => __('Đã đặt làm tài khoản mặc định.'),
        ]);
    }

    /**
     * Xoá một tài khoản khỏi sổ. Nếu là mặc định thì gán mặc định cho tài khoản mới nhất còn lại.
     */
    public function destroy(Request $request, $id)
    {
        if ($blocked = $this->ensureEnabled()) {
            return $blocked;
        }

        $user = Auth::user();

        $account = UserPaymentAccount::where('user_id', $user->id)->find($id);
        if (!$account) {
            return response()->json(['success' => false, 'message' => __('Không tìm thấy tài khoản.')], 404);
        }

        $wasDefault = $account->is_default;
        $label = $account->bank_name . ' - ' . $account->account_number;

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

        ActivityLog::log('Xoá tài khoản nhận tiền khỏi sổ: ' . $label, $user->id);

        return response()->json([
            'success' => true,
            'message' => __('Đã xoá tài khoản khỏi sổ.'),
        ]);
    }
}
