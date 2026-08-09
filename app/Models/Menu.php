<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Menu extends Model
{
    use HasFactory;

    // Các cột cho phép ghi dữ liệu hàng loạt
    protected $fillable = [
        'title',
        'url',
        'icon',
        'position',
        'target',
        'order',
        'status',
        'parent_id',
        'auth_rule'
    ];

    // Chuyển đổi kiểu dữ liệu cho cột trạng thái
    protected $casts = [
        'status' => 'boolean',
        'order' => 'integer',
    ];

    /**
     * Mối quan hệ: Một Menu có thể thuộc về một Menu cha (parent_id).
     */
    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * Mối quan hệ: Một Menu có thể có nhiều Menu con cấp dưới.
     */
    public function children()
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('order', 'asc');
    }

    /**
     * Phạm vi truy vấn (Scope): Chỉ lấy các menu đang kích hoạt (status = true).
     */
    public function scopeActive($query)
    {
        return $query->where('status', true);
    }
}
