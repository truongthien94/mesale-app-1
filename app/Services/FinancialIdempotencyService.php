<?php

namespace App\Services;

use App\Models\IdempotencyKey;
use Closure;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class FinancialIdempotencyService
{
    public const OUTCOME_COMPLETED = 'completed';

    public const OUTCOME_REPLAY = 'replay';

    public const OUTCOME_CONFLICT = 'conflict';

    public const OUTCOME_IN_PROGRESS = 'in_progress';

    /**
     * Return a prior result before controllers evaluate mutable feature or entity state.
     *
     * @return array{outcome: string, status?: int, data?: array<string, mixed>}|null
     */
    public function replayIfPresent(
        int $userId,
        string $operation,
        string $rawKey,
        array $payload
    ): ?array {
        $record = IdempotencyKey::query()
            ->where('user_id', $userId)
            ->where('operation', $operation)
            ->where('key_hash', hash('sha256', $rawKey))
            ->first();

        if (! $record) {
            return null;
        }

        return $this->outcomeForExistingRecord($record, $this->payloadHash($payload));
    }

    /**
     * @param  Closure(): array{status: int, data: array<string, mixed>}  $callback
     * @return array{outcome: string, status?: int, data?: array<string, mixed>}
     */
    public function execute(
        int $userId,
        string $operation,
        string $rawKey,
        array $payload,
        Closure $callback
    ): array {
        $keyHash = hash('sha256', $rawKey);
        $requestHash = $this->payloadHash($payload);

        return DB::transaction(function () use ($userId, $operation, $keyHash, $requestHash, $callback): array {
            $now = now();
            $inserted = DB::table('idempotency_keys')->insertOrIgnore([
                'user_id' => $userId,
                'operation' => $operation,
                'key_hash' => $keyHash,
                'request_hash' => $requestHash,
                'status' => IdempotencyKey::STATUS_PROCESSING,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $record = IdempotencyKey::query()
                ->where('user_id', $userId)
                ->where('operation', $operation)
                ->where('key_hash', $keyHash)
                ->lockForUpdate()
                ->first();

            if (! $record) {
                throw new RuntimeException('IDEMPOTENCY_RECORD_UNAVAILABLE');
            }

            if ($inserted === 0) {
                return $this->outcomeForExistingRecord($record, $requestHash);
            }

            $result = $callback();
            if (! isset($result['status'], $result['data']) || ! is_int($result['status']) || ! is_array($result['data'])) {
                throw new RuntimeException('INVALID_IDEMPOTENCY_CALLBACK_RESULT');
            }

            $data = $result['data'];
            $data['idempotent_replay'] = false;

            $record->forceFill([
                'status' => IdempotencyKey::STATUS_COMPLETED,
                'response_status' => $result['status'],
                'response_body' => $data,
                'completed_at' => now(),
            ])->save();

            return [
                'outcome' => self::OUTCOME_COMPLETED,
                'status' => $result['status'],
                'data' => $data,
            ];
        }, 3);
    }

    /**
     * @return array{outcome: string, status?: int, data?: array<string, mixed>}
     */
    private function outcomeForExistingRecord(IdempotencyKey $record, string $requestHash): array
    {
        if (! hash_equals($record->request_hash, $requestHash)) {
            return ['outcome' => self::OUTCOME_CONFLICT];
        }

        if ($record->status !== IdempotencyKey::STATUS_COMPLETED || ! is_array($record->response_body)) {
            return ['outcome' => self::OUTCOME_IN_PROGRESS];
        }

        $data = $record->response_body;
        $data['idempotent_replay'] = true;

        return [
            'outcome' => self::OUTCOME_REPLAY,
            'status' => $record->response_status ?? 200,
            'data' => $data,
        ];
    }

    private function payloadHash(array $payload): string
    {
        $canonical = $this->canonicalize($payload);

        return hash('sha256', json_encode($canonical, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    private function canonicalize(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }

        if (array_is_list($value)) {
            return array_map(fn (mixed $item): mixed => $this->canonicalize($item), $value);
        }

        ksort($value, SORT_STRING);

        foreach ($value as $key => $item) {
            $value[$key] = $this->canonicalize($item);
        }

        return $value;
    }
}
