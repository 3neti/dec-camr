<?php

declare(strict_types=1);

use App\Models\ConfigurationFile;
use App\Models\Gateway;
use App\Models\Meter;
use App\Models\MeterLocation;
use App\Models\Site;
use Illuminate\Support\Facades\DB;

function rtuUrl(string $path, string $mac): string
{
    return '/rtu/index.php/rtu/rtu_check_update/'.rawurlencode($mac)."/{$path}";
}

function rtuTelemetryPayload(array $overrides = []): array
{
    return array_merge([
        'save_to_meter_data' => 1,
        'meter_id' => 'MTR-001',
        'location' => 'SITEA',
        'datetime' => '2026-07-01 00:00:00',
        'mac_address' => 'AA:BB:CC:DD:EE:01',
        'gateway_mac' => 'AA:BB:CC:DD:EE:01',
        'vrms_a' => 220.1,
        'vrms_b' => 221.2,
        'vrms_c' => 222.3,
        'irms_a' => 1.1,
        'irms_b' => 1.2,
        'irms_c' => 1.3,
        'freq' => 60,
        'pf' => 0.98,
        'watt' => 100,
        'va' => 230,
        'var' => 10,
        'wh_del' => 1,
        'wh_rec' => 2,
        'wh_net' => 3,
        'wh_total' => 4,
        'varh_neg' => 5,
        'varh_pos' => 6,
        'varh_net' => 7,
        'varh_total' => 8,
        'vah_total' => 9,
        'max_rec_kw_dmd' => 1.5,
        'max_rec_kw_dmd_time' => '2026-07-01 00:00:10',
        'max_del_kw_dmd' => 2.5,
        'max_del_kw_dmd_time' => '2026-07-01 00:00:20',
        'max_pos_kvar_dmd' => 3.5,
        'max_pos_kvar_dmd_time' => '2026-07-01 00:00:30',
        'max_neg_kvar_dmd' => 4.5,
        'max_neg_kvar_dmd_time' => '2026-07-01 00:00:40',
        'v_ph_angle_a' => 10,
        'v_ph_angle_b' => 20,
        'v_ph_angle_c' => 30,
        'i_ph_angle_a' => 40,
        'i_ph_angle_b' => 50,
        'i_ph_angle_c' => 60,
        'soft_rev' => '1.2',
        'relay_status' => 0,
    ], $overrides);
}

beforeEach(function () {
    $this->site = Site::factory()->create([
        'site_code' => 'SITEA',
        'building_description' => 'RTU Protocol Site',
    ]);

    $this->location = MeterLocation::factory()->create([
        'site_idx' => $this->site->site_id,
        'location_code' => 'ER-A',
        'location_description' => 'Building A',
    ]);

    $this->gateway = Gateway::factory()->create([
        'site_idx' => $this->site->site_id,
        'location_idx' => $this->location->location_id,
        'site_code' => $this->site->site_code,
        'gateway_mac' => 'AA:BB:CC:DD:EE:01',
        'gateway_sn' => 'GW-SN-001',
        'gateway_ip' => '10.0.0.10',
        'update_rtu' => 1,
        'update_rtu_location' => 1,
        'update_rtu_ssh' => 1,
        'update_rtu_force_lp' => 1,
    ]);

    $this->configFile = ConfigurationFile::factory()->create([
        'config_file' => 'zmd402.cfg',
    ]);

    $this->meter = Meter::create([
        'site_idx' => $this->site->site_id,
        'site_code' => $this->site->site_code,
        'rtu_idx' => $this->gateway->rtu_id,
        'location_idx' => $this->location->location_id,
        'building_idx' => 0,
        'config_idx' => $this->configFile->config_id,
        'meter_name' => 'MTR-001',
        'meter_name_addressable' => 1,
        'meter_load_profile' => 'NO',
        'meter_default_name' => 'MTR-001',
        'meter_type' => 'Power',
        'meter_brand' => 'Test',
        'meter_role' => 'Client Meter',
        'meter_status' => 'Active',
        'meter_multiplier' => 1,
        'last_log_update' => '0000-00-00 00:00:00',
        'soft_rev' => '0',
        'created_by_user_idx' => 1,
        'modified_by_user_idx' => 1,
    ]);
});

test('rtu check_time returns plain timestamp payload', function () {
    $response = $this->get('/check_time.php');

    $response->assertOk();
    $response->assertHeader('Content-Type', 'text/plain; charset=UTF-8');
    expect($response->getContent())->toMatch('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/');
});

