<?php

declare(strict_types=1);

namespace App\Actions\Analytics;

use App\Models\User;
use Carbon\CarbonImmutable;
use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Support\Collection;
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
    public function execute(
        ?string $fromDate = null,
        ?string $toDate = null,
        ?string $buildingCode = null,
        ?string $meterIdentifier = null,
        ?User $legacyUser = null,
    ): array {
        $allowedSiteIds = $this->allowedSiteIds($legacyUser);
        $contexts = $this->availableContexts($allowedSiteIds);
        $context = $this->defaultContext($contexts, $fromDate, $toDate, $buildingCode, $meterIdentifier);

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
                'timeRangeControls' => [
                    'from' => '',
                    'to' => '',
                    'min' => '',
                    'max' => '',
                    'timezoneLabel' => config('app.timezone', 'UTC'),
                    'presets' => [],
                ],
                'contextControls' => [
                    'buildingCode' => '',
                    'meterIdentifier' => '',
                    'buildingOptions' => [],
                    'meterOptions' => [],
                ],
                'queryState' => [
                    'building' => null,
                    'meter' => null,
                    'from' => null,
                    'to' => null,
                    'url' => '/analytics',
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
            siteIds: $allowedSiteIds,
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
            'timeRangeControls' => [
                'from' => $context['from']->toDateString(),
                'to' => $context['to']->toDateString(),
                'min' => $context['availableFrom']->toDateString(),
                'max' => $context['availableTo']->toDateString(),
                'timezoneLabel' => config('app.timezone', 'UTC'),
                'presets' => $this->timeRangePresets($context),
            ],
            'contextControls' => [
                'buildingCode' => $context['buildingCode'],
                'meterIdentifier' => $context['meterIdentifier'],
                'buildingOptions' => $this->buildingOptions($contexts),
                'meterOptions' => $this->meterOptions($contexts, $context['buildingCode']),
            ],
            'queryState' => $this->queryState($context),
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
     * @param  list<int>|null  $siteIds
     * @return Collection<int, object{meter_id: int|string|null, meter_name: string|null, site_idx: int|string|null, site_code: string|null, building_code: string|null, earliest_datetime: string|null, latest_datetime: string|null}>
     */
    private function availableContexts(?array $siteIds): Collection
    {
        $query = DB::table('meter_data')
            ->join('meter_details', function ($join): void {
                $join->on('meter_data.meter_id', '=', 'meter_details.meter_id')
                    ->orOn('meter_data.meter_id', '=', 'meter_details.meter_name');
            })
            ->join('meter_building_table', 'meter_details.building_idx', '=', 'meter_building_table.building_id')
            ->leftJoin('meter_site', 'meter_building_table.site_idx', '=', 'meter_site.site_id')
            ->whereRaw('UPPER(meter_details.meter_status) = ?', ['ACTIVE'])
            ->whereColumn('meter_data.location', 'meter_building_table.building_code')
            ->select([
                'meter_details.meter_id',
                'meter_details.meter_name',
                'meter_building_table.site_idx',
                'meter_site.site_code',
                'meter_building_table.building_code',
                DB::raw('MIN(meter_data.datetime) as earliest_datetime'),
                DB::raw('MAX(meter_data.datetime) as latest_datetime'),
            ])
            ->groupBy('meter_details.meter_id', 'meter_details.meter_name', 'meter_building_table.site_idx', 'meter_site.site_code', 'meter_building_table.building_code')
            ->orderByDesc('latest_datetime')
            ->orderBy('meter_details.meter_name');

        if ($siteIds !== null) {
            $query->whereIn('meter_building_table.site_idx', $siteIds);
        }

        return $query->get();
    }

    /**
     * @param  Collection<int, object{meter_id: int|string|null, meter_name: string|null, site_idx: int|string|null, site_code: string|null, building_code: string|null, earliest_datetime: string|null, latest_datetime: string|null}>  $contexts
     * @return array{meterIdentifier: string, buildingCode: string, siteCode: string|null, from: CarbonImmutable, to: CarbonImmutable, availableFrom: CarbonImmutable, availableTo: CarbonImmutable}|null
     */
    private function defaultContext(
        Collection $contexts,
        ?string $fromDate = null,
        ?string $toDate = null,
        ?string $buildingCode = null,
        ?string $meterIdentifier = null,
    ): ?array {
        $row = $this->selectedContextRow($contexts, $buildingCode, $meterIdentifier);

        if ($row === null || $row->latest_datetime === null) {
            return null;
        }

        $availableFrom = CarbonImmutable::parse((string) ($row->earliest_datetime ?? $row->latest_datetime))->startOfDay();
        $availableTo = CarbonImmutable::parse((string) $row->latest_datetime)->endOfDay();
        $latest = CarbonImmutable::parse((string) $row->latest_datetime);
        $from = $latest->startOfDay();
        $to = $latest->startOfHour();

        if ($fromDate !== null && $toDate !== null && $fromDate !== '' && $toDate !== '') {
            $selectedFrom = $this->parseDateBoundary($fromDate);
            $selectedTo = $this->parseDateBoundary($toDate, endOfDay: true);

            if ($selectedFrom !== null && $selectedTo !== null && $selectedFrom->lessThanOrEqualTo($selectedTo)) {
                $from = $selectedFrom;
                $to = $selectedTo;
            }
        }

        if ($to->lessThan($from)) {
            $to = $from;
        }

        return [
            'meterIdentifier' => (string) $row->meter_name,
            'buildingCode' => (string) $row->building_code,
            'siteCode' => $row->site_code !== null ? (string) $row->site_code : null,
            'from' => $from,
            'to' => $to,
            'availableFrom' => $availableFrom,
            'availableTo' => $availableTo,
        ];
    }

    /**
     * @param  Collection<int, object{meter_id: int|string|null, meter_name: string|null, site_idx: int|string|null, site_code: string|null, building_code: string|null, earliest_datetime: string|null, latest_datetime: string|null}>  $contexts
     * @return object{meter_id: int|string|null, meter_name: string|null, site_idx: int|string|null, site_code: string|null, building_code: string|null, earliest_datetime: string|null, latest_datetime: string|null}|null
     */
    private function selectedContextRow(Collection $contexts, ?string $buildingCode, ?string $meterIdentifier): ?object
    {
        $buildingCode = trim((string) $buildingCode);
        $meterIdentifier = trim((string) $meterIdentifier);
        $buildingContexts = $buildingCode !== ''
            ? $contexts->where('building_code', $buildingCode)->values()
            : $contexts;

        if ($buildingContexts->isEmpty()) {
            $buildingContexts = $contexts;
        }

        if ($meterIdentifier !== '') {
            $matchedMeter = $buildingContexts
                ->first(fn (object $row): bool => (string) $row->meter_name === $meterIdentifier || (string) $row->meter_id === $meterIdentifier);

            if ($matchedMeter !== null) {
                return $matchedMeter;
            }
        }

        return $buildingContexts->first();
    }

    /**
     * @param  Collection<int, object{meter_id: int|string|null, meter_name: string|null, site_idx: int|string|null, site_code: string|null, building_code: string|null, earliest_datetime: string|null, latest_datetime: string|null}>  $contexts
     * @return list<array{value: string, label: string, description: string}>
     */
    private function buildingOptions(Collection $contexts): array
    {
        return $contexts
            ->groupBy('building_code')
            ->map(fn (Collection $buildingContexts, string $buildingCode): array => [
                'value' => $buildingCode,
                'label' => $buildingCode,
                'description' => sprintf('%d telemetry meter%s available', $buildingContexts->count(), $buildingContexts->count() === 1 ? '' : 's'),
            ])
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, object{meter_id: int|string|null, meter_name: string|null, site_idx: int|string|null, site_code: string|null, building_code: string|null, earliest_datetime: string|null, latest_datetime: string|null}>  $contexts
     * @return list<array{value: string, label: string, description: string, buildingCode: string}>
     */
    private function meterOptions(Collection $contexts, string $buildingCode): array
    {
        return $contexts
            ->where('building_code', $buildingCode)
            ->map(fn (object $row): array => [
                'value' => (string) $row->meter_name,
                'label' => (string) $row->meter_name,
                'description' => sprintf('Site %s / numeric ID %s', $row->site_code ?? 'unassigned', $row->meter_id ?? 'unknown'),
                'buildingCode' => $buildingCode,
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<int>|null
     */
    private function allowedSiteIds(?User $legacyUser): ?array
    {
        if (! $legacyUser instanceof User) {
            return [];
        }

        if ($legacyUser->hasFullSiteAccess()) {
            return null;
        }

        return DB::table('user_access_group')
            ->where('user_idx', (string) $legacyUser->id)
            ->pluck('site_idx')
            ->map(fn (mixed $siteId): int => (int) $siteId)
            ->unique()
            ->values()
            ->all();
    }

    private function parseDateBoundary(string $value, bool $endOfDay = false): ?CarbonImmutable
    {
        try {
            $date = CarbonImmutable::parse($value);
        } catch (InvalidFormatException) {
            return null;
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) === 1) {
            return $endOfDay ? $date->endOfDay() : $date->startOfDay();
        }

        return $date;
    }

    /**
     * @param  array{meterIdentifier: string, buildingCode: string, siteCode: string|null, from: CarbonImmutable, to: CarbonImmutable, availableFrom: CarbonImmutable, availableTo: CarbonImmutable}  $context
     * @return array{building: string, meter: string, from: string, to: string, url: string}
     */
    private function queryState(array $context): array
    {
        $query = [
            'building' => $context['buildingCode'],
            'meter' => $context['meterIdentifier'],
            'from' => $context['from']->toDateString(),
            'to' => $context['to']->toDateString(),
        ];

        return [
            ...$query,
            'url' => '/analytics?'.http_build_query($query),
        ];
    }

    /**
     * @param  array{meterIdentifier: string, buildingCode: string, siteCode: string|null, from: CarbonImmutable, to: CarbonImmutable, availableFrom: CarbonImmutable, availableTo: CarbonImmutable}  $context
     * @return list<array{key: string, label: string, from: string, to: string, description: string}>
     */
    private function timeRangePresets(array $context): array
    {
        return [
            [
                'key' => 'selected-day',
                'label' => 'Selected day',
                'from' => $context['from']->toDateString(),
                'to' => $context['from']->toDateString(),
                'description' => 'Review the current investigation day.',
            ],
            [
                'key' => 'available-window',
                'label' => 'All telemetry',
                'from' => $context['availableFrom']->toDateString(),
                'to' => $context['availableTo']->toDateString(),
                'description' => 'Review the full available telemetry range for this context.',
            ],
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
