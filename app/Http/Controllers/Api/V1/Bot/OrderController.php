<?php

namespace App\Http\Controllers\Api\V1\Bot;

use App\Http\Controllers\Api\V1\OrderController as BaseOrderController;
use App\Models\CashbackHistory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

/**
 * API Đơn hàng dành cho hệ thống ngoài / Bot (tiền tố /api/v1/bot).
 *
 * Khác biệt so với Open API (dành cho App Mobile):
 *  - KHÔNG trả về `id` (khóa chính nội bộ của database). Hệ thống ngoài chỉ cần và chỉ nên
 *    làm việc với `order_id` — mã đơn hàng thật từ sàn — để đối chiếu đơn.
 *  - Endpoint chi tiết vì vậy cũng tra cứu theo `order_id` thay vì khóa chính.
 *
 * Toàn bộ logic lọc, tìm kiếm, phân trang và ràng buộc chỉ xem được đơn của chính mình
 * đều kế thừa nguyên vẹn từ lớp cha nên không có nguy cơ lệch hành vi giữa hai nhóm API.
 */
class OrderController extends BaseOrderController
{
    /**
     * GET /api/v1/bot/orders/{order_id}
     * Chi tiết đơn hàng, tra cứu bằng mã đơn hàng của sàn.
     */
    public function show(Request $request, string $order_id): JsonResponse
    {
        return $this->respondDetail($request, $order_id);
    }

    /**
     * Tra cứu theo cột `order_id` (có ràng buộc UNIQUE ở database) thay vì khóa chính.
     * Vẫn giới hạn theo user_id nên không thể xem đơn của thành viên khác.
     */
    protected function resolveOrder(int $userId, string $key): ?CashbackHistory
    {
        $key = trim($key);
        if ($key === '') {
            return null;
        }

        return CashbackHistory::where('user_id', $userId)
            ->where('order_id', $key)
            ->first();
    }

    /**
     * Ẩn khóa chính nội bộ khỏi danh sách đơn hàng.
     */
    protected function transformListItem(CashbackHistory $order): array
    {
        return Arr::except(parent::transformListItem($order), ['id']);
    }

    /**
     * Ẩn khóa chính nội bộ khỏi chi tiết đơn hàng.
     */
    protected function transformDetail(CashbackHistory $order): array
    {
        return Arr::except(parent::transformDetail($order), ['id']);
    }
}
