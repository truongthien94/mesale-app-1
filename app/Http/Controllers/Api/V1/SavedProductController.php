<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\ActivityLog;
use App\Models\SavedProduct;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * API Sản phẩm đã lưu (mua sau) của thành viên.
 */
class SavedProductController extends ApiController
{
    /**
     * GET /api/v1/openapi/saved-products
     * Query: page, per_page (tối đa 50)
     */
    public function index(Request $request): JsonResponse
    {
        $user = $this->apiUser($request);

        $perPage = max(1, min((int) $request->query('per_page', 12), 50));
        $products = SavedProduct::where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->paginate($perPage);

        return $this->ok([
            'items' => $products->getCollection()->map(fn (SavedProduct $p) => [
                'id' => $p->id,
                'platform' => $p->platform,
                'name' => $p->name,
                'image' => $p->image,
                'price' => (float) $p->price,
                'cashback_amount' => (float) $p->cashback_amount,
                'affiliate_url' => $p->affiliate_url,
                'product_url' => $p->product_url,
                'created_at' => optional($p->created_at)->toIso8601String(),
            ]),
            'pagination' => [
                'current_page' => $products->currentPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
                'last_page' => $products->lastPage(),
            ],
        ]);
    }

    /**
     * POST /api/v1/openapi/saved-products
     * Body: platform, name, image, price, cashback_amount, affiliate_url, product_url
     */
    public function store(Request $request): JsonResponse
    {
        $user = $this->apiUser($request);

        try {
            $validated = $request->validate([
                'platform' => 'nullable|string|max:50',
                'name' => 'required|string|max:500',
                'image' => 'nullable|string',
                'price' => 'required|numeric|min:0',
                'cashback_amount' => 'required|numeric|min:0',
                'affiliate_url' => 'required|string',
                'product_url' => 'nullable|string',
            ]);
        } catch (ValidationException $e) {
            return $this->fail(__('Dữ liệu không hợp lệ.'), 422, 'VALIDATION_ERROR', $e->errors());
        }

        // Quy tắc nghiệp vụ: Không lưu lại các sản phẩm bị thiếu thông tin giá bán (sản phẩm dạng ước tính)
        if ((float)$validated['price'] <= 0) {
            return $this->fail(__('Sản phẩm này chưa lấy được đầy đủ thông tin giá bán nên không thể lưu lại. Vui lòng thử lấy link lại sau.'), 422, 'MISSING_PRICE');
        }

        // Chống lưu trùng sản phẩm
        $exists = SavedProduct::where('user_id', $user->id)
            ->where(function ($query) use ($request) {
                if ($request->filled('product_url')) {
                    $query->where('product_url', $request->product_url);
                } else {
                    $query->where('affiliate_url', $request->affiliate_url);
                }
            })
            ->exists();
        if ($exists) {
            return $this->fail(__('Sản phẩm này đã có trong danh sách lưu trữ của bạn.'), 409, 'ALREADY_SAVED');
        }

        $product = SavedProduct::create([
            'user_id' => $user->id,
            'platform' => $validated['platform'] ?? 'shopee',
            'name' => $validated['name'],
            'image' => $validated['image'] ?? null,
            'price' => $validated['price'],
            'cashback_amount' => $validated['cashback_amount'],
            'affiliate_url' => $validated['affiliate_url'],
            'product_url' => $validated['product_url'] ?? null,
        ]);

        ActivityLog::log(__('Lưu sản phẩm để mua sau (qua Open API): :name', ['name' => $product->name]), $user->id);

        return $this->ok([
            'id' => $product->id,
        ], __('Đã lưu sản phẩm thành công!'), 201);
    }

    /**
     * DELETE /api/v1/openapi/saved-products/{id}
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $user = $this->apiUser($request);

        $product = SavedProduct::where('user_id', $user->id)->find($id);
        if (!$product) {
            return $this->fail(__('Không tìm thấy sản phẩm đã lưu.'), 404, 'NOT_FOUND');
        }

        ActivityLog::log(__('Xóa sản phẩm đã lưu (qua Open API): :name', ['name' => $product->name]), $user->id);
        $product->delete();

        return $this->ok(null, __('Đã xóa sản phẩm khỏi danh sách lưu trữ thành công.'));
    }
}
