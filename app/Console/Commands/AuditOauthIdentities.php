<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Kiểm tra trùng lặp định danh nhà cung cấp OAuth trước khi thêm ràng buộc UNIQUE.
 *
 * Bối cảnh (BLK-AUTH-002): cột `apple_id` đã có UNIQUE, nhưng `google_id` chỉ có INDEX.
 * Nếu thêm UNIQUE vào `google_id` khi dữ liệu đang trùng thì migration sẽ THẤT BẠI
 * trên production. Lệnh này chỉ ĐỌC, không sửa gì, dùng để quyết định trước.
 */
class AuditOauthIdentities extends Command
{
    protected $signature = 'oauth:audit-identities
                            {--provider=all : Nhà cung cấp cần kiểm tra: google, apple hoặc all}';

    protected $description = 'Audit duplicate Google/Apple provider identities before adding a unique constraint (read-only)';

    public function handle(): int
    {
        $provider = (string) $this->option('provider');

        if (! in_array($provider, ['all', 'google', 'apple'], true)) {
            $this->components->error('Tham số --provider chỉ nhận: google, apple hoặc all.');

            return self::INVALID;
        }

        $columns = $provider === 'all'
            ? ['google_id', 'apple_id']
            : [$provider.'_id'];

        $hasDuplicates = false;

        foreach ($columns as $column) {
            if ($this->auditColumn($column)) {
                $hasDuplicates = true;
            }
        }

        $this->newLine();

        if ($hasDuplicates) {
            $this->components->warn(
                'Phát hiện định danh trùng lặp. KHÔNG thêm ràng buộc UNIQUE trước khi chủ sở hữu '
                .'quyết định cách hợp nhất/thu hồi từng trường hợp. Migration sẽ thất bại nếu thêm ngay.'
            );

            return self::FAILURE;
        }

        $this->components->info('Không có định danh trùng lặp. An toàn để thêm ràng buộc UNIQUE.');

        return self::SUCCESS;
    }

    /**
     * Kiểm tra một cột định danh. Trả về true nếu phát hiện trùng lặp.
     */
    private function auditColumn(string $column): bool
    {
        $this->newLine();
        $this->components->twoColumnDetail('<fg=cyan>Cột kiểm tra</>', $column);

        $total = DB::table('users')
            ->whereNotNull($column)
            ->where($column, '!=', '')
            ->count();

        $this->components->twoColumnDetail('Tài khoản đã liên kết', (string) $total);

        // Nhóm theo giá trị định danh; bất kỳ nhóm nào có > 1 user là trùng lặp.
        $duplicates = DB::table('users')
            ->select($column, DB::raw('COUNT(*) as total'))
            ->whereNotNull($column)
            ->where($column, '!=', '')
            ->groupBy($column)
            ->havingRaw('COUNT(*) > 1')
            ->orderByDesc('total')
            ->get();

        if ($duplicates->isEmpty()) {
            $this->components->twoColumnDetail('Định danh trùng lặp', '<fg=green>0</>');

            return false;
        }

        $affectedUsers = (int) $duplicates->sum('total');

        $this->components->twoColumnDetail(
            'Định danh trùng lặp',
            '<fg=red>'.$duplicates->count().'</>'
        );
        $this->components->twoColumnDetail('Tài khoản bị ảnh hưởng', '<fg=red>'.$affectedUsers.'</>');

        // Chỉ in ID nội bộ và số lượng. Không in email, tên hay giá trị định danh
        // nhà cung cấp vì đó là dữ liệu định danh thành viên.
        $this->newLine();
        $this->line('  Chi tiết (chỉ user ID nội bộ, đã ẩn thông tin định danh):');

        foreach ($duplicates as $index => $row) {
            $userIds = DB::table('users')
                ->where($column, $row->{$column})
                ->orderBy('id')
                ->pluck('id')
                ->implode(', ');

            $this->line(sprintf(
                '  #%d  số tài khoản: %d  →  user ID: %s',
                $index + 1,
                $row->total,
                $userIds
            ));
        }

        return true;
    }
}
