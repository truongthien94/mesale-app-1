<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DailyCheckin extends Model
{
    use HasFactory;

    // Danh sách các cột điền thông tin
    protected $fillable = [
        'user_id',
        'coins_earned',
        'streak_days',
        'checked_in_date'
    ];

    // Chuyển đổi định dạng dữ liệu
    protected $casts = [
        'coins_earned' => 'decimal:2',
        'checked_in_date' => 'date',
    ];

    /**
     * Mối quan hệ: Điểm danh của một Người dùng (User).
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
