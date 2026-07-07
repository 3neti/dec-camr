<?php

use App\Actions\Rtu\ReplayTelemetryFileAction;
use App\Actions\Ui\SimulateTelemetryAction;
use App\Models\Company;
use App\Models\Division;
use App\Models\User;
use App\Support\Ui\Scenarios\OperatorScenarioRegistry;
use App\Support\Ui\Scenarios\OperatorScenarioRunner;
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

Artisan::command('camr:scenario {scenario?} {--list} {--dry-run} {--no-seed} {--no-simulate} {--anchor=} {--allow-production}', function (): int {
    $registry = new OperatorScenarioRegistry;

    if ((bool) $this->option('list')) {
        $definitions = $registry->all();
        $this->info('Available CAMR lifecycle scenarios:');
        foreach ($definitions as $definition) {
            $this->line(sprintf('- %s (%s)', $definition->key, $definition->title));
            $this->line(sprintf('  persona=%s, seed=%s, simulator=%s', $definition->persona, $definition->seedProfile, $definition->simulatorScenario));
        }

        return self::SUCCESS;
    }

    $scenario = $this->argument('scenario');
    if (! is_string($scenario) || trim($scenario) === '') {
        $this->error('Scenario key is required unless --list is used.');

        return self::FAILURE;
    }

    $runner = new OperatorScenarioRunner(
        registry: $registry,
        simulator: app(SimulateTelemetryAction::class),
    );

    try {
        $result = $runner->run(
            scenarioKey: $scenario,
            dryRun: (bool) $this->option('dry-run'),
            runSeed: ! (bool) $this->option('no-seed'),
            runSimulation: ! (bool) $this->option('no-simulate'),
            anchor: $this->option('anchor') === null ? null : (string) $this->option('anchor'),
            allowProduction: (bool) $this->option('allow-production'),
        );
    } catch (InvalidArgumentException $exception) {
        $this->error($exception->getMessage());
        if (str_contains($exception->getMessage(), 'Unknown scenario:')) {
            $this->line('Available scenarios: '.implode(', ', array_keys($registry->all())));
        }

        return self::FAILURE;
    }

    $definition = $result['scenario'];
    $run = $result['run'];

    $this->info(sprintf('Scenario: %s', $definition['key']));
    $this->line(sprintf('Title: %s', $definition['title']));
    $this->line(sprintf('Persona: %s', $definition['persona']));
    $this->line(sprintf('Seed profile: %s', $definition['seed_profile']));
    $this->line(sprintf('Simulator: %s / %s @ %s', $definition['simulator_scenario'], $definition['simulator_speed'], $definition['simulator_duration']));
    if ($definition['deterministic_anchor'] !== null) {
        $this->line(sprintf('Scenario default anchor: %s', $definition['deterministic_anchor']));
    }
    if ($run['dry_run']) {
        $this->line('Mode: dry-run');
    }

    $this->line(sprintf('Seed status: %s (profile=%s)', $run['seed']['status'], $run['seed']['profile']));
    $this->line(sprintf('Simulation status: %s (scenario=%s)', $run['simulate']['status'], $run['simulate']['scenario']));

    if (is_string($this->option('anchor')) && $this->option('anchor') !== '') {
        $this->line(sprintf('Anchor: %s', (string) $this->option('anchor')));
    } elseif (($definition['deterministic_anchor'] ?? null) !== null) {
        $this->line(sprintf('Anchor: %s', (string) $definition['deterministic_anchor']));
    }

    $this->line(sprintf('Suggested next step: %s', $result['metadata']['suggested_test_filter'] ?? 'none'));

    return self::SUCCESS;
})->purpose('Run a lifecycle scenario (seed + simulator orchestration)');

Artisan::command('camr:simulate {--profile=demo} {--duration=10m} {--speed=real} {--scenario=normal} {--deterministic=1} {--anchor=} {--dry-run} {--allow-production}', function (): int {
    if (app()->environment('production') && ! (bool) $this->option('allow-production')) {
        $this->error('camr:simulate is disabled in production. Use --allow-production if this is intentional.');

        return self::FAILURE;
    }

    $supportedProfiles = ['minimal', 'demo', 'heavy'];
    $supportedScenarios = ['normal', 'offline-recovery', 'report-window', 'analytics-demo'];
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

    $anchor = $this->option('anchor');

    try {
        $summary = $simulator->simulate(
            durationInput: $duration,
            speed: $speed,
            scenario: $scenario,
            profile: $profile,
            dryRun: $dryRun,
            deterministic: $deterministicMode,
            anchor: $anchor === null ? null : (string) $anchor
        );
    } catch (InvalidArgumentException $exception) {
        $this->error($exception->getMessage());

        return self::FAILURE;
    }

    $this->info(sprintf('Simulation scenario: %s', $scenario));
    $this->info(sprintf('Profile: %s', $profile));
    $this->info(sprintf('Speed: %s', $speed));
    $this->info(sprintf('Deterministic mode: %s', $deterministicMode ? 'enabled' : 'disabled'));
    if ($deterministicMode) {
        $this->info(sprintf('Anchor: %s', $anchor ?: '2026-07-01 08:00:00'));
    }
    $this->info(sprintf('Dry run: %s', $dryRun ? 'yes' : 'no'));
    $this->info(sprintf('Rows inserted: %d', $summary['rows_inserted']));
    $this->info(sprintf('Meters covered: %d', $summary['meters_covered']));
    $this->info(sprintf('Gateways covered: %d', $summary['gateways_covered']));

    return self::SUCCESS;
})->purpose('Run deterministic telemetry simulation');

Artisan::command('camr:replay-telemetry {--file=} {--speed=real} {--anchor=} {--dry-run} {--loop} {--allow-production}', function (): int {
    if (app()->environment('production') && ! (bool) $this->option('allow-production')) {
        $this->error('camr:replay-telemetry is disabled in production. Use --allow-production if this is intentional.');

        return self::FAILURE;
    }

    $file = $this->option('file');
    if (! is_string($file) || trim($file) === '') {
        $this->error('The --file option is required.');

        return self::FAILURE;
    }

    try {
        $summary = app(ReplayTelemetryFileAction::class)->replay(
            file: $file,
            dryRun: (bool) $this->option('dry-run'),
            speed: (string) $this->option('speed'),
            anchor: $this->option('anchor') === null ? null : (string) $this->option('anchor'),
            loop: (bool) $this->option('loop'),
        );
    } catch (InvalidArgumentException $exception) {
        $this->error($exception->getMessage());

        return self::FAILURE;
    }

    $this->info('Telemetry replay complete.');
    $this->line(sprintf('File: %s', $file));
    $this->line(sprintf('Speed: %s', (string) $this->option('speed')));
    $this->line(sprintf('Dry run: %s', (bool) $this->option('dry-run') ? 'yes' : 'no'));
    if (is_string($this->option('anchor')) && $this->option('anchor') !== '') {
        $this->line(sprintf('Anchor: %s', (string) $this->option('anchor')));
    }
    $this->line(sprintf('Rows seen: %d', $summary['rows_seen']));
    $this->line(sprintf('Rows replayed: %d', $summary['rows_replayed']));
    $this->line(sprintf('Rows saved: %d', $summary['rows_saved']));
    $this->line(sprintf('Rows failed: %d', $summary['rows_failed']));

    return self::SUCCESS;
})->purpose('Replay gateway-shaped telemetry from a CSV file through the live RTU ingest path');
