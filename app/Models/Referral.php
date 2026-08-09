<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Referral extends Model
{
    use HasFactory;

    // Danh sách các cột có thể điền thông tin
    protected $fillable = [
        'referrer_id',
        'referred_id'
    ];

    /**
     * Mối quan hệ: Lấy thông tin người giới thiệu (Referrer - F0).
     */
    public function referrer()
    {
        return $this->belongsTo(User::class, 'referrer_id');
    }

    /**
     * Mối quan hệ: Lấy thông tin người được giới thiệu (Referred - F1).
     */
    public function referred()
    {
        return $this->belongsTo(User::class, 'referred_id');
    }
}
