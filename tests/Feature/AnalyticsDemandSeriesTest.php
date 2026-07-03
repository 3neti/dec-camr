<?php

use App\Actions\Analytics\BuildDemandSeriesAction;
use App\Models\Building;
use App\Models\Gateway;
use App\Models\Meter;
use App\Models\MeterLocation;
use App\Models\Site;
use App\Models\User;
use App\Support\Analytics\DemandSeriesGrain;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

function seedAnalyticsDemandContext(): array
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

function seedAnalyticsDemandTelemetry(string $meterIdentifier, string $buildingCode, array $samples): void
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

test('demand series calculates hourly kw demand using report-compatible semantics', function () {
    $context = seedAnalyticsDemandContext();
    $context['meter']->update([
        'meter_multiplier' => 1.25,
    ]);

    seedAnalyticsDemandTelemetry('MTR-AN-001', 'BLDG-A', [
        ['datetime' => '2026-07-01 00:00:00', 'wh_total' => 1000],
        ['datetime' => '2026-07-01 01:00:00', 'wh_total' => 1230],
        ['datetime' => '2026-07-01 01:55:00', 'wh_total' => 1300],
    ]);

    $series = app(BuildDemandSeriesAction::class)->execute(
        meterIdentifier: 'MTR-AN-001',
        buildingCode: 'BLDG-A',
        from: CarbonImmutable::parse('2026-07-01 00:00:00'),
        to: CarbonImmutable::parse('2026-07-01 01:00:00'),
        grain: DemandSeriesGrain::Hourly,
    );

    expect($series)->toHaveCount(2)
        ->and($series[0]['periodStart'])->toBe(CarbonImmutable::parse('2026-07-01 00:00:00')->toIso8601String())
        ->and($series[0]['periodEnd'])->toBe(CarbonImmutable::parse('2026-07-01 01:00:00')->toIso8601String())
        ->and($series[0]['grain'])->toBe('hourly')
        ->and($series[0]['context']['meterIdentifier'])->toBe('MTR-AN-001')
        ->and($series[0]['context']['meterId'])->toBe($context['meter']->meter_id)
        ->and($series[0]['kwDemand'])->toBe(287.5)
        ->and($series[0]['minReading']['whTotal'])->toBe(1000.0)
        ->and($series[0]['maxReading']['whTotal'])->toBe(1230.0)
        ->and($series[0]['elapsedMinutes'])->toBe(60.0)
        ->and($series[0]['multiplier'])->toBe(1.25)
        ->and($series[0]['confidence']['level'])->toBe('Calculated')
        ->and($series[0]['peakMarker']['isPeak'])->toBeTrue()
        ->and($series[0]['sourceLineage']['calculation'])->toBe('((max.whTotal - min.whTotal) / elapsedMinutes) * 60 * meterMultiplier')
        ->and($series[0]['sourceLineage']['minIdentifierMatchStrategy'])->toBe('meter_name')
        ->and($series[0]['sourceLineage']['maxIdentifierMatchStrategy'])->toBe('meter_name')
        ->and($series[1]['kwDemand'])->toBe(95.45)
        ->and($series[1]['peakMarker']['isPeak'])->toBeFalse();
});

test('demand series supports fifteen-minute grain with report-compatible min and max boundaries', function () {
    seedAnalyticsDemandContext();

    seedAnalyticsDemandTelemetry('MTR-AN-001', 'BLDG-A', [
        ['datetime' => '2026-07-01 00:10:00', 'wh_total' => 1000],
        ['datetime' => '2026-07-01 00:16:00', 'wh_total' => 1040],
        ['datetime' => '2026-07-01 00:20:00', 'wh_total' => 1100],
    ]);

    $series = app(BuildDemandSeriesAction::class)->execute(
        meterIdentifier: 'MTR-AN-001',
        buildingCode: 'BLDG-A',
        from: CarbonImmutable::parse('2026-07-01 00:00:00'),
        to: CarbonImmutable::parse('2026-07-01 00:00:00'),
        grain: 'fifteen-minute',
    );

    expect($series)->toHaveCount(1)
        ->and($series[0]['grain'])->toBe('fifteen-minute')
        ->and($series[0]['periodEnd'])->toBe(CarbonImmutable::parse('2026-07-01 00:15:00')->toIso8601String())
        ->and($series[0]['minReading']['timestamp'])->toBe(CarbonImmutable::parse('2026-07-01 00:10:00')->toIso8601String())
        ->and($series[0]['maxReading']['timestamp'])->toBe(CarbonImmutable::parse('2026-07-01 00:16:00')->toIso8601String())
        ->and($series[0]['elapsedMinutes'])->toBe(6.0)
        ->and($series[0]['kwDemand'])->toBe(400.0)
        ->and($series[0]['confidence']['level'])->toBe('Calculated');
});

