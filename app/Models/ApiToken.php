<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * Model quản lý Personal Access Token của Open API.
 *
 * Cơ chế bảo mật:
 *  - Token gốc (plaintext) được sinh ngẫu nhiên 64 ký tự, chỉ trả về client duy nhất một lần.
 *  - Database chỉ lưu bản băm SHA-256 (không thể đảo ngược) nên kể cả khi lộ DB cũng không lấy được token thật.
 *  - Hỗ trợ thời hạn (expires_at) và thu hồi (xóa bản ghi) để vô hiệu hóa phiên đăng nhập tức thì.
 */
class ApiToken extends Model
{
    protected $table = 'api_tokens';

    protected $fillable = [
        'user_id',
        'name',
        'token',
        'last_ip',
        'last_used_at',
        'expires_at',
    ];

    protected $casts = [
        'last_used_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    /**
     * Quan hệ: Token thuộc về một thành viên.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Băm token gốc bằng thuật toán SHA-256 để lưu/đối chiếu trong database.
     */
    public static function hashToken(string $plainText): string
    {
        return hash('sha256', $plainText);
    }

    /**
     * Sinh một token mới cho thành viên và trả về [chuỗi token gốc, bản ghi ApiToken].
     * Chuỗi token gốc chỉ tồn tại tại thời điểm này, cần trả ngay về client.
     *
     * @param  int|null  $ttlDays  Số ngày hiệu lực (null hoặc <= 0 nghĩa là vĩnh viễn)
     * @return array{0: string, 1: \App\Models\ApiToken}
     */
    public static function generateFor(User $user, ?string $name = null, ?int $ttlDays = null, ?string $ip = null): array
    {
        // Sinh chuỗi ngẫu nhiên entropy cao làm token gốc
        $plainText = Str::random(64);

        $token = self::create([
            'user_id' => $user->id,
            'name' => $name ? Str::limit(strip_tags($name), 100, '') : null,
            'token' => self::hashToken($plainText),
            'last_ip' => $ip,
            'last_used_at' => now(),
            'expires_at' => ($ttlDays && $ttlDays > 0) ? now()->addDays($ttlDays) : null,
        ]);

        return [$plainText, $token];
    }

    /**
     * Tìm bản ghi token còn hiệu lực dựa trên chuỗi token gốc client gửi lên.
     * Trả về null nếu token không tồn tại hoặc đã hết hạn.
     */
    public static function findValid(string $plainText): ?self
    {
        if (trim($plainText) === '') {
            return null;
        }

        $token = self::where('token', self::hashToken($plainText))->first();

        if (!$token) {
            return null;
        }

        // Token đã hết hạn: tự động dọn dẹp và coi như không hợp lệ
        if ($token->expires_at && $token->expires_at->isPast()) {
            $token->delete();
            return null;
        }

        return $token;
    }
}
