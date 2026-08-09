<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Banner extends Model
{
    use HasFactory;

    // Danh sách cột điền thông tin
    protected $fillable = [
        'image_url',
        'link',
        'title',
        'order',
        'is_active'
    ];
}
