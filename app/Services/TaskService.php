<?php

namespace App\Services;

use App\Models\Task;
use App\Models\UserTask;
use App\Models\User;
use App\Models\BalanceLog;
use App\Models\Notification;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\DB;

class TaskService
{
    /**
     * Xác định khoảng thời gian lọc dữ liệu hành động cho nhiệm vụ.
     * - daily: Từ 00:00:00 đến 23:59:59 của ngày hôm nay.
     * - weekly: Từ đầu tuần đến cuối tuần hiện tại.
     * - one_time: Dựa trên ngày bắt đầu (start_at) và ngày kết thúc (end_at) của nhiệm vụ.
     * 
     * @param Task $task Đối tượng nhiệm vụ
     * @return array Mảng chứa [$startDate, $endDate] dưới dạng Carbon object hoặc null
     */
    private function getTaskTimeRange(Task $task): array
    {
        $startDate = null;
        $endDate = null;

        if ($task->type === 'daily') {
            $startDate = now()->startOfDay();
            $endDate = now()->endOfDay();
        } elseif ($task->type === 'weekly') {
            $startDate = now()->startOfWeek();
            $endDate = now()->endOfWeek();
        } else {
            // Đối với nhiệm vụ một lần, lọc theo thời gian bắt đầu và kết thúc của nhiệm vụ
            $startDate = $task->start_at;
            $endDate = $task->end_at;
        }

        return [$startDate, $endDate];
    }

    /**
     * Tính progress thực tế của user cho một task dựa trên action type.
     * Dùng cho auto-verify (profile, referral, cashback, checkin, withdraw, save_product).
     */
    public function computeProgress(User $user, Task $task): int
    {
        return match ($task->action) {
            'profile'      => $this->computeProfileProgress($user),
            'referral'     => $this->computeReferralProgress($user, $task),
            'cashback'     => $this->computeCashbackProgress($user, $task),
            'checkin'      => $this->computeCheckinProgress($user, $task),
            'withdraw'     => $this->computeWithdrawProgress($user, $task),
            'save_product' => $this->computeSaveProductProgress($user, $task),
            default        => 0, // custom: admin tự xác nhận
        };
    }

    /**
     * Tính tiến trình hoàn thành nhiệm vụ hoàn thiện hồ sơ.
     * Không lọc theo thời gian vì đây là trạng thái hiện tại của tài khoản.
     */
    private function computeProfileProgress(User $user): int
    {
        $score = 0;
        if (!empty($user->name)) $score++;
        if (!empty($user->phone)) $score++;
        if (!empty($user->email_verified_at)) $score++;
        return min($score, 3);
    }

    /**
     * Tính số lượng thành viên cấp dưới được giới thiệu trong thời gian diễn ra nhiệm vụ.
     * Nếu yêu cầu đơn hàng, các đơn hàng của thành viên cấp dưới cũng phải được duyệt trong khoảng thời gian này.
     */
    private function computeReferralProgress(User $user, Task $task): int
    {
        [$startDate, $endDate] = $this->getTaskTimeRange($task);
        $query = User::where('referred_by', $user->id);

        // Lọc theo thời gian đăng ký của thành viên được giới thiệu
        if ($startDate) {
            $query->where('created_at', '>=', $startDate);
        }
        if ($endDate) {
            $query->where('created_at', '<=', $endDate);
        }

        // Lọc theo yêu cầu thành viên được giới thiệu phải phát sinh đơn hàng thành công
        if ($task->referral_require_order) {
            $query->whereHas('cashbackHistories', function ($q) use ($startDate, $endDate, $task) {
                $q->where('status', 'approved');
                // Nếu có cấu hình số tiền hoàn tối thiểu, lọc các đơn hàng của bạn bè đạt mức này
                if ($task->min_order_amount > 0) {
                    $q->where('cashback_amount', '>=', $task->min_order_amount);
                }
                if ($startDate) {
                    $q->where('approved_at', '>=', $startDate);
                }
                if ($endDate) {
                    $q->where('approved_at', '<=', $endDate);
                }
            });
        }

        return $query->count();
    }

