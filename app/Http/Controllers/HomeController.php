<?php

namespace App\Http\Controllers;

use App\Models\Banner;
use App\Models\BotConfig;
use App\Models\CashbackHistory;
use App\Models\CashbackClick;
use App\Models\ClickLog;
use App\Models\Setting;
use App\Models\ShortLink;
use App\Services\ShopeeCashbackService;
use App\Services\TikTokCashbackService;
use App\Services\LazadaCashbackService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class HomeController extends Controller
{
    protected ShopeeCashbackService $shopeeCashbackService;
    protected TikTokCashbackService $tiktokCashbackService;
    protected LazadaCashbackService $lazadaCashbackService;

    /**
     * Khởi tạo HomeController với dịch vụ hoàn tiền Shopee, TikTok Shop và Lazada.
     *
     * @param ShopeeCashbackService $shopeeCashbackService
     * @param TikTokCashbackService $tiktokCashbackService
     * @param LazadaCashbackService $lazadaCashbackService
     */
    public function __construct(
        ShopeeCashbackService $shopeeCashbackService,
        TikTokCashbackService $tiktokCashbackService,
        LazadaCashbackService $lazadaCashbackService
    ) {
        $this->shopeeCashbackService = $shopeeCashbackService;
        $this->tiktokCashbackService = $tiktokCashbackService;
        $this->lazadaCashbackService = $lazadaCashbackService;
    }

    /**
     * Hiển thị trang chủ website.
     * Tích hợp lấy dữ liệu thống kê hệ thống (thực tế và số ảo tăng tính thuyết phục),
     * danh sách bài viết blog mới nhất để hiển thị tại trang chủ.
     */
    public function index(?Request $request = null)
    {
        $request = $request ?? request();

        // Lấy danh sách banner quảng cáo hoạt động
        $banners = Banner::where('is_active', true)->orderBy('order')->get();

        // Đọc tỷ lệ hoàn tiền hiện tại để giới thiệu ở trang chủ
        $cashbackRate = Setting::getVal('shopee_cashback_rate', 50);

        // Đọc các cấu hình giao diện homepage từ bảng settings (prefix hp_)
        // Cho phép admin tùy chỉnh nội dung text, bật/tắt section mà không cần sửa mã nguồn
        $hpSettings = Setting::where('key', 'like', 'hp_%')->pluck('value', 'key')->toArray();

        // Nạp danh sách block của trình dựng trang (Page Builder) theo thứ tự hiển thị.
        // Ở chế độ xem trước của admin, ưu tiên lấy bản nháp chưa lưu từ session.
        $blocks = $this->resolveHomeBlocks($request);

        // Nguồn số liệu ảo cho khối thống kê: ưu tiên settings của khối stats (nếu có),
        // sau đó tới khối hero (bố cục chia đôi), cuối cùng fallback các key hp_ cũ trong bảng settings.
        $statsSettings = optional($blocks->firstWhere('type', 'stats'))->settings
            ?: optional($blocks->firstWhere('type', 'hero'))->settings
            ?: [];
        $usersVirtual  = (int) ($statsSettings['hp_stats_users_value']  ?? $hpSettings['hp_stats_users_value']  ?? 1450);
        $paidVirtual   = (float) ($statsSettings['hp_stats_paid_value']  ?? $hpSettings['hp_stats_paid_value']  ?? 125000000);
        $clicksVirtual = (int) ($statsSettings['hp_stats_clicks_value'] ?? $hpSettings['hp_stats_clicks_value'] ?? 48200);

        // Quy tắc nghiệp vụ: Lấy số liệu thực tế từ database kết hợp số ảo (động từ cấu hình admin) để tăng mức độ tin cậy và chuyên nghiệp cho giao diện trang chủ.
        $totalUsers = \App\Models\User::count() + $usersVirtual;

        $realCashbackSum = CashbackHistory::where('status', 'approved')->sum('cashback_amount');
        $totalCashbackPaid = $realCashbackSum + $paidVirtual; // Thêm số ảo động từ Admin để tạo hiệu ứng chuyên nghiệp ban đầu

        $totalClicks = ClickLog::count() + $clicksVirtual;

        // Quy tắc nghiệp vụ: Lấy 3 bài viết blog mới nhất để hiển thị trên trang chủ giúp cải thiện SEO và cung cấp cẩm nang hữu ích cho người dùng.
        $latestPosts = \App\Models\Post::published()
            ->with(['category'])
            ->orderBy('published_at', 'desc')
            ->limit(3)
            ->get();

        // Lấy danh sách mã giảm giá Shopee còn hạn dùng để hiển thị trên trang chủ
        $shopeeCoupons = [];
        $couponStatus = Setting::getVal('coupon_status', '1');
        if ($couponStatus === '1') {
            $shopeeCoupons = \App\Models\Coupon::where('platform', 'shopee')
                ->where(function ($q) {
                    $q->whereNull('expired_at')
                        ->orWhere('expired_at', '>=', now());
                })
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();
        }

        return view('home', compact(
            'banners',
            'cashbackRate',
            'totalUsers',
            'totalCashbackPaid',
            'totalClicks',
            'latestPosts',
            'hpSettings',
            'shopeeCoupons',
            'blocks'
        ));
    }

    /**
     * Lấy danh sách block hiển thị trên trang chủ.
     * Nếu là yêu cầu xem trước từ trình dựng trang (admin đã đăng nhập) thì dựng block
     * từ bản nháp trong session; ngược lại lấy dữ liệu đã xuất bản từ database.
     */
    protected function resolveHomeBlocks(Request $request)
    {
        $isPreview = $request->boolean('__builder_preview')
            && Auth::check()
            && Auth::user()->isAdmin()
            && session()->has('hp_builder_draft');

        if ($isPreview) {
            return collect(session('hp_builder_draft', []))
                ->map(function ($b) {
                    $block = new \App\Models\PageBlock();
                    $block->type = $b['type'] ?? '';
                    $block->name = $b['name'] ?? '';
                    $block->settings = $b['settings'] ?? [];
                    $block->enabled = !empty($b['enabled']);
                    return $block;
                })
                ->filter(fn($b) => $b->type && view()->exists('home.blocks.' . $b->type))
                ->values();
        }

        return \App\Models\PageBlock::forPage('home');
    }

    /**
     * Xử lý AJAX lấy thông tin sản phẩm Shopee và hoàn tiền dự kiến.
     */
    public function getProductInfo(Request $request)
    {
        // Xác định thành viên thực hiện yêu cầu
        $user = null;
        if (Auth::check()) {
            $user = Auth::user();
        } else {
            // BẢO MẬT: Bắt buộc xác thực qua API Token / API Key bí mật cá nhân (Header Bearer hoặc tham số api_token / api_key)
            $tokenInput = $request->bearerToken() ?: $request->input('api_token') ?: $request->input('api_key');
            if (!empty($tokenInput)) {
                // 1. Kiểm tra API Key cá nhân trong bảng users
                $user = \App\Models\User::where('api_token', $tokenInput)->where('status', 'active')->first();

                // 2. Nếu không khớp, kiểm tra thêm trong bảng api_tokens (Personal Access Token)
                if (!$user) {
                    $validToken = \App\Models\ApiToken::findValid($tokenInput);
                    if ($validToken && $validToken->user && $validToken->user->status === 'active') {
                        $user = $validToken->user;
                    }
                }
            }
        }

        // Quy tắc nghiệp vụ: Hệ thống chỉ cho phép người dùng đã xác thực mới được phép phân tích sản phẩm và tạo link hoàn tiền.
        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => __('Vui lòng đăng nhập tài khoản hoặc cung cấp mã thành viên hợp lệ để thực hiện lấy link hoàn tiền.'),
                'redirect' => route('login')
            ], 401);
        }

        // === GIỚI HẠN TẦN SUẤT TẠO LINK (RATE LIMIT) ===
        // Quy tắc nghiệp vụ: Giới hạn số lần lấy link hoàn tiền của mỗi thành viên trong 5 phút nhằm tránh spam hoặc tấn công cào dữ liệu liên tục gây nghẽn băng thông
        $rateLimit = (int) Setting::getVal('rate_limit_create_link_5m', 10);
        if ($rateLimit > 0) {
            $userId = $user->id;
            $limiterKey = 'create_link_limit:' . $userId;

            // Nếu tài khoản đã đạt số lần tạo link tối đa quy định trong 5 phút (300 giây)
            if (\Illuminate\Support\Facades\RateLimiter::tooManyAttempts($limiterKey, $rateLimit)) {
                $secondsLeft = \Illuminate\Support\Facades\RateLimiter::availableIn($limiterKey);
                $minutesLeft = ceil($secondsLeft / 60);

                return response()->json([
                    'status' => 'error',
                    'message' => __('Bạn đã đạt giới hạn tạo link hoàn tiền (:limit link/5 phút). Vui lòng thử lại sau :minutes phút.', [
                        'limit' => $rateLimit,
                        'minutes' => $minutesLeft
                    ])
                ], 429);
            }

            // Tăng số lần thử (lưu trong 5 phút = 300 giây)
            \Illuminate\Support\Facades\RateLimiter::hit($limiterKey, 300);
        }

        // Tự động bổ sung giao thức https:// nếu người dùng dán link thiếu (ví dụ: shopee.vn, www.shopee.vn...)
        $urlInput = $request->input('url');
        if ($urlInput && !str_starts_with($urlInput, 'http://') && !str_starts_with($urlInput, 'https://')) {
            $urlInput = 'https://' . $urlInput;
            $request->merge(['url' => $urlInput]);
        }

        // 1. Kiểm tra validation URL đầu vào
        $request->validate([
            'url' => 'required|url'
        ], [
            'url.required' => __('Vui lòng dán link sản phẩm Shopee.'),
            'url.url' => __('Địa chỉ link sản phẩm không đúng định dạng.')
        ]);

        $url = $request->input('url');

        // === BẢO MẬT: Chống tấn công SSRF & Ràng buộc tên miền hợp lệ ===
        // Quy tắc nghiệp vụ: Ngăn chặn hoàn toàn việc kẻ tấn công lợi dụng máy chủ để quét cổng dịch vụ nội bộ (loopback/private IP) 
        // hoặc gửi các yêu cầu cURL không mong muốn tới máy chủ khác ngoài hệ thống sàn thương mại điện tử.
        if (!\App\Helpers\SecurityHelper::validateSsfUrl($url)) {
            return response()->json([
                'status' => 'error',
                'message' => __('Đường dẫn sản phẩm không hợp lệ hoặc không an toàn.')
            ], 422);
        }

        // Ràng buộc cụ thể host của URL phải thuộc hệ thống Shopee hoặc TikTok hợp lệ
        $parsedUrl = parse_url($url);
        $host = isset($parsedUrl['host']) ? strtolower($parsedUrl['host']) : '';
        $isValidDomain = false;

        // Danh sách các mẫu regex khớp với các tên miền chính thức của Shopee, TikTok Shop và Lazada
        $allowedDomains = [
            '/^(.*\.)?shopee\.(vn|sg|co\.id|com\.my|co\.th|ph|tw|com|cn|com\.br|cl|co|mx)$/i',
            '/^(.*\.)?shope\.ee$/i',
            '/^(.*\.)?shp\.ee$/i',
            '/^(.*\.)?tiktok\.com$/i',
            '/^(.*\.)?tiktok\.shop$/i',
            '/^(.*\.)?lazada\.(vn|sg|co\.id|com\.my|co\.th|com\.ph)$/i',
            '/^(.*\.)?lzd\.co$/i'
        ];

        foreach ($allowedDomains as $pattern) {
            if (preg_match($pattern, $host)) {
                $isValidDomain = true;
                break;
            }
        }

        if (!$isValidDomain) {
            return response()->json([
                'status' => 'error',
                'message' => __('Hệ thống chỉ hỗ trợ xử lý đường dẫn sản phẩm chính thức từ Shopee, TikTok Shop hoặc Lazada.')
            ], 422);
        }

        // 1. Phân loại link sản phẩm theo sàn thương mại điện tử (Shopee, Lazada, TikTok...) để dễ dàng mở rộng sau này
        $platform = null;
        if (str_contains($url, 'shopee.') || str_contains($url, 'shope.ee') || str_contains($url, 'shp.ee')) {
            $platform = 'shopee';
        } elseif (str_contains($url, 'tiktok.com') || str_contains($url, 'tiktok.shop')) {
            $platform = 'tiktok';
        } elseif (str_contains($url, 'lazada.') || str_contains($url, 'lzd.co')) {
            $platform = 'lazada';
        }

        // Nếu link không thuộc sàn nào được hỗ trợ
        if (!$platform) {
            return response()->json([
                'status' => 'error',
                'message' => __('Hệ thống hiện tại chỉ hỗ trợ hoàn tiền cho các sản phẩm từ các sàn thương mại điện tử liên kết.')
            ], 422);
        }

        // 2. Kiểm tra trạng thái hoạt động của sàn tương ứng được cấu hình từ quản trị hệ thống
        if ($platform === 'shopee' && Setting::getVal('shopee_status', '1') === '0') {
            return response()->json([
                'status' => 'error',
                'message' => __('Tính năng hoàn tiền Shopee hiện đang tạm bảo trì hoặc tạm ngưng hoạt động.')
            ], 422);
        }

        if ($platform === 'tiktok' && Setting::getVal('tiktok_status', '1') === '0') {
            return response()->json([
                'status' => 'error',
                'message' => __('Tính năng hoàn tiền TikTok Shop hiện đang tạm bảo trì hoặc tạm ngưng hoạt động.')
            ], 422);
        }

        if ($platform === 'lazada' && Setting::getVal('lazada_status', '0') === '0') {
            return response()->json([
                'status' => 'error',
                'message' => __('Tính năng hoàn tiền Lazada hiện đang tạm bảo trì hoặc tạm ngưng hoạt động.')
            ], 422);
        }

        // 2. Tạo mã transId / orderId đối soát trước dựa trên cấu hình tùy chỉnh hệ thống
        $transId = Setting::generateOrderCode($platform);

        // Gọi service phân tích link sản phẩm ở backend dựa trên sàn tương ứng
        if ($platform === 'tiktok') {
            $response = $this->tiktokCashbackService->getProductData($url, $user->id, $transId);
        } elseif ($platform === 'lazada') {
            $response = $this->lazadaCashbackService->getProductData($url, $user->id, $transId);
        } else {
            $response = $this->shopeeCashbackService->getProductData($url, $user->id, $transId);
        }

        if ($response['status'] === 'success') {
            $productData = $response['data'];
            // Bổ sung thông tin nền tảng sản phẩm (shopee/tiktok) để client có thể lưu vào mục mua sau
            $productData['platform'] = $platform;

            // Lưu vết click, nâng giới hạn ảnh sản phẩm lên 1000 ký tự để hỗ trợ các URL CDN dài của TikTok
            CashbackClick::create([
                'user_id' => $user->id,
                'platform' => $platform,
                'trans_id' => $transId,
                'product_name' => Str::limit($productData['name'], 500, '...'),
                'product_image' => Str::limit($productData['image'] ?? '', 1000, ''),
                'original_price' => $productData['price'],
                'cashback_amount' => $productData['cashback_amount'],
                'cashback_rate' => $productData['cashback_rate'],
                'commission_amount' => $productData['commission_amount'],
                'affiliate_url' => $productData['affiliate_url'],
            ]);

            // Ràng buộc nghiệp vụ: Thêm cấu hình hiển thị hoặc ẩn giá bán hiện tại dựa trên thiết lập của Admin cho từng nền tảng
            // shopee_show_current_price cho Shopee, tiktok_show_current_price cho TikTok Shop, lazada_show_current_price cho Lazada (mặc định '1' - Hiển thị, '0' - Ẩn)
            $showPrice = match ($platform) {
                'tiktok' => Setting::getVal('tiktok_show_current_price', '1'),
                'lazada' => Setting::getVal('lazada_show_current_price', '1'),
                default => Setting::getVal('shopee_show_current_price', '1'),
            };
            $productData['show_price'] = $showPrice;

            // Đính kèm lưu ý hoàn tiền của sàn tương ứng để hiển thị động ở giao diện
            $rawNotice = match ($platform) {
                'tiktok' => Setting::getVal('hp_cashback_notice_tiktok', ''),
                'lazada' => Setting::getVal('hp_cashback_notice_lazada', ''),
                default => Setting::getVal('hp_cashback_notice', ''),
            };
            $productData['cashback_notice'] = nl2br(e($rawNotice));

            // Loại bỏ các trường thông tin nội bộ / không muốn công khai ra API response.
            // Riêng cashback_rate được GIỮ LẠI cho sản phẩm ước tính (is_estimated) để giao diện hiển thị
            // "hoàn trả khoảng X%" — trường hợp không có giá cụ thể (ví dụ Lazada API không trả giá).
            unset($productData['commission_amount'], $productData['demo_order_id']);
            if (empty($productData['is_estimated'])) {
                unset($productData['cashback_rate']);
            }

            return response()->json([
                'status' => 'success',
                'message' => __('Lấy thông tin sản phẩm thành công!'),
                'data' => $productData,
                'logged_in' => true
            ]);
        }

        return response()->json([
            'status' => 'error',
            'message' => $response['message'] ?? __('Không thể phân tích thông tin sản phẩm từ sàn thương mại. Vui lòng kiểm tra lại đường dẫn.')
        ], 400);
    }

    /**
     * Chuyển hướng liên kết rút gọn và ghi nhận lượt click tracking.
     * Logic này giúp ẩn link affiliate phức tạp của Shopee và ghi lại số lần nhấp chuột.
     *
     * @param string $code
     * @return \Illuminate\Http\RedirectResponse
     */
    public function redirectShortLink(string $code)
    {
        // Truy vấn bản ghi liên kết rút gọn theo code ngẫu nhiên
        $shortLink = ShortLink::where('code', $code)->first();

        // Nếu mã không hợp lệ hoặc liên kết không tồn tại, quay về trang chủ với thông báo lỗi
        if (!$shortLink) {
            return redirect()->route('home')->with('error', __('Liên kết không tồn tại hoặc đã hết hạn.'));
        }

        // Tăng số lượt click của liên kết phục vụ cho việc đo lường, đối soát sau này
        $shortLink->increment('clicks');

        // === BẢO MẬT: Sanitize toàn bộ dữ liệu đầu vào trước khi lưu DB (Defense-in-Depth) ===

        // 1. Thu thập và validate địa chỉ IP — chỉ chấp nhận IPv4/IPv6 hợp lệ
        $rawIp = request()->ip();
        $ip = filter_var($rawIp, FILTER_VALIDATE_IP) ? $rawIp : 'Invalid IP';

        // 2. Thu thập User Agent: loại bỏ mọi thẻ HTML/script để chống XSS, giới hạn 500 ký tự để tránh payload quá lớn
        $rawUserAgent = request()->userAgent() ?? 'Unknown';
        $userAgent = mb_substr(strip_tags($rawUserAgent), 0, 500);

        // 3. Phân tích thiết bị dựa trên User Agent (phân tích trên chuỗi đã sanitize)
        $device = 'Desktop';
        if (preg_match('/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows ce|xda|xiino/i', $userAgent)) {
            $device = 'Mobile';
        } elseif (preg_match('/ipad|playbook|silk/i', $userAgent)) {
            $device = 'Tablet';
        }

        // 4. Truy vấn vị trí địa lý dựa vào IP — sanitize dữ liệu trả về từ API bên thứ 3
        $location = __('Không xác định');
        if ($ip !== '127.0.0.1' && $ip !== '::1' && $ip !== 'Invalid IP') {
            try {
                $locResponse = \Illuminate\Support\Facades\Http::timeout(2)->get("http://ip-api.com/json/{$ip}?lang=vi");
                if ($locResponse->successful()) {
                    $locData = $locResponse->json();
                    if (($locData['status'] ?? '') === 'success') {
                        // Sanitize dữ liệu trả về từ API bên thứ 3 để tránh chèn mã độc gián tiếp
                        $city = strip_tags($locData['city'] ?? '');
                        $country = strip_tags($locData['country'] ?? '');
                        $location = mb_substr($city . ', ' . $country, 0, 200);
                    }
                }
            } catch (\Exception $e) {
                // Bỏ qua lỗi kết nối API vị trí để đảm bảo người dùng luôn được chuyển hướng thành công sang Shopee
            }
        } else {
            $location = __('Localhost (Môi trường phát triển)');
        }

        // 5. Ghi log click vào bảng click_logs (lưu toàn bộ lịch sử, không ghi đè)
        ClickLog::create([
            'short_link_id' => $shortLink->id,
            'ip_address' => $ip,
            'device' => $device,
            'location' => $location,
            'user_agent' => $userAgent,
        ]);

        // Kiểm tra xem yêu cầu truy cập có phải từ Crawler/Bot của các mạng xã hội hoặc công cụ tìm kiếm không
        $isBot = false;
        if (!empty($userAgent)) {
            $socialBots = [
                'facebookexternalhit',
                'facebot',
                'twitterbot',
                'telegrambot',
                'whatsapp',
                'pinterestbot',
                'linkedinbot',
                'slackbot',
                'discordbot',
                'viber',
                'zalobot',
                'googlebot',
                'bingbot',
                'yandexbot',
                'baiduspider',
                'duckduckbot'
            ];
            foreach ($socialBots as $bot) {
                if (stripos($userAgent, $bot) !== false) {
                    $isBot = true;
                    break;
                }
            }
        }

        // Nếu là bot mạng xã hội/công cụ tìm kiếm và liên kết rút gọn có lưu tiêu đề sản phẩm
        // Trả về trang HTML chứa các thẻ Open Graph meta để hiển thị ảnh và tiêu đề trên mạng xã hội
        if ($isBot && !empty($shortLink->product_name)) {
            $siteName = Setting::getVal('site_name', 'Hoàn Tiền Shopee');
            $siteFavicon = Setting::getVal('site_favicon', '/favicon.ico');

            // Tạo mô tả ngắn gọn và hấp dẫn cho link preview khi chia sẻ trên mạng xã hội
            $description = __('Nhấp vào liên kết để mua sản phẩm và nhận hoàn tiền hoa hồng tự động từ :site_name.', ['site_name' => $siteName]);

            return response()->view('shortlink_preview', [
                'productName' => $shortLink->product_name,
                'productImage' => $shortLink->product_image ?: asset($siteFavicon),
                'description' => $description,
                'destinationUrl' => $shortLink->destination_url,
                'currentUrl' => url($shortLink->code),
                'siteName' => $siteName,
                'siteFavicon' => $siteFavicon
            ]);
        }

        // Thực hiện redirect away (chuyển hướng ra ngoài ứng dụng) đến link affiliate Shopee gốc cho người dùng thông thường
        return redirect()->away($shortLink->destination_url);
    }

    public function botGuide()
    {
        $zaloConfig     = BotConfig::forType('zalo');
        $telegramConfig = BotConfig::forType('telegram');

        return view('bots.guide', compact('zaloConfig', 'telegramConfig'));
    }
}
