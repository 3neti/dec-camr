<?php

declare(strict_types=1);

namespace App\Actions\Analytics;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

final class BuildAnalyticsWorkbenchDataAction
{
    public function __construct(
        private readonly BuildConsumptionSeriesAction $consumptionSeries,
        private readonly BuildDemandSeriesAction $demandSeries,
        private readonly BuildBuildingConsumptionSummaryAction $buildingConsumptionSummary,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function execute(): array
    {
        $context = $this->defaultContext();

        if ($context === null) {
            return [
                'analyticsContext' => [
                    'hasData' => false,
                    'meterIdentifier' => null,
                    'buildingCode' => null,
                    'siteCode' => null,
                    'from' => null,
                    'to' => null,
                    'periodLabel' => 'No telemetry window available',
                    'grain' => 'hourly',
                ],
                'contractData' => [
                    'consumptionPoints' => [],
                    'demandPoints' => [],
                    'buildingSummaries' => [],
                ],
                'contractEvidence' => $this->contractEvidence([], [], []),
                'emptyState' => [
                    'kind' => 'no-data',
                    'title' => 'No analytics telemetry available',
                    'description' => 'Run php artisan camr:scenario analytics-demo to create deterministic analytics showcase data.',
                    'contextLabel' => 'No telemetry context',
                    'sourceLabel' => 'Analytics contracts',
                    'missingIntervalCount' => 0,
                ],
            ];
        }

        $consumptionPoints = $this->consumptionSeries->execute(
            meterIdentifier: $context['meterIdentifier'],
            buildingCode: $context['buildingCode'],
            from: $context['from'],
            to: $context['to'],
        );
        $demandPoints = $this->demandSeries->execute(
            meterIdentifier: $context['meterIdentifier'],
            buildingCode: $context['buildingCode'],
            from: $context['from'],
            to: $context['to'],
        );
        $buildingSummaries = $this->buildingConsumptionSummary->execute(
            from: $context['from'],
            to: $context['to'],
        );

        return [
            'analyticsContext' => [
                'hasData' => true,
                'meterIdentifier' => $context['meterIdentifier'],
                'buildingCode' => $context['buildingCode'],
                'siteCode' => $context['siteCode'],
                'from' => $context['from']->toIso8601String(),
                'to' => $context['to']->toIso8601String(),
                'periodLabel' => sprintf('%s to %s', $context['from']->toDateTimeString(), $context['to']->toDateTimeString()),
                'grain' => 'hourly',
            ],
            'contractData' => [
                'consumptionPoints' => $consumptionPoints,
                'demandPoints' => $demandPoints,
                'buildingSummaries' => $buildingSummaries,
            ],
            'contractEvidence' => $this->contractEvidence($consumptionPoints, $demandPoints, $buildingSummaries),
            'emptyState' => [
                'kind' => 'missing-filter',
                'title' => 'Contract data is wired',
                'description' => 'AN-017 connects real analytics contracts to this shell. AN-018 will compose the full visual workspace.',
                'contextLabel' => sprintf('%s / %s', $context['buildingCode'], $context['meterIdentifier']),
                'sourceLabel' => 'ConsumptionSeriesPoint + DemandSeriesPoint + BuildingConsumptionSummary',
                'missingIntervalCount' => $this->missingIntervalCount($consumptionPoints, $demandPoints),
            ],
        ];
    }

    /**
     * @return array{meterIdentifier: string, buildingCode: string, siteCode: string|null, from: CarbonImmutable, to: CarbonImmutable}|null
     */
    private function defaultContext(): ?array
    {
        $row = DB::table('meter_data')
            ->join('meter_details', function ($join): void {
                $join->on('meter_data.meter_id', '=', 'meter_details.meter_id')
                    ->orOn('meter_data.meter_id', '=', 'meter_details.meter_name');
            })
            ->join('meter_building_table', 'meter_details.building_idx', '=', 'meter_building_table.building_id')
            ->whereRaw('UPPER(meter_details.meter_status) = ?', ['ACTIVE'])
            ->whereColumn('meter_data.location', 'meter_building_table.building_code')
            ->select([
                'meter_details.meter_name',
                'meter_details.site_code',
                'meter_building_table.building_code',
                DB::raw('MAX(meter_data.datetime) as latest_datetime'),
            ])
            ->groupBy('meter_details.meter_name', 'meter_details.site_code', 'meter_building_table.building_code')
            ->orderByDesc('latest_datetime')
            ->orderBy('meter_details.meter_name')
            ->first();

        if ($row === null || $row->latest_datetime === null) {
            return null;
        }

        $latest = CarbonImmutable::parse((string) $row->latest_datetime);
        $from = $latest->startOfDay();
        $to = $latest->startOfHour();

        if ($to->lessThan($from)) {
            $to = $from;
        }

        return [
            'meterIdentifier' => (string) $row->meter_name,
            'buildingCode' => (string) $row->building_code,
            'siteCode' => $row->site_code !== null ? (string) $row->site_code : null,
            'from' => $from,
            'to' => $to,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $consumptionPoints
     * @param  list<array<string, mixed>>  $demandPoints
     * @param  list<array<string, mixed>>  $buildingSummaries
     * @return array<string, mixed>
     */
    private function contractEvidence(array $consumptionPoints, array $demandPoints, array $buildingSummaries): array
    {
        return [
            'consumptionPointCount' => count($consumptionPoints),
            'demandPointCount' => count($demandPoints),
            'buildingSummaryCount' => count($buildingSummaries),
            'calculatedConsumptionCount' => $this->confidenceCount($consumptionPoints, 'Calculated'),
            'calculatedDemandCount' => $this->confidenceCount($demandPoints, 'Calculated'),
            'incompleteCount' => $this->confidenceCount($consumptionPoints, 'Incomplete')
                + $this->confidenceCount($demandPoints, 'Incomplete')
                + $this->confidenceCount($buildingSummaries, 'Incomplete'),
            'unknownCount' => $this->confidenceCount($consumptionPoints, 'Unknown')
                + $this->confidenceCount($demandPoints, 'Unknown')
                + $this->confidenceCount($buildingSummaries, 'Unknown'),
            'topBuildingCode' => $buildingSummaries[0]['buildingCode'] ?? null,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $items
     */
    private function confidenceCount(array $items, string $level): int
    {
        return collect($items)
            ->filter(fn (array $item): bool => ($item['confidence']['level'] ?? null) === $level)
            ->count();
    }

    /**
     * @param  list<array<string, mixed>>  $consumptionPoints
     * @param  list<array<string, mixed>>  $demandPoints
     */
    private function missingIntervalCount(array $consumptionPoints, array $demandPoints): int
    {
        return collect([...$consumptionPoints, ...$demandPoints])
            ->sum(fn (array $point): int => (int) ($point['missingData']['missingIntervalCount'] ?? 0));
    }
}
