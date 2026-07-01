<?php

use App\Actions\Ui\SimulateTelemetryAction;
use App\Models\Company;
use App\Models\Division;
use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Seeders\Profiles\AbstractProfileSeeder;
use Database\Seeders\SeedProfileManager;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;

Artisan::command('inspire', function (): void {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('camr:seed-profile {--profile=demo}', function (): int {
    $profile = strtolower((string) $this->option('profile'));
    $manager = new SeedProfileManager;
    $supportedProfiles = $manager->supportedProfiles();

    if (! in_array($profile, $supportedProfiles, true)) {
        $this->error(sprintf('Unsupported profile: %s', $profile));
        $this->info(sprintf('Supported profiles: %s', implode(', ', $supportedProfiles)));

        return self::FAILURE;
    }

    $admin = User::query()->firstOrCreate(
        ['email' => 'admin@demo.local'],
        [
            'name' => 'admin',
            'password' => Hash::make(AbstractProfileSeeder::DEFAULT_PASSWORD),
            'user_real_name' => 'Demo Seed Administrator',
            'user_job_title' => 'Platform Administrator',
            'user_type' => 'Admin',
            'user_access' => 'ALL',
            'email_verified_at' => CarbonImmutable::now(),
        ]
    );

    $company = Company::query()->firstOrCreate(
        ['company_name' => 'Characterization Company'],
        [
            'company_code' => 'COMP001',
            'created_by_user_idx' => $admin->id,
            'modified_by_user_idx' => $admin->id,
        ]
    );

    $division = Division::query()->firstOrCreate(
        ['division_code' => 'DIV001'],
        [
            'division_name' => 'Characterization Division',
            'created_by_user_idx' => $admin->id,
            'modified_by_user_idx' => $admin->id,
        ]
    );

    $this->info(sprintf('Seeding CAMR UI profile: %s', $profile));
    $manager->seed($profile, $admin, $division, $company);
    $this->info('Profile seed complete.');

    return self::SUCCESS;
})->purpose('Seed a deterministic CAMR UI foundation profile');

Artisan::command('camr:simulate {--profile=demo} {--duration=10m} {--speed=real} {--scenario=normal} {--deterministic=1} {--dry-run} {--allow-production}', function (): int {
    if (app()->environment('production') && ! (bool) $this->option('allow-production')) {
        $this->error('camr:simulate is disabled in production. Use --allow-production if this is intentional.');

        return self::FAILURE;
    }

    $supportedProfiles = ['minimal', 'demo', 'heavy'];
    $supportedScenarios = ['normal', 'offline-recovery', 'report-window'];
    $supportedSpeeds = ['slow', 'real', 'fast'];

    $profile = strtolower(trim((string) $this->option('profile')));
    if (! in_array($profile, $supportedProfiles, true)) {
        $this->error(sprintf('Unsupported profile: %s', $profile));
        $this->info(sprintf('Supported profiles: %s', implode(', ', $supportedProfiles)));

        return self::FAILURE;
    }

    $rawScenario = (string) $this->option('scenario');
    $normalizedScenario = strtolower(trim($rawScenario));
    if (! in_array($normalizedScenario, $supportedScenarios, true)) {
        $this->error(sprintf('Unsupported scenario: %s', $rawScenario));
        $this->info(sprintf('Supported scenarios: %s', implode(', ', $supportedScenarios)));

        return self::FAILURE;
    }

    $scenario = $normalizedScenario;
    $duration = (string) $this->option('duration');

    $rawSpeed = (string) $this->option('speed');
    $speed = strtolower(trim($rawSpeed));
    if (! in_array($speed, $supportedSpeeds, true)) {
        $this->error(sprintf('Unsupported speed: %s', $rawSpeed));
        $this->info(sprintf('Supported speeds: %s', implode(', ', $supportedSpeeds)));

        return self::FAILURE;
    }
    $dryRun = (bool) $this->option('dry-run');
    $deterministic = (string) $this->option('deterministic');
    if (! in_array(strtolower($deterministic), ['0', '1', 'true', 'false'], true)) {
        $this->error(sprintf('Unsupported --deterministic value: %s', $deterministic));
        $this->info('Supported values: 0, 1, true, false');

        return self::FAILURE;
    }

    $deterministicMode = in_array(strtolower($deterministic), ['1', 'true'], true);
    $simulator = app(SimulateTelemetryAction::class);

    $summary = $simulator->simulate(
        durationInput: $duration,
        speed: $speed,
        scenario: $scenario,
        profile: $profile,
        dryRun: $dryRun,
        deterministic: $deterministicMode
    );

    $this->info(sprintf('Simulation scenario: %s', $scenario));
    $this->info(sprintf('Profile: %s', $profile));
    $this->info(sprintf('Speed: %s', $speed));
    $this->info(sprintf('Deterministic mode: %s', $deterministicMode ? 'enabled' : 'disabled'));
    $this->info(sprintf('Dry run: %s', $dryRun ? 'yes' : 'no'));
    $this->info(sprintf('Rows inserted: %d', $summary['rows_inserted']));
    $this->info(sprintf('Meters covered: %d', $summary['meters_covered']));
    $this->info(sprintf('Gateways covered: %d', $summary['gateways_covered']));

    return self::SUCCESS;
})->purpose('Run deterministic telemetry simulation');
