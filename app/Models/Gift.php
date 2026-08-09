<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Gift extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'image',
        'description',
        'price',
        'stock',
        'type', // voucher, phone_card, giftcode, physical
        'tag',
        'status'
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'stock' => 'integer',
        'status' => 'boolean'
    ];

    /**
     * Quan hệ: Một quà tặng có nhiều lượt đổi quà.
     */
    public function redemptions()
    {
        return $this->hasMany(GiftRedemption::class);
    }
}
