<?php

namespace App\Services;

use App\Repositories\Contracts\ProductRepositoryInterface;
use App\Models\Setting;
use App\Models\ShortLink;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Service xử lý link sản phẩm Lazada và lấy thông tin hoàn tiền qua Lazada Affiliate API.
 * Khác biệt so với Shopee/TikTok:
 *  - API tạo tracking link của Lazada nhận productId (long), không nhận URL → phải trích productId từ URL.
 *  - Response tạo link chỉ trả về commisionRate (tỉ lệ %), productName, trackingLink; KHÔNG có giá và ảnh sản phẩm.
 *    Vì vậy hệ thống bổ sung thêm cấu hình API thông tin sản phẩm Lazada bên ngoài (apilazada_product_*) có cấu trúc
 *    giống API Shopee để lấy tên, ảnh, giá bán và hoa hồng thực tế. Khi API này tắt hoặc gọi thất bại, sản phẩm
 *    quay về dạng ước tính (is_estimated) nhưng vẫn hiển thị được tỉ lệ hoàn tiền theo commisionRate.
 *  - API tạo link KHÔNG có tham số sub_id → tự nối sub_idN={transId} vào trackingLink để đối soát về sau.
 */
class LazadaCashbackService
{
    protected ProductRepositoryInterface $productRepository;

    /**
     * Khởi tạo service với ProductRepository.
     *
     * @param ProductRepositoryInterface $productRepository
     */
    public function __construct(ProductRepositoryInterface $productRepository)
    {
        $this->productRepository = $productRepository;
    }

