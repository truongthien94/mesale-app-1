<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * API Thông báo: danh sách, đếm chưa đọc và đánh dấu đã đọc cho thành viên đang đăng nhập.
 */
class NotificationController extends ApiController
{
    /**
     * GET /api/v1/openapi/notifications
     * Query: type (general|personal), filter (all|unread), page, per_page
     */
    public function index(Request $request): JsonResponse
    {
        $user = $this->apiUser($request);

        $query = Notification::where('user_id', $user->id);

        // Lọc theo nhóm thông báo
        $type = $request->query('type');
        if ($type && in_array($type, ['general', 'personal'], true)) {
            $query->where('type', $type);
        }

        // Lọc theo trạng thái đọc
        if ($request->query('filter') === 'unread') {
            $query->where('is_read', false);
        }

        $perPage = (int) $request->query('per_page', 15);
        $perPage = max(1, min($perPage, 50));

        $notifications = $query->orderByDesc('created_at')->paginate($perPage);

        $items = $notifications->getCollection()->map(fn (Notification $n) => [
            'id' => $n->id,
            'title' => $n->title,
            'content' => $n->content,
            'type' => $n->type,
            'is_read' => (bool) $n->is_read,
            'created_at' => optional($n->created_at)->toIso8601String(),
        ]);

        return $this->ok([
            'items' => $items,
            'unread_total' => Notification::where('user_id', $user->id)->where('is_read', false)->count(),
            'pagination' => [
                'current_page' => $notifications->currentPage(),
                'per_page' => $notifications->perPage(),
                'total' => $notifications->total(),
                'last_page' => $notifications->lastPage(),
            ],
        ]);
    }

    /**
     * GET /api/v1/openapi/notifications/unread-count
     * Số thông báo chưa đọc (dùng cho huy hiệu badge trên App).
     */
    public function unreadCount(Request $request): JsonResponse
    {
        $user = $this->apiUser($request);

        return $this->ok([
            'unread_total' => Notification::where('user_id', $user->id)->where('is_read', false)->count(),
            'general' => Notification::where('user_id', $user->id)->where('type', 'general')->where('is_read', false)->count(),
            'personal' => Notification::where('user_id', $user->id)->where('type', 'personal')->where('is_read', false)->count(),
        ]);
    }

    /**
     * POST /api/v1/openapi/notifications/{id}/read
     * Đánh dấu một thông báo là đã đọc.
     */
    public function markRead(Request $request, int $id): JsonResponse
    {
        $user = $this->apiUser($request);

        $notification = Notification::where('user_id', $user->id)->where('id', $id)->first();
        if (!$notification) {
            return $this->fail(__('Không tìm thấy thông báo.'), 404, 'NOT_FOUND');
        }

        if (!$notification->is_read) {
            $notification->is_read = true;
            $notification->save();
        }

        return $this->ok(null, __('Đã đánh dấu đã đọc thông báo.'));
    }

    /**
     * POST /api/v1/openapi/notifications/read-all
     * Đánh dấu toàn bộ thông báo chưa đọc thành đã đọc.
     */
    public function markAllRead(Request $request): JsonResponse
    {
        $user = $this->apiUser($request);

        $updated = Notification::where('user_id', $user->id)->where('is_read', false)->update(['is_read' => true]);

        return $this->ok(['updated' => $updated], __('Đã đánh dấu đọc tất cả thông báo.'));
    }
}
