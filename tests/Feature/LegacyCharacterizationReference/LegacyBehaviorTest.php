<?php

namespace Tests\Feature\Characterization;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class LegacyBehaviorTest extends LegacyCharacterizationTestCase
{
    public function test_login_page_renders_with_seeded_web_settings(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Login')
            ->assertSee('Centralized Automated Meter Reading')
            ->assertSee('Username')
            ->assertSee('Password');
    }

    public function test_successful_login_sets_legacy_session_and_redirects_to_site(): void
    {
        $this->post('/login-user', [
            'user_name' => 'admin',
            'InputPassword' => '123456',
        ])
            ->assertRedirect('site')
            ->assertSessionHas('loginID', $this->fixture['adminId']);
    }

    public function test_failed_login_messages_are_preserved(): void
    {
        $this->from('/')->post('/login-user', [
            'user_name' => 'missing',
            'InputPassword' => '123456',
        ])
            ->assertRedirect('/')
            ->assertSessionHas('fail', 'This Username is not Registered.');

        $this->from('/')->post('/login-user', [
            'user_name' => 'admin',
            'InputPassword' => 'wrong-password',
        ])
            ->assertRedirect('/')
            ->assertSessionHas('fail', 'Incorrect Password');
    }

    public function test_protected_routes_redirect_anonymous_users_to_login(): void
    {
        foreach (['/site', '/user', '/sap_report', '/site_details/' . $this->fixture['siteAId']] as $uri) {
            $this->get($uri)
                ->assertRedirect('/')
                ->assertSessionHas('fail', 'You Have to Login First');
        }
    }

    public function test_admin_site_list_returns_all_sites_in_datatables_shape(): void
    {
        $response = $this->actingAsLegacyUser($this->fixture['adminId'])
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->getJson('/site/list');

        $response->assertOk()
            ->assertJsonStructure(['data'])
            ->assertSee('SITEA')
            ->assertSee('SITEB');
    }

    public function test_scoped_user_site_list_returns_only_assigned_sites(): void
    {
        $response = $this->actingAsLegacyUser($this->fixture['scopedUserId'])
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->getJson('/site/user/list');

        $response->assertOk()
            ->assertJsonStructure(['data'])
            ->assertSee('SITEA')
            ->assertDontSee('SITEB');
    }

    public function test_site_detail_page_loads_operational_dashboard_sections(): void
    {
        $this->actingAsLegacyUser($this->fixture['adminId'])
            ->get('/site_details/' . $this->fixture['siteAId'])
            ->assertOk()
            ->assertSee('SITEA')
            ->assertSee('Gateway')
            ->assertSee('Meter')
            ->assertSee('Building');
    }

    public function test_gateway_and_meter_create_validation_and_success_contracts(): void
    {
        $this->actingAsLegacyUser($this->fixture['adminId'])
            ->from('/site_details/' . $this->fixture['siteAId'])
            ->post('/create_gateway_post', [])
            ->assertRedirect('/site_details/' . $this->fixture['siteAId'])
            ->assertSessionHasErrors([
                'gateway_sn',
                'gateway_mac',
                'gateway_ip',
                'location_id',
            ]);

        $this->actingAsLegacyUser($this->fixture['adminId'])
            ->postJson('/create_gateway_post', [
                'siteID' => $this->fixture['siteAId'],
                'site_code' => 'SITEA',
                'gateway_sn' => 'GW-SN-002',
                'gateway_mac' => 'AA:BB:CC:DD:EE:02',
                'gateway_ip' => '10.0.0.11',
                'location_id' => $this->fixture['locationAId'],
                'connection_type' => 'LAN',
            ])
            ->assertOk()
            ->assertJson(['success' => 'Gateway Information successfully created!']);

        $gatewayId = DB::table('meter_rtu')->where('gateway_sn', 'GW-SN-002')->value('rtu_id');

        $this->actingAsLegacyUser($this->fixture['adminId'])
            ->from('/site_details/' . $this->fixture['siteAId'])
            ->post('/create_meter_post', [])
            ->assertRedirect('/site_details/' . $this->fixture['siteAId'])
            ->assertSessionHasErrors([
                'meter_name',
                'meter_model_id',
                'meter_default_name',
                'rtu_sn_number_id',
                'location_id',
            ]);

        $this->actingAsLegacyUser($this->fixture['adminId'])
            ->postJson('/create_meter_post', [
                'siteID' => $this->fixture['siteAId'],
                'site_code' => 'SITEA',
                'meter_name' => 'MTR-NEW',
                'meter_name_addressable' => 1,
                'meter_default_name' => '3',
                'meter_model_id' => $this->fixture['configId'],
                'rtu_sn_number_id' => $gatewayId,
                'location_id' => $this->fixture['locationAId'],
                'meter_multiplier' => 1,
                'meter_role' => 'Client Meter',
                'meter_status' => 'Active',
            ])
            ->assertOk()
            ->assertJson(['success' => 'Meter Information Successfully Created!']);

        $this->assertDatabaseHas('meter_details', ['meter_name' => 'MTR-NEW']);
    }

