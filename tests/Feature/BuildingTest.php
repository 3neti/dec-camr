<?php

use App\Models\Building;
use App\Models\Meter;
use App\Models\Site;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->site = Site::factory()->create([
        'site_code' => 'SITEA',
        'building_description' => 'Accessible Building',
    ]);
    $this->building = Building::factory()->create([
        'site_idx' => $this->site->site_id,
        'building_code' => 'SITEA',
        'building_description' => 'Accessible Building',
    ]);
});

test('legacy building page requires legacy login session', function () {
    $this->get('/building')->assertRedirect('/')->assertSessionHas('fail', 'You Have to Login First');
});

test('legacy building page renders through inertia when loginID exists', function () {
    $this->withSession(['loginID' => User::factory()->create()->id])
        ->get('/building')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Building')
            ->where('title', 'Building List')
            ->where('buildings.0.building_code', $this->building->building_code)
        );
});

test('legacy building list contract can be filtered by site and includes action links', function () {
    $admin = User::factory()->create();

    $this->withSession(['loginID' => $admin->id])
        ->getJson('/getBuilding?siteID='.$this->site->site_id)
        ->assertOk()
        ->assertJsonPath('recordsTotal', 1)
        ->assertJsonPath('recordsFiltered', 1)
        ->assertJsonPath('data.0.building_code', $this->building->building_code)
        ->assertJsonFragment(['action' => '<a href="#" data-id="'.$this->building->building_id.'" class="bi bi-pencil-fill btn_icon_table btn_icon_table_edit" id="editBuilding" title="Update Building Information"></a> <a href="#" data-id="'.$this->building->building_id.'" class="bi bi-trash3-fill btn_icon_table btn_icon_table_delete" id="deleteBuilding" title="Delete Building Information"></a>']);
});

test('legacy building list supports datatables search, pagination, and draw', function () {
    Building::factory()->create([
        'site_idx' => $this->site->site_id,
        'building_code' => 'ZZZ-BLDG',
        'building_description' => 'Secondary',
    ]);

    $admin = User::factory()->create();

    $this->withSession(['loginID' => $admin->id])
        ->json('GET', '/getBuilding', [
            'siteID' => $this->site->site_id,
            'draw' => 15,
            'start' => 0,
            'length' => 1,
            'search' => ['value' => 'SITEA'],
            'order' => [['column' => 0, 'dir' => 'asc']],
            'columns' => [
                ['data' => 'building_code'],
                ['data' => 'building_description'],
            ],
        ])
        ->assertOk()
        ->assertJsonPath('draw', 15)
        ->assertJsonPath('recordsTotal', 2)
        ->assertJsonPath('recordsFiltered', 2)
        ->assertJsonCount(1, 'data');
});

test('legacy building create validates required fields and persists on success', function () {
    $admin = User::factory()->create();

    $this->withSession(['loginID' => $admin->id])
        ->post('/create_building_post', [
            'siteID' => '',
            'building_code' => '',
            'building_description' => '',
        ])->assertSessionHasErrors([
            'siteID' => 'Site is Required',
            'building_code' => 'Building Code is Required',
            'building_description' => 'Building Description is Required',
        ]);

    $this->withSession(['loginID' => $admin->id])
        ->postJson('/create_building_post', [
            'siteID' => $this->site->site_id,
            'building_code' => 'SITEB',
            'building_description' => 'North Campus',
        ])
        ->assertOk()
        ->assertJson(['success' => 'Building Information Successfully Created!']);

    expect(Building::query()->where('site_idx', $this->site->site_id)
        ->where('building_code', 'SITEB')->exists())->toBeTrue();
});

test('legacy building create rejects duplicates per site and updates remain scoped', function () {
    $admin = User::factory()->create();
    $otherSite = Site::factory()->create([
        'site_code' => 'SITEB',
        'building_description' => 'Other Site',
    ]);

    $this->withSession(['loginID' => $admin->id])
        ->post('/create_building_post', [
            'siteID' => $this->site->site_id,
            'building_code' => $this->building->building_code,
            'building_description' => 'Duplicate Description',
        ])
        ->assertSessionHasErrors(['building_code' => 'The building code has already been taken.']);

    $this->withSession(['loginID' => $admin->id])
        ->post('/create_building_post', [
            'siteID' => $otherSite->site_id,
            'building_code' => $this->building->building_code,
            'building_description' => 'Different Site',
        ])
        ->assertOk()
        ->assertJson(['success' => 'Building Information Successfully Created!']);
});

test('legacy building info endpoint returns payload by building id', function () {
    $admin = User::factory()->create();

    $response = $this->withSession(['loginID' => $admin->id])
        ->postJson('/building_info', ['buildingID' => $this->building->building_id])
        ->assertOk()
        ->json();

    expect($response['building_code'])->toBe($this->building->building_code);
    expect($response['building_description'])->toBe($this->building->building_description);
});

test('legacy building update validates required fields and updates', function () {
    $admin = User::factory()->create();

    $this->withSession(['loginID' => $admin->id])
        ->post('/update_building_post', [
            'buildingID' => $this->building->building_id,
            'siteID' => $this->site->site_id,
            'building_code' => '',
            'building_description' => '',
        ])
        ->assertSessionHasErrors([
            'building_code' => 'Building Code is Required',
            'building_description' => 'Building Description is Required',
        ]);

    $this->withSession(['loginID' => $admin->id])
        ->postJson('/update_building_post', [
            'buildingID' => $this->building->building_id,
            'siteID' => $this->site->site_id,
            'building_code' => 'SITEA-UPDATED',
            'building_description' => 'Updated Building',
        ])
        ->assertOk()
        ->assertJson(['success' => 'Building Information Successfully Updated!']);

    $this->building->refresh();
    expect($this->building->building_code)->toBe('SITEA-UPDATED');
    expect($this->building->building_description)->toBe('Updated Building');
});

test('legacy building delete returns deleted confirmation', function () {
    $admin = User::factory()->create();
    $target = Building::factory()->create([
        'site_idx' => $this->site->site_id,
        'building_code' => 'SITE-B',
    ]);

    $this->withSession(['loginID' => $admin->id])
        ->post('/delete_building_confirmed', ['buildingID' => $target->building_id])
        ->assertOk();

    expect(Building::query()->find($target->building_id))->toBeNull();
});

test('legacy building delete is blocked while dependent meters exist', function () {
    $admin = User::factory()->create();
    $target = Building::factory()->create([
        'site_idx' => $this->site->site_id,
        'building_code' => 'BLDG-DEL',
        'building_description' => 'Blocking Building',
    ]);

    Meter::factory()->create([
        'building_idx' => $target->building_id,
        'site_idx' => $this->site->site_id,
    ]);

    $this->withSession(['loginID' => $admin->id])
        ->post('/delete_building_confirmed', ['buildingID' => $target->building_id])
        ->assertStatus(500)
        ->assertJson(['error' => 'Delete Failed!']);

    expect(Building::query()->find($target->building_id))->not->toBeNull();
});
