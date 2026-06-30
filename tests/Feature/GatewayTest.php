<?php

use App\Models\Gateway;
use App\Models\Meter;
use App\Models\MeterLocation;
use App\Models\Site;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->site = Site::factory()->create([
        'site_code' => 'SITEA',
        'building_description' => 'Gateway Test Building',
    ]);

    $this->location = MeterLocation::factory()->create([
        'site_idx' => $this->site->site_id,
        'location_code' => 'ER-01',
        'location_description' => 'Gateway Test Location',
    ]);

    $this->gateway = Gateway::factory()->create([
        'site_idx' => $this->site->site_id,
        'site_code' => $this->site->site_code,
        'location_idx' => $this->location->location_id,
        'gateway_sn' => 'GW-001',
        'gateway_mac' => 'AA:BB:CC:DD:EE:01',
        'gateway_ip' => '10.0.0.10',
    ]);
});

test('legacy gateway page requires legacy login session', function () {
    $this->get('/gateway')->assertRedirect('/')->assertSessionHas('fail', 'You Have to Login First');
});

test('legacy gateway page renders when loginID session exists', function () {
    $this->withSession(['loginID' => User::factory()->create()->id])
        ->get('/gateway')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Gateway')
            ->where('title', 'Gateway Management')
            ->where('gateways.0.gateway_sn', $this->gateway->gateway_sn)
        );
});

test('legacy gateway list includes legacy action marker and scope filter', function () {
    $admin = User::factory()->create();

    $this->withSession(['loginID' => $admin->id])
        ->getJson('/getGateway?siteID='.$this->site->site_id)
        ->assertOk()
        ->assertJsonPath('recordsTotal', 1)
        ->assertJsonPath('recordsFiltered', 1)
        ->assertJsonFragment(['gateway_sn' => 'GW-001'])
        ->assertJsonFragment([
            'action' => '<div align="center" class="action_table_menu_gateway"><a href="#" data-id="'.$this->gateway->rtu_id.'" class="btn-info btn-circle btn-sm bi bi-eye-fill btn_icon_table btn_icon_table_view" id="ViewGateway"></a><a href="#" data-id="'.$this->gateway->rtu_id.'" class="btn-warning btn-circle btn-sm bi bi-pencil-fill btn_icon_table btn_icon_table_edit" id="EditGateway"></a><a href="#" data-id="'.$this->gateway->rtu_id.'" class="btn-danger btn-circle btn-sm bi bi-trash3-fill btn_icon_table btn_icon_table_delete" id="DeleteGateway"></a></div>',
        ]);
});

test('legacy gateway list supports datatables search, pagination, and draw', function () {
    Gateway::factory()->create([
        'site_idx' => $this->site->site_id,
        'site_code' => $this->site->site_code,
        'location_idx' => $this->location->location_id,
        'gateway_sn' => 'GW-099',
        'gateway_mac' => 'AA:BB:CC:DD:EE:99',
        'gateway_ip' => '10.0.0.99',
    ]);

    $admin = User::factory()->create();

    $this->withSession(['loginID' => $admin->id])
        ->json('GET', '/getGateway', [
            'siteID' => $this->site->site_id,
            'draw' => 14,
            'start' => 0,
            'length' => 1,
            'search' => ['value' => 'GW-'],
            'order' => [['column' => 0, 'dir' => 'asc']],
            'columns' => [
                ['data' => 'gateway_sn'],
                ['data' => 'gateway_mac'],
            ],
        ])
        ->assertOk()
        ->assertJsonPath('draw', 14)
        ->assertJsonPath('recordsTotal', 2)
        ->assertJsonPath('recordsFiltered', 2)
        ->assertJsonCount(1, 'data');
});

