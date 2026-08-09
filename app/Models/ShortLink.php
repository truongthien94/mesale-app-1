<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShortLink extends Model
{
    use HasFactory;

    // Chỉ định bảng liên kết trong database
    protected $table = 'short_links';

    // Danh sách các cột được phép gán dữ liệu hàng loạt (Mass Assignment)
    protected $fillable = [
        'code',
        'destination_url',
        'product_name',
        'product_image',
        'user_id',
        'clicks'
    ];

    // Ép kiểu dữ liệu cho các trường tương ứng
    protected $casts = [
        'user_id' => 'integer',
        'clicks' => 'integer'
    ];

    /**
     * Thiết lập mối quan hệ: Mỗi liên kết rút gọn thuộc về một người dùng (nếu có đăng nhập).
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Mối quan hệ: Mỗi liên kết rút gọn có nhiều bản ghi lịch sử click.
     * Sắp xếp theo thời gian mới nhất trước.
     */
    public function clickLogs(): HasMany
    {
        return $this->hasMany(ClickLog::class, 'short_link_id')->orderByDesc('created_at');
    }
}
