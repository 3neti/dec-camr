<?php

use App\Models\Gateway;
use App\Models\MeterData;
use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Seeders\Profiles\AbstractProfileSeeder;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;

$seedProfileAdminPassword = AbstractProfileSeeder::DEFAULT_PASSWORD;
$legacyBaselineAdminPassword = '123456';
$scenarioAnchor = CarbonImmutable::create(2026, 7, 1, 8, 0, 0);

$fetchScenarioUser = function (string $name): User {
    $user = User::query()->where('name', $name)->first();

    expect($user)->not->toBeNull();

    return $user;
};

test('fresh-install smoke journey has bootstrap data and navigates core operator pages', function () use ($legacyBaselineAdminPassword, $fetchScenarioUser, $scenarioAnchor) {
    $this->travelTo($scenarioAnchor);

    $this->artisan('camr:scenario fresh-install-smoke')
        ->assertSuccessful()
        ->expectsOutputToContain('Scenario: fresh-install-smoke');

    $admin = $fetchScenarioUser('admin');

    $loginResponse = $this->post('/login-user', [
        'user_name' => $admin->name,
        'InputPassword' => $legacyBaselineAdminPassword,
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
    $this->travelBack();
});

test('operations-gateway-recovery smoke validates offline inspection and recovery-oriented operator context', function () use ($seedProfileAdminPassword, $fetchScenarioUser, $scenarioAnchor) {
    $this->travelTo($scenarioAnchor);

    $this->artisan(sprintf('camr:scenario operations-gateway-recovery --anchor="%s"', $scenarioAnchor->format('Y-m-d H:i:s')))
        ->assertSuccessful()
        ->expectsOutputToContain('Scenario: operations-gateway-recovery')
        ->expectsOutputToContain(sprintf('Anchor: %s', $scenarioAnchor->format('Y-m-d H:i:s')));

    $opsAdmin = $fetchScenarioUser('ops_admin_demo');

    $loginResponse = $this->post('/login-user', [
        'user_name' => $opsAdmin->name,
        'InputPassword' => $seedProfileAdminPassword,
    ]);

    $loginResponse
        ->assertRedirect('/site')
        ->assertSessionHas('loginID', $opsAdmin->id);

    $offlineCutoff = $scenarioAnchor->subMinutes(120)->toDateTimeString();
    $offlineGateway = Gateway::query()
        ->where(function ($query) use ($offlineCutoff): void {
            $query->whereNull('last_log_update')
                ->orWhere('last_log_update', '<', $offlineCutoff);
        })
        ->orderBy('gateway_sn')
        ->first();

    expect($offlineGateway)->not->toBeNull();

    $gatewayPayload = $this->withSession(['loginID' => $opsAdmin->id])
        ->postJson('/gateway_info', ['gatewayID' => $offlineGateway->rtu_id])
        ->assertOk()
        ->json();

    expect($gatewayPayload['gateway_sn'])->toBe($offlineGateway->gateway_sn);
    expect((string) $gatewayPayload['gateway_mac'])->toBe((string) $offlineGateway->gateway_mac);

    $gatewayPage = $this->withSession(['loginID' => $opsAdmin->id])
        ->get('/gateway')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Gateway')
            ->where('title', 'Gateway Management')
            ->has('gateways')
        );

    $gatewayPageGatewaySerials = collect($gatewayPage->inertiaProps('gateways'))
        ->pluck('gateway_sn')
        ->filter()
        ->values();

    expect($gatewayPageGatewaySerials)->toContain($offlineGateway->gateway_sn);

    $latestTelemetryTimestamp = MeterData::query()->max('datetime');
    $latestTelemetryMeter = MeterData::query()->orderByDesc('datetime')->value('meter_id');

    expect($latestTelemetryTimestamp)->not->toBeNull();
    expect($latestTelemetryMeter)->not->toBeNull();

    $dashboardResponse = $this->actingAs($opsAdmin)
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

    $gatewayHealth = collect($dashboardResponse->inertiaProps('gatewayHealth'));
    $telemetryTimeline = collect($dashboardResponse->inertiaProps('telemetryTimeline'));
    $operationalCommandBar = $dashboardResponse->inertiaProps('operationalCommandBar');
    $onlineGateway = Gateway::query()
        ->where('last_log_update', '>=', $scenarioAnchor->subMinutes(30)->toDateTimeString())
        ->orderBy('gateway_sn')
        ->first();

    expect($dashboardResponse->inertiaProps('gatewaySummary.offline'))->toBeGreaterThan(0);
    expect($dashboardResponse->inertiaProps('gatewaySummary.online'))->toBeGreaterThan(0);
    expect($gatewayHealth->contains(fn (array $gateway): bool => $gateway['gatewaySn'] === $offlineGateway->gateway_sn && $gateway['status'] === 'offline'))->toBeTrue();
    expect($onlineGateway)->not->toBeNull();
    expect($telemetryTimeline)->not->toBeEmpty();
    expect($operationalCommandBar)->toBeArray();
    expect($operationalCommandBar['gatewaySn'])->not->toBe('');
    expect($gatewayPageGatewaySerials)->toContain($operationalCommandBar['gatewaySn']);
    expect(collect($operationalCommandBar['commands'])->contains(fn (array $command): bool => $command['enabled'] === true))->toBeTrue();
    expect(MeterData::query()->count())->toBeGreaterThan(0);

    $this->travelBack();
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

    $start = CarbonImmutable::create(2026, 7, 1, 7, 0, 0);
    $end = CarbonImmutable::create(2026, 7, 1, 8, 0, 0);

    $reportMeter = DB::table('meter_details')
        ->join('meter_building_table', 'meter_details.building_idx', '=', 'meter_building_table.building_id')
        ->whereRaw('UPPER(meter_details.meter_status) = ?', ['ACTIVE'])
        ->whereIn('meter_details.site_idx', function ($query) use ($analyst): void {
            $query->select('site_idx')
                ->from('user_access_group')
                ->where('user_idx', (string) $analyst->id);
        })
        ->whereExists(function ($query) use ($start, $end): void {
            $query->selectRaw('1')
                ->from('meter_data')
                ->whereColumn('meter_data.meter_id', 'meter_details.meter_name')
                ->whereColumn('meter_data.location', 'meter_building_table.building_code')
                ->whereBetween('meter_data.datetime', [$start->toDateTimeString(), $end->toDateTimeString()]);
        })
        ->orderBy('meter_details.meter_id')
        ->first([
            'meter_details.meter_id',
            'meter_details.meter_name',
            'meter_details.site_idx',
            'meter_building_table.building_code',
        ]);

    expect($reportMeter)->not->toBeNull();

    $buildingCode = (string) $reportMeter->building_code;

    expect(MeterData::query()
        ->where('location', (string) $buildingCode)
        ->where('meter_id', (string) $reportMeter->meter_name)
        ->whereBetween('datetime', [$start->toDateTimeString(), $end->toDateTimeString()])
        ->count())->toBeGreaterThan(0);

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

test('energy-manager analytics smoke validates abnormal consumption investigation and report handoff', function () use ($seedProfileAdminPassword, $fetchScenarioUser, $scenarioAnchor) {
    $this->travelTo($scenarioAnchor);

    $this->artisan(sprintf('camr:scenario analytics-demo --anchor="%s"', $scenarioAnchor->format('Y-m-d H:i:s')))
        ->assertSuccessful()
        ->expectsOutputToContain('Scenario: analytics-demo')
        ->expectsOutputToContain('Simulation status: completed (scenario=analytics-demo)');

    $energyManager = $fetchScenarioUser('analyst_demo');

    $loginResponse = $this->post('/login-user', [
        'user_name' => $energyManager->name,
        'InputPassword' => $seedProfileAdminPassword,
    ]);

    $loginResponse
        ->assertRedirect('/site')
        ->assertSessionHas('loginID', $energyManager->id);

    $analyticsResponse = $this->withSession(['loginID' => $energyManager->id])
        ->get('/analytics')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Analytics')
            ->where('title', 'Analytics Workbench')
            ->where('status.label', 'Workspace Composed')
            ->where('analyticsContext.hasData', true)
            ->where('exportPanel.title', 'Evidence Export Panel')
            ->where('exportPanel.actions.0.reportFamily', 'consumption')
            ->where('exportPanel.actions.1.reportFamily', 'demand')
            ->where('exportPanel.actions.2.reportFamily', 'raw')
            ->where('exportPanel.actions.3.reportFamily', 'site')
        );

    $buildingSummaries = collect($analyticsResponse->inertiaProps('contractData.buildingSummaries'));
    $consumptionPoints = collect($analyticsResponse->inertiaProps('contractData.consumptionPoints'));
    $demandPoints = collect($analyticsResponse->inertiaProps('contractData.demandPoints'));
    $topBuilding = $buildingSummaries->first();
    $secondBuilding = $buildingSummaries->skip(1)->first();
    $peakDemandPoint = $demandPoints
        ->filter(fn (array $point): bool => (bool) ($point['peakMarker']['isPeak'] ?? false))
        ->first();

    expect($buildingSummaries)->not->toBeEmpty();
    expect($topBuilding)->not->toBeNull();
    expect($secondBuilding)->not->toBeNull();
    expect((bool) ($topBuilding['comparison']['isTopConsumer'] ?? false))->toBeTrue();
    expect((float) $topBuilding['totalKwh'])->toBeGreaterThan((float) $secondBuilding['totalKwh']);
    expect($consumptionPoints->contains(fn (array $point): bool => $point['confidence']['level'] === 'Calculated'))->toBeTrue();
    expect($demandPoints->contains(fn (array $point): bool => $point['confidence']['level'] === 'Calculated'))->toBeTrue();
    expect($peakDemandPoint)->not->toBeNull();
    expect($analyticsResponse->inertiaProps('contractEvidence.incompleteCount'))->toBeGreaterThan(0);
    expect($buildingSummaries->contains(fn (array $summary): bool => $summary['confidence']['level'] !== 'Calculated'))->toBeTrue();
    expect($analyticsResponse->inertiaProps('exportPanel.preservationNote'))->toBe('Analytics explains evidence. Reports remain the approved workflow for formal XLSX and workbook exports.');

    $this->withSession(['loginID' => $energyManager->id])
        ->get('/consumption_report')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Reports')
            ->where('reportType', 'consumption')
            ->where('downloadShelf.title', 'Download shelf')
        );

    $this->withSession(['loginID' => $energyManager->id])
        ->get('/demand_report')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Reports')
            ->where('reportType', 'demand')
            ->where('downloadShelf.title', 'Download shelf')
        );

    $this->travelBack();
});

