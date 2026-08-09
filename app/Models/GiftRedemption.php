<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class GiftRedemption extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'user_id',
        'gift_id',
        'amount',
        'shipping_info',
        'status', // pending, approved, rejected
        'gift_data',
        'notes',
        'processed_at'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'shipping_info' => 'array', // Tự động cast JSON sang array
        'processed_at' => 'datetime'
    ];

    /**
     * Quan hệ: Lượt đổi quà thuộc về một user.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Quan hệ: Lượt đổi quà thuộc về một quà tặng.
     */
    public function gift()
    {
        return $this->belongsTo(Gift::class);
    }
}