    public function test_csv_meter_import_contract_for_missing_bad_and_valid_files(): void
    {
        Storage::fake('public');

        $this->actingAsLegacyUser($this->fixture['adminId'])
            ->from('/site_details/' . $this->fixture['siteAId'])
            ->post('/import_meters', [])
            ->assertRedirect('/site_details/' . $this->fixture['siteAId'])
            ->assertSessionHasErrors(['csv_file']);

        $badCsv = UploadedFile::fake()->createWithContent('meters.csv', "ER-A,MTR-BAD\n");

        $this->actingAsLegacyUser($this->fixture['adminId'])
            ->postJson('/import_meters', [
                'import_gateway_idx' => $this->fixture['gatewayId'],
                'import_gateway_site_idx' => $this->fixture['siteAId'],
                'import_gateway_site_code' => 'SITEA',
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
            "ER-A,MTR-IMPORT,Tenant B,Brand X,Power,Active,zmd402.cfg,4,Client Meter,1,Imported meter\n"
        );

        $this->actingAsLegacyUser($this->fixture['adminId'])
            ->postJson('/import_meters', [
                'import_gateway_idx' => $this->fixture['gatewayId'],
                'import_gateway_site_idx' => $this->fixture['siteAId'],
                'import_gateway_site_code' => 'SITEA',
                'csv_file' => $validCsv,
            ])
            ->assertOk()
            ->assertJson([
                'success' => 'CSV File Successfully Imported!',
                'total_line' => 1,
            ])
            ->assertSee('New Meter');

        $this->assertDatabaseHas('meter_details', [
            'meter_name' => 'MTR-IMPORT',
            'site_idx' => $this->fixture['siteAId'],
        ]);
    }

    public function test_raw_report_json_and_excel_export_dependency_contract(): void
    {
        $payload = [
            'site_id' => $this->fixture['siteAId'],
            'meter_id' => 'MTR-001',
            'meter_role' => 'Client Meter',
            'start_date' => '2026-01-01',
            'start_time' => '00:00:00',
            'end_date' => '2026-01-01',
            'end_time' => '01:00:00',
            'cols_set1' => 'false',
            'cols_set2' => 'false',
            'cols_set3' => 'false',
            'cols_set5' => 'false',
            'cols_set6' => 'false',
            'cols_set7' => 'false',
        ];

        $this->actingAsLegacyUser($this->fixture['adminId'])
            ->postJson('/generate_raw_report', $payload, ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()
            ->assertJsonStructure(['data'])
            ->assertSee('2026-01-01 00:00:00')
            ->assertSee('2026-01-01 01:00:00');

        $this->assertNotNull(app('router')->getRoutes()->getByName('generate_raw_report_excel'));
        $this->assertFileExists(public_path('/template/Raw Data.xlsx'));
    }
}
