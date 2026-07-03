<?php

declare(strict_types=1);

namespace App\Actions\Analytics;

use App\Models\Meter;
use App\Support\Analytics\DemandSeriesGrain;
use App\Support\Analytics\DemandSeriesPoint;
use App\Support\Analytics\TelemetryPointConfidence;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use InvalidArgumentException;

final class BuildDemandSeriesAction
{
    public function __construct(
        private readonly BuildTelemetryPointsAction $telemetryPoints,
    ) {}

    /**
     * @return list<array<string, mixed>>
     */
    public function execute(
        string $meterIdentifier,
        string $buildingCode,
        CarbonInterface $from,
        CarbonInterface $to,
        DemandSeriesGrain|string $grain = DemandSeriesGrain::Hourly,
        int $limit = 500,
    ): array {
        $grain = is_string($grain) ? $this->grainFromString($grain) : $grain;
        $from = CarbonImmutable::instance($from);
        $to = CarbonImmutable::instance($to);

        if ($to->lessThan($from)) {
            throw new InvalidArgumentException('Demand series end time must be greater than or equal to start time.');
        }

        $telemetry = collect($this->telemetryPoints->execute(
            from: $from->subMinutes(5),
            to: $to->addMinutes($grain->minutes() + 5),
            limit: $limit,
        ))
            ->filter(fn (array $point): bool => $point['rawMeterIdentifier'] === $meterIdentifier && $point['rawLocation'] === $buildingCode)
            ->values();

        $multiplier = $this->meterMultiplier($meterIdentifier);
        $series = [];
        $cursor = $from;

        while ($cursor->lessThanOrEqualTo($to)) {
            $nextBoundary = $cursor->addMinutes($grain->minutes());
            $windowStart = $cursor->subMinutes(5);
            $windowEnd = $cursor->addMinutes($grain->reportWindowEndOffsetMinutes());
            $minReading = $grain === DemandSeriesGrain::Hourly
                ? $this->firstPointAtOrAfter($telemetry, $windowStart)
                : $this->lastPointAtOrBefore($telemetry, $windowEnd);
            $maxReading = $this->firstPointAtOrAfter($telemetry, $windowEnd);

            $series[] = $this->seriesPoint(
                periodStart: $cursor,
                periodEnd: $nextBoundary,
                grain: $grain,
                meterIdentifier: $meterIdentifier,
                buildingCode: $buildingCode,
                multiplier: $multiplier,
                minReading: $minReading,
                maxReading: $maxReading,
                windowStart: $windowStart,
                windowEnd: $windowEnd,
            )->toArray();

            $cursor = $nextBoundary;
        }

        return $this->markPeak($series);
    }

    private function grainFromString(string $grain): DemandSeriesGrain
    {
        return DemandSeriesGrain::tryFrom($grain)
            ?? throw new InvalidArgumentException("Unsupported demand series grain [{$grain}].");
    }

