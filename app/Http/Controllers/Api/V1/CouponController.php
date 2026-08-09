<?php

namespace App\Http\Controllers\Api\V1;

use App\Helpers\MoneyHelper;
use App\Models\Coupon;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * API Danh sách mã giảm giá (coupon) đang hiệu lực.
 */
class CouponController extends ApiController
{
    /**
     * GET /api/v1/openapi/coupons
     * Query: platform, category, search, page, per_page (tối đa 50)
     */
    public function index(Request $request): JsonResponse
    {
        if (Setting::getVal('coupon_status', '1') !== '1') {
            return $this->fail(__('Chức năng mã giảm giá hiện tại đang tạm khóa.'), 403, 'COUPON_DISABLED');
        }

        $query = Coupon::query()
            ->where('platform', 'shopee')
            ->where(function ($q) {
                $q->whereNull('expired_at')->orWhere('expired_at', '>=', now());
            });

        if (($platform = $request->query('platform')) && $platform !== 'all') {
            $query->where('platform', $platform);
        }
        if (($category = $request->query('category')) && $category !== 'all') {
            $query->where('category', $category);
        }
        if ($search = trim((string) $request->query('search', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhere('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $perPage = max(1, min((int) $request->query('per_page', 12), 50));
        $coupons = $query->orderByDesc('created_at')->paginate($perPage);

        // Danh mục coupon Shopee còn hiệu lực để App dựng bộ lọc
        $categories = Coupon::where('platform', 'shopee')
            ->where(function ($q) {
                $q->whereNull('expired_at')->orWhere('expired_at', '>=', now());
            })
            ->whereNotNull('category')->where('category', '!=', '')
            ->distinct()->pluck('category')->values();

        return $this->ok([
            'items' => $coupons->getCollection()->map(fn (Coupon $c) => [
                'id' => $c->id,
                'platform' => $c->platform,
                'code' => $c->code,
                'title' => $c->title,
                'description' => $c->description,
                'category' => $c->category,
                'min_spend' => (int) MoneyHelper::round($c->min_spend),
                'discount_amount' => (int) MoneyHelper::round($c->discount_amount),
                'discount_percentage' => (float) $c->discount_percentage,
                'image_url' => $c->image_url,
                'redirect_link' => $c->redirect_link,
                'expired_at' => optional($c->expired_at)->toIso8601String(),
            ]),
            'categories' => $categories,
            'pagination' => [
                'current_page' => $coupons->currentPage(),
                'per_page' => $coupons->perPage(),
                'total' => $coupons->total(),
                'last_page' => $coupons->lastPage(),
            ],
        ]);
    }
}
