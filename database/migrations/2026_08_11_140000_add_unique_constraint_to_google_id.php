<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * BLK-AUTH-002: thêm ràng buộc UNIQUE cho users.google_id.
 *
 * Trước migration này, `apple_id` đã có UNIQUE nhưng `google_id` chỉ có INDEX,
 * nên cùng một định danh Google có thể nằm trên nhiều tài khoản.
 *
 * Audit production ngày 2026-08-11 xác nhận không có định danh Google trùng lặp,
 * nên việc thêm ràng buộc là an toàn. Xem docs/release/OAUTH-IDENTITY-AUDIT.md.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'google_id') || $this->hasUniqueGoogleIdIndex()) {
            return;
        }

        Schema::table('users', function (Blueprint $table): void {
            $table->unique('google_id', 'users_google_id_unique');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('users', 'google_id') || ! $this->hasIndex('users_google_id_unique')) {
            return;
        }

        Schema::table('users', function (Blueprint $table): void {
            $table->dropUnique('users_google_id_unique');
        });
    }

    private function hasUniqueGoogleIdIndex(): bool
    {
        $connection = Schema::getConnection();
        if ($connection->getDriverName() === 'sqlite') {
            foreach ($connection->select("PRAGMA index_list('users')") as $index) {
                if ((int) ($index->unique ?? 0) !== 1) {
                    continue;
                }
                $columns = array_map(
                    static fn (object $column): string => (string) $column->name,
                    $connection->select("PRAGMA index_info('".str_replace("'", "''", (string) $index->name)."')")
                );
                if ($columns === ['google_id']) {
                    return true;
                }
            }

            return false;
        }

        foreach ($connection->select("SHOW INDEX FROM users WHERE Column_name = 'google_id'") as $index) {
            if ((int) ($index->Non_unique ?? 1) === 0 && ($index->Sub_part ?? null) === null) {
                return true;
            }
        }

        return false;
    }

    private function hasIndex(string $name): bool
    {
        $connection = Schema::getConnection();
        if ($connection->getDriverName() === 'sqlite') {
            foreach ($connection->select("PRAGMA index_list('users')") as $index) {
                if (($index->name ?? null) === $name) {
                    return true;
                }
            }

            return false;
        }

        return $connection->select("SHOW INDEX FROM users WHERE Key_name = ?", [$name]) !== [];
    }
};
