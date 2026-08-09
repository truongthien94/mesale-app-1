<?php

namespace App\Console\Commands;

use App\Models\IdempotencyKey;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;

class PruneIdempotencyKeys extends Command
{
    protected $signature = 'idempotency:prune
                            {--completed-hours= : Override completed-key retention in hours}
                            {--processing-hours= : Override processing-key retention in hours}
                            {--dry-run : Count prunable rows without deleting them}';

    protected $description = 'Prune expired financial idempotency records in bounded batches';

    public function handle(): int
    {
        $completedHours = $this->retentionHours(
            'completed-hours',
            (int) config('idempotency.completed_retention_hours', 24),
        );
        $processingHours = $this->retentionHours(
            'processing-hours',
            (int) config('idempotency.processing_retention_hours', 168),
        );

        if ($completedHours === null || $processingHours === null) {
            return self::INVALID;
        }

        if ($processingHours < $completedHours) {
            $this->components->error('Processing retention must be greater than or equal to completed retention.');

            return self::INVALID;
        }

        $batchSize = max(1, (int) config('idempotency.prune_batch_size', 500));
        $dryRun = (bool) $this->option('dry-run');
        $now = now();

        $completed = $this->prune(
            IdempotencyKey::query()->prunableCompleted($now->copy()->subHours($completedHours)),
            $batchSize,
            $dryRun,
        );
        $processing = $this->prune(
            IdempotencyKey::query()->prunableProcessing($now->copy()->subHours($processingHours)),
            $batchSize,
            $dryRun,
        );

        $verb = $dryRun ? 'would prune' : 'pruned';
        $this->components->info(
            "Idempotency cleanup {$verb} {$completed} completed and {$processing} stale processing record(s)."
        );

        return self::SUCCESS;
    }

    private function retentionHours(string $option, int $configuredDefault): ?int
    {
        $override = $this->option($option);

        if ($override === null) {
            return max(1, $configuredDefault);
        }

        if (! is_numeric($override) || (int) $override < 1 || (string) (int) $override !== (string) $override) {
            $this->components->error("The --{$option} option must be a positive whole number.");

            return null;
        }

        return (int) $override;
    }

    private function prune(Builder $query, int $batchSize, bool $dryRun): int
    {
        if ($dryRun) {
            return (clone $query)->count();
        }

        $deleted = 0;

        do {
            $ids = (clone $query)
                ->orderBy('id')
                ->limit($batchSize)
                ->pluck('id');

            if ($ids->isEmpty()) {
                break;
            }

            // Reapply status/cutoff predicates so a row that changes after selection is not deleted.
            $deleted += (clone $query)->whereKey($ids)->delete();
        } while ($ids->count() === $batchSize);

        return $deleted;
    }
}
