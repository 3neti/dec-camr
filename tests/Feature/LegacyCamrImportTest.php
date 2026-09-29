<?php

use App\Actions\Migration\ImportLegacyCamrDataAction;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    config()->set('database.connections.legacy_fixture', [
        'driver' => 'sqlite',
        'database' => ':memory:',
        'prefix' => '',
        'foreign_key_constraints' => false,
    ]);

    DB::purge('legacy_fixture');

    foreach ([
        'user_tb' => 'user_id',
        'meter_company_table' => 'company_id',
        'meter_division_table' => 'division_id',
        'meter_configuration_file' => 'config_id',
        'meter_site' => 'site_id',
        'meter_building_table' => 'building_id',
        'meter_location_table' => 'location_id',
        'meter_rtu' => 'rtu_id',
        'meter_details' => 'meter_id',
        'user_access_group' => 'user_access_id',
        'meter_data' => 'id',
    ] as $table => $key) {
        Schema::connection('legacy_fixture')->create($table, function (Blueprint $blueprint) use ($table, $key): void {
            $blueprint->integer($key)->primary();

            if ($table === 'user_tb') {
                $blueprint->string('user_name');
                $blueprint->string('user_real_name')->nullable();
                $blueprint->string('user_type')->nullable();
                $blueprint->string('user_access')->nullable();
            }

            if ($table === 'meter_company_table') {
                $blueprint->string('company_name');
                $blueprint->string('company_code')->nullable();
            }
        });
    }
});

test('imports legacy identity and hierarchy data without usable legacy credentials', function () {
    DB::connection('legacy_fixture')->table('user_tb')->insert([
        'user_id' => 37,
        'user_name' => 'legacy.admin',
        'user_real_name' => 'Legacy Admin',
        'user_type' => 'Admin',
        'user_access' => 'ALL',
    ]);
    DB::connection('legacy_fixture')->table('meter_company_table')->insert([
        'company_id' => 4,
        'company_name' => 'CAMR Company',
        'company_code' => 'CAMR',
    ]);

    $counts = app(ImportLegacyCamrDataAction::class)->execute('legacy_fixture');

    expect($counts['user_tb'])->toBe(1)
        ->and($counts['meter_company_table'])->toBe(1)
        ->and(DB::table('meter_company_table')->where('company_id', 4)->value('company_code'))->toBe('CAMR');

    $user = DB::table('users')->where('id', 37)->first();
    expect($user->name)->toBe('legacy.admin')
        ->and($user->email)->toBe('legacy-37@preview.invalid')
        ->and(Hash::check('123456', $user->password))->toBeFalse();
});

test('dry run counts source rows without changing the target', function () {
    DB::connection('legacy_fixture')->table('user_tb')->insert([
        'user_id' => 37,
        'user_name' => 'legacy.admin',
    ]);

    $counts = app(ImportLegacyCamrDataAction::class)->execute('legacy_fixture', true);

    expect($counts['user_tb'])->toBe(1)
        ->and(DB::table('users')->count())->toBe(0);
});
