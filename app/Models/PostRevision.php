<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PostRevision extends Model
{
    use HasFactory;

    // Tắt chế độ tự động cập nhật thời gian updated_at vì bảng chỉ lưu lịch sử dạng log
    public $timestamps = false;

    protected $fillable = [
        'post_id',
        'user_id',
        'title',
        'summary',
        'content',
        'created_at'
    ];

    protected $casts = [
        'created_at' => 'datetime'
    ];

    /**
     * Mối quan hệ với bài viết chính (Post)
     */
    public function post()
    {
        return $this->belongsTo(Post::class, 'post_id');
    }

    /**
     * Mối quan hệ với người thực hiện chỉnh sửa (User)
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
