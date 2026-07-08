<?php

declare(strict_types=1);

namespace App\Actions\Ui;

use App\Actions\Rtu\IngestRtuTelemetryAction;
use App\Models\Building;
use App\Models\Gateway;
use App\Models\Meter;
use App\Models\Site;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use InvalidArgumentException;

final class SimulateTelemetryAction
{
    public function __construct(
        private readonly IngestRtuTelemetryAction $ingestRtuTelemetry,
    ) {}

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
        'analytics-demo',
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

        if ($scenario === 'analytics-demo') {
            return $this->seedAnalyticsDemoTelemetry($meters, $dryRun, $anchor);
        }

        if ($durationMinutes % $stepMinutes === 0) {
            $steps = max(1, (int) ($durationMinutes / $stepMinutes));
        }

        $gatewayMacs = Gateway::query()
            ->whereIn('rtu_id', $meters->pluck('rtu_idx')->unique()->values())
            ->pluck('gateway_mac', 'rtu_id')
            ->all();
        $buildingCodes = Site::query()
            ->join('meter_building_table', 'meter_site.building_idx', '=', 'meter_building_table.building_id')
            ->whereIn('meter_site.site_id', $meters->pluck('site_idx')->unique()->values())
            ->pluck('meter_building_table.building_code', 'meter_site.site_id')
            ->all();