test('rtu get_update_csv returns flag for existing gateway', function () {
    $response = $this->get(rtuUrl('get_update_csv', $this->gateway->gateway_mac));

    $response->assertOk();
    $response->assertHeader('Content-Type', 'text/plain; charset=UTF-8');
    $response->assertSee('1');
});

test('rtu get_update_csv returns server error when gateway is missing', function () {
    $response = $this->get(rtuUrl('get_update_csv', 'missing-mac'));

    $response->assertStatus(500);
});

test('rtu get_content_csv returns active meter rows when update flag is on', function () {
    $response = $this->get(rtuUrl('get_content_csv', $this->gateway->gateway_mac));

    $response->assertOk();
    $response->assertHeader('Content-Type', 'text/plain; charset=UTF-8');
    $response->assertSee('MTR-001,zmd402.cfg,MTR-001');
    expect($response->getContent())->toContain('MTR-001,zmd402.cfg,MTR-001');
});

test('rtu get_content_csv returns empty payload when update flag is off', function () {
    $this->gateway->forceFill(['update_rtu' => 0])->save();

    $response = $this->get(rtuUrl('get_content_csv', $this->gateway->gateway_mac));

    $response->assertOk();
    $response->assertHeader('Content-Type', 'text/plain; charset=UTF-8');
    expect($response->getContent())->toBe('');
});

test('rtu reset_update_csv clears flag and is idempotent', function () {
    $this->gateway->update(['update_rtu' => 1]);

    $response = $this->get(rtuUrl('reset_update_csv', $this->gateway->gateway_mac));
    $response->assertOk();
    $response->assertHeader('Content-Type', 'text/plain; charset=UTF-8');
    expect($response->getContent())->toBe('');

    $this->assertDatabaseHas('meter_rtu', [
        'rtu_id' => $this->gateway->rtu_id,
        'update_rtu' => 0,
    ]);

    $response = $this->get(rtuUrl('reset_update_csv', $this->gateway->gateway_mac));
    $response->assertOk();
    expect($response->getContent())->toBe('');

    $this->assertDatabaseHas('meter_rtu', [
        'rtu_id' => $this->gateway->rtu_id,
        'update_rtu' => 0,
    ]);
});

test('rtu get_update_location returns location flag for existing gateway', function () {
    $response = $this->get(rtuUrl('get_update_location', $this->gateway->gateway_mac));

    $response->assertOk();
    $response->assertHeader('Content-Type', 'text/plain; charset=UTF-8');
    $response->assertSee('1');
});

test('rtu get_content_location returns quoted location payload when flag is on and empty when off', function () {
    $response = $this->get(rtuUrl('get_content_location', $this->gateway->gateway_mac));

    $response->assertOk();
    $response->assertHeader('Content-Type', 'text/plain; charset=UTF-8');
    expect($response->getContent())->toContain('location = "SITEA"');

    $this->gateway->forceFill(['update_rtu_location' => 0])->save();
    $response = $this->get(rtuUrl('get_content_location', $this->gateway->gateway_mac));

    $response->assertOk();
    expect($response->getContent())->toBe('');
});

test('rtu reset_update_location clears site-code flag', function () {
    $response = $this->get(rtuUrl('reset_update_location', $this->gateway->gateway_mac));

    $response->assertOk();
    $response->assertHeader('Content-Type', 'text/plain; charset=UTF-8');
    expect($response->getContent())->toBe('');
    $this->assertDatabaseHas('meter_rtu', [
        'rtu_id' => $this->gateway->rtu_id,
        'update_rtu_location' => 0,
    ]);
});

test('rtu force_load_profile returns flag and reset force_lp clears flag', function () {
    $response = $this->get(rtuUrl('force_lp', $this->gateway->gateway_mac));
    $response->assertOk();
    $response->assertHeader('Content-Type', 'text/plain; charset=UTF-8');
    $response->assertSee('1');

    $response = $this->get(rtuUrl('reset_force_lp', $this->gateway->gateway_mac));
    $response->assertOk();
    $response->assertHeader('Content-Type', 'text/plain; charset=UTF-8');
    expect($response->getContent())->toBe('');

    $this->assertDatabaseHas('meter_rtu', [
        'rtu_id' => $this->gateway->rtu_id,
        'update_rtu_force_lp' => 0,
    ]);
});

test('rtu remote ssh flag returns current value', function () {
    $response = $this->get(rtuUrl('rtu_remote_ssh', $this->gateway->gateway_mac));

    $response->assertOk();
    $response->assertHeader('Content-Type', 'text/plain; charset=UTF-8');
    $response->assertSee('1');
});

