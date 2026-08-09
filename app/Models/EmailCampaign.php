<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Model EmailCampaign - Chiến dịch Email Marketing.
 *
 * Quản lý toàn bộ vòng đời của một chiến dịch email: soạn thảo → lên lịch → gửi → hoàn thành.
 * Email thực tế được đẩy vào bảng email_queues và xử lý bởi Cron Job.
 */
class EmailCampaign extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'subject',
        'body',
        'from_name',
        'from_email',
        'target_audience',
        'target_filter',
        'status',
        'scheduled_at',
        'sent_at',
        'total_recipients',
        'total_sent',
        'total_failed',
        'created_by',
    ];

    protected $casts = [
        'target_filter' => 'array',
        'scheduled_at' => 'datetime',
        'sent_at' => 'datetime',
    ];

    // Danh sách đối tượng nhắm mục tiêu hợp lệ
    public const AUDIENCES = [
        'all'           => 'Tất cả thành viên',
        'active'        => 'Thành viên đang hoạt động (active)',
        'inactive'      => 'Thành viên bị tạm khoá (suspended)',
        'has_balance'   => 'Thành viên có số dư trong ví',
        'no_orders'     => 'Thành viên chưa có đơn hoàn tiền nào',
        'referrers'     => 'Thành viên đã giới thiệu ít nhất 1 người',
        'top_referrers' => 'Top thành viên giới thiệu nhiều nhất (Top 100)',
    ];

    // Danh sách trạng thái chiến dịch
    public const STATUSES = [
        'draft'      => 'Nháp',
        'scheduled'  => 'Đã lên lịch',
        'sending'    => 'Đang gửi',
        'sent'       => 'Đã gửi xong',
        'cancelled'  => 'Đã huỷ',
    ];

    /**
     * Màu badge trạng thái campaign cho giao diện.
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'draft'     => 'gray',
            'scheduled' => 'blue',
            'sending'   => 'yellow',
            'sent'      => 'green',
            'cancelled' => 'red',
            default     => 'gray',
        };
    }

    /**
     * Nhãn trạng thái tiếng Việt.
     */
    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    /**
     * Tên đối tượng nhắm mục tiêu tiếng Việt.
     */
    public function getAudienceLabelAttribute(): string
    {
        return self::AUDIENCES[$this->target_audience] ?? $this->target_audience;
    }

    /**
     * Tỷ lệ gửi thành công (%).
     */
    public function getSuccessRateAttribute(): float
    {
        if ($this->total_recipients === 0) return 0;
        return round(($this->total_sent / $this->total_recipients) * 100, 1);
    }

    /**
     * Lấy danh sách User theo tiêu chí target_audience.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function buildRecipientQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = User::query()->whereNotNull('email');

        switch ($this->target_audience) {
            case 'active':
                $query->where('status', 'active');
                break;

            case 'inactive':
                $query->where('status', 'suspended');
                break;

            case 'has_balance':
                $query->where('balance', '>', 0);
                break;

            case 'no_orders':
                $query->whereDoesntHave('cashbackHistories');
                break;

            case 'referrers':
                // Người dùng đã giới thiệu ít nhất 1 người (referred_by = id của người này)
                $query->whereHas('referredUsers');
                break;

            case 'top_referrers':
                // Top 100 người giới thiệu nhiều nhất
                $topIds = User::withCount(['referredUsers as referral_count'])
                    ->having('referral_count', '>', 0)
                    ->orderByDesc('referral_count')
                    ->limit(100)
                    ->pluck('id');
                $query->whereIn('id', $topIds);
                break;

            case 'all':
            default:
                // Không thêm điều kiện — lấy tất cả
                break;
        }

        // Áp dụng bộ lọc bổ sung từ target_filter JSON
        if (!empty($this->target_filter)) {
            if (!empty($this->target_filter['min_balance'])) {
                $query->where('balance', '>=', (int) $this->target_filter['min_balance']);
            }
            if (!empty($this->target_filter['registered_after'])) {
                $query->where('created_at', '>=', $this->target_filter['registered_after']);
            }
        }

        return $query;
    }

    /**
     * Đếm số người nhận thực tế theo tiêu chí hiện tại.
     */
    public function countRecipients(): int
    {
        return $this->buildRecipientQuery()->count();
    }

    /**
     * Render nội dung email với các biến thay thế cho từng user.
     * Hỗ trợ các biến: {{name}}, {{email}}, {{balance}}, {{referral_code}}, {{site_name}}
     */
    public function renderBodyForUser(User $user): string
    {
        $siteName = Setting::getVal('site_name', 'Hoàn Tiền Shopee');

        return str_replace(
            ['{{name}}', '{{email}}', '{{balance}}', '{{referral_code}}', '{{site_name}}'],
            [
                e($user->name),
                e($user->email),
                number_format($user->balance ?? 0) . 'đ',
                e($user->referral_code ?? ''),
                e($siteName),
            ],
            $this->body
        );
    }

    /**
     * Đẩy toàn bộ email của chiến dịch vào hàng đợi (email_queues) và chuyển sang trạng thái 'sending'.
     * Render nội dung riêng cho từng người nhận, insert theo batch 200 để tránh N+1.
     *
     * @return int Số người nhận đã đưa vào hàng đợi (0 nếu không có ai phù hợp).
     */
    public function dispatchToQueue(): int
    {
        $subject = $this->subject;

        $users = $this->buildRecipientQuery()
            ->select('id', 'name', 'email', 'balance', 'referral_code')
            ->get();

        if ($users->isEmpty()) {
            return 0;
        }

        // Chuyển trạng thái sang "đang gửi" và khởi tạo lại bộ đếm
        $this->update([
            'status'           => 'sending',
            'sent_at'          => now(),
            'total_recipients' => $users->count(),
            'total_sent'       => 0,
            'total_failed'     => 0,
        ]);

        $users->chunk(200)->each(function ($batch) use ($subject) {
            $now  = now();
            $rows = [];

            foreach ($batch as $user) {
                if (empty($user->email)) continue;

                $rows[] = [
                    'campaign_id'   => $this->id,
                    'to_email'      => $user->email,
                    'to_name'       => $user->name,
                    'subject'       => $subject,
                    'body'          => $this->renderBodyForUser($user),
                    'status'        => 'pending',
                    'attempts'      => 0,
                    'error_message' => null,
                    'sent_at'       => null,
                    'created_at'    => $now,
                    'updated_at'    => $now,
                ];
            }

            if (!empty($rows)) {
                EmailQueue::insert($rows);
            }
        });

        return $users->count();
    }

    /**
     * Đẩy các chiến dịch đã đến giờ lên lịch vào hàng đợi. Dùng cho Cron/Scheduler.
     * Chiến dịch không có người nhận phù hợp sẽ được đánh dấu 'sent' để không lặp lại.
     *
     * @return int Số chiến dịch đã được kích hoạt.
     */
    public static function dispatchDueScheduled(): int
    {
        $due = self::where('status', 'scheduled')
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '<=', now())
            ->get();

        $count = 0;
        foreach ($due as $campaign) {
            if ($campaign->dispatchToQueue() === 0) {
                $campaign->update(['status' => 'sent', 'sent_at' => now()]);
            }
            $count++;
        }

        return $count;
    }

    /**
     * Hoàn tất các chiến dịch đang gửi mà hàng đợi đã xử lý xong (không còn email pending).
     * Cập nhật thống kê và chuyển trạng thái 'sending' → 'sent'. Dùng cho Cron/Scheduler.
     *
     * @return int Số chiến dịch vừa được hoàn tất.
     */
    public static function finalizeCompletedSending(): int
    {
        $sending = self::where('status', 'sending')->get();

        $count = 0;
        foreach ($sending as $campaign) {
            $campaign->refreshStats();
            $hasPending = $campaign->emailQueues()->where('status', 'pending')->exists();
            if (!$hasPending) {
                $campaign->update(['status' => 'sent']);
                $count++;
            }
        }

        return $count;
    }

    // === RELATIONSHIPS ===

    /**
     * Admin đã tạo chiến dịch này.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Danh sách email trong hàng đợi thuộc chiến dịch này.
     */
    public function emailQueues()
    {
        return $this->hasMany(EmailQueue::class, 'campaign_id');
    }

    /**
     * Cập nhật thống kê campaign từ bảng email_queues.
     */
    public function refreshStats(): void
    {
        $stats = $this->emailQueues()->selectRaw('
            COUNT(*) as total,
            SUM(CASE WHEN status = "sent" THEN 1 ELSE 0 END) as total_sent,
            SUM(CASE WHEN status = "failed" THEN 1 ELSE 0 END) as total_failed
        ')->first();

        $this->update([
            'total_recipients' => $stats->total ?? 0,
            'total_sent'       => $stats->total_sent ?? 0,
            'total_failed'     => $stats->total_failed ?? 0,
        ]);
    }
}