        $gatewayGroups = $this->gatewayGroups($meters->pluck('rtu_idx')->unique()->values());
        $persistentOfflineGateways = $gatewayGroups['persistent_offline_gateways'];
        $recoveryGateways = $gatewayGroups['recovery_gateways'];
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
                $persistentOfflineGateways,
                $recoveryGateways,
                $pendingFlagGateways,
                $scenario,
                $dryRun,
            );

            $meterList = $meters->values();

            foreach ($meterList as $index => $meter) {
                if ($this->shouldSkipTelemetry(
                    $scenario,
                    $step,
                    $steps,
                    $meter,
                    $persistentOfflineGateways,
                    $recoveryGateways,
                    $midpointStep,
                )) {
                    continue;
                }

                $rows[] = $this->telemetryRow($meter, $index, $step, $timestamp, $buildingCodes, $gatewayMacs);
                $updatedMeterIds[] = (int) $meter->meter_id;
                $updatedGatewayIds[] = (int) $meter->rtu_idx;
                $updatedSiteIds[] = (int) $meter->site_idx;
            }

            if (! $dryRun && $rows !== []) {
                $meterRowsInserted += $this->persistTelemetryRows($rows);
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

            if ($scenario === 'offline-recovery' && $persistentOfflineGateways !== []) {
                Gateway::query()
                    ->whereIn('rtu_id', $persistentOfflineGateways)
                    ->update([
                        'last_log_update' => $timeAnchor->subMinutes(180)->toDateTimeString(),
                        'soft_rev' => '2.09',
                    ]);
            }
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
            'persistent_offline_gateways' => $gatewayIds->take(min(1, $offlineGatewayCount))->values()->toArray(),
            'recovery_gateways' => $gatewayIds->slice(min(1, $offlineGatewayCount), max(0, $offlineGatewayCount - 1))->values()->toArray(),
            'pending_flag_gateways' => $gatewayIds->slice($offlineGatewayCount, $pendingGatewayCount)->values()->toArray(),
        ];
    }

    /**
     * @param  array<int, int>  $persistentOfflineGateways
     * @param  array<int, int>  $recoveryGateways
     */
    private function shouldSkipTelemetry(
        string $scenario,
        int $step,
        int $steps,
        Meter $meter,
        array $persistentOfflineGateways,
        array $recoveryGateways,
        int $midpointStep,
    ): bool {
        if (in_array((int) $meter->rtu_idx, $persistentOfflineGateways, true)) {
            return true;
        }

        if ($scenario !== 'offline-recovery') {
            return false;
        }

        if (! in_array((int) $meter->rtu_idx, $recoveryGateways, true)) {
            return false;
        }

        return $step <= $midpointStep;
    }

    private function applyScenarioGatewayState(
        int $step,
        int $steps,
        array $persistentOfflineGateways,
        array $recoveryGateways,
        array $pendingFlagGateways,
        string $scenario,
        bool $dryRun,
    ): void {
        if ($dryRun || ($persistentOfflineGateways === [] && $recoveryGateways === [] && $pendingFlagGateways === [])) {
            return;
        }

        if ($scenario === 'offline-recovery') {
            $now = CarbonImmutable::now()->toDateTimeString();
            $staleOffsetMinutes = max(180, max(1, $steps * 2) * 15);
            $offlineScenarioGateways = array_values(array_unique([
                ...$persistentOfflineGateways,
                ...$recoveryGateways,
            ]));

            Gateway::query()
                ->whereIn('rtu_id', $offlineScenarioGateways)
                ->update([
                    'last_log_update' => CarbonImmutable::now()->subMinutes($staleOffsetMinutes)->toDateTimeString(),
                    'soft_rev' => '2.09',
                ]);

            if ($step === $steps && $recoveryGateways !== []) {
                Gateway::query()
                    ->whereIn('rtu_id', $recoveryGateways)
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

    /**
     * @param  Collection<int, Meter>  $meters
     * @return array<string, int>
     */
    private function seedAnalyticsDemoTelemetry(Collection $meters, bool $dryRun, ?string $anchor): array
    {
        $selectedBuildingIds = $meters
            ->filter(fn (Meter $meter): bool => $meter->building_idx !== null && $meter->building_idx !== 0)
            ->unique('building_idx')
            ->pluck('building_idx')
            ->take(4)
            ->values();

        if ($selectedBuildingIds->isEmpty()) {
            return [
                'rows_inserted' => 0,
                'meters_covered' => 0,
                'gateways_covered' => 0,
            ];
        }

        $selectedMeters = $meters
            ->whereIn('building_idx', $selectedBuildingIds->all())
            ->values();

        $buildingCodes = Building::query()
            ->whereIn('building_id', $selectedBuildingIds->all())
            ->pluck('building_code', 'building_id')
            ->all();

        $gatewayMacs = Gateway::query()
            ->whereIn('rtu_id', $selectedMeters->pluck('rtu_idx')->unique()->values())
            ->pluck('gateway_mac', 'rtu_id')
            ->all();

        $baseDate = $this->resolveDeterministicAnchor($anchor)->startOfDay();
        $rows = [];
        $rowsInserted = 0;
        $updatedMeterIds = [];
        $updatedGatewayIds = [];
        $updatedSiteIds = [];

        foreach ($selectedBuildingIds as $index => $buildingId) {
            $buildingMeters = $selectedMeters
                ->where('building_idx', $buildingId)
                ->values();

            foreach ($buildingMeters as $meterIndex => $meter) {
                $buildingCode = (string) ($buildingCodes[$meter->building_idx] ?? $meter->site_code);
                $gatewayMac = (string) ($gatewayMacs[$meter->rtu_idx] ?? '');

                $rows = [
                    ...$rows,
                    ...match ($index) {
                        0 => $this->analyticsDemoWindowRows(
                            meter: $meter,
                            buildingCode: $buildingCode,
                            gatewayMac: $gatewayMac,
                            start: $baseDate,
                            startingWhTotal: 100000 + ($meterIndex * 10000),
                            hourlyDeltas: $this->normalConsumptionPattern(),
                        ),
                        1 => $this->analyticsDemoWindowRows(
                            meter: $meter,
                            buildingCode: $buildingCode,
                            gatewayMac: $gatewayMac,
                            start: $baseDate,
                            startingWhTotal: 200000 + ($meterIndex * 10000),
                            hourlyDeltas: $this->abnormalHighConsumptionPattern(),
                        ),
                        2 => [
                            $this->analyticsDemoTelemetryRow($meter, $buildingCode, $gatewayMac, $baseDate, 300000 + ($meterIndex * 10000), 620),
                        ],
                        default => $this->analyticsDemoWindowRows(
                            meter: $meter,
                            buildingCode: $buildingCode,
                            gatewayMac: $gatewayMac,
                            start: $baseDate,
                            startingWhTotal: 400000 + ($meterIndex * 10000),
                            hourlyDeltas: array_fill(0, 24, 0),
                        ),
                    },
                ];

                $updatedMeterIds[] = (int) $meter->meter_id;
                $updatedGatewayIds[] = (int) $meter->rtu_idx;
                $updatedSiteIds[] = (int) $meter->site_idx;
            }
        }

        if (! $dryRun && $rows !== []) {
            $rowsInserted = $this->persistTelemetryRows($rows);
            $lastTimestamp = $baseDate->addHours(23)->addMinutes(55);
            $this->updateState(
                'report-window',
                array_values(array_unique($updatedMeterIds)),
                array_values(array_unique($updatedGatewayIds)),
                array_values(array_unique($updatedSiteIds)),
                $lastTimestamp,
            );
        }

        return [
            'rows_inserted' => $dryRun ? 0 : $rowsInserted,
            'meters_covered' => count(array_unique($updatedMeterIds)),
            'gateways_covered' => count(array_unique($updatedGatewayIds)),
        ];
    }

    /**
     * @return list<int>
     */
    private function normalConsumptionPattern(): array
    {
        return [
            48, 46, 44, 42, 45, 55,
            72, 88, 96, 102, 108, 112,
            118, 116, 110, 104, 98, 86,
            74, 66, 58, 54, 51, 49,
        ];
    }

    /**
     * @return list<int>
     */
    private function abnormalHighConsumptionPattern(): array
    {
        return [
            72, 70, 68, 66, 74, 98,
            130, 168, 210, 252, 286, 310,
            342, 360, 330, 292, 248, 206,
            170, 142, 116, 98, 86, 78,
        ];
    }

    /**
     * @return list<array<string, int|float|string|CarbonImmutable>>
     */
    private function analyticsDemoWindowRows(
        Meter $meter,
        string $buildingCode,
        string $gatewayMac,
        CarbonImmutable $start,
        int $startingWhTotal,
        array $hourlyDeltas,
    ): array {
        $rows = [];
        $runningWhTotal = $startingWhTotal;

        foreach ($hourlyDeltas as $hour => $delta) {
            $windowStart = $start->addHours($hour);
            $rows[] = $this->analyticsDemoTelemetryRow($meter, $buildingCode, $gatewayMac, $windowStart, $runningWhTotal, $delta);

            $runningWhTotal += $delta;
            $rows[] = $this->analyticsDemoTelemetryRow($meter, $buildingCode, $gatewayMac, $windowStart->addMinutes(55), $runningWhTotal, $delta);
        }

        return $rows;
    }

    /**
     * @return array<string, int|float|string|CarbonImmutable>
     */
    private function analyticsDemoTelemetryRow(
        Meter $meter,
        string $buildingCode,
        string $gatewayMac,
        CarbonImmutable $timestamp,
        int $whTotal,
        int $hourlyDelta,
    ): array {
        $baseLoad = max(1, $hourlyDelta);

        return [
            'location' => $buildingCode,
            'meter_id' => (string) $meter->meter_name,
            'datetime' => $timestamp->toDateTimeString(),
            'vrms_a' => 228 + ($hourlyDelta % 5),
            'vrms_b' => 226 + ($hourlyDelta % 4),
            'vrms_c' => 227 + ($hourlyDelta % 3),
            'irms_a' => round(2.5 + ($baseLoad / 100), 3),
            'irms_b' => round(2.4 + ($baseLoad / 110), 3),
            'irms_c' => round(2.3 + ($baseLoad / 120), 3),
            'freq' => 59.92 + (($hourlyDelta % 4) * 0.01),
            'pf' => 0.92 + (($hourlyDelta % 6) * 0.01),
            'watt' => $baseLoad * 16,
            'va' => $baseLoad * 18,
            'var' => $baseLoad * 4,
            'wh_del' => $whTotal,
            'wh_rec' => 0,
            'wh_net' => $whTotal,
            'wh_total' => $whTotal,
            'varh_neg' => $baseLoad,
            'varh_pos' => $baseLoad * 2,
            'varh_net' => $baseLoad * 3,
            'varh_total' => $baseLoad * 4,
            'vah_total' => $baseLoad * 5,
            'max_rec_kw_dmd' => round($baseLoad / 100, 3),
            'max_rec_kw_dmd_time' => $timestamp->toDateTimeString(),
            'max_del_kw_dmd' => round($baseLoad / 110, 3),
            'max_del_kw_dmd_time' => $timestamp->toDateTimeString(),
            'max_pos_kvar_dmd' => round($baseLoad / 300, 3),
            'max_pos_kvar_dmd_time' => $timestamp->toDateTimeString(),
            'max_neg_kvar_dmd' => round($baseLoad / 400, 3),
            'max_neg_kvar_dmd_time' => $timestamp->toDateTimeString(),
            'v_ph_angle_a' => 0.1,
            'v_ph_angle_b' => 0.2,
            'v_ph_angle_c' => 0.3,
            'i_ph_angle_a' => 1.1,
            'i_ph_angle_b' => 1.2,
            'i_ph_angle_c' => 1.3,
            'mac_addr' => $gatewayMac,
            'soft_rev' => '2.12',
            'relay_status' => 0,
            'dt' => $timestamp,
            'genset_status' => 0,
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function persistTelemetryRows(array $rows): int
    {
        $saved = 0;

        foreach ($rows as $row) {
            $result = $this->ingestRtuTelemetry->execute([
                ...$row,
                'save_to_meter_data' => 1,
                'gateway_mac' => (string) ($row['mac_addr'] ?? ''),
            ]);

            if ($result['saved']) {
                $saved++;
            }
        }

        return $saved;
    }

    /**
     * @param  array<int, string>  $buildingCodes
     * @param  array<int, string>  $gatewayMacs
     * @return array<string, int|float|string|CarbonImmutable>
     */
    private function telemetryRow(
        Meter $meter,
        int $index,
        int $step,
        CarbonImmutable $timestamp,
        array $buildingCodes,
        array $gatewayMacs,
    ): array {
        $baseOffset = (($meter->meter_id % 97) + ($step * 11) + $index) % 100;

        return [
            'location' => (string) ($buildingCodes[$meter->site_idx] ?? $meter->site_code),
            'meter_id' => (string) $meter->meter_name,
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
            'mac_addr' => (string) ($gatewayMacs[$meter->rtu_idx] ?? ''),
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
