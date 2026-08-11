<?php

namespace App\Http\Controllers\Api\V1;

use App\Helpers\MoneyHelper;
use App\Models\CashbackClick;
use App\Models\CashbackHistory;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * API Danh sách đơn hàng hoàn tiền của thành viên đang đăng nhập.
 * Hỗ trợ lọc theo trạng thái, nền tảng, tìm kiếm và phân trang.
 */
class OrderController extends ApiController
{
    /**
     * GET /api/v1/openapi/orders
     * Query: status, platform, search, start_date, end_date, page, per_page
     */
    public function index(Request $request): JsonResponse
    {
        $user = $this->apiUser($request);

        $showUnrecorded = $this->supportsUnrecordedRecords()
            && Setting::getVal('cashback_show_pending_clicks', '1') === '1';
        $status = $request->query('status');
        if (! in_array($status, ['pending', 'approved', 'rejected', 'unrecorded'], true)) {
            $status = null;
        }
        // Match the web contract: when the virtual group is disabled, its filter falls back to recorded orders.
        if ($status === 'unrecorded' && ! $showUnrecorded) {
            $status = null;
        }

        $applyCommonFilters = function ($query, array $searchColumns) use ($request): void {
            $platform = $request->query('platform');
            if (in_array($platform, ['shopee', 'tiktok', 'lazada'], true)) {
                $query->where('platform', $platform);
            }

            $search = trim((string) $request->query('search', ''));
            if ($search !== '') {
                $query->where(function ($nested) use ($search, $searchColumns): void {
                    foreach ($searchColumns as $column) {
                        $nested->orWhere($column, 'like', "%{$search}%");
                    }
                });
            }

            if ($startDate = $this->validDateFilter($request->query('start_date'))) {
                $query->whereDate('created_at', '>=', $startDate);
            }
            if ($endDate = $this->validDateFilter($request->query('end_date'))) {
                $query->whereDate('created_at', '<=', $endDate);
            }
        };

        $historyQuery = null;
        if ($status !== 'unrecorded') {
            $historyQuery = CashbackHistory::where('user_id', $user->id);
            if ($status !== null) {
                $historyQuery->where('status', $status);
            }
            $applyCommonFilters($historyQuery, ['product_name', 'order_id']);
        }

        $clickQuery = null;
        if ($showUnrecorded && ($status === null || $status === 'unrecorded')) {
            $clickQuery = CashbackClick::where('user_id', $user->id)
                ->whereDoesntHave('cashbackHistory');
            $applyCommonFilters($clickQuery, ['product_name', 'trans_id']);
        }

        // Giới hạn số bản ghi mỗi trang trong khoảng an toàn 1..50
        $perPage = (int) $request->query('per_page', 15);
        $perPage = max(1, min($perPage, 50));

        $records = $this->paginateOrderFeed($request, $historyQuery, $clickQuery, $perPage);
        $items = $records->getCollection()->map(function ($record): array {
            return $record instanceof CashbackClick
                ? $this->transformUnrecordedListItem($record)
                : $this->transformListItem($record);
        });

        return $this->ok([
            'items' => $items,
            'pagination' => [
                'current_page' => $records->currentPage(),
                'per_page' => $records->perPage(),
                'total' => $records->total(),
                'last_page' => $records->lastPage(),
            ],
            'meta' => [
                'show_unrecorded' => $showUnrecorded,
            ],
        ]);
    }

    /**
     * Keep Bot API behavior recorded-order-only while the Open API exposes the web parity feed.
     */
    protected function supportsUnrecordedRecords(): bool
    {
        return true;
    }

