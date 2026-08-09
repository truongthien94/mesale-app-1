<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\ActivityLog;
use App\Models\ApiToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * API Quản lý phiên đăng nhập (mỗi phiên tương ứng một token thiết bị trong bảng api_tokens).
 * Cho phép thành viên xem các thiết bị đang đăng nhập và thu hồi (đăng xuất từ xa).
 */
class SessionController extends ApiController
{
    /**
     * Lấy ID token đang dùng cho request hiện tại (do middleware api.auth gắn vào).
     */
    private function currentTokenId(Request $request): int
    {
        $current = $request->attributes->get('api_token');
        return $current instanceof ApiToken ? $current->id : 0;
    }

    /**
     * GET /api/v1/openapi/sessions
     * Danh sách phiên đăng nhập của thành viên, đánh dấu phiên hiện tại.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $this->apiUser($request);
        $currentId = $this->currentTokenId($request);

        $sessions = ApiToken::where('user_id', $user->id)
            ->orderByDesc('last_used_at')
            ->get()
            ->map(fn (ApiToken $t) => [
                'id' => $t->id,
                'device_name' => $t->name,
                'last_ip' => $t->last_ip,
                'last_used_at' => optional($t->last_used_at)->toIso8601String(),
                'created_at' => optional($t->created_at)->toIso8601String(),
                'expires_at' => optional($t->expires_at)->toIso8601String(),
                'is_current' => $t->id === $currentId,
            ]);

        return $this->ok(['items' => $sessions]);
    }

    /**
     * POST /api/v1/openapi/sessions/{id}/revoke
     * Thu hồi một phiên đăng nhập (đăng xuất thiết bị đó). Không cho thu hồi chính phiên hiện tại.
     */
    public function revoke(Request $request, int $id): JsonResponse
    {
        $user = $this->apiUser($request);
        $currentId = $this->currentTokenId($request);

        if ($id === $currentId) {
            return $this->fail(__('Không thể thu hồi phiên đang sử dụng. Hãy dùng chức năng đăng xuất.'), 400, 'CANNOT_REVOKE_CURRENT');
        }

        $token = ApiToken::where('user_id', $user->id)->where('id', $id)->first();
        if (!$token) {
            return $this->fail(__('Không tìm thấy phiên đăng nhập.'), 404, 'SESSION_NOT_FOUND');
        }

        $token->delete();
        ActivityLog::log(__('Thu hồi một phiên đăng nhập thiết bị (qua Open API)'), $user->id);

        return $this->ok(null, __('Đã thu hồi phiên đăng nhập thành công.'));
    }

    /**
     * POST /api/v1/openapi/sessions/revoke-others
     * Đăng xuất khỏi tất cả thiết bị khác, chỉ giữ lại phiên hiện tại.
     */
    public function revokeOthers(Request $request): JsonResponse
    {
        $user = $this->apiUser($request);
        $currentId = $this->currentTokenId($request);

        $deleted = ApiToken::where('user_id', $user->id)
            ->where('id', '!=', $currentId)
            ->delete();

        ActivityLog::log(__('Đăng xuất khỏi tất cả thiết bị khác (qua Open API)'), $user->id);

        return $this->ok(['revoked' => $deleted], __('Đã đăng xuất khỏi tất cả thiết bị khác.'));
    }
}
