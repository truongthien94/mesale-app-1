<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('users', 'referral_code_eligible_until')) {
            return;
        }

        Schema::table('users', function (Blueprint $table): void {
            $table->timestamp('referral_code_eligible_until')
                ->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('referral_code_eligible_until');
        });
    }
};