    /**
     * Xử lý link sản phẩm Lazada và lấy thông tin hoàn tiền qua Lazada Affiliate API.
     *
     * @param string $url URL sản phẩm Lazada
     * @param int|null $userId ID người dùng
     * @param string|null $transId Mã giao dịch đối soát
     * @return array
     */
    public function getProductData(string $url, ?int $userId = null, ?string $transId = null): array
    {
        // 1. Chuẩn hoá URL đầu vào và giải quyết link rút gọn (s.lazada.vn, c.lazada...)
        $url = trim($url);
        $url = $this->resolveShortLink($url);

        // 2. Đọc cấu hình kết nối Lazada Affiliate API từ Setting hoặc file môi trường (.env)
        $apiUrl = trim(Setting::getVal('apilazada_url')) ?: config('services.lazada.api_url', 'https://api.lazada.vn/rest');
        $userToken = Setting::getVal('apilazada_user_token') ?: config('services.lazada.user_token');
        $appKey = Setting::getVal('apilazada_app_key') ?: config('services.lazada.app_key');
        $appSecret = Setting::getVal('apilazada_app_secret') ?: config('services.lazada.app_secret');
        $subIdParam = trim(Setting::getVal('lazada_subid_param')) ?: 'sub_id1';

        // Business Rule: Cần đủ cả 3 thông tin xác thực API mới hoạt động được, thiếu 1 là chặn
        if (empty($userToken) || empty($appKey) || empty($appSecret)) {
            Log::error("Lazada integration error: Thiếu cấu hình API Lazada (userToken/appKey/appSecret).");
            return [
                'status' => 'error',
                'message' => __('Hệ thống chưa được cấu hình cho Lazada. Vui lòng liên hệ quản trị viên.')
            ];
        }

        // 3. Trích xuất productId từ URL Lazada (mẫu -i{id} trong đường dẫn sản phẩm)
        $productId = $this->extractProductId($url);
        if (empty($productId)) {
            Log::warning("Lazada API - Không trích được productId từ URL: " . $url);
            return [
                'status' => 'error',
                'message' => __('Không thể phân tích ID sản phẩm từ liên kết Lazada này. Vui lòng dán link chi tiết sản phẩm.')
            ];
        }

        // Sử dụng trans_id làm sub_id để đối soát đơn hàng sau này. Nếu không có (demo/khách vãng lai), sinh mã tạm.
        $subId = $transId ?: ($userId ? 'member_' . $userId : 'guest_' . time());

        try {
            // Ràng buộc bảo mật (Anti-SSRF): Xác thực URL API Lazada không trỏ tới dải IP Private/Loopback nội bộ
            if (!\App\Helpers\SecurityHelper::validateSsfUrl($apiUrl)) {
                Log::error("Lazada API URL is blocked by SSRF protector: " . $apiUrl);
                return [
                    'status' => 'error',
                    'message' => __('Cấu hình kết nối hệ thống đối tác Lazada không hợp lệ hoặc không an toàn.')
                ];
            }

            // 4. Kiểm tra Cache sản phẩm cục bộ trước khi gọi API tạo link
            // Sử dụng tiền tố 'lazada_' làm khóa duy nhất để phân biệt với Shopee/TikTok trong bảng products
            $lazadaDbId = 'lazada_' . $productId;
            $cachedProduct = $this->productRepository->findByShopeeId($lazadaDbId);

            $cacheHours = (int)Setting::getVal('cache_clean_estimated_hours_lazada', 24);

            // Tối ưu chi phí gọi API thông tin sản phẩm (mỗi lượt gọi có thể bị trừ phí): chỉ gọi lại khi dữ liệu
            // giá đã hết hạn làm mới.
            // LƯU Ý QUAN TRỌNG: KHÔNG dùng cột products.updated_at để đo thời hạn làm mới, vì mỗi lượt dán link đều
            // ghi lại affiliate_url mới (sub_id khác nhau) khiến updated_at luôn được cập nhật → giá bán sẽ bị đóng băng
            // vĩnh viễn với các sản phẩm được tra cứu thường xuyên. Thay vào đó dùng một khoá cache riêng có thời gian
            // sống bằng đúng số giờ cấu hình để mốc làm mới giá luôn chính xác.
            $refreshHours = max(1, $cacheHours);
            $apiSyncedCacheKey = 'lazada_product_api_synced:' . $productId;

            $hasFreshPricedCache = $cachedProduct
                && (float)$cachedProduct->price > 0
                && Cache::has($apiSyncedCacheKey);

            // 5. Gọi Lazada API tạo tracking link (luôn gọi để lấy tracking link mới, kể cả khi có cache thông tin sản phẩm)
            Log::info("Lazada API - Creating link for productId: " . $productId . " with sub_id: " . $subId);

            // Dựng tham số request: nếu có đủ app_key + app_secret thì ký chuẩn Lazada Open Platform,
            // ngược lại fallback gửi userToken thô (dành cho trường hợp gọi qua trung gian).
            $apiPath = '/marketing/product/link';
            if (\App\Helpers\LazadaApiSigner::canSign($appKey, $appSecret)) {
                // userToken là tham số nghiệp vụ (theo tài liệu), KHÔNG phải OAuth access_token → đưa vào params để ký.
                $queryParams = \App\Helpers\LazadaApiSigner::signedParams(
                    $apiPath,
                    ['productId' => $productId, 'userToken' => $userToken],
                    $appKey,
                    $appSecret,
                    null
                );
            } else {
                $queryParams = [
                    'userToken' => $userToken,
                    'productId' => $productId
                ];
            }

            $response = Http::withoutVerifying()
                ->timeout(15)
                ->withHeaders([
                    'Accept' => 'application/json'
                ])
                ->get(rtrim($apiUrl, '/') . $apiPath, $queryParams);

            if (!$response->successful()) {
                Log::error("Lazada API - Create Link failed: " . $response->status() . " - " . $response->body());
                return [
                    'status' => 'error',
                    'message' => __('Không thể tạo link hoàn tiền Lazada. Vui lòng thử lại sau.')
                ];
            }

            $linkData = $response->json();

            // Lazada trả về mã lỗi ở cấp cao nhất khi request sai (thiếu app_key, sai chữ ký, token hết hạn...).
            if (!empty($linkData['code']) && $linkData['code'] !== '0' && $linkData['code'] !== 0) {
                Log::error("Lazada API - Create Link error: " . $response->body());

                // Trường hợp thường gặp nhất: sản phẩm không tham gia chương trình tiếp thị liên kết của Lazada,
                // API trả về lỗi dạng "Invalid Param:invalid product". Đây là lỗi của sản phẩm chứ không phải lỗi
                // cấu hình hệ thống nên phải hiển thị thông báo thân thiện, dễ hiểu cho người dùng cuối.
                if ($this->isProductNotEligibleError($linkData['code'] ?? null, $linkData['message'] ?? null)) {
                    return $this->productNotEligibleResponse();
                }

                // Các lỗi còn lại (sai chữ ký, token hết hạn...) hiển thị thông báo cụ thể để quản trị viên dễ chẩn đoán
                return [
                    'status' => 'error',
                    'message' => __('Lỗi từ Lazada: :message', ['message' => $linkData['message'] ?? ($linkData['code'] . '')])
                ];
            }

            // Lazada Open Platform bọc dữ liệu trong result.data; một số bản tài liệu rút gọn trả thẳng data.
            $payload = $linkData['result']['data'] ?? $linkData['data'] ?? $linkData;

            $trackingLink = $payload['trackingLink'] ?? null;
            $productName = $payload['productName'] ?? __('Sản phẩm Lazada');
            $commissionRate = $this->parseRate($payload['commisionRate'] ?? $payload['commissionRate'] ?? 0);
            // Lazada trả tỉ lệ hoa hồng dạng phân số (ví dụ 0.04 = 4%) → quy đổi sang phần trăm để hiển thị.
            if ($commissionRate > 0 && $commissionRate < 1) {
                $commissionRate *= 100;
            }

            // Không có tracking link nghĩa là Lazada từ chối tạo link tiếp thị cho sản phẩm này
            if (empty($trackingLink)) {
                Log::error("Lazada API - No trackingLink returned. Body: " . $response->body());
                return $this->productNotEligibleResponse();
            }

            // 6. Nối sub-id (mã đối soát) vào tracking link để về sau khớp với conversion report
            $affiliateUrl = $this->appendSubId($trackingLink, $subIdParam, $subId);

            // 6.1. Gọi API thông tin sản phẩm Lazada bên ngoài (APISHOPEE) để lấy tên, ảnh, giá bán và hoa hồng thực tế.
            // Nhờ đó sản phẩm Lazada không còn phải hiển thị dạng ước tính như trước.
            // Chỉ gọi SAU KHI Lazada đã xác nhận sản phẩm hợp lệ để không tốn phí gọi API cho các sản phẩm
            // nằm ngoài chương trình tiếp thị liên kết (vốn chắc chắn sẽ bị từ chối tạo link).
            $apiProduct = $hasFreshPricedCache ? null : $this->fetchProductInfoFromApi($url, $productId);

            // Đánh dấu mốc đã lấy được giá bán từ API để tạm thời không gọi lại trong khoảng thời gian cấu hình
            if (!empty($apiProduct['price']) && (float)$apiProduct['price'] > 0) {
                Cache::put($apiSyncedCacheKey, true, now()->addHours($refreshHours));
            }

            // 7. Xác định tên, ảnh, giá bán và hoa hồng thực tế của sản phẩm.
            // Thứ tự ưu tiên dữ liệu: API thông tin sản phẩm (đầy đủ nhất) → cache cục bộ → API tạo link Lazada → cào meta trang chi tiết.
            $hasApiName = !empty($apiProduct['name']);
            $displayName = $apiProduct['name']
                ?? (($cachedProduct && !empty($cachedProduct->name)) ? $cachedProduct->name : $productName);
            $productImage = $apiProduct['image'] ?? ($cachedProduct->image ?? null);

            // Giá bán và hoa hồng chỉ có khi lấy được từ API thông tin sản phẩm; nếu không có thì tận dụng lại cache cũ
            $price = (float)($apiProduct['price'] ?? 0);
            $commissionAmount = (float)($apiProduct['commission'] ?? 0);
            if ($price <= 0 && $cachedProduct && (float)$cachedProduct->price > 0) {
                $price = (float)$cachedProduct->price;
                $commissionAmount = (float)$cachedProduct->commission_amount;
            }

            // Ràng buộc hợp lý dữ liệu bên ngoài: hoa hồng không thể lớn hơn chính giá bán sản phẩm.
            // Nếu API trả về giá trị vô lý, bỏ qua và tính lại theo tỉ lệ hoa hồng của Lazada để không hiển thị sai lệch.
            if ($price > 0 && $commissionAmount > $price) {
                Log::warning("Lazada Product API - Hoa hồng trả về lớn hơn giá bán (price={$price}, commission={$commissionAmount}), bỏ qua giá trị này.");
                $commissionAmount = 0.0;
            }

            // Nếu API thông tin sản phẩm không trả về hoa hồng, ước lượng theo tỉ lệ hoa hồng của API tạo link
            if ($price > 0 && $commissionAmount <= 0 && $commissionRate > 0) {
                $commissionAmount = $price * ($commissionRate / 100);
            }

            // Ngược lại, nếu API tạo link không trả tỉ lệ hoa hồng thì suy ngược ra từ giá bán và hoa hồng thực tế
            if ($commissionRate <= 0 && $price > 0 && $commissionAmount > 0) {
                $commissionRate = round(($commissionAmount / $price) * 100, 2);
            }

            // Ràng buộc nghiệp vụ: Không lấy được bất kỳ thông tin hoa hồng nào (cả tỉ lệ lẫn số tiền đều bằng 0)
            // đồng nghĩa sản phẩm không nằm trong chương trình tiếp thị liên kết của Lazada. Chặn luôn tại đây để
            // người dùng không nhận về một liên kết hoàn tiền 0đ vô nghĩa.
            if ($commissionRate <= 0 && $commissionAmount <= 0) {
                Log::warning("Lazada API - Sản phẩm không có thông tin hoa hồng (productId: {$productId}). Body: " . $response->body());
                return $this->productNotEligibleResponse();
            }

            $cashbackSystemRate = (float)Setting::getVal('lazada_fake_cashback_rate', 50);

            if ($price > 0) {
                // Có giá bán thực tế → tính được số tiền hoàn cụ thể giống Shopee/TikTok Shop
                // Chuẩn hoá về số nguyên đồng vì đồng Việt Nam không có đơn vị nhỏ hơn 1 đồng
                $cashbackAmount = \App\Helpers\MoneyHelper::round($commissionAmount * ($cashbackSystemRate / 100));
                $cashbackRate = round(($cashbackAmount / $price) * 100, 2);
                $isEstimated = false;
            } else {
                // Không lấy được giá (chưa bật API thông tin sản phẩm hoặc gọi thất bại)
                // → chỉ hiển thị tỉ lệ hoàn ước tính = commisionRate × (lazada_fake_cashback_rate/100)
                $cashbackAmount = 0.0;
                $cashbackRate = round($commissionRate * ($cashbackSystemRate / 100), 2);
                $isEstimated = true;
            }

            // Ràng buộc an toàn dữ liệu: cột cashback_rate chỉ lưu tối đa 999.99 nên phải chặn trên giá trị bất thường
            // (ví dụ API bên ngoài trả về hoa hồng lớn hơn cả giá bán) để tránh gây lỗi ghi cơ sở dữ liệu.
            $cashbackRate = \App\Helpers\CashbackHelper::clampRate($cashbackRate);
            $commissionRate = \App\Helpers\CashbackHelper::clampRate($commissionRate);

            // Nếu vẫn chưa có ảnh sản phẩm (API thông tin sản phẩm đang tắt hoặc gọi lỗi), cào thẻ Open Graph
            // của trang chi tiết Lazada để lấy ảnh và tên tiếng Việt thân thiện hơn tên tiếng Anh từ API tạo link.
            if (empty($productImage)) {
                $meta = $this->fetchProductMetaFromPage($url);
                if (!empty($meta['image'])) {
                    $productImage = $meta['image'];
                }
                // Chỉ ghi đè tên khi chưa có tên chuẩn từ API thông tin sản phẩm
                if (!empty($meta['title']) && !$hasApiName) {
                    $displayName = $meta['title'];
                }
            }

            // 8. Lưu/cập nhật cache sản phẩm (giữ lại tên, ảnh, giá bán & hoa hồng để đối soát nhanh)
            // Lưu ý: Repository sẽ tự động bỏ qua không lưu nếu sản phẩm thiếu thông tin (không lấy được giá bán),
            // nhờ đó bảng cache không bị lấp đầy các bản ghi ước tính 0đ và lượt tra cứu sau vẫn gọi lại API.
            $preparedDbData = [
                'shopee_id' => $lazadaDbId,
                'name' => Str::limit($displayName, 250, '...'),
                'image' => $productImage,
                'price' => \App\Helpers\MoneyHelper::round($price),
                'commission_amount' => \App\Helpers\MoneyHelper::round($commissionAmount),
                // Lưu tỉ lệ hoa hồng (%) vào cột shopee_commission để tham chiếu nhanh khi cần.
                // LƯU Ý QUAN TRỌNG: với riêng sàn Lazada thì cột này chứa TỶ LỆ phần trăm chứ không phải SỐ TIỀN
                // như hai sàn Shopee và TikTok Shop. Vì vậy tuyệt đối không được chuẩn hoá nó về số nguyên đồng,
                // làm vậy sẽ phá hỏng tỷ lệ hoa hồng của sản phẩm (ví dụ 4,5% bị đẩy thành 5%).
                'shopee_commission' => $commissionRate,
                'cashback_amount' => \App\Helpers\MoneyHelper::round($cashbackAmount),
                'cashback_rate' => $cashbackRate,
                'affiliate_url' => $affiliateUrl
            ];
            $this->productRepository->createOrUpdate($preparedDbData);
            if ($cachedProduct) {
                $this->productRepository->incrementSearchCount($cachedProduct);
            }

            $productData = [
                'shopee_id' => $lazadaDbId,
                'name' => $displayName,
                'image' => $productImage,
                'price' => \App\Helpers\MoneyHelper::round($price),
                'commission_amount' => \App\Helpers\MoneyHelper::round($commissionAmount),
                'cashback_amount' => \App\Helpers\MoneyHelper::round($cashbackAmount),
                'cashback_rate' => $cashbackRate,
                'affiliate_url' => $affiliateUrl,
                // Chỉ là dữ liệu ước tính khi không lấy được giá bán thực tế của sản phẩm
                'is_estimated' => $isEstimated
            ];
            $source = $hasFreshPricedCache ? 'cache' : 'api';

            // 9. Tạo liên kết rút gọn an toàn (Short Link) nếu cấu hình hệ thống bật
            $isShortLinkEnabled = Setting::getVal('shortlink_status', '0') === '1';
            if ($isShortLinkEnabled && !empty($productData['affiliate_url'])) {
                $shortCode = $this->generateUniqueShortCode();
                ShortLink::create([
                    'code' => $shortCode,
                    'destination_url' => $productData['affiliate_url'],
                    'product_name' => $productData['name'] ?? null,
                    'product_image' => $productData['image'] ?? null,
                    'user_id' => $userId,
                    'clicks' => 0
                ]);

                $shortDomain = Setting::getVal('shortlink_domain');
                if (!empty($shortDomain)) {
                    $shortDomain = rtrim($shortDomain, '/');
                    if (!str_starts_with($shortDomain, 'http://') && !str_starts_with($shortDomain, 'https://')) {
                        $shortDomain = 'https://' . $shortDomain;
                    }
                    $productData['affiliate_url'] = $shortDomain . '/' . $shortCode;
                } else {
                    $productData['affiliate_url'] = url('/' . $shortCode);
                }
            }

            return [
                'status' => 'success',
                'source' => $source,
                'data' => $productData
            ];
        } catch (\Exception $e) {
            Log::error("Lazada API error: " . $e->getMessage() . "\n" . $e->getTraceAsString());
            return [
                'status' => 'error',
                'message' => __('Đã xảy ra lỗi trong quá trình kết nối với hệ thống đối tác Lazada.')
            ];
        }
    }

