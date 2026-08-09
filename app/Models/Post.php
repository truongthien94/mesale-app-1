<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    use HasFactory;

    // Các trường được phép gán dữ liệu hàng loạt
    protected $fillable = [
        'author_id',
        'category_id',
        'title',
        'slug',
        'summary',
        'content',
        'thumbnail',
        'gallery',
        'video_url',
        'status',
        'published_at',
        'is_sticky',
        'is_featured',
        'is_commentable',
        'view_count',
        'share_count',
        'like_count',
        'comment_count',
        'faq_schema',
    ];

    // Ép kiểu các thuộc tính sang kiểu dữ liệu phù hợp
    protected $casts = [
        'is_sticky' => 'boolean',
        'is_featured' => 'boolean',
        'is_commentable' => 'boolean',
        'published_at' => 'datetime',
        'gallery' => 'array',
        'faq_schema' => 'array',
        'view_count' => 'integer',
        'share_count' => 'integer',
        'like_count' => 'integer',
        'comment_count' => 'integer',
    ];

    /**
     * Mối quan hệ tác giả (Author)
     * Mỗi bài viết thuộc về một tài khoản người dùng/admin
     */
    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /**
     * Mối quan hệ danh mục (Category)
     * Mỗi bài viết có một danh mục chính
     */
    public function category()
    {
        return $this->belongsTo(PostCategory::class, 'category_id');
    }

    /**
     * Mối quan hệ nhiều-nhiều với nhãn bài viết (Tags)
     * Một bài viết có thể gắn nhiều nhãn
     */
    public function tags()
    {
        return $this->belongsToMany(PostTag::class, 'post_tag_maps', 'post_id', 'tag_id');
    }

    /**
     * Mối quan hệ bình luận (Comments)
     * Một bài viết có nhiều bình luận của độc giả
     */
    public function comments()
    {
        return $this->hasMany(Comment::class, 'post_id');
    }

    /**
     * Mối quan hệ bình luận đã duyệt (Approved Comments)
     * Một bài viết hiển thị các bình luận ở trạng thái 'approved'
     */
    public function approvedComments()
    {
        return $this->hasMany(Comment::class, 'post_id')
            ->where('status', 'approved')
            ->whereNull('parent_id')
            ->orderBy('created_at', 'desc');
    }

    /**
     * Mối quan hệ đa hình SEO Meta (Morph One SEO Meta)
     * Mỗi bài viết có cấu hình SEO Meta riêng
     */
    public function seoMeta()
    {
        return $this->morphOne(SeoMeta::class, 'seoable');
    }

    /**
     * Mối quan hệ lịch sử chỉnh sửa (Revisions)
     * Lưu trữ các phiên bản cũ để phục hồi khi cần thiết
     */
    public function revisions()
    {
        return $this->hasMany(PostRevision::class, 'post_id')->orderBy('created_at', 'desc');
    }

    /**
     * Mối quan hệ danh sách lượt xem (Views)
     */
    public function views()
    {
        return $this->hasMany(PostView::class, 'post_id');
    }

    /**
     * Mối quan hệ danh sách lượt thích (Likes)
     */
    public function likes()
    {
        return $this->hasMany(PostLike::class, 'post_id');
    }

    /**
     * Mối quan hệ danh sách lượt chia sẻ (Shares)
     * Giúp liên kết bài viết với các lượt chia sẻ để hỗ trợ thống kê và dọn dẹp khi xóa bài viết.
     */
    public function shares()
    {
        return $this->hasMany(PostShare::class, 'post_id');
    }

    /**
     * Scope lọc các bài viết đã được xuất bản công khai
     */
    public function scopePublished($query)
    {
        return $query->where('status', 'published')
            ->where(function ($q) {
                $q->whereNull('published_at')
                  ->orWhere('published_at', '<=', now());
            });
    }

    /**
     * Scope lọc các bài viết nổi bật
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }
}
