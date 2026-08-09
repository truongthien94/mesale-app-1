<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    use HasFactory;

    /**
     * Tên bảng tương ứng trong cơ sở dữ liệu.
     *
     * @var string
     */
    protected $table = 'coupons';

    /**
     * Các trường được phép gán dữ liệu hàng loạt (mass assignable).
     *
     * @var array
     */
    protected $fillable = [
        'platform',
        'code',
        'title',
        'description',
        'category',
        'min_spend',
        'discount_amount',
        'discount_percentage',
        'clicks',
        'expired_at',
        'redirect_link',
        'source',
        'image_url',
    ];

    /**
     * Các trường cần ép kiểu dữ liệu.
     *
     * @var array
     */
    protected $casts = [
        'min_spend' => 'integer',
        'discount_amount' => 'integer',
        'discount_percentage' => 'integer',
        'clicks' => 'integer',
        'expired_at' => 'datetime',
    ];
}
