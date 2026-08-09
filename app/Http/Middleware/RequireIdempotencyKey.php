<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireIdempotencyKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $key = $request->header('Idempotency-Key');

        if (! is_string($key) || trim($key) === '') {
            return response()->json([
                'success' => false,
                'code' => 'IDEMPOTENCY_KEY_REQUIRED',
                'message' => __('Thiếu Idempotency-Key cho thao tác tài chính.'),
            ], 400);
        }

        $key = trim($key);
        if (! preg_match('/\A[A-Za-z0-9][A-Za-z0-9._:-]{7,127}\z/', $key)) {
            return response()->json([
                'success' => false,
                'code' => 'IDEMPOTENCY_KEY_INVALID',
                'message' => __('Idempotency-Key không hợp lệ.'),
            ], 422);
        }

        $request->attributes->set('idempotency_key', $key);

        return $next($request);
    }
}