    protected function paginateOrderFeed(
        Request $request,
        $historyQuery,
        $clickQuery,
        int $perPage
    ): LengthAwarePaginator {
        if ($clickQuery === null) {
            return $historyQuery
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->paginate($perPage);
        }

        if ($historyQuery === null) {
            return $clickQuery
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->paginate($perPage);
        }

        $total = (clone $historyQuery)->count() + (clone $clickQuery)->count();
        $lastPage = max(1, (int) ceil($total / $perPage));
        $page = max(1, (int) $request->query('page', 1));
        $take = min($page, $lastPage) * $perPage;

        $historyItems = $historyQuery
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->take($take)
            ->get();
        $clickItems = $clickQuery
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->take($take)
            ->get();

        $merged = $historyItems->concat($clickItems)->sort(function ($left, $right): int {
            $leftTimestamp = optional($left->created_at)->getTimestamp() ?? 0;
            $rightTimestamp = optional($right->created_at)->getTimestamp() ?? 0;
            $dateOrder = $rightTimestamp <=> $leftTimestamp;
            if ($dateOrder !== 0) {
                return $dateOrder;
            }

            $leftIsClick = $left instanceof CashbackClick;
            $rightIsClick = $right instanceof CashbackClick;
            if ($leftIsClick !== $rightIsClick) {
                return $leftIsClick <=> $rightIsClick;
            }

            return $right->id <=> $left->id;
        })->values();

        return new LengthAwarePaginator(
            $merged->slice(($page - 1) * $perPage, $perPage)->values(),
            $total,
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );
    }

    protected function validDateFilter(mixed $value): ?string
    {
        if (! is_string($value) || ! preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value, $parts)) {
            return null;
        }

        return checkdate((int) $parts[2], (int) $parts[3], (int) $parts[1]) ? $value : null;
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
        if (! $order) {
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
            'record_type' => 'order',
            'order_id' => $order->order_id,
            'trans_id' => $order->trans_id, // Mã giao dịch đối soát (sub_id) để người dùng/bot liên kết đơn hàng với lượt click
            'platform' => $order->platform,
            'product_name' => $order->product_name,
            'product_image' => $order->product_image,
            'original_price' => (int) MoneyHelper::round($order->original_price),
            'commission_amount' => (int) MoneyHelper::round($order->commission_amount), // Tổng số tiền hoa hồng thực tế nhận từ sàn Shopee/TikTok
            'cashback_amount' => (int) MoneyHelper::round($order->cashback_amount),
            'cashback_rate' => (float) $order->cashback_rate,
            'affiliate_url' => $order->affiliate_url,
            'status' => $order->status,
            'rejected_reason' => $order->rejected_reason,
            'approved_at' => optional($order->approved_at)->toIso8601String(),
            'created_at' => optional($order->created_at)->toIso8601String(),
        ];
    }

    protected function transformUnrecordedListItem(CashbackClick $click): array
    {
        return [
            'id' => $click->id,
            'record_type' => 'unrecorded',
            'order_id' => null,
            'trans_id' => $click->trans_id,
            'platform' => $click->platform,
            'product_name' => $click->product_name,
            'product_image' => $click->product_image,
            'original_price' => (int) MoneyHelper::round($click->original_price),
            'commission_amount' => (int) MoneyHelper::round($click->commission_amount),
            'cashback_amount' => (int) MoneyHelper::round($click->cashback_amount),
            'cashback_rate' => (float) $click->cashback_rate,
            'affiliate_url' => $click->affiliate_url,
            'status' => 'unrecorded',
            'rejected_reason' => null,
            'approved_at' => null,
            'created_at' => optional($click->created_at)->toIso8601String(),
        ];
    }

    /**
     * Dữ liệu chi tiết một đơn hàng. Lớp con ghi đè để ẩn/thêm trường.
     */
    protected function transformDetail(CashbackHistory $order): array
    {
        return [
            'id' => $order->id,
            'record_type' => 'order',
            'order_id' => $order->order_id,
            'trans_id' => $order->trans_id,
            'platform' => $order->platform,
            'product_name' => $order->product_name,
            'product_image' => $order->product_image,
            'shop_name' => $order->shop_name,
            'original_price' => (int) MoneyHelper::round($order->original_price),
            'commission_amount' => (int) MoneyHelper::round($order->commission_amount),
            'cashback_amount' => (int) MoneyHelper::round($order->cashback_amount),
            'cashback_rate' => (float) $order->cashback_rate,
            'affiliate_url' => $order->affiliate_url,
            'status' => $order->status,
            'rejected_reason' => $order->rejected_reason,
            'approved_at' => optional($order->approved_at)->toIso8601String(),
            'created_at' => optional($order->created_at)->toIso8601String(),
        ];
    }
}
