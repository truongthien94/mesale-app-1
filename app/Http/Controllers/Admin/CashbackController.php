<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CashbackHistory;
use App\Services\ReferralCommissionService;
use App\Services\CashbackApprovalService;
use App\Services\ShopeeSyncService;
use App\Services\TikTokSyncService;
use App\Services\LazadaSyncService;
use App\Models\ShopeeAccount;
use App\Models\Setting;
use App\Models\User;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CashbackController extends Controller
{


    /**
     * Hiển thị danh sách lịch sử hoàn tiền Shopee cần duyệt (Search, Filter, Paginate).
     */
    public function index(Request $request)
    {
        // Lấy danh sách kèm quan hệ user và commissions để tính toán lợi nhuận và hiển thị không bị lỗi N+1
        // Thêm trường tính toán lợi nhuận (profit) để phục vụ việc sắp xếp theo lợi nhuận từ database
        $query = CashbackHistory::with(['user', 'commissions'])
            ->select('cashback_histories.*')
            ->selectRaw('(commission_amount - cashback_amount - COALESCE((SELECT SUM(amount) FROM referral_commissions WHERE referral_commissions.cashback_history_id = cashback_histories.id), 0)) as profit');

        // 1. Tìm theo mã đơn hàng hoặc mã giao dịch
        if ($request->filled('order_code')) {
            $code = '%' . $request->order_code . '%';
            $query->where(function($q) use ($code) {
                $q->where('order_id', 'like', $code)
                  ->orWhere('trans_id', 'like', $code);
            });
        }

        // 2. Tìm theo tên sản phẩm
        if ($request->filled('product_name')) {
            $query->where('product_name', 'like', '%' . $request->product_name . '%');
        }

        // 3. Tìm theo thành viên (Tên hoặc Email)
        if ($request->filled('user_search')) {
            $userSearch = '%' . $request->user_search . '%';
            $query->whereHas('user', function($userQuery) use ($userSearch) {
                $userQuery->where('email', 'like', $userSearch)
                          ->orWhere('name', 'like', $userSearch);
            });
        }

        // 4. Lọc theo trạng thái duyệt đơn
        if ($request->filled('status') && in_array($request->status, ['pending', 'approved', 'rejected'])) {
            $query->where('status', $request->status);
        }

        // 5. Lọc theo nền tảng (platform)
        if ($request->filled('platform')) {
            $query->where('platform', $request->platform);
        }

        // 6. Lọc theo khoảng thời gian tạo đơn (Từ ngày - Đến ngày)
        // Việc lọc này giúp quản trị viên dễ dàng tra cứu các đơn hoàn tiền phát sinh trong một khoảng thời gian cụ thể
        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        // Tạo query đếm số lượng cho từng trạng thái phục vụ thanh điều hướng lọc nhanh (tabs).
        // Lọc theo các điều kiện tìm kiếm hiện tại (ngoại trừ lọc theo status) để con số hiển thị chính xác theo ngữ cảnh tìm kiếm.
        $countQuery = CashbackHistory::query();
        if ($request->filled('order_code')) {
            $code = '%' . $request->order_code . '%';
            $countQuery->where(function($q) use ($code) {
                $q->where('order_id', 'like', $code)
                  ->orWhere('trans_id', 'like', $code);
            });
        }
        if ($request->filled('product_name')) {
            $countQuery->where('product_name', 'like', '%' . $request->product_name . '%');
        }
        if ($request->filled('user_search')) {
            $userSearch = '%' . $request->user_search . '%';
            $countQuery->whereHas('user', function($userQuery) use ($userSearch) {
                $userQuery->where('email', 'like', $userSearch)
                          ->orWhere('name', 'like', $userSearch);
            });
        }
        if ($request->filled('platform')) {
            $countQuery->where('platform', $request->platform);
        }

        // Đồng bộ bộ lọc thời gian sang cả truy vấn đếm để các con số trên tab khớp với bộ lọc
        if ($request->filled('start_date')) {
            $countQuery->whereDate('created_at', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $countQuery->whereDate('created_at', '<=', $request->end_date);
        }

        // Thực hiện nhóm và đếm số lượng đơn hàng của từng trạng thái trong CSDL
        $counts = $countQuery->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();

        $statusCounts = [
            'all' => array_sum($counts),
            'pending' => $counts['pending'] ?? 0,
            'approved' => $counts['approved'] ?? 0,
            'rejected' => $counts['rejected'] ?? 0,
        ];

        // Lấy số dòng hiển thị (limit), mặc định là 15, giới hạn tối đa 500 dòng để tránh quá tải
        $limit = $request->integer('limit', 15);
        if ($limit < 1 || $limit > 500) {
            $limit = 15;
        }

        // Lấy cấu hình sắp xếp động từ request để cho phép sắp xếp theo giá trị đơn hàng, hoa hồng, hoàn tiền, ngày tạo, cập nhật
        // Điều này giúp quản trị viên dễ dàng lọc ra những đơn hàng hoàn tiền có giá trị lớn hoặc vừa cập nhật
        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');

        // Whitelist các cột được phép sắp xếp để ngăn chặn triệt để lỗ hổng SQL Injection qua tham số sort_by
        // Thêm 'profit' vào danh sách cho phép sắp xếp theo lợi nhuận thực tế
        $allowedSorts = ['created_at', 'updated_at', 'original_price', 'commission_amount', 'cashback_amount', 'profit'];
        if (!in_array($sortBy, $allowedSorts)) {
            $sortBy = 'created_at';
        }
        if (!in_array($sortOrder, ['asc', 'desc'])) {
            $sortOrder = 'desc';
        }

        $histories = $query->orderBy($sortBy, $sortOrder)->paginate($limit)->withQueryString();

        // Kiểm tra xem yêu cầu có phải là AJAX để trả về kết quả JSON dạng partial render.
        // Điều này giúp giao diện admin chuyển tiếp mượt mà như một trang SPA.
        if ($request->ajax()) {
            return response()->json([
                'html' => view('admin.cashback.partials.table', compact('histories'))->render(),
                'statusCounts' => $statusCounts,
                'selectableCashbackIds' => $histories->pluck('id')->toArray()
            ]);
        }

        return view('admin.cashback.index', compact('histories', 'statusCounts'));
    }

    /**
     * Phê duyệt đơn hoàn tiền và tính toán hoa hồng giới thiệu 2 tầng F1/F2.
     * Giải thích: Chuyển toàn bộ logic xử lý qua CashbackApprovalService để đảm bảo nhất quán dữ liệu.
     */
    public function approve(Request $request, $id)
    {
        try {
            $cashback = CashbackHistory::findOrFail($id);

            $options = [
                'source' => 'manual',
            ];

            if ($request->has('cashback_amount')) {
                $options['cashback_amount'] = (float)$request->input('cashback_amount');
            }

            $success = CashbackApprovalService::approve($cashback, 0, 0, $options);

            if ($success) {
                return back()->with('success', 'Đã duyệt thành công đơn hàng hoàn tiền và chia sẻ hoa hồng liên kết.');
            }

            return back()->with('error', 'Đơn hàng này đã được xử lý từ trước.');
        } catch (\Exception $e) {
            \Log::error("Approve cashback failed in controller: " . $e->getMessage());
            return back()->with('error', 'Có lỗi hệ thống xảy ra trong quá trình duyệt đơn hoàn tiền. Vui lòng kiểm tra file log.');
        }
    }

    /**
     * Từ chối đơn hoàn tiền.
     * Giải thích: Gọi CashbackApprovalService::reject để xử lý từ chối đơn hàng một cách an toàn.
     */
    public function reject(Request $request, $id)
    {
        $request->validate([
            'rejected_reason' => 'required|string|max:255'
        ], [
            'rejected_reason.required' => 'Vui lòng điền lý do từ chối đơn hàng.'
        ]);

        try {
            $cashback = CashbackHistory::findOrFail($id);
            $success = CashbackApprovalService::reject($cashback, $request->rejected_reason, [
                'source' => 'manual'
            ]);

            if ($success) {
                return back()->with('success', 'Đã từ chối phê duyệt đơn hoàn tiền.');
            }

            return back()->with('error', 'Đơn hàng này đã được xử lý từ trước.');
        } catch (\Exception $e) {
            \Log::error("Reject cashback failed in controller: " . $e->getMessage());
            return back()->with('error', 'Có lỗi hệ thống xảy ra khi từ chối duyệt đơn hoàn tiền.');
        }
    }

    /**
     * API: Lấy danh sách lịch sử click và thông tin hoa hồng MLM của đơn hàng hoàn tiền.
     * Trả về click logs từ short link tương ứng + commissions (F1/F2) kèm thông tin referrer.
     * Giới hạn 50 click logs gần nhất để tránh response quá nặng.
     */
    public function getClickLogs($id)
    {
        $cashback = CashbackHistory::findOrFail($id);

        // === PHẦN 1: LỊCH SỬ CLICK LOGS ===
        $logs = [];
        $totalLogs = 0;
        $totalClicks = 0;

        // Trích xuất mã rút gọn (code) nằm ở cuối đường dẫn affiliate
        if ($cashback->affiliate_url) {
            $code = basename($cashback->affiliate_url);
            $shortLink = \App\Models\ShortLink::where('code', $code)->first();

            if ($shortLink) {
                // Lấy tối đa 50 click logs gần nhất, sắp xếp mới nhất trước
                $logs = \App\Models\ClickLog::where('short_link_id', $shortLink->id)
                    ->orderByDesc('created_at')
                    ->limit(50)
                    ->get(['id', 'ip_address', 'device', 'location', 'user_agent', 'created_at']);

                $totalLogs = \App\Models\ClickLog::where('short_link_id', $shortLink->id)->count();
                $totalClicks = $shortLink->clicks;
            }
        }

        // === PHẦN 2: HOA HỒNG MLM PHÁT SINH TỪ ĐƠN HÀNG NÀY ===
        // Chỉ trả về thông tin cần thiết: tên, email referrer, tầng, số tiền, trạng thái
        $commissions = \App\Models\ReferralCommission::where('cashback_history_id', $cashback->id)
            ->with(['referrer:id,name,email,referral_code'])
            ->orderBy('level')
            ->get()
            ->map(function ($comm) {
                return [
                    'id' => $comm->id,
                    'level' => $comm->level,
                    'amount' => $comm->amount,
                    'status' => $comm->status,
                    'created_at' => $comm->created_at,
                    'referrer_name' => $comm->referrer->name ?? 'N/A',
                    'referrer_email' => $comm->referrer->email ?? 'N/A',
                    'referrer_code' => $comm->referrer->referral_code ?? 'N/A',
                ];
            });

        return response()->json([
            'logs' => $logs,
            'total' => $totalLogs,
            'total_clicks' => $totalClicks,
            'commissions' => $commissions,
        ]);
    }

    /**
     * Phê duyệt hàng loạt đơn hoàn tiền và phân chia hoa hồng giới thiệu 2 tầng.
     * Chống Race Condition bằng cách chạy trong Database Transaction và lockForUpdate.
     */
    public function bulkApprove(Request $request)
    {
        $request->validate([
            'ids' => 'required|string',
        ]);

        $ids = explode(',', $request->ids);
        $ids = array_filter(array_map('intval', $ids));

        if (empty($ids)) {
            return response()->json(['status' => 'error', 'message' => 'Không có đơn hàng nào được chọn.']);
        }

        try {
            $count = 0;
            // Lặp qua danh sách ID đơn hàng và duyệt bằng CashbackApprovalService để đảm bảo đầy đủ các nghiệp vụ (gửi mail, Telegram, MLM F1/F2) và chống Race Condition
            foreach ($ids as $id) {
                $cashback = CashbackHistory::where('id', $id)->where('status', 'pending')->first();
                if ($cashback) {
                    $success = CashbackApprovalService::approve($cashback, 0, 0, [
                        'source' => 'manual'
                    ]);
                    if ($success) {
                        $count++;
                    }
                }
            }

            if ($count === 0) {
                return response()->json(['status' => 'error', 'message' => 'Không tìm thấy đơn hàng nào ở trạng thái chờ duyệt để xử lý.']);
            }

            return response()->json(['status' => 'success', 'message' => "Đã duyệt thành công {$count} đơn hoàn tiền được chọn."]);
        } catch (\Exception $e) {
            \Log::error("Bulk approve cashback failed: " . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Có lỗi hệ thống xảy ra: ' . $e->getMessage()]);
        }
    }

    /**
     * Từ chối hàng loạt đơn hoàn tiền.
     * Chống Race Condition bằng cách chạy trong Database Transaction và lockForUpdate.
     */
    public function bulkReject(Request $request)
    {
        $request->validate([
            'ids' => 'required|string',
            'rejected_reason' => 'required|string|max:255'
        ], [
            'rejected_reason.required' => 'Vui lòng điền lý do từ chối hàng loạt.'
        ]);

        $ids = explode(',', $request->ids);
        $ids = array_filter(array_map('intval', $ids));

        if (empty($ids)) {
            return response()->json(['status' => 'error', 'message' => 'Không có đơn hàng nào được chọn.']);
        }

        try {
            $count = 0;
            // Lặp qua danh sách ID đơn hàng và từ chối bằng CashbackApprovalService để đảm bảo đầy đủ các nghiệp vụ (gửi thông báo động theo sàn, ghi log) và chống Race Condition
            foreach ($ids as $id) {
                $cashback = CashbackHistory::where('id', $id)->where('status', 'pending')->first();
                if ($cashback) {
                    $success = CashbackApprovalService::reject($cashback, $request->rejected_reason, [
                        'source' => 'manual'
                    ]);
                    if ($success) {
                        $count++;
                    }
                }
            }

            if ($count === 0) {
                return response()->json(['status' => 'error', 'message' => 'Không tìm thấy đơn hàng nào ở trạng thái chờ duyệt để xử lý.']);
            }

            return response()->json(['status' => 'success', 'message' => "Đã từ chối phê duyệt {$count} đơn hoàn tiền."]);
        } catch (\Exception $e) {
            \Log::error("Bulk reject cashback failed: " . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Có lỗi hệ thống xảy ra: ' . $e->getMessage()]);
        }
    }

    /**
     * Xóa hàng loạt đơn hoàn tiền khỏi hệ thống.
     * Chống Race Condition bằng cách chạy trong Database Transaction và lockForUpdate.
     */
    public function bulkDestroy(Request $request)
    {
        $request->validate([
            'ids' => 'required|string',
            'confirm_text' => 'required|string',
        ]);

        // Dùng mb_strtoupper (hỗ trợ Unicode) để cụm từ tiếng Việt có dấu viết thường vẫn được chấp nhận,
        // vì strtoupper() thuần chỉ xử lý ký tự ASCII nên "xóa hàng loạt" sẽ không bao giờ khớp được.
        if (mb_strtoupper($request->confirm_text, 'UTF-8') !== 'XÓA HÀNG LOẠT') {
            return response()->json(['status' => 'error', 'message' => __('Từ khóa xác nhận không chính xác.')]);
        }

        $ids = explode(',', $request->ids);
        $ids = array_filter(array_map('intval', $ids));

        if (empty($ids)) {
            return response()->json(['status' => 'error', 'message' => 'Không có đơn hàng nào được chọn.']);
        }

        try {
            DB::transaction(function () use ($ids) {
                // Khóa các đơn hàng trước khi xóa để tránh Race Condition
                $cashbacks = CashbackHistory::whereIn('id', $ids)->lockForUpdate()->get();

                foreach ($cashbacks as $cashback) {
                    $orderId = $cashback->order_id;
                    
                    // Xóa các hoa hồng MLM liên kết của đơn hàng này
                    DB::table('referral_commissions')->where('cashback_history_id', $cashback->id)->delete();

                    // Thực hiện xóa đơn hàng
                    $cashback->delete();

                    ActivityLog::log("Xóa đơn hoàn tiền khỏi hệ thống (Hành động hàng loạt): Đơn {$orderId}", auth()->id());
                }
            });

            return response()->json(['status' => 'success', 'message' => 'Đã xóa hàng loạt các đơn hoàn tiền được chọn thành công!']);
        } catch (\Exception $e) {
            \Log::error("Bulk destroy cashback failed: " . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Có lỗi xảy ra trong quá trình xóa hàng loạt đơn hoàn tiền: ' . $e->getMessage()]);
        }
    }

    /**
     * API thống kê đơn hoàn tiền theo thời gian (tuần/tháng/năm).
     * Trả về dữ liệu JSON gồm: số đơn theo trạng thái, tổng tiền hoàn, hoa hồng Shopee và lợi nhuận hệ thống.
     * Tối ưu hiệu năng bằng cách dùng 1 raw query duy nhất group by ngày/tháng thay vì nhiều query riêng lẻ.
     */
    public function cashbackStats(Request $request)
    {
        // Whitelist khoảng thời gian hợp lệ để tránh tham số giả mạo
        $period = $request->input('period', 'week');
        if (!in_array($period, ['week', 'month', 'year'])) {
            $period = 'week';
        }

        // Xác định khoảng thời gian và cách group by dựa trên period
        if ($period === 'week') {
            $startDate = now()->subDays(6)->startOfDay();
            $groupFormat = '%Y-%m-%d';
            $labelFormat = 'd/m';
            $days = 7;
        } elseif ($period === 'month') {
            $startDate = now()->subDays(29)->startOfDay();
            $groupFormat = '%Y-%m-%d';
            $labelFormat = 'd/m';
            $days = 30;
        } else {
            $startDate = now()->subMonths(11)->startOfMonth();
            $groupFormat = '%Y-%m';
            $labelFormat = null; // Xử lý riêng cho tháng
            $days = 12;
        }

        // Query gom nhóm: đếm số đơn + tính tổng tiền cho mỗi ngày/tháng và trạng thái
        $rawData = CashbackHistory::where('created_at', '>=', $startDate)
            ->select(
                DB::raw("DATE_FORMAT(created_at, '{$groupFormat}') as date_key"),
                'status',
                DB::raw('COUNT(*) as order_count'),
                DB::raw('COALESCE(SUM(cashback_amount), 0) as cashback_sum'),
                DB::raw('COALESCE(SUM(commission_amount), 0) as commission_sum')
            )
            ->groupBy('date_key', 'status')
            ->get();

        // Tạo lookup map từ kết quả raw query để truy cập nhanh O(1)
        $dataMap = [];
        foreach ($rawData as $row) {
            $dataMap[$row->date_key][$row->status] = [
                'count' => (int)$row->order_count,
                'cashback' => (float)$row->cashback_sum,
                'commission' => (float)$row->commission_sum,
            ];
        }

        // Xây dựng mảng labels + dữ liệu cho từng mốc thời gian
        $labels = [];
        $pending = [];
        $approved = [];
        $rejected = [];
        $cashbackData = [];
        $cashbackPendingData = [];
        $profitData = [];
        $profitPendingData = [];
        // Mảng lưu doanh thu hệ thống (tổng hoa hồng Shopee trả trước khi chia cashback) phục vụ tab Doanh thu mới
        $commissionData = [];
        $commissionPendingData = [];

        if ($period === 'year') {
            // Duyệt 12 tháng gần nhất
            for ($i = 11; $i >= 0; $i--) {
                $date = now()->subMonths($i);
                $key = $date->format('Y-m');
                $labels[] = __('Tháng') . ' ' . $date->format('m/Y');

                $p = $dataMap[$key]['pending'] ?? ['count' => 0, 'cashback' => 0, 'commission' => 0];
                $a = $dataMap[$key]['approved'] ?? ['count' => 0, 'cashback' => 0, 'commission' => 0];
                $r = $dataMap[$key]['rejected'] ?? ['count' => 0, 'cashback' => 0, 'commission' => 0];

                $pending[] = $p['count'];
                $approved[] = $a['count'];
                $rejected[] = $r['count'];
                $cashbackData[] = round($a['cashback']);
                $cashbackPendingData[] = round($p['cashback']);
                $profitData[] = round($a['commission'] - $a['cashback']);
                $profitPendingData[] = round($p['commission'] - $p['cashback']);
                // Ghi nhận tổng hoa hồng Shopee trả (doanh thu) cho cả hai trạng thái đã duyệt và chờ duyệt
                $commissionData[] = round($a['commission']);
                $commissionPendingData[] = round($p['commission']);
            }
        } else {
            // Duyệt từng ngày (7 hoặc 30 ngày)
            $totalDays = $period === 'week' ? 6 : 29;
            for ($i = $totalDays; $i >= 0; $i--) {
                $date = now()->subDays($i);
                $key = $date->format('Y-m-d');
                $labels[] = $date->format($labelFormat);

                $p = $dataMap[$key]['pending'] ?? ['count' => 0, 'cashback' => 0, 'commission' => 0];
                $a = $dataMap[$key]['approved'] ?? ['count' => 0, 'cashback' => 0, 'commission' => 0];
                $r = $dataMap[$key]['rejected'] ?? ['count' => 0, 'cashback' => 0, 'commission' => 0];

                $pending[] = $p['count'];
                $approved[] = $a['count'];
                $rejected[] = $r['count'];
                $cashbackData[] = round($a['cashback']);
                $cashbackPendingData[] = round($p['cashback']);
                $profitData[] = round($a['commission'] - $a['cashback']);
                $profitPendingData[] = round($p['commission'] - $p['cashback']);
                // Ghi nhận tổng hoa hồng Shopee trả (doanh thu) cho cả hai trạng thái đã duyệt và chờ duyệt
                $commissionData[] = round($a['commission']);
                $commissionPendingData[] = round($p['commission']);
            }
        }

        // Tính tổng cho khoảng thời gian đang xem
        $totalPending = array_sum($pending);
        $totalApproved = array_sum($approved);
        $totalRejected = array_sum($rejected);
        $totalCashback = array_sum($cashbackData);
        $totalCashbackPending = array_sum($cashbackPendingData);
        $totalProfit = array_sum($profitData);
        $totalProfitPending = array_sum($profitPendingData);
        // Tính tổng doanh thu của khoảng thời gian phục vụ hiển thị card tóm tắt trên giao diện thống kê
        $totalCommission = array_sum($commissionData);
        $totalCommissionPending = array_sum($commissionPendingData);

        return response()->json([
            'success' => true,
            'labels' => $labels,
            'pending' => $pending,
            'approved' => $approved,
            'rejected' => $rejected,
            'cashback_data' => $cashbackData,
            'cashback_pending_data' => $cashbackPendingData,
            'profit_data' => $profitData,
            'profit_pending_data' => $profitPendingData,
            'commission_data' => $commissionData,
            'commission_pending_data' => $commissionPendingData,
            'totals' => [
                'all' => $totalPending + $totalApproved + $totalRejected,
                'pending' => $totalPending,
                'approved' => $totalApproved,
                'rejected' => $totalRejected,
                'cashback' => $totalCashback,
                'cashback_pending' => $totalCashbackPending,
                'profit' => $totalProfit,
                'profit_pending' => $totalProfitPending,
                'commission' => $totalCommission,
                'commission_pending' => $totalCommissionPending,
            ],
            'period' => $period,
        ]);
    }

    /**
     * Xóa đơn hàng hoàn tiền đơn lẻ khỏi hệ thống.
     * Giải thích nghiệp vụ:
     * - Chạy trong Database Transaction để đảm bảo tính toàn vẹn dữ liệu.
     * - Sử dụng lockForUpdate() để khóa bản ghi đơn hàng, chống hiện tượng tranh chấp dữ liệu (Race Condition).
     * - Tiến hành xóa sạch các hoa hồng MLM liên kết của đơn hàng này để tránh lỗi mâu thuẫn dữ liệu hoặc rác CSDL.
     * - Ghi lại lịch sử hoạt động (Activity Log) cho admin.
     */
    public function destroy(Request $request, $id)
    {
        try {
            DB::transaction(function () use ($id) {
                // Khóa bản ghi đơn hàng trước khi thực hiện xóa
                $cashback = CashbackHistory::where('id', $id)->lockForUpdate()->firstOrFail();
                $orderId = $cashback->order_id;
                
                // Xóa các hoa hồng giới thiệu MLM F1/F2 liên kết với đơn hoàn tiền này
                DB::table('referral_commissions')->where('cashback_history_id', $cashback->id)->delete();

                // Thực hiện xóa đơn hoàn tiền
                $cashback->delete();

                // Ghi nhận lịch sử hoạt động của admin
                ActivityLog::log("Xóa đơn hoàn tiền khỏi hệ thống: Đơn {$orderId}", auth()->id());
            });

            return back()->with('success', 'Đã xóa đơn hàng hoàn tiền thành công!');
        } catch (\Exception $e) {
            \Log::error("Destroy cashback failed in controller: " . $e->getMessage());
            return back()->with('error', 'Có lỗi xảy ra trong quá trình xóa đơn hàng hoàn tiền: ' . $e->getMessage());
        }
    }

    /**
     * Đồng bộ tất cả tài khoản Shopee Affiliate + TikTok Shop + Lazada và trả về kết quả gộp dưới dạng JSON.
     * Chỉ đồng bộ những sàn đang được BẬT trong cấu hình hệ thống, các sàn đã tắt sẽ bị bỏ qua hoàn toàn.
     * Được gọi qua AJAX từ nút "Sync API" trên trang quản lý đơn hoàn tiền.
     */
    public function syncApi(Request $request)
    {
        if (config('app.demo')) {
            return response()->json([
                'success' => false,
                'error' => __('Tính năng đồng bộ API bị khóa ở chế độ Demo.')
            ], 403);
        }

        $allDetails = [];
        $totalApproved = 0;
        $totalRejected = 0;
        $totalIgnored = 0;
        $totalAlreadyProcessed = 0;
        $errors = [];
        $skippedPlatforms = [];

        // Đọc trạng thái bật/tắt của từng sàn và trạng thái kết nối API tương ứng.
        // Chỉ đồng bộ những sàn đang BẬT cả 2 công tắc, tránh gọi API tới sàn mà Admin đã tắt.
        $shopeeEnabled = Setting::getVal('shopee_status', '1') === '1'
            && Setting::getVal('apishopee_status', '1') === '1';
        $tiktokEnabled = Setting::getVal('tiktok_status', '1') === '1'
            && Setting::getVal('apitiktok_status', '1') === '1';
        $lazadaEnabled = Setting::getVal('lazada_status', '0') === '1'
            && Setting::getVal('apilazada_status', '1') === '1';

        // 1. Đồng bộ tất cả tài khoản Shopee Affiliate đang hoạt động (nếu sàn Shopee đang bật)
        if ($shopeeEnabled) {
            $shopeeAccounts = ShopeeAccount::where('status', 'active')->get();
            $shopeeSyncService = new ShopeeSyncService();

            foreach ($shopeeAccounts as $account) {
                $result = $shopeeSyncService->syncAccount($account, 30);

                if ($result['success']) {
                    $totalApproved += $result['approved'];
                    $totalRejected += $result['rejected'];
                    foreach ($result['details'] ?? [] as $detail) {
                        $detail['platform'] = 'shopee';
                        $detail['source_name'] = $account->name;
                        $allDetails[] = $detail;
                    }
                    ActivityLog::log("Sync API cashback: Shopee '{$account->name}' - Duyệt {$result['approved']}, Từ chối {$result['rejected']}.", auth()->id());
                } else {
                    $errors[] = 'Shopee (' . $account->name . '): ' . ($account->error_message ?? $result['error'] ?? 'Lỗi kết nối');
                }
            }
        } else {
            $skippedPlatforms[] = Setting::getVal('shopee_platform_name', 'Shopee');
        }

        // 2. Đồng bộ TikTok Shop qua RioHub API (nếu sàn TikTok Shop đang bật)
        if ($tiktokEnabled) {
            $tiktokSyncService = new TikTokSyncService();
            $tiktokResult = $tiktokSyncService->syncOrders(30);

            if ($tiktokResult['success']) {
                $totalApproved += $tiktokResult['approved'];
                $totalRejected += $tiktokResult['rejected'];
                foreach ($tiktokResult['details'] ?? [] as $detail) {
                    $detail['platform'] = 'tiktok';
                    $detail['source_name'] = 'TikTok Shop';
                    $allDetails[] = $detail;
                }
                ActivityLog::log("Sync API cashback: TikTok Shop - Duyệt {$tiktokResult['approved']}, Từ chối {$tiktokResult['rejected']}.", auth()->id());
            } else {
                $errors[] = 'TikTok Shop: ' . ($tiktokResult['error'] ?? 'Lỗi kết nối RioHub API');
            }
        } else {
            $skippedPlatforms[] = Setting::getVal('tiktok_platform_name', 'TikTok Shop');
        }

        // 3. Đồng bộ Lazada qua Lazada Affiliate API (nếu sàn Lazada đang bật)
        if ($lazadaEnabled) {
            $lazadaSyncService = new LazadaSyncService();
            $lazadaResult = $lazadaSyncService->syncOrders(30);

            if ($lazadaResult['success']) {
                $totalApproved += $lazadaResult['approved'];
                $totalRejected += $lazadaResult['rejected'];
                foreach ($lazadaResult['details'] ?? [] as $detail) {
                    $detail['platform'] = 'lazada';
                    $detail['source_name'] = 'Lazada';
                    $allDetails[] = $detail;
                }
                ActivityLog::log("Sync API cashback: Lazada - Duyệt {$lazadaResult['approved']}, Từ chối {$lazadaResult['rejected']}.", auth()->id());
            } else {
                $errors[] = 'Lazada: ' . ($lazadaResult['error'] ?? 'Lỗi kết nối Lazada Affiliate API');
            }
        } else {
            $skippedPlatforms[] = Setting::getVal('lazada_platform_name', 'Lazada');
        }

        // Nếu tất cả các sàn đều đang tắt thì báo rõ cho Admin thay vì trả về kết quả rỗng khó hiểu
        if (!$shopeeEnabled && !$tiktokEnabled && !$lazadaEnabled) {
            return response()->json([
                'success' => false,
                'error' => __('Tất cả các sàn đều đang được TẮT trong cấu hình. Vui lòng bật lại sàn cần đồng bộ tại Cài đặt hệ thống.')
            ], 400);
        }

        // 3. Tổng hợp số liệu
        foreach ($allDetails as $detail) {
            $sys = $detail['status_system'] ?? '';
            if ($sys === 'ignored') {
                $totalIgnored++;
            } elseif ($sys === 'already_processed' || $sys === 'recalled') {
                $totalAlreadyProcessed++;
            }
        }

        // Sắp xếp theo thời gian mua hàng mới nhất lên đầu
        usort($allDetails, fn($a, $b) => strcmp($b['purchase_time'] ?? '', $a['purchase_time'] ?? ''));

        $hasAnySuccess = $totalApproved > 0 || $totalRejected > 0 || count($allDetails) > 0;

        if (!$hasAnySuccess && !empty($errors) && empty($allDetails)) {
            return response()->json([
                'success' => false,
                'error' => implode('; ', $errors)
            ], 400);
        }

        return response()->json([
            'success' => true,
            'errors' => $errors,
            // Danh sách các sàn bị bỏ qua do Admin đã tắt, hiển thị dạng ghi chú trên modal kết quả
            'skipped_platforms' => $skippedPlatforms,
            'summary' => [
                'total_scanned' => count($allDetails),
                'approved' => $totalApproved,
                'rejected' => $totalRejected,
                'already_processed' => $totalAlreadyProcessed,
                'ignored' => $totalIgnored,
            ],
            'details' => $allDetails
        ]);
    }
}
