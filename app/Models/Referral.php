<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Referral extends Model
{
    use HasFactory;

    // Danh sách các cột có thể điền thông tin
    protected $fillable = [
        'referrer_id',
        'referred_id',
    ];

    /**
     * A null referrer preserves the referred member's relationship history
     * without retaining the deleted member's personal information.
     */
    public function hasAnonymizedReferrer(): bool
    {
        return $this->referrer_id === null;
    }

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
