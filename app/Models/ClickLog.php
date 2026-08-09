<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model ClickLog: Lưu trữ lịch sử từng lượt click vào link rút gọn.
 * Mỗi bản ghi tương ứng với 1 lượt click riêng biệt, phục vụ phân tích hành vi và chống gian lận.
 */
class ClickLog extends Model
{
    // Bảng click_logs không dùng cột updated_at, chỉ ghi log 1 lần khi click
    const UPDATED_AT = null;

    // Danh sách các cột được phép gán dữ liệu hàng loạt (Mass Assignment)
    protected $fillable = [
        'short_link_id',
        'ip_address',
        'device',
        'location',
        'user_agent',
    ];

    /**
     * Mối quan hệ: Mỗi log click thuộc về 1 liên kết rút gọn (ShortLink).
     */
    public function shortLink(): BelongsTo
    {
        return $this->belongsTo(ShortLink::class, 'short_link_id');
    }
}
