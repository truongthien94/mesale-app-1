<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SavedProduct extends Model
{
    // Các thuộc tính có thể gán hàng loạt (mass assignable)
    protected $fillable = [
        'user_id',
        'platform',
        'name',
        'image',
        'price',
        'cashback_amount',
        'affiliate_url',
        'product_url',
    ];

    /**
     * Định nghĩa mối quan hệ: Sản phẩm đã lưu thuộc về một người dùng (User)
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
