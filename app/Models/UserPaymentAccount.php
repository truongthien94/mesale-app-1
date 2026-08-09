<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class UserPaymentAccount extends Model
{
    use HasFactory;

    // Danh sách các cột có thể điền thông tin (mass assignable)
    protected $fillable = [
        'user_id',
        'payment_method',
        'bank_name',
        'account_number',
        'account_name',
        'is_default',
    ];

    // Chuyển đổi định dạng dữ liệu
    protected $casts = [
        'is_default' => 'boolean',
    ];

    /**
     * Mối quan hệ: Một tài khoản nhận tiền đã lưu thuộc về một Người dùng (User).
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
