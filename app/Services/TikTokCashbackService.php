<?php

namespace App\Services;

use App\Repositories\Contracts\ProductRepositoryInterface;
use App\Models\Setting;
use App\Models\ShortLink;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TikTokCashbackService
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
     * Xử lý link sản phẩm TikTok Shop và lấy thông tin hoàn tiền qua RioHub API.
     *
     * @param string $url URL sản phẩm TikTok Shop
     * @param int|null $userId ID người dùng
     * @param string|null $transId Mã giao dịch đối soát
     * @return array
     */
    public function getProductData(string $url, ?int $userId = null, ?string $transId = null): array
    {
        // 1. Chuẩn hoá URL đầu vào
        $url = trim($url);

        // 2. Đọc cấu hình kết nối API của RioHub từ Cấu hình Setting hoặc file môi trường (.env)
        $apiUrl = trim(Setting::getVal('apitiktok_url')) ?: 'https://riohub.vn/api/v1';
        $apiKey = Setting::getVal('apitiktok_key') ?: config('services.riohub.api_key');
        $creatorUsername = Setting::getVal('tiktok_creator_username');

        // Business Rule: Bắt buộc phải có API Key và Creator Username để xác thực với RioHub
        if (empty($apiKey)) {
            Log::error("TikTok Shop integration error: apitiktok_key / RIOHUB_API_KEY is not configured");
            return [
                'status' => 'error',
                'message' => __('Hệ thống chưa được cấu hình API Key cho TikTok Shop. Vui lòng liên hệ quản trị viên.')
            ];
        }

        if (empty($creatorUsername)) {
            Log::error("TikTok Shop integration error: tiktok_creator_username is not configured in settings");
            return [
                'status' => 'error',
                'message' => __('Hệ thống chưa được cấu hình Creator Username cho TikTok Shop. Vui lòng liên hệ quản trị viên.')
            ];
        }

        // 3. Sử dụng trans_id làm sub_id để đối soát đơn hàng sau này
        // Nếu không có trans_id (chạy demo hoặc khách vãng lai), sinh mã định danh tạm thời
        $subId = $transId ?: ($userId ? 'member_' . $userId : 'guest_' . time());

        // 4. Gọi RioHub API để tạo link affiliate TikTok Shop
        try {
            // Ràng buộc bảo mật (Anti-SSRF): Xác thực URL API TikTok không trỏ tới dải IP Private/Loopback nội bộ
            if (!\App\Helpers\SecurityHelper::validateSsfUrl($apiUrl)) {
                Log::error("TikTok Shop API URL is blocked by SSRF protector: " . $apiUrl);
                return [
                    'status' => 'error',
                    'message' => __('Cấu hình kết nối hệ thống đối tác TikTok Shop không hợp lệ hoặc không an toàn.')
                ];
            }

            Log::info("TikTok Shop API - Creating link for: " . $url . " with sub_id: " . $subId);

            // Lấy tên miền (domain) làm channel gửi lên RioHub để đảm bảo ngắn gọn và hợp lệ
            $host = request()->getHost();
            $channel = preg_replace('/[^A-Za-z0-9_-]/', '', Str::slug($host));
            if (empty($channel) || strlen($channel) > 64) {
                $channel = 'web';
            }

            $response = Http::withoutVerifying()
                ->timeout(15)
                ->withHeaders([
                    'X-Riohub-Api-Key' => $apiKey,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json'
                ])
                ->post(rtrim($apiUrl, '/') . '/partner/tiktok/affiliate/links', [
                    'creator_username' => $creatorUsername,
                    'product_url' => $url,
                    'sub_id' => $subId,
                    'type' => 'PRODUCT',
                    'channel' => $channel
                ]);

            if (!$response->successful()) {
                Log::error("TikTok Shop API - Create Link failed: " . $response->status() . " - " . $response->body());
                $errorData = $response->json();

                // Trả về thông báo lỗi trực quan từ RioHub nếu sản phẩm không được duyệt hoa hồng
                if (isset($errorData['error']['code']) && $errorData['error']['code'] === 'product_not_promotable') {
                    return [
                        'status' => 'error',
                        'message' => __('Sản phẩm này không hỗ trợ hoa hồng affiliate hoặc chưa được Shop phê duyệt chương trình tiếp thị liên kết.')
                    ];
                }

                // Trả về message lỗi chi tiết từ hệ thống đối tác nếu có (ví dụ lỗi 502 từ TikTok Shop API)
                if (isset($errorData['error']['message'])) {
                    return [
                        'status' => 'error',
                        'message' => __('Lỗi từ đối tác: :message', ['message' => $errorData['error']['message']])
                    ];
                }

                return [
                    'status' => 'error',
                    'message' => __('Không thể tạo link hoàn tiền TikTok Shop. Vui lòng thử lại sau.')
                ];
            }

            $linkData = $response->json();
            $productId = $linkData['product_id'] ?? null;
            $affiliateUrl = $linkData['affiliate_link'] ?? null;

            if (!$productId) {
                Log::error("TikTok Shop API - No product_id returned in link creation");
                return [
                    'status' => 'error',
                    'message' => __('Không thể phân tích ID sản phẩm từ liên kết này.')
                ];
            }

            // 5. Kiểm tra Cache sản phẩm cục bộ trước khi gọi API thông tin sản phẩm
            // Sử dụng định dạng 'tiktok_' làm tiền tố để phân biệt với Shopee trong bảng products
            $tiktokDbId = 'tiktok_' . $productId;
            $cachedProduct = $this->productRepository->findByShopeeId($tiktokDbId);
            $productData = null;
            $source = 'api';

            // Nếu sản phẩm đã có trong cache và chưa quá 24 giờ (theo cấu hình cache_clean_estimated_hours_tiktok)
            // Ràng buộc bổ sung: Chỉ dùng lại bản ghi cache có đầy đủ giá bán, các bản ghi cũ thiếu giá sẽ bị bỏ qua
            // để hệ thống gọi lại API lấy thông tin sản phẩm đầy đủ.
            $cacheHours = (int)Setting::getVal('cache_clean_estimated_hours_tiktok', 24);
            if ($cachedProduct && (float)$cachedProduct->price > 0 && $cachedProduct->updated_at->addHours($cacheHours)->isFuture()) {
                $this->productRepository->incrementSearchCount($cachedProduct);

                // Tính toán lại Cashback thực tế dựa trên hoa hồng gốc
                $productData = $this->calculateCashbackData($cachedProduct, $affiliateUrl);
                $source = 'cache';
            } else {
                // 6. Gọi RioHub API lấy thông tin chi tiết sản phẩm TikTok Shop
                Log::info("TikTok Shop API - Fetching product info for: " . $productId);

                $productResponse = Http::withoutVerifying()
                    ->timeout(15)
                    ->withHeaders([
                        'X-Riohub-Api-Key' => $apiKey,
                        'Accept' => 'application/json'
                    ])
                    ->get(rtrim($apiUrl, '/') . '/partner/tiktok/affiliate/products', [
                        'creator_username' => $creatorUsername,
                        'product_id' => $productId
                    ]);

                if ($productResponse->successful()) {
                    $productJson = $productResponse->json();
                    $productsList = $productJson['products'] ?? [];

                    if (!empty($productsList)) {
                        $rawProduct = $productsList[0];

                        // Parse và làm sạch thông tin sản phẩm
                        $name = $rawProduct['title'] ?? 'Sản phẩm TikTok Shop';
                        $image = $rawProduct['main_image_url'] ?? null;

                        // Giá gốc (Lấy minimum_amount từ original_price)
                        $originalPrice = (float)($rawProduct['original_price']['minimum_amount'] ?? 0);
                        if ($originalPrice <= 0) {
                            $originalPrice = (float)($rawProduct['sales_price']['minimum_amount'] ?? 0);
                        }

                        // Tính toán hoa hồng gốc của hệ thống từ API
                        // RioHub trả về commission.amount dạng khoảng, ví dụ "3299.94 - 5099.94" hoặc số đơn lẻ
                        $commissionAmount = $this->parseCommissionAmount($rawProduct['commission']['amount'] ?? '0');

                        // Tính toán cashback cho người dùng
                        $cashbackData = $this->calculateRates($originalPrice, $commissionAmount);

                        // Lưu thông tin sản phẩm vào DB cache cục bộ
                        // Lưu ý: Repository sẽ tự động bỏ qua không lưu nếu sản phẩm thiếu thông tin (không có giá bán)
                        $preparedDbData = [
                            'shopee_id' => $tiktokDbId, // Tái sử dụng cột shopee_id để làm khóa duy nhất
                            'name' => Str::limit($name, 250, '...'),
                            'image' => $image,
                            // Chuẩn hoá về số nguyên đồng để bảng cache sản phẩm không lưu phần thập phân vô nghĩa
                            'price' => \App\Helpers\MoneyHelper::round($originalPrice),
                            'commission_amount' => \App\Helpers\MoneyHelper::round($commissionAmount),
                            'shopee_commission' => \App\Helpers\MoneyHelper::round($commissionAmount), // Lưu đồng bộ cột hoa hồng gốc
                            'cashback_amount' => $cashbackData['cashback_amount'],
                            'cashback_rate' => $cashbackData['cashback_rate'],
                            'affiliate_url' => $affiliateUrl
                        ];

                        $this->productRepository->createOrUpdate($preparedDbData);

                        $productData = [
                            'shopee_id' => $tiktokDbId,
                            'name' => $name,
                            'image' => $image,
                            'price' => $originalPrice,
                            'commission_amount' => $commissionAmount,
                            'cashback_amount' => \App\Helpers\MoneyHelper::round($cashbackData['cashback_amount']),
                            'cashback_rate' => round($cashbackData['cashback_rate'], 2),
                            'affiliate_url' => $affiliateUrl,
                            'shop_name' => $rawProduct['shop']['name'] ?? null,
                            'is_estimated' => $originalPrice <= 0
                        ];
                        $source = 'api';
                    }
                }
            }

            // Fallback nếu không gọi được thông tin chi tiết sản phẩm, vẫn trả về dữ liệu link để user mua hàng
            if (!$productData) {
                Log::warning("TikTok Shop API - Could not fetch product details. Using fallback data.");
                $productData = [
                    'shopee_id' => $tiktokDbId,
                    'name' => __('Sản phẩm TikTok Shop'),
                    'image' => 'https://picsum.photos/400/400?random=99',
                    'price' => 0.0,
                    'commission_amount' => 0.0,
                    'cashback_amount' => 0.0,
                    'cashback_rate' => 0.0,
                    'affiliate_url' => $affiliateUrl,
                    'is_estimated' => true
                ];
                $source = 'fallback_no_details';
            }

            // 7. Tạo liên kết rút gọn an toàn (Short Link) nếu cấu hình hệ thống bật
            $isShortLinkEnabled = Setting::getVal('shortlink_status', '0') === '1';
            if ($isShortLinkEnabled && !empty($productData['affiliate_url'])) {
                // Tự động sinh mã rút gọn duy nhất và lưu vào cơ sở dữ liệu để ẩn link gốc chuyên nghiệp, phục vụ tracking click
                // Bổ sung thêm product_name và product_image phục vụ hiển thị Open Graph khi share lên MXH
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
            Log::error("TikTok Shop API error: " . $e->getMessage() . "\n" . $e->getTraceAsString());
            return [
                'status' => 'error',
                'message' => __('Đã xảy ra lỗi trong quá trình kết nối với hệ thống đối tác TikTok Shop.')
            ];
        }
    }

    /**
     * Tính toán Cashback dựa trên cấu hình hệ thống.
     *
     * @param float $price
     * @param float $commission
     * @return array
     */
    private function calculateRates(float $price, float $commission): array
    {
        $cashbackSystemRate = (float)Setting::getVal('tiktok_fake_cashback_rate', 50);

        if ($price <= 0) {
            return [
                'cashback_amount' => 0.0,
                'cashback_rate' => 0.0
            ];
        }

        // Mặc định hoàn trả theo tỷ lệ hoa hồng được thiết lập
        // Chuẩn hoá về số nguyên đồng vì đồng Việt Nam không có đơn vị nhỏ hơn 1 đồng
        $cashbackAmount = \App\Helpers\MoneyHelper::round($commission * ($cashbackSystemRate / 100));

        // Chặn trên giá trị tỷ lệ để không vượt ngưỡng lưu trữ của cột decimal(5,2)
        $cashbackRate = \App\Helpers\CashbackHelper::clampRate(($cashbackAmount / $price) * 100);

        return [
            'cashback_amount' => $cashbackAmount,
            'cashback_rate' => round($cashbackRate, 2)
        ];
    }

    /**
     * Tính toán dữ liệu hoàn tiền cho sản phẩm lưu trong Cache.
     *
     * @param \App\Models\Product $cachedProduct
     * @param string $affiliateUrl
     * @return array
     */
    private function calculateCashbackData($cachedProduct, string $affiliateUrl): array
    {
        $price = (float)$cachedProduct->price;
        $commissionAmount = (float)$cachedProduct->commission_amount;

        $rates = $this->calculateRates($price, $commissionAmount);

        return [
            'shopee_id' => $cachedProduct->shopee_id,
            'name' => $cachedProduct->name,
            'image' => $cachedProduct->image,
            'price' => $price,
            'commission_amount' => $commissionAmount,
            'cashback_amount' => $rates['cashback_amount'],
            'cashback_rate' => $rates['cashback_rate'],
            'affiliate_url' => $affiliateUrl,
            'is_estimated' => $price <= 0
        ];
    }

    /**
     * Trích xuất hoa hồng từ chuỗi khoảng giá trị trả về của RioHub (ví dụ: "3299.94 - 5099.94").
     * Ưu tiên lấy giá trị trung bình để hiển thị ước tính chính xác nhất cho khách hàng.
     *
     * @param string $amountStr
     * @return float
     */
    private function parseCommissionAmount(string $amountStr): float
    {
        $amountStr = trim($amountStr);
        if (str_contains($amountStr, '-')) {
            $parts = explode('-', $amountStr);
            $min = (float)trim($parts[0]);
            $max = (float)trim($parts[1]);
            return ($min + $max) / 2;
        }
        return (float)$amountStr;
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
