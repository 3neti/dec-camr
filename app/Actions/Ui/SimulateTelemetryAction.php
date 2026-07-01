<?php

declare(strict_types=1);

namespace App\Actions\Ui;

use App\Models\Gateway;
use App\Models\Meter;
use App\Models\MeterData;
use App\Models\Site;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use InvalidArgumentException;

final class SimulateTelemetryAction
{
    private const DETERMINISTIC_DEFAULT_ANCHOR = '2026-07-01 08:00:00';

    private const PROFILE_MINIMAL = 'minimal';

    private const PROFILE_DEMO = 'demo';

    private const PROFILE_HEAVY = 'heavy';

    private const SUPPORTED_PROFILES = [
        self::PROFILE_MINIMAL,
        self::PROFILE_DEMO,
        self::PROFILE_HEAVY,
    ];

    /**
     * @var array<int, string>
     */
    private const SUPPORTED_SCENARIOS = [
        'normal',
        'offline-recovery',
        'report-window',
    ];

    private const SPEED_TO_MINUTES = [
        'slow' => 30,
        'real' => 15,
        'fast' => 5,
    ];

    /**
     * @return array<string, int>
     */
    public function simulate(
        string $durationInput,
        string $speed = 'real',
        string $scenario = 'normal',
        string $profile = 'demo',
        bool $dryRun = false,
        bool $deterministic = true,
        ?string $anchor = null,
    ): array {
        $scenario = $this->validateScenario($scenario);
        $profile = $this->validateProfile($profile);
        $speed = $this->validateSpeed($speed);
        $durationMinutes = max(5, $this->parseDurationMinutes($durationInput));
        $stepMinutes = self::SPEED_TO_MINUTES[$speed];
        $steps = (int) max(1, (int) floor($durationMinutes / max(1, $stepMinutes)));
        $meters = $this->metersForProfile($profile);

        if ($meters->isEmpty()) {
            return [
                'rows_inserted' => 0,
                'meters_covered' => 0,
                'gateways_covered' => 0,
            ];
        }

        if ($durationMinutes % $stepMinutes === 0) {
            $steps = max(1, (int) ($durationMinutes / $stepMinutes));
        }

        $gatewayGroups = $this->gatewayGroups($meters->pluck('rtu_idx')->unique()->values());
        $offlineRecoveryGateways = $gatewayGroups['offline_recovery_gateways'];
        $pendingFlagGateways = $gatewayGroups['pending_flag_gateways'];

        $rows = [];
        $meterRowsInserted = 0;
        $updatedMeterIds = [];
        $updatedGatewayIds = [];
        $updatedSiteIds = [];

        $timeAnchor = $deterministic
            ? $this->resolveDeterministicAnchor($anchor)
            : CarbonImmutable::now();
        $startTime = $timeAnchor->subMinutes($durationMinutes)->startOfMinute();
        $midpointStep = (int) max(1, intdiv($steps, 2));
        $endTime = $startTime;

        for ($step = 1; $step <= $steps; $step++) {
            $timestamp = $startTime->addMinutes($step * $stepMinutes);
            $endTime = $timestamp;

            $this->applyScenarioGatewayState(
                $step,
                $steps,
                $offlineRecoveryGateways,
                $pendingFlagGateways,
                $scenario,
                $dryRun,
            );

            $meterList = $meters->values();

            foreach ($meterList as $index => $meter) {
                if ($this->shouldSkipTelemetry($scenario, $step, $steps, $meter, $offlineRecoveryGateways, $midpointStep)) {
                    continue;
                }

                $rows[] = $this->telemetryRow($meter, $index, $step, $timestamp);
                $updatedMeterIds[] = (int) $meter->meter_id;
                $updatedGatewayIds[] = (int) $meter->rtu_idx;
                $updatedSiteIds[] = (int) $meter->site_idx;
            }

            if (! $dryRun && $rows !== []) {
                MeterData::query()->insert($rows);
                $meterRowsInserted += count($rows);
                $rows = [];
            }
        }

        if (! $dryRun) {
            $this->updateState(
                $scenario,
                array_values(array_unique($updatedMeterIds)),
                array_values(array_unique($updatedGatewayIds)),
                array_values(array_unique($updatedSiteIds)),
                $endTime,
            );
        }

        return [
            'rows_inserted' => $meterRowsInserted,
            'meters_covered' => count(array_unique($updatedMeterIds)),
            'gateways_covered' => count(array_unique($updatedGatewayIds)),
        ];
    }

    /**
     * @return Collection<int, Meter>
     */
    private function metersForProfile(string $profile): Collection
    {
        $meters = Meter::query()
            ->where('meter_status', 'ACTIVE')
            ->orderBy('meter_id')
            ->get();

        return match ($profile) {
            self::PROFILE_MINIMAL => $meters->take(12),
            self::PROFILE_HEAVY => $meters,
            default => $meters->take(64),
        };
    }

