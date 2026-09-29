<?php

namespace App\Console\Commands;

use App\Actions\Migration\ImportLegacyCamrDataAction;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Throwable;

#[Signature('camr:import-legacy {--source=legacy_mysql} {--dry-run}')]
#[Description('Import a current legacy CAMR database into an empty Laravel 13 database')]
final class ImportLegacyCamrData extends Command
{
    public function handle(ImportLegacyCamrDataAction $import): int
    {
        try {
            $counts = $import->execute(
                (string) $this->option('source'),
                (bool) $this->option('dry-run'),
            );
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->table(['Source table', 'Rows'], collect($counts)->map(
            fn (int $count, string $table): array => [$table, $count],
        )->values()->all());

        return self::SUCCESS;
    }
}
