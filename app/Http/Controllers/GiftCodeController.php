<?php

namespace App\Http\Controllers;

use App\Models\GiftCode;
use App\Models\GiftCodeRedemption;
use App\Models\ActivityLog;
use App\Models\BalanceLog;
use App\Models\Notification;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Controller xử lý chức năng nhập Giftcode nhận thưởng phía thành viên.
 */
class GiftCodeController extends Controller
{
    /**
     * Hiển thị trang nhập Giftcode và lịch sử đổi mã của thành viên.
     */
    public function index(Request $request)
    {
        // Kiểm tra trạng thái bật/tắt của chức năng
        if (Setting::getVal('gift_code_enabled', '0') !== '1') {
            return redirect()->route('dashboard')
                ->with('error', __('Chức năng nhập Giftcode hiện đang tạm khóa để bảo trì.'));
        }

        $user = Auth::user();

        // Lịch sử các lượt đổi mã của chính người dùng
        $redemptions = GiftCodeRedemption::with('giftCode')
            ->where('user_id', $user->id)
            ->orderBy('id', 'desc')
            ->paginate(8)
            ->withQueryString();

        // Tổng tiền thưởng đã nhận từ Giftcode để hiển thị widget thống kê
        $totalEarned = (float) GiftCodeRedemption::where('user_id', $user->id)->sum('amount');

        $intro = Setting::getVal('gift_code_intro', '');

        return view('dashboard.giftcode.index', compact('redemptions', 'totalEarned', 'intro'));
    }

    /**
     * Phản hồi thống nhất cho cả request AJAX (JSON) lẫn request thường (redirect + flash).
     */
    private function respond(Request $request, bool $success, string $message, array $extra = [])
    {
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(array_merge(['success' => $success, 'message' => $message], $extra));
        }

        if ($success) {
            return redirect()->route('giftcode.index')->with('success', $message);
        }

