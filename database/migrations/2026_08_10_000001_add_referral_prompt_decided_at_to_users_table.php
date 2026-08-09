<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->timestamp('referral_prompt_decided_at')
                ->nullable()
                ->after('referred_by');
        });

        // Existing members must not receive a newly introduced onboarding prompt.
        DB::table('users')
            ->whereNull('referral_prompt_decided_at')
            ->update([
                'referral_prompt_decided_at' => DB::raw('COALESCE(created_at, CURRENT_TIMESTAMP)'),
            ]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('referral_prompt_decided_at');
        });
    }
};
