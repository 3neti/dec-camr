<?php

declare(strict_types=1);

use App\Models\MeterLocation;
use App\Models\Site;
use App\Models\User;

beforeEach(function () {
    $this->site = Site::factory()->create([
        'site_code' => 'SITEA',
        'building_description' => 'Location Test Building',
    ]);
    $this->location = MeterLocation::factory()->create([
        'site_idx' => $this->site->site_id,
        'location_code' => 'ER-A',
        'location_description' => 'Location A',
    ]);
});

test('legacy meter location list contract requires legacy login session', function () {
    $this->post('/getMeterLocation', ['siteID' => $this->site->site_id])->assertRedirect('/');
});

test('legacy meter location list contract returns datatable payload and action anchors', function () {
    $admin = User::factory()->create();

    $this->withSession(['loginID' => $admin->id])
        ->postJson('/getMeterLocation', ['siteID' => $this->site->site_id])
        ->assertOk()
        ->assertJsonPath('recordsTotal', 1)
        ->assertJsonPath('recordsFiltered', 1)
        ->assertJsonFragment([
            'location_code' => 'ER-A',
            'location_description' => 'Location A',
            'action' => '<a href="#" title="Click to Edit" data-id="'.$this->location->location_id.'" style="cursor: pointer;" class="btn-warning btn-circle bi bi-pencil-fill btn_icon_accordion btn_icon_table_edit" id="editMeterLocation"></a><a href="#" title="Click to Delete" data-id="'.$this->location->location_id.'" style="cursor: pointer;" class="btn-danger btn-circle bi-trash3-fill btn_icon_accordion btn_icon_table_delete" id="deleteMeterLocation"></a>',
        ]);
});

test('legacy meter location create validates required fields and creates rows', function () {
    $admin = User::factory()->create();

    $this->withSession(['loginID' => $admin->id])
        ->post('/create_meter_location_post', [
            'siteID' => '',
            'location_code' => '',
            'location_description' => '',
        ])
        ->assertSessionHasErrors([
            'siteID' => 'Site is Required',
            'location_code' => 'Location Code is Required',
            'location_description' => 'Location Description is Required',
        ]);

    $this->withSession(['loginID' => $admin->id])
        ->postJson('/create_meter_location_post', [
            'siteID' => $this->site->site_id,
            'location_code' => 'ER-NEW',
            'location_description' => 'New Location',
        ])
        ->assertOk()
        ->assertJson(['success' => 'Meter Location Information Successfully Created!']);

    expect(MeterLocation::query()->where('location_code', 'ER-NEW')
        ->where('site_idx', $this->site->site_id)
        ->exists())->toBeTrue();
});

test('legacy meter location create enforces site-scoped uniqueness', function () {
    $admin = User::factory()->create();
    $otherSite = Site::factory()->create([
        'site_code' => 'SITEB',
        'building_description' => 'Other Building',
    ]);

    $this->withSession(['loginID' => $admin->id])
        ->post('/create_meter_location_post', [
            'siteID' => $this->site->site_id,
            'location_code' => 'ER-A',
            'location_description' => 'Some Desc',
        ])
        ->assertSessionHasErrors(['location_code' => 'The location code has already been taken.']);

    $this->withSession(['loginID' => $admin->id])
        ->post('/create_meter_location_post', [
            'siteID' => $otherSite->site_id,
            'location_code' => 'ER-A',
            'location_description' => 'Some Desc',
        ])
        ->assertOk()
        ->assertJson(['success' => 'Meter Location Information Successfully Created!']);
});

test('legacy meter location info endpoint returns payload and handles missing rows', function () {
    $admin = User::factory()->create();

    $payload = $this->withSession(['loginID' => $admin->id])
        ->postJson('/meter_location_info', ['meterlocationID' => $this->location->location_id])
        ->assertOk()
        ->json();

    expect($payload['location_code'])->toBe($this->location->location_code);
    expect($payload['location_description'])->toBe($this->location->location_description);
});

test('legacy meter location update validates required and updates', function () {
    $admin = User::factory()->create();

    $this->withSession(['loginID' => $admin->id])
        ->post('/update_meter_location_post', [
            'meterlocationID' => $this->location->location_id,
            'siteID' => '',
            'location_code' => '',
            'location_description' => '',
        ])
        ->assertSessionHasErrors([
            'siteID' => 'Site is Required',
            'location_code' => 'Location Code is Required',
            'location_description' => 'Location Description is Required',
        ]);

    $this->withSession(['loginID' => $admin->id])
        ->postJson('/update_meter_location_post', [
            'meterlocationID' => $this->location->location_id,
            'siteID' => $this->site->site_id,
            'location_code' => 'ER-U',
            'location_description' => 'Updated Location',
        ])
        ->assertOk()
        ->assertJson(['success' => 'Building Information Successfully Updated!']);

    $this->location->refresh();
    expect($this->location->location_code)->toBe('ER-U');
    expect($this->location->location_description)->toBe('Updated Location');
});

test('legacy meter location delete returns textual confirmation', function () {
    $admin = User::factory()->create();
    $target = MeterLocation::factory()->create([
        'site_idx' => $this->site->site_id,
        'location_code' => 'ER-DEL',
        'location_description' => 'Delete Me',
    ]);

    $this->withSession(['loginID' => $admin->id])
        ->post('/delete_meter_location_confirmed', ['meterlocationID' => $target->location_id])
        ->assertOk()
        ->assertSee('Deleted');

    expect(MeterLocation::query()->find($target->location_id))->toBeNull();
});

test('legacy meter location accordion endpoint returns raw location list for selected site', function () {
    $admin = User::factory()->create();

    $this->withSession(['loginID' => $admin->id])
        ->postJson('/get_ee_room_location_accordion', ['siteID' => $this->site->site_id])
        ->assertOk()
        ->assertJsonCount(1)
        ->assertJsonFragment(['location_code' => 'ER-A']);
});
