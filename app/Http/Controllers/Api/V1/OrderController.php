<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\CashbackHistory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * API Danh sách đơn hàng hoàn tiền của thành viên đang đăng nhập.
 * Hỗ trợ lọc theo trạng thái, nền tảng, tìm kiếm và phân trang.
 */
class OrderController extends ApiController
{
    /**
     * GET /api/v1/openapi/orders
     * Query: status, platform, search, page, per_page
     */
    public function index(Request $request): JsonResponse
    {
        $user = $this->apiUser($request);

        $query = CashbackHistory::where('user_id', $user->id);

        // Lọc theo trạng thái đơn (pending / approved / rejected)
        $status = $request->query('status');
        if ($status && in_array($status, ['pending', 'approved', 'rejected'], true)) {
            $query->where('status', $status);
        }

        // Lọc theo nền tảng (shopee / tiktok)
        $platform = $request->query('platform');
        if ($platform && in_array($platform, ['shopee', 'tiktok'], true)) {
            $query->where('platform', $platform);
        }

        // Tìm kiếm theo mã đơn hoặc tên sản phẩm
        if ($search = trim((string) $request->query('search', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('order_id', 'like', "%{$search}%")
                    ->orWhere('product_name', 'like', "%{$search}%");
            });
        }

        // Giới hạn số bản ghi mỗi trang trong khoảng an toàn 1..50
        $perPage = (int) $request->query('per_page', 15);
        $perPage = max(1, min($perPage, 50));

        $orders = $query->orderByDesc('created_at')->paginate($perPage);

        $items = $orders->getCollection()->map(fn (CashbackHistory $order) => $this->transformListItem($order));

        return $this->ok([
            'items' => $items,
            'pagination' => [
                'current_page' => $orders->currentPage(),
                'per_page' => $orders->perPage(),
                'total' => $orders->total(),
                'last_page' => $orders->lastPage(),
            ],
        ]);
    }

    /**
     * GET /api/v1/openapi/orders/{id}
     * Chi tiết một đơn hàng hoàn tiền của chính thành viên.
     */
    public function show(Request $request, string $id): JsonResponse
    {
        return $this->respondDetail($request, $id);
    }

    /**
     * Xử lý chung cho endpoint chi tiết đơn hàng: tra cứu theo khóa rồi dựng dữ liệu trả về.
     * Tách riêng để lớp con (API cho Bot) tái sử dụng mà không lặp lại logic.
     */
    protected function respondDetail(Request $request, string $key): JsonResponse
    {
        $user = $this->apiUser($request);

        $order = $this->resolveOrder((int) $user->id, $key);
        if (!$order) {
            return $this->fail(__('Không tìm thấy đơn hàng.'), 404, 'ORDER_NOT_FOUND');
        }

        return $this->ok($this->transformDetail($order));
    }

    /**
     * Tra cứu đơn hàng theo khóa định danh, luôn giới hạn trong phạm vi đơn của chính thành viên.
     * Open API dùng khóa chính dạng số; lớp con có thể đổi sang khóa khác (ví dụ mã đơn hàng).
     */
    protected function resolveOrder(int $userId, string $key): ?CashbackHistory
    {
        return CashbackHistory::where('user_id', $userId)->find((int) $key);
    }

    /**
     * Dữ liệu một dòng trong danh sách đơn hàng. Lớp con ghi đè để ẩn/thêm trường.
     */
    protected function transformListItem(CashbackHistory $order): array
    {
        return [
            'id' => $order->id,
            'order_id' => $order->order_id,
            'trans_id' => $order->trans_id, // Mã giao dịch đối soát (sub_id) để người dùng/bot liên kết đơn hàng với lượt click
            'platform' => $order->platform,
            'product_name' => $order->product_name,
            'product_image' => $order->product_image,
            'original_price' => (float) $order->original_price,
            'commission_amount' => (float) $order->commission_amount, // Tổng số tiền hoa hồng thực tế nhận từ sàn Shopee/TikTok
            'cashback_amount' => (float) $order->cashback_amount,
            'cashback_rate' => (float) $order->cashback_rate,
            'status' => $order->status,
            'rejected_reason' => $order->rejected_reason,
            'approved_at' => optional($order->approved_at)->toIso8601String(),
            'created_at' => optional($order->created_at)->toIso8601String(),
        ];
    }

    /**
     * Dữ liệu chi tiết một đơn hàng. Lớp con ghi đè để ẩn/thêm trường.
     */
    protected function transformDetail(CashbackHistory $order): array
    {
        return [
            'id' => $order->id,
            'order_id' => $order->order_id,
            'trans_id' => $order->trans_id,
            'platform' => $order->platform,
            'product_name' => $order->product_name,
            'product_image' => $order->product_image,
            'shop_name' => $order->shop_name,
            'original_price' => (float) $order->original_price,
            'commission_amount' => (float) $order->commission_amount,
            'cashback_amount' => (float) $order->cashback_amount,
            'cashback_rate' => (float) $order->cashback_rate,
            'status' => $order->status,
            'rejected_reason' => $order->rejected_reason,
            'approved_at' => optional($order->approved_at)->toIso8601String(),
            'created_at' => optional($order->created_at)->toIso8601String(),
        ];
    }
}
