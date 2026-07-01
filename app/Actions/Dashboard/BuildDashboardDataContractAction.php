<?php

declare(strict_types=1);

namespace App\Actions\Dashboard;

use App\Models\Gateway;
use App\Models\Meter;
use App\Models\MeterData;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class BuildDashboardDataContractAction
{
    private const ONLINE_THRESHOLD_MINUTES = 30;

    private const OFFLINE_THRESHOLD_MINUTES = 120;

    private const REPORT_READY_WINDOW_HOURS = 24;

    /**
     * @return array<string, mixed>
     */
    public function execute(User $user): array
    {
        $now = CarbonImmutable::now();
        $onlineCutoff = $now->subMinutes(self::ONLINE_THRESHOLD_MINUTES);
        $offlineCutoff = $now->subMinutes(self::OFFLINE_THRESHOLD_MINUTES);
        $reportCutoff = $now->subHours(self::REPORT_READY_WINDOW_HOURS);

        $gatewayBaseQuery = Gateway::query();
        $activeMeterBaseQuery = Meter::query()->whereRaw('UPPER(meter_status) = ?', ['ACTIVE']);
        $recentTelemetryBaseQuery = MeterData::query()->where('datetime', '>=', $onlineCutoff->toDateTimeString());
        $reportTelemetryBaseQuery = MeterData::query()->where('datetime', '>=', $reportCutoff->toDateTimeString());

        $lastReceivedAt = MeterData::query()->max('datetime');
        $recentReadings = (clone $recentTelemetryBaseQuery)->count();
        $recentActiveMeters = (clone $recentTelemetryBaseQuery)->distinct()->count('meter_id');
        $recentSitesWithTelemetry = DB::table('meter_data')
            ->join('meter_details', 'meter_data.meter_id', '=', 'meter_details.meter_id')
            ->where('meter_data.datetime', '>=', $reportCutoff->toDateTimeString())
            ->distinct()
            ->count('meter_details.site_idx');
        $historicTelemetryCount = MeterData::query()->count();
        $reportWindowReadings = (clone $reportTelemetryBaseQuery)->count();
        $reportWindowMeters = (clone $reportTelemetryBaseQuery)->distinct()->count('meter_id');

        return [
            'context' => [
                'user' => [
                    'id' => $user->id,
                    'name' => (string) ($user->user_real_name ?: $user->name),
                    'role' => (string) ($user->user_type ?: 'User'),
                    'access' => (string) ($user->user_access ?: 'Selected'),
                ],
                'generatedAt' => $now->toIso8601String(),
            ],
            'gatewaySummary' => [
                'total' => (clone $gatewayBaseQuery)->count(),
                ...$this->healthCounts($gatewayBaseQuery, $onlineCutoff, $offlineCutoff),
            ],
            'meterSummary' => [
                'total' => Meter::query()->count(),
                'active' => (clone $activeMeterBaseQuery)->count(),
                ...$this->healthCounts($activeMeterBaseQuery, $onlineCutoff, $offlineCutoff),
            ],
            'telemetrySummary' => [
                'recentReadings' => $recentReadings,
                'activeMeters' => $recentActiveMeters,
                'lastReceivedAt' => is_string($lastReceivedAt) && $lastReceivedAt !== ''
                    ? CarbonImmutable::parse($lastReceivedAt)->toIso8601String()
                    : null,
            ],
            'pendingUpdateSummary' => [
                'total' => Gateway::query()
                    ->where(function (Builder $query): void {
                        $query
                            ->where('update_rtu', 1)
                            ->orWhere('update_rtu_location', 1)
                            ->orWhere('update_rtu_ssh', 1)
                            ->orWhere('update_rtu_force_lp', 1);
                    })
                    ->count(),
                'csv' => Gateway::query()->where('update_rtu', 1)->count(),
                'location' => Gateway::query()->where('update_rtu_location', 1)->count(),
                'ssh' => Gateway::query()->where('update_rtu_ssh', 1)->count(),
                'forceLoadProfile' => Gateway::query()->where('update_rtu_force_lp', 1)->count(),
            ],
            'reportReadiness' => [
                'raw' => [
                    'state' => $this->reportState($reportWindowReadings, $historicTelemetryCount),
                    'recentReadings' => $reportWindowReadings,
                    'lastReceivedAt' => is_string($lastReceivedAt) && $lastReceivedAt !== ''
                        ? CarbonImmutable::parse($lastReceivedAt)->toIso8601String()
                        : null,
                ],
                'consumption' => [
                    'state' => $this->reportState($reportWindowReadings, $historicTelemetryCount),
                    'recentReadings' => $reportWindowReadings,
                    'activeMeters' => $reportWindowMeters,
                ],
                'demand' => [
                    'state' => $this->reportState($reportWindowReadings, $historicTelemetryCount),
                    'recentReadings' => $reportWindowReadings,
                    'activeMeters' => $reportWindowMeters,
                ],
                'sap' => [
                    'state' => $this->reportState($recentSitesWithTelemetry, $historicTelemetryCount),
                    'sitesWithRecentTelemetry' => $recentSitesWithTelemetry,
                    'recentReadings' => $reportWindowReadings,
                ],
                'site' => [
                    'state' => $this->reportState($recentSitesWithTelemetry, $historicTelemetryCount),
                    'sitesWithRecentTelemetry' => $recentSitesWithTelemetry,
                    'recentReadings' => $reportWindowReadings,
                ],
            ],
        ];
    }

    /**
     * @return array{online: int, stale: int, offline: int}
     */
    private function healthCounts(Builder $query, CarbonImmutable $onlineCutoff, CarbonImmutable $offlineCutoff): array
    {
        $onlineCutoffValue = $onlineCutoff->toDateTimeString();
        $offlineCutoffValue = $offlineCutoff->toDateTimeString();

        return [
            'online' => (clone $query)->where('last_log_update', '>=', $onlineCutoffValue)->count(),
            'stale' => (clone $query)
                ->whereNotNull('last_log_update')
                ->where('last_log_update', '<', $onlineCutoffValue)
                ->where('last_log_update', '>=', $offlineCutoffValue)
                ->count(),
            'offline' => (clone $query)
                ->where(function (Builder $builder) use ($offlineCutoffValue): void {
                    $builder
                        ->whereNull('last_log_update')
                        ->orWhere('last_log_update', '<', $offlineCutoffValue);
                })
                ->count(),
        ];
    }

    private function reportState(int $recentSignalCount, int $historicSignalCount): string
    {
        if ($recentSignalCount > 0) {
            return 'ready';
        }

        if ($historicSignalCount > 0) {
            return 'partial';
        }

        return 'empty';
    }
}
