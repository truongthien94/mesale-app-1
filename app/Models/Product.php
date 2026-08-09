<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Product extends Model
{
    use HasFactory;

    // Danh sách các cột điền thông tin
    protected $fillable = [
        'shopee_id',
        'name',
        'image',
        'price',
        'commission_amount',
        'shopee_commission',
        'cashback_amount',
        'cashback_rate',
        'affiliate_url',
        'search_count'
    ];

    // Chuyển đổi kiểu dữ liệu
    protected $casts = [
        'price' => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'shopee_commission' => 'decimal:2',
        'cashback_amount' => 'decimal:2',
        'cashback_rate' => 'decimal:2',
    ];
}
