<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ActivityLog extends Model
{
    use HasFactory;

    // Không sử dụng trường updated_at vì nhật ký hoạt động là bất biến
    const UPDATED_AT = null;

    // Danh sách cột điền thông tin
    protected $fillable = [
        'user_id',
        'activity',
        'ip_address',
        'user_agent'
    ];

    /**
     * Mối quan hệ: Nhật ký hoạt động thuộc về một Người dùng (User).
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Helper ghi nhận hoạt động một cách nhanh chóng.
     *
     * @param string $activity Mô tả hoạt động
     * @param int|null $userId ID người dùng (nếu có)
     * @return self
     */
    public static function log(string $activity, ?int $userId = null)
    {
        return self::create([
            'user_id' => $userId ?? auth()->id(),
            'activity' => $activity,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent()
        ]);
    }
}