    /**
     * @param  Collection<int, int>  $gatewayIds
     * @return array<string, array<int, int>>
     */
    private function gatewayGroups(Collection $gatewayIds): array
    {
        $offlineGatewayCount = max(1, (int) floor(count($gatewayIds) * 0.1));
        $pendingGatewayCount = max(1, (int) floor(count($gatewayIds) * 0.15));

        return [
            'offline_recovery_gateways' => $gatewayIds->take($offlineGatewayCount)->values()->toArray(),
            'pending_flag_gateways' => $gatewayIds->slice($offlineGatewayCount, $pendingGatewayCount)->values()->toArray(),
        ];
    }

    /**
     * @param  array<int, int>  $offlineRecoveryGateways
     */
    private function shouldSkipTelemetry(
        string $scenario,
        int $step,
        int $steps,
        Meter $meter,
        array $offlineRecoveryGateways,
        int $midpointStep,
    ): bool {
        if ($scenario !== 'offline-recovery') {
            return false;
        }

        if ($step > $midpointStep) {
            return false;
        }

        return in_array((int) $meter->rtu_idx, $offlineRecoveryGateways, true);
    }

    private function applyScenarioGatewayState(
        int $step,
        int $steps,
        array $offlineRecoveryGateways,
        array $pendingFlagGateways,
        string $scenario,
        bool $dryRun,
    ): void {
        if ($dryRun || $offlineRecoveryGateways === [] && $pendingFlagGateways === []) {
            return;
        }

        if ($scenario === 'offline-recovery') {
            $now = CarbonImmutable::now()->toDateTimeString();
            $staleOffsetMinutes = max(1, $steps * 2) * 15;
            Gateway::query()
                ->whereIn('rtu_id', $offlineRecoveryGateways)
                ->update([
                    'last_log_update' => CarbonImmutable::now()->subMinutes($staleOffsetMinutes)->toDateTimeString(),
                    'soft_rev' => '2.09',
                ]);

            if ($step === $steps) {
                Gateway::query()
                    ->whereIn('rtu_id', $offlineRecoveryGateways)
                    ->update([
                        'last_log_update' => $now,
                        'soft_rev' => '2.12',
                    ]);
            }
        }

        if ($scenario === 'normal' || $scenario === 'report-window') {
            $isPendingWindow = ($scenario === 'normal' && ($step % 3 === 0));

            Gateway::query()
                ->whereIn('rtu_id', $pendingFlagGateways)
                ->update([
                    'update_rtu' => $isPendingWindow ? 1 : 0,
                    'update_rtu_location' => $isPendingWindow ? 1 : 0,
                    'update_rtu_ssh' => $isPendingWindow ? 1 : 0,
                    'update_rtu_force_lp' => ($step === 1) ? 1 : (($step % 5 === 0) ? 1 : 0),
                ]);
        }
    }

    private function updateState(
        string $scenario,
        array $meterIds,
        array $gatewayIds,
        array $siteIds,
        CarbonImmutable $timestamp,
    ): void {
        if ($meterIds !== []) {
            Meter::query()
                ->whereIn('meter_id', $meterIds)
                ->update([
                    'last_log_update' => $timestamp->toDateTimeString(),
                    'soft_rev' => $scenario === 'report-window' ? '2.12' : '2.10',
                ]);
        }

        if ($gatewayIds !== []) {
            Gateway::query()
                ->whereIn('rtu_id', $gatewayIds)
                ->update([
                    'last_log_update' => $timestamp->toDateTimeString(),
                    'soft_rev' => $scenario === 'report-window' ? '2.12' : '2.10',
                ]);
        }

        if ($siteIds !== []) {
            Site::query()
                ->whereIn('site_id', $siteIds)
                ->update(['last_log_update' => $timestamp->toDateTimeString()]);
        }

        if ($scenario === 'report-window') {
            $this->seedReportWindowHints($timestamp, $siteIds);
        }
    }

    private function validateProfile(string $profile): string
    {
        $normalized = strtolower(trim($profile));

        if (! in_array($normalized, self::SUPPORTED_PROFILES, true)) {
            throw new InvalidArgumentException(sprintf('Unsupported profile: %s', $profile));
        }

        return $normalized;
    }

    private function validateScenario(string $scenario): string
    {
        $normalized = strtolower(trim($scenario));

        if (! in_array($normalized, self::SUPPORTED_SCENARIOS, true)) {
            throw new InvalidArgumentException(sprintf('Unsupported scenario: %s', $scenario));
        }

        return $normalized;
    }

    private function validateSpeed(string $speed): string
    {
        $normalized = strtolower(trim($speed));

        if (! array_key_exists($normalized, self::SPEED_TO_MINUTES)) {
            throw new InvalidArgumentException(sprintf('Unsupported speed: %s', $speed));
        }

        return $normalized;
    }

    private function resolveDeterministicAnchor(?string $anchor): CarbonImmutable
    {
        $rawAnchor = trim((string) ($anchor === null || $anchor === '' ? self::DETERMINISTIC_DEFAULT_ANCHOR : $anchor));
        $parsed = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $rawAnchor);
        $parseErrors = \DateTimeImmutable::getLastErrors();

