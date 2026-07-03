<?php

declare(strict_types=1);

namespace App\Actions\Analytics;

use App\Models\Meter;
use App\Support\Analytics\ConsumptionSeriesGrain;
use App\Support\Analytics\ConsumptionSeriesPoint;
use App\Support\Analytics\TelemetryPointConfidence;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use InvalidArgumentException;

final class BuildConsumptionSeriesAction
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
        ConsumptionSeriesGrain|string $grain = ConsumptionSeriesGrain::Hourly,
        int $limit = 500,
    ): array {
        $grain = is_string($grain) ? $this->grainFromString($grain) : $grain;
        $from = CarbonImmutable::instance($from);
        $to = CarbonImmutable::instance($to);

        if ($to->lessThan($from)) {
            throw new InvalidArgumentException('Consumption series end time must be greater than or equal to start time.');
        }

        $telemetry = collect($this->telemetryPoints->execute(
            from: $from->subMinutes(5),
            to: $to->addMinutes($grain->minutes()),
            limit: $limit,
        ))
            ->filter(fn (array $point): bool => $point['rawMeterIdentifier'] === $meterIdentifier && $point['rawLocation'] === $buildingCode)
            ->values();

        $multiplier = $this->meterMultiplier($meterIdentifier);
        $series = [];
        $cursor = $from;

        while ($cursor->lessThanOrEqualTo($to)) {
            $nextBoundary = $cursor->addMinutes($grain->minutes());
            $windowEnd = $cursor->addMinutes($grain->reportWindowEndOffsetMinutes());
            $startReading = $this->firstPointAtOrAfter($telemetry, $cursor->subMinutes(5));
            $endReading = $this->firstPointAtOrAfter($telemetry, $windowEnd);

            $series[] = $this->seriesPoint(
                periodStart: $cursor,
                periodEnd: $nextBoundary,
                grain: $grain,
                meterIdentifier: $meterIdentifier,
                buildingCode: $buildingCode,
                multiplier: $multiplier,
                startReading: $startReading,
                endReading: $endReading,
                windowEnd: $windowEnd,
            )->toArray();

            $cursor = $nextBoundary;
        }

        return $series;
    }

    private function grainFromString(string $grain): ConsumptionSeriesGrain
    {
        return ConsumptionSeriesGrain::tryFrom($grain)
            ?? throw new InvalidArgumentException("Unsupported consumption series grain [{$grain}].");
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
     * @param  array<string, mixed>|null  $startReading
     * @param  array<string, mixed>|null  $endReading
     */
    private function seriesPoint(
        CarbonImmutable $periodStart,
        CarbonImmutable $periodEnd,
        ConsumptionSeriesGrain $grain,
        string $meterIdentifier,
        string $buildingCode,
        float $multiplier,
        ?array $startReading,
        ?array $endReading,
        CarbonImmutable $windowEnd,
    ): ConsumptionSeriesPoint {
        $missingStart = $startReading === null;
        $missingEnd = $endReading === null;
        $startWh = $startReading !== null ? (float) $startReading['measurements']['energy']['whTotal'] : null;
        $endWh = $endReading !== null ? (float) $endReading['measurements']['energy']['whTotal'] : null;
        $kwhTotal = ($startWh !== null && $endWh !== null)
            ? round(($endWh - $startWh) * $multiplier, 3)
            : null;

        return new ConsumptionSeriesPoint(
            periodStart: $periodStart,
            periodEnd: $periodEnd,
            grain: $grain->value,
            context: [
                'meterIdentifier' => $meterIdentifier,
                'buildingCode' => $buildingCode,
                'meterId' => $startReading['context']['meterId'] ?? $endReading['context']['meterId'] ?? null,
                'meterName' => $startReading['context']['meterName'] ?? $endReading['context']['meterName'] ?? null,
                'siteId' => $startReading['context']['siteId'] ?? $endReading['context']['siteId'] ?? null,
                'siteCode' => $startReading['context']['siteCode'] ?? $endReading['context']['siteCode'] ?? null,
                'gatewayId' => $startReading['context']['gatewayId'] ?? $endReading['context']['gatewayId'] ?? null,
                'gatewaySn' => $startReading['context']['gatewaySn'] ?? $endReading['context']['gatewaySn'] ?? null,
                'gatewayMac' => $startReading['context']['gatewayMac'] ?? $endReading['context']['gatewayMac'] ?? null,
            ],
            kwhTotal: $kwhTotal,
            startReading: $this->boundaryReading($startReading),
            endReading: $this->boundaryReading($endReading),
            multiplier: $multiplier,
            missingData: [
                'missingIntervalCount' => ($missingStart ? 1 : 0) + ($missingEnd ? 1 : 0),
                'missingStartReading' => $missingStart,
                'missingEndReading' => $missingEnd,
                'expectedEndBoundary' => $windowEnd->toIso8601String(),
            ],
            confidence: $this->confidence($startReading, $endReading, $missingStart, $missingEnd, $kwhTotal),
            sourceLineage: [
                'contract' => 'ConsumptionSeriesPoint',
                'calculation' => '(end.whTotal - start.whTotal) * meterMultiplier',
                'reportCompatible' => true,
                'startTelemetryPointId' => $startReading['id'] ?? null,
                'endTelemetryPointId' => $endReading['id'] ?? null,
                'startIdentifierMatchStrategy' => $startReading['sourceLineage']['identifierMatchStrategy'] ?? null,
                'endIdentifierMatchStrategy' => $endReading['sourceLineage']['identifierMatchStrategy'] ?? null,
                'sourceContract' => 'TelemetryPoint',
            ],
        );
    }

    /**
     * @param  array<string, mixed>|null  $point
     * @return array<string, mixed>|null
     */
    private function boundaryReading(?array $point): ?array
    {
        if ($point === null) {
            return null;
        }

        return [
            'id' => $point['id'],
            'timestamp' => $point['timestamp'],
            'whTotal' => $point['measurements']['energy']['whTotal'],
            'confidence' => $point['confidence'],
            'identifierMatchStrategy' => $point['sourceLineage']['identifierMatchStrategy'],
        ];
    }

    /**
     * @param  array<string, mixed>|null  $startReading
     * @param  array<string, mixed>|null  $endReading
     * @return array<string, mixed>
     */
    private function confidence(?array $startReading, ?array $endReading, bool $missingStart, bool $missingEnd, ?float $kwhTotal): array
    {
        if ($missingStart || $missingEnd) {
            return [
                'level' => TelemetryPointConfidence::Incomplete->value,
                'reason' => 'Consumption window is missing a required boundary reading.',
            ];
        }

        $levels = [
            (string) $startReading['confidence']['level'],
            (string) $endReading['confidence']['level'],
        ];

        if (in_array(TelemetryPointConfidence::Incomplete->value, $levels, true) || in_array(TelemetryPointConfidence::Unknown->value, $levels, true)) {
            return [
                'level' => TelemetryPointConfidence::Incomplete->value,
                'reason' => 'Consumption window inherits incomplete or unknown telemetry confidence.',
            ];
        }

        if ($kwhTotal === null || $kwhTotal <= 0.0) {
            return [
                'level' => TelemetryPointConfidence::Unknown->value,
                'reason' => 'Consumption delta is zero or negative and should not be treated as measured consumption without review.',
            ];
        }

        return [
            'level' => TelemetryPointConfidence::Calculated->value,
            'reason' => 'Consumption calculated from measured TelemetryPoint boundary readings.',
        ];
    }
}
