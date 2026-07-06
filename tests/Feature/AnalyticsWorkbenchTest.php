<?php

use App\Actions\Analytics\BuildBuildingConsumptionSummaryAction;
use App\Actions\Analytics\BuildConsumptionSeriesAction;
use App\Actions\Analytics\BuildDemandSeriesAction;
use App\Models\User;
use Carbon\CarbonImmutable;
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
            ->where('contractEvidence.consumptionPointCount', 0)
            ->where('contractEvidence.demandPointCount', 0)
            ->where('contractEvidence.buildingSummaryCount', 0)
            ->where('emptyState.kind', 'no-data'));
});

test('analytics workbench receives real contract data from analytics demo scenario', function () {
    $user = User::factory()->create();

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
            ->where('emptyState.kind', 'missing-filter')
            ->has('contractData.consumptionPoints')
            ->has('contractData.demandPoints')
            ->has('contractData.buildingSummaries'));

    expect($response->inertiaProps('contractEvidence.consumptionPointCount'))->toBeGreaterThan(0)
        ->and($response->inertiaProps('contractEvidence.demandPointCount'))->toBeGreaterThan(0)
        ->and($response->inertiaProps('contractEvidence.calculatedConsumptionCount'))->toBeGreaterThan(0)
        ->and($response->inertiaProps('contractEvidence.calculatedDemandCount'))->toBeGreaterThan(0)
        ->and($response->inertiaProps('contractEvidence.buildingSummaryCount'))->toBeGreaterThan(0)
        ->and($response->inertiaProps('timeRangeControls.from'))->toBe(CarbonImmutable::parse($response->inertiaProps('analyticsContext.from'))->toDateString())
        ->and($response->inertiaProps('timeRangeControls.to'))->toBe(CarbonImmutable::parse($response->inertiaProps('analyticsContext.to'))->toDateString());
});

test('analytics workbench selected query window drives every contract', function () {
    $user = User::factory()->create();

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
        ->and($response->inertiaProps('contractData.consumptionPoints'))->toEqual($expectedConsumptionPoints)
        ->and($response->inertiaProps('contractData.demandPoints'))->toEqual($expectedDemandPoints)
        ->and($response->inertiaProps('contractData.buildingSummaries'))->toEqual($expectedBuildingSummaries);
});

test('analytics workbench contract data uses one selected analytical window', function () {
    $user = User::factory()->create();

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

test('analytics appears in operator shell navigation without replacing reports', function () {
    $navigation = file_get_contents(resource_path('js/config/operatorShellNavigation.ts'));

    expect($navigation)->toContain("title: 'Analytics'")
        ->and($navigation)->toContain("href: '/analytics'")
        ->and($navigation)->toContain("title: 'SAP Report'")
        ->and($navigation)->toContain("href: '/sap_report'");
});
