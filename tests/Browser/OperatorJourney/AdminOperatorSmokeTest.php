<?php

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;

$seedAnalyticsDemoScenario = function (object $testCase): User {
    $testCase->travelTo(CarbonImmutable::create(2026, 7, 1, 8, 0, 0));

    $testCase->artisan('camr:scenario analytics-demo')
        ->assertSuccessful();

    $admin = User::query()
        ->where('name', 'admin')
        ->first();

    expect($admin)->not->toBeNull();
    expect(Hash::check('123456', (string) $admin?->password))->toBeTrue();

    return $admin;
};

$authenticateLegacyBrowserUser = function (object $testCase, User $user): void {
    $testCase
        ->actingAs($user)
        ->withSession(['loginID' => $user->id]);
};

test('admin login page exposes the explicit test credential and accepts the legacy login payload in a real browser', function () use ($seedAnalyticsDemoScenario) {
    config()->set('session.driver', 'file');

    $seedAnalyticsDemoScenario($this);

    $page = visit('/');

    $page
        ->assertSee('Centralized Automated Meter Reading')
        ->assertSee('Test login')
        ->assertSee('admin')
        ->assertSee('123456')
        ->assertNoJavaScriptErrors();

    $page->script(<<<'JS'
        function() {
            const setInputValue = (selector, value) => {
                const input = document.querySelector(selector);
                input.value = value;
                input.dispatchEvent(new Event('input', { bubbles: true }));
                input.dispatchEvent(new Event('change', { bubbles: true }));
            };

            setInputValue('#user_name', 'admin');
            setInputValue('#InputPassword', '123456');
        }
        JS);

    $page
        ->assertScript("document.querySelector('#user_name').value", 'admin')
        ->assertScript("document.querySelector('#InputPassword').value", '123456');

    $loginResult = $page->script(<<<'JS'
        async function() {
            const form = document.querySelector('[data-test="legacy-login-form"]');
            const body = new URLSearchParams();
            body.set('_token', form.querySelector('[name="_token"]').value);
            body.set('user_name', form.querySelector('[name="user_name"]').value);
            body.set('InputPassword', form.querySelector('[name="InputPassword"]').value);

            const response = await fetch(form.action, {
                method: 'POST',
                body,
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
                },
                credentials: 'same-origin',
            });

            return {
                status: response.status,
                url: response.url,
                redirected: response.redirected,
            };
        }
        JS);

    expect($loginResult)->toMatchArray([
        'status' => 200,
        'redirected' => true,
    ]);
    expect((string) $loginResult['url'])->toEndWith('/site');

    $this->travelBack();
});

test('authenticated admin can open the operator home in a real browser', function () use ($seedAnalyticsDemoScenario) {
    config()->set('session.driver', 'file');

    $admin = $seedAnalyticsDemoScenario($this);

    Route::get('/__browser-test/login/{user}', function (User $user, Request $request) {
        Auth::guard()->login($user);
        $request->session()->put('loginID', $user->id);

        return redirect('/site');
    })->middleware('web');

    $page = visit(sprintf('/__browser-test/login/%d', $admin->id));

    $page
        ->assertPathIs('/site')
        ->assertSee('Site Management')
        ->assertNoJavaScriptErrors();

    $this->travelBack();
});

test('authenticated admin can open dashboard in a real browser', function () use ($seedAnalyticsDemoScenario) {
    config()->set('session.driver', 'file');

    $admin = $seedAnalyticsDemoScenario($this);

    $this
        ->actingAs($admin)
        ->withSession(['loginID' => $admin->id]);

    visit('/dashboard')
        ->assertPathIs('/dashboard')
        ->assertSee('CAMR Operator Console')
        ->assertSee('Gateway Health')
        ->assertSee('Recent Telemetry')
        ->assertNoJavaScriptErrors();

    $this->travelBack();
});

test('authenticated admin can open analytics in a real browser', function () use ($seedAnalyticsDemoScenario) {
    config()->set('session.driver', 'file');

    $admin = $seedAnalyticsDemoScenario($this);

    $this
        ->actingAs($admin)
        ->withSession(['loginID' => $admin->id]);

    visit('/analytics')
        ->assertPathIs('/analytics')
        ->assertSee('Analytics Workbench')
        ->assertSee('Historical Analysis')
        ->assertSee('Consumption Trend')
        ->assertNoJavaScriptErrors();

    $this->travelBack();
});

