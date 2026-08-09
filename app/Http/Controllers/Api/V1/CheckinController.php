<?php

namespace App\Http\Controllers\Api\V1;

use App\Helpers\MoneyHelper;
use App\Models\ActivityLog;
use App\Models\BalanceLog;
use App\Models\CashbackHistory;
use App\Models\DailyCheckin;
use App\Models\Notification;
use App\Models\Setting;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * API Điểm danh hàng ngày.
 * Cho phép App lấy trạng thái điểm danh (streak, lịch sử, mốc thưởng, điều kiện) và thực hiện điểm danh.
 */
class CheckinController extends ApiController
{
    /**
     * Đảm bảo tính năng điểm danh đang bật ở cấp hệ thống.
     */
    private function ensureFeatureEnabled(): ?JsonResponse
    {
        if (Setting::getVal('daily_checkin_enabled', '1') !== '1') {
            return $this->fail(__('Tính năng điểm danh hiện đang tạm khóa.'), 403, 'CHECKIN_DISABLED');
        }

        return null;
    }

    /**
     * Đánh giá toàn bộ điều kiện điểm danh của thành viên.
     * Trả về null nếu đủ điều kiện, ngược lại trả về chuỗi lý do chưa đủ điều kiện.
     */
    private function eligibilityError(User $user, Request $request): ?string
    {
        // Yêu cầu xác minh email
        if (Setting::getVal('checkin_email_verification_required', '0') === '1' && is_null($user->email_verified_at)) {
            return __('Tài khoản của bạn chưa xác minh email. Vui lòng xác minh email để có thể điểm danh.');
        }

        // Giới hạn theo loại thiết bị (dựa trên User-Agent do App gửi lên)
        $allowedDevice = Setting::getVal('checkin_device_allow', 'both');
        if ($allowedDevice !== 'both') {
            $userAgent = $request->userAgent() ?? 'Unknown';
            $isMobile = preg_match('/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows ce|xda|xiino/i', $userAgent) || preg_match('/ipad|playbook|silk/i', $userAgent);

            if ($allowedDevice === 'mobile' && ! $isMobile) {
                return __('Hệ thống chỉ cho phép điểm danh trên thiết bị di động (Mobile).');
            }
            if ($allowedDevice === 'desktop' && $isMobile) {
                return __('Hệ thống chỉ cho phép điểm danh trên máy tính (Desktop).');
            }
        }

        // Số đơn hàng tối thiểu phát sinh trong tháng
        $minOrders = (int) Setting::getVal('checkin_min_orders_monthly', 0);
        if ($minOrders > 0) {
            $monthlyOrders = CashbackHistory::where('user_id', $user->id)
                ->whereYear('created_at', now()->year)
                ->whereMonth('created_at', now()->month)
                ->count();
            if ($monthlyOrders < $minOrders) {
                return __('Bạn cần phát sinh tối thiểu :min đơn hàng hoàn tiền trong tháng này để được điểm danh (Hiện tại có :current đơn).', [
                    'min' => $minOrders,
                    'current' => $monthlyOrders,
                ]);
            }
        }

        // Số ngày tuổi tài khoản tối thiểu
        $minAccountAgeDays = (int) Setting::getVal('checkin_min_account_age_days', 0);
        if ($minAccountAgeDays > 0 && $user->created_at) {
            $accountAgeDays = (int) Carbon::parse($user->created_at)->startOfDay()->diffInDays(now()->startOfDay());
            if ($accountAgeDays < $minAccountAgeDays) {
                return __('Tài khoản của bạn cần đăng ký tối thiểu :min ngày mới được điểm danh (Hiện tại tài khoản mới đăng ký được :current ngày).', [
                    'min' => $minAccountAgeDays,
                    'current' => $accountAgeDays,
                ]);
            }
        }

        return null;
    }

    /**
     * GET /api/v1/openapi/checkin
     * Trạng thái điểm danh: đã điểm danh hôm nay chưa, streak, mốc thưởng, lịch sử, điều kiện.
     */
    public function index(Request $request): JsonResponse
    {
        if ($resp = $this->ensureFeatureEnabled()) {
            return $resp;
        }

        $user = $this->apiUser($request);

        $hasCheckedInToday = DailyCheckin::where('user_id', $user->id)
            ->where('checked_in_date', now()->toDateString())
            ->exists();

        // Streak còn hiệu lực nếu lần điểm danh cuối là hôm nay hoặc hôm qua
        $lastCheckin = DailyCheckin::where('user_id', $user->id)
            ->orderByDesc('checked_in_date')
            ->first();
        $currentStreak = 0;
        if ($lastCheckin) {
            $lastDate = Carbon::parse($lastCheckin->checked_in_date);
            if ($lastDate->isToday() || $lastDate->isYesterday()) {
                $currentStreak = (int) $lastCheckin->streak_days;
            }
        }

        $milestones = array_map(
            fn ($amount): int => (int) MoneyHelper::round($amount),
            json_decode(Setting::getVal('checkin_streak_milestones', '{"7":2000}'), true) ?: []
        );

        $history = DailyCheckin::where('user_id', $user->id)
            ->orderByDesc('checked_in_date')
            ->paginate(10);

        $eligibilityError = $this->eligibilityError($user, $request);

        return $this->ok([
            'has_checked_in_today' => $hasCheckedInToday,
            'can_checkin' => ! $hasCheckedInToday && $eligibilityError === null,
            'ineligible_reason' => $hasCheckedInToday ? __('Hôm nay bạn đã điểm danh rồi.') : $eligibilityError,
            'current_streak' => $currentStreak,
            'reward_coins' => (int) MoneyHelper::round(Setting::getVal('checkin_reward_coins', 500)),
            'milestones' => (object) $milestones,
            'history' => [
                'items' => $history->getCollection()->map(fn (DailyCheckin $c) => [
                    'coins_earned' => (int) MoneyHelper::round($c->coins_earned),
                    'streak_days' => (int) $c->streak_days,
                    'checked_in_date' => optional($c->checked_in_date)->toDateString(),
                ]),
                'pagination' => [
                    'current_page' => $history->currentPage(),
                    'per_page' => $history->perPage(),
                    'total' => $history->total(),
                    'last_page' => $history->lastPage(),
                ],
            ],
        ]);
    }

