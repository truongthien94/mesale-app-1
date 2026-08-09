<?php

namespace App\Http\Middleware;

use App\Models\ApiToken;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware xác thực token Bearer cho Open API.
 *
 * Luồng bảo mật:
 *  - Đọc token gốc từ header `Authorization: Bearer {token}`.
 *  - Đối chiếu bản băm SHA-256 với database, kiểm tra hạn dùng.
 *  - Kiểm tra tài khoản còn hoạt động (status = active).
 *  - Gắn thành viên đã xác thực vào request để controller sử dụng qua $request->apiUser().
 *
 * Chế độ `allow_key` (api.auth:allow_key):
 *  - Chấp nhận thêm Khóa API Token cá nhân (users.api_token) mà thành viên tự lấy ở trang Hồ sơ.
 *  - CHỈ áp dụng cho các endpoint đã công bố trong Tài liệu API (lấy link hoàn tiền, tra cứu đơn hàng).
 *  - Các endpoint nhạy cảm (rút tiền, đổi mật khẩu, bảo mật, phiên đăng nhập, xóa tài khoản...)
 *    vẫn bắt buộc dùng token phiên đăng nhập có hạn dùng và thu hồi được, tránh việc một khóa
 *    tĩnh vĩnh viễn bị lộ có thể rút sạch số dư của thành viên.
 */
class ApiAuthenticate
{
    public function handle(Request $request, Closure $next, ?string $mode = null): Response
    {
        // Chế độ cho phép dùng Khóa API Token cá nhân (API Key) ngoài token phiên đăng nhập
        $allowPersonalKey = $mode === 'allow_key';

        // Khóa cá nhân có thể gửi qua Bearer header hoặc tham số api_key / api_token cho tiện tích hợp.
        // BẮT BUỘC ép kiểu chuỗi: client có thể gửi api_key dạng mảng (api_key[]=x) khiến các hàm
        // xử lý chuỗi phía sau ném TypeError và trả về lỗi 500 kèm nguy cơ lộ thông tin hệ thống.
        $credential = $request->bearerToken();
        if (!is_string($credential) || $credential === '') {
            $credential = null;

            if ($allowPersonalKey) {
                foreach (['api_key', 'api_token'] as $field) {
                    $value = $request->input($field);
                    if (is_string($value) && trim($value) !== '') {
                        $credential = trim($value);
                        break;
                    }
                }
            }
        }

        if (empty($credential)) {
            return response()->json([
                'success' => false,
                'code' => 'UNAUTHENTICATED',
                'message' => $allowPersonalKey
                    ? __('Thiếu khóa xác thực. Vui lòng gửi kèm Khóa API Token (API Key) hoặc token đăng nhập.')
                    : __('Thiếu token xác thực. Vui lòng đăng nhập để lấy token truy cập.'),
            ], 401);
        }

        $token = ApiToken::findValid($credential);
        $user = $token?->user;

        // Token phiên còn hiệu lực nhưng tài khoản gắn với nó đã bị xóa: thu hồi ngay token mồ côi
        if ($token && !$user) {
            $token->delete();
        }

        // Không khớp token phiên đăng nhập: thử đối chiếu Khóa API Token cá nhân (nếu endpoint cho phép)
        if (!$token && $allowPersonalKey) {
            $user = User::where('api_token', $credential)->first();
        }

        if (!$user) {
            return response()->json([
                'success' => false,
                'code' => 'INVALID_TOKEN',
                'message' => $allowPersonalKey
                    ? __('Khóa API Token không hợp lệ hoặc đã hết hạn. Vui lòng kiểm tra lại trong trang Hồ sơ.')
                    : __('Token không hợp lệ hoặc đã hết hạn. Vui lòng đăng nhập lại.'),
            ], 401);
        }

        // Tài khoản đã bị khóa/ngưng hoạt động
        if ($user->status !== 'active') {
            // Thu hồi token phiên gắn với tài khoản không còn hợp lệ
            $token?->delete();
            return response()->json([
                'success' => false,
                'code' => 'ACCOUNT_INACTIVE',
                'message' => __('Tài khoản của bạn đã bị khóa hoặc ngưng hoạt động.'),
            ], 403);
        }

        // Cập nhật dấu vết sử dụng token (không chặn luồng nếu ghi lỗi)
        if ($token) {
            try {
                $token->forceFill([
                    'last_used_at' => now(),
                    'last_ip' => $request->ip(),
                ])->saveQuietly();
            } catch (\Throwable $e) {
                // Bỏ qua lỗi ghi metadata để không ảnh hưởng tới request chính
            }
        }

        // Gắn thông tin xác thực vào request cho các controller phía sau
        $request->setUserResolver(fn () => $user);
        $request->attributes->set('api_token', $token);

        return $next($request);
    }
}