        return redirect()->back()->withInput()->with('error', $message);
    }

    /**
     * Thực hiện đổi Giftcode nhận thưởng.
     */
    public function redeem(Request $request)
    {
        // Kiểm tra trạng thái bật/tắt của chức năng
        if (Setting::getVal('gift_code_enabled', '0') !== '1') {
            return $this->respond($request, false, __('Chức năng nhập Giftcode hiện đang tạm khóa.'));
        }

        $request->validate([
            'code' => 'required|string|max:50',
        ], [
            'code.required' => __('Vui lòng nhập mã Giftcode.'),
        ]);

        // Chuẩn hóa mã giống cách lưu ở admin (in hoa, bỏ khoảng trắng)
        $code = strtoupper(trim(str_replace(' ', '', $request->input('code'))));
        $user = Auth::user();

        try {
            $amount = DB::transaction(function () use ($code, $user, $request) {
                // Khóa bản ghi mã để chống đổi vượt số lượt khi nhiều request đồng thời
                $giftCode = GiftCode::where('code', $code)->lockForUpdate()->first();

                if (!$giftCode) {
                    throw new \RuntimeException(__('Mã Giftcode không tồn tại. Vui lòng kiểm tra lại.'));
                }

                // 1. Trạng thái & khung thời gian hiệu lực
                if (!$giftCode->status) {
                    throw new \RuntimeException(__('Mã Giftcode này hiện đã bị tạm dừng.'));
                }
                if (!$giftCode->hasStarted()) {
                    throw new \RuntimeException(__('Mã Giftcode này chưa tới thời gian sử dụng.'));
                }
                if ($giftCode->isExpired()) {
                    throw new \RuntimeException(__('Mã Giftcode này đã hết hạn sử dụng.'));
                }
                if ($giftCode->isSoldOut()) {
                    throw new \RuntimeException(__('Mã Giftcode này đã hết lượt sử dụng.'));
                }

                // 2. Khóa bản ghi user để chống cộng tiền sai do race condition
                $dbUser = User::where('id', $user->id)->lockForUpdate()->firstOrFail();

                // Đảm bảo tài khoản người dùng vẫn ở trạng thái hoạt động (active), phòng trường hợp tài khoản bị khoá song song trong lúc gửi yêu cầu
                if ($dbUser->status !== 'active') {
                    throw new \RuntimeException(__('Tài khoản của bạn hiện đang bị tạm khóa.'));
                }

                // 3. Kiểm tra các điều kiện đủ tư cách riêng của từng mã
                if ($giftCode->require_verified_email && empty($dbUser->email_verified_at)) {
                    throw new \RuntimeException(__('Bạn cần xác minh email trước khi sử dụng mã này.'));
                }

                if ($giftCode->min_total_cashback > 0 && $dbUser->total_cashback < $giftCode->min_total_cashback) {
                    throw new \RuntimeException(__('Bạn cần đạt tối thiểu :amount tiền hoàn tích lũy để dùng mã này.', [
                        'amount' => number_format($giftCode->min_total_cashback, 0, ',', '.') . 'đ',
                    ]));
                }

                if ($giftCode->min_account_age_days > 0
                    && $dbUser->created_at
                    && $dbUser->created_at->gt(now()->subDays($giftCode->min_account_age_days))) {
                    throw new \RuntimeException(__('Tài khoản của bạn cần hoạt động tối thiểu :days ngày để dùng mã này.', [
                        'days' => $giftCode->min_account_age_days,
                    ]));
                }

                if ($giftCode->new_user_within_days
                    && $dbUser->created_at
                    && $dbUser->created_at->lt(now()->subDays($giftCode->new_user_within_days))) {
                    throw new \RuntimeException(__('Mã này chỉ dành cho tài khoản mới đăng ký trong vòng :days ngày.', [
                        'days' => $giftCode->new_user_within_days,
                    ]));
                }

                // 4. Kiểm tra giới hạn số lượt mỗi user
                $userUsedCount = GiftCodeRedemption::where('gift_code_id', $giftCode->id)
                    ->where('user_id', $dbUser->id)
                    ->count();
                if ($userUsedCount >= $giftCode->per_user_limit) {
                    throw new \RuntimeException(__('Bạn đã sử dụng mã này đủ số lần cho phép.'));
                }

                // 5. Tính phần thưởng và cộng vào ví khả dụng
                $amount = $giftCode->resolveRewardAmount();

                $oldBalance = $dbUser->balance;
                $newBalance = $oldBalance + $amount;
                // balance được gán tường minh (không mass-assign) vì cột này đã bị loại khỏi $fillable vì lý do bảo mật.
                $dbUser->balance = $newBalance;
                $dbUser->save();

                // 6. Tăng lượt sử dụng của mã
                $giftCode->increment('used_count');

                // 7. Lưu nhật ký lượt đổi
                GiftCodeRedemption::create([
                    'gift_code_id' => $giftCode->id,
                    'user_id' => $dbUser->id,
                    'code' => $giftCode->code,
                    'amount' => $amount,
                    'ip_address' => $request->ip(),
                ]);

                // 8. Ghi nhận biến động số dư (sử dụng hàm dịch đa ngôn ngữ để hiển thị mô tả lịch sử giao dịch chính xác theo ngôn ngữ lựa chọn)
                BalanceLog::write(
                    $dbUser,
                    $oldBalance,
                    $amount,
                    $newBalance,
                    'giftcode_reward',
                    __('Nhận thưởng Giftcode :code', ['code' => '#' . $giftCode->code])
                );

                // 9. Ghi nhật ký hoạt động bảo mật (sử dụng hàm dịch đa ngôn ngữ phòng trường hợp admin/user duyệt xem lịch sử hoạt động ngoài frontend)
                ActivityLog::log(
                    __('Đổi Giftcode :code nhận thưởng :amount', [
                        'code' => '#' . $giftCode->code,
                        'amount' => number_format($amount, 0, ',', '.') . 'đ'
                    ]),
                    $dbUser->id
                );

                // 10. Gửi thông báo cho người dùng
                Notification::create([
                    'user_id' => $dbUser->id,
                    'title' => __('Nhận thưởng Giftcode thành công'),
                    'content' => __('Bạn vừa nhận :amount từ mã Giftcode :code vào số dư ví khả dụng.', [
                        'amount' => number_format($amount, 0, ',', '.') . 'đ',
                        'code' => $giftCode->code,
                    ]),
                ]);

                return $amount;
            });

            return $this->respond($request, true, __('Chúc mừng! Bạn đã nhận :amount từ Giftcode vào ví khả dụng.', [
                'amount' => number_format($amount, 0, ',', '.') . 'đ',
            ]), ['amount' => $amount]);
        } catch (\RuntimeException $e) {
            // Lỗi nghiệp vụ có thông báo thân thiện cho người dùng
            return $this->respond($request, false, $e->getMessage());
        } catch (\Throwable $e) {
            \Log::error('Lỗi đổi Giftcode: ' . $e->getMessage());
            return $this->respond($request, false, __('Có lỗi xảy ra khi đổi mã, vui lòng thử lại sau.'));
        }
    }
}