test('legacy gateway creation validates required fields and creates on success', function () {
    $admin = User::factory()->create();

    $this->withSession(['loginID' => $admin->id])
        ->post('/create_gateway_post', [])
        ->assertSessionHasErrors([
            'gateway_sn' => 'Gateway Serial Number is Required',
            'gateway_mac' => 'MAC Address is Required',
            'gateway_ip' => 'IP Address/Sim # is Required',
            'location_id' => 'Area/EE Room is Required',
        ]);

    $this->withSession(['loginID' => $admin->id])
        ->postJson('/create_gateway_post', [
            'siteID' => $this->site->site_id,
            'site_code' => $this->site->site_code,
            'gateway_sn' => 'GW-002',
            'gateway_mac' => 'AA:BB:CC:DD:EE:02',
            'gateway_ip' => '10.0.0.11',
            'location_id' => $this->location->location_id,
            'connection_type' => 'LAN',
        ])
        ->assertOk()
        ->assertJson(['success' => 'Gateway Information successfully created!']);

    expect(Gateway::query()->where('gateway_sn', 'GW-002')->exists())->toBeTrue();
});

test('legacy gateway info endpoint returns gateway payload by id', function () {
    $admin = User::factory()->create();

    $payload = $this->withSession(['loginID' => $admin->id])
        ->postJson('/gateway_info', ['gatewayID' => $this->gateway->rtu_id])
        ->assertOk()
        ->json();

    expect($payload['gateway_sn'])->toBe($this->gateway->gateway_sn);
    expect((int) $payload['rtu_id'])->toBe($this->gateway->rtu_id);
});

test('legacy gateway update validates required fields and updates', function () {
    $admin = User::factory()->create();

    $this->withSession(['loginID' => $admin->id])
        ->post('/update_gateway_post', [
            'gatewayID' => $this->gateway->rtu_id,
            'gateway_sn' => '',
            'gateway_mac' => '',
            'gateway_ip' => '',
            'location_id' => '',
        ])
        ->assertSessionHasErrors([
            'gateway_sn' => 'Gateway Serial Number is Required',
            'gateway_mac' => 'MAC Address is Required',
            'gateway_ip' => 'IP Address/Sim # is Required',
            'location_id' => 'Area/EE Room is Required',
        ]);

    $this->withSession(['loginID' => $admin->id])
        ->postJson('/update_gateway_post', [
            'gatewayID' => $this->gateway->rtu_id,
            'site_code' => $this->site->site_code,
            'gateway_sn' => 'GW-001-U',
            'gateway_mac' => 'AA:BB:CC:DD:EE:AA',
            'gateway_ip' => '10.0.0.20',
            'location_id' => $this->location->location_id,
            'connection_type' => 'LAN',
        ])
        ->assertOk()
        ->assertJson(['success' => 'Gateway Information successfully updated!']);

    expect($this->gateway->refresh()->gateway_sn)->toBe('GW-001-U');
    expect($this->gateway->refresh()->gateway_ip)->toBe('10.0.0.20');
});

test('legacy gateway delete returns deleted confirmation', function () {
    $admin = User::factory()->create();

    $target = Gateway::factory()->create([
        'site_idx' => $this->site->site_id,
        'site_code' => $this->site->site_code,
    ]);

    $this->withSession(['loginID' => $admin->id])
        ->post('/delete_gateway_confirmed', ['gatewayID' => $target->rtu_id])
        ->assertOk()
        ->assertSee('Deleted');

    expect(Gateway::query()->find($target->rtu_id))->toBeNull();
});

test('legacy gateway delete is blocked while dependent meters exist', function () {
    $admin = User::factory()->create();

    $target = Gateway::factory()->create([
        'site_idx' => $this->site->site_id,
        'site_code' => $this->site->site_code,
    ]);

    Meter::factory()->create([
        'rtu_idx' => $target->rtu_id,
        'site_idx' => $this->site->site_id,
    ]);

    $this->withSession(['loginID' => $admin->id])
        ->post('/delete_gateway_confirmed', ['gatewayID' => $target->rtu_id])
        ->assertStatus(500)
        ->assertSee('Delete Failed');

    expect(Gateway::query()->find($target->rtu_id))->not->toBeNull();
});
