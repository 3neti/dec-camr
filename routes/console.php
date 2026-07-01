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

Artisan::command('camr:simulate {--profile=demo} {--duration=10m} {--speed=real} {--scenario=normal} {--dry-run} {--allow-production}', function (): int {
    if (app()->environment('production') && ! (bool) $this->option('allow-production')) {
        $this->error('camr:simulate is disabled in production. Use --allow-production if this is intentional.');

        return self::FAILURE;
    }

    $rawScenario = (string) $this->option('scenario');
    $normalizedScenario = strtolower(trim($rawScenario));
    $supportedScenarios = ['normal', 'offline-recovery', 'report-window'];
    $scenario = in_array($normalizedScenario, $supportedScenarios, true) ? $normalizedScenario : 'normal';
    if ($normalizedScenario !== $scenario) {
        $this->warn(sprintf('Unknown scenario "%s" received; using "%s".', $normalizedScenario, $scenario));
    }

    $profile = strtolower((string) $this->option('profile'));
    $duration = (string) $this->option('duration');
    $rawSpeed = (string) $this->option('speed');
    $normalizedSpeed = strtolower(trim($rawSpeed));
    $supportedSpeeds = ['slow', 'real', 'fast'];
    $speed = in_array($normalizedSpeed, $supportedSpeeds, true) ? $normalizedSpeed : 'real';
    if ($rawSpeed !== $speed) {
        $this->warn(sprintf('Unknown speed "%s" received; using "%s".', $rawSpeed, $speed));
    }
    $dryRun = (bool) $this->option('dry-run');
    $simulator = app(SimulateTelemetryAction::class);

    $summary = $simulator->simulate(
        durationInput: $duration,
        speed: $speed,
        scenario: $scenario,
        profile: $profile,
        dryRun: $dryRun
    );

    $this->info(sprintf('Simulation scenario: %s', $scenario));
    $this->info(sprintf('Profile: %s', $profile));
    $this->info(sprintf('Speed: %s', $speed));
    $this->info(sprintf('Dry run: %s', $dryRun ? 'yes' : 'no'));
    $this->info(sprintf('Rows inserted: %d', $summary['rows_inserted']));
    $this->info(sprintf('Meters covered: %d', $summary['meters_covered']));
    $this->info(sprintf('Gateways covered: %d', $summary['gateways_covered']));

    return self::SUCCESS;
})->purpose('Run deterministic telemetry simulation');
