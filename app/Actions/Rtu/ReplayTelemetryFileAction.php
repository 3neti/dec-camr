<?php

declare(strict_types=1);

namespace App\Actions\Rtu;

use Carbon\CarbonImmutable;
use InvalidArgumentException;
use SplFileObject;
use Throwable;

final class ReplayTelemetryFileAction
{
    private const SUPPORTED_SPEEDS = ['slow', 'real', 'fast'];

    public function __construct(
        private readonly IngestRtuTelemetryAction $ingestRtuTelemetry,
    ) {}

    /**
     * @return array{rows_seen: int, rows_replayed: int, rows_failed: int, rows_saved: int}
     */
    public function replay(string $file, bool $dryRun = false, string $speed = 'real', ?string $anchor = null, bool $loop = false): array
    {
        $speed = strtolower(trim($speed));
        if (! in_array($speed, self::SUPPORTED_SPEEDS, true)) {
            throw new InvalidArgumentException(sprintf('Unsupported speed: %s. Supported speeds: %s', $speed, implode(', ', self::SUPPORTED_SPEEDS)));
        }

        if (! is_file($file) || ! is_readable($file)) {
            throw new InvalidArgumentException(sprintf('Telemetry replay file is not readable: %s', $file));
        }

        $rows = $this->rows($file);
        if ($rows === []) {
            return [
                'rows_seen' => 0,
                'rows_replayed' => 0,
                'rows_failed' => 0,
                'rows_saved' => 0,
            ];
        }

        $offsetSeconds = $this->anchorOffsetSeconds($rows, $anchor);
        $iterations = $loop ? 2 : 1;
        $summary = [
            'rows_seen' => count($rows) * $iterations,
            'rows_replayed' => 0,
            'rows_failed' => 0,
            'rows_saved' => 0,
        ];

        for ($iteration = 0; $iteration < $iterations; $iteration++) {
            foreach ($rows as $row) {
                $payload = $this->anchoredPayload($row, $offsetSeconds, $iteration);

                if (! $this->isReplayable($payload)) {
                    $summary['rows_failed']++;

                    continue;
                }

                $summary['rows_replayed']++;

                if ($dryRun) {
                    continue;
                }

                $result = $this->ingestRtuTelemetry->execute($payload);
                if ($result['saved']) {
                    $summary['rows_saved']++;
                }
            }
        }

        return $summary;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function rows(string $file): array
    {
        $csv = new SplFileObject($file);
        $csv->setFlags(SplFileObject::READ_CSV | SplFileObject::SKIP_EMPTY);
        $headers = [];
        $rows = [];

        foreach ($csv as $index => $row) {
            if (! is_array($row) || $row === [null]) {
                continue;
            }

            if ($index === 0) {
                $headers = array_map(static fn (mixed $value): string => trim((string) $value), $row);

                continue;
            }

            if (count($row) < count($headers)) {
                $row = array_pad($row, count($headers), null);
            }

            $rows[] = array_combine($headers, array_slice($row, 0, count($headers))) ?: [];
        }

        return $rows;
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function anchorOffsetSeconds(array $rows, ?string $anchor): int
    {
        if ($anchor === null || trim($anchor) === '') {
            return 0;
        }

        try {
            $anchorTime = strtolower(trim($anchor)) === 'now'
                ? CarbonImmutable::now()->startOfMinute()
                : CarbonImmutable::parse($anchor);
            $firstTimestamp = CarbonImmutable::parse((string) ($rows[0]['datetime'] ?? ''));
        } catch (Throwable) {
            throw new InvalidArgumentException(sprintf('Invalid anchor format: %s. Expected Y-m-d H:i:s or now', $anchor));
        }

        return (int) $firstTimestamp->diffInSeconds($anchorTime, false);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function anchoredPayload(array $payload, int $offsetSeconds, int $iteration): array
    {
        if ($offsetSeconds === 0 && $iteration === 0) {
            return $payload;
        }

        try {
            $timestamp = CarbonImmutable::parse((string) ($payload['datetime'] ?? ''))
                ->addSeconds($offsetSeconds)
                ->addMinutes($iteration * 5);
        } catch (Throwable) {
            return $payload;
        }

        return [
            ...$payload,
            'datetime' => $timestamp->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function isReplayable(array $payload): bool
    {
        return trim((string) ($payload['meter_id'] ?? '')) !== ''
            && trim((string) ($payload['location'] ?? '')) !== ''
            && trim((string) ($payload['datetime'] ?? '')) !== '';
    }
}