test('rtu telemetry post returns OK timestamp and inserts meter_data when save_to_meter_data is 1', function () {
    $response = $this->post('/http_post_server.php', rtuTelemetryPayload());

    $response->assertOk();
    $response->assertHeader('Content-Type', 'text/plain; charset=UTF-8');
    expect($response->getContent())->toMatch('/^OK, \d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/');

    expect((int) DB::table('meter_data')->count())->toBeGreaterThan(0);

    $payload = rtuTelemetryPayload();
    $this->assertDatabaseHas('meter_data', [
        'meter_id' => $payload['meter_id'],
        'location' => $payload['location'],
        'vrms_a' => (float) $payload['vrms_a'],
        'pf' => (float) $payload['pf'],
        'soft_rev' => (string) $payload['soft_rev'],
    ]);
});

test('rtu telemetry updates legacy last_log_update and soft_rev when save_to_meter_data is 1', function () {
    $datetime = '2026-07-01 01:02:03';
    $payload = rtuTelemetryPayload(['datetime' => $datetime]);

    $response = $this->post('/http_post_server.php', $payload);
    $response->assertOk();

    $this->assertDatabaseHas('meter_rtu', [
        'rtu_id' => $this->gateway->rtu_id,
        'site_code' => $payload['location'],
        'gateway_mac' => $payload['mac_address'],
        'last_log_update' => $datetime,
        'soft_rev' => $payload['soft_rev'],
    ]);

    $this->assertDatabaseHas('meter_details', [
        'site_code' => $payload['location'],
        'meter_name' => $payload['meter_id'],
        'last_log_update' => $datetime,
    ]);

    $this->assertDatabaseHas('meter_site', [
        'site_code' => $payload['location'],
        'last_log_update' => $datetime,
    ]);
});

test('rtu telemetry does not perform data insert or updates when save_to_meter_data is 0', function () {
    $initialMeterDataCount = DB::table('meter_data')->count();
    $initialMeterSite = DB::table('meter_site')
        ->where('site_code', 'SITEA')
        ->value('last_log_update');

    $payload = rtuTelemetryPayload([
        'save_to_meter_data' => 0,
        'datetime' => '2026-07-01 02:03:04',
        'location' => 'SITEA',
        'mac_address' => 'AA:BB:CC:DD:EE:01',
        'soft_rev' => '99.9',
    ]);

    $response = $this->post('/http_post_server.php', $payload);
    $response->assertOk();

    expect(DB::table('meter_data')->count())->toBe($initialMeterDataCount);

    $this->assertDatabaseHas('meter_rtu', [
        'rtu_id' => $this->gateway->rtu_id,
        'site_code' => $payload['location'],
        'gateway_mac' => $payload['mac_address'],
        'last_log_update' => $this->gateway->fresh()?->last_log_update,
        'soft_rev' => $this->gateway->fresh()?->soft_rev,
    ]);

    $this->assertDatabaseHas('meter_details', [
        'site_code' => $payload['location'],
        'meter_name' => $payload['meter_id'],
        'last_log_update' => $this->meter->fresh()?->last_log_update,
    ]);

    $this->assertDatabaseHas('meter_site', [
        'site_code' => $payload['location'],
        'last_log_update' => $initialMeterSite,
    ]);
});

test('rtu telemetry is public protocol endpoint and malformed payload still returns protocol acknowledgement', function () {
    $response = $this->post('/http_post_server.php', [
        'save_to_meter_data' => 1,
        'meter_id' => 'MTR-001',
    ]);

    $response->assertOk();
    $response->assertHeader('Content-Type', 'text/plain; charset=UTF-8');
    expect($response->getContent())->toStartWith('OK, ');
});

test('rtu telemetry missing gateway is characterized as acknowledged without updates', function () {
    $this->post('/http_post_server.php', rtuTelemetryPayload([
        'gateway_mac' => 'MISSING-MAC',
        'mac_address' => 'MISSING-MAC',
    ]))->assertOk();

    $this->assertDatabaseMissing('meter_rtu', [
        'gateway_mac' => 'MISSING-MAC',
    ]);

    $latest = DB::table('meter_data')
        ->where('meter_id', 'MTR-001')
        ->latest('id')
        ->first();

    expect($latest)->not->toBeNull();
});

test('rtu telemetry missing meter does not update meter_details last_log_update', function () {
    $original = $this->meter->fresh()?->last_log_update;

    $response = $this->post('/http_post_server.php', rtuTelemetryPayload([
        'meter_id' => 'MISSING-METER',
        'soft_rev' => '3.1',
    ]));
    $response->assertOk();

    $this->meter->refresh();
    expect($this->meter->last_log_update)->toBe($original);

    $this->assertDatabaseHas('meter_data', [
        'meter_id' => 'MISSING-METER',
    ]);
});