    /**
     * Phản hồi chuẩn khi sản phẩm không nằm trong chương trình tiếp thị liên kết của Lazada.
     * Gom về một chỗ để mọi nhánh xử lý đều trả về cùng một thông báo thân thiện với người dùng.
     *
     * @return array
     */
    private function productNotEligibleResponse(): array
    {
        return [
            'status' => 'error',
            'message' => __('Sản phẩm này không nằm trong chương trình tiếp thị liên kết của Lazada nên không tạo được link hoàn tiền.')
        ];
    }

    /**
     * Nhận diện lỗi từ Lazada thuộc nhóm "sản phẩm không hợp lệ / không tham gia chương trình affiliate".
     *
     * Lazada không có mã lỗi riêng cho trường hợp này mà trả về lỗi tham số chung kèm mô tả, ví dụ:
     *   {"type":"ISV","code":"IllegalParam","message":"Invalid Param:invalid product"}
     * Vì vậy phải xét cả mã lỗi lẫn nội dung mô tả để phân biệt với các lỗi cấu hình hệ thống
     * (sai chữ ký, token hết hạn...) vốn cần hiển thị nguyên văn cho quản trị viên.
     *
     * @param mixed $code Mã lỗi Lazada trả về
     * @param mixed $message Nội dung mô tả lỗi
     * @return bool
     */
    private function isProductNotEligibleError($code, $message): bool
    {
        $code = strtolower(trim((string)$code));
        $message = strtolower(trim((string)$message));

        // 1. Các mã lỗi chỉ đích danh sản phẩm không hợp lệ
        $productErrorCodes = ['invalidproduct', 'illegalproduct', 'productnotfound', 'invalid_product', 'product_not_found'];
        if (in_array($code, $productErrorCodes, true)) {
            return true;
        }

        // 2. Nội dung mô tả có nhắc trực tiếp tới sản phẩm không hợp lệ / không có hoa hồng
        $productErrorMessages = [
            'invalid product',
            'product is invalid',
            'product not found',
            'product does not exist',
            'product not exist',
            'item not found',
            'no commission',
            'not in the affiliate',
            'not join',
        ];
        foreach ($productErrorMessages as $needle) {
            if ($needle !== '' && str_contains($message, $needle)) {
                return true;
            }
        }

        // 3. Lỗi tham số chung nhưng mô tả có nhắc tới product/item → quy về lỗi sản phẩm không hợp lệ
        $paramErrorCodes = ['illegalparam', 'invalidparam', 'illegal_param', 'invalid_param', 'param_error'];
        if (in_array($code, $paramErrorCodes, true) && (str_contains($message, 'product') || str_contains($message, 'item'))) {
            return true;
        }

        return false;
    }

