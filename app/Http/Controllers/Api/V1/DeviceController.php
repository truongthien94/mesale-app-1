<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\DeviceToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * API Đăng ký thiết bị nhận thông báo đẩy (Push Notification).
 * App gọi sau khi đăng nhập để lưu token FCM/APNs; gọi hủy khi đăng xuất/tắt push.
 */
class DeviceController extends ApiController
{
    /**
     * GET /api/v1/openapi/devices
     * Danh sách thiết bị đang đăng ký nhận push của thành viên.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $this->apiUser($request);

        $devices = DeviceToken::where('user_id', $user->id)
            ->orderByDesc('last_used_at')
            ->get()
            ->map(fn (DeviceToken $d) => [
                'id' => $d->id,
                'platform' => $d->platform,
                'device_name' => $d->device_name,
                'last_used_at' => optional($d->last_used_at)->toIso8601String(),
            ]);

        return $this->ok(['items' => $devices]);
    }

    /**
     * POST /api/v1/openapi/devices/register
     * Body: token (bắt buộc), platform (android|ios|web), device_name
     * Lưu hoặc cập nhật token; nếu token đã tồn tại thì gắn về user hiện tại (đổi tài khoản trên cùng máy).
     */
    public function register(Request $request): JsonResponse
    {
        $user = $this->apiUser($request);

        try {
            $validated = $request->validate([
                'token' => 'required|string|max:512',
                'platform' => 'nullable|in:android,ios,web',
                'device_name' => 'nullable|string|max:255',
            ]);
        } catch (ValidationException $e) {
            return $this->fail(__('Dữ liệu không hợp lệ.'), 422, 'VALIDATION_ERROR', $e->errors());
        }

        // updateOrCreate theo token (unique) để tránh trùng và tự chuyển chủ sở hữu khi đổi tài khoản trên cùng thiết bị
        $device = DeviceToken::updateOrCreate(
            ['token' => $validated['token']],
            [
                'user_id' => $user->id,
                'platform' => $validated['platform'] ?? 'android',
                'device_name' => $validated['device_name'] ?? null,
                'last_used_at' => now(),
            ]
        );

        return $this->ok(['id' => $device->id], __('Đăng ký nhận thông báo đẩy thành công.'));
    }

    /**
     * POST /api/v1/openapi/devices/unregister
     * Body: token — hủy đăng ký push cho thiết bị (gọi khi đăng xuất hoặc tắt thông báo).
     */
    public function unregister(Request $request): JsonResponse
    {
        $user = $this->apiUser($request);

        try {
            $validated = $request->validate([
                'token' => 'required|string|max:512',
            ]);
        } catch (ValidationException $e) {
            return $this->fail(__('Dữ liệu không hợp lệ.'), 422, 'VALIDATION_ERROR', $e->errors());
        }

        // Chỉ xóa token thuộc về chính user để tránh hủy nhầm thiết bị người khác
        DeviceToken::where('user_id', $user->id)
            ->where('token', $validated['token'])
            ->delete();

        return $this->ok(null, __('Đã hủy đăng ký nhận thông báo đẩy.'));
    }
}
