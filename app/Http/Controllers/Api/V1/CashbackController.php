<?php

namespace App\Http\Controllers\Api\V1;

use App\Helpers\MoneyHelper;
use App\Helpers\SecurityHelper;
use App\Models\CashbackClick;
use App\Models\Setting;
use App\Services\LazadaCashbackService;
use App\Services\ShopeeCashbackService;
use App\Services\TikTokCashbackService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * API Lấy link hoàn tiền: nhận link sản phẩm Shopee/TikTok Shop/Lazada, phân giải và trả về
 * link affiliate rút gọn kèm thông tin hoàn tiền cho thành viên đang đăng nhập.
 *
 * Tái sử dụng nguyên vẹn logic bảo mật (chống SSRF, ràng buộc tên miền, giới hạn tần suất)
 * giống luồng web để đảm bảo an toàn khi mở ra cho App Mobile / Frontend.
 *
 * Danh sách sàn được hỗ trợ ở đây phải luôn khớp với HomeController@getProductInfo — nếu web
 * nhận link của một sàn mà API lại từ chối thì Bot và App Mobile sẽ báo lỗi sai cho khách hàng
 * dù hệ thống thực tế đã bật sàn đó.
 */
class CashbackController extends ApiController
{
    public function __construct(
        protected ShopeeCashbackService $shopeeCashbackService,
        protected TikTokCashbackService $tiktokCashbackService,
        protected LazadaCashbackService $lazadaCashbackService
    ) {}

