<?php

namespace Tests\Feature\LegacyCharacterizationReference;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

abstract class LegacyCharacterizationTestCase extends TestCase
{
    protected array $fixture = [];

    protected function setUp(): void
    {
        if (! env('CHARACTERIZATION_DB_TESTS', false)) {
            $this->markTestSkipped('Set CHARACTERIZATION_DB_TESTS=1 and use a dedicated test database to run legacy characterization tests.');
        }

        parent::setUp();

        $this->assertSafeDatabase();

        $this->rebuildCharacterizationSchema();

        $this->fixture = $this->seedCharacterizationFixtures();
    }

    protected function assertSafeDatabase(): void
    {
        $database = (string) config('database.connections.'.config('database.default').'.database');

        $this->assertNotSame('', $database, 'A dedicated test database must be configured.');
        $this->assertMatchesRegularExpression('/(test|testing|characterization)/i', $database, 'Refusing to migrate a database whose name does not look like a test database.');
    }

    protected function rebuildCharacterizationSchema(): void
    {
        Schema::disableForeignKeyConstraints();

        foreach ([
            'activity_log',
            'meter_data',
            'meter_details',
            'meter_rtu',
            'meter_configuration_file',
            'meter_location_table',
            'meter_building_table',
            'meter_site',
            'meter_division_table',
            'meter_company_table',
            'user_access_group',
            'user_tb',
            'web_page_settings',
            'sessions',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::enableForeignKeyConstraints();

        DB::statement(<<<'SQL'
            CREATE TABLE web_page_settings (
                default_web_settings INT NULL DEFAULT 1,
                navigation_header_title VARCHAR(255) NULL DEFAULT 'Lighting Automation System',
                image_logo LONGBLOB NULL,
                header_navigation_width DOUBLE NULL DEFAULT 70,
                login_page_logo_width DOUBLE NULL,
                created_at DATETIME NULL,
                created_by_user_idx INT NULL,
                updated_at DATETIME NULL,
                modified_by_user_idx INT NULL
            )
        SQL);

        DB::statement(<<<'SQL'
            CREATE TABLE user_tb (
                user_id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
                user_id_sap TEXT NULL,
                user_name VARCHAR(100) NOT NULL,
                user_real_name VARCHAR(100) NOT NULL,
                user_job_title VARCHAR(100) NULL,
                user_password VARCHAR(255) NOT NULL,
                user_type VARCHAR(100) NOT NULL,
                user_expiration DATE NOT NULL DEFAULT '9999-12-31',
                user_list_src VARCHAR(100) NOT NULL DEFAULT 'AMR',
                user_access VARCHAR(100) NOT NULL DEFAULT 'Selected',
                user_email_address VARCHAR(100) NULL,
                user_site_list_ids TEXT NULL,
                created_at DATETIME NULL,
                created_by_user_idx INT NOT NULL,
                updated_at DATETIME NULL,
                modified_by_user_idx INT NULL
            )
        SQL);

        DB::statement(<<<'SQL'
            CREATE TABLE user_access_group (
                user_access_id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
                user_idx TEXT NOT NULL,
                user_name TEXT NULL,
                user_expiration DATE NULL,
                site_idx INT NOT NULL,
                created_at DATETIME NULL,
                created_by_user_idx INT NULL,
                updated_at DATETIME NULL,
                updated_by_user_idx INT NULL,
                access_list_src TEXT NULL
            )
        SQL);

        DB::statement(<<<'SQL'
            CREATE TABLE meter_company_table (
                company_id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
                company_code VARCHAR(255) NULL,
                company_name VARCHAR(255) NOT NULL,
                created_by_user_idx INT NOT NULL,
                created_at DATETIME NULL,
                modified_by_user_idx INT NULL,
                updated_at DATETIME NULL
            )
        SQL);

        DB::statement(<<<'SQL'
            CREATE TABLE meter_division_table (
                division_id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
                division_code VARCHAR(255) NOT NULL,
                division_name VARCHAR(255) NOT NULL,
                created_by_user_idx INT NOT NULL,
                created_at DATETIME NULL,
                modified_by_user_idx INT NULL,
                updated_at DATETIME NULL
            )
        SQL);

        DB::statement(<<<'SQL'
            CREATE TABLE meter_site (
                site_id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
                division_idx INT NOT NULL,
                company_idx INT NOT NULL,
                building_idx INT NULL DEFAULT 0,
                site_code VARCHAR(100) NULL,
                created_by_user_idx INT NOT NULL,
                created_at DATETIME NULL,
                modified_by_user_idx INT NULL DEFAULT 0,
                updated_at DATETIME NULL,
                last_log_update DATETIME NULL,
                deleted_at VARCHAR(50) NULL
            )
        SQL);

        DB::statement(<<<'SQL'
            CREATE TABLE meter_building_table (
                building_id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
                site_idx INT NOT NULL,
                meter_site_id INT NULL,
                building_code VARCHAR(255) NOT NULL,
                building_description VARCHAR(255) NOT NULL,
                cut_off INT NULL,
                device_ip_range VARCHAR(255) NULL,
                ip_network VARCHAR(255) NULL,
                ip_netmask VARCHAR(255) NULL,
                ip_gateway VARCHAR(255) NULL,
                created_at DATETIME NULL,
                created_by_user_idx INT NULL,
                updated_at DATETIME NULL,
                modified_by_user_idx INT NULL
            )
        SQL);

        DB::statement(<<<'SQL'
            CREATE TABLE meter_location_table (
                location_id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
                site_idx INT NOT NULL,
                building_id INT NULL DEFAULT 0,
                location_code VARCHAR(255) NOT NULL,
                location_description VARCHAR(255) NOT NULL,
                created_at TIMESTAMP NULL,
                created_by_user_idx INT NULL,
                updated_at TIMESTAMP NULL,
                modified_by_user_idx INT NULL
            )
        SQL);

        DB::statement(<<<'SQL'
            CREATE TABLE meter_configuration_file (
                config_id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
                meter_model TEXT NOT NULL,
                config_file TEXT NOT NULL,
                created_by_user_idx INT NOT NULL,
                created_at DATETIME NULL,
                modified_by_user_idx INT NULL,
                updated_at DATETIME NULL
            )
        SQL);

        DB::statement(<<<'SQL'
            CREATE TABLE meter_rtu (
                rtu_id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
                site_idx INT NOT NULL,
                location_idx INT NULL,
                site_code VARCHAR(100) NULL,
                gateway_sn VARCHAR(255) NOT NULL,
                gateway_mac VARCHAR(255) NOT NULL,
                gateway_ip VARCHAR(255) NOT NULL,
                connection_type VARCHAR(50) NOT NULL,
                ip_netmask VARCHAR(255) NULL,
                ip_gateway VARCHAR(255) NULL,
                rtu_server_ip VARCHAR(50) NULL,
                gateway_description VARCHAR(255) NULL,
                update_rtu INT NULL DEFAULT 0,
                update_rtu_location INT NULL DEFAULT 0,
                update_rtu_ssh INT NULL DEFAULT 0,
                update_rtu_force_lp INT NULL DEFAULT 0,
                idf_number VARCHAR(255) NULL,
                switch_name VARCHAR(255) NULL,
                idf_port VARCHAR(255) NULL,
                created_at VARCHAR(20) NULL,
                created_by_user_idx INT NOT NULL,
                updated_at VARCHAR(20) NULL,
                modified_by_user_idx INT NULL,
                last_log_update VARCHAR(20) NULL DEFAULT '0000-00-00 00:00:00',
                soft_rev VARCHAR(100) NULL DEFAULT '0'
            )
        SQL);

        DB::statement(<<<'SQL'
            CREATE TABLE meter_details (
                meter_id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
                site_idx INT NOT NULL,
                rtu_idx INT NOT NULL,
                location_idx INT NOT NULL,
                building_idx INT NULL DEFAULT 0,
                config_idx INT NOT NULL,
                site_code VARCHAR(100) NOT NULL,
                meter_name VARCHAR(255) NOT NULL,
                meter_name_addressable INT NOT NULL DEFAULT 1,
                meter_load_profile VARCHAR(50) NOT NULL DEFAULT 'NO',
                meter_default_name VARCHAR(255) NOT NULL,
                meter_type VARCHAR(255) NULL,
                meter_brand VARCHAR(255) NULL,
                meter_role VARCHAR(100) NOT NULL DEFAULT 'Client Meter',
                meter_remarks VARCHAR(255) NULL,
                customer_name VARCHAR(255) NULL,
                meter_multiplier DOUBLE NOT NULL DEFAULT 1,
                meter_status VARCHAR(50) NOT NULL,
                last_log_update VARCHAR(30) NOT NULL DEFAULT '0000-00-00 00:00:00',
                soft_rev VARCHAR(50) NULL DEFAULT '0',
                created_at DATETIME NULL,
                created_by_user_idx INT NOT NULL,
                updated_at DATETIME NULL,
                modified_by_user_idx INT NOT NULL
            )
        SQL);

        DB::statement(<<<'SQL'
            CREATE TABLE meter_data (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                location VARCHAR(30) NOT NULL DEFAULT 'Home',
                meter_id VARCHAR(30) NOT NULL,
                datetime DATETIME NOT NULL,
                vrms_a DOUBLE NOT NULL,
                vrms_b DOUBLE NOT NULL,
                vrms_c DOUBLE NOT NULL,
                irms_a DOUBLE NOT NULL,
                irms_b DOUBLE NOT NULL,
                irms_c DOUBLE NOT NULL,
                freq DOUBLE NOT NULL,
                pf DOUBLE NOT NULL,
                watt DOUBLE NOT NULL,
                va DOUBLE NOT NULL,
                var DOUBLE NOT NULL,
                wh_del DOUBLE NOT NULL,
                wh_rec DOUBLE NOT NULL,
                wh_net DOUBLE NOT NULL,
                wh_total DOUBLE NOT NULL,
                varh_neg DOUBLE NOT NULL,
                varh_pos DOUBLE NOT NULL,
                varh_net DOUBLE NOT NULL,
                varh_total DOUBLE NOT NULL,
                vah_total DOUBLE NOT NULL,
                max_rec_kw_dmd DOUBLE NOT NULL,
                max_rec_kw_dmd_time DATETIME NULL,
                max_del_kw_dmd DOUBLE NOT NULL,
                max_del_kw_dmd_time DATETIME NULL,
                max_pos_kvar_dmd DOUBLE NOT NULL,
                max_pos_kvar_dmd_time DATETIME NULL,
                max_neg_kvar_dmd DOUBLE NOT NULL,
                max_neg_kvar_dmd_time DATETIME NULL,
                v_ph_angle_a DOUBLE NOT NULL,
                v_ph_angle_b DOUBLE NOT NULL,
                v_ph_angle_c DOUBLE NOT NULL,
                i_ph_angle_a DOUBLE NOT NULL,
                i_ph_angle_b DOUBLE NOT NULL,
                i_ph_angle_c DOUBLE NOT NULL,
                mac_addr TEXT NOT NULL,
                soft_rev TEXT NOT NULL,
                relay_status INT NOT NULL,
                dt TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                genset_status INT NULL,
                INDEX meter_data_index (meter_id, datetime, location)
            )
        SQL);

        DB::statement(<<<'SQL'
            CREATE TABLE sessions (
                id VARCHAR(255) NOT NULL PRIMARY KEY,
                user_id BIGINT UNSIGNED NULL,
                ip_address VARCHAR(45) NULL,
                user_agent TEXT NULL,
                payload TEXT NOT NULL,
                last_activity INT NOT NULL,
                INDEX sessions_user_id_index (user_id),
                INDEX sessions_last_activity_index (last_activity)
            )
        SQL);

        DB::statement(<<<'SQL'
            CREATE TABLE activity_log (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                log_name VARCHAR(255) NULL,
                description TEXT NOT NULL,
                subject_type VARCHAR(255) NULL,
                subject_id BIGINT UNSIGNED NULL,
                causer_type VARCHAR(255) NULL,
                causer_id BIGINT UNSIGNED NULL,
                properties LONGTEXT NULL,
                created_at TIMESTAMP NULL,
                updated_at TIMESTAMP NULL,
                INDEX activity_log_log_name_index (log_name),
                INDEX subject (subject_type, subject_id),
                INDEX causer (causer_type, causer_id)
            )
        SQL);
    }

    protected function seedCharacterizationFixtures(): array
    {
        DB::table('web_page_settings')->insert([
            'default_web_settings' => 1,
            'navigation_header_title' => 'Centralized Automated Meter Reading',
            'image_logo' => '',
            'header_navigation_width' => 70,
            'login_page_logo_width' => 100,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $adminId = DB::table('user_tb')->insertGetId([
            'user_name' => 'admin',
            'user_real_name' => 'DEC',
            'user_password' => Hash::make('123456'),
            'user_type' => 'Admin',
            'user_access' => 'ALL',
            'user_email_address' => 'admin@example.test',
            'created_by_user_idx' => 0,
            'modified_by_user_idx' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $scopedUserId = DB::table('user_tb')->insertGetId([
            'user_name' => 'scoped',
            'user_real_name' => 'Scoped User',
            'user_password' => Hash::make('123456'),
            'user_type' => 'User',
            'user_access' => 'Selected',
            'user_email_address' => 'scoped@example.test',
            'created_by_user_idx' => $adminId,
            'modified_by_user_idx' => $adminId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $companyId = DB::table('meter_company_table')->insertGetId([
            'company_code' => 'COMP',
            'company_name' => 'Characterization Company',
            'created_by_user_idx' => $adminId,
            'modified_by_user_idx' => $adminId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $divisionId = DB::table('meter_division_table')->insertGetId([
            'division_code' => 'DIV',
            'division_name' => 'Characterization Division',
            'created_by_user_idx' => $adminId,
            'modified_by_user_idx' => $adminId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $siteAId = DB::table('meter_site')->insertGetId([
            'division_idx' => $divisionId,
            'company_idx' => $companyId,
            'site_code' => 'SITEA',
            'created_by_user_idx' => $adminId,
            'modified_by_user_idx' => $adminId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $siteBId = DB::table('meter_site')->insertGetId([
            'division_idx' => $divisionId,
            'company_idx' => $companyId,
            'site_code' => 'SITEB',
            'created_by_user_idx' => $adminId,
            'modified_by_user_idx' => $adminId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $buildingAId = DB::table('meter_building_table')->insertGetId([
            'site_idx' => $siteAId,
            'building_code' => 'SITEA',
            'building_description' => 'Accessible Building',
            'cut_off' => 25,
            'created_by_user_idx' => $adminId,
            'modified_by_user_idx' => $adminId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $buildingBId = DB::table('meter_building_table')->insertGetId([
            'site_idx' => $siteBId,
            'building_code' => 'SITEB',
            'building_description' => 'Restricted Building',
            'cut_off' => 25,
            'created_by_user_idx' => $adminId,
            'modified_by_user_idx' => $adminId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('meter_site')->where('site_id', $siteAId)->update(['building_idx' => $buildingAId]);
        DB::table('meter_site')->where('site_id', $siteBId)->update(['building_idx' => $buildingBId]);

        $locationAId = DB::table('meter_location_table')->insertGetId([
            'site_idx' => $siteAId,
            'building_id' => $buildingAId,
            'location_code' => 'ER-A',
            'location_description' => 'Electrical Room A',
            'created_by_user_idx' => $adminId,
            'modified_by_user_idx' => $adminId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $configId = DB::table('meter_configuration_file')->insertGetId([
            'meter_model' => 'zmd402',
            'config_file' => 'zmd402.cfg',
            'created_by_user_idx' => $adminId,
            'modified_by_user_idx' => $adminId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $gatewayId = DB::table('meter_rtu')->insertGetId([
            'site_idx' => $siteAId,
            'location_idx' => $locationAId,
            'site_code' => 'SITEA',
            'gateway_sn' => 'GW-SN-001',
            'gateway_mac' => 'AA:BB:CC:DD:EE:01',
            'gateway_ip' => '10.0.0.10',
            'connection_type' => 'LAN',
            'gateway_description' => 'Fixture gateway',
            'update_rtu' => 0,
            'update_rtu_location' => 0,
            'created_by_user_idx' => $adminId,
            'modified_by_user_idx' => $adminId,
            'created_at' => now()->format('Y-m-d H:i:s'),
            'updated_at' => now()->format('Y-m-d H:i:s'),
        ]);

        $meterId = DB::table('meter_details')->insertGetId([
            'site_idx' => $siteAId,
            'rtu_idx' => $gatewayId,
            'location_idx' => $locationAId,
            'building_idx' => $buildingAId,
            'config_idx' => $configId,
            'site_code' => 'SITEA',
            'meter_name' => 'MTR-001',
            'meter_name_addressable' => 1,
            'meter_default_name' => '1',
            'meter_type' => 'Power',
            'meter_brand' => 'FixtureBrand',
            'meter_role' => 'Client Meter',
            'customer_name' => 'Tenant A',
            'meter_multiplier' => 1,
            'meter_status' => 'Active',
            'created_by_user_idx' => $adminId,
            'modified_by_user_idx' => $adminId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('meter_details')->insert([
            'site_idx' => $siteAId,
            'rtu_idx' => $gatewayId,
            'location_idx' => $locationAId,
            'building_idx' => $buildingAId,
            'config_idx' => $configId,
            'site_code' => 'SITEA',
            'meter_name' => 'MTR-INACTIVE',
            'meter_name_addressable' => 1,
            'meter_default_name' => '2',
            'meter_role' => 'Client Meter',
            'meter_multiplier' => 1,
            'meter_status' => 'Inactive',
            'created_by_user_idx' => $adminId,
            'modified_by_user_idx' => $adminId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('user_access_group')->insert([
            'user_idx' => $scopedUserId,
            'user_name' => 'scoped',
            'site_idx' => $siteAId,
            'created_by_user_idx' => $adminId,
            'updated_by_user_idx' => $adminId,
            'access_list_src' => 'CAMR',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->insertMeterData('SITEA', 'MTR-001', '2026-01-01 00:00:00', 100);
        $this->insertMeterData('SITEA', 'MTR-001', '2026-01-01 01:00:00', 125);

        return compact(
            'adminId',
            'scopedUserId',
            'companyId',
            'divisionId',
            'siteAId',
            'siteBId',
            'buildingAId',
            'buildingBId',
            'locationAId',
            'configId',
            'gatewayId',
            'meterId'
        );
    }

    protected function actingAsLegacyUser(int $userId): self
    {
        return $this->withSession(['loginID' => $userId]);
    }

    protected function insertMeterData(string $location, string $meterId, string $datetime, float $whTotal): void
    {
        DB::table('meter_data')->insert([
            'location' => $location,
            'meter_id' => $meterId,
            'datetime' => $datetime,
            'vrms_a' => 230,
            'vrms_b' => 231,
            'vrms_c' => 232,
            'irms_a' => 1,
            'irms_b' => 2,
            'irms_c' => 3,
            'freq' => 60,
            'pf' => 0.98,
            'watt' => 1000,
            'va' => 1100,
            'var' => 100,
            'wh_del' => $whTotal,
            'wh_rec' => 0,
            'wh_net' => $whTotal,
            'wh_total' => $whTotal,
            'varh_neg' => 0,
            'varh_pos' => 0,
            'varh_net' => 0,
            'varh_total' => 0,
            'vah_total' => $whTotal,
            'max_rec_kw_dmd' => 0,
            'max_del_kw_dmd' => 0,
            'max_pos_kvar_dmd' => 0,
            'max_neg_kvar_dmd' => 0,
            'v_ph_angle_a' => 0,
            'v_ph_angle_b' => 0,
            'v_ph_angle_c' => 0,
            'i_ph_angle_a' => 0,
            'i_ph_angle_b' => 0,
            'i_ph_angle_c' => 0,
            'mac_addr' => 'AA:BB:CC:DD:EE:01',
            'soft_rev' => '1.0',
            'relay_status' => 1,
        ]);
    }
}
