<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\ActivityLog;
use App\Models\ApiToken;
use App\Models\CashbackHistory;
use App\Models\Setting;
use App\Models\User;
use App\Models\Withdrawal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
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
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $pendingWithdraw = Withdrawal::where('user_id', $user->id)->where('status', 'pending')->count();

        return $this->ok([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'avatar' => $user->avatar,
            'referral_code' => $user->referral_code,
            'status' => $user->status,
            'email_verified' => !is_null($user->email_verified_at),
            'wallet' => [
                // Số dư khả dụng có thể rút
                'balance' => (float) $user->balance,
                'total_cashback' => (float) $user->total_cashback,
                'total_referral_earned' => (float) $user->total_referral_earned,
                'total_withdrawn' => (float) $user->total_withdrawn,
                'currency' => 'VND',
            ],
            'stats' => [
                'orders_total' => (int) $cashbackStats->sum(),
                'orders_pending' => (int) ($cashbackStats['pending'] ?? 0),
                'orders_approved' => (int) ($cashbackStats['approved'] ?? 0),
                'orders_rejected' => (int) ($cashbackStats['rejected'] ?? 0),
                'referrals_count' => (int) $user->referredUsers()->count(),
                'withdrawals_pending' => (int) $pendingWithdraw,
            ],
            'created_at' => optional($user->created_at)->toIso8601String(),
        ]);
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
                'phone' => 'nullable|string|max:15|unique:users,phone,' . $user->id,
            ]);
        } catch (ValidationException $e) {
            return $this->fail(__('Dữ liệu không hợp lệ.'), 422, 'VALIDATION_ERROR', $e->errors());
        }

        $model = User::find($user->id);
        $model->name = $validated['name'];
        // Số điện thoại để trống phải lưu là NULL (không phải chuỗi rỗng) vì cột này có ràng buộc UNIQUE
        $model->phone = !empty($validated['phone']) ? $validated['phone'] : null;
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

        if (!Hash::check($validated['current_password'], $user->password)) {
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
     * Cho phép xóa tài khoản dù còn số dư, tự động lưu vết số dư trước khi xóa vào nhật ký hệ thống.
     */
    public function deleteAccount(Request $request): JsonResponse
    {
        if (config('app.demo')) {
            return $this->fail(__('Chức năng này bị vô hiệu hóa trong chế độ Demo.'), 403, 'DEMO_DISABLED');
        }

        // Chỉ cho phép khi quản trị viên đã bật tùy chọn tự xóa tài khoản
        if (Setting::getVal('allow_self_delete_account', '0') !== '1') {
            return $this->fail(__('Tính năng tự xóa tài khoản hiện đang bị tắt.'), 403, 'FEATURE_DISABLED');
        }

        $user = $this->apiUser($request);

        try {
            $request->validate(['password' => 'required|string'], [
                'password.required' => __('Vui lòng nhập mật khẩu để xác nhận xóa tài khoản.'),
            ]);
        } catch (ValidationException $e) {
            return $this->fail(__('Thiếu mật khẩu xác nhận.'), 422, 'VALIDATION_ERROR', $e->errors());
        }

        if (!Hash::check($request->password, $user->password)) {
            return $this->fail(__('Mật khẩu không chính xác.'), 422, 'WRONG_PASSWORD');
        }

        try {
            $userEmail = $user->email;
            $userBalance = (float) $user->balance;

            DB::transaction(function () use ($user) {
                $model = User::where('id', $user->id)->lockForUpdate()->first();
                if (!$model) {
                    throw new \Exception('USER_NOT_FOUND');
                }
                $email = $model->email;
                // Dọn dẹp dữ liệu không tự cascade (api_tokens có cascade nên tự xóa theo user)
                DB::table('activity_logs')->where('user_id', $model->id)->delete();
                DB::table('password_reset_tokens')->where('email', $email)->delete();
                DB::table('sessions')->where('user_id', $model->id)->delete();
                $model->delete();
            });

            // Ghi nhận nhật ký hệ thống kèm số dư trước khi xóa (truyền null cho user_id để log giữ lại trên hệ thống)
            ActivityLog::log(__('Thành viên tự xóa tài khoản qua Open API: :email (Số dư còn lại trước khi xóa: :balance)', [
                'email' => $userEmail,
                'balance' => \App\Helpers\CurrencyHelper::format($userBalance),
            ]), null);

            return $this->ok(null, __('Tài khoản của bạn đã được xóa vĩnh viễn.'));
        } catch (\Exception $e) {
            \Log::error('Lỗi xóa tài khoản qua Open API: ' . $e->getMessage());
            return $this->fail(__('Có lỗi xảy ra trong quá trình xóa tài khoản. Vui lòng thử lại sau.'), 500, 'DELETE_FAILED');
        }
    }
}