    /**
     * Gọi API thông tin sản phẩm Lazada bên ngoài (hệ thống APISHOPEE) để lấy tên, ảnh, giá bán và hoa hồng.
     *
     * Cấu trúc endpoint và cách xác thực giống hệt API Shopee đang dùng:
     *   GET {apilazada_product_url}?product_link={link}  kèm Header  X-API-KEY: {apilazada_product_key}
     * Tham số product_link nhận: link sản phẩm đầy đủ, link rút gọn chia sẻ (s.lazada.vn) hoặc trực tiếp itemId dạng số.
     * Phản hồi mẫu: { price, sales, rating, imageUrl, shopName, commission, productLink, productName }
     *
     * @param string $url URL sản phẩm Lazada đã được phân giải link rút gọn
     * @param string|null $productId Mã itemId đã bóc tách từ URL (dùng dự phòng khi URL không khả dụng)
     * @return array|null Mảng thông tin đã chuẩn hoá, hoặc null nếu cấu hình đang tắt / gọi API thất bại
     */
    private function fetchProductInfoFromApi(string $url, ?string $productId = null): ?array
    {
        // Chỉ hoạt động khi quản trị viên bật cấu hình API thông tin sản phẩm Lazada trong Admin Panel
        if (Setting::getVal('apilazada_product_status', '0') !== '1') {
            return null;
        }

        $apiUrl = trim((string)Setting::getVal('apilazada_product_url', 'https://apishopee.cmsnt.co/api/v1/lazada/product'));
        $apiKey = trim((string)Setting::getVal('apilazada_product_key', ''));

        if (empty($apiUrl)) {
            Log::warning('Lazada Product API: Chưa cấu hình đường dẫn endpoint API thông tin sản phẩm.');
            return null;
        }

        // Ràng buộc bảo mật (Anti-SSRF): Đảm bảo endpoint không trỏ về mạng nội bộ hoặc dải IP Private
        if (!\App\Helpers\SecurityHelper::validateSsfUrl($apiUrl)) {
            Log::error('Lazada Product API URL is blocked by SSRF protector: ' . $apiUrl);
            return null;
        }

        try {
            Log::info('Lazada Product API request: ' . $url);

            $response = Http::withoutVerifying()
                ->timeout(15)
                ->withHeaders([
                    'X-API-KEY' => $apiKey,
                    'Accept' => 'application/json'
                ])
                ->get($apiUrl, [
                    'product_link' => $url !== '' ? $url : (string)$productId
                ]);

            if (!$response->successful()) {
                Log::warning('Lazada Product API failed: ' . $response->status() . ' - ' . $response->body());
                return null;
            }

            $data = $response->json();
            if (!is_array($data)) {
                Log::warning('Lazada Product API returned invalid payload: ' . $response->body());
                return null;
            }

            // Một số phiên bản API bọc dữ liệu trong khoá data, số còn lại trả thẳng ở cấp cao nhất
            $payload = (isset($data['data']) && is_array($data['data'])) ? $data['data'] : $data;

            // Bắt buộc phải có tối thiểu tên sản phẩm hoặc giá bán mới coi là phản hồi hợp lệ
            if (empty($payload['productName']) && empty($payload['price'])) {
                Log::warning('Lazada Product API returned unexpected format: ' . $response->body());
                return null;
            }

            return [
                'name' => !empty($payload['productName']) ? (string)$payload['productName'] : null,
                'image' => !empty($payload['imageUrl']) ? (string)$payload['imageUrl'] : null,
                'price' => $this->parseCurrencyValue($payload['price'] ?? 0),
                'commission' => $this->parseCurrencyValue($payload['commission'] ?? 0),
                'shop_name' => !empty($payload['shopName']) ? (string)$payload['shopName'] : null,
                'product_link' => !empty($payload['productLink']) ? (string)$payload['productLink'] : null,
            ];
        } catch (\Throwable $e) {
            Log::error('Lazada Product API error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Chuẩn hoá giá trị tiền tệ trả về từ API (có thể là số hoặc chuỗi định dạng "802.890₫").
     *
     * @param mixed $value
     * @return float
     */
    private function parseCurrencyValue($value): float
    {
        if (is_numeric($value)) {
            return (float)$value;
        }

        // Định dạng tiền Việt dùng dấu chấm phân cách hàng nghìn nên loại bỏ toàn bộ ký tự không phải chữ số
        $clean = preg_replace('/[^\d]/', '', (string)$value);
        return $clean === '' ? 0.0 : (float)$clean;
    }

    /**
     * Giải quyết link rút gọn / chuyển tiếp của Lazada (s.lazada.vn, c.lazada.*) để lấy URL gốc chứa productId.
     * Gửi request không tự chuyển hướng để lấy header Location, tránh bị chặn IP.
     *
     * @param string $url
     * @return string
     */
    private function resolveShortLink(string $url): string
    {
        // Chỉ phân giải các tên miền rút gọn / chia sẻ của Lazada
        if (!str_contains($url, 's.lazada') && !str_contains($url, 'c.lazada') && !str_contains($url, 'lzd.co')) {
            return $url;
        }

        try {
            Log::info("LazadaCashbackService: Đang phân giải link rút gọn: " . $url);

            // Dùng User-Agent điện thoại vì link chia sẻ từ app Lazada thường chỉ trả trang web dành cho mobile.
            // Cho phép Guzzle tự theo các redirect HTTP (3xx) tới tối đa 5 chặng.
            $response = Http::withoutVerifying()
                ->timeout(12)
                ->withOptions([
                    'allow_redirects' => ['max' => 5]
                ])
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 16_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.0 Mobile/15E148 Safari/604.1'
                ])
                ->get($url);

            // 1) Nếu đã được redirect HTTP thẳng tới URL sản phẩm thật (có chứa mẫu -i{id})
            $effective = (string)$response->effectiveUri();
            if (!empty($effective) && preg_match('/-i\d+/', $effective)) {
                Log::info("LazadaCashbackService: Đã giải quyết (HTTP redirect) {$url} thành {$effective}");
                return $effective;
            }

            // 2) Trang chia sẻ từ app Lazada trả HTTP 200 kèm HTML chứa link sản phẩm thật
            //    (qua thẻ meta refresh và/hoặc window.location.href). Bóc tách URL sản phẩm từ body.
            $body = (string)$response->body();
            if ($body !== '') {
                $realUrl = $this->extractLazadaProductUrlFromHtml($body);
                if (!empty($realUrl)) {
                    Log::info("LazadaCashbackService: Đã giải quyết (HTML) {$url} thành {$realUrl}");
                    return $realUrl;
                }
            }
        } catch (\Throwable $e) {
            Log::warning("LazadaCashbackService: Lỗi khi giải quyết link rút gọn {$url}: " . $e->getMessage());
        }

        return $url;
    }

    /**
     * Bóc tách URL trang sản phẩm Lazada thật từ nội dung HTML của trang chia sẻ (interstitial).
     * Trang chia sẻ từ app Lazada không redirect bằng HTTP mà chuyển hướng bằng meta refresh / JS,
     * nên phải tìm trực tiếp URL sản phẩm (chứa mẫu -i{id}) trong body.
     *
     * @param string $html
     * @return string|null
     */
    private function extractLazadaProductUrlFromHtml(string $html): ?string
    {
        // Ưu tiên URL sản phẩm đầy đủ trỏ tới trang chi tiết (/products/...-i{id}...)
        if (preg_match('#https?://[^"\'\s\\\\<>]*lazada\.[a-z.]+/products/[^"\'\s\\\\<>]*-i\d+[^"\'\s\\\\<>]*#i', $html, $m)) {
            return html_entity_decode($m[0]);
        }

        // Fallback: lấy tham số url=... trong thẻ meta refresh nếu có chứa mẫu -i{id}
        if (preg_match('#url=([^"\'\s>]+-i\d+[^"\'\s>]*)#i', $html, $m)) {
            return html_entity_decode($m[1]);
        }

        return null;
    }

    /**
     * Cào meta sản phẩm (og:image + og:title) từ trang chi tiết Lazada.
     * Do API tạo link của Lazada không trả về ảnh và tên thường là tiếng Anh, ta lấy thẻ meta
     * Open Graph của trang PDP. Chỉ nhận URL sản phẩm hợp lệ của Lazada để hạn chế rủi ro SSRF.
     *
     * @param string $productUrl URL trang chi tiết sản phẩm Lazada (đã được resolve)
     * @return array{image: ?string, title: ?string}
     */
    private function fetchProductMetaFromPage(string $productUrl): array
    {
        $result = ['image' => null, 'title' => null];

        // Chỉ cào từ tên miền Lazada hợp lệ và URL có mẫu -i{id}
        if (!preg_match('#https?://[^/]*lazada\.[a-z.]+/#i', $productUrl) || !preg_match('/-i\d+/', $productUrl)) {
            return $result;
        }

        try {
            $response = Http::withoutVerifying()
                ->timeout(10)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                    'Accept' => 'text/html,application/xhtml+xml'
                ])
                ->get($productUrl);

            if (!$response->successful()) {
                return $result;
            }

            $body = (string)$response->body();

            // 1. Ảnh: thẻ meta og:image (chấp nhận thứ tự content/property đảo nhau)
            if (
                preg_match('#<meta[^>]+property=["\']og:image["\'][^>]+content=["\']([^"\']+)["\']#i', $body, $m)
                || preg_match('#<meta[^>]+content=["\']([^"\']+)["\'][^>]+property=["\']og:image["\']#i', $body, $m)
            ) {
                $img = html_entity_decode(trim($m[1]));
                // Một số ảnh Lazada gắn hậu tố .webp phía sau .jpg → chuẩn hoá về ảnh gốc cho tương thích rộng
                $img = preg_replace('/(\.(?:jpg|jpeg|png))_\.webp$/i', '$1', $img);
                $result['image'] = $img ?: null;
            }

            // 2. Tên tiếng Việt: thẻ meta og:title, cắt bỏ hậu tố "| Lazada Việt Nam" / "| Daraz..."
            if (
                preg_match('#<meta[^>]+property=["\']og:title["\'][^>]+content=["\']([^"\']+)["\']#i', $body, $m)
                || preg_match('#<meta[^>]+content=["\']([^"\']+)["\'][^>]+property=["\']og:title["\']#i', $body, $m)
            ) {
                $title = html_entity_decode(trim($m[1]));
                // Bỏ phần thương hiệu sàn phía sau dấu gạch đứng cuối cùng (ví dụ: "... | Lazada Việt Nam")
                $title = preg_replace('/\s*\|\s*(Lazada|Daraz)[^|]*$/iu', '', $title);
                $title = trim($title);
                $result['title'] = $title !== '' ? $title : null;
            }
        } catch (\Throwable $e) {
            Log::warning("LazadaCashbackService: Lỗi cào meta sản phẩm từ {$productUrl}: " . $e->getMessage());
        }

