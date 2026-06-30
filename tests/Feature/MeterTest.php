<?php

use App\Models\ConfigurationFile;
use App\Models\Gateway;
use App\Models\Meter;
use App\Models\MeterLocation;
use App\Models\Site;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->markTestSkipped('Meter slice is preview-only and not yet officially migrated.');
    $this->site = Site::factory()->create([
        'site_code' => 'SITEA',
        'building_description' => 'Meter Test Building',
    ]);

    $this->configurationFile = ConfigurationFile::factory()->create([
        'config_file' => 'zmd402.cfg',
        'meter_model' => 'zmd402',
    ]);

    $this->location = MeterLocation::factory()->create([
        'site_idx' => $this->site->site_id,
        'location_code' => 'ER-01',
        'location_description' => 'Meter Test Location',
    ]);

    $this->gateway = Gateway::factory()->create([
        'site_idx' => $this->site->site_id,
        'site_code' => $this->site->site_code,
        'location_idx' => $this->location->location_id,
        'gateway_sn' => 'GW-001',
    ]);

    $this->meter = Meter::factory()->create([
        'site_idx' => $this->site->site_id,
        'site_code' => $this->site->site_code,
        'rtu_idx' => $this->gateway->rtu_id,
        'location_idx' => $this->location->location_id,
        'config_idx' => $this->configurationFile->config_id,
        'meter_name' => 'MTR-001',
        'meter_default_name' => '1',
    ]);
});

test('legacy meter page requires legacy login session', function () {
    $this->get('/meter')->assertRedirect('/')->assertSessionHas('fail', 'You Have to Login First');
});

test('legacy meter page renders when loginID session exists', function () {
    $this->withSession(['loginID' => User::factory()->create()->id])
        ->get('/meter')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Meter')
            ->where('title', 'Meter Management')
            ->has('meters', 1)
            ->where('meters.0.meter_name', 'MTR-001')
        );
});

test('legacy meter list includes record contract', function () {
    $admin = User::factory()->create();

    $this->withSession(['loginID' => $admin->id])
        ->getJson('/getMeter?siteID='.$this->site->site_id)
        ->assertOk()
        ->assertJsonPath('recordsTotal', 1)
        ->assertJsonPath('recordsFiltered', 1)
        ->assertJsonFragment(['meter_name' => 'MTR-001'])
        ->assertJsonFragment([
            'action' => '<div align="center" class="action_table_menu_gateway"><a href="#" data-id="'.$this->meter->meter_id.'" class="btn-warning btn-circle btn-sm bi bi-pencil-fill btn_icon_table btn_icon_table_edit" id="EditMeter"></a><a href="#" data-id="'.$this->meter->meter_id.'" class="btn-danger btn-circle btn-sm bi bi-trash3-fill btn_icon_table btn_icon_table_delete" id="DeleteMeter"></a></div>',
        ]);
});

test('legacy meter creation validates required fields and creates on success', function () {
    $admin = User::factory()->create();

    $this->withSession(['loginID' => $admin->id])
        ->post('/create_meter_post', [])
        ->assertSessionHasErrors([
            'meter_name' => 'Meter Description/Serial Number is Required',
            'meter_model_id' => 'Configuration file is Required',
            'meter_default_name' => 'Alternate Address is Required',
            'rtu_sn_number_id' => 'Gateway is Required',
            'location_id' => 'Area/EE Room is Required',
        ]);

    $this->withSession(['loginID' => $admin->id])
        ->postJson('/create_meter_post', [
            'siteID' => $this->site->site_id,
            'site_code' => $this->site->site_code,
            'meter_name' => 'MTR-NEW',
            'meter_name_addressable' => 1,
            'meter_default_name' => '3',
            'meter_model_id' => $this->configurationFile->config_id,
            'rtu_sn_number_id' => $this->gateway->rtu_id,
            'location_id' => $this->location->location_id,
            'customer_name' => 'Tenant',
            'meter_type' => 'Power',
            'meter_brand' => 'Brand X',
            'meter_multiplier' => 1,
            'meter_role' => 'Client Meter',
            'meter_status' => 'ACTIVE',
            'meter_remarks' => 'Added via test',
        ])
        ->assertOk()
        ->assertJson(['success' => 'Meter Information Successfully Created!']);

    expect(Meter::query()->where('meter_name', 'MTR-NEW')->exists())->toBeTrue();
});

