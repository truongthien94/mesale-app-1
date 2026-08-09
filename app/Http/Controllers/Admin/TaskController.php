<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\UserTask;
use App\Models\User;
use App\Models\ActivityLog;
use App\Services\TaskService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TaskController extends Controller
{
    public function __construct(private TaskService $taskService) {}

    /**
     * Danh sách nhiệm vụ + tab submissions.
     */
    public function index(Request $request)
    {
        $tab = $request->get('tab', 'tasks');

        // --- Tab: Danh sách tasks ---
        $tasksQuery = Task::with('creator')->orderBy('sort_order')->orderBy('id');

        if ($tab === 'tasks') {
            if ($search = $request->get('search')) {
                $tasksQuery->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
            }
            if ($request->get('type')) {
                $tasksQuery->where('type', $request->type);
            }
            if ($request->get('action')) {
                $tasksQuery->where('action', $request->action);
            }
            if ($request->get('status') !== null && $request->get('status') !== '') {
                $tasksQuery->where('is_active', (bool) $request->status);
            }
        }

        $limitTasks = $request->integer('limit', 15);
        if ($limitTasks < 1 || $limitTasks > 500) $limitTasks = 15;
        $tasks = $tasksQuery->withCount([
            'userTasks',
            // Đếm số lượng nhiệm vụ thành viên đã nhận thưởng (claimed) để phục vụ tính số tiền đã chi trả
            'userTasks as claimed_tasks_count' => function ($query) {
                $query->where('status', 'claimed');
            }
        ])->paginate($limitTasks, ['*'], 'tasks_page')->withQueryString();

        // Stats tổng quan
        $totalTasks    = Task::count();
        $activeTasks   = Task::where('is_active', true)->count();
        $totalClaimed  = UserTask::where('status', 'claimed')->count();
        $pendingClaim  = UserTask::where('status', 'completed')->count();

        // Số yêu cầu xác nhận hoàn thành đang chờ Admin kiểm duyệt (nhiệm vụ thủ công)
        $pendingReview = UserTask::where('status', 'pending')->count();

        // --- Tab: Submissions (user tasks) ---
        // Luôn đẩy các yêu cầu đang chờ duyệt lên đầu danh sách để Admin xử lý trước
        $submissionsQuery = UserTask::with(['user', 'task'])
            ->orderByRaw("CASE WHEN status = 'pending' THEN 0 ELSE 1 END")
            ->orderBy('id', 'desc');

        if ($tab === 'submissions') {
            if ($search = $request->get('search')) {
                $submissionsQuery->where(function ($q) use ($search) {
                    $q->whereHas('user', function ($uq) use ($search) {
                        $uq->where('name', 'like', "%{$search}%")
                           ->orWhere('email', 'like', "%{$search}%");
                    })->orWhereHas('task', function ($tq) use ($search) {
                        $tq->where('title', 'like', "%{$search}%");
                    });
                });
            }
            if ($request->get('status') && in_array($request->status, ['pending', 'in_progress', 'completed', 'claimed'])) {
                $submissionsQuery->where('status', $request->status);
            }
            if ($taskId = $request->integer('task_id')) {
                $submissionsQuery->where('task_id', $taskId);
            }
        }

        $limitSubs = $request->integer('limit', 15);
        if ($limitSubs < 1 || $limitSubs > 500) $limitSubs = 15;
        $submissions = $submissionsQuery->paginate($limitSubs, ['*'], 'subs_page')->withQueryString();

        $allTasks = Task::orderBy('title')->get(['id', 'title']);

        return view('admin.tasks.index', compact(
            'tab', 'tasks', 'submissions', 'allTasks',
            'totalTasks', 'activeTasks', 'totalClaimed', 'pendingClaim', 'pendingReview'
        ));
    }

    /**
     * Bật/tắt toàn bộ tính năng Nhiệm vụ nhận thưởng.
     */
    public function toggleFeature()
    {
        $current = \App\Models\Setting::getVal('tasks_enabled', '0');
        $new     = $current === '1' ? '0' : '1';

        \App\Models\Setting::setVal('tasks_enabled', $new);

        ActivityLog::log(
            ($new === '1' ? 'Bật' : 'Tắt') . ' tính năng Nhiệm vụ nhận thưởng',
            auth()->id()
        );

        return back()->with('success', $new === '1'
            ? __('Đã bật tính năng Nhiệm vụ nhận thưởng. Thành viên có thể xem và làm nhiệm vụ.')
            : __('Đã tắt tính năng Nhiệm vụ nhận thưởng. Thành viên sẽ không thấy mục này.'));
    }

    /**
     * Cập nhật cấu hình chung của chức năng Nhiệm vụ nhận thưởng.
     */
    public function updateConfig(Request $request)
    {
        // Xác thực dữ liệu đầu vào cho cấu hình
        $request->validate([
            'tasks_enabled' => 'required|boolean',
            'tasks_title'   => 'nullable|string|max:255',
            'tasks_intro'   => 'nullable|string|max:1000',
        ]);

        // Lưu cấu hình vào CSDL
        \App\Models\Setting::setVal('tasks_enabled', $request->tasks_enabled);
        \App\Models\Setting::setVal('tasks_title', $request->input('tasks_title', ''));
        \App\Models\Setting::setVal('tasks_intro', $request->input('tasks_intro', ''));

        // Xóa cache cấu hình để áp dụng ngay lập tức ngoài frontend và admin
        \Illuminate\Support\Facades\Cache::forget('setting.tasks_enabled');
        \Illuminate\Support\Facades\Cache::forget('setting.tasks_title');
        \Illuminate\Support\Facades\Cache::forget('setting.tasks_intro');

        $statusText = $request->tasks_enabled ? 'BẬT' : 'TẮT';
        // Lưu nhật ký hoạt động của admin để theo dõi bảo mật
        ActivityLog::log("Cập nhật cấu hình chức năng Nhiệm vụ: {$statusText}", auth()->id());

        return redirect()->route('admin.tasks.index')
            ->with('success', __('Đã cập nhật cấu hình Nhiệm vụ thành công!'));
    }

    /**
     * Tạo mới nhiệm vụ.
     */
    private function validationRules(Request $request): array
    {
        $maxTarget = $request->input('action') === 'profile' ? 3 : 9999;
        return [
            'title'                  => 'required|string|max:255',
            // Hai trường dưới đây nhận mã HTML từ trình soạn thảo trực quan nên cần giới hạn dài hơn văn bản thuần
            'description'            => 'nullable|string|max:5000',
            'guide'                  => 'nullable|string|max:15000',
            'type'                   => 'required|in:daily,weekly,one_time',
            'action'                 => 'required|in:profile,referral,cashback,checkin,withdraw,save_product,custom',
            'referral_require_order' => 'boolean',
            'target_count'           => "required|integer|min:1|max:{$maxTarget}",
            'min_order_amount'       => 'nullable|numeric|min:0', // Giá trị đơn hàng tối thiểu
            'reward_amount'          => 'required|numeric|min:0|max:100000000',
            'is_active'              => 'required|boolean',
            'start_at'               => 'nullable|date',
            'end_at'                 => 'nullable|date|after_or_equal:start_at',
            'icon'                   => 'nullable|string|max:50',
            'badge_color'            => 'nullable|in:orange,blue,green,purple,red,yellow,pink,cyan',
            'sort_order'             => 'nullable|integer|min:0|max:9999',
        ];
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->validationRules($request));

        // Lọc sạch mã HTML của phần Mô tả ngắn và Hướng dẫn thực hiện trước khi lưu (chống Stored XSS)
        $validated['description'] = Task::sanitizeHtmlContent($validated['description'] ?? null);
        $validated['guide']       = Task::sanitizeHtmlContent($validated['guide'] ?? null);

        $validated['created_by'] = auth()->id();
        $validated['sort_order'] = $validated['sort_order'] ?? 0;

        $task = Task::create($validated);
        ActivityLog::log("Tạo nhiệm vụ mới: {$task->title} (ID: {$task->id})", auth()->id());

        return redirect()->route('admin.tasks.index', ['tab' => 'tasks'])
            ->with('success', __('Tạo nhiệm vụ thành công!'));
    }

    /**
     * Cập nhật nhiệm vụ.
     */
    public function update(Request $request, Task $task)
    {
        $validated = $request->validate($this->validationRules($request));

        // Lọc sạch mã HTML của phần Mô tả ngắn và Hướng dẫn thực hiện trước khi lưu (chống Stored XSS)
        $validated['description'] = Task::sanitizeHtmlContent($validated['description'] ?? null);
        $validated['guide']       = Task::sanitizeHtmlContent($validated['guide'] ?? null);

        $task->update($validated);
        ActivityLog::log("Cập nhật nhiệm vụ: {$task->title} (ID: {$task->id})", auth()->id());

        return redirect()->route('admin.tasks.index', ['tab' => 'tasks'])
            ->with('success', __('Cập nhật nhiệm vụ thành công!'));
    }

    /**
     * Xóa nhiệm vụ.
     */
    public function destroy(Task $task)
    {
        $title = $task->title;
        $task->delete();
        ActivityLog::log("Xóa nhiệm vụ: {$title}", auth()->id());

        return redirect()->route('admin.tasks.index', ['tab' => 'tasks'])
            ->with('success', __('Đã xóa nhiệm vụ thành công!'));
    }

    /**
     * Bật/tắt trạng thái active của nhiệm vụ.
     */
    public function toggleStatus(Task $task)
    {
        $task->update(['is_active' => !$task->is_active]);
        $status = $task->is_active ? 'BẬT' : 'TẮT';
        ActivityLog::log("Chuyển trạng thái nhiệm vụ '{$task->title}': {$status}", auth()->id());

        return response()->json([
            'success'   => true,
            'is_active' => $task->is_active,
            'message'   => __("Đã :status nhiệm vụ thành công!", ['status' => $status]),
        ]);
    }

    /**
     * Admin xác nhận thủ công một submission (dành cho action = custom).
     */
    public function approveSubmission(Request $request, int $submissionId)
    {
        $userTask = UserTask::with(['user', 'task'])->findOrFail($submissionId);

        if ($userTask->task->action !== 'custom') {
            return back()->with('error', __('Chỉ có thể duyệt thủ công nhiệm vụ loại Tùy chỉnh (custom). Nhiệm vụ tự động được hệ thống xác nhận.'));
        }

        if ($userTask->status === 'claimed') {
            return back()->with('error', __('Thành viên đã nhận thưởng rồi.'));
        }

        $result = $this->taskService->adminApprove($userTask->user, $userTask->task);

        if (!$result['success']) {
            return back()->with('error', $result['message']);
        }

        return back()->with('success', $result['message']);
    }

    /**
     * Admin từ chối / reset submission về in_progress.
     */
    public function rejectSubmission(Request $request, int $submissionId)
    {
        $request->validate(['notes' => 'nullable|string|max:500']);

        $userTask = UserTask::with(['user', 'task'])->findOrFail($submissionId);

        if ($userTask->status === 'claimed') {
            return back()->with('error', __('Không thể từ chối vì thành viên đã nhận thưởng.'));
        }

        // Lọc sạch thẻ HTML trong lý do từ chối trước khi lưu và hiển thị lại cho thành viên
        $reason = trim(strip_tags((string) $request->input('notes'))) ?: null;

        // Chỉ nhiệm vụ thủ công mới có luồng thành viên gửi yêu cầu xác nhận nên mới lưu lý do từ chối
        $isCustomTask = optional($userTask->task)->action === 'custom';

        $userTask->update([
            'status'        => 'in_progress',
            'progress'      => 0,
            'completed_at'  => null,
            'reject_reason' => $isCustomTask ? $reason : null,
            'reviewed_at'   => now(),
            'reviewed_by'   => auth()->id(),
        ]);

        ActivityLog::log("Admin từ chối/reset nhiệm vụ '{$userTask->task->title}' của user #{$userTask->user_id}", auth()->id());

        // Gửi thông báo kèm lý do để thành viên biết đường khắc phục và gửi lại yêu cầu.
        // Nhiệm vụ tự động không có bước gửi yêu cầu nên không báo "bị từ chối" gây khó hiểu cho thành viên.
        if ($isCustomTask && $userTask->user) {
            $content = __('Yêu cầu xác nhận nhiệm vụ ":title" của bạn chưa được duyệt.', [
                'title' => optional($userTask->task)->title,
            ]);

            if ($reason) {
                $content .= ' ' . __('Lý do: :reason.', ['reason' => $reason]);
            }

            $content .= ' ' . __('Bạn có thể khắc phục và gửi lại yêu cầu.');

            \App\Models\Notification::create([
                'user_id' => $userTask->user_id,
                'title'   => __('Yêu cầu xác nhận nhiệm vụ bị từ chối'),
                'content' => $content,
            ]);
        }

        return back()->with('success', __('Đã từ chối và reset tiến độ nhiệm vụ.'));
    }

    /**
     * Cập nhật thứ tự sắp xếp nhiệm vụ (drag & drop AJAX).
     */
    public function updateOrder(Request $request)
    {
        $request->validate(['orders' => 'required|array', 'orders.*' => 'integer']);

        DB::transaction(function () use ($request) {
            foreach ($request->orders as $sort => $id) {
                Task::where('id', $id)->update(['sort_order' => $sort]);
            }
        });

        return response()->json(['success' => true, 'message' => __('Đã cập nhật thứ tự nhiệm vụ.')]);
    }

    /**
     * Xóa hàng loạt nhiệm vụ.
     */
    public function bulkDestroy(Request $request)
    {
        $request->validate([
            'task_ids'     => 'required|string',
            'confirm_text' => 'required|string',
        ]);

        if (strtoupper($request->confirm_text) !== 'XÓA HÀNG LOẠT') {
            return response()->json(['status' => 'error', 'message' => __('Từ khóa xác nhận không chính xác.')]);
        }

        $taskIds = array_filter(array_map('intval', explode(',', $request->task_ids)));
        if (empty($taskIds)) {
            return response()->json(['status' => 'error', 'message' => __('Không có nhiệm vụ hợp lệ.')]);
        }

        DB::transaction(function () use ($taskIds) {
            foreach (Task::whereIn('id', $taskIds)->lockForUpdate()->get() as $task) {
                $title = $task->title;
                $task->delete();
                ActivityLog::log("Xóa hàng loạt nhiệm vụ: {$title}", auth()->id());
            }
        });

        return response()->json([
            'status'  => 'success',
            'message' => __('Đã xóa :count nhiệm vụ thành công.', ['count' => count($taskIds)]),
        ]);
    }

    /**
     * Lấy dữ liệu thống kê nhiệm vụ làm của thành viên theo tuần/tháng/năm để hiển thị biểu đồ Chart.js.
     * Thống kê này phục vụ phân tích số lượng nhiệm vụ đang làm, chờ duyệt, đã hoàn thành và chi phí trả thưởng tương ứng.
     */
    public function taskStats(Request $request)
    {
        // Xác định khoảng thời gian lọc (mặc định là tuần 'week')
        $period = $request->input('period', 'week');
        if (!in_array($period, ['week', 'month', 'year'])) {
            $period = 'week';
        }

        // Cấu hình các tham số ngày bắt đầu và định dạng ngày gom nhóm dựa trên khoảng thời gian
        if ($period === 'week') {
            $startDate = now()->subDays(6)->startOfDay();
            $groupFormat = '%Y-%m-%d';
            $labelFormat = 'd/m';
        } elseif ($period === 'month') {
            $startDate = now()->subDays(29)->startOfDay();
            $groupFormat = '%Y-%m-%d';
            $labelFormat = 'd/m';
        } else {
            $startDate = now()->subMonths(11)->startOfMonth();
            $groupFormat = '%Y-%m';
            $labelFormat = null;
        }

        // Truy vấn gom nhóm số lượng nhiệm vụ của thành viên và tổng tiền thưởng tương ứng.
        // Cần join bảng tasks để lấy tiền thưởng reward_amount.
        $rawData = UserTask::join('tasks', 'user_tasks.task_id', '=', 'tasks.id')
            ->where('user_tasks.created_at', '>=', $startDate)
            ->select(
                DB::raw("DATE_FORMAT(user_tasks.created_at, '{$groupFormat}') as date_key"),
                'user_tasks.status',
                DB::raw('COUNT(*) as submission_count'),
                DB::raw('COALESCE(SUM(tasks.reward_amount), 0) as reward_sum')
            )
            ->groupBy('date_key', 'user_tasks.status')
            ->get();

        // Bản đồ hóa dữ liệu để tìm kiếm nhanh theo ngày và trạng thái O(1)
        $dataMap = [];
        foreach ($rawData as $row) {
            $dataMap[$row->date_key][$row->status] = [
                'count' => (int) $row->submission_count,
                'reward' => (float) $row->reward_sum,
            ];
        }

        // Tạo mảng dữ liệu cho labels và các cột biểu đồ tương ứng
        $labels = [];
        $inProgress = [];
        $completed = [];
        $claimed = [];
        $rewardData = [];
        $rewardPendingData = [];

        if ($period === 'year') {
            // Duyệt qua 12 tháng gần nhất
            for ($i = 11; $i >= 0; $i--) {
                $date = now()->subMonths($i);
                $key = $date->format('Y-m');
                $labels[] = __('Tháng') . ' ' . $date->format('m/Y');

                $ip = $dataMap[$key]['in_progress'] ?? ['count' => 0, 'reward' => 0];
                // Yêu cầu xác nhận đang chờ Admin duyệt vẫn thuộc nhóm chưa hoàn thành
                $pd = $dataMap[$key]['pending'] ?? ['count' => 0, 'reward' => 0];
                $c = $dataMap[$key]['completed'] ?? ['count' => 0, 'reward' => 0];
                $cl = $dataMap[$key]['claimed'] ?? ['count' => 0, 'reward' => 0];

                $inProgress[] = $ip['count'] + $pd['count'];
                $completed[] = $c['count'];
                $claimed[] = $cl['count'];
                $rewardData[] = round($cl['reward']);
                $rewardPendingData[] = round($c['reward']);
            }
        } else {
            // Duyệt qua 7 hoặc 30 ngày gần nhất
            $totalDays = $period === 'week' ? 6 : 29;
            for ($i = $totalDays; $i >= 0; $i--) {
                $date = now()->subDays($i);
                $key = $date->format('Y-m-d');
                $labels[] = $date->format($labelFormat);

                $ip = $dataMap[$key]['in_progress'] ?? ['count' => 0, 'reward' => 0];
                // Yêu cầu xác nhận đang chờ Admin duyệt vẫn thuộc nhóm chưa hoàn thành
                $pd = $dataMap[$key]['pending'] ?? ['count' => 0, 'reward' => 0];
                $c = $dataMap[$key]['completed'] ?? ['count' => 0, 'reward' => 0];
                $cl = $dataMap[$key]['claimed'] ?? ['count' => 0, 'reward' => 0];

                $inProgress[] = $ip['count'] + $pd['count'];
                $completed[] = $c['count'];
                $claimed[] = $cl['count'];
                $rewardData[] = round($cl['reward']);
                $rewardPendingData[] = round($c['reward']);
            }
        }

        // Tính tổng tích lũy cho khoảng thời gian này
        $totalInProgress = array_sum($inProgress);
        $totalCompleted = array_sum($completed);
        $totalClaimed = array_sum($claimed);
        $totalReward = array_sum($rewardData);
        $totalRewardPending = array_sum($rewardPendingData);

        return response()->json([
            'success' => true,
            'labels' => $labels,
            'in_progress' => $inProgress,
            'completed' => $completed,
            'claimed' => $claimed,
            'reward_data' => $rewardData,
            'reward_pending_data' => $rewardPendingData,
            'totals' => [
                'all' => $totalInProgress + $totalCompleted + $totalClaimed,
                'in_progress' => $totalInProgress,
                'completed' => $totalCompleted,
                'claimed' => $totalClaimed,
                'reward' => $totalReward,
                'reward_pending' => $totalRewardPending,
            ],
            'period' => $period,
        ]);
    }
}