    /**
     * POST /api/v1/openapi/cashback/link
     * Body: { "url": "https://shopee.vn/..." }
     */
    public function create(Request $request): JsonResponse
    {
        $user = $this->apiUser($request);

        // Giới hạn tần suất tạo link theo cấu hình (chống spam/cào dữ liệu)
        $rateLimit = (int) Setting::getVal('rate_limit_create_link_5m', 10);
        if ($rateLimit > 0) {
            $limiterKey = 'api-create-link:'.$user->id;
            if (RateLimiter::tooManyAttempts($limiterKey, $rateLimit)) {
                $minutesLeft = ceil(RateLimiter::availableIn($limiterKey) / 60);

                return $this->fail(
                    __('Bạn đã đạt giới hạn tạo link hoàn tiền (:limit link/5 phút). Vui lòng thử lại sau :minutes phút.', [
                        'limit' => $rateLimit,
                        'minutes' => $minutesLeft,
                    ]),
                    429,
                    'RATE_LIMITED'
                );
            }
            RateLimiter::hit($limiterKey, 300);
        }

        // Tự bổ sung https:// nếu thiếu giao thức
        $urlInput = $request->input('url');
        if ($urlInput && ! str_starts_with($urlInput, 'http://') && ! str_starts_with($urlInput, 'https://')) {
            $urlInput = 'https://'.$urlInput;
            $request->merge(['url' => $urlInput]);
        }

        try {
            $request->validate([
                'url' => 'required|url',
            ], [
                'url.required' => __('Vui lòng cung cấp link sản phẩm Shopee, TikTok Shop hoặc Lazada.'),
                'url.url' => __('Địa chỉ link sản phẩm không đúng định dạng.'),
            ]);
        } catch (ValidationException $e) {
            return $this->fail(__('Link sản phẩm không hợp lệ.'), 422, 'VALIDATION_ERROR', $e->errors());
        }

        $url = $request->input('url');

        // Chống SSRF: chặn truy vấn tới IP nội bộ / loopback
        if (! SecurityHelper::validateSsfUrl($url)) {
            return $this->fail(__('Đường dẫn sản phẩm không hợp lệ hoặc không an toàn.'), 422, 'UNSAFE_URL');
        }

        // Ràng buộc tên miền hợp lệ thuộc Shopee, TikTok Shop hoặc Lazada
        // (giữ đồng bộ với danh sách ở HomeController@getProductInfo)
        $host = strtolower(parse_url($url, PHP_URL_HOST) ?? '');
        $allowedDomains = [
            '/^(.*\.)?shopee\.(vn|sg|co\.id|com\.my|co\.th|ph|tw|com|cn|com\.br|cl|co|mx)$/i',
            '/^(.*\.)?shope\.ee$/i',
            '/^(.*\.)?shp\.ee$/i',
            '/^(.*\.)?tiktok\.com$/i',
            '/^(.*\.)?tiktok\.shop$/i',
            '/^(.*\.)?lazada\.(vn|sg|co\.id|com\.my|co\.th|com\.ph)$/i',
            '/^(.*\.)?lzd\.co$/i',
        ];
        $isValidDomain = false;
        foreach ($allowedDomains as $pattern) {
            if (preg_match($pattern, $host)) {
                $isValidDomain = true;
                break;
            }
        }
        if (! $isValidDomain) {
            return $this->fail(__('Hệ thống chỉ hỗ trợ xử lý đường dẫn sản phẩm chính thức từ Shopee, TikTok Shop hoặc Lazada.'), 422, 'DOMAIN_NOT_SUPPORTED');
        }

        // Phân loại sàn
        $platform = null;
        if (str_contains($url, 'shopee.') || str_contains($url, 'shope.ee') || str_contains($url, 'shp.ee')) {
            $platform = 'shopee';
        } elseif (str_contains($url, 'tiktok.com') || str_contains($url, 'tiktok.shop')) {
            $platform = 'tiktok';
        } elseif (str_contains($url, 'lazada.') || str_contains($url, 'lzd.co')) {
            $platform = 'lazada';
        }
        if (! $platform) {
            return $this->fail(__('Hệ thống hiện tại chỉ hỗ trợ hoàn tiền cho các sản phẩm từ các sàn thương mại điện tử liên kết.'), 422, 'PLATFORM_NOT_SUPPORTED');
        }

        // Kiểm tra trạng thái hoạt động của sàn tương ứng
        if ($platform === 'shopee' && Setting::getVal('shopee_status', '1') === '0') {
            return $this->fail(__('Tính năng hoàn tiền Shopee hiện đang tạm bảo trì hoặc tạm ngưng hoạt động.'), 422, 'PLATFORM_MAINTENANCE');
        }
        if ($platform === 'tiktok' && Setting::getVal('tiktok_status', '1') === '0') {
            return $this->fail(__('Tính năng hoàn tiền TikTok Shop hiện đang tạm bảo trì hoặc tạm ngưng hoạt động.'), 422, 'PLATFORM_MAINTENANCE');
        }
        // Lazada mặc định TẮT ('0') khác với Shopee/TikTok — sàn này cần Admin cấu hình App Key
        // của Lazada Open Platform rồi mới bật được, nên không thể mặc định coi là đang hoạt động.
        if ($platform === 'lazada' && Setting::getVal('lazada_status', '0') === '0') {
            return $this->fail(__('Tính năng hoàn tiền Lazada hiện đang tạm bảo trì hoặc tạm ngưng hoạt động.'), 422, 'PLATFORM_MAINTENANCE');
        }

        // Sinh mã đối soát và gọi service phân giải sản phẩm tương ứng với sàn
        $transId = Setting::generateOrderCode($platform);
        $response = match ($platform) {
            'tiktok' => $this->tiktokCashbackService->getProductData($url, $user->id, $transId),
            'lazada' => $this->lazadaCashbackService->getProductData($url, $user->id, $transId),
            default => $this->shopeeCashbackService->getProductData($url, $user->id, $transId),
        };

        if (($response['status'] ?? '') !== 'success') {
            return $this->fail(
                $response['message'] ?? __('Không thể phân tích thông tin sản phẩm từ sàn thương mại. Vui lòng kiểm tra lại đường dẫn.'),
                400,
                'RESOLVE_FAILED'
            );
        }

        $productData = $response['data'];
        $productData['platform'] = $platform;

        // Lưu vết click phục vụ đối soát đơn hàng sau này (khớp trans_id)
        CashbackClick::create([
            'user_id' => $user->id,
            'platform' => $platform,
            'trans_id' => $transId,
            'product_name' => Str::limit($productData['name'], 500, '...'),
            // Ảnh có thể trống: riêng Lazada, API tạo link không trả ảnh nên service phải cào thẻ
            // Open Graph — cào hụt thì giá trị là null (giống guard ở HomeController@getProductInfo).
            'product_image' => Str::limit($productData['image'] ?? '', 1000, ''),
            'original_price' => $productData['price'],
            'cashback_amount' => $productData['cashback_amount'],
            'cashback_rate' => $productData['cashback_rate'],
            'commission_amount' => $productData['commission_amount'],
            'affiliate_url' => $productData['affiliate_url'],
        ]);

        return $this->ok([
            'trans_id' => $transId,
            'platform' => $platform,
            'name' => $productData['name'],
            'image' => $productData['image'] ?? null,
            'price' => (int) MoneyHelper::round($productData['price']),
            'commission_amount' => (int) MoneyHelper::round($productData['commission_amount']), // Số tiền hoa hồng ước tính thực tế nhận từ sàn
            'cashback_amount' => (int) MoneyHelper::round($productData['cashback_amount']),
            'cashback_rate' => (float) $productData['cashback_rate'],
            // Cờ báo số liệu chỉ là ƯỚC TÍNH vì không lấy được giá bán thật (hay gặp ở Lazada: API tạo
            // link không trả giá). Khi bằng true thì price và cashback_amount có thể bằng 0 — hệ thống
            // ngoài phải hiển thị theo cashback_rate ("hoàn khoảng X%") thay vì báo cho khách là 0đ.
            'is_estimated' => (bool) ($productData['is_estimated'] ?? false),
            // Link rút gọn/affiliate để người dùng bấm mua hàng và được ghi nhận hoàn tiền
            'affiliate_url' => $productData['affiliate_url'],
        ], __('Lấy link hoàn tiền thành công!'));
    }
}
