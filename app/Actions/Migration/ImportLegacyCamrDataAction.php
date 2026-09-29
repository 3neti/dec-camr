<?php

declare(strict_types=1);

namespace App\Actions\Migration;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use RuntimeException;

final class ImportLegacyCamrDataAction
{
    /** @var array<string, string> */
    private const TABLES = [
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
    ];

    /** @return array<string, int> */
    public function execute(string $source = 'legacy_mysql', bool $dryRun = false): array
    {
        $target = (string) config('database.default');

        if ($source === $target) {
            throw new RuntimeException('Source and target database connections must differ.');
        }

        if (config("database.connections.{$source}.database") !== ':memory:'
            && config("database.connections.{$source}.database") === config("database.connections.{$target}.database")
            && config("database.connections.{$source}.host") === config("database.connections.{$target}.host")) {
            throw new RuntimeException('Source and target databases must differ.');
        }

        $counts = [];

        foreach (self::TABLES as $sourceTable => $primaryKey) {
            $targetTable = $sourceTable === 'user_tb' ? 'users' : $sourceTable;

            if (! Schema::connection($source)->hasTable($sourceTable)
                || ! Schema::connection($target)->hasTable($targetTable)) {
                throw new RuntimeException("Required import table is missing: {$sourceTable} or {$targetTable}.");
            }

            if (! in_array($primaryKey, Schema::connection($source)->getColumnListing($sourceTable), true)) {
                throw new RuntimeException("Required source key is missing: {$sourceTable}.{$primaryKey}.");
            }

            $counts[$sourceTable] = DB::connection($source)->table($sourceTable)->count();

            if (! $dryRun && DB::connection($target)->table($targetTable)->exists()) {
                throw new RuntimeException("Target table must be empty before import: {$targetTable}.");
            }
        }

        if ($dryRun) {
            return $counts;
        }

        $disabledPassword = Hash::make(Str::random(64));

        foreach (self::TABLES as $sourceTable => $primaryKey) {
            $targetTable = $sourceTable === 'user_tb' ? 'users' : $sourceTable;
            $targetColumnSet = array_fill_keys(Schema::connection($target)->getColumnListing($targetTable), true);
            $copied = 0;

            DB::connection($source)->table($sourceTable)->orderBy($primaryKey)
                ->chunkById(500, function ($records) use ($sourceTable, $targetTable, $target, $targetColumnSet, $disabledPassword, &$copied): void {
                    $rows = [];

                    foreach ($records as $record) {
                        $sourceRow = (array) $record;
                        $rows[] = $sourceTable === 'user_tb'
                            ? $this->mapUser($sourceRow, $disabledPassword)
                            : array_intersect_key($sourceRow, $targetColumnSet);
                    }

                    DB::connection($target)->table($targetTable)->insert($rows);
                    $copied += count($rows);
                }, $primaryKey);

            if ($copied !== $counts[$sourceTable]) {
                throw new RuntimeException("Imported row count differs for {$sourceTable}.");
            }
        }

        return $counts;
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    public function mapUser(array $row, string $disabledPassword): array
    {
        $id = (int) $row['user_id'];
        $name = trim((string) ($row['user_name'] ?? ''));

        return [
            'id' => $id,
            'name' => $name !== '' ? $name : "legacy-user-{$id}",
            'email' => "legacy-{$id}@preview.invalid",
            'password' => $disabledPassword,
            'user_real_name' => $row['user_real_name'] ?? null,
            'user_job_title' => $row['user_job_title'] ?? null,
            'user_type' => $row['user_type'] ?? null,
            'user_access' => $row['user_access'] ?? 'Selected',
            'created_by_user_idx' => $row['created_by_user_idx'] ?? null,
            'modified_by_user_idx' => $row['modified_by_user_idx'] ?? null,
            'created_at' => $row['created_at'] ?? null,
            'updated_at' => $row['updated_at'] ?? null,
        ];
    }
}
