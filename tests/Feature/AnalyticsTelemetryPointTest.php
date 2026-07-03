<?php

use App\Actions\Analytics\BuildTelemetryPointsAction;
use App\Models\Building;
use App\Models\Gateway;
use App\Models\Meter;
use App\Models\MeterLocation;
use App\Models\Site;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

function seedAnalyticsTelemetryContext(): array
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
        'meter_status' => 'ACTIVE',
        'created_by_user_idx' => $owner->id,
        'modified_by_user_idx' => $owner->id,
    ]);

    return compact('site', 'building', 'location', 'gateway', 'meter');
}

test('telemetry point contract returns persisted measurement context with measured confidence', function () {
    $context = seedAnalyticsTelemetryContext();
    $timestamp = CarbonImmutable::parse('2026-07-01 08:15:00');

    DB::table('meter_data')->insert([
        'location' => 'BLDG-A',
        'meter_id' => 'MTR-AN-001',
        'datetime' => $timestamp->toDateTimeString(),
        'vrms_a' => 230.1,
        'vrms_b' => 231.2,
        'vrms_c' => 232.3,
        'irms_a' => 12.1,
        'irms_b' => 12.2,
        'irms_c' => 12.3,
        'freq' => 60,
        'pf' => 0.97,
        'watt' => 7100,
        'va' => 7300,
        'var' => 400,
        'wh_del' => 1200,
        'wh_rec' => 0,
        'wh_net' => 1200,
        'wh_total' => 101200,
        'mac_addr' => '00:11:22:33:44:55',
        'soft_rev' => '2.12',
        'dt' => $timestamp,
        'created_at' => $timestamp,
        'updated_at' => $timestamp,
    ]);

    $points = app(BuildTelemetryPointsAction::class)->execute(
        from: $timestamp->subMinute(),
        to: $timestamp->addMinute(),
    );

    expect($points)->toHaveCount(1)
        ->and($points[0]['rawMeterIdentifier'])->toBe('MTR-AN-001')
        ->and($points[0]['rawLocation'])->toBe('BLDG-A')
        ->and($points[0]['timestamp'])->toBe($timestamp->toIso8601String())
        ->and($points[0]['context']['meterId'])->toBe($context['meter']->meter_id)
        ->and($points[0]['context']['meterName'])->toBe('MTR-AN-001')
        ->and($points[0]['context']['siteId'])->toBe($context['site']->site_id)
        ->and($points[0]['context']['siteCode'])->toBe('SITEA')
        ->and($points[0]['context']['buildingId'])->toBe($context['building']->building_id)
        ->and($points[0]['context']['buildingCode'])->toBe('BLDG-A')
        ->and($points[0]['context']['gatewayId'])->toBe($context['gateway']->rtu_id)
        ->and($points[0]['context']['gatewaySn'])->toBe('GW-AN-001')
        ->and($points[0]['context']['gatewayMac'])->toBe('00:11:22:33:44:55')
        ->and($points[0]['measurements']['voltage']['a'])->toBe(230.1)
        ->and($points[0]['measurements']['current']['b'])->toBe(12.2)
        ->and($points[0]['measurements']['frequency'])->toBe(60.0)
        ->and($points[0]['measurements']['powerFactor'])->toBe(0.97)
        ->and($points[0]['measurements']['energy']['whTotal'])->toBe(101200.0)
        ->and($points[0]['measurements']['device']['gatewayMacFromTelemetry'])->toBe('00:11:22:33:44:55')
        ->and($points[0]['measurements']['device']['softRev'])->toBe('2.12')
        ->and($points[0]['confidence']['level'])->toBe('Measured')
        ->and($points[0]['confidence']['missingContext'])->toBeFalse()
        ->and($points[0]['confidence']['zeroDefaultAmbiguity'])->toBeFalse()
        ->and($points[0]['sourceLineage']['table'])->toBe('meter_data')
        ->and($points[0]['sourceLineage']['timestampColumn'])->toBe('datetime')
        ->and($points[0]['sourceLineage']['joinedMeterContext'])->toBeTrue()
        ->and($points[0]['sourceLineage']['identifierMatchStrategy'])->toBe('meter_name');
});

