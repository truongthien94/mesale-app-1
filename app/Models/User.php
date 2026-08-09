<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// [BẢO MẬT] Các cột nhạy cảm về tiền và đặc quyền KHÔNG được đưa vào danh sách mass-assignment
// nhằm loại bỏ hoàn toàn nguy cơ leo thang đặc quyền / tự nâng số dư nếu về sau có đoạn code
// lỡ dùng User::create($request->all()) hoặc ->update($request->all()).
// Các cột bị loại bỏ có chủ đích (chỉ được gán tường minh qua thuộc tính hoặc forceCreate trong seeder):
//   balance, total_cashback, total_referral_earned, total_withdrawn, role, role_id, status
// Lưu ý: các luồng đăng ký (register/google/api) không set các cột này nữa mà dựa vào giá trị
// mặc định của database (balance=0, total_*=0, role='user', status='active').
#[Fillable([
    'name',
    'email',
    'password',
    'phone',
    'locale',
    'currency',
    'avatar',
    'referral_code',
    'referred_by',
    // Số lượt click vào link giới thiệu
    'referral_clicks',
    // email_verified_at lưu thời gian xác minh email thành công
    'email_verified_at',
    // google_id liên kết với tài khoản đăng nhập Google
    'google_id',
    'apple_id',
    // google2fa_secret lưu khoá bí mật 2FA của Google Authenticator
    'google2fa_secret',
    // google2fa_enabled lưu trạng thái bật/tắt xác thực 2FA Google
    'google2fa_enabled',
    // email_otp_enabled lưu trạng thái bật/tắt xác thực mã OTP qua email
    'email_otp_enabled',
    // otp_code lưu mã OTP email tạm thời
    'otp_code',
    // otp_expires_at lưu thời hạn của mã OTP email
    'otp_expires_at',
    // utm_source lưu nguồn chiến dịch tiếp thị khi đăng ký tài khoản
    'utm_source',
    // ip_address lưu địa chỉ IP khi đăng ký tài khoản
    'ip_address',
    // user_agent lưu thông tin thiết bị khi đăng ký
    'user_agent',
    // country lưu quốc gia khi đăng ký
    'country',
    // last_seen_at lưu thời gian hoạt động (online) gần nhất
    'last_seen_at',
    // bot_zalo_chat_id liên kết tài khoản với Zalo Bot
    'bot_zalo_chat_id',
    // bot_telegram_chat_id liên kết tài khoản với Telegram Bot
    'bot_telegram_chat_id',
])]
#[Hidden(['password', 'remember_token', 'google_id', 'apple_id', 'google2fa_secret', 'otp_code'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     * Chuyển đổi định dạng dữ liệu cho các trường database.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'balance' => 'decimal:2',
            'total_cashback' => 'decimal:2',
            'total_referral_earned' => 'decimal:2',
            'total_withdrawn' => 'decimal:2',
            // google2fa_enabled tự động ép kiểu về boolean
            'google2fa_enabled' => 'boolean',
            // email_otp_enabled tự động ép kiểu về boolean
            'email_otp_enabled' => 'boolean',
            // otp_expires_at tự động ép kiểu về đối tượng Carbon datetime
            'otp_expires_at' => 'datetime',
            // last_seen_at tự động ép kiểu về đối tượng Carbon datetime
            'last_seen_at' => 'datetime',
            // Số lượt click vào link giới thiệu
            'referral_clicks' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (User $user) {
            // Đọc trực tiếp giá trị thô trong mảng attributes, KHÔNG dùng $user->api_token
            // để tránh kích hoạt accessor getApiTokenAttribute() ngay giữa lúc đang tạo tài khoản.
            if (empty($user->getAttributes()['api_token'] ?? null)) {
                $user->setAttribute('api_token', 'sk_live_' . \Illuminate\Support\Str::random(32));
            }

            // Tự động kiểm tra và gán ID an toàn = MAX(id) + 1 khi tạo tài khoản mới.
            // Giải quyết dứt điểm lỗi trùng ID (Duplicate entry '64' for key PRIMARY) 
            // khi con trỏ AUTO_INCREMENT của MariaDB trên các server bị lệch/hỏng do restore DB.
            if (empty($user->id) || static::where('id', $user->id)->exists()) {
                $maxId = (int) static::max('id');
                $user->id = $maxId + 1;
            }
        });

        // Preserve referral history before the user's FK cascade can run.
        static::deleting(function (User $user): void {
            if (Schema::hasTable('referrals')) {
                DB::table('referrals')
                    ->where('referrer_id', $user->getKey())
                    ->update(['referrer_id' => null]);
            }

            if (Schema::hasTable('referral_commissions')) {
                DB::table('referral_commissions')
                    ->where('referrer_id', $user->getKey())
                    ->update(['referrer_id' => null]);
            }
        });
    }

    /**
     * Kiểm tra xem user có phải là admin không.
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Chuẩn hoá số điện thoại về dạng thống nhất trước khi lưu hoặc tra cứu.
     * Giải thích logic: Loại bỏ mọi ký tự không phải chữ số (khoảng trắng, dấu chấm, gạch ngang),
     * sau đó quy đổi đầu số quốc tế của Việt Nam (+84 / 84) về dạng bắt đầu bằng số 0.
     */
    public static function normalizePhone(?string $phone): ?string
    {
        if ($phone === null) {
            return null;
        }

        // Chỉ giữ lại các chữ số trong chuỗi người dùng nhập vào
        $digits = preg_replace('/\D+/', '', $phone);

        if ($digits === '') {
            return null;
        }

        // Quy đổi đầu số quốc tế 84xxxxxxxxx về dạng nội địa 0xxxxxxxxx
        if (str_starts_with($digits, '84') && strlen($digits) >= 10) {
            $digits = '0' . substr($digits, 2);
        }

        return $digits;
    }

    /**
     * Tìm tài khoản theo thông tin đăng nhập là Email HOẶC Số điện thoại.
     * Giải thích logic: Nếu chuỗi nhập vào có dạng email thì tra cứu theo cột email (không phân biệt hoa thường),
     * ngược lại chuẩn hoá thành số điện thoại rồi dò tìm cả các định dạng cũ (+84, 84, có khoảng trắng) đã lưu trước đây.
     */
    public static function findByLogin(?string $login): ?self
    {
        $login = trim((string) $login);

        if ($login === '') {
            return null;
        }

        // Trường hợp người dùng nhập địa chỉ email
        if (filter_var($login, FILTER_VALIDATE_EMAIL)) {
            return static::whereRaw('LOWER(email) = ?', [mb_strtolower($login)])->first();
        }

        $normalized = static::normalizePhone($login);

        if ($normalized === null) {
            return null;
        }

        // Dò tìm số điện thoại theo các định dạng có thể đã được lưu trong dữ liệu cũ
        $candidates = array_unique(array_filter([
            $normalized,
            $login,
            '+84' . ltrim($normalized, '0'),
            '84' . ltrim($normalized, '0'),
        ]));

        return static::whereNotNull('phone')->whereIn('phone', $candidates)->first();
    }

    /**
     * Quan hệ: Một User có nhiều lịch sử hoàn tiền.
     */
    public function cashbackHistories()
    {
        return $this->hasMany(CashbackHistory::class);
    }

    /**
     * Quan hệ: Một User có nhiều yêu cầu rút tiền.
     */
    public function withdrawals()
    {
        return $this->hasMany(Withdrawal::class);
    }

    /**
     * Quan hệ: Lấy thông tin người đã giới thiệu User này (F0 của User).
     */
    public function referrer()
    {
        return $this->belongsTo(User::class, 'referred_by');
    }

    /**
     * Quan hệ: Danh sách những người được User này giới thiệu trực tiếp (F1).
     */
    public function referredUsers()
    {
        return $this->hasMany(User::class, 'referred_by');
    }

    /**
     * Quan hệ: Danh sách F1 trong bảng referrals.
     */
    public function referrals()
    {
        return $this->hasMany(Referral::class, 'referrer_id');
    }

    /**
     * Quan hệ: Hoa hồng tiếp thị liên kết nhận được.
     */
    public function referralCommissions()
    {
        return $this->hasMany(ReferralCommission::class, 'referrer_id');
    }

    /**
     * Quan hệ: Nhật ký điểm danh hằng ngày.
     */
    public function dailyCheckins()
    {
        return $this->hasMany(DailyCheckin::class);
    }

    /**
     * Quan hệ: Nhật ký hoạt động tài khoản.
     */
    public function activityLogs()
    {
        return $this->hasMany(ActivityLog::class);
    }

    /**
     * Quan hệ: Danh sách thông báo của User.
     */
    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    /**
     * Quan hệ: Một User có nhiều sản phẩm đã lưu mua sau.
     */
    public function savedProducts()
    {
        return $this->hasMany(SavedProduct::class);
    }

    /**
     * Quan hệ: Một User có nhiều tài khoản nhận tiền đã lưu (sổ tài khoản rút tiền nhanh).
     */
    public function paymentAccounts()
    {
        return $this->hasMany(UserPaymentAccount::class);
    }

    /**
     * Quan hệ belongsTo: Liên kết người dùng với vai trò phân quyền (Role).
     */
    public function roleRelation()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    /**
     * Kiểm tra quyền hạn cụ thể của tài khoản Admin cấp dưới.
     * @param string $permission Tên quyền cần kiểm tra (ví dụ: 'manage_settings')
     * @return bool Trả về true nếu có quyền hoặc là Super Admin, ngược lại false.
     */
    public function hasPermission(string $permission): bool
    {
        // 1. Nếu không phải là admin, mặc định không có bất kỳ quyền quản trị nào
        if ($this->role !== 'admin') {
            return false;
        }

        // 2. Nếu là admin và không được gán role_id cụ thể -> Super Admin (toàn quyền)
        if ($this->role_id === null) {
            return true;
        }

        // 3. Đọc danh sách quyền được phân trong vai trò (Role)
        $role = $this->roleRelation;
        if (!$role) {
            return false;
        }

        // Kiểm tra xem quyền cần check có nằm trong mảng permissions của vai trò đó không
        return in_array($permission, $role->permissions ?? []);
    }

    /**
     * Kiểm tra xem người dùng có đang hoạt động trực tuyến (online) hay không.
     * Online được định nghĩa là thời gian ghi nhận hoạt động cuối cùng (last_seen_at) trong vòng 5 phút gần nhất.
     *
     * @return bool
     */
    public function isOnline(): bool
    {
        return $this->last_seen_at && $this->last_seen_at->gt(now()->subMinutes(5));
    }

    /**
     * Lấy thông tin thiết bị và trình duyệt của người dùng khi đăng ký.
     * Giải thích: Phân tích chuỗi user_agent thô được lưu khi đăng ký để hiển thị ở trang quản lý thành viên 
     * giúp admin dễ dàng phát hiện các trường hợp tạo nhiều tài khoản (clone) trên cùng một thiết bị/trình duyệt.
     *
     * @return string
     */
    public function getRegisterDeviceAttribute(): string
    {
        $userAgent = $this->user_agent;
        if (empty($userAgent)) {
            return __('Thiết bị không xác định');
        }

        $os = __('OS không xác định');

        // Nhận diện hệ điều hành phổ biến
        if (preg_match('/iphone/i', $userAgent)) {
            $os = 'iPhone (iOS)';
        } elseif (preg_match('/ipad/i', $userAgent)) {
            $os = 'iPad (iPadOS)';
        } elseif (preg_match('/android/i', $userAgent)) {
            $os = 'Android';
        } elseif (preg_match('/macintosh|mac os x/i', $userAgent)) {
            $os = 'macOS';
        } elseif (preg_match('/windows|win32/i', $userAgent)) {
            $os = 'Windows';
        } elseif (preg_match('/linux/i', $userAgent)) {
            $os = 'Linux';
        }

        $browser = '';
        // Nhận diện trình duyệt phổ biến
        if (preg_match('/chrome/i', $userAgent) && !preg_match('/edge|edg/i', $userAgent) && !preg_match('/opr/i', $userAgent)) {
            $browser = 'Chrome';
        } elseif (preg_match('/safari/i', $userAgent) && !preg_match('/chrome/i', $userAgent)) {
            $browser = 'Safari';
        } elseif (preg_match('/firefox/i', $userAgent)) {
            $browser = 'Firefox';
        } elseif (preg_match('/edge|edg/i', $userAgent)) {
            $browser = 'Microsoft Edge';
        } elseif (preg_match('/opr/i', $userAgent)) {
            $browser = 'Opera';
        } elseif (preg_match('/fb_iab|fbav/i', $userAgent)) {
            $browser = 'Facebook App';
        }

        return $browser ? "{$os} ({$browser})" : $os;
    }

    /**
     * Tự động sinh hoặc đọc mã API Token cá nhân của người dùng.
     *
     * [QUAN TRỌNG] Accessor này TUYỆT ĐỐI KHÔNG được gọi $this->saveQuietly().
     * Lý do: saveQuietly() trên một model chưa tồn tại sẽ thực thi luôn câu lệnh INSERT.
     * Trước đây, sự kiện `creating` có đọc thuộc tính api_token nên vô tình kích hoạt accessor này,
     * khiến mỗi lần tạo tài khoản bị chèn 2 lần: lần 1 do accessor (thành công), lần 2 do luồng
     * create() ban đầu (báo lỗi trùng khoá email/số điện thoại/mã giới thiệu).
     * Với tài khoản đã tồn tại, chỉ cập nhật đúng cột api_token thay vì lưu toàn bộ model,
     * tránh việc chỉ đọc token mà lại ghi đè các thay đổi khác đang có trên model.
     */
    public function getApiTokenAttribute($value)
    {
        if (empty($value)) {
            $newToken = 'sk_live_' . \Illuminate\Support\Str::random(32);
            $this->attributes['api_token'] = $newToken;

            // Tài khoản chưa được lưu vào CSDL: chỉ gán vào bộ nhớ, việc ghi xuống DB do lệnh tạo tài khoản đảm nhiệm
            if ($this->exists) {
                try {
                    // Cập nhật riêng cột api_token (không bắn sự kiện, không đụng tới updated_at)
                    static::query()->toBase()
                        ->where($this->getKeyName(), $this->getKey())
                        ->update(['api_token' => $newToken]);

                    // Đồng bộ lại giá trị gốc để model không bị coi là "dirty" ở cột này
                    $this->syncOriginalAttribute('api_token');
                } catch (\Throwable $e) {
                    \Log::error('Lỗi lưu API Token tự sinh cho User ID ' . $this->getKey() . ': ' . $e->getMessage());
                }
            }

            return $newToken;
        }
        return $value;
    }

    /**
     * Tạo mới lại mã API Token cá nhân khi người dùng muốn hủy token cũ.
     */
    public function regenerateApiToken(): string
    {
        $newToken = 'sk_live_' . \Illuminate\Support\Str::random(32);
        $this->forceFill(['api_token' => $newToken])->save();
        return $newToken;
    }
}