test('analyst can review analytics evidence controls in a real browser', function () use ($seedAnalyticsDemoScenario) {
    config()->set('session.driver', 'file');

    $seedAnalyticsDemoScenario($this);

    $analyst = User::query()
        ->where('name', 'analyst_demo')
        ->first();

    expect($analyst)->not->toBeNull();

    $this
        ->actingAs($analyst)
        ->withSession(['loginID' => $analyst->id]);

    visit('/analytics')
        ->assertPathIs('/analytics')
        ->assertSee('Analytics Workbench')
        ->assertSee('Historical Analysis')
        ->assertSee('Portfolio')
        ->assertSee('Selected building')
        ->assertSee('Building')
        ->assertSee('Meter')
        ->assertSee('Investigation Window')
        ->assertSee('Start date')
        ->assertSee('End date')
        ->assertSee('Selected Window Consumption')
        ->assertSee('Peak Demand')
        ->assertSee('Building Leader')
        ->assertSee('Consumption Trend')
        ->assertSee('Demand Curve')
        ->assertSee('Building Comparison')
        ->assertSee('Load Profile Explorer')
        ->assertSee('Evidence Export Panel')
        ->assertNoJavaScriptErrors();

    $this->travelBack();
});

test('admin can review live scada replay signals on the dashboard in a real browser', function () {
    config()->set('session.driver', 'file');

    $this->travelTo(CarbonImmutable::create(2026, 7, 1, 8, 0, 0));

    $this->artisan('camr:scenario live-scada-demo')
        ->assertSuccessful();

    $admin = User::query()
        ->where('name', 'admin')
        ->first();

    expect($admin)->not->toBeNull();

    $this
        ->actingAs($admin)
        ->withSession(['loginID' => $admin->id]);

    visit('/dashboard')
        ->assertPathIs('/dashboard')
        ->assertSee('CAMR Operator Console')
        ->assertSee('Telemetry freshness')
        ->assertSee('Pending operator work')
        ->assertSee('Gateways')
        ->assertSee('Meters')
        ->assertSee('Telemetry')
        ->assertSee('Pending Updates')
        ->assertSee('Operations Snapshot')
        ->assertSee('Latest telemetry')
        ->assertSee('Recent Telemetry')
        ->assertSee('Gateway Health')
        ->assertSee('Meter Health')
        ->assertSee('Timeline')
        ->assertSee('Operational Command Bar')
        ->assertSee('Pending Update Panel')
        ->assertSee('Report Readiness')
        ->assertNoJavaScriptErrors();

    $this->travelBack();
});

test('operations engineer can review gateway recovery signals in a real browser', function () {
    config()->set('session.driver', 'file');

    $this->travelTo(CarbonImmutable::create(2026, 7, 1, 8, 0, 0));

    $this->artisan('camr:scenario operations-gateway-recovery --anchor="2026-07-01 08:00:00"')
        ->assertSuccessful();

    $opsAdmin = User::query()
        ->where('name', 'ops_admin_demo')
        ->first();

    expect($opsAdmin)->not->toBeNull();

    $this
        ->actingAs($opsAdmin)
        ->withSession(['loginID' => $opsAdmin->id]);

    visit('/dashboard')
        ->assertPathIs('/dashboard')
        ->assertSee('CAMR Operator Console')
        ->assertSee('Needs Attention')
        ->assertSee('gateway')
        ->assertSee('offline')
        ->assertSee('stale')
        ->assertSee('Open gateway')
        ->assertSee('Gateway Health')
        ->assertSee('Meter Health')
        ->assertSee('Timeline')
        ->assertSee('stale/offline transitions')
        ->assertSee('Operational Command Bar')
        ->assertSee('Pending Update Panel')
        ->assertNoJavaScriptErrors();

    $this->travelBack();
});

test('operations engineer can review live operations command surface in a real browser', function () {
    config()->set('session.driver', 'file');

    $this->travelTo(CarbonImmutable::create(2026, 7, 1, 8, 0, 0));

    $this->artisan('camr:scenario operations-gateway-recovery --anchor="2026-07-01 08:00:00"')
        ->assertSuccessful();

    $opsAdmin = User::query()
        ->where('name', 'ops_admin_demo')
        ->first();

    expect($opsAdmin)->not->toBeNull();

    $this
        ->actingAs($opsAdmin)
        ->withSession(['loginID' => $opsAdmin->id]);

    visit('/dashboard')
        ->assertPathIs('/dashboard')
        ->assertSee('Operational Command Bar')
        ->assertSee('Approved RTU-safe commands')
        ->assertSee('Download CSV payload')
        ->assertSee('Reset CSV update flag')
        ->assertSee('Download location payload')
        ->assertSee('Reset location update flag')
        ->assertSee('Check force LP flag')
        ->assertSee('Reset force LP flag')
        ->assertSee('Check remote SSH flag')
        ->assertSee('Open command')
        ->assertNoJavaScriptErrors();

    $this->travelBack();
});

