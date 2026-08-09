<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PostView extends Model
{
    use HasFactory;

    // Tắt timestamps mặc định của Eloquent vì bảng chỉ có viewed_at dạng timestamp
    public $timestamps = false;

    protected $fillable = [
        'post_id',
        'user_id',
        'ip_address',
        'user_agent',
        'viewed_at'
    ];

    protected $casts = [
        'viewed_at' => 'datetime'
    ];

    /**
     * Mối quan hệ với bài viết (Post)
     */
    public function post()
    {
        return $this->belongsTo(Post::class, 'post_id');
    }

    /**
     * Mối quan hệ với thành viên xem (User)
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
