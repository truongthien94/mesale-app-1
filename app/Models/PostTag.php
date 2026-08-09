<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PostTag extends Model
{
    use HasFactory;

    // Các trường được phép gán dữ liệu hàng loạt
    protected $fillable = [
        'name',
        'slug',
    ];

    /**
     * Mối quan hệ nhiều-nhiều với bài viết (Posts)
     * Một tag có thể được sử dụng trong nhiều bài viết khác nhau
     */
    public function posts()
    {
        return $this->belongsToMany(Post::class, 'post_tag_maps', 'tag_id', 'post_id');
    }

    /**
     * Mối quan hệ đa hình SEO Meta (Morph One SEO Meta)
     * Mỗi nhãn bài viết có thể có cấu hình SEO Meta riêng biệt
     */
    public function seoMeta()
    {
        return $this->morphOne(SeoMeta::class, 'seoable');
    }
}
