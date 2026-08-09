<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Translation extends Model
{
    // Khai báo các cột có thể gán dữ liệu hàng loạt (mass assignable)
    protected $fillable = [
        'language_code',
        'key',
        'value'
    ];
}
