<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware kiểm soát trạng thái bật/tắt của hệ thống Open API.
 *
 *  - Luôn kiểm tra công tắc tổng `openapi_status`: nếu tắt thì toàn bộ API ngừng phục vụ.
 *  - Nếu truyền tham số nhóm (ví dụ: api.enabled:withdraw), kiểm tra thêm công tắc riêng
 *    của nhóm endpoint đó (`openapi_{group}_status`) để Admin có thể tắt từng chức năng độc lập.
 */
class ApiEndpointEnabled
{
    private const DEFAULT_DISABLED_GROUPS = [
        'auth_oauth_google',
        'auth_oauth_apple',
    ];

    public function handle(Request $request, Closure $next, ?string $group = null): Response
    {
        // 1. Công tắc tổng của toàn bộ hệ thống Open API
        if (Setting::getVal('openapi_status', '0') !== '1') {
            return response()->json([
                'success' => false,
                'code' => 'API_DISABLED',
                'message' => __('Hệ thống Open API hiện đang tắt. Vui lòng liên hệ quản trị viên.'),
            ], 503);
        }

        // 2. Công tắc riêng của từng nhóm endpoint (nếu có khai báo)
        if ($group !== null) {
            $default = in_array($group, self::DEFAULT_DISABLED_GROUPS, true) ? '0' : '1';
            if (Setting::getVal("openapi_{$group}_status", $default) !== '1') {
                return response()->json([
                    'success' => false,
                    'code' => 'ENDPOINT_DISABLED',
                    'message' => __('Chức năng API này hiện đang bị vô hiệu hóa bởi quản trị viên.'),
                ], 403);
            }
        }

        return $next($request);
    }
}
