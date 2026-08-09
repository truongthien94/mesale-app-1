<?php

$completedRetentionHours = max(1, (int) env('IDEMPOTENCY_COMPLETED_RETENTION_HOURS', 24));

return [
    // Clients may safely retry financial mutations for one full day.
    'completed_retention_hours' => $completedRetentionHours,

    // Processing markers get a longer safety window before an abandoned key is released.
    'processing_retention_hours' => max(
        $completedRetentionHours,
        (int) env('IDEMPOTENCY_PROCESSING_RETENTION_HOURS', 168),
    ),

    // Bound each delete statement to avoid long-running table locks.
    'prune_batch_size' => max(1, (int) env('IDEMPOTENCY_PRUNE_BATCH_SIZE', 500)),
];