test('legacy meter info endpoint returns meter payload by id', function () {
    $admin = User::factory()->create();

    $payload = $this->withSession(['loginID' => $admin->id])
        ->postJson('/meter_info', ['meterID' => $this->meter->meter_id])
        ->assertOk()
        ->json();

    expect($payload['meter_name'])->toBe($this->meter->meter_name);
    expect((int) $payload['meter_id'])->toBe($this->meter->meter_id);
});

test('legacy meter update validates required fields and updates', function () {
    $admin = User::factory()->create();

    $this->withSession(['loginID' => $admin->id])
        ->post('/update_meter_post', [
            'meterID' => $this->meter->meter_id,
            'meter_name' => '',
            'meter_model_id' => '',
            'meter_default_name' => '',
            'rtu_sn_number_id' => '',
            'location_id' => '',
        ])
        ->assertSessionHasErrors([
            'meter_name' => 'Meter Description/Serial Number is Required',
            'meter_model_id' => 'Configuration file is Required',
            'meter_default_name' => 'Alternate Address is Required',
            'rtu_sn_number_id' => 'Gateway is Required',
            'location_id' => 'Area/EE Room is Required',
        ]);

    $this->withSession(['loginID' => $admin->id])
        ->postJson('/update_meter_post', [
            'meterID' => $this->meter->meter_id,
            'siteID' => $this->site->site_id,
            'site_code' => $this->site->site_code,
            'meter_name' => 'MTR-UPDATED',
            'meter_name_addressable' => 1,
            'meter_default_name' => '3',
            'meter_model_id' => $this->configurationFile->config_id,
            'rtu_sn_number_id' => $this->gateway->rtu_id,
            'location_id' => $this->location->location_id,
            'customer_name' => 'Tenant',
            'meter_type' => 'Power',
            'meter_brand' => 'Brand X',
            'meter_multiplier' => 2,
            'meter_role' => 'Client Meter',
            'meter_status' => 'ACTIVE',
            'meter_remarks' => 'Updated in test',
        ])
        ->assertOk()
        ->assertJson(['success' => 'Meter Information Successfully Updated!']);

    expect($this->meter->refresh()->meter_name)->toBe('MTR-UPDATED');
});

test('legacy meter delete returns deleted confirmation', function () {
    $admin = User::factory()->create();

    $target = Meter::factory()->create([
        'site_idx' => $this->site->site_id,
        'site_code' => $this->site->site_code,
        'rtu_idx' => $this->gateway->rtu_id,
        'location_idx' => $this->location->location_id,
        'config_idx' => $this->configurationFile->config_id,
        'meter_name' => 'MTR-TO-DELETE',
    ]);

    $this->withSession(['loginID' => $admin->id])
        ->post('/delete_meter_confirmed', ['meterID' => $target->meter_id])
        ->assertOk()
        ->assertSee('Deleted');

    expect(Meter::query()->find($target->meter_id))->toBeNull();
});

test('legacy meter CSV import validates file then imports valid payload', function () {
    $admin = User::factory()->create();

    $this->withSession(['loginID' => $admin->id])
        ->post('/import_meters', [])
        ->assertSessionHasErrors(['csv_file']);

    $badCsv = UploadedFile::fake()->createWithContent('meters.csv', "ER-01,MTR-BAD\n");

    $this->withSession(['loginID' => $admin->id])
        ->postJson('/import_meters', [
            'import_gateway_idx' => $this->gateway->rtu_id,
            'import_gateway_site_idx' => $this->site->site_id,
            'import_gateway_site_code' => $this->site->site_code,
            'csv_file' => $badCsv,
        ])
        ->assertOk()
        ->assertJson([
            'error' => 'CSV File Error, please check the Content/Column Count.',
            'total_line' => 0,
            'result_csv_import' => 0,
        ]);

    $validCsv = UploadedFile::fake()->createWithContent(
        'meters.csv',
        "ER-01,MTR-IMPORT,Tenant B,Brand X,Power,Active,zmd402.cfg,4,Client Meter,1,Imported meter\n"
    );

    $this->withSession(['loginID' => $admin->id])
        ->postJson('/import_meters', [
            'import_gateway_idx' => $this->gateway->rtu_id,
            'import_gateway_site_idx' => $this->site->site_id,
            'import_gateway_site_code' => $this->site->site_code,
            'csv_file' => $validCsv,
        ])
        ->assertOk()
        ->assertJson([
            'success' => 'CSV File Successfully Imported!',
            'total_line' => 1,
        ]);

    expect(Meter::query()->where('meter_name', 'MTR-IMPORT')->exists())->toBeTrue();
});
