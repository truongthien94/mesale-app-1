<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BotConfig;
use App\Models\BotMessage;
use App\Services\ZaloBotService;
use App\Services\TelegramBotService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BotController extends Controller
{
    /**
     * Hiển thị trang quản lý bot với cấu hình và danh sách tin nhắn.
     */
    public function index(Request $request)
    {
        $tab = $request->input('tab', 'zalo');
        $botType = in_array($tab, ['zalo', 'telegram', 'messages']) ? $tab : 'zalo';

        $zaloConfig = BotConfig::forType('zalo');
        $telegramConfig = BotConfig::forType('telegram');

        $messages = BotMessage::with('user')
            ->when($botType === 'messages', function ($q) use ($request) {
                $filterBot = $request->input('bot', '');
                if (in_array($filterBot, ['zalo', 'telegram'])) {
                    $q->where('bot_type', $filterBot);
                }
            })
            ->orderByDesc('created_at')
            ->paginate(30)
            ->withQueryString();

        $botTypeForMessages = $request->input('bot', '');

        return view('admin.bots.index', compact(
            'tab',
            'zaloConfig',
            'telegramConfig',
            'messages',
            'botTypeForMessages'
        ));
    }

    /**
     * Lưu cấu hình cho Zalo Bot bao gồm cả ảnh mã QR Code.
     */
    public function saveZaloConfig(Request $request)
    {
        if (config('app.demo')) {
            return back()->with('error', __('Chức năng này bị vô hiệu hoá trong chế độ Demo.'));
        }

        $request->validate([
            'bot_token'              => 'nullable|string|max:500',
            'bot_username'           => 'nullable|string|max:100',
            'welcome_message'        => 'nullable|string|max:1000',
            'cashback_template'      => 'nullable|string|max:2000',
            'not_found_message'      => 'nullable|string|max:500',
            'login_required_message' => 'nullable|string|max:2000',
            'qr_code'                => 'nullable|string|max:500', // Đường dẫn hình ảnh QR Code của Zalo Bot
        ]);

        $config = BotConfig::forType('zalo');
        $extra = $config->extra ?? [];
        $extra['qr_code'] = $request->input('qr_code');

        $config->update([
            'is_enabled'             => $request->boolean('is_enabled'),
            'bot_token'              => $request->input('bot_token'),
            'bot_username'           => $request->input('bot_username'),
            'welcome_message'        => $request->input('welcome_message'),
            'cashback_template'      => $request->input('cashback_template'),
            'not_found_message'      => $request->input('not_found_message'),
            'login_required_message' => $request->input('login_required_message'),
            'extra'                  => $extra, // Lưu thông tin QR Code vào cột extra
        ]);

        return redirect()->route('admin.bots.index', ['tab' => 'zalo'])
            ->with('success', __('Đã lưu cấu hình Zalo Bot thành công!'));
    }

    /**
     * Lưu cấu hình cho Telegram Bot.
     */
    public function saveTelegramConfig(Request $request)
    {
        if (config('app.demo')) {
            return back()->with('error', __('Chức năng này bị vô hiệu hoá trong chế độ Demo.'));
        }

        $request->validate([
            'bot_token'              => 'nullable|string|max:500',
            'bot_username'           => 'nullable|string|max:100',
            'welcome_message'        => 'nullable|string|max:1000',
            'cashback_template'      => 'nullable|string|max:2000',
            'not_found_message'      => 'nullable|string|max:500',
            'login_required_message' => 'nullable|string|max:2000',
        ]);

        $config = BotConfig::forType('telegram');
        $config->update([
            'is_enabled'             => $request->boolean('is_enabled'),
            'bot_token'              => $request->input('bot_token'),
            'bot_username'           => $request->input('bot_username'),
            'welcome_message'        => $request->input('welcome_message'),
            'cashback_template'      => $request->input('cashback_template'),
            'not_found_message'      => $request->input('not_found_message'),
            'login_required_message' => $request->input('login_required_message'),
        ]);

        return redirect()->route('admin.bots.index', ['tab' => 'telegram'])
            ->with('success', __('Đã lưu cấu hình Telegram Bot thành công!'));
    }

    /**
     * Đặt webhook cho Zalo Bot.
     */
    public function setZaloWebhook(Request $request)
    {
        if (config('app.demo')) {
            return response()->json(['success' => false, 'message' => __('Chức năng bị khoá ở chế độ Demo.')]);
        }

        $config = BotConfig::forType('zalo');
        if (empty($config->bot_token)) {
            return response()->json(['success' => false, 'message' => __('Vui lòng lưu Bot Token trước.')]);
        }

        // Generate hoặc tái sử dụng secret token (64 ký tự hex an toàn)
        $secret = $config->webhook_secret ?: Str::random(32);
        $config->update(['webhook_secret' => $secret]);

        // Secret nằm trong path URL: /webhook/zalo-bot/{secret}
        $webhookUrl = url('/webhook/zalo-bot/' . $secret);
        $service = new ZaloBotService($config->bot_token);
        $result = $service->setWebhook($webhookUrl, $secret);

        if (!empty($result['ok'])) {
            return response()->json(['success' => true, 'message' => __('Đã đặt webhook Zalo Bot thành công!'), 'url' => $webhookUrl]);
        }

        $error = $result['description'] ?? $result['error'] ?? __('Lỗi không xác định.');
        return response()->json(['success' => false, 'message' => __('Lỗi: ') . $error]);
    }

    /**
     * Đặt webhook cho Telegram Bot.
     */
    public function setTelegramWebhook(Request $request)
    {
        if (config('app.demo')) {
            return response()->json(['success' => false, 'message' => __('Chức năng bị khoá ở chế độ Demo.')]);
        }

        $config = BotConfig::forType('telegram');
        if (empty($config->bot_token)) {
            return response()->json(['success' => false, 'message' => __('Vui lòng lưu Bot Token trước.')]);
        }

        // Generate hoặc tái sử dụng secret token — nhúng trong path URL
        $secret = $config->webhook_secret ?: Str::random(32);
        $config->update(['webhook_secret' => $secret]);

        // Secret nằm trong path: /webhook/telegram-bot/{secret}
        // Telegram cũng nhận secret_token trong header để double-verify
        $webhookUrl = url('/webhook/telegram-bot/' . $secret);
        $service = new TelegramBotService($config->bot_token);
        // drop_pending_updates=true: xoá hàng đợi retry tích luỹ trước đó
        $result = $service->setWebhook($webhookUrl, $secret, dropPendingUpdates: true);

        if (!empty($result['ok'])) {
            return response()->json(['success' => true, 'message' => __('Đã đặt webhook Telegram Bot thành công!'), 'url' => $webhookUrl]);
        }

        $error = $result['description'] ?? $result['error'] ?? __('Lỗi không xác định.');
        return response()->json(['success' => false, 'message' => __('Lỗi: ') . $error]);
    }

    /**
     * Kiểm tra kết nối Zalo Bot.
     */
    public function testZaloBot(Request $request)
    {
        $config = BotConfig::forType('zalo');
        if (empty($config->bot_token)) {
            return response()->json(['success' => false, 'message' => __('Chưa có Bot Token.')]);
        }

        $service = new ZaloBotService($config->bot_token);
        $result = $service->getMe();

        if (!empty($result['ok'])) {
            $botName = $result['result']['first_name'] ?? $result['result']['username'] ?? 'Bot';
            return response()->json(['success' => true, 'message' => __('Kết nối thành công! Bot: ') . $botName, 'data' => $result['result']]);
        }

        $error = $result['description'] ?? $result['error'] ?? __('Lỗi không xác định.');
        return response()->json(['success' => false, 'message' => __('Kết nối thất bại: ') . $error]);
    }

    /**
     * Kiểm tra kết nối Telegram Bot.
     */
    public function testTelegramBot(Request $request)
    {
        $config = BotConfig::forType('telegram');
        if (empty($config->bot_token)) {
            return response()->json(['success' => false, 'message' => __('Chưa có Bot Token.')]);
        }

        $service = new TelegramBotService($config->bot_token);
        $result = $service->getMe();

        if (!empty($result['ok'])) {
            $botName = $result['result']['first_name'] ?? $result['result']['username'] ?? 'Bot';
            return response()->json(['success' => true, 'message' => __('Kết nối thành công! Bot: @') . ($result['result']['username'] ?? $botName), 'data' => $result['result']]);
        }

        $error = $result['description'] ?? $result['error'] ?? __('Lỗi không xác định.');
        return response()->json(['success' => false, 'message' => __('Kết nối thất bại: ') . $error]);
    }

    /**
     * Xoá tất cả tin nhắn của một loại bot.
     */
    public function clearMessages(Request $request)
    {
        if (config('app.demo')) {
            return response()->json(['success' => false, 'message' => __('Chức năng bị khoá ở chế độ Demo.')]);
        }

        $botType = $request->input('bot_type');
        if (!in_array($botType, ['zalo', 'telegram'])) {
            return response()->json(['success' => false, 'message' => __('Loại bot không hợp lệ.')]);
        }

        BotMessage::where('bot_type', $botType)->delete();

        return response()->json(['success' => true, 'message' => __('Đã xoá toàn bộ lịch sử tin nhắn.')]);
    }
}
