<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\UserTask;
use App\Services\TaskService;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function __construct(private TaskService $taskService) {}

    private function ensureEnabled()
    {
        if (\App\Models\Setting::getVal('tasks_enabled', '0') !== '1') {
            abort(404);
        }
    }

    /**
     * Trang danh sách nhiệm vụ của người dùng.
     */
    public function index()
    {
        $this->ensureEnabled();
        $user  = auth()->user();
        $tasks = $this->taskService->getTasksForUser($user);

        $claimedCount    = UserTask::where('user_id', $user->id)->where('status', 'claimed')->count();
        $completedCount  = UserTask::where('user_id', $user->id)->where('status', 'completed')->count();
        $inProgressCount = UserTask::where('user_id', $user->id)->where('status', 'in_progress')->count();
        $totalEarned     = \App\Models\BalanceLog::where('user_id', $user->id)
            ->where('type', 'task_reward')
            ->sum('amount_change');

        return view('dashboard.tasks', compact(
            'tasks', 'claimedCount', 'completedCount', 'inProgressCount', 'totalEarned'
        ));
    }

    /**
     * User nhận thưởng sau khi hoàn thành nhiệm vụ.
     */
    public function claim(Request $request, Task $task)
    {
        $this->ensureEnabled();

        if (!$task->isAvailable()) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => __('Nhiệm vụ này không còn hoạt động.')]);
            }
            return back()->with('error', __('Nhiệm vụ này không còn hoạt động.'));
        }

        $result = $this->taskService->claimReward(auth()->user(), $task);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json($result);
        }

        return back()->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    /**
     * Thành viên gửi yêu cầu xác nhận đã hoàn thành nhiệm vụ loại Tùy chỉnh (thủ công).
     * Yêu cầu sẽ chuyển sang trạng thái chờ duyệt để Admin kiểm tra trong tab Tiến độ.
     */
    public function submit(Request $request, Task $task)
    {
        $this->ensureEnabled();

        $request->validate([
            'note' => 'nullable|string|max:500',
        ], [], [
            'note' => __('ghi chú'),
        ]);

        if (!$task->isAvailable()) {
            $result = ['success' => false, 'message' => __('Nhiệm vụ này không còn hoạt động.')];

            return ($request->wantsJson() || $request->ajax())
                ? response()->json($result)
                : back()->with('error', $result['message']);
        }

        // Lọc sạch thẻ HTML trong ghi chú để tránh chèn mã độc vào trang quản trị
        $note = trim(strip_tags((string) $request->input('note'))) ?: null;

        $result = $this->taskService->submitCustomTask(auth()->user(), $task, $note);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json($result);
        }

        return back()->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    /**
     * Đồng bộ tiến độ nhiệm vụ và trả về dữ liệu AJAX.
     */
    public function syncProgress(Task $task)
    {
        $this->ensureEnabled();
        $user     = auth()->user();
        $userTask = $this->taskService->syncProgress($user, $task);

        return response()->json([
            'success'  => true,
            'progress' => $userTask->progress,
            'target'   => $task->target_count,
            'percent'  => $userTask->getProgressPercent(),
            'status'   => $userTask->status,
        ]);
    }
}
