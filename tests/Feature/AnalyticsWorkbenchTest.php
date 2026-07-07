<?php

use App\Actions\Analytics\BuildBuildingConsumptionSummaryAction;
use App\Actions\Analytics\BuildConsumptionSeriesAction;
use App\Actions\Analytics\BuildDemandSeriesAction;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;

test('analytics workbench requires legacy login session', function () {
    $this->get('/analytics')
        ->assertRedirect('/')
        ->assertSessionHas('fail', 'You Have to Login First');
});

test('analytics workbench shell renders through inertia when loginID exists', function () {
    $user = User::factory()->create();

    $this->withSession(['loginID' => $user->id])
        ->get('/analytics')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Analytics')
            ->where('title', 'Analytics Workbench')
            ->where('status.label', 'Workspace Composed')
            ->where('exportPanel.title', 'Evidence Export Panel')
            ->where('exportPanel.actions.0.id', 'consumption-report')
            ->where('exportPanel.actions.0.reportFamily', 'consumption')
            ->where('workbenchSections.0.id', 'consumption')
            ->where('workbenchSections.1.id', 'demand')
            ->where('workbenchSections.2.id', 'building-comparison')
            ->where('workbenchSections.3.id', 'load-profile')
            ->where('readinessChecklist.0', 'Run php artisan camr:scenario analytics-demo for deterministic showcase data.')
            ->where('analyticsContext.hasData', false)
            ->where('analyticsContext.periodLabel', 'No telemetry window available')
            ->where('timeRangeControls.from', '')
            ->where('timeRangeControls.presets', [])
            ->where('contextControls.buildingCode', '')
            ->where('contextControls.meterIdentifier', '')
            ->where('contextControls.buildingOptions', [])
            ->where('contextControls.meterOptions', [])
            ->where('queryState.url', '/analytics')
            ->where('contractEvidence.consumptionPointCount', 0)
            ->where('contractEvidence.demandPointCount', 0)
            ->where('contractEvidence.buildingSummaryCount', 0)
            ->where('emptyState.kind', 'no-data'));
});

test('analytics workbench receives real contract data from analytics demo scenario', function () {
    $user = User::factory()->create(['user_type' => 'Admin', 'user_access' => 'ALL']);

    $this->artisan('camr:scenario analytics-demo')
        ->assertSuccessful();

    $response = $this->withSession(['loginID' => $user->id])
        ->get('/analytics')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Analytics')
            ->where('status.label', 'Workspace Composed')
            ->where('exportPanel.preservationNote', 'Analytics explains evidence. Reports remain the approved workflow for formal XLSX and workbook exports.')
            ->where('analyticsContext.hasData', true)
            ->where('analyticsContext.grain', 'hourly')
            ->where('timeRangeControls.timezoneLabel', config('app.timezone'))
            ->where('timeRangeControls.presets.0.key', 'selected-day')
            ->where('timeRangeControls.presets.1.key', 'available-window')
            ->where('contextControls.buildingCode', fn (string $buildingCode): bool => $buildingCode !== '')
            ->where('contextControls.meterIdentifier', fn (string $meterIdentifier): bool => $meterIdentifier !== '')
            ->has('contextControls.buildingOptions')
            ->has('contextControls.meterOptions')
            ->where('queryState.url', fn (string $url): bool => str_starts_with($url, '/analytics?') && str_contains($url, 'building=') && str_contains($url, 'meter=') && str_contains($url, 'from=') && str_contains($url, 'to='))
            ->where('emptyState.kind', 'missing-filter')
            ->has('contractData.consumptionPoints')
            ->has('contractData.demandPoints')
            ->has('contractData.buildingSummaries'));

    expect($response->inertiaProps('contractEvidence.consumptionPointCount'))->toBeGreaterThan(0)
        ->and($response->inertiaProps('contractEvidence.demandPointCount'))->toBeGreaterThan(0)
        ->and($response->inertiaProps('contractEvidence.buildingSummaryCount'))->toBeGreaterThan(0)
        ->and($response->inertiaProps('timeRangeControls.from'))->toBe(CarbonImmutable::parse($response->inertiaProps('analyticsContext.from'))->toDateString())
        ->and($response->inertiaProps('timeRangeControls.to'))->toBe(CarbonImmutable::parse($response->inertiaProps('analyticsContext.to'))->toDateString());
});

