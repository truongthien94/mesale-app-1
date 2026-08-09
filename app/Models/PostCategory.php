<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PostCategory extends Model
{
    use HasFactory;

    // Các trường được phép gán dữ liệu hàng loạt (Mass Assignment)
    protected $fillable = [
        'parent_id',
        'name',
        'slug',
        'description',
        'image',
        'icon',
        'order',
        'is_visible'
    ];

    // Ép kiểu các thuộc tính sang kiểu dữ liệu phù hợp
    protected $casts = [
        'is_visible' => 'boolean',
        'order' => 'integer',
    ];

    /**
     * Mối quan hệ danh mục cha (Parent Category)
     * Một danh mục có thể thuộc về một danh mục cha
     */
    public function parent()
    {
        return $this->belongsTo(PostCategory::class, 'parent_id');
    }

    /**
     * Mối quan hệ danh mục con (Child Categories)
     * Một danh mục có thể có nhiều danh mục con
     */
    public function children()
    {
        return $this->hasMany(PostCategory::class, 'parent_id')->orderBy('order', 'asc');
    }

    /**
     * Mối quan hệ bài viết (Posts)
     * Một danh mục có thể có nhiều bài viết trực tiếp thuộc về nó
     */
    public function posts()
    {
        return $this->hasMany(Post::class, 'category_id');
    }

    /**
     * Mối quan hệ đa hình SEO Meta (Morph One SEO Meta)
     * Mỗi danh mục có một bản ghi SEO Meta tương ứng để tối ưu SEO
     */
    public function seoMeta()
    {
        return $this->morphOne(SeoMeta::class, 'seoable');
    }
}
