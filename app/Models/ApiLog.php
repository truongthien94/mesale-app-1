<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model đại diện cho bảng api_logs — nhật ký gọi API.
 *
 * Mỗi bản ghi tương ứng với 1 lần request API (Open API hoặc Bot API),
 * lưu trữ thông tin người gọi, endpoint, dữ liệu request và kết quả trả về.
 *
 * Business rule: Bảng này chỉ có thao tác INSERT (ghi log 1 lần),
 * không bao giờ UPDATE, do đó tắt updated_at để tiết kiệm dung lượng.
 */
class ApiLog extends Model
{
    // Tắt updated_at vì bảng log chỉ ghi 1 lần, không bao giờ cập nhật
    const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'method',
        'endpoint',
        'api_group',
        'status_code',
        'request_data',
        'response_time_ms',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'status_code'      => 'integer',
        'response_time_ms' => 'integer',
    ];

    /**
     * Quan hệ: Bản ghi log này thuộc về thành viên nào.
     * Nullable vì request chưa xác thực vẫn được ghi log.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
