<?php

declare(strict_types=1);

namespace App\Actions\Analytics;

use App\Models\Building;
use App\Models\Meter;
use App\Support\Analytics\BuildingConsumptionSummary;
use App\Support\Analytics\ConsumptionSeriesGrain;
use App\Support\Analytics\TelemetryPointConfidence;
use Carbon\CarbonInterface;

final class BuildBuildingConsumptionSummaryAction
{
    public function __construct(
        private readonly BuildConsumptionSeriesAction $consumptionSeries,
    ) {}

    /**
     * @return list<array<string, mixed>>
     */
    public function execute(
        CarbonInterface $from,
        CarbonInterface $to,
        ConsumptionSeriesGrain|string $grain = ConsumptionSeriesGrain::Hourly,
        ?array $siteIds = null,
    ): array {
        $query = Building::query()
            ->leftJoin('meter_site', 'meter_building_table.site_idx', '=', 'meter_site.site_id')
            ->select([
                'meter_building_table.building_id',
                'meter_building_table.building_code',
                'meter_building_table.building_description',
                'meter_building_table.site_idx',
                'meter_site.site_code',
            ])
            ->orderBy('meter_building_table.building_code');

        if ($siteIds !== null) {
            $query->whereIn('meter_building_table.site_idx', $siteIds);
        }

        $summaries = $query
            ->get()
            ->map(function (object $building) use ($from, $to, $grain): BuildingConsumptionSummary {
                $meters = Meter::query()
                    ->where('building_idx', (int) $building->building_id)
                    ->whereRaw('UPPER(meter_status) = ?', ['ACTIVE'])
                    ->orderBy('meter_name')
                    ->get(['meter_id', 'meter_name']);

                $seriesPoints = [];
                foreach ($meters as $meter) {
                    $seriesPoints = [
                        ...$seriesPoints,
                        ...$this->consumptionSeries->execute(
                            meterIdentifier: (string) $meter->meter_name,
                            buildingCode: (string) $building->building_code,
                            from: $from,
                            to: $to,
                            grain: $grain,
                        ),
                    ];
                }

                return $this->summaryFromSeries($building, $meters->count(), $seriesPoints);
            })
            ->values()
            ->all();

        $peakTotal = collect($summaries)->max(fn (BuildingConsumptionSummary $summary): float => $summary->totalKwh);

        return collect($summaries)
            ->map(function (BuildingConsumptionSummary $summary) use ($peakTotal): array {
                $comparison = [
                    'rankBasis' => 'totalKwh',
                    'isTopConsumer' => $peakTotal !== null && $summary->totalKwh === $peakTotal && $summary->totalKwh > 0.0,
                    'topConsumerKwh' => $peakTotal,
                ];

                return new BuildingConsumptionSummary(
                    buildingId: $summary->buildingId,
                    buildingCode: $summary->buildingCode,
                    buildingName: $summary->buildingName,
                    site: $summary->site,
                    totalKwh: $summary->totalKwh,
                    meterCount: $summary->meterCount,
                    seriesPointCount: $summary->seriesPointCount,
                    missingData: $summary->missingData,
                    comparison: $comparison,
                    confidence: $summary->confidence,
                    sourceLineage: $summary->sourceLineage,
                )->toArray();
            })
            ->sortByDesc('totalKwh')
            ->values()
            ->all();
    }

    /**
     * @param  iterable<int, array<string, mixed>>  $seriesPoints
     */
    private function summaryFromSeries(object $building, int $meterCount, iterable $seriesPoints): BuildingConsumptionSummary
    {
        $points = collect($seriesPoints);
        $calculatedPoints = $points
            ->filter(fn (array $point): bool => (string) $point['confidence']['level'] === TelemetryPointConfidence::Calculated->value);
        $incompletePoints = $points
            ->filter(fn (array $point): bool => (string) $point['confidence']['level'] === TelemetryPointConfidence::Incomplete->value);
        $unknownPoints = $points
            ->filter(fn (array $point): bool => (string) $point['confidence']['level'] === TelemetryPointConfidence::Unknown->value);
        $missingIntervalCount = (int) $points->sum(fn (array $point): int => (int) $point['missingData']['missingIntervalCount']);

        $totalKwh = round((float) $calculatedPoints->sum(fn (array $point): float => (float) $point['kwhTotal']), 3);
        $confidence = $this->confidence(
            meterCount: $meterCount,
            seriesPointCount: $points->count(),
            incompleteCount: $incompletePoints->count(),
            unknownCount: $unknownPoints->count(),
        );

        return new BuildingConsumptionSummary(
            buildingId: (int) $building->building_id,
            buildingCode: (string) $building->building_code,
            buildingName: (string) $building->building_description,
            site: [
                'siteId' => $building->site_idx !== null ? (int) $building->site_idx : null,
                'siteCode' => $building->site_code !== null ? (string) $building->site_code : null,
            ],
            totalKwh: $totalKwh,
            meterCount: $meterCount,
            seriesPointCount: $points->count(),
            missingData: [
                'missingIntervalCount' => $missingIntervalCount,
                'incompleteSeriesPointCount' => $incompletePoints->count(),
                'unknownSeriesPointCount' => $unknownPoints->count(),
            ],
            comparison: [
                'rankBasis' => 'totalKwh',
                'isTopConsumer' => false,
                'topConsumerKwh' => null,
            ],
            confidence: $confidence,
            sourceLineage: [
                'contract' => 'BuildingConsumptionSummary',
                'sourceContract' => 'ConsumptionSeriesPoint',
                'buildingTable' => 'meter_building_table',
                'meterTable' => 'meter_details',
                'includedMeterCount' => $meterCount,
                'calculatedSeriesPointCount' => $calculatedPoints->count(),
            ],
        );
    }

    /**
     * @return array<string, string>
     */
    private function confidence(int $meterCount, int $seriesPointCount, int $incompleteCount, int $unknownCount): array
    {
        if ($meterCount === 0 || $seriesPointCount === 0) {
            return [
                'level' => TelemetryPointConfidence::Incomplete->value,
                'reason' => 'Building has no active meter consumption series for the selected period.',
            ];
        }

        if ($incompleteCount > 0) {
            return [
                'level' => TelemetryPointConfidence::Incomplete->value,
                'reason' => 'Building summary includes incomplete consumption windows.',
            ];
        }

        if ($unknownCount > 0) {
            return [
                'level' => TelemetryPointConfidence::Unknown->value,
                'reason' => 'Building summary includes zero or negative consumption windows.',
            ];
        }

        return [
            'level' => TelemetryPointConfidence::Calculated->value,
            'reason' => 'Building total calculated from complete consumption series points.',
        ];
    }
}