test('executive analytics smoke validates concise review of trend top movers and confidence summary', function () use ($seedProfileAdminPassword, $fetchScenarioUser, $scenarioAnchor) {
    $this->travelTo($scenarioAnchor);

    $this->artisan(sprintf('camr:scenario analytics-demo --anchor="%s"', $scenarioAnchor->format('Y-m-d H:i:s')))
        ->assertSuccessful()
        ->expectsOutputToContain('Scenario: analytics-demo')
        ->expectsOutputToContain('Simulation status: completed (scenario=analytics-demo)');

    $executiveReviewer = $fetchScenarioUser('analyst_demo');

    $loginResponse = $this->post('/login-user', [
        'user_name' => $executiveReviewer->name,
        'InputPassword' => $seedProfileAdminPassword,
    ]);

    $loginResponse
        ->assertRedirect('/site')
        ->assertSessionHas('loginID', $executiveReviewer->id);

    $analyticsResponse = $this->withSession(['loginID' => $executiveReviewer->id])
        ->get('/analytics')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Analytics')
            ->where('title', 'Analytics Workbench')
            ->where('status.label', 'Workspace Composed')
            ->where('analyticsContext.hasData', true)
            ->where('exportPanel.title', 'Evidence Export Panel')
            ->where('exportPanel.actions.0.label', 'Open Consumption Report')
            ->where('exportPanel.actions.1.label', 'Open Demand Report')
        );

    $buildingSummaries = collect($analyticsResponse->inertiaProps('contractData.buildingSummaries'));
    $consumptionPoints = collect($analyticsResponse->inertiaProps('contractData.consumptionPoints'));
    $demandPoints = collect($analyticsResponse->inertiaProps('contractData.demandPoints'));
    $topBuilding = $buildingSummaries->first();
    $secondBuilding = $buildingSummaries->skip(1)->first();
    $calculatedConsumptionTotal = $consumptionPoints
        ->filter(fn (array $point): bool => $point['confidence']['level'] === 'Calculated')
        ->sum(fn (array $point): float => (float) $point['kwhTotal']);
    $peakDemand = $demandPoints
        ->filter(fn (array $point): bool => $point['confidence']['level'] === 'Calculated')
        ->max('kwDemand');

    expect($analyticsResponse->inertiaProps('contractEvidence.consumptionPointCount'))->toBeGreaterThan(0);
    expect($analyticsResponse->inertiaProps('contractEvidence.demandPointCount'))->toBeGreaterThan(0);
    expect($analyticsResponse->inertiaProps('contractEvidence.buildingSummaryCount'))->toBeGreaterThan(1);
    expect($calculatedConsumptionTotal)->toBeGreaterThan(0);
    expect($peakDemand)->toBeGreaterThan(0);
    expect($topBuilding)->not->toBeNull();
    expect($secondBuilding)->not->toBeNull();
    expect((bool) ($topBuilding['comparison']['isTopConsumer'] ?? false))->toBeTrue();
    expect((float) $topBuilding['totalKwh'])->toBeGreaterThan((float) $secondBuilding['totalKwh']);
    expect($buildingSummaries->contains(fn (array $summary): bool => $summary['confidence']['level'] !== 'Calculated'))->toBeTrue();
    expect($analyticsResponse->inertiaProps('exportPanel.preservationNote'))->toBe('Analytics explains evidence. Reports remain the approved workflow for formal XLSX and workbook exports.');

    $this->withSession(['loginID' => $executiveReviewer->id])
        ->get('/site_report')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Reports')
            ->where('reportType', 'site')
            ->where('downloadShelf.title', 'Download shelf')
        );

    $this->travelBack();
});

