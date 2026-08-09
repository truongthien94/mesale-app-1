<?php

namespace App\Http\Controllers;

use App\Models\BotConfig;
use App\Models\BotMessage;
use App\Models\CashbackClick;
use App\Models\Setting;
use App\Models\User;
use App\Helpers\BotLinkHelper;
use App\Services\TelegramBotService;
use App\Services\LazadaCashbackService;
use App\Services\ShopeeCashbackService;
use App\Services\TikTokCashbackService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TelegramBotWebhookController extends Controller
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
        // Luôn trả về 200 để Telegram không retry — mọi lỗi đều bị bắt ở đây
        try {
            return $this->process($request);
        } catch (\Throwable $e) {
            Log::error('TelegramBot handle() unhandled exception: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['ok' => true]);
        }
    }

    protected function process(Request $request)
    {
        $config = BotConfig::forType('telegram');

        if (!$config->is_enabled || empty($config->bot_token)) {
            return response()->json(['ok' => true]);
        }

        // Xác thực secret trong URL path: /webhook/telegram-bot/{secret}
        if (!empty($config->webhook_secret)) {
            $routeSecret = $request->route('secret', '');
            if (!hash_equals($config->webhook_secret, $routeSecret)) {
                Log::warning('TelegramBot webhook rejected: invalid secret in path', ['ip' => $request->ip()]);
                return response()->json(['ok' => false], 401);
            }
        }

        $payload = $request->all();

        $message = $payload['message'] ?? $payload['channel_post'] ?? null;
        if (!$message) {
            return response()->json(['ok' => true]);
        }

        $chatId   = (string) ($message['chat']['id'] ?? '');
        $fromId   = (string) ($message['from']['id'] ?? '');
        $text     = trim($message['text'] ?? '');
        $fromName = trim(($message['from']['first_name'] ?? '') . ' ' . ($message['from']['last_name'] ?? ''));
        $fromName = trim($fromName) ?: ($message['from']['username'] ?? null);

        if (empty($chatId) || empty($fromId) || empty($text)) {
            return response()->json(['ok' => true]);
        }

        // Log inbound
        BotMessage::create([
            'bot_type'  => 'telegram',
            'chat_id'   => $chatId,
            'user_name' => $fromName,
            'direction' => 'inbound',
            'content'   => $text,
            'metadata'  => $message,
        ]);

        $service = new TelegramBotService($config->bot_token);
        $reply   = $this->processMessage($text, $chatId, $fromId, $config);

        if (!empty($reply)) {
            // Gửi phản hồi — dùng HTML mode, đơn giản và không lỗi escape
            $service->sendMessage($chatId, $reply, 'HTML');

            // Log outbound
            BotMessage::create([
                'bot_type'  => 'telegram',
                'chat_id'   => $chatId,
                'user_name' => null,
                'direction' => 'outbound',
                'content'   => $reply,
            ]);
        }

        return response()->json(['ok' => true]);
    }

    protected function processMessage(string $text, string $chatId, string $fromId, BotConfig $config): ?string
    {
        $isGroup = ($chatId !== $fromId);

        // Lệnh /link <api_token_hoac_referral_code> để liên kết tài khoản
        if (Str::startsWith($text, '/link')) {
            $parts = explode(' ', trim($text), 2);
            $code  = trim($parts[1] ?? '');
            if (empty($code)) {
                return "❌ Vui lòng nhập mã:\n<code>/link MÃ_LIÊN_KẾT</code>\n\nMã liên kết của bạn ở phần <b>Hồ sơ</b> trên website.";
            }
            $user = User::where('api_token', $code)->orWhere('referral_code', strtoupper($code))->first();
            if (!$user) {
                return "❌ Không tìm thấy tài khoản với mã <code>{$code}</code>.\nVui lòng kiểm tra lại mã liên kết trong phần <b>Hồ sơ</b> trên website.";
            }
            // Liên kết bằng ID cá nhân của user (fromId) thay vì ID nhóm (chatId)
            $user->update(['bot_telegram_chat_id' => $fromId]);
            return "✅ <b>Liên kết thành công!</b>\n\nTài khoản <b>" . htmlspecialchars($user->name) . "</b> đã được liên kết với Telegram này.\nBây giờ hãy gửi link sản phẩm Shopee hoặc TikTok Shop để nhận link hoàn tiền!";
        }

        // Lệnh /start hoặc chào hỏi
        if (Str::startsWith($text, '/start') || in_array(mb_strtolower($text), ['hi', 'hello', 'xin chào', 'chào', 'chao'])) {
            // Trong group, không trả lời tin nhắn chào hỏi tự động để tránh loãng group
            if ($isGroup) {
                return null;
            }
            $linked  = User::where('bot_telegram_chat_id', $fromId)->exists();
            $welcome = $config->welcome_message ?? 'Xin chào! Hãy gửi link sản phẩm Shopee hoặc TikTok Shop để nhận link hoàn tiền ngay.';
            if ($linked) {
                return $welcome;
            }
            return $welcome . "\n\n" . ($config->login_required_message ?? "🔗 Để hoàn tiền về đúng tài khoản, liên kết bằng lệnh:\n<code>/link MÃ_GIỚI_THIỆU</code>\n\nMã của bạn ở phần <b>Hồ sơ</b> trên website.");
        }

        // Nếu không phải link sản phẩm, kiểm tra trạng thái liên kết để phản hồi hướng dẫn phù hợp
        if (!$this->isProductLink($text)) {
            // Trong group, nếu không phải link sản phẩm thì bỏ qua để tránh spam group
            if ($isGroup) {
                return null;
            }
            $linked  = User::where('bot_telegram_chat_id', $fromId)->exists();
            if ($linked) {
                return "❌ Vui lòng gửi link sản phẩm Shopee, TikTok Shop hoặc Lazada hợp lệ.";
            }
            return "❌ Vui lòng gửi link sản phẩm Shopee, TikTok Shop hoặc Lazada hợp lệ.\n\n" . ($config->login_required_message ?? "Nếu chưa liên kết tài khoản:\n<code>/link MÃ_GIỚI_THIỆU</code>");
        }

        // Thêm https:// nếu thiếu
        if (!str_starts_with($text, 'http://') && !str_starts_with($text, 'https://')) {
            $text = 'https://' . $text;
        }

        // Xác định platform (dùng chung nguồn nhận diện với Bot Zalo)
        $platform = BotLinkHelper::detectPlatform($text);

        if (!$platform) {
            // Trong group, nếu không nhận dạng được platform thì im lặng
            if ($isGroup) {
                return null;
            }
            return $config->not_found_message ?? 'Hệ thống chỉ hỗ trợ link Shopee, TikTok Shop và Lazada.';
        }

        // Lazada mặc định TẮT ('0') và cần Admin cấu hình App Key của Lazada Open Platform mới chạy
        // được, nên phải báo rõ đang tạm ngưng thay vì để service lỗi rồi trả về "không tìm thấy sản
        // phẩm" khiến khách tưởng link hỏng. Shopee/TikTok Shop giữ nguyên hành vi cũ là không kiểm
        // tra công tắc, tránh làm đổi cách chạy của các Bot đang phục vụ khách.
        if ($platform === 'lazada' && Setting::getVal('lazada_status', '0') === '0') {
            if ($isGroup) {
                return null;
            }
            return __('Tính năng hoàn tiền Lazada hiện đang tạm bảo trì hoặc tạm ngưng hoạt động.');
        }

        // Kiểm tra liên kết tài khoản (truy vấn theo fromId)
        $user = User::where('bot_telegram_chat_id', $fromId)->first();
        if (!$user) {
            // Trong group, nếu user chưa liên kết thì im lặng để tránh spam
            if ($isGroup) {
                return null;
            }
            return $config->login_required_message ?? "⚠️ Telegram của bạn chưa được liên kết với tài khoản nào.\n\nDùng lệnh sau để liên kết:\n<code>/link MÃ_GIỚI_THIỆU</code>\n\nMã giới thiệu của bạn ở phần <b>Hồ sơ</b> trên website.";
        }

        try {
            $transId = Setting::generateOrderCode($platform);

            $response = match ($platform) {
                'tiktok' => $this->tiktokCashbackService->getProductData($text, $user->id, $transId),
                'lazada' => $this->lazadaCashbackService->getProductData($text, $user->id, $transId),
                default => $this->shopeeCashbackService->getProductData($text, $user->id, $transId),
            };

            if ($response['status'] !== 'success') {
                // Trong group, nếu lấy thông tin sản phẩm thất bại thì im lặng
                if ($isGroup) {
                    return null;
                }
                return $config->not_found_message ?? 'Không lấy được thông tin sản phẩm. Vui lòng thử lại.';
            }

            $productData = $response['data'];

            CashbackClick::create([
                'user_id'           => $user->id,
                'platform'          => $platform,
                'trans_id'          => $transId,
                'product_name'      => Str::limit($productData['name'], 500, '...'),
                'product_image'     => Str::limit($productData['image'] ?? '', 1000, ''),
                'original_price'    => $productData['price'],
                'cashback_amount'   => $productData['cashback_amount'],
                'cashback_rate'     => $productData['cashback_rate'],
                'commission_amount' => $productData['commission_amount'],
                'affiliate_url'     => $productData['affiliate_url'],
            ]);

            $shortLink      = $productData['affiliate_url'];
            $cashbackAmount = number_format($productData['cashback_amount'], 0, ',', '.');
            $productName    = htmlspecialchars(Str::limit($productData['name'], 100));

            $template = $config->cashback_template
                ?? "✅ <b>Link hoàn tiền của bạn:</b>\n\n🛍️ <b>{product_name}</b>\n💰 Hoàn tiền ước tính: <b>{cashback_amount}đ</b>\n🔗 {cashback_link}";

            return str_replace(
                ['{product_name}', '{cashback_amount}', '{cashback_link}', '{platform}'],
                [$productName, $cashbackAmount, $shortLink, strtoupper($platform)],
                $template
            );
        } catch (\Throwable $e) {
            Log::error('TelegramBot processMessage error: ' . $e->getMessage());
            if ($isGroup) {
                return null;
            }
            return $config->not_found_message ?? 'Có lỗi xảy ra. Vui lòng thử lại sau.';
        }
    }

    protected function isProductLink(string $text): bool
    {
        // Danh sách tên miền nằm tập trung ở BotLinkHelper để không bị lệch với Bot Zalo
        // khi hệ thống mở thêm sàn liên kết mới.
        //
        // Tắt nhận diện tên miền bị bẻ dấu chấm: Telegram không chèn thẻ xem trước kiểu Zalo nên
        // người dùng không cần bẻ link, và Bot này cũng không có bước dựng lại link như Bot Zalo —
        // nhận vào chỉ khiến tin nhắn chết ở bước sau với thông báo lỗi sai bản chất.
        return BotLinkHelper::isProductLink($text, includeBrokenVariants: false);
    }
}
