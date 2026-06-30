<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\ConfigurationFile;
use App\Models\Division;
use App\Models\Gateway;
use App\Models\Meter;
use App\Models\MeterLocation;
use App\Models\Site;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use ZipArchive;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * @var array<string, array<int, array<string, mixed>>>
     */
    private array $legacyRowsByTable = [];

    /**
     * @var array<string, Site>
     */
    private array $legacySiteCache = [];

    /**
     * @var array<string, MeterLocation>
     */
    private array $legacyLocationCache = [];

    /**
     * @var array<string, Gateway>
     */
    private array $legacyGatewayCache = [];

    /**
     * @var array<string, ConfigurationFile>
     */
    private array $legacyConfigCache = [];

    public function run(): void
    {
        $admin = User::updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'admin',
                'password' => Hash::make('123456'),
            ]
        );

        $company = Company::updateOrCreate(
            ['company_name' => 'Characterization Company'],
            [
                'company_code' => 'COMP001',
                'created_by_user_idx' => $admin->id,
                'modified_by_user_idx' => $admin->id,
            ]
        );

        $division = Division::updateOrCreate(
            ['division_name' => 'Characterization Division'],
            [
                'division_code' => 'DIV001',
                'created_by_user_idx' => $admin->id,
                'modified_by_user_idx' => $admin->id,
            ]
        );

        Site::updateOrCreate(
            ['site_code' => 'SITE001'],
            [
                'division_idx' => $division->division_id,
                'company_idx' => $company->company_id,
                'building_idx' => 1,
                'building_description' => 'Characterization Building',
                'created_by_user_idx' => $admin->id,
                'modified_by_user_idx' => $admin->id,
                'last_log_update' => now(),
            ]
        );

        ConfigurationFile::updateOrCreate(
            ['config_file' => 'zmd402_serial_2400.cfg'],
            [
                'meter_model' => 'zmd402_serial_2400',
                'created_by_user_idx' => $admin->id,
            ]
        );

        ConfigurationFile::updateOrCreate(
            ['config_file' => 'zmd402_serial.cfg'],
            [
                'meter_model' => 'zmd402_serial',
                'created_by_user_idx' => $admin->id,
            ]
        );

        ConfigurationFile::updateOrCreate(
            ['config_file' => 's4e.cfg'],
            [
                'meter_model' => 'S4E',
                'created_by_user_idx' => $admin->id,
            ]
        );

        $this->seedLegacyFixtureRows($admin, $division, $company);
    }

    private function seedLegacyFixtureRows(User $admin, Division $division, Company $company): void
    {
        $meterModelConfigRows = $this->readLegacyBackupRows('meter_model_config');
        $meterSiteRows = $this->readLegacyBackupRows('meter_site');
        $meterGatewayRows = $this->readLegacyBackupRows('meter_rtu');
        $meterRows = $this->readLegacyBackupRows('meter_details');

        if (empty($meterModelConfigRows) && empty($meterSiteRows) && empty($meterGatewayRows) && empty($meterRows)) {
            return;
        }

        $this->seedLegacyConfigurationFiles($admin, $meterModelConfigRows);
        $this->seedLegacySites($admin, $division, $company, $meterSiteRows);
        $this->seedLegacyGateways($admin, $meterGatewayRows);
        $this->seedLegacyMeters($admin, $meterRows);

        $this->seedLegacyFallback($admin, $division, $company);
    }

    private function seedLegacyFallback(User $admin, Division $division, Company $company): void
    {
        $legacySite = Site::query()->where('site_code', 'SMSL')->first();

        if ($legacySite === null) {
            $legacySite = Site::updateOrCreate(
                ['site_code' => 'SMSL'],
                [
                    'division_idx' => $division->division_id,
                    'company_idx' => $company->company_id,
                    'building_idx' => 1,
                    'building_description' => 'SM San Lazaro',
                    'created_by_user_idx' => $admin->id,
                    'modified_by_user_idx' => $admin->id,
                ]
            );
        }

        $legacyLocation = MeterLocation::query()
            ->firstOrCreate(
                [
                    'site_idx' => $legacySite->site_id,
                    'location_code' => 'EE ROOM 18',
                ],
                [
                    'building_id' => 0,
                    'location_description' => 'EE ROOM 18',
                    'created_by_user_idx' => $admin->id,
                    'modified_by_user_idx' => $admin->id,
                ]
            );

        Gateway::query()->firstOrCreate(
            [
                'site_idx' => $legacySite->site_id,
                'gateway_sn' => '20191226',
            ],
            [
                'location_idx' => $legacyLocation->location_id,
                'site_code' => 'SMSL',
                'gateway_mac' => '4e:b8:61:88:74:4f',
                'gateway_ip' => '10.93.189.130',
                'connection_type' => 'LAN',
                'created_by_user_idx' => $admin->id,
                'modified_by_user_idx' => $admin->id,
                'ip_netmask' => null,
                'ip_gateway' => null,
                'rtu_server_ip' => null,
                'gateway_description' => null,
                'idf_number' => 'NA',
                'switch_name' => 'NA',
                'idf_port' => 'NA',
            ]
        );

        Meter::query()->firstOrCreate(
            [
                'site_idx' => $legacySite->site_id,
                'rtu_idx' => Gateway::query()->where('gateway_sn', '20191226')->value('rtu_id') ?? 0,
                'meter_name' => '030011100348',
            ],
            [
                'location_idx' => $legacyLocation->location_id,
                'building_idx' => 0,
                'config_idx' => ConfigurationFile::query()->first()->config_id ?? 1,
                'site_code' => 'SMSL',
                'meter_name_addressable' => 1,
                'meter_load_profile' => 'NO',
                'meter_default_name' => '030011100348',
                'meter_type' => null,
                'meter_brand' => null,
                'meter_role' => 'Client Meter',
                'meter_remarks' => null,
                'customer_name' => 'LAVISH LASHES STUDIO',
                'meter_multiplier' => 1,
                'meter_status' => 'ACTIVE',
                'last_log_update' => now()->toDateTimeString(),
                'soft_rev' => '2.10',
                'created_by_user_idx' => $admin->id,
                'modified_by_user_idx' => $admin->id,
            ]
        );
    }

    private function seedLegacyConfigurationFiles(User $admin, array $rows): void
    {
        foreach ($rows as $row) {
            $configFile = $this->legacyValue($row['config_file'] ?? null);
            $meterModel = $this->legacyValue($row['meter_model'] ?? null);

            if ($configFile === null || $configFile === '') {
                continue;
            }

            $config = ConfigurationFile::query()->updateOrCreate(
                ['config_file' => $configFile],
                [
                    'meter_model' => $meterModel ?? $configFile,
                    'created_by_user_idx' => $admin->id,
                ]
            );

            $this->legacyConfigCache[$configFile] = $config;
            $this->legacyConfigCache[strtolower($configFile)] = $config;
        }
    }

    private function seedLegacySites(User $admin, Division $division, Company $company, array $rows): void
    {
        foreach ($rows as $row) {
            $siteCode = $this->legacyValue($row['site_code'] ?? null);

            if ($siteCode === null || $siteCode === '') {
                continue;
            }

            $siteCode = strtoupper((string) $siteCode);
            $site = Site::query()->updateOrCreate(
                ['site_code' => $siteCode],
                [
                    'division_idx' => $division->division_id,
                    'company_idx' => $company->company_id,
                    'building_idx' => 0,
                    'building_description' => $this->legacyValue($row['site_name'] ?? $siteCode),
                    'created_by_user_idx' => $admin->id,
                    'modified_by_user_idx' => $admin->id,
                    'last_log_update' => $this->legacyValue($row['last_log_update']) ?? now()->toDateTimeString(),
                ]
            );

            $this->legacySiteCache[$siteCode] = $site;
            $this->legacySiteCache[(string) $row['id']] = $site;
        }
    }

    private function seedLegacyGateways(User $admin, array $rows): void
    {
        foreach ($rows as $row) {
            $siteCode = strtoupper((string) $this->legacyValue($row['rtu_site_name'] ?? null));
            $gatewaySn = $this->legacyValue($row['rtu_sn_number'] ?? null);

            if ($gatewaySn === null || $gatewaySn === '' || $siteCode === '') {
                continue;
            }

            $site = $this->legacySiteCache[$siteCode] ?? null;

            if ($site === null) {
                continue;
            }

            $location = $this->seedLegacyLocation(
                $admin,
                $site,
                $this->legacyValue($row['rtu_physical_location'] ?? null)
            );

            $gateway = Gateway::query()->updateOrCreate(
                [
                    'site_idx' => $site->site_id,
                    'gateway_sn' => (string) $gatewaySn,
                ],
                [
                    'location_idx' => $location?->location_id,
                    'site_code' => $siteCode,
                    'gateway_mac' => (string) $this->legacyValue($row['mac_addr'] ?? ''),
                    'gateway_ip' => (string) $this->legacyValue($row['phone_no_or_ip_address'] ?? ''),
                    'connection_type' => $this->legacyValue($row['connection_type']) ?? 'LAN',
                    'ip_netmask' => $this->legacyValue($row['ip_netmask'] ?? null),
                    'ip_gateway' => $this->legacyValue($row['ip_gateway'] ?? null),
                    'rtu_server_ip' => $this->legacyValue($row['rtu_server_ip'] ?? null),
                    'gateway_description' => $this->legacyValue($row['gateway_description'] ?? null),
                    'update_rtu' => (int) $this->legacyNumber($row['update_rtu'] ?? 0),
                    'update_rtu_location' => (int) $this->legacyNumber($row['update_rtu_location'] ?? 0),
                    'update_rtu_ssh' => (int) $this->legacyNumber($row['update_rtu_ssh'] ?? 0),
                    'update_rtu_force_lp' => (int) $this->legacyNumber($row['update_rtu_force_lp'] ?? 0),
                    'idf_number' => $this->legacyValue($row['idf_number'] ?? null) ?? 'NA',
                    'switch_name' => $this->legacyValue($row['switch_name'] ?? null),
                    'idf_port' => $this->legacyValue($row['idf_port'] ?? null),
                    'created_by_user_idx' => $admin->id,
                    'modified_by_user_idx' => $admin->id,
                ]
            );

            $this->legacyGatewayCache[(string) $gatewaySn] = $gateway;
        }
    }

    private function seedLegacyMeters(User $admin, array $rows): void
    {
        foreach ($rows as $row) {
            $siteCode = strtoupper((string) $this->legacyValue($row['meter_site_name'] ?? null));
            $gatewaySn = $this->legacyValue($row['rtu_sn_number'] ?? null);
            $meterName = $this->legacyValue($row['meter_name'] ?? null);

            if ($siteCode === '' || $meterName === null || $meterName === '') {
                continue;
            }

            $site = $this->legacySiteCache[$siteCode] ?? null;
            $gateway = $this->legacyGatewayCache[(string) $gatewaySn] ?? null;

            if ($site === null || $gateway === null) {
                continue;
            }

            $location = $this->seedLegacyLocation(
                $admin,
                $site,
                $this->legacyValue($row['physical_location'] ?? null),
                true
            );

            $configIdx = null;
            $configFile = $this->legacyValue($row['meter_config_file'] ?? null);
            $meterModel = $this->legacyValue($row['meter_model'] ?? null);

            if ($configFile !== null && $configFile !== '') {
                $configIdx = $this->resolveConfigIdx($admin, $configFile, $meterModel);
            } elseif ($meterModel !== null && $meterModel !== '') {
                $configIdx = $this->resolveConfigIdx($admin, $meterModel.'.cfg', $meterModel);
            }

            if ($configIdx === null) {
                $configIdx = ConfigurationFile::query()->value('config_id');
            }

            Meter::query()->updateOrCreate(
                [
                    'site_idx' => $site->site_id,
                    'rtu_idx' => $gateway->rtu_id,
                    'meter_name' => (string) $meterName,
                ],
                [
                    'location_idx' => $location?->location_id ?? 0,
                    'building_idx' => 0,
                    'config_idx' => $configIdx ?? 1,
                    'site_code' => $siteCode,
                    'meter_name_addressable' => (int) $this->legacyNumber($row['meter_name_addressable'] ?? 1, 1),
                    'meter_load_profile' => $this->legacyValue($row['meter_load_profile'] ?? null) ?? 'NO',
                    'meter_default_name' => $this->legacyValue($row['meter_default_name'] ?? $meterName),
                    'meter_type' => $this->legacyValue($row['meter_type'] ?? null),
                    'meter_brand' => $this->legacyValue($row['meter_model'] ?? null),
                    'meter_role' => $this->legacyValue($row['meter_role'] ?? 'Client Meter'),
                    'meter_remarks' => $this->legacyValue($row['measurement_sequence'] ?? null),
                    'customer_name' => $this->legacyValue($row['customer_name'] ?? null),
                    'meter_multiplier' => $this->legacyFloat($row['meter_multiplier'] ?? 1, 1.0),
                    'meter_status' => $this->legacyValue($row['meter_status'] ?? 'ACTIVE') ?? 'ACTIVE',
                    'last_log_update' => $this->legacyValue($row['last_log_update'] ?? now()->toDateTimeString()),
                    'soft_rev' => $this->legacyValue($row['soft_rev'] ?? '0'),
                    'created_by_user_idx' => $admin->id,
                    'modified_by_user_idx' => $admin->id,
                ]
            );
        }
    }

    private function resolveConfigIdx(User $admin, string $configFile, ?string $meterModel = null): ?int
    {
        if (($this->legacyConfigCache[$configFile] ?? null) !== null) {
            return $this->legacyConfigCache[$configFile]->config_id;
        }

        $model = ConfigurationFile::query()->firstWhere('config_file', $configFile);
        if ($model !== null) {
            $this->legacyConfigCache[$configFile] = $model;

            return $model->config_id;
        }

        $created = ConfigurationFile::query()->create([
            'meter_model' => $meterModel ?? $configFile,
            'config_file' => $configFile,
            'created_by_user_idx' => $admin->id,
        ]);

        $this->legacyConfigCache[$configFile] = $created;

        return $created->config_id;
    }

    private function seedLegacyLocation(User $admin, Site $site, ?string $code = null, bool $createIfMissing = false): ?MeterLocation
    {
        if ($code === null || $code === '') {
            return null;
        }

        if (! $createIfMissing) {
            $cacheKey = $site->site_id.':'.$code;

            return $this->legacyLocationCache[$cacheKey] ?? null;
        }

        $cacheKey = $site->site_id.':'.$code;
        $location = $this->legacyLocationCache[$cacheKey]
            ?? MeterLocation::query()->firstOrCreate(
                [
                    'site_idx' => $site->site_id,
                    'location_code' => $code,
                ],
                [
                    'building_id' => 0,
                    'location_description' => $code,
                    'created_by_user_idx' => $admin->id,
                    'modified_by_user_idx' => $admin->id,
                ]
            );

        $this->legacyLocationCache[$cacheKey] = $location;

        return $location;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function readLegacyBackupRows(string $tableName): array
    {
        if (isset($this->legacyRowsByTable[$tableName])) {
            return $this->legacyRowsByTable[$tableName];
        }

        $backupPath = env('CAMR_LEGACY_METER_READING_BACKUP', '/Users/rli/Documents/DEC/backup/meter_reading/meter_reading.sql.zip');

        if (! is_file($backupPath)) {
            return $this->legacyRowsByTable[$tableName] = [];
        }

        $zip = new ZipArchive;
        if ($zip->open($backupPath) !== true) {
            return $this->legacyRowsByTable[$tableName] = [];
        }

        $sqlContent = null;
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);

            if ($name === false || ! str_ends_with(strtolower((string) $name), '.sql')) {
                continue;
            }

            $sqlContent = $zip->getFromName($name);
            break;
        }

        $zip->close();

        if ($sqlContent === false || $sqlContent === null) {
            return $this->legacyRowsByTable[$tableName] = [];
        }

        $pattern = '/INSERT INTO `'.preg_quote($tableName, '/').'`\s*\(([^)]+)\)\s*VALUES\s*(.+?);/s';
        if (! preg_match_all($pattern, $sqlContent, $insertMatches, PREG_SET_ORDER)) {
            return $this->legacyRowsByTable[$tableName] = [];
        }

        $rows = [];

        foreach ($insertMatches as $insertMatch) {
            if (! isset($insertMatch[1], $insertMatch[2])) {
                continue;
            }

            $headerRaw = $insertMatch[1];
            $valuesRaw = $insertMatch[2];

            preg_match_all('/`([^`]+)`/', $headerRaw, $headerMatches);
            $headers = $headerMatches[1] ?? [];

            foreach ($this->splitInsertRows($valuesRaw) as $row) {
                $values = $this->parseRowValues($row);
                if (empty($values)) {
                    continue;
                }

                $rowData = [];
                foreach ($headers as $index => $header) {
                    $rowData[$header] = $values[$index] ?? null;
                }

                $rows[] = $rowData;
            }
        }

        $this->legacyRowsByTable[$tableName] = $rows;

        return $rows;
    }

    /**
     * @return array<int, string>
     */
    private function splitInsertRows(string $valuesRaw): array
    {
        $rows = [];
        $cursor = 0;
        $inString = false;
        $escape = false;
        $depth = 0;
        $current = '';

        $length = strlen($valuesRaw);

        for ($cursor = 0; $cursor < $length; $cursor++) {
            $char = $valuesRaw[$cursor];

            if (! $inString && $char === '(') {
                if ($depth === 0) {
                    $current = '';
                    $depth = 1;

                    continue;
                }

                $current .= '(';
                $depth++;

                continue;
            }

            if (! $inString && $depth === 0) {
                continue;
            }

            if ($inString && $escape) {
                $current .= $char;
                $escape = false;

                continue;
            }

            if ($char === '\\' && $inString) {
                $escape = true;

                continue;
            }

            if (! $inString && $char === ')') {
                $depth--;

                if ($depth === 0) {
                    $rows[] = trim($current);
                    $current = '';

                    continue;
                }

                $current .= ')';

                continue;
            }

            if ($char === "'") {
                $inString = ! $inString;

                continue;
            }

            if ($inString && $char === '\\') {
                $escape = true;

                continue;
            }

            $current .= $char;

            if ($inString && ! $escape && $char === '(') {
                $depth++;
            }
        }

        return $rows;
    }

    /**
     * @return array<int, mixed>
     */
    private function parseRowValues(string $row): array
    {
        $values = [];
        $current = '';
        $inString = false;
        $escape = false;

        $length = strlen($row);
        for ($i = 0; $i < $length; $i++) {
            $char = $row[$i];

            if (! $inString && $char === "'") {
                $inString = true;

                continue;
            }

            if ($inString) {
                if ($escape) {
                    $current .= $char;
                    $escape = false;

                    continue;
                }

                if ($char === '\\') {
                    $escape = true;

                    continue;
                }

                if ($char === "'") {
                    $inString = false;

                    continue;
                }

                $current .= $char;

                continue;
            }

            if ($char === ',') {
                $values[] = $this->normalizeLegacyValue($current);
                $current = '';

                continue;
            }

            if (strlen($current) > 0 || ! ctype_space($char)) {
                $current .= $char;
            }
        }

        if (trim($current) !== '') {
            $values[] = $this->normalizeLegacyValue($current);
        }

        return $values;
    }

    private function legacyValue(mixed $value): mixed
    {
        return $this->normalizeLegacyValue($value);
    }

    private function normalizeLegacyValue(mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }

        $string = trim((string) $value);

        if ($string === '') {
            return null;
        }

        if (strcasecmp($string, 'NULL') === 0) {
            return null;
        }

        return $string;
    }

    private function legacyNumber(mixed $value, int $default = 0): int
    {
        $normalized = $this->normalizeLegacyValue($value);

        if (! is_numeric((string) $normalized)) {
            return $default;
        }

        $rawValue = (string) $normalized;

        if (str_contains($rawValue, '.')) {
            return (int) (float) $rawValue;
        }

        return (int) $rawValue;
    }

    private function legacyFloat(mixed $value, float $default = 0): float
    {
        $normalized = $this->normalizeLegacyValue($value);

        if (! is_numeric((string) $normalized)) {
            return $default;
        }

        $rawValue = (string) $normalized;

        return (float) $rawValue;
    }
}