test('analytics workbench selected building and meter query drives context contracts', function () {
    $user = User::factory()->create(['user_type' => 'Admin', 'user_access' => 'ALL']);

    $this->artisan('camr:scenario analytics-demo')
        ->assertSuccessful();

    $defaultResponse = $this->withSession(['loginID' => $user->id])
        ->get('/analytics')
        ->assertOk();

    $targetBuilding = collect($defaultResponse->inertiaProps('contextControls.buildingOptions'))
        ->first(fn (array $option): bool => $option['value'] !== $defaultResponse->inertiaProps('contextControls.buildingCode'));

    if ($targetBuilding === null) {
        $this->markTestSkipped('Analytics demo scenario has only one building context available.');
    }

    $buildingResponse = $this->withSession(['loginID' => $user->id])
        ->get('/analytics?building='.$targetBuilding['value'])
        ->assertOk();

    $targetMeter = $buildingResponse->inertiaProps('contextControls.meterOptions.0');
    $selectedDate = (string) $buildingResponse->inertiaProps('timeRangeControls.from');

    $response = $this->withSession(['loginID' => $user->id])
        ->get('/analytics?building='.$targetBuilding['value'].'&meter='.$targetMeter['value'].'&from='.$selectedDate.'&to='.$selectedDate)
        ->assertOk();

    $from = CarbonImmutable::parse($selectedDate)->startOfDay();
    $to = CarbonImmutable::parse($selectedDate)->endOfDay();
    $expectedConsumptionPoints = app(BuildConsumptionSeriesAction::class)->execute(
        meterIdentifier: (string) $targetMeter['value'],
        buildingCode: (string) $targetBuilding['value'],
        from: $from,
        to: $to,
    );
    $expectedDemandPoints = app(BuildDemandSeriesAction::class)->execute(
        meterIdentifier: (string) $targetMeter['value'],
        buildingCode: (string) $targetBuilding['value'],
        from: $from,
        to: $to,
    );

    expect($response->inertiaProps('analyticsContext.buildingCode'))->toBe($targetBuilding['value'])
        ->and($response->inertiaProps('analyticsContext.meterIdentifier'))->toBe($targetMeter['value'])
        ->and($response->inertiaProps('contextControls.buildingCode'))->toBe($targetBuilding['value'])
        ->and($response->inertiaProps('contextControls.meterIdentifier'))->toBe($targetMeter['value'])
        ->and($response->inertiaProps('queryState.building'))->toBe($targetBuilding['value'])
        ->and($response->inertiaProps('queryState.meter'))->toBe($targetMeter['value'])
        ->and($response->inertiaProps('queryState.from'))->toBe($selectedDate)
        ->and($response->inertiaProps('queryState.to'))->toBe($selectedDate)
        ->and($response->inertiaProps('contractData.consumptionPoints'))->toEqual($expectedConsumptionPoints)
        ->and($response->inertiaProps('contractData.demandPoints'))->toEqual($expectedDemandPoints);
});

