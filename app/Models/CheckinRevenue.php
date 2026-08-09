<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CheckinRevenue extends Model
{
    use HasFactory;

    /**
     * Tên bảng tương ứng trong cơ sở dữ liệu.
     *
     * @var string
     */
    protected $table = 'checkin_revenues';

    /**
     * Các trường được phép gán dữ liệu hàng loạt (mass assignable).
     *
     * @var array
     */
    protected $fillable = [
        'order_id',
        'product_name',
        'original_price',
        'commission_amount',
        'status',
        'shop_name',
        'fraud_reason',
    ];
}