    /**
     * Tính số lượng đơn hàng hoàn tiền đã được duyệt thành công trong thời gian diễn ra nhiệm vụ.
     * Cập nhật thêm điều kiện lọc theo số tiền hoàn tối thiểu (min_order_amount)
     * để chống tình trạng thành viên lạm dụng mua các đơn hàng có tiền hoàn cực nhỏ.
     */
    private function computeCashbackProgress(User $user, Task $task): int
    {
        [$startDate, $endDate] = $this->getTaskTimeRange($task);
        $query = \App\Models\CashbackHistory::where('user_id', $user->id)
            ->where('status', 'approved');

        // Chỉ tính các đơn hàng có số tiền hoàn (cashback_amount) tối thiểu bằng mức cấu hình của nhiệm vụ
        if ($task->min_order_amount > 0) {
            $query->where('cashback_amount', '>=', $task->min_order_amount);
        }

        // Lọc theo thời gian đơn hàng được phê duyệt hoàn tiền thành công
        if ($startDate) {
            $query->where('approved_at', '>=', $startDate);
        }
        if ($endDate) {
            $query->where('approved_at', '<=', $endDate);
        }

        return $query->count();
    }

    /**
     * Tính số lượt điểm danh của user trong thời gian diễn ra nhiệm vụ.
     */
    private function computeCheckinProgress(User $user, Task $task): int
    {
        [$startDate, $endDate] = $this->getTaskTimeRange($task);
        $query = \App\Models\DailyCheckin::where('user_id', $user->id);

        // Lọc theo thời gian thực hiện điểm danh
        if ($startDate) {
            $query->where('created_at', '>=', $startDate);
        }
        if ($endDate) {
            $query->where('created_at', '<=', $endDate);
        }

        return $query->count();
    }

    /**
     * Kiểm tra user có phát sinh yêu cầu rút tiền (chờ duyệt hoặc đã duyệt) trong thời gian diễn ra nhiệm vụ hay không.
     */
    private function computeWithdrawProgress(User $user, Task $task): int
    {
        [$startDate, $endDate] = $this->getTaskTimeRange($task);
        $query = \App\Models\Withdrawal::where('user_id', $user->id)
            ->whereIn('status', ['approved', 'pending']);

        // Lọc theo thời gian tạo yêu cầu rút tiền
        if ($startDate) {
            $query->where('created_at', '>=', $startDate);
        }
        if ($endDate) {
            $query->where('created_at', '<=', $endDate);
        }

        return $query->count() > 0 ? 1 : 0;
    }

    /**
     * Tính số lượng sản phẩm được lưu trong thời gian diễn ra nhiệm vụ.
     */
    private function computeSaveProductProgress(User $user, Task $task): int
    {
        [$startDate, $endDate] = $this->getTaskTimeRange($task);
        $query = \App\Models\SavedProduct::where('user_id', $user->id);

        // Lọc theo thời gian lưu sản phẩm
        if ($startDate) {
            $query->where('created_at', '>=', $startDate);
        }
        if ($endDate) {
            $query->where('created_at', '<=', $endDate);
        }

        return $query->count();
    }

    /**
     * Lấy hoặc tạo UserTask cho user trong period hiện tại.
     */
    public function getOrCreateUserTask(User $user, Task $task): UserTask
    {
        $periodKey = $task->getPeriodKey();

        return UserTask::firstOrCreate(
            [
                'user_id'    => $user->id,
                'task_id'    => $task->id,
                'period_key' => $periodKey,
            ],
            [
                'progress' => 0,
                'status'   => 'in_progress',
            ]
        );
    }

