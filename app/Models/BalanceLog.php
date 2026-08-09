<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BalanceLog extends Model
{
    use HasFactory;

    // Các cột cho phép ghi dữ liệu hàng loạt
    protected $fillable = [
        'user_id',
        'amount_before',
        'amount_change',
        'amount_after',
        'type',
        'description'
    ];

    // Chuyển đổi kiểu dữ liệu
    protected $casts = [
        'amount_before' => 'decimal:2',
        'amount_change' => 'decimal:2',
        'amount_after' => 'decimal:2',
    ];

    /**
     * Mối quan hệ: Một bản ghi dòng tiền thuộc về một Người dùng (User).
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Helper ghi nhận lịch sử biến động số dư của thành viên.
     * Phương thức này nhận trực tiếp số dư trước và sau để đảm bảo tính toán luôn chuẩn xác,
     * tránh sai lệch số liệu do không đồng bộ dữ liệu ví.
     *
     * @param \App\Models\User $user
     * @param float $amountBefore Số dư trước khi đổi
     * @param float $amountChange Số tiền biến động (âm hoặc dương)
     * @param float $amountAfter Số dư sau khi đổi
     * @param string $type Loại biến động
     * @param string $description Mô tả chi tiết
     * @return \App\Models\BalanceLog
     */
    public static function write(User $user, float $amountBefore, float $amountChange, float $amountAfter, string $type, string $description)
    {
        return self::create([
            'user_id' => $user->id,
            'amount_before' => $amountBefore,
            'amount_change' => $amountChange,
            'amount_after' => $amountAfter,
            'type' => $type,
            'description' => $description
        ]);
    }
}
