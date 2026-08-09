<?php

namespace App\Http\Controllers\Api\V1;

use App\Helpers\MoneyHelper;
use App\Models\BalanceLog;
use App\Models\Setting;
use App\Models\Task;
use App\Models\UserTask;
use App\Services\TaskService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * API Nhiệm vụ nhận thưởng của thành viên đang đăng nhập.
 * Cho phép App/Frontend lấy danh sách nhiệm vụ kèm tiến độ, đồng bộ tiến độ và nhận thưởng.
 */
class TaskController extends ApiController
{
    public function __construct(private TaskService $taskService) {}

    /**
     * Đảm bảo tính năng Nhiệm vụ đang được bật ở cấp hệ thống.
     * Trả về null nếu hợp lệ, ngược lại trả về phản hồi lỗi.
     */
    private function ensureFeatureEnabled(): ?JsonResponse
    {
        if (Setting::getVal('tasks_enabled', '0') !== '1') {
            return $this->fail(__('Tính năng nhiệm vụ hiện đang tắt.'), 403, 'TASKS_DISABLED');
        }

        return null;
    }

    /**
     * Chuyển một nhiệm vụ (kèm userTask đã gắn) thành mảng dữ liệu API.
     */
    private function transformTask(Task $task): array
    {
        $userTask = $task->userTask ?? null;

        return [
            'id' => $task->id,
            'title' => $task->title,
            'description' => $task->description,
            'guide' => $task->guide,
            // Bản HTML đã lọc sạch của hai trường trên, dùng cho ứng dụng hiển thị đúng định dạng đã soạn
            'description_html' => $task->description_html,
            'guide_html' => $task->guide_html,
            'type' => $task->type,
            'type_label' => __($task->getTypeLabel()),
            'action' => $task->action,
            'action_label' => __($task->getActionLabel()),
            'icon' => $task->icon,
            'badge_color' => $task->badge_color,
            'target_count' => (int) $task->target_count,
            'reward_amount' => (int) MoneyHelper::round($task->reward_amount),
            'reward_type' => $task->reward_type,
            'start_at' => optional($task->start_at)->toIso8601String(),
            'end_at' => optional($task->end_at)->toIso8601String(),
            'progress' => $userTask?->progress ?? 0,
            'percent' => $userTask?->getProgressPercent() ?? 0,
            'status' => $userTask?->status ?? 'in_progress',
            // Thông tin luồng thành viên tự xác nhận hoàn thành nhiệm vụ thủ công
            'submitted_at' => optional($userTask?->submitted_at)->toIso8601String(),
            'submit_note' => $userTask?->submit_note,
            'reject_reason' => $userTask?->reject_reason,
        ];
    }

    /**
     * GET /api/v1/openapi/tasks
     * Danh sách nhiệm vụ đang hoạt động kèm tiến độ của thành viên và thống kê tổng hợp.
     */
    public function index(Request $request): JsonResponse
    {
        if ($resp = $this->ensureFeatureEnabled()) {
            return $resp;
        }

        $user = $this->apiUser($request);
        $tasks = $this->taskService->getTasksForUser($user);

        $stats = [
            'claimed' => UserTask::where('user_id', $user->id)->where('status', 'claimed')->count(),
            'completed' => UserTask::where('user_id', $user->id)->where('status', 'completed')->count(),
            'in_progress' => UserTask::where('user_id', $user->id)->where('status', 'in_progress')->count(),
            // Số yêu cầu xác nhận nhiệm vụ thủ công đang chờ quản trị viên duyệt
            'pending' => UserTask::where('user_id', $user->id)->where('status', 'pending')->count(),
            'total_earned' => (int) MoneyHelper::round(
                BalanceLog::where('user_id', $user->id)
                    ->where('type', 'task_reward')
                    ->sum('amount_change')
            ),
        ];

        return $this->ok([
            'items' => $tasks->map(fn (Task $task) => $this->transformTask($task))->values(),
            'stats' => $stats,
        ]);
    }

    /**
     * GET /api/v1/openapi/tasks/{task}/sync
     * Đồng bộ lại tiến độ nhiệm vụ theo dữ liệu thực tế (auto-verify).
     */
    public function sync(Request $request, Task $task): JsonResponse
    {
        if ($resp = $this->ensureFeatureEnabled()) {
            return $resp;
        }

        if (! $task->isAvailable()) {
            return $this->fail(__('Nhiệm vụ này không còn hoạt động.'), 404, 'TASK_UNAVAILABLE');
        }

        $user = $this->apiUser($request);
        $userTask = $this->taskService->syncProgress($user, $task);

        return $this->ok([
            'task_id' => $task->id,
            'progress' => $userTask->progress,
            'target' => (int) $task->target_count,
            'percent' => $userTask->getProgressPercent(),
            'status' => $userTask->status,
        ]);
    }

    /**
     * POST /api/v1/openapi/tasks/{task}/claim
     * Nhận thưởng sau khi hoàn thành nhiệm vụ (chạy trong giao dịch khóa dòng).
     */
    public function claim(Request $request, Task $task): JsonResponse
    {
        if ($resp = $this->ensureFeatureEnabled()) {
            return $resp;
        }

        if (! $task->isAvailable()) {
            return $this->fail(__('Nhiệm vụ này không còn hoạt động.'), 404, 'TASK_UNAVAILABLE');
        }

        $result = $this->taskService->claimReward($this->apiUser($request), $task);

        if (! ($result['success'] ?? false)) {
            return $this->fail($result['message'] ?? __('Không thể nhận thưởng.'), 400, 'CLAIM_FAILED');
        }

        return $this->ok(
            ['amount' => (int) MoneyHelper::round($result['amount'] ?? 0)],
            $result['message'] ?? null
        );
    }

    /**
     * POST /api/v1/openapi/tasks/{task}/submit
     * Thành viên gửi yêu cầu xác nhận đã hoàn thành nhiệm vụ loại Tùy chỉnh (thủ công),
     * kèm ghi chú/bằng chứng để quản trị viên kiểm duyệt.
     */
    public function submit(Request $request, Task $task): JsonResponse
    {
        if ($resp = $this->ensureFeatureEnabled()) {
            return $resp;
        }

        $request->validate(['note' => 'nullable|string|max:500']);

        if (! $task->isAvailable()) {
            return $this->fail(__('Nhiệm vụ này không còn hoạt động.'), 404, 'TASK_UNAVAILABLE');
        }

        // Lọc sạch thẻ HTML trong ghi chú để tránh chèn mã độc vào trang quản trị
        $note = trim(strip_tags((string) $request->input('note'))) ?: null;
        $result = $this->taskService->submitCustomTask($this->apiUser($request), $task, $note);

        if (! ($result['success'] ?? false)) {
            return $this->fail($result['message'] ?? __('Không thể gửi yêu cầu xác nhận.'), 400, 'SUBMIT_FAILED');
        }

        return $this->ok(['task_id' => $task->id, 'status' => 'pending'], $result['message'] ?? null);
    }
}
