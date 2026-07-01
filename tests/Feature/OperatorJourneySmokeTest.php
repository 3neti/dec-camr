<?php

use App\Models\Gateway;
use App\Models\MeterData;
use App\Models\Site;
use App\Models\User;
use Database\Seeders\Profiles\AbstractProfileSeeder;
use Inertia\Testing\AssertableInertia as Assert;

$seedProfileAdminPassword = AbstractProfileSeeder::DEFAULT_PASSWORD;

$fetchScenarioUser = function (string $name): array {
    $user = User::query()->where('name', $name)->first();

    expect($user)->not->toBeNull();

    return [
        'id' => (int) $user->id,
        'name' => $user->name,
    ];
};

test('fresh-install smoke journey has bootstrap data and navigates core operator pages', function () use ($seedProfileAdminPassword, $fetchScenarioUser) {
    $this->artisan('camr:scenario fresh-install-smoke')
        ->assertSuccessful()
        ->expectsOutputToContain('Scenario: fresh-install-smoke');

    $admin = $fetchScenarioUser('admin_phase0');

    $loginResponse = $this->post('/login-user', [
        'user_name' => $admin['name'],
        'InputPassword' => $seedProfileAdminPassword,
    ]);

    $loginResponse
        ->assertRedirect('/site')
        ->assertSessionHas('loginID', $admin['id']);

    $this->withSession(['loginID' => $admin['id']])
        ->get('/site')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Site')
            ->where('title', 'Site Management')
            ->has('sites')
        );

    $this->withSession(['loginID' => $admin['id']])
        ->get('/company')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Company')
            ->where('title', 'Company List')
            ->has('companies')
        );

    $companyPayload = $this->withSession(['loginID' => $admin['id']])
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

    $divisionPayload = $this->withSession(['loginID' => $admin['id']])
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
        'user_name' => $opsAdmin['name'],
        'InputPassword' => $seedProfileAdminPassword,
    ]);

    $loginResponse
        ->assertRedirect('/site')
        ->assertSessionHas('loginID', $opsAdmin['id']);

    $siteId = Site::query()->value('site_id');

    expect($siteId)->not->toBeNull();

    $gatewayPayload = $this->withSession(['loginID' => $opsAdmin['id']])
        ->getJson(sprintf('/getGateway?siteID=%d', (int) $siteId))
        ->assertOk()
        ->json();

    expect($gatewayPayload['recordsTotal'])->toBeGreaterThan(0);
    expect($gatewayPayload['recordsFiltered'])->toBeGreaterThan(0);
    expect($gatewayPayload['data'])->toBeArray()->not->toBeEmpty();

    $this->withSession(['loginID' => $opsAdmin['id']])
        ->get('/gateway')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Gateway')
            ->where('title', 'Gateway Management')
            ->has('gateways')
        );

    expect(MeterData::query()->count())->toBeGreaterThan(0);
    expect(Gateway::query()->where('soft_rev', '2.12')->exists())->toBeTrue();
});
