<?php

namespace App\Services;

use App\Repositories\Contracts\ProductRepositoryInterface;
use App\Models\Setting;
use App\Models\ShortLink;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ShopeeCashbackService
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
     * Xử lý link sản phẩm Shopee và lấy thông tin hoàn tiền.
     *
     * @param string $url URL sản phẩm Shopee
     * @param int|null $userId ID người dùng
     * @param string|null $transId Mã giao dịch đối soát
     * @return array
     */
    public function getProductData(string $url, ?int $userId = null, ?string $transId = null): array
    {
        // 1. Chuẩn hoá URL
        $url = trim($url);

        // Giải quyết link rút gọn (shope.ee, s.shopee.vn, shp.ee) để lấy link gốc sản phẩm chính thức
        // giúp hệ thống đối soát chính xác mã sản phẩm cache và gọi API hoặc Crawler đáng tin cậy hơn.
        $url = $this->resolveShortLink($url);

        // Chặn sớm các đường dẫn KHÔNG PHẢI sản phẩm (video ngắn, livestream) để báo lỗi rõ ràng cho người dùng,
        // tránh việc hệ thống vẫn gọi API/cào HTML rồi trả về thông báo lỗi chung chung khó hiểu.
        $nonProductMessage = $this->detectNonProductLink($url);
        if ($nonProductMessage) {
            Log::info("ShopeeCashbackService: Từ chối xử lý link không phải sản phẩm: {$url}");
            return [
                'status' => 'error',
                'message' => $nonProductMessage
            ];
        }

        // 2. Trích xuất Shopee ID tạm thời để kiểm tra cache (nếu có trong URL)
        $shopeeId = $this->extractShopeeId($url);

        $productData = null;
        $source = 'api';

        if ($shopeeId) {
            $cachedProduct = $this->productRepository->findByShopeeId($shopeeId);

            // Quy tắc nghiệp vụ: Chỉ tái sử dụng bản ghi cache có đầy đủ thông tin (giá bán > 0 và có link affiliate).
            // Các bản ghi cũ bị thiếu giá bán (tồn dư trước đây) sẽ bị bỏ qua để hệ thống gọi lại API lấy dữ liệu đầy đủ.
            if ($cachedProduct && (float)$cachedProduct->price > 0 && !empty($cachedProduct->affiliate_url)) {
                // Tăng số lượt tra cứu
                $this->productRepository->incrementSearchCount($cachedProduct);

                // Tính toán lại cashback theo cấu hình hiện tại để tránh hiển thị sai lệch khi admin thay đổi config
                $price = (float)$cachedProduct->price;
                $commissionAmount = (float)$cachedProduct->commission_amount;

                $cashbackSystemRate = (float)Setting::getVal('shopee_fake_cashback_rate', 50);

                // Chuẩn hoá về số nguyên đồng vì đồng Việt Nam không có đơn vị nhỏ hơn 1 đồng
                $cashbackAmount = \App\Helpers\MoneyHelper::round($commissionAmount * ($cashbackSystemRate / 100));
                // Chặn trên giá trị tỷ lệ để không vượt ngưỡng lưu trữ của cột decimal(5,2)
                $cashbackRate = \App\Helpers\CashbackHelper::clampRate(($cashbackAmount / $price) * 100);

                $productData = [
                    'shopee_id' => $cachedProduct->shopee_id,
                    'name' => $cachedProduct->name,
                    'image' => $cachedProduct->image,
                    'price' => $price,
                    'commission_amount' => $commissionAmount,
                    'cashback_amount' => \App\Helpers\MoneyHelper::round($cashbackAmount),
                    'cashback_rate' => round($cashbackRate, 2),
                    'affiliate_url' => $cachedProduct->affiliate_url,
                    'is_estimated' => false
                ];
                $source = 'cache';
            }
        }

        // 3. Nếu chưa có dữ liệu trong cache cục bộ, thực hiện gọi API ngoài từ hệ thống APISHOPEE (chỉ gọi nếu API được bật hoạt động)
        if (!$productData && Setting::getVal('apishopee_status', '0') === '1') {
            // Lấy URL endpoint cấu hình API Shopee từ Admin Panel, mặc định sẽ trỏ đến apishopee.cmsnt.co
            $apiUrl = Setting::getVal('apishopee_url', 'https://apishopee.cmsnt.co/api/v1/shopee/product');

            // Ràng buộc bảo mật (Anti-SSRF): Đảm bảo URL gọi đi không trỏ tới mạng nội bộ hoặc IP Private
            if (\App\Helpers\SecurityHelper::validateSsfUrl($apiUrl)) {
                // Lấy mã API Key từ cấu hình admin đã lưu để xác thực quyền truy cập
                $apiKey = Setting::getVal('apishopee_key');

                try {
                    Log::info("Shopee API request URL: " . $url);
                    // Gửi request HTTP GET với timeout 10 giây, đính kèm Header X-API-KEY và tắt SSL verify để tránh lỗi chứng chỉ môi trường local
                    $response = Http::withoutVerifying()->timeout(10)->withHeaders([
                        'X-API-KEY' => $apiKey
                    ])->get($apiUrl, [
                        'product_link' => $url
                    ]);

                    Log::info("Shopee API response status: " . $response->status());

                    if ($response->successful()) {
                        $apiData = $response->json();
                        Log::info("Shopee API response body: " . json_encode($apiData, JSON_UNESCAPED_UNICODE));

                        // Kiểm tra cấu trúc dữ liệu trả về từ APISHOPEE (phải chứa productName và price)
                        if (isset($apiData['productName']) && isset($apiData['price'])) {
                            // Xử lý dữ liệu sản phẩm, tính toán cashback và lưu vào DB cache
                            $productData = $this->processAndSaveApiData($apiData, $shopeeId, $url);
                            $source = 'api';
                        }
                    }

                    if (!$productData) {
                        Log::warning("Shopee API return unexpected format or error: " . $response->body());
                    }
                } catch (\Exception $e) {
                    Log::error("Failed to connect Shopee API: " . $e->getMessage() . "\n" . $e->getTraceAsString());
                }
            } else {
                Log::error("Shopee API URL is blocked by SSRF protector: " . $apiUrl);
            }
        }

        // 4. Cơ chế Fallback: Cào HTML Shopee giả lập Facebook Bot User-Agent để lấy tên và ảnh sản phẩm thực tế (ước tính tỉ lệ hoàn tiền)
        if (!$productData) {
            $scrapedData = $this->scrapeShopeePageAsFacebookBot($url, $shopeeId);
            if ($scrapedData) {
                $productData = $scrapedData;
                $source = 'facebook_bot_fallback';
            }
        }

        // Kiểm tra an toàn để ngăn chặn lỗi Fatal Error khi không lấy được dữ liệu sản phẩm Shopee
        // từ cả hệ thống API APISHOPEE lẫn cơ chế cào HTML fallback.
        if (!$productData) {
            return [
                'status' => 'error',
                'message' => __('Không thể lấy thông tin sản phẩm Shopee.')
            ];
        }

        // 5. Tự động chuyển đổi sang link affiliate rút gọn cá nhân hoá cho từng User
        $subId = $transId ?: ($userId ? 'member_' . $userId : 'guest_' . time());
        $originLink = $productData['affiliate_url'] ?? $url;

        // Kiểm tra xem cấu hình API chuyển đổi link là gì
        $linkConverter = Setting::getVal('shopee_link_converter', 'shopee_origin');

        if ($linkConverter === 'shp.today' || $linkConverter === 'shptoday') {
            // Sử dụng dịch vụ shp.today để chuyển đổi link
            $shpTodayUrl = $this->shortenViaShpToday($originLink, $subId, $userId);
            if ($shpTodayUrl) {
                // Gán trực tiếp link shp.today rút gọn vào affiliate_url
                $productData['affiliate_url'] = $shpTodayUrl;

                return [
                    'status' => 'success',
                    'source' => $source,
                    'data' => $productData
                ];
            }
            // Nếu gọi API shp.today thất bại, hệ thống tự động fallback sử dụng API gốc từ Shopee bên dưới
            Log::warning("ShopeeCashbackService: Gọi shp.today thất bại, tự động chuyển hướng sang API Shopee gốc.");
        }

        // Mặc định: Sử dụng API chuyển đổi link gốc của Shopee
        $affiliateId = Setting::getVal('shopee_app_id', 'shopee_demo_id_123');
        $encodedOrigin = urlencode($originLink);
        $personalAffUrl = "https://s.shopee.vn/an_redir?origin_link={$encodedOrigin}&affiliate_id={$affiliateId}&sub_id={$subId}";

        // Kiểm tra cấu hình xem tính năng rút gọn link nội bộ có được bật hay không
        $isShortLinkEnabled = Setting::getVal('shortlink_status', '0') === '1';

        if ($isShortLinkEnabled) {
            // Tự động sinh mã rút gọn duy nhất và lưu vào cơ sở dữ liệu để ẩn link gốc chuyên nghiệp, phục vụ tracking click
            // Bổ sung thêm product_name và product_image phục vụ hiển thị Open Graph khi share lên MXH
            $shortCode = $this->generateUniqueShortCode();
            ShortLink::create([
                'code' => $shortCode,
                'destination_url' => $personalAffUrl,
                'product_name' => $productData['name'] ?? null,
                'product_image' => $productData['image'] ?? null,
                'user_id' => $userId,
                'clicks' => 0
            ]);

            // Lấy tên miền rút gọn riêng được cấu hình trong settings (Custom Domain)
            $shortDomain = Setting::getVal('shortlink_domain');
            if (!empty($shortDomain)) {
                $shortDomain = rtrim($shortDomain, '/');
                // Bổ sung giao thức https:// nếu Admin quên không nhập protocol
                if (!str_starts_with($shortDomain, 'http://') && !str_starts_with($shortDomain, 'https://')) {
                    $shortDomain = 'https://' . $shortDomain;
                }
                $productData['affiliate_url'] = $shortDomain . '/' . $shortCode;
            } else {
                // Nếu không cấu hình tên miền riêng, hệ thống tự động sử dụng tên miền hiện tại của website
                $productData['affiliate_url'] = url('/' . $shortCode);
            }
        } else {
            // Nếu tắt tính năng rút gọn, trả về link affiliate trực tiếp của Shopee
            $productData['affiliate_url'] = $personalAffUrl;
        }

        return [
            'status' => 'success',
            'source' => $source,
            'data' => $productData
        ];
    }

    /**
     * Nhận diện các đường dẫn Shopee KHÔNG PHẢI là link sản phẩm (video ngắn Shopee Video, livestream).
     *
     * Người dùng rất hay bấm "Chia sẻ" ngay trên video hoặc phiên phát trực tiếp, khi đó link rút gọn
     * (vd: https://vn.shp.ee/xxxx) sẽ phân giải về dạng https://sv.shopee.vn/share-video/... chứ không
     * chứa mã sản phẩm nào. Hàm này trả về thông báo lỗi tương ứng để hướng dẫn người dùng lấy đúng link.
     *
     * @param string $url URL đã được phân giải khỏi dạng rút gọn
     * @return string|null Thông báo lỗi nếu là link không phải sản phẩm, null nếu hợp lệ
     */
    private function detectNonProductLink(string $url): ?string
    {
        // Nếu URL vẫn chứa mã sản phẩm hợp lệ (i.shopId.itemId hoặc /product/shopId/itemId) thì chắc chắn
        // đây là link sản phẩm, không cần kiểm tra tiếp (tránh nhận diện nhầm sản phẩm có chữ "video" trong tên).
        if (preg_match('/i\.\d+\.\d+/', $url) || preg_match('/product\/\d+\/\d+/', $url)) {
            return null;
        }

        $parts = parse_url($url);
        $host  = strtolower($parts['host'] ?? '');
        $path  = strtolower($parts['path'] ?? '');

        // Giải mã toàn bộ URL để bắt được cả trường hợp link thật bị bọc bên trong tham số của link
        // trung gian mà hệ thống chưa bóc tách được, ví dụ:
        // https://shopee.vn/universal-link?redir=https%3A%2F%2Fsv.shopee.vn%2Fshare-video%2F...%26share_obj%3Dvideo
        $decoded = strtolower(urldecode($url));

        // 1. Link video ngắn của Shopee Video (sv.shopee.vn/share-video/..., shopee.vn/video/...)
        if (str_starts_with($host, 'sv.shopee.')
            || str_contains($decoded, '//sv.shopee.')
            || str_contains($decoded, '/share-video')
            || str_contains($decoded, '/universal-link/video')
            || preg_match('#^/video(/|$)#', $path)
            || str_contains($decoded, 'share_obj=video')
        ) {
            return __('Đường dẫn bạn vừa dán là link VIDEO của Shopee, không phải link sản phẩm. Vui lòng mở video, bấm vào sản phẩm được gắn kèm trong video rồi chọn "Chia sẻ" để lấy đúng link sản phẩm.');
        }

        // 2. Link phiên phát trực tiếp (Shopee Live)
        if (str_starts_with($host, 'live.shopee.')
            || str_contains($decoded, '//live.shopee.')
            || str_contains($decoded, '/livestreaming')
            || preg_match('#^/live(/|$)#', $path)
            || str_contains($decoded, 'share_obj=live')
        ) {
            return __('Đường dẫn bạn vừa dán là link PHÁT TRỰC TIẾP (Livestream) của Shopee, không phải link sản phẩm. Vui lòng chọn sản phẩm trong giỏ hàng của phiên live rồi lấy link chia sẻ của sản phẩm đó.');
        }

        return null;
    }

    /**
     * Trích xuất Shopee ID từ đường dẫn sản phẩm.
     *
     * @param string $url
     * @return string|null
     */
    private function extractShopeeId(string $url): ?string
    {
        // Phân tích biểu thức chính quy để tìm ID sản phẩm Shopee (thường là cụm số sau ký tự i.)
        // Ví dụ: https://shopee.vn/product/12345/67890 hoặc https://shopee.vn/Ten-San-Pham-i.12345.67890
        if (preg_match('/i\.(\d+)\.(\d+)/', $url, $matches)) {
            return $matches[1] . '_' . $matches[2];
        }
        if (preg_match('/product\/(\d+)\/(\d+)/', $url, $matches)) {
            return $matches[1] . '_' . $matches[2];
        }
        // Hỗ trợ định dạng URL rút gọn sau khi phân giải: https://shopee.vn/{slug}/{shop_id}/{item_id}
        if (preg_match('/shopee\.[^\/]+\/[^\/]+\/(\d+)\/(\d+)/', $url, $matches)) {
            return $matches[1] . '_' . $matches[2];
        }

        // Trường hợp link rút gọn (shope.ee) thì không trích xuất được trực tiếp ngay, phải dùng hash ngẫu nhiên
        return 'shp_' . md5($url);
    }

    /**
     * Xử lý dữ liệu thô nhận được từ API, tính toán cashback và lưu trữ.
     *
     * @param array $apiData
     * @param string|null $shopeeId
     * @param string $originalUrl
     * @return array
     */
    /**
     * Xử lý dữ liệu thô nhận được từ APISHOPEE, tính toán cashback và lưu trữ.
     *
     * @param array $apiData Dữ liệu thô từ APISHOPEE
     * @param string|null $shopeeId ID Shopee đã trích xuất trước đó
     * @param string $originalUrl Link sản phẩm gốc
     * @return array
     */
    private function processAndSaveApiData(array $apiData, ?string $shopeeId, string $originalUrl): array
    {
        // 1. Xác định link sản phẩm chính thức trả về từ API, nếu không có thì dùng link gốc
        $finalLink = $apiData['productLink'] ?? $originalUrl;

        // 2. Trích xuất ID sản phẩm từ link chính thức để đảm bảo tính đồng bộ, nếu không được thì dùng ID cũ
        $id = $this->extractShopeeId($finalLink) ?: $shopeeId ?: 'shp_' . md5($originalUrl);
        $price = (float)($apiData['price'] ?? 0);

        // 3. Tính toán hoa hồng dựa trên kết quả trả về từ API
        // Nếu API không trả về hoa hồng thì mặc định giả lập 5% giá sản phẩm làm hoa hồng
        $commissionAmount = (float)($apiData['commission'] ?? ($price * 0.05));

        // 4. Lấy tỷ lệ hoàn tiền cashback hệ thống chia cho người mua (mặc định là 70% trên tổng hoa hồng)
        $cashbackSystemRate = (float)Setting::getVal('shopee_fake_cashback_rate', 50);

        // Tính bằng tổng hoa hồng nhân với tỷ lệ phần trăm được chia
        // Chuẩn hoá về số nguyên đồng vì đồng Việt Nam không có đơn vị nhỏ hơn 1 đồng
        $cashbackAmount = \App\Helpers\MoneyHelper::round($commissionAmount * ($cashbackSystemRate / 100));

        // 5. Phần trăm hoàn tiền thực tế so với giá bán của sản phẩm
        // Chặn trên giá trị tỷ lệ để không vượt ngưỡng lưu trữ của cột decimal(5,2)
        $cashbackRate = \App\Helpers\CashbackHelper::clampRate($price > 0 ? ($cashbackAmount / $price) * 100 : 0);

        $preparedData = [
            'shopee_id' => (string)$id,
            'name' => $apiData['productName'] ?? 'Sản phẩm Shopee',
            'image' => $apiData['imageUrl'] ?? 'https://picsum.photos/400/400?random=99',
            // Chuẩn hoá về số nguyên đồng để bảng cache sản phẩm không lưu phần thập phân vô nghĩa
            'price' => \App\Helpers\MoneyHelper::round($price),
            'commission_amount' => \App\Helpers\MoneyHelper::round($commissionAmount),
            'shopee_commission' => \App\Helpers\MoneyHelper::round($commissionAmount), // Sử dụng hoa hồng Shopee trả trực tiếp từ API
            'cashback_amount' => $cashbackAmount,
            'cashback_rate' => round($cashbackRate, 2),
            'affiliate_url' => $finalLink,
            'is_estimated' => false
        ];

        // 6. Lưu thông tin vào database để làm bộ đệm cache giúp tăng tốc cho các lượt tra cứu sau
        $this->productRepository->createOrUpdate($preparedData);

        return $preparedData;
    }



    /**
     * Hỗ trợ parse số tiền từ chuỗi có ký hiệu tiền tệ và dấu chấm phân cách.
     *
     * @param string $val Chuỗi tiền tệ (ví dụ: ₫157.500)
     * @return float Giá trị số thực
     */
    private function parseCurrencyString(string $val): float
    {
        $clean = preg_replace('/[^\d]/', '', $val);
        return (float)$clean;
    }

    /**
     * Tạo mã rút gọn ngẫu nhiên và đảm bảo tính duy nhất trong bảng short_links.
     *
     * @return string
     */
    private function generateUniqueShortCode(): string
    {
        // Lấy độ dài ký tự rút gọn từ cấu hình hệ thống (mặc định là 8, giới hạn an toàn từ 3 đến 32 ký tự)
        $length = (int)Setting::getVal('shortlink_length', 8);
        if ($length < 3 || $length > 32) {
            $length = 8;
        }

        do {
            // Sinh ngẫu nhiên chuỗi có độ dài cấu hình alphanumeric
            $code = Str::random($length);
        } while (ShortLink::where('code', $code)->exists());

        return $code;
    }

    /**
     * Cào thông tin thô của sản phẩm Shopee qua User-Agent Facebook Bot.
     * Giải pháp này tận dụng việc Shopee bắt buộc phải trả về HTML render sẵn chứa các thẻ Open Graph meta
     * (og:title, og:image) cho các mạng xã hội để tạo link preview mà không thể chặn IP.
     * 
     * Do phương pháp này chỉ đọc HTML thô từ phản hồi nên sẽ không có giá và hoa hồng chính xác từ Shopee API,
     * ta sẽ để trống các giá trị tài chính này và hiển thị dạng ước tính tỉ lệ phần trăm cho người dùng.
     *
     * @param string $url URL sản phẩm Shopee gốc
     * @param string $shopeeId ID định danh Shopee của sản phẩm
     * @return array|null Trả về mảng thông tin sản phẩm hoặc null nếu thất bại
     */
    private function scrapeShopeePageAsFacebookBot(string $url, string $shopeeId): ?array
    {
        try {
            Log::info("Bắt đầu cào HTML sản phẩm Shopee qua Facebook Bot cho URL: " . $url);

            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                // Giả lập User-Agent Facebook External Hit mà Shopee bắt buộc phải tin tưởng
                CURLOPT_USERAGENT => 'facebookexternalhit/1.1 (+http://www.facebook.com/externalhit_uatext.php)',
                CURLOPT_TIMEOUT => 15,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_ENCODING => 'gzip, deflate',
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_HTTPHEADER => [
                    'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                    'Accept-Language: vi-VN,vi;q=0.9,en-US;q=0.8,en;q=0.7',
                ],
            ]);

            $html = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            if ($curlError || $httpCode !== 200 || !$html) {
                Log::warning("Cào HTML Shopee qua Facebook Bot thất bại. Mã lỗi HTTP: {$httpCode}. Lỗi Curl: {$curlError}");
                return null;
            }

            // 1. Trích xuất tên sản phẩm từ thẻ og:title hoặc thẻ <title>
            $productName = null;
            if (
                preg_match('/property="og:title"[^>]*content="([^"]+)"/', $html, $m) ||
                preg_match('/content="([^"]+)"[^>]*property="og:title"/', $html, $m)
            ) {
                $productName = preg_replace('/\s*\|\s*Shopee\s+Việt\s+Nam$/u', '', htmlspecialchars_decode($m[1]));
            } elseif (preg_match('/<title[^>]*>([^<]+)<\/title>/', $html, $m)) {
                $productName = preg_replace('/\s*\|\s*Shopee\s+Việt\s+Nam$/u', '', htmlspecialchars_decode(trim($m[1])));
            }

            if (!$productName) {
                Log::warning("Không tìm thấy tên sản phẩm trong HTML của URL: {$url}");
                return null;
            }

            // 2. Trích xuất ảnh sản phẩm từ thẻ og:image
            $imageUrl = 'https://picsum.photos/400/400?random=99';
            if (
                preg_match('/property="og:image"[^>]*content="([^"]+)"/', $html, $m) ||
                preg_match('/content="([^"]+)"[^>]*property="og:image"/', $html, $m)
            ) {
                $imageUrl = htmlspecialchars_decode($m[1]);
            }

            // 3. Với cơ chế cào HTML qua Facebook Bot, không xác định được hoa hồng thực tế nên đặt tỉ lệ hoàn bằng 0
            // Nhằm ẩn đi tỷ lệ hoàn ước tính ở frontend theo yêu cầu nghiệp vụ
            $cashbackRate = 0.0;

            $preparedData = [
                'shopee_id' => $shopeeId,
                'name' => $productName,
                'image' => $imageUrl,
                'price' => 0.0, // Giá bằng 0 chỉ thị cho frontend hiển thị chế độ ước tính
                'commission_amount' => 0.0,
                'shopee_commission' => 0.0,
                'cashback_amount' => 0.0,
                'cashback_rate' => round($cashbackRate, 2),
                'affiliate_url' => $url,
                'is_estimated' => true
            ];

            // 4. Không lưu cache dữ liệu cào ước tính này vào bảng products vì đang thiếu thông tin giá bán.
            // Quy tắc nghiệp vụ: Chỉ hiển thị tạm cho người dùng ở lượt tra cứu hiện tại, lượt sau hệ thống
            // sẽ gọi lại API để lấy dữ liệu đầy đủ thay vì đọc lại một bản ghi cache thiếu giá.
            Log::info("Cào HTML Shopee qua Facebook Bot thành công (không lưu cache do thiếu giá bán): {$productName}");
            return $preparedData;
        } catch (\Exception $e) {
            Log::error("Lỗi khi cào dữ liệu qua Facebook Bot: " . $e->getMessage() . "\n" . $e->getTraceAsString());
            return null;
        }
    }

    /**
     * Bóc tách link trung gian universal-link của Shopee để lấy đường dẫn đích thật.
     *
     * Ví dụ: https://shopee.vn/universal-link?deep_and_web=1&redir=https%3A%2F%2Fsv.shopee.vn%2Fshare-video%2F...
     * sẽ được bóc thành https://sv.shopee.vn/share-video/...
     *
     * @param string $url URL cần kiểm tra
     * @return string URL đích thật nếu bóc tách được, ngược lại trả lại nguyên URL ban đầu
     */
    private function unwrapUniversalLink(string $url): string
    {
        // Chỉ xử lý đúng dạng link bọc universal-link, các link khác giữ nguyên
        if (!str_contains(strtolower($url), '/universal-link')) {
            return $url;
        }

        $query = parse_url($url, PHP_URL_QUERY);
        if (empty($query)) {
            return $url;
        }

        parse_str($query, $params);

        // Duyệt lần lượt các tham số chứa link đích mà Shopee đang sử dụng
        foreach (['redir', 'deep_and_deeplink', 'deeplink', 'url'] as $key) {
            $candidate = $params[$key] ?? null;
            if (!is_string($candidate) || $candidate === '') {
                continue;
            }

            // === BẢO MẬT (Chống SSRF): Tham số redir nằm ngay trên link người dùng dán vào nên hoàn toàn
            // có thể bị kẻ xấu chỉnh sửa để trỏ tới máy chủ nội bộ. Vì vậy chỉ chấp nhận link đích thuộc
            // đúng hệ thống tên miền Shopee và vượt qua bộ lọc SSRF chung của hệ thống.
            if ($this->isShopeeUrl($candidate) && \App\Helpers\SecurityHelper::validateSsfUrl($candidate)) {
                return $candidate;
            }

            Log::warning("ShopeeCashbackService: Bỏ qua tham số {$key} của universal-link do không thuộc tên miền Shopee hợp lệ: " . $candidate);
        }

        return $url;
    }

    /**
     * Ghép đường dẫn chuyển hướng tương đối thành URL tuyệt đối dựa trên link hiện tại.
     *
     * @param string $location Giá trị header Location (có thể tuyệt đối hoặc tương đối)
     * @param string $baseUrl URL đang được phân giải ở chặng hiện tại
     * @return string URL tuyệt đối
     */
    private function toAbsoluteUrl(string $location, string $baseUrl): string
    {
        // Đã là URL tuyệt đối thì dùng luôn
        if (preg_match('#^https?://#i', $location)) {
            return $location;
        }

        $base = parse_url($baseUrl);
        if (empty($base['scheme']) || empty($base['host'])) {
            return $location;
        }

        return $base['scheme'] . '://' . $base['host'] . '/' . ltrim($location, '/');
    }

    /**
     * Kiểm tra một URL có thuộc hệ thống tên miền chính thức của Shopee hay không.
     *
     * @param string $url URL cần kiểm tra
     * @return bool
     */
    private function isShopeeUrl(string $url): bool
    {
        // Bỏ qua các giao thức khác http/https (ví dụ deeplink mở app dạng shopeevn://)
        if (!preg_match('#^https?://#i', $url)) {
            return false;
        }

        $host = strtolower((string)parse_url($url, PHP_URL_HOST));
        if ($host === '') {
            return false;
        }

        // Khớp chính xác phần đuôi tên miền để tránh bị qua mặt bởi dạng shopee.vn.domain-gia-mao.com
        return (bool)preg_match('/^(.*\.)?(shopee\.[a-z.]{2,6}|shope\.ee|shp\.ee)$/i', $host);
    }

    /**
     * Giải quyết link rút gọn (shope.ee, s.shopee.vn, shp.ee) để lấy link gốc cuối cùng.
     * Hỗ trợ trích xuất chính xác ID sản phẩm để đối soát cache hoặc lấy qua API/Crawler.
     *
     * @param string $url URL sản phẩm dạng rút gọn
     * @return string URL sản phẩm gốc hoàn chỉnh
     */
    public function resolveShortLink(string $url): string
    {
        try {
            $currentUrl = $url;
            $maxRedirects = 5;

            for ($i = 0; $i < $maxRedirects; $i++) {
                // Bóc tách link trung gian universal-link của Shopee trước khi xét tiếp.
                // Thực tế link rút gọn vn.shp.ee thường chuyển hướng về dạng bọc:
                // https://shopee.vn/universal-link?deep_and_web=1&redir=<link thật đã mã hoá>
                // Nếu không bóc tham số redir, hệ thống sẽ dừng ở link bọc và không đọc được link đích thật.
                $unwrapped = $this->unwrapUniversalLink($currentUrl);
                if ($unwrapped !== $currentUrl) {
                    Log::info("ShopeeCashbackService: Đã bóc tách link universal-link hop {$i} thành: " . $unwrapped);
                    $currentUrl = $unwrapped;
                    continue;
                }

                // Chỉ phân giải các tên miền rút gọn hoặc chuyển tiếp của Shopee
                if (str_contains($currentUrl, 'shope.ee') || str_contains($currentUrl, 'shp.ee') || str_contains($currentUrl, 's.shopee.vn')) {
                    Log::info("ShopeeCashbackService: Đang phân giải thủ công link rút gọn hop {$i}: " . $currentUrl);

                    // Gửi request không tự động chuyển hướng (allow_redirects = false)
                    // Điều này nhằm lấy header Location của link rút gọn mà không cần gửi request tiếp tới shopee.vn
                    // giúp tránh việc server bị Cloudflare/Shopee chặn IP (IP block).
                    $response = Http::withoutVerifying()
                        ->timeout(10)
                        ->withOptions([
                            'allow_redirects' => false
                        ])
                        ->withHeaders([
                            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'
                        ])
                        ->get($currentUrl);

                    $location = $response->header('Location');
                    if (!empty($location)) {
                        // Header Location được phép trả về đường dẫn tương đối (/abc). Phải ghép lại thành
                        // URL tuyệt đối, nếu không link thu được sẽ cụt và các bước sau (nhận diện link
                        // video, gọi API lấy thông tin sản phẩm) đều nhận URL sai.
                        $currentUrl = $this->toAbsoluteUrl(trim($location), $currentUrl);
                    } else {
                        $effective = (string)$response->effectiveUri();
                        if ($effective && $effective !== $currentUrl) {
                            $currentUrl = $effective;
                        } else {
                            break;
                        }
                    }
                } else {
                    break;
                }
            }

            if ($currentUrl !== $url) {
                Log::info("ShopeeCashbackService: Đã giải quyết thành công link rút gọn {$url} thành {$currentUrl}");
                return $currentUrl;
            }
        } catch (\Throwable $e) {
            Log::warning("ShopeeCashbackService: Lỗi khi giải quyết thủ công link rút gọn {$url}: " . $e->getMessage());
        }
        return $url;
    }

    /**
     * Chuyển đổi link Shopee sang link rút gọn affiliate của shp.today bằng cách gửi request curl giả lập.
     *
     * @param string $originUrl Link gốc của sản phẩm Shopee
     * @param string $subId Mã đối soát giao dịch (transId hoặc memberId)
     * @param int|null $userId ID người dùng thực hiện chuyển đổi
     * @return string|null Trả về link rút gọn của shp.today hoặc null nếu thất bại
     */
    private function shortenViaShpToday(string $originUrl, string $subId, ?int $userId = null): ?string
    {
        // Sử dụng trực tiếp Shopee App ID (Affiliate ID) cấu hình ở trên để làm us_id
        $usId = Setting::getVal('shopee_app_id');
        if (empty($usId)) {
            Log::error('ShopeeCashbackService: Chưa cấu hình Shopee App ID (Affiliate ID) trong settings. Bỏ qua rút gọn qua shp.today.');
            return null;
        }

        try {
            Log::info("ShopeeCashbackService: Đang chuyển đổi link qua shp.today cho URL: " . $originUrl);

            // Gửi request POST giả lập curl đến shp.today với đầy đủ thông số
            $response = Http::withHeaders([
                'accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7',
                'accept-language' => 'vi,en-US;q=0.9,en;q=0.8',
                'cache-control' => 'max-age=0',
                'dnt' => '1',
                'origin' => 'https://shp.today',
                'referer' => 'https://shp.today/',
                'user-agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36',
            ])->asForm()->post('https://shp.today/', [
                'text' => $originUrl,
                'us_id' => $usId,
                'sub1' => $subId,          // sub1 chứa mã đối soát giao dịch (trans_id) để đối soát đơn hàng sau này
                'sub2' => '',   // sub2 chứa user_id để tiện tracking
                'sub3' => '',
                'sub4' => '',
                'sub5' => '',
                'submit' => '',
            ]);

            if (!$response->successful()) {
                Log::error('ShopeeCashbackService: Lỗi HTTP khi kết nối đến shp.today: ' . $response->status());
                return null;
            }

            $html = $response->body();
            $shortLink = '';

            // Parse link rút gọn từ textarea #out_text bằng regex để tránh bị vướng thẻ html
            if (preg_match('/<textarea[^>]*id="out_text"[^>]*>(.*?)<\/textarea>/is', $html, $matches)) {
                $shortLink = trim($matches[1]);
            }

            // Fallback sang DOMDocument
            if (empty($shortLink)) {
                $dom = new \DOMDocument();
                @$dom->loadHTML($html);
                $xpath = new \DOMXPath($dom);
                $textarea = $xpath->query('//textarea[@id="out_text"]')->item(0);
                if ($textarea) {
                    $shortLink = trim($textarea->nodeValue);
                }
            }

            if (!empty($shortLink)) {
                Log::info("ShopeeCashbackService: Chuyển đổi link qua shp.today thành công: " . $shortLink);
                return $shortLink;
            }

            Log::error('ShopeeCashbackService: Không tìm thấy link rút gọn trong HTML phản hồi từ shp.today.');
        } catch (\Throwable $e) {
            Log::error('ShopeeCashbackService: Lỗi trong quá trình chuyển đổi link qua shp.today: ' . $e->getMessage());
        }

        return null;
    }
}
