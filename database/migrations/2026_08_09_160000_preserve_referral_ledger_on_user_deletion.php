<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Keep referral history and paid commissions when a referrer is deleted.
     * The referrer identity is intentionally anonymized by nulling its FK.
     */
    public function up(): void
    {
        $this->makeReferrerNullable('referrals');
        $this->makeReferrerNullable('referral_commissions');
    }

    /**
     * Reverting is refused after anonymization because restoring a required FK
     * would either lose ledger rows or require inventing a deleted user.
     */
    public function down(): void
    {
        foreach (['referrals', 'referral_commissions'] as $tableName) {
            if (! Schema::hasTable($tableName)) {
                continue;
            }

            if (DB::table($tableName)->whereNull('referrer_id')->exists()) {
                throw new RuntimeException(
                    "Cannot roll back referral ledger protection while {$tableName} contains anonymized rows."
                );
            }
        }

        $this->restoreCascadeForeignKey('referrals');
        $this->restoreCascadeForeignKey('referral_commissions');
    }

    private function makeReferrerNullable(string $tableName): void
    {
        if (! Schema::hasTable($tableName) || ! Schema::hasColumn($tableName, 'referrer_id')) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table): void {
            $table->dropForeign(['referrer_id']);
        });

        Schema::table($tableName, function (Blueprint $table): void {
            $table->unsignedBigInteger('referrer_id')->nullable()->change();
        });

        Schema::table($tableName, function (Blueprint $table): void {
            $table->foreign('referrer_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    private function restoreCascadeForeignKey(string $tableName): void
    {
        if (! Schema::hasTable($tableName) || ! Schema::hasColumn($tableName, 'referrer_id')) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table): void {
            $table->dropForeign(['referrer_id']);
        });

        Schema::table($tableName, function (Blueprint $table): void {
            $table->unsignedBigInteger('referrer_id')->nullable(false)->change();
        });

        Schema::table($tableName, function (Blueprint $table): void {
            $table->foreign('referrer_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();
        });
    }
};
