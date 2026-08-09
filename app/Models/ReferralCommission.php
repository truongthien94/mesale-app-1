<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReferralCommission extends Model
{
    use HasFactory;

    // Danh sách các cột có thể điền thông tin
    protected $fillable = [
        'referrer_id',
        'referred_id',
        'cashback_history_id',
        'amount',
        'level',
        'status',
    ];

    // Chuyển đổi định dạng dữ liệu
    protected $casts = [
        'amount' => 'decimal:2',
        'referrer_id' => 'integer',
        'referred_id' => 'integer',
        'cashback_history_id' => 'integer',
        'level' => 'integer',
    ];

    /**
     * A null referrer is an intentional anonymized ledger row, not an orphan.
     */
    public function hasAnonymizedReferrer(): bool
    {
        return $this->referrer_id === null;
    }

    /**
     * Mối quan hệ: Hoa hồng thuộc về một Người giới thiệu nhận được (Referrer).
     */
    public function referrer()
    {
        return $this->belongsTo(User::class, 'referrer_id');
    }

    /**
     * Mối quan hệ: Người mua hàng phát sinh hoa hồng (Referred).
     */
    public function referred()
    {
        return $this->belongsTo(User::class, 'referred_id');
    }

    /**
     * Mối quan hệ: Liên kết tới lịch sử hoàn tiền đơn hàng phát sinh (Cashback History).
     */
    public function cashbackHistory()
    {
        return $this->belongsTo(CashbackHistory::class, 'cashback_history_id');
    }
}
