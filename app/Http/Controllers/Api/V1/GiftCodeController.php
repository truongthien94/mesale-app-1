<?php

namespace App\Http\Controllers\Api\V1;

use App\Helpers\MoneyHelper;
use App\Models\ActivityLog;
use App\Models\BalanceLog;
use App\Models\GiftCode;
use App\Models\GiftCodeRedemption;
use App\Models\Notification;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * API Nhập Giftcode nhận thưởng cho App Mobile.
 */
class GiftCodeController extends ApiController
{
    /**
     * POST /api/v1/openapi/giftcode/redeem
     * Body: code
     */
    public function redeem(Request $request): JsonResponse
    {
        if (Setting::getVal('gift_code_enabled', '0') !== '1') {
            return $this->fail(__('Chức năng nhập Giftcode hiện đang tạm khóa.'), 403, 'GIFTCODE_DISABLED');
        }

        try {
            $request->validate([
                'code' => 'required|string|max:50',
            ], [
                'code.required' => __('Vui lòng nhập mã Giftcode.'),
            ]);
        } catch (ValidationException $e) {
            return $this->fail(__('Dữ liệu không hợp lệ.'), 422, 'VALIDATION_ERROR', $e->errors());
        }

        // Chuẩn hóa mã giống cách lưu ở admin (in hoa, bỏ khoảng trắng)
        $code = strtoupper(trim(str_replace(' ', '', $request->input('code'))));
        $user = $this->apiUser($request);

        try {
            $amount = DB::transaction(function () use ($code, $user, $request) {
                // Khóa bản ghi mã chống đổi vượt số lượt
                $giftCode = GiftCode::where('code', $code)->lockForUpdate()->first();

                if (! $giftCode) {
                    throw new \RuntimeException(__('Mã Giftcode không tồn tại. Vui lòng kiểm tra lại.'));
                }
                if (! $giftCode->status) {
                    throw new \RuntimeException(__('Mã Giftcode này hiện đã bị tạm dừng.'));
                }
                if (! $giftCode->hasStarted()) {
                    throw new \RuntimeException(__('Mã Giftcode này chưa tới thời gian sử dụng.'));
                }
                if ($giftCode->isExpired()) {
                    throw new \RuntimeException(__('Mã Giftcode này đã hết hạn sử dụng.'));
                }
                if ($giftCode->isSoldOut()) {
                    throw new \RuntimeException(__('Mã Giftcode này đã hết lượt sử dụng.'));
                }

                // Khóa dòng user chống cộng tiền sai do race condition
                $dbUser = User::where('id', $user->id)->lockForUpdate()->firstOrFail();
                if ($dbUser->status !== 'active') {
                    throw new \RuntimeException(__('Tài khoản của bạn hiện đang bị tạm khóa.'));
                }

                if ($giftCode->require_verified_email && empty($dbUser->email_verified_at)) {
                    throw new \RuntimeException(__('Bạn cần xác minh email trước khi sử dụng mã này.'));
                }
                if ($giftCode->min_total_cashback > 0 && $dbUser->total_cashback < $giftCode->min_total_cashback) {
                    throw new \RuntimeException(__('Bạn cần đạt tối thiểu :amount tiền hoàn tích lũy để dùng mã này.', [
                        'amount' => number_format($giftCode->min_total_cashback, 0, ',', '.').'đ',
                    ]));
                }
                if ($giftCode->min_account_age_days > 0 && $dbUser->created_at
                    && $dbUser->created_at->gt(now()->subDays($giftCode->min_account_age_days))) {
                    throw new \RuntimeException(__('Tài khoản của bạn cần hoạt động tối thiểu :days ngày để dùng mã này.', [
                        'days' => $giftCode->min_account_age_days,
                    ]));
                }
                if ($giftCode->new_user_within_days && $dbUser->created_at
                    && $dbUser->created_at->lt(now()->subDays($giftCode->new_user_within_days))) {
                    throw new \RuntimeException(__('Mã này chỉ dành cho tài khoản mới đăng ký trong vòng :days ngày.', [
                        'days' => $giftCode->new_user_within_days,
                    ]));
                }

                $userUsedCount = GiftCodeRedemption::where('gift_code_id', $giftCode->id)
                    ->where('user_id', $dbUser->id)
                    ->count();
                if ($userUsedCount >= $giftCode->per_user_limit) {
                    throw new \RuntimeException(__('Bạn đã sử dụng mã này đủ số lần cho phép.'));
                }

                $amount = $giftCode->resolveRewardAmount();

                $oldBalance = $dbUser->balance;
                $newBalance = $oldBalance + $amount;
                // balance được gán tường minh (không mass-assign) vì cột này đã bị loại khỏi $fillable vì lý do bảo mật.
                $dbUser->balance = $newBalance;
                $dbUser->save();
                $giftCode->increment('used_count');

                GiftCodeRedemption::create([
                    'gift_code_id' => $giftCode->id,
                    'user_id' => $dbUser->id,
                    'code' => $giftCode->code,
                    'amount' => $amount,
                    'ip_address' => $request->ip(),
                ]);

                BalanceLog::write(
                    $dbUser,
                    $oldBalance,
                    $amount,
                    $newBalance,
                    'giftcode_reward',
                    __('Nhận thưởng Giftcode :code', ['code' => '#'.$giftCode->code])
                );

                ActivityLog::log(__('Đổi Giftcode :code nhận thưởng :amount (qua Open API)', [
                    'code' => '#'.$giftCode->code,
                    'amount' => number_format($amount, 0, ',', '.').'đ',
                ]), $dbUser->id);

                Notification::create([
                    'user_id' => $dbUser->id,
                    'title' => __('Nhận thưởng Giftcode thành công'),
                    'content' => __('Bạn vừa nhận :amount từ mã Giftcode :code vào số dư ví khả dụng.', [
                        'amount' => number_format($amount, 0, ',', '.').'đ',
                        'code' => $giftCode->code,
                    ]),
                ]);

                return $amount;
            });

            return $this->ok([
                'amount' => (int) MoneyHelper::round($amount),
            ], __('Chúc mừng! Bạn đã nhận :amount từ Giftcode vào ví khả dụng.', [
                'amount' => number_format($amount, 0, ',', '.').'đ',
            ]));
        } catch (\RuntimeException $e) {
            return $this->fail($e->getMessage(), 400, 'REDEEM_FAILED');
        } catch (\Throwable $e) {
            \Log::error('Lỗi đổi Giftcode qua Open API.', ['exception' => $e::class]);

            return $this->fail(__('Có lỗi xảy ra khi đổi mã, vui lòng thử lại sau.'), 500, 'REDEEM_ERROR');
        }
    }
}