test('analytics workbench exposes canonical shareable query state', function () {
    $user = User::factory()->create(['user_type' => 'Admin', 'user_access' => 'ALL']);

    $this->artisan('camr:scenario analytics-demo')
        ->assertSuccessful();

    $defaultResponse = $this->withSession(['loginID' => $user->id])
        ->get('/analytics')
        ->assertOk();

    $selectedBuilding = (string) $defaultResponse->inertiaProps('contextControls.buildingCode');
    $selectedMeter = (string) $defaultResponse->inertiaProps('contextControls.meterIdentifier');
    $selectedDate = (string) $defaultResponse->inertiaProps('timeRangeControls.from');

    $response = $this->withSession(['loginID' => $user->id])
        ->get('/analytics?meter='.$selectedMeter.'&to='.$selectedDate.'&building='.$selectedBuilding.'&from='.$selectedDate)
        ->assertOk();

    expect($response->inertiaProps('queryState'))->toMatchArray([
        'building' => $selectedBuilding,
        'meter' => $selectedMeter,
        'from' => $selectedDate,
        'to' => $selectedDate,
        'url' => '/analytics?building='.urlencode($selectedBuilding).'&meter='.urlencode($selectedMeter).'&from='.$selectedDate.'&to='.$selectedDate,
    ]);
});

test('analytics workbench selected query window drives every contract', function () {
    $user = User::factory()->create(['user_type' => 'Admin', 'user_access' => 'ALL']);

    $this->artisan('camr:scenario analytics-demo')
        ->assertSuccessful();

    $defaultResponse = $this->withSession(['loginID' => $user->id])
        ->get('/analytics')
        ->assertOk();
    $selectedDate = (string) $defaultResponse->inertiaProps('timeRangeControls.from');

    $response = $this->withSession(['loginID' => $user->id])
        ->get('/analytics?from='.$selectedDate.'&to='.$selectedDate)
        ->assertOk();

    $from = CarbonImmutable::parse($selectedDate)->startOfDay();
    $to = CarbonImmutable::parse($selectedDate)->endOfDay();
    $meterIdentifier = (string) $response->inertiaProps('analyticsContext.meterIdentifier');
    $buildingCode = (string) $response->inertiaProps('analyticsContext.buildingCode');

    $expectedConsumptionPoints = app(BuildConsumptionSeriesAction::class)->execute(
        meterIdentifier: $meterIdentifier,
        buildingCode: $buildingCode,
        from: $from,
        to: $to,
    );
    $expectedDemandPoints = app(BuildDemandSeriesAction::class)->execute(
        meterIdentifier: $meterIdentifier,
        buildingCode: $buildingCode,
        from: $from,
        to: $to,
    );
    $expectedBuildingSummaries = app(BuildBuildingConsumptionSummaryAction::class)->execute(
        from: $from,
        to: $to,
    );

    expect(CarbonImmutable::parse($response->inertiaProps('analyticsContext.from'))->toDateTimeString())->toBe($from->toDateTimeString())
        ->and(CarbonImmutable::parse($response->inertiaProps('analyticsContext.to'))->toDateTimeString())->toBe($to->toDateTimeString())
        ->and($response->inertiaProps('timeRangeControls.from'))->toBe($selectedDate)
        ->and($response->inertiaProps('timeRangeControls.to'))->toBe($selectedDate)
        ->and($response->inertiaProps('queryState.from'))->toBe($selectedDate)
        ->and($response->inertiaProps('queryState.to'))->toBe($selectedDate)
        ->and($response->inertiaProps('contractData.consumptionPoints'))->toEqual($expectedConsumptionPoints)
        ->and($response->inertiaProps('contractData.demandPoints'))->toEqual($expectedDemandPoints)
        ->and($response->inertiaProps('contractData.buildingSummaries'))->toEqual($expectedBuildingSummaries);
});

