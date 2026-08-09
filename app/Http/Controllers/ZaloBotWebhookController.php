<?php

namespace App\Http\Controllers;

use App\Models\BotConfig;
use App\Models\BotMessage;
use App\Models\CashbackClick;
use App\Models\Setting;
use App\Models\User;
use App\Helpers\BotLinkHelper;
use App\Services\ZaloBotService;
use App\Services\LazadaCashbackService;
use App\Services\ShopeeCashbackService;
use App\Services\TikTokCashbackService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ZaloBotWebhookController extends Controller
{
    protected ShopeeCashbackService $shopeeCashbackService;
    protected TikTokCashbackService $tiktokCashbackService;
    protected LazadaCashbackService $lazadaCashbackService;

    public function __construct(
        ShopeeCashbackService $shopeeCashbackService,
        TikTokCashbackService $tiktokCashbackService,
        LazadaCashbackService $lazadaCashbackService
    ) {
        $this->shopeeCashbackService = $shopeeCashbackService;
        $this->tiktokCashbackService = $tiktokCashbackService;
        $this->lazadaCashbackService = $lazadaCashbackService;
    }

    public function handle(Request $request)
    {
        // Luôn trả về 200 để Zalo không retry — mọi lỗi đều bị bắt ở đây
        try {
            return $this->process($request);
        } catch (\Throwable $e) {
            Log::error('ZaloBot handle() unhandled exception: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['ok' => true]);
        }
    }

    protected function process(Request $request)
    {
        $config = BotConfig::forType('zalo');

        if (!$config->is_enabled || empty($config->bot_token)) {
            return response()->json(['ok' => true]);
        }

        // Xác thực secret trong URL path và header X-Bot-Api-Secret-Token từ Zalo Bot Platform
        if (!empty($config->webhook_secret)) {
            $routeSecret = $request->route('secret', '');
            if (!hash_equals($config->webhook_secret, $routeSecret)) {
                Log::warning('ZaloBot webhook rejected: invalid secret in path', ['ip' => $request->ip()]);
                return response()->json(['ok' => false], 401);
            }

            $headerSecret = $request->header('X-Bot-Api-Secret-Token', '');
            if (!empty($headerSecret) && !hash_equals($config->webhook_secret, $headerSecret)) {
                Log::warning('ZaloBot webhook rejected: invalid secret in header', ['ip' => $request->ip()]);
                return response()->json(['ok' => false], 401);
            }
        }

        $payload = $request->all();
        Log::debug('ZaloBot webhook payload', $payload);

        // Zalo Bot Platform gửi message bọc trong result, kiểm tra cả hai trường hợp
        $message = $payload['result']['message'] ?? $payload['message'] ?? null;
        if (!$message) {
            return response()->json(['ok' => true]);
        }

        $chatId   = (string) ($message['chat']['id'] ?? $message['from']['id'] ?? '');

        // Xử lý sự kiện tin nhắn không được hỗ trợ (Zalo tự động chuyển link thành Card Preview làm Zapps lỗi)
        $eventName = $payload['event_name'] ?? null;
        if ($eventName === 'message.unsupported.received' && !empty($chatId)) {
            Log::info('ZaloBot: Received message.unsupported.received, sending instructions to user.');
            $service = new ZaloBotService($config->bot_token);
            $reply = "⚠️ Không thể đọc được liên kết dạng Xem trước (Preview) trên Zalo điện thoại.\n\n"
                   . "👉 Bạn vui lòng làm theo hướng dẫn sau để nhận link hoàn tiền:\n\n"
                   . "Dán link sản phẩm vào khung chat, sau đó bấm nút tắt (dấu x ở góc trên ô Xem trước) rồi mới bấm Gửi.";
            $service->sendMessage($chatId, $reply);
            return response()->json(['ok' => true]);
        }
        
        // Ưu tiên trích xuất URL từ các thuộc tính link hoặc attachments trước, vì ứng dụng Zalo
        // tự động tạo link card và có thể ghi đè tiêu đề/nội dung text khác vào trường message.text.
        $text = '';
        if (!empty($message['link'])) {
            $text = trim($message['link']);
            Log::info('ZaloBot: Trích xuất URL từ thuộc tính link: ' . $text);
        } elseif (!empty($message['attachments']) && is_array($message['attachments'])) {
            foreach ($message['attachments'] as $attachment) {
                if (isset($attachment['type']) && $attachment['type'] === 'link' && !empty($attachment['payload']['url'])) {
                    $text = trim($attachment['payload']['url']);
                    Log::info('ZaloBot: Trích xuất URL từ danh sách attachments: ' . $text);
                    break;
                }
            }
        }

        // Nếu không có link card/attachment, lấy text thường làm fallback
        if (empty($text)) {
            $text = trim($message['text'] ?? '');
        }

        // Ghi log chi tiết payload và kết quả trích xuất vào zalobot.log để hỗ trợ debug live
        $this->logDebug('ZaloBot webhook raw message', [
            'chat_id' => $chatId,
            'original_text' => $message['text'] ?? null,
            'extracted_text' => $text,
            'has_link' => !empty($message['link']),
            'has_attachments' => !empty($message['attachments'])
        ]);
        
        // Zalo sử dụng display_name cho tên hiển thị của người dùng
        $userName = trim($message['from']['display_name'] ?? ($message['from']['first_name'] ?? '') . ' ' . ($message['from']['last_name'] ?? ''));
        $userName = trim($userName) ?: null;

        if (empty($chatId) || empty($text)) {
            return response()->json(['ok' => true]);
        }

        // Lưu tin nhắn đến (inbound)
        BotMessage::create([
            'bot_type'  => 'zalo',
            'chat_id'   => $chatId,
            'user_name' => $userName,
            'direction' => 'inbound',
            'content'   => $text,
            'metadata'  => $message,
        ]);

        $service = new ZaloBotService($config->bot_token);
        $reply   = $this->processMessage($text, $chatId, $config);

        // Gửi trả lời
        $service->sendMessage($chatId, $reply);

        // Lưu tin nhắn đi (outbound)
        BotMessage::create([
            'bot_type'  => 'zalo',
            'chat_id'   => $chatId,
            'user_name' => null,
            'direction' => 'outbound',
            'content'   => $reply,
        ]);

        return response()->json(['ok' => true]);
    }

    /**
     * Xử lý nội dung tin nhắn và trả về phản hồi phù hợp.
     */
    protected function processMessage(string $text, string $chatId, BotConfig $config): string
    {
        // Xử lý lệnh /link <api_token_hoac_referral_code> để liên kết tài khoản
        if (Str::startsWith($text, '/link')) {
            $parts = explode(' ', trim($text), 2);
            $code = trim($parts[1] ?? '');
            if (empty($code)) {
                return "❌ Vui lòng nhập mã: /link <MÃ_CÁ_NHÂN>\n\nMã liên kết của bạn ở phần Hồ sơ trên website.";
            }
            $user = User::where('api_token', $code)->orWhere('referral_code', strtoupper($code))->first();
            if (!$user) {
                return "❌ Không tìm thấy tài khoản với mã *{$code}*.\nVui lòng kiểm tra lại mã liên kết trong phần Hồ sơ trên website.";
            }
            $user->update(['bot_zalo_chat_id' => $chatId]);
            return "✅ Liên kết thành công!\n\nTài khoản *{$user->name}* đã được liên kết với Zalo này.\nBây giờ hãy gửi link sản phẩm Shopee hoặc TikTok Shop để nhận link hoàn tiền!";
        }

        // Xử lý lệnh /start hoặc chào hỏi
        if (Str::startsWith($text, '/start') || in_array(mb_strtolower($text), ['hi', 'hello', 'xin chào', 'chào', 'chao'])) {
            $linked = User::where('bot_zalo_chat_id', $chatId)->exists();
            if ($linked) {
                return $config->welcome_message ?? "Xin chào! Hãy gửi link sản phẩm Shopee hoặc TikTok Shop để nhận link hoàn tiền ngay.";
            }
            return ($config->welcome_message ?? "Xin chào! Hãy gửi link sản phẩm Shopee hoặc TikTok Shop để nhận link hoàn tiền ngay.")
                . "\n\n" . ($config->login_required_message ?? "🔗 Để hoàn tiền về đúng tài khoản của bạn, hãy liên kết bằng lệnh:\n/link <MÃ GIỚI THIỆU>\n\nMã của bạn ở phần Hồ sơ trên website.");
        }

        // Nếu không phải link sản phẩm, kiểm tra trạng thái liên kết để phản hồi hướng dẫn phù hợp
        if (!$this->isProductLink($text)) {
            $linked = User::where('bot_zalo_chat_id', $chatId)->exists();
            if ($linked) {
                return "❌ Vui lòng gửi link sản phẩm Shopee, TikTok Shop hoặc Lazada hợp lệ.";
            }
            return "❌ Vui lòng gửi link sản phẩm Shopee, TikTok Shop hoặc Lazada hợp lệ.\n\n" . ($config->login_required_message ?? "Nếu chưa liên kết tài khoản, dùng lệnh:\n/link <MÃ GIỚI THIỆU>");
        }

        // Tự động trích xuất URL sạch từ tin nhắn để loại bỏ các ký tự/chữ viết kèm theo (tránh lỗi phân tích)
        $text = $this->extractUrlFromText($text);

        // Tự động thêm https:// nếu thiếu
        if (!str_starts_with($text, 'http://') && !str_starts_with($text, 'https://')) {
            $text = 'https://' . $text;
        }

        // Xác định platform (dùng chung nguồn nhận diện với Bot Telegram)
        $platform = BotLinkHelper::detectPlatform($text);

        if (!$platform) {
            return $config->not_found_message ?? __('Hệ thống chỉ hỗ trợ link Shopee, TikTok Shop và Lazada.');
        }

        // Lazada mặc định TẮT ('0') và cần Admin cấu hình App Key của Lazada Open Platform mới chạy
        // được, nên phải báo rõ đang tạm ngưng thay vì để service lỗi rồi trả về "không tìm thấy sản
        // phẩm" khiến khách tưởng link hỏng. Shopee/TikTok Shop giữ nguyên hành vi cũ là không kiểm
        // tra công tắc, tránh làm đổi cách chạy của các Bot đang phục vụ khách.
        if ($platform === 'lazada' && Setting::getVal('lazada_status', '0') === '0') {
            return __('Tính năng hoàn tiền Lazada hiện đang tạm bảo trì hoặc tạm ngưng hoạt động.');
        }

        // Tìm user theo Zalo chat_id đã liên kết
        $user = User::where('bot_zalo_chat_id', $chatId)->first();
        if (!$user) {
            return $config->login_required_message ?? "⚠️ Zalo của bạn chưa được liên kết với tài khoản nào.\n\nDùng lệnh sau để liên kết:\n/link <MÃ GIỚI THIỆU>\n\nMã giới thiệu của bạn ở phần Hồ sơ trên website.";
        }

        try {
            $transId = Setting::generateOrderCode($platform);

            $this->logDebug('ZaloBot calling service getProductData', [
                'platform' => $platform,
                'trans_id' => $transId,
                'user_id' => $user->id,
                'text' => $text
            ]);

            $response = match ($platform) {
                'tiktok' => $this->tiktokCashbackService->getProductData($text, $user->id, $transId),
                'lazada' => $this->lazadaCashbackService->getProductData($text, $user->id, $transId),
                default => $this->shopeeCashbackService->getProductData($text, $user->id, $transId),
            };

            $this->logDebug('ZaloBot service response', [
                'status' => $response['status'] ?? null,
                'message' => $response['message'] ?? null,
                'data' => isset($response['data']) ? [
                    'name' => $response['data']['name'] ?? null,
                    'price' => $response['data']['price'] ?? null,
                    'cashback_amount' => $response['data']['cashback_amount'] ?? null,
                    'affiliate_url' => $response['data']['affiliate_url'] ?? null
                ] : null
            ]);

            if ($response['status'] !== 'success') {
                return $config->not_found_message ?? __('Không lấy được thông tin sản phẩm. Vui lòng thử lại.');
            }

            $productData = $response['data'];

            // Lưu click (ShortLink đã được tạo tự động bên trong service)
            CashbackClick::create([
                'user_id'          => $user->id,
                'platform'         => $platform,
                'trans_id'         => $transId,
                'product_name'     => Str::limit($productData['name'], 500, '...'),
                'product_image'    => Str::limit($productData['image'] ?? '', 1000, ''),
                'original_price'   => $productData['price'],
                'cashback_amount'  => $productData['cashback_amount'],
                'cashback_rate'    => $productData['cashback_rate'],
                'commission_amount'=> $productData['commission_amount'],
                'affiliate_url'    => $productData['affiliate_url'],
            ]);

            // affiliate_url đã được service tự động rút gọn thành short link
            $shortLink = $productData['affiliate_url'];
            $cashbackAmount = number_format($productData['cashback_amount'], 0, ',', '.');

            $template = $config->cashback_template ?? "✅ Link hoàn tiền:\n\n🛍️ {product_name}\n💰 Hoàn tiền ước tính: {cashback_amount}đ\n🔗 {cashback_link}";

            return str_replace(
                ['{product_name}', '{cashback_amount}', '{cashback_link}', '{platform}'],
                [Str::limit($productData['name'], 100), $cashbackAmount, $shortLink, strtoupper($platform)],
                $template
            );
        } catch (\Throwable $e) {
            Log::error('ZaloBot processMessage error: ' . $e->getMessage());
            $this->logDebug('ZaloBot processMessage exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return $config->not_found_message ?? __('Có lỗi xảy ra. Vui lòng thử lại sau.');
        }
    }

    /**
     * Kiểm tra xem text có phải link sản phẩm không.
     */
    protected function isProductLink(string $text): bool
    {
        // Danh sách tên miền (kể cả biến thể bị bẻ dấu chấm để lách thẻ xem trước của Zalo)
        // nằm tập trung ở BotLinkHelper để không bị lệch với Bot Telegram khi mở thêm sàn mới.
        return BotLinkHelper::isProductLink($text);
    }

    /**
     * Ghi log riêng biệt vào tệp storage/logs/zalobot.log để sếp Thành dễ dàng đối soát live.
     */
    private function logDebug(string $message, array $context = []): void
    {
        try {
            Log::build([
                'driver' => 'single',
                'path' => storage_path('logs/zalobot.log'),
            ])->info($message, $context);
        } catch (\Throwable $e) {
            Log::error('Failed to write to zalobot.log: ' . $e->getMessage());
        }
    }

    /**
     * Trích xuất URL sạch từ tin nhắn văn bản hỗn hợp (loại bỏ text thừa xung quanh).
     */
    protected function extractUrlFromText(string $text): string
    {
        // 1. Tìm kiếm URL đầy đủ có giao thức http/https
        if (preg_match('/(https?:\/\/[^\s]+)/i', $text, $matches)) {
            return $matches[1];
        }

        // 2. Tìm kiếm URL không có giao thức nhưng thuộc các tên miền được hỗ trợ
        $domains = BotLinkHelper::PRODUCT_DOMAINS;
        foreach ($domains as $domain) {
            if (preg_match('/(' . preg_quote($domain, '/') . '[^\s]*)/i', $text, $matches)) {
                return 'https://' . $matches[1];
            }
        }

        // 3. Hỗ trợ trích xuất các link viết cách quãng bằng khoảng trắng hoặc dấu phẩy để vượt qua bộ lọc preview của Zalo App
        // Ví dụ: "s.shopee.vn 6ff90DH9Ch" hoặc "s.shopee.vn , 6ff90DH9Ch" hoặc "shopee.vn 12345 67890"
        foreach ($domains as $domain) {
            $escapedDomain = preg_quote($domain, '/');
            if (stripos($text, $domain) !== false) {
                // Lấy phần text bắt đầu từ domain
                $pos = stripos($text, $domain);
                $subText = substr($text, $pos);
                
                // Chuẩn hoá: thay thế tất cả khoảng trắng, dấu phẩy, nhiều gạch chéo liên tiếp thành một dấu gạch chéo '/'
                $cleanedPath = preg_replace('/[\s,\/]+/', '/', $subText);
                
                // Ví dụ: "s.shopee.vn 6ff90DH9Ch" -> "s.shopee.vn/6ff90DH9Ch"
                // "shopee.vn product 123 456" -> "shopee.vn/product/123/456"
                // Lấy từ đầu đến hết các ký tự URL hợp lệ
                if (preg_match('/^(' . $escapedDomain . '[a-zA-Z0-9\/\-_?=&%.]*)/i', $cleanedPath, $matches)) {
                    $reconstructed = 'https://' . $matches[1];
                    Log::info('ZaloBot: Reconstructed URL from interrupted format: ' . $reconstructed);
                    return $reconstructed;
                }
            }
        }

        // 4. Hỗ trợ các định dạng viết tách hoàn toàn không có dấu chấm (thay bằng khoảng trắng, dấu gạch ngang, gạch dưới)
        // Ví dụ: "s shopee vn 6ff90DH9Ch" hoặc "s-shopee-vn 6ff90DH9Ch" hoặc "shopee vn product 123 456"
        $domainReplacements = [
            's shopee vn' => 's.shopee.vn',
            's-shopee-vn' => 's.shopee.vn',
            's_shopee_vn' => 's.shopee.vn',
            'shopee vn'   => 'shopee.vn',
            'shopee-vn'   => 'shopee.vn',
            'shopee_vn'   => 'shopee.vn',
            'shope ee'    => 'shope.ee',
            'shope-ee'    => 'shope.ee',
            'shope_ee'    => 'shope.ee',
            'shp ee'      => 'shp.ee',
            'shp-ee'      => 'shp.ee',
            'shp_ee'      => 'shp.ee',
            'tiktok com'  => 'tiktok.com',
            'tiktok-com'  => 'tiktok.com',
            'tiktok_com'  => 'tiktok.com',
        ];

        foreach ($domainReplacements as $brokenDomain => $cleanDomain) {
            if (stripos($text, $brokenDomain) !== false) {
                // Thay thế domain viết hỏng thành domain chuẩn
                $pos = stripos($text, $brokenDomain);
                $subText = substr($text, $pos);
                
                // Thay thế domain hỏng bằng domain sạch
                $subText = str_ireplace($brokenDomain, $cleanDomain, $subText);
                
                // Chuẩn hoá: thay thế tất cả khoảng trắng, dấu phẩy, nhiều gạch chéo liên tiếp thành một dấu gạch chéo '/'
                $cleanedPath = preg_replace('/[\s,\/]+/', '/', $subText);
                
                $escapedDomain = preg_quote($cleanDomain, '/');
                if (preg_match('/^(' . $escapedDomain . '[a-zA-Z0-9\/\-_?=&%.]*)/i', $cleanedPath, $matches)) {
                    $reconstructed = 'https://' . $matches[1];
                    Log::info('ZaloBot: Reconstructed URL from broken domain format: ' . $reconstructed);
                    return $reconstructed;
                }
            }
        }

        return $text;
    }
}
