<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Model nhật ký lượt đổi Giftcode của thành viên.
 */
class GiftCodeRedemption extends Model
{
    use HasFactory;

    protected $table = 'gift_code_redemptions';

    protected $fillable = [
        'gift_code_id',
        'user_id',
        'code',
        'amount',
        'ip_address',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    /**
     * Quan hệ: Lượt đổi thuộc về một mã Giftcode.
     */
    public function giftCode()
    {
        return $this->belongsTo(GiftCode::class);
    }

    /**
     * Quan hệ: Lượt đổi thuộc về một thành viên.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
