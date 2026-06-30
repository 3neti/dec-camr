<?php

use App\Models\ConfigurationFile;
use App\Models\Meter;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->configurationFile = ConfigurationFile::factory()->create(['config_file' => 'zmd402.cfg']);
});

test('legacy configuration file page requires legacy login session', function () {
    $this->get('/configuration_file')->assertRedirect('/')->assertSessionHas('fail', 'You Have to Login First');
});

test('legacy configuration file page renders through inertia when loginID exists', function () {
    $this->withSession(['loginID' => User::factory()->create()->id])
        ->get('/configuration_file')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('ConfigurationFile')
            ->where('title', 'Configuration File List')
            ->where('configuration_files.0.config_file', $this->configurationFile->config_file)
        );
});

test('legacy configuration file list contract uses action link ids', function () {
    $admin = User::factory()->create();

    $this->withSession(['loginID' => $admin->id])
        ->postJson('/configuration_file_list')
        ->assertOk()
        ->assertJsonPath('recordsTotal', 1)
        ->assertJsonPath('recordsFiltered', 1)
        ->assertJsonFragment(['config_file' => $this->configurationFile->config_file])
        ->assertJsonFragment([
            'action' => '<a href="#" data-id="'.$this->configurationFile->config_id.'" class="bi bi-pencil-fill btn_icon_table btn_icon_table_edit" id="editconfiguration_file" title="Update Company Information"></a> <a href="#" data-id="'.$this->configurationFile->config_id.'" class="bi bi-trash3-fill btn_icon_table btn_icon_table_delete" id="deleteconfiguration_file" title="Delete Company Information"></a>',
        ]);
});

test('legacy configuration file list supports datatables search, pagination, and draw', function () {
    $admin = User::factory()->create();
    ConfigurationFile::factory()->create(['config_file' => 'aaa.cfg']);
    ConfigurationFile::factory()->create(['config_file' => 'zzz.cfg']);

    $this->withSession(['loginID' => $admin->id])
        ->postJson('/configuration_file_list', [
            'draw' => 4,
            'start' => 0,
            'length' => 2,
            'search' => ['value' => '.cfg'],
            'order' => [['column' => 0, 'dir' => 'asc']],
            'columns' => [
                ['data' => 'config_file'],
                ['data' => 'created_at_dt_format'],
            ],
        ])
        ->assertOk()
        ->assertJsonPath('draw', 4)
        ->assertJsonPath('recordsTotal', 3)
        ->assertJsonPath('recordsFiltered', 3)
        ->assertJsonCount(2, 'data');
});

test('legacy configuration file create validates required name and persists on success', function () {
    $admin = User::factory()->create();

    $this->withSession(['loginID' => $admin->id])
        ->post('/create_configuration_file_post', ['configuration_file_name' => ''])
        ->assertSessionHasErrors(['configuration_file_name' => 'File Name is Required']);

    $this->withSession(['loginID' => $admin->id])
        ->postJson('/create_configuration_file_post', ['configuration_file_name' => 'seeded-config.cfg'])
        ->assertOk()
        ->assertJson(['success' => 'Configuration File Information Successfully Created!']);

    expect(ConfigurationFile::query()->where('config_file', 'seeded-config.cfg')->exists())->toBeTrue();
});

test('legacy configuration file create rejects duplicates with validation message', function () {
    $admin = User::factory()->create();

    $this->withSession(['loginID' => $admin->id])
        ->post('/create_configuration_file_post', ['configuration_file_name' => $this->configurationFile->config_file])
        ->assertSessionHasErrors([
            'configuration_file_name' => 'The configuration file name has already been taken.',
        ]);
});

test('legacy configuration file info endpoint returns record payload by legacy id key', function () {
    $admin = User::factory()->create();

    $response = $this->withSession(['loginID' => $admin->id])
        ->postJson('/configuration_file_info', ['ConfigFileID' => $this->configurationFile->config_id])
        ->assertOk()
        ->json();

    expect($response['config_file'])->toBe($this->configurationFile->config_file);
});

test('legacy configuration file update validates and updates', function () {
    $admin = User::factory()->create();

    $this->withSession(['loginID' => $admin->id])
        ->post('/update_configuration_file_post', ['ConfigFileID' => $this->configurationFile->config_id, 'configuration_file_name' => ''])
        ->assertSessionHasErrors(['configuration_file_name' => 'File Name is Required']);

    $this->withSession(['loginID' => $admin->id])
        ->postJson('/update_configuration_file_post', ['ConfigFileID' => $this->configurationFile->config_id, 'configuration_file_name' => 'updated.cfg'])
        ->assertOk()
        ->assertJson(['success' => 'Configuration File Successfully Updated!']);

    expect($this->configurationFile->refresh()->config_file)->toBe('updated.cfg');
});

test('legacy configuration file delete returns deleted confirmation', function () {
    $admin = User::factory()->create();
    $target = ConfigurationFile::factory()->create(['config_file' => 'disposable.cfg']);

    $this->withSession(['loginID' => $admin->id])
        ->post('/delete_configuration_file_confirmed', ['ConfigFileID' => $target->config_id])
        ->assertOk();

    expect(ConfigurationFile::query()->find($target->config_id))->toBeNull();
});

test('legacy configuration file delete is blocked while dependent meters exist', function () {
    $admin = User::factory()->create();
    $target = ConfigurationFile::factory()->create(['config_file' => 'meter-dependent.cfg']);

    Meter::factory()->create(['config_idx' => $target->config_id]);

    $this->withSession(['loginID' => $admin->id])
        ->post('/delete_configuration_file_confirmed', ['ConfigFileID' => $target->config_id])
        ->assertStatus(500)
        ->assertJson(['error' => 'Delete Failed!']);

    expect(ConfigurationFile::query()->find($target->config_id))->not->toBeNull();
});
