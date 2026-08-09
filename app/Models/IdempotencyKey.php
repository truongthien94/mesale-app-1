<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IdempotencyKey extends Model
{
    public const STATUS_PROCESSING = 'processing';

    public const STATUS_COMPLETED = 'completed';

    protected $fillable = [
        'user_id',
        'operation',
        'key_hash',
        'request_hash',
        'status',
        'response_status',
        'response_body',
        'completed_at',
    ];

    protected $casts = [
        'response_status' => 'integer',
        'response_body' => 'encrypted:array',
        'completed_at' => 'datetime',
    ];
}
