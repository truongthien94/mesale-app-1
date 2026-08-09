<?php

namespace App\Http\Controllers;

use App\Models\CashbackHistory;
use App\Models\ActivityLog;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserDashboardController extends Controller
{


    /**
     * Hiển thị trang tổng quan Dashboard của người dùng.
     */
    public function index()
    {
        $user = Auth::user();

        // Lấy 5 lịch sử hoàn tiền gần nhất của user
        $recentCashback = CashbackHistory::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        // Lấy 5 thông báo chưa đọc gần nhất
        $recentNotifications = Notification::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        // Nạp dữ liệu thống kê biểu đồ mặc định (30 ngày) để hiển thị nhanh ngay khi tải trang không qua AJAX
        $chartRequest = new Request(['period' => '30days']);
        $savingsChartData = json_decode($this->getSavingsChartData($chartRequest)->getContent(), true);

        return view('dashboard.index', compact('user', 'recentCashback', 'recentNotifications', 'savingsChartData'));
    }

    /**
     * Lấy dữ liệu biểu đồ thống kê số tiền tiết kiệm được theo từng ngày.
     * Tiền tiết kiệm được tính từ các đơn hàng hoàn tiền thành công (status = 'approved').
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSavingsChartData(Request $request)
    {
        $user = Auth::user();
        $period = $request->get('period', '30days');

        // Xác định ngày bắt đầu tính toán dựa trên mốc thời gian người dùng yêu cầu (7 ngày, 30 ngày, 90 ngày, năm nay)
        $startDate = match ($period) {
            '7days' => now()->subDays(6)->startOfDay(),
            '90days' => now()->subDays(89)->startOfDay(),
            'this_year' => now()->startOfYear(),
            default => now()->subDays(29)->startOfDay(),
        };

        $endDate = now()->endOfDay();

        // Truy vấn tổng số tiền hoàn tiền nhóm theo ngày (Bao gồm đơn đã duyệt 'approved' và đơn chờ duyệt 'pending')
        $rawSavings = CashbackHistory::where('user_id', $user->id)
            ->whereIn('status', ['approved', 'pending'])
            ->whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('DATE(created_at) as date, SUM(cashback_amount) as total_savings')
            ->groupBy('date')
            ->pluck('total_savings', 'date')
            ->toArray();

        $labels = [];
        $data = [];
        $totalPeriodSavings = 0;

        // Tạo chuỗi ngày liên tục từ ngày bắt đầu đến hiện tại để đảm bảo đường biểu đồ không bị ngắt khoảng
        $current = clone $startDate;
        while ($current <= $endDate) {
            $dateStr = $current->format('Y-m-d');
            $labels[] = $current->format('d/m');

            $savings = isset($rawSavings[$dateStr]) ? (float) $rawSavings[$dateStr] : 0;
            // Chuẩn hoá về số nguyên đồng để biểu đồ tiết kiệm không hiển thị phần thập phân vô nghĩa
            $data[] = \App\Helpers\MoneyHelper::round($savings);
            $totalPeriodSavings += $savings;

            $current->addDay();
        }

        return response()->json([
            'period' => $period,
            'labels' => $labels,
            'data' => $data,
            'total_savings' => $totalPeriodSavings,
            'total_savings_formatted' => \App\Helpers\CurrencyHelper::format($totalPeriodSavings),
        ]);
    }

    /**
     * Lấy danh sách link hoàn tiền đã tạo của người dùng với tính năng phân trang (Load More / Ajax).
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUserCreatedLinks(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => __('Vui lòng đăng nhập.')], 401);
        }

        $offset = (int) $request->get('offset', 0);
        $limit = (int) $request->get('limit', 6);
        if ($limit <= 0) $limit = 6;
        if ($limit > 30) $limit = 30;

        $clicks = \App\Models\CashbackClick::where('user_id', $user->id)
            ->with(['cashbackHistory'])
            ->orderBy('created_at', 'desc')
            ->skip($offset)
            ->take($limit + 1)
            ->get();

        $platformNames = [
            'shopee' => \App\Models\Setting::getVal('shopee_platform_name', 'Shopee'),
            'tiktok' => \App\Models\Setting::getVal('tiktok_platform_name', 'TikTok Shop'),
            'lazada' => \App\Models\Setting::getVal('lazada_platform_name', 'Lazada'),
        ];

        $hasMore = $clicks->count() > $limit;
        $items = $clicks->slice(0, $limit)->map(function ($click) use ($platformNames) {
            $history = $click->cashbackHistory;
            $status = 'none';
            $statusLabel = __('Chưa ghi nhận đơn');

            if ($history) {
                $status = $history->status;
                if ($history->status === 'approved') {
                    $statusLabel = __('Đã ghi nhận (Đã duyệt)');
                } elseif ($history->status === 'pending') {
                    $statusLabel = __('Đã ghi nhận (Chờ duyệt)');
                } else {
                    $statusLabel = __('Đã ghi nhận (Từ chối)');
                }
            }

            $platformKey = strtolower($click->platform ?? 'shopee');

            return [
                'id' => $click->id,
                'trans_id' => $click->trans_id,
                'platform' => $platformKey,
                'platform_name' => $platformNames[$platformKey] ?? ucfirst($platformKey),
                'product_name' => $click->product_name ?: __('Sản phẩm Shopee'),
                'product_image' => $click->product_image,
                'cashback_amount_formatted' => \App\Helpers\CurrencyHelper::format($click->cashback_amount),
                'affiliate_url' => $click->affiliate_url,
                'created_at_human' => $click->created_at->diffForHumans(),
                'status' => $status,
                'status_label' => $statusLabel,
            ];
        });

        return response()->json([
            'status' => 'success',
            'data' => $items->values(),
            'has_more' => $hasMore,
        ]);
    }

    /**
     * Hiển thị danh sách đầy đủ lịch sử hoàn tiền của người dùng.
     *
     * Ngoài các đơn hàng đã được sàn ghi nhận (bảng cashback_histories), danh sách còn có thể
     * hiển thị gộp thêm các link hoàn tiền người dùng vừa tạo nhưng sàn chưa ghi nhận đơn về
     * (bảng cashback_clicks) nếu Quản trị viên bật cấu hình cashback_show_pending_clicks.
     * Việc này giúp khách hàng nhìn thấy ngay sản phẩm vừa tạo link, tránh hiểu nhầm hệ thống không ghi nhận.
     */
    public function cashbackHistory(Request $request)
    {
        $user = Auth::user();
        $perPage = 10;

        // Cấu hình cho phép hiển thị các link đã tạo nhưng sàn chưa ghi nhận đơn
        $showPendingClicks = \App\Models\Setting::getVal('cashback_show_pending_clicks', '1') === '1';

        // Xác định trạng thái đang lọc. Giá trị "unrecorded" là nhóm ảo dành cho link chưa được sàn ghi nhận
        $status = $request->get('status');
        if (!in_array($status, ['pending', 'approved', 'rejected', 'unrecorded'], true)) {
            $status = null;
        }
        // Nếu Admin đã tắt tính năng thì bỏ qua bộ lọc nhóm ảo để tránh trả về danh sách trống
        if ($status === 'unrecorded' && !$showPendingClicks) {
            $status = null;
        }

        // Hàm dùng chung áp các điều kiện lọc (nền tảng, từ khoá, khoảng thời gian) cho cả hai nguồn dữ liệu
        $applyCommonFilters = function ($builder, array $searchColumns) use ($request) {
            // Lọc theo nền tảng
            if (in_array($request->platform, ['shopee', 'tiktok', 'lazada'], true)) {
                $builder->where('platform', $request->platform);
            }

            // Tìm kiếm theo tên sản phẩm hoặc mã đơn hàng / mã đối soát
            if ($request->filled('search')) {
                $search = '%' . $request->search . '%';
                $builder->where(function ($q) use ($search, $searchColumns) {
                    foreach ($searchColumns as $column) {
                        $q->orWhere($column, 'like', $search);
                    }
                });
            }

            // Lọc theo khoảng thời gian tạo đơn (Từ ngày - Đến ngày)
            // Giúp người dùng dễ dàng tra cứu các đơn hoàn tiền phát sinh trong một khoảng thời gian cụ thể
            if ($request->filled('start_date')) {
                $builder->whereDate('created_at', '>=', $request->start_date);
            }
            if ($request->filled('end_date')) {
                $builder->whereDate('created_at', '<=', $request->end_date);
            }
        };

        // 1. Nguồn dữ liệu chính: các đơn hoàn tiền đã được sàn ghi nhận
        $historyQuery = null;
        if ($status !== 'unrecorded') {
            $historyQuery = CashbackHistory::where('user_id', $user->id);
            if ($status) {
                $historyQuery->where('status', $status);
            }
            $applyCommonFilters($historyQuery, ['product_name', 'order_id']);
        }

        // 2. Nguồn dữ liệu phụ: các link đã tạo nhưng chưa có đơn tương ứng đồng bộ về từ sàn
        $clickQuery = null;
        if ($showPendingClicks && ($status === null || $status === 'unrecorded')) {
            $clickQuery = \App\Models\CashbackClick::where('user_id', $user->id)
                ->whereDoesntHave('cashbackHistory');
            $applyCommonFilters($clickQuery, ['product_name', 'trans_id']);
        }

        if (!$clickQuery) {
            // Chỉ có đơn đã ghi nhận (Admin tắt tính năng hoặc đang lọc theo trạng thái đơn cụ thể):
            // dùng phân trang chuẩn của Eloquent cho nhẹ, không cần gộp thủ công
            $histories = $historyQuery->orderBy('created_at', 'desc')
                ->orderBy('id', 'desc')
                ->paginate($perPage)
                ->withQueryString();
        } elseif (!$historyQuery) {
            // Chỉ có nhóm link chưa được sàn ghi nhận (đang lọc riêng nhóm này)
            $histories = $clickQuery->orderBy('created_at', 'desc')
                ->orderBy('id', 'desc')
                ->paginate($perPage)
                ->withQueryString();

            // Đánh dấu cờ nhận biết để tầng giao diện phân biệt đây là link chưa được sàn ghi nhận đơn
            $histories->getCollection()->each(function ($click) {
                $click->setAttribute('is_pending_click', true);
            });
        } else {
            // Hiển thị gộp cả hai nguồn: phải tự phân trang vì dữ liệu nằm ở hai bảng khác nhau
            $total = (clone $historyQuery)->count() + (clone $clickQuery)->count();
            $lastPage = max(1, (int) ceil($total / $perPage));

            // Lấy dư dữ liệu tới hết trang hiện tại ở mỗi nguồn, đảm bảo sau khi gộp và sắp xếp
            // vẫn đủ bản ghi chính xác cho trang đang xem. Số bản ghi lấy ra luôn được chặn theo
            // trang cuối cùng thực tế để tránh truy vấn nặng khi bị truyền tham số page quá lớn.
            $page = max(1, (int) $request->get('page', 1));
            $take = min($page, $lastPage) * $perPage;

            $historyItems = $historyQuery->orderBy('created_at', 'desc')->orderBy('id', 'desc')->take($take)->get();

            $clickItems = $clickQuery->orderBy('created_at', 'desc')->orderBy('id', 'desc')->take($take)->get();
            $clickItems->each(function ($click) {
                $click->setAttribute('is_pending_click', true);
            });

            // Gộp hai nguồn và sắp xếp theo thời gian tạo mới nhất.
            // Trường hợp trùng thời điểm sẽ ưu tiên đơn đã ghi nhận rồi mới tới ID lớn hơn,
            // giúp thứ tự luôn ổn định giữa các trang (không bị lặp hoặc thiếu bản ghi).
            $merged = $historyItems->concat($clickItems)->sort(function ($a, $b) {
                $compare = $b->created_at->getTimestamp() <=> $a->created_at->getTimestamp();
                if ($compare !== 0) {
                    return $compare;
                }

                $aPending = (bool) ($a->is_pending_click ?? false);
                $bPending = (bool) ($b->is_pending_click ?? false);
                if ($aPending !== $bPending) {
                    return $aPending <=> $bPending;
                }

                return $b->id <=> $a->id;
            })->values();

            $histories = new \Illuminate\Pagination\LengthAwarePaginator(
                $merged->slice(($page - 1) * $perPage, $perPage)->values(),
                $total,
                $perPage,
                $page,
                [
                    'path' => $request->url(),
                    'query' => $request->query(),
                ]
            );
        }

        if ($request->ajax()) {
            return view('dashboard.partials.cashback_list', compact('histories'))->render();
        }

        return view('dashboard.cashback', compact('histories', 'showPendingClicks'));
    }

    /**
     * Xem lịch sử biến động số dư tài khoản (dòng tiền chi tiết).
     * Bổ sung tính năng tìm kiếm theo mô tả dòng tiền hoặc loại giao dịch.
     */
    public function balanceLogs(Request $request)
    {
        $user = Auth::user();
        \Log::info('balanceLogs Request parameters:', [
            'all' => $request->all(),
            'ajax' => $request->ajax(),
            'full_url' => $request->fullUrl()
        ]);
        
        $query = \App\Models\BalanceLog::where('user_id', $user->id);

        // Lọc theo loại giao dịch (type) nếu có
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // Thực hiện tìm kiếm theo mô tả giao dịch hoặc loại giao dịch nếu có từ khóa
        if ($request->has('search') && !empty($request->search)) {
            $search = '%' . $request->search . '%';
            $query->where(function($q) use ($search) {
                $q->where('description', 'like', $search)
                  ->orWhere('type', 'like', $search);
            });
        }

        // Sắp xếp nhật ký dòng tiền theo thời gian mới nhất (giảm dần).
        // Sử dụng thêm sắp xếp phụ theo ID giảm dần để ngăn ngừa hiện tượng xáo trộn 
        // đối với các giao dịch được tạo đồng thời trong cùng một giây.
        $logs = $query->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(15)
            ->withQueryString();

        if ($request->ajax()) {
            return view('dashboard.partials.balance_log_list', compact('logs'))->render();
        }

        return view('dashboard.balance_logs', compact('logs'));
    }

    /**
     * Xem nhật ký hoạt động của tài khoản.
     * Bổ sung tính năng tìm kiếm theo nội dung hoạt động, địa chỉ IP hoặc trình duyệt.
     */
    public function activityLogs(Request $request)
    {
        $user = Auth::user();
        $query = ActivityLog::where('user_id', $user->id);

        // Thực hiện tìm kiếm theo hoạt động, địa chỉ IP hoặc thiết bị/User Agent nếu có từ khóa
        if ($request->has('search') && !empty($request->search)) {
            $search = '%' . $request->search . '%';
            $query->where(function($q) use ($search) {
                $q->where('activity', 'like', $search)
                  ->orWhere('ip_address', 'like', $search)
                  ->orWhere('user_agent', 'like', $search);
            });
        }

        $logs = $query->orderBy('created_at', 'desc')
            ->paginate(15)
            ->withQueryString();

        if ($request->ajax()) {
            return view('dashboard.partials.activity_log_list', compact('logs'))->render();
        }

        return view('dashboard.logs', compact('logs'));
    }

    /**
     * Quản lý thông báo người dùng theo các tab thông báo chung và thông báo cá nhân.
     */
    public function notifications(Request $request)
    {
        $user = Auth::user();
        $tab = $request->query('tab', 'general'); // Mặc định hiển thị tab chung

        if (!in_array($tab, ['general', 'personal'])) {
            $tab = 'general';
        }

        // Bộ lọc trạng thái đọc: all (tất cả) hoặc unread (chưa đọc)
        $filter = $request->query('filter', 'all');
        if (!in_array($filter, ['all', 'unread'])) {
            $filter = 'all';
        }

        $notifications = Notification::where('user_id', $user->id)
            ->where('type', $tab)
            ->when($filter === 'unread', fn ($q) => $q->where('is_read', false))
            ->orderBy('created_at', 'desc')
            ->paginate(10)
            ->withQueryString();

        if ($request->ajax()) {
            return view('dashboard.partials.notification_list', compact('notifications', 'tab', 'filter'))->render();
        }

        // Đếm số thông báo chưa đọc theo từng nhóm để hiển thị huy hiệu trên tab
        $generalUnread = Notification::where('user_id', $user->id)
            ->where('type', 'general')->where('is_read', false)->count();
        $personalUnread = Notification::where('user_id', $user->id)
            ->where('type', 'personal')->where('is_read', false)->count();

        return view('dashboard.notifications', compact('notifications', 'tab', 'filter', 'generalUnread', 'personalUnread'));
    }

    /**
     * Đánh dấu toàn bộ thông báo là đã đọc bằng AJAX.
     */
    public function markAllAsRead()
    {
        Notification::where('user_id', Auth::id())
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return response()->json([
            'status' => 'success',
            'message' => 'Đã đánh dấu đọc tất cả thông báo!'
        ]);
    }

    /**
     * Đánh dấu một thông báo cụ thể là đã đọc bằng AJAX.
     * Chỉ cho phép sửa đổi thông báo thuộc về chính người dùng đang đăng nhập.
     */
    public function markAsRead($id)
    {
        $notification = Notification::where('user_id', Auth::id())
            ->where('id', $id)
            ->firstOrFail();

        if (!$notification->is_read) {
            $notification->is_read = true;
            $notification->save();
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Đã đánh dấu đã đọc thông báo thành công!'
        ]);
    }

    public function shortcuts()
    {
        // Kiểm tra trạng thái hoạt động của phím tắt từ cấu hình
        if (\App\Models\Setting::getVal('ios_shortcut_status', '0') === '0') {
            abort(404);
        }

        // Lấy thông tin tài khoản người dùng đang đăng nhập
        $user = Auth::user();

        // Trả về view hướng dẫn cài đặt phím tắt
        return view('dashboard.shortcuts', compact('user'));
    }
}
