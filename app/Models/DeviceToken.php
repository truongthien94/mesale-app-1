<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model token thiết bị dùng để gửi thông báo đẩy (Push Notification) qua FCM/APNs.
 */
class DeviceToken extends Model
{
    protected $table = 'device_tokens';

    protected $fillable = [
        'user_id',
        'token',
        'platform',
        'device_name',
        'last_used_at',
    ];

    protected $casts = [
        'last_used_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
