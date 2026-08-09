<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Withdrawal;
use App\Models\User;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WithdrawalController extends Controller
{


    /**
     * Danh sách yêu cầu rút tiền (Search, Filter, Paginate).
     */
    public function index(Request $request)
    {
        $query = Withdrawal::with('user');

        // Tìm kiếm theo tên hoặc email hoặc số điện thoại của user
        if ($request->has('user_search') && !empty($request->user_search)) {
            $search = '%' . $request->user_search . '%';
            $query->whereHas('user', function($userQuery) use ($search) {
                $userQuery->where('email', 'like', $search)
                          ->orWhere('name', 'like', $search)
                          ->orWhere('phone', 'like', $search);
            });
        }

        // Tìm kiếm theo số tài khoản hoặc tên tài khoản nhận tiền
        if ($request->has('account_search') && !empty($request->account_search)) {
            $search = '%' . $request->account_search . '%';
            $query->where(function($q) use ($search) {
                $q->where('account_number', 'like', $search)
                  ->orWhere('account_name', 'like', $search);
            });
        }

        // Tìm kiếm theo tên ngân hàng / ví nhận
        if ($request->has('bank_name') && !empty($request->bank_name)) {
            $query->where('bank_name', 'like', '%' . $request->bank_name . '%');
        }

        // Lọc theo khoảng thời gian tạo yêu cầu rút tiền (Date Range)
        if ($request->has('start_date') && !empty($request->start_date)) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }
        if ($request->has('end_date') && !empty($request->end_date)) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        // Tạo bản clone query để đếm số lượng theo trạng thái mà vẫn giữ nguyên các điều kiện lọc (Search & Date Range) trước khi áp dụng status
        $countQuery = clone $query;

        // Lọc theo trạng thái rút tiền
        $status = $request->input('status');
        if (in_array($status, ['pending', 'approved', 'rejected'])) {
            $query->where('status', $status);
        }

        // Cấu hình số dòng hiển thị động (limit), tối đa 500 dòng
        $limit = $request->integer('limit', 15);
        if ($limit < 1 || $limit > 500) {
            $limit = 15;
        }

        // Hỗ trợ sắp xếp động theo các cột số tiền rút, ngày tạo, cập nhật
        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');

        // Whitelist các cột được phép sắp xếp để bảo mật hệ thống
        $allowedSorts = ['created_at', 'updated_at', 'amount'];
        if (!in_array($sortBy, $allowedSorts)) {
            $sortBy = 'created_at';
        }
        if (!in_array($sortOrder, ['asc', 'desc'])) {
            $sortOrder = 'desc';
        }

        $withdrawals = $query->orderBy($sortBy, $sortOrder)->paginate($limit)->withQueryString();

        // Đếm số lượng theo từng trạng thái dựa trên bộ lọc đang hoạt động để hiển thị số lượng chính xác trên các tab ngoài view
        $statusCounts = [
            'all' => (clone $countQuery)->count(),
            'pending' => (clone $countQuery)->where('status', 'pending')->count(),
            'approved' => (clone $countQuery)->where('status', 'approved')->count(),
            'rejected' => (clone $countQuery)->where('status', 'rejected')->count(),
        ];

        // Thống kê số tiền rút thành công (approved) theo các mốc thời gian để Admin dễ dàng kiểm soát dòng tiền chi trả
        $todayWithdrawn = Withdrawal::where('status', 'approved')->whereDate('created_at', today())->sum('amount');
        $weekWithdrawn = Withdrawal::where('status', 'approved')->where('created_at', '>=', now()->startOfWeek())->sum('amount');
        $monthWithdrawn = Withdrawal::where('status', 'approved')->where('created_at', '>=', now()->startOfMonth())->sum('amount');
        $totalWithdrawn = Withdrawal::where('status', 'approved')->sum('amount');

        // Dữ liệu phục vụ tính năng tích chọn nhanh để duyệt chi / xóa hàng loạt ngoài giao diện
        // - selectableWithdrawalIds: toàn bộ ID đang hiển thị trên trang (dùng cho nút chọn tất cả)
        // - pendingWithdrawalIds: chỉ các lệnh đang chờ duyệt (chỉ nhóm này mới được duyệt chi hàng loạt)
        // - pendingWithdrawalAmounts: số tiền thực nhận theo từng ID chờ duyệt để tính tổng tiền thực chi trên modal xác nhận
        $selectableWithdrawalIds = $withdrawals->pluck('id')->toArray();
        $pendingWithdrawals = $withdrawals->where('status', 'pending');
        $pendingWithdrawalIds = $pendingWithdrawals->pluck('id')->values()->toArray();
        $pendingWithdrawalAmounts = $pendingWithdrawals->mapWithKeys(function ($item) {
            return [$item->id => (float)($item->real_amount ?? $item->amount)];
        })->toArray();

        // Nếu là yêu cầu AJAX -> Trả về HTML partial table và các dữ liệu đếm đi kèm để cập nhật UI mượt mà
        if ($request->ajax()) {
            return response()->json([
                'html' => view('admin.withdrawals.partials.table', compact('withdrawals'))->render(),
                'statusCounts' => $statusCounts,
                'selectableWithdrawalIds' => $selectableWithdrawalIds,
                'pendingWithdrawalIds' => $pendingWithdrawalIds,
                'pendingWithdrawalAmounts' => (object)$pendingWithdrawalAmounts,
            ]);
        }

        return view('admin.withdrawals.index', compact(
            'withdrawals',
            'statusCounts',
            'todayWithdrawn',
            'weekWithdrawn',
            'monthWithdrawn',
            'totalWithdrawn',
            'selectableWithdrawalIds',
            'pendingWithdrawalIds',
            'pendingWithdrawalAmounts'
        ));
    }

    /**
     * Xử lý nghiệp vụ phê duyệt MỘT yêu cầu rút tiền (Tiền đã trừ từ ví lúc gửi yêu cầu).
     * Giải thích nghiệp vụ:
     * - Được dùng chung cho cả duyệt đơn lẻ và duyệt nhanh hàng loạt để tránh trùng lặp logic.
     * - Chạy trong Database Transaction kèm lockForUpdate() nhằm chống Race Condition (duyệt 2 lần).
     * - Trả về true nếu duyệt thành công, false nếu lệnh không tồn tại hoặc đã được xử lý từ trước.
     */
    private function processApprove($id): bool
    {
        return DB::transaction(function () use ($id) {
            $withdrawal = Withdrawal::where('id', $id)->lockForUpdate()->first();

            // Bỏ qua các lệnh không còn tồn tại hoặc đã được duyệt/từ chối trước đó
            if (!$withdrawal || $withdrawal->status !== 'pending') {
                return false;
            }

            // 1. Cập nhật trạng thái duyệt thành công
            $withdrawal->status = 'approved';
            $withdrawal->processed_at = now();
            $withdrawal->save();

            // 2. Ghi nhận số tiền rút thành công của User
            $user = User::where('id', $withdrawal->user_id)->lockForUpdate()->firstOrFail();
            $user->total_withdrawn += $withdrawal->amount;
            $user->save();

            // Gửi thông báo đến user
            \App\Models\Notification::create([
                'user_id' => $user->id,
                'title' => 'Yêu cầu rút tiền thành công',
                'content' => "Yêu cầu rút tiền trị giá " . number_format($withdrawal->amount) . "đ đã được xử lý và chuyển khoản thành công."
            ]);

            // Đưa email thông báo rút tiền thành công vào hàng đợi (Queue) để gửi bất đồng bộ nhằm tăng tốc độ phê duyệt của Admin
            try {
                \App\Models\Setting::sendEmailQueue($user->email, 'withdrawal_approved', [
                    'code' => $withdrawal->code ?? ('HTS W' . $withdrawal->id),
                    'name' => $user->name,
                    'email' => $user->email,
                    'amount' => number_format($withdrawal->real_amount),
                    'payment_method' => $withdrawal->payment_method === 'bank' ? 'Chuyển khoản ngân hàng' : 'Ví điện tử',
                    'account_name' => $withdrawal->account_name,
                    'account_number' => $withdrawal->account_number,
                    'bank_name' => $withdrawal->bank_name
                ]);
            } catch (\Exception $e) {
                \Log::error('Lỗi thêm email duyệt rút tiền vào hàng đợi: ' . $e->getMessage());
            }

            // Gửi thông báo Telegram khi duyệt rút tiền thành công
            try {
                \App\Models\Setting::sendTelegramTemplate('telegram_template_withdrawal_approved', [
                    'code' => $withdrawal->code ?? ('HTS W' . $withdrawal->id),
                    'name' => $user->name,
                    'email' => $user->email,
                    'amount' => number_format($withdrawal->real_amount),
                    'payment_method' => $withdrawal->payment_method === 'bank' ? 'Chuyển khoản ngân hàng' : 'Ví điện tử',
                    'bank_name' => $withdrawal->bank_name,
                    'account_number' => $withdrawal->account_number,
                    'account_name' => $withdrawal->account_name,
                ]);
            } catch (\Exception $e) {
                \Log::error('Lỗi gửi Telegram duyệt rút tiền: ' . $e->getMessage());
            }

            ActivityLog::log("Phê duyệt yêu cầu rút tiền ID {$withdrawal->id} cho user {$user->email}", auth()->id());

            return true;
        });
    }

    /**
     * Phê duyệt yêu cầu rút tiền đơn lẻ (Tiền đã trừ từ ví lúc gửi yêu cầu).
     */
    public function approve(Request $request, $id)
    {
        try {
            // Gọi hàm xử lý nghiệp vụ dùng chung với duyệt hàng loạt
            $approved = $this->processApprove($id);

            if (!$approved) {
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => __('Yêu cầu rút tiền này đã được xử lý từ trước.')
                    ], 400);
                }
                return back()->with('error', __('Yêu cầu rút tiền này đã được xử lý.'));
            }

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => __('Đã phê duyệt yêu cầu rút tiền thành công.')
                ]);
            }

            return back()->with('success', __('Đã phê duyệt yêu cầu rút tiền thành công.'));
        } catch (\Exception $e) {
            \Log::error("Approve withdrawal failed: " . $e->getMessage());
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => __('Có lỗi hệ thống xảy ra trong quá trình duyệt yêu cầu rút tiền. Vui lòng kiểm tra file log.')
                ], 500);
            }
            return back()->with('error', __('Có lỗi hệ thống xảy ra trong quá trình duyệt yêu cầu rút tiền. Vui lòng kiểm tra file log.'));
        }
    }

    /**
     * Phê duyệt nhanh HÀNG LOẠT các yêu cầu rút tiền được tích chọn.
     * Giải thích nghiệp vụ:
     * - Chỉ xử lý các lệnh đang ở trạng thái "pending", tự động bỏ qua các lệnh đã duyệt/từ chối trước đó.
     * - Mỗi lệnh được duyệt trong một Database Transaction riêng biệt kèm lockForUpdate() để chống Race Condition,
     *   đồng thời đảm bảo một lệnh lỗi không làm rollback toàn bộ các lệnh đã duyệt thành công.
     */
    public function bulkApprove(Request $request)
    {
        $request->validate([
            'ids' => 'required|string',
        ]);

        $ids = explode(',', $request->ids);
        $ids = array_filter(array_map('intval', $ids));

        if (empty($ids)) {
            return response()->json([
                'status' => 'error',
                'message' => __('Không có yêu cầu rút tiền nào được chọn.')
            ]);
        }

        // Nới thời gian thực thi vì mỗi lệnh duyệt có thể phát sinh một lượt gửi thông báo Telegram (HTTP chờ tối đa 10 giây).
        // Trường hợp xấu nhất bị timeout, các lệnh đã duyệt vẫn được lưu an toàn (mỗi lệnh là một transaction riêng)
        // và Admin chỉ cần thao tác lại với phần còn lại vì các lệnh đã duyệt sẽ tự động được bỏ qua.
        @set_time_limit(600);

        $count = 0;   // Số lệnh duyệt chi thành công
        $skipped = 0; // Số lệnh bị bỏ qua (đã xử lý trước đó hoặc phát sinh lỗi)

        foreach ($ids as $id) {
            try {
                if ($this->processApprove($id)) {
                    $count++;
                } else {
                    $skipped++;
                }
            } catch (\Exception $e) {
                // Ghi log và bỏ qua lệnh lỗi để tiếp tục xử lý các lệnh còn lại
                \Log::error("Bulk approve withdrawal ID {$id} failed: " . $e->getMessage());
                $skipped++;
            }
        }

        if ($count === 0) {
            return response()->json([
                'status' => 'error',
                'message' => __('Không tìm thấy yêu cầu rút tiền nào ở trạng thái chờ duyệt để xử lý.')
            ]);
        }

        ActivityLog::log("Duyệt chi hàng loạt {$count} yêu cầu rút tiền", auth()->id());

        $message = __('Đã duyệt chi thành công :count yêu cầu rút tiền được chọn.', ['count' => $count]);
        if ($skipped > 0) {
            $message .= ' ' . __('Đã bỏ qua :skipped yêu cầu do không còn ở trạng thái chờ duyệt.', ['skipped' => $skipped]);
        }

        return response()->json(['status' => 'success', 'message' => $message]);
    }

    /**
     * Từ chối yêu cầu rút tiền (Hoàn trả tiền vào ví của User).
     */
    public function reject(Request $request, $id)
    {
        $request->validate([
            'notes' => 'required|string|max:255'
        ], [
            'notes.required' => 'Vui lòng nhập lý do từ chối rút tiền.'
        ]);

        try {
            return DB::transaction(function () use ($request, $id) {
                $withdrawal = Withdrawal::where('id', $id)->lockForUpdate()->firstOrFail();

                if ($withdrawal->status !== 'pending') {
                    return back()->with('error', 'Yêu cầu rút tiền này đã được xử lý.');
                }

                // 1. Cập nhật trạng thái từ chối
                $withdrawal->status = 'rejected';
                $withdrawal->notes = $request->notes;
                $withdrawal->processed_at = now();
                $withdrawal->save();

                // 2. Hoàn trả tiền vào ví balance của User
                $user = User::where('id', $withdrawal->user_id)->lockForUpdate()->firstOrFail();
                $oldBalance = $user->balance;
                $user->balance += $withdrawal->amount;
                $user->save();

                // Ghi nhận biến động số dư (Giao dịch hoàn tiền rút bị từ chối)
                \App\Models\BalanceLog::write(
                    $user,
                    $oldBalance,
                    $withdrawal->amount,
                    $user->balance,
                    'withdraw_refund',
                    "Hoàn tiền rút bị từ chối (Lý do: {$request->notes})"
                );

                // Gửi thông báo hoàn tiền lại cho user
                \App\Models\Notification::create([
                    'user_id' => $user->id,
                    'title' => 'Yêu cầu rút tiền bị từ chối',
                    'content' => "Yêu cầu rút " . number_format($withdrawal->amount) . "đ bị từ chối. Lý do: {$request->notes}. Số tiền đã được hoàn lại vào ví."
                ]);

                ActivityLog::log("Từ chối yêu cầu rút tiền ID {$withdrawal->id}. Lý do: {$request->notes}", auth()->id());

                // Gửi thông báo Telegram khi từ chối rút tiền
                try {
                    \App\Models\Setting::sendTelegramTemplate('telegram_template_withdrawal_rejected', [
                        'code' => $withdrawal->code ?? ('HTS W' . $withdrawal->id),
                        'name' => $user->name,
                        'email' => $user->email,
                        'amount' => number_format($withdrawal->amount),
                        'reason' => $request->notes,
                    ]);
                } catch (\Exception $e) {
                    \Log::error('Lỗi gửi Telegram từ chối rút tiền: ' . $e->getMessage());
                }

                return back()->with('success', 'Đã từ chối yêu cầu và hoàn tiền lại vào tài khoản user.');
            });
        } catch (\Exception $e) {
            \Log::error("Reject withdrawal failed: " . $e->getMessage());
            return back()->with('error', 'Có lỗi hệ thống xảy ra khi từ chối yêu cầu rút tiền. Vui lòng kiểm tra file log.');
        }
    }

    /**
     * Lấy thông tin chi tiết của yêu cầu rút tiền và lịch sử số dư gần đây của user.
     * Trả về JSON để hiển thị động trên Modal.
     */
    public function show($id)
    {
        try {
            $withdrawal = Withdrawal::with('user')->findOrFail($id);
            $userId = $withdrawal->user_id;

            // Lấy trực tiếp từ bảng balance_logs mới thiết lập để đảm bảo đầy đủ và tốc độ tối đa
            $financialLogs = DB::table('balance_logs')
                ->where('user_id', $userId)
                ->orderBy('created_at', 'desc')
                ->limit(15)
                ->get();

            // Format lại dữ liệu trước khi trả về client
            $formattedLogs = $financialLogs->map(function ($log) {
                return [
                    'id' => $log->id,
                    'type' => $log->type,
                    'amount_before' => (float)$log->amount_before,
                    'amount_change' => (float)$log->amount_change,
                    'amount_after' => (float)$log->amount_after,
                    'description' => $log->description,
                    'created_at' => date('d/m/Y H:i', strtotime($log->created_at))
                ];
            });

            return response()->json([
                'success' => true,
                'withdrawal' => [
                    'id' => $withdrawal->id,
                    'code' => $withdrawal->code ?? ('HTS W' . $withdrawal->id),
                    'amount' => (float)$withdrawal->amount,
                    'fee' => (float)($withdrawal->fee ?? 0),
                    'real_amount' => (float)($withdrawal->real_amount ?? $withdrawal->amount),
                    'payment_method' => $withdrawal->payment_method,
                    'account_number' => $withdrawal->account_number,
                    'account_name' => $withdrawal->account_name,
                    'bank_name' => $withdrawal->bank_name,
                    'status' => $withdrawal->status,
                    'notes' => $withdrawal->notes,
                    'created_at' => $withdrawal->created_at->format('d/m/Y H:i'),
                    'processed_at' => $withdrawal->processed_at ? $withdrawal->processed_at->format('d/m/Y H:i') : null,
                    'user' => [
                        'name' => $withdrawal->user->name,
                        'email' => $withdrawal->user->email,
                        'balance' => (float)$withdrawal->user->balance
                    ]
                ],
                'logs' => $formattedLogs
            ]);

        } catch (\Exception $e) {
            \Log::error("Get withdrawal details failed: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Không thể lấy thông tin chi tiết lệnh rút tiền do lỗi hệ thống.'
            ], 500);
        }
    }

    /**
     * API thống kê yêu cầu rút tiền theo thời gian (AJAX).
     * Giải thích nghiệp vụ:
     * - Trả về dữ liệu JSON gồm: số tiền đã rút, số lượng lệnh rút theo ngày/tháng, 
     *   và top thành viên rút tiền nhiều nhất.
     * - Giúp admin theo dõi dòng tiền chi trả hoa hồng và kiểm soát quỹ tiền mặt của hệ thống.
     * - Hỗ trợ các mốc thời gian: tuần, tháng, năm.
     */
    public function withdrawalStats(Request $request)
    {
        // Nhận tham số period, mặc định là week
        $period = $request->input('period', 'week');
        if (!in_array($period, ['week', 'month', 'year'])) {
            $period = 'week';
        }

        // Xác định khoảng thời gian và format gom nhóm ngày/tháng trong MySQL
        if ($period === 'week') {
            $startDate = \Carbon\Carbon::now()->subDays(6)->startOfDay();
            $groupFormat = '%Y-%m-%d';
            $labelFormat = 'd/m';
        } elseif ($period === 'month') {
            $startDate = \Carbon\Carbon::now()->subDays(29)->startOfDay();
            $groupFormat = '%Y-%m-%d';
            $labelFormat = 'd/m';
        } else {
            $startDate = \Carbon\Carbon::now()->subMonths(11)->startOfMonth();
            $groupFormat = '%Y-%m';
            $labelFormat = null;
        }

        // Truy vấn tổng số tiền rút (chỉ lệnh đã approved) theo ngày/tháng
        $statsData = Withdrawal::where('status', 'approved')
            ->where('created_at', '>=', $startDate)
            ->select(
                \Illuminate\Support\Facades\DB::raw("DATE_FORMAT(created_at, '{$groupFormat}') as date_key"),
                \Illuminate\Support\Facades\DB::raw('SUM(amount) as total_amount')
            )
            ->groupBy('date_key')
            ->pluck('total_amount', 'date_key')
            ->toArray();

        // Tạo mảng labels và datasets cho Chart.js
        $labels = [];
        $amounts = [];

        if ($period === 'year') {
            // Duyệt 12 tháng gần nhất
            for ($i = 11; $i >= 0; $i--) {
                $date = \Carbon\Carbon::now()->subMonths($i);
                $key = $date->format('Y-m');
                $labels[] = __('Tháng') . ' ' . $date->format('m/Y');
                $amounts[] = (float)($statsData[$key] ?? 0);
            }
        } else {
            // Duyệt từng ngày
            $totalDays = $period === 'week' ? 6 : 29;
            for ($i = $totalDays; $i >= 0; $i--) {
                $date = \Carbon\Carbon::now()->subDays($i);
                $key = $date->format('Y-m-d');
                $labels[] = $date->format($labelFormat);
                $amounts[] = (float)($statsData[$key] ?? 0);
            }
        }

        // Tính tổng tiền đã duyệt trong thời gian này
        $totalApproved = Withdrawal::where('status', 'approved')
            ->where('created_at', '>=', $startDate)
            ->sum('amount');

        // Tính tổng tiền đang chờ duyệt trong thời gian này
        $totalPending = Withdrawal::where('status', 'pending')
            ->where('created_at', '>=', $startDate)
            ->sum('amount');

        // Lấy top 5 thành viên rút tiền nhiều nhất trong khoảng thời gian này
        $topUsers = Withdrawal::where('status', 'approved')
            ->where('created_at', '>=', $startDate)
            ->select('user_id', \Illuminate\Support\Facades\DB::raw('SUM(amount) as total_withdrawn'))
            ->groupBy('user_id')
            ->orderByDesc('total_withdrawn')
            ->limit(5)
            ->with('user:id,name,email')
            ->get()
            ->map(function ($item) {
                return [
                    'user_name' => $item->user ? $item->user->name : 'N/A',
                    'user_email' => $item->user ? $item->user->email : 'N/A',
                    'total_amount' => (float)$item->total_withdrawn
                ];
            });

        return response()->json([
            'success' => true,
            'labels' => $labels,
            'amounts' => $amounts,
            'totals' => [
                'approved' => (float)$totalApproved,
                'pending' => (float)$totalPending,
            ],
            'top_users' => $topUsers,
            'period' => $period,
        ]);
    }

    /**
     * Xóa 1 yêu cầu rút tiền khỏi hệ thống.
     * Giải thích nghiệp vụ:
     * - Nếu lệnh ở trạng thái "pending" (chờ duyệt): Tiền khả dụng đã bị trừ từ trước sẽ được hoàn trả lại ví user và ghi log biến động số dư.
     * - Nếu lệnh ở trạng thái "approved" (đã duyệt): Số tiền rút sẽ bị trừ bớt khỏi tổng tích lũy rút tiền (total_withdrawn) của user.
     * - Sử dụng DB::transaction và lockForUpdate() chống Race Condition.
     */
    public function destroy(Request $request, $id)
    {
        try {
            DB::transaction(function () use ($id) {
                // Khóa bản ghi lệnh rút tiền trước khi xử lý xóa
                $withdrawal = Withdrawal::where('id', $id)->lockForUpdate()->firstOrFail();
                $code = $withdrawal->code ?? ('HTS W' . $withdrawal->id);
                $status = $withdrawal->status;
                $amount = $withdrawal->amount;

                $user = User::where('id', $withdrawal->user_id)->lockForUpdate()->first();

                // Nếu lệnh đã ở trạng thái approved -> trừ bớt tổng tiền đã rút của thành viên
                if ($status === 'approved' && $user) {
                    $user->total_withdrawn = max(0, $user->total_withdrawn - $amount);
                    $user->save();
                }

                // Thực hiện xóa yêu cầu rút tiền (Không hoàn lại tiền cho user theo chỉ đạo nghiệp vụ)
                $withdrawal->delete();

                ActivityLog::log("Xóa yêu cầu rút tiền #{$code} (Trạng thái: {$status}) khỏi hệ thống", auth()->id());
            });

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['status' => 'success', 'message' => 'Đã xóa yêu cầu rút tiền thành công!']);
            }

            return back()->with('success', 'Đã xóa yêu cầu rút tiền thành công!');
        } catch (\Exception $e) {
            \Log::error("Destroy withdrawal failed: " . $e->getMessage());
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['status' => 'error', 'message' => 'Có lỗi xảy ra trong quá trình xóa yêu cầu rút tiền: ' . $e->getMessage()], 500);
            }
            return back()->with('error', 'Có lỗi xảy ra trong quá trình xóa yêu cầu rút tiền: ' . $e->getMessage());
        }
    }

    /**
     * Xóa hàng loạt yêu cầu rút tiền khỏi hệ thống.
     */
    public function bulkDestroy(Request $request)
    {
        $request->validate([
            'ids' => 'required|string',
            'confirm_text' => 'required|string',
        ]);

        // Dùng mb_strtoupper (hỗ trợ Unicode) để cụm từ tiếng Việt có dấu viết thường vẫn được chấp nhận,
        // vì strtoupper() thuần chỉ xử lý ký tự ASCII nên "xóa hàng loạt" sẽ không khớp được.
        if (mb_strtoupper($request->confirm_text, 'UTF-8') !== 'XÓA HÀNG LOẠT') {
            return response()->json(['status' => 'error', 'message' => __('Từ khóa xác nhận không chính xác.')]);
        }

        $ids = explode(',', $request->ids);
        $ids = array_filter(array_map('intval', $ids));

        if (empty($ids)) {
            return response()->json(['status' => 'error', 'message' => __('Không có yêu cầu rút tiền nào được chọn.')]);
        }

        try {
            $deleted = 0;

            DB::transaction(function () use ($ids, &$deleted) {
                $withdrawals = Withdrawal::whereIn('id', $ids)->lockForUpdate()->get();

                foreach ($withdrawals as $withdrawal) {
                    $code = $withdrawal->code ?? ('HTS W' . $withdrawal->id);
                    $status = $withdrawal->status;
                    $amount = $withdrawal->amount;

                    $user = User::where('id', $withdrawal->user_id)->lockForUpdate()->first();

                    if ($status === 'approved' && $user) {
                        $user->total_withdrawn = max(0, $user->total_withdrawn - $amount);
                        $user->save();
                    }

                    // Thực hiện xóa lệnh rút tiền (Không hoàn lại tiền cho user)
                    $withdrawal->delete();

                    ActivityLog::log("Xóa yêu cầu rút tiền hàng loạt #{$code}", auth()->id());

                    $deleted++;
                }
            });

            if ($deleted === 0) {
                return response()->json(['status' => 'error', 'message' => __('Không tìm thấy yêu cầu rút tiền nào để xóa.')]);
            }

            return response()->json([
                'status' => 'success',
                'message' => __('Đã xóa thành công :count yêu cầu rút tiền được chọn khỏi hệ thống!', ['count' => $deleted])
            ]);
        } catch (\Exception $e) {
            \Log::error("Bulk destroy withdrawal failed: " . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => __('Có lỗi xảy ra trong quá trình xóa hàng loạt yêu cầu rút tiền. Vui lòng kiểm tra file log.')], 500);
        }
    }
}
