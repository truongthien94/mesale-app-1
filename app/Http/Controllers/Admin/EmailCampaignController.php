<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Coupon;
use App\Models\EmailCampaign;
use App\Models\GiftCode;
use App\Models\Setting;
use App\Models\Task;
use Illuminate\Http\Request;

/**
 * Controller quản lý Email Campaign (Chiến dịch email marketing).
 *
 * Hỗ trợ: CRUD, xem trước, đếm người nhận, gửi ngay/lên lịch, nhân bản và xem thống kê.
 */
class EmailCampaignController extends Controller
{
    /**
     * Hiển thị danh sách các chiến dịch email.
     */
    public function index(Request $request)
    {
        $query = EmailCampaign::with('creator')->latest();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('subject', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        $campaigns = $query->paginate(15)->withQueryString();

        // Thống kê tổng quan cho cards phía trên
        $stats = [
            'total'     => EmailCampaign::count(),
            'sent'      => EmailCampaign::where('status', 'sent')->count(),
            'draft'     => EmailCampaign::where('status', 'draft')->count(),
            'scheduled' => EmailCampaign::where('status', 'scheduled')->count(),
        ];

        return view('admin.email-campaigns.index', compact('campaigns', 'stats'));
    }

    /**
     * Hiển thị form tạo chiến dịch mới.
     */
    public function create()
    {
        $audiences = EmailCampaign::AUDIENCES;
        $siteName  = Setting::getVal('site_name', 'Hoàn Tiền Shopee');
        $siteEmail = Setting::getVal('mail_from_address', '');
        $aiEnabled = Setting::getVal('ai_status', '0') === '1';
        $aiCoupons = $this->activeCouponsForAi();
        $aiGiftCodes = $this->activeGiftCodesForAi();
        $aiTasks = $this->activeTasksForAi();
        return view('admin.email-campaigns.create', compact('audiences', 'siteName', 'siteEmail', 'aiEnabled', 'aiCoupons', 'aiGiftCodes', 'aiTasks'));
    }

    /**
     * Lưu chiến dịch mới vào database.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'             => 'required|string|max:255',
            'subject'          => 'required|string|max:255',
            'body'             => 'required|string',
            'from_name'        => 'nullable|string|max:100',
            'from_email'       => 'nullable|email|max:150',
            'target_audience'  => 'required|in:' . implode(',', array_keys(EmailCampaign::AUDIENCES)),
            'min_balance'      => 'nullable|integer|min:0',
            'registered_after' => 'nullable|date',
            'schedule_type'    => 'required|in:now,scheduled',
            'scheduled_at'     => 'required_if:schedule_type,scheduled|nullable|date|after:now',
        ], [
            'name.required'           => 'Vui lòng nhập tên chiến dịch.',
            'subject.required'        => 'Vui lòng nhập tiêu đề email.',
            'body.required'           => 'Vui lòng nhập nội dung email.',
            'target_audience.in'      => 'Đối tượng nhắm mục tiêu không hợp lệ.',
            'scheduled_at.required_if'=> 'Vui lòng chọn thời gian gửi khi lên lịch.',
            'scheduled_at.after'      => 'Thời gian lên lịch phải là thời điểm trong tương lai.',
        ]);

        $targetFilter = [];
        if ($request->filled('min_balance')) {
            $targetFilter['min_balance'] = (int) $request->min_balance;
        }
        if ($request->filled('registered_after')) {
            $targetFilter['registered_after'] = $request->registered_after;
        }

        $status      = $request->schedule_type === 'now' ? 'draft' : 'scheduled';
        $scheduledAt = $request->schedule_type === 'scheduled' ? $request->scheduled_at : null;

        $campaign = EmailCampaign::create([
            'name'            => $validated['name'],
            'subject'         => $validated['subject'],
            'body'            => $validated['body'],
            'from_name'       => $request->from_name ?: Setting::getVal('site_name', 'Hoàn Tiền Shopee'),
            'from_email'      => $request->from_email ?: Setting::getVal('mail_from_address', ''),
            'target_audience' => $validated['target_audience'],
            'target_filter'   => !empty($targetFilter) ? $targetFilter : null,
            'status'          => $status,
            'scheduled_at'    => $scheduledAt,
            'created_by'      => auth()->id(),
        ]);

        ActivityLog::log("Tạo chiến dịch email mới: {$campaign->name}", auth()->id());

        // Nếu Admin muốn gửi ngay thì gọi thẳng logic send (không redirect GET sang route POST)
        if ($request->schedule_type === 'now' && $request->boolean('send_now')) {
            return $this->send($campaign);
        }

        return redirect()->route('admin.email_campaigns.show', $campaign)
            ->with('success', __('Tạo chiến dịch email thành công!'));
    }

    /**
     * Hiển thị chi tiết & thống kê một chiến dịch.
     */
    public function show(EmailCampaign $emailCampaign)
    {
        // Làm mới số liệu thống kê từ email_queues
        if (in_array($emailCampaign->status, ['sending', 'sent'])) {
            $emailCampaign->refreshStats();
            $emailCampaign->refresh();
        }

        $recentQueue = $emailCampaign->emailQueues()
            ->orderByDesc('id')
            ->take(50)
            ->get();

        $pendingCount = $emailCampaign->emailQueues()->where('status', 'pending')->count();
        $recipientCount = $emailCampaign->countRecipients();

        return view('admin.email-campaigns.show', compact(
            'emailCampaign',
            'recentQueue',
            'pendingCount',
            'recipientCount'
        ));
    }

    /**
     * Lấy nhật ký hàng đợi gửi email có phân trang và tìm kiếm (gọi qua AJAX).
     */
    public function queueLogs(Request $request, EmailCampaign $emailCampaign)
    {
        $query = $emailCampaign->emailQueues()->orderByDesc('id');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('to_email', 'like', "%{$search}%")
                  ->orWhere('to_name', 'like', "%{$search}%");
            });
        }

        $logs = $query->paginate(15);

        // Biến đổi dữ liệu sang định dạng gọn nhẹ cho frontend
        $items = collect($logs->items())->map(fn ($qItem) => [
            'to_name'       => $qItem->to_name,
            'to_email'      => $qItem->to_email,
            'status'        => $qItem->status,
            'status_label'  => match($qItem->status) {
                'sent'    => __('Đã gửi'),
                'failed'  => __('Thất bại'),
                default   => __('Chờ gửi'),
            },
            'attempts'      => $qItem->attempts,
            'error_message' => $qItem->error_message,
            'sent_at_label' => $qItem->sent_at ? $qItem->sent_at->format('d/m H:i') : '—',
        ]);

        return response()->json([
            'success'      => true,
            'data'         => $items,
            'current_page' => $logs->currentPage(),
            'last_page'    => $logs->lastPage(),
            'total'        => $logs->total(),
            'per_page'     => $logs->perPage(),
        ]);
    }

    /**
     * Hiển thị form chỉnh sửa chiến dịch (chỉ cho phép khi còn ở trạng thái draft/scheduled).
     */
    public function edit(EmailCampaign $emailCampaign)
    {
        if (!in_array($emailCampaign->status, ['draft', 'scheduled'])) {
            return back()->with('error', __('Không thể chỉnh sửa chiến dịch đang gửi hoặc đã hoàn thành.'));
        }

        $audiences = EmailCampaign::AUDIENCES;
        $siteName  = Setting::getVal('site_name', 'Hoàn Tiền Shopee');
        $siteEmail = Setting::getVal('mail_from_address', '');
        return view('admin.email-campaigns.edit', compact('emailCampaign', 'audiences', 'siteName', 'siteEmail'));
    }

    /**
     * Cập nhật thông tin chiến dịch.
     */
    public function update(Request $request, EmailCampaign $emailCampaign)
    {
        if (!in_array($emailCampaign->status, ['draft', 'scheduled'])) {
            return back()->with('error', __('Không thể chỉnh sửa chiến dịch đang gửi hoặc đã hoàn thành.'));
        }

        $validated = $request->validate([
            'name'             => 'required|string|max:255',
            'subject'          => 'required|string|max:255',
            'body'             => 'required|string',
            'from_name'        => 'nullable|string|max:100',
            'from_email'       => 'nullable|email|max:150',
            'target_audience'  => 'required|in:' . implode(',', array_keys(EmailCampaign::AUDIENCES)),
            'min_balance'      => 'nullable|integer|min:0',
            'registered_after' => 'nullable|date',
            'schedule_type'    => 'required|in:now,scheduled',
            'scheduled_at'     => 'required_if:schedule_type,scheduled|nullable|date|after:now',
        ]);

        $targetFilter = [];
        if ($request->filled('min_balance')) {
            $targetFilter['min_balance'] = (int) $request->min_balance;
        }
        if ($request->filled('registered_after')) {
            $targetFilter['registered_after'] = $request->registered_after;
        }

        $status      = $request->schedule_type === 'now' ? 'draft' : 'scheduled';
        $scheduledAt = $request->schedule_type === 'scheduled' ? $request->scheduled_at : null;

        $emailCampaign->update([
            'name'            => $validated['name'],
            'subject'         => $validated['subject'],
            'body'            => $validated['body'],
            'from_name'       => $request->from_name ?: Setting::getVal('site_name', 'Hoàn Tiền Shopee'),
            'from_email'      => $request->from_email ?: Setting::getVal('mail_from_address', ''),
            'target_audience' => $validated['target_audience'],
            'target_filter'   => !empty($targetFilter) ? $targetFilter : null,
            'status'          => $status,
            'scheduled_at'    => $scheduledAt,
        ]);

        ActivityLog::log("Cập nhật chiến dịch email: {$emailCampaign->name}", auth()->id());

        return redirect()->route('admin.email_campaigns.show', $emailCampaign)
            ->with('success', __('Cập nhật chiến dịch thành công!'));
    }

    /**
     * Xoá chiến dịch (chỉ khi còn draft/scheduled/cancelled).
     * Các email đã được đưa vào hàng đợi sẽ được xoá theo cascade.
     */
    public function destroy(EmailCampaign $emailCampaign)
    {
        if (in_array($emailCampaign->status, ['sending'])) {
            return back()->with('error', __('Không thể xoá chiến dịch đang trong quá trình gửi.'));
        }

        $name = $emailCampaign->name;
        // Xoá các email trong hàng đợi chưa gửi của chiến dịch này
        $emailCampaign->emailQueues()->where('status', 'pending')->delete();
        $emailCampaign->delete();

        ActivityLog::log("Xoá chiến dịch email: {$name}", auth()->id());

        return redirect()->route('admin.email_campaigns.index')
            ->with('success', __('Đã xoá chiến dịch email thành công.'));
    }

    /**
     * Gửi chiến dịch ngay lập tức: đưa tất cả email vào hàng đợi.
     * Để tránh tạo trùng, chỉ cho phép gửi 1 lần khi status là draft hoặc scheduled.
     */
    public function send(EmailCampaign $emailCampaign)
    {
        if (!in_array($emailCampaign->status, ['draft', 'scheduled'])) {
            return back()->with('error', __('Chiến dịch này không thể gửi (đang gửi hoặc đã hoàn thành).'));
        }

        // Đẩy toàn bộ email vào hàng đợi (logic dùng chung với Cron lên lịch)
        $count = $emailCampaign->dispatchToQueue();

        if ($count === 0) {
            return back()->with('error', __('Không tìm thấy thành viên nào phù hợp với tiêu chí đã chọn.'));
        }

        ActivityLog::log(
            "Khởi động gửi chiến dịch email: {$emailCampaign->name} ({$count} người nhận)",
            auth()->id()
        );

        return redirect()->route('admin.email_campaigns.show', $emailCampaign)
            ->with('success', __(
                'Đã đưa :count email vào hàng đợi! Cron Job sẽ xử lý gửi dần (~10 email/phút).',
                ['count' => $count]
            ));
    }

    /**
     * Huỷ chiến dịch đã lên lịch, xoá email pending trong hàng đợi.
     */
    public function cancel(EmailCampaign $emailCampaign)
    {
        if (!in_array($emailCampaign->status, ['draft', 'scheduled'])) {
            return back()->with('error', __('Chỉ có thể huỷ chiến dịch ở trạng thái Nháp hoặc Đã lên lịch.'));
        }

        $emailCampaign->emailQueues()->where('status', 'pending')->delete();
        $emailCampaign->update(['status' => 'cancelled']);

        ActivityLog::log("Huỷ chiến dịch email: {$emailCampaign->name}", auth()->id());

        return back()->with('success', __('Đã huỷ chiến dịch thành công.'));
    }

    /**
     * Nhân bản chiến dịch hiện có sang một chiến dịch nháp mới.
     */
    public function duplicate(EmailCampaign $emailCampaign)
    {
        $newName = 'Copy - ' . $emailCampaign->name;

        $copy = EmailCampaign::create([
            'name'            => $newName,
            'subject'         => $emailCampaign->subject,
            'body'            => $emailCampaign->body,
            'from_name'       => $emailCampaign->from_name,
            'from_email'      => $emailCampaign->from_email,
            'target_audience' => $emailCampaign->target_audience,
            'target_filter'   => $emailCampaign->target_filter,
            'status'          => 'draft',
            'created_by'      => auth()->id(),
        ]);

        ActivityLog::log("Nhân bản chiến dịch email: {$emailCampaign->name} → {$newName}", auth()->id());

        return redirect()->route('admin.email_campaigns.edit', $copy)
            ->with('success', __('Đã nhân bản thành công! Bạn đang chỉnh sửa bản sao.'));
    }

    /**
     * API AJAX: Đếm số người nhận theo audience + filter đang chọn trên form.
     * Trả về JSON { count: int }.
     */
    public function countRecipients(Request $request)
    {
        $request->validate([
            'target_audience'  => 'required|in:' . implode(',', array_keys(EmailCampaign::AUDIENCES)),
            'min_balance'      => 'nullable|integer|min:0',
            'registered_after' => 'nullable|date',
        ]);

        // Tạo instance tạm để tái sử dụng logic buildRecipientQuery
        $temp = new EmailCampaign();
        $temp->target_audience = $request->target_audience;
        $temp->target_filter   = array_filter([
            'min_balance'      => $request->filled('min_balance') ? (int) $request->min_balance : null,
            'registered_after' => $request->filled('registered_after') ? $request->registered_after : null,
        ]);

        $count = $temp->buildRecipientQuery()->count();

        return response()->json(['count' => $count]);
    }

    /**
     * Preview email được render cho một user mẫu (user đầu tiên phù hợp hoặc chính Admin).
     */
    public function preview(Request $request)
    {
        $request->validate([
            'subject' => 'nullable|string|max:255',
            'body'    => 'required|string',
        ]);

        // Dùng chính tài khoản Admin để xem trước
        $user = auth()->user();
        $siteName = Setting::getVal('site_name', 'Hoàn Tiền Shopee');

        // Thay thế biến trong body
        $body = str_replace(
            ['{{name}}', '{{email}}', '{{balance}}', '{{referral_code}}', '{{site_name}}'],
            [
                e($user->name),
                e($user->email),
                number_format($user->balance ?? 0) . 'đ',
                e($user->referral_code ?? 'DEMO123'),
                e($siteName),
            ],
            $request->body
        );

        return response()->json([
            'subject' => $request->subject,
            'body'    => $body,
        ]);
    }

    /**
     * API AJAX: Tạo nhanh nội dung email (tiêu đề + body HTML) bằng AI.
     * Trả về JSON { success: bool, subject?: string, body?: string, message?: string }.
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
            'prompt'        => 'required|string|max:2000',
            'tone'          => 'nullable|string|max:50',
            'with_subject'  => 'nullable|boolean',
            'coupon_ids'     => 'nullable|array|max:20',
            'coupon_ids.*'   => 'integer',
            'giftcode_ids'   => 'nullable|array|max:20',
            'giftcode_ids.*' => 'integer',
            'task_ids'       => 'nullable|array|max:20',
            'task_ids.*'     => 'integer',
        ], [
            'prompt.required' => __('Vui lòng mô tả nội dung email bạn muốn tạo.'),
        ]);

        $siteName    = Setting::getVal('site_name', 'Hoàn Tiền Shopee');
        $themeColor  = Setting::getVal('theme_color', '#ee4d2d');
        $tone        = $validated['tone'] ?? 'than-thien';
        $withSubject = $request->boolean('with_subject');

        // Xây dựng khối mã giảm giá, giftcode, nhiệm vụ (nếu Admin chọn chia sẻ) để đưa vào ngữ cảnh cho AI
        $couponBlock   = $this->buildCouponPromptBlock($validated['coupon_ids'] ?? []);
        $giftCodeBlock = $this->buildGiftCodePromptBlock($validated['giftcode_ids'] ?? []);
        $taskBlock     = $this->buildTaskPromptBlock($validated['task_ids'] ?? []);

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
Bạn là chuyên gia copywriting email marketing cho nền tảng hoàn tiền mua sắm Shopee tên là "{$siteName}".
Nhiệm vụ: viết một email marketing bằng TIẾNG VIỆT theo yêu cầu của người dùng.

QUY TẮC BẮT BUỘC:
- Chỉ trả về DUY NHẤT một object JSON hợp lệ, KHÔNG kèm giải thích, KHÔNG bọc trong dấu ```.
- Cấu trúc JSON: {"subject": "tiêu đề email", "body": "mã HTML đầy đủ của email"}.
- Trường "body" phải là HTML hoàn chỉnh dùng inline CSS (style="..."), responsive, tối đa rộng 600px, căn giữa.
- Dùng màu thương hiệu chủ đạo là {$themeColor} cho tiêu đề, nút bấm và điểm nhấn.
- Có thể chèn các biến cá nhân hóa (giữ NGUYÊN văn dạng chuỗi này, không thay đổi): {{name}}, {{email}}, {{balance}}, {{referral_code}}, {{site_name}}.
- Nên có nút kêu gọi hành động (CTA) rõ ràng.
- Giọng điệu: {$toneText}.
- Không dùng thẻ <script>, không dùng JavaScript trong body.
SYS;

        // Nếu có mã giảm giá được chọn, bổ sung yêu cầu chia sẻ mã vào system prompt.
        // Mục tiêu chính của email loại này là nhắc khách nhớ đến thương hiệu website.
        if ($couponBlock !== '') {
            $systemPrompt .= <<<CP


NHIỆM VỤ CHIA SẺ MÃ GIẢM GIÁ (ƯU TIÊN CAO):
- Mục tiêu CHÍNH của email này là NHẮC KHÁCH HÀNG NHỚ ĐẾN thương hiệu "{$siteName}", tạo thiện cảm để khách quay lại website; việc chia sẻ mã giảm giá chỉ là "cái cớ" thân thiện.
- Đưa các mã giảm giá Shopee dưới đây vào email. TUYỆT ĐỐI giữ nguyên mã (code) và đường link, KHÔNG bịa thêm mã, KHÔNG đổi link:
{$couponBlock}
- Trình bày mỗi mã thành một "thẻ" (card) bắt mắt: hiển thị nổi bật MÃ CODE (font lớn, dễ copy), tiêu đề ưu đãi, điều kiện đơn tối thiểu và hạn sử dụng nếu có.
- Mỗi mã có một nút CTA "Dùng mã ngay" trỏ đúng tới link đã cho của mã đó.
- Nhấn mạnh tên thương hiệu "{$siteName}" ở đầu và cuối email (tiêu đề, lời chào, chân trang) để khách ghi nhớ website.
- Kết thúc bằng lời mời khách quay lại "{$siteName}" để mua sắm hoàn tiền và săn thêm mã giảm giá mỗi ngày.
CP;
        }

        // Nếu có giftcode được chọn, bổ sung yêu cầu chia sẻ mã quà tặng (cộng tiền vào ví)
        if ($giftCodeBlock !== '') {
            $giftRedeemUrl = route('giftcode.index');
            $systemPrompt .= <<<GC


NHIỆM VỤ CHIA SẺ GIFTCODE (MÃ QUÀ TẶNG CỘNG TIỀN VÀO VÍ):
- Đây là mã quà tặng: người dùng nhập mã trên "{$siteName}" để nhận tiền thưởng thẳng vào ví. Mục tiêu vẫn là NHẮC KHÁCH NHỚ ĐẾN thương hiệu "{$siteName}" và kéo khách quay lại website.
- Đưa các giftcode dưới đây vào email. TUYỆT ĐỐI giữ nguyên mã (code), KHÔNG bịa thêm mã:
{$giftCodeBlock}
- Trình bày mỗi giftcode thành một "thẻ quà tặng" nổi bật: hiển thị MÃ CODE (font lớn, dễ copy), số tiền thưởng và hạn sử dụng nếu có.
- Mỗi giftcode có nút CTA "Nhập mã nhận thưởng" trỏ tới đường link: {$giftRedeemUrl}
- Giải thích ngắn gọn cách dùng: đăng nhập "{$siteName}" → vào trang Giftcode → nhập mã để nhận tiền vào ví.
GC;
        }

        // Nếu có nhiệm vụ được chọn, bổ sung yêu cầu chia sẻ danh sách nhiệm vụ thành viên
        if ($taskBlock !== '') {
            $taskUrl = route('tasks.index');
            $systemPrompt .= <<<TK


NHIỆM VỤ THÀNH VIÊN (CHIA SẺ NHIỆM VỤ ĐỂ NHẬN THƯỞNG):
- Giới thiệu các nhiệm vụ kiếm tiền/kiếm thưởng dưới đây đang diễn ra trên hệ thống để người dùng tham gia:
{$taskBlock}
- Trình bày mỗi nhiệm vụ thành một "thẻ nhiệm vụ" (card) thu hút: hiển thị Tiêu đề nhiệm vụ, mô tả ngắn và số tiền thưởng nhận được khi hoàn thành.
- Mỗi nhiệm vụ có một nút kêu gọi hành động CTA "Tham gia nhiệm vụ" hoặc "Làm ngay" trỏ đến đường dẫn: {$taskUrl}
- Nhấn mạnh rằng hoàn thành nhiệm vụ là cách nhanh nhất để gia tăng số dư ví.
TK;
        }

        $shareHint = ($couponBlock !== '' || $giftCodeBlock !== '' || $taskBlock !== '')
            ? "\n(Hãy lồng ghép các mã giảm giá, giftcode hoặc nhiệm vụ đã cung cấp vào email và ưu tiên nhắc nhớ thương hiệu website.)"
            : '';

        $userPrompt = $withSubject
            ? "Yêu cầu nội dung email: {$validated['prompt']}{$shareHint}"
            : "Yêu cầu nội dung email: {$validated['prompt']}\n(Vẫn trả về trường subject gợi ý, nhưng tập trung vào body.){$shareHint}";

        try {
            $aiService = app(\App\Services\AIService::class);
            $response = $aiService->chat([
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user',   'content' => $userPrompt],
            ], [
                'max_tokens'  => 4000,
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
            $subject = null;
            $body    = null;

            if ($start !== false && $end !== false && $end > $start) {
                $json = substr($clean, $start, $end - $start + 1);
                $parsed = json_decode($json, true);
                if (is_array($parsed)) {
                    $subject = $parsed['subject'] ?? null;
                    $body    = $parsed['body'] ?? null;
                }
            }

            // Nếu không phân tích được JSON, coi toàn bộ phản hồi là body HTML
            if (empty($body)) {
                $body = $clean;
            }

            ActivityLog::log(__('Tạo nội dung email bằng AI cho chiến dịch'), auth()->id());

            return response()->json([
                'success' => true,
                'subject' => $subject,
                'body'    => $body,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('Lỗi khi tạo nội dung AI: :error', ['error' => $e->getMessage()]),
            ], 422);
        }
    }

    /**
     * Làm mới (refresh) thống kê campaign từ bảng email_queues.
     * Dùng khi Admin muốn cập nhật số liệu gửi thủ công.
     */
    public function refreshStats(EmailCampaign $emailCampaign)
    {
        $emailCampaign->refreshStats();
        $emailCampaign->refresh();

        // Nếu không còn email pending nào và campaign đang ở trạng thái sending → chuyển sang sent
        $pendingCount = $emailCampaign->emailQueues()->where('status', 'pending')->count();
        if ($emailCampaign->status === 'sending' && $pendingCount === 0 && $emailCampaign->total_recipients > 0) {
            $emailCampaign->update(['status' => 'sent']);
        }

        if (request()->ajax()) {
            return response()->json([
                'total_recipients' => $emailCampaign->total_recipients,
                'total_sent'       => $emailCampaign->total_sent,
                'total_failed'     => $emailCampaign->total_failed,
                'pending_count'    => $pendingCount,
                'status'           => $emailCampaign->status,
                'status_label'     => $emailCampaign->status_label,
                'success_rate'     => $emailCampaign->success_rate,
            ]);
        }

        return back()->with('success', __('Đã làm mới thống kê chiến dịch.'));
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

    /**
     * Lấy danh sách nhiệm vụ đang hoạt động để hiển thị cho Admin chọn
     * chia sẻ trong modal tạo nội dung bằng AI. Trả về mảng gọn nhẹ cho frontend.
     */
    private function activeTasksForAi()
    {
        $now = now();
        return Task::where('is_active', true)
            ->where(function ($q) use ($now) {
                $q->whereNull('start_at')->orWhere('start_at', '<=', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('end_at')->orWhere('end_at', '>=', $now);
            })
            ->orderBy('sort_order')
            ->limit(50)
            ->get()
            ->map(fn ($t) => [
                'id'            => $t->id,
                'title'         => $t->title,
                'description'   => $t->description,
                'reward_amount' => number_format($t->reward_amount) . 'đ',
                'type_label'    => $t->getTypeLabel(),
            ])
            ->values();
    }

    /**
     * Dựng khối văn bản mô tả các Nhiệm vụ được chọn để đưa vào prompt cho AI.
     *
     * @param  array<int>  $taskIds
     */
    private function buildTaskPromptBlock(array $taskIds): string
    {
        $taskIds = array_filter(array_map('intval', $taskIds));
        if (empty($taskIds)) {
            return '';
        }

        $now = now();
        $tasks = Task::whereIn('id', $taskIds)
            ->where('is_active', true)
            ->where(function ($q) use ($now) {
                $q->whereNull('start_at')->orWhere('start_at', '<=', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('end_at')->orWhere('end_at', '>=', $now);
            })
            ->get();

        if ($tasks->isEmpty()) {
            return '';
        }

        $lines = [];
        foreach ($tasks as $t) {
            $parts = ["Nhiệm vụ: {$t->title}"];
            if ($t->description) {
                $parts[] = "Mô tả: {$t->description}";
            }
            $parts[] = "Phần thưởng: " . number_format($t->reward_amount) . 'đ';
            $parts[] = "Loại nhiệm vụ: " . $t->getTypeLabel();
            $lines[] = '- ' . implode(' | ', $parts);
        }

        return implode("\n", $lines);
    }
}
