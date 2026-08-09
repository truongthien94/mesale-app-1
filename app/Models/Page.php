<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Model Page: Quản lý các trang nội dung tĩnh (Điều khoản dịch vụ, Chính sách bảo mật, v.v.)
 * Mỗi trang có slug riêng biệt để tạo URL thân thiện SEO.
 */
class Page extends Model
{
    // Các trường được phép gán dữ liệu hàng loạt
    protected $fillable = [
        'title',
        'slug',
        'content',
        'status',
        'sort_order',
        'meta_description',
        'is_noindex',
    ];

    // Ép kiểu dữ liệu cho các thuộc tính
    protected $casts = [
        'sort_order' => 'integer',
        'is_noindex' => 'boolean',
    ];

    /**
     * Scope: Lọc các trang đã xuất bản (status = published)
     * Dùng để hiển thị phía frontend cho khách truy cập
     */
    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }
}
