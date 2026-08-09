<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Withdrawal extends Model
{
    use HasFactory;

    // Danh sách các cột có thể điền thông tin (mass assignable)
    protected $fillable = [
        'user_id',
        'code',
        'amount',
        'fee',
        'real_amount',
        'payment_method',
        'account_number',
        'account_name',
        'bank_name',
        'status',
        'notes',
        'processed_at'
    ];

    // Chuyển đổi định dạng dữ liệu
    protected $casts = [
        'amount' => 'decimal:2',
        'fee' => 'decimal:2',
        'real_amount' => 'decimal:2',
        'processed_at' => 'datetime',
    ];

    /**
     * Mối quan hệ: Một yêu cầu rút tiền thuộc về một Người dùng (User).
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