test('telemetry point contract resolves numeric legacy meter identifiers', function () {
    $context = seedAnalyticsTelemetryContext();
    $timestamp = CarbonImmutable::parse('2026-07-01 08:30:00');

    DB::table('meter_data')->insert([
        'location' => 'BLDG-A',
        'meter_id' => (string) $context['meter']->meter_id,
        'datetime' => $timestamp->toDateTimeString(),
        'vrms_a' => 229.5,
        'irms_a' => 9.4,
        'freq' => 60,
        'pf' => 0.96,
        'watt' => 5200,
        'wh_total' => 99100,
        'created_at' => $timestamp,
        'updated_at' => $timestamp,
    ]);

    $points = app(BuildTelemetryPointsAction::class)->execute();

    expect($points)->toHaveCount(1)
        ->and($points[0]['rawMeterIdentifier'])->toBe((string) $context['meter']->meter_id)
        ->and($points[0]['context']['meterId'])->toBe($context['meter']->meter_id)
        ->and($points[0]['context']['meterName'])->toBe('MTR-AN-001')
        ->and($points[0]['confidence']['level'])->toBe('Measured')
        ->and($points[0]['sourceLineage']['joinedMeterContext'])->toBeTrue()
        ->and($points[0]['sourceLineage']['identifierMatchStrategy'])->toBe('meter_id');
});

test('telemetry point contract marks missing meter context as incomplete', function () {
    $timestamp = CarbonImmutable::parse('2026-07-01 09:00:00');

    DB::table('meter_data')->insert([
        'location' => 'UNKNOWN',
        'meter_id' => 'MISSING-METER',
        'datetime' => $timestamp->toDateTimeString(),
        'vrms_a' => 230,
        'irms_a' => 10,
        'freq' => 60,
        'wh_total' => 1200,
        'created_at' => $timestamp,
        'updated_at' => $timestamp,
    ]);

    $points = app(BuildTelemetryPointsAction::class)->execute();

    expect($points)->toHaveCount(1)
        ->and($points[0]['context']['meterId'])->toBeNull()
        ->and($points[0]['context']['gatewaySn'])->toBeNull()
        ->and($points[0]['confidence']['level'])->toBe('Incomplete')
        ->and($points[0]['confidence']['missingContext'])->toBeTrue()
        ->and($points[0]['sourceLineage']['joinedMeterContext'])->toBeFalse()
        ->and($points[0]['sourceLineage']['identifierMatchStrategy'])->toBe('none');
});

test('telemetry point contract marks all-zero measurement rows as unknown', function () {
    seedAnalyticsTelemetryContext();
    $timestamp = CarbonImmutable::parse('2026-07-01 09:15:00');

    DB::table('meter_data')->insert([
        'location' => 'BLDG-A',
        'meter_id' => 'MTR-AN-001',
        'datetime' => $timestamp->toDateTimeString(),
        'created_at' => $timestamp,
        'updated_at' => $timestamp,
    ]);

    $points = app(BuildTelemetryPointsAction::class)->execute();

    expect($points)->toHaveCount(1)
        ->and($points[0]['context']['meterName'])->toBe('MTR-AN-001')
        ->and($points[0]['measurements']['voltage']['a'])->toBe(0.0)
        ->and($points[0]['confidence']['level'])->toBe('Unknown')
        ->and($points[0]['confidence']['missingContext'])->toBeFalse()
        ->and($points[0]['confidence']['zeroDefaultAmbiguity'])->toBeTrue()
        ->and($points[0]['sourceLineage']['identifierMatchStrategy'])->toBe('meter_name');
});

test('telemetry point contract applies chronological range and limit', function () {
    seedAnalyticsTelemetryContext();

    foreach ([
        '2026-07-01 07:45:00',
        '2026-07-01 08:00:00',
        '2026-07-01 08:15:00',
    ] as $index => $datetime) {
        DB::table('meter_data')->insert([
            'location' => 'BLDG-A',
            'meter_id' => 'MTR-AN-001',
            'datetime' => $datetime,
            'wh_total' => 1000 + $index,
            'created_at' => $datetime,
            'updated_at' => $datetime,
        ]);
    }

    $points = app(BuildTelemetryPointsAction::class)->execute(
        from: CarbonImmutable::parse('2026-07-01 07:59:59'),
        to: CarbonImmutable::parse('2026-07-01 08:16:00'),
        limit: 1,
    );

    expect($points)->toHaveCount(1)
        ->and($points[0]['timestamp'])->toBe(CarbonImmutable::parse('2026-07-01 08:00:00')->toIso8601String())
        ->and($points[0]['measurements']['energy']['whTotal'])->toBe(1001.0);
});
