<?php

use App\Models\Building;
use App\Models\Gateway;
use App\Models\Meter;
use App\Models\MeterData;
use App\Models\Site;
use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Seeders\Profiles\AbstractProfileSeeder;
use Inertia\Testing\AssertableInertia as Assert;

$seedProfileAdminPassword = AbstractProfileSeeder::DEFAULT_PASSWORD;

$fetchScenarioUser = function (string $name): User {
    $user = User::query()->where('name', $name)->first();

    expect($user)->not->toBeNull();

    return $user;
};

test('fresh-install smoke journey has bootstrap data and navigates core operator pages', function () use ($seedProfileAdminPassword, $fetchScenarioUser) {
    $this->artisan('camr:scenario fresh-install-smoke')
        ->assertSuccessful()
        ->expectsOutputToContain('Scenario: fresh-install-smoke');

    $admin = $fetchScenarioUser('admin_phase0');

    $loginResponse = $this->post('/login-user', [
        'user_name' => $admin->name,
        'InputPassword' => $seedProfileAdminPassword,
    ]);

    $loginResponse
        ->assertRedirect('/site')
        ->assertSessionHas('loginID', $admin->id);

    $this->withSession(['loginID' => $admin->id])
        ->get('/site')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Site')
            ->where('title', 'Site Management')
            ->has('sites')
        );

    $this->withSession(['loginID' => $admin->id])
        ->get('/company')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Company')
            ->where('title', 'Company List')
            ->has('companies')
        );

    $latestTelemetryTimestamp = MeterData::query()->max('datetime');
    $latestTelemetryMeter = MeterData::query()->orderByDesc('datetime')->value('meter_id');

    expect($latestTelemetryTimestamp)->not->toBeNull();
    expect($latestTelemetryMeter)->not->toBeNull();

    $this->actingAs($admin)
        ->get(route('dashboard'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->where('context.user.id', $admin->id)
            ->where('context.user.name', (string) ($admin->user_real_name ?: $admin->name))
            ->where('gatewaySummary.total', Gateway::query()->count())
            ->where('telemetrySummary.lastReceivedAt', CarbonImmutable::parse((string) $latestTelemetryTimestamp)->toIso8601String())
            ->where('telemetrySummary.recentTelemetry.0.meterId', (string) $latestTelemetryMeter)
            ->where('telemetrySummary.recentTelemetry.0.status', 'online')
        );

    $companyPayload = $this->withSession(['loginID' => $admin->id])
        ->postJson('/company_list', [
            'draw' => 1,
            'start' => 0,
            'length' => 10,
            'search' => ['value' => 'Characterization'],
            'order' => [['column' => 0, 'dir' => 'asc']],
            'columns' => [['data' => 'company_name']],
        ])
        ->assertOk()
        ->json();

    expect($companyPayload['recordsFiltered'])->toBeGreaterThan(0);
    expect($companyPayload['data'])->toBeArray();

    $divisionPayload = $this->withSession(['loginID' => $admin->id])
        ->postJson('/division_list', ['draw' => 1, 'start' => 0, 'length' => 10])
        ->assertOk()
        ->json();

    expect($divisionPayload['recordsTotal'])->toBeGreaterThan(0);
});

test('operations-gateway-recovery smoke validates seeded recovery-aware context', function () use ($seedProfileAdminPassword, $fetchScenarioUser) {
    $this->artisan('camr:scenario operations-gateway-recovery')
        ->assertSuccessful()
        ->expectsOutputToContain('Scenario: operations-gateway-recovery');

    $opsAdmin = $fetchScenarioUser('ops_admin_demo');

    $loginResponse = $this->post('/login-user', [
        'user_name' => $opsAdmin->name,
        'InputPassword' => $seedProfileAdminPassword,
    ]);

    $loginResponse
        ->assertRedirect('/site')
        ->assertSessionHas('loginID', $opsAdmin->id);

    $siteId = Site::query()->value('site_id');

    expect($siteId)->not->toBeNull();

    $gatewayPayload = $this->withSession(['loginID' => $opsAdmin['id']])
        ->getJson(sprintf('/getGateway?siteID=%d', (int) $siteId))
        ->assertOk()
        ->json();

    expect($gatewayPayload['recordsTotal'])->toBeGreaterThan(0);
    expect($gatewayPayload['recordsFiltered'])->toBeGreaterThan(0);
    expect($gatewayPayload['data'])->toBeArray()->not->toBeEmpty();

    $this->withSession(['loginID' => $opsAdmin->id])
        ->get('/gateway')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Gateway')
            ->where('title', 'Gateway Management')
            ->has('gateways')
        );

    $latestTelemetryTimestamp = MeterData::query()->max('datetime');
    $latestTelemetryMeter = MeterData::query()->orderByDesc('datetime')->value('meter_id');
    $latestGatewaySoftRev = Gateway::query()->where('soft_rev', '2.12')->exists();

    expect($latestTelemetryTimestamp)->not->toBeNull();
    expect($latestTelemetryMeter)->not->toBeNull();

    $this->actingAs($opsAdmin)
        ->get(route('dashboard'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->where('context.user.id', $opsAdmin->id)
            ->where('context.user.name', (string) ($opsAdmin->user_real_name ?: $opsAdmin->name))
            ->where('gatewaySummary.total', Gateway::query()->count())
            ->where('telemetrySummary.lastReceivedAt', CarbonImmutable::parse((string) $latestTelemetryTimestamp)->toIso8601String())
            ->where('telemetrySummary.recentTelemetry.0.meterId', (string) $latestTelemetryMeter)
            ->where('telemetrySummary.recentTelemetry.0.status', 'online')
        );

    expect(MeterData::query()->count())->toBeGreaterThan(0);
    expect($latestGatewaySoftRev)->toBeTrue();
});