    /**
     * Đồng bộ progress của user cho một task (auto-verify actions).
     * Trả về UserTask đã được cập nhật.
     */
    public function syncProgress(User $user, Task $task): UserTask
    {
        if ($task->action === 'custom') {
            return $this->getOrCreateUserTask($user, $task);
        }

        $userTask = $this->getOrCreateUserTask($user, $task);

        if ($userTask->status === 'claimed') {
            return $userTask;
        }

        $progress = $this->computeProgress($user, $task);
        $newProgress = min($progress, $task->target_count);
        $wasCompleted = $userTask->status === 'completed';

        $updateData = ['progress' => $newProgress];
        if ($newProgress >= $task->target_count && $userTask->status === 'in_progress') {
            $updateData['status'] = 'completed';
            $updateData['completed_at'] = now();
        }

        $userTask->update($updateData);
        return $userTask->fresh();
    }

    /**
     * User nhận phần thưởng nhiệm vụ đã hoàn thành.
     * Chạy trong DB transaction với lockForUpdate.
     */
    public function claimReward(User $user, Task $task): array
    {
        return DB::transaction(function () use ($user, $task) {
            $periodKey = $task->getPeriodKey();

            $userTask = UserTask::where('user_id', $user->id)
                ->where('task_id', $task->id)
                ->where('period_key', $periodKey)
                ->lockForUpdate()
                ->first();

            if (!$userTask) {
                return ['success' => false, 'message' => __('Bạn chưa bắt đầu nhiệm vụ này.')];
            }

            if ($userTask->status === 'claimed') {
                return ['success' => false, 'message' => __('Bạn đã nhận phần thưởng nhiệm vụ này rồi.')];
            }

            // Luôn re-verify với dữ liệu thực tế tại thời điểm nhận thưởng (chống gian lận)
            // Dùng fresh() để đọc từ DB trong transaction, tránh stale data từ session
            if ($task->action !== 'custom') {
                $freshUser = $user->fresh();
                $progress = $this->computeProgress($freshUser, $task);
                if ($progress >= $task->target_count) {
                    // Đảm bảo status = completed nếu chưa đúng
                    if ($userTask->status !== 'completed') {
                        $userTask->update([
                            'progress'     => $task->target_count,
                            'status'       => 'completed',
                            'completed_at' => now(),
                        ]);
                    }
                } else {
                    // Điều kiện không còn đủ → thu hồi và từ chối
                    $userTask->update([
                        'progress'     => $progress,
                        'status'       => 'in_progress',
                        'completed_at' => null,
                    ]);
                    return ['success' => false, 'message' => __('Nhiệm vụ chưa hoàn thành. Tiến độ: :progress/:target', [
                        'progress' => $progress,
                        'target'   => $task->target_count,
                    ])];
                }
            } elseif ($userTask->status !== 'completed') {
                return ['success' => false, 'message' => __('Nhiệm vụ chưa được xác nhận bởi quản trị viên.')];
            }

            // Cộng phần thưởng vào ví
            $lockedUser = User::where('id', $user->id)->lockForUpdate()->first();
            $oldBalance = $lockedUser->balance;
            $newBalance = $oldBalance + $task->reward_amount;

            // balance được gán tường minh (không mass-assign) vì cột này đã bị loại khỏi $fillable vì lý do bảo mật.
            $lockedUser->balance = $newBalance;
            $lockedUser->save();

            BalanceLog::create([
                'user_id'       => $lockedUser->id,
                'amount_before' => $oldBalance,
                'amount_change' => $task->reward_amount,
                'amount_after'  => $newBalance,
                'type'          => 'task_reward',
                'description'   => __('Nhận thưởng nhiệm vụ: :title (+:amount đ)', [
                    'title'  => $task->title,
                    'amount' => number_format($task->reward_amount),
                ]),
            ]);

            $userTask->update([
                'status'     => 'claimed',
                'claimed_at' => now(),
            ]);

            // Gửi thông báo trong ứng dụng
            Notification::create([
                'user_id' => $lockedUser->id,
                'title'   => __('Nhận thưởng nhiệm vụ thành công!'),
                'content' => __('Bạn đã hoàn thành nhiệm vụ ":title" và nhận được :amount đ vào ví.', [
                    'title'  => $task->title,
                    'amount' => number_format($task->reward_amount),
                ]),
                'type'    => 'personal',
                'is_read' => false,
            ]);

            ActivityLog::log(__('Nhận thưởng nhiệm vụ: :title (+:amount đ)', [
                'title'  => $task->title,
                'amount' => number_format($task->reward_amount),
            ]), $lockedUser->id);

            return [
                'success' => true,
                'message' => __('Nhận thưởng thành công! +:amount đ đã được cộng vào ví.', [
                    'amount' => number_format($task->reward_amount),
                ]),
                'amount'  => $task->reward_amount,
            ];
        });
    }