test('maintenance-meter-update smoke validates scoped site to gateway to meter workflow', function () use ($seedProfileAdminPassword, $fetchScenarioUser, $scenarioAnchor) {
    $this->travelTo($scenarioAnchor);

    $this->artisan(sprintf('camr:scenario maintenance-meter-update --anchor="%s"', $scenarioAnchor->format('Y-m-d H:i:s')))
        ->assertSuccessful()
        ->expectsOutputToContain('Scenario: maintenance-meter-update')
        ->expectsOutputToContain(sprintf('Anchor: %s', $scenarioAnchor->format('Y-m-d H:i:s')));

    $maintenanceUser = $fetchScenarioUser('maintenance_demo');

    $loginResponse = $this->post('/login-user', [
        'user_name' => $maintenanceUser->name,
        'InputPassword' => $seedProfileAdminPassword,
    ]);

    $loginResponse
        ->assertRedirect('/site')
        ->assertSessionHas('loginID', $maintenanceUser->id);

    $allowedSiteIds = DB::table('user_access_group')
        ->where('user_idx', (string) $maintenanceUser->id)
        ->orderBy('site_idx')
        ->pluck('site_idx')
        ->map(fn ($siteId): int => (int) $siteId)
        ->values();

    expect($allowedSiteIds)->not->toBeEmpty();

    $disallowedSiteId = Gateway::query()
        ->whereNotIn('site_idx', $allowedSiteIds->all())
        ->orderBy('site_idx')
        ->value('site_idx');

    expect($disallowedSiteId)->not->toBeNull();

    $sitePage = $this->withSession(['loginID' => $maintenanceUser->id])
        ->get('/site')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Site')
            ->where('title', 'Site Management')
            ->has('sites')
        );

    expect(collect($sitePage->inertiaProps('sites'))->pluck('site_id')->map(fn ($siteId): int => (int) $siteId)->all())
        ->toEqualCanonicalizing($allowedSiteIds->all());

    $siteListPayload = $this->withSession(['loginID' => $maintenanceUser->id])
        ->getJson('/site/list')
        ->assertOk()
        ->json();

    expect($siteListPayload['recordsTotal'])->toBe($allowedSiteIds->count());
    expect(collect($siteListPayload['data'])->pluck('site_id')->contains((int) $disallowedSiteId))->toBeFalse();

    $selectedSiteId = $allowedSiteIds->first();

    $gatewayPage = $this->withSession(['loginID' => $maintenanceUser->id])
        ->get('/gateway')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Gateway')
            ->where('title', 'Gateway Management')
            ->has('gateways')
        );

    $gatewayPayload = $this->withSession(['loginID' => $maintenanceUser->id])
        ->getJson('/getGateway?siteID='.$selectedSiteId)
        ->assertOk()
        ->json();

    $gatewaySerials = collect($gatewayPayload['data'])->pluck('gateway_sn')->filter()->values();

    expect($gatewayPayload['recordsTotal'])->toBeGreaterThan(0);
    expect($gatewaySerials)->not->toBeEmpty();

    $meterPage = $this->withSession(['loginID' => $maintenanceUser->id])
        ->get('/meter')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Meter')
            ->where('title', 'Meter Management')
            ->has('meters')
        );

    $meterPayload = $this->withSession(['loginID' => $maintenanceUser->id])
        ->getJson('/getMeter?siteID='.$selectedSiteId)
        ->assertOk()
        ->json();

    $meterNames = collect($meterPayload['data'])->pluck('meter_name')->filter()->values();

    expect($meterPayload['recordsTotal'])->toBeGreaterThan(0);
    expect($meterNames)->not->toBeEmpty();
    expect(collect($gatewayPage->inertiaProps('gateways'))->pluck('gateway_sn')->intersect($gatewaySerials)->isNotEmpty())->toBeTrue();
    expect(collect($meterPage->inertiaProps('meters'))->pluck('meter_name')->intersect($meterNames)->isNotEmpty())->toBeTrue();

    $this->travelBack();
});
