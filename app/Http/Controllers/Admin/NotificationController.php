<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\User;
use App\Models\ActivityLog;
use App\Models\EmailQueue;
use App\Models\Setting;
use App\Models\Coupon;
use App\Models\GiftCode;
use Illuminate\Http\Request;

class NotificationController extends Controller
{


    public function index(Request $request)
    {
        $query = Notification::with('user');

        // Lọc tìm kiếm theo từ khóa (Tiêu đề, nội dung, email hoặc tên người nhận)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('content', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($uq) use ($search) {
                      $uq->where('email', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%");
                  });
            });
        }

        // Lọc theo loại thông báo (Chung - general hoặc Cá nhân - personal)
        if ($request->filled('type') && $request->type !== 'all') {
            $query->where('type', $request->type);
        }

        // Lọc theo trạng thái đã đọc hay chưa
        if ($request->filled('status') && $request->status !== 'all') {
            if ($request->status === 'read') {
                $query->where('is_read', true);
            } elseif ($request->status === 'unread') {
                $query->where('is_read', false);
            }
        }

        $notifications = $query->orderBy('created_at', 'desc')
            ->paginate(15)
            ->withQueryString();

        if ($request->ajax()) {
            return view('admin.notifications.partials.history_list', compact('notifications'));
        }

        $aiEnabled = Setting::getVal('ai_status', '0') === '1';
        $aiCoupons = [];
        $aiGiftCodes = [];
        if ($aiEnabled) {
            $aiCoupons = $this->activeCouponsForAi();
            $aiGiftCodes = $this->activeGiftCodesForAi();
        }

        return view('admin.notifications.index', compact('notifications', 'aiEnabled', 'aiCoupons', 'aiGiftCodes'));
    }

    /**
     * Gửi thông báo cho một người dùng cụ thể hoặc toàn bộ hệ thống.
     * Nếu Admin tick checkbox "Gửi kèm Email", hệ thống sẽ đẩy email vào hàng đợi
     * thay vì gửi trực tiếp để tránh quá tải SMTP server.
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:150',
            'content' => 'required|string',
            'send_to' => 'required|in:all,single',
            'user_email' => 'required_if:send_to,single|nullable|email',
            'send_email' => 'nullable|boolean',
        ], [
            'title.required' => 'Vui lòng nhập tiêu đề thông báo.',
            'content.required' => 'Vui lòng nhập nội dung thông báo.',
            'user_email.required_if' => 'Vui lòng nhập email người dùng nhận thông báo.',
        ]);

        $title = $request->title;
        $content = $request->content;
        // Kiểm tra xem Admin có muốn gửi kèm email hay không
        $sendEmail = $request->boolean('send_email');

        if ($request->send_to === 'all') {
            // 1. Gửi thông báo cho tất cả người dùng
            $emailCount = 0;
            User::chunk(100, function ($users) use ($title, $content, $sendEmail, &$emailCount) {
                foreach ($users as $user) {
                    // Thay thế các biến cá nhân hóa cho từng user
                    $userTitle = $this->replaceNotificationPlaceholders($title, $user);
                    $userContent = $this->replaceNotificationPlaceholders($content, $user);

                    // Tạo thông báo trong hệ thống (hiển thị trên dashboard)
                    Notification::create([
                        'user_id' => $user->id,
                        'title' => $userTitle,
                        'content' => $userContent,
                        'type' => 'general',
                        'is_read' => false,
                    ]);

                    // Nếu Admin chọn gửi kèm email, đẩy vào hàng đợi
                    if ($sendEmail && !empty($user->email)) {
                        $this->queueNotificationEmail($user, $userTitle, $userContent);
                        $emailCount++;
                    }
                }
            });

            $message = 'Đã gửi thông báo đến toàn bộ thành viên thành công!';
            if ($sendEmail) {
                $message .= " ({$emailCount} email đã được đưa vào hàng đợi gửi)";
            }

            ActivityLog::log("Gửi thông báo hệ thống cho toàn bộ thành viên: {$title}" . ($sendEmail ? ' (kèm email)' : ''), auth()->id());
            return back()->with('success', $message);
        } else {
            // 2. Gửi thông báo cho một người dùng duy nhất
            $user = User::where('email', $request->user_email)->first();
            if (!$user) {
                return back()->withErrors(['user_email' => 'Không tìm thấy người dùng có địa chỉ email này.'])->withInput();
            }

            // Thay thế các biến cá nhân hóa cho user này
            $userTitle = $this->replaceNotificationPlaceholders($title, $user);
            $userContent = $this->replaceNotificationPlaceholders($content, $user);

            Notification::create([
                'user_id' => $user->id,
                'title' => $userTitle,
                'content' => $userContent,
                'type' => 'personal',
                'is_read' => false,
            ]);

            // Nếu Admin chọn gửi kèm email cho cá nhân
            if ($sendEmail) {
                $this->queueNotificationEmail($user, $userTitle, $userContent);
            }

            $message = "Đã gửi thông báo tới người dùng {$user->email} thành công!";
            if ($sendEmail) {
                $message .= ' (email đã được đưa vào hàng đợi gửi)';
            }

            ActivityLog::log("Gửi thông báo cá nhân cho user {$user->email}: {$title}" . ($sendEmail ? ' (kèm email)' : ''), auth()->id());
            return back()->with('success', $message);
        }
    }

    /**
     * Thay thế các biến cá nhân hóa trong nội dung thông báo cho từng User.
     * Hỗ trợ các biến: {{name}}, {{email}}, {{balance}}, {{referral_code}}, {{site_name}}
     */
    private function replaceNotificationPlaceholders(string $text, User $user): string
    {
        $siteName = Setting::getVal('site_name', 'Hoàn Tiền Shopee');

        return str_replace(
            ['{{name}}', '{{email}}', '{{balance}}', '{{referral_code}}', '{{site_name}}'],
            [
                $user->name ?? $user->email,
                $user->email,
                number_format($user->balance ?? 0) . 'đ',
                $user->referral_code ?? '',
                $siteName,
            ],
            $text
        );
    }

    /**
     * Đẩy email thông báo vào hàng đợi để cron job xử lý gửi dần.
     * Email sẽ được format dưới dạng HTML đẹp trước khi lưu vào queue.
     *
     * @param User $user Người nhận
     * @param string $title Tiêu đề thông báo
     * @param string $content Nội dung thông báo
     */
    private function queueNotificationEmail(User $user, string $title, string $content): void
    {
        // Lấy tên website từ cấu hình để hiển thị trong email
        $siteName = Setting::getVal('site_name', 'Hoàn Tiền Shopee');

        // Build nội dung HTML email thông báo với thiết kế chuyên nghiệp
        $emailBody = '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #f0f0f0; border-radius: 12px; background-color: #ffffff;">
            <div style="text-align: center; margin-bottom: 20px;">
                <h2 style="color: #ee4d2d; margin: 0;">' . e($siteName) . '</h2>
            </div>
            <p>Xin chào <strong>' . e($user->name) . '</strong>,</p>
            <p>Bạn có một thông báo mới từ hệ thống:</p>
            <div style="background-color: #fff5f1; border-left: 4px solid #ee4d2d; padding: 15px; margin: 20px 0; border-radius: 0 8px 8px 0;">
                <h3 style="color: #ee4d2d; margin: 0 0 8px 0; font-size: 16px;">' . e($title) . '</h3>
                <p style="color: #333333; margin: 0; font-size: 14px; line-height: 1.6;">' . nl2br(e($content)) . '</p>
            </div>
            <hr style="border: 0; border-top: 1px solid #eeeeee; margin: 20px 0;">
            <p style="text-align: center; color: #999999; font-size: 11px; margin: 0;">© ' . date('Y') . ' ' . e($siteName) . '. All rights reserved.</p>
        </div>';

        // Tạo bản ghi trong hàng đợi email
        EmailQueue::create([
            'to_email' => $user->email,
            'to_name' => $user->name,
            'subject' => "[{$siteName}] {$title}",
            'body' => $emailBody,
            'status' => 'pending',
        ]);
    }

    /**
     * API AJAX: Tạo nhanh nội dung thông báo (tiêu đề + nội dung văn bản) bằng AI.
     * Trả về JSON { success: bool, title?: string, content?: string, message?: string }.
     */
    public function generateAi(Request $request)
    {
        // Chặn gọi API thực tế khi đang ở chế độ demo để tránh lạm dụng tài nguyên
        if (config('app.demo', false)) {
            return response()->json([
                'success' => false,
                'message' => __('Tính năng tạo nội dung bằng AI bị vô hiệu hóa trong phiên bản thử nghiệm (Demo).'),
            ], 422);
        }

        // Kiểm tra dịch vụ AI đã được bật trong cài đặt hệ thống chưa
        if (Setting::getVal('ai_status', '0') !== '1') {
            return response()->json([
                'success' => false,
                'message' => __('Dịch vụ AI hiện đang tắt. Vui lòng kích hoạt trong Cài đặt > AI.'),
            ], 422);
        }

        $validated = $request->validate([
            'prompt'       => 'required|string|max:2000',
            'tone'         => 'nullable|string|max:50',
            'with_subject' => 'nullable|boolean',
            'coupon_ids'   => 'nullable|array',
            'coupon_ids.*' => 'integer',
            'giftcode_ids'   => 'nullable|array',
            'giftcode_ids.*' => 'integer',
        ], [
            'prompt.required' => __('Vui lòng mô tả nội dung thông báo bạn muốn tạo.'),
        ]);

        $siteName    = Setting::getVal('site_name', 'Hoàn Tiền Shopee');
        $tone        = $validated['tone'] ?? 'than-thien';
        $withSubject = $request->boolean('with_subject');

        // Xây dựng khối mã giảm giá & giftcode (nếu Admin chọn chia sẻ) để đưa vào ngữ cảnh cho AI
        $couponBlock   = $this->buildCouponPromptBlock($validated['coupon_ids'] ?? []);
        $giftCodeBlock = $this->buildGiftCodePromptBlock($validated['giftcode_ids'] ?? []);

        // Bản đồ giọng điệu sang mô tả tiếng Việt để hướng dẫn AI
        $toneMap = [
            'than-thien'   => 'thân thiện, gần gũi',
            'chuyen-nghiep'=> 'chuyên nghiệp, trang trọng',
            'khan-cap'     => 'khẩn cấp, thúc đẩy hành động ngay',
            'hao-hung'     => 'hào hứng, nhiệt huyết, nhiều cảm xúc',
        ];
        $toneText = $toneMap[$tone] ?? 'thân thiện, gần gũi';

        // System prompt: yêu cầu AI trả về đúng định dạng JSON để dễ phân tích
        $systemPrompt = <<<SYS
Bạn là chuyên gia truyền thông và chăm sóc khách hàng cho nền tảng hoàn tiền mua sắm Shopee tên là "{$siteName}".
Nhiệm vụ: viết một tin nhắn thông báo hệ thống ngắn gọn, dễ hiểu bằng TIẾNG VIỆT theo yêu cầu của người dùng.

QUY TẮC BẮT BUỘC:
- Chỉ trả về DUY NHẤT một object JSON hợp lệ, KHÔNG kèm giải thích, KHÔNG bọc trong dấu ```.
- Cấu trúc JSON: {"title": "tiêu đề thông báo ngắn gọn", "content": "nội dung chi tiết thông báo, dạng văn bản thường (có thể dùng ngắt dòng \\n)"}.
- Nội dung thông báo hệ thống cần ngắn gọn, trực diện, dễ đọc trên màn hình điện thoại/máy tính (không dùng thẻ HTML phức tạp, chỉ dùng chữ thường, các biểu tượng cảm xúc nếu cần thiết).
- Có thể dùng các biến cá nhân hóa (giữ NGUYÊN văn dạng chuỗi này, không thay đổi): {{name}}, {{email}}, {{balance}}, {{referral_code}}, {{site_name}}.
- Giọng điệu: {$toneText}.
SYS;

        // Nếu có mã giảm giá được chọn, bổ sung yêu cầu chia sẻ mã vào system prompt.
        if ($couponBlock !== '') {
            $systemPrompt .= <<<CP


NHIỆM VỤ CHIA SẺ MÃ GIẢM GIÁ (ƯU TIÊN CAO):
- Đưa các mã giảm giá Shopee dưới đây vào nội dung thông báo. TUYỆT ĐỐI giữ nguyên mã (code) và đường link, KHÔNG bịa thêm mã, KHÔNG đổi link:
{$couponBlock}
- Trình bày mỗi mã ngắn gọn, rõ ràng: ghi rõ MÃ CODE, mức ưu đãi và kèm link sử dụng mã ngay cạnh mã đó.
- Khuyên người dùng copy mã và click vào link để đi săn deal mua sắm hoàn tiền.
CP;
        }

        // Nếu có giftcode được chọn, bổ sung yêu cầu chia sẻ mã quà tặng (cộng tiền vào ví)
        if ($giftCodeBlock !== '') {
            $giftRedeemUrl = route('giftcode.index');
            $systemPrompt .= <<<GC


NHIỆM VỤ CHIA SẺ GIFTCODE (MÃ QUÀ TẶNG CỘNG TIỀN VÀO VÍ):
- Đây là mã quà tặng: người dùng nhập mã trên website để nhận tiền thưởng thẳng vào ví.
- Đưa các giftcode dưới đây vào nội dung thông báo. TUYỆT ĐỐI giữ nguyên mã (code), KHÔNG bịa thêm mã:
{$giftCodeBlock}
- Hướng dẫn người dùng copy mã, đăng nhập website và truy cập đường link để đổi thưởng: {$giftRedeemUrl}
GC;
        }

        $shareHint = ($couponBlock !== '' || $giftCodeBlock !== '')
            ? "\n(Hãy lồng ghép các mã giảm giá và giftcode đã cung cấp vào nội dung thông báo.)"
            : '';

        $userPrompt = $withSubject
            ? "Yêu cầu nội dung thông báo: {$validated['prompt']}{$shareHint}"
            : "Yêu cầu nội dung thông báo: {$validated['prompt']}\n(Vẫn trả về trường title gợi ý, nhưng tập trung vào content).{$shareHint}";

        try {
            $aiService = app(\App\Services\AIService::class);
            $response = $aiService->chat([
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user',   'content' => $userPrompt],
            ], [
                'max_tokens'  => 2000,
                'temperature' => 0.8,
            ]);

            // AIService trả về chuỗi lỗi đã chuẩn hóa thay vì ném exception khi gặp sự cố
            if (str_contains($response, 'Lỗi khi kết nối với AI API') || str_contains($response, 'Dịch vụ AI hiện đang')) {
                return response()->json(['success' => false, 'message' => $response], 422);
            }

            // Loại bỏ rào ``` nếu AI lỡ bọc kết quả trong code fence
            $clean = trim($response);
            $clean = preg_replace('/^```(?:json)?\s*/i', '', $clean);
            $clean = preg_replace('/\s*```$/', '', $clean);

            // Cắt lấy đoạn JSON nằm giữa dấu { đầu tiên và } cuối cùng
            $start = strpos($clean, '{');
            $end   = strrpos($clean, '}');
            $title   = null;
            $content = null;

            if ($start !== false && $end !== false && $end > $start) {
                $json = substr($clean, $start, $end - $start + 1);
                $parsed = json_decode($json, true);
                if (is_array($parsed)) {
                    $title   = $parsed['title'] ?? null;
                    $content = $parsed['content'] ?? null;
                }
            }

            // Nếu không phân tích được JSON, coi toàn bộ phản hồi là content thông báo
            if (empty($content)) {
                $content = $clean;
            }

            ActivityLog::log(__('Tạo nội dung thông báo bằng AI'), auth()->id());

            return response()->json([
                'success' => true,
                'title'   => $title,
                'content' => $content,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('Lỗi khi tạo nội dung AI: :error', ['error' => $e->getMessage()]),
            ], 422);
        }
    }

    /**
     * Lấy danh sách mã giảm giá Shopee còn hiệu lực để hiển thị cho Admin chọn
     * chia sẻ trong modal tạo nội dung bằng AI. Trả về mảng gọn nhẹ cho frontend.
     */
    private function activeCouponsForAi()
    {
        return Coupon::where('platform', 'shopee')
            ->where(function ($q) {
                $q->whereNull('expired_at')->orWhere('expired_at', '>=', now());
            })
            ->orderByDesc('clicks')
            ->limit(50)
            ->get()
            ->map(fn ($c) => [
                'id'                  => $c->id,
                'code'                => $c->code,
                'title'               => $c->title,
                'discount_percentage' => $c->discount_percentage,
                'discount_amount'     => $c->discount_amount,
                'min_spend'           => $c->min_spend,
                'expired_at'          => $c->expired_at?->format('d/m/Y'),
            ])
            ->values();
    }

    /**
     * Dựng khối văn bản mô tả các mã giảm giá được chọn để đưa vào prompt cho AI.
     * Chỉ lấy mã Shopee còn hiệu lực để tránh chia sẻ mã đã hết hạn.
     *
     * @param  array<int>  $couponIds
     */
    private function buildCouponPromptBlock(array $couponIds): string
    {
        $couponIds = array_filter(array_map('intval', $couponIds));
        if (empty($couponIds)) {
            return '';
        }

        $coupons = Coupon::whereIn('id', $couponIds)
            ->where('platform', 'shopee')
            ->where(function ($q) {
                $q->whereNull('expired_at')->orWhere('expired_at', '>=', now());
            })
            ->get();

        if ($coupons->isEmpty()) {
            return '';
        }

        $lines = [];
        foreach ($coupons as $c) {
            $parts = ["Mã: {$c->code}"];
            if ($c->title) {
                $parts[] = "Ưu đãi: {$c->title}";
            }
            if ($c->min_spend) {
                $parts[] = 'Đơn tối thiểu: ' . number_format($c->min_spend) . 'đ';
            }
            if ($c->expired_at) {
                $parts[] = 'HSD: ' . $c->expired_at->format('d/m/Y');
            }
            $parts[] = 'Link dùng mã: ' . ($c->redirect_link ?: route('coupons.index'));
            $lines[] = '- ' . implode(' | ', $parts);
        }

        return implode("\n", $lines);
    }

    /**
     * Lấy danh sách Giftcode còn sẵn sàng đổi (đang bật, đã bắt đầu, chưa hết hạn,
     * chưa hết lượt) để Admin chọn chia sẻ trong modal tạo nội dung bằng AI.
     */
    private function activeGiftCodesForAi()
    {
        return $this->redeemableGiftCodesQuery()
            ->orderByDesc('id')
            ->limit(50)
            ->get()
            ->map(fn ($g) => [
                'id'           => $g->id,
                'code'         => $g->code,
                'title'        => $g->title,
                'reward_label' => $g->reward_label,
                'expires_at'   => $g->expires_at?->format('d/m/Y'),
            ])
            ->values();
    }

    /**
     * Dựng khối văn bản mô tả các Giftcode được chọn để đưa vào prompt cho AI.
     * Chỉ lấy mã còn sẵn sàng đổi để tránh chia sẻ mã hết hạn/hết lượt.
     *
     * @param  array<int>  $giftCodeIds
     */
    private function buildGiftCodePromptBlock(array $giftCodeIds): string
    {
        $giftCodeIds = array_filter(array_map('intval', $giftCodeIds));
        if (empty($giftCodeIds)) {
            return '';
        }

        $giftCodes = $this->redeemableGiftCodesQuery()
            ->whereIn('id', $giftCodeIds)
            ->get();

        if ($giftCodes->isEmpty()) {
            return '';
        }

        $lines = [];
        foreach ($giftCodes as $g) {
            $parts = ["Mã: {$g->code}"];
            if ($g->title) {
                $parts[] = "Tên: {$g->title}";
            }
            $parts[] = "Thưởng: {$g->reward_label}";
            if ($g->expires_at) {
                $parts[] = 'HSD: ' . $g->expires_at->format('d/m/Y');
            }
            $lines[] = '- ' . implode(' | ', $parts);
        }

        return implode("\n", $lines);
    }

    /**
     * Query dùng chung: các Giftcode đang sẵn sàng cho người dùng đổi
     * (bật, đã bắt đầu, chưa hết hạn, chưa hết tổng lượt).
     */
    private function redeemableGiftCodesQuery()
    {
        return GiftCode::where('status', true)
            ->where(fn ($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>=', now()))
            ->where(fn ($q) => $q->whereNull('max_uses')->orWhereColumn('used_count', '<', 'max_uses'));
    }
}
