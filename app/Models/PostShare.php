<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PostShare extends Model
{
    use HasFactory;

    // Tắt timestamps mặc định vì bảng chỉ lưu created_at
    public $timestamps = false;

    protected $fillable = [
        'post_id',
        'platform',
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
}
