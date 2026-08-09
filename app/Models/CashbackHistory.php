<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CashbackHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'platform',
        'order_id',
        'trans_id',
        'product_name',
        'shop_name',
        'product_image',
        'original_price',
        'cashback_amount',
        'cashback_rate',
        'commission_amount',
        'affiliate_url',
        'status',
        'approved_at',
        'rejected_reason',
        'fraud_reason',
        'click_metadata',
        'status_timeline'
    ];

    // Chuyển đổi kiểu dữ liệu cho các trường thập phân và ngày tháng
    protected $casts = [
        'original_price' => 'decimal:2',
        'cashback_amount' => 'decimal:2',
        'cashback_rate' => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'approved_at' => 'datetime',
        'click_metadata' => 'array',
        'status_timeline' => 'array',
    ];

    /**
     * Ghi thêm một sự kiện vào nhật ký dòng thời gian thay đổi trạng thái của đơn hoàn tiền.
     * Lưu ý: Hàm này chỉ gán vào thuộc tính, việc lưu vào DB do nơi gọi đảm nhiệm (->save()).
     *
     * @param string $event Loại sự kiện: created | approved | rejected | clawback
     * @param array $data Dữ liệu bổ sung (source, reason, amount...)
     * @return void
     */
    public function pushTimeline(string $event, array $data = []): void
    {
        $timeline = $this->status_timeline ?? [];

        $timeline[] = array_merge([
            'event' => $event,
            'at' => now()->toIso8601String(),
            'admin_id' => auth()->id(),
        ], $data);

        $this->status_timeline = $timeline;
    }

    /**
     * Mối quan hệ: Một lịch sử hoàn tiền thuộc về một Người dùng (User).
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Mối quan hệ: Một đơn hoàn tiền có thể phát sinh hoa hồng tiếp thị liên kết cho người giới thiệu.
     */
    public function commissions()
    {
        return $this->hasMany(ReferralCommission::class, 'cashback_history_id');
    }

    /**
     * Lấy liên kết rút gọn (ShortLink) tương ứng từ affiliate_url của đơn hàng.
     * Hàm này phân tích link rút gọn để tìm ra code và lấy ra clicks tracking.
     *
     * @return \App\Models\ShortLink|null
     */
    public function getShortLink()
    {
        // Nếu không có link affiliate thì trả về null
        if (!$this->affiliate_url) {
            return null;
        }
        
        // Trích xuất mã rút gọn (code) nằm ở cuối đường dẫn url
        $code = basename($this->affiliate_url);
        
        // Truy vấn bảng short_links để lấy thông tin click tracking
        return ShortLink::where('code', $code)->first();
    }
}
