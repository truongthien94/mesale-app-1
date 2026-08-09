<?php

namespace App\Http\Controllers;

use App\Models\DailyCheckin;
use App\Models\Setting;
use App\Models\User;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DailyCheckinController extends Controller
{


    /**
     * Hiển thị trang điểm danh hàng ngày.
     */
    public function index()
    {
        $user = Auth::user();
        
        // 1. Kiểm tra tính năng có bật không
        $enabled = Setting::getVal('daily_checkin_enabled', '1') === '1';
        if (!$enabled) {
            return redirect()->route('dashboard')->with('error', 'Tính năng điểm danh tạm thời bị khoá.');
        }

        // Kiểm tra yêu cầu xác minh email
        $emailVerifiedRequired = Setting::getVal('checkin_email_verification_required', '0') === '1';
        $emailWarning = null;
        if ($emailVerifiedRequired && is_null($user->email_verified_at)) {
            $emailWarning = __('Tài khoản của bạn chưa xác minh email. Vui lòng xác minh email để có thể điểm danh.');
        }

        // Kiểm tra thiết bị truy cập của người dùng xem có được phép không
        $allowedDevice = Setting::getVal('checkin_device_allow', 'both');
        $userAgent = request()->userAgent() ?? 'Unknown';
        $isMobile = preg_match('/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows ce|xda|xiino/i', $userAgent) || preg_match('/ipad|playbook|silk/i', $userAgent);
        
        $deviceWarning = null;
        if ($allowedDevice === 'mobile' && !$isMobile) {
            $deviceWarning = __('Hệ thống chỉ cho phép điểm danh trên thiết bị di động (Mobile). Bạn đang truy cập bằng máy tính.');
        } elseif ($allowedDevice === 'desktop' && $isMobile) {
            $deviceWarning = __('Hệ thống chỉ cho phép điểm danh trên máy tính (Desktop). Bạn đang truy cập bằng thiết bị di động.');
        }

        // Kiểm tra điều kiện số đơn hàng phát sinh tối thiểu trong tháng hiện tại
        $minOrders = (int) Setting::getVal('checkin_min_orders_monthly', 0);
        $monthlyOrdersCount = 0;
        $orderWarning = null;
        if ($minOrders > 0) {
            $monthlyOrdersCount = \App\Models\CashbackHistory::where('user_id', $user->id)
                ->whereYear('created_at', now()->year)
                ->whereMonth('created_at', now()->month)
                ->count();
            
            if ($monthlyOrdersCount < $minOrders) {
                $orderWarning = __('Hệ thống yêu cầu bạn phải phát sinh tối thiểu :min đơn hàng hoàn tiền trong tháng này để được điểm danh (Hiện tại bạn chỉ mới có :current đơn).', [
                    'min' => $minOrders,
                    'current' => $monthlyOrdersCount
                ]);
            }
        }

        // Kiểm tra điều kiện số ngày đăng ký tối thiểu của tài khoản
        $minAccountAgeDays = (int) Setting::getVal('checkin_min_account_age_days', 0);
        $accountAgeWarning = null;
        if ($minAccountAgeDays > 0 && $user->created_at) {
            $accountAgeDays = (int) \Carbon\Carbon::parse($user->created_at)->startOfDay()->diffInDays(now()->startOfDay());
            if ($accountAgeDays < $minAccountAgeDays) {
                $accountAgeWarning = __('Tài khoản của bạn cần đăng ký tối thiểu :min ngày mới được điểm danh (Hiện tại tài khoản mới đăng ký được :current ngày).', [
                    'min' => $minAccountAgeDays,
                    'current' => $accountAgeDays
                ]);
            }
        }

        // Lấy lịch sử điểm danh của user (phân trang)
        $checkins = DailyCheckin::where('user_id', $user->id)
            ->orderBy('checked_in_date', 'desc')
            ->paginate(10);

        // Kiểm tra xem hôm nay đã điểm danh chưa
        $hasCheckedInToday = DailyCheckin::where('user_id', $user->id)
            ->where('checked_in_date', now()->toDateString())
            ->exists();

        // Lấy streak hiện tại của user từ lần điểm danh cuối cùng
        $lastCheckin = DailyCheckin::where('user_id', $user->id)
            ->orderBy('checked_in_date', 'desc')
            ->first();

        $currentStreak = 0;
        if ($lastCheckin) {
            $lastDate = \Carbon\Carbon::parse($lastCheckin->checked_in_date);
            // Nếu điểm danh hôm nay hoặc hôm qua thì streak còn hiệu lực
            if ($lastDate->isToday() || $lastDate->isYesterday()) {
                $currentStreak = $lastCheckin->streak_days;
            }
        }

        // 2. Tạo bảng xếp hạng điểm danh (10 người dùng có streak hiện tại lớn nhất)
        // Chỉ xét người dùng điểm danh hôm nay hoặc hôm qua (streak còn hiệu lực) để tránh hiển thị streak cũ không còn hoạt động
        $showLeaderboard = Setting::getVal('show_checkin_leaderboard', '1') === '1';
        if ($showLeaderboard) {
            $leaderboard = DailyCheckin::select('user_id', DB::raw('MAX(streak_days) as max_streak'))
                ->whereIn('checked_in_date', [now()->toDateString(), now()->subDay()->toDateString()])
                ->groupBy('user_id')
                ->orderBy('max_streak', 'desc')
                ->limit(10)
                ->get();

            // Nạp thông tin user cho bảng xếp hạng (batch load tránh N+1 query)
            $leaderboardUserIds = $leaderboard->pluck('user_id');
            $leaderboardUsers = User::whereIn('id', $leaderboardUserIds)->get()->keyBy('id');
            foreach ($leaderboard as $item) {
                $item->user = $leaderboardUsers->get($item->user_id);
            }
        } else {
            $leaderboard = collect([]);
        }

        // Cấu hình xu thưởng để hiển thị
        // Chuẩn hoá y hệt lúc cộng tiền ở hàm store() để con số quảng bá trên trang luôn bằng đúng số tiền thực nhận
        $rewardCoins = \App\Helpers\MoneyHelper::round(Setting::getVal('checkin_reward_coins', 500));
        $milestonesJson = Setting::getVal('checkin_streak_milestones', '{"7":2000}');
        $milestones = array_map(
            fn ($bonus) => \App\Helpers\MoneyHelper::round($bonus),
            json_decode($milestonesJson, true) ?: []
        );

        // Tính toán redirect URL (gắn UTM nếu chưa có) để truyền xuống view an toàn
        $checkinRedirectEnabled = Setting::getVal('checkin_redirect_enabled', '0') === '1';
        $checkinRedirectDevice = Setting::getVal('checkin_redirect_device', 'both');
        $checkinRedirectUrl = Setting::getVal('checkin_redirect_url', '');
        if (!empty($checkinRedirectUrl)) {
            $utmSource = Setting::getVal('checkin_redirect_utm_source', 'diemdanh');
            if (!empty($utmSource) && !str_contains($checkinRedirectUrl, 'utm_source=')) {
                $separator = str_contains($checkinRedirectUrl, '?') ? '&' : '?';
                $checkinRedirectUrl .= $separator . 'utm_source=' . urlencode($utmSource);
            }
        }

        return view('dashboard.checkin', compact(
            'user',
            'checkins',
            'hasCheckedInToday',
            'currentStreak',
            'leaderboard',
            'showLeaderboard',
            'rewardCoins',
            'milestones',
            'deviceWarning',
            'orderWarning',
            'emailWarning',
            'accountAgeWarning',
            'checkinRedirectEnabled',
            'checkinRedirectDevice',
            'checkinRedirectUrl'
        ));
    }

    /**
     * Xử lý yêu cầu điểm danh bằng POST/AJAX.
     */
    public function checkin(Request $request)
    {
        $user = Auth::user();

        // 1. Kiểm tra tính năng có hoạt động không
        $enabled = Setting::getVal('daily_checkin_enabled', '1') === '1';
        if (!$enabled) {
            return response()->json([
                'status' => 'error',
                'message' => 'Tính năng điểm danh hiện đang tạm khóa.'
            ], 403);
        }

        // Kiểm tra yêu cầu xác minh email
        $emailVerifiedRequired = Setting::getVal('checkin_email_verification_required', '0') === '1';
        if ($emailVerifiedRequired && is_null($user->email_verified_at)) {
            return response()->json([
                'status' => 'error',
                'message' => __('Tài khoản của bạn chưa xác minh email. Vui lòng xác minh email để có thể điểm danh.')
            ], 400);
        }

        // Kiểm tra thiết bị của user xem có được phép điểm danh không
        $allowedDevice = Setting::getVal('checkin_device_allow', 'both');
        if ($allowedDevice !== 'both') {
            $userAgent = request()->userAgent() ?? 'Unknown';
            $isMobile = preg_match('/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows ce|xda|xiino/i', $userAgent) || preg_match('/ipad|playbook|silk/i', $userAgent);
            
            if ($allowedDevice === 'mobile' && !$isMobile) {
                return response()->json([
                    'status' => 'error',
                    'message' => __('Hệ thống chỉ cho phép điểm danh trên thiết bị di động (Mobile).')
                ], 400);
            }
            
            if ($allowedDevice === 'desktop' && $isMobile) {
                return response()->json([
                    'status' => 'error',
                    'message' => __('Hệ thống chỉ cho phép điểm danh trên máy tính (Desktop).')
                ], 400);
            }
        }

        // Kiểm tra số lượng đơn hàng hoàn tiền tối thiểu trong tháng hiện tại
        $minOrders = (int) Setting::getVal('checkin_min_orders_monthly', 0);
        if ($minOrders > 0) {
            $monthlyOrdersCount = \App\Models\CashbackHistory::where('user_id', $user->id)
                ->whereYear('created_at', now()->year)
                ->whereMonth('created_at', now()->month)
                ->count();
                
            if ($monthlyOrdersCount < $minOrders) {
                return response()->json([
                    'status' => 'error',
                    'message' => __('Bạn cần phát sinh tối thiểu :min đơn hàng hoàn tiền trong tháng này để được điểm danh (Hiện tại có :current đơn).', [
                        'min' => $minOrders,
                        'current' => $monthlyOrdersCount
                    ])
                ], 400);
            }
        }

        // Kiểm tra số ngày đăng ký tối thiểu của tài khoản
        $minAccountAgeDays = (int) Setting::getVal('checkin_min_account_age_days', 0);
        if ($minAccountAgeDays > 0 && $user->created_at) {
            $accountAgeDays = (int) \Carbon\Carbon::parse($user->created_at)->startOfDay()->diffInDays(now()->startOfDay());
            if ($accountAgeDays < $minAccountAgeDays) {
                return response()->json([
                    'status' => 'error',
                    'message' => __('Tài khoản của bạn cần đăng ký tối thiểu :min ngày mới được điểm danh (Hiện tại tài khoản mới đăng ký được :current ngày).', [
                        'min' => $minAccountAgeDays,
                        'current' => $accountAgeDays
                    ])
                ], 400);
            }
        }

        $today = now()->toDateString();

        // 2. Thực hiện toàn bộ quy trình kiểm tra và cập nhật trong Transaction.
        // Cần khóa dòng (lockForUpdate) bản ghi User ngay từ đầu để xếp hàng (tuần tự hóa) các yêu cầu điểm danh đồng thời của cùng một tài khoản.
        // Nếu không khóa User trước mà chỉ kiểm tra exists() trên bảng daily_checkins, do bản ghi ngày hôm nay chưa tồn tại nên lockForUpdate() trên daily_checkins sẽ không khóa được gì,
        // dẫn đến các request song song đều vượt qua bước kiểm tra và ghi đè số dư ví, gây lỗi Double Check-in hoặc sai lệch số tiền.
        return DB::transaction(function () use ($user, $today) {
            // Khóa dòng user để đảm bảo tính an toàn dữ liệu và đồng bộ hóa tuyệt đối
            $userModel = User::where('id', $user->id)->lockForUpdate()->first();

            if (!$userModel) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Không tìm thấy thông tin tài khoản người dùng.'
                ], 404);
            }

            // Kiểm tra xem hôm nay tài khoản này đã điểm danh hay chưa
            $alreadyCheckedIn = DailyCheckin::where('user_id', $userModel->id)
                ->where('checked_in_date', $today)
                ->exists();

            if ($alreadyCheckedIn) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Hôm nay bạn đã điểm danh rồi. Vui lòng quay lại vào ngày mai!'
                ], 400);
            }

            // 3. Tính toán số ngày điểm danh liên tiếp (Streak) dựa trên ngày hôm qua
            $yesterday = now()->subDay()->toDateString();
            $lastCheckin = DailyCheckin::where('user_id', $userModel->id)
                ->where('checked_in_date', $yesterday)
                ->first();

            if ($lastCheckin) {
                $streakDays = $lastCheckin->streak_days + 1;
            } else {
                $streakDays = 1;
            }

            // 4. Tính toán tiền thưởng nhận được từ cấu hình động của hệ thống
            // Chuẩn hoá về số nguyên đồng phòng trường hợp admin nhập giá trị cấu hình có phần thập phân
            $rewardCoins = \App\Helpers\MoneyHelper::round(Setting::getVal('checkin_reward_coins', 500));
            $totalEarned = $rewardCoins;

            $milestonesJson = Setting::getVal('checkin_streak_milestones', '{"7":2000}');
            $milestones = json_decode($milestonesJson, true) ?: [];

            // Kiểm tra xem chuỗi streak hiện tại có được thưởng thêm theo các mốc cấu hình hay không
            $bonusCoins = 0;
            $isBonus = false;
            if (isset($milestones[$streakDays])) {
                $bonusCoins = \App\Helpers\MoneyHelper::round($milestones[$streakDays]);
                $totalEarned += $bonusCoins;
                $isBonus = true;
            }

            // 5. Lưu bản ghi điểm danh mới vào cơ sở dữ liệu
            DailyCheckin::create([
                'user_id' => $userModel->id,
                'coins_earned' => $totalEarned,
                'streak_days' => $streakDays,
                'checked_in_date' => $today
            ]);

            // 6. Cộng tiền thưởng trực tiếp vào số dư ví của User đã được khóa dòng ở trên
            $oldBalance = $userModel->balance;
            $userModel->balance += $totalEarned;
            $userModel->total_cashback += $totalEarned;
            $userModel->save();

            // Ghi nhận biến động số dư
            $logMsg = "Điểm danh ngày {$today}";
            if ($isBonus) {
                $logMsg .= " (Thưởng chuỗi {$streakDays} ngày)";
            }
            \App\Models\BalanceLog::write(
                $userModel,
                $oldBalance,
                $totalEarned,
                $userModel->balance,
                'checkin',
                $logMsg
            );

            // Ghi nhận lịch sử hoạt động
            $msg = "Điểm danh ngày {$today} nhận +" . number_format($rewardCoins) . "đ";
            if ($isBonus) {
                $msg .= " (Thưởng chuỗi {$streakDays} ngày +" . number_format($bonusCoins) . "đ)";
            }
            ActivityLog::log($msg, $user->id);

            // Gửi thông báo hệ thống cho user
            \App\Models\Notification::create([
                'user_id' => $user->id,
                'title' => 'Điểm danh thành công!',
                'content' => "Bạn đã nhận được " . number_format($totalEarned) . "đ từ việc điểm danh ngày hôm nay. Chuỗi ngày hiện tại: {$streakDays} ngày."
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Điểm danh thành công!',
                'data' => [
                    'coins_earned' => $totalEarned,
                    'streak_days' => $streakDays,
                    'is_bonus' => $isBonus,
                    'bonus_amount' => $bonusCoins,
                    'new_balance' => (float)$userModel->balance
                ]
            ]);
        });
    }
}