        if (! $parsed instanceof \DateTimeImmutable) {
            throw new InvalidArgumentException(sprintf('Invalid anchor format: %s. Expected Y-m-d H:i:s', $rawAnchor));
        }

        if ($parseErrors !== false && ($parseErrors['error_count'] > 0 || $parseErrors['warning_count'] > 0)) {
            throw new InvalidArgumentException(sprintf('Invalid anchor format: %s. Expected Y-m-d H:i:s', $rawAnchor));
        }

        return CarbonImmutable::instance($parsed)->startOfMinute();
    }

    private function seedReportWindowHints(CarbonImmutable $timestamp, array $siteIds): void
    {
        if ($siteIds === []) {
            return;
        }

        $active = min(8, count($siteIds));

        Site::query()
            ->whereIn('site_id', array_slice($siteIds, 0, $active))
            ->update([
                'last_log_update' => $timestamp->toDateTimeString(),
            ]);
    }

    private function telemetryRow(Meter $meter, int $index, int $step, CarbonImmutable $timestamp): array
    {
        $baseOffset = (($meter->meter_id % 97) + ($step * 11) + $index) % 100;

        return [
            'location' => (string) $meter->location_idx,
            'meter_id' => (string) $meter->meter_id,
            'datetime' => $timestamp->toDateTimeString(),
            'vrms_a' => 220 + ($baseOffset % 16),
            'vrms_b' => 219 + (($baseOffset + 2) % 15),
            'vrms_c' => 218 + (($baseOffset + 4) % 15),
            'irms_a' => 3 + (($baseOffset + 1) % 5),
            'irms_b' => 3 + (($baseOffset + 3) % 4),
            'irms_c' => 2 + (($baseOffset + 2) % 4),
            'freq' => 59.85 + (($baseOffset % 5) * 0.02),
            'pf' => 0.91 + (($baseOffset % 10) * 0.007),
            'watt' => 750 + (($baseOffset * 3) % 300),
            'va' => 920 + (($baseOffset * 2) % 220),
            'var' => -180 + (($baseOffset * 3) % 360),
            'wh_del' => 41000 + $baseOffset * 2,
            'wh_rec' => 27000 + $baseOffset * 3,
            'wh_net' => 14000 + ($baseOffset % 100),
            'wh_total' => 109000 + $baseOffset * 15,
            'varh_neg' => 250 + ($baseOffset % 20),
            'varh_pos' => 360 + (($baseOffset + 7) % 22),
            'varh_net' => 410 + (($baseOffset + 3) % 30),
            'varh_total' => 860 + (($baseOffset + 6) % 27),
            'vah_total' => 740 + (($baseOffset + 10) % 20),
            'max_rec_kw_dmd' => 1.5 + (($baseOffset + $step) * 0.08),
            'max_rec_kw_dmd_time' => $timestamp->toDateTimeString(),
            'max_del_kw_dmd' => 1.3 + (($baseOffset + $step) * 0.07),
            'max_del_kw_dmd_time' => $timestamp->toDateTimeString(),
            'max_pos_kvar_dmd' => 0.5 + (($baseOffset + $step) % 12) * 0.05,
            'max_pos_kvar_dmd_time' => $timestamp->toDateTimeString(),
            'max_neg_kvar_dmd' => 0.31 + (($baseOffset + $step) % 10) * 0.04,
            'max_neg_kvar_dmd_time' => $timestamp->toDateTimeString(),
            'v_ph_angle_a' => 2.5 + (($baseOffset + $step) % 14) * 0.01,
            'v_ph_angle_b' => 2.7 + (($baseOffset + $step + 1) % 14) * 0.01,
            'v_ph_angle_c' => 2.9 + (($baseOffset + $step + 2) % 14) * 0.01,
            'i_ph_angle_a' => 0.6 + (($baseOffset + $step) % 9) * 0.02,
            'i_ph_angle_b' => 0.8 + (($baseOffset + $step) % 9) * 0.02,
            'i_ph_angle_c' => 1.0 + (($baseOffset + $step) % 9) * 0.02,
            'mac_addr' => (string) Gateway::query()
                ->where('rtu_id', (int) $meter->rtu_idx)
                ->value('gateway_mac'),
            'soft_rev' => ($step % 2 === 0) ? '2.12' : '2.10',
            'relay_status' => ($step % 3 === 0) ? 1 : 0,
            'dt' => $timestamp,
            'genset_status' => ($index % 12 === 0) ? 1 : 0,
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ];
    }

    private function parseDurationMinutes(string $durationInput): int
    {
        $duration = trim($durationInput);
        if ($duration === '') {
            return 60;
        }

        $duration = strtolower($duration);
        $matches = [];
        if (! preg_match('/^(\\d+)\\s*(m|min|minutes|h|hours|s|sec|seconds)$/', $duration, $matches)) {
            return 60;
        }

        $value = (int) $matches[1];
        $unit = $matches[2];

        return match ($unit) {
            'h', 'hours' => $value * 60,
            's', 'sec', 'seconds' => max(1, (int) round($value / 60)),
            default => $value,
        };
    }
}