    private function meterMultiplier(string $meterIdentifier): float
    {
        $meter = Meter::query()
            ->where('meter_name', $meterIdentifier)
            ->orWhere('meter_id', $meterIdentifier)
            ->first(['meter_multiplier']);

        return (float) ($meter?->meter_multiplier ?? 1);
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $telemetry
     * @return array<string, mixed>|null
     */
    private function firstPointAtOrAfter(Collection $telemetry, CarbonImmutable $timestamp): ?array
    {
        return $telemetry
            ->first(fn (array $point): bool => CarbonImmutable::parse((string) $point['timestamp'])->greaterThanOrEqualTo($timestamp));
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $telemetry
     * @return array<string, mixed>|null
     */
    private function lastPointAtOrBefore(Collection $telemetry, CarbonImmutable $timestamp): ?array
    {
        return $telemetry
            ->filter(fn (array $point): bool => CarbonImmutable::parse((string) $point['timestamp'])->lessThanOrEqualTo($timestamp))
            ->last();
    }

    /**
     * @param  array<string, mixed>|null  $minReading
     * @param  array<string, mixed>|null  $maxReading
     */
    private function seriesPoint(
        CarbonImmutable $periodStart,
        CarbonImmutable $periodEnd,
        DemandSeriesGrain $grain,
        string $meterIdentifier,
        string $buildingCode,
        float $multiplier,
        ?array $minReading,
        ?array $maxReading,
        CarbonImmutable $windowStart,
        CarbonImmutable $windowEnd,
    ): DemandSeriesPoint {
        $missingMin = $minReading === null;
        $missingMax = $maxReading === null;
        $minWh = $minReading !== null ? (float) $minReading['measurements']['energy']['whTotal'] : null;
        $maxWh = $maxReading !== null ? (float) $maxReading['measurements']['energy']['whTotal'] : null;
        $minTimestamp = $minReading !== null ? CarbonImmutable::parse((string) $minReading['timestamp']) : null;
        $maxTimestamp = $maxReading !== null ? CarbonImmutable::parse((string) $maxReading['timestamp']) : null;

        if ($minWh === 0.0 && $maxWh !== null) {
            $minWh = $maxWh;
            $minTimestamp = $maxTimestamp;
        }

        $elapsedMinutes = ($minTimestamp !== null && $maxTimestamp !== null)
            ? abs(($maxTimestamp->getTimestamp() - $minTimestamp->getTimestamp()) / 60)
            : null;

        if ($elapsedMinutes !== null && $elapsedMinutes <= 0.0) {
            $elapsedMinutes = 1.0;
        }

        $whDelta = ($minWh !== null && $maxWh !== null) ? $maxWh - $minWh : null;
        $kwDemand = ($maxWh !== null && $maxWh !== 0.0 && $whDelta !== null && $whDelta !== 0.0 && $elapsedMinutes !== null)
            ? (float) number_format((($whDelta / $elapsedMinutes) * 60) * $multiplier, 2, '.', '')
            : null;

        return new DemandSeriesPoint(
            periodStart: $periodStart,
            periodEnd: $periodEnd,
            grain: $grain->value,
            context: [
                'meterIdentifier' => $meterIdentifier,
                'buildingCode' => $buildingCode,
                'meterId' => $minReading['context']['meterId'] ?? $maxReading['context']['meterId'] ?? null,
                'meterName' => $minReading['context']['meterName'] ?? $maxReading['context']['meterName'] ?? null,
                'siteId' => $minReading['context']['siteId'] ?? $maxReading['context']['siteId'] ?? null,
                'siteCode' => $minReading['context']['siteCode'] ?? $maxReading['context']['siteCode'] ?? null,
                'gatewayId' => $minReading['context']['gatewayId'] ?? $maxReading['context']['gatewayId'] ?? null,
                'gatewaySn' => $minReading['context']['gatewaySn'] ?? $maxReading['context']['gatewaySn'] ?? null,
                'gatewayMac' => $minReading['context']['gatewayMac'] ?? $maxReading['context']['gatewayMac'] ?? null,
            ],
            kwDemand: $kwDemand,
            minReading: $this->boundaryReading($minReading, $minWh, $minTimestamp),
            maxReading: $this->boundaryReading($maxReading, $maxWh, $maxTimestamp),
            elapsedMinutes: $elapsedMinutes,
            multiplier: $multiplier,
            peakMarker: [
                'isPeak' => false,
            ],
            missingData: [
                'missingIntervalCount' => ($missingMin ? 1 : 0) + ($missingMax ? 1 : 0),
                'missingMinReading' => $missingMin,
                'missingMaxReading' => $missingMax,
                'expectedStartBoundary' => $windowStart->toIso8601String(),
                'expectedEndBoundary' => $windowEnd->toIso8601String(),
            ],
            confidence: $this->confidence($minReading, $maxReading, $missingMin, $missingMax, $kwDemand),
            sourceLineage: [
                'contract' => 'DemandSeriesPoint',
                'calculation' => '((max.whTotal - min.whTotal) / elapsedMinutes) * 60 * meterMultiplier',
                'reportCompatible' => true,
                'minTelemetryPointId' => $minReading['id'] ?? null,
                'maxTelemetryPointId' => $maxReading['id'] ?? null,
                'minIdentifierMatchStrategy' => $minReading['sourceLineage']['identifierMatchStrategy'] ?? null,
                'maxIdentifierMatchStrategy' => $maxReading['sourceLineage']['identifierMatchStrategy'] ?? null,
                'sourceContract' => 'TelemetryPoint',
            ],
        );
    }

    /**
     * @param  array<string, mixed>|null  $point
     * @return array<string, mixed>|null
     */
    private function boundaryReading(?array $point, ?float $whTotal, ?CarbonImmutable $timestamp): ?array
    {
        if ($point === null || $whTotal === null || $timestamp === null) {
            return null;
        }

        return [
            'id' => $point['id'],
            'timestamp' => $timestamp->toIso8601String(),
            'whTotal' => $whTotal,
            'confidence' => $point['confidence'],
            'identifierMatchStrategy' => $point['sourceLineage']['identifierMatchStrategy'],
        ];
    }

    /**
     * @param  array<string, mixed>|null  $minReading
     * @param  array<string, mixed>|null  $maxReading
     * @return array<string, mixed>
     */
    private function confidence(?array $minReading, ?array $maxReading, bool $missingMin, bool $missingMax, ?float $kwDemand): array
    {
        if ($missingMin || $missingMax) {
            return [
                'level' => TelemetryPointConfidence::Incomplete->value,
                'reason' => 'Demand window is missing a required boundary reading.',
            ];
        }

        $levels = [
            (string) $minReading['confidence']['level'],
            (string) $maxReading['confidence']['level'],
        ];

        if (in_array(TelemetryPointConfidence::Incomplete->value, $levels, true) || in_array(TelemetryPointConfidence::Unknown->value, $levels, true)) {
            return [
                'level' => TelemetryPointConfidence::Incomplete->value,
                'reason' => 'Demand window inherits incomplete or unknown telemetry confidence.',
            ];
        }

        if ($kwDemand === null || $kwDemand <= 0.0) {
            return [
                'level' => TelemetryPointConfidence::Unknown->value,
                'reason' => 'Demand delta is zero or negative and should not be treated as measured demand without review.',
            ];
        }

        return [
            'level' => TelemetryPointConfidence::Calculated->value,
            'reason' => 'Demand calculated from measured TelemetryPoint boundary readings.',
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $series
     * @return list<array<string, mixed>>
     */
    private function markPeak(array $series): array
    {
        $peak = collect($series)
            ->pluck('kwDemand')
            ->filter(fn (mixed $value): bool => is_float($value) && $value > 0.0)
            ->max();

        return collect($series)
            ->map(function (array $point) use ($peak): array {
                $point['peakMarker'] = [
                    'isPeak' => $peak !== null && $point['kwDemand'] === $peak,
                    'peakKwDemand' => $peak,
                ];

                return $point;
            })
            ->values()
            ->all();
    }
}
