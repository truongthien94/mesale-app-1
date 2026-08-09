<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\CashbackHistory;
use App\Models\Withdrawal;
use App\Models\ReferralCommission;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminDashboardController extends Controller
{


    /**
     * Trang chủ Admin Dashboard.
     * Thu thập toàn bộ số liệu thống kê, bao gồm doanh thu, chi phí, lợi nhuận thực tế
     * và dữ liệu biểu đồ tăng trưởng 30 ngày gần nhất.
     */
    public function index()
    {
        // ===== 0. CẤU HÌNH WIDGETS THỐNG KÊ =====
        $defaultWidgets = [
            ['key' => 'total_users', 'name' => __('Tổng thành viên'), 'visible' => true],
            ['key' => 'total_orders', 'name' => __('Tổng đơn hàng'), 'visible' => true], // Widget đơn hàng mới được thêm vào bên phải widget thành viên
            ['key' => 'member_balance', 'name' => __('Số dư thành viên'), 'visible' => true], // Widget hiển thị tổng số dư hiện tại của tất cả thành viên trong hệ thống
            ['key' => 'revenue', 'name' => __('Doanh thu Shopee'), 'visible' => true],
            ['key' => 'checkin_revenue', 'name' => __('Doanh thu Điểm danh'), 'visible' => true],
            ['key' => 'coupon_revenue', 'name' => __('Doanh thu Mã giảm giá'), 'visible' => true],
            ['key' => 'cashback', 'name' => __('Cashback trả F0'), 'visible' => true],
            ['key' => 'mlm_commission', 'name' => __('Hoa hồng MLM (F1+F2)'), 'visible' => true],
            ['key' => 'profit', 'name' => __('Lợi nhuận thực tế'), 'visible' => true],
            ['key' => 'total_withdrawn', 'name' => __('Tổng rút thành công'), 'visible' => true],
            ['key' => 'online_users', 'name' => __('Thành viên Online'), 'visible' => true],
            ['key' => 'checkin_paid', 'name' => __('Chi trả Điểm danh'), 'visible' => true],
            ['key' => 'task_paid', 'name' => __('Chi trả Nhiệm vụ'), 'visible' => true], // Widget hiển thị tổng số tiền đã chi trả thưởng làm nhiệm vụ của người dùng
            ['key' => 'giftcode_paid', 'name' => __('Chi trả Giftcode'), 'visible' => true], // Widget hiển thị tổng số tiền đã chi trả thưởng khi người dùng đổi mã Giftcode
            ['key' => 'gift_exchanged', 'name' => __('Quà tặng quy đổi'), 'visible' => true], // Widget hiển thị tổng số tiền thành viên đã dùng để đổi quà tặng
        ];

        $widgetsConfigRaw = \App\Models\Setting::getVal('admin_dashboard_widgets');
        $widgetsConfig = [];
        if ($widgetsConfigRaw) {
            $widgetsConfig = json_decode($widgetsConfigRaw, true);
        }

        if (empty($widgetsConfig)) {
            $widgetsConfig = $defaultWidgets;
        } else {
            // Kiểm tra xem có widget nào mới chưa có trong config không để bổ sung vào đúng vị trí sau widget thành viên
            // Business rule: Giúp tự động cập nhật bố cục cho tài khoản admin đã từng lưu cấu hình trước đó mà không cần reset DB
            $configKeys = array_column($widgetsConfig, 'key');
            foreach ($defaultWidgets as $dw) {
                if (!in_array($dw['key'], $configKeys)) {
                    if ($dw['key'] === 'total_orders') {
                        $usersIndex = array_search('total_users', $configKeys);
                        if ($usersIndex !== false) {
                            // Chèn widget đơn hàng ngay sau widget tổng thành viên
                            array_splice($widgetsConfig, $usersIndex + 1, 0, [$dw]);
                        } else {
                            $widgetsConfig[] = $dw;
                        }
                    } elseif ($dw['key'] === 'member_balance') {
                        // Tìm vị trí của widget tổng thành viên hoặc tổng đơn hàng để chèn widget số dư thành viên ngay bên cạnh
                        // Giúp giao diện các widget liên quan đến tài chính và người dùng nằm liền kề trực quan hơn
                        $usersIndex = array_search('total_users', $configKeys);
                        $ordersIndex = array_search('total_orders', $configKeys);
                        $insertIndex = $ordersIndex !== false ? $ordersIndex : ($usersIndex !== false ? $usersIndex : -1);
                        if ($insertIndex !== -1) {
                            array_splice($widgetsConfig, $insertIndex + 1, 0, [$dw]);
                        } else {
                            $widgetsConfig[] = $dw;
                        }
                    } elseif ($dw['key'] === 'task_paid') {
                        // Tìm vị trí của widget chi trả điểm danh để chèn widget chi trả nhiệm vụ ngay sau đó
                        // Giúp nhóm các widget chi phí thưởng nằm cạnh nhau dễ theo dõi
                        $checkinPaidIndex = array_search('checkin_paid', $configKeys);
                        if ($checkinPaidIndex !== false) {
                            array_splice($widgetsConfig, $checkinPaidIndex + 1, 0, [$dw]);
                        } else {
                            $widgetsConfig[] = $dw;
                        }
                    } elseif ($dw['key'] === 'giftcode_paid') {
                        // Tìm vị trí của widget chi trả nhiệm vụ để chèn widget chi trả giftcode ngay sau đó
                        // Giúp nhóm các widget chi phí thưởng nằm cạnh nhau dễ theo dõi
                        $taskPaidIndex = array_search('task_paid', $configKeys);
                        if ($taskPaidIndex !== false) {
                            array_splice($widgetsConfig, $taskPaidIndex + 1, 0, [$dw]);
                        } else {
                            $widgetsConfig[] = $dw;
                        }
                    } elseif ($dw['key'] === 'gift_exchanged') {
                        // Tìm vị trí của widget chi trả giftcode để chèn widget quà tặng quy đổi ngay sau đó
                        // Giúp các widget liên quan đến phần thưởng và quà tặng được gom nhóm hợp lý
                        $giftcodePaidIndex = array_search('giftcode_paid', $configKeys);
                        if ($giftcodePaidIndex !== false) {
                            array_splice($widgetsConfig, $giftcodePaidIndex + 1, 0, [$dw]);
                        } else {
                            $widgetsConfig[] = $dw;
                        }
                    } else {
                        $widgetsConfig[] = $dw;
                    }
                    $configKeys = array_column($widgetsConfig, 'key');
                }
            }
            // Loại bỏ các widget không còn tồn tại nếu có
            $defaultKeys = array_column($defaultWidgets, 'key');
            $widgetsConfig = array_filter($widgetsConfig, function($w) use ($defaultKeys) {
                return in_array($w['key'], $defaultKeys);
            });
            $widgetsConfig = array_values($widgetsConfig);
            
            // Cập nhật lại tên bản dịch nếu thay đổi ngôn ngữ
            foreach ($widgetsConfig as &$w) {
                foreach ($defaultWidgets as $dw) {
                    if ($w['key'] === $dw['key']) {
                        $w['name'] = $dw['name'];
                        break;
                    }
                }
            }
        }

        // ===== 1. THỐNG KÊ TỔNG HỢP =====

        // Tổng thành viên (không tính admin để phản ánh đúng lượng user thực)
        $totalUsers = User::where('role', '!=', 'admin')->count();

        // Số thành viên đăng ký mới trong ngày hôm nay (không tính admin)
        $todayUsers = User::where('role', '!=', 'admin')
            ->whereDate('created_at', today())
            ->count();

        // Tổng số đơn hàng hoàn tiền được ghi nhận trên hệ thống
        $totalOrders = CashbackHistory::count();

        // Số đơn hàng hoàn tiền mới phát sinh trong ngày hôm nay
        $todayOrders = CashbackHistory::whereDate('created_at', today())->count();

        // Số thành viên đang online (hoạt động trong vòng 5 phút qua)
        $totalOnlineUsers = User::where('last_seen_at', '>=', now()->subMinutes(5))->count();

        // Tổng doanh thu = Tổng hoa hồng nhận được từ Shopee của các đơn đã duyệt
        $totalRevenue = CashbackHistory::where('status', 'approved')->sum('commission_amount');

        // Tổng cashback đã trả cho người mua hàng (F0) — chỉ đơn đã duyệt
        $totalCashback = CashbackHistory::where('status', 'approved')->sum('cashback_amount');

        // Tổng hoa hồng MLM đã chi cho người giới thiệu (F1 + F2)
        // Đây là chi phí bắt buộc phải trừ khi tính lợi nhuận thực
        $totalReferralPaid = ReferralCommission::where('status', 'approved')->sum('amount');

        // Doanh thu điểm danh (Đã duyệt)
        $totalCheckinRevenue = \App\Models\CheckinRevenue::where('status', 'approved')->sum('commission_amount');

        // Doanh thu điểm danh đang chờ đối soát (Pending)
        $totalCheckinPending = \App\Models\CheckinRevenue::where('status', 'pending')->sum('commission_amount');

        // Doanh thu mã giảm giá (Đã duyệt)
        $totalCouponRevenue = \App\Models\CouponRevenue::where('status', 'approved')->sum('commission_amount');

        // Doanh thu mã giảm giá đang chờ đối soát (Pending)
        $totalCouponPending = \App\Models\CouponRevenue::where('status', 'pending')->sum('commission_amount');

        // Lợi nhuận thực tế = Doanh thu Shopee - Cashback F0 - Hoa hồng MLM (F1+F2) + Doanh thu Điểm danh + Doanh thu Mã giảm giá
        // Công thức này phản ánh đúng số tiền hệ thống giữ lại sau khi trả hết các bên
        $systemProfit = $totalRevenue - $totalCashback - $totalReferralPaid + $totalCheckinRevenue + $totalCouponRevenue;

        // Tính tổng số dư ví khả dụng hiện tại của toàn bộ thành viên trong hệ thống (không bao gồm các tài khoản quản trị)
        // Việc thống kê số dư này giúp admin nắm được tổng nghĩa vụ tài chính/nợ cần chi trả cho người dùng bất kỳ lúc nào
        $totalMemberBalance = User::where('role', '!=', 'admin')->sum('balance');

        // Tổng tiền rút thành công của toàn bộ thành viên
        $totalWithdrawn = Withdrawal::where('status', 'approved')->sum('amount');

        // Tổng tiền chi trả cho tính năng Điểm danh hàng ngày
        $totalCheckinPaid = \App\Models\DailyCheckin::sum('coins_earned');

        // Số tiền chi trả cho tính năng Điểm danh hôm nay
        $todayCheckinPaid = \App\Models\DailyCheckin::whereDate('checked_in_date', today())->sum('coins_earned');

        // Tính tổng số tiền đã chi trả thưởng cho các nhiệm vụ mà thành viên đã nhận thưởng thành công (status = claimed)
        // Kết hợp với bảng tasks để lấy được giá trị thưởng reward_amount của từng nhiệm vụ
        $totalTaskPaid = \App\Models\UserTask::where('status', 'claimed')
            ->join('tasks', 'user_tasks.task_id', '=', 'tasks.id')
            ->sum('tasks.reward_amount');

        // Tính tổng số tiền đã chi trả thưởng làm nhiệm vụ phát sinh trong ngày hôm nay
        $todayTaskPaid = \App\Models\UserTask::where('status', 'claimed')
            ->whereDate('user_tasks.claimed_at', today())
            ->join('tasks', 'user_tasks.task_id', '=', 'tasks.id')
            ->sum('tasks.reward_amount');

        // Tính tổng số tiền đã chi trả thưởng khi thành viên đổi mã quà tặng Giftcode thành công
        // Lấy từ bảng gift_code_redemptions lưu trữ tất cả các lượt nhập mã thành công của người dùng
        $totalGiftcodePaid = \App\Models\GiftCodeRedemption::sum('amount');

        // Tính tổng số tiền chi trả thưởng Giftcode phát sinh trong ngày hôm nay
        $todayGiftcodePaid = \App\Models\GiftCodeRedemption::whereDate('created_at', today())->sum('amount');

        // Tính tổng số tiền mà user đã sử dụng để đổi quà tặng thành công (approved)
        // Việc này giúp admin nắm được tổng giá trị quà tặng đã được quy đổi thực tế
        $totalGiftExchanged = \App\Models\GiftRedemption::where('status', 'approved')->sum('amount');

        // Tính tổng số tiền đổi quà tặng đang ở trạng thái chờ duyệt (pending)
        // Giúp admin theo dõi dòng tiền đang tạm khóa để chuẩn bị phê duyệt/xử lý
        $pendingGiftExchanged = \App\Models\GiftRedemption::where('status', 'pending')->sum('amount');

        // Tính số tiền yêu cầu đổi quà tặng phát sinh hôm nay (bao gồm cả chờ duyệt và thành công)
        // Phục vụ thống kê hiệu suất hoạt động đổi quà trong ngày
        $todayGiftExchanged = \App\Models\GiftRedemption::whereDate('created_at', today())->sum('amount');

        // ===== 1.5. THỐNG KÊ CHƯA GHI NHẬN (PENDING) =====
        
        // Doanh thu chưa ghi nhận (Shopee pending)
        $pendingRevenue = CashbackHistory::where('status', 'pending')->sum('commission_amount');

        // Số đơn hoàn tiền đang chờ duyệt
        $pendingCashbackCount = CashbackHistory::where('status', 'pending')->count();
        $pendingCashbackAmount = CashbackHistory::where('status', 'pending')->sum('cashback_amount');

        // Dự kiến hoa hồng MLM (F1+F2) cho các đơn đang pending
        $pendingReferralF1Rate = (float)\App\Models\Setting::getVal('referral_f1_rate', 5);
        $pendingReferralF2Rate = (float)\App\Models\Setting::getVal('referral_f2_rate', 2);
        $referralEnabled = \App\Models\Setting::getVal('referral_enabled', '1') === '1';
        $referralF2Enabled = \App\Models\Setting::getVal('referral_f2_enabled', '1') === '1';

        $pendingReferralsPaid = 0;
        $pendingCashbacks = CashbackHistory::where('status', 'pending')
            ->with(['user.referrer'])
            ->get();

        // Lặp qua để dự tính hoa hồng MLM của các đơn pending dựa vào tuyến trên của thành viên mua hàng
        foreach ($pendingCashbacks as $cb) {
            if ($referralEnabled && $cb->user && $cb->user->referred_by) {
                // Hoa hồng F1 dự kiến
                $f1Comm = \App\Helpers\MoneyHelper::round($cb->cashback_amount * ($pendingReferralF1Rate / 100));
                $pendingReferralsPaid += $f1Comm;

                // Hoa hồng F2 dự kiến
                if ($referralF2Enabled && $cb->user->referrer && $cb->user->referrer->referred_by) {
                    $f2Comm = \App\Helpers\MoneyHelper::round($cb->cashback_amount * ($pendingReferralF2Rate / 100));
                    $pendingReferralsPaid += $f2Comm;
                }
            }
        }

        // Lợi nhuận chưa ghi nhận dự kiến = (Doanh thu Shopee pending - Cashback F0 pending - MLM pending) + Checkin pending + Coupon pending
        $pendingProfit = ($pendingRevenue - $pendingCashbackAmount - $pendingReferralsPaid) + $totalCheckinPending + $totalCouponPending;

        // ===== 2. THỐNG KÊ CHỜ DUYỆT (Cần admin xử lý) =====

        // Số lệnh rút tiền đang chờ duyệt
        $pendingWithdrawalCount = Withdrawal::where('status', 'pending')->count();
        $pendingWithdrawalAmount = Withdrawal::where('status', 'pending')->sum('amount');

        // Số đơn đổi quà đang chờ duyệt
        $pendingGiftCount = \App\Models\GiftRedemption::where('status', 'pending')->count();
        $pendingGiftAmount = \App\Models\GiftRedemption::where('status', 'pending')->sum('amount');

        // ===== 3. DANH SÁCH GIAO DỊCH MỚI NHẤT =====

        $recentCashbacks = CashbackHistory::with('user')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        $recentWithdrawals = Withdrawal::with('user')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        $recentLogs = ActivityLog::with('user')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        // ===== 4. DỮ LIỆU BIỂU ĐỒ TĂNG TRƯỞNG (Mặc định 30 ngày gần nhất) =====
        $growthChartData = $this->getGrowthChartData('30_days');
        $labels = $growthChartData['labels'];
        $revenueSeries = $growthChartData['revenueSeries'];
        $cashbackSeries = $growthChartData['cashbackSeries'];
        $referralSeries = $growthChartData['referralSeries'];
        $pendingRevenueSeries = $growthChartData['pendingRevenueSeries'];
        $pendingProfitSeries = $growthChartData['pendingProfitSeries'];
        $profitSeries = $growthChartData['profitSeries'];

        return view('admin.dashboard', compact(
            'widgetsConfig',
            'totalUsers',
            'todayUsers',
            'totalOrders',
            'todayOrders',
            'totalOnlineUsers',
            'totalMemberBalance',
            'totalRevenue',
            'totalCashback',
            'totalReferralPaid',
            'systemProfit',
            'totalWithdrawn',
            'totalCheckinPaid',
            'todayCheckinPaid',
            'totalTaskPaid',
            'todayTaskPaid',
            'totalGiftcodePaid',
            'todayGiftcodePaid',
            'totalGiftExchanged',
            'pendingGiftExchanged',
            'todayGiftExchanged',
            'totalCheckinRevenue',
            'totalCheckinPending',
            'totalCouponRevenue',
            'totalCouponPending',
            'pendingRevenue',
            'pendingProfit',
            'pendingCashbackCount',
            'pendingCashbackAmount',
            'pendingWithdrawalCount',
            'pendingWithdrawalAmount',
            'pendingGiftCount',
            'pendingGiftAmount',
            'recentCashbacks',
            'recentWithdrawals',
            'recentLogs',
            'labels',
            'revenueSeries',
            'cashbackSeries',
            'referralSeries',
            'pendingRevenueSeries',
            'pendingProfitSeries',
            'profitSeries'
        ));
    }

    /**
     * Thu thập dữ liệu biểu đồ tăng trưởng doanh thu & chi phí theo khoảng thời gian được chọn.
     * Hỗ trợ các mốc thời gian: 'today' (hôm nay), '7_days' (7 ngày), '30_days' (30 ngày), '1_year' (1 năm).
     * Mọi logic block đều có comment tiếng Việt giải thích chi tiết.
     *
     * @param string $range Tùy chọn khoảng thời gian ('today', '7_days', '30_days', '1_year')
     * @return array Mảng chứa mảng nhãn (labels) và dữ liệu các đường chuỗi (series)
     */
    public function getGrowthChartData(string $range = '30_days'): array
    {
        // Dự kiến hoa hồng MLM (F1+F2) cho các đơn pending để tính lợi nhuận chưa ghi nhận
        $pendingReferralF1Rate = (float)\App\Models\Setting::getVal('referral_f1_rate', 5);
        $pendingReferralF2Rate = (float)\App\Models\Setting::getVal('referral_f2_rate', 2);
        $referralEnabled = \App\Models\Setting::getVal('referral_enabled', '1') === '1';
        $referralF2Enabled = \App\Models\Setting::getVal('referral_f2_enabled', '1') === '1';

        $labels = [];
        $revenueSeries = [];
        $cashbackSeries = [];
        $referralSeries = [];
        $pendingRevenueSeries = [];
        $pendingProfitSeries = [];
        $profitSeries = [];

        if ($range === 'today') {
            // ===== TRƯỜNG HỢP 1: HÔM NAY (Theo 24 khung giờ trong ngày) =====
            $start = today()->startOfDay();
            $end = today()->endOfDay();

            // Lấy dữ liệu doanh thu Shopee đã duyệt theo giờ
            $chartData = CashbackHistory::select(
                    DB::raw('HOUR(approved_at) as hour_val'),
                    DB::raw('SUM(commission_amount) as revenue'),
                    DB::raw('SUM(cashback_amount) as cashback')
                )
                ->where('status', 'approved')
                ->whereNotNull('approved_at')
                ->whereBetween('approved_at', [$start, $end])
                ->groupBy(DB::raw('HOUR(approved_at)'))
                ->get();

            // Lấy hoa hồng MLM đã duyệt theo giờ
            $referralChartData = ReferralCommission::select(
                    DB::raw('HOUR(created_at) as hour_val'),
                    DB::raw('SUM(amount) as referral_paid')
                )
                ->where('status', 'approved')
                ->whereBetween('created_at', [$start, $end])
                ->groupBy(DB::raw('HOUR(created_at)'))
                ->get();

            // Lấy doanh thu Shopee pending theo giờ
            $pendingShopeeChartData = CashbackHistory::select(
                    DB::raw('HOUR(created_at) as hour_val'),
                    DB::raw('SUM(commission_amount) as revenue'),
                    DB::raw('SUM(cashback_amount) as cashback')
                )
                ->where('status', 'pending')
                ->whereBetween('created_at', [$start, $end])
                ->groupBy(DB::raw('HOUR(created_at)'))
                ->get();

            // Lấy doanh thu Điểm danh pending theo giờ
            $pendingCheckinChartData = \App\Models\CheckinRevenue::select(
                    DB::raw('HOUR(created_at) as hour_val'),
                    DB::raw('SUM(commission_amount) as commission')
                )
                ->where('status', 'pending')
                ->whereBetween('created_at', [$start, $end])
                ->groupBy(DB::raw('HOUR(created_at)'))
                ->get();

            // Lấy doanh thu Mã giảm giá pending theo giờ
            $pendingCouponChartData = \App\Models\CouponRevenue::select(
                    DB::raw('HOUR(created_at) as hour_val'),
                    DB::raw('SUM(commission_amount) as commission')
                )
                ->where('status', 'pending')
                ->whereBetween('created_at', [$start, $end])
                ->groupBy(DB::raw('HOUR(created_at)'))
                ->get();

            // Lấy doanh thu Điểm danh đã duyệt theo giờ
            $checkinChartData = \App\Models\CheckinRevenue::select(
                    DB::raw('HOUR(created_at) as hour_val'),
                    DB::raw('SUM(commission_amount) as commission')
                )
                ->where('status', 'approved')
                ->whereBetween('created_at', [$start, $end])
                ->groupBy(DB::raw('HOUR(created_at)'))
                ->get();

            // Lấy doanh thu Mã giảm giá đã duyệt theo giờ
            $couponChartData = \App\Models\CouponRevenue::select(
                    DB::raw('HOUR(created_at) as hour_val'),
                    DB::raw('SUM(commission_amount) as commission')
                )
                ->where('status', 'approved')
                ->whereBetween('created_at', [$start, $end])
                ->groupBy(DB::raw('HOUR(created_at)'))
                ->get();

            // Tính hoa hồng MLM cho các đơn pending hôm nay theo giờ
            $pendingCashbacks = CashbackHistory::where('status', 'pending')
                ->whereBetween('created_at', [$start, $end])
                ->with(['user.referrer'])
                ->get();

            $pendingReferralByHour = [];
            foreach ($pendingCashbacks as $cb) {
                $h = (int)$cb->created_at->format('H');
                if (!isset($pendingReferralByHour[$h])) {
                    $pendingReferralByHour[$h] = 0;
                }
                if ($referralEnabled && $cb->user && $cb->user->referred_by) {
                    $f1Comm = \App\Helpers\MoneyHelper::round($cb->cashback_amount * ($pendingReferralF1Rate / 100));
                    $pendingReferralByHour[$h] += $f1Comm;
                    if ($referralF2Enabled && $cb->user->referrer && $cb->user->referrer->referred_by) {
                        $f2Comm = \App\Helpers\MoneyHelper::round($cb->cashback_amount * ($pendingReferralF2Rate / 100));
                        $pendingReferralByHour[$h] += $f2Comm;
                    }
                }
            }

            $currentHour = (int)now()->format('H');
            for ($h = 0; $h <= $currentHour; $h++) {
                $labels[] = sprintf('%02d:00', $h);

                $dayData = $chartData->firstWhere('hour_val', $h);
                $revenueVal = $dayData ? (float)$dayData->revenue : 0;
                $cashbackVal = $dayData ? (float)$dayData->cashback : 0;
                $revenueSeries[] = $revenueVal;
                $cashbackSeries[] = $cashbackVal;

                $refData = $referralChartData->firstWhere('hour_val', $h);
                $refVal = $refData ? (float)$refData->referral_paid : 0;
                $referralSeries[] = $refVal;

                $pShopee = $pendingShopeeChartData->firstWhere('hour_val', $h);
                $pShopeeRevenue = $pShopee ? (float)$pShopee->revenue : 0;
                $pShopeeCashback = $pShopee ? (float)$pShopee->cashback : 0;

                $pCheckin = $pendingCheckinChartData->firstWhere('hour_val', $h);
                $pCheckinComm = $pCheckin ? (float)$pCheckin->commission : 0;

                $pCoupon = $pendingCouponChartData->firstWhere('hour_val', $h);
                $pCouponComm = $pCoupon ? (float)$pCoupon->commission : 0;

                $pendingRevenueSeries[] = $pShopeeRevenue + $pCheckinComm + $pCouponComm;

                $pMLM = $pendingReferralByHour[$h] ?? 0;
                $pendingProfitSeries[] = ($pShopeeRevenue - $pShopeeCashback - $pMLM) + $pCheckinComm + $pCouponComm;

                $cCheckin = $checkinChartData->firstWhere('hour_val', $h);
                $cCheckinComm = $cCheckin ? (float)$cCheckin->commission : 0;

                $cCoupon = $couponChartData->firstWhere('hour_val', $h);
                $cCouponComm = $cCoupon ? (float)$cCoupon->commission : 0;

                $profitSeries[] = $revenueVal - $cashbackVal - $refVal + $cCheckinComm + $cCouponComm;
            }

        } elseif ($range === '1_year') {
            // ===== TRƯỜNG HỢP 2: 1 NĂM (12 Tháng Gần Nhất) =====
            $start = now()->subMonths(11)->startOfMonth();

            $chartData = CashbackHistory::select(
                    DB::raw('DATE_FORMAT(approved_at, "%Y-%m") as month_val'),
                    DB::raw('SUM(commission_amount) as revenue'),
                    DB::raw('SUM(cashback_amount) as cashback')
                )
                ->where('status', 'approved')
                ->whereNotNull('approved_at')
                ->where('approved_at', '>=', $start)
                ->groupBy(DB::raw('DATE_FORMAT(approved_at, "%Y-%m")'))
                ->get();

            $referralChartData = ReferralCommission::select(
                    DB::raw('DATE_FORMAT(created_at, "%Y-%m") as month_val'),
                    DB::raw('SUM(amount) as referral_paid')
                )
                ->where('status', 'approved')
                ->where('created_at', '>=', $start)
                ->groupBy(DB::raw('DATE_FORMAT(created_at, "%Y-%m")'))
                ->get();

            $pendingShopeeChartData = CashbackHistory::select(
                    DB::raw('DATE_FORMAT(created_at, "%Y-%m") as month_val'),
                    DB::raw('SUM(commission_amount) as revenue'),
                    DB::raw('SUM(cashback_amount) as cashback')
                )
                ->where('status', 'pending')
                ->where('created_at', '>=', $start)
                ->groupBy(DB::raw('DATE_FORMAT(created_at, "%Y-%m")'))
                ->get();

            $pendingCheckinChartData = \App\Models\CheckinRevenue::select(
                    DB::raw('DATE_FORMAT(created_at, "%Y-%m") as month_val'),
                    DB::raw('SUM(commission_amount) as commission')
                )
                ->where('status', 'pending')
                ->where('created_at', '>=', $start)
                ->groupBy(DB::raw('DATE_FORMAT(created_at, "%Y-%m")'))
                ->get();

            $pendingCouponChartData = \App\Models\CouponRevenue::select(
                    DB::raw('DATE_FORMAT(created_at, "%Y-%m") as month_val'),
                    DB::raw('SUM(commission_amount) as commission')
                )
                ->where('status', 'pending')
                ->where('created_at', '>=', $start)
                ->groupBy(DB::raw('DATE_FORMAT(created_at, "%Y-%m")'))
                ->get();

            $checkinChartData = \App\Models\CheckinRevenue::select(
                    DB::raw('DATE_FORMAT(created_at, "%Y-%m") as month_val'),
                    DB::raw('SUM(commission_amount) as commission')
                )
                ->where('status', 'approved')
                ->where('created_at', '>=', $start)
                ->groupBy(DB::raw('DATE_FORMAT(created_at, "%Y-%m")'))
                ->get();

            $couponChartData = \App\Models\CouponRevenue::select(
                    DB::raw('DATE_FORMAT(created_at, "%Y-%m") as month_val'),
                    DB::raw('SUM(commission_amount) as commission')
                )
                ->where('status', 'approved')
                ->where('created_at', '>=', $start)
                ->groupBy(DB::raw('DATE_FORMAT(created_at, "%Y-%m")'))
                ->get();

            $pendingCashbacks = CashbackHistory::where('status', 'pending')
                ->where('created_at', '>=', $start)
                ->with(['user.referrer'])
                ->get();

            $pendingReferralByMonth = [];
            foreach ($pendingCashbacks as $cb) {
                $mStr = $cb->created_at->format('Y-m');
                if (!isset($pendingReferralByMonth[$mStr])) {
                    $pendingReferralByMonth[$mStr] = 0;
                }
                if ($referralEnabled && $cb->user && $cb->user->referred_by) {
                    $f1Comm = \App\Helpers\MoneyHelper::round($cb->cashback_amount * ($pendingReferralF1Rate / 100));
                    $pendingReferralByMonth[$mStr] += $f1Comm;
                    if ($referralF2Enabled && $cb->user->referrer && $cb->user->referrer->referred_by) {
                        $f2Comm = \App\Helpers\MoneyHelper::round($cb->cashback_amount * ($pendingReferralF2Rate / 100));
                        $pendingReferralByMonth[$mStr] += $f2Comm;
                    }
                }
            }

            for ($i = 11; $i >= 0; $i--) {
                $dt = now()->subMonths($i);
                $monthStr = $dt->format('Y-m');
                $labels[] = __('Thg :month', ['month' => $dt->format('m/Y')]);

                $dayData = $chartData->firstWhere('month_val', $monthStr);
                $revenueVal = $dayData ? (float)$dayData->revenue : 0;
                $cashbackVal = $dayData ? (float)$dayData->cashback : 0;
                $revenueSeries[] = $revenueVal;
                $cashbackSeries[] = $cashbackVal;

                $refData = $referralChartData->firstWhere('month_val', $monthStr);
                $refVal = $refData ? (float)$refData->referral_paid : 0;
                $referralSeries[] = $refVal;

                $pShopee = $pendingShopeeChartData->firstWhere('month_val', $monthStr);
                $pShopeeRevenue = $pShopee ? (float)$pShopee->revenue : 0;
                $pShopeeCashback = $pShopee ? (float)$pShopee->cashback : 0;

                $pCheckin = $pendingCheckinChartData->firstWhere('month_val', $monthStr);
                $pCheckinComm = $pCheckin ? (float)$pCheckin->commission : 0;

                $pCoupon = $pendingCouponChartData->firstWhere('month_val', $monthStr);
                $pCouponComm = $pCoupon ? (float)$pCoupon->commission : 0;

                $pendingRevenueSeries[] = $pShopeeRevenue + $pCheckinComm + $pCouponComm;

                $pMLM = $pendingReferralByMonth[$monthStr] ?? 0;
                $pendingProfitSeries[] = ($pShopeeRevenue - $pShopeeCashback - $pMLM) + $pCheckinComm + $pCouponComm;

                $cCheckin = $checkinChartData->firstWhere('month_val', $monthStr);
                $cCheckinComm = $cCheckin ? (float)$cCheckin->commission : 0;

                $cCoupon = $couponChartData->firstWhere('month_val', $monthStr);
                $cCouponComm = $cCoupon ? (float)$cCoupon->commission : 0;

                $profitSeries[] = $revenueVal - $cashbackVal - $refVal + $cCheckinComm + $cCouponComm;
            }

        } else {
            // ===== TRƯỜNG HỢP 3: 7 NGÀY HOẶC 30 NGÀY (Mặc định) =====
            $days = ($range === '7_days') ? 7 : 30;
            $start = now()->subDays($days - 1)->startOfDay();

            $chartData = CashbackHistory::select(
                    DB::raw('DATE(approved_at) as date'),
                    DB::raw('SUM(commission_amount) as revenue'),
                    DB::raw('SUM(cashback_amount) as cashback')
                )
                ->where('status', 'approved')
                ->whereNotNull('approved_at')
                ->where('approved_at', '>=', $start)
                ->groupBy(DB::raw('DATE(approved_at)'))
                ->orderBy('date', 'asc')
                ->get();

            $referralChartData = ReferralCommission::select(
                    DB::raw('DATE(created_at) as date'),
                    DB::raw('SUM(amount) as referral_paid')
                )
                ->where('status', 'approved')
                ->where('created_at', '>=', $start)
                ->groupBy(DB::raw('DATE(created_at)'))
                ->orderBy('date', 'asc')
                ->get();

            $pendingShopeeChartData = CashbackHistory::select(
                    DB::raw('DATE(created_at) as date'),
                    DB::raw('SUM(commission_amount) as revenue'),
                    DB::raw('SUM(cashback_amount) as cashback')
                )
                ->where('status', 'pending')
                ->where('created_at', '>=', $start)
                ->groupBy(DB::raw('DATE(created_at)'))
                ->get();

            $pendingCheckinChartData = \App\Models\CheckinRevenue::select(
                    DB::raw('DATE(created_at) as date'),
                    DB::raw('SUM(commission_amount) as commission')
                )
                ->where('status', 'pending')
                ->where('created_at', '>=', $start)
                ->groupBy(DB::raw('DATE(created_at)'))
                ->get();

            $pendingCouponChartData = \App\Models\CouponRevenue::select(
                    DB::raw('DATE(created_at) as date'),
                    DB::raw('SUM(commission_amount) as commission')
                )
                ->where('status', 'pending')
                ->where('created_at', '>=', $start)
                ->groupBy(DB::raw('DATE(created_at)'))
                ->get();

            $checkinChartData = \App\Models\CheckinRevenue::select(
                    DB::raw('DATE(created_at) as date'),
                    DB::raw('SUM(commission_amount) as commission')
                )
                ->where('status', 'approved')
                ->where('created_at', '>=', $start)
                ->groupBy(DB::raw('DATE(created_at)'))
                ->get();

            $couponChartData = \App\Models\CouponRevenue::select(
                    DB::raw('DATE(created_at) as date'),
                    DB::raw('SUM(commission_amount) as commission')
                )
                ->where('status', 'approved')
                ->where('created_at', '>=', $start)
                ->groupBy(DB::raw('DATE(created_at)'))
                ->get();

            $pendingCashbacks = CashbackHistory::where('status', 'pending')
                ->where('created_at', '>=', $start)
                ->with(['user.referrer'])
                ->get();

            $pendingReferralByDate = [];
            foreach ($pendingCashbacks as $cb) {
                $dateStr = $cb->created_at->toDateString();
                if (!isset($pendingReferralByDate[$dateStr])) {
                    $pendingReferralByDate[$dateStr] = 0;
                }
                if ($referralEnabled && $cb->user && $cb->user->referred_by) {
                    $f1Comm = \App\Helpers\MoneyHelper::round($cb->cashback_amount * ($pendingReferralF1Rate / 100));
                    $pendingReferralByDate[$dateStr] += $f1Comm;
                    if ($referralF2Enabled && $cb->user->referrer && $cb->user->referrer->referred_by) {
                        $f2Comm = \App\Helpers\MoneyHelper::round($cb->cashback_amount * ($pendingReferralF2Rate / 100));
                        $pendingReferralByDate[$dateStr] += $f2Comm;
                    }
                }
            }

            for ($i = $days - 1; $i >= 0; $i--) {
                $dateStr = now()->subDays($i)->toDateString();
                $labels[] = now()->subDays($i)->format('d/m');

                $dayData = $chartData->firstWhere('date', $dateStr);
                $revenueVal = $dayData ? (float)$dayData->revenue : 0;
                $cashbackVal = $dayData ? (float)$dayData->cashback : 0;
                $revenueSeries[] = $revenueVal;
                $cashbackSeries[] = $cashbackVal;

                $refData = $referralChartData->firstWhere('date', $dateStr);
                $refVal = $refData ? (float)$refData->referral_paid : 0;
                $referralSeries[] = $refVal;

                $pShopee = $pendingShopeeChartData->firstWhere('date', $dateStr);
                $pShopeeRevenue = $pShopee ? (float)$pShopee->revenue : 0;
                $pShopeeCashback = $pShopee ? (float)$pShopee->cashback : 0;

                $pCheckin = $pendingCheckinChartData->firstWhere('date', $dateStr);
                $pCheckinComm = $pCheckin ? (float)$pCheckin->commission : 0;

                $pCoupon = $pendingCouponChartData->firstWhere('date', $dateStr);
                $pCouponComm = $pCoupon ? (float)$pCoupon->commission : 0;

                $pendingRevenueSeries[] = $pShopeeRevenue + $pCheckinComm + $pCouponComm;

                $pMLM = isset($pendingReferralByDate[$dateStr]) ? (float)$pendingReferralByDate[$dateStr] : 0;
                $pendingProfitSeries[] = ($pShopeeRevenue - $pShopeeCashback - $pMLM) + $pCheckinComm + $pCouponComm;

                $cCheckin = $checkinChartData->firstWhere('date', $dateStr);
                $cCheckinComm = $cCheckin ? (float)$cCheckin->commission : 0;

                $cCoupon = $couponChartData->firstWhere('date', $dateStr);
                $cCouponComm = $cCoupon ? (float)$cCoupon->commission : 0;

                $profitSeries[] = $revenueVal - $cashbackVal - $refVal + $cCheckinComm + $cCouponComm;
            }
        }

        return [
            'labels' => $labels,
            'revenueSeries' => $revenueSeries,
            'cashbackSeries' => $cashbackSeries,
            'referralSeries' => $referralSeries,
            'pendingRevenueSeries' => $pendingRevenueSeries,
            'pendingProfitSeries' => $pendingProfitSeries,
            'profitSeries' => $profitSeries,
            'range' => $range,
        ];
    }

    /**
     * Endpoint API trả về dữ liệu biểu đồ tăng trưởng dạng JSON khi Admin chọn khoảng thời gian.
     * Mọi logic block đều có comment tiếng Việt giải thích chi tiết.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getGrowthChartDataAjax(Request $request)
    {
        $range = (string)$request->query('range', '30_days');
        $validRanges = ['today', '7_days', '30_days', '1_year'];
        if (!in_array($range, $validRanges, true)) {
            $range = '30_days';
        }

        $data = $this->getGrowthChartData($range);

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    /**
     * Lưu cấu hình sắp xếp và ẩn/hiển thị widget thống kê của Admin Dashboard.
     * Mọi logic block có comment tiếng Việt giải thích đầy đủ.
     */
    public function saveWidgetsConfig(Request $request)
    {
        // Kiểm tra dữ liệu đầu vào gửi lên qua request AJAX
        $request->validate([
            'widgets' => 'required|array',
            'widgets.*.key' => 'required|string',
            'widgets.*.name' => 'required|string',
            'widgets.*.visible' => 'required|boolean',
        ]);

        $widgets = $request->input('widgets');

        // Thực hiện cập nhật cấu hình dạng chuỗi JSON vào bảng settings
        \App\Models\Setting::setVal('admin_dashboard_widgets', json_encode($widgets), 'Cấu hình ẩn hiện và thứ tự widget thống kê Admin Dashboard');

        // Ghi nhận nhật ký hoạt động (activity log) của admin để phục vụ giám sát bảo mật
        \App\Models\ActivityLog::log('Cấu hình lại các widget thống kê trên Dashboard quản trị.');

        // Trả về phản hồi JSON báo thành công cho phía Client
        return response()->json([
            'success' => true,
            'message' => __('Cấu hình widget thống kê đã được lưu thành công!')
        ]);
    }
}
