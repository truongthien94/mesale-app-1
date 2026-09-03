<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Controller cơ sở cho toàn bộ Open API v1.
 * Cung cấp các hàm phản hồi JSON thống nhất và tiện ích lấy thành viên đã xác thực.
 */
abstract class ApiController extends Controller
{
    /**
     * Phản hồi JSON thành công theo chuẩn thống nhất của Open API.
     */
    protected function ok(mixed $data = null, ?string $message = null, int $status = 200): JsonResponse
    {
        $payload = ['success' => true];
        if ($message !== null) {
            $payload['message'] = $message;
        }
        if ($data !== null) {
            $payload['data'] = $data;
        }
        return response()->json($payload, $status);
    }

    /**
     * Phản hồi JSON lỗi theo chuẩn thống nhất của Open API.
     */
    protected function fail(string $message, int $status = 400, ?string $code = null, mixed $errors = null): JsonResponse
    {
        $payload = ['success' => false, 'message' => $message];
        if ($code !== null) {
            $payload['code'] = $code;
        }
        if ($errors !== null) {
            $payload['errors'] = $errors;
        }
        return response()->json($payload, $status);
    }

    /**
     * Lấy thành viên đã được middleware api.auth xác thực gắn vào request.
     */
    protected function apiUser(Request $request): User
    {
        return $request->user();
    }
}
