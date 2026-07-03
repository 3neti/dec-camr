<?php

use App\Actions\Analytics\BuildConsumptionSeriesAction;
use App\Models\Building;
use App\Models\Gateway;
use App\Models\Meter;
use App\Models\MeterLocation;
use App\Models\Site;
use App\Models\User;
use App\Support\Analytics\ConsumptionSeriesGrain;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

function seedAnalyticsConsumptionContext(): array
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
    $building = Building::factory()->create([
        'site_idx' => $site->site_id,
        'building_code' => 'BLDG-A',
        'created_by_user_idx' => $owner->id,
        'modified_by_user_idx' => $owner->id,
    ]);
    $site->update([
        'building_idx' => $building->building_id,
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
        'gateway_sn' => 'GW-AN-001',
        'gateway_mac' => '00:11:22:33:44:55',
        'created_by_user_idx' => $owner->id,
        'modified_by_user_idx' => $owner->id,
    ]);
    $meter = Meter::factory()->create([
        'site_idx' => $site->site_id,
        'site_code' => $site->site_code,
        'rtu_idx' => $gateway->rtu_id,
        'location_idx' => $location->location_id,
        'building_idx' => $building->building_id,
        'meter_name' => 'MTR-AN-001',
        'meter_default_name' => 'AN001',
        'meter_multiplier' => 1.0,
        'meter_status' => 'ACTIVE',
        'created_by_user_idx' => $owner->id,
        'modified_by_user_idx' => $owner->id,
    ]);

    return compact('site', 'building', 'location', 'gateway', 'meter');
}

function seedAnalyticsConsumptionTelemetry(string $meterIdentifier, string $buildingCode, array $samples): void
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

test('consumption series calculates hourly kwh using report-compatible boundary semantics', function () {
    $context = seedAnalyticsConsumptionContext();
    $context['meter']->update([
        'meter_multiplier' => 1.25,
    ]);

    seedAnalyticsConsumptionTelemetry('MTR-AN-001', 'BLDG-A', [
        ['datetime' => '2026-07-01 00:00:00', 'wh_total' => 1000],
        ['datetime' => '2026-07-01 00:55:00', 'wh_total' => 1230],
        ['datetime' => '2026-07-01 01:00:00', 'wh_total' => 1230],
        ['datetime' => '2026-07-01 01:55:00', 'wh_total' => 1300],
    ]);

    $series = app(BuildConsumptionSeriesAction::class)->execute(
        meterIdentifier: 'MTR-AN-001',
        buildingCode: 'BLDG-A',
        from: CarbonImmutable::parse('2026-07-01 00:00:00'),
        to: CarbonImmutable::parse('2026-07-01 01:00:00'),
        grain: ConsumptionSeriesGrain::Hourly,
    );

    expect($series)->toHaveCount(2)
        ->and($series[0]['periodStart'])->toBe(CarbonImmutable::parse('2026-07-01 00:00:00')->toIso8601String())
        ->and($series[0]['periodEnd'])->toBe(CarbonImmutable::parse('2026-07-01 01:00:00')->toIso8601String())
        ->and($series[0]['grain'])->toBe('hourly')
        ->and($series[0]['context']['meterIdentifier'])->toBe('MTR-AN-001')
        ->and($series[0]['context']['meterId'])->toBe($context['meter']->meter_id)
        ->and($series[0]['context']['buildingCode'])->toBe('BLDG-A')
        ->and($series[0]['kwhTotal'])->toBe(287.5)
        ->and($series[0]['startReading']['whTotal'])->toBe(1000.0)
        ->and($series[0]['endReading']['whTotal'])->toBe(1230.0)
        ->and($series[0]['multiplier'])->toBe(1.25)
        ->and($series[0]['confidence']['level'])->toBe('Calculated')
        ->and($series[0]['missingData']['missingIntervalCount'])->toBe(0)
        ->and($series[0]['sourceLineage']['calculation'])->toBe('(end.whTotal - start.whTotal) * meterMultiplier')
        ->and($series[0]['sourceLineage']['startIdentifierMatchStrategy'])->toBe('meter_name')
        ->and($series[0]['sourceLineage']['endIdentifierMatchStrategy'])->toBe('meter_name')
        ->and($series[1]['kwhTotal'])->toBe(87.5);
});

test('consumption series supports daily grain with report-compatible daily end boundary', function () {
    seedAnalyticsConsumptionContext();

    seedAnalyticsConsumptionTelemetry('MTR-AN-001', 'BLDG-A', [
        ['datetime' => '2026-07-01 00:00:00', 'wh_total' => 1000],
        ['datetime' => '2026-07-01 23:55:00', 'wh_total' => 1500],
    ]);

    $series = app(BuildConsumptionSeriesAction::class)->execute(
        meterIdentifier: 'MTR-AN-001',
        buildingCode: 'BLDG-A',
        from: CarbonImmutable::parse('2026-07-01 00:00:00'),
        to: CarbonImmutable::parse('2026-07-01 00:00:00'),
        grain: 'daily',
    );

    expect($series)->toHaveCount(1)
        ->and($series[0]['grain'])->toBe('daily')
        ->and($series[0]['periodEnd'])->toBe(CarbonImmutable::parse('2026-07-02 00:00:00')->toIso8601String())
        ->and($series[0]['kwhTotal'])->toBe(500.0)
        ->and($series[0]['missingData']['expectedEndBoundary'])->toBe(CarbonImmutable::parse('2026-07-01 23:55:00')->toIso8601String())
        ->and($series[0]['confidence']['level'])->toBe('Calculated');
});