        return $result;
    }

    /**
     * Trích xuất productId (item id) từ URL sản phẩm Lazada.
     * Lazada nhúng id sản phẩm theo mẫu "-i{productId}" (ví dụ: ...-i1234567890-s9876543210.html)
     * hoặc qua tham số truy vấn ?itemId= / ?productId=.
     *
     * @param string $url
     * @return string|null
     */
    private function extractProductId(string $url): ?string
    {
        // Ưu tiên mẫu -i{id} trong đường dẫn (định dạng phổ biến nhất của Lazada)
        if (preg_match('/-i(\d+)/', $url, $m)) {
            return $m[1];
        }

        // Fallback: tham số truy vấn itemId / productId
        $query = parse_url($url, PHP_URL_QUERY);
        if (!empty($query)) {
            parse_str($query, $params);
            foreach (['itemId', 'productId', 'item_id', 'product_id'] as $key) {
                if (!empty($params[$key]) && ctype_digit((string)$params[$key])) {
                    return (string)$params[$key];
                }
            }
        }

        return null;
    }

    /**
     * Nối tham số sub-id (mã đối soát trans_id) vào tracking link Lazada.
     *
     * @param string $trackingLink
     * @param string $subIdParam Tên tham số sub-id (mặc định sub_id1)
     * @param string $subId Giá trị mã đối soát
     * @return string
     */
    private function appendSubId(string $trackingLink, string $subIdParam, string $subId): string
    {
        $separator = str_contains($trackingLink, '?') ? '&' : '?';
        return $trackingLink . $separator . $subIdParam . '=' . urlencode($subId);
    }

    /**
     * Chuẩn hoá tỉ lệ hoa hồng trả về từ Lazada (có thể là "5", "5%", "5.5").
     *
     * @param mixed $rate
     * @return float
     */
    private function parseRate($rate): float
    {
        if (is_numeric($rate)) {
            return (float)$rate;
        }
        $clean = trim(str_replace('%', '', (string)$rate));
        return is_numeric($clean) ? (float)$clean : 0.0;
    }

    /**
     * Sinh mã rút gọn ngắn duy nhất cho ShortLink.
     *
     * @return string
     */
    private function generateUniqueShortCode(): string
    {
        $length = (int)Setting::getVal('shortlink_length', 8);
        if ($length < 3 || $length > 32) {
            $length = 8;
        }

        do {
            $code = Str::random($length);
        } while (ShortLink::where('code', $code)->exists());

        return $code;
    }
}
