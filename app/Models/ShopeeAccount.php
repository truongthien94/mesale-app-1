<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShopeeAccount extends Model
{
    // Các thuộc tính có thể gán hàng loạt
    protected $fillable = [
        'name',
        'cookie',
        'username',
        'status',
        'error_message',
        'last_sync_at',
    ];

    // Chuyển đổi thuộc tính cookie thành dạng mảng khi truy xuất
    protected $casts = [
        'cookie' => 'array',
        'last_sync_at' => 'datetime',
    ];
}
