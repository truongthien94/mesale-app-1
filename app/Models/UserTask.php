<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserTask extends Model
{
    protected $fillable = [
        'user_id', 'task_id', 'progress', 'status',
        'period_key', 'completed_at', 'claimed_at',
        // Luồng thành viên tự xác nhận hoàn thành nhiệm vụ thủ công
        'submitted_at', 'submit_note', 'reject_reason', 'reviewed_at', 'reviewed_by',
    ];

    protected $casts = [
        'progress'     => 'integer',
        'completed_at' => 'datetime',
        'claimed_at'   => 'datetime',
        'submitted_at' => 'datetime',
        'reviewed_at'  => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    /**
     * Quản trị viên đã kiểm duyệt yêu cầu xác nhận hoàn thành của thành viên.
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function getStatusLabel(): string
    {
        return match ($this->status) {
            'in_progress' => 'Đang thực hiện',
            'pending'     => 'Chờ duyệt',
            'completed'   => 'Hoàn thành (chưa nhận)',
            'claimed'     => 'Đã nhận thưởng',
            default       => $this->status,
        };
    }

    public function getStatusBadgeClass(): string
    {
        return match ($this->status) {
            'in_progress' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
            'pending'     => 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400',
            'completed'   => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
            'claimed'     => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
            default       => 'bg-gray-100 text-gray-700 dark:bg-slate-800 dark:text-gray-400',
        };
    }

    public function getProgressPercent(): int
    {
        if (!$this->task || $this->task->target_count <= 0) return 0;
        return min(100, (int) round($this->progress / $this->task->target_count * 100));
    }
}