test('demand series exposes incomplete confidence for missing boundary readings', function () {
    seedAnalyticsDemandContext();

    seedAnalyticsDemandTelemetry('MTR-AN-001', 'BLDG-A', [
        ['datetime' => '2026-07-01 00:00:00', 'wh_total' => 1000],
    ]);

    $series = app(BuildDemandSeriesAction::class)->execute(
        meterIdentifier: 'MTR-AN-001',
        buildingCode: 'BLDG-A',
        from: CarbonImmutable::parse('2026-07-01 00:00:00'),
        to: CarbonImmutable::parse('2026-07-01 00:00:00'),
    );

    expect($series)->toHaveCount(1)
        ->and($series[0]['kwDemand'])->toBeNull()
        ->and($series[0]['minReading']['whTotal'])->toBe(1000.0)
        ->and($series[0]['maxReading'])->toBeNull()
        ->and($series[0]['missingData']['missingIntervalCount'])->toBe(1)
        ->and($series[0]['missingData']['missingMinReading'])->toBeFalse()
        ->and($series[0]['missingData']['missingMaxReading'])->toBeTrue()
        ->and($series[0]['confidence']['level'])->toBe('Incomplete');
});

test('demand series preserves numeric telemetry identifier lineage', function () {
    $context = seedAnalyticsDemandContext();
    $context['meter']->update([
        'meter_multiplier' => 2.0,
    ]);
    $meterIdentifier = (string) $context['meter']->meter_id;

    seedAnalyticsDemandTelemetry($meterIdentifier, 'BLDG-A', [
        ['datetime' => '2026-07-01 00:00:00', 'wh_total' => 200],
        ['datetime' => '2026-07-01 01:00:00', 'wh_total' => 250],
    ]);

    $series = app(BuildDemandSeriesAction::class)->execute(
        meterIdentifier: $meterIdentifier,
        buildingCode: 'BLDG-A',
        from: CarbonImmutable::parse('2026-07-01 00:00:00'),
        to: CarbonImmutable::parse('2026-07-01 00:00:00'),
    );

    expect($series)->toHaveCount(1)
        ->and($series[0]['kwDemand'])->toBe(100.0)
        ->and($series[0]['context']['meterId'])->toBe($context['meter']->meter_id)
        ->and($series[0]['sourceLineage']['minIdentifierMatchStrategy'])->toBe('meter_id')
        ->and($series[0]['sourceLineage']['maxIdentifierMatchStrategy'])->toBe('meter_id');
});

test('demand series marks zero or negative demand as unknown instead of hiding the window', function () {
    seedAnalyticsDemandContext();

    seedAnalyticsDemandTelemetry('MTR-AN-001', 'BLDG-A', [
        ['datetime' => '2026-07-01 00:00:00', 'wh_total' => 1000],
        ['datetime' => '2026-07-01 01:00:00', 'wh_total' => 1000],
    ]);

    $series = app(BuildDemandSeriesAction::class)->execute(
        meterIdentifier: 'MTR-AN-001',
        buildingCode: 'BLDG-A',
        from: CarbonImmutable::parse('2026-07-01 00:00:00'),
        to: CarbonImmutable::parse('2026-07-01 00:00:00'),
    );

    expect($series)->toHaveCount(1)
        ->and($series[0]['kwDemand'])->toBeNull()
        ->and($series[0]['confidence']['level'])->toBe('Unknown')
        ->and($series[0]['confidence']['reason'])->toContain('zero or negative');
});

test('demand series applies legacy zero-min fallback to avoid artificial spikes', function () {
    seedAnalyticsDemandContext();

    seedAnalyticsDemandTelemetry('MTR-AN-001', 'BLDG-A', [
        ['datetime' => '2026-07-01 00:00:00', 'wh_total' => 0],
        ['datetime' => '2026-07-01 01:00:00', 'wh_total' => 1200],
    ]);

    $series = app(BuildDemandSeriesAction::class)->execute(
        meterIdentifier: 'MTR-AN-001',
        buildingCode: 'BLDG-A',
        from: CarbonImmutable::parse('2026-07-01 00:00:00'),
        to: CarbonImmutable::parse('2026-07-01 00:00:00'),
    );

    expect($series)->toHaveCount(1)
        ->and($series[0]['minReading']['timestamp'])->toBe(CarbonImmutable::parse('2026-07-01 01:00:00')->toIso8601String())
        ->and($series[0]['minReading']['whTotal'])->toBe(1200.0)
        ->and($series[0]['maxReading']['timestamp'])->toBe(CarbonImmutable::parse('2026-07-01 01:00:00')->toIso8601String())
        ->and($series[0]['maxReading']['whTotal'])->toBe(1200.0)
        ->and($series[0]['elapsedMinutes'])->toBe(1.0)
        ->and($series[0]['kwDemand'])->toBeNull()
        ->and($series[0]['confidence']['level'])->toBe('Unknown');
});
