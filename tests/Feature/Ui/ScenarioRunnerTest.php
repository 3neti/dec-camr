<?php

use App\Actions\Ui\SimulateTelemetryAction;
use Illuminate\Support\Facades\DB;

test('scenario list displays known scenarios', function () {
    $this->artisan('camr:scenario --list')
        ->assertSuccessful()
        ->expectsOutput('Available CAMR lifecycle scenarios:')
        ->expectsOutputToContain('admin-provisioning')
        ->expectsOutputToContain('operations-gateway-recovery')
        ->expectsOutputToContain('maintenance-meter-update')
        ->expectsOutputToContain('analyst-report-export')
        ->expectsOutputToContain('analytics-demo')
        ->expectsOutputToContain('fresh-install-smoke')
        ->expectsOutputToContain('heavy-data-readiness');
});

test('invalid scenario is rejected with supported list', function () {
    $this->artisan('camr:scenario bogus --dry-run')
        ->assertFailed()
        ->expectsOutput('Unknown scenario: bogus')
        ->expectsOutputToContain('Available scenarios:');
});

test('dry-run does not mutate database', function () {
    $beforeUsers = DB::table('users')->count();

    $this->artisan(sprintf('camr:scenario operations-gateway-recovery --dry-run'))
        ->assertSuccessful()
        ->expectsOutputToContain('Mode: dry-run')
        ->expectsOutputToContain('Seed status: dry-run (profile=demo)');

    expect(DB::table('users')->count())->toBe($beforeUsers);
});

test('dry-run performs zero simulator writes', function () {
    $beforeState = [
        'meter_data_count' => DB::table('meter_data')->count(),
        'meter_rtu_state' => DB::table('meter_rtu')
            ->orderBy('rtu_id')
            ->get(['rtu_id', 'last_log_update', 'soft_rev', 'update_rtu', 'update_rtu_location', 'update_rtu_ssh', 'update_rtu_force_lp'])
            ->map(static fn (object $row): array => (array) $row)
            ->toArray(),
        'meter_details_state' => DB::table('meter_details')
            ->orderBy('meter_id')
            ->get(['meter_id', 'last_log_update', 'soft_rev'])
            ->map(static fn (object $row): array => (array) $row)
            ->toArray(),
        'meter_site_state' => DB::table('meter_site')
            ->orderBy('site_id')
            ->get(['site_id', 'last_log_update'])
            ->map(static fn (object $row): array => (array) $row)
            ->toArray(),
    ];

    $this->artisan('camr:scenario operations-gateway-recovery --dry-run')
        ->assertSuccessful()
        ->expectsOutputToContain('Simulation status: dry-run (scenario=offline-recovery)');

    $afterState = [
        'meter_data_count' => DB::table('meter_data')->count(),
        'meter_rtu_state' => DB::table('meter_rtu')
            ->orderBy('rtu_id')
            ->get(['rtu_id', 'last_log_update', 'soft_rev', 'update_rtu', 'update_rtu_location', 'update_rtu_ssh', 'update_rtu_force_lp'])
            ->map(static fn (object $row): array => (array) $row)
            ->toArray(),
        'meter_details_state' => DB::table('meter_details')
            ->orderBy('meter_id')
            ->get(['meter_id', 'last_log_update', 'soft_rev'])
            ->map(static fn (object $row): array => (array) $row)
            ->toArray(),
        'meter_site_state' => DB::table('meter_site')
            ->orderBy('site_id')
            ->get(['site_id', 'last_log_update'])
            ->map(static fn (object $row): array => (array) $row)
            ->toArray(),
    ];

    expect($afterState['meter_data_count'])->toBe($beforeState['meter_data_count']);
    expect($afterState['meter_rtu_state'])->toBe($beforeState['meter_rtu_state']);
    expect($afterState['meter_details_state'])->toBe($beforeState['meter_details_state']);
    expect($afterState['meter_site_state'])->toBe($beforeState['meter_site_state']);
});

test('custom anchor is accepted and reported', function () {
    $anchor = '2026-07-01 08:00:00';

    $this->artisan(sprintf('camr:scenario analyst-report-export --dry-run --anchor="%s"', $anchor))
        ->assertSuccessful()
        ->expectsOutputToContain('Mode: dry-run')
        ->expectsOutputToContain(sprintf('Anchor: %s', $anchor));
});

test('invalid anchor is rejected', function () {
    $simulator = new SimulateTelemetryAction;
    $method = (new ReflectionClass($simulator))->getMethod('resolveDeterministicAnchor');
    $method->setAccessible(true);

    $this->expectException(InvalidArgumentException::class);
    $this->expectExceptionMessage('Invalid anchor format: bad-anchor. Expected Y-m-d H:i:s');
    $method->invoke($simulator, 'bad-anchor');
});

test('no-seed and no-simulate skips both execution steps', function () {
    $this->artisan('camr:scenario fresh-install-smoke --no-seed --no-simulate')
        ->assertSuccessful()
        ->expectsOutputToContain('Seed status: skipped (profile=minimal)')
        ->expectsOutputToContain('Simulation status: skipped (scenario=normal)');
});