    /**
     * Thành viên tự xác nhận đã hoàn thành nhiệm vụ loại Tùy chỉnh (thủ công).
     *
     * Yêu cầu được đưa vào trạng thái 'pending' để chờ Admin kiểm duyệt trong tab Tiến độ.
     * Chạy trong Database Transaction kèm lockForUpdate nhằm chống việc gửi trùng nhiều lần
     * do người dùng bấm nút liên tục hoặc gửi nhiều request song song.
     */
    public function submitCustomTask(User $user, Task $task, ?string $note = null): array
    {
        if ($task->action !== 'custom') {
            return ['success' => false, 'message' => __('Nhiệm vụ này được hệ thống tự động ghi nhận, không cần gửi xác nhận.')];
        }

        $result = DB::transaction(function () use ($user, $task, $note) {
            $periodKey = $task->getPeriodKey();

            $userTask = UserTask::where('user_id', $user->id)
                ->where('task_id', $task->id)
                ->where('period_key', $periodKey)
                ->lockForUpdate()
                ->first();

            // Trường hợp thành viên chưa từng mở trang nhiệm vụ thì tạo mới bản ghi tiến độ
            if (!$userTask) {
                $userTask = UserTask::create([
                    'user_id'    => $user->id,
                    'task_id'    => $task->id,
                    'period_key' => $periodKey,
                    'progress'   => 0,
                    'status'     => 'in_progress',
                ]);
            }

            if ($userTask->status === 'pending') {
                return ['success' => false, 'message' => __('Yêu cầu của bạn đang chờ quản trị viên duyệt, vui lòng đợi thêm.')];
            }

            if ($userTask->status === 'completed') {
                return ['success' => false, 'message' => __('Nhiệm vụ đã được duyệt, bạn có thể nhận thưởng ngay.')];
            }

            if ($userTask->status === 'claimed') {
                return ['success' => false, 'message' => __('Bạn đã nhận phần thưởng của nhiệm vụ này rồi.')];
            }

            $userTask->update([
                'status'        => 'pending',
                'submitted_at'  => now(),
                'submit_note'   => $note,
                'reject_reason' => null, // Xóa lý do từ chối của lần gửi trước
                'reviewed_at'   => null,
                'reviewed_by'   => null,
            ]);

            ActivityLog::log("Gửi yêu cầu xác nhận hoàn thành nhiệm vụ '{$task->title}' (ID: {$task->id})", $user->id);

            return [
                'success' => true,
                'message' => __('Đã gửi yêu cầu xác nhận! Quản trị viên sẽ kiểm tra và duyệt trong thời gian sớm nhất.'),
            ];
        });

        // Báo Telegram cho Admin biết có yêu cầu mới cần kiểm duyệt.
        // Đặt ngoài Database Transaction để không giữ khóa dòng trong lúc ghi hàng đợi gửi tin.
        if ($result['success'] ?? false) {
            try {
                // Telegram gửi tin ở chế độ parse_mode = HTML nên mọi ký tự < > & trong dữ liệu động
                // bắt buộc phải được mã hóa, nếu không Telegram sẽ từ chối gửi toàn bộ tin nhắn
                $escape = static fn ($value) => htmlspecialchars((string) $value, ENT_NOQUOTES, 'UTF-8');

                \App\Models\Setting::sendTelegramTemplate('telegram_template_task_submitted', [
                    'name'          => $escape($user->name),
                    'email'         => $escape($user->email),
                    'task_title'    => $escape($task->title),
                    'task_type'     => $escape($task->getTypeLabel()),
                    'reward_amount' => number_format($task->reward_amount),
                    'note'          => $escape($note ?: 'Không có ghi chú'),
                ]);
            } catch (\Exception $e) {
                \Log::error('Lỗi gửi Telegram yêu cầu xác nhận nhiệm vụ: ' . $e->getMessage());
            }
        }

        return $result;
    }