test('consumption series uses first qualifying readings at or after each boundary', function () {
    seedAnalyticsConsumptionContext();

    seedAnalyticsConsumptionTelemetry('MTR-AN-001', 'BLDG-A', [
        ['datetime' => '2026-06-30 23:50:00', 'wh_total' => 900],
        ['datetime' => '2026-06-30 23:56:00', 'wh_total' => 1000],
        ['datetime' => '2026-07-01 00:01:00', 'wh_total' => 1100],
        ['datetime' => '2026-07-01 00:54:00', 'wh_total' => 1200],
        ['datetime' => '2026-07-01 00:56:00', 'wh_total' => 1250],
        ['datetime' => '2026-07-01 00:59:00', 'wh_total' => 1300],
    ]);

    $series = app(BuildConsumptionSeriesAction::class)->execute(
        meterIdentifier: 'MTR-AN-001',
        buildingCode: 'BLDG-A',
        from: CarbonImmutable::parse('2026-07-01 00:00:00'),
        to: CarbonImmutable::parse('2026-07-01 00:00:00'),
    );

    expect($series)->toHaveCount(1)
        ->and($series[0]['startReading']['timestamp'])->toBe(CarbonImmutable::parse('2026-06-30 23:56:00')->toIso8601String())
        ->and($series[0]['startReading']['whTotal'])->toBe(1000.0)
        ->and($series[0]['endReading']['timestamp'])->toBe(CarbonImmutable::parse('2026-07-01 00:56:00')->toIso8601String())
        ->and($series[0]['endReading']['whTotal'])->toBe(1250.0)
        ->and($series[0]['kwhTotal'])->toBe(250.0);
});

test('consumption series exposes incomplete confidence for missing boundary readings', function () {
    seedAnalyticsConsumptionContext();

    seedAnalyticsConsumptionTelemetry('MTR-AN-001', 'BLDG-A', [
        ['datetime' => '2026-07-01 00:00:00', 'wh_total' => 1000],
    ]);

    $series = app(BuildConsumptionSeriesAction::class)->execute(
        meterIdentifier: 'MTR-AN-001',
        buildingCode: 'BLDG-A',
        from: CarbonImmutable::parse('2026-07-01 00:00:00'),
        to: CarbonImmutable::parse('2026-07-01 00:00:00'),
    );

    expect($series)->toHaveCount(1)
        ->and($series[0]['kwhTotal'])->toBeNull()
        ->and($series[0]['startReading']['whTotal'])->toBe(1000.0)
        ->and($series[0]['endReading'])->toBeNull()
        ->and($series[0]['missingData']['missingIntervalCount'])->toBe(1)
        ->and($series[0]['missingData']['missingStartReading'])->toBeFalse()
        ->and($series[0]['missingData']['missingEndReading'])->toBeTrue()
        ->and($series[0]['confidence']['level'])->toBe('Incomplete');
});

test('consumption series preserves numeric telemetry identifier lineage', function () {
    $context = seedAnalyticsConsumptionContext();
    $context['meter']->update([
        'meter_multiplier' => 2.0,
    ]);
    $meterIdentifier = (string) $context['meter']->meter_id;

    seedAnalyticsConsumptionTelemetry($meterIdentifier, 'BLDG-A', [
        ['datetime' => '2026-07-01 00:00:00', 'wh_total' => 200],
        ['datetime' => '2026-07-01 00:55:00', 'wh_total' => 250],
    ]);

    $series = app(BuildConsumptionSeriesAction::class)->execute(
        meterIdentifier: $meterIdentifier,
        buildingCode: 'BLDG-A',
        from: CarbonImmutable::parse('2026-07-01 00:00:00'),
        to: CarbonImmutable::parse('2026-07-01 00:00:00'),
    );

    expect($series)->toHaveCount(1)
        ->and($series[0]['kwhTotal'])->toBe(100.0)
        ->and($series[0]['context']['meterId'])->toBe($context['meter']->meter_id)
        ->and($series[0]['sourceLineage']['startIdentifierMatchStrategy'])->toBe('meter_id')
        ->and($series[0]['sourceLineage']['endIdentifierMatchStrategy'])->toBe('meter_id');
});

test('consumption series marks zero or negative deltas as unknown instead of hiding the window', function () {
    seedAnalyticsConsumptionContext();

    seedAnalyticsConsumptionTelemetry('MTR-AN-001', 'BLDG-A', [
        ['datetime' => '2026-07-01 00:00:00', 'wh_total' => 1000],
        ['datetime' => '2026-07-01 00:55:00', 'wh_total' => 1000],
    ]);

    $series = app(BuildConsumptionSeriesAction::class)->execute(
        meterIdentifier: 'MTR-AN-001',
        buildingCode: 'BLDG-A',
        from: CarbonImmutable::parse('2026-07-01 00:00:00'),
        to: CarbonImmutable::parse('2026-07-01 00:00:00'),
    );

    expect($series)->toHaveCount(1)
        ->and($series[0]['kwhTotal'])->toBe(0.0)
        ->and($series[0]['confidence']['level'])->toBe('Unknown')
        ->and($series[0]['confidence']['reason'])->toContain('zero or negative');
});
