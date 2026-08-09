<?php

namespace App\Http\Controllers\Api\V1;

use App\Helpers\MoneyHelper;
use App\Models\BalanceLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * API Biến động số dư ví của thành viên đang đăng nhập.
 * Hỗ trợ lọc theo loại giao dịch, tìm kiếm theo mô tả và phân trang.
 */
class BalanceLogController extends ApiController
{
    /**
     * GET /api/v1/openapi/balance-logs
     * Query: type, search, page, per_page (tối đa 50)
     */
    public function index(Request $request): JsonResponse
    {
        $user = $this->apiUser($request);

        $query = BalanceLog::where('user_id', $user->id);

        // Lọc theo loại giao dịch (checkin, gift_exchange, giftcode_reward, withdraw_request, ...)
        if ($type = trim((string) $request->query('type', ''))) {
            $query->where('type', $type);
        }

        // Tìm kiếm theo mô tả hoặc loại giao dịch
        if ($search = trim((string) $request->query('search', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                    ->orWhere('type', 'like', "%{$search}%");
            });
        }

        $perPage = max(1, min((int) $request->query('per_page', 15), 50));

        $logs = $query->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate($perPage);

        $items = $logs->getCollection()->map(function (BalanceLog $log): array {
            $amountBefore = (int) MoneyHelper::round($log->amount_before);
            $amountAfter = (int) MoneyHelper::round($log->amount_after);
            $amountChange = $amountAfter - $amountBefore;

            return [
                'id' => $log->id,
                'type' => $log->type,
                'description' => $log->description,
                'amount_before' => $amountBefore,
                'amount_change' => $amountChange,
                'amount_after' => $amountAfter,
                'is_credit' => $amountChange >= 0,
                'created_at' => optional($log->created_at)->toIso8601String(),
            ];
        });

        return $this->ok([
            'items' => $items,
            'pagination' => [
                'current_page' => $logs->currentPage(),
                'per_page' => $logs->perPage(),
                'total' => $logs->total(),
                'last_page' => $logs->lastPage(),
            ],
        ]);
    }
}