    /**
     * POST /api/v1/openapi/checkin
     * Thực hiện điểm danh; cộng thưởng vào ví trong giao dịch khóa dòng chống điểm danh trùng.
     */
    public function store(Request $request): JsonResponse
    {
        if ($resp = $this->ensureFeatureEnabled()) {
            return $resp;
        }

        $user = $this->apiUser($request);

        if ($reason = $this->eligibilityError($user, $request)) {
            return $this->fail($reason, 400, 'CHECKIN_INELIGIBLE');
        }

        $today = now()->toDateString();

        return DB::transaction(function () use ($user, $today) {
            // Khóa dòng user để tuần tự hóa các yêu cầu điểm danh đồng thời của cùng tài khoản
            $userModel = User::where('id', $user->id)->lockForUpdate()->first();
            if (! $userModel) {
                return $this->fail(__('Không tìm thấy thông tin tài khoản người dùng.'), 404, 'USER_NOT_FOUND');
            }

            $alreadyCheckedIn = DailyCheckin::where('user_id', $userModel->id)
                ->where('checked_in_date', $today)
                ->exists();
            if ($alreadyCheckedIn) {
                return $this->fail(__('Hôm nay bạn đã điểm danh rồi. Vui lòng quay lại vào ngày mai!'), 400, 'ALREADY_CHECKED_IN');
            }

            // Tính streak dựa trên bản ghi hôm qua
            $yesterday = now()->subDay()->toDateString();
            $lastCheckin = DailyCheckin::where('user_id', $userModel->id)
                ->where('checked_in_date', $yesterday)
                ->first();
            $streakDays = $lastCheckin ? $lastCheckin->streak_days + 1 : 1;

            // Tính thưởng + thưởng mốc streak
            // Chuẩn hoá về số nguyên đồng phòng trường hợp admin nhập giá trị cấu hình có phần thập phân
            $rewardCoins = MoneyHelper::round(Setting::getVal('checkin_reward_coins', 500));
            $totalEarned = $rewardCoins;
            $milestones = json_decode(Setting::getVal('checkin_streak_milestones', '{"7":2000}'), true) ?: [];
            $bonusCoins = 0;
            $isBonus = false;
            if (isset($milestones[$streakDays])) {
                $bonusCoins = MoneyHelper::round($milestones[$streakDays]);
                $totalEarned += $bonusCoins;
                $isBonus = true;
            }

            DailyCheckin::create([
                'user_id' => $userModel->id,
                'coins_earned' => $totalEarned,
                'streak_days' => $streakDays,
                'checked_in_date' => $today,
            ]);

            // Cộng thưởng vào ví
            $oldBalance = $userModel->balance;
            $userModel->balance += $totalEarned;
            $userModel->total_cashback += $totalEarned;
            $userModel->save();

            $logMsg = __('Điểm danh ngày :date', ['date' => $today]);
            if ($isBonus) {
                $logMsg .= ' '.__('(Thưởng chuỗi :days ngày)', ['days' => $streakDays]);
            }
            BalanceLog::write($userModel, $oldBalance, $totalEarned, $userModel->balance, 'checkin', $logMsg);

            ActivityLog::log(__('Điểm danh ngày :date nhận +:amount (qua Open API)', [
                'date' => $today,
                'amount' => number_format($totalEarned, 0, ',', '.').'đ',
            ]), $userModel->id);

            Notification::create([
                'user_id' => $userModel->id,
                'title' => __('Điểm danh thành công!'),
                'content' => __('Bạn đã nhận :amount từ việc điểm danh hôm nay. Chuỗi ngày hiện tại: :days ngày.', [
                    'amount' => number_format($totalEarned, 0, ',', '.').'đ',
                    'days' => $streakDays,
                ]),
            ]);

            return $this->ok([
                'coins_earned' => (int) MoneyHelper::round($totalEarned),
                'streak_days' => (int) $streakDays,
                'is_bonus' => $isBonus,
                'bonus_amount' => (int) MoneyHelper::round($bonusCoins),
                'new_balance' => (int) MoneyHelper::round($userModel->balance),
            ], __('Điểm danh thành công!'));
        });
    }
}
