<?php

use App\Actions\Analytics\BuildBuildingConsumptionSummaryAction;
use App\Actions\Analytics\BuildConsumptionSeriesAction;
use App\Actions\Analytics\BuildDemandSeriesAction;
use App\Models\Building;
use App\Models\Meter;
use Carbon\CarbonImmutable;

test('analytics demo scenario is registered', function () {
    $this->artisan('camr:scenario --list')
        ->assertSuccessful()
        ->expectsOutputToContain('analytics-demo');
});

test('analytics demo data readiness creates a deterministic analytics story', function () {
    $this->artisan('camr:scenario analytics-demo')
        ->assertSuccessful()
        ->expectsOutputToContain('Scenario: analytics-demo')
        ->expectsOutputToContain('Simulation status: completed (scenario=analytics-demo)');

    $meters = Meter::query()
        ->whereRaw('UPPER(meter_status) = ?', ['ACTIVE'])
        ->orderBy('meter_id')
        ->get()
        ->filter(fn (Meter $meter): bool => $meter->building_idx !== null && (int) $meter->building_idx !== 0)
        ->unique('building_idx')
        ->take(4)
        ->values();

    expect($meters)->toHaveCount(4);

    $buildingCodes = Building::query()
        ->whereIn('building_id', $meters->pluck('building_idx')->values())
        ->pluck('building_code', 'building_id');

    $from = CarbonImmutable::parse('2026-07-01 00:00:00');
    $to = CarbonImmutable::parse('2026-07-01 23:00:00');

    $normalConsumption = app(BuildConsumptionSeriesAction::class)->execute(
        meterIdentifier: (string) $meters[0]->meter_name,
        buildingCode: (string) $buildingCodes[$meters[0]->building_idx],
        from: $from,
        to: $to,
    );
    $abnormalConsumption = app(BuildConsumptionSeriesAction::class)->execute(
        meterIdentifier: (string) $meters[1]->meter_name,
        buildingCode: (string) $buildingCodes[$meters[1]->building_idx],
        from: $from,
        to: $to,
    );
    $abnormalDemand = app(BuildDemandSeriesAction::class)->execute(
        meterIdentifier: (string) $meters[1]->meter_name,
        buildingCode: (string) $buildingCodes[$meters[1]->building_idx],
        from: $from,
        to: $to,
    );
    $incompleteConsumption = app(BuildConsumptionSeriesAction::class)->execute(
        meterIdentifier: (string) $meters[2]->meter_name,
        buildingCode: (string) $buildingCodes[$meters[2]->building_idx],
        from: $from,
        to: $from,
    );
    $unknownConsumption = app(BuildConsumptionSeriesAction::class)->execute(
        meterIdentifier: (string) $meters[3]->meter_name,
        buildingCode: (string) $buildingCodes[$meters[3]->building_idx],
        from: $from,
        to: $from,
    );

    $normalTotal = collect($normalConsumption)->sum(fn (array $point): float => (float) $point['kwhTotal']);
    $abnormalTotal = collect($abnormalConsumption)->sum(fn (array $point): float => (float) $point['kwhTotal']);
    $demandPeak = collect($abnormalDemand)->max('kwDemand');

    expect($normalConsumption)->toHaveCount(24)
        ->and($abnormalConsumption)->toHaveCount(24)
        ->and($abnormalTotal)->toBeGreaterThan($normalTotal * 1.8)
        ->and($demandPeak)->toBeGreaterThan(300.0)
        ->and(collect($abnormalDemand)->contains(fn (array $point): bool => $point['peakMarker']['isPeak'] === true))->toBeTrue()
        ->and($incompleteConsumption[0]['confidence']['level'])->toBe('Incomplete')
        ->and($unknownConsumption[0]['confidence']['level'])->toBe('Unknown');

    $buildingSummaries = app(BuildBuildingConsumptionSummaryAction::class)->execute(
        from: $from,
        to: $from,
    );

    expect($buildingSummaries)->not->toBeEmpty()
        ->and($buildingSummaries[0]['buildingCode'])->toBe((string) $buildingCodes[$meters[1]->building_idx])
        ->and($buildingSummaries[0]['comparison']['isTopConsumer'])->toBeTrue()
        ->and(collect($buildingSummaries)->pluck('confidence.level')->contains('Calculated'))->toBeTrue()
        ->and(collect($buildingSummaries)->pluck('confidence.level')->contains('Incomplete'))->toBeTrue()
        ->and(collect($buildingSummaries)->pluck('confidence.level')->contains('Unknown'))->toBeTrue();
});
