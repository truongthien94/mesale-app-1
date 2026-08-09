<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

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

    public function scopePrunableCompleted(Builder $query, Carbon $cutoff): Builder
    {
        return $query
            ->where('status', self::STATUS_COMPLETED)
            ->where('created_at', '<=', $cutoff);
    }

    public function scopePrunableProcessing(Builder $query, Carbon $cutoff): Builder
    {
        return $query
            ->where('status', self::STATUS_PROCESSING)
            ->where('created_at', '<=', $cutoff);
    }
}