    /**
     * Admin xác nhận thủ công nhiệm vụ custom cho user.
     */
    public function adminApprove(User $user, Task $task): array
    {
        $result = DB::transaction(function () use ($user, $task) {
            $periodKey = $task->getPeriodKey();

            $userTask = UserTask::where('user_id', $user->id)
                ->where('task_id', $task->id)
                ->where('period_key', $periodKey)
                ->lockForUpdate()
                ->first();

            if (!$userTask) {
                return ['success' => false, 'message' => 'Không tìm thấy bản ghi nhiệm vụ.'];
            }

            if ($userTask->status === 'claimed') {
                return ['success' => false, 'message' => 'Thành viên đã nhận thưởng rồi.'];
            }

            $userTask->update([
                'progress'      => $task->target_count,
                'status'        => 'completed',
                'completed_at'  => now(),
                'reject_reason' => null,
                'reviewed_at'   => now(),
                'reviewed_by'   => auth()->id(),
            ]);

            ActivityLog::log("Admin xác nhận hoàn thành nhiệm vụ '{$task->title}' cho user #{$user->id} ({$user->email})", auth()->id());

            return ['success' => true, 'message' => 'Đã xác nhận hoàn thành nhiệm vụ. Thành viên có thể nhận thưởng.'];
        });

        // Báo cho thành viên biết yêu cầu đã được duyệt để vào nhận thưởng.
        // Đặt ngoài giao dịch để tránh đẩy thông báo đẩy (push) đi khi giao dịch chưa kịp ghi nhận thành công.
        if ($result['success'] ?? false) {
            Notification::create([
                'user_id' => $user->id,
                'title'   => __('Nhiệm vụ đã được duyệt'),
                'content' => __('Nhiệm vụ ":title" của bạn đã được xác nhận hoàn thành. Vào trang Nhiệm vụ để nhận :amount đ tiền thưởng nhé!', [
                    'title'  => $task->title,
                    'amount' => number_format($task->reward_amount),
                ]),
            ]);
        }

        return $result;
    }

    /**
     * Lấy danh sách tasks hiển thị cho user kèm trạng thái tiến độ.
     */
    public function getTasksForUser(User $user): \Illuminate\Support\Collection
    {
        $tasks = Task::where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('start_at')->orWhere('start_at', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('end_at')->orWhere('end_at', '>=', now());
            })
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return $tasks->map(function (Task $task) use ($user) {
            $userTask = $this->getOrCreateUserTask($user, $task);

            // Auto-sync progress cho các action tự động (không phải custom)
            if ($task->action !== 'custom' && $userTask->status !== 'claimed') {
                $progress = $this->computeProgress($user, $task);
                $capped    = min($progress, $task->target_count);
                $changed   = $capped !== $userTask->progress
                          || ($progress >= $task->target_count && $userTask->status === 'in_progress')
                          || ($progress < $task->target_count  && $userTask->status === 'completed');

                if ($changed) {
                    $updateData = ['progress' => $capped];
                    if ($progress >= $task->target_count && $userTask->status === 'in_progress') {
                        $updateData['status']       = 'completed';
                        $updateData['completed_at'] = now();
                    } elseif ($progress < $task->target_count && $userTask->status === 'completed') {
                        // Điều kiện bị mất → thu hồi trạng thái hoàn thành
                        $updateData['status']       = 'in_progress';
                        $updateData['completed_at'] = null;
                    }
                    $userTask->update($updateData);
                    $userTask = $userTask->fresh();
                }
            }

            $task->userTask = $userTask;
            return $task;
        });
    }
}