test('analyst-report-export smoke validates preview and export workflow on seeded telemetry', function () use ($seedProfileAdminPassword, $fetchScenarioUser) {
    $this->artisan('camr:scenario analyst-report-export')
        ->assertSuccessful()
        ->expectsOutputToContain('Scenario: analyst-report-export');

    $analyst = $fetchScenarioUser('analyst_demo');

    $loginResponse = $this->post('/login-user', [
        'user_name' => $analyst->name,
        'InputPassword' => $seedProfileAdminPassword,
    ]);

    $loginResponse
        ->assertRedirect('/site')
        ->assertSessionHas('loginID', $analyst->id);

    $reportMeter = Meter::query()
        ->where('meter_status', 'ACTIVE')
        ->whereIn('site_idx', function ($query) use ($analyst): void {
            $query->select('site_idx')
                ->from('user_access_group')
                ->where('user_idx', (string) $analyst->id);
        })
        ->orderBy('meter_id')
        ->first();

    expect($reportMeter)->not->toBeNull();

    $buildingCode = Building::query()
        ->where('building_id', $reportMeter->building_idx)
        ->value('building_code');

    expect($buildingCode)->not->toBeNull();

    $start = CarbonImmutable::create(2026, 7, 1, 8, 0, 0);
    $end = $start->addHour();

    MeterData::query()->insert([
        [
            'location' => (string) $buildingCode,
            'meter_id' => (string) $reportMeter->meter_name,
            'datetime' => $start->toDateTimeString(),
            'vrms_a' => 230,
            'vrms_b' => 230,
            'vrms_c' => 230,
            'irms_a' => 4,
            'irms_b' => 4,
            'irms_c' => 4,
            'freq' => 60,
            'pf' => 0.95,
            'watt' => 920,
            'va' => 980,
            'var' => 45,
            'wh_del' => 250,
            'wh_rec' => 50,
            'wh_net' => -200,
            'wh_total' => 1000,
            'created_at' => $start->toDateTimeString(),
            'updated_at' => $start->toDateTimeString(),
        ],
        [
            'location' => (string) $buildingCode,
            'meter_id' => (string) $reportMeter->meter_name,
            'datetime' => $start->addMinutes(30)->toDateTimeString(),
            'vrms_a' => 231,
            'vrms_b' => 230,
            'vrms_c' => 229,
            'irms_a' => 4,
            'irms_b' => 5,
            'irms_c' => 4,
            'freq' => 60,
            'pf' => 0.96,
            'watt' => 940,
            'va' => 995,
            'var' => 48,
            'wh_del' => 310,
            'wh_rec' => 60,
            'wh_net' => -250,
            'wh_total' => 1120,
            'created_at' => $start->addMinutes(30)->toDateTimeString(),
            'updated_at' => $start->addMinutes(30)->toDateTimeString(),
        ],
        [
            'location' => (string) $buildingCode,
            'meter_id' => (string) $reportMeter->meter_name,
            'datetime' => $end->toDateTimeString(),
            'vrms_a' => 232,
            'vrms_b' => 231,
            'vrms_c' => 230,
            'irms_a' => 5,
            'irms_b' => 5,
            'irms_c' => 4,
            'freq' => 60,
            'pf' => 0.97,
            'watt' => 965,
            'va' => 1015,
            'var' => 50,
            'wh_del' => 380,
            'wh_rec' => 75,
            'wh_net' => -305,
            'wh_total' => 1260,
            'created_at' => $end->toDateTimeString(),
            'updated_at' => $end->toDateTimeString(),
        ],
    ]);

    $this->withSession(['loginID' => $analyst->id])
        ->get('/consumption_report')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Reports')
            ->where('title', 'Consumption Report')
            ->where('reportType', 'consumption')
            ->where('previewSummary.title', 'Preview summary')
            ->where('downloadShelf.title', 'Download shelf')
        );

    $payload = $this->withSession(['loginID' => $analyst->id])
        ->postJson('/generate_consumption_report/hourly', [
            'site_id' => $reportMeter->site_idx,
            'meter_id' => (string) $reportMeter->meter_name,
            'start_date' => $start->format('Y-m-d'),
            'start_time' => $start->format('H:i'),
            'end_date' => $end->format('Y-m-d'),
            'end_time' => $end->format('H:i'),
            'draw' => 301,
        ])
        ->assertOk()
        ->assertJsonPath('draw', 301)
        ->assertJsonStructure(['draw', 'recordsTotal', 'recordsFiltered', 'data']);

    expect($payload->json('recordsFiltered'))->toBeGreaterThan(0);
    expect($payload->json('data'))->toBeArray()->not->toBeEmpty();

    $this->withSession(['loginID' => $analyst->id])
        ->get(sprintf(
            '/download_consumption_report?site_id=%d&meter_id=%s',
            (int) $reportMeter->site_idx,
            urlencode((string) $reportMeter->meter_name),
        ))
        ->assertOk()
        ->assertDownload()
        ->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
        ->assertHeaderContains('Content-Disposition', '.xlsx');
});
