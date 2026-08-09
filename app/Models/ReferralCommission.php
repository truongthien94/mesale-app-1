<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

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
        'status'
    ];

    // Chuyển đổi định dạng dữ liệu
    protected $casts = [
        'amount' => 'decimal:2',
    ];

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