test('analytics workbench contract data uses one selected analytical window', function () {
    $user = User::factory()->create(['user_type' => 'Admin', 'user_access' => 'ALL']);

    $this->artisan('camr:scenario analytics-demo')
        ->assertSuccessful();

    $response = $this->withSession(['loginID' => $user->id])
        ->get('/analytics')
        ->assertOk();

    $from = CarbonImmutable::parse($response->inertiaProps('analyticsContext.from'));
    $to = CarbonImmutable::parse($response->inertiaProps('analyticsContext.to'));
    $meterIdentifier = (string) $response->inertiaProps('analyticsContext.meterIdentifier');
    $buildingCode = (string) $response->inertiaProps('analyticsContext.buildingCode');

    $expectedConsumptionPoints = app(BuildConsumptionSeriesAction::class)->execute(
        meterIdentifier: $meterIdentifier,
        buildingCode: $buildingCode,
        from: $from,
        to: $to,
    );
    $expectedDemandPoints = app(BuildDemandSeriesAction::class)->execute(
        meterIdentifier: $meterIdentifier,
        buildingCode: $buildingCode,
        from: $from,
        to: $to,
    );
    $expectedBuildingSummaries = app(BuildBuildingConsumptionSummaryAction::class)->execute(
        from: $from,
        to: $to,
    );

    expect($response->inertiaProps('contractData.consumptionPoints'))->toEqual($expectedConsumptionPoints)
        ->and($response->inertiaProps('contractData.demandPoints'))->toEqual($expectedDemandPoints)
        ->and($response->inertiaProps('contractData.buildingSummaries'))->toEqual($expectedBuildingSummaries);
});

test('analytics workbench honors scoped user site access for context options and summaries', function () {
    $scopedUser = User::factory()->create([
        'name' => 'analytics-scoped',
        'user_type' => 'User',
        'user_access' => 'Selected',
    ]);
    $admin = User::factory()->create(['user_type' => 'Admin', 'user_access' => 'ALL']);

    $this->artisan('camr:scenario analytics-demo')
        ->assertSuccessful();

    $adminResponse = $this->withSession(['loginID' => $admin->id])
        ->get('/analytics')
        ->assertOk();

    $summaries = collect($adminResponse->inertiaProps('contractData.buildingSummaries'))
        ->filter(fn (array $summary): bool => isset($summary['site']['siteId']))
        ->values();

    if ($summaries->count() < 2) {
        $this->markTestSkipped('Analytics demo scenario needs at least two site-backed building summaries for scoped access coverage.');
    }

    $allowedSummary = $summaries->first();
    $blockedSummary = $summaries->first(fn (array $summary): bool => (int) $summary['site']['siteId'] !== (int) $allowedSummary['site']['siteId']);

    if ($blockedSummary === null) {
        $this->markTestSkipped('Analytics demo scenario needs building summaries across multiple sites for scoped access coverage.');
    }

    DB::table('user_access_group')->insert([
        'user_idx' => (string) $scopedUser->id,
        'user_name' => $scopedUser->name,
        'site_idx' => (int) $allowedSummary['site']['siteId'],
        'created_by_user_idx' => $admin->id,
        'access_list_src' => 'CAMR',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $response = $this->withSession(['loginID' => $scopedUser->id])
        ->get('/analytics?building='.$blockedSummary['buildingCode'])
        ->assertOk();

    expect($response->inertiaProps('analyticsContext.hasData'))->toBeTrue()
        ->and($response->inertiaProps('analyticsContext.buildingCode'))->not->toBe($blockedSummary['buildingCode'])
        ->and(collect($response->inertiaProps('contextControls.buildingOptions'))
            ->contains(fn (array $option): bool => $option['value'] === $blockedSummary['buildingCode']))->toBeFalse()
        ->and(collect($response->inertiaProps('contractData.buildingSummaries'))
            ->every(fn (array $summary): bool => (int) ($summary['site']['siteId'] ?? 0) === (int) $allowedSummary['site']['siteId']))->toBeTrue();
});

test('analytics appears in operator shell navigation without replacing reports', function () {
    $navigation = file_get_contents(resource_path('js/config/operatorShellNavigation.ts'));

    expect($navigation)->toContain("title: 'Analytics'")
        ->and($navigation)->toContain("href: '/analytics'")
        ->and($navigation)->toContain("title: 'SAP Report'")
        ->and($navigation)->toContain("href: '/sap_report'");
});
