<?php

namespace Tests\Feature;

use App\Models\IdempotencyKey;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class IdempotencyPruneCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-08-10 12:00:00');
        $this->createIsolatedSchema();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_command_prunes_only_expired_completed_and_stale_processing_rows(): void
    {
        config()->set([
            'idempotency.completed_retention_hours' => 24,
            'idempotency.processing_retention_hours' => 168,
            'idempotency.prune_batch_size' => 1,
        ]);

        $oldCompleted = $this->insertKey(IdempotencyKey::STATUS_COMPLETED, now()->subHours(25));
        $recentCompleted = $this->insertKey(IdempotencyKey::STATUS_COMPLETED, now()->subHours(23));
        $oldProcessing = $this->insertKey(IdempotencyKey::STATUS_PROCESSING, now()->subHours(169));
        $recentProcessing = $this->insertKey(IdempotencyKey::STATUS_PROCESSING, now()->subHours(167));

        $this->artisan('idempotency:prune')
            ->expectsOutputToContain('pruned 1 completed and 1 stale processing')
            ->assertSuccessful();

        $this->assertDatabaseMissing('idempotency_keys', ['id' => $oldCompleted]);
        $this->assertDatabaseMissing('idempotency_keys', ['id' => $oldProcessing]);
        $this->assertDatabaseHas('idempotency_keys', ['id' => $recentCompleted]);
        $this->assertDatabaseHas('idempotency_keys', ['id' => $recentProcessing]);
    }

    public function test_dry_run_and_retention_overrides_do_not_mutate_rows(): void
    {
        $completed = $this->insertKey(IdempotencyKey::STATUS_COMPLETED, now()->subHours(49));
        $processing = $this->insertKey(IdempotencyKey::STATUS_PROCESSING, now()->subHours(241));

        $this->artisan('idempotency:prune', [
            '--completed-hours' => '48',
            '--processing-hours' => '240',
            '--dry-run' => true,
        ])
            ->expectsOutputToContain('would prune 1 completed and 1 stale processing')
            ->assertSuccessful();

        $this->assertDatabaseHas('idempotency_keys', ['id' => $completed]);
        $this->assertDatabaseHas('idempotency_keys', ['id' => $processing]);
    }

    public function test_processing_retention_cannot_be_shorter_than_completed_retention(): void
    {
        $record = $this->insertKey(IdempotencyKey::STATUS_PROCESSING, now()->subDays(30));

        $this->artisan('idempotency:prune', [
            '--completed-hours' => '48',
            '--processing-hours' => '24',
        ])->assertExitCode(2);

        $this->assertDatabaseHas('idempotency_keys', ['id' => $record]);
    }

    public function test_prune_command_is_scheduled_daily_without_overlap(): void
    {
        Artisan::call('schedule:list');

        $event = collect(app(Schedule::class)->events())->first(
            static fn ($event): bool => str_contains((string) $event->command, 'idempotency:prune')
        );

        $this->assertNotNull($event);
        $this->assertSame('20 3 * * *', $event->expression);
        $this->assertSame('idempotency-prune', $event->description);
        $this->assertTrue($event->withoutOverlapping);
    }

    private function insertKey(string $status, Carbon $createdAt): int
    {
        return DB::table('idempotency_keys')->insertGetId([
            'user_id' => 1,
            'operation' => 'test-operation-'.uniqid(),
            'key_hash' => hash('sha256', uniqid('key-', true)),
            'request_hash' => hash('sha256', uniqid('request-', true)),
            'status' => $status,
            'response_status' => $status === IdempotencyKey::STATUS_COMPLETED ? 201 : null,
            'response_body' => null,
            'completed_at' => $status === IdempotencyKey::STATUS_COMPLETED ? $createdAt : null,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);
    }

    private function createIsolatedSchema(): void
    {
        Schema::create('idempotency_keys', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('operation', 100);
            $table->char('key_hash', 64);
            $table->char('request_hash', 64);
            $table->string('status', 20)->default('processing');
            $table->unsignedSmallInteger('response_status')->nullable();
            $table->longText('response_body')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'operation', 'key_hash'], 'idempotency_user_operation_key_unique');
            $table->index(['status', 'created_at']);
        });
    }
}
