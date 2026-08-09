<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PostLike extends Model
{
    use HasFactory;

    // Tắt timestamps mặc định của Eloquent vì bảng chỉ lưu created_at
    public $timestamps = false;

    protected $fillable = [
        'post_id',
        'user_id',
        'ip_address',
        'created_at'
    ];

    protected $casts = [
        'created_at' => 'datetime'
    ];

    /**
     * Mối quan hệ với bài viết (Post)
     */
    public function post()
    {
        return $this->belongsTo(Post::class, 'post_id');
    }

    /**
     * Mối quan hệ với thành viên thích (User)
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
