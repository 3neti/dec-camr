<?php

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
