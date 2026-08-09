<?php

namespace App\Http\Controllers;

use App\Models\CashbackHistory;
use App\Models\DailyCheckin;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RankingController extends Controller
{
    /**
     * Hiển thị trang bảng xếp hạng (Ranking/Leaderboard) trang khách.
     * 
     * Logic nghiệp vụ:
     * - Kiểm tra cấu hình bật/tắt của trang Bảng xếp hạng.
     * - Đọc giới hạn số dòng hiển thị ranking_limit.
     * - Truy vấn danh sách Top đơn hàng, Top tiền hoàn, Top điểm danh, Top giới thiệu.
     * - Chỉ query khi tab BXH tương ứng được bật để tối ưu hiệu năng.
     */
    public function index()
    {
        // 1. Kiểm tra tính năng có được bật chung không
        $enabled = Setting::getVal('ranking_status', '1') === '1';
        if (!$enabled) {
            return redirect()->route('home')->with('error', __('Tính năng bảng xếp hạng tạm thời bị khoá.'));
        }

        // Lấy giới hạn hiển thị tối đa của bảng xếp hạng (mặc định là 10)
        $limit = (int) Setting::getVal('ranking_limit', 10);
        if ($limit <= 0) {
            $limit = 10;
        }

        // 2. Khởi tạo các tập hợp dữ liệu và lấy dữ liệu theo cài đặt từng BXH

        // A. Top đơn hàng (thành viên có số lượng đơn hoàn tiền được duyệt nhiều nhất)
        $topOrders = collect();
        $topOrdersEnabled = Setting::getVal('ranking_top_orders_status', '1') === '1';
        if ($topOrdersEnabled) {
            $topOrders = CashbackHistory::select('user_id', DB::raw('COUNT(*) as total_orders'))
                ->where('status', 'approved')
                ->groupBy('user_id')
                ->orderBy('total_orders', 'desc')
                ->limit($limit)
                ->get();

            // Nạp thông tin người dùng tương ứng (Eager loading thủ công tránh lỗi N+1 queries)
            $userIds = $topOrders->pluck('user_id');
            $users = User::whereIn('id', $userIds)->get()->keyBy('id');
            foreach ($topOrders as $item) {
                $item->user = $users->get($item->user_id);
            }
            // Lọc bỏ các bản ghi bị trống user (nếu có user bị xóa khỏi DB)
            $topOrders = $topOrders->filter(fn($item) => $item->user !== null);
        }

        // B. Top tiền hoàn (thành viên có tổng số tiền nhận hoàn cao nhất)
        $topCashback = collect();
        $topCashbackEnabled = Setting::getVal('ranking_top_cashback_status', '1') === '1';
        if ($topCashbackEnabled) {
            $topCashback = User::where('role', 'user')
                ->where('status', 'active')
                ->where('total_cashback', '>', 0)
                ->orderBy('total_cashback', 'desc')
                ->limit($limit)
                ->get();
        }

        // C. Top điểm danh (thành viên có chuỗi streak điểm danh liên tiếp dài nhất)
        $topCheckin = collect();
        $topCheckinEnabled = Setting::getVal('ranking_top_checkin_status', '1') === '1';
        if ($topCheckinEnabled) {
            // Lấy chuỗi streak lớn nhất của từng user từ lịch sử điểm danh
            $topCheckin = DailyCheckin::select('user_id', DB::raw('MAX(streak_days) as max_streak'))
                ->groupBy('user_id')
                ->orderBy('max_streak', 'desc')
                ->limit($limit)
                ->get();

            // Nạp thông tin người dùng
            $userIds = $topCheckin->pluck('user_id');
            $users = User::whereIn('id', $userIds)->get()->keyBy('id');
            foreach ($topCheckin as $item) {
                $item->user = $users->get($item->user_id);
            }
            $topCheckin = $topCheckin->filter(fn($item) => $item->user !== null);
        }

        // D. Top giới thiệu (thành viên giới thiệu trực tiếp F1 đăng ký nhiều nhất)
        $topReferral = collect();
        $topReferralEnabled = Setting::getVal('ranking_top_referral_status', '1') === '1';
        if ($topReferralEnabled) {
            $topReferral = User::select('referred_by as user_id', DB::raw('COUNT(*) as total_referrals'))
                ->whereNotNull('referred_by')
                ->groupBy('referred_by')
                ->orderBy('total_referrals', 'desc')
                ->limit($limit)
                ->get();

            // Nạp thông tin người giới thiệu
            $userIds = $topReferral->pluck('user_id');
            $users = User::whereIn('id', $userIds)->get()->keyBy('id');
            foreach ($topReferral as $item) {
                $item->user = $users->get($item->user_id);
            }
            $topReferral = $topReferral->filter(fn($item) => $item->user !== null);
        }

        // E. Top số dư khả dụng (thành viên có số dư ví hiện tại cao nhất)
        $topBalance = collect();
        $topBalanceEnabled = Setting::getVal('ranking_top_balance_status', '1') === '1';
        if ($topBalanceEnabled) {
            $topBalance = User::where('role', 'user')
                ->where('status', 'active')
                ->where('balance', '>', 0)
                ->orderBy('balance', 'desc')
                ->limit($limit)
                ->get();
        }

        // Trả về view ranking.index ở frontend kèm theo dữ liệu
        return view('ranking.index', compact(
            'topOrders',
            'topCashback',
            'topCheckin',
            'topReferral',
            'topBalance',
            'topOrdersEnabled',
            'topCashbackEnabled',
            'topCheckinEnabled',
            'topReferralEnabled',
            'topBalanceEnabled'
        ));
    }
}
