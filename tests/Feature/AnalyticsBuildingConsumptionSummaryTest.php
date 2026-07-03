<?php

use App\Actions\Analytics\BuildBuildingConsumptionSummaryAction;
use App\Models\Building;
use App\Models\Gateway;
use App\Models\Meter;
use App\Models\MeterLocation;
use App\Models\Site;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

function seedAnalyticsBuildingSummaryContext(): array
{
    $owner = User::factory()->create([
        'user_type' => 'Admin',
        'user_access' => 'ALL',
    ]);
    $site = Site::factory()->create([
        'site_code' => 'SITEA',
        'created_by_user_idx' => $owner->id,
        'modified_by_user_idx' => $owner->id,
    ]);
    $location = MeterLocation::factory()->create([
        'site_idx' => $site->site_id,
        'created_by_user_idx' => $owner->id,
        'modified_by_user_idx' => $owner->id,
    ]);
    $gateway = Gateway::factory()->create([
        'site_idx' => $site->site_id,
        'location_idx' => $location->location_id,
        'site_code' => $site->site_code,
        'created_by_user_idx' => $owner->id,
        'modified_by_user_idx' => $owner->id,
    ]);
    $buildingA = Building::factory()->create([
        'site_idx' => $site->site_id,
        'building_code' => 'BLDG-A',
        'building_description' => 'Building A',
        'created_by_user_idx' => $owner->id,
        'modified_by_user_idx' => $owner->id,
    ]);
    $buildingB = Building::factory()->create([
        'site_idx' => $site->site_id,
        'building_code' => 'BLDG-B',
        'building_description' => 'Building B',
        'created_by_user_idx' => $owner->id,
        'modified_by_user_idx' => $owner->id,
    ]);
    $site->update([
        'building_idx' => $buildingA->building_id,
    ]);

    return compact('owner', 'site', 'location', 'gateway', 'buildingA', 'buildingB');
}

function seedAnalyticsBuildingSummaryMeter(array $context, Building $building, string $meterName, float $multiplier = 1.0): Meter
{
    return Meter::factory()->create([
        'site_idx' => $context['site']->site_id,
        'site_code' => $context['site']->site_code,
        'rtu_idx' => $context['gateway']->rtu_id,
        'location_idx' => $context['location']->location_id,
        'building_idx' => $building->building_id,
        'meter_name' => $meterName,
        'meter_default_name' => $meterName,
        'meter_multiplier' => $multiplier,
        'meter_status' => 'ACTIVE',
        'created_by_user_idx' => $context['owner']->id,
        'modified_by_user_idx' => $context['owner']->id,
    ]);
}

function seedAnalyticsBuildingSummaryTelemetry(string $meterIdentifier, string $buildingCode, array $samples): void
{
    foreach ($samples as $sample) {
        DB::table('meter_data')->insert([
            'location' => $buildingCode,
            'meter_id' => $meterIdentifier,
            'datetime' => $sample['datetime'],
            'vrms_a' => $sample['vrms_a'] ?? 230,
            'irms_a' => $sample['irms_a'] ?? 10,
            'freq' => $sample['freq'] ?? 60,
            'pf' => $sample['pf'] ?? 0.97,
            'watt' => $sample['watt'] ?? 5000,
            'wh_total' => $sample['wh_total'],
            'created_at' => $sample['datetime'],
            'updated_at' => $sample['datetime'],
        ]);
    }
}

