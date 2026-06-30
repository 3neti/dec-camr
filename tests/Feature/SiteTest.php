<?php

use App\Models\Company;
use App\Models\Division;
use App\Models\Site;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->division = Division::factory()->create(['division_code' => 'DIV001', 'division_name' => 'Characterization Division']);
    $this->company = Company::factory()->create(['company_code' => 'COMP001', 'company_name' => 'Characterization Company']);
    $this->site = Site::factory()->create([
        'site_code' => 'SITEA',
        'building_description' => 'Accessible Building',
        'division_idx' => $this->division->division_id,
        'company_idx' => $this->company->company_id,
    ]);
});

test('legacy site page requires legacy login session', function () {
    $this->get('/site')->assertRedirect('/')->assertSessionHas('fail', 'You Have to Login First');
});

test('legacy site page renders when loginID session exists', function () {
    $this->withSession(['loginID' => User::factory()->create()->id])
        ->get('/site')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Site')
            ->where('title', 'Site Management')
            ->where('sites.0.site_code', $this->site->site_code)
        );
});

test('legacy site admin list contract uses records total and action links', function () {
    $admin = User::factory()->create();

    $this->withSession(['loginID' => $admin->id])
        ->getJson('/site/list')
        ->assertOk()
        ->assertJsonPath('recordsTotal', 1)
        ->assertJsonPath('recordsFiltered', 1)
        ->assertJsonFragment(['building_code' => $this->site->site_code])
        ->assertJsonFragment(['action' => '<div align="center" class="action_table_menu_site"><a href="/site_details/'.$this->site->site_id.'" class="btn-info btn-circle btn-sm bi bi-eye-fill btn_icon_table btn_icon_table_view"></a><a href="#" data-id="'.$this->site->site_id.'" class="btn-warning btn-circle btn-sm bi bi-pencil-fill btn_icon_table btn_icon_table_edit" id="editSite"></a><a href="#" data-id="'.$this->site->site_id.'" class="btn-danger btn-circle btn-sm bi bi-trash3-fill btn_icon_table btn_icon_table_delete" id="deleteSite"></a></div>']);
});

test('legacy site scoped user list contract is read-only for actions', function () {
    $scopedUser = User::factory()->create();

    $this->withSession(['loginID' => $scopedUser->id])
        ->getJson('/site/user/list')
        ->assertOk()
        ->assertJsonPath('recordsTotal', 1)
        ->assertJsonPath('recordsFiltered', 1)
        ->assertJsonPath('data.0.action', '<div align="center" class="action_table_menu_site"><a href="/site_details/'.$this->site->site_id.'" class="btn-info btn-circle btn-sm bi bi-eye-fill btn_icon_table btn_icon_table_view"></a></div>');
});

test('legacy site creation validates required fields and persists on success', function () {
    $admin = User::factory()->create();

    $this->withSession(['loginID' => $admin->id])
        ->post('/create_site_post', [
            'building_code' => '',
            'building_description' => '',
            'division_id' => '',
            'company_id' => '',
        ])
        ->assertSessionHasErrors([
            'building_code' => 'Building Code is Required',
            'building_description' => 'Building Description is Required',
            'division_id' => 'Division is Required',
            'company_id' => 'Company is Required',
        ]);

    $this->withSession(['loginID' => $admin->id])
        ->postJson('/create_site_post', [
            'building_code' => 'SITEB',
            'building_description' => 'North Campus',
            'division_id' => $this->division->division_id,
            'company_id' => $this->company->company_id,
        ])
        ->assertOk()
        ->assertJson(['success' => 'Building Information Successfully Created!']);

    expect(Site::query()->where('site_code', 'SITEB')->exists())->toBeTrue();
});

test('legacy site creation rejects duplicate identifiers with validation', function () {
    $admin = User::factory()->create();

    $this->withSession(['loginID' => $admin->id])
        ->post('/create_site_post', [
            'building_code' => $this->site->site_code,
            'building_description' => 'Unique Site Description',
            'division_id' => $this->division->division_id,
            'company_id' => $this->company->company_id,
        ])
        ->assertSessionHasErrors(['building_code' => 'The building code has already been taken.']);

    $this->withSession(['loginID' => $admin->id])
        ->post('/create_site_post', [
            'building_code' => 'UNIQUE',
            'building_description' => $this->site->building_description,
            'division_id' => $this->division->division_id,
            'company_id' => $this->company->company_id,
        ])
        ->assertSessionHasErrors(['building_description' => 'The building description has already been taken.']);
});

test('legacy site info endpoint returns payload by legacy id key', function () {
    $admin = User::factory()->create();

    $response = $this->withSession(['loginID' => $admin->id])
        ->postJson('/site_info', ['siteID' => $this->site->site_id])
        ->assertOk()
        ->json();

    expect($response['site_code'])->toBe($this->site->site_code);
    expect($response['building_description'])->toBe($this->site->building_description);
});

test('legacy site update validates and updates', function () {
    $admin = User::factory()->create();

    $this->withSession(['loginID' => $admin->id])
        ->post('/update_site_post', [
            'SiteID' => $this->site->site_id,
            'building_code' => '',
            'building_description' => '',
            'division_id' => '',
            'company_id' => '',
        ])
        ->assertSessionHasErrors([
            'building_code' => 'Building Code is Required',
            'building_description' => 'Building Description is Required',
            'division_id' => 'Division is Required',
            'company_id' => 'Company is Required',
        ]);

    $this->withSession(['loginID' => $admin->id])
        ->postJson('/update_site_post', [
            'SiteID' => $this->site->site_id,
            'building_code' => 'SITEA-UPDATED',
            'building_description' => 'Updated Building',
            'division_id' => $this->division->division_id,
            'company_id' => $this->company->company_id,
        ])
        ->assertOk()
        ->assertJson(['success' => 'Building Information Successfully Updated!']);

    $this->site->refresh();
    expect($this->site->site_code)->toBe('SITEA-UPDATED');
    expect($this->site->building_description)->toBe('Updated Building');
});

test('legacy site delete returns deleted confirmation', function () {
    $admin = User::factory()->create();
    $target = Site::factory()->create([
        'division_idx' => $this->division->division_id,
        'company_idx' => $this->company->company_id,
    ]);

    $this->withSession(['loginID' => $admin->id])
        ->post('/delete_site_confirmed', ['siteID' => $target->site_id])
        ->assertOk();

    expect(Site::query()->find($target->site_id))->toBeNull();
});
