<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Comment extends Model
{
    use HasFactory;

    // Các trường được phép gán dữ liệu hàng loạt
    protected $fillable = [
        'post_id',
        'user_id',
        'parent_id',
        'author_name',
        'author_email',
        'content',
        'status',
        'like_count',
        'ip_address',
        'user_agent',
    ];

    // Ép kiểu thuộc tính
    protected $casts = [
        'like_count' => 'integer',
    ];

    /**
     * Mối quan hệ bài viết (Post)
     * Mỗi bình luận thuộc về một bài viết cụ thể
     */
    public function post()
    {
        return $this->belongsTo(Post::class, 'post_id');
    }

    /**
     * Mối quan hệ thành viên (User)
     * Một bình luận có thể được viết bởi một thành viên đã đăng nhập
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Mối quan hệ bình luận cha (Parent Comment)
     * Trả về bình luận gốc của câu trả lời này
     */
    public function parent()
    {
        return $this->belongsTo(Comment::class, 'parent_id');
    }

    /**
     * Mối quan hệ các câu trả lời (Replies / Child Comments)
     * Trả về danh sách câu trả lời của bình luận này
     */
    public function replies()
    {
        return $this->hasMany(Comment::class, 'parent_id')
            ->where('status', 'approved')
            ->orderBy('created_at', 'asc');
    }

    /**
     * Lấy tên hiển thị của người bình luận (Author Name)
     * Ưu tiên tên của User đã đăng nhập, sau đó mới dùng tên nhập tay của khách
     */
    public function getAuthorNameAttribute()
    {
        if ($this->user) {
            return $this->user->name;
        }
        return $this->attributes['author_name'] ?: 'Khách ẩn danh';
    }
}
