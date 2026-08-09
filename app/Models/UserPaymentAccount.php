<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
     * Keep a database-enforced, cross-user identity for each payout destination.
     * Payment account names are intentionally excluded: the destination is the
     * method, provider and account number, not the mutable display name.
     */
    protected static function booted(): void
    {
        static::creating(function (self $account): void {
            $account->destination_hash = self::destinationHash(
                $account->payment_method,
                $account->bank_name,
                $account->account_number,
            );
        });

        static::updating(function (self $account): void {
            if ($account->isDirty(['payment_method', 'bank_name', 'account_number'])) {
                $account->destination_hash = self::destinationHash(
                    $account->payment_method,
                    $account->bank_name,
                    $account->account_number,
                );
            }
        });
    }

    public static function destinationHash(?string $paymentMethod, ?string $bankName, ?string $accountNumber): string
    {
        return hash('sha256', implode("\x1f", [
            self::normalizeComponent($paymentMethod),
            self::normalizeComponent($bankName),
            self::normalizeComponent($accountNumber, true),
        ]));
    }

    private static function normalizeComponent(?string $value, bool $removeWhitespace = false): string
    {
        $normalized = trim((string) $value);
        $normalized = preg_replace('/\s+/u', $removeWhitespace ? '' : ' ', $normalized) ?? $normalized;

        return function_exists('mb_strtolower')
            ? mb_strtolower($normalized, 'UTF-8')
            : strtolower($normalized);
    }

    /**
     * Mối quan hệ: Một tài khoản nhận tiền đã lưu thuộc về một Người dùng (User).
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