test('building consumption summary aggregates calculated consumption by building', function () {
    $context = seedAnalyticsBuildingSummaryContext();
    seedAnalyticsBuildingSummaryMeter($context, $context['buildingA'], 'MTR-A1', 1.0);
    seedAnalyticsBuildingSummaryMeter($context, $context['buildingA'], 'MTR-A2', 2.0);
    seedAnalyticsBuildingSummaryMeter($context, $context['buildingB'], 'MTR-B1', 1.0);

    seedAnalyticsBuildingSummaryTelemetry('MTR-A1', 'BLDG-A', [
        ['datetime' => '2026-07-01 00:00:00', 'wh_total' => 100],
        ['datetime' => '2026-07-01 00:55:00', 'wh_total' => 180],
    ]);
    seedAnalyticsBuildingSummaryTelemetry('MTR-A2', 'BLDG-A', [
        ['datetime' => '2026-07-01 00:00:00', 'wh_total' => 200],
        ['datetime' => '2026-07-01 00:55:00', 'wh_total' => 260],
    ]);
    seedAnalyticsBuildingSummaryTelemetry('MTR-B1', 'BLDG-B', [
        ['datetime' => '2026-07-01 00:00:00', 'wh_total' => 50],
        ['datetime' => '2026-07-01 00:55:00', 'wh_total' => 90],
    ]);

    $summaries = app(BuildBuildingConsumptionSummaryAction::class)->execute(
        from: CarbonImmutable::parse('2026-07-01 00:00:00'),
        to: CarbonImmutable::parse('2026-07-01 00:00:00'),
    );

    expect($summaries)->toHaveCount(2)
        ->and($summaries[0]['buildingCode'])->toBe('BLDG-A')
        ->and($summaries[0]['buildingName'])->toBe('Building A')
        ->and($summaries[0]['site']['siteId'])->toBe($context['site']->site_id)
        ->and($summaries[0]['site']['siteCode'])->toBe('SITEA')
        ->and($summaries[0]['totalKwh'])->toBe(200.0)
        ->and($summaries[0]['meterCount'])->toBe(2)
        ->and($summaries[0]['seriesPointCount'])->toBe(2)
        ->and($summaries[0]['missingData']['missingIntervalCount'])->toBe(0)
        ->and($summaries[0]['confidence']['level'])->toBe('Calculated')
        ->and($summaries[0]['comparison']['isTopConsumer'])->toBeTrue()
        ->and($summaries[0]['comparison']['topConsumerKwh'])->toBe(200.0)
        ->and($summaries[0]['sourceLineage']['sourceContract'])->toBe('ConsumptionSeriesPoint')
        ->and($summaries[0]['sourceLineage']['includedMeterCount'])->toBe(2)
        ->and($summaries[1]['buildingCode'])->toBe('BLDG-B')
        ->and($summaries[1]['totalKwh'])->toBe(40.0)
        ->and($summaries[1]['comparison']['isTopConsumer'])->toBeFalse();
});

test('building consumption summary exposes incomplete evidence for missing series windows', function () {
    $context = seedAnalyticsBuildingSummaryContext();
    seedAnalyticsBuildingSummaryMeter($context, $context['buildingA'], 'MTR-A1');

    seedAnalyticsBuildingSummaryTelemetry('MTR-A1', 'BLDG-A', [
        ['datetime' => '2026-07-01 00:00:00', 'wh_total' => 100],
    ]);

    $summaries = app(BuildBuildingConsumptionSummaryAction::class)->execute(
        from: CarbonImmutable::parse('2026-07-01 00:00:00'),
        to: CarbonImmutable::parse('2026-07-01 00:00:00'),
    );

    $buildingA = collect($summaries)->firstWhere('buildingCode', 'BLDG-A');

    expect($buildingA['totalKwh'])->toBe(0.0)
        ->and($buildingA['meterCount'])->toBe(1)
        ->and($buildingA['seriesPointCount'])->toBe(1)
        ->and($buildingA['missingData']['missingIntervalCount'])->toBe(1)
        ->and($buildingA['missingData']['incompleteSeriesPointCount'])->toBe(1)
        ->and($buildingA['confidence']['level'])->toBe('Incomplete');
});

test('building consumption summary marks buildings with only unknown windows as unknown', function () {
    $context = seedAnalyticsBuildingSummaryContext();
    seedAnalyticsBuildingSummaryMeter($context, $context['buildingA'], 'MTR-A1');

    seedAnalyticsBuildingSummaryTelemetry('MTR-A1', 'BLDG-A', [
        ['datetime' => '2026-07-01 00:00:00', 'wh_total' => 100],
        ['datetime' => '2026-07-01 00:55:00', 'wh_total' => 100],
    ]);

    $summaries = app(BuildBuildingConsumptionSummaryAction::class)->execute(
        from: CarbonImmutable::parse('2026-07-01 00:00:00'),
        to: CarbonImmutable::parse('2026-07-01 00:00:00'),
    );

    $buildingA = collect($summaries)->firstWhere('buildingCode', 'BLDG-A');

    expect($buildingA['totalKwh'])->toBe(0.0)
        ->and($buildingA['missingData']['unknownSeriesPointCount'])->toBe(1)
        ->and($buildingA['confidence']['level'])->toBe('Unknown');
});
