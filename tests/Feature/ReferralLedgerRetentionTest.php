<?php

namespace Tests\Feature;

use App\Models\ReferralCommission;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ReferralLedgerRetentionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createLegacyReferralSchema();

        $migration = require base_path(
            'database/migrations/2026_08_09_160000_preserve_referral_ledger_on_user_deletion.php'
        );
        $migration->up();
    }

    public function test_deleting_referrer_anonymizes_referrer_foreign_keys_and_preserves_counterparty_ledger(): void
    {
        $referrer = $this->createUser([
            'name' => 'Referrer PII',
            'email' => 'referrer-pii@example.test',
            'referral_code' => 'REFERRER1',
        ]);
        $referred = $this->createUser([
            'name' => 'Referred member',
            'email' => 'referred@example.test',
            'referral_code' => 'REFERRED1',
            'referred_by' => $referrer->id,
        ]);

        $cashbackHistoryId = DB::table('cashback_histories')->insertGetId([
            'user_id' => $referred->id,
            'status' => 'approved',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $referralId = DB::table('referrals')->insertGetId([
            'referrer_id' => $referrer->id,
            'referred_id' => $referred->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $commissionId = ReferralCommission::create([
            'referrer_id' => $referrer->id,
            'referred_id' => $referred->id,
            'cashback_history_id' => $cashbackHistoryId,
            'amount' => 2500,
            'level' => 1,
            'status' => 'approved',
        ])->id;

        $referrer->delete();

        $this->assertDatabaseMissing('users', [
            'id' => $referrer->id,
            'email' => 'referrer-pii@example.test',
        ]);
        $this->assertDatabaseHas('users', [
            'id' => $referred->id,
            'referred_by' => null,
        ]);
        $this->assertDatabaseHas('referrals', [
            'id' => $referralId,
            'referrer_id' => null,
            'referred_id' => $referred->id,
        ]);
        $this->assertDatabaseHas('referral_commissions', [
            'id' => $commissionId,
            'referrer_id' => null,
            'referred_id' => $referred->id,
            'cashback_history_id' => $cashbackHistoryId,
        ]);

        $commission = ReferralCommission::query()->where('referred_id', $referred->id)->firstOrFail();
        $this->assertTrue($commission->hasAnonymizedReferrer());
        $this->assertNull($commission->referrer);
        $this->assertSame(1, DB::table('referrals')->where('referred_id', $referred->id)->count());
        $this->assertSame(1, DB::table('referral_commissions')->where('referred_id', $referred->id)->count());
    }

    public function test_rollback_refuses_to_reintroduce_required_referrer_foreign_keys_after_anonymization(): void
    {
        $referred = $this->createUser(['email' => 'rollback-referred@example.test']);

        DB::table('referrals')->insert([
            'referrer_id' => null,
            'referred_id' => $referred->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $migration = require base_path(
            'database/migrations/2026_08_09_160000_preserve_referral_ledger_on_user_deletion.php'
        );

        $this->expectException(\RuntimeException::class);
        $migration->down();
    }

    private function createUser(array $attributes = []): User
    {
        $user = new User;
        $user->forceFill(array_merge([
            'name' => 'Referral test user',
            'email' => fake()->unique()->safeEmail(),
            'password' => Hash::make('correct-password'),
            'status' => 'active',
            'role' => 'user',
            'balance' => 0,
            'total_cashback' => 0,
            'total_referral_earned' => 0,
            'total_withdrawn' => 0,
        ], $attributes));
        $user->save();

        return $user;
    }

    private function createLegacyReferralSchema(): void
    {
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->nullable()->unique();
            $table->string('password')->nullable();
            $table->decimal('balance', 15, 2)->default(0);
            $table->decimal('total_cashback', 15, 2)->default(0);
            $table->decimal('total_referral_earned', 15, 2)->default(0);
            $table->decimal('total_withdrawn', 15, 2)->default(0);
            $table->string('referral_code')->nullable()->unique();
            $table->unsignedBigInteger('referred_by')->nullable();
            $table->string('status')->default('active');
            $table->string('role')->default('user');
            $table->string('api_token', 64)->nullable()->unique();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->foreign('referred_by')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('cashback_histories', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('status');
            $table->timestamps();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::create('referrals', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('referrer_id');
            $table->unsignedBigInteger('referred_id')->unique();
            $table->timestamps();
            $table->foreign('referrer_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('referred_id')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::create('referral_commissions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('referrer_id');
            $table->unsignedBigInteger('referred_id');
            $table->unsignedBigInteger('cashback_history_id');
            $table->decimal('amount', 15, 2);
            $table->unsignedInteger('level');
            $table->string('status')->default('pending');
            $table->timestamps();
            $table->foreign('referrer_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('referred_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('cashback_history_id')->references('id')->on('cashback_histories')->cascadeOnDelete();
        });
    }
}
