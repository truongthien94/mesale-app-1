<?php

namespace App\Http\Controllers;

use App\Models\CashbackHistory;
use App\Models\Referral;
use App\Models\ReferralCommission;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReferralController extends Controller
{


    /**
     * Hiển thị trang tiếp thị liên kết (Affiliate Referral).
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        // 1. Kiểm tra tính năng có bật không
        $enabled = Setting::getVal('referral_enabled', '1') === '1';
        if (!$enabled) {
            return redirect()->route('dashboard')->with('error', 'Tính năng tiếp thị liên kết tạm thời bị khoá.');
        }

        // Lấy link giới thiệu cá nhân (Sử dụng trang chủ làm trang đích giới thiệu thay vì trang đăng ký theo yêu cầu)
        $referralLink = route('home', ['ref' => $user->referral_code]);

        // 2. Lấy danh sách thành viên F1 (đăng ký trực tiếp qua link)
        $f1Users = User::where('referred_by', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();

        $f1Ids = $f1Users->pluck('id')->toArray();

        // 3. Lấy danh sách thành viên F2 (đăng ký qua F1)
        $f2Enabled = Setting::getVal('referral_f2_enabled', '1') === '1';
        $f2Users = collect();
        if ($f2Enabled && !empty($f1Ids)) {
            $f2Users = User::whereIn('referred_by', $f1Ids)
                ->orderBy('created_at', 'desc')
                ->get();
        }

        // 4. Thống kê hoa hồng tiếp thị liên kết nhận được
        $totalCommission = ReferralCommission::where('referrer_id', $user->id)
            ->where('status', 'approved')
            ->sum('amount');

        $pendingCommission = ReferralCommission::where('referrer_id', $user->id)
            ->where('status', 'pending')
            ->sum('amount');

        // Tổng hoa hồng đã duyệt phát sinh từ TỪNG thành viên cấp dưới (để hiển thị ở tab Mạng lưới)
        // Trả về dạng [referred_id => tổng tiền], dùng để biết thành viên nào tạo ra hoa hồng hiệu quả nhất
        $memberEarnings = ReferralCommission::where('referrer_id', $user->id)
            ->where('status', 'approved')
            ->groupBy('referred_id')
            ->selectRaw('referred_id, SUM(amount) as total')
            ->pluck('total', 'referred_id');

        // Danh sách lịch sử hoa hồng (phân trang) - hỗ trợ lọc theo cấp (F1/F2) và trạng thái (chờ duyệt/đã duyệt)
        $commissions = ReferralCommission::with(['referred', 'cashbackHistory'])
            ->where('referrer_id', $user->id)
            ->when($request->filled('level'), fn($q) => $q->where('level', $request->query('level')))
            ->when($request->filled('status'), fn($q) => $q->where('status', $request->query('status')))
            ->orderBy('created_at', 'desc')
            ->paginate(10)
            ->appends($request->only(['level', 'status'])); // Giữ tham số lọc khi chuyển trang AJAX

        // Gộp toàn bộ ID thành viên cấp dưới (F1 + F2 nếu bật) để lấy đơn hàng phát sinh của họ
        $memberIds = array_merge($f1Ids, $f2Users->pluck('id')->toArray());

        // Danh sách đơn hàng của các thành viên cấp dưới (phân trang riêng bằng 'orders_page' để không đụng phân trang hoa hồng)
        // whereIn với mảng rỗng sẽ tự trả về paginator rỗng, tránh nhánh null gây cảnh báo foreach ở view.
        $memberOrders = CashbackHistory::with('user')
            ->whereIn('user_id', $memberIds)
            ->orderBy('created_at', 'desc')
            ->paginate(10, ['*'], 'orders_page')
            ->appends(['tab' => 'orders']);

        // Tổng số đơn hàng đã phát sinh từ mạng lưới thành viên (dùng cho nhãn tab)
        $memberOrdersCount = $memberOrders->total();

        // Lấy cấu hình tỷ lệ % hoa hồng để hiển thị cho người dùng biết
        $f1Rate = Setting::getVal('referral_f1_rate', 5);
        $f2Rate = Setting::getVal('referral_f2_rate', 2);

        // Lấy nội dung chính sách tiếp thị liên kết đã cấu hình trong admin settings để hiển thị ở giao diện
        $referralPolicy = Setting::getVal('referral_policy', '');

        // Trả về giao diện danh sách hoa hồng một phần (partial HTML) khi có yêu cầu AJAX để tối ưu tốc độ tải trang
        if ($request->ajax()) {
            return view('dashboard.partials.referral_commission_list', compact('commissions'))->render();
        }

        return view('dashboard.referrals', compact(
            'user',
            'referralLink',
            'f1Users',
            'f2Users',
            'f2Enabled',
            'totalCommission',
            'pendingCommission',
            'commissions',
            'memberOrders',
            'memberOrdersCount',
            'memberEarnings',
            'f1Ids',
            'f1Rate',
            'f2Rate',
            'referralPolicy'
        ));
    }
}
