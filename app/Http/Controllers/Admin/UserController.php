<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{


    /**
     * Áp dụng bộ lọc tìm kiếm danh sách thành viên lên một query builder.
     * Giải thích: Tách riêng thành hàm dùng chung để trang danh sách (index) và
     * chức năng xuất file CSV theo bộ lọc luôn cho ra cùng một tập dữ liệu, tránh lệch kết quả.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param Request $request
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function applyUserFilters($query, Request $request)
    {
        // 1. Tìm kiếm theo tên, email hoặc số điện thoại
        if ($request->has('search') && !empty($request->search)) {
            $search = '%' . $request->search . '%';
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', $search)
                  ->orWhere('email', 'like', $search)
                  ->orWhere('phone', 'like', $search);
            });
        }

        // Lọc theo nguồn UTM Source khi đăng ký
        if ($request->has('utm_source') && !empty($request->utm_source)) {
            $query->where('utm_source', 'like', '%' . $request->utm_source . '%');
        }

        // 2. Lọc chính xác hoặc tương đối theo địa chỉ IP đăng ký
        if ($request->has('ip_address') && !empty($request->ip_address)) {
            $query->where('ip_address', 'like', '%' . $request->ip_address . '%');
        }

        // 3. Lọc tương đối theo thông tin thiết bị / trình duyệt (user_agent)
        if ($request->has('user_agent') && !empty($request->user_agent)) {
            $query->where('user_agent', 'like', '%' . $request->user_agent . '%');
        }

        // Lọc theo vai trò (role)
        if ($request->has('role') && in_array($request->role, ['admin', 'user'])) {
            $query->where('role', $request->role);
        }

        // Lọc các thành viên được giới thiệu bởi một người giới thiệu cụ thể (tìm kiếm theo ID, email hoặc mã giới thiệu của người giới thiệu)
        if ($request->has('referred_by') && !empty($request->referred_by)) {
            $referredBySearch = $request->referred_by;
            $query->whereHas('referrer', function ($q) use ($referredBySearch) {
                $q->where('id', $referredBySearch)
                  ->orWhere('email', 'like', '%' . $referredBySearch . '%')
                  ->orWhere('referral_code', $referredBySearch);
            });
        }

        // Lọc theo hình thức đăng ký: chỉ hiển thị thành viên được giới thiệu (referred_by khác null) hoặc đăng ký trực tiếp (referred_by bằng null)
        if ($request->has('is_referred') && in_array($request->is_referred, ['yes', 'no'])) {
            if ($request->is_referred === 'yes') {
                $query->whereNotNull('referred_by');
            } else {
                $query->whereNull('referred_by');
            }
        }

        // Lọc theo trạng thái hoạt động (status)
        if ($request->has('status') && in_array($request->status, ['active', 'suspended'])) {
            $query->where('status', $request->status);
        }

        // Lọc theo trạng thái kết nối (online/offline)
        // Online được xác định khi thời gian hoạt động gần nhất trong vòng 5 phút vừa qua.
        if ($request->has('is_online') && in_array($request->is_online, ['online', 'offline'])) {
            if ($request->is_online === 'online') {
                $query->where('last_seen_at', '>=', now()->subMinutes(5));
            } else {
                $query->where(function ($q) {
                    $q->where('last_seen_at', '<', now()->subMinutes(5))
                      ->orWhereNull('last_seen_at');
                });
            }
        }

        return $query;
    }

    /**
     * Danh sách người dùng trong hệ thống (Search, Filter, Paginate).
     */
    public function index(Request $request)
    {
        // Áp dụng toàn bộ bộ lọc tìm kiếm dùng chung (chia sẻ với chức năng xuất file CSV)
        $query = $this->applyUserFilters(User::query(), $request);

        // Lấy cấu hình số dòng hiển thị (limit), mặc định là 15 dòng, tối đa 500 dòng
        $limit = $request->integer('limit', 15);
        if ($limit < 1 || $limit > 500) {
            $limit = 15;
        }

        // Lấy cấu hình sắp xếp động từ request để cho phép sắp xếp theo số dư, ngày tạo, hoặc các trường tài chính khác
        // Điều này giúp quản trị viên dễ dàng tìm ra những thành viên có hoạt động tích cực hoặc số dư lớn
        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');

        // Whitelist các cột được phép sắp xếp để ngăn chặn triệt để lỗ hổng SQL Injection qua tham số sort_by
        $allowedSorts = ['created_at', 'balance', 'total_cashback', 'total_withdrawn'];
        if (!in_array($sortBy, $allowedSorts)) {
            $sortBy = 'created_at';
        }
        if (!in_array($sortOrder, ['asc', 'desc'])) {
            $sortOrder = 'desc';
        }

        // Nạp thêm quan hệ roleRelation và referrer để hiển thị vai trò và người giới thiệu ở UI
        $users = $query->with(['roleRelation', 'referrer'])
            ->withCount([
                'cashbackHistories as pending_orders_count' => function ($q) {
                    $q->where('status', 'pending');
                },
                'cashbackHistories as approved_orders_count' => function ($q) {
                    $q->where('status', 'approved');
                }
            ])
            ->orderBy($sortBy, $sortOrder)
            ->paginate($limit)
            ->withQueryString();
        
        // Lấy danh sách các vai trò để hiển thị ở modal cập nhật
        $roles = \App\Models\Role::all();

        // Nếu là yêu cầu AJAX từ frontend, trả về kết quả HTML của bảng và dữ liệu bổ sung dạng JSON
        if ($request->ajax()) {
            $selectableUserIds = $users->reject(fn($u) => $u->id === auth()->id())->pluck('id')->toArray();
            return response()->json([
                'html' => view('admin.users.partials.table', compact('users'))->render(),
                'selectableUserIds' => $selectableUserIds,
                // Tổng số bản ghi khớp bộ lọc, dùng để hiển thị ở modal xuất file CSV
                'total' => $users->total(),
            ]);
        }

        // Thống kê tổng quan toàn hệ thống (không phụ thuộc bộ lọc) hiển thị ở widget đầu trang
        $stats = [
            // Thành viên có phát sinh đơn hàng (tồn tại ít nhất 1 bản ghi cashback_histories)
            'active' => User::whereHas('cashbackHistories')->count(),
            // Thành viên đang online (hoạt động trong vòng 5 phút gần nhất)
            'online' => User::where('last_seen_at', '>=', now()->subMinutes(5))->count(),
            // Tài khoản quản trị viên
            'admin' => User::where('role', 'admin')->count(),
            // Tài khoản bị khoá
            'suspended' => User::where('status', 'suspended')->count(),
            // Tổng số thành viên toàn hệ thống (dùng cho modal xuất file CSV)
            'total' => User::count(),
        ];

        // Danh sách cột có thể xuất file CSV, dùng cho modal tuỳ chọn & sắp xếp cột trước khi xuất
        $exportColumns = $this->exportColumnsForView();

        return view('admin.users.index', compact('users', 'roles', 'stats', 'exportColumns'));
    }

    /**
     * Danh sách nhật ký biến động số dư hệ thống (lịch sử dòng tiền).
     */
    public function balanceLogs(Request $request)
    {
        $query = \App\Models\BalanceLog::query()->with('user');

        // Tìm kiếm tách biệt theo thông tin thành viên (tên, email, sđt)
        if ($request->has('user_search') && !empty($request->user_search)) {
            $search = '%' . $request->user_search . '%';
            $query->whereHas('user', function($q) use ($search) {
                $q->where('name', 'like', $search)
                  ->orWhere('email', 'like', $search)
                  ->orWhere('phone', 'like', $search);
            });
        }

        // Lọc theo loại biến động số dư
        if ($request->has('type') && !empty($request->type)) {
            $query->where('type', $request->type);
        }

        // Cấu hình số dòng hiển thị động (limit), tối đa 500 dòng
        $limit = $request->integer('limit', 15);
        if ($limit < 1 || $limit > 500) {
            $limit = 15;
        }

        // Sắp xếp nhật ký biến động số dư theo thời gian mới nhất (giảm dần). 
        // Trường hợp nhiều giao dịch phát sinh cùng một giây (ví dụ: chia hoa hồng MLM đa tầng), 
        // sắp xếp phụ theo ID giảm dần để đảm bảo hiển thị đúng thứ tự ghi nhận và không bị xáo trộn.
        $logs = $query->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc')
            ->paginate($limit)
            ->withQueryString();

        return view('admin.balance_logs.index', compact('logs'));
    }

    /**
     * Giao diện chỉnh sửa thông tin thành viên (Trang mới).
     * Lấy thêm thông tin tổng tiền nhận từ điểm danh, giftcode và nhiệm vụ.
     */
    public function edit(User $user)
    {
        $roles = \App\Models\Role::all();

        // Tính toán các khoản tiền thưởng mà người dùng đã nhận được
        // 1. Tổng tiền nhận từ điểm danh hằng ngày
        $checkin_earned = \App\Models\DailyCheckin::where('user_id', $user->id)->sum('coins_earned');

        // 2. Tổng tiền nhận từ việc nhập Giftcode
        $giftcode_earned = \App\Models\GiftCodeRedemption::where('user_id', $user->id)->sum('amount');

        // 3. Tổng tiền nhận từ việc hoàn thành nhiệm vụ (trạng thái đã nhận thưởng 'claimed')
        $tasks_earned = \App\Models\UserTask::where('user_id', $user->id)
            ->where('status', 'claimed')
            ->join('tasks', 'user_tasks.task_id', '=', 'tasks.id')
            ->sum('tasks.reward_amount');

        // 4. Tổng tiền nhận từ hoàn tiền đơn hàng thực tế (đơn hàng đã duyệt 'approved')
        $order_cashback_earned = \App\Models\CashbackHistory::where('user_id', $user->id)
            ->where('status', 'approved')
            ->sum('cashback_amount');

        return view('admin.users.edit', compact('user', 'roles', 'checkin_earned', 'giftcode_earned', 'tasks_earned', 'order_cashback_earned'));
    }

    /**
     * Đăng nhập nhanh vào tài khoản người dùng dưới quyền Admin.
     */
    public function loginAs(User $user)
    {
        // Chặn đăng nhập nhanh dưới danh nghĩa thành viên ở chế độ Demo để bảo mật dữ liệu
        if (config('app.demo')) {
            return back()->with('error', __('Chức năng đăng nhập nhanh dưới danh nghĩa thành viên bị khóa ở chế độ Demo.'));
        }

        // 1. Ràng buộc bảo mật: Không tự đăng nhập vào tài khoản chính mình
        if ($user->id === auth()->id()) {
            return back()->with('error', __('Bạn đang đăng nhập bằng tài khoản này rồi.'));
        }

        // 1.5. Ràng buộc bảo mật (Chống leo thang đặc quyền):
        // Không cho phép đăng nhập nhanh vào bất kỳ tài khoản quản trị nào (kể cả Super Admin lẫn admin phụ).
        // Nếu không chặn, một admin phụ chỉ cần quyền manage_users là có thể mượn danh Super Admin để chiếm toàn quyền hệ thống.
        if ($user->role === 'admin') {
            ActivityLog::log(__("Từ chối yêu cầu đăng nhập nhanh vào một tài khoản quản trị khác (User ID :id, :email)", ['id' => $user->id, 'email' => $user->email]), auth()->id());
            return back()->with('error', __('Không thể đăng nhập nhanh vào một tài khoản quản trị khác vì lý do bảo mật.'));
        }

        // 2. Ghi nhận nhật ký hoạt động của Admin để phục vụ giám sát bảo mật
        ActivityLog::log(__("Admin đăng nhập nhanh vào tài khoản User ID :id (:email)", ['id' => $user->id, 'email' => $user->email]), auth()->id());

        // 3. Lưu ID Admin hiện tại vào session trước khi đăng nhập để hỗ trợ tính năng "Quay lại Admin"
        session(['admin_impersonator_id' => auth()->id()]);

        // 4. Thực hiện đăng nhập dưới danh nghĩa thành viên
        \Illuminate\Support\Facades\Auth::login($user);

        // 5. Chuyển hướng người dùng về trang Dashboard với thông báo thành công
        return redirect()->route('dashboard')->with('success', __('Đăng nhập nhanh vào tài khoản :name thành công!', ['name' => $user->name]));
    }

    /**
     * Quay lại tài khoản Admin gốc từ tài khoản đang impersonate.
     */
    public function returnToAdmin()
    {
        // 1. Kiểm tra session xem có chứa ID của Admin gốc đang giả lập hay không
        if (!session()->has('admin_impersonator_id')) {
            return redirect()->route('dashboard');
        }

        // 2. Lấy ID Admin ra và xoá khỏi session
        $adminId = session()->pull('admin_impersonator_id');
        $admin = User::find($adminId);

        // 3. Xác thực tài khoản Admin gốc tồn tại, có quyền quản trị và đang hoạt động (active)
        if (!$admin || $admin->role !== 'admin' || $admin->status !== 'active') {
            return redirect()->route('dashboard')->with('error', __('Không tìm thấy tài khoản quản trị gốc hoặc tài khoản đã bị khóa.'));
        }

        // 4. Ghi nhận nhật ký quay lại tài khoản Admin
        ActivityLog::log(__("Admin quay lại tài khoản quản trị từ việc giả lập tài khoản User"), $admin->id);

        // 5. Thực hiện đăng nhập lại bằng tài khoản Admin
        \Illuminate\Support\Facades\Auth::login($admin);

        // 6. Chuyển hướng về trang danh sách thành viên trong Admin với thông báo thành công
        return redirect()->route('admin.users.index')->with('success', __('Quay lại tài khoản quản trị thành công!'));
    }

    /**
     * Lấy danh sách thành viên cấp dưới (F1 & F2) qua AJAX có hỗ trợ lọc, tìm kiếm và phân trang.
     */
    public function getMlmAjax(Request $request, User $user)
    {
        $page = (int)$request->input('page', 1);
        $perPage = (int)$request->input('per_page', 10);
        $level = $request->input('level', 'all'); // all, f1, f2
        $search = $request->input('search');

        // Định nghĩa truy vấn F1
        $f1Query = DB::table('users')
            ->select('id', 'name', 'email', 'balance', 'created_at', DB::raw("'F1' as level"))
            ->where('referred_by', $user->id);

        // Định nghĩa truy vấn F2
        $f2Query = DB::table('users')
            ->select('id', 'name', 'email', 'balance', 'created_at', DB::raw("'F2' as level"))
            ->whereIn('referred_by', function ($q) use ($user) {
                $q->select('id')->from('users')->where('referred_by', $user->id);
            });

        // Kết hợp union tùy thuộc bộ lọc
        if ($level === 'f1') {
            $mainQuery = $f1Query;
        } elseif ($level === 'f2') {
            $mainQuery = $f2Query;
        } else {
            $mainQuery = $f1Query->union($f2Query);
        }

        // Tạo truy vấn con để thực hiện lọc tìm kiếm và sắp xếp
        $query = DB::table(DB::raw("({$mainQuery->toSql()}) as sub"))
            ->mergeBindings($mainQuery);

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $total = $query->count();
        $items = $query->orderBy('created_at', 'desc')
            ->offset(($page - 1) * $perPage)
            ->limit($perPage)
            ->get();

        // Định dạng dữ liệu ngày tháng và tiền tệ trước khi trả về
        $formattedItems = $items->map(function ($item) {
            return [
                'id' => $item->id,
                'name' => $item->name,
                'email' => $item->email,
                'level' => $item->level,
                'balance' => (float)$item->balance,
                'formatted_balance' => number_format($item->balance) . 'đ',
                'created_at' => $item->created_at ? date('d/m/Y H:i', strtotime($item->created_at)) : 'N/A',
                'detail_url' => route('admin.users.edit', $item->id),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $formattedItems,
            'total' => $total,
            'has_more' => ($page * $perPage) < $total,
        ]);
    }

    /**
     * Lấy danh sách biến động số dư qua AJAX có hỗ trợ lọc, tìm kiếm và phân trang.
     */
    public function getBalanceLogsAjax(Request $request, User $user)
    {
        $page = (int)$request->input('page', 1);
        $perPage = (int)$request->input('per_page', 10);
        $search = $request->input('search');
        $type = $request->input('type', 'all');

        $query = \App\Models\BalanceLog::where('user_id', $user->id);

        if ($type !== 'all' && !empty($type)) {
            $query->where('type', $type);
        }

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                  ->orWhere('type', 'like', "%{$search}%");
            });
        }

        $total = $query->count();
        // Sắp xếp nhật ký biến động số dư theo thời gian mới nhất (giảm dần). 
        // Trong trường hợp nhiều giao dịch phát sinh cùng một giây, sắp xếp phụ theo ID giảm dần 
        // để đảm bảo hiển thị đúng thứ tự thời gian thực tế ghi nhận trên hệ thống.
        $items = $query->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc')
            ->offset(($page - 1) * $perPage)
            ->limit($perPage)
            ->get();

        $formattedItems = $items->map(function ($item) {
            return [
                'id' => $item->id,
                'amount_before' => (float)$item->amount_before,
                'formatted_amount_before' => number_format($item->amount_before) . 'đ',
                'amount_change' => (float)$item->amount_change,
                'formatted_amount_change' => ($item->amount_change > 0 ? '+' : '') . number_format($item->amount_change) . 'đ',
                'amount_after' => (float)$item->amount_after,
                'formatted_amount_after' => number_format($item->amount_after) . 'đ',
                'type' => $item->type,
                'description' => $item->description,
                'created_at' => $item->created_at ? $item->created_at->format('d/m/Y H:i:s') : 'N/A',
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $formattedItems,
            'total' => $total,
            'has_more' => ($page * $perPage) < $total,
        ]);
    }

    /**
     * Lấy danh sách lịch sử cashback Shopee qua AJAX có hỗ trợ lọc, tìm kiếm và phân trang.
     */
    public function getCashbacksAjax(Request $request, User $user)
    {
        $page = (int)$request->input('page', 1);
        $perPage = (int)$request->input('per_page', 10);
        $search = $request->input('search');
        $status = $request->input('status', 'all');

        $query = \App\Models\CashbackHistory::where('user_id', $user->id);

        if ($status !== 'all' && !empty($status)) {
            $query->where('status', $status);
        }

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('product_name', 'like', "%{$search}%")
                  ->orWhere('order_code', 'like', "%{$search}%");
            });
        }

        $total = $query->count();
        $items = $query->orderBy('created_at', 'desc')
            ->offset(($page - 1) * $perPage)
            ->limit($perPage)
            ->get();

        $formattedItems = $items->map(function ($item) {
            return [
                'id' => $item->id,
                'product_image' => $item->product_image,
                'product_name' => $item->product_name ?? __('Sản phẩm Shopee'),
                'order_code' => $item->order_code ?? 'N/A',
                'order_price' => (float)$item->order_price,
                'formatted_order_price' => number_format($item->order_price) . 'đ',
                'cashback_amount' => (float)$item->cashback_amount,
                'formatted_cashback_amount' => '+' . number_format($item->cashback_amount) . 'đ',
                'status' => $item->status,
                'created_at' => $item->created_at ? $item->created_at->format('d/m/Y H:i') : 'N/A',
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $formattedItems,
            'total' => $total,
            'has_more' => ($page * $perPage) < $total,
        ]);
    }

    /**
     * Lấy danh sách nhật ký hoạt động qua AJAX có hỗ trợ lọc, tìm kiếm và phân trang.
     */
    public function getActivityLogsAjax(Request $request, User $user)
    {
        $page = (int)$request->input('page', 1);
        $perPage = (int)$request->input('per_page', 10);
        $search = $request->input('search');

        $query = \App\Models\ActivityLog::where('user_id', $user->id);

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('activity', 'like', "%{$search}%")
                  ->orWhere('ip_address', 'like', "%{$search}%")
                  ->orWhere('user_agent', 'like', "%{$search}%");
            });
        }

        $total = $query->count();
        $items = $query->orderBy('created_at', 'desc')
            ->offset(($page - 1) * $perPage)
            ->limit($perPage)
            ->get();

        $formattedItems = $items->map(function ($item) {
            return [
                'id' => $item->id,
                'activity' => $item->activity,
                'ip_address' => $item->ip_address ?? 'N/A',
                'user_agent' => $item->user_agent ?? 'N/A',
                'created_at' => $item->created_at ? $item->created_at->format('d/m/Y H:i:s') : 'N/A',
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $formattedItems,
            'total' => $total,
            'has_more' => ($page * $perPage) < $total,
        ]);
    }

    /**
     * Cập nhật thông tin chi tiết, mật khẩu và trạng thái bảo mật của người dùng.
     */
    public function update(Request $request, User $user)
    {
        // Chuẩn hoá số điện thoại về dạng thống nhất trước khi kiểm tra tính duy nhất
        if ($request->filled('phone')) {
            $request->merge(['phone' => User::normalizePhone($request->phone)]);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            // Email có thể để trống với các tài khoản đăng ký bằng Số điện thoại
            'email' => 'nullable|email|max:255|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:15|unique:users,phone,' . $user->id,
            'role' => 'required|in:admin,user',
            'role_id' => 'nullable|exists:roles,id',
            'status' => 'required|in:active,suspended',
            'email_verified' => 'required|in:0,1',
            'password' => 'nullable|string|min:8',
            'google2fa_enabled' => 'nullable|boolean',
            'email_otp_enabled' => 'nullable|boolean',
            'reset_2fa' => 'nullable|boolean',
        ]);

        // Cập nhật thông tin cơ bản: Tên, Email, Số điện thoại và Vai trò
        // Lưu ý: Email và SĐT để trống phải lưu là NULL (không phải chuỗi rỗng) vì hai cột này có ràng buộc UNIQUE
        $user->name = $request->name;
        $user->email = $request->filled('email') ? $request->email : null;
        $user->phone = $request->filled('phone') ? $request->phone : null;
        $user->role = $request->role;
        
        // Nếu chuyển sang quyền admin -> gán thêm vai trò cụ thể (hoặc để trống là Super Admin)
        if ($request->role === 'admin') {
            $user->role_id = $request->role_id;
        } else {
            // Nếu hạ cấp về user bình thường -> xóa vai trò admin cũ
            $user->role_id = null;
        }
        
        // Chặn admin tự thay đổi vai trò (role), quyền cụ thể (role_id) hoặc tự khóa trạng thái của chính mình
        if ($user->id === auth()->id()) {
            if ($request->role !== $user->role || $request->role_id != $user->role_id) {
                return back()->with('error', __('Bạn không thể tự nâng cấp hoặc thay đổi vai trò của chính mình.'));
            }
            if ($request->status === 'suspended') {
                return back()->with('error', __('Bạn không thể khoá tài khoản Admin đang sử dụng.'));
            }
        }
        
        $user->status = $request->status;

        // Cập nhật trạng thái xác thực email
        if ($request->email_verified == '1') {
            if (is_null($user->email_verified_at)) {
                $user->email_verified_at = now();
            }
        } else {
            $user->email_verified_at = null;
        }

        // Cập nhật mật khẩu nếu có nhập mới
        if ($request->filled('password')) {
            $user->password = \Illuminate\Support\Facades\Hash::make($request->password);
            ActivityLog::log(__("Admin đặt lại mật khẩu mới cho User ID :id (:email)", ['id' => $user->id, 'email' => $user->email]), auth()->id());
        }

        // Bật/tắt bảo mật Email OTP
        $user->email_otp_enabled = $request->has('email_otp_enabled');

        // Bật/tắt hoặc reset 2FA Google Authenticator
        if ($request->has('reset_2fa')) {
            $user->google2fa_enabled = false;
            $user->google2fa_secret = null;
            ActivityLog::log(__("Admin xoá khoá bí mật 2FA của User ID :id (:email)", ['id' => $user->id, 'email' => $user->email]), auth()->id());
        } else {
            $user->google2fa_enabled = $request->has('google2fa_enabled');
        }

        $user->save();

        ActivityLog::log(__("Cập nhật thông tin chi tiết User ID :id (:email)", ['id' => $user->id, 'email' => $user->email]), auth()->id());

        return redirect()->route('admin.users.edit', $user->id)->with('success', __('Cập nhật thông tin thành viên thành công!'));
    }

    /**
     * Điều chỉnh số dư ví của thành viên (Cộng hoặc trừ tiền thủ công).
     */
    public function adjustBalance(Request $request, User $user)
    {
        $request->validate([
            'type' => 'required|in:add,subtract',
            'amount' => 'required|numeric|min:1',
            'reason' => 'required|string|max:255',
        ], [
            'amount.min' => __('Số tiền điều chỉnh tối thiểu là 1đ.'),
            'reason.required' => __('Vui lòng nhập lý do điều chỉnh số dư để lưu nhật ký.'),
        ]);

        // Chuẩn hoá về số nguyên đồng để số dư ví không bị lẫn phần thập phân khi admin điều chỉnh tay
        $amount = \App\Helpers\MoneyHelper::round($request->amount);
        $reason = $request->reason;

        // Sử dụng Transaction để cập nhật số dư an toàn
        DB::transaction(function () use ($user, $amount, $request, $reason) {
            $userModel = User::where('id', $user->id)->lockForUpdate()->first();
            $oldBalance = $userModel->balance;

            if ($request->type === 'add') {
                $userModel->balance += $amount;
                $userModel->total_cashback += $amount;
                $msg = __('Admin cộng +:amount vào ví. Lý do: :reason', ['amount' => number_format($amount) . 'đ', 'reason' => $reason]);
                $changeAmount = $amount;
            } else {
                if ($userModel->balance < $amount) {
                    throw new \Exception(__('Số dư hiện tại không đủ để trừ.'));
                }
                $userModel->balance -= $amount;
                $msg = __('Admin trừ -:amount khỏi ví. Lý do: :reason', ['amount' => number_format($amount) . 'đ', 'reason' => $reason]);
                $changeAmount = -$amount;
            }

            $userModel->save();

            // Ghi nhận biến động số dư
            \App\Models\BalanceLog::write(
                $userModel,
                $oldBalance,
                $changeAmount,
                $userModel->balance,
                'admin_adjust',
                __('Điều chỉnh bởi Admin: :reason', ['reason' => $reason])
            );

            // Ghi nhận hoạt động
            ActivityLog::log($msg, $userModel->id);

            // Gửi thông báo đến user
            \App\Models\Notification::create([
                'user_id' => $userModel->id,
                'title' => __('Biến động số dư tài khoản'),
                'content' => $msg
            ]);
        });

        return back()->with('success', __('Điều chỉnh số dư tài khoản thành công!'));
    }

    /**
     * Danh mục toàn bộ cột dữ liệu có thể xuất ra file CSV của thành viên.
     * Giải thích: Đây là nguồn dữ liệu duy nhất (single source of truth) cho cả modal tuỳ chọn cột
     * ở giao diện Admin lẫn quá trình ghi file phía server, giúp tránh lệch nhau khi bổ sung cột mới.
     *
     * Mỗi cột gồm: label (tên hiển thị), group (nhóm hiển thị trong modal),
     * default (có được chọn sẵn hay không) và value (hàm lấy giá trị của cột đó).
     *
     * @return array
     */
    private function exportColumnDefinitions(): array
    {
        return [
            // --- Nhóm: Thông tin tài khoản ---
            'id' => [
                'label' => __('ID'),
                'group' => __('Thông tin tài khoản'),
                'default' => true,
                'value' => fn(User $u) => $u->id,
            ],
            'name' => [
                'label' => __('Họ tên'),
                'group' => __('Thông tin tài khoản'),
                'default' => true,
                'value' => fn(User $u) => $u->name,
            ],
            'email' => [
                'label' => __('Email'),
                'group' => __('Thông tin tài khoản'),
                'default' => true,
                'value' => fn(User $u) => $u->email,
            ],
            'phone' => [
                'label' => __('Số điện thoại'),
                'group' => __('Thông tin tài khoản'),
                'default' => true,
                'value' => fn(User $u) => $u->phone ?? '',
            ],
            'role' => [
                'label' => __('Vai trò'),
                'group' => __('Thông tin tài khoản'),
                'default' => true,
                'value' => fn(User $u) => $u->role,
            ],
            'role_name' => [
                'label' => __('Nhóm quyền quản trị'),
                'group' => __('Thông tin tài khoản'),
                'default' => false,
                'value' => fn(User $u) => $u->roleRelation->name ?? '',
            ],
            'status' => [
                'label' => __('Trạng thái'),
                'group' => __('Thông tin tài khoản'),
                'default' => true,
                'value' => fn(User $u) => $u->status,
            ],
            'email_verified_at' => [
                'label' => __('Ngày xác thực Email'),
                'group' => __('Thông tin tài khoản'),
                'default' => false,
                'value' => fn(User $u) => $u->email_verified_at ? $u->email_verified_at->format('d/m/Y H:i') : '',
            ],
            'created_at' => [
                'label' => __('Ngày đăng ký'),
                'group' => __('Thông tin tài khoản'),
                'default' => true,
                'value' => fn(User $u) => $u->created_at ? $u->created_at->format('d/m/Y H:i') : '',
            ],

            // --- Nhóm: Tài chính & Ví tiền ---
            'balance' => [
                'label' => __('Số dư'),
                'group' => __('Tài chính'),
                'default' => true,
                'value' => fn(User $u) => $u->balance,
            ],
            'total_cashback' => [
                'label' => __('Tổng hoàn tiền'),
                'group' => __('Tài chính'),
                'default' => true,
                'value' => fn(User $u) => $u->total_cashback,
            ],
            'total_withdrawn' => [
                'label' => __('Tổng đã rút'),
                'group' => __('Tài chính'),
                'default' => true,
                'value' => fn(User $u) => $u->total_withdrawn,
            ],
            'total_referral_earned' => [
                'label' => __('Tổng hoa hồng giới thiệu'),
                'group' => __('Tài chính'),
                'default' => false,
                'value' => fn(User $u) => $u->total_referral_earned,
            ],
            'pending_orders_count' => [
                'label' => __('Số đơn chờ duyệt'),
                'group' => __('Tài chính'),
                'default' => false,
                'value' => fn(User $u) => $u->pending_orders_count ?? 0,
            ],
            'approved_orders_count' => [
                'label' => __('Số đơn đã duyệt'),
                'group' => __('Tài chính'),
                'default' => false,
                'value' => fn(User $u) => $u->approved_orders_count ?? 0,
            ],

            // --- Nhóm: Giới thiệu (Affiliate/MLM) ---
            'referrer' => [
                'label' => __('Người giới thiệu'),
                'group' => __('Giới thiệu'),
                'default' => true,
                'value' => fn(User $u) => $u->referrer ? ($u->referrer->name . ' (' . $u->referrer->email . ')') : '',
            ],
            'referrer_id' => [
                'label' => __('ID người giới thiệu'),
                'group' => __('Giới thiệu'),
                'default' => false,
                'value' => fn(User $u) => $u->referred_by ?? '',
            ],
            'referral_code' => [
                'label' => __('Mã giới thiệu'),
                'group' => __('Giới thiệu'),
                'default' => true,
                'value' => fn(User $u) => $u->referral_code ?? '',
            ],
            'referral_clicks' => [
                'label' => __('Lượt click link giới thiệu'),
                'group' => __('Giới thiệu'),
                'default' => true,
                'value' => fn(User $u) => $u->referral_clicks,
            ],
            'referred_users_count' => [
                'label' => __('Số thành viên F1 đã giới thiệu'),
                'group' => __('Giới thiệu'),
                'default' => false,
                'value' => fn(User $u) => $u->referred_users_count ?? 0,
            ],

            // --- Nhóm: Nguồn truy cập & Thiết bị ---
            'utm_source' => [
                'label' => __('Nguồn UTM'),
                'group' => __('Nguồn & Thiết bị'),
                'default' => true,
                'value' => fn(User $u) => $u->utm_source ?? '',
            ],
            'ip_address' => [
                'label' => __('Địa chỉ IP đăng ký'),
                'group' => __('Nguồn & Thiết bị'),
                'default' => false,
                'value' => fn(User $u) => $u->ip_address ?? '',
            ],
            'country' => [
                'label' => __('Quốc gia'),
                'group' => __('Nguồn & Thiết bị'),
                'default' => false,
                'value' => fn(User $u) => $u->country ?? '',
            ],
            'register_device' => [
                'label' => __('Thiết bị đăng ký'),
                'group' => __('Nguồn & Thiết bị'),
                'default' => false,
                'value' => fn(User $u) => $u->register_device,
            ],
            'user_agent' => [
                'label' => __('User Agent (chuỗi gốc)'),
                'group' => __('Nguồn & Thiết bị'),
                'default' => false,
                'value' => fn(User $u) => $u->user_agent ?? '',
            ],
            'last_seen_at' => [
                'label' => __('Hoạt động gần nhất'),
                'group' => __('Nguồn & Thiết bị'),
                'default' => false,
                'value' => fn(User $u) => $u->last_seen_at ? $u->last_seen_at->format('d/m/Y H:i') : '',
            ],
        ];
    }

    /**
     * Làm sạch giá trị trước khi ghi vào file CSV để chống lỗ hổng chèn công thức (CSV Injection).
     *
     * Giải thích: Các trường như họ tên, nguồn UTM, User Agent, quốc gia... đều do người dùng cuối
     * tự nhập hoặc gửi lên từ trình duyệt. Nếu giá trị bắt đầu bằng các ký tự =, +, -, @, Tab hoặc
     * xuống dòng thì Excel/Google Sheets sẽ hiểu đó là một công thức và thực thi ngay khi quản trị viên
     * mở file, dẫn tới nguy cơ bị đánh cắp dữ liệu hoặc chạy lệnh độc hại trên máy quản trị viên.
     * Cách xử lý: chèn thêm dấu nháy đơn ở đầu để phần mềm bảng tính hiểu đây là văn bản thuần.
     *
     * @param mixed $value
     * @return string
     */
    private function sanitizeCsvValue($value): string
    {
        // Giữ nguyên các giá trị số (số dư, tổng tiền, số lượt click...) để Excel vẫn tính toán được
        if ($value === null || is_numeric($value)) {
            return (string) $value;
        }

        $value = (string) $value;

        // Chặn các ký tự mở đầu có thể bị hiểu nhầm thành công thức bảng tính
        if ($value !== '' && in_array($value[0], ['=', '+', '-', '@', "\t", "\r"], true)) {
            return "'" . $value;
        }

        return $value;
    }

    /**
     * Trả về danh sách cột xuất file dạng mảng gọn nhẹ để đẩy ra giao diện (modal tuỳ chọn cột).
     * Loại bỏ closure 'value' vì không thể chuyển thành JSON.
     *
     * @return array
     */
    private function exportColumnsForView(): array
    {
        $columns = [];
        foreach ($this->exportColumnDefinitions() as $key => $definition) {
            $columns[] = [
                'key' => $key,
                'label' => $definition['label'],
                'group' => $definition['group'],
                'default' => (bool) $definition['default'],
            ];
        }
        return $columns;
    }

    /**
     * Xuất danh sách người dùng ra định dạng file CSV.
     * Hỗ trợ tuỳ biến từ modal ở giao diện Admin: chọn cột nào được xuất, sắp xếp thứ tự cột,
     * phạm vi dữ liệu (toàn bộ hay theo bộ lọc đang áp dụng) và ký tự phân cách của file.
     */
    public function exportCsv(Request $request)
    {
        // Chặn tải xuống danh sách thành viên ở chế độ Demo để bảo vệ thông tin cá nhân của người dùng thử nghiệm
        if (config('app.demo')) {
            return redirect()->back()->with('error', __('Tính năng xuất danh sách thành viên bị khóa ở chế độ Demo để bảo vệ dữ liệu.'));
        }

        $definitions = $this->exportColumnDefinitions();

        // 1. Xác định danh sách cột cần xuất theo đúng thứ tự người dùng đã sắp xếp ở modal.
        // Tham số columns được gửi lên dạng chuỗi các key ngăn cách bởi dấu phẩy (ví dụ: id,name,email).
        $requestedColumns = $request->input('columns');
        if (is_string($requestedColumns)) {
            $requestedColumns = explode(',', $requestedColumns);
        }
        $requestedColumns = is_array($requestedColumns) ? $requestedColumns : [];

        // Chỉ giữ lại các key hợp lệ (whitelist) và loại bỏ trùng lặp để chống chèn dữ liệu lạ
        $selectedColumns = array_values(array_unique(array_filter(
            array_map(fn($key) => trim((string) $key), $requestedColumns),
            fn($key) => isset($definitions[$key])
        )));

        // Nếu không chọn cột nào hợp lệ (hoặc tải trực tiếp bằng link cũ) -> dùng bộ cột mặc định
        if (empty($selectedColumns)) {
            $selectedColumns = array_keys(array_filter($definitions, fn($definition) => $definition['default']));
        }

        // 2. Xác định ký tự phân cách của file CSV (Excel bản tiếng Việt thường cần dấu chấm phẩy)
        $delimiterMap = [
            'comma' => ',',
            'semicolon' => ';',
            'tab' => "\t",
        ];
        $delimiter = $delimiterMap[$request->input('delimiter', 'comma')] ?? ',';

        // 3. Xác định phạm vi dữ liệu: toàn bộ thành viên hoặc chỉ những thành viên khớp bộ lọc đang áp dụng
        $scope = $request->input('scope') === 'filtered' ? 'filtered' : 'all';
        $query = User::query();
        if ($scope === 'filtered') {
            $query = $this->applyUserFilters($query, $request);
        }

        // Nạp sẵn quan hệ cần thiết theo đúng các cột được chọn để tránh truy vấn thừa (N+1 query)
        $query->with('referrer');
        if (in_array('role_name', $selectedColumns, true)) {
            $query->with('roleRelation');
        }
        if (array_intersect(['pending_orders_count', 'approved_orders_count'], $selectedColumns)) {
            $query->withCount([
                'cashbackHistories as pending_orders_count' => fn($q) => $q->where('status', 'pending'),
                'cashbackHistories as approved_orders_count' => fn($q) => $q->where('status', 'approved'),
            ]);
        }
        if (in_array('referred_users_count', $selectedColumns, true)) {
            $query->withCount('referredUsers');
        }

        // 4. Giữ nguyên thứ tự sắp xếp đang xem ở danh sách khi xuất theo bộ lọc
        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');
        $allowedSorts = ['created_at', 'balance', 'total_cashback', 'total_withdrawn'];
        if (!in_array($sortBy, $allowedSorts)) {
            $sortBy = 'created_at';
        }
        if (!in_array($sortOrder, ['asc', 'desc'])) {
            $sortOrder = 'desc';
        }
        $query->orderBy($sortBy, $sortOrder);

        // Bổ sung khoá sắp xếp phụ theo ID để thứ tự bản ghi luôn xác định (deterministic).
        // Bắt buộc phải có: quá trình đọc dữ liệu theo lô dùng LIMIT/OFFSET, nếu nhiều bản ghi
        // trùng giá trị cột sắp xếp (ví dụ đăng ký cùng một thời điểm) thì MySQL có thể trả về
        // thứ tự khác nhau giữa các lô, làm file xuất ra bị trùng lặp hoặc thiếu bản ghi.
        $query->orderBy('id', 'desc');

        // Ghi nhận hoạt động xuất dữ liệu nhạy cảm để phục vụ giám sát bảo mật
        ActivityLog::log(__('Xuất danh sách thành viên ra file CSV (:count cột, phạm vi: :scope)', [
            'count' => count($selectedColumns),
            'scope' => $scope === 'filtered' ? __('theo bộ lọc') : __('toàn bộ'),
        ]));

        $fileName = 'users_export_' . now()->format('YmdHis') . '.csv';

        $headers = array(
            "Content-type"        => "text/csv; charset=UTF-8",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        );

        $callback = function() use ($query, $definitions, $selectedColumns, $delimiter) {
            $file = fopen('php://output', 'w');

            // Hỗ trợ tiếng Việt có dấu trong Excel (BOM)
            fputs($file, "\xEF\xBB\xBF");

            // Dòng tiêu đề được ghi theo đúng thứ tự cột người dùng đã sắp xếp.
            // Luôn truyền tham số $escape rỗng vì từ PHP 8.4 việc bỏ trống tham số này sẽ sinh cảnh báo
            // Deprecated, nội dung cảnh báo có thể bị chèn thẳng vào file CSV tải về làm hỏng dữ liệu.
            fputcsv($file, array_map(fn($key) => $definitions[$key]['label'], $selectedColumns), $delimiter, '"', '');

            // Duyệt dữ liệu theo từng lô 500 bản ghi để không bị tràn bộ nhớ khi danh sách thành viên lớn
            $query->chunk(500, function ($users) use ($file, $definitions, $selectedColumns, $delimiter) {
                foreach ($users as $user) {
                    $row = [];
                    foreach ($selectedColumns as $key) {
                        // Làm sạch từng ô dữ liệu để chống lỗ hổng chèn công thức khi mở bằng Excel
                        $row[] = $this->sanitizeCsvValue($definitions[$key]['value']($user));
                    }
                    fputcsv($file, $row, $delimiter, '"', '');
                }

                // Đẩy dữ liệu của lô hiện tại xuống trình duyệt ngay, tránh dồn toàn bộ file vào bộ nhớ đệm
                if (ob_get_level() > 0) {
                    ob_flush();
                }
                flush();
            });

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Xóa thành viên và toàn bộ dữ liệu liên quan khỏi hệ thống.
     * Chống Race Condition bằng cách chạy trong DB Transaction và Lock.
     */
    public function destroy(User $user)
    {
        // 1. Ràng buộc bảo mật: Không được tự xóa tài khoản của chính mình
        if ($user->id === auth()->id()) {
            return back()->with('error', __('Bạn không thể tự xóa tài khoản của chính mình.'));
        }

        try {
            DB::transaction(function () use ($user) {
                // 2. Lock bản ghi user để tránh tranh chấp dữ liệu trong quá trình xóa
                $userModel = User::where('id', $user->id)->lockForUpdate()->first();
                if (!$userModel) {
                    throw new \Exception(__('Thành viên không tồn tại.'));
                }

                $userEmail = $userModel->email;

                // 3. Chủ động xóa các dữ liệu liên quan mà DB không tự động cascade sạch sẽ
                // Xóa nhật ký hoạt động của tài khoản này
                DB::table('activity_logs')->where('user_id', $userModel->id)->delete();

                // Xóa token khôi phục mật khẩu liên quan đến email
                DB::table('password_reset_tokens')->where('email', $userEmail)->delete();

                // Xóa các session đang hoạt động của người dùng này
                DB::table('sessions')->where('user_id', $userModel->id)->delete();

                // 4. Thực hiện xóa bản ghi người dùng (DB sẽ tự động cascade xóa cashback_histories, withdrawals, referrals, referral_commissions, daily_checkins, notifications, balance_logs, short_links...)
                $userModel->delete();

                // 5. Ghi log hoạt động quản trị viên
                ActivityLog::log(__("Xóa thành viên khỏi hệ thống: :email", ['email' => $userEmail]), auth()->id());
            });

            return redirect()->route('admin.users.index')->with('success', __('Xóa thành viên và toàn bộ dữ liệu liên quan thành công!'));
        } catch (\Exception $e) {
            // Không lộ chi tiết exception, log vào hệ thống
            logger()->error('Lỗi khi xóa thành viên: ' . $e->getMessage());
            return back()->with('error', __('Có lỗi xảy ra trong quá trình xóa thành viên. Vui lòng thử lại sau.'));
        }
    }

    /**
     * Xóa hàng loạt thành viên và dữ liệu liên kết.
     * Chống Race Condition bằng DB Transaction và Lock.
     */
    public function bulkDestroy(Request $request)
    {
        // 1. Kiểm tra tham số đầu vào và tích chọn checkbox xác nhận xóa
        $request->validate([
            'user_ids' => 'required|string',
            'confirm_checkbox' => 'required',
        ]);

        // Yêu cầu quản trị viên phải tích chọn xác nhận xóa trước khi thực thi
        if (!$request->boolean('confirm_checkbox')) {
            if ($request->wantsJson()) {
                return response()->json(['status' => 'error', 'message' => __('Bạn phải tích chọn ô xác nhận trước khi thực hiện xóa.')]);
            }
            return back()->with('error', __('Bạn phải tích chọn ô xác nhận trước khi thực hiện xóa.'));
        }

        $userIds = explode(',', $request->user_ids);
        $userIds = array_filter(array_map('intval', $userIds));

        // Ràng buộc bảo mật: Không xóa tài khoản chính mình
        $userIds = array_diff($userIds, [auth()->id()]);

        if (empty($userIds)) {
            if ($request->wantsJson()) {
                return response()->json(['status' => 'error', 'message' => __('Không có thành viên hợp lệ nào để xóa.')]);
            }
            return back()->with('error', __('Không có thành viên hợp lệ nào để xóa.'));
        }

        try {
            // Thực thi xóa hàng loạt trong Database Transaction để đảm bảo tính toàn vẹn dữ liệu
            DB::transaction(function () use ($userIds) {
                // Sử dụng lockForUpdate để khóa các hàng dữ liệu tránh Race Condition
                $users = User::whereIn('id', $userIds)->lockForUpdate()->get();

                foreach ($users as $userModel) {
                    $userEmail = $userModel->email;

                    // Xóa nhật ký hoạt động của tài khoản này
                    DB::table('activity_logs')->where('user_id', $userModel->id)->delete();

                    // Xóa token khôi phục mật khẩu liên quan đến email
                    DB::table('password_reset_tokens')->where('email', $userEmail)->delete();

                    // Xóa các session đang hoạt động của người dùng này
                    DB::table('sessions')->where('user_id', $userModel->id)->delete();

                    // Thực hiện xóa bản ghi người dùng
                    $userModel->delete();

                    // Ghi log hoạt động quản trị viên
                    ActivityLog::log(__("Xóa thành viên khỏi hệ thống (Hành động hàng loạt): :email", ['email' => $userEmail]), auth()->id());
                }
            });

            // Trả về JSON nếu request yêu cầu (AJAX)
            if ($request->wantsJson()) {
                return response()->json(['status' => 'success', 'message' => __('Đã xóa hàng loạt các thành viên được chọn thành công!')]);
            }

            return redirect()->route('admin.users.index')->with('success', __('Đã xóa hàng loạt các thành viên được chọn thành công!'));
        } catch (\Exception $e) {
            logger()->error('Lỗi khi xóa thành viên hàng loạt: ' . $e->getMessage());
            
            // Trả về JSON lỗi nếu request yêu cầu (AJAX)
            if ($request->wantsJson()) {
                return response()->json(['status' => 'error', 'message' => __('Có lỗi xảy ra trong quá trình xóa hàng loạt thành viên: :error', ['error' => $e->getMessage()])]);
            }

            return back()->with('error', __('Có lỗi xảy ra trong quá trình xóa hàng loạt thành viên. Chi tiết: :error', ['error' => $e->getMessage()]));
        }
    }

    /**
     * Điều chỉnh số dư hàng loạt cho nhiều người dùng.
     * Chống Race Condition bằng DB Transaction và Lock.
     */
    public function bulkAdjustBalance(Request $request)
    {
        $request->validate([
            'user_ids' => 'required|string',
            'type' => 'required|in:add,subtract',
            'amount' => 'required|numeric|min:1',
            'reason' => 'required|string|max:255',
        ], [
            'amount.min' => __('Số tiền điều chỉnh tối thiểu là 1đ.'),
            'reason.required' => __('Vui lòng nhập lý do điều chỉnh số dư để lưu nhật ký.'),
        ]);

        $userIds = explode(',', $request->user_ids);
        $userIds = array_filter(array_map('intval', $userIds));
        // Chuẩn hoá về số nguyên đồng để số dư ví không bị lẫn phần thập phân khi admin điều chỉnh tay
        $amount = \App\Helpers\MoneyHelper::round($request->amount);
        $reason = $request->reason;
        $type = $request->type;

        if (empty($userIds)) {
            if ($request->wantsJson()) {
                return response()->json(['status' => 'error', 'message' => __('Không có thành viên nào được chọn.')]);
            }
            return back()->with('error', __('Không có thành viên nào được chọn.'));
        }

        try {
            // Chạy transaction để đảm bảo toàn bộ thành viên đều được cộng/trừ thành công hoặc rollback
            DB::transaction(function () use ($userIds, $amount, $type, $reason) {
                // Lock các bản ghi users để tránh Race Condition khi có request song song
                $users = User::whereIn('id', $userIds)->lockForUpdate()->get();

                foreach ($users as $userModel) {
                    $oldBalance = $userModel->balance;

                    if ($type === 'add') {
                        $userModel->balance += $amount;
                        $userModel->total_cashback += $amount;
                        $msg = __('Admin cộng hàng loạt +:amount vào ví. Lý do: :reason', ['amount' => number_format($amount) . 'đ', 'reason' => $reason]);
                        $changeAmount = $amount;
                    } else {
                        if ($userModel->balance < $amount) {
                            throw new \Exception(__('Thành viên :email không đủ số dư để thực hiện giao dịch trừ tiền (Số dư hiện tại: :balance).', ['email' => $userModel->email, 'balance' => number_format($oldBalance) . 'đ']));
                        }
                        $userModel->balance -= $amount;
                        $msg = __('Admin trừ hàng loạt -:amount khỏi ví. Lý do: :reason', ['amount' => number_format($amount) . 'đ', 'reason' => $reason]);
                        $changeAmount = -$amount;
                    }

                    $userModel->save();

                    // Ghi nhận biến động số dư
                    \App\Models\BalanceLog::write(
                        $userModel,
                        $oldBalance,
                        $changeAmount,
                        $userModel->balance,
                        'admin_adjust',
                        __('Điều chỉnh hàng loạt bởi Admin: :reason', ['reason' => $reason])
                    );

                    // Ghi nhận hoạt động
                    ActivityLog::log($msg, $userModel->id);

                    // Gửi thông báo đến user
                    \App\Models\Notification::create([
                        'user_id' => $userModel->id,
                        'title' => __('Biến động số dư tài khoản'),
                        'content' => $msg
                    ]);
                }
            });

            // Trả về JSON nếu request yêu cầu (AJAX)
            if ($request->wantsJson()) {
                return response()->json(['status' => 'success', 'message' => __('Đã điều chỉnh số dư hàng loạt thành công cho các thành viên được chọn!')]);
            }

            return back()->with('success', __('Đã điều chỉnh số dư hàng loạt thành công cho các thành viên được chọn!'));
        } catch (\Exception $e) {
            logger()->error('Lỗi khi điều chỉnh số dư hàng loạt: ' . $e->getMessage());
            
            // Trả về JSON lỗi nếu request yêu cầu (AJAX)
            if ($request->wantsJson()) {
                return response()->json(['status' => 'error', 'message' => __('Giao dịch thất bại: :error', ['error' => $e->getMessage()])]);
            }

            return back()->with('error', __('Giao dịch thất bại: :error', ['error' => $e->getMessage()]));
        }
    }

    /**
     * Lấy danh sách các phiên hoạt động qua AJAX.
     */
    public function getSessionsAjax(Request $request, User $user)
    {
        $sessions = DB::table('sessions')
            ->where('user_id', $user->id)
            ->orderBy('last_activity', 'desc')
            ->get()
            ->map(function ($session) {
                $parsed = $this->parseUserAgent($session->user_agent);
                return [
                    'id' => $session->id,
                    'ip_address' => $session->ip_address ?? 'N/A',
                    'device_os' => $parsed['device'],
                    'browser' => $parsed['browser'],
                    'icon' => $parsed['icon'],
                    'created_at' => \Carbon\Carbon::createFromTimestamp($session->last_activity)->format('d/m/Y H:i:s'),
                    'time_ago' => \Carbon\Carbon::createFromTimestamp($session->last_activity)->diffForHumans(),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $sessions,
            'total' => count($sessions),
            'has_more' => false
        ]);
    }

    /**
     * Admin đăng xuất một phiên thiết bị của user.
     */
    public function logoutSession(Request $request, User $user, $id)
    {
        $deleted = DB::table('sessions')
            ->where('id', $id)
            ->where('user_id', $user->id)
            ->delete();

        if ($deleted) {
            ActivityLog::log(__("Admin đăng xuất thiết bị của User ID :id (:email)", ['id' => $user->id, 'email' => $user->email]), auth()->id());
            ActivityLog::log('Thiết bị đã bị đăng xuất bởi quản trị viên', $user->id);
            return back()->with('success', __('Đã đăng xuất thiết bị của người dùng thành công.'));
        }

        return back()->with('error', __('Không tìm thấy phiên thiết bị hoặc phiên đã hết hạn.'));
    }

    /**
     * API thống kê số lượng user đăng ký theo thời gian (tuần/tháng/năm).
     * Trả về dữ liệu JSON phục vụ biểu đồ Chart.js ở frontend.
     */
    /**
     * Lấy dữ liệu thống kê thành viên đăng ký theo chu kỳ thời gian, thiết bị và quốc gia.
     * Giải thích: Thống kê số lượng thành viên đăng ký mới theo thời gian (Tuần/Tháng/Năm) để vẽ biểu đồ,
     * đồng thời phân tích thông tin thiết bị (User Agent) và quốc gia đăng ký của các thành viên này
     * giúp quản trị viên nắm rõ nguồn truy cập của người dùng để tối ưu chiến dịch marketing hoặc phát hiện tài khoản ảo (clone).
     */
    public function registrationStats(Request $request)
    {
        // Whitelist khoảng thời gian hợp lệ để tránh tham số giả mạo
        $period = $request->input('period', 'week');
        if (!in_array($period, ['week', 'month', 'year'])) {
            $period = 'week';
        }

        $labels = [];
        $data = [];

        // Xác định mốc thời gian bắt đầu dựa trên period được chọn
        $startDate = null;
        $endDate = now()->endOfDay();

        if ($period === 'week') {
            // Thống kê 7 ngày gần nhất: nhóm theo từng ngày
            $startDate = now()->subDays(6)->startOfDay();
            for ($i = 6; $i >= 0; $i--) {
                $date = now()->subDays($i);
                $labels[] = $date->format('d/m');
                $data[] = User::whereDate('created_at', $date->toDateString())->count();
            }
        } elseif ($period === 'month') {
            // Thống kê 30 ngày gần nhất: nhóm theo từng ngày
            $startDate = now()->subDays(29)->startOfDay();
            for ($i = 29; $i >= 0; $i--) {
                $date = now()->subDays($i);
                $labels[] = $date->format('d/m');
                $data[] = User::whereDate('created_at', $date->toDateString())->count();
            }
        } else {
            // Thống kê 12 tháng gần nhất: nhóm theo tháng
            $startDate = now()->subMonths(11)->startOfMonth();
            for ($i = 11; $i >= 0; $i--) {
                $date = now()->subMonths($i);
                $labels[] = __('Tháng') . ' ' . $date->format('m/Y');
                $data[] = User::whereYear('created_at', $date->year)
                    ->whereMonth('created_at', $date->month)
                    ->count();
            }
        }

        // Tính tổng user đăng ký trong khoảng thời gian đang xem
        $total = array_sum($data);

        // 1. Thống kê theo quốc gia của các user đăng ký trong khoảng thời gian này
        // Sử dụng IFNULL và NULLIF để gom các giá trị rỗng hoặc NULL về chuỗi 'Unknown' để dễ xử lý dịch thuật
        $countries = User::whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('IFNULL(NULLIF(country, ""), "Unknown") as country_name, count(*) as count')
            ->groupBy('country_name')
            ->orderByDesc('count')
            ->limit(10)
            ->get();

        $countryLabels = [];
        $countryData = [];
        foreach ($countries as $c) {
            $countryLabels[] = $c->country_name === 'Unknown' ? __('Không xác định') : $c->country_name;
            $countryData[] = $c->count;
        }

        // 2. Thống kê theo thiết bị của các user đăng ký trong khoảng thời gian này
        // Vì trường user_agent là text dài và phức tạp, ta sẽ lấy danh sách thô đã nhóm theo user_agent từ database
        // sau đó phân tích qua hàm PHP parseUserAgent để cộng dồn số lượng theo hệ điều hành/thiết bị
        $userAgents = User::whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('user_agent, count(*) as count')
            ->groupBy('user_agent')
            ->get();

        $deviceCounts = [];
        foreach ($userAgents as $ua) {
            $parsed = $this->parseUserAgent($ua->user_agent);
            $deviceName = $parsed['device'];
            
            if (!isset($deviceCounts[$deviceName])) {
                $deviceCounts[$deviceName] = 0;
            }
            $deviceCounts[$deviceName] += $ua->count;
        }
        
        // Sắp xếp các thiết bị theo số lượng giảm dần và lấy top 10 thiết bị hàng đầu
        arsort($deviceCounts);
        $topDevices = array_slice($deviceCounts, 0, 10, true);

        $deviceLabels = array_keys($topDevices);
        $deviceData = array_values($topDevices);

        // 3. Thống kê theo nguồn UTM (utm_source) của các user đăng ký trong khoảng thời gian này
        // Sử dụng IFNULL và NULLIF để gom các giá trị rỗng hoặc NULL về chuỗi 'Direct' biểu thị truy cập trực tiếp
        $utmSources = User::whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('IFNULL(NULLIF(utm_source, ""), "Direct") as utm_name, count(*) as count')
            ->groupBy('utm_name')
            ->orderByDesc('count')
            ->limit(10)
            ->get();

        $utmLabels = [];
        $utmData = [];
        foreach ($utmSources as $u) {
            $utmLabels[] = $u->utm_name === 'Direct' ? __('Trực tiếp (Direct)') : $u->utm_name;
            $utmData[] = $u->count;
        }

        return response()->json([
            'success' => true,
            'labels' => $labels,
            'data' => $data,
            'total' => $total,
            'period' => $period,
            'device_labels' => $deviceLabels,
            'device_data' => $deviceData,
            'country_labels' => $countryLabels,
            'country_data' => $countryData,
            'utm_labels' => $utmLabels,
            'utm_data' => $utmData,
        ]);
    }

    /**
     * Phân tích User Agent thô để nhận dạng Hệ điều hành, Trình duyệt và Icon.
     * Giải thích: Chuyển đổi chuỗi user agent dài dòng từ trình duyệt thành tên thiết bị và trình duyệt gọn gàng dễ đọc.
     */
    private function parseUserAgent($userAgent)
    {
        if (empty($userAgent)) {
            return [
                'device' => __('Thiết bị không xác định'),
                'browser' => __('Trình duyệt không xác định'),
                'icon' => 'monitor'
            ];
        }

        $os = __('OS không xác định');
        $icon = 'monitor';
        
        if (preg_match('/iphone/i', $userAgent)) {
            $os = 'iPhone (iOS)';
            $icon = 'smartphone';
        } elseif (preg_match('/ipad/i', $userAgent)) {
            $os = 'iPad (iPadOS)';
            $icon = 'tablet';
        } elseif (preg_match('/android/i', $userAgent)) {
            $os = 'Android';
            $icon = 'smartphone';
        } elseif (preg_match('/macintosh|mac os x/i', $userAgent)) {
            $os = 'macOS';
            $icon = 'laptop';
        } elseif (preg_match('/windows|win32/i', $userAgent)) {
            $os = 'Windows';
            $icon = 'monitor';
        } elseif (preg_match('/linux/i', $userAgent)) {
            $os = 'Linux';
            $icon = 'monitor';
        }

        $browser = __('Trình duyệt không xác định');
        if (preg_match('/chrome/i', $userAgent) && !preg_match('/edge|edg/i', $userAgent) && !preg_match('/opr/i', $userAgent)) {
            $browser = 'Chrome';
        } elseif (preg_match('/safari/i', $userAgent) && !preg_match('/chrome/i', $userAgent)) {
            $browser = 'Safari';
        } elseif (preg_match('/firefox/i', $userAgent)) {
            $browser = 'Firefox';
        } elseif (preg_match('/edge|edg/i', $userAgent)) {
            $browser = 'Microsoft Edge';
        } elseif (preg_match('/opr/i', $userAgent)) {
            $browser = 'Opera';
        } elseif (preg_match('/fb_iab|fbav/i', $userAgent)) {
            $browser = 'Facebook App';
        }

        return [
            'device' => $os,
            'browser' => $browser,
            'icon' => $icon
        ];
    }

    /**
     * Dọn dẹp thủ công toàn bộ lịch sử biến động số dư dòng tiền (balance_logs).
     */
    public function clearBalanceLogs(Request $request)
    {
        try {
            \App\Models\BalanceLog::query()->delete();
            ActivityLog::log("Dọn dẹp toàn bộ lịch sử biến động số dư", auth()->id());
            return back()->with('success', __('Đã dọn dẹp toàn bộ lịch sử biến động số dư thành công!'));
        } catch (\Exception $e) {
            return back()->with('error', __('Lỗi khi dọn dẹp biến động số dư: :msg', ['msg' => $e->getMessage()]));
        }
    }
}
