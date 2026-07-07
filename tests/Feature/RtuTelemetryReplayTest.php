<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\DB;

function scadaReplayFixture(): string
{
    return database_path('fixtures/telemetry/scada-demo-readings.csv');
}

test('camr replay telemetry dry-run performs zero writes', function () {
    $beforeCount = DB::table('meter_data')->count();

    $this->artisan('camr:replay-telemetry --file='.scadaReplayFixture().' --dry-run --anchor="2026-07-01 08:00:00"')
        ->assertSuccessful()
        ->expectsOutputToContain('Telemetry replay complete.')
        ->expectsOutputToContain('Dry run: yes')
        ->expectsOutputToContain('Rows replayed: 8')
        ->expectsOutputToContain('Rows saved: 0');

    expect(DB::table('meter_data')->count())->toBe($beforeCount);
});

test('camr replay telemetry replays fixture through live ingest path', function () {
    $this->artisan('camr:seed-profile --profile=demo')
        ->assertSuccessful();

    $this->artisan('camr:replay-telemetry --file='.scadaReplayFixture().' --anchor="2026-07-01 08:00:00"')
        ->assertSuccessful()
        ->expectsOutputToContain('Rows replayed: 8')
        ->expectsOutputToContain('Rows saved: 8')
        ->expectsOutputToContain('Rows failed: 0');

    expect(DB::table('meter_data')
        ->where('meter_id', 'MDTR-002')
        ->where('location', 'BLD-D-OPS-01')
        ->where('datetime', '2026-07-01 08:10:00')
        ->where('mac_addr', 'aa:bb:cc:dd:55:01')
        ->value('wh_total'))->toBe(200450.0);

    expect(DB::table('meter_rtu')
        ->where('gateway_mac', 'aa:bb:cc:dd:55:01')
        ->value('last_log_update'))->toBe('2026-07-01 08:15:00');
});

test('camr replay telemetry is idempotent when run repeatedly', function () {
    $this->artisan('camr:seed-profile --profile=demo')
        ->assertSuccessful();

    $command = 'camr:replay-telemetry --file='.scadaReplayFixture().' --anchor="2026-07-01 08:00:00"';

    $this->artisan($command)->assertSuccessful();
    $firstCount = DB::table('meter_data')->where('mac_addr', 'aa:bb:cc:dd:55:01')->count();

    $this->artisan($command)->assertSuccessful();
    $secondCount = DB::table('meter_data')->where('mac_addr', 'aa:bb:cc:dd:55:01')->count();

    expect($firstCount)->toBe($secondCount);
});

test('camr replay telemetry rejects missing file clearly', function () {
    $this->artisan('camr:replay-telemetry --file=/tmp/not-a-camr-file.csv')
        ->assertFailed()
        ->expectsOutputToContain('Telemetry replay file is not readable: /tmp/not-a-camr-file.csv');
});

test('camr replay telemetry counts malformed rows without killing replay', function () {
    $file = tempnam(sys_get_temp_dir(), 'camr-replay-');
    file_put_contents($file, implode(PHP_EOL, [
        'save_to_meter_data,meter_id,location,datetime,mac_address,wh_total',
        '1,,BLD-D-OPS-01,2026-07-01 08:00:00,aa:bb:cc:dd:55:01,100',
        '1,MDTR-001,BLD-D-OPS-01,2026-07-01 08:05:00,aa:bb:cc:dd:55:01,125',
    ]));

    $this->artisan('camr:seed-profile --profile=demo')
        ->assertSuccessful();

    $this->artisan('camr:replay-telemetry --file='.$file)
        ->assertSuccessful()
        ->expectsOutputToContain('Rows seen: 2')
        ->expectsOutputToContain('Rows replayed: 1')
        ->expectsOutputToContain('Rows failed: 1');
});

test('live scada demo scenario populates dashboard and analytics from replayed telemetry', function () {
    $this->artisan('camr:scenario live-scada-demo')
        ->assertSuccessful()
        ->expectsOutputToContain('Scenario: live-scada-demo')
        ->expectsOutputToContain('Simulation status: completed (scenario=file-replay)');

    $user = User::query()->where('name', 'ops_admin_demo')->firstOrFail();

    $dashboardResponse = $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk();

    $analyticsResponse = $this->withSession(['loginID' => $user->id])
        ->get('/analytics')
        ->assertOk();

    expect($dashboardResponse->inertiaProps('telemetrySummary.recentReadings'))->toBeGreaterThan(0)
        ->and($dashboardResponse->inertiaProps('telemetrySummary.recentTelemetry'))->not->toBeEmpty()
        ->and($analyticsResponse->inertiaProps('analyticsContext.hasData'))->toBeTrue()
        ->and($analyticsResponse->inertiaProps('contractEvidence.consumptionPointCount'))->toBeGreaterThan(0)
        ->and($analyticsResponse->inertiaProps('contractEvidence.demandPointCount'))->toBeGreaterThan(0);
});