test('admin can review maintenance crud surface in a real browser', function (string $path, array $visibleText) use ($authenticateLegacyBrowserUser, $seedAnalyticsDemoScenario) {
    config()->set('session.driver', 'file');

    $admin = $seedAnalyticsDemoScenario($this);

    $authenticateLegacyBrowserUser($this, $admin);

    $page = visit($path)
        ->assertPathIs($path);

    foreach ($visibleText as $text) {
        $page->assertSee($text);
    }

    $page->assertNoJavaScriptErrors();

    $this->travelBack();
})->with([
    'company maintenance' => ['/company', ['Company List', 'Create company', 'Existing companies', 'Actions']],
    'division maintenance' => ['/division', ['Division List', 'Create division', 'Existing divisions', 'Actions']],
    'site maintenance' => ['/site', ['Site Management', 'Create site', 'Existing sites', 'Actions']],
    'building maintenance' => ['/building', ['Building List', 'Create building', 'Existing buildings', 'Actions']],
    'gateway maintenance' => ['/gateway', ['Gateway Management', 'Create gateway', 'Existing gateways', 'Actions']],
    'meter maintenance' => ['/meter', ['Meter Management', 'Create meter', 'Existing meters', 'Actions']],
]);

test('admin can review core operator surface on a mobile viewport in a real browser', function (string $path, array $visibleText) use ($authenticateLegacyBrowserUser, $seedAnalyticsDemoScenario) {
    config()->set('session.driver', 'file');

    $admin = $seedAnalyticsDemoScenario($this);

    $authenticateLegacyBrowserUser($this, $admin);

    $page = visit($path)
        ->on()->mobile()
        ->assertPathIs($path);

    foreach ($visibleText as $text) {
        $page->assertSee($text);
    }

    $page->assertNoJavaScriptErrors();

    $this->travelBack();
})->with([
    'operator home mobile' => ['/site', ['Site Management', 'Create site']],
    'dashboard mobile' => ['/dashboard', ['CAMR Operator Console', 'Gateway Health', 'Recent Telemetry']],
    'analytics mobile' => ['/analytics', ['Analytics Workbench', 'Consumption Trend', 'Demand Curve']],
]);

test('admin can review report workflow surface in a real browser', function (string $path, array $visibleText) use ($authenticateLegacyBrowserUser, $seedAnalyticsDemoScenario) {
    config()->set('session.driver', 'file');

    $admin = $seedAnalyticsDemoScenario($this);

    $authenticateLegacyBrowserUser($this, $admin);

    $page = visit($path)
        ->assertPathIs($path);

    foreach ($visibleText as $text) {
        $page->assertSee($text);
    }

    $page->assertNoJavaScriptErrors();

    $this->travelBack();
})->with([
    'sap report' => ['/sap_report', ['SAP Report', 'Report filters', 'Preview summary', 'Download shelf']],
    'raw report' => ['/raw_report', ['Raw Report', 'Report filters', 'Preview summary', 'Download shelf']],
    'site report' => ['/site_report', ['Site Report', 'Report filters', 'Preview summary', 'Download shelf']],
    'consumption report' => ['/consumption_report', ['Consumption Report', 'Report filters', 'Preview summary', 'Download shelf']],
    'demand report' => ['/demand_report', ['Demand Report', 'Report filters', 'Preview summary', 'Download shelf']],
]);

test('analyst can prepare a consumption export in a real browser', function () use ($authenticateLegacyBrowserUser, $seedAnalyticsDemoScenario) {
    config()->set('session.driver', 'file');

    $seedAnalyticsDemoScenario($this);

    $analyst = User::query()
        ->where('name', 'analyst_demo')
        ->first();

    expect($analyst)->not->toBeNull();

    $reportMeter = DB::table('meter_details')
        ->join('meter_data', 'meter_data.meter_id', '=', 'meter_details.meter_name')
        ->whereNotNull('meter_details.site_idx')
        ->orderBy('meter_details.meter_id')
        ->select('meter_details.site_idx', 'meter_details.meter_name')
        ->first();

    expect($reportMeter)->not->toBeNull();

    $authenticateLegacyBrowserUser($this, $analyst);

    $page = visit('/consumption_report')
        ->assertPathIs('/consumption_report')
        ->assertSee('Consumption Report')
        ->assertSee('Report filters')
        ->assertSee('Download Consumption Export')
        ->assertSee('Download shelf')
        ->assertSee('No downloads in this session yet')
        ->assertNoJavaScriptErrors();

    $page
        ->fill('#site_id', (string) $reportMeter->site_idx)
        ->fill('#meter_id', (string) $reportMeter->meter_name)
        ->fill('#start_date', '2026-07-01')
        ->fill('#start_time', '00:00')
        ->fill('#end_date', '2026-07-01')
        ->fill('#end_time', '23:59')
        ->wait(0.5)
        ->assertScript("document.querySelector('#site_id').value", (string) $reportMeter->site_idx)
        ->assertScript("document.querySelector('#meter_id').value", (string) $reportMeter->meter_name)
        ->assertSee('Ready')
        ->assertSee('Download shelf')
        ->assertSee('KWh Consumption')
        ->assertNoJavaScriptErrors();

    $this->travelBack();
});
