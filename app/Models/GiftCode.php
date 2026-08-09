<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

/**
 * Model Giftcode (mã quà tặng nhập tay nhận thưởng vào ví).
 * Mỗi mã có thể cấu hình phần thưởng và bộ điều kiện sử dụng riêng biệt.
 */
class GiftCode extends Model
{
    use HasFactory;

    protected $table = 'gift_codes';

    protected $fillable = [
        'code',
        'title',
        'description',
        'reward_type',
        'reward_amount',
        'reward_min',
        'reward_max',
        'max_uses',
        'used_count',
        'per_user_limit',
        'starts_at',
        'expires_at',
        'require_verified_email',
        'min_total_cashback',
        'min_account_age_days',
        'new_user_within_days',
        'status',
    ];

    protected $casts = [
        'reward_amount' => 'decimal:2',
        'reward_min' => 'decimal:2',
        'reward_max' => 'decimal:2',
        'max_uses' => 'integer',
        'used_count' => 'integer',
        'per_user_limit' => 'integer',
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
        'require_verified_email' => 'boolean',
        'min_total_cashback' => 'decimal:2',
        'min_account_age_days' => 'integer',
        'new_user_within_days' => 'integer',
        'status' => 'boolean',
    ];

    /**
     * Quan hệ: Một mã Giftcode có nhiều lượt đổi.
     */
    public function redemptions()
    {
        return $this->hasMany(GiftCodeRedemption::class);
    }

    /**
     * Sinh một mã code ngẫu nhiên duy nhất chưa tồn tại trong hệ thống.
     *
     * @param int $length Độ dài phần ngẫu nhiên
     * @param string $prefix Tiền tố mã (mặc định GIFT)
     */
    public static function generateUniqueCode(int $length = 8, string $prefix = 'GIFT'): string
    {
        do {
            $code = $prefix . strtoupper(Str::random($length));
        } while (self::where('code', $code)->exists());

        return $code;
    }

    /**
     * Mã đã hết hạn hiệu lực hay chưa.
     */
    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    /**
     * Mã đã tới thời gian bắt đầu hiệu lực hay chưa.
     */
    public function hasStarted(): bool
    {
        return $this->starts_at === null || $this->starts_at->isPast();
    }

    /**
     * Mã đã sử dụng hết tổng lượt cho phép hay chưa.
     */
    public function isSoldOut(): bool
    {
        return $this->max_uses !== null && $this->used_count >= $this->max_uses;
    }

    /**
     * Mã có đang ở trạng thái sẵn sàng cho người dùng đổi không (chưa xét điều kiện riêng của từng user).
     */
    public function isRedeemable(): bool
    {
        return $this->status && $this->hasStarted() && !$this->isExpired() && !$this->isSoldOut();
    }

    /**
     * Số lượt còn lại của mã (null nếu không giới hạn).
     */
    public function remainingUses(): ?int
    {
        if ($this->max_uses === null) {
            return null;
        }

        return max(0, $this->max_uses - $this->used_count);
    }

    /**
     * Tính ra số tiền thưởng cụ thể của mã (random nếu cấu hình theo khoảng).
     */
    public function resolveRewardAmount(): int
    {
        if ($this->reward_type === 'random') {
            $min = (int) round($this->reward_min);
            $max = (int) round($this->reward_max);
            if ($max <= $min) {
                return max(0, $min);
            }
            return random_int($min, $max);
        }

        return (int) round($this->reward_amount);
    }

    /**
     * Nhãn hiển thị phần thưởng (dùng cho cả admin lẫn giao diện khách).
     */
    public function getRewardLabelAttribute(): string
    {
        if ($this->reward_type === 'random') {
            return number_format($this->reward_min, 0, ',', '.') . 'đ - ' . number_format($this->reward_max, 0, ',', '.') . 'đ';
        }

        return number_format($this->reward_amount, 0, ',', '.') . 'đ';
    }
}
