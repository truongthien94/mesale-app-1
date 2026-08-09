<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\ActivityLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * API Nhật ký hoạt động tài khoản của thành viên đang đăng nhập.
 */
class ActivityLogController extends ApiController
{
    /**
     * GET /api/v1/openapi/logs
     * Query: search, page, per_page (tối đa 50)
     */
    public function index(Request $request): JsonResponse
    {
        $user = $this->apiUser($request);

        $query = ActivityLog::where('user_id', $user->id);

        // Tìm kiếm theo nội dung hoạt động, IP hoặc thiết bị
        if ($search = trim((string) $request->query('search', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('activity', 'like', "%{$search}%")
                    ->orWhere('ip_address', 'like', "%{$search}%")
                    ->orWhere('user_agent', 'like', "%{$search}%");
            });
        }

        $perPage = max(1, min((int) $request->query('per_page', 15), 50));
        $logs = $query->orderByDesc('created_at')->paginate($perPage);

        return $this->ok([
            'items' => $logs->getCollection()->map(fn (ActivityLog $log) => [
                'id' => $log->id,
                'activity' => $log->activity,
                'ip_address' => $log->ip_address,
                'user_agent' => $log->user_agent,
                'created_at' => optional($log->created_at)->toIso8601String(),
            ]),
            'pagination' => [
                'current_page' => $logs->currentPage(),
                'per_page' => $logs->perPage(),
                'total' => $logs->total(),
                'last_page' => $logs->lastPage(),
            ],
        ]);
    }
}
